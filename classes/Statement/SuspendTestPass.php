<?php

namespace ILIAS\Plugin\XapiProgress\Statement;


use ilCmiXapiDateTime;
use ilCmiXapiLrsType;
use ILIAS\Plugin\XapiProgress\Xapi\Statement\XapiStatement;
use ilObject;
use ilObjUser;


class SuspendTestPass extends TestPass
{
    public function __construct(ilCmiXapiLrsType $lrsType, array $eventParam = [])
    {

        $eventParam['event'] = 'suspendTestPass';

        parent::__construct($lrsType, $eventParam);
    }

}