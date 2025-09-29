    <footer style="background: #333; color: white; text-align: center; padding: 2rem; margin-top: 4rem;">
        <div style="max-width: 1200px; margin: 0 auto;">
            <p>&copy; 2024 PetCare. All rights reserved. | 
               <a href="/PetCare/about.php" style="color: #667eea;">About</a> | 
               <a href="/PetCare/contact.php" style="color: #667eea;">Contact</a>
            </p>
            <p style="margin-top: 1rem; font-size: 0.9rem; color: #ccc;">
                Connecting loving families with pets in need of homes.
            </p>
        </div>
    </footer>
    
    <script>
        // Common JavaScript functions
        
        // Confirm delete actions
        function confirmDelete(message = 'Are you sure you want to delete this item?') {
            return confirm(message);
        }
        
        // Show/hide elements
        function toggleElement(elementId) {
            const element = document.getElementById(elementId);
            if (element) {
                element.style.display = element.style.display === 'none' ? 'block' : 'none';
            }
        }
        
        // Form validation
        function validateForm(formId) {
            const form = document.getElementById(formId);
            if (!form) return false;
            
            const requiredFields = form.querySelectorAll('[required]');
            let isValid = true;
            
            requiredFields.forEach(field => {
                if (!field.value.trim()) {
                    field.style.borderColor = '#dc3545';
                    isValid = false;
                } else {
                    field.style.borderColor = '#ddd';
                }
            });
            
            return isValid;
        }
        
        // Auto-hide alerts
        document.addEventListener('DOMContentLoaded', function() {
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(alert => {
                setTimeout(() => {
                    alert.style.opacity = '0';
                    setTimeout(() => {
                        alert.remove();
                    }, 300);
                }, 5000);
            });
        });
        
        // Image preview for file inputs
        function previewImage(input, previewId) {
            if (input.files && input.files[0]) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    const preview = document.getElementById(previewId);
                    if (preview) {
                        preview.src = e.target.result;
                        preview.style.display = 'block';
                    }
                };
                reader.readAsDataURL(input.files[0]);
            }
        }
        
        // Search functionality
        function filterTable(inputId, tableId) {
            const input = document.getElementById(inputId);
            const table = document.getElementById(tableId);
            
            if (!input || !table) return;
            
            const filter = input.value.toLowerCase();
            const rows = table.getElementsByTagName('tr');
            
            for (let i = 1; i < rows.length; i++) {
                const row = rows[i];
                const cells = row.getElementsByTagName('td');
                let found = false;
                
                for (let j = 0; j < cells.length; j++) {
                    const cell = cells[j];
                    if (cell.textContent.toLowerCase().indexOf(filter) > -1) {
                        found = true;
                        break;
                    }
                }
                
                row.style.display = found ? '' : 'none';
            }
        }
        
        // Status update functions
        function updateStatus(itemId, newStatus, type) {
            if (confirm(`Are you sure you want to change the status to ${newStatus}?`)) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = window.location.href;
                
                const idInput = document.createElement('input');
                idInput.type = 'hidden';
                idInput.name = 'item_id';
                idInput.value = itemId;
                
                const statusInput = document.createElement('input');
                statusInput.type = 'hidden';
                statusInput.name = 'new_status';
                statusInput.value = newStatus;
                
                const typeInput = document.createElement('input');
                typeInput.type = 'hidden';
                typeInput.name = 'update_type';
                typeInput.value = type;
                
                form.appendChild(idInput);
                form.appendChild(statusInput);
                form.appendChild(typeInput);
                
                document.body.appendChild(form);
                form.submit();
            }
        }
        
        // Pet image upload preview
        function handlePetImageUpload(input) {
            if (input.files && input.files[0]) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    const preview = document.getElementById('pet-image-preview');
                    if (preview) {
                        preview.src = e.target.result;
                        preview.style.display = 'block';
                    }
                };
                reader.readAsDataURL(input.files[0]);
            }
        }
        
        // Adoption request status update
        function updateAdoptionStatus(requestId, status) {
            const statusText = status.charAt(0).toUpperCase() + status.slice(1);
            if (confirm(`Are you sure you want to ${status} this adoption request?`)) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = window.location.href;
                
                const idInput = document.createElement('input');
                idInput.type = 'hidden';
                idInput.name = 'request_id';
                idInput.value = requestId;
                
                const statusInput = document.createElement('input');
                statusInput.type = 'hidden';
                statusInput.name = 'status';
                statusInput.value = status;
                
                form.appendChild(idInput);
                form.appendChild(statusInput);
                
                document.body.appendChild(form);
                form.submit();
            }
        }
        
        // Vaccination date validation
        function validateVaccinationDate() {
            const vaccinationDate = document.getElementById('vaccination_date');
            const nextDueDate = document.getElementById('next_due_date');
            
            if (vaccinationDate && nextDueDate) {
                const vacDate = new Date(vaccinationDate.value);
                const nextDate = new Date(nextDueDate.value);
                
                if (nextDate <= vacDate) {
                    alert('Next due date must be after vaccination date');
                    nextDueDate.focus();
                    return false;
                }
            }
            return true;
        }
        
        // Pet age calculation
        function calculateAge(birthDate) {
            const today = new Date();
            const birth = new Date(birthDate);
            let age = today.getFullYear() - birth.getFullYear();
            const monthDiff = today.getMonth() - birth.getMonth();
            
            if (monthDiff < 0 || (monthDiff === 0 && today.getDate() < birth.getDate())) {
                age--;
            }
            
            return age;
        }
        
        // Auto-calculate age when birth date changes
        function updateAgeFromBirthDate() {
            const birthDateInput = document.getElementById('birth_date');
            const ageInput = document.getElementById('age');
            
            if (birthDateInput && ageInput) {
                birthDateInput.addEventListener('change', function() {
                    if (this.value) {
                        ageInput.value = calculateAge(this.value);
                    }
                });
            }
        }
        
        // Initialize on page load
        document.addEventListener('DOMContentLoaded', function() {
            updateAgeFromBirthDate();
        });
    </script>
</body>
</html>