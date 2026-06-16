<?php

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Display the main settings page wrapper.
 *
 * @author    David Bisset
 * @package   Charitable/Admin View/Settings
 * @copyright Copyright (c) 2023, WP Charitable LLC
 * @license   http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since     1.0.0
 * @version   1.8.9
 */

$charitable_active_tab      = isset( $_GET['tab'] ) ? esc_html( $_GET['tab'] ) : 'general'; // phpcs:ignore
$charitable_tab_no_form_tag = array( 'import', 'export', 'tools' );
$charitable_group           = isset( $_GET['group'] ) ? esc_html( $_GET['group'] ) : $charitable_active_tab; // phpcs:ignore
$charitable_sections        = charitable_get_admin_settings()->get_sections();
$charitable_show_return     = $charitable_group !== $charitable_active_tab;
$charitable_css             = '';

if ( $charitable_show_return ) {
	/**
	 * Filter the return link text.
	 *
	 * @since 1.6.19
	 *
	 * @param string $default    The default return link text.
	 * @param string $active_tab The active tab.
	 * @param string $group      The current group.
	 */
	$charitable_return_tab_text = apply_filters(
		'charitable_settings_return_tab_text',
		sprintf(
			/* translators: %s: tab name */
			__( '&#8592; Return to %s', 'charitable' ),
			$charitable_active_tab
		),
		$charitable_active_tab,
		$charitable_group
	);

	/**
	 * Filter the return link URL.
	 *
	 * @since 1.6.19
	 *
	 * @param string $default   The default return link URL
	 * @param string $active_tab The active tab.
	 * @param string $group      The current group.
	 */
	$charitable_return_tab_url = apply_filters(
		'charitable_settings_return_tab_url',
		add_query_arg(
			array( 'tab' => $charitable_active_tab ),
			admin_url( 'admin.php?page=charitable-settings' )
		),
		$charitable_active_tab,
		$charitable_group
	);
}

ob_start();
?>
<div id="charitable-settings" class="wrap">
	<h1 class="screen-reader-text"><?php echo get_admin_page_title(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></h1>
	<h1><?php echo get_admin_page_title(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></h1>
	<?php do_action( 'charitable_maybe_show_notification' ); ?>
	<h2 class="nav-tab-wrapper">
		<?php foreach ( $charitable_sections as $charitable_section_key => $charitable_section_name ) : ?>
			<?php
			$charitable_url_query_arg_array = array( 'tab' => $charitable_section_key );
			?>
			<a href="<?php echo esc_url( add_query_arg( $charitable_url_query_arg_array, admin_url( 'admin.php?page=charitable-settings' ) ) ); ?>" class="nav-tab nav-tab-<?php echo esc_attr( $charitable_section_key ); ?> <?php echo ( esc_attr( $charitable_active_tab ) === esc_attr( $charitable_section_key ) ) ? ' nav-tab-active' : ''; ?>"><?php echo esc_html( $charitable_section_name ); ?></a>
		<?php endforeach ?>
	</h2>
	<?php if ( $charitable_show_return ) : ?>
		<?php /* translators: %s: active settings tab label */ ?>
		<p><a href="<?php echo esc_url( $charitable_return_tab_url ); ?>"><?php echo $charitable_return_tab_text; // phpcs:ignore ?></a></p>
	<?php endif ?>
	<?php
		/**
		 * Do or render something right before the settings form.
		 *
		 * @since 1.0.0
		 *
		 * @param string $group The settings group we are viewing.
		 */
		do_action( 'charitable_before_admin_settings', $charitable_group );

	?>

	<?php
	// Show CTA only for marketing and donors tabs.
	// Security tab will show actual settings (either from spam blocker or core).
	if ( 'marketing' === $charitable_active_tab || 'donors' === $charitable_active_tab ) :
		?>
		<?php do_action( 'charitable_pro_settings_cta', $charitable_active_tab ); ?>
	<?php else : ?>

		<?php if ( ! in_array( strtolower( $charitable_active_tab ), $charitable_tab_no_form_tag ) ) : // phpcs:ignore WordPress.PHP.StrictInArray.MissingTrueStrict ?>
		<form method="post" action="options.php">
		<?php endif; ?>
			<table class="form-table">
			<?php
			if ( ! in_array( strtolower( $charitable_active_tab ), $charitable_tab_no_form_tag ) ) : // phpcs:ignore WordPress.PHP.StrictInArray.MissingTrueStrict
				settings_fields( 'charitable_settings' );
				endif;

				charitable_do_settings_fields( 'charitable_settings_' . $charitable_group, 'charitable_settings_' . $charitable_group );
			?>
			</table>
			<?php if ( ! in_array( strtolower( $charitable_active_tab ), $charitable_tab_no_form_tag ) ) : // phpcs:ignore ?>
				<?php
					/**
					 * Filter the submit button at the bottom of the settings table.
					 *
					 * @since 1.6.0
					 *
					 * @param string $button The button output.
					 */
					echo apply_filters( 'charitable_settings_button_' . $charitable_group, get_submit_button( null, 'primary', 'submit', true, null ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				?>
			<?php endif; ?>
		<?php if ( ! in_array( strtolower( $charitable_active_tab ), $charitable_tab_no_form_tag ) ) : // phpcs:ignore ?>
		</form>
		<?php endif; ?>

	<?php endif; ?>

	<?php if ( 'security' === $charitable_active_tab) : ?>
		<?php do_action( 'charitable_pro_settings_cta', $charitable_active_tab ); ?>
	<?php endif; ?>

	<?php
		/**
		 * Do or render something right after the settings form.
		 *
		 * @since 1.0.0
		 *
		 * @param string $group The settings group we are viewing.
		 */
		do_action( 'charitable_after_admin_settings', $charitable_group );
	?>
</div>
<?php

echo ob_get_clean(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
