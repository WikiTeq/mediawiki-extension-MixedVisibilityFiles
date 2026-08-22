<?php

namespace MediaWiki\Extension\MixedVisibilityFiles;

use MediaWiki\FileRepo\AuthenticatedFileEntryPoint;
use MediaWiki\FileRepo\ThumbnailEntryPoint;
use MediaWiki\Permissions\GroupPermissionsLookup;

class WrappedPermissionsLookup extends GroupPermissionsLookup {

	private GroupPermissionsLookup $original;

	public function __construct( GroupPermissionsLookup $original ) {
		$this->original = $original;
	}

	public function groupHasPermission( string $group, string $permission ): bool {
		// Intercept JUST the call in AuthenticatedFileEntryPoint::execute()
		// to `GroupPermissionsLookup::groupHasPermission( '*', 'read' )`
		// and return false
		if ( $group === '*'
			&& $permission === 'read'
			&& wfGetCaller() === AuthenticatedFileEntryPoint::class . '->execute'
		) {
			return false;
		}
		// Same for the per-file gate in ThumbnailEntryPoint::maybeDenyAccess(),
		// which otherwise skips all thumbnail permission checks on public wikis
		if ( $group === '*'
			&& $permission === 'read'
			&& wfGetCaller() === ThumbnailEntryPoint::class . '->maybeDenyAccess'
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
		return $this->original->getGroupPermissions( $groups );
	}

	public function getGroupsWithPermission( string $permission ): array {
		return $this->original->getGroupsWithPermission( $permission );
	}

}
