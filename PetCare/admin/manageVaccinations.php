<?php
require_once '../config/db.php';
requireUserType('admin');

$page_title = 'Manage Vaccinations';
$message = '';
$message_type = '';

// Handle vaccination updates
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action'])) {
    $vaccination_id = (int)$_POST['vaccination_id'];
    $action = $_POST['action'];
    
    try {
        if ($action === 'delete') {
            $stmt = $pdo->prepare("DELETE FROM vaccinations WHERE id = ?");
            $stmt->execute([$vaccination_id]);
            $message = 'Vaccination record deleted successfully.';
            $message_type = 'success';
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
                WHERE id = ?
            ");
            $stmt->execute([$vaccine_name, $vaccination_date, $next_due_date, $veterinarian, $notes, $vaccination_id]);
            $message = 'Vaccination record updated successfully.';
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

try {
    // Get total count
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM vaccinations");
    $total_vaccinations = $stmt->fetch()['count'];
    $total_pages = ceil($total_vaccinations / $limit);
    
    // Get vaccinations with related information
    $stmt = $pdo->prepare("
        SELECT v.*, p.name as pet_name, p.species, p.breed, s.shelter_name
        FROM vaccinations v
        JOIN pets p ON v.pet_id = p.id
        JOIN shelters s ON p.shelter_id = s.id
        ORDER BY v.vaccination_date DESC 
        LIMIT ? OFFSET ?
    ");
    $stmt->execute([$limit, $offset]);
    $vaccinations = $stmt->fetchAll();
    
} catch(PDOException $e) {
    $vaccinations = [];
    $total_pages = 0;
}
?>

<?php include '../common/header.php'; ?>
<?php include '../common/navbar_admin.php'; ?>

<div class="main-content">
    <h1 style="color: #333; margin-bottom: 2rem;">💉 Manage Vaccinations</h1>
    
    <?php if ($message): ?>
        <div class="alert alert-<?php echo $message_type; ?>">
            <?php echo $message; ?>
        </div>
    <?php endif; ?>
    
    <!-- Vaccination Statistics -->
    <div class="grid grid-4" style="margin-bottom: 3rem;">
        <?php
        try {
            // Get vaccination statistics
            $stmt = $pdo->query("SELECT COUNT(*) as count FROM vaccinations");
            $total_vaccinations = $stmt->fetch()['count'];
            
            $stmt = $pdo->query("
                SELECT COUNT(*) as count FROM vaccinations 
                WHERE next_due_date <= DATE_ADD(NOW(), INTERVAL 30 DAY)
            ");
            $due_soon = $stmt->fetch()['count'];
            
            $stmt = $pdo->query("
                SELECT COUNT(*) as count FROM vaccinations 
                WHERE next_due_date < NOW()
            ");
            $overdue = $stmt->fetch()['count'];
            
            $stmt = $pdo->query("
                SELECT COUNT(DISTINCT pet_id) as count FROM vaccinations
            ");
            $vaccinated_pets = $stmt->fetch()['count'];
            
        } catch(PDOException $e) {
            $total_vaccinations = $due_soon = $overdue = $vaccinated_pets = 0;
        }
        ?>
        
        <div class="card text-center">
            <div style="font-size: 2rem; color: #667eea; margin-bottom: 0.5rem;">
                <?php echo $total_vaccinations; ?>
            </div>
            <h4>Total Vaccinations</h4>
        </div>
        
        <div class="card text-center">
            <div style="font-size: 2rem; color: #f39c12; margin-bottom: 0.5rem;">
                <?php echo $due_soon; ?>
            </div>
            <h4>Due Soon (30 days)</h4>
        </div>
        
        <div class="card text-center">
            <div style="font-size: 2rem; color: #e74c3c; margin-bottom: 0.5rem;">
                <?php echo $overdue; ?>
            </div>
            <h4>Overdue</h4>
        </div>
        
        <div class="card text-center">
            <div style="font-size: 2rem; color: #27ae60; margin-bottom: 0.5rem;">
                <?php echo $vaccinated_pets; ?>
            </div>
            <h4>Vaccinated Pets</h4>
        </div>
    </div>
    
    <!-- Vaccinations Table -->
    <div class="card">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
            <h2 style="color: #333; margin: 0;">All Vaccination Records</h2>
            <div>
                <input type="text" id="search-vaccinations" placeholder="Search vaccinations..." 
                       style="padding: 0.5rem; border: 2px solid #ddd; border-radius: 5px; width: 250px;"
                       onkeyup="filterTable('search-vaccinations', 'vaccinations-table')">
            </div>
        </div>
        
        <?php if (!empty($vaccinations)): ?>
            <div class="table-responsive">
                <table class="table" id="vaccinations-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Pet</th>
                            <th>Vaccine</th>
                            <th>Vaccination Date</th>
                            <th>Next Due</th>
                            <th>Veterinarian</th>
                            <th>Shelter</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($vaccinations as $vaccination): ?>
                        <tr>
                            <td><?php echo $vaccination['id']; ?></td>
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
                            <td><?php echo htmlspecialchars($vaccination['shelter_name']); ?></td>
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
            <p style="color: #666; text-align: center; padding: 2rem;">No vaccination records found.</p>
        <?php endif; ?>
    </div>
    
    <!-- Vaccination Management Tools -->
    <div class="grid grid-2" style="margin-top: 3rem;">
        <div class="card">
            <h3 style="color: #333; margin-bottom: 1.5rem;">📊 Vaccination Analytics</h3>
            
            <?php
            try {
                // Get vaccinations by month
                $stmt = $pdo->prepare("
                    SELECT DATE_FORMAT(vaccination_date, '%Y-%m') as month, COUNT(*) as count 
                    FROM vaccinations 
                    WHERE vaccination_date >= DATE_SUB(NOW(), INTERVAL 12 MONTH)
                    GROUP BY DATE_FORMAT(vaccination_date, '%Y-%m') 
                    ORDER BY month DESC 
                    LIMIT 6
                ");
                $stmt->execute();
                $monthly_stats = $stmt->fetchAll();
                
                // Get most common vaccines
                $stmt = $pdo->query("
                    SELECT vaccine_name, COUNT(*) as count 
                    FROM vaccinations 
                    GROUP BY vaccine_name 
                    ORDER BY count DESC 
                    LIMIT 5
                ");
                $common_vaccines = $stmt->fetchAll();
                
            } catch(PDOException $e) {
                $monthly_stats = [];
                $common_vaccines = [];
            }
            ?>
            
            <h4 style="color: #667eea; margin-bottom: 1rem;">Most Common Vaccines</h4>
            <?php if (!empty($common_vaccines)): ?>
                <div style="margin-bottom: 2rem;">
                    <?php foreach ($common_vaccines as $vaccine): ?>
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.5rem;">
                            <span><?php echo htmlspecialchars($vaccine['vaccine_name']); ?></span>
                            <span class="badge badge-info"><?php echo $vaccine['count']; ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
            
            <h4 style="color: #667eea; margin-bottom: 1rem;">Monthly Vaccinations (Last 6 Months)</h4>
            <?php if (!empty($monthly_stats)): ?>
                <div style="margin-bottom: 1rem;">
                    <?php foreach ($monthly_stats as $stat): ?>
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.5rem;">
                            <span><?php echo date('M Y', strtotime($stat['month'] . '-01')); ?></span>
                            <span class="badge badge-success"><?php echo $stat['count']; ?> vaccinations</span>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <p style="color: #666;">No data available.</p>
            <?php endif; ?>
        </div>
        
        <div class="card">
            <h3 style="color: #333; margin-bottom: 1.5rem;">🔧 Vaccination Management</h3>
            
            <div style="margin-bottom: 1.5rem;">
                <h4 style="color: #667eea; margin-bottom: 1rem;">Quick Actions</h4>
                <div style="display: flex; flex-direction: column; gap: 0.5rem;">
                    <button class="btn btn-secondary" onclick="exportVaccinations()">Export Vaccination Data</button>
                    <button class="btn btn-warning" onclick="sendReminderEmails()">Send Due Reminders</button>
                    <button class="btn btn-info" onclick="generateVaccinationReport()">Generate Report</button>
                </div>
            </div>
            
            <div>
                <h4 style="color: #667eea; margin-bottom: 1rem;">System Maintenance</h4>
                <div style="display: flex; flex-direction: column; gap: 0.5rem;">
                    <button class="btn btn-danger" onclick="cleanupOldRecords()">Cleanup Old Records</button>
                    <button class="btn btn-warning" onclick="updateVaccinationStatus()">Update Pet Status</button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Edit Vaccination Modal -->
<div id="editModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1000;">
    <div style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); background: white; padding: 2rem; border-radius: 10px; width: 500px;">
        <h3 style="margin-bottom: 1rem;">Edit Vaccination Record</h3>
        <form method="POST" id="editForm">
            <input type="hidden" name="vaccination_id" id="editVaccinationId">
            <input type="hidden" name="action" value="update">
            
            <div class="form-group">
                <label for="vaccine_name">Vaccine Name:</label>
                <input type="text" id="vaccine_name" name="vaccine_name" required>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="vaccination_date">Vaccination Date:</label>
                    <input type="date" id="vaccination_date" name="vaccination_date" required>
                </div>
                
                <div class="form-group">
                    <label for="next_due_date">Next Due Date:</label>
                    <input type="date" id="next_due_date" name="next_due_date">
                </div>
            </div>
            
            <div class="form-group">
                <label for="veterinarian">Veterinarian:</label>
                <input type="text" id="veterinarian" name="veterinarian">
            </div>
            
            <div class="form-group">
                <label for="notes">Notes:</label>
                <textarea id="notes" name="notes" rows="3"></textarea>
            </div>
            
            <div style="display: flex; gap: 1rem; justify-content: flex-end;">
                <button type="button" onclick="closeEditModal()" class="btn btn-secondary">Cancel</button>
                <button type="submit" class="btn">Update Record</button>
            </div>
        </form>
    </div>
</div>

<script>
function editVaccination(vaccinationId) {
    // In a real application, you would fetch the vaccination data via AJAX
    // For now, we'll show a placeholder form
    document.getElementById('editVaccinationId').value = vaccinationId;
    document.getElementById('editModal').style.display = 'block';
}

function closeEditModal() {
    document.getElementById('editModal').style.display = 'none';
}

function exportVaccinations() {
    if (confirm('Export all vaccination data to CSV?')) {
        window.location.href = 'exportVaccinations.php';
    }
}

function sendReminderEmails() {
    if (confirm('Send reminder emails for overdue and due soon vaccinations?')) {
        alert('Reminder email feature coming soon!');
    }
}

function generateVaccinationReport() {
    if (confirm('Generate vaccination analytics report?')) {
        window.location.href = 'vaccinationReport.php';
    }
}

function cleanupOldRecords() {
    if (confirm('This will remove vaccination records older than 5 years. Continue?')) {
        alert('Cleanup feature coming soon!');
    }
}

function updateVaccinationStatus() {
    if (confirm('Update pet vaccination status based on current records?')) {
        alert('Status update feature coming soon!');
    }
}

// Close modal when clicking outside
document.getElementById('editModal').addEventListener('click', function(e) {
    if (e.target === this) {
        closeEditModal();
    }
});
</script>

<?php include '../common/footer.php'; ?>