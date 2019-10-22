<?php
/**
 * Plugin Name: AMP Beta Tester
 * Description: Opt-in to receive non-stable release builds for the AMP plugin.
 * Plugin URI: https://amp-wp.org
 * Author: AMP Project Contributors
 * Author URI: https://github.com/ampproject/amp-wp/graphs/contributors
 * Version: 0.1
 * Text Domain: amp
 * Domain Path: /languages/
 * License: GPLv2 or later
 *
 * @package AMP Beta Tester
 */

namespace AMP_Beta_Tester;

define( 'AMP__BETA_TESTER__DIR__', dirname( __FILE__ ) );
define( 'AMP_PLUGIN_FILE', 'amp/amp.php' );

// DEV_CODE. This block of code is removed during the build process.
if ( file_exists( AMP__BETA_TESTER__DIR__ . '/amp.php' ) ) {
	add_filter(
		'site_transient_update_plugins',
		function ( $updates ) {
			if ( isset( $updates->response ) && is_array( $updates->response ) ) {
				if ( array_key_exists( 'amp/amp-beta-tester.php', $updates->response ) ) {
					unset( $updates->response['amp/amp-beta-tester.php'] );
				}

				if ( array_key_exists( 'amp/amp.php', $updates->response ) ) {
					unset( $updates->response['amp/amp.php'] );
				}
			}

			return $updates;
		}
	);
}

register_activation_hook( __FILE__, __NAMESPACE__ . '\force_plugin_update_check' );
add_action( 'plugins_loaded', __NAMESPACE__ . '\init' );

/**
 * Force a plugin update check. This will allows us to modify the plugin update cache so we
 * can set a custom update.
 */
function force_plugin_update_check() {
	if ( defined( 'DOING_CRON' ) ) {
		return;
	}
	delete_site_transient( 'update_plugins' );
}

/**
 * Hook into WP.
 *
 * @return void
 */
function init() {
	// Remind the user that an unstable AMP plugin is in use.
	if ( defined( 'AMP__FILE__' ) ) {
		add_action( 'admin_bar_menu', __NAMESPACE__ . '\show_unstable_reminder' );
	}

	add_filter( 'pre_set_site_transient_update_plugins', __NAMESPACE__ . '\update_amp_manifest' );
	add_action( 'after_plugin_row_' . AMP_PLUGIN_FILE, __NAMESPACE__ . '\replace_view_version_details_link', 10, 2 );
}

/**
 * Modifies the AMP plugin manifest to point to a new beta update if one exists.
 *
 * @param \stdClass $updates Object containing information on plugin updates.
 * @return \stdClass
 */
function update_amp_manifest( $updates ) {
	if ( ! isset( $updates->no_update ) ) {
		return $updates;
	}

	$amp_version = get_plugin_data( WP_PLUGIN_DIR . '/' . AMP_PLUGIN_FILE )['Version'];

	if ( ! is_pre_release( $amp_version ) ) {
		return $updates;
	}

	$manifest_type = isset( $updates->response[ AMP_PLUGIN_FILE ] ) ? 'response' : 'no_update';
	$amp_manifest  = $updates->{$manifest_type}[ AMP_PLUGIN_FILE ];

	$github_releases = get_amp_github_releases();

	if ( is_array( $github_releases ) ) {
		$amp_to_be_updated = false;

		foreach ( $github_releases as $release ) {
			if ( $release->prerelease ) {
				$release_version = $release->tag_name;

				// If there is a new release, let's see if there is a zip available for download.
				if ( version_compare( $release_version, $amp_version, '>=' ) ) {
					foreach ( $release->assets as $asset ) {
						if ( 'amp.zip' === $asset->name ) {
							$amp_manifest->new_version = $release_version;
							$amp_manifest->package     = $asset->browser_download_url;
							$amp_manifest->url         = $release->html_url;

							// Set the AMP plugin to be updated.
							$updates->{$manifest_type}[ AMP_PLUGIN_FILE ] = $amp_manifest;

							if ( 'response' === $manifest_type ) {
								unset( $updates->no_update[ AMP_PLUGIN_FILE ] );
							}

							$amp_to_be_updated = true;
							break;
						}
					}

					if ( $amp_to_be_updated ) {
						break;
					}
				}
			}
		}
	}

	return $updates;
}

/**
 * Fetch AMP releases from GitHub.
 *
 * @return array|null
 */
function get_amp_github_releases() {
	$raw_response = wp_remote_get( 'https://api.github.com/repos/ampproject/amp-wp/releases' );
	if ( is_wp_error( $raw_response ) ) {
		return null;
	}
	return json_decode( $raw_response['body'] );
}

/**
 * Replace the 'View version details' link with the link to the release on GitHub.
 *
 * @param string $file Plugin file.
 * @param array  $plugin_data Plugin data.
 */
function replace_view_version_details_link( $file, $plugin_data ) {
	$plugin_version = $plugin_data['Version'];

	if ( is_pre_release( $plugin_version ) ) {
		?>
		<script>
			document.addEventListener('DOMContentLoaded', function() {
				const links = document.querySelectorAll("[data-slug='amp'] a.thickbox.open-plugin-details-modal");

				links.forEach( (link) => {
					link.className = 'overridden'; // Override class so that onclick listeners are disabled.
					link.target = '_blank';
					<?php
					if ( isset( $plugin_data['url'] ) ) {
						echo "link.href = '" . esc_js( $plugin_data['url'] ) . "';";
					}
					?>
				} );
			}, false);
		</script>
		<?php
	}
}

/**
 * Determine if the supplied version code is a prerelease.
 *
 * @param string $plugin_version Plugin version code.
 * @return bool
 */
function is_pre_release( $plugin_version ) {
	return (bool) preg_match( '/^\d+\.\d+\.\d+-(beta|alpha|RC)\d?$/', $plugin_version );
}

/**
 * Displays the version code in the admin bar to act as a reminder that an unstable version
 * of AMP is being used.
 */
function show_unstable_reminder() {
	global $wp_admin_bar;

	if ( is_pre_release( AMP__VERSION ) ) {
		$args = [
			'id'     => 'amp-beta-tester-admin-bar',
			'title'  => sprintf( 'AMP v%s', AMP__VERSION ),
			'parent' => 'top-secondary',
			'href'   => admin_url( 'admin.php?page=amp-options' ),
		];
		$wp_admin_bar->add_node( $args );

		// Highlight the menu.
		echo '<style>#wpadminbar #wp-admin-bar-amp-beta-tester-admin-bar { background: #0075C2; }</style>';
	}
}
