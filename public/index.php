<?php
require __DIR__ . '/../vendor/autoload.php';

use Dotenv\Dotenv;
use Okea\Backend\Config\Database;
use Okea\Backend\Controllers\AuthController;

header('Content-Type: application/json');

$dotenv = Dotenv::createImmutable(__DIR__ . '/../');
$dotenv->load();

$db = (new Database())->connect();

$method = $_SERVER['REQUEST_METHOD'];
$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$body = json_decode(file_get_contents('php://input'), true) ?? [];

$ctrl = new AuthController($db);

switch (true) {
    case $uri === '/' && $method === 'GET':
        echo json_encode(["message" => "Backend funcionando 🚀"]);
        break;

    case $uri === '/auth/register' && $method === 'POST':
        echo json_encode($ctrl->register($body));
        break;

    case $uri === '/auth/login' && $method === 'POST':
        echo json_encode($ctrl->login($body));
        break;

    case $uri === '/auth/refresh' && $method === 'POST':
        $authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
        $token = str_replace('Bearer ', '', $authHeader);
        echo json_encode($ctrl->refresh($token));
        break;

    case $uri === '/auth/logout' && $method === 'POST':
        echo json_encode($ctrl->logout());
        break;

    default:
        http_response_code(404);
        echo json_encode(["error" => "Ruta no encontrada"]);
}