<?php
require_once '../config/db.php';
requireUserType('admin');

$page_title = 'Admin Dashboard';

// Get dashboard statistics
try {
    $stats = [];
    
    // Total users by type
    $stmt = $pdo->query("SELECT user_type, COUNT(*) as count FROM users GROUP BY user_type");
    $user_stats = $stmt->fetchAll();
    $stats['users'] = array_column($user_stats, 'count', 'user_type');
    
    // Total pets by status
    $stmt = $pdo->query("SELECT status, COUNT(*) as count FROM pets GROUP BY status");
    $pet_stats = $stmt->fetchAll();
    $stats['pets'] = array_column($pet_stats, 'count', 'status');
    
    // Total adoption requests by status
    $stmt = $pdo->query("SELECT status, COUNT(*) as count FROM adoption_requests GROUP BY status");
    $adoption_stats = $stmt->fetchAll();
    $stats['adoptions'] = array_column($adoption_stats, 'count', 'status');
    
    // Recent activities
    $stmt = $pdo->prepare("
        SELECT 'user' as type, CONCAT(first_name, ' ', last_name) as name, created_at 
        FROM users 
        WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
        UNION ALL
        SELECT 'pet' as type, name, created_at 
        FROM pets 
        WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
        UNION ALL
        SELECT 'adoption' as type, CONCAT('Adoption Request #', id), request_date as created_at 
        FROM adoption_requests 
        WHERE request_date >= DATE_SUB(NOW(), INTERVAL 7 DAY)
        ORDER BY created_at DESC 
        LIMIT 10
    ");
    $stmt->execute();
    $recent_activities = $stmt->fetchAll();
    
} catch(PDOException $e) {
    $stats = ['users' => [], 'pets' => [], 'adoptions' => []];
    $recent_activities = [];
}
?>

<?php include '../common/header.php'; ?>
<?php include '../common/navbar_admin.php'; ?>

<div class="main-content">
    <h1 style="color: #333; margin-bottom: 2rem;">📊 Admin Dashboard</h1>
    
    <!-- Statistics Cards -->
    <div class="grid grid-4" style="margin-bottom: 3rem;">
        <div class="card text-center">
            <div style="font-size: 2.5rem; color: #667eea; margin-bottom: 0.5rem;">
                <?php echo array_sum($stats['users']); ?>
            </div>
            <h4>Total Users</h4>
            <div style="font-size: 0.9rem; color: #666; margin-top: 0.5rem;">
                <?php 
                $admin_count = $stats['users']['admin'] ?? 0;
                $shelter_count = $stats['users']['shelter'] ?? 0;
                $adopter_count = $stats['users']['adopter'] ?? 0;
                echo "Admin: $admin_count | Shelter: $shelter_count | Adopter: $adopter_count";
                ?>
            </div>
        </div>
        
        <div class="card text-center">
            <div style="font-size: 2.5rem; color: #27ae60; margin-bottom: 0.5rem;">
                <?php echo array_sum($stats['pets']); ?>
            </div>
            <h4>Total Pets</h4>
            <div style="font-size: 0.9rem; color: #666; margin-top: 0.5rem;">
                <?php 
                $available = $stats['pets']['available'] ?? 0;
                $adopted = $stats['pets']['adopted'] ?? 0;
                $pending = $stats['pets']['pending'] ?? 0;
                echo "Available: $available | Adopted: $adopted | Pending: $pending";
                ?>
            </div>
        </div>
        
        <div class="card text-center">
            <div style="font-size: 2.5rem; color: #e74c3c; margin-bottom: 0.5rem;">
                <?php echo array_sum($stats['adoptions']); ?>
            </div>
            <h4>Adoption Requests</h4>
            <div style="font-size: 0.9rem; color: #666; margin-top: 0.5rem;">
                <?php 
                $pending_adoptions = $stats['adoptions']['pending'] ?? 0;
                $approved_adoptions = $stats['adoptions']['approved'] ?? 0;
                $completed_adoptions = $stats['adoptions']['completed'] ?? 0;
                echo "Pending: $pending_adoptions | Approved: $approved_adoptions | Completed: $completed_adoptions";
                ?>
            </div>
        </div>
        
        <div class="card text-center">
            <div style="font-size: 2.5rem; color: #f39c12; margin-bottom: 0.5rem;">
                <?php 
                try {
                    $stmt = $pdo->query("SELECT COUNT(*) as count FROM shelters");
                    $shelter_count = $stmt->fetch()['count'];
                    echo $shelter_count;
                } catch(PDOException $e) {
                    echo '0';
                }
                ?>
            </div>
            <h4>Active Shelters</h4>
            <div style="font-size: 0.9rem; color: #666; margin-top: 0.5rem;">
                Partner shelters
            </div>
        </div>
    </div>
    
    <!-- Quick Actions -->
    <div class="card" style="margin-bottom: 3rem;">
        <h2 style="color: #333; margin-bottom: 1.5rem;">⚡ Quick Actions</h2>
        <div class="grid grid-4">
            <a href="manageUsers.php" class="btn">👥 Manage Users</a>
            <a href="managePets.php" class="btn">🐾 Manage Pets</a>
            <a href="manageAdoptions.php" class="btn">💝 Review Adoptions</a>
            <a href="reports.php" class="btn">📊 View Reports</a>
        </div>
    </div>
    
    <!-- Recent Activities -->
    <div class="card" style="margin-bottom: 3rem;">
        <h2 style="color: #333; margin-bottom: 1.5rem;">📈 Recent Activities (Last 7 Days)</h2>
        
        <?php if (!empty($recent_activities)): ?>
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Type</th>
                            <th>Name/Description</th>
                            <th>Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recent_activities as $activity): ?>
                        <tr>
                            <td>
                                <span class="badge badge-<?php 
                                    echo $activity['type'] === 'user' ? 'info' : 
                                        ($activity['type'] === 'pet' ? 'success' : 'warning'); 
                                ?>">
                                    <?php echo ucfirst($activity['type']); ?>
                                </span>
                            </td>
                            <td><?php echo htmlspecialchars($activity['name']); ?></td>
                            <td><?php echo date('M j, Y g:i A', strtotime($activity['created_at'])); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <p style="color: #666; text-align: center; padding: 2rem;">No recent activities found.</p>
        <?php endif; ?>
    </div>
    
    <!-- System Status -->
    <div class="grid grid-2">
        <div class="card">
            <h3 style="color: #333; margin-bottom: 1.5rem;">🔧 System Status</h3>
            
            <div style="margin-bottom: 1rem;">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.5rem;">
                    <span>Database Connection</span>
                    <span class="badge badge-success">Online</span>
                </div>
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.5rem;">
                    <span>File Uploads</span>
                    <span class="badge badge-success">Active</span>
                </div>
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.5rem;">
                    <span>Email System</span>
                    <span class="badge badge-warning">Pending</span>
                </div>
                <div style="display: flex; justify-content: space-between; align-items: center;">
                    <span>Backup System</span>
                    <span class="badge badge-success">Active</span>
                </div>
            </div>
        </div>
        
        <div class="card">
            <h3 style="color: #333; margin-bottom: 1.5rem;">📋 Pending Tasks</h3>
            
            <?php
            try {
                $stmt = $pdo->prepare("
                    SELECT COUNT(*) as count FROM adoption_requests 
                    WHERE status = 'pending'
                ");
                $stmt->execute();
                $pending_adoptions = $stmt->fetch()['count'];
                
                $stmt = $pdo->prepare("
                    SELECT COUNT(*) as count FROM users 
                    WHERE created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
                ");
                $stmt->execute();
                $new_users = $stmt->fetch()['count'];
                
            } catch(PDOException $e) {
                $pending_adoptions = 0;
                $new_users = 0;
            }
            ?>
            
            <div style="margin-bottom: 1rem;">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.5rem;">
                    <span>Pending Adoption Reviews</span>
                    <span class="badge badge-warning"><?php echo $pending_adoptions; ?></span>
                </div>
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.5rem;">
                    <span>New Users (24h)</span>
                    <span class="badge badge-info"><?php echo $new_users; ?></span>
                </div>
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.5rem;">
                    <span>System Updates</span>
                    <span class="badge badge-secondary">0</span>
                </div>
                <div style="display: flex; justify-content: space-between; align-items: center;">
                    <span>Security Alerts</span>
                    <span class="badge badge-success">0</span>
                </div>
            </div>
            
            <div style="margin-top: 1.5rem;">
                <a href="manageAdoptions.php" class="btn btn-warning" style="width: 100%;">
                    Review Pending Adoptions
                </a>
            </div>
        </div>
    </div>
</div>

<?php include '../common/footer.php'; ?>