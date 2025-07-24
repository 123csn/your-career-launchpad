<?php
require_once '../config/config.php';
require_once '../config/database.php';

if (!isLoggedIn() || !hasRole('employer')) {
    redirectWith(BASE_URL . '/login.php', 'Please login as an employer to access this page.', 'warning');
}

$db = new Database();
$conn = $db->getConnection();

// Get job ID
$job_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if (!$job_id) {
    redirectWith('dashboard.php', 'Invalid job ID.', 'danger');
}

// Fetch job
$stmt = $conn->prepare('SELECT * FROM job WHERE id = ?');
$stmt->execute([$job_id]);
$job = $stmt->fetch();
if (!$job) {
    redirectWith('dashboard.php', 'Job not found.', 'danger');
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = sanitizeInput($_POST['title']);
    $description = sanitizeInput($_POST['description']);
    $requirements = sanitizeInput($_POST['requirements']);
    $location = sanitizeInput($_POST['location']);
    $job_type = sanitizeInput($_POST['job_type']);
    $salary_range = sanitizeInput($_POST['salary_range']);
    $deadline = sanitizeInput($_POST['deadline']);
    $status = sanitizeInput($_POST['status']);

    if (empty($title)) $errors[] = 'Job title is required';
    if (empty($description)) $errors[] = 'Job description is required';
    if (empty($requirements)) $errors[] = 'Job requirements are required';
    if (empty($location)) $errors[] = 'Location is required';
    if (!in_array($job_type, ['full-time', 'part-time', 'internship', 'contract'])) $errors[] = 'Invalid job type';
    if (!in_array($status, ['active', 'closed'])) $errors[] = 'Invalid status';

    if (empty($errors)) {
        try {
            $stmt = $conn->prepare('UPDATE job SET title=?, description=?, requirements=?, location=?, job_type=?, salary_range=?, deadline=?, status=? WHERE id=?');
            $stmt->execute([$title, $description, $requirements, $location, $job_type, $salary_range, $deadline ?: null, $status, $job_id]);
            redirectWith('dashboard.php', 'Job updated successfully!', 'success');
        } catch (PDOException $e) {
            $errors[] = 'An error occurred while updating the job.';
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
                    <h4 class="mb-0">Edit Job</h4>
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
                    <form method="POST">
                        <div class="mb-3">
                            <label for="title" class="form-label">Job Title <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="title" name="title" value="<?php echo htmlspecialchars(isset($_POST['title']) ? $_POST['title'] : $job['title']); ?>" required>
                        </div>
                        <div class="mb-3">
                            <label for="description" class="form-label">Job Description <span class="text-danger">*</span></label>
                            <textarea class="form-control" id="description" name="description" rows="6" required><?php echo htmlspecialchars(isset($_POST['description']) ? $_POST['description'] : $job['description']); ?></textarea>
                        </div>
                        <div class="mb-3">
                            <label for="requirements" class="form-label">Requirements <span class="text-danger">*</span></label>
                            <textarea class="form-control" id="requirements" name="requirements" rows="4" required><?php echo htmlspecialchars(isset($_POST['requirements']) ? $_POST['requirements'] : $job['requirements']); ?></textarea>
                        </div>
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="location" class="form-label">Location <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="location" name="location" value="<?php echo htmlspecialchars(isset($_POST['location']) ? $_POST['location'] : $job['location']); ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label for="job_type" class="form-label">Job Type <span class="text-danger">*</span></label>
                                <select class="form-select" id="job_type" name="job_type" required>
                                    <option value="">Select Type</option>
                                    <option value="full-time" <?php echo ((isset($_POST['job_type']) ? $_POST['job_type'] : $job['job_type']) === 'full-time') ? 'selected' : ''; ?>>Full Time</option>
                                    <option value="part-time" <?php echo ((isset($_POST['job_type']) ? $_POST['job_type'] : $job['job_type']) === 'part-time') ? 'selected' : ''; ?>>Part Time</option>
                                    <option value="internship" <?php echo ((isset($_POST['job_type']) ? $_POST['job_type'] : $job['job_type']) === 'internship') ? 'selected' : ''; ?>>Internship</option>
                                    <option value="contract" <?php echo ((isset($_POST['job_type']) ? $_POST['job_type'] : $job['job_type']) === 'contract') ? 'selected' : ''; ?>>Contract</option>
                                </select>
                            </div>
                        </div>
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="salary_range" class="form-label">Salary Range</label>
                                <input type="text" class="form-control" id="salary_range" name="salary_range" value="<?php echo htmlspecialchars(isset($_POST['salary_range']) ? $_POST['salary_range'] : $job['salary_range']); ?>">
                            </div>
                            <div class="col-md-6">
                                <label for="deadline" class="form-label">Application Deadline</label>
                                <input type="date" class="form-control" id="deadline" name="deadline" value="<?php echo htmlspecialchars(isset($_POST['deadline']) ? $_POST['deadline'] : $job['deadline']); ?>">
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="status" class="form-label">Status <span class="text-danger">*</span></label>
                            <select class="form-select" id="status" name="status" required>
                                <option value="active" <?php echo ((isset($_POST['status']) ? $_POST['status'] : $job['status']) === 'active') ? 'selected' : ''; ?>>Active</option>
                                <option value="closed" <?php echo ((isset($_POST['status']) ? $_POST['status'] : $job['status']) === 'closed') ? 'selected' : ''; ?>>Closed</option>
                            </select>
                        </div>
                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-primary">Save Changes</button>
                            <a href="dashboard.php" class="btn btn-light">Cancel</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
<?php include '../includes/footer.php'; ?> 