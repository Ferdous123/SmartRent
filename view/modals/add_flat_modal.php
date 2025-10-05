<!-- Add Flat Modal -->
<div id="addFlatModal" class="modal" style="display: none;">
    <div class="modal-content">
        <div class="modal-header">
            <h3>Add New Flat</h3>
            <button class="modal-close" onclick="closeAddFlatModal()">&times;</button>
        </div>
        <div class="modal-body">
            <form id="addFlatForm">
                <input type="hidden" id="flat_building_id" name="building_id">
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="flat_number">Flat Number <span class="required">*</span></label>
                        <input type="text" id="flat_number" name="flat_number" required>
                        <span class="form-error" id="flat_number_error"></span>
                    </div>
                    
                    <div class="form-group">
                        <label for="floor_number">Floor Number <span class="required">*</span></label>
                        <input type="number" id="floor_number" name="floor_number" min="1" required>
                        <span class="form-error" id="floor_number_error"></span>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="bedrooms">Bedrooms</label>
                        <input type="number" id="bedrooms" name="bedrooms" min="0" max="10">
                    </div>
                    
                    <div class="form-group">
                        <label for="bathrooms">Bathrooms</label>
                        <input type="number" id="bathrooms" name="bathrooms" min="0" max="10">
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="base_rent">Base Rent (৳)</label>
                    <input type="number" id="base_rent" name="base_rent" min="0" step="0.01" value="0">
                </div>
                
                <div class="modal-actions">
                    <button type="button" class="btn-secondary" onclick="closeAddFlatModal()">Cancel</button>
                    <button type="submit" class="btn-primary">Add Flat</button>
                </div>
            </form>
        </div>
    </div>
</div>
