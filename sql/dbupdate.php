<#1>
<?php
if (!$ilDB->tableExists('auth_authhk_azuread')) {
    $fields_conf = array(
            'id' => array(
                    'type' => 'integer',
                    'length' => 4,
                    'notnull' => true
            ),
            'active' => array(
                'type' => 'integer',
                'length' => 1,
                'notnull' => true
             ),
            'secret' => array(
                    'type' => 'text',
                    'length' => 256,
                    'notnull' => true
            ),
            'provider' => array(
                'type' => 'text',
                'length' => 256,
                'notnull' => true
            ),
            'session_duration' => array(
                    'type' => 'integer',
                    'length' => 8,
                    'notnull' => true,
                    'default' =>5
            ),
            'logout_scope' => array(
                    'type' => 'integer',
                    'length' => 1,
                    'notnull' => true,
                    'default' =>0
            ),
            'sync_allowed' => array(
                'type' => 'integer',
                'length' => 1,
                'notnull' => true,
                'default' => 1
            ),
            'is_custom_session' => array(
                'type' => 'integer',
                'length' => 1,
                'notnull' => true
            ),
            'role' => array(
                'type' => 'integer',
                'length' => 2,
                'notnull' => true
            ),
    );



    $ilDB->createTable("auth_authhk_azuread", $fields_conf);
    $ilDB->addPrimaryKey("auth_authhk_azuread", array("id"));
}
?>

<#2>
<?php

if ($ilDB->tableColumnExists("auth_authhk_azuread", "secret")){
    $ilDB->renameTableColumn("auth_authhk_azuread", "secret", "apikey");
}

if (!$ilDB->tableColumnExists("auth_authhk_azuread", "secretkey")){
	$ilDB->addTableColumn('auth_authhk_azuread', 'secretkey', array(
		'type' => 'text',
		'length' => 256,
		'notnull' => 0
	));
}
?>
<#3>
<?php
\minervis\plugins\AzureAD\Status\Repository::getInstance()->installTables();
?>

