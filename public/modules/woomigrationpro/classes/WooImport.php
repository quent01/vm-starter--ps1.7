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

require_once 'WooClient.php';
require_once "loggers/WooLogger.php";
require_once "WooValidator.php";

class WooImport
{
    const UNFRIENDLY_ERROR = false;

    // --- Objects, Option & response vars:
    // wooComerce version 4.9.8
    protected $validator;
    protected $obj;
    protected $process;
    protected $client;
    protected $query;
    protected $url;
    protected $force_ids;
    protected $regenerate;
    protected $image_path;
    protected $image_supplier_path;
    protected $version;
    protected $shop_is_feature_active;
    protected $mapping;
    protected $ps_validation_errors = true;
    protected $prices_include_tax;
    protected $prices_include_tax_autoload;
    protected $default_tax_rate;

    protected $error_msg;
    protected $warning_msg;
    protected $response;
    protected $wpml;

    // --- Constructor / destructor:

    public function __construct(WooMigrationProProcess $process, $version, $url_cart, $force_ids, WooClient $client = null, WooQuery $query = null)
    {
        $this->regenerate = false; //@TODO dynamic from step two
        $this->process = $process;
        $this->version = $version;
        $this->url = $url_cart;
        $this->force_ids = $force_ids;
        $this->client = $client;
        $this->query = $query;
        $this->mapping = WooMigrationProMapping::listMapping(true, true);
        $this->shop_is_feature_active = Shop::isFeatureActive();
        $this->wpml = Configuration::get($this->module->name . '_wpml');
        $this->module = Module::getInstanceByName('woomigrationpro');
        $this->logger = new WooLogger();
        $this->validator = new WooValidator();
    }

    // --- Configuration methods:

    public function setImagePath($string)
    {
        $this->image_path = $string;
    }

    public function setDefaultTaxRate($int)
    {
        $this->default_tax_rate = $int;
    }

    public function setPricesIncludeTax($string)
    {
        $this->prices_include_tax = $string;
    }

    public function setPricesIncludeTaxAutoload($string)
    {
        $this->prices_include_tax_autoload = $string;
    }

    public function setImageSupplierPath($string)
    {
        $this->image_supplier_path = $string;
    }

    public function preserveOn()
    {
        $this->force_ids = true;
    }

    public function preserveOff()
    {
        $this->force_ids = false;
    }

    // --- After object methods:

    public function getErrorMsg()
    {
        return $this->error_msg;
    }


    public function getWarningMsg()
    {
        return $this->warning_msg;
    }

    public function getResponse()
    {
        return $this->response;
    }

    // --- Import methods:

    public function manufacturers($manufacturers)
    {

        //Load images for manufacturers to temporary dir
        $this->loadImagesToLocal($manufacturers['manufacturers'], 'id_manufacturer', 'manufacturers', $this->url, $this->image_path);

        foreach ($manufacturers['manufacturers'] as $manufacturer) {
            if ($manufacturerObj = $this->createObjectModel('Manufacturer', $manufacturer['id_manufacturer'])) {
                $manufacturerObj->name = $manufacturer['name'];
                $manufacturerObj->date_add = date('Y-m-d H:i:s', time());
                $manufacturerObj->date_upd = date('Y-m-d H:i:s', time());
                $manufacturerObj->active = 1;
                $res = false;
                $err_tmp = '';
                $this->validator->setObject($manufacturerObj);
                $this->validator->checkFields();
                $manufacturer_error_tmp = $this->validator->getValidationMessages();
                if ($manufacturerObj->id && $manufacturerObj->manufacturerExists($manufacturerObj->id)) {
                    try {
                        $res = $manufacturerObj->update();
                    } catch (PrestaShopException $e) {
                        $err_tmp = $e->getMessage();
                    }
                }

                if (!$res) {
                    try {
                        $res = $manufacturerObj->add(false);
                    } catch (PrestaShopException $e) {
                        $err_tmp = $e->getMessage();
                    }
                }

                if (!$res) {
                    $this->showMigrationMessageAndLog(sprintf(WooImport::displayError($this->module->l('Manufacturer (ID: %1$s) cannot be saved. %2$s')), (isset($manufacturer['id_manufacturer']) && !WooImport::isEmpty($manufacturer['id_manufacturer'])) ? Tools::safeOutput($manufacturer['id_manufacturer']) : 'No ID', $err_tmp), 'Manufacturer');
                } else {
                    $url = $manufacturer['url'];
                    $fileExt = pathinfo(parse_url($url, PHP_URL_PATH), PATHINFO_EXTENSION);
                    $FilePath = _PS_TMP_IMG_DIR_ . '/mp_temp_dir/manufacturers/' . $manufacturer['id_manufacturer'] . '.' . $fileExt;
                    if (file_exists($FilePath) && !(WooImport::copyImg($manufacturerObj->id, null, $FilePath, 'manufacturers', $this->regenerate))) {
                        $this->showMigrationMessageAndLog($url . ' ' . self::displayError($this->module->l('can not be copied.')), 'Manufacturer', true);
                    }

                    //@TODO Associate manufacturers to shop
                    self::addLog('Manufacturer', $manufacturer['id_manufacturer'], $manufacturerObj->id);
                }
                $this->showMigrationMessageAndLog($manufacturer_error_tmp, 'Manufacturer');
            }
        }
        $this->updateProcess(count($manufacturers['manufacturers']));
    }

    public function taxes($taxes, $taxRulesGroups)
    {
        // import tax
        foreach ($taxes['taxes'] as $tax) {
            if ($taxObject = $this->createObjectModel('Tax', $tax['id_tax'])) {
                $l = Configuration::get('PS_LANG_DEFAULT');
                $taxObject->rate = $tax['rate'];
                $taxObject->active = 1;
                if ($this->version >= 1.5) {
                    $taxObject->deleted = 0;
                }
                $taxObject->name[$l] = $tax['name'];


                $res = false;
                $err_tmp = '';

                $this->validator->setObject($taxObject);
                $this->validator->checkFields();
                $tax_error_tmp = $this->validator->getValidationMessages();
                if ($taxObject->id && Tax::existsInDatabase($taxObject->id, 'tax')) {
                    try {
                        $res = $taxObject->update();
                    } catch (PrestaShopException $e) {
                        $err_tmp = $e->getMessage();
                    }
                }
                if (!$res) {
                    try {
                        $res = $taxObject->add(false);
                    } catch (PrestaShopException $e) {
                        $err_tmp = $e->getMessage();
                    }
                }

                if (!$res) {
                    $this->showMigrationMessageAndLog(sprintf(WooImport::displayError($this->module->l('Tax (ID: %1$s) cannot be saved. %2$s')), (isset($tax['id_tax']) && !WooImport::isEmpty($tax['id_tax'])) ? Tools::safeOutput($tax['id_tax']) : 'No ID', $err_tmp), 'Tax');
                } else {
                    self::addLog('Tax', $tax['id_tax'], $taxObject->id);
                }
                $this->showMigrationMessageAndLog($tax_error_tmp, 'Tax');
            }
        }
        // import tax rules group
        foreach ($taxRulesGroups as $taxRulesGroup) {
            if (WooImport::isEmpty($taxRulesGroup)) {
                $taxRulesGroup = 'standart';
            }
            if (!WooMigrationProConvertDataStructur::getTaxRulesGroups($taxRulesGroup) && WooImport::isEmpty(WooMigrationProConvertDataStructur::getTaxRulesGroups($taxRulesGroup))) {
                $taxRulesGroupModel = new TaxRulesGroup();
                $taxRulesGroupModel->name = $taxRulesGroup;
                unset($taxRulesGroup);
                $taxRulesGroupModel->active = 1;
                $taxRulesGroupModel->date_add = date('Y-m-d H:i:s', time());
                $taxRulesGroupModel->date_upd = date('Y-m-d H:i:s', time());

                $res = false;
                $err_tmp = '';

                $this->validator->setObject($taxRulesGroupModel);
                $this->validator->checkFields();
                $tax_rule_group_error_tmp = $this->validator->getValidationMessages();

                if (!$res) {
                    try {
                        $res = $taxRulesGroupModel->add(false);
                    } catch (PrestaShopException $e) {
                        $err_tmp = $e->getMessage();
                    }
                }

                if (!$res) {
                    $this->showMigrationMessageAndLog(sprintf(WooImport::displayError($this->module->l('Tax Rules Group (ID: %1$s) cannot be saved. %2$s')), (isset($taxRulesGroup) && !WooImport::isEmpty($taxRulesGroup)) ? Tools::safeOutput($taxRulesGroup) : 'No Name', $err_tmp), 'TaxRulesGroup');
                } else {
                    continue;
                }
                $this->showMigrationMessageAndLog($tax_rule_group_error_tmp, 'TaxRulesGroup');
            }
        }

        foreach ($taxes['tax_rules'] as $taxRule) {
            if ($taxRuleModel = $this->createObjectModel('TaxRule', $taxRule['id_tax_rule'])) {
                if (WooImport::isEmpty($taxRule['class'])) {
                    $taxRule['class'] = 'standart';
                }
                $tax_rules_groups = WooMigrationProConvertDataStructur::getTaxRulesGroups($taxRule['class']);
                unset($taxRule['class']);
                $taxRuleModel->id_tax_rules_group = $tax_rules_groups[0]['id_tax_rules_group'];
                //get country from target shop db by iso code
                $iso_code = $this->checkIsoCode($taxRule['id_country']);
                if (!WooImport::isEmpty($iso_code)) {
                    if (Validate::isLanguageIsoCode($iso_code)) {
                        if (!WooImport::isEmpty(CountryCore::getByIso($iso_code))) {
                            $taxRuleModel->id_country = CountryCore::getByIso($iso_code);
                            if (!WooImport::isEmpty($taxRule['id_state'])) {
                                if (!WooImport::isEmpty(StateCore::getIdByIso($taxRule['id_state']))) {
                                    $taxRuleModel->id_state = StateCore::getIdByIso($taxRule['id_state']);
                                } else {
                                    $taxRuleModel->id_state = 0;
                                }
                            } else {
                                $taxRuleModel->id_state = 0;
                            }
                        } else {
                            $this->showMigrationMessageAndLog(sprintf(WooImport::displayError('Tax Rule (ID: %1$s) ' . $iso_code . ' Country is not Valid.'), (isset($taxRule['id_tax_rule']) && !WooImport::isEmpty($taxRule['id_tax_rule'])) ? Tools::safeOutput($taxRule['id_tax_rule']) : 'No ID'), 'TaxRule');
                        }
                    } else {
                        $this->showMigrationMessageAndLog(sprintf(WooImport::displayError('Tax Rule (ID: %1$s) ' . $iso_code . ' Country Iso Code is not Valid.'), (isset($taxRule['id_tax_rule']) && !WooImport::isEmpty($taxRule['id_tax_rule'])) ? Tools::safeOutput($taxRule['id_tax_rule']) : 'No ID'), 'TaxRule');
                    }
                } else {
                    $taxRuleModel->id_country = 0;
                }
                $taxRuleModel->id_tax = $taxRule['id_tax_rule'];
                $taxRuleModel->zipcode_from = 0;
                $taxRuleModel->zipcode_to = 0;
                $taxRuleModel->behavior = 0;

                $res = false;
                $err_tmp = '';
                $this->validator->setObject($taxRuleModel);
                $this->validator->checkFields();
                $tax_rule_error_tmp = $this->validator->getValidationMessages();

                if ($taxRuleModel->id && TaxRule::existsInDatabase($taxRuleModel->id, 'tax_rule')) {
                    try {
                        $res = $taxRuleModel->update();
                    } catch (PrestaShopException $e) {
                        $err_tmp = $e->getMessage();
                    }
                }
                if (!$res) {
                    try {
                        $res = $taxRuleModel->add(false);
                    } catch (PrestaShopException $e) {
                        $err_tmp = $e->getMessage();
                    }
                }
                if (!$res) {
                    $this->showMigrationMessageAndLog(sprintf(WooImport::displayError($this->module->l('Tax Rule (ID: %1$s) cannot be saved. %2$s')), (isset($taxRule['id_tax_rule']) && !WooImport::isEmpty($taxRule['id_tax_rule'])) ? Tools::safeOutput($taxRule['id_tax_rule']) : 'No ID', $err_tmp), 'TaxRule');
                } else {
                    self::addLog('TaxRule', $taxRule['id_tax_rule'], $taxRuleModel->id);
                }
                $this->showMigrationMessageAndLog($tax_rule_error_tmp, 'TaxRule');
            }
        }

        $this->updateProcess(count($taxes['taxes']));
    }

    public function categories($categories, $innerMethodCall = false)
    {
        $this->loadImagesToLocal($categories['category_img'], 'id_category', 'categories', $this->url, $this->image_path);
        foreach ($categories['category'] as $category) {
            $categories_home_root = array(Configuration::get('PS_ROOT_CATEGORY'), Configuration::get('PS_HOME_CATEGORY'));
            if (isset($category['id_category']) && in_array((int)$category['id_category'], $categories_home_root)) {
                $this->showMigrationMessageAndLog(WooImport::displayError($this->module->l('The category ID cannot be the same as the Root category ID or the Home category ID.')), 'Category');
            }
            if ($categoryObj = $this->createObjectModel('Category', $category['id_category'])) {
                $categoryObj->active = 1;
                if ($category['id_parent'] != 0) {
                    $category['id_parent'] = DB::getInstance()->getRow('SELECT category_target_id FROM `' . _DB_PREFIX_ . 'woomigrationpro_category` WHERE  category_source_id = ' .$category['id_parent']. '')['category_target_id'];
                }
                if (isset($category['id_parent']) && !in_array((int)$category['id_parent'], $categories_home_root) && (int)$category['id_parent'] != 0) {
                    if (!Category::categoryExists((int)$category['id_parent'])) {
                        // -- if parent category not exist create it
                        $this->client->serializeOn();
                        $this->client->setPostData($this->query->singleCategory($this->wpml, (int)$category['id_parent']));
                        if ($this->client->query()) {
                            $parentCategory = $this->client->getContent();
                            $import = new WooImport($this->process, $this->version, $this->url, $this->force_ids, $this->client, $this->query);
                            $import->setImagePath($this->image_path);
                            $import->categories($parentCategory, true);

                            $this->error_msg = $import->getErrorMsg();
                            $this->warning_msg = $import->getWarningMsg();
                            $this->response = $import->getResponse();
                        } else {
                            $this->showMigrationMessageAndLog(WooImport::displayError('Can\'t execute query to source Shop. ' . $this->client->getMessage()), 'Category');
                        }
                    }
                } else {
                    $categoryObj->id_parent = Configuration::get('PS_HOME_CATEGORY');
                }

                $id_parent = self::getLocalID('Category', (int)$category['id_parent'], 'data');
                $categoryObj->id_parent = $id_parent ? $id_parent : Configuration::get('PS_HOME_CATEGORY');
                $categoryObj->position = $category['position'];
                $categoryObj->date_add = date('Y-m-d H:i:s', time());
                $categoryObj->date_upd = date('Y-m-d H:i:s', time());


                if (isset($categories['category_lang'][$category['ID']])) {
                    foreach ($categories['category_lang'][$category['ID']] as $lang) {
                        $lang['id_lang'] = self::getLanguageID($lang['id_lang']);
                        $categoryObj->name[$lang['id_lang']] = Tools::htmlentitiesDecodeUTF8($lang['name']);   //  htmlspecialchars_decode  $lang['name'];

                        $categoryObj->link_rewrite[$lang['id_lang']] = $lang['link_rewrite'];

                        if (isset($categoryObj->link_rewrite[$lang['id_lang']]) && !WooImport::isEmpty($categoryObj->link_rewrite[$lang['id_lang']])) {
                            $valid_link = Validate::isLinkRewrite($categoryObj->link_rewrite[$lang['id_lang']]);
                        } else {
                            $valid_link = false;
                        }
                        if (!$valid_link) {
                            $categoryObj->link_rewrite[$lang['id_lang']] = Tools::link_rewrite($categoryObj->name[$lang['id_lang']]);

                            if ($categoryObj->link_rewrite[$lang['id_lang']] == '') {
                                $categoryObj->link_rewrite[$lang['id_lang']] = 'friendly-url-autogeneration-failed';
                                $this->showMigrationMessageAndLog(sprintf(WooImport::displayError($this->module->l('URL rewriting failed to auto-generate a friendly URL for: %s')), $categoryObj->name[$lang['id_lang']]), 'Category');
                            }

                            $this->showMigrationMessageAndLog(sprintf(WooImport::displayError($this->module->l('The link for %1$s (ID: %2$s) was re-written as %3$s.')), $lang['link_rewrite'], (isset($category['id_category']) && !WooImport::isEmpty($category['id_category'])) ? $category['id_category'] : 'null', $categoryObj->link_rewrite[$lang['id_lang']]), 'Category');
                        }

                        $categoryObj->description[$lang['id_lang']] = $lang['description'];
                        $categoryObj->meta_title[$lang['id_lang']] = $lang['name'];
                        $categoryObj->meta_description[$lang['id_lang']] = $lang['description'];
                        $categoryObj->meta_keywords[$lang['id_lang']] = $lang['name'];
                    }
                } else {
                    $id_lang = Configuration::get('PS_LANG_DEFAULT');
                    $categoryObj->name[$id_lang] = Tools::htmlentitiesDecodeUTF8($category['name']); //  htmlspecialchars_decode  $lang['name'];
                    $categoryObj->link_rewrite[$id_lang] = $category['slug'];
                    $categoryObj->description[$id_lang] = $category['description'];
                }

                //@TODO get shop id from step-2

                $categoryObj->id_shop_default = (int)Configuration::get('PS_SHOP_DEFAULT');


                $res = false;
                $err_tmp = '';

                $this->validator->setObject($categoryObj);
                $this->validator->checkFields();
                $category_error_tmp = $this->validator->getValidationMessages();

                if ($categoryObj->id && $categoryObj->id == $categoryObj->id_parent) {
                    $this->showMigrationMessageAndLog(WooImport::displayError($this->module->l('A category cannot be its own parent category.')), 'Category');
                    continue;
                }
                if ($categoryObj->id == Configuration::get('PS_ROOT_CATEGORY')) {
                    $this->showMigrationMessageAndLog(WooImport::displayError($this->module->l('The root category cannot be modified.')), 'Category');
                    continue;
                }

                /* No automatic nTree regeneration for import */
                $categoryObj->doNotRegenerateNTree = true;
                // If id category AND id category already in base, trying to update
                if ($categoryObj->id && $categoryObj->categoryExists($categoryObj->id) && !in_array($categoryObj->id, $categories_home_root)) {
                    try {
                        $res = $categoryObj->update();
                    } catch (PrestaShopException $e) {
                        $err_tmp = $e->getMessage();
                    }
                }

                // If no id_category or update failed
                if (!$res) {
                    try {
                        $res = $categoryObj->add(false);
                    } catch (PrestaShopException $e) {
                        $err_tmp = $e->getMessage();
                    }
                }
                $this->showMigrationMessageAndLog($category_error_tmp, 'Category');

                // If both failed, mysql error
                if (!$res) {
                    $this->showMigrationMessageAndLog(sprintf(WooImport::displayError($this->module->l('Category (ID: %1$s) cannot be saved. %2$s')), (isset($category['id_category']) && !WooImport::isEmpty($category['id_category'])) ? Tools::safeOutput($category['id_category']) : 'No ID', $err_tmp), 'Category');
                } else {
                    foreach ($categories['category_img'] as $image_cat) {
                        if ($image_cat['id_category'] == $category['id_category']) {
                            if (!empty($image_cat['meta_value'][0])) {
                                $url = $image_cat['meta_value'][0];
                                $fileExt = pathinfo(parse_url($url, PHP_URL_PATH), PATHINFO_EXTENSION);
                                $FilePath = _PS_TMP_IMG_DIR_ . '/mp_temp_dir/categories/' . $category['id_category'] . '.' . $fileExt;
                                if (file_exists($FilePath) && !(WooImport::copyImg($categoryObj->id, null, $FilePath, 'categories', $this->regenerate))) {
                                    $this->showMigrationMessageAndLog($url . ' ' . self::displayError($this->module->l('can not be copied.')), 'Category', true);
                                }
                            }
                        }
                    }

                    //@TODO Associate category to shop
                    if (isset($categories['category_lang'][$category['ID']])) {
                        foreach ($categories['category_lang'][$category['ID']] as $lang) {
                            $result = Db::getInstance()->execute('REPLACE INTO `' . _DB_PREFIX_ . 'woomigrationpro_category` (`category_source_id`, `category_target_id`) 
                                VALUES ('. (int)$lang["id_category"] .', '. (int)$category['id_category'] .' )');
                            if (!$result) {
                                $this->showMigrationMessageAndLog(Db::getInstance()->getMsgError(), 'Category');
                            }
                        }
                    } else {
                        $result = Db::getInstance()->execute('REPLACE INTO `' . _DB_PREFIX_ . 'woomigrationpro_category` (`category_source_id`, `category_target_id`) 
                                VALUES ('. (int)$category["id_category"] .', '. (int)$category['id_category'] .' )');
                        if (!$result) {
                            $this->showMigrationMessageAndLog(Db::getInstance()->getMsgError(), 'Category');
                        }
                    }

                    self::addLog('Category', $category['id_category'], $categoryObj->id);
                }
            }
        }
        if (!$innerMethodCall) {
            $this->updateProcess(count($categories['category']));
        }
        Category::regenerateEntireNtree();
    }

    public function products($products)
    {
        $array_image = array();
        foreach ($products['product_imgs']['product_imgs_all'] as $img) {
            $array_image[$img['post_id']][] = $img;
        }
        $this->loadImagesToLocal($array_image, 'id_image', 'products', $this->url, $this->image_path);

        Module::setBatchMode(true);

//         import attribute  group  OK
        if (isset($products['attribute_group'])) {
            foreach ($products['attribute_group'] as $attributeGroup) {
                if ($attributeGroupObj = $this->createObjectModel('AttributeGroup', $attributeGroup['attribute_id'])) {
                    $attributeGroupObj->is_color_group = null;
                    $attributeGroupObj->group_type = 'select';
                    $l = Configuration::get('PS_LANG_DEFAULT');
                    $attributeGroupObj->name[$l] = $attributeGroup['attribute_name'];
                    $attributeGroupObj->public_name[$l] = $attributeGroup['attribute_label'];
                    $res = false;
                    $err_tmp = '';
                    $this->validator->setObject($attributeGroupObj);
                    $this->validator->checkFields();
                    $attribute_group_error_tmp = $this->validator->getValidationMessages();
                    if ($attributeGroupObj->id && AttributeGroup::existsInDatabase($attributeGroupObj->id, 'attribute_group')) {
                        try {
                            $res = $attributeGroupObj->update();
                        } catch (PrestaShopException $e) {
                            $err_tmp = $e->getMessage();
                        }
                    }
                    if (!$res) {
                        try {
                            $res = $attributeGroupObj->add(false);
                        } catch (PrestaShopException $e) {
                            $err_tmp = $e->getMessage();
                        }
                    }
                    if (!$res) {
                        $this->showMigrationMessageAndLog(sprintf(WooImport::displayError($this->module->l('AttributeGroup (ID: %1$s) cannot be saved. %2$s')), (isset($attributeGroup['id_attribute_group']) && !WooImport::isEmpty($attributeGroup['id_attribute_group'])) ? Tools::safeOutput($attributeGroup['id_attribute_group']) : 'No ID', $err_tmp), 'AttributeGroup');
                    } else {
                        self::addLog('AttributeGroup', $attributeGroup['attribute_id'], $attributeGroupObj->id);
                    }
                    $this->showMigrationMessageAndLog($attribute_group_error_tmp, 'AttributeGroup');
                }
            }
        }

        if (isset($products['attribute'])) {
            foreach ($products['attribute'] as $attribute) {
                if ($attributeObj = $this->createObjectModel('Attribute', $attribute['id_attribute'])) {
                    $attributeObj->id_attribute_group = self::getLocalID('AttributeGroup', $attribute['attribute_id'], 'data');
                    $attributeObj->color = '#5D9CEC';
//                    if (!WooImport::isEmpty($productAdditionalFourth['attribute_lang']) && false) {
//                        foreach ($products['attribute_lang'][$attribute['id_attribute']] as $lang) {
//                            //  if ($attribute['id_attribute'] == $lang['id_attribute']) {
//                            $lang['id_lang'] = self::getLanguageID($lang['id_lang']);
//                            $attributeObj->name[$lang['id_lang']] = $lang['name'];
//                            //  }
//                        }
//                    } else {
                    $l = Configuration::get('PS_LANG_DEFAULT');
                    $attributeObj->name[$l] = $attribute['name'];
//                    }
                    $res = false;
                    $err_tmp = '';
                    $this->validator->setObject($attributeObj);
                    $this->validator->checkFields();
                    $attribute_error_tmp = $this->validator->getValidationMessages();
                    if ($attributeObj->id && Attribute::existsInDatabase($attributeObj->id, 'attribute')) {
                        try {
                            $res = $attributeObj->update();
                        } catch (PrestaShopException $e) {
                            $err_tmp = $e->getMessage();
                        }
                    }
                    if (!$res) {
                        try {
                            $res = $attributeObj->add(false);
                        } catch (PrestaShopException $e) {
                            $err_tmp = $e->getMessage();
                        }
                    }
                    if (!$res) {
                        $this->showMigrationMessageAndLog(sprintf(WooImport::displayError($this->module->l('Attribute (ID: %1$s) cannot be saved. %2$s')), (isset($attribute['id_attribute']) && !WooImport::isEmpty($attribute['id_attribute'])) ? Tools::safeOutput($attribute['id_attribute']) : 'No ID', $err_tmp), 'Attribute');
                    } else {
                        self::addLog('Attribute', $attribute['id_attribute'], $attributeObj->id);
                    }
                    $this->showMigrationMessageAndLog($attribute_error_tmp, 'Attribute');
                }
            }
        }

        foreach ($products['product'] as $product) {
            if ($productObj = $this->createObjectModel('Product', (int)$product['id_product'])) {
                if (Tools::strlen($product['post_name']) == 0) {
                    continue;
                }
                //Add product details from product_meta
                $product_meta = $products['product_meta'][(int)$product['ID']];
                if (isset($product_meta)) {
                    $productObj->id_supplier = 0;
                    $productObj->reference = $product_meta['_sku'];
                    $productObj->supplier_reference = $product_meta['supplier_reference'];
                    $productObj->location = $product_meta['location'];
                    $productObj->width = $product_meta['_width']; // product_meta
                    $productObj->height = $product_meta['_height']; // product_meta
                    $productObj->depth = $product_meta['_length']; // product_meta
                    $productObj->weight = $product_meta['_weight'];
                    $productObj->quantity_discount = $product_meta['quantity_discount'];
                    $productObj->ean13 = $product_meta['ean13'];
                    $productObj->upc = $product_meta['upc'];
                    $productObj->cache_is_pack = $product_meta['cache_is_pack'];
                    $productObj->cache_has_attachments = $product_meta['cache_has_attachments'];
                    $manufacturer_local_id = self::getLocalID('Manufacturer', $products['product_manufacturer'][$product['id_product']][0]['id_manufacturer'], 'data');
                    $productObj->id_manufacturer = $manufacturer_local_id;
                    $id_default_cat = DB::getInstance()->getRow('SELECT category_target_id FROM `' . _DB_PREFIX_ . 'woomigrationpro_category` WHERE  category_source_id = ' .$products['product_cat'][$product['ID']][0]['id_category']. '')['category_target_id'];
                    if (Tools::strlen($id_default_cat) > 0) {
                        if (!WooImport::isEmpty(self::getLocalID('Category', $id_default_cat, 'data'))) {
                            $id_default_cat_from_local = $id_category = self::getLocalID('Category', $id_default_cat, 'data');
                        } else {
                            $id_default_cat_from_local = $id_default_cat;
                        }
                    }
                    if (Tools::strlen($id_default_cat_from_local == 0)) {
                        $id_default_cat_from_local = Configuration::get('PS_HOME_CATEGORY');
                    }
                    $productObj->id_category_default = $id_default_cat_from_local;
                    unset($id_default_cat_from_local);

                    if (Tools::strlen($product_meta['_tax_class'] == 0)) { // product_meta
                        $product_meta['_tax_class'] = 'standart';
                    }
                    $tax_rules_group = WooMigrationProConvertDataStructur::getTaxRulesGroups($product_meta['_tax_class']);
                    unset($product['_tax_class']);
                    $productObj->id_tax_rules_group = $tax_rules_group[0]['id_tax_rules_group'];
                    $productObj->on_sale = $product_meta['on_sale'];
                    $productObj->online_only = $product_meta['online_only'];
                    $productObj->ecotax = $product_meta['ecotax'];
                    $productObj->minimal_quantity = 1;
                    $product_woo_price = 0;
                    if (isset($product_meta['_max_variation_regular_price']) && !WooImport::isEmpty($product_meta['_max_variation_regular_price'])) {
                        $product_woo_price = $product_meta['_max_variation_regular_price'];
                    } else {
                        if (isset($product_meta['_regular_price']) && !WooImport::isEmpty($product_meta['_regular_price'])) {
                            $product_woo_price = $product_meta['_regular_price'];
                        } else {
                            if (isset($product_meta['_regular_price']) && !WooImport::isEmpty($product_meta['_regular_price'])) {
                                $product_woo_price = $product_meta['_price'];
                            }
                        }
                    }

                    if ($this->prices_include_tax == 'no' && $this->prices_include_tax_autoload == 'no') {
                        $productObj->price = $product_woo_price;  //@TODO fix taxes RATE issue
                    } else {
                        if (isset($this->default_tax_rate)) {
                            $productObj->price = $product_woo_price / (1 + $this->default_tax_rate / 100);
                        } else {
                            $productObj->price = $product_woo_price;
                        }
                    }
                    $productObj->wholesale_price = str_replace(',', '.', $product_meta['_purchase_price']);
                    if (Tools::strlen($productObj->wholesale_price) == 0) {
                        $productObj->wholesale_price = 0;
                    }
                    if (Tools::strlen($productObj->price) == 0) {
                        $productObj->price = 0;
                    }
                    $productObj->price = WooImport::wround($productObj->price);
                    $productObj->wholesale_price = WooImport::wround($productObj->wholesale_price);
                    $productObj->unity = $product_meta['unity'];
                    $productObj->unit_price_ratio = $product_meta['unit_price_ratio'];
                    $productObj->additional_shipping_cost = $product_meta['additional_shipping_cost'];
                    $productObj->customizable = $product_meta['customizable'];
                    $productObj->text_fields = $product_meta['text_fields'];
                    $productObj->uploadable_files = $product_meta['uploadable_files'];
                    if ($product['active'] == 'publish') {
                        $productObj->active = 1;
                    } else {
                        $productObj->active = 0;
                    }

                    $productObj->available_for_order = 1;
                    $productObj->condition = $product_meta['condition'];
                    $productObj->show_price = 1;
                    $productObj->indexed = 0; // always zero for new PS $product['indexed'];
                    $productObj->cache_default_attribute = $product_meta['cache_default_attribute'];
                    $productObj->date_add = $product['date_add'];
                    $productObj->date_upd = $product['post_upd'];
                    $productObj->out_of_stock = $product_meta['out_of_stock'];
                    $productObj->quantity = ($product_meta['_stock'] == 'null' || $product_meta['_stock'] == '') ? 0 : $product_meta['_stock'];
                    $productObj->is_virtual = ($product_meta['_virtual'] == 'no') ? 0 : 1;
                    if ($product_meta['_visibility'] == 'visible') {
                        $product_meta['_visibility'] = 'both';
                    } else {
                        if ($product_meta['_visibility'] == 'hidden') {
                            $product_meta['_visibility'] = 'none';
                        }
                    }
                    $productObj->visibility = $product_meta['_visibility'];
                    if (!isset($products['product_lang'][$product['id_product']])) {
                        $l = Configuration::get('PS_LANG_DEFAULT');
                        $productObj->meta_description[$l] = $product_meta['_yoast_wpseo_metadesc'];
                        $productObj->meta_keywords[$l] = $product_meta['_yoast_wpseo_focuskw'];
                        $productObj->meta_title[$l] = $product_meta['_yoast_wpseo_title'];
                        $productObj->name[$l] = $product['post_title'];
                        $productObj->link_rewrite[$l] = $product['post_name'];


                        if (isset($productObj->link_rewrite[$l]) && isset($productObj->link_rewrite[$l])) {
                            $valid_link = Validate::isLinkRewrite($productObj->link_rewrite[$l]);
                        } else {
                            $valid_link = false;
                        }
                        if (!$valid_link) {
                            if (isset($product['post_name'])) {
                                $productObj->link_rewrite[$l] = Tools::link_rewrite($product['post_name']);
                            } else {
                                $productObj->link_rewrite[$l] = Tools::link_rewrite($product['post_title']);
                            }


                            if ($productObj->link_rewrite[$l] == '') {
                                $productObj->link_rewrite[$l] = 'friendly-url-autogeneration-failed';
                                $this->showMigrationMessageAndLog(sprintf(WooImport::displayError($this->module->l('URL rewriting failed to auto-generate a friendly URL for: %s')), $productObj->name[$l]), 'Product');
                            }

                            $this->showMigrationMessageAndLog(sprintf(WooImport::displayError($this->module->l('The link for %1$s (ID: %2$s) was re-written as %3$s.')), $product['link_rewrite'], (isset($product['id_product']) && !WooImport::isEmpty($product['id_product'])) ? $product['id_product'] : 'null', $productObj->link_rewrite[$l]), 'Product');
                        }
                        $productObj->description[$l] = str_replace(array("", "\r", "\n"), "<br/>", Tools::htmlentitiesDecodeUTF8($product['post_content']));
                        $productObj->description_short[$l] = str_replace(array("", "\r", "\n"), "<br/>", $product['post_excerpt']);
                    }
                }
                if (isset($products['product_lang'][$product['id_product']])) {
                    foreach ($products['product_lang'][$product['id_product']] as $lang) {
                        $localLang = self::getLanguageID($lang['id_lang']);

                        if (isset($products['product_langs_meta'][$product['id_product']][$lang['id_lang']])) {
                            foreach ($products['product_langs_meta'][$product['id_product']][$lang['id_lang']] as $langMeta) {
                                $productObj->meta_description[$localLang] = ($langMeta['meta_key'] === '_yoast_wpseo_metadesc' ? $langMeta['meta_value'] : '');
                                $productObj->meta_keywords[$localLang] = ($langMeta['meta_key'] === '_yoast_wpseo_focuskw' ? $langMeta['meta_value'] : '');
                                $productObj->meta_title[$localLang] = ($langMeta['meta_key'] === '_yoast_wpseo_title' ? $langMeta['meta_value'] : '');
                            }
                        }
                        if ($productObj->meta_title[$localLang] == '' || $productObj->meta_title[$localLang] == null) {
                            $productObj->meta_title[$localLang] = $lang['post_title'];
                        }

                        $productObj->name[$localLang] = $lang['post_title'];
                        $productObj->link_rewrite[$localLang] = $lang['post_name'];

                        if (isset($productObj->link_rewrite[$localLang]) && isset($productObj->link_rewrite[$localLang])) {
                            $valid_link = Validate::isLinkRewrite($productObj->link_rewrite[$localLang]);
                        } else {
                            $valid_link = false;
                        }
                        if (!$valid_link) {
                            if (isset($lang['post_name'])) {
                                $productObj->link_rewrite[$localLang] = Tools::link_rewrite($lang['post_name']);
                            } else {
                                $productObj->link_rewrite[$localLang] = Tools::link_rewrite($lang['post_title']);
                            }


                            if ($productObj->link_rewrite[$localLang] == '') {
                                $productObj->link_rewrite[$localLang] = 'friendly-url-autogeneration-failed';
                                $this->showMigrationMessageAndLog(sprintf(WooImport::displayError($this->module->l('URL rewriting failed to auto-generate a friendly URL for: %s')), $productObj->name[$lang['id_lang']]), 'Product');
                            }
                            $this->showMigrationMessageAndLog(sprintf(WooImport::displayError($this->module->l('The link for %1$s (ID: %2$s) was re-written as %3$s.')), $lang['link_rewrite'], (isset($product['id_product']) && !WooImport::isEmpty($product['id_product'])) ? $product['id_product'] : 'null', $productObj->link_rewrite[$lang['id_lang']]), 'Product');
                        }
                        $productObj->description[$localLang] = str_replace(array("", "\r", "\n"), "<br/>", Tools::htmlentitiesDecodeUTF8($lang['post_content']));
                        $productObj->description_short[$localLang] = str_replace(array("", "\r", "\n"), "<br/>", $lang['post_excerpt']);
                        //  }
                    }
                }

                $l = Configuration::get('PS_LANG_DEFAULT');
                $productObj->available_now[$l] = $product['_availability_instock_notification'];
                $productObj->available_later[$l] = $product['_availability_backorder_notification'];
                //@TODO get shop id from step-2
                $productObj->id_shop_default = (int)Configuration::get('PS_SHOP_DEFAULT');
                $res = false;
                $err_tmp = '';
                $this->validator->setObject($productObj);
                $this->validator->checkFields();
                $product_error_tmp = $this->validator->getValidationMessages();
                if ($productObj->id && Product::existsInDatabase((int)$productObj->id, 'product')) {
                    try {
                        $res = $productObj->update();
                    } catch (PrestaShopException $e) {
                        $err_tmp = $e->getMessage();
                    }
                }
                if (!$res) {
                    try {
                        $res = $productObj->add(false);
                    } catch (PrestaShopException $e) {
                        $err_tmp = $e->getMessage();
                    }
                }


                if (!$res) {
                    $this->showMigrationMessageAndLog(sprintf(WooImport::displayError($this->module->l('Product (ID: %1$s) cannot be saved. %2$s')), (isset($product['ID']) && !WooImport::isEmpty($product['ID'])) ? Tools::safeOutput($product['ID']) : 'No ID', $err_tmp), 'Product');
                } else {
                    Configuration::updateValue('woomigrationpro_product', $product['ID']);
                    $stock = ($products['product_meta'][$product['ID']]['_stock'] == 'null' || $products['product_meta'][$product['ID']]['_stock'] == '') ? 0 : $products['product_meta'][$product['ID']]['_stock'];
                    StockAvailable::setQuantity($productObj->id, 0, $stock);
                    if ($products['product_meta'][$product['ID']]['_stock_status'] == 'instock') {
                        StockAvailable::setProductOutOfStock($productObj->id, true);
                    }
                    if (isset($tax_rules_group)) {
                        Db::getInstance()->Execute('UPDATE `' . _DB_PREFIX_ . 'product` SET 
                                `id_tax_rules_group`=' . (int)$tax_rules_group[0]['id_tax_rules_group'] . ' WHERE  `id_product`=' . (int)$productObj->id . ';');
                        Db::getInstance()->Execute('UPDATE `' . _DB_PREFIX_ . 'product_shop` SET 
                                `id_tax_rules_group`=' . (int)$tax_rules_group[0]['id_tax_rules_group'] . ' WHERE  `id_product`=' . (int)$productObj->id . ';');
                    }
                    // import accessories @Warrning require froce_products_id
                    if (isset($product_meta['_related_ids'])) {
                        $this->importProductAccessories($product['id_product'], $product_meta['_related_ids']);
                    }

                    //Import product categories
                    $product_cat = array();
                    if (isset($products['product_cat'][$product['ID']])) {
                        foreach ($products['product_cat'][$product['ID']] as $categoryProduct) {
                            $categoryProduct['id_category'] = DB::getInstance()->getRow('SELECT category_target_id FROM `' . _DB_PREFIX_ . 'woomigrationpro_category` WHERE  category_source_id = ' .$categoryProduct['id_category']. '')['category_target_id'];
                            $id_category = self::getLocalID('Category', $categoryProduct['id_category'], 'data');
                            if (Tools::strlen($id_category) == 0) {
                                $id_category = $categoryProduct['id_category'];
                            }
                            $product_cat[] = $id_category;
                        }
                    }
                    if (count($product_cat) !== 0) {
                        $productObj->addToCategories($product_cat);
                    } else {
                        $id_home_cat = Configuration::get('PS_HOME_CATEGORY');
                        $product_cat[] = $id_home_cat;
                        $result = $productObj->addToCategories($product_cat);
                        if (!$result) {
                            $this->showMigrationMessageAndLog(WooImport::displayError('Can\'t add category_product. ' . Db::getInstance()->getMsgError()), 'Product');
                        }
                    }

//                  //import images
                    //@TODO FIX SAVE SAME IMAGE ID MIGRATIONPRODATA
                    foreach ($array_image[$product['ID']] as $image) {
                        if ($imageObject = new Image()) {
                            $imageObject->id_product = $productObj->id; //@TODO FIX IF PRODUCT IMAGE EXIST WITH COVER
                            $imageObject->position = Image::getHighestPosition($productObj->id) + 1;
                            // if ($product['_thumbnail_id'] == $image['id_image'] && !Image::getCover($productObj->id)) {
                            $imageObject->cover = 1;
                            //  } else {
                            //     $imageObject->cover = null;
                            // }
                            //language fields
                            $l = Configuration::get('PS_LANG_DEFAULT');
                            $imageObject->legend[$l] = $product['post_title'];

                            $res = false;
                            $err_tmp = '';
                            $this->validator->setObject($imageObject);
                            $this->validator->checkFields();
                            $image_error_tmp = $this->validator->getValidationMessages();
                            if ($imageObject->id && Image::existsInDatabase($imageObject->id, 'image')) {
                                try {
                                    $res = $imageObject->update();
                                } catch (PrestaShopException $e) {
                                    $err_tmp = $e->getMessage();
                                }
                            }
                            if (!$res) {
                                try {
                                    $res = $imageObject->add(false);
                                } catch (PrestaShopException $e) {
                                    $err_tmp = $e->getMessage();
                                }
                            }
                            if (!$res) {
                                $imageObject->cover = 0;
                                try {
                                    $res = $imageObject->add(false);
                                } catch (PrestaShopException $e) {
                                    $err_tmp = $e->getMessage();
                                }
                            }
                            if (!$res) {
                                $this->showMigrationMessageAndLog(sprintf(WooImport::displayError($this->module->l('Image (ID: %1$s) cannot be saved. %2$s')), (isset($image['id_image']) && !WooImport::isEmpty($image['id_image'])) ? Tools::safeOutput($image['id_image']) : 'No ID', $err_tmp), 'Image');
                            } else {
                                $url = $this->image_path . $image['meta_value'];
                                $filename = pathinfo(parse_url($url, PHP_URL_PATH), PATHINFO_FILENAME);
                                $fileExt = pathinfo(parse_url($url, PHP_URL_PATH), PATHINFO_EXTENSION);
                                $FilePath = _PS_TMP_IMG_DIR_ . '/mp_temp_dir/products/' . $filename . '.' . $fileExt;
                                if (file_exists($FilePath) && !(WooImport::copyImg($productObj->id, $imageObject->id, $FilePath, 'products', $this->regenerate))) {
                                    $this->showMigrationMessageAndLog($url . ' ' . self::displayError($this->module->l('can not be copied.')), 'Image', true);
                                }
                                if (self::getLocalID('Image', $image['id_image'], 'data')) {
                                    self::addLog('Image', $image['id_image'], $imageObject->id);
                                }
                            }
                            $this->showMigrationMessageAndLog($image_error_tmp, 'Image');
                        }
                    }

                    if (isset($product['_downloadable_files'])) {
                        foreach ($products['product_download'][$product['ID']] as $productDownload) {
                            if ($product['id_product'] == $productDownload['post_parent']) {
                                if ($productDownloadObject = $this->createObjectModel('ProductDownload', $productDownload['ID'])) {
                                    $productDownloadObject->id_product = $productObj->id;
                                    $productDownloadObject->display_filename = end(explode('/', $productDownload['meta_value']));
                                    $productDownloadObject->filename = sha1(microtime());
                                    $productDownloadObject->date_add = $productDownload['post_date'];
                                    $productDownloadObject->active = 1;
                                    $res = false;
                                    $err_tmp = '';
                                    $this->validator->setObject($productDownloadObject);
                                    $this->validator->checkFields();
                                    $product_download_error_tmp = $this->validator->getValidationMessages();
                                    if ($productDownloadObject->id && ProductDownload::existsInDatabase($productDownloadObject->id, 'product_download')) {
                                        try {
                                            $res = $productDownloadObject->update();
                                        } catch (PrestaShopException $e) {
                                            $err_tmp = $e->getMessage();
                                        }
                                    }
                                    if (!$res) {
                                        try {
                                            $res = $productDownloadObject->add(false);
                                        } catch (PrestaShopException $e) {
                                            $err_tmp = $e->getMessage();
                                        }
                                    }

                                    if (!$res) {
                                        $this->showMigrationMessageAndLog(sprintf(WooImport::displayError($this->module->l('ProductDownload (ID: %1$s) cannot be saved. Product (ID: %2$s). %3$s')), (isset($productDownload['ID']) && !WooImport::isEmpty($productDownload['ID'])) ? Tools::safeOutput($productDownload['ID']) : 'No ID', $productObj->id, $err_tmp), 'ProductDownload');
                                    } else {
                                        $client = new WooClient($this->url . '/migration_pro/server.php', Configuration::get('woomigrationpro_token'));
                                        $client->setPostData('../wp-content/uploads/' . $productDownload['meta_value']);
                                        $client->setTimeout(999);
                                        $client->query('file');
                                        file_put_contents(getcwd() . '/../download/' . $productDownloadObject->filename, $client->getContent());
                                        self::addLog('ProductDownload', $productDownload['ID'], $productDownloadObject->id);
                                    }
                                    $this->showMigrationMessageAndLog($product_download_error_tmp, 'ProductDownload');
                                }
                            }
                        }
                    }

//                  import Product Attribute
                    $add_combination = false;
                    $default_on = true;
                    foreach ($products['product_variation'][$product['ID']] as $productAttribute) {
                        if (empty($productAttribute['_regular_price']) && empty($productAttribute['_sku']) && empty($productAttribute['_weight'])) {
                            continue;
                        }
                        if ($combinationModel = $this->createObjectModel('Combination', $productAttribute['id_product_attribute'])) {
                            $combinationModel->id_product = $productObj->id;
                            $combinationModel->location = $productAttribute['location'];
                            $combinationModel->ean13 = $productAttribute['ean13'];
                            $combinationModel->upc = $productAttribute['upc'];
                            $combinationModel->quantity = (int)$productAttribute['_stock'];
                            $combinationModel->reference = $productAttribute['_sku'];
                            $combinationModel->supplier_reference = $productAttribute['supplier_reference'];
//                            $combinationModel->wholesale_price = $productAttribute['_regular_price'];
//                            $combinationModel->wholesale_price = self::isEmpty($product_meta['_max_variation_regular_price'])? $product_meta['_regular_price'] : $product_meta['_max_variation_regular_price'];
                            if ($this->prices_include_tax == 'no' && $this->prices_include_tax_autoload == 'no') {
                                $combinationModel->price = $productAttribute['_regular_price'] - $product_woo_price;
                            } else {
                                /**  Price without tax   **/
                                $combinationModel->price = $productAttribute['_regular_price'] / (1 + $this->default_tax_rate / 100) - $productObj->price;
                            }
                            if (Tools::strlen($combinationModel->price) == 0) {
                                $combinationModel->price = 0;
                                $combinationModel->wholesale_price = 0;
                            }
                            $combinationModel->ecotax = $productAttribute['ecotax'];
                            $combinationModel->weight = $productAttribute['_weight'];
//                            $combinationModel->unit_price_impact = $productObj->price - $productAttribute['_regular_price'];
                            $combinationModel->minimal_quantity = (isset($productAttribute['minimal_quantity']) && isset($productAttribute['minimal_quantity'])) ? $productAttribute['minimal_quantity'] : 1;
                            $combinationModel->price = WooImport::wround($combinationModel->price);
                            $combinationModel->wholesale_price = WooImport::wround($combinationModel->wholesale_price);
                            if ($default_on) {
                                $combinationModel->default_on = 1;
                                $default_on = false;
                            } else {
                                $combinationModel->default_on = null;
                            }
                            $res = false;
                            $err_tmp = '';
                            $this->validator->setObject($combinationModel);
                            $this->validator->checkFields();
                            $combination_error_tmp = $this->validator->getValidationMessages();
                            if ($combinationModel->id && Combination::existsInDatabase($combinationModel->id, 'product_attribute')) {
                                try {
                                    $res = $combinationModel->update();
                                } catch (PrestaShopException $e) {
                                    $err_tmp = $e->getMessage();
                                }
                            }
                            if (!$res) {
                                try {
                                    $res = $combinationModel->add(false);
                                } catch (PrestaShopException $e) {
                                    $err_tmp = $e->getMessage();
                                }
                            }
                            if (!$res) {
                                $this->showMigrationMessageAndLog(sprintf(WooImport::displayError($this->module->l('Product attribute (ID: %1$s) cannot be saved. %2$s')), (isset($productAttribute['id_product_attribute']) && !WooImport::isEmpty($productAttribute['id_product_attribute'])) ? Tools::safeOutput($productAttribute['id_product_attribute']) : 'No ID', $err_tmp), 'Combination');
                            } else {
                                $add_combination = true;
                                self::addLog('Combination', $productAttribute['id_product_attribute'], $combinationModel->id);
                                StockAvailable::setQuantity($combinationModel->id_product, $combinationModel->id, $productAttribute['_stock']);
                                if ($productObj->active) {
                                    StockAvailable::setProductDependsOnStock($combinationModel->id_product, true, null, $combinationModel->id);
                                } else {
                                    StockAvailable::setProductDependsOnStock($combinationModel->id_product, fasle, null, $combinationModel->id);
                                }
                                if ($productAttribute['_stock_status'] == 'instock') {
                                    StockAvailable::setProductOutOfStock($combinationModel->id_product, true, null, $combinationModel->id);
                                } else {
                                    StockAvailable::setProductOutOfStock($combinationModel->id_product, false, null, $combinationModel->id);
                                }

                                //import product_attribute_image
//                                $sql_values = array();
                                //   foreach ($product_attribute as $productAttributeImage) {
                                //    if ($productAttributeImage['id_product_attribute'] == $productAttribute['id_product_attribute']) {
//                                $id_product_attribute_img = self::getLocalID('Image', (int)$productAttributeImage['_thumbnail_id'], 'data');
//                                $id_product_attribute = $combinationModel->id;
//                                $sql_values[] = '(' . (int)$id_product_attribute . ', ' . (int)$id_product_attribute_img . ')';
                                //  }
                                // }
//                                if (isset($sql_values)) {
//                                    $result = Db::getInstance()->execute(
//                                        '
//                                        REPLACE INTO `' . _DB_PREFIX_ . 'product_attribute_image` (`id_product_attribute`, `id_image`)
//                                        VALUES ' . implode(',', $sql_values)
//                                    );
//                                    if (!$result) {
//                                        $this->showMigrationMessageAndLog(WooImport::displayError(
//                                            'Can\'t add product_attribute_image. ' . Db::getInstance()->getMsgError()
//                                        ), 'Combination');
//                                    }
//                                }
                            }
                            $this->showMigrationMessageAndLog($combination_error_tmp, 'Combination');

                            $attribute_ids = array();
                            foreach ($productAttribute as $key => $value) {
                                if (preg_match('/attribute_pa_/', $key)) {
//                                    foreach (explode('/', $value) as $val) {
                                    if (!empty($value)) {
                                        $value = str_replace("-", " ", $value);
                                        $key = str_replace("-", " ", $key);
                                        $attribute_ids[] = $this->getAttributeIdByName($value, str_replace('attribute_pa_', '', $key));
                                    }
//                                    }
                                } elseif (preg_match('/attribute_/', $key)) {
//                                    foreach (explode('/', $value) as $val) {
                                    if (!empty($value)) {
                                        $value = str_replace("-", " ", $value);
                                        $key = str_replace("-", " ", $key);
                                        $attribute_ids[] = $this->getAttributeIdByName($value, str_replace('attribute_', '', $key));
                                    }
//                                    }
                                }
                            }
//                            old combination
                            /* foreach ($products['product_combination'][$productAttribute['id_product_attribute']] as $combination) {
                                 $attribute_ids[] = $this->getAttributeIdByName($combination['name'], $combination['group_name']);
                             }*/
                            $productObj->addAttributeCombinaison((int)$combinationModel->id, $attribute_ids);
                            unset($attribute_ids);
                        }
                        // }
                        /**
                         * Add spesific price for attributes
                         */
                        $specificPriceObj = 0;
                        if (!self::isEmpty($productAttribute['_sale_price']) && $specificPriceObj = $this->createObjectModel('SpecificPrice', $productAttribute['id_product_attribute'])) {
                            $specificPriceObj->id_shop = 0;
                            $specificPriceObj->id_product = $productObj->id;
                            $specificPriceObj->id_currency = 0;
                            $specificPriceObj->id_country = 0;
                            $specificPriceObj->id_group = 0;
                            $specificPriceObj->price = -1;
                            $specificPriceObj->from_quantity = 1;
                            $reduction = ($product_woo_price - $productAttribute['_sale_price']);
                            $specificPriceObj->reduction = WooImport::wround($reduction);
                            if ($specificPriceObj->reduction < 0) {
                                $specificPriceObj->reduction = 0;
                            }
                            $specificPriceObj->reduction_type = 'amount';
                            $specificPriceObj->from = (WooImport::isEmpty($productAttribute['_sale_price_dates_from'])) ? 0 : date('Y-m-d h:i:s', $productAttribute['_sale_price_dates_from']);
                            $specificPriceObj->to = (WooImport::isEmpty($productAttribute['_sale_price_dates_to'])) ? 0 : date('Y-m-d h:i:s', $productAttribute['_sale_price_dates_to']);
                            $specificPriceObj->id_customer = isset($productAttribute['id_customer']) ? $productAttribute['id_customer'] : 0;
                            $specificPriceObj->id_cart = 0;
                            $specificPriceObj->id_product_attribute = $combinationModel->id;
                            $specificPriceObj->id_specific_price_rule = $productAttribute['id_specific_price_rule'];
                            $specificPriceObj->reduction_tax = (isset($productAttribute['reduction_tax']) && isset($productAttribute['reduction_tax'])) ? $productAttribute['reduction_tax'] : 1;
                            $res = false;
                            $err_tmp = '';
                            $this->validator->setObject($specificPriceObj);
                            $this->validator->checkFields();
                            $specific_price_error_tmp = $this->validator->getValidationMessages();
                            if ($specificPriceObj->id && SpecificPrice::existsInDatabase($specificPriceObj->id, 'specific_price')) {
                                try {
                                    $res = $specificPriceObj->update();
                                } catch (PrestaShopException $e) {
                                    $err_tmp = $e->getMessage();
                                }
                            }
                            if (!$res) {
                                try {
                                    $res = $specificPriceObj->add(false);
                                } catch (PrestaShopException $e) {
                                    $err_tmp = $e->getMessage();
                                }
                            }
                            if (!$res) {
                                $this->showMigrationMessageAndLog(sprintf(WooImport::displayError($this->module->l('SpecificPrice (ID: %1$s) cannot be saved. %2$s')), (isset($productAttribute['ID']) && !WooImport::isEmpty($productAttribute['ID'])) ? Tools::safeOutput($productAttribute['ID']) : 'No ID', $err_tmp), 'SpecificPrice');
                            } else {
                                self::addLog('SpecificPrice', $productAttribute['id_product_attribute'], $specificPriceObj->id);
                            }
                            $this->showMigrationMessageAndLog($specific_price_error_tmp, 'SpecificPrice');
                        }
                    }


                    // import product_specific price
                    if (!$add_combination && !self::isEmpty($product_meta['_sale_price']) && ($product_meta['_sale_price'] > 0 && $product_meta['_sale_price'] < $product_woo_price) && $specificPriceObj = $this->createObjectModel('SpecificPrice', $product['ID'])) {
                        if (!isset($product_meta['id_product_attribute'])) {
                            $specificPriceObj->id_shop = 0;
                            $specificPriceObj->id_product = $productObj->id;
                            $specificPriceObj->id_currency = 0;
                            $specificPriceObj->id_country = 0;
                            $specificPriceObj->id_group = 0;
                            $specificPriceObj->price = -1;
                            $specificPriceObj->from_quantity = 1;
//                            if ($this->prices_include_tax == 'no' && $this->prices_include_tax_autoload == 'no') {
                            $reduction = $product_woo_price - $product_meta['_sale_price'];
//                            } else {
//                                $reduction = ($productObj->price - $product_meta['_sale_price']) * (1 + $this->default_tax_rate / 100);
//                            }
                            $specificPriceObj->reduction = WooImport::wround($reduction);
                            if ($specificPriceObj->reduction < 0) {
                                $specificPriceObj->reduction = 0;
                            }
                            $specificPriceObj->reduction_type = 'amount';
                            $specificPriceObj->from = (WooImport::isEmpty($product_meta['_sale_price_dates_from'])) ? 0 : date('Y-m-d h:i:s', $product_meta['_sale_price_dates_from']);
                            $specificPriceObj->to = (WooImport::isEmpty($product_meta['_sale_price_dates_to'])) ? 0 : date('Y-m-d h:i:s', $product_meta['_sale_price_dates_from']);
                            $specificPriceObj->id_customer = (isset($product_meta['id_customer']) && isset($product_meta['id_customer'])) ? $product_meta['id_customer'] : 0;
                            $specificPriceObj->id_cart = 0;
                            $specificPriceObj->id_product_attribute = $product_meta['id_product_attribute'];
                            $specificPriceObj->id_specific_price_rule = $product_meta['id_specific_price_rule'];

                            $specificPriceObj->reduction_tax = (isset($product_meta['reduction_tax']) && isset($product_meta['reduction_tax'])) ? $product_meta['reduction_tax'] : 1;
                        } else {
                            $specificPriceObj->id_shop = 0;
                            $specificPriceObj->id_product = $productObj->id;
                            $specificPriceObj->id_currency = 0;
                            $specificPriceObj->id_country = 0;
                            $specificPriceObj->id_group = self::getCustomerGroupID($product_meta['id_group']);
                            $specificPriceObj->price = -1;
                            $specificPriceObj->from_quantity = 1;
                            if (isset($product['_sale_price']) && isset($product['_sale_price'])) {
                                $reduction = ($product['_regular_price'] - $product['_sale_price']);
                            } else {
                                $reduction = 0;
                            }
                            $specificPriceObj->reduction = $reduction;
                            $specificPriceObj->reduction_type = 'amount';
                            $specificPriceObj->from = (WooImport::isEmpty($product['_sale_price_dates_from'])) ? 0 : date('Y-m-d h:i:s', $product['_sale_price_dates_from']);
                            $specificPriceObj->to = (WooImport::isEmpty($product['_sale_price_dates_from'])) ? 0 : date('Y-m-d h:i:s', $product['_sale_price_dates_from']);
                            $specificPriceObj->id_customer = (isset($product['id_customer']) && isset($product['id_customer'])) ? $product['id_customer'] : 0;
                            $specificPriceObj->id_shop = 0;
                            $specificPriceObj->id_cart = 0;
                            /*
                                                                $specificPriceObj->id_product_attribute = self::getLocalID(
                                                                    'Combination',
                                                                    (int)$product['ID'],
                                                                    'data'
                                                                );
                                                                */
                            $specificPriceObj->id_specific_price_rule = $product['id_specific_price_rule'];
                            $specificPriceObj->reduction_tax = (isset($product['reduction_tax']) && isset($product['reduction_tax'])) ? $product['reduction_tax'] : 1;
                        }
                        $res = false;
                        $err_tmp = '';
                        $this->validator->setObject($specificPriceObj);
                        $this->validator->checkFields();
                        $specific_price_error_tmp = $this->validator->getValidationMessages();
                        if ($specificPriceObj->id && SpecificPrice::existsInDatabase($specificPriceObj->id, 'specific_price')) {
                            try {
                                $res = $specificPriceObj->update();
                            } catch (PrestaShopException $e) {
                                $err_tmp = $e->getMessage();
                            }
                        }
                        if (!$res) {
                            try {
                                $res = $specificPriceObj->add(false);
                            } catch (PrestaShopException $e) {
                                $err_tmp = $e->getMessage();
                            }
                        }
                        if (!$res) {
                            $this->showMigrationMessageAndLog(sprintf(WooImport::displayError($this->module->l('SpecificPrice (ID: %1$s) cannot be saved. %2$s')), (isset($product['ID']) && !WooImport::isEmpty($product['ID'])) ? Tools::safeOutput($product['ID']) : 'No ID', $err_tmp), 'SpecificPrice');
                        } else {
                            self::addLog('SpecificPrice', $product['ID'], $specificPriceObj->id);
                        }
                        $this->showMigrationMessageAndLog($specific_price_error_tmp, 'SpecificPrice');
                    }
                    //   }
                    //  }

                    //import product_tag
                    $tag_lists = array();
                    foreach ($products['product_tag'][$product['ID']] as $productTag) {
                        if (!self::isEmpty($productTag['tag_name'])) {
                            $tag_lists[] = $productTag['tag_name'] ;
                        }
                    }
                    foreach (Language::getLanguages() as $langs) {
                        if (!self::isEmpty($tag_lists)) {
                            Tag::addTags($langs['id_lang'], $productObj->id, $tag_lists);
                        }
                    }

                    if (count($this->error_msg) == 0) {
                        self::addLog('Product', $product['ID'], $productObj->id);
                        Module::processDeferedFuncCall();
                        Module::processDeferedClearCache();
                        Tag::updateTagCount();
                    }
                }
                $this->showMigrationMessageAndLog($product_error_tmp, 'Product');
            }
        }
        $this->updateProcess(count($products['product']));
    }

    public function importProductAccessories($id_product, $_related_ids)
    {
        $sql_values = array();
        $related_ids = unserialize($_related_ids);
        foreach ($related_ids as $related_product_id) {
            $sql_values[] = '(' . (int)$id_product . ', ' . (int)$related_product_id . ')';
        }
        if (!self::isEmpty($sql_values)) {
            $result = Db::getInstance()->execute('REPLACE INTO `' . _DB_PREFIX_ . 'accessory` (`id_product_1`, `id_product_2`) 
                                VALUES ' . implode(',', $sql_values));
            if (!$result) {
                if ($this->moduel_error_reporting) {
                    $this->showMigrationMessageAndLog(WooImport::displayError('Can\'t add accessory. ' . Db::getInstance()->getMsgError()), 'Product');
                }
            }
        }
    }

    public function customers($customers, $addresses)
    {
        foreach ($customers as $customer) {
            if ($customerObject = $this->createObjectModel('Customer', $customer['id_customer'])) {
                //wp reserve user firsst last name in one string.
                $firstLastName = explode(" ", $customer['name']);
                $customerObject->secure_key = $this->secure_key = md5(uniqid(rand(), true));
                $customerObject->lastname = self::checkEmptyProperty($customer['last_name'], "lastname");
                $customerObject->firstname = self::checkEmptyProperty($customer['first_name'], "firstname");
                $customerObject->email = self::checkEmptyProperty($customer['email'], "empty@empty.com");
                $encrpt_password = Tools::substr(_COOKIE_KEY_, 0, 6);
                $customerObject->passwd = Tools::encrypt($encrpt_password);
                $customerObject->id_gender = 1;
                $customerObject->newsletter = 0;
                $customerObject->newsletter_date_add = date('Y-m-d H:i:s', time());
                $customerObject->active = 1;
                $customerObject->id_default_group = Configuration::get('PS_CUSTOMER_GROUP');
                $customerObject->date_add = $customer['date_add'];
                $customerObject->date_upd = $customer['date_add'];
                $customerObject->website = $customer['website'];
                Configuration::updateValue('PS_GUEST_CHECKOUT_ENABLED', 1);
                $res = false;
                $err_tmp = '';
                $this->validator->setObject($customerObject);
                $this->validator->checkFields();
                $customer_error_tmp = $this->validator->getValidationMessages();
                if ($customerObject->id && Customer::existsInDatabase($customerObject->id, 'customer')) {
                    try {
                        $res = $customerObject->update();
                    } catch (PrestaShopException $e) {
                        $err_tmp = $e->getMessage();
                    }
                }
                if (!$res) {
                    try {
                        $res = $customerObject->add(false);
                    } catch (PrestaShopException $e) {
                        $err_tmp = $e->getMessage();
                    }
                }
                if (!$res) {
                    $this->showMigrationMessageAndLog(sprintf(WooImport::displayError($this->module->l('Customer (ID: %1$s) cannot be saved. %2$s')), (isset($customer['id_customer']) && !WooImport::isEmpty($customer['id_customer'])) ? Tools::safeOutput($customer['id_customer']) : 'No ID', $err_tmp), 'Customer');
                } else {
                    Configuration::updateValue('woomigrationpro_customer', $customer['id_customer']);
                    if ($addressObject = $this->createObjectModel('Address', $addresses[$customer['id_customer']]['id'])) {
                        $addressObject->id_customer = $customerObject->id;
                        //find country and state id from target
                        $iso_code = $this->checkIsoCode($addresses[$customer['id_customer']]['billing_country']);
                        if (isset($iso_code)) {
                            if (!WooImport::isEmpty($iso_code)) {
                                if (Validate::isLanguageIsoCode($iso_code)) {
                                    if (!WooImport::isEmpty(CountryCore::getByIso($iso_code))) {
                                        $addressObject->id_country = CountryCore::getByIso($iso_code);
                                        if (!WooImport::isEmpty($addresses[$customer['id_customer']]['billing_state'])) {
                                            if (!WooImport::isEmpty(StateCore::getIdByIso($addresses[$customer['id_customer']]['billing_state']))) {
                                                $addressObject->id_state = StateCore::getIdByIso($addresses[$customer['id_customer']]['billing_state']);
                                            } else {
                                                $addressObject->id_state = 0;
                                            }
                                        } else {
                                            $addressObject->id_state = 0;
                                        }
                                    } else {
                                        $this->showMigrationMessageAndLog(sprintf(WooImport::displayError('Address of Customer (ID: %1$s) ' . $iso_code . '  Country is not avilable on your database.'), (isset($addresses[$customer['id_customer']]['id_customer']) && !WooImport::isEmpty($addresses[$customer['id_customer']]['id_customer'])) ? Tools::safeOutput($addresses[$customer['id_customer']]['id_customer']) : 'No ID'), 'Address');
                                    }
                                } else {
                                    $this->showMigrationMessageAndLog(sprintf(WooImport::displayError('Address of Customer (ID: %1$s) ' . $iso_code . ' Country Iso Code is not Valid.'), (isset($addresses[$customer['id_customer']]['id_customer']) && !WooImport::isEmpty($addresses[$customer['id_customer']]['id_customer'])) ? Tools::safeOutput($addresses[$customer['id_customer']]['id_customer']) : 'No ID'), 'Address');
                                }
                            } else {
                                $this->showMigrationMessageAndLog(sprintf(WooImport::displayError('Address of Customer (ID: %1$s)  Country Iso Code is Null.Address can not be saved.'), (isset($customer['id_customer']) && !WooImport::isEmpty($customer['id_customer'])) ? Tools::safeOutput($customer['id_customer']) : 'No ID'), 'Address');
                            }
                            $addressObject->alias = 'Adress Alias';
                            $addressObject->company = $addresses[$customer['id_customer']]['billing_company'];
                            $addressObject->lastname = self::checkEmptyProperty($addresses[$customer['id_customer']]['billing_last_name'], 'emptyLastName');
                            $addressObject->firstname = self::checkEmptyProperty($addresses[$customer['id_customer']]['billing_first_name'], 'emptyFirstname');
                            $addressObject->address1 = self::checkEmptyProperty($addresses[$customer['id_customer']]['billing_address_1'], 'address 1');
                            $addressObject->address2 = self::checkEmptyProperty($addresses[$customer['id_customer']]['billing_address_2'], 'address 2');
                            $addressObject->postcode = $addresses[$customer['id_customer']]['billing_postcode'];
                            $addressObject->other = "bill";
                            $addressObject->city = self::checkEmptyProperty($addresses[$customer['id_customer']]['billing_city'], 'emptyCity');
                            $addressObject->phone = self::checkEmptyProperty($addresses[$customer['id_customer']]['billing_phone'], '000 000 000');
                            $addressObject->date_add = date('Y-m-d H:i:s', time());
                            $addressObject->date_upd = date('Y-m-d H:i:s', time());
                        } else {
                            $iso_code = $this->checkIsoCode($addresses[$customer['id_customer']]['shipping_country']);
                            if (!WooImport::isEmpty($iso_code)) {
                                if (Validate::isLanguageIsoCode($iso_code)) {
                                    if (!WooImport::isEmpty(CountryCore::getByIso($iso_code))) {
                                        $addressObject->id_country = CountryCore::getByIso($iso_code);
                                        if (!WooImport::isEmpty($addresses[$customer['id_customer']]['shipping_state'])) {
                                            if (!WooImport::isEmpty(StateCore::getIdByIso($addresses[$customer['id_customer']]['shipping_state']))) {
                                                $addressObject->id_state = StateCore::getIdByIso($addresses[$customer['id_customer']]['shipping_state']);
                                            } else {
                                                $addressObject->id_state = 0;
                                            }
                                        } else {
                                            $addressObject->id_state = 0;
                                        }
                                    } else {
                                        $this->showMigrationMessageAndLog(sprintf(WooImport::displayError('Shipping Address of Customer (ID: %1$s) ' . $iso_code . '  Country iso code is not avilable on your database.'), (isset($addresses[$customer['id_customer']]['id_customer']) && !WooImport::isEmpty($addresses[$customer['id_customer']]['id_customer'])) ? Tools::safeOutput($addresses[$customer['id_customer']]['id_customer']) : 'No ID'), 'Address');
                                    }
                                } else {
                                    $this->showMigrationMessageAndLog(sprintf(WooImport::displayError('Shipping Address of Customer (ID: %1$s) ' . $iso_code . '  Country Iso Code is not Valid.'), (isset($addresses[$customer['id_customer']]['id_customer']) && !WooImport::isEmpty($addresses[$customer['id_customer']]['id_customer'])) ? Tools::safeOutput($addresses[$customer['id_customer']]['id_customer']) : 'No ID'), 'Address');
                                }
                            } else {
                                $this->showMigrationMessageAndLog(sprintf(WooImport::displayError('Shipping Address of Customer (ID: %1$s) ' . $iso_code . ' Country Iso Code is Null.Address can not be saved.'), (isset($customer['id_customer']) && !WooImport::isEmpty($customer['id_customer'])) ? Tools::safeOutput($customer['id_customer']) : 'No ID'), 'Address');
                            }
                            $addressObject->alias = 'Adress Alias';
                            $addressObject->company = $addresses[$customer['id_customer']]['shipping_company'];
                            $addressObject->lastname = self::checkEmptyProperty($addresses[$customer['id_customer']]['shipping_last_name'], 'emptyLastname');
                            $addressObject->firstname = self::checkEmptyProperty($addresses[$customer['id_customer']]['shipping_first_name'], 'emptyFirstname');
                            $addressObject->address1 = self::checkEmptyProperty($addresses[$customer['id_customer']]['shipping_address_1'], 'address 1');
                            $addressObject->address2 = self::checkEmptyProperty($addresses[$customer['id_customer']]['shipping_address_2'], 'address 2');
                            $addressObject->postcode = $addresses[$customer['id_customer']]['shipping_postcode'];
                            $addressObject->city = self::checkEmptyProperty($addresses[$customer['id_customer']]['shipping_city'], 'emptyCity');
                            $addressObject->other = "ship";
                            $addressObject->phone = self::checkEmptyProperty($addresses[$customer['id_customer']]['shipping_phone'], '000 000 000');
                            $addressObject->date_add = date('Y-m-d H:i:s', time());
                            $addressObject->date_upd = date('Y-m-d H:i:s', time());
                        }
                        $res = false;
                        $err_tmp = '';
                        $this->validator->setObject($addressObject);
                        $this->validator->checkFields();
                        $address_error_tmp = $this->validator->getValidationMessages();
                        if ($addressObject->id && Address::existsInDatabase($addressObject->id, 'address')) {
                            try {
                                $res = $addressObject->update();
                            } catch (PrestaShopException $e) {
                                $err_tmp = $e->getMessage();
                            }
                        }
                        if (!$res) {
                            try {
                                $res = $addressObject->add(false);
                            } catch (PrestaShopException $e) {
                                $err_tmp = $e->getMessage();
                            }
                        }
                        if (!$res) {
                            $this->showMigrationMessageAndLog(sprintf(WooImport::displayError($this->module->l('Address of Customer (ID: %1$s) cannot be saved. %2$s')), (isset($addresses[$customer['id_customer']]['id_customer']) && !WooImport::isEmpty($addresses[$customer['id_customer']]['id_customer'])) ? Tools::safeOutput($addresses[$customer['id_customer']]['id_customer']) : 'No ID', $err_tmp), 'Address');
                        } else {
                            self::addLog('Address', $addresses[$customer['id_customer']]['id'], $addressObject->id);
                        }
                        $this->showMigrationMessageAndLog($address_error_tmp, 'Address');
                    }

                    if (count($this->error_msg) == 0) {
                        self::addLog('Customer', $customer['id_customer'], $customerObject->id);
                        WooMigrationProWoo::storeCustomerPass($customerObject->id, $customer['email'], $customer['passwd']);
                    }
                }
                $this->showMigrationMessageAndLog($customer_error_tmp, 'Customer');
            }
        }
        $this->updateProcess(count($customers));
    }


    public function orders($orders, $orderDetails, $orderHistorys)
    {
        foreach ($orders['order'] as $cart) {
            if ($cartObject = $this->createObjectModel('Cart', $cart['ID'])) {
                $id_customer = 0;
                $cartObject->id_carrier = 2;   //  fix it after carrier import
                $cartObject->id_lang = Configuration::get('PS_LANG_DEFAULT');
                if (!WooImport::isEmpty($cart['_customer_user'])) {
                    $id_customer = self::getLocalID('Customer', $cart['_customer_user'], 'data');
                }
                if ($id_customer < 1) {
                    $ids_customers = Customer::getCustomersByEmail($orders['billing_address'][$cart['ID']]['_billing_email']);
                    if (count($ids_customers) > 0) {
                        $id_customer = $ids_customers[0]['id_customer'];
                    } else {
                        $id_customer = $this->createCustomer($cart['ID'], $orders);
                    }
                }
                //get shipping and billing adress off costumer.(becouse shipping inser to db first and then bill)
                if (!WooImport::isEmpty($id_customer)) {
                    $id_address_delivery = AddressCore::getFirstCustomerAddressId($id_customer);
                    $id_address_invoice = WooMigrationProConvertDataStructur::getSecondCustomerAddressId($id_customer);
                } else {
                    continue;
                }
                if ($id_address_delivery < 1 && $id_address_invoice < 1) {
                    $order_address = self::createAddress($id_customer, $orders['shipping_address'][$cart['ID']], $orders['billing_address'][$cart['ID']]);
                    $id_address_delivery = $order_address[0];
                    $id_address_invoice = $order_address[1];
                }
                $cartObject->id_address_delivery = $id_address_delivery;
                $cartObject->id_address_invoice = !WooImport::isEmpty($id_address_invoice) ? $id_address_invoice : $id_address_delivery;
                $currency = $cart['_order_currency'];
                $cartObject->id_currency = WooMigrationProMapping::listMapping(true, false, true)['currencies'][$currency] ? WooMigrationProMapping::listMapping(true, false, true)['currencies'][$currency] : Configuration::get('PS_CURRENCY_DEFAULT');
                $cartObject->id_customer = $id_customer;
                $cartObject->id_guest = $id_customer;
                $customer = new Customer((int)$id_customer);
                unset($id_customer);
                $cartObject->id_carrier = 1;
                if (!WooImport::isEmpty($customer->secure_key)) {
                    $cartObject->secure_key = $customer->secure_key;
                } else {
                    //if order not belong to customer.
                    $cartObject->secure_key = $this->secure_key = md5(uniqid(rand(), true));
                }
                $cartObject->gift_message = $cart['gift_message'];
                $cartObject->mobil_theme = $cart['mobil_theme'];
                $cartObject->allow_seperated_package = $cart['allow_seperated_package '];
                $cartObject->date_add = date('Y-m-d H:i:s', time());
                $cartObject->date_upd = date('Y-m-d H:i:s', time());
                $res = false;
                $err_tmp = '';
                $this->validator->setObject($cartObject);
                $this->validator->checkFields();
                $cart_error_tmp = $this->validator->getValidationMessages();

                if ($cartObject->id && Cart::existsInDatabase($cartObject->id, 'cart')) {
                    try {
                        $res = $cartObject->update();
                    } catch (PrestaShopException $e) {
                        $err_tmp = $e->getMessage();
                    }
                }
                if (!$res) {
                    try {
                        $res = $cartObject->add(false);
                    } catch (PrestaShopException $e) {
                        $err_tmp = $e->getMessage();
                    }
                }
                if (!$res) {
                    $this->showMigrationMessageAndLog(sprintf(WooImport::displayError($this->module->l('Cart (ID: %1$s) cannot be saved. %2$s')), (isset($cart['ID']) && !WooImport::isEmpty($cart['ID'])) ? Tools::safeOutput($cart['ID']) : 'No ID', $err_tmp), 'Cart');
                } else {
                    if (count($this->error_msg) == 0) {
                        self::addLog('cart', $cart['ID'], $cartObject->id);
                    }
                }
                $this->showMigrationMessageAndLog($cart_error_tmp, 'Cart');
            }
        }
        foreach ($orders['order'] as $order) {
            if ($orderModel = $this->createObjectModel('Order', $order['id_order'], 'orders')) {
                $id_customer = 0;
                if (!WooImport::isEmpty($order['_customer_user'])) {
                    $id_customer = self::getLocalID('Customer', $order['_customer_user'], 'data');
                }

                if ($id_customer < 1) {
                    $ids_customers = Customer::getCustomersByEmail($orders['billing_address'][$order['id_order']]['_billing_email']);
                    if (count($ids_customers) > 0) {
                        $id_customer = $ids_customers[0]['id_customer'];
                    } else {
                        $id_customer = self::getLocalID('Customer', $order['id_order'], 'data');
                    }
                }
                if (!WooImport::isEmpty($id_customer)) {
                    $id_address_delivery = AddressCore::getFirstCustomerAddressId($id_customer);
                    $id_address_invoice = WooMigrationProConvertDataStructur::getSecondCustomerAddressId($id_customer);
                    if ($id_address_delivery < 1) {
                        $id_address_delivery = WooMigrationProConvertDataStructur::firstCustomerAddressId($id_customer);
                    }
                } else {
                    continue;
                }
                $orderModel->id_address_delivery = $id_address_delivery;
                $orderModel->id_address_invoice = !WooImport::isEmpty($id_address_invoice) ? $id_address_invoice : $id_address_delivery;
                $orderModel->id_cart = self::getLocalID('Cart', $order['ID'], 'data');
                $currency = $order['_order_currency'];
                $orderModel->id_currency = WooMigrationProMapping::listMapping(true, false, true)['currencies'][$currency] ? WooMigrationProMapping::listMapping(true, false, true)['currencies'][$currency] : Configuration::get('PS_CURRENCY_DEFAULT');
                $orderModel->id_lang = Configuration::get('PS_LANG_DEFAULT');
                $orderModel->id_customer = $id_customer;
                $customer = new Customer((int)$id_customer);
                unset($id_customer);
                $orderModel->id_carrier = 1;
                if (!WooImport::isEmpty($customer->secure_key)) {
                    $orderModel->secure_key = $customer->secure_key;
                } else {
                    //if order not belong to customer.
                    $orderModel->secure_key = $this->secure_key = md5(uniqid(rand(), true));
                }
                if (WooImport::isEmpty($order['_payment_method_title'])) {
                    $orderModel->payment = 'QuickPay';
                } else {
                    $orderModel->payment = $order['_payment_method_title'];
                }
                $orderModel->module = 'cheque';
                $orderModel->recyclable = 0;
                $orderModel->total_paid = $order['_order_total'];
                $orderModel->total_paid_tax_incl = $order['_order_total'];
                $orderModel->total_paid_real = $order['_order_total'];
                $orderModel->total_products = $order['_order_total'] - ($order['_order_shipping'] + $order['_order_shipping_tax']);
                $orderModel->total_products_wt = $order['_order_total'] - ($order['_order_shipping'] + $order['_order_shipping_tax']);
                $orderModel->total_shipping = $order['_order_shipping'] + $order['_order_shipping_tax'];
                $orderModel->carrier_tax_rate = ($order['_order_shipping_tax'] * 100) / $order['_order_shipping'];
                $orderModel->conversion_rate = 0;
                $orderModel->valid = 1;
                $orderModel->date_add = $order['post_date'];
                $orderModel->date_upd = $order['post_modified'];
                $orderModel->total_shipping_tax_incl = $order['_order_shipping'] + $order['_order_shipping_tax'];
                $orderModel->total_shipping_tax_excl = $order['_order_shipping'];
                $orderModel->reference = "#" . $order['id_order'];
                $orderModel->id_shop = (int)Configuration::get('PS_SHOP_DEFAULT');
                $orderModel->total_paid = WooImport::wround($orderModel->total_paid);
                $orderModel->total_paid_real = WooImport::wround($orderModel->total_paid_real);
                $orderModel->total_products = WooImport::wround($orderModel->total_products);
                $orderModel->total_products_wt = WooImport::wround($orderModel->total_products_wt);
                $orderModel->total_shipping = WooImport::wround($orderModel->total_shipping);
                $orderModel->total_shipping_tax_incl = WooImport::wround($orderModel->total_shipping_tax_incl);
                $orderModel->total_shipping_tax_excl = WooImport::wround($orderModel->total_shipping_tax_excl);
                $orderModel->total_paid_tax_incl = WooImport::wround($orderModel->total_paid_tax_incl);
                $orderModel->current_state = WooMigrationProMapping::listMapping(true, false, true)['order_states'][$order['post_status']];
                $res = false;
                $err_tmp = '';
                $this->validator->setObject($orderModel);
                $this->validator->checkFields();
                $order_error_tmp = $this->validator->getValidationMessages();
                if ($orderModel->id && self::existsInDatabase($orderModel->id, 'orders', 'order')) {
                    try {
                        $res = $orderModel->update();
                    } catch (PrestaShopException $e) {
                        $err_tmp = $e->getMessage();
                    }
                }
                if (!$res) {
                    try {
                        $res = $orderModel->add(false);
                    } catch (PrestaShopException $e) {
                        $err_tmp = $e->getMessage();
                    }
                }
                if (!$res) {
                    $this->showMigrationMessageAndLog(sprintf(WooImport::displayError($this->module->l('Order (ID: %1$s) cannot be saved. %2$s')), (isset($order['id_order']) && !WooImport::isEmpty($order['id_order'])) ? Tools::safeOutput($order['id_order']) : 'No ID', $err_tmp), 'Order');
                } else {
                    //import Order Detail
                    foreach ($orderDetails[$order['id_order']] as $orderDetail) {
                        if ($orderDetailModel = $this->createObjectModel('OrderDetail', $orderDetail['order_item_id'])) {
                            $orderDetailModel->id_order = $orderModel->id;
                            $id_product = self::getLocalID('Product', (int)$orderDetail['_product_id'], 'data');
                            $product_attribute_id = self::getLocalID('Combination', (int)$orderDetail['_variation_id'], 'data');
                            $orderDetailModel->product_id = WooImport::isEmpty($id_product) ? $orderDetail['_product_id'] : $id_product;
                            $orderDetailModel->id_warehouse = "0";
                            $orderDetailModel->product_attribute_id = WooImport::isEmpty($product_attribute_id) ? $orderDetail['_variation_id'] : $product_attribute_id;
                            $orderDetailModel->product_name = $orderDetail['order_item_name'];
                            $orderDetailModel->product_quantity = $orderDetail['_qty'];
                            $orderDetailModel->product_quantity_in_stock = ProductCore::getQuantity($id_product, $product_attribute_id);
                            $orderDetailModel->product_quantity_return = $orderDetail['product_quantity_return'];
                            $orderDetailModel->product_quantity_refunded = $orderDetail['product_quantity_refunded'];
                            $orderDetailModel->product_quantity_reinjected = $orderDetail['product_quantity_reinjected'];
                            $orderDetailModel->product_price = Tools::ps_round($orderDetail['_line_total'] / $orderDetail['_qty'], 6);
                            $orderDetailModel->reduction_percent = $orderDetail['reduction_percent'];
                            $orderDetailModel->reduction_amount = $orderDetail['reduction_amount'];
                            $orderDetailModel->group_reduction = $orderDetail['group_reduction'];
                            $orderDetailModel->product_quantity_discount = $orderDetail['product_quantity_discount'];
                            $orderDetailModel->product_ean13 = $orderDetail['product_ean13'];
                            $orderDetailModel->product_upc = $orderDetail['product_upc'];
                            $orderDetailModel->product_reference = $orderDetail['order_item_name'];
                            $orderDetailModel->product_supplier_reference = $orderDetail['product_supplier_reference'];
                            $orderDetailModel->product_weight = $orderDetail['product_weight'];
                            $orderDetailModel->tax_name = $orderDetail['_tax_class'];
                            $orderDetailModel->tax_rate = $orderDetail['tax_amount'] * 100 / Tools::ps_round($orderDetail['_line_total'], 6);
                            $orderDetailModel->ecotax = $orderDetail['ecotax'];
                            $orderDetailModel->ecotax_tax_rate = $orderDetail['ecotax_tax_rate'];
                            $orderDetailModel->discount_quantity_applied = $orderDetail['discount_quantity_applied'];
                            $orderDetailModel->download_hash = $orderDetail['download_hash'];
                            $orderDetailModel->download_nb = $orderDetail['download_nb'];
                            $orderDetailModel->download_deadline = $orderDetail['download_deadline'];
                            $orderDetailModel->id_shop = Configuration::get('PS_SHOP_DEFAULT');
                            $orderDetailModel->unit_price_tax_excl = Tools::ps_round($orderDetail['_line_total'] / $orderDetail['_qty'], 6);
                            $orderDetailModel->unit_price_tax_incl = Tools::ps_round((Tools::ps_round($orderDetail['_line_total'], 6) / $orderDetail['_qty']) + ($orderDetail['_line_tax'] / $orderDetail['_qty']), 6);
                            $orderDetailModel->total_price_tax_excl = Tools::ps_round($orderDetail['_line_total'], 6);
                            $orderDetailModel->total_price_tax_incl = Tools::ps_round($orderDetail['_line_total'], 6) + Tools::ps_round($orderDetail['_line_tax'], 6);
                            $orderDetailModel->total_shipping_price_tax_excl = $orderDetail['cost'];
                            $orderDetailModel->total_shipping_price_tax_incl = $orderDetail['cost'] + $orderDetail['shipping_tax_amount'];

                            //@TODO Nujno take generirovat informasii ot PS 1.4 nije
                            //                                    }

                            $res = false;
                            $err_tmp = '';
                            $this->validator->setObject($orderDetailModel);
                            $this->validator->checkFields();
                            $order_detail_error_tmp = $this->validator->getValidationMessages();
                            if ($orderDetailModel->id && OrderDetail::existsInDatabase(
                                $orderDetailModel->id,
                                'order_detail'
                            )) {
                                try {
                                    $res = $orderDetailModel->update();
                                } catch (PrestaShopException $e) {
                                    $err_tmp = $e->getMessage();
                                }
                            }
                            if (!$res) {
                                try {
                                    $res = $orderDetailModel->add(false);
                                } catch (PrestaShopException $e) {
                                    $err_tmp = $e->getMessage();
                                }
                            }
                            if (!$res) {
                                $this->showMigrationMessageAndLog(sprintf(WooImport::displayError($this->module->l('Order Detail (ID: %1$s) cannot be saved. %2$s')), (isset($orderDetail['id_order_detail']) && !WooImport::isEmpty($orderDetail['id_order_detail'])) ? Tools::safeOutput($orderDetail['id_order_detail']) : 'No ID', $err_tmp), 'OrderDetail');
                            } else {
                                self::addLog(
                                    'OrderDetail',
                                    $orderDetail['order_item_id'],
                                    $orderDetailModel->id
                                );
                            }
                            $this->showMigrationMessageAndLog($order_detail_error_tmp, 'OrderDetail');
                        }
                    }

                    //                         import Order History
                    foreach ($orderHistorys as $orderHistory) {
                        if ($orderHistory['id_order'] == $order['id_order']) {
                            if ($orderHistoryModel = $this->createObjectModel('OrderHistory', $orderHistory['id_order'])) {
                                $orderHistoryModel->id_order = $orderModel->id;
                                $orderHistoryModel->id_order_state = WooMigrationProMapping::listMapping(true, false, true)['order_states'][$order['post_status']];
                                $orderHistoryModel->id_employee = $orderHistory['id_employee'];
                                $orderHistoryModel->date_add = $orderHistory['date_add'];


                                $res = false;
                                $err_tmp = '';
                                $this->validator->setObject($orderHistoryModel);
                                $this->validator->checkFields();
                                $order_history_error_tmp = $this->validator->getValidationMessages();
                                if ($orderHistoryModel->id && OrderHistory::existsInDatabase($orderHistoryModel->id, 'order_history')) {
                                    try {
                                        $res = $orderHistoryModel->update();
                                    } catch (PrestaShopException $e) {
                                        $err_tmp = $e->getMessage();
                                    }
                                }
                                if (!$res) {
                                    try {
                                        $res = $orderHistoryModel->add(false);
                                    } catch (PrestaShopException $e) {
                                        $err_tmp = $e->getMessage();
                                    }
                                }
                                if (!$res) {
                                    $this->showMigrationMessageAndLog(sprintf(WooImport::displayError($this->module->l('Order History (ID: %1$s) cannot be saved. %2$s')), (isset($orderHistory['id_order_history']) && !WooImport::isEmpty($orderHistory['id_order_history'])) ? Tools::safeOutput($orderHistory['id_order_history']) : 'No ID', $err_tmp), 'OrderHistory');
                                } else {
                                    self::addLog('OrderHistory', $orderHistory['id_order'], $orderHistoryModel->id);
                                }
                                $this->showMigrationMessageAndLog($order_history_error_tmp, 'OrderHistory');
                            }
                        }
                    }
                    if (count($this->error_msg) == 0) {
                        Configuration::updateValue('woomigrationpro_order', $order['id_order']);
                        self::addLog('Order', $order['id_order'], $orderModel->id);
                    }
                }
                $this->showMigrationMessageAndLog($order_error_tmp, 'Order');
            }
        }

        //        update order invoice and shipping addresses

        foreach ($orders['billing_address'] as $address) {
            $id_customer = self::getLocalID('Customer', $address['_customer_user'], 'data');
            if (!WooImport::isEmpty($id_customer)) {
                $customer = new Customer($id_customer);
                $CustomerExistAdress = $customer->getAddresses(Configuration::get('PS_LANG_DEFAULT'));
                $addres1Array = $this->getCustomersAdress1Array($CustomerExistAdress);
                if ($billAddressObject = $this->createObjectModel('Address', $address['id'])) {
                    if (!in_array($address['_billing_address_1'], $addres1Array)) {
                        $id_customer = self::getLocalID('Customer', $address['_customer_user'], 'data');
                        $billAddressObject->id_customer = $id_customer;
                        //find country and state id from target
                        $iso_code = $this->checkIsoCode($address['_billing_country']);
                        if (!WooImport::isEmpty($iso_code)) {
                            if (Validate::isLanguageIsoCode($iso_code)) {
                                if (!WooImport::isEmpty(CountryCore::getByIso($iso_code))) {
                                    $billAddressObject->id_country = CountryCore::getByIso($iso_code);
                                    if (!WooImport::isEmpty($address['_billing_state'])) {
                                        if (!WooImport::isEmpty(StateCore::getIdByIso($address['billing_state']))) {
                                            $billAddressObject->id_state = StateCore::getIdByIso($address['_billing_state']);
                                        } else {
                                            $billAddressObject->id_state = 0;
                                        }
                                    } else {
                                        $billAddressObject->id_state = 0;
                                    }
                                } else {
                                    $this->showMigrationMessageAndLog(sprintf(WooImport::displayError('Address of Customer (ID: %1$s) ' . $iso_code . ' Country is not avilable on your database.'), (isset($address['_id_customer']) && !WooImport::isEmpty($address['_id_customer'])) ? Tools::safeOutput($address['_id_customer']) : 'No ID'), 'Address');
                                }
                            } else {
                                $this->showMigrationMessageAndLog(sprintf(WooImport::displayError('Address of Customer (ID: %1$s)' . $iso_code . ' Country Iso Code is not Valid.'), (isset($address['_id_customer']) && !WooImport::isEmpty($address['_id_customer'])) ? Tools::safeOutput($address['_id_customer']) : 'No ID'), 'Address');
                            }
                        } else {
                            $this->showMigrationMessageAndLog(sprintf(WooImport::displayError('Address of Customer (ID: %1$s) ' . $iso_code . ' Country Iso Code is Null.Address can not be saved.'), (isset($customer['_id_customer']) && !WooImport::isEmpty($customer['_id_customer'])) ? Tools::safeOutput($customer['_id_customer']) : 'No ID'), 'Address');
                        }
                        $billAddressObject->alias = 'Adress Alias';
                        $billAddressObject->company = $address['_billing_company'];
                        $billAddressObject->lastname = self::checkEmptyProperty($address['_billing_last_name'], 'emptyLastname');
                        $billAddressObject->firstname = self::checkEmptyProperty(self::cleanString($address['_billing_first_name']), 'emptyFirstname');
                        $billAddressObject->address1 = self::checkEmptyProperty($address['_billing_address_1'], 'address 1');
                        $billAddressObject->address2 = self::checkEmptyProperty($address['_billing_address_2'], 'address 2');
                        $billAddressObject->postcode = $address['_billing_postcode'];
                        $billAddressObject->other = "bill";
                        $billAddressObject->city = self::checkEmptyProperty($address['_billing_city'], 'emptyCIty');
                        $billAddressObject->phone = self::checkEmptyProperty($address['_billing_phone'], '000 000 000');
                        $billAddressObject->date_add = date('Y-m-d H:i:s', time());
                        $billAddressObject->date_upd = date('Y-m-d H:i:s', time());
                        $res = false;
                        $err_tmp = '';
                        $this->validator->setObject($billAddressObject);
                        $this->validator->checkFields();
                        $billAddress_history_error_tmp = $this->validator->getValidationMessages();
                        if ($billAddressObject->id && Address::existsInDatabase($billAddressObject->id, 'address')) {
                            try {
                                $res = $billAddressObject->update();
                            } catch (PrestaShopException $e) {
                                $err_tmp = $e->getMessage();
                            }
                        }
                        if (!$res) {
                            try {
                                $res = $billAddressObject->add(false);
                            } catch (PrestaShopException $e) {
                                $err_tmp = $e->getMessage();
                            }
                        }
                        if (!$res) {
                            $this->showMigrationMessageAndLog(sprintf(WooImport::displayError($this->module->l('Address of Customer (ID: %1$s) cannot be saved. %2$s')), (isset($address['_id_customer']) && !WooImport::isEmpty($address['_id_customer'])) ? Tools::safeOutput($address['_id_customer']) : 'No ID', $err_tmp), 'Address');
                        } else {
                            self::addLog('Address', $address['id'], $billAddressObject->id);
                            $id_order = self::getLocalID('Order', $address['id_order'], 'data');
                            $order = new Order($id_order);
                            $order->id_address_invoice = $billAddressObject->id;
                            if (!WooImport::isEmpty($order->id_address_invoice)) {
                                $order->save();
                            }
                        }
                        $this->showMigrationMessageAndLog($billAddress_history_error_tmp, 'Address');
                    }
                }
            }
        }

        foreach ($orders['shipping_address'] as $address) {
            $id_customer = self::getLocalID('Customer', $address['_customer_user'], 'data');
            if (!WooImport::isEmpty($id_customer)) {
                $customer = new Customer($id_customer);
                $CustomerExistAdress = $customer->getAddresses(Configuration::get('PS_LANG_DEFAULT'));
                $addres1Array = $this->getCustomersAdress1Array($CustomerExistAdress);
                if ($shipAddressObject = $this->createObjectModel('Address', $address['id'])) {
                    if (!in_array($address['_shipping_address_1'], $addres1Array)) {
                        $id_customer = self::getLocalID('Customer', $address['_customer_user'], 'data');
                        $shipAddressObject->id_customer = $id_customer;
                        //find country and state id from target
                        $iso_code = $this->checkIsoCode($address['_shipping_country']);
                        if (!WooImport::isEmpty($iso_code)) {
                            if (Validate::isLanguageIsoCode($iso_code)) {
                                if (!WooImport::isEmpty(CountryCore::getByIso($iso_code))) {
                                    $shipAddressObject->id_country = CountryCore::getByIso($iso_code);
                                    if (!WooImport::isEmpty($address['_shipping_state'])) {
                                        if (!WooImport::isEmpty(StateCore::getIdByIso($address['_shipping_state']))) {
                                            $shipAddressObject->id_state = StateCore::getIdByIso($address['_shipping_state']);
                                        } else {
                                            $shipAddressObject->id_state = 0;
                                        }
                                    } else {
                                        $shipAddressObject->id_state = 0;
                                    }
                                } else {
                                    $this->showMigrationMessageAndLog(sprintf(WooImport::displayError('Address of Customer (ID: %1$s) ' . $iso_code . ' Country is not avilable on your database.'), (isset($address['_customer_user']) && !WooImport::isEmpty($address['_customer_user'])) ? Tools::safeOutput($address['_customer_user']) : 'No ID'), 'Address');
                                }
                            } else {
                                $this->showMigrationMessageAndLog(sprintf(WooImport::displayError('Address of Customer (ID: %1$s) ' . $iso_code . '  Country Iso Code is not Valid.'), (isset($address['_customer_user']) && !WooImport::isEmpty($address['_customer_user'])) ? Tools::safeOutput($address['_customer_user']) : 'No ID'), 'Address');
                            }
                        } else {
                            $this->showMigrationMessageAndLog(sprintf(WooImport::displayError('Address of Customer (ID: %1$s) ' . $iso_code . ' Country Iso Code is Null.Address can not be saved.'), (isset($customer['_customer_user']) && !WooImport::isEmpty($customer['_customer_user'])) ? Tools::safeOutput($customer['_customer_user']) : 'No ID'), 'Address');
                        }
                        $shipAddressObject->alias = 'Adress Alias';
                        $shipAddressObject->company = $address['_shipping_company'];
                        $shipAddressObject->lastname = self::checkEmptyProperty($address['_shipping_last_name'], 'emptyLastname');
                        $shipAddressObject->firstname = self::checkEmptyProperty(self::cleanString($address['_shipping_first_name']), 'emptyFirstname');
                        $shipAddressObject->address1 = self::checkEmptyProperty($address['_shipping_address_1'], 'address 1');
                        $shipAddressObject->address2 = self::checkEmptyProperty($address['_shipping_address_2'], 'address 2');
                        $shipAddressObject->postcode = $address['_shipping_postcode'];
                        $shipAddressObject->other = "shiping_address";
                        $shipAddressObject->city = self::checkEmptyProperty($address['_shipping_city'], 'emptyCity');
                        $shipAddressObject->date_add = date('Y-m-d H:i:s', time());
                        $shipAddressObject->date_upd = date('Y-m-d H:i:s', time());
                        $res = false;
                        $err_tmp = '';
                        $this->validator->setObject($shipAddressObject);
                        $this->validator->checkFields();
                        $shipAddress_history_error_tmp = $this->validator->getValidationMessages();
                        if ($shipAddressObject->id && Address::existsInDatabase($shipAddressObject->id, 'address')) {
                            try {
                                $res = $shipAddressObject->update();
                            } catch (PrestaShopException $e) {
                                $err_tmp = $e->getMessage();
                            }
                        }
                        if (!$res) {
                            try {
                                $res = $shipAddressObject->add(false);
                            } catch (PrestaShopException $e) {
                                $err_tmp = $e->getMessage();
                            }
                        }
                        if (!$res) {
                            $this->showMigrationMessageAndLog(sprintf(WooImport::displayError($this->module->l('Address of Customer (ID: %1$s) cannot be saved. %2$s')), (isset($address['_customer_user']) && !WooImport::isEmpty($address['_customer_user'])) ? Tools::safeOutput($address['_customer_user']) : 'No ID', $err_tmp), 'Address');
                        } else {
                            self::addLog('Address', $address['id'], $shipAddressObject->id);
                            $id_order = self::getLocalID('Order', $address['id_order'], 'data');
                            $order = new Order($id_order);
                            $order->id_address_delivery = $shipAddressObject->id;
                            if (WooImport::isEmpty($order->id_address_delivery)) {
                                $order->save();
                            }
                        }
                        $this->showMigrationMessageAndLog($shipAddress_history_error_tmp, 'Address');
                    }
                }
            }
        }

        $this->updateProcess(count($orders['order']));
    }

    // --- Internal helper methods:

    public function getCustomersAdress1Array($customerAddresses)
    {
        $result = array();

        foreach ($customerAddresses as $address) {
            $result[] = $address['address1'];
        }

        return $result;
    }

    public function createObjectModel($className, $objectID, $table_name = '')
    {
        if (!WooMigrationProData::exist($className, $objectID)) {
            // -- if keep old IDs and if exists in DataBase
            // -- else  isset($objectID) && (int)$objectID

            if (!WooImport::isEmpty($table_name)) {
                $existInDataBase = self::existsInDatabase((int)$objectID, Tools::strtolower($table_name), Tools::strtolower($className));
            } else {
                $existInDataBase = $className::existsInDatabase((int)$objectID, $className::$definition['table']);
                // [For PrestaShop Team] - This code call class definition attribute extended from ObjectModel class
                // like Order::$definition
            }

            if ($existInDataBase) {
                $this->obj = new $className((int)$objectID);
            } else {
                $this->obj = new $className();
            }

            if ($this->force_ids) {
                $this->obj->force_id = true;
                $this->obj->id = $objectID;
            }
            return $this->obj;
        }
    }

    public function updateProcess($count)
    {
        if (!count($this->error_msg) && $count > 0) {
            $this->process->imported += $count;//@TODO count of item
//            $this->process->id_source = $source_id;
            if ($this->process->total <= $this->process->imported) {
                $this->process->finish = 1;
                $this->response['execute_time'] = number_format((time() - strtotime($this->process->time_start)), 3, '.', '');
            }
            $this->response['type'] = $this->process->type;
            $this->response['total'] = (int)$this->process->total;
            $this->response['imported'] = (int)$this->process->imported;
            $this->response['process'] = ($this->process->finish == 1) ? 'finish' : 'continue';
            $this->process->save();

            if (!WooMigrationProProcess::getActiveProcessObject()) {
                $allWarningMessages = $this->logger->getAllWarnings();
                $this->warning_msg = $allWarningMessages;
            }
        }
    }

    public static function existsInDatabase($id_entity, $table, $entity_name)
    {
        $row = Db::getInstance()->getRow('
			SELECT `id_' . bqSQL($entity_name) . '` as id
			FROM `' . _DB_PREFIX_ . bqSQL($table) . '` e
			WHERE e.`id_' . bqSQL($entity_name) . '` = ' . (int)$id_entity, false);

        return isset($row['id']);
    }

    /**
     * Copy images from temporary directory to original PrestaShop directory for all types
     * @param mixed $id_entity Image name
     * @param mixed $id_image Image name only for Products
     * @param mixed $FilePath Temmporary file path
     * @param mixed $entity Type of image
     * @param mixed $regenerate
     * @return boolean
     */
    public static function copyImg($id_entity, $id_image, $FilePath, $entity = 'products', $regenerate = false)
    {
        $tmpfile = $FilePath;

        if (self::isEmpty($id_image)) {
            $id_image = null;
        }
        switch ($entity) {
            default:
            case 'carriers':
                $path = _PS_SHIP_IMG_DIR_ . (int)$id_entity;
                break;
            case 'products':
                $image_obj = new Image($id_image);
                $path = $image_obj->getPathForCreation();
                break;
            case 'categories':
                $path = _PS_CAT_IMG_DIR_ . (int)$id_entity;
                break;
            case 'manufacturers':
                $path = _PS_MANU_IMG_DIR_ . (int)$id_entity;
                break;
            case 'suppliers':
                $path = _PS_SUPP_IMG_DIR_ . (int)$id_entity;
                break;
            case 'employees':
                $path = _PS_EMPLOYEE_IMG_DIR_ . (int)$id_entity;
                break;
            case 'attributes':
                $path = _PS_COL_IMG_DIR_ . (int)$id_entity;
                break;
        }
        // Evaluate the memory required to resize the image: if it's too much, you can't resize it.
        if (!ImageManager::checkImageMemoryLimit($tmpfile)) {
            @unlink($tmpfile);
            return false;
        }
        copy($tmpfile, $path . '.jpg');

        return true;
    }

    public static function getBestPath($tgt_width, $tgt_height, $path_infos)
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

    public static function imageExits($url)
    {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_NOBODY, true);
        curl_exec($ch);
        $response_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($response_code === 200) {
            return true;
        } else {
            return false;
        }
    }

    public function getLocalID($map_type, $sourceID, $table_type = 'map')
    {
        if ($table_type === "map") {
            $result = (isset($this->mapping[$map_type][$sourceID]) && !WooImport::isEmpty($this->mapping[$map_type][$sourceID])) ? $this->mapping[$map_type][$sourceID] : 0;
        } else {
            $result = WooMigrationProData::getLocalID($map_type, $sourceID);
            if (WooImport::isEmpty($result)) {
                $result = WooMigrationProMigratedData::getLocalID($map_type, $sourceID);
            }
        }

        return (int)$result;
    }


    public function getLanguageID($source_lang_id)
    {
        return $this->getLocalID('languages', $source_lang_id);
    }


    public function getCustomerGroupID($source_lang_id)
    {
        return $this->getLocalID('customer_groups', $source_lang_id);
    }

// after end workin delete
    public function getDefaultCategory($product_id, $product_cats)
    {

        foreach ($product_cats as $product_cat) {
            if ($product_cat['ID'] == $product_id) {
                return $product_cat['id_category'];
            }
        }
    }

    public static function cleanString($input)
    {
        return preg_replace('/[^ A-Za-z]/', '', $input);
    }


    public function createCustomer($id_order, $address)
    {
        $costumerDetails = $address['billing_address'][$id_order];
        $shipAddress = $address['shipping_address'][$id_order];
        if ($customerObject = $this->createObjectModel('Customer', $costumerDetails['id_order'])) {
            $customerObject->secure_key = $this->secure_key = md5(uniqid(rand(), true));
            $customerObject->lastname = self::checkEmptyProperty($costumerDetails['_billing_last_name'], 'emptyLastname');
            $customerObject->firstname = self::checkEmptyProperty($costumerDetails['_billing_first_name'], 'emptyFirstname');
            $customerObject->email = self::checkEmptyProperty($costumerDetails['_billing_email'], 'empty@empty.com');
            $customerObject->passwd = Tools::encrypt('VWq312!@^h8r2gVXu#),M');
            $customerObject->id_gender = 1;
            $customerObject->newsletter = 0;
            $customerObject->is_guest = 1;
            $customerObject->newsletter_date_add = date('Y-m-d H:i:s', time());
            $customerObject->active = 1;
            $customerObject->deleted = 0;
            $customerObject->date_add = date('Y-m-d H:i:s', time());
            $customerObject->date_upd = date('Y-m-d H:i:s', time());
            $res = false;
            $err_tmp = '';

            $this->validator->setObject($customerObject);
            $this->validator->checkFields();
            $custome_error_tmp = $this->validator->getValidationMessages();
            try {
                $res = $customerObject->add(false);
            } catch (PrestaShopException $e) {
                $err_tmp = $e->getMessage();
            }
            if (!$res) {
                $this->showMigrationMessageAndLog(sprintf(WooImport::displayError('Cant create new Customer for order (ID: %1$s)'), (isset($costumerDetails['id_order']) && !WooImport::isEmpty($costumerDetails['id_order'])) ? Tools::safeOutput($costumerDetails['id_order']) : 'No ID', $err_tmp), 'Customer');
            } else {
                self::addLog('Customer', $costumerDetails['id_order'], $customerObject->id);
            }
            $this->showMigrationMessageAndLog($custome_error_tmp, 'Customer');
        }


        if ($customerObject->id) {
            $id_customer = $customerObject->id;
        } else {
            $id_customer = self::getLocalID('Customer', $costumerDetails['id_order'], 'data');
        }
        self::createAddress($id_customer, $shipAddress, $costumerDetails);

        return $id_customer;
    }

    public function createAddress($id_customer, $shipAddress, $costumerDetails)
    {
        $addressObject1 = new Address();
        $addressObject1->id_customer = $id_customer;
        $iso_code = $this->checkIsoCode($shipAddress['_shipping_country']);
        if (!WooImport::isEmpty($iso_code)) {
            if (Validate::isLanguageIsoCode($iso_code)) {
                if (!WooImport::isEmpty(CountryCore::getByIso($iso_code))) {
                    $addressObject1->id_country = CountryCore::getByIso($iso_code);
                    if (!WooImport::isEmpty($shipAddress['_shipping_state'])) {
                        if (!WooImport::isEmpty(StateCore::getIdByIso($shipAddress['_shipping_state']))) {
                            $addressObject1->id_state = StateCore::getIdByIso($shipAddress['_shipping_state']);
                        } else {
                            $addressObject1->id_state = 0;
                        }
                    } else {
                        $addressObject1->id_state = 0;
                    }
                } else {
                    $this->showMigrationMessageAndLog(sprintf(WooImport::displayError('Shipping Address of Order (ID: %1$s) ' . $iso_code . ' Country is not avilable on your database.'), (isset($costumerDetails['id_order']) && !WooImport::isEmpty($costumerDetails['id_order'])) ? Tools::safeOutput($costumerDetails['id_order']) : 'No ID'), 'Address');
                }
            } else {
                $this->showMigrationMessageAndLog(sprintf(WooImport::displayError('Shipping Address of Order (ID: %1$s) ' . $iso_code . ' Country Iso Code is not Valid.'), (isset($costumerDetails['id_order']) && !WooImport::isEmpty($costumerDetails['id_order'])) ? Tools::safeOutput($costumerDetails['id_order']) : 'No ID'), 'Address');
            }
        } else {
            $this->showMigrationMessageAndLog(sprintf(WooImport::displayError('Shipping Address of Order (ID: %1$s) ' . $iso_code . ' Country Iso Code is Null.Address can not be saved.'), (isset($costumerDetails['id_order']) && !WooImport::isEmpty($costumerDetails['id_order'])) ? Tools::safeOutput($costumerDetails['id_order']) : 'No ID'), 'Address');
        }
        $addressObject1->alias = 'Adress Alias';
        $addressObject1->company = $shipAddress['_shipping_company'];
        $addressObject1->lastname = self::checkEmptyProperty(self::cleanString($shipAddress['_shipping_last_name']), 'emptyLastname');
        $addressObject1->firstname = self::checkEmptyProperty(self::cleanString($shipAddress['_shipping_first_name']), 'emptyFirstname');
        $addressObject1->address1 = self::checkEmptyProperty(str_replace(array('º', '/', '_'), '', $shipAddress['_shipping_address_1']), 'address 1');
        $addressObject1->address2 = self::checkEmptyProperty(str_replace('__', '', $shipAddress['_shipping_address_2']), 'address 2');
        $addressObject1->postcode = $shipAddress['_shipping_postcode'];
        $addressObject1->city = self::checkEmptyProperty($shipAddress['_shipping_city'], 'emptyCity');
        $addressObject1->other = "ship";
        $addressObject1->date_add = date('Y-m-d H:i:s', time());
        $addressObject1->date_upd = date('Y-m-d H:i:s', time());

        $res = false;
        $err_tmp = '';
        $this->validator->setObject($addressObject1);
        $this->validator->checkFields();
        $addressObject1_error_tmp = $this->validator->getValidationMessages();
        if (!$res) {
            try {
                $res = $addressObject1->add(false);
            } catch (PrestaShopException $e) {
                $err_tmp = $e->getMessage();
            }
        }
        if (!$res) {
            $this->showMigrationMessageAndLog(sprintf(WooImport::displayError($this->module->l('Shipping Address of Order (ID: %1$s) cannot be saved. %2$s'), (isset($costumerDetails['id_order']) && !WooImport::isEmpty($costumerDetails['id_order'])) ? Tools::safeOutput($costumerDetails['id_order']) : 'No ID', $err_tmp)), 'Address');
        } else {
            $addressObject2 = new Address();
            $addressObject2->id_customer = $id_customer;
            $iso_code = $this->checkIsoCode($costumerDetails['_billing_country']);
            if (!WooImport::isEmpty($iso_code)) {
                if (Validate::isLanguageIsoCode($iso_code)) {
                    if (!WooImport::isEmpty(CountryCore::getByIso($iso_code))) {
                        $addressObject2->id_country = CountryCore::getByIso($iso_code);
                        if (!WooImport::isEmpty($costumerDetails['_billing_state'])) {
                            if (!WooImport::isEmpty(StateCore::getIdByIso($costumerDetails['_billing_state']))) {
                                $addressObject2->id_state = StateCore::getIdByIso($costumerDetails['_billing_state']);
                            } else {
                                $addressObject2->id_state = 0;
                            }
                        } else {
                            $addressObject2->id_state = 0;
                        }
                    } else {
                        $this->showMigrationMessageAndLog(sprintf(WooImport::displayError('Bill Address of Order (ID: %1$s) ' . $iso_code . ' Country is not avilable on your database.'), (isset($costumerDetails['id_order']) && !WooImport::isEmpty($costumerDetails['id_order'])) ? Tools::safeOutput($costumerDetails['id_order']) : 'No ID'), 'Address');
                    }
                } else {
                    $this->showMigrationMessageAndLog(sprintf(WooImport::displayError('Bill Address of Order (ID: %1$s) ' . $iso_code . 'Country Iso Code is not Valid.'), (isset($costumerDetails['id_order']) && !WooImport::isEmpty($costumerDetails['id_order'])) ? Tools::safeOutput($costumerDetails['id_order']) : 'No ID'), 'Address');
                }
            } else {
                $this->showMigrationMessageAndLog(sprintf(WooImport::displayError('Bill Address of Order (ID: %1$s) ' . $iso_code . ' Country Iso Code is Null.Address can not be saved.'), (isset($costumerDetails['id_order']) && !WooImport::isEmpty($costumerDetails['id_order'])) ? Tools::safeOutput($costumerDetails['id_order']) : 'No ID'), 'Address');
            }
            $addressObject2->alias = 'Adress Alias';
            $addressObject2->company = $costumerDetails['_billing_company'];
            $addressObject2->lastname = self::checkEmptyProperty(self::cleanString($costumerDetails['_billing_last_name']), "Lastname");
            $addressObject2->firstname = self::checkEmptyProperty(self::cleanString($costumerDetails['_billing_first_name']), 'Firstname');
            $addressObject2->address1 = self::checkEmptyProperty(str_replace(array('º', '/', '_'), '', $costumerDetails['_billing_address_1']), 'address 1');
            $addressObject2->address2 = self::checkEmptyProperty(str_replace('__', '', $costumerDetails['_billing_address_2']), 'address 2');
            $addressObject2->postcode = $costumerDetails['_billing_postcode'];
            $addressObject2->other = "Bill";
            $addressObject2->city = self::checkEmptyProperty($costumerDetails['_billing_city'], 'City');
            $addressObject2->phone = self::checkEmptyProperty($costumerDetails['_billing_phone'], '000 000 000');
            $addressObject2->date_add = date('Y-m-d H:i:s', time());
            $addressObject2->date_upd = date('Y-m-d H:i:s', time());

            $res = false;
            $err_tmp = '';
            $this->validator->setObject($addressObject2);
            $this->validator->checkFields();
            $addressObject2_error_tmp = $this->validator->getValidationMessages();

            if (!$res) {
                try {
                    $res = $addressObject2->add(false);
                } catch (PrestaShopException $e) {
                    $err_tmp = $e->getMessage();
                }
            }

            if (!$res) {
                $this->showMigrationMessageAndLog(sprintf(WooImport::displayError($this->module->l('Bill Address of Order (ID: %1$s) cannot be saved. %2$s')), (isset($costumerDetails['id_order']) && !WooImport::isEmpty($costumerDetails['id_order'])) ? Tools::safeOutput($costumerDetails['id_order']) : 'No ID', $err_tmp), 'Address');
            }
            $this->showMigrationMessageAndLog($addressObject2_error_tmp, 'Address');
        }
        $this->showMigrationMessageAndLog($addressObject1_error_tmp, 'Address');

        return array($addressObject1->id, $addressObject2->id);
    }

    public static function isEmpty($field)
    {
        if (version_compare(PHP_VERSION, '5.5.0', '<')) {
            return ($field === '' || $field === null || $field === array() || $field === 0 || $field === '0');
        } else {
            return empty($field);
        }
    }

    public static function displayError($string = 'Fatal error')
    {
        return $string;
    }

    public static function wround($field)
    {
        return Tools::ps_round($field, 6);
    }

    public function getManufactureImage($man_id, $man_images)
    {
        foreach ($man_images as $man_image) {
            if ($man_image['ID'] == $man_id) {
                return $man_image;
            }
        }
    }

    public function getProductManufactureId($id_product, $manufactures)
    {
        foreach ($manufactures as $manufacture) {
            if ($manufacture['id_product'] == $id_product) {
                return $manufacture['id_manufacturer'];
            }
        }
    }

    public static function checkEmptyProperty($property, $value)
    {
        if (!WooImport::isEmpty($property)) {
            return $property;
        } else {
            return "Empty" . $value;
        }
    }

    public static function addLog($entity_type, $source_id, $local_id)
    {
        WooMigrationProData::import((string)$entity_type, (int)$source_id, (int)$local_id);
        WooMigrationProMigratedData::import((string)$entity_type, (int)$source_id, (int)$local_id);
    }

    public function checkIsoCode($code)
    {
        if ($code == 'UK') {
            return 'GB';
        }
        if (!WooImport::isEmpty($code)) {
            if (!CountryCore::getByIso($code)) {
                $iso_code = $this->addCountry($code);
            } else {
                $iso_code = $code;
            }
        } else {
            $iso_code = Configuration::get('PS_LOCALE_COUNTRY');
        }
        return $iso_code;
    }

    public function addCountry($iso_code)
    {
        $this->client->setPostData($this->query->getCountries());
        $this->client->serializeOn();
        if ($this->client->query()) {
            $county_and_continent = $this->client->getContent();
            foreach ($county_and_continent['continents'] as $key => $continents) {
                foreach ($continents as $continent) {
                    if (array_search("" . $iso_code . "", $continent)) {
                        $new_country = array('iso_code' => $iso_code, 'country' => $county_and_continent['countries'][$iso_code], 'continent' => $continents['name'], 'continen_code' => $key);
                    }
                }
            }
            return $this->importCountry($new_country);
        }
    }

    public function importCountry($country)
    {
        // import country
        if ($countryObject = new Country()) {
            $countryObject->id_zone = $this->getZoneId($country['continen_code']);
            $countryObject->id_currency = 0;
            $countryObject->call_prefix = 0;
            $countryObject->iso_code = $country['iso_code'];
            $countryObject->active = 1;
            $countryObject->contains_states = 0;
            $countryObject->need_identification_number = 0;
            $countryObject->need_zip_code = 0;
            $countryObject->display_tax_label = 1;
            $l = Configuration::get('PS_LANG_DEFAULT');
            $countryObject->name[$l] = $country['country'];
        }
        $res = false;
        $err_tmp = '';
        $this->validator->setObject($countryObject);
        $this->validator->checkFields();
        $countryerror_tmp = $this->validator->getValidationMessages();
        if ($countryObject->id && Country::existsInDatabase($countryObject->id, 'Country')) {
            try {
                $res = $countryObject->update();
            } catch (PrestaShopException $e) {
                $err_tmp = $e->getMessage();
            }
        }
        if (!$res) {
            try {
                $res = $countryObject->add(false);
            } catch (PrestaShopException $e) {
                $err_tmp = $e->getMessage();
            }
        }
        if (!$res) {
            $this->showMigrationMessageAndLog(sprintf($this->module->l('Country (ID: %1$s) cannot be saved. %2$s'), (isset($countryObject->id_country) && !WooImport::isEmpty($countryObject->id_country)) ? Tools::safeOutput($countryObject->iso_code) : 'No ID', $err_tmp), 'Country');
        }
        $this->showMigrationMessageAndLog($countryerror_tmp, 'Country');
    }

    public function getZoneId($country)
    {
        switch ($country) {
            case 'AF':
                $zone_id = 4;
                break;
            case 'AS':
                $zone_id = 3;
                break;
            case 'EU':
                $zone_id = 1;
                break;
            case 'NA':
                $zone_id = 2;
                break;
            case 'OC':
                $zone_id = 5;
                break;
            case 'SA':
                $zone_id = 6;
                break;
            case 'AN':
                $zone_id = 8;
                break;
        }

        $sql = "SELECT zone.id_zone from " . _DB_PREFIX_ . "zone as zone where zone.id_zone=" . $zone_id;
        return Db::getInstance()->getValue($sql);
    }

    //product attribute

//    private function createAttributeFromProductVariation($productAttributeCombination)
//    {
//        if ($attributeInfo = $this->getAttributeInfoFromVariation($productAttributeCombination)) {
//            $attribute_ids = array();
//            foreach ($attributeInfo as $value) {
//                $attribute_group_name = str_replace('pa_', '', str_replace('-', ' ', $value['attribute_group_name']));
//                $id_attr_group = $this->getAttributeGroupIdByName($attribute_group_name);
//                if (!$id_attr_group) {
//                    $id_attr_group = $this->importProductAttributeGroupForCombination($value['attribute_group_name']);
//                }
//                $attribute_name = str_replace('-', ' ', $value['attribute']);
//                if (!$this->getAttributeIdByName($attribute_name, $id_attr_group)) {
//                    if (!empty($value['attribute']) && !empty($value['attribute_group_name'])) {
//                        $attributeObj = new Attribute;
//                        $attributeObj->id_attribute_group = $id_attr_group;
//                        $attributeObj->color = '#5D9CEC';
//                        $l = Configuration::get('PS_LANG_DEFAULT');
//                        $attributeObj->name[$l] = $attribute_name;
//                        if ($attributeObj->add(false)) {
//                            array_push($attribute_ids, $attributeObj->id);
//                        } else {
//                            $this->showMigrationMessageAndLog(sprintf(WooImport::displayError('Attribute (ID: %1$s) cannot be saved. %2$s'), (isset($attributeObj->id) && !empty($attributeObj->id)) ? Tools::safeOutput($attributeObj->id) : 'No ID'), 'Attribute');
//                        }
//                    } else {
//                        return false;
//                    }
//                } else {
//                    array_push($attribute_ids, $this->getAttributeIdByName($attribute_name, $id_attr_group));
//                }
//            }
//            return $attribute_ids;
//        }
//    }

    public function getAttributeInfoFromVariation($variation)
    {

        foreach ($variation as $key => $value) {
            $attr_and_group = array();
            if (preg_match('/attribute_/', $key)) {
                $attr_group_name = str_replace('attribute_', '', $key);
                $attr_and_group[] = array('attribute_group_name' => $attr_group_name, 'attribute' => $value);
            }
        }
        return $attr_and_group;
    }

    /**
     * Loading images from source server to local with MultiThread method curl_multi_init()
     * @param array $ImageIds Array with IDs
     * @param string $Key Key of array where ID
     * @param string $Entity Sub directory in temporary directory
     * @param string $Host Host address
     * @param string $EndDir End directory  in host
     */
    public function loadImagesToLocal($ImageIds, $Key, $Entity, $Host, $EndDir)
    {
        try {
            $urls = array();
            $url_log = array();
            //Generating  urls from image IDs
            foreach ($ImageIds as $ImageId) {
                if ($Entity === 'products') {
                    foreach ($ImageId as $img) {
                        $urls[] = $EndDir . $img['meta_value'];
                    }
                } elseif ($Entity === 'manufacturers') {
                    $urls[] = $ImageId['url'];
                    $url_log[$ImageId['url']] = $ImageId['id_manufacturer'];
                } elseif ($Entity === 'categories') {
                    if (!empty($ImageId['meta_value'][0])) {
                        $urls[] = $ImageId['meta_value'][0];
                        $url_log[$ImageId['meta_value'][0]] = $ImageId['id_category'];
                    } else {
                        continue;
                    }
                } else {
                    $urls[] = $Host . $EndDir . (int)$ImageId[$Key] . '.jpg';
                }
            }
            $path = _PS_TMP_IMG_DIR_ . '/mp_temp_dir/' . $Entity;
            //Checking exist  temporary path on server
            if (!file_exists($path) && !is_dir($path)) {
                if (!file_exists(_PS_TMP_IMG_DIR_ . 'mp_temp_dir') && !is_dir(_PS_TMP_IMG_DIR_ . 'mp_temp_dir')) {
                    mkdir(_PS_TMP_IMG_DIR_ . 'mp_temp_dir', 0777);
                }
                mkdir($path, 0777);
            }


            //Removing all temporary files
            array_map('unlink', glob("$path/*.*"));

            $curlArr = array();
            $i = 0;
            $master = curl_multi_init();

            //Options for  CURLs array
            $options = array(
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_HEADER => false,
                CURLOPT_CUSTOMREQUEST => 'GET',
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_ENCODING => "",
                CURLOPT_CONNECTTIMEOUT => 10000,
                CURLOPT_TIMEOUT => 10000,
                CURLOPT_SSL_VERIFYHOST => 0,
                CURLOPT_SSL_VERIFYPEER => false,
                CURL_HTTP_VERSION_1_1 => 1
            );
            //set options for each urls
            foreach ($urls as $url) {
                $curlArr[$i] = curl_init();
                curl_setopt($curlArr[$i], CURLOPT_URL, $url);
                curl_setopt_array($curlArr[$i], $options);
                curl_multi_add_handle($master, $curlArr[$i]);
                $i++;
            }
            //Beginning load all images
            $running = null;
            $i = 0;
            do {
                curl_multi_exec($master, $running);
                usleep(5);
            } while ($running > 0);

            //Copying  images to temporary dir, Closing  Curls & Removing curls from curl_multi
            $NotFoundImages = array();
            foreach ($urls as $url) {
                $httpCode = curl_getinfo($curlArr[$i], CURLINFO_HTTP_CODE);
                //If image  found on source server start copy
                if ($httpCode === 200) {
                    if ($Entity === 'manufacturers') {
                        $fileExt = pathinfo(parse_url($url, PHP_URL_PATH), PATHINFO_EXTENSION);
                        $filePath = $path . '/' . $url_log[$url] . '.' . $fileExt;
                    } elseif ($Entity === 'categories') {
                        $fileExt = pathinfo(parse_url($url, PHP_URL_PATH), PATHINFO_EXTENSION);
                        $filePath = $path . '/' . $url_log[$url] . '.' . $fileExt;
                    } else {
                        $filename = pathinfo(parse_url($url, PHP_URL_PATH), PATHINFO_FILENAME);
                        $fileExt = pathinfo(parse_url($url, PHP_URL_PATH), PATHINFO_EXTENSION);
                        $filePath = $path . '/' . $filename . '.' . $fileExt;
                    }
                    if (file_exists($filePath)) {
                        unlink($filePath);
                    }
                    $file = fopen($filePath, 'x');
                    $contents = curl_multi_getcontent($curlArr[$i]);
                    fwrite($file, $contents);
                    fclose($file);
                } else if ($httpCode === 404) {
                    $NotFoundImages[$url] = false;
                } else {
                    $this->showMigrationMessageAndLog($url . ' ' . self::displayError($this->module->l('File Not Found in source server.')), 'Image', true);
                }
                curl_multi_remove_handle($master, $curlArr[$i]);
                curl_close($curlArr[$i]);
                $i++;
            }
            //Close curl_multi
            curl_multi_close($master);
        } catch (Exception $ex) {
            $this->showMigrationMessageAndLog('loadImagesToLocal  ' . self::displayError($this->module->l($ex->getMessage())), 'Image', true);
        }
    }

    public function getAttributeGroupIdByName($name)
    {
        $sql = 'SELECT id_attribute_group from ' . _DB_PREFIX_ . 'attribute_group_lang where public_name = "' . $name . '" and id_lang =' . Configuration::get('PS_LANG_DEFAULT');
        return Db::getInstance()->getValue($sql);
    }

    public function importProductAttributeGroupForCombination($attribute_name)
    {
        $attributeGroupObj = new AttributeGroup();
        $attributeGroupObj->is_color_group = null;
        $attributeGroupObj->group_type = 'select';
        $l = Configuration::get('PS_LANG_DEFAULT');
        $attribute_name = str_replace('pa_', '', str_replace('-', ' ', $attribute_name));
        $attributeGroupObj->name[$l] = Tools::ucfirst($attribute_name);
        $attributeGroupObj->public_name[$l] = $attribute_name;
        $this->validator->setObject($attributeGroupObj);
        $this->validator->checkFields();
        $attributeGroup_error_tmp = $this->validator->getValidationMessages();
        try {
            $res = $attributeGroupObj->add(false);
        } catch (PrestaShopException $e) {
            $err_tmp = $e->getMessage();
        }

        if (!$res) {
            $this->showMigrationMessageAndLog(sprintf(WooImport::displayError('AttributeGroup (ID: %1$s) cannot be saved. %2$s'), (isset($attributeGroupObj->id) && !empty($attributeGroupObj->id)) ? Tools::safeOutput($attributeGroupObj->id) : 'No ID', $err_tmp), 'AttributeGroup');
        } else {
            return $attributeGroupObj->id;
        }
        $this->showMigrationMessageAndLog($attributeGroup_error_tmp, 'AttributeGroup');
    }

    public function getAttributeIdByName($name, $group_name)
    {
        $sql = 'SELECT agl.id_attribute_group from ' . _DB_PREFIX_ . 'attribute_group_lang as agl  where agl.name = "' . $group_name . '" and  agl.id_lang =' . Configuration::get('PS_LANG_DEFAULT');
        $id_attribute_group = Db::getInstance()->getValue($sql);
        if (empty($id_attribute_group)) {
            $custom_attribute_group = new AttributeGroup();
            $l = Configuration::get('PS_LANG_DEFAULT');
            $custom_attribute_group->name[$l] = $group_name;
            $custom_attribute_group->public_name[$l] = $group_name;
            $custom_attribute_group->is_color_group = null;
            $custom_attribute_group->group_type = 'select';
            $res = false;
            $err_tmp = '';
            $this->validator->setObject($custom_attribute_group);
            $this->validator->checkFields();
            $custom_attribute_group_error_tmp = $this->validator->getValidationMessages();
            try {
                $res = $custom_attribute_group->add(false);
            } catch (PrestaShopException $e) {
                $err_tmp = $e->getMessage();
            }
            if (!$res) {
                $this->showMigrationMessageAndLog(sprintf(WooImport::displayError($this->module->l('Custumize Attribute Group (ID: %1$s) cannot be saved. %2$s')), (isset($custom_attribute_group) && !WooImport::isEmpty($custom_attribute_group)) ? Tools::safeOutput($custom_attribute_group) : 'No Name', $err_tmp), 'CustomeAttributeGroup');
            } else {
                $id_attribute_group = $custom_attribute_group->id;
            }
//            $this->showMigrationMessageAndLog($custom_attribute_group_error_tmp, 'CustomAttributeGroup');
        }
        $sql = 'SELECT a.id_attribute from ' . _DB_PREFIX_ . 'attribute as a join ' . _DB_PREFIX_ . 'attribute_lang as al on a.id_attribute=al.id_attribute where al.name = "' . $name . '" and a.id_attribute_group=' . $id_attribute_group . '  and al.id_lang =' . Configuration::get('PS_LANG_DEFAULT');
        $id_attribute = Db::getInstance()->getValue($sql);
        if (empty($id_attribute)) {
            $custom_attribute = new Attribute();
            $custom_attribute->id_attribute_group = $id_attribute_group;
            $l = Configuration::get('PS_LANG_DEFAULT');
            $custom_attribute->name[$l] = $name;
            $res = false;
            $err_tmp = '';
            $this->validator->setObject($custom_attribute);
            $this->validator->checkFields();
            $custom_attribute_error_tmp = $this->validator->getValidationMessages();
            try {
                $res = $custom_attribute->add(false);
            } catch (PrestaShopException $e) {
                $err_tmp = $e->getMessage();
            }

            if (!$res) {
                $this->showMigrationMessageAndLog(sprintf(WooImport::displayError($this->module->l('Cutom Attribute (ID: %1$s) cannot be saved. %2$s')), (isset($custom_attribute) && !WooImport::isEmpty($custom_attribute)) ? Tools::safeOutput($custom_attribute) : 'No Name', $err_tmp), 'Custome Attribute');
            } else {
                $id_attribute = $custom_attribute->id;
            }
//            $this->showMigrationMessageAndLog($custom_attribute_error_tmp, 'Custom Attribute');
        }
        return $id_attribute;
    }

    public function showMigrationMessageAndLog($log, $entityType, $showOnlyWarning = true)
    {
        if ($this->ps_validation_errors) {
            if ($showOnlyWarning) {
                if (is_array($log)) {
                    foreach ($log as $logIndex => $logText) {
                        $this->logger->addWarningLog($logText, $entityType);
                    }
                } else {
                    $this->logger->addWarningLog($log, $entityType);
                }
            } else {
                if (is_array($log)) {
                    foreach ($log as $logIndex => $logText) {
                        $this->logger->addErrorLog($logText, $entityType);
                        $this->error_msg[] = $logText;
                    }
                } else {
                    $this->logger->addErrorLog($log, $entityType);
                    $this->error_msg[] = $log;
                }
            }
        } else {
            if (is_array($log)) {
                foreach ($log as $logIndex => $logText) {
                    $this->logger->addWarningLog($logText, $entityType);
                }
            } else {
                $this->logger->addWarningLog($log, $entityType);
            }
        }
    }
}
