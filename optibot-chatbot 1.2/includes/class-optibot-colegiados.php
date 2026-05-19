<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class Optibot_Colegiados {

    public static function ajax_get_colegiados() {
        check_ajax_referer( 'optibot_admin_nonce', 'nonce' );
        if ( ! current_user_can( 'manage_options' ) ) wp_send_json_error( 'Sin permisos.' );

        global $wpdb;
        $table  = $wpdb->prefix . 'optibot_colegiados';
        $search = isset( $_POST['search'] ) ? sanitize_text_field( $_POST['search'] ) : '';
        $page   = isset( $_POST['paged'] )  ? intval( $_POST['paged'] )               : 1;
        $per    = 20;
        $offset = ( $page - 1 ) * $per;

        if ( $search ) {
            $like  = '%' . $wpdb->esc_like( $search ) . '%';
            $rows  = $wpdb->get_results( $wpdb->prepare(
                "SELECT * FROM $table WHERE nombre LIKE %s OR apellidos LIKE %s OR num_colegiado LIKE %s OR localidad LIKE %s ORDER BY apellidos, nombre LIMIT %d OFFSET %d",
                $like, $like, $like, $like, $per, $offset
            ) );
            $total = $wpdb->get_var( $wpdb->prepare(
                "SELECT COUNT(*) FROM $table WHERE nombre LIKE %s OR apellidos LIKE %s OR num_colegiado LIKE %s OR localidad LIKE %s",
                $like, $like, $like, $like
            ) );
        } else {
            $rows  = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM $table ORDER BY apellidos, nombre LIMIT %d OFFSET %d", $per, $offset ) );
            $total = $wpdb->get_var( "SELECT COUNT(*) FROM $table" );
        }

        wp_send_json_success( [ 'rows' => $rows, 'total' => intval( $total ), 'pages' => ceil( $total / $per ) ] );
    }

    public static function ajax_save_colegiado() {
        check_ajax_referer( 'optibot_admin_nonce', 'nonce' );
        if ( ! current_user_can( 'manage_options' ) ) wp_send_json_error( 'Sin permisos.' );

        global $wpdb;
        $table = $wpdb->prefix . 'optibot_colegiados';
        $id    = isset( $_POST['id'] ) ? intval( $_POST['id'] ) : 0;

        $data = [
            'num_colegiado' => sanitize_text_field( $_POST['num_colegiado'] ?? '' ),
            'nombre'        => sanitize_text_field( $_POST['nombre']        ?? '' ),
            'apellidos'     => sanitize_text_field( $_POST['apellidos']     ?? '' ),
            'especialidad'  => sanitize_text_field( $_POST['especialidad']  ?? '' ),
            'email'         => sanitize_email(      $_POST['email']         ?? '' ),
            'telefono'      => sanitize_text_field( $_POST['telefono']      ?? '' ),
            'direccion'     => sanitize_textarea_field( $_POST['direccion'] ?? '' ),
            'localidad'     => sanitize_text_field( $_POST['localidad']     ?? '' ),
            'provincia'     => sanitize_text_field( $_POST['provincia']     ?? '' ),
            'codigo_postal' => sanitize_text_field( $_POST['codigo_postal'] ?? '' ),
            'estado'        => sanitize_text_field( $_POST['estado']        ?? 'activo' ),
            'fecha_alta'    => sanitize_text_field( $_POST['fecha_alta']    ?? '' ) ?: null,
        ];

        if ( empty( $data['num_colegiado'] ) || empty( $data['nombre'] ) || empty( $data['apellidos'] ) ) {
            wp_send_json_error( 'Número de colegiado, nombre y apellidos son obligatorios.' );
        }

        $formats = [ '%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%s' ];

        if ( $id > 0 ) {
            $result = $wpdb->update( $table, $data, [ 'id' => $id ], $formats, [ '%d' ] );
        } else {
            $result = $wpdb->insert( $table, $data, $formats );
            $id     = $wpdb->insert_id;
        }

        if ( false === $result ) {
            wp_send_json_error( 'Error al guardar: ' . $wpdb->last_error );
        }

        wp_send_json_success( [ 'id' => $id ] );
    }

    public static function ajax_delete_colegiado() {
        check_ajax_referer( 'optibot_admin_nonce', 'nonce' );
        if ( ! current_user_can( 'manage_options' ) ) wp_send_json_error( 'Sin permisos.' );

        global $wpdb;
        $id = intval( $_POST['id'] ?? 0 );
        if ( ! $id ) wp_send_json_error( 'ID inválido.' );

        $wpdb->delete( $wpdb->prefix . 'optibot_colegiados', [ 'id' => $id ], [ '%d' ] );
        wp_send_json_success();
    }

    public static function ajax_import_csv() {
        check_ajax_referer( 'optibot_admin_nonce', 'nonce' );
        if ( ! current_user_can( 'manage_options' ) ) wp_send_json_error( 'Sin permisos.' );

        if ( empty( $_FILES['csv_file']['tmp_name'] ) ) {
            wp_send_json_error( 'No se ha subido ningún archivo.' );
        }

        $file = $_FILES['csv_file']['tmp_name'];
        if ( ( $handle = fopen( $file, 'r' ) ) === false ) {
            wp_send_json_error( 'No se puede leer el archivo.' );
        }

        global $wpdb;
        $table   = $wpdb->prefix . 'optibot_colegiados';
        $header  = null;
        $count   = 0;
        $errors  = 0;

        while ( ( $row = fgetcsv( $handle, 1000, ';' ) ) !== false ) {
            if ( ! $header ) {
                $header = array_map( 'trim', $row );
                continue;
            }

            if ( count( $row ) < 3 ) continue;

            $data = array_combine( $header, array_pad( $row, count( $header ), '' ) );

            $insert = [
                'num_colegiado' => sanitize_text_field( $data['num_colegiado'] ?? $data['numero'] ?? '' ),
                'nombre'        => sanitize_text_field( $data['nombre']        ?? '' ),
                'apellidos'     => sanitize_text_field( $data['apellidos']     ?? '' ),
                'especialidad'  => sanitize_text_field( $data['especialidad']  ?? '' ),
                'email'         => sanitize_email(      $data['email']         ?? '' ),
                'telefono'      => sanitize_text_field( $data['telefono']      ?? '' ),
                'localidad'     => sanitize_text_field( $data['localidad']     ?? '' ),
                'provincia'     => sanitize_text_field( $data['provincia']     ?? 'Valencia' ),
                'codigo_postal' => sanitize_text_field( $data['codigo_postal'] ?? '' ),
                'estado'        => sanitize_text_field( $data['estado']        ?? 'activo' ),
            ];

            if ( empty( $insert['num_colegiado'] ) ) { $errors++; continue; }

            $existing = $wpdb->get_var( $wpdb->prepare( "SELECT id FROM $table WHERE num_colegiado = %s", $insert['num_colegiado'] ) );
            if ( $existing ) {
                $wpdb->update( $table, $insert, [ 'id' => $existing ] );
            } else {
                $wpdb->insert( $table, $insert );
            }
            $count++;
        }

        fclose( $handle );
        wp_send_json_success( [ 'imported' => $count, 'errors' => $errors ] );
    }

    /**
     * Search colegiados to provide context to the AI
     */
    public static function search_for_context( $query ) {
        global $wpdb;
        $table = $wpdb->prefix . 'optibot_colegiados';
        $like  = '%' . $wpdb->esc_like( $query ) . '%';

        $results = $wpdb->get_results( $wpdb->prepare(
            "SELECT num_colegiado, nombre, apellidos, especialidad, localidad, provincia, estado FROM $table
             WHERE (nombre LIKE %s OR apellidos LIKE %s OR num_colegiado LIKE %s)
             AND estado = 'activo'
             LIMIT 5",
            $like, $like, $like
        ) );

        if ( empty( $results ) ) return '';

        $context = "Colegiados encontrados relacionados con la consulta:\n";
        foreach ( $results as $r ) {
            $context .= "- Nº {$r->num_colegiado}: {$r->nombre} {$r->apellidos}";
            if ( $r->especialidad ) $context .= " | Especialidad: {$r->especialidad}";
            if ( $r->localidad )    $context .= " | {$r->localidad}";
            $context .= " | Estado: {$r->estado}\n";
        }

        return $context;
    }

    public static function get_stats() {
        global $wpdb;
        $table = $wpdb->prefix . 'optibot_colegiados';
        return [
            'total'   => intval( $wpdb->get_var( "SELECT COUNT(*) FROM $table" ) ),
            'activos' => intval( $wpdb->get_var( "SELECT COUNT(*) FROM $table WHERE estado = 'activo'" ) ),
        ];
    }
}
