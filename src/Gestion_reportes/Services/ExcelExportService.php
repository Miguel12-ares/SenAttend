<?php

namespace App\Gestion_reportes\Services;

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use RuntimeException;

/**
 * Servicio responsable de la generación física de archivos Excel
 * para los reportes de asistencia.
 *
 * - Recibe datos ya procesados por la capa de negocio
 * - Aplica formato (encabezados, estilos básicos)
 * - Ordena datos por nombre de aprendiz (si no vienen ya ordenados)
 * - Guarda el archivo en el directorio /exports
 */
class ExcelExportService
{
    /**
     * Genera un archivo Excel y lo guarda en /exports
     *
     * @param array $rows Filas de datos, cada una como array asociativo
     * @param string $fileName Nombre de archivo (solo nombre, sin ruta)
     * @param array $meta Información adicional para cabecera del reporte
     * @return string Ruta absoluta del archivo generado
     */
    public function generateExcel(array $rows, string $fileName, array $meta = []): string
    {
        if (empty($rows)) {
            throw new RuntimeException('No hay datos para exportar.');
        }

        // Asegurar directorio de exports DENTRO de la raíz pública
        // Para que la URL /exports/... apunte a /public/exports/...
        $exportDir = __DIR__ . '/../../../public/exports';
        if (!is_dir($exportDir) && !mkdir($exportDir, 0775, true) && !is_dir($exportDir)) {
            throw new RuntimeException('No se pudo crear el directorio de exports.');
        }

        // Sanitizar nombre de archivo para evitar path traversal
        $fileName = preg_replace('/[^a-zA-Z0-9_\-\.]/', '_', $fileName);
        $filePath = $exportDir . DIRECTORY_SEPARATOR . $fileName;

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Reporte Asistencia');

        $currentRow = 1;

        // Encabezado del reporte (meta)
        if (!empty($meta)) {
            $sheet->setCellValue("A{$currentRow}", 'Reporte de Asistencia - SENAttend');
            $sheet->mergeCells("A{$currentRow}:G{$currentRow}");
            $sheet->getStyle("A{$currentRow}")->getFont()->setBold(true)->setSize(14);
            $sheet->getStyle("A{$currentRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $currentRow += 2;

            foreach ($meta as $label => $value) {
                $sheet->setCellValue("A{$currentRow}", $label);
                $sheet->setCellValue("B{$currentRow}", $value);
                $sheet->getStyle("A{$currentRow}")->getFont()->setBold(true);
                $currentRow++;
            }

            $currentRow += 2;
        }

        // Encabezados de columnas
        $headers = [
            'A' => 'Documento',
            'B' => 'Nombre Completo',
            'C' => 'Hora de Ingreso',
            'D' => 'Estado Asistencia',
            'E' => 'Ficha',
            'F' => 'Fecha Reporte',
            'G' => 'Instructor',
        ];

        foreach ($headers as $col => $title) {
            $sheet->setCellValue($col . $currentRow, $title);
        }

        $headerRange = "A{$currentRow}:G{$currentRow}";
        $sheet->getStyle($headerRange)->getFont()->setBold(true);
        $sheet->getStyle($headerRange)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle($headerRange)->getFill()->setFillType(Fill::FILL_SOLID)
            ->getStartColor()->setARGB('FFE0E0E0');
        $sheet->getStyle($headerRange)->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);

        $currentRow++;

        // Ordenar datos por nombre de aprendiz si no vienen ordenados
        usort($rows, static function (array $a, array $b): int {
            return strcmp($a['nombre_completo'] ?? '', $b['nombre_completo'] ?? '');
        });

        // Contenido
        foreach ($rows as $row) {
            $sheet->setCellValue("A{$currentRow}", $row['documento'] ?? '');
            $sheet->setCellValue("B{$currentRow}", $row['nombre_completo'] ?? '');
            $sheet->setCellValue("C{$currentRow}", $row['hora_ingreso'] ?? '');
            $sheet->setCellValue("D{$currentRow}", $row['estado_asistencia'] ?? '');
            $sheet->setCellValue("E{$currentRow}", $row['ficha'] ?? '');
            $sheet->setCellValue("F{$currentRow}", $row['fecha_reporte'] ?? '');
            $sheet->setCellValue("G{$currentRow}", $row['instructor'] ?? '');

            $sheet->getStyle("A{$currentRow}:G{$currentRow}")
                ->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);

            $currentRow++;
        }

        // Auto-size columnas
        foreach (array_keys($headers) as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        // Guardar archivo
        $writer = new Xlsx($spreadsheet);
        $writer->save($filePath);

        return realpath($filePath) ?: $filePath;
    }
}


