<?php
/**
 * AMP Paired Browsing experience template.
 *
 * 🚫🚫🚫
 * DO NOT EDIT THIS FILE WHILE INSIDE THE PLUGIN! Changes You make will be lost when a new version
 * of the AMP plugin is released.
 * 🚫🚫🚫
 *
 * @package AMP
 */

$url     = remove_query_arg( AMP_Theme_Support::PAIRED_BROWSING_QUERY_VAR );
$amp_url = add_query_arg( amp_get_slug(), '1', $url );
?>

<!DOCTYPE html>
<html <?php language_attributes(); // // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
	<head>
		<meta charset="<?php bloginfo( 'charset' ); ?>">
		<title><?php esc_html_e( 'Loading...', 'amp' ); ?></title>
		<?php print_admin_styles(); ?>
	</head>
	<body>
		<section>
			<nav id="header">
				<ul>
					<li>
						<img src="<?php echo esc_url( amp_get_asset_url( 'images/amp-white-icon.svg' ) ); ?>" alt="">
					</li>
					<li>
						<span>Paired Browsing</span>
					</li>
				</ul>
			</nav>
		</section>

		<div class="container">
			<div id="non-amp">
				<iframe src="<?php echo esc_url( $url ); ?>" sandbox="allow-forms allow-scripts allow-same-origin allow-popups"></iframe>
			</div>

			<div id="amp">
				<iframe src="<?php echo esc_url( $amp_url ); ?>" sandbox="allow-forms allow-scripts allow-same-origin allow-popups"></iframe>
			</div>
		</div>

		<script>
			window.ampSlug = <?php echo wp_json_encode( amp_get_slug() ); ?>;
			window.ampPairedBrowsingQueryVar = <?php echo wp_json_encode( AMP_Theme_Support::PAIRED_BROWSING_QUERY_VAR ); ?>;
		</script>
		<?php print_footer_scripts(); ?>
	</body>
</html>
