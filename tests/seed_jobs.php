<?php
require_once '../config/config.php';
require_once '../config/database.php';

$db = new Database();
$conn = $db->getConnection();

$job_titles = [
    'Software Engineer', 'Marketing Intern', 'Data Analyst', 'Graphic Designer', 'HR Assistant',
    'Customer Service Rep', 'Business Analyst', 'Content Writer', 'Sales Executive', 'IT Support',
    'Finance Intern', 'Operations Manager', 'UX Designer', 'Project Coordinator', 'Mobile App Developer',
    'Legal Assistant', 'Digital Marketing Specialist', 'QA Tester', 'Administrative Assistant', 'Research Assistant'
];
$job_types = ['full-time', 'part-time', 'internship', 'contract'];
$locations = ['Kuala Lumpur', 'Petaling Jaya', 'Remote', 'Penang', 'Cyberjaya', 'Shah Alam', 'Subang Jaya', 'Sunway'];
$requirements = [
    'PHP, JavaScript, MySQL', 'Communication, Social Media', 'Excel, SQL, Python', 'Photoshop, Illustrator',
    'Organization, MS Office', 'Negotiation, CRM', 'Writing, SEO', 'Windows, Networking',
    'Accounting, Excel', 'Leadership, Organization', 'Figma, UX Research', 'Planning, Communication',
    'Flutter, Android, iOS', 'Legal Research, MS Office', 'Google Ads, Facebook Ads', 'Testing, Selenium',
    'Research, Data Analysis'
];
$salary_ranges = ['RM1000 - RM1500', 'RM2000 - RM3000', 'RM2500 - RM3500', 'RM3000 - RM4000', 'RM3500 - RM4500', 'RM3500 - RM5000', 'RM4000 - RM6000', 'RM5000 - RM7000', 'RM6000 - RM8000'];

for ($i = 0; $i < 20; $i++) {
    $title = $job_titles[array_rand($job_titles)];
    $description = 'This is a test job for ' . $title . '. Responsibilities include ...';
    $req = $requirements[array_rand($requirements)];
    $location = $locations[array_rand($locations)];
    $job_type = $job_types[array_rand($job_types)];
    $salary = $salary_ranges[array_rand($salary_ranges)];
    $status = 'Open';
    $stmt = $conn->prepare("INSERT INTO job (employer_id, title, description, requirements, location, job_type, salary_range, status) VALUES (1, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute([$title, $description, $req, $location, $job_type, $salary, $status]);
}
echo "20 test jobs inserted!\n"; 