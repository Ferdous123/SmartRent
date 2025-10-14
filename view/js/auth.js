

var isFormSubmitting = false;


function initRegistrationForm() {
    var form = document.getElementById('registerForm');
    var userTypeSelect = document.getElementById('user_type');
    var passwordField = document.getElementById('password');
    var confirmPasswordField = document.getElementById('confirm_password');
    
    if (!form) return;
    
    if (userTypeSelect) {
        userTypeSelect.addEventListener('change', function() {
            showRoleInfo(this.value);
        });
        
        if (userTypeSelect.value) {
            showRoleInfo(userTypeSelect.value);
        }
    }
    
    setupRealTimeValidation();
    
    if (passwordField) {
        passwordField.addEventListener('input', function() {
            checkPasswordStrength(this.value);
        });
    }
    
    if (confirmPasswordField) {
        confirmPasswordField.addEventListener('input', function() {
            checkPasswordMatch();
        });
    }
    
    form.addEventListener('submit', function(e) {
        e.preventDefault();
        handleRegistration();
    });
}

function initLoginForm() {
    var form = document.getElementById('loginForm');
    
    if (!form) return;
    
    setupRealTimeValidation();
    
    form.addEventListener('submit', function(e) {
        e.preventDefault();
        handleLogin();
    });
    
    // Setup enter key handling
    var inputs = form.querySelectorAll('input');
    for (var i = 0; i < inputs.length; i++) {
        inputs[i].addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                handleLogin();
            }
        });
    }
}


function setupRealTimeValidation() {
    var inputs = document.querySelectorAll('input[required], select[required]');
    
    for (var i = 0; i < inputs.length; i++) {

        inputs[i].addEventListener('blur', function() {
            validateSingleField(this);
        });
        

        inputs[i].addEventListener('focus', function() {
            clearFieldError(this);
        });
        

        if (this.type === 'email') {
            inputs[i].addEventListener('input', function() {
                if (this.value.length > 0) {
                    validateEmail(this);
                }
            });
        }
        
        if (this.name === 'username') {
            inputs[i].addEventListener('input', function() {
                if (this.value.length > 0) {
                    validateUsername(this);
                }
            });
        }
    }
}


function showRoleInfo(userType) {
    var roleInfoContainer = document.getElementById('role_info');
    var roleCards = document.querySelectorAll('.role-card');
    
    if (!roleInfoContainer) return;
    

    for (var i = 0; i < roleCards.length; i++) {
        roleCards[i].classList.remove('active');
    }
    
    if (userType) {
        roleInfoContainer.style.display = 'block';
        var selectedCard = document.getElementById(userType + '_info');
        if (selectedCard) {
            selectedCard.classList.add('active');
        }
    } else {
        roleInfoContainer.style.display = 'none';
    }
}


function checkPasswordStrength(password) {
    var strengthElement = document.getElementById('password_strength');
    
    if (!strengthElement) return;
    
    var strength = getPasswordStrength(password);
    
    strengthElement.className = 'password-strength ' + strength;
    

    if (password.length > 0) {
        strengthElement.style.opacity = '1';
    } else {
        strengthElement.style.opacity = '0';
    }
}


function getPasswordStrength(password) {
    var score = 0;
    
    if (password.length >= 8) score++;
    if (password.match(/[a-z]/)) score++;
    if (password.match(/[A-Z]/)) score++;
    if (password.match(/[0-9]/)) score++;
    if (password.match(/[^a-zA-Z0-9]/)) score++;
    
    if (score <= 2) return 'weak';
    if (score <= 4) return 'medium';
    return 'strong';
}

function checkPasswordMatch() {
    var passwordField = document.getElementById('password');
    var confirmField = document.getElementById('confirm_password');
    var errorSpan = document.getElementById('confirm_password_error');
    
    if (!passwordField || !confirmField || !errorSpan) return;
    
    if (confirmField.value.length > 0) {
        if (passwordField.value !== confirmField.value) {
            showFieldError(confirmField, 'Passwords do not match');
        } else {
            clearFieldError(confirmField);
        }
    }
}

function validateSingleField(field) {
    var fieldName = field.name;
    var fieldValue = field.value.trim();
    
    clearFieldError(field);
    
    if (field.hasAttribute('required') && fieldValue === '') {
        showFieldError(field, 'This field is required');
        return false;
    }
    
    switch (fieldName) {
        case 'email':
            return validateEmail(field);
        case 'username':
            return validateUsername(field);
        case 'password':
            return validatePassword(field);
        case 'confirm_password':
            return validateConfirmPassword(field);
        case 'full_name':
            return validateFullName(field);
        case 'phone_number':
            return validatePhoneNumber(field);
        default:
            return true;
    }
}

function validateEmail(field) {
    var email = field.value.trim();
    var emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    
    if (email && !emailPattern.test(email)) {
        showFieldError(field, 'Please enter a valid email address');
        return false;
    }
    
    clearFieldError(field);
    return true;
}

function validateUsername(field) {
    var username = field.value.trim();
    
    if (username.length < 3) {
        showFieldError(field, 'Username must be at least 3 characters');
        return false;
    }
    
    if (username.length > 20) {
        showFieldError(field, 'Username must be less than 20 characters');
        return false;
    }
    
    if (!/^[a-zA-Z0-9_]+$/.test(username)) {
        showFieldError(field, 'Username can only contain letters, numbers, and underscores');
        return false;
    }
    
    clearFieldError(field);
    return true;
}

function validatePassword(field) {
    var password = field.value;
    
    if (password.length < 6) {
        showFieldError(field, 'Password must be at least 6 characters');
        return false;
    }
    
    if (password.length > 50) {
        showFieldError(field, 'Password must be less than 50 characters');
        return false;
    }
    
    clearFieldError(field);
    return true;
}

function validateConfirmPassword(field) {
    var password = document.getElementById('password').value;
    var confirmPassword = field.value;
    
    if (confirmPassword !== password) {
        showFieldError(field, 'Passwords do not match');
        return false;
    }
    
    clearFieldError(field);
    return true;
}

function validateFullName(field) {
    var name = field.value.trim();
    
    if (name.length < 2) {
        showFieldError(field, 'Full name must be at least 2 characters');
        return false;
    }
    
    if (name.length > 120) {
        showFieldError(field, 'Full name must be less than 120 characters');
        return false;
    }
    
    clearFieldError(field);
    return true;
}

function validatePhoneNumber(field) {
    var phone = field.value.trim();
    
    if (phone && phone.length > 0) {
        if (phone.length < 10) {
            showFieldError(field, 'Please enter a valid phone number');
            return false;
        }
        
        if (phone.length > 15) {
            showFieldError(field, 'Phone number is too long');
            return false;
        }
    }
    
    clearFieldError(field);
    return true;
}

function showFieldError(field, message) {
    var errorSpan = document.getElementById(field.name + '_error');
    
    if (errorSpan) {
        errorSpan.textContent = message;
        errorSpan.style.display = 'block';
    }
    
    field.classList.add('error');
    field.classList.remove('success');
}

function clearFieldError(field) {
    var errorSpan = document.getElementById(field.name + '_error');
    
    if (errorSpan) {
        errorSpan.textContent = '';
        errorSpan.style.display = 'none';
    }
    
    field.classList.remove('error');
    if (field.value.trim() !== '') {
        field.classList.add('success');
    }
}

function validateForm(form) {
    var isValid = true;
    var inputs = form.querySelectorAll('input[required], select[required]');
    
    for (var i = 0; i < inputs.length; i++) {
        if (!validateSingleField(inputs[i])) {
            isValid = false;
        }
    }
    
    return isValid;
}

function handleRegistration() {
    var form = document.getElementById('registerForm');
    var submitBtn = document.getElementById('registerBtn');
    
    if (isFormSubmitting) return;
    
    if (!validateForm(form)) {
        showMessage('Please fix the errors above', 'error');
        return;
    }
    
    var agreeTerms = document.getElementById('agree_terms');
    if (!agreeTerms.checked) {
        showFieldError(agreeTerms, 'You must agree to the terms and conditions');
        showMessage('Please agree to the terms and conditions', 'error');
        return;
    }
    
    isFormSubmitting = true;
    setButtonLoading(submitBtn, true);
    
    var formData = new FormData(form);
    
    var xhr = new XMLHttpRequest();
    xhr.open('POST', '../controller/auth_controller.php', true);
    
    xhr.onreadystatechange = function() {
        if (xhr.readyState === 4) {
            isFormSubmitting = false;
            setButtonLoading(submitBtn, false);
            
            if (xhr.status === 200) {
                try {
                    var response = JSON.parse(xhr.responseText);
                    handleAuthResponse(response);
                } catch (e) {
                    showMessage('Invalid server response', 'error');
                }
            } else {
                showMessage('Server error. Please try again.', 'error');
            }
        }
    };
    
    xhr.send(formData);
}

function handleLogin() {
    var form = document.getElementById('loginForm');
    var submitBtn = document.getElementById('loginBtn');
    
    if (isFormSubmitting) return;
    
    if (!validateForm(form)) {
        showMessage('Please fix the errors above', 'error');
        return;
    }
    
    isFormSubmitting = true;
    setButtonLoading(submitBtn, true);
    
    var formData = new FormData(form);
    
    var xhr = new XMLHttpRequest();
    xhr.open('POST', '../controller/auth_controller.php', true);
    
    xhr.onreadystatechange = function() {
        if (xhr.readyState === 4) {
            isFormSubmitting = false;
            setButtonLoading(submitBtn, false);
            
            if (xhr.status === 200) {
                try {
                    var response = JSON.parse(xhr.responseText);
                    handleAuthResponse(response);
                } catch (e) {
                    showMessage('Invalid server response', 'error');
                }
            } else {
                showMessage('Server error. Please try again.', 'error');
            }
        }
    };
    
    xhr.send(formData);
}

function handleAuthResponse(response) {
    
    if (response.success) {
        showMessage(response.message, 'success');
        
        setTimeout(function() {
            if (response.redirect) {
                window.location.href = response.redirect;
            }
        }, 1500);
        
    } else {
        showMessage(response.message, 'error');
        
        if (response.field_errors) {
            for (var fieldName in response.field_errors) {
                var field = document.querySelector('[name="' + fieldName + '"]');
                if (field) {
                    showFieldError(field, response.field_errors[fieldName]);
                }
            }
        }
    }
}

function showMessage(message, type) {
    var container = document.getElementById('message_container');
    
    if (!container) {
        container = document.createElement('div');
        container.id = 'message_container';
        container.className = 'message-container';
        document.body.appendChild(container);
    }
    
    var messageDiv = document.createElement('div');
    messageDiv.className = 'message ' + type;
    messageDiv.textContent = message;
    
    container.appendChild(messageDiv);
    
    setTimeout(function() {
        if (messageDiv.parentNode) {
            messageDiv.style.animation = 'slideOutRight 0.5s ease forwards';
            setTimeout(function() {
                if (messageDiv.parentNode) {
                    container.removeChild(messageDiv);
                }
            }, 500);
        }
    }, 5000);
}

function setButtonLoading(button, loading) {
    if (!button) return;
    
    var btnText = button.querySelector('.btn-text');
    var btnLoading = button.querySelector('.btn-loading');
    
    if (loading) {
        button.disabled = true;
        if (btnText) btnText.style.display = 'none';
        if (btnLoading) btnLoading.style.display = 'inline';
        button.style.opacity = '0.8';
    } else {
        button.disabled = false;
        if (btnText) btnText.style.display = 'inline';
        if (btnLoading) btnLoading.style.display = 'none';
        button.style.opacity = '1';
    }
}

var usernameCheckTimeout;
function checkUsernameAvailability(username) {
    clearTimeout(usernameCheckTimeout);
    
    usernameCheckTimeout = setTimeout(function() {
        if (username.length >= 3) {
            var xhr = new XMLHttpRequest();
            xhr.open('POST', '../controller/auth_controller.php', true);
            xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
            
            xhr.onreadystatechange = function() {
                if (xhr.readyState === 4 && xhr.status === 200) {
                    try {
                        var response = JSON.parse(xhr.responseText);
                        var usernameField = document.getElementById('username');
                        
                        if (response.available) {
                            clearFieldError(usernameField);
                        } else {
                            showFieldError(usernameField, 'Username is already taken');
                        }
                    } catch (e) {
                    }
                }
            };
            
            xhr.send('action=check_username&username=' + encodeURIComponent(username));
        }
    }, 500);
}

function setupUsernameCheck() {
    var usernameField = document.getElementById('username');
    
    if (usernameField) {
        usernameField.addEventListener('input', function() {
            var username = this.value.trim();
            if (username.length >= 3) {
                checkUsernameAvailability(username);
            }
        });
    }
}

document.addEventListener('DOMContentLoaded', function() {
    if (document.getElementById('registerForm')) {
        initRegistrationForm();
        setupUsernameCheck();
    }
    
    if (document.getElementById('loginForm')) {
        initLoginForm();
    }
});

window.addEventListener('beforeunload', function() {
    isFormSubmitting = false;
});

window.addEventListener('popstate', function() {
    isFormSubmitting = false;
});

