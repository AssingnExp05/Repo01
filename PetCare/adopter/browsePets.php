<?php
require_once '../config/db.php';
requireUserType('adopter');

$page_title = 'Browse Pets';

// Get pets with pagination and filters
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 12;
$offset = ($page - 1) * $limit;

// Filter parameters
$species_filter = $_GET['species'] ?? '';
$breed_filter = $_GET['breed'] ?? '';
$age_filter = $_GET['age'] ?? '';
$size_filter = $_GET['size'] ?? '';
$gender_filter = $_GET['gender'] ?? '';
$search_query = $_GET['search'] ?? '';

try {
    // Build query with filters
    $where_conditions = ["p.status = 'available'"];
    $params = [];
    
    if ($species_filter) {
        $where_conditions[] = "p.species = ?";
        $params[] = $species_filter;
    }
    
    if ($breed_filter) {
        $where_conditions[] = "p.breed LIKE ?";
        $params[] = "%$breed_filter%";
    }
    
    if ($age_filter) {
        if ($age_filter === 'puppy') {
            $where_conditions[] = "p.age <= 1";
        } elseif ($age_filter === 'young') {
            $where_conditions[] = "p.age BETWEEN 2 AND 4";
        } elseif ($age_filter === 'adult') {
            $where_conditions[] = "p.age BETWEEN 5 AND 8";
        } elseif ($age_filter === 'senior') {
            $where_conditions[] = "p.age > 8";
        }
    }
    
    if ($size_filter) {
        $where_conditions[] = "p.size = ?";
        $params[] = $size_filter;
    }
    
    if ($gender_filter) {
        $where_conditions[] = "p.gender = ?";
        $params[] = $gender_filter;
    }
    
    if ($search_query) {
        $where_conditions[] = "(p.name LIKE ? OR p.description LIKE ? OR p.breed LIKE ?)";
        $search_param = "%$search_query%";
        $params[] = $search_param;
        $params[] = $search_param;
        $params[] = $search_param;
    }
    
    $where_clause = implode(' AND ', $where_conditions);
    
    // Get total count
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as count 
        FROM pets p 
        JOIN shelters s ON p.shelter_id = s.id 
        WHERE $where_clause
    ");
    $stmt->execute($params);
    $total_pets = $stmt->fetch()['count'];
    $total_pages = ceil($total_pets / $limit);
    
    // Get pets
    $stmt = $pdo->prepare("
        SELECT p.*, s.shelter_name, s.license_number
        FROM pets p 
        JOIN shelters s ON p.shelter_id = s.id 
        WHERE $where_clause
        ORDER BY p.created_at DESC 
        LIMIT ? OFFSET ?
    ");
    $params[] = $limit;
    $params[] = $offset;
    $stmt->execute($params);
    $pets = $stmt->fetchAll();
    
} catch(PDOException $e) {
    $pets = [];
    $total_pages = 0;
}
?>

<?php include '../common/header.php'; ?>
<?php include '../common/navbar_adopter.php'; ?>

<div class="main-content">
    <h1 style="color: #333; margin-bottom: 2rem;">🔍 Browse Available Pets</h1>
    
    <!-- Search and Filters -->
    <div class="card" style="margin-bottom: 2rem;">
        <form method="GET" action="">
            <div class="form-group" style="margin-bottom: 1.5rem;">
                <label for="search">Search Pets:</label>
                <input type="text" id="search" name="search" placeholder="Search by name, breed, or description..." 
                       value="<?php echo htmlspecialchars($search_query); ?>"
                       style="width: 100%; padding: 0.75rem; border: 2px solid #ddd; border-radius: 5px;">
            </div>
            
            <div class="grid grid-3" style="gap: 1rem;">
                <div class="form-group">
                    <label for="species">Species:</label>
                    <select id="species" name="species">
                        <option value="">All Species</option>
                        <option value="dog" <?php echo $species_filter === 'dog' ? 'selected' : ''; ?>>Dog</option>
                        <option value="cat" <?php echo $species_filter === 'cat' ? 'selected' : ''; ?>>Cat</option>
                        <option value="bird" <?php echo $species_filter === 'bird' ? 'selected' : ''; ?>>Bird</option>
                        <option value="rabbit" <?php echo $species_filter === 'rabbit' ? 'selected' : ''; ?>>Rabbit</option>
                        <option value="other" <?php echo $species_filter === 'other' ? 'selected' : ''; ?>>Other</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="breed">Breed:</label>
                    <input type="text" id="breed" name="breed" placeholder="e.g., Golden Retriever" 
                           value="<?php echo htmlspecialchars($breed_filter); ?>">
                </div>
                
                <div class="form-group">
                    <label for="age">Age Group:</label>
                    <select id="age" name="age">
                        <option value="">All Ages</option>
                        <option value="puppy" <?php echo $age_filter === 'puppy' ? 'selected' : ''; ?>>Puppy (0-1 years)</option>
                        <option value="young" <?php echo $age_filter === 'young' ? 'selected' : ''; ?>>Young (2-4 years)</option>
                        <option value="adult" <?php echo $age_filter === 'adult' ? 'selected' : ''; ?>>Adult (5-8 years)</option>
                        <option value="senior" <?php echo $age_filter === 'senior' ? 'selected' : ''; ?>>Senior (8+ years)</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="size">Size:</label>
                    <select id="size" name="size">
                        <option value="">All Sizes</option>
                        <option value="small" <?php echo $size_filter === 'small' ? 'selected' : ''; ?>>Small</option>
                        <option value="medium" <?php echo $size_filter === 'medium' ? 'selected' : ''; ?>>Medium</option>
                        <option value="large" <?php echo $size_filter === 'large' ? 'selected' : ''; ?>>Large</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="gender">Gender:</label>
                    <select id="gender" name="gender">
                        <option value="">All Genders</option>
                        <option value="male" <?php echo $gender_filter === 'male' ? 'selected' : ''; ?>>Male</option>
                        <option value="female" <?php echo $gender_filter === 'female' ? 'selected' : ''; ?>>Female</option>
                    </select>
                </div>
                
                <div class="form-group" style="display: flex; align-items: end; gap: 0.5rem;">
                    <button type="submit" class="btn" style="flex: 1;">Search</button>
                    <a href="browsePets.php" class="btn btn-secondary">Clear</a>
                </div>
            </div>
        </form>
    </div>
    
    <!-- Results Summary -->
    <div style="margin-bottom: 2rem;">
        <p style="color: #666;">
            <?php if ($total_pets > 0): ?>
                Found <strong><?php echo $total_pets; ?></strong> available pet<?php echo $total_pets !== 1 ? 's' : ''; ?>
                <?php if ($search_query || $species_filter || $breed_filter || $age_filter || $size_filter || $gender_filter): ?>
                    matching your criteria
                <?php endif; ?>
            <?php else: ?>
                No pets found matching your criteria
            <?php endif; ?>
        </p>
    </div>
    
    <!-- Pets Grid -->
    <?php if (!empty($pets)): ?>
        <div class="grid grid-3" style="margin-bottom: 3rem;">
            <?php foreach ($pets as $pet): ?>
                <div class="card" style="text-align: center;">
                    <div style="margin-bottom: 1rem;">
                        <?php if ($pet['photo_path'] && file_exists('../uploads/' . $pet['photo_path'])): ?>
                            <img src="../uploads/<?php echo htmlspecialchars($pet['photo_path']); ?>" 
                                 alt="<?php echo htmlspecialchars($pet['name']); ?>" 
                                 style="width: 100%; height: 200px; object-fit: cover; border-radius: 10px;">
                        <?php else: ?>
                            <div style="width: 100%; height: 200px; background: #f0f0f0; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 3rem;">
                                🐕
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <h3 style="margin-bottom: 0.5rem; color: #333;"><?php echo htmlspecialchars($pet['name']); ?></h3>
                    
                    <div style="margin-bottom: 1rem; color: #666;">
                        <p><strong>Species:</strong> <?php echo ucfirst($pet['species']); ?></p>
                        <p><strong>Breed:</strong> <?php echo htmlspecialchars($pet['breed']); ?></p>
                        <p><strong>Age:</strong> <?php echo $pet['age']; ?> years old</p>
                        <p><strong>Gender:</strong> <?php echo ucfirst($pet['gender']); ?></p>
                        <p><strong>Size:</strong> <?php echo ucfirst($pet['size']); ?></p>
                        <p><strong>Adoption Fee:</strong> $<?php echo number_format($pet['adoption_fee'], 2); ?></p>
                    </div>
                    
                    <div style="margin-bottom: 1rem;">
                        <p><strong>Shelter:</strong> <?php echo htmlspecialchars($pet['shelter_name']); ?></p>
                        <p style="color: #666; font-size: 0.9rem;">
                            <?php echo htmlspecialchars(substr($pet['description'], 0, 100)) . '...'; ?>
                        </p>
                    </div>
                    
                    <div style="margin-bottom: 1.5rem;">
                        <span class="badge badge-<?php 
                            echo $pet['vaccination_status'] === 'up_to_date' ? 'success' : 
                                ($pet['vaccination_status'] === 'partial' ? 'warning' : 'secondary'); 
                        ?>">
                            <?php echo ucfirst(str_replace('_', ' ', $pet['vaccination_status'])); ?> Vaccinations
                        </span>
                    </div>
                    
                    <div style="display: flex; gap: 0.5rem; flex-direction: column;">
                        <a href="petDetails.php?id=<?php echo $pet['id']; ?>" class="btn">View Details</a>
                        <button onclick="requestAdoption(<?php echo $pet['id']; ?>)" class="btn btn-success">Request Adoption</button>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        
        <!-- Pagination -->
        <?php if ($total_pages > 1): ?>
            <div style="text-align: center; margin-top: 2rem;">
                <div style="display: inline-flex; gap: 0.5rem;">
                    <?php if ($page > 1): ?>
                        <a href="?page=<?php echo $page - 1; ?>&species=<?php echo $species_filter; ?>&breed=<?php echo $breed_filter; ?>&age=<?php echo $age_filter; ?>&size=<?php echo $size_filter; ?>&gender=<?php echo $gender_filter; ?>&search=<?php echo urlencode($search_query); ?>" 
                           class="btn btn-secondary">Previous</a>
                    <?php endif; ?>
                    
                    <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                        <a href="?page=<?php echo $i; ?>&species=<?php echo $species_filter; ?>&breed=<?php echo $breed_filter; ?>&age=<?php echo $age_filter; ?>&size=<?php echo $size_filter; ?>&gender=<?php echo $gender_filter; ?>&search=<?php echo urlencode($search_query); ?>" 
                           class="btn <?php echo $i === $page ? 'btn-primary' : 'btn-secondary'; ?>">
                            <?php echo $i; ?>
                        </a>
                    <?php endfor; ?>
                    
                    <?php if ($page < $total_pages): ?>
                        <a href="?page=<?php echo $page + 1; ?>&species=<?php echo $species_filter; ?>&breed=<?php echo $breed_filter; ?>&age=<?php echo $age_filter; ?>&size=<?php echo $size_filter; ?>&gender=<?php echo $gender_filter; ?>&search=<?php echo urlencode($search_query); ?>" 
                           class="btn btn-secondary">Next</a>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>
        
    <?php else: ?>
        <div class="card text-center" style="padding: 3rem;">
            <div style="font-size: 4rem; margin-bottom: 1rem;">🔍</div>
            <h3 style="color: #666; margin-bottom: 1rem;">No pets found</h3>
            <p style="color: #666; margin-bottom: 2rem;">
                <?php if ($search_query || $species_filter || $breed_filter || $age_filter || $size_filter || $gender_filter): ?>
                    No pets match your current search criteria. Try adjusting your filters or search terms.
                <?php else: ?>
                    There are currently no pets available for adoption. Check back later for new listings!
                <?php endif; ?>
            </p>
            <a href="browsePets.php" class="btn">View All Pets</a>
        </div>
    <?php endif; ?>
</div>

<!-- Adoption Request Modal -->
<div id="adoptionModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1000;">
    <div style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); background: white; padding: 2rem; border-radius: 10px; width: 500px;">
        <h3 style="margin-bottom: 1rem;">Request Adoption</h3>
        <form method="POST" action="requestAdoption.php" id="adoptionForm">
            <input type="hidden" name="pet_id" id="adoptionPetId">
            
            <div class="form-group">
                <label for="notes">Tell us about yourself and why you'd like to adopt this pet:</label>
                <textarea id="notes" name="notes" rows="4" required 
                          placeholder="Please share information about your home, experience with pets, lifestyle, and why you think this pet would be a good fit for your family..."></textarea>
            </div>
            
            <div style="display: flex; gap: 1rem; justify-content: flex-end;">
                <button type="button" onclick="closeAdoptionModal()" class="btn btn-secondary">Cancel</button>
                <button type="submit" class="btn btn-success">Submit Request</button>
            </div>
        </form>
    </div>
</div>

<script>
function requestAdoption(petId) {
    document.getElementById('adoptionPetId').value = petId;
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