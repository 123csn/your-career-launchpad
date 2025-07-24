<?php
require_once '../config/config.php';
require_once '../config/database.php';

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Check if user is logged in and is a student
if (!isLoggedIn() || !hasRole('student')) {
    redirectWith(BASE_URL . '/login.php', 'Please login as a student', 'error');
}

// Helper function to get profile picture URL
function getProfilePictureUrl($profilePicturePath = null) {
    if (!empty($profilePicturePath) && file_exists('../' . $profilePicturePath)) {
        return BASE_URL . '/' . $profilePicturePath;
    }
    return BASE_URL . '/assets/images/default-avatar.png';
}

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

$db = new Database();
$conn = $db->getConnection();

// Ensure upload directories exist
$uploadDirs = ['../uploads/profile_pictures', '../uploads/resumes'];
foreach ($uploadDirs as $dir) {
    if (!file_exists($dir)) {
        mkdir($dir, 0777, true);
    }
}

// Get current profile data with user information
try {
    $query = "SELECT s.*, u.email, s.id as profile_id 
              FROM users u 
              LEFT JOIN student_profiles s ON u.id = s.user_id 
              WHERE u.id = :user_id";
    $stmt = $conn->prepare($query);
    $stmt->execute(['user_id' => $_SESSION['user_id']]);
    $profile = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$profile) {
        // If no profile exists, get at least the user data
        $query = "SELECT id, email FROM users WHERE id = :user_id";
        $stmt = $conn->prepare($query);
        $stmt->execute(['user_id' => $_SESSION['user_id']]);
        $profile = $stmt->fetch(PDO::FETCH_ASSOC);
    }
} catch (PDOException $e) {
    $error = 'Error fetching profile data: ' . $e->getMessage();
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Sanitize input
    $firstName = sanitizeInput($_POST['first_name']);
    $lastName = sanitizeInput($_POST['last_name']);
    $phone = sanitizeInput($_POST['phone']);
    $education = !empty($_POST['education_level']) ? sanitizeInput($_POST['education_level']) : $profile['education_level'];
    $skills = sanitizeInput($_POST['skills']);
    $bio = sanitizeInput($_POST['bio']);

    // Validate education level
    $validEducationLevels = ['high_school', 'diploma', 'bachelors_degree', 'masters_degree', 'phd', 'other'];
    if (!in_array($education, $validEducationLevels)) {
        $education = $profile['education_level']; // Keep the original value if invalid
    }

    // Handle profile picture
    $profilePicturePath = $profile['profile_picture'] ?? '';
    
    // Check if user wants to remove the profile picture
    if (isset($_POST['remove_profile_picture']) && $_POST['remove_profile_picture'] === 'yes') {
        if (!empty($profilePicturePath) && file_exists('../' . $profilePicturePath)) {
            unlink('../' . $profilePicturePath);
        }
        $profilePicturePath = '';
    }
    // Handle new profile picture upload
    elseif (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] === UPLOAD_ERR_OK) {
        $fileExtension = strtolower(pathinfo($_FILES['profile_picture']['name'], PATHINFO_EXTENSION));
        if (in_array($fileExtension, ['jpg', 'jpeg', 'png'])) {
            $newFileName = uniqid() . '.' . $fileExtension;
            $uploadPath = '../uploads/profile_pictures/' . $newFileName;
            
            if (move_uploaded_file($_FILES['profile_picture']['tmp_name'], $uploadPath)) {
                // Delete old profile picture if it exists
                if (!empty($profilePicturePath) && file_exists('../' . $profilePicturePath)) {
                    unlink('../' . $profilePicturePath);
                }
                $profilePicturePath = 'uploads/profile_pictures/' . $newFileName;
            }
        }
    }

    // Handle resume upload
    $resumePath = $profile['resume_path'] ?? '';
    if (isset($_FILES['resume']) && $_FILES['resume']['error'] === UPLOAD_ERR_OK) {
        $fileExtension = strtolower(pathinfo($_FILES['resume']['name'], PATHINFO_EXTENSION));
        if (in_array($fileExtension, ['pdf', 'doc', 'docx', 'jpg', 'jpeg', 'png'])) {
            $newFileName = uniqid() . '.' . $fileExtension;
            $uploadPath = '../uploads/resumes/' . $newFileName;
            
            if (move_uploaded_file($_FILES['resume']['tmp_name'], $uploadPath)) {
                // Delete old resume if it exists
                if (!empty($resumePath) && file_exists('../' . $resumePath)) {
                    unlink('../' . $resumePath);
                }
                $resumePath = 'uploads/resumes/' . $newFileName;
            }
        }
    }

    try {
        // Check if profile exists using profile_id
        if (isset($profile['profile_id'])) {
            // Update existing profile
            $query = "UPDATE student_profiles 
                     SET first_name = :first_name,
                         last_name = :last_name,
                         phone = :phone,
                         education_level = :education_level,
                         skills = :skills,
                         bio = :bio,
                         profile_picture = :profile_picture,
                         resume_path = :resume_path
                     WHERE user_id = :user_id";
        } else {
            // Insert new profile
            $query = "INSERT INTO student_profiles 
                     (user_id, first_name, last_name, phone, education_level, skills, bio, profile_picture, resume_path)
                     VALUES 
                     (:user_id, :first_name, :last_name, :phone, :education_level, :skills, :bio, :profile_picture, :resume_path)";
        }

        $stmt = $conn->prepare($query);
        $params = [
            'user_id' => $_SESSION['user_id'],
            'first_name' => $firstName,
            'last_name' => $lastName,
            'phone' => $phone,
            'education_level' => $education,
            'skills' => $skills,
            'bio' => $bio,
            'profile_picture' => $profilePicturePath,
            'resume_path' => $resumePath
        ];
        
        echo '<pre>'; print_r($params); echo '</pre>';
        if ($stmt->execute($params)) {
            redirectWith(BASE_URL . '/student/dashboard.php', 'Profile updated successfully!', 'success');
        } else {
            $error = 'Database error: ' . implode(', ', $stmt->errorInfo());
            echo $error;
        }
    } catch (PDOException $e) {
        $error = 'Database error: ' . $e->getMessage();
    }
}

$currentEducation = isset($_POST['education_level']) ? $_POST['education_level'] : ($profile['education_level'] ?? '');

include '../includes/header.php';
?>

<div class="container py-4">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h4 class="mb-0">Edit Profile</h4>
                </div>
                <div class="card-body">
                    <?php if (isset($error)): ?>
                        <div class="alert alert-danger"><?php echo $error; ?></div>
                    <?php endif; ?>

                    <form action="" method="POST" enctype="multipart/form-data">
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="first_name" class="form-label">First Name*</label>
                                <input type="text" class="form-control" id="first_name" name="first_name" 
                                       value="<?php echo htmlspecialchars($profile['first_name'] ?? ''); ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label for="last_name" class="form-label">Last Name*</label>
                                <input type="text" class="form-control" id="last_name" name="last_name" 
                                       value="<?php echo htmlspecialchars($profile['last_name'] ?? ''); ?>" required>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="email" class="form-label">Email</label>
                            <input type="email" class="form-control" id="email" value="<?php echo htmlspecialchars($profile['email']); ?>" disabled>
                            <small class="text-muted">Email cannot be changed</small>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Profile Picture</label>
                            <div class="mb-2">
                                <img src="<?php echo getProfilePictureUrl($profile['profile_picture'] ?? null); ?>" 
                                     alt="Profile Picture" 
                                     class="rounded-circle" 
                                     style="width: 100px; height: 100px; object-fit: cover;">
                            </div>
                            <div class="mb-2">
                                <input type="file" class="form-control" id="profile_picture" name="profile_picture" 
                                       accept=".jpg,.jpeg,.png">
                                <small class="text-muted">Upload a new picture or leave empty to keep current</small>
                            </div>
                            <?php if (!empty($profile['profile_picture'])): ?>
                            <div class="form-check">
                                <input type="checkbox" class="form-check-input" id="remove_profile_picture" name="remove_profile_picture" value="yes">
                                <label class="form-check-label" for="remove_profile_picture">Remove profile picture and use default avatar</label>
                            </div>
                            <?php endif; ?>
                        </div>

                        <div class="mb-3">
                            <label for="phone" class="form-label">Phone Number</label>
                            <input type="tel" class="form-control" id="phone" name="phone" 
                                   value="<?php echo htmlspecialchars($profile['phone'] ?? ''); ?>">
                        </div>

                        <div class="mb-3">
                            <label for="education_level" class="form-label">Education Level*</label>
                            <select class="form-select" id="education_level" name="education_level" required>
                                <option value="">Select Education Level</option>
                                <option value="high_school" <?php if($currentEducation=="high_school") echo "selected"; ?>>High School</option>
                                <option value="diploma" <?php if($currentEducation=="diploma") echo "selected"; ?>>Diploma</option>
                                <option value="bachelors_degree" <?php if($currentEducation=="bachelors_degree") echo "selected"; ?>>Bachelor's Degree</option>
                                <option value="masters_degree" <?php if($currentEducation=="masters_degree") echo "selected"; ?>>Master's Degree</option>
                                <option value="phd" <?php if($currentEducation=="phd") echo "selected"; ?>>PhD</option>
                                <option value="other" <?php if($currentEducation=="other") echo "selected"; ?>>Other</option>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label for="skills" class="form-label">Skills (comma separated)*</label>
                            <input type="text" class="form-control" id="skills" name="skills" 
                                   value="<?php echo htmlspecialchars($profile['skills'] ?? ''); ?>"
                                   placeholder="e.g., PHP, JavaScript, Project Management" required>
                        </div>

                        <div class="mb-3">
                            <label for="resume" class="form-label">Resume (PDF, DOC, DOCX, JPG, JPEG, PNG)</label>
                            <?php if (!empty($profile['resume_path'])): ?>
                                <div class="mb-2 d-flex align-items-center">
                                    <a href="<?php echo BASE_URL . '/' . $profile['resume_path']; ?>" 
                                       class="btn btn-sm btn-outline-primary me-2" target="_blank">
                                        View Current Resume
                                    </a>
                                    <span class="text-muted small">Upload new file to replace current resume</span>
                                </div>
                            <?php endif; ?>
                            <input type="file" class="form-control" id="resume" name="resume" 
                                   accept=".pdf,.doc,.docx,.jpg,.jpeg,.png">
                        </div>

                        <div class="mb-3">
                            <label for="bio" class="form-label">Bio*</label>
                            <textarea class="form-control" id="bio" name="bio" rows="4" 
                                    placeholder="Tell us about yourself..." required><?php 
                                echo htmlspecialchars($profile['bio'] ?? ''); 
                            ?></textarea>
                        </div>

                        <div class="d-flex justify-content-between">
                            <a href="dashboard.php" class="btn btn-secondary">Cancel</a>
                            <button type="submit" class="btn btn-primary">Save Changes</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?> 