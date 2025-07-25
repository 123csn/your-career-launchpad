<?php
require_once '../config/config.php';
require_once '../config/database.php';

if (!isLoggedIn() || !hasRole('admin')) {
    redirectWith(BASE_URL . '/login.php', 'Please login as an admin to access this page.', 'warning');
}

$db = new Database();
$conn = $db->getConnection();

// --- Users Tab Data & Deletion ---
$userDeleteMsg = '';
if (isset($_POST['delete_user_id']) && is_numeric($_POST['delete_user_id'])) {
    $deleteId = (int)$_POST['delete_user_id'];
    if ($deleteId !== $_SESSION['user_id']) { // Prevent self-delete
        $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
        $stmt->execute([$deleteId]);
        $userDeleteMsg = 'User deleted successfully.';
    } else {
        $userDeleteMsg = 'You cannot delete your own account.';
    }
}

$users = $conn->query("SELECT u.id, u.email, u.role, s.first_name, s.last_name, e.company_name FROM users u
    LEFT JOIN student_profiles s ON u.id = s.user_id
    LEFT JOIN employer_profiles e ON u.id = e.user_id
    ORDER BY u.role, u.id")->fetchAll();

include '../includes/header.php';
?>

<style>
.management-header {
    background: white;
    border-radius: 8px;
    padding: 1.5rem;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    border: 1px solid #e0e0e0;
    margin-bottom: 2rem;
}

.management-header h1 {
    color: #0d6efd;
    font-size: 1.8rem;
    margin-bottom: 0.5rem;
}

.back-btn {
    background: #6c757d;
    color: white;
    border: none;
    padding: 0.5rem 1rem;
    border-radius: 4px;
    text-decoration: none;
    display: inline-block;
    margin-bottom: 1rem;
    transition: background-color 0.3s;
}

.back-btn:hover {
    background: #5a6268;
    color: white;
    text-decoration: none;
}

.table-container {
    background: white;
    border-radius: 8px;
    padding: 1.5rem;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    border: 1px solid #e0e0e0;
}

.table th {
    background: #f8f9fa;
    border-color: #dee2e6;
    color: #495057;
    font-weight: 600;
}

.table td {
    vertical-align: middle;
}

.role-badge {
    padding: 0.25rem 0.5rem;
    border-radius: 4px;
    font-size: 0.8rem;
    font-weight: 500;
}

.role-admin {
    background: #dc3545;
    color: white;
}

.role-student {
    background: #28a745;
    color: white;
}

.role-employer {
    background: #007bff;
    color: white;
}
</style>

<div class="container py-5">
    <div class="management-header">
        <a href="<?php echo BASE_URL; ?>/admin/dashboard.php" class="back-btn">
            <i class="fas fa-arrow-left"></i> Back to Dashboard
        </a>
        <h1><i class="fas fa-users"></i> User Management</h1>
        <p class="text-muted">Manage all registered users including students, employers, and administrators.</p>
    </div>

    <div class="table-container">
        <?php if ($userDeleteMsg): ?>
            <div class="alert alert-<?php echo strpos($userDeleteMsg, 'successfully') !== false ? 'success' : 'warning'; ?> alert-dismissible fade show">
                <?php echo $userDeleteMsg; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <div class="table-responsive">
            <table class="table table-bordered table-hover align-middle">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Role</th>
                        <th>Name / Company</th>
                        <th>Email</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($users as $user): ?>
                        <tr>
                            <td><?php echo $user['id']; ?></td>
                            <td>
                                <span class="role-badge role-<?php echo $user['role']; ?>">
                                    <?php echo ucfirst($user['role']); ?>
                                </span>
                            </td>
                            <td>
                                <?php
                                if ($user['role'] === 'student') {
                                    echo htmlspecialchars(trim($user['first_name'] . ' ' . $user['last_name']));
                                } elseif ($user['role'] === 'employer') {
                                    echo htmlspecialchars($user['company_name']);
                                } else {
                                    echo '-';
                                }
                                ?>
                            </td>
                            <td><?php echo htmlspecialchars($user['email']); ?></td>
                            <td>
                                <?php if ($user['id'] !== $_SESSION['user_id']): ?>
                                    <form method="POST" style="display:inline;" onsubmit="return confirm('Are you sure you want to delete this user? This action cannot be undone.');">
                                        <input type="hidden" name="delete_user_id" value="<?php echo $user['id']; ?>">
                                        <button type="submit" class="btn btn-sm btn-danger">
                                            <i class="fas fa-trash"></i> Delete
                                        </button>
                                    </form>
                                <?php else: ?>
                                    <span class="text-muted">
                                        <i class="fas fa-user"></i> (You)
                                    </span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <?php if (empty($users)): ?>
            <div class="text-center py-4">
                <i class="fas fa-users fa-3x text-muted mb-3"></i>
                <p class="text-muted">No users found.</p>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php include '../includes/footer.php'; ?> 