<?php
require_once '../config/config.php';
require_once '../config/database.php';

if (!isLoggedIn() || !hasRole('admin')) {
    redirectWith(BASE_URL . '/login.php', 'Please login as an admin to access this page.', 'warning');
}

$db = new Database();
$conn = $db->getConnection();

// Auto-close jobs whose deadline has passed
$today = date('Y-m-d');
$conn->prepare("UPDATE job SET status = 'closed' WHERE deadline IS NOT NULL AND deadline < ? AND status = 'active'")->execute([$today]);

// Handle test job generation
$success = false;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['generate_jobs'])) {
    $job_titles = [
        'Software Engineer', 'Marketing Intern', 'Data Analyst', 'Graphic Designer', 'HR Assistant',
        'Customer Service Rep', 'Business Analyst', 'Content Writer', 'Sales Executive', 'IT Support',
        'Finance Intern', 'Operations Manager', 'UX Designer', 'Project Coordinator', 'Mobile App Developer',
        'Legal Assistant', 'Digital Marketing Specialist', 'QA Tester', 'Administrative Assistant', 'Research Assistant'
    ];
    $job_types = ['full-time', 'part-time', 'internship', 'contract'];
    $locations = ['Kuala Lumpur', 'Petaling Jaya', 'Remote', 'Penang', 'Cyberjaya', 'Shah Alam', 'Subang Jaya', 'Sunway'];
    $requirements = [
        'PHP, JavaScript, MySQL', 'Communication, Social Media', 'Excel, SQL, Python', 'Photoshop, Illustrator',
        'Organization, MS Office', 'Negotiation, CRM', 'Writing, SEO', 'Windows, Networking',
        'Accounting, Excel', 'Leadership, Organization', 'Figma, UX Research', 'Planning, Communication',
        'Flutter, Android, iOS', 'Legal Research, MS Office', 'Google Ads, Facebook Ads', 'Testing, Selenium',
        'Research, Data Analysis'
    ];
    $salary_ranges = ['RM1000 - RM1500', 'RM2000 - RM3000', 'RM2500 - RM3500', 'RM3000 - RM4000', 'RM3500 - RM4500', 'RM3500 - RM5000', 'RM4000 - RM6000', 'RM5000 - RM7000', 'RM6000 - RM8000'];
    for ($i = 0; $i < 20; $i++) {
        $title = $job_titles[array_rand($job_titles)];
        $description = 'This is a test job for ' . $title . '. Responsibilities include ...';
        $req = $requirements[array_rand($requirements)];
        $location = $locations[array_rand($locations)];
        $job_type = $job_types[array_rand($job_types)];
        $salary = $salary_ranges[array_rand($salary_ranges)];
        $status = 'Open';
        $stmt = $conn->prepare("INSERT INTO job (employer_id, title, description, requirements, location, job_type, salary_range, status) VALUES (1, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$title, $description, $req, $location, $job_type, $salary, $status]);
    }
    $success = true;
}

// --- Stats Data ---
$totalUsers = $totalStudents = $totalEmployers = $totalJobs = $totalApplications = $totalFeedback = 0;
// Users
$stmt = $conn->query("SELECT COUNT(*) FROM users");
$totalUsers = $stmt->fetchColumn();
$stmt = $conn->query("SELECT COUNT(*) FROM users WHERE role = 'student'");
$totalStudents = $stmt->fetchColumn();
$stmt = $conn->query("SELECT COUNT(*) FROM users WHERE role = 'employer'");
$totalEmployers = $stmt->fetchColumn();
// Jobs
$stmt = $conn->query("SELECT COUNT(*) FROM job WHERE status = 'active'");
$activeJobs = $stmt->fetchColumn();
$stmt = $conn->query("SELECT COUNT(*) FROM job WHERE status = 'pending'");
$pendingJobs = $stmt->fetchColumn();
$totalJobs = $activeJobs + $pendingJobs;
// Applications
$stmt = $conn->query("SELECT COUNT(*) FROM applications");
$totalApplications = $stmt->fetchColumn();
// Feedback
if (file_exists('../feedbacks.txt')) {
    $totalFeedback = count(file('../feedbacks.txt', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES));
}

// --- Recent Activity Data ---
$recentActivities = [];

// Recent job postings
$stmt = $conn->query("SELECT j.title, e.company_name, j.created_at FROM job j 
                     INNER JOIN employer_profiles e ON j.employer_id = e.id 
                     ORDER BY j.created_at DESC LIMIT 3");
while ($row = $stmt->fetch()) {
    $recentActivities[] = [
        'type' => 'job',
        'icon' => '📄',
        'description' => 'New job posted: "' . $row['title'] . '"',
        'details' => 'Company: ' . $row['company_name'],
        'time' => date('j M Y H:i', strtotime($row['created_at']))
    ];
}

// Recent user registrations
$stmt = $conn->query("SELECT u.email, u.role, u.created_at FROM users u 
                     ORDER BY u.created_at DESC LIMIT 3");
while ($row = $stmt->fetch()) {
    $recentActivities[] = [
        'type' => 'user',
        'icon' => '👤',
        'description' => 'New user registered: ' . $row['email'],
        'details' => 'Role: ' . ucfirst($row['role']),
        'time' => date('j M Y H:i', strtotime($row['created_at']))
    ];
}

// Recent feedback
if (file_exists('../feedbacks.txt')) {
    $lines = file('../feedbacks.txt', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    $feedbacks = [];
    foreach ($lines as $line) {
        $data = json_decode($line, true);
        if ($data && isset($data['rating'], $data['date'])) {
            $feedbacks[] = $data;
        }
    }
    // Sort by date and take latest 3
    usort($feedbacks, function($a, $b) {
        return strtotime($b['date']) - strtotime($a['date']);
    });
    $feedbacks = array_slice($feedbacks, 0, 3);
    
    foreach ($feedbacks as $fb) {
        $recentActivities[] = [
            'type' => 'feedback',
            'icon' => '⭐',
            'description' => 'New feedback received: ' . $fb['rating'] . ' stars',
            'details' => 'User: ' . (isset($fb['user']) ? $fb['user'] : 'Guest'),
            'time' => $fb['date']
        ];
    }
}

// Sort all activities by time (most recent first)
usort($recentActivities, function($a, $b) {
    return strtotime($b['time']) - strtotime($a['time']);
});
$recentActivities = array_slice($recentActivities, 0, 3);

// --- Feedback Tab Data & Deletion ---
$feedbackMsg = '';
$feedbackFile = '../feedbacks.txt';
if (isset($_POST['delete_feedback_date']) && isset($_POST['delete_feedback_user'])) {
    $delDate = $_POST['delete_feedback_date'];
    $delUser = $_POST['delete_feedback_user'];
    if (file_exists($feedbackFile)) {
        $lines = file($feedbackFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        $newLines = [];
        foreach ($lines as $line) {
            $data = json_decode($line, true);
            if ($data && isset($data['date'], $data['user'])) {
                if (!($data['date'] == $delDate && $data['user'] == $delUser)) {
                    $newLines[] = $line;
                }
            } else {
                $newLines[] = $line;
            }
        }
        file_put_contents($feedbackFile, implode("\n", $newLines) . (count($newLines) ? "\n" : ""));
        $feedbackMsg = 'Feedback deleted.';
    }
}
$allFeedbacks = [];
if (file_exists($feedbackFile)) {
    $lines = file($feedbackFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        $data = json_decode($line, true);
        if ($data && isset($data['feedback'], $data['rating'], $data['date'])) {
            $allFeedbacks[] = $data;
        }
    }
    $allFeedbacks = array_reverse($allFeedbacks);
}
// Prepare user name lookup for all user IDs in feedbacks
$userNames = [];
$userIds = array_unique(array_filter(array_map(function($fb) { return is_numeric($fb['user']) ? $fb['user'] : null; }, $allFeedbacks)));
if (!empty($userIds)) {
    $db = new Database();
    $conn = $db->getConnection();
    $placeholders = implode(',', array_fill(0, count($userIds), '?'));
    $sql = "SELECT u.id, s.first_name, s.last_name FROM users u LEFT JOIN student_profiles s ON u.id = s.user_id WHERE u.id IN ($placeholders)";
    $stmt = $conn->prepare($sql);
    $stmt->execute($userIds);
    while ($row = $stmt->fetch()) {
        $userNames[$row['id']] = trim($row['first_name'] . ' ' . $row['last_name']);
    }
}

include '../includes/header.php';
?>

<style>
.overview-cards {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 1.5rem;
    margin-bottom: 2rem;
}

.overview-card {
    background: white;
    border-radius: 8px;
    padding: 1.5rem;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    border: 1px solid #e0e0e0;
}

.overview-card h3 {
    color: #0d6efd;
    font-size: 1.1rem;
    margin-bottom: 1rem;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.overview-card .stats {
    margin-bottom: 1rem;
}

.overview-card .stat-item {
    display: flex;
    justify-content: space-between;
    margin-bottom: 0.5rem;
    font-size: 0.9rem;
}

.overview-card .manage-btn {
    background: #ffc107;
    color: #000;
    border: none;
    padding: 0.5rem 1rem;
    border-radius: 4px;
    font-weight: bold;
    cursor: pointer;
    width: 100%;
    transition: background-color 0.3s;
}

.overview-card .manage-btn:hover {
    background: #ffb300;
}

.recent-activity {
    background: white;
    border-radius: 8px;
    padding: 1.5rem;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    border: 1px solid #e0e0e0;
    margin-bottom: 2rem;
}

.recent-activity h3 {
    color: #0d6efd;
    font-size: 1.2rem;
    margin-bottom: 1rem;
}

.activity-item {
    display: flex;
    align-items: flex-start;
    gap: 1rem;
    padding: 1rem 0;
    border-bottom: 1px solid #f0f0f0;
}

.activity-item:last-child {
    border-bottom: none;
}

.activity-icon {
    font-size: 1.5rem;
    min-width: 2rem;
}

.activity-content {
    flex: 1;
}

.activity-description {
    font-weight: 500;
    margin-bottom: 0.25rem;
}

.activity-details {
    color: #666;
    font-size: 0.9rem;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.feedback-section {
    background: white;
    border-radius: 8px;
    padding: 1.5rem;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    border: 1px solid #e0e0e0;
}

.feedback-section h3 {
    color: #0d6efd;
    font-size: 1.2rem;
    margin-bottom: 1rem;
}

.feedback-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 1rem;
}

.feedback-card {
    background: #f8f9fa;
    border-radius: 6px;
    padding: 1rem;
    border: 1px solid #e9ecef;
}

.feedback-rating {
    margin-bottom: 0.5rem;
}

.feedback-text {
    font-size: 0.9rem;
    margin-bottom: 0.5rem;
    line-height: 1.4;
}

.feedback-meta {
    font-size: 0.8rem;
    color: #666;
    margin-bottom: 0.5rem;
}

.feedback-actions {
    display: flex;
    gap: 0.5rem;
}

@media (max-width: 768px) {
    .overview-cards {
        grid-template-columns: 1fr;
    }
    
    .feedback-grid {
        grid-template-columns: 1fr;
    }
}
</style>

<div class="container py-5">
    <!-- Overview Cards -->
    <div class="overview-cards">
        <div class="overview-card">
            <h3><i class="fas fa-chart-bar"></i>System Stats</h3>
            <div class="stats">
                <div class="stat-item">
                    <span>Total Users:</span>
                    <span><?php echo $totalUsers; ?></span>
                </div>
                <div class="stat-item">
                    <span>Active Jobs:</span>
                    <span><?php echo $activeJobs; ?></span>
                </div>
                <div class="stat-item">
                    <span>Applications:</span>
                    <span><?php echo $totalApplications; ?></span>
                </div>
            </div>
        </div>

        <div class="overview-card">
            <h3><i class="fas fa-users"></i>Users</h3>
            <div class="stats">
                <div class="stat-item">
                    <span>Students:</span>
                    <span><?php echo $totalStudents; ?></span>
                </div>
                <div class="stat-item">
                    <span>Employers:</span>
                    <span><?php echo $totalEmployers; ?></span>
                </div>
            </div>
            <a href="<?php echo BASE_URL; ?>/admin/manage-users.php" class="manage-btn" style="text-decoration: none; display: block; text-align: center;">Manage</a>
        </div>

        <div class="overview-card">
            <h3><i class="fas fa-briefcase"></i>Jobs</h3>
            <div class="stats">
                <div class="stat-item">
                    <span>Active:</span>
                    <span><?php echo $activeJobs; ?></span>
                </div>
                <div class="stat-item">
                    <span>Pending:</span>
                    <span><?php echo $pendingJobs; ?></span>
                </div>
            </div>
            <a href="<?php echo BASE_URL; ?>/admin/manage-jobs.php" class="manage-btn" style="text-decoration: none; display: block; text-align: center;">Manage</a>
        </div>
    </div>

    <!-- Recent Activity -->
    <div class="recent-activity">
        <h3>Recent Activity</h3>
        <?php if (empty($recentActivities)): ?>
            <p class="text-muted">No recent activity</p>
        <?php else: ?>
            <?php foreach ($recentActivities as $activity): ?>
                <div class="activity-item">
                    <div class="activity-icon"><?php echo $activity['icon']; ?></div>
                    <div class="activity-content">
                        <div class="activity-description"><?php echo htmlspecialchars($activity['description']); ?></div>
                        <div class="activity-details">
                            <i class="fas fa-clock"></i>
                            <?php echo htmlspecialchars($activity['time']); ?> | <?php echo htmlspecialchars($activity['details']); ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <!-- Feedback Section -->
    <div class="feedback-section">
        <h3>User Feedback</h3>
        <?php if ($feedbackMsg): ?>
            <div class="alert alert-success"><?php echo $feedbackMsg; ?></div>
        <?php endif; ?>
        
        <?php if (empty($allFeedbacks)): ?>
            <p class="text-muted">No feedback available</p>
        <?php else: ?>
            <div class="feedback-grid">
                <?php foreach ($allFeedbacks as $fb): ?>
                    <div class="feedback-card">
                        <div class="feedback-rating">
                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                <span style="color:<?php echo $i <= $fb['rating'] ? '#ffc107' : '#e4e5e9'; ?>; font-size:1.1em;">★</span>
                            <?php endfor; ?>
                        </div>
                        <div class="feedback-text"><?php echo htmlspecialchars($fb['feedback']); ?></div>
                        <div class="feedback-meta">
                            <?php echo htmlspecialchars($fb['date']); ?>
                            <?php if (is_numeric($fb['user']) && isset($userNames[$fb['user']]) && $userNames[$fb['user']]): ?>
                                - <?php echo htmlspecialchars($userNames[$fb['user']]); ?>
                            <?php elseif (!is_numeric($fb['user'])): ?>
                                - Guest
                            <?php endif; ?>
                        </div>
                        <div class="feedback-actions">
                            <form method="POST" action="" onsubmit="return confirm('Delete this feedback?');" style="display:inline;">
                                <input type="hidden" name="delete_feedback_date" value="<?php echo htmlspecialchars($fb['date']); ?>">
                                <input type="hidden" name="delete_feedback_user" value="<?php echo htmlspecialchars($fb['user']); ?>">
                                <button type="submit" class="btn btn-sm btn-danger">Delete</button>
                            </form>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php include '../includes/footer.php'; ?> 