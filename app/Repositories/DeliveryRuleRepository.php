<?php

namespace App\Repositories;

use PDO;

class DeliveryRuleRepository extends BaseRepository
{
    protected string $table = 'delivery_rules';

    public function getAllActive(): array
    {
        $sql = "SELECT * FROM delivery_rules WHERE is_active = 1 ORDER BY base_fee ASC, id ASC";
        $stmt = $this->db->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function findById(int $id): ?array
    {
        $sql = "SELECT * FROM delivery_rules WHERE id = :id AND is_active = 1 LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['id' => $id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ?: null;
    }

    public function findByRegionName(string $regionName): ?array
    {
        $sql = "SELECT * FROM delivery_rules WHERE LOWER(region_name) = LOWER(:region_name) AND is_active = 1 LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['region_name' => trim($regionName)]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ?: null;
    }
}
