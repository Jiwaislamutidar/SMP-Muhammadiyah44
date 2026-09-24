<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login Guru - SMP Muhammadiyah 44</title>
    
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

    <style>
        /* (Biarkan bagian CSS persis sama seperti milikmu sebelumnya, tidak ada yang diubah di sini) */
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: 'Inter', sans-serif; background-color: #075c36; min-height: 100vh; width: 100vw; overflow: hidden; }
        .login-wrapper { position: relative; width: 100%; min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 24px; }
        .subtle-background-ambient-graphic { position: absolute; inset: 0; display: flex; align-items: center; justify-content: center; z-index: 1; pointer-events: none; overflow: hidden; }
        .subtle-background-ambient-graphic svg { width: 100vw; height: 100vh; min-width: 800px; min-height: 800px; }
        .header-top-branding { position: absolute; top: 32px; left: 48px; right: 48px; display: flex; justify-content: space-between; align-items: center; color: rgba(255, 255, 255, 0.7); font-size: 12px; letter-spacing: 1.2px; text-transform: uppercase; z-index: 2; }
        .academic-year-footer { position: absolute; bottom: 24px; left: 0; right: 0; text-align: center; color: rgba(255, 255, 255, 0.6); font-size: 12px; letter-spacing: 1px; text-transform: uppercase; z-index: 2; }
        .center-login-box { position: relative; z-index: 10; width: 100%; max-width: 440px; background: #ffffff; border-radius: 16px; padding: 36px 32px; box-shadow: 0 20px 30px -10px rgba(0, 0, 0, 0.35); }
        .top-heading-section { text-align: center; margin-bottom: 24px; }
        .school-logo-img { width: 54px; height: 54px; object-fit: contain; margin-bottom: 8px; display: block; margin-left: auto; margin-right: auto; border-radius: 12px; background: #f2faf5; padding: 6px; box-shadow: 0 8px 18px rgba(8, 116, 67, 0.12); }
        .text-sub { color: #16a66a; font-size: 12px; font-weight: 700; letter-spacing: 0.8px; text-transform: uppercase; margin-bottom: 4px; }
        .login-title { font-size: 24px; font-weight: 700; color: #17211c; }
        .login-desc { color: #6b756f; font-size: 13px; margin-top: 4px; }
        .form-elements { display: flex; flex-direction: column; gap: 16px; }
        .label { font-size: 13px; font-weight: 600; color: #17211c; margin-bottom: 6px; display: block; }
        .input-field { width: 100%; height: 44px; padding: 0 14px; background: #ffffff; border: 1px solid #e4eae6; border-radius: 8px; font-size: 14px; color: #17211c; outline: none; transition: border-color 0.2s; }
        .input-field:focus { border-color: #087443; }
        .password-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 6px; }
        .forgot-link { color: #087443; font-size: 12px; text-decoration: none; font-weight: 500; }
        .password-wrapper { position: relative; }
        .toggle-password-btn { position: absolute; right: 12px; top: 50%; transform: translateY(-50%); background: none; border: none; cursor: pointer; color: #6b756f; font-size: 18px; display: flex; align-items: center; }
        .remember-me-checkbox { display: flex; align-items: center; gap: 8px; cursor: pointer; }
        .remember-me-checkbox input { accent-color: #087443; width: 16px; height: 16px; cursor: pointer; }
        .label-ingat-saya { font-size: 13px; color: #17211c; }
        .primary-submit-button { width: 100%; height: 46px; background: #087443; color: #ffffff; border: none; border-radius: 8px; font-size: 14px; font-weight: 600; cursor: pointer; transition: background-color 0.2s; margin-top: 4px; }
        .primary-submit-button:hover { background: #065f36; }
        .alert-error { background-color: #fde8e8; border-left: 4px solid #f05252; color: #9b1c1c; padding: 10px; border-radius: 6px; font-size: 13px; margin-bottom: 14px; }
        @media (max-width: 640px) { .header-top-branding { left: 20px; right: 20px; top: 20px; } }
    </style>
</head>
<body>

<div class="login-wrapper">
    <div class="subtle-background-ambient-graphic">
        <svg viewBox="0 0 1000 1000" preserveAspectRatio="none" fill="none" xmlns="http://www.w3.org/2000/svg">
            <path d="M500,0 L570,430 L1000,500 L570,570 L500,1000 L430,570 L0,500 L430,430 Z" fill="#ffffff" fill-opacity="0.06" />
        </svg>
    </div>

    <div class="header-top-branding">
        <div>PORTAL PRESENSI</div>
        <div>SMP MUHAMMADIYAH 44</div>
    </div>

    <div class="center-login-box">
        <div class="top-heading-section">
            <img src="{{ asset('logo.jpg') }}" alt="Logo SMP Muhammadiyah 44" class="school-logo-img">
            <div class="text-sub">SMP MUHAMMADIYAH 44</div>
            <h2 class="login-title">Login Presensi Guru</h2>
            <p class="login-desc">Masuk menggunakan Username & Password Anda.</p>
        </div>

        @if($errors->any())
            <div class="alert-error">
                {{ $errors->first() }}
            </div>
        @endif

        <!-- PERUBAHAN PENTING: Gunakan route('guru.login') agar tidak memanggil controller Siswa -->
        <form action="{{ route('guru.login') }}" method="POST" class="form-elements">
            @csrf

            <div>
                <!-- Label Disesuaikan (Guru biasanya pakai Username/NIP) -->
                <label class="label" for="username">Username / NIP</label>
                <input type="text" id="username" name="username" class="input-field" placeholder="Masukkan Username atau NIP" required />
            </div>

            <div>
                <div class="password-header">
                    <label class="label" for="password" style="margin-bottom: 0;">Kata Sandi</label>
                    <a href="#" class="forgot-link">Lupa sandi?</a>
                </div>
                <div class="password-wrapper">
                    <input type="password" id="password" name="password" class="input-field" placeholder="••••••••••••" required />
                    <button type="button" class="toggle-password-btn" onclick="togglePassword()">
                        <i class="bi bi-eye-slash" id="toggleIcon"></i>
                    </button>
                </div>
            </div>

            <label class="remember-me-checkbox">
                <input type="checkbox" name="remember" />
                <span class="label-ingat-saya">Ingat saya</span>
            </label>

            <button type="submit" class="primary-submit-button">Masuk Ke Portal</button>
        </form>
    </div>

    <div class="academic-year-footer">
        TAHUN AJARAN 2026/2027
    </div>
</div>

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