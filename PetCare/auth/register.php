<?php
require_once '../config/db.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = sanitizeInput($_POST['username']);
    $email = sanitizeInput($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $user_type = sanitizeInput($_POST['user_type']);
    $first_name = sanitizeInput($_POST['first_name']);
    $last_name = sanitizeInput($_POST['last_name']);
    $phone = sanitizeInput($_POST['phone']);
    $address = sanitizeInput($_POST['address']);
    
    // Validation
    if (empty($username) || empty($email) || empty($password) || empty($user_type) || empty($first_name) || empty($last_name)) {
        $error = 'Please fill in all required fields';
    } elseif (!validateEmail($email)) {
        $error = 'Please enter a valid email address';
    } elseif ($password !== $confirm_password) {
        $error = 'Passwords do not match';
    } elseif (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters long';
    } elseif (!in_array($user_type, ['shelter', 'adopter'])) {
        $error = 'Invalid user type selected';
    } else {
        try {
            // Check if username or email already exists
            $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
            $stmt->execute([$username, $email]);
            if ($stmt->fetch()) {
                $error = 'Username or email already exists';
            } else {
                // Insert new user
                $hashed_password = hashPassword($password);
                $stmt = $pdo->prepare("INSERT INTO users (username, email, password, user_type, first_name, last_name, phone, address) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([$username, $email, $hashed_password, $user_type, $first_name, $last_name, $phone, $address]);
                
                // If shelter user, create shelter record
                if ($user_type === 'shelter') {
                    $user_id = $pdo->lastInsertId();
                    $shelter_name = sanitizeInput($_POST['shelter_name']);
                    $license_number = sanitizeInput($_POST['license_number']);
                    $description = sanitizeInput($_POST['description']);
                    $website = sanitizeInput($_POST['website']);
                    
                    $stmt = $pdo->prepare("INSERT INTO shelters (user_id, shelter_name, license_number, description, website) VALUES (?, ?, ?, ?, ?)");
                    $stmt->execute([$user_id, $shelter_name, $license_number, $description, $website]);
                }
                
                $success = 'Registration successful! You can now <a href="login.php">login</a>.';
            }
        } catch(PDOException $e) {
            $error = 'Registration failed. Please try again.';
        }
    }
}

// If already logged in, redirect
if (isLoggedIn()) {
    redirectByUserType();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - PetCare</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Arial', sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 2rem 0;
        }
        
        .register-container {
            background: white;
            padding: 2rem;
            border-radius: 10px;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.1);
            width: 100%;
            max-width: 600px;
            margin: 0 auto;
        }
        
        .register-header {
            text-align: center;
            margin-bottom: 2rem;
        }
        
        .register-header h1 {
            color: #333;
            margin-bottom: 0.5rem;
        }
        
        .register-header p {
            color: #666;
        }
        
        .form-row {
            display: flex;
            gap: 1rem;
            margin-bottom: 1.5rem;
        }
        
        .form-group {
            flex: 1;
            margin-bottom: 1.5rem;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            color: #333;
            font-weight: bold;
        }
        
        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 0.75rem;
            border: 2px solid #ddd;
            border-radius: 5px;
            font-size: 1rem;
            transition: border-color 0.3s;
        }
        
        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: #667eea;
        }
        
        .form-group textarea {
            resize: vertical;
            min-height: 80px;
        }
        
        .btn {
            width: 100%;
            padding: 0.75rem;
            background: #667eea;
            color: white;
            border: none;
            border-radius: 5px;
            font-size: 1rem;
            cursor: pointer;
            transition: background 0.3s;
        }
        
        .btn:hover {
            background: #5a6fd8;
        }
        
        .error {
            background: #f8d7da;
            color: #721c24;
            padding: 0.75rem;
            border-radius: 5px;
            margin-bottom: 1rem;
            border: 1px solid #f5c6cb;
        }
        
        .success {
            background: #d4edda;
            color: #155724;
            padding: 0.75rem;
            border-radius: 5px;
            margin-bottom: 1rem;
            border: 1px solid #c3e6cb;
        }
        
        .login-link {
            text-align: center;
            margin-top: 1.5rem;
        }
        
        .login-link a {
            color: #667eea;
            text-decoration: none;
        }
        
        .login-link a:hover {
            text-decoration: underline;
        }
        
        .home-link {
            text-align: center;
            margin-top: 1rem;
        }
        
        .home-link a {
            color: #666;
            text-decoration: none;
        }
        
        .home-link a:hover {
            text-decoration: underline;
        }
        
        .shelter-fields {
            display: none;
            border-top: 2px solid #eee;
            padding-top: 1.5rem;
            margin-top: 1.5rem;
        }
        
        .required {
            color: red;
        }
    </style>
</head>
<body>
    <div class="register-container">
        <div class="register-header">
            <h1>🐾 PetCare Registration</h1>
            <p>Join our community and help pets find loving homes!</p>
        </div>
        
        <?php if ($error): ?>
            <div class="error"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div class="success"><?php echo $success; ?></div>
        <?php endif; ?>
        
        <form method="POST" action="">
            <div class="form-row">
                <div class="form-group">
                    <label for="first_name">First Name <span class="required">*</span>:</label>
                    <input type="text" id="first_name" name="first_name" required 
                           value="<?php echo isset($_POST['first_name']) ? htmlspecialchars($_POST['first_name']) : ''; ?>">
                </div>
                
                <div class="form-group">
                    <label for="last_name">Last Name <span class="required">*</span>:</label>
                    <input type="text" id="last_name" name="last_name" required 
                           value="<?php echo isset($_POST['last_name']) ? htmlspecialchars($_POST['last_name']) : ''; ?>">
                </div>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="username">Username <span class="required">*</span>:</label>
                    <input type="text" id="username" name="username" required 
                           value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>">
                </div>
                
                <div class="form-group">
                    <label for="email">Email <span class="required">*</span>:</label>
                    <input type="email" id="email" name="email" required 
                           value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
                </div>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="password">Password <span class="required">*</span>:</label>
                    <input type="password" id="password" name="password" required>
                </div>
                
                <div class="form-group">
                    <label for="confirm_password">Confirm Password <span class="required">*</span>:</label>
                    <input type="password" id="confirm_password" name="confirm_password" required>
                </div>
            </div>
            
            <div class="form-group">
                <label for="user_type">Account Type <span class="required">*</span>:</label>
                <select id="user_type" name="user_type" required onchange="toggleShelterFields()">
                    <option value="">Select account type</option>
                    <option value="adopter" <?php echo (isset($_POST['user_type']) && $_POST['user_type'] === 'adopter') ? 'selected' : ''; ?>>Pet Adopter</option>
                    <option value="shelter" <?php echo (isset($_POST['user_type']) && $_POST['user_type'] === 'shelter') ? 'selected' : ''; ?>>Pet Shelter</option>
                </select>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="phone">Phone:</label>
                    <input type="tel" id="phone" name="phone" 
                           value="<?php echo isset($_POST['phone']) ? htmlspecialchars($_POST['phone']) : ''; ?>">
                </div>
                
                <div class="form-group">
                    <label for="address">Address:</label>
                    <input type="text" id="address" name="address" 
                           value="<?php echo isset($_POST['address']) ? htmlspecialchars($_POST['address']) : ''; ?>">
                </div>
            </div>
            
            <div id="shelter-fields" class="shelter-fields">
                <h3>Shelter Information</h3>
                <div class="form-group">
                    <label for="shelter_name">Shelter Name:</label>
                    <input type="text" id="shelter_name" name="shelter_name" 
                           value="<?php echo isset($_POST['shelter_name']) ? htmlspecialchars($_POST['shelter_name']) : ''; ?>">
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="license_number">License Number:</label>
                        <input type="text" id="license_number" name="license_number" 
                               value="<?php echo isset($_POST['license_number']) ? htmlspecialchars($_POST['license_number']) : ''; ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="website">Website:</label>
                        <input type="url" id="website" name="website" 
                               value="<?php echo isset($_POST['website']) ? htmlspecialchars($_POST['website']) : ''; ?>">
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="description">Shelter Description:</label>
                    <textarea id="description" name="description" placeholder="Tell us about your shelter..."><?php echo isset($_POST['description']) ? htmlspecialchars($_POST['description']) : ''; ?></textarea>
                </div>
            </div>
            
            <button type="submit" class="btn">Register</button>
        </form>
        
        <div class="login-link">
            <p>Already have an account? <a href="login.php">Login here</a></p>
        </div>
        
        <div class="home-link">
            <a href="../index.php">← Back to Home</a>
        </div>
    </div>
    
    <script>
        function toggleShelterFields() {
            const userType = document.getElementById('user_type').value;
            const shelterFields = document.getElementById('shelter-fields');
            
            if (userType === 'shelter') {
                shelterFields.style.display = 'block';
            } else {
                shelterFields.style.display = 'none';
            }
        }
        
        // Show shelter fields if shelter was selected and form was submitted with errors
        document.addEventListener('DOMContentLoaded', function() {
            const userType = document.getElementById('user_type').value;
            if (userType === 'shelter') {
                document.getElementById('shelter-fields').style.display = 'block';
            }
        });
    </script>
</body>
</html>