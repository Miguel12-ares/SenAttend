<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Estadísticas de Asistencia - SenAttend</title>
    <?php include __DIR__ . '/../layouts/base.php'; ?>
    <?php asset_css('dashboard.css'); ?>
    <?php asset_css('components.css'); ?>
    <style>
        .estadisticas-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }

        .estadisticas-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            border-radius: 10px;
            margin-bottom: 30px;
            text-align: center;
        }

        .estadisticas-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .estadistica-card {
            background: white;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            padding: 20px;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }

        .estadistica-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 15px rgba(0, 0, 0, 0.2);
        }

        .card-icon {
            font-size: 3rem;
            margin-bottom: 15px;
            display: block;
        }

        .card-title {
            font-size: 1.2rem;
            font-weight: bold;
            margin-bottom: 10px;
            color: #333;
        }

        .card-description {
            color: #666;
            margin-bottom: 15px;
        }

        .card-button {
            background: #667eea;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 5px;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            transition: background 0.3s ease;
        }

        .card-button:hover {
            background: #5a6fd8;
        }

        .filtros-section {
            background: white;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            padding: 20px;
            margin-bottom: 30px;
        }

        .filtros-form {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            align-items: end;
        }

        .form-group {
            display: flex;
            flex-direction: column;
        }

        .form-group label {
            margin-bottom: 5px;
            font-weight: bold;
            color: #333;
        }

        .form-group select,
        .form-group input {
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 1rem;
        }

        .btn-primary {
            background: #28a745;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 5px;
            cursor: pointer;
            font-size: 1rem;
            transition: background 0.3s ease;
        }

        .btn-primary:hover {
            background: #218838;
        }

        .resultados-section {
            background: white;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            padding: 20px;
        }

        .loading {
            text-align: center;
            padding: 40px;
        }

        .spinner {
            border: 4px solid #f3f3f3;
            border-top: 4px solid #667eea;
            border-radius: 50%;
            width: 40px;
            height: 40px;
            animation: spin 1s linear infinite;
            margin: 0 auto 20px;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        .error-message {
            background: #f8d7da;
            color: #721c24;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
            border: 1px solid #f5c6cb;
        }

        .chart-container {
            margin-top: 20px;
            height: 400px;
        }
    </style>
</head>
<body>
    <?php include __DIR__ . '/../components/header.php'; ?>

    <div class="estadisticas-container">
        <div class="estadisticas-header">
            <h1><i class="fas fa-chart-bar"></i> Estadísticas de Asistencia</h1>
            <p>Analiza el rendimiento de asistencia de aprendices y fichas formativas</p>
        </div>

        <?php if (isset($_SESSION['errors']) && !empty($_SESSION['errors'])): ?>
            <div class="error-message">
                <i class="fas fa-exclamation-triangle"></i>
                <?php foreach ($_SESSION['errors'] as $error): ?>
                    <div><?php echo htmlspecialchars($error); ?></div>
                <?php endforeach; ?>
            </div>
            <?php unset($_SESSION['errors']); ?>
        <?php endif; ?>

        <!-- Menú de opciones principales -->
        <div class="estadisticas-grid">
            <div class="estadistica-card">
                <i class="fas fa-user-graduate card-icon" style="color: #28a745;"></i>
                <div class="card-title">Estadísticas por Aprendiz</div>
                <div class="card-description">
                    Analiza la asistencia individual de uno o varios aprendices con métricas detalladas.
                </div>
                <button class="card-button" onclick="mostrarVistaAprendiz()">
                    <i class="fas fa-user"></i> Ver Estadísticas
                </button>
            </div>

            <div class="estadistica-card">
                <i class="fas fa-users card-icon" style="color: #007bff;"></i>
                <div class="card-title">Estadísticas por Ficha</div>
                <div class="card-description">
                    Visualiza estadísticas agregadas de toda una ficha formativa y compara aprendices.
                </div>
                <button class="card-button" onclick="mostrarVistaFicha()">
                    <i class="fas fa-folder"></i> Ver Estadísticas
                </button>
            </div>

            <div class="estadistica-card">
                <i class="fas fa-flag card-icon" style="color: #dc3545;"></i>
                <div class="card-title">Reportes por Analizar</div>
                <div class="card-description">
                    Casos marcados como críticos que requieren atención especial del coordinador.
                </div>
                <button class="card-button" onclick="mostrarVistaReportes()">
                    <i class="fas fa-exclamation-triangle"></i> Ver Reportes
                </button>
            </div>

            <div class="estadistica-card">
                <i class="fas fa-download card-icon" style="color: #6f42c1;"></i>
                <div class="card-title">Exportar Datos</div>
                <div class="card-description">
                    Descarga datos tabulares en formato CSV para análisis externos.
                </div>
                <button class="card-button" onclick="mostrarVistaExportar()">
                    <i class="fas fa-file-csv"></i> Exportar CSV
                </button>
            </div>
        </div>

        <!-- Sección de filtros y resultados -->
        <div id="filtros-resultados" style="display: none;">
            <div class="filtros-section">
                <h3><i class="fas fa-filter"></i> Filtros de Búsqueda</h3>
                <form id="filtrosForm" class="filtros-form">
                    <div class="form-group">
                        <label for="fecha_desde">Fecha Desde:</label>
                        <input type="date" id="fecha_desde" name="fecha_desde">
                    </div>

                    <div class="form-group">
                        <label for="fecha_hasta">Fecha Hasta:</label>
                        <input type="date" id="fecha_hasta" name="fecha_hasta">
                    </div>

                    <div class="form-group">
                        <label for="ficha">Ficha:</label>
                        <select id="ficha" name="ficha">
                            <option value="">Todas las fichas</option>
                            <?php foreach ($fichas as $ficha): ?>
                                <option value="<?php echo $ficha['id']; ?>">
                                    <?php echo htmlspecialchars($ficha['numero_ficha'] . ' - ' . $ficha['nombre']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group" id="aprendizGroup" style="display: none;">
                        <label for="aprendiz">Aprendiz:</label>
                        <select id="aprendiz" name="aprendiz">
                            <option value="">Seleccionar aprendiz...</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <button type="button" class="btn-primary" onclick="aplicarFiltros()">
                            <i class="fas fa-search"></i> Buscar
                        </button>
                    </div>
                </form>
            </div>

            <div class="resultados-section">
                <div id="loading" class="loading" style="display: none;">
                    <div class="spinner"></div>
                    <p>Cargando estadísticas...</p>
                </div>

                <div id="resultados">
                    <div style="text-align: center; padding: 40px; color: #666;">
                        <i class="fas fa-info-circle" style="font-size: 3rem; margin-bottom: 20px;"></i>
                        <h3>Selecciona una opción</h3>
                        <p>Elige el tipo de estadísticas que deseas visualizar y configura los filtros necesarios.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        let vistaActual = null;

        function mostrarVistaAprendiz() {
            vistaActual = 'aprendiz';
            document.getElementById('filtros-resultados').style.display = 'block';
            document.getElementById('aprendizGroup').style.display = 'block';
            document.getElementById('resultados').innerHTML = `
                <div style="text-align: center; padding: 40px; color: #666;">
                    <i class="fas fa-user-graduate" style="font-size: 3rem; margin-bottom: 20px; color: #28a745;"></i>
                    <h3>Estadísticas por Aprendiz</h3>
                    <p>Selecciona un aprendiz y configura las fechas para ver sus estadísticas detalladas.</p>
                </div>
            `;
            cargarAprendices();
        }

        function mostrarVistaFicha() {
            vistaActual = 'ficha';
            document.getElementById('filtros-resultados').style.display = 'block';
            document.getElementById('aprendizGroup').style.display = 'none';
            document.getElementById('resultados').innerHTML = `
                <div style="text-align: center; padding: 40px; color: #666;">
                    <i class="fas fa-users" style="font-size: 3rem; margin-bottom: 20px; color: #007bff;"></i>
                    <h3>Estadísticas por Ficha</h3>
                    <p>Selecciona una ficha y configura las fechas para ver estadísticas agregadas.</p>
                </div>
            `;
        }

        function mostrarVistaReportes() {
            vistaActual = 'reportes';
            document.getElementById('filtros-resultados').style.display = 'block';
            document.getElementById('aprendizGroup').style.display = 'none';
            aplicarFiltros();
        }

        function mostrarVistaExportar() {
            vistaActual = 'exportar';
            document.getElementById('filtros-resultados').style.display = 'block';
            document.getElementById('aprendizGroup').style.display = 'none';
            document.getElementById('resultados').innerHTML = `
                <div style="text-align: center; padding: 40px; color: #666;">
                    <i class="fas fa-download" style="font-size: 3rem; margin-bottom: 20px; color: #6f42c1;"></i>
                    <h3>Exportar Datos CSV</h3>
                    <p>Configura los filtros y haz clic en "Buscar" para generar el archivo CSV.</p>
                </div>
            `;
        }

        function cargarAprendices() {
            const fichaId = document.getElementById('ficha').value;
            if (!fichaId) return;

            fetch(`/api/fichas/${fichaId}/aprendices`)
                .then(response => response.json())
                .then(data => {
                    const select = document.getElementById('aprendiz');
                    select.innerHTML = '<option value="">Seleccionar aprendiz...</option>';

                    if (data.success && data.data) {
                        data.data.forEach(aprendiz => {
                            const option = document.createElement('option');
                            option.value = aprendiz.id_aprendiz;
                            option.textContent = `${aprendiz.nombre} ${aprendiz.apellido}`;
                            select.appendChild(option);
                        });
                    }
                })
                .catch(error => {
                    console.error('Error cargando aprendices:', error);
                });
        }

        function aplicarFiltros() {
            if (!vistaActual) return;

            const loading = document.getElementById('loading');
            const resultados = document.getElementById('resultados');

            loading.style.display = 'block';
            resultados.innerHTML = '';

            const filtros = {
                fecha_desde: document.getElementById('fecha_desde').value,
                fecha_hasta: document.getElementById('fecha_hasta').value,
                id_ficha: document.getElementById('ficha').value,
                id_aprendiz: document.getElementById('aprendiz').value
            };

            let url = '';
            let method = 'GET';

            switch (vistaActual) {
                case 'aprendiz':
                    url = '/api/estadisticas/aprendiz';
                    break;
                case 'ficha':
                    url = '/api/estadisticas/ficha';
                    break;
                case 'reportes':
                    url = '/api/estadisticas/reportes';
                    break;
                case 'exportar':
                    url = '/api/estadisticas/exportar';
                    method = 'GET';
                    break;
            }

            // Construir query string
            const queryParams = new URLSearchParams();
            Object.keys(filtros).forEach(key => {
                if (filtros[key]) {
                    queryParams.append(key, filtros[key]);
                }
            });

            if (vistaActual === 'exportar') {
                queryParams.append('tipo', 'aprendiz'); // Por defecto
            }

            const finalUrl = url + (queryParams.toString() ? '?' + queryParams.toString() : '');

            fetch(finalUrl, {
                method: method,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(response => response.json())
            .then(data => {
                loading.style.display = 'none';

                if (data.success) {
                    mostrarResultados(data.data, vistaActual);
                } else {
                    resultados.innerHTML = `
                        <div class="error-message">
                            <i class="fas fa-exclamation-triangle"></i>
                            Error: ${data.message || 'Error desconocido'}
                        </div>
                    `;
                }
            })
            .catch(error => {
                loading.style.display = 'none';
                console.error('Error:', error);
                resultados.innerHTML = `
                    <div class="error-message">
                        <i class="fas fa-exclamation-triangle"></i>
                        Error de conexión. Por favor, intenta nuevamente.
                    </div>
                `;
            });
        }

        function mostrarResultados(data, tipo) {
            const resultados = document.getElementById('resultados');

            if (tipo === 'exportar') {
                resultados.innerHTML = `
                    <h3><i class="fas fa-check-circle" style="color: #28a745;"></i> Datos listos para exportar</h3>
                    <p>Se encontraron ${data.length} registros. Los datos están listos para descargar.</p>
                    <button class="btn-primary" onclick="descargarCSV(data)">
                        <i class="fas fa-download"></i> Descargar CSV
                    </button>
                `;
                return;
            }

            // Mostrar estadísticas en formato legible
            let html = '<h3>Resultados</h3>';

            if (tipo === 'aprendiz') {
                html += generarHTMLAprendiz(data);
            } else if (tipo === 'ficha') {
                html += generarHTMLFicha(data);
            } else if (tipo === 'reportes') {
                html += generarHTMLReportes(data);
            }

            resultados.innerHTML = html;
        }

        function generarHTMLAprendiz(data) {
            return `
                <div class="estadisticas-grid">
                    <div class="estadistica-card">
                        <h4>Asistencia General</h4>
                        <p><strong>Total asistencias:</strong> ${data.total_asistencias}</p>
                        <p><strong>Porcentaje asistencia:</strong> ${data.porcentaje_asistencia}%</p>
                        <p><strong>Porcentaje inasistencia:</strong> ${data.porcentaje_inasistencia}%</p>
                    </div>
                    <div class="estadistica-card">
                        <h4>Inasistencias</h4>
                        <p><strong>Total inasistencias:</strong> ${data.total_inasistencias}</p>
                        <p><strong>Con excusa:</strong> ${data.total_inasistencias_con_excusa}</p>
                        <p><strong>Frecuencia promedio:</strong> ${data.frecuencia_inasistencia_dias_promedio || 'N/A'} días</p>
                    </div>
                    <div class="estadistica-card">
                        <h4>Tardanzas</h4>
                        <p><strong>Total tardanzas:</strong> ${data.total_tardanzas}</p>
                    </div>
                    <div class="estadistica-card">
                        <h4>Alertas</h4>
                        <p><strong>Reporte por analizar:</strong> ${data.flags.reporte_por_analizar ? 'Sí' : 'No'}</p>
                        ${data.flags.motivos.length > 0 ? '<p><strong>Motivos:</strong> ' + data.flags.motivos.join(', ') + '</p>' : ''}
                    </div>
                </div>
            `;
        }

        function generarHTMLFicha(data) {
            return `
                <div class="estadisticas-grid">
                    <div class="estadistica-card">
                        <h4>Estadísticas Generales</h4>
                        <p><strong>Total registros:</strong> ${data.totales.total_registros}</p>
                        <p><strong>Presentes:</strong> ${data.totales.presentes}</p>
                        <p><strong>Ausentes:</strong> ${data.totales.ausentes}</p>
                        <p><strong>Tardanzas:</strong> ${data.totales.tardanzas}</p>
                    </div>
                    <div class="estadistica-card">
                        <h4>Porcentajes</h4>
                        <p><strong>Asistencia:</strong> ${data.porcentajes.asistencia}%</p>
                        <p><strong>Inasistencia:</strong> ${data.porcentajes.inasistencia}%</p>
                        <p><strong>Tardanza:</strong> ${data.porcentajes.tardanza}%</p>
                    </div>
                </div>
                ${data.top_inasistentes.length > 0 ? `
                    <h4>Top 5 Aprendices con más Inasistencias</h4>
                    <table style="width: 100%; border-collapse: collapse; margin-top: 20px;">
                        <thead>
                            <tr style="background: #f8f9fa;">
                                <th style="padding: 10px; border: 1px solid #ddd;">Nombre</th>
                                <th style="padding: 10px; border: 1px solid #ddd;">Total Inasistencias</th>
                                <th style="padding: 10px; border: 1px solid #ddd;">Con Excusa</th>
                            </tr>
                        </thead>
                        <tbody>
                            ${data.top_inasistentes.map(aprendiz => `
                                <tr>
                                    <td style="padding: 10px; border: 1px solid #ddd;">${aprendiz.nombre} ${aprendiz.apellido}</td>
                                    <td style="padding: 10px; border: 1px solid #ddd;">${aprendiz.total_inasistencias}</td>
                                    <td style="padding: 10px; border: 1px solid #ddd;">${aprendiz.inasistencias_con_excusa}</td>
                                </tr>
                            `).join('')}
                        </tbody>
                    </table>
                ` : ''}
            `;
        }

        function generarHTMLReportes(data) {
            if (data.length === 0) {
                return '<p>No se encontraron casos para reportar.</p>';
            }

            return `
                <p>Se encontraron ${data.length} casos que requieren atención:</p>
                <table style="width: 100%; border-collapse: collapse; margin-top: 20px;">
                    <thead>
                        <tr style="background: #f8f9fa;">
                            <th style="padding: 10px; border: 1px solid #ddd;">Aprendiz</th>
                            <th style="padding: 10px; border: 1px solid #ddd;">Ficha</th>
                            <th style="padding: 10px; border: 1px solid #ddd;">Fecha</th>
                            <th style="padding: 10px; border: 1px solid #ddd;">Estado</th>
                            <th style="padding: 10px; border: 1px solid #ddd;">Total Inasistencias</th>
                        </tr>
                    </thead>
                    <tbody>
                        ${data.map(reporte => `
                            <tr>
                                <td style="padding: 10px; border: 1px solid #ddd;">${reporte.nombre} ${reporte.apellido}</td>
                                <td style="padding: 10px; border: 1px solid #ddd;">${reporte.numero_ficha}</td>
                                <td style="padding: 10px; border: 1px solid #ddd;">${reporte.fecha}</td>
                                <td style="padding: 10px; border: 1px solid #ddd;">${reporte.asistencia_estado}</td>
                                <td style="padding: 10px; border: 1px solid #ddd;">${reporte.total_inasistencias_aprendiz}</td>
                            </tr>
                        `).join('')}
                    </tbody>
                </table>
            `;
        }

        function descargarCSV(data) {
            if (!data || data.length === 0) {
                alert('No hay datos para descargar');
                return;
            }

            // Convertir a CSV
            const headers = Object.keys(data[0]);
            const csvContent = [
                headers.join(','),
                ...data.map(row => headers.map(header => `"${row[header] || ''}"`).join(','))
            ].join('\n');

            // Crear y descargar archivo
            const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
            const link = document.createElement('a');
            link.href = URL.createObjectURL(blob);
            link.download = `estadisticas_${vistaActual}_${new Date().toISOString().split('T')[0]}.csv`;
            link.click();
        }

        // Event listener para cambio de ficha
        document.getElementById('ficha').addEventListener('change', cargarAprendices);

        // Set default dates
        const today = new Date();
        const lastMonth = new Date();
        lastMonth.setMonth(today.getMonth() - 1);

        document.getElementById('fecha_desde').value = lastMonth.toISOString().split('T')[0];
        document.getElementById('fecha_hasta').value = today.toISOString().split('T')[0];
    </script>
</body>
</html>
