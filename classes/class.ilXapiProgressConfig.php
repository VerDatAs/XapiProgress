<?php

/**
 * ilXapiProgressConfig
 */
class ilXapiProgressConfig
{
    const LRS_STATEMENT_LIMIT = 100;
    const RESTRICTED_NONE = 0;
    const RESTRICTED_BY_LOCAL_READABILITY = 1;
    const RESTRICTED_BY_ORG_UNITS = 2;
    const DELETE_NEVER = 0;
    const DELETE_OBJ_DELETED = 1;
    const DELETE_OBJ_TRASH = 2;
    const PREFIX = "uihk_xapip";

    private array $possibleH5pVerbs = ['answered','asked','attempted','attended','commented','completed','exited','experienced','failed','initialized','interacted','launched','mastered','passed','preferred','progressed','responded','resumed','scored','suspended','terminated','voided'];
//    possible, but not supported
//    'imported','registered','shared',
//    not supported custom verbs
//    private array $customH5pVerbs = ['downloaded','copied','accessed-reuse','accessed-embed','accessed-copyright'];

    private ILIAS\DI\Container $dic;
    private ilDBInterface $db;
    private ?int $lrsTypeId = null;
    private bool $onlyCourse = false;
    private array $courses = [];
    private bool $needConsent = false;
    private int $dataDelete = self::DELETE_NEVER;
    private int $restrictedUserAccess = self::RESTRICTED_BY_LOCAL_READABILITY;
    private array $events = [];
    private array $h5pVerbs = [];
    private string $lastUpdateBy = '';




    public function __construct()
    {
        global $DIC;
        $this->dic = $DIC;
        $this->db = $this->dic->database();
        $this->read();
    }

    protected function read():void
    {
//        if ($this->db->tableExists('xapip_settings')) {
            $res = $this->db->query("SELECT * FROM xapip_settings ORDER BY id DESC LIMIT 1");
            while ($result = $this->db->fetchAssoc($res)) {
                $this->setLrsTypeId((int) $result['lrs_type_id']);
                $this->setOnlyCourse((bool) $result['only_course']);
                $this->setCoursesFromString((string) $result['courses']);
                $this->setNeedConsent((bool) $result['need_consent']);
                $this->setDataDelete((int) $result['data_delete']);
                $this->setRestrictedUserAccess((int) $result['restricted_user_access']);
                $this->setEventsFromString((string) $result['events']);
                $this->setH5pVerbsFromString((string) $result['h5p_verbs']);
                $this->setLastUpdateBy((int) $result['usr_id'], (string) $result['updated']);
            }
//        }
    }

    public function save(): void {
        $updated = new DateTime('now', new DateTimeZone('GMT'));
        $values = [
            'id' => ['integer', $this->db->nextId('xapip_settings')],
            'lrs_type_id' => ['integer', $this->getLrsTypeId()],
            'only_course' => ['integer', (int) $this->isOnlyCourse()],
            'courses' => ['string', implode(',', $this->getCourses())],
            'need_consent' => ['integer', (int) $this->isNeedConsent()],
            'data_delete' => ['integer', $this->getDataDelete()],
            'restricted_user_access' => ['integer', $this->getRestrictedUserAccess()],
            'events' => ['string', implode(',', $this->getEvents())],
            'h5p_verbs' => ['string', implode(',', $this->getH5pVerbs())],
            'usr_id' => ['integer', $this->dic->user()->getId()],
            'updated' => ['timestamp', date('Y-m-d H:i:s', time())]
        ];
        $this->db->insert('xapip_settings',$values);
    }

    public static function getValue(string $key) :?string {
        global $DIC;
        return $DIC->settings()->get(self::PREFIX. "_" . $key);
    }

    public static function setValue(string $key, string $value) : void
    {
        global $DIC;
        $DIC->settings()->set(self::PREFIX. "_" . $key, $value);
    }

    public static function getExtendedNames() : bool
    {
        return false;
    }


    public function getLrsTypeId() : ?int
    {
        return $this->lrsTypeId;
    }

    public function setLrsTypeId(int $lrsTypeId) : void
    {
        $this->lrsTypeId = $lrsTypeId;
    }

    public function isOnlyCourse() : bool
    {
        return $this->onlyCourse;
    }

    public function setOnlyCourse(bool $onlyCourse) : void
    {
        $this->onlyCourse = $onlyCourse;
    }

    public function getCourses() : array
    {
        return $this->courses;
    }

    public function setCourses(array $courses) : void
    {
        $this->courses = $courses;
    }

    public function setCoursesFromString(string $sCourses) : void
    {
        $courses = [];
        if ($sCourses != '') {
            $courses = explode(',', $sCourses);
            for ($i = 0; $i < count($courses); $i++) {
                $courses[$i] = trim($courses[$i]);
            }
        }
        $this->courses = $courses;
    }

    public function isNeedConsent() : bool
    {
        return $this->needConsent;
    }

    public function setNeedConsent(bool $needConsent) : void
    {
        $this->needConsent = $needConsent;
    }

    public function getDataDelete() : int
    {
        return $this->dataDelete;
    }

    public function setDataDelete(int $dataDelete) : void
    {
        $this->dataDelete = $dataDelete;
    }

    public function getRestrictedUserAccess() : int
    {
        return $this->restrictedUserAccess;
    }

    public function setRestrictedUserAccess(int $restrictedUserAccess) : void
    {
        $this->restrictedUserAccess = $restrictedUserAccess;
    }

    public function getEvents() : array
    {
        return $this->events;
    }

    public function setEvents(array $events) : void
    {
        $this->events = $events;
    }

    public function setEventsFromString(string $sEvents) : void
    {
        $events = [];
        if ($sEvents != '') {
            $events = explode(',', $sEvents); //ToDo Check ,
            for ($i = 0; $i < count($events); $i++) {
                $events[$i] = trim($events[$i]);
            }
        }
        $this->events = $events;
    }

    public function getPossibleH5pVerbs() : array
    {
        return $this->possibleH5pVerbs;
    }

    public function getH5pVerbs() : array
    {
        return $this->h5pVerbs;
    }

    public function setH5pVerbs(array $h5pVerbs) : void
    {
        $this->h5pVerbs = $h5pVerbs;
    }

    public function setH5pVerbsFromString(string $sh5pVerbs) : void
    {
        $h5pVerbs = [];
        if ($sh5pVerbs != '') {
            $h5pVerbs = explode(',', $sh5pVerbs); //ToDo Check ,
            for ($i = 0; $i < count($h5pVerbs); $i++) {
                $h5pVerbs[$i] = trim($h5pVerbs[$i]);
            }
        }
        $this->h5pVerbs = $h5pVerbs;
    }

    public function getLastUpdateBy() : string
    {
        return $this->lastUpdateBy;
    }

    public function setLastUpdateBy(int $user_id, string $updated) : void
    {
        $user = new ilObjUser($user_id);
        $lastUpdateBy = $this->dic->user()->getFullname();
        $lastUpdateBy .= ' (' . $updated  . ')';
        $this->lastUpdateBy = $lastUpdateBy;
    }

    public function getPossibleEvents() : array
    {
        $jsonFileContent =  $this->dic->refinery()->to()->string()->transform(
            file_get_contents( dirname(__DIR__, 1) . '/plugin.ini.json')
        );
        $tasks = json_decode($jsonFileContent, true);
        return $tasks['eventTask'] ?? [];
    }

    public function getConsentForUser(int $usrId, int $refId): bool
    {
        $res = $this->db->queryF("SELECT * FROM xapip_consent_log WHERE usr_id=%s AND ref_id=%s ORDER BY log_date DESC LIMIT 1",
            ["integer", "integer"],
            [$usrId, $refId]
        );
        while ($result = $this->db->fetchAssoc($res)) {
            if ($result['status'] == 1) return true;
        }
        return false;
    }

    public function setConsentForUser(int $usrId, int $refId, bool $allow = false): void
    {
        $this->db->replace(
            'xapip_consent_log',
            array(
                'usr_id' => array('integer', $usrId),
                'ref_id' => array('integer', $refId),
                'log_date' => array('timestamp', date('Y-m-d H:i:s'))
            ),
            array(
                'status' => array('integer', $allow)
            )
        );
    }

}