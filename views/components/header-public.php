<?php
/**
 * Componente de Header Público
 * Para páginas públicas como welcome y home
 */
$currentPath = $_SERVER['REQUEST_URI'] ?? '/';
$currentPath = strtok($currentPath, '?'); // Remover query string
$currentPath = rtrim($currentPath, '/') ?: '/';
?>
<header class="header-public">
    <div class="container">
        <div class="header-content">
            <div class="header-left">
                <button class="menu-toggle-public" id="menuTogglePublic" aria-label="Menú">
                    <span></span>
                    <span></span>
                    <span></span>
                </button>
                <div class="logo">
                    <a href="/" style="text-decoration: none; color: inherit;">
                        <h1>SENAttend</h1>
                    </a>
                </div>
            </div>
            
            <nav class="nav-public" id="mainNavPublic">
                <ul class="nav-menu-public">
                    <li><a href="/" class="nav-link-public <?= $currentPath === '/' ? 'active' : '' ?>">
                        <i class="fas fa-home"></i> Inicio
                    </a></li>
                    <li><a href="/home" class="nav-link-public <?= $currentPath === '/home' ? 'active' : '' ?>">
                        <i class="fas fa-qrcode"></i> Generar QR
                    </a></li>
                    <li><a href="/login" class="btn-login-public">
                        <i class="fas fa-sign-in-alt"></i> Iniciar Sesión
                    </a></li>
                </ul>
            </nav>
        </div>
    </div>
</header>

