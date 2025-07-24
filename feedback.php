<?php
require_once 'config/config.php';
require_once 'config/database.php';
ini_set('display_errors', 1);
error_reporting(E_ALL);
include 'includes/header.php';

$feedbackMsg = '';

// Handle feedback deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_feedback']) && isset($_POST['feedback_date'])) {
    $user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;
    $feedback_date = $_POST['feedback_date'];
    if ($user_id) {
        // Remove the feedback from feedbacks.txt
        $lines = file('feedbacks.txt', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        $newLines = [];
        foreach ($lines as $line) {
            $data = json_decode($line, true);
            if ($data && isset($data['user'], $data['date'])) {
                if (!($data['user'] == $user_id && $data['date'] == $feedback_date)) {
                    $newLines[] = $line;
                }
            } else {
                $newLines[] = $line;
            }
        }
        file_put_contents('feedbacks.txt', implode("\n", $newLines) . (count($newLines) ? "\n" : ""));
        $feedbackMsg = 'Your feedback has been deleted.';
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['feedback']) && !empty($_POST['rating']) && !isset($_POST['delete_feedback'])) {
    $feedback = trim(strip_tags($_POST['feedback']));
    $rating = (int)$_POST['rating'];
    $user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;
    $user = $user_id ? $user_id : 'Guest';
    $entry = [
        'date' => date('Y-m-d H:i:s'),
        'user' => $user,
        'rating' => $rating,
        'feedback' => $feedback
    ];
    file_put_contents('feedbacks.txt', json_encode($entry) . "\n", FILE_APPEND);
    $feedbackMsg = 'Thank you for your feedback!';
}

// Read all feedbacks
$allFeedbacks = [];
if (file_exists('feedbacks.txt')) {
    $lines = file('feedbacks.txt', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        $data = json_decode($line, true);
        if ($data && isset($data['feedback'], $data['rating'], $data['date'])) {
            $allFeedbacks[] = $data;
        }
    }
    // Show most recent first
    $allFeedbacks = array_reverse($allFeedbacks);
}

// Prepare user name lookup for all user IDs in feedbacks
$userNames = [];
$userIds = array_unique(array_filter(array_map(function($fb) { return is_numeric($fb['user']) ? $fb['user'] : null; }, $allFeedbacks)));
if (!empty($userIds)) {
    $db = new Database();
    $conn = $db->getConnection();
    $placeholders = implode(',', array_fill(0, count($userIds), '?'));
    $sql = "SELECT u.id, s.first_name, s.last_name FROM users u LEFT JOIN student_profiles s ON u.id = s.user_id WHERE u.id IN ($placeholders)";
    $stmt = $conn->prepare($sql);
    $stmt->execute($userIds);
    while ($row = $stmt->fetch()) {
        $userNames[$row['id']] = trim($row['first_name'] . ' ' . $row['last_name']);
    }
}
?>
<div class="container py-5" style="max-width:600px;">
    <h1 class="mb-4 text-center">Feedback</h1>
    <?php if ($feedbackMsg): ?>
        <div class="alert alert-success text-center"><?php echo $feedbackMsg; ?></div>
    <?php endif; ?>
    <form method="POST" action="" class="mb-5">
        <div class="mb-3">
            <label for="feedback" class="form-label">Your Feedback</label>
            <textarea class="form-control" id="feedback" name="feedback" rows="5" required></textarea>
        </div>
        <div class="mb-3">
            <label class="form-label">Rating</label><br>
            <?php for ($i = 1; $i <= 5; $i++): ?>
                <input type="radio" class="btn-check" name="rating" id="star<?php echo $i; ?>" value="<?php echo $i; ?>" required>
                <label class="btn btn-outline-warning" for="star<?php echo $i; ?>">
                    <?php for ($j = 1; $j <= $i; $j++) echo '★'; ?>
                </label>
            <?php endfor; ?>
        </div>
        <div class="d-grid">
            <button type="submit" class="btn btn-primary">Submit Feedback</button>
        </div>
    </form>
    <?php if (!empty($allFeedbacks)): ?>
        <h2 class="mb-4 text-center">Recent Feedback</h2>
        <div class="row g-3">
        <?php foreach ($allFeedbacks as $fb): ?>
            <div class="col-md-6 col-lg-4">
                <div class="card h-100 mb-3">
                    <div class="card-body">
                        <div class="mb-2">
                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                <span style="color:<?php echo $i <= $fb['rating'] ? '#ffc107' : '#e4e5e9'; ?>; font-size:1.2em;">★</span>
                            <?php endfor; ?>
                        </div>
                        <p class="mb-1"><?php echo nl2br(htmlspecialchars($fb['feedback'])); ?></p>
                        <div class="text-muted small mb-2">Posted on <?php echo htmlspecialchars($fb['date']); ?><?php 
                            if (is_numeric($fb['user']) && isset($userNames[$fb['user']]) && $userNames[$fb['user']]) {
                                echo ' &ndash; ' . htmlspecialchars($userNames[$fb['user']]);
                            } elseif (!is_numeric($fb['user'])) {
                                echo ' &ndash; Guest';
                            }
                        ?></div>
                        <?php if (isset($_SESSION['user_id']) && is_numeric($fb['user']) && $_SESSION['user_id'] == $fb['user']): ?>
                            <form method="POST" action="" onsubmit="return confirm('Are you sure you want to delete this feedback?');">
                                <input type="hidden" name="feedback_date" value="<?php echo htmlspecialchars($fb['date']); ?>">
                                <button type="submit" name="delete_feedback" class="btn btn-sm btn-danger">Delete</button>
                            </form>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>
<?php include 'includes/footer.php'; ?> 