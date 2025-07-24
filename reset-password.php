<?php
require_once 'config/config.php';
require_once 'config/database.php';

// If user is already logged in, redirect to appropriate dashboard
if (isLoggedIn()) {
    redirectToDashboard();
}

$error = '';
$success = '';
$validToken = false;
$token = $_GET['token'] ?? '';

if (empty($token)) {
    redirectWith('login.php', 'Invalid password reset link.', 'error');
}

$db = new Database();
$conn = $db->getConnection();

// Verify token and check if it's not expired
$query = "SELECT pr.*, u.email 
          FROM password_resets pr 
          INNER JOIN users u ON pr.user_id = u.id 
          WHERE pr.token = :token AND pr.used = 0 AND pr.expiry > NOW()";
$stmt = $conn->prepare($query);
$stmt->execute(['token' => $token]);
$reset = $stmt->fetch();

if (!$reset) {
    redirectWith('login.php', 'Invalid or expired password reset link.', 'error');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';
    
    if (empty($password)) {
        $error = 'Please enter a new password.';
    } elseif (strlen($password) < 8) {
        $error = 'Password must be at least 8 characters long.';
    } elseif ($password !== $confirmPassword) {
        $error = 'Passwords do not match.';
    } else {
        try {
            // Update password
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            $query = "UPDATE users SET password = :password WHERE id = :user_id";
            $stmt = $conn->prepare($query);
            $stmt->execute([
                'password' => $hashedPassword,
                'user_id' => $reset['user_id']
            ]);
            
            // Mark reset token as used
            $query = "UPDATE password_resets SET used = 1 WHERE id = :id";
            $stmt = $conn->prepare($query);
            $stmt->execute(['id' => $reset['id']]);
            
            redirectWith('login.php', 'Your password has been reset successfully. You can now login with your new password.', 'success');
        } catch (PDOException $e) {
            $error = 'Error resetting password. Please try again.';
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
                    <h4 class="mb-0">Reset Password</h4>
                </div>
                <div class="card-body">
                    <?php if ($error): ?>
                        <div class="alert alert-danger"><?php echo $error; ?></div>
                    <?php endif; ?>

                    <p class="mb-3">Setting new password for <strong><?php echo htmlspecialchars($reset['email']); ?></strong></p>

                    <form method="POST" action="">
                        <div class="mb-3">
                            <label for="password" class="form-label">New Password</label>
                            <input type="password" class="form-control" id="password" name="password" required 
                                   minlength="8">
                            <div class="form-text">Password must be at least 8 characters long.</div>
                        </div>

                        <div class="mb-3">
                            <label for="confirm_password" class="form-label">Confirm New Password</label>
                            <input type="password" class="form-control" id="confirm_password" 
                                   name="confirm_password" required minlength="8">
                        </div>
                        
                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-primary">Reset Password</button>
                            <a href="login.php" class="btn btn-link">Back to Login</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?> 