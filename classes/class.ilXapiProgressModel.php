<?php
use function Sabre\Uri\split;

/**
 * Class ilXapiProgressModel: Each report must implement its own model which extends this class.
 *
 */
abstract class ilXapiProgressModel {

	protected ilDBInterface $db;
	protected ilXapiProgressPlugin $pl;
	protected ilObjUser $user;
	protected ilAccessHandler $access;
	protected ilLogger $log;

	public function __construct() {
		global $DIC;
		$this->db = $DIC->database();
		$this->pl = ilXapiProgressPlugin::getInstance();
		$this->user = $DIC->user();
		$this->access = $DIC->access();
		$this->log = ilLoggerFactory::getRootLogger();
	}


	abstract public function getSearchData(array $filters);


	abstract public function getReportData(array $ids, array $filters);


	/**
	 * Return all the ref-ids (of Categories) where the current user can administrate users
	 */
	protected function getRefIdsWhereUserCanAdministrateUsers() : array
    {
		$sql = 'SELECT DISTINCT time_limit_owner FROM usr_data';
		$set = $this->db->query($sql);
		$refIds = array();
		while ($rec = $this->db->fetchAssoc($set)) {
			$refIds[] = $rec['time_limit_owner'];
		}
		foreach ($refIds as $k => $refId) {
			if (!$this->access->checkAccess('read_users', '', $refId)) {
				unset($refIds[$k]);
			}
		}

		return $refIds;
	}


	/**
	 * Build records from SQL Query string
	 */
	protected function buildRecords(string $sql) : array
    {
        $sql = preg_replace('/[ ]{2,}|[\t]|[\n]/', ' ', trim($sql));
		$result = $this->db->query($sql);
		$return = array();
		while ($rec = $this->db->fetchAssoc($result)) {
			$return[] = $rec;
		}

		return $return;
	}


	/**
	 * for_sub_objects - flag if it should say object_grade or just grade
	 */
	protected function getLPMark(int $obj_id, int $usr_id, array &$v, bool $for_sub_objects = false):void
    {
		$result = $this->db->queryF("SELECT mark,u_comment FROM ut_lp_marks WHERE obj_id=%s AND usr_id=%s", [ "integer", "integer" ], [
			$obj_id,
			$usr_id
		])->fetchAssoc();

		$prefix = $for_sub_objects ? 'object_' : '';

		if ($result) {
			$v[$prefix . "grade"] = $result["mark"];
			$v[$prefix . "comments"] = $result["u_comment"];
		} else {
			$v[$prefix . "grade"] = "";
			$v[$prefix . "comments"] = "";
		}
	}

	protected function buildTempTableWithUserAssignments(string $table_name) : void
    {
		$q = "DROP TABLE IF EXISTS $table_name";
		$this->db->manipulate($q);

		$q = "CREATE TEMPORARY TABLE IF NOT EXISTS $table_name AS (
				SELECT DISTINCT object_reference.ref_id AS ref_id, rbac_ua.usr_id AS user_id, orgu_path_storage.path AS path
					FROM rbac_ua
					JOIN  rbac_fa ON rbac_fa.rol_id = rbac_ua.rol_id
					JOIN object_reference ON rbac_fa.parent = object_reference.ref_id
					JOIN object_data ON object_data.obj_id = object_reference.obj_id
					JOIN orgu_path_storage ON orgu_path_storage.ref_id = object_reference.ref_id
				WHERE object_data.type = 'orgu' AND object_reference.deleted IS NULL
			);";
		$this->db->manipulate($q);
	}


    public function refreshDataFromLRS() : void
    {
		global $DIC;
		$limit = (string) ilXapiProgressConfig::LRS_STATEMENT_LIMIT;
		$lrs = ilXapiProgressPlugin::getLrsType();
//            $lrs = new ilCmiXapiLrsType($lrsTypeId);
		$crsRefId = $DIC->http()->wrapper()->query()->retrieve('ref_id',$DIC->refinery()->kindlyTo()->int());
		$activityId = urlencode($this->getActivityIdForRefId($crsRefId));
		$statementUrl = $lrs->getLrsEndpointStatementsLink();
		$basicAuth = $lrs->getBasicAuth();
        $exceptStatementIds = [];
        $checkAr = $this->getLastStatements($crsRefId);
		$until = $checkAr["until"];

//        die($until);
		$this->log->debug("data from LRS until: " . $until);
		// get all data

//$lastStored='2023-11-23T22:01:05.496Z';

		// asc not desc
		if ($until == "") {
			$url = "{$statementUrl}?activity={$activityId}&related_activities=true&ascending=true&limit={$limit}";
		} else { // get all since lastStored
			$url = "{$statementUrl}?activity={$activityId}&related_activities=true&ascending=true&limit={$limit}&since=".urlencode($until);
            $exceptStatementIds = $checkAr["exceptStatementIds"];//explode(" ",$checkAr["exceptStatementIds"]);
		}
//        $this->log->dump($exceptStatementIds);
		$this->log->debug($url);

		$req = new ilXapiProgressRequest($basicAuth);
		$body = $req->queryReport($url);
//        $this->log->dump($body);
		$this->getFullData($body, $statementUrl, $crsRefId, $lrs->getTypeId(), $req, $exceptStatementIds);
		// write to file
		//file_put_contents("{$this->pl->getDirectory()}/data/{$crsRefId}.js","const statements={$this->getData($crsRefId)};");
    }

	private function getFullData(string $body, string $statementUrl, int $crsRefId, int $lrsTypeId, $req, array $exceptStatementIds): void {
		try {
			$obj = json_decode($body, false, 512, JSON_THROW_ON_ERROR);
			if (count($obj->statements) == 0) {
				$this->log->debug("no statements found");
				return;
			}
		}
		catch (\JsonException $exception) {
			$this->log->error($exception->getMessage());
			$this->log->error($body);
			return;
		}

//		$lastStored = $obj->statements[0]->stored;
//		$lastStatementDateTime = ilCmiXapiDateTime::fromXapiTimestamp($lastStored)->get(IL_CAL_DATETIME);
//		$ret = preg_match('/(\.\d+)Z$/', $lastStored, $matches);
////die($lastStored . ' m:'.$matches[1].' r'.$ret);
//		if ($ret == 1) {
//			$lastStatementDateTime .= $matches[1];
//		}
        $lastStatementDateTime = $obj->statements[count($obj->statements)-1]->timestamp;

        if (count($exceptStatementIds) > 0) {
            for ($i=0; $i<count($obj->statements); $i++) {
                if(in_array($obj->statements[$i]->id, $exceptStatementIds)){
                    unset($obj->statements[$i]);
                }
            }
        }

        if (count($obj->statements) > 0) {
            $this->storeStatements($lastStatementDateTime, $crsRefId, $lrsTypeId, $obj);
        }

		if (!empty($obj->more)) {
			$more = explode("?",$obj->more);
			if (count($more) != 2) {
				$this->log->error("wrong format: {$obj->more}");
				return;
			}
			$url = "{$statementUrl}?{$more[1]}";
			$body = $req->queryReport($url);
			$this->getFullData($body, $statementUrl, $crsRefId, $lrsTypeId, $req, $exceptStatementIds);
		}
	}
	
	private function getActivityIdForRefId(int $refId): string
    {
		$objId = ilObject::_lookupObjId($refId);
		$objType = ilObject::_lookupType($objId);
		return ilLink::_getLink($refId, $objType) . "&obj_id_lrs=" . $objId;
	}

	private function storeStatements(string $lastStatementDateTime, int $crsRefId, int $lrsTypeId, $obj): void
	{
		global $DIC;
        $ilDB = $DIC->database();
		$id = $ilDB->nextId('xapip_data');
        $ilDB->insert('xapip_data', array(
            'id' => array('integer', $id),
			'crs_id' => array('integer', $crsRefId),
            'lrs_type_id' => array('integer', $lrsTypeId),
            'until' => array('string', $lastStatementDateTime),
            'data' => array('text', json_encode($obj->statements))
        ));
	}

	private function getLastStatements(int $crsId): array
	{
		global $DIC;
        $checkAr = [];
        $until = "";
        $exceptStatementIds = [];
        $ilDB = $DIC->database();
		$query = "SELECT until,data FROM xapip_data WHERE crs_id=" . $ilDB->quote($crsId, "integer") . " ORDER by id DESC LIMIT 1";
        $result = $ilDB->query($query);
        $row = $ilDB->fetchAssoc($result);
		if ($row != null) {
            $until = (string) $row['until'];
            $statements = json_decode((string) $row['data'], false, 512, 0);

            if(is_array($statements)){
                foreach ($statements as $statement) {
                    if ($statement->timestamp == $until) {
                        $exceptStatementIds[] = (string) $statement->id;
                    }
                }
            }
		}
        $checkAr['until'] = $until;
        $checkAr['exceptStatementIds'] = $exceptStatementIds;
        return $checkAr;
	}

	private function normalizeData($dataRow) : void
    {
		$ret = array();
		try {
			$obj = json_decode($dataRow, false, 512, JSON_THROW_ON_ERROR);
			if (count($obj->statements) == 0) {
				$this->log->debug("no statments found");
				return;
			}
		}
		catch (\JsonException $exception) {
			$this->log->error($exception->getMessage());
			$this->log->error($dataRow);
			return;
		}
		$this->log->debug(var_export($obj,true));
	}
	
	public static function getData(int $crsId): string
	{
		global $DIC;
        $ilDB = $DIC->database();
		$query = "SELECT data FROM xapip_data WHERE crs_id=" . $crsId . " ORDER by id DESC";
        $result = $ilDB->query($query);
		$data = "";
        while ($row = $ilDB->fetchAssoc($result)) {
			//$normalizedData = $this->normalizeData($row["data"]);
			$d = ltrim($row["data"],"[");
			$d = rtrim($d,"]");
			$data .= $d.",";
		};
		$data = rtrim($data,",");
		$ret = "[" . $data . "]";
		return $ret;
	}

}
