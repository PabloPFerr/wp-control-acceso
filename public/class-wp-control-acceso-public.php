<?php

class WP_Control_Acceso_Public {
    private $plugin_name;
    private $version;
    private $registros;

    public function __construct($plugin_name, $version) {
        $this->plugin_name = $plugin_name;
        $this->version = $version;
        $this->registros = new WP_Control_Acceso_Registros();
    }

    public function enqueue_styles() {
        wp_enqueue_style($this->plugin_name,
            plugin_dir_url(__FILE__) . 'css/wp-control-acceso-public.css',
            array(), $this->version, 'all');
        
        wp_enqueue_style('bootstrap',
            'https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css');
        wp_enqueue_style('fontawesome',
            'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css');
    }

    public function enqueue_scripts() {
        wp_enqueue_script($this->plugin_name,
            plugin_dir_url(__FILE__) . 'js/wp-control-acceso-public.js',
            array('jquery'), $this->version, false);

        wp_enqueue_script('bootstrap',
            'https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js',
            array('jquery'), '5.3.0', true);

        wp_localize_script($this->plugin_name, 'wpControlAcceso', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('wp_control_acceso_nonce'),
            'export_nonce_action' => 'wp_control_acceso_export_nonce',
            'export_nonce' => wp_create_nonce('wp_control_acceso_export_nonce')
        ));
    }

    /**
     * Shortcode para el formulario de registro
     */
    public function display_registro_form() {
        if (!is_user_logged_in()) {
            return '<div class="alert alert-warning">' . esc_html__('Debes iniciar sesión para registrar tu asistencia.', 'wp-control-acceso') . '</div>';
        }

        // Verificar si el usuario tiene el rol correcto
        if (!class_exists('WP_Control_Acceso')) {
            // This should ideally not happen if the plugin is loaded correctly.
            // Consider logging an error or returning a more generic error message.
            require_once WP_CONTROL_ACCESO_PLUGIN_DIR . 'includes/class-wp-control-acceso.php';
        }
        // The message "No tienes permisos para registrar asistencia." is already handled by WP_Control_Acceso::current_user_can
        // as it calls render_registro_shortcode from includes/class-wp-control-acceso.php, which has this message internationalized.
        // However, this method is also called directly by the shortcode definition in class-wp-control-acceso.php
        // which uses WP_Control_Acceso::current_user_can() which returns a boolean.
        // The message here is for direct calls or if the capability check in the main class changes.
        // It's better to keep the i18n string consistent.
        if (!WP_Control_Acceso::current_user_can('control_acceso_register_attendance')) {
            return '<div class="alert alert-warning">' . esc_html__('No tienes permisos para registrar asistencia.', 'wp-control-acceso') . '</div>';
        }

        $user_id = get_current_user_id();
        $registro_activo = $this->tiene_registro_activo($user_id);

        ob_start();
        include 'partials/registro-form.php';
        return ob_get_clean();
    }

    /**
     * Shortcode para el dashboard personal
     */
    public function display_dashboard() {
        if (!is_user_logged_in()) {
            return '<div class="alert alert-warning">' . esc_html__('Debes iniciar sesión para ver tu dashboard.', 'wp-control-acceso') . '</div>';
        }

        // Verificar si el usuario tiene el rol correcto
        if (!class_exists('WP_Control_Acceso')) {
            require_once WP_CONTROL_ACCESO_PLUGIN_DIR . 'includes/class-wp-control-acceso.php';
        }
        // Similar to display_registro_form, the message "No tienes permisos para ver el dashboard."
        // is handled by render_dashboard_shortcode in includes/class-wp-control-acceso.php.
        // Keeping it consistent here.
        if (!WP_Control_Acceso::current_user_can('control_acceso_register_attendance')) {
            return '<div class="alert alert-warning">' . esc_html__('No tienes permisos para ver el dashboard.', 'wp-control-acceso') . '</div>';
        }

        $user_id = get_current_user_id();
        $fecha_inicio = date('Y-m-d');
        $fecha_fin = date('Y-m-d');

        $registros_hoy = $this->registros->get_registros_por_fecha($fecha_inicio, $fecha_fin, $user_id);
        $horas_hoy = $this->registros->get_horas_por_dia($fecha_inicio, $fecha_fin, $user_id);
        $registro_activo = $this->tiene_registro_activo($user_id);

        ob_start();
        include 'partials/dashboard.php';
        return ob_get_clean();
    }

    /**
     * Verificar si el usuario tiene un registro activo
     */
    private function tiene_registro_activo($user_id) {
        global $wpdb;
        
        // Obtener el nombre correcto de la tabla
        $table_name = $wpdb->prefix . 'control_acceso_registros';
        
        // Construir la consulta
        $query = $wpdb->prepare("
            SELECT * FROM {$table_name}
            WHERE user_id = %d 
            AND hora_salida IS NULL
            AND DATE(hora_entrada) = CURDATE()
            ORDER BY hora_entrada DESC
            LIMIT 1
        ", $user_id);
        
        // Ejecutar la consulta
        $registro = $wpdb->get_row($query);
        
        // Si hay un error en la consulta, registrarlo
        if ($wpdb->last_error) {
            error_log('Error en tiene_registro_activo: ' . $wpdb->last_error);
            error_log('Query ejecutada: ' . $query);
            return null;
        }
        
        return $registro;
    }

    /**
     * Manejar registro de entrada vía AJAX
     */
    public function handle_registro_entrada() {
        check_ajax_referer('wp_control_acceso_nonce', 'nonce');

        if (!is_user_logged_in()) {
            wp_send_json_error(__('No has iniciado sesión', 'wp-control-acceso'));
        }

        // Verificar si el usuario tiene el rol correcto
        if (!class_exists('WP_Control_Acceso')) {
            require_once WP_CONTROL_ACCESO_PLUGIN_DIR . 'includes/class-wp-control-acceso.php';
        }
        if (!WP_Control_Acceso::current_user_can('control_acceso_register_attendance')) {
            wp_send_json_error(__('No tienes permisos para registrar asistencia', 'wp-control-acceso'));
        }

        $user_id = get_current_user_id();
        $resultado = $this->registros->registrar_entrada($user_id);

        if (is_wp_error($resultado)) {
            // Error messages from WP_Control_Acceso_Registros should also be internationalized.
            // Assuming they are, otherwise, they will appear in the original language.
            wp_send_json_error($resultado->get_error_message());
        }

        wp_send_json_success(__('Entrada registrada correctamente', 'wp-control-acceso'));
    }

    /**
     * Manejar registro de salida vía AJAX
     */
    public function handle_registro_salida() {
        check_ajax_referer('wp_control_acceso_nonce', 'nonce');

        if (!is_user_logged_in()) {
            wp_send_json_error(__('No has iniciado sesión', 'wp-control-acceso'));
        }

        // Verificar si el usuario tiene el rol correcto
        if (!class_exists('WP_Control_Acceso')) {
            require_once WP_CONTROL_ACCESO_PLUGIN_DIR . 'includes/class-wp-control-acceso.php';
        }
        if (!WP_Control_Acceso::current_user_can('control_acceso_register_attendance')) {
            wp_send_json_error(__('No tienes permisos para registrar asistencia', 'wp-control-acceso'));
        }

        $user_id = get_current_user_id();
        $resultado = $this->registros->registrar_salida($user_id);

        if (is_wp_error($resultado)) {
            // Error messages from WP_Control_Acceso_Registros should also be internationalized.
            wp_send_json_error($resultado->get_error_message());
        }

        wp_send_json_success(__('Salida registrada correctamente', 'wp-control-acceso'));
    }

    /**
     * Manejar exportación de registros vía AJAX
     */
    public function exportar_registros() {
        // Verificar nonce
        check_ajax_referer('wp_control_acceso_export_nonce', 'export_nonce');

        // Verificar si el usuario está logueado
        if (!is_user_logged_in()) {
            wp_send_json_error(
                array('message' => __('Debes iniciar sesión para exportar registros.', 'wp-control-acceso')), // Already internationalized
                403
            );
        }

        // Verificar permisos (reutilizando 'control_acceso_view_own_reports')
        // Asumiendo que WP_Control_Acceso::current_user_can() está disponible y es adecuada.
        // Si no, cargar la clase o usar current_user_can() directamente con la capacidad.
        if (!class_exists('WP_Control_Acceso')) {
            // Esto es una salvaguarda, la clase debería estar cargada.
            // Considera un mejor manejo si la clase no está disponible.
            require_once WP_CONTROL_ACCESO_PLUGIN_DIR . 'includes/class-wp-control-acceso.php';
        }

        if (!WP_Control_Acceso::current_user_can('control_acceso_view_own_reports')) {
            wp_send_json_error(
                array('message' => __('No tienes permiso para exportar estos registros.', 'wp-control-acceso')), // Already internationalized
                403
            );
        }

        $user_id = get_current_user_id();
        
        // Tipo de reporte para la exportación - 'detallado' como default para exportación personal
        // Esto podría ser un parámetro si se desea más flexibilidad.
        $tipo_reporte = 'detallado'; 
        
        // Fechas - para exportación personal, podría ser todo el historial o un rango específico.
        // Aquí asumimos que exportar_excel puede manejar un user_id sin fechas específicas,
        // o que se deben proporcionar fechas. Para este ejemplo, no pasaremos fechas,
        // asumiendo que exportar_excel para un user_id específico sin fechas devuelve todos sus registros.
        // Si se requieren fechas, deben obtenerse del request AJAX (ej: $_POST['fecha_inicio'], $_POST['fecha_fin'])
        // y ser validadas/sanitizadas.

        $registros_handler = new WP_Control_Acceso_Registros();

        // Llamar a exportar_excel.
        // La función exportar_excel en WP_Control_Acceso_Registros debe:
        // 1. Aceptar $user_id como parámetro.
        // 2. Manejar la generación del archivo y enviarlo al navegador.
        // 3. Terminar la ejecución con die() o exit().
        // Los parámetros $tipo, $fecha_inicio, $fecha_fin son requeridos por la función original.
        // Para una exportación personal, podríamos asumir tipo 'detallado' y un rango de fechas amplio o específico.
        // Por simplicidad, usaremos null para las fechas, asumiendo que la función puede manejarlo
        // o que se adaptará.
        
        // Nota: La firma original es exportar_excel($tipo, $fecha_inicio, $fecha_fin, $user_id = null)
        // Para una exportación personal, solo necesitamos el user_id.
        // Se debe asegurar que exportar_excel puede funcionar con $user_id y $tipo sin $fecha_inicio y $fecha_fin,
        // o que se le pasan valores por defecto (ej. todas las fechas).
        // Para este ejemplo, se asume que la función se adaptará o que se pasarán las fechas desde el AJAX si son necesarias.
        // Aquí, vamos a pasar null para las fechas, lo que implicaría que la función `exportar_excel`
        // debería tener defaults o lógica para manejar esto para un usuario específico.
        // O, más probablemente, el JS debería enviar el tipo de reporte y las fechas.
        // Para el propósito de esta tarea, seguiremos la instrucción de llamar con user_id.
        // Es probable que `exportar_excel` necesite ajuste o que el JS envíe más datos (tipo, fechas).

        // Suponiendo que el JS enviará 'tipo', 'fecha_inicio', 'fecha_fin'
        $tipo = isset($_POST['tipo_reporte']) ? sanitize_text_field($_POST['tipo_reporte']) : 'detallado';
        $fecha_inicio = isset($_POST['fecha_inicio']) ? sanitize_text_field($_POST['fecha_inicio']) : null;
        $fecha_fin = isset($_POST['fecha_fin']) ? sanitize_text_field($_POST['fecha_fin']) : null;

        // Validar fechas si son proporcionadas
        if ($fecha_inicio && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha_inicio)) {
            wp_send_json_error(array('message' => __('Formato de fecha de inicio inválido.', 'wp-control-acceso')), 400);
        }
        if ($fecha_fin && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha_fin)) {
            wp_send_json_error(array('message' => __('Formato de fecha de fin inválido.', 'wp-control-acceso')), 400);
        }
        
        // Asegurarse de que el tipo de reporte es válido
        $allowed_types = ['detallado', 'por_dia', 'totales']; // Ajustar según los tipos reales
        if (!in_array($tipo, $allowed_types)) {
            wp_send_json_error(array('message' => __('Tipo de reporte inválido.', 'wp-control-acceso')), 400);
        }

        // Si 'totales' es el tipo, el user_id no debería ser usado por la función original de exportar_excel
        // ya que get_horas_totales no filtra por user_id.
        // Sin embargo, para una "exportación personal", 'totales' para un solo usuario tiene sentido.
        // Esto implica que `exportar_excel` o los métodos de datos que llama deben ser consistentes.
        // Para `control_acceso_view_own_reports`, solo `detallado` y `por_dia` filtrados por user_id son seguros.
        if ($tipo === 'totales' && !WP_Control_Acceso::current_user_can('control_acceso_view_reports')) {
             wp_send_json_error(
                array('message' => __('No tienes permiso para exportar reportes totales.', 'wp-control-acceso')),
                403
            );
        }


        // The exportar_excel method now handles sending the file and then calls exit().
        // No further processing or JSON response should be sent from this handler.
        $registros_handler->exportar_excel($tipo, $fecha_inicio, $fecha_fin, $user_id);
        // Execution stops in exportar_excel. Code below this line will not be reached.
    }
}
