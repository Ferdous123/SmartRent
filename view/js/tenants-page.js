// Tenants Management Page JavaScript
// Global variables
var currentTab = 'all';
var allTenants = [];
var pendingAssignments = [];
var outstandingTenants = [];
var availableFlats = [];
var currentTenantId = null;
var currentAssignmentId = null;

// Initialize page
document.addEventListener('DOMContentLoaded', function() {
    loadAllData();
    setupEventListeners();
    
    // Check URL parameters - open add modal if requested
    var urlParams = new URLSearchParams(window.location.search);
    if (urlParams.get('action') === 'add') {
        setTimeout(function() {
            showAddTenantModal();
        }, 500);
    }
    
    // Switch to specific tab if requested
    var tab = urlParams.get('tab');
    if (tab && (tab === 'pending' || tab === 'outstanding')) {
        setTimeout(function() {
            switchTenantsTab(tab);
        }, 300);
    }
});

// Setup event listeners
function setupEventListeners() {
    // Add Tenant Form - OTP
    var otpForm = document.getElementById('otpForm');
    if (otpForm) {
        otpForm.addEventListener('submit', function(e) {
            e.preventDefault();
            handleGenerateOTP();
        });
    }
    
    // Add Tenant Form - Direct
    var directForm = document.getElementById('directForm');
    if (directForm) {
        directForm.addEventListener('submit', function(e) {
            e.preventDefault();
            handleDirectAssignment();
        });
    }
    
    // Add Tenant Form - Generate
    var generateForm = document.getElementById('generateForm');
    if (generateForm) {
        generateForm.addEventListener('submit', function(e) {
            e.preventDefault();
            handleGenerateCredentials();
        });
    }
    
    // Move Tenant Form
    var moveForm = document.getElementById('moveTenantForm');
    if (moveForm) {
        moveForm.addEventListener('submit', function(e) {
            e.preventDefault();
            handleMoveTenant();
        });
    }
    
    // End Tenancy Form
    var endForm = document.getElementById('endTenancyForm');
    if (endForm) {
        endForm.addEventListener('submit', function(e) {
            e.preventDefault();
            handleSendEndNotice();
        });
    }
    
    // Calculate advance balance when inputs change
    var transferInput = document.getElementById('transfer_advance');
    var additionalInput = document.getElementById('additional_advance');
    if (transferInput && additionalInput) {
        transferInput.addEventListener('input', calculateNewAdvance);
        additionalInput.addEventListener('input', calculateNewAdvance);
    }
}

// Load all data
function loadAllData() {
    loadTenants();
    loadPendingAssignments();
    loadOutstandingTenants();
    loadAvailableFlats();
    loadBuildings(); // Add this line
}

// Load tenants
function loadTenants() {
    var xhr = new XMLHttpRequest();
    xhr.open('POST', '../controller/tenant_controller.php', true);
    xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
    
    xhr.onreadystatechange = function() {
        if (xhr.readyState === 4 && xhr.status === 200) {
            try {
                var response = JSON.parse(xhr.responseText);
                if (response.success) {
                    allTenants = response.tenants;
                    displayTenants(response.tenants);
                    populateBuildingFilter(response.tenants); // Add this line
                } else {
                    showError('Failed to load tenants');
                }
            } catch (e) {
                console.error('Error loading tenants:', e);
                showError('Error loading tenants');
            }
        }
    };
    
    xhr.send('action=get_tenants');
}

// Load buildings for filter dropdown
function loadBuildings() {
    var xhr = new XMLHttpRequest();
    xhr.open('POST', '../controller/tenant_controller.php', true);
    xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
    
    xhr.onreadystatechange = function() {
        if (xhr.readyState === 4 && xhr.status === 200) {
            try {
                var response = JSON.parse(xhr.responseText);
                if (response.success) {
                    populateBuildingFilter(response.buildings);
                }
            } catch (e) {
                console.error('Error loading buildings:', e);
            }
        }
    };
    
    xhr.send('action=get_buildings');
}

// Populate building filter dropdown
function populateBuildingFilter(buildings) {
    var buildingSelect = document.getElementById('buildingFilter');
    if (!buildingSelect) return;
    
    buildingSelect.innerHTML = '<option value="">All Buildings</option>';
    
    for (var i = 0; i < buildings.length; i++) {
        var option = document.createElement('option');
        option.value = buildings[i].building_name;
        option.textContent = buildings[i].building_name;
        buildingSelect.appendChild(option);
    }
}

// Display tenants
function displayTenants(tenants) {
    var tbody = document.getElementById('tenantsTableBody');
    
    if (!tenants || tenants.length === 0) {
        tbody.innerHTML = '<tr><td colspan="8" style="text-align: center; padding: 2rem;">' +
            '<div class="empty-state">' +
            '<div class="empty-state-icon">👥</div>' +
            '<h4>No Tenants Found</h4>' +
            '<p>Start by adding your first tenant</p>' +
            '</div></td></tr>';
        return;
    }
    
    var html = '';
    for (var i = 0; i < tenants.length; i++) {
        var tenant = tenants[i];
        var outstanding = parseFloat(tenant.total_outstanding || 0);
        var statusClass = outstanding > 0 ? 'overdue' : 'paid';
        var statusText = outstanding > 0 ? 'Overdue' : 'Paid';
        
        html += '<tr>' +
            '<td>' +
                '<div class="tenant-photo">' +
                    (tenant.profile_picture_url ? 
                        '<img src="' + escapeHtml(tenant.profile_picture_url) + '" alt="Photo">' :
                        '<span>' + escapeHtml(tenant.full_name.charAt(0).toUpperCase()) + '</span>') +
                '</div>' +
            '</td>' +
            '<td>' + escapeHtml(tenant.full_name) + '</td>' +
            '<td>' +
                '<div class="flat-details">' +
                    (tenant.flat_details || 'No flats assigned') +
                '</div>' +
            '</td>' +
            '<td>' + (tenant.contact_number || 'N/A') + '</td>' +
            '<td>৳' + formatNumber(tenant.total_advance || 0) + '</td>' +
            '<td>' + (outstanding > 0 ? '৳' + formatNumber(outstanding) : '৳0.00') + '</td>' +
            '<td><span class="status-badge ' + statusClass + '">' + statusText + '</span></td>' +
            '<td>' +
                '<div class="action-buttons">' +
                    '<button class="btn-small btn-view" onclick="viewTenantDetails(' + tenant.user_id + ')">View</button>' +
                    '<button class="btn-small btn-message" onclick="sendMessage(' + tenant.user_id + ')">Message</button>' +
                '</div>' +
            '</td>' +'</tr>';
    }
    
    tbody.innerHTML = html;
}

// Load pending assignments
function loadPendingAssignments() {
    var xhr = new XMLHttpRequest();
    xhr.open('POST', '../controller/tenant_controller.php', true);
    xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
    
    xhr.onreadystatechange = function() {
        if (xhr.readyState === 4 && xhr.status === 200) {
            try {
                var response = JSON.parse(xhr.responseText);
                if (response.success) {
                    pendingAssignments = response.assignments;
                    displayPendingAssignments(response.assignments);
                }
            } catch (e) {
                console.error('Error loading pending assignments:', e);
            }
        }
    };
    
    xhr.send('action=get_pending_assignments');
}

// Display pending assignments
function displayPendingAssignments(assignments) {
    var tbody = document.getElementById('pendingTableBody');
    
    if (!assignments || assignments.length === 0) {
        tbody.innerHTML = '<tr><td colspan="7" style="text-align: center; padding: 2rem;">' +
            '<div class="empty-state">' +
            '<div class="empty-state-icon">✓</div>' +
            '<h4>No Pending Assignments</h4>' +
            '<p>All assignments are confirmed</p>' +
            '</div></td></tr>';
        return;
    }
    
    var html = '';
    for (var i = 0; i < assignments.length; i++) {
        var assignment = assignments[i];
        var hours = Math.floor(assignment.seconds_remaining / 3600);
        var minutes = Math.floor((assignment.seconds_remaining % 3600) / 60);
        var timeClass = hours < 6 ? 'critical' : hours < 12 ? 'warning' : '';
        
        html += '<tr>' +
            '<td>' + escapeHtml(assignment.flat_number) + '</td>' +
            '<td>' + escapeHtml(assignment.building_name) + '</td>' +
            '<td>' + (assignment.tenant_name || 'Awaiting claim') + '</td>' +
            '<td><span class="status-badge ' + assignment.assignment_type + '">' + assignment.assignment_type + '</span></td>' +
            '<td>৳' + formatNumber(assignment.advance_amount) + '</td>' +
            '<td>' +
                '<div class="countdown-timer ' + timeClass + '">' +
                    '⏱ ' + hours + 'h ' + minutes + 'm' +
                '</div>' +
            '</td>' +
            '<td>' +
                '<div class="action-buttons">' +
                    '<button class="btn-small btn-cancel" onclick="cancelAssignment(' + assignment.assignment_id + ')">Cancel</button>' +
                '</div>' +
            '</td>' +
        '</tr>';
    }
    
    tbody.innerHTML = html;
}

// Load outstanding tenants
function loadOutstandingTenants() {
    var xhr = new XMLHttpRequest();
    xhr.open('POST', '../controller/tenant_controller.php', true);
    xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
    
    xhr.onreadystatechange = function() {
        if (xhr.readyState === 4 && xhr.status === 200) {
            try {
                var response = JSON.parse(xhr.responseText);
                if (response.success) {
                    outstandingTenants = response.tenants;
                    displayOutstandingTenants(response.tenants);
                }
            } catch (e) {
                console.error('Error loading outstanding tenants:', e);
            }
        }
    };
    
    xhr.send('action=get_outstanding_tenants');
}

// Display outstanding tenants
function displayOutstandingTenants(tenants) {
    var tbody = document.getElementById('outstandingTableBody');
    
    if (!tenants || tenants.length === 0) {
        tbody.innerHTML = '<tr><td colspan="6" style="text-align: center; padding: 2rem;">' +
            '<div class="empty-state">' +
            '<div class="empty-state-icon">✓</div>' +
            '<h4>No Outstanding Payments</h4>' +
            '<p>All tenants are up to date</p>' +
            '</div></td></tr>';
        return;
    }
    
    var html = '';
    for (var i = 0; i < tenants.length; i++) {
        var tenant = tenants[i];
        
        html += '<tr>' +
            '<td>' + escapeHtml(tenant.full_name) + '</td>' +
            '<td>' + (tenant.contact_number || 'N/A') + '</td>' +
            '<td style="color: #e74c3c; font-weight: bold;">৳' + formatNumber(tenant.total_outstanding) + '</td>' +
            '<td>' + tenant.overdue_count + '</td>' +
            '<td>' + (tenant.days_overdue > 0 ? tenant.days_overdue + ' days' : 'Due now') + '</td>' +
            '<td>' +
                '<div class="action-buttons">' +
                    '<button class="btn-small btn-remind" onclick="sendPaymentReminder(' + tenant.user_id + ')">Send Reminder</button>' +
                    '<button class="btn-small btn-view" onclick="viewTenantDetails(' + tenant.user_id + ')">View</button>' +
                '</div>' +
            '</td>' +
        '</tr>';
    }
    
    tbody.innerHTML = html;
}

// Load available flats
function loadAvailableFlats() {
    var xhr = new XMLHttpRequest();
    xhr.open('POST', '../controller/tenant_controller.php', true);
    xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
    
    xhr.onreadystatechange = function() {
        if (xhr.readyState === 4 && xhr.status === 200) {
            try {
                var response = JSON.parse(xhr.responseText);
                if (response.success) {
                    availableFlats = response.flats;
                    populateFlatDropdowns(response.flats);
                }
            } catch (e) {
                console.error('Error loading flats:', e);
            }
        }
    };
    
    xhr.send('action=get_available_flats');
}

// Populate flat dropdowns
function populateFlatDropdowns(flats) {
    var dropdowns = ['otp_flat_id', 'direct_flat_id', 'generate_flat_id', 'new_flat_id'];
    
    for (var d = 0; d < dropdowns.length; d++) {
        var select = document.getElementById(dropdowns[d]);
        if (select) {
            select.innerHTML = '<option value="">-- Select Flat --</option>';
            for (var i = 0; i < flats.length; i++) {
                var flat = flats[i];
                var option = document.createElement('option');
                option.value = flat.flat_id;
                option.textContent = flat.building_name + ' - ' + flat.flat_number + ' (Floor ' + flat.floor_number + ')';
                select.appendChild(option);
            }
        }
    }
}

// Switch tabs
function switchTenantsTab(tab) {
    currentTab = tab;
    
    var tabs = document.querySelectorAll('.tab-btn');
    for (var i = 0; i < tabs.length; i++) {
        tabs[i].classList.remove('active');
    }
    
    var panes = document.querySelectorAll('.tab-pane');
    for (var i = 0; i < panes.length; i++) {
        panes[i].classList.remove('active');
    }
    
    if (tab === 'all') {
        tabs[0].classList.add('active');
        document.getElementById('allTenantsTab').classList.add('active');
    } else if (tab === 'pending') {
        tabs[1].classList.add('active');
        document.getElementById('pendingTab').classList.add('active');
    } else if (tab === 'outstanding') {
        tabs[2].classList.add('active');
        document.getElementById('outstandingTab').classList.add('active');
    }
}

// Search tenants
function searchTenants() {
    var input = document.getElementById('searchInput');
    var filter = input.value.toLowerCase();
    var table = document.getElementById('tenantsTable');
    var tr = table.getElementsByTagName('tr');
    
    for (var i = 1; i < tr.length; i++) {
        var td = tr[i].getElementsByTagName('td');
        var found = false;
        
        for (var j = 0; j < td.length; j++) {
            if (td[j]) {
                var txtValue = td[j].textContent || td[j].innerText;
                if (txtValue.toLowerCase().indexOf(filter) > -1) {
                    found = true;
                    break;
                }
            }
        }
        
        tr[i].style.display = found ? '' : 'none';
    }
}

// Enhanced filter tenants
function filterTenants() {
    var buildingFilter = document.getElementById('buildingFilter').value.toLowerCase();
    var statusFilter = document.getElementById('statusFilter').value;
    var moveInFilter = document.getElementById('moveInFilter').value;
    var moveOutFilter = document.getElementById('moveOutFilter').value;
    
    var table = document.getElementById('tenantsTable');
    var tr = table.getElementsByTagName('tr');
    
    for (var i = 1; i < tr.length; i++) {
        var show = true;
        
        // Building filter
        if (buildingFilter) {
            var flatDetails = tr[i].getElementsByTagName('td')[2].textContent.toLowerCase();
            if (flatDetails.indexOf(buildingFilter) === -1) {
                show = false;
            }
        }
        
        // Status filter
        if (statusFilter) {
            var statusBadge = tr[i].querySelector('.status-badge');
            if (statusBadge && !statusBadge.classList.contains(statusFilter)) {
                show = false;
            }
        }
        
        // Move-in filter
        if (moveInFilter && show) {
            var tenant = findTenantByRow(i);
            if (tenant) {
                show = checkMoveInFilter(tenant, moveInFilter);
            }
        }
        
        // Move-out filter
        if (moveOutFilter && show) {
            var tenant = findTenantByRow(i);
            if (tenant) {
                show = checkMoveOutFilter(tenant, moveOutFilter);
            }
        }
        
        tr[i].style.display = show ? '' : 'none';
    }
}

// Find tenant by table row index
function findTenantByRow(rowIndex) {
    var table = document.getElementById('tenantsTable');
    var rows = table.getElementsByTagName('tr');
    var tenantName = rows[rowIndex].getElementsByTagName('td')[1].textContent;
    
    for (var i = 0; i < allTenants.length; i++) {
        if (allTenants[i].full_name === tenantName) {
            return allTenants[i];
        }
    }
    return null;
}

// Check move-in filter
function checkMoveInFilter(tenant, filter) {
    if (!tenant.assigned_flats || tenant.assigned_flats.length === 0) {
        return false;
    }
    
    var now = new Date();
    var hasMatch = false;
    
    for (var i = 0; i < tenant.assigned_flats.length; i++) {
        var moveInDate = new Date(tenant.assigned_flats[i].confirmed_at);
        var monthsDiff = getMonthsDifference(moveInDate, now);
        
        switch(filter) {
            case 'this_month':
                if (moveInDate.getMonth() === now.getMonth() && 
                    moveInDate.getFullYear() === now.getFullYear()) {
                    hasMatch = true;
                }
                break;
            case 'last_3_months':
                if (monthsDiff <= 3) {
                    hasMatch = true;
                }
                break;
            case 'last_6_months':
                if (monthsDiff <= 6) {
                    hasMatch = true;
                }
                break;
            case 'older':
                if (monthsDiff > 6) {
                    hasMatch = true;
                }
                break;
        }
    }
    
    return hasMatch;
}

// Check move-out filter
function checkMoveOutFilter(tenant, filter) {
    if (!tenant.assigned_flats || tenant.assigned_flats.length === 0) {
        return false;
    }
    
    var now = new Date();
    var hasMatch = false;
    
    for (var i = 0; i < tenant.assigned_flats.length; i++) {
        var flat = tenant.assigned_flats[i];
        
        if (filter === 'has_notice') {
            if (flat.end_notice_sent_at) {
                hasMatch = true;
            }
        } else if (flat.move_out_date) {
            var moveOutDate = new Date(flat.move_out_date);
            var monthsDiff = getMonthsDifference(now, moveOutDate);
            
            switch(filter) {
                case 'this_month':
                    if (moveOutDate.getMonth() === now.getMonth() && 
                        moveOutDate.getFullYear() === now.getFullYear()) {
                        hasMatch = true;
                    }
                    break;
                case 'next_month':
                    var nextMonth = new Date(now.getFullYear(), now.getMonth() + 1, 1);
                    if (moveOutDate.getMonth() === nextMonth.getMonth() && 
                        moveOutDate.getFullYear() === nextMonth.getFullYear()) {
                        hasMatch = true;
                    }
                    break;
                case 'next_3_months':
                    if (monthsDiff >= 0 && monthsDiff <= 3) {
                        hasMatch = true;
                    }
                    break;
            }
        }
    }
    
    return hasMatch;
}

// Calculate months difference
function getMonthsDifference(date1, date2) {
    var months = (date2.getFullYear() - date1.getFullYear()) * 12;
    months -= date1.getMonth();
    months += date2.getMonth();
    return months;
}

// Reset all filters
function resetFilters() {
    document.getElementById('searchInput').value = '';
    document.getElementById('buildingFilter').value = '';
    document.getElementById('statusFilter').value = '';
    document.getElementById('moveInFilter').value = '';
    document.getElementById('moveOutFilter').value = '';
    
    var table = document.getElementById('tenantsTable');
    var tr = table.getElementsByTagName('tr');
    
    for (var i = 1; i < tr.length; i++) {
        tr[i].style.display = '';
    }
}

// Show add tenant modal
function showAddTenantModal() {
    document.getElementById('addTenantModal').style.display = 'flex';
    switchAddMethod('otp');
}

// Close add tenant modal
function closeAddTenantModal() {
    document.getElementById('addTenantModal').style.display = 'none';
    
    document.getElementById('otpForm').reset();
    document.getElementById('directForm').reset();
    document.getElementById('generateForm').reset();
    
    document.getElementById('otpDisplay').style.display = 'none';
    document.getElementById('credentialsDisplay').style.display = 'none';
}

// Switch add method
function switchAddMethod(method) {
    var tabs = document.querySelectorAll('.method-tab');
    for (var i = 0; i < tabs.length; i++) {
        tabs[i].classList.remove('active');
    }
    
    var contents = document.querySelectorAll('.method-content');
    for (var i = 0; i < contents.length; i++) {
        contents[i].classList.remove('active');
    }
    
    if (method === 'otp') {
        tabs[0].classList.add('active');
        document.getElementById('otpMethod').classList.add('active');
    } else if (method === 'direct') {
        tabs[1].classList.add('active');
        document.getElementById('directMethod').classList.add('active');
    } else if (method === 'generate') {
        tabs[2].classList.add('active');
        document.getElementById('generateMethod').classList.add('active');
    }
}

// Handle generate OTP
function handleGenerateOTP() {
    var flatId = document.getElementById('otp_flat_id').value;
    var advanceAmount = document.getElementById('otp_advance_amount').value;
    
    if (!flatId || !advanceAmount || advanceAmount <= 0) {
        showMessage('Please fill all required fields', 'error');
        return;
    }
    
    var formData = new FormData();
    formData.append('action', 'generate_otp');
    formData.append('flat_id', flatId);
    formData.append('advance_amount', advanceAmount);
    
    var xhr = new XMLHttpRequest();
    xhr.open('POST', '../controller/tenant_controller.php', true);
    
    xhr.onreadystatechange = function() {
        if (xhr.readyState === 4 && xhr.status === 200) {
            try {
                var response = JSON.parse(xhr.responseText);
                
                if (response.success) {
                    document.getElementById('otpForm').style.display = 'none';
                    document.getElementById('generatedOTP').textContent = response.otp_code;
                    document.getElementById('otpExpiresAt').textContent = formatDateTime(response.expires_at);
                    document.getElementById('otpDisplay').style.display = 'block';
                    
                    showMessage(response.message, 'success');
                    loadPendingAssignments();
                } else {
                    showMessage(response.message, 'error');
                }
            } catch (e) {
                console.error('Error:', e);
                showMessage('Failed to generate OTP', 'error');
            }
        }
    };
    
    xhr.send(formData);
}

// Copy OTP
function copyOTP() {
    var otpText = document.getElementById('generatedOTP').textContent;
    copyToClipboard(otpText);
    showMessage('OTP copied to clipboard', 'success');
}

// Handle direct assignment
function handleDirectAssignment() {
    var flatId = document.getElementById('direct_flat_id').value;
    var tenantId = document.getElementById('direct_tenant_id').value;
    var advanceAmount = document.getElementById('direct_advance_amount').value;
    
    if (!flatId || !tenantId || !advanceAmount || advanceAmount <= 0) {
        showMessage('Please fill all required fields', 'error');
        return;
    }
    
    var formData = new FormData();
    formData.append('action', 'assign_tenant_direct');
    formData.append('flat_id', flatId);
    formData.append('tenant_id', tenantId);
    formData.append('advance_amount', advanceAmount);
    
    var xhr = new XMLHttpRequest();
    xhr.open('POST', '../controller/tenant_controller.php', true);
    
    xhr.onreadystatechange = function() {
        if (xhr.readyState === 4 && xhr.status === 200) {
            try {
                var response = JSON.parse(xhr.responseText);
                
                if (response.success) {
                    showMessage(response.message, 'success');
                    closeAddTenantModal();
                    loadPendingAssignments();
                } else {
                    showMessage(response.message, 'error');
                }
            } catch (e) {
                console.error('Error:', e);
                showMessage('Failed to assign tenant', 'error');
            }
        }
    };
    
    xhr.send(formData);
}

// Search tenants for assignment
function searchTenantsForAssign() {
    var searchTerm = document.getElementById('tenantSearch').value;
    
    if (searchTerm.length < 2) {
        document.getElementById('tenantSearchResults').style.display = 'none';
        return;
    }
    
    var formData = new FormData();
    formData.append('action', 'search_tenants');
    formData.append('search_term', searchTerm);
    
    var xhr = new XMLHttpRequest();
    xhr.open('POST', '../controller/tenant_controller.php', true);
    
    xhr.onreadystatechange = function() {
        if (xhr.readyState === 4 && xhr.status === 200) {
            try {
                var response = JSON.parse(xhr.responseText);
                
                if (response.success) {
                    displayTenantSearchResults(response.tenants);
                }
            } catch (e) {
                console.error('Error:', e);
            }
        }
    };
    
    xhr.send(formData);
}

// Display tenant search results
function displayTenantSearchResults(tenants) {
    var container = document.getElementById('tenantSearchResults');
    
    if (!tenants || tenants.length === 0) {
        container.innerHTML = '<div class="search-result-item">No tenants found</div>';
        container.style.display = 'block';
        return;
    }
    
    var html = '';
    for (var i = 0; i < tenants.length; i++) {
        var tenant = tenants[i];
        html += '<div class="search-result-item" onclick="selectTenant(' + tenant.user_id + ', \'' + 
                escapeHtml(tenant.full_name) + '\')">' +
                '<strong>' + escapeHtml(tenant.full_name) + '</strong><br>' +
                '<small>' + escapeHtml(tenant.email) + '</small>' +
                '</div>';
    }
    
    container.innerHTML = html;
    container.style.display = 'block';
}

// Select tenant
function selectTenant(userId, fullName) {
    document.getElementById('direct_tenant_id').value = userId;
    document.getElementById('tenantSearch').value = fullName;
    document.getElementById('tenantSearchResults').style.display = 'none';
}

// Handle generate credentials
function handleGenerateCredentials() {
    var flatId = document.getElementById('generate_flat_id').value;
    var advanceAmount = document.getElementById('generate_advance_amount').value;
    
    if (!flatId || !advanceAmount || advanceAmount <= 0) {
        showMessage('Please fill all required fields', 'error');
        return;
    }
    
    var formData = new FormData();
    formData.append('action', 'generate_credentials');
    formData.append('flat_id', flatId);
    formData.append('advance_amount', advanceAmount);
    
    var xhr = new XMLHttpRequest();
    xhr.open('POST', '../controller/tenant_controller.php', true);
    
    xhr.onreadystatechange = function() {
        if (xhr.readyState === 4 && xhr.status === 200) {
            try {
                var response = JSON.parse(xhr.responseText);
                
                if (response.success) {
                    document.getElementById('generateForm').style.display = 'none';
                    document.getElementById('generatedUsername').textContent = response.username;
                    document.getElementById('generatedPassword').textContent = response.password;
                    document.getElementById('credentialsDisplay').style.display = 'block';
                    
                    showMessage(response.message, 'success');
                    loadPendingAssignments();
                } else {
                    showMessage(response.message, 'error');
                }
            } catch (e) {
                console.error('Error:', e);
                showMessage('Failed to generate credentials', 'error');
            }
        }
    };
    
    xhr.send(formData);
}

// Copy username
function copyUsername() {
    var username = document.getElementById('generatedUsername').textContent;
    copyToClipboard(username);
    showMessage('Username copied', 'success');
}

// Copy password
function copyPassword() {
    var password = document.getElementById('generatedPassword').textContent;
    copyToClipboard(password);
    showMessage('Password copied', 'success');
}

// View tenant details
function viewTenantDetails(tenantId) {
    currentTenantId = tenantId;
    
    var formData = new FormData();
    formData.append('action', 'get_tenant_details');
    formData.append('tenant_id', tenantId);
    
    var xhr = new XMLHttpRequest();
    xhr.open('POST', '../controller/tenant_controller.php', true);
    
    xhr.onreadystatechange = function() {
        if (xhr.readyState === 4 && xhr.status === 200) {
            try {
                var response = JSON.parse(xhr.responseText);
                
                if (response.success) {
                    displayTenantDetails(response.tenant);
                    document.getElementById('tenantDetailsModal').style.display = 'flex';
                } else {
                    showMessage(response.message, 'error');
                }
            } catch (e) {
                console.error('Error:', e);
                showMessage('Failed to load tenant details', 'error');
            }
        }
    };
    
    xhr.send(formData);
}

// Display tenant details
function displayTenantDetails(tenant) {
    document.getElementById('tenantFullName').textContent = tenant.full_name;
    document.getElementById('tenantEmail').textContent = tenant.email;
    document.getElementById('tenantContact').textContent = tenant.contact_number || 'No contact';
    
    var photoLarge = document.getElementById('tenantPhotoLarge');
    if (tenant.profile_picture_url) {
        photoLarge.innerHTML = '<img src="' + escapeHtml(tenant.profile_picture_url) + '" alt="Photo">';
    } else {
        document.getElementById('tenantInitial').textContent = tenant.full_name.charAt(0).toUpperCase();
    }
    
    var flatsContainer = document.getElementById('assignedFlatsContainer');
    var flatsHtml = '';
    
    var totalAdvance = 0;
    
    for (var i = 0; i < tenant.assigned_flats.length; i++) {
        var flat = tenant.assigned_flats[i];
        totalAdvance += parseFloat(flat.advance_balance);
        
        var noticeHtml = '';
        if (flat.end_notice_sent_at) {
            noticeHtml = '<div class="notice-warning">' +
                '<p>End Notice Sent - ' + (flat.notice_hours_remaining > 0 ? 
                    flat.notice_hours_remaining + ' hours remaining' : 'Expired') + '</p>' +
                '</div>';
        }
        
        flatsHtml += '<div class="flat-assignment-card">' +
            '<div class="flat-assignment-header">' +
                '<div class="flat-assignment-title">' + 
                    escapeHtml(flat.building_name) + ' - ' + escapeHtml(flat.flat_number) +
                '</div>' +
            '</div>' +
            '<div class="flat-assignment-details">' +
                '<div class="detail-row">' +
                    '<span class="label">Floor:</span>' +
                    '<span class="value">' + flat.floor_number + '</span>' +
                '</div>' +
                '<div class="detail-row">' +
                    '<span class="label">Rent:</span>' +
                    '<span class="value">৳' + formatNumber(flat.base_rent) + '</span>' +
                '</div>' +
                '<div class="detail-row">' +
                    '<span class="label">Advance Balance:</span>' +
                    '<span class="value">৳' + formatNumber(flat.advance_balance) + '</span>' +
                '</div>' +
                '<div class="detail-row">' +
                    '<span class="label">Move-in Date:</span>' +
                    '<span class="value">' + formatDate(flat.confirmed_at) + '</span>' +
                '</div>' +
            '</div>' +
            noticeHtml +
            '<div class="flat-actions">' +
                (flat.end_notice_sent_at ? 
                    '<button class="btn-small btn-secondary" onclick="cancelEndNotice(' + flat.assignment_id + ')">Cancel Notice</button>' :
                    '<button class="btn-small btn-warning" onclick="showEndTenancyModal(' + flat.assignment_id + ')">End Tenancy</button>') +
                '<button class="btn-small btn-primary" onclick="showMoveTenantModal(' + flat.assignment_id + ')">Move to Different Flat</button>' +
            '</div>' +
        '</div>';
    }
    
    flatsContainer.innerHTML = flatsHtml;
    
    document.getElementById('totalAdvance').textContent = '৳' + formatNumber(totalAdvance);
    document.getElementById('totalOutstanding').textContent = '৳' + formatNumber(tenant.total_outstanding || 0);
}

// Close tenant details modal
function closeTenantDetailsModal() {
    document.getElementById('tenantDetailsModal').style.display = 'none';
    currentTenantId = null;
}

// Show move tenant modal
function showMoveTenantModal(assignmentId) {
    currentAssignmentId = assignmentId;
    
    var tenant = findCurrentTenantData();
    if (!tenant) return;
    
    var assignment = null;
    for (var i = 0; i < tenant.assigned_flats.length; i++) {
        if (tenant.assigned_flats[i].assignment_id == assignmentId) {
            assignment = tenant.assigned_flats[i];
            break;
        }
    }
    
    if (!assignment) return;
    
    document.getElementById('move_assignment_id').value = assignmentId;
    document.getElementById('currentFlatDetails').textContent = 
        assignment.building_name + ' - ' + assignment.flat_number + ' (Floor ' + assignment.floor_number + ')';
    document.getElementById('currentAdvanceBalance').textContent = '৳' + formatNumber(assignment.advance_balance);
    document.getElementById('transfer_advance').value = assignment.advance_balance;
    document.getElementById('transfer_advance').max = assignment.advance_balance;
    
    calculateNewAdvance();
    
    document.getElementById('moveTenantModal').style.display = 'flex';
}

// Close move tenant modal
function closeMoveTenantModal() {
    document.getElementById('moveTenantModal').style.display = 'none';
    document.getElementById('moveTenantForm').reset();
    currentAssignmentId = null;
}

// Calculate new advance
function calculateNewAdvance() {
    var transfer = parseFloat(document.getElementById('transfer_advance').value) || 0;
    var additional = parseFloat(document.getElementById('additional_advance').value) || 0;
    var newAdvance = transfer + additional;
    
    document.getElementById('newAdvanceBalance').textContent = '৳' + formatNumber(newAdvance);
}

// Handle move tenant
function handleMoveTenant() {
    var assignmentId = document.getElementById('move_assignment_id').value;
    var newFlatId = document.getElementById('new_flat_id').value;
    var transferAdvance = document.getElementById('transfer_advance').value;
    var additionalAdvance = document.getElementById('additional_advance').value;
    
    if (!newFlatId) {
        showMessage('Please select a new flat', 'error');
        return;
    }
    
    var formData = new FormData();
    formData.append('action', 'move_tenant');
    formData.append('assignment_id', assignmentId);
    formData.append('new_flat_id', newFlatId);
    formData.append('transfer_advance', transferAdvance || 0);
    formData.append('additional_advance', additionalAdvance || 0);
    
    var xhr = new XMLHttpRequest();
    xhr.open('POST', '../controller/tenant_controller.php', true);
    
    xhr.onreadystatechange = function() {
        if (xhr.readyState === 4 && xhr.status === 200) {
            try {
                var response = JSON.parse(xhr.responseText);
                
                if (response.success) {
                    showMessage(response.message, 'success');
                    closeMoveTenantModal();
                    closeTenantDetailsModal();
                    loadAllData();
                } else {
                    showMessage(response.message, 'error');
                }
            } catch (e) {
                console.error('Error:', e);
                showMessage('Failed to move tenant', 'error');
            }
        }
    };
    
    xhr.send(formData);
}

// Show end tenancy modal
function showEndTenancyModal(assignmentId) {
    currentAssignmentId = assignmentId;
    
    var tenant = findCurrentTenantData();
    if (!tenant) return;
    
    var assignment = null;
    for (var i = 0; i < tenant.assigned_flats.length; i++) {
        if (tenant.assigned_flats[i].assignment_id == assignmentId) {
            assignment = tenant.assigned_flats[i];
            break;
        }
    }
    
    if (!assignment) return;
    
    document.getElementById('end_assignment_id').value = assignmentId;
    document.getElementById('endTenancyDetails').innerHTML = 
        '<strong>Tenant:</strong> ' + escapeHtml(tenant.full_name) + '<br>' +
        '<strong>Flat:</strong> ' + escapeHtml(assignment.building_name) + ' - ' + escapeHtml(assignment.flat_number) + '<br>' +
        '<strong>Advance Balance:</strong> ৳' + formatNumber(assignment.advance_balance);
    
    document.getElementById('endTenancyModal').style.display = 'flex';
}

// Close end tenancy modal
function closeEndTenancyModal() {
    document.getElementById('endTenancyModal').style.display = 'none';
    document.getElementById('endTenancyForm').reset();
    currentAssignmentId = null;
}

// Handle send end notice
function handleSendEndNotice() {
    var assignmentId = document.getElementById('end_assignment_id').value;
    
    var formData = new FormData();
    formData.append('action', 'send_end_notice');
    formData.append('assignment_id', assignmentId);
    
    var xhr = new XMLHttpRequest();
    xhr.open('POST', '../controller/tenant_controller.php', true);
    
    xhr.onreadystatechange = function() {
        if (xhr.readyState === 4 && xhr.status === 200) {
            try {
                var response = JSON.parse(xhr.responseText);
                
                if (response.success) {
                    showMessage(response.message, 'success');
                    closeEndTenancyModal();
                    closeTenantDetailsModal();
                    loadAllData();
                } else {
                    showMessage(response.message, 'error');
                }
            } catch (e) {
                console.error('Error:', e);
                showMessage('Failed to send end notice', 'error');
            }
        }
    };
    
    xhr.send(formData);
}

// Cancel end notice
function cancelEndNotice(assignmentId) {
    if (!confirm('Are you sure you want to cancel the end notice?')) {
        return;
    }
    
    var formData = new FormData();
    formData.append('action', 'cancel_end_notice');
    formData.append('assignment_id', assignmentId);
    
    var xhr = new XMLHttpRequest();
    xhr.open('POST', '../controller/tenant_controller.php', true);
    
    xhr.onreadystatechange = function() {
        if (xhr.readyState === 4 && xhr.status === 200) {
            try {
                var response = JSON.parse(xhr.responseText);
                
                if (response.success) {
                    showMessage(response.message, 'success');
                    closeTenantDetailsModal();
                    loadAllData();
                } else {
                    showMessage(response.message, 'error');
                }
            } catch (e) {
                console.error('Error:', e);showMessage('Failed to cancel notice', 'error');
            }
        }
    };
    
    xhr.send(formData);
}

// Cancel assignment
function cancelAssignment(assignmentId) {
    if (!confirm('Are you sure you want to cancel this assignment?')) {
        return;
    }
    
    showMessage('Cancel assignment feature coming soon', 'info');
}

// Send payment reminder
function sendPaymentReminder(tenantId) {
    showMessage('Payment reminder feature coming soon', 'info');
}

// Send message
function sendMessage(tenantId) {
    showMessage('Messaging feature coming soon', 'info');
}

// Edit tenant profile
function editTenantProfile() {
    showMessage('Edit profile feature coming soon', 'info');
}

// Send message to tenant
function sendMessageToTenant() {
    showMessage('Messaging feature coming soon', 'info');
}

// Generate tenant slip
function generateTenantSlip() {
    showMessage('Slip generation feature coming soon', 'info');
}

// Utility functions
function findCurrentTenantData() {
    if (!currentTenantId) return null;
    
    for (var i = 0; i < allTenants.length; i++) {
        if (allTenants[i].user_id == currentTenantId) {
            return allTenants[i];
        }
    }
    return null;
}

function copyToClipboard(text) {
    var tempInput = document.createElement('input');
    tempInput.value = text;
    document.body.appendChild(tempInput);
    tempInput.select();
    document.execCommand('copy');
    document.body.removeChild(tempInput);
}

function formatNumber(num) {
    if (num === undefined || num === null) return '0';
    return parseFloat(num).toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ",");
}

function formatDate(dateString) {
    if (!dateString) return 'N/A';
    var date = new Date(dateString);
    var options = { year: 'numeric', month: 'short', day: 'numeric' };
    return date.toLocaleDateString('en-US', options);
}

function formatDateTime(dateString) {
    if (!dateString) return 'N/A';
    var date = new Date(dateString);
    var options = { 
        year: 'numeric', 
        month: 'short', 
        day: 'numeric', 
        hour: '2-digit', 
        minute: '2-digit' 
    };
    return date.toLocaleDateString('en-US', options);
}

function escapeHtml(text) {
    if (!text) return '';
    var div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

function showMessage(message, type) {
    var container = document.getElementById('messageContainer');
    if (!container) {
        container = document.createElement('div');
        container.id = 'messageContainer';
        container.style.position = 'fixed';
        container.style.top = '100px';
        container.style.right = '20px';
        container.style.zIndex = '10000';
        document.body.appendChild(container);
    }
    
    var messageDiv = document.createElement('div');
    messageDiv.textContent = message;
    messageDiv.style.padding = '1rem 1.5rem';
    messageDiv.style.marginBottom = '10px';
    messageDiv.style.borderRadius = '8px';
    messageDiv.style.boxShadow = '0 4px 12px rgba(0,0,0,0.15)';
    messageDiv.style.maxWidth = '400px';
    messageDiv.style.fontWeight = '500';
    
    if (type === 'success') {
        messageDiv.style.background = '#d4edda';
        messageDiv.style.color = '#155724';
        messageDiv.style.border = '1px solid #c3e6cb';
    } else if (type === 'error') {
        messageDiv.style.background = '#f8d7da';
        messageDiv.style.color = '#721c24';
        messageDiv.style.border = '1px solid #f5c6cb';
    } else if (type === 'warning') {
        messageDiv.style.background = '#fff3cd';
        messageDiv.style.color = '#856404';
        messageDiv.style.border = '1px solid #ffeeba';
    } else {
        messageDiv.style.background = '#d1ecf1';
        messageDiv.style.color = '#0c5460';
        messageDiv.style.border = '1px solid #bee5eb';
    }
    
    container.appendChild(messageDiv);
    
    setTimeout(function() {
        if (messageDiv.parentNode) {
            messageDiv.style.opacity = '0';
            messageDiv.style.transition = 'opacity 0.5s';
            setTimeout(function() {
                if (messageDiv.parentNode) {
                    container.removeChild(messageDiv);
                }
            }, 500);
        }
    }, 5000);
}

function showError(message) {
    showMessage(message, 'error');
}