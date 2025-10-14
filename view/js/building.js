
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
var excludedFlats = []; 


function showAddBuildingModal() {
    var modal = document.getElementById('addBuildingModal');
    if (modal) {
        modal.style.display = 'flex';
        currentBuildingStep = 1;
        showBuildingStep(1);
        resetBuildingForm();
    }
}


function closeAddBuildingModal() {
    var modal = document.getElementById('addBuildingModal');
    if (modal) {
        modal.style.display = 'none';
    }
    resetBuildingForm();
}


function resetBuildingForm() {
    var basicForm = document.getElementById('buildingBasicForm');
    var namingForm = document.getElementById('buildingNamingForm');
    
    if (basicForm) {
        basicForm.reset();
    }
    if (namingForm) {
        namingForm.reset();
    }
    

    var errorSpans = document.querySelectorAll('.form-error');
    for (var i = 0; i < errorSpans.length; i++) {
        errorSpans[i].textContent = '';
    }
    

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


function showBuildingStep(step) {

    for (var i = 1; i <= 3; i++) {
        var stepElement = document.getElementById('buildingStep' + i);
        if (stepElement) {
            stepElement.style.display = 'none';
        }
    }
    

    var currentStepElement = document.getElementById('buildingStep' + step);
    if (currentStepElement) {
        currentStepElement.style.display = 'block';
    }
    
    currentBuildingStep = step;
}


function nextBuildingStep(step) {
    if (step === 2) {

        if (validateBuildingBasicInfo()) {
            saveBuildingBasicInfo();
            showBuildingStep(2);
        }
    }
}


function previousBuildingStep(step) {
    showBuildingStep(step);
}


function validateBuildingBasicInfo() {
    var isValid = true;
    

    document.getElementById('building_name_error').textContent = '';
    document.getElementById('address_error').textContent = '';
    document.getElementById('total_floors_error').textContent = '';
    document.getElementById('flats_per_floor_error').textContent = '';
    

    var buildingName = document.getElementById('building_name').value.trim();
    if (buildingName === '') {
        document.getElementById('building_name_error').textContent = 'Building name is required';
        isValid = false;
    }
    

    var address = document.getElementById('building_address').value.trim();
    if (address === '') {
        document.getElementById('address_error').textContent = 'Address is required';
        isValid = false;
    }
    

    var totalFloors = parseInt(document.getElementById('total_floors').value);
    if (isNaN(totalFloors) || totalFloors < 1 || totalFloors > 50) {
        document.getElementById('total_floors_error').textContent = 'Please enter floors between 1 and 50';
        isValid = false;
    }
    

    var flatsPerFloor = parseInt(document.getElementById('flats_per_floor').value);
    if (isNaN(flatsPerFloor) || flatsPerFloor < 1 || flatsPerFloor > 20) {
        document.getElementById('flats_per_floor_error').textContent = 'Please enter flats between 1 and 20';
        isValid = false;
    }
    
    return isValid;
}


function saveBuildingBasicInfo() {
    buildingFormData.building_name = document.getElementById('building_name').value.trim();
    buildingFormData.address = document.getElementById('building_address').value.trim();
    buildingFormData.total_floors = parseInt(document.getElementById('total_floors').value);
    buildingFormData.flats_per_floor = parseInt(document.getElementById('flats_per_floor').value);
}


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


function openEditBuildingModal(buildingId) {
    var modal = document.getElementById('editBuildingModal');
    if (!modal) return;
    
    modal.style.display = 'flex';
    

    showMessage('Loading building details...', 'info');
    

    var formData = new FormData();
    formData.append('action', 'get_building_details');
    formData.append('building_id', buildingId);
    
    var xhr = new XMLHttpRequest();
    xhr.open('POST', '../controller/building_controller.php', true);
    
    xhr.onreadystatechange = function() {
        if (xhr.readyState === 4 && xhr.status === 200) {
            try {
                var response = JSON.parse(xhr.responseText);
                
                if (response.success) {
                    populateEditBuildingForm(response.building);
                    

                    loadFirstFlatCharges(buildingId);
                } else {
                    showMessage(response.message || 'Failed to load building', 'error');
                    closeEditBuildingModal();
                }
            } catch (e) {
                console.error('Parse error:', e);
                showMessage('Error loading building details', 'error');
                closeEditBuildingModal();
            }
        }
    };
    
    xhr.send(formData);
}


function populateEditBuildingForm(building) {
    document.getElementById('edit_building_id').value = building.building_id;
    document.getElementById('edit_building_name').value = building.building_name;
    document.getElementById('edit_building_address').value = building.address;
    document.getElementById('edit_total_floors').value = building.total_floors;
}


function loadFirstFlatCharges(buildingId) {
    var formData = new FormData();
    formData.append('action', 'get_first_flat_charges');
    formData.append('building_id', buildingId);
    
    var xhr = new XMLHttpRequest();
    xhr.open('POST', '../controller/building_controller.php', true);
    
    xhr.onreadystatechange = function() {
        if (xhr.readyState === 4 && xhr.status === 200) {
            try {
                var response = JSON.parse(xhr.responseText);
                
                if (response.success && response.charges) {
                    var charges = response.charges;
                    
                    document.getElementById('edit_default_rent').value = charges.rent || 0;
                    document.getElementById('edit_default_gas_bill').value = charges.gas_bill || 0;
                    document.getElementById('edit_default_water_bill').value = charges.water_bill || 0;
                    document.getElementById('edit_default_service_charge').value = charges.service_charge || 0;
                    document.getElementById('edit_default_cleaning_charge').value = charges.cleaning_charge || 0;
                    document.getElementById('edit_default_miscellaneous').value = charges.miscellaneous || 0;
                }
                
                if (response.success && response.meter) {
                    document.getElementById('edit_default_meter_type').value = response.meter.meter_type || '';
                    document.getElementById('edit_default_per_unit_cost').value = response.meter.per_unit_cost || '';
                }
            } catch (e) {
                console.error('Error loading charges:', e);
            }
        }
    };
    
    xhr.send(formData);
}

function closeEditBuildingModal() {
    var modal = document.getElementById('editBuildingModal');
    if (modal) {
        modal.style.display = 'none';
    }
}

document.addEventListener('DOMContentLoaded', function() {
    var editForm = document.getElementById('editBuildingForm');
    if (editForm) {
        editForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            var formData = new FormData(this);
            formData.append('action', 'update_building');
            
            showMessage('Updating building...', 'info');
            
            var xhr = new XMLHttpRequest();
            xhr.open('POST', '../controller/building_controller.php', true);
            
            xhr.onreadystatechange = function() {
                if (xhr.readyState === 4) {
                    console.log('Update response:', xhr.responseText);
                    
                    if (xhr.status === 200) {
                        try {
                            var response = JSON.parse(xhr.responseText);
                            
                            if (response.success) {
                                showMessage(response.message, 'success');
                                closeEditBuildingModal();
                                
                                setTimeout(function() {
                                    window.location.reload();
                                }, 1000);
                            } else {
                                showMessage(response.message || 'Failed to update building', 'error');
                            }
                        } catch (e) {
                            console.error('Parse error:', e);
                            showMessage('Error updating building', 'error');
                        }
                    } else {
                        showMessage('Server error. Please try again.', 'error');
                    }
                }
            };
            
            xhr.send(formData);
        });
    }
});



function generateFlatPreview() {
    buildingFormData.naming_scheme = document.querySelector('input[name="naming_scheme"]:checked').value;
    
    if (buildingFormData.naming_scheme === 'floor_letter') {
        buildingFormData.letter_start = document.getElementById('letter_start').value;
    } else {
        buildingFormData.custom_prefix = document.getElementById('custom_prefix').value.trim();
        buildingFormData.numbering_style = document.getElementById('numbering_style').value;
    }
    
    generatedFlats = [];
    var flatCounter = 1;
    
    for (var floor = 1; floor <= buildingFormData.total_floors; floor++) {
        for (var flatNum = 1; flatNum <= buildingFormData.flats_per_floor; flatNum++) {
            var flatNumber = '';
            
            if (buildingFormData.naming_scheme === 'floor_letter') {
                var letterIndex = flatNum - 1;
                var startCharCode = buildingFormData.letter_start.charCodeAt(0);
                var letter = String.fromCharCode(startCharCode + letterIndex);
                flatNumber = floor + letter;
            } else {
                var prefix = buildingFormData.custom_prefix;
                
                if (buildingFormData.numbering_style === 'floor_based') {
                    var num = (floor * 100) + flatNum;
                    flatNumber = prefix + num;
                } else {
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
    
    displayBuildingPreview();
    showBuildingStep(3);
}

function toggleFlatExclusion(flatNumber) {
    var index = excludedFlats.indexOf(flatNumber);
    
    if (index > -1) {
        excludedFlats.splice(index, 1);
    } else {
        excludedFlats.push(flatNumber);
    }
    
    var flatElement = document.querySelector('[data-flat-number="' + flatNumber + '"]');
    if (flatElement) {
        if (excludedFlats.indexOf(flatNumber) > -1) {
            flatElement.classList.add('excluded');
        } else {
            flatElement.classList.remove('excluded');
        }
    }
    
    updateFlatCount();
}

function updateFlatCount() {
    var totalFlats = generatedFlats.length;
    var excludedCount = excludedFlats.length;
    var includedCount = totalFlats - excludedCount;
    
    document.getElementById('preview_total_flats').textContent = includedCount + ' (excluded: ' + excludedCount + ')';
}

function displayBuildingPreview() {
    document.getElementById('preview_building_name').textContent = buildingFormData.building_name;
    document.getElementById('preview_address').textContent = buildingFormData.address;
    document.getElementById('preview_floors').textContent = buildingFormData.total_floors;
    document.getElementById('preview_total_flats').textContent = generatedFlats.length;
    
    var container = document.getElementById('flatsPreviewContainer');
    container.innerHTML = '';
    
    var instruction = document.createElement('p');
    instruction.className = 'preview-instruction';
    instruction.textContent = 'Click on any flat to exclude it from creation';
    container.appendChild(instruction);
    
    var currentFloor = 0;
    var floorDiv = null;
    
    for (var i = 0; i < generatedFlats.length; i++) {
        var flat = generatedFlats[i];
        
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
        
        var flatItem = document.createElement('div');
        flatItem.className = 'flat-item';
        flatItem.textContent = flat.flat_number;
        flatItem.setAttribute('data-flat-number', flat.flat_number);
        
        if (excludedFlats.indexOf(flat.flat_number) > -1) {
            flatItem.classList.add('excluded');
        }
        
        flatItem.onclick = (function(flatNum) {
            return function() {
                toggleFlatExclusion(flatNum);
            };
        })(flat.flat_number);
        
        floorDiv.appendChild(flatItem);
    }
    
    if (floorDiv) {
        container.appendChild(floorDiv);
    }
}

function submitBuilding() {
    var flatsToCreate = [];
    for (var i = 0; i < generatedFlats.length; i++) {
        if (excludedFlats.indexOf(generatedFlats[i].flat_number) === -1) {
            flatsToCreate.push(generatedFlats[i]);
        }
    }
    
    if (flatsToCreate.length === 0) {
        showMessage('Please select at least one flat to create', 'error');
        return;
    }
    
    showMessage('Creating building with ' + flatsToCreate.length + ' flats...', 'info');
    
    var formData = new FormData();
    formData.append('action', 'create_building');
    formData.append('building_name', buildingFormData.building_name);
    formData.append('address', buildingFormData.address);
    formData.append('total_floors', buildingFormData.total_floors);
    formData.append('flats_data', JSON.stringify(flatsToCreate));
    
    var xhr = new XMLHttpRequest();
    xhr.open('POST', '../controller/building_controller.php', true);
    
    xhr.onreadystatechange = function() {
        if (xhr.readyState === 4) {
            console.log('Response status:', xhr.status);
            console.log('Response text:', xhr.responseText);
            
            if (xhr.status === 200) {
                try {
                    var jsonStart = xhr.responseText.indexOf('{');
                    var jsonText = xhr.responseText;
                    if (jsonStart > 0) {
                        jsonText = xhr.responseText.substring(jsonStart);
                    }
                    
                    var response = JSON.parse(jsonText);
                    console.log('Parsed response:', response);
                    
                    if (response.success) {
                        showMessage(response.message, 'success');
                        
                        var createButton = document.querySelector('#buildingStep3 .btn-primary');
                        if (createButton) {
                            createButton.textContent = 'Close';
                            createButton.onclick = function() {
                                closeAddBuildingModal();
                                window.location.reload();
                            };
                        }
                        
                        var backButton = document.querySelector('#buildingStep3 .btn-secondary');
                        if (backButton) {
                            backButton.style.display = 'none';
                        }
                        
                    } else {
                        showMessage(response.message || 'Failed to create building', 'error');
                    }
                } catch (e) {
                    console.error('JSON parse error:', e);
                    console.error('Response was:', xhr.responseText);
                    showMessage('Server response error. Building may have been created. Please refresh the page.', 'error');
                }
            } else {
                showMessage('Server error (Status: ' + xhr.status + '). Please try again.', 'error');
            }
        }
    };
    
    xhr.onerror = function() {
        showMessage('Network error. Please check your connection.', 'error');
    };
    
    xhr.send(formData);
}