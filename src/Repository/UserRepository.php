<?php
namespace App\Repository;

use App\Service\DatabaseService;
use PDO;

class UserRepository
{
    private PDO $pdo;
    public function __construct(DatabaseService $db)
    {
        $this->pdo = $db->getPdo();
    }

    public function findByEmail(string $email): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM users WHERE email = ?');
        $stmt->execute([$email]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public function create(string $name, string $email, string $password): int
    {
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $this->pdo->prepare('INSERT INTO users (name,email,password) VALUES (?,?,?)');
        $stmt->execute([$name, $email, $hash]);
        return (int) $this->pdo->lastInsertId();
    }
}
