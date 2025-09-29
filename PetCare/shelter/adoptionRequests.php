<?php
require_once '../config/db.php';
requireUserType('shelter');

$page_title = 'Adoption Requests';
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

// Handle adoption request updates
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action'])) {
    $request_id = (int)$_POST['request_id'];
    $action = $_POST['action'];
    
    try {
        if ($action === 'update_status') {
            $new_status = $_POST['new_status'];
            $notes = sanitizeInput($_POST['notes']);
            
            $stmt = $pdo->prepare("
                UPDATE adoption_requests 
                SET status = ?, notes = ? 
                WHERE id = ? AND shelter_id = ?
            ");
            $stmt->execute([$new_status, $notes, $request_id, $shelter_id]);
            
            // If approved, update pet status
            if ($new_status === 'approved') {
                $stmt = $pdo->prepare("
                    UPDATE pets p 
                    JOIN adoption_requests ar ON p.id = ar.pet_id 
                    SET p.status = 'pending' 
                    WHERE ar.id = ? AND p.shelter_id = ?
                ");
                $stmt->execute([$request_id, $shelter_id]);
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

// Filter parameters
$status_filter = $_GET['status'] ?? '';

try {
    // Build query with filters
    $where_conditions = ["ar.shelter_id = ?"];
    $params = [$shelter_id];
    
    if ($status_filter) {
        $where_conditions[] = "ar.status = ?";
        $params[] = $status_filter;
    }
    
    $where_clause = implode(' AND ', $where_conditions);
    
    // Get total count
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as count 
        FROM adoption_requests ar 
        WHERE $where_clause
    ");
    $stmt->execute($params);
    $total_requests = $stmt->fetch()['count'];
    $total_pages = ceil($total_requests / $limit);
    
    // Get adoption requests
    $stmt = $pdo->prepare("
        SELECT ar.*, 
               p.name as pet_name, p.species, p.breed, p.age, p.gender, p.photo_path,
               u.first_name, u.last_name, u.email, u.phone, u.address
        FROM adoption_requests ar
        JOIN pets p ON ar.pet_id = p.id
        JOIN users u ON ar.adopter_id = u.id
        WHERE $where_clause
        ORDER BY ar.request_date DESC 
        LIMIT ? OFFSET ?
    ");
    $params[] = $limit;
    $params[] = $offset;
    $stmt->execute($params);
    $adoption_requests = $stmt->fetchAll();
    
} catch(PDOException $e) {
    $adoption_requests = [];
    $total_pages = 0;
}
?>

<?php include '../common/header.php'; ?>
<?php include '../common/navbar_shelter.php'; ?>

<div class="main-content">
    <h1 style="color: #333; margin-bottom: 2rem;">💝 Adoption Requests - <?php echo htmlspecialchars($shelter['shelter_name']); ?></h1>
    
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
                        <option value="pending" <?php echo $status_filter === 'pending' ? 'selected' : ''; ?>>Pending</option>
                        <option value="approved" <?php echo $status_filter === 'approved' ? 'selected' : ''; ?>>Approved</option>
                        <option value="rejected" <?php echo $status_filter === 'rejected' ? 'selected' : ''; ?>>Rejected</option>
                        <option value="completed" <?php echo $status_filter === 'completed' ? 'selected' : ''; ?>>Completed</option>
                    </select>
                </div>
                
                <div class="form-group" style="display: flex; align-items: end;">
                    <a href="adoptionRequests.php" class="btn btn-secondary">Clear Filters</a>
                </div>
            </div>
        </form>
    </div>
    
    <!-- Adoption Requests -->
    <?php if (!empty($adoption_requests)): ?>
        <div class="grid grid-1" style="margin-bottom: 3rem;">
            <?php foreach ($adoption_requests as $request): ?>
                <div class="card" style="margin-bottom: 1.5rem;">
                    <div style="display: flex; gap: 2rem; align-items: start;">
                        <!-- Pet Information -->
                        <div style="flex: 0 0 200px;">
                            <div style="text-align: center; margin-bottom: 1rem;">
                                <?php if ($request['photo_path'] && file_exists('../uploads/' . $request['photo_path'])): ?>
                                    <img src="../uploads/<?php echo htmlspecialchars($request['photo_path']); ?>" 
                                         alt="<?php echo htmlspecialchars($request['pet_name']); ?>" 
                                         style="width: 150px; height: 150px; object-fit: cover; border-radius: 10px;">
                                <?php else: ?>
                                    <div style="width: 150px; height: 150px; background: #f0f0f0; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 3rem;">
                                        🐕
                                    </div>
                                <?php endif; ?>
                            </div>
                            
                            <h3 style="margin-bottom: 0.5rem; color: #333; text-align: center;"><?php echo htmlspecialchars($request['pet_name']); ?></h3>
                            <p style="text-align: center; color: #666; font-size: 0.9rem;">
                                <?php echo ucfirst($request['species']); ?> - <?php echo htmlspecialchars($request['breed']); ?><br>
                                <?php echo $request['age']; ?> years, <?php echo ucfirst($request['gender']); ?>
                            </p>
                        </div>
                        
                        <!-- Adopter Information -->
                        <div style="flex: 1;">
                            <h4 style="color: #333; margin-bottom: 1rem;">Adopter Information</h4>
                            <div class="grid grid-2" style="margin-bottom: 1rem;">
                                <div>
                                    <p><strong>Name:</strong> <?php echo htmlspecialchars($request['first_name'] . ' ' . $request['last_name']); ?></p>
                                    <p><strong>Email:</strong> <?php echo htmlspecialchars($request['email']); ?></p>
                                </div>
                                <div>
                                    <p><strong>Phone:</strong> <?php echo $request['phone'] ? htmlspecialchars($request['phone']) : 'Not provided'; ?></p>
                                    <p><strong>Address:</strong> <?php echo $request['address'] ? htmlspecialchars($request['address']) : 'Not provided'; ?></p>
                                </div>
                            </div>
                            
                            <div style="margin-bottom: 1rem;">
                                <p><strong>Request Date:</strong> <?php echo date('M j, Y g:i A', strtotime($request['request_date'])); ?></p>
                                <p><strong>Status:</strong> 
                                    <span class="badge badge-<?php 
                                        echo $request['status'] === 'pending' ? 'warning' : 
                                            ($request['status'] === 'approved' ? 'success' : 
                                            ($request['status'] === 'rejected' ? 'danger' : 'info')); 
                                    ?>">
                                        <?php echo ucfirst($request['status']); ?>
                                    </span>
                                </p>
                            </div>
                            
                            <?php if ($request['notes']): ?>
                                <div style="margin-bottom: 1rem;">
                                    <p><strong>Adopter Notes:</strong></p>
                                    <p style="color: #666; font-style: italic;"><?php echo htmlspecialchars($request['notes']); ?></p>
                                </div>
                            <?php endif; ?>
                            
                            <?php if ($request['status'] === 'pending'): ?>
                                <div style="display: flex; gap: 1rem; flex-wrap: wrap;">
                                    <button onclick="updateRequestStatus(<?php echo $request['id']; ?>, 'approved')" 
                                            class="btn btn-success">
                                        Approve Request
                                    </button>
                                    <button onclick="updateRequestStatus(<?php echo $request['id']; ?>, 'rejected')" 
                                            class="btn btn-danger">
                                        Reject Request
                                    </button>
                                    <button onclick="viewRequestDetails(<?php echo $request['id']; ?>)" 
                                            class="btn btn-info">
                                        View Details
                                    </button>
                                </div>
                            <?php else: ?>
                                <div style="display: flex; gap: 1rem; flex-wrap: wrap;">
                                    <button onclick="viewRequestDetails(<?php echo $request['id']; ?>)" 
                                            class="btn btn-info">
                                        View Details
                                    </button>
                                    <?php if ($request['status'] === 'approved'): ?>
                                        <button onclick="updateRequestStatus(<?php echo $request['id']; ?>, 'completed')" 
                                                class="btn btn-success">
                                            Mark as Completed
                                        </button>
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
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
        
    <?php else: ?>
        <div class="card text-center" style="padding: 3rem;">
            <div style="font-size: 4rem; margin-bottom: 1rem;">💝</div>
            <h3 style="color: #666; margin-bottom: 1rem;">No adoption requests found</h3>
            <p style="color: #666; margin-bottom: 2rem;">
                <?php if ($status_filter): ?>
                    No adoption requests match your current filter. Try adjusting your search criteria.
                <?php else: ?>
                    You haven't received any adoption requests yet. Make sure your pets are listed and visible to potential adopters.
                <?php endif; ?>
            </p>
            <a href="viewPets.php" class="btn">View Your Pets</a>
        </div>
    <?php endif; ?>
</div>

<!-- Status Update Modal -->
<div id="statusModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1000;">
    <div style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); background: white; padding: 2rem; border-radius: 10px; width: 500px;">
        <h3 style="margin-bottom: 1rem;">Update Adoption Request Status</h3>
        <form method="POST" id="statusForm">
            <input type="hidden" name="request_id" id="statusRequestId">
            <input type="hidden" name="action" value="update_status">
            <input type="hidden" name="new_status" id="newStatus">
            
            <div class="form-group">
                <label for="notes">Notes (optional):</label>
                <textarea id="notes" name="notes" rows="4" 
                          placeholder="Add any notes about this decision..."></textarea>
            </div>
            
            <div style="display: flex; gap: 1rem; justify-content: flex-end;">
                <button type="button" onclick="closeStatusModal()" class="btn btn-secondary">Cancel</button>
                <button type="submit" class="btn" id="submitBtn">Update Status</button>
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
function updateRequestStatus(requestId, status) {
    document.getElementById('statusRequestId').value = requestId;
    document.getElementById('newStatus').value = status;
    
    const submitBtn = document.getElementById('submitBtn');
    if (status === 'approved') {
        submitBtn.textContent = 'Approve Request';
        submitBtn.className = 'btn btn-success';
    } else if (status === 'rejected') {
        submitBtn.textContent = 'Reject Request';
        submitBtn.className = 'btn btn-danger';
    } else if (status === 'completed') {
        submitBtn.textContent = 'Mark as Completed';
        submitBtn.className = 'btn btn-success';
    }
    
    document.getElementById('statusModal').style.display = 'block';
}

function closeStatusModal() {
    document.getElementById('statusModal').style.display = 'none';
}

function viewRequestDetails(requestId) {
    // In a real application, you would fetch the details via AJAX
    // For now, we'll show a placeholder
    document.getElementById('detailsContent').innerHTML = `
        <p>Loading adoption request details...</p>
        <p><strong>Request ID:</strong> ${requestId}</p>
        <p><strong>Status:</strong> Pending</p>
        <p><strong>Notes:</strong> No additional notes available</p>
    `;
    document.getElementById('detailsModal').style.display = 'block';
}

function closeDetailsModal() {
    document.getElementById('detailsModal').style.display = 'none';
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