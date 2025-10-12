// Tenants Page JavaScript

function initTenantsPage() {
    console.log('Initializing tenants page...');
    loadAllTenants();
    loadAvailableTenantsForDropdown();
    loadBuildingsForDropdown();
}

// Tab switching
function switchTab(tabName) {
    // Hide all tabs
    document.querySelectorAll('.tab-content').forEach(function(tab) {
        tab.classList.remove('active');
    });
    
    // Remove active from all buttons
    document.querySelectorAll('.tab-btn').forEach(function(btn) {
        btn.classList.remove('active');
    });
    
    // Show selected tab
    document.getElementById('tab-' + tabName).classList.add('active');
    event.target.classList.add('active');
    
    // Load data for tab
    switch(tabName) {
        case 'all':
            loadAllTenants();
            break;
        case 'active':
            loadActiveTenants();
            break;
        case 'pending':
            loadPendingTenants();
            break;
        case 'available':
            loadAvailableTenants();
            break;
    }
}

// Load tenants
function loadAllTenants() {
    // TODO: Implement tenant loading
    console.log('Loading all tenants...');
}

function loadActiveTenants() {
    // TODO: Implement
    console.log('Loading active tenants...');
}

function loadPendingTenants() {
    // TODO: Implement
    console.log('Loading pending tenants...');
}

function loadAvailableTenants() {
    // TODO: Implement
    console.log('Loading available tenants...');
}

// Modal functions
function showAddTenantModal() {
    document.getElementById('addTenantModal').style.display = 'flex';
}

function closeAddTenantModal() {
    document.getElementById('addTenantModal').style.display = 'none';
}

function showAssignTenantModal() {
    document.getElementById('assignTenantModal').style.display = 'flex';
}

function closeAssignTenantModal() {
    document.getElementById('assignTenantModal').style.display = 'none';
}

function showGenerateOTPModal() {
    document.getElementById('generateOTPModal').style.display = 'flex';
}

function closeGenerateOTPModal() {
    document.getElementById('generateOTPModal').style.display = 'none';
    document.getElementById('otpResult').style.display = 'none';
    document.getElementById('otpFormActions').style.display = 'flex';
    document.getElementById('generateOTPForm').reset();
}

// Form handlers
function handleAddTenant(e) {
    e.preventDefault();
    showMessage('Add tenant functionality coming soon', 'info');
}

function handleAssignTenant(e) {
    e.preventDefault();
    showMessage('Assign tenant functionality coming soon', 'info');
}

function handleGenerateOTP(e) {
    e.preventDefault();
    
    // Simulate OTP generation
    var otp = Math.floor(100000 + Math.random() * 900000).toString();
    
    document.getElementById('otpCode').textContent = otp;
    document.getElementById('otpResult').style.display = 'block';
    document.getElementById('otpFormActions').style.display = 'none';
    
    showMessage('OTP generated successfully!', 'success');
}

function copyOTP() {
    var otp = document.getElementById('otpCode').textContent;
    navigator.clipboard.writeText(otp);
    showMessage('OTP copied to clipboard!', 'success');
}

function loadAvailableTenantsForDropdown() {
    // TODO: Load tenants for dropdown
}

function loadBuildingsForDropdown() {
    // TODO: Load buildings for dropdown
}

function loadFlats(buildingId) {
    // TODO: Load flats for selected building
}

function loadOTPFlats(buildingId) {
    // TODO: Load flats for OTP generation
}

function refreshTenants() {
    loadAllTenants();
    showMessage('Refreshing tenants...', 'info');
}