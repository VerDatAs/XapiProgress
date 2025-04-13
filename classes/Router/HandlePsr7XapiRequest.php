<?php
namespace ILIAS\Plugins\XapiProgress\Router;
/**
 * Handle Client Requests
 *
 * @author  Uwe Kohnle <kohnle@internetlehrer-gmbh.de>
 * @author  Christian Stepper <stepper@internetlehrer-gmbh.de>
 */


use XapiProgressRouterGUI;
use Exception;
use ilXapiProgressPlugin;
use ILIAS\DI\Container;
use ILIAS\HTTP\Response\Sender\ResponseSendingException;
use ilObject;
use ilObjectFactory;
use ilPlugin;
use ilTemplate;

#use ilXapiProgressPlugin;


/**
 * @property $storedParams
 */
class HandlePsr7XapiRequest
{
    CONST LOAD_JS_QUERY_CMD = ['showContents', 'view', 'resume', 'layout', 'ilObjLearningModule'];

    CONST XAPI_PSR7_REQUEST_HEADER = 'xAPI';


    /**
     * @var null|self
     */
    private static ?HandlePsr7XapiRequest $instance = null;

    protected Container $dic;

//    protected int $refId;

    protected int $objId;

    protected int $usrId;

    protected ilXapiProgressPlugin $parentObj;

    protected bool $ishandleXapiRequest;
    /**
     * @var \GuzzleHttp\Psr7\ServerRequest|\Psr\Http\Message\ServerRequestInterface
     */
    private $request;


    public function __construct(ilXapiProgressPlugin $parentObj)
    {
        global $DIC; /** @var Container $DIC */

        $this->dic = $DIC;

        $this->parentObj = $parentObj;
//ToDo
//        $this->request = \GuzzleHttp\Psr7\ServerRequest::fromGlobals();
        $this->dic->logger()->root()->debug("CHECKGUIREQUEST");
        if ($this->isH5PObjGuiRequest()) {
            $this->dic->logger()->root()->debug("GUIREQUEST");
            if (!$this->isXapiRequest()) {

//                $this->dic->logger()->root()->debug("MODIFYGUIREPONSE");
//                $this->modifyH5PObjGuiResponse();

            }

        } else {

            #$this->modifyResponseSendAllStatements();

        }


    }


    private function isH5PObjGuiRequest() : bool
    {
        $refId = false;
        if ($this->dic->http()->wrapper()->query()->has('ref_id')) {
            $refId = $this->dic->http()->wrapper()->query()->retrieve('ref_id',$this->dic->refinery()->kindlyTo()->int());
        }
        $cmd = false;
        if ($this->dic->http()->wrapper()->query()->has('cmd')) {
            $cmd = $this->dic->http()->wrapper()->query()->retrieve('cmd',$this->dic->refinery()->kindlyTo()->string());
        }
        $target = false;
        if ($this->dic->http()->wrapper()->query()->has('target')) {
            $refId = $this->dic->http()->wrapper()->query()->retrieve('target',$this->dic->refinery()->kindlyTo()->string());
        }

//
//        $this->refId = (int)(filter_var($this->request->getQueryParams()['ref_id'], FILTER_SANITIZE_NUMBER_INT) ?? 0);
//
//        $cmd = filter_var($this->request->getQueryParams()['cmd'], FILTER_SANITIZE_STRING) ?? null;
//
//        $target = filter_var($this->request->getQueryParams()['target'], FILTER_SANITIZE_STRING) ?? false;

        if(!$refId && $target) {

            $idsArr = explode('_', $target);

            $refId =  (int)array_pop($idsArr);

        }

        if($refId && !$cmd) {

//            try {
//die($refId);
//                /** @var ilObject $obj */
//                $obj = ilObjectFactory::getInstanceByRefId($refId);

//                $cmd = get_class($obj);

//            } catch (Exception $e) {
//                return false;
//            }
        }
        $this->dic->logger()->root()->debug("GUIREPONSECMD=".$cmd);
        if($refId && in_array($cmd, self::LOAD_JS_QUERY_CMD)) {

            return true;

        }

        return false;

    }

    protected function isXapiRequest() : bool
    {
        $hasHeader = $this->dic->http()->request()->hasHeader(self::XAPI_PSR7_REQUEST_HEADER);
//            $this->request->hasHeader(self::XAPI_PSR7_REQUEST_HEADER);

        if($hasHeader) {

            return true;

        }

        return false;

    }

    //todo: Check patch for h5p-plugin
    private function modifyH5PObjGuiResponse() : void
    {

        $urlParts = parse_url(ILIAS_HTTP_PATH . '/' . XapiProgressRouterGUI::getUrl());

        /** @var array $queryParam */
        parse_str($urlParts['query'], $queryParam);

        $cmdClassParts = explode('\\', $queryParam['cmdClass']);

        $queryParam['cmdClass'] = array_pop($cmdClassParts); # ('events2lrsroutergui');

        $urlRouterQuery = http_build_query($queryParam);

        $urlRouter = ILIAS_HTTP_PATH . '/ilias.php?' . $urlRouterQuery;

        $iliasPath = ILIAS_HTTP_PATH;

        $scriptPath = str_replace(ILIAS_ABSOLUTE_PATH, '', dirname(__DIR__, 2)) . '/src/js/';

        $iliasHttpScriptPath = ILIAS_HTTP_PATH . $scriptPath;

        $initXapiProgressJs = $iliasHttpScriptPath . 'init.XapiProgress.js';

        $initH5PJs = $iliasHttpScriptPath . 'init.H5P.js';

        if(isset($GLOBALS['tpl'])) {
            $this->dic->logger()->root()->debug("GLOBALSexist");
        }


        if($this->dic->offsetExists('tpl')) {
            $this->dic->logger()->root()->debug("OFFSETexist");
        }


//        $this->dic->globalScreen()->layout()->factory()->content()->
        // todo check ui template fix and remove 1 === 2 &&
        if(isset($GLOBALS['tpl']) && $this->dic->offsetExists('tpl')) {
            $this->dic->logger()->root()->debug("BOTHexist");
//            /** @var \ilGlobalTemplate $ilTpl */
//            global $ilTpl;

//            $tpl = $this->dic['tpl'];

            $tpl = $this->dic->ui()->mainTemplate();
//
//            $tpl->addOnLoadCode('let urlXapiProgressRouterGUI = "' . $urlRouter . '";');
//            $this->dic->logger()->root()->debug('let urlXapiProgressRouterGUI = "' . $urlRouter . '";');
//            $tpl->addJavaScript(ILIAS_HTTP_PATH .
//                str_replace(ILIAS_ABSOLUTE_PATH, '', dirname(__DIR__, 2)) .
//                '/src/js/h5p_ilXaaS.js');
//
//            $this->dic->logger()->root()->debug(ILIAS_HTTP_PATH .
//                str_replace(ILIAS_ABSOLUTE_PATH, '', dirname(__DIR__, 2)) .
//                '/src/js/h5p_ilXaaS.js');

            $tpl->addJavaScript(ILIAS_HTTP_PATH .
                str_replace(ILIAS_ABSOLUTE_PATH, '', dirname(__DIR__, 2)) .
                '/src/js/XapiProgress.H5P.js');

//            echo('<script src="'.$initXapiProgressJs.'"></script>');
//            echo('<script>XapiProgress = $.extend(XapiProgress, {iliasHttpPath: "'. $iliasPath .'",pluginScriptPath: "'.$scriptPath.'",urlRouterGUI: "'.$urlRouter.'"});'
//                . 'XapiProgress.getScript("init.H5P.js");'
//                . '</script>');

        }

    }


    private function modifyResponseSendAllStatements() : void
    {
        $urlParts = parse_url(ILIAS_HTTP_PATH . '/' . XapiProgressRouterGUI::getUrl('sendAllStatements'));

        /** @var array $queryParam */
        parse_str($urlParts['query'], $queryParam);

        $cmdClassParts = explode('\\', $queryParam['cmdClass']);

        $queryParam['cmdClass'] = array_pop($cmdClassParts);

        $urlRouterQuery = http_build_query($queryParam);

        $urlRouter = ILIAS_HTTP_PATH . '/ilias.php?' . $urlRouterQuery;
if(!$this->dic->http()->request()->hasHeader('X-Requested-With')) {
    echo <<<HEREDOC
<script>
(function ($) {

    $(window).one('load', function (e) {
        $.ajax({
            type: 'GET',
            async: true,
            url: "$urlRouter"
        });
    });
})(jQuery);
</script>
HEREDOC;
}
    }

    public static function fixUITemplateInCronContext() : void
    {
        global $DIC; /** @var Container */

        // Fix missing tpl ui in cron context used in some core object constructor
        if ($DIC->offsetExists("tpl")) {
            if (!isset($GLOBALS["tpl"])) {
                $GLOBALS["tpl"] = $DIC->ui()->mainTemplate();
            }
        } else {
            if (!isset($GLOBALS["tpl"])) {
                $GLOBALS["tpl"] = new class() extends ilTemplate {

                    /**
                     * @inheritDoc
                     */
                    public function __construct()
                    {
                        #parent::__construct();
                    }
                };
            }

            $DIC->offsetSet("tpl", $GLOBALS["tpl"]);
        }
    }




}



