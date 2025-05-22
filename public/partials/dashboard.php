<?php
$user = wp_get_current_user();
?>

<div class="wp-control-acceso-dashboard">
    <!-- Estado actual -->
    <div class="card mb-4">
        <div class="card-body">
            <div class="d-flex align-items-center justify-content-between">
                <div class="d-flex align-items-center">
                    <?php echo get_avatar($user->ID, 48, '', '', array('class' => 'rounded-circle me-3')); ?>
                    <div>
                        <h5 class="mb-1"><?php echo esc_html($user->display_name); ?></h5>
                        <p class="text-muted mb-0">
                            <?php if ($registro_activo): ?>
                                <span class="text-success">
                                    <i class="fas fa-circle"></i> <?php esc_html_e('En el trabajo', 'wp-control-acceso'); ?>
                                </span>
                            <?php else: ?>
                                <span class="text-secondary">
                                    <i class="fas fa-circle"></i> <?php esc_html_e('Fuera del trabajo', 'wp-control-acceso'); ?>
                                </span>
                            <?php endif; ?>
                        </p>
                    </div>
                </div>
                <div>
                    <?php if ($registro_activo): ?>
                        <button class="btn btn-danger" onclick="registrarSalida()">
                            <i class="fas fa-sign-out-alt"></i> <?php esc_html_e('Registrar Salida', 'wp-control-acceso'); ?>
                        </button>
                    <?php else: ?>
                        <button class="btn btn-primary" onclick="registrarEntrada()">
                            <i class="fas fa-sign-in-alt"></i> <?php esc_html_e('Registrar Entrada', 'wp-control-acceso'); ?>
                        </button>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Estadísticas del día -->
    <div class="row">
        <div class="col-md-6">
            <div class="card mb-4">
                <div class="card-body">
                    <h6 class="card-title text-muted mb-3">
                        <i class="fas fa-clock"></i> <?php esc_html_e('Horas Trabajadas Hoy', 'wp-control-acceso'); ?>
                    </h6>
                    <h3>
                        <?php
                        $horas_trabajadas = !empty($horas_hoy) ? $horas_hoy[0]->horas_totales : 0;
                        // translators: %s is the number of hours
                        printf(esc_html__('%s horas', 'wp-control-acceso'), esc_html(number_format_i18n($horas_trabajadas, 2)));
                        ?>
                    </h3>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card mb-4">
                <div class="card-body">
                    <h6 class="card-title text-muted mb-3">
                        <i class="fas fa-list"></i> <?php esc_html_e('Registros de Hoy', 'wp-control-acceso'); ?>
                    </h6>
                    <h3><?php echo count($registros_hoy); ?></h3>
                </div>
            </div>
        </div>
    </div>

    <!-- Registros del día -->
    <div class="card">
        <div class="card-header">
            <h5 class="mb-0">
                <i class="fas fa-history"></i> <?php esc_html_e('Historial de Hoy', 'wp-control-acceso'); ?>
            </h5>
        </div>
        <div class="card-body">
            <?php if (empty($registros_hoy)): ?>
                <div class="alert alert-info">
                    <i class="fas fa-info-circle"></i> <?php esc_html_e('No hay registros para el día de hoy', 'wp-control-acceso'); ?>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th><i class="fas fa-clock"></i> <?php esc_html_e('Entrada', 'wp-control-acceso'); ?></th>
                                <th><i class="fas fa-clock"></i> <?php esc_html_e('Salida', 'wp-control-acceso'); ?></th>
                                <th><i class="fas fa-hourglass-half"></i> <?php esc_html_e('Duración', 'wp-control-acceso'); ?></th>
                                <th><i class="fas fa-circle"></i> <?php esc_html_e('Estado', 'wp-control-acceso'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($registros_hoy as $registro): ?>
                            <tr>
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
        </div>
    </div>
</div>

<script>
function registrarEntrada() {
    jQuery.ajax({
        url: wpControlAcceso.ajax_url,
        type: 'POST',
        data: {
            action: 'wp_control_acceso_entrada',
            nonce: wpControlAcceso.nonce
        },
        success: function(response) {
            if (response.success) {
                location.reload();
            } else {
                alert(response.data); // AJAX error messages are already i18n'd in the PHP class
            }
        }
    });
}

function registrarSalida() {
    jQuery.ajax({
        url: wpControlAcceso.ajax_url,
        type: 'POST',
        data: {
            action: 'wp_control_acceso_salida',
            nonce: wpControlAcceso.nonce
        },
        success: function(response) {
            if (response.success) {
                location.reload();
            } else {
                alert(response.data); // AJAX error messages are already i18n'd in the PHP class
            }
        }
    });
}
</script>
