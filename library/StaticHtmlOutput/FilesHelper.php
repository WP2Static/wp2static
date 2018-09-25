<?php
/**
 * StaticHtmlOutput_FilesHelper
 *
 * @package WP Static HTML Output
 *
 * Copyright (c) 2011 Leon Stafford
 */
class StaticHtmlOutput_FilesHelper {

    protected $_directory;

    /**
     * Constructor
     */
    public function __construct() {
        $this->_directory = '';
    }


    /**
     * Delete directory and its files
     *
     * @param string $dir Directory
     * @return boolean
     */
    public static function delete_dir_with_files( $dir ) {
        if ( is_dir( $dir ) ) {
            $files = array_diff( scandir( $dir ), array( '.', '..' ) );

            foreach ( $files as $file ) {
                ( is_dir( "$dir/$file" ) ) ?
                    self::delete_dir_with_files( "$dir/$file" )
                    : unlink( "$dir/$file" );
            }

            return rmdir( $dir );
        }
    }


    /**
     * Scan directory contents recursively
     *
     * @param string $dir            Directory
     * @param string $siteroot       Site root
     * @param string $file_list_path File list path
     * @return void
     */
    public static function recursively_scan_dir(
        $dir,
        $siteroot,
        $file_list_path
    ) {
        // rm duplicate slashes in path (TODO: fix cause)
        $dir = str_replace( '//', '/', $dir );
        $files = scandir( $dir );

        foreach ( $files as $item ) {
            if ( $item != '.' && $item != '..' && $item != '.git' ) {
                if ( is_dir( $dir . '/' . $item ) ) {
                    self::recursively_scan_dir(
                        $dir . '/' . $item, $siteroot,
                        $file_list_path
                    );
                } elseif ( is_file( $dir . '/' . $item ) ) {

                    $subdir = str_replace(
                        '/wp-admin/admin-ajax.php',
                        '',
                        $_SERVER['REQUEST_URI']
                    );
                    $subdir = ltrim( $subdir, '/' );
                    $clean_dir = str_replace(
                        $siteroot . '/',
                        '',
                        $dir . '/'
                    );
                    $clean_dir = str_replace( $subdir, '', $clean_dir );
                    $filename = $dir . '/' . $item . "\n";
                    $filename = str_replace( '//', '/', $filename );
                    // $this->wsLog('FILE TO ADD:');
                    // $this->wsLog($filename);
                    file_put_contents(
                        $file_list_path,
                        $filename,
                        FILE_APPEND | LOCK_EX
                    );
                }//end if
            }//end if
        }//end foreach
    }


    /**
     * Get list of local files by URL
     *
     * @param string $url URL
     * @return array
     */
    public static function getListOfLocalFilesByUrl( $url ) {
        $files = array();

        $directory = str_replace( home_url( '/' ), ABSPATH, $url );

        if ( is_dir( $directory ) ) {
            $iterator = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator(
                    $directory,
                    RecursiveDirectoryIterator::SKIP_DOTS
                )
            );

            foreach ( $iterator as $fileName => $fileObject ) {
                if ( self::fileNameLooksCrawlable( $fileName ) &&
                  self::filePathLooksCrawlable( $fileName ) ) {
                    array_push(
                        $files,
                        home_url( str_replace( ABSPATH, '', $fileName ) )
                    );
                }
            }
        }

        return $files;
    }


    /**
     * Check whether file appears to be crawlable
     *
     * @param string $file_name File
     * @return boolean
     */
    public static function fileNameLooksCrawlable( $file_name ) {
        return (
            ( ! strpos( $file_name, 'wp-static-html-output' ) !== false ) &&
            ( ! strpos( $file_name, 'previous-export' ) !== false ) &&
            is_file( $file_name )
        );
    }


    /**
     * Check whether path appears to be crawlable
     *
     * @param string $file_name Path
     * @return boolean
     */
    public static function filePathLooksCrawlable( $file_name ) {
        $path_info = pathinfo( $file_name );

        return (
            isset( $path_info['extension'] ) &&
            (
                ! in_array(
                    $path_info['extension'],
                    array( 'php', 'phtml', 'tpl' )
                )
            )
        );
    }


    /**
     * Build initial file list
     *
     * @param boolean $viaCLI           CLI flag
     * @param string  $uploadsPath      Uploads path
     * @param string  $uploadsURL       Uploads directory
     * @param string  $workingDirectory Working directory
     * @param string  $pluginHook       Reference to $this
     * @return integer
     */
    public static function buildInitialFileList(
        $viaCLI = false,
        $uploadsPath,
        $uploadsURL,
        $workingDirectory,
        $pluginHook
    ) {

        // TODO: how useful is this?
        set_time_limit( 0 );

        $exporter = wp_get_current_user();

        // setting path to store the archive dir path
        $archiveName = $workingDirectory . '/' . $pluginHook . '-' . time();

        // append username if done via UI
        if ( $exporter->user_login ) {
            $archiveName .= '-' . $exporter->user_login;
        }

        $archiveDir = $archiveName . '/';

        /**
         * saving the current archive name to file to persist across
         * requests / functions
         */
        file_put_contents(
            $uploadsPath . '/WP-STATIC-CURRENT-ARCHIVE',
            $archiveDir
        );

        if ( ! file_exists( $archiveDir ) ) {
            wp_mkdir_p( $archiveDir );
        }

        $baseUrl = untrailingslashit( home_url() );

        $urlsQueue = array_merge(
            array( trailingslashit( $baseUrl ) ),
            self::getListOfLocalFilesByUrl( get_template_directory_uri() ),
            self::getAllWPPostURLs( $baseUrl )
        );

        $urlsQueue = array_unique(
            array_merge(
                $urlsQueue,
                self::getListOfLocalFilesByUrl( $uploadsURL )
            )
        );

        $str = implode( "\n", $urlsQueue );
        file_put_contents( $uploadsPath . '/WP-STATIC-INITIAL-CRAWL-LIST', $str ); // TODO: using uploads path for initial file list build, subseqent one will all be done in working dir
        file_put_contents( $workingDirectory . '/WP-STATIC-CRAWLED-LINKS', '' );

        return count( $urlsQueue );
    }


    /**
     * Build final file list
     *
     * @param boolean $viaCLI           CLI flag
     * @param string  $additionalUrls   Additional URLs (newline separated)
     * @param string  $uploadsPath      Uploads path
     * @param string  $uploadsURL       Uploads URL
     * @param string  $workingDirectory Working directory
     * @param object  $pluginHook       Reference to $this
     * @return integer
     */
    public static function buildFinalFileList(
        $viaCLI = false,
        $additionalUrls,
        $uploadsPath, // TODO: also working dir?
        $uploadsURL,
        $workingDirectory,
        $pluginHook
    ) {
        /**
         * TODO: copy initial file list and process as per
         * TODO: ... generateModifiedFileList() notes
         */

        /**
         * saving the current archive name to file to persist across
         * requests / functions
         */
        file_put_contents(
            $workingDirectory . '/WP-STATIC-CURRENT-ARCHIVE',
            $archiveDir
        );

        if ( ! file_exists( $archiveDir ) ) {
            wp_mkdir_p( $archiveDir );
        }

        $baseUrl = untrailingslashit( home_url() );

        $urlsQueue = array_merge(
            array( trailingslashit( $baseUrl ) ),
            self::getListOfLocalFilesByUrl( get_template_directory_uri() ),
            self::getAllWPPostURLs( $baseUrl ),
            explode( "\n", $additionalUrls )
        );

        // TODO: shift this as an option to exclusions area
        $urlsQueue = array_unique(
            array_merge(
                $urlsQueue,
                self::getListOfLocalFilesByUrl( $uploadsURL )
            )
        );

        $str = implode( "\n", $urlsQueue );
        file_put_contents( $uploadsPath . '/WP-STATIC-INITIAL-CRAWL-LIST', $str ); // TODO: using uploads path for initial file list build, subseqent one will all be done in working dir
        file_put_contents( $workingDirectory . '/WP-STATIC-CRAWLED-LINKS', '' );

        return count( $urlsQueue );
    }


    /**
     * Get all post URLs
     *
     * @param string $wp_site_url Site URL
     * @return array
     */
    public static function getAllWPPostURLs( $wp_site_url ) {
        global $wpdb;

        /**
         * NOTE: re using $wpdb->ret_results vs WP_Query
         * https://wordpress.stackexchange.com/a/151843/20982
         * get_results may be faster, but more error prone
         *
         * TODO: benchmark difference and use WP_Query if not noticably slower
         *
         * NOTE: inherit post_status allows unlinked attachmnt pages to be
         * NOTE: ... created
         */
        $posts = $wpdb->get_results(
            "
            SELECT
                ID,
                post_type
            FROM
                {$wpdb->posts}
            WHERE
                post_status = 'publish' AND
                post_type NOT IN ('revision','nav_menu_item')
            "
        );

        $postURLs = array();

        foreach ( $posts as $post ) {
            switch ( $post->post_type ) {
                case 'page':
                    $permalink = get_page_link( $post->ID );
                    break;
                case 'post':
                    $permalink = get_permalink( $post->ID );
                    break;
                case 'attachment':
                    $permalink = get_attachment_link( $post->ID );
                    break;
                default:
                    $permalink = get_post_permalink( $post->ID );
                    break;
            }

            /**
             * Get the post's URL and each sub-chunk of the path as a URL
             *
             * Ex. http://domain.com/2018/01/01/my-post/ to yield:
             *
             * http://domain.com/2018/01/01/my-post/
             * http://domain.com/2018/01/01/
             * http://domain.com/2018/01/
             * http://domain.com/2018/
             */

            $parsed_link = parse_url( $permalink );

            // rely on WP's site URL vs reconstructing from parsed
            $link_host = $wp_site_url . '/';

            $link_path = $parsed_link['path'];

            // TODO: Windows filepath support?
            $path_segments = explode( '/', $link_path );

            // remove first and last empty elements
            array_shift( $path_segments );
            array_pop( $path_segments );

            $number_of_segments = count( $path_segments );

            // build each URL
            for ( $i = 0; $i < $number_of_segments; $i++ ) {
                $full_url = $link_host;

                for ( $x = 0; $x <= $i; $x++ ) {
                    $full_url .= $path_segments[ $x ] . '/';
                }
                $postURLs[] = $full_url;
            }
        }//end foreach

        // gets all category page links
        $args = array(
            'public'   => true,
        );

        $taxonomies = get_taxonomies( $args, 'objects' );
        foreach ( $taxonomies as $taxonomy ) {
            $terms = get_terms(
                $taxonomy->name,
                array(
                    'hide_empty' => true,
                )
            );

            foreach ( $terms as $term ) {
                $permalink = get_term_link( $term );

                $postURLs[] = trim( $permalink );
            }
        }

        // de-duplicate the array
        return array_unique( $postURLs );
    }
}
