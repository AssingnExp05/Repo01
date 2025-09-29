<?php
require_once '../config/db.php';
requireUserType('adopter');

$page_title = 'Adopter Dashboard';

// Get adopter information
try {
    $stmt = $pdo->prepare("
        SELECT * FROM users WHERE id = ?
    ");
    $stmt->execute([$_SESSION['user_id']]);
    $adopter = $stmt->fetch();
    
    if (!$adopter) {
        header('Location: /PetCare/index.php');
        exit();
    }
    
} catch(PDOException $e) {
    header('Location: /PetCare/index.php');
    exit();
}

// Get dashboard statistics
try {
    $stats = [];
    
    // Total adoption requests
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM adoption_requests WHERE adopter_id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $stats['total_requests'] = $stmt->fetch()['count'];
    
    // Pending requests
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM adoption_requests WHERE adopter_id = ? AND status = 'pending'");
    $stmt->execute([$_SESSION['user_id']]);
    $stats['pending_requests'] = $stmt->fetch()['count'];
    
    // Approved requests
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM adoption_requests WHERE adopter_id = ? AND status = 'approved'");
    $stmt->execute([$_SESSION['user_id']]);
    $stats['approved_requests'] = $stmt->fetch()['count'];
    
    // Completed adoptions
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM adoption_requests WHERE adopter_id = ? AND status = 'completed'");
    $stmt->execute([$_SESSION['user_id']]);
    $stats['completed_adoptions'] = $stmt->fetch()['count'];
    
    // Recent adoption requests
    $stmt = $pdo->prepare("
        SELECT ar.*, p.name as pet_name, p.species, p.breed, p.photo_path, s.shelter_name
        FROM adoption_requests ar
        JOIN pets p ON ar.pet_id = p.id
        JOIN shelters s ON ar.shelter_id = s.id
        WHERE ar.adopter_id = ?
        ORDER BY ar.request_date DESC
        LIMIT 5
    ");
    $stmt->execute([$_SESSION['user_id']]);
    $recent_requests = $stmt->fetchAll();
    
} catch(PDOException $e) {
    $stats = ['total_requests' => 0, 'pending_requests' => 0, 'approved_requests' => 0, 'completed_adoptions' => 0];
    $recent_requests = [];
}
?>

<?php include '../common/header.php'; ?>
<?php include '../common/navbar_adopter.php'; ?>

<div class="main-content">
    <h1 style="color: #333; margin-bottom: 2rem;">🐕 Welcome, <?php echo htmlspecialchars($adopter['first_name'] . ' ' . $adopter['last_name']); ?></h1>
    
    <!-- Adopter Information -->
    <div class="card" style="margin-bottom: 3rem;">
        <h2 style="color: #333; margin-bottom: 1.5rem;">📋 Your Information</h2>
        <div class="grid grid-2">
            <div>
                <p><strong>Name:</strong> <?php echo htmlspecialchars($adopter['first_name'] . ' ' . $adopter['last_name']); ?></p>
                <p><strong>Email:</strong> <?php echo htmlspecialchars($adopter['email']); ?></p>
                <p><strong>Username:</strong> <?php echo htmlspecialchars($adopter['username']); ?></p>
            </div>
            <div>
                <p><strong>Phone:</strong> <?php echo $adopter['phone'] ? htmlspecialchars($adopter['phone']) : 'Not provided'; ?></p>
                <p><strong>Address:</strong> <?php echo $adopter['address'] ? htmlspecialchars($adopter['address']) : 'Not provided'; ?></p>
                <p><strong>Member Since:</strong> <?php echo date('M j, Y', strtotime($adopter['created_at'])); ?></p>
            </div>
        </div>
    </div>
    
    <!-- Statistics Cards -->
    <div class="grid grid-4" style="margin-bottom: 3rem;">
        <div class="card text-center">
            <div style="font-size: 2.5rem; color: #667eea; margin-bottom: 0.5rem;">
                <?php echo $stats['total_requests']; ?>
            </div>
            <h4>Total Requests</h4>
        </div>
        
        <div class="card text-center">
            <div style="font-size: 2.5rem; color: #f39c12; margin-bottom: 0.5rem;">
                <?php echo $stats['pending_requests']; ?>
            </div>
            <h4>Pending</h4>
        </div>
        
        <div class="card text-center">
            <div style="font-size: 2.5rem; color: #27ae60; margin-bottom: 0.5rem;">
                <?php echo $stats['approved_requests']; ?>
            </div>
            <h4>Approved</h4>
        </div>
        
        <div class="card text-center">
            <div style="font-size: 2.5rem; color: #e74c3c; margin-bottom: 0.5rem;">
                <?php echo $stats['completed_adoptions']; ?>
            </div>
            <h4>Completed</h4>
        </div>
    </div>
    
    <!-- Quick Actions -->
    <div class="card" style="margin-bottom: 3rem;">
        <h2 style="color: #333; margin-bottom: 1.5rem;">⚡ Quick Actions</h2>
        <div class="grid grid-4">
            <a href="browsePets.php" class="btn">🔍 Browse Pets</a>
            <a href="myAdoptions.php" class="btn">💝 My Adoptions</a>
            <a href="careGuides.php" class="btn">📚 Care Guides</a>
            <a href="../contact.php" class="btn">📞 Contact Support</a>
        </div>
    </div>
    
    <!-- Recent Adoption Requests -->
    <div class="card" style="margin-bottom: 3rem;">
        <h2 style="color: #333; margin-bottom: 1.5rem;">💝 Recent Adoption Requests</h2>
        
        <?php if (!empty($recent_requests)): ?>
            <div style="max-height: 400px; overflow-y: auto;">
                <?php foreach ($recent_requests as $request): ?>
                    <div style="display: flex; align-items: center; padding: 1.5rem; border-bottom: 1px solid #eee; margin-bottom: 1rem;">
                        <div style="margin-right: 1.5rem;">
                            <?php if ($request['photo_path'] && file_exists('../uploads/' . $request['photo_path'])): ?>
                                <img src="../uploads/<?php echo htmlspecialchars($request['photo_path']); ?>" 
                                     alt="<?php echo htmlspecialchars($request['pet_name']); ?>" 
                                     style="width: 80px; height: 80px; object-fit: cover; border-radius: 10px;">
                            <?php else: ?>
                                <div style="width: 80px; height: 80px; background: #f0f0f0; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 2rem;">
                                    🐕
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <div style="flex: 1;">
                            <h4 style="margin: 0 0 0.5rem 0; color: #333;"><?php echo htmlspecialchars($request['pet_name']); ?></h4>
                            <p style="margin: 0.25rem 0; color: #666; font-size: 0.9rem;">
                                <strong>Species:</strong> <?php echo ucfirst($request['species']); ?> - <?php echo htmlspecialchars($request['breed']); ?>
                            </p>
                            <p style="margin: 0.25rem 0; color: #666; font-size: 0.9rem;">
                                <strong>Shelter:</strong> <?php echo htmlspecialchars($request['shelter_name']); ?>
                            </p>
                            <p style="margin: 0.25rem 0; color: #666; font-size: 0.8rem;">
                                <strong>Requested:</strong> <?php echo date('M j, Y g:i A', strtotime($request['request_date'])); ?>
                            </p>
                        </div>
                        
                        <div style="text-align: right;">
                            <div style="margin-bottom: 0.5rem;">
                                <span class="badge badge-<?php 
                                    echo $request['status'] === 'pending' ? 'warning' : 
                                        ($request['status'] === 'approved' ? 'success' : 
                                        ($request['status'] === 'rejected' ? 'danger' : 'info')); 
                                ?>">
                                    <?php echo ucfirst($request['status']); ?>
                                </span>
                            </div>
                            
                            <?php if ($request['status'] === 'pending'): ?>
                                <p style="color: #666; font-size: 0.8rem; margin: 0;">
                                    Awaiting review
                                </p>
                            <?php elseif ($request['status'] === 'approved'): ?>
                                <p style="color: #27ae60; font-size: 0.8rem; margin: 0;">
                                    Ready for adoption!
                                </p>
                            <?php elseif ($request['status'] === 'rejected'): ?>
                                <p style="color: #e74c3c; font-size: 0.8rem; margin: 0;">
                                    Request declined
                                </p>
                            <?php else: ?>
                                <p style="color: #667eea; font-size: 0.8rem; margin: 0;">
                                    Adoption completed
                                </p>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            
            <div style="text-align: center; margin-top: 1.5rem;">
                <a href="myAdoptions.php" class="btn">View All My Adoptions</a>
            </div>
            
        <?php else: ?>
            <div style="text-align: center; padding: 3rem;">
                <div style="font-size: 4rem; margin-bottom: 1rem;">💝</div>
                <h3 style="color: #666; margin-bottom: 1rem;">No adoption requests yet</h3>
                <p style="color: #666; margin-bottom: 2rem;">
                    Start your journey by browsing available pets and submitting your first adoption request!
                </p>
                <a href="browsePets.php" class="btn">Browse Available Pets</a>
            </div>
        <?php endif; ?>
    </div>
    
    <!-- Adoption Success Rate -->
    <div class="card">
        <h2 style="color: #333; margin-bottom: 1.5rem;">📊 Your Adoption Journey</h2>
        
        <div class="grid grid-3">
            <div class="text-center">
                <div style="font-size: 2rem; color: #667eea; margin-bottom: 0.5rem;">
                    <?php echo $stats['total_requests'] > 0 ? round(($stats['completed_adoptions'] / $stats['total_requests']) * 100, 1) : 0; ?>%
                </div>
                <h4>Success Rate</h4>
                <small style="color: #666;"><?php echo $stats['completed_adoptions']; ?> of <?php echo $stats['total_requests']; ?> requests completed</small>
            </div>
            
            <div class="text-center">
                <div style="font-size: 2rem; color: #f39c12; margin-bottom: 0.5rem;">
                    <?php echo $stats['pending_requests']; ?>
                </div>
                <h4>Pending Reviews</h4>
                <small style="color: #666;">Awaiting shelter response</small>
            </div>
            
            <div class="text-center">
                <div style="font-size: 2rem; color: #27ae60; margin-bottom: 0.5rem;">
                    <?php echo $stats['approved_requests']; ?>
                </div>
                <h4>Ready for Adoption</h4>
                <small style="color: #666;">Approved requests</small>
            </div>
        </div>
    </div>
</div>

<?php include '../common/footer.php'; ?>