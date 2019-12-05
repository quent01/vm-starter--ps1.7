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

class WooMigrationProWoo extends ObjectModel
{
    public $id;
    public $mail;
    public $passwd;

    public static $definition = array(
        'table' => 'migrationpro_woo',
        'primary' => 'id_woo',
        'fields' => array(
            'mail' => array('type' => self::TYPE_STRING, 'validate' => 'isEmail', 'required' => true, 'size' =>
                255),
            'id_customer' => array('type' => self::TYPE_STRING, 'validate' => 'isUnsignedInt', 'required' => true, 'size' =>
                11),
            'passwd' => array('type' => self::TYPE_STRING, 'validate' => 'isEmail', 'required' => true, 'size' => 255)
        ),
    );

    public static function storeCustomerPass($id_customer, $mail, $pass)
    {
        $sql = 'INSERT INTO ' . _DB_PREFIX_ . 'migrationpro_woo SET id_customer=' . (int)$id_customer . ', mail=\'' . pSQL($mail) . '\',passwd=\'' . pSQL($pass) . '\'';
        return Db::getInstance()->execute($sql);
    }

    public static function getWooUser($mail)
    {
        $sql = 'SELECT * FROM ' . _DB_PREFIX_ . 'migrationpro_woo where mail=\'' . pSQL($mail) . '\'';
        return Db::getInstance()->executeS($sql);
    }

    public static function deleteUserById($id)
    {
        $sql = 'DELETE FROM ' . _DB_PREFIX_ . 'migrationpro_woo where id_customer=' . (int)$id;
        return Db::getInstance()->execute($sql);
    }
}
