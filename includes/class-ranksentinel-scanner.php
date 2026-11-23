<?php
if (!defined('ABSPATH')) { exit; }
final class RankSentinel_Scanner {
    public static function scan() {
        $results = array();
        $results['Search engine visibility'] = get_option('blog_public') === '0' ? 'Warning: search engines discouraged' : 'OK: search engines allowed';
        $home = wp_remote_get(home_url('/'), array('timeout' => 10, 'redirection' => 3, 'limit_response_size' => 1048576));
        if (is_wp_error($home)) {
            $results['Homepage'] = 'Request failed: ' . $home->get_error_message();
        } else {
            $code = wp_remote_retrieve_response_code($home);
            $results['Homepage HTTP status'] = (string) $code;
            $header = wp_remote_retrieve_header($home, 'x-robots-tag');
            $body = wp_remote_retrieve_body($home);
            $robots = '';
            if (preg_match('/<meta\s+[^>]*name\s*=\s*["\']robots["\'][^>]*>/i', $body, $matches)) {
                $robots = $matches[0];
            }
            $results['Homepage noindex'] = (stripos((string) $header . ' ' . $robots, 'noindex') !== false) ? 'Warning: noindex detected' : 'No noindex detected in standard robots directives';
        }
        $sitemap = wp_remote_head(home_url('/wp-sitemap.xml'), array('timeout' => 10, 'redirection' => 3));
        $results['WordPress core sitemap'] = is_wp_error($sitemap) ? 'Request failed' : 'HTTP ' . wp_remote_retrieve_response_code($sitemap) . ' (SEO plugins may use a different sitemap URL)';
        return $results;
    }
}
