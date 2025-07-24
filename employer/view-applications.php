<?php
require_once '../config/config.php';
require_once '../config/database.php';

if (!isLoggedIn() || !hasRole('employer')) {
    redirectWith(BASE_URL . '/login.php', 'Please login as an employer', 'error');
}

$db = new Database();
$conn = $db->getConnection();

$jobId = isset($_GET['job_id']) ? (int)$_GET['job_id'] : 0;
if (!$jobId) {
    redirectWith('dashboard.php', 'Invalid job ID.', 'danger');
}

// Get employer profile
$query = "SELECT * FROM employer_profiles WHERE user_id = :user_id";
$stmt = $conn->prepare($query);
$stmt->execute(['user_id' => $_SESSION['user_id']]);
$employer = $stmt->fetch();
if (!$employer) {
    redirectWith(BASE_URL . '/employer/edit-profile.php', 'Please complete your company profile first', 'warning');
}

// Check if the job belongs to this employer
$query = "SELECT * FROM job WHERE id = :job_id AND employer_id = :employer_id";
$stmt = $conn->prepare($query);
$stmt->execute(['job_id' => $jobId, 'employer_id' => $employer['id']]);
$job = $stmt->fetch();
if (!$job) {
    redirectWith('dashboard.php', 'Job not found or does not belong to you.', 'danger');
}

// Get all applications for this job
$query = "SELECT a.*, s.first_name, s.last_name, s.education_level, s.skills, s.bio, s.profile_picture, u.email, a.status as app_status
          FROM applications a
          INNER JOIN student_profiles s ON a.student_id = s.id
          INNER JOIN users u ON s.user_id = u.id
          WHERE a.job_id = :job_id
          ORDER BY a.applied_at DESC";
$stmt = $conn->prepare($query);
$stmt->execute(['job_id' => $jobId]);
$applications = $stmt->fetchAll();

// Handle accept/reject actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['app_id'], $_POST['action'])) {
    $app_id = (int)$_POST['app_id'];
    $action = $_POST['action'];
    // Fetch application and student info
    $stmt = $conn->prepare("SELECT a.*, s.user_id, u.email, s.first_name FROM applications a INNER JOIN student_profiles s ON a.student_id = s.id INNER JOIN users u ON s.user_id = u.id WHERE a.id = ?");
    $stmt->execute([$app_id]);
    $app_info = $stmt->fetch();
    if (!$app_info) {
        redirectWith("view-applications.php?job_id=$jobId", "Application not found.", "danger");
        exit;
    }
    $student_id = $app_info['student_id'];
    $student_email = $app_info['email'];
    $student_name = $app_info['first_name'];
    $job_title = $job['title'];
    if (in_array($action, ['accept', 'reject'])) {
        $new_status = $action === 'accept' ? 'accepted' : 'rejected';
        $stmt = $conn->prepare("UPDATE applications SET status = :status WHERE id = :id");
        $stmt->execute(['status' => $new_status, 'id' => $app_id]);
        // Add notification
        $notif_msg = "Your application for '$job_title' was " . ($new_status === 'accepted' ? 'accepted' : 'rejected') . ".";
        $notif_link = BASE_URL . "/student/view-application.php?id=$app_id";
        $stmt = $conn->prepare("INSERT INTO notifications (student_id, message, link) VALUES (?, ?, ?)");
        $stmt->execute([$student_id, $notif_msg, $notif_link]);
        // Send email
        $subject = "Application Status Update: $job_title";
        $body = "Hi $student_name,\n\nYour application for '$job_title' was " . ($new_status === 'accepted' ? 'accepted' : 'rejected') . ".\n\nYou can view the details here: $notif_link\n\nBest regards,\nYour Career Launchpad Team";
        mail($student_email, $subject, $body);
        header("Location: view-applications.php?job_id=$jobId");
        exit;
    } elseif ($action === 'undo_reject' || $action === 'undo_accept') {
        $stmt = $conn->prepare("UPDATE applications SET status = 'pending' WHERE id = :id");
        $stmt->execute(['id' => $app_id]);
        // Add notification
        $notif_msg = "Your application for '$job_title' status was reverted to pending.";
        $notif_link = BASE_URL . "/student/view-application.php?id=$app_id";
        $stmt = $conn->prepare("INSERT INTO notifications (student_id, message, link) VALUES (?, ?, ?)");
        $stmt->execute([$student_id, $notif_msg, $notif_link]);
        // Send email
        $subject = "Application Status Update: $job_title";
        $body = "Hi $student_name,\n\nYour application for '$job_title' status was reverted to pending.\n\nYou can view the details here: $notif_link\n\nBest regards,\nYour Career Launchpad Team";
        mail($student_email, $subject, $body);
        header("Location: view-applications.php?job_id=$jobId");
        exit;
    }
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
.applications-table {
    width: 100%;
    border-collapse: separate;
    border-spacing: 0;
}
.applications-table th.status-col, .applications-table td.status-col {
    width: 90px;
    min-width: 70px;
    max-width: 110px;
    text-align: center;
}
.applications-table th.applied-date-col, .applications-table td.applied-date-col {
    width: 130px;
    min-width: 100px;
    max-width: 160px;
    text-align: center;
}
.applicant-info {
    display: flex;
    align-items: flex-start;
    gap: 1rem;
}
.applicant-details {
    flex: 1;
    min-width: 0;
}
.applicant-details .applicant-name {
    font-weight: bold;
    font-size: 1.1rem;
}
.applicant-details .applicant-bio {
    color: #666;
    font-size: 0.97rem;
    margin-bottom: 0.2rem;
}
.applicant-details .applicant-meta {
    font-size: 0.97rem;
    color: #444;
    margin-bottom: 0.2rem;
}
@media (max-width: 900px) {
    .applicant-info { flex-direction: column; align-items: flex-start; }
}
</style>
<div class="container py-4">
    <a href="dashboard.php" class="back-btn"><i class="fas fa-arrow-left"></i> Back to Dashboard</a>
    <div class="card mb-4">
        <div class="card-header bg-white">
            <h4 class="mb-0">Applications for: <span class="text-primary"><?php echo htmlspecialchars($job['title']); ?></span></h4>
        </div>
        <div class="card-body">
            <?php if (empty($applications)): ?>
                <div class="text-center py-5">
                    <img src="<?php echo BASE_URL; ?>/assets/images/no-data.svg" alt="No Applications" style="width: 120px; height: 120px; opacity: 0.5;">
                    <h5 class="mt-3">No Applications Yet</h5>
                    <p class="text-muted">No students have applied for this job yet.</p>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover applications-table">
                        <thead>
                            <tr>
                                <th>Applicant</th>
                                <th class="status-col">Status</th>
                                <th class="applied-date-col">Applied Date</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($applications as $app): ?>
                                <tr>
                                    <td>
                                        <div class="applicant-info">
                                            <img src="<?php echo !empty($app['profile_picture']) ? BASE_URL . '/' . $app['profile_picture'] : BASE_URL . '/assets/images/default-avatar.png'; ?>" 
                                                 alt="<?php echo htmlspecialchars($app['first_name'] . ' ' . $app['last_name']); ?>" 
                                                 class="me-2 rounded-circle" 
                                                 style="width: 36px; height: 36px; object-fit: cover;">
                                            <div class="applicant-details">
                                                <div class="applicant-name"><?php echo htmlspecialchars($app['first_name'] . ' ' . $app['last_name']); ?></div>
                                                <div class="applicant-bio"><?php echo htmlspecialchars($app['bio']); ?></div>
                                                <div class="applicant-meta"><strong>Email:</strong> <?php echo htmlspecialchars($app['email']); ?></div>
                                                <div class="applicant-meta"><strong>Education:</strong> <?php echo htmlspecialchars($app['education_level']); ?></div>
                                                <div class="applicant-meta"><strong>Skills:</strong> <?php echo htmlspecialchars($app['skills']); ?></div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="status-col">
                                        <span class="badge bg-<?php 
                                            echo match($app['app_status']) {
                                                'pending' => 'warning',
                                                'accepted' => 'success',
                                                'rejected' => 'danger',
                                                default => 'secondary'
                                            };
                                        ?>">
                                            <?php echo ucfirst($app['app_status']); ?>
                                        </span>
                                    </td>
                                    <td class="applied-date-col"><?php echo date('M d, Y', strtotime($app['applied_at'])); ?></td>
                                    <td>
                                        <div class="d-flex flex-row flex-wrap gap-2 align-items-center">
                                            <a href="mailto:<?php echo htmlspecialchars($app['email']); ?>" class="btn btn-sm btn-outline-primary">Contact</a>
                                            <!-- View Details Modal Trigger -->
                                            <button type="button" class="btn btn-sm btn-info" data-bs-toggle="modal" data-bs-target="#appModal<?php echo $app['id']; ?>">View</button>
                                            <?php if ($app['app_status'] === 'pending'): ?>
                                                <form method="POST" action="">
                                                    <input type="hidden" name="app_id" value="<?php echo $app['id']; ?>">
                                                    <button type="submit" name="action" value="accept" class="btn btn-sm btn-success">Accept</button>
                                                    <button type="submit" name="action" value="reject" class="btn btn-sm btn-danger">Reject</button>
                                                </form>
                                            <?php elseif ($app['app_status'] === 'rejected'): ?>
                                                <form method="POST" action="">
                                                    <input type="hidden" name="app_id" value="<?php echo $app['id']; ?>">
                                                    <button type="submit" name="action" value="undo_reject" class="btn btn-sm btn-secondary">Undo Reject</button>
                                                </form>
                                            <?php elseif ($app['app_status'] === 'accepted'): ?>
                                                <form method="POST" action="">
                                                    <input type="hidden" name="app_id" value="<?php echo $app['id']; ?>">
                                                    <button type="submit" name="action" value="undo_accept" class="btn btn-sm btn-secondary">Undo Accept</button>
                                                </form>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                                <!-- Modal for application details -->
                                <div class="modal fade" id="appModal<?php echo $app['id']; ?>" tabindex="-1">
                                  <div class="modal-dialog modal-lg">
                                    <div class="modal-content">
                                      <div class="modal-header">
                                        <h5 class="modal-title">Application Details: <?php echo htmlspecialchars($app['first_name'] . ' ' . $app['last_name']); ?></h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                      </div>
                                      <div class="modal-body">
                                        <p><strong>Cover Letter:</strong></p>
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
                                        <p><strong>Resume:</strong></p>
                                        <?php
                                        // Fetch resume path
                                        $stmt_resume = $conn->prepare("SELECT resume_path FROM student_profiles WHERE id = ?");
                                        $stmt_resume->execute([$app['student_id']]);
                                        $resume = $stmt_resume->fetchColumn();
                                        ?>
                                        <?php if ($resume): ?>
                                          <?php
                                          $resume_url = BASE_URL . '/' . $resume;
                                          $resume_ext = strtolower(pathinfo($resume, PATHINFO_EXTENSION));
                                          $can_preview = in_array($resume_ext, ['pdf', 'jpg', 'jpeg', 'png']);
                                          ?>
                                          <?php if ($can_preview): ?>
                                            <button type="button" class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#resumeModal<?php echo $app['id']; ?>">View Resume</button>
                                          <?php else: ?>
                                            <a href="<?php echo $resume_url; ?>" target="_blank" class="btn btn-outline-primary">Download Resume</a>
                                          <?php endif; ?>
                                        <?php else: ?>
                                          <span class="text-muted">No resume uploaded.</span>
                                        <?php endif; ?>
                                      </div>
                                      <div class="modal-footer">
                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                      </div>
                                    </div>
                                  </div>
                                </div>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>
<!-- Render all resume modals after the table -->
<?php foreach ($applications as $app): ?>
<?php
// Fetch resume path
$stmt_resume = $conn->prepare("SELECT resume_path FROM student_profiles WHERE id = ?");
$stmt_resume->execute([$app['student_id']]);
$resume = $stmt_resume->fetchColumn();
$resume_url = BASE_URL . '/' . $resume;
$resume_ext = strtolower(pathinfo($resume, PATHINFO_EXTENSION));
$can_preview = in_array($resume_ext, ['pdf', 'jpg', 'jpeg', 'png']);
?>
<?php if ($resume && $can_preview): ?>
<div class="modal fade" id="resumeModal<?php echo $app['id']; ?>" tabindex="-1">
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
<?php endif; ?>
<?php endforeach; ?>
<?php include '../includes/footer.php'; ?> 