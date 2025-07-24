<?php
require_once '../config/config.php';
require_once '../config/database.php';

if (!isLoggedIn() || !hasRole('student')) {
    redirectWith(BASE_URL . '/login.php', 'Please login as a student', 'error');
}

$db = new Database();
$conn = $db->getConnection();

// Get student profile
$query = "SELECT * FROM student_profiles WHERE user_id = :user_id";
$stmt = $conn->prepare($query);
$stmt->execute(['user_id' => $_SESSION['user_id']]);
$student = $stmt->fetch();

if (!$student) {
    redirectWith(BASE_URL . '/student/complete-profile.php', 'Please complete your profile first', 'warning');
}

// Get all applications
$query = "SELECT a.*, j.title as job_title, e.company_name, e.logo_path
          FROM applications a
          INNER JOIN job j ON a.job_id = j.id
          INNER JOIN employer_profiles e ON j.employer_id = e.id
          WHERE a.student_id = :student_id
          ORDER BY a.applied_at DESC";
$stmt = $conn->prepare($query);
$stmt->execute(['student_id' => $student['id']]);
$applications = $stmt->fetchAll();

include '../includes/header.php';
?>
<div class="container py-4">
    <h2 class="mb-4">My Applications</h2>
    <div class="card">
        <div class="card-body">
            <?php if (empty($applications)): ?>
                <div class="text-center py-5">
                    <h5 class="mt-3">No Applications Yet</h5>
                    <p class="text-muted">Start applying for jobs to begin your career journey.</p>
                    <a href="../job.php" class="btn btn-primary">Browse Jobs</a>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Company</th>
                                <th>Position</th>
                                <th>Applied Date</th>
                                <th>Status</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($applications as $app): ?>
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <img src="<?php echo !empty($app['logo_path']) ? BASE_URL . '/' . $app['logo_path'] : BASE_URL . '/assets/images/DefaultCompanyLogo.jpg'; ?>" 
                                                 alt="<?php echo htmlspecialchars($app['company_name']); ?>" 
                                                 class="me-2" 
                                                 style="width: 30px; height: 30px; object-fit: cover;">
                                            <?php echo htmlspecialchars($app['company_name']); ?>
                                        </div>
                                    </td>
                                    <td><?php echo htmlspecialchars($app['job_title']); ?></td>
                                    <td><?php echo date('M d, Y', strtotime($app['applied_at'])); ?></td>
                                    <td>
                                        <span class="badge bg-<?php 
                                            echo match($app['status']) {
                                                'pending' => 'warning',
                                                'accepted' => 'success',
                                                'rejected' => 'danger',
                                                default => 'secondary'
                                            };
                                        ?>">
                                            <?php echo ucfirst($app['status']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <a href="view-application.php?id=<?php echo $app['id']; ?>" 
                                           class="btn btn-sm btn-outline-primary">
                                            View
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>
<?php include '../includes/footer.php'; ?> 