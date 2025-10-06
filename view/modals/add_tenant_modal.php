<!-- Add Tenant Modal -->
<div id="addTenantModal" class="modal" style="display: none;">
    <div class="modal-content">
        <div class="modal-header">
            <h3>Add Tenant</h3>
            <button class="modal-close" onclick="closeAddTenantModal()">&times;</button>
        </div>
        <div class="modal-body">
            <!-- Method Selection Tabs -->
            <div class="method-tabs">
                <button class="method-tab active" onclick="switchAddMethod('otp')">Generate OTP</button>
                <button class="method-tab" onclick="switchAddMethod('direct')">Direct Assignment</button>
                <button class="method-tab" onclick="switchAddMethod('generate')">Generate Credentials</button>
            </div>

            <!-- OTP Method -->
            <div id="otpMethod" class="method-content active">
                <p class="method-description">Generate an OTP code for tenant to self-assign</p>
                <form id="otpForm">
                    <div class="form-group">
                        <label>Select Flat <span class="required">*</span></label>
                        <select id="otp_flat_id" name="flat_id" required>
                            <option value="">-- Select Flat --</option>
                        </select>
                        <span class="form-error" id="otp_flat_error"></span>
                    </div>
                    
                    <div class="form-group">
                        <label>Advance Amount <span class="required">*</span></label>
                        <input type="number" id="otp_advance_amount" name="advance_amount" 
                               min="0" step="0.01" placeholder="Enter advance amount" required>
                        <span class="form-error" id="otp_advance_error"></span>
                    </div>
                    
                    <div class="modal-actions">
                        <button type="button" class="btn-secondary" onclick="closeAddTenantModal()">Cancel</button>
                        <button type="submit" class="btn-primary">Generate OTP</button>
                    </div>
                </form>
                
                <!-- OTP Display (after generation) -->
                <div id="otpDisplay" style="display: none;">
                    <div class="otp-result">
                        <h4>OTP Generated Successfully!</h4>
                        <div class="otp-code-box">
                            <span class="otp-code" id="generatedOTP"></span>
                            <button type="button" class="btn-copy" onclick="copyOTP()">Copy</button>
                        </div>
                        <p class="otp-expires">Expires at: <span id="otpExpiresAt"></span></p>
                        <p class="otp-instruction">Give this OTP to the tenant to claim the flat</p>
                        <button type="button" class="btn-primary full-width" onclick="closeAddTenantModal()">Close</button>
                    </div>
                </div>
            </div>

            <!-- Direct Assignment Method -->
            <div id="directMethod" class="method-content">
                <p class="method-description">Assign a registered tenant directly</p>
                <form id="directForm">
                    <div class="form-group">
                        <label>Select Flat <span class="required">*</span></label>
                        <select id="direct_flat_id" name="flat_id" required>
                            <option value="">-- Select Flat --</option>
                        </select>
                        <span class="form-error" id="direct_flat_error"></span>
                    </div>
                    
                    <div class="form-group">
                        <label>Search Tenant <span class="required">*</span></label>
                        <input type="text" id="tenantSearch" placeholder="Type tenant name or email..." 
                               onkeyup="searchTenantsForAssign()">
                        <div id="tenantSearchResults" class="search-results"></div>
                        <input type="hidden" id="direct_tenant_id" name="tenant_id">
                        <span class="form-error" id="direct_tenant_error"></span>
                    </div>
                    
                    <div class="form-group">
                        <label>Advance Amount <span class="required">*</span></label>
                        <input type="number" id="direct_advance_amount" name="advance_amount" 
                               min="0" step="0.01" placeholder="Enter advance amount" required>
                        <span class="form-error" id="direct_advance_error"></span>
                    </div>
                    
                    <div class="modal-actions">
                        <button type="button" class="btn-secondary" onclick="closeAddTenantModal()">Cancel</button>
                        <button type="submit" class="btn-primary">Assign Tenant</button>
                    </div>
                </form>
            </div>

            <!-- Generate Credentials Method -->
            <div id="generateMethod" class="method-content">
                <p class="method-description">Generate credentials for unregistered tenant</p>
                <form id="generateForm">
                    <div class="form-group">
                        <label>Select Flat <span class="required">*</span></label>
                        <select id="generate_flat_id" name="flat_id" required>
                            <option value="">-- Select Flat --</option>
                        </select>
                        <span class="form-error" id="generate_flat_error"></span>
                    </div>
                    
                    <div class="form-group">
                        <label>Advance Amount <span class="required">*</span></label>
                        <input type="number" id="generate_advance_amount" name="advance_amount" 
                               min="0" step="0.01" placeholder="Enter advance amount" required>
                        <span class="form-error" id="generate_advance_error"></span>
                    </div>
                    
                    <div class="modal-actions">
                        <button type="button" class="btn-secondary" onclick="closeAddTenantModal()">Cancel</button>
                        <button type="submit" class="btn-primary">Generate Credentials</button>
                    </div>
                </form>
                
                <!-- Credentials Display (after generation) -->
                <div id="credentialsDisplay" style="display: none;">
                    <div class="credentials-result">
                        <h4>Tenant Credentials Generated!</h4>
                        <div class="credential-box">
                            <label>Username:</label>
                            <div class="credential-value">
                                <span id="generatedUsername"></span>
                                <button type="button" class="btn-copy-small" onclick="copyUsername()">Copy</button>
                            </div>
                        </div>
                        <div class="credential-box">
                            <label>Password:</label>
                            <div class="credential-value">
                                <span id="generatedPassword"></span>
                                <button type="button" class="btn-copy-small" onclick="copyPassword()">Copy</button>
                            </div>
                        </div>
                        <p class="credential-instruction">Give these credentials to the tenant. They can use them to login and complete their profile.</p>
                        <button type="button" class="btn-primary full-width" onclick="closeAddTenantModal()">Close</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
/* Modal Base Styles - CRITICAL FOR POPUP */
.modal {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.5);
    display: none;
    align-items: center;
    justify-content: center;
    z-index: 9999;
    backdrop-filter: blur(5px);
}

.modal-content {
    background: white;
    border-radius: 15px;
    max-width: 600px;
    width: 90%;
    max-height: 90vh;
    overflow-y: auto;
    box-shadow: 0 8px 30px rgba(0,0,0,0.2);
    animation: modalSlideIn 0.3s ease;
}

.modal-header {
    padding: 20px 25px 15px;
    border-bottom: 1px solid #e0e0e0;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.modal-header h3 {
    margin: 0;
    color: #333;
}

.modal-close {
    background: none;
    border: none;
    font-size: 24px;
    cursor: pointer;
    color: #999;
    transition: color 0.2s;
}

.modal-close:hover {
    color: #f44336;
}

.modal-body {
    padding: 25px;
}

.modal-actions {
    display: flex;
    gap: 1rem;
    justify-content: flex-end;
    margin-top: 2rem;
    padding-top: 1.5rem;
    border-top: 1px solid #e0e0e0;
}

@keyframes modalSlideIn {
    from {
        opacity: 0;
        transform: translateY(-50px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.method-tabs {
    display: flex;
    gap: 0.5rem;
    margin-bottom: 1.5rem;
    border-bottom: 2px solid #e0e0e0;
}

.method-tab {
    flex: 1;
    padding: 0.75rem;
    border: none;
    background: none;
    cursor: pointer;
    font-weight: 500;
    color: #666;
    transition: all 0.3s;
    border-bottom: 3px solid transparent;
}

.method-tab.active {
    color: #667eea;
    border-bottom-color: #667eea;
}

.method-tab:hover {
    background: rgba(102, 126, 234, 0.1);
}

.method-content {
    display: none;
}

.method-content.active {
    display: block;
}

.method-description {
    color: #666;
    font-size: 14px;
    margin-bottom: 1.5rem;
    padding: 0.75rem;
    background: #f5f7fa;
    border-radius: 6px;
}

.required {
    color: #f44336;
}

.form-group {
    margin-bottom: 1.5rem;
}

.form-group label {
    display: block;
    margin-bottom: 0.5rem;
    font-weight: 600;
    color: #333;
}

.form-group input,
.form-group select {
    width: 100%;
    padding: 0.75rem;
    border: 2px solid #e0e0e0;
    border-radius: 6px;
    font-size: 14px;
}

.form-group input:focus,
.form-group select:focus {
    outline: none;
    border-color: #667eea;
}

.form-error {
    display: block;
    color: #f44336;
    font-size: 12px;
    margin-top: 0.25rem;
    min-height: 18px;
}

.otp-result,
.credentials-result {
    text-align: center;
    padding: 1rem;
}

.otp-code-box {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 1rem;
    margin: 1.5rem 0;
    padding: 1.5rem;
    background: #e3f2fd;
    border-radius: 8px;
}

.otp-code {
    font-size: 32px;
    font-weight: bold;
    color: #1976d2;
    letter-spacing: 4px;
}

.btn-copy {
    padding: 0.5rem 1rem;
    background: #2196f3;
    color: white;
    border: none;
    border-radius: 6px;
    cursor: pointer;
    font-weight: 600;
}

.btn-copy:hover {
    background: #1976d2;
}

.otp-expires {
    color: #f57c00;
    font-weight: 600;
    margin: 0.5rem 0;
}

.otp-instruction,
.credential-instruction {
    color: #666;
    font-size: 14px;
    margin: 1rem 0;
}

.credential-box {
    margin: 1rem 0;
    padding: 1rem;
    background: #f5f7fa;
    border-radius: 6px;
    text-align: left;
}

.credential-box label {
    display: block;
    font-weight: 600;
    margin-bottom: 0.5rem;
    color: #333;
}

.credential-value {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 1rem;
}

.credential-value span {
    font-size: 18px;
    font-weight: bold;
    color: #1976d2;
}

.btn-copy-small {
    padding: 0.25rem 0.75rem;
    background: #2196f3;
    color: white;
    border: none;
    border-radius: 4px;
    cursor: pointer;
    font-size: 12px;
}

.btn-copy-small:hover {
    background: #1976d2;
}

.search-results {
    max-height: 200px;
    overflow-y: auto;
    border: 1px solid #e0e0e0;
    border-radius: 6px;
    margin-top: 0.5rem;
    display: none;
    background: white;
}

.search-result-item {
    padding: 0.75rem;
    cursor: pointer;
    border-bottom: 1px solid #e0e0e0;
    transition: background 0.2s;
}

.search-result-item:hover {
    background: #f5f7fa;
}

.search-result-item:last-child {
    border-bottom: none;
}

.full-width {
    width: 100%;
}

.btn-primary {
    background: #667eea;
    color: white;
    border: none;
    padding: 0.75rem 1.5rem;
    border-radius: 6px;
    cursor: pointer;
    font-weight: 600;
}

.btn-primary:hover {
    background: #5568d3;
}

.btn-secondary {
    background: #f5f7fa;
    color: #333;
    border: 2px solid #e0e0e0;
    padding: 0.75rem 1.5rem;
    border-radius: 6px;
    cursor: pointer;
    font-weight: 600;
}

.btn-secondary:hover {
    background: #e0e0e0;
}
</style>