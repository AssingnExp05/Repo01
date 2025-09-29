<?php
require_once '../config/db.php';
requireUserType('shelter');

$page_title = 'Shelter Dashboard';

// Get shelter information
try {
    $stmt = $pdo->prepare("
        SELECT s.*, u.first_name, u.last_name, u.email, u.phone, u.address
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
    
    $shelter_id = $shelter['id'];
    
} catch(PDOException $e) {
    header('Location: /PetCare/index.php');
    exit();
}

// Get dashboard statistics
try {
    $stats = [];
    
    // Total pets
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM pets WHERE shelter_id = ?");
    $stmt->execute([$shelter_id]);
    $stats['total_pets'] = $stmt->fetch()['count'];
    
    // Available pets
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM pets WHERE shelter_id = ? AND status = 'available'");
    $stmt->execute([$shelter_id]);
    $stats['available_pets'] = $stmt->fetch()['count'];
    
    // Adopted pets
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM pets WHERE shelter_id = ? AND status = 'adopted'");
    $stmt->execute([$shelter_id]);
    $stats['adopted_pets'] = $stmt->fetch()['count'];
    
    // Pending adoption requests
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM adoption_requests WHERE shelter_id = ? AND status = 'pending'");
    $stmt->execute([$shelter_id]);
    $stats['pending_requests'] = $stmt->fetch()['count'];
    
    // Recent pets
    $stmt = $pdo->prepare("
        SELECT * FROM pets 
        WHERE shelter_id = ? 
        ORDER BY created_at DESC 
        LIMIT 5
    ");
    $stmt->execute([$shelter_id]);
    $recent_pets = $stmt->fetchAll();
    
    // Recent adoption requests
    $stmt = $pdo->prepare("
        SELECT ar.*, p.name as pet_name, u.first_name, u.last_name, u.email
        FROM adoption_requests ar
        JOIN pets p ON ar.pet_id = p.id
        JOIN users u ON ar.adopter_id = u.id
        WHERE ar.shelter_id = ?
        ORDER BY ar.request_date DESC
        LIMIT 5
    ");
    $stmt->execute([$shelter_id]);
    $recent_requests = $stmt->fetchAll();
    
} catch(PDOException $e) {
    $stats = ['total_pets' => 0, 'available_pets' => 0, 'adopted_pets' => 0, 'pending_requests' => 0];
    $recent_pets = $recent_requests = [];
}
?>

<?php include '../common/header.php'; ?>
<?php include '../common/navbar_shelter.php'; ?>

<div class="main-content">
    <h1 style="color: #333; margin-bottom: 2rem;">🏥 Welcome, <?php echo htmlspecialchars($shelter['shelter_name']); ?></h1>
    
    <!-- Shelter Information -->
    <div class="card" style="margin-bottom: 3rem;">
        <h2 style="color: #333; margin-bottom: 1.5rem;">📋 Shelter Information</h2>
        <div class="grid grid-2">
            <div>
                <p><strong>Shelter Name:</strong> <?php echo htmlspecialchars($shelter['shelter_name']); ?></p>
                <p><strong>License Number:</strong> <?php echo htmlspecialchars($shelter['license_number']); ?></p>
                <p><strong>Contact Person:</strong> <?php echo htmlspecialchars($shelter['first_name'] . ' ' . $shelter['last_name']); ?></p>
                <p><strong>Email:</strong> <?php echo htmlspecialchars($shelter['email']); ?></p>
            </div>
            <div>
                <p><strong>Phone:</strong> <?php echo $shelter['phone'] ? htmlspecialchars($shelter['phone']) : 'Not provided'; ?></p>
                <p><strong>Address:</strong> <?php echo $shelter['address'] ? htmlspecialchars($shelter['address']) : 'Not provided'; ?></p>
                <p><strong>Website:</strong> <?php echo $shelter['website'] ? '<a href="' . htmlspecialchars($shelter['website']) . '" target="_blank">' . htmlspecialchars($shelter['website']) . '</a>' : 'Not provided'; ?></p>
            </div>
        </div>
        <?php if ($shelter['description']): ?>
            <div style="margin-top: 1rem;">
                <p><strong>Description:</strong></p>
                <p style="color: #666; line-height: 1.6;"><?php echo htmlspecialchars($shelter['description']); ?></p>
            </div>
        <?php endif; ?>
    </div>
    
    <!-- Statistics Cards -->
    <div class="grid grid-4" style="margin-bottom: 3rem;">
        <div class="card text-center">
            <div style="font-size: 2.5rem; color: #667eea; margin-bottom: 0.5rem;">
                <?php echo $stats['total_pets']; ?>
            </div>
            <h4>Total Pets</h4>
        </div>
        
        <div class="card text-center">
            <div style="font-size: 2.5rem; color: #27ae60; margin-bottom: 0.5rem;">
                <?php echo $stats['available_pets']; ?>
            </div>
            <h4>Available for Adoption</h4>
        </div>
        
        <div class="card text-center">
            <div style="font-size: 2.5rem; color: #e74c3c; margin-bottom: 0.5rem;">
                <?php echo $stats['adopted_pets']; ?>
            </div>
            <h4>Successfully Adopted</h4>
        </div>
        
        <div class="card text-center">
            <div style="font-size: 2.5rem; color: #f39c12; margin-bottom: 0.5rem;">
                <?php echo $stats['pending_requests']; ?>
            </div>
            <h4>Pending Requests</h4>
        </div>
    </div>
    
    <!-- Quick Actions -->
    <div class="card" style="margin-bottom: 3rem;">
        <h2 style="color: #333; margin-bottom: 1.5rem;">⚡ Quick Actions</h2>
        <div class="grid grid-4">
            <a href="addPet.php" class="btn">🐾 Add New Pet</a>
            <a href="viewPets.php" class="btn">👀 View All Pets</a>
            <a href="adoptionRequests.php" class="btn">💝 Review Requests</a>
            <a href="vaccinationTracker.php" class="btn">💉 Vaccination Tracker</a>
        </div>
    </div>
    
    <!-- Recent Activity -->
    <div class="grid grid-2" style="margin-bottom: 3rem;">
        <div class="card">
            <h3 style="color: #333; margin-bottom: 1.5rem;">🐾 Recent Pet Additions</h3>
            
            <?php if (!empty($recent_pets)): ?>
                <div style="max-height: 300px; overflow-y: auto;">
                    <?php foreach ($recent_pets as $pet): ?>
                        <div style="display: flex; align-items: center; padding: 1rem; border-bottom: 1px solid #eee; margin-bottom: 0.5rem;">
                            <div style="margin-right: 1rem;">
                                <?php if ($pet['photo_path'] && file_exists('../uploads/' . $pet['photo_path'])): ?>
                                    <img src="../uploads/<?php echo htmlspecialchars($pet['photo_path']); ?>" 
                                         alt="<?php echo htmlspecialchars($pet['name']); ?>" 
                                         style="width: 50px; height: 50px; object-fit: cover; border-radius: 5px;">
                                <?php else: ?>
                                    <div style="width: 50px; height: 50px; background: #f0f0f0; border-radius: 5px; display: flex; align-items: center; justify-content: center;">
                                        🐕
                                    </div>
                                <?php endif; ?>
                            </div>
                            <div style="flex: 1;">
                                <h4 style="margin: 0; color: #333;"><?php echo htmlspecialchars($pet['name']); ?></h4>
                                <p style="margin: 0.25rem 0; color: #666; font-size: 0.9rem;">
                                    <?php echo ucfirst($pet['species']); ?> - <?php echo htmlspecialchars($pet['breed']); ?>
                                </p>
                                <p style="margin: 0; color: #666; font-size: 0.8rem;">
                                    Added: <?php echo date('M j, Y', strtotime($pet['created_at'])); ?>
                                </p>
                            </div>
                            <div>
                                <span class="badge badge-<?php 
                                    echo $pet['status'] === 'available' ? 'success' : 
                                        ($pet['status'] === 'adopted' ? 'info' : 
                                        ($pet['status'] === 'pending' ? 'warning' : 'secondary')); 
                                ?>">
                                    <?php echo ucfirst($pet['status']); ?>
                                </span>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <p style="color: #666; text-align: center; padding: 2rem;">No pets added yet.</p>
            <?php endif; ?>
        </div>
        
        <div class="card">
            <h3 style="color: #333; margin-bottom: 1.5rem;">💝 Recent Adoption Requests</h3>
            
            <?php if (!empty($recent_requests)): ?>
                <div style="max-height: 300px; overflow-y: auto;">
                    <?php foreach ($recent_requests as $request): ?>
                        <div style="padding: 1rem; border-bottom: 1px solid #eee; margin-bottom: 0.5rem;">
                            <h4 style="margin: 0 0 0.5rem 0; color: #333;"><?php echo htmlspecialchars($request['pet_name']); ?></h4>
                            <p style="margin: 0.25rem 0; color: #666; font-size: 0.9rem;">
                                <strong>Adopter:</strong> <?php echo htmlspecialchars($request['first_name'] . ' ' . $request['last_name']); ?>
                            </p>
                            <p style="margin: 0.25rem 0; color: #666; font-size: 0.9rem;">
                                <strong>Email:</strong> <?php echo htmlspecialchars($request['email']); ?>
                            </p>
                            <p style="margin: 0.25rem 0; color: #666; font-size: 0.8rem;">
                                Requested: <?php echo date('M j, Y g:i A', strtotime($request['request_date'])); ?>
                            </p>
                            <div style="margin-top: 0.5rem;">
                                <span class="badge badge-<?php 
                                    echo $request['status'] === 'pending' ? 'warning' : 
                                        ($request['status'] === 'approved' ? 'success' : 
                                        ($request['status'] === 'rejected' ? 'danger' : 'info')); 
                                ?>">
                                    <?php echo ucfirst($request['status']); ?>
                                </span>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <p style="color: #666; text-align: center; padding: 2rem;">No adoption requests yet.</p>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Performance Metrics -->
    <div class="card">
        <h2 style="color: #333; margin-bottom: 1.5rem;">📊 Shelter Performance</h2>
        
        <div class="grid grid-3">
            <div class="text-center">
                <div style="font-size: 2rem; color: #27ae60; margin-bottom: 0.5rem;">
                    <?php echo $stats['total_pets'] > 0 ? round(($stats['adopted_pets'] / $stats['total_pets']) * 100, 1) : 0; ?>%
                </div>
                <h4>Adoption Success Rate</h4>
                <small style="color: #666;"><?php echo $stats['adopted_pets']; ?> of <?php echo $stats['total_pets']; ?> pets adopted</small>
            </div>
            
            <div class="text-center">
                <div style="font-size: 2rem; color: #667eea; margin-bottom: 0.5rem;">
                    <?php echo $stats['available_pets']; ?>
                </div>
                <h4>Currently Available</h4>
                <small style="color: #666;">Pets ready for adoption</small>
            </div>
            
            <div class="text-center">
                <div style="font-size: 2rem; color: #f39c12; margin-bottom: 0.5rem;">
                    <?php echo $stats['pending_requests']; ?>
                </div>
                <h4>Pending Reviews</h4>
                <small style="color: #666;">Adoption requests to review</small>
            </div>
        </div>
    </div>
</div>

<?php include '../common/footer.php'; ?>