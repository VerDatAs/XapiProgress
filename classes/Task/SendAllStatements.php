<?php
/* Copyright (c) internetlehrer GmbH, Extended GPL, see LICENSE */

namespace ILIAS\Plugin\XapiProgress\Task;

use ILIAS\DI\Container;
use ILIAS\Plugin\XapiProgress\Xapi\Request\XapiRequest;
use ilLoggerFactory;
use ilCmiXapiLrsType;

/**
 * Class SendAllStatements
 *
 * @package ILIAS\Plugin\XapiProgress\Task
 *
 * @author  Uwe Kohnle <kohnle@internetlehrer-gmbh.de>
 * @author  Christian Stepper <stepper@internetlehrer-gmbh.de>
 */
class SendAllStatements
{
    protected Container $dic;

    protected ?ilCmiXapiLrsType $lrsType;

    protected ?XapiRequest $lrsRequest;

    use \ILIAS\Plugin\XapiProgress\Model\DbXapiProgressQueue;

    public function __construct(int $queueId)
    {
        global $DIC; /**@var Container $DIC */

        $this->dic = $DIC;

        $this->setQueueIdAndLoadEntry($queueId);

        if($this->loadAllQueueEntriesForCronJob()) {
            $this->dic->logger()->root()->log('[CRON JOB TASK] ################## [CRON JOB TASK] n entries: ' . count($this->queueEntries));
            $this->dic->logger()->root()->log('[CRON JOB TASK] ################## [CRON JOB TASK] run() sending all failed statements ');
            $this->run();
            #$this->dic->logger()->root()->log('[CRON JOB TASK] ################## [CRON JOB TASK] deleteQueueEntryById(cronJobQueueId) ');
            #$this->deleteQueueEntryById();
        }
        $this->dic->logger()->root()->log('[CRON JOB TASK] ################## [CRON JOB TASK] deleteQueueEntryById(cronJobQueueId) ');
        $this->deleteQueueEntryById();
    }


    public function run() : bool
    {
        $this->lrsType = $this->lrsType ?? \ilXapiProgressPlugin::getLrsType();

        $this->lrsRequest = $this->lrsRequest ?? new XapiRequest(
            $this->lrsType->getLrsEndpointStatementsLink(),
            $this->lrsType->getLrsKey(),
            $this->lrsType->getLrsSecret()
        );

        foreach ($this->queueEntries as $queueId => $entry) {

            usleep(10);

            $this->dic->logger()->root()->log('[CRON JOB TASK] ################## [CRON JOB TASK]');
            $this->dic->logger()->root()->log('[CRON JOB TASK] ################## [CRON JOB TASK]');
            $this->dic->logger()->root()->log('[CRON JOB TASK] ################## [CRON JOB TASK]');
            $this->dic->logger()->root()->log('[CRON JOB TASK] ################## [CRON JOB TASK]');
            $this->dic->logger()->root()->log('[CRON JOB TASK] ################## [CRON JOB TASK]');
            $this->dic->logger()->root()->log('[CRON JOB TASK] ################## [CRON JOB TASK]');
            $this->dic->logger()->root()->log('[CRON JOB TASK] ################## [CRON JOB TASK]');
            $this->dic->logger()->root()->log('[CRON JOB TASK] ################## [CRON JOB TASK] sendStatement(' . $entry['queue_id'] . ') ');
            $this->dic->logger()->root()->dump($entry);


            if($this->lrsRequest->sendStatement($entry['statement'])) {

                #$this->dic->logger()->root()->log('[CRON JOB TASK] ################## [CRON JOB TASK] deleteQueueEntryById(stateFaildQueueId) ');
                #$this->deleteQueueEntryById($queueId);

                $this->dic->logger()->root()->log('[CRON JOB TASK] ################## [CRON JOB TASK] updateQueueWithStateDeletableById(stateFaildQueueId) ');
                $this->updateQueueWithStateDeletableById($entry['queue_id']);

            } else {

                usleep(10);

                $newState = (int)$entry['state'] + 1;
                $newFailedDate = date('Y-m-d H:i:s');

                $this->dic->logger()->root()->log('[CRON JOB TASK] ################## [CRON JOB TASK] state++ updateQueueEntryWithStateAndFailedDateById(' . $entry['queue_id'] . ') ');
                $this->updateQueueEntryWithStateAndFailedDateById((int)$entry['queue_id'], $newState, $newFailedDate);

            }

        }

        $this->dic->logger()->root()->log('[CRON JOB TASK] ################## [CRON JOB TASK] deleteQueueEntryById(' . $this->queueId . ') ');
        $this->deleteQueueEntryById();

        return true;

    }

}