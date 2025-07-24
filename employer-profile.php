<?php
require_once 'config/config.php';
require_once 'config/database.php';

$employer_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if (!$employer_id) {
    echo '<div class="container py-5"><div class="alert alert-danger">Invalid employer ID.</div></div>';
    exit;
}

$db = new Database();
$conn = $db->getConnection();

// Fetch employer profile and email
$query = 'SELECT e.*, u.email FROM employer_profiles e INNER JOIN users u ON e.user_id = u.id WHERE e.id = :id';
$stmt = $conn->prepare($query);
$stmt->execute(['id' => $employer_id]);
$employer = $stmt->fetch();

include 'includes/header.php';
?>
<div class="container py-5">
    <?php if (!$employer): ?>
        <div class="alert alert-danger">Employer profile not found.</div>
    <?php else: ?>
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card mb-4">
                    <div class="card-body text-center">
                        <img src="<?php echo !empty($employer['logo_path']) ? BASE_URL . '/' . $employer['logo_path'] : BASE_URL . '/assets/images/DefaultCompanyLogo.jpg'; ?>" 
                             alt="<?php echo htmlspecialchars($employer['company_name']); ?> Logo" 
                             class="mb-3" style="width: 100px; height: 100px; object-fit: cover; border-radius: 16px;">
                        <h2 class="mb-1"><?php echo htmlspecialchars($employer['company_name']); ?></h2>
                        <p class="text-muted mb-2"><?php echo htmlspecialchars($employer['industry']); ?><?php if ($employer['company_size']): ?> &bull; <?php echo htmlspecialchars($employer['company_size']); ?><?php endif; ?></p>
                        <p class="mb-2"><i class="fas fa-map-marker-alt me-1"></i> <?php echo htmlspecialchars($employer['location']); ?></p>
                        <?php if ($employer['website']): ?>
                            <p class="mb-2"><a href="<?php echo htmlspecialchars($employer['website']); ?>" target="_blank" class="btn btn-outline-primary">Visit Website</a></p>
                        <?php endif; ?>
                        <p class="mb-3"><strong>Contact Email:</strong> <a href="mailto:<?php echo htmlspecialchars($employer['email']); ?>"><?php echo htmlspecialchars($employer['email']); ?></a></p>
                        <hr>
                        <h5>About Company</h5>
                        <p><?php echo nl2br(htmlspecialchars($employer['about'])); ?></p>
                    </div>
                </div>
                <div class="card mb-4">
                    <div class="card-body">
                        <h4 class="mb-3">Job Listings by <?php echo htmlspecialchars($employer['company_name']); ?></h4>
                        <?php
                        $stmt_jobs = $conn->prepare('SELECT * FROM job WHERE employer_id = :employer_id ORDER BY created_at DESC');
                        $stmt_jobs->execute(['employer_id' => $employer['id']]);
                        $jobs = $stmt_jobs->fetchAll();
                        ?>
                        <?php if ($jobs): ?>
                            <div class="table-responsive">
                                <table class="table table-bordered align-middle">
                                    <thead>
                                        <tr>
                                            <th>Title</th>
                                            <th>Type</th>
                                            <th>Location</th>
                                            <th>Status</th>
                                            <th>View</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                    <?php foreach ($jobs as $job): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($job['title']); ?></td>
                                            <td><?php echo htmlspecialchars($job['job_type']); ?></td>
                                            <td><?php echo htmlspecialchars($job['location']); ?></td>
                                            <td>
                                                <span class="badge bg-<?php echo strtolower($job['status']) === 'active' ? 'success' : 'secondary'; ?>">
                                                    <?php echo htmlspecialchars(strtolower($job['status'])); ?>
                                                </span>
                                            </td>
                                            <td><a href="jobs/view.php?id=<?php echo $job['id']; ?>" class="btn btn-sm btn-outline-primary" target="_blank">View</a></td>
                                        </tr>
                                    <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <p class="text-muted">No jobs posted yet.</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>
<?php include 'includes/footer.php'; ?> 