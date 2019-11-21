<?php
/**
 * NOTICE OF LICENSE
 *
 * @author    202 ecommerce <tech@202-ecommerce.com>
 * @author    Mondial Relay
 * @copyright Copyright (c) Mondial Relay
 * @license http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 */

use MondialrelayClasslib\Extensions\ProcessMonitor\Controllers\Front\CronController;
use MondialrelayClasslib\Extensions\ProcessLogger\ProcessLoggerHandler;

class MondialrelayOrdersStatusUpdateModuleFrontController extends CronController
{
    public $taskDefinition = array();
    
    public function __construct()
    {
        $this->taskDefinition = array(
            'name'      => 'mondialrelay:orders_update',
            'title'     => array(
                'en' => 'Orders status update',
                'fr' => 'Mise à jour des statuts de commande',
                $this->context->language->iso_code => $this->l('Orders status update', 'ordersStatusUpdate'),
            ),
            'frequency' => $this->l('6 hours (recommended)', 'ordersStatusUpdate'),
        );
        parent::__construct();
    }
    
    public function checkAccess()
    {
        if (Tools::getValue('deprecated_task') && Tools::getValue('secure_key') && Tools::getValue('secure_key') == Configuration::get(Mondialrelay::DEPRECATED_SECURE_KEY)) {
            return true;
        }
        return parent::checkAccess();
    }
    
    public function processCron($data)
    {
        ProcessLoggerHandler::openLogger($this->processMonitor);

        if (Tools::getValue('deprecated_task')) {
            ProcessLoggerHandler::logError(
                $this->module->l('You are using a deprecated CRON url. Please note that starting from the v3.1.0 of the module, you should change the URL of your Cron task. You can still use the old Cron task in the v3.0.x of the module. For getting the new Cron task URL please check the "Advanced Settings" tab.', 'ordersStatusUpdate')
            );
        }
        
        try {
            if (!$this->updateOrdersStatus()) {
                ProcessLoggerHandler::logError(
                    $this->module->l('Failed to update orders.', 'ordersStatusUpdate')
                );
            }
        } catch (Exception $ex) {
            ProcessLoggerHandler::logError(sprintf(
                $this->module->l('Failed to update orders : %s', 'ordersStatusUpdate'),
                $ex->getMessage()
            ));
        }
        
        try {
            if (!$this->cleanUnusedRelaySelections()) {
                ProcessLoggerHandler::logError(
                    $this->module->l('Failed to clean unused relay selections.', 'ordersStatusUpdate')
                );
            }
        } catch (Exception $ex) {
            ProcessLoggerHandler::logError(sprintf(
                $this->module->l('Failed to clean unused relay selections %s', 'ordersStatusUpdate'),
                $ex->getMessage()
            ));
        }
        
        ProcessLoggerHandler::closeLogger();
    }
    
    /**
     * Checks if order statuses were updated from the Mondial Relay API, and
     * updates our own if needed
     *
     * @return boolean
     */
    protected function updateOrdersStatus()
    {
        ProcessLoggerHandler::openLogger($this->processMonitor);
        ProcessLoggerHandler::logInfo($this->module->l('Start updating orders...', 'ordersStatusUpdate'));

        $newOrderStateId = (int)Configuration::get(Mondialrelay::OS_ORDER_DELIVERED);
        if (!$newOrderStateId) {
            ProcessLoggerHandler::logInfo(
                $this->module->l('No order status configured for delivered orders; aborting.', 'ordersStatusUpdate')
            );
            ProcessLoggerHandler::save();
            return true;
        }
        
        $selectedRelays = MondialrelaySelectedRelay::getAllUndeliveredWithLabel();
        if (empty($selectedRelays)) {
            ProcessLoggerHandler::logInfo(
                $this->module->l('No orders to update.', 'ordersStatusUpdate')
            );
            ProcessLoggerHandler::closeLogger();
            return true;
        }
        
        ProcessLoggerHandler::logInfo(sprintf(
            $this->module->l('%d to check...', 'ordersStatusUpdate'),
            count($selectedRelays)
        ));
        
        $params = array();
        foreach ($selectedRelays as $selectedRelay) {
            $params[] = array(
                'selectedRelay' => $selectedRelay,
                'Expedition' => $selectedRelay->expedition_num,
            );
        }
        
        $service = MondialrelayService::getService('Order_Trace');

        // Set data
        if (!$service->init($params)) {
            foreach ($this->formatServiceErrors($params, $service->getErrors()) as $error) {
                ProcessLoggerHandler::logError($error);
            }
            ProcessLoggerHandler::closeLogger();
            return false;
        }

        // Send data
        if (!$service->send()) {
            foreach ($this->formatServiceErrors($params, $service->getErrors()) as $error) {
                ProcessLoggerHandler::logError($error);
            }
            ProcessLoggerHandler::closeLogger();
            return false;
        }

        $resultSet = $service->getResult();
        
        foreach ($resultSet as $key => $result) {
            $selectedRelay = $params[$key]['selectedRelay'];

            // If we failed to retrieve the order
            if (!MondialrelayServiceTracingColis::isSuccessStatCode($result->STAT)) {
                ProcessLoggerHandler::logError(sprintf(
                    $this->module->l('Order %s : API error %d : %s', 'ordersStatusUpdate'),
                    $selectedRelay->id_order,
                    $result->STAT,
                    $service->getErrorFromStatCode($result->STAT)
                ));
                continue;
            }
            
            if ($result->STAT == MondialrelayServiceTracingColis::STAT_CODE_DELIVERED) {
                $history = new OrderHistory();
                $history->id_order = (int)$selectedRelay->id_order;
                $history->changeIdOrderState($newOrderStateId, (int)$selectedRelay->id_order);
                $history->addWithemail();
                ProcessLoggerHandler::logInfo(sprintf(
                    $this->module->l('Order %d updated.'),
                    $selectedRelay->id_order
                ));
                break;
            }
        }
        
        ProcessLoggerHandler::logInfo(
            $this->module->l('Finished updating orders.', 'ordersStatusUpdate')
        );
        
        ProcessLoggerHandler::closeLogger();
        return true;
    }
    
    /**
     * Each order has its own data an errors set; so we need to assemble the two
     * to create a common errors array
     * @param type $data
     * @param type $serviceErrors
     */
    protected function formatServiceErrors($data, $serviceErrors)
    {
        $errors = array();
        $errorFormat = $this->module->l('Order %s : API response : %s', 'ordersStatusUpdate');
        foreach ($serviceErrors as $key => $errors) {
            if ($key == 'generic') {
                foreach ($errors as $error) {
                    $errors[] = $error;
                }
                continue;
            }
            $selectedRelay = $data[$key]['selectedRelay'];
            $id_order = Validate::isLoadedObject($selectedRelay) ? $selectedRelay->id_order : '?';
            
            foreach ($errors as $error) {
                $errors[] = sprintf(
                    $errorFormat,
                    $id_order,
                    $error
                );
            }
        }
        
        return $errors;
    }
    
    protected function cleanUnusedRelaySelections()
    {
        ProcessLoggerHandler::logInfo(
            $this->module->l('Start cleaning unused relay selections...', 'ordersStatusUpdate')
        );

        // Delete unused addresses
        // Get "old" relay selections
        $query = new DbQuery();
        $query->select('mr_sr.*')
            ->from(MondialrelaySelectedRelay::$definition['table'], 'mr_sr')
            ->where('mr_sr.id_order IS NULL')
            ->where('DATE_ADD(mr_sr.date_upd, INTERVAL 1 DAY) < NOW()')
            ->where('(mr_sr.selected_relay_num IS NOT NULL AND mr_sr.selected_relay_num <> "")')
        ;
        
        $selectedRelaysData = Db::getInstance()->executeS($query);
        if (empty($selectedRelaysData)) {
            ProcessLoggerHandler::logInfo(
                $this->module->l('No relay selections to remove.', 'ordersStatusUpdate')
            );
            return true;
        }
        
        ProcessLoggerHandler::logInfo(sprintf(
            $this->module->l('%d selections to remove...', 'ordersStatusUpdate'),
            count($selectedRelaysData)
        ));
            
        foreach ($selectedRelaysData as $line) {
            $selectedRelay = new MondialrelaySelectedRelay();
            $selectedRelay->hydrate($line);

            if (!MondialrelaySelectedRelay::isUsedRelayAddress($selectedRelay->id_address_delivery)) {
                // Delete the address
                $address = new Address($selectedRelay->id_address_delivery);
                $address->delete();

                // Update cart if needed
                $cart = new Cart($selectedRelay->id_cart);
                if (Validate::isLoadedObject($cart) && $cart->id_address_delivery == $selectedRelay->id_address_delivery) {
                    ProcessLoggerHandler::logInfo(sprintf(
                        $this->module->l('Reset cart %d delivery option...', 'ordersStatusUpdate'),
                        $cart->id
                    ));

                    // Set any address from customer
                    $cart->updateAddressId($cart->id_address_delivery, (int)Address::getFirstCustomerAddressId((int)$cart->id_customer));
                    // Reset delivery option
                    $cart->setDeliveryOption(null);
                    $cart->save();
                }
            }

            // Delete selection
            $selectedRelay->delete();
        }
            
        ProcessLoggerHandler::logInfo(
            $this->module->l('Finished cleaning unused relay selections.', 'ordersStatusUpdate')
        );
        
        ProcessLoggerHandler::closeLogger();
        return true;
    }
}
