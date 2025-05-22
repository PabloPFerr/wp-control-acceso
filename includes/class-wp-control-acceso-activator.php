<?php

/**
 * Fired during plugin activation
 */
class WP_Control_Acceso_Activator {

    /**
     * Crea los roles y configura las capacidades durante la activación
     */
    public static function activate() {
        self::create_tables();
        self::setup_roles_and_capabilities();
        self::setup_cron_jobs();
    }

    /**
     * Crea las tablas necesarias en la base de datos
     */
    private static function create_tables() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}control_acceso_registros (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            user_id bigint(20) NOT NULL,
            hora_entrada datetime NOT NULL,
            hora_salida datetime DEFAULT NULL,
            duracion float DEFAULT NULL,
            cierre_automatico tinyint(1) DEFAULT 0,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY user_id (user_id),
            KEY hora_entrada (hora_entrada),
            KEY hora_salida (hora_salida)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }

    /**
     * Configura los roles y capacidades
     */
    private static function setup_roles_and_capabilities() {
        // Remover el rol existente si existe
        remove_role('empleado');
        
        // Crear el rol de empleado con las capacidades básicas de WordPress
        add_role(
            'empleado',
            'Empleado',
            array(
                'read' => true,
                'control_acceso_register_attendance' => true,
                'control_acceso_view_own_reports' => true,
                'control_acceso_view_reports' => false, // Solo para administradores
            )
        );

        // Asignar capacidades al rol de administrador
        $admin = get_role('administrator');
        if ($admin) {
            $admin->add_cap('control_acceso_register_attendance'); // Can clock in/out
            $admin->add_cap('control_acceso_view_own_reports');   // Can view their own reports
            $admin->add_cap('control_acceso_view_reports');       // Can view all reports (admin context)
            $admin->add_cap('control_acceso_manage_users');       // Can manage plugin users/roles
            $admin->add_cap('control_acceso_manage_all');         // Can manage all plugin settings
        }

        // Remover roles anteriores para asegurar una actualización limpia de capacidades
        remove_role('control_acceso_supervisor');
        remove_role('control_acceso_auditor');

        // Crear rol de Supervisor
        add_role(
            'control_acceso_supervisor',
            __('Supervisor de Acceso', 'wp-control-acceso'),
            array(
                'read' => true, // Basic WP capability
                'control_acceso_manage_users' => true,
                'control_acceso_view_reports' => true,
                // Supervisors might also need to register their own attendance
                'control_acceso_register_attendance' => true, 
                'control_acceso_view_own_reports' => true,
            )
        );

        // Crear rol de Auditor
        add_role(
            'control_acceso_auditor',
            __('Auditor de Acceso', 'wp-control-acceso'),
            array(
                'read' => true, // Basic WP capability
                'control_acceso_view_reports' => true,
                 // Auditors might also need to register their own attendance
                'control_acceso_register_attendance' => true,
                'control_acceso_view_own_reports' => true,
            )
        );

        // Debug: Verificar que los roles se crearon correctamente
        if (defined('WP_DEBUG') && WP_DEBUG) {
            $empleado_role = get_role('empleado');
            if ($empleado_role) {
                error_log('WP Control Acceso Debug: Rol "empleado" creado/actualizado con capacidades: ' . print_r($empleado_role->capabilities, true));
            } else {
                error_log('WP Control Acceso Debug: Error: No se pudo crear/verificar el rol "empleado".');
            }
            $supervisor_role = get_role('control_acceso_supervisor');
            if ($supervisor_role) {
                error_log('WP Control Acceso Debug: Rol "control_acceso_supervisor" creado/actualizado con capacidades: ' . print_r($supervisor_role->capabilities, true));
            } else {
                error_log('WP Control Acceso Debug: Error: No se pudo crear/verificar el rol "control_acceso_supervisor".');
            }
            $auditor_role = get_role('control_acceso_auditor');
            if ($auditor_role) {
                error_log('WP Control Acceso Debug: Rol "control_acceso_auditor" creado/actualizado con capacidades: ' . print_r($auditor_role->capabilities, true));
            } else {
                error_log('WP Control Acceso Debug: Error: No se pudo crear/verificar el rol "control_acceso_auditor".');
            }
        }
    }

    /**
     * Configura los trabajos cron
     */
    private static function setup_cron_jobs() {
        // Programar el evento cron si no existe
        if (!wp_next_scheduled('wp_control_acceso_cierre_automatico')) {
            // Programar para la próxima medianoche
            $tomorrow = strtotime('tomorrow midnight');
            wp_schedule_event($tomorrow, 'daily', 'wp_control_acceso_cierre_automatico');
        }
    }
}
