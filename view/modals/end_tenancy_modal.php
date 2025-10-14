
<div id="endTenancyModal" class="modal" style="display: none;">
    <div class="modal-content">
        <div class="modal-header">
            <h3>End Tenancy Notice</h3>
            <button class="modal-close" onclick="closeEndTenancyModal()">&times;</button>
        </div>
        <div class="modal-body">
            <div class="warning-box">
                <p>⚠️ This will send a 24-hour notice to the tenant.</p>
                <p>The tenant will be notified and must respond within 24 hours.</p>
                <p>If no response is received, the tenancy will be automatically ended.</p>
            </div>
            
            <form id="endTenancyForm">
                <input type="hidden" id="end_assignment_id" name="assignment_id">
                
                <div class="tenancy-info">
                    <h4>Tenancy Details</h4>
                    <p id="endTenancyDetails"></p>
                </div>
                
                <div class="form-group">
                    <label>Reason for Ending (Optional)</label>
                    <textarea id="end_reason" name="reason" rows="3" 
                              placeholder="Enter reason for ending tenancy..."></textarea>
                </div>
                
                <div class="modal-actions">
                    <button type="button" class="btn-secondary" onclick="closeEndTenancyModal()">Cancel</button>
                    <button type="submit" class="btn-danger">Send End Notice</button>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
.warning-box {
    padding: 15px;
    background: #fff3e0;
    border-left: 4px solid #f57c00;
    border-radius: 6px;
    margin-bottom: 25px;
}

.warning-box p {
    margin: 8px 0;
    color: #f57c00;
    font-size: 14px;
}

.tenancy-info {
    padding: 15px;
    background: #f5f5f5;
    border-radius: 8px;
    margin-bottom: 25px;
}

.tenancy-info h4 {
    margin: 0 0 8px 0;
    color: #333;
}

.tenancy-info p {
    margin: 4px 0;
    color: #666;
}

.btn-danger {
    background: #f44336;
    color: white;
    border: none;
    padding: 12px 24px;
    border-radius: 6px;
    cursor: pointer;
    font-weight: 600;
}

.btn-danger:hover {
    background: #d32f2f;
}
</style>