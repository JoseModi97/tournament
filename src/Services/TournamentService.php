<?php

namespace App\Services;

use App\Core\Database;
use App\Core\Response;
use DateTimeImmutable;
use PDO;

class TournamentService
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getConnection();
    }

    public function list(): void
    {
        $stmt = $this->db->query('SELECT * FROM tournaments ORDER BY start_time ASC');
        $tournaments = $stmt->fetchAll(PDO::FETCH_ASSOC);
        Response::json($tournaments);
    }

    public function create(array $data, array $user): void
    {
        $name = trim($data['name'] ?? '');
        $description = trim($data['description'] ?? '');
        $entryFee = (float) ($data['entry_fee'] ?? 0);
        $prizePool = (float) ($data['prize_pool'] ?? 0);
        $startTime = $data['start_time'] ?? null;

        if ($name === '' || !$startTime) {
            Response::json(['error' => 'Name and start_time are required'], 422);
            return;
        }

        $stmt = $this->db->prepare('INSERT INTO tournaments (name, description, entry_fee, prize_pool, start_time, status, created_by, created_at) VALUES (:name, :description, :entry_fee, :prize_pool, :start_time, :status, :created_by, :created_at)');
        $stmt->execute([
            'name' => $name,
            'description' => $description,
            'entry_fee' => $entryFee,
            'prize_pool' => $prizePool,
            'start_time' => $startTime,
            'status' => $data['status'] ?? 'upcoming',
            'created_by' => $user['id'],
            'created_at' => (new DateTimeImmutable())->format(DATE_ATOM),
        ]);

        Response::json(['message' => 'Tournament created'], 201);
    }

    public function get(int $id): void
    {
        $stmt = $this->db->prepare('SELECT * FROM tournaments WHERE id = :id');
        $stmt->execute(['id' => $id]);
        $tournament = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$tournament) {
            Response::json(['error' => 'Tournament not found'], 404);
            return;
        }

        $matchesStmt = $this->db->prepare('SELECT m.*, u.name AS staff_name FROM matches m LEFT JOIN users u ON u.id = m.assigned_staff_id WHERE m.tournament_id = :id');
        $matchesStmt->execute(['id' => $id]);
        $tournament['matches'] = $matchesStmt->fetchAll(PDO::FETCH_ASSOC);

        $participantsStmt = $this->db->prepare('SELECT u.id, u.name FROM registrations r JOIN users u ON u.id = r.user_id WHERE r.tournament_id = :id');
        $participantsStmt->execute(['id' => $id]);
        $tournament['participants'] = $participantsStmt->fetchAll(PDO::FETCH_ASSOC);

        Response::json($tournament);
    }

    public function join(int $id, array $user): void
    {
        $this->db->beginTransaction();
        try {
            $stmt = $this->db->prepare('SELECT * FROM tournaments WHERE id = :id');
            $stmt->execute(['id' => $id]);
            $tournament = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$tournament) {
                $this->db->rollBack();
                Response::json(['error' => 'Tournament not found'], 404);
                return;
            }

            if ($tournament['status'] !== 'upcoming') {
                $this->db->rollBack();
                Response::json(['error' => 'Tournament closed'], 409);
                return;
            }

            $checkStmt = $this->db->prepare('SELECT id FROM registrations WHERE user_id = :user_id AND tournament_id = :tournament_id');
            $checkStmt->execute([
                'user_id' => $user['id'],
                'tournament_id' => $id,
            ]);
            if ($checkStmt->fetch()) {
                $this->db->rollBack();
                Response::json(['error' => 'Already registered'], 409);
                return;
            }

            $entryFee = (float) $tournament['entry_fee'];
            if ($entryFee > 0) {
                $balanceStmt = $this->db->prepare('SELECT wallet_balance FROM users WHERE id = :id');
                $balanceStmt->execute(['id' => $user['id']]);
                $balance = (float) $balanceStmt->fetchColumn();
                if ($balance < $entryFee) {
                    $this->db->rollBack();
                    Response::json(['error' => 'Insufficient balance'], 422);
                    return;
                }

                $updateWallet = $this->db->prepare('UPDATE users SET wallet_balance = wallet_balance - :amount WHERE id = :id');
                $updateWallet->execute([
                    'amount' => $entryFee,
                    'id' => $user['id'],
                ]);

                $transactionStmt = $this->db->prepare('INSERT INTO wallet_transactions (user_id, type, amount, status, created_at, metadata) VALUES (:user_id, :type, :amount, :status, :created_at, :metadata)');
                $transactionStmt->execute([
                    'user_id' => $user['id'],
                    'type' => 'entry_fee',
                    'amount' => $entryFee,
                    'status' => 'completed',
                    'created_at' => (new DateTimeImmutable())->format(DATE_ATOM),
                    'metadata' => json_encode(['tournament_id' => $id]),
                ]);
            }

            $registrationStmt = $this->db->prepare('INSERT INTO registrations (user_id, tournament_id, status, created_at) VALUES (:user_id, :tournament_id, :status, :created_at)');
            $registrationStmt->execute([
                'user_id' => $user['id'],
                'tournament_id' => $id,
                'status' => 'confirmed',
                'created_at' => (new DateTimeImmutable())->format(DATE_ATOM),
            ]);

            $this->db->commit();
            Response::json(['message' => 'Successfully joined tournament']);
        } catch (\Throwable $e) {
            $this->db->rollBack();
            Response::json(['error' => 'Failed to join tournament', 'details' => $e->getMessage()], 500);
        }
    }

    public function createMatch(array $data, array $user): void
    {
        $tournamentId = (int) ($data['tournament_id'] ?? 0);
        $name = trim($data['name'] ?? '');
        $staffId = $data['staff_id'] ?? null;

        if ($tournamentId <= 0 || $name === '') {
            Response::json(['error' => 'tournament_id and name are required'], 422);
            return;
        }

        $stmt = $this->db->prepare('INSERT INTO matches (tournament_id, name, status, assigned_staff_id, created_at) VALUES (:tournament_id, :name, :status, :staff, :created_at)');
        $stmt->execute([
            'tournament_id' => $tournamentId,
            'name' => $name,
            'status' => $data['status'] ?? 'scheduled',
            'staff' => $staffId,
            'created_at' => (new DateTimeImmutable())->format(DATE_ATOM),
        ]);

        Response::json(['message' => 'Match created'], 201);
    }

    public function listMatchesForStaff(int $staffId): void
    {
        $stmt = $this->db->prepare('SELECT m.*, t.name AS tournament_name FROM matches m JOIN tournaments t ON t.id = m.tournament_id WHERE m.assigned_staff_id = :staff_id');
        $stmt->execute(['staff_id' => $staffId]);
        Response::json($stmt->fetchAll(PDO::FETCH_ASSOC));
    }

    public function updateMatchResult(int $matchId, array $payload, array $staff): void
    {
        $stmt = $this->db->prepare('SELECT * FROM matches WHERE id = :id');
        $stmt->execute(['id' => $matchId]);
        $match = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$match) {
            Response::json(['error' => 'Match not found'], 404);
            return;
        }

        if ($match['assigned_staff_id'] && (int) $match['assigned_staff_id'] !== (int) $staff['id']) {
            Response::json(['error' => 'Not assigned to this match'], 403);
            return;
        }

        $update = $this->db->prepare('UPDATE matches SET status = :status, result_text = :result WHERE id = :id');
        $update->execute([
            'status' => $payload['status'] ?? 'completed',
            'result' => $payload['result_text'] ?? null,
            'id' => $matchId,
        ]);

        Response::json(['message' => 'Match updated']);
    }
}
