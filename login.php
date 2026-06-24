<?php
require_once 'db.php';
safe_session_start();

// Redirect to index.php if already logged in
if (isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - SEO Audit Suite</title>
    <link rel="stylesheet" href="index.css">
    <!-- Lucide Icons -->
    <script src="https://unpkg.com/lucide@latest"></script>
</head>
<body class="login-wrapper">

    <div class="login-box glass-panel">
        <div class="login-logo">
            <div class="logo-icon">
                <i data-lucide="shield-check" style="width: 28px; height: 28px; color: white;"></i>
            </div>
            <div>
                <h1 style="font-weight: 800; text-align: center;">SEO Audit Suite</h1>
                <p style="color: var(--text-secondary); font-size: 0.9rem; text-align: center; margin-top: 4px;">Administrative Login</p>
            </div>
        </div>

        <div id="error-message" style="display: none; background: rgba(239, 68, 68, 0.15); border: 1px solid rgba(239, 68, 68, 0.3); color: #fca5a5; padding: 12px 16px; border-radius: var(--radius-sm); font-size: 0.9rem; margin-bottom: 24px;"></div>

        <form id="login-form">
            <div class="form-group">
                <label for="username">Username</label>
                <div style="position: relative;">
                    <i data-lucide="user" style="position: absolute; left: 14px; top: 13px; width: 18px; height: 18px; color: var(--text-muted);"></i>
                    <input type="text" id="username" name="username" class="form-input" style="padding-left: 44px;" placeholder="Enter username..." required autocomplete="username">
                </div>
            </div>

            <div class="form-group" style="margin-bottom: 30px;">
                <label for="password">Password</label>
                <div style="position: relative;">
                    <i data-lucide="lock" style="position: absolute; left: 14px; top: 13px; width: 18px; height: 18px; color: var(--text-muted);"></i>
                    <input type="password" id="password" name="password" class="form-input" style="padding-left: 44px;" placeholder="Enter password..." required autocomplete="current-password">
                </div>
            </div>

            <button type="submit" id="submit-btn" class="btn btn-primary" style="width: 100%; height: 48px;">
                <span>Sign In</span>
            </button>
        </form>
    </div>

    <script>
        // Initialize Lucide Icons
        lucide.createIcons();

        document.getElementById('login-form').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const submitBtn = document.getElementById('submit-btn');
            const errorMsg = document.getElementById('error-message');
            
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<span class="spinner"></span>';
            errorMsg.style.display = 'none';

            const formData = new FormData(this);

            fetch('api.php?action=login', {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    window.location.href = 'index.php';
                } else {
                    errorMsg.textContent = data.error || 'Login failed. Please try again.';
                    errorMsg.style.display = 'block';
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = '<span>Sign In</span>';
                }
            })
            .catch(err => {
                console.error(err);
                errorMsg.textContent = 'A network error occurred. Please try again.';
                errorMsg.style.display = 'block';
                submitBtn.disabled = false;
                submitBtn.innerHTML = '<span>Sign In</span>';
            });
        });
    </script>
</body>
</html>
