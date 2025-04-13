<?php

use ILIAS\DI\Container;

require_once __DIR__ . "/../vendor/autoload.php";


/**
 * Abstract GUI-Class ilXapiProgressGUI
 * Each report must have a separate GUI class which extends this class and implement the abstract
 * methods
 *
 * @author            Uwe Kohnle <kohnle@internetlehrer-gmbh.de>
 * @version           $Id:
 *
 * @ilCtrl_IsCalledBy ilXapiProgressGUI: ilRouterGUI, ilUIPluginRouterGUI
 */
abstract class ilXapiProgressGUI {
    // use DICTrait;
	/** Addition exports available */
	const EXPORT_PDF = 19;
	const CMD_REPORT = 'report';
	const CMD_SEARCH = 'search';
	const CMD_APPLY_FILTER_SEARCH = 'applyFilterSearch';
	const CMD_RESET_FILTER_SEARCH = 'resetFilterSearch';
	const CMD_APPLY_FILTER_REPORT = 'applyFilterReport';
	const CMD_RESET_FILTER_REPORT = 'resetFilterReport';
	/** Session keys used to store the ids of courses/tests/users and filters status, last status changed */
	const SESSION_KEY_IDS = 'XapiProgress_ids';
	protected ilGlobalTemplateInterface $tpl;
	protected ilXapiProgressPlugin $pl;
	protected ilCtrl $ctrl;
	protected ilTabsGUI $tabs;
	protected ilPluginAdmin $ilPluginAdmin;
	protected ilXapiProgressModel $model;
	protected ilAccessHandler $access;
	protected ?ilTable2GUI $table = NULL;
	
	protected Container $dic;

	protected ilObjCourse $crsObj;

    protected ilLocatorGUI $locator;

	public function __construct() {
		global $DIC;
		$this->dic = $DIC;
		$this->tpl = $DIC->ui()->mainTemplate();
		$this->pl = ilXapiProgressPlugin::getInstance();
        $this->locator = $DIC['ilLocator'];
		//$this->pl->updateLanguages();
		$this->ctrl = $DIC->ctrl();
		$this->tabs = $DIC->tabs();

        $this->ilPluginAdmin = $DIC["ilPluginAdmin"];
		$this->access = $DIC->access();
		$this->crsObj = new ilObjCourse($_GET['ref_id']);

		if (!$this->pl->isActive()) {
			$this->tpl->setOnScreenMessage('failure',$this->pl->txt("plugin_not_activated"), true);
            $this->ctrl->redirectByClass(ilDashboardGUI::class, 'jumpToSelectedItems');
		}
		$this->checkAccess();
	}


	public function getStandardCmd() : string
    {
		return self::CMD_SEARCH;
	}


	public function executeCommand() : void
    {
        //$this->tpl->loadStandardTemplate();

		$next_class = $this->ctrl->getNextClass($this);
		switch ($next_class) {
			case '':
				$cmd = $this->ctrl->getCmd($this->getStandardCmd());
				if (!in_array($cmd, get_class_methods($this))) {
					$this->{$this->getStandardCmd()}();
					if (DEBUG) {
                        $this->tpl->setOnScreenMessage('info',"COMMAND NOT FOUND! Redirecting to standard class in ilXapiProgressGUI executeCommand()");
					}
					return;
				}
				switch ($cmd) {
					default:
						$this->$cmd();
						break;
				}
				break;
			default:
				require_once($this->ctrl->lookupClassPath($next_class));
				$gui = new $next_class();
				$this->ctrl->forwardCommand($gui);
				break;
		}
		$this->prepareOutput();
        $this->tpl->printToStdout();
    }

	protected function prepareOutput(): void
	{
		$this->locator->addRepositoryItems($this->crsObj->getRefId());
		$this->tpl->loadStandardTemplate();
		$this->tpl->setLocator();
		$this->tpl->setTitle($this->crsObj->getPresentationTitle());
		$this->tpl->setDescription($this->crsObj->getLongDescription());
//		$this->tpl->setTitleIcon(ilObject::_getIcon('', 'big', 'crs'), $this->dic->language()->txt('obj_crs'));
		// reuse the tabs that were saved
		if (isset($_SESSION[ilXapiProgressOverviewGUI::class]['TabTarget']))
		{
			$this->dic->tabs()->target = $_SESSION[ilXapiProgressOverviewGUI::class]['TabTarget'];
			$this->dic->tabs()->activateTab(ilXapiProgressUIHookGUI::TAB_REPORTS);

        }
	}
    public function initSubtabs(): void
    {
//        $this->dic->tabs()->addSubTab('consent',$this->pl->txt('consent'),$this->dic->ctrl()->getLinkTarget($this, 'consent'));
//
//        $validator = new ilCertificateActiveValidator();
//
//        if ($validator->validate()) {
//            $this->dic->tabs()->addSubTab(
//                self::SUBTAB_ID_CERTIFICATE,
//                $this->language->txt(self::SUBTAB_ID_CERTIFICATE),
//                $this->dic->ctrl()->getLinkTargetByClass(ilCertificateGUI::class, 'certificateEditor')
//            );
//        }
    }

	/**
	 * Display the search table
	 */
	public function search(): void {
		$data = $this->model->getSearchData($this->table->getFilterNames());
		$this->table->setData($data);
		// Store the user/object ids in the session so the report() can reuse them
		$this->storeIdsInSession($data);
		$this->tpl->setContent($this->table->getHTML());
	}


	/**
	 * Return the available exports (additional to standard CSV and Excel)
	 * Format: key=ID, value=Text
	 */
	abstract public function getAvailableExports() : array;


	/**
	 * Display report table
	 */
	public function report() : void
    {
		// If the user has reduced data with checkboxes, store those new check ids in the session
		if (isset($_POST['id']) && count($_POST['id'])) {
			$_SESSION[self::SESSION_KEY_IDS] = $_POST['id'];
		}
	}


	/**
	 * Apply a filter to search table
	 */
	public function applyFilterSearch() : void
    {
		$this->table->writeFilterToSession();
		$this->table->resetOffset();
		$this->search();
	}


	/**
	 * Reset filter from search table
	 */
	public function resetFilterSearch() : void
    {
		$this->table->resetOffset();
		$this->table->resetFilter();
		$this->ctrl->redirect($this, self::CMD_SEARCH);
	}


	/**
	 * Apply a filter to report table
	 */
	public function applyFilterReport() : void
    {
		$this->table->writeFilterToSession();
		$this->table->resetOffset();
		$this->report();
	}


	/**
	 * Reset filter from report table
	 */
	public function resetFilterReport() : void
    {
		$this->table->resetOffset();
		$this->table->resetFilter();
		$this->report();
	}


	public function __toString() {
		return strtolower(get_class($this));
	}


    /**
     * @return bool
     */
	public static function hasAccess() : bool {
		global $DIC;
        $hasAccess = false;

        // Coming from a course?
//        if (isset($_GET['rep_crs_ref_id'])) {
//            $hasAccess = $DIC->access()->checkAccess("edit_learning_progress", "", $_GET['rep_crs_ref_id'], '', $_SESSION[self::SESSION_KEY_IDS][0]);
//        }

        // admin
        if (!$hasAccess) {
            $hasAccess = $DIC->rbac()->review()->isAssigned($DIC->user()->getId(), SYSTEM_ROLE_ID);
        }

        //visible and read access users in administration; 'USER_FOLDER_ID' = 7;
        if (!$hasAccess) {
            $hasAccess = $DIC->rbac()->system()->checkAccess('visible,read', 7);
        }

//        if (ilXapiProgressConfig::getValue('restricted_user_access') == ilXapiProgressConfig::RESTRICTED_BY_LOCAL_READABILITY) {
//            $refIds = getRefIdsWhereUserCanAdministrateUsers();
//        }


        return $hasAccess;
    }


	/**
	 * Check read permission for the report of current request defined in MainMenu plugin.
	 * If the user is coming from a course, he can also view the reports if he/she has permission
	 * 'edit_learning_progress' Redirect if user has no access
	 *
	 */
	protected function checkAccess() : void
    {
		$hasAccess = static::hasAccess();

		if (!$hasAccess) {
			$this->tpl->setOnScreenMessage('failure', $this->pl->txt("permission_denied"), true);
            $this->ctrl->redirectByClass(ilDashboardGUI::class, 'jumpToSelectedItems');
		}
	}


	/**
	 * Set if the JasperReport library is available
	 *
	 * @return bool
	 */
	protected function isActiveJasperReports() : bool
    {
		return false;
	}


	/**
	 * Store IDs from data array in session
	 */
	protected function storeIdsInSession(array $data) : void
    {
		if (!count($data)) {
			return;
		}
		$ids = array();
		foreach ($data as $v) {
			if (isset($v['id'])) {
				$ids[] = (int)$v['id'];
			}
		}
//		$_SESSION[self::SESSION_KEY_IDS] = array_unique($ids); //HACK
	}

    public function refreshdata() : void
    {
        $this->model->refreshDataFromLRS();
		$this->ctrl->saveParameter($this, 'ref_id');
		$uri = $this->ctrl->getLinkTargetByClass(array(
			ilUIPluginRouterGUI::class,
			ilXapiProgressOverviewGUI::class,
		), ilXapiProgressGUI::CMD_REPORT);
        $this->tpl->setOnScreenMessage("success",$this->pl->txt("successfully_refreshed"),true);
		$this->ctrl->redirectToUrl($uri);
    }
}
