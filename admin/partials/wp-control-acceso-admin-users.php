<?php
// Verificar que el usuario tiene permisos
if (!current_user_can('control_acceso_manage_users')) {
    return;
}

// Obtener todos los usuarios con roles relevantes
$users = get_users(array(
    'role__in' => array('empleado', 'supervisor_acceso', 'auditor_acceso')
));

// Procesar cambios de rol si se envía el formulario
if (isset($_POST['submit_role_changes']) && check_admin_referer('control_acceso_users_update')) {
    if (isset($_POST['user_roles']) && is_array($_POST['user_roles'])) {
        foreach ($_POST['user_roles'] as $user_id => $new_role) {
            $user = get_user_by('id', $user_id);
            if ($user) {
                // Eliminar roles anteriores relacionados con control de acceso
                $user->remove_role('empleado');
                $user->remove_role('supervisor_acceso');
                $user->remove_role('auditor_acceso');
                
                // Asignar nuevo rol si se seleccionó uno
                if (!empty($new_role)) {
                    $user->add_role($new_role);
                }
            }
        }
        echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__('Roles actualizados correctamente.', 'wp-control-acceso') . '</p></div>';
    }
}
?>

<div class="wrap">
    <h1><?php esc_html_e('Gestión de Usuarios de Control de Acceso', 'wp-control-acceso'); ?></h1>
    <p><?php esc_html_e('Gestiona los empleados, supervisores y auditores del sistema de control de acceso.', 'wp-control-acceso'); ?></p>

    <div class="tablenav top">
        <div class="alignleft actions">
            <a href="<?php echo esc_url(admin_url('user-new.php')); ?>" class="button button-primary"><?php esc_html_e('Añadir Nuevo Usuario', 'wp-control-acceso'); ?></a>
        </div>
    </div>

    <form method="post" action="">
        <?php wp_nonce_field('control_acceso_users_update'); ?>
        
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th><?php esc_html_e('Usuario', 'wp-control-acceso'); ?></th>
                    <th><?php esc_html_e('Email', 'wp-control-acceso'); ?></th>
                    <th><?php esc_html_e('Rol Actual', 'wp-control-acceso'); ?></th>
                    <th><?php esc_html_e('Cambiar Rol', 'wp-control-acceso'); ?></th>
                    <th><?php esc_html_e('Último Acceso', 'wp-control-acceso'); ?></th>
                    <th><?php esc_html_e('Acciones', 'wp-control-acceso'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php 
                // Obtener todos los usuarios
                $all_users = get_users();
                
                if (!empty($all_users)): 
                    foreach ($all_users as $user):
                        // Obtener roles del usuario
                        $user_roles = $user->roles;
                        $is_empleado = in_array('empleado', $user_roles);
                        $is_supervisor = in_array('control_acceso_supervisor', $user_roles);
                        $is_auditor = in_array('control_acceso_auditor', $user_roles);
                        
                        $last_login = get_user_meta($user->ID, 'last_login', true);
                ?>
                    <tr>
                        <td><?php echo esc_html($user->display_name); ?></td>
                        <td><?php echo esc_html($user->user_email); ?></td>
                        <td>
                            <?php 
                            $display_roles = [];
                            if ($is_empleado) $display_roles[] = esc_html__('Empleado', 'wp-control-acceso');
                            if ($is_supervisor) $display_roles[] = esc_html__('Supervisor de Acceso', 'wp-control-acceso');
                            if ($is_auditor) $display_roles[] = esc_html__('Auditor de Acceso', 'wp-control-acceso');
                            
                            if (empty($display_roles)) {
                                // Display other WordPress roles if no plugin-specific role is assigned
                                $other_roles = array_map('ucfirst', array_filter($user_roles, function($role_slug) {
                                    return !in_array($role_slug, ['empleado', 'control_acceso_supervisor', 'control_acceso_auditor']);
                                }));
                                if (!empty($other_roles)) {
                                    echo esc_html(implode(', ', $other_roles));
                                } else {
                                    echo esc_html__('Sin rol asignado', 'wp-control-acceso');
                                }
                            } else {
                                echo esc_html(implode(', ', $display_roles));
                            }
                            ?>
                        </td>
                        <td>
                            <select name="user_plugin_roles[<?php echo $user->ID; ?>]">
                                <option value=""><?php esc_html_e('-- Mantener rol base / Quitar rol de acceso --', 'wp-control-acceso'); ?></option>
                                <option value="empleado" <?php selected($is_empleado); ?>><?php esc_html_e('Empleado', 'wp-control-acceso'); ?></option>
                                <option value="control_acceso_supervisor" <?php selected($is_supervisor); ?>><?php esc_html_e('Supervisor de Acceso', 'wp-control-acceso'); ?></option>
                                <option value="control_acceso_auditor" <?php selected($is_auditor); ?>><?php esc_html_e('Auditor de Acceso', 'wp-control-acceso'); ?></option>
                            </select>
                        </td>
                        <td><?php echo $last_login ? esc_html(date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($last_login))) : esc_html__('Nunca', 'wp-control-acceso'); ?></td>
                        <td>
                            <a href="<?php echo esc_url(admin_url('user-edit.php?user_id=' . $user->ID)); ?>" 
                               class="button button-small">
                                <?php esc_html_e('Editar', 'wp-control-acceso'); ?>
                            </a>
                        </td>
                    </tr>
                <?php 
                    endforeach; 
                else: 
                ?>
                    <tr>
                        <td colspan="6"><?php esc_html_e('No hay usuarios registrados.', 'wp-control-acceso'); ?></td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>

        <p class="submit">
            <input type="submit" name="submit_role_changes" class="button button-primary" value="<?php esc_attr_e('Guardar Cambios', 'wp-control-acceso'); ?>">
        </p>
    </form>
</div>
