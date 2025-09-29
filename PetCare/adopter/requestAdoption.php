<?php
require_once '../config/db.php';
requireUserType('adopter');

$page_title = 'Adoption Request';
$message = '';
$message_type = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $pet_id = (int)$_POST['pet_id'];
    $notes = sanitizeInput($_POST['notes']);
    
    if (empty($notes) || strlen($notes) < 50) {
        $message = 'Please provide at least 50 characters describing why you\'d like to adopt this pet.';
        $message_type = 'error';
    } else {
        try {
            // Verify pet exists and is available
            $stmt = $pdo->prepare("
                SELECT p.id, p.shelter_id 
                FROM pets p 
                WHERE p.id = ? AND p.status = 'available'
            ");
            $stmt->execute([$pet_id]);
            $pet = $stmt->fetch();
            
            if (!$pet) {
                $message = 'This pet is no longer available for adoption.';
                $message_type = 'error';
            } else {
                // Check if user already has a pending request for this pet
                $stmt = $pdo->prepare("
                    SELECT id FROM adoption_requests 
                    WHERE pet_id = ? AND adopter_id = ? AND status = 'pending'
                ");
                $stmt->execute([$pet_id, $_SESSION['user_id']]);
                if ($stmt->fetch()) {
                    $message = 'You already have a pending adoption request for this pet.';
                    $message_type = 'error';
                } else {
                    // Create adoption request
                    $stmt = $pdo->prepare("
                        INSERT INTO adoption_requests (pet_id, adopter_id, shelter_id, notes) 
                        VALUES (?, ?, ?, ?)
                    ");
                    $stmt->execute([$pet_id, $_SESSION['user_id'], $pet['shelter_id'], $notes]);
                    
                    $message = 'Adoption request submitted successfully! The shelter will review your application and contact you soon.';
                    $message_type = 'success';
                }
            }
        } catch(PDOException $e) {
            $message = 'Error submitting adoption request: ' . $e->getMessage();
            $message_type = 'error';
        }
    }
}

// If no POST data, redirect to browse pets
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: browsePets.php');
    exit();
}
?>

<?php include '../common/header.php'; ?>
<?php include '../common/navbar_adopter.php'; ?>

<div class="main-content">
    <h1 style="color: #333; margin-bottom: 2rem;">💝 Adoption Request</h1>
    
    <?php if ($message): ?>
        <div class="alert alert-<?php echo $message_type; ?>">
            <?php echo $message; ?>
        </div>
    <?php endif; ?>
    
    <?php if ($message_type === 'success'): ?>
        <div class="card text-center" style="padding: 3rem;">
            <div style="font-size: 4rem; margin-bottom: 1rem;">✅</div>
            <h2 style="color: #27ae60; margin-bottom: 1rem;">Request Submitted Successfully!</h2>
            <p style="color: #666; margin-bottom: 2rem; line-height: 1.6;">
                Thank you for your interest in adopting a pet! Your adoption request has been submitted to the shelter. 
                They will review your application and contact you within 3-5 business days.
            </p>
            
            <div style="background: #f8f9fa; padding: 2rem; border-radius: 10px; margin-bottom: 2rem; text-align: left;">
                <h3 style="color: #333; margin-bottom: 1rem;">What happens next?</h3>
                <ul style="color: #666; line-height: 1.8;">
                    <li>The shelter will review your application and background</li>
                    <li>They may contact you for additional information or clarification</li>
                    <li>If approved, you'll be invited for a meet-and-greet with the pet</li>
                    <li>After a successful meeting, you can finalize the adoption</li>
                </ul>
            </div>
            
            <div style="display: flex; gap: 1rem; justify-content: center; flex-wrap: wrap;">
                <a href="myAdoptions.php" class="btn">View My Adoption Requests</a>
                <a href="browsePets.php" class="btn btn-secondary">Browse More Pets</a>
                <a href="dashboard.php" class="btn btn-secondary">Back to Dashboard</a>
            </div>
        </div>
    <?php else: ?>
        <div class="card text-center" style="padding: 3rem;">
            <div style="font-size: 4rem; margin-bottom: 1rem;">❌</div>
            <h2 style="color: #e74c3c; margin-bottom: 1rem;">Request Failed</h2>
            <p style="color: #666; margin-bottom: 2rem;">
                There was an issue with your adoption request. Please try again or contact support if the problem persists.
            </p>
            
            <div style="display: flex; gap: 1rem; justify-content: center; flex-wrap: wrap;">
                <a href="browsePets.php" class="btn">Browse Pets</a>
                <a href="dashboard.php" class="btn btn-secondary">Back to Dashboard</a>
                <a href="../contact.php" class="btn btn-secondary">Contact Support</a>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php include '../common/footer.php'; ?>