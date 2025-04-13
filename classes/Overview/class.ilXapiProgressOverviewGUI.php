<?php
require_once __DIR__ . "/../../vendor/autoload.php";

/**
 * GUI-Class ilXapiProgressOverviewGUI
 *
 * @ilCtrl_IsCalledBy ilXapiProgressOverviewGUI: ilRouterGUI, ilUIPluginRouterGUI
 */
class ilXapiProgressOverviewGUI extends ilXapiProgressGUI {

	const CMD_SHOW_OBJECTS_IN_COURSE = 'showObjectsInCourse';

	protected ilLogger $log;

	//protected ilObjCourseGUI $crsGUI;

	function __construct() {
		global $ilTabs;
        parent::__construct();
		$this->log = ilLoggerFactory::getRootLogger();
		$this->model = new ilXapiProgressOverviewModel();
	}


	public function executeCommand(): void {
		parent::executeCommand();
	}


//	/**
//	 * Redirect to OverviewLP report which shows objects in courses which are relevant for LP
//	 */
//	public function showObjectsInCourse() {
//		$this->ctrl->setParameterByClass(ilXapiProgressOverviewLPGUI::class, "from", self::class);
//		$this->ctrl->redirectByClass(ilXapiProgressOverviewLPGUI::class, ilXapiProgressOverviewLPGUI::CMD_REPORT);
//	}


	/**
	 * Display table for searching the courses
	 */
	public function search(): void
    {
		$this->tpl->setTitle($this->pl->txt('report_users_per_course'));
		$this->table = new ilXapiProgressOverviewSearchTableGUI($this, ilXapiProgressGUI::CMD_SEARCH);
		$this->table->setTitle($this->pl->txt('search_courses'));
		parent::search();
	}


	/**
	 * Display report table
	 */
	public function report(): void {

        $lrs_data = '';
//		parent::report(); //Hack
		if (isset($_GET['ref_id'])) {
			$this->ctrl->saveParameter($this, 'ref_id');
		}
		//$this->tpl->setTitle($this->pl->txt('report_overview'));
		if ($this->table === NULL) {
			$this->table = new ilXapiProgressOverviewReportTableGUI($this, ilXapiProgressGUI::CMD_REPORT);
		}
		$data = array("id" => "dummy");
		//?
		//$data = $this->model->getReportData($_SESSION[self::SESSION_KEY_IDS], $this->table->getFilterNames());
		$this->table->setData($data);
		
		$crsId = (int) $_GET['ref_id'];
        $crsId = $this->dic->http()->wrapper()->query()->retrieve('ref_id',$this->dic->refinery()->kindlyTo()->int());
        $report = '';
        if ($this->dic->http()->wrapper()->post()->has("lpd_report")) {
            $report = $this->dic->http()->wrapper()->post()->retrieve('lpd_report', $this->dic->refinery()->kindlyTo()->string());
        }
//        die(".".$report);
//		$report = (isset($_POST["lpd_report"])) ? $_POST["lpd_report"] : '';
        $statusChangedFrom = '';
        if ($this->dic->http()->wrapper()->post()->has("status_changed_from")) {
            $statusChangedFrom = $this->dic->http()->wrapper()->post()->retrieve('status_changed_from', $this->dic->refinery()->kindlyTo()->string());
        }
        $statusChangedTo = '';
        if ($this->dic->http()->wrapper()->post()->has("status_changed_to")) {
            $statusChangedTo = $this->dic->http()->wrapper()->post()->retrieve('status_changed_to', $this->dic->refinery()->kindlyTo()->string());
        }

//        $statusChangedFrom = (isset($_POST["status_changed_from"])) ? $_POST["status_changed_from"] : '';
//		$statusChangedTo = (isset($_POST["status_changed_to"])) ? $_POST["status_changed_to"] : '';
		
		if ($this->ctrl->getCmd() != self::CMD_APPLY_FILTER_REPORT
			&& $this->ctrl->getCmd() != self::CMD_RESET_FILTER_REPORT) {
//			$onlyUnique = isset($_GET['pre_xpt']);
//			$this->storeIdsInSession($data, $onlyUnique); //Todo
            $this->storeIdsInSession($data);
		}
		if ($this->ctrl->getCmd() == self::CMD_APPLY_FILTER_REPORT) {
			$reportFolder = $this->pl->getDirectory() . "/templates/reports/". $report;
			$echart = '<script src="./Customizing/global/plugins/Services/UIComponent/UserInterfaceHook/XapiProgress/src/echarts.min.js"></script>';
        	$lrs_collection = '<script src="./Customizing/global/plugins/Services/UIComponent/UserInterfaceHook/XapiProgress/src/xapicollection.js"></script>';
			if (file_exists($reportFolder)) {
//                die($reportFolder.'/tpl.template_row.html');
				$this->table->setRowTemplate('./'.$reportFolder.'/tpl.template_row.html');
        		$lrs_data = '<script>const statusChangedFrom="' . $statusChangedFrom .'";const statusChangedTo="' . $statusChangedTo .'";const statements=' . ilXapiProgressModel::getData($crsId) . ';</script>';
			} else {
				$this->table->setRowTemplate('tpl.template_row_overview.html', $this->pl->getDirectory());
				$this->tpl->setVariable("REPORT_TITLE","no report folder");
				$this->tpl->setVariable("VALUE","no data");
			}
			$this->tpl->setContent($echart . $lrs_collection . $lrs_data . $this->table->getHTML());
		} else {
			//?
			$this->tpl->setContent($this->table->getHTML());
		}
		
	}


	public function applyFilterSearch(): void {
		$this->table = new ilXapiProgressOverviewSearchTableGUI($this, $this->getStandardCmd());
		parent::applyFilterSearch();
	}


	public function resetFilterSearch(): void {
		$this->table = new ilXapiProgressOverviewSearchTableGUI($this, $this->getStandardCmd());
		parent::resetFilterSearch();
	}


	public function applyFilterReport(): void {
		if (isset($_GET['ref_id'])) {
			$this->ctrl->saveParameter($this, 'ref_id');
		}
		$this->table = new ilXapiProgressOverviewReportTableGUI($this, ilXapiProgressGUI::CMD_REPORT);
		parent::applyFilterReport();
	}


	public function resetFilterReport(): void {
		if (isset($_GET['ref_id'])) {
			$this->ctrl->saveParameter($this, 'ref_id');
		}
		$this->table = new ilXapiProgressOverviewReportTableGUI($this, ilXapiProgressGUI::CMD_REPORT);
		parent::resetFilterReport();
	}


	public function getAvailableExports():array
    {
        $exports = [];
		if ($this->isActiveJasperReports()) {
			$exports[self::EXPORT_PDF] = 'export_pdf';
		}

		return $exports;
	}
}
