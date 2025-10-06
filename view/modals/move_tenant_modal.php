<!-- Move Tenant Modal -->
<div id="moveTenantModal" class="modal" style="display: none;">
    <div class="modal-content">
        <div class="modal-header">
            <h3>Move Tenant to Different Flat</h3>
            <button class="modal-close" onclick="closeMoveTenantModal()">&times;</button>
        </div>
        <div class="modal-body">
            <form id="moveTenantForm">
                <input type="hidden" id="move_assignment_id" name="assignment_id">
                
                <div class="current-flat-info">
                    <h4>Current Flat</h4>
                    <p id="currentFlatDetails"></p>
                    <p>Advance Balance: <strong id="currentAdvanceBalance">৳0.00</strong></p>
                </div>
                
                <div class="form-group">
                    <label>Select New Flat <span class="required">*</span></label>
                    <select id="new_flat_id" name="new_flat_id" required>
                        <option value="">-- Select Flat --</option>
                    </select>
                    <span class="form-error" id="new_flat_error"></span>
                </div>
                
                <div class="form-group">
                    <label>Transfer Advance Amount</label>
                    <input type="number" id="transfer_advance" name="transfer_advance" 
                           min="0" step="0.01" value="0">
                    <small>Amount to transfer from current advance balance</small>
                </div>
                
                <div class="form-group">
                    <label>Additional Advance (Optional)</label>
                    <input type="number" id="additional_advance" name="additional_advance" 
                           min="0" step="0.01" value="0" placeholder="0.00">
                    <small>Additional advance amount if needed</small>
                </div>
                
                <div class="advance-summary">
                    <p>New Advance Balance: <strong id="newAdvanceBalance">৳0.00</strong></p>
                </div>
                
                <div class="modal-actions">
                    <button type="button" class="btn-secondary" onclick="closeMoveTenantModal()">Cancel</button>
                    <button type="submit" class="btn-primary">Move Tenant</button>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
.current-flat-info {
    padding: 1rem;
    background: #e3f2fd;
    border-radius: 8px;
    margin-bottom: 1.5rem;
}

.current-flat-info h4 {
    margin: 0 0 0.5rem 0;
    color: #1976d2;
}

.current-flat-info p {
    margin: 0.25rem 0;
    color: #333;
}

.advance-summary {
    padding: 1rem;
    background: #e8f5e9;
    border-radius: 8px;
    margin-top: 1rem;
}

.advance-summary p {
    margin: 0;
    font-size: 16px;
    color: #388e3c;
}
</style>