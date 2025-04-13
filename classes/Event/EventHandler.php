<?php
/* Copyright (c) internetlehrer GmbH, Extended GPL, see LICENSE */

namespace ILIAS\Plugin\XapiProgress\Event;

use ILIAS\DI\Container;
use ILIAS\BackgroundTasks\Implementation\Bucket\BasicBucket;
use ILIAS\BackgroundTasks\Implementation\Tasks\AbstractJob;
use ILIAS\Plugin\XapiProgress\Model\DbXapiProgressQueue;
use ILIAS\Plugin\XapiProgress\Task\TaskManager;
use ILIAS\Plugin\XapiProgress\Task\SendSingleStatement;
use ILIAS\Plugin\XapiProgress\Task\SendAllStatements;
use ILIAS\BackgroundTasks\Implementation\TaskManager\BasicTaskManager;

/**
 * Class EventHandler
 *
 * @package ILIAS\Plugin\XapiProgress\Event
 *
 * @author  Uwe Kohnle <kohnle@internetlehrer-gmbh.de>
* @author  Christian Stepper <stepper@internetlehrer-gmbh.de>
 */

class EventHandler
{
    const RAISE_COMP_PLUGIN = 'Services/Tracking';

    protected array $param = [];

    public string $tasksNs = 'ILIAS\Plugin\XapiProgress\Task\\';

    public string $eventNs = 'ILIAS\Plugin\XapiProgress\Event\\';

    public string $task;

//    public ?string $event = null;

    public int $queueId;

    public Container $dic;

    use DbXapiProgressQueue;


    /**
     * @throws \Exception
     */
    public function __construct(int $queueId)
    {

        global $DIC; /** @var Container $DIC */

        $this->dic = $DIC;

        $this->queueId = $queueId;

        $this->event = $this->event ?? $this->getEventFromQueueEntryById($this->queueId);

        $this->task = TaskManager::getEventTask()[$this->event];

        $this->executeBackgroundTask();
    }

//    public static function getEvent() : array
//    {
//        return array_keys(
//            TaskManager::getEventTask()
//        );
//    }

    /**
     * @throws \Exception
     */
    public function executeBackgroundTask() : void
    {
        $taskFactory = $this->dic->backgroundTasks()->taskFactory();

        $taskManager = $this->getTaskManager();

        $bucket = new BasicBucket();

        // create task for the job to build statements and request the lrs
        /** @var AbstractJob $task */
        $task = $taskFactory->createTask($this->tasksNs . $this->task, [$this->queueId]);

        // schedule the task
        $bucket->setTask($task);

        // trigger async task execution
        $taskManager->run($bucket);
    }

    /**
     * @return \ILIAS\Plugin\XapiProgress\Task\TaskManager|\ILIAS\BackgroundTasks\TaskManager
     *
     */
    public function getTaskManager()
    {
        $taskMan = new TaskManager($this->dic->backgroundTasks()->persistence()); # $this->dic->backgroundTasks()->persistence()

        $taskMan->setQueueId($this->queueId);

        return $taskMan;
    }
}