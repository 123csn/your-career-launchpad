<?php
require_once '../config/config.php';
require_once '../config/database.php';

// Check if user is logged in and is a student
if (!isLoggedIn() || !hasRole('student')) {
    redirectWith(BASE_URL . '/login.php', 'Please login as a student', 'error');
}

$db = new Database();
$conn = $db->getConnection();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Sanitize input
    $firstName = sanitizeInput($_POST['first_name']);
    $lastName = sanitizeInput($_POST['last_name']);
    $phone = sanitizeInput($_POST['phone']);
    $education = sanitizeInput($_POST['education_level']);
    $skills = sanitizeInput($_POST['skills']);
    $bio = sanitizeInput($_POST['bio']);

    // Handle resume upload
    $resumePath = '';
    if (isset($_FILES['resume']) && $_FILES['resume']['error'] === UPLOAD_ERR_OK) {
        $fileExtension = strtolower(pathinfo($_FILES['resume']['name'], PATHINFO_EXTENSION));
        if (in_array($fileExtension, ALLOWED_RESUME_TYPES)) {
            $resumePath = 'uploads/resumes/' . uniqid() . '.' . $fileExtension;
            move_uploaded_file($_FILES['resume']['tmp_name'], '../' . $resumePath);
        }
    }

    try {
        // Check if profile already exists
        $stmt = $conn->prepare("SELECT id FROM student_profiles WHERE user_id = :user_id");
        $stmt->execute(['user_id' => $_SESSION['user_id']]);
        $existing = $stmt->fetch();
        if ($existing) {
            // Update existing profile
            $query = "UPDATE student_profiles SET first_name = :first_name, last_name = :last_name, phone = :phone, education_level = :education_level, skills = :skills, resume_path = :resume_path, bio = :bio WHERE user_id = :user_id";
        } else {
            // Insert new profile
            $query = "INSERT INTO student_profiles (user_id, first_name, last_name, phone, education_level, skills, resume_path, bio) VALUES (:user_id, :first_name, :last_name, :phone, :education_level, :skills, :resume_path, :bio)";
        }
        $stmt = $conn->prepare($query);
        $stmt->execute([
            'user_id' => $_SESSION['user_id'],
            'first_name' => $firstName,
            'last_name' => $lastName,
            'phone' => $phone,
            'education_level' => $education,
            'skills' => $skills,
            'resume_path' => $resumePath,
            'bio' => $bio
        ]);
        redirectWith(BASE_URL . '/student/dashboard.php', 'Profile completed successfully!', 'success');
    } catch (PDOException $e) {
        $error = 'Error creating profile. Please try again.';
    }
}

include '../includes/header.php';
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h3 class="mb-0">Complete Your Profile</h3>
                </div>
                <div class="card-body">
                    <?php if (isset($error)): ?>
                        <div class="alert alert-danger"><?php echo $error; ?></div>
                    <?php endif; ?>

                    <form action="" method="POST" enctype="multipart/form-data">
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="first_name" class="form-label">First Name*</label>
                                <input type="text" class="form-control" id="first_name" name="first_name" required>
                            </div>
                            <div class="col-md-6">
                                <label for="last_name" class="form-label">Last Name*</label>
                                <input type="text" class="form-control" id="last_name" name="last_name" required>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="phone" class="form-label">Phone Number</label>
                            <input type="tel" class="form-control" id="phone" name="phone">
                        </div>

                        <div class="mb-3">
                            <label for="education_level" class="form-label">Education Level*</label>
                            <select class="form-select" id="education_level" name="education_level" required>
                                <option value="">Select Education Level</option>
                                <option value="high_school">High School</option>
                                <option value="diploma">Diploma</option>
                                <option value="bachelors_degree">Bachelor's Degree</option>
                                <option value="masters_degree">Master's Degree</option>
                                <option value="phd">PhD</option>
                                <option value="other">Other</option>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label for="skills" class="form-label">Skills (comma separated)*</label>
                            <input type="text" class="form-control" id="skills" name="skills" 
                                   placeholder="e.g., PHP, JavaScript, Project Management" required>
                        </div>

                        <div class="mb-3">
                            <label for="resume" class="form-label">Resume (PDF, DOC, DOCX)</label>
                            <input type="file" class="form-control" id="resume" name="resume" 
                                   accept=".pdf,.doc,.docx">
                        </div>

                        <div class="mb-3">
                            <label for="bio" class="form-label">Bio*</label>
                            <textarea class="form-control" id="bio" name="bio" rows="4" 
                                      placeholder="Tell us about yourself..." required></textarea>
                        </div>

                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary">Complete Profile</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?> 