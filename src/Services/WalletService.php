<?php

namespace App\Services;

use App\Core\Database;
use App\Core\Response;
use DateTimeImmutable;
use PDO;

class WalletService
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getConnection();
    }

    public function summary(int $userId): void
    {
        $stmt = $this->db->prepare('SELECT wallet_balance FROM users WHERE id = :id');
        $stmt->execute(['id' => $userId]);
        $balance = (float) $stmt->fetchColumn();

        $transactionsStmt = $this->db->prepare('SELECT * FROM wallet_transactions WHERE user_id = :id ORDER BY created_at DESC LIMIT 50');
        $transactionsStmt->execute(['id' => $userId]);

        Response::json([
            'balance' => $balance,
            'transactions' => $transactionsStmt->fetchAll(PDO::FETCH_ASSOC),
        ]);
    }

    public function deposit(int $userId, float $amount): void
    {
        if ($amount <= 0) {
            Response::json(['error' => 'Amount must be positive'], 422);
            return;
        }

        $this->db->beginTransaction();
        try {
            $update = $this->db->prepare('UPDATE users SET wallet_balance = wallet_balance + :amount WHERE id = :id');
            $update->execute([
                'amount' => $amount,
                'id' => $userId,
            ]);

            $transaction = $this->db->prepare('INSERT INTO wallet_transactions (user_id, type, amount, status, created_at, metadata) VALUES (:user_id, :type, :amount, :status, :created_at, :metadata)');
            $transaction->execute([
                'user_id' => $userId,
                'type' => 'deposit',
                'amount' => $amount,
                'status' => 'completed',
                'created_at' => (new DateTimeImmutable())->format(DATE_ATOM),
                'metadata' => json_encode(['source' => 'manual_top_up']),
            ]);

            $this->db->commit();
            Response::json(['message' => 'Deposit recorded']);
        } catch (\Throwable $e) {
            $this->db->rollBack();
            Response::json(['error' => 'Failed to deposit', 'details' => $e->getMessage()], 500);
        }
    }

    public function requestWithdrawal(int $userId, float $amount): void
    {
        if ($amount <= 0) {
            Response::json(['error' => 'Amount must be positive'], 422);
            return;
        }

        $this->db->beginTransaction();
        try {
            $balanceStmt = $this->db->prepare('SELECT wallet_balance FROM users WHERE id = :id');
            $balanceStmt->execute(['id' => $userId]);
            $balance = (float) $balanceStmt->fetchColumn();
            if ($balance < $amount) {
                $this->db->rollBack();
                Response::json(['error' => 'Insufficient balance'], 422);
                return;
            }

            $update = $this->db->prepare('UPDATE users SET wallet_balance = wallet_balance - :amount WHERE id = :id');
            $update->execute([
                'amount' => $amount,
                'id' => $userId,
            ]);

            $transaction = $this->db->prepare('INSERT INTO wallet_transactions (user_id, type, amount, status, created_at, metadata) VALUES (:user_id, :type, :amount, :status, :created_at, :metadata)');
            $transaction->execute([
                'user_id' => $userId,
                'type' => 'withdrawal',
                'amount' => $amount,
                'status' => 'pending',
                'created_at' => (new DateTimeImmutable())->format(DATE_ATOM),
                'metadata' => json_encode([]),
            ]);

            $withdrawal = $this->db->prepare('INSERT INTO withdrawal_requests (user_id, amount, status, created_at) VALUES (:user_id, :amount, :status, :created_at)');
            $withdrawal->execute([
                'user_id' => $userId,
                'amount' => $amount,
                'status' => 'pending',
                'created_at' => (new DateTimeImmutable())->format(DATE_ATOM),
            ]);

            $this->db->commit();
            Response::json(['message' => 'Withdrawal requested']);
        } catch (\Throwable $e) {
            $this->db->rollBack();
            Response::json(['error' => 'Failed to request withdrawal', 'details' => $e->getMessage()], 500);
        }
    }

    public function listWithdrawalRequests(): array
    {
        $stmt = $this->db->query('SELECT wr.*, u.name AS user_name FROM withdrawal_requests wr JOIN users u ON u.id = wr.user_id ORDER BY wr.created_at DESC');
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function updateWithdrawalStatus(int $id, string $status, int $adminId): void
    {
        $allowed = ['approved', 'rejected'];
        if (!in_array($status, $allowed, true)) {
            Response::json(['error' => 'Invalid status'], 422);
            return;
        }

        $this->db->beginTransaction();
        try {
            $stmt = $this->db->prepare('SELECT * FROM withdrawal_requests WHERE id = :id');
            $stmt->execute(['id' => $id]);
            $request = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$request) {
                $this->db->rollBack();
                Response::json(['error' => 'Request not found'], 404);
                return;
            }

            if ($request['status'] !== 'pending') {
                $this->db->rollBack();
                Response::json(['error' => 'Request already processed'], 409);
                return;
            }

            $updateRequest = $this->db->prepare('UPDATE withdrawal_requests SET status = :status, reviewed_by = :admin_id, reviewed_at = :reviewed_at WHERE id = :id');
            $updateRequest->execute([
                'status' => $status,
                'admin_id' => $adminId,
                'reviewed_at' => (new DateTimeImmutable())->format(DATE_ATOM),
                'id' => $id,
            ]);

            $transactionStatus = $status === 'approved' ? 'completed' : 'cancelled';
            $updateTransaction = $this->db->prepare('UPDATE wallet_transactions SET status = :status WHERE user_id = :user_id AND type = :type AND status = :current_status AND amount = :amount');
            $updateTransaction->execute([
                'status' => $transactionStatus,
                'user_id' => $request['user_id'],
                'type' => 'withdrawal',
                'current_status' => 'pending',
                'amount' => $request['amount'],
            ]);

            if ($status === 'rejected') {
                $refund = $this->db->prepare('UPDATE users SET wallet_balance = wallet_balance + :amount WHERE id = :id');
                $refund->execute([
                    'amount' => $request['amount'],
                    'id' => $request['user_id'],
                ]);
            }

            $this->db->commit();
            Response::json(['message' => 'Withdrawal ' . $status]);
        } catch (\Throwable $e) {
            $this->db->rollBack();
            Response::json(['error' => 'Failed to update request', 'details' => $e->getMessage()], 500);
        }
    }
}
