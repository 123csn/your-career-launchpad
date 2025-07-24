<?php
require_once 'config/config.php';
require_once 'config/database.php';

$db = new Database();
$conn = $db->getConnection();

// Auto-close jobs whose deadline has passed
$today = date('Y-m-d');
$conn->prepare("UPDATE job SET status = 'closed' WHERE deadline IS NOT NULL AND deadline < ? AND status = 'active'")->execute([$today]);

// Get filter parameters
$keyword = isset($_GET['keyword']) ? sanitizeInput($_GET['keyword']) : '';
$location = isset($_GET['location']) ? sanitizeInput($_GET['location']) : '';
$job_type = isset($_GET['job_type']) ? sanitizeInput($_GET['job_type']) : '';
$industry = isset($_GET['industry']) ? sanitizeInput($_GET['industry']) : '';
$skillset = isset($_GET['skillset']) ? sanitizeInput($_GET['skillset']) : '';
$internship_only = isset($_GET['internship_only']) ? true : false;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$per_page = 10;

// Build query with OR logic
$conditions = [];
$params = [];
if ($keyword) {
    $conditions[] = "(j.title LIKE ? OR j.description LIKE ? OR j.requirements LIKE ?)";
    $keyword_param = "%{$keyword}%";
    $params = array_merge($params, [$keyword_param, $keyword_param, $keyword_param]);
}
if ($location) {
    $conditions[] = "j.location LIKE ?";
    $params[] = "%{$location}%";
}
if ($job_type) {
    $conditions[] = "j.job_type = ?";
    $params[] = $job_type;
}

$query = "SELECT j.*, e.company_name, e.logo_path FROM job j INNER JOIN employer_profiles e ON j.employer_id = e.id WHERE j.status = 'active'";
if (!empty($conditions)) {
    $query .= " AND (" . implode(' OR ', $conditions) . ")";
}

// Get total count for pagination
$count_query = str_replace("j.*, e.company_name, e.logo_path", "COUNT(*) as total", $query);
$stmt = $conn->prepare($count_query);
$stmt->execute($params);
$total_jobs = $stmt->fetch()['total'];
$total_pages = ceil($total_jobs / $per_page);

// Add pagination to main query
$query .= " ORDER BY j.created_at DESC LIMIT $per_page OFFSET " . (($page - 1) * $per_page);

// Get jobs
$stmt = $conn->prepare($query);
$stmt->execute($params);
$jobs = $stmt->fetchAll();

include 'includes/header.php';
?>

<div class="container">
    <!-- Search Form -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" class="job-search-form mb-4">
                <div class="row g-3 align-items-end">
                    <div class="col-md-5">
                        <label for="keyword" class="form-label">Keyword</label>
                        <input type="text" class="form-control" id="keyword" name="keyword" 
                               value="<?php echo htmlspecialchars($keyword); ?>" 
                               placeholder="Job title or skills">
                    </div>
                    <div class="col-md-4">
                        <label for="location" class="form-label">Location</label>
                        <input type="text" class="form-control" id="location" name="location" 
                               value="<?php echo htmlspecialchars($location); ?>" 
                               placeholder="City or country">
                    </div>
                    <div class="col-md-2">
                        <label for="job_type" class="form-label">Job Type</label>
                        <select class="form-select" id="job_type" name="job_type">
                            <option value="">All Types</option>
                            <option value="full-time" <?php echo $job_type === 'full-time' ? 'selected' : ''; ?>>Full Time</option>
                            <option value="part-time" <?php echo $job_type === 'part-time' ? 'selected' : ''; ?>>Part Time</option>
                            <option value="internship" <?php echo $job_type === 'internship' ? 'selected' : ''; ?>>Internship</option>
                            <option value="contract" <?php echo $job_type === 'contract' ? 'selected' : ''; ?>>Contract</option>
                        </select>
                    </div>
                    <div class="col-md-1">
                        <button type="submit" class="btn btn-primary w-100">Search</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Results Count -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="mb-0">Available Jobs</h2>
        <p class="text-muted mb-0"><?php echo $total_jobs; ?> jobs found</p>
    </div>

    <!-- Job Listings -->
    <?php if (!empty($jobs)): ?>
        <?php
        // Score jobs by number of matched fields
        $scored_jobs = [];
        foreach ($jobs as $job) {
            $matched = [];
            if ($keyword && (stripos($job['title'], $keyword) !== false || stripos($job['description'], $keyword) !== false || stripos($job['requirements'], $keyword) !== false)) {
                $matched[] = 'Keyword';
            }
            if ($location && stripos($job['location'], $location) !== false) {
                $matched[] = 'Location';
            }
            if ($job_type && strtolower($job['job_type']) === strtolower($job_type)) {
                $matched[] = 'Job Type';
            }
            $scored_jobs[] = [
                'job' => $job,
                'matched' => $matched,
                'score' => count($matched)
            ];
        }
        // Sort by score descending
        usort($scored_jobs, function($a, $b) { return $b['score'] <=> $a['score']; });
        ?>
        <div class="row">
            <?php foreach ($scored_jobs as $item): $job = $item['job']; $matched = $item['matched']; ?>
                <div class="col-md-6 mb-4">
                    <div class="card h-100">
                        <div class="card-body">
                            <div class="d-flex align-items-center mb-3">
                                <img src="<?php echo $job['logo_path'] ? BASE_URL . '/' . $job['logo_path'] : BASE_URL . '/assets/images/DefaultCompanyLogo.jpg'; ?>" 
                                     alt="<?php echo htmlspecialchars($job['company_name']); ?>" 
                                     class="company-logo me-3">
                                <div>
                                    <h5 class="card-title mb-1"><?php echo htmlspecialchars($job['title']); ?></h5>
                                    <h6 class="company-name text-muted mb-0"><?php echo htmlspecialchars($job['company_name']); ?></h6>
                                </div>
                            </div>
                            <div class="job-meta mb-3">
                                <span class="badge bg-primary"><?php echo htmlspecialchars($job['job_type']); ?></span>
                                <span class="badge bg-secondary"><?php echo htmlspecialchars($job['location']); ?></span>
                                <?php if ($job['salary_range']): ?>
                                    <span class="badge bg-info"><?php echo htmlspecialchars($job['salary_range']); ?></span>
                                <?php endif; ?>
                            </div>
                            <p class="card-text"><?php echo substr(htmlspecialchars($job['description']), 0, 150) . '...'; ?></p>
                            <?php if (!empty($matched)): ?>
                                <div class="mb-2"><small class="text-success">Matched: <?php echo implode(', ', $matched); ?></small></div>
                            <?php endif; ?>
                            <div class="d-flex justify-content-between align-items-center mt-3">
                                <small class="text-muted">
                                    Posted <?php echo time_elapsed_string($job['created_at']); ?>
                                </small>
                                <a href="jobs/view.php?id=<?php echo $job['id']; ?>" class="btn btn-outline-primary">View Details</a>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- Pagination -->
        <?php if ($total_pages > 1): ?>
            <nav aria-label="Job listings pagination" class="mt-4">
                <ul class="pagination justify-content-center">
                    <?php
                    // Build base query string with all current filters
                    $query_params = [
                        'keyword' => $keyword,
                        'location' => $location,
                        'job_type' => $job_type
                    ];
                    $base_query = http_build_query(array_filter($query_params));
                    ?>
                    <?php if ($page > 1): ?>
                        <li class="page-item">
                            <a class="page-link" href="?<?php echo $base_query . ($base_query ? '&' : '') . 'page=' . ($page - 1); ?>">Previous</a>
                        </li>
                    <?php endif; ?>
                    <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                        <li class="page-item <?php echo $i === $page ? 'active' : ''; ?>">
                            <a class="page-link" href="?<?php echo $base_query . ($base_query ? '&' : '') . 'page=' . $i; ?>"><?php echo $i; ?></a>
                        </li>
                    <?php endfor; ?>
                    <?php if ($page < $total_pages): ?>
                        <li class="page-item">
                            <a class="page-link" href="?<?php echo $base_query . ($base_query ? '&' : '') . 'page=' . ($page + 1); ?>">Next</a>
                        </li>
                    <?php endif; ?>
                </ul>
            </nav>
        <?php endif; ?>
    <?php else: ?>
        <div class="text-center py-5">
            <i class="fas fa-search fa-3x text-muted mb-3"></i>
            <h3>No jobs found</h3>
            <p class="text-muted">Try adjusting your search criteria or check back later for new opportunities.</p>
        </div>
    <?php endif; ?>
</div>

<?php
// AI-Powered Job Recommendations for Students
if (isLoggedIn() && hasRole('student')) {
    // Fetch student skills
    $stmt = $conn->prepare("SELECT skills FROM student_profiles WHERE user_id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $student_skills = $stmt->fetchColumn();
    $skills_arr = array_map('trim', explode(',', strtolower($student_skills)));
    // Fetch all active jobs
    $stmt = $conn->prepare("SELECT j.*, e.company_name, e.logo_path FROM job j INNER JOIN employer_profiles e ON j.employer_id = e.id WHERE j.status = 'active'");
    $stmt->execute();
    $all_jobs = $stmt->fetchAll();
    // Score jobs by skill match
    $job_scores = [];
    foreach ($all_jobs as $job) {
        $job_text = strtolower($job['requirements'] . ' ' . $job['description']);
        $score = 0;
        $matched_skills = [];
        foreach ($skills_arr as $skill) {
            if ($skill && strpos($job_text, $skill) !== false) {
                $score++;
                $matched_skills[] = $skill;
            }
        }
        if ($score > 0) {
            $job_scores[] = [
                'job' => $job,
                'score' => $score,
                'matched_skills' => $matched_skills
            ];
        }
    }
    // Sort jobs by score descending
    usort($job_scores, function($a, $b) { return $b['score'] <=> $a['score']; });
    // Show top 5 recommendations
    if (!empty($job_scores)) {
        echo '<div class="card mb-4"><div class="card-body">';
        echo '<h4 class="mb-3">Recommended for You</h4>';
        echo '<div class="row">';
        foreach (array_slice($job_scores, 0, 5) as $rec) {
            $job = $rec['job'];
            $matched = implode(', ', $rec['matched_skills']);
            echo '<div class="col-md-6 mb-3">';
            echo '<div class="card h-100"><div class="card-body">';
            echo '<div class="d-flex align-items-center mb-2">';
            echo '<img src="' . ($job['logo_path'] ? BASE_URL . '/' . $job['logo_path'] : BASE_URL . '/assets/images/DefaultCompanyLogo.jpg') . '" class="company-logo me-2" style="width:40px;height:40px;object-fit:cover;border-radius:8px;">';
            echo '<div><h5 class="mb-0">' . htmlspecialchars($job['title']) . '</h5>';
            echo '<div class="text-muted">' . htmlspecialchars($job['company_name']) . '</div></div></div>';
            echo '<div class="mb-2"><span class="badge bg-primary">' . htmlspecialchars($job['job_type']) . '</span> <span class="badge bg-secondary">' . htmlspecialchars($job['location']) . '</span></div>';
            echo '<p class="mb-2">' . substr(htmlspecialchars($job['description']), 0, 80) . '...</p>';
            echo '<div class="mb-2"><small class="text-success">Matches your skills: ' . htmlspecialchars($matched) . '</small></div>';
            echo '<a href="jobs/view.php?id=' . $job['id'] . '" class="btn btn-outline-primary btn-sm">View Details</a>';
            echo '</div></div></div>';
        }
        echo '</div></div></div>';
    }
}

// Helper function to format time elapsed
function time_elapsed_string($datetime) {
    $now = new DateTime;
    $ago = new DateTime($datetime);
    $diff = $now->diff($ago);

    if ($diff->d == 0) {
        if ($diff->h == 0) {
            if ($diff->i == 0) {
                return "just now";
            }
            return $diff->i . " minute" . ($diff->i > 1 ? "s" : "") . " ago";
        }
        return $diff->h . " hour" . ($diff->h > 1 ? "s" : "") . " ago";
    }
    if ($diff->d < 7) {
        return $diff->d . " day" . ($diff->d > 1 ? "s" : "") . " ago";
    }
    if ($diff->d < 30) {
        $weeks = floor($diff->d / 7);
        return $weeks . " week" . ($weeks > 1 ? "s" : "") . " ago";
    }
    if ($diff->m < 12) {
        return $diff->m . " month" . ($diff->m > 1 ? "s" : "") . " ago";
    }
    return $diff->y . " year" . ($diff->y > 1 ? "s" : "") . " ago";
}

include 'includes/footer.php';
?> 