<?php
namespace Okea\Backend\Controllers;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Okea\Backend\Models\User;
use PDO;

class AuthController {
    private PDO $db;

    public function __construct(PDO $db) {
        $this->db = $db;
    }
    public function register(array $body): array {
        $nombre = trim($body['nombre'] ?? '');
        $email  = strtolower(trim($body['email'] ?? ''));
        $pass   = $body['password'] ?? '';

        if (!$nombre || !$email || !$pass) {
            http_response_code(400);
            return ["error" => "nombre, email y password son requeridos"];
        }

        $userModel = new User($this->db);

        if ($userModel->findByEmail($email)) {
            http_response_code(409);
            return ["error" => "El email ya está registrado"];
        }

        $hash = password_hash($pass, PASSWORD_DEFAULT);
        $userId = $userModel->create($nombre, $email, $hash);
        $userModel->assignDefaultRole($userId);

        http_response_code(201);
        return ["message" => "Usuario creado", "id" => $userId];
    }
    public function login(array $body): array {
        $email = strtolower(trim($body['email'] ?? ''));
        $pass  = $body['password'] ?? '';

        if (!$email || !$pass) {
            http_response_code(400);
            return ["error" => "email y password son requeridos"];
        }

        $userModel = new User($this->db);
        $user = $userModel->findByEmail($email);

        if (!$user || !password_verify($pass, $user['password'])) {
            http_response_code(401);
            return ["error" => "Credenciales inválidas"];
        }

        $roles = $userModel->getRoles((int)$user['id']);

        $payload = [
            "sub"   => (int)$user['id'],
            "email" => $user['email'],
            "roles" => $roles,
            "iat"   => time(),
            "exp"   => time() + (60 * 60 * 24 * 7) // 7 días
        ];

        $token = JWT::encode($payload, $_ENV['JWT_SECRET'], 'HS256');

        return [
            "message" => "Login exitoso",
            "token"   => $token,
            "usuario" => [
                "id"    => (int)$user['id'],
                "nombre"=> $user['nombre'],
                "email" => $user['email'],
                "roles" => $roles
            ]
        ];
    }
    public function refresh(string $token): array {
        try {
            $decoded = JWT::decode($token, new Key($_ENV['JWT_SECRET'], 'HS256'));

            $payload = [
                "sub"   => $decoded->sub,
                "email" => $decoded->email,
                "roles" => $decoded->roles,
                "iat"   => time(),
                "exp"   => time() + (60 * 60 * 24 * 7)
            ];

            $newToken = JWT::encode($payload, $_ENV['JWT_SECRET'], 'HS256');

            return ["token" => $newToken];
        } catch (\Exception $e) {
            http_response_code(401);
            return ["error" => "Token inválido o expirado"];
        }
    }

    public function logout(): array {
        // En PHP puro no se manejan cookies httpOnly como en Express tan fácil,
        // pero se puede limpiar en el cliente o invalidar tokens en DB si implementas blacklist.
        return ["message" => "Logout realizado (el cliente debe descartar el token)"];
    }
}