<?php

/**
 * TableGUI ilXapiProgressReportTableGUI
 *
 * @author  Uwe Kohnle <kohnle@internetlehrer-gmbh.de>
 * @version $Id:
 */
abstract class ilXapiProgressReportTableGUI extends ilTable2GUI {

	protected array $ignored_cols = array();

	protected array $date_cols = array();

	protected string $date_format = ilXapiProgressFormatter::DEFAULT_DATE_FORMAT;

	protected ilXapiProgressPlugin $pl;

	protected ilToolbarGUI $toolbar;

	protected ilCtrl $ctrl;

	protected ilXapiProgressFormatter $formatter;

	protected string $filter_cmd = ilXapiProgressGUI::CMD_APPLY_FILTER_REPORT;

	protected string $reset_cmd = ilXapiProgressGUI::CMD_RESET_FILTER_REPORT;

	protected array $filter_names = array();

	/**
	 * @param ilXapiProgressGUI $a_parent_obj
	 * @param string         $a_parent_cmd
	 */
	function __construct(ilXapiProgressGUI $a_parent_obj, $a_parent_cmd) {
		global $DIC;
		$this->pl = ilXapiProgressPlugin::getInstance();
		$this->toolbar = $DIC->toolbar();
		$this->ctrl = $DIC->ctrl();
		$this->formatter = ilXapiProgressFormatter::getInstance();
		$this->setId('ui_' . $this->ctrl->getCmdClass());
		$this->setPrefix('pre');
		parent::__construct($a_parent_obj, $a_parent_cmd);
		$this->setIgnoredCols(array( 'id', 'unique_id', 'obj_id', 'ref_id' ));
		$this->setDateCols(array( 'status_changed' ));
        if ($this->ctrl->getCmdClass() == 'ilXapiProgressOverviewGUI') {
			$report = $_POST["lpd_report"];
			switch ($report) {
				case "overview":
				case "choose":
					// ToDo
					$this->setRowTemplate('tpl.template_row_overview.html', $this->pl->getDirectory());
					break;
				default:
					// Custom Repoort
					$reportTemplate = $this->pl->getDirectory() . "/templates/reports/". $report . "/tpl.template_row.html" ;
					if (file_exists($reportTemplate)) {
						$this->setRowTemplate($reportTemplate);
					} else {
						$this->setRowTemplate('tpl.template_row_overview.html', $this->pl->getDirectory());
					}
			}
        } else {
            $this->setRowTemplate('tpl.template_row.html', $this->pl->getDirectory());
            $this->initColumns();
        }
		$this->setFormAction($this->ctrl->getFormAction($this->parent_obj));
		$this->setEnableHeader(true);
		$this->setEnableTitle(true);
		//$this->setTopCommands(true);
		$this->setShowRowsSelector(false);

        $this->initToolbar();
//		$this->parent_object = $a_parent_obj;
		//$this->setExportFormats(array());
		$this->setDisableFilterHiding(true);
		$this->initFilter();
	}


	/**
	 * @inheritdoc
	 */
	public function numericOrdering(string $a_field): bool
    {
		return true;
	}


	/**
	 * @inheritdoc
	 */
	public function setExportFormats(array $formats): void
    {
		parent::setExportFormats(array( self::EXPORT_EXCEL, self::EXPORT_CSV ));
//		foreach ($this->parent_object->getAvailableExports() as $k => $format) {
//			$this->export_formats[$k] = $this->pl->getPrefix() . '_' . $format;
//		}
	}


	/**
	 * Add filters for status and status changed
	 */
	public function initFilter(): void
    {
		global $DIC;
		$lang = $DIC->user()->getLanguage();
		$item = new ilSelectInputGUI($this->pl->txt('lpd_report'), 'lpd_report');
		$reports = json_decode(file_get_contents('./Customizing/global/plugins/Services/UIComponent/UserInterfaceHook/XapiProgress/templates/reports.json'),true);
        $lpd_reports = array( 'choose' => $this->pl->txt('choose'));
        $lpd_reports['overview'] = $this->pl->txt('overview');
		foreach ($reports as $report) {
			$reportTxt = (isset($report[$lang])) ? $report[$lang] : $report["en"];
			$lpd_reports[$report["id"]] = $reportTxt;
		}
        $item->setOptions($lpd_reports);
		$this->addFilterItemWithValue($item);
		$item = new ilDateTimeInputGUI($this->pl->txt('status_changed_from'), 'status_changed_from');
		//$item->setMode(ilDateTimeInputGUI::MODE_INPUT);
		$this->addFilterItemWithValue($item);
		$item = new ilDateTimeInputGUI($this->pl->txt('status_changed_to'), 'status_changed_to');
		//$item->setMode(ilDateTimeInputGUI::MODE_INPUT);
		$this->addFilterItemWithValue($item);
	}


	public function fillRow($a_set): void
    {
        if (strtolower($this->ctrl->getCmdClass()) != 'ilxapiprogressoverviewgui') {
            $this->tpl->setVariable('ID', $a_set['id']);
            foreach ($this->getColumns() as $k => $v) {
                if (isset($a_set[$k])) {
                    if (!in_array($k, $this->getIgnoredCols())) {
                        if (in_array($k, $this->getDateCols())) {
                            $value = $this->formatter->format($a_set[$k],
                                ilXapiProgressFormatter::FORMAT_STR_DATE, [
                                    'format' => $this->getDateFormat()
                                ]);
                        } else {
                            $formatter = isset($v['formatter']) ? $v['formatter'] : null;
                            $value = $this->formatter->format($a_set[$k], $formatter);
                        }
                        $this->tpl->setCurrentBlock('td');
                        $this->tpl->setVariable('VALUE', $value);
                        $this->tpl->parseCurrentBlock();
                    }
                } else {
                    $this->tpl->setCurrentBlock('td');
                    $this->tpl->setVariable('VALUE', '&nbsp;');
                    $this->tpl->parseCurrentBlock();
                }
            }
        }
	}


	/**
	 * Method each subclass must implement to handle custom exports
	 */
	public abstract function exportDataCustom(int $format, bool $send = false);


	/**
	 * Apply custom report downloads
	 */
	public function exportData(int $format, bool $send = false): void
    {
//		if (in_array($format, array_keys($this->parent_object->getAvailableExports()))) {
//			$this->exportDataCustom($format, $send);
//		} else {
			parent::exportData($format, $send);
//		}
	}


	/**
	 * Init toolbar containing a back link
	 * Determine if the user has clicked on the report tab in a course ($_GET['rep_crs_ref_id'] is
	 * set) or the back link should go to the parent's search form...
	 */
	protected function initToolbar() {
		/*
		if (isset($_GET['rep_crs_ref_id'])) {
			$this->ctrl->setParameterByClass(ilObjCourseGUI::class, 'ref_id', $_GET['rep_crs_ref_id']);
			$url = $this->ctrl->getLinkTargetByClass(array( ilRepositoryGUI::class, ilObjCourseGUI::class ));
			$txt = $this->pl->txt('back_to_course');
		} else {
			$url = $this->ctrl->getLinkTarget($this->parent_obj);
			$txt = $this->pl->txt('back');
		}
		$button = ilLinkButton::getInstance();
		$button->setCaption('<b>&lt; ' . $txt . '</b>', false);
		$button->setUrl($url);
		$this->toolbar->addButtonInstance($button);
		*/
        $class = $this->ctrl->getCmdClass();
        $this->ctrl->setParameterByClass($class, 'ref_id', $_GET['ref_id']);
        $uri = $this->ctrl->getLinkTargetByClass(array(
            ilUIPluginRouterGUI::class,
            $class,
        ), "refreshdata");
        $button = ilLinkButton::getInstance();
        $txt = $this->pl->txt('refresh_data');
        $button->setCaption('<b>' . $txt . '</b>', false);
        $button->setUrl($uri);
        $this->toolbar->addButtonInstance($button);
	}


	/**
	 * Setup columns based on data
	 */
	protected function initColumns() {
		foreach ($this->getColumns() as $k => $v) {
			$this->addColumn($v['txt'], $k, 'auto');
		}
	}


	/**
	 * @return array
	 */
	protected function getColumns() : array
    {
		return array(
			'title' => array( 'txt' => $this->pl->txt('title') ),
			'path' => array( 'txt' => $this->pl->txt('path') ),
			'firstname' => array( 'txt' => $this->pl->txt('firstname') ),
			'lastname' => array( 'txt' => $this->pl->txt('lastname') ),
			'country' => array( 'txt' => $this->pl->txt('country') ),
			'department' => array( 'txt' => $this->pl->txt('department') ),
			'org_units' => array( 'txt' => $this->pl->txt('org_units'), 'default' => true ),
			'grade' => array( 'txt' => $this->pl->txt('grade') ),
			'comments' => array( 'txt' => $this->pl->txt('comments') ),
			'active' => array(
				'txt' => $this->pl->txt('active'),
				'formatter' => ilXapiProgressFormatter::FORMAT_INT_YES_NO,
			),
			'status_changed' => array( 'txt' => $this->pl->txt('status_changed') ),
			'user_status' => array(
				'txt' => $this->pl->txt('user_status'),
				'formatter' => ilXapiProgressFormatter::FORMAT_INT_STATUS,
			),
		);
	}


	/**
	 * Excel Version of Fill Header. Likely to
	 * be overwritten by derived class.
	 * @param ilExcel $a_excel excel wrapper
	 * @param int     $a_row   row counter
	 */
	protected function fillHeaderExcel(ilExcel $a_excel, int &$a_row): void
    {
		$col = 0;
		foreach ($this->getColumns() as $column) {
			$title = strip_tags($column["txt"]);
			if ($title) {
				$a_excel->setCell($a_row, $col ++, $title);
			}
		}
		$a_excel->setBold("A" . $a_row . ":" . $a_excel->getColumnCoord($col - 1) . $a_row);
	}


	/**
	 * Excel Version of Fill Row. Most likely to
	 * be overwritten by derived class.
	 * @param ilExcel $a_excel excel wrapper
	 * @param int     $a_row   row counter
	 * @param array   $a_set   data array
	 */
	protected function fillRowExcel(ilExcel $a_excel, int &$a_row, array $a_set): void
    {
		$col = 0;

		foreach ($this->getColumns() as $key => $column) {
			$formatter = isset($column['formatter']) ? $column['formatter'] : NULL;
			$value = $this->formatter->format($a_set[$key], $formatter);

			if (is_array($a_set[$key])) {
				$value = implode(', ', $a_set[$key]);
			}
			$a_excel->setCell($a_row, $col ++, $value);
		}
	}


	protected function fillRowCSV(ilCSVWriter $a_csv, array $a_set): void
    {
		foreach ($this->getColumns() as $k => $v) {
			if (!in_array($k, $this->getIgnoredCols())) {
				if (isset($a_set[$k])) {
					$formatter = isset($v['formatter']) ? $v['formatter'] : NULL;
					$value = $this->formatter->format($a_set[$k], $formatter);
				} else {
					$value = '';
				}
				$a_csv->addColumn(strip_tags($value));
			}
		}
		$a_csv->addRow();
	}


	protected function addFilterItemWithValue(ilFormPropertyGUI $item, bool $optional = false): void
    {
		$this->addFilterItem($item, $optional);
		$value = $this->getFilterItemValue($item);
		$this->filter_names[$item->getPostVar()] = $value;
	}


	/**
	 * Return value of a filter depending on the InputGUI class
	 * @param ilFormPropertyGUI $item
	 * @return bool|object|string
	 */
	protected function getFilterItemValue(ilFormPropertyGUI $item)
    {
        /**
		 * @var ilFormPropertyGUI $item
		 */
		$value = '';
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
				// Why is this necessary? Bug? ilDateTimeInputGUI::clearFromSession() has no effect...
				if ($this->ctrl->getCmd() == ilXapiProgressGUI::CMD_RESET_FILTER_REPORT) {
					$item->setDate(NULL);
				}
				$date = $item->getDate();
				if ($date) {
					$value = $date;
				}
				break;
			default:
				$value = $item->getValue();
				break;
		}

		return $value;
	}

	/******************************************************
	 * Getters & Setters
	 ******************************************************/

	/**
	 * Get all the filter with the current value from session
	 */
	public function getFilterNames() : array
    {
		foreach ($this->getFilterItems() as $item) {
			$this->filter_names[$item->getPostVar()] = $this->getFilterItemValue($item);
		}

		return $this->filter_names;
	}


	public function setIgnoredCols(array $ignored_cols) {
		$this->ignored_cols = $ignored_cols;
	}

	public function getIgnoredCols() : array
    {
		return $this->ignored_cols;
	}

	public function setDateCols(array $date_cols) {
		$this->date_cols = $date_cols;
	}

	public function getDateCols() : array
    {
		return $this->date_cols;
	}

	public function setDateFormat(string $date_format) {
		$this->date_format = $date_format;
	}

	public function getDateFormat() : string
    {
		return $this->date_format;
	}
}
