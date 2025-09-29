<?php
require_once '../config/db.php';
requireUserType('adopter');

$page_title = 'Pet Details';

// Get pet ID from URL
$pet_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$pet_id) {
    header('Location: browsePets.php');
    exit();
}

// Get pet details
try {
    $stmt = $pdo->prepare("
        SELECT p.*, s.shelter_name, s.license_number, s.description as shelter_description,
               u.first_name as shelter_contact_first, u.last_name as shelter_contact_last,
               u.email as shelter_email, u.phone as shelter_phone, u.address as shelter_address
        FROM pets p
        JOIN shelters s ON p.shelter_id = s.id
        JOIN users u ON s.user_id = u.id
        WHERE p.id = ? AND p.status = 'available'
    ");
    $stmt->execute([$pet_id]);
    $pet = $stmt->fetch();
    
    if (!$pet) {
        header('Location: browsePets.php');
        exit();
    }
    
    // Get vaccination records
    $stmt = $pdo->prepare("
        SELECT * FROM vaccinations 
        WHERE pet_id = ? 
        ORDER BY vaccination_date DESC
    ");
    $stmt->execute([$pet_id]);
    $vaccinations = $stmt->fetchAll();
    
} catch(PDOException $e) {
    header('Location: browsePets.php');
    exit();
}
?>

<?php include '../common/header.php'; ?>
<?php include '../common/navbar_adopter.php'; ?>

<div class="main-content">
    <!-- Breadcrumb -->
    <div style="margin-bottom: 2rem;">
        <a href="browsePets.php" style="color: #667eea; text-decoration: none;">← Back to Browse Pets</a>
    </div>
    
    <div class="grid grid-2" style="gap: 3rem;">
        <!-- Pet Photos and Basic Info -->
        <div class="card">
            <div style="text-align: center; margin-bottom: 2rem;">
                <?php if ($pet['photo_path'] && file_exists('../uploads/' . $pet['photo_path'])): ?>
                    <img src="../uploads/<?php echo htmlspecialchars($pet['photo_path']); ?>" 
                         alt="<?php echo htmlspecialchars($pet['name']); ?>" 
                         style="width: 100%; max-width: 400px; height: 300px; object-fit: cover; border-radius: 10px;">
                <?php else: ?>
                    <div style="width: 100%; max-width: 400px; height: 300px; background: #f0f0f0; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 4rem; margin: 0 auto;">
                        🐕
                    </div>
                <?php endif; ?>
            </div>
            
            <h1 style="color: #333; margin-bottom: 1rem; text-align: center;"><?php echo htmlspecialchars($pet['name']); ?></h1>
            
            <div class="grid grid-2" style="margin-bottom: 2rem;">
                <div>
                    <p><strong>Species:</strong> <?php echo ucfirst($pet['species']); ?></p>
                    <p><strong>Breed:</strong> <?php echo htmlspecialchars($pet['breed']); ?></p>
                    <p><strong>Age:</strong> <?php echo $pet['age']; ?> years old</p>
                    <p><strong>Gender:</strong> <?php echo ucfirst($pet['gender']); ?></p>
                </div>
                <div>
                    <p><strong>Size:</strong> <?php echo ucfirst($pet['size']); ?></p>
                    <p><strong>Color:</strong> <?php echo htmlspecialchars($pet['color']); ?></p>
                    <p><strong>Adoption Fee:</strong> $<?php echo number_format($pet['adoption_fee'], 2); ?></p>
                    <p><strong>Health Status:</strong> <?php echo htmlspecialchars($pet['health_status']); ?></p>
                </div>
            </div>
            
            <div style="text-align: center; margin-bottom: 2rem;">
                <span class="badge badge-<?php 
                    echo $pet['vaccination_status'] === 'up_to_date' ? 'success' : 
                        ($pet['vaccination_status'] === 'partial' ? 'warning' : 'secondary'); 
                ?>">
                    <?php echo ucfirst(str_replace('_', ' ', $pet['vaccination_status'])); ?> Vaccinations
                </span>
            </div>
            
            <div style="text-align: center;">
                <button onclick="requestAdoption(<?php echo $pet['id']; ?>)" class="btn btn-success" style="font-size: 1.1rem; padding: 1rem 2rem;">
                    💝 Request Adoption
                </button>
            </div>
        </div>
        
        <!-- Pet Description and Details -->
        <div class="card">
            <h2 style="color: #333; margin-bottom: 1.5rem;">About <?php echo htmlspecialchars($pet['name']); ?></h2>
            
            <div style="margin-bottom: 2rem;">
                <h3 style="color: #667eea; margin-bottom: 1rem;">Description</h3>
                <p style="line-height: 1.8; color: #666;">
                    <?php echo nl2br(htmlspecialchars($pet['description'])); ?>
                </p>
            </div>
            
            <div style="margin-bottom: 2rem;">
                <h3 style="color: #667eea; margin-bottom: 1rem;">Shelter Information</h3>
                <div class="grid grid-2">
                    <div>
                        <p><strong>Shelter:</strong> <?php echo htmlspecialchars($pet['shelter_name']); ?></p>
                        <p><strong>License:</strong> <?php echo htmlspecialchars($pet['license_number']); ?></p>
                        <p><strong>Contact:</strong> <?php echo htmlspecialchars($pet['shelter_contact_first'] . ' ' . $pet['shelter_contact_last']); ?></p>
                    </div>
                    <div>
                        <p><strong>Email:</strong> <?php echo htmlspecialchars($pet['shelter_email']); ?></p>
                        <p><strong>Phone:</strong> <?php echo $pet['shelter_phone'] ? htmlspecialchars($pet['shelter_phone']) : 'Not provided'; ?></p>
                        <p><strong>Address:</strong> <?php echo $pet['shelter_address'] ? htmlspecialchars($pet['shelter_address']) : 'Not provided'; ?></p>
                    </div>
                </div>
                
                <?php if ($pet['shelter_description']): ?>
                    <div style="margin-top: 1rem;">
                        <p><strong>About the Shelter:</strong></p>
                        <p style="color: #666; line-height: 1.6;"><?php echo htmlspecialchars($pet['shelter_description']); ?></p>
                    </div>
                <?php endif; ?>
            </div>
            
            <!-- Vaccination Records -->
            <?php if (!empty($vaccinations)): ?>
                <div style="margin-bottom: 2rem;">
                    <h3 style="color: #667eea; margin-bottom: 1rem;">Vaccination Records</h3>
                    <div style="max-height: 200px; overflow-y: auto;">
                        <?php foreach ($vaccinations as $vaccination): ?>
                            <div style="padding: 1rem; border: 1px solid #eee; border-radius: 5px; margin-bottom: 0.5rem;">
                                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.5rem;">
                                    <strong><?php echo htmlspecialchars($vaccination['vaccine_name']); ?></strong>
                                    <span style="color: #666; font-size: 0.9rem;">
                                        <?php echo date('M j, Y', strtotime($vaccination['vaccination_date'])); ?>
                                    </span>
                                </div>
                                <?php if ($vaccination['next_due_date']): ?>
                                    <p style="margin: 0; color: #666; font-size: 0.9rem;">
                                        <strong>Next Due:</strong> <?php echo date('M j, Y', strtotime($vaccination['next_due_date'])); ?>
                                    </p>
                                <?php endif; ?>
                                <?php if ($vaccination['veterinarian']): ?>
                                    <p style="margin: 0; color: #666; font-size: 0.9rem;">
                                        <strong>Veterinarian:</strong> <?php echo htmlspecialchars($vaccination['veterinarian']); ?>
                                    </p>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Adoption Process Information -->
    <div class="card" style="margin-top: 3rem;">
        <h2 style="color: #333; margin-bottom: 1.5rem;">Adoption Process</h2>
        
        <div class="grid grid-4">
            <div style="text-align: center;">
                <div style="font-size: 2rem; margin-bottom: 1rem;">1️⃣</div>
                <h4>Submit Request</h4>
                <p style="color: #666; font-size: 0.9rem;">Fill out the adoption request form with information about yourself and your home.</p>
            </div>
            
            <div style="text-align: center;">
                <div style="font-size: 2rem; margin-bottom: 1rem;">2️⃣</div>
                <h4>Shelter Review</h4>
                <p style="color: #666; font-size: 0.9rem;">The shelter will review your application and may contact you for additional information.</p>
            </div>
            
            <div style="text-align: center;">
                <div style="font-size: 2rem; margin-bottom: 1rem;">3️⃣</div>
                <h4>Meet & Greet</h4>
                <p style="color: #666; font-size: 0.9rem;">If approved, you'll schedule a meeting with the pet to ensure it's a good match.</p>
            </div>
            
            <div style="text-align: center;">
                <div style="font-size: 2rem; margin-bottom: 1rem;">4️⃣</div>
                <h4>Finalize Adoption</h4>
                <p style="color: #666; font-size: 0.9rem;">Complete the adoption paperwork and welcome your new family member home!</p>
            </div>
        </div>
    </div>
</div>

<!-- Adoption Request Modal -->
<div id="adoptionModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1000;">
    <div style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); background: white; padding: 2rem; border-radius: 10px; width: 600px; max-height: 80vh; overflow-y: auto;">
        <h3 style="margin-bottom: 1rem;">Request Adoption for <?php echo htmlspecialchars($pet['name']); ?></h3>
        <form method="POST" action="requestAdoption.php" id="adoptionForm">
            <input type="hidden" name="pet_id" value="<?php echo $pet['id']; ?>">
            
            <div class="form-group">
                <label for="notes">Tell us about yourself and why you'd like to adopt <?php echo htmlspecialchars($pet['name']); ?>:</label>
                <textarea id="notes" name="notes" rows="6" required 
                          placeholder="Please share information about your home, experience with pets, lifestyle, and why you think this pet would be a good fit for your family..."></textarea>
            </div>
            
            <div style="background: #f8f9fa; padding: 1rem; border-radius: 5px; margin-bottom: 1rem;">
                <h4 style="color: #333; margin-bottom: 0.5rem;">Adoption Information</h4>
                <p style="margin: 0; color: #666; font-size: 0.9rem;">
                    <strong>Adoption Fee:</strong> $<?php echo number_format($pet['adoption_fee'], 2); ?><br>
                    <strong>Shelter:</strong> <?php echo htmlspecialchars($pet['shelter_name']); ?><br>
                    <strong>Contact:</strong> <?php echo htmlspecialchars($pet['shelter_email']); ?>
                </p>
            </div>
            
            <div style="display: flex; gap: 1rem; justify-content: flex-end;">
                <button type="button" onclick="closeAdoptionModal()" class="btn btn-secondary">Cancel</button>
                <button type="submit" class="btn btn-success">Submit Adoption Request</button>
            </div>
        </form>
    </div>
</div>

<script>
function requestAdoption(petId) {
    document.getElementById('adoptionModal').style.display = 'block';
}

function closeAdoptionModal() {
    document.getElementById('adoptionModal').style.display = 'none';
}

// Close modal when clicking outside
document.getElementById('adoptionModal').addEventListener('click', function(e) {
    if (e.target === this) {
        closeAdoptionModal();
    }
});

// Form validation
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('adoptionForm');
    const notes = document.getElementById('notes');
    
    form.addEventListener('submit', function(e) {
        if (notes.value.trim().length < 50) {
            e.preventDefault();
            alert('Please provide at least 50 characters describing why you\'d like to adopt this pet.');
            notes.focus();
        }
    });
});
</script>

<?php include '../common/footer.php'; ?>