<?php
/**
 * Junxtion Restaurant - Landing Page
 * Beautiful, elegant restaurant experience
 */

$configPath = __DIR__ . '/../private/config.php';
$config = file_exists($configPath) ? require $configPath : [];

$baseUrl = $config['app']['base_url'] ?? 'https://junxtionapp.co.za';
$appName = $config['app']['name'] ?? 'Junxtion';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no, viewport-fit=cover">
    <meta name="theme-color" content="#1A1A1A">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="description" content="<?= htmlspecialchars($appName) ?> - Experience exceptional dining. Order your favorites for pickup or delivery.">

    <title><?= htmlspecialchars($appName) ?> - Fine Dining Experience</title>

    <!-- PWA Manifest -->
    <link rel="manifest" href="/pwa/manifest.json">

    <!-- Favicon -->
    <link rel="icon" type="image/svg+xml" href="/favicon.svg">
    <link rel="apple-touch-icon" href="/assets/images/icon.svg">

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Playfair+Display:wght@400;500;600;700&display=swap" rel="stylesheet">

    <!-- Styles -->
    <link rel="stylesheet" href="/assets/css/app.css">

    <style>
        /* Landing Page Specific Styles */
        .landing-page {
            background: var(--dark);
        }

        .particles {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            overflow: hidden;
            z-index: 0;
        }

        .particle {
            position: absolute;
            width: 4px;
            height: 4px;
            background: var(--accent);
            border-radius: 50%;
            opacity: 0.3;
            animation: float 15s infinite ease-in-out;
        }

        @keyframes float {
            0%, 100% { transform: translateY(0) rotate(0deg); opacity: 0.3; }
            50% { transform: translateY(-100px) rotate(180deg); opacity: 0.6; }
        }

        .hero-image {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><defs><radialGradient id="g" cx="50%" cy="30%"><stop offset="0%" stop-color="%23C8102E" stop-opacity="0.15"/><stop offset="100%" stop-color="%231A1A1A" stop-opacity="0"/></radialGradient></defs><rect fill="url(%23g)" width="100" height="100"/></svg>');
            background-size: cover;
            z-index: 0;
        }

        .hero-glow {
            position: absolute;
            top: 20%;
            left: 50%;
            transform: translateX(-50%);
            width: 300px;
            height: 300px;
            background: radial-gradient(circle, rgba(200, 16, 46, 0.2) 0%, transparent 70%);
            border-radius: 50%;
            filter: blur(40px);
            z-index: 0;
        }

        .logo-container {
            position: relative;
            margin-bottom: 2rem;
        }

        .logo-ring {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            width: 100px;
            height: 100px;
            border: 2px solid var(--accent);
            border-radius: 50%;
            opacity: 0.3;
            animation: pulse-ring 3s infinite ease-out;
        }

        @keyframes pulse-ring {
            0% { transform: translate(-50%, -50%) scale(1); opacity: 0.3; }
            100% { transform: translate(-50%, -50%) scale(1.5); opacity: 0; }
        }

        .hero-logo {
            position: relative;
            z-index: 1;
            width: 80px;
            height: 80px;
            margin: 0 auto;
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            border-radius: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 0 60px rgba(200, 16, 46, 0.5);
        }

        .hero-logo-text {
            font-family: var(--font-display);
            font-size: 2rem;
            font-weight: 700;
            color: white;
        }

        .divider {
            width: 60px;
            height: 2px;
            background: linear-gradient(90deg, transparent, var(--accent), transparent);
            margin: 0 auto 1.5rem;
        }

        .scroll-indicator {
            position: absolute;
            bottom: 30px;
            left: 50%;
            transform: translateX(-50%);
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 8px;
            color: var(--gray-500);
            font-size: 12px;
            animation: bounce 2s infinite;
            cursor: pointer;
        }

        @keyframes bounce {
            0%, 100% { transform: translateX(-50%) translateY(0); }
            50% { transform: translateX(-50%) translateY(10px); }
        }

        .scroll-indicator svg {
            width: 24px;
            height: 24px;
            opacity: 0.5;
        }

        /* Feature Cards */
        .features-section {
            padding: 3rem 1.5rem;
            background: var(--cream);
        }

        .feature-card {
            display: flex;
            align-items: flex-start;
            gap: 1rem;
            padding: 1.25rem;
            background: var(--warm-white);
            border-radius: 16px;
            margin-bottom: 1rem;
            box-shadow: var(--shadow-sm);
            border: 1px solid rgba(0,0,0,0.03);
        }

        .feature-icon {
            width: 48px;
            height: 48px;
            flex-shrink: 0;
            background: linear-gradient(135deg, var(--primary-glow) 0%, transparent 100%);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--primary);
        }

        .feature-content h4 {
            font-family: var(--font-display);
            font-size: 1rem;
            font-weight: 600;
            margin-bottom: 4px;
        }

        .feature-content p {
            font-size: 0.875rem;
            color: var(--gray-500);
            line-height: 1.5;
        }

        /* Bottom Fixed CTA */
        .bottom-cta {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            padding: 1rem 1.5rem calc(1rem + env(safe-area-inset-bottom));
            background: linear-gradient(180deg, transparent 0%, var(--dark) 30%);
            z-index: 100;
        }

        .bottom-cta .btn {
            width: 100%;
            max-width: 400px;
            margin: 0 auto;
            display: flex;
        }

        /* Responsive - Tablet */
        @media (min-width: 768px) {
            .hero-glow {
                width: 400px;
                height: 400px;
            }

            .hero-logo {
                width: 100px;
                height: 100px;
                border-radius: 24px;
            }

            .hero-logo-text {
                font-size: 2.5rem;
            }

            .logo-ring {
                width: 130px;
                height: 130px;
            }

            .features-section {
                padding: 4rem 2rem;
                max-width: 600px;
                margin: 0 auto;
            }

            .feature-card {
                padding: 1.5rem;
            }

            .feature-icon {
                width: 56px;
                height: 56px;
            }

            .feature-content h4 {
                font-size: 1.125rem;
            }

            .bottom-cta {
                max-width: 600px;
                left: 50%;
                transform: translateX(-50%);
            }
        }

        /* Responsive - Desktop */
        @media (min-width: 1024px) {
            .hero-glow {
                width: 500px;
                height: 500px;
            }

            .hero-logo {
                width: 120px;
                height: 120px;
            }

            .hero-logo-text {
                font-size: 3rem;
            }

            .logo-ring {
                width: 150px;
                height: 150px;
            }

            .features-section {
                max-width: 900px;
                display: grid;
                grid-template-columns: repeat(2, 1fr);
                gap: 1.5rem;
            }

            .features-section h2,
            .features-section > p:first-of-type {
                grid-column: 1 / -1;
            }

            .feature-card {
                margin-bottom: 0;
            }

            .bottom-cta {
                max-width: 700px;
            }

            .bottom-cta .btn {
                max-width: 500px;
            }
        }

        /* Responsive - Large Desktop */
        @media (min-width: 1280px) {
            .features-section {
                max-width: 1100px;
                grid-template-columns: repeat(4, 1fr);
            }

            .features-section h2,
            .features-section > p:first-of-type {
                grid-column: 1 / -1;
            }

            .feature-card {
                flex-direction: column;
                text-align: center;
            }

            .feature-icon {
                margin: 0 auto 1rem;
            }
        }
    </style>
</head>
<body>
    <div class="landing-page">
        <!-- Hero Section -->
        <section class="hero-section">
            <!-- Background Effects -->
            <div class="hero-image"></div>
            <div class="hero-glow"></div>
            <div class="particles" id="particles"></div>

            <!-- Content -->
            <div class="hero-content">
                <!-- Badge -->
                <div class="hero-badge animate-fadeInDown">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor">
                        <polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/>
                    </svg>
                    Premium Dining Experience
                </div>

                <!-- Logo -->
                <div class="logo-container">
                    <div class="logo-ring"></div>
                    <div class="hero-logo">
                        <span class="hero-logo-text">J</span>
                    </div>
                </div>

                <!-- Title -->
                <h1 class="hero-title">
                    Welcome to<br><span><?= htmlspecialchars($appName) ?></span>
                </h1>

                <div class="divider"></div>

                <!-- Subtitle -->
                <p class="hero-subtitle">
                    Experience exceptional flavors crafted with passion.
                    Order your favorites for pickup or delivery.
                </p>

                <!-- CTA Buttons -->
                <div class="hero-cta">
                    <a href="/app/home.php" class="btn btn-primary btn-lg btn-block">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M4 6h16M4 12h16M4 18h16"/>
                        </svg>
                        View Menu
                    </a>
                    <a href="/app/home.php" class="btn btn-dark btn-lg btn-block">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/>
                            <circle cx="12" cy="7" r="4"/>
                        </svg>
                        Sign In
                    </a>
                </div>

                <!-- Features -->
                <div class="hero-features">
                    <div class="hero-feature">
                        <div class="hero-feature-icon">
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <circle cx="12" cy="12" r="10"/>
                                <polyline points="12 6 12 12 16 14"/>
                            </svg>
                        </div>
                        <div class="hero-feature-text">Fast Delivery</div>
                    </div>
                    <div class="hero-feature">
                        <div class="hero-feature-icon">
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>
                            </svg>
                        </div>
                        <div class="hero-feature-text">Fresh Quality</div>
                    </div>
                    <div class="hero-feature">
                        <div class="hero-feature-icon">
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/>
                            </svg>
                        </div>
                        <div class="hero-feature-text">Top Rated</div>
                    </div>
                </div>
            </div>

            <!-- Scroll Indicator -->
            <div class="scroll-indicator" onclick="scrollToFeatures()">
                <span>Learn More</span>
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <polyline points="6 9 12 15 18 9"/>
                </svg>
            </div>
        </section>

        <!-- Features Section -->
        <section class="features-section" id="features">
            <h2 style="font-family: var(--font-display); font-size: 1.5rem; text-align: center; margin-bottom: 0.5rem;">Why Choose Us</h2>
            <p style="text-align: center; color: var(--gray-500); margin-bottom: 2rem; font-size: 0.875rem;">Experience the difference</p>

            <div class="feature-card animate-fadeInUp">
                <div class="feature-icon">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <rect x="1" y="3" width="15" height="13"/>
                        <polygon points="16 8 20 8 23 11 23 16 16 16 16 8"/>
                        <circle cx="5.5" cy="18.5" r="2.5"/>
                        <circle cx="18.5" cy="18.5" r="2.5"/>
                    </svg>
                </div>
                <div class="feature-content">
                    <h4>Fast Delivery</h4>
                    <p>Get your food delivered hot and fresh to your doorstep in minutes</p>
                </div>
            </div>

            <div class="feature-card animate-fadeInUp stagger-1">
                <div class="feature-icon">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/>
                        <polyline points="9 22 9 12 15 12 15 22"/>
                    </svg>
                </div>
                <div class="feature-content">
                    <h4>Easy Pickup</h4>
                    <p>Skip the queue with our convenient pickup service</p>
                </div>
            </div>

            <div class="feature-card animate-fadeInUp stagger-2">
                <div class="feature-icon">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>
                    </svg>
                </div>
                <div class="feature-content">
                    <h4>Quality Ingredients</h4>
                    <p>We use only the freshest, locally-sourced ingredients</p>
                </div>
            </div>

            <div class="feature-card animate-fadeInUp stagger-3">
                <div class="feature-icon">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/>
                    </svg>
                </div>
                <div class="feature-content">
                    <h4>Made with Love</h4>
                    <p>Every dish is prepared with care and passion by our expert chefs</p>
                </div>
            </div>

            <!-- Spacer for bottom CTA -->
            <div style="height: 100px;"></div>
        </section>

        <!-- Bottom CTA -->
        <div class="bottom-cta">
            <a href="/app/home.php" class="btn btn-gold btn-lg">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M4 6h16M4 12h16M4 18h16"/>
                </svg>
                Start Ordering Now
            </a>
        </div>
    </div>

    <script>
        // Create floating particles
        function createParticles() {
            const container = document.getElementById('particles');
            for (let i = 0; i < 20; i++) {
                const particle = document.createElement('div');
                particle.className = 'particle';
                particle.style.left = Math.random() * 100 + '%';
                particle.style.top = Math.random() * 100 + '%';
                particle.style.animationDelay = Math.random() * 15 + 's';
                particle.style.animationDuration = (10 + Math.random() * 10) + 's';
                container.appendChild(particle);
            }
        }

        // Scroll to features
        function scrollToFeatures() {
            document.getElementById('features').scrollIntoView({ behavior: 'smooth' });
        }

        // Initialize
        createParticles();

        // Service Worker Registration
        if ('serviceWorker' in navigator) {
            window.addEventListener('load', function() {
                navigator.serviceWorker.register('/pwa/service-worker.js')
                    .then(function(registration) {
                        console.log('SW registered:', registration.scope);
                    })
                    .catch(function(error) {
                        console.log('SW registration failed:', error);
                    });
            });
        }
    </script>
</body>
</html>
