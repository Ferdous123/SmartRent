// Buildings Management Page JavaScript - W3Schools Style
// Global variables
var currentBuildingId = null;
var currentFlatId = null;
var allBuildings = [];
var confirmCallback = null;

// Initialize page when loaded
document.addEventListener('DOMContentLoaded', function() {
    loadBuildings();
    setupEventListeners();
});

// Setup event listeners
function setupEventListeners() {
    // Edit Building Form
    var editBuildingForm = document.getElementById('editBuildingForm');
    if (editBuildingForm) {
        editBuildingForm.addEventListener('submit', handleEditBuilding);
    }
    
    // Add Flat Form
    var addFlatForm = document.getElementById('addFlatForm');
    if (addFlatForm) {
        addFlatForm.addEventListener('submit', handleAddFlat);
    }
    
    // Edit Flat Form
    var editFlatForm = document.getElementById('editFlatForm');
    if (editFlatForm) {
        editFlatForm.addEventListener('submit', handleEditFlat);
    }
    
    // Assign Manager Form
    var assignManagerForm = document.getElementById('assignManagerForm');
    if (assignManagerForm) {
        assignManagerForm.addEventListener('submit', handleAssignManager);
    }
}

// Load all buildings
function loadBuildings() {
    var xhr = new XMLHttpRequest();
    xhr.open('POST', '../controller/building_controller.php', true);
    xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
    
    xhr.onreadystatechange = function() {
        if (xhr.readyState === 4 && xhr.status === 200) {
            try {
                var response = JSON.parse(xhr.responseText);
                if (response.success) {
                    allBuildings = response.buildings;
                    displayBuildings(response.buildings);
                } else {
                    showError('Failed to load buildings');
                }
            } catch (e) {
                showError('Error loading buildings');
            }
        }
    };
    
    xhr.send('action=get_buildings');
}

// Display buildings
function displayBuildings(buildings) {
    var container = document.getElementById('buildingsContainer');
    
    if (!buildings || buildings.length === 0) {
        container.innerHTML = '<div class="empty-state">' +
            '<div class="empty-state-icon">🏢</div>' +
            '<h4>No Buildings Found</h4>' +
            '<p>Start by adding your first building</p>' +
            '<button class="btn-primary" onclick="showAddBuildingModal()">Add Building</button>' +
            '</div>';
        return;
    }
    
    var html = '';
    for (var i = 0; i < buildings.length; i++) {
        html += createBuildingCard(buildings[i]);
    }
    
    container.innerHTML = html;
    
    // Load flats for all buildings after rendering
    for (var i = 0; i < buildings.length; i++) {
        loadFlats(buildings[i].building_id);
    }
}

// Create building card HTML
function createBuildingCard(building) {
    var occupancyRate = building.total_flats > 0 ? 
        Math.round((building.occupied_flats / building.total_flats) * 100) : 0;
    
    return '<div class="building-card" data-building-id="' + building.building_id + '">' +
        '<div class="building-card-header">' +
            '<div class="building-info">' +
                '<h3>' + escapeHtml(building.building_name) + '</h3>' +
                '<p>' + escapeHtml(building.address) + '</p>' +
            '</div>' +
            '<div class="building-stats-summary">' +
                '<div class="stat-item">' +
                    '<div class="stat-value">' + building.total_floors + '</div>' +
                    '<div class="stat-label">Floors</div>' +
                '</div>' +
                '<div class="stat-item">' +
                    '<div class="stat-value">' + (building.actual_flats || 0) + '</div>' +
                    '<div class="stat-label">Flats</div>' +
                '</div>' +
                '<div class="stat-item">' +
                    '<div class="stat-value">' + occupancyRate + '%</div>' +
                    '<div class="stat-label">Occupied</div>' +
                '</div>' +
            '</div>' +
            '<div class="building-actions">' +
                '<button class="icon-btn" onclick="editBuilding(' + building.building_id + ')" title="Edit">✏️</button>' +
                '<button class="icon-btn" onclick="confirmDeleteBuilding(' + building.building_id + ')" title="Delete">🗑️</button>' +
            '</div>' +
        '</div>' +
        '<div class="building-tabs">' +
            '<button class="tab-btn active" onclick="switchTab(' + building.building_id + ', \'flats\')">Flats</button>' +
            '<button class="tab-btn" onclick="switchTab(' + building.building_id + ', \'managers\')">Managers</button>' +
        '</div>' +
        '<div class="tab-content">' +
            '<div class="tab-pane active" id="flats-' + building.building_id + '">' +
                '<button class="btn-primary" onclick="showAddFlatModal(' + building.building_id + ')">+ Add Flat</button>' +
                '<div class="flats-grid" id="flats-grid-' + building.building_id + '">Loading...</div>' +
            '</div>' +
            '<div class="tab-pane" id="managers-' + building.building_id + '">' +
                '<button class="btn-primary" onclick="showAssignManagerModal(' + building.building_id + ')">+ Assign Manager</button>' +
                '<div class="managers-list" id="managers-list-' + building.building_id + '">Loading...</div>' +
            '</div>' +
        '</div>' +
    '</div>';
}

// Switch tabs
function switchTab(buildingId, tab) {
    // Update tab buttons
    var card = document.querySelector('[data-building-id="' + buildingId + '"]');
    var tabButtons = card.querySelectorAll('.tab-btn');
    var tabPanes = card.querySelectorAll('.tab-pane');
    
    for (var i = 0; i < tabButtons.length; i++) {
        tabButtons[i].classList.remove('active');
    }
    
    for (var i = 0; i < tabPanes.length; i++) {
        tabPanes[i].classList.remove('active');
    }
    
    event.target.classList.add('active');
    document.getElementById(tab + '-' + buildingId).classList.add('active');
    
    // Load data if not loaded
    if (tab === 'flats') {
        loadFlats(buildingId);
    } else if (tab === 'managers') {
        loadManagers(buildingId);
    }
}

// Load flats for a building
function loadFlats(buildingId) {
    var xhr = new XMLHttpRequest();
    xhr.open('POST', '../controller/building_controller.php', true);
    xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
    
    xhr.onreadystatechange = function() {
        if (xhr.readyState === 4 && xhr.status === 200) {
            try {
                var response = JSON.parse(xhr.responseText);
                if (response.success) {
                    displayFlats(buildingId, response.flats);
                }
            } catch (e) {
                console.error('Error loading flats');
            }
        }
    };
    
    xhr.send('action=get_flats&building_id=' + buildingId);
}

// Display flats
function displayFlats(buildingId, flats) {
    var container = document.getElementById('flats-grid-' + buildingId);
    
    if (!flats || flats.length === 0) {
        container.innerHTML = '<div class="empty-state">' +
            '<p>No flats added yet</p>' +
            '</div>';
        return;
    }
    
    var html = '';
    for (var i = 0; i < flats.length; i++) {
        html += createFlatCard(flats[i]);
    }
    
    container.innerHTML = html;
}

// Create flat card HTML
function createFlatCard(flat) {
    var statusClass = flat.assignment_status === 'confirmed' ? 'occupied' : 
                     flat.status === 'maintenance' ? 'maintenance' : '';
    
    var tenantInfo = '';
    if (flat.tenant_name) {
        tenantInfo = '<div class="tenant-info">' +
            '<strong>Tenant:</strong> ' + escapeHtml(flat.tenant_name) +
            '</div>';
    }
    
    return '<div class="flat-card ' + statusClass + '">' +
        '<div class="flat-header">' +
            '<div class="flat-number">' + escapeHtml(flat.flat_number) + '</div>' +
            '<span class="flat-status-badge ' + flat.status + '">' + flat.status + '</span>' +
        '</div>' +
        '<div class="flat-details">' +
            '<div class="flat-detail-row">' +
                '<span>Floor:</span><span>' + flat.floor_number + '</span>' +
            '</div>' +
            '<div class="flat-detail-row">' +
                '<span>Bedrooms:</span><span>' + (flat.bedrooms || 'N/A') + '</span>' +
            '</div>' +
            '<div class="flat-detail-row">' +
                '<span>Rent:</span><span>৳' + formatNumber(flat.base_rent || 0) + '</span>' +
            '</div>' +
        '</div>' +
        tenantInfo +
        '<div class="flat-actions">' +
            '<button class="btn-small btn-edit" onclick="editFlat(' + flat.flat_id + ')">Edit</button>' +
            '<button class="btn-small btn-delete" onclick="confirmDeleteFlat(' + flat.flat_id + ')">Delete</button>' +
        '</div>' +
    '</div>';
}

// Load managers for a building
function loadManagers(buildingId) {
    var xhr = new XMLHttpRequest();
    xhr.open('POST', '../controller/building_controller.php', true);
    xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
    
    xhr.onreadystatechange = function() {
        if (xhr.readyState === 4 && xhr.status === 200) {
            try {
                var response = JSON.parse(xhr.responseText);
                if (response.success) {
                    displayManagers(buildingId, response.managers);
                }
            } catch (e) {
                console.error('Error loading managers');
            }
        }
    };
    
    xhr.send('action=get_managers&building_id=' + buildingId);
}

// Display managers
function displayManagers(buildingId, managers) {
    var container = document.getElementById('managers-list-' + buildingId);
    
    if (!managers || managers.length === 0) {
        container.innerHTML = '<div class="empty-state">' +
            '<p>No managers assigned yet</p>' +
            '</div>';
        return;
    }
    
    var html = '';
    for (var i = 0; i < managers.length; i++) {
        html += createManagerItem(managers[i], buildingId);
    }
    
    container.innerHTML = html;
}

// Create manager item HTML
function createManagerItem(manager, buildingId) {
    var initial = manager.manager_name.charAt(0).toUpperCase();
    
    return '<div class="manager-item">' +
        '<div class="manager-info">' +
            '<div class="manager-avatar">' + initial + '</div>' +
            '<div class="manager-details">' +
                '<h4>' + escapeHtml(manager.manager_name) + '</h4>' +
                '<p>' + escapeHtml(manager.manager_email) + '</p>' +
                '<p>Assigned: ' + formatDate(manager.assigned_date) + '</p>' +
            '</div>' +
        '</div>' +
        '<div class="manager-actions">' +
            '<button onclick="confirmRemoveManager(' + buildingId + ', ' + manager.manager_id + ')">Remove</button>' +
        '</div>' +
    '</div>';
}

// Edit building
function editBuilding(buildingId) {
    var building = findBuildingById(buildingId);
    if (!building) return;
    
    document.getElementById('edit_building_id').value = building.building_id;
    document.getElementById('edit_building_name').value = building.building_name;
    document.getElementById('edit_building_address').value = building.address;
    document.getElementById('edit_total_floors').value = building.total_floors;
    
    showEditBuildingModal();
}

function handleEditBuilding(e) {
    e.preventDefault();
    
    var formData = new FormData(e.target);
    formData.append('action', 'update_building');
    
    var xhr = new XMLHttpRequest();
    xhr.open('POST', '../controller/building_controller.php', true);
    
    xhr.onreadystatechange = function() {
        if (xhr.readyState === 4 && xhr.status === 200) {
            try {
                var response = JSON.parse(xhr.responseText);
                if (response.success) {
                    showMessage(response.message, 'success');
                    closeEditBuildingModal();
                    loadBuildings();
                } else {
                    showMessage(response.message, 'error');
                }
            } catch (e) {
                showMessage('Failed to update building', 'error');
            }
        }
    };
    
    xhr.send(formData);
}

// Delete building
function confirmDeleteBuilding(buildingId) {
    showConfirmDialog(
        'Delete Building',
        'Are you sure you want to delete this building? This action cannot be undone.',
        function() {
            deleteBuilding(buildingId);
        }
    );
}

function deleteBuilding(buildingId) {
    var xhr = new XMLHttpRequest();
    xhr.open('POST', '../controller/building_controller.php', true);
    xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
    
    xhr.onreadystatechange = function() {
        if (xhr.readyState === 4 && xhr.status === 200) {
            try {
                var response = JSON.parse(xhr.responseText);
                showMessage(response.message, response.success ? 'success' : 'error');
                if (response.success) {
                    loadBuildings();
                }
            } catch (e) {
                showMessage('Failed to delete building', 'error');
            }
        }
    };
    
    xhr.send('action=delete_building&building_id=' + buildingId);
}

// Add flat
function showAddFlatModal(buildingId) {
    currentBuildingId = buildingId;
    document.getElementById('flat_building_id').value = buildingId;
    document.getElementById('addFlatModal').style.display = 'flex';
}

function closeAddFlatModal() {
    document.getElementById('addFlatModal').style.display = 'none';
    document.getElementById('addFlatForm').reset();
}

function handleAddFlat(e) {
    e.preventDefault();
    
    var formData = new FormData(e.target);
    formData.append('action', 'add_flat');
    
    var xhr = new XMLHttpRequest();
    xhr.open('POST', '../controller/building_controller.php', true);
    
    xhr.onreadystatechange = function() {
        if (xhr.readyState === 4 && xhr.status === 200) {
            try {
                var response = JSON.parse(xhr.responseText);
                if (response.success) {
                    showMessage(response.message, 'success');
                    closeAddFlatModal();
                    loadFlats(currentBuildingId);
                } else {
                    showMessage(response.message, 'error');
                }
            } catch (e) {
                showMessage('Failed to add flat', 'error');
            }
        }
    };
    
    xhr.send(formData);
}

// Edit flat
function editFlat(flatId) {
    currentFlatId = flatId;
    
    var xhr = new XMLHttpRequest();
    xhr.open('POST', '../controller/building_controller.php', true);
    xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
    
    xhr.onreadystatechange = function() {
        if (xhr.readyState === 4 && xhr.status === 200) {
            try {
                var response = JSON.parse(xhr.responseText);
                if (response.success && response.flat) {
                    var flat = response.flat;
                    document.getElementById('edit_flat_id').value = flat.flat_id;
                    document.getElementById('edit_flat_number').value = flat.flat_number;
                    document.getElementById('edit_floor_number').value = flat.floor_number;
                    document.getElementById('edit_bedrooms').value = flat.bedrooms || '';
                    document.getElementById('edit_bathrooms').value = flat.bathrooms || '';
                    document.getElementById('edit_base_rent').value = flat.base_rent || 0;
                    document.getElementById('edit_flat_status').value = flat.status;
                    
                    showEditFlatModal();
                }
            } catch (e) {
                showMessage('Failed to load flat details', 'error');
            }
        }
    };
    
    xhr.send('action=get_flat&flat_id=' + flatId);
}

function showEditFlatModal() {
    document.getElementById('editFlatModal').style.display = 'flex';
}

function closeEditFlatModal() {
    document.getElementById('editFlatModal').style.display = 'none';
    document.getElementById('editFlatForm').reset();
}

function handleEditFlat(e) {
    e.preventDefault();
    
    var formData = new FormData(e.target);
    formData.append('action', 'update_flat');
    
    var xhr = new XMLHttpRequest();
    xhr.open('POST', '../controller/building_controller.php', true);
    
    xhr.onreadystatechange = function() {
        if (xhr.readyState === 4 && xhr.status === 200) {
            try {
                var response = JSON.parse(xhr.responseText);
                if (response.success) {
                    showMessage(response.message, 'success');
                    closeEditFlatModal();
                    loadBuildings();
                } else {
                    showMessage(response.message, 'error');
                }
            } catch (e) {
                showMessage('Failed to update flat', 'error');
            }
        }
    };
    
    xhr.send(formData);
}

// Delete flat
function confirmDeleteFlat(flatId) {
    showConfirmDialog(
        'Delete Flat',
        'Are you sure you want to delete this flat?',
        function() {
            deleteFlat(flatId);
        }
    );
}

function deleteFlat(flatId) {
    var xhr = new XMLHttpRequest();
    xhr.open('POST', '../controller/building_controller.php', true);
    xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
    
    xhr.onreadystatechange = function() {
        if (xhr.readyState === 4 && xhr.status === 200) {
            try {
                var response = JSON.parse(xhr.responseText);
                showMessage(response.message, response.success ? 'success' : 'error');
                if (response.success) {
                    loadBuildings();
                }
            } catch (e) {
                showMessage('Failed to delete flat', 'error');
            }
        }
    };
    
    xhr.send('action=delete_flat&flat_id=' + flatId);
}

// Assign manager
function showAssignManagerModal(buildingId) {
    currentBuildingId = buildingId;
    document.getElementById('manager_building_id').value = buildingId;
    
    loadAvailableManagers();
    document.getElementById('assignManagerModal').style.display = 'flex';
}

function closeAssignManagerModal() {
    document.getElementById('assignManagerModal').style.display = 'none';
    document.getElementById('assignManagerForm').reset();
}

function loadAvailableManagers() {
    var xhr = new XMLHttpRequest();
    xhr.open('POST', '../controller/building_controller.php', true);
    xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
    
    xhr.onreadystatechange = function() {
        if (xhr.readyState === 4 && xhr.status === 200) {
            try {
                var response = JSON.parse(xhr.responseText);
                if (response.success) {
                    populateManagerSelect(response.managers);
                }
            } catch (e) {
                console.error('Failed to load managers');
            }
        }
    };
    
    xhr.send('action=get_available_managers');
}

function populateManagerSelect(managers) {
    var select = document.getElementById('manager_id');
    select.innerHTML = '<option value="">-- Select Manager --</option>';
    
    for (var i = 0; i < managers.length; i++) {
        var option = document.createElement('option');
        option.value = managers[i].user_id;
        option.textContent = managers[i].full_name + ' (' + managers[i].email + ')';
        select.appendChild(option);
    }
}

function handleAssignManager(e) {
    e.preventDefault();
    
    var formData = new FormData(e.target);
    formData.append('action', 'assign_manager');
    
    var xhr = new XMLHttpRequest();
    xhr.open('POST', '../controller/building_controller.php', true);
    
    xhr.onreadystatechange = function() {
        if (xhr.readyState === 4 && xhr.status === 200) {
            try {
                var response = JSON.parse(xhr.responseText);
                if (response.success) {
                    showMessage(response.message, 'success');
                    closeAssignManagerModal();
                    loadManagers(currentBuildingId);
                } else {
                    showMessage(response.message, 'error');
                }
            } catch (e) {
                showMessage('Failed to assign manager', 'error');
            }
        }
    };
    
    xhr.send(formData);
}

// Remove manager
function confirmRemoveManager(buildingId, managerId) {
    showConfirmDialog(
        'Remove Manager',
        'Are you sure you want to remove this manager from the building?',
        function() {
            removeManager(buildingId, managerId);
        }
    );
}

function removeManager(buildingId, managerId) {
    var xhr = new XMLHttpRequest();
    xhr.open('POST', '../controller/building_controller.php', true);
    xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
    
    xhr.onreadystatechange = function() {
        if (xhr.readyState === 4 && xhr.status === 200) {
            try {
                var response = JSON.parse(xhr.responseText);
                showMessage(response.message, response.success ? 'success' : 'error');
                if (response.success) {
                    loadManagers(buildingId);
                }
            } catch (e) {
                showMessage('Failed to remove manager', 'error');
            }
        }
    };
    
    xhr.send('action=remove_manager&building_id=' + buildingId + '&manager_id=' + managerId);
}

// Modal functions
function showEditBuildingModal() {
    document.getElementById('editBuildingModal').style.display = 'flex';
}

function closeEditBuildingModal() {
    document.getElementById('editBuildingModal').style.display = 'none';
    document.getElementById('editBuildingForm').reset();
}

// Confirmation dialog
function showConfirmDialog(title, message, callback) {
    document.getElementById('confirmTitle').textContent = title;
    document.getElementById('confirmMessage').textContent = message;
    
    confirmCallback = callback;
    
    var confirmBtn = document.getElementById('confirmBtn');
    confirmBtn.onclick = function() {
        if (confirmCallback) {
            confirmCallback();
        }
        closeConfirmDialog();
    };
    
    document.getElementById('confirmDialog').style.display = 'flex';
}

function closeConfirmDialog() {
    document.getElementById('confirmDialog').style.display = 'none';
    confirmCallback = null;
}

// Utility functions
function findBuildingById(buildingId) {
    for (var i = 0; i < allBuildings.length; i++) {
        if (allBuildings[i].building_id == buildingId) {
            return allBuildings[i];
        }
    }
    return null;
}

function formatNumber(num) {
    if (num === undefined || num === null) return '0';
    return num.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ",");
}

function formatDate(dateString) {
    if (!dateString) return 'N/A';
    var date = new Date(dateString);
    return date.toLocaleDateString();
}

function escapeHtml(text) {
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
        container.style.top = '20px';
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
    
    if (type === 'success') {
        messageDiv.style.background = '#d4edda';
        messageDiv.style.color = '#155724';
        messageDiv.style.border = '1px solid #c3e6cb';
    } else if (type === 'error') {
        messageDiv.style.background = '#f8d7da';
        messageDiv.style.color = '#721c24';
        messageDiv.style.border = '1px solid #f5c6cb';
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