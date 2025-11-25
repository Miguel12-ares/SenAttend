<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SENAttend - Sistema de Asistencia SENA</title>
    <link rel="stylesheet" href="/assets/vendor/fontawesome/css/all.min.css">
    <link rel="stylesheet" href="/css/style.css">
    <link rel="stylesheet" href="/css/components/header-public.css">
    <link rel="stylesheet" href="/css/welcome/welcome.css">
</head>
<body>
    <?php include __DIR__ . '/../components/header-public.php'; ?>
    
    <!-- Hero Section -->
    <section class="hero">
        <div class="hero-content">
            <h1>SENAttend</h1>
            <p class="subtitle">Sistema de Asistencia SENA</p>
            <p class="description">
                Plataforma diseñada para la gestión eficiente y moderna del registro de asistencia 
                de aprendices en el Servicio Nacional de Aprendizaje (SENA). Desarrollada por 
                aprendices del Tecnólogo en Análisis y Desarrollo de Software (ADSO).
            </p>
            <div class="cta-buttons">
                <a href="/login" class="btn-hero btn-hero-primary">
                    <i class="fas fa-sign-in-alt"></i>
                    Iniciar Sesión
                </a>
                <a href="/home" class="btn-hero btn-hero-secondary">
                    <i class="fas fa-qrcode"></i>
                    Generar Código QR
                </a>
            </div>
        </div>
    </section>

    <!-- Features Section -->
    <section class="features">
        <div class="container">
            <h2 class="section-title">Funcionalidades Principales</h2>
            <p class="section-subtitle">
                Descubre las características que hacen de SENAttend la solución ideal para 
                el control de asistencia en el SENA
            </p>
            <div class="features-grid">
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-qrcode"></i>
                    </div>
                    <h3>Registro con QR</h3>
                    <p>
                        Sistema rápido y seguro de registro de asistencia mediante códigos QR 
                        personalizados para cada aprendiz.
                    </p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-users"></i>
                    </div>
                    <h3>Gestión de Aprendices</h3>
                    <p>
                        Administración completa de aprendices, fichas y relaciones entre ellos 
                        de manera eficiente y organizada.
                    </p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-chart-line"></i>
                    </div>
                    <h3>Reportes y Estadísticas</h3>
                    <p>
                        Visualiza reportes detallados y estadísticas en tiempo real sobre 
                        la asistencia de los aprendices.
                    </p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-shield-alt"></i>
                    </div>
                    <h3>Seguridad</h3>
                    <p>
                        Sistema de autenticación robusto con roles y permisos para garantizar 
                        la seguridad de la información.
                    </p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-mobile-alt"></i>
                    </div>
                    <h3>Diseño Responsive</h3>
                    <p>
                        Interfaz adaptativa que funciona perfectamente en dispositivos móviles, 
                        tablets y computadoras.
                    </p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-clock"></i>
                    </div>
                    <h3>Tiempo Real</h3>
                    <p>
                        Registro y actualización de datos en tiempo real para un control 
                        inmediato y preciso de la asistencia.
                    </p>
                </div>
            </div>
        </div>
    </section>

    <!-- About Section -->
    <section class="about">
        <div class="container">
            <div class="about-content">
                <h2 class="section-title">Sobre el Proyecto</h2>
                <p>
                    SENAttend fue diseñado y desarrollado como parte del proceso de formación 
                    de aprendices del Tecnólogo en Análisis y Desarrollo de Software (ADSO) 
                    del Servicio Nacional de Aprendizaje (SENA).
                </p>
                <p>
                    Este sistema busca modernizar y optimizar el proceso de registro de asistencia 
                    en las diferentes sedes del SENA, proporcionando una herramienta eficiente, 
                    segura y fácil de usar tanto para instructores como para aprendices.
                </p>
                <p>
                    La aplicación utiliza tecnologías modernas de desarrollo web siguiendo las 
                    mejores prácticas de programación y arquitectura de software, garantizando 
                    un código limpio, mantenible y escalable.
                </p>
                <div class="about-badge">
                    <i class="fas fa-graduation-cap"></i>
                    Desarrollado por Aprendices ADSO
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="footer">
        <div class="footer-content">
            <div class="footer-links">
                <a href="/home">
                    <i class="fas fa-qrcode"></i>
                    Generar QR
                </a>
                <a href="/login">
                    <i class="fas fa-sign-in-alt"></i>
                    Acceso al Sistema
                </a>
            </div>
            <div class="footer-copyright">
                <p>&copy; <?= date('Y') ?> SENA - Servicio Nacional de Aprendizaje</p>
                <p><strong>SENAttend</strong> - Sistema de Asistencia SENA</p>
            </div>
        </div>
    </footer>

    <script src="/js/app.js"></script>
</body>
</html>

