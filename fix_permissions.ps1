$file = "c:\wamp64\www\SenAttend\config\permissions_config.php"
$content = Get-Content $file -Raw

# Reemplazar ROLE_COORDINADOR por ROLE_ADMINISTRATIVO
$content = $content -replace 'ROLE_COORDINADOR', 'ROLE_ADMINISTRATIVO'

# Guardar el archivo
$content | Set-Content $file -NoNewline

Write-Host "Archivo actualizado exitosamente"
