<?php
$page_title = 'Contact Us';
$message = '';
$message_type = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = sanitizeInput($_POST['name']);
    $email = sanitizeInput($_POST['email']);
    $subject = sanitizeInput($_POST['subject']);
    $message_text = sanitizeInput($_POST['message']);
    
    if (empty($name) || empty($email) || empty($subject) || empty($message_text)) {
        $message = 'Please fill in all fields.';
        $message_type = 'error';
    } elseif (!validateEmail($email)) {
        $message = 'Please enter a valid email address.';
        $message_type = 'error';
    } else {
        // In a real application, you would send an email here
        // For now, we'll just show a success message
        $message = 'Thank you for your message! We will get back to you within 24 hours.';
        $message_type = 'success';
        
        // Clear form data
        $name = $email = $subject = $message_text = '';
    }
}
?>

<?php include 'common/header.php'; ?>

<div class="main-content">
    <div class="grid grid-2" style="gap: 3rem;">
        <!-- Contact Form -->
        <div class="card">
            <h2 style="color: #333; margin-bottom: 1.5rem;">📧 Send us a Message</h2>
            
            <?php if ($message): ?>
                <div class="alert alert-<?php echo $message_type; ?>">
                    <?php echo $message; ?>
                </div>
            <?php endif; ?>
            
            <form method="POST" action="">
                <div class="form-group">
                    <label for="name">Full Name *</label>
                    <input type="text" id="name" name="name" required 
                           value="<?php echo isset($name) ? htmlspecialchars($name) : ''; ?>">
                </div>
                
                <div class="form-group">
                    <label for="email">Email Address *</label>
                    <input type="email" id="email" name="email" required 
                           value="<?php echo isset($email) ? htmlspecialchars($email) : ''; ?>">
                </div>
                
                <div class="form-group">
                    <label for="subject">Subject *</label>
                    <select id="subject" name="subject" required>
                        <option value="">Select a subject</option>
                        <option value="General Inquiry" <?php echo (isset($subject) && $subject === 'General Inquiry') ? 'selected' : ''; ?>>General Inquiry</option>
                        <option value="Adoption Question" <?php echo (isset($subject) && $subject === 'Adoption Question') ? 'selected' : ''; ?>>Adoption Question</option>
                        <option value="Shelter Partnership" <?php echo (isset($subject) && $subject === 'Shelter Partnership') ? 'selected' : ''; ?>>Shelter Partnership</option>
                        <option value="Technical Support" <?php echo (isset($subject) && $subject === 'Technical Support') ? 'selected' : ''; ?>>Technical Support</option>
                        <option value="Account Issue" <?php echo (isset($subject) && $subject === 'Account Issue') ? 'selected' : ''; ?>>Account Issue</option>
                        <option value="Other" <?php echo (isset($subject) && $subject === 'Other') ? 'selected' : ''; ?>>Other</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="message">Message *</label>
                    <textarea id="message" name="message" required rows="6" 
                              placeholder="Please describe your inquiry in detail..."><?php echo isset($message_text) ? htmlspecialchars($message_text) : ''; ?></textarea>
                </div>
                
                <button type="submit" class="btn">Send Message</button>
            </form>
        </div>
        
        <!-- Contact Information -->
        <div class="card">
            <h2 style="color: #333; margin-bottom: 1.5rem;">📞 Get in Touch</h2>
            
            <div style="margin-bottom: 2rem;">
                <h4 style="color: #667eea; margin-bottom: 1rem;">📍 Office Address</h4>
                <p style="color: #666; line-height: 1.6;">
                    123 Pet Care Avenue<br>
                    Animal City, AC 12345<br>
                    United States
                </p>
            </div>
            
            <div style="margin-bottom: 2rem;">
                <h4 style="color: #667eea; margin-bottom: 1rem;">📞 Phone Numbers</h4>
                <p style="color: #666; line-height: 1.6;">
                    <strong>General Inquiries:</strong> (555) 123-4567<br>
                    <strong>Adoption Support:</strong> (555) 123-4568<br>
                    <strong>Shelter Services:</strong> (555) 123-4569
                </p>
            </div>
            
            <div style="margin-bottom: 2rem;">
                <h4 style="color: #667eea; margin-bottom: 1rem;">✉️ Email Addresses</h4>
                <p style="color: #666; line-height: 1.6;">
                    <strong>General:</strong> info@petcare.com<br>
                    <strong>Adoptions:</strong> adoptions@petcare.com<br>
                    <strong>Shelters:</strong> shelters@petcare.com<br>
                    <strong>Support:</strong> support@petcare.com
                </p>
            </div>
            
            <div style="margin-bottom: 2rem;">
                <h4 style="color: #667eea; margin-bottom: 1rem;">🕒 Business Hours</h4>
                <p style="color: #666; line-height: 1.6;">
                    <strong>Monday - Friday:</strong> 9:00 AM - 6:00 PM<br>
                    <strong>Saturday:</strong> 10:00 AM - 4:00 PM<br>
                    <strong>Sunday:</strong> Closed
                </p>
            </div>
            
            <div style="background: #f8f9fa; padding: 1.5rem; border-radius: 10px; margin-bottom: 2rem;">
                <h4 style="color: #333; margin-bottom: 1rem;">🚨 Emergency Support</h4>
                <p style="color: #666; line-height: 1.6; margin-bottom: 1rem;">
                    For urgent adoption or pet care issues outside business hours, please call our emergency line:
                </p>
                <p style="color: #e74c3c; font-weight: bold; font-size: 1.1rem;">
                    (555) 911-PETS
                </p>
            </div>
            
            <div>
                <h4 style="color: #667eea; margin-bottom: 1rem;">💬 Social Media</h4>
                <p style="color: #666; line-height: 1.6;">
                    Follow us on social media for updates, success stories, and pet care tips:
                </p>
                <div style="display: flex; gap: 1rem; margin-top: 1rem;">
                    <a href="#" style="color: #667eea; text-decoration: none; padding: 0.5rem 1rem; border: 2px solid #667eea; border-radius: 5px; transition: all 0.3s;" onmouseover="this.style.background='#667eea'; this.style.color='white'" onmouseout="this.style.background='transparent'; this.style.color='#667eea'">Facebook</a>
                    <a href="#" style="color: #667eea; text-decoration: none; padding: 0.5rem 1rem; border: 2px solid #667eea; border-radius: 5px; transition: all 0.3s;" onmouseover="this.style.background='#667eea'; this.style.color='white'" onmouseout="this.style.background='transparent'; this.style.color='#667eea'">Twitter</a>
                    <a href="#" style="color: #667eea; text-decoration: none; padding: 0.5rem 1rem; border: 2px solid #667eea; border-radius: 5px; transition: all 0.3s;" onmouseover="this.style.background='#667eea'; this.style.color='white'" onmouseout="this.style.background='transparent'; this.style.color='#667eea'">Instagram</a>
                </div>
            </div>
        </div>
    </div>
    
    <!-- FAQ Section -->
    <div class="card" style="margin-top: 3rem;">
        <h2 style="color: #333; margin-bottom: 2rem; text-align: center;">❓ Frequently Asked Questions</h2>
        
        <div class="grid grid-2" style="gap: 2rem;">
            <div>
                <h4 style="color: #667eea; margin-bottom: 0.5rem;">How do I adopt a pet?</h4>
                <p style="color: #666; line-height: 1.6; margin-bottom: 1.5rem;">
                    Browse available pets, create an account, and submit an adoption request. The shelter will review your application and contact you.
                </p>
                
                <h4 style="color: #667eea; margin-bottom: 0.5rem;">Is there an adoption fee?</h4>
                <p style="color: #666; line-height: 1.6; margin-bottom: 1.5rem;">
                    Adoption fees vary by pet and shelter. Fees typically cover vaccinations, spaying/neutering, and other medical care.
                </p>
                
                <h4 style="color: #667eea; margin-bottom: 0.5rem;">How long does the adoption process take?</h4>
                <p style="color: #666; line-height: 1.6; margin-bottom: 1.5rem;">
                    The process typically takes 3-7 days, depending on the shelter's review process and your availability for a meet-and-greet.
                </p>
            </div>
            
            <div>
                <h4 style="color: #667eea; margin-bottom: 0.5rem;">Can I return a pet if it doesn't work out?</h4>
                <p style="color: #666; line-height: 1.6; margin-bottom: 1.5rem;">
                    Most shelters have return policies. Contact the shelter directly to discuss your situation and their specific policies.
                </p>
                
                <h4 style="color: #667eea; margin-bottom: 0.5rem;">How do I become a shelter partner?</h4>
                <p style="color: #666; line-height: 1.6; margin-bottom: 1.5rem;">
                    Register as a shelter user and provide your shelter's license information. Our team will verify your credentials.
                </p>
                
                <h4 style="color: #667eea; margin-bottom: 0.5rem;">Is my personal information secure?</h4>
                <p style="color: #666; line-height: 1.6; margin-bottom: 1.5rem;">
                    Yes, we use industry-standard security measures to protect your data. We never share your information without your consent.
                </p>
            </div>
        </div>
    </div>
</div>

<?php include 'common/footer.php'; ?>