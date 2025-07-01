<?php

namespace App\Models;

use App\Config\Database;
use Doctrine\DBAL\Connection;
use Ramsey\Uuid\Uuid;

class Transfer
{
    private Connection $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    public function create(array $data): array
    {
        $transferRequestId = Uuid::uuid4()->toString();
        
        $sql = "INSERT INTO transfers (
            transfer_request_id, sending_name, sending_account, 
            receiving_name, receiving_account, amount, 
            sending_currency, receiving_currency, description, status
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

        $this->db->executeStatement($sql, [
            $transferRequestId,
            $data['sending_name'],
            $data['sending_account'],
            $data['receiving_name'],
            $data['receiving_account'],
            $data['amount'],
            $data['sending_currency'],
            $data['receiving_currency'],
            $data['description'] ?? '',
            'pending'
        ]);

        return $this->findByTransferRequestId($transferRequestId);
    }

    public function findByTransferRequestId(string $transferRequestId): ?array
    {
        $sql = "SELECT * FROM transfers WHERE transfer_request_id = ?";
        $result = $this->db->fetchAssociative($sql, [$transferRequestId]);
        
        return $result ?: null;
    }

    public function updateStatus(string $transferRequestId, string $status, ?array $externalApiResponse = null): bool
    {
        $sql = "UPDATE transfers SET status = ?, external_api_response = ?, updated_at = CURRENT_TIMESTAMP WHERE transfer_request_id = ?";
        
        return $this->db->executeStatement($sql, [
            $status,
            $externalApiResponse ? json_encode($externalApiResponse) : null,
            $transferRequestId
        ]) > 0;
    }

    public function getAll(): array
    {
        $sql = "SELECT * FROM transfers ORDER BY created_at DESC";
        return $this->db->fetchAllAssociative($sql);
    }

    public function getByStatus(string $status): array
    {
        $sql = "SELECT * FROM transfers WHERE status = ? ORDER BY created_at DESC";
        return $this->db->fetchAllAssociative($sql, [$status]);
    }

    public function validateTransferData(array $data): array
    {
        $errors = [];

        if (empty($data['sending_name'])) {
            $errors[] = 'Sending name is required';
        }

        if (empty($data['sending_account'])) {
            $errors[] = 'Sending account is required';
        }

        if (empty($data['receiving_name'])) {
            $errors[] = 'Receiving name is required';
        }

        if (empty($data['receiving_account'])) {
            $errors[] = 'Receiving account is required';
        }

        if (!isset($data['amount']) || $data['amount'] <= 0) {
            $errors[] = 'Valid amount is required';
        }

        if (empty($data['sending_currency'])) {
            $errors[] = 'Sending currency is required';
        }

        if (empty($data['receiving_currency'])) {
            $errors[] = 'Receiving currency is required';
        }

        return $errors;
    }
} 