<?php
/* Copyright (c) internetlehrer GmbH, Extended GPL, see LICENSE */

namespace ILIAS\Plugin\XapiProgress\Event\Services\Tracking;

/**
 * Class SendStatementsByQueueId
 *
 * @package ILIAS\Plugin\XapiProgress\Event
 *
 * @author  Uwe Kohnle <kohnle@internetlehrer-gmbh.de>
 * @author  Christian Stepper <stepper@internetlehrer-gmbh.de>
 */
class SendStatementsByQueueId
{
    public function __construct(array $queueIds)
    {
        // (cronjob will trigger the event sendAllStatements that init this class)
        new \ILIAS\Plugin\XapiProgress\Task\SendStatementsByQueueId($queueIds);
    }


}