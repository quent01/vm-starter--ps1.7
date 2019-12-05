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

class WooMigrationProConvertDataStructur
{
    public function __construct()
    {
    }

    // for TaxRulesGroup
    public static function convertTaxRulesGroupStructur($taxRulesGroups)
    {
        $result = array();
        foreach ($taxRulesGroups as $key => $value) {
            $result[] = array('id_tax_rules_group' => $key, 'name' => $value);
        }

        return $result;
    }


    public static function convertCustomerAdressStructur($address)
    {
        $firstModify = array();
        $SecondModify = array();
        foreach ($address as $key => $value) {
            $firstModify[$value['user_id']][$key] = $value;
            foreach ($firstModify as $key => $value) {
                $SecondModify[$key] = self::arrayColumn($value, 'meta_value', 'meta_key');
            }
        }

        array_walk(
            $SecondModify,
            function (&$value, $key) use ($address) {
                foreach ($address as $adr) {
                    if ($adr['user_id'] == $key) {
                        $id = $adr['umeta_id'];
                    }
                }
                $value['id'] = $id;

                return $value['id_customer'] = $key;
            }
        );

        return $SecondModify;
    }

    public static function convertOrderAddressStructure($address, $bill = false)
    {
        $firstModify = array();
        $SecondModify = array();
        foreach ($address as $key => $value) {
            $firstModify[$value['post_id']][$key] = $value;
            foreach ($firstModify as $key => $value) {
                $SecondModify[$key] = self::arrayColumn($value, 'meta_value', 'meta_key');
            }
        }

        array_walk(
            $SecondModify,
            function (&$value, $key) use ($address, $bill) {
                foreach ($address as $adr) {
                    if ($adr['post_id'] == $key) {
                        $id = $adr['meta_id'];
                        break;
                    }
                }
                if ($bill) {
                    $value['id'] = $id + 1;
                } else {
                    $value['id'] = $id;
                }

                return $value['id_order'] = $key;
            }
        );

        return $SecondModify;
    }

    public static function connectWcMetadataWithData($wc_metaData, $wc_data, $meta_key, $connector_key = null, $type = null)
    {
        $result = array();
        $firstModify = array();
        $SecondModify = array();
        array_walk(
            $wc_metaData,
            function ($value, $key) use (&$firstModify, $meta_key, &$SecondModify) {
                $firstModify[$value[$meta_key]][$key] = $value;
                array_walk(
                    $firstModify,
                    function ($value, $key) use (&$SecondModify) {
                        $SecondModify[$key] = self::arrayColumn($value, 'meta_value', 'meta_key');
                    }
                );
            }
        );
        if ($connector_key === null) {
            $connector_key = $meta_key;
        }
        if ($type === 'order_detail') {
            foreach ($wc_data as $key => $value) {
                $result[$value['order_id']][$value['order_item_id']] = array_merge($value, $SecondModify[$value[$connector_key]]);
            }
        } else {
            for ($i = 0; $i < count($SecondModify); $i++) {
                $result[] = array_merge($wc_data[$i], $SecondModify[$wc_data[$i][$connector_key]]);
            }
        }
        return $result;
    }


    public static function connectOrderAdditional($orderDetail, $orderAdditional)
    {
        $firstModify = array();
        $SecondModify = array();
        foreach ($orderAdditional as $key => $value) {
            $firstModify[$value['order_id']][$key] = $value;

            foreach ($firstModify as $key => $value) {
                $SecondModify[$key] = self::arrayColumn($value, 'meta_value', 'meta_key');
            }
        }

        foreach ($orderDetail as $key => &$value) {
            foreach ($SecondModify as $modify_key => $modify_value) {
                if ($value['order_id'] == $modify_key) {
                    $value = array_merge($value, $modify_value);
                }
            }
        }

        return $orderDetail;
    }

    public static function getSecondCustomerAddressId($id_customer)
    {
        $tp = constant('_DB_PREFIX_');
        $sql = 'SELECT `id_address` FROM `' . $tp . 'address` WHERE `id_customer` = ' . pSQL($id_customer) . ' AND `deleted` = 0 AND `active` = 1 LIMIT 1,1';
        $result = Db::getInstance()->executeS($sql);
        return $result[0]['id_address'];
    }

    public static function firstCustomerAddressId($id_customer)
    {
        $tp = constant('_DB_PREFIX_');
        $sql = 'SELECT `id_address` FROM `' . $tp . 'address` WHERE `id_customer` = ' . pSQL($id_customer) . ' AND `deleted` = 0 AND `active` = 1 LIMIT 1';
        $result = Db::getInstance()->executeS($sql);
        return $result[0]['id_address'];
    }

    public static function getTaxRulesGroups($name, $only_active = true)
    {
        return Db::getInstance()->executeS(
            'SELECT DISTINCT g.id_tax_rules_group, g.name, g.active
			FROM `' . _DB_PREFIX_ . 'tax_rules_group` g'
            . Shop::addSqlAssociation('tax_rules_group', 'g') . ' WHERE deleted = 0'
            . ($only_active ? ' AND g.`active` = 1' : '') . '
            AND name="' . pSQL($name) . '"
			ORDER BY name ASC'
        );
    }


    public static function arrayColumn($input, $columnKey, $indexKey = null)
    {
        if (!function_exists('array_column')) {
            $array = array();
            foreach ($input as $value) {
                if (!array_key_exists($columnKey, $value)) {
                    trigger_error("Key \"$columnKey\" does not exist in array");
                    return false;
                }
                if (is_null($indexKey)) {
                    $array[] = $value[$columnKey];
                } else {
                    if (!array_key_exists($indexKey, $value)) {
                        trigger_error("Key \"$indexKey\" does not exist in array");
                        return false;
                    }
                    if (!is_scalar($value[$indexKey])) {
                        trigger_error("Key \"$indexKey\" does not contain scalar value");
                        return false;
                    }
                    $array[$value[$indexKey]] = $value[$columnKey];
                }
            }
            return $array;
        } else {
            return array_column($input, $columnKey, $indexKey);
        }
    }
}
