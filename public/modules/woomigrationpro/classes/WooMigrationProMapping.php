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

class WooMigrationProMapping extends ObjectModel
{
    public $id;
    public $type;
    public $source_id;
    public $source_name;
    public $local_id;

    public static $definition = array(
        'table' => 'woomigrationpro_mapping',
        'primary' => 'id_mapping',
        'fields' => array(
            'type' => array('type' => self::TYPE_STRING, 'validate' => 'isGenericName', 'required' => true),
            'source_id' => array('type' => self::TYPE_INT, 'validate' => 'isUnsignedInt', 'required' => true),
            'source_name' => array('type' => self::TYPE_STRING, 'validate' => 'isGenericName', 'required' => true),
            'local_id' => array('type' => self::TYPE_INT, 'validate' => 'isNullOrUnsignedId')
        ),
    );

    public static function listMapping($list = false, $keyAsSourceId = false, $keyAsSourceName = false)
    {
        $sql = new DbQuery();
        $sql->select('*');
        $sql->from('woomigrationpro_mapping');
        $mappings = array();
        $rows = Db::getInstance()->executeS($sql);
        if (!$list) {
            return $rows;
        }

        if ($keyAsSourceId) {
            foreach ($rows as $row) {
                if ($row['type'] === 'multi_shops') {
                    $mappings[$row['type']] = $row['local_id'];
                } else {
                    $mappings[$row['type']][$row['source_id']] = $row['local_id'];
                }
            }
        } else if ($keyAsSourceName) {
            foreach ($rows as $row) {
                $mappings[$row['type']][$row['source_name']] = $row['local_id'];
            }
        } else {
            foreach ($rows as $row) {
                $mappings[$row['type']][$row['id_mapping']] = array(
                    'id_mapping' => $row['id_mapping'],
                    'source_id' => $row['source_id'],
                    'source_name' => $row['source_name'],
                    'local_id' => $row['local_id']
                );
            }
        }

        return $mappings;
    }
}
