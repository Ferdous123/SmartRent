<!-- Edit Building Modal -->
<div id="editBuildingModal" class="modal" style="display: none;">
    <div class="modal-content" style="max-width: 800px;">
        <div class="modal-header">
            <h3>Edit Building</h3>
            <button class="modal-close" onclick="closeEditBuildingModal()">&times;</button>
        </div>
        <div class="modal-body">
            <form id="editBuildingForm">
                <input type="hidden" id="edit_building_id" name="building_id">
                
                <!-- Building Basic Information -->
                <h4 style="color: #667eea; margin-bottom: 1rem;">Building Information</h4>
                
                <div class="form-group">
                    <label for="edit_building_name">Building Name <span class="required">*</span></label>
                    <input type="text" id="edit_building_name" name="building_name" required>
                </div>
                
                <div class="form-group">
                    <label for="edit_building_address">Address <span class="required">*</span></label>
                    <textarea id="edit_building_address" name="address" rows="3" required></textarea>
                </div>
                
                <div class="form-group">
                    <label for="edit_total_floors">Total Floors <span class="required">*</span></label>
                    <input type="number" id="edit_total_floors" name="total_floors" min="1" required>
                </div>
                
                <hr style="margin: 2rem 0; border: none; border-top: 1px solid #e0e0e0;">
                
                <!-- Default Charges Section -->
                <h4 style="color: #667eea; margin-bottom: 1rem;">Default Charges for All Flats</h4>
                <p style="color: #666; margin-bottom: 1rem; font-size: 14px;">
                    Set default values that will be applied to all flats in this building.
                </p>
                <div style="background: #fff3cd; border: 1px solid #ffc107; padding: 0.75rem; border-radius: 6px; margin-bottom: 1.5rem; font-size: 14px;">
                    <strong>Note:</strong> These values will overwrite existing charges for ALL flats in this building.
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="edit_default_rent">Rent (৳)</label>
                        <input type="number" id="edit_default_rent" name="default_rent" min="0" step="0.01" value="0">
                    </div>
                    
                    <div class="form-group">
                        <label for="edit_default_gas_bill">Gas Bill (৳)</label>
                        <input type="number" id="edit_default_gas_bill" name="default_gas_bill" min="0" step="0.01" value="0">
                    </div>
                    
                    <div class="form-group">
                        <label for="edit_default_water_bill">Water Bill (৳)</label>
                        <input type="number" id="edit_default_water_bill" name="default_water_bill" min="0" step="0.01" value="0">
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="edit_default_service_charge">Service Charge (৳)</label>
                        <input type="number" id="edit_default_service_charge" name="default_service_charge" min="0" step="0.01" value="0">
                    </div>
                    
                    <div class="form-group">
                        <label for="edit_default_cleaning_charge">Cleaning Charge (৳)</label>
                        <input type="number" id="edit_default_cleaning_charge" name="default_cleaning_charge" min="0" step="0.01" value="0">
                    </div>
                    
                    <div class="form-group">
                        <label for="edit_default_miscellaneous">Miscellaneous (৳)</label>
                        <input type="number" id="edit_default_miscellaneous" name="default_miscellaneous" min="0" step="0.01" value="0">
                    </div>
                </div>

                <hr style="margin: 1.5rem 0; border: none; border-top: 1px solid #e0e0e0;">

                <!-- Default Meter Settings -->
                <h4 style="color: #667eea; margin-bottom: 1rem;">Default Electric Meter Settings</h4>

                <div class="form-row">
                    <div class="form-group">
                        <label for="edit_default_meter_type">Electric Meter Type</label>
                        <select id="edit_default_meter_type" name="default_meter_type">
                            <option value="">No Electric Meter</option>
                            <option value="electric_prepaid">Prepaid</option>
                            <option value="electric_postpaid">Postpaid</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="edit_default_per_unit_cost">Per Unit Cost (৳)</label>
                        <input type="number" id="edit_default_per_unit_cost" name="default_per_unit_cost" min="0" step="0.01">
                        <small style="color: #666; font-size: 12px;">Leave empty if prepaid or no meter</small>
                    </div>
                </div>
                
                <div class="modal-actions">
                    <button type="button" class="btn-secondary" onclick="closeEditBuildingModal()">Cancel</button>
                    <button type="submit" class="btn-primary">Save Changes</button>
                </div>
            </form>
        </div>
    </div>
</div>