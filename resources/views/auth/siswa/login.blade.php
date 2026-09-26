<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" href="{{ asset('logo.jpg') }}" type="image/jpeg">
    <title>Login Presensi - SMP Muhammadiyah 44</title>
    
    <!-- Icon Bootstrap -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

    <!-- File CSS Siswa -->
    <link rel="stylesheet" href="{{ asset('siswa css/login.css') }}">
</head>
<body>

<div class="login-wrapper">

    <!-- Background Ornamen -->
    <div class="subtle-background-ambient-graphic">
        <svg viewBox="0 0 1000 1000" preserveAspectRatio="none" fill="none" xmlns="http://www.w3.org/2000/svg">
            <path 
                d="M500,0 L570,430 L1000,500 L570,570 L500,1000 L430,570 L0,500 L430,430 Z" 
                fill="#ffffff" 
                fill-opacity="0.06" 
            />
        </svg>
    </div>

    <!-- Header Atas -->
    <div class="header-top-branding">
        <div>PORTAL PRESENSI</div>
        <div>SMP MUHAMMADIYAH 44</div>
    </div>

    <!-- Form Box -->
    <div class="center-login-box">
        
        <div class="top-heading-section">
            <img src="{{ asset('logo.jpg') }}" alt="Logo SMP Muhammadiyah 44" class="school-logo-img">
            <div class="text-sub">SMP MUHAMMADIYAH 44</div>
            <h2 class="login-title">Login Presensi Siswa</h2>
            <p class="login-desc">Masuk menggunakan Username & Password Anda.</p>
        </div>

        <!-- Notif Error -->
        @if(session('error'))
            <div class="alert-error">
                <i class="bi bi-exclamation-circle-fill"></i> {{ session('error') }}
            </div>
        @endif

        <form action="{{ route('login') }}" method="POST" class="form-elements">
            @csrf

            <!-- Username -->
            <div class="floating-field">
                <input 
                    type="text" 
                    id="username"
                    name="username" 
                    class="input-field" 
                    value="{{ old('username') }}"
                    placeholder=" "
                    required
                />
                <label class="label" for="username">Username / NISN</label>
            </div>

            <!-- Password -->
            <div>
                <div class="password-header">
                    <a href="#" class="forgot-link">Lupa sandi?</a>
                </div>
                <div class="password-wrapper floating-field">
                    <input 
                        type="password" 
                        id="password"
                        name="password" 
                        class="input-field" 
                        placeholder=" "
                        required
                    />
                    <label class="label" for="password">Kata Sandi</label>
                    <button type="button" class="toggle-password-btn" onclick="togglePassword()">
                        <i class="bi bi-eye-slash" id="toggleIcon"></i>
                    </button>
                </div>
            </div>

            <!-- Remember Me -->
            <label class="remember-me-checkbox">
                <input type="checkbox" name="remember" />
                <span class="label-ingat-saya">Ingat saya</span>
            </label>

            <!-- Tombol Submit -->
            <button type="submit" class="primary-submit-button">
                Masuk Ke Portal
            </button>
        </form>

    </div>

    <!-- Footer -->
    <div class="academic-year-footer">
        TAHUN AJARAN 2026/2027
    </div>

</div>

<!-- Script Toggle Password -->
<script>
    function togglePassword() {
        const passwordInput = document.getElementById('password');
        const toggleIcon = document.getElementById('toggleIcon');
        
        if (passwordInput.type === 'password') {
            passwordInput.type = 'text';
            toggleIcon.classList.remove('bi-eye-slash');
            toggleIcon.classList.add('bi-eye');
        } else {
            passwordInput.type = 'password';
            toggleIcon.classList.remove('bi-eye');
            toggleIcon.classList.add('bi-eye-slash');
        }
    }
</script>

</body>
</html>