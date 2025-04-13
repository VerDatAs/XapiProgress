<?php
/* Copyright (c) internetlehrer GmbH, Extended GPL, see LICENSE */

namespace ILIAS\Plugin\XapiProgress\Event\Services\Tracking;

/**
 * Class HandleQueueEntries
 *
 * @package ILIAS\Plugin\XapiProgress\Event
 *
 * @author  Uwe Kohnle <kohnle@internetlehrer-gmbh.de>
 * @author  Christian Stepper <stepper@internetlehrer-gmbh.de>
 */
class HandleQueueEntries
{
    public function __construct(int $queueId)
    {
        // (backgroundTask can trigger the event handleQueueEntries with the id of a queueEntry that contains a bucketId to delete all il_bt_-Table entries)
        new \ILIAS\Plugin\XapiProgress\Task\HandleQueueEntries($queueId);
    }


}