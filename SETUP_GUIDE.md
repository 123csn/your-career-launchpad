# 🚀 Quick Setup Guide for Lecturers

## Prerequisites
- XAMPP installed and running
- PHP 8.0 or higher
- MySQL 8.0 or higher

## 📋 Step-by-Step Setup

### 1. Download and Extract
1. Download the project from GitHub
2. Extract to your XAMPP htdocs folder: `C:\xampp\htdocs\career-launchpad`

### 2. Set Up Database
1. Start XAMPP Control Panel
2. Start Apache and MySQL services
3. Open phpMyAdmin: `http://localhost/phpmyadmin`
4. Create new database: `career_launchpad`
5. Import the SQL file: `database/career_launchpad.sql`

### 3. Configure Database Connection
1. Copy `config/database.example.php` to `config/database.php`
2. Edit `config/database.php` if needed (default settings work with XAMPP)

### 4. Set File Permissions (if on Linux/Mac)
```bash
chmod 755 uploads/
chmod 755 uploads/resumes/
chmod 755 uploads/logos/
chmod 755 uploads/profile_pictures/
```

### 5. Access the Application
- Open browser and go to: `http://localhost/career-launchpad`

## 🔑 Default Login Credentials

| Role | Email | Password |
|------|-------|----------|
| Admin | admin@test.com | admin123 |
| Employer | employer@test.com | employer123 |
| Student | student@test.com | student123 |

## 🧪 Testing the Application

### Admin Features
1. Login as admin
2. View system statistics
3. Manage users and jobs
4. Generate test data

### Employer Features
1. Login as employer
2. Complete profile
3. Post new jobs
4. View applications

### Student Features
1. Login as student
2. Complete profile
3. Search and apply for jobs
4. Track applications

## 📁 Project Structure

```
career-launchpad/
├── admin/              # Admin panel
├── employer/           # Employer features
├── student/            # Student features
├── includes/           # Shared components
├── assets/             # CSS, JS, images
├── database/           # Database schema
├── config/             # Configuration files
├── uploads/            # File uploads (empty)
└── index.php           # Main entry point
```

## 🔧 Troubleshooting

### Common Issues:
1. **Database Connection Error**: Check if MySQL is running
2. **File Upload Error**: Check uploads directory permissions
3. **404 Errors**: Ensure .htaccess is present
4. **Session Issues**: Check PHP session configuration

### Debug Mode:
Set `DEBUG_MODE = true` in `config/config.php` to see error messages.

## 📞 Support

If you encounter any issues:
1. Check XAMPP error logs
2. Verify database connection
3. Ensure all files are present
4. Check file permissions

---
**Student ID**: 22019285  
**Course**: CP2 Capstone Project 