<?php

require_once __DIR__ . "/../vendor/autoload.php";

class ilXapiProgressAccess {

	protected ilXapiProgressPlugin $pl;
	protected ilObjUser $usr;
	protected ilRbacReview $rbacreview;


	public function __construct() {
		global $DIC;
		$this->usr = $DIC->user();
		$this->rbacreview = $DIC->rbac()->review();
		$this->pl = ilXapiProgressPlugin::getInstance();
	}


	public function hasCurrentUserReportsPermission() : bool
    {
		//XapiProgress Access to Employees?
		$arr_orgus_perm_empl = ilObjOrgUnitTree::_getInstance()->getOrgusWhereUserHasPermissionForOperation('view_learning_progress');
		if (count($arr_orgus_perm_empl) > 0) {
			return true;
		}

		//XapiProgress Access Rec?
		$arr_orgus_perm_sup = ilObjOrgUnitTree::_getInstance()->getOrgusWhereUserHasPermissionForOperation('view_learning_progress_rec');
		if (count($arr_orgus_perm_sup) > 0) {
			return true;
		}

		$global_roles = $this->rbacreview->assignedGlobalRoles($this->usr->getId());
		//Administrator
		if (in_array(2, $global_roles)) {
			return true;
		}

		return false;
	}
}
