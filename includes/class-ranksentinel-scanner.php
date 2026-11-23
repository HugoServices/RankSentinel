<?php
/**
 * SEO checks run by RankSentinel.
 *
 * @package RankSentinel
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Runs the checks and returns structured results.
 *
 * Each result is an array with:
 * - id      (string) Stable identifier of the check.
 * - label   (string) Human-readable name of the check.
 * - status  (string) good | warning | critical | info.
 * - message (string) What was found.
 * - advice  (string) What to do about it (empty when nothing to do).
 */
final class RankSentinel_Scanner {

	/**
	 * Runs every check.
	 *
	 * @return array<int, array<string, string>>
	 */
	public static function scan() {
		$results   = array();
		$results[] = self::check_visibility();

		$home = wp_remote_get(
			home_url( '/' ),
			array(
				'timeout'             => 10,
				'redirection'         => 3,
				'limit_response_size' => 1048576,
			)
		);

		$results[] = self::check_homepage_status( $home );
		$results[] = self::check_noindex( $home );
		$results[] = self::check_sitemap();

		return $results;
	}

	/**
	 * Computes a 0-100 score from the results (info results are ignored).
	 *
	 * @param array<int, array<string, string>> $results Scan results.
	 * @return int|null Null when no result can be scored.
	 */
	public static function score( $results ) {
		$points = array(
			'good'     => 100,
			'warning'  => 50,
			'critical' => 0,
		);
		$total  = 0;
		$count  = 0;
		foreach ( $results as $result ) {
			if ( isset( $points[ $result['status'] ] ) ) {
				$total += $points[ $result['status'] ];
				++$count;
			}
		}
		return $count ? (int) round( $total / $count ) : null;
	}

	/**
	 * Builds one result.
	 *
	 * @param string $id      Identifier.
	 * @param string $label   Label.
	 * @param string $status  Status.
	 * @param string $message Message.
	 * @param string $advice  Advice.
	 * @return array<string, string>
	 */
	private static function result( $id, $label, $status, $message, $advice = '' ) {
		return compact( 'id', 'label', 'status', 'message', 'advice' );
	}

	/**
	 * Settings → Reading → "Discourage search engines".
	 *
	 * @return array<string, string>
	 */
	private static function check_visibility() {
		$label = __( 'Search engine visibility', 'ranksentinel' );
		if ( '0' === (string) get_option( 'blog_public' ) ) {
			return self::result(
				'visibility',
				$label,
				'critical',
				__( 'WordPress asks search engines not to index this site.', 'ranksentinel' ),
				__( 'Go to Settings → Reading and uncheck “Discourage search engines from indexing this site”.', 'ranksentinel' )
			);
		}
		return self::result( 'visibility', $label, 'good', __( 'Search engines are allowed to index this site.', 'ranksentinel' ) );
	}

	/**
	 * HTTP status of the homepage.
	 *
	 * @param array|WP_Error $home Response of the homepage request.
	 * @return array<string, string>
	 */
	private static function check_homepage_status( $home ) {
		$label = __( 'Homepage response', 'ranksentinel' );
		if ( is_wp_error( $home ) ) {
			return self::result(
				'homepage',
				$label,
				'critical',
				/* translators: %s: error message. */
				sprintf( __( 'The homepage could not be reached: %s', 'ranksentinel' ), $home->get_error_message() ),
				__( 'Some hosts block requests from a site to itself. Check that the homepage opens normally in a browser.', 'ranksentinel' )
			);
		}

		$code = (int) wp_remote_retrieve_response_code( $home );
		/* translators: %d: HTTP status code. */
		$message = sprintf( __( 'The homepage answers with HTTP code %d.', 'ranksentinel' ), $code );

		if ( $code >= 200 && $code < 300 ) {
			return self::result( 'homepage', $label, 'good', $message );
		}
		if ( $code >= 300 && $code < 400 ) {
			return self::result( 'homepage', $label, 'warning', $message, __( 'The homepage redirects too many times. Check your redirects and the site address in Settings → General.', 'ranksentinel' ) );
		}
		return self::result( 'homepage', $label, 'critical', $message, __( 'Visitors and search engines cannot see the homepage. Check your hosting, maintenance mode and security plugins.', 'ranksentinel' ) );
	}

	/**
	 * Noindex directives on the homepage (X-Robots-Tag header and robots meta tags).
	 *
	 * @param array|WP_Error $home Response of the homepage request.
	 * @return array<string, string>
	 */
	private static function check_noindex( $home ) {
		$label = __( 'Homepage indexing', 'ranksentinel' );
		if ( is_wp_error( $home ) ) {
			return self::result( 'noindex', $label, 'info', __( 'Not checked, because the homepage could not be reached.', 'ranksentinel' ) );
		}

		$header = wp_remote_retrieve_header( $home, 'x-robots-tag' );
		if ( is_array( $header ) ) {
			$header = implode( ' ', $header );
		}
		$body   = wp_remote_retrieve_body( $home );
		$robots = '';
		if ( preg_match_all( '/<meta\s+[^>]*name\s*=\s*["\'](?:robots|googlebot)["\'][^>]*>/i', $body, $matches ) ) {
			$robots = implode( ' ', $matches[0] );
		}

		if ( false !== stripos( (string) $header . ' ' . $robots, 'noindex' ) ) {
			return self::result(
				'noindex',
				$label,
				'critical',
				__( 'A “noindex” directive was found: search engines are asked not to index the homepage.', 'ranksentinel' ),
				__( 'Check the settings of your SEO plugin for the homepage, and the X-Robots-Tag header of your server.', 'ranksentinel' )
			);
		}
		return self::result( 'noindex', $label, 'good', __( 'No “noindex” directive found in the standard robots directives.', 'ranksentinel' ) );
	}

	/**
	 * WordPress core sitemap.
	 *
	 * @return array<string, string>
	 */
	private static function check_sitemap() {
		$label = __( 'XML sitemap', 'ranksentinel' );
		$url   = home_url( '/wp-sitemap.xml' );
		$args  = array(
			'timeout'     => 10,
			'redirection' => 3,
		);

		$response = wp_remote_head( $url, $args );
		// Some servers refuse HEAD requests: try again with GET.
		if ( ! is_wp_error( $response ) && 405 === (int) wp_remote_retrieve_response_code( $response ) ) {
			$args['limit_response_size'] = 65536;
			$response                    = wp_remote_get( $url, $args );
		}

		if ( is_wp_error( $response ) ) {
			return self::result( 'sitemap', $label, 'info', __( 'Not checked, because the request failed.', 'ranksentinel' ) );
		}

		$code = (int) wp_remote_retrieve_response_code( $response );
		if ( 200 === $code ) {
			return self::result( 'sitemap', $label, 'good', __( 'The WordPress sitemap is available at /wp-sitemap.xml.', 'ranksentinel' ) );
		}
		return self::result(
			'sitemap',
			$label,
			'warning',
			/* translators: %d: HTTP status code. */
			sprintf( __( 'The WordPress sitemap answers with HTTP code %d.', 'ranksentinel' ), $code ),
			__( 'If you use Yoast, Rank Math or SEOPress, your sitemap is probably at /sitemap_index.xml: check it in your SEO plugin.', 'ranksentinel' )
		);
	}
}
