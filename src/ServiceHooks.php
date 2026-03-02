<?php

namespace MediaWiki\Extension\MixedVisibilityFiles;

use MediaWiki\Config\ServiceOptions;
use MediaWiki\Hook\MediaWikiServicesHook;
use MediaWiki\MediaWikiServices;
use MediaWiki\Permissions\GroupPermissionsLookup;

class ServiceHooks implements MediaWikiServicesHook {

	/** @inheritDoc */
	public function onMediaWikiServices( $container ) {
		// Not always needed
		if ( MW_ENTRY_POINT !== 'img_auth' ) {
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
