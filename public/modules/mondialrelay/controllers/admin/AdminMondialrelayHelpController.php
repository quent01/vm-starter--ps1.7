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

use MondialrelayClasslib\Install\ModuleInstaller;

class AdminMondialrelayHelpController extends AdminMondialrelayController
{
    /**
     * @see AdminController::setMedia()
     */
    public function setMedia($isNewTheme = false)
    {
        parent::setMedia($isNewTheme);

        $this->addJS($this->module->getPathUri() . '/views/js/admin/help.js');

        Media::addJsDef(array(
            'MONDIALRELAY_HELP' => array(
                'helpUrl' => $this->context->link->getAdminLink('AdminMondialrelayHelp'),
            )
        ));
    }
    
    public function initContent()
    {
        $this->content .= $this->createTemplate('offers.tpl')->fetch();
        $this->content .= $this->createTemplate('setup_guide.tpl')->fetch();
        $this->content .= $this->createTemplate('quick_help.tpl')->fetch();
        $this->content .= $this->createTemplate('contact_us.tpl')->fetch();
        
        parent::initContent();
    }
    
    public function ajaxProcessCheckRequirements()
    {
        // Check dependencies
        if (!MondialrelayTools::checkDependencies()) {
            $this->jsonError($this->module->l('SOAP and cURL should be installed on your server.', 'AdminMondialrelayHelpController'));
        } else {
            $this->jsonConfirmation($this->module->l('SOAP and cURL : OK', 'AdminMondialrelayHelpController'));
        }
        
        // Check address
        $service = MondialrelayService::getService('Label_Generation');
        $service->checkExpeAddress();
        $errors = $service->getErrors();
        
        if (!empty($errors['generic'])) {
            $this->jsonError(
                $this->createTemplate('list_errors.tpl')
                    ->assign(array(
                        'title' => $this->module->l('Please kindly correct the following errors on the contact page :', 'AdminMondialrelayHelpController'),
                        'errors'=> $errors['generic']
                    ))
                    ->fetch()
            );
        } else {
            $this->jsonConfirmation($this->module->l('Shop contact address : OK', 'AdminMondialrelayHelpController'));
        }
        
        // Check hooks
        $missingHooks = MondialrelayTools::getModuleMissingHooks();
        if (!empty($missingHooks)) {
            $this->jsonError(
                $this->createTemplate('missing_hooks_error.tpl')
                    ->assign(array(
                        'title' => $this->module->l('The module is not registered with the following hooks :', 'AdminMondialrelayHelpController'),
                        'errors'=> $missingHooks
                    ))
                    ->fetch()
            );
        } else {
            $this->jsonConfirmation($this->module->l('Hooks installation : OK', 'AdminMondialrelayHelpController'));
        }
    }
    
    public function ajaxProcessRegisterHooks()
    {
        $installer = new ModuleInstaller($this->module);
        try {
            if ($installer->registerHooks()) {
                $this->jsonConfirmation($this->module->l('Hooks successfully registered.', 'AdminMondialrelayHelpController'));
            } else {
                $this->jsonError($this->module->l('An unknown error occurred while registering hooks.', 'AdminMondialrelayHelpController'));
            }
        } catch (Exception $e) {
            $this->jsonError($this->module->l('An error occurred while registering hooks : %error%', 'AdminMondialrelayHelpController'), array('%error%' => $e->getMessage()));
        }
    }
}
