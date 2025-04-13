<?php

require_once __DIR__ . "/../vendor/autoload.php";

/**
 * User interface hook class for XapiProgress-Plugin
 *
 * @author  Uwe Kohnle <kohnle@internetlehrer-gmbh.de>
 *
 * @version $Id$
 * @ingroup ServicesUIComponent
 */
class ilXapiProgressUIHookGUI extends ilUIHookPluginGUI
{
    public const TAB_REPORTS = 'reports';

    protected ilCtrl $ctrl;

    protected ilXapiProgressPlugin $pl;

    protected ilAccessHandler $access;

    protected \ILIAS\DI\Container $dic;


    public function __construct()
    {
        global $DIC;
        $this->dic = $DIC;
        $this->ctrl = $DIC->ctrl();
        $this->pl = ilXapiProgressPlugin::getInstance();
        $this->access = $DIC->access();
    }


    /**
     * Add a new tab 'reports' for courses which shows the report table for all the members of a
     * course
     */
    public function modifyGUI(string $a_comp, string $a_part, array $a_par = array()): void
    {
        global $ilTabs;

        if(isset($GLOBALS['tpl']) && $this->dic->offsetExists('tpl')) {
            $this->dic->ui()->mainTemplate()->addJavaScript('.' .
                str_replace(ILIAS_ABSOLUTE_PATH, '', dirname(__DIR__, 2)) .
                '/XapiProgress/src/js/XapiProgress.H5P.js');
        }

        if ($a_part == 'tabs') {
            if ($this->ctrl->getContextObjType() == 'crs') {
                $crsID = $this->ctrl->getContextObjId();
                if ($crsID) {
                    $arr_refId = ilObject2::_getAllReferences($crsID);
                    //check Permission "edit learning progress"
                    $refId = array_values($arr_refId);
                    if ($this->access->checkAccess("edit_learning_progress", "", $refId[0], "", $crsID)) {
                        $this->ctrl->saveParameterByClass(ilXapiProgressOverviewGUI::class, 'ref_id');
                        //$this->ctrl->setParameterByClass(ilXapiProgressOverviewGUI::class, 'rep_crs_ref_id', $refId[0]);
                        $uri = $this->ctrl->getLinkTargetByClass(array(
                            ilUIPluginRouterGUI::class,
                            ilXapiProgressOverviewGUI::class,
                        ), ilXapiProgressGUI::CMD_REPORT);
                        // Write the correct course ID into the session - this is used by the report table
                        $_SESSION[ilXapiProgressGUI::SESSION_KEY_IDS] = array( $crsID );
                        /** @var ilTabsGUI $ilTabsGUI */
                        $ilTabsGUI = $a_par['tabs'];
                        $ilTabsGUI->addTab(self::TAB_REPORTS, $this->pl->txt('reports'), $uri);
                        //                        $ilTabsGUI->addTab('consent', $this->pl->txt('consent'), $uri);
                        // save the tabs for reuse on the plugin pages
                        // (these do not have the test gui as parent)
                        // not nice, but effective
                        $_SESSION[ilXapiProgressOverviewGUI::class]['TabTarget'] = $ilTabs->target;
                        //$_SESSION[ilXapiProgressOverviewGUI::class]['TabSubTarget'] = $ilTabs->sub_target;
                    }
                }
            }
        }
    }

    /**
     * HTML Output.
     * @param mixed  $a_comp
     * @param mixed  $a_part
     * @param array  $a_par
     * @return array
     * @throws ilObjectException
     * @throws ilObjectNotFoundException
     * @global mixed $DIC
     */
    public function getHTML($a_comp, $a_part, array $a_par = array()): array
    {
        if ($a_comp === 'Services/Container' && $a_part === 'right_column') {
            if (strtolower($this->dic->ctrl()->getCmdClass()) === strtolower(ilObjCourseGUI::class)) {
                $config = new ilXapiProgressConfig();
                if($config->isOnlyCourse() && $config->isNeedConsent()) {
                    $refId = $this->filterRefId();
                    $checked = $config->getConsentForUser($this->dic->user()->getId(), $refId);
                    if ($this->dic->http()->wrapper()->query()->has('XapiProgressConsent')) {
                        $checked = (bool) $this->dic->http()->wrapper()->query()->retrieve(
                            'XapiProgressConsent',
                            $this->dic->refinery()->kindlyTo()->int()
                        );
                        $config->setConsentForUser($this->dic->user()->getId(), $refId, $checked);
                        $this->dic->ctrl()->redirectByClass(ilObjCourseGUI::class, $this->dic->ctrl()->getCmd());
                    }

                    return array(
                        'mode' => self::APPEND,
                        'html' => $this->getConsentForm($checked, $config->getLrsTypeId())
                    );
                }
            }
        }

        return array(
            'mode' =>   '',
            'html' =>   ''
        );
    }

    private function getConsentForm(bool $currState, int $lrsTypeId): string
    {
        $this->dic->language()->loadLanguageModule('cmix');
        $lrsType = new ilCmiXapiLrsType($lrsTypeId);
        $identMode = 'conf_privacy_ident_' . ilObjCmiXapiGUI::getPrivacyIdentString($lrsType->getPrivacyIdent());
        $nameMode = 'conf_privacy_name_' . ilObjCmiXapiGUI::getPrivacyNameString($lrsType->getPrivacyName());

        $tpl = $this->plugin_object->getTemplate('default/tpl.consent_panel.html');
        $tpl->setVariable('TXT_BLOCK_HEADER', '');
        $tpl->setVariable('INFO_ALLOW_LP2LRS', $this->plugin_object->txt('consent_allow_info') . ' ' . $lrsType->getTitle());
        #$tpl->setVariable('TXT_CURRENT_STATE', $this->plugin_object->txt('txt_current_state'));
        $tpl->setVariable('CURRENT_STATE', $currState ? $this->plugin_object->txt('consent_enabled') : $this->plugin_object->txt('consent_disabled'));
        $tpl->setVariable(
            'TOGGLE_BUTTON',
            $this->dic->ui()->renderer()->render([$this->getToggleButtonUI($currState)])
        );

        #cmix_lrs_type
        //        $tpl->setVariable('HEADER_PRIVACY_LRS_TITLE', $this->dic->language()->txt('cmix_lrs_type'));
        //        $tpl->setVariable('PRIVACY_LRS_TITLE', $lrsType->getTitle());

        $tpl->setVariable('HEADER_PRIVACY_IDENT_MODE', $this->dic->language()->txt('username'));
        $tpl->setVariable('PRIVACY_IDENT_MODE', $this->dic->language()->txt($identMode));

        $tpl->setVariable('HEADER_PRIVACY_NAME_MODE', $this->dic->language()->txt('content_privacy_ident'));
        $tpl->setVariable('PRIVACY_NAME_MODE', $this->dic->language()->txt($nameMode));

        # nameMode

        # $this->dic->language()->txt("conf_user_registered_mail")


        $status = '<div class="dropdown" style="display: inline; float: right;"><span id="lp2lrsCurrentState"><i>' . ($currState ? $this->plugin_object->txt('consent_enabled') : $this->plugin_object->txt('consent_disabled')) . '</i></span></div>';

        $acc = new ilAccordionGUI();
        $acc->addItem($this->plugin_object->txt('consent_allow_header') . $status, $tpl->get());
        return $acc->getHTML();
    }


    private function getToggleButtonUI(bool $currentState): ILIAS\UI\Component\Component
    {
        $uri = $this->dic->http()->request()->getUri() . '&XapiProgressConsent=';
        $url = substr($uri, 0, strpos($uri, '&XapiProgressConsent='));
        $url .= '&XapiProgressConsent=';

        $b = $this->dic->ui()->factory()->button()->toggle('', $url . '1', $url . '0', $currentState);
        #$b->withUnavailableAction();

        return $b->withAdditionalOnLoadCode(function ($id) {
            return '
                $(\'#' . $id . '\').on(\'click\', function(e) {
                    $(\'#' . $id . '\').prop(\'disabled\', \'disabled\');
                    $(\'#lp2lrsCurrentState\').html(\'&nbsp;\').addClass(\'il-btn-with-loading-animation\');
                }).prop(\'disabled\', \'disabled\');
                setTimeout(function() {
                    $(\'#' . $id . '\').prop(\'disabled\', \'\');
                }, 1000);
            ';
        });
    }


    /**
     * Get the Object RefId from the URL.
     *
     * @return integer|null
     */
    private function filterRefId(): ?int
    {
        $obj_ref_id = filter_input(INPUT_GET, 'ref_id');

        if ($obj_ref_id === null) {
            $param_target = filter_input(INPUT_GET, 'target');
            $obj_ref_id = explode("_", $param_target)[1];
        }

        $obj_ref_id = intval($obj_ref_id);

        if ($obj_ref_id > 0) {
            return $obj_ref_id;
        } else {
            return null;
        }
    }


}
