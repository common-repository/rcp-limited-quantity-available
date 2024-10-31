<?php
/**
 * Membership Level Settings
 *
 * @copyright Copyright (c) 2018, Restrict Content Pro team
 * @license   http://opensource.org/licenses/gpl-2.0.php GNU Public License
 */

namespace RCP\Addon\LimitedQuantityAvailable\Settings;

defined( 'WPINC' ) or die;

/**
 * Displays the quantity available form field on the membership level edit screen.
 *
 * @param object $level Levle object.
 *
 * @since 1.0
 * @return void
 */
function subscription_form_fields( $level ) {

	/**
	 * @var $rcp_levels_db \RCP_Levels
	 */
	global $rcp_levels_db;
	$qty = empty( $level->id ) ? 0 : $rcp_levels_db->get_meta( $level->id, 'level_quantity_available', true );
	?>
	<tr class="form-field" id="rcp-lqa-wrapper">
		<th scope="row" valign="top">
			<label for="rcp-lqa-total-available"><?php _e( 'Total Quantity Available', 'rcp-lqa' ); ?></label>
		</th>

		<td>
			<input type="number" id="rcp-lqa-total-available" name="rcp-lqa-total-available" value="<?php echo esc_attr( $qty ); ?>" style="width: 100px;">
			<p class="description"><?php _e( 'Limit the total number of times this membership level can be sold. For example, enter 20 to allow a maximum of 20 sales. After 20 sales, the membership level will not show on your registration form. Renewal payments do not count towards total sales. Enter 0 for unlimited. If you set up limited quantities on a membership level that already has sales, those sales will count towards the total.', 'rcp-lqa' ); ?></p>
		</td>
	</tr>
	<?php
	wp_nonce_field( 'rcp_lqa_level_nonce', 'rcp_lqa_level_nonce' );
}
add_action( 'rcp_edit_subscription_form', '\RCP\Addon\LimitedQuantityAvailable\Settings\subscription_form_fields' );
add_action( 'rcp_add_subscription_form', '\RCP\Addon\LimitedQuantityAvailable\Settings\subscription_form_fields' );

/**
 * Saves the quantity available defined on the membership level edit screen.
 *
 * @param int   $id   ID of the level being added or edited.
 * @param array $args Arguments for updating the membership level.
 *
 * @since 1.0
 * @return void
 */
function save_subscription_form_fields( $id, $args ) {

	if ( isset( $_GET['activate_subscription'] ) || isset( $_GET['deactivate_subscription'] ) ) {
		return;
	}

	if ( empty( $_POST['rcp_lqa_level_nonce' ] ) || ! wp_verify_nonce( $_POST['rcp_lqa_level_nonce'], 'rcp_lqa_level_nonce' ) ) {
		return;
	}

	if ( ! current_user_can( 'rcp_manage_levels' ) ) {
		return;
	}

	/**
	 * @var $rcp_levels_db \RCP_Levels
	 */
	global $rcp_levels_db, $rcp_payments_db_name, $wpdb;

	if ( empty( $_POST['rcp-lqa-total-available'] ) ) {
		$rcp_levels_db->delete_meta( $id, 'level_quantity_available' );
		return;
	}

	/**
	 * Update quantity sold for existing levels if they weren't previously enabled for limited quantity.
	 */
	$qty_available = (int) $rcp_levels_db->get_meta( $id, 'level_quantity_available', true );
	if ( empty( $qty_available ) ) {
		$level_name = rcp_get_subscription_name( $id );
		$unique_sales = (int) $wpdb->query( $wpdb->prepare( "SELECT DISTINCT user_id FROM {$rcp_payments_db_name} WHERE subscription = %s and status = 'complete'", $level_name ) );
		$rcp_levels_db->update_meta( $id, 'level_quantity_sold', $unique_sales );
	}

	$rcp_levels_db->update_meta( $id, 'level_quantity_available', absint( $_POST['rcp-lqa-total-available'] ) );
}
add_action( 'rcp_edit_subscription_level', '\RCP\Addon\LimitedQuantityAvailable\Settings\save_subscription_form_fields', 10, 2 );
add_action( 'rcp_add_subscription', '\RCP\Addon\LimitedQuantityAvailable\Settings\save_subscription_form_fields', 10, 2 );