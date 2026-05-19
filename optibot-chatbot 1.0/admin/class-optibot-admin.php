<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class Optibot_Admin {

    public function add_admin_menu() {
        add_menu_page(
            'OPTIBOT Chatbot',
            'OPTIBOT',
            'manage_options',
            'optibot-settings',
            [ $this, 'page_settings' ],
            'dashicons-format-chat',
            80
        );

        add_submenu_page( 'optibot-settings', 'Configuración General', 'Configuración',     'manage_options', 'optibot-settings',    [ $this, 'page_settings' ] );
        add_submenu_page( 'optibot-settings', 'Apariencia',            'Apariencia',        'manage_options', 'optibot-appearance',   [ $this, 'page_appearance' ] );
        add_submenu_page( 'optibot-settings', 'Colegiados',            'Colegiados',        'manage_options', 'optibot-colegiados',   [ $this, 'page_colegiados' ] );
        add_submenu_page( 'optibot-settings', 'Estadísticas',          'Estadísticas',      'manage_options', 'optibot-stats',        [ $this, 'page_stats' ] );
    }

    public function register_settings() {
        $fields = [
            'optibot_api_key', 'optibot_bot_name', 'optibot_welcome_message',
            'optibot_primary_color', 'optibot_secondary_color', 'optibot_text_color',
            'optibot_bg_color', 'optibot_chat_bg_color', 'optibot_bubble_position',
            'optibot_bubble_size', 'optibot_chat_width', 'optibot_chat_height',
            'optibot_font_size', 'optibot_border_radius', 'optibot_show_pages',
            'optibot_enable_web_search', 'optibot_enable_colegiados', 'optibot_enable_gov_info',
            'optibot_max_tokens', 'optibot_avatar_icon', 'optibot_show_branding',
        ];
        foreach ( $fields as $field ) {
            register_setting( 'optibot_options', $field );
        }
    }

    public function enqueue_assets( $hook ) {
        if ( strpos( $hook, 'optibot' ) === false ) return;

        wp_enqueue_style(  'optibot-admin-css', OPTIBOT_PLUGIN_URL . 'admin/admin.css', [], OPTIBOT_VERSION );
        wp_enqueue_script( 'optibot-admin-js',  OPTIBOT_PLUGIN_URL . 'admin/admin.js',  [ 'jquery', 'wp-color-picker' ], OPTIBOT_VERSION, true );
        wp_enqueue_style( 'wp-color-picker' );

        wp_localize_script( 'optibot-admin-js', 'optibotAdmin', [
            'ajax_url' => admin_url( 'admin-ajax.php' ),
            'nonce'    => wp_create_nonce( 'optibot_admin_nonce' ),
        ] );
    }

    /* -------- PAGE: SETTINGS -------- */
    public function page_settings() {
        $s = Optibot_Settings::get_all();
        ?>
        <div class="wrap optibot-admin-wrap">
            <?php $this->render_header( 'Configuración General' ); ?>
            <form method="post" action="options.php" class="optibot-form">
                <?php settings_fields( 'optibot_options' ); ?>

                <div class="optibot-card">
                    <h2><span class="dashicons dashicons-admin-network"></span> API de Inteligencia Artificial</h2>
                    <p class="description">OPTIBOT utiliza la API de Claude (Anthropic). <a href="https://console.anthropic.com" target="_blank">Obtener API Key →</a></p>

                    <table class="form-table">
                        <tr>
                            <th><label for="optibot_api_key">API Key de Anthropic *</label></th>
                            <td>
                                <input type="password" id="optibot_api_key" name="optibot_api_key"
                                       value="<?php echo esc_attr( $s['api_key'] ); ?>"
                                       class="regular-text" autocomplete="new-password" />
                                <p class="description">Tu clave de API de Anthropic (sk-ant-...).</p>
                            </td>
                        </tr>
                        <tr>
                            <th><label for="optibot_max_tokens">Longitud máxima de respuesta</label></th>
                            <td>
                                <input type="number" id="optibot_max_tokens" name="optibot_max_tokens"
                                       value="<?php echo esc_attr( $s['max_tokens'] ); ?>"
                                       min="200" max="1500" class="small-text" />
                                <p class="description">Tokens de respuesta (200-1500). Recomendado: 800.</p>
                            </td>
                        </tr>
                    </table>
                </div>

                <div class="optibot-card">
                    <h2><span class="dashicons dashicons-admin-users"></span> Personalidad del Bot</h2>
                    <table class="form-table">
                        <tr>
                            <th><label for="optibot_bot_name">Nombre del chatbot</label></th>
                            <td>
                                <input type="text" id="optibot_bot_name" name="optibot_bot_name"
                                       value="<?php echo esc_attr( $s['bot_name'] ); ?>"
                                       class="regular-text" maxlength="40" />
                            </td>
                        </tr>
                        <tr>
                            <th><label for="optibot_welcome_message">Mensaje de bienvenida</label></th>
                            <td>
                                <textarea id="optibot_welcome_message" name="optibot_welcome_message"
                                          rows="3" class="large-text"><?php echo esc_textarea( $s['welcome_message'] ); ?></textarea>
                            </td>
                        </tr>
                        <tr>
                            <th>Icono del avatar</th>
                            <td>
                                <select name="optibot_avatar_icon">
                                    <?php
                                    $icons = [ 'eye' => '👁 Ojo', 'glasses' => '👓 Gafas', 'robot' => '🤖 Robot', 'star' => '⭐ Estrella' ];
                                    foreach ( $icons as $val => $label ) {
                                        echo '<option value="' . esc_attr( $val ) . '"' . selected( $s['avatar_icon'], $val, false ) . '>' . esc_html( $label ) . '</option>';
                                    }
                                    ?>
                                </select>
                            </td>
                        </tr>
                    </table>
                </div>

                <div class="optibot-card">
                    <h2><span class="dashicons dashicons-admin-plugins"></span> Funcionalidades</h2>
                    <table class="form-table">
                        <tr>
                            <th>Acceso al directorio de colegiados</th>
                            <td>
                                <label>
                                    <input type="checkbox" name="optibot_enable_colegiados" value="1"
                                           <?php checked( $s['enable_colegiados'], '1' ); ?> />
                                    Permitir consultas sobre colegiados
                                </label>
                            </td>
                        </tr>
                        <tr>
                            <th>Información gubernamental</th>
                            <td>
                                <label>
                                    <input type="checkbox" name="optibot_enable_gov_info" value="1"
                                           <?php checked( $s['enable_gov_info'], '1' ); ?> />
                                    Incluir fuentes normativas y administrativas en el contexto
                                </label>
                            </td>
                        </tr>
                        <tr>
                            <th>Visibilidad del chatbot</th>
                            <td>
                                <select name="optibot_show_pages">
                                    <option value="all"  <?php selected( $s['show_pages'], 'all' ); ?>>En todas las páginas</option>
                                    <option value="home" <?php selected( $s['show_pages'], 'home' ); ?>>Solo en la portada</option>
                                    <option value="none" <?php selected( $s['show_pages'], 'none' ); ?>>Desactivado</option>
                                </select>
                            </td>
                        </tr>
                        <tr>
                            <th>Mostrar créditos "Powered by"</th>
                            <td>
                                <label>
                                    <input type="checkbox" name="optibot_show_branding" value="1"
                                           <?php checked( $s['show_branding'], '1' ); ?> />
                                    Mostrar "Powered by OPTIBOT"
                                </label>
                            </td>
                        </tr>
                    </table>
                </div>

                <?php submit_button( 'Guardar configuración', 'primary large' ); ?>
            </form>
        </div>
        <?php
    }

    /* -------- PAGE: APPEARANCE -------- */
    public function page_appearance() {
        $s = Optibot_Settings::get_all();
        ?>
        <div class="wrap optibot-admin-wrap">
            <?php $this->render_header( 'Apariencia del Chatbot' ); ?>

            <div class="optibot-appearance-layout">
                <div class="optibot-form-col">
                    <form method="post" action="options.php" class="optibot-form" id="optibot-appearance-form">
                        <?php settings_fields( 'optibot_options' ); ?>

                        <div class="optibot-card">
                            <h2><span class="dashicons dashicons-art"></span> Colores</h2>
                            <table class="form-table">
                                <tr>
                                    <th><label>Color principal (cabecera / burbuja)</label></th>
                                    <td><input type="text" name="optibot_primary_color" value="<?php echo esc_attr( $s['primary_color'] ); ?>" class="optibot-color-picker" /></td>
                                </tr>
                                <tr>
                                    <th><label>Color secundario (burbujas del bot)</label></th>
                                    <td><input type="text" name="optibot_secondary_color" value="<?php echo esc_attr( $s['secondary_color'] ); ?>" class="optibot-color-picker" /></td>
                                </tr>
                                <tr>
                                    <th><label>Color del texto en cabecera / burbuja</label></th>
                                    <td><input type="text" name="optibot_text_color" value="<?php echo esc_attr( $s['text_color'] ); ?>" class="optibot-color-picker" /></td>
                                </tr>
                                <tr>
                                    <th><label>Color fondo del chat</label></th>
                                    <td><input type="text" name="optibot_chat_bg_color" value="<?php echo esc_attr( $s['chat_bg_color'] ); ?>" class="optibot-color-picker" /></td>
                                </tr>
                                <tr>
                                    <th><label>Color fondo ventana</label></th>
                                    <td><input type="text" name="optibot_bg_color" value="<?php echo esc_attr( $s['bg_color'] ); ?>" class="optibot-color-picker" /></td>
                                </tr>
                            </table>
                        </div>

                        <div class="optibot-card">
                            <h2><span class="dashicons dashicons-editor-expand"></span> Tamaños y Posición</h2>
                            <table class="form-table">
                                <tr>
                                    <th><label>Posición en pantalla</label></th>
                                    <td>
                                        <select name="optibot_bubble_position" id="optibot_bubble_position">
                                            <option value="bottom-right" <?php selected( $s['position'], 'bottom-right' ); ?>>Abajo a la derecha</option>
                                            <option value="bottom-left"  <?php selected( $s['position'], 'bottom-left' ); ?>>Abajo a la izquierda</option>
                                        </select>
                                    </td>
                                </tr>
                                <tr>
                                    <th><label>Tamaño del botón (px)</label></th>
                                    <td>
                                        <input type="range" name="optibot_bubble_size" id="optibot_bubble_size"
                                               min="44" max="80" value="<?php echo esc_attr( $s['bubble_size'] ); ?>" />
                                        <span id="bubble_size_val"><?php echo esc_html( $s['bubble_size'] ); ?>px</span>
                                    </td>
                                </tr>
                                <tr>
                                    <th><label>Ancho del chat (px)</label></th>
                                    <td>
                                        <input type="range" name="optibot_chat_width" id="optibot_chat_width"
                                               min="280" max="520" value="<?php echo esc_attr( $s['chat_width'] ); ?>" />
                                        <span id="chat_width_val"><?php echo esc_html( $s['chat_width'] ); ?>px</span>
                                    </td>
                                </tr>
                                <tr>
                                    <th><label>Alto del chat (px)</label></th>
                                    <td>
                                        <input type="range" name="optibot_chat_height" id="optibot_chat_height"
                                               min="360" max="700" value="<?php echo esc_attr( $s['chat_height'] ); ?>" />
                                        <span id="chat_height_val"><?php echo esc_html( $s['chat_height'] ); ?>px</span>
                                    </td>
                                </tr>
                                <tr>
                                    <th><label>Tamaño de fuente (px)</label></th>
                                    <td>
                                        <input type="range" name="optibot_font_size" id="optibot_font_size"
                                               min="12" max="18" value="<?php echo esc_attr( $s['font_size'] ); ?>" />
                                        <span id="font_size_val"><?php echo esc_html( $s['font_size'] ); ?>px</span>
                                    </td>
                                </tr>
                                <tr>
                                    <th><label>Radio de bordes (px)</label></th>
                                    <td>
                                        <input type="range" name="optibot_border_radius" id="optibot_border_radius"
                                               min="0" max="30" value="<?php echo esc_attr( $s['border_radius'] ); ?>" />
                                        <span id="border_radius_val"><?php echo esc_html( $s['border_radius'] ); ?>px</span>
                                    </td>
                                </tr>
                            </table>
                        </div>

                        <?php submit_button( 'Guardar apariencia', 'primary large' ); ?>
                    </form>
                </div>

                <div class="optibot-preview-col">
                    <div class="optibot-card optibot-preview-card">
                        <h2><span class="dashicons dashicons-visibility"></span> Vista previa</h2>
                        <div id="optibot-live-preview">
                            <?php $this->render_preview( $s ); ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }

    /* -------- PAGE: COLEGIADOS -------- */
    public function page_colegiados() {
        $stats = Optibot_Colegiados::get_stats();
        ?>
        <div class="wrap optibot-admin-wrap">
            <?php $this->render_header( 'Gestión de Colegiados' ); ?>

            <div class="optibot-stats-bar">
                <span class="stat-item"><strong><?php echo esc_html( $stats['total'] ); ?></strong> Total</span>
                <span class="stat-item stat-green"><strong><?php echo esc_html( $stats['activos'] ); ?></strong> Activos</span>
            </div>

            <div class="optibot-card">
                <div class="optibot-toolbar">
                    <input type="text" id="optibot-search-col" placeholder="Buscar por nombre, número, localidad..." class="regular-text" />
                    <button class="button button-primary" id="optibot-add-colegiado">
                        <span class="dashicons dashicons-plus-alt"></span> Nuevo colegiado
                    </button>
                    <label class="button">
                        <span class="dashicons dashicons-upload"></span> Importar CSV
                        <input type="file" id="optibot-csv-upload" accept=".csv" style="display:none" />
                    </label>
                    <a href="#" id="optibot-download-template" class="button">
                        <span class="dashicons dashicons-download"></span> Plantilla CSV
                    </a>
                </div>

                <div id="optibot-colegiados-table-wrap">
                    <table class="wp-list-table widefat fixed striped" id="optibot-colegiados-table">
                        <thead>
                            <tr>
                                <th>Nº Colegiado</th>
                                <th>Nombre completo</th>
                                <th>Especialidad</th>
                                <th>Localidad</th>
                                <th>Estado</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody id="optibot-colegiados-tbody">
                            <tr><td colspan="6" class="loading-row">Cargando...</td></tr>
                        </tbody>
                    </table>
                    <div id="optibot-pagination"></div>
                </div>
            </div>

            <!-- Modal -->
            <div id="optibot-modal" class="optibot-modal" style="display:none">
                <div class="optibot-modal-inner">
                    <div class="optibot-modal-header">
                        <h3 id="optibot-modal-title">Colegiado</h3>
                        <button class="optibot-modal-close">✕</button>
                    </div>
                    <div class="optibot-modal-body">
                        <input type="hidden" id="col-id" value="" />
                        <div class="optibot-form-grid">
                            <div class="form-field">
                                <label>Nº Colegiado *</label>
                                <input type="text" id="col-num" placeholder="COL-001" />
                            </div>
                            <div class="form-field">
                                <label>Estado</label>
                                <select id="col-estado">
                                    <option value="activo">Activo</option>
                                    <option value="inactivo">Inactivo</option>
                                    <option value="baja">Baja</option>
                                </select>
                            </div>
                            <div class="form-field">
                                <label>Nombre *</label>
                                <input type="text" id="col-nombre" />
                            </div>
                            <div class="form-field">
                                <label>Apellidos *</label>
                                <input type="text" id="col-apellidos" />
                            </div>
                            <div class="form-field">
                                <label>Especialidad</label>
                                <input type="text" id="col-especialidad" placeholder="Optometría, Audiología..." />
                            </div>
                            <div class="form-field">
                                <label>Fecha de alta</label>
                                <input type="date" id="col-fecha-alta" />
                            </div>
                            <div class="form-field">
                                <label>Email profesional</label>
                                <input type="email" id="col-email" />
                            </div>
                            <div class="form-field">
                                <label>Teléfono</label>
                                <input type="text" id="col-telefono" />
                            </div>
                            <div class="form-field form-field-full">
                                <label>Dirección</label>
                                <input type="text" id="col-direccion" />
                            </div>
                            <div class="form-field">
                                <label>Localidad</label>
                                <input type="text" id="col-localidad" />
                            </div>
                            <div class="form-field">
                                <label>Provincia</label>
                                <input type="text" id="col-provincia" value="Valencia" />
                            </div>
                            <div class="form-field">
                                <label>Código postal</label>
                                <input type="text" id="col-cp" maxlength="5" />
                            </div>
                        </div>
                    </div>
                    <div class="optibot-modal-footer">
                        <button class="button" id="optibot-modal-cancel">Cancelar</button>
                        <button class="button button-primary" id="optibot-modal-save">Guardar</button>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }

    /* -------- PAGE: STATS -------- */
    public function page_stats() {
        global $wpdb;
        $conv_table  = $wpdb->prefix . 'optibot_conversations';
        $total_msgs  = intval( $wpdb->get_var( "SELECT COUNT(*) FROM $conv_table" ) );
        $total_sess  = intval( $wpdb->get_var( "SELECT COUNT(DISTINCT session_id) FROM $conv_table" ) );
        $today_msgs  = intval( $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM $conv_table WHERE DATE(created_at) = %s AND role='user'", current_time('Y-m-d') ) ) );
        $stats_col   = Optibot_Colegiados::get_stats();
        ?>
        <div class="wrap optibot-admin-wrap">
            <?php $this->render_header( 'Estadísticas' ); ?>
            <div class="optibot-stats-grid">
                <div class="optibot-stat-card">
                    <div class="stat-icon" style="background:#005B9A">💬</div>
                    <div class="stat-info"><span class="stat-num"><?php echo esc_html( $total_msgs ); ?></span><span class="stat-label">Mensajes totales</span></div>
                </div>
                <div class="optibot-stat-card">
                    <div class="stat-icon" style="background:#00A8CC">🔗</div>
                    <div class="stat-info"><span class="stat-num"><?php echo esc_html( $total_sess ); ?></span><span class="stat-label">Sesiones únicas</span></div>
                </div>
                <div class="optibot-stat-card">
                    <div class="stat-icon" style="background:#27ae60">📅</div>
                    <div class="stat-info"><span class="stat-num"><?php echo esc_html( $today_msgs ); ?></span><span class="stat-label">Consultas hoy</span></div>
                </div>
                <div class="optibot-stat-card">
                    <div class="stat-icon" style="background:#8e44ad">👥</div>
                    <div class="stat-info"><span class="stat-num"><?php echo esc_html( $stats_col['activos'] ); ?></span><span class="stat-label">Colegiados activos</span></div>
                </div>
            </div>
        </div>
        <?php
    }

    /* -------- HELPERS -------- */
    private function render_header( $title ) {
        ?>
        <div class="optibot-admin-header">
            <div class="optibot-logo">
                <span class="optibot-logo-icon">👁</span>
                <div>
                    <strong>OPTIBOT</strong>
                    <small>Colegio de Ópticos de Valencia</small>
                </div>
            </div>
            <h1><?php echo esc_html( $title ); ?></h1>
        </div>
        <?php
    }

    private function render_preview( $s ) {
        ?>
        <div class="optibot-preview-chat" style="
            width: <?php echo esc_attr( min( $s['chat_width'], 340 ) ); ?>px;
            border-radius: <?php echo esc_attr( $s['border_radius'] ); ?>px;
            font-size: <?php echo esc_attr( $s['font_size'] ); ?>px;
            background: <?php echo esc_attr( $s['bg_color'] ); ?>;
        ">
            <div class="preview-header" style="background:<?php echo esc_attr( $s['primary_color'] ); ?>; color:<?php echo esc_attr( $s['text_color'] ); ?>; border-radius:<?php echo esc_attr( $s['border_radius'] ); ?>px <?php echo esc_attr( $s['border_radius'] ); ?>px 0 0;">
                <span>👁 <?php echo esc_html( $s['bot_name'] ); ?></span>
                <span style="opacity:.7">✕</span>
            </div>
            <div class="preview-messages" style="background:<?php echo esc_attr( $s['chat_bg_color'] ); ?>">
                <div class="preview-bubble bot" style="background:<?php echo esc_attr( $s['secondary_color'] ); ?>; color:#fff;">
                    <?php echo esc_html( wp_trim_words( $s['welcome_message'], 12 ) ); ?>
                </div>
                <div class="preview-bubble user" style="background:<?php echo esc_attr( $s['primary_color'] ); ?>; color:<?php echo esc_attr( $s['text_color'] ); ?>;">
                    ¿Cómo me colegio?
                </div>
                <div class="preview-bubble bot" style="background:<?php echo esc_attr( $s['secondary_color'] ); ?>; color:#fff;">
                    Para colegiarte necesitas...
                </div>
            </div>
            <div class="preview-input">
                <input type="text" placeholder="Escribe tu pregunta..." disabled />
                <button style="background:<?php echo esc_attr( $s['primary_color'] ); ?>; color:<?php echo esc_attr( $s['text_color'] ); ?>;">➤</button>
            </div>
        </div>
        <?php
    }
}
