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

if (!defined('_PS_VERSION_')) {
    exit;
}
require_once(_PS_MODULE_DIR_ . 'woomigrationpro/classes/loggers/WooMigrationProDBWarningLogger.php');
require_once(_PS_MODULE_DIR_ . 'woomigrationpro/classes/loggers/WooMigrationProDBErrorLogger.php');

class AdminWooMigrationProController extends AdminController
{
    // --- response vars:
    public $errors;
    protected $response;

    // --- request vars:
    protected $stepNumber;
    protected $toStepNumber;

    // -- dynamic vars:
    protected $forceIds = true;
    protected $moduel_error_reporting = true;
    protected $forceCategoryIds = false;
    protected $forceProductIds = false;
    protected $forceCustomerIds = false;
    protected $forceOrderIds = false;
    protected $forceManufacturerIds = false;
    protected $truncate = false;

    // --- source cart vars:
    protected $url_cart;
    protected $server_path = '/wp-content/plugins/connector/server.php';
    protected $token_cart;
    protected $cms;
    protected $image_category;
    protected $image_product;
    protected $image_manufacturer;
    protected $image_supplier;
    protected $table_prefix;
    protected $version;
    protected $charset;
    protected $blowfish_key;
    protected $cookie_key;
    protected $mapping;
    protected $languagesForQuery;
    protected $speed;
    // ---get sorce tax rule group count
    protected $taxRulesGroupCount;
    protected $prices_include_tax;
    protected $prices_include_tax_autoload;
    protected $default_country;
    protected $default_tax_rate;

    // --- helper objects
    protected $query;
    protected $client;
    protected $woo_version;
    protected $wpml;
    protected $recent_data;

    public function __construct()
    {
        $this->display = 'edit';
        parent::__construct();
        $this->controller_type = 'moduleadmin'; //instead of AdminController’s admin
        $tab = new Tab($this->id); // an instance with your tab is created; if the tab is not attached to the module, the exception will be thrown
        if (!$tab->module) {
            throw new PrestaShopException('Admin tab ' . get_class($this) . ' is not a module tab');
        }
        $this->module = Module::getInstanceByName($tab->module);
        if (!$this->module->id) {
            throw new PrestaShopException("Module {$tab->module} not found");
        }
        $this->tabAccess = Profile::getProfileAccess(
            $this->context->employee->id_profile,
            Tab::getIdFromClassName('AdminWooMigrationPro')
        );

        $this->stepNumber = (int)Tools::getValue('step_number');
        $this->toStepNumber = (int)Tools::getValue('to_step_number');

        $this->initParams();
        if ($this->stepNumber > 1 || self::isEmpty($this->stepNumber)) {
            self::initHelperObjects();
        }
    }


    private function initHelperObjects()
    {
        $this->mapping = WooMigrationProMapping::listMapping(true, true);
        if (isset($this->mapping['languages'])) {
            // -- unset language where value = 0
            if (($key = array_search(0, $this->mapping['languages'])) !== false) {
                unset($this->mapping['languages'][$key]);
            }
            $keys = array_keys($this->mapping['languages']);
            $this->languagesForQuery = implode(',', $keys);
        }

        if (!self::isEmpty($this->url_cart) && !self::isEmpty($this->token_cart)) {
            $this->client = new WooClient($this->url_cart . $this->server_path, $this->token_cart);
        }
        $this->query = new WooQuery();
        $this->query->setVersion($this->version);
        $this->query->setCart($this->cms);
        $this->query->setPrefix($this->table_prefix);
        $this->query->setLanguages($this->languagesForQuery);
        $this->query->setRowCount($this->speed);
        $this->query->setRecentData($this->recent_data);
    }

    // --- request processes
    public function postProcess()
    {
        parent::postProcess();
    }

    public function clearSmartyCache()
    {
        Tools::enableCache();
        Tools::clearCache($this->context->smarty);
        Tools::restoreCacheSettings();
    }

    public function ajaxProcessGenerateZip()
    {
        if (!Configuration::get('woomigrationpro_token_is_generated')) {
            if (!Configuration::get('woomigrationpro_token')) {
                $token = md5(Configuration::get('PS_SHOP_DOMAIN') . end(explode('\\', _PS_ADMIN_DIR_)));
                Configuration::updateValue('woomigrationpro_token', $token);
            }
            $txt_file = Tools::file_get_contents(_PS_MODULE_DIR_ . '/woomigrationpro/views/templates/admin/server.tpl');
            $txt_file = str_replace("[[[[[[sample_token]]]]]]]", Configuration::get('woomigrationpro_token'), $txt_file);
            $txt_file = str_replace("[[[[[[old_domain]]]]]]]", Configuration::get('PS_SHOP_DOMAIN'), $txt_file);
            file_put_contents(_PS_MODULE_DIR_ . '/woomigrationpro/assets/server.php', $txt_file);
            $zip = new ZipArchive;
            if ($zip->open(_PS_MODULE_DIR_ . 'woomigrationpro/assets/connector.zip')) {
                // Add file to the zip file
                $zip->addFile(_PS_MODULE_DIR_ . '/woomigrationpro/assets/server.php', 'connector/server.php');
                // All files are added, so close the zip file.
                $zip->close();
            }
            Configuration::updateValue('woomigrationpro_token_is_generated', '1');
        }
    }

    public function ajaxProcessValidateStep()
    {
        $this->response = array('has_error' => false, 'has_warning' => false);

        if (!$this->tabAccess['edit']) {
            $this->errors[] = WooImport::displayError($this->module->l('You do not have permission to use this wizard.'));
        } else {
            // -- reset old data and response url to start new migration
            if (Tools::getIsset('resume')) {
                $this->response['step_form'] = $this->module->renderStepThree();
            } elseif (Tools::getValue('to_step_number') >= Tools::getValue('step_number')) {
                $this->validateStepFieldsValue();
            }
        }
        if (count($this->errors)) {
            $this->response['has_error'] = true;
            $this->response['errors'] = $this->errors;
        } else {
            $this->response['has_error'] = false;
            $this->response['errors'] = null;
        }
        if (count($this->warnings)) {
            $this->response['has_warning'] = true;
            $this->response['warnings'] = $this->warnings;
        } else {
            $this->response['has_warning'] = false;
            $this->response['warnings'] = null;
        }

        die(Tools::jsonEncode($this->response));
    }

    // --- validate functions

    private function validateStepFieldsValue()
    {
        if ($this->stepNumber == 1) {
            // validate url
            if (!Tools::getValue('source_shop_url')) {
                $this->errors[] = $this->module->l('WooCommerce URL is required.');
            } elseif (!Validate::isAbsoluteUrl(Tools::getValue('source_shop_url'))) {
                $this->errors[] = $this->module->l('Please enter a valid WooCommerce URL.');
            }
            if ($this->truncateModuleTables()) {
                $this->errors[] = $this->module->l('Permission access to truncate module tables is denied! Please contact with support team!');
            }

            if (self::isEmpty($this->errors)) {
                $this->client = new WooClient(
                    Tools::getValue('source_shop_url') . $this->server_path,
                    Configuration::get('woomigrationpro_token')
                );
                if ($this->client->check()) {
                    $content = $this->client->getContent();
                    if (isset($content['cms']) && !self::isEmpty($content['cms'])) {
                        $this->saveParamsToConfiguration($content);
                        $this->initHelperObjects();
                        if ($this->requestToCartDetails()) {
                            $this->response['step_form'] = $this->module->renderStepTwo();
                        }
                    } else {
                        $this->errors[] = WooImport::displayError($this->module->l('Please check the URL ') . ' - ' . $this->client->getMessage());
                    }
                } else {
                    $this->errors[] = WooImport::displayError('Please check URL') . ' - ' . $this->client->getMessage();
                }
            }
        } elseif ($this->stepNumber == 2) {
            $maps = Tools::getValue('map');
            $languageSumValue = array_sum($maps['languages']);
            $languageDiffResArray = array_diff_assoc($maps['languages'], array_unique($maps['languages']));
            if (!($languageSumValue > 0 && self::isEmpty($languageDiffResArray))) {
                $this->errors[] = $this->module->l('Select a different language for each source language.');
            }
            if (self::isEmpty($this->errors)) {
                $this->initHelperObjects();
                Configuration::updateValue($this->module->name . '_force_category_ids', Tools::getValue('force_category_ids'));
                Configuration::updateValue($this->module->name . '_force_product_ids', Tools::getValue('force_product_ids'));
                Configuration::updateValue($this->module->name . '_force_customer_ids', Tools::getValue('force_customer_ids'));
                Configuration::updateValue($this->module->name . '_force_order_ids', Tools::getValue('force_order_ids'));
                Configuration::updateValue($this->module->name . '_turn_on_errors', Tools::getValue('turn_on_errors'));
                Configuration::updateValue($this->module->name . '_force_manufacturer_ids', Tools::getValue('force_manufacturer_ids'));
                Configuration::updateValue($this->module->name . '_clear_data', Tools::getValue('clear_data'));
                Configuration::updateValue($this->module->name . '_migrate_recent_data', Tools::getValue('migrate_recent_data'));
                Configuration::updateValue($this->module->name . '_query_row_count', self::convertSpeedNameToNumeric(Tools::getValue('speed')));
                if ($this->createMapping($maps) && $this->createProcess()) {
                    $this->saveMappingValues(WooMigrationProMapping::listMapping(true, true));
                    // turn on allow html iframe on
                    if (!Configuration::get('PS_ALLOW_HTML_IFRAME')) {
                        Configuration::updateValue('PS_ALLOW_HTML_IFRAME', 1);
                        Configuration::updateValue($this->module->name . '_allow_html_iframe', 1);
                    }
                    //get tax rate for default country
                    $this->client->setPostData($this->query->getDefaultTaxRate($this->default_country));
                    $this->client->serializeOn();
                    if ($this->client->query()) {
                        $default_tax_rate = $this->client->getContent();
                        Configuration::updateValue($this->module->name . '_default_tax_rate', $default_tax_rate['default_country_tax_rate'][0]['rate']);
                        $this->response['step_form'] = $this->module->renderStepThree();
                    } else {
                        $this->errors[] = $this->l('Can\'t execute query to source Shop. ' . $this->client->getMessage());
                    }
                } else {
                    $this->errors[] = $this->module->l('Select a minimum of one data type to start migrating.');
                }
            }
        }
    }


    public function ajaxProcessClearCache()
    {
        if (Tools::getValue('clear_cache')) {
            ini_set('max_execution_time', 0);
            Tools::clearSmartyCache();
            Tools::clearXMLCache();
            Media::clearCache();
            Tools::generateIndex();
            Search::indexation(true);

            //Clear  temporary directory
            $path = _PS_TMP_IMG_DIR_ . '/mp_temp_dir';
            array_map('unlink', glob("$path/*.*"));

            $this->response['has_error'] = false;
            $this->response['has_warning'] = false;
            if (count($this->errors)) {
                $this->response['has_error'] = true;
                $this->response['errors'] = $this->errors;
            }
            if (count($this->warnings)) {
                $this->response['has_warning'] = true;
                $this->response['warnings'] = $this->warnings;
            }

            die(Tools::jsonEncode($this->response));
        }
    }

    public function ajaxProcessDebugOn()
    {
        if (Tools::getValue('turn') == 1) {
            Configuration::updateValue('debug_mode', 1);
        } else {
            Configuration::updateValue('debug_mode', 0);
        }
    }

    public function ajaxProcessSpeedUp()
    {
        if (!self::isEmpty(Tools::getValue('speed'))) {
            Configuration::updateValue($this->module->name . '_query_row_count', Tools::getValue('speed'));
        }
    }

    public function ajaxProcessImportProcess($die = true)
    {
        $this->response = array('has_error' => false, 'has_warning' => false);

        if (!$this->tabAccess['edit']) {
            $this->errors[] = WooImport::displayError($this->module->l('You do not have permission to use this wizard.'));
        } else {
            if ($this->response['percent'] != 100) {
                $activeProcess = WooMigrationProProcess::getActiveProcessObject();
                if (Validate::isLoadedObject($activeProcess)) {
                    $this->query->setOffset($activeProcess->imported);
                    if ($activeProcess->imported == 0) {
                        if ($this->truncate && !$this->truncateTables($activeProcess->type)) {
                            $this->errors[] = 'Can\'t clear current data on Target shop ' . Db::getInstance()->getMsgError();
                        }
                        $activeProcess->time_start = date('Y-m-d H:i:s', time());
                        $activeProcess->save();
                    }
                    if ($activeProcess->type == 'taxes') {
                        $this->importTaxes($activeProcess);
                        $this->clearSmartyCache();
                    } elseif ($activeProcess->type == 'manufacturers') {
                        $this->importManufacturers($activeProcess);
                        $this->clearSmartyCache();
                    } elseif ($activeProcess->type == 'categories') {
                        $this->importCategories($activeProcess);
                        $this->clearSmartyCache();
                    } elseif ($activeProcess->type == 'products') {
                        $this->importProducts($activeProcess);
                        $this->clearSmartyCache();
                    } elseif ($activeProcess->type == 'customers') {
                        $this->importCustomers($activeProcess);
                        $this->clearSmartyCache();
                    } elseif ($activeProcess->type == 'orders') {
                        $this->importOrders($activeProcess);
                        $this->clearSmartyCache();
                    }
                } else {
                    die('no process.');
                }
            }
            $this->response['percent'] = WooMigrationProProcess::calculateImportedDataPercent();
            if ($this->response['percent'] == 100) {
                // turn off allow html iframe feature
                Currency::refreshCurrencies();
                if (Configuration::get($this->module->name . '_allow_html_iframe')) {
                    Configuration::updateValue('PS_ALLOW_HTML_IFRAME', 0, null, 0, 0);
                    Configuration::updateValue($this->module->name . '_allow_html_iframe', 0, null, 0, 0);
                }
            }
        }

        if (count($this->errors)) {
            $this->response['has_error'] = true;
            $this->response['errors'] = $this->errors;
        }
        if (count($this->warnings)) {
            $this->response['has_warning'] = true;
            $this->response['warnings'] = $this->warnings;
        }

        if ($die) {
            die(Tools::jsonEncode($this->response));
        }
    }

    // --- import functions

    private function importTaxes($process)
    {
        $this->client->serializeOn();
        $this->client->setPostData($this->query->taxes());
        if ($this->client->query()) {
            $taxs = $this->client->getContent();
            $taxRulesGroups = $this->converTaxRulesGroupStructur($taxs['tax_rules']);
            $import = new WooImport($process, $this->version, $this->url_cart, $this->forceIds);
            $import->taxes($taxs, $taxRulesGroups);
            $this->errors = $import->getErrorMsg();
            $this->warnings = $import->getWarningMsg();
            $this->response = $import->getResponse();
        } else {
            $this->errors[] = $this->l('Can\'t execute query to source Shop. ' . $this->client->getMessage());
        }
    }


    public function converTaxRulesGroupStructur($taxrulesgroups)
    {
        $convertedStructur = array();

        foreach ($taxrulesgroups as $taxrulesgroup) {
            $convertedStructur[] = $taxrulesgroup['tax_rules_group'];
        }
        return array_unique($convertedStructur);
    }

    private function importManufacturers($process)
    {
        $this->client->serializeOn();
        $this->client->setPostData($this->query->manufactures());
        if ($this->client->query()) {
            $manufacturers = $this->client->getContent();
            $import = new WooImport($process, $this->version, $this->url_cart, $this->forceManufacturerIds);
            $import->setImagePath($this->image_manufacturer);
            $import->manufacturers($manufacturers);
            $this->errors = $import->getErrorMsg();
            $this->warnings = $import->getWarningMsg();
            $this->response = $import->getResponse();
        } else {
            $this->errors[] = $this->l('Can\'t execute query to source Shop. ' . $this->client->getMessage());
        }
    }

    private function importCategories($process)
    {

        //@TODO find fix for PS 1.4 for category id 2 WHERE is ID 2 standart category from list
        $this->client->serializeOn();
        $this->client->setPostData($this->query->category($this->wpml));
        if ($this->client->query()) {
            $categories = $this->client->getContent();
            $import = new WooImport(
                $process,
                $this->version,
                $this->url_cart,
                $this->forceCategoryIds,
                $this->client,
                $this->query
            );
            $import->setImagePath($this->image_category);
            $import->categories($categories);
            $this->errors = $import->getErrorMsg();
            $this->warnings = $import->getWarningMsg();
            $this->response = $import->getResponse();
        } else {
            $this->errors[] = $this->l('Can\'t execute query to source Shop. ' . $this->client->getMessage());
        }
    }

    private function importProducts($process)
    {
        $this->client->serializeOn();
        $this->client->setPostData($this->query->product($this->wpml));
        if ($this->client->query()) {
            $products = $this->client->getContent();
            $import = new WooImport($process, $this->version, $this->url_cart, $this->forceProductIds);
            $import->setImagePath($this->url_cart . '/wp-content/uploads/');
            $import->setPricesIncludeTax($this->prices_include_tax);
            $import->setPricesIncludeTaxAutoload($this->prices_include_tax_autoload);
            $import->setDefaultTaxRate(Configuration::get($this->module->name . '_default_tax_rate'));
            $import->products($products);
            $this->errors = $import->getErrorMsg();
            $this->warnings = $import->getWarningMsg();
            $this->response = $import->getResponse();
        } else {
            $this->errors[] = $this->l(
                'Can\'t execute query to source Shop. ' . $this->client->getMessage()
            );
        }
    }

    private static function getAtachmentUrls($products)
    {
        $urls_array = array();
        foreach ($products as $product) {
            if (!self::isEmpty(unserialize($product['_downloadable_files']))) {
                foreach (unserialize($product['_downloadable_files']) as $file) {
                    $urls_array[] = pSQL($file['file']);
                }
            }
        }

        return '"' . implode('","', array_filter($urls_array)) . '"';
    }

    private function importCustomers($process)
    {
        $this->client->serializeOn();
        $this->client->setPostData($this->query->customers());
        if ($this->client->query()) {
            $customers = $this->client->getContent();
            $customers['customer'] = WooMigrationProConvertDataStructur::connectWcMetadataWithData($customers['customer_second'], $customers['customer'], 'id_customer');
            $import = new WooImport($process, $this->version, $this->url_cart, $this->forceCustomerIds);
            $import->customers($customers['customer'], $customers['addresses']);
            $this->errors = $import->getErrorMsg();
            $this->warnings = $import->getWarningMsg();
            $this->response = $import->getResponse();
        } else {
            $this->errors[] = $this->l('Can\'t execute query to source Shop. ' . $this->client->getMessage());
        }
    }

    private function importOrders($process)
    {
        $this->client->serializeOn();
        $this->client->setPostData($this->query->order($this->woo_version));
        if ($this->client->query()) {
            $orders = $this->client->getContent();
            $orders['order'] = WooMigrationProConvertDataStructur::connectWcMetadataWithData($orders['order_detail'], $orders['order'], 'post_id', 'ID');
            $orderHistorys = $orders['order_history'];
            $orders['billing_address'] = WooMigrationProConvertDataStructur::convertOrderAddressStructure($orders['billing_address'], true);
            $orders['shipping_address'] = WooMigrationProConvertDataStructur::convertOrderAddressStructure($orders['shipping_address']);
            $orderDetails = WooMigrationProConvertDataStructur::connectWcMetadataWithData($orders['line'], $orders['order_item'], 'order_item_id', null, 'order_detail');
            if (!self::isEmpty($orders['shipping'])) {
                $orderDetails = WooMigrationProConvertDataStructur::connectOrderAdditional($orderDetails, $orders['shipping']);
            }
            if (!self::isEmpty($orders['tax'])) {
                $orderDetails = WooMigrationProConvertDataStructur::connectOrderAdditional($orderDetails, $orders['tax']);
            }
            $import = new WooImport($process, $this->version, $this->url_cart, $this->forceOrderIds);
            $import->orders($orders, $orderDetails, $orderHistorys);
            $this->errors = $import->getErrorMsg();
            $this->warnings = $import->getWarningMsg();
            $this->response = $import->getResponse();
        } else {
            $this->errors[] = $this->l('Can\'t execute query to source Shop. ' . $this->client->getMessage());
        }
    }

    // --- Internal helper methods:

    private function truncateModuleTables()
    {
        $res = Db::getInstance()->execute('TRUNCATE TABLE  `' . _DB_PREFIX_ . 'woomigrationpro_data`');
        $res &= Db::getInstance()->execute('TRUNCATE TABLE  `' . _DB_PREFIX_ . 'woomigrationpro_process`');
        $res &= Db::getInstance()->execute('TRUNCATE TABLE  `' . _DB_PREFIX_ . 'woomigrationpro_mapping`');
        $res &= Db::getInstance()->execute('TRUNCATE TABLE  `' . _DB_PREFIX_ . 'migrationpro_woo`');

        if (!$res) {
            return false;
        }
    }

    private function createMapping($maps)
    {
        $res = true;
        foreach ($maps as $map) {
            foreach ($map as $key => $val) {
                $mapping = new WooMigrationProMapping($key);
                $mapping->local_id = $val;
                $res &= $mapping->save();
            }
        }

        return $res;
    }

    private function createProcess()
    {
        $res = Db::getInstance()->execute('TRUNCATE TABLE  `' . _DB_PREFIX_ . 'woomigrationpro_process`');
        $res &= Db::getInstance()->execute('TRUNCATE TABLE  `' . _DB_PREFIX_ . 'woomigrationpro_data`');
        WooMigrationProDBErrorLogger::removeErrorLogs();
        WooMigrationProDBWarningLogger::removeWarningLogs();
        WooMigrationProDBWarningLogger::removeLogFile();
        $this->client->setPostData($this->query->getCountInfo($this->wpml));
        $this->client->serializeOn();
        if ($this->client->query()) {
            $createProcessAction = $this->client->getContent();
            foreach ($createProcessAction as $processKey => $processCount) {
                if (isset($processCount[0]['c']) && !self::isEmpty($processCount[0]['c'])) {
                    // becouse woocommerce keep taxRuleGroups in one string
//                    if (!is_numeric($processCount[0]['c'])) {
//                        $taxGrups = array();
//                        $taxGrups[] = $processCount[0]['c'];
//                        $processCount[0]['c'] = count(self::getTaxGroupsArray($taxGrups));
//                    }
                    $process = new WooMigrationProProcess();
                    $process->type = $processKey;
                    $process->total = (int)$processCount[0]['c'];
                    $process->imported = 0;
                    $process->id_source = 0;
                    $process->error = 0;
                    $process->point = 0;
                    $process->time_start = 0;
                    $process->finish = 0;
                    $res &= $process->add();
                }
            }

            return $res;
        }

        return false;
    }

    private function saveParamsToConfiguration($content)
    {
        Configuration::updateValue($this->module->name . '_url', Tools::getValue('source_shop_url'));
        Configuration::updateValue($this->module->name . '_woo_version', $content['woo_version']);
        Configuration::updateValue($this->module->name . '_cms', $content['cms']);
        Configuration::updateValue($this->module->name . '_wpml', $content['wpml_is_active']);
//        Configuration::updateValue($this->module->name . '_wpml', 0);
        Configuration::updateValue($this->module->name . '_image_product', $content['image_product']);
//        Configuration::updateValue($this->module->name . '_image_manufacturer', $content['image_manufacturer']);
//        Configuration::updateValue($this->module->name . '_image_supplier', $content['image_supplier']);
        Configuration::updateValue($this->module->name . '_table_prefix', $content['table_prefix']);
        Configuration::updateValue($this->module->name . '_version', $content['version']);
        Configuration::updateValue($this->module->name . '_charset', $content['charset']);
        Configuration::updateValue($this->module->name . '_blowfish_key', $content['blowfish_key']);
        Configuration::updateValue($this->module->name . '_cookie_key', $content['cookie_key']);

        $this->initParams();
    }

    private function initParams()
    {
        $this->url_cart = Configuration::get($this->module->name . '_url');
        $this->token_cart = Configuration::get($this->module->name . '_token');
        $this->woo_version = Configuration::get($this->module->name . '_woo_version');
        $this->cms = Configuration::get($this->module->name . '_cms');
        $this->image_category = Configuration::get($this->module->name . '_image_category');
        $this->image_product = Configuration::get($this->module->name . '_image_product');
        $this->image_manufacturer = Configuration::get($this->module->name . '_image_manufacturer');
        $this->image_supplier = Configuration::get($this->module->name . '_image_supplier');
        $this->table_prefix = Configuration::get($this->module->name . '_table_prefix');
        $this->version = Configuration::get($this->module->name . '_version');
        $this->charset = Configuration::get($this->module->name . '_charset');
        $this->blowfish_key = Configuration::get($this->module->name . '_blowfish_key');
        $this->cookie_key = Configuration::get($this->module->name . '_cookie_key');
        $this->forceCategoryIds = Configuration::get($this->module->name . '_force_category_ids');
        $this->forceProductIds = Configuration::get($this->module->name . '_force_product_ids');
        $this->forceCustomerIds = Configuration::get($this->module->name . '_force_customer_ids');
        $this->forceOrderIds = Configuration::get($this->module->name . '_force_order_ids');
        $this->moduel_error_reporting = Configuration::get($this->module->name . '_turn_on_errors');
        $this->forceManufacturerIds = Configuration::get($this->module->name . '_force_manufacturer_ids');
        $this->truncate = Configuration::get($this->module->name . '_clear_data');
        $this->speed = Configuration::get($this->module->name . '_query_row_count');
        $this->wpml = Configuration::get($this->module->name . '_wpml');
        $this->prices_include_tax = Configuration::get($this->module->name . '_prices_include_tax');
        $this->prices_include_tax_autoload = Configuration::get($this->module->name . '_prices_include_tax_autoload');
        $this->default_country = Configuration::get($this->module->name . '_default_country');
        $this->recent_data = Configuration::get($this->module->name . '_migrate_recent_data');
    }

    private function requestToCartDetails()
    {
        // --- get default values from source cart

        $this->client->setPostData($this->query->getDefaultShopValues());
        $this->client->serializeOn();
        $this->client->query();
        $resultDefaultShopValues = $this->client->getContent();

        $this->query->setVersion($this->version);
        Configuration::updateValue($this->module->name . '_default_currency', $resultDefaultShopValues['default_currency'][0]['source_name']);
        Configuration::updateValue($this->module->name . '_default_lang', $resultDefaultShopValues['default_lang'][0]['source_name']);
        Configuration::updateValue($this->module->name . '_prices_include_tax', $resultDefaultShopValues['woocommerce_prices_include_tax'][0]['option_value']);
        Configuration::updateValue($this->module->name . '_prices_include_tax_autoload', $resultDefaultShopValues['woocommerce_prices_include_tax'][0]['autoload']);
        Configuration::updateValue($this->module->name . '_default_country', $resultDefaultShopValues['default_country'][0]['option_value']);
        $this->client->setPostData($this->query->getMappingInfo($resultDefaultShopValues['default_lang'][0]['value']));
        $this->client->query();
        $mappingInformation = $this->client->getContent();
        $mapping_value = WooMigrationProSaveMapping::listMapping(true, true);

        if (is_array($mappingInformation)) {
            if ($this->checkMappingIsEmpty(WooMigrationProMapping::listMapping())) {
                if (Db::getInstance()->execute('TRUNCATE TABLE  `' . _DB_PREFIX_ . 'woomigrationpro_mapping`')) {
                    foreach ($mappingInformation as $mappingType => $mappingObject) {
                        foreach ($mappingObject as $value) {
                            $mapping = new WooMigrationProMapping();
                            $mapping->type = $mappingType;
                            $mapping->source_id = $value['source_id'];
                            $mapping->source_name = $value['source_name'];
                            if (!self::isEmpty($mapping_value)) {
                                $mapping->local_id = $mapping_value[$mappingType][$value['source_id']];
                            }
                            if (!$mapping->save()) {
                                $this->errors[] = $this->module->l('Can\'t save to database mapping information. ');
                            }
                        }
                    }
                } else {
                    $this->errors[] = $this->module->l('Can\'t truncate mapping table');
                }
            }
        }
        if (self::isEmpty($this->errors)) {
            return true;
        }

        return false;
    }

    protected function checkMappingIsEmpty($mapping)
    {

        if (self::isEmpty($mapping)) {
            return true;
        } else {
            foreach ($mapping as $map) {
                if ($map['local_id'] == 0) {
                    return true;
                }
            }
        }

        return false;
    }

    protected function truncateTables($case)
    {
        $res = false;
        switch ($case) {
            case 'taxes':
                $res &= Db::getInstance()->execute('TRUNCATE TABLE `' . _DB_PREFIX_ . 'tax`');
                $res &= Db::getInstance()->execute('TRUNCATE TABLE `' . _DB_PREFIX_ . 'tax_lang`');
                $res &= Db::getInstance()->execute('TRUNCATE TABLE `' . _DB_PREFIX_ . 'tax_rule`');
                $res &= Db::getInstance()->execute('TRUNCATE TABLE `' . _DB_PREFIX_ . 'tax_rules_group`');
                $res &= Db::getInstance()->execute('TRUNCATE TABLE `' . _DB_PREFIX_ . 'tax_rules_group_shop`');
                break;
            case 'manufacturers':
                $res &= Db::getInstance()->execute('TRUNCATE TABLE `' . _DB_PREFIX_ . 'manufacturer`');
                $res &= Db::getInstance()->execute('TRUNCATE TABLE `' . _DB_PREFIX_ . 'manufacturer_lang`');
                $res &= Db::getInstance()->execute('TRUNCATE TABLE `' . _DB_PREFIX_ . 'manufacturer_shop`');
                foreach (scandir(_PS_MANU_IMG_DIR_) as $d) {
                    if (preg_match('/^[0-9]+(\-(.*))?\.jpg$/', $d)) {
                        unlink(_PS_MANU_IMG_DIR_ . $d);
                    }
                }
                break;
            case 'categories':
                $res &= Db::getInstance()->execute('TRUNCATE TABLE `' . _DB_PREFIX_ . 'woomigrationpro_category`');
                $res &= Db::getInstance()->execute(
                    '
					DELETE FROM `' . _DB_PREFIX_ . 'category`
					WHERE id_category NOT IN (' . (int)Configuration::get('PS_HOME_CATEGORY') .
                    ', ' . (int)Configuration::get('PS_ROOT_CATEGORY') . ')'
                );
                $res &= Db::getInstance()->execute(
                    '
					DELETE FROM `' . _DB_PREFIX_ . 'category_lang`
					WHERE id_category NOT IN (' . (int)Configuration::get('PS_HOME_CATEGORY') .
                    ', ' . (int)Configuration::get('PS_ROOT_CATEGORY') . ')'
                );
                $res &= Db::getInstance()->execute(
                    '
					DELETE FROM `' . _DB_PREFIX_ . 'category_shop`
					WHERE `id_category` NOT IN (' . (int)Configuration::get('PS_HOME_CATEGORY') .
                    ', ' . (int)Configuration::get('PS_ROOT_CATEGORY') . ')'
                );
//                $res &= Db::getInstance()->execute('ALTER TABLE `' . _DB_PREFIX_ . 'category` AUTO_INCREMENT = 3');
                foreach (scandir(_PS_CAT_IMG_DIR_) as $d) {
                    if (preg_match('/^[0-9]+(\-(.*))?\.jpg$/', $d)) {
                        unlink(_PS_CAT_IMG_DIR_ . $d);
                    }
                }
                break;
            case 'products':
                $res &= Db::getInstance()->execute('TRUNCATE TABLE `' . _DB_PREFIX_ . 'product`');
                $res &= Db::getInstance()->execute('TRUNCATE TABLE `' . _DB_PREFIX_ . 'product_shop`');
                $res &= Db::getInstance()->execute('TRUNCATE TABLE `' . _DB_PREFIX_ . 'feature_product`');
                $res &= Db::getInstance()->execute('TRUNCATE TABLE `' . _DB_PREFIX_ . 'product_lang`');
                $res &= Db::getInstance()->execute('TRUNCATE TABLE `' . _DB_PREFIX_ . 'category_product`');
                $res &= Db::getInstance()->execute('TRUNCATE TABLE `' . _DB_PREFIX_ . 'product_tag`');
                $res &= Db::getInstance()->execute('TRUNCATE TABLE `' . _DB_PREFIX_ . 'image`');
                $res &= Db::getInstance()->execute('TRUNCATE TABLE `' . _DB_PREFIX_ . 'image_lang`');
                $res &= Db::getInstance()->execute('TRUNCATE TABLE `' . _DB_PREFIX_ . 'image_shop`');
                $res &= Db::getInstance()->execute('TRUNCATE TABLE `' . _DB_PREFIX_ . 'specific_price`');
                $res &= Db::getInstance()->execute('TRUNCATE TABLE `' . _DB_PREFIX_ . 'specific_price_priority`');
                $res &= Db::getInstance()->execute('TRUNCATE TABLE `' . _DB_PREFIX_ . 'product_carrier`');
                $res &= Db::getInstance()->execute('TRUNCATE TABLE `' . _DB_PREFIX_ . 'cart_product`');
                //@TODO if presta version not 17
//                $res &= Db::getInstance()->execute('TRUNCATE TABLE `' . _DB_PREFIX_ . 'compare_product`');
                if (count(
                    Db::getInstance()->executeS('SHOW TABLES LIKE \'' . _DB_PREFIX_ . 'favorite_product\' ')
                )) { //check if table exist
                    $res &= Db::getInstance()->execute('TRUNCATE TABLE `' . _DB_PREFIX_ . 'favorite_product`');
                }
                $res &= Db::getInstance()->execute('TRUNCATE TABLE `' . _DB_PREFIX_ . 'product_attachment`');
                $res &= Db::getInstance()->execute('TRUNCATE TABLE `' . _DB_PREFIX_ . 'accessory`');
                $res &= Db::getInstance()->execute('TRUNCATE TABLE `' . _DB_PREFIX_ . 'product_country_tax`');
                $res &= Db::getInstance()->execute('TRUNCATE TABLE `' . _DB_PREFIX_ . 'product_download`');
                $res &= Db::getInstance()->execute('TRUNCATE TABLE `' . _DB_PREFIX_ . 'product_group_reduction_cache`');
                $res &= Db::getInstance()->execute('TRUNCATE TABLE `' . _DB_PREFIX_ . 'product_sale`');
                $res &= Db::getInstance()->execute('TRUNCATE TABLE `' . _DB_PREFIX_ . 'product_supplier`');
                //@TODO if presta version not 17
//                $res &= Db::getInstance()->execute('TRUNCATE TABLE `' . _DB_PREFIX_ . 'scene_products`');
                $res &= Db::getInstance()->execute('TRUNCATE TABLE `' . _DB_PREFIX_ . 'warehouse_product_location`');
                $res &= Db::getInstance()->execute('TRUNCATE TABLE `' . _DB_PREFIX_ . 'stock`');
                $res &= Db::getInstance()->execute('TRUNCATE TABLE `' . _DB_PREFIX_ . 'stock_available`');
                $res &= Db::getInstance()->execute('TRUNCATE TABLE `' . _DB_PREFIX_ . 'stock_mvt`');
                $res &= Db::getInstance()->execute('TRUNCATE TABLE `' . _DB_PREFIX_ . 'customization`');
                $res &= Db::getInstance()->execute('TRUNCATE TABLE `' . _DB_PREFIX_ . 'customization_field`');
                $res &= Db::getInstance()->execute('TRUNCATE TABLE `' . _DB_PREFIX_ . 'customization_field_lang`');
                $res &= Db::getInstance()->execute('TRUNCATE TABLE `' . _DB_PREFIX_ . 'supply_order_detail`');
                $res &= Db::getInstance()->execute('TRUNCATE TABLE `' . _DB_PREFIX_ . 'attribute_impact`');
                $res &= Db::getInstance()->execute('TRUNCATE TABLE `' . _DB_PREFIX_ . 'product_attribute`');
                $res &= Db::getInstance()->execute('TRUNCATE TABLE `' . _DB_PREFIX_ . 'product_attribute_shop`');
                $res &= Db::getInstance()->execute('TRUNCATE TABLE `' . _DB_PREFIX_ . 'product_attribute_combination`');
                $res &= Db::getInstance()->execute('TRUNCATE TABLE `' . _DB_PREFIX_ . 'product_attribute_image`');
                $res &= Db::getInstance()->execute('TRUNCATE TABLE `' . _DB_PREFIX_ . 'pack`');
                Image::deleteAllImages(_PS_PROD_IMG_DIR_);
                if (!file_exists(_PS_PROD_IMG_DIR_)) {
                    mkdir(_PS_PROD_IMG_DIR_);
                }
//                break;
//            case 'combinations':

                $res &= Db::getInstance()->execute('TRUNCATE TABLE `' . _DB_PREFIX_ . 'attribute_impact`');
                $res &= Db::getInstance()->execute('TRUNCATE TABLE `' . _DB_PREFIX_ . 'attribute_lang`');
                $res &= Db::getInstance()->execute('TRUNCATE TABLE `' . _DB_PREFIX_ . 'attribute_group_lang`');
                $res &= Db::getInstance()->execute('TRUNCATE TABLE `' . _DB_PREFIX_ . 'attribute_group_shop`');
                $res &= Db::getInstance()->execute('TRUNCATE TABLE `' . _DB_PREFIX_ . 'attribute_shop`');
                $res &= Db::getInstance()->execute('TRUNCATE TABLE `' . _DB_PREFIX_ . 'product_attribute`');
                $res &= Db::getInstance()->execute('TRUNCATE TABLE `' . _DB_PREFIX_ . 'product_attribute_shop`');
                $res &= Db::getInstance()->execute('TRUNCATE TABLE `' . _DB_PREFIX_ . 'product_attribute_combination`');
                $res &= Db::getInstance()->execute('TRUNCATE TABLE `' . _DB_PREFIX_ . 'product_attribute_image`');
                $res &= Db::getInstance()->execute('DELETE FROM `' . _DB_PREFIX_ . 'attribute`');
                $res &= Db::getInstance()->execute('DELETE FROM `' . _DB_PREFIX_ . 'attribute_group`');
                $res &= Db::getInstance()->execute(
                    'DELETE FROM `' . _DB_PREFIX_ . 'stock_available` WHERE id_product_attribute != 0'
                );
//            case 'suppliers':
                $res &= Db::getInstance()->execute('TRUNCATE TABLE `' . _DB_PREFIX_ . 'supplier`');
                $res &= Db::getInstance()->execute('TRUNCATE TABLE `' . _DB_PREFIX_ . 'supplier_lang`');
                $res &= Db::getInstance()->execute('TRUNCATE TABLE `' . _DB_PREFIX_ . 'supplier_shop`');
                foreach (scandir(_PS_SUPP_IMG_DIR_) as $d) {
                    if (preg_match('/^[0-9]+(\-(.*))?\.jpg$/', $d)) {
                        unlink(_PS_SUPP_IMG_DIR_ . $d);
                    }
                }
//                break;
                break;
            case 'customers':
                $res &= Db::getInstance()->execute('TRUNCATE TABLE `' . _DB_PREFIX_ . 'customer`');
//                break;
//            case 'addresses':
                $res &= Db::getInstance()->execute('TRUNCATE TABLE `' . _DB_PREFIX_ . 'address`');
                break;
            case 'orders':
//                $res &= Db::getInstance()->execute('TRUNCATE TABLE `' . _DB_PREFIX_ . 'customer`');
//                $res &= Db::getInstance()->execute('TRUNCATE TABLE `' . _DB_PREFIX_ . 'address`');
                $res &= Db::getInstance()->execute('TRUNCATE TABLE `' . _DB_PREFIX_ . 'orders`');
                $res &= Db::getInstance()->execute('TRUNCATE TABLE `' . _DB_PREFIX_ . 'order_detail`');
                $res &= Db::getInstance()->execute('TRUNCATE TABLE `' . _DB_PREFIX_ . 'order_history`');
                $res &= Db::getInstance()->execute('TRUNCATE TABLE `' . _DB_PREFIX_ . 'order_carrier`');
                $res &= Db::getInstance()->execute('TRUNCATE TABLE `' . _DB_PREFIX_ . 'cart`');
                break;
        }
        Image::clearTmpDir();

        return $res;
    }

    // --- Static utility methods:

    public static function getCleanIDs($rows, $key)
    {
        $result = array();
        if (is_array($rows) && !self::isEmpty($rows)) {
            foreach ($rows as $row) {
                if (!self::isEmpty($row[$key]) && $row[$key]) {
                    $result[] = $row[$key];
                }
            }
            $result = array_unique($result);
            if (is_array($result)) {
                $result = implode(',', $result);
            }


            return $result;
        }
    }


    public static function getTaxGroupsArray($getTaxGroups)
    {
        $result = array_filter(array_map('trim', explode("\n", str_replace(' ', '-', Tools::strtolower($getTaxGroups[0])))));
        //-- becouse woocoommerce dont keep 'Standart Rates' class in db, and other class keep in one string.
        array_unshift($result, '', 'standart-rates');
        unset($result[0]);

        return $result;
    }

    public static function getCleanNames($taxRulesGroups, $metakey, $prefix = '')
    {
        $result = '';

        foreach ($taxRulesGroups as $key => $value) {
            $result .= '"' . $prefix . $value[$metakey] . '",';
        }
        ($metakey != 'attribute_name') ? $result .= '""' : $result = trim($result, ",");


        return $result;
    }


    public static function addGroupIdToAttribute($attrgroups, $attributes, $prefix)
    {
        $result = array();
        foreach ($attributes as $key => $attribute) {
            $k = str_replace($prefix, "", $attribute['attribute_group']);
            foreach ($attrgroups as $key => $attrgroup) {
                if ($attrgroup['attribute_name'] == $k) {
                    $id_attrgroup = $attrgroup['attribute_id'];
                }
            }
            $attribute['attrgroup_id'] = $id_attrgroup;
            $result[] = $attribute;
        }

        return $result;
    }


    public static function addAnyAttribute($attrgroups, $attributes, $prefix, $attrlangs = null)
    {

        $names_attribute_group = '';
        foreach ($attrgroups as $key => $value) {
            $names_attribute_group .= $prefix . $value['attribute_name'] . ',';
        }
        $names_attribute_group = trim($names_attribute_group, ",");
        $array_attribute_group_ids = explode(",", $names_attribute_group);
        $count = 1111111111;
        $lang = WooMigrationProMapping::listMapping(true, true)['languages'];
        $any_attribute = array();
        $any_attribute_lang = array();
        foreach ($array_attribute_group_ids as $id) {
            $any_attribute[] = array('id_attribute' => $count, 'name' => 'any', 'attribute_group' => $id);
            foreach ($lang as $key => $value) {
                $any_attribute_lang[] = array('id_attribute' => $count, 'name' => 'any', 'id_lang' => $key);
            }
            $count++;
        }
        //add langs

        if (!self::isEmpty($attrlangs)) {
            return array_merge($attrlangs, $any_attribute_lang);
        } else {
            return array_merge($attributes, $any_attribute);
        }
    }


    public static function addressMerge($billing_address, $shipping_address)
    {
        $shipping_keys = array("shipping_first_name", "shipping_last_name", "shipping_company", "shipping_email", "shipping_phone", "shipping_country", "shipping_address_1", "shipping_address_2", "shipping_city", "shipping_state", "shipping_postcode", "last_update");

        foreach ($billing_address as $key => $value) {
            foreach ($shipping_keys as $shipping_key) {
                $billing_address[$key][$shipping_key] = $shipping_address[$key][$shipping_key];
            }
        }

        return $billing_address;
    }

    public static function addAttributeIdToProductVariation($attribute_variations, $attributes, $prefix)
    {
        $count = 1;
        $result = array();
        foreach ($attribute_variations as $attribute_variation) {
            foreach ($attribute_variation as $key => $value) {
                if (preg_match('/attribute_pa_/', $key)) {
                    $attrgroup_name = str_replace("attribute_pa_", $prefix, $key);
                    if (self::isEmpty($value) || $value == '') {
                        $value = 'any';
                    }

                    foreach ($attributes as $attribute) {
                        if (strcasecmp($value, $attribute['slug']) == 0 && strcasecmp($attrgroup_name, $attribute['attribute_group']) == 0) {
                            $id_attribute = $attribute['id_attribute'];
                        }
                    }
                    $attribute_variation['id_attribute'] = $id_attribute;
                    unset($id_attribute);
                    $attribute_variation['id_product_attribute'] = $count;
                    $count++;
                    $result[] = $attribute_variation;
                }
            }
        }

        return $result;
    }

    public static function getAllImgIds($products, $thmids, $varimgids)
    {

        $imgids = '';

        foreach ($products as $product) {
            if (!self::isEmpty($product['_product_image_gallery'])) {
                $imgids .= ',' . $product['_product_image_gallery'] . ',';
            }
        }


        $result = $imgids . ',' . $thmids . ',' . $varimgids;

        $result = implode(',', array_filter(explode(',', $result)));

        return $result;
    }

    public static function displayError($string = 'Fatal error', $htmlentities = false)
    {
        return $htmlentities ? Tools::htmlentitiesUTF8(Tools::stripslashes($string)) : $string;
    }

    public static function addOriginalPostParentId($images, $post_variations)
    {
        $result = array();
        foreach ($images as $image) {
            $fakeParent = $image['post_parent'];
            foreach ($post_variations as $post_variation) {
                if ($post_variation['ID'] == $fakeParent) {
                    $realParentId = $post_variation['post_parent'];
                    $image['real_parent'] = $realParentId;
                }
            }

            $result[] = $image;
        }

        return $result;
    }

    public static function getProductAttributeNames($products)
    {
        $result = array();
        foreach ($products as $product) {
            $attr = $product['_product_attributes'];
            $arrAttr = unserialize($attr);
            foreach ($arrAttr as $attrName) {
                $result[] = '"' . pSQL($attrName['name']) . '"' . ',';
            }
        }
        $result = array_unique($result);
        $result = implode($result);
        $result = str_replace("pa_", "", $result);
        $result = Tools::substr($result, 0, -1);

        return $result;
    }


    public static function convertSpeedNameToNumeric($speed)
    {
        switch ((string)$speed) {
            case 'VerySlow':
                $row_count = 2;
                break;
            case 'Slow':
                $row_count = 5;
                break;
            case 'Normal':
                $row_count = 10;
                break;
            case 'Fast':
                $row_count = 25;
                break;
            case 'VeryFast':
                $row_count = 85;
                break;
            case 'MigrationProSpeed':
                $row_count = 100;
                break;
            default:
                $row_count = 10;
                break;
        }

        return $row_count;
    }

    public function saveMappingValues($mapping_values)
    {

        if (Db::getInstance()->execute('TRUNCATE TABLE  `' . _DB_PREFIX_ . 'woomigrationpro_save_mapping`')) {
            foreach ($mapping_values as $mappingType => $mappingObject) {
                foreach ($mappingObject as $source_id => $local_id) {
                    $mapping = new WooMigrationProSaveMapping();
                    $mapping->type = $mappingType;
                    $mapping->source_id = $source_id;
                    $mapping->source_name = $mappingType;
                    $mapping->local_id = $local_id;
                    if (!$mapping->save()) {
                        $this->errors[] = $this->module->l('Can\'t save to database mapping information. ');
                    }
                }
            }
        }
    }


    public static function isEmpty($field)
    {
        if (version_compare(PHP_VERSION, '5.5.0', '<')) {
            return ($field === '' || $field === null || $field === array() || $field === 0 || $field === '0');
        } else {
            return empty($field);
        }
    }
}
