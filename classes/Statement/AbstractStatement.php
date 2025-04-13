<?php

namespace ILIAS\Plugin\XapiProgress\Statement;


use ilCmiXapiLrsType;
use ILIAS\Plugin\XapiProgress\Xapi\Statement\XapiStatement;


abstract class AbstractStatement extends XapiStatement
{
    public function __construct(
        ilCmiXapiLrsType $lrsType,
        array $eventParam = []
    )
    {
        parent::__construct($lrsType, $eventParam);
    }

}