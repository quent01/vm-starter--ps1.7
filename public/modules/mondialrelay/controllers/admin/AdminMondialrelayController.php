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

/**
 * Note : Most of this file was copied to
 * AdminMondialrelayAdvancedSettingsController because we couldn't inherit from
 * it
 */
abstract class AdminMondialrelayController extends ModuleAdminController
{
    public $bootstrap = true;
    
    /**
     * @var boolean Wether the Mondial Relay header should be display on the page
     */
    protected $with_mondialrelay_header = true;
    
    /**
     * @see parent::init()
     */
    public function init()
    {
        parent::init();

        $this->context->smarty->assign(array(
            'module_path' => $this->module->getPathUri(),
            'with_mondialrelay_header' => $this->with_mondialrelay_header,
            'help_link' => $this->context->controller->controller_name,
            'mondialrelay_carrier_settings_link' => $this->context->link->getAdminLink('AdminMondialrelayCarriersSettings'),
            'prestashop_carrier_settings_link' => $this->context->link->getAdminLink('AdminCarriers'),
            'advanced_settings_link' => $this->context->link->getAdminLink('AdminMondialrelayAdvancedSettings'),
            'store_contact_link' => $this->context->link->getAdminLink('AdminStores') . '#store_fieldset_contact',
            'prestashop_performance_url' => $this->context->link->getAdminLink('AdminPerformance'),
            'logs_link' => $this->context->link->getAdminLink('AdminMondialrelayProcessLogger'),
            'account_settings_link' => $this->context->link->getAdminLink('AdminMondialrelayAccountSettings'),
        ));
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
                'unknown_error' => $this->module->l('An unknown error occurred.', 'AdminMondialrelayController')
            )
        ));
    }

    /**
     * No action will ever be processed until both SOAP and cURL are installed.
     * @see AdminController::postProcess()
     */
    public function postProcess()
    {
        if (!MondialrelayTools::checkDependencies()) {
            $error = $this->module->l('SOAP and cURL should be installed on your server.', 'AdminMondialrelayController');

            if (!$this->ajax) {
                $this->errors[] = $error;
            } else {
                $this->json = true;
                $this->jsonError($error);
            }

            return false;
        }

        return parent::postProcess();
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
            case 'HelperForm':
                $this->tpl_form_vars['original_template'] = $this->helper->base_folder . $this->helper->base_tpl;
                break;
        }
    }

    /**
     * We also want to check if this abstract controller has a specific template
     * @see parent::createTemplate()
     *
     * @param string $tpl_name Template filename
     * @return Smarty_Internal_Template
     */
    public function createTemplate($tpl_name)
    {
        if (file_exists($this->getTemplatePath().'mondialrelay/'.$tpl_name) && $this->viewAccess()) {
            return $this->context->smarty->createTemplate($this->getTemplatePath().'mondialrelay/'.$tpl_name, $this->context->smarty);
        }

        return parent::createTemplate($tpl_name);
    }

    /**
     * Adds a default filter to a list, when none is set. Bear in mind that this
     * will prevent from selecting a "no filter" option !
     *
     * @param string $filter_key
     * @param string $value
     * @param string $force table name (if don't send table, list id is empty, when we call from other controller)
     */
    public function setDefaultFilter($filter_key, $value, $force = '')
    {
        $list_id = $force != '' ? $force : $this->list_id;
        $cookieKey = $this->getPrefix().$list_id.'Filter_'.$filter_key;
        if ($this->context->cookie->{$cookieKey} == '' || $force) {
            $this->context->cookie->{$cookieKey} = !is_array($value) ? $value : json_encode($value);
        }
    }

    /**
     * function getCookieFilterPrefix doesn't exist on early versions of prestashop 1.6.1.*
     */
    protected function getPrefix()
    {
        if (method_exists($this, 'getCookieFilterPrefix')) {
            return $this->getCookieFilterPrefix();
        } else {
            return str_replace(array('admin', 'controller'), '', Tools::strtolower(get_class($this)));
        }
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
         * Pull request was accepted by PrestaShop (08/07/2019) :
         * https://github.com/PrestaShop/PrestaShop/pull/14525
         *
         * We'll use our own template if needed...
         */
        if (version_compare(phpversion(), '7.2', '>=')
            && version_compare(_PS_VERSION_, '1.7.7', '<')
            && $this->layout == 'layout-ajax.tpl') {
            $this->layout = $this->getTemplatePath().'mondialrelay/layout-ajax.tpl';
        }
        return parent::display();
    }
}
