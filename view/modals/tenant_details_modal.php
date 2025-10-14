
<div id="tenantDetailsModal" class="modal" style="display: none;">
    <div class="modal-content" style="max-width: 800px;">
        <div class="modal-header">
            <h3>Tenant Details</h3>
            <button class="modal-close" onclick="closeTenantDetailsModal()">&times;</button>
        </div>
        <div class="modal-body">

            <div class="tenant-info-section">
                <div class="tenant-header">
                    <div class="tenant-photo-large" id="tenantPhotoLarge">
                        <span id="tenantInitial"></span>
                    </div>
                    <div class="tenant-basic-info">
                        <h3 id="tenantFullName"></h3>
                        <p id="tenantEmail"></p>
                        <p id="tenantContact"></p>
                    </div>
                </div>
            </div>


            <div class="section">
                <h4>Assigned Flats</h4>
                <div id="assignedFlatsContainer"></div>
            </div>


            <div class="section">
                <h4>Financial Summary</h4>
                <div class="financial-grid">
                    <div class="financial-item">
                        <span class="label">Total Advance Balance:</span>
                        <span class="value" id="totalAdvance">৳0.00</span>
                    </div>
                    <div class="financial-item">
                        <span class="label">Total Outstanding:</span>
                        <span class="value outstanding" id="totalOutstanding">৳0.00</span>
                    </div>
                </div>
            </div>


            <div class="modal-actions">
                <button class="btn-secondary" onclick="editTenantProfile()">Edit Profile</button>
                <button class="btn-primary" onclick="sendMessageToTenant()">Send Message</button>
                <button class="btn-primary" onclick="generateTenantSlip()">Generate Slip</button>
            </div>
        </div>
    </div>
</div>

<style>
.tenant-info-section {
    margin-bottom: 25px;
    padding-bottom: 25px;
    border-bottom: 2px solid #e0e0e0;
}

.tenant-header {
    display: flex;
    align-items: center;
    gap: 25px;
}

.tenant-photo-large {
    width: 80px;
    height: 80px;
    border-radius: 50%;
    background: #667eea;
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: bold;
    font-size: 32px;
}

.tenant-photo-large img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    border-radius: 50%;
}

.tenant-basic-info h3 {
    margin: 0 0 8px 0;
    color: #333;
}

.tenant-basic-info p {
    margin: 4px 0;
    color: #666;
    font-size: 14px;
}

.section {
    margin: 25px 0;
}

.section h4 {
    margin: 0 0 15px 0;
    color: #333;
    font-size: 16px;
    font-weight: 600;
}

.flat-assignment-card {
    padding: 15px;
    background: #f5f5f5;
    border-radius: 8px;
    margin-bottom: 15px;
    border-left: 4px solid #667eea;
}

.flat-assignment-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 12px;
}

.flat-assignment-title {
    font-weight: 600;
    color: #333;
}

.flat-assignment-details {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 8px;
    font-size: 14px;
    margin-bottom: 12px;
}

.detail-row {
    display: flex;
    justify-content: space-between;
}

.detail-row .label {
    color: #666;
}

.detail-row .value {
    color: #333;
    font-weight: 500;
}

.flat-actions {
    display: flex;
    gap: 8px;
    margin-top: 12px;
}

.notice-warning {
    padding: 12px;
    background: #fff3e0;
    border-left: 4px solid #f57c00;
    border-radius: 4px;
    margin-top: 12px;
}

.notice-warning p {
    margin: 0;
    color: #f57c00;
    font-weight: 600;
    font-size: 13px;
}

.financial-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 15px;
}

.financial-item {
    padding: 15px;
    background: #f5f5f5;
    border-radius: 8px;
    display: flex;
    flex-direction: column;
    gap: 8px;
}

.financial-item .label {
    font-size: 14px;
    color: #666;
}

.financial-item .value {
    font-size: 20px;
    font-weight: bold;
    color: #27ae60;
}

.financial-item .value.outstanding {
    color: #e74c3c;
}
</style>