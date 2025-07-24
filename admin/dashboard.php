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

// --- Stats Tab Data ---
$totalUsers = $totalStudents = $totalEmployers = $totalJobs = $totalApplications = $totalFeedback = 0;
// Users
$stmt = $conn->query("SELECT COUNT(*) FROM users");
$totalUsers = $stmt->fetchColumn();
$stmt = $conn->query("SELECT COUNT(*) FROM users WHERE role = 'student'");
$totalStudents = $stmt->fetchColumn();
$stmt = $conn->query("SELECT COUNT(*) FROM users WHERE role = 'employer'");
$totalEmployers = $stmt->fetchColumn();
// Jobs
$stmt = $conn->query("SELECT COUNT(*) FROM job");
$totalJobs = $stmt->fetchColumn();
// Applications
$stmt = $conn->query("SELECT COUNT(*) FROM applications");
$totalApplications = $stmt->fetchColumn();
// Feedback
if (file_exists('../feedbacks.txt')) {
    $totalFeedback = count(file('../feedbacks.txt', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES));
}

// --- Users Tab Data & Deletion ---
$userDeleteMsg = '';
if (isset($_POST['delete_user_id']) && is_numeric($_POST['delete_user_id'])) {
    $deleteId = (int)$_POST['delete_user_id'];
    if ($deleteId !== $_SESSION['user_id']) { // Prevent self-delete
        $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
        $stmt->execute([$deleteId]);
        $userDeleteMsg = 'User deleted.';
    }
}
$users = $conn->query("SELECT u.id, u.email, u.role, s.first_name, s.last_name, e.company_name FROM users u
    LEFT JOIN student_profiles s ON u.id = s.user_id
    LEFT JOIN employer_profiles e ON u.id = e.user_id
    ORDER BY u.role, u.id")->fetchAll();

// --- Jobs Tab Data & Actions ---
$jobMsg = '';
if (isset($_POST['delete_job_id']) && is_numeric($_POST['delete_job_id'])) {
    $jobId = (int)$_POST['delete_job_id'];
    $stmt = $conn->prepare("DELETE FROM job WHERE id = ?");
    $stmt->execute([$jobId]);
    $jobMsg = 'Job deleted.';
}
if (isset($_POST['toggle_job_id']) && is_numeric($_POST['toggle_job_id']) && isset($_POST['new_status'])) {
    $jobId = (int)$_POST['toggle_job_id'];
    $newStatus = $_POST['new_status'] === 'active' ? 'active' : 'closed';
    $stmt = $conn->prepare("UPDATE job SET status = ? WHERE id = ?");
    $stmt->execute([$newStatus, $jobId]);
    $jobMsg = 'Job status updated.';
}
$jobs = $conn->query("SELECT j.id, j.title, j.status, e.company_name FROM job j INNER JOIN employer_profiles e ON j.employer_id = e.id ORDER BY j.id DESC")->fetchAll();

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
<div class="container py-5">
    <h2 class="mb-4">Admin Dashboard</h2>
    <ul class="nav nav-tabs mb-4" id="adminTab" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link active" id="stats-tab" data-bs-toggle="tab" data-bs-target="#stats" type="button" role="tab">Stats</button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="users-tab" data-bs-toggle="tab" data-bs-target="#users" type="button" role="tab">Users</button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="jobs-tab" data-bs-toggle="tab" data-bs-target="#jobs" type="button" role="tab">Jobs</button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="feedback-tab" data-bs-toggle="tab" data-bs-target="#feedback" type="button" role="tab">Feedback</button>
        </li>
    </ul>
    <div class="tab-content" id="adminTabContent">
        <div class="tab-pane fade show active" id="stats" role="tabpanel">
            <div class="row g-4 mb-4">
                <div class="col-md-4">
                    <div class="card text-center">
                        <div class="card-body">
                            <h5 class="card-title">Total Users</h5>
                            <h2><?php echo $totalUsers; ?></h2>
                            <div class="text-muted">Students: <?php echo $totalStudents; ?> | Employers: <?php echo $totalEmployers; ?></div>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card text-center">
                        <div class="card-body">
                            <h5 class="card-title">Total Jobs</h5>
                            <h2><?php echo $totalJobs; ?></h2>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card text-center">
                        <div class="card-body">
                            <h5 class="card-title">Total Applications</h5>
                            <h2><?php echo $totalApplications; ?></h2>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card text-center">
                        <div class="card-body">
                            <h5 class="card-title">Total Feedback</h5>
                            <h2><?php echo $totalFeedback; ?></h2>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="tab-pane fade" id="users" role="tabpanel">
            <?php if ($userDeleteMsg): ?>
                <div class="alert alert-success"><?php echo $userDeleteMsg; ?></div>
            <?php endif; ?>
            <div class="table-responsive">
                <table class="table table-bordered table-hover align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>ID</th>
                            <th>Role</th>
                            <th>Name / Company</th>
                            <th>Email</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($users as $user): ?>
                            <tr>
                                <td><?php echo $user['id']; ?></td>
                                <td><?php echo ucfirst($user['role']); ?></td>
                                <td>
                                    <?php
                                    if ($user['role'] === 'student') {
                                        echo htmlspecialchars(trim($user['first_name'] . ' ' . $user['last_name']));
                                    } elseif ($user['role'] === 'employer') {
                                        echo htmlspecialchars($user['company_name']);
                                    } else {
                                        echo '-';
                                    }
                                    ?>
                                </td>
                                <td><?php echo htmlspecialchars($user['email']); ?></td>
                                <td>
                                    <?php if ($user['id'] !== $_SESSION['user_id']): ?>
                                        <form method="POST" style="display:inline;" onsubmit="return confirm('Delete this user?');">
                                            <input type="hidden" name="delete_user_id" value="<?php echo $user['id']; ?>">
                                            <button type="submit" class="btn btn-sm btn-danger">Delete</button>
                                        </form>
                                    <?php else: ?>
                                        <span class="text-muted">(You)</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <div class="tab-pane fade" id="jobs" role="tabpanel">
            <?php if ($jobMsg): ?>
                <div class="alert alert-success"><?php echo $jobMsg; ?></div>
            <?php endif; ?>
            <div class="table-responsive">
                <table class="table table-bordered table-hover align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>ID</th>
                            <th>Title</th>
                            <th>Company</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($jobs as $job): ?>
                            <tr>
                                <td><?php echo $job['id']; ?></td>
                                <td><?php echo htmlspecialchars($job['title']); ?></td>
                                <td><?php echo htmlspecialchars($job['company_name']); ?></td>
                                <td>
                                    <span class="badge bg-<?php echo $job['status'] === 'active' ? 'success' : 'secondary'; ?>">
                                        <?php echo ucfirst($job['status']); ?>
                                    </span>
                                </td>
                                <td>
                                    <form method="POST" style="display:inline;">
                                        <input type="hidden" name="toggle_job_id" value="<?php echo $job['id']; ?>">
                                        <input type="hidden" name="new_status" value="<?php echo $job['status'] === 'active' ? 'closed' : 'active'; ?>">
                                        <button type="submit" class="btn btn-sm btn-outline-<?php echo $job['status'] === 'active' ? 'secondary' : 'success'; ?>">
                                            <?php echo $job['status'] === 'active' ? 'Close' : 'Reactivate'; ?>
                                        </button>
                                    </form>
                                    <form method="POST" style="display:inline;" onsubmit="return confirm('Delete this job?');">
                                        <input type="hidden" name="delete_job_id" value="<?php echo $job['id']; ?>">
                                        <button type="submit" class="btn btn-sm btn-danger">Delete</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <form method="POST" class="mt-4">
                <button type="submit" name="generate_jobs" class="btn btn-primary btn-lg">Generate Test Jobs</button>
            </form>
            <?php if ($success): ?>
                <div class="alert alert-success mt-3">20 test jobs have been generated and added to the database.</div>
            <?php endif; ?>
        </div>
        <div class="tab-pane fade" id="feedback" role="tabpanel">
            <?php if ($feedbackMsg): ?>
                <div class="alert alert-success"><?php echo $feedbackMsg; ?></div>
            <?php endif; ?>
            <div class="row g-3">
                <?php foreach ($allFeedbacks as $fb): ?>
                    <div class="col-md-6 col-lg-4">
                        <div class="card h-100 mb-3">
                            <div class="card-body">
                                <div class="mb-2">
                                    <?php for ($i = 1; $i <= 5; $i++): ?>
                                        <span style="color:<?php echo $i <= $fb['rating'] ? '#ffc107' : '#e4e5e9'; ?>; font-size:1.2em;">★</span>
                                    <?php endfor; ?>
                                </div>
                                <p class="mb-1"><?php echo nl2br(htmlspecialchars($fb['feedback'])); ?></p>
                                <div class="text-muted small mb-2">Posted on <?php echo htmlspecialchars($fb['date']); ?><?php 
                                    if (is_numeric($fb['user']) && isset($userNames[$fb['user']]) && $userNames[$fb['user']]) {
                                        echo ' &ndash; ' . htmlspecialchars($userNames[$fb['user']]);
                                    } elseif (!is_numeric($fb['user'])) {
                                        echo ' &ndash; Guest';
                                    }
                                ?></div>
                                <form method="POST" action="" onsubmit="return confirm('Delete this feedback?');">
                                    <input type="hidden" name="delete_feedback_date" value="<?php echo htmlspecialchars($fb['date']); ?>">
                                    <input type="hidden" name="delete_feedback_user" value="<?php echo htmlspecialchars($fb['user']); ?>">
                                    <button type="submit" class="btn btn-sm btn-danger">Delete</button>
                                </form>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>
<script>
    // Optionally, activate the first tab on page load
    var triggerTabList = [].slice.call(document.querySelectorAll('#adminTab button'));
    triggerTabList.forEach(function (triggerEl) {
        triggerEl.addEventListener('click', function (event) {
            event.preventDefault();
            var tabTrigger = new bootstrap.Tab(triggerEl);
            tabTrigger.show();
        });
    });
</script>
<?php include '../includes/footer.php'; ?> 