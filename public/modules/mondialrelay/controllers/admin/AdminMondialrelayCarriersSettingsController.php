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
require_once _PS_MODULE_DIR_ . '/mondialrelay/classes/MondialrelayTools.php';

use MondialrelayClasslib\Actions\ActionsHandler;

class AdminMondialrelayCarriersSettingsController extends AdminMondialrelayController
{
    protected $fields_form_newMondialrelayCarrier = array();
    
    /** @var array $insuranceLevelsList see MondialrelayCarrierMethod::getInsuranceLevelsList() */
    protected $insuranceLevelsList = array();
    
    /** @var array $deliveryModesList see MondialrelayCarrierMethod::getDeliveryModesList() */
    protected $deliveryModesList = array();
    
    public function __construct()
    {
        $this->table = MondialrelayCarrierMethod::$definition['table'];
        
        $carrierMethod = new MondialrelayCarrierMethod();
        $this->insuranceLevelsList = $carrierMethod->getInsuranceLevelsList();
        $this->deliveryModesList = $carrierMethod->getDeliveryModesList();
        
        parent::__construct();
        
        $this->initList();
    }

    public function init()
    {
        $this->initNewMondialrelayCarrierFormFields();

        parent::init();
    }
    
    public function initList()
    {
        $this->explicitSelect = true;
        
        $this->fields_list = array(
            $this->identifier => array(
                'title' => $this->module->l('ID Mondial Relay carrier', 'AdminMondialrelayCarriersSettingsController'),
                'align' => 'center',
                'class' => 'fixed-width-xs',
            ),
            'id_carrier' => array(
                'title' => $this->module->l('ID Prestashop Carrier', 'AdminMondialrelayCarriersSettingsController'),
                'filter_key' => 'p_c!id_carrier',
                'align' => 'center',
                'class' => 'fixed-width-xs',
            ),
            'name' => array(
                'title' => $this->module->l('Carrier', 'AdminMondialrelayCarriersSettingsController'),
                'filter_key' => 'p_c!name'
            ),
            'delivery_mode' => array(
                'title' => $this->module->l('Delivery mode', 'AdminMondialrelayCarriersSettingsController'),
                'callback' => 'getDeliveryModeLabel'
            ),
            'insurance_level' => array(
                'title' => $this->module->l('Insurance', 'AdminMondialrelayCarriersSettingsController'),
                'callback' => 'getInsuranceLevelLabel'
            )
        );
        
        $this->_join = 'LEFT JOIN `'._DB_PREFIX_.Carrier::$definition['table'].'` p_c ON p_c.id_carrier = a.id_carrier ';
        $this->_join .= Shop::addSqlAssociation(Carrier::$definition['table'], 'p_c');
        $this->_where = 'AND p_c.deleted = 0';
        $this->_group = 'GROUP BY a.id_carrier';
        
        $this->actions = array('edit', 'delete');
    }
    
    public function renderList()
    {
        // Render form before list
        $helper = new HelperForm($this);
        $this->setHelperDisplay($helper);
        $helper->fields_value = array('name' => '', 'delay' => '', 'delivery_mode' => '', 'insurance_level' => '');

        $this->content .= $helper->generateForm($this->fields_form_newMondialrelayCarrier);
        
        // Render list
        $list = parent::renderList();
        if (!empty($this->_list)) {
            $this->content .= $list;
            return;
        }
        
        // If list is empty, we have a custom message
        $tpl = $this->createTemplate('list_empty.tpl');
        $tpl->assign(array(
            'title' => $this->helper->title,
            'message' => $this->module->l('No shipping methods available. Please create a new carrier using the form above.', 'AdminMondialrelayCarriersSettingsController'),
        ));
        $this->content .= $tpl->fetch();
    }
    
    public function setHelperDisplay(Helper $helper)
    {
        parent::setHelperDisplay($helper);
        
        // If we're setting up the list
        if (get_class($helper) == 'HelperList') {
            // We need to set the helper's identifier as the one from "Carrier"
            // to have the links referencing the right object
            $helper->identifier = Carrier::$definition['primary'];
            
            unset($helper->toolbar_btn['new']);
            $helper->title = $this->module->l('Carriers List', 'AdminMondialrelayCarriersSettingsController');
        }
    }
    
    protected function initNewMondialrelayCarrierFormFields()
    {
        $description = $this->module->l('Create a new carrier(s) associated with the Mondial Relay module. [br] You will be able to add additional settings to this carrier once it is created via [a] Shipping > Carriers[/a]. [br] Please note that it is required to modify your carrier shipping fees, delivery time, package weight, height, etc... [br] Please pay attention that, by default, a new carrier will be available for every zone enabled in your shop.', 'AdminMondialrelayCarriersSettingsController', array('href' => $this->context->link->getAdminLink('AdminCarriers')));
        
        $this->fields_form_newMondialrelayCarrier = array(array(
            'form' => array(
                'legend' => array(
                    'title' => $this->module->l('Create a New Carrier', 'AdminMondialrelayCarriersSettingsController'),
                    'icon' => 'icon-cog'
                ),
                'description' => $description,
                'input' => array(
                    array(
                        'label' => $this->module->l('Carrier name', 'AdminMondialrelayCarriersSettingsController'),
                        'name' => 'name',
                        'type' => 'text',
                        'required' => true,
                    ),
                    array(
                        'label' => $this->module->l('Delivery time', 'AdminMondialrelayCarriersSettingsController'),
                        'name' => 'delay',
                        'type' => 'text',
                        'required' => true,
                    ),
                    array(
                        'label' => $this->module->l('Delivery mode', 'AdminMondialrelayCarriersSettingsController'),
                        'name' => 'delivery_mode',
                        'type' => 'select',
                        'options' => array(
                            'id' => 'value',
                            'name' => 'label',
                            'query' => MondialrelayTools::formatArrayForSelect($this->deliveryModesList),
                        ),
                        'hint' => $this->module->l('Please consult the details of your offer to find informations about your delivery mode options.', 'AdminMondialrelayCarriersSettingsController'),
                        'required' => true,
                    ),
                    array(
                        'label' => $this->module->l('Insurance', 'AdminMondialrelayCarriersSettingsController'),
                        'name' => 'insurance_level',
                        'type' => 'select',
                        'options' => array(
                            'id' => 'value',
                            'name' => 'label',
                            'query' => MondialrelayTools::formatArrayForSelect($this->insuranceLevelsList),
                        ),
                        'hint' => $this->module->l('Please consult the details of your offer to find informations about your insurance options.', 'AdminMondialrelayCarriersSettingsController'),
                        'required' => true,
                    ),
                ),
                'submit' => array(
                    'title' => $this->module->l('Save', 'AdminMondialrelayCarriersSettingsController'),
                    'name' => 'submitAddNewMondialrelayCarrier',
                    // We have to change our button id, otherwise some PS native
                    // JS script will hide it.
                    'id' => 'mondialrelay_submit-carrier-btn'
                ),
            ),
        ));
    }
    
    public function initProcess()
    {
        parent::initProcess();
        if (Tools::isSubmit('submitAddNewMondialrelayCarrier')) {
            $this->action = 'addNewMondialrelayCarrier';
        }
    }

    /**
     * Add new shipping method
     *
     * @return bool
     */
    protected function processAddNewMondialrelayCarrier()
    {
        // Validate fields
        foreach ($this->fields_form_newMondialrelayCarrier[0]['form']['input'] as $field) {
            $value = trim(Tools::getValue($field['name']));
            
            if (!empty($field['required']) && empty($value) && (string)$value != '0') {
                $this->errors[] = $this->module->l('Field %field% is required.', 'AdminMondialrelayCarriersSettingsController', array('%field%' => $field['label']));
                continue;
            }
        }
        
        if (!empty($this->errors)) {
            return false;
        }
        
        // Create the handler
        $handler = new ActionsHandler();

        /**
         * Add a new Prestashop carrier.
         *
         * - The carrier is created with all zones.
         * - Default pricing behavior is "according to weight".
         * - A default weight range will be created (depending on the selected
         * "delivery mode" for the Mondial Relay carrier).
         * - A default price range will be created as well (same values for every
         * "delivery mode").
         * - The carrier  will be available for every client groups.
         *
         * @param string $name The new carrier's name
         * @param string $delay The new carrier's delay
         *
         * @return Carrier|false The new carrier or false
         */
        
        // Set input data
        $handler->setConveyor(array(
                'name' => Tools::getValue('name'),
                'delay' => Tools::getValue('delay'),
                'delivery_mode' => Tools::getValue('delivery_mode'),
                'insurance_level' => Tools::getValue('insurance_level'),
                'weight_coeff' => Configuration::get(Mondialrelay::WEIGHT_COEFF),
            ))
            // Set list of actions to execute
            ->addActions(
                'addNativeCarrier',
                'addMondialRelayCarrierMethod',
                'setDefaultZones',
                'setDefaultRangeWeight',
                'setDefaultRangePrice',
                'setDefaultGroups'
            );

        // Process actions chain
        try {
            $processStatus = $handler->process('NewCarrier');
        } catch (\Exception $e) {
            $actionsResult = $handler->getConveyor();
            if (!empty($actionsResult['errors'])) {
                $this->errors = array_merge($this->errors, $actionsResult['errors']);
            }
            $this->errors[] = $this->module->l('Could not add new carrier : %error%', 'AdminMondialrelayCarriersSettingsController', array('%error%' => $e->getMessage()));
            
            // If process failed, delete native carrier if it exists
            if (!empty($actionsResult['carrier']) && Validate::isLoadedObject($actionsResult['carrier'])) {
                $actionsResult['carrier']->delete();
            }
            
            return false;
        }
        
        // Get process result, set errors if any
        $actionsResult = $handler->getConveyor();

        // If process failed, delete native carrier if it exists
        if (!$processStatus) {
            if (!empty($actionsResult['carrier']) && Validate::isLoadedObject($actionsResult['carrier'])) {
                $actionsResult['carrier']->delete();
            }
            if (!empty($actionsResult['errors'])) {
                $this->errors = array_merge($this->errors, $actionsResult['errors']);
            }
            return false;
        }
        
        if (!empty($actionsResult['errors'])) {
            $this->warnings = array_merge($this->warnings, $actionsResult['errors']);
        }
        $this->confirmations[] = $this->module->l('Carrier successfully created.', 'AdminMondialrelayCarriersSettingsController');
        
        return true;
    }
    
    public function getDeliveryModeLabel($delivery_mode, $data)
    {
        return $this->deliveryModesList[$delivery_mode];
    }
    
    public function getInsuranceLevelLabel($delivery_mode, $data)
    {
        return $this->insuranceLevelsList[$delivery_mode];
    }
    
    /**
     * Displays an "edit" link; we need it pointing to the AdminCarrierWizard
     * controller.
     *
     * Most of this code is from AdminCarriers
     *
     * @param string $token
     * @param int $id
     * @param string $name
     *
     * @return string
     */
    public function displayEditLink($token, $id, $name)
    {
        $tpl = $this->createTemplate('helpers/list/list_action_edit.tpl');
        if (!array_key_exists('Edit', self::$cache_lang)) {
            self::$cache_lang['Edit'] = $this->l('Edit', 'Helper');
        }

        $tpl->assign(array(
            'href' => $this->context->link->getAdminLink('AdminCarrierWizard')
                . '&id_carrier=' . (int)$id
                . '&action_origin=AdminMondialrelayCarriersSettings',
            'target' => '_blank',
            'action' => $this->module->l('View / Edit', 'AdminMondialrelayCarriersSettingsController'),
            'id' => $id
        ));

        return $tpl->fetch();
    }
    
    /**
     * Displays a "delete" link; we need it pointing to the AdminCarriers
     * controller.
     *
     * Most of this code is from AdminCarriers
     *
     * @param string $token
     * @param int $id
     * @param string $name
     *
     * @return string
     */
    public function displayDeleteLink($token, $id, $name)
    {
        $tpl = $this->createTemplate('helpers/list/list_action_delete.tpl');

        if (!array_key_exists('Delete', self::$cache_lang)) {
            self::$cache_lang['Delete'] = $this->l('Delete', 'Helper');
        }

        if (!array_key_exists('DeleteItem', self::$cache_lang)) {
            self::$cache_lang['DeleteItem'] = $this->l('Delete selected item?', 'Helper');
        }

        if (!array_key_exists('Name', self::$cache_lang)) {
            self::$cache_lang['Name'] = $this->l('Name:', 'Helper');
        }

        if (!is_null($name)) {
            $name = '\n\n'.self::$cache_lang['Name'].' '.$name;
        }

        $data = array(
            $this->identifier => $id,
            'href' => $this->context->link->getAdminLink('AdminCarriers')
                . '&id_carrier='.(int)$id
                . '&deletecarrier=1'
                . '&action_origin=AdminMondialrelayCarriersSettings',
            'action' => self::$cache_lang['Delete'],
        );

        if ($this->specificConfirmDelete !== false) {
            $data['confirm'] = !is_null($this->specificConfirmDelete) ? '\r'.$this->specificConfirmDelete : addcslashes(Tools::htmlentitiesDecodeUTF8(self::$cache_lang['DeleteItem'].$name), '\'');
        }

        $tpl->assign(array_merge($this->tpl_delete_link_vars, $data));

        return $tpl->fetch();
    }
}
