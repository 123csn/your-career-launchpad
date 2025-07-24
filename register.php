<?php
require_once 'config/config.php';
require_once 'config/database.php';

// Check if user is already logged in
if (isLoggedIn()) {
    redirectWith(BASE_URL, 'You are already logged in.', 'info');
}

$errors = [];

// Process registration form
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = sanitizeInput($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $role = sanitizeInput($_POST['role']);
    
    // Validate input
    if (empty($email)) {
        $errors[] = "Email is required";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Invalid email format";
    }
    
    if (empty($password)) {
        $errors[] = "Password is required";
    } elseif (strlen($password) < 8) {
        $errors[] = "Password must be at least 8 characters long";
    }
    
    if ($password !== $confirm_password) {
        $errors[] = "Passwords do not match";
    }
    
    if (!in_array($role, ['student', 'employer'])) {
        $errors[] = "Invalid role selected";
    }
    
    if (empty($errors)) {
        $db = new Database();
        $conn = $db->getConnection();
        
        try {
            // Check if email already exists
            $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
            $stmt->execute([$email]);
            
            if ($stmt->fetch()) {
                $errors[] = "Email already registered";
            } else {
                // Create new user
                $stmt = $conn->prepare("INSERT INTO users (email, password, role) VALUES (?, ?, ?)");
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                $stmt->execute([$email, $hashed_password, $role]);
                
                $user_id = $conn->lastInsertId();
                
                // Create profile placeholder based on role
                if ($role === 'student') {
                    $stmt = $conn->prepare("INSERT INTO student_profiles (user_id) VALUES (?)");
                } else {
                    $stmt = $conn->prepare("INSERT INTO employer_profiles (user_id) VALUES (?)");
                }
                $stmt->execute([$user_id]);
                
                // Set session variables
                $_SESSION['user_id'] = $user_id;
                $_SESSION['user_email'] = $email;
                $_SESSION['user_role'] = $role;
                
                // Redirect to complete profile
                if ($role === 'student') {
                    redirectWith(BASE_URL . '/student/complete-profile.php', 'Account created successfully! Please complete your profile.', 'success');
                } else {
                    redirectWith(BASE_URL . '/employer/complete-profile.php', 'Account created successfully! Please complete your company profile.', 'success');
                }
            }
        } catch (PDOException $e) {
            $errors[] = "An error occurred. Please try again later.";
        }
    }
}

include 'includes/header.php';
?>

<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-6 col-lg-5">
            <div class="card shadow-sm">
                <div class="card-body p-5">
                    <h2 class="text-center mb-4">Create Account</h2>
                    
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
                            <label for="email" class="form-label">Email address</label>
                            <input type="email" class="form-control" id="email" name="email" 
                                   value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>" 
                                   required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="password" class="form-label">Password</label>
                            <input type="password" class="form-control" id="password" name="password" 
                                   required minlength="8">
                            <div class="form-text">Password must be at least 8 characters long</div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="confirm_password" class="form-label">Confirm Password</label>
                            <input type="password" class="form-control" id="confirm_password" name="confirm_password" 
                                   required minlength="8">
                        </div>
                        
                        <div class="mb-4">
                            <label class="form-label d-block">I am a:</label>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" name="role" id="role_student" 
                                       value="student" <?php echo (!isset($_POST['role']) || $_POST['role'] === 'student') ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="role_student">Student</label>
                            </div>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" name="role" id="role_employer" 
                                       value="employer" <?php echo (isset($_POST['role']) && $_POST['role'] === 'employer') ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="role_employer">Employer</label>
                            </div>
                        </div>
                        
                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary btn-lg">Register</button>
                        </div>
                    </form>
                    
                    <div class="text-center mt-4">
                        <p class="mb-0">Already have an account? <a href="login.php">Login here</a></p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?> 