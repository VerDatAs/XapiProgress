<?php
namespace ILIAS\Plugin\XapiProgress\Event\Services\Tracking;
/**
 * Class H5P (former UiEvent)
 *
 * @author  Uwe Kohnle <kohnle@internetlehrer-gmbh.de>
 * @author  Christian Stepper <stepper@internetlehrer-gmbh.de>
 */


use ILIAS\DI\Container;

use ILIAS\Plugin\XapiProgress\Event\EventHandler;

use ILIAS\Plugin\XapiProgress\Model\DbXapiProgressQueue;


class H5P extends EventHandler
{
    use DbXapiProgressQueue;

    public function __construct(int $queueId)
    {
        global $DIC; /** @var Container $DIC */

        $this->dic = $DIC;

        $this->event = 'H5P';

        #if($this->mergeStatement($queueId)) {

            parent::__construct($queueId);

        #}

    }

    private function mergeStatement(int $queueId) : bool
    {

        $entry = $this->loadQueueEntry($queueId, false);

        $statement = json_decode($entry['statement'], 1)[0];

        $parameter = json_decode($entry['parameter'], 1);

        $fromEvent = $parameter['uiEventData'];

        $statement['verb'] = $fromEvent['verb'];

        $this->updateQueueEntryWithStatementById(json_encode($statement), $queueId);

        return true;
    }
}