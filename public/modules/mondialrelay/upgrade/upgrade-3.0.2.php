<?php
if (!defined('_PS_VERSION_')) {
    exit;
}

/**
 * Upgrade to 3.0.2
 * @param Mondialrelay $module
 */
function upgrade_module_3_0_2($module)
{
    $sqlDescribeColumns = "SHOW COLUMNS FROM `"._DB_PREFIX_."%tableName%`";
    $sqlAddColumn = "ALTER TABLE "._DB_PREFIX_."%tableName% ADD COLUMN %columnName%";

    $describeMethodColumns = Db::getInstance()->executeS(
        str_replace("%tableName%", "mondialrelay_carrier_method", $sqlDescribeColumns)
    );

    $newMethodColumns = array('date_add', 'date_upd');

    foreach ($newMethodColumns as $newColumn) {
        if(!in_array($newColumn, $describeMethodColumns)) {
            Db::getInstance()->execute(str_replace(
                array("%tableName%", "%columnName"),
                array("mondialrelay_carrier_method", $newColumn),
                $sqlAddColumn
            ));
            continue;
        }
    }

    return true;
}

