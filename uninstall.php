<?php
/**
 * Removes all plugin data when the plugin is deleted.
 */

defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

delete_post_meta_by_key( '_plp_enabled' );
delete_post_meta_by_key( '_plp_token' );
