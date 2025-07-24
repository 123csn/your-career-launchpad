<?php
require_once 'config/config.php';
require_once 'config/database.php';

// If user is already logged in, redirect to appropriate dashboard
if (isLoggedIn()) {
    redirectToDashboard();
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = sanitizeInput($_POST['email']);
    
    if (empty($email)) {
        $error = 'Please enter your email address.';
    } else {
        $db = new Database();
        $conn = $db->getConnection();
        
        // Check if email exists
        $query = "SELECT id, email FROM users WHERE email = :email";
        $stmt = $conn->prepare($query);
        $stmt->execute(['email' => $email]);
        $user = $stmt->fetch();
        
        if ($user) {
            // Generate reset token
            $token = bin2hex(random_bytes(32));
            $expiry = date('Y-m-d H:i:s', strtotime('+1 hour'));
            
            // Store reset token in database
            $query = "INSERT INTO password_resets (user_id, token, expiry) VALUES (:user_id, :token, :expiry)";
            $stmt = $conn->prepare($query);
            $stmt->execute([
                'user_id' => $user['id'],
                'token' => $token,
                'expiry' => $expiry
            ]);
            
            // Send reset link (in a real application, this would be sent via email)
            $resetLink = BASE_URL . '/reset-password.php?token=' . $token;
            $success = 'Password reset instructions have been sent to your email address. For demo purposes, here is your reset link: <a href="' . $resetLink . '">Reset Password</a>';
        } else {
            // Don't reveal if email exists or not for security
            $success = 'If your email address exists in our database, you will receive a password recovery link at your email address.';
        }
    }
}

include 'includes/header.php';
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h4 class="mb-0">Forgot Password</h4>
                </div>
                <div class="card-body">
                    <?php if ($error): ?>
                        <div class="alert alert-danger"><?php echo $error; ?></div>
                    <?php endif; ?>
                    
                    <?php if ($success): ?>
                        <div class="alert alert-success"><?php echo $success; ?></div>
                    <?php endif; ?>

                    <form method="POST" action="">
                        <div class="mb-3">
                            <label for="email" class="form-label">Email Address</label>
                            <input type="email" class="form-control" id="email" name="email" required>
                            <div class="form-text">Enter your registered email address and we'll send you a link to reset your password.</div>
                        </div>
                        
                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-primary">Send Reset Link</button>
                            <a href="login.php" class="btn btn-link">Back to Login</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?> 