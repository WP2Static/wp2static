<?php

class WPSHO_PostSettings {

    public static function get( $sets = array() ) {

        $settings = array();
        $key_sets = array();
        $target_keys = array();

        $key_sets['general'] = array(
            'baseUrl',
            'debug_mode',
            'selected_deployment_option',
        );

        $key_sets['crawling'] = array(
            'additionalUrls',
            'basicAuthPassword',
            'basicAuthUser',
            'crawlPort',
            'crawl_delay',
            'detection_level',
            'parse_css',
            'excludeURLs',
            'simultaneousCurlRequests',
            'useBasicAuth',
        );

        $key_sets['processing'] = array(
            'removeConditionalHeadComments',
            'allowOfflineUsage',
            'baseHREF',
            'rewrite_rules',
            'rename_rules',
            'removeWPMeta',
            'removeWPLinks',
            'useBaseHref',
            'useRelativeURLs',
            'removeConditionalHeadComments',
            'removeWPMeta',
            'removeWPLinks',
            'removeHTMLComments',
            'removeCanonical',
        );

        $key_sets['advanced'] = array(
            'crawl_increment',
            'completionEmail',
            'delayBetweenAPICalls',
            'deployBatchSize',
        );

        $key_sets['folder'] = array(
            'baseUrl-folder',
            'targetFolder',
        );

        $key_sets['zip'] = array(
            'baseUrl-zip',
            'allowOfflineUsage',
        );

        $key_sets['github'] = array(
            'baseUrl-github',
            'ghBranch',
            'ghPath',
            'ghToken',
            'ghRepo',
            'ghCommitMessage',
        );

        $key_sets['bitbucket'] = array(
            'baseUrl-bitbucket',
            'bbBranch',
            'bbPath',
            'bbToken',
            'bbRepo',
        );

        $key_sets['gitlab'] = array(
            'baseUrl-gitlab',
            'glBranch',
            'glPath',
            'glToken',
            'glProject',
        );

        $key_sets['ftp'] = array(
            'baseUrl-ftp',
            'ftpPassword',
            'ftpRemotePath',
            'ftpServer',
            'ftpPort',
            'ftpTLS',
            'ftpUsername',
            'useActiveFTP',
        );

        $key_sets['bunnycdn'] = array(
            'baseUrl-bunnycdn',
            'bunnycdnStorageZoneAccessKey',
            'bunnycdnPullZoneAccessKey',
            'bunnycdnPullZoneID',
            'bunnycdnStorageZoneName',
            'bunnycdnRemotePath',
        );

        $key_sets['s3'] = array(
            'baseUrl-s3',
            'cfDistributionId',
            's3Bucket',
            's3Key',
            's3Region',
            's3RemotePath',
            's3Secret',
            's3CacheControl',
        );

        $key_sets['netlify'] = array(
            'baseUrl-netlify',
            'netlifyHeaders',
            'netlifyPersonalAccessToken',
            'netlifyRedirects',
            'netlifySiteID',
        );

        $key_sets['wpenv'] = array(
            'wp_site_url',
            'wp_site_path',
            'wp_site_subdir',
            'wp_uploads_path',
            'wp_uploads_url',
            'baseUrl',
            'wp_active_theme',
            'wp_themes',
            'wp_uploads',
            'wp_plugins',
            'wp_content',
            'wp_inc',
        );

        foreach ( $sets as $set ) {
            $target_keys = array_merge( $target_keys, $key_sets[ $set ] );
        }

        // @codingStandardsIgnoreStart
        foreach ( $target_keys as $key ) {
            $settings[ $key ] =
                isset( $_POST[ $key ] ) ?
                $_POST[ $key ] :
                null;
        }
        // @codingStandardsIgnoreEnd

        /*
            Settings requiring transformation
        */

        // @codingStandardsIgnoreStart
        $settings['crawl_increment'] =
            isset( $_POST['crawl_increment'] ) ?
            (int) $_POST['crawl_increment'] :
            1;

        // any baseUrl required if creating an offline ZIP
        $settings['baseUrl'] =
            isset( $_POST['baseUrl'] ) ?
            rtrim( $_POST['baseUrl'], '/' ) . '/' :
            'http://OFFLINEZIP.wpsho';
        // @codingStandardsIgnoreEnd

        return array_filter( $settings );
    }
}

