<?php
require_once '../config/db.php';
requireUserType('adopter');

$page_title = 'My Adoption Requests';

// Get adoption requests with pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 10;
$offset = ($page - 1) * $limit;

// Filter parameters
$status_filter = $_GET['status'] ?? '';

try {
    // Build query with filters
    $where_conditions = ["ar.adopter_id = ?"];
    $params = [$_SESSION['user_id']];
    
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
               p.name as pet_name, p.species, p.breed, p.age, p.gender, p.photo_path, p.adoption_fee,
               s.shelter_name, s.license_number,
               u.first_name as shelter_contact_first, u.last_name as shelter_contact_last,
               u.email as shelter_email, u.phone as shelter_phone
        FROM adoption_requests ar
        JOIN pets p ON ar.pet_id = p.id
        JOIN shelters s ON ar.shelter_id = s.id
        JOIN users u ON s.user_id = u.id
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
<?php include '../common/navbar_adopter.php'; ?>

<div class="main-content">
    <h1 style="color: #333; margin-bottom: 2rem;">💝 My Adoption Requests</h1>
    
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
                    <a href="myAdoptions.php" class="btn btn-secondary">Clear Filters</a>
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
                        
                        <!-- Request Details -->
                        <div style="flex: 1;">
                            <div style="display: flex; justify-content: space-between; align-items: start; margin-bottom: 1rem;">
                                <h4 style="color: #333; margin: 0;">Adoption Request #<?php echo $request['id']; ?></h4>
                                <span class="badge badge-<?php 
                                    echo $request['status'] === 'pending' ? 'warning' : 
                                        ($request['status'] === 'approved' ? 'success' : 
                                        ($request['status'] === 'rejected' ? 'danger' : 'info')); 
                                ?>">
                                    <?php echo ucfirst($request['status']); ?>
                                </span>
                            </div>
                            
                            <div class="grid grid-2" style="margin-bottom: 1rem;">
                                <div>
                                    <p><strong>Request Date:</strong> <?php echo date('M j, Y g:i A', strtotime($request['request_date'])); ?></p>
                                    <p><strong>Shelter:</strong> <?php echo htmlspecialchars($request['shelter_name']); ?></p>
                                    <p><strong>Adoption Fee:</strong> $<?php echo number_format($request['adoption_fee'], 2); ?></p>
                                </div>
                                <div>
                                    <p><strong>Shelter Contact:</strong> <?php echo htmlspecialchars($request['shelter_contact_first'] . ' ' . $request['shelter_contact_last']); ?></p>
                                    <p><strong>Email:</strong> <?php echo htmlspecialchars($request['shelter_email']); ?></p>
                                    <p><strong>Phone:</strong> <?php echo $request['shelter_phone'] ? htmlspecialchars($request['shelter_phone']) : 'Not provided'; ?></p>
                                </div>
                            </div>
                            
                            <?php if ($request['notes']): ?>
                                <div style="margin-bottom: 1rem;">
                                    <p><strong>Your Notes:</strong></p>
                                    <p style="color: #666; font-style: italic; background: #f8f9fa; padding: 1rem; border-radius: 5px;">
                                        <?php echo nl2br(htmlspecialchars($request['notes'])); ?>
                                    </p>
                                </div>
                            <?php endif; ?>
                            
                            <?php if ($request['admin_notes']): ?>
                                <div style="margin-bottom: 1rem;">
                                    <p><strong>Shelter Response:</strong></p>
                                    <p style="color: #666; background: #e8f5e8; padding: 1rem; border-radius: 5px;">
                                        <?php echo nl2br(htmlspecialchars($request['admin_notes'])); ?>
                                    </p>
                                </div>
                            <?php endif; ?>
                            
                            <!-- Status-specific information -->
                            <?php if ($request['status'] === 'pending'): ?>
                                <div style="background: #fff3cd; padding: 1rem; border-radius: 5px; margin-bottom: 1rem;">
                                    <p style="margin: 0; color: #856404;">
                                        <strong>⏳ Your request is being reviewed</strong><br>
                                        The shelter will review your application and contact you within 3-5 business days.
                                    </p>
                                </div>
                            <?php elseif ($request['status'] === 'approved'): ?>
                                <div style="background: #d4edda; padding: 1rem; border-radius: 5px; margin-bottom: 1rem;">
                                    <p style="margin: 0; color: #155724;">
                                        <strong>✅ Your request has been approved!</strong><br>
                                        Contact the shelter to schedule a meet-and-greet and finalize the adoption.
                                    </p>
                                </div>
                            <?php elseif ($request['status'] === 'rejected'): ?>
                                <div style="background: #f8d7da; padding: 1rem; border-radius: 5px; margin-bottom: 1rem;">
                                    <p style="margin: 0; color: #721c24;">
                                        <strong>❌ Your request was not approved</strong><br>
                                        Don't worry! There are many other pets looking for loving homes.
                                    </p>
                                </div>
                            <?php elseif ($request['status'] === 'completed'): ?>
                                <div style="background: #d1ecf1; padding: 1rem; border-radius: 5px; margin-bottom: 1rem;">
                                    <p style="margin: 0; color: #0c5460;">
                                        <strong>🎉 Adoption completed!</strong><br>
                                        Congratulations on your new family member! We hope you have many happy years together.
                                    </p>
                                </div>
                            <?php endif; ?>
                            
                            <div style="display: flex; gap: 1rem; flex-wrap: wrap;">
                                <a href="petDetails.php?id=<?php echo $request['pet_id']; ?>" class="btn btn-info">View Pet Details</a>
                                
                                <?php if ($request['status'] === 'approved'): ?>
                                    <a href="mailto:<?php echo htmlspecialchars($request['shelter_email']); ?>" class="btn btn-success">Contact Shelter</a>
                                <?php endif; ?>
                                
                                <?php if ($request['status'] === 'rejected'): ?>
                                    <a href="browsePets.php" class="btn btn-secondary">Browse More Pets</a>
                                <?php endif; ?>
                            </div>
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
                    You haven't submitted any adoption requests yet. Start your journey by browsing available pets!
                <?php endif; ?>
            </p>
            <a href="browsePets.php" class="btn">Browse Available Pets</a>
        </div>
    <?php endif; ?>
</div>

<?php include '../common/footer.php'; ?>