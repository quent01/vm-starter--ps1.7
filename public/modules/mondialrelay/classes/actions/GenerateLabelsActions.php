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
use MondialrelayClasslib\Actions\DefaultActions;
use MondialrelayClasslib\Extensions\ProcessLogger\ProcessLoggerHandler;

class GenerateLabelsActions extends DefaultActions
{
    /**
     * Prepares data for a call to the webservice, using an array of id_order
     * @return boolean
     */
    public function prepareData()
    {
        if (!isset($this->conveyor['errors'])) {
            $this->conveyor['errors'] = array();
        }
            
        $order_ids = $this->conveyor['order_ids'];
        if (empty($order_ids)) {
            $this->conveyor['errors'][] = $this->l('No data to prepare.', 'GenerateLabelsActions');
            return false;
        }
        
        $data = array();
        foreach ($order_ids as $id_order) {
            $order = new Order($id_order);
            if (!Validate::isLoadedObject($order)) {
                $this->conveyor['errors'][] = sprintf(
                    $this->l('Could not retrieve order from id : %s', 'GenerateLabelsActions'),
                    $id_order
                );
                continue;
            }
            
            $carrierMethod = MondialrelayCarrierMethod::getFromNativeCarrierId($order->id_carrier);
            if (!Validate::isLoadedObject($carrierMethod)) {
                $this->conveyor['errors'][] = sprintf(
                    $this->l('Order %s : Could not find Mondial Relay carrier method.', 'GenerateLabelsActions'),
                    $id_order
                );
                continue;
            }
            
            $selectedRelay = MondialrelaySelectedRelay::getFromIdCart($order->id_cart);
            if (!Validate::isLoadedObject($selectedRelay)) {
                $this->conveyor['errors'][] = sprintf(
                    $this->l('Order %s : Could not find Mondial Relay order.', 'GenerateLabelsActions'),
                    $id_order
                );
                continue;
            }
            
            // If the carrier needs a relay
            if ($carrierMethod->needsRelay()) {
                $address = new Address($selectedRelay->id_address_delivery);
                // If we don't have an address
                if (!Validate::isLoadedObject($address)) {
                    // If we have relay number, try to create the address
                    // We won't try to use the cart address, because it may be
                    // from the old module
                    if ($selectedRelay->selected_relay_num) {
                        $cart = new Cart($order->id_cart);

                        // Create the handler
                        $handler = new ActionsHandler();

                        // Set input data
                        $handler->setConveyor(array(
                            'enseigne' => Configuration::get(Mondialrelay::WEBSERVICE_ENSEIGNE),
                            'country_iso' => $selectedRelay->selected_relay_country_iso,
                            'relayNumber' => $selectedRelay->selected_relay_num,
                            'carrierMethod' => $carrierMethod,
                            'cart' => $cart,
                        ));
                        $handler->addActions('getRelayInformations', 'setSelectedRelay');

                        // Process actions chain
                        try {
                            $handler->process('SelectRelay');
                        } catch (\Exception $e) {
                            $phpError = $e->getFile() . ":" . $e->getLine() . " - " . $e->getMessage();
                            ProcessLoggerHandler::logError($phpError);
                            
                            $actionsResult = $handler->getConveyor();
            
                            if (empty($actionsResult['errors'])) {
                                $this->conveyor['errors'][] = sprintf(
                                    $this->l('Order %s : Could not create missing Mondial Relay address : %s', 'GenerateLabelsActions'),
                                    $id_order,
                                    $phpError
                                );
                                continue;
                            }
                            
                            $errorFormat = $this->l('Order %s : %s', 'GenerateLabelsActions');
                            foreach ($actionsResult['errors'] as $error) {
                                $this->conveyor['errors'][] = sprintf(
                                    $errorFormat,
                                    $id_order,
                                    $error
                                );
                            }
                            continue;
                        }

                        // Get process result, set errors if any
                        $actionsResult = $handler->getConveyor();
                        if (!empty($actionsResult['errors'])) {
                            $errorFormat = $this->l('Order %s : %s', 'GenerateLabelsActions');
                            foreach ($actionsResult['errors'] as $error) {
                                $this->conveyor['errors'][] = sprintf(
                                    $errorFormat,
                                    $id_order,
                                    $error
                                );
                            }
                            continue;
                        }
                        
                        // Reload order, selected relay
                        $order->clearCache();
                        $order = new Order($id_order);
                        $selectedRelay->clearCache();
                        $selectedRelay = new MondialrelaySelectedRelay($selectedRelay->id);
                        
                        // Load created address
                        $address = new Address($selectedRelay->id_address_delivery);
                    } else {
                        $this->conveyor['errors'][] = sprintf(
                            $this->l('Order %s : Could not find Mondial Relay address.', 'GenerateLabelsActions'),
                            $id_order
                        );
                        continue;
                    }
                }

                $customer = new Customer($selectedRelay->id_customer);
                if (!Validate::isLoadedObject($customer)) {
                    $this->conveyor['errors'][] = sprintf(
                        $this->l('Order %s : Could not find customer.', 'GenerateLabelsActions'),
                        $id_order
                    );
                    continue;
                }
                
                $dataLine = array(
                    'id_mondialrelay_selected_relay' => $selectedRelay->id,
                    'ModeLiv'                        => $carrierMethod->delivery_mode,
                    'NDossier'                       => $order->id,
                    'NClient'                        => $customer->id,
                    'Dest_Ad1'                       => Tools::replaceAccentedChars(Tools::substr($address->firstname . ' ' . $address->lastname, 0, 32)),
                    'Dest_Ad2'                       => $selectedRelay->selected_relay_adr2,
                    'Dest_Ad3'                       => $selectedRelay->selected_relay_adr3,
                    'Dest_Ad4'                       => $selectedRelay->selected_relay_adr4,
                    'Dest_Ville'                     => $selectedRelay->selected_relay_city,
                    'Dest_CP'                        => $selectedRelay->selected_relay_postcode,
                    'Dest_Pays'                      => $selectedRelay->selected_relay_country_iso,
                    'Dest_Tel1'                      => $address->phone,
                    'Dest_Tel2'                      => $address->phone_mobile,
                    'Dest_Mail'                      => $customer->email,
                    'Poids'                          => $selectedRelay->package_weight,
                    'LIV_Rel_Pays'                   => $selectedRelay->selected_relay_country_iso,
                    'LIV_Rel'                        => $selectedRelay->selected_relay_num,
                    'Assurance'                      => $selectedRelay->insurance_level,
                );
            } else {
                $address = new Address($order->id_address_delivery);
                if (!Validate::isLoadedObject($address)) {
                    $this->conveyor['errors'][] = sprintf(
                        $this->l('Order %s : Could not find delivery address.', 'GenerateLabelsActions'),
                        $id_order
                    );
                    continue;
                }

                $customer = new Customer($order->id_customer);
                if (!Validate::isLoadedObject($customer)) {
                    $this->conveyor['errors'][] = sprintf(
                        $this->l('Order %s : Could not find customer.', 'GenerateLabelsActions'),
                        $id_order
                    );
                    continue;
                }
                
                $dataLine = array(
                    'id_mondialrelay_selected_relay' => $selectedRelay->id,
                    'ModeLiv'                        => $carrierMethod->delivery_mode,
                    'NDossier'                       => $order->id,
                    'NClient'                        => $customer->id,
                    'Dest_Ad1'                       => Tools::replaceAccentedChars(Tools::substr($address->firstname . ' ' . $address->lastname, 0, 32)),
                    'Dest_Ad2'                       => Tools::replaceAccentedChars(Tools::substr($address->address2, 0, 32)),
                    'Dest_Ad3'                       => Tools::replaceAccentedChars(Tools::substr($address->address1, 0, 32)),
                    'Dest_Ville'                     => Tools::replaceAccentedChars($address->city),
                    'Dest_CP'                        => $address->postcode,
                    'Dest_Pays'                      => Country::getIsoById($address->id_country),
                    'Dest_Tel1'                      => $address->phone,
                    'Dest_Tel2'                      => $address->phone_mobile,
                    'Dest_Mail'                      => $customer->email,
                    'Poids'                          => $selectedRelay->package_weight,
                    'Assurance'                      => $selectedRelay->insurance_level,
                );
            }
            
            $data[] = $dataLine;
        }
        
        $this->conveyor['preparedData'] = $data;
        return true;
    }
    
    public function generateLabels()
    {
        if (!isset($this->conveyor['errors'])) {
            $this->conveyor['errors'] = array();
        }
            
        if (empty($this->conveyor['preparedData'])) {
            $this->conveyor['errors'][] = $this->l('No data to send.', 'GenerateLabelsActions');
            return false;
        }
        
        try {
            ProcessLoggerHandler::logInfo(sprintf(
                $this->l("Starting attempt to generate %d labels.", 'GenerateLabelsActions'),
                count($this->conveyor['preparedData'])
            ));
            $service = MondialrelayService::getService('Label_Generation');

            // Check shop address
            if (!$service->checkExpeAddress()) {
                $errors = $service->getErrors();
                foreach ($errors['generic'] as $error) {
                    ProcessLoggerHandler::logError($error);
                    $this->conveyor['errors'][] = $error;
                }
                ProcessLoggerHandler::saveLogsInDb();
                return false;
            }

            // Set data
            if (!$service->init($this->conveyor['preparedData'])) {
                $this->setErrorsFromService($this->conveyor['preparedData'], $service->getErrors());
                ProcessLoggerHandler::saveLogsInDb();
                return false;
            }

            // Send data
            if (!$service->send()) {
                $this->setErrorsFromService($this->conveyor['preparedData'], $service->getErrors());
                ProcessLoggerHandler::saveLogsInDb();
                return false;
            }

            $resultSet = $service->getResult();
            
            $this->conveyor['updatedOrders'] = array();
            foreach ($resultSet as $key => $result) {
                $selectedRelay = new MondialrelaySelectedRelay(
                    $this->conveyor['preparedData'][$key]['id_mondialrelay_selected_relay']
                );
                
                // If we failed to generate the label
                if ($result->STAT != 0) {
                    if (!Validate::isLoadedObject($selectedRelay)) {
                        $error = sprintf(
                            $this->l('A label could not be generated, and the associated MR order was not found : %s', 'GenerateLabelsActions'),
                            json_encode($this->conveyor['preparedData'][$key])
                        );
                        
                        ProcessLoggerHandler::logError($error);
                        $this->conveyor['errors'][] = $error;
                        $id_order = '?';
                    } else {
                        $id_order = $selectedRelay->id_order;
                    }
                    
                    $error = sprintf(
                        $this->l('Order %s : API error %d : %s', 'GenerateLabelsActions'),
                        $id_order,
                        $result->STAT,
                        $service->getErrorFromStatCode($result->STAT)
                    );
                    
                    ProcessLoggerHandler::logError($error);
                    $this->conveyor['errors'][] = $error;
                    continue;
                }
                
                // If the label was generated, we need to save ExpeditionNum and
                // URL_Etiquette
                if (!Validate::isLoadedObject($selectedRelay)) {
                    $error = sprintf(
                        $this->l('A label was generated, but the MR order to update was not found : %s', 'GenerateLabelsActions'),
                        json_encode($this->conveyor['preparedData'][$key])
                    );
                    
                    ProcessLoggerHandler::logError($error);
                    $this->conveyor['errors'][] = $error;
                    continue;
                }
                $id_order = $selectedRelay->id_order;
                
                $selectedRelay->expedition_num = $result->ExpeditionNum;
                $selectedRelay->setTrackingUrl(
                    Configuration::get(Mondialrelay::WEBSERVICE_ENSEIGNE),
                    Configuration::get(Mondialrelay::WEBSERVICE_BRAND_CODE),
                    Configuration::get('PS_LANG_DEFAULT'),
                    Configuration::get(Mondialrelay::WEBSERVICE_KEY)
                );
                $selectedRelay->label_url = Mondialrelay::URL_DOMAIN . $result->URL_Etiquette;
                $selectedRelay->date_label_generation = date('Y-m-d H:i:s');
                $selectedRelay->save();
                
                $order = new Order($id_order);
                if (!Validate::isLoadedObject($order)) {
                    $error = sprintf(
                        $this->l('A label was generated, but the PrestaShop order to update was not found : %s', 'GenerateLabelsActions'),
                        $id_order
                    );
                    
                    ProcessLoggerHandler::logError($error);
                    $this->conveyor['errors'][] = $error;
                    continue;
                }
                
                $orderCarrier = new OrderCarrier($order->getIdOrderCarrier());
                if (!Validate::isLoadedObject($orderCarrier)) {
                    $error = sprintf(
                        $this->l('A label was generated, but the PrestaShop order_carrier to update was not found : %s', 'GenerateLabelsActions'),
                        $id_order
                    );
                    
                    ProcessLoggerHandler::logError($error);
                    $this->conveyor['errors'][] = $error;
                } else {
                    $orderCarrier->tracking_number = $result->ExpeditionNum;
                    $orderCarrier->save();
                }
                
                // Change order state
                $newOrderStateId = (int)Configuration::get(Mondialrelay::OS_LABEL_GENERATED, null, null, $order->id_shop);
                if ($newOrderStateId) {
                    $orderHistory = new OrderHistory();
                    $orderHistory->id_order = $order->id;
                    $orderHistory->changeIdOrderState($newOrderStateId, $order->id);
                    $orderHistory->addWithemail();
                }
                
                $this->conveyor['updatedOrders'][] = $order;
            }
            
            if (count($this->conveyor['updatedOrders']) == count($this->conveyor['preparedData'])) {
                ProcessLoggerHandler::logSuccess(
                    $this->l('All labels generated.', 'GenerateLabelsActions')
                );
            } else {
                ProcessLoggerHandler::logError(sprintf(
                    $this->l('%d/%d labels generated.', 'GenerateLabelsActions'),
                    count($this->conveyor['updatedOrders']),
                    count($this->conveyor['preparedData'])
                ));
            }
            
            ProcessLoggerHandler::saveLogsInDb();
            return true;
        } catch (Exception $e) {
            if (isset($service)) {
                $this->setErrorsFromService($this->conveyor['preparedData'], $service->getErrors());
            }
            $error = sprintf(
                $this->l('An error occurred : %s', 'GenerateLabelsActions'),
                $e->getMessage()
            );
            
            ProcessLoggerHandler::logError($error);
            ProcessLoggerHandler::saveLogsInDb();
            $this->conveyor['errors'][] = $error;
            return false;
        }
    }
    
    public function sendTrackingEmails()
    {
        if (!isset($this->conveyor['errors'])) {
            $this->conveyor['errors'] = array();
        }

        if (empty($this->conveyor['updatedOrders'])) {
            $this->conveyor['errors'][] = $this->l('No updated orders to send emails.', 'GenerateLabelsActions');
            return false;
        }

        /** @var Order $order */
        foreach ($this->conveyor['updatedOrders'] as $order) {
            // If we don't have a selected relay...
            $selectedRelay = MondialrelaySelectedRelay::getFromIdCart($order->id_cart);
            if (!Validate::isLoadedObject($selectedRelay) || !$selectedRelay->tracking_url) {
                $this->conveyor['errors'][] = sprintf(
                    $this->l('Order %s does not exist or the label is not generated.', 'GenerateLabelsActions'),
                    $order->id
                );
            }

            // Get the path of theme by id_shop if exists
            $shop = new Shop((int)$order->id_shop);
            if (isset($shop->theme)) {
                // PS17
                $theme_name = $shop->theme->getName();
            } else {
                // PS16
                $theme_name = $shop->theme_name;
            }

            if (_THEME_NAME_ != $theme_name) {
                $theme_path = _PS_ROOT_DIR_.'/themes/'.$theme_name.'/';
            } else {
                $theme_path = _PS_ROOT_DIR_.'/themes/'._THEME_NAME_.'/';
            }

            $template_folder_path = _PS_MODULE_DIR_.'mondialrelay/mails/';
            $template_override_folder_path = $theme_path.'modules/mondialrelay/mails/';
            $template_name = 'order_tracking';

            // We'll try to find a mail with the order's language, the shop's
            // language, and english
            $iso_array = array(
                Language::getIsoById((int)$order->id_lang),
                Language::getIsoById(Configuration::get('PS_LANG_DEFAULT')),
                (Language::getIdByIso('en') ? 'en' : false),
            );

            $template_path = false;
            foreach ($iso_array as $iso) {
                if (!$iso) {
                    continue;
                }

                // Check theme module folder...
                if (file_exists($template_override_folder_path . '/' . $iso . '/' . $template_name . '.html')
                    && file_exists($template_override_folder_path . '/' . $iso . '/' . $template_name . '.txt')
                ) {
                    $template_path = $template_override_folder_path;
                    break;
                }

                // Check module folder...
                if (file_exists($template_folder_path . '/' . $iso . '/' . $template_name . '.html')
                    && file_exists($template_folder_path . '/' . $iso . '/' . $template_name . '.txt')
                ) {
                    $template_path = $template_folder_path;
                    break;
                }
            }

            if (!$template_path) {
                $error = $this->l('Order %s : no mail template found for language %s', 'GenerateLabelsActions');
                ProcessLoggerHandler::logError($error);
                $this->conveyor['errors'][] = sprintf(
                    $error,
                    $order->id,
                    Language::getIsoById($order->id_lang)
                );
                continue;
            }

            $subject  = $this->l('Tracking your order', false, (int)$order->id_lang).' - '.$order->getUniqReference();
            $deliveryAddress = new Address($order->id_address_delivery);
            $invoiceAddress = new Address($order->id_address_invoice);
            $customer = new Customer($order->id_customer);
            $carrier = new Carrier($order->id_carrier);

            $templateVars = array(
                '{firstname}' => $customer->firstname,
                '{lastname}' => $customer->lastname,
                '{email}' => $customer->email,
                '{delivery_block_txt}' => AddressFormat::generateAddress($deliveryAddress, array('avoid' => array()), "\n", ' ', array()),
                '{invoice_block_txt}' => AddressFormat::generateAddress($invoiceAddress, array('avoid' => array()), "\n", ' ', array()),
                '{delivery_block_html}' => AddressFormat::generateAddress($deliveryAddress, array('avoid' => array()), "<br/>", ' ', array(
                    'firstname'    => '<span style="font-weight:bold;">%s</span>',
                    'lastname'    => '<span style="font-weight:bold;">%s</span>'
                )),
                '{invoice_block_html}' => AddressFormat::generateAddress($invoiceAddress, array('avoid' => array()), "<br/>", ' ', array(
                    'firstname'    => '<span style="font-weight:bold;">%s</span>',
                    'lastname'    => '<span style="font-weight:bold;">%s</span>'
                )),
                '{order_name}' => $order->getUniqReference(),
                '{date}' => Tools::displayDate(date('Y-m-d H:i:s'), null, 1),
                '{carrier}' => $carrier->name,
                '{payment}' => Tools::substr($order->payment, 0, 32),
                '{mondialrelay_tracking_url}' => $selectedRelay->tracking_url,
            );

            $resultMail = Mail::Send(
                (int)Language::getIdByIso($iso),
                $template_name,
                $subject,
                $templateVars,
                $customer->email,
                $customer->firstname.' '.$customer->lastname,
                Configuration::get('PS_SHOP_EMAIL'),
                Configuration::get('PS_SHOP_NAME'),
                null,
                null,
                $template_folder_path
            );

            if (!$resultMail) {
                $error = $this->l('Order %s : Mail could not be sent', 'GenerateLabelsActions');
                ProcessLoggerHandler::logError($error);
                $this->conveyor['errors'][] = sprintf(
                    $error,
                    $order->id
                );
            }
        }
    }
    
    /**
     * Each order has its own data an errors set; so we need to assemble the two
     * to create a common errors array
     * @param type $preparedData
     * @param type $serviceErrors
     */
    protected function setErrorsFromService($preparedData, $serviceErrors)
    {
        $errorFormat = $this->l('Order %s : API response : %s', 'GenerateLabelsActions');
        $genericErrors = $serviceErrors['generic'];
        unset($serviceErrors['generic']);
        
        foreach ($serviceErrors as $key => $errors) {
            $selectedRelay = new MondialrelaySelectedRelay($preparedData[$key]['id_mondialrelay_selected_relay']);
            $id_order = Validate::isLoadedObject($selectedRelay) ? $selectedRelay->id_order : '?';
            
            foreach ($errors as $error) {
                $error = sprintf(
                    $errorFormat,
                    $id_order,
                    $error
                );
                ProcessLoggerHandler::logError($error, Order::class, $id_order);
                $this->conveyor['errors'][] = $error;
            }
        }
        
        foreach ($genericErrors as $error) {
            ProcessLoggerHandler::logError($error, Order::class, $id_order);
            $this->conveyor['errors'][] = $error;
        }
    }
}
