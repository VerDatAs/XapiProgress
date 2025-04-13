<?php
/* Copyright (c) internetlehrer GmbH, Extended GPL, see LICENSE */

namespace ILIAS\Plugin\XapiProgress\Event\Services\Tracking;

/**
 * Class DeleteAllBtEntriesByBucketId
 * @package ILIAS\Plugin\XapiProgress\Event
 * @author  Uwe Kohnle <kohnle@internetlehrer-gmbh.de>
 * @author  Christian Stepper <stepper@internetlehrer-gmbh.de>
 */
class DeleteAllBtEntriesByBucketId
{
    public function __construct(int $queueId)
    {
        // (backgroundTask will trigger the event deleteAllBtEntriesByBucketId that init this class)
        new \ILIAS\Plugin\XapiProgress\Task\DeleteAllBtEntriesByBucketId($queueId);
    }

}