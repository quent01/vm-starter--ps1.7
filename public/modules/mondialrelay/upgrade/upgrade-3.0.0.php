<?php
/**
 * NOTICE OF LICENSE
 *
 * @author    202 ecommerce <tech@202-ecommerce.com>
 * @author    Mondial Relay
 * @copyright Copyright (c) Mondial Relay
 * @license http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

/**
 * Upgrade to 3.0.0
 * @param Mondialrelay $module
 */
function upgrade_module_3_0_0($module)
{
    $installer = new MondialrelayClasslib\Install\ModuleInstaller($module);
    
    /*** CONFIGURATION ***/
    
    // Update conf keys/values
    Configuration::updateValue(Mondialrelay::DISPLAY_MAP, Configuration::get('MONDIAL_RELAY_MODE') == 'widget');
    Configuration::deleteByName('MONDIAL_RELAY_MODE');
    Configuration::deleteByName('MONDIAL_RELAY');
    
    // Configuration was previously stored as a serialized array (one per shop)
    // We will now store each field as its own
    $selectConfiguration = new DbQuery();
    $selectConfiguration->select("*")
        ->from("configuration", "c")
        ->where("c.name = 'MR_ACCOUNT_DETAIL'")
    ;
    
    foreach (Db::getInstance()->executeS($selectConfiguration) as $row) {
        $conf = unserialize($row['value']);
        if (empty($conf) || !is_array($conf)) {
            continue;
        }
        
        $id_shop_group = (int)$row['id_shop_group'];
        $id_shop = (int)$row['id_shop'];
        
        Configuration::updateValue(
            Mondialrelay::WEBSERVICE_ENSEIGNE,
            $conf['MR_ENSEIGNE_WEBSERVICE'],
            false,
            $id_shop_group,
            $id_shop
        );
        Configuration::updateValue(
            Mondialrelay::WEBSERVICE_BRAND_CODE,
            $conf['MR_CODE_MARQUE'],
            false,
            $id_shop_group,
            $id_shop
        );
        Configuration::updateValue(
            Mondialrelay::WEBSERVICE_KEY,
            $conf['MR_KEY_WEBSERVICE'],
            false,
            $id_shop_group,
            $id_shop
        );
        $lang  = in_array($conf['MR_LANGUAGE'], array('FR', 'ES', 'NL')) ? $conf['MR_LANGUAGE'] : 'FR';
        Configuration::updateValue(
            Mondialrelay::LABEL_LANG,
            $lang,
            false,
            $id_shop_group,
            $id_shop
        );
        Configuration::updateValue(
            Mondialrelay::WEIGHT_COEFF,
            $conf['MR_WEIGHT_COEFFICIENT'],
            false,
            $id_shop_group,
            $id_shop
        );
    }
    Configuration::deleteByName('MR_ACCOUNT_DETAIL');

    Configuration::updateValue(Mondialrelay::OS_DISPLAY_LABEL, Configuration::get('PS_OS_PREPARATION'));
    Configuration::updateValue(Mondialrelay::OS_LABEL_GENERATED, Configuration::get('PS_OS_SHIPPING'));
    Configuration::updateValue(Mondialrelay::OS_ORDER_DELIVERED, Configuration::get('PS_OS_DELIVERED'));

    /*** DATABASE ***/

    // Update ObjectModels
    $sqlBackupExists = "SHOW TABLES LIKE '"._DB_PREFIX_."%tableName%_backup'";
    $sqlBackupCreateTable = "CREATE TABLE IF NOT EXISTS `"._DB_PREFIX_."%tableName%_backup` LIKE `"._DB_PREFIX_."%tableName%`";
    $sqlBackupFillTable = "REPLACE `"._DB_PREFIX_."%tableName%_backup` SELECT * FROM `"._DB_PREFIX_."%tableName%`";
    
    $sqlTableExists = "SHOW TABLES LIKE '"._DB_PREFIX_."%tableName%'";
    $sqlRenameTable = "RENAME TABLE "._DB_PREFIX_."%oldTableName% TO "._DB_PREFIX_."%newTableName%";
    
    $sqlDescribeColumns = "SHOW COLUMNS FROM `"._DB_PREFIX_."%tableName%`";
    $sqlRenameColumn = "ALTER TABLE `"._DB_PREFIX_."%tableName%` "
        . "CHANGE `%oldColumnName%` %newColumnDefinition%";
    $sqlDropColumn = "ALTER TABLE "._DB_PREFIX_."%tableName% DROP COLUMN %columnName%";
    
    // Backup mr_method table
    $methodBackupExists = Db::getInstance()->executeS(
        str_replace("%tableName%", "mr_method", $sqlBackupExists)
    );
    if ($methodBackupExists == false) {
        // Create backup table
        if (!Db::getInstance()->execute(
            str_replace('%tableName%', 'mr_method', $sqlBackupCreateTable)
        )) {
            throw new Exception("Could not create `mr_method` backup table");
        }
    }
    
    // Fill the backup table
    if (!Db::getInstance()->execute(
        str_replace('%tableName%', 'mr_method', $sqlBackupFillTable)
    )) {
        throw new Exception("Could not fill `mr_method` backup table");
    }

    // Update the mr_method table
    $methodTableExists = Db::getInstance()->executeS(
        str_replace("%tableName%", "mondialrelay_carrier_method", $sqlTableExists)
    );
    if ($methodTableExists == false) {
        // Rename the mr_method table
        if (!Db::getInstance()->execute(str_replace(
            array("%oldTableName%", "%newTableName%"),
            array("mr_method", "mondialrelay_carrier_method"),
            $sqlRenameTable
        ))) {
            throw new Exception("Could not rename `mr_method` table");
        }

        // Rename / drop the mr_method columns
        $renameMethodColumns = array(
            'id_mr_method' => "`id_mondialrelay_carrier_method` int(10) unsigned NOT NULL AUTO_INCREMENT",
            'dlv_mode' => "`delivery_mode` enum('24R','DRI','LD1','LDS','HOM') NOT NULL",
            'insurance' => "`insurance_level` enum('0','1','2','3','4','5') NOT NULL DEFAULT '0'",
        );
        $dropMethodColumns = array('name', 'country_list', 'col_mode');
        $describeMethodColumns = Db::getInstance()->executeS(
            str_replace("%tableName%", "mondialrelay_carrier_method", $sqlDescribeColumns)
        );

        foreach ($describeMethodColumns as $oldColumn) {
            if (isset($renameMethodColumns[$oldColumn['Field']])) {
                if (!Db::getInstance()->execute(str_replace(
                    array("%tableName%", "%oldColumnName%", "%newColumnDefinition%"),
                    array("mondialrelay_carrier_method", $oldColumn['Field'], $renameMethodColumns[$oldColumn['Field']]),
                    $sqlRenameColumn
                ))) {
                    throw new Exception("Could not update `mondialrelay_carrier_method` column `".$oldColumn['Field']."`");
                }
                continue;
            }

            if (in_array($oldColumn['Field'], $dropMethodColumns)) {
                Db::getInstance()->execute(str_replace(
                    array("%tableName%", "%columnName%"),
                    array("mondialrelay_carrier_method", $oldColumn['Field']),
                    $sqlDropColumn
                ));
                continue;
            }
        }

        // Classlib will alter the columns if needed
        $installer->installObjectModel("MondialrelayCarrierMethod");

        // Update carrier_method data : add date_add, date_upd
        Db::getInstance()->execute(
            "UPDATE "._DB_PREFIX_."mondialrelay_carrier_method "
                . "SET date_add = NOW(), date_upd = NOW() "
                . "WHERE date_add = '0000-00-00 00:00:00'"
        );
    }
    
    // Backup mr_selected table
    $selectedBackupExists = Db::getInstance()->executeS(
        str_replace("%tableName%", "mr_selected", $sqlBackupExists)
    );
    if ($selectedBackupExists == false) {
        // Create backup table
        if (!Db::getInstance()->execute(
            str_replace('%tableName%', 'mr_selected', $sqlBackupCreateTable)
        )) {
            throw new Exception("Could not create `mr_selected` backup table");
        }
    }
    
    // Fill the backup table
    if (!Db::getInstance()->execute(
        str_replace('%tableName%', 'mr_selected', $sqlBackupFillTable)
    )) {
        throw new Exception("Could not fill `mr_selected` backup table");
    }

    // Update mr_selected table
    $selectedTableExists = Db::getInstance()->executeS(
        str_replace("%tableName%", "mondialrelay_selected_relay", $sqlTableExists)
    );
    if ($selectedTableExists == false) {
        // Rename the mr_selected table
        if (!Db::getInstance()->execute(str_replace(
            array("%oldTableName%", "%newTableName%"),
            array("mr_selected", "mondialrelay_selected_relay"),
            $sqlRenameTable
        ))) {
            throw new Exception("Could not rename `mr_selected` table");
        }

        // Rename the mr_selected columns
        $renameSelectedColumns = array(
            'id_mr_selected' => "`id_mondialrelay_selected_relay` int(10) unsigned NOT NULL AUTO_INCREMENT",
            'id_method' => "`id_mondialrelay_carrier_method` int(10) unsigned NOT NULL",
            'MR_insurance' => "`insurance_level` enum('0','1','2','3','4','5') NOT NULL DEFAULT '0'",
            'MR_poids' => "`package_weight` varchar(7) DEFAULT NULL",
            'MR_Selected_Num' => "`selected_relay_num` varchar(6) DEFAULT NULL",
            'MR_Selected_LgAdr1' => "`selected_relay_adr1` varchar(36) DEFAULT NULL",
            'MR_Selected_LgAdr2' => "`selected_relay_adr2` varchar(36) DEFAULT NULL",
            'MR_Selected_LgAdr3' => "`selected_relay_adr3` varchar(36) DEFAULT NULL",
            'MR_Selected_LgAdr4' => "`selected_relay_adr4` varchar(36) DEFAULT NULL",
            'MR_Selected_CP' => "`selected_relay_postcode` varchar(10) DEFAULT NULL",
            'MR_Selected_Ville' => "`selected_relay_city` varchar(32) DEFAULT NULL",
            'MR_Selected_Pays' => "`selected_relay_country_iso` varchar(2) DEFAULT NULL",
            'url_suivi' => "`tracking_url` varchar(1000) DEFAULT NULL",
            'url_etiquette' => "`label_url` varchar(1000) DEFAULT NULL",
            'exp_number' => "`expedition_num` varchar(8) DEFAULT NULL",
        );
        $describeSelectedColumns = Db::getInstance()->executeS(
            str_replace("%tableName%", "mondialrelay_selected_relay", $sqlDescribeColumns)
        );

        foreach ($describeSelectedColumns as $oldColumn) {
            if (!isset($renameSelectedColumns[$oldColumn['Field']])) {
                continue;
            }

            if (!Db::getInstance()->execute(str_replace(
                array("%tableName%", "%oldColumnName%", "%newColumnDefinition%"),
                array("mondialrelay_selected_relay", $oldColumn['Field'], $renameSelectedColumns[$oldColumn['Field']]),
                $sqlRenameColumn
            ))) {
                throw new Exception("Could not update `mondialrelay_selected_relay` column `".$oldColumn['Field']."`");
            }
        }

        // Classlib will alter the columns if needed
        $installer->installObjectModel("MondialrelaySelectedRelay");
    }

    // If the new carrier_method table was already there
    if ($methodTableExists) {
        // Get all carrier methods from the old module that aren't in the new
        // module
        $selectOldCarriersToCopy = new DbQuery();
        $selectOldCarriersToCopy
            ->select('*')
            ->from('mr_method')
            ->where('id_carrier NOT IN ('
                . 'SELECT id_carrier FROM '._DB_PREFIX_.'mondialrelay_carrier_method'
                . ')');
        $oldCarriersToCopy = Db::getInstance()->executeS($selectOldCarriersToCopy);

        foreach ($oldCarriersToCopy as &$oldCarrier) {
            // Copy each carrier
            Db::getInstance()->insert(
                'mondialrelay_carrier_method',
                array(
                    'delivery_mode' => pSQL($oldCarrier['dlv_mode']),
                    'insurance_level' => (int)$oldCarrier['insurance'],
                    'id_carrier' => (int)$oldCarrier['id_carrier'],
                    'is_deleted' => (int)$oldCarrier['is_deleted'],
                    'date_add' => array('type' => 'sql', 'value' => 'NOW()'),
                    'date_upd' => array('type' => 'sql', 'value' => 'NOW()'),
                )
            );
            $id_mondialrelay_carrier_method = Db::getInstance()->Insert_ID();

            // Copy selections for the old carrier
            $selectOldCarrierSelectionsToCopy = new DbQuery();
            $selectOldCarrierSelectionsToCopy
                ->select('*')
                ->from('mr_selected')
                ->where('id_method = ' . (int)$oldCarrier['id_mr_method']);
            $oldCarrierSelectionsToCopy = Db::getInstance()->executeS($selectOldCarrierSelectionsToCopy);

            foreach ($oldCarrierSelectionsToCopy as $oldCarrierSelection) {
                $newSelectionData = array(
                    'id_customer' => (int)$oldCarrierSelection['id_customer'],
                    'id_mondialrelay_carrier_method' => (int)$id_mondialrelay_carrier_method,
                    'id_cart' => (int)$oldCarrierSelection['id_cart'],
                    'id_order' => (int)$oldCarrierSelection['id_order'],
                    'package_weight' => pSQL($oldCarrierSelection['MR_poids']),
                    'insurance_level' => (int)$oldCarrierSelection['MR_insurance'],
                    'selected_relay_num' => pSQL($oldCarrierSelection['MR_Selected_Num']),
                    'selected_relay_adr1' => pSQL($oldCarrierSelection['MR_Selected_LgAdr1']),
                    'selected_relay_adr2' => pSQL($oldCarrierSelection['MR_Selected_LgAdr2']),
                    'selected_relay_adr3' => pSQL($oldCarrierSelection['MR_Selected_LgAdr3']),
                    'selected_relay_adr4' => pSQL($oldCarrierSelection['MR_Selected_LgAdr4']),
                    'selected_relay_postcode' => pSQL($oldCarrierSelection['MR_Selected_CP']),
                    'selected_relay_city' => pSQL($oldCarrierSelection['MR_Selected_Ville']),
                    'selected_relay_country_iso' => pSQL($oldCarrierSelection['MR_Selected_Pays']),
                    'tracking_url' => pSQL($oldCarrierSelection['url_suivi']),
                    'label_url' => pSQL($oldCarrierSelection['url_etiquette']),
                    'expedition_num' => pSQL($oldCarrierSelection['exp_number']),
                    'date_add' => pSQL($oldCarrierSelection['date_add']),
                    'date_upd' => pSQL($oldCarrierSelection['date_upd']),
                );
                Db::getInstance()->update(
                    'mondialrelay_selected_relay',
                    $newSelectionData,
                    'id_cart = ' . (int)$oldCarrierSelection['id_cart']
                );
                if (!Db::getInstance()->numRows()) {
                    Db::getInstance()->insert(
                        'mondialrelay_selected_relay',
                        $newSelectionData,
                        true
                    );
                }
            }
        }
    }

    // Update selected_relay : add date_add, date_upd
    Db::getInstance()->execute(
        "UPDATE "._DB_PREFIX_."mondialrelay_selected_relay msr "
            . "INNER JOIN "._DB_PREFIX_."orders o ON o.id_order = msr.id_order "
            . "SET msr.date_add = o.date_add, msr.date_upd = o.date_upd "
            . "WHERE msr.date_add = '0000-00-00 00:00:00'"
    );
    Db::getInstance()->execute(
        "UPDATE "._DB_PREFIX_."mondialrelay_selected_relay msr "
            . "INNER JOIN "._DB_PREFIX_."cart c ON c.id_cart = msr.id_cart "
            . "SET msr.date_add = c.date_add, msr.date_upd = c.date_upd "
            . "WHERE msr.date_add = '0000-00-00 00:00:00'"
    );

    //update weight from order if it's null
    Db::getInstance()->execute(
        "UPDATE "._DB_PREFIX_."mondialrelay_selected_relay msr "
        . "INNER JOIN (SELECT odt.id_order, ROUND(SUM(odt.`product_weight` * odt.`product_quantity`) * ".(int)Configuration::get(MondialRelay::WEIGHT_COEFF).") AS new_weight "
        . "FROM "._DB_PREFIX_.OrderDetail::$definition['table']." odt GROUP BY odt.id_order) "
        . "odts ON odts.id_order = msr.id_order SET msr.package_weight = odts.new_weight "
        . "WHERE msr.package_weight is NULL OR msr.package_weight = 0"
    );

    // Remove old method table
    Db::getInstance()->execute("DROP TABLE IF EXISTS "._DB_PREFIX_."mr_method");

    // We shouldn't need to synchronize the old "selected" table with the new
    // "mondialrelay_selected_relay" table. If the new table isn't there, we'll
    // update the old one; if the new table is there, it's impossible that any
    // data from the old table wasn't copied along with the carriers; this would
    // mean that a selection was created from the old module using a carrier
    // from the new module

    // Remove old "selected" table
    Db::getInstance()->execute("DROP TABLE IF EXISTS "._DB_PREFIX_."mr_selected");

    // Backup unused method_shop table
    $methodShopBackupExists = Db::getInstance()->executeS(
        str_replace("%tableName%", "mr_method_shop", $sqlBackupExists)
    );
    if ($methodShopBackupExists == false) {
        // If the backup table doesn't exist, create it
        if (!Db::getInstance()->execute(str_replace(
            array("%oldTableName%", "%newTableName%"),
            array("mr_method_shop", "mr_method_shop_backup"),
            $sqlRenameTable
        ))) {
            throw new Exception("Could not backup `mr_method_shop` table");
        }
    } else {
        // If the backup table already exists, fill it again
        if (!Db::getInstance()->execute(
            str_replace('%tableName%', 'mr_method_shop', $sqlBackupFillTable)
        )) {
            throw new Exception("Could not fill `mr_method_shop` backup table");
        }
        // Remove old "method_shop" table
        Db::getInstance()->execute("DROP TABLE IF EXISTS "._DB_PREFIX_."mr_method_shop");
    }
    
    // Backup unused history table
    $historyBackupExists = Db::getInstance()->executeS(
        str_replace("%tableName%", "mr_history", $sqlBackupExists)
    );
    if ($historyBackupExists == false) {
        // If the backup table doesn't exist, create it
        if (!Db::getInstance()->execute(str_replace(
            array("%oldTableName%", "%newTableName%"),
            array("mr_history", "mr_history_backup"),
            $sqlRenameTable
        ))) {
            throw new Exception("Could not backup `mr_history` table");
        }
    } else {
        // If the backup table already exists, fill it again
        if (!Db::getInstance()->execute(
            str_replace('%tableName%', 'mr_history', $sqlBackupFillTable)
        )) {
            throw new Exception("Could not fill `mr_history` backup table");
        }
        // Remove old "history" table
        Db::getInstance()->execute("DROP TABLE IF EXISTS "._DB_PREFIX_."mr_history");
    }
    
    /*** HOOKS ***/
    $installer->clearHookUsed();
    $installer->registerHooks();
    
    /*** ADMIN TABS ***/
    $installer->uninstallModuleAdminControllers();
    $installer->installAdminControllers();

    /*** Extentions ***/
    $installer->installExtensions();
    
    return true;
}
