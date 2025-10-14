
<div id="assignManagerModal" class="modal" style="display: none;">
    <div class="modal-content">
        <div class="modal-header">
            <h3>Assign Manager to Building</h3>
            <button class="modal-close" onclick="closeAssignManagerModal()">&times;</button>
        </div>
        <div class="modal-body">
            <form id="assignManagerForm">
                <input type="hidden" id="manager_building_id" name="building_id">
                
                <div class="form-group">
                    <label for="manager_id">Select Manager <span class="required">*</span></label>
                    <select id="manager_id" name="manager_id" required>
                        <option value="">-- Select Manager --</option>
                    </select>
                    <span class="form-error" id="manager_id_error"></span>
                </div>
                
                <div class="modal-actions">
                    <button type="button" class="btn-secondary" onclick="closeAssignManagerModal()">Cancel</button>
                    <button type="submit" class="btn-primary">Assign Manager</button>
                </div>
            </form>
        </div>
    </div>
</div>