<?php
// Verificar que el usuario tiene permisos
if (!current_user_can('control_acceso_manage_all')) {
    return;
}

// Guardar configuraciones
if (isset($_POST['wp_control_acceso_save_settings']) && check_admin_referer('wp_control_acceso_settings')) {
    // Cierre automático
    $cierre_automatico = isset($_POST['cierre_automatico']) ? 1 : 0;
    update_option('wp_control_acceso_cierre_automatico', $cierre_automatico);

    // Hora de cierre automático
    $hora_cierre = sanitize_text_field($_POST['hora_cierre']);
    update_option('wp_control_acceso_hora_cierre', $hora_cierre);

    // Notificaciones por email
    $notificaciones_email = isset($_POST['notificaciones_email']) ? 1 : 0;
    update_option('wp_control_acceso_notificaciones_email', $notificaciones_email);

    echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__('Configuración guardada.', 'wp-control-acceso') . '</p></div>';
}

// Obtener configuraciones actuales
$cierre_automatico = get_option('wp_control_acceso_cierre_automatico', 0);
$hora_cierre = get_option('wp_control_acceso_hora_cierre', '23:59');
$notificaciones_email = get_option('wp_control_acceso_notificaciones_email', 0);
?>

<div class="wrap">
    <h1><?php esc_html_e('Configuración de Control de Acceso', 'wp-control-acceso'); ?></h1>

    <form method="post" action="">
        <?php wp_nonce_field('wp_control_acceso_settings'); ?>

        <table class="form-table">
            <tr>
                <th scope="row"><?php esc_html_e('Cierre Automático', 'wp-control-acceso'); ?></th>
                <td>
                    <label for="cierre_automatico">
                        <input type="checkbox" name="cierre_automatico" id="cierre_automatico" 
                               value="1" <?php checked($cierre_automatico, 1); ?>>
                        <?php esc_html_e('Habilitar cierre automático de registros', 'wp-control-acceso'); ?>
                    </label>
                    <p class="description">
                        <?php esc_html_e('Si está habilitado, los registros sin hora de salida se cerrarán automáticamente.', 'wp-control-acceso'); ?>
                    </p>
                </td>
            </tr>

            <tr>
                <th scope="row"><?php esc_html_e('Hora de Cierre Automático', 'wp-control-acceso'); ?></th>
                <td>
                    <input type="time" name="hora_cierre" value="<?php echo esc_attr($hora_cierre); ?>">
                    <p class="description">
                        <?php esc_html_e('Hora a la que se ejecutará el cierre automático de registros.', 'wp-control-acceso'); ?>
                    </p>
                </td>
            </tr>

            <tr>
                <th scope="row"><?php esc_html_e('Notificaciones por Email', 'wp-control-acceso'); ?></th>
                <td>
                    <label for="notificaciones_email">
                        <input type="checkbox" name="notificaciones_email" id="notificaciones_email" 
                               value="1" <?php checked($notificaciones_email, 1); ?>>
                        <?php esc_html_e('Enviar notificaciones por email', 'wp-control-acceso'); ?>
                    </label>
                    <p class="description">
                        <?php esc_html_e('Si está habilitado, se enviarán notificaciones por email cuando se registren entradas/salidas.', 'wp-control-acceso'); ?>
                    </p>
                </td>
            </tr>
        </table>

        <p class="submit">
            <input type="submit" name="wp_control_acceso_save_settings" class="button button-primary" 
                   value="<?php esc_attr_e('Guardar Cambios', 'wp-control-acceso'); ?>">
        </p>
    </form>
</div>
