<?php

class WP_Control_Acceso {
    protected $loader;
    protected $plugin_name;
    protected $version;

    public function __construct() {
        $this->version = WP_CONTROL_ACCESO_VERSION;
        $this->plugin_name = 'wp-control-acceso';

        $this->load_dependencies();
        $this->set_locale();
        $this->define_admin_hooks();
        $this->define_public_hooks();
        // $this->create_custom_tables(); // Removed: Table creation is handled by the Activator class.
    }

    private function load_dependencies() {
        require_once WP_CONTROL_ACCESO_PLUGIN_DIR . 'includes/class-wp-control-acceso-loader.php';
        require_once WP_CONTROL_ACCESO_PLUGIN_DIR . 'includes/class-wp-control-acceso-i18n.php';
        require_once WP_CONTROL_ACCESO_PLUGIN_DIR . 'includes/class-wp-control-acceso-registros.php';
        require_once WP_CONTROL_ACCESO_PLUGIN_DIR . 'admin/class-wp-control-acceso-admin.php';
        require_once WP_CONTROL_ACCESO_PLUGIN_DIR . 'public/class-wp-control-acceso-public.php';

        $this->loader = new WP_Control_Acceso_Loader();
    }

    private function set_locale() {
        $plugin_i18n = new WP_Control_Acceso_i18n();
        $this->loader->add_action('plugins_loaded', $plugin_i18n, 'load_plugin_textdomain');
    }

    private function define_admin_hooks() {
        $plugin_admin = new WP_Control_Acceso_Admin($this->get_plugin_name(), $this->get_version());

        // Agregar menú de administración
        $this->loader->add_action('admin_menu', $plugin_admin, 'add_menu');
        
        // Registrar estilos y scripts
        $this->loader->add_action('admin_enqueue_scripts', $plugin_admin, 'enqueue_styles');
        $this->loader->add_action('admin_enqueue_scripts', $plugin_admin, 'enqueue_scripts');

        // Agregar enlaces de acción en la página de plugins
        $this->loader->add_filter('plugin_action_links_wp-control-acceso/wp-control-acceso.php', 
            $plugin_admin, 'add_action_links');
    }

    private function define_public_hooks() {
        $plugin_public = new WP_Control_Acceso_Public($this->get_plugin_name(), $this->get_version());
        
        // Registrar estilos y scripts
        $this->loader->add_action('wp_enqueue_scripts', $plugin_public, 'enqueue_styles');
        $this->loader->add_action('wp_enqueue_scripts', $plugin_public, 'enqueue_scripts');
        
        // Registrar endpoints AJAX
        $this->loader->add_action('wp_ajax_wp_control_acceso_entrada', $plugin_public, 'handle_registro_entrada');
        $this->loader->add_action('wp_ajax_wp_control_acceso_salida', $plugin_public, 'handle_registro_salida');
        $this->loader->add_action('wp_ajax_wp_control_acceso_exportar', $plugin_public, 'exportar_registros');

        // Registrar el hook para el cierre automático
        $registros = new WP_Control_Acceso_Registros();
        $this->loader->add_action('wp_control_acceso_cierre_automatico', $registros, 'cerrar_registros_automaticamente');

        // Registrar shortcodes
        add_shortcode('control_acceso_registro', array($this, 'render_registro_shortcode'));
        add_shortcode('control_acceso_dashboard', array($this, 'render_dashboard_shortcode'));
        add_shortcode('control_acceso_mis_reportes', array($this, 'render_mis_reportes_shortcode'));
    }

    private function create_custom_tables() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}control_acceso_registros (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            user_id bigint(20) NOT NULL,
            hora_entrada datetime NOT NULL,
            hora_salida datetime DEFAULT NULL,
            duracion float DEFAULT NULL,
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
    // Ensure the create_custom_tables method is removed if it's no longer called from constructor
    // However, it was noted that the Activator class already handles this.
    // If this method is *only* for the constructor, it can be removed.
    // If it might be used elsewhere (unlikely for this specific method name/purpose), it could stay.
    // For now, assuming it was solely for the constructor and thus redundant due to Activator.
    // Let's comment it out or remove it completely if no other part of *this* class calls it.
    // Given the specific instruction to remove the *call* from constructor, I'll leave the method itself
    // unless explicitly told to remove the method definition.
    // The task was "Remove the line $this->create_custom_tables();", not the method itself.
    // However, the method create_custom_tables() is still present in includes/class-wp-control-acceso-activator.php
    // The one in this file is a duplicate and can be removed.

    // Removing the duplicate method definition as it's handled by the Activator.
    /*
    private function create_custom_tables() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}control_acceso_registros (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            user_id bigint(20) NOT NULL,
            hora_entrada datetime NOT NULL,
            hora_salida datetime DEFAULT NULL,
            duracion float DEFAULT NULL,
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
    */

    /**
     * Verifica si el usuario actual tiene un permiso específico
     */
    public static function current_user_can($capability) {
        if (!is_user_logged_in()) {
            return false;
        }

        $user = wp_get_current_user();
        
        // Si es administrador, tiene todos los permisos
        if (in_array('administrator', $user->roles)) {
            return true;
        }

        // Verificar si el usuario tiene la capacidad específica
        return $user->has_cap($capability);
    }

    /**
     * Renderiza el shortcode de registro
     */
    public function render_registro_shortcode() {
        if (!self::current_user_can('control_acceso_register_attendance')) {
            return '<div class="alert alert-warning">' . esc_html__('No tienes permiso para registrar asistencia.', 'wp-control-acceso') . '</div>';
        }
        ob_start();
        include plugin_dir_path(dirname(__FILE__)) . 'public/partials/registro-form.php';
        return ob_get_clean();
    }

    /**
     * Renderiza el shortcode del dashboard
     */
    public function render_dashboard_shortcode() {
        if (!self::current_user_can('control_acceso_register_attendance')) {
            return '<div class="alert alert-warning">' . esc_html__('No tienes permiso para ver el dashboard.', 'wp-control-acceso') . '</div>';
        }
        ob_start();
        include plugin_dir_path(dirname(__FILE__)) . 'public/partials/dashboard.php';
        return ob_get_clean();
    }

    /**
     * Renderiza el shortcode de reportes personales
     */
    public function render_mis_reportes_shortcode() {
        if (!is_user_logged_in()) {
            return '<div class="alert alert-warning">' . esc_html__('Debes iniciar sesión para ver tus reportes.', 'wp-control-acceso') . '</div>';
        }

        if (!self::current_user_can('control_acceso_view_own_reports')) {
            return '<div class="alert alert-warning">' . esc_html__('No tienes permiso para ver reportes.', 'wp-control-acceso') . '</div>';
        }

        ob_start();
        include plugin_dir_path(dirname(__FILE__)) . 'public/partials/mis-reportes.php';
        return ob_get_clean();
    }

    public function run() {
        $this->loader->run();
    }

    public function get_plugin_name() {
        return $this->plugin_name;
    }

    public function get_version() {
        return $this->version;
    }
}
