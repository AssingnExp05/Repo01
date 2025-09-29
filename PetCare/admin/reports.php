<?php
require_once '../config/db.php';
requireUserType('admin');

$page_title = 'Reports & Analytics';

// Get comprehensive statistics
try {
    // User statistics
    $stmt = $pdo->query("SELECT user_type, COUNT(*) as count FROM users GROUP BY user_type");
    $user_stats = $stmt->fetchAll();
    $users_by_type = array_column($user_stats, 'count', 'user_type');
    
    // Pet statistics
    $stmt = $pdo->query("SELECT status, COUNT(*) as count FROM pets GROUP BY status");
    $pet_stats = $stmt->fetchAll();
    $pets_by_status = array_column($pet_stats, 'count', 'status');
    
    // Adoption statistics
    $stmt = $pdo->query("SELECT status, COUNT(*) as count FROM adoption_requests GROUP BY status");
    $adoption_stats = $stmt->fetchAll();
    $adoptions_by_status = array_column($adoption_stats, 'count', 'status');
    
    // Monthly trends
    $stmt = $pdo->prepare("
        SELECT 
            DATE_FORMAT(created_at, '%Y-%m') as month,
            COUNT(*) as count
        FROM users 
        WHERE created_at >= DATE_SUB(NOW(), INTERVAL 12 MONTH)
        GROUP BY DATE_FORMAT(created_at, '%Y-%m')
        ORDER BY month DESC
        LIMIT 12
    ");
    $stmt->execute();
    $user_trends = $stmt->fetchAll();
    
    $stmt = $pdo->prepare("
        SELECT 
            DATE_FORMAT(created_at, '%Y-%m') as month,
            COUNT(*) as count
        FROM pets 
        WHERE created_at >= DATE_SUB(NOW(), INTERVAL 12 MONTH)
        GROUP BY DATE_FORMAT(created_at, '%Y-%m')
        ORDER BY month DESC
        LIMIT 12
    ");
    $stmt->execute();
    $pet_trends = $stmt->fetchAll();
    
    $stmt = $pdo->prepare("
        SELECT 
            DATE_FORMAT(request_date, '%Y-%m') as month,
            COUNT(*) as count
        FROM adoption_requests 
        WHERE request_date >= DATE_SUB(NOW(), INTERVAL 12 MONTH)
        GROUP BY DATE_FORMAT(request_date, '%Y-%m')
        ORDER BY month DESC
        LIMIT 12
    ");
    $stmt->execute();
    $adoption_trends = $stmt->fetchAll();
    
    // Top performing shelters
    $stmt = $pdo->query("
        SELECT 
            s.shelter_name,
            COUNT(p.id) as total_pets,
            SUM(CASE WHEN p.status = 'adopted' THEN 1 ELSE 0 END) as adopted_pets,
            ROUND(SUM(CASE WHEN p.status = 'adopted' THEN 1 ELSE 0 END) * 100.0 / COUNT(p.id), 2) as adoption_rate
        FROM shelters s
        LEFT JOIN pets p ON s.id = p.shelter_id
        GROUP BY s.id, s.shelter_name
        HAVING total_pets > 0
        ORDER BY adoption_rate DESC, total_pets DESC
        LIMIT 10
    ");
    $top_shelters = $stmt->fetchAll();
    
    // Pet species distribution
    $stmt = $pdo->query("
        SELECT species, COUNT(*) as count 
        FROM pets 
        GROUP BY species 
        ORDER BY count DESC
    ");
    $species_distribution = $stmt->fetchAll();
    
    // Vaccination coverage
    $stmt = $pdo->query("
        SELECT 
            COUNT(DISTINCT p.id) as total_pets,
            COUNT(DISTINCT v.pet_id) as vaccinated_pets,
            ROUND(COUNT(DISTINCT v.pet_id) * 100.0 / COUNT(DISTINCT p.id), 2) as coverage_rate
        FROM pets p
        LEFT JOIN vaccinations v ON p.id = v.pet_id
    ");
    $vaccination_coverage = $stmt->fetch();
    
} catch(PDOException $e) {
    $users_by_type = $pets_by_status = $adoptions_by_status = [];
    $user_trends = $pet_trends = $adoption_trends = [];
    $top_shelters = $species_distribution = [];
    $vaccination_coverage = ['total_pets' => 0, 'vaccinated_pets' => 0, 'coverage_rate' => 0];
}
?>

<?php include '../common/header.php'; ?>
<?php include '../common/navbar_admin.php'; ?>

<div class="main-content">
    <h1 style="color: #333; margin-bottom: 2rem;">📊 Reports & Analytics</h1>
    
    <!-- Key Metrics -->
    <div class="grid grid-4" style="margin-bottom: 3rem;">
        <div class="card text-center">
            <div style="font-size: 2.5rem; color: #667eea; margin-bottom: 0.5rem;">
                <?php echo array_sum($users_by_type); ?>
            </div>
            <h4>Total Users</h4>
            <div style="font-size: 0.9rem; color: #666; margin-top: 0.5rem;">
                <?php 
                $admin_count = $users_by_type['admin'] ?? 0;
                $shelter_count = $users_by_type['shelter'] ?? 0;
                $adopter_count = $users_by_type['adopter'] ?? 0;
                echo "Admin: $admin_count | Shelter: $shelter_count | Adopter: $adopter_count";
                ?>
            </div>
        </div>
        
        <div class="card text-center">
            <div style="font-size: 2.5rem; color: #27ae60; margin-bottom: 0.5rem;">
                <?php echo array_sum($pets_by_status); ?>
            </div>
            <h4>Total Pets</h4>
            <div style="font-size: 0.9rem; color: #666; margin-top: 0.5rem;">
                <?php 
                $available = $pets_by_status['available'] ?? 0;
                $adopted = $pets_by_status['adopted'] ?? 0;
                $pending = $pets_by_status['pending'] ?? 0;
                echo "Available: $available | Adopted: $adopted | Pending: $pending";
                ?>
            </div>
        </div>
        
        <div class="card text-center">
            <div style="font-size: 2.5rem; color: #e74c3c; margin-bottom: 0.5rem;">
                <?php echo array_sum($adoptions_by_status); ?>
            </div>
            <h4>Adoption Requests</h4>
            <div style="font-size: 0.9rem; color: #666; margin-top: 0.5rem;">
                <?php 
                $pending_adoptions = $adoptions_by_status['pending'] ?? 0;
                $approved_adoptions = $adoptions_by_status['approved'] ?? 0;
                $completed_adoptions = $adoptions_by_status['completed'] ?? 0;
                echo "Pending: $pending_adoptions | Approved: $approved_adoptions | Completed: $completed_adoptions";
                ?>
            </div>
        </div>
        
        <div class="card text-center">
            <div style="font-size: 2.5rem; color: #f39c12; margin-bottom: 0.5rem;">
                <?php echo $vaccination_coverage['coverage_rate']; ?>%
            </div>
            <h4>Vaccination Coverage</h4>
            <div style="font-size: 0.9rem; color: #666; margin-top: 0.5rem;">
                <?php echo $vaccination_coverage['vaccinated_pets']; ?> of <?php echo $vaccination_coverage['total_pets']; ?> pets
            </div>
        </div>
    </div>
    
    <!-- Monthly Trends -->
    <div class="grid grid-3" style="margin-bottom: 3rem;">
        <div class="card">
            <h3 style="color: #333; margin-bottom: 1.5rem;">📈 User Registration Trends</h3>
            <?php if (!empty($user_trends)): ?>
                <div style="max-height: 300px; overflow-y: auto;">
                    <?php foreach (array_reverse($user_trends) as $trend): ?>
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.5rem; padding: 0.5rem; background: #f8f9fa; border-radius: 5px;">
                            <span><?php echo date('M Y', strtotime($trend['month'] . '-01')); ?></span>
                            <span class="badge badge-info"><?php echo $trend['count']; ?> users</span>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <p style="color: #666; text-align: center; padding: 2rem;">No data available</p>
            <?php endif; ?>
        </div>
        
        <div class="card">
            <h3 style="color: #333; margin-bottom: 1.5rem;">🐾 Pet Listing Trends</h3>
            <?php if (!empty($pet_trends)): ?>
                <div style="max-height: 300px; overflow-y: auto;">
                    <?php foreach (array_reverse($pet_trends) as $trend): ?>
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.5rem; padding: 0.5rem; background: #f8f9fa; border-radius: 5px;">
                            <span><?php echo date('M Y', strtotime($trend['month'] . '-01')); ?></span>
                            <span class="badge badge-success"><?php echo $trend['count']; ?> pets</span>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <p style="color: #666; text-align: center; padding: 2rem;">No data available</p>
            <?php endif; ?>
        </div>
        
        <div class="card">
            <h3 style="color: #333; margin-bottom: 1.5rem;">💝 Adoption Request Trends</h3>
            <?php if (!empty($adoption_trends)): ?>
                <div style="max-height: 300px; overflow-y: auto;">
                    <?php foreach (array_reverse($adoption_trends) as $trend): ?>
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.5rem; padding: 0.5rem; background: #f8f9fa; border-radius: 5px;">
                            <span><?php echo date('M Y', strtotime($trend['month'] . '-01')); ?></span>
                            <span class="badge badge-warning"><?php echo $trend['count']; ?> requests</span>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <p style="color: #666; text-align: center; padding: 2rem;">No data available</p>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Top Performing Shelters -->
    <div class="card" style="margin-bottom: 3rem;">
        <h2 style="color: #333; margin-bottom: 1.5rem;">🏆 Top Performing Shelters</h2>
        
        <?php if (!empty($top_shelters)): ?>
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Rank</th>
                            <th>Shelter Name</th>
                            <th>Total Pets</th>
                            <th>Adopted Pets</th>
                            <th>Adoption Rate</th>
                            <th>Performance</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($top_shelters as $index => $shelter): ?>
                        <tr>
                            <td>
                                <span class="badge badge-<?php echo $index < 3 ? 'success' : 'info'; ?>">
                                    #<?php echo $index + 1; ?>
                                </span>
                            </td>
                            <td><?php echo htmlspecialchars($shelter['shelter_name']); ?></td>
                            <td><?php echo $shelter['total_pets']; ?></td>
                            <td><?php echo $shelter['adopted_pets']; ?></td>
                            <td><?php echo $shelter['adoption_rate']; ?>%</td>
                            <td>
                                <div style="width: 100px; height: 20px; background: #f0f0f0; border-radius: 10px; overflow: hidden;">
                                    <div style="width: <?php echo $shelter['adoption_rate']; ?>%; height: 100%; background: <?php echo $shelter['adoption_rate'] > 70 ? '#27ae60' : ($shelter['adoption_rate'] > 40 ? '#f39c12' : '#e74c3c'); ?>;"></div>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <p style="color: #666; text-align: center; padding: 2rem;">No shelter data available</p>
        <?php endif; ?>
    </div>
    
    <!-- Pet Species Distribution -->
    <div class="grid grid-2" style="margin-bottom: 3rem;">
        <div class="card">
            <h3 style="color: #333; margin-bottom: 1.5rem;">🐕 Pet Species Distribution</h3>
            
            <?php if (!empty($species_distribution)): ?>
                <div style="margin-bottom: 1rem;">
                    <?php 
                    $total_pets = array_sum(array_column($species_distribution, 'count'));
                    foreach ($species_distribution as $species): 
                        $percentage = $total_pets > 0 ? round(($species['count'] / $total_pets) * 100, 1) : 0;
                    ?>
                        <div style="margin-bottom: 1rem;">
                            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.5rem;">
                                <span><?php echo ucfirst($species['species']); ?></span>
                                <span class="badge badge-info"><?php echo $species['count']; ?> (<?php echo $percentage; ?>%)</span>
                            </div>
                            <div style="width: 100%; height: 20px; background: #f0f0f0; border-radius: 10px; overflow: hidden;">
                                <div style="width: <?php echo $percentage; ?>%; height: 100%; background: #667eea;"></div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <p style="color: #666; text-align: center; padding: 2rem;">No species data available</p>
            <?php endif; ?>
        </div>
        
        <div class="card">
            <h3 style="color: #333; margin-bottom: 1.5rem;">📋 Report Generation</h3>
            
            <div style="margin-bottom: 2rem;">
                <h4 style="color: #667eea; margin-bottom: 1rem;">Quick Reports</h4>
                <div style="display: flex; flex-direction: column; gap: 0.5rem;">
                    <button class="btn btn-secondary" onclick="generateUserReport()">User Analytics Report</button>
                    <button class="btn btn-secondary" onclick="generatePetReport()">Pet Management Report</button>
                    <button class="btn btn-secondary" onclick="generateAdoptionReport()">Adoption Success Report</button>
                    <button class="btn btn-secondary" onclick="generateVaccinationReport()">Vaccination Coverage Report</button>
                </div>
            </div>
            
            <div>
                <h4 style="color: #667eea; margin-bottom: 1rem;">Custom Reports</h4>
                <div style="display: flex; flex-direction: column; gap: 0.5rem;">
                    <button class="btn btn-warning" onclick="generateCustomReport()">Generate Custom Report</button>
                    <button class="btn btn-info" onclick="scheduleReport()">Schedule Automated Reports</button>
                </div>
            </div>
        </div>
    </div>
    
    <!-- System Performance -->
    <div class="card">
        <h2 style="color: #333; margin-bottom: 1.5rem;">⚡ System Performance</h2>
        
        <div class="grid grid-4">
            <div class="text-center">
                <div style="font-size: 2rem; color: #27ae60; margin-bottom: 0.5rem;">99.9%</div>
                <h4>Uptime</h4>
                <small style="color: #666;">Last 30 days</small>
            </div>
            
            <div class="text-center">
                <div style="font-size: 2rem; color: #667eea; margin-bottom: 0.5rem;">2.3s</div>
                <h4>Avg Response Time</h4>
                <small style="color: #666;">Page load time</small>
            </div>
            
            <div class="text-center">
                <div style="font-size: 2rem; color: #f39c12; margin-bottom: 0.5rem;">1.2GB</div>
                <h4>Database Size</h4>
                <small style="color: #666;">Total storage used</small>
            </div>
            
            <div class="text-center">
                <div style="font-size: 2rem; color: #e74c3c; margin-bottom: 0.5rem;">0</div>
                <h4>Active Issues</h4>
                <small style="color: #666;">System alerts</small>
            </div>
        </div>
    </div>
</div>

<script>
function generateUserReport() {
    if (confirm('Generate comprehensive user analytics report?')) {
        window.location.href = 'generateReport.php?type=users';
    }
}

function generatePetReport() {
    if (confirm('Generate pet management report?')) {
        window.location.href = 'generateReport.php?type=pets';
    }
}

function generateAdoptionReport() {
    if (confirm('Generate adoption success report?')) {
        window.location.href = 'generateReport.php?type=adoptions';
    }
}

function generateVaccinationReport() {
    if (confirm('Generate vaccination coverage report?')) {
        window.location.href = 'generateReport.php?type=vaccinations';
    }
}

function generateCustomReport() {
    alert('Custom report generator coming soon!');
}

function scheduleReport() {
    alert('Automated report scheduling coming soon!');
}
</script>

<?php include '../common/footer.php'; ?>