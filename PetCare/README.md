# PetCare - Pet Adoption Platform

A comprehensive pet adoption web application built with HTML, CSS, JavaScript, PHP, and MySQL.

## Features

### 🏠 Public Features
- **Homepage**: Welcome page with featured pets and platform statistics
- **About Page**: Information about the platform and how it works
- **Contact Page**: Contact form and support information
- **Pet Browsing**: Browse available pets with advanced filtering

### 👥 User Types

#### 1. **Administrators**
- **Dashboard**: System overview with key metrics and recent activities
- **User Management**: Manage all user accounts (admin, shelter, adopter)
- **Pet Management**: Oversee all pets across all shelters
- **Adoption Management**: Review and approve/reject adoption requests
- **Vaccination Tracking**: Monitor vaccination records across all pets
- **Reports & Analytics**: Comprehensive reporting and system analytics

#### 2. **Shelters**
- **Dashboard**: Shelter-specific overview with pet and adoption statistics
- **Add Pets**: Add new pets with photos and detailed information
- **View Pets**: Manage existing pet listings with status updates
- **Adoption Requests**: Review and respond to adoption requests
- **Vaccination Tracker**: Maintain vaccination records for their pets

#### 3. **Adopters**
- **Dashboard**: Personal adoption journey overview
- **Browse Pets**: Search and filter available pets
- **Pet Details**: Detailed pet information and adoption process
- **My Adoptions**: Track adoption request status
- **Care Guides**: Access pet care information and resources

## 🛠️ Technical Stack

- **Frontend**: HTML5, CSS3, JavaScript (Vanilla)
- **Backend**: PHP 7.4+
- **Database**: MySQL 5.7+
- **File Uploads**: Secure image upload with validation
- **Security**: Password hashing, input sanitization, SQL injection prevention

## 📁 Project Structure

```
PetCare/
├── 📂 config/
│   └── db.php                  # Database connection and helper functions
├── 📂 admin/                   # Admin-only pages
│   ├── dashboard.php
│   ├── manageUsers.php
│   ├── managePets.php
│   ├── manageAdoptions.php
│   ├── manageVaccinations.php
│   └── reports.php
├── 📂 shelter/                 # Shelter-only pages
│   ├── dashboard.php
│   ├── addPet.php
│   ├── viewPets.php
│   ├── adoptionRequests.php
│   └── vaccinationTracker.php
├── 📂 adopter/                 # Adopter-only pages
│   ├── dashboard.php
│   ├── browsePets.php
│   ├── petDetails.php
│   ├── myAdoptions.php
│   └── careGuides.php
├── 📂 auth/                    # Authentication
│   ├── login.php
│   ├── register.php
│   └── logout.php
├── 📂 common/                  # Shared layouts
│   ├── header.php
│   ├── footer.php
│   ├── navbar_admin.php
│   ├── navbar_shelter.php
│   └── navbar_adopter.php
├── 📂 database/
│   └── schema.sql              # MySQL database schema
├── 📂 uploads/                 # Pet photos and documents
├── index.php                   # Homepage
├── about.php                   # About page
└── contact.php                 # Contact form
```

## 🚀 Installation

### Prerequisites
- Web server (Apache/Nginx)
- PHP 7.4 or higher
- MySQL 5.7 or higher
- Web browser

### Setup Instructions

1. **Clone/Download the project**
   ```bash
   # Place the PetCare folder in your web server directory
   # For XAMPP: C:\xampp\htdocs\PetCare
   # For WAMP: C:\wamp64\www\PetCare
   # For Linux: /var/www/html/PetCare
   ```

2. **Database Setup**
   ```sql
   -- Create database
   CREATE DATABASE petcare_db;
   
   -- Import the schema
   mysql -u root -p petcare_db < database/schema.sql
   ```

3. **Configure Database Connection**
   - Edit `config/db.php`
   - Update database credentials:
     ```php
     define('DB_HOST', 'localhost');
     define('DB_NAME', 'petcare_db');
     define('DB_USER', 'your_username');
     define('DB_PASS', 'your_password');
     ```

4. **Set Permissions**
   ```bash
   # Make uploads directory writable
   chmod 755 uploads/
   chmod 644 uploads/.htaccess
   ```

5. **Access the Application**
   - Open your web browser
   - Navigate to: `http://localhost/PetCare`
   - Default admin login: `admin` / `password`

## 🔐 Default Accounts

### Administrator
- **Username**: `admin`
- **Email**: `admin@petcare.com`
- **Password**: `password`

### Sample Shelter
- **Username**: `shelter1`
- **Email**: `shelter@petcare.com`
- **Password**: `password`

### Sample Adopter
- **Username**: `adopter1`
- **Email**: `adopter@petcare.com`
- **Password**: `password`

## 🎨 Features Overview

### User Authentication
- Secure login/logout system
- User registration with validation
- Role-based access control
- Session management

### Pet Management
- Add pets with photos and detailed information
- Update pet status (available, adopted, pending, not available)
- Advanced search and filtering
- Vaccination tracking

### Adoption Process
- Submit adoption requests
- Review and approve/reject requests
- Track adoption status
- Communication between adopters and shelters

### File Uploads
- Secure image upload for pet photos
- File type validation
- Automatic file naming
- Directory protection

### Responsive Design
- Mobile-friendly interface
- Modern CSS with flexbox and grid
- Consistent styling across all pages
- User-friendly navigation

## 🔧 Configuration

### Database Settings
Edit `config/db.php` to configure:
- Database host, name, username, password
- PDO connection settings
- Helper functions for user management

### File Upload Settings
- Maximum file size: 5MB
- Allowed formats: JPG, PNG, GIF, BMP, WEBP
- Upload directory: `uploads/`
- Security: PHP files blocked, directory listing disabled

## 🛡️ Security Features

- **Password Hashing**: Uses PHP's `password_hash()` function
- **Input Sanitization**: All user inputs are sanitized
- **SQL Injection Prevention**: Prepared statements used throughout
- **File Upload Security**: Type validation and directory protection
- **Session Management**: Secure session handling
- **Access Control**: Role-based page access

## 📱 Browser Compatibility

- Chrome 60+
- Firefox 55+
- Safari 12+
- Edge 79+
- Mobile browsers (iOS Safari, Chrome Mobile)

## 🤝 Contributing

1. Fork the repository
2. Create a feature branch
3. Make your changes
4. Test thoroughly
5. Submit a pull request

## 📄 License

This project is open source and available under the MIT License.

## 🆘 Support

For support and questions:
- Email: support@petcare.com
- Phone: (555) 123-4567
- Documentation: Check the `docs/` folder

## 🔄 Updates

### Version 1.0.0
- Initial release
- Complete pet adoption platform
- Three user types (admin, shelter, adopter)
- Full CRUD operations
- File upload functionality
- Responsive design

---

**PetCare** - Connecting loving families with pets in need of homes. 🐾