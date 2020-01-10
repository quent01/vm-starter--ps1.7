<?php

if (!defined('_PS_VERSION_')) {
    exit;
}

@ini_set('max_execution_time', 0);
@ini_set('error_reporting', 1);
@ini_set('memory_limit', "-1");

require_once(_PS_MODULE_DIR_ . 'woomigrationpro/classes/WooMigrationProMapping.php');
require_once(_PS_MODULE_DIR_ . 'woomigrationpro/classes/WooMigrationProSaveMapping.php');
require_once(_PS_MODULE_DIR_ . 'woomigrationpro/classes/WooMigrationProProcess.php');
require_once(_PS_MODULE_DIR_ . 'woomigrationpro/classes/WooMigrationProData.php');
require_once(_PS_MODULE_DIR_ . 'woomigrationpro/classes/WooMigrationProWoo.php');
require_once(_PS_MODULE_DIR_ . 'woomigrationpro/classes/WooMigrationProMigratedData.php');
require_once(_PS_MODULE_DIR_ . 'woomigrationpro/classes/WooMigrationProConvertDataStructur.php');
require_once(_PS_MODULE_DIR_ . 'woomigrationpro/classes/WooClient.php');
require_once(_PS_MODULE_DIR_ . 'woomigrationpro/classes/WooQuery.php');
require_once(_PS_MODULE_DIR_ . 'woomigrationpro/classes/WooImport.php');
require_once(_PS_OVERRIDE_DIR_ . 'modules/woomigrationpro/classes/WooImport.php');
require_once(_PS_MODULE_DIR_ . 'woomigrationpro/classes/WooPasswordEncrypt.php');


class WooMigrationProOverride extends WooMigrationPro{

}
