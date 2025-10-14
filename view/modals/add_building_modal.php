
<div id="addBuildingModal" class="modal" style="display: none;">
    <div class="modal-content">
        <div class="modal-header">
            <h3>Add New Building</h3>
            <button class="modal-close" onclick="closeAddBuildingModal()">&times;</button>
        </div>
        <div class="modal-body">
            <div id="buildingSetupSteps">

                <div class="setup-step" id="buildingStep1">
                    <h4>Step 1: Basic Information</h4>
                    <form id="buildingBasicForm">
                        <div class="form-group">
                            <label for="building_name">Building Name <span class="required">*</span></label>
                            <input type="text" id="building_name" name="building_name" required>
                            <span class="form-error" id="building_name_error"></span>
                        </div>
                        
                        <div class="form-group">
                            <label for="building_address">Address <span class="required">*</span></label>
                            <textarea id="building_address" name="address" rows="3" required></textarea>
                            <span class="form-error" id="address_error"></span>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="total_floors">Total Floors <span class="required">*</span></label>
                                <input type="number" id="total_floors" name="total_floors" min="1" max="50" required>
                                <span class="form-error" id="total_floors_error"></span>
                            </div>
                            
                            <div class="form-group">
                                <label for="flats_per_floor">Flats Per Floor <span class="required">*</span></label>
                                <input type="number" id="flats_per_floor" name="flats_per_floor" min="1" max="20" required>
                                <span class="form-error" id="flats_per_floor_error"></span>
                            </div>
                        </div>
                        
                        <div class="modal-actions">
                            <button type="button" class="btn-primary" onclick="nextBuildingStep(2)">Next</button>
                        </div>
                    </form>
                </div>


                <div class="setup-step" id="buildingStep2" style="display: none;">
                    <h4>Step 2: Flat Naming Scheme</h4>
                    <form id="buildingNamingForm">
                        <div class="form-group">
                            <label>Select Naming Scheme <span class="required">*</span></label>
                            <div class="radio-group">
                                <label class="radio-option">
                                    <input type="radio" name="naming_scheme" value="floor_letter" checked onchange="toggleNamingOptions()">
                                    <div class="radio-content">
                                        <strong>Floor + Letter</strong>
                                        <p>Examples: 1A, 1B, 2A, 2B, 3A, 3B</p>
                                    </div>
                                </label>
                                
                                <label class="radio-option">
                                    <input type="radio" name="naming_scheme" value="custom" onchange="toggleNamingOptions()">
                                    <div class="radio-content">
                                        <strong>Custom Naming</strong>
                                        <p>Define your own naming pattern</p>
                                    </div>
                                </label>
                            </div>
                        </div>
                        

                        <div id="floorLetterOptions" class="naming-options">
                            <div class="form-group">
                                <label for="letter_start">Starting Letter</label>
                                <select id="letter_start" name="letter_start">
                                    <option value="A">A</option>
                                    <option value="B">B</option>
                                    <option value="C">C</option>
                                </select>
                            </div>
                        </div>
                        

                        <div id="customNamingOptions" class="naming-options" style="display: none;">
                            <div class="form-group">
                                <label for="custom_prefix">Prefix (Optional)</label>
                                <input type="text" id="custom_prefix" name="custom_prefix" placeholder="e.g., FL, APT">
                                <small>Leave empty for numbers only</small>
                            </div>
                            
                            <div class="form-group">
                                <label>Numbering Style</label>
                                <select id="numbering_style" name="numbering_style">
                                    <option value="sequential">Sequential (101, 102, 103...)</option>
                                    <option value="floor_based">Floor-based (101, 102, 201, 202...)</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="modal-actions">
                            <button type="button" class="btn-secondary" onclick="previousBuildingStep(1)">Back</button>
                            <button type="button" class="btn-primary" onclick="generateFlatPreview()">Preview Flats</button>
                        </div>
                    </form>
                </div>


                <div class="setup-step" id="buildingStep3" style="display: none;">
                    <h4>Step 3: Preview & Confirm</h4>
                    
                    <div class="preview-summary">
                        <h5>Building Summary</h5>
                        <table class="summary-table">
                            <tr>
                                <td><strong>Building Name:</strong></td>
                                <td id="preview_building_name"></td>
                            </tr>
                            <tr>
                                <td><strong>Address:</strong></td>
                                <td id="preview_address"></td>
                            </tr>
                            <tr>
                                <td><strong>Total Floors:</strong></td>
                                <td id="preview_floors"></td>
                            </tr>
                            <tr>
                                <td><strong>Total Flats:</strong></td>
                                <td id="preview_total_flats"></td>
                            </tr>
                        </table>
                    </div>
                    
                    <div class="preview-flats">
                        <h5>Generated Flats</h5>
                        <div id="flatsPreviewContainer" class="flats-grid">
                        </div>
                    </div>
                    
                    <div class="modal-actions">
                        <button type="button" class="btn-secondary" onclick="previousBuildingStep(2)">Back</button>
                        <button type="button" class="btn-primary" onclick="submitBuilding()">Create Building</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>