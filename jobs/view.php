<?php
require_once '../config/config.php';
require_once '../config/database.php';

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Get job ID
$job_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$job_id) {
    redirectWith(BASE_URL . '/job.php', 'Invalid job ID.', 'danger');
}

$db = new Database();
$conn = $db->getConnection();

// Get job details
$query = "SELECT j.*, e.company_name, e.logo_path, e.website, e.about as company_about 
          FROM job j 
          INNER JOIN employer_profiles e ON j.employer_id = e.id 
          WHERE j.id = ? AND j.status = 'active'";
$stmt = $conn->prepare($query);
$stmt->execute([$job_id]);
$job = $stmt->fetch();

if (!$job) {
    redirectWith(BASE_URL . '/job.php', 'Job not found.', 'danger');
}

// Check if user has already applied
$has_applied = false;
if (isLoggedIn() && hasRole('student')) {
    $stmt = $conn->prepare("SELECT id FROM applications WHERE job_id = ? AND student_id = (SELECT id FROM student_profiles WHERE user_id = ?)");
    $stmt->execute([$job_id, $_SESSION['user_id']]);
    $has_applied = $stmt->fetch() !== false;
}

// Process job application
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    echo "<pre>POST: "; print_r($_POST); echo "</pre>";
    echo "<pre>FILES: "; print_r($_FILES); echo "</pre>";

    $cover_letter = sanitizeInput($_POST['cover_letter']);
    $cover_letter_file_path = '';
    // Handle cover letter file upload
    if (isset($_FILES['cover_letter_file']) && $_FILES['cover_letter_file']['error'] === UPLOAD_ERR_OK) {
        $fileExtension = strtolower(pathinfo($_FILES['cover_letter_file']['name'], PATHINFO_EXTENSION));
        if (in_array($fileExtension, ['pdf', 'doc', 'docx', 'txt'])) {
            if (!file_exists('../uploads/cover_letters')) {
                mkdir('../uploads/cover_letters', 0777, true);
            }
            $newFileName = uniqid() . '.' . $fileExtension;
            $uploadPath = '../uploads/cover_letters/' . $newFileName;
            if (move_uploaded_file($_FILES['cover_letter_file']['tmp_name'], $uploadPath)) {
                $cover_letter_file_path = 'uploads/cover_letters/' . $newFileName;
                $cover_letter = '[FILE] ' . $cover_letter_file_path;
            }
        }
    }
    // Get student profile ID and resume
    $stmt = $conn->prepare("SELECT id, resume_path FROM student_profiles WHERE user_id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $student_profile = $stmt->fetch();
    $student_id = $student_profile['id'];
    $resume_path = $student_profile['resume_path'];
    echo "<pre>student_id: $student_id\nresume_path: $resume_path</pre>";

    if (empty($resume_path)) {
        echo "NO RESUME";
        exit;
    }

    try {
        $stmt = $conn->prepare("INSERT INTO applications (job_id, student_id, cover_letter) VALUES (?, ?, ?)");
        if ($stmt->execute([$_POST['job_id'], $student_id, $cover_letter])) {
            // echo "Application inserted!";
            redirectWith($_SERVER['REQUEST_URI'], 'Application submitted successfully!', 'success');
        } else {
            echo "Insert failed: ";
            print_r($stmt->errorInfo());
        }
    } catch (PDOException $e) {
        echo "PDOException: " . $e->getMessage();
    }
    exit;
}

include '../includes/header.php';
?>

<div class="container">
    <nav aria-label="breadcrumb" class="mt-3">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="<?php echo BASE_URL; ?>/job.php">Jobs</a></li>
            <li class="breadcrumb-item active" aria-current="page"><?php echo htmlspecialchars($job['title']); ?></li>
        </ol>
    </nav>

    <div class="row">
        <!-- Job Details -->
        <div class="col-lg-8">
            <div class="card mb-4">
                <div class="card-body">
                    <div class="d-flex align-items-center mb-4">
                        <img src="<?php echo $job['logo_path'] ? BASE_URL . '/' . $job['logo_path'] : BASE_URL . '/assets/images/DefaultCompanyLogo.jpg'; ?>" 
                             alt="<?php echo htmlspecialchars($job['company_name']); ?>" 
                             class="company-logo me-3">
                        <div>
                            <h1 class="h3 mb-1"><?php echo htmlspecialchars($job['title']); ?></h1>
                            <h2 class="h6 text-muted mb-0"><?php echo htmlspecialchars($job['company_name']); ?></h2>
                        </div>
                    </div>

                    <div class="job-meta mb-4">
                        <span class="badge bg-primary me-2"><?php echo htmlspecialchars($job['job_type']); ?></span>
                        <span class="badge bg-secondary me-2"><?php echo htmlspecialchars($job['location']); ?></span>
                        <?php if ($job['salary_range']): ?>
                            <span class="badge bg-info me-2"><?php echo htmlspecialchars($job['salary_range']); ?></span>
                        <?php endif; ?>
                        <span class="badge bg-light text-dark">
                            Posted <?php echo date('F j, Y', strtotime($job['created_at'])); ?>
                        </span>
                    </div>

                    <h3 class="h5 mb-3">Job Description</h3>
                    <div class="mb-4">
                        <?php echo nl2br(htmlspecialchars($job['description'])); ?>
                    </div>

                    <h3 class="h5 mb-3">Requirements</h3>
                    <div class="mb-4">
                        <?php echo nl2br(htmlspecialchars($job['requirements'])); ?>
                    </div>

                    <?php if (isLoggedIn() && hasRole('student')): ?>
                        <?php if ($has_applied): ?>
                            <div class="alert alert-info">
                                <i class="fas fa-check-circle me-2"></i>
                                You have already applied for this position.
                            </div>
                        <?php else: ?>
                            <button type="button" class="btn btn-primary btn-lg" data-bs-toggle="modal" data-bs-target="#applyModal">
                                Apply Now
                            </button>
                        <?php endif; ?>
                    <?php elseif (!isLoggedIn()): ?>
                        <div class="alert alert-warning">
                            <i class="fas fa-info-circle me-2"></i>
                            Please <a href="<?php echo BASE_URL; ?>/login.php">login</a> as a student to apply for this position.
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Company Info -->
        <div class="col-lg-4">
            <div class="card mb-4">
                <div class="card-body">
                    <h3 class="h5 mb-3">About the Company</h3>
                    <p><?php echo nl2br(htmlspecialchars($job['company_about'])); ?></p>
                    
                    <?php if ($job['website']): ?>
                        <p class="mb-0">
                            <i class="fas fa-globe me-2"></i>
                            <a href="<?php echo htmlspecialchars($job['website']); ?>" target="_blank" rel="noopener noreferrer">
                                Visit Website
                            </a>
                        </p>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Share Job -->
            <div class="card">
                <div class="card-body">
                    <h3 class="h5 mb-3">Share This Job</h3>
                    <div class="d-flex gap-2">
                        <a href="https://www.linkedin.com/sharing/share-offsite/?url=<?php echo urlencode(BASE_URL . $_SERVER['REQUEST_URI']); ?>" 
                           class="btn btn-outline-primary" target="_blank" rel="noopener noreferrer">
                            <i class="fab fa-linkedin"></i>
                        </a>
                        <a href="https://twitter.com/intent/tweet?url=<?php echo urlencode(BASE_URL . $_SERVER['REQUEST_URI']); ?>&text=<?php echo urlencode($job['title'] . ' at ' . $job['company_name']); ?>" 
                           class="btn btn-outline-info" target="_blank" rel="noopener noreferrer">
                            <i class="fab fa-twitter"></i>
                        </a>
                        <a href="mailto:?subject=<?php echo urlencode('Job Opportunity: ' . $job['title']); ?>&body=<?php echo urlencode('Check out this job: ' . BASE_URL . $_SERVER['REQUEST_URI']); ?>" 
                           class="btn btn-outline-secondary">
                            <i class="fas fa-envelope"></i>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Apply Modal -->
<?php if (isLoggedIn() && hasRole('student') && !$has_applied): ?>
<div class="modal fade" id="applyModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form method="POST" action="<?php echo htmlspecialchars($_SERVER['REQUEST_URI']); ?>" id="job-application-form" enctype="multipart/form-data">
                <input type="hidden" name="job_id" value="<?php echo $job['id']; ?>">
                <div class="modal-header">
                    <h5 class="modal-title">Apply for <?php echo htmlspecialchars($job['title']); ?></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <?php if (isset($error)): ?>
                        <div class="alert alert-danger"><?php echo $error; ?></div>
                    <?php endif; ?>
                    <?php
                    // Check if student has a resume
                    $has_resume = false;
                    if (isLoggedIn() && hasRole('student')) {
                        $stmt = $conn->prepare("SELECT resume_path FROM student_profiles WHERE user_id = ?");
                        $stmt->execute([$_SESSION['user_id']]);
                        $profile = $stmt->fetch();
                        $has_resume = !empty($profile['resume_path']);
                    }
                    ?>
                    <?php if (!$has_resume): ?>
                        <div class="alert alert-warning" id="resume-warning">
                            You must <a href="<?php echo BASE_URL; ?>/student/edit-profile.php" class="alert-link" target="_blank">upload a resume</a> in your profile before applying.<br>
                            <small>After uploading your resume, please refresh this page to apply.</small>
                        </div>
                    <?php endif; ?>
                    <div class="mb-3">
                        <label for="cover_letter" class="form-label">Cover Letter (optional)</label>
                        <textarea class="form-control" id="cover_letter" name="cover_letter" rows="6"
                                  placeholder="Introduce yourself and explain why you're a great fit for this position..."></textarea>
                    </div>
                    <div class="mb-3">
                        <label for="cover_letter_file" class="form-label">Or Upload Cover Letter File (PDF, DOC, DOCX, TXT)</label>
                        <input type="file" class="form-control" id="cover_letter_file" name="cover_letter_file" accept=".pdf,.doc,.docx,.txt">
                        <small class="text-muted">If you upload a file, it will be used as your cover letter instead of the text above.</small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary" <?php if (!$has_resume) echo 'disabled'; ?>>Submit Application</button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php endif; ?>

<?php include '../includes/footer.php'; ?> 