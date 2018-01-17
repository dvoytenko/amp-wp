<?php
/**
 * Register widgets, and add actions.
 *
 * @package AMP
 */

/**
 * Register the widgets.
 */
class AMP_Widgets {

	/**
	 * Add the actions.
	 *
	 * @return void.
	 */
	public function init() {
		add_action( 'widgets_init', array( $this, 'register_widgets' ) );
	}

	/**
	 * Add the filters.
	 *
	 * @return void.
	 */
	public function register_widgets() {
		$widgets = self::get_widgets();
		foreach ( $widgets as $native_wp_widget => $amp_widget ) {
			unregister_widget( $native_wp_widget );
			register_widget( $amp_widget );
		}
	}

	/**
	 * Get the widgets to unregister and register.
	 *
	 * @return array $widgets An associative array, with the previous WP widget mapped to the new AMP widget.
	 */
	public function get_widgets() {
		return array(
			'WP_Widget_Archives'        => 'AMP_Widget_Archives',
			'WP_Widget_Categories'      => 'AMP_Widget_Categories',
			'WP_Widget_Recent_Comments' => 'AMP_Widget_Recent_Comments',
		);
	}

}
