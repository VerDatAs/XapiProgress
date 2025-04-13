<?php

/** @noRector */

chdir("../../../../../../../");

require_once("Services/Init/classes/class.ilInitialisation.php");
ilInitialisation::initILIAS();

global $DIC;

$logger = $DIC->logger()->root();
$logger->debug('############### endpointH5P Request');

$XapiProgress = new ilXapiProgressPlugin();

$postBody = $DIC->http()->request()->getParsedBody();

$receivedVerb = $postBody['verb']['display'] ?? [uniqid()];

$config = new ilXapiProgressConfig();
$trackVerbs = $config->getH5pVerbs();

if(in_array(array_pop($receivedVerb), $trackVerbs)) {

    $urlH5PModule = parse_url($postBody['UrlH5PModule'], PHP_URL_QUERY);

    /** @var array $queryParam */
    parse_str($urlH5PModule, $queryParam);

    $gotoLink = null;

    if ($target = $queryParam['target'] ?? null) {

        $ids = explode('_', $target);

        $queryParam['ref_id'] = array_pop($ids);

        $queryParam['gotoLink'] = $postBody['UrlH5PModule'];

    }

    $logger->debug('############### endpointH5P $postBody');
    $logger->debug(print_r($postBody,true));

    $hasReadAccess = $DIC->access()->checkAccessOfUser(
        $DIC->user()->getId(),
        'read', 'read',
        (int) $queryParam['ref_id']
    );

    if ($hasReadAccess && $refId = (int) $queryParam['ref_id'] ?? 0) {

        $handleEventParam = [
            'obj_id' => \ilObject::_lookupObjectId($refId),
            'ref_id' => $refId,
            'usr_id' => $DIC->user()->getId(),
            'event' => 'H5P'
        ];

        $postBody['queryParamH5PModule'] = $queryParam;

        unset($postBody['UrlH5PModule']);

        $handleEventParam['uiEventData'] = $postBody;

        $logger->debug('############### endpointH5P $handleEventParam');
        $logger->debug(print_r($handleEventParam,true));

        $XapiProgress->handleEvent('Services/Tracking', 'H5P', $handleEventParam);

    }
}
