<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - TimeWise</title>

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">

    <!-- Custom Auth Stylesheet -->
    <link rel="stylesheet" href="{{ asset('css/auth.css') }}?v=3.9">

    <!-- Custom Cube Loading Animation Styles -->
    <style>
        /* Full-Screen Dark Translucent Overlay */
        #loadingOverlay {
            background-color: rgba(26, 18, 11, 0.85); /* Dark espresso theme backdrop */
            backdrop-filter: blur(4px);
            transition: opacity 0.3s ease-in-out;
            z-index: 9999;
        }

        /* Circular Orbital Track Container */
        .cube-loader-wrapper {
            position: relative;
            width: 90px;
            height: 90px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        /* Faint Circular Orbit Line */
        .cube-loader-wrapper::before {
            content: '';
            position: absolute;
            width: 70px;
            height: 70px;
            border: 2px dashed rgba(213, 206, 163, 0.3); /* Taupe accent ring */
            border-radius: 50%;
        }

        /* Rotating Orbit Frame */
        .orbit-container {
            width: 70px;
            height: 70px;
            position: absolute;
            animation: orbitRotate 1.6s cubic-bezier(0.5, 0, 0.5, 1) infinite;
        }

        /* The Cube moving in a circular path */
        .moving-cube {
            width: 20px;
            height: 20px;
            background-color: #d5cea3; /* Taupe accent color */
            border-radius: 4px;
            position: absolute;
            top: -10px;
            left: calc(50% - 10px);
            box-shadow: 0 0 15px rgba(213, 206, 163, 0.8);
            animation: cubeSelfSpin 1.6s ease-in-out infinite;
        }

        /* Keyframes for circular orbital motion */
        @keyframes orbitRotate {
            0% {
                transform: rotate(0deg);
            }
            100% {
                transform: rotate(360deg);
            }
        }

        /* Keyframes for cube rotation and pulse scaling */
        @keyframes cubeSelfSpin {
            0%, 100% {
                transform: rotate(0deg) scale(1);
            }
            50% {
                transform: rotate(180deg) scale(1.25);
                background-color: #3c2a21; /* Espresso highlight */
                box-shadow: 0 0 20px rgba(213, 206, 163, 1);
            }
        }
    </style>
</head>
<body>

    <!-- Circular Cube Loading Overlay -->
    <div id="loadingOverlay" class="d-none position-fixed top-0 start-0 w-100 h-100 flex-column align-items-center justify-content-center">
        <div class="cube-loader-wrapper mb-3">
            <div class="orbit-container">
                <div class="moving-cube"></div>
            </div>
        </div>
        <div class="text-light fw-semibold text-uppercase small tracking-wider">Creating Account...</div>
    </div>

    <div class="container-fluid p-0 vh-100">
        <div class="row g-0 h-100">

            <!-- Left Branding Column -->
            <div class="col-lg-6 hero-panel d-none d-lg-flex flex-column justify-content-between p-5">

                <div>
                    <span class="mini-logo">Tw<span class="accent-dot">.</span></span>
                </div>

                <div class="my-auto pe-lg-4">
                    <h1 class="brand-title text-uppercase mb-3">TimeWise</h1>
                    <p class="brand-tagline text-uppercase mb-0">
                        Less time managing,<br>more time becoming
                    </p>
                </div>

                <div class="hero-footer">
                    &copy; {{ date('Y') }} TimeWise. All rights reserved.
                </div>

            </div>

            <!-- Right Register Form Panel -->
            <div class="col-lg-6 form-panel d-flex align-items-center justify-content-center p-4 p-sm-5">
                <div class="w-100" style="max-width: 420px;">
                    <div class="card border-0 auth-card">
                        <div class="card-body p-4 p-sm-5">

                            <h3 class="fw-bold mb-4 text-espresso">Register</h3>

                            <!-- Validation Errors -->
                            <div
                                id="errorBox"
                                class="alert alert-danger mb-4 py-2 px-3 small rounded-3 align-items-start {{ $errors->any() ? 'd-flex' : 'd-none' }}"
                                role="alert"
                            >
                                <i class="bi bi-exclamation-circle-fill me-2 flex-shrink-0 mt-1"></i>

                                <div id="errorList" class="w-100">
                                    @if($errors->any())
                                        @foreach($errors->all() as $error)
                                            <div class="py-1">{{ $error }}</div>
                                        @endforeach
                                    @endif
                                </div>
                            </div>

                            <!-- Registration Form -->
                            <form method="POST" action="{{ route('register') }}" id="registerForm">
                                @csrf

                                <!-- Name -->
                                <div class="mb-3">
                                    <label for="name" class="form-label text-taupe fw-semibold small text-uppercase">
                                        Name
                                    </label>

                                    <input
                                        type="text"
                                        class="form-control"
                                        id="name"
                                        name="name"
                                        value="{{ old('name') }}"
                                        required
                                        autofocus
                                        autocomplete="name"
                                    >
                                </div>

                                <!-- Email -->
                                <div class="mb-3">
                                    <label for="email" class="form-label text-taupe fw-semibold small text-uppercase">
                                        Email Address
                                    </label>

                                    <input
                                        type="email"
                                        class="form-control"
                                        id="email"
                                        name="email"
                                        value="{{ old('email') }}"
                                        required
                                        autocomplete="username"
                                    >
                                </div>

                                <!-- Password -->
                                <div class="mb-3">
                                    <label for="password" class="form-label text-taupe fw-semibold small text-uppercase">
                                        Password
                                    </label>

                                    <div class="input-group-seamless" id="passwordGroup">
                                        <input
                                            type="password"
                                            class="form-control"
                                            id="password"
                                            name="password"
                                            required
                                            autocomplete="new-password"
                                        >

                                        <button
                                            class="btn-toggle-eye"
                                            type="button"
                                            onclick="togglePasswordVisibility('password', 'toggleIcon1')"
                                            aria-label="Toggle password visibility"
                                        >
                                            <i class="bi bi-eye" id="toggleIcon1"></i>
                                        </button>
                                    </div>
                                </div>

                                <!-- Confirm Password -->
                                <div class="mb-4">
                                    <label for="password_confirmation" class="form-label text-taupe fw-semibold small text-uppercase">
                                        Confirm Password
                                    </label>

                                    <div class="input-group-seamless" id="confirmPasswordGroup">
                                        <input
                                            type="password"
                                            class="form-control"
                                            id="password_confirmation"
                                            name="password_confirmation"
                                            required
                                            autocomplete="new-password"
                                        >

                                        <button
                                            class="btn-toggle-eye"
                                            type="button"
                                            onclick="togglePasswordVisibility('password_confirmation', 'toggleIcon2')"
                                            aria-label="Toggle confirm password visibility"
                                        >
                                            <i class="bi bi-eye" id="toggleIcon2"></i>
                                        </button>
                                    </div>
                                </div>

                                <!-- Submit -->
                                <button type="submit" id="submitBtn" class="btn btn-espresso w-100 py-2 fw-semibold text-uppercase">
                                    Create Account
                                </button>
                            </form>

                            <!-- Login Link -->
                            <div class="mt-4 text-center auth-footer-text small">
                                Already have an account?
                                <a href="{{ route('login') }}" class="auth-action-link">
                                    Login
                                </a>
                            </div>

                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>

    <!-- Bootstrap JS Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <!-- Password Toggle and Client-Side Validation -->
    <script>
        function togglePasswordVisibility(inputId, iconId) {
            const passwordInput = document.getElementById(inputId);
            const toggleIcon = document.getElementById(iconId);

            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                toggleIcon.classList.remove('bi-eye');
                toggleIcon.classList.add('bi-eye-slash');
            } else {
                passwordInput.type = 'password';
                toggleIcon.classList.remove('bi-eye-slash');
                toggleIcon.classList.add('bi-eye');
            }
        }

        const registerForm = document.getElementById('registerForm');
        const passwordInput = document.getElementById('password');
        const confirmPasswordInput = document.getElementById('password_confirmation');
        const errorBox = document.getElementById('errorBox');
        const errorList = document.getElementById('errorList');
        const passwordGroup = document.getElementById('passwordGroup');
        const confirmPasswordGroup = document.getElementById('confirmPasswordGroup');

        // Clear custom error borders when editing passwords
        [passwordInput, confirmPasswordInput].forEach(input => {
            input.addEventListener('input', function () {
                passwordGroup.style.borderColor = '';
                confirmPasswordGroup.style.borderColor = '';
            });
        });

        let isSubmitting = false;

        // Client-side password validation & delayed loading animation
        registerForm.addEventListener('submit', function (e) {
            if (!isSubmitting) {
                const password = passwordInput.value;
                const confirmPassword = confirmPasswordInput.value;
                const errors = [];

                if (password.length < 8) {
                    errors.push('The password must be at least 8 characters.');
                }

                if (password !== confirmPassword) {
                    errors.push('The password confirmation does not match.');
                }

                if (errors.length > 0) {
                    e.preventDefault();

                    // Display errors safely
                    errorList.replaceChildren();

                    errors.forEach(function (message) {
                        const errorMessage = document.createElement('div');
                        errorMessage.className = 'py-1';
                        errorMessage.textContent = message;
                        errorList.appendChild(errorMessage);
                    });

                    errorBox.classList.remove('d-none');
                    errorBox.classList.add('d-flex');

                    if (password.length < 8 || password !== confirmPassword) {
                        passwordGroup.style.borderColor = '#dc3545';
                    }

                    if (password !== confirmPassword) {
                        confirmPasswordGroup.style.borderColor = '#dc3545';
                    }
                } else {
                    // Validation passed: Prevent immediate submit & trigger 1s loading overlay
                    e.preventDefault();
                    isSubmitting = true;

                    const loadingOverlay = document.getElementById('loadingOverlay');
                    loadingOverlay.classList.remove('d-none');
                    loadingOverlay.classList.add('d-flex');

                    errorBox.classList.add('d-none');
                    errorBox.classList.remove('d-flex');
                    errorList.replaceChildren();
                    passwordGroup.style.borderColor = '';
                    confirmPasswordGroup.style.borderColor = '';

                    // Wait 1000ms (1 second) before submitting to backend
                    setTimeout(function () {
                        registerForm.submit();
                    }, 1000);
                }
            }
        });
    </script>

</body>
</html>