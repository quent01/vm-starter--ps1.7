<?php
/**
 * 2007-2019 ETS-Soft
 *
 * NOTICE OF LICENSE
 *
 * This file is not open source! Each license that you purchased is only available for 1 wesite only.
 * If you want to use this file on more websites (or projects), you need to purchase additional licenses.
 * You are not allowed to redistribute, resell, lease, license, sub-license or offer our resources to any third party.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade PrestaShop to newer
 * versions in the future. If you wish to customize PrestaShop for your
 * needs please contact us for extra customization service at an affordable price
 *
 * @author ETS-Soft <etssoft.jsc@gmail.com>
 * @copyright  2007-2019 ETS-Soft
 * @license    Valid for 1 website (or project) for each purchase of license
 *  International Registered Trademark & Property of ETS-Soft
 */

include_once(_PS_MODULE_DIR_ . 'ets_woo2pres/classes/ReadXMl.php');

class Woo2PressDataImport extends Module
{
    public $_module;

    public function __construct()
    {
        parent::__construct();
        if (!(isset($this->context)) || !$this->context)
            $this->context = Context::getContext();
        $this->_module = Module::getInstanceByName('ets_woo2pres');
    }

    public function importData($file_xml, $class_name, $definition, $foreign_key = array(), $multishop = false)
    {
        if (!isset($definition['table']))
            return true;
        $start_time = time();
        $definition['multilang_temp'] = isset($definition['multilang_shop']) ? $definition['multilang_shop'] : false;
        if ($definition['table'] == 'product_attribute' || $definition['table'] == 'tax_rules_group' || $definition['table'] == 'manufacturer' || $definition['table'] == 'supplier' || $definition['table'] == 'group' || $definition['table'] == 'feature' || $definition['table'] == 'attribute_group' || $definition['table'] == 'attribute' || $definition['table'] == 'image' || $definition['table'] == 'employee' || $definition['table'] == 'cms' || $definition['table'] == 'country' || $definition['table'] == 'contact' || $definition['table'] == 'zone') {
            $definition['multilang_shop'] = true;
        }
        if ($class_name == 'CustomizationField')
            $definition['multilang_shop'] = false;

        $table = $definition['table'];
        $primary = $definition['primary'];
        $fields = $definition['fields'];
        $id_import_history = Context::getContext()->cookie->id_import_history;
        $import_history = new Woo2PressImportHistory($id_import_history);
        $readXml = new Woo2PressReadXML($file_xml);
        $languages = Language::getLanguages(false);
        while ($xml = $readXml->_readXML()) {
            if (isset($xml->$table) && $xml->$table) {
                $check_import = Db::getInstance()->getRow('SELECT * FROM ' . _DB_PREFIX_ . 'ets_woo2press_' . pSQL($table) . '_import where id_import_history=' . (int)$id_import_history);
                if (!$check_import) {
                    if ($import_history->delete_before_importing && $class_name != 'Zone' && $class_name != 'Currency' && $class_name != 'Language' && $class_name != 'ShopGroup' && $class_name != 'Shop' && $class_name != 'ShopUrl' && $class_name != 'Employee' && $class_name != 'Country') {
                        try {
                            if ($class_name == 'Group') {
                                $groups_to_keep = array(
                                    Configuration::get('PS_UNIDENTIFIED_GROUP'),
                                    Configuration::get('PS_GUEST_GROUP'),
                                    Configuration::get('PS_CUSTOMER_GROUP')
                                );
                                $add_group = ' AND id_group NOT IN (' . implode(',', $groups_to_keep) . ')';
                            } elseif ($class_name == 'OrderState')
                                $add_group = 'AND id_order_state > 14';
                            else
                                $add_group = '';
                            if ($multishop || (Configuration::get('PS_MULTISHOP_FEATURE_ACTIVE') && Shop::getContext() == Shop::CONTEXT_ALL) || (!$multishop && !Configuration::get('PS_MULTISHOP_FEATURE_ACTIVE'))) {
                                if ($class_name == 'Carrier') {
                                    Db::getInstance()->execute('UPDATE ' . _DB_PREFIX_ . 'carrier SET deleted=1');
                                } else {
                                    Db::getInstance()->execute('DELETE FROM ' . _DB_PREFIX_ . pSQL($table) . ' WHERE 1 ' . $add_group . ($class_name == 'Category' ? ' AND id_parent!=0 AND is_root_category!=1' : '') . ($class_name == 'CMSCategory' ? ' AND id_parent!=0' : '') . '');
                                    if (isset($definition['multilang']) && $definition['multilang'])
                                        Db::getInstance()->execute('DELETE  FROM ' . _DB_PREFIX_ . pSQL($table) . '_lang WHERE 1 ' . $add_group . ($class_name == 'CMSCategory' ? ' AND id_cms_category NOT IN (SELECT id_cms_category FROM ' . _DB_PREFIX_ . 'cms_category WHERE id_parent=0)' : '') . ($class_name == 'Category' ? ' AND id_category NOT IN (SELECT id_category FROM ' . _DB_PREFIX_ . 'category WHERE id_parent=0 OR is_root_category=1)' : '') . '');
                                    if (isset($definition['multilang_shop']) && $definition['multilang_shop'])
                                        Db::getInstance()->execute('DELETE FROM ' . _DB_PREFIX_ . pSQL($table) . '_shop WHERE 1 ' . $add_group . ($class_name == 'CMSCategory' ? ' AND id_cms_category NOT IN (SELECT id_cms_category FROM ' . _DB_PREFIX_ . 'cms_category WHERE id_parent=0)' : '') . ($class_name == 'Category' ? ' AND id_category NOT IN (SELECT id_category FROM ' . _DB_PREFIX_ . 'category WHERE id_parent=0 OR is_root_category=1)' : '') . '');
                                    if ($class_name != 'Category' && $class_name != 'Cart' && $class_name != 'CMSCategory')
                                        Db::getInstance()->execute('ALTER TABLE ' . _DB_PREFIX_ . pSQL($table) . ' AUTO_INCREMENT = 1');
                                    elseif ($class_name == 'Category') {
                                        $max_id_category = (int)Db::getInstance()->getValue('SELECT MAX(id_category) FROM ' . _DB_PREFIX_ . 'category', 0);
                                        $max_id_category++;
                                        Db::getInstance()->execute('ALTER TABLE ' . _DB_PREFIX_ . pSQL($table) . ' AUTO_INCREMENT = ' . (int)$max_id_category);
                                    } elseif ($class_name == 'CMSCategory') {
                                        $max_id_category = (int)Db::getInstance()->getValue('SELECT MAX(id_cms_category) FROM ' . _DB_PREFIX_ . 'cms_category', 0);
                                        $max_id_category++;
                                        Db::getInstance()->execute('ALTER TABLE ' . _DB_PREFIX_ . pSQL($table) . ' AUTO_INCREMENT = ' . (int)$max_id_category);
                                    }
                                    if ($definition['table'] == 'group')
                                        Db::getInstance()->execute('delete from ' . _DB_PREFIX_ . 'category_group');
                                    if ($class_name == 'Product') {
                                        if ($multishop || (Configuration::get('PS_MULTISHOP_FEATURE_ACTIVE') && Shop::getContext() == Shop::CONTEXT_ALL)) {
                                            Db::getInstance()->execute('DELETE FROM ' . _DB_PREFIX_ . 'product_attribute_shop');
                                        } else {
                                            Db::getInstance()->execute('DELETE FROM ' . _DB_PREFIX_ . 'product_attribute_shop WHERE id_shop = ' . (int)Context::getContext()->shop->id);
                                        }
                                        Db::getInstance()->execute('DELETE FROM ' . _DB_PREFIX_ . 'category_product');
                                        Db::getInstance()->execute('DELETE FROM ' . _DB_PREFIX_ . 'product_supplier');
                                        Db::getInstance()->execute('DELETE FROM ' . _DB_PREFIX_ . 'product_tag');
                                        Db::getInstance()->execute('DELETE FROM ' . _DB_PREFIX_ . 'feature_product');
                                        Db::getInstance()->execute('DELETE FROM ' . _DB_PREFIX_ . 'product_attribute WHERE id_product NOT IN (SELECT id_product FROM ' . _DB_PREFIX_ . 'product_attribute_shop)');
                                        Db::getInstance()->execute('DELETE FROM ' . _DB_PREFIX_ . 'product_attribute_combination');
                                        Db::getInstance()->execute('DELETE FROM ' . _DB_PREFIX_ . 'accessory');
                                        if (!file_exists(dirname(__FILE__) . '/../xml/' . $import_history->file_name . '/StockAvailable.xml') && !file_exists(dirname(__FILE__) . '/../xml/' . $import_history->file_name . '/StockAvailable_1.xml')) {
                                            Db::getInstance()->execute('DELETE FROM ' . _DB_PREFIX_ . 'stock_available');
                                        }
                                    }
                                    if ($class_name == 'Combination') {
                                        Db::getInstance()->execute('DELETE FROM ' . _DB_PREFIX_ . 'product_attribute_combination');
                                    }
                                }
                            } else {
                                if ($definition['multilang_shop'])
                                    $add = ' AND ' . pSQL($primary) . ' NOT IN (SELECT ' . pSQL($primary) . ' FROM ' . _DB_PREFIX_ . pSQL($table) . '_shop WHERE id_shop!="' . (int)Context::getContext()->shop->id . '" )';
                                else
                                    $add = '';
                                if ($class_name == 'Carrier') {
                                    Db::getInstance()->execute('UPDATE ' . _DB_PREFIX_ . 'carrier SET deleted=1 WHERE 1 ' . $add);
                                } else {
                                    Db::getInstance()->execute('DELETE FROM ' . _DB_PREFIX_ . pSQL($table) . ' WHERE 1 ' . $add . $add_group . ($class_name == 'CMSCategory' ? ' AND id_parent!=0' : '') . ($class_name == 'Category' ? ' AND id_parent!=0 AND is_root_category!=1' : '') . ($class_name == 'StockAvailable' || $class_name == "Order" ? ' AND id_shop="' . (int)Context::getContext()->shop->id . '"' : ''));
                                    if (isset($definition['multilang']) && $definition['multilang'])
                                        Db::getInstance()->execute('DELETE  FROM ' . _DB_PREFIX_ . pSQL($table) . '_lang WHERE 1 ' . ($definition['multilang_temp'] ? ' AND id_shop="' . (int)Context::getContext()->shop->id . '"' : '') . $add_group . ($class_name == 'CMSCategory' ? ' AND id_cms_category NOT IN (SELECT id_cms_category FROM ' . _DB_PREFIX_ . 'cms_category WHERE id_parent=0)' : '') . ($class_name == 'Category' ? ' AND id_category NOT IN (SELECT id_category FROM ' . _DB_PREFIX_ . 'category WHERE id_parent=0 OR is_root_category=1)' : ''));
                                    if (isset($definition['multilang_shop']) && $definition['multilang_shop'])
                                        Db::getInstance()->execute('DELETE FROM ' . _DB_PREFIX_ . pSQL($table) . '_shop WHERE id_shop="' . (int)Context::getContext()->shop->id . '" ' . $add_group . ($class_name == 'CMSCategory' ? ' AND id_cms_category NOT IN (SELECT id_cms_category FROM ' . _DB_PREFIX_ . 'cms_category WHERE id_parent=0)' : '') . ($class_name == 'Category' ? ' AND id_category NOT IN (SELECT id_category FROM ' . _DB_PREFIX_ . 'category WHERE id_parent=0 OR is_root_category=1)' : ''));
                                    if ($definition['table'] == 'group')
                                        Db::getInstance()->execute('delete from ' . _DB_PREFIX_ . 'category_group WHERE 1' . $add . $add_group);
                                    if ($class_name == 'Product') {
                                        Db::getInstance()->execute('DELETE FROM ' . _DB_PREFIX_ . 'category_product WHERE 1' . $add);
                                        Db::getInstance()->execute('DELETE FROM ' . _DB_PREFIX_ . 'product_supplier WHERE 1' . $add);
                                        Db::getInstance()->execute('DELETE FROM ' . _DB_PREFIX_ . 'feature_product WHERE 1' . $add);
                                        Db::getInstance()->execute('DELETE FROM ' . _DB_PREFIX_ . 'product_attribute WHERE 1' . $add);
                                        Db::getInstance()->execute('DELETE FROM ' . _DB_PREFIX_ . 'product_attribute_combination');
                                        Db::getInstance()->execute('DELETE FROM ' . _DB_PREFIX_ . 'accessory' . $add);
                                    }
                                }
                            }
                            if ($class_name == 'Order') {
                                Db::getInstance()->execute('DELETE FROM ' . _DB_PREFIX_ . 'order_detail WHERE id_order NOT IN (SELECT id_order from ' . _DB_PREFIX_ . 'orders)');
                            }
                        } catch (Exception $exception) {
                            die('error');
                        }
                    } elseif ($class_name == 'SpecificPrice') {
                        Db::getInstance()->execute('DELETE FROM ' . _DB_PREFIX_ . pSQL($table) . ' WHERE id_product in (SELECT id_new FROM ' . _DB_PREFIX_ . 'ets_woo2press_product_import WHERE id_import_history="' . (int)$id_import_history . '") AND ' . pSQL($primary) . ' NOT IN (SELECT id_new FROM ' . _DB_PREFIX_ . 'ets_woo2press_' . pSQL($table) . '_import WHERE id_import_history ="' . (int)$id_import_history . '")');
                    }
                }
                foreach ($xml->$table as $data_xml) {
                    $object_id = 0;

                    $imported = (int)Db::getInstance()->getValue('SELECT number_import FROM ' . _DB_PREFIX_ . 'ets_woo2press_import_history WHERE id_import_history=' . (int)$id_import_history, 0);
                    if (!(int)Db::getInstance()->getValue('SELECT id_new FROM ' . _DB_PREFIX_ . 'ets_woo2press_' . pSQL($table) . '_import WHERE id_old=' . (int)$data_xml->$primary . ' AND id_import_history="' . (int)$id_import_history . '"', false)) {
                        if ($class_name == 'Employee') {
                            if ($id_employee = (int)Db::getInstance()->getValue('SELECT id_employee FROM ' . _DB_PREFIX_ . 'employee WHERE email="' . pSQL((string)$data_xml->email) . '"', 0)) {
                                Db::getInstance()->execute('INSERT INTO ' . _DB_PREFIX_ . 'ets_woo2press_' . pSQL($table) . '_import values("","' . (int)$data_xml->$primary . '","' . (int)$id_employee . '","' . (int)$id_import_history . '")');
                                $imported++;
                                Db::getInstance()->execute('UPDATE ' . _DB_PREFIX_ . 'ets_woo2press_import_history SET number_import ="' . (int)$imported . '" WHERE id_import_history=' . (int)$id_import_history);
                                continue;
                            }
                        }
                        if ($class_name == 'Customer' && !(int)$data_xml->is_guest && !(int)$data_xml->deleted) {
                            if ($id_customer = (int)Db::getInstance()->getValue('SELECT id_customer FROM ' . _DB_PREFIX_ . 'customer WHERE email="' . pSQL((string)$data_xml->email) . '" AND is_guest=0 AND deleted=0', 0)) {
                                Db::getInstance()->execute('INSERT INTO ' . _DB_PREFIX_ . 'ets_woo2press_' . pSQL($table) . '_import values("","' . (int)$data_xml->$primary . '","' . (int)$id_customer . '","' . (int)$id_import_history . '")');
                                $imported++;
                                Db::getInstance()->execute('UPDATE ' . _DB_PREFIX_ . 'ets_woo2press_import_history SET number_import ="' . (int)$imported . '" WHERE id_import_history=' . (int)$id_import_history);
                                continue;
                            }
                        }
                        $imported = (int)Db::getInstance()->getValue('SELECT number_import FROM ' . _DB_PREFIX_ . 'ets_woo2press_import_history WHERE id_import_history=' . (int)$id_import_history, 0);

                        if ($class_name != 'Zone' && $class_name != 'Language' && $class_name != 'Currency' && $class_name != 'ShopGroup' && $class_name != 'Shop' && $class_name != 'ShopUrl' && $class_name != 'Employee' && $class_name != 'Country' && $class_name != 'Carrier') {
                            if ($import_history->force_all_id_number && $class_name != 'StockAvailable') {

                                if (isset($definition['multilang_shop']) && $definition['multilang_shop'] && !$multishop) {
                                    $object_id = (int)Db::getInstance()->getValue('SELECT tb.' . pSQL($primary) . ' FROM ' . _DB_PREFIX_ . pSQL($table) . ' tb, ' . _DB_PREFIX_ . pSQL($table) . '_shop tbs WHERE tb.' . pSQL($primary) . '=' . (int)$data_xml->$primary . ' AND tb.' . pSQL($primary) . ' = tbs.' . pSQL($primary) . ' AND tbs.id_shop=' . (int)Context::getContext()->shop->id);
                                } else
                                    $object_id = (int)Db::getInstance()->getValue('SELECT ' . pSQL($primary) . ' FROM ' . _DB_PREFIX_ . pSQL($table) . ' WHERE ' . pSQL($primary) . '=' . (int)$data_xml->$primary);

                                if ($class_name == 'Category' && !isset($data_xml->is_root_category)) {
                                    $object_id = 0;
                                }
                            }
                        } else {
                            if ($class_name == 'Language') {
                                $object_id = (int)Db::getInstance()->getValue('SELECT id_lang FROM ' . _DB_PREFIX_ . 'lang WHERE iso_code="' . pSQL((string)$data_xml->iso_code) . '"', 0);
                                if ($object_id) {
                                    Db::getInstance()->execute('INSERT INTO ' . _DB_PREFIX_ . 'ets_woo2press_' . pSQL($table) . '_import values("","' . (int)$data_xml->$primary . '","' . (int)$object_id . '","' . (int)$id_import_history . '")');
                                    $imported++;
                                    Db::getInstance()->execute('UPDATE ' . _DB_PREFIX_ . 'ets_woo2press_import_history SET number_import ="' . (int)$imported . '" WHERE id_import_history=' . (int)$id_import_history);
                                    continue;
                                }
                            } elseif ($class_name == 'Currency') {
                                $object_id = (int)Db::getInstance()->getValue('SELECT id_currency FROM ' . _DB_PREFIX_ . 'currency WHERE iso_code="' . pSQL((string)$data_xml->iso_code) . '"', 0);
                            } elseif ($class_name == 'Zone') {
                                $object_id = (int)Db::getInstance()->getValue('SELECT id_zone FROM ' . _DB_PREFIX_ . 'zone WHERE name="' . pSQL((string)$data_xml->name) . '"', 0);
                            } elseif ($class_name == 'Country') {
                                $object_id = (int)Db::getInstance()->getValue('SELECT id_country FROM ' . _DB_PREFIX_ . 'country WHERE iso_code="' . pSQL((string)$data_xml->iso_code) . '"', 0);
                            } elseif ($class_name != 'Employee' && $class_name != 'Carrier')
                                $object_id = (int)Db::getInstance()->getValue('SELECT ' . pSQL($primary) . ' FROM ' . _DB_PREFIX_ . pSQL($table) . ' WHERE ' . pSQL($primary) . '=' . (int)$data_xml->$primary, 0);
                            if ($import_history->force_all_id_number && !$object_id && $class_name != 'Employee' && $class_name != 'Carrier') {
                                if (isset($definition['multilang_shop']) && $definition['multilang_shop'] && !$multishop) {
                                    $object_id = (int)Db::getInstance()->getValue('SELECT tb.' . pSQL($primary) . ' FROM ' . _DB_PREFIX_ . pSQL($table) . ' tb, ' . _DB_PREFIX_ . pSQL($table) . '_shop tbs WHERE tb.' . pSQL($primary) . '=' . (int)$data_xml->$primary . ' AND tb.' . pSQL($primary) . ' = tbs.' . pSQL($primary) . ' AND tbs.id_shop=' . (int)Context::getContext()->shop->id);
                                } else
                                    $object_id = (int)Db::getInstance()->getValue('SELECT ' . pSQL($primary) . ' FROM ' . _DB_PREFIX_ . pSQL($table) . ' WHERE ' . pSQL($primary) . '=' . (int)$data_xml->$primary);
                            }

                        }
                        if ($class_name == 'Group') {
                            if (isset($data_xml->visitor_group) && (int)$data_xml->visitor_group)
                                $object_id = (int)Configuration::get('PS_UNIDENTIFIED_GROUP');
                            if (isset($data_xml->guest_group) && (int)$data_xml->guest_group)
                                $object_id = (int)Configuration::get('PS_GUEST_GROUP');
                            if (isset($data_xml->customer_group) && (int)$data_xml->customer_group)
                                $object_id = (int)Configuration::get('PS_CUSTOMER_GROUP');
                            if (isset($data_xml->default_group) && (int)$data_xml->default_group)
                                $object_id = (int)Configuration::get('PS_CUSTOMER_GROUP');
                            if (isset($object_id) && $object_id) {
                                Db::getInstance()->execute('INSERT INTO ' . _DB_PREFIX_ . 'ets_woo2press_' . pSQL($table) . '_import values("","' . (int)$data_xml->$primary . '","' . (int)$object_id . '","' . (int)$id_import_history . '")');
                                $imported++;
                                Db::getInstance()->execute('UPDATE ' . _DB_PREFIX_ . 'ets_woo2press_import_history SET number_import ="' . (int)$imported . '" WHERE id_import_history=' . (int)$id_import_history);
                                continue;
                            }
                        }
                        if ($class_name == 'CMSCategory') {
                            if ((int)$data_xml->id_parent == 0 && !isset($data_xml->woocommerce)) {
                                $object_id = (int)Db::getInstance()->getValue('SELECT id_cms_category FROM ' . _DB_PREFIX_ . 'cms_category WHERE id_parent=0', 0);
                                if ($object_id) {
                                    Db::getInstance()->execute('INSERT INTO ' . _DB_PREFIX_ . 'ets_woo2press_' . pSQL($table) . '_import values("","' . (int)$data_xml->$primary . '","' . (int)$object_id . '","' . (int)$id_import_history . '")');
                                    $imported++;
                                    Db::getInstance()->execute('UPDATE ' . _DB_PREFIX_ . 'ets_woo2press_import_history SET number_import ="' . (int)$imported . '" WHERE id_import_history=' . (int)$id_import_history);
                                    continue;
                                }
                            }
                        }
                        if ($class_name == 'Category') {
                            if (isset($data_xml->is_root_category)) {
                                if ((int)$data_xml->is_root_category) {
                                    $object_id = (int)Db::getInstance()->getValue('SELECT id_category FROM ' . _DB_PREFIX_ . 'category WHERE is_root_category=1', 0);
                                    Db::getInstance()->execute('INSERT INTO ' . _DB_PREFIX_ . 'ets_woo2press_' . pSQL($table) . '_import values("","' . (int)$data_xml->$primary . '","' . (int)$object_id . '","' . (int)$id_import_history . '")');
                                    $imported++;
                                    Db::getInstance()->execute('UPDATE ' . _DB_PREFIX_ . 'ets_woo2press_import_history SET number_import ="' . (int)$imported . '" WHERE id_import_history=' . (int)$id_import_history);
                                    continue;
                                }
                                if ((int)$data_xml->id_parent == 0) {
                                    $object_id = (int)Db::getInstance()->getValue('SELECT id_category FROM ' . _DB_PREFIX_ . 'category WHERE id_parent=0', 0);
                                }
                            } else {
                                if ((int)$data_xml->id_parent == 0 && !isset($data_xml->woocommerce)) {
                                    $object_id = (int)Db::getInstance()->getValue('SELECT id_category FROM ' . _DB_PREFIX_ . 'category WHERE is_root_category=1', 0);
                                    Db::getInstance()->execute('INSERT INTO ' . _DB_PREFIX_ . 'ets_woo2press_' . pSQL($table) . '_import values("","' . (int)$data_xml->$primary . '","' . (int)$object_id . '","' . (int)$id_import_history . '")');
                                    $imported++;
                                    Db::getInstance()->execute('UPDATE ' . _DB_PREFIX_ . 'ets_woo2press_import_history SET number_import ="' . (int)$imported . '" WHERE id_import_history=' . (int)$id_import_history);
                                    continue;
                                }
                            }
                        }
                        if (isset($object_id) && $object_id) {
                            $object = new $class_name($object_id);
                        } else
                            $object = new $class_name();
                        if ($class_name == 'Country' && isset($data_xml->woocommerce) && $object_id) {
                            $object->active = (int)$data_xml->active;
                            $object->id_zone = (int)$data_xml->id_zone;
                        } elseif ($fields) {
                            foreach ($fields as $key => $field) {
                                if (!isset($field['lang']) || !$field['lang']) {
                                    if ($class_name == 'Order') {
                                        if (($key == 'total_paid_tax_incl' || $key == 'total_paid_tax_excl') && !isset($data_xml->total_paid_tax_incl)) {
                                            $object->$key = $data_xml->total_paid;
                                        }
                                        if (($key == 'total_shipping_tax_incl' || $key == 'total_shipping_tax_excl') && !isset($data_xml->total_shipping_tax_incl)) {
                                            $object->$key = $data_xml->total_shipping;
                                        }
                                        if (($key == 'total_discounts_tax_incl' || $key == 'total_discounts_taxt_excl') && !isset($data_xml->total_discounts_tax_incl)) {
                                            $object->$key = $data_xml->total_discounts;
                                        }
                                    }
                                    if ((!isset($field['required']) || !$field['required']) && (!isset($data_xml->$key) || (string)$data_xml->$key == ''))
                                        continue;
                                    if (isset($field['size']) && $field['size'] < Tools::strlen($data_xml->$key))
                                        $object->$key = Tools::substr((string)$data_xml->$key, 0, $field['size']);
                                    else
                                        $object->$key = (string)$data_xml->$key;
                                    if (isset($field['required']) && $field['required'] && $object->$key == '' && $key != 'id_product_attribute') {
                                        if ($key == 'sign')
                                            $object->$key = '$';
                                        elseif($class_name == 'SpecificPrice' && $key == 'reduction_tax')
                                            $object->$key = 1;
                                        elseif($class_name == 'SpecificPrice')
                                            $object->$key = '0';
                                        else
                                            $object->$key = '1';
                                    }
                                    if (isset($field['validate']) && $field['validate'] && method_exists('Validate', $field['validate']) && $object->$key != '' && $object->$key != '0000-00-00') {
                                        $validate = $field['validate'];
                                        if (!Validate::$validate($object->$key)) {
                                            if ($key == 'secure_key' && $class_name == 'Order')
                                                $object->$key = md5('secure' . rand() . time());
                                            else
                                                $object->$key = '';
                                        }
                                        unset($validate);
                                    }
                                }
                            }
                        }
                        //multilang.
                        if (isset($definition['multilang']) && $definition['multilang']) {
                            if (!isset($data_xml->datalanguage) || !$data_xml->datalanguage)
                                continue;
                            $language_xml_default = null;
                            foreach ($data_xml->datalanguage as $language_xml) {
                                if ((isset($language_xml['default']) && (int)$language_xml['default']) || !isset($language_xml['iso_code'])) {
                                    $language_xml_default = $language_xml;
                                    break;
                                }
                            }
                            $list_language_xml = array();
                            foreach ($data_xml->datalanguage as $language_xml) {
                                $iso_code = isset($language_xml['iso_code']) ? (string)$language_xml['iso_code'] : '';
                                if ($iso_code) {
                                    $id_lang = Language::getIdByIso($iso_code);
                                    $list_language_xml[] = $id_lang;
                                } else
                                    $id_lang = 0;
                                if ($id_lang) {
                                    foreach ($fields as $key => $field) {
                                        if (isset($field['lang']) && $field['lang']) {
                                            $temp = $object->$key;
                                            if (isset($field['size']) && $field['size'] && $field['size'] < Tools::strlen((string)$language_xml->$key))
                                                $temp[$id_lang] = Tools::substr((string)$language_xml->$key, 0, $field['size']);
                                            else
                                                $temp[$id_lang] = (string)$language_xml->$key;
                                            if (isset($field['required']) && $field['required'] && !$temp[$id_lang]) {
                                                if (isset($language_xml_default) && $language_xml_default && isset($language_xml_default->$key) && (string)$language_xml_default->$key) {
                                                    $temp[$id_lang] = (string)$language_xml_default->$key;
                                                } else
                                                    $temp[$id_lang] = $this->l('Empty');
                                            }
                                            if (isset($field['validate']) && $field['validate'] && method_exists('Validate', $field['validate'])) {
                                                $validate = $field['validate'];
                                                if (isset($temp[$id_lang]) && !Validate::$validate($temp[$id_lang])) {
                                                    $temp[$id_lang] = $this->l('Empty');
                                                }
                                                unset($validate);
                                            }
                                            $object->$key = $temp;
                                        }
                                    }
                                }
                            }
                            foreach ($languages as $language) {
                                if (!in_array($language['id_lang'], $list_language_xml)) {
                                    foreach ($fields as $key => $field) {
                                        if (isset($field['lang']) && $field['lang']) {
                                            $temp = $object->$key;
                                            if (isset($field['required']) && $field['required'] && (!isset($temp[$language['id_lang']]) || !$temp[$language['id_lang']])) {
                                                if (isset($language_xml_default) && $language_xml_default && isset($language_xml_default->$key) && (string)$language_xml_default->$key) {
                                                    if (isset($field['size']) && $field['size'] && $field['size'] < Tools::strlen((string)$language_xml_default->$key))
                                                        $temp[$language['id_lang']] = Tools::substr((string)$language_xml_default->$key, 0, $field['size']);
                                                    else
                                                        $temp[$language['id_lang']] = (string)$language_xml_default->$key;
                                                } else
                                                    $temp[$language['id_lang']] = $this->l('Empty');
                                            }
                                            if (isset($field['validate']) && $field['validate'] && method_exists('Validate', $field['validate'])) {
                                                $validate = $field['validate'];
                                                if (isset($temp[$language['id_lang']]) && !Validate::$validate($temp[$language['id_lang']])) {
                                                    $temp[$language['id_lang']] = $this->l('Empty');
                                                }
                                                unset($validate);
                                            }
                                            $object->$key = $temp;
                                        }
                                    }
                                }
                            }
                        }
                        if ($class_name == 'Shop') {
                            if (version_compare(_PS_VERSION_, '1.7', '>=')) {
                                if (!isset($data_xml->theme_name) || (isset($data_xml->theme_name) && !is_dir(dirname(__FILE__) . '/../../../themes/' . (string)$data_xml->theme_name))) {
                                    $object->theme_name = 'classic';
                                }
                            } else {
                                if (!(int)Db::getInstance()->getValue('SELECT id_theme FROM ' . _DB_PREFIX_ . 'theme WHERE id_theme=' . (int)$object->id_theme, 0)) {
                                    $object->id_theme = (int)Db::getInstance()->getValue('SELECT id_theme FROM ' . _DB_PREFIX_ . 'theme', 0);
                                }
                            }
                        }
                        if ($foreign_key) {
                            foreach ($foreign_key as $key => $value) {
                                if (isset($object->$key) && $object->$key) {
                                    if ($key_extra = (int)Db::getInstance()->getValue('SELECT id_new FROM ' . _DB_PREFIX_ . 'ets_woo2press_' . pSQL($value['table_parent']) . '_import WHERE id_old=' . (int)$object->$key . ' AND id_import_history="' . (int)$id_import_history . '"', 0)) {
                                        $object->$key = $key_extra;
                                    } elseif (!Db::getInstance()->getRow('SELECT id_new FROM ' . _DB_PREFIX_ . 'ets_woo2press_' . pSQL($value['table_parent']) . '_import WHERE id_import_history="' . (int)$id_import_history . '"')) {
                                        if ($value['table_parent'] == 'category') {
                                            if ($key == 'id_category_default')
                                                $object->$key = $import_history->id_category_default;
                                        } elseif ($value['table_parent'] == 'supplier') {
                                            $object->$key = (int)$import_history->id_supplier;
                                        } elseif ($value['table_parent'] == 'manufacturer')
                                            $object->$key = (int)$import_history->id_manufacture;
                                        elseif ($value['table_parent'] == 'cms_category')
                                            $object->$key = (int)$import_history->id_category_cms;
                                        elseif ($import_history->force_all_id_number)
                                            $object->$key = (int)Db::getInstance()->getValue('SELECT ' . pSQL($value['key']) . ' FROM ' . _DB_PREFIX_ . pSQL($value['table_parent']) . ' WHERE ' . pSQL($value['key']) . '=' . (int)$object->$key, 0);
                                        elseif ($key == 'id_shop' && $class_name != 'Delivery')
                                            $object->$key = (int)Context::getContext()->shop->id;
                                    } else
                                        $object->$key = '0';
                                    if ($key == 'id_employee')
                                        $object->$key = Context::getContext()->employee->id;
                                } elseif ($key == 'id_shop' && $class_name != 'Delivery')
                                    $object->$key = (int)Context::getContext()->shop->id;
                                elseif ($key == 'id_parent' && $class_name == 'Category' && isset($data_xml->woocommerce))
                                    $object->$key = (int)Db::getInstance()->getValue('SELECT id_category FROM ' . _DB_PREFIX_ . 'category WHERE is_root_category=1', 0);
                                elseif ($key == 'id_parent' && $class_name == 'CMSCategory' && isset($data_xml->woocommerce)) {
                                    $object->$key = (int)Db::getInstance()->getValue('SELECT id_cms_category FROM ' . _DB_PREFIX_ . 'cms_category WHERE id_parent=0', 0);
                                } elseif ($key == 'id_lang')
                                    $object->$key = (int)Context::getContext()->language->id;
                                elseif ($key == 'id_currency') {
                                    $object->$key = (int)Configuration::get('PS_CURRENCY_DEFAULT');
                                } elseif ($key == 'id_address_delivery') {
                                    if ($id_address_delivery = (int)Db::getInstance()->getValue('SELECT id_address FROM ' . _DB_PREFIX_ . 'address WHERE alias = "deleted"', false)) {
                                        $object->$key = $id_address_delivery;
                                    } else {
                                        $id_country = (int)Configuration::get('PS_COUNTRY_DEFAULT');
                                        $id_state = (int)Db::getInstance()->getValue('SELECT id_state FROM `' . _DB_PREFIX_ . 'state` s WHERE s.`id_country` = ' . (int)$id_country, false);
                                        if ((bool)Db::getInstance()->execute("
                                                INSERT INTO " . _DB_PREFIX_ . "address(id_country, id_state, id_customer, id_manufacturer, id_supplier, id_warehouse, alias, company, lastname, firstname, address1, address2, postcode, city, other, phone, phone_mobile, vat_number, dni, date_add, date_upd, active, deleted) 
                                                VALUES (" . (int)$id_country . ", " . (int)$id_state . ", 0, 0, 0, 0, 'deleted', 'deleted', 'deleted', 'deleted', 'deleted', 'deleted', '', 'deleted', '', '123456789', '', '', '', now(), now(), 1, 1)"
                                            , false)) {
                                            $object->$key = (int)Db::getInstance()->Insert_ID();
                                        }
                                    }
                                }
                            }
                        }
                        if ($class_name == 'Language' && isset($object->locale) && !$object->locale)
                            $object->locale = 'en-US';
                        if ($class_name == 'OrderHistory' && !$object->id_order) {
                            $imported++;
                            Db::getInstance()->execute('UPDATE ' . _DB_PREFIX_ . 'ets_woo2press_import_history SET number_import ="' . (int)$imported . '" WHERE id_import_history=' . (int)$id_import_history);
                            continue;
                        }

                        if ($class_name == 'Customer' && $object->is_guest == 0) {
                            if (Configuration::get('ETS_WOO2PRESS_NEW_PASSWD')) {
                                $new_passwd = $this->_module->genSecure(8);
                                $object->passwd = md5(pSQL(_COOKIE_KEY_ . $new_passwd));
                                Db::getInstance()->execute('INSERT INTO ' . _DB_PREFIX_ . 'ets_woo2press_customer_pasword(id_import_history,first_name,last_name,email,passwd) VALUES("' . (int)$id_import_history . '","' . pSQL($object->firstname) . '","' . pSQL($object->lastname) . '","' . pSQL($object->email) . '","' . pSQL($new_passwd) . '")');
                            } else {
                                Db::getInstance()->execute('ALTER TABLE `' . _DB_PREFIX_ . 'customer` ADD COLUMN IF NOT EXISTS `passwd_old_wp` VARCHAR(60) NOT NULL ');
                            }
                        }
                        if ($class_name == 'Employee') {
                            if (Configuration::get('ETS_WOO2PRESS_NEW_PASSWD')) {
                                $new_passwd = $this->_module->genSecure(8);
                                $object->passwd = md5(pSQL(_COOKIE_KEY_ . $new_passwd));
                                Db::getInstance()->execute('INSERT INTO ' . _DB_PREFIX_ . 'ets_woo2press_employee_pasword(id_import_history,first_name,last_name,email,passwd) VALUES("' . (int)$id_import_history . '","' . pSQL($object->firstname) . '","' . pSQL($object->lastname) . '","' . pSQL($object->email) . '","' . pSQL($new_passwd) . '")');
                            } else {
                                Db::getInstance()->execute('ALTER TABLE `' . _DB_PREFIX_ . 'employee` ADD COLUMN IF NOT EXISTS `passwd_old_wp` VARCHAR(60) NOT NULL ');
                            }
                        }
                        if ($class_name == 'CustomizationField') {
                            Configuration::updateValue('PS_CUSTOMIZATION_FEATURE_ACTIVE', 1);
                        }
                        if ($file_xml == 'page_cms') {
                            $object->id_cms_category = (int)Db::getInstance()->getValue('SELECT id_cms_category FROM ' . _DB_PREFIX_ . 'cms_category WHERE id_parent=0', 0);
                        }
                        if ($fields) {
                            $ok = true;
                            foreach ($fields as $key => $field) {
                                if (!isset($field['lang']) || !$field['lang']) {
                                    if (isset($field['required']) && $field['required'] && $object->$key == '' && $key != 'id_product_attribute') {
                                        if ($class_name == 'Delivery')
                                            $object->$key = 0;
                                        elseif (isset($field['validate']) && $field['validate'] == 'isBool')
                                            $object->$key = 0;
                                        elseif ($class_name == 'Image') {
                                            $ok = false;
                                            $error = 'Insert ' . $class_name . ' ' . $primary . '=' . (int)$data_xml->$primary . ' error because ' . $key . ' is required';
                                            Tools::error_log($error . "\n", 3, dirname(__file__) . '/../xml/' . $import_history->file_name . '/errors.log');
                                            break;
                                        } else {
                                            $object->$key = '0';
                                            $error = 'Insert ' . $class_name . ' ' . $primary . '=' . (int)$data_xml->$primary . ' but ' . $key . ' does not exist';
                                            Tools::error_log($error . "\n", 3, dirname(__file__) . '/../xml/' . $import_history->file_name . '/errors.log');
                                        }
                                    }
                                }
                            }
                            if (!$ok) {
                                continue;
                            }
                        }
                        $fileds_unquies = Db::getInstance()->executeS("SHOW INDEX FROM " . _DB_PREFIX_ . pSQL($table) . " where NOT non_unique AND Key_name!='PRIMARY'");
                        if ($fileds_unquies) {
                            $unquies = array();
                            foreach ($fileds_unquies as $field) {
                                if (!isset($unquies[$field['Key_name']]))
                                    $unquies[$field['Key_name']] = array();
                                $unquies[$field['Key_name']][] = $field['Column_name'];
                            }
                            foreach ($unquies as $unquie) {
                                $sql = 'SELECT ' . pSQL($primary) . ' FROM ' . _DB_PREFIX_ . pSQL($table) . ' WHERE 1';
                                foreach ($unquie as $value) {
                                    $sql .= ' AND `' . pSQL($value) . '` ="' . pSQL($object->$value) . '" ';
                                }
                                if ($id = Db::getInstance()->getValue($sql, 0)) {
                                    $object->id = $id;
                                }
                            }
                        }
                        if ($class_name == 'Customer' && $object->id_gender > 2)
                            $object->id_gender = 2;
                        if ($class_name == 'Customer' && $object->is_guest) {
                            Configuration::updateValue('PS_GUEST_CHECKOUT_ENABLED', 1);
                            Db::getInstance()->execute('INSERT INTO ' . _DB_PREFIX_ . 'guest(id_operating_system,id_web_browser,id_customer) VALUES (0,1,0)');
                            $object->id_guest = Db::getInstance()->Insert_ID();
                        }
                        if (($class_name == 'Category' || $class_name == 'CMSCategory') && $object->id == $object->id_parent && $object->id == 1)
                            unset($object->id);
                        if ($class_name == 'SpecificPrice' && $object->price <= 0) {
                            $object->price = -1;
                        }
                        if (isset($object->id) && $object->id)
                            $object->update(true);
                        else {
                            if ($import_history->force_all_id_number && $class_name != 'Employee' && $class_name != 'Carrier') {
                                if (!(int)Db::getInstance()->getValue('SELECT ' . pSQL($primary) . ' FROM ' . _DB_PREFIX_ . pSQL($table) . ' WHERE ' . pSQL($primary) . '=' . (int)$data_xml->$primary)) {
                                    $object->id = (int)$data_xml->$primary;
                                }
                                if ($class_name == 'Category' && !isset($data_xml->is_root_category)) {
                                    $object->force_id = 0;
                                    $_POST['forceIDs'] = 0;
                                } elseif ($class_name == 'CMSCategory' && $object->id == 1) {
                                    $object->force_id = 0;
                                    $_POST['forceIDs'] = 0;
                                } else {
                                    $object->force_id = 1;
                                }
                            }
                            if ($class_name == 'StockAvailable') {
                                if (!$multishop && Shop::getContext() == Shop::CONTEXT_ALL) {
                                    $shops = Db::getInstance()->executeS('SELECT * FROM ' . _DB_PREFIX_ . 'shop');
                                    foreach ($shops as $shop) {
                                        $object->id_shop = $shop['id_shop'];
                                        unset($object->id);
                                        $object->add();
                                    }
                                } else {
                                    $object->id_shop = Context::getContext()->shop->id;
                                    unset($object->id);
                                    $object->add();
                                }
                            } else {
                                if ($class_name == 'Language' && $object->id && $object->iso_code && Language::downloadAndInstallLanguagePack($object->iso_code) === true) {
                                    $object->id = Language::getIdByIso($object->iso_code, true);
                                } else
                                    $object->add();
                            }

                            if ($class_name == 'Shop') {
                                $tables_import = array(
                                    'module' => true,
                                    'hook_module' => true,
                                );
                                $object->copyShopData((int)Configuration::get('PS_SHOP_DEFAULT'), $tables_import);
                            }
                        }

                        if (($class_name == 'Customer' && !Configuration::get('ETS_WOO2PRESS_NEW_PASSWD')) || ($class_name == 'Employee' && !Configuration::get('ETS_WOO2PRESS_NEW_PASSWD'))) {
                            if ($class_name == 'Customer') {
                                Db::getInstance()->execute('UPDATE ' . _DB_PREFIX_ . 'customer SET passwd_old_wp="' . (string)$data_xml->passwd . '" WHERE id_customer=' . (int)$object->id);
                            }
                            if ($class_name == 'Employee') {
                                Db::getInstance()->execute('UPDATE ' . _DB_PREFIX_ . 'employee SET passwd_old_wp="' . (string)$data_xml->passwd . '" WHERE id_employee=' . (int)$object->id);
                            }
                        }
                        if (isset($object->id) && $object->id) {
                            $imported++;
                            Db::getInstance()->execute('UPDATE ' . _DB_PREFIX_ . 'ets_woo2press_import_history SET number_import ="' . (int)$imported . '" WHERE id_import_history=' . (int)$id_import_history);
                            Db::getInstance()->execute('INSERT INTO ' . _DB_PREFIX_ . 'ets_woo2press_' . pSQL($table) . '_import values("","' . (int)$data_xml->$primary . '","' . (int)$object->id . '","' . (int)$id_import_history . '")');
                        } else {
                            $error = 'Insert ' . $class_name . ' width ' . $primary . '=' . (int)$data_xml->$primary . ' error because ' . $primary . ' is null';
                            if (method_exists('Tools', 'error_log')) {
                                Tools::error_log($error . "\n", 3, dirname(__file__) . '/../xml/' . $import_history->file_name . '/errors.log');
                            }
                        }

                        if ($class_name == 'Product' && !file_exists(dirname(__FILE__) . '/../xml/' . $import_history->file_name . '/StockAvailable.xml') && !file_exists(dirname(__FILE__) . '/../xml/' . $import_history->file_name . '/StockAvailable_1.xml')) {
                            Db::getInstance()->execute('UPDATE ' . _DB_PREFIX_ . 'product SET quantity="' . (int)$data_xml->quantity . '" WHERE id_product=' . (int)$object->id);
                            Db::getInstance()->execute('UPDATE ' . _DB_PREFIX_ . 'stock_available SET quantity="' . (int)$data_xml->quantity . '" WHERE id_product=' . (int)$object->id);
                        }
                        if ($class_name == 'Group') {
                            if (!Db::getInstance()->executeS('SELECT * FROM ' . _DB_PREFIX_ . 'module_group WHERE id_group=' . (int)$object->id)) {
                                $module_groups = Db::getInstance()->executeS('SELECT * FROM ' . _DB_PREFIX_ . 'module_group WHERE id_group=' . (int)Configuration::get('PS_CUSTOMER_GROUP'));
                                if ($module_groups) {
                                    foreach ($module_groups as $module_group)
                                        Db::getInstance()->execute('INSERT INTO ' . _DB_PREFIX_ . 'module_group VALUES("' . (int)$module_group['id_module'] . '","' . (int)$module_group['id_shop'] . '","' . (int)$object->id . '")');
                                }
                            }
                        }
                        if (isset($object->date_add) && isset($data_xml->date_add)) {
                            Db::getInstance()->Execute('UPDATE ' . _DB_PREFIX_ . pSQL($table) . ' SET date_add="' . pSQL((string)$data_xml->date_add) . '" WHERE ' . pSQL($primary) . '="' . (int)$object->id . '"');
                        }
                        if (isset($object->date_upd) && isset($data_xml->date_upd)) {
                            Db::getInstance()->Execute('UPDATE ' . _DB_PREFIX_ . pSQL($table) . ' SET date_upd="' . pSQL((string)$data_xml->date_upd) . '" WHERE ' . pSQL($primary) . '="' . (int)$object->id . '"');
                        }
                        if ($class_name == 'Carrier' && isset($data_xml->id_reference) && (int)$data_xml->id_reference) {
                            $id_reference = Db::getInstance()->getValue('SELECT id_reference FROM ' . _DB_PREFIX_ . 'carrier WHERE id_carrier=' . (int)$object->id);
                            $id_reference_new = (int)Db::getInstance()->getValue('SELECT id_new FROM ' . _DB_PREFIX_ . 'ets_woo2press_reference_import WHERE id_old=' . (int)$data_xml->id_reference . ' AND id_import_history=' . (int)$id_import_history, 0);
                            if ($id_reference_new) {
                                Db::getInstance()->execute('UPDATE ' . _DB_PREFIX_ . 'carrier SET id_reference="' . (int)$id_reference_new . '" WHERE id_carrier=' . (int)$object->id);
                            } else {
                                Db::getInstance()->execute('INSERT INTO ' . _DB_PREFIX_ . 'ets_woo2press_reference_import values("","' . (int)$data_xml->id_reference . '","' . (int)$id_reference . '","' . (int)$id_import_history . '")');
                            }
                        }
                        if (isset($definition['multilang_shop']) && $definition['multilang_shop']) {
                            if (isset($data_xml->datashop) && $data_xml->datashop) {
                                if ($class_name == 'Category') {
                                    if ($object->id_parent != 0 && $object->is_root_category != 1)
                                        Db::getInstance()->execute('DELETE FROM ' . _DB_PREFIX_ . pSQL($table) . '_shop WHERE ' . pSQL($primary) . '="' . (int)$object->id . '"');
                                } elseif ($class_name == 'CMSCategory') {
                                    if ($object->id_parent != 0)
                                        Db::getInstance()->execute('DELETE FROM ' . _DB_PREFIX_ . pSQL($table) . '_shop WHERE ' . pSQL($primary) . '="' . (int)$object->id . '"');
                                } else
                                    Db::getInstance()->execute('DELETE FROM ' . _DB_PREFIX_ . pSQL($table) . '_shop WHERE ' . pSQL($primary) . '="' . (int)$object->id . '"');
                                if ($multishop) {
                                    foreach ($data_xml->datashop as $datashop) {
                                        $id_shop_old = (int)$datashop['id_shop'];
                                        $id_shop_new = Db::getInstance()->getValue('SELECT id_new FROM ' . _DB_PREFIX_ . 'ets_woo2press_shop_import WHERE id_old=' . (int)$id_shop_old . ' AND id_import_history="' . (int)$id_import_history . '"', 0);
                                        if (!Db::getInstance()->executeS('SELECT * FROM ' . _DB_PREFIX_ . pSQL($table) . '_shop WHERE id_shop ="' . (int)$id_shop_new . '" AND ' . pSQL($primary) . '="' . (int)$object->id . '"')) {
                                            $sql_shop = 'INSERT INTO ' . _DB_PREFIX_ . pSQL($table) . '_shop SET ' . pSQL($primary) . '="' . (int)$object->id . '", id_shop="' . (int)$id_shop_new . '"';
                                            foreach ($fields as $key => $field) {
                                                if (isset($field['shop']) && $field['shop']) {
                                                    if (isset($foreign_key[$key]) && $foreign_key[$key] && isset($datashop[$key]) && $datashop[$key]) {
                                                        $value = $foreign_key[$key];
                                                        if ($key_extra = (int)Db::getInstance()->getValue('SELECT id_new FROM ' . _DB_PREFIX_ . 'ets_woo2press_' . pSQL($value['table_parent']) . '_import WHERE id_old=' . (int)$datashop[$key] . ' AND id_import_history="' . (int)$id_import_history . '"', 0)) {
                                                            $sql_shop .= ',`' . pSQL($key) . '`="' . (int)$key_extra . '"';
                                                        } else {
                                                            if ($key_extra = (int)Db::getInstance()->getValue('SELECT ' . pSQL($value['key']) . ' FROM ' . _DB_PREFIX_ . pSQL($value['table_parent']) . ' WHERE ' . pSQL($value['key']) . '=' . (int)$datashop[$key], 0))
                                                                $sql_shop .= ',`' . pSQL($key) . '`="' . (int)$key_extra . '"';
                                                            else {
                                                                $key_extra = (int)Db::getInstance()->getValue('SELECT ' . pSQL($value['key']) . ' FROM ' . _DB_PREFIX_ . pSQL($value['table_parent']), 0);
                                                                $sql_shop .= ',`' . pSQL($key) . '`="' . (int)$key_extra . '"';
                                                            }

                                                        }
                                                    } else {
                                                        $value = isset($datashop[$key]) ? (string)$datashop[$key] : $object->$key;
                                                        if ($value || $key != 'default_on')
                                                            $sql_shop .= ',`' . pSQL($key) . '`="' . pSQL($value, true) . '"';
                                                    }

                                                }

                                            }
                                            Db::getInstance()->execute($sql_shop);
                                        }
                                    }
                                } else {
                                    if (Configuration::get('PS_MULTISHOP_FEATURE_ACTIVE') && Shop::getContext() == Shop::CONTEXT_ALL) {
                                        $shops = Db::getInstance()->executeS('SELECT * FROM ' . _DB_PREFIX_ . 'shop');
                                        if ($shops) {
                                            foreach ($shops as $shop) {
                                                foreach ($data_xml->datashop as $datashop) {
                                                    $id_shop_new = $shop['id_shop'];
                                                    if (!Db::getInstance()->executeS('SELECT * FROM ' . _DB_PREFIX_ . pSQL($table) . '_shop WHERE id_shop ="' . (int)$id_shop_new . '" AND ' . pSQL($primary) . '="' . (int)$object->id . '"')) {
                                                        $sql_shop = 'INSERT INTO ' . _DB_PREFIX_ . pSQL($table) . '_shop SET ' . pSQL($primary) . '="' . (int)$object->id . '", id_shop="' . (int)$id_shop_new . '"';
                                                        foreach ($fields as $key => $field) {
                                                            if (isset($field['shop']) && $field['shop']) {
                                                                if (isset($foreign_key[$key]) && $foreign_key[$key] && isset($datashop[$key]) && $datashop[$key]) {
                                                                    $value = $foreign_key[$key];
                                                                    if ($key_extra = (int)Db::getInstance()->getValue('SELECT id_new FROM ' . _DB_PREFIX_ . 'ets_woo2press_' . pSQL($value['table_parent']) . '_import WHERE id_old=' . (int)$datashop[$key] . ' AND id_import_history="' . (int)$id_import_history . '"', 0)) {
                                                                        $sql_shop .= ',`' . pSQL($key) . '`="' . (int)$key_extra . '"';
                                                                    } else {
                                                                        if ($key_extra = (int)Db::getInstance()->getValue('SELECT ' . pSQL($value['key']) . ' FROM ' . _DB_PREFIX_ . pSQL($value['table_parent']) . ' WHERE ' . pSQL($value['key']) . '=' . (int)$datashop[$key], 0))
                                                                            $sql_shop .= ',`' . pSQL($key) . '`="' . (int)$key_extra . '"';
                                                                        else {
                                                                            $key_extra = (int)Db::getInstance()->getValue('SELECT ' . pSQL($value['key']) . ' FROM ' . _DB_PREFIX_ . pSQL($value['table_parent']), 0);
                                                                            $sql_shop .= ',`' . pSQL($key) . '`="' . (int)$key_extra . '"';
                                                                        }

                                                                    }
                                                                } else {
                                                                    $value = isset($datashop[$key]) ? (string)$datashop[$key] : $object->$key;
                                                                    if ($value || $key != 'default_on')
                                                                        $sql_shop .= ',`' . pSQL($key) . '`="' . pSQL($value, true) . '"';
                                                                }
                                                            }

                                                        }
                                                        Db::getInstance()->execute($sql_shop);
                                                    }
                                                    break;
                                                }
                                            }
                                        }
                                    } else {
                                        foreach ($data_xml->datashop as $datashop) {
                                            $id_shop_new = (int)Context::getContext()->shop->id;
                                            if (!Db::getInstance()->executeS('SELECT * FROM ' . _DB_PREFIX_ . pSQL($table) . '_shop WHERE id_shop ="' . (int)$id_shop_new . '" AND ' . pSQL($primary) . '="' . (int)$object->id . '"')) {
                                                $sql_shop = 'INSERT INTO ' . _DB_PREFIX_ . pSQL($table) . '_shop SET ' . pSQL($primary) . '="' . (int)$object->id . '", id_shop="' . (int)$id_shop_new . '"';
                                                foreach ($fields as $key => $field) {
                                                    if (isset($field['shop']) && $field['shop']) {
                                                        if (isset($foreign_key[$key]) && $foreign_key[$key] && isset($datashop[$key]) && $datashop[$key]) {
                                                            $value = $foreign_key[$key];
                                                            if ($key_extra = (int)Db::getInstance()->getValue('SELECT id_new FROM ' . _DB_PREFIX_ . 'ets_woo2press_' . pSQL($value['table_parent']) . '_import WHERE id_old=' . (int)$datashop[$key] . ' AND id_import_history="' . (int)$id_import_history . '"', 0)) {
                                                                $sql_shop .= ',`' . pSQL($key) . '`="' . (int)$key_extra . '"';
                                                            } else {
                                                                if ($key_extra = (int)Db::getInstance()->getValue('SELECT ' . pSQL($value['key']) . ' FROM ' . _DB_PREFIX_ . pSQL($value['table_parent']) . ' WHERE ' . pSQL($value['key']) . '=' . (int)$datashop[$key], 0))
                                                                    $sql_shop .= ',`' . pSQL($key) . '`="' . (int)$key_extra . '"';
                                                                else {
                                                                    $key_extra = (int)Db::getInstance()->getValue('SELECT ' . pSQL($value['key']) . ' FROM ' . _DB_PREFIX_ . pSQL($value['table_parent']), 0);
                                                                    $sql_shop .= ',`' . pSQL($key) . '`="' . (int)$key_extra . '"';
                                                                }

                                                            }
                                                        } else {
                                                            $value = isset($datashop[$key]) ? (string)$datashop[$key] : $object->$key;
                                                            if ($value || $key != 'default_on')
                                                                $sql_shop .= ',`' . pSQL($key) . '`="' . pSQL($value, true) . '"';
                                                        }
                                                    }

                                                }
                                                Db::getInstance()->execute($sql_shop);
                                            }
                                            break;
                                        }
                                    }
                                }
                            } else {
                                if (Configuration::get('PS_MULTISHOP_FEATURE_ACTIVE') && Shop::getContext() != Shop::CONTEXT_ALL) {
                                    if ($class_name == 'Category') {
                                        if ($object->id_parent != 0 && $object->is_root_category != 1)
                                            Db::getInstance()->execute('DELETE FROM ' . _DB_PREFIX_ . pSQL($table) . '_shop WHERE ' . pSQL($primary) . '="' . (int)$object->id . '" AND id_shop!=' . (int)Context::getContext()->shop->id);
                                    } elseif ($class_name == 'CMSCategory') {
                                        if ($object->id_parent != 0)
                                            Db::getInstance()->execute('DELETE FROM ' . _DB_PREFIX_ . pSQL($table) . '_shop WHERE ' . pSQL($primary) . '="' . (int)$object->id . '" AND id_shop!=' . (int)Context::getContext()->shop->id);
                                    } else
                                        Db::getInstance()->execute('DELETE FROM ' . _DB_PREFIX_ . pSQL($table) . '_shop WHERE ' . pSQL($primary) . '="' . (int)$object->id . '" AND id_shop!=' . (int)Context::getContext()->shop->id);
                                }
                            }

                        }
                        //data_language.
                        if (isset($definition['multilang']) && $definition['multilang'] && isset($data_xml->datalanguage) && $data_xml->datalanguage) {
                            if ($class_name == 'Category') {
                                if ($object->id_parent != 0 && $object->is_root_category != 1)
                                    Db::getInstance()->execute('DELETE FROM ' . _DB_PREFIX_ . pSQL($table) . '_lang WHERE ' . pSQL($primary) . '="' . (int)$object->id . '"');
                            } elseif ($class_name == 'CMSCategory') {
                                if ($object->id_parent != 0)
                                    Db::getInstance()->execute('DELETE FROM ' . _DB_PREFIX_ . pSQL($table) . '_lang WHERE ' . pSQL($primary) . '="' . (int)$object->id . '"');
                            } else
                                Db::getInstance()->execute('DELETE FROM ' . _DB_PREFIX_ . pSQL($table) . '_lang WHERE ' . pSQL($primary) . '="' . (int)$object->id . '"');
                            foreach ($data_xml->datalanguage as $datalanguage) {
                                if (isset($definition['multilang_temp']) && $definition['multilang_temp']) {
                                    if ($multishop) {
                                        $id_shop_old = (int)$datalanguage->id_shop;
                                        $id_shop_new = Db::getInstance()->getValue('SELECT id_new FROM ' . _DB_PREFIX_ . 'ets_woo2press_shop_import WHERE id_old=' . (int)$id_shop_old . ' AND id_import_history="' . (int)$id_import_history . '"', 0);
                                        $iso_code = (string)$datalanguage['iso_code'];
                                        $id_lang = Language::getIdByIso($iso_code);
                                        if (!Db::getInstance()->executeS('SELECT * FROM ' . _DB_PREFIX_ . pSQL($table) . '_lang WHERE id_shop ="' . (int)$id_shop_new . '" AND ' . pSQL($primary) . '="' . (int)$object->id . '" AND id_lang="' . (int)$id_lang . '"')) {
                                            $sql_lang = 'INSERT INTO ' . _DB_PREFIX_ . pSQL($table) . '_lang SET ' . pSQL($primary) . '="' . (int)$object->id . '",' . (isset($definition['multilang_temp']) && $definition['multilang_temp'] ? 'id_shop="' . (int)$id_shop_new . '",' : '') . 'id_lang="' . (int)$id_lang . '"';
                                            foreach ($fields as $key => $field) {
                                                if (isset($field['lang']) && $field['lang'] && isset($datalanguage->$key) && $datalanguage->$key) {
                                                    $sql_lang .= ',`' . pSQL($key) . '`="' . pSQL((string)$datalanguage->$key, true) . '"';
                                                }

                                            }
                                            Db::getInstance()->execute($sql_lang);
                                        }
                                    } else {
                                        if (Configuration::get('PS_MULTISHOP_FEATURE_ACTIVE') && Shop::getContext() == Shop::CONTEXT_ALL) {
                                            $shops = Db::getInstance()->executeS('SELECT * FROM ' . _DB_PREFIX_ . 'shop');
                                            foreach ($shops as $shop) {
                                                $id_shop_new = $shop['id_shop'];
                                                $iso_code = isset($datalanguage['iso_code']) ? (string)$datalanguage['iso_code'] : '';
                                                $id_lang = $iso_code ? Language::getIdByIso($iso_code) : 0;
                                                if ($id_lang) {
                                                    if (!Db::getInstance()->executeS('SELECT * FROM ' . _DB_PREFIX_ . pSQL($table) . '_lang WHERE id_shop ="' . (int)$id_shop_new . '" AND ' . pSQL($primary) . '="' . (int)$object->id . '" AND id_lang="' . (int)$id_lang . '"')) {
                                                        $sql_lang = 'INSERT INTO ' . _DB_PREFIX_ . pSQL($table) . '_lang SET ' . pSQL($primary) . '="' . (int)$object->id . '",' . (isset($definition['multilang_temp']) && $definition['multilang_temp'] ? 'id_shop="' . (int)$id_shop_new . '",' : '') . 'id_lang="' . (int)$id_lang . '"';
                                                        foreach ($fields as $key => $field) {
                                                            if (isset($field['lang']) && $field['lang'] && isset($datalanguage->$key) && $datalanguage->$key) {
                                                                $sql_lang .= ',`' . pSQL($key) . '`="' . pSQL((string)$datalanguage->$key, true) . '"';
                                                            }
                                                        }
                                                        Db::getInstance()->execute($sql_lang);
                                                    }
                                                } else {
                                                    foreach ($languages as $language) {
                                                        $id_lang = $language['id_lang'];
                                                        if (!Db::getInstance()->executeS('SELECT * FROM ' . _DB_PREFIX_ . pSQL($table) . '_lang WHERE id_shop ="' . (int)$id_shop_new . '" AND ' . pSQL($primary) . '="' . (int)$object->id . '" AND id_lang="' . (int)$id_lang . '"')) {
                                                            $sql_lang = 'INSERT INTO ' . _DB_PREFIX_ . pSQL($table) . '_lang SET ' . pSQL($primary) . '="' . (int)$object->id . '",' . (isset($definition['multilang_temp']) && $definition['multilang_temp'] ? 'id_shop="' . (int)$id_shop_new . '",' : '') . 'id_lang="' . (int)$id_lang . '"';
                                                            foreach ($fields as $key => $field) {
                                                                if (isset($field['lang']) && $field['lang'] && isset($datalanguage->$key) && $datalanguage->$key) {
                                                                    $sql_lang .= ',`' . pSQL($key) . '`="' . pSQL((string)$datalanguage->$key, true) . '"';
                                                                }
                                                            }
                                                            Db::getInstance()->execute($sql_lang);
                                                        }
                                                    }
                                                }

                                            }
                                        } else {
                                            $id_shop_new = Context::getContext()->shop->id;
                                            $iso_code = isset($datalanguage['iso_code']) ? (string)$datalanguage['iso_code'] : '';
                                            $id_lang = $iso_code ? Language::getIdByIso($iso_code) : 0;
                                            if ($id_lang) {
                                                if (!Db::getInstance()->executeS('SELECT * FROM ' . _DB_PREFIX_ . pSQL($table) . '_lang WHERE id_shop ="' . (int)$id_shop_new . '" AND ' . pSQL($primary) . '="' . (int)$object->id . '" AND id_lang="' . (int)$id_lang . '"')) {
                                                    $sql_lang = 'INSERT INTO ' . _DB_PREFIX_ . pSQL($table) . '_lang SET ' . pSQL($primary) . '="' . (int)$object->id . '",' . (isset($definition['multilang_temp']) && $definition['multilang_temp'] ? 'id_shop="' . (int)$id_shop_new . '",' : '') . 'id_lang="' . (int)$id_lang . '"';
                                                    foreach ($fields as $key => $field) {
                                                        if (isset($field['lang']) && $field['lang'] && isset($datalanguage->$key) && $datalanguage->$key) {
                                                            $sql_lang .= ',`' . pSQL($key) . '`="' . pSQL((string)$datalanguage->$key, true) . '"';
                                                        }

                                                    }
                                                    Db::getInstance()->execute($sql_lang);
                                                }
                                            } else {
                                                foreach ($languages as $language) {
                                                    $id_lang = $language['id_lang'];
                                                    if (!Db::getInstance()->executeS('SELECT * FROM ' . _DB_PREFIX_ . pSQL($table) . '_lang WHERE id_shop ="' . (int)$id_shop_new . '" AND ' . pSQL($primary) . '="' . (int)$object->id . '" AND id_lang="' . (int)$id_lang . '"')) {
                                                        $sql_lang = 'INSERT INTO ' . _DB_PREFIX_ . pSQL($table) . '_lang SET ' . pSQL($primary) . '="' . (int)$object->id . '",' . (isset($definition['multilang_temp']) && $definition['multilang_temp'] ? 'id_shop="' . (int)$id_shop_new . '",' : '') . 'id_lang="' . (int)$id_lang . '"';
                                                        foreach ($fields as $key => $field) {
                                                            if (isset($field['lang']) && $field['lang'] && isset($datalanguage->$key) && $datalanguage->$key) {
                                                                $sql_lang .= ',`' . pSQL($key) . '`="' . pSQL((string)$datalanguage->$key, true) . '"';
                                                            }

                                                        }
                                                        Db::getInstance()->execute($sql_lang);
                                                    }
                                                }
                                            }

                                        }
                                    }
                                } else {
                                    $iso_code = isset($datalanguage['iso_code']) ? (string)$datalanguage['iso_code'] : '';
                                    $id_lang = $iso_code ? Language::getIdByIso($iso_code) : 0;
                                    if ($id_lang) {
                                        if (!Db::getInstance()->executeS('SELECT * FROM ' . _DB_PREFIX_ . pSQL($table) . '_lang WHERE ' . pSQL($primary) . '="' . (int)$object->id . '" AND id_lang="' . (int)$id_lang . '"')) {
                                            $sql_lang = 'INSERT INTO ' . _DB_PREFIX_ . pSQL($table) . '_lang SET ' . pSQL($primary) . '="' . (int)$object->id . '",id_lang="' . (int)$id_lang . '"';
                                            foreach ($fields as $key => $field) {
                                                if (isset($field['lang']) && $field['lang'] && isset($datalanguage->$key) && $datalanguage->$key) {
                                                    $sql_lang .= ',`' . $key . '`="' . pSQL((string)$datalanguage->$key, true) . '"';
                                                }

                                            }
                                            Db::getInstance()->execute($sql_lang);
                                        }
                                    } else {
                                        foreach ($languages as $language) {
                                            $id_lang = $language['id_lang'];
                                            if (!Db::getInstance()->executeS('SELECT * FROM ' . _DB_PREFIX_ . pSQL($table) . '_lang WHERE ' . pSQL($primary) . '="' . (int)$object->id . '" AND id_lang="' . (int)$id_lang . '"')) {
                                                $sql_lang = 'INSERT INTO ' . _DB_PREFIX_ . pSQL($table) . '_lang SET ' . pSQL($primary) . '="' . (int)$object->id . '",id_lang="' . (int)$id_lang . '"';
                                                foreach ($fields as $key => $field) {
                                                    if (isset($field['lang']) && $field['lang'] && isset($datalanguage->$key) && $datalanguage->$key) {
                                                        $sql_lang .= ',`' . pSQL($key) . '`="' . pSQL((string)$datalanguage->$key, true) . '"';
                                                    }
                                                }
                                                Db::getInstance()->execute($sql_lang);
                                            }
                                        }
                                    }

                                }
                            }
                        }
                        if ($class_name == 'Image' && $object->id && isset($data_xml->link_image)) {
                            $url = str_replace(' ', '%20', (string)$data_xml->link_image);
                            $object->associateTo(Context::getContext()->shop->id);
                            if (!$this->copyImg($object->id_product, $object->id, $url, 'products', true)) {
                                //$object->delete();
                            }
                        }
                        if (($class_name == 'Category' || $class_name == 'Supplier' || $class_name == 'Manufacturer') && $object->id && isset($data_xml->link_image)) {
                            $url = str_replace(' ', '%20', (string)$data_xml->link_image);
                            $this->copyImg($object->id, null, $url, $class_name, true);
                        }
                        if ($class_name == 'Carrier' && $object->id && isset($data_xml->link_image) && (string)$data_xml->link_image) {
                            $url = str_replace(' ', '%20', (string)$data_xml->link_image);
                            $url = urldecode(trim($url));
                            $parced_url = parse_url($url);
                            if (!function_exists('http_build_url')) {
                                include_once(_PS_MODULE_DIR_ . 'ets_woo2pres/classes/http_build_url.php');
                            }
                            $url = http_build_url('', $parced_url);
                            $context = stream_context_create(array('http' => array('header' => 'User-Agent: Mozilla compatible')));
                            if (file_exists(_PS_SHIP_IMG_DIR_ . '/' . (int)$object->id . '.jpg'))
                                @unlink(_PS_SHIP_IMG_DIR_ . '/' . (int)$object->id . '.jpg');
                            if (is_file(_PS_TMP_IMG_DIR_ . 'carrier_mini_' . (int)$object->id . '.jpg')) {
                                @unlink(_PS_TMP_IMG_DIR_ . 'carrier_mini_' . (int)$object->id . '.jpg');
                            }
                            if (is_file(_PS_TMP_IMG_DIR_ . 'carrier_mini_' . (int)$object->id . '_' . (int)Context::getContext()->shop->id . '.jpg')) {
                                @unlink(_PS_TMP_IMG_DIR_ . 'carrier_mini_' . (int)$object->id . '_' . (int)Context::getContext()->shop->id . '.jpg');
                            }
                            Woo2PressDataImport::copy($url, _PS_SHIP_IMG_DIR_ . '/' . (int)$object->id . '.jpg', $context);
                        }
                        Configuration::updateValue('PS_WOO2PRESS_IMPORTING', _DB_PREFIX_ . $table);
                        if ($imported % 100 == 0 || $class_name == 'Image' && $imported % 5 == 0 || time() - $start_time > 60) {
                            die('Imported (Auto stop) ' . $class_name . '=' . $imported);
                        }
                    }
                }
            }
            $readXml->deleteFileXML();
        }
        if ($readXml->imported) {
            Db::getInstance()->execute('UPDATE ' . _DB_PREFIX_ . 'ets_woo2press_import_history SET currentindex =1 WHERE id_import_history=' . (int)$id_import_history);
        }
    }

    public function checkStockAvailable($id_product, $id_product_attribute, $id_shop)
    {
        return ($id_product_attribute != 0 && (int)Db::getInstance()->getValue('
            SELECT id_product 
            FROM ' . _DB_PREFIX_ . 'product_attribute_shop 
            WHERE id_product=' . ($id_product) . ' AND id_product_attribute=' . (int)$id_product_attribute . ' AND id_shop=' . (int)$id_shop));
    }

    public function importData14($file_xml, $class_name, $foreign_key = array())
    {
        $start_time = time();
        $class = new $class_name();
        $tableProtected = new ReflectionProperty($class_name, 'table');
        $tableProtected->setAccessible(true);
        $table = $tableProtected->getValue($class);
        $tablesProtected = new ReflectionProperty($class_name, 'tables');
        $tablesProtected->setAccessible(true);
        $tables = $tablesProtected->getValue($class);
        if (count($tables) >= 2 || $class_name == 'Carrier' || $class_name == 'FeatureValue' || $class_name == 'Feature' || $class_name == 'AttributeGroup' || $class_name == 'Attribute' || $class_name == 'CMS')
            $multilang = true;
        else
            $multilang = false;
        $identifierProtected = new ReflectionProperty($class_name, 'identifier');
        $identifierProtected->setAccessible(true);
        $primary = $identifierProtected->getValue($class);
        $fieldsRequiredProtected = new ReflectionProperty($class_name, 'fieldsRequired');
        $fieldsRequiredProtected->setAccessible(true);
        $fieldsRequired = $fieldsRequiredProtected->getValue($class);
        $fieldsSizeProtected = new ReflectionProperty($class_name, 'fieldsSize');
        $fieldsSizeProtected->setAccessible(true);
        $fieldsSize = $fieldsSizeProtected->getValue($class);
        $fieldsValidateProtected = new ReflectionProperty($class_name, 'fieldsValidate');
        $fieldsValidateProtected->setAccessible(true);
        $fieldsValidate = $fieldsValidateProtected->getValue($class);
        $fieldsValidateLangProtected = new ReflectionProperty($class_name, 'fieldsValidateLang');
        $fieldsValidateLangProtected->setAccessible(true);
        $fieldsValidateLang = $fieldsValidateLangProtected->getValue($class);
        if ($multilang) {
            $fieldsRequiredLangProtected = new ReflectionProperty($class_name, 'fieldsRequiredLang');
            $fieldsRequiredLangProtected->setAccessible(true);
            $fieldsRequiredLang = $fieldsRequiredLangProtected->getValue($class);
            $fieldsSizeLangProtected = new ReflectionProperty($class_name, 'fieldsSizeLang');
            $fieldsSizeLangProtected->setAccessible(true);
            $fieldsSizeLang = $fieldsSizeLangProtected->getValue($class);
        } else {
            $fieldsSizeLang = false;
            $fieldsRequiredLang = false;
        }
        $fields = Db::getInstance()->ExecuteS('DESCRIBE ' . _DB_PREFIX_ . pSQL($table));
        if ($multilang)
            $fields_lang = Db::getInstance()->ExecuteS('DESCRIBE ' . _DB_PREFIX_ . pSQL($table) . '_lang');
        $id_import_history = Context::getContext()->cookie->id_import_history;
        $import_history = Db::getInstance()->getRow('SELECT * FROM ' . _DB_PREFIX_ . 'ets_woo2press_import_history WHERE id_import_history=' . (int)$id_import_history);
        $readXML = new Woo2PressReadXML($file_xml);
        $languages = Language::getLanguages(false);
        while ($xml = $readXML->_readXML()) {
            if (isset($xml->$table) && $xml->$table) {
                $check_import = Db::getInstance()->getRow('SELECT * FROM ' . _DB_PREFIX_ . 'ets_woo2press_' . pSQL($table) . '_import where id_import_history=' . (int)$id_import_history);
                if (!$check_import) {
                    if ($import_history['delete_before_importing'] && $class_name != 'Zone' && $class_name != 'Currency' && $class_name != 'Language' && $class_name != 'Employee' && $class_name != 'Country') {
                        if ($class_name == 'Group') {
                            $add_group = ' AND id_group!=1';
                        } else
                            $add_group = '';
                        if ($class_name == 'Carrier') {
                            Db::getInstance()->execute('UPDATE ' . _DB_PREFIX_ . 'carrier SET deleted=1');
                        } else {
                            Db::getInstance()->execute('DELETE FROM ' . _DB_PREFIX_ . pSQL($table) . ' WHERE 1 ' . $add_group . ($class_name == 'Category' || $class_name == 'CMSCategory' ? ' AND id_parent!=0' : '') . '');
                            if ($multilang) {
                                Db::getInstance()->execute('DELETE  FROM ' . _DB_PREFIX_ . pSQL($table) . '_lang WHERE 1 ' . $add_group . ($class_name == 'CMSCategory' ? ' AND ' . pSQl($primary) . ' NOT IN (SELECT id_cms_category FROM ' . _DB_PREFIX_ . 'cms_category WHERE id_parent=0)' : '') . ($class_name == 'Category' ? ' AND ' . pSQl($primary) . ' NOT IN (SELECT id_category FROM ' . _DB_PREFIX_ . 'category WHERE id_parent=0)' : '') . '');
                            }
                            if ($class_name != 'Category' && $class_name != 'Cart' || $class_name != 'CMSCategory')
                                Db::getInstance()->execute('ALTER TABLE ' . _DB_PREFIX_ . pSQL($table) . ' AUTO_INCREMENT = 1');
                            elseif ($class_name == 'Category') {
                                $max_id_category = (int)Db::getInstance()->getValue('SELECT MAX(id_category) FROM ' . _DB_PREFIX_ . 'category', 0);
                                $max_id_category++;
                                Db::getInstance()->execute('ALTER TABLE ' . _DB_PREFIX_ . pSQL($table) . ' AUTO_INCREMENT = ' . (int)$max_id_category);
                            } elseif ($class_name == 'CMSCategory') {
                                $max_id_category = (int)Db::getInstance()->getValue('SELECT MAX(id_cms_category) FROM ' . _DB_PREFIX_ . 'cms_category', 0);
                                $max_id_category++;
                                Db::getInstance()->execute('ALTER TABLE ' . _DB_PREFIX_ . pSQL($table) . ' AUTO_INCREMENT = ' . (int)$max_id_category);
                            }
                            if ($table == 'group')
                                Db::getInstance()->execute('delete from ' . _DB_PREFIX_ . 'category_group');
                            if ($class_name == 'Product') {
                                Db::getInstance()->execute('DELETE FROM ' . _DB_PREFIX_ . 'category_product');
                                Db::getInstance()->execute('DELETE FROM ' . _DB_PREFIX_ . 'product_tag');
                                Db::getInstance()->execute('DELETE FROM ' . _DB_PREFIX_ . 'feature_product');
                                Db::getInstance()->execute('DELETE FROM ' . _DB_PREFIX_ . 'product_attribute');
                                Db::getInstance()->execute('DELETE FROM ' . _DB_PREFIX_ . 'product_attribute_combination');
                            }
                            if ($class_name == 'Combination') {
                                Db::getInstance()->execute('DELETE FROM ' . _DB_PREFIX_ . 'product_attribute_combination');
                            }
                        }
                    }
                }

                foreach ($xml->$table as $data_xml) {
                    $object_id = 0;
                    if (!Db::getInstance()->getRow('SELECT * FROM ' . _DB_PREFIX_ . 'ets_woo2press_' . pSQL($table) . '_import WHERE id_old=' . (int)$data_xml->$primary . ' AND id_import_history="' . (int)$id_import_history . '"', false)) {
                        $imported = (int)Db::getInstance()->getValue('SELECT number_import FROM ' . _DB_PREFIX_ . 'ets_woo2press_import_history WHERE id_import_history=' . (int)$id_import_history, 0);
                        if ($class_name != 'Zone' && $class_name != 'Language' && $class_name != 'Currency' && $class_name != 'Employee' && $class_name != 'Country' && $class_name != 'Carrier') {
                            if ($import_history['force_all_id_number']) {
                                $object_id = (int)Db::getInstance()->getValue('SELECT ' . pSQL($primary) . ' FROM ' . _DB_PREFIX_ . pSQL($table) . ' WHERE ' . pSQL($primary) . '=' . (int)$data_xml->$primary, 0);
                            }
                        } else {
                            if ($class_name == 'Language') {
                                $object_id = (int)Db::getInstance()->getValue('SELECT id_lang FROM ' . _DB_PREFIX_ . 'lang WHERE iso_code="' . pSQL((string)$data_xml->iso_code) . '"', 0);
                                if ($object_id) {
                                    Db::getInstance()->execute('INSERT INTO ' . _DB_PREFIX_ . 'ets_woo2press_' . pSQL($table) . '_import values("","' . (int)$data_xml->$primary . '","' . (int)$object_id . '","' . (int)$id_import_history . '")');
                                    $imported++;
                                    Db::getInstance()->execute('UPDATE ' . _DB_PREFIX_ . 'ets_woo2press_import_history SET number_import ="' . (int)$imported . '" WHERE id_import_history=' . (int)$id_import_history);
                                    continue;
                                }
                            } elseif ($class_name == 'Currency') {
                                $object_id = (int)Db::getInstance()->getValue('SELECT id_currency FROM ' . _DB_PREFIX_ . 'currency WHERE iso_code="' . pSQL((string)$data_xml->iso_code) . '"', 0);
                            } elseif ($class_name == 'Zone') {
                                $object_id = (int)Db::getInstance()->getValue('SELECT id_zone FROM ' . _DB_PREFIX_ . 'zone WHERE name="' . pSQL((string)$data_xml->name) . '"');
                            } elseif ($class_name == 'Country') {
                                $object_id = (int)Db::getInstance()->getValue('SELECT id_country FROM ' . _DB_PREFIX_ . 'country WHERE iso_code="' . pSQL((string)$data_xml->iso_code) . '"');
                            }
                            if ($import_history['force_all_id_number'] && !$object_id && $class_name != 'Employee' && $class_name != 'Carrier') {
                                $object_id = (int)Db::getInstance()->getValue('SELECT ' . pSQL($primary) . ' FROM ' . _DB_PREFIX_ . pSQL($table) . ' WHERE ' . pSQL($primary) . '=' . (int)$data_xml->$primary, 0);
                            }
                        }
                        if ($class_name == 'Group') {
                            if ((isset($data_xml->visitor_group) && (int)$data_xml->visitor_group) || (isset($data_xml->guest_group) && (int)$data_xml->guest_group) || (isset($data_xml->customer_group) && (int)$data_xml->customer_group) || (isset($data_xml->default_group) && (int)$data_xml->default_group))
                                $object_id = 1;
                            if (isset($object_id) && $object_id) {
                                Db::getInstance()->execute('INSERT INTO ' . _DB_PREFIX_ . 'ets_woo2press_' . pSQL($table) . '_import values("","' . (int)$data_xml->$primary . '","' . (int)$object_id . '","' . (int)$id_import_history . '")');
                                $imported++;
                                Db::getInstance()->execute('UPDATE ' . _DB_PREFIX_ . 'ets_woo2press_import_history SET number_import ="' . (int)$imported . '" WHERE id_import_history=' . (int)$id_import_history);
                                continue;
                            }

                        }
                        if ($class_name == 'CMSCategory') {
                            if ((int)$data_xml->id_parent == 0 && !isset($data_xml->woocommerce)) {
                                $object_id = (int)Db::getInstance()->getValue('SELECT id_cms_category FROM ' . _DB_PREFIX_ . 'cms_category WHERE id_parent=0');
                                if ($object_id) {
                                    Db::getInstance()->execute('INSERT INTO ' . _DB_PREFIX_ . 'ets_woo2press_' . pSQL($table) . '_import values("","' . (int)$data_xml->$primary . '","' . (int)$object_id . '","' . (int)$id_import_history . '")');
                                    $imported++;
                                    Db::getInstance()->execute('UPDATE ' . _DB_PREFIX_ . 'ets_woo2press_import_history SET number_import ="' . (int)$imported . '" WHERE id_import_history=' . (int)$id_import_history);
                                    continue;
                                }
                            }
                        }
                        if ($class_name == 'Category') {
                            if (isset($data_xml->is_root_category)) {
                                if ((int)$data_xml->id_parent == 0)
                                    continue;
                                if ((int)$data_xml->is_root_category == 1) {
                                    $object_id = (int)Db::getInstance()->getValue('SELECT id_category FROM ' . _DB_PREFIX_ . 'category WHERE id_parent=0');
                                    Db::getInstance()->execute('INSERT INTO ' . _DB_PREFIX_ . 'ets_woo2press_' . pSQL($table) . '_import values("","' . (int)$data_xml->$primary . '","' . (int)$object_id . '","' . (int)$id_import_history . '")');
                                    $imported++;
                                    Db::getInstance()->execute('UPDATE ' . _DB_PREFIX_ . 'ets_woo2press_import_history SET number_import ="' . (int)$imported . '" WHERE id_import_history=' . (int)$id_import_history);
                                    continue;
                                }
                            } else {

                                if ((int)$data_xml->id_parent == 0 && !isset($data_xml->woocommerce)) {
                                    $object_id = (int)Db::getInstance()->getValue('SELECT id_category FROM ' . _DB_PREFIX_ . 'category WHERE id_parent=0');
                                    Db::getInstance()->execute('INSERT INTO ' . _DB_PREFIX_ . 'ets_woo2press_' . pSQL($table) . '_import values("","' . (int)$data_xml->$primary . '","' . (int)$object_id . '","' . (int)$id_import_history . '")');
                                    $imported++;
                                    Db::getInstance()->execute('UPDATE ' . _DB_PREFIX_ . 'ets_woo2press_import_history SET number_import ="' . (int)$imported . '" WHERE id_import_history=' . (int)$id_import_history);
                                    continue;
                                }
                            }
                        }
                        if (isset($object_id) && $object_id) {
                            $object = new $class_name($object_id);
                        } else
                            $object = new $class_name();
                        if ($class_name == 'Employee') {
                            if ($id_employee = (int)Db::getInstance()->getValue('SELECT id_employee FROM ' . _DB_PREFIX_ . 'employee WHERE email="' . pSQL((string)$data_xml->email) . '"')) {
                                Db::getInstance()->execute('INSERT INTO ' . _DB_PREFIX_ . 'ets_woo2press_employee_import values("","' . (int)$data_xml->$primary . '","' . (int)$id_employee . '","' . (int)$id_import_history . '")');
                                $imported++;
                                Db::getInstance()->execute('UPDATE ' . _DB_PREFIX_ . 'ets_woo2press_import_history SET number_import ="' . (int)$imported . '" WHERE id_import_history=' . (int)$id_import_history);
                                continue;
                            }
                        }
                        if ($class_name == 'Customer' && !(int)$data_xml->is_guest && !(int)$data_xml->deleted) {
                            if ($id_customer = (int)Db::getInstance()->getValue('SELECT id_customer FROM ' . _DB_PREFIX_ . 'customer WHERE email="' . pSQL((string)$data_xml->email) . '" AND is_guest=0 AND deleted=0')) {
                                Db::getInstance()->execute('INSERT INTO ' . _DB_PREFIX_ . 'ets_woo2press_' . pSQL($table) . '_import values("","' . (int)$data_xml->$primary . '","' . (int)$id_customer . '","' . (int)$id_import_history . '")');
                                $imported++;
                                Db::getInstance()->execute('UPDATE ' . _DB_PREFIX_ . 'ets_woo2press_import_history SET number_import ="' . (int)$imported . '" WHERE id_import_history=' . (int)$id_import_history);
                                continue;
                            }
                        }
                        if ($class_name == 'Country' && isset($data_xml->woocommerce) && $object_id) {
                            $object->active = (int)$data_xml->active;
                            $object->id_zone = (int)$data_xml->id_zone;
                        } elseif ($fields) {
                            foreach ($fields as $field) {
                                if (isset($field['field']))
                                    $key = $field['field'];
                                else
                                    $key = $field['Field'];

                                if ($key != $primary) {
                                    if ((!$fieldsRequired || !in_array($key, $fieldsRequired)) && (!isset($data_xml->$key) || (string)$data_xml->$key == '')) {
                                        continue;
                                    }
                                    if (isset($fieldsSize[$key]) && $fieldsSize[$key] < Tools::strlen($data_xml->$key))
                                        $object->$key = Tools::substr((string)$data_xml->$key, 0, $fieldsSize[$key]);
                                    else
                                        $object->$key = (string)$data_xml->$key;
                                    if ($fieldsRequired && in_array($key, $fieldsRequired) && $object->$key == '' && $key != 'id_product_attribute') {
                                        if ($key == 'sign')
                                            $object->$key = '$';
                                        elseif($class_name == 'SpecificPrice' && $key == 'reduction_tax')
                                            $object->$key = 1;
                                        elseif($class_name == 'SpecificPrice')
                                            $object->$key = '0';
                                        else
                                            $object->$key = '1';
                                    }
                                    if (isset($fieldsValidate[$key]) && method_exists('Validate', $fieldsValidate[$key]) && $object->$key != '') {
                                        $validate = $fieldsValidate[$key];
                                        if (!Validate::$validate($object->$key)) {
                                            if ($key == 'secure_key' && $class_name == 'Order') {
                                                $object->$key = md5('secure' . rand() . time());
                                            } else
                                                $object->$key = '';
                                        }
                                        unset($validate);
                                    }
                                }
                            }
                        }
                        if ($foreign_key) {
                            foreach ($foreign_key as $key => $value) {
                                if (isset($object->$key) && $object->$key) {
                                    if ($key == 'id_parent') {
                                        $object->$key = (int)Db::getInstance()->getValue('SELECT id_new FROM ' . _DB_PREFIX_ . 'ets_woo2press_' . pSQL($value['table_parent']) . '_import WHERE id_old=' . (int)$object->$key . ' AND id_import_history="' . (int)$id_import_history . '"');
                                    } elseif ($key_extra = (int)Db::getInstance()->getValue('SELECT id_new FROM ' . _DB_PREFIX_ . 'ets_woo2press_' . pSQL($value['table_parent']) . '_import WHERE id_old=' . (int)$object->$key . ' AND id_import_history="' . (int)$id_import_history . '"')) {
                                        $object->$key = $key_extra;
                                    } elseif (!Db::getInstance()->getValue('SELECT id_new FROM ' . _DB_PREFIX_ . 'ets_woo2press_' . pSQL($value['table_parent']) . '_import WHERE id_import_history="' . (int)$id_import_history . '"', 0)) {
                                        if ($value['table_parent'] == 'category')
                                            $object->$key = $import_history['id_category_default'];
                                        elseif ($value['table_parent'] == 'supplier') {
                                            $object->$key = (int)$import_history['id_supplier'];
                                        } elseif ($value['table_parent'] == 'manufacturer')
                                            $object->$key = (int)$import_history['id_manufacture'];
                                        elseif ($value['table_parent'] == 'cms_category')
                                            $object->$key = (int)$import_history['id_category_cms'];
                                        elseif ($import_history['force_all_id_number'])
                                            $object->$key = (int)Db::getInstance()->getValue('SELECT ' . pSQL($value['key']) . ' FROM ' . _DB_PREFIX_ . pSQL($value['table_parent']) . ' WHERE ' . pSQL($value['key']) . '=' . (int)$object->$key);
                                    } else
                                        $object->$key = '0';
                                    if ($key == 'id_employee')
                                        $object->$key = Context::getContext()->employee->id;
                                } elseif ($key == 'id_parent' && $class_name == 'Category' && isset($data_xml->woocommerce))
                                    $object->$key = (int)Db::getInstance()->getValue('SELECT id_category FROM ' . _DB_PREFIX_ . 'category WHERE id_parent=0');
                                elseif ($key == 'id_parent' && $class_name == 'CMSCategory' && isset($data_xml->woocommerce))
                                    $object->$key = (int)Db::getInstance()->getValue('SELECT id_cms_category FROM ' . _DB_PREFIX_ . 'cms_category WHERE id_parent=0', 0);
                                elseif ($key == 'id_currency') {
                                    $object->$key = (int)Configuration::get('PS_CURRENCY_DEFAULT');
                                } elseif ($key == 'id_address_delivery') {
                                    if ($id_address_delivery = (int)Db::getInstance()->getValue('SELECT id_address FROM ' . _DB_PREFIX_ . 'address WHERE alias = "deleted"', false)) {
                                        $object->$key = $id_address_delivery;
                                    } else {
                                        $id_country = (int)Configuration::get('PS_COUNTRY_DEFAULT');
                                        $id_state = (int)Db::getInstance()->getValue('SELECT id_state FROM `' . _DB_PREFIX_ . 'state` s WHERE s.`id_country` = ' . (int)$id_country, false);
                                        if ((bool)Db::getInstance()->execute("
                                                INSERT INTO " . _DB_PREFIX_ . "address(id_country, id_state, id_customer, id_manufacturer, id_supplier, id_warehouse, alias, company, lastname, firstname, address1, address2, postcode, city, other, phone, phone_mobile, vat_number, dni, date_add, date_upd, active, deleted) 
                                                VALUES (" . (int)$id_country . ", " . (int)$id_state . ", 0, 0, 0, 0, 'deleted', 'deleted', 'deleted', 'deleted', 'deleted', 'deleted', '', 'deleted', '', '123456789', '', '', '', now(), now(), 1, 1)"
                                            , false)) {
                                            $object->$key = (int)Db::getInstance()->Insert_ID();
                                        }
                                    }
                                }
                            }
                        }
                        $fileds_unquies = Db::getInstance()->executeS("SHOW INDEX FROM " . _DB_PREFIX_ . pSQL($table) . " where NOT non_unique AND Key_name!='PRIMARY'");
                        if ($fileds_unquies) {
                            $unquies = array();
                            foreach ($fileds_unquies as $field) {
                                if (!isset($unquies[$field['Key_name']]))
                                    $unquies[$field['Key_name']] = array();
                                $unquies[$field['Key_name']][] = $field['Column_name'];
                            }
                            foreach ($unquies as $unquie) {
                                $sql = 'SELECT ' . pSQL($primary) . ' FROM ' . _DB_PREFIX_ . pSQL($table) . ' WHERE 1';
                                foreach ($unquie as $value) {
                                    $sql .= ' AND `' . pSQL($value) . '` ="' . pSQL($object->$value) . '"';
                                }
                                if ($id = Db::getInstance()->getValue($sql)) {
                                    $object->id = $id;
                                }
                            }
                        }

                        if ($multilang && isset($data_xml->datalanguage) && $data_xml->datalanguage) {
                            $language_xml_default = null;
                            foreach ($data_xml->datalanguage as $language_xml) {
                                if ((isset($language_xml['default']) && (int)$language_xml['default']) || !isset($language_xml['iso_code'])) {
                                    $language_xml_default = $language_xml;
                                    break;
                                }
                            }
                            $list_language_xml = array();
                            foreach ($data_xml->datalanguage as $language_xml) {
                                $iso_code = isset($language_xml['iso_code']) ? (string)$language_xml['iso_code'] : '';
                                $id_lang = $iso_code ? Language::getIdByIso($iso_code) : 0;
                                $list_language_xml[] = $id_lang;
                                if ($id_lang) {
                                    foreach ($fields_lang as $field) {
                                        if (isset($field['field']))
                                            $key = $field['field'];
                                        else
                                            $key = $field['Field'];
                                        if ($key != $primary && $key != 'id_lang') {
                                            $temp = $object->$key;
                                            if (isset($fieldsSizeLang[$key]) && $fieldsSizeLang[$key] < Tools::strlen((string)$language_xml->$key))
                                                $temp[$id_lang] = Tools::substr((string)$language_xml->$key, 0, $fieldsSizeLang[$key]);
                                            else
                                                $temp[$id_lang] = (string)$language_xml->$key;
                                            if ($fieldsRequiredLang && in_array($key, $fieldsRequiredLang) && $temp[$id_lang] == '') {
                                                if (isset($language_xml_default) && $language_xml_default && isset($language_xml_default->$key) && (string)$language_xml_default->$key != '') {
                                                    $temp[$id_lang] = (string)$language_xml_default->$key;
                                                } else
                                                    $temp[$id_lang] = 'Empty.';
                                            }
                                            if (isset($fieldsValidateLang[$key]) && method_exists('Validate', $fieldsValidateLang[$key]) && $temp[$id_lang] != '') {
                                                $validate = $fieldsValidateLang[$key];
                                                if (!Validate::$validate($temp[$id_lang])) {
                                                    $temp[$id_lang] = 'Empty';
                                                }
                                                unset($validate);
                                            }
                                            $object->$key = $temp;
                                        }
                                    }
                                }
                            }
                            foreach ($languages as $language) {
                                if (!in_array($language['id_lang'], $list_language_xml)) {
                                    foreach ($fields_lang as $field) {
                                        if (isset($field['field']))
                                            $key = $field['field'];
                                        else
                                            $key = $field['Field'];
                                        if ($key != $primary && $key != 'id_lang') {
                                            $temp = $object->$key;
                                            if ($fieldsRequiredLang && in_array($key, $fieldsRequiredLang) && !$temp[$language['id_lang']]) {
                                                if (isset($language_xml_default) && $language_xml_default && isset($language_xml_default->$key) && (string)$language_xml_default->$key != '') {
                                                    if (isset($fieldsSizeLang[$key]) && $fieldsSizeLang[$key] < Tools::strlen((string)$language_xml_default->$key))
                                                        $temp[$language['id_lang']] = Tools::substr((string)$language_xml_default->$key, 0, $fieldsSizeLang[$key]);
                                                    else
                                                        $temp[$language['id_lang']] = (string)$language_xml_default->$key;
                                                } else
                                                    $temp[$language['id_lang']] = 'Empty.';
                                            }
                                            if (isset($fieldsValidateLang[$key]) && method_exists('Validate', $fieldsValidateLang[$key]) && $temp[$language['id_lang']] != '') {
                                                $validate = $fieldsValidateLang[$key];
                                                if (!Validate::$validate($temp[$language['id_lang']])) {
                                                    $temp[$language['id_lang']] = 'Empty';
                                                }
                                                unset($validate);
                                            }
                                            $object->$key = $temp;
                                        }
                                    }
                                }
                            }
                        }
                        if ($class_name == 'OrderHistory' && !$object->id_order) {
                            $imported++;
                            Db::getInstance()->execute('UPDATE ' . _DB_PREFIX_ . 'ets_woo2press_import_history SET number_import ="' . (int)$imported . '" WHERE id_import_history=' . (int)$id_import_history);
                            continue;
                        }
                        if ($class_name == 'Customer' && $object->is_guest == 0) {
                            if (Configuration::get('ETS_WOO2PRESS_NEW_PASSWD')) {
                                $new_passwd = $this->_module->genSecure(8);
                                $object->passwd = md5(pSQL(_COOKIE_KEY_ . $new_passwd));
                                Db::getInstance()->execute('INSERT INTO ' . _DB_PREFIX_ . 'ets_woo2press_customer_pasword(id_import_history,first_name,last_name,email,passwd) VALUES("' . (int)$id_import_history . '","' . pSQL($object->firstname) . '","' . pSQL($object->lastname) . '","' . pSQL($object->email) . '","' . pSQL($new_passwd) . '")');
                            } else {
                                Db::getInstance()->execute('ALTER TABLE `' . _DB_PREFIX_ . 'customer` ADD COLUMN IF NOT EXISTS `passwd_old_wp` VARCHAR(60) NOT NULL ');
                            }
                        }
                        if ($class_name == 'Employee' && Configuration::get('ETS_WOO2PRESS_NEW_PASSWD')) {
                            $new_passwd = $this->_module->genSecure(8);
                            $object->passwd = md5(pSQL(_COOKIE_KEY_ . $new_passwd));
                            Db::getInstance()->execute('INSERT INTO ' . _DB_PREFIX_ . 'ets_woo2press_employee_pasword(id_import_history,first_name,last_name,email,passwd) VALUES("' . (int)$id_import_history . '","' . pSQL($object->firstname) . '","' . pSQL($object->lastname) . '","' . pSQL($object->email) . '","' . pSQL($new_passwd) . '")');
                        }
                        if ($file_xml == 'page_cms') {
                            $object->id_cms_category = (int)Db::getInstance()->getValue('SELECT id_cms_category FROM ' . _DB_PREFIX_ . 'cms_category WHERE id_parent=0', 0);
                        }
                        if ($fields) {
                            $ok = true;
                            foreach ($fields as $field) {
                                if ($key != $primary) {
                                    if ($fieldsRequired && in_array($key, $fieldsRequired) && !$object->$key && $key != 'id_product_attribute') {
                                        if ($class_name == 'Delivery')
                                            $object->$key = 0;
                                        elseif ($class_name == 'Image') {
                                            $ok = false;
                                            $error = 'Insert ' . $class_name . ' width ' . $primary . '=' . (int)$data_xml->$primary . ' error because ' . $key . ' is required';
                                            if (method_exists('Tools', 'error_log')) {
                                                Tools::error_log($error . "\n", 3, dirname(__file__) . '/../xml/' . $import_history['file_name'] . '/errors.log');
                                            }
                                            break;
                                        } else {
                                            $object->$key = '0';
                                            $error = 'Insert ' . $class_name . ' ' . $primary . '=' . (int)$data_xml->$primary . ' but ' . $key . ' does not exist';
                                            if (method_exists('Tools', 'error_log')) {
                                                Tools::error_log($error . "\n", 3, dirname(__file__) . '/../xml/' . $import_history['file_name'] . '/errors.log');
                                            }
                                        }
                                    }
                                }
                            }
                            if (!$ok)
                                continue;
                        }
                        if ($class_name == 'Customer' && $object->id_gender > 2)
                            $object->id_gender = 2;
                        if ($class_name == 'Customer' && $object->is_guest) {
                            Configuration::updateValue('PS_GUEST_CHECKOUT_ENABLED', 1);
                            Db::getInstance()->execute('INSERT INTO ' . _DB_PREFIX_ . 'guest(id_operating_system,id_web_browser,id_customer) VALUES (0,1,0)');
                            $object->id_guest = Db::getInstance()->Insert_ID();
                        }
                        if ($class_name == 'Address') {
                            $object->company = 'emply';
                        }
                        if (Db::getInstance()->getRow('SELECT * FROM ' . _DB_PREFIX_ . 'ets_woo2press_' . pSQL($table) . '_import WHERE id_old=' . (int)$data_xml->$primary . ' AND id_import_history="' . (int)$id_import_history . '"', false))
                            continue;
                        if ($class_name == 'SpecificPrice' && $object->price <= 0) {
                            $object->price = 0;
                        }
                        if ($object->id) {
                            $object->update();
                        } else {
                            if ($import_history['force_all_id_number'] && $class_name != 'Employee' && $class_name != 'Carrier')
                                $object->id = $data_xml->$primary;

                            if ($class_name == 'Language' && $object->id && $object->iso_code && Language::downloadAndInstallLanguagePack($object->iso_code) === true) {
                                $object->id = Language::getIdByIso($object->iso_code, true);
                            } else
                                $object->add();
                        }
                        if ($class_name == 'Group') {
                            if (!Db::getInstance()->executeS('SELECT * FROM ' . _DB_PREFIX_ . 'module_group WHERE id_group=' . (int)$object->id)) {
                                $module_groups = Db::getInstance()->executeS('SELECT * FROM ' . _DB_PREFIX_ . 'module_group WHERE id_group=1');
                                if ($module_groups) {
                                    foreach ($module_groups as $module_group)
                                        Db::getInstance()->execute('INSERT INTO ' . _DB_PREFIX_ . 'module_group VALUES("' . (int)$module_group['id_module'] . '","' . (int)$object->id . '")');
                                }
                            }
                        }
                        if (isset($object->date_add) && isset($data_xml->date_add)) {
                            Db::getInstance()->execute('UPDATE ' . _DB_PREFIX_ . pSQL($table) . ' SET date_add="' . pSQL((string)$data_xml->date_add) . '" WHERE ' . pSQL($primary) . '="' . (int)$object->id . '"');
                        }
                        if (isset($object->date_upd) && isset($data_xml->date_upd)) {
                            Db::getInstance()->execute('UPDATE ' . _DB_PREFIX_ . pSQL($table) . ' SET date_upd="' . pSQL((string)$data_xml->date_upd) . '" WHERE ' . pSQL($primary) . '="' . (int)$object->id . '"');
                        }
                        if (($class_name == 'Customer' && !Configuration::get('ETS_WOO2PRESS_NEW_PASSWD')) || ($class_name == 'Employee' && !Configuration::get('ETS_WOO2PRESS_NEW_PASSWD'))) {
                            if ($class_name == 'Customer') {
                                Db::getInstance()->execute('UPDATE ' . _DB_PREFIX_ . 'customer SET passwd_old_wp="' . (string)$data_xml->passwd . '" WHERE id_customer=' . (int)$object->id);
                            }
                            if ($class_name == 'Employee') {
                                Db::getInstance()->execute('UPDATE ' . _DB_PREFIX_ . 'employee SET passwd_old_wp="' . (string)$data_xml->passwd . '" WHERE id_employee=' . (int)$object->id);
                            }
                        }
                        if (isset($object->id) && $object->id) {
                            $imported++;
                            Db::getInstance()->execute('UPDATE ' . _DB_PREFIX_ . 'ets_woo2press_import_history SET number_import ="' . (int)$imported . '" WHERE id_import_history=' . (int)$id_import_history);
                            Db::getInstance()->execute('INSERT INTO ' . _DB_PREFIX_ . 'ets_woo2press_' . pSQL($table) . '_import values("","' . (int)$data_xml->$primary . '","' . (int)$object->id . '","' . (int)$id_import_history . '")');
                        } else {
                            $error = 'Insert ' . $class_name . ' width ' . $primary . '=' . (int)$data_xml->$primary . ' error because ' . $primary . ' is null';
                            if (method_exists('Tools', 'error_log')) {
                                Tools::error_log($error . "\n", 3, dirname(__file__) . '/../xml/' . $import_history['file_name'] . '/errors.log');
                            }
                        }

                        if ($class_name == 'Image' && $object->id && isset($data_xml->link_image)) {
                            $url = str_replace(' ', '%20', (string)$data_xml->link_image);
                            if (!$this->copyImg14($object->id_product, $object->id, $url)) {
                                $object->delete();
                            }
                        }
                        if ($class_name == 'Carrier' && $object->id && isset($data_xml->link_image) && (string)$data_xml->link_image) {
                            $url = str_replace(' ', '%20', (string)$data_xml->link_image);
                            $url = urldecode(trim($url));
                            $parced_url = parse_url($url);
                            if (!function_exists('http_build_url')) {
                                include_once(_PS_MODULE_DIR_ . 'ets_woo2pres/classes/http_build_url.php');
                            }
                            $url = http_build_url('', $parced_url);
                            $context = stream_context_create(array('http' => array('header' => 'User-Agent: Mozilla compatible')));
                            if (file_exists(_PS_SHIP_IMG_DIR_ . '/' . (int)$object->id . '.jpg'))
                                @unlink(_PS_SHIP_IMG_DIR_ . '/' . (int)$object->id . '.jpg');
                            if (is_file(_PS_TMP_IMG_DIR_ . 'carrier_mini_' . (int)$object->id . '.jpg')) {
                                @unlink(_PS_TMP_IMG_DIR_ . 'carrier_mini_' . (int)$object->id . '.jpg');
                            }
                            Woo2PressDataImport::copy($url, _PS_SHIP_IMG_DIR_ . '/' . (int)$object->id . '.jpg', $context);
                        }
                        if (($class_name == 'Category' || $class_name == 'Supplier' || $class_name == 'Manufacturer') && $object->id && isset($data_xml->link_image)) {
                            $url = str_replace(' ', '%20', (string)$data_xml->link_image);
                            $this->copyImg14($object->id, null, $url, $class_name);
                        }
                        Configuration::updateValue('PS_WOO2PRESS_IMPORTING', _DB_PREFIX_ . $table);
                        if ($imported % 100 == 0 || $class_name == 'Image' && $imported % 5 == 0 || time() - $start_time > 60) {
                            die('Imported (Auto stop) ' . $class_name . '=' . $imported);
                        }
                    }
                }
            }
            $readXML->deleteFileXML();
        }
        if ($readXML->imported) {
            Db::getInstance()->execute('UPDATE ' . _DB_PREFIX_ . 'ets_woo2press_import_history SET currentindex =1 WHERE id_import_history=' . (int)$id_import_history);
        }
    }

    public function copyImg($id_entity, $id_image = null, $url = '', $entity = 'products', $regenerate = true)
    {
        $tmpfile = tempnam(_PS_TMP_IMG_DIR_, 'ps_import');
        $watermark_types = explode(',', Configuration::get('WATERMARK_TYPES'));
        switch ($entity) {
            default:
            case 'products':
                if (is_file(_PS_TMP_IMG_DIR_ . 'product_mini_' . (int)$id_entity . '.jpg')) {
                    @unlink(_PS_TMP_IMG_DIR_ . 'product_mini_' . (int)$id_entity . '.jpg');
                }
                if (is_file(_PS_TMP_IMG_DIR_ . 'product_mini_' . (int)$id_entity . '_' . (int)Context::getContext()->shop->id . '.jpg')) {
                    @unlink(_PS_TMP_IMG_DIR_ . 'product_mini_' . (int)$id_entity . '_' . (int)Context::getContext()->shop->id . '.jpg');
                }
                $image_obj = new Image($id_image);
                $path = $image_obj->getPathForCreation();
                break;
            case 'Category':
                $entity = 'categories';
                $path = _PS_CAT_IMG_DIR_ . (int)$id_entity;
                if (is_file(_PS_TMP_IMG_DIR_ . 'category_mini_' . (int)$id_entity . '.jpg')) {
                    @unlink(_PS_TMP_IMG_DIR_ . 'category_mini_' . (int)$id_entity . '.jpg');
                }
                if (is_file(_PS_TMP_IMG_DIR_ . 'product_mini_' . (int)$id_entity . '_' . (int)Context::getContext()->shop->id . '.jpg')) {
                    @unlink(_PS_TMP_IMG_DIR_ . 'product_mini_' . (int)$id_entity . '_' . (int)Context::getContext()->shop->id . '.jpg');
                }
                break;
            case 'Manufacturer':
                $entity = 'manufacturers';
                $path = _PS_MANU_IMG_DIR_ . (int)$id_entity;
                if (is_file(_PS_TMP_IMG_DIR_ . 'manufacturer_mini_' . (int)$id_entity . '.jpg')) {
                    @unlink(_PS_TMP_IMG_DIR_ . 'manufacturer_mini_' . (int)$id_entity . '.jpg');
                }
                if (is_file(_PS_TMP_IMG_DIR_ . 'manufacturer_mini_' . (int)$id_entity . '_' . (int)Context::getContext()->shop->id . '.jpg')) {
                    @unlink(_PS_TMP_IMG_DIR_ . 'manufacturer_mini_' . (int)$id_entity . '_' . (int)Context::getContext()->shop->id . '.jpg');
                }
                break;
            case 'Supplier':
                $entity = 'suppliers';
                $path = _PS_SUPP_IMG_DIR_ . (int)$id_entity;
                if (is_file(_PS_TMP_IMG_DIR_ . 'supplier_mini_' . (int)$id_entity . '.jpg')) {
                    @unlink(_PS_TMP_IMG_DIR_ . 'supplier_mini_' . (int)$id_entity . '.jpg');
                }
                if (is_file(_PS_TMP_IMG_DIR_ . 'supplier_mini_' . (int)$id_entity . '_' . (int)Context::getContext()->shop->id . '.jpg')) {
                    @unlink(_PS_TMP_IMG_DIR_ . 'supplier_mini_' . (int)$id_entity . '_' . (int)Context::getContext()->shop->id . '.jpg');
                }
                break;
            case 'stores':
                $path = _PS_STORE_IMG_DIR_ . (int)$id_entity;
                break;
        }
        $url = urldecode(trim($url));
        $parced_url = parse_url($url);
        if (isset($parced_url['path'])) {
            $uri = ltrim($parced_url['path'], '/');
            $parts = explode('/', $uri);
            foreach ($parts as &$part) {
                $part = rawurlencode($part);
            }
            unset($part);
            $parced_url['path'] = '/' . implode('/', $parts);
        }

        if (isset($parced_url['query'])) {
            $query_parts = array();
            parse_str($parced_url['query'], $query_parts);
            $parced_url['query'] = http_build_query($query_parts);
        }
        if (!function_exists('http_build_url')) {
            if (version_compare(_PS_VERSION_, '1.6', '<'))
                include_once(_PS_MODULE_DIR_ . 'ets_woo2pres/classes/http_build_url.php');
            else
                require_once(_PS_TOOL_DIR_ . 'http_build_url/http_build_url.php');
        }
        $url = http_build_url('', $parced_url);
        $orig_tmpfile = $tmpfile;
        $context = stream_context_create(array('http' => array('header' => 'User-Agent: Mozilla compatible')));
        if (self::copy($url, $tmpfile, $context)) {
            //Evaluate the memory required to resize the image: if it's too much, you can't resize it.
            if (!ImageManager::checkImageMemoryLimit($tmpfile)) {
                @unlink($tmpfile);
                return false;
            }
            $tgt_width = $tgt_height = 0;
            $src_width = $src_height = 0;
            $error = 0;
            if (file_exists($path . '.jpg'))
                @unlink($path . '.jpg');
            ImageManager::resize($tmpfile, $path . '.jpg', null, null, 'jpg', false, $error, $tgt_width, $tgt_height, 5, $src_width, $src_height);
            $images_types = ImageType::getImagesTypes($entity, true);
            if ($regenerate) {
                $path_infos = array();
                $path_infos[] = array($tgt_width, $tgt_height, $path . '.jpg');
                foreach ($images_types as $image_type) {
                    $tmpfile = self::get_best_path($image_type['width'], $image_type['height'], $path_infos);
                    if (file_exists($path . '-' . Tools::stripslashes($image_type['name']) . '.jpg'))
                        @unlink($path . '-' . Tools::stripslashes($image_type['name']) . '.jpg');
                    if (ImageManager::resize(
                        $tmpfile,
                        $path . '-' . Tools::stripslashes($image_type['name']) . '.jpg',
                        $image_type['width'],
                        $image_type['height'],
                        'jpg',
                        false,
                        $error,
                        $tgt_width,
                        $tgt_height,
                        5,
                        $src_width,
                        $src_height
                    )) {
                        // the last image should not be added in the candidate list if it's bigger than the original image
                        if ($tgt_width <= $src_width && $tgt_height <= $src_height) {
                            $path_infos[] = array($tgt_width, $tgt_height, $path . '-' . Tools::stripslashes($image_type['name']) . '.jpg');
                        }
                        if ($entity == 'products') {
                            if (is_file(_PS_TMP_IMG_DIR_ . 'product_mini_' . (int)$id_entity . '.jpg')) {
                                @unlink(_PS_TMP_IMG_DIR_ . 'product_mini_' . (int)$id_entity . '.jpg');
                            }
                            if (is_file(_PS_TMP_IMG_DIR_ . 'product_mini_' . (int)$id_entity . '_' . (int)Context::getContext()->shop->id . '.jpg')) {
                                @unlink(_PS_TMP_IMG_DIR_ . 'product_mini_' . (int)$id_entity . '_' . (int)Context::getContext()->shop->id . '.jpg');
                            }
                        }
                    }
                    if (in_array($image_type['id_image_type'], $watermark_types)) {
                        Hook::exec('actionWatermark', array('id_image' => $id_image, 'id_product' => $id_entity));
                    }
                }
            }
        } else {
            @unlink($orig_tmpfile);
            return false;
        }
        @unlink($orig_tmpfile);
        return true;
    }

    protected static function get_best_path($tgt_width, $tgt_height, $path_infos)
    {
        $path_infos = array_reverse($path_infos);
        $path = '';
        foreach ($path_infos as $path_info) {
            list($width, $height, $path) = $path_info;
            if ($width >= $tgt_width && $height >= $tgt_height) {
                return $path;
            }
        }
        return $path;
    }

    public function copyImg14($id_entity, $id_image = null, $url, $entity = 'products')
    {
        $tmpfile = tempnam(_PS_TMP_IMG_DIR_, 'ps_import');
        $watermark_types = explode(',', Configuration::get('WATERMARK_TYPES'));
        switch ($entity) {
            default:
            case 'products':
                $imageObj = new Image($id_image);
                $path = $imageObj->getPathForCreation();
                if (is_file(_PS_TMP_IMG_DIR_ . 'product_mini_' . (int)$id_entity . '.jpg')) {
                    @unlink(_PS_TMP_IMG_DIR_ . 'product_mini_' . (int)$id_entity . '.jpg');
                }
                break;
            case 'Category':
                $entity = 'categories';
                $path = _PS_CAT_IMG_DIR_ . (int)$id_entity;
                if (is_file(_PS_TMP_IMG_DIR_ . 'categorie_mini_' . (int)$id_entity . '.jpg')) {
                    @unlink(_PS_TMP_IMG_DIR_ . 'categorie_mini_' . (int)$id_entity . '.jpg');
                }
                break;
            case 'Manufacturer':
                $entity = 'manufacturers';
                $path = _PS_MANU_IMG_DIR_ . (int)$id_entity;
                if (is_file(_PS_TMP_IMG_DIR_ . 'manufacturer_mini_' . (int)$id_entity . '.jpg')) {
                    @unlink(_PS_TMP_IMG_DIR_ . 'manufacturer_mini_' . (int)$id_entity . '.jpg');
                }
                break;
            case 'Supplier':
                $entity = 'suppliers';
                $path = _PS_SUPP_IMG_DIR_ . (int)$id_entity;
                if (is_file(_PS_TMP_IMG_DIR_ . 'supplier_mini_' . (int)$id_entity . '.jpg')) {
                    @unlink(_PS_TMP_IMG_DIR_ . 'supplier_mini_' . (int)$id_entity . '.jpg');
                }
                break;
        }
        $url_source_file = str_replace(' ', '%20', trim($url));
        $url_source_file = Tools::file_get_contents($url_source_file);
        if (@file_put_contents($tmpfile, $url_source_file) && file_exists($tmpfile)) {
            if (file_exists($path . '.jpg'))
                @unlink($path . '.jpg');
            imageResize($tmpfile, $path . '.jpg');
            $imagesTypes = ImageType::getImagesTypes($entity);
            foreach ($imagesTypes as $imageType) {
                if (file_exists($path . '-' . Tools::stripslashes($imageType['name']) . '.jpg'))
                    @unlink($path . '-' . Tools::stripslashes($imageType['name']) . '.jpg');
                imageResize($tmpfile, $path . '-' . Tools::stripslashes($imageType['name']) . '.jpg', $imageType['width'], $imageType['height']);
            }
            if (in_array($imageType['id_image_type'], $watermark_types))
                Module::hookExec('watermark', array('id_image' => $id_image, 'id_product' => $id_entity));
        } else {
            @unlink($tmpfile);
            return false;
        }
        @unlink($tmpfile);
        return true;
    }

    public static function copy($source, $destination, $stream_context = null)
    {
        if (is_null($stream_context) && !preg_match('/^https?:\/\//', $source)) {
            return @copy($source, $destination);
        }
        return @file_put_contents($destination, self::file_get_contents($source, false, $stream_context));
    }

    public static function file_get_contents($url, $use_include_path = false, $stream_context = null, $curl_timeout = 5)
    {
        if ($stream_context == null && preg_match('/^https?:\/\//', $url)) {
            $stream_context = @stream_context_create(array('http' => array('timeout' => $curl_timeout)));
        }
        if (function_exists('curl_init') && !in_array(Tools::getRemoteAddr(), array('127.0.0.1', '::1'))) {
            $curl = curl_init();
            curl_setopt_array($curl, array(
                CURLOPT_AUTOREFERER => true,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_MAXREDIRS => 5,
                CURLOPT_RETURNTRANSFER => 1,
                CURLOPT_URL => html_entity_decode($url),
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_SSL_VERIFYHOST => false,
            ));
            $content = curl_exec($curl);
            curl_close($curl);
            return $content;
        } elseif (in_array(ini_get('allow_url_fopen'), array('On', 'on', '1')) || !preg_match('/^https?:\/\//', $url)) {
            return Tools::file_get_contents($url, $use_include_path, $stream_context);
        } else {
            return false;
        }
    }
}