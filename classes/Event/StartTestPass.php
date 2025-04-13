<?php
/* Copyright (c) internetlehrer GmbH, Extended GPL, see LICENSE */

namespace ILIAS\Plugin\XapiProgress\Event\Modules\Test;

use ILIAS\Plugin\XapiProgress\Event\EventHandler;

/**
 * Class StartTestPass
 *
 * @package ILIAS\Plugin\XapiProgress\Event
 *
 * @author  Uwe Kohnle <kohnle@internetlehrer-gmbh.de>
 * @author  Christian Stepper <stepper@internetlehrer-gmbh.de>
 */
class StartTestPass extends EventHandler
{
    public function __construct(int $queueId)
    {
        #$this->event = 'startTestPass';
        // add code

        parent::__construct($queueId);

    }
}