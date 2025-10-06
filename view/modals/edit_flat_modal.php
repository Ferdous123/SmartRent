<!-- Edit Flat Modal - Extended -->
<div id="editFlatModal" class="modal" style="display: none;">
    <div class="modal-content" style="max-width: 800px;">
        <div class="modal-header">
            <h3>Edit Flat Details</h3>
            <button class="modal-close" onclick="closeEditFlatModal()">&times;</button>
        </div>
        <div class="modal-body">
            <form id="editFlatForm">
                <input type="hidden" id="edit_flat_id" name="flat_id">
                
                <!-- Basic Information -->
                <h4 style="color: #667eea; margin-bottom: 1rem;">Basic Information</h4>

                <div class="form-row">
                    <div class="form-group">
                        <label for="edit_flat_number">Flat Number <span class="required">*</span></label>
                        <input type="text" id="edit_flat_number" name="flat_number" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="edit_floor_number">Floor Number <span class="required">*</span></label>
                        <input type="number" id="edit_floor_number" name="floor_number" min="1" required>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="edit_bedrooms">Bedrooms</label>
                        <input type="number" id="edit_bedrooms" name="bedrooms" min="0" max="10">
                    </div>
                    
                    <div class="form-group">
                        <label for="edit_bathrooms">Bathrooms</label>
                        <input type="number" id="edit_bathrooms" name="bathrooms" min="0" max="10">
                    </div>
                </div>
                
                <!-- Electric Meter Only -->
                <h4 style="color: #667eea; margin-bottom: 1rem;">Electric Meter</h4>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="edit_electric_type">Type</label>
                        <select id="edit_electric_type" name="electric_type" onchange="toggleMeterFields('electric')">
                            <option value="">No Electric Meter</option>
                            <option value="electric_prepaid">Prepaid</option>
                            <option value="electric_postpaid">Postpaid</option>
                        </select>
                    </div>
                    
                    <div class="form-group" id="edit_electric_number_group" style="display: none;">
                        <label for="edit_electric_number">Meter Number</label>
                        <input type="text" id="edit_electric_number" name="electric_number">
                    </div>
                    
                    <div class="form-group" id="edit_electric_cost_group" style="display: none;">
                        <label for="edit_electric_cost">Per Unit Cost (৳)</label>
                        <input type="number" id="edit_electric_cost" name="electric_cost" min="0" step="0.01">
                    </div>
                </div>
                
                <div class="form-row" id="edit_electric_reading_group" style="display: none;">
                    <div class="form-group">
                        <label for="edit_electric_current">Current Reading</label>
                        <input type="number" id="edit_electric_current" name="electric_current" min="0" step="0.01">
                    </div>
                    
                    <div class="form-group">
                        <label for="edit_electric_previous">Previous Reading</label>
                        <input type="number" id="edit_electric_previous" name="electric_previous" min="0" step="0.01">
                    </div>
                </div>
                
                <hr style="margin: 1.5rem 0; border: none; border-top: 1px solid #e0e0e0;">
                
                <!-- Fixed Monthly Charges -->
                <h4 style="color: #667eea; margin-bottom: 1rem;">Monthly Charges</h4>

                <div class="form-row">
                    <div class="form-group">
                        <label for="edit_rent">Rent (৳)</label>
                        <input type="number" id="edit_rent" name="rent" min="0" step="0.01">
                    </div>
                    
                    <div class="form-group">
                        <label for="edit_gas_bill">Gas Bill (৳)</label>
                        <input type="number" id="edit_gas_bill" name="gas_bill" min="0" step="0.01">
                    </div>
                    
                    <div class="form-group">
                        <label for="edit_water_bill">Water Bill (৳)</label>
                        <input type="number" id="edit_water_bill" name="water_bill" min="0" step="0.01">
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="edit_service_charge">Service Charge (৳)</label>
                        <input type="number" id="edit_service_charge" name="service_charge" min="0" step="0.01">
                    </div>
                    
                    <div class="form-group">
                        <label for="edit_cleaning_charge">Cleaning Charge (৳)</label>
                        <input type="number" id="edit_cleaning_charge" name="cleaning_charge" min="0" step="0.01">
                    </div>
                    
                    <div class="form-group">
                        <label for="edit_miscellaneous">Miscellaneous (৳)</label>
                        <input type="number" id="edit_miscellaneous" name="miscellaneous" min="0" step="0.01">
                    </div>
                </div>
                
                <div class="modal-actions">
                    <button type="button" class="btn-secondary" onclick="closeEditFlatModal()">Cancel</button>
                    <button type="submit" class="btn-primary">Save Changes</button>
                </div>
            </form>
        </div>
    </div>
</div>