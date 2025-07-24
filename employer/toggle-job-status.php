<?php
require_once '../config/config.php';
require_once '../config/database.php';

// Check if user is logged in and is an employer
if (!isLoggedIn() || !hasRole('employer')) {
    redirectWith(BASE_URL . '/login.php', 'Please login as an employer to access this page.', 'warning');
}

// Get job ID and status
$job_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$status = isset($_GET['status']) ? sanitizeInput($_GET['status']) : '';

if (!$job_id || !in_array($status, ['active', 'closed'])) {
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
        redirectWith('dashboard.php', 'You do not have permission to modify this job.', 'danger');
    }
    
    // Update job status
    $stmt = $conn->prepare("UPDATE job SET status = ? WHERE id = ?");
    $stmt->execute([$status, $job_id]);
    
    $message = $status === 'active' ? 'Job listing reactivated successfully.' : 'Job listing closed successfully.';
    redirectWith('dashboard.php', $message, 'success');
} catch (PDOException $e) {
    redirectWith('dashboard.php', 'An error occurred while updating the job status.', 'danger');
}
?> 