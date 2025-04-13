<?php
/* Copyright (c) internetlehrer GmbH, Extended GPL, see LICENSE */

require_once __DIR__ . "/../vendor/autoload.php";

use ILIAS\DI\Container;
use ILIAS\Plugin\XapiProgress\Event\Services\Tracking\SendStatementsByQueueId;
use ILIAS\Plugin\XapiProgress\Model\DbXapiProgressQueue;

/**
 * Class ilXapiProgressConfigGUI
 *
 * @author      Björn Heyser <info@bjoernheyser.de>
 * @author      Uwe Kohnle <kohnle@internetlehrer-gmbh.de>
 *
 * @ilCtrl_Calls ilXapiProgressConfigGUI: ilCommonActionDispatcherGUI
 * @ilCtrl_IsCalledBy ilXapiProgressConfigGUI: ilObjComponentSettingsGUI
 */
class ilXapiProgressConfigGUI extends ilPluginConfigGUI
{
    private array $onScreenMessage = [];

    /**
	 * @var ilXapiProgressPlugin
	 */
	protected ?ilPlugin $plugin_object;

    protected Container $dic;

    protected ilTabsGUI $tabs;

    protected ilGlobalTemplateInterface $tpl;

    protected ilLanguage $lng;

    /**
     * @var ilCtrl
     */
    protected ilCtrl $ctrl;

    protected ilSetting $settings;

    private bool $isPostRequestAllowed;

    private ilXapiProgressConfig $config;


    use DbXapiProgressQueue;

    public function __construct()
    {
        global $DIC; /** @var Container $DIC */

        $this->dic = $DIC;

        $this->tabs = $this->dic->tabs();

        $this->tpl = $this->dic->ui()->mainTemplate();

        $this->lng = $this->dic->language();

        $this->ctrl = $this->dic->ctrl();

        $this->settings = $this->dic->settings();

        $this->isPostRequestAllowed = $this->hasAvailableLrsTypes();

        $this->config = new ilXapiProgressConfig();

    }

    public function performCommand(string $cmd) : void
	{
        if(isset($this->dic->http()->request()->getQueryParams()['table_failed_statements_table_nav'])) {
            $cmd = 'tab_failed_statements';
        }

        switch ($cmd) {
            case 'configure':
            case 'tab_lrs_type':
                $this->initTabs('tab_lrs_type');
                $this->tabLrsType();
                break;

            case 'save_lrs_type':
                $this->saveLrsType();
                break;


            case 'tab_events':
                $this->initTabs('tab_events');
                $this->tabEvents();
                break;

            case 'save_events':
                $this->saveEvents();
                break;


            case 'tab_failed_statements':
            case 'apply_filter_failed_statements':
            case 'reset_filter_failed_statements':
                $this->countQueueEntries();
                $this->initTabs('tab_failed_statements');
                $this->tabFailedStatements($cmd);
                break;

            case 'confirm_delete_statements':
                $this->confirmDeleteStatements();
                break;

            case 'send_failed_statements':
                $this->sendFailedStatements();
                break;

            case 'tab_xapi_endpoint':
                $this->ctrl->redirectByClass(ilXapiProgressConfigGUI::class);
                break;

            case 'tab_tracking_verbs':
                $this->initTabs('tab_tracking_verbs');
                $this->tabTrackingVerbs();
                break;

            case 'save_tracking_verbs':
                $this->initTabs('tab_tracking_verbs');
                $this->saveTrackingVerbs();
                break;


            default:
                $this->dic->ui()->mainTemplate()->setOnScreenMessage('failure',$this->plugin_object->txt('not_supported_cmd') . $this->ctrl->getCmd(), true);
                #$this->tabFailedStatements();
                $this->ctrl->redirectByClass("ilobjcomponentsettingsgui", "listPlugins");
        }

	}

    private function initTabs(?string $tab = null) : void
    {
        if($tab) {

            $this->tabs->addTab('tab_lrs_type', $this->plugin_object->txt('configuration'),
                $this->ctrl->getLinkTarget($this, 'tab_lrs_type')
            );

            if($this->config->getLrsTypeId() != null) {

                $this->tabs->addTab('tab_events', 'Events',
                    $this->ctrl->getLinkTarget($this, 'tab_events')
                );

                $this->tabs->addTab('tab_tracking_verbs', $this->plugin_object->txt('h5pverbs'),
                    $this->ctrl->getLinkTarget($this, 'tab_tracking_verbs')
                );

                $this->tabs->addTab('tab_failed_statements', 'Statements',
                    $this->ctrl->getLinkTarget($this, 'tab_failed_statements')
                );
            }

            /*
            $this->tabs->addNonTabbedLink('tab_xapi_endpoint', ilXapiEndpointPlugin::PLUGIN_NAME,
                $this->ctrl->getLinkTarget(new ilXapiEndpointConfigGUI(), 'configure')
            );
            */
            $this->tabs->activateTab($tab);

        }
    }

    protected function tabLrsType(ilPropertyFormGUI $form = null) : void
    {
        $this->setContent(($form ?? $this->buildFormLrsType())->getHTML());
    }

    protected function buildFormLrsType(): ilPropertyFormGUI
    {
        global $DIC; /* @var \ILIAS\DI\Container $DIC */

        $form = new ilPropertyFormGUI();

        if(!$this->isPostRequestAllowed) {
            return $form;
        }

        $form->setFormAction($DIC->ctrl()->getFormAction($this));
        $form->addCommandButton('save_lrs_type', $this->lng->txt('save'));

        $form->setTitle($this->plugin_object->txt('configuration'));

        $ne = new ilNonEditableValueGUI($this->plugin_object->txt("last_update"), "");
        $ne->setValue($this->config->getLastUpdateBy());
        $form->addItem($ne);

        $item = new ilRadioGroupInputGUI($this->plugin_object->txt('lrs_type'), 'lrs_type_id');
        $item->setRequired(true);
        $types = ilCmiXapiLrsTypeList::getTypesData(false);
        foreach ($types as $type)
        {
            $option = new ilRadioOption($type['title'], $type['type_id'], $type['description']);
            $item->addOption($option);
        }
        if ($this->config->getLrsTypeId() != null) {
            $item->setValue($this->config->getLrsTypeId());
        }
        $form->addItem($item);

        $item = new ilCheckboxInputGUI($this->plugin_object->txt('only_course'),'only_course');
        $item->setInfo($this->plugin_object->txt('only_course_info'));
        $item->setValue("1");
        $item->setChecked($this->config->isOnlyCourse());

        $courses = new ilTextAreaInputGUI($this->plugin_object->txt('select_courses'), 'courses');
        $courses->setInfo($this->plugin_object->txt('select_courses_info'));
        $item->addSubItem($courses);

        $consent = new ilCheckboxInputGUI($this->plugin_object->txt('need_consent'), 'need_consent');
        $consent->setInfo($this->plugin_object->txt('need_consent_info'));
        $consent->setValue("1");
        $consent->setChecked($this->config->isNeedConsent());
        $item->addSubItem($consent);

        $delete = new ilRadioGroupInputGUI($this->plugin_object->txt('data_delete'), 'data_delete');
        $delete->setInfo($this->plugin_object->txt('data_delete_info'));
        $option = new ilRadioOption($this->plugin_object->txt('delete_never'), $this->config::DELETE_NEVER);
        $delete->addOption($option);
        $option = new ilRadioOption($this->plugin_object->txt('delete_obj_deleted'), $this->config::DELETE_OBJ_DELETED);
        $delete->addOption($option);
        $option = new ilRadioOption($this->plugin_object->txt('delete_obj_trash'), $this->config::DELETE_OBJ_TRASH);
        $option->setValue($this->config->getDataDelete());
        $delete->addOption($option);
        $item->addSubItem($delete);
        $form->addItem($item);

        return $form;
    }

    /*
    protected function configure(ilPropertyFormGUI $form = null)
    {
        $this->tabLrsType($form);
    }
    */

    protected function saveLrsType()
    {
        global $DIC; /* @var \ILIAS\DI\Container $DIC */

        $form = $this->buildFormLrsType();

        if( !$form->checkInput() ) {
            $this->tabLrsType($form);
        } else {
            $cfailure = [];
            $courses = [];
            $sCourses = rtrim((string) str_replace(["\n","\r"," "],'',$form->getInput('courses')),',');
            if ($sCourses != '') {
            $courses = explode(',', $sCourses);
            for ($i=0; $i<count($courses); $i++) {
                if (!is_numeric($courses[$i])) {
                    $cfailure[] = $courses[$i];
                }
            }
            }
            if (count($cfailure) > 0) {
                $this->tpl->setOnScreenMessage('failure', $this->plugin_object->txt("courses_failure"). ' ' .implode(',',$cfailure), true);
            } else {
                $this->config->setLrsTypeId((int) $form->getInput('lrs_type_id'));
                $this->config->setOnlyCourse((bool) $form->getInput('only_course'));
                $this->config->setCourses($courses);
                $this->config->setNeedConsent((bool) $form->getInput('need_consent'));
                $this->config->setDataDelete((int) $form->getInput('data_delete'));

                $this->config->save();
            }
        }

        $DIC->ctrl()->redirect($this, 'tab_lrs_type');
    }


    protected function tabEvents(ilPropertyFormGUI $form = null) : void
    {
        $this->setContent(($form ?? $this->buildFormEvents())->getHTML());
    }

    protected function buildFormEvents(): ilPropertyFormGUI
    {
        if($this->config->getLrsTypeId() == null) {
            $this->tpl->setOnScreenMessage('failure', $this->plugin_object->txt("lrs_type_not_set"), true);
        }
        //$this->plugin_object = new ilXapiProgressPlugin();

        $form = new ilPropertyFormGUI();

        $form->setFormAction($this->ctrl->getFormAction($this));
        $form->addCommandButton('save_events', $this->lng->txt('save'));

        $form->setTitle('Events');

        $allEvents = array_keys($this->config->getPossibleEvents());

        $defaultEvents = $this->plugin_object::getDefaultEvents();

        foreach ($allEvents as $event) {

            if(in_array($event, $defaultEvents)) {
                continue;
            }

            $item = new ilCheckboxInputGUI($event, $event);
            $item->setInfo($this->plugin_object->txt($event . '_info'));
            $item->setValue("1");
            if(in_array($event, $this->config->getEvents())) {
                $item->setChecked(true);
            }
            if ($event == 'afterChangeEvent') {
                $ni = new ilNumberInputGUI($this->plugin_object->txt('after_change_time_diff_min'),'after_change_time_diff_min');
                $ni->setValue((string) $this->dic->settings()->get('after_change_time_diff_min', "1"));
                $ni->setMaxLength(4);
                $ni->setSize(4);
                $ni->setInfo($this->plugin_object->txt('after_change_time_diff_min_info'));
                $item->addSubItem($ni);
            }
            if ($event == 'readCounterChange') {
                $ni = new ilNumberInputGUI($this->plugin_object->txt('read_counter_time_diff_min'),'read_counter_time_diff_min');
                $ni->setValue((string) $this->dic->settings()->get('read_counter_time_diff_min', "1"));
                $ni->setMaxLength(4);
                $ni->setSize(4);
                $ni->setInfo($this->plugin_object->txt('read_counter_time_diff_min_info'));
                $item->addSubItem($ni);
//                $ni = new ilNumberInputGUI($this->plugin_object->txt('tracking_time_span_max'),'tracking_time_span_max');
//                $ni->setValue((string) $this->dic->settings()->get('tracking_time_span_max', 3600));
//                $ni->setMaxLength(6);
//                $ni->setSize(6);
//                $ni->setInfo($this->plugin_object->txt('tracking_time_span_max_info'));
//                $item->addSubItem($ni);
            }

            $form->addItem($item);
        } // EOF foreach ($allEvents as $allEvent)

//        foreach ($defaultEvents as $event) {
//
//            $item = new ilHiddenInputGUI('event[]');
//
//            $item->setValue($event);
//
//            $form->addItem($item);
//
//        } // EOF foreach ($defaultEvents as $allEvent)

        return $form;
    }

    protected function saveEvents()
    {
        $form = $this->buildFormEvents();

        $form->setValuesByPost();

        if( !$form->checkInput() )
        {
            $this->tabEvents($form);
        }

        $events = [];
        $allEvents = array_keys($this->config->getPossibleEvents());

        foreach ($allEvents as $event) {
            if($form->getInput($event) !== null && $form->getInput($event) == "1") {
                $events[] = $event;
            }
        }
        if($this->config->getLrsTypeId() == null) {
            $this->tpl->setOnScreenMessage('failure', $this->plugin_object->txt("lrs_type_not_set"), true);
        } else {
            $this->config->setEvents($events);
            $this->config->save();
        }

//        if($form->getInput('tracking_time_span_max') !== null && $form->getInput('tracking_time_span_max') !== '') {
//            $this->settings->set('tracking_time_span_max', $form->getInput('tracking_time_span_max'));
//        }
        if($form->getInput('after_change_time_diff_min') !== null && $form->getInput('after_change_time_diff_min') !== '') {
            $this->settings->set('after_change_time_diff_min', (string) $form->getInput('after_change_time_diff_min'));
        }

        if($form->getInput('read_counter_time_diff_min') !== null && $form->getInput('read_counter_time_diff_min') !== '') {
            $this->settings->set('read_counter_time_diff_min', (string) $form->getInput('read_counter_time_diff_min'));
        }

        $this->ctrl->redirect($this, 'tab_events');
    }

//    protected function readEvents()
//    {
//        return json_decode($this->settings->get($this->plugin_object->getId() . '__events', 0));
//    }

//    protected function writeEvents($events)
//    {
//        $defaultEvents = $this->plugin_object::getDefaultEvents();
//        $newEvents = array_diff($events, $defaultEvents, [""]);
//        $events = array_merge($defaultEvents, $newEvents);
//        $this->settings->set($this->plugin_object->getId() . '__events', json_encode($events));
//    }


    protected function tabTrackingVerbs(ilPropertyFormGUI $form = null) : void
    {

        $this->setContent(($form ?? $this->buildFormTrackingVerbs())->getHTML());

    }

    protected function buildFormTrackingVerbs(): ilPropertyFormGUI
    {
        if($this->config->getLrsTypeId() == null) {
            $this->tpl->setOnScreenMessage('failure', $this->plugin_object->txt("lrs_type_not_set"), true);
        }
        $form = new ilPropertyFormGUI();

        if(!$this->isPostRequestAllowed) {
            return $form;
        }

        $form->setFormAction($this->ctrl->getFormAction($this));

        $form->addCommandButton('save_tracking_verbs', $this->lng->txt('save'));

        $form->setTitle($this->plugin_object->txt('h5pverbs'));

        $trackH5pVerbs = $this->config->getH5pVerbs();

        foreach ($this->config->getPossibleH5pVerbs() as $verb) {
            $cb = new ilCheckboxInputGUI($verb, $verb);
            $cb->setValue(1);
            $cb->setChecked(in_array($verb, $trackH5pVerbs));
            $form->addItem($cb);
        }

        return $form;
    }


    protected function saveTrackingVerbs()
    {
        $form = $this->buildFormTrackingVerbs();
        if( !$form->checkInput() )
        {
            $this->tabTrackingVerbs($form);
        } else {

            $trackVerbs = [];
            foreach ($this->config->getPossibleH5pVerbs() as $verb) {
                if ($form->getInput($verb)) {
                    $trackVerbs[] = $verb;
                }
            }
            if ($this->config->getLrsTypeId() == null) {
                $this->tpl->setOnScreenMessage('failure', $this->plugin_object->txt("lrs_type_not_set"), true);
            } else {
                $this->config->setH5pVerbs($trackVerbs);
                $this->config->save();
            }
        }
        $this->ctrl->redirect($this, 'tab_tracking_verbs');
    }


    protected function tabFailedStatements(string $cmd) : void
    {
        $tblContent = '';

        $filterValue = [];

        $this->setFailedStatementSummaries();

        if($this->countQueueEntries(self::$STATE_CRON_FAILED)) {
            $tableGui = new ilXapiProgressTableGUI($this);

            if($cmd === 'apply_filter_failed_statements') {
                /** @var ilDateTimeInputGUI $filterDateFailed */
                $filterDateFailed = $tableGui->getFilterItemByPostVar('date_failed');
                $filterDateFailed->setValueByArray($this->dic->http()->request()->getParsedBody());
                $filterValue['date_failed'] = $filterDateFailed->getDate(); # $this->dic->http()->request()->getParsedBody()['date_failed'];
                $filterValue['date_failed'] = is_null($filterValue['date_failed']) ? '' : substr($filterValue['date_failed']->__toString(), 0, 10);

                /** @var ilTextInputGUI $filterObjId */
                $filterObjId = $tableGui->getFilterItemByPostVar('obj_id');
                $filterObjId->setValueByArray($this->dic->http()->request()->getParsedBody());
                $filterValue['obj_id'] = $filterObjId->getValue();
            }

            $tableGui->setData(
                $tableGui->withRowSelector(
                    $this->getQueueEntriesWithStateFailed($filterValue)
                )
            );

            $modalStyle = '<style>.modal-dialog {width: 80%;}</style>';

            $tblContent = $modalStyle . $tableGui->getHTML();
        }

        $this->setContent($tblContent);
    }



    private function setFailedStatementSummaries() : bool
    {
        if(!$this->numQueueEntries) {
            $this->onScreenMessage[] = $this->renderMessage('success',$this->numQueueEntries . ' Statements');
            return true;
        }

        $setNumCronExec = function($state, $type = 'confirmation') {
            $txtWithState = ' Statements mit Status ';
            if($numCronExec = $this->countQueueEntries($state)) {
                $this->onScreenMessage[] = $this->renderMessage($type,$numCronExec . $txtWithState . $state);
            }
        };

        $setNumCronExec(self::$STATE_CRON_EXEC_1);
        $setNumCronExec(self::$STATE_CRON_EXEC_2);
        $setNumCronExec(self::$STATE_CRON_EXEC_3);
        $setNumCronExec(self::$STATE_CRON_FAILED, 'failure');

        return true;
    }

    function renderMessage(string $type = 'info', string $msg = '') : string
    {
        /** @var ILIAS\UI\Implementation\Component\MessageBox\MessageBox $type */
        $msgBox = $this->dic->ui()->factory()->messageBox()->$type($msg);
        return $this->dic->ui()->renderer()->render($msgBox);
    }

    private function confirmDeleteStatements() : void
    {
        $this->deleteStatements();
        $this->ctrl->redirect($this, 'tab_failed_statements');
    }

    private function deleteStatements() : void
    {
        foreach($this->dic->http()->request()->getParsedBody()['queue_id'] ?? [] AS $queueId) {
            $this->deleteQueueEntryById($queueId);
        }
    }

    private function sendFailedStatements() : void
    {
        new SendStatementsByQueueId($this->dic->http()->request()->getParsedBody()['queue_id']);
        $this->ctrl->redirect($this, 'tab_failed_statements');
    }


    protected function setContent(string $content) : void
    {
        if( !$this->hasAvailableLrsTypes() ) {
            $this->dic->ui()->mainTemplate()->setOnScreenMessage('failure',$this->plugin_object->txt('lrs_type_not_set'),true);
        }

        $this->tpl->setContent(
            implode('', $this->onScreenMessage) .
            $content
        );


    }

    public function hasAvailableLrsTypes() : bool
    {
        return (bool)count(ilCmiXapiLrsTypeList::getTypesData(false));
    }


    public function isPostRequestAllowed() : bool
    {
        return $this->isPostRequestAllowed;
    }

}
