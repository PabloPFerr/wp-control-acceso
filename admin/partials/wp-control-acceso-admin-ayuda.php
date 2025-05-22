<?php
if (!defined('ABSPATH')) exit;
?>

<div class="wrap">
    <h1><i class="fas fa-question-circle"></i> <?php esc_html_e('Ayuda - Control de Acceso', 'wp-control-acceso'); ?></h1>

    <div class="card">
        <div class="card-header">
            <h2><i class="fas fa-book"></i> <?php esc_html_e('Guía de Uso', 'wp-control-acceso'); ?></h2>
        </div>
        <div class="card-body">
            <!-- Shortcodes -->
            <section class="mb-5">
                <h3><i class="fas fa-code"></i> <?php esc_html_e('Shortcodes Disponibles', 'wp-control-acceso'); ?></h3>
                <div class="table-responsive">
                    <table class="table table-bordered">
                        <thead>
                            <tr>
                                <th><?php esc_html_e('Shortcode', 'wp-control-acceso'); ?></th>
                                <th><?php esc_html_e('Descripción', 'wp-control-acceso'); ?></th>
                                <th><?php esc_html_e('Uso Recomendado', 'wp-control-acceso'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td><code>[control_acceso_registro]</code></td>
                                <td>
                                    <ul>
                                        <li><?php esc_html_e('Muestra el formulario de registro de entrada/salida', 'wp-control-acceso'); ?></li>
                                        <li><?php esc_html_e('Incluye estado actual del usuario', 'wp-control-acceso'); ?></li>
                                        <li><?php esc_html_e('Botones para registrar entrada y salida', 'wp-control-acceso'); ?></li>
                                    </ul>
                                </td>
                                <td><?php esc_html_e('Colocar en una página dedicada al registro de asistencia', 'wp-control-acceso'); ?></td>
                            </tr>
                            <tr>
                                <td><code>[control_acceso_dashboard]</code></td>
                                <td>
                                    <ul>
                                        <li><?php esc_html_e('Muestra el dashboard personal del usuario', 'wp-control-acceso'); ?></li>
                                        <li><?php esc_html_e('Estadísticas del día actual', 'wp-control-acceso'); ?></li>
                                        <li><?php esc_html_e('Historial de registros del día', 'wp-control-acceso'); ?></li>
                                    </ul>
                                </td>
                                <td><?php esc_html_e('Ideal para la página principal del usuario o área personal', 'wp-control-acceso'); ?></td>
                            </tr>
                            <tr>
                                <td><code>[control_acceso_mis_reportes]</code></td>
                                <td>
                                    <ul>
                                        <li><?php esc_html_e('Sistema de reportes personales', 'wp-control-acceso'); ?></li>
                                        <li><?php esc_html_e('Filtros por fecha', 'wp-control-acceso'); ?></li>
                                        <li><?php esc_html_e('Diferentes tipos de reportes', 'wp-control-acceso'); ?></li>
                                        <li><?php esc_html_e('Exportación a Excel', 'wp-control-acceso'); ?></li>
                                    </ul>
                                </td>
                                <td><?php esc_html_e('Página dedicada a reportes y estadísticas personales', 'wp-control-acceso'); ?></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </section>

            <!-- Configuración Recomendada -->
            <section class="mb-5">
                <h3><i class="fas fa-cogs"></i> <?php esc_html_e('Configuración Recomendada', 'wp-control-acceso'); ?></h3>
                <div class="alert alert-info">
                    <h4><?php esc_html_e('Estructura de Páginas Sugerida', 'wp-control-acceso'); ?></h4>
                    <ol>
                        <li><?php printf(esc_html__('Crear una página "%1$s" con el shortcode %2$s', 'wp-control-acceso'), esc_html__('Registro de Asistencia', 'wp-control-acceso'), '<code>[control_acceso_registro]</code>'); ?></li>
                        <li><?php printf(esc_html__('Crear una página "%1$s" con el shortcode %2$s', 'wp-control-acceso'), esc_html__('Mi Dashboard', 'wp-control-acceso'), '<code>[control_acceso_dashboard]</code>'); ?></li>
                        <li><?php printf(esc_html__('Crear una página "%1$s" con el shortcode %2$s', 'wp-control-acceso'), esc_html__('Mis Reportes', 'wp-control-acceso'), '<code>[control_acceso_mis_reportes]</code>'); ?></li>
                    </ol>
                </div>
            </section>

            <!-- Panel de Administración -->
            <section class="mb-5">
                <h3><i class="fas fa-user-shield"></i> <?php esc_html_e('Panel de Administración', 'wp-control-acceso'); ?></h3>
                <div class="row">
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header">
                                <h4><?php esc_html_e('Dashboard Admin', 'wp-control-acceso'); ?></h4>
                            </div>
                            <div class="card-body">
                                <ul>
                                    <li><?php esc_html_e('Vista general de todos los usuarios', 'wp-control-acceso'); ?></li>
                                    <li><?php esc_html_e('Estadísticas globales', 'wp-control-acceso'); ?></li>
                                    <li><?php esc_html_e('Registros del día actual', 'wp-control-acceso'); ?></li>
                                    <li><?php esc_html_e('Estado de todos los usuarios', 'wp-control-acceso'); ?></li>
                                </ul>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header">
                                <h4><?php esc_html_e('Reportes Administrativos', 'wp-control-acceso'); ?></h4>
                            </div>
                            <div class="card-body">
                                <ul>
                                    <li><?php esc_html_e('Reportes de todos los usuarios', 'wp-control-acceso'); ?></li>
                                    <li><?php esc_html_e('Filtros avanzados', 'wp-control-acceso'); ?></li>
                                    <li><?php esc_html_e('Exportación a Excel', 'wp-control-acceso'); ?></li>
                                    <li><?php esc_html_e('Estadísticas detalladas', 'wp-control-acceso'); ?></li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <!-- FAQ -->
            <section class="mb-5">
                <h3><i class="fas fa-question"></i> <?php esc_html_e('Preguntas Frecuentes', 'wp-control-acceso'); ?></h3>
                <div class="accordion" id="faqAccordion">
                    <div class="accordion-item">
                        <h2 class="accordion-header">
                            <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#faq1">
                                <?php esc_html_e('¿Cómo agrego los shortcodes a una página?', 'wp-control-acceso'); ?>
                            </button>
                        </h2>
                        <div id="faq1" class="accordion-collapse collapse show">
                            <div class="accordion-body">
                                <ol>
                                    <li><?php esc_html_e('Ve a Pages > Add New en WordPress', 'wp-control-acceso'); ?></li>
                                    <li><?php esc_html_e('Dale un título a tu página', 'wp-control-acceso'); ?></li>
                                    <li><?php esc_html_e('Agrega un bloque "Shortcode"', 'wp-control-acceso'); ?></li>
                                    <li><?php esc_html_e('Pega el shortcode deseado', 'wp-control-acceso'); ?></li>
                                    <li><?php esc_html_e('Publica la página', 'wp-control-acceso'); ?></li>
                                </ol>
                            </div>
                        </div>
                    </div>
                    <div class="accordion-item">
                        <h2 class="accordion-header">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq2">
                                <?php esc_html_e('¿Cómo funciona el registro de horas?', 'wp-control-acceso'); ?>
                            </button>
                        </h2>
                        <div id="faq2" class="accordion-collapse collapse">
                            <div class="accordion-body">
                                <ul>
                                    <li><?php esc_html_e('El usuario registra su entrada al llegar', 'wp-control-acceso'); ?></li>
                                    <li><?php esc_html_e('El sistema guarda la hora exacta', 'wp-control-acceso'); ?></li>
                                    <li><?php esc_html_e('Al salir, registra su salida', 'wp-control-acceso'); ?></li>
                                    <li><?php esc_html_e('El sistema calcula automáticamente las horas trabajadas', 'wp-control-acceso'); ?></li>
                                </ul>
                            </div>
                        </div>
                    </div>
                    <div class="accordion-item">
                        <h2 class="accordion-header">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq3">
                                <?php esc_html_e('¿Cómo exporto los reportes?', 'wp-control-acceso'); ?>
                            </button>
                        </h2>
                        <div id="faq3" class="accordion-collapse collapse">
                            <div class="accordion-body">
                                <ol>
                                    <li><?php esc_html_e('Ve a la sección de reportes', 'wp-control-acceso'); ?></li>
                                    <li><?php esc_html_e('Aplica los filtros deseados', 'wp-control-acceso'); ?></li>
                                    <li><?php printf(esc_html__('Haz clic en "%s"', 'wp-control-acceso'), esc_html__('Exportar a Excel', 'wp-control-acceso')); ?></li>
                                    <li><?php esc_html_e('Se descargará un archivo Excel con los datos', 'wp-control-acceso'); ?></li>
                                </ol>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Soporte -->
            <section>
                <h3><i class="fas fa-life-ring"></i> <?php esc_html_e('Soporte', 'wp-control-acceso'); ?></h3>
                <div class="alert alert-success">
                    <h4><?php esc_html_e('¿Necesitas ayuda adicional?', 'wp-control-acceso'); ?></h4>
                    <p><?php esc_html_e('Si tienes dudas o problemas con el plugin, puedes:', 'wp-control-acceso'); ?></p>
                    <ul>
                        <li><?php esc_html_e('Revisar la documentación completa en línea', 'wp-control-acceso'); ?></li>
                        <li><?php esc_html_e('Contactar al soporte técnico', 'wp-control-acceso'); ?></li>
                        <li><?php esc_html_e('Reportar problemas en el repositorio del plugin', 'wp-control-acceso'); ?></li>
                    </ul>
                </div>
            </section>
        </div>
    </div>
</div>
