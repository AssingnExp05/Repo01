<?php
require_once '../config/db.php';
requireUserType('admin');

$page_title = 'Manage Adoption Requests';
$message = '';
$message_type = '';

// Handle adoption status updates
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action'])) {
    $request_id = (int)$_POST['request_id'];
    $action = $_POST['action'];
    
    try {
        if ($action === 'update_status') {
            $new_status = $_POST['new_status'];
            $admin_notes = sanitizeInput($_POST['admin_notes']);
            
            $stmt = $pdo->prepare("
                UPDATE adoption_requests 
                SET status = ?, admin_notes = ?, approved_by = ?, approved_at = NOW() 
                WHERE id = ?
            ");
            $stmt->execute([$new_status, $admin_notes, $_SESSION['user_id'], $request_id]);
            
            // If approved, update pet status
            if ($new_status === 'approved') {
                $stmt = $pdo->prepare("
                    UPDATE pets p 
                    JOIN adoption_requests ar ON p.id = ar.pet_id 
                    SET p.status = 'pending' 
                    WHERE ar.id = ?
                ");
                $stmt->execute([$request_id]);
            }
            
            $message = 'Adoption request updated successfully.';
            $message_type = 'success';
        }
    } catch(PDOException $e) {
        $message = 'Error updating adoption request: ' . $e->getMessage();
        $message_type = 'error';
    }
}

// Get adoption requests with pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 10;
$offset = ($page - 1) * $limit;

try {
    // Get total count
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM adoption_requests");
    $total_requests = $stmt->fetch()['count'];
    $total_pages = ceil($total_requests / $limit);
    
    // Get adoption requests with related information
    $stmt = $pdo->prepare("
        SELECT ar.*, 
               p.name as pet_name, p.species, p.breed, p.age, p.gender,
               ua.first_name as adopter_first_name, ua.last_name as adopter_last_name, ua.email as adopter_email,
               s.shelter_name, us.first_name as shelter_first_name, us.last_name as shelter_last_name,
               admin.first_name as admin_first_name, admin.last_name as admin_last_name
        FROM adoption_requests ar
        JOIN pets p ON ar.pet_id = p.id
        JOIN users ua ON ar.adopter_id = ua.id
        JOIN shelters s ON ar.shelter_id = s.id
        JOIN users us ON s.user_id = us.id
        LEFT JOIN users admin ON ar.approved_by = admin.id
        ORDER BY ar.request_date DESC 
        LIMIT ? OFFSET ?
    ");
    $stmt->execute([$limit, $offset]);
    $adoption_requests = $stmt->fetchAll();
    
} catch(PDOException $e) {
    $adoption_requests = [];
    $total_pages = 0;
}
?>

<?php include '../common/header.php'; ?>
<?php include '../common/navbar_admin.php'; ?>

<div class="main-content">
    <h1 style="color: #333; margin-bottom: 2rem;">💝 Manage Adoption Requests</h1>
    
    <?php if ($message): ?>
        <div class="alert alert-<?php echo $message_type; ?>">
            <?php echo $message; ?>
        </div>
    <?php endif; ?>
    
    <!-- Adoption Statistics -->
    <div class="grid grid-4" style="margin-bottom: 3rem;">
        <?php
        try {
            $stmt = $pdo->query("SELECT status, COUNT(*) as count FROM adoption_requests GROUP BY status");
            $adoption_stats = $stmt->fetchAll();
            $stats = array_column($adoption_stats, 'count', 'status');
        } catch(PDOException $e) {
            $stats = ['pending' => 0, 'approved' => 0, 'rejected' => 0, 'completed' => 0];
        }
        ?>
        
        <div class="card text-center">
            <div style="font-size: 2rem; color: #f39c12; margin-bottom: 0.5rem;">
                <?php echo $stats['pending'] ?? 0; ?>
            </div>
            <h4>Pending</h4>
        </div>
        
        <div class="card text-center">
            <div style="font-size: 2rem; color: #27ae60; margin-bottom: 0.5rem;">
                <?php echo $stats['approved'] ?? 0; ?>
            </div>
            <h4>Approved</h4>
        </div>
        
        <div class="card text-center">
            <div style="font-size: 2rem; color: #e74c3c; margin-bottom: 0.5rem;">
                <?php echo $stats['rejected'] ?? 0; ?>
            </div>
            <h4>Rejected</h4>
        </div>
        
        <div class="card text-center">
            <div style="font-size: 2rem; color: #667eea; margin-bottom: 0.5rem;">
                <?php echo $stats['completed'] ?? 0; ?>
            </div>
            <h4>Completed</h4>
        </div>
    </div>
    
    <!-- Adoption Requests Table -->
    <div class="card">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
            <h2 style="color: #333; margin: 0;">All Adoption Requests</h2>
            <div>
                <input type="text" id="search-adoptions" placeholder="Search adoption requests..." 
                       style="padding: 0.5rem; border: 2px solid #ddd; border-radius: 5px; width: 250px;"
                       onkeyup="filterTable('search-adoptions', 'adoptions-table')">
            </div>
        </div>
        
        <?php if (!empty($adoption_requests)): ?>
            <div class="table-responsive">
                <table class="table" id="adoptions-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Pet</th>
                            <th>Adopter</th>
                            <th>Shelter</th>
                            <th>Request Date</th>
                            <th>Status</th>
                            <th>Approved By</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($adoption_requests as $request): ?>
                        <tr>
                            <td><?php echo $request['id']; ?></td>
                            <td>
                                <strong><?php echo htmlspecialchars($request['pet_name']); ?></strong><br>
                                <small><?php echo ucfirst($request['species']); ?> - <?php echo htmlspecialchars($request['breed']); ?></small><br>
                                <small><?php echo $request['age']; ?> years, <?php echo ucfirst($request['gender']); ?></small>
                            </td>
                            <td>
                                <strong><?php echo htmlspecialchars($request['adopter_first_name'] . ' ' . $request['adopter_last_name']); ?></strong><br>
                                <small><?php echo htmlspecialchars($request['adopter_email']); ?></small>
                            </td>
                            <td>
                                <strong><?php echo htmlspecialchars($request['shelter_name']); ?></strong><br>
                                <small><?php echo htmlspecialchars($request['shelter_first_name'] . ' ' . $request['shelter_last_name']); ?></small>
                            </td>
                            <td><?php echo date('M j, Y g:i A', strtotime($request['request_date'])); ?></td>
                            <td>
                                <span class="badge badge-<?php 
                                    echo $request['status'] === 'pending' ? 'warning' : 
                                        ($request['status'] === 'approved' ? 'success' : 
                                        ($request['status'] === 'rejected' ? 'danger' : 'info')); 
                                ?>">
                                    <?php echo ucfirst($request['status']); ?>
                                </span>
                            </td>
                            <td>
                                <?php if ($request['admin_first_name']): ?>
                                    <?php echo htmlspecialchars($request['admin_first_name'] . ' ' . $request['admin_last_name']); ?><br>
                                    <small><?php echo date('M j, Y', strtotime($request['approved_at'])); ?></small>
                                <?php else: ?>
                                    <span style="color: #666;">Not approved</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div style="display: flex; gap: 0.5rem; flex-wrap: wrap;">
                                    <button onclick="viewAdoptionDetails(<?php echo $request['id']; ?>)" 
                                            class="btn btn-info" style="padding: 0.25rem 0.5rem; font-size: 0.8rem;">
                                        View Details
                                    </button>
                                    <?php if ($request['status'] === 'pending'): ?>
                                        <button onclick="updateAdoptionStatus(<?php echo $request['id']; ?>)" 
                                                class="btn btn-warning" style="padding: 0.25rem 0.5rem; font-size: 0.8rem;">
                                            Update Status
                                        </button>
                                    <?php endif; ?>
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
            <p style="color: #666; text-align: center; padding: 2rem;">No adoption requests found.</p>
        <?php endif; ?>
    </div>
    
    <!-- Adoption Management Tools -->
    <div class="grid grid-2" style="margin-top: 3rem;">
        <div class="card">
            <h3 style="color: #333; margin-bottom: 1.5rem;">📊 Adoption Analytics</h3>
            
            <?php
            try {
                // Get adoption requests by month
                $stmt = $pdo->prepare("
                    SELECT DATE_FORMAT(request_date, '%Y-%m') as month, COUNT(*) as count 
                    FROM adoption_requests 
                    WHERE request_date >= DATE_SUB(NOW(), INTERVAL 12 MONTH)
                    GROUP BY DATE_FORMAT(request_date, '%Y-%m') 
                    ORDER BY month DESC 
                    LIMIT 6
                ");
                $stmt->execute();
                $monthly_stats = $stmt->fetchAll();
                
                // Get success rate
                $stmt = $pdo->query("
                    SELECT 
                        COUNT(*) as total,
                        SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed
                    FROM adoption_requests
                ");
                $success_rate = $stmt->fetch();
                
            } catch(PDOException $e) {
                $monthly_stats = [];
                $success_rate = ['total' => 0, 'completed' => 0];
            }
            ?>
            
            <h4 style="color: #667eea; margin-bottom: 1rem;">Success Rate</h4>
            <div style="margin-bottom: 2rem;">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.5rem;">
                    <span>Total Requests</span>
                    <span class="badge badge-info"><?php echo $success_rate['total']; ?></span>
                </div>
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.5rem;">
                    <span>Completed</span>
                    <span class="badge badge-success"><?php echo $success_rate['completed']; ?></span>
                </div>
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.5rem;">
                    <span>Success Rate</span>
                    <span class="badge badge-<?php echo $success_rate['total'] > 0 ? 'success' : 'secondary'; ?>">
                        <?php echo $success_rate['total'] > 0 ? round(($success_rate['completed'] / $success_rate['total']) * 100, 1) : 0; ?>%
                    </span>
                </div>
            </div>
            
            <h4 style="color: #667eea; margin-bottom: 1rem;">Monthly Requests (Last 6 Months)</h4>
            <?php if (!empty($monthly_stats)): ?>
                <div style="margin-bottom: 1rem;">
                    <?php foreach ($monthly_stats as $stat): ?>
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.5rem;">
                            <span><?php echo date('M Y', strtotime($stat['month'] . '-01')); ?></span>
                            <span class="badge badge-info"><?php echo $stat['count']; ?> requests</span>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <p style="color: #666;">No data available.</p>
            <?php endif; ?>
        </div>
        
        <div class="card">
            <h3 style="color: #333; margin-bottom: 1.5rem;">🔧 Adoption Management</h3>
            
            <div style="margin-bottom: 1.5rem;">
                <h4 style="color: #667eea; margin-bottom: 1rem;">Quick Actions</h4>
                <div style="display: flex; flex-direction: column; gap: 0.5rem;">
                    <button class="btn btn-secondary" onclick="exportAdoptions()">Export Adoption Data</button>
                    <button class="btn btn-warning" onclick="bulkUpdateAdoptions()">Bulk Status Update</button>
                    <button class="btn btn-info" onclick="generateAdoptionReport()">Generate Report</button>
                </div>
            </div>
            
            <div>
                <h4 style="color: #667eea; margin-bottom: 1rem;">System Maintenance</h4>
                <div style="display: flex; flex-direction: column; gap: 0.5rem;">
                    <button class="btn btn-danger" onclick="cleanupOldRequests()">Cleanup Old Requests</button>
                    <button class="btn btn-warning" onclick="sendReminderEmails()">Send Reminder Emails</button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Status Update Modal -->
<div id="statusModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1000;">
    <div style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); background: white; padding: 2rem; border-radius: 10px; width: 500px;">
        <h3 style="margin-bottom: 1rem;">Update Adoption Status</h3>
        <form method="POST" id="statusForm">
            <input type="hidden" name="request_id" id="statusRequestId">
            <input type="hidden" name="action" value="update_status">
            
            <div class="form-group">
                <label for="new_status">New Status:</label>
                <select id="new_status" name="new_status" required>
                    <option value="">Select status</option>
                    <option value="approved">Approved</option>
                    <option value="rejected">Rejected</option>
                    <option value="completed">Completed</option>
                </select>
            </div>
            
            <div class="form-group">
                <label for="admin_notes">Admin Notes:</label>
                <textarea id="admin_notes" name="admin_notes" rows="4" 
                          placeholder="Add any notes about this decision..."></textarea>
            </div>
            
            <div style="display: flex; gap: 1rem; justify-content: flex-end;">
                <button type="button" onclick="closeStatusModal()" class="btn btn-secondary">Cancel</button>
                <button type="submit" class="btn">Update Status</button>
            </div>
        </form>
    </div>
</div>

<!-- Details Modal -->
<div id="detailsModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1000;">
    <div style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); background: white; padding: 2rem; border-radius: 10px; width: 600px; max-height: 80vh; overflow-y: auto;">
        <h3 style="margin-bottom: 1rem;">Adoption Request Details</h3>
        <div id="detailsContent"></div>
        <div style="text-align: center; margin-top: 2rem;">
            <button onclick="closeDetailsModal()" class="btn btn-secondary">Close</button>
        </div>
    </div>
</div>

<script>
function updateAdoptionStatus(requestId) {
    document.getElementById('statusRequestId').value = requestId;
    document.getElementById('statusModal').style.display = 'block';
}

function closeStatusModal() {
    document.getElementById('statusModal').style.display = 'none';
}

function viewAdoptionDetails(requestId) {
    // In a real application, you would fetch the details via AJAX
    // For now, we'll show a placeholder
    document.getElementById('detailsContent').innerHTML = `
        <p>Loading adoption request details...</p>
        <p><strong>Request ID:</strong> ${requestId}</p>
        <p><strong>Status:</strong> Pending</p>
        <p><strong>Notes:</strong> No notes available</p>
    `;
    document.getElementById('detailsModal').style.display = 'block';
}

function closeDetailsModal() {
    document.getElementById('detailsModal').style.display = 'none';
}

function exportAdoptions() {
    if (confirm('Export all adoption data to CSV?')) {
        window.location.href = 'exportAdoptions.php';
    }
}

function bulkUpdateAdoptions() {
    alert('Bulk status update feature coming soon!');
}

function generateAdoptionReport() {
    if (confirm('Generate adoption analytics report?')) {
        window.location.href = 'adoptionReport.php';
    }
}

function cleanupOldRequests() {
    if (confirm('This will remove adoption requests older than 1 year. Continue?')) {
        alert('Cleanup feature coming soon!');
    }
}

function sendReminderEmails() {
    if (confirm('Send reminder emails to pending adoption requests?')) {
        alert('Reminder email feature coming soon!');
    }
}

// Close modals when clicking outside
document.getElementById('statusModal').addEventListener('click', function(e) {
    if (e.target === this) {
        closeStatusModal();
    }
});

document.getElementById('detailsModal').addEventListener('click', function(e) {
    if (e.target === this) {
        closeDetailsModal();
    }
});
</script>

<?php include '../common/footer.php'; ?>