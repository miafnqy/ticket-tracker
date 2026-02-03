# Ticket Tracker API (Pure PHP Architecture)

Пример реализации REST API и админ-панели на чистом PHP (без использования Laravel/Symfony)

## Технический стек

- **Backend:** PHP 8.4 (Strict Types), Pure PHP
- **Database:** MySQL 8.0 (PDO, Prepared Statements)
- **Frontend:** Vanilla JS + Bootstrap 5 (SPA-like подход)
- **Infrastructure:** Docker, Docker Compose (Nginx + PHP-FPM + MySQL)

## 🏗 Архитектурные решения

Я намеренно отказался от фреймворков, чтобы реализовать mini-core с нуля:
2.  **MVC & Repository Pattern:** Четкое разделение ответственности (Controller -> Service/Repository -> Database).
3.  **Singleton Database:** Единая точка подключения через PDO.
4.  **Security:**
    - Защита от SQL Injection (Prepared Statements).
    - HttpOnly Cookies для сессий.
    - Хеширование паролей (`password_hash`).
5.  **Frontend:** Легковесный клиент на чистом JS (fetch API) без сборщиков (Webpack/Vite), чтобы упростить деплой и CI/CD.

## 🚀 Установка и запуск

- **docker-compose up -d --build**

## Примечания

- **При первом запуске база данных автоматически создаст таблицы и тестовых пользователей**

## Доступы

- **Main Page: http://localhost:8080/app.html**
- **API: http://localhost:8080/api**

## Тестовые аккаунты

- **admin password**
- **user password**