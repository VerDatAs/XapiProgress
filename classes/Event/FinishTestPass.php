<?php
/* Copyright (c) internetlehrer GmbH, Extended GPL, see LICENSE */

namespace ILIAS\Plugin\XapiProgress\Event\Modules\Test;

use ILIAS\Plugin\XapiProgress\Event\EventHandler;

/**
 * Class FinishTestPass
 *
 * @package ILIAS\Plugin\XapiProgress\Event
 *
 * @author  Uwe Kohnle <kohnle@internetlehrer-gmbh.de>
 * @author  Christian Stepper <stepper@internetlehrer-gmbh.de>
 */
class FinishTestPass extends EventHandler
{
    public function __construct(int $queueId)
    {

        parent::__construct($queueId);

    }
}