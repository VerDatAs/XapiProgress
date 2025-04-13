<?php

namespace ILIAS\Plugin\XapiProgress\Xapi\Statement;

interface XapiStatementInterface
{
    public function buildResult() : ?array;

    public function buildTimestamp() : string;

    public function buildActor() : array;

    public function buildVerb() : array;

    public function buildObject() : array;

    public function buildContext() : array;

    public function jsonSerialize() : array;
}