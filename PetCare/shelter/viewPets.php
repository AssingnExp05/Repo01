<?php
require_once '../config/db.php';
requireUserType('shelter');

$page_title = 'View Pets';
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

// Handle pet status updates
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action'])) {
    $pet_id = (int)$_POST['pet_id'];
    $action = $_POST['action'];
    
    try {
        if ($action === 'update_status') {
            $new_status = $_POST['new_status'];
            $stmt = $pdo->prepare("UPDATE pets SET status = ? WHERE id = ? AND shelter_id = ?");
            $stmt->execute([$new_status, $pet_id, $shelter_id]);
            $message = 'Pet status updated successfully.';
            $message_type = 'success';
        } elseif ($action === 'delete') {
            $stmt = $pdo->prepare("DELETE FROM pets WHERE id = ? AND shelter_id = ?");
            $stmt->execute([$pet_id, $shelter_id]);
            $message = 'Pet deleted successfully.';
            $message_type = 'success';
        }
    } catch(PDOException $e) {
        $message = 'Error updating pet: ' . $e->getMessage();
        $message_type = 'error';
    }
}

// Get pets with pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 12;
$offset = ($page - 1) * $limit;

// Filter parameters
$status_filter = $_GET['status'] ?? '';
$species_filter = $_GET['species'] ?? '';

try {
    // Build query with filters
    $where_conditions = ["p.shelter_id = ?"];
    $params = [$shelter_id];
    
    if ($status_filter) {
        $where_conditions[] = "p.status = ?";
        $params[] = $status_filter;
    }
    
    if ($species_filter) {
        $where_conditions[] = "p.species = ?";
        $params[] = $species_filter;
    }
    
    $where_clause = implode(' AND ', $where_conditions);
    
    // Get total count
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM pets p WHERE $where_clause");
    $stmt->execute($params);
    $total_pets = $stmt->fetch()['count'];
    $total_pages = ceil($total_pets / $limit);
    
    // Get pets
    $stmt = $pdo->prepare("
        SELECT p.*, 
               COUNT(ar.id) as adoption_requests_count
        FROM pets p
        LEFT JOIN adoption_requests ar ON p.id = ar.pet_id
        WHERE $where_clause
        GROUP BY p.id
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
<?php include '../common/navbar_shelter.php'; ?>

<div class="main-content">
    <h1 style="color: #333; margin-bottom: 2rem;">🐾 View Pets - <?php echo htmlspecialchars($shelter['shelter_name']); ?></h1>
    
    <?php if ($message): ?>
        <div class="alert alert-<?php echo $message_type; ?>">
            <?php echo $message; ?>
        </div>
    <?php endif; ?>
    
    <!-- Filters -->
    <div class="card" style="margin-bottom: 2rem;">
        <form method="GET" action="">
            <div class="form-row">
                <div class="form-group">
                    <label for="status">Filter by Status:</label>
                    <select id="status" name="status" onchange="this.form.submit()">
                        <option value="">All Statuses</option>
                        <option value="available" <?php echo $status_filter === 'available' ? 'selected' : ''; ?>>Available</option>
                        <option value="adopted" <?php echo $status_filter === 'adopted' ? 'selected' : ''; ?>>Adopted</option>
                        <option value="pending" <?php echo $status_filter === 'pending' ? 'selected' : ''; ?>>Pending</option>
                        <option value="not_available" <?php echo $status_filter === 'not_available' ? 'selected' : ''; ?>>Not Available</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="species">Filter by Species:</label>
                    <select id="species" name="species" onchange="this.form.submit()">
                        <option value="">All Species</option>
                        <option value="dog" <?php echo $species_filter === 'dog' ? 'selected' : ''; ?>>Dog</option>
                        <option value="cat" <?php echo $species_filter === 'cat' ? 'selected' : ''; ?>>Cat</option>
                        <option value="bird" <?php echo $species_filter === 'bird' ? 'selected' : ''; ?>>Bird</option>
                        <option value="rabbit" <?php echo $species_filter === 'rabbit' ? 'selected' : ''; ?>>Rabbit</option>
                        <option value="other" <?php echo $species_filter === 'other' ? 'selected' : ''; ?>>Other</option>
                    </select>
                </div>
                
                <div class="form-group" style="display: flex; align-items: end;">
                    <a href="viewPets.php" class="btn btn-secondary">Clear Filters</a>
                </div>
            </div>
        </form>
    </div>
    
    <!-- Pets Grid -->
    <?php if (!empty($pets)): ?>
        <div class="grid grid-3" style="margin-bottom: 3rem;">
            <?php foreach ($pets as $pet): ?>
                <div class="card">
                    <div style="text-align: center; margin-bottom: 1rem;">
                        <?php if ($pet['photo_path'] && file_exists('../uploads/' . $pet['photo_path'])): ?>
                            <img src="../uploads/<?php echo htmlspecialchars($pet['photo_path']); ?>" 
                                 alt="<?php echo htmlspecialchars($pet['name']); ?>" 
                                 style="width: 100%; height: 200px; object-fit: cover; border-radius: 5px;">
                        <?php else: ?>
                            <div style="width: 100%; height: 200px; background: #f0f0f0; border-radius: 5px; display: flex; align-items: center; justify-content: center; font-size: 3rem;">
                                🐕
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <h3 style="margin-bottom: 0.5rem; color: #333;"><?php echo htmlspecialchars($pet['name']); ?></h3>
                    
                    <div style="margin-bottom: 1rem;">
                        <p><strong>Species:</strong> <?php echo ucfirst($pet['species']); ?></p>
                        <p><strong>Breed:</strong> <?php echo htmlspecialchars($pet['breed']); ?></p>
                        <p><strong>Age:</strong> <?php echo $pet['age']; ?> years old</p>
                        <p><strong>Gender:</strong> <?php echo ucfirst($pet['gender']); ?></p>
                        <p><strong>Size:</strong> <?php echo ucfirst($pet['size']); ?></p>
                        <p><strong>Adoption Fee:</strong> $<?php echo number_format($pet['adoption_fee'], 2); ?></p>
                    </div>
                    
                    <div style="margin-bottom: 1rem;">
                        <span class="badge badge-<?php 
                            echo $pet['status'] === 'available' ? 'success' : 
                                ($pet['status'] === 'adopted' ? 'info' : 
                                ($pet['status'] === 'pending' ? 'warning' : 'secondary')); 
                        ?>">
                            <?php echo ucfirst($pet['status']); ?>
                        </span>
                        
                        <?php if ($pet['adoption_requests_count'] > 0): ?>
                            <span class="badge badge-warning" style="margin-left: 0.5rem;">
                                <?php echo $pet['adoption_requests_count']; ?> requests
                            </span>
                        <?php endif; ?>
                    </div>
                    
                    <div style="display: flex; gap: 0.5rem; flex-wrap: wrap;">
                        <button onclick="updatePetStatus(<?php echo $pet['id']; ?>)" 
                                class="btn btn-warning" style="flex: 1; font-size: 0.9rem;">
                            Update Status
                        </button>
                        <button onclick="confirmDelete('Are you sure you want to delete this pet?')" 
                                class="btn btn-danger" style="flex: 1; font-size: 0.9rem;">
                            Delete
                        </button>
                        <form method="POST" style="display: inline; flex: 1;">
                            <input type="hidden" name="pet_id" value="<?php echo $pet['id']; ?>">
                            <input type="hidden" name="action" value="delete">
                        </form>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        
        <!-- Pagination -->
        <?php if ($total_pages > 1): ?>
            <div style="text-align: center; margin-top: 2rem;">
                <div style="display: inline-flex; gap: 0.5rem;">
                    <?php if ($page > 1): ?>
                        <a href="?page=<?php echo $page - 1; ?>&status=<?php echo $status_filter; ?>&species=<?php echo $species_filter; ?>" 
                           class="btn btn-secondary">Previous</a>
                    <?php endif; ?>
                    
                    <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                        <a href="?page=<?php echo $i; ?>&status=<?php echo $status_filter; ?>&species=<?php echo $species_filter; ?>" 
                           class="btn <?php echo $i === $page ? 'btn-primary' : 'btn-secondary'; ?>">
                            <?php echo $i; ?>
                        </a>
                    <?php endfor; ?>
                    
                    <?php if ($page < $total_pages): ?>
                        <a href="?page=<?php echo $page + 1; ?>&status=<?php echo $status_filter; ?>&species=<?php echo $species_filter; ?>" 
                           class="btn btn-secondary">Next</a>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>
        
    <?php else: ?>
        <div class="card text-center" style="padding: 3rem;">
            <div style="font-size: 4rem; margin-bottom: 1rem;">🐾</div>
            <h3 style="color: #666; margin-bottom: 1rem;">No pets found</h3>
            <p style="color: #666; margin-bottom: 2rem;">
                <?php if ($status_filter || $species_filter): ?>
                    No pets match your current filters. Try adjusting your search criteria.
                <?php else: ?>
                    You haven't added any pets yet. Start by adding your first pet!
                <?php endif; ?>
            </p>
            <a href="addPet.php" class="btn">Add Your First Pet</a>
        </div>
    <?php endif; ?>
</div>

<!-- Status Update Modal -->
<div id="statusModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1000;">
    <div style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); background: white; padding: 2rem; border-radius: 10px; width: 400px;">
        <h3 style="margin-bottom: 1rem;">Update Pet Status</h3>
        <form method="POST" id="statusForm">
            <input type="hidden" name="pet_id" id="statusPetId">
            <input type="hidden" name="action" value="update_status">
            
            <div class="form-group">
                <label for="new_status">New Status:</label>
                <select id="new_status" name="new_status" required>
                    <option value="">Select status</option>
                    <option value="available">Available</option>
                    <option value="adopted">Adopted</option>
                    <option value="pending">Pending</option>
                    <option value="not_available">Not Available</option>
                </select>
            </div>
            
            <div style="display: flex; gap: 1rem; justify-content: flex-end;">
                <button type="button" onclick="closeStatusModal()" class="btn btn-secondary">Cancel</button>
                <button type="submit" class="btn">Update Status</button>
            </div>
        </form>
    </div>
</div>

<script>
function updatePetStatus(petId) {
    document.getElementById('statusPetId').value = petId;
    document.getElementById('statusModal').style.display = 'block';
}

function closeStatusModal() {
    document.getElementById('statusModal').style.display = 'none';
}

// Close modal when clicking outside
document.getElementById('statusModal').addEventListener('click', function(e) {
    if (e.target === this) {
        closeStatusModal();
    }
});
</script>

<?php include '../common/footer.php'; ?>