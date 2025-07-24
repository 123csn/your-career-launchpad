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

$errors = [];

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate input
    $company_name = sanitizeInput($_POST['company_name']);
    $industry = sanitizeInput($_POST['industry']);
    $company_size = sanitizeInput($_POST['company_size']);
    $website = sanitizeInput($_POST['website']);
    $location = sanitizeInput($_POST['location']);
    $about = sanitizeInput($_POST['about']);
    $use_default_logo = isset($_POST['use_default_logo']);
    
    if (empty($company_name)) {
        $errors[] = "Company name is required";
    }
    if (empty($industry)) {
        $errors[] = "Industry is required";
    }
    if (!empty($website) && !filter_var($website, FILTER_VALIDATE_URL)) {
        $errors[] = "Invalid website URL";
    }
    
    // Handle logo
    $logo_path = $profile ? $profile['logo_path'] : null;
    
    if ($use_default_logo) {
        // If user wants to use default logo, remove the existing logo if any
        if ($logo_path && file_exists(ROOT_PATH . '/' . $logo_path)) {
            unlink(ROOT_PATH . '/' . $logo_path);
        }
        $logo_path = null;
    } elseif (isset($_FILES['logo']) && $_FILES['logo']['error'] === UPLOAD_ERR_OK) {
        $file = $_FILES['logo'];
        $allowed_types = ['image/jpeg', 'image/png'];
        $max_size = 5 * 1024 * 1024; // 5MB
        
        if (!in_array($file['type'], $allowed_types)) {
            $errors[] = "Logo must be a JPG or PNG file";
        } elseif ($file['size'] > $max_size) {
            $errors[] = "Logo file size must be less than 5MB";
        } else {
            $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
            $filename = uniqid('company_') . '.' . $extension;
            $upload_path = UPLOADS_PATH . '/logos/' . $filename;
            
            // Create uploads directory if it doesn't exist
            if (!file_exists(UPLOADS_PATH . '/logos')) {
                mkdir(UPLOADS_PATH . '/logos', 0777, true);
            }
            
            if (move_uploaded_file($file['tmp_name'], $upload_path)) {
                // Delete old logo if exists
                if ($logo_path && file_exists(ROOT_PATH . '/' . $logo_path)) {
                    unlink(ROOT_PATH . '/' . $logo_path);
                }
                $logo_path = 'uploads/logos/' . $filename;
            } else {
                $errors[] = "Failed to upload logo";
            }
        }
    }
    
    if (empty($errors)) {
        try {
            // Always check if profile exists before insert/update
            $stmt = $conn->prepare("SELECT id FROM employer_profiles WHERE user_id = ?");
            $stmt->execute([$_SESSION['user_id']]);
            $existing = $stmt->fetch();
            if ($existing) {
                // Update existing profile
                $stmt = $conn->prepare("
                    UPDATE employer_profiles 
                    SET company_name = ?, industry = ?, company_size = ?, 
                        website = ?, location = ?, about = ?, logo_path = ?
                    WHERE user_id = ?
                ");
                $stmt->execute([
                    $company_name, $industry, $company_size,
                    $website, $location, $about, $logo_path,
                    $_SESSION['user_id']
                ]);
            } else {
                // Insert new profile
                $stmt = $conn->prepare("
                    INSERT INTO employer_profiles 
                    (user_id, company_name, industry, company_size, website, location, about, logo_path)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?)
                ");
                $stmt->execute([
                    $_SESSION['user_id'], $company_name, $industry, $company_size,
                    $website, $location, $about, $logo_path
                ]);
            }
            // Fetch the latest profile after update/insert
            $stmt = $conn->prepare("SELECT * FROM employer_profiles WHERE user_id = ?");
            $stmt->execute([$_SESSION['user_id']]);
            $profile = $stmt->fetch();
            redirectWith('dashboard.php', 'Profile updated successfully!', 'success');
        } catch (PDOException $e) {
            $errors[] = "An error occurred while updating your profile.";
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
                    <h4 class="mb-0">Edit Company Profile</h4>
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

                    <form method="POST" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" enctype="multipart/form-data">
                        <div class="row mb-4">
                            <div class="col-md-3">
                                <div class="text-center">
                                    <img src="<?php echo $profile && $profile['logo_path'] ? BASE_URL . '/' . $profile['logo_path'] : BASE_URL . '/assets/images/DefaultCompanyLogo.jpg'; ?>" 
                                         alt="Company Logo" class="img-fluid rounded-circle mb-3" 
                                         style="width: 150px; height: 150px; object-fit: cover;" id="logo-preview">
                                    <div class="mb-3">
                                        <input type="file" class="form-control" id="logo" name="logo" accept="image/jpeg,image/png">
                                        <div class="form-text">Max file size: 5MB</div>
                                    </div>
                                    <div class="form-check mb-3">
                                        <input class="form-check-input" type="checkbox" id="use_default_logo" name="use_default_logo">
                                        <label class="form-check-label" for="use_default_logo">
                                            Use default logo
                                        </label>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-9">
                                <div class="mb-3">
                                    <label for="company_name" class="form-label">Company Name <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="company_name" name="company_name" 
                                           value="<?php echo $profile ? htmlspecialchars($profile['company_name']) : ''; ?>" 
                                           required>
                                </div>

                                <div class="mb-3">
                                    <label for="industry" class="form-label">Industry <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="industry" name="industry" 
                                           value="<?php echo $profile ? htmlspecialchars($profile['industry']) : ''; ?>" 
                                           required>
                                </div>

                                <div class="mb-3">
                                    <label for="company_size" class="form-label">Company Size</label>
                                    <select class="form-select" id="company_size" name="company_size">
                                        <option value="">Select Size</option>
                                        <option value="1-10" <?php echo $profile && $profile['company_size'] === '1-10' ? 'selected' : ''; ?>>1-10 employees</option>
                                        <option value="11-50" <?php echo $profile && $profile['company_size'] === '11-50' ? 'selected' : ''; ?>>11-50 employees</option>
                                        <option value="51-200" <?php echo $profile && $profile['company_size'] === '51-200' ? 'selected' : ''; ?>>51-200 employees</option>
                                        <option value="201-500" <?php echo $profile && $profile['company_size'] === '201-500' ? 'selected' : ''; ?>>201-500 employees</option>
                                        <option value="501+" <?php echo $profile && $profile['company_size'] === '501+' ? 'selected' : ''; ?>>501+ employees</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="website" class="form-label">Website</label>
                            <input type="url" class="form-control" id="website" name="website" 
                                   value="<?php echo $profile && !is_null($profile['website']) ? htmlspecialchars($profile['website']) : ''; ?>" 
                                   placeholder="https://example.com">
                        </div>

                        <div class="mb-3">
                            <label for="location" class="form-label">Location</label>
                            <input type="text" class="form-control" id="location" name="location" 
                                   value="<?php echo $profile ? htmlspecialchars($profile['location']) : ''; ?>" 
                                   placeholder="City, Country">
                        </div>

                        <div class="mb-3">
                            <label for="about" class="form-label">About Company</label>
                            <textarea class="form-control" id="about" name="about" rows="5" 
                                      placeholder="Tell us about your company, culture, and what makes you unique..."
                            ><?php echo $profile ? htmlspecialchars($profile['about']) : ''; ?></textarea>
                        </div>

                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-primary">Save Changes</button>
                            <a href="<?php echo BASE_URL; ?>/employer/dashboard.php" class="btn btn-secondary" type="button">Cancel</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Update the JavaScript section
document.getElementById('logo').addEventListener('change', function(e) {
    const file = e.target.files[0];
    if (file) {
        const reader = new FileReader();
        reader.onload = function(e) {
            document.getElementById('logo-preview').src = e.target.result;
        };
        reader.readAsDataURL(file);
        // Uncheck the default logo checkbox when a file is selected
        document.getElementById('use_default_logo').checked = false;
    }
});

document.getElementById('use_default_logo').addEventListener('change', function(e) {
    if (this.checked) {
        // Clear the file input
        document.getElementById('logo').value = '';
        // Set preview to default logo
        document.getElementById('logo-preview').src = '<?php echo BASE_URL; ?>/assets/images/DefaultCompanyLogo.jpg';
    }
});
</script>

<?php include '../includes/footer.php'; ?> 