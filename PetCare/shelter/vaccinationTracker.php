<?php
require_once '../config/db.php';
requireUserType('shelter');

$page_title = 'Vaccination Tracker';
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

// Handle vaccination updates
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action'])) {
    $vaccination_id = (int)$_POST['vaccination_id'];
    $action = $_POST['action'];
    
    try {
        if ($action === 'add') {
            $pet_id = (int)$_POST['pet_id'];
            $vaccine_name = sanitizeInput($_POST['vaccine_name']);
            $vaccination_date = $_POST['vaccination_date'];
            $next_due_date = $_POST['next_due_date'];
            $veterinarian = sanitizeInput($_POST['veterinarian']);
            $notes = sanitizeInput($_POST['notes']);
            
            // Verify pet belongs to this shelter
            $stmt = $pdo->prepare("SELECT id FROM pets WHERE id = ? AND shelter_id = ?");
            $stmt->execute([$pet_id, $shelter_id]);
            if ($stmt->fetch()) {
                $stmt = $pdo->prepare("
                    INSERT INTO vaccinations (pet_id, vaccine_name, vaccination_date, next_due_date, veterinarian, notes) 
                    VALUES (?, ?, ?, ?, ?, ?)
                ");
                $stmt->execute([$pet_id, $vaccine_name, $vaccination_date, $next_due_date, $veterinarian, $notes]);
                $message = 'Vaccination record added successfully.';
                $message_type = 'success';
            } else {
                $message = 'Invalid pet selected.';
                $message_type = 'error';
            }
        } elseif ($action === 'update') {
            $vaccine_name = sanitizeInput($_POST['vaccine_name']);
            $vaccination_date = $_POST['vaccination_date'];
            $next_due_date = $_POST['next_due_date'];
            $veterinarian = sanitizeInput($_POST['veterinarian']);
            $notes = sanitizeInput($_POST['notes']);
            
            $stmt = $pdo->prepare("
                UPDATE vaccinations 
                SET vaccine_name = ?, vaccination_date = ?, next_due_date = ?, 
                    veterinarian = ?, notes = ? 
                WHERE id = ? AND pet_id IN (SELECT id FROM pets WHERE shelter_id = ?)
            ");
            $stmt->execute([$vaccine_name, $vaccination_date, $next_due_date, $veterinarian, $notes, $vaccination_id, $shelter_id]);
            $message = 'Vaccination record updated successfully.';
            $message_type = 'success';
        } elseif ($action === 'delete') {
            $stmt = $pdo->prepare("
                DELETE FROM vaccinations 
                WHERE id = ? AND pet_id IN (SELECT id FROM pets WHERE shelter_id = ?)
            ");
            $stmt->execute([$vaccination_id, $shelter_id]);
            $message = 'Vaccination record deleted successfully.';
            $message_type = 'success';
        }
    } catch(PDOException $e) {
        $message = 'Error updating vaccination record: ' . $e->getMessage();
        $message_type = 'error';
    }
}

// Get vaccinations with pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 10;
$offset = ($page - 1) * $limit;

// Filter parameters
$status_filter = $_GET['status'] ?? '';

try {
    // Build query with filters
    $where_conditions = ["p.shelter_id = ?"];
    $params = [$shelter_id];
    
    if ($status_filter === 'due_soon') {
        $where_conditions[] = "v.next_due_date <= DATE_ADD(NOW(), INTERVAL 30 DAY) AND v.next_due_date > NOW()";
    } elseif ($status_filter === 'overdue') {
        $where_conditions[] = "v.next_due_date < NOW()";
    } elseif ($status_filter === 'current') {
        $where_conditions[] = "v.next_due_date > DATE_ADD(NOW(), INTERVAL 30 DAY)";
    }
    
    $where_clause = implode(' AND ', $where_conditions);
    
    // Get total count
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as count 
        FROM vaccinations v
        JOIN pets p ON v.pet_id = p.id
        WHERE $where_clause
    ");
    $stmt->execute($params);
    $total_vaccinations = $stmt->fetch()['count'];
    $total_pages = ceil($total_vaccinations / $limit);
    
    // Get vaccinations
    $stmt = $pdo->prepare("
        SELECT v.*, p.name as pet_name, p.species, p.breed
        FROM vaccinations v
        JOIN pets p ON v.pet_id = p.id
        WHERE $where_clause
        ORDER BY v.vaccination_date DESC 
        LIMIT ? OFFSET ?
    ");
    $params[] = $limit;
    $params[] = $offset;
    $stmt->execute($params);
    $vaccinations = $stmt->fetchAll();
    
    // Get pets for dropdown
    $stmt = $pdo->prepare("SELECT id, name, species, breed FROM pets WHERE shelter_id = ? ORDER BY name");
    $stmt->execute([$shelter_id]);
    $pets = $stmt->fetchAll();
    
} catch(PDOException $e) {
    $vaccinations = [];
    $pets = [];
    $total_pages = 0;
}
?>

<?php include '../common/header.php'; ?>
<?php include '../common/navbar_shelter.php'; ?>

<div class="main-content">
    <h1 style="color: #333; margin-bottom: 2rem;">💉 Vaccination Tracker - <?php echo htmlspecialchars($shelter['shelter_name']); ?></h1>
    
    <?php if ($message): ?>
        <div class="alert alert-<?php echo $message_type; ?>">
            <?php echo $message; ?>
        </div>
    <?php endif; ?>
    
    <!-- Filters and Add Button -->
    <div class="card" style="margin-bottom: 2rem;">
        <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem;">
            <form method="GET" action="" style="display: flex; gap: 1rem; align-items: center;">
                <div>
                    <label for="status">Filter by Status:</label>
                    <select id="status" name="status" onchange="this.form.submit()">
                        <option value="">All Vaccinations</option>
                        <option value="current" <?php echo $status_filter === 'current' ? 'selected' : ''; ?>>Current</option>
                        <option value="due_soon" <?php echo $status_filter === 'due_soon' ? 'selected' : ''; ?>>Due Soon (30 days)</option>
                        <option value="overdue" <?php echo $status_filter === 'overdue' ? 'selected' : ''; ?>>Overdue</option>
                    </select>
                </div>
                <a href="vaccinationTracker.php" class="btn btn-secondary">Clear Filters</a>
            </form>
            
            <button onclick="openAddModal()" class="btn">Add Vaccination Record</button>
        </div>
    </div>
    
    <!-- Vaccinations Table -->
    <?php if (!empty($vaccinations)): ?>
        <div class="card">
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Pet</th>
                            <th>Vaccine</th>
                            <th>Vaccination Date</th>
                            <th>Next Due</th>
                            <th>Veterinarian</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($vaccinations as $vaccination): ?>
                        <tr>
                            <td>
                                <strong><?php echo htmlspecialchars($vaccination['pet_name']); ?></strong><br>
                                <small><?php echo ucfirst($vaccination['species']); ?> - <?php echo htmlspecialchars($vaccination['breed']); ?></small>
                            </td>
                            <td><?php echo htmlspecialchars($vaccination['vaccine_name']); ?></td>
                            <td><?php echo date('M j, Y', strtotime($vaccination['vaccination_date'])); ?></td>
                            <td>
                                <?php if ($vaccination['next_due_date']): ?>
                                    <?php 
                                    $due_date = strtotime($vaccination['next_due_date']);
                                    $today = time();
                                    $days_until_due = ceil(($due_date - $today) / (60 * 60 * 24));
                                    
                                    if ($days_until_due < 0) {
                                        echo '<span style="color: #e74c3c;">' . date('M j, Y', $due_date) . '</span><br><small style="color: #e74c3c;">Overdue</small>';
                                    } elseif ($days_until_due <= 30) {
                                        echo '<span style="color: #f39c12;">' . date('M j, Y', $due_date) . '</span><br><small style="color: #f39c12;">Due soon</small>';
                                    } else {
                                        echo date('M j, Y', $due_date);
                                    }
                                    ?>
                                <?php else: ?>
                                    <span style="color: #666;">Not specified</span>
                                <?php endif; ?>
                            </td>
                            <td><?php echo $vaccination['veterinarian'] ? htmlspecialchars($vaccination['veterinarian']) : '-'; ?></td>
                            <td>
                                <?php if ($vaccination['next_due_date']): ?>
                                    <?php 
                                    $due_date = strtotime($vaccination['next_due_date']);
                                    $today = time();
                                    $days_until_due = ceil(($due_date - $today) / (60 * 60 * 24));
                                    
                                    if ($days_until_due < 0) {
                                        echo '<span class="badge badge-danger">Overdue</span>';
                                    } elseif ($days_until_due <= 30) {
                                        echo '<span class="badge badge-warning">Due Soon</span>';
                                    } else {
                                        echo '<span class="badge badge-success">Current</span>';
                                    }
                                    ?>
                                <?php else: ?>
                                    <span class="badge badge-secondary">Unknown</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div style="display: flex; gap: 0.5rem; flex-wrap: wrap;">
                                    <button onclick="editVaccination(<?php echo $vaccination['id']; ?>)" 
                                            class="btn btn-warning" style="padding: 0.25rem 0.5rem; font-size: 0.8rem;">
                                        Edit
                                    </button>
                                    <button onclick="confirmDelete('Are you sure you want to delete this vaccination record?')" 
                                            class="btn btn-danger" style="padding: 0.25rem 0.5rem; font-size: 0.8rem;">
                                        Delete
                                    </button>
                                    <form method="POST" style="display: inline;">
                                        <input type="hidden" name="vaccination_id" value="<?php echo $vaccination['id']; ?>">
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
                            <a href="?page=<?php echo $page - 1; ?>&status=<?php echo $status_filter; ?>" 
                               class="btn btn-secondary">Previous</a>
                        <?php endif; ?>
                        
                        <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                            <a href="?page=<?php echo $i; ?>&status=<?php echo $status_filter; ?>" 
                               class="btn <?php echo $i === $page ? 'btn-primary' : 'btn-secondary'; ?>">
                                <?php echo $i; ?>
                            </a>
                        <?php endfor; ?>
                        
                        <?php if ($page < $total_pages): ?>
                            <a href="?page=<?php echo $page + 1; ?>&status=<?php echo $status_filter; ?>" 
                               class="btn btn-secondary">Next</a>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>
        
    <?php else: ?>
        <div class="card text-center" style="padding: 3rem;">
            <div style="font-size: 4rem; margin-bottom: 1rem;">💉</div>
            <h3 style="color: #666; margin-bottom: 1rem;">No vaccination records found</h3>
            <p style="color: #666; margin-bottom: 2rem;">
                <?php if ($status_filter): ?>
                    No vaccination records match your current filter. Try adjusting your search criteria.
                <?php else: ?>
                    You haven't added any vaccination records yet. Start by adding your first vaccination record!
                <?php endif; ?>
            </p>
            <button onclick="openAddModal()" class="btn">Add First Vaccination Record</button>
        </div>
    <?php endif; ?>
</div>

<!-- Add/Edit Vaccination Modal -->
<div id="vaccinationModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1000;">
    <div style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); background: white; padding: 2rem; border-radius: 10px; width: 500px; max-height: 80vh; overflow-y: auto;">
        <h3 style="margin-bottom: 1rem;" id="modalTitle">Add Vaccination Record</h3>
        <form method="POST" id="vaccinationForm">
            <input type="hidden" name="action" id="formAction" value="add">
            <input type="hidden" name="vaccination_id" id="vaccinationId">
            
            <div class="form-group">
                <label for="pet_id">Pet *</label>
                <select id="pet_id" name="pet_id" required>
                    <option value="">Select a pet</option>
                    <?php foreach ($pets as $pet): ?>
                        <option value="<?php echo $pet['id']; ?>">
                            <?php echo htmlspecialchars($pet['name'] . ' (' . ucfirst($pet['species']) . ' - ' . $pet['breed'] . ')'); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="form-group">
                <label for="vaccine_name">Vaccine Name *</label>
                <input type="text" id="vaccine_name" name="vaccine_name" required>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="vaccination_date">Vaccination Date *</label>
                    <input type="date" id="vaccination_date" name="vaccination_date" required>
                </div>
                
                <div class="form-group">
                    <label for="next_due_date">Next Due Date</label>
                    <input type="date" id="next_due_date" name="next_due_date">
                </div>
            </div>
            
            <div class="form-group">
                <label for="veterinarian">Veterinarian</label>
                <input type="text" id="veterinarian" name="veterinarian">
            </div>
            
            <div class="form-group">
                <label for="notes">Notes</label>
                <textarea id="notes" name="notes" rows="3"></textarea>
            </div>
            
            <div style="display: flex; gap: 1rem; justify-content: flex-end;">
                <button type="button" onclick="closeVaccinationModal()" class="btn btn-secondary">Cancel</button>
                <button type="submit" class="btn">Save Record</button>
            </div>
        </form>
    </div>
</div>

<script>
function openAddModal() {
    document.getElementById('modalTitle').textContent = 'Add Vaccination Record';
    document.getElementById('formAction').value = 'add';
    document.getElementById('vaccinationForm').reset();
    document.getElementById('vaccinationId').value = '';
    document.getElementById('vaccinationModal').style.display = 'block';
}

function editVaccination(vaccinationId) {
    // In a real application, you would fetch the vaccination data via AJAX
    // For now, we'll show a placeholder form
    document.getElementById('modalTitle').textContent = 'Edit Vaccination Record';
    document.getElementById('formAction').value = 'update';
    document.getElementById('vaccinationId').value = vaccinationId;
    document.getElementById('vaccinationModal').style.display = 'block';
}

function closeVaccinationModal() {
    document.getElementById('vaccinationModal').style.display = 'none';
}

// Close modal when clicking outside
document.getElementById('vaccinationModal').addEventListener('click', function(e) {
    if (e.target === this) {
        closeVaccinationModal();
    }
});

// Form validation
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('vaccinationForm');
    const vaccinationDate = document.getElementById('vaccination_date');
    const nextDueDate = document.getElementById('next_due_date');
    
    // Date validation
    function validateDates() {
        if (vaccinationDate.value && nextDueDate.value) {
            const vacDate = new Date(vaccinationDate.value);
            const nextDate = new Date(nextDueDate.value);
            
            if (nextDate <= vacDate) {
                nextDueDate.setCustomValidity('Next due date must be after vaccination date');
            } else {
                nextDueDate.setCustomValidity('');
            }
        }
    }
    
    vaccinationDate.addEventListener('change', validateDates);
    nextDueDate.addEventListener('change', validateDates);
    
    form.addEventListener('submit', function(e) {
        validateDates();
        if (!form.checkValidity()) {
            e.preventDefault();
        }
    });
});
</script>

<?php include '../common/footer.php'; ?>