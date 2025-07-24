<?php
require_once '../config/config.php';
require_once '../config/database.php';

// Check if user is logged in and is an employer
if (!isLoggedIn() || !hasRole('employer')) {
    redirectWith(BASE_URL . '/login.php', 'Please login as an employer to access this page.', 'warning');
}

// Get job ID
$job_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$job_id) {
    redirectWith('dashboard.php', 'Invalid request.', 'danger');
}

$db = new Database();
$conn = $db->getConnection();

try {
    // Get employer profile ID
    $stmt = $conn->prepare("SELECT id FROM employer_profiles WHERE user_id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $employer_id = $stmt->fetch()['id'];
    
    // Verify job belongs to employer
    $stmt = $conn->prepare("SELECT id FROM job WHERE id = ? AND employer_id = ?");
    $stmt->execute([$job_id, $employer_id]);
    
    if (!$stmt->fetch()) {
        redirectWith('dashboard.php', 'You do not have permission to delete this job.', 'danger');
    }
    
    // Begin transaction
    $conn->beginTransaction();
    
    // Delete job applications first (due to foreign key constraint)
    $stmt = $conn->prepare("DELETE FROM applications WHERE job_id = ?");
    $stmt->execute([$job_id]);
    
    // Delete job
    $stmt = $conn->prepare("DELETE FROM job WHERE id = ?");
    $stmt->execute([$job_id]);
    
    // Commit transaction
    $conn->commit();
    
    redirectWith('dashboard.php', 'Job listing deleted successfully.', 'success');
} catch (PDOException $e) {
    // Rollback transaction on error
    if ($conn->inTransaction()) {
        $conn->rollBack();
    }
    redirectWith('dashboard.php', 'An error occurred while deleting the job.', 'danger');
}
?> 