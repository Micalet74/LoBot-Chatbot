<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Optibot_Crawler
 * Provides helper methods to fetch content from the site and external sources
 * for enriching AI context. Runs via WP Cron.
 */
class Optibot_Crawler {

    public static function init() {
        if ( ! wp_next_scheduled( 'optibot_crawl_site' ) ) {
            wp_schedule_event( time(), 'daily', 'optibot_crawl_site' );
        }
        add_action( 'optibot_crawl_site', [ __CLASS__, 'crawl' ] );
    }

    public static function crawl() {
        self::cache_site_content();
        self::cache_gov_sources();
    }

    /**
     * Index all public pages/posts and store a summary in transient.
     */
    public static function cache_site_content() {
        $posts = get_posts( [
            'post_type'      => [ 'post', 'page' ],
            'post_status'    => 'publish',
            'posts_per_page' => 200,
        ] );

        $index = [];
        foreach ( $posts as $post ) {
            $index[] = [
                'id'      => $post->ID,
                'title'   => get_the_title( $post ),
                'url'     => get_permalink( $post ),
                'excerpt' => wp_trim_words( wp_strip_all_tags( $post->post_content ), 60 ),
            ];
        }

        set_transient( 'optibot_site_index', $index, DAY_IN_SECONDS );
    }

    /**
     * Cache references to official Spanish/EU optics regulation sources.
     */
    public static function cache_gov_sources() {
        $sources = [
            [
                'name'    => 'BOE - Ley 44/2003 Ordenación Profesiones Sanitarias',
                'url'     => 'https://www.boe.es/buscar/act.php?id=BOE-A-2003-21340',
                'summary' => 'Marco legal de las profesiones sanitarias en España, incluidos ópticos-optometristas.',
            ],
            [
                'name'    => 'CGCOO - Consejo General de Ópticos-Optometristas',
                'url'     => 'https://www.cgcoo.es',
                'summary' => 'Organismo colegial nacional. Información sobre colegiación, legislación y noticias del sector.',
            ],
            [
                'name'    => 'Conselleria de Sanitat - Establecimientos Sanitarios',
                'url'     => 'https://www.san.gva.es',
                'summary' => 'Información sobre autorización y registro de establecimientos sanitarios de óptica en la Comunitat Valenciana.',
            ],
            [
                'name'    => 'Ministerio de Sanidad - Profesiones Sanitarias',
                'url'     => 'https://www.sanidad.gob.es/profesionales/profesionesReguladas/home.htm',
                'summary' => 'Registro estatal de profesionales sanitarios, reconocimiento de títulos y normativa aplicable.',
            ],
        ];

        set_transient( 'optibot_gov_sources', $sources, DAY_IN_SECONDS );
    }

    public static function get_site_index() {
        $index = get_transient( 'optibot_site_index' );
        if ( false === $index ) {
            self::cache_site_content();
            $index = get_transient( 'optibot_site_index' );
        }
        return $index ?: [];
    }

    public static function get_gov_sources() {
        $sources = get_transient( 'optibot_gov_sources' );
        if ( false === $sources ) {
            self::cache_gov_sources();
            $sources = get_transient( 'optibot_gov_sources' );
        }
        return $sources ?: [];
    }
}
