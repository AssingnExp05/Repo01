<?php
require_once '../config/db.php';
requireUserType('adopter');

$page_title = 'Pet Care Guides';

// Get care guides
try {
    $stmt = $pdo->prepare("
        SELECT cg.*, u.first_name, u.last_name
        FROM care_guides cg
        JOIN users u ON cg.created_by = u.id
        ORDER BY cg.created_at DESC
    ");
    $stmt->execute();
    $care_guides = $stmt->fetchAll();
    
} catch(PDOException $e) {
    $care_guides = [];
}
?>

<?php include '../common/header.php'; ?>
<?php include '../common/navbar_adopter.php'; ?>

<div class="main-content">
    <h1 style="color: #333; margin-bottom: 2rem;">📚 Pet Care Guides</h1>
    
    <div class="card" style="margin-bottom: 2rem;">
        <p style="color: #666; line-height: 1.6; margin-bottom: 0;">
            Welcome to our comprehensive pet care guides! Here you'll find expert advice and tips for taking care of your new furry, feathered, or scaled family member. 
            These guides are created by experienced pet owners, veterinarians, and animal care professionals to help you provide the best possible care for your pet.
        </p>
    </div>
    
    <!-- Care Guides -->
    <?php if (!empty($care_guides)): ?>
        <div class="grid grid-1" style="margin-bottom: 3rem;">
            <?php foreach ($care_guides as $guide): ?>
                <div class="card" style="margin-bottom: 2rem;">
                    <div style="display: flex; justify-content: space-between; align-items: start; margin-bottom: 1rem;">
                        <h2 style="color: #333; margin: 0;"><?php echo htmlspecialchars($guide['title']); ?></h2>
                        <span class="badge badge-info"><?php echo ucfirst($guide['species']); ?></span>
                    </div>
                    
                    <div style="margin-bottom: 1rem;">
                        <p style="color: #666; font-size: 0.9rem;">
                            <strong>Created by:</strong> <?php echo htmlspecialchars($guide['first_name'] . ' ' . $guide['last_name']); ?> | 
                            <strong>Date:</strong> <?php echo date('M j, Y', strtotime($guide['created_at'])); ?>
                        </p>
                    </div>
                    
                    <div style="line-height: 1.8; color: #666;">
                        <?php echo nl2br(htmlspecialchars($guide['content'])); ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <div class="card text-center" style="padding: 3rem;">
            <div style="font-size: 4rem; margin-bottom: 1rem;">📚</div>
            <h3 style="color: #666; margin-bottom: 1rem;">No care guides available</h3>
            <p style="color: #666; margin-bottom: 2rem;">
                Care guides are being prepared by our team of experts. Check back soon for comprehensive pet care information!
            </p>
        </div>
    <?php endif; ?>
    
    <!-- General Pet Care Tips -->
    <div class="card" style="margin-top: 3rem;">
        <h2 style="color: #333; margin-bottom: 1.5rem;">💡 General Pet Care Tips</h2>
        
        <div class="grid grid-2">
            <div>
                <h3 style="color: #667eea; margin-bottom: 1rem;">🐕 Dog Care Essentials</h3>
                <ul style="color: #666; line-height: 1.8;">
                    <li><strong>Exercise:</strong> Dogs need daily exercise - aim for at least 30 minutes of physical activity</li>
                    <li><strong>Nutrition:</strong> Feed high-quality dog food appropriate for your dog's age and size</li>
                    <li><strong>Grooming:</strong> Regular brushing, nail trimming, and dental care are essential</li>
                    <li><strong>Training:</strong> Consistent training and socialization help create a well-behaved pet</li>
                    <li><strong>Health:</strong> Regular vet checkups, vaccinations, and parasite prevention</li>
                </ul>
            </div>
            
            <div>
                <h3 style="color: #667eea; margin-bottom: 1rem;">🐱 Cat Care Essentials</h3>
                <ul style="color: #666; line-height: 1.8;">
                    <li><strong>Litter Box:</strong> Keep clean and accessible - cats are very particular about cleanliness</li>
                    <li><strong>Scratching:</strong> Provide scratching posts to satisfy natural instincts</li>
                    <li><strong>Play:</strong> Interactive toys and playtime help keep cats mentally stimulated</li>
                    <li><strong>Indoor Safety:</strong> Remove toxic plants and secure windows and balconies</li>
                    <li><strong>Health:</strong> Regular vet visits, vaccinations, and dental care</li>
                </ul>
            </div>
        </div>
    </div>
    
    <!-- Emergency Information -->
    <div class="card" style="margin-top: 3rem;">
        <h2 style="color: #333; margin-bottom: 1.5rem;">🚨 Emergency Pet Care</h2>
        
        <div style="background: #f8d7da; padding: 1.5rem; border-radius: 10px; margin-bottom: 2rem;">
            <h3 style="color: #721c24; margin-bottom: 1rem;">Emergency Contacts</h3>
            <div class="grid grid-2">
                <div>
                    <p><strong>Emergency Veterinarian:</strong> (555) 911-PETS</p>
                    <p><strong>Animal Poison Control:</strong> (555) 123-4567</p>
                    <p><strong>24/7 Pet Hospital:</strong> (555) 789-0123</p>
                </div>
                <div>
                    <p><strong>Local Animal Control:</strong> (555) 456-7890</p>
                    <p><strong>Pet Emergency Fund:</strong> (555) 234-5678</p>
                    <p><strong>Behavioral Hotline:</strong> (555) 345-6789</p>
                </div>
            </div>
        </div>
        
        <div class="grid grid-2">
            <div>
                <h3 style="color: #e74c3c; margin-bottom: 1rem;">Signs of Emergency</h3>
                <ul style="color: #666; line-height: 1.8;">
                    <li>Difficulty breathing or excessive panting</li>
                    <li>Unconsciousness or collapse</li>
                    <li>Severe bleeding or trauma</li>
                    <li>Ingestion of toxic substances</li>
                    <li>Seizures or convulsions</li>
                    <li>Severe vomiting or diarrhea</li>
                    <li>Inability to urinate or defecate</li>
                    <li>Extreme lethargy or unresponsiveness</li>
                </ul>
            </div>
            
            <div>
                <h3 style="color: #f39c12; margin-bottom: 1rem;">First Aid Kit Essentials</h3>
                <ul style="color: #666; line-height: 1.8;">
                    <li>Gauze pads and bandages</li>
                    <li>Antiseptic solution</li>
                    <li>Digital thermometer</li>
                    <li>Hydrogen peroxide (for inducing vomiting)</li>
                    <li>Activated charcoal</li>
                    <li>Emergency contact numbers</li>
                    <li>Pet carrier or blanket</li>
                    <li>Muzzle (for safety)</li>
                </ul>
            </div>
        </div>
    </div>
    
    <!-- Resources -->
    <div class="card" style="margin-top: 3rem;">
        <h2 style="color: #333; margin-bottom: 1.5rem;">🔗 Additional Resources</h2>
        
        <div class="grid grid-3">
            <div>
                <h4 style="color: #667eea; margin-bottom: 1rem;">Online Resources</h4>
                <ul style="color: #666; line-height: 1.8;">
                    <li><a href="#" style="color: #667eea;">Pet Health Information</a></li>
                    <li><a href="#" style="color: #667eea;">Training Videos</a></li>
                    <li><a href="#" style="color: #667eea;">Nutrition Guides</a></li>
                    <li><a href="#" style="color: #667eea;">Behavioral Tips</a></li>
                </ul>
            </div>
            
            <div>
                <h4 style="color: #667eea; margin-bottom: 1rem;">Local Services</h4>
                <ul style="color: #666; line-height: 1.8;">
                    <li><a href="#" style="color: #667eea;">Veterinarians</a></li>
                    <li><a href="#" style="color: #667eea;">Grooming Services</a></li>
                    <li><a href="#" style="color: #667eea;">Training Classes</a></li>
                    <li><a href="#" style="color: #667eea;">Pet Sitters</a></li>
                </ul>
            </div>
            
            <div>
                <h4 style="color: #667eea; margin-bottom: 1rem;">Support Groups</h4>
                <ul style="color: #666; line-height: 1.8;">
                    <li><a href="#" style="color: #667eea;">New Pet Owner Support</a></li>
                    <li><a href="#" style="color: #667eea;">Behavioral Issues</a></li>
                    <li><a href="#" style="color: #667eea;">Senior Pet Care</a></li>
                    <li><a href="#" style="color: #667eea;">Special Needs Pets</a></li>
                </ul>
            </div>
        </div>
    </div>
</div>

<?php include '../common/footer.php'; ?>