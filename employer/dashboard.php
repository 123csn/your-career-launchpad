<?php
require_once '../config/config.php';
require_once '../config/database.php';

// Check if user is logged in and is an employer
if (!isLoggedIn() || !hasRole('employer')) {
    redirectWith(BASE_URL . '/login.php', 'Please login as an employer to access this page.', 'warning');
}

$db = new Database();
$conn = $db->getConnection();

// Auto-close jobs whose deadline has passed
$today = date('Y-m-d');
$conn->prepare("UPDATE job SET status = 'closed' WHERE deadline IS NOT NULL AND deadline < ? AND status = 'active'")->execute([$today]);

// Get employer profile
$stmt = $conn->prepare("SELECT * FROM employer_profiles WHERE user_id = ?");
$stmt->execute([$_SESSION['user_id']]);
$profile = $stmt->fetch();

if (!$profile) {
    redirectWith(BASE_URL . '/employer/complete-profile.php', 'Please complete your company profile first.', 'warning');
}

// Get employer's jobs with application counts
$stmt = $conn->prepare("
    SELECT j.*, 
           COUNT(DISTINCT a.id) as total_applications,
           COUNT(DISTINCT CASE WHEN a.status = 'pending' THEN a.id END) as pending_applications
    FROM job j
    LEFT JOIN applications a ON j.id = a.job_id
    WHERE j.employer_id = ?
    GROUP BY j.id
    ORDER BY j.created_at DESC
");
$stmt->execute([$profile['id']]);
$jobs = $stmt->fetchAll();

// Handle Reactivate Job with new deadline
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reactivate_job_id'], $_POST['new_deadline'])) {
    $reactivate_job_id = (int)$_POST['reactivate_job_id'];
    $new_deadline = $_POST['new_deadline'];
    // Validate deadline
    if ($new_deadline && strtotime($new_deadline) >= strtotime(date('Y-m-d'))) {
        $stmt = $conn->prepare("UPDATE job SET status = 'active', deadline = ? WHERE id = ? AND employer_id = ?");
        $stmt->execute([$new_deadline, $reactivate_job_id, $profile['id']]);
        redirectWith('dashboard.php', 'Job reactivated with new deadline!', 'success');
    } else {
        redirectWith('dashboard.php', 'Please select a valid future deadline.', 'danger');
    }
}

include '../includes/header.php';
?>

<div class="container py-4">
    <div class="row">
        <!-- Sidebar -->
        <div class="col-lg-3">
            <div class="card mb-4">
                <div class="card-body text-center">
                    <img src="<?php echo $profile['logo_path'] ? BASE_URL . '/' . $profile['logo_path'] : BASE_URL . '/assets/images/DefaultCompanyLogo.jpg'; ?>" 
                         alt="<?php echo htmlspecialchars($profile['company_name']); ?>"
                         class="img-fluid rounded-circle mb-3" style="width: 120px; height: 120px; object-fit: cover;">
                    <h5 class="card-title"><?php echo htmlspecialchars($profile['company_name']); ?></h5>
                    <p class="text-muted mb-0"><?php echo htmlspecialchars($profile['industry']); ?></p>
                </div>
                <div class="list-group list-group-flush">
                    <a href="dashboard.php" class="list-group-item list-group-item-action active">
                        <i class="fas fa-tachometer-alt me-2"></i>Dashboard
                    </a>
                    <a href="post-job.php" class="list-group-item list-group-item-action">
                        <i class="fas fa-plus-circle me-2"></i>Post New Job
                    </a>
                    <a href="edit-profile.php" class="list-group-item list-group-item-action">
                        <i class="fas fa-user-edit me-2"></i>Edit Profile
                    </a>
                </div>
            </div>
        </div>

        <!-- Main Content -->
        <div class="col-lg-9">
            <!-- Stats Cards -->
            <div class="row mb-4">
                <div class="col-md-4">
                    <div class="card bg-primary text-white h-100">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="text-uppercase mb-1">Active Jobs</h6>
                                    <h2 class="mb-0">
                                        <?php 
                                        echo count(array_filter($jobs, function($job) {
                                            return $job['status'] === 'active';
                                        }));
                                        ?>
                                    </h2>
                                </div>
                                <i class="fas fa-briefcase fa-2x opacity-50"></i>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card bg-success text-white h-100">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="text-uppercase mb-1">Total Applications</h6>
                                    <h2 class="mb-0">
                                        <?php 
                                        echo array_sum(array_column($jobs, 'total_applications'));
                                        ?>
                                    </h2>
                                </div>
                                <i class="fas fa-users fa-2x opacity-50"></i>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card bg-warning text-white h-100">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="text-uppercase mb-1">Pending Reviews</h6>
                                    <h2 class="mb-0">
                                        <?php 
                                        echo array_sum(array_column($jobs, 'pending_applications'));
                                        ?>
                                    </h2>
                                </div>
                                <i class="fas fa-clock fa-2x opacity-50"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Job Listings -->
            <div class="card">
                <div class="card-header bg-white d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Posted Jobs</h5>
                    <a href="post-job.php" class="btn btn-primary btn-sm">
                        <i class="fas fa-plus me-1"></i>Post New Job
                    </a>
                </div>
                <div class="card-body p-0">
                    <?php if (!empty($jobs)): ?>
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>Title</th>
                                        <th>Type</th>
                                        <th>Applications</th>
                                        <th>Status</th>
                                        <th>Posted</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($jobs as $job): ?>
                                        <tr>
                                            <td>
                                                <a href="<?php echo BASE_URL; ?>/jobs/view.php?id=<?php echo $job['id']; ?>" class="text-decoration-none">
                                                    <?php echo htmlspecialchars($job['title']); ?>
                                                </a>
                                            </td>
                                            <td>
                                                <span class="badge bg-primary">
                                                    <?php echo htmlspecialchars($job['job_type']); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <a href="view-applications.php?job_id=<?php echo $job['id']; ?>" class="text-decoration-none">
                                                    <?php echo $job['total_applications']; ?> total
                                                    <?php if ($job['pending_applications'] > 0): ?>
                                                        <span class="badge bg-warning text-dark">
                                                            <?php echo $job['pending_applications']; ?> new
                                                        </span>
                                                    <?php endif; ?>
                                                </a>
                                            </td>
                                            <td>
                                                <?php if ($job['status'] === 'active'): ?>
                                                    <span class="badge bg-success">Active</span>
                                                <?php else: ?>
                                                    <span class="badge bg-secondary">Closed</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <small class="text-muted">
                                                    <?php echo date('M j, Y', strtotime($job['created_at'])); ?>
                                                </small>
                                            </td>
                                            <td>
                                                <div class="btn-group btn-group-sm">
                                                    <a href="edit-job.php?id=<?php echo $job['id']; ?>" 
                                                       class="btn btn-outline-primary" title="Edit">
                                                        <i class="fas fa-edit"></i>
                                                    </a>
                                                    <?php if ($job['status'] === 'active'): ?>
                                                        <a href="toggle-job-status.php?id=<?php echo $job['id']; ?>&status=closed" 
                                                           class="btn btn-outline-secondary" 
                                                           onclick="return confirm('Are you sure you want to close this job listing?')"
                                                           title="Close">
                                                            <i class="fas fa-times"></i>
                                                        </a>
                                                    <?php else: ?>
                                                        <button type="button" class="btn btn-outline-success" data-bs-toggle="modal" data-bs-target="#reactivateModal<?php echo $job['id']; ?>" title="Reactivate">
                                                            <i class="fas fa-redo"></i>
                                                        </button>
                                                    <?php endif; ?>
                                                    <a href="delete-job.php?id=<?php echo $job['id']; ?>" 
                                                       class="btn btn-outline-danger"
                                                       onclick="return confirm('Are you sure you want to delete this job listing? This action cannot be undone.')"
                                                       title="Delete">
                                                        <i class="fas fa-trash"></i>
                                                    </a>
                                                </div>
                                            </td>
                                        </tr>
                                        <!-- Reactivate Modal -->
                                        <div class="modal fade" id="reactivateModal<?php echo $job['id']; ?>" tabindex="-1">
                                            <div class="modal-dialog">
                                                <div class="modal-content">
                                                    <form method="POST" action="dashboard.php">
                                                        <div class="modal-header">
                                                            <h5 class="modal-title">Reactivate Job: <?php echo htmlspecialchars($job['title']); ?></h5>
                                                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                        </div>
                                                        <div class="modal-body">
                                                            <div class="mb-3">
                                                                <label for="new_deadline_<?php echo $job['id']; ?>" class="form-label">New Application Deadline</label>
                                                                <input type="date" class="form-control" id="new_deadline_<?php echo $job['id']; ?>" name="new_deadline" min="<?php echo date('Y-m-d'); ?>" required>
                                                            </div>
                                                            <input type="hidden" name="reactivate_job_id" value="<?php echo $job['id']; ?>">
                                                        </div>
                                                        <div class="modal-footer">
                                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                            <button type="submit" class="btn btn-success">Reactivate</button>
                                                        </div>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="text-center py-5">
                            <i class="fas fa-briefcase fa-3x text-muted mb-3"></i>
                            <h5>No Jobs Posted Yet</h5>
                            <p class="text-muted mb-0">Start by posting your first job listing.</p>
                            <a href="post-job.php" class="btn btn-primary mt-3">Post a Job</a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?> 