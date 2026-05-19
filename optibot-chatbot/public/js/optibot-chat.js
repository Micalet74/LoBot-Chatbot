/* global optibotConfig */
(function () {
    'use strict';

    var cfg     = window.optibotConfig || {};
    var css     = cfg.css || {};
    var botName = cfg.bot_name || 'OPTIBOT';
    var history = [];
    var sessionId = 'ob_' + Math.random().toString(36).slice(2);
    var isOpen    = false;
    var isBusy    = false;

    /* ─── Apply CSS variables ───────────────────────── */
    function applyStyles() {
        var root = document.getElementById('optibot-root');
        if (!root) return;
        var map = {
            '--ob-primary':   css.primary_color   || '#005B9A',
            '--ob-secondary': css.secondary_color || '#00A8CC',
            '--ob-text':      css.text_color      || '#ffffff',
            '--ob-bg':        css.bg_color        || '#ffffff',
            '--ob-chat-bg':   css.chat_bg_color   || '#f0f4f8',
            '--ob-bubble-size': (css.bubble_size || '60') + 'px',
            '--ob-width':       (css.chat_width  || '380') + 'px',
            '--ob-height':      (css.chat_height || '520') + 'px',
            '--ob-font':        (css.font_size   || '14') + 'px',
            '--ob-radius':      (css.border_radius|| '16') + 'px',
        };
        Object.keys(map).forEach(function (k) { root.style.setProperty(k, map[k]); });
        document.getElementById('optibot-root').dataset.position = css.position || 'bottom-right';
    }

    /* ─── Toggle window ─────────────────────────────── */
    function toggleChat() {
        if (isOpen) { closeChat(); } else { openChat(); }
    }

    function openChat() {
        isOpen = true;
        var win = document.getElementById('optibot-window');
        var ico = document.querySelector('.optibot-bubble-icon');
        var cls = document.querySelector('.optibot-bubble-close');
        win.style.display = 'flex';
        win.classList.add('ob-entering');
        setTimeout(function () { win.classList.remove('ob-entering'); }, 350);
        if (ico) ico.style.display = 'none';
        if (cls) cls.style.display = 'flex';
        document.getElementById('optibot-unread').style.display = 'none';

        // Show welcome message once
        var msgs = document.getElementById('optibot-messages');
        if (!msgs.children.length) {
            appendMessage('bot', cfg.welcome_message || '¡Hola! Soy ' + botName + '.', true);
        }
        document.getElementById('optibot-input').focus();
    }

    function closeChat() {
        isOpen = false;
        var win = document.getElementById('optibot-window');
        var ico = document.querySelector('.optibot-bubble-icon');
        var cls = document.querySelector('.optibot-bubble-close');
        win.classList.add('ob-leaving');
        setTimeout(function () {
            win.style.display = 'none';
            win.classList.remove('ob-leaving');
        }, 200);
        if (ico) ico.style.display = 'flex';
        if (cls) cls.style.display = 'none';
    }

    /* ─── Messages ──────────────────────────────────── */
    function appendMessage(role, text, isWelcome) {
        var msgs = document.getElementById('optibot-messages');
        var wrap = document.createElement('div');
        wrap.className = 'ob-msg ob-' + role;

        var avatar = document.createElement('div');
        avatar.className = 'ob-avatar';
        avatar.innerHTML = role === 'bot' ? '👁' : '👤';

        var bubble = document.createElement('div');
        bubble.className = 'ob-bubble';
        bubble.innerHTML = formatText(text);

        var ts = document.createElement('div');
        ts.className = 'ob-timestamp';
        ts.textContent = new Date().toLocaleTimeString('es-ES', { hour: '2-digit', minute: '2-digit' });

        var inner = document.createElement('div');
        inner.style.maxWidth = '100%';
        inner.appendChild(bubble);
        inner.appendChild(ts);

        if (role === 'bot') {
            wrap.appendChild(avatar);
            wrap.appendChild(inner);
        } else {
            wrap.appendChild(inner);
            wrap.appendChild(avatar);
        }

        msgs.appendChild(wrap);
        msgs.scrollTop = msgs.scrollHeight;
        return bubble;
    }

    function formatText(text) {
        // Basic markdown-like formatting
        return text
            .replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;')
            .replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>')
            .replace(/\*(.*?)\*/g, '<em>$1</em>')
            .replace(/`([^`]+)`/g, '<code>$1</code>')
            .replace(/\n/g, '<br>');
    }

    function showTyping(show) {
        document.getElementById('optibot-typing').style.display = show ? 'flex' : 'none';
        document.getElementById('optibot-messages').scrollTop = 9999;
    }

    /* ─── Send message ──────────────────────────────── */
    function sendMessage() {
        if (isBusy) return;
        var input = document.getElementById('optibot-input');
        var text  = input.value.trim();
        if (!text) return;

        input.value = '';
        input.style.height = '';

        appendMessage('user', text);
        history.push({ role: 'user', content: text });

        isBusy = true;
        document.getElementById('optibot-send').disabled = true;
        showTyping(true);

        // Hide suggestions after first real message
        document.getElementById('optibot-suggestions').style.display = 'none';

        var body = new FormData();
        body.append('action',     'optibot_chat');
        body.append('nonce',      cfg.nonce);
        body.append('message',    text);
        body.append('session_id', sessionId);
        body.append('history',    JSON.stringify(history.slice(-10)));

        fetch(cfg.ajax_url, { method: 'POST', body: body })
            .then(function (r) { return r.json(); })
            .then(function (data) {
                showTyping(false);
                var reply = data.success
                    ? (data.data.message || 'No pude generar una respuesta.')
                    : (data.data && data.data.message ? data.data.message : 'Error de conexión. Inténtalo de nuevo.');
                appendMessage('bot', reply);
                history.push({ role: 'assistant', content: reply });
            })
            .catch(function () {
                showTyping(false);
                appendMessage('bot', '⚠️ Error de conexión. Por favor, inténtalo de nuevo.');
            })
            .finally(function () {
                isBusy = false;
                document.getElementById('optibot-send').disabled = false;
                document.getElementById('optibot-input').focus();
            });
    }

    /* ─── Events ────────────────────────────────────── */
    function bindEvents() {
        document.getElementById('optibot-bubble').addEventListener('click', toggleChat);
        document.getElementById('optibot-close').addEventListener('click', closeChat);

        document.getElementById('optibot-send').addEventListener('click', sendMessage);

        var inp = document.getElementById('optibot-input');
        inp.addEventListener('keydown', function (e) {
            if (e.key === 'Enter' && !e.shiftKey) {
                e.preventDefault();
                sendMessage();
            }
        });
        // Auto-grow textarea
        inp.addEventListener('input', function () {
            this.style.height = 'auto';
            this.style.height = Math.min(this.scrollHeight, 100) + 'px';
        });

        // Clear conversation
        document.getElementById('optibot-clear').addEventListener('click', function () {
            history = [];
            document.getElementById('optibot-messages').innerHTML = '';
            document.getElementById('optibot-suggestions').style.display = 'flex';
            appendMessage('bot', cfg.welcome_message || '¡Hola! ¿En qué puedo ayudarte?', true);
        });

        // Quick suggestions
        document.querySelectorAll('.optibot-suggestion').forEach(function (btn) {
            btn.addEventListener('click', function () {
                var input = document.getElementById('optibot-input');
                input.value = btn.textContent;
                sendMessage();
            });
        });

        // Show unread badge when closed
        window.addEventListener('blur', function () {
            if (!isOpen) {
                // Could implement unread logic here
            }
        });

        // Keyboard shortcut: Esc to close
        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape' && isOpen) closeChat();
        });
    }

    /* ─── Init ──────────────────────────────────────── */
    function init() {
        if (!document.getElementById('optibot-root')) return;
        applyStyles();
        bindEvents();

        // Show notification badge after 5s if not opened
        setTimeout(function () {
            if (!isOpen) {
                document.getElementById('optibot-unread').style.display = 'flex';
            }
        }, 5000);
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }

})();
