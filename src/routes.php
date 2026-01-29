<?php

declare(strict_types=1);

/** @var \Bramus\Router\Router $router */

$router->setNamespace('App\Controller');

$router->mount('/api', function () use ($router) {

    $router->post('/login', 'AuthController@login');
    $router->post('/register', 'AuthController@register');

    $router->get('/ping', function() {
        header('Content-Type: application/json');
        echo json_encode(['status' => 'pong', 'timestamp' => time()]);
        exit;
    });
});

$router->before('GET|POST|PUT|PATCH|DELETE', '/api/tickets.*', function () {

    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    if (!isset($_SESSION['user_id'])) {
        header('Content-Type: application/json');
        http_response_code(401);
        echo json_encode([
            'error' => 'Unauthorized',
            'message' => 'Please login to access this resource'
        ]);
        exit;
    }
});

$router->mount('/api', function () use ($router) {

    $router->post('/logout', 'AuthController@logout');

    $router->get('/tickets', 'TicketController@index');
    $router->post('/tickets', 'TicketController@store');
    $router->get('/tickets/(\d+)', 'TicketController@show');

    $router->patch('/tickets/(\d+)/status', 'TicketController@updateStatus');
});

$router->set404(function () {
    header('HTTP/1.1 404 Not Found');
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Route not found']);
    exit;
});