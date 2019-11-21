<?php
/**
 * NOTICE OF LICENSE
 *
 * @author    202 ecommerce <tech@202-ecommerce.com>
 * @author    Mondial Relay
 * @copyright Copyright (c) Mondial Relay
 * @license http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

require_once _PS_MODULE_DIR_ . '/mondialrelay/vendor/autoload.php';
require_once _PS_MODULE_DIR_ . '/mondialrelay/classes/MondialrelayTools.php';
require_once _PS_MODULE_DIR_ . '/mondialrelay/classes/MondialrelayCarrierMethod.php';
require_once _PS_MODULE_DIR_ . '/mondialrelay/classes/MondialrelaySelectedRelay.php';

/*
 * We can't use "use" statements because PS 1.6 can't parse the module file if we do
 * But we still need them so Classlib's refresh script will copy the files
 * So HAXX
 * use MondialrelayClasslib\Module;
 * use MondialrelayClasslib\Extensions\ProcessLogger\ProcessLoggerExtension
 * use MondialrelayClasslib\Extensions\ProcessMonitor\ProcessMonitorExtension
 */

class Mondialrelay extends MondialrelayClasslib\Module
{
    
    /**
     * Configuration key; Webservice information; should be provided by Mondial Relay
     */
    const WEBSERVICE_ENSEIGNE = 'MONDIALRELAY_WEBSERVICE_ENSEIGNE';
    const WEBSERVICE_BRAND_CODE = 'MONDIALRELAY_WEBSERVICE_BRAND_CODE';
    const WEBSERVICE_KEY = 'MONDIALRELAY_WEBSERVICE_KEY';
    
    /** @var string Configuration key; the id of the language in
     * which to generate the labels
     */
    const LABEL_LANG = 'MONDIALRELAY_LABEL_LANG';
    
    /** @var string Configuration key; a coefficient to apply to product's weight
     * when trying to calculate an order weight from
     */
    const WEIGHT_COEFF = 'MONDIALRELAY_WEIGHT_COEFF';
    
    /** @var string Configuration key; wether to display the Mondial Relay widget
     * with a map
     */
    const DISPLAY_MAP = 'MONDIALRELAY_DISPLAY_MAP';
    
    /** @var string Configuration key; Orders with this state will be available
     * for label generation
     */
    const OS_DISPLAY_LABEL = 'MONDIALRELAY_OS_DISPLAY_LABEL';
    
    /** @var string Configuration key; Orders will switch to this state when a
     * label has been generated
     */
    const OS_LABEL_GENERATED = 'MONDIALRELAY_OS_LABEL_GENERATED';
    
    /** @var string Configuration key; Orders will switch to this state once they
     * have been reported as "delivered" by Mondial Relay
     */
    const OS_ORDER_DELIVERED = 'MONDIALRELAY_OS_ORDER_DELIVERED';
    
    /** @var string Configuration key; the secure key for the deprecated cron
     * task
     * @see cron.php
     * @see MondialrelayOrdersStatusUpdateModuleFrontController::checkAccess()
     */
    const DEPRECATED_SECURE_KEY = 'MONDIAL_RELAY_SECURE_KEY';
    
    /** @var int The minimum package weight supported by the webservice */
    const MINIMUM_PACKAGE_WEIGHT = 15;
    
    /** @var string The only collection mode supported by the module */
    const COLLECTION_MODE = 'CCC';
    
    /** Mondial Relay's domain; needed for tracking URLs and label downloads */
    const URL_DOMAIN = 'http://www.mondialrelay.com';
    
    public $extensions = array(
        MondialrelayClasslib\Extensions\ProcessLogger\ProcessLoggerExtension::class,
        MondialrelayClasslib\Extensions\ProcessMonitor\ProcessMonitorExtension::class
    );

    /** @var array $moduleAdminControllers
     */
    public $moduleAdminControllers = array(
        array(
            'name' => array(
                'en' => 'Mondial Relay',
                'fr' => 'Mondial Relay'
            ),
            'class_name' => 'AdminMondialrelaySettings',
            'parent_class_name' => 'SELL',
            'visible' => true,
            'icon' => 'local_shipping',
        ),
        array(
            'name' => array(
                'en' => 'Labels Generation',
                'fr' => 'Générer des étiquettes'
            ),
            'class_name' => 'AdminMondialrelayLabelsGeneration',
            'parent_class_name' => 'AdminMondialrelaySettings',
            'visible' => true,
        ),
        array(
            'name' => array(
                'en' => 'Labels History',
                'fr' => 'Historique des étiquettes'
            ),
            'class_name' => 'AdminMondialrelayLabelsHistory',
            'parent_class_name' => 'AdminMondialrelaySettings',
            'visible' => true,
        ),
        array(
            'name' => array(
                'en' => 'Account Settings',
                'fr' => 'Paramètres du compte'
            ),
            'class_name' => 'AdminMondialrelayAccountSettings',
            'parent_class_name' => 'AdminMondialrelaySettings',
            'visible' => true,
        ),
        array(
            'name' => array(
                'en' => 'Advanced Settings',
                'fr' => 'Paramètres avancés'
            ),
            'class_name' => 'AdminMondialrelayAdvancedSettings',
            'parent_class_name' => 'AdminMondialrelaySettings',
            'visible' => true,
        ),
        array(
            'name' => array(
                'en' => 'Carriers Settings',
                'fr' => 'Paramètres des transporteurs'
            ),
            'class_name' => 'AdminMondialrelayCarriersSettings',
            'parent_class_name' => 'AdminMondialrelaySettings',
            'visible' => true,
        ),
        array(
            'name' => array(
                'en' => 'Activity Logs',
                'fr' => 'Logs d\'Activité'
            ),
            'class_name' => 'AdminMondialrelayProcessLogger',
            'parent_class_name' => 'AdminMondialrelaySettings',
            'visible' => true,
        ),
        array(
            'name' => array(
                'en' => 'Help',
                'fr' => 'Aide'
            ),
            'class_name' => 'AdminMondialrelayHelp',
            'parent_class_name' => 'AdminMondialrelaySettings',
            'visible' => true,
        ),
        // This controller doesn't have a link in the menu; it's only accessible
        // through order pages
        array(
            'name' => array(
                'en' => "Edit Weight & Insurance & Point Relais",
                'fr' => "Modifier le poids & l'assurance & le Point Relais"
            ),
            'class_name' => 'AdminMondialrelaySelectedRelay',
            'parent_class_name' => 'AdminMondialrelaySettings',
            'visible' => false,
        ),
        // This controller is installed with ProcessMonitor by default; set it
        // as "invisible".
        array(
            'name' => array(
                'en' => 'Scheduled Tasks',
                'fr' => 'Tâches planifiées',
            ),
            'class_name' => 'AdminMondialrelayProcessMonitor',
            'parent_class_name' => 'AdminMondialrelaySettings',
            'visible' => false,
        ),
    );
    
    public $objectModels = array(MondialrelayCarrierMethod::class, MondialrelaySelectedRelay::class);
    
    public $hooks = array(
        'actionAdminCarriersControllerDeleteAfter',
        'actionCarrierUpdate',
        'actionFrontControllerSetMedia',
        'displayBeforeCarrier',
        'displayCarrierList',
        'displayAfterCarrier',
        'actionCarrierProcess',
        'actionValidateStepComplete',
        'actionValidateOrder',
        'actionBeforeAjaxDieOrderOpcControllerinit',
        'actionAdminControllerSetMedia',
        'actionObjectAddressUpdateBefore',
        'actionObjectAddressDeleteBefore',
    );

    /**
     * Used to avoid spam or unauthorized execution of cron controller
     * @var string Unique token depend on _COOKIE_KEY_ which is unique to this website
     * @see Tools::encrypt()
     */
    public $secure_key;
    
    /**
     * List of cron tasks indexed by controller name
     * Title value must be an array indexed by iso language (en is required)
     * Frequency value can be hourly, daily, weekly, monthly
     *
     * @var array
      */
    public $cronTasks = array();
    
    public function __construct()
    {
        $this->name              = 'mondialrelay';
        $this->tab               = 'shipping_logistics';
        $this->version           = '3.0.2';
        $this->installed_version = '';
        $this->bootstrap         = true;
        $this->module_key        = 'd7903ef40ee11ecfc77bf5ddf85a5c5b';
        $this->ps_versions_compliancy = array('min' => '1.6.1', 'max' => _PS_VERSION_);
        $this->author            = '202 ecommerce';

        parent::__construct();

        $this->displayName = $this->l('Mondial Relay');
        $this->description = $this->l('Deliver in Points Relais');
        $this->secure_key = Tools::encrypt($this->name);
        
        // Tab install is skipped on PS 1.6, as "SELL" doesn't exist yet
        // We're unsure wether this will only be used during install, so it's in
        // the constructor
        if (version_compare(_PS_VERSION_, '1.7', '<')) {
            unset($this->moduleAdminControllers[0]['parent_class_name']);
        }
        
        $this->cronTasks = array(
            'ordersStatusUpdate' => array(
                'name'      => 'mondialrelay:orders_update',
                'title'     => array(
                    'en' => 'Orders status update',
                    'fr' => 'Mise à jour des statuts de commande',
                    $this->context->language->iso_code => $this->l('Orders status update'),
                ),
                'frequency' => $this->l('6 hours (recommended)'),
            )
        );
    }

    /**
     * Make our checks, and install with classlib
     */
    public function install()
    {
        if (!MondialrelayTools::checkDependencies()) {
            $this->_errors[] = Tools::displayError(
                $this->l('SOAP and cURL should be installed on your server.')
            );
            return false;
        }
        
        if (!parent::install()) {
            return false;
        }
        
        // Set default configuration values
        $this->setConfigurationDefault(self::DISPLAY_MAP, false);
        $this->setConfigurationDefault(self::OS_DISPLAY_LABEL, Configuration::get('PS_OS_PREPARATION'));
        $this->setConfigurationDefault(self::OS_LABEL_GENERATED, Configuration::get('PS_OS_SHIPPING'));
        $this->setConfigurationDefault(self::OS_ORDER_DELIVERED, Configuration::get('PS_OS_DELIVERED'));
        $this->setConfigurationDefault(self::LABEL_LANG, 'FR');

        return true;
    }

    /**
     * Uninstall with classlib
     */
    public function uninstall()
    {
        // Set all linked native carriers as deleted
        $carriers = MondialrelayCarrierMethod::getAllPrestashopCarriers(null);
        if (!empty($carriers)) {
            foreach ($carriers as $carrier) {
                $carrier->deleted = true;
                $carrier->save();
            }
        }
        
        // Don't uninstall any table; we want to keep everything in DB for
        // history purposes
        if (!\Module::uninstall()) {
            return false;
        }
        
        $installer = new MondialrelayClasslib\Install\ModuleInstaller($this);
        return $installer->uninstallConfiguration() && $installer->uninstallModuleAdminControllers();
    }
    
    /**
     * @inheritdoc
     */
    public function disable($force_all = false)
    {
        if (!parent::disable($force_all)) {
            return false;
        }
        
        // We need to disable the carriers on the shops where the module was
        // disabled
        if ($force_all) {
            $id_shop_list = Shop::getShops(false, null, true);
        } else {
            $id_shop_list = Shop::getContextListShopID();
        }
        
        return MondialrelayCarrierMethod::removeNativeCarriersFromShops($id_shop_list);
    }
    
    /**
     * Redirect to our AdminControllers
     */
    public function getContent()
    {
        if (!MondialrelayTools::checkWebserviceConfiguration()) {
            Tools::redirectAdmin($this->context->link->getAdminLink('AdminMondialrelayAccountSettings'));
        }
        Tools::redirectAdmin($this->context->link->getAdminLink('AdminMondialrelayLabelsGeneration'));
    }
    
    /**
     * Executed after a carrier deletion.
     * If a carrier was deleted, we must also set our Mondial Relay carrier as
     * deleted. We also need this to redirect the user if the deletion happened
     * from the module's controller.
     *
     * @param array $params
     * @return void
     */
    public function hookActionAdminCarriersControllerDeleteAfter($params)
    {
        if (!$params['return']) {
            return;
        }
        
        $carrierMethod = MondialrelayCarrierMethod::getFromNativeCarrierId(Tools::getValue('id_carrier'));
        if (!$carrierMethod) {
            return;
        }
        
        $carrierMethod->is_deleted = true;
        $carrierMethod->save();
        
        if (Tools::getValue('action_origin') == 'AdminMondialrelayCarriersSettings') {
            $this->context->controller->setRedirectAfter(
                $this->context->link->getAdminLink('AdminMondialrelayCarriersSettings')
                . "&conf=1"
            );
        }
    }
    
    /**
     * Executed after a carrier update. In fact, carriers are not updated; a copy
     * is created and the "original" carrier is set as "deleted".
     * This means we have to update our carrier methods with the new id_carrier.
     *
     * @param array $params
     */
    public function hookActionCarrierUpdate($params)
    {
        $id_oldCarrier  = $params['id_carrier'];
        $newCarrier = $params['carrier'];
        
        if ($id_oldCarrier == $newCarrier->id) {
            return;
        }
        
        // Get the existing carrier method
        $oldMRCarrierMethod = MondialrelayCarrierMethod::getFromNativeCarrierId($id_oldCarrier);
        if (!$oldMRCarrierMethod) {
            return;
        }
        
        // Duplicate it, and update id_carrier
        $newMRCarrierMethod = $oldMRCarrierMethod->duplicateObject();
        $newMRCarrierMethod->id_carrier = $newCarrier->id;
        $newMRCarrierMethod->save();
        
        // Set old carrier method as deleted
        $oldMRCarrierMethod->is_deleted = true;
        $oldMRCarrierMethod->save();
    }
    
    /**
     * RELAY SELECTION DISPLAY
     */
    
    /**
     * Includes javascript used "globally" in the checkout process; this allows
     * to bind events to the form even if our carrier wasn't selected yet.
     *
     * This hook is used directly on PS 17 and on PS 16 when using OPC. But we
     * have to call it from hookDisplayBeforeCarrier in PS 16 when using the
     * 5-steps checkout, because we can't properly detect on which step of the
     * order we are at this point of the process; if the carrier has an error, a
     * STEP_DELIVERY will be displayed, but we'll still detect a STEP_PAYMENT
     * when this hook is triggered.
     *
     * @see self::hookDisplayBeforeCarrier()
     * @param type $params
     * @return string
     */
    public function hookActionFrontControllerSetMedia($params)
    {
        // Checks if we should display an address error
        $this->setAddressError();
        
        // Check if we're in the checkout process
        $controller = $this->context->controller->php_self;
        if (!in_array($controller, array('order', 'order-opc'))) {
            return;
        }
        
        // PS 1.7 always uses OPC, so no need to check wether to include our
        // files.
        // However, PS 1.6 might be using 5-steps checkout, so we would need to
        // check we're on the STEP_DELIVERY.
        // BUT, this hook is called by PS *before* validating the carrier; so
        // at this point, we might detect a STEP_PAYMENT, but if the carrier has
        // an error, PS will switch to a STEP_DELIVERY after processing the
        // carrier
        // @see hookDisplayBeforeCarrier
        if (version_compare(_PS_VERSION_, '1.7', '<')
            && $controller == 'order'
            && empty($params['direct_call'])
        ) {
            return;
        }
        
        // Add CSS
        if (version_compare(_PS_VERSION_, '1.7', '>=')) {
            $this->context->controller->registerStylesheet('modules-mondialrelay_checkout', 'modules/'.$this->name.'/views/css/front/checkout.css');
        } else {
            $this->context->controller->addCSS('modules/'.$this->name.'/views/css/front/checkout.css');
        }
        
        // Add global values
        $noSelectionError = $this->l('Please select a Point Relais.');
        $saveRelayError = $this->l('An unknown error has occurred; your selected Point Relais could not be saved.');
        
        if (version_compare(_PS_VERSION_, '1.7', '<')) {
            // Strings passed to JS shuld be escaped on PS1.6; single quotes
            // wille break the script
            $noSelectionError = addslashes($noSelectionError);
            $saveRelayError = addslashes($saveRelayError);
        }
        
        Media::addJsDef(array(
            // Only these are really global; the address might change during the
            // OPC process, and so the carriers might too. @see hookDisplayCarrierList
            'MONDIALRELAY_ENSEIGNE' => Configuration::get(self::WEBSERVICE_ENSEIGNE),
            'MONDIALRELAY_DISPLAY_MAP' => Configuration::get(self::DISPLAY_MAP),
            'MONDIALRELAY_NO_SELECTION_ERROR' => $noSelectionError,
            'MONDIALRELAY_SAVE_RELAY_ERROR' => $saveRelayError,
            'MONDIALRELAY_AJAX_CHECKOUT_URL' => $this->context->link->getModuleLink('mondialrelay', 'ajaxCheckout', array(), true),
            // These values aren't actually global; but we'll set them anyway to avoid JS errors
            'MONDIALRELAY_NATIVE_RELAY_CARRIERS_IDS' => array(),
            'MONDIALRELAY_CARRIER_METHODS' => array(),
            'MONDIALRELAY_SELECTED_RELAY_IDENTIFIER' => null,
            'MONDIALRELAY_COUNTRY_ISO' => '',
            'MONDIALRELAY_POSTCODE' => '',
        ));

        // Add javascript for Prestashop
        if (version_compare(_PS_VERSION_, '1.7', '>=')) {
            $this->context->controller->registerJavascript('modules-mondialrelay_checkout', 'modules/'.$this->name.'/views/js/front/checkout/checkout-17.js');
        } elseif ($controller == 'order') {
            $this->context->controller->addJS($this->getPathUri() . 'views/js/front/checkout/checkout-16-5steps.js');
        } elseif ($controller == 'order-opc') {
            $this->context->controller->addJS($this->getPathUri() . 'views/js/front/checkout/checkout-16-opc.js');
        }
    }
    
    /**
     * Used on PS 16 with 5-steps checkout.
     *
     * @see self::hookActionFrontControllerSetMedia()
     * @param array $params
     * @return void
     */
    public function hookDisplayBeforeCarrier($params)
    {
        $controller = $this->context->controller->php_self;
        if (version_compare(_PS_VERSION_, '1.7', '>=') || $controller == 'order-opc') {
            return;
        }
        $this->hookActionFrontControllerSetMedia(array('direct_call' => true));
    }
    
    /**
     * Used to display the widget area on PS 16; triggered each time the
     * delivery option changes when using 5-steps checkout, and also when the
     * address changes when using OPC.
     *
     * This is where we'll set up the more "specific" JS variables.
     *
     * Never triggered natively in PS17;
     * @see self::hookDisplayAfterCarrier()
     *
     * @param array $params
     * @return string
     */
    public function hookDisplayCarrierList($params)
    {
        $address = new Address($this->context->cart->id_address_delivery);
        if (!Validate::isLoadedObject($address)) {
            return;
        }

        $deliveryOptionsList = $this->context->cart->getDeliveryOptionList(null, true);
        // Get selected relay, if any
        $selectedRelay = MondialrelaySelectedRelay::getFromIdCart($this->context->cart->id);
        // If Mondial Relay isn't set up...
        $isModuleConfigured = MondialRelayTools::checkDependencies() && MondialRelayTools::checkWebserviceConfiguration();
        //get current carrier method
        $carrierMethod = MondialrelayCarrierMethod::getFromNativeCarrierId($this->context->cart->id_carrier);

        /* In some rare cases with slow connection and multi switch between we arrive here
         * with id_address_delivery which is not present in deliveryOptions.
         * So first we check that MR module is available, it's probably only in PS 1.7.
         * After we check 2 possibilities. If probably it was PR choosed before this error,
         * we update missing information in cart_product, forcing using MR carrier with PR.
         * IF cart carrier is not one with PR (MR or not), we force using previous selected address from cookies.
         * After update cart session updateCartChecksum or we can't pass to payment step
        */
        if ($isModuleConfigured
            && version_compare(_PS_VERSION_, '1.7', '>=')
            && !in_array($address->id, array_keys($deliveryOptionsList))
        ) {
            if (Validate::isLoadedObject($carrierMethod)
                && Validate::isLoadedObject($selectedRelay)
                && $address->deleted != 1
                && $selectedRelay->id_address_delivery == $address->id
            ) {
                $sql = 'UPDATE `' . _DB_PREFIX_ . 'cart_product`
                    SET `id_address_delivery` = ' . (int) $address->id . '
                    WHERE  `id_cart` = ' . (int) $this->context->cart->id;
                Db::getInstance()->execute($sql);

                $sql = 'UPDATE `' . _DB_PREFIX_ . 'customization`
                    SET `id_address_delivery` = ' . (int) $address->id . '
                    WHERE  `id_cart` = ' . (int) $this->context->cart->id;
                Db::getInstance()->execute($sql);
            } elseif (!$carrierMethod
                || (Validate::isLoadedObject($carrierMethod) && !$carrierMethod->needsRelay())
                || $address->deleted == 1
            ) {
                // If we're not using an MR carrier, or an MR carrier that doesn't
                // require a PR, or an address that was deleted (most likely by
                // us, but selected anyway due to AJAX requests concurrency)
                // We want to use a valid address; any will do but we'd prefer
                // the one we saved
                $old_address = new Address($this->context->cookie->mondialrelay_id_original_delivery_address);
                if (!Validate::isLoadedObject($old_address) || $old_address->deleted == 1) {
                    $old_address = new Address(Address::getFirstCustomerAddressId($this->context->cart->id_customer));
                    $this->context->cookie->mondialrelay_id_original_delivery_address = $old_address->id;
                    $this->context->cookie->write();
                }
                
                if (Validate::isLoadedObject($old_address) && $old_address->deleted != 1) {
                    $this->context->cart->id_address_delivery = $old_address->id;
                    $this->context->cart->update();
                    $sql = 'UPDATE `' . _DB_PREFIX_ . 'cart_product`
                    SET `id_address_delivery` = ' . (int) $old_address->id . '
                    WHERE  `id_cart` = ' . (int) $this->context->cart->id;
                    Db::getInstance()->execute($sql);
                    $sql = 'UPDATE `' . _DB_PREFIX_ . 'customization`
                    SET `id_address_delivery` = ' . (int) $old_address->id . '
                    WHERE  `id_cart` = ' . (int) $this->context->cart->id;
                    Db::getInstance()->execute($sql);
                }
            }
            // Create the handler
            $handler = new MondialrelayClasslib\Actions\ActionsHandler();
            // Set input data
            $handler->setConveyor(array(
                // We need to pass the whole cart, otherwise our modifications
                // may be overwritten if PS saves the cart during its process
                'cart' => $this->context->cart,
            ));
            // Set actions to execute
            $actions = array('updateCartChecksum');
            call_user_func_array(array($handler, 'addActions'), $actions);
            // Process actions chain
            $handler->process('SelectRelay');
        }

        //reset address in case id_address_delivery is changed
        $address = new Address($this->context->cart->id_address_delivery);
        //reset deliveryOptionList because we probably updated manually delivery address
        $deliveryOptionsList = $this->context->cart->getDeliveryOptionList(null, true);
        
        if (empty($deliveryOptionsList)) {
            return;
        }

        $carriersIds = array_map(
            function ($v) {
                return str_replace(',', '', $v);
            },
            array_keys($deliveryOptionsList[$address->id])
        );
        
        // Find Mondial Relay carriers requiring a relay selection among the
        // available carriers
        $mondialRelayNativeCarriersIds = MondialrelayCarrierMethod::findMondialrelayCarrierIds($carriersIds, true);
        if (!$mondialRelayNativeCarriersIds) {
            return '';
        }
        
        // Get all associated carrier methods
        $mondialRelayCarrierMethods = array();
        foreach ($mondialRelayNativeCarriersIds as $id_carrier) {
            $carrierMethod = MondialrelayCarrierMethod::getFromNativeCarrierId($id_carrier);
            $mondialRelayCarrierMethods[$id_carrier] = $carrierMethod->getFields();
        }
        
        // Get selected relay, if any
        $selectedRelay = MondialrelaySelectedRelay::getFromIdCart($this->context->cart->id);
        // If Mondial Relay isn't set up...
        $isModuleConfigured = MondialRelayTools::checkDependencies() && MondialRelayTools::checkWebserviceConfiguration();

        // We have to do this manually, as the header has already been rendered,
        // and if called from an AJAX request, the context may have changed.
        $js_def = array(
            'MONDIALRELAY_NATIVE_RELAY_CARRIERS_IDS' => $mondialRelayNativeCarriersIds,
            'MONDIALRELAY_CARRIER_METHODS' => $mondialRelayCarrierMethods,
            'MONDIALRELAY_SELECTED_RELAY_IDENTIFIER' => Validate::isLoadedObject($selectedRelay) ?
                $selectedRelay->getFullRelayIdentifier() : null,
            'MONDIALRELAY_COUNTRY_ISO' => Country::getIsoById($address->id_country),
            'MONDIALRELAY_POSTCODE' => $address->postcode,
            'MONDIALRELAY_BAD_CONFIGURATION' => !$isModuleConfigured,
        );
        
        // Multi-shop / multi-theme might not work properly when using
        // the basic "$context->smarty->createTemplate($tpl_name)" syntax, as
        // the template's compile_id will be the same for every shop / theme
        // See https://github.com/PrestaShop/PrestaShop/pull/13804
        $scope = $this->context->smarty->createData($this->context->smarty);
        $scope->assign(array(
            'fromAjax' => $this->context->controller->ajax,
            'js_def' => $js_def,
            'js_inclusion_template' => _PS_ALL_THEMES_DIR_ . 'javascript.tpl',
            'isModuleConfigured' => $isModuleConfigured,
            'selectedRelay' => Validate::isLoadedObject($selectedRelay) ? $selectedRelay : false,
            'module_url' => $this->getPathUri(),
        ));
        
        if (isset($this->context->shop->theme)) {
            // PS17
            $themeName = $this->context->shop->theme->getName();
        } else {
            // PS16
            $themeName = $this->context->shop->theme_name;
        }

        return $this->context->smarty->createTemplate(
            $this->getTemplatePath('checkout/widget-area.tpl'),
            $scope,
            $themeName
        )->fetch();
    }

    /**
     * Used to display the widget area on PS 17; triggered each time the
     * delivery option changes, unless stopped (see checkout-17.js)
     *
     * @param array $params
     * @return string
     */
    public function hookDisplayAfterCarrier($params)
    {
        return $this->hookDisplayCarrierList($params);
    }
    
    /**
     * We want prevent the user from modifying an address if it's a relay
     * address
     * @see self::setAddressError()
     * @param type $params
     */
    public function hookActionObjectAddressUpdateBefore($params)
    {
        $controller_redirect = !empty($this->context->controller->php_self) ?
            $this->context->controller->php_self
            : Tools::getValue('controller');
        if ($controller_redirect == 'address') {
            $controller_redirect = 'addresses';
        }
        
        $this->setAddressError($controller_redirect);
    }
    
    /**
     * We want prevent the user from deleting an address if it's a relay
     * address
     * @see self::setAddressError()
     * @param type $params
     */
    public function hookActionObjectAddressDeleteBefore($params)
    {
        $controller_redirect = !empty($this->context->controller->php_self) ?
            $this->context->controller->php_self
            : Tools::getValue('controller');
        if ($controller_redirect == 'address') {
            $controller_redirect = 'addresses';
        }
        
        $this->setAddressError($controller_redirect);
    }
    
    /**
     * RELAY VALIDATION
     *
     * The actual relay save is done in the ajaxCheckout front controller.
     *
     * See hookActionCarrierProcess() for PS 16 5-steps checkout validation.
     *
     * See hookActionBeforeAjaxDieOrderOpcControllerinit() for PS 16 OPC
     * checkout validation.
     *
     * See hookActionValidateStepComplete() for PS 1.7.2+.
     *
     * hookActionCarrierProcess() is also used for some sanity checks on the
     * cart.
     *
     * There's no way to properly implement back-end validation for
     * PS 1.7.0-1.7.2, we lack usable hooks.
     */
    
    /**
     * Cart sanity preservation.
     *
     * Carrier selection validation for PS 16 when using 5-steps checkout.
     *
     * This hook is also triggered when using OPC (on PS 16 and 17), but we
     * can't display errors properly nor (un)validate the carrier selection step
     *
     * @see hookActionBeforeAjaxDieOrderOpcControllerinit() for PS 16 with OPC
     * @see hookActionValidateStepComplete for PS 17
     *
     * @param array $params
     */
    public function hookActionCarrierProcess($params)
    {
        $cart = $this->context->cart;

        // If Mondial Relay isn't set up...
        if (!MondialRelayTools::checkDependencies() || !MondialRelayTools::checkWebserviceConfiguration()) {
            $this->context->controller->errors[] = $this->l('This carrier has not been configured yet; please contact the merchant.');
            return false;
        }
        
        $selectedRelay = MondialrelaySelectedRelay::getFromIdCart($cart->id);
        $delivery_option = Tools::getValue('delivery_option');
        
        // Create the handler
        $handler = new MondialrelayClasslib\Actions\ActionsHandler();
        
        // Set input data
        $handler->setConveyor(array(
            // We need to pass the whole cart, otherwise our modifications
            // may be overwritten if PS saves the cart during its process
            'cart' => $cart,
            'selectedRelay' => $selectedRelay,
            'deliveryOption' => $delivery_option,
            'id_original_delivery_address' => $this->context->cookie->mondialrelay_id_original_delivery_address,
        ));
        
        // Set actions to execute
        $actions = array('syncCartFromDeliveryOption');
        
        // On PS 17, we can't modify the cart without resetting it's checksum,
        // or the checkout process will start over (and possibly bug)
        if (version_compare(_PS_VERSION_, '1.7', '>=')) {
            $actions[] = 'updateCartChecksum';
        }
        call_user_func_array(array($handler, 'addActions'), $actions);

        // Process actions chain
        $handler->process('SelectRelay');

        // If we're not using a Mondial Relay carrier...
        $carrierMethod = MondialrelayCarrierMethod::getFromNativeCarrierId($cart->id_carrier);
        if (!Validate::isLoadedObject($carrierMethod)) {
            return true;
        }
        
        // If we don't need a relay...
        if (!$carrierMethod->needsRelay()) {
            // We still need to save the selection
            $selectedRelay->id_address_delivery = $cart->id_address_delivery;
            $selectedRelay->id_customer = $cart->id_customer;
            $selectedRelay->id_mondialrelay_carrier_method = $carrierMethod->id;
            $selectedRelay->id_cart = $cart->id;
            $selectedRelay->insurance_level = $carrierMethod->insurance_level;
            // Remove relay data
            $selectedRelay->selected_relay_num = null;
            $selectedRelay->selected_relay_adr1 = null;
            $selectedRelay->selected_relay_adr2 = null;
            $selectedRelay->selected_relay_adr3 = null;
            $selectedRelay->selected_relay_adr4 = null;
            $selectedRelay->selected_relay_postcode = null;
            $selectedRelay->selected_relay_city = null;
            $selectedRelay->selected_relay_country_iso = null;
            $selectedRelay->save();
        } else if ($selectedRelay->id_mondialrelay_carrier_method && $carrierMethod->id != $selectedRelay->id_mondialrelay_carrier_method) {
            // If we changed carrier method, make sure we're still using the
            // same delivery_mode
            $oldCarrierMethod = new MondialrelayCarrierMethod($selectedRelay->id_mondialrelay_carrier_method);
            if ($oldCarrierMethod->delivery_mode != $carrierMethod->delivery_mode) {
                // If we changed delivery_mode, then we still need a relay
                // Problem is, the currently selected relay may not be available
                // for the new delivery_mode
                $selectedRelay->selected_relay_num = null;
                $selectedRelay->selected_relay_adr1 = null;
                $selectedRelay->selected_relay_adr2 = null;
                $selectedRelay->selected_relay_adr3 = null;
                $selectedRelay->selected_relay_adr4 = null;
                $selectedRelay->selected_relay_postcode = null;
                $selectedRelay->selected_relay_city = null;
                $selectedRelay->selected_relay_country_iso = null;
                $selectedRelay->save();
            }
        }
        
        // We won't use this hook for validation when on PS 17 or on PS 16 with
        // OPC, as it doesn't allow a clean error display.
        $controller = $this->context->controller->php_self;
        if (version_compare(_PS_VERSION_, '1.7', '>=') || $controller == 'order-opc') {
            return true;
        }
        
        // Check if we indeed have a selected relay...
        if ($carrierMethod->needsRelay()
            && (!Validate::isLoadedObject($selectedRelay) || !$selectedRelay->selected_relay_num)
        ) {
            $this->context->controller->errors[] = $this->l('Please select a Point Relais.');
            return false;
        }
        
        return true;
    }
    
    /**
     * This manages the order process when using OPC on PS 16. It's not pretty,
     * but it's still the cleanest way I could think of... This will ensure that
     * payment methods will never be displayed if a Mondial Relay carrier is
     * used unless the user has selected a relay.
     *
     * It is triggered by AJAX calls to the OPC controller; our custom JS sends
     * an AJAX request right when landing on the page (see checkout-16-opc.js).
     *
     * There's no way to block the payment methods when not using AJAX calls...
     *
     * @param type $params
     * @return boolean
     */
    public function hookActionBeforeAjaxDieOrderOpcControllerinit($params)
    {
        // If we're not using a Mondial Relay carrier...
        $carrierMethod = MondialrelayCarrierMethod::getFromNativeCarrierId($this->context->cart->id_carrier);
        if (!Validate::isLoadedObject($carrierMethod) || !$carrierMethod->needsRelay()) {
            return true;
        }
        $content = Tools::jsonDecode($params['value']);
        
        // If Mondial Relay isn't set up...
        if (!MondialRelayTools::checkDependencies() || !MondialRelayTools::checkWebserviceConfiguration()) {
            $content->HOOK_PAYMENT = $this->displayError($this->l('This carrier has not been configured yet; please contact the merchant.'));
            die(Tools::jsonEncode($content));
        }
        
        // If we have a selected relay...
        $selectedRelay = MondialrelaySelectedRelay::getFromIdCart($this->context->cart->id);
        if (Validate::isLoadedObject($selectedRelay) && $selectedRelay->selected_relay_num) {
            return true;
        }
        
        // Remove payment options, display an error, and die
        $content->HOOK_PAYMENT = $this->displayError($this->l('Please select a Point Relais.'));
        die(Tools::jsonEncode($content));
    }
    
    /**
     * Carrier selection validation for PS 1.7.2+
     *
     * The module doesn't support 1.7.0-1.7.2, and those PS version don't
     * feature this hook. This means that relay validation depends entirely on
     * the JS in FO.
     * We're using this hook because actionCarrierProcess doesn't allow a clean
     * validation and errors display.
     * In case on js error, we add one more check here. You'll see this error only if alert didn't work in FO.
     * @param array $params
     */
    public function hookActionValidateStepComplete($params)
    {
        $this->hookActionCarrierProcess($params);
        $cart = $this->context->cart;
        $carrierMethod = MondialrelayCarrierMethod::getFromNativeCarrierId($cart->id_carrier);
        if (!Validate::isLoadedObject($carrierMethod)) {
            return true;
        }

        $selectedRelay = MondialrelaySelectedRelay::getFromIdCart($cart->id);
        if ($carrierMethod->needsRelay()
            && (!Validate::isLoadedObject($selectedRelay) || !$selectedRelay->selected_relay_num)
        ) {
            $params['completed'] = false;
            $this->context->controller->errors[] = $this->l('For using the delivery to Points Relais by Mondial Relay carrier, you should select a Point Relais. If you don\'t see a widget with Points Relais, please refresh the page .');
            return false;
        }
    }
    
    /**
     * ORDER VALIDATION
     */
    
    /**
     * Order validation; we'll simply update the selected relay with the order
     * information.
     *
     * We can't interrupt the process; we're in the middle of the order
     * creation... Validation *should* have been done before arriving here...
     */
    public function hookActionValidateOrder($params)
    {
        $cart = $params['cart'];
        $order = $params['order'];

        // If we're not using a Mondial Relay carrier...
        $carrierMethod = MondialrelayCarrierMethod::getFromNativeCarrierId($cart->id_carrier);
        if (!Validate::isLoadedObject($carrierMethod)) {
            return true;
        }

        // If we don't have a selected relay...
        // This *should* never happen. And since payment modules redirect
        // the user once order validation is done, we can't warn him about
        // it.
        $selectedRelay = MondialrelaySelectedRelay::getFromIdCart($cart->id);
        \MondialrelayClasslib\Extensions\ProcessLogger\ProcessLoggerHandler::openLogger();
        $hasError = false;
        if (!Validate::isLoadedObject($selectedRelay)) {
            \MondialrelayClasslib\Extensions\ProcessLogger\ProcessLoggerHandler::logError(
                $this->l('No matching selection was found using the cart @id_cart@.', false, array('@id_order@' => $order->id, '@id_cart@' => $cart->id)),
                Order::class,
                $order->id
            );
            $hasError = true;
        } elseif (empty($selectedRelay->id_address_delivery)) {
            \MondialrelayClasslib\Extensions\ProcessLogger\ProcessLoggerHandler::logError(
                $this->l('Order @id@ : No delivery address was found in the Mondial Relay selection.', false, array('@id@' => $order->id)),
                Order::class,
                $order->id
            );
            $hasError = true;
        } elseif ($selectedRelay->id_address_delivery != $order->id_address_delivery) {
            \MondialrelayClasslib\Extensions\ProcessLogger\ProcessLoggerHandler::logError(
                $this->l('Order @id@ : The delivery addresses in the Mondial Relay selection and the order are different.', false, array('@id@' => $order->id)),
                Order::class,
                $order->id
            );
            $hasError = true;
        } elseif ($carrierMethod->needsRelay() && !$selectedRelay->selected_relay_num) {
            \MondialrelayClasslib\Extensions\ProcessLogger\ProcessLoggerHandler::logError(
                $this->l('Order @id@ : No relay was found in the Mondial Relay selection.', false, array('@id@' => $order->id)),
                Order::class,
                $order->id
            );
            $hasError = true;
        }
        if ($hasError) {
            \MondialrelayClasslib\Extensions\ProcessLogger\ProcessLoggerHandler::closeLogger();
            return false;
        }

        // Update the selected relay with the order id
        $selectedRelay->id_order = $order->id;

        // Update the package weight; it might be lower than the webservice
        // limit, but the merchant can update the weight in BO
        $selectedRelay->package_weight = $cart->getTotalWeight() * Configuration::get(self::WEIGHT_COEFF);
        if (!$selectedRelay->save()) {
            \MondialrelayClasslib\Extensions\ProcessLogger\ProcessLoggerHandler::logError(
                $this->l('Order @id@ : Could not set id_order and package_weight in Mondial Relay selection.', false, array('@id@' => $order->id)),
                Order::class,
                $order->id
            );
        }

        if ($selectedRelay->selected_relay_num) {
            // Set the address as "deleted"
            $address = new Address($selectedRelay->id_address_delivery);
            $address->deleted = 1;
            if (!$address->save()) {
                \MondialrelayClasslib\Extensions\ProcessLogger\ProcessLoggerHandler::logError(
                    $this->l('Order @id@ : Could not set customer address as "deleted".', false, array('@id@' => $order->id)),
                    Order::class,
                    $order->id
                );
            }
        }
        
        \MondialrelayClasslib\Extensions\ProcessLogger\ProcessLoggerHandler::logSuccess(
            $this->l('Order @id@ : New Mondial Relay order registered.', false, array('@id@' => $order->id)),
            Order::class,
            $order->id
        );
        \MondialrelayClasslib\Extensions\ProcessLogger\ProcessLoggerHandler::closeLogger();
        
        return true;
    }
    
    /**
     * BACK OFFICE
     */
    
    public function hookActionAdminControllerSetMedia($params)
    {
        // Checks if we should display an address error
        $this->setAddressError();
        
        if ($this->context->controller->controller_name != 'AdminOrders' || !Tools::isSubmit('vieworder')) {
            return;
        }
        
        $order = new Order(Tools::getValue('id_order'));
        if (!Validate::isLoadedObject($order)) {
            return;
        }
        
        // If we're not using a Mondial Relay carrier...
        if (!MondialrelayCarrierMethod::findMondialrelayCarrierIds(array($order->id_carrier))) {
            return;
        }
        // If we don't have a tracking url...
        $selectedRelay = MondialrelaySelectedRelay::getFromIdCart($order->id_cart);
        if (!Validate::isLoadedObject($selectedRelay) || !$selectedRelay->tracking_url) {
            return;
        }
        
        // Add JS
        $this->context->controller->addJS('modules/'.$this->name.'/views/js/admin/orders.js');
        Media::addJsDef(array(
            'MONDIALRELAY_ORDER_TRACKING_NUMBER' => $selectedRelay->expedition_num,
            'MONDIALRELAY_ORDER_TRACKING_URL' => $selectedRelay->tracking_url,
        ));
    }
    
    /**
     * This... Should be a hook. It's called directly by PS when on an order
     * page; specifically, it calls the 'displayInfoByCart' method from the
     * module managing the carrier.
     *
     * @param int $id_cart
     * @see AdminOrdersController
     */
    public function displayInfoByCart($id_cart)
    {
        // We need to use the order; the actual carrier may have been changed
        $order = new Order(Order::getOrderByCartId($id_cart));

        // If we're not using a Mondial Relay carrier
        if (!Validate::isLoadedObject($carrierMethod = MondialrelayCarrierMethod::getFromNativeCarrierId($order->id_carrier))) {
            return;
        }
        
        $template = $this->context->smarty
            ->createTemplate($this->getTemplatePath('views/templates/admin/hook/displayInfoByCart.tpl'));

        $backUrl = $this->context->link->getAdminLink('AdminOrders') . '&vieworder&id_order=' . Tools::getValue('id_order');

        // If we don't have a relay selected...
        $selectedRelay = MondialrelaySelectedRelay::getFromIdCart($id_cart);

        if (!Validate::isLoadedObject($selectedRelay)) {
            $this->context->controller->errors[] = $this->l('Unexpected error occurred. There is no Mondial Relay carrier expedition information.');
            return;
        }

        if ($carrierMethod->needsRelay() && !$selectedRelay->selected_relay_num) {
            $this->context->controller->errors[] = $this->l('This order is using a Mondial Relay carrier, but no relay is selected.');
            $template->assign(array(
                'updateRelaySelection_url' => $this->context->link->getAdminLink('AdminMondialrelaySelectedRelay')
                    . '&id_order=' . Tools::getValue('id_order')
                    . '&add' . MondialrelaySelectedRelay::$definition['table']
                    . '&back=' . urlencode($backUrl),
            ));
        } elseif ($carrierMethod->needsRelay() && !$selectedRelay->expedition_num) {
            $template->assign(array(
                'selectedRelay' => $selectedRelay,
                'updateRelaySelection_url' => $this->context->link->getAdminLink('AdminMondialrelaySelectedRelay')
                    . '&' . MondialrelaySelectedRelay::$definition['primary'] . '=' . $selectedRelay->id
                    . '&back=' . urlencode($backUrl),
                'needRelay' => $carrierMethod->needsRelay(),
            ));
        } else {
            $template->assign(array(
                'selectedRelay' => $selectedRelay,
                'needRelay' => $carrierMethod->needsRelay(),
            ));
        }

        return $template->fetch();
    }
    
    /**
     * TOOLS
     */
    
    /**
     * Checks and adds an error if we're trying to update or delete an address
     * representing a Point Relais
     */
    protected function setAddressError($controller_redirect = false)
    {
        $controller = !empty($this->context->controller->php_self) ?
            $this->context->controller->php_self
            : Tools::getValue('controller');
        $blockAddress = false;
        
        if ($controller == 'address'
            // On PS17, this is a possibility
            || (
                $controller == 'order'
                && (
                    Tools::isSubmit('editAddress')
                    || Tools::isSubmit('submitAddress')
                    || Tools::isSubmit('deleteAddress')
                )
            )
            || $controller == 'AdminAddresses'
        ) {
            $address = new Address(Tools::getValue('id_address'));
            if (Validate::isLoadedObject($address)
                && MondialrelaySelectedRelay::isRelayAddress($address->id)
            ) {
                $blockAddress = true;
            }
        }
        
        if ($blockAddress
            || $this->context->cookie->mondialrelay_flashAddressError
        ) {
            unset($this->context->cookie->mondialrelay_flashAddressError);
            $this->context->cookie->write();
            $this->context->controller->errors[] = $this->l('You are trying to modify the address of a Mondial Relay Point Relais. It is not possible to modify this address. Please create a new address if needed.');
        }
        
        if (!$blockAddress || !$controller_redirect) {
            return;
        }
        
        $this->context->cookie->mondialrelay_flashAddressError = true;
        $this->context->cookie->write();
        
        if (strpos($controller_redirect, 'Admin') === false) {
            Tools::redirect($this->context->link->getPageLink(
                $controller_redirect,
                null,
                null
            ));
        } else {
            Tools::redirectAdmin($this->context->link->getAdminLink(
                $controller_redirect
            ));
        }
        exit;
    }
    
    /**
     * A custom translation function; with built-in replacements, specific
     * replacements, and <a></a> management.
     * If '[a]' tags are in the strings, the function will look for 'href' and
     * 'target' fields. The first '[a]' will use 'href' and 'target', the second
     * will use 'href_1' and 'target_1', and so on.
     *
     * @param type $string
     * @param type $specific
     * @param type $replacements
     * @return type
     */
    public function l($string, $specific = false, $replacements = array())
    {
        $string = parent::l($string, $specific);
        
        // Replace formatting and 'a' closing tags
        $search = array(
            '[b]',
            '[/b]',
            '[br]',
            '[em]',
            '[/em]',
            '[/a]',
            '[small]',
            '[/small]',
            '[strong]',
            '[/strong]',
            '[i]',
            '[/i]'
        );
        $replace = array(
            '<b>',
            '</b>',
            '<br>',
            '<em>',
            '</em>',
            '</a>',
            '<small>',
            '</small>',
            '<strong>',
            '</strong>',
            '<i>',
            '</i>'
        );
        $string = str_replace($search, $replace, $string);
        
        // Replace 'a' opening tags
        $n = 0;
        $string = preg_replace_callback(
            '#\[a\]#',
            function ($matches) use (&$n) {
                $r = '<a href="@href' . ($n ? '_' . $n : '') . '@" target="@target' . ($n ? '_' . $n : '') . '@">';
                ++$n;
                return $r;
            },
            $string
        );

        // Replace custom tags, including 'href' and 'target' values
        foreach ($replacements as $k => $v) {
            if (preg_match("#href(?:_\d+)?|target(?:_\d+)?#", $k)) {
                $k = '@' . $k . '@';
            }
            $string = str_replace($k, $v, $string);
        }
        
        // Replace empty @href@ and @target@ tags
        return preg_replace(
            array('# href="@href(?:_\d+)?@"#', '# target="@target(?:_\d+)?@"#'),
            array(' href="#"', ''),
            $string
        );
    }
    
    /**
     * Sets a key with a value if not already set (non-strict comparison). Used
     * for module install/upgrades.
     *
     * @param string $key : the configuration key to set
     * @param string $defaultValue : the default value
     *
     * @return void
     */
    public function setConfigurationDefault($key, $defaultValue)
    {
        if (!Configuration::hasKey($key)) {
            Configuration::updateValue($key, $defaultValue);
        }
    }
    
    /**
     * Renames a key in Configuration; used for module upgrades.
     *
     * @param string $oldKey : the configuration key to update
     * @param string $newKey : the new configuration key
     *
     * @return void
     */
    public function updateConfigurationKey($oldKey, $newKey)
    {
        Db::getInstance()->execute(
            "UPDATE `" . _DB_PREFIX_ . "configuration` "
            . "SET `name` = '" . pSQL($newKey) . "' "
            . "WHERE `name` = '" . pSQL($oldKey) . "'"
        );
    }
}
