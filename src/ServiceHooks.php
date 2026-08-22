<?php

namespace MediaWiki\Extension\MixedVisibilityFiles;

use MediaWiki\Config\ServiceOptions;
use MediaWiki\Hook\MediaWikiServicesHook;
use MediaWiki\MediaWikiServices;
use MediaWiki\Permissions\GroupPermissionsLookup;

class ServiceHooks implements MediaWikiServicesHook {

	/**
	 * Entry points that stream file content gated on whether `*` has the
	 * `read` right. Core's own per-file permission checks are skipped on all
	 * of them while that right is present, so each one needs the wrapped
	 * GroupPermissionsLookup to report a non-public wiki.
	 */
	private const WRAPPED_ENTRY_POINTS = [ 'img_auth', 'thumb', 'thumb_handler' ];

	/** @inheritDoc */
	public function onMediaWikiServices( $container ) {
		// Not always needed
		if ( !in_array( MW_ENTRY_POINT, self::WRAPPED_ENTRY_POINTS, true ) ) {
			return;
		}
		$container->redefineService(
			'GroupPermissionsLookup',
			static function ( MediaWikiServices $services ): GroupPermissionsLookup {
				$original = new GroupPermissionsLookup(
					new ServiceOptions(
						GroupPermissionsLookup::CONSTRUCTOR_OPTIONS,
						$services->getMainConfig()
					)
				);
				return new WrappedPermissionsLookup( $original );
			}
		);
	}
}
