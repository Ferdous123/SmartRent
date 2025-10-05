<!-- Edit Building Modal -->
<div id="editBuildingModal" class="modal" style="display: none;">
    <div class="modal-content">
        <div class="modal-header">
            <h3>Edit Building</h3>
            <button class="modal-close" onclick="closeEditBuildingModal()">&times;</button>
        </div>
        <div class="modal-body">
            <form id="editBuildingForm">
                <input type="hidden" id="edit_building_id" name="building_id">
                
                <div class="form-group">
                    <label for="edit_building_name">Building Name <span class="required">*</span></label>
                    <input type="text" id="edit_building_name" name="building_name" required>
                    <span class="form-error" id="edit_building_name_error"></span>
                </div>
                
                <div class="form-group">
                    <label for="edit_building_address">Address <span class="required">*</span></label>
                    <textarea id="edit_building_address" name="address" rows="3" required></textarea>
                    <span class="form-error" id="edit_address_error"></span>
                </div>
                
                <div class="form-group">
                    <label for="edit_total_floors">Total Floors <span class="required">*</span></label>
                    <input type="number" id="edit_total_floors" name="total_floors" min="1" max="50" required>
                    <span class="form-error" id="edit_total_floors_error"></span>
                </div>
                
                <div class="modal-actions">
                    <button type="button" class="btn-secondary" onclick="closeEditBuildingModal()">Cancel</button>
                    <button type="submit" class="btn-primary">Save Changes</button>
                </div>
            </form>
        </div>
    </div>
</div>