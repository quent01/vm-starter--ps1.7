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

@ini_set('max_execution_time', 0);
@ini_set('error_reporting', 1);
@ini_set('memory_limit', "-1");

require_once(_PS_MODULE_DIR_ . 'woomigrationpro/classes/WooMigrationProMapping.php');
require_once(_PS_MODULE_DIR_ . 'woomigrationpro/classes/WooMigrationProSaveMapping.php');
require_once(_PS_MODULE_DIR_ . 'woomigrationpro/classes/WooMigrationProProcess.php');
require_once(_PS_MODULE_DIR_ . 'woomigrationpro/classes/WooMigrationProData.php');
require_once(_PS_MODULE_DIR_ . 'woomigrationpro/classes/WooMigrationProWoo.php');
require_once(_PS_MODULE_DIR_ . 'woomigrationpro/classes/WooMigrationProMigratedData.php');
require_once(_PS_MODULE_DIR_ . 'woomigrationpro/classes/WooMigrationProConvertDataStructur.php');
require_once(_PS_MODULE_DIR_ . 'woomigrationpro/classes/WooClient.php');
require_once(_PS_MODULE_DIR_ . 'woomigrationpro/classes/WooQuery.php');
require_once(_PS_MODULE_DIR_ . 'woomigrationpro/classes/WooImport.php');
require_once(_PS_MODULE_DIR_ . 'woomigrationpro/classes/WooPasswordEncrypt.php');

class WooMigrationPro extends Module
{
    protected $wizard_steps;

    public function __construct()
    {
        $this->name = 'woomigrationpro';
        $this->tab = 'migration_tools';
        $this->version = '5.1.4';
        $this->author = 'MigrationPro';
        $this->need_instance = 0;
        $this->bootstrap = true;
        $this->module_key = '74432b4d3121a3e8994b2d6f8968deba';
        $this->author_address = '0x24cA4dE04f3EC79296742139589b4f9A9892E1ec';

        parent::__construct();

        $this->displayName = $this->l('MigrationPro: WooCommerce To PrestaShop Migration Tool');
        $this->description = $this->l(
            'Migrate Products, Categories, Customers, Orders, Images, Features, Combinations, Attributes, Tax, Manufacturers.'
        );
        $this->confirmUninstall = $this->l('Dont do it until each customer login at least one time.');
        $this->ps_versions_compliancy = array('min' => '1.6', 'max' => _PS_VERSION_);
    }

    public function install()
    {
        // Prepare tab
        $tab = new Tab();
        $tab->active = 1;
        $tab->class_name = 'AdminWooMigrationPro';
        $tab->name = array();
        foreach (Language::getLanguages(true) as $lang) {
            $tab->name[$lang['id_lang']] = 'MigrationPro';
        }
        $tab->id_parent = -1;
        $tab->module = $this->name;

        include(dirname(__FILE__) . '/sql/install.php');

        Configuration::updateValue('woomigrationpro_module_path', $this->local_path);
        Configuration::updateValue('woomigrationpro_token_is_generated', '0');

        if (!$tab->add() ||
            !parent::install() ||
            !$this->registerHook('header') ||
            !$this->registerHook('backOfficeHeader') ||
            !$this->registerHook('actionBeforeAuthentication')
        ) {
            return false;
        }

        return true;
    }

    public function uninstall()
    {
        include(dirname(__FILE__) . '/sql/uninstall.php');

        Configuration::deleteByName('migrationpro_module_path');

        $id_tab = (int)Tab::getIdFromClassName('AdminWooMigrationPro');

        if ($id_tab) {
            $tab = new Tab($id_tab);
            $tab->delete();
        }

        if (!parent::uninstall()) {
            return false;
        }

        return true;
    }

    public function initWizard()
    {
        $this->wizard_steps = array(
            'name' => 'migrationpro_wizard',
            'steps' => array(
                array(
                    'title' => $this->l('Connect shop'),
                ),
                array(
                    'title' => $this->l('Map data'),
                ),
                array(
                    'title' => $this->l('Start migration'),
                )
            )
        );
    }

    // steps form fields
    public function renderStepOne()
    {
        $this->fields_form = array(
            'form' => array(
                'id_form' => 'step_migrationpro_connection',
                'legend' => array(
                    'title' => $this->l('Start New Migration'),
                    'icon' => 'icon-AdminTools'
                ),
                'input' => array(
                    array(
                        'type' => 'text',
                        'label' => $this->l('Source WooCommerce URL'),
                        'name' => 'source_shop_url',
                        'required' => true,
                        'placeholder' => 'https://old-woocommerce-url.com/',
                        'hint' => array(
                            $this->l(
                                'Enter a valid URL. e.g. http://old-woocommerce.com (http:// or https://)'
                            )
                        )
                    )
                )
            )
        );

        $fields_value = $this->getStepOneFieldsValues();

        return $this->renderGenericForm(array('form' => $this->fields_form), $fields_value);
    }

    public function renderStepTwo()
    {
        $mappings = WooMigrationProMapping::listMapping(true);
        if (self::isEmpty($mappings)) {
            return false;
        }
        // Shops mapping
        $multiShopsInputs = array();

        foreach ($mappings['multi_shops'] as $key => $val) {
            $multiShopsInputs[] = array(
                'type' => 'select',
                'label' => $val['source_name'],
                'name' => "map[multi_shops][$key]",
                'required' => true,
                'options' => array(
                    'query' => Shop::getShops(),
                    'id' => 'id_shop',
                    'name' => 'name'
                ),
//                'condition' => Shop::isFeatureActive(),
                'default_value' => Shop::getContextShopID(),
                'hint' => $this->l('Select the target shop that you want to migrate to.')
            );
        }

        $multiShops = array(
            'form' => array(
                'id_form' => 'step_migrationpro_configuration',
                'legend' => array(
                    'title' => $this->l('Shops'),
                    'icon' => 'icon-shopping-cart'
                ),
                'input' => $multiShopsInputs
            )
        );

        // Currencies Mapping
        $currenciesInputs = array();
        if (isset($mappings['currencies']) && self::isEmpty($mappings['currencies'])) {
            $mappings['currencies'][1] = array('source_name' => Configuration::get($this->name . '_default_currency'));
        }

        if (isset($mappings['currencies'])) {
            foreach ($mappings['currencies'] as $key => $val) {
                $currenciesInputs[] = array(
                    'type' => 'select',
                    'label' => $val['source_name'],
                    'hint' => $this->l(
                        'Select the target Cart currencies to properly match the source WooCommerce Store currencies. This is necessary to create currencies in the target PrestaShop Store.'
                    ),
                    'name' => "map[currencies][$key]",
                    'required' => true,
                    'options' => array(
                        'query' => Currency::getCurrencies(),
                        'id' => 'id_currency',
                        'name' => 'name'
                    ),
                    //                'default_value' => Currency::getCurrent()
                );
            }
            $currencies = array(
                'form' => array(
                    'id_form' => 'step_migrationpro_configuration',
                    'legend' => array(
                        'title' => $this->l('Currencies'),
                        'icon' => 'icon-money'
                    ),
                    'input' => $currenciesInputs
                )
            );
        }


        // Languages Mapping
        $languagesInputs = array();
        if (self::isEmpty($mappings['languages'])) {
            $mappings['languages'][1] = array('source_name' => Configuration::get($this->name . '_default_lang'));
        }
        foreach ($mappings['languages'] as $key => $val) {
            $languagesInputs[] = array(
                'type' => 'select',
                'label' => $val['source_name'],
                'hint' => $this->l(
                    'Select the target Cart languages to properly match the source WooCommerce Store languages. This is necessary to create languages in the target PrestaShop Store.'
                ),
                'name' => "map[languages][$key]",
                'required' => true,
                'options' => array(
                    'query' => array_merge(
                        array(array('id_lang' => 0, 'name' => 'none')),
                        Language::getLanguages()
                    ),
                    'id' => 'id_lang',
                    'name' => 'name'
                ),
            );
        }

        $languages = array(
            'form' => array(
                'id_form' => 'step_migrationpro_configuration',
                'legend' => array(
                    'title' => $this->l('Languages'),
                    'icon' => 'icon-AdminParentLocalization'
                ),
                'input' => $languagesInputs
            )
        );


        // Orders Status Mapping
        $ordersStatusInputs = array();

        foreach ($mappings['order_states'] as $key => $val) {
            $ordersStatusInputs[] = array(
                'type' => 'select',
                'label' => $val['source_name'],
                'hint' => $this->l(
                    'Select the target Cart order status to properly match the source WoCommerce Store order status. This is necessary to create order status in the target PrestaShop Store.'
                ),
                'name' => "map[order_states][$key]",
                'required' => true,
                'options' => array(
                    'query' => OrderState::getOrderStates($this->context->language->id),
                    'id' => 'id_order_state',
                    'name' => 'name'
                ),
            );
        }

        $ordersStatus = array(
            'form' => array(
                'id_form' => 'step_migrationpro_configuration',
                'legend' => array(
                    'title' => $this->l('Order status'),
                    'icon' => 'icon-time'
                ),
                'input' => $ordersStatusInputs
            )
        );

        //entities to migrate
        $entitiesToMigrate = array(
            array(
                'type' => 'switch',
                'label' => $this->l("All"),
                'desc' => $this->l('Select all options'),
                'hint' => $this->l('Select all boxes'),
                'name' => 'entities_select_all',
                'id' => 'entities_select_all',
                'is_bool' => true,
                'values' => array(
                    array(
                        'id' => 'active_on',
                        'value' => true,
                        'label' => $this->l('Enabled')
                    ),
                    array(
                        'id' => 'active_off',
                        'value' => false,
                        'label' => $this->l('Disabled')
                    )
                )

            ),
            array(
                'type' => 'switch',
                'label' => $this->l("Taxes"),
                'hint' => $this->l('Enable this option to migrate Taxes '),
                'name' => 'entities_taxes',
                'id' => 'entities_taxes',
                'is_bool' => true,
                'values' => array(
                    array(
                        'id' => 'active_on',
                        'value' => true,
                        'label' => $this->l('Enabled')
                    ),
                    array(
                        'id' => 'active_off',
                        'value' => false,
                        'label' => $this->l('Disabled')
                    )
                )

            ),
            array(
                'type' => 'switch',
                'label' => "Manufacturers",
                'name' => 'entities_manufacturers',
                'hint' => $this->l('Enable this option to migrate Manufacturers '),
                'id' => 'entities_manufacturers',
                'is_bool' => true,
//                 'desc' => $this->l('Enable this option to migrate Manufacturers '),
                'values' => array(
                    array(
                        'id' => 'active_on',
                        'value' => true,
                        'label' => $this->l('Enabled')
                    ),
                    array(
                        'id' => 'active_off',
                        'value' => false,
                        'label' => $this->l('Disabled')
                    )
                )

            ),
            array(
                'type' => 'switch',
                'label' => "Categories",
                'hint' => $this->l('Enable this option to migrate Categories '),
                'name' => 'entities_categories',
                'id' => 'entities_categories',
                'is_bool' => true,
                'values' => array(
                    array(
                        'id' => 'active_on',
                        'value' => true,
                        'label' => $this->l('Enabled')
                    ),
                    array(
                        'id' => 'active_off',
                        'value' => false,
                        'label' => $this->l('Disabled')
                    )
                )

            ),
            array(
                'type' => 'switch',
                'label' => "Products",
                'hint' => $this->l('Enable this option to migrate Products '),
                'name' => 'entities_products',
                'id' => 'entities_products',
                'is_bool' => true,
                'values' => array(
                    array(
                        'id' => 'active_on',
                        'value' => true,
                        'label' => $this->l('Enabled')
                    ),
                    array(
                        'id' => 'active_off',
                        'value' => false,
                        'label' => $this->l('Disabled')
                    )
                )

            ),
            array(
                'type' => 'switch',
                'label' => "Customers",
                'hint' => $this->l('Enable this option to migrate Customers '),
                'name' => 'entities_customers',
                'id' => 'entities_customers',
                'is_bool' => true,
                'values' => array(
                    array(
                        'id' => 'active_on',
                        'value' => true,
                        'label' => $this->l('Enabled')
                    ),
                    array(
                        'id' => 'active_off',
                        'value' => false,
                        'label' => $this->l('Disabled')
                    )
                )

            ),
            array(
                'type' => 'switch',
                'label' => "Orders",
                'hint' => $this->l('Enable this option to migrate Orders '),
                'name' => 'entities_orders',
                'id' => 'entities_orders',
                'is_bool' => true,
                'values' => array(
                    array(
                        'id' => 'active_on',
                        'value' => true,
                        'label' => $this->l('Enabled')
                    ),
                    array(
                        'id' => 'active_off',
                        'value' => false,
                        'label' => $this->l('Disabled')
                    )
                )

            )
        );

        $entitiesToMigrate = array(
            'form' => array(
                'id_form' => 'step_migrationpro_configuration',
                'legend' => array(
                    'title' => $this->l('Data to Migrate'),
                    'icon' => 'icon-AdminCatalog'
                ),
                'input' => $entitiesToMigrate
            )
        );

        // Additional Options

        $advancedOptionsArray = array(
            array(
                'type' => 'switch',
                'label' => $this->l("All"),
                'hint' => $this->l('Keep all data ID'),
                'name' => 'force_select_all',
                'id' => 'force_select_all',
                'is_bool' => true,
                'desc' => $this->l('Select all options'),
                'values' => array(
                    array(
                        'id' => 'active_on',
                        'value' => true,
                        'label' => $this->l('Enabled')
                    ),
                    array(
                        'id' => 'active_off',
                        'value' => false,
                        'label' => $this->l('Disabled')
                    )
                )
            ),
            array(
                'type' => 'switch',
                'label' => "Categories ID",
                'hint' => $this->l('If there is no specific problem with that, we recommend you to keep your source Categories Id.'),
                'name' => 'force_category_ids',
                'id' => 'force_category_ids',
                'is_bool' => true,
                'desc' => $this->l('Keep Categories ID'),
                'values' => array(
                    array(
                        'id' => 'active_on',
                        'value' => true,
                        'label' => $this->l('Enabled')
                    ),
                    array(
                        'id' => 'active_off',
                        'value' => false,
                        'label' => $this->l('Disabled')
                    )
                )
            ),
            array(
                'type' => 'switch',
                'label' => "Products ID",
                'hint' => $this->l('If there is no specific problem with that, we recommend you to keep your source Products Id.'),
                'name' => 'force_product_ids',
                'id' => 'force_product_ids',
                'is_bool' => true,
                'desc' => $this->l('Keep Products ID'),
                'values' => array(
                    array(
                        'id' => 'active_on',
                        'value' => true,
                        'label' => $this->l('Enabled')
                    ),
                    array(
                        'id' => 'active_off',
                        'value' => false,
                        'label' => $this->l('Disabled')
                    )
                )

            ),
            array(
                'type' => 'switch',
                'label' => "Customers ID",
                'hint' => $this->l('If there is no specific problem with that, we recommend you to keep your source Customers ID'),
                'name' => 'force_customer_ids',
                'id' => 'force_customer_ids',
                'is_bool' => true,
                'desc' => $this->l('Keep Customers ID'),
                'values' => array(
                    array(
                        'id' => 'active_on',
                        'value' => true,
                        'label' => $this->l('Enabled')
                    ),
                    array(
                        'id' => 'active_off',
                        'value' => false,
                        'label' => $this->l('Disabled')
                    )
                )

            ),
            array(
                'type' => 'switch',
                'label' => "Orders ID",
                'hint' => $this->l('If there is no specific problem with that, we recommend you to keep your source Orders ID'),
                'name' => 'force_order_ids',
                'id' => 'force_order_ids',
                'is_bool' => true,
                'desc' => $this->l('Keep Orders ID'),
                'values' => array(
                    array(
                        'id' => 'active_on',
                        'value' => true,
                        'label' => $this->l('Enabled')
                    ),
                    array(
                        'id' => 'active_off',
                        'value' => false,
                        'label' => $this->l('Disabled')
                    )
                )
            ),
        );

        $advancedOptions = array(
            'form' => array(
                'id_form' => 'step_migrationpro_configuration',
                'legend' => array(
                    'title' => $this->l('Keep source data ID'),
                    'icon' => 'icon-AdminTools'
                ),
                'input' => $advancedOptionsArray
            )
        );


        $additionalOptionInputs = array();

        $additionalOptionInputs[] = array(
            'type' => 'switch',
            'label' => $this->l("Clean Target"),
            'hint' => $this->l('Clean data type on target PrestaShop which you selected to migrate.'),
            'name' => 'clear_data',
            'id' => 'clear_data',
            'is_bool' => true,
            'desc' => $this->l('Clean target PrestaShop data before migration.'),
            'values' => array(
                array(
                    'id' => 'active_on',
                    'value' => true,
                    'label' => $this->l('Enabled')
                ),
                array(
                    'id' => 'active_off',
                    'value' => false,
                    'label' => $this->l('Disabled')
                )
            )

        );

        $additionalOptionInputs[] = array(
            'type' => 'switch',
            'label' => $this->l("Migrate recent data"),
            'hint' => $this->l('This feature migrates data that are not yet in the target PrestaShop'),
            'name' => 'migrate_recent_data',
            'id' => 'migrate_recent_data',
            'is_bool' => true,
            'desc' => $this->l('Migrate recent data only (will work only after a first migration)'),
            'values' => array(
                array(
                    'id' => 'active_on',
                    'value' => true,
                    'label' => $this->l('Enabled')
                ),
                array(
                    'id' => 'active_off',
                    'value' => false,
                    'label' => $this->l('Disabled')
                )
            )

        );

//        $additionalOptionInputs[] = array(
//            'type'    => 'switch',
//            'label'   => "Data Validation",
//            'hint'    => $this->l('If this feature is disabled, the module will only migrate the valid data to the target PrestaShop'),
//            'name'    => 'ps_validation_errors',
//            'id'      => 'ps_validation_errors',
//            'is_bool' => true,
//            'desc'    => $this->l('Before starting the migration, the PrestaShop Validator checks that the data is suitable for PrestaShop or not. If the data is not suitable, the PrestaShop Validator will show the error message and its exact location.'),
//            'values'  => array(
//                array(
//                    'id'    => 'active_on',
//                    'value' => true,
//                    'label' => $this->l('Enabled')
//                ),
//                array(
//                    'id'    => 'active_off',
//                    'value' => false,
//                    'label' => $this->l('Disabled')
//                )
//            )
//        );

        $additionalOptions = array(
            'form' => array(
                'id_form' => 'step_migrationpro_configuration',
                'legend' => array(
                    'title' => $this->l('Additional Options'),
                    'icon' => 'icon-AdminTools'
                ),
                'input' => $additionalOptionInputs
            )
        );

        $speedOptionsInputs = array();
        $speedOptionsInputs[] = array(
            'type' => 'text',
            'label' => $this->l('Select Migration Speed:'),
            'name' => "speed",
            'hint' => $this->l(
                'You can select your migration speed with this option. By default this options is set on normal, if you want to use this option, we recommend you to consider your server power.'
            ),
            'id' => 'input_speed_range_slider',
        );

        $speedOptions = array(
            'form' => array(
                'id_form' => 'step_migrationpro_configuration',
                'legend' => array(
                    'title' => $this->l('Migration Speed'),
                    'icon' => 'icon-AdminTools'
                ),
                'input' => $speedOptionsInputs
            )
        );

        $fields_value = $this->getStepTwoFieldsValues();
        $step_arry = array($multiShops, $languages, $ordersStatus, $entitiesToMigrate, $advancedOptions, $additionalOptions, $speedOptions);
        if (isset($currencies)) {
            $step_arry[] = $currencies;
        }
        return $this->renderGenericForm($step_arry, $fields_value);
    }

    public function renderStepThree()
    {
        if (count($lastExecutingProcesses = WooMigrationProProcess::getAll())) {
            $this->context->smarty->assign('processes', $lastExecutingProcesses);
        }

        $this->context->smarty->assign(
            array(
                'percent' => WooMigrationProProcess::calculateImportedDataPercent(),
            )
        );

        $output = $this->context->smarty->fetch($this->local_path . 'views/templates/admin/view.tpl');

        return $output;
    }

    // form fields values
    public function getStepOneFieldsValues()
    {
        return array(
            'source_shop_url' => Configuration::get($this->name . '_url')
        );
    }

    public function getStepTwoFieldsValues()
    {
        $mappings = WooMigrationProMapping::listMapping();

        $fieldValues = array(
            'entities_taxes' => 0,
            'entities_manufacturers' => 0,
            'entities_categories' => 0,
            'entities_products' => 0,
            'entities_customers' => 0,
            'entities_orders' => 0,
            'entities_select_all' => 0,
            'force_category_ids' => 0,
            'force_product_ids' => 0,
            'force_customer_ids' => 0,
            'clear_data' => 0,
            'migrate_recent_data' => 0,
            'force_order_ids' => 0,
            'force_manufacturer_ids' => 0,
            'force_select_all' => 0,
            'speed' => 'Normal'
        );
        if (!self::isEmpty($mappings)) {
            foreach ($mappings as $map) {
                $fieldValues['map[' . $map['type'] . '][' . $map['id_mapping'] . ']'] = $map['local_id'];
            }
        }

        return $fieldValues;
    }

    // form fields value validation

    // helper functions for form
    public function renderGenericForm($fields_form, $fields_value, $tpl_vars = array())
    {
        $helper = new HelperForm();
        $helper->show_toolbar = false;
        $helper->default_form_language = $this->context->language->id;
        $helper->allow_employee_form_lang = Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG', 0);
        $helper->tpl_vars = array_merge(
            array(
                'fields_value' => $fields_value,
                'languages' => $this->context->controller->getLanguages(),
                'id_language' => $this->context->language->id
            ),
            $tpl_vars
        );

        return $helper->generateForm($fields_form);
    }

    public function getFieldValue($key)
    {
        return Tools::getValue($key);
    }

    /**
     * Load the configuration form
     */
    public function getContent()
    {

        /**
         * If values have been submitted in the form, process.
         */
        if (((bool)Tools::isSubmit('submitMigrationproModule')) == true) {
            $this->postProcess();
        }

        $this->initWizard();
        $this->context->smarty->assign('module_dir', $this->_path);
        $howToIntroduction = $this->context->smarty->fetch($this->local_path . 'views/templates/admin/configure.tpl');

        $this->context->smarty->assign(
            array(
                'wizard_steps' => $this->wizard_steps,
                'validate_url' => $this->context->link->getAdminLink('AdminWooMigrationPro'),
                'warningLogPath' => $this->_path . 'classes/loggers/warning_logs.txt',
                'downloadLogText' => $this->l('Download log file'),
                'multistore_enable' => Shop::isFeatureActive(),
                'wizard_contents' => array(
                    'contents' => array(
                        0 => $howToIntroduction . $this->renderStepOne(),
                        1 => $this->renderStepTwo(),
                        2 => ''
                    )
                ),
                'labels' => array(
                    'next' => $this->l('Next'),
                    'previous' => $this->l(
                        'Previous'
                    ),
                    'finish' => $this->l('Migrate')
                )
            )
        );
        $output = '';
        $processObject = WooMigrationProProcess::getActiveProcessObject();
        if (Validate::isLoadedObject($processObject) && $lastExecutingProcesses = WooMigrationProProcess::getAll()) {
            if (count($lastExecutingProcesses = WooMigrationProProcess::getAll())) {
                $this->context->smarty->assign('processes', $lastExecutingProcesses);
            }
//            $this->context->smarty->assign('processes', $lastExecutingProcesses);
        }

        $this->context->smarty->assign(
            array(
                'percent' => WooMigrationProProcess::calculateImportedDataPercent(),
            )
        );

        $output .= $this->context->smarty->fetch($this->local_path . 'views/templates/admin/wizard.tpl');

        return $output;//.$this->renderForm();
    }

    /**
     * Create the form that will be displayed in the configuration of your module.
     */
    protected function renderForm()
    {
        $helper = new HelperForm();

        $helper->show_toolbar = false;
        $helper->table = $this->table;
        $helper->module = $this;
        $helper->default_form_language = $this->context->language->id;
        $helper->allow_employee_form_lang = Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG', 0);

        $helper->identifier = $this->identifier;
        $helper->submit_action = 'submitMigrationproModule';
        $helper->currentIndex = $this->context->link->getAdminLink('AdminModules', false)
            . '&configure=' . $this->name . '&tab_module=' . $this->tab . '&module_name=' . $this->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');

        $helper->tpl_vars = array(
            'fields_value' => $this->getConfigFormValues(), /* Add values for your inputs */
            'languages' => $this->context->controller->getLanguages(),
            'id_language' => $this->context->language->id,
        );

        return $helper->generateForm(array($this->getConfigForm()));
    }

    /**
     * Create the structure of your form.
     */
    protected function getConfigForm()
    {
        return array(
            'form' => array(
                'legend' => array(
                    'title' => $this->l('Settings'),
                    'icon' => 'icon-cogs',
                ),
                'input' => array(
                    array(
                        'type' => 'switch',
                        'label' => $this->l('Live mode'),
                        'name' => 'MIGRATIONPRO_LIVE_MODE',
                        'is_bool' => true,
                        'desc' => $this->l('Use this module in live mode'),
                        'values' => array(
                            array(
                                'id' => 'active_on',
                                'value' => true,
                                'label' => $this->l('Enabled')
                            ),
                            array(
                                'id' => 'active_off',
                                'value' => false,
                                'label' => $this->l('Disabled')
                            )
                        ),
                    ),
                    array(
                        'col' => 3,
                        'type' => 'text',
                        'prefix' => '<i class="icon icon-envelope"></i>',
                        'desc' => $this->l('Enter a valid email address'),
                        'name' => 'MIGRATIONPRO_ACCOUNT_EMAIL',
                        'label' => $this->l('Email'),
                    ),
                    array(
                        'type' => 'password',
                        'name' => 'MIGRATIONPRO_ACCOUNT_PASSWORD',
                        'label' => $this->l('Password'),
                    ),
                ),
                'submit' => array(
                    'title' => $this->l('Save'),
                ),
            ),
        );
    }

    /**
     * Set values for the inputs.
     */
    protected function getConfigFormValues()
    {
        return array(
            'MIGRATIONPRO_LIVE_MODE' => Configuration::get('MIGRATIONPRO_LIVE_MODE', true),
            'MIGRATIONPRO_ACCOUNT_EMAIL' => Configuration::get(
                'MIGRATIONPRO_ACCOUNT_EMAIL',
                'contact@prestashop.com'
            ),
            'MIGRATIONPRO_ACCOUNT_PASSWORD' => Configuration::get('MIGRATIONPRO_ACCOUNT_PASSWORD', null),
        );
    }

    /**
     * Save form data.
     */
    protected function postProcess()
    {
        $form_values = $this->getConfigFormValues();

        foreach (array_keys($form_values) as $key) {
            Configuration::updateValue($key, Tools::getValue($key));
        }
    }

    public function hookActionBeforeAuthentication()
    {
        $mail = Tools::getValue('email');
        $pass = Tools::getValue('password');

        if (version_compare(_PS_VERSION_, '1.7.0.0', '>=')) {
            $mail = Tools::getValue('email');
            $pass = Tools::getValue('password');
        } else {
            $mail = Tools::getValue('email');
            $pass = Tools::getValue('passwd');
        }
        if (Validate::isEmail($mail)) {
            $result = WooMigrationProWoo::getWooUser($mail);
            if (!self::isEmpty($result)) {
                $hashpass = $result[0]['passwd'];
                $id_customer = $result[0]['id_customer'];
                $wp_hasher = new WooPasswordEncrypt(8, true);
                if ($wp_hasher->checkPassword($pass, $hashpass)) {
                    $customer = new Customer($id_customer);
                    $customer->passwd = Tools::encrypt($pass);
                    if ($customer->save()) {
                        WooMigrationProWoo::deleteUserById($id_customer);
                    }
                }
            }
        }
    }

    /**
     * Add the CSS & JavaScript files you want to be loaded in the BO.
     */
    public function hookBackOfficeHeader()
    {
        if (Tools::getValue('module_name') == $this->name || Tools::getValue('configure') == $this->name) {
            $this->context->controller->addJquery();
            $this->context->controller->addJS($this->_path . '/views/js/migrationpro_wizard.js');
            $this->context->controller->addJS($this->_path . '/views/js/rangeslider/ion.rangeSlider.min.js');
            $this->context->controller->addJS($this->_path . '/views/js/smartWizardMigrationPro.js');
            $this->context->controller->addJqueryPlugin('typewatch');
            $this->context->controller->addCSS($this->_path . 'views/css/migrationpro.css');
            $this->context->controller->addCSS($this->_path . 'views/css/rangeslider/ion.rangeSlider.css');
            $this->context->controller->addCSS($this->_path . 'views/css/rangeslider/ion.rangeSlider.skinModern.css');
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
