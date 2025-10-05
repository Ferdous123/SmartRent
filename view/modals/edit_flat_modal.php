<!-- Edit Flat Modal -->
<div id="editFlatModal" class="modal" style="display: none;">
    <div class="modal-content">
        <div class="modal-header">
            <h3>Edit Flat</h3>
            <button class="modal-close" onclick="closeEditFlatModal()">&times;</button>
        </div>
        <div class="modal-body">
            <form id="editFlatForm">
                <input type="hidden" id="edit_flat_id" name="flat_id">
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="edit_flat_number">Flat Number <span class="required">*</span></label>
                        <input type="text" id="edit_flat_number" name="flat_number" required>
                        <span class="form-error" id="edit_flat_number_error"></span>
                    </div>
                    
                    <div class="form-group">
                        <label for="edit_floor_number">Floor Number <span class="required">*</span></label>
                        <input type="number" id="edit_floor_number" name="floor_number" min="1" required>
                        <span class="form-error" id="edit_floor_number_error"></span>
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
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="edit_base_rent">Base Rent (৳)</label>
                        <input type="number" id="edit_base_rent" name="base_rent" min="0" step="0.01">
                    </div>
                    
                    <div class="form-group">
                        <label for="edit_flat_status">Status</label>
                        <select id="edit_flat_status" name="status">
                            <option value="available">Available</option>
                            <option value="occupied">Occupied</option>
                            <option value="maintenance">Maintenance</option>
                        </select>
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