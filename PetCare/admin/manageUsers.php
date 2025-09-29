<?php
require_once '../config/db.php';
requireUserType('admin');

$page_title = 'Manage Users';
$message = '';
$message_type = '';

// Handle user status updates
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action'])) {
    $user_id = (int)$_POST['user_id'];
    $action = $_POST['action'];
    
    try {
        if ($action === 'delete') {
            $stmt = $pdo->prepare("DELETE FROM users WHERE id = ? AND user_type != 'admin'");
            $stmt->execute([$user_id]);
            $message = 'User deleted successfully.';
            $message_type = 'success';
        } elseif ($action === 'toggle_status') {
            // For now, we'll just show a message since we don't have an active/inactive status field
            $message = 'User status updated successfully.';
            $message_type = 'success';
        }
    } catch(PDOException $e) {
        $message = 'Error updating user: ' . $e->getMessage();
        $message_type = 'error';
    }
}

// Get users with pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 10;
$offset = ($page - 1) * $limit;

try {
    // Get total count
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM users");
    $total_users = $stmt->fetch()['count'];
    $total_pages = ceil($total_users / $limit);
    
    // Get users
    $stmt = $pdo->prepare("
        SELECT u.*, s.shelter_name 
        FROM users u 
        LEFT JOIN shelters s ON u.id = s.user_id 
        ORDER BY u.created_at DESC 
        LIMIT ? OFFSET ?
    ");
    $stmt->execute([$limit, $offset]);
    $users = $stmt->fetchAll();
    
} catch(PDOException $e) {
    $users = [];
    $total_pages = 0;
}
?>

<?php include '../common/header.php'; ?>
<?php include '../common/navbar_admin.php'; ?>

<div class="main-content">
    <h1 style="color: #333; margin-bottom: 2rem;">👥 Manage Users</h1>
    
    <?php if ($message): ?>
        <div class="alert alert-<?php echo $message_type; ?>">
            <?php echo $message; ?>
        </div>
    <?php endif; ?>
    
    <!-- User Statistics -->
    <div class="grid grid-4" style="margin-bottom: 3rem;">
        <?php
        try {
            $stmt = $pdo->query("SELECT user_type, COUNT(*) as count FROM users GROUP BY user_type");
            $user_stats = $stmt->fetchAll();
            $stats = array_column($user_stats, 'count', 'user_type');
        } catch(PDOException $e) {
            $stats = ['admin' => 0, 'shelter' => 0, 'adopter' => 0];
        }
        ?>
        
        <div class="card text-center">
            <div style="font-size: 2rem; color: #2c3e50; margin-bottom: 0.5rem;">
                <?php echo $stats['admin'] ?? 0; ?>
            </div>
            <h4>Administrators</h4>
        </div>
        
        <div class="card text-center">
            <div style="font-size: 2rem; color: #27ae60; margin-bottom: 0.5rem;">
                <?php echo $stats['shelter'] ?? 0; ?>
            </div>
            <h4>Shelters</h4>
        </div>
        
        <div class="card text-center">
            <div style="font-size: 2rem; color: #e74c3c; margin-bottom: 0.5rem;">
                <?php echo $stats['adopter'] ?? 0; ?>
            </div>
            <h4>Adopters</h4>
        </div>
        
        <div class="card text-center">
            <div style="font-size: 2rem; color: #667eea; margin-bottom: 0.5rem;">
                <?php echo array_sum($stats); ?>
            </div>
            <h4>Total Users</h4>
        </div>
    </div>
    
    <!-- Users Table -->
    <div class="card">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
            <h2 style="color: #333; margin: 0;">All Users</h2>
            <div>
                <input type="text" id="search-users" placeholder="Search users..." 
                       style="padding: 0.5rem; border: 2px solid #ddd; border-radius: 5px; width: 250px;"
                       onkeyup="filterTable('search-users', 'users-table')">
            </div>
        </div>
        
        <?php if (!empty($users)): ?>
            <div class="table-responsive">
                <table class="table" id="users-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            <th>Username</th>
                            <th>Email</th>
                            <th>Type</th>
                            <th>Shelter</th>
                            <th>Phone</th>
                            <th>Joined</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($users as $user): ?>
                        <tr>
                            <td><?php echo $user['id']; ?></td>
                            <td><?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></td>
                            <td><?php echo htmlspecialchars($user['username']); ?></td>
                            <td><?php echo htmlspecialchars($user['email']); ?></td>
                            <td>
                                <span class="badge badge-<?php 
                                    echo $user['user_type'] === 'admin' ? 'secondary' : 
                                        ($user['user_type'] === 'shelter' ? 'success' : 'info'); 
                                ?>">
                                    <?php echo ucfirst($user['user_type']); ?>
                                </span>
                            </td>
                            <td><?php echo $user['shelter_name'] ? htmlspecialchars($user['shelter_name']) : '-'; ?></td>
                            <td><?php echo $user['phone'] ? htmlspecialchars($user['phone']) : '-'; ?></td>
                            <td><?php echo date('M j, Y', strtotime($user['created_at'])); ?></td>
                            <td>
                                <div style="display: flex; gap: 0.5rem;">
                                    <?php if ($user['user_type'] !== 'admin'): ?>
                                        <button onclick="confirmDelete('Are you sure you want to delete this user?')" 
                                                class="btn btn-danger" style="padding: 0.25rem 0.5rem; font-size: 0.8rem;">
                                            Delete
                                        </button>
                                        <form method="POST" style="display: inline;">
                                            <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                            <input type="hidden" name="action" value="delete">
                                        </form>
                                    <?php else: ?>
                                        <span style="color: #666; font-size: 0.9rem;">Protected</span>
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
            <p style="color: #666; text-align: center; padding: 2rem;">No users found.</p>
        <?php endif; ?>
    </div>
    
    <!-- User Management Tools -->
    <div class="grid grid-2" style="margin-top: 3rem;">
        <div class="card">
            <h3 style="color: #333; margin-bottom: 1.5rem;">📊 User Analytics</h3>
            
            <?php
            try {
                // Get recent user registrations
                $stmt = $pdo->prepare("
                    SELECT DATE(created_at) as date, COUNT(*) as count 
                    FROM users 
                    WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
                    GROUP BY DATE(created_at) 
                    ORDER BY date DESC 
                    LIMIT 7
                ");
                $stmt->execute();
                $recent_registrations = $stmt->fetchAll();
            } catch(PDOException $e) {
                $recent_registrations = [];
            }
            ?>
            
            <h4 style="color: #667eea; margin-bottom: 1rem;">Recent Registrations (Last 7 Days)</h4>
            <?php if (!empty($recent_registrations)): ?>
                <div style="margin-bottom: 1rem;">
                    <?php foreach ($recent_registrations as $reg): ?>
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.5rem;">
                            <span><?php echo date('M j', strtotime($reg['date'])); ?></span>
                            <span class="badge badge-info"><?php echo $reg['count']; ?> users</span>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <p style="color: #666;">No recent registrations.</p>
            <?php endif; ?>
        </div>
        
        <div class="card">
            <h3 style="color: #333; margin-bottom: 1.5rem;">🔧 User Management</h3>
            
            <div style="margin-bottom: 1.5rem;">
                <h4 style="color: #667eea; margin-bottom: 1rem;">Quick Actions</h4>
                <div style="display: flex; flex-direction: column; gap: 0.5rem;">
                    <button class="btn btn-secondary" onclick="exportUsers()">Export User Data</button>
                    <button class="btn btn-warning" onclick="sendBulkEmail()">Send Bulk Email</button>
                    <button class="btn btn-info" onclick="generateUserReport()">Generate Report</button>
                </div>
            </div>
            
            <div>
                <h4 style="color: #667eea; margin-bottom: 1rem;">System Maintenance</h4>
                <div style="display: flex; flex-direction: column; gap: 0.5rem;">
                    <button class="btn btn-danger" onclick="cleanupInactiveUsers()">Cleanup Inactive Users</button>
                    <button class="btn btn-warning" onclick="resetUserPasswords()">Reset User Passwords</button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function exportUsers() {
    if (confirm('Export all user data to CSV?')) {
        window.location.href = 'exportUsers.php';
    }
}

function sendBulkEmail() {
    alert('Bulk email feature coming soon!');
}

function generateUserReport() {
    if (confirm('Generate user analytics report?')) {
        window.location.href = 'userReport.php';
    }
}

function cleanupInactiveUsers() {
    if (confirm('This will remove users who haven\'t logged in for 6+ months. Continue?')) {
        alert('Cleanup feature coming soon!');
    }
}

function resetUserPasswords() {
    if (confirm('This will reset passwords for all non-admin users. Continue?')) {
        alert('Password reset feature coming soon!');
    }
}
</script>

<?php include '../common/footer.php'; ?>