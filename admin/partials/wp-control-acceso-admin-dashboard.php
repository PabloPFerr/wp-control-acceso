<?php
// Verificar que el usuario tiene permisos
if (!current_user_can('manage_options')) {
    return;
}

// Obtener estadísticas
global $wpdb;
$total_usuarios = count_users();
$usuarios_activos = $wpdb->get_var("
    SELECT COUNT(DISTINCT user_id) 
    FROM {$wpdb->prefix}control_acceso_registros 
    WHERE hora_entrada >= CURDATE() AND hora_salida IS NULL
");
$registros_hoy = $wpdb->get_var("
    SELECT COUNT(*) 
    FROM {$wpdb->prefix}control_acceso_registros 
    WHERE DATE(hora_entrada) = CURDATE()
");

// Obtener registros del día
$registros = $wpdb->get_results("
    SELECT r.*, u.display_name 
    FROM {$wpdb->prefix}control_acceso_registros r
    JOIN {$wpdb->users} u ON r.user_id = u.ID
    WHERE DATE(r.hora_entrada) = CURDATE()
    ORDER BY r.hora_entrada DESC
");
?>

<div class="wrap">
    <h1><?php echo esc_html(get_admin_page_title()); ?></h1>

    <!-- Tarjetas de estadísticas -->
    <div class="row mt-4">
        <div class="col-md-4">
            <div class="card bg-primary text-white">
                <div class="card-body">
                    <h5 class="card-title">
                        <i class="fas fa-users"></i> <?php esc_html_e('Total Usuarios', 'wp-control-acceso'); ?>
                    </h5>
                    <h2><?php echo $total_usuarios['total_users']; ?></h2>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card bg-success text-white">
                <div class="card-body">
                    <h5 class="card-title">
                        <i class="fas fa-user-check"></i> <?php esc_html_e('Usuarios Activos', 'wp-control-acceso'); ?>
                    </h5>
                    <h2><?php echo $usuarios_activos; ?></h2>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card bg-info text-white">
                <div class="card-body">
                    <h5 class="card-title">
                        <i class="fas fa-clock"></i> <?php esc_html_e('Registros Hoy', 'wp-control-acceso'); ?>
                    </h5>
                    <h2><?php echo $registros_hoy; ?></h2>
                </div>
            </div>
        </div>
    </div>

    <!-- Tabla de registros -->
    <div class="card mt-4">
        <div class="card-header">
            <h5 class="mb-0">
                <i class="fas fa-list"></i> <?php esc_html_e('Registros de Hoy', 'wp-control-acceso'); ?>
            </h5>
        </div>
        <div class="card-body">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th><i class="fas fa-user"></i> <?php esc_html_e('Usuario', 'wp-control-acceso'); ?></th>
                        <th><i class="fas fa-clock"></i> <?php esc_html_e('Entrada', 'wp-control-acceso'); ?></th>
                        <th><i class="fas fa-clock"></i> <?php esc_html_e('Salida', 'wp-control-acceso'); ?></th>
                        <th><i class="fas fa-hourglass-half"></i> <?php esc_html_e('Duración', 'wp-control-acceso'); ?></th>
                        <th><i class="fas fa-circle"></i> <?php esc_html_e('Estado', 'wp-control-acceso'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($registros as $registro): ?>
                    <tr>
                        <td><?php echo esc_html($registro->display_name); ?></td>
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
    </div>
</div>
