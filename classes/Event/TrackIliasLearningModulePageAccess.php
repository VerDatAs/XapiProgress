<?php
/* Copyright (c) internetlehrer GmbH, Extended GPL, see LICENSE */

namespace ILIAS\Plugin\XapiProgress\Event\Services\Tracking;

use ILIAS\Plugin\XapiProgress\Event\EventHandler;

/**
 * Class TrackIliasLearningModulePageAccess
 *
 * @package ILIAS\Plugin\XapiProgress\Event
 *
 * @author  Uwe Kohnle <kohnle@internetlehrer-gmbh.de>
 * @author  Christian Stepper <stepper@internetlehrer-gmbh.de>
 */
class TrackIliasLearningModulePageAccess extends EventHandler
{
    public function __construct(int $queueId)
    {
        $this->event = 'trackIliasLearningModulePageAccess'; # lcfirst(__CLASS__);
        // add code

        parent::__construct($queueId);

    }
}