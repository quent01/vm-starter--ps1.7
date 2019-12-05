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

class WooMigrationProData extends ObjectModel
{
    const TYPE_TAX = 't';
    const TYPE_TAXRULESGROUP = 'trg';
    const TYPE_TAXRULE = 'tr';
    const TYPE_COUNTRY = 'co';
    const TYPE_STATE = 'st';
    const TYPE_CATEGORY = 'c';
    const TYPE_PRODUCT = 'p';
    const TYPE_ATTRIBUTEGROUP = 'ag';
    const TYPE_ATTRIBUTE = 'a';
    const TYPE_COMBINATION = 'com'; //PRODUCT_ATTRIBUTE
    const TYPE_SUPPLIER = 's';
    const TYPE_MANUFACTURER = 'm';
    const TYPE_SPECIFICPRICE = 'sp';
    const TYPE_IMAGE = 'i';
    const TYPE_FEATURE = 'f';
    const TYPE_FEATUREVALUE = 'fv';
    const TYPE_CUSTOMIZATIONFIELD = 'cf';
    const TYPE_TAG = 't';
    const TYPE_CUSTOMER = 'cus';
    const TYPE_ADDRESS = 'adr';
    const TYPE_ORDER = 'o';
    const TYPE_ORDERDETAIL = 'od';
    const TYPE_ORDERHISTORY = 'oh';
    const TYPE_STOCKAVAILABLE = 'sa';
    const TYPE_CART = 'crt';

    public $id;

    public $type;

    public $source_id;

    public $local_id;

    public static $definition = array(
        'table' => 'woomigrationpro_data',
        'primary' => 'id_data',
        'fields' => array(
            'type' => array('type' => self::TYPE_STRING, 'validate' => 'isGenericName', 'required' => true),
            'source_id' => array('type' => self::TYPE_INT, 'validate' => 'isUnsignedInt', 'required' => true),
            'local_id' => array('type' => self::TYPE_INT, 'validate' => 'isUnsignedInt', 'required' => true)
        ),
    );


    public static function exist($typeString, $sourceId)
    {
        $type = constant("self::TYPE_" . Tools::strtoupper($typeString));

        if (!is_null($type)) {
            return Db::getInstance()->getValue(
                'SELECT 1 FROM ' . _DB_PREFIX_ . 'woomigrationpro_data WHERE type=\'' . pSQL(
                    $type
                ) . '\' AND source_id=' . (int)$sourceId
            );
        }

        return false;
    }

    public static function import($typeString, $sourceID, $localID)
    {
        $type = constant("self::TYPE_" . Tools::strtoupper($typeString));
        $sql = 'INSERT INTO ' . _DB_PREFIX_ . 'woomigrationpro_data SET type=\'' . pSQL($type) . '\', source_id=' . (int)$sourceID . ', local_id=' . (int)$localID;

        return Db::getInstance()->execute($sql);
    }

    public static function getLocalID($typeString, $sourceID)
    {
        $type = constant("self::TYPE_" . Tools::strtoupper($typeString));
        $sql = 'SELECT local_id FROM ' . _DB_PREFIX_ . 'woomigrationpro_data WHERE type=\'' . pSQL($type) . '\' AND source_id=' . (int)$sourceID . ';';

        return Db::getInstance()->getValue($sql);
    }
}
