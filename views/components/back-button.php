<?php
/**
 * Componente reutilizable de botón "Volver"
 * 
 * @param string $url URL a la que redirige (por defecto: /dashboard o página anterior)
 * @param string $text Texto del botón (por defecto: "Volver")
 * @param string $class Clases CSS adicionales
 */
$url = $url ?? null;
$text = $text ?? 'Volver';
$class = $class ?? '';

// Si no se proporciona URL, intentar usar JavaScript para volver atrás
// o redirigir al dashboard según el rol del usuario
if (!$url && isset($user)) {
    $rol = $user['rol'] ?? '';
    switch ($rol) {
        case 'aprendiz':
            $url = '/aprendiz/panel';
            break;
        case 'portero':
            $url = '/portero/panel';
            break;
        case 'instructor':
        case 'coordinador':
        case 'admin':
        case 'administrativo':
            $url = '/dashboard';
            break;
        default:
            $url = '/dashboard';
    }
}

// Si aún no hay URL, usar JavaScript para volver atrás
$onclick = !$url ? 'onclick="window.history.back(); return false;"' : '';
$href = $url ? "href=\"{$url}\"" : 'href="#"';
?>
<a <?= $href ?> <?= $onclick ?> class="btn-back <?= htmlspecialchars($class) ?>">
    <i class="fas fa-arrow-left"></i>
    <?= htmlspecialchars($text) ?>
</a>

