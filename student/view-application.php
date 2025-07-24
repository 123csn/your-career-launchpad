<?php
require_once '../config/config.php';
require_once '../config/database.php';

if (!isLoggedIn() || !hasRole('student')) {
    redirectWith(BASE_URL . '/login.php', 'Please login as a student', 'error');
}

$db = new Database();
$conn = $db->getConnection();

$appId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if (!$appId) {
    redirectWith('applications.php', 'Invalid application ID.', 'danger');
}

// Get student profile
$query = "SELECT * FROM student_profiles WHERE user_id = :user_id";
$stmt = $conn->prepare($query);
$stmt->execute(['user_id' => $_SESSION['user_id']]);
$student = $stmt->fetch();
if (!$student) {
    redirectWith(BASE_URL . '/student/complete-profile.php', 'Please complete your profile first', 'warning');
}

// Get application details
$query = "SELECT a.*, j.title as job_title, j.description as job_description, j.requirements as job_requirements, j.location as job_location, j.job_type, j.salary_range, j.deadline, e.company_name, e.logo_path, e.website, e.about as company_about
          FROM applications a
          INNER JOIN job j ON a.job_id = j.id
          INNER JOIN employer_profiles e ON j.employer_id = e.id
          WHERE a.id = :app_id AND a.student_id = :student_id";
$stmt = $conn->prepare($query);
$stmt->execute(['app_id' => $appId, 'student_id' => $student['id']]);
$app = $stmt->fetch();

if (!$app) {
    redirectWith('applications.php', 'Application not found.', 'danger');
}

// Ensure employer_id is available in $app
if (empty($app['employer_id'])) {
    $stmt_empid = $conn->prepare('SELECT id FROM employer_profiles WHERE company_name = ? LIMIT 1');
    $stmt_empid->execute([$app['company_name']]);
    $app['employer_id'] = $stmt_empid->fetchColumn();
}

// Handle cancel application
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cancel_application'])) {
    $stmt = $conn->prepare("DELETE FROM applications WHERE id = :id AND student_id = :student_id AND status = 'pending'");
    $stmt->execute(['id' => $appId, 'student_id' => $student['id']]);
    redirectWith('applications.php', 'Application cancelled successfully.', 'success');
}

// Fetch employer email for contact using employer_profiles.user_id
$employer_email = null;
if (!empty($app['employer_id'])) {
    $stmt_email = $conn->prepare('SELECT u.email FROM employer_profiles e INNER JOIN users u ON e.user_id = u.id WHERE e.id = ?');
    $stmt_email->execute([$app['employer_id']]);
    $employer_email = $stmt_email->fetchColumn();
}

include '../includes/header.php';
?>
<style>
.back-btn {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.4rem 1.1rem 0.4rem 0.9rem;
    border: 1.5px solid #3949ab;
    border-radius: 30px;
    background: #fff;
    color: #3949ab;
    font-weight: 600;
    text-decoration: none;
    font-size: 1.05rem;
    transition: background 0.2s, color 0.2s, border 0.2s;
    margin-bottom: 1.2rem;
}
.back-btn:hover {
    background: #3949ab;
    color: #fff;
    border-color: #3949ab;
    text-decoration: none;
}
</style>
<div class="container py-4">
    <a href="applications.php" class="back-btn"><i class="fas fa-arrow-left"></i> Back to Applications</a>
    <div class="card mb-4">
        <div class="card-body">
            <div class="d-flex align-items-center mb-3">
                <a href="../employer-profile.php?id=<?php echo $app['employer_id']; ?>" target="_blank">
                    <img src="<?php echo !empty($app['logo_path']) ? BASE_URL . '/' . $app['logo_path'] : BASE_URL . '/assets/images/DefaultCompanyLogo.jpg'; ?>" 
                         alt="<?php echo htmlspecialchars($app['company_name']); ?>" 
                         style="width: 60px; height: 60px; object-fit: cover; border-radius: 12px; margin-right: 1rem; cursor:pointer;">
                </a>
                <div>
                    <h4 class="mb-0"><?php echo htmlspecialchars($app['job_title']); ?></h4>
                    <div class="text-muted mb-1">at <?php echo htmlspecialchars($app['company_name']); ?></div>
                    <span class="badge bg-<?php 
                        echo match($app['status']) {
                            'pending' => 'warning',
                            'accepted' => 'success',
                            'rejected' => 'danger',
                            default => 'secondary'
                        };
                    ?>">
                        <?php echo ucfirst($app['status']); ?> Application
                    </span>
                </div>
            </div>
            <ul class="list-unstyled mb-3">
                <li><strong>Location:</strong> <?php echo htmlspecialchars($app['job_location']); ?></li>
                <li><strong>Job Type:</strong> <?php echo htmlspecialchars($app['job_type']); ?></li>
                <li><strong>Salary Range:</strong> <?php echo htmlspecialchars($app['salary_range']); ?></li>
                <li><strong>Applied Date:</strong> <?php echo date('M d, Y', strtotime($app['applied_at'])); ?></li>
                <?php if ($app['deadline']): ?>
                <li><strong>Application Deadline:</strong> <?php echo date('M d, Y', strtotime($app['deadline'])); ?></li>
                <?php endif; ?>
            </ul>
            <h5>Job Description</h5>
            <p><?php echo nl2br(htmlspecialchars($app['job_description'])); ?></p>
            <h5>Requirements</h5>
            <p><?php echo nl2br(htmlspecialchars($app['job_requirements'])); ?></p>
            <h5>About Company</h5>
            <p><?php echo nl2br(htmlspecialchars($app['company_about'])); ?></p>
            <?php if (!empty($app['website'])): ?>
                <p><strong>Company Website:</strong> <a href="<?php echo htmlspecialchars($app['website']); ?>" target="_blank"><?php echo htmlspecialchars($app['website']); ?></a></p>
            <?php endif; ?>
            <h5>Cover Letter</h5>
            <div class="border rounded p-2 mb-3" style="white-space: pre-line; background: #f8f9fa;">
                <?php
                $cover_letter_val = $app['cover_letter'] ?? '';
                if (strpos($cover_letter_val, '[FILE] ') === 0) {
                    $file_path = trim(str_replace('[FILE]', '', $cover_letter_val));
                    $file_name = basename($file_path);
                    echo '<a href="' . BASE_URL . '/' . $file_path . '" target="_blank" class="btn btn-outline-primary">Download Cover Letter (' . htmlspecialchars($file_name) . ')</a>';
                } else {
                    echo htmlspecialchars($cover_letter_val ?: 'No cover letter provided.');
                }
                ?>
            </div>
            <h5>Resume</h5>
            <?php
            $stmt_resume = $conn->prepare("SELECT resume_path FROM student_profiles WHERE id = ?");
            $stmt_resume->execute([$app['student_id'] ?? $student['id']]);
            $resume = $stmt_resume->fetchColumn();
            ?>
            <?php if ($resume): ?>
                <?php
                $resume_url = BASE_URL . '/' . $resume;
                $resume_ext = strtolower(pathinfo($resume, PATHINFO_EXTENSION));
                $can_preview = in_array($resume_ext, ['pdf', 'jpg', 'jpeg', 'png']);
                ?>
                <?php if ($can_preview): ?>
                    <button type="button" class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#resumeModal">View Resume</button>
                    <!-- Resume Preview Modal -->
                    <div class="modal fade" id="resumeModal" tabindex="-1">
                      <div class="modal-dialog modal-xl">
                        <div class="modal-content">
                          <div class="modal-header">
                            <h5 class="modal-title">Resume Preview</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                          </div>
                          <div class="modal-body" style="min-height:70vh;">
                            <?php if ($resume_ext === 'pdf'): ?>
                              <iframe src="<?php echo $resume_url; ?>" width="100%" height="600px" style="border:none;"></iframe>
                            <?php else: ?>
                              <img src="<?php echo $resume_url; ?>" alt="Resume Image" class="img-fluid mx-auto d-block" style="max-height:65vh;">
                            <?php endif; ?>
                          </div>
                          <div class="modal-footer">
                            <a href="<?php echo $resume_url; ?>" target="_blank" class="btn btn-primary">Download</a>
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                          </div>
                        </div>
                      </div>
                    </div>
                <?php else: ?>
                    <a href="<?php echo $resume_url; ?>" target="_blank" class="btn btn-outline-primary">Download Resume</a>
                <?php endif; ?>
            <?php else: ?>
                <span class="text-muted">No resume uploaded.</span>
            <?php endif; ?>
            <?php if ($app['status'] === 'pending'): ?>
                <form method="POST" onsubmit="return confirm('Are you sure you want to cancel this application?');" class="mt-3">
                    <button type="submit" name="cancel_application" class="btn btn-danger">Cancel Application</button>
                </form>
            <?php endif; ?>
            <h5>Contact Employer</h5>
            <ul class="list-unstyled mb-3">
                <?php if ($employer_email): ?>
                    <li><strong>Email:</strong> <a href="mailto:<?php echo htmlspecialchars($employer_email); ?>">Contact via Email</a></li>
                <?php endif; ?>
                <?php if (!empty($app['website'])): ?>
                    <li><strong>Website:</strong> <a href="<?php echo htmlspecialchars($app['website']); ?>" target="_blank"><?php echo htmlspecialchars($app['website']); ?></a></li>
                <?php endif; ?>
            </ul>
            <?php if (!empty($app['employer_id'])): ?>
                <a href="../employer-profile.php?id=<?php echo $app['employer_id']; ?>" class="btn btn-outline-info mb-3" target="_blank">
                    View Employer Profile
                </a>
            <?php endif; ?>
        </div>
    </div>
</div>
<?php include '../includes/footer.php'; ?> 