<?php

/**
 * TableGUI ilXapiProgressSearchTableGUI
 *
 * @author  uwe Kohnle <kohnle@internetlehrer-gmbh.de>
 * @version $Id:
 *
 */
abstract class ilXapiProgressSearchTableGUI extends ilTable2GUI {

	protected array $filter_names = array();

	protected ilXapiProgressPlugin $pl;

	protected ilCtrl $ctrl;

	protected ilToolbarGUI $toolbar;

	protected string $filter_cmd = ilXapiProgressGUI::CMD_APPLY_FILTER_SEARCH;

	protected string $reset_cmd = ilXapiProgressGUI::CMD_RESET_FILTER_SEARCH;

	protected ilXapiProgressFormatter $formatter;


	function __construct(ilXapiProgressGUI $a_parent_obj, string $a_parent_cmd) {
		global $DIC;
		$this->pl = ilXapiProgressPlugin::getInstance();
		$this->ctrl = $DIC->ctrl();
		$this->toolbar = $DIC->toolbar();
		$this->setId($this->ctrl->getCmdClass());
		$this->setPrefix('pre');
		$this->formatter = ilXapiProgressFormatter::getInstance();
		parent::__construct($a_parent_obj, $a_parent_cmd);
		$this->setRowTemplate('tpl.template_row.checkboxes.html', $this->pl->getDirectory());
		$this->setEnableHeader(true);
		$this->setEnableTitle(true);
		$this->setTopCommands(true);
		$this->setShowRowsSelector(true);
		$this->setFormAction($this->ctrl->getFormAction($a_parent_obj));
		$this->initFilter();
		$this->setDisableFilterHiding(true);
		// Setup columns
		$this->addColumn("", "", "1", true);
		$this->setSelectAllCheckbox("id[]");
		foreach ($this->getSelectableColumns() as $k => $v) {
			if ($this->isColumnSelected($k)) {
				$this->addColumn($v['txt'], $k, 'auto');
			}
		}
	}


	protected function addFilterItemWithValue(ilFormPropertyGUI $item, bool $optional = false): void
    {
//		/**
//		 * @var ilFormPropertyGUI $item
//		 */
		$this->addFilterItem($item, $optional);
		$item->readFromSession();
		switch (get_class($item)) {
			case ilSelectInputGUI::class:
				/** @var ilSelectInputGUI $item */
				$value = $item->getValue();
				break;
			case ilCheckboxInputGUI::class:
				/** @var ilCheckboxInputGUI $item */
				$value = $item->getChecked();
				break;
			case ilDateTimeInputGUI::class:
				/** @var ilDateTimeInputGUI $item */
				$value = $item->getDate();
				break;
			default:
				$value = $item->getValue();
				break;
		}
		$this->filter_names[$item->getPostVar()] = $value;
	}


	public function getFilterNames() : array
    {
		return $this->filter_names;
	}

	public function fillRow(array $a_set): void
    {
		$this->tpl->setVariable('ID', $a_set['id']);
		foreach ($this->getSelectableColumns() as $k => $v) {
			if ($this->isColumnSelected($k)) {
				if ($a_set[$k] != '') {
					$this->tpl->setCurrentBlock('td');
					$formatter = (isset($v['formatter'])) ? $v['formatter'] : NULL;
					$value = $this->formatter->format($a_set[$k], $formatter);
					$this->tpl->setVariable('VALUE', $value);
					$this->tpl->parseCurrentBlock();
				} else {
					$this->tpl->setCurrentBlock('td');
					$this->tpl->setVariable('VALUE', '&nbsp;');
					$this->tpl->parseCurrentBlock();
				}
			}
		}
	}
}
