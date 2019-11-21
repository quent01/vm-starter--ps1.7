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

require_once _PS_MODULE_DIR_ . '/mondialrelay/controllers/admin/AdminMondialrelayController.php';
require_once _PS_MODULE_DIR_ . '/mondialrelay/classes/MondialrelayCarrierMethod.php';
require_once _PS_MODULE_DIR_ . '/mondialrelay/classes/MondialrelaySelectedRelay.php';

use MondialrelayClasslib\Actions\ActionsHandler;

class AdminMondialrelaySelectedRelayController extends AdminMondialrelayController
{
    public $className = 'MondialrelaySelectedRelay';
    
    public $display = 'edit';
    
    /**
     * @var array Holds relay data between object validation and save
     */
    protected $relayInformations = array();
    
    public function __construct()
    {
        $this->table = MondialrelaySelectedRelay::$definition['table'];
        
        parent::__construct();
        
        $carrierMethod = new MondialrelayCarrierMethod();
        $this->insuranceLevelsList = $carrierMethod->getInsuranceLevelsList();
    }
    
    public function initContent()
    {
        if (!MondialRelayTools::checkDependencies()) {
            $this->errors[] = $this->module->l('SOAP and cURL should be installed on your server.', 'AdminMondialrelayController');
            return;
        }
        
        if (!MondialRelayTools::checkWebserviceConfiguration()) {
            $this->errors[] = $this->module->l('Please configure your webservice from the Account Settings tab.', 'AdminMondialrelayController');
            return;
        }
        
        if ($this->display == 'edit') {
            if (!$this->loadObject()) {
                $this->errors[] = $this->module->l('Could not find updatable Mondial Relay order.', 'AdminMondialrelaySelectedRelayController');
                return;
            }

            if ($this->object->label_url) {
                $this->errors[] = $this->module->l('Label has already been generated for this order.', 'AdminMondialrelaySelectedRelayController');
                return;
            }
        }
        
        if ($this->display == 'add') {
            $order = new Order(Tools::getValue('id_order'));
            if (!Validate::isLoadedObject($order)) {
                $this->errors[] = $this->module->l('No order was specified.', 'AdminMondialrelaySelectedRelayController');
                return;
            }
            
            $carrierMethod = MondialrelayCarrierMethod::getFromNativeCarrierId($order->id_carrier);
            if (!Validate::isLoadedObject($carrierMethod)) {
                $this->errors[] = $this->module->l('This order is not using a Mondial Relay carrier.', 'AdminMondialrelaySelectedRelayController');
                return;
            }
        }
        
        return parent::initContent();
    }
    
    public function setMedia($isNewTheme = false)
    {
        if (version_compare(_PS_VERSION_, '1.7', '>=')) {
            parent::setMedia($isNewTheme);
        } else {
            parent::setMedia();
        }
        
        $this->addJS($this->module->getPathUri() . '/views/js/admin/selected-relay.js');
    }
    
    public function renderForm()
    {
        if ($this->display == 'edit') {
            $carrierMethod = new MondialrelayCarrierMethod($this->object->id_mondialrelay_carrier_method);
            $address = new Address($this->object->id_address_delivery);
            if (!Validate::isLoadedObject($address)) {
                $order = new Order($this->object->id_order);
                $address = new Address($order->id_address_delivery);
            }
        } else {
            // display == 'add'
            $order = new Order(Tools::getValue('id_order'));
            $carrierMethod = MondialrelayCarrierMethod::getFromNativeCarrierId($order->id_carrier);
            $address = new Address($order->id_address_delivery);
        }
            
        $this->fields_form = array(
            'legend' => array(
                'title' => $this->module->l('Edit Weight & Insurance & Relay Point', 'AdminMondialrelaySelectedRelayController'),
            ),
            'input' => array(
                array(
                    'label' => $this->module->l('Order ID', 'AdminMondialrelaySelectedRelayController'),
                    'name' => 'id_order',
                    'value' => $this->id_object ? $this->object->id_order : Tools::getValue('id_order'),
                    'type' => 'text',
                    'readonly' => true,
                ),
                array(
                    'type' => 'text',
                    'label' => $this->module->l('Weight (grams)', 'AdminMondialrelaySelectedRelayController'),
                    'name' => 'package_weight',
                    'hint' => $this->module->l('The weight must be greater or equal to @limit@ grams.', 'AdminMondialrelaySelectedRelayController', array('@limit@' => Mondialrelay::MINIMUM_PACKAGE_WEIGHT))
                ),
                array(
                    'label' => $this->module->l('Insurance', 'AdminMondialrelaySelectedRelayController'),
                    'name' => 'insurance_level',
                    'type' => 'select',
                    'options' => array(
                        'id' => 'value',
                        'name' => 'label',
                        'query' => MondialrelayTools::formatArrayForSelect($this->insuranceLevelsList),
                    ),
                    'hint' => $this->module->l('Please consult the details of your offer to find informations about your delivery mode options.', 'AdminMondialrelayCarriersSettingsController'),
                    'required' => true,
                ),
            ),
            'submit' => array(
                'title' => $this->module->l('Save', 'AdminMondialrelaySelectedRelayController'),
            )
        );
        
        if ($carrierMethod->needsRelay()) {
            // Add JS values for the widget
            Media::addJsDef(array(
                'MONDIALRELAY_ENSEIGNE' => Configuration::get(Mondialrelay::WEBSERVICE_ENSEIGNE),
                'MONDIALRELAY_DISPLAY_MAP' => Configuration::get(Mondialrelay::DISPLAY_MAP),
                'MONDIALRELAY_NO_SELECTION_ERROR' => $this->module->l('Please select a Point Relais.', 'AdminMondialrelaySelectedRelayController'),
                'MONDIALRELAY_SELECTED_RELAY_IDENTIFIER' => $this->object->getFullRelayIdentifier(),
                'MONDIALRELAY_COUNTRY_ISO' => $address->id_country ? Country::getIsoById($address->id_country) : '',
                'MONDIALRELAY_POSTCODE' => $address->postcode ? $address->postcode : '',
                'MONDIALRELAY_DELIVERY_MODE' => $carrierMethod->delivery_mode,
            ));
            // Add an input to select a relay
            $this->fields_form['input'][] = array(
                'label' => $this->module->l('Relay', 'AdminMondialrelaySelectedRelayController'),
                'name' => 'selected_relay_full_identifier',
                'type' => 'mondialrelay_relay-input',
                'required' => true,
            );
        }
        
        return parent::renderForm();
    }
    
    protected function _childValidation()
    {
        // If we're selecting a relay for the first time, the 'id_order' is a
        // parameter; if we're updating a relay selection, we have a loaded
        // object which *must* have an id_order
        $order = new Order($this->id_object ? $this->object->id_order : Tools::getValue('id_order'));
        if (!Validate::isLoadedObject($order)) {
            $this->errors[] = $this->module->l('No order was specified.', 'AdminMondialrelaySelectedRelayController');
            return;
        }
            
        $carrierMethod = MondialrelayCarrierMethod::getFromNativeCarrierId($order->id_carrier);
        if (!Validate::isLoadedObject($carrierMethod)) {
            $this->errors[] = $this->module->l('This order is not using a Mondial Relay carrier.', 'AdminMondialrelaySelectedRelayController');
            return;
        }

        $package_weight = (int)Tools::getValue('package_weight');
        if ($package_weight < Mondialrelay::MINIMUM_PACKAGE_WEIGHT) {
            $this->errors[] = $this->module->l('The weight must be greater or equal to @limit@ grams.', 'AdminMondialrelaySelectedRelayController', array('@limit@' => Mondialrelay::MINIMUM_PACKAGE_WEIGHT));
            return;
        }
        
        // Check if we need to save the relay
        if ($carrierMethod->needsRelay()) {
            $fullRelayIdentifier = Tools::getValue('selected_relay_full_identifier');
            if (!$fullRelayIdentifier) {
                $this->errors['selected_relay_full_identifier'] = $this->module->l('You must select a Point Relais.', 'AdminMondialrelaySelectedRelayController');
                return false;
            }

            list($relay_country_iso, $relay_num) = explode('-', $fullRelayIdentifier);
            if (!$relay_country_iso || !$relay_num) {
                $this->errors['selected_relay_full_identifier'] = $this->module->l('Invalid relay identifier : @identifier@', 'AdminMondialrelaySelectedRelayController', array('@identifier@' => $fullRelayIdentifier));
                return false;
            }

            $validationObject = new MondialrelaySelectedRelay();
            if (($error = $validationObject->validateField('selected_relay_country_iso', $relay_country_iso, null, array(), true)) !== true) {
                $this->errors['selected_relay_full_identifier'] = $error;
                return false;
            }
            if (($error = $validationObject->validateField('selected_relay_num', $relay_num, null, array(), true)) !== true) {
                $this->errors['selected_relay_full_identifier'] = $error;
                return false;
            }

            $this->setRelayInformations($relay_country_iso, $relay_num);
        }
    }
    
    /**
     * Retrieve relay informations and save them for later use. Will also
     * validate the relay information (filling the $errors property).
     *
     * @param string $relay_country_iso
     * @param string $relay_num
     * @return boolean
     *
     * @see _childValidation()
     * @see copyFromPost()
     */
    protected function setRelayInformations($relay_country_iso, $relay_num)
    {
        // Create the handler
        $handler = new ActionsHandler();
        
        // Set input data
        $handler->setConveyor(array(
                'enseigne' => Configuration::get(Mondialrelay::WEBSERVICE_ENSEIGNE),
                'country_iso' => $relay_country_iso,
                'relayNumber' => $relay_num,
            ))
            ->addActions('getRelayInformations');

        // Process actions chain
        try {
            $handler->process('SelectRelay');
        } catch (\Exception $e) {
            $actionsResult = $handler->getConveyor();
            
            if (empty($actionsResult['errors'])) {
                $this->errors[] = $e->getMessage();
                return false;
            }
            
            foreach ($actionsResult['errors'] as $error) {
                $this->errors[] = $error;
            }
            
            return false;
        }
        
        // Get process result, set errors if any
        $actionsResult = $handler->getConveyor();
        if (!empty($actionsResult['errors'])) {
            foreach ($actionsResult['errors'] as $error) {
                $this->errors[] = $error;
            }
            return false;
        }
        
        $this->relayInformations = $actionsResult['relayInfos'];
    }
    
    /**
     * This will update the selected relay and the client's address.
     * We're not sending every field through the form; we'll get the rest from
     * the API and/or the previous orders.
     *
     * @param MondialrelaySelectedRelay $object
     * @param string $table
     * @see AdminController::copyFromPost()
     */
    protected function copyFromPost(&$object, $table)
    {
        if ($this->id_object) {
            // We're updating a relay
            $carrierMethod = new MondialrelayCarrierMethod($this->object->id_mondialrelay_carrier_method);
        } else {
            // We're adding a relay
            $order = new Order(Tools::getValue('id_order'));
            $carrierMethod = MondialrelayCarrierMethod::getFromNativeCarrierId($order->id_carrier);
            $object->id_order = $order->id;
            $object->id_cart = $order->id_cart;
        }
        
        if ($carrierMethod->needsRelay()) {
            // Create the handler
            $handler = new ActionsHandler();

            // Set input data
            $handler->setConveyor(array(
                'carrierMethod' => $carrierMethod,
                'cart' => new Cart($object->id_cart),
                'relayInfos' => $this->relayInformations,
                'id_order' => $object->id_order,
                // prevents the action from saving the relay, as PS might want to do
                // it himself; we'd end up with 2 lines created in DB
                'noRelaySave' => true,
                // Sets the address as "deleted" so it won't appear on FO
                'deleteAddress' => true,
            ));
            $actions = array('setSelectedRelay');

            // Set actions to execute
            call_user_func_array(array($handler, 'addActions'), $actions);

            // Process actions chain
            try {
                $handler->process('SelectRelay');
            } catch (\Exception $e) {
                $actionsResult = $handler->getConveyor();

                if (!empty($actionsResult['errors'])) {
                    foreach ($actionsResult['errors'] as $error) {
                        $this->errors[] = $error;
                    }
                }

                throw new PrestaShopException(sprintf(
                    $this->module->l('Could not save selected relay : %s', 'AdminMondialrelaySelectedRelayController'),
                    $e->getMessage()
                ));
            }

            // Get process result, set errors if any
            $actionsResult = $handler->getConveyor();
            if (!empty($actionsResult['errors'])) {
                foreach ($actionsResult['errors'] as $error) {
                    $this->errors[] = $error;
                }
                throw new PrestaShopException($this->module->l('Could not save selected relay.', 'AdminMondialrelaySelectedRelayController'));
            }

            // Update the object to save
            $object = $actionsResult['selectedRelay'];
        }
        
        parent::copyFromPost($object, $table);
        
        // Do this after PS process, to make sure it's not overwritten
        $object->package_weight = (int)$object->package_weight;
    }

    public function getTemplateFormVars()
    {
        $this->tpl_form_vars['selectedRelay'] = Validate::isLoadedObject($this->object) ? $this->object : false;
        $this->tpl_form_vars['module_url'] = $this->module->getPathUri();
        return parent::getTemplateFormVars();
    }
    
    public function getInsuranceLevelLabel($delivery_mode, $data)
    {
        return $this->insuranceLevelsList[$delivery_mode];
    }
}
