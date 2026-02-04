const API_URL = '/api';
let currentUser = null;
let searchTimeout;
const ticketModal = new bootstrap.Modal(document.getElementById('ticketModal'));

const AVAILABLE_TAGS = ['Bug', 'Feature', 'Urgent'];

document.addEventListener('DOMContentLoaded', () => {
    checkSession();
});

async function handleLogin(e) {
    e.preventDefault();
    const login = document.getElementById('login').value;
    const password = document.getElementById('login-password').value;

    showLoader(true);
    try {
        const res = await fetch(`${API_URL}/login`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ login, password })
        });

        if (res.ok) {
            const data = await res.json();
            localStorage.setItem('user', JSON.stringify(data.user));
            checkSession();
        } else {
            alert('Ошибка входа! Проверьте данные.');
        }
    } catch (err) {
        console.error(err);
        alert('Ошибка сети');
    } finally {
        showLoader(false);
    }
}

function checkSession() {
    const userStr = localStorage.getItem('user');
    if (userStr) {
        currentUser = JSON.parse(userStr);

        document.getElementById('auth-screen').classList.add('hidden');
        document.getElementById('dashboard-screen').classList.remove('hidden');
        document.getElementById('navbar').classList.remove('hidden');

        document.getElementById('user-display').innerText = currentUser.login;

        setupRoleBasedUI();

        loadTickets(1);
    } else {
        showLoginScreen();
    }
}

function logout() {
    localStorage.removeItem('user');
    currentUser = null;

    fetch(`${API_URL}/logout`, { method: 'POST' }).catch(() => {});

    showLoginScreen();
}

function showLoginScreen() {
    document.getElementById('auth-screen').classList.remove('hidden');
    document.getElementById('dashboard-screen').classList.add('hidden');
    document.getElementById('navbar').classList.add('hidden');
}

function setupRoleBasedUI() {
    const isAdmin = currentUser.role === 'admin';
    const title = document.getElementById('dashboard-title');

    if (isAdmin) {
        title.innerText = 'Все обращения (Администратор)';
    } else {
        title.innerText = 'Мои обращения';
    }
}

function applyFiltersDebounced() {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => loadTickets(1), 300);
}

function applyFilters() {
    loadTickets(1);
}

function toggleOrder() {
    const btn = document.getElementById('btn-order-by');
    const input = document.getElementById('filter-order-by');

    if (input.value === 'DESC') {
        input.value = 'ASC';
        btn.innerText = '⬆️ Старые';
    } else {
        input.value = 'DESC';
        btn.innerText = '⬇️ Новые';
    }
    loadTickets(1);
}

async function loadTickets(page = 1) {
    const title = document.getElementById('filter-title').value;
    const status = document.getElementById('filter-status').value;
    const sortBy = document.getElementById('filter-sort-by').value;
    const orderBy = document.getElementById('filter-order-by').value;

    const params = new URLSearchParams({
        page: page,
        sort_by: sortBy,
        order_by: orderBy
    });
    if (title) params.append('title', title);
    if (status) params.append('status', status);

    showLoader(true);
    try {
        const res = await fetch(`${API_URL}/tickets?${params.toString()}`);

        if (res.ok) {
            const json = await res.json();
            const tickets = json.data || json.tickets || [];
            const meta = json.meta || {};

            renderTable(tickets);
            renderPagination(meta);
        } else if (res.status === 401 || res.status === 403) {
            logout();
        }
    } catch (e) {
        console.error(e);
    } finally {
        showLoader(false);
    }
}

function renderTable(tickets) {
    const tbody = document.getElementById('tickets-table-body');
    tbody.innerHTML = '';

    if (!tickets.length) {
        tbody.innerHTML = '<tr><td colspan="6" class="text-center py-4 text-muted">Записей не найдено</td></tr>';
        return;
    }

    tickets.forEach(t => {
        let badgeClass = 'bg-secondary';
        switch (t.status_code) {
            case 'done':
                badgeClass = 'bg-success';
                break;
            case 'review':
                badgeClass = 'bg-info text-dark';
                break;
            case 'in_progress':
                badgeClass = 'bg-warning text-dark';
                break;
            case 'todo':
            default:
                badgeClass = 'bg-primary';
                break;
        }

        let tagsHtml = '<span class="text-muted small">-</span>';
        if (t.tags && Array.isArray(t.tags) && t.tags.length > 0) {
            tagsHtml = t.tags.map(tag =>
                `<span class="badge bg-light text-dark border me-1">${escapeHtml(tag)}</span>`
            ).join('');
        }

        const row = document.createElement('tr');
        row.className = 'cursor-pointer';
        row.onclick = () => openEditModal(t);

        row.innerHTML = `
            <td>#${t.id}</td>
            <td>
                <div class="fw-bold text-truncate" style="max-width: 300px;">
                    ${escapeHtml(t.title)}
                </div>
            </td>
            <td>
                <span class="badge ${badgeClass}">${t.status_name || t.status_code}</span>
            </td>
            <td>${tagsHtml}</td>
            <td>${new Date(t.created_at).toLocaleDateString()}</td>
            <td>${escapeHtml(t.user_login)}</td>`;
        tbody.appendChild(row);
    });
}

function renderPagination(meta) {
    const container = document.getElementById('pagination-container');
    container.innerHTML = '';

    if (!meta || !meta.total_pages || meta.total_pages <= 1) return;

    const prevDisabled = meta.current_page === 1 ? 'disabled' : '';
    container.innerHTML += `
        <li class="page-item ${prevDisabled}">
            <button class="page-link" onclick="loadTickets(${meta.current_page - 1})">Previous</button>
        </li>
    `;

    for (let i = 1; i <= meta.total_pages; i++) {
        const active = i === meta.current_page ? 'active' : '';
        container.innerHTML += `
            <li class="page-item ${active}">
                <button class="page-link" onclick="loadTickets(${i})">${i}</button>
            </li>
        `;
    }

    const nextDisabled = meta.current_page === meta.total_pages ? 'disabled' : '';
    container.innerHTML += `
        <li class="page-item ${nextDisabled}">
            <button class="page-link" onclick="loadTickets(${meta.current_page + 1})">Next</button>
        </li>
    `;
}

function openCreateModal() {
    document.getElementById('ticket-form').reset();
    document.getElementById('ticket-id').value = '';
    document.getElementById('modalTitle').innerText = 'Новое обращение';

    renderTagSelection([]);

    document.getElementById('status-group').classList.add('hidden');

    document.getElementById('ticket-status-select').value = "todo";

    document.getElementById('ticket-title').disabled = false;
    document.getElementById('ticket-desc').disabled = false;

    ticketModal.show();
}

function openEditModal(ticket) {
    document.getElementById('ticket-id').value = ticket.id;
    document.getElementById('ticket-title').value = ticket.title;
    document.getElementById('ticket-desc').value = ticket.description;
    document.getElementById('modalTitle').innerText = `Обращение #${ticket.id}`;

    renderTagSelection(ticket.tags || []);

    const statusGroup = document.getElementById('status-group');
    const statusSelect = document.getElementById('ticket-status-select');

    const tagInputs = document.querySelectorAll('#tags-container input');

    statusGroup.classList.remove('hidden');

    statusSelect.value = ticket.status_code || 'new';

    if (currentUser.role === 'admin') {
        statusSelect.disabled = false;
        document.getElementById('ticket-title').disabled = false;
        document.getElementById('ticket-desc').disabled = false;
        tagInputs.forEach(input => input.disabled = false);
    } else {
        statusSelect.disabled = true;
        document.getElementById('ticket-title').disabled = true;
        document.getElementById('ticket-desc').disabled = true;
        tagInputs.forEach(input => input.disabled = true);
    }

    ticketModal.show();
}

async function saveTicket() {
    const id = document.getElementById('ticket-id').value;
    const title = document.getElementById('ticket-title').value;
    const description = document.getElementById('ticket-desc').value;

    const statusCode = document.getElementById('ticket-status-select').value;

    const checkedBoxes = document.querySelectorAll('#tags-container input:checked');
    const tags = Array.from(checkedBoxes).map(cb => cb.value);

    const payload = {
        title: title,
        description: description,
        tags: tags
    };

    let url = `${API_URL}/tickets`;
    let method = 'POST';

    if (id) {
        url = `${API_URL}/tickets/${id}`;
        method = 'PATCH';

        if (currentUser.role === 'admin') {
            payload.status_code = statusCode;
        }
    }

    showLoader(true);
    try {
        const res = await fetch(url, {
            method: method,
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload)
        });

        if (res.ok) {
            ticketModal.hide();
            loadTickets();
        } else {
            const err = await res.json();
            alert('Ошибка: ' + (err.error || 'Не удалось сохранить'));
        }
    } catch (e) {
        console.error(e);
        alert('Произошла ошибка сети');
    } finally {
        showLoader(false);
    }
}

function showLoader(show) {
    const el = document.getElementById('loader');
    if (show) el.classList.remove('hidden');
    else el.classList.add('hidden');
}

function renderTagSelection(selectedTags = []) {
    const container = document.getElementById('tags-container');
    container.innerHTML = '';

    AVAILABLE_TAGS.forEach(tagName => {
        const isChecked = selectedTags.includes(tagName) ? 'checked' : '';

        const html = `
            <input type="checkbox" class="btn-check" id="tag-btn-${tagName}" 
                   value="${tagName}" autocomplete="off" ${isChecked}>
            <label class="btn btn-outline-secondary btn-sm" for="tag-btn-${tagName}">${tagName}</label>
        `;
        container.innerHTML += html;
    });
}

function escapeHtml(text) {
    if (!text) return '';
    return String(text)
        .replace(/&/g, "&amp;")
        .replace(/</g, "&lt;")
        .replace(/>/g, "&gt;")
        .replace(/"/g, "&quot;")
        .replace(/'/g, "&#039;");
}