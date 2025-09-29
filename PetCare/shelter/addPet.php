<?php
require_once '../config/db.php';
requireUserType('shelter');

$page_title = 'Add New Pet';
$message = '';
$message_type = '';

// Get shelter information
try {
    $stmt = $pdo->prepare("
        SELECT s.id as shelter_id, s.shelter_name
        FROM shelters s
        JOIN users u ON s.user_id = u.id
        WHERE u.id = ?
    ");
    $stmt->execute([$_SESSION['user_id']]);
    $shelter = $stmt->fetch();
    
    if (!$shelter) {
        header('Location: /PetCare/index.php');
        exit();
    }
    
    $shelter_id = $shelter['shelter_id'];
    
} catch(PDOException $e) {
    header('Location: /PetCare/index.php');
    exit();
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = sanitizeInput($_POST['name']);
    $species = sanitizeInput($_POST['species']);
    $breed = sanitizeInput($_POST['breed']);
    $age = (int)$_POST['age'];
    $gender = sanitizeInput($_POST['gender']);
    $size = sanitizeInput($_POST['size']);
    $color = sanitizeInput($_POST['color']);
    $description = sanitizeInput($_POST['description']);
    $health_status = sanitizeInput($_POST['health_status']);
    $vaccination_status = sanitizeInput($_POST['vaccination_status']);
    $adoption_fee = (float)$_POST['adoption_fee'];
    
    // Validation
    if (empty($name) || empty($species) || empty($gender) || empty($size)) {
        $message = 'Please fill in all required fields.';
        $message_type = 'error';
    } elseif ($age < 0 || $age > 30) {
        $message = 'Please enter a valid age (0-30 years).';
        $message_type = 'error';
    } elseif ($adoption_fee < 0) {
        $message = 'Adoption fee cannot be negative.';
        $message_type = 'error';
    } else {
        try {
            // Handle file upload
            $photo_path = '';
            if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
                $upload_dir = '../uploads/';
                if (!is_dir($upload_dir)) {
                    mkdir($upload_dir, 0755, true);
                }
                
                $file_extension = strtolower(pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION));
                $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif'];
                
                if (in_array($file_extension, $allowed_extensions)) {
                    $new_filename = uniqid() . '_' . time() . '.' . $file_extension;
                    $upload_path = $upload_dir . $new_filename;
                    
                    if (move_uploaded_file($_FILES['photo']['tmp_name'], $upload_path)) {
                        $photo_path = $new_filename;
                    }
                }
            }
            
            // Insert pet
            $stmt = $pdo->prepare("
                INSERT INTO pets (shelter_id, name, species, breed, age, gender, size, color, 
                                description, health_status, vaccination_status, adoption_fee, photo_path) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $shelter_id, $name, $species, $breed, $age, $gender, $size, $color,
                $description, $health_status, $vaccination_status, $adoption_fee, $photo_path
            ]);
            
            $message = 'Pet added successfully!';
            $message_type = 'success';
            
            // Clear form data
            $name = $species = $breed = $age = $gender = $size = $color = $description = $health_status = $vaccination_status = $adoption_fee = '';
            
        } catch(PDOException $e) {
            $message = 'Error adding pet: ' . $e->getMessage();
            $message_type = 'error';
        }
    }
}
?>

<?php include '../common/header.php'; ?>
<?php include '../common/navbar_shelter.php'; ?>

<div class="main-content">
    <h1 style="color: #333; margin-bottom: 2rem;">🐾 Add New Pet</h1>
    
    <?php if ($message): ?>
        <div class="alert alert-<?php echo $message_type; ?>">
            <?php echo $message; ?>
        </div>
    <?php endif; ?>
    
    <div class="card">
        <form method="POST" action="" enctype="multipart/form-data">
            <div class="form-row">
                <div class="form-group">
                    <label for="name">Pet Name *</label>
                    <input type="text" id="name" name="name" required 
                           value="<?php echo isset($name) ? htmlspecialchars($name) : ''; ?>">
                </div>
                
                <div class="form-group">
                    <label for="species">Species *</label>
                    <select id="species" name="species" required>
                        <option value="">Select species</option>
                        <option value="dog" <?php echo (isset($species) && $species === 'dog') ? 'selected' : ''; ?>>Dog</option>
                        <option value="cat" <?php echo (isset($species) && $species === 'cat') ? 'selected' : ''; ?>>Cat</option>
                        <option value="bird" <?php echo (isset($species) && $species === 'bird') ? 'selected' : ''; ?>>Bird</option>
                        <option value="rabbit" <?php echo (isset($species) && $species === 'rabbit') ? 'selected' : ''; ?>>Rabbit</option>
                        <option value="other" <?php echo (isset($species) && $species === 'other') ? 'selected' : ''; ?>>Other</option>
                    </select>
                </div>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="breed">Breed</label>
                    <input type="text" id="breed" name="breed" 
                           value="<?php echo isset($breed) ? htmlspecialchars($breed) : ''; ?>">
                </div>
                
                <div class="form-group">
                    <label for="age">Age (years) *</label>
                    <input type="number" id="age" name="age" min="0" max="30" required 
                           value="<?php echo isset($age) ? $age : ''; ?>">
                </div>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="gender">Gender *</label>
                    <select id="gender" name="gender" required>
                        <option value="">Select gender</option>
                        <option value="male" <?php echo (isset($gender) && $gender === 'male') ? 'selected' : ''; ?>>Male</option>
                        <option value="female" <?php echo (isset($gender) && $gender === 'female') ? 'selected' : ''; ?>>Female</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="size">Size *</label>
                    <select id="size" name="size" required>
                        <option value="">Select size</option>
                        <option value="small" <?php echo (isset($size) && $size === 'small') ? 'selected' : ''; ?>>Small</option>
                        <option value="medium" <?php echo (isset($size) && $size === 'medium') ? 'selected' : ''; ?>>Medium</option>
                        <option value="large" <?php echo (isset($size) && $size === 'large') ? 'selected' : ''; ?>>Large</option>
                    </select>
                </div>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="color">Color</label>
                    <input type="text" id="color" name="color" 
                           value="<?php echo isset($color) ? htmlspecialchars($color) : ''; ?>">
                </div>
                
                <div class="form-group">
                    <label for="adoption_fee">Adoption Fee ($)</label>
                    <input type="number" id="adoption_fee" name="adoption_fee" min="0" step="0.01" 
                           value="<?php echo isset($adoption_fee) ? $adoption_fee : ''; ?>">
                </div>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="health_status">Health Status</label>
                    <input type="text" id="health_status" name="health_status" 
                           value="<?php echo isset($health_status) ? htmlspecialchars($health_status) : ''; ?>">
                </div>
                
                <div class="form-group">
                    <label for="vaccination_status">Vaccination Status</label>
                    <select id="vaccination_status" name="vaccination_status">
                        <option value="not_vaccinated" <?php echo (isset($vaccination_status) && $vaccination_status === 'not_vaccinated') ? 'selected' : ''; ?>>Not Vaccinated</option>
                        <option value="partial" <?php echo (isset($vaccination_status) && $vaccination_status === 'partial') ? 'selected' : ''; ?>>Partially Vaccinated</option>
                        <option value="up_to_date" <?php echo (isset($vaccination_status) && $vaccination_status === 'up_to_date') ? 'selected' : ''; ?>>Up to Date</option>
                    </select>
                </div>
            </div>
            
            <div class="form-group">
                <label for="description">Description</label>
                <textarea id="description" name="description" rows="4" 
                          placeholder="Describe the pet's personality, behavior, special needs, etc..."><?php echo isset($description) ? htmlspecialchars($description) : ''; ?></textarea>
            </div>
            
            <div class="form-group">
                <label for="photo">Pet Photo</label>
                <input type="file" id="photo" name="photo" accept="image/*" onchange="previewImage(this, 'photo-preview')">
                <small style="color: #666;">Accepted formats: JPG, PNG, GIF. Maximum size: 5MB</small>
                
                <div id="photo-preview" style="margin-top: 1rem; display: none;">
                    <img id="preview-img" src="" alt="Preview" style="max-width: 200px; max-height: 200px; border-radius: 5px; border: 2px solid #ddd;">
                </div>
            </div>
            
            <div style="display: flex; gap: 1rem; justify-content: flex-end;">
                <a href="dashboard.php" class="btn btn-secondary">Cancel</a>
                <button type="submit" class="btn">Add Pet</button>
            </div>
        </form>
    </div>
    
    <!-- Help Section -->
    <div class="card" style="margin-top: 2rem;">
        <h3 style="color: #333; margin-bottom: 1rem;">💡 Tips for Adding Pets</h3>
        <ul style="color: #666; line-height: 1.8;">
            <li><strong>Clear Photos:</strong> Take clear, well-lit photos that show the pet's personality</li>
            <li><strong>Detailed Description:</strong> Include information about the pet's temperament, energy level, and any special needs</li>
            <li><strong>Accurate Information:</strong> Provide accurate age, breed, and health information</li>
            <li><strong>Honest Assessment:</strong> Be honest about any behavioral or health issues</li>
            <li><strong>Regular Updates:</strong> Keep pet information and photos updated regularly</li>
        </ul>
    </div>
</div>

<script>
function previewImage(input, previewId) {
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = function(e) {
            const preview = document.getElementById(previewId);
            const img = document.getElementById('preview-img');
            if (preview && img) {
                img.src = e.target.result;
                preview.style.display = 'block';
            }
        };
        reader.readAsDataURL(input.files[0]);
    }
}

// Form validation
document.addEventListener('DOMContentLoaded', function() {
    const form = document.querySelector('form');
    const ageInput = document.getElementById('age');
    const feeInput = document.getElementById('adoption_fee');
    
    // Age validation
    ageInput.addEventListener('input', function() {
        if (this.value < 0 || this.value > 30) {
            this.setCustomValidity('Age must be between 0 and 30 years');
        } else {
            this.setCustomValidity('');
        }
    });
    
    // Fee validation
    feeInput.addEventListener('input', function() {
        if (this.value < 0) {
            this.setCustomValidity('Adoption fee cannot be negative');
        } else {
            this.setCustomValidity('');
        }
    });
    
    // Form submission validation
    form.addEventListener('submit', function(e) {
        if (!validateForm('pet-form')) {
            e.preventDefault();
        }
    });
});
</script>

<?php include '../common/footer.php'; ?>