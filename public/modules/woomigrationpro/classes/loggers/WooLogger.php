<?php
/**
 * NOTICE OF LICENSE
 *
 * This source file is subject to a commercial license from MigrationPro
 * Use, copy, modification or distribution of this source file without written
 * license agreement from the MigrationPro is strictly forbidden.
 * In order to obtain a license, please contact us: contact@migration-pro.com
 *
 * INFORMATION SUR LA LICENCE D'UTILISATION
 *
 * L'utilisation de ce fichier source est soumise a une licence commerciale
 * concedee par la societe MigrationPro
 * Toute utilisation, reproduction, modification ou distribution du present
 * fichier source sans contrat de licence ecrit de la part de la MigrationPro est
 * expressement interdite.
 * Pour obtenir une licence, veuillez contacter la MigrationPro a l'adresse: contact@migration-pro.com
 *
 * @author    MigrationPro
 * @copyright Copyright (c) 2012-2019 MigrationPro
 * @license   Commercial license
 * @package   MigrationPro: WooCommerce To PrestaShop
 */

require_once 'WooMigrationProDBErrorLogger.php';
require_once 'WooMigrationProDBWarningLogger.php';
require_once 'WooEntityTypeMapper.php';

class WooLogger
{
    public function addErrorLog($log, $entityType)
    {
        WooMigrationProDBErrorLogger::addErrorLog($log, $entityType);
    }

    public function addWarningLog($log, $entityType)
    {
        WooMigrationProDBWarningLogger::addWarningLogToDB($log, $entityType);
        WooMigrationProDBWarningLogger::addWarningLogToFile($log);
    }

    public function getAllWarnings()
    {
        $warnings = WooMigrationProDBWarningLogger::getAllWarnings();

        $warnings = array_map(array($this, 'createWarningMessage'), $warnings);

        return $warnings;
    }

    private function createWarningMessage($warning)
    {
        $warning = 'There is (are) ' . $warning['count'] . ' ' . WooEntityTypeMapper::getEntityTypeNameByAlias($warning['entity_type']) . ' warning(s).';

        return $warning;
    }
}
