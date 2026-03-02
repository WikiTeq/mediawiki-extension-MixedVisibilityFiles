<?php

namespace MediaWiki\Extension\MixedVisibilityFiles;

use MediaWiki\Hook\GetDoubleUnderscoreIDsHook;
use MediaWiki\Permissions\Hook\GetUserPermissionsErrorsExpensiveHook;
use PageProps;

class VisibilityHooks implements
	GetDoubleUnderscoreIDsHook,
	GetUserPermissionsErrorsExpensiveHook
{

	private PageProps $pageProps;

	public function __construct( PageProps $pageProps ) {
		$this->pageProps = $pageProps;
	}

	/**
	 * @param array &$doubleUnderscoreIDs
	 */
	public function onGetDoubleUnderscoreIDs( &$doubleUnderscoreIDs ) {
		$doubleUnderscoreIDs[] = 'makeFilePublic';
	}

	/** @inheritDoc */
	public function onGetUserPermissionsErrorsExpensive( $title, $user, $action,
		&$result
	) {
		// Most of the time we aren't interested in doing anything, simplest
		// checks first
		if ( MW_ENTRY_POINT !== 'img_auth' ) {
			return;
		}
		// Only trying to affect file reads by anonymous users
		if ( $action !== 'read' ) {
			return;
		}
		if ( $user->isRegistered() ) {
			return;
		}
		if ( $title->getNamespace() !== NS_FILE ) {
			return;
		}

		$allProps = $this->pageProps->getAllProperties( $title );
		if ( $allProps && $allProps[$title->getId()] ) {
			$props = $allProps[$title->getId()];
			if ( array_key_exists( 'makeFilePublic', $props ) ) {
				// It is public, nothing to do
				return;
			}
		}

		// Anonymous user is trying to read a file not marked as public
		// Even if we tried returning a nice error in $result it would be
		// ignored by img_auth.php, but we need to set something or otherwise
		// the hook is considered a success even if we return false
		$result = false;
		return false;
	}

}
