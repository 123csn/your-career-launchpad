<?php
require_once '../config/config.php';
require_once '../config/database.php';

if (!isLoggedIn() || !hasRole('admin')) {
    redirectWith(BASE_URL . '/login.php', 'Please login as an admin to access this page.', 'warning');
}

$db = new Database();
$conn = $db->getConnection();

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

// --- Jobs Tab Data & Actions ---
$jobMsg = '';
if (isset($_POST['delete_job_id']) && is_numeric($_POST['delete_job_id'])) {
    $jobId = (int)$_POST['delete_job_id'];
    $stmt = $conn->prepare("DELETE FROM job WHERE id = ?");
    $stmt->execute([$jobId]);
    $jobMsg = 'Job deleted successfully.';
}
if (isset($_POST['toggle_job_id']) && is_numeric($_POST['toggle_job_id']) && isset($_POST['new_status'])) {
    $jobId = (int)$_POST['toggle_job_id'];
    $newStatus = $_POST['new_status'] === 'active' ? 'active' : 'closed';
    $stmt = $conn->prepare("UPDATE job SET status = ? WHERE id = ?");
    $stmt->execute([$newStatus, $jobId]);
    $jobMsg = 'Job status updated successfully.';
}

$jobs = $conn->query("SELECT j.id, j.title, j.status, j.location, j.job_type, e.company_name FROM job j INNER JOIN employer_profiles e ON j.employer_id = e.id ORDER BY j.id DESC")->fetchAll();

include '../includes/header.php';
?>

<style>
.management-header {
    background: white;
    border-radius: 8px;
    padding: 1.5rem;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    border: 1px solid #e0e0e0;
    margin-bottom: 2rem;
}

.management-header h1 {
    color: #0d6efd;
    font-size: 1.8rem;
    margin-bottom: 0.5rem;
}

.back-btn {
    background: #6c757d;
    color: white;
    border: none;
    padding: 0.5rem 1rem;
    border-radius: 4px;
    text-decoration: none;
    display: inline-block;
    margin-bottom: 1rem;
    transition: background-color 0.3s;
}

.back-btn:hover {
    background: #5a6268;
    color: white;
    text-decoration: none;
}

.table-container {
    background: white;
    border-radius: 8px;
    padding: 1.5rem;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    border: 1px solid #e0e0e0;
}

.table th {
    background: #f8f9fa;
    border-color: #dee2e6;
    color: #495057;
    font-weight: 600;
}

.table td {
    vertical-align: middle;
}

.status-badge {
    padding: 0.25rem 0.5rem;
    border-radius: 4px;
    font-size: 0.8rem;
    font-weight: 500;
}

.status-active {
    background: #28a745;
    color: white;
}

.status-closed {
    background: #6c757d;
    color: white;
}

.job-type-badge {
    padding: 0.25rem 0.5rem;
    border-radius: 4px;
    font-size: 0.8rem;
    font-weight: 500;
    background: #17a2b8;
    color: white;
}

.generate-section {
    background: white;
    border-radius: 8px;
    padding: 1.5rem;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    border: 1px solid #e0e0e0;
    margin-bottom: 2rem;
}

.generate-btn {
    background: #ffc107;
    color: #000;
    border: none;
    padding: 0.75rem 1.5rem;
    border-radius: 4px;
    font-weight: bold;
    cursor: pointer;
    font-size: 1rem;
    transition: background-color 0.3s;
}

.generate-btn:hover {
    background: #ffb300;
}
</style>

<div class="container py-5">
    <div class="management-header">
        <a href="<?php echo BASE_URL; ?>/admin/dashboard.php" class="back-btn">
            <i class="fas fa-arrow-left"></i> Back to Dashboard
        </a>
        <h1><i class="fas fa-briefcase"></i> Job Management</h1>
        <p class="text-muted">Manage all job postings including active, closed, and pending jobs.</p>
    </div>

    <!-- Generate Test Jobs Section -->
    <div class="generate-section">
        <h3><i class="fas fa-plus-circle"></i> Generate Test Data</h3>
        <p class="text-muted mb-3">Generate sample job postings for testing purposes.</p>
        <form method="POST">
            <button type="submit" name="generate_jobs" class="generate-btn">
                <i class="fas fa-magic"></i> Generate Test Jobs
            </button>
        </form>
        <?php if ($success): ?>
            <div class="alert alert-success mt-3">
                <i class="fas fa-check-circle"></i> 20 test jobs have been generated and added to the database.
            </div>
        <?php endif; ?>
    </div>

    <div class="table-container">
        <?php if ($jobMsg): ?>
            <div class="alert alert-success alert-dismissible fade show">
                <i class="fas fa-check-circle"></i> <?php echo $jobMsg; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <div class="table-responsive">
            <table class="table table-bordered table-hover align-middle">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Title</th>
                        <th>Company</th>
                        <th>Location</th>
                        <th>Type</th>
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
                            <td><?php echo htmlspecialchars($job['location']); ?></td>
                            <td>
                                <span class="job-type-badge">
                                    <?php echo ucfirst(str_replace('-', ' ', $job['job_type'])); ?>
                                </span>
                            </td>
                            <td>
                                <span class="status-badge status-<?php echo $job['status']; ?>">
                                    <?php echo ucfirst($job['status']); ?>
                                </span>
                            </td>
                            <td>
                                <form method="POST" style="display:inline;">
                                    <input type="hidden" name="toggle_job_id" value="<?php echo $job['id']; ?>">
                                    <input type="hidden" name="new_status" value="<?php echo $job['status'] === 'active' ? 'closed' : 'active'; ?>">
                                    <button type="submit" class="btn btn-sm btn-outline-<?php echo $job['status'] === 'active' ? 'secondary' : 'success'; ?>">
                                        <i class="fas fa-<?php echo $job['status'] === 'active' ? 'pause' : 'play'; ?>"></i>
                                        <?php echo $job['status'] === 'active' ? 'Close' : 'Activate'; ?>
                                    </button>
                                </form>
                                <form method="POST" style="display:inline;" onsubmit="return confirm('Are you sure you want to delete this job? This action cannot be undone.');">
                                    <input type="hidden" name="delete_job_id" value="<?php echo $job['id']; ?>">
                                    <button type="submit" class="btn btn-sm btn-danger">
                                        <i class="fas fa-trash"></i> Delete
                                    </button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <?php if (empty($jobs)): ?>
            <div class="text-center py-4">
                <i class="fas fa-briefcase fa-3x text-muted mb-3"></i>
                <p class="text-muted">No jobs found.</p>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php include '../includes/footer.php'; ?> 