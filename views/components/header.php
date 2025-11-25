<?php
/**
 * Componente de Header Reutilizable
 * Incluye menú hamburguesa para responsive
 */
$user = $user ?? null;
$currentPage = $currentPage ?? '';
?>
<header class="header">
    <div class="container">
        <div class="header-content">
            <div class="header-left">
                <button class="menu-toggle" id="menuToggle" aria-label="Menú">
                    <span></span>
                    <span></span>
                    <span></span>
                </button>
                <div class="logo">
                    <h1>SENAttend</h1>
                </div>
                
                <?php if ($user): ?>
                <div class="nav-user-mobile">
                    <a href="/perfil" class="user-icon-link" title="Mi Perfil">
                        <i class="fas fa-user-circle"></i>
                    </a>
                    <a href="/auth/logout" class="logout-icon-link" title="Cerrar Sesión">
                        <i class="fas fa-sign-out-alt"></i>
                    </a>
                </div>
                <?php endif; ?>
            </div>
            
            <?php if ($user): ?>
            <nav class="nav" id="mainNav">
                <ul class="nav-menu">
                    <?php if ($user['rol'] === 'admin'): ?>
                        <li><a href="/dashboard" class="<?= $currentPage === 'dashboard' ? 'active' : '' ?>">
                            <i class="fas fa-home"></i> Dashboard
                        </a></li>
                        <li><a href="/fichas" class="<?= $currentPage === 'fichas' ? 'active' : '' ?>">
                            <i class="fas fa-clipboard-list"></i> Fichas
                        </a></li>
                        <li><a href="/aprendices" class="<?= $currentPage === 'aprendices' ? 'active' : '' ?>">
                            <i class="fas fa-users"></i> Aprendices
                        </a></li>
                    <?php elseif (in_array($user['rol'], ['instructor', 'coordinador'])): ?>
                        <li><a href="/dashboard" class="<?= $currentPage === 'dashboard' ? 'active' : '' ?>">
                            <i class="fas fa-home"></i> Dashboard
                        </a></li>
                        <li><a href="/asistencia/registrar" class="<?= $currentPage === 'asistencia' ? 'active' : '' ?>">
                            <i class="fas fa-check"></i> Registrar Asistencia
                        </a></li>
                        <li><a href="/qr/escanear" class="<?= $currentPage === 'qr-escanear' ? 'active' : '' ?>">
                            <i class="fas fa-camera"></i> Escanear QR
                        </a></li>
                    <?php endif; ?>
                </ul>
                
                <div class="nav-user">
                    <a href="/perfil" class="user-icon-link" title="Mi Perfil">
                        <i class="fas fa-user-circle"></i>
                    </a>
                    <a href="/auth/logout" class="logout-icon-link" title="Cerrar Sesión">
                        <i class="fas fa-sign-out-alt"></i>
                    </a>
                </div>
            </nav>
            <?php endif; ?>
        </div>
    </div>
</header>

