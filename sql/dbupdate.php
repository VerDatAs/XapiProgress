<#1>
<?php
if(!$ilDB->tableExists('xapip_queue'))
{
    $fields = array (
        "queue_id" => array (
            "notnull" => true,
            "length" => 8,
            "default" => "0",
            "type" => "integer"
        )
        ,"ref_id" => array (
            "notnull" => false,
            "length" => 4,
            "default" => null,
            "type" => "integer"
        )
        ,"obj_id" => array (
            "notnull" => true,
            "length" => 4,
            "default" => "0",
            "type" => "integer"
        )
        ,"usr_id" => array (
            "notnull" => true,
            "length" => 4,
            "default" => "0",
            "type" => "integer"
        )
        ,"event" => array(
            "notnull" => false,
            "type" => "text",
            "length" => 64
        )
        ,"date" => array (
            "notnull" => false,
            "type" => "timestamp"
        )
        ,"state" => array (
            "notnull" => true,
            "length" => 2,
            "default" => "2",
            "type" => "integer"
        )
        ,"bucket_id" => array (
            "notnull" => false,
            "length" => 8,
            "default" => null,
            "type" => "integer"
        )
        ,"date_failed" => array (
            "notnull" => false,
            "type" => "timestamp"
        )
        ,"parameter" => array (
            "notnull" => false,
            "type" => 'clob'
        )
        ,"statement" => array (
            "notnull" => false,
            "type" => 'clob'
        )
    );
    $ilDB->createTable('xapip_queue', $fields);
    $ilDB->addPrimaryKey('xapip_queue', array("queue_id"));
    $ilDB->createSequence('xapip_queue');
}
?>
<#2>
<?php
$fields_data = array(
    'id' => array(
        'type' => 'integer',
        'length' => 8,
        'notnull' => true
    ),
    'crs_id' => array(
        'type' => 'integer',
        'length' => 8,
        'notnull' => true
    ),
    'lrs_type_id' => array(
        'type' => 'integer',
        'length' => 2,
        'notnull' => true,
        'default' => 1
    ),
    'until' => array(
        'type' => 'timestamp',
        'notnull' => true
    ),
    'data' => array(
        'type' => 'clob',
        'notnull' => false
    )
);
$ilDB->createTable("xapip_data", $fields_data);
$ilDB->addPrimaryKey("xapip_data", array("id"));
$ilDB->createSequence("xapip_data");
?>
<#3>
<?php
if ($ilDB->tableColumnExists('xapip_data', 'until')) {
    $ilDB->dropTableColumn('xapip_data', 'until');
}

if (!$ilDB->tableColumnExists('xapip_data', 'until')) {
    $ilDB->addTableColumn("xapip_data", "until", [
        'type' => 'text',
        'length' => 32,
        'notnull' => true,
        'default' => ''
    ]);
}
?>
<#4>
<?php
if(!$ilDB->tableExists('xapip_consent_log')) {
    $fields_data = array(
        'usr_id' => array(
            'type' => 'integer',
            'length' => 4,
            'notnull' => true
        ),
        'ref_id' => array(
            'type' => 'integer',
            'length' => 4,
            'notnull' => true
        ),
        'status' => array(
            'type' => 'integer',
            'length' => 1,
            'notnull' => true,
            'default' => 0
        ),
        'log_date' => array(
            'type' => 'timestamp',
            'notnull' => true,
        )
    );
    $ilDB->createTable("xapip_consent_log", $fields_data);
    $ilDB->addPrimaryKey("xapip_consent_log", array("usr_id", 'ref_id', 'log_date'));
}
?>
<#5>
<?php
if(!$ilDB->tableExists('xapip_settings')) {
    $fields_data = array(
        'id' => array(
            'type' => 'integer',
            'length' => 2,
            'notnull' => true,
            'default' => 1
        ),
        'lrs_type_id' => array(
            'type' => 'integer',
            'length' => 4,
            'notnull' => true,
            'default' => 0
        ),
        'only_course' => array(
            'type' => 'integer',
            'length' => 1,
            'notnull' => true,
            'default' => 1
        ),
        'courses' => array(
            'type' => 'clob',
            'notnull' => false
        ),
        'need_consent' => array(
            'type' => 'integer',
            'length' => 1,
            'notnull' => true,
            'default' => 0
        ),
        'data_delete' => array(
            'type' => 'integer',
            'length' => 1,
            'notnull' => true,
            'default' => 0
        ),
        'restricted_user_access' => array(
            'type' => 'integer',
            'length' => 1,
            'notnull' => true,
            'default' => 1
        ),
        'events' => array(
            'type' => 'text',
            'length' => 1024,
            'notnull' => true,
            'default' => ''
        ),
        'h5p_verbs' => array(
            'type' => 'text',
            'length' => 1024,
            'notnull' => true,
            'default' => ''
        ),
        'usr_id' => array (
            'type' => 'integer',
            'notnull' => true,
            'length' => 4,
            'default' => 0
        ),
        'updated' => array(
            'type' => 'timestamp',
            'notnull' => true
        )
    );
    $ilDB->createTable("xapip_settings", $fields_data);
    $ilDB->addPrimaryKey("xapip_settings", array("id"));
    $ilDB->createSequence('xapip_settings');
}
?>
