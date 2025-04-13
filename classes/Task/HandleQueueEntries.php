<?php
/* Copyright (c) internetlehrer GmbH, Extended GPL, see LICENSE */

namespace ILIAS\Plugin\XapiProgress\Task;

use ILIAS\DI\Container;
use ILIAS\Plugin\XapiProgress\Xapi\Request\XapiRequest;
use ilLoggerFactory;
use ilCmiXapiLrsType;

/**
 * Class HandleQueueEntries
 *
 * @package ILIAS\Plugin\XapiProgress\Task
 *
 * @author  Uwe Kohnle <kohnle@internetlehrer-gmbh.de>
 * @author  Christian Stepper <stepper@internetlehrer-gmbh.de>
 */
class HandleQueueEntries
{
    /**
     * @var Container
     */
    protected Container $dic;

    protected ?ilCmiXapiLrsType $lrsType;

    protected ?XapiRequest $lrsRequest;

    use \ILIAS\Plugin\XapiProgress\Model\DbXapiProgressQueue;

    public function __construct(int $queueId)
    {
        global $DIC; /**@var Container $DIC */

        $this->dic = $DIC;

        $this->setQueueIdAndLoadEntry($queueId);

        $this->bucketId = json_decode($this->parameter, 1)['bucket_id'] ?? $this->bucketId;

        #if(
            $this->getInitializedEntriesAndSendStatement();
    #) {


        #}

    }

    private function getInitializedEntriesAndSendStatement(?int $excludeQueueId = null) : bool
    {
        $this->lrsType = $this->lrsType ?? \ilXapiProgressPlugin::getLrsType();

        $this->lrsRequest = $this->lrsRequest ?? new XapiRequest(
                $this->lrsType->getLrsEndpointStatementsLink(),
                $this->lrsType->getLrsKey(),
                $this->lrsType->getLrsSecret()
            );

        $statements = $this->getQueueEntriesWithStateInitialized(true, true);

        foreach ($statements as $queueId => $statement) {

            #usleep(10);

            if($this->lrsRequest->sendStatement($statement)) {

                $this->deleteQueueEntryById($queueId);
                #$this->updateQueueWithStateDeletableById($queueId);

            } else {

                usleep(10);

                $newState = (int)self::$STATE_CRON_EXEC_1;
                $newFailedDate = date('Y-m-d H:i:s');

                #$this->dic->logger()->root()->log('[CRON JOB TASK] ################## [CRON JOB TASK] state++ updateQueueEntryWithStateAndFailedDateById(' . $entry['queue_id'] . ') ');
                $this->updateQueueEntryWithStateAndFailedDateById((int)$queueId, $newState, $newFailedDate);

            }

        }

        return true;
    }

}