<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo SITE_NAME; ?></title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link rel="icon" type="image/png" href="<?php echo BASE_URL; ?>/assets/images/Logo.png">
    <style>
        .company-logo {
            width: 50px;
            height: 50px;
            object-fit: cover;
            border-radius: 5px;
        }
        .navbar-brand img {
            height: 40px;
        }
        .notification-bell {
            width: 22px;
            height: 22px;
            filter: invert(1); /* Make icon white */
        }
        .notif-badge {
            position: absolute;
            top: 2px;
            left: 16px;
            transform: translate(-50%, -50%);
            z-index: 2;
            font-size: 0.75rem;
            padding: 2px 6px;
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand" href="<?php echo BASE_URL; ?>">
                <?php echo SITE_NAME; ?>
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo BASE_URL; ?>/job.php">Job</a>
                    </li>
                    <?php if (isLoggedIn()): ?>
                        <?php if (hasRole('student')): ?>
                            <li class="nav-item">
                                <a class="nav-link" href="<?php echo BASE_URL; ?>/student/dashboard.php">Dashboard</a>
                            </li>
                        <?php elseif (hasRole('employer')): ?>
                            <li class="nav-item">
                                <a class="nav-link" href="<?php echo BASE_URL; ?>/employer/dashboard.php">Dashboard</a>
                            </li>
                        <?php elseif (hasRole('admin')): ?>
                            <li class="nav-item">
                                <a class="nav-link" href="<?php echo BASE_URL; ?>/admin/dashboard.php">Admin Dashboard</a>
                            </li>
                        <?php endif; ?>
                    <?php endif; ?>
                </ul>
                <ul class="navbar-nav">
                    <?php if (isLoggedIn()): ?>
                        <?php if (hasRole('student')): ?>
                            <?php
                            // Fetch unread notifications count and recent notifications
                            $notif_count = 0;
                            $recent_notifs = [];
                            try {
                                $db = new Database();
                                $conn = $db->getConnection();
                                $stmt = $conn->prepare("SELECT s.id FROM student_profiles s INNER JOIN users u ON s.user_id = u.id WHERE u.id = ?");
                                $stmt->execute([$_SESSION['user_id']]);
                                $student_id = $stmt->fetchColumn();
                                if ($student_id) {
                                    $stmt = $conn->prepare("SELECT COUNT(*) FROM notifications WHERE student_id = ? AND is_read = 0");
                                    $stmt->execute([$student_id]);
                                    $notif_count = $stmt->fetchColumn();
                                    $stmt = $conn->prepare("SELECT * FROM notifications WHERE student_id = ? ORDER BY created_at DESC LIMIT 5");
                                    $stmt->execute([$student_id]);
                                    $recent_notifs = $stmt->fetchAll();
                                }
                            } catch (Exception $e) {}
                            ?>
                            <li class="nav-item dropdown">
                                <a class="nav-link position-relative" href="#" id="notifDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                                    <img src="<?php echo BASE_URL; ?>/assets/images/notification.png" alt="Notifications" class="notification-bell">
                                    <?php if ($notif_count > 0): ?>
                                        <span class="notif-badge badge rounded-pill bg-danger">
                                            <?php echo $notif_count; ?>
                                        </span>
                                    <?php endif; ?>
                                </a>
                                <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="notifDropdown" style="min-width: 320px;">
                                    <li class="dropdown-header">Notifications</li>
                                    <?php if (empty($recent_notifs)): ?>
                                        <li><span class="dropdown-item text-muted">No notifications</span></li>
                                    <?php else: ?>
                                        <?php foreach ($recent_notifs as $notif): ?>
                                            <li>
                                                <a class="dropdown-item<?php echo !$notif['is_read'] ? ' fw-bold' : ''; ?>" href="<?php echo htmlspecialchars($notif['link']); ?>">
                                                    <?php echo htmlspecialchars($notif['message']); ?>
                                                    <br><small class="text-muted"><?php echo date('M d, Y H:i', strtotime($notif['created_at'])); ?></small>
                                                </a>
                                            </li>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </ul>
                            </li>
                        <?php endif; ?>
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown">
                                Account
                            </a>
                            <ul class="dropdown-menu dropdown-menu-end">
                                <li><a class="dropdown-item" href="<?php echo BASE_URL; ?>/profile.php">Profile</a></li>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item" href="<?php echo BASE_URL; ?>/logout.php">Logout</a></li>
                            </ul>
                        </li>
                    <?php else: ?>
                        <li class="nav-item">
                            <a class="nav-link" href="<?php echo BASE_URL; ?>/login.php">Login</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="<?php echo BASE_URL; ?>/register.php">Register</a>
                        </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>

    <?php if (isset($_SESSION['flash'])): ?>
        <div class="container mt-3">
            <div class="alert alert-<?php echo $_SESSION['flash']['type']; ?> alert-dismissible fade show">
                <?php echo $_SESSION['flash']['message']; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        </div>
        <?php unset($_SESSION['flash']); ?>
    <?php endif; ?>

    <main class="container py-4"> 