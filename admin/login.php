<?php require_once 'auth.php'; ?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>kodanHUB | Neural Access</title>
    <link rel="icon" type="image/svg+xml" href="https://kodan.software/kodan-terminal.svg">
    <link rel="stylesheet" href="css/modern-hub.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.2/gsap.min.js"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
</head>
<body class="login-body">
    <div class="login-card">
        <div class="logo-box" style="margin-bottom: 2rem;">
            <svg viewBox="60 0 380 120" xmlns="http://www.w3.org/2000/svg" class="logo-svg">
                <defs>
                    <filter id="neon-glow" x="-50%" y="-50%" width="200%" height="200%">
                        <feGaussianBlur in="SourceAlpha" stdDeviation="2" result="blur" />
                        <feFlood flood-color="#00FFC2" flood-opacity="0.8" result="color" />
                        <feComposite in="color" in2="blur" operator="in" result="glow" />
                        <feMerge>
                            <feMergeNode in="glow" />
                            <feMergeNode in="SourceGraphic" />
                        </feMerge>
                    </filter>
                </defs>
                <path d="M 100.75 40 L 70.75 60 L 100.75 80" fill="none" stroke="#00FFC2" stroke-width="10" stroke-linecap="round" stroke-linejoin="round" filter="url(#neon-glow)" />
                <text x="234.5" y="60" text-anchor="middle" dominant-baseline="middle" class="kodan-text">kodan</text>
                <path d="M 384.25 30 L 368.25 90" fill="none" stroke="#00FFC2" stroke-width="10" stroke-linecap="round" filter="url(#neon-glow)" />
                <path d="M 399.25 40 L 429.25 60 L 399.25 80" fill="none" stroke="#00FFC2" stroke-width="10" stroke-linecap="round" stroke-linejoin="round" filter="url(#neon-glow)" />
            </svg>
        </div>
        
        <h1 style="color: var(--mint-neon); font-size: 0.8rem; letter-spacing: 4px; text-transform: uppercase; margin-bottom: 2rem; font-weight: 700;">Admin Neural Hub</h1>

        <?php if (isset($error)): ?>
            <div style="background: rgba(255, 77, 77, 0.1); color: #ff4d4d; padding: 10px; border-radius: 8px; margin-bottom: 1.5rem; font-size: 0.75rem; border: 1px solid rgba(255, 77, 77, 0.2);">
                <i data-lucide="alert-triangle" style="width:14px; vertical-align: middle; margin-right: 5px;"></i>
                <?php echo $error; ?>
            </div>
        <?php endif; ?>

        <form action="login.php" method="POST">
            <div class="form-group">
                <label for="password">Contraseña Maestra</label>
                <input type="password" name="password" id="password" required autofocus placeholder="••••••••">
            </div>
            <button type="submit" name="login" class="btn-neural" style="width: 100%; padding: 1rem;">Establecer Conexión</button>
        </form>
        
        <p style="margin-top: 3rem; color: var(--text-muted); font-size: 0.6rem; letter-spacing: 1px; text-transform: uppercase;">
            &copy; 2026 KODAN HUB ENGINEERING
        </p>
    </div>

    <script src="js/neural-ui.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            gsap.from('.login-card', {
                duration: 1.2,
                y: 50,
                opacity: 0,
                ease: "power4.out"
            });
        });
    </script>
</body>
</html>
