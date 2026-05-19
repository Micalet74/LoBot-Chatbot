<?php
/**
 * Plugin Name: OPTIBOT Chatbot
 * Plugin URI:  https://colegiodeopticos.es
 * Description: Chatbot con IA especializado en óptica y optometría para el Colegio de Ópticos de Valencia. Impulsado por Claude AI.
 * Version:     1.0.0
 * Author:      Colegio de Ópticos de Valencia
 * Author URI:  https://colegiodeopticos.es
 * License:     GPL-2.0+
 * Text Domain: optibot-chatbot
 * Domain Path: /languages
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

define( 'OPTIBOT_VERSION',     '1.0.0' );
define( 'OPTIBOT_PLUGIN_DIR',  plugin_dir_path( __FILE__ ) );
define( 'OPTIBOT_PLUGIN_URL',  plugin_dir_url( __FILE__ ) );
define( 'OPTIBOT_PLUGIN_FILE', __FILE__ );

require_once OPTIBOT_PLUGIN_DIR . 'includes/class-optibot-settings.php';
require_once OPTIBOT_PLUGIN_DIR . 'includes/class-optibot-api.php';
require_once OPTIBOT_PLUGIN_DIR . 'includes/class-optibot-crawler.php';
require_once OPTIBOT_PLUGIN_DIR . 'includes/class-optibot-colegiados.php';
require_once OPTIBOT_PLUGIN_DIR . 'admin/class-optibot-admin.php';
require_once OPTIBOT_PLUGIN_DIR . 'public/class-optibot-public.php';

function optibot_run() {
    $plugin_admin  = new Optibot_Admin();
    $plugin_public = new Optibot_Public();

    // Admin hooks
    add_action( 'admin_menu',            [ $plugin_admin, 'add_admin_menu' ] );
    add_action( 'admin_init',            [ $plugin_admin, 'register_settings' ] );
    add_action( 'admin_enqueue_scripts', [ $plugin_admin, 'enqueue_assets' ] );

    // Public hooks
    add_action( 'wp_enqueue_scripts',    [ $plugin_public, 'enqueue_assets' ] );
    add_action( 'wp_footer',             [ $plugin_public, 'render_chatbot' ] );

    // AJAX hooks (logged-in and non-logged-in users)
    add_action( 'wp_ajax_optibot_chat',        [ 'Optibot_API', 'handle_chat' ] );
    add_action( 'wp_ajax_nopriv_optibot_chat', [ 'Optibot_API', 'handle_chat' ] );

    // Colegiados AJAX (admin only)
    add_action( 'wp_ajax_optibot_get_colegiados', [ 'Optibot_Colegiados', 'ajax_get_colegiados' ] );
    add_action( 'wp_ajax_optibot_save_colegiado', [ 'Optibot_Colegiados', 'ajax_save_colegiado' ] );
    add_action( 'wp_ajax_optibot_delete_colegiado', [ 'Optibot_Colegiados', 'ajax_delete_colegiado' ] );
    add_action( 'wp_ajax_optibot_import_colegiados', [ 'Optibot_Colegiados', 'ajax_import_csv' ] );
}
add_action( 'plugins_loaded', 'optibot_run' );

// Activation / Deactivation
register_activation_hook(   __FILE__, 'optibot_activate' );
register_deactivation_hook( __FILE__, 'optibot_deactivate' );

function optibot_activate() {
    global $wpdb;
    $charset_collate = $wpdb->get_charset_collate();

    // Tabla de colegiados
    $table = $wpdb->prefix . 'optibot_colegiados';
    $sql = "CREATE TABLE IF NOT EXISTS $table (
        id           BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
        num_colegiado VARCHAR(50)  NOT NULL,
        nombre        VARCHAR(150) NOT NULL,
        apellidos     VARCHAR(200) NOT NULL,
        especialidad  VARCHAR(200) DEFAULT '',
        email         VARCHAR(150) DEFAULT '',
        telefono      VARCHAR(50)  DEFAULT '',
        direccion     TEXT         DEFAULT '',
        localidad     VARCHAR(150) DEFAULT '',
        provincia     VARCHAR(100) DEFAULT '',
        codigo_postal VARCHAR(10)  DEFAULT '',
        estado        VARCHAR(50)  DEFAULT 'activo',
        fecha_alta    DATE         DEFAULT NULL,
        created_at    DATETIME     DEFAULT CURRENT_TIMESTAMP,
        updated_at    DATETIME     DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        UNIQUE KEY num_colegiado (num_colegiado)
    ) $charset_collate;";

    require_once ABSPATH . 'wp-admin/includes/upgrade.php';
    dbDelta( $sql );

    // Tabla de historial de conversaciones
    $table2 = $wpdb->prefix . 'optibot_conversations';
    $sql2 = "CREATE TABLE IF NOT EXISTS $table2 (
        id           BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
        session_id   VARCHAR(100) NOT NULL,
        role         VARCHAR(20)  NOT NULL,
        content      LONGTEXT     NOT NULL,
        created_at   DATETIME     DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        KEY session_id (session_id)
    ) $charset_collate;";
    dbDelta( $sql2 );

    // Opciones por defecto
    $defaults = [
        'optibot_api_key'         => '',
        'optibot_bot_name'        => 'OPTIBOT',
        'optibot_welcome_message' => '¡Hola! Soy OPTIBOT, tu asistente especializado en óptica y optometría del Colegio de Ópticos de Valencia. ¿En qué puedo ayudarte?',
        'optibot_primary_color'   => '#005B9A',
        'optibot_secondary_color' => '#00A8CC',
        'optibot_text_color'      => '#ffffff',
        'optibot_bg_color'        => '#ffffff',
        'optibot_chat_bg_color'   => '#f0f4f8',
        'optibot_bubble_position' => 'bottom-right',
        'optibot_bubble_size'     => '60',
        'optibot_chat_width'      => '380',
        'optibot_chat_height'     => '520',
        'optibot_font_size'       => '14',
        'optibot_border_radius'   => '16',
        'optibot_show_pages'      => ['all'],
        'optibot_enable_web_search' => '1',
        'optibot_enable_colegiados' => '1',
        'optibot_enable_gov_info'   => '1',
        'optibot_max_tokens'        => '800',
        'optibot_avatar_icon'       => 'eye',
        'optibot_show_branding'     => '1',
    ];
    foreach ( $defaults as $key => $val ) {
        if ( false === get_option( $key ) ) {
            add_option( $key, $val );
        }
    }

    flush_rewrite_rules();
}

function optibot_deactivate() {
    flush_rewrite_rules();
}
