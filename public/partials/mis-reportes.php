<?php
// The initial checks for is_user_logged_in and capability are handled by the shortcode rendering function
// in includes/class-wp-control-acceso.php, which includes internationalized messages.
// This partial should ideally not repeat those checks or messages if it's only included after them.

$user_id = get_current_user_id(); // Assume user is logged in and has permissions by this point.
$registros = new WP_Control_Acceso_Registros();

// Procesar filtros
$fecha_inicio = isset($_GET['fecha_inicio']) ? sanitize_text_field($_GET['fecha_inicio']) : date('Y-m-d', strtotime('-30 days'));
$fecha_fin = isset($_GET['fecha_fin']) ? sanitize_text_field($_GET['fecha_fin']) : date('Y-m-d');
$tipo_reporte = isset($_GET['tipo']) ? sanitize_text_field($_GET['tipo']) : 'detallado';
?>

<div class="wp-control-acceso-reportes">
    <!-- Filtros -->
    <div class="card mb-4">
        <div class="card-header">
            <h5 class="mb-0">
                <i class="fas fa-filter"></i> <?php esc_html_e('Filtros', 'wp-control-acceso'); ?>
            </h5>
        </div>
        <div class="card-body">
            <form method="get" class="row g-3">
                <?php foreach ($_GET as $key => $value): ?>
                    <?php if ($key !== 'fecha_inicio' && $key !== 'fecha_fin' && $key !== 'tipo' && $key !== 'page_id' && $key !== 'paged'): // Exclude common WP query vars ?>
                        <input type="hidden" name="<?php echo esc_attr($key); ?>" value="<?php echo esc_attr($value); ?>">
                    <?php endif; ?>
                <?php endforeach; ?>

                <div class="col-md-4">
                    <label for="mis_reportes_fecha_inicio" class="form-label"><?php esc_html_e('Fecha Inicio', 'wp-control-acceso'); ?></label>
                    <input type="date" id="mis_reportes_fecha_inicio" name="fecha_inicio" class="form-control" 
                           value="<?php echo esc_attr($fecha_inicio); ?>">
                </div>
                
                <div class="col-md-4">
                    <label for="mis_reportes_fecha_fin" class="form-label"><?php esc_html_e('Fecha Fin', 'wp-control-acceso'); ?></label>
                    <input type="date" id="mis_reportes_fecha_fin" name="fecha_fin" class="form-control" 
                           value="<?php echo esc_attr($fecha_fin); ?>">
                </div>
                
                <div class="col-md-4">
                    <label for="mis_reportes_tipo" class="form-label"><?php esc_html_e('Tipo de Reporte', 'wp-control-acceso'); ?></label>
                    <select name="tipo" id="mis_reportes_tipo" class="form-control">
                        <option value="detallado" <?php selected($tipo_reporte, 'detallado'); ?>>
                            <?php esc_html_e('Registros Detallados', 'wp-control-acceso'); ?>
                        </option>
                        <option value="por_dia" <?php selected($tipo_reporte, 'por_dia'); ?>>
                            <?php esc_html_e('Horas por Día', 'wp-control-acceso'); ?>
                        </option>
                        <option value="totales" <?php selected($tipo_reporte, 'totales'); ?>>
                            <?php esc_html_e('Resumen Total', 'wp-control-acceso'); ?>
                        </option>
                    </select>
                </div>
                
                <div class="col-12">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-search"></i> <?php esc_html_e('Filtrar', 'wp-control-acceso'); ?>
                    </button>
                    <button type="button" class="btn btn-success" onclick="exportarMisReportes()">
                        <i class="fas fa-file-excel"></i> <?php esc_html_e('Exportar a Excel', 'wp-control-acceso'); ?>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Resultados -->
    <div class="card">
        <div class="card-header">
            <h5 class="mb-0">
                <?php if ($tipo_reporte === 'detallado'): ?>
                    <i class="fas fa-list"></i> <?php esc_html_e('Registros Detallados', 'wp-control-acceso'); ?>
                <?php elseif ($tipo_reporte === 'por_dia'): ?>
                    <i class="fas fa-calendar-day"></i> <?php esc_html_e('Horas por Día', 'wp-control-acceso'); ?>
                <?php else: ?>
                    <i class="fas fa-chart-line"></i> <?php esc_html_e('Resumen Total', 'wp-control-acceso'); ?>
                <?php endif; ?>
            </h5>
        </div>
        <div class="card-body">
            <?php if ($tipo_reporte === 'detallado'): ?>
                <?php
                $registros_detallados = $registros->get_registros_por_fecha($fecha_inicio, $fecha_fin, $user_id);
                if (empty($registros_detallados)): ?>
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle"></i> <?php esc_html_e('No se encontraron registros', 'wp-control-acceso'); ?>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th><i class="fas fa-calendar"></i> <?php esc_html_e('Fecha', 'wp-control-acceso'); ?></th>
                                    <th><i class="fas fa-clock"></i> <?php esc_html_e('Entrada', 'wp-control-acceso'); ?></th>
                                    <th><i class="fas fa-clock"></i> <?php esc_html_e('Salida', 'wp-control-acceso'); ?></th>
                                    <th><i class="fas fa-hourglass-half"></i> <?php esc_html_e('Duración', 'wp-control-acceso'); ?></th>
                                    <th><i class="fas fa-circle"></i> <?php esc_html_e('Estado', 'wp-control-acceso'); ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($registros_detallados as $registro): ?>
                                <tr>
                                    <td><?php echo esc_html(date_i18n(get_option('date_format'), strtotime($registro->hora_entrada))); ?></td>
                                    <td><?php echo esc_html(date_i18n(get_option('time_format'), strtotime($registro->hora_entrada))); ?></td>
                                    <td>
                                        <?php 
                                        if ($registro->hora_salida) {
                                            echo esc_html(date_i18n(get_option('time_format'), strtotime($registro->hora_salida)));
                                            if ($registro->cierre_automatico) {
                                                echo ' <span class="badge bg-warning" title="' . esc_attr__('Cierre automático', 'wp-control-acceso') . '"><i class="fas fa-robot"></i></span>';
                                            }
                                        } else {
                                            echo '<span class="badge bg-warning">' . esc_html__('En curso', 'wp-control-acceso') . '</span>';
                                        }
                                        ?>
                                    </td>
                                    <td>
                                        <?php 
                                        if ($registro->duracion) {
                                            echo '<span class="badge bg-info">' . 
                                                esc_html(sprintf(__('%s horas', 'wp-control-acceso'), number_format_i18n($registro->duracion, 2))) . '</span>';
                                        } else {
                                            echo '<span class="badge bg-secondary">-</span>';
                                        }
                                        ?>
                                    </td>
                                    <td>
                                        <?php if (!$registro->hora_salida): ?>
                                            <span class="badge bg-primary"><?php esc_html_e('Activo', 'wp-control-acceso'); ?></span>
                                        <?php else: ?>
                                            <span class="badge bg-success"><?php esc_html_e('Finalizado', 'wp-control-acceso'); ?></span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>

            <?php elseif ($tipo_reporte === 'por_dia'): ?>
                <?php
                $registros_por_dia = $registros->get_horas_por_dia($fecha_inicio, $fecha_fin, $user_id);
                if (empty($registros_por_dia)): ?>
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle"></i> <?php esc_html_e('No se encontraron registros', 'wp-control-acceso'); ?>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th><i class="fas fa-calendar"></i> <?php esc_html_e('Fecha', 'wp-control-acceso'); ?></th>
                                    <th><i class="fas fa-clock"></i> <?php esc_html_e('Horas Trabajadas', 'wp-control-acceso'); ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($registros_por_dia as $registro): ?>
                                <tr>
                                    <td><?php echo esc_html(date_i18n(get_option('date_format'), strtotime($registro->fecha))); ?></td>
                                    <td>
                                        <span class="badge bg-info">
                                            <?php printf(esc_html__('%s horas', 'wp-control-acceso'), esc_html(number_format_i18n($registro->horas_totales, 2))); ?>
                                        </span>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>

            <?php else: // 'totales' ?>
                <?php
                // For "mis reportes", get_horas_totales should be filtered by user_id or a new method created.
                // Assuming get_horas_totales can accept user_id or it's handled appropriately.
                // For this example, let's assume get_horas_totales for a single user or adapt.
                // A better approach would be a dedicated method like get_summary_for_user($user_id, $fecha_inicio, $fecha_fin)
                
                // Let's fetch the summary data specifically for the current user
                $dias_trabajados = 0;
                $horas_totales_usuario = 0;
                
                $registros_del_periodo = $registros->get_registros_por_fecha($fecha_inicio, $fecha_fin, $user_id);
                $dias_unicos = [];
                foreach ($registros_del_periodo as $reg) {
                    if ($reg->duracion) {
                        $horas_totales_usuario += $reg->duracion;
                    }
                    $fecha_dia = date('Y-m-d', strtotime($reg->hora_entrada));
                    if (!in_array($fecha_dia, $dias_unicos)) {
                        $dias_unicos[] = $fecha_dia;
                    }
                }
                $dias_trabajados = count($dias_unicos);
                $promedio_diario = $dias_trabajados > 0 ? $horas_totales_usuario / $dias_trabajados : 0;

                if ($dias_trabajados == 0 && $horas_totales_usuario == 0):
                ?>
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle"></i> <?php esc_html_e('No se encontraron registros para el resumen.', 'wp-control-acceso'); ?>
                    </div>
                <?php else: ?>
                    <div class="row">
                        <div class="col-md-4">
                            <div class="card bg-light">
                                <div class="card-body text-center">
                                    <h6 class="card-title"><?php esc_html_e('Días Trabajados', 'wp-control-acceso'); ?></h6>
                                    <h2><?php echo esc_html($dias_trabajados); ?></h2>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card bg-light">
                                <div class="card-body text-center">
                                    <h6 class="card-title"><?php esc_html_e('Horas Totales', 'wp-control-acceso'); ?></h6>
                                    <h2><?php echo esc_html(number_format_i18n($horas_totales_usuario, 2)); ?></h2>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card bg-light">
                                <div class="card-body text-center">
                                    <h6 class="card-title"><?php esc_html_e('Promedio Diario', 'wp-control-acceso'); ?></h6>
                                    <h2>
                                        <?php echo esc_html(number_format_i18n($promedio_diario, 2)); ?>
                                    </h2>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
function exportarMisReportes() {
    // Construct the URL for export
    var exportUrl = new URL(wpControlAcceso.ajax_url);
    exportUrl.searchParams.append('action', 'wp_control_acceso_exportar');
    exportUrl.searchParams.append('tipo', '<?php echo esc_js($tipo_reporte); ?>');
    exportUrl.searchParams.append('fecha_inicio', '<?php echo esc_js($fecha_inicio); ?>');
    exportUrl.searchParams.append('fecha_fin', '<?php echo esc_js($fecha_fin); ?>');
    // user_id is already available in PHP as $user_id
    exportUrl.searchParams.append('user_id', '<?php echo esc_js($user_id); ?>'); 
    // Use the localized nonce for export
    exportUrl.searchParams.append(wpControlAcceso.export_nonce_action, wpControlAcceso.export_nonce);

    window.location.href = exportUrl.toString();
}
</script>
