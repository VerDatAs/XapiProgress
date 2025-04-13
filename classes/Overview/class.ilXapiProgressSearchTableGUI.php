<?php

/**
 * TableGUI ilXapiProgressOverviewTableGUI
 *
 */
class ilXapiProgressOverviewSearchTableGUI extends ilXapiProgressSearchTableGUI {

	/**
	 * @param ilXapiProgressGUI $a_parent_obj
	 * @param string         $a_parent_cmd
	 */
	function __construct(ilXapiProgressGUI $a_parent_obj, $a_parent_cmd) {
		parent::__construct($a_parent_obj, $a_parent_cmd);
		$this->addCommandButton(ilXapiProgressGUI::CMD_REPORT, $this->pl->txt('report_all_users_per_course'));
		$this->addMultiCommand(ilXapiProgressGUI::CMD_REPORT, $this->pl->txt('report_selected_users_per_course'));
	}

public function getSelectableColumns(): array
    {
		$cols['title'] = array( 'txt' => $this->pl->txt('title'), 'default' => true );
		$cols['path'] = array( 'txt' => $this->pl->txt('path'), 'default' => true );

		return $cols;
	}


	public function initFilter(): void
    {
		$te = new ilTextInputGUI('Title', 'title');
		$this->addFilterItemWithValue($te);
		parent::initFilter();
	}
}
