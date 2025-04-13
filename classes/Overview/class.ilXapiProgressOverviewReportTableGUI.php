<?php

/**
 * Report table for the report Overview
 *
 */
class ilXapiProgressOverviewReportTableGUI extends ilXapiProgressReportTableGUI {

	public function __construct($a_parent_obj, $a_parent_cmd) {
		parent::__construct($a_parent_obj, $a_parent_cmd);
		//$this->addCommandButton(ilXapiProgressOverviewGUI::CMD_SHOW_OBJECTS_IN_COURSE, $this->pl->txt("show_objects_in_course"));
	}


	public function exportDataCustom(int $format, bool $send = false) {
		// switch ($format) {
			// case ilXapiProgressGUI::EXPORT_PDF:
				// $export = new ilXapiProgressOverviewPdfExport();
				// $export->setCsvColumns($this->getColumns());
				// $export->setReportGroups(array( 'id' => 'group_course' ));
				// $export->setCsvData($this->getData());
				// $export->execute();
				// break;
		// }
	}
}
