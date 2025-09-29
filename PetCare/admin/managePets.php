<?php
require_once '../config/db.php';
requireUserType('admin');

$page_title = 'Manage Pets';
$message = '';
$message_type = '';

// Handle pet status updates
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action'])) {
    $pet_id = (int)$_POST['pet_id'];
    $action = $_POST['action'];
    
    try {
        if ($action === 'delete') {
            $stmt = $pdo->prepare("DELETE FROM pets WHERE id = ?");
            $stmt->execute([$pet_id]);
            $message = 'Pet deleted successfully.';
            $message_type = 'success';
        } elseif ($action === 'update_status') {
            $new_status = $_POST['new_status'];
            $stmt = $pdo->prepare("UPDATE pets SET status = ? WHERE id = ?");
            $stmt->execute([$new_status, $pet_id]);
            $message = 'Pet status updated successfully.';
            $message_type = 'success';
        }
    } catch(PDOException $e) {
        $message = 'Error updating pet: ' . $e->getMessage();
        $message_type = 'error';
    }
}

// Get pets with pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 10;
$offset = ($page - 1) * $limit;

try {
    // Get total count
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM pets");
    $total_pets = $stmt->fetch()['count'];
    $total_pages = ceil($total_pets / $limit);
    
    // Get pets with shelter information
    $stmt = $pdo->prepare("
        SELECT p.*, s.shelter_name, s.user_id as shelter_user_id
        FROM pets p 
        JOIN shelters s ON p.shelter_id = s.id 
        ORDER BY p.created_at DESC 
        LIMIT ? OFFSET ?
    ");
    $stmt->execute([$limit, $offset]);
    $pets = $stmt->fetchAll();
    
} catch(PDOException $e) {
    $pets = [];
    $total_pages = 0;
}
?>

<?php include '../common/header.php'; ?>
<?php include '../common/navbar_admin.php'; ?>

<div class="main-content">
    <h1 style="color: #333; margin-bottom: 2rem;">🐾 Manage Pets</h1>
    
    <?php if ($message): ?>
        <div class="alert alert-<?php echo $message_type; ?>">
            <?php echo $message; ?>
        </div>
    <?php endif; ?>
    
    <!-- Pet Statistics -->
    <div class="grid grid-4" style="margin-bottom: 3rem;">
        <?php
        try {
            $stmt = $pdo->query("SELECT status, COUNT(*) as count FROM pets GROUP BY status");
            $pet_stats = $stmt->fetchAll();
            $stats = array_column($pet_stats, 'count', 'status');
        } catch(PDOException $e) {
            $stats = ['available' => 0, 'adopted' => 0, 'pending' => 0, 'not_available' => 0];
        }
        ?>
        
        <div class="card text-center">
            <div style="font-size: 2rem; color: #27ae60; margin-bottom: 0.5rem;">
                <?php echo $stats['available'] ?? 0; ?>
            </div>
            <h4>Available</h4>
        </div>
        
        <div class="card text-center">
            <div style="font-size: 2rem; color: #e74c3c; margin-bottom: 0.5rem;">
                <?php echo $stats['adopted'] ?? 0; ?>
            </div>
            <h4>Adopted</h4>
        </div>
        
        <div class="card text-center">
            <div style="font-size: 2rem; color: #f39c12; margin-bottom: 0.5rem;">
                <?php echo $stats['pending'] ?? 0; ?>
            </div>
            <h4>Pending</h4>
        </div>
        
        <div class="card text-center">
            <div style="font-size: 2rem; color: #6c757d; margin-bottom: 0.5rem;">
                <?php echo $stats['not_available'] ?? 0; ?>
            </div>
            <h4>Not Available</h4>
        </div>
    </div>
    
    <!-- Pets Table -->
    <div class="card">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
            <h2 style="color: #333; margin: 0;">All Pets</h2>
            <div>
                <input type="text" id="search-pets" placeholder="Search pets..." 
                       style="padding: 0.5rem; border: 2px solid #ddd; border-radius: 5px; width: 250px;"
                       onkeyup="filterTable('search-pets', 'pets-table')">
            </div>
        </div>
        
        <?php if (!empty($pets)): ?>
            <div class="table-responsive">
                <table class="table" id="pets-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Photo</th>
                            <th>Name</th>
                            <th>Species</th>
                            <th>Breed</th>
                            <th>Age</th>
                            <th>Gender</th>
                            <th>Size</th>
                            <th>Status</th>
                            <th>Shelter</th>
                            <th>Added</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($pets as $pet): ?>
                        <tr>
                            <td><?php echo $pet['id']; ?></td>
                            <td>
                                <?php if ($pet['photo_path'] && file_exists('../uploads/' . $pet['photo_path'])): ?>
                                    <img src="../uploads/<?php echo htmlspecialchars($pet['photo_path']); ?>" 
                                         alt="<?php echo htmlspecialchars($pet['name']); ?>" 
                                         style="width: 50px; height: 50px; object-fit: cover; border-radius: 5px;">
                                <?php else: ?>
                                    <div style="width: 50px; height: 50px; background: #f0f0f0; border-radius: 5px; display: flex; align-items: center; justify-content: center;">
                                        🐕
                                    </div>
                                <?php endif; ?>
                            </td>
                            <td><?php echo htmlspecialchars($pet['name']); ?></td>
                            <td><?php echo ucfirst($pet['species']); ?></td>
                            <td><?php echo htmlspecialchars($pet['breed']); ?></td>
                            <td><?php echo $pet['age']; ?> years</td>
                            <td><?php echo ucfirst($pet['gender']); ?></td>
                            <td><?php echo ucfirst($pet['size']); ?></td>
                            <td>
                                <span class="badge badge-<?php 
                                    echo $pet['status'] === 'available' ? 'success' : 
                                        ($pet['status'] === 'adopted' ? 'info' : 
                                        ($pet['status'] === 'pending' ? 'warning' : 'secondary')); 
                                ?>">
                                    <?php echo ucfirst($pet['status']); ?>
                                </span>
                            </td>
                            <td><?php echo htmlspecialchars($pet['shelter_name']); ?></td>
                            <td><?php echo date('M j, Y', strtotime($pet['created_at'])); ?></td>
                            <td>
                                <div style="display: flex; gap: 0.5rem; flex-wrap: wrap;">
                                    <button onclick="updatePetStatus(<?php echo $pet['id']; ?>)" 
                                            class="btn btn-warning" style="padding: 0.25rem 0.5rem; font-size: 0.8rem;">
                                        Update Status
                                    </button>
                                    <button onclick="confirmDelete('Are you sure you want to delete this pet?')" 
                                            class="btn btn-danger" style="padding: 0.25rem 0.5rem; font-size: 0.8rem;">
                                        Delete
                                    </button>
                                    <form method="POST" style="display: inline;">
                                        <input type="hidden" name="pet_id" value="<?php echo $pet['id']; ?>">
                                        <input type="hidden" name="action" value="delete">
                                    </form>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            
            <!-- Pagination -->
            <?php if ($total_pages > 1): ?>
                <div style="text-align: center; margin-top: 2rem;">
                    <div style="display: inline-flex; gap: 0.5rem;">
                        <?php if ($page > 1): ?>
                            <a href="?page=<?php echo $page - 1; ?>" class="btn btn-secondary">Previous</a>
                        <?php endif; ?>
                        
                        <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                            <a href="?page=<?php echo $i; ?>" 
                               class="btn <?php echo $i === $page ? 'btn-primary' : 'btn-secondary'; ?>">
                                <?php echo $i; ?>
                            </a>
                        <?php endfor; ?>
                        
                        <?php if ($page < $total_pages): ?>
                            <a href="?page=<?php echo $page + 1; ?>" class="btn btn-secondary">Next</a>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>
            
        <?php else: ?>
            <p style="color: #666; text-align: center; padding: 2rem;">No pets found.</p>
        <?php endif; ?>
    </div>
    
    <!-- Pet Management Tools -->
    <div class="grid grid-2" style="margin-top: 3rem;">
        <div class="card">
            <h3 style="color: #333; margin-bottom: 1.5rem;">📊 Pet Analytics</h3>
            
            <?php
            try {
                // Get pets by species
                $stmt = $pdo->query("SELECT species, COUNT(*) as count FROM pets GROUP BY species ORDER BY count DESC");
                $species_stats = $stmt->fetchAll();
                
                // Get recent pet additions
                $stmt = $pdo->prepare("
                    SELECT DATE(created_at) as date, COUNT(*) as count 
                    FROM pets 
                    WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
                    GROUP BY DATE(created_at) 
                    ORDER BY date DESC 
                    LIMIT 7
                ");
                $stmt->execute();
                $recent_additions = $stmt->fetchAll();
            } catch(PDOException $e) {
                $species_stats = [];
                $recent_additions = [];
            }
            ?>
            
            <h4 style="color: #667eea; margin-bottom: 1rem;">Pets by Species</h4>
            <?php if (!empty($species_stats)): ?>
                <div style="margin-bottom: 2rem;">
                    <?php foreach ($species_stats as $stat): ?>
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.5rem;">
                            <span><?php echo ucfirst($stat['species']); ?></span>
                            <span class="badge badge-info"><?php echo $stat['count']; ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
            
            <h4 style="color: #667eea; margin-bottom: 1rem;">Recent Additions (Last 7 Days)</h4>
            <?php if (!empty($recent_additions)): ?>
                <div style="margin-bottom: 1rem;">
                    <?php foreach ($recent_additions as $addition): ?>
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.5rem;">
                            <span><?php echo date('M j', strtotime($addition['date'])); ?></span>
                            <span class="badge badge-success"><?php echo $addition['count']; ?> pets</span>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <p style="color: #666;">No recent additions.</p>
            <?php endif; ?>
        </div>
        
        <div class="card">
            <h3 style="color: #333; margin-bottom: 1.5rem;">🔧 Pet Management</h3>
            
            <div style="margin-bottom: 1.5rem;">
                <h4 style="color: #667eea; margin-bottom: 1rem;">Quick Actions</h4>
                <div style="display: flex; flex-direction: column; gap: 0.5rem;">
                    <button class="btn btn-secondary" onclick="exportPets()">Export Pet Data</button>
                    <button class="btn btn-warning" onclick="bulkUpdateStatus()">Bulk Status Update</button>
                    <button class="btn btn-info" onclick="generatePetReport()">Generate Report</button>
                </div>
            </div>
            
            <div>
                <h4 style="color: #667eea; margin-bottom: 1rem;">System Maintenance</h4>
                <div style="display: flex; flex-direction: column; gap: 0.5rem;">
                    <button class="btn btn-danger" onclick="cleanupOldPets()">Cleanup Old Listings</button>
                    <button class="btn btn-warning" onclick="updateVaccinationStatus()">Update Vaccination Status</button>
                </div>
            </div>
        </div>
    </div>
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

function exportPets() {
    if (confirm('Export all pet data to CSV?')) {
        window.location.href = 'exportPets.php';
    }
}

function bulkUpdateStatus() {
    alert('Bulk status update feature coming soon!');
}

function generatePetReport() {
    if (confirm('Generate pet analytics report?')) {
        window.location.href = 'petReport.php';
    }
}

function cleanupOldPets() {
    if (confirm('This will remove pets that have been listed for 6+ months without adoption. Continue?')) {
        alert('Cleanup feature coming soon!');
    }
}

function updateVaccinationStatus() {
    if (confirm('Update vaccination status for all pets?')) {
        alert('Vaccination update feature coming soon!');
    }
}

// Close modal when clicking outside
document.getElementById('statusModal').addEventListener('click', function(e) {
    if (e.target === this) {
        closeStatusModal();
    }
});
</script>

<?php include '../common/footer.php'; ?>