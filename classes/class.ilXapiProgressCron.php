<?php
/* Copyright (c) internetlehrer GmbH, Extended GPL, see LICENSE */

/**
 * Class ilXapiProgressCron
 *
 * @author      Uwe Kohnle <kohnle@internetlehrer-gmbh.de>
 * @author      Björn Heyser <info@bjoernheyser.de>
 */


require_once dirname(__DIR__) . "/vendor/autoload.php";

use \ILIAS\DI\Container;

class ilXapiProgressCron extends ilCronJob
{
	const JOB_ID = 'sendstatements';

    protected Container $dic;

	public function __construct()
	{
        global $DIC; /**@var Container $DIC */
        $this->dic = $DIC;
        $this->dic->logger()->root()->log(' init CronJob');
	}
	
	public function getId() : string
    {
		return self::JOB_ID;
	}

    public function getTitle() : string
    {
        return "XapiProgress";
    }

    public function getDescription() : string
    {
        return $this->dic->language()->txt("ui_uihk_xapip_cronjob_description");
    }

    public function getDefaultScheduleType() : int
    {
        return self::SCHEDULE_TYPE_IN_HOURS;
    }

    public function getDefaultScheduleValue(): int
    {
        return 1;
    }

    public function hasAutoActivation() : bool
    {
        return true;
    }

    public function hasFlexibleSchedule() : bool
    {
        return false;
    }

	public function run(): \ilCronJobResult
    {
        $this->dic->logger()->root()->log(' try run x');

		$cronResult = new ilCronJobResult();
        $cronResult->setStatus(ilCronJobResult::STATUS_NO_ACTION);

        try {
            $this->dic->event()->raise('Services/Tracking', 'sendAllStatements', [
                'obj_id' => 1,
                'ref_id' => 1,
                'usr_id' => $this->dic->user()->getId()
            ]);
            $cronResult->setStatus(ilCronJobResult::STATUS_OK);
            $this->dic->logger()->root()->log('RAISED sendAllStatements');
        } catch(Exception $e) {
            $cronResult->setStatus(ilCronJobResult::STATUS_FAIL);
            $this->dic->logger()->root()->dump($e);
        }

		return $cronResult;
	}

}
