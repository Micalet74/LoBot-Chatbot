<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class Optibot_Public {

    public function enqueue_assets() {
        if ( ! $this->should_show() ) return;

        wp_enqueue_style(
            'optibot-public-css',
            OPTIBOT_PLUGIN_URL . 'public/css/optibot-chat.css',
            [],
            OPTIBOT_VERSION
        );

        wp_enqueue_script(
            'optibot-public-js',
            OPTIBOT_PLUGIN_URL . 'public/js/optibot-chat.js',
            [],
            OPTIBOT_VERSION,
            true
        );

        $s = Optibot_Settings::get_all();

        wp_localize_script( 'optibot-public-js', 'optibotConfig', [
            'ajax_url'        => admin_url( 'admin-ajax.php' ),
            'nonce'           => wp_create_nonce( 'optibot_nonce' ),
            'bot_name'        => $s['bot_name'],
            'welcome_message' => $s['welcome_message'],
            'show_branding'   => $s['show_branding'],
            'avatar_icon'     => $s['avatar_icon'],
            'css'             => [
                'primary_color'   => $s['primary_color'],
                'secondary_color' => $s['secondary_color'],
                'text_color'      => $s['text_color'],
                'bg_color'        => $s['bg_color'],
                'chat_bg_color'   => $s['chat_bg_color'],
                'bubble_size'     => $s['bubble_size'],
                'chat_width'      => $s['chat_width'],
                'chat_height'     => $s['chat_height'],
                'font_size'       => $s['font_size'],
                'border_radius'   => $s['border_radius'],
                'position'        => $s['position'],
            ],
        ] );
    }

    public function render_chatbot() {
        if ( ! $this->should_show() ) return;
        $s = Optibot_Settings::get_all();
        $icon = $this->get_avatar_svg( $s['avatar_icon'] );
        ?>
        <div id="optibot-root" data-position="<?php echo esc_attr( $s['position'] ); ?>">
            <!-- Chat bubble button -->
            <button id="optibot-bubble" aria-label="Abrir chatbot <?php echo esc_attr( $s['bot_name'] ); ?>" title="<?php echo esc_attr( $s['bot_name'] ); ?>">
                <span class="optibot-bubble-icon"><?php echo $icon; // phpcs:ignore ?></span>
                <span class="optibot-bubble-close" style="display:none">✕</span>
                <span id="optibot-unread" style="display:none">1</span>
            </button>

            <!-- Chat window -->
            <div id="optibot-window" role="dialog" aria-label="Chat con <?php echo esc_attr( $s['bot_name'] ); ?>" style="display:none">
                <!-- Header -->
                <div id="optibot-header">
                    <div class="optibot-header-info">
                        <span class="optibot-header-avatar"><?php echo $icon; // phpcs:ignore ?></span>
                        <div>
                            <strong><?php echo esc_html( $s['bot_name'] ); ?></strong>
                            <small>Asistente especializado en óptica</small>
                        </div>
                    </div>
                    <div class="optibot-header-actions">
                        <button id="optibot-clear" title="Nueva conversación">🗑</button>
                        <button id="optibot-close" title="Cerrar">✕</button>
                    </div>
                </div>

                <!-- Quick suggestions -->
                <div id="optibot-suggestions">
                    <button class="optibot-suggestion">¿Cómo me colegio?</button>
                    <button class="optibot-suggestion">Verificar un colegiado</button>
                    <button class="optibot-suggestion">Normativa óptica</button>
                    <button class="optibot-suggestion">Formación continua</button>
                </div>

                <!-- Messages -->
                <div id="optibot-messages" role="log" aria-live="polite"></div>

                <!-- Typing indicator -->
                <div id="optibot-typing" style="display:none">
                    <span></span><span></span><span></span>
                </div>

                <!-- Input -->
                <div id="optibot-input-area">
                    <textarea id="optibot-input" placeholder="Escribe tu pregunta..." rows="1" aria-label="Mensaje"></textarea>
                    <button id="optibot-send" aria-label="Enviar">
                        <svg viewBox="0 0 24 24" width="18" height="18" fill="currentColor">
                            <path d="M2.01 21L23 12 2.01 3 2 10l15 2-15 2z"/>
                        </svg>
                    </button>
                </div>

                <?php if ( $s['show_branding'] === '1' ) : ?>
                <div id="optibot-branding">Powered by <strong>Airos74</strong> · Colegio de Ópticos de Valencia</div>
                <?php endif; ?>
            </div>
        </div>
        <?php
    }

    private function should_show() {
        $show = Optibot_Settings::get( 'optibot_show_pages', 'all' );
        if ( $show === 'none' ) return false;
        if ( $show === 'home' && ! is_front_page() ) return false;
        // Don't show in admin
        if ( is_admin() ) return false;
        return true;
    }

    private function get_avatar_svg( $icon ) {
        $icons = [
            'eye' => '<svg viewBox="0 0 24 24" width="22" height="22" fill="currentColor"><path d="M12 4.5C7 4.5 2.73 7.61 1 12c1.73 4.39 6 7.5 11 7.5s9.27-3.11 11-7.5c-1.73-4.39-6-7.5-11-7.5zM12 17c-2.76 0-5-2.24-5-5s2.24-5 5-5 5 2.24 5 5-2.24 5-5 5zm0-8c-1.66 0-3 1.34-3 3s1.34 3 3 3 3-1.34 3-3-1.34-3-3-3z"/></svg>',
            'glasses' => '<svg viewBox="0 0 24 24" width="22" height="22" fill="currentColor"><path d="M21.58 8.45l-1.1-3.86C20.08 3.63 19.13 3 18.07 3H5.93c-1.06 0-2.01.63-2.41 1.59L2.42 8.45C1.58 8.88 1 9.76 1 10.8V16c0 1.66 1.34 3 3 3h1c1.66 0 3-1.34 3-3v-1h8v1c0 1.66 1.34 3 3 3h1c1.66 0 3-1.34 3-3v-5.2c0-1.04-.58-1.92-1.42-2.35zM5.93 5h12.14l.69 2.44C18.44 7.16 18.07 7 17.72 7H6.28c-.35 0-.72.16-1.04.44L5.93 5zM7 16c0 .55-.45 1-1 1H5c-.55 0-1-.45-1-1v-5.2c0-.45.25-.83.62-.99L5 10h2v5c0 .34-.01.67-.04 1H7zm14 0c0 .55-.45 1-1 1h-1c-.55 0-1-.45-1-1h-.04c-.03-.33-.04-.66-.04-1v-5h2l.38.81c.37.16.62.54.62.99V16h.08z"/></svg>',
            'robot' => '<svg viewBox="0 0 24 24" width="22" height="22" fill="currentColor"><path d="M20 9V7c0-1.1-.9-2-2-2h-3c0-1.66-1.34-3-3-3S9 3.34 9 5H6C4.9 5 4 5.9 4 7v2c-1.66 0-3 1.34-3 3s1.34 3 3 3v4c0 1.1.9 2 2 2h12c1.1 0 2-.9 2-2v-4c1.66 0 3-1.34 3-3s-1.34-3-3-3zm-2 10H6V7h12v12zm-9-6c-.83 0-1.5-.67-1.5-1.5S8.17 10 9 10s1.5.67 1.5 1.5S9.83 13 9 13zm6 0c-.83 0-1.5-.67-1.5-1.5s.67-1.5 1.5-1.5 1.5.67 1.5 1.5-.67 1.5-1.5 1.5zm-3 3h-2v-2h2v2zm2 0h-2v-2h2v2z"/></svg>',
            'star' => '<svg viewBox="0 0 24 24" width="22" height="22" fill="currentColor"><path d="M12 17.27L18.18 21l-1.64-7.03L22 9.24l-7.19-.61L12 2 9.19 8.63 2 9.24l5.46 4.73L5.82 21z"/></svg>',
        ];
        return $icons[ $icon ] ?? $icons['eye'];
    }
}
