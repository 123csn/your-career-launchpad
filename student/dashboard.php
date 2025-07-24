<?php
require_once '../config/config.php';
require_once '../config/database.php';

// Check if user is logged in and is a student
if (!isLoggedIn() || !hasRole('student')) {
    redirectWith(BASE_URL . '/login.php', 'Please login as a student', 'error');
}

// Helper function to safely get profile picture URL
function getProfilePictureUrl($profilePicturePath = null) {
    if (!empty($profilePicturePath) && file_exists('../' . $profilePicturePath)) {
        return BASE_URL . '/' . $profilePicturePath;
    }
    return BASE_URL . '/assets/images/default-avatar.png';
}

// Helper function to safely get company logo URL
function getCompanyLogo($logoPath) {
    if (!empty($logoPath) && file_exists('../' . $logoPath)) {
        return BASE_URL . '/' . $logoPath;
    }
    return BASE_URL . '/assets/images/DefaultCompanyLogo.jpg';
}

// Helper function to format education level
function formatEducationLevel($value) {
    $map = [
        'high_school' => "High School",
        'diploma' => "Diploma",
        'bachelors_degree' => "Bachelor's Degree",
        'masters_degree' => "Master's Degree",
        'phd' => "PhD",
        'other' => "Other"
    ];
    return $map[$value] ?? $value;
}

// Get student information
$db = new Database();
$conn = $db->getConnection();

$query = "SELECT s.*, u.email 
          FROM student_profiles s 
          INNER JOIN users u ON s.user_id = u.id 
          WHERE u.id = :user_id";
$stmt = $conn->prepare($query);
$stmt->execute(['user_id' => $_SESSION['user_id']]);
$student = $stmt->fetch();

// If student profile doesn't exist, redirect to complete profile
if (!$student) {
    redirectWith(BASE_URL . '/student/complete-profile.php', 'Please complete your profile first', 'warning');
}

// Handle mark as read BEFORE fetching notifications
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['notif_id'], $_POST['mark_read'])) {
        $notif_id = (int)$_POST['notif_id'];
        $stmt = $conn->prepare("UPDATE notifications SET is_read = 1 WHERE id = ? AND student_id = ?");
        $stmt->execute([$notif_id, $student['id']]);
        header("Location: dashboard.php");
        exit;
    } elseif (isset($_POST['mark_all_read'])) {
        $stmt = $conn->prepare("UPDATE notifications SET is_read = 1 WHERE student_id = ?");
        $stmt->execute([$student['id']]);
        header("Location: dashboard.php");
        exit;
    }
}
// Fetch all notifications for this student
$stmt = $conn->prepare("SELECT * FROM notifications WHERE student_id = ? ORDER BY created_at DESC");
$stmt->execute([$student['id']]);
$all_notifications = $stmt->fetchAll();

// Get application statistics
$query = "SELECT 
            COUNT(*) as total,
            SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
            SUM(CASE WHEN status = 'accepted' THEN 1 ELSE 0 END) as accepted
          FROM applications 
          WHERE student_id = :student_id";
$stmt = $conn->prepare($query);
$stmt->execute(['student_id' => $student['id']]);
$stats = $stmt->fetch();

// Get recent applications
$query = "SELECT a.*, j.title as job_title, e.company_name, e.logo_path
          FROM applications a
          INNER JOIN job j ON a.job_id = j.id
          INNER JOIN employer_profiles e ON j.employer_id = e.id
          WHERE a.student_id = :student_id
          ORDER BY a.applied_at DESC
          LIMIT 5";
$stmt = $conn->prepare($query);
$stmt->execute(['student_id' => $student['id']]);
$applications = $stmt->fetchAll();

// Personalized Job Recommendations
// Fetch student skills and education_level
$student_skills = array_map('trim', explode(',', strtolower($student['skills'])));
$student_major = strtolower($student['education_level']);
// Fetch job IDs the student has already applied to
$stmt = $conn->prepare("SELECT job_id FROM applications WHERE student_id = ?");
$stmt->execute([$student['id']]);
$applied_job_ids = array_column($stmt->fetchAll(), 'job_id');
// Fetch all active jobs (excluding already applied)
$query = "SELECT j.*, e.company_name, e.logo_path FROM job j INNER JOIN employer_profiles e ON j.employer_id = e.id WHERE j.status = 'active'";
if (!empty($applied_job_ids)) {
    $placeholders = implode(',', array_fill(0, count($applied_job_ids), '?'));
    $query .= " AND j.id NOT IN ($placeholders)";
}
$query .= " ORDER BY j.created_at DESC";
$stmt = $conn->prepare($query);
$params = !empty($applied_job_ids) ? $applied_job_ids : [];
$stmt->execute($params);
$all_jobs = $stmt->fetchAll();
// Score jobs
$job_scores = [];
foreach ($all_jobs as $job) {
    $score = 0;
    $matched = [];
    $job_text = strtolower($job['requirements'] . ' ' . $job['description']);
    // Match skills
    $matched_skills = [];
    foreach ($student_skills as $skill) {
        if ($skill && strpos($job_text, $skill) !== false) {
            $score++;
            $matched_skills[] = $skill;
        }
    }
    if (!empty($matched_skills)) {
        $matched[] = 'Skills: ' . implode(', ', $matched_skills);
    }
    // Match major/education
    $major_matched = false;
    if ($student_major && (strpos(strtolower($job['title']), $student_major) !== false || strpos($job_text, $student_major) !== false)) {
        $score++;
        $matched[] = 'Major';
        $major_matched = true;
    }
    // Only recommend if at least two matches (skills+major or 2+ skills)
    if ($score >= 2) {
        $job_scores[] = [
            'job' => $job,
            'score' => $score,
            'matched' => $matched
        ];
    }
}
// Sort by score descending
usort($job_scores, function($a, $b) { return $b['score'] <=> $a['score']; });

include '../includes/header.php';
?>

<div class="container py-4">
    <div class="row">
        <!-- Sidebar -->
        <div class="col-md-3">
            <div class="card mb-4">
                <div class="card-body text-center">
                    <img src="<?php echo getProfilePictureUrl($student['profile_picture']); ?>" 
                         alt="Profile Picture" 
                         class="rounded-circle mb-3" 
                         style="width: 150px; height: 150px; object-fit: cover;">
                    <h5 class="card-title mb-0"><?php echo htmlspecialchars($student['first_name'] . ' ' . $student['last_name']); ?></h5>
                    <p class="text-muted mb-1"><?php echo htmlspecialchars(formatEducationLevel($student['education_level'] ?? '')); ?></p>
                    <div class="d-grid gap-2">
                        <a href="edit-profile.php" class="btn btn-outline-primary">Edit Profile</a>
                        <a href="../job.php" class="btn btn-primary">Browse Jobs</a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Main Content -->
        <div class="col-md-9">
            <!-- Recommended for You Section -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">Recommended for You</h5>
                </div>
                <div class="card-body">
                    <?php if (empty($job_scores)): ?>
                        <div class="text-muted">No personalized recommendations at this time. Try updating your profile skills or check back later!</div>
                    <?php else: ?>
                        <div class="row">
                            <?php foreach (array_slice($job_scores, 0, 5) as $rec): $job = $rec['job']; $matched = $rec['matched']; ?>
                                <div class="col-md-6 mb-3">
                                    <div class="card h-100">
                                        <div class="card-body">
                                            <div class="d-flex align-items-center mb-2">
                                                <img src="<?php echo getCompanyLogo($job['logo_path']); ?>" class="company-logo me-2" style="width:40px;height:40px;object-fit:cover;border-radius:8px;">
                                                <div>
                                                    <h5 class="mb-0"><?php echo htmlspecialchars($job['title']); ?></h5>
                                                    <div class="text-muted"><?php echo htmlspecialchars($job['company_name']); ?></div>
                                                </div>
                                            </div>
                                            <div class="mb-2"><span class="badge bg-primary"><?php echo htmlspecialchars($job['job_type']); ?></span> <span class="badge bg-secondary"><?php echo htmlspecialchars($job['location']); ?></span></div>
                                            <p class="mb-2"><?php echo substr(htmlspecialchars($job['description']), 0, 80) . '...'; ?></p>
                                            <?php if (!empty($matched)): ?>
                                                <div class="mb-2"><small class="text-success">Matched: <?php echo implode('; ', $matched); ?> (<?php echo $rec['score']; ?> matches)</small></div>
                                            <?php endif; ?>
                                            <a href="../jobs/view.php?id=<?php echo $job['id']; ?>" class="btn btn-outline-primary btn-sm">View Details</a>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            <!-- Notifications Section -->
            <div class="card mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Notifications</h5>
                    <?php if (!empty($all_notifications)): ?>
                        <form method="POST" style="display:inline;">
                            <button type="submit" name="mark_all_read" class="btn btn-sm btn-outline-secondary">Mark All as Read</button>
                        </form>
                    <?php endif; ?>
                </div>
                <div class="card-body">
                    <?php if (empty($all_notifications)): ?>
                        <div class="text-center text-muted">No notifications yet.</div>
                    <?php else: ?>
                        <ul class="list-group list-group-flush">
                            <?php foreach ($all_notifications as $notif): ?>
                                <li class="list-group-item d-flex justify-content-between align-items-start<?php echo !$notif['is_read'] ? ' fw-bold' : ''; ?>">
                                    <div>
                                        <a href="<?php echo htmlspecialchars($notif['link']); ?>" class="text-decoration-none">
                                            <?php echo htmlspecialchars($notif['message']); ?>
                                        </a>
                                        <br><small class="text-muted"><?php echo date('M d, Y H:i', strtotime($notif['created_at'])); ?></small>
                                    </div>
                                    <?php if (!$notif['is_read']): ?>
                                        <form method="POST" style="margin-left:1rem;">
                                            <input type="hidden" name="notif_id" value="<?php echo $notif['id']; ?>">
                                            <button type="submit" name="mark_read" class="btn btn-sm btn-link">Mark as Read</button>
                                        </form>
                                    <?php endif; ?>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                </div>
            </div>
            <!-- Statistics Cards -->
            <div class="row mb-4">
                <div class="col-md-4">
                    <div class="card bg-primary text-white">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="card-title mb-0">Total Applications</h6>
                                    <h2 class="mb-0"><?php echo $stats['total'] ?? 0; ?></h2>
                                </div>
                                <i class="fas fa-file-alt fa-2x"></i>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card bg-warning text-dark">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="card-title mb-0">Pending</h6>
                                    <h2 class="mb-0"><?php echo $stats['pending'] ?? 0; ?></h2>
                                </div>
                                <i class="fas fa-clock fa-2x"></i>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card bg-success text-white">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="card-title mb-0">Accepted</h6>
                                    <h2 class="mb-0"><?php echo $stats['accepted'] ?? 0; ?></h2>
                                </div>
                                <i class="fas fa-check-circle fa-2x"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Recent Applications -->
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">My Applications</h5>
                    <a href="applications.php" class="btn btn-sm btn-primary">View All</a>
                </div>
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
                                                    <img src="<?php echo getCompanyLogo($app['logo_path']); ?>" 
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
    </div>
</div>

<?php include '../includes/footer.php'; ?> 