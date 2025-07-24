# 🚀 Career Launchpad - Job Portal

A comprehensive job portal web application connecting students with employers. Built with PHP, MySQL, and Bootstrap 5, this project demonstrates full-stack web development skills and serves as a capstone project.

[![PHP](https://img.shields.io/badge/PHP-8.0+-777BB4?style=for-the-badge&logo=php&logoColor=white)](https://php.net)
[![MySQL](https://img.shields.io/badge/MySQL-8.0+-4479A1?style=for-the-badge&logo=mysql&logoColor=white)](https://mysql.com)
[![Bootstrap](https://img.shields.io/badge/Bootstrap-5.0+-7952B3?style=for-the-badge&logo=bootstrap&logoColor=white)](https://getbootstrap.com)
[![Apache](https://img.shields.io/badge/Apache-2.4+-D22128?style=for-the-badge&logo=apache&logoColor=white)](https://httpd.apache.org)

## 🌟 Live Demo

🔗 **Demo URL**: [Coming Soon]

## 📸 Screenshots

<details>
<summary>Click to view screenshots</summary>

### Homepage
![Homepage](screenshots/homepage.png)

### Job Search
![Job Search](screenshots/job-search.png)

### Admin Dashboard
![Admin Dashboard](screenshots/admin-dashboard.png)

### User Profile
![User Profile](screenshots/user-profile.png)

</details>

## 🎯 Features

### For Students
- User registration and login
- Profile creation and management
- Browse and apply for jobs
- Track application status

### For Employers
- Company profile management
- Post and manage job listings
- View and manage applicants
- Dashboard with application statistics

### For Admin
- User management
- Job listing moderation
- System statistics and monitoring
- Content management

## ⚙️ Tech Stack
- Frontend: HTML5, CSS3, JavaScript, Bootstrap 5
- Backend: PHP 8.x
- Database: MySQL
- Server: XAMPP/Apache

## 🚀 Quick Start

### Prerequisites
- [XAMPP](https://www.apachefriends.org/) or similar LAMP stack
- PHP 8.0 or higher
- MySQL 8.0 or higher
- Apache web server

### Installation Steps

1. **Clone the repository**
   ```bash
   git clone https://github.com/yourusername/career-launchpad.git
   cd career-launchpad
   ```

2. **Set up the database**
   - Start XAMPP and ensure Apache & MySQL are running
   - Open phpMyAdmin: `http://localhost/phpmyadmin`
   - Create a new database named `career_launchpad`
   - Import the SQL file: `database/career_launchpad.sql`

3. **Configure the application**
   ```bash
   # Copy the example configuration
   cp config/database.example.php config/database.php
   
   # Edit the configuration file with your database credentials
   nano config/database.php
   ```

4. **Set file permissions** (Linux/Mac only)
   ```bash
   chmod 755 uploads/
   chmod 644 config/database.php
   ```

5. **Access the application**
   ```
   http://localhost/career-launchpad
   ```

### Default Login Credentials

| Role | Email | Password |
|------|-------|----------|
| Admin | admin@test.com | admin123 |
| Employer | employer@test.com | employer123 |
| Student | student@test.com | student123 |

## 🗄️ Project Structure
```
career-launchpad/
├── assets/           # Static files (CSS, JS, images)
├── config/           # Configuration files
├── controllers/      # MVC Controllers
├── database/        # Database schema and migrations
├── includes/        # Reusable PHP components
├── models/          # MVC Models
├── uploads/         # User uploaded files
└── views/           # MVC Views
```

## 🔐 Security Features
- Password hashing using bcrypt
- SQL injection prevention using prepared statements
- XSS protection
- CSRF token implementation
- Input validation and sanitization

## 👥 Default Users
After database setup, you can use these test accounts:

1. Admin:
   - Email: admin@test.com
   - Password: admin123

2. Employer:
   - Email: employer@test.com
   - Password: employer123

3. Student:
   - Email: student@test.com
   - Password: student123

## 🛠️ Development

### Project Structure
```
career-launchpad/
├── admin/              # Admin panel files
├── assets/             # Static files (CSS, JS, images)
├── config/             # Configuration files
├── controllers/        # MVC Controllers
├── database/          # Database schema and migrations
├── employer/          # Employer-specific pages
├── includes/          # Reusable PHP components
├── jobs/              # Job-related pages
├── models/            # MVC Models
├── student/           # Student-specific pages
├── tests/             # Test files
├── uploads/           # User uploaded files
├── views/             # MVC Views
├── .htaccess          # Apache configuration
├── .gitignore         # Git ignore rules
├── index.php          # Main entry point
└── README.md          # Project documentation
```

### Key Features Implemented
- ✅ User authentication and authorization
- ✅ Role-based access control (Student, Employer, Admin)
- ✅ Job posting and management
- ✅ Job search and filtering
- ✅ Application system
- ✅ Profile management
- ✅ Admin dashboard with statistics
- ✅ Feedback system with ratings
- ✅ Responsive design with Bootstrap 5
- ✅ File upload functionality
- ✅ Security features (SQL injection prevention, XSS protection)

## 🚀 Deployment

For deployment instructions, see [DEPLOYMENT.md](DEPLOYMENT.md)

## 🤝 Contributing

1. Fork the repository
2. Create a feature branch (`git checkout -b feature/AmazingFeature`)
3. Commit your changes (`git commit -m 'Add some AmazingFeature'`)
4. Push to the branch (`git push origin feature/AmazingFeature`)
5. Open a Pull Request

## 📝 License

This project is part of a university capstone project. All rights reserved.

## 📞 Support

If you have any questions or need help:
- Create an issue on GitHub
- Contact the project maintainer

## 🙏 Acknowledgments

- Bootstrap 5 for the responsive UI framework
- Font Awesome for icons
- XAMPP for the development environment
- All contributors and testers
- My lecturer for guidance throughout this project
- Fellow students for testing and feedback

## 📝 Personal Notes

This project was developed as part of my CP2 Capstone Project. I learned a lot about:
- Full-stack web development with PHP and MySQL
- User authentication and security best practices
- Database design and optimization
- Responsive web design with Bootstrap
- Project management and documentation

The development process took approximately [X] weeks, with challenges including:
- Implementing secure user authentication
- Designing an intuitive user interface
- Optimizing database queries for performance
- Ensuring cross-browser compatibility

I'm particularly proud of the job recommendation system and the admin dashboard features.

---
⭐ **Star this repository if you find it helpful!** 