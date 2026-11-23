<?php
/**
 * Plugin Name:       RankSentinel
 * Plugin URI:        https://github.com/HugoServices/RankSentinel
 * Description:       Lightweight SEO health checks for WordPress: search engine visibility, homepage response, noindex directives and sitemap.
 * Version:           0.2.0
 * Requires at least: 6.5
 * Requires PHP:      7.4
 * Author:            Hugo Services
 * Author URI:        https://www.hugoherve.fr
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       ranksentinel
 * Domain Path:       /languages
 *
 * @package RankSentinel
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'RANKSENTINEL_VERSION', '0.2.0' );
define( 'RANKSENTINEL_URL', plugin_dir_url( __FILE__ ) );

require_once __DIR__ . '/includes/class-ranksentinel-scanner.php';

add_action(
	'init',
	function () {
		load_plugin_textdomain( 'ranksentinel', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
	}
);

add_action(
	'admin_menu',
	function () {
		$hook = add_management_page( 'RankSentinel', 'RankSentinel', 'manage_options', 'ranksentinel', 'ranksentinel_render_page' );
		add_action( 'load-' . $hook, 'ranksentinel_handle_scan' );
	}
);

add_action(
	'admin_enqueue_scripts',
	function ( $hook ) {
		if ( 'tools_page_ranksentinel' !== $hook ) {
			return;
		}
		wp_enqueue_style( 'ranksentinel-admin', RANKSENTINEL_URL . 'assets/admin.css', array(), RANKSENTINEL_VERSION );
	}
);

add_filter(
	'plugin_action_links_' . plugin_basename( __FILE__ ),
	function ( $links ) {
		$url = admin_url( 'tools.php?page=ranksentinel' );
		array_unshift( $links, '<a href="' . esc_url( $url ) . '">' . esc_html__( 'Run a check', 'ranksentinel' ) . '</a>' );
		return $links;
	}
);

/**
 * Runs a scan when the form is submitted, then redirects (no resubmission on reload).
 */
function ranksentinel_handle_scan() {
	if ( ! isset( $_POST['ranksentinel_scan'] ) || ! current_user_can( 'manage_options' ) ) {
		return;
	}
	check_admin_referer( 'ranksentinel_scan_action' );

	$results = RankSentinel_Scanner::scan();
	update_option(
		'ranksentinel_last_scan',
		array(
			'version'   => 2,
			'timestamp' => time(),
			'results'   => $results,
		),
		false
	);

	wp_safe_redirect( add_query_arg( 'ranksentinel_done', '1', admin_url( 'tools.php?page=ranksentinel' ) ) );
	exit;
}

/**
 * Status labels and icons.
 *
 * @return array<string, array<string, string>>
 */
function ranksentinel_statuses() {
	return array(
		'good'     => array(
			'label' => __( 'OK', 'ranksentinel' ),
			'icon'  => 'dashicons-yes-alt',
		),
		'warning'  => array(
			'label' => __( 'To check', 'ranksentinel' ),
			'icon'  => 'dashicons-warning',
		),
		'critical' => array(
			'label' => __( 'Critical', 'ranksentinel' ),
			'icon'  => 'dashicons-dismiss',
		),
		'info'     => array(
			'label' => __( 'Not checked', 'ranksentinel' ),
			'icon'  => 'dashicons-info',
		),
	);
}

/**
 * Summary label such as "2 critical", with plural forms.
 *
 * @param string $status Status.
 * @param int    $count  Number of results with this status.
 * @return string
 */
function ranksentinel_count_label( $status, $count ) {
	switch ( $status ) {
		case 'good':
			/* translators: %d: number of checks. */
			$label = _n( '%d OK', '%d OK', $count, 'ranksentinel' );
			break;
		case 'warning':
			/* translators: %d: number of checks. */
			$label = _n( '%d to check', '%d to check', $count, 'ranksentinel' );
			break;
		case 'critical':
			/* translators: %d: number of checks. */
			$label = _n( '%d critical', '%d critical', $count, 'ranksentinel' );
			break;
		default:
			/* translators: %d: number of checks. */
			$label = _n( '%d not checked', '%d not checked', $count, 'ranksentinel' );
	}
	return sprintf( $label, $count );
}

/**
 * Renders the Tools → RankSentinel page.
 */
function ranksentinel_render_page() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}

	$last    = get_option( 'ranksentinel_last_scan' );
	$has_run = is_array( $last ) && isset( $last['version'] ) && 2 === (int) $last['version'] && ! empty( $last['results'] );
	$results = $has_run ? $last['results'] : array();
	$score   = $has_run ? RankSentinel_Scanner::score( $results ) : null;
	$counts  = array_fill_keys( array( 'good', 'warning', 'critical', 'info' ), 0 );
	foreach ( $results as $result ) {
		if ( isset( $counts[ $result['status'] ] ) ) {
			++$counts[ $result['status'] ];
		}
	}
	$statuses = ranksentinel_statuses();

	if ( null === $score ) {
		$tone = 'none';
	} elseif ( $score >= 90 ) {
		$tone = 'good';
	} elseif ( $score >= 50 ) {
		$tone = 'warning';
	} else {
		$tone = 'critical';
	}
	?>
	<div class="wrap rs-wrap">
		<h1 class="screen-reader-text">RankSentinel</h1>

		<header class="rs-hero">
			<div class="rs-hero__brand">
				<span class="rs-logo" aria-hidden="true">
					<svg viewBox="0 0 24 24" width="28" height="28" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/><path d="m9 12 2 2 4-4"/></svg>
				</span>
				<div>
					<p class="rs-hero__title">RankSentinel</p>
					<p class="rs-hero__subtitle"><?php esc_html_e( 'Quick SEO health check of your site', 'ranksentinel' ); ?></p>
				</div>
			</div>
			<form method="post" class="rs-hero__action">
				<?php wp_nonce_field( 'ranksentinel_scan_action' ); ?>
				<button type="submit" name="ranksentinel_scan" value="1" class="rs-button">
					<span class="dashicons dashicons-update" aria-hidden="true"></span>
					<?php $has_run ? esc_html_e( 'Run a new check', 'ranksentinel' ) : esc_html_e( 'Run the check', 'ranksentinel' ); ?>
				</button>
			</form>
		</header>

		<?php if ( isset( $_GET['ranksentinel_done'] ) && $has_run ) : // phpcs:ignore WordPress.Security.NonceVerification.Recommended ?>
			<div class="notice notice-success is-dismissible"><p><?php esc_html_e( 'Check complete. Here are the results.', 'ranksentinel' ); ?></p></div>
		<?php endif; ?>

		<?php if ( ! $has_run ) : ?>
			<section class="rs-empty">
				<span class="dashicons dashicons-search" aria-hidden="true"></span>
				<h2><?php esc_html_e( 'No check yet', 'ranksentinel' ); ?></h2>
				<p><?php esc_html_e( 'Run a check to see in a few seconds whether search engines can find and index your site. Nothing is sent to an outside service.', 'ranksentinel' ); ?></p>
			</section>
		<?php else : ?>
			<section class="rs-summary rs-tone-<?php echo esc_attr( $tone ); ?>">
				<div class="rs-score" role="img" aria-label="<?php echo esc_attr( sprintf( /* translators: %d: score out of 100. */ __( 'SEO score: %d out of 100', 'ranksentinel' ), (int) $score ) ); ?>">
					<svg viewBox="0 0 120 120" aria-hidden="true">
						<circle class="rs-score__track" cx="60" cy="60" r="52"/>
						<circle class="rs-score__value" cx="60" cy="60" r="52" pathLength="100" stroke-dasharray="<?php echo esc_attr( (int) $score ); ?> 100"/>
					</svg>
					<span class="rs-score__number"><?php echo esc_html( (int) $score ); ?><small>/100</small></span>
				</div>
				<div class="rs-summary__text">
					<h2>
						<?php
						if ( 'good' === $tone ) {
							esc_html_e( 'Your site is ready for search engines', 'ranksentinel' );
						} elseif ( 'warning' === $tone ) {
							esc_html_e( 'A few points need your attention', 'ranksentinel' );
						} else {
							esc_html_e( 'Search engines may not see your site', 'ranksentinel' );
						}
						?>
					</h2>
					<p class="rs-summary__date">
						<?php
						/* translators: %s: date and time of the last check. */
						printf( esc_html__( 'Last check: %s', 'ranksentinel' ), esc_html( wp_date( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), (int) $last['timestamp'] ) ) );
						?>
					</p>
					<ul class="rs-pills">
						<?php foreach ( $counts as $status => $count ) : ?>
							<?php if ( $count ) : ?>
								<li class="rs-pill rs-pill--<?php echo esc_attr( $status ); ?>"><?php echo esc_html( ranksentinel_count_label( $status, $count ) ); ?></li>
							<?php endif; ?>
						<?php endforeach; ?>
					</ul>
				</div>
			</section>

			<section class="rs-grid">
				<?php foreach ( $results as $result ) : ?>
					<?php $status = isset( $statuses[ $result['status'] ] ) ? $result['status'] : 'info'; ?>
					<article class="rs-card rs-card--<?php echo esc_attr( $status ); ?>">
						<header class="rs-card__head">
							<span class="rs-card__icon dashicons <?php echo esc_attr( $statuses[ $status ]['icon'] ); ?>" aria-hidden="true"></span>
							<h3><?php echo esc_html( $result['label'] ); ?></h3>
							<span class="rs-badge rs-badge--<?php echo esc_attr( $status ); ?>"><?php echo esc_html( $statuses[ $status ]['label'] ); ?></span>
						</header>
						<p class="rs-card__message"><?php echo esc_html( $result['message'] ); ?></p>
						<?php if ( ! empty( $result['advice'] ) ) : ?>
							<p class="rs-card__advice"><span class="dashicons dashicons-lightbulb" aria-hidden="true"></span><?php echo esc_html( $result['advice'] ); ?></p>
						<?php endif; ?>
					</article>
				<?php endforeach; ?>
			</section>
		<?php endif; ?>

		<footer class="rs-footer">
			<?php
			printf(
				/* translators: 1: version number, 2: link to the GitHub repository. */
				esc_html__( 'RankSentinel %1$s · free and open-source plugin · %2$s', 'ranksentinel' ),
				esc_html( RANKSENTINEL_VERSION ),
				'<a href="https://github.com/HugoServices/RankSentinel" target="_blank" rel="noopener noreferrer">GitHub</a>'
			);
			?>
		</footer>
	</div>
	<?php
}
