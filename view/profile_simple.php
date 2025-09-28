<!DOCTYPE html>
<html>
<head>
    <title>Profile - SmartRent</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .profile-avatar { width: 100px; height: 100px; border-radius: 50%; background: #667eea; color: white; display: flex; align-items: center; justify-content: center; font-size: 24px; cursor: pointer; margin-bottom: 20px; }
        .profile-avatar img { width: 100%; height: 100%; object-fit: cover; border-radius: 50%; }
        .tab { display: inline-block; padding: 10px 20px; background: #f0f0f0; margin-right: 5px; cursor: pointer; }
        .tab.active { background: #667eea; color: white; }
        .tab-content { border: 1px solid #ccc; padding: 20px; margin-top: 10px; }
        .tab-pane { display: none; }
        .tab-pane.active { display: block; }
        .form-group { margin-bottom: 15px; }
        .form-group label { display: block; margin-bottom: 5px; }
        .form-group input, .form-group textarea { width: 100%; padding: 8px; border: 1px solid #ccc; }
        .btn { padding: 10px 20px; background: #667eea; color: white; border: none; cursor: pointer; }
        .message { padding: 10px; margin: 10px 0; border-radius: 5px; }
        .success { background: #d4edda; color: #155724; }
        .error { background: #f8d7da; color: #721c24; }
    </style>
</head>
<body>
    <script>
        function uploadFile() {
    var fileInput = document.getElementById('profileUpload');
    var file = fileInput.files[0];
    
    if (!file) {
        showMessage('Please select a file first', 'error');
        return;
    }
    
    // Check file type
    var allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
    if (!allowedTypes.includes(file.type)) {
        showMessage('Please select a valid image file (JPG, PNG, or GIF)', 'error');
        return;
    }
    
    // Check file size (2MB max)
    if (file.size > 2 * 1024 * 1024) {
        showMessage('File size must be less than 2MB', 'error');
        return;
    }
    
    var formData = new FormData();
    formData.append('profile_picture', file);
    formData.append('action', 'upload_picture');
    
    // Show loading message
    showMessage('Uploading...', 'info');
    
    var xhr = new XMLHttpRequest();
    xhr.open('POST', '../controller/profile_controller.php', true);
    
    xhr.onload = function() {
        if (xhr.status === 200) {
            try {
                var response = JSON.parse(xhr.responseText);
                showMessage(response.message, response.success ? 'success' : 'error');
                
                if (response.success) {
                    // Reload page to show new image
                    setTimeout(function() {
                        location.reload();
                    }, 1000);
                }
            } catch (e) {
                showMessage('Upload failed - invalid response', 'error');
            }
        } else {
            showMessage('Upload failed - server error', 'error');
        }
    };
    
    xhr.onerror = function() {
        showMessage('Upload failed - network error', 'error');
    };
    
    xhr.send(formData);
}
    </script>



    <h1>Profile Settings</h1>
    
   <div class="profile-avatar" onclick="document.getElementById('profileUpload').click()">
    <?php if (!empty($user_profile['profile_picture_url']) && file_exists('../' . $user_profile['profile_picture_url'])): ?>
        <img src="../<?php echo $user_profile['profile_picture_url']; ?>" alt="Profile">
    <?php else: ?>
        <?php echo strtoupper(substr($user_profile['full_name'] ?? 'U', 0, 1)); ?>
    <?php endif; ?>
</div>

<form id="uploadForm" enctype="multipart/form-data" style="margin-bottom: 20px;">
    <input type="file" id="profileUpload" name="profile_picture" accept="image/*" style="margin-bottom: 10px;">
    <button type="button" onclick="uploadFile()" class="btn">Upload Photo</button>
</form>
    
    <div class="tab-content">
        <div id="personal" class="tab-pane active">
            <form id="profileForm">
                <div class="form-group">
                    <label>Full Name</label>
                    <input type="text" name="full_name" value="<?php echo htmlspecialchars($user_profile['full_name'] ?? ''); ?>" required>
                </div>
                <div class="form-group">
                    <label>Phone Number</label>
                    <input type="text" name="phone_number" value="<?php echo htmlspecialchars($user_profile['contact_number'] ?? ''); ?>">
                </div>
                <div class="form-group">
                    <label>NID Number</label>
                    <input type="text" name="nid_number" value="<?php echo htmlspecialchars($user_profile['nid_number'] ?? ''); ?>">
                </div>
                <div class="form-group">
                    <label>Address</label>
                    <textarea name="permanent_address"><?php echo htmlspecialchars($user_profile['permanent_address'] ?? ''); ?></textarea>
                </div>
                <button type="submit" class="btn">Update Profile</button>
            </form>
        </div>
        
        <div id="security" class="tab-pane">
            <p>Password change and 2FA settings will be here.</p>
        </div>
    </div>
    
    <div id="messages"></div>
    
    <script>
        function showTab(tabName) {
            // Hide all panes
            var panes = document.querySelectorAll('.tab-pane');
            panes.forEach(function(pane) {
                pane.classList.remove('active');
            });
            
            // Remove active from tabs
            var tabs = document.querySelectorAll('.tab');
            tabs.forEach(function(tab) {
                tab.classList.remove('active');
            });
            
            // Show selected
            document.getElementById(tabName).classList.add('active');
            event.target.classList.add('active');
        }
        
        // Handle file upload
        document.getElementById('profileUpload').addEventListener('change', function(e) {
            var file = e.target.files[0];
            if (!file) return;
            
            var formData = new FormData();
            formData.append('profile_picture', file);
            formData.append('action', 'upload_picture');
            
            fetch('../controller/profile_controller.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                showMessage(data.message, data.success ? 'success' : 'error');
                if (data.success) location.reload();
            });
        });
        
        // Handle form submit
        document.getElementById('profileForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            var formData = new FormData(this);
            formData.append('action', 'update_profile');
            
            fetch('../controller/profile_controller.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                showMessage(data.message, data.success ? 'success' : 'error');
            });
        });
        
        function showMessage(message, type) {
            var div = document.createElement('div');
            div.className = 'message ' + type;
            div.textContent = message;
            document.getElementById('messages').appendChild(div);
            
            setTimeout(function() {
                div.remove();
            }, 3000);
        }
    </script>
</body>
</html>