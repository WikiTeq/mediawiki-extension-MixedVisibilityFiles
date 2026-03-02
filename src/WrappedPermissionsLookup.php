<?php

namespace MediaWiki\Extension\MixedVisibilityFiles;

use MediaWiki\Permissions\GroupPermissionsLookup;
use MediaWiki\Permissions\PermissionManager;

class WrappedPermissionsLookup extends GroupPermissionsLookup {

	private GroupPermissionsLookup $original;

	public function __construct( GroupPermissionsLookup $original ) {
		$this->original = $original;
	}

	public function groupHasPermission( string $group, string $permission ): bool {
		return $this->original->groupHasPermission( $group, $permission );
	}

	public function getGrantedPermissions( string $group ): array {
		return $this->original->getGrantedPermissions( $group );
	}

	public function getRevokedPermissions( string $group ): array {
		return $this->original->getRevokedPermissions( $group );
	}

	public function getGroupPermissions( array $groups ): array {
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
