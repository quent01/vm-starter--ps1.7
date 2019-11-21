<?php
/**
 * NOTICE OF LICENSE
 *
 * @author    202 ecommerce <tech@202-ecommerce.com>
 * @author    Mondial Relay
 * @copyright Copyright (c) Mondial Relay
 * @license http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 */

use MondialrelayClasslib\Actions\ActionsHandler;

class MondialrelayAjaxCheckoutModuleFrontController extends ModuleFrontController
{
    /** @var string 'ok' or 'error' */
    public $status = '';

    /** @var array */
    public $warnings = array();

    /** @var array */
    public $informations = array();

    /** @var array */
    public $confirmations = array();

    /** @var array */
    public $errors = array();
    
    /** @var array */
    public $content = array();
    
    /**
     * @see AdminController
     * Shortcut to set up a json success payload
     *
     * @param string $message Success message
     */
    public function jsonConfirmation($message)
    {
        $this->json = true;
        $this->confirmations[] = $message;
        if ($this->status === '') {
            $this->status = 'ok';
        }
    }

    /**
     * @see AdminController
     * Shortcut to set up a json error payload
     *
     * @param string $message Error message
     */
    public function jsonError($message)
    {
        $this->json = true;
        $this->errors[] = $message;
        if ($this->status === '') {
            $this->status = 'error';
        }
    }
    
    protected function displayAjax()
    {
        $this->ajaxDie(Tools::jsonEncode(array(
            'status' => $this->status ? $this->status : 'ok',
            'warnings' => $this->warnings,
            'informations' => $this->informations,
            'confirmations' => $this->confirmations,
            'error' => $this->errors,
            'content' => $this->content,
        )));
    }

    /**
     * @see FrontController::postProcess()
     */
    public function postProcess()
    {
        parent::postProcess();
        if ($this->ajax) {
            $action = Tools::getValue('action');
            switch ($action) {
                case 'saveSelectedRelay':
                    $this->saveSelectedRelay();
                    break;
                default:
                    die();
            }
        }
    }
    
    /**
     * Saves the selected relay
     */
    protected function saveSelectedRelay()
    {
        // If Mondial Relay isn't set up...
        if (!MondialRelayTools::checkDependencies() || !MondialRelayTools::checkWebserviceConfiguration()) {
            $this->jsonError($this->l('This carrier has not been configured yet; please contact the merchant.', 'ajaxCheckout'));
            return false;
        }
        
        $cart = $this->context->cart;
        $id_carrier = Tools::getValue('id_carrier', $cart->id_carrier);
        
        // If we're not using a Mondial Relay carrier...
        $carrierMethod = MondialrelayCarrierMethod::getFromNativeCarrierId($id_carrier);
        if (!Validate::isLoadedObject($carrierMethod)) {
            $this->jsonError($this->module->l('This carrier is not registered with Mondial Relay.', 'ajaxCheckout'));
            return false;
        }
        
        // If the carrier doesn't need a relay...
        if (!$carrierMethod->needsRelay()) {
            $this->jsonError($this->module->l('This carrier does not need a relay selection.', 'ajaxCheckout'));
            return false;
        }
        
        // If we're not selecting a relay...
        $newRelay = Tools::getValue("mondialrelay_selectedRelay");
        if (!$newRelay) {
            $this->jsonError($this->module->l('Please select a Point Relais.', 'ajaxCheckout'));
            return false;
        }
        
        list($country_iso, $relayNumber) = explode('-', $newRelay);
        if (!$country_iso || !$relayNumber) {
            $this->jsonError($this->module->l('Please select a Point Relais.', 'ajaxCheckout'));
            return false;
        }

        // Create the handler
        $handler = new ActionsHandler();
        
        // Set input data
        $handler->setConveyor(array(
            'enseigne' => Configuration::get(Mondialrelay::WEBSERVICE_ENSEIGNE),
            'country_iso' => $country_iso,
            'relayNumber' => $relayNumber,
            'carrierMethod' => $carrierMethod,
            // We need to pass the whole cart, otherwise our modifications
            // may be overwritten if PS saves the cart during its process
            'cart' => $cart,
        ));
        $actions = array('getRelayInformations', 'setSelectedRelay');
        
        // On PS 17, we can't modify the cart without resetting it's checksum,
        // or the checkout process will start over (and possibly bug)
        if (version_compare(_PS_VERSION_, '1.7', '>=')) {
            $actions[] = 'updateCartChecksum';
        }
        
        // Set actions to execute
        call_user_func_array(array($handler, 'addActions'), $actions);

        // Process actions chain
        try {
            $handler->process('SelectRelay');
        } catch (\Exception $e) {
            $this->jsonError(sprintf(
                $this->module->l('Could not save selected Point Relais : %s', 'ajaxCheckout'),
                $e->getMessage()
            ));
            
            $actionsResult = $handler->getConveyor();
            
            if (empty($actionsResult['errors'])) {
                return false;
            }
            
            foreach ($actionsResult['errors'] as $error) {
                $this->jsonError($error);
            }
            
            return false;
        }
        
        // Get process result, set errors if any
        $actionsResult = $handler->getConveyor();
        if (!empty($actionsResult['errors'])) {
            $this->jsonError($this->module->l('Could not save selected Point Relais.', 'ajaxCheckout'));
            foreach ($actionsResult['errors'] as $error) {
                $this->jsonError($error);
            }
            return false;
        }
        
        // Save original delivery address in case we change carrier
        // Also warn the FO that the address has changed; the inputs might be
        // obsolete, and a page reload needed
        if (!empty($actionsResult['id_original_address_delivery'])) {
            $this->context->cookie->mondialrelay_id_original_delivery_address = $actionsResult['id_original_address_delivery'];
            $this->context->cookie->write();
        }
        
        // Render relay summary
        // Multi-shop / multi-theme might not work properly when using
        // the basic "$context->smarty->createTemplate($tpl_name)" syntax, as
        // the template's compile_id will be the same for every shop / theme
        // See https://github.com/PrestaShop/PrestaShop/pull/13804
        $scope = $this->context->smarty->createData($this->context->smarty);
        $scope->assign(array(
            'selectedRelay' => $actionsResult['selectedRelay'],
        ));
        
        if (isset($this->context->shop->theme)) {
            // PS17
            $themeName = $this->context->shop->theme->getName();
        } else {
            // PS16
            $themeName = $this->context->shop->theme_name;
        }

        $this->content['relaySummary'] = $this->context->smarty->createTemplate(
            $this->module->getTemplatePath('checkout/relay-summary.tpl'),
            $scope,
            $themeName
        )->fetch();
        
        return true;
    }
}
