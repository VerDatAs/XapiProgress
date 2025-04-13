<?php
/* Copyright (c) internetlehrer GmbH, Extended GPL, see LICENSE */

namespace ILIAS\Plugin\XapiProgress\Xapi\Statement;

use Closure;
use Exception;
use ILIAS\DI\Container;
use ILIAS\Plugin\XapiProgress\Statement as ilgStmt;

class XapiStatementBuilder
{
    protected Container $dic;

    private static XapiStatementBuilder $instance;

    protected string $event;

    private XapiStatementList $statementsList;

    private array $xapiStatements = [];

    public function __construct(string $event = '')
    {
        global $DIC; /**@var Container $DIC */

        $this->dic = $DIC;

        $this->event = $event;

        $this->statementsList = new XapiStatementList();

    }

    public static function getInstance(string $event = '') : self
    {
        return $instance ?? $instance = new self($event);
    }



    /**
     * @throws Exception
     */
    public function buildPostBody(array $param) : string
    {
        $paramList = (!count($param[0] ?? [])) ? [$param] : $param;

        foreach ($paramList as $item) {

            $this->createXapiStatementAndAddToStatementList($item);

        }

        #$statementList = $this->createStatementList($param);

        #$this->dic->logger()->root()->dump($statementList);

        return $this->statementsList->getPostBody();

    }


    /**
     * @param array $param
     * @return XapiStatementBuilder
     */
    public function createXapiStatementAndAddToStatementList(array $param) : self # \ILIAS\Plugin\XapiProgress\Xapi\Statement\XapiStatementList
    {

        $eventBasedStatementClass = $this->event ? 'ILIAS\Plugin\XapiProgress\Statement\\' : 'ILIAS\Plugin\XapiProgress\Xapi\Statement\\';
        $eventBasedStatementClass .= $this->event ? ucfirst($this->event) : 'XapiStatement';

        #echo $eventBasedStatementClass; exit;

        /** @var ilgStmt\AfterChangeEvent|ilgStmt\ReadCounterChange|ilgStmt\TrackIliasLearningModulePageAccess|ilgStmt\UpdateStatus|XapiStatement $eventBasedStatementClass */
        $statement = new $eventBasedStatementClass(
            \ilXapiProgressPlugin::getLrsType(),
            $param,
            $this->event
        );

        $this->addStatementToStatementsList($statement);

        return $this;
    }

    public function addStatementToStatementsList(XapiStatement $statement) : self
    {
        $this->statementsList->addStatement($statement);

        $this->xapiStatements[] = $statement;

        return $this;
    }

    public function getXapiStatements() : array
    {

        return $this->xapiStatements;

    }

    public function getStatementsList() : XapiStatementList
    {

        return $this->statementsList;

    }

}