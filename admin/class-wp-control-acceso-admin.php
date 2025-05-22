<?php

class WP_Control_Acceso_Admin {
    private $plugin_name;
    private $version;

    public function __construct($plugin_name, $version) {
        $this->plugin_name = $plugin_name;
        $this->version = $version;

        // Agregar hooks para el manejo de roles en el perfil de usuario
        add_action('show_user_profile', array($this, 'add_control_acceso_role_field'));
        add_action('edit_user_profile', array($this, 'add_control_acceso_role_field'));
        add_action('personal_options_update', array($this, 'save_control_acceso_role'));
        add_action('edit_user_profile_update', array($this, 'save_control_acceso_role'));
        add_action('wp_ajax_get_control_acceso_role', array($this, 'get_control_acceso_role_ajax'));
    }

    public function enqueue_styles() {
        wp_enqueue_style($this->plugin_name, 
            plugin_dir_url(__FILE__) . 'css/wp-control-acceso-admin.css', 
            array(), $this->version, 'all');
        
        // Bootstrap y Font Awesome
        wp_enqueue_style('bootstrap', 
            'https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css');
        wp_enqueue_style('fontawesome', 
            'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css');
    }

    public function enqueue_scripts() {
        wp_enqueue_script($this->plugin_name,
            plugin_dir_url(__FILE__) . 'js/wp-control-acceso-admin.js',
            array('jquery'), $this->version, false);

        // Bootstrap JS
        wp_enqueue_script('bootstrap',
            'https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js',
            array('jquery'), '5.3.0', true);

        // Chart.js
        wp_enqueue_script('chartjs',
            'https://cdn.jsdelivr.net/npm/chart.js',
            array(), '4.4.1', true);

        // Localizar script para AJAX
        wp_localize_script($this->plugin_name, 'wpControlAcceso', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('wp_control_acceso_nonce')
        ));
    }

    public function add_menu() {
        // Menú principal
        add_menu_page(
            __('Control de Acceso', 'wp-control-acceso'), // Page Title
            __('Control de Acceso', 'wp-control-acceso'), // Menu Title
            'read', // Capability
            $this->plugin_name, // Menu Slug
            array($this, 'display_plugin_admin_dashboard'),
            'dashicons-clock',
            26
        );

        // Submenús
        if (current_user_can('control_acceso_manage_users')) {
            add_submenu_page(
                $this->plugin_name, // Parent Slug
                __('Usuarios de Control de Acceso', 'wp-control-acceso'), // Page Title
                __('Usuarios', 'wp-control-acceso'), // Menu Title
                'control_acceso_manage_users', // Capability
                $this->plugin_name . '-users', // Menu Slug
                array($this, 'display_plugin_users_page')
            );
        }

        if (current_user_can('control_acceso_view_reports')) {
            add_submenu_page(
                $this->plugin_name,
                __('Reportes de Control de Acceso', 'wp-control-acceso'),
                __('Reportes', 'wp-control-acceso'),
                'control_acceso_view_reports',
                $this->plugin_name . '-reports',
                array($this, 'display_plugin_reports_page')
            );
        }

        if (current_user_can('control_acceso_manage_all')) {
            add_submenu_page(
                $this->plugin_name,
                __('Configuración de Control de Acceso', 'wp-control-acceso'),
                __('Configuración', 'wp-control-acceso'),
                'control_acceso_manage_all',
                $this->plugin_name . '-settings',
                array($this, 'display_plugin_admin_settings')
            );
        }

        // Menú de ayuda (accesible para todos)
        add_submenu_page(
            $this->plugin_name,
            __('Ayuda de Control de Acceso', 'wp-control-acceso'),
            __('Ayuda', 'wp-control-acceso'),
            'read',
            $this->plugin_name . '-ayuda',
            array($this, 'display_ayuda_page')
        );
    }

    public function add_action_links($links) {
        $settings_link = array(
            '<a href="' . admin_url('admin.php?page=' . $this->plugin_name) . '">' .
            __('Dashboard', 'wp-control-acceso') . '</a>', // Already internationalized, good.
        );
        return array_merge($settings_link, $links);
    }

    /**
     * Muestra el dashboard administrativo
     */
    public function display_plugin_admin_dashboard() {
        if (current_user_can('control_acceso_view_reports')) {
            include_once 'partials/wp-control-acceso-admin-dashboard.php';
        } else {
            echo '<div class="wrap"><div class="alert alert-warning">' . esc_html__('No tienes permiso para ver esta página.', 'wp-control-acceso') . '</div></div>';
        }
    }

    /**
     * Muestra la página de reportes
     */
    public function display_plugin_reports_page() {
        if (current_user_can('control_acceso_view_reports')) {
            include_once 'partials/wp-control-acceso-admin-reportes.php';
        } else {
            echo '<div class="wrap"><div class="alert alert-warning">' . esc_html__('No tienes permiso para ver esta página.', 'wp-control-acceso') . '</div></div>';
        }
    }

    /**
     * Muestra la página de ayuda
     */
    public function display_ayuda_page() {
        require_once plugin_dir_path(dirname(__FILE__)) . 'admin/partials/wp-control-acceso-admin-ayuda.php';
    }

    /**
     * Muestra la página de usuarios
     */
    public function display_plugin_users_page() {
        if (current_user_can('control_acceso_manage_users')) {
            include_once 'partials/wp-control-acceso-admin-users.php';
        } else {
            echo '<div class="wrap"><div class="alert alert-warning">' . esc_html__('No tienes permiso para ver esta página.', 'wp-control-acceso') . '</div></div>';
        }
    }

    /**
     * Muestra la página de configuración
     */
    public function display_plugin_admin_settings() {
        if (current_user_can('control_acceso_manage_all')) {
            include_once 'partials/wp-control-acceso-admin-settings.php';
        } else {
            echo '<div class="wrap"><div class="alert alert-warning">' . esc_html__('No tienes permiso para ver esta página.', 'wp-control-acceso') . '</div></div>';
        }
    }

    /**
     * Agrega el campo de rol en el perfil de usuario
     */
    public function add_control_acceso_role_field($user) {
        if (!current_user_can('manage_options')) {
            return;
        }

        $control_acceso_role = get_user_meta($user->ID, 'control_acceso_role', true);
        ?>
        <h3><?php esc_html_e('Rol Específico de Control de Acceso', 'wp-control-acceso'); ?></h3>
        <table class="form-table">
            <tr>
                <th><label for="control_acceso_plugin_role"><?php esc_html_e('Rol en Control de Acceso', 'wp-control-acceso'); ?></label></th>
                <td>
                    <select name="control_acceso_plugin_role" id="control_acceso_plugin_role">
                        <option value=""><?php esc_html_e('-- Sin Rol Específico de Acceso --', 'wp-control-acceso'); ?></option>
                        <option value="control_acceso_supervisor" <?php selected(get_user_meta($user->ID, 'control_acceso_plugin_role', true), 'control_acceso_supervisor'); ?>>
                            <?php esc_html_e('Supervisor de Acceso', 'wp-control-acceso'); ?>
                        </option>
                        <option value="control_acceso_auditor" <?php selected(get_user_meta($user->ID, 'control_acceso_plugin_role', true), 'control_acceso_auditor'); ?>>
                            <?php esc_html_e('Auditor de Acceso', 'wp-control-acceso'); ?>
                        </option>
                    </select>
                    <?php wp_nonce_field('save_control_acceso_role_nonce_action', 'control_acceso_role_nonce'); ?>
                    <p class="description"><?php esc_html_e('Selecciona un rol específico para este usuario dentro del plugin Control de Acceso. Esto es adicional a sus roles generales de WordPress.', 'wp-control-acceso'); ?></p>
                </td>
            </tr>
        </table>
        <?php
    }

    /**
     * Guarda el rol de Control de Acceso
     */
    public function save_control_acceso_role($user_id) {
        // Verificar el nonce
        if (!isset($_POST['control_acceso_role_nonce']) || !wp_verify_nonce($_POST['control_acceso_role_nonce'], 'save_control_acceso_role_nonce_action')) {
            wp_die(
                esc_html__('Security check failed!', 'wp-control-acceso'), // Already internationalized, good.
                esc_html__('Nonce Verification Failed', 'wp-control-acceso'), // Already internationalized, good.
                array('response' => 403)
            );
        }

        if (!current_user_can('manage_options')) {
            return false; 
        }

        if (isset($_POST['control_acceso_plugin_role'])) {
            $user = new WP_User($user_id);
            $new_plugin_role = sanitize_text_field($_POST['control_acceso_plugin_role']);
            $current_plugin_role = get_user_meta($user_id, 'control_acceso_plugin_role', true);

            // Plugin-specific roles to manage
            $plugin_roles_to_manage = ['control_acceso_supervisor', 'control_acceso_auditor'];

            // Remove current plugin-specific role if it exists and is one we manage
            if (!empty($current_plugin_role) && in_array($current_plugin_role, $plugin_roles_to_manage)) {
                $user->remove_role($current_plugin_role);
            }

            // Add the new role if it's a valid plugin-specific role
            if (!empty($new_plugin_role) && in_array($new_plugin_role, $plugin_roles_to_manage)) {
                $user->add_role($new_plugin_role);
                update_user_meta($user_id, 'control_acceso_plugin_role', $new_plugin_role);
            } else {
                // If "-- Sin Rol Específico --" or an invalid role is selected, delete the meta
                delete_user_meta($user_id, 'control_acceso_plugin_role');
            }
        }
    }

    /**
     * Obtiene el rol de Control de Acceso vía AJAX
     */
    public function get_control_acceso_role_ajax() {
        check_ajax_referer('wp_control_acceso_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Permiso denegado', 'wp-control-acceso'));
        }

        $user_id = isset($_POST['user_id']) ? intval($_POST['user_id']) : 0;
        if (!$user_id) {
            wp_send_json_error(__('Usuario no válido', 'wp-control-acceso'));
        }

        $role = get_user_meta($user_id, 'control_acceso_role', true);
        wp_send_json_success($role);
    }
}
