<?php
/* Copyright (c) internetlehrer GmbH, Extended GPL, see LICENSE */

/**
 * Class ilXapiProgressCron
 *
 * @author      Uwe Kohnle <kohnle@internetlehrer-gmbh.de>
 * @author      Björn Heyser <info@bjoernheyser.de>
 */


require_once dirname(__DIR__) . "/vendor/autoload.php";

use \ILIAS\DI\Container;
use ILIAS\Plugin\XapiProgress\Xapi\Request\XapiRequest;

class ilXapiProgressAsyncCron extends ilCronJob
{
	const JOB_ID = 'sendstatements_async';

    protected Container $dic;

    protected ?ilCmiXapiLrsType $lrsType = null;

    protected ?XapiRequest $lrsRequest = null;

    use \ILIAS\Plugin\XapiProgress\Model\DbXapiProgressQueue;


    public function __construct()
	{
        global $DIC; /**@var Container $DIC */
        $this->dic = $DIC;
        $this->dic->logger()->root()->log(' init CronJob');
	}
	
	public function getId() : string
    {
		return self::JOB_ID;
	}

    public function getTitle() : string
    {
        return $this->dic->language()->txt("ui_uihk_xapip_async_cronjob_title");
    }

    public function getDescription() : string
    {
        return $this->dic->language()->txt("ui_uihk_xapip_async_cronjob_description");
    }

    public function getDefaultScheduleType() : int
    {
        return self::SCHEDULE_TYPE_DAILY;
    }

    public function getDefaultScheduleValue(): int
    {
        return 1;
    }

    public function hasAutoActivation() : bool
    {
        return true;
    }

    public function hasFlexibleSchedule() : bool
    {
        return false;
    }

	public function run(): ilCronJobResult
    {
        #$this->dic->logger()->root()->log(' try run x');

		$cronResult = new ilCronJobResult();
        $cronResult->setStatus(ilCronJobResult::STATUS_NO_ACTION);

        try {

            $this->execJob();

            $cronResult->setStatus(ilCronJobResult::STATUS_OK);

            #$this->dic->logger()->root()->log('ASYNC CRON EXECUTED');

        } catch(Exception $e) {

            $cronResult->setStatus(ilCronJobResult::STATUS_FAIL);

            $this->dic->logger()->root()->log($e->getMessage());

        }

		return $cronResult;
	}


    private function execJob() : void
    {
        $this->lrsType = $this->lrsType ?? \ilXapiProgressPlugin::getLrsType();

        $this->lrsRequest = $this->lrsRequest ?? new XapiRequest(
                $this->lrsType->getLrsEndpointStatementsLink(),
                $this->lrsType->getLrsKey(),
                $this->lrsType->getLrsSecret()
            );

        #$statements = $this->getQueueEntriesWithStateScheduled(true, true);
        $statements = [];

        $statements = array_replace(
            $statements,
            $this->getQueueEntriesWithStateInitialized(
                true,
                true,
                $this->withUserId()
            )
        );

        #$this->dic->logger()->root()->dump($statements);

        foreach ($statements as $queueId => $statement) {

            if($this->lrsRequest->sendStatement($statement)) {

                $this->updateQueueWithStateDeletableById($queueId);

            } else {

                usleep(10);

                $newState = (int)self::$STATE_CRON_EXEC_1;

                $newFailedDate = date('Y-m-d H:i:s');

                $this->updateQueueEntryWithStateAndFailedDateById((int)$queueId, $newState, $newFailedDate);

            }

        }


        if(count($statements)) {

            $this->dic->event()->raise('Services/Tracking', 'handleQueueEntries', [
                'obj_id' => 1,
                'ref_id' => 1,
                'usr_id' => $this->dic->user()->getId(),
            ]);
        }

    }

    private function withUserId() : ?int
    {
        $userId = $this->dic->user()->getId();

        $globalAdminRole = $this->dic->rbac()->review()->getRolesByFilter(
            $this->dic->rbac()->review()::FILTER_ALL_GLOBAL,
            $userId,
            'Administrator'
        )[0]['rol_id'] ?? 0;

        $assignedGlobalRoles = $this->dic->rbac()->review()->assignedGlobalRoles($userId);

        return !in_array($globalAdminRole, $assignedGlobalRoles) ? $userId : null;

    }

    public static function runAsync() : bool
    {
        global $DIC;
        $_this = new self();

        $deactivate = false;

        #if(!($state = ilCronManager::isJobActive($_this::JOB_ID))) {

            #ilCronManager::activateJob($_this, true);

            #$deactivate =
            #$state = true;

        #}

        $state = $DIC->cron()->manager()->isJobActive($_this::JOB_ID);

        if($state) {
            $DIC->cron()->manager()->runJobManual($_this::JOB_ID, $DIC->user());
        }

        if($deactivate) {

            #ilCronManager::deactivateJob($_this);

        }

        #$_this->dic->logger()->root()->log(($state ? 'activate' : 'deactivate') . ' CronJob ' . self::JOB_ID);

        return true;
    }

    public static function installAsyncJob(ilXapiProgressPlugin $plugin)
    {
        global $DIC;
        if (isset($DIC['cron.repository'])) {
            $job = $DIC->cron()->repository()->getJobInstance(self::JOB_ID, $plugin::PLUGIN_COMPONENT, self::class,
                false); //Todo Check false
            $DIC->cron()->repository()->createDefaultEntry($job, $plugin::PLUGIN_COMPONENT, self::class,
                $plugin->getDirectory() . '/classes/');
        }
//        ilCronManager::updateFromXML(
//            $plugin::PLUGIN_COMPONENT,
//            self::JOB_ID,
//            self::class,
//            $plugin->getDirectory() . '/classes/'
//        );
    }


    public static function uninstallAsyncJob(ilXapiProgressPlugin $plugin)
    {
        global $DIC;
        if (isset($DIC['cron.repository'])) {
            $DIC->cron()->repository()->unregisterJob($plugin::PLUGIN_COMPONENT, []);
        }
//        ilCronManager::clearFromXML($plugin::PLUGIN_COMPONENT, []);
    }
	
}
