<?php
namespace App\Repository;

use App\Service\DatabaseService;
use PDO;

class TicketRepository
{
    private PDO $pdo;
    public function __construct(DatabaseService $db)
    {
        $this->pdo = $db->getPdo();
    }

    public function all(): array
    {
        $stmt = $this->pdo->query('SELECT * FROM tickets ORDER BY created_at DESC');
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    public function find(int $id): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM tickets WHERE id = ?');
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }
    public function create(array $data): int
    {
        $now = date(DATE_ATOM);
        $stmt = $this->pdo->prepare('INSERT INTO tickets (title,description,status,priority,created_at,updated_at,reporter_id) VALUES (?,?,?,?,?,?,?)');
        $stmt->execute([$data['title'], $data['description'] ?? null, $data['status'], $data['priority'] ?? null, $now, $now, $data['reporter_id']]);
        return (int) $this->pdo->lastInsertId();
    }
    public function update(int $id, array $data): bool
    {
        $now = date(DATE_ATOM);
        $stmt = $this->pdo->prepare('UPDATE tickets SET title=?, description=?, status=?, priority=?, updated_at=? WHERE id=?');
        return $stmt->execute([$data['title'], $data['description'] ?? null, $data['status'], $data['priority'] ?? null, $now, $id]);
    }
    public function delete(int $id): bool
    {
        $stmt = $this->pdo->prepare('DELETE FROM tickets WHERE id = ?');
        return $stmt->execute([$id]);
    }
}
