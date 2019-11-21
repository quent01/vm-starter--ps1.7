<?php
/**
 * NOTICE OF LICENSE
 *
 * @author    202 ecommerce <tech@202-ecommerce.com>
 * @author    Mondial Relay
 * @copyright Copyright (c) Mondial Relay
 * @license http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 */

use MondialrelayClasslib\Actions\DefaultActions;
use MondialrelayClasslib\Extensions\ProcessLogger\ProcessLoggerHandler;

class SelectRelayActions extends DefaultActions
{
    public function getRelayInformations()
    {
        if (!isset($this->conveyor['errors'])) {
            $this->conveyor['errors'] = array();
        }

        $params = array(
            'Enseigne' => $this->conveyor['enseigne'],
            'Pays' => Tools::strtoupper($this->conveyor['country_iso']),
            'NumPointRelais' => $this->conveyor['relayNumber'],
        );

        $service = MondialrelayService::getService('Relay_Infos');
        ProcessLoggerHandler::logInfo(sprintf(
            $this->l('Getting Point Relais %s-%s informations', 'SelectRelayActions'),
            $this->conveyor['country_iso'],
            $this->conveyor['relayNumber']
        ));

        // Set data
        if (!$service->init(array($params))) {
            $errors = $this->flattenServiceErrors($service->getErrors());
            foreach ($errors as $error) {
                ProcessLoggerHandler::logError($error);
            }
            $this->conveyor['errors'] = $errors;
            ProcessLoggerHandler::saveLogsInDb();
            return false;
        }

        // Send data
        if (!$service->send()) {
            $errors = $this->flattenServiceErrors($service->getErrors());
            foreach ($errors as $error) {
                ProcessLoggerHandler::logError($error);
            }
            $this->conveyor['errors'] = $errors;
            ProcessLoggerHandler::saveLogsInDb();
            return false;
        }

        $result = $service->getResult();

        $statCode = $result[0]->STAT;
        if ($statCode != 0) {
            $error = $service->getErrorFromStatCode($result[0]->STAT);
            ProcessLoggerHandler::logError($error);
            $this->conveyor['errors'][] = $error;
            return false;
        }
        
        if (empty($result[0]->PointsRelais)) {
            $error =  sprintf(
                $this->l('Could not find Point Relais %s', 'SelectRelayActions'),
                Tools::strtoupper($this->conveyor['country_iso']) . '-' . $this->conveyor['relayNumber']
            );
            ProcessLoggerHandler::logError($error);
            $this->conveyor['errors'][] = $error;
            ProcessLoggerHandler::saveLogsInDb();
            return false;
        }
        
        $this->conveyor['relayInfos'] = $result[0]->PointsRelais;
        return true;
    }

    public function setSelectedRelay()
    {
        if (!isset($this->conveyor['errors'])) {
            $this->conveyor['errors'] = array();
        }
            
        $cart = $this->conveyor['cart'];
        if (!Validate::isLoadedObject($cart)) {
            $error = $this->l('Invalid Cart', 'SelectRelayActions');
            ProcessLoggerHandler::logError($error);
            $this->conveyor['errors'][] = $error;
            ProcessLoggerHandler::saveLogsInDb();
            return false;
        }
        
        $carrier_method = $this->conveyor['carrierMethod'];
        if (!Validate::isLoadedObject($cart)) {
            $error = $this->l('Invalid Mondial Relay carrier method.', 'SelectRelayActions');
            ProcessLoggerHandler::logError($error, Cart::class, $cart->id);
            $this->conveyor['errors'][] = $error;
            ProcessLoggerHandler::saveLogsInDb();
            return false;
        }
        
        if (empty($this->conveyor['relayInfos'])) {
            $error = $this->l('No Point Relay informations specified.', 'SelectRelayActions');
            ProcessLoggerHandler::logError($error, Cart::class, $cart->id);
            $this->conveyor['errors'][] = $error;
            ProcessLoggerHandler::saveLogsInDb();
            return false;
        }
        $relayInfos = $this->conveyor['relayInfos'];
        
        $selectedRelay = MondialrelaySelectedRelay::getFromIdCart($cart->id);

        // Save the original delivery address, if it's not one of Mondial Relay's
        // address
        if ($cart->id_address_delivery && false === MondialrelaySelectedRelay::getAnyFromIdAddressDelivery($cart->id_address_delivery)) {
            $this->conveyor['id_original_address_delivery'] = $cart->id_address_delivery;
        }
        
        // Get new relay address if it exists
        $newRelayAddress = MondialrelaySelectedRelay::getCustomerRelayAddress(
            $cart->id_customer,
            trim($relayInfos->Num),
            trim($relayInfos->Pays)
        );
        
        // Get current order id
        if ($selectedRelay->id_order) {
            $id_currentOrder = $selectedRelay->id_order;
        } elseif (!empty($this->conveyor['id_order'])) {
            $id_currentOrder = $this->conveyor['id_order'];
        } else {
            $id_currentOrder = null;
        }
        
        // If the new relay's address already exists
        if ($newRelayAddress) {
            // If we have an old relay address but we've never used it before
            if ($selectedRelay->id_address_delivery
                && !MondialrelaySelectedRelay::isUsedRelayAddress(
                    $selectedRelay->id_address_delivery,
                    // Never used the address before, except for the order we're
                    // updating
                    $id_currentOrder
                )
            ) {
                // Delete the old address
                $oldRelayAddress = new Address($selectedRelay->id_address_delivery);
                $oldRelayAddress->delete();
            }
        } else {
            // If there's no existing address for the new relay
            // If we have an old relay address but we've never used it before
            if ($selectedRelay->id_address_delivery
                && MondialrelaySelectedRelay::isRelayAddress($selectedRelay->id_address_delivery)
                && !MondialrelaySelectedRelay::isUsedRelayAddress(
                    $selectedRelay->id_address_delivery,
                    // Never used the address before, except for the order we're
                    // updating
                    $id_currentOrder
                )
            ) {
                // We'll update the old relay's address
                $newRelayAddress = new Address($selectedRelay->id_address_delivery);
            } else {
                // If we don't have an old address or if it's already been used
                // We must create a new address
                $newRelayAddress = new Address();
            }
            
            // If we're going to use a never-used-before address, we need to set
            // the phone number
            if (!empty($this->conveyor['id_original_address_delivery'])) {
                $originalAddress = new Address($this->conveyor['id_original_address_delivery']);
                if (!Validate::isLoadedObject($originalAddress)) {
                    $this->conveyor['errors'][] = $this->l('Could not save phone number in address; please update your address manually so that your carrier may contact you if needed.', 'SelectRelayActions');
                } else {
                    $newRelayAddress->phone = $originalAddress->phone;
                    $newRelayAddress->phone_number = $originalAddress->phone_mobile;
                }
            }
        }
        
        // Most of the time, we want the address to be activated; but sometimes
        // we'll want to have it deleted (e.g. when modifying it from BO)
        $newRelayAddress->deleted = (int)!empty($this->conveyor['deleteAddress']);
        
        // Set the address fields and save it
        $this->setAddressFields($newRelayAddress, $cart, $relayInfos);
        if (!$newRelayAddress->save()) {
            $error = $this->l('Could not save selected Point Relais address data.', 'SelectRelayActions');
            ProcessLoggerHandler::logError($error, Cart::class, $cart->id);
            $this->conveyor['errors'][] = $error;
            ProcessLoggerHandler::saveLogsInDb();
            return false;
        }

        // Set the new address for the selected relay
        $selectedRelay->id_address_delivery = $newRelayAddress->id;
        
        $this->setSelectedRelayFields($selectedRelay, $cart, $carrier_method, $relayInfos);
        
        // When selecting a relay from BO, the native PS process may use the
        // ObjectModel::add() method, which will create 2 different lines in DB.
        // We need a way to prevent this function from saving the object
        // immediately.
        if (empty($this->conveyor['noRelaySave'])) {
            if (!$selectedRelay->save()) {
                $error = $this->l('Could not save selected Point Relais address ID.', 'SelectRelayActions');
                ProcessLoggerHandler::logError($error, Cart::class, $cart->id);
                $this->conveyor['errors'][] = $error;
                ProcessLoggerHandler::saveLogsInDb();
                return false;
            }
        }
        
        $this->conveyor['selectedRelay'] = $selectedRelay;
        
        // Update the delivery address...
        MondialrelayTools::setCartDeliveryAddress($cart, $selectedRelay->id_address_delivery);
        if (!$cart->save()) {
            $error = $this->l('Could not update cart with the selected Point Relais address.', 'SelectRelayActions');
            ProcessLoggerHandler::logError($error, Cart::class, $cart->id);
            $this->conveyor['errors'][] = $error;
            ProcessLoggerHandler::saveLogsInDb();
            return false;
        }
        
        // Refresh cache
        $cart->getDeliveryOptionList(null, true);
        
        // We must also update the cart's delivery option
        $cart->setDeliveryOption(array(
            $selectedRelay->id_address_delivery => $carrier_method->id_carrier.","
        ));
        if (!$cart->save()) {
            $error = $this->l('Could not update cart delivery option.', 'SelectRelayActions');
            ProcessLoggerHandler::logError($error, Cart::class, $cart->id);
            $this->conveyor['errors'][] = $error;
            ProcessLoggerHandler::saveLogsInDb();
            return false;
        }
        
        // If we have an order, update it as well
        if ($id_currentOrder) {
            $order = new Order($id_currentOrder);
            if (!Validate::isLoadedObject($order)) {
                $error = $this->l('Could not find order to update with the selected Point Relais address.', 'SelectRelayActions');
                ProcessLoggerHandler::logError($error, Cart::class, $cart->id);
                $this->conveyor['errors'][] = $error;
                ProcessLoggerHandler::saveLogsInDb();
                return false;
            }
            $order->id_address_delivery = $selectedRelay->id_address_delivery;
            if (!$order->save()) {
                $error = $this->l('Could not update order with the selected Point Relais address.', 'SelectRelayActions');
                ProcessLoggerHandler::logError($error, Cart::class, $cart->id);
                $this->conveyor['errors'][] = $error;
                ProcessLoggerHandler::saveLogsInDb();
                return false;
            }
        }
        
        return true;
    }
    
    /**
     * This is mostly from OrderController on PS 17; the OrderController
     * generates a checksum at the end of every process and saves it in the
     * cart. When started again, it checks the checksum and reinitializes the
     * checkout process if it doesn't match. So we have to regerenate the
     * checksum if we update the cart outside of the OrderController process.
     * @return boolean
     */
    public function updateCartChecksum()
    {
        if (!isset($this->conveyor['errors'])) {
            $this->conveyor['errors'] = array();
        }
            
        $cart = $this->conveyor['cart'];
        if (!Validate::isLoadedObject($cart)) {
            $this->conveyor['errors'][] = $this->l('Invalid Cart ID', 'SelectRelayActions');
            return false;
        }
        
        $dataQuery = new DbQuery();
        $dataQuery->select('checkout_session_data')
            ->from('cart')
            ->where('id_cart = '.(int)$cart->id)
        ;
        $rawData = Db::getInstance()->getValue($dataQuery);
        
        if (!$rawData) {
            $this->conveyor['errors'][] = $this->l('Could not retrieve checkout session data from cart.', 'SelectRelayActions');
            return false;
        }
        
        $cartChecksum = new CartChecksum(new AddressChecksum());
        $data = json_decode($rawData, true);
        $data['checksum'] = $cartChecksum->generateChecksum($cart);

        $updateResult = Db::getInstance()->execute(
            'UPDATE '._DB_PREFIX_.'cart SET checkout_session_data = "'.pSQL(json_encode($data)).'"
                WHERE id_cart = '.(int)$cart->id
        );
        
        if (!$updateResult) {
            $this->conveyor['errors'][] = $this->l('Could not update cart checkout session data.', 'SelectRelayActions');
            return false;
        }

        return true;
    }
    
    public function syncCartFromDeliveryOption()
    {
        if (!isset($this->conveyor['errors'])) {
            $this->conveyor['errors'] = array();
        }
            
        $cart = $this->conveyor['cart'];
        if (!Validate::isLoadedObject($cart)) {
            $this->conveyor['errors'][] = $this->l('Invalid Cart object', 'SelectRelayActions');
            return false;
        }
        
        $selectedRelay = $this->conveyor['selectedRelay'];
        if (!Validate::isLoadedObject($selectedRelay)) {
            $this->conveyor['errors'][] = $this->l('Invalid MondialrelaySelectedRelay object', 'SelectRelayActions');
            return false;
        }
        
        $delivery_option = $this->conveyor['deliveryOption'];
        if (empty($delivery_option) || !is_array($delivery_option)) {
            $this->conveyor['errors'][] = $this->l('Invalid delivery option', 'SelectRelayActions');
            return false;
        }
        
        /**
         * Basically, since we save our relay and have to update our cart using
         * AJAX, the DB might be desynchronized from the form that was just
         * submitted.
         * So we'll try to read the submitted delivery option, and manage the
         * delivery address and carrier ourselves.
         *
         * When PS receives a delivery option with a different address than the
         * one in the $cart->id_address_delivery, it will suppose that we are
         * trying to use multi-shipping, and save the delivery option as JSON
         * without updating any other fields. Which is a problem for us, since
         * the address in the form might be different than the one in $cart
         * after our AJAX call when selecting a relay.
         */
        $delivery_option_addresses_ids = array_keys($delivery_option);
        $delivery_option_address_id = $delivery_option_addresses_ids[0];

        $delivery_option_carriers = explode(',', $delivery_option[$delivery_option_address_id]);

        // If we're trying to use an MR carrier that requires a relay
        if (MondialrelayCarrierMethod::findMondialrelayCarrierIds($delivery_option_carriers, true)) {
            // If we're trying to use an MR address
            if ($delivery_option_address_id == $selectedRelay->id_address_delivery) {
                // Make sure the cart is using our relay delivery address
                MondialrelayTools::setCartDeliveryAddress($cart, $selectedRelay->id_address_delivery);
                $cart->save();
                
                // Refresh the cache...
                $cart->getDeliveryOptionList(null, true);

                // Use the original delivery option
                $cart->setDeliveryOption($delivery_option);

                $cart->save();
            } else {
                // If we're trying to use an MR carrier
                // If we're trying to use a non-MR address
                // Use our relay delivery address anyway, we're not going to
                // use a non-MR address as a relay
                MondialrelayTools::setCartDeliveryAddress($cart, $selectedRelay->id_address_delivery);
                $cart->save();
                
                // Refresh the cache...
                $cart->getDeliveryOptionList(null, true);

                // Re-update the cart with a "correct" delivery option
                $delivery_option[$selectedRelay->id_address_delivery] =
                        $delivery_option[$delivery_option_address_id];
                unset($delivery_option[$delivery_option_address_id]);
                $cart->setDeliveryOption($delivery_option);

                $cart->save();
            }
        } else {
            // If we're trying to use a non-MR carrier, or an MR carrier that
            // doesn't require a relay
            if ($delivery_option_address_id == $selectedRelay->id_address_delivery
                && $selectedRelay->selected_relay_num
            ) {
                // If we're trying to use an MR relay address
                // We need to update the cart with the original, "native"
                // delivery address if we have one; we might have replaced
                // it with our relay's address before
                // If we don't have one, just use any... As long as it's active
                $new_delivery_address_id = false;
                if (!empty($this->conveyor['id_original_delivery_address'])) {
                    $new_delivery_address_id = $this->conveyor['id_original_delivery_address'];
                } else {
                    $new_delivery_address_id = Address::getFirstCustomerAddressId($cart->id_customer);
                }
                
                if ($new_delivery_address_id) {
                    MondialrelayTools::setCartDeliveryAddress($cart, $new_delivery_address_id);
                    $cart->save();
                    
                    // Refresh the cache...
                    $cart->getDeliveryOptionList(null, true);

                    // Re-update the cart with a "correct" delivery option
                    $delivery_option[$new_delivery_address_id] =
                            $delivery_option[$delivery_option_address_id];
                    unset($delivery_option[$delivery_option_address_id]);
                    $cart->setDeliveryOption($delivery_option);

                    $cart->save();
                }
            } else {
                // If we're trying to use a non-MR carrier, or an MR carrier
                // that doesn't require a relay
                // If we're trying to use a non-MR address
                // It's possible we're using an MR address that was deleted;
                // make sure the delivery option has a valid address before
                // trying to use it
                $deliveryOptionAddress = new Address($delivery_option_address_id);
                if (Validate::isLoadedObject($deliveryOptionAddress)) {
                    // We need to update the cart with the delivery option's
                    // address; we might have replaced it with our relay's
                    // address before
                    MondialrelayTools::setCartDeliveryAddress($cart, $delivery_option_address_id);
                    $cart->save();

                    // Refresh the cache...
                    $cart->getDeliveryOptionList(null, true);

                    // Then save the delivery option again
                    $cart->setDeliveryOption($delivery_option);
                    $cart->save();
                } else {
                    // We need to update the cart with a valid delivery option,
                    // the delivery option with the deleted address was
                    // probably added in the cart...
                    MondialrelayTools::setCartDeliveryAddress($cart, $cart->id_address_delivery);
                    $cart->save();
                    
                    $delivery_option[$cart->id_address_delivery] =
                            $delivery_option[$delivery_option_address_id];
                    unset($delivery_option[$delivery_option_address_id]);
                    $cart->setDeliveryOption($delivery_option);

                    $cart->save();

                    // Refresh the cache...
                    $cart->getDeliveryOptionList(null, true);
                }
            }
                
            // Delete the address if it represents a relay and was never used
            // to place an order
            $selectedRelayAddress = new Address($selectedRelay->id_address_delivery);
            if (Validate::isLoadedObject($selectedRelayAddress)
                && MondialrelaySelectedRelay::isRelayAddress($selectedRelayAddress->id)
                && !MondialrelaySelectedRelay::isUsedRelayAddress($selectedRelayAddress->id)
            ) {
                $selectedRelayAddress->delete();
                $selectedRelay->id_address_delivery = null;
                $selectedRelay->selected_relay_num = null;
                $selectedRelay->save();
            }

            // If we're not trying to use a Mondial Relay carrier at all...
            if (!MondialrelayCarrierMethod::findMondialrelayCarrierIds($delivery_option_carriers)) {
                // Delete the relay selection completely
                $selectedRelay->delete();
            }
        }
        
        return true;
    }

    /**
     * @param $address
     * @param $cart object PS Cart
     * @param $relayInfos
     */
    protected function setAddressFields($address, $cart, $relayInfos)
    {
        $address->id_country = Country::getByIso(trim($relayInfos->Pays));
        $address->id_customer = $cart->id_customer;
        $old_address = new Address($cart->id_address_delivery);

        $address->alias = "Point Mondial Relay : " . $relayInfos->Pays . '-' . $relayInfos->Num;

        $customer = new Customer($cart->id_customer);

        $address->firstname = $customer->firstname;
        $address->lastname = $customer->lastname;

        $address->address1 = trim($relayInfos->LgAdr3);
        if (trim($relayInfos->LgAdr4)) {
            $address->address2 = trim($relayInfos->LgAdr4);
        }

        $address->postcode = trim($relayInfos->CP);
        $address->city = trim($relayInfos->Ville);

        // Save name in company, because the name is more important.
        $address->company = trim($relayInfos->LgAdr1);
        if (trim($relayInfos->LgAdr2)) {
            $address->company .= ' ' . trim($relayInfos->LgAdr2);
        }
        // Point relay is saved in alias and other, it's enough
        $address->other = $address->alias;
        // PR hasn't phone, so we copy customer previous address phone number
        if ($old_address->phone) {
            $address->phone = $old_address->phone;
        }
        if ($old_address->phone_mobile) {
            $address->phone_mobile = $old_address->phone_mobile;
        }

        // We have to truncate the fields, because PS will throw an error if
        // they're too long
        $truncateFields = array('alias', 'firstname', 'lastname', 'company', 'address1', 'address2', 'postcode', 'city');
        foreach ($truncateFields as $field) {
            if (isset(Address::$definition['fields'][$field]['size'])) {
                $address->{$field} = Tools::substr($address->{$field}, 0, Address::$definition['fields'][$field]['size']);
            }
        }
    }
        
    protected function setSelectedRelayFields($selectedRelay, $cart, $carrier_method, $relayInfos)
    {
        $selectedRelay->id_customer = $cart->id_customer;
        $selectedRelay->id_mondialrelay_carrier_method = $carrier_method->id;
        $selectedRelay->id_cart = $cart->id;
        $selectedRelay->insurance_level = $carrier_method->insurance_level;
        $selectedRelay->selected_relay_num = trim($relayInfos->Num);
        $selectedRelay->selected_relay_adr1 = trim($relayInfos->LgAdr1);
        $selectedRelay->selected_relay_adr2 = trim($relayInfos->LgAdr2);
        $selectedRelay->selected_relay_adr3 = trim($relayInfos->LgAdr3);
        $selectedRelay->selected_relay_adr4 = trim($relayInfos->LgAdr4);
        $selectedRelay->selected_relay_postcode = trim($relayInfos->CP);
        $selectedRelay->selected_relay_city = trim($relayInfos->Ville);
        $selectedRelay->selected_relay_country_iso = trim($relayInfos->Pays);
    }

    /**
     * The service may return errors for the whole service, or for specific
     * items. Since we only use one item at a time when selecting a relay, we
     * just need to flatten the returned error array
     * @param array $serviceErrors
     */
    protected function flattenServiceErrors($serviceErrors)
    {
        $errors = array();
        $genericErrors = $serviceErrors['generic'];
        unset($serviceErrors['generic']);
        
        foreach ($serviceErrors as $error) {
            $errors = array_merge($errors, $error);
        }
        
        // Make sure generic errors are at the end
        $errors = array_merge($errors, $genericErrors);
        return $errors;
    }
}
