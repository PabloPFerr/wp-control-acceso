<?php
// Verificar que el usuario tiene permisos
if (!current_user_can('manage_options')) {
    return;
}

$registros = new WP_Control_Acceso_Registros();

// Procesar filtros
$fecha_inicio = isset($_GET['fecha_inicio']) ? sanitize_text_field($_GET['fecha_inicio']) : date('Y-m-d');
$fecha_fin = isset($_GET['fecha_fin']) ? sanitize_text_field($_GET['fecha_fin']) : date('Y-m-d');
$user_id = isset($_GET['user_id']) ? intval($_GET['user_id']) : null;

// Obtener datos según el tipo de reporte
$tipo_reporte = isset($_GET['tipo']) ? sanitize_text_field($_GET['tipo']) : 'detallado';

// Obtener los datos para los gráficos
$datos_grafico = $registros->get_horas_por_dia($fecha_inicio, $fecha_fin, $user_id);
$labels = array();
$horas = array();
foreach ($datos_grafico as $dato) {
    $labels[] = $dato->fecha;
    $horas[] = $dato->horas_totales;
}
?>

<div class="wrap">
    <h1><?php echo esc_html(get_admin_page_title()); ?></h1>

    <!-- Filtros -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="get" action="<?php echo esc_url(admin_url('admin.php')); ?>" class="row g-3">
                <input type="hidden" name="page" value="wp-control-acceso-reports">
                
                <div class="col-md-3">
                    <label for="fecha_inicio" class="form-label"><?php esc_html_e('Fecha Inicio', 'wp-control-acceso'); ?></label>
                    <input type="date" id="fecha_inicio" name="fecha_inicio" class="form-control" 
                           value="<?php echo esc_attr($fecha_inicio); ?>">
                </div>
                
                <div class="col-md-3">
                    <label for="fecha_fin" class="form-label"><?php esc_html_e('Fecha Fin', 'wp-control-acceso'); ?></label>
                    <input type="date" id="fecha_fin" name="fecha_fin" class="form-control" 
                           value="<?php echo esc_attr($fecha_fin); ?>">
                </div>
                
                <div class="col-md-3">
                    <label for="user_id_filter" class="form-label"><?php esc_html_e('Usuario', 'wp-control-acceso'); ?></label>
                    <?php
                    wp_dropdown_users(array(
                        'name' => 'user_id',
                        'id' => 'user_id_filter',
                        'class' => 'form-control',
                        'show_option_all' => esc_html__('Todos los usuarios', 'wp-control-acceso'),
                        'selected' => $user_id
                    ));
                    ?>
                </div>
                
                <div class="col-md-3">
                    <label for="tipo_reporte_filter" class="form-label"><?php esc_html_e('Tipo de Reporte', 'wp-control-acceso'); ?></label>
                    <select name="tipo" id="tipo_reporte_filter" class="form-control">
                        <option value="detallado" <?php selected($tipo_reporte, 'detallado'); ?>>
                            <?php esc_html_e('Detallado', 'wp-control-acceso'); ?>
                        </option>
                        <option value="por_dia" <?php selected($tipo_reporte, 'por_dia'); ?>>
                            <?php esc_html_e('Por Día', 'wp-control-acceso'); ?>
                        </option>
                        <option value="totales" <?php selected($tipo_reporte, 'totales'); ?>>
                            <?php esc_html_e('Totales', 'wp-control-acceso'); ?>
                        </option>
                    </select>
                </div>
                
                <div class="col-12">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-filter"></i> <?php esc_html_e('Filtrar', 'wp-control-acceso'); ?>
                    </button>
                    <button type="button" class="btn btn-success" onclick="exportarExcel()">
                        <i class="fas fa-file-excel"></i> <?php esc_html_e('Exportar a Excel', 'wp-control-acceso'); ?>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Tabs de reportes -->
    <div class="card">
        <div class="card-header">
            <ul class="nav nav-tabs card-header-tabs">
                <li class="nav-item">
                    <a class="nav-link <?php echo $tipo_reporte === 'detallado' ? 'active' : ''; ?>" 
                       href="<?php echo esc_url(admin_url('admin.php?page=wp-control-acceso-reports&tipo=detallado&fecha_inicio=' . $fecha_inicio . '&fecha_fin=' . $fecha_fin . '&user_id=' . $user_id)); ?>">
                        <i class="fas fa-list"></i> <?php esc_html_e('Registros Detallados', 'wp-control-acceso'); ?>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo $tipo_reporte === 'por_dia' ? 'active' : ''; ?>" 
                       href="<?php echo esc_url(admin_url('admin.php?page=wp-control-acceso-reports&tipo=por_dia&fecha_inicio=' . $fecha_inicio . '&fecha_fin=' . $fecha_fin . '&user_id=' . $user_id)); ?>">
                        <i class="fas fa-calendar-day"></i> <?php esc_html_e('Horas por Día', 'wp-control-acceso'); ?>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo $tipo_reporte === 'totales' ? 'active' : ''; ?>" 
                       href="<?php echo esc_url(admin_url('admin.php?page=wp-control-acceso-reports&tipo=totales&fecha_inicio=' . $fecha_inicio . '&fecha_fin=' . $fecha_fin . '&user_id=' . $user_id)); ?>">
                        <i class="fas fa-clock"></i> <?php esc_html_e('Horas Totales', 'wp-control-acceso'); ?>
                    </a>
                </li>
            </ul>
        </div>
        <div class="card-body">
            <div class="tab-content mt-3">
                <?php if ($tipo_reporte === 'detallado'): ?>
                    <?php
                    $registros_detallados = $registros->get_registros_por_fecha($fecha_inicio, $fecha_fin, $user_id);
                    if (empty($registros_detallados)): ?>
                        <div class="alert alert-info"><?php esc_html_e('No hay registros para mostrar.', 'wp-control-acceso'); ?></div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th><?php esc_html_e('Usuario', 'wp-control-acceso'); ?></th>
                                        <th><?php esc_html_e('Entrada', 'wp-control-acceso'); ?></th>
                                        <th><?php esc_html_e('Salida', 'wp-control-acceso'); ?></th>
                                        <th><?php esc_html_e('Duración', 'wp-control-acceso'); ?></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($registros_detallados as $registro): ?>
                                        <tr>
                                            <td><?php echo esc_html($registro->display_name); ?></td>
                                            <td><?php echo esc_html(date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($registro->hora_entrada))); ?></td>
                                            <td><?php echo $registro->hora_salida ? esc_html(date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($registro->hora_salida))) : esc_html__('En curso', 'wp-control-acceso'); ?></td>
                                            <td><?php echo $registro->duracion ? esc_html(sprintf(__('%s horas', 'wp-control-acceso'), number_format_i18n($registro->duracion, 2))) : '-'; ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>

                <?php if ($tipo_reporte === 'por_dia'): ?>
                    <?php
                    $registros_por_dia = $registros->get_horas_por_dia($fecha_inicio, $fecha_fin, $user_id);
                    if (empty($registros_por_dia)): ?>
                        <div class="alert alert-info"><?php esc_html_e('No hay registros para mostrar.', 'wp-control-acceso'); ?></div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th><?php esc_html_e('Fecha', 'wp-control-acceso'); ?></th>
                                        <th><?php esc_html_e('Usuario', 'wp-control-acceso'); ?></th>
                                        <th><?php esc_html_e('Horas', 'wp-control-acceso'); ?></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($registros_por_dia as $registro): ?>
                                        <tr>
                                            <td><?php echo esc_html(date_i18n(get_option('date_format'), strtotime($registro->fecha))); ?></td>
                                            <td><?php echo esc_html($registro->display_name); ?></td>
                                            <td><?php echo esc_html(sprintf(__('%s horas', 'wp-control-acceso'), number_format_i18n($registro->horas_totales, 2))); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>

                <?php if ($tipo_reporte === 'totales'): ?>
                    <?php
                    $registros_totales = $registros->get_horas_totales($fecha_inicio, $fecha_fin);
                    if (empty($registros_totales)): ?>
                        <div class="alert alert-info"><?php esc_html_e('No hay registros para mostrar.', 'wp-control-acceso'); ?></div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th><?php esc_html_e('Usuario', 'wp-control-acceso'); ?></th>
                                        <th><?php esc_html_e('Días Trabajados', 'wp-control-acceso'); ?></th>
                                        <th><?php esc_html_e('Horas Totales', 'wp-control-acceso'); ?></th>
                                        <th><?php esc_html_e('Promedio Diario', 'wp-control-acceso'); ?></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($registros_totales as $registro): ?>
                                        <tr>
                                            <td><?php echo esc_html($registro->display_name); ?></td>
                                            <td><?php echo esc_html($registro->dias_trabajados); ?></td>
                                            <td><?php echo esc_html(sprintf(__('%s horas', 'wp-control-acceso'), number_format_i18n($registro->horas_totales, 2))); ?></td>
                                            <td><?php echo ($registro->dias_trabajados > 0) ? esc_html(sprintf(__('%s horas', 'wp-control-acceso'), number_format_i18n($registro->horas_totales / $registro->dias_trabajados, 2))) : '-'; ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Gráficos -->
    <div class="row mt-4">
        <!-- Gráfico de horas por día -->
        <div class="col-md-6 mb-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0"><?php esc_html_e('Horas por Día', 'wp-control-acceso'); ?></h5>
                </div>
                <div class="card-body grafico-container">
                    <canvas id="graficoHorasDia"></canvas>
                </div>
            </div>
        </div>

        <!-- Gráfico de totales por usuario -->
        <div class="col-md-6 mb-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0"><?php esc_html_e('Horas Totales por Usuario', 'wp-control-acceso'); ?></h5>
                </div>
                <div class="card-body grafico-container">
                    <canvas id="graficoTotalesUsuario"></canvas>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Configuración de los gráficos
document.addEventListener('DOMContentLoaded', function() {
    // Gráfico de horas por día
    var ctxDia = document.getElementById('graficoHorasDia').getContext('2d');
    new Chart(ctxDia, {
        type: 'line',
        data: {
            labels: <?php echo json_encode($labels); ?>,
            datasets: [{
                label: '<?php esc_html_e('Horas', 'wp-control-acceso'); ?>',
                data: <?php echo json_encode($horas); ?>,
                borderColor: 'rgb(75, 192, 192)',
                tension: 0.1
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                title: {
                    display: true,
                    text: '<?php esc_html_e('Horas Trabajadas por Día', 'wp-control-acceso'); ?>'
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    title: {
                        display: true,
                        text: '<?php esc_html_e('Horas', 'wp-control-acceso'); ?>'
                    }
                }
            }
        }
    });

    // Gráfico de totales por usuario
    <?php
    $datos_totales = $registros->get_horas_totales($fecha_inicio, $fecha_fin);
    $usuarios = array();
    $horas_totales_grafico = array(); // Renamed to avoid conflict with $horas from previous graph
    foreach ($datos_totales as $dato) {
        $usuarios[] = $dato->display_name;
        $horas_totales_grafico[] = $dato->horas_totales;
    }
    ?>
    var ctxTotal = document.getElementById('graficoTotalesUsuario').getContext('2d');
    new Chart(ctxTotal, {
        type: 'bar',
        data: {
            labels: <?php echo json_encode($usuarios); ?>,
            datasets: [{
                label: '<?php esc_html_e('Horas Totales', 'wp-control-acceso'); ?>',
                data: <?php echo json_encode($horas_totales_grafico); ?>,
                backgroundColor: 'rgba(54, 162, 235, 0.5)',
                borderColor: 'rgb(54, 162, 235)',
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                title: {
                    display: true,
                    text: '<?php esc_html_e('Horas Totales por Usuario', 'wp-control-acceso'); ?>'
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    title: {
                        display: true,
                        text: '<?php esc_html_e('Horas', 'wp-control-acceso'); ?>'
                    }
                }
            }
        }
    });
});

function exportarExcel() {
    // Construct the URL for export
    var exportUrl = new URL(ajaxurl);
    exportUrl.searchParams.append('action', 'wp_control_acceso_exportar');
    exportUrl.searchParams.append('tipo', '<?php echo esc_js($tipo_reporte); ?>');
    exportUrl.searchParams.append('fecha_inicio', '<?php echo esc_js($fecha_inicio); ?>');
    exportUrl.searchParams.append('fecha_fin', '<?php echo esc_js($fecha_fin); ?>');
    exportUrl.searchParams.append('user_id', '<?php echo esc_js($user_id); ?>');
    // Use the localized nonce for export
    exportUrl.searchParams.append(wpControlAcceso.export_nonce_action, wpControlAcceso.export_nonce); 

    window.location.href = exportUrl.toString();
}
</script>

<style>
.card {
    margin-bottom: 2rem;
}
.card-body {
    padding: 1.5rem;
}
.table {
    margin-bottom: 0;
}
.grafico-container {
    height: 300px;
    position: relative;
}
canvas {
    max-height: 100%;
    width: 100% !important;
}
</style>