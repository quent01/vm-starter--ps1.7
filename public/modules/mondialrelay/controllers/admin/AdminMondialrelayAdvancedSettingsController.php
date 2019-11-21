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

require_once _PS_MODULE_DIR_ . '/mondialrelay/mondialrelay.php';
require_once _PS_MODULE_DIR_ . '/mondialrelay/classes/MondialrelayTools.php';
require_once _PS_MODULE_DIR_ . '/mondialrelay/controllers/admin/AdminMondialrelayLabelsGenerationController.php';

use MondialrelayClasslib\Extensions\ProcessMonitor\Controllers\Admin\AdminProcessMonitorController;

/**
 * We can't inherit from 2 classes, so we had to copy some codes from
 * AdminMondialrelayController
 */
class AdminMondialrelayAdvancedSettingsController extends AdminProcessMonitorController
{
    public $bootstrap = true;
    
    /** @var string We want to move the "list" panel from the top of the page to the bottom;
     * so when the parent class wants to render it, we actually put its HTML in
     * this variable then output it whenever we want
     *
     * @see self::renderList()
     */
    public $listHtml = '';
    
    /**
     * @see parent::init()
     */
    public function init()
    {
        parent::init();
        $this->context->smarty->assign(array(
            'module_path' => $this->module->getPathUri(),
            'mondialrelay_carrier_settings_link' => $this->context->link->getAdminLink('AdminMondialrelayCarriersSettings'),
            'account_settings_link' => $this->context->link->getAdminLink('AdminMondialrelayAccountSettings'),
            'advanced_settings_link' => $this->context->link->getAdminLink('AdminMondialrelayAdvancedSettings'),
            'prestashop_carrier_settings_link' => $this->context->link->getAdminLink('AdminCarriers'),
            'store_contact_link' => $this->context->link->getAdminLink('AdminStores') . '#store_fieldset_contact',
        ));

        $this->initOptions();
    }

    /**
     * @see AdminController::setMedia()
     */
    public function setMedia($isNewTheme = false)
    {
        if (version_compare(_PS_VERSION_, '1.7', '<')) {
            parent::setMedia();
        } else {
            parent::setMedia($isNewTheme);
        }
        
        $this->addCSS($this->module->getPathUri() . '/views/css/admin/global.css');
        $this->addJS($this->module->getPathUri() . '/views/js/admin/global.js');
        
        Media::addJsDef(array(
            'MONDIALRELAY_MESSAGES' => array(
                // Leave the "specific" as-is; we'll only have one translation this way.
                'unknown_error' => $this->module->l('An unknown error occurred.', 'AdminMondialrelayController')
            )
        ));
    }
    
    /**
     * Sets the fields for an "options" form
     */
    public function initOptions()
    {
        $orderStates = OrderState::getOrderStates($this->context->language->id);
        $orderStates[] = array(
            'id_order_state' => 0,
            'name' => $this->module->l('No status change', 'AdminMondialrelayController'),
        );
        
        $this->fields_options = array(
            'display_mode' => array(
                'title' => $this->module->l('Front Office : ParcelShops Display Mode', 'AdminMondialrelayAdvancedSettingsController'),
                'icon' => 'icon-cog',
                'description' => $this->createTemplate('display_mode_description.tpl')->fetch(),
                'fields' => array(
                    Mondialrelay::DISPLAY_MAP => array(
                        'type' => 'radio',
                        'title' => $this->module->l('Display Mode :', 'AdminMondialrelayAdvancedSettingsController'),
                        'choices' => array(
                            0 => $this->module->l('Normal', 'AdminMondialrelayAdvancedSettingsController'),
                            1 =>$this->module->l('Widget', 'AdminMondialrelayAdvancedSettingsController'),
                        ),
                    ),
                ),
                'submit' => array(
                    'title' => $this->module->l('Save', 'AdminMondialrelayAdvancedSettingsController'),
                )
            ),
            'order_statuses' => array(
                'title' => $this->module->l('Mondial Relay Order Status', 'AdminMondialrelayAdvancedSettingsController'),
                'icon' => 'icon-cog',
                'description' => $this->module->l('By default, Mondial Relay module uses "Processing in progress" order status for showing orders available for labels generation. Once labels are generated, orders pass to the "Shipped" status. For already delivered orders the module uses "Delivered" status. [br] Here you can change it and use your own system of order status associations :', 'AdminMondialrelayAdvancedSettingsController'),
                'fields' => array(
                    Mondialrelay::OS_DISPLAY_LABEL => array(
                        'type' => 'select',
                        'title' => $this->module->l('Order status to apply for showing orders available for labels generation :', 'AdminMondialrelayAdvancedSettingsController'),
                        'list' => $orderStates,
                        'identifier' => 'id_order_state',
                        'form_group_class' => 'mondialrelay_long-label'
                    ),
                    Mondialrelay::OS_LABEL_GENERATED => array(
                        'type' => 'select',
                        'title' => $this->module->l('Order status to apply after labels generation :', 'AdminMondialrelayAdvancedSettingsController'),
                        'list' => $orderStates,
                        'identifier' => 'id_order_state',
                    ),
                    Mondialrelay::OS_ORDER_DELIVERED => array(
                        'type' => 'select',
                        'title' => $this->module->l('Order status to apply for already delivered orders :', 'AdminMondialrelayAdvancedSettingsController'),
                        'list' => $orderStates,
                        'identifier' => 'id_order_state',
                    ),
                ),
                'submit' => array(
                    'title' => $this->module->l('Save', 'AdminMondialrelayAdvancedSettingsController'),
                )
            ),
        );
    }

    /**
     * No action will ever be processed until both SOAP and cURL are installed.
     * @see AdminController::postProcess()
     */
    public function postProcess()
    {
        if (!MondialrelayTools::checkDependencies()) {
            // Leave the "specific" as-is; we'll only have one translation this way.
            $error = $this->module->l('SOAP and cURL should be installed on your server.', 'AdminMondialrelayController');

            if (!$this->ajax) {
                $this->errors[] = $error;
            } else {
                $this->json = true;
                $this->jsonError($error);
            }

            return false;
        }
        //we need update default state filter each time we change configurations, otherwise we need reset filter
        if (Tools::isSubmit('submitOptionsmondialrelay_processmonitor')) {
            $labelGenerator = new AdminMondialrelayLabelsGenerationController();
            $labelGenerator->setDefaultFilter('o!current_state', Tools::getValue(Mondialrelay::OS_DISPLAY_LABEL), $labelGenerator->table);
        }
        return parent::postProcess();
    }
    
    /**
     * @see AdminProcessMonitorController::initContent()
     */
    public function initContent()
    {
        parent::initContent();

        /** @see self::$listHtml */
        $this->content .= $this->listHtml;

        $this->context->smarty->assign('content', $this->content);
    }

    /*
     * Fix; original template can't have a "required" label without a "hint" field...
     * Also add a "button" field type
     */
    public function setHelperDisplay(Helper $helper)
    {
        parent::setHelperDisplay($helper);

        $this->helper->module = $this->module;
        switch (get_class($this->helper)) {
            case 'HelperOptions':
                $this->tpl_option_vars['original_template'] = $this->helper->base_folder . $this->helper->base_tpl;
                break;
            case 'HelperList':
                $this->helper->title = $this->module->l('Task status', 'AdminMondialrelayAdvancedSettingsController');
                
                // The list may be rendered either from PS (with tpl_list_vars)
                // or by renderCronTasks (with $helper->tpl_vars)
                $this->tpl_list_vars['original_header'] = $this->helper->base_folder . 'list_header.tpl';
                $this->helper->tpl_vars['original_header'] = $this->helper->base_folder . 'list_header.tpl';
                break;
        }
    }
    
    public function renderCronTasks()
    {
        $oldListId = $this->list_id;
        $this->list_id = 'mondialrelay_cron-tasks';
        $tasksList = parent::renderCronTasks();
        $this->list_id = $oldListId;
        return $tasksList;
    }
    
    /**
     * @see AdminController::renderList
     * @see self::$listHtml
     *
     * @return string
     */
    public function renderList()
    {
        $this->listHtml = parent::renderList();
        return '';
    }
    
    /**
     * @inheritdoc
     */
    public function display()
    {
        /**
         * When responding to an AJAX request, the layout-ajax template throws
         * a warning on PHP 7.2 (because of the smarty "|count" function, see
         * https://wiki.php.net/rfc/counting_non_countables.
         *
         * So we'll use our own template...
         */
        if ($this->layout == 'layout-ajax.tpl') {
            $this->layout = $this->getTemplatePath().'mondialrelay/layout-ajax.tpl';
        }
        return parent::display();
    }
}
