// Building Management JavaScript - W3Schools Style
// Global variables for building creation
var currentBuildingStep = 1;
var buildingFormData = {
    building_name: '',
    address: '',
    total_floors: 0,
    flats_per_floor: 0,
    naming_scheme: 'floor_letter',
    letter_start: 'A',
    custom_prefix: '',
    numbering_style: 'sequential'
};
var generatedFlats = [];
var excludedFlats = []; // Track excluded flats

// Open Add Building Modal
function showAddBuildingModal() {
    var modal = document.getElementById('addBuildingModal');
    if (modal) {
        modal.style.display = 'flex';
        currentBuildingStep = 1;
        showBuildingStep(1);
        resetBuildingForm();
    }
}

// Close Add Building Modal
function closeAddBuildingModal() {
    var modal = document.getElementById('addBuildingModal');
    if (modal) {
        modal.style.display = 'none';
    }
    resetBuildingForm();
}

// Reset building form
function resetBuildingForm() {
    var basicForm = document.getElementById('buildingBasicForm');
    var namingForm = document.getElementById('buildingNamingForm');
    
    if (basicForm) {
        basicForm.reset();
    }
    if (namingForm) {
        namingForm.reset();
    }
    
    // Clear all errors
    var errorSpans = document.querySelectorAll('.form-error');
    for (var i = 0; i < errorSpans.length; i++) {
        errorSpans[i].textContent = '';
    }
    
    // Reset data
    buildingFormData = {
        building_name: '',
        address: '',
        total_floors: 0,
        flats_per_floor: 0,
        naming_scheme: 'floor_letter',
        letter_start: 'A',
        custom_prefix: '',
        numbering_style: 'sequential'
    };
    generatedFlats = [];
    excludedFlats = [];
}

// Show specific step
function showBuildingStep(step) {
    // Hide all steps
    for (var i = 1; i <= 3; i++) {
        var stepElement = document.getElementById('buildingStep' + i);
        if (stepElement) {
            stepElement.style.display = 'none';
        }
    }
    
    // Show current step
    var currentStepElement = document.getElementById('buildingStep' + step);
    if (currentStepElement) {
        currentStepElement.style.display = 'block';
    }
    
    currentBuildingStep = step;
}

// Navigate to next step
function nextBuildingStep(step) {
    if (step === 2) {
        // Validate step 1
        if (validateBuildingBasicInfo()) {
            saveBuildingBasicInfo();
            showBuildingStep(2);
        }
    }
}

// Navigate to previous step
function previousBuildingStep(step) {
    showBuildingStep(step);
}

// Validate basic information
function validateBuildingBasicInfo() {
    var isValid = true;
    
    // Clear previous errors
    document.getElementById('building_name_error').textContent = '';
    document.getElementById('address_error').textContent = '';
    document.getElementById('total_floors_error').textContent = '';
    document.getElementById('flats_per_floor_error').textContent = '';
    
    // Building name
    var buildingName = document.getElementById('building_name').value.trim();
    if (buildingName === '') {
        document.getElementById('building_name_error').textContent = 'Building name is required';
        isValid = false;
    }
    
    // Address
    var address = document.getElementById('building_address').value.trim();
    if (address === '') {
        document.getElementById('address_error').textContent = 'Address is required';
        isValid = false;
    }
    
    // Total floors
    var totalFloors = parseInt(document.getElementById('total_floors').value);
    if (isNaN(totalFloors) || totalFloors < 1 || totalFloors > 50) {
        document.getElementById('total_floors_error').textContent = 'Please enter floors between 1 and 50';
        isValid = false;
    }
    
    // Flats per floor
    var flatsPerFloor = parseInt(document.getElementById('flats_per_floor').value);
    if (isNaN(flatsPerFloor) || flatsPerFloor < 1 || flatsPerFloor > 20) {
        document.getElementById('flats_per_floor_error').textContent = 'Please enter flats between 1 and 20';
        isValid = false;
    }
    
    return isValid;
}

// Save basic information
function saveBuildingBasicInfo() {
    buildingFormData.building_name = document.getElementById('building_name').value.trim();
    buildingFormData.address = document.getElementById('building_address').value.trim();
    buildingFormData.total_floors = parseInt(document.getElementById('total_floors').value);
    buildingFormData.flats_per_floor = parseInt(document.getElementById('flats_per_floor').value);
}

// Toggle naming options based on scheme
function toggleNamingOptions() {
    var selectedScheme = document.querySelector('input[name="naming_scheme"]:checked').value;
    
    var floorLetterOptions = document.getElementById('floorLetterOptions');
    var customNamingOptions = document.getElementById('customNamingOptions');
    
    if (selectedScheme === 'floor_letter') {
        floorLetterOptions.style.display = 'block';
        customNamingOptions.style.display = 'none';
    } else {
        floorLetterOptions.style.display = 'none';
        customNamingOptions.style.display = 'block';
    }
    
    buildingFormData.naming_scheme = selectedScheme;
}

// Generate flat preview
function generateFlatPreview() {
    // Save naming preferences
    buildingFormData.naming_scheme = document.querySelector('input[name="naming_scheme"]:checked').value;
    
    if (buildingFormData.naming_scheme === 'floor_letter') {
        buildingFormData.letter_start = document.getElementById('letter_start').value;
    } else {
        buildingFormData.custom_prefix = document.getElementById('custom_prefix').value.trim();
        buildingFormData.numbering_style = document.getElementById('numbering_style').value;
    }
    
    // Generate flats array
    generatedFlats = [];
    var flatCounter = 1;
    
    for (var floor = 1; floor <= buildingFormData.total_floors; floor++) {
        for (var flatNum = 1; flatNum <= buildingFormData.flats_per_floor; flatNum++) {
            var flatNumber = '';
            
            if (buildingFormData.naming_scheme === 'floor_letter') {
                // Floor + Letter naming
                var letterIndex = flatNum - 1;
                var startCharCode = buildingFormData.letter_start.charCodeAt(0);
                var letter = String.fromCharCode(startCharCode + letterIndex);
                flatNumber = floor + letter;
            } else {
                // Custom naming
                var prefix = buildingFormData.custom_prefix;
                
                if (buildingFormData.numbering_style === 'floor_based') {
                    // Floor-based: 101, 102, 201, 202
                    var num = (floor * 100) + flatNum;
                    flatNumber = prefix + num;
                } else {
                    // Sequential: 101, 102, 103, 104
                    flatNumber = prefix + (100 + flatCounter);
                    flatCounter++;
                }
            }
            
            generatedFlats.push({
                flat_number: flatNumber,
                floor_number: floor
            });
        }
    }
    
    // Display preview
    displayBuildingPreview();
    showBuildingStep(3);
}

// Toggle flat exclusion
function toggleFlatExclusion(flatNumber) {
    var index = excludedFlats.indexOf(flatNumber);
    
    if (index > -1) {
        // Remove from excluded list
        excludedFlats.splice(index, 1);
    } else {
        // Add to excluded list
        excludedFlats.push(flatNumber);
    }
    
    // Update the visual state
    var flatElement = document.querySelector('[data-flat-number="' + flatNumber + '"]');
    if (flatElement) {
        if (excludedFlats.indexOf(flatNumber) > -1) {
            flatElement.classList.add('excluded');
        } else {
            flatElement.classList.remove('excluded');
        }
    }
    
    // Update the total count
    updateFlatCount();
}

// Update flat count display
function updateFlatCount() {
    var totalFlats = generatedFlats.length;
    var excludedCount = excludedFlats.length;
    var includedCount = totalFlats - excludedCount;
    
    document.getElementById('preview_total_flats').textContent = includedCount + ' (excluded: ' + excludedCount + ')';
}

// Display building preview
function displayBuildingPreview() {
    // Update summary
    document.getElementById('preview_building_name').textContent = buildingFormData.building_name;
    document.getElementById('preview_address').textContent = buildingFormData.address;
    document.getElementById('preview_floors').textContent = buildingFormData.total_floors;
    document.getElementById('preview_total_flats').textContent = generatedFlats.length;
    
    // Display flats grid
    var container = document.getElementById('flatsPreviewContainer');
    container.innerHTML = '';
    
    // Add instruction text
    var instruction = document.createElement('p');
    instruction.className = 'preview-instruction';
    instruction.textContent = 'Click on any flat to exclude it from creation';
    container.appendChild(instruction);
    
    var currentFloor = 0;
    var floorDiv = null;
    
    for (var i = 0; i < generatedFlats.length; i++) {
        var flat = generatedFlats[i];
        
        // Create new floor section if needed
        if (flat.floor_number !== currentFloor) {
            if (floorDiv) {
                container.appendChild(floorDiv);
            }
            
            currentFloor = flat.floor_number;
            
            var floorHeader = document.createElement('div');
            floorHeader.className = 'floor-header';
            floorHeader.textContent = 'Floor ' + currentFloor;
            container.appendChild(floorHeader);
            
            floorDiv = document.createElement('div');
            floorDiv.className = 'floor-flats';
        }
        
        // Create flat item
        var flatItem = document.createElement('div');
        flatItem.className = 'flat-item';
        flatItem.textContent = flat.flat_number;
        flatItem.setAttribute('data-flat-number', flat.flat_number);
        
        // Check if already excluded
        if (excludedFlats.indexOf(flat.flat_number) > -1) {
            flatItem.classList.add('excluded');
        }
        
        // Add click handler
        flatItem.onclick = (function(flatNum) {
            return function() {
                toggleFlatExclusion(flatNum);
            };
        })(flat.flat_number);
        
        floorDiv.appendChild(flatItem);
    }
    
    // Append last floor
    if (floorDiv) {
        container.appendChild(floorDiv);
    }
}

// Submit building to server
function submitBuilding() {
    // Filter out excluded flats
    var flatsToCreate = [];
    for (var i = 0; i < generatedFlats.length; i++) {
        if (excludedFlats.indexOf(generatedFlats[i].flat_number) === -1) {
            flatsToCreate.push(generatedFlats[i]);
        }
    }
    
    // Check if at least one flat is selected
    if (flatsToCreate.length === 0) {
        showMessage('Please select at least one flat to create', 'error');
        return;
    }
    
    // Show loading
    showMessage('Creating building with ' + flatsToCreate.length + ' flats...', 'info');
    
    // Prepare data for server
    var formData = new FormData();
    formData.append('action', 'create_building');
    formData.append('building_name', buildingFormData.building_name);
    formData.append('address', buildingFormData.address);
    formData.append('total_floors', buildingFormData.total_floors);
    formData.append('flats_data', JSON.stringify(flatsToCreate));
    
    // Send AJAX request
    var xhr = new XMLHttpRequest();
    xhr.open('POST', '../controller/building_controller.php', true);
    
    xhr.onreadystatechange = function() {
        if (xhr.readyState === 4) {
            if (xhr.status === 200) {
                try {
                    var response = JSON.parse(xhr.responseText);
                    
                    if (response.success) {
                        showMessage(response.message, 'success');
                        closeAddBuildingModal();
                        
                        // Reload page after 1 second
                        setTimeout(function() {
                            location.reload();
                        }, 1000);
                    } else {
                        showMessage(response.message, 'error');
                    }
                } catch (e) {
                    showMessage('Failed to create building. Please try again.', 'error');
                }
            } else {
                showMessage('Server error. Please try again.', 'error');
            }
        }
    };
    
    xhr.send(formData);
}