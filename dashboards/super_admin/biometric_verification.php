<?php
require_once '../../includes/header.php';
?>

<div class="container-fluid py-5">
    <div class="row justify-content-center">
        <div class="col-xl-8">
            <div class="card border-0 shadow-lg overflow-hidden glass-card">
                <div class="card-body p-0">
                    <div class="row g-0">
                        <!-- Scanner Side -->
                        <div class="col-md-5 bg-primary text-white p-5 d-flex flex-column align-items-center justify-content-center text-center scanner-gradient">
                            <div class="mb-4">
                                <i class="bi bi-shield-check fs-1"></i>
                            </div>
                            <h2 class="fw-bold mb-3">Biometric Scanner</h2>
                            <p class="text-white-50 mb-5">Please place your finger on the scanner area or enter Biometric ID for verification.</p>
                            
                            <div class="scanner-container mb-4">
                                <div class="scanner-box">
                                    <i class="bi bi-fingerprint"></i>
                                    <div class="scan-line"></div>
                                </div>
                            </div>

                            <div class="w-100" style="max-width: 250px;">
                                <input type="text" id="biometric_id" class="form-control form-control-lg bg-white bg-opacity-10 border-white border-opacity-25 text-white text-center rounded-pill" placeholder="Enter Bio ID..." autofocus>
                                <button id="btn-verify" class="btn btn-light w-100 mt-3 rounded-pill fw-bold py-2 shadow-sm text-primary">Verify Now</button>
                            </div>
                            
                            <div class="mt-4 text-white-50 small">
                                <i class="bi bi-info-circle me-1"></i> System Status: <span class="badge bg-success bg-opacity-25 text-success border border-success border-opacity-25 rounded-pill px-3">Active</span>
                            </div>
                        </div>

                        <!-- Result Side -->
                        <div class="col-md-7 bg-white p-5 position-relative" id="result-side">
                            <div class="welcome-view text-center py-5">
                                <div class="mb-4 text-muted">
                                    <i class="bi bi-person-badge display-1 opacity-25"></i>
                                </div>
                                <h3 class="fw-bold text-dark mb-2">Ready to Scan</h3>
                                <p class="text-muted">Awaiting biometric authentication...</p>
                            </div>

                            <!-- Success View (Hidden by default) -->
                            <div id="success-view" class="d-none animate__animated animate__fadeIn">
                                <div class="text-center mb-4">
                                    <div class="user-avatar-container mb-3">
                                        <img src="../../assets/img/avatar.png" id="res-avatar" class="rounded-circle shadow-lg border border-5 border-white" style="width: 150px; height: 150px; object-fit: cover;">
                                        <div class="status-indicator bg-success"></div>
                                    </div>
                                    <h2 class="fw-bold text-dark mb-0" id="res-name">User Name</h2>
                                    <p class="badge bg-primary bg-opacity-10 text-primary px-3 py-2 rounded-pill mt-2" id="res-role">Staff Member</p>
                                </div>

                                <div class="row g-3">
                                    <div class="col-6">
                                        <div class="p-3 border border-light rounded-4 bg-light bg-opacity-50">
                                            <small class="text-muted d-block mb-1">Department</small>
                                            <span class="fw-bold text-dark" id="res-dept">N/A</span>
                                        </div>
                                    </div>
                                    <div class="col-6">
                                        <div class="p-3 border border-light rounded-4 bg-light bg-opacity-50">
                                            <small class="text-muted d-block mb-1">Designation</small>
                                            <span class="fw-bold text-dark" id="res-desig">N/A</span>
                                        </div>
                                    </div>
                                    <div class="col-12 text-center mt-4">
                                        <div class="alert alert-success border-0 rounded-4 shadow-sm py-3 px-4">
                                            <i class="bi bi-check-circle-fill me-2 fs-5"></i>
                                            <span id="res-message" class="fw-bold">Attendance Logged Successfully!</span>
                                            <div class="mt-1 text-success-emphasis opacity-75" id="res-time">Checked in at 09:15 AM</div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    .glass-card {
        background: rgba(255, 255, 255, 0.7) !important;
        backdrop-filter: blur(20px) !important;
        -webkit-backdrop-filter: blur(20px) !important;
    }

    .scanner-gradient {
        background: linear-gradient(135deg, #2563eb, #1e4ed8);
    }

    .scanner-container {
        position: relative;
        padding: 20px;
    }

    .scanner-box {
        width: 120px;
        height: 120px;
        background: rgba(255, 255, 255, 0.1);
        border: 2px solid rgba(255, 255, 255, 0.3);
        border-radius: 20px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 60px;
        color: rgba(255, 255, 255, 0.9);
        position: relative;
        overflow: hidden;
        transition: all 0.3s ease;
    }

    .scan-line {
        position: absolute;
        width: 100%;
        height: 4px;
        background: #60a5fa;
        box-shadow: 0 0 15px 4px rgba(96, 165, 250, 0.6);
        top: 0;
        left: 0;
        display: none;
        animation: scan 2s ease-in-out infinite;
    }

    @keyframes scan {
        0%, 100% { top: 10%; }
        50% { top: 90%; }
    }

    .scanning .scan-line {
        display: block;
    }

    .scanning .scanner-box {
        border-color: #60a5fa;
        background: rgba(96, 165, 250, 0.1);
        transform: scale(1.05);
    }

    .user-avatar-container {
        position: relative;
        display: inline-block;
    }

    .status-indicator {
        position: absolute;
        bottom: 5px;
        right: 15px;
        width: 25px;
        height: 25px;
        border-radius: 50%;
        border: 4px solid white;
    }

    #biometric_id:focus {
        background: rgba(255, 255, 255, 0.15) !important;
        box-shadow: 0 0 0 4px rgba(255, 255, 255, 0.1);
    }
</style>

<!-- SweetAlert2 for nicer notifications -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css"/>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const input = document.getElementById('biometric_id');
    const btn = document.getElementById('btn-verify');
    const scannerBox = document.querySelector('.scanner-container');

    function performScan() {
        const bioId = input.value.trim();
        if(!bioId) {
            Swal.fire({
                icon: 'warning',
                title: 'No Input',
                text: 'Please enter a Biometric ID or scan your finger.',
                confirmButtonColor: '#2563eb'
            });
            return;
        }

        // Add scanning class
        scannerBox.classList.add('scanning');
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Scanning...';

        // Simulate network delay for effect
        setTimeout(() => {
            fetch('../../api/process_attendance_ajax.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `biometric_id=${encodeURIComponent(bioId)}`
            })
            .then(response => response.json())
            .then(data => {
                scannerBox.classList.remove('scanning');
                btn.disabled = false;
                btn.innerHTML = 'Verify Now';
                
                if(data.status === 'success') {
                    showResult(data);
                } else if(data.status === 'info') {
                   Swal.fire({
                        icon: 'info',
                        title: 'Notice',
                        text: data.message,
                        confirmButtonColor: '#2563eb'
                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Verification Failed',
                        text: data.message,
                        confirmButtonColor: '#2563eb'
                    });
                }
                input.value = '';
                input.focus();
            })
            .catch(error => {
                scannerBox.classList.remove('scanning');
                btn.disabled = false;
                btn.innerHTML = 'Verify Now';
                console.error('Error:', error);
                Swal.fire('Error', 'Something went wrong on the server.', 'error');
            });
        }, 1500);
    }

    function showResult(data) {
        const welcomeView = document.querySelector('.welcome-view');
        const successView = document.getElementById('success-view');

        // Update Details
        document.getElementById('res-name').textContent = data.user.name;
        document.getElementById('res-role').textContent = data.user.role.toUpperCase();
        document.getElementById('res-dept').textContent = data.user.department || 'General';
        document.getElementById('res-desig').textContent = data.user.designation || 'Staff';
        document.getElementById('res-message').textContent = data.message;
        document.getElementById('res-time').textContent = `${data.action} at ${data.time}`;
        
        // Hide welcome, show success
        welcomeView.classList.add('d-none');
        successView.classList.remove('d-none');

        // Play success sound (if we had one) or trigger haptic
        
        // Reset after 8 seconds
        setTimeout(() => {
            successView.classList.add('animate__fadeOut');
            setTimeout(() => {
                successView.classList.add('d-none');
                successView.classList.remove('animate__fadeOut');
                welcomeView.classList.remove('d-none');
            }, 500);
        }, 8000);
    }

    btn.addEventListener('click', performScan);
    input.addEventListener('keypress', function(e) {
        if(e.key === 'Enter') performScan();
    });
});
</script>

<?php require_once '../../includes/footer.php'; ?>
