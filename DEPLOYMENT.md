# Deployment Guide

This guide will help you deploy the Career Launchpad job portal to various hosting platforms.

## 🚀 Quick Deployment Options

### 1. Local Development (XAMPP)
- Follow the installation instructions in README.md
- Perfect for development and testing

### 2. Shared Hosting (cPanel, etc.)
- Upload files via FTP/cPanel File Manager
- Create MySQL database
- Import database schema
- Update database configuration

### 3. VPS/Dedicated Server
- Install LAMP stack (Linux, Apache, MySQL, PHP)
- Configure virtual host
- Set up SSL certificate
- Configure email settings

## 📋 Pre-Deployment Checklist

- [ ] Update database configuration
- [ ] Set DEBUG_MODE to false
- [ ] Configure email settings
- [ ] Set up SSL certificate
- [ ] Configure file permissions
- [ ] Test all features
- [ ] Backup database

## 🔧 Configuration for Production

### Database Configuration
```php
// config/database.php
define('DB_HOST', 'your-db-host');
define('DB_NAME', 'your-db-name');
define('DB_USER', 'your-db-user');
define('DB_PASS', 'your-db-password');
define('BASE_URL', 'https://yourdomain.com');
define('DEBUG_MODE', false);
```

### File Permissions
```bash
# Set proper permissions
chmod 755 uploads/
chmod 644 config/database.php
chmod 644 feedbacks.txt
```

### Apache Configuration (.htaccess)
```apache
RewriteEngine On
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule ^(.*)$ index.php?url=$1 [QSA,L]

# Security headers
Header always set X-Content-Type-Options nosniff
Header always set X-Frame-Options DENY
Header always set X-XSS-Protection "1; mode=block"
```

## 🌐 Popular Hosting Platforms

### 1. Heroku
- Use ClearDB for MySQL
- Configure buildpacks
- Set environment variables

### 2. DigitalOcean
- Deploy LAMP stack droplet
- Use managed MySQL database
- Configure firewall

### 3. AWS
- Use EC2 for web server
- RDS for MySQL database
- S3 for file storage
- CloudFront for CDN

### 4. Shared Hosting (HostGator, Bluehost, etc.)
- Upload via FTP
- Create MySQL database
- Import SQL file
- Update configuration

## 📧 Email Configuration

For production, configure SMTP settings:

```php
// In config/database.php
define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_PORT', 587);
define('SMTP_USER', 'your-email@gmail.com');
define('SMTP_PASS', 'your-app-password');
define('SMTP_SECURE', 'tls');
```

## 🔒 Security Considerations

1. **SSL Certificate**: Always use HTTPS in production
2. **Database Security**: Use strong passwords
3. **File Uploads**: Validate file types and sizes
4. **Session Security**: Use secure session settings
5. **Error Handling**: Don't display errors in production

## 📊 Performance Optimization

1. **Database Indexing**: Add indexes to frequently queried columns
2. **Caching**: Implement caching for job listings
3. **Image Optimization**: Compress uploaded images
4. **CDN**: Use CDN for static assets

## 🐛 Troubleshooting

### Common Issues:
1. **Database Connection**: Check credentials and host
2. **File Uploads**: Check directory permissions
3. **Email**: Verify SMTP settings
4. **404 Errors**: Check .htaccess configuration

### Debug Mode:
Set `DEBUG_MODE = true` temporarily to see error messages.

## 📞 Support

For deployment issues, check:
1. Hosting provider documentation
2. PHP version compatibility
3. MySQL version requirements
4. Server error logs 