<!-- Tenant Details Modal -->
<div id="tenantDetailsModal" class="modal" style="display: none;">
    <div class="modal-content" style="max-width: 800px;">
        <div class="modal-header">
            <h3>Tenant Details</h3>
            <button class="modal-close" onclick="closeTenantDetailsModal()">&times;</button>
        </div>
        <div class="modal-body">
            <!-- Tenant Basic Info -->
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

            <!-- Assigned Flats -->
            <div class="section">
                <h4>Assigned Flats</h4>
                <div id="assignedFlatsContainer"></div>
            </div>

            <!-- Financial Summary -->
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

            <!-- Actions -->
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
    margin-bottom: 1.5rem;
    padding-bottom: 1.5rem;
    border-bottom: 2px solid #e0e0e0;
}

.tenant-header {
    display: flex;
    align-items: center;
    gap: 1.5rem;
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
    margin: 0 0 0.5rem 0;
    color: #333;
}

.tenant-basic-info p {
    margin: 0.25rem 0;
    color: #666;
    font-size: 14px;
}

.section {
    margin: 1.5rem 0;
}

.section h4 {
    margin: 0 0 1rem 0;
    color: #333;
    font-size: 16px;
    font-weight: 600;
}

.flat-assignment-card {
    padding: 1rem;
    background: #f5f7fa;
    border-radius: 8px;
    margin-bottom: 1rem;
    border-left: 4px solid #667eea;
}

.flat-assignment-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 0.75rem;
}

.flat-assignment-title {
    font-weight: 600;
    color: #333;
}

.flat-assignment-details {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 0.5rem;
    font-size: 14px;
    margin-bottom: 0.75rem;
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
    gap: 0.5rem;
    margin-top: 0.75rem;
}

.notice-warning {
    padding: 0.75rem;
    background: #fff3e0;
    border-left: 4px solid #f57c00;
    border-radius: 4px;
    margin-top: 0.75rem;
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
    gap: 1rem;
}

.financial-item {
    padding: 1rem;
    background: #f5f7fa;
    border-radius: 8px;
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
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