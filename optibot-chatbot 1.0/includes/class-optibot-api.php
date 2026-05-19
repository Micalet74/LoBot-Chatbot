<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class Optibot_API {

    public static function handle_chat() {
        check_ajax_referer( 'optibot_nonce', 'nonce' );

        $message    = isset( $_POST['message'] )    ? sanitize_text_field( wp_unslash( $_POST['message'] ) )    : '';
        $session_id = isset( $_POST['session_id'] ) ? sanitize_text_field( wp_unslash( $_POST['session_id'] ) ) : '';
        $history    = isset( $_POST['history'] )    ? json_decode( stripslashes( $_POST['history'] ), true )    : [];

        if ( empty( $message ) ) {
            wp_send_json_error( [ 'message' => 'Mensaje vacío.' ] );
        }

        $api_key = Optibot_Settings::get( 'optibot_api_key' );
        if ( empty( $api_key ) ) {
            wp_send_json_error( [ 'message' => 'El chatbot no está configurado correctamente. Contacta con el administrador.' ] );
        }

        // Enrich with colegiados data if query seems related
        $colegiado_context = '';
        if ( Optibot_Settings::get( 'optibot_enable_colegiados', '1' ) === '1' ) {
            $colegiado_context = self::get_colegiado_context( $message );
        }

        // Build messages array
        $messages = self::build_messages( $history, $message, $colegiado_context );

        // Call Claude API
        $response = self::call_claude( $api_key, $messages );

        if ( is_wp_error( $response ) ) {
            wp_send_json_error( [ 'message' => $response->get_error_message() ] );
        }

        // Save conversation
        if ( ! empty( $session_id ) ) {
            self::save_conversation( $session_id, 'user',      $message );
            self::save_conversation( $session_id, 'assistant', $response );
        }

        wp_send_json_success( [ 'message' => $response ] );
    }

    private static function build_messages( $history, $new_message, $extra_context = '' ) {
        $messages = [];

        // Include last 6 exchanges for context (keep tokens low)
        if ( is_array( $history ) ) {
            $history = array_slice( $history, -12 );
            foreach ( $history as $entry ) {
                if ( isset( $entry['role'] ) && isset( $entry['content'] ) ) {
                    $role    = in_array( $entry['role'], [ 'user', 'assistant' ] ) ? $entry['role'] : 'user';
                    $content = sanitize_textarea_field( $entry['content'] );
                    $messages[] = [ 'role' => $role, 'content' => $content ];
                }
            }
        }

        $user_content = $new_message;
        if ( ! empty( $extra_context ) ) {
            $user_content .= "\n\n[CONTEXTO INTERNO - Datos del directorio de colegiados]:\n" . $extra_context;
        }

        $messages[] = [ 'role' => 'user', 'content' => $user_content ];

        return $messages;
    }

    private static function call_claude( $api_key, $messages ) {
        $max_tokens  = intval( Optibot_Settings::get( 'optibot_max_tokens', '800' ) );
        $system      = Optibot_Settings::get_system_prompt();

        // Add current web page context
        $page_context = self::get_page_context();
        if ( ! empty( $page_context ) ) {
            $system .= "\n\nCONTEXTO DE LA PÁGINA WEB ACTUAL:\n" . $page_context;
        }

        $body = wp_json_encode( [
            'model'      => 'claude-sonnet-4-20250514',
            'max_tokens' => max( 200, min( $max_tokens, 1500 ) ),
            'system'     => $system,
            'messages'   => $messages,
        ] );

        $response = wp_remote_post( 'https://api.anthropic.com/v1/messages', [
            'headers' => [
                'x-api-key'         => $api_key,
                'anthropic-version' => '2023-06-01',
                'content-type'      => 'application/json',
            ],
            'body'    => $body,
            'timeout' => 45,
        ] );

        if ( is_wp_error( $response ) ) {
            return new WP_Error( 'api_error', 'Error de conexión con el servicio de IA: ' . $response->get_error_message() );
        }

        $code = wp_remote_retrieve_response_code( $response );
        $data = json_decode( wp_remote_retrieve_body( $response ), true );

        if ( $code !== 200 ) {
            $err = isset( $data['error']['message'] ) ? $data['error']['message'] : 'Error desconocido.';
            return new WP_Error( 'api_error', 'Error de la API: ' . $err );
        }

        if ( isset( $data['content'][0]['text'] ) ) {
            return $data['content'][0]['text'];
        }

        return new WP_Error( 'api_error', 'Respuesta inesperada de la API.' );
    }

    private static function get_colegiado_context( $message ) {
        // Detect if the query is about a specific colegiado
        $keywords = [ 'colegiado', 'óptico', 'optometrista', 'profesional', 'buscar', 'encontrar', 'número', 'verificar', 'colegiación' ];
        $message_lower = mb_strtolower( $message );
        $is_relevant   = false;

        foreach ( $keywords as $kw ) {
            if ( strpos( $message_lower, $kw ) !== false ) {
                $is_relevant = true;
                break;
            }
        }

        if ( ! $is_relevant ) return '';

        return Optibot_Colegiados::search_for_context( $message );
    }

    private static function get_page_context() {
        // This is called server-side; the page URL/title comes from the AJAX referrer
        $referer = wp_get_referer();
        if ( empty( $referer ) ) return '';

        $context = "El usuario está navegando en: {$referer}\n";

        // Try to get post content if same domain
        $post_id = url_to_postid( $referer );
        if ( $post_id ) {
            $post = get_post( $post_id );
            if ( $post ) {
                $excerpt = wp_trim_words( wp_strip_all_tags( $post->post_content ), 80, '...' );
                $context .= "Título de la página: " . get_the_title( $post_id ) . "\n";
                $context .= "Contenido relevante: " . $excerpt . "\n";
            }
        }

        return $context;
    }

    private static function save_conversation( $session_id, $role, $content ) {
        global $wpdb;
        $wpdb->insert(
            $wpdb->prefix . 'optibot_conversations',
            [
                'session_id' => $session_id,
                'role'       => $role,
                'content'    => $content,
            ],
            [ '%s', '%s', '%s' ]
        );
    }
}
