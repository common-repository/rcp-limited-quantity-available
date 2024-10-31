<?php
/**
 * Plugin Name: Restrict Content Pro - Limited Quantity Available
 * Plugin URI: https://restrictcontentpro.com/downloads/limited-quantity-available/
 * Description: Control the number of times a membership level can be purchased.
 * Author: iThemes, LLC
 * Author URI: https://ithemes.com
 * Contributors: jthillithemes, layotte, ithemes
 * Version: 1.0.3
 * Text Domain: rcp-lqa
 * Domain Path: languages
 * iThemes Package: rcp-limited-quantity-available
 */
namespace RCP\Addon\LimitedQuantityAvailable;

defined( 'WPINC' ) or die;

define( 'RCP_LQA_VERSION', '1.0.3' );
define( 'RCP_LQA_PATH', plugin_dir_path( __FILE__ ) );
define( 'RCP_LQA_URL', plugin_dir_url( __FILE__ ) );

/**
 * Loads the plugin files.
 *
 * @since 1.0
 * @return void
 */
function loader() {

	if ( ! defined( 'RCP_PLUGIN_VERSION' ) || version_compare( RCP_PLUGIN_VERSION, 2.6, '<' ) ) {
		add_action( 'admin_notices', '\RCP\Addon\LimitedQuantityAvailable\incompatible_version_notice' );
		return;
	}

	require_once RCP_LQA_PATH . 'settings/subscription-levels.php';

	add_action( 'rcp_insert_payment', '\RCP\Addon\LimitedQuantityAvailable\increment_quantity_sold', 10, 2 );
	add_filter( 'rcp_template_stack', '\RCP\Addon\LimitedQuantityAvailable\modify_template_stack' );
	add_filter( 'rcp_get_template_part', '\RCP\Addon\LimitedQuantityAvailable\sold_out_template', 10, 3 );
	add_filter( 'register_form_stripe', '\RCP\Addon\LimitedQuantityAvailable\register_form_stripe', 10, 2 );

}
add_action( 'plugins_loaded', '\RCP\Addon\LimitedQuantityAvailable\loader' );

/**
 * Displays an admin notice if using an incompatible version of RCP core.
 *
 * @since 1.0
 * @return void
 */
function incompatible_version_notice() {
	echo '<div class="error"><p>' . __( 'Limited Quantity Available requires Restrict Content Pro version 2.6 or higher. Please upgrade Restrict Content Pro to the latest version.', 'rcp-lqa' ) . '</p></div>';
}

/**
 * Loads the plugin translation files.
 *
 * @since 1.0
 * @return void
 */
function textdomain() {
	load_plugin_textdomain( 'rcp-lqa', false, RCP_LQA_PATH . 'languages' );
}
add_action( 'plugins_loaded', '\RCP\Addon\LimitedQuantityAvailable\textdomain' );

/**
 * Gets the level quantity available for the specified level ID.
 *
 * @param int $level_id The subscription level ID.
 *
 * @since 1.0
 * @return int The quantity available.
 */
function get_level_quantity_available( $level_id ) {
	/**
	 * @var $rcp_levels_db \RCP_Levels
	 */
	global $rcp_levels_db;
	return (int) apply_filters( 'rcp_lqa_level_quantity_available', $rcp_levels_db->get_meta( $level_id, 'level_quantity_available', true ), $level_id );
}

/**
 * Gets the total number of unique purchases for the specified level ID.
 *
 * @param int $level_id The subscription level ID.
 *
 * @since 1.0
 * @return int The total number of unique sales.
 */
function get_level_quantity_sold( $level_id ) {
	/**
	 * @var $rcp_levels_db \RCP_Levels
	 */
	global $rcp_levels_db;
	return (int) apply_filters( 'rcp_lqa_level_quantity_sold', $rcp_levels_db->get_meta( $level_id, 'level_quantity_sold', true ), $level_id );
}

/**
 * Increments the quantity sold counter for the subscription level.
 * and deactivates the level when it reaches the limited quantity available.
 *
 * @param int   $payment_id ID of the payment.
 * @param array $data Payment information
 *
 * @since 1.0
 * @return void
 */
function increment_quantity_sold( $payment_id, $data ) {

	/**
	 * @var $rcp_levels_db \RCP_Levels
	 */
	global $rcp_levels_db;

	$member = new \RCP_Member( $data['user_id'] );

	$level_id = $member->get_subscription_id();

	$qty_available = get_level_quantity_available( $level_id );

	if ( ! $qty_available ) {
		return;
	}

	/**
	 * We need a way to see if this is a renewal payment, since we do not
	 * want to count renewals as part of the total quantity sold. RCP does
	 * not save whether or not a payment is a renewal, and checking to see
	 * if the member is_recurring is not a reliable indicator, because they
	 * may be doing manual renewals. So we see if the member has more than
	 * 1 payment for this subscription.
	 */
	$rcp_payments = new \RCP_Payments;

	$member_payments = $rcp_payments->get_payments( array(
		'user_id'      => $member->ID,
		'subscription' => $data['subscription'],
		'status'       => 'complete',
		'number'       => 2
	) );

	if ( count( $member_payments ) > 1 ) {
		return;
	}

	$sold = get_level_quantity_sold( $level_id );
	$sold = ! empty( $sold ) ? $sold : 0;
	$sold++;

	$rcp_levels_db->update_meta( $level_id, 'level_quantity_sold', $sold );

	// Deactivate the subscription level if the qty sold has reached the qty available
	if ( $sold >= $qty_available ) {
		$rcp_levels_db->update( $level_id, array( 'status' => 'inactive' ) );
	}
}

/**
 * Add the plugin's template folder to the RCP template stack.
 *
 * @param array $template_stack
 *
 * @since 1.0
 * @return array
 */
function modify_template_stack( $template_stack ) {
	$template_stack[] = RCP_LQA_PATH . 'templates';

	return $template_stack;
}

/**
 * Loads the sold out template if needed.
 *
 * @param array $templates Array of possible template file names.
 * @param string $slug     Template slug.
 * @param string $name     Template name.
 *
 * @since 1.0
 * @return array
 */
function sold_out_template( $templates, $slug, $name ) {

	if ( 'register' !== $slug && 'single' !== $name ) {
		return $templates;
	}

	global $rcp_level;

	$qty_available = get_level_quantity_available( $rcp_level );

	if ( ! $qty_available ) {
		return $templates;
	}

	// Allow existing subscribers renew if they have the same level
	if ( is_user_logged_in() && $rcp_level == rcp_get_subscription_id( get_current_user_id() ) ) {
		return $templates;
	}

	$sold = get_level_quantity_sold( $rcp_level );

	if ( $sold >= $qty_available ) {
		remove_filter( 'rcp_get_template_part', '\RCP\Addon\LimitedQuantityAvailable\sold_out_template', 10 );
		$templates = rcp_get_template_part( 'register', 'single-sold-out' );
		add_filter( 'rcp_get_template_part', '\RCP\Addon\LimitedQuantityAvailable\sold_out_template', 10, 3 );
	}

	return $templates;
}

/**
 * Modify the contents of `[register_form_stripe]` to show the sold out template if the subscription level is sold out.
 *
 * @param string $output Shortcode output.
 * @param array  $atts   Shortcode attributes.
 *
 * @since 1.0.1
 * @return string
 */
function register_form_stripe( $output, $atts ) {

	$rcp_level = absint( $atts['id'] );

	$qty_available = get_level_quantity_available( $rcp_level );

	if ( empty( $qty_available ) ) {
		return $output;
	}

	// Allow existing subscribers to renew if they have the same level.
	if ( is_user_logged_in() && $rcp_level == rcp_get_subscription_id( get_current_user_id() ) ) {
		return $output;
	}

	$sold = get_level_quantity_sold( $rcp_level );

	// Load sold out template if the subscription level is sold out.
	if ( $sold >= $qty_available ) {
		ob_start();
		rcp_get_template_part( 'register', 'single-sold-out' );
		$output = ob_get_clean();
	}

	return $output;

}

if ( ! function_exists( 'ithemes_rcp_limited_quantity_available_updater_register' ) ) {
	function ithemes_rcp_limited_quantity_available_updater_register( $updater ) {
		$updater->register( 'REPO', __FILE__ );
	}
	add_action( 'ithemes_updater_register', 'ithemes_rcp_limited_quantity_available_updater_register' );

	require( __DIR__ . '/lib/updater/load.php' );
}