<?php

/**
 * Class ilXapiProgressRequest
 *
 * @author  Stefan Schneider <schneider@hrz.uni-marburg.de>
 * @version $Id:
 *
 */

class ilXapiProgressRequest extends ilCmiXapiAbstractRequest {

	public function __construct(string $basicAuth)
	{
		parent::__construct($basicAuth);
	}

	/**
     * @return string $reportResponse
     */
    public function queryReport(string $url): string
    {
        $reportResponse = $this->sendRequest($url);
        return $reportResponse;
    }
}

