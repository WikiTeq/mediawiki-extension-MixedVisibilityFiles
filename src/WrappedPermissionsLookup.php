<?php

// We run Phan with both MW 1.39 and MW 1.43, the suppression from 1.39 isn't
// needed on 1.43 and phan would complain about that
// @phan-file-suppress UnusedPluginSuppression,UnusedPluginFileSuppression

namespace MediaWiki\Extension\MixedVisibilityFiles;

use MediaWiki\FileRepo\AuthenticatedFileEntryPoint;
use MediaWiki\Permissions\GroupPermissionsLookup;
use MediaWiki\Permissions\PermissionManager;

class WrappedPermissionsLookup extends GroupPermissionsLookup {

	private GroupPermissionsLookup $original;

	public function __construct( GroupPermissionsLookup $original ) {
		$this->original = $original;
	}

	public function groupHasPermission( string $group, string $permission ): bool {
		// 1.43.5:
		// Intercept JUST the call in AuthenticatedFileEntryPoint::execute()
		// to `GroupPermissionsLookup::groupHasPermission( '*', 'read' )`
		// and return false
		if ( $group === '*'
			&& $permission === 'read'
			// Exists for 1.43.5; class does not need to exist for `::class`
			// to work
			// @phan-suppress-next-line PhanUndeclaredClassReference
			&& wfGetCaller() === AuthenticatedFileEntryPoint::class . '->execute'
		) {
			return false;
		}
		return $this->original->groupHasPermission( $group, $permission );
	}

	public function getGrantedPermissions( string $group ): array {
		return $this->original->getGrantedPermissions( $group );
	}

	public function getRevokedPermissions( string $group ): array {
		return $this->original->getRevokedPermissions( $group );
	}

	public function getGroupPermissions( array $groups ): array {
		// 1.39.15:
		// Intercept JUST the call in img_auth.php to
		// `$permissionManager->getGroupPermissions( [ '*' ] )` which
		// the permission manager delegates to the group permissions lookup,
		// and remove the `read` right
		if ( $groups === [ '*' ]
			&& wfGetCaller() === PermissionManager::class . '->getGroupPermissions'
			&& wfGetCaller( 3 ) === 'wfImageAuthMain'
		) {
			return array_diff(
				$this->original->getGroupPermissions( $groups ),
				[ 'read' ]
			);
		}
		return $this->original->getGroupPermissions( $groups );
	}

	public function getGroupsWithPermission( string $permission ): array {
		return $this->original->getGroupsWithPermission( $permission );
	}

}
