<?php
require_once '../config/config.php';
require_once '../config/database.php';

// Check if user is logged in and is an employer
if (!isLoggedIn() || !hasRole('employer')) {
    redirectWith(BASE_URL . '/login.php', 'Please login as an employer to access this page.', 'warning');
}

$db = new Database();
$conn = $db->getConnection();

// Get employer profile
$stmt = $conn->prepare("SELECT * FROM employer_profiles WHERE user_id = ?");
$stmt->execute([$_SESSION['user_id']]);
$profile = $stmt->fetch();

if (!$profile) {
    redirectWith(BASE_URL . '/employer/complete-profile.php', 'Please complete your company profile first.', 'warning');
}

$errors = [];

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate input
    $title = sanitizeInput($_POST['title']);
    $description = sanitizeInput($_POST['description']);
    $requirements = sanitizeInput($_POST['requirements']);
    $location = sanitizeInput($_POST['location']);
    $job_type = sanitizeInput($_POST['job_type']);
    $salary_range = sanitizeInput($_POST['salary_range']);
    $deadline = sanitizeInput($_POST['deadline']);
    
    if (empty($title)) {
        $errors[] = "Job title is required";
    }
    if (empty($description)) {
        $errors[] = "Job description is required";
    }
    if (empty($requirements)) {
        $errors[] = "Job requirements are required";
    }
    if (empty($location)) {
        $errors[] = "Location is required";
    }
    if (!in_array($job_type, ['full-time', 'part-time', 'internship', 'contract'])) {
        $errors[] = "Invalid job type";
    }
    if (!empty($deadline) && strtotime($deadline) < strtotime('today')) {
        $errors[] = "Deadline cannot be in the past";
    }
    
    if (empty($errors)) {
        try {
            $stmt = $conn->prepare("
                INSERT INTO job (
                    employer_id, title, description, requirements, location, 
                    job_type, salary_range, deadline, status
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'active')
            ");
            
            $stmt->execute([
                $profile['id'],
                $title,
                $description,
                $requirements,
                $location,
                $job_type,
                $salary_range,
                $deadline ?: null
            ]);
            
            redirectWith('dashboard.php', 'Job posted successfully!', 'success');
        } catch (PDOException $e) {
            $errors[] = "An error occurred while posting the job.";
            $errors[] = $e->getMessage(); // Debug: show actual error
        }
    }
}

include '../includes/header.php';
?>

<div class="container py-4">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header bg-white">
                    <h4 class="mb-0">Post a New Job</h4>
                </div>
                <div class="card-body">
                    <?php if (!empty($errors)): ?>
                        <div class="alert alert-danger">
                            <ul class="mb-0">
                                <?php foreach ($errors as $error): ?>
                                    <li><?php echo $error; ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>

                    <form method="POST" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>">
                        <div class="mb-3">
                            <label for="title" class="form-label">Job Title <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="title" name="title" 
                                   value="<?php echo isset($_POST['title']) ? htmlspecialchars($_POST['title']) : ''; ?>" 
                                   required>
                        </div>

                        <div class="mb-3">
                            <label for="description" class="form-label">Job Description <span class="text-danger">*</span></label>
                            <textarea class="form-control" id="description" name="description" rows="6" required
                                      placeholder="Describe the role, responsibilities, and what a typical day looks like..."
                            ><?php echo isset($_POST['description']) ? htmlspecialchars($_POST['description']) : ''; ?></textarea>
                        </div>

                        <div class="mb-3">
                            <label for="requirements" class="form-label">Requirements <span class="text-danger">*</span></label>
                            <textarea class="form-control" id="requirements" name="requirements" rows="4" required
                                      placeholder="List the required skills, experience, education, certifications..."
                            ><?php echo isset($_POST['requirements']) ? htmlspecialchars($_POST['requirements']) : ''; ?></textarea>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="location" class="form-label">Location <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="location" name="location" 
                                       value="<?php echo isset($_POST['location']) ? htmlspecialchars($_POST['location']) : ''; ?>" 
                                       required>
                            </div>
                            <div class="col-md-6">
                                <label for="job_type" class="form-label">Job Type <span class="text-danger">*</span></label>
                                <select class="form-select" id="job_type" name="job_type" required>
                                    <option value="">Select Type</option>
                                    <option value="full-time" <?php echo (isset($_POST['job_type']) && $_POST['job_type'] === 'full-time') ? 'selected' : ''; ?>>Full Time</option>
                                    <option value="part-time" <?php echo (isset($_POST['job_type']) && $_POST['job_type'] === 'part-time') ? 'selected' : ''; ?>>Part Time</option>
                                    <option value="internship" <?php echo (isset($_POST['job_type']) && $_POST['job_type'] === 'internship') ? 'selected' : ''; ?>>Internship</option>
                                    <option value="contract" <?php echo (isset($_POST['job_type']) && $_POST['job_type'] === 'contract') ? 'selected' : ''; ?>>Contract</option>
                                </select>
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="salary_range" class="form-label">Salary Range</label>
                                <input type="text" class="form-control" id="salary_range" name="salary_range" 
                                       value="<?php echo isset($_POST['salary_range']) ? htmlspecialchars($_POST['salary_range']) : ''; ?>" 
                                       placeholder="e.g. $50,000 - $70,000">
                            </div>
                            <div class="col-md-6">
                                <label for="deadline" class="form-label">Application Deadline</label>
                                <input type="date" class="form-control" id="deadline" name="deadline" 
                                       value="<?php echo isset($_POST['deadline']) ? htmlspecialchars($_POST['deadline']) : ''; ?>" 
                                       min="<?php echo date('Y-m-d'); ?>">
                            </div>
                        </div>

                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-primary btn-lg">Post Job</button>
                            <a href="dashboard.php" class="btn btn-light">Cancel</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?> 