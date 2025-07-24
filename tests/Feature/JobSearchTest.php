<?php

use PHPUnit\Framework\TestCase;
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';

class JobSearchTest extends TestCase
{
    private $db;
    
    protected function setUp(): void
    {
        $this->db = new Database();
    }
    
    public function testJobSearch()
    {
        // Test keyword search
        $conn = $this->db->getConnection();
        $keyword = "developer";
        
        $query = "SELECT * FROM job WHERE title LIKE :keyword OR description LIKE :keyword";
        $stmt = $conn->prepare($query);
        $stmt->execute(['keyword' => "%$keyword%"]);
        $results = $stmt->fetchAll();
        
        $this->assertIsArray($results);
    }
    
    public function testJobFilters()
    {
        // Test job type filter
        $conn = $this->db->getConnection();
        $jobType = "full-time";
        
        $query = "SELECT * FROM job WHERE job_type = :jobType";
        $stmt = $conn->prepare($query);
        $stmt->execute(['jobType' => $jobType]);
        $results = $stmt->fetchAll();
        
        $this->assertIsArray($results);
    }
} 