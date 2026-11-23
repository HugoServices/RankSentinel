<?php
/**
 * Plugin Name: RankSentinel
 * Description: Lightweight SEO health checks for WordPress: homepage indexability and WordPress core sitemap availability.
 * Version: 0.1.0
 * Requires PHP: 7.4
 * License: GPL-2.0-or-later
 * Text Domain: ranksentinel
 */
if (!defined('ABSPATH')) { exit; }
require_once __DIR__ . '/includes/class-ranksentinel-scanner.php';
add_action('admin_menu', function () {
    add_management_page('RankSentinel', 'RankSentinel', 'manage_options', 'ranksentinel', 'ranksentinel_render_page');
});
function ranksentinel_render_page() {
    if (!current_user_can('manage_options')) { return; }
    if (isset($_POST['ranksentinel_scan']) && check_admin_referer('ranksentinel_scan_action')) {
        $results = RankSentinel_Scanner::scan();
        update_option('ranksentinel_last_scan', array('time' => current_time('mysql'), 'results' => $results), false);
    }
    $last = get_option('ranksentinel_last_scan');
    echo '<div class="wrap"><h1>RankSentinel</h1><p>Run a manual SEO health check. No external service required.</p>';
    echo '<form method="post">'; wp_nonce_field('ranksentinel_scan_action'); submit_button('Run SEO check', 'primary', 'ranksentinel_scan'); echo '</form>';
    if (is_array($last)) {
        echo '<h2>Last scan: ' . esc_html($last['time']) . '</h2><table class="widefat striped"><thead><tr><th>Check</th><th>Result</th></tr></thead><tbody>';
        foreach ($last['results'] as $label => $value) {
            echo '<tr><td>' . esc_html($label) . '</td><td>' . esc_html($value) . '</td></tr>';
        }
        echo '</tbody></table>';
    }
    echo '</div>';
}
