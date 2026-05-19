/* global optibotAdmin, jQuery */
(function ($) {
    'use strict';

    // ─── Color pickers ───────────────────────────────────────────
    $('.optibot-color-picker').wpColorPicker({
        change: debounce(updatePreview, 200),
        clear:  debounce(updatePreview, 200),
    });

    // ─── Range sliders ────────────────────────────────────────────
    const rangeIds = ['optibot_bubble_size','optibot_chat_width','optibot_chat_height','optibot_font_size','optibot_border_radius'];
    rangeIds.forEach(function (id) {
        var $el = $('#' + id);
        $el.on('input', function () {
            $('#' + id.replace('optibot_','') + '_val').text(this.value + 'px');
            updatePreview();
        });
    });

    // ─── Live preview update ──────────────────────────────────────
    function getVal(name) {
        return $('[name="' + name + '"]').val() || '';
    }
    function updatePreview() {
        var $chat = $('.optibot-preview-chat');
        if (!$chat.length) return;
        var primary   = getVal('optibot_primary_color');
        var secondary = getVal('optibot_secondary_color');
        var textColor = getVal('optibot_text_color');
        var bgColor   = getVal('optibot_bg_color');
        var chatBg    = getVal('optibot_chat_bg_color');
        var radius    = getVal('optibot_border_radius');
        var fontSize  = getVal('optibot_font_size');
        var width     = Math.min(parseInt(getVal('optibot_chat_width')) || 340, 340);

        $chat.css({ width: width + 'px', borderRadius: radius + 'px', fontSize: fontSize + 'px', background: bgColor });
        $chat.find('.preview-header').css({ background: primary, color: textColor, borderRadius: radius + 'px ' + radius + 'px 0 0' });
        $chat.find('.preview-bubble.bot').css({ background: secondary, color: '#fff' });
        $chat.find('.preview-bubble.user').css({ background: primary, color: textColor });
        $chat.find('.preview-messages').css({ background: chatBg });
        $chat.find('.preview-input button').css({ background: primary, color: textColor });
    }

    // ─── Colegiados table ─────────────────────────────────────────
    var currentPage = 1;
    var currentSearch = '';

    function loadColegiados(page, search) {
        page   = page   || 1;
        search = search || '';
        currentPage   = page;
        currentSearch = search;

        $('#optibot-colegiados-tbody').html('<tr><td colspan="6" class="loading-row">Cargando...</td></tr>');

        $.post(optibotAdmin.ajax_url, {
            action:  'optibot_get_colegiados',
            nonce:   optibotAdmin.nonce,
            paged:   page,
            search:  search,
        }, function (res) {
            if (!res.success) { alert('Error: ' + res.data); return; }
            renderTable(res.data.rows);
            renderPagination(res.data.total, res.data.pages, page);
        });
    }

    function badgeClass(estado) {
        if (estado === 'activo')   return 'badge-activo';
        if (estado === 'inactivo') return 'badge-inactivo';
        return 'badge-baja';
    }

    function renderTable(rows) {
        if (!rows || !rows.length) {
            $('#optibot-colegiados-tbody').html('<tr><td colspan="6" class="loading-row">No se encontraron colegiados.</td></tr>');
            return;
        }
        var html = '';
        rows.forEach(function (r) {
            html += '<tr>' +
                '<td><strong>' + esc(r.num_colegiado) + '</strong></td>' +
                '<td>' + esc(r.apellidos) + ', ' + esc(r.nombre) + '</td>' +
                '<td>' + esc(r.especialidad || '—') + '</td>' +
                '<td>' + esc(r.localidad || '—') + '</td>' +
                '<td><span class="' + badgeClass(r.estado) + '">' + esc(r.estado) + '</span></td>' +
                '<td>' +
                    '<button class="button button-small optibot-edit-col" data-id="' + r.id + '" data-row=\'' + JSON.stringify(r).replace(/'/g,"&#39;") + '\'>Editar</button> ' +
                    '<button class="button button-small optibot-delete-col" data-id="' + r.id + '" data-name="' + esc(r.nombre + ' ' + r.apellidos) + '">Eliminar</button>' +
                '</td>' +
            '</tr>';
        });
        $('#optibot-colegiados-tbody').html(html);
    }

    function renderPagination(total, pages, current) {
        var $p = $('#optibot-pagination');
        $p.empty();
        if (pages <= 1) { $p.append('<span style="color:#888;font-size:12px">Total: ' + total + ' colegiados</span>'); return; }
        $p.append('<span style="color:#888;font-size:12px;margin-right:8px">Total: ' + total + '</span>');
        for (var i = 1; i <= pages; i++) {
            var $btn = $('<button>' + i + '</button>');
            if (i === current) $btn.addClass('active');
            $btn.data('page', i);
            $p.append($btn);
        }
    }

    $(document).on('click', '#optibot-pagination button', function () {
        loadColegiados($(this).data('page'), currentSearch);
    });

    var searchTimer;
    $('#optibot-search-col').on('input', function () {
        clearTimeout(searchTimer);
        var val = $(this).val();
        searchTimer = setTimeout(function () { loadColegiados(1, val); }, 400);
    });

    // ─── Modal ────────────────────────────────────────────────────
    function openModal(data) {
        data = data || {};
        $('#optibot-modal-title').text(data.id ? 'Editar colegiado' : 'Nuevo colegiado');
        $('#col-id').val(data.id || '');
        $('#col-num').val(data.num_colegiado || '');
        $('#col-nombre').val(data.nombre || '');
        $('#col-apellidos').val(data.apellidos || '');
        $('#col-especialidad').val(data.especialidad || '');
        $('#col-email').val(data.email || '');
        $('#col-telefono').val(data.telefono || '');
        $('#col-direccion').val(data.direccion || '');
        $('#col-localidad').val(data.localidad || '');
        $('#col-provincia').val(data.provincia || 'Valencia');
        $('#col-cp').val(data.codigo_postal || '');
        $('#col-estado').val(data.estado || 'activo');
        $('#col-fecha-alta').val(data.fecha_alta || '');
        $('#optibot-modal').show();
    }

    function closeModal() { $('#optibot-modal').hide(); }

    $('#optibot-add-colegiado').on('click', function () { openModal(); });
    $(document).on('click', '.optibot-edit-col', function () {
        openModal(JSON.parse($(this).attr('data-row').replace(/&#39;/g,"'")));
    });
    $('#optibot-modal-cancel, .optibot-modal-close').on('click', closeModal);
    $('#optibot-modal').on('click', function (e) { if ($(e.target).is('#optibot-modal')) closeModal(); });

    $('#optibot-modal-save').on('click', function () {
        var $btn = $(this).prop('disabled', true).text('Guardando...');
        $.post(optibotAdmin.ajax_url, {
            action:        'optibot_save_colegiado',
            nonce:         optibotAdmin.nonce,
            id:            $('#col-id').val(),
            num_colegiado: $('#col-num').val(),
            nombre:        $('#col-nombre').val(),
            apellidos:     $('#col-apellidos').val(),
            especialidad:  $('#col-especialidad').val(),
            email:         $('#col-email').val(),
            telefono:      $('#col-telefono').val(),
            direccion:     $('#col-direccion').val(),
            localidad:     $('#col-localidad').val(),
            provincia:     $('#col-provincia').val(),
            codigo_postal: $('#col-cp').val(),
            estado:        $('#col-estado').val(),
            fecha_alta:    $('#col-fecha-alta').val(),
        }, function (res) {
            $btn.prop('disabled', false).text('Guardar');
            if (!res.success) { alert('Error: ' + res.data); return; }
            closeModal();
            loadColegiados(currentPage, currentSearch);
        });
    });

    // Delete
    $(document).on('click', '.optibot-delete-col', function () {
        if (!confirm('¿Eliminar a ' + $(this).data('name') + '?')) return;
        var id = $(this).data('id');
        $.post(optibotAdmin.ajax_url, { action: 'optibot_delete_colegiado', nonce: optibotAdmin.nonce, id: id }, function (res) {
            if (!res.success) { alert('Error al eliminar.'); return; }
            loadColegiados(currentPage, currentSearch);
        });
    });

    // CSV Import
    $('#optibot-csv-upload').on('change', function () {
        var file = this.files[0];
        if (!file) return;
        if (!confirm('¿Importar el archivo "' + file.name + '"? Los colegiados existentes se actualizarán.')) { $(this).val(''); return; }

        var fd = new FormData();
        fd.append('action', 'optibot_import_colegiados');
        fd.append('nonce', optibotAdmin.nonce);
        fd.append('csv_file', file);

        $.ajax({
            url:         optibotAdmin.ajax_url,
            type:        'POST',
            data:        fd,
            processData: false,
            contentType: false,
            success: function (res) {
                if (!res.success) { alert('Error: ' + res.data); return; }
                alert('Importación completada: ' + res.data.imported + ' colegiados importados/actualizados. Errores: ' + res.data.errors);
                loadColegiados(1, '');
            },
        });
        $(this).val('');
    });

    // CSV Template download
    $('#optibot-download-template').on('click', function (e) {
        e.preventDefault();
        var csv = 'num_colegiado;nombre;apellidos;especialidad;email;telefono;localidad;provincia;codigo_postal;estado\n';
        csv += 'COL-001;Ana;García López;Optometría;ana@ejemplo.com;963000001;Valencia;Valencia;46001;activo\n';
        csv += 'COL-002;Carlos;Martínez Ruiz;Audiología;carlos@ejemplo.com;963000002;Gandía;Valencia;46700;activo\n';
        var blob = new Blob(['\uFEFF' + csv], { type: 'text/csv;charset=utf-8;' });
        var url  = URL.createObjectURL(blob);
        var $a   = $('<a>').attr({ href: url, download: 'plantilla_colegiados.csv' }).appendTo('body');
        $a[0].click();
        $a.remove();
    });

    // ─── Init ─────────────────────────────────────────────────────
    if ($('#optibot-colegiados-tbody').length) loadColegiados();

    // ─── Helpers ──────────────────────────────────────────────────
    function esc(str) {
        return String(str || '').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
    }
    function debounce(fn, ms) {
        var t; return function () { clearTimeout(t); t = setTimeout(fn, ms); };
    }

})(jQuery);
