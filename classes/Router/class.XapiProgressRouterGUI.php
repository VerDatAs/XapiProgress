<?php

#namespace ILIAS\Plugins\XapiProgress\Router;

require_once __DIR__ . "/../../vendor/autoload.php";


#use ILIAS\DI\Container;
#use ilXapiProgressPlugin;
#use ILIAS\Plugin\XapiProgress\Event as Event;
#use ilObject;
#use ilObjectFactory;
#use ilUIPluginRouterGUI;

/**
 * Class XapiProgressRouterGUI
 *
 * @package           XapiProgressRouterGUI
 *
 * @author            internetlehrer-gmbh.de
 *
 * @ilCtrl_isCalledBy XapiProgressRouterGUI: ilUIPluginRouterGUI
 */
class XapiProgressRouterGUI
{

    const CMD_H5P_ACTION = "h5pAction";
    const GET_PARAM_OBJ_ID = "obj_id";
    const PLUGIN_CLASS_NAME = ilXapiProgressPlugin::class;

    const EXEC_BT = 'execBT';


    protected ilObject $object;

    private \ILIAS\DI\Container $dic;

    private ilLogger $logger;


    /**
     * XapiProgressRouterGUI constructor
     */
    public function __construct()
    {
        global $DIC;

        $this->dic = $DIC;

        $this->logger = $this->dic->logger()->root();

    }



    public function executeCommand() : void
    {

        $next_class = $this->dic->ctrl()->getNextClass($this);

        $cmd = $this->dic->ctrl()->getCmd();

        if($action = $this->dic->http()->request()->getQueryParams()[$cmd] ?? false) {

            #$this->dic->http()->close();

            $this->{$action}();

        }

        exit;

    }


    /**
     * @param string $action
     * @return string
     */
    public static function getUrl(string $action = 'handleEvent') : string
    {
        global $DIC;

        $DIC->ctrl()->setParameterByClass(self::class, self::GET_PARAM_OBJ_ID, $DIC->ctrl()->getContextObjId());

        $DIC->ctrl()->setParameterByClass(self::class, self::CMD_H5P_ACTION, $action);

        return $DIC->ctrl()->getLinkTargetByClass([ilUIPluginRouterGUI::class, self::class], self::CMD_H5P_ACTION, "", true, false);

    }


    /**
     * @throws Exception
     * @throws Exception
     */
    public function handleEvent() : bool
    {

        $this->logger->debug('############### InitRouteXapiRequest');

        $XapiProgress = new ilXapiProgressPlugin();

        $postBody = $this->dic->http()->request()->getParsedBody();

        $receivedVerb = $postBody['verb']['display'] ?? [uniqid()];

        $config = new ilXapiProgressConfig();
        $trackVerbs = $config->getH5pVerbs();
        if(in_array(array_pop($receivedVerb), $trackVerbs)) {

            $urlH5PModule = parse_url($postBody['UrlH5PModule'], PHP_URL_QUERY);



            /** @var array $queryParam */
            parse_str($urlH5PModule, $queryParam);

            $gotoLink = null;

            if($target = $queryParam['target'] ?? null) {

                $ids = explode('_', $target);

                $queryParam['ref_id'] = array_pop($ids);

                $queryParam['gotoLink'] = $postBody['UrlH5PModule'];

            }


            $this->logger->debug('############### InitRouteXapiRequest dump $uiEventData');
            #$this->logger->dump();

            $hasReadAccess = $this->dic->access()->checkAccessOfUser(
                $this->dic->user()->getId(),
                'read', 'read',
                (int)$queryParam['ref_id']
            );

            if($hasReadAccess && $refId = (int)$queryParam['ref_id'] ?? 0) {

                $handleEventParam = [
                    'obj_id' => \ilObject::_lookupObjectId($refId),
                    'ref_id' => $refId,
                    'usr_id' => $this->dic->user()->getId(),
                    'event' => 'H5P'
                ];

                $this->logger->debug('############### InitRouteXapiRequest handleEvent');

                $postBody['queryParamH5PModule'] = $queryParam;

                unset($postBody['UrlH5PModule']);

                $handleEventParam['uiEventData'] = $postBody;

                #$this->logger->debug(print_r($handleEventParam));

                $XapiProgress->handleEvent('Services/Tracking', 'H5P', $handleEventParam);

            }
        }

        return true;

    }


    public function sendAllStatements() : void
    {
        try {

            ilXapiProgressAsyncCron::runAsync();
            /*
            $this->dic->event()->raise('Services/Tracking', 'sendAllStatements', [
                'obj_id' => 1,
                'ref_id' => 1,
                'usr_id' => $this->dic->user()->getId(),
                #'event' => 'SendAllStatements'
            ]);
*/
            /*
            $XapiProgress = new ilXapiProgressPlugin();
            $XapiProgress->handleEvent('Services/Tracking', 'sendAllStatements', [
                'obj_id' => 1,
                'ref_id' => 1,
                'usr_id' => $this->dic->user()->getId(),
                'event' => 'SendAllStatements'
            ]);
            */
            $this->dic->logger()->root()->log('ASYNC REQUEST');

        } catch(Exception $e) {

            $this->dic->logger()->root()->log($e->getMessage());

            exit(500);

        }
    }


    public function execBT() : bool
    {
         $post = $this->dic->http()->request()->getParsedBody();

        $ns = $post['event'];

        $queueId = $post['queue_id'];

        /** @var ILIAS\Plugin\XapiProgress\Event\Services\Tracking\H5P|ILIAS\Plugin\XapiProgress\Event\Services\Tracking\SendAllStatements|ILIAS\Plugin\XapiProgress\Event\Services\Tracking\UpdateStatus $ns */
        new $ns($queueId);

        return true;
    }





}
