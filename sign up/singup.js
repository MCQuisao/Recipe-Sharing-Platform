document.addEventListener('DOMContentLoaded', () => {

    const loginBtn = document.getElementById('btn-login');
    const signupBtn = document.getElementById('btn-signup');
    const loginForm = document.getElementById('login-form');
    const signupForm = document.getElementById('signup-form');
    const loginVisual = document.querySelector('.login-visual');
    const signupVisual = document.querySelector('.signup-visual');

    const forgotTrigger = document.getElementById('forgot-pass-trigger');
    const forgotPanel = document.getElementById('forgot-panel');
    const closeOverlay = document.querySelector('.close-overlay');
    const passToggles = document.querySelectorAll('.show-hide-btn');

    // Recovery Elements
    const sendRecoveryBtn = document.getElementById('send-recovery-btn');
    const recoverEmailInput = document.getElementById('recover-email');

    function switchState(target) {
        if (target === 'login') {
            loginBtn.classList.add('active');
            signupBtn.classList.remove('active');
            loginForm.classList.add('active');
            signupForm.classList.remove('active');
            loginVisual.classList.add('active');
            signupVisual.classList.remove('active');
        } else {
            signupBtn.classList.add('active');
            loginBtn.classList.remove('active');
            signupForm.classList.add('active');
            loginForm.classList.remove('active');
            signupVisual.classList.add('active');
            loginVisual.classList.remove('active');
        }
    }

    loginBtn.addEventListener('click', () => switchState('login'));
    signupBtn.addEventListener('click', () => switchState('signup'));

    passToggles.forEach(btn => {
        btn.addEventListener('click', (e) => {
            const input = e.currentTarget.closest('.input-group').querySelector('input');
            input.type = input.type === 'password' ? 'text' : 'password';

            e.currentTarget.style.color = input.type === 'text' ? 'var(--accent-color)' : 'inherit';
        });
    });

    forgotTrigger.addEventListener('click', () => {
        forgotPanel.classList.add('active');
        loginForm.style.opacity = '0.3';
    });

    closeOverlay.addEventListener('click', () => {
        forgotPanel.classList.remove('active');
        loginForm.style.opacity = '1';
    });

    // Password Recovery REST API implementation block
    if (sendRecoveryBtn) {
        sendRecoveryBtn.addEventListener('click', async () => {
            const email = recoverEmailInput.value.trim();

            if (!email) {
                alert("Please fill in your email address.");
                return;
            }

            // UI Loading indicators
            sendRecoveryBtn.disabled = true;
            sendRecoveryBtn.innerText = "Sending Link...";

            try {
                // FIXED PATH: Changed from '../' to '../' to explicitly step out of folder
                const response = await fetch('../auth/forgot-password.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ email: email })
                });

                const data = await response.json();

                if (response.ok) {
                    alert(data.message);
                    // Reset fields and close overlay
                    recoverEmailInput.value = '';
                    closeOverlay.click();
                } else {
                    alert(data.error || "An error occurred. Please try again.");
                }
            } catch (err) {
                console.error("Recovery request error trace:", err);
                alert("Network connection error. Check server status.");
            } finally {
                // Restore original button states
                sendRecoveryBtn.disabled = false;
                sendRecoveryBtn.innerText = "Send Link";
            }
        });
    }

    loginForm.addEventListener('submit', async (e) => {
        e.preventDefault();

        const email = document.getElementById('login-email').value.trim();
        const password = document.getElementById('login-pass').value;

        if (!email || !password) {
            alert("Please fill in all fields.");
            return;
        }

        try {
            const res = await fetch("../auth/login.php", {
                method: "POST",
                headers: { "Content-Type": "application/json" },
                body: JSON.stringify({ email, password })
            });

            const data = await res.json();

            if (res.ok && data.redirect) {
                window.location.href = data.redirect;
            } else {
                alert(data.error || "Login failed");
            }
        } catch (err) {
            alert("Server error. Please try again.");
            console.error(err);
        }
    });

    signupForm.addEventListener('submit', async (e) => {
        e.preventDefault();

        const first_name = document.getElementById('signup-fname').value.trim();
        const last_name = document.getElementById('signup-lname').value.trim();
        const email = document.getElementById('signup-email').value.trim();
        const password = document.getElementById('signup-pass').value;

        if (!first_name || !last_name || !email || !password) {
            alert("All fields are required.");
            return;
        }

        try {
            const res = await fetch("../auth/register.php", {
                method: "POST",
                headers: { "Content-Type": "application/json" },
                body: JSON.stringify({ first_name, last_name, email, password })
            });

            const data = await res.json();

            if (res.ok && data.redirect) {
                window.location.href = data.redirect;
            } else {
                alert(data.error || "Signup failed");
            }
        } catch (err) {
            alert("Server error. Please try again.");
            console.error(err);
        }
    });

});