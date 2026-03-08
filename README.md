Ticket Tracker API (Pure PHP Architecture)

An example of a REST API and admin panel implementation in pure PHP (without using Laravel/Symfony).

🛠 Technical Stack

    Backend: PHP 8.4 (Strict Types), Pure PHP

    Database: MySQL 8.0 (PDO, Prepared Statements)

    Frontend: Vanilla JS + Bootstrap 5 (SPA-like approach)

    Infrastructure: Docker, Docker Compose (Nginx + PHP-FPM + MySQL)

🏗 Architectural Decisions

I intentionally avoided frameworks to implement a mini-core from scratch:

    MVC & Repository Pattern: Clear separation of concerns (Controller -> Service/Repository -> Database).

    Singleton Database: Single connection point via PDO.

    Security:

        SQL Injection protection (Prepared Statements).

        HttpOnly Cookies for sessions.

        Password hashing (password_hash).

    Frontend: Lightweight pure JS client (fetch API) without bundlers (Webpack/Vite) to simplify deployment and CI/CD.

🚀 Installation & Run
Bash

docker-compose up -d --build

📌 Notes

    Upon the first run, the database will automatically create the necessary tables and seed test users.

🌐 Access

    Main Page: http://localhost:8080/app.html

    API: http://localhost:8080/api

🔑 Test Accounts

    Login: admin | Password: password

    Login: user | Password: password