<?php
require_once 'config/db.php';

// If user is already logged in, redirect to appropriate dashboard
if (isLoggedIn()) {
    redirectByUserType();
}

// Get featured pets for homepage
try {
    $stmt = $pdo->prepare("
        SELECT p.*, s.shelter_name 
        FROM pets p 
        JOIN shelters s ON p.shelter_id = s.id 
        WHERE p.status = 'available' 
        ORDER BY p.created_at DESC 
        LIMIT 6
    ");
    $stmt->execute();
    $featured_pets = $stmt->fetchAll();
} catch(PDOException $e) {
    $featured_pets = [];
}

$page_title = 'Home';
?>

<?php include 'common/header.php'; ?>

<div class="main-content">
    <!-- Hero Section -->
    <section style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 4rem 2rem; border-radius: 10px; text-align: center; margin-bottom: 3rem;">
        <h1 style="font-size: 3rem; margin-bottom: 1rem;">🐾 Welcome to PetCare</h1>
        <p style="font-size: 1.2rem; margin-bottom: 2rem; max-width: 600px; margin-left: auto; margin-right: auto;">
            Connecting loving families with pets in need of homes. Find your perfect companion or help a pet find theirs.
        </p>
        <div style="display: flex; gap: 1rem; justify-content: center; flex-wrap: wrap;">
            <a href="auth/register.php" class="btn" style="background: white; color: #667eea;">Get Started</a>
            <a href="adopter/browsePets.php" class="btn" style="background: rgba(255,255,255,0.2); border: 2px solid white;">Browse Pets</a>
        </div>
    </section>

    <!-- Features Section -->
    <section class="grid grid-3" style="margin-bottom: 3rem;">
        <div class="card text-center">
            <div style="font-size: 3rem; margin-bottom: 1rem;">🏠</div>
            <h3>For Pet Adopters</h3>
            <p>Browse available pets, learn about their personalities, and find your perfect companion. Our platform makes it easy to connect with shelters and start the adoption process.</p>
            <a href="auth/register.php" class="btn">Start Adopting</a>
        </div>
        
        <div class="card text-center">
            <div style="font-size: 3rem; margin-bottom: 1rem;">🏥</div>
            <h3>For Shelters</h3>
            <p>Manage your pet listings, track adoption requests, and maintain vaccination records. Our tools help you efficiently care for pets and connect them with loving families.</p>
            <a href="auth/register.php" class="btn">Join as Shelter</a>
        </div>
        
        <div class="card text-center">
            <div style="font-size: 3rem; margin-bottom: 1rem;">👥</div>
            <h3>For Administrators</h3>
            <p>Oversee the entire platform, manage users, monitor adoption processes, and generate reports. Ensure the best experience for all users and pets.</p>
            <a href="auth/login.php" class="btn">Admin Login</a>
        </div>
    </section>

    <!-- Featured Pets Section -->
    <?php if (!empty($featured_pets)): ?>
    <section style="margin-bottom: 3rem;">
        <h2 style="text-align: center; margin-bottom: 2rem; color: #333;">🐾 Featured Pets Looking for Homes</h2>
        <div class="grid grid-3">
            <?php foreach ($featured_pets as $pet): ?>
            <div class="card" style="text-align: center;">
                <?php if ($pet['photo_path'] && file_exists('uploads/' . $pet['photo_path'])): ?>
                    <img src="uploads/<?php echo htmlspecialchars($pet['photo_path']); ?>" 
                         alt="<?php echo htmlspecialchars($pet['name']); ?>" 
                         style="width: 100%; height: 200px; object-fit: cover; border-radius: 5px; margin-bottom: 1rem;">
                <?php else: ?>
                    <div style="width: 100%; height: 200px; background: #f0f0f0; border-radius: 5px; margin-bottom: 1rem; display: flex; align-items: center; justify-content: center; font-size: 3rem;">
                        🐕
                    </div>
                <?php endif; ?>
                
                <h3><?php echo htmlspecialchars($pet['name']); ?></h3>
                <p><strong>Species:</strong> <?php echo ucfirst($pet['species']); ?></p>
                <p><strong>Breed:</strong> <?php echo htmlspecialchars($pet['breed']); ?></p>
                <p><strong>Age:</strong> <?php echo $pet['age']; ?> years old</p>
                <p><strong>Gender:</strong> <?php echo ucfirst($pet['gender']); ?></p>
                <p><strong>Size:</strong> <?php echo ucfirst($pet['size']); ?></p>
                <p><strong>Shelter:</strong> <?php echo htmlspecialchars($pet['shelter_name']); ?></p>
                <p style="margin: 1rem 0;"><?php echo htmlspecialchars(substr($pet['description'], 0, 100)) . '...'; ?></p>
                <a href="adopter/petDetails.php?id=<?php echo $pet['id']; ?>" class="btn">View Details</a>
            </div>
            <?php endforeach; ?>
        </div>
        
        <div style="text-align: center; margin-top: 2rem;">
            <a href="adopter/browsePets.php" class="btn">View All Available Pets</a>
        </div>
    </section>
    <?php endif; ?>

    <!-- Statistics Section -->
    <section class="grid grid-4" style="margin-bottom: 3rem;">
        <?php
        try {
            // Get statistics
            $stats = [];
            
            // Total pets
            $stmt = $pdo->query("SELECT COUNT(*) as count FROM pets");
            $stats['total_pets'] = $stmt->fetch()['count'];
            
            // Available pets
            $stmt = $pdo->query("SELECT COUNT(*) as count FROM pets WHERE status = 'available'");
            $stats['available_pets'] = $stmt->fetch()['count'];
            
            // Adopted pets
            $stmt = $pdo->query("SELECT COUNT(*) as count FROM pets WHERE status = 'adopted'");
            $stats['adopted_pets'] = $stmt->fetch()['count'];
            
            // Total shelters
            $stmt = $pdo->query("SELECT COUNT(*) as count FROM shelters");
            $stats['total_shelters'] = $stmt->fetch()['count'];
            
        } catch(PDOException $e) {
            $stats = ['total_pets' => 0, 'available_pets' => 0, 'adopted_pets' => 0, 'total_shelters' => 0];
        }
        ?>
        
        <div class="card text-center">
            <div style="font-size: 2.5rem; color: #667eea; margin-bottom: 0.5rem;"><?php echo $stats['total_pets']; ?></div>
            <h4>Total Pets</h4>
        </div>
        
        <div class="card text-center">
            <div style="font-size: 2.5rem; color: #27ae60; margin-bottom: 0.5rem;"><?php echo $stats['available_pets']; ?></div>
            <h4>Available for Adoption</h4>
        </div>
        
        <div class="card text-center">
            <div style="font-size: 2.5rem; color: #e74c3c; margin-bottom: 0.5rem;"><?php echo $stats['adopted_pets']; ?></div>
            <h4>Successfully Adopted</h4>
        </div>
        
        <div class="card text-center">
            <div style="font-size: 2.5rem; color: #f39c12; margin-bottom: 0.5rem;"><?php echo $stats['total_shelters']; ?></div>
            <h4>Partner Shelters</h4>
        </div>
    </section>

    <!-- Call to Action -->
    <section style="background: #f8f9fa; padding: 3rem 2rem; border-radius: 10px; text-align: center;">
        <h2 style="margin-bottom: 1rem; color: #333;">Ready to Make a Difference?</h2>
        <p style="margin-bottom: 2rem; color: #666; max-width: 600px; margin-left: auto; margin-right: auto;">
            Whether you're looking to adopt a pet or help animals in need, PetCare is here to connect you with the right resources and community.
        </p>
        <div style="display: flex; gap: 1rem; justify-content: center; flex-wrap: wrap;">
            <a href="auth/register.php" class="btn">Join Our Community</a>
            <a href="about.php" class="btn btn-secondary">Learn More</a>
        </div>
    </section>
</div>

<?php include 'common/footer.php'; ?>