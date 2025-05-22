<?php

class WP_Control_Acceso_Registros {
    private $table_name;

    public function __construct() {
        global $wpdb;
        $this->table_name = $wpdb->prefix . 'control_acceso_registros';
    }

    /**
     * Obtener registros del día actual
     */
    public function get_registros_hoy() {
        global $wpdb; // Ensure $wpdb is available
        // The table names {$this->table_name} and {$wpdb->users} are considered safe.
        // CURDATE() is a SQL function.
        // Using prepare for consistency and future-proofing.
        $sql = $wpdb->prepare(
            "SELECT r.*, u.display_name, u.user_email
             FROM {$this->table_name} r 
             JOIN {$wpdb->users} u ON r.user_id = u.ID 
             WHERE DATE(r.hora_entrada) = CURDATE() 
             ORDER BY r.hora_entrada DESC"
        );

        if (false === $sql) { // Check if prepare failed
            // Handle error, perhaps log it or return an empty array/WP_Error
            error_log('WPDB prepare failed in get_registros_hoy: ' . $wpdb->last_error);
            return []; 
        }
        return $wpdb->get_results($sql);
    }

    /**
     * Obtener registros por rango de fechas
     */
    public function get_registros_por_fecha($fecha_inicio, $fecha_fin, $user_id = null) {
        global $wpdb;
        
        $sql = "SELECT r.*, u.display_name, u.user_email 
                FROM {$this->table_name} r
                JOIN {$wpdb->users} u ON r.user_id = u.ID
                WHERE DATE(r.hora_entrada) BETWEEN %s AND %s";
        
        $params = [$fecha_inicio, $fecha_fin];
        
        if ($user_id) {
            $sql .= " AND r.user_id = %d";
            $params[] = $user_id;
        }
        
        $sql .= " ORDER BY r.hora_entrada DESC";
        
        return $wpdb->get_results($wpdb->prepare($sql, $params));
    }

    /**
     * Obtener horas por día
     */
    public function get_horas_por_dia($fecha_inicio, $fecha_fin, $user_id = null) {
        global $wpdb;
        
        $sql = "SELECT 
                    DATE(r.hora_entrada) as fecha,
                    u.display_name,
                    u.user_email,
                    SUM(r.duracion) as horas_totales
                FROM {$this->table_name} r
                JOIN {$wpdb->users} u ON r.user_id = u.ID
                WHERE DATE(r.hora_entrada) BETWEEN %s AND %s";
        
        $params = [$fecha_inicio, $fecha_fin];
        
        if ($user_id) {
            $sql .= " AND r.user_id = %d";
            $params[] = $user_id;
        }
        
        $sql .= " GROUP BY DATE(r.hora_entrada), r.user_id
                 ORDER BY fecha DESC";
        
        return $wpdb->get_results($wpdb->prepare($sql, $params));
    }

    /**
     * Obtener horas totales
     */
    public function get_horas_totales($fecha_inicio, $fecha_fin) {
        global $wpdb;
        return $wpdb->get_results($wpdb->prepare("
            SELECT 
                u.ID as user_id,
                u.display_name,
                u.user_email,
                COUNT(DISTINCT DATE(r.hora_entrada)) as dias_trabajados,
                SUM(r.duracion) as horas_totales
            FROM {$this->table_name} r
            JOIN {$wpdb->users} u ON r.user_id = u.ID
            WHERE DATE(r.hora_entrada) BETWEEN %s AND %s
            GROUP BY r.user_id
            ORDER BY horas_totales DESC
        ", $fecha_inicio, $fecha_fin));
    }

    /**
     * Registrar entrada
     */
    public function registrar_entrada($user_id) {
        global $wpdb;
        
        // Verificar si ya tiene una entrada sin salida
        $registro_activo = $wpdb->get_row($wpdb->prepare("
            SELECT * FROM {$this->table_name}
            WHERE user_id = %d AND hora_salida IS NULL
        ", $user_id));

        if ($registro_activo) {
            return new WP_Error('registro_activo', __('Ya tienes un registro activo', 'wp-control-acceso'));
        }

        $resultado = $wpdb->insert(
            $this->table_name,
            array(
                'user_id' => $user_id,
                'hora_entrada' => current_time('mysql')
            ),
            array('%d', '%s')
        );

        if ($resultado === false) {
            return new WP_Error('db_error', __('Error al registrar la entrada', 'wp-control-acceso'));
        }

        return $wpdb->insert_id;
    }

    /**
     * Registrar salida
     */
    public function registrar_salida($user_id) {
        global $wpdb;
        
        // Buscar registro activo
        $registro_activo = $wpdb->get_row($wpdb->prepare("
            SELECT * FROM {$this->table_name}
            WHERE user_id = %d AND hora_salida IS NULL
        ", $user_id));

        if (!$registro_activo) {
            return new WP_Error('no_registro', __('No tienes un registro activo', 'wp-control-acceso'));
        }

        $hora_salida = current_time('mysql');
        $duracion = (strtotime($hora_salida) - strtotime($registro_activo->hora_entrada)) / 3600;

        $resultado = $wpdb->update(
            $this->table_name,
            array(
                'hora_salida' => $hora_salida,
                'duracion' => $duracion
            ),
            array('id' => $registro_activo->id),
            array('%s', '%f'),
            array('%d')
        );

        if ($resultado === false) {
            return new WP_Error('db_error', __('Error al registrar la salida', 'wp-control-acceso'));
        }

        return true;
    }

    /**
     * Exportar a Excel
     */
    public function exportar_excel($tipo, $fecha_inicio, $fecha_fin, $user_id = null) {
        // Input parameters ($tipo, $fecha_inicio, $fecha_fin, $user_id) are assumed to be sanitized 
        // by the calling AJAX handler (exportar_registros in class-wp-control-acceso-public.php).

        require_once WP_CONTROL_ACCESO_PLUGIN_DIR . 'vendor/autoload.php';

        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // Set document properties (optional, but good practice)
        $spreadsheet->getProperties()
            ->setCreator("WP Control Acceso Plugin")
            ->setLastModifiedBy("WP Control Acceso Plugin")
            ->setTitle("Reporte Control de Acceso")
            ->setSubject("Reporte generado por WP Control Acceso Plugin");

        switch ($tipo) {
            case 'detallado':
                $registros = $this->get_registros_por_fecha($fecha_inicio, $fecha_fin, $user_id);
                if (empty($registros) && $user_id && !$fecha_inicio && !$fecha_fin) { // Special case: all records for a user
                    $registros = $this->get_registros_por_fecha(null, null, $user_id);
                }
                $this->generar_excel_detallado($sheet, $registros);
                break;
            case 'por_dia':
                $registros = $this->get_horas_por_dia($fecha_inicio, $fecha_fin, $user_id);
                if (empty($registros) && $user_id && !$fecha_inicio && !$fecha_fin) { // Special case: all records for a user
                    $registros = $this->get_horas_por_dia(null, null, $user_id);
                }
                $this->generar_excel_por_dia($sheet, $registros);
                break;
            case 'totales':
                // For 'totales', user_id might not be applicable if it's a global total report.
                // The AJAX handler should ideally prevent sending user_id if 'totales' is selected for global view.
                // If 'totales' is for a specific user, then get_horas_totales would need modification.
                // For now, assume get_horas_totales is for global as per its original implementation.
                $registros = $this->get_horas_totales($fecha_inicio, $fecha_fin);
                $this->generar_excel_totales($sheet, $registros);
                break;
            default:
                // Handle invalid report type if necessary, though AJAX handler should validate.
                // For now, this will result in an empty Excel.
                break;
        }

        $filename = 'reporte_control_acceso_' . $tipo . '_' . wp_date('Y-m-d') . '.xlsx';

        // Clean output buffer before sending headers
        if (ob_get_level()) {
            ob_end_clean();
        }

        // Set HTTP headers for direct download
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Cache-Control: max-age=0');
        // If you're serving to IE 9, then the following may be needed
        header('Cache-Control: max-age=1');
        // If you're serving to IE over SSL, then the following may be needed
        header('Expires: Mon, 26 Jul 1997 05:00:00 GMT'); // Date in the past
        header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT'); // always modified
        header('Cache-Control: cache, must-revalidate'); // HTTP/1.1
        header('Pragma: public'); // HTTP/1.0

        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        $writer->save('php://output');
        
        exit; // Terminate script execution after sending the file
    }

    private function generar_excel_detallado($sheet, $registros) {
        $sheet->setCellValue('A1', __('Usuario', 'wp-control-acceso'));
        $sheet->setCellValue('B1', __('Fecha', 'wp-control-acceso'));
        $sheet->setCellValue('C1', __('Entrada', 'wp-control-acceso'));
        $sheet->setCellValue('D1', __('Salida', 'wp-control-acceso'));
        $sheet->setCellValue('E1', __('Duración (horas)', 'wp-control-acceso'));

        $row = 2;
        foreach ($registros as $registro) {
            $sheet->setCellValue('A' . $row, $registro->display_name);
            $sheet->setCellValue('B' . $row, date('d/m/Y', strtotime($registro->hora_entrada)));
            $sheet->setCellValue('C' . $row, date('H:i:s', strtotime($registro->hora_entrada)));
            $sheet->setCellValue('D' . $row, $registro->hora_salida ? date('H:i:s', strtotime($registro->hora_salida)) : '-');
            $sheet->setCellValue('E' . $row, $registro->duracion ?? '-');
            $row++;
        }
    }

    private function generar_excel_por_dia($sheet, $registros) {
        $sheet->setCellValue('A1', __('Fecha', 'wp-control-acceso'));
        $sheet->setCellValue('B1', __('Usuario', 'wp-control-acceso'));
        $sheet->setCellValue('C1', __('Horas Totales', 'wp-control-acceso'));

        $row = 2;
        foreach ($registros as $registro) {
            $sheet->setCellValue('A' . $row, date('d/m/Y', strtotime($registro->fecha)));
            $sheet->setCellValue('B' . $row, $registro->display_name);
            $sheet->setCellValue('C' . $row, number_format($registro->horas_totales, 2));
            $row++;
        }
    }

    private function generar_excel_totales($sheet, $registros) {
        $sheet->setCellValue('A1', __('Usuario', 'wp-control-acceso'));
        $sheet->setCellValue('B1', __('Días Trabajados', 'wp-control-acceso'));
        $sheet->setCellValue('C1', __('Horas Totales', 'wp-control-acceso'));
        $sheet->setCellValue('D1', __('Promedio Diario', 'wp-control-acceso'));

        $row = 2;
        foreach ($registros as $registro) {
            $promedio = $registro->dias_trabajados > 0 ? 
                       $registro->horas_totales / $registro->dias_trabajados : 0;

            $sheet->setCellValue('A' . $row, $registro->display_name);
            $sheet->setCellValue('B' . $row, $registro->dias_trabajados);
            $sheet->setCellValue('C' . $row, number_format($registro->horas_totales, 2));
            $sheet->setCellValue('D' . $row, number_format($promedio, 2));
            $row++;
        }
    }

    /**
     * Cierra automáticamente los registros que no han sido cerrados
     * @return int Número de registros cerrados
     */
    public function cerrar_registros_automaticamente() {
        global $wpdb;
        $tabla_registros = $wpdb->prefix . 'control_acceso_registros';
        $hora_actual = current_time('mysql');
        
        // Obtener registros sin cerrar del día anterior
        $registros_abiertos = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM $tabla_registros 
                WHERE hora_salida IS NULL 
                AND DATE(hora_entrada) < DATE(%s)",
                $hora_actual
            )
        );

        $registros_cerrados = 0;
        foreach ($registros_abiertos as $registro) {
            // Establecer la hora de salida a las 23:59:59 del mismo día
            $fecha_entrada = date('Y-m-d', strtotime($registro->hora_entrada));
            $hora_salida = $fecha_entrada . ' 23:59:59';
            
            // Calcular la duración
            $duracion = (strtotime($hora_salida) - strtotime($registro->hora_entrada)) / 3600;
            
            // Actualizar el registro
            $wpdb->update(
                $tabla_registros,
                array(
                    'hora_salida' => $hora_salida,
                    'duracion' => $duracion,
                    'cierre_automatico' => 1
                ),
                array('id' => $registro->id),
                array('%s', '%f', '%d'),
                array('%d')
            );
            
            $registros_cerrados++;
        }
        
        return $registros_cerrados;
    }
}
