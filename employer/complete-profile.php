<?php
require_once '../config/config.php';
require_once '../config/database.php';

// Check if user is logged in and is an employer
if (!isLoggedIn() || !hasRole('employer')) {
    redirectWith(BASE_URL . '/login.php', 'Please login as an employer', 'error');
}

$db = new Database();
$conn = $db->getConnection();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Sanitize input
    $companyName = sanitizeInput($_POST['company_name']);
    $industry = sanitizeInput($_POST['industry']);
    $companySize = sanitizeInput($_POST['company_size']);
    $website = sanitizeInput($_POST['website']);
    $location = sanitizeInput($_POST['location']);
    $about = sanitizeInput($_POST['about']);

    $errors = [];

    // Validate input
    if (empty($companyName)) {
        $errors[] = "Company name is required";
    }
    if (empty($industry)) {
        $errors[] = "Industry is required";
    }

    // Handle logo upload
    $logoPath = '';
    if (isset($_FILES['logo']) && $_FILES['logo']['error'] === UPLOAD_ERR_OK) {
        $allowedTypes = ['image/jpeg', 'image/png'];
        $maxSize = 5 * 1024 * 1024; // 5MB

        if (!in_array($_FILES['logo']['type'], $allowedTypes)) {
            $errors[] = "Logo must be a JPG or PNG file";
        } elseif ($_FILES['logo']['size'] > $maxSize) {
            $errors[] = "Logo file size must be less than 5MB";
        } else {
            $fileExtension = strtolower(pathinfo($_FILES['logo']['name'], PATHINFO_EXTENSION));
            $logoPath = 'uploads/logos/' . uniqid('company_') . '.' . $fileExtension;
            
            // Create uploads directory if it doesn't exist
            if (!file_exists('../uploads/logos')) {
                mkdir('../uploads/logos', 0777, true);
            }
            
            if (!move_uploaded_file($_FILES['logo']['tmp_name'], '../' . $logoPath)) {
                $errors[] = "Failed to upload logo";
            }
        }
    }

    if (empty($errors)) {
        try {
            // Check if profile already exists
            $stmt = $conn->prepare("SELECT id FROM employer_profiles WHERE user_id = :user_id");
            $stmt->execute(['user_id' => $_SESSION['user_id']]);
            $existing = $stmt->fetch();
            if ($existing) {
                // Update existing profile
                $query = "UPDATE employer_profiles SET company_name = :company_name, industry = :industry, company_size = :company_size, website = :website, location = :location, about = :about, logo_path = :logo_path WHERE user_id = :user_id";
            } else {
                // Insert employer profile
                $query = "INSERT INTO employer_profiles (user_id, company_name, industry, company_size, website, location, about, logo_path) VALUES (:user_id, :company_name, :industry, :company_size, :website, :location, :about, :logo_path)";
            }
            $stmt = $conn->prepare($query);
            $stmt->execute([
                'user_id' => $_SESSION['user_id'],
                'company_name' => $companyName,
                'industry' => $industry,
                'company_size' => $companySize,
                'website' => $website,
                'location' => $location,
                'about' => $about,
                'logo_path' => $logoPath
            ]);
            redirectWith(BASE_URL . '/employer/dashboard.php', 'Profile completed successfully!', 'success');
        } catch (PDOException $e) {
            $errors[] = 'Error creating profile. Please try again.';
        }
    }
}

include '../includes/header.php';
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h3 class="mb-0">Complete Company Profile</h3>
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

                    <form action="" method="POST" enctype="multipart/form-data">
                        <div class="mb-3">
                            <label for="company_name" class="form-label">Company Name*</label>
                            <input type="text" class="form-control" id="company_name" name="company_name" required>
                        </div>

                        <div class="mb-3">
                            <label for="industry" class="form-label">Industry*</label>
                            <input type="text" class="form-control" id="industry" name="industry" 
                                   placeholder="e.g., Technology, Healthcare, Education" required>
                        </div>

                        <div class="mb-3">
                            <label for="company_size" class="form-label">Company Size</label>
                            <select class="form-select" id="company_size" name="company_size">
                                <option value="">Select Size</option>
                                <option value="1-10">1-10 employees</option>
                                <option value="11-50">11-50 employees</option>
                                <option value="51-200">51-200 employees</option>
                                <option value="201-500">201-500 employees</option>
                                <option value="501+">501+ employees</option>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label for="website" class="form-label">Company Website</label>
                            <input type="url" class="form-control" id="website" name="website" 
                                   placeholder="https://example.com">
                        </div>

                        <div class="mb-3">
                            <label for="location" class="form-label">Location</label>
                            <input type="text" class="form-control" id="location" name="location" 
                                   placeholder="City, Country">
                        </div>

                        <div class="mb-3">
                            <label for="logo" class="form-label">Company Logo</label>
                            <input type="file" class="form-control" id="logo" name="logo" 
                                   accept="image/jpeg,image/png">
                            <div class="form-text">Maximum file size: 5MB. Accepted formats: JPG, PNG</div>
                        </div>

                        <div class="mb-3">
                            <label for="about" class="form-label">About Company*</label>
                            <textarea class="form-control" id="about" name="about" rows="4" 
                                      placeholder="Tell us about your company, culture, and what makes you unique..." required></textarea>
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