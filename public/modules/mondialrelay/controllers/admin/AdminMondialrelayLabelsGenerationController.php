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
require_once _PS_MODULE_DIR_ . '/mondialrelay/mondialrelay.php';
require_once _PS_MODULE_DIR_ . '/mondialrelay/classes/MondialrelayTools.php';

use MondialrelayClasslib\Actions\ActionsHandler;

class AdminMondialrelayLabelsGenerationController extends AdminMondialrelayController
{
    /**
     * @inheritdoc
     */
    protected $with_mondialrelay_header = false;
    
    /** @var array $insuranceLevelsList see MondialrelayCarrierMethod::getInsuranceLevelsList() */
    protected $insuranceLevelsList = array();
    
    /**
     * @inheritdoc
     * Our list will always have at least one filter (order state)
     */
    protected $filter = true;
    
    /**
     * @inheritdoc
     * We don't want a link on whole lines
     */
    protected $list_no_link = true;

    public function __construct()
    {
        $this->table = MondialrelaySelectedRelay::$definition['table'];
        
        parent::__construct();
        
        $carrierMethod = new MondialrelayCarrierMethod();
        $this->insuranceLevelsList = $carrierMethod->getInsuranceLevelsList();
        
        $this->initList();
    }

    public function init()
    {
        return parent::init();
    }
    
    public function initList()
    {
        $this->explicitSelect = true;
        
        $this->fields_list = array(
            'id_order' => array(
                'title' => $this->module->l('Order ID', 'AdminMondialrelayLabelsGenerationController'),
                'align' => 'center',
                'class' => 'fixed-width-xs',
                'filter_key' => 'a!id_order'
            ),
            'current_state' => array(
                'title' => $this->module->l('Order Status', 'AdminMondialrelayLabelsGenerationController'),
                'callback' => 'getOrderStateName',
                'type' => 'select',
                'list' => array_column(OrderState::getOrderStates($this->context->language->id), 'name', 'id_order_state'),
                'filter_key' => 'o!current_state',
            ),
            'total_paid' => array(
                'title' => $this->module->l('Total price', 'AdminMondialrelayLabelsGenerationController'),
                'type' => 'price',
            ),
            'total_shipping' => array(
                'title' => $this->module->l('Total shipping costs', 'AdminMondialrelayLabelsGenerationController'),
                'type' => 'price',
            ),
            'date_add' => array(
                'title' => $this->module->l('Date', 'AdminMondialrelayLabelsGenerationController'),
                'filter_key' => 'o!date_add',
                'type' => 'date',
            ),
            'package_weight' => array(
                'title' => $this->module->l('Weight (grams)', 'AdminMondialrelayLabelsGenerationController'),
            ),
            'insurance_level' => array(
                'title' => $this->module->l('Insurance', 'AdminMondialrelayLabelsGenerationController'),
                'callback' => 'getInsuranceLevelLabel',
                'filter_key' => 'a!insurance_level',
            ),
            'selected_relay_num' => array(
                'title' => $this->module->l('MR Number', 'AdminMondialrelayLabelsGenerationController'),
            ),
            'selected_relay_country_iso' => array(
                'title' => $this->module->l('MR Country', 'AdminMondialrelayLabelsGenerationController'),
            ),
        );
        
        // Build the query
        $this->_select .= 'a.' . MondialrelaySelectedRelay::$definition['primary'] . ', '
            . 'CONCAT(c.firstname, " ", c.lastname) AS customer_name, '
            . 'o.id_currency, '
            . 'os_l.name AS os_name';
        $this->_join .=
            'INNER JOIN `'._DB_PREFIX_. MondialrelayCarrierMethod::$definition['table'].'` mr_cm '
                . 'ON mr_cm.id_mondialrelay_carrier_method = a.id_mondialrelay_carrier_method '
            . 'INNER JOIN `'._DB_PREFIX_.Order::$definition['table'].'` o ON o.id_order = a.id_order '
            . 'INNER JOIN `'._DB_PREFIX_.Customer::$definition['table'].'` c ON c.id_customer = o.id_customer '
            . 'INNER JOIN `'._DB_PREFIX_.OrderState::$definition['table'].'_lang` os_l '
                . 'ON os_l.id_order_state = o.current_state AND os_l.id_lang = ' . (int)$this->context->language->id;
        $this->_where .= 'AND (a.expedition_num = "" OR a.expedition_num IS NULL) ';
        $this->_where .= 'AND o.id_shop IN (' . implode(', ', Shop::getContextListShopID()) . ') ';

        // Per-row actions
        $this->actions_available[] = 'generate';
        $this->actions = array('generate');
        
        // Bulk actions
        $this->bulk_actions = array(
            'generateSelectionLabels' => array(
                'text' => $this->module->l('Generate labels for selected orders', 'AdminMondialrelayLabelsGenerationController'),
                'icon' => ''
            ),
        );
    }
    
    public function getOrderStateName($id_order_state, $data)
    {
        return $data['os_name'];
    }
    
    public function getInsuranceLevelLabel($insurance_level, $data)
    {
        return $this->insuranceLevelsList[$insurance_level];
    }

    /**
     * Display "generate" and "edit" action link side by side
     * @see HelperList::displayListContent
     */
    public function displayGenerateLink($token, $id, $name = null)
    {
        $tpl = $this->helper->createTemplate('list_actions.tpl');
        if (!array_key_exists('Generate', HelperList::$cache_lang)) {
            HelperList::$cache_lang['Generate'] = $this->module->l('Generate', 'AdminMondialrelayLabelsGenerationController');
        }
        
        $tpl->assign(array(
            'href_generate' => $this->context->link->getAdminLink('AdminMondialrelayLabelsGeneration')
                    . '&generateLabel&' . MondialrelaySelectedRelay::$definition['primary'] . '=' . $id,
            'action' => HelperList::$cache_lang['Generate'],
            'id' => $id,
            'href_edit' => $this->context->link->getAdminLink('AdminMondialrelaySelectedRelay')
                .'&'.$this->helper->identifier.'='.$id.'&update'.$this->helper->table
                .'&back='.urlencode($this->context->link->getAdminLink('AdminMondialrelayLabelsGeneration')),
        ));
        return $tpl->fetch();
    }

    /**
     * @inheritdoc
     */
    public function setHelperDisplay(Helper $helper)
    {
        parent::setHelperDisplay($helper);
        switch (get_class($this->helper)) {
            case 'HelperList':
                $this->tpl_list_vars['original_content'] = $this->helper->base_folder . 'list_content.tpl';
                $this->tpl_list_vars['view_order_url'] = $this->context->link->getAdminLink('AdminOrders')
                    . '&vieworder&id_order=';
                break;
        }
    }

    public function initPageHeaderToolbar()
    {
        parent::initPageHeaderToolbar();
        $this->page_header_toolbar_title = $this->module->l('Labels Generation', 'AdminMondialrelayLabelsGenerationController');
    }
    
    public function initContent()
    {
        $this->informations[] = $this->module->l('You can create labels for all Mondial relay orders in selected status. You can select multiple lines or all orders if you want. [br] Please use bulk actions for selection and labels generations. [br] To see the labels History please click [a]here[/a].', 'AdminMondialrelayLabelsGenerationController', array('href' => $this->context->link->getAdminLink('AdminMondialrelayLabelsHistory'), 'target' => 'blank'));
    
        return parent::initContent();
    }

    public function processFilter()
    {
        $filterOrderState = (int)Configuration::get(Mondialrelay::OS_DISPLAY_LABEL);
        if ($filterOrderState) {
            $this->setDefaultFilter('o!current_state', $filterOrderState);
        }
        return parent::processFilter();
    }

    public function initProcess()
    {
        parent::initProcess();
        if (Tools::isSubmit('generateLabel')) {
            $this->action = 'generateLabel';
        }
    }
    
    /**
     * Prepare (get from DB) and send (return successes / errors), for a single
     * order
     */
    public function processGenerateLabel()
    {
        $selectedRelay = new MondialrelaySelectedRelay(
            Tools::getValue(MondialrelaySelectedRelay::$definition['primary'])
        );
        if (!Validate::isLoadedObject($selectedRelay)) {
            $this->errors[] = Tools::displayError('The object cannot be loaded (or found)');
            return false;
        }
        
        if (true !== ($error = $this->validateGeneration($selectedRelay))) {
            $this->errors[] = $error;
            return false;
        }
        
        // Create the handler
        $handler = new ActionsHandler();
        
        // Set input data
        $handler->setConveyor(array(
            // The Actions chain was designed with bulk actions in mind, so we
            // have to pass an array
            'order_ids' => array($selectedRelay->id_order),
        ));
        
        // Set actions to execute
        $handler->addActions('prepareData', 'generateLabels', 'sendTrackingEmails');

        // Process actions chain
        try {
            $handler->process('GenerateLabels');
        } catch (\Exception $e) {
            $this->errors[] = sprintf(
                $this->module->l('Could not generate label : %s', 'AdminMondialrelayLabelsGenerationController'),
                $e->getMessage()
            );
            $this->warnings[] = $this->module->l('Please [a] Check requirements [/a] to verify if all settings are OK.', 'AdminMondialrelayLabelsGenerationController', array('href' => $this->context->link->getAdminLink('AdminMondialrelayHelp') . '#mondialrelay_requirements-results', 'target' => 'blank'));

            $actionsResult = $handler->getConveyor();
            if (!empty($actionsResult['errors'])) {
                $this->errors = array_merge($this->errors, $actionsResult['errors']);
            }

            return false;
        }
        
        // Get process result, set errors if any
        $actionsResult = $handler->getConveyor();
        if (!empty($actionsResult['errors'])) {
            $this->errors[] = $this->module->l('Could not generate label.', 'AdminMondialrelayLabelsGenerationController');
            $this->errors = array_merge($this->errors, $actionsResult['errors']);
            $this->warnings[] = $this->module->l('Please [a] Check requirements [/a] to verify if all settings are OK.', 'AdminMondialrelayLabelsGenerationController', array('href' => $this->context->link->getAdminLink('AdminMondialrelayHelp') . '#mondialrelay_requirements-results', 'target' => 'blank'));
            return false;
        } else {
            $this->confirmations[] = $this->module->l('Label generated; see the [a] "Labels History" [/a] tab to download it.', 'AdminMondialrelayLabelsGenerationController', array('href' => $this->context->link->getAdminLink('AdminMondialrelayLabelsHistory'), 'target' => 'blank'));
        }
    }
    
    
    /**
     * Prepare (get from DB) and send (return successes / errors), for multiple
     * orders
     */
    public function processBulkGenerateSelectionLabels()
    {
        $selectionIds = $this->boxes;
        
        $orderIds = array();
        $errorFormat = $this->module->l('Order %s : %s', 'AdminMondialrelayLabelsGenerationController');
        foreach ($selectionIds as $id_mondialrelay_selected_relay) {
            $selectedRelay = new MondialrelaySelectedRelay($id_mondialrelay_selected_relay);
            if (!Validate::isLoadedObject($selectedRelay)) {
                $this->errors[] = Tools::displayError(sprintf(
                    'The MondialrelaySelectedRelay object %s cannot be loaded (or found)',
                    $id_mondialrelay_selected_relay
                ));
                continue;
            }
        
            if (true !== ($error = $this->validateGeneration($selectedRelay))) {
                $this->errors[] = sprintf(
                    $errorFormat,
                    $selectedRelay->id_order,
                    $error
                );
                continue;
            }
            
            $orderIds[] = $selectedRelay->id_order;
        }
        
        // Create the handler
        $handler = new ActionsHandler();
        
        // Set input data
        $handler->setConveyor(array(
            // The Actions chain was designed with bulk actions in mind, so we
            // have to pass an array
            'order_ids' => $orderIds,
        ));
        
        // Set actions to execute
        $handler->addActions('prepareData', 'generateLabels', 'sendTrackingEmails');

        // Process actions chain
        try {
            $handler->process('GenerateLabels');
        } catch (\Exception $e) {
            $this->errors[] = sprintf(
                $this->module->l('Could not generate label : %s', 'AdminMondialrelayLabelsGenerationController'),
                $e->getMessage()
            );
            
            $actionsResult = $handler->getConveyor();
            
            if (!empty($actionsResult['errors'])) {
                $this->errors = array_merge($this->errors, $actionsResult['errors']);
            }
            
            $this->warnings[] = $this->module->l('Please [a] Check requirements [/a] to verify if all settings are OK.', 'AdminMondialrelayLabelsGenerationController', array('href' => $this->context->link->getAdminLink('AdminMondialrelayHelp') . '#mondialrelay_requirements-results', 'target' => 'blank'));
            return false;
        }
        
        // Get process result, set errors if any
        $actionsResult = $handler->getConveyor();
        if (!empty($actionsResult['errors'])) {
            $this->errors = array_merge($this->errors, $actionsResult['errors']);
            $this->warnings[] = $this->module->l('Please [a] Check requirements [/a] to verify if all settings are OK.', 'AdminMondialrelayLabelsGenerationController', array('href' => $this->context->link->getAdminLink('AdminMondialrelayHelp') . '#mondialrelay_requirements-results', 'target' => 'blank'));
            return false;
        } else {
            $this->confirmations[] = $this->module->l('Labels generated; see the [a] "Labels History" [/a] tab to download them.', 'AdminMondialrelayLabelsGenerationController', array('href' => $this->context->link->getAdminLink('AdminMondialrelayLabelsHistory'), 'target' => 'blank'));
        }
    }
    
    /**
     * Basic validation before generating a label
     *
     * @param MondialrelaySelectedRelay $selectedRelay
     * @return string|true
     */
    public function validateGeneration($selectedRelay)
    {
        if ($selectedRelay->expedition_num) {
            return $this->module->l('A label was already generated for this order.', 'AdminMondialrelayLabelsGenerationController');
        }
        
        if (!$selectedRelay->id_order || !Validate::isLoadedObject(new Order($selectedRelay->id_order))) {
            return $this->module->l('This MR order has no associated PrestaShop order.', 'AdminMondialrelayLabelsGenerationController');
        }
        
        if (!$selectedRelay->package_weight || $selectedRelay->package_weight < Mondialrelay::MINIMUM_PACKAGE_WEIGHT) {
            return sprintf(
                $this->module->l('You must set a weight for the order (15 grams minimum).', 'AdminMondialrelayLabelsGenerationController'),
                Mondialrelay::MINIMUM_PACKAGE_WEIGHT
            );
        }
        
        return true;
    }
}
