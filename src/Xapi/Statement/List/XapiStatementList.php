<?php
namespace ILIAS\Plugin\XapiProgress\Xapi\Statement;
/* Copyright (c) internetlehrer GmbH, Extended GPL, see LICENSE */

use Exception;
use JsonSerializable;


/**
 * Class XapiStatementList
 *
 * @author      Uwe Kohnle <kohnle@internetlehrer-gmbh.de>
 * @author      Björn Heyser <info@bjoernheyser.de>
 */
class XapiStatementList implements JsonSerializable
{
	protected array $statements = [];
	
	public function addStatement(XapiStatement $statement)
	{
		$this->statements[] = $statement;
	}
	
	public function getStatements(): array
	{
		return $this->statements;
	}

    /**
     * @throws Exception
     */
	public function getPostBody(): string
    {
		if(DEVMODE)
		{
			return json_encode($this->jsonSerialize(), JSON_PRETTY_PRINT);
		}
		
		return json_encode($this->jsonSerialize());
	}

    /**
     * @throws Exception
     */
	public function jsonSerialize(): array
    {
		$jsonSerializable = [];
		
		foreach($this->statements as $statement)
		{

			$jsonSerializable[] = $statement->jsonSerialize();

		}

		return $jsonSerializable;
	}

}
