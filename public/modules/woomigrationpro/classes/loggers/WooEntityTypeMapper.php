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

class WooEntityTypeMapper
{
    public static function getEntityTypeNameByAlias($alias)
    {
        $entityTypesAndAliases = self::entityTypes();

        if (array_key_exists($alias, $entityTypesAndAliases)) {
            return $entityTypesAndAliases[$alias];
        }

        return 'Common';
    }

    private static function entityTypes()
    {
        $entityTypeAliasesAndNames = array(
            't'=>'Tax',
            'trg'=>'Tax Rules Group',
            'tr'=>'Tax Rule',
            'co'=>'Country',
            'st'=>'State',
            'c'=>'Category',
            'crr'=>'Carrier',
            'p'=>'Product',
            'atc'=>'Attachment',
            'prd'=>'Product Download',
            'spr'=>'Specific Price Rule',
            'spg'=> 'Specific Price Rule Condition Group',
            'spc'=>'Specific Price Rule Condition',
            'ag'=>'Attribute Group',
            'a'=>'Attribute',
            'com'=>'Combination',
            's'=>'Supplier',
            'm'=>'Manufacturer',
            'sp'=>'Specific Price',
            'i'=>'Image',
            'f'=>'Feature',
            'fv'=>'Feature Value',
            'cf'=>'Customization Field',
            'tag'=>'Tag',
            'cus'=>'Customer',
            'ct'=>'Customer Thread',
            'cm'=>'Customer Message',
            'car'=>'Cart',
            'e'=>'Employee',
            'adr'=>'Address',
            'o'=>'Order',
            'od'=>'Order Detail',
            'ort'=>'Order Return',
            'oh'=>'Order History',
            'osp'=>'Order Slip',
            'oi'=>'Order Invoice',
            'oc'=>'Order Carrier',
            'ocr'=>'Order Cart Rule',
            'op'=>'Order Payment',
            'om'=>'Order Message',
            'mes'=>'Message',
            'sa'=> 'Stock Available',
            'ps' => 'Product Supplier',
            'cms'=> 'CMS',
            'cro' => 'CMS Role',
            'ctg' => 'CMS Category',
            'cbl' => 'CMS Block',
            'cr' => 'Cart Rule',
            'cpg' => 'Cart Rule Product Rule Group',
            'cpr' => 'Cart Rule Product Rule',
            'met' => 'Meta',
            'war' => 'Warehouse',
            'stk' => 'Stock',
            'wpl' => 'Ware House Location',
            'zn' => 'Zone',
            'dlv' => 'Delivery',
            'rn' => 'Range Price',
            'rw' => 'Range Weight',
        );

        return $entityTypeAliasesAndNames;
    }
}
