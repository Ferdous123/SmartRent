
document.addEventListener('DOMContentLoaded', function() {
    initLandingPage();
});

// Initialize all landing page functionality
function initLandingPage() {
    setupNavbarScroll();
    setupSmoothScrolling();
    setupAnimateOnScroll();
    setupCardHoverEffects();
    setupFormValidation();
}

// Navbar scroll effect
function setupNavbarScroll() {
    var navbar = document.querySelector('.navbar');
    
    window.addEventListener('scroll', function() {
        var scrolled = window.pageYOffset;
        
        if (scrolled > 100) {
            navbar.style.background = 'linear-gradient(135deg, rgba(102,126,234,0.95) 0%, rgba(118,75,162,0.95) 100%)';
            navbar.style.backdropFilter = 'blur(10px)';
        } else {
            navbar.style.background = 'linear-gradient(135deg, #667eea 0%, #764ba2 100%)';
            navbar.style.backdropFilter = 'none';
        }
    });
}

// Smooth scrolling for navigation links
function setupSmoothScrolling() {
    var navLinks = document.querySelectorAll('.nav-menu a[href^="#"]');
    
    for (var i = 0; i < navLinks.length; i++) {
        navLinks[i].addEventListener('click', function(e) {
            e.preventDefault();
            
            var targetId = this.getAttribute('href').substring(1);
            var targetElement = document.getElementById(targetId);
            
            if (targetElement) {
                var offsetTop = targetElement.offsetTop - 80;
                
                window.scrollTo({
                    top: offsetTop,
                    behavior: 'smooth'
                });
            }
        });
    }
}

// Animate elements on scroll
function setupAnimateOnScroll() {
    var elements = document.querySelectorAll('.feature-item, .user-card');
    
    function checkScroll() {
        var windowHeight = window.innerHeight;
        var scrollTop = window.pageYOffset;
        
        for (var i = 0; i < elements.length; i++) {
            var element = elements[i];
            var elementTop = element.offsetTop;
            
            if (scrollTop + windowHeight > elementTop + 100) {
                element.style.opacity = '1';
                element.style.transform = 'translateY(0)';
                element.style.transition = 'all 0.6s ease';
            }
        }
    }
    
    // Initialize elements as hidden
    for (var i = 0; i < elements.length; i++) {
        elements[i].style.opacity = '0';
        elements[i].style.transform = 'translateY(30px)';
    }
    
    window.addEventListener('scroll', checkScroll);
    checkScroll(); // Check on load
}

// Enhanced card hover effects
function setupCardHoverEffects() {
    var userCards = document.querySelectorAll('.user-card');
    var featureItems = document.querySelectorAll('.feature-item');
    
    // User cards hover effect
    for (var i = 0; i < userCards.length; i++) {
        userCards[i].addEventListener('mouseenter', function() {
            this.style.transform = 'translateY(-15px) scale(1.02)';
            this.style.boxShadow = '0 30px 60px rgba(0,0,0,0.25)';
        });
        
        userCards[i].addEventListener('mouseleave', function() {
            this.style.transform = 'translateY(0) scale(1)';
            this.style.boxShadow = '0 15px 35px rgba(0,0,0,0.1)';
        });
    }
    
    // Feature items hover effect
    for (var i = 0; i < featureItems.length; i++) {
        featureItems[i].addEventListener('mouseenter', function() {
            var icon = this.querySelector('.feature-icon');
            if (icon) {
                icon.style.transform = 'scale(1.2) rotateY(360deg)';
                icon.style.transition = 'all 0.6s ease';
            }
        });
        
        featureItems[i].addEventListener('mouseleave', function() {
            var icon = this.querySelector('.feature-icon');
            if (icon) {
                icon.style.transform = 'scale(1) rotateY(0deg)';
            }
        });
    }
}

// Basic form validation for future forms
function setupFormValidation() {
    // This will be used for registration/login forms
    var forms = document.querySelectorAll('form');
    
    for (var i = 0; i < forms.length; i++) {
        forms[i].addEventListener('submit', function(e) {
            if (!validateForm(this)) {
                e.preventDefault();
                showFormErrors(this);
            }
        });
    }
}

// Validate form function
function validateForm(form) {
    var isValid = true;
    var inputs = form.querySelectorAll('input[required], select[required], textarea[required]');
    
    // Remove previous error messages
    var errorSpans = form.querySelectorAll('.error-message');
    for (var i = 0; i < errorSpans.length; i++) {
        errorSpans[i].remove();
    }
    
    for (var i = 0; i < inputs.length; i++) {
        var input = inputs[i];
        var value = input.value.trim();
        
        if (value === '') {
            isValid = false;
            showInputError(input, 'This field is required');
        } else if (input.type === 'email' && !isValidEmail(value)) {
            isValid = false;
            showInputError(input, 'Please enter a valid email address');
        } else if (input.type === 'password' && value.length < 6) {
            isValid = false;
            showInputError(input, 'Password must be at least 6 characters');
        }
    }
    
    return isValid;
}

// Show input error message
function showInputError(input, message) {
    var errorSpan = document.createElement('span');
    errorSpan.className = 'error-message';
    errorSpan.textContent = message;
    errorSpan.style.color = '#ff4444';
    errorSpan.style.fontSize = '14px';
    errorSpan.style.display = 'block';
    errorSpan.style.marginTop = '5px';
    errorSpan.style.animation = 'fadeIn 0.3s ease';
    
    input.style.borderColor = '#ff4444';
    input.style.boxShadow = '0 0 5px rgba(255,68,68,0.3)';
    
    input.parentNode.appendChild(errorSpan);
}

// Email validation
function isValidEmail(email) {
    var emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return emailPattern.test(email);
}

// Show success message
function showSuccessMessage(message) {
    var successDiv = document.createElement('div');
    successDiv.className = 'success-message';
    successDiv.textContent = message;
    successDiv.style.cssText = `
        position: fixed;
        top: 100px;
        right: 20px;
        background: linear-gradient(135deg, #00c851 0%, #007e33 100%);
        color: white;
        padding: 15px 25px;
        border-radius: 10px;
        box-shadow: 0 10px 30px rgba(0,200,81,0.3);
        z-index: 9999;
        animation: slideInRight 0.5s ease;
    `;
    
    document.body.appendChild(successDiv);
    
    setTimeout(function() {
        successDiv.style.animation = 'slideOutRight 0.5s ease';
        setTimeout(function() {
            document.body.removeChild(successDiv);
        }, 500);
    }, 3000);
}

// Show error message
function showErrorMessage(message) {
    var errorDiv = document.createElement('div');
    errorDiv.className = 'error-message';
    errorDiv.textContent = message;
    errorDiv.style.cssText = `
        position: fixed;
        top: 100px;
        right: 20px;
        background: linear-gradient(135deg, #ff4444 0%, #cc0000 100%);
        color: white;
        padding: 15px 25px;
        border-radius: 10px;
        box-shadow: 0 10px 30px rgba(255,68,68,0.3);
        z-index: 9999;
        animation: slideInRight 0.5s ease;
    `;
    
    document.body.appendChild(errorDiv);
    
    setTimeout(function() {
        errorDiv.style.animation = 'slideOutRight 0.5s ease';
        setTimeout(function() {
            document.body.removeChild(errorDiv);
        }, 500);
    }, 4000);
}

// Button loading state
function setButtonLoading(button, loading) {
    if (loading) {
        button.originalText = button.textContent;
        button.textContent = 'Loading...';
        button.disabled = true;
        button.style.opacity = '0.7';
        button.style.cursor = 'not-allowed';
    } else {
        button.textContent = button.originalText || button.textContent;
        button.disabled = false;
        button.style.opacity = '1';
        button.style.cursor = 'pointer';
    }
}

// Add CSS animations dynamically
function addDynamicStyles() {
    var style = document.createElement('style');
    style.textContent = `
        @keyframes slideInRight {
            from {
                opacity: 0;
                transform: translateX(100px);
            }
            to {
                opacity: 1;
                transform: translateX(0);
            }
        }
        
        @keyframes slideOutRight {
            from {
                opacity: 1;
                transform: translateX(0);
            }
            to {
                opacity: 0;
                transform: translateX(100px);
            }
        }
        
        @keyframes fadeIn {
            from {
                opacity: 0;
            }
            to {
                opacity: 1;
            }
        }
    `;
    document.head.appendChild(style);
}

// Initialize dynamic styles
addDynamicStyles();

// Page performance optimization
function optimizePagePerformance() {
    // Lazy load images when they come into viewport
    var images = document.querySelectorAll('img');
    var imageObserver = new IntersectionObserver(function(entries) {
        entries.forEach(function(entry) {
            if (entry.isIntersecting) {
                var img = entry.target;
                if (img.dataset.src) {
                    img.src = img.dataset.src;
                    img.classList.remove('lazy');
                    imageObserver.unobserve(img);
                }
            }
        });
    });
    
    for (var i = 0; i < images.length; i++) {
        if (images[i].dataset.src) {
            imageObserver.observe(images[i]);
        }
    }
}

// Call optimization when page loads
window.addEventListener('load', optimizePagePerformance);