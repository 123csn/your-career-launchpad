<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'config/config.php';
require_once 'config/database.php';

// Get latest job
$db = new Database();
$conn = $db->getConnection();

$query = "SELECT j.*, e.company_name, e.logo_path 
          FROM job j 
          INNER JOIN employer_profiles e ON j.employer_id = e.id 
          WHERE j.status = 'active' 
          ORDER BY j.created_at DESC 
          LIMIT 6";
$stmt = $conn->prepare($query);
$stmt->execute();
$latest_jobs = $stmt->fetchAll();

include 'includes/header.php';
?>

<style>
    /* Theme Colors (example: dark blue and gold to match a typical modern logo) */
    body {
        font-family: 'Montserrat', Arial, sans-serif;
        background-color: #f7f8fa;
        color: #222;
    }
    .bg-primary {
        background: linear-gradient(90deg, #1a237e 0%, #3949ab 100%) !important;
    }
    .btn-primary, .bg-primary {
        border: none;
        background-color: #3949ab !important;
    }
    .btn-primary {
        background: linear-gradient(90deg, #3949ab 0%, #ffd600 100%) !important;
        color: #222 !important;
        font-weight: 600;
        letter-spacing: 1px;
        border-radius: 30px;
        box-shadow: 0 4px 16px rgba(57,73,171,0.08);
        transition: background 0.3s;
    }
    .btn-primary:hover {
        background: linear-gradient(90deg, #ffd600 0%, #3949ab 100%) !important;
        color: #1a237e !important;
    }
    .display-4, h1.hero-title, .hero-title {
        font-family: 'Pacifico', cursive;
        font-size: 4rem;
        font-weight: 400;
        letter-spacing: 2px;
        color: #ffd600;
        text-shadow: 1px 2px 8px rgba(26,35,126,0.08);
    }
    h1, h2, h3, h4, h5, h6 {
        font-family: 'Montserrat', Arial, sans-serif;
        font-weight: 700;
        letter-spacing: 1px;
    }
    .hero .display-4 {
        font-size: 3rem;
        color: #ffd600;
        text-shadow: 1px 2px 8px rgba(26,35,126,0.08);
    }
    .hero .lead {
        font-size: 1.3rem;
        color: #fffde7;
        font-weight: 500;
    }
    .card-title {
        font-size: 1.2rem;
        font-weight: 600;
        color: #3949ab;
    }
    .company-name {
        font-size: 1rem;
        color: #757575;
        font-weight: 500;
    }
    .btn-outline-primary {
        border-radius: 30px;
        font-weight: 600;
        letter-spacing: 1px;
        color: #3949ab;
        border-color: #3949ab;
    }
    .btn-outline-primary:hover {
        background: #ffd600;
        color: #1a237e;
        border-color: #ffd600;
    }
    /* Make the logo smaller and round */
    .hero-logo {
        max-width: 110px;
        max-height: 110px;
        border-radius: 20px;
        box-shadow: 0 2px 12px rgba(26,35,126,0.08);
        margin-top: 1rem;
        margin-bottom: 1rem;
    }
    .hero {
        padding: 2.5rem 0 2rem 0;
        border-radius: 0 0 24px 24px;
    }
    .hero > .container {
        max-width: 1200px;
        margin: 0 auto;
        padding-left: 2rem;
        padding-right: 2rem;
    }
    .hero .row {
        display: flex;
        align-items: center;
        min-height: 340px;
    }
    .hero-logo-col {
        display: flex;
        align-items: center;
        justify-content: center;
        height: 100%;
        min-height: 180px;
    }
    .hero-logo {
        max-width: 120px;
        max-height: 120px;
        border-radius: 20px;
        box-shadow: 0 2px 12px rgba(26,35,126,0.08);
        margin-top: 1rem;
        margin-bottom: 1rem;
    }
    .hero-content-col {
        display: flex;
        flex-direction: column;
        justify-content: center;
        align-items: flex-start;
        height: 100%;
        padding-left: 3rem;
    }
    .display-4, h1.hero-title, .hero-title {
        font-family: 'Pacifico', cursive;
        font-size: 4rem;
        font-weight: 400;
        letter-spacing: 2px;
        color: #ffd600;
        text-shadow: 1px 2px 8px rgba(26,35,126,0.08);
        margin-bottom: 0.5rem;
    }
    .hero-content-col .lead {
        color: #fffde7;
        font-size: 1.3rem;
        margin-bottom: 2rem;
    }
    h1, h2, h3, h4, h5, h6 {
        font-family: 'Montserrat', Arial, sans-serif;
        font-weight: 700;
        letter-spacing: 1px;
    }
    .hero-search-form {
        display: flex;
        justify-content: flex-start;
        align-items: stretch;
        gap: 1.2rem;
        margin-top: 0.5rem;
        margin-bottom: 0.5rem;
        max-width: 600px;
        width: 100%;
        background: #fff;
        border-radius: 16px;
        box-shadow: 0 4px 24px rgba(26,35,126,0.08);
        padding: 0.5rem 0.5rem 0.5rem 1rem;
    }
    .hero-search-form input {
        font-size: 1.2rem;
        padding: 0.9rem 1.2rem;
        border-radius: 10px;
        border: none;
        background: transparent;
        outline: none;
        flex: 1 1 0;
        min-width: 0;
    }
    .hero-search-form input:focus {
        background: #f7f8fa;
    }
    .hero-search-form button {
        background: #ffd600;
        color: #222;
        font-weight: 600;
        border: none;
        border-radius: 10px;
        font-size: 1.2rem;
        padding: 0.9rem 2rem;
        min-width: 110px;
        transition: background 0.2s, color 0.2s;
        box-shadow: 0 2px 8px rgba(57,73,171,0.08);
    }
    .hero-search-form button:hover {
        background: #3949ab;
        color: #fff;
    }
    @media (max-width: 1199.98px) {
        .hero > .container {
            max-width: 1000px;
        }
        .display-4, h1.hero-title, .hero-title {
            font-size: 3rem;
        }
        .hero-content-col {
            padding-left: 1.5rem;
        }
    }
    @media (max-width: 991.98px) {
        .display-4, h1.hero-title, .hero-title {
            font-size: 2.2rem;
        }
        .hero-logo {
            max-width: 60px;
            max-height: 60px;
        }
        .hero {
            padding: 1.5rem 0 1rem 0;
        }
        .hero .row {
            flex-direction: column;
            min-height: unset;
        }
        .hero-logo-col, .hero-content-col {
            width: 100%;
            justify-content: center;
            align-items: center;
            text-align: center;
        }
        .hero-content-col {
            align-items: center;
            padding-left: 0;
        }
        .hero-search-form {
            flex-direction: column;
            gap: 0.5rem;
            width: 100%;
            align-items: stretch;
            max-width: 100%;
            padding: 0.5rem 0.5rem;
        }
        .hero-search-form input,
        .hero-search-form button {
            width: 100%;
            font-size: 1rem;
        }
    }
    @media (max-width: 767.98px) {
        .display-4, h1.hero-title, .hero-title {
            font-size: 1.5rem;
        }
        .hero-logo {
            max-width: 48px;
            max-height: 48px;
        }
        .hero {
            padding: 1rem 0 0.5rem 0;
        }
    }
    /* Stack search fields vertically on small screens */
    @media (max-width: 991.98px) {
        .job-search-form .row.g-2 {
            flex-direction: column;
        }
        .job-search-form .col-md-5, .job-search-form .col-md-2 {
            max-width: 100%;
            flex: 0 0 100%;
        }
        .job-search-form button {
            margin-top: 0.5rem;
        }
    }
    /* Ensure View All Jobs button is always visible and centered */
    .btn.btn-primary.btn-lg {
        display: inline-block;
        margin: 1.5rem auto 0 auto;
        font-size: 1.1rem;
        padding: 0.75rem 2.5rem;
        border-radius: 30px;
        box-shadow: 0 2px 8px rgba(57,73,171,0.08);
    }
    .text-center.mt-4 {
        text-align: center !important;
        width: 100%;
    }
</style>

<!-- Hero Section -->
<section class="hero bg-primary text-white py-5 mb-5">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-md-4 hero-logo-col">
                <img src="assets/images/Logo.png" alt="Job Search" class="img-fluid hero-logo mb-3">
            </div>
            <div class="col-md-8 hero-content-col">
                <h1 class="display-4 hero-title">Find Your Dream Job</h1>
                <p class="lead">Connect with top employers and launch your career today.</p>
                <form action="job.php" method="GET" class="hero-search-form">
                    <input type="text" name="keyword" placeholder="Job title/keyword">
                    <input type="text" name="location" placeholder="Location">
                    <button type="submit">Search</button>
                </form>
            </div>
        </div>
    </div>
</section>

<!-- Latest Jobs Section -->
<section class="latest-jobs mb-5">
    <div class="container">
        <h2 class="mb-4">Latest Job Opportunities</h2>
        <div class="row">
            <?php foreach ($latest_jobs as $job): ?>
            <div class="col-md-4 mb-4">
                <div class="card h-100">
                    <div class="card-body">
                        <div class="d-flex align-items-center mb-3">
                            <img src="<?php echo $job['logo_path'] ? BASE_URL . '/' . $job['logo_path'] : BASE_URL . '/assets/images/DefaultCompanyLogo.jpg'; ?>" 
                                 alt="<?php echo htmlspecialchars($job['company_name']); ?>" 
                                 class="company-logo me-3">
                            <h5 class="card-title mb-0"><?php echo htmlspecialchars($job['title']); ?></h5>
                        </div>
                        <h6 class="company-name text-muted"><?php echo htmlspecialchars($job['company_name']); ?></h6>
                        <div class="job-meta my-3">
                            <span class="badge bg-primary"><?php echo htmlspecialchars($job['job_type']); ?></span>
                            <span class="badge bg-secondary"><?php echo htmlspecialchars($job['location']); ?></span>
                        </div>
                        <p class="card-text"><?php echo substr(htmlspecialchars($job['description']), 0, 100) . '...'; ?></p>
                    </div>
                    <div class="card-footer bg-white border-top-0">
                        <a href="jobs/view.php?id=<?php echo $job['id']; ?>" class="btn btn-outline-primary w-100">View Details</a>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <div class="text-center mt-4">
            <a href="job.php" class="btn btn-primary btn-lg">View All Jobs</a>
        </div>
    </div>
</section>

<!-- Features Section -->
<section class="features bg-light py-5">
    <div class="container">
        <h2 class="text-center mb-5">Why Choose Us</h2>
        <div class="row g-4">
            <div class="col-md-4">
                <div class="text-center">
                    <i class="fas fa-search fa-3x text-primary mb-3"></i>
                    <h4>Easy Job Search</h4>
                    <p>Find relevant opportunities quickly with our smart search filters.</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="text-center">
                    <i class="fas fa-building fa-3x text-primary mb-3"></i>
                    <h4>Top Employers</h4>
                    <p>Connect with leading companies looking for fresh talent.</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="text-center">
                    <i class="fas fa-rocket fa-3x text-primary mb-3"></i>
                    <h4>Career Growth</h4>
                    <p>Launch and accelerate your career with the right opportunities.</p>
                </div>
            </div>
        </div>
    </div>
</section>

<?php include 'includes/footer.php'; ?> 

<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@600;700&family=Nunito:wght@400;600&display=swap" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Great+Vibes&family=Playfair+Display:wght@400;600;700&display=swap" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Pacifico&family=Montserrat:wght@400;600;700&display=swap" rel="stylesheet"> 