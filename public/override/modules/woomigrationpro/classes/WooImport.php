<?php

require_once _PS_MODULE_DIR_.'woomigrationpro/classes/'.'WooImport.php';
require_once _PS_MODULE_DIR_ . 'woomigrationpro/classes/' . 'WooClient.php';
require_once _PS_MODULE_DIR_ . 'woomigrationpro/classes/'."loggers/WooLogger.php";
require_once _PS_MODULE_DIR_ . 'woomigrationpro/classes/'."WooValidator.php";

class WooImportOverride extends WooImport{

    public function __construct(WooMigrationProProcess $process, $version, $url_cart, $force_ids, WooClient $client = null, WooQuery $query = null){
        parent::__construct($process, $version, $url_cart, $force_ids, $client, $query);
    }

    public function checkIsoCode($code)
    {
        try{
            if ($code == 'UK') {
                return 'GB';
            }
            if (!WooImport::isEmpty($code)) {
                $code2 = Country::getByIso($code);
                if (isset($code2)) {
                    $iso_code = $this->addCountry($code);
                } 
            } else {
                $iso_code = Configuration::get('PS_LOCALE_COUNTRY');
            }
            return $iso_code;
        }
        catch(\Exception $e){
            var_dump($e->getMessage());
        }
    }

    public function addCountry($iso_code)
    {
        $this->client->setPostData($this->query->getCountries());
        $this->client->serializeOn();
        if ($this->client->query()) {
            $county_and_continent = $this->client->getContent();
            foreach ($county_and_continent['continents'] as $key => $continents) {
                foreach ($continents as $continent) {
                    if (array_search("" . $iso_code . "", $continent)) {
                        $new_country = array('iso_code' => $iso_code, 'country' => $county_and_continent['countries'][$iso_code], 'continent' => $continents['name'], 'continen_code' => $key);
                    }
                }
            }
            return $this->importCountry($new_country);
        }
    }


    public function importCountry($country)
    {
        // import country
        if ($countryObject = new Country()) {
            $countryObject->id_zone = $this->getZoneId($country['continen_code']);
            $countryObject->id_currency = 0;
            $countryObject->call_prefix = 0;
            $countryObject->iso_code = $country['iso_code'];
            $countryObject->active = 1;
            $countryObject->contains_states = 0;
            $countryObject->need_identification_number = 0;
            $countryObject->need_zip_code = 0;
            $countryObject->display_tax_label = 1;
            $l = Configuration::get('PS_LANG_DEFAULT');
            $countryObject->name[$l] = $country['country'];
        }
        $res = false;
        $err_tmp = '';
        $this->validator->setObject($countryObject);
        $this->validator->checkFields();
        $countryerror_tmp = $this->validator->getValidationMessages();
        if ($countryObject->id && Country::existsInDatabase($countryObject->id, 'Country')) {
            try {
                $res = $countryObject->update();
            } catch (PrestaShopException $e) {
                $err_tmp = $e->getMessage();
            }
        }
        if (!$res) {
            try {
                $res = $countryObject->add(false);
            } catch (PrestaShopException $e) {
                $err_tmp = $e->getMessage();
            }
        }
        if (!$res) {
            $this->showMigrationMessageAndLog(sprintf($this->module->l('Country (ID: %1$s) cannot be saved. %2$s'), (isset($countryObject->id_country) && !WooImport::isEmpty($countryObject->id_country)) ? Tools::safeOutput($countryObject->iso_code) : 'No ID', $err_tmp), 'Country');
        }
        $this->showMigrationMessageAndLog($countryerror_tmp, 'Country');
    }


    public function getZoneId($country)
    {
        switch ($country) {
            case 'AF':
                $zone_id = 4;
                break;
            case 'AS':
                $zone_id = 3;
                break;
            case 'EU':
                $zone_id = 1;
                break;
            case 'NA':
                $zone_id = 2;
                break;
            case 'OC':
                $zone_id = 5;
                break;
            case 'SA':
                $zone_id = 6;
                break;
            case 'AN':
                $zone_id = 8;
                break;
            default:
                $zone_id = 1;
                break;
        }

        $sql = "SELECT zone.id_zone from " . _DB_PREFIX_ . "zone as zone where zone.id_zone=" . $zone_id;
        return Db::getInstance()->getValue($sql);
    }

    public function orders($orders, $orderDetails, $orderHistorys)
    {
        foreach ($orders['order'] as $cart) {
            if ($cartObject = $this->createObjectModel('Cart', $cart['ID'])) {
                $id_customer = 0;
                $cartObject->id_carrier = 2;   //  fix it after carrier import
                $cartObject->id_lang = Configuration::get('PS_LANG_DEFAULT');
                if (!WooImport::isEmpty($cart['_customer_user'])) {
                    $id_customer = self::getLocalID('Customer', $cart['_customer_user'], 'data');
                }
                if ($id_customer < 1) {
                    $ids_customers = Customer::getCustomersByEmail($orders['billing_address'][$cart['ID']]['_billing_email']);
                    if (count($ids_customers) > 0) {
                        $id_customer = $ids_customers[0]['id_customer'];
                    } else {
                        $id_customer = $this->createCustomer($cart['ID'], $orders);
                    }
                }
                //get shipping and billing adress off costumer.(becouse shipping inser to db first and then bill)
                if (!WooImport::isEmpty($id_customer)) {
                    $id_address_delivery = AddressCore::getFirstCustomerAddressId($id_customer);
                    $id_address_invoice = WooMigrationProConvertDataStructur::getSecondCustomerAddressId($id_customer);
                } else {
                    continue;
                }
                if ($id_address_delivery < 1 && $id_address_invoice < 1) {
                    $order_address = self::createAddress($id_customer, $orders['shipping_address'][$cart['ID']], $orders['billing_address'][$cart['ID']]);
                    $id_address_delivery = $order_address[0];
                    $id_address_invoice = $order_address[1];
                }
                $cartObject->id_address_delivery = $id_address_delivery;
                $cartObject->id_address_invoice = !WooImport::isEmpty($id_address_invoice) ? $id_address_invoice : $id_address_delivery;
                $currency = $cart['_order_currency'];
                $cartObject->id_currency = WooMigrationProMapping::listMapping(true, false, true)['currencies'][$currency] ? WooMigrationProMapping::listMapping(true, false, true)['currencies'][$currency] : Configuration::get('PS_CURRENCY_DEFAULT');
                $cartObject->id_customer = $id_customer;
                $cartObject->id_guest = $id_customer;
                $customer = new Customer((int) $id_customer);
                unset($id_customer);
                $cartObject->id_carrier = 1;
                if (!WooImport::isEmpty($customer->secure_key)) {
                    $cartObject->secure_key = $customer->secure_key;
                } else {
                    //if order not belong to customer.
                    $cartObject->secure_key = $this->secure_key = md5(uniqid(rand(), true));
                }
                $cartObject->gift_message = $cart['gift_message'];
                $cartObject->mobil_theme = $cart['mobil_theme'];
                $cartObject->allow_seperated_package = $cart['allow_seperated_package '];
                $cartObject->date_add = date('Y-m-d H:i:s', time());
                $cartObject->date_upd = date('Y-m-d H:i:s', time());
                $res = false;
                $err_tmp = '';
                $this->validator->setObject($cartObject);
                $this->validator->checkFields();
                $cart_error_tmp = $this->validator->getValidationMessages();

                if ($cartObject->id && Cart::existsInDatabase($cartObject->id, 'cart')) {
                    try {
                        $res = $cartObject->update();
                    } catch (PrestaShopException $e) {
                        $err_tmp = $e->getMessage();
                    }
                }
                if (!$res) {
                    try {
                        $res = $cartObject->add(false);
                    } catch (PrestaShopException $e) {
                        $err_tmp = $e->getMessage();
                    }
                }
                if (!$res) {
                    $this->showMigrationMessageAndLog(sprintf(WooImport::displayError($this->module->l('Cart (ID: %1$s) cannot be saved. %2$s')), (isset($cart['ID']) && !WooImport::isEmpty($cart['ID'])) ? Tools::safeOutput($cart['ID']) : 'No ID', $err_tmp), 'Cart');
                } else {
                    if (count($this->error_msg) == 0) {
                        self::addLog('cart', $cart['ID'], $cartObject->id);
                    }
                }
                $this->showMigrationMessageAndLog($cart_error_tmp, 'Cart');
            }
        }
        foreach ($orders['order'] as $order) {
            if ($orderModel = $this->createObjectModel('Order', $order['id_order'], 'orders')) {
                $id_customer = 0;
                if (!WooImport::isEmpty($order['_customer_user'])) {
                    $id_customer = self::getLocalID('Customer', $order['_customer_user'], 'data');
                }

                if ($id_customer < 1) {
                    $ids_customers = Customer::getCustomersByEmail($orders['billing_address'][$order['id_order']]['_billing_email']);
                    if (count($ids_customers) > 0) {
                        $id_customer = $ids_customers[0]['id_customer'];
                    } else {
                        $id_customer = self::getLocalID('Customer', $order['id_order'], 'data');
                    }
                }
                if (!WooImport::isEmpty($id_customer)) {
                    $id_address_delivery = AddressCore::getFirstCustomerAddressId($id_customer);
                    $id_address_invoice = WooMigrationProConvertDataStructur::getSecondCustomerAddressId($id_customer);
                    if ($id_address_delivery < 1) {
                        $id_address_delivery = WooMigrationProConvertDataStructur::firstCustomerAddressId($id_customer);
                    }
                } else {
                    continue;
                }
                $orderModel->id_address_delivery = $id_address_delivery;
                $orderModel->id_address_invoice = !WooImport::isEmpty($id_address_invoice) ? $id_address_invoice : $id_address_delivery;
                $orderModel->id_cart = self::getLocalID('Cart', $order['ID'], 'data');
                $currency = $order['_order_currency'];
                $orderModel->id_currency = WooMigrationProMapping::listMapping(true, false, true)['currencies'][$currency] ? WooMigrationProMapping::listMapping(true, false, true)['currencies'][$currency] : Configuration::get('PS_CURRENCY_DEFAULT');
                $orderModel->id_lang = Configuration::get('PS_LANG_DEFAULT');
                $orderModel->id_customer = $id_customer;
                $customer = new Customer((int) $id_customer);
                unset($id_customer);
                $orderModel->id_carrier = 1;
                if (!WooImport::isEmpty($customer->secure_key)) {
                    $orderModel->secure_key = $customer->secure_key;
                } else {
                    //if order not belong to customer.
                    $orderModel->secure_key = $this->secure_key = md5(uniqid(rand(), true));
                }
                if (WooImport::isEmpty($order['_payment_method_title'])) {
                    $orderModel->payment = 'QuickPay';
                } else {
                    $orderModel->payment = $order['_payment_method_title'];
                }
                $orderModel->module = 'cheque';
                $orderModel->recyclable = 0;
                $orderModel->total_paid = $order['_order_total'];
                $orderModel->total_paid_tax_incl = $order['_order_total'];
                $orderModel->total_paid_real = $order['_order_total'];
                $orderModel->total_products = $order['_order_total'] - ($order['_order_shipping'] + $order['_order_shipping_tax']);
                $orderModel->total_products_wt = $order['_order_total'] - ($order['_order_shipping'] + $order['_order_shipping_tax']);
                $orderModel->total_shipping = $order['_order_shipping'] + $order['_order_shipping_tax'];
                $orderModel->carrier_tax_rate = ($order['_order_shipping_tax'] * 100) / $order['_order_shipping'];
                $orderModel->conversion_rate = 0;
                $orderModel->valid = 1;
                $orderModel->date_add = $order['post_date'];
                $orderModel->date_upd = $order['post_modified'];
                $orderModel->total_shipping_tax_incl = $order['_order_shipping'] + $order['_order_shipping_tax'];
                $orderModel->total_shipping_tax_excl = $order['_order_shipping'];
                $orderModel->reference = "#" . $order['id_order'];
                $orderModel->id_shop = (int) Configuration::get('PS_SHOP_DEFAULT');
                $orderModel->total_paid = WooImport::wround($orderModel->total_paid);
                $orderModel->total_paid_real = WooImport::wround($orderModel->total_paid_real);
                $orderModel->total_products = WooImport::wround($orderModel->total_products);
                $orderModel->total_products_wt = WooImport::wround($orderModel->total_products_wt);
                $orderModel->total_shipping = WooImport::wround($orderModel->total_shipping);
                $orderModel->total_shipping_tax_incl = WooImport::wround($orderModel->total_shipping_tax_incl);
                $orderModel->total_shipping_tax_excl = WooImport::wround($orderModel->total_shipping_tax_excl);
                $orderModel->total_paid_tax_incl = WooImport::wround($orderModel->total_paid_tax_incl);
                $orderModel->current_state = WooMigrationProMapping::listMapping(true, false, true)['order_states'][$order['post_status']];
                $res = false;
                $err_tmp = '';
                $this->validator->setObject($orderModel);
                $this->validator->checkFields();
                $order_error_tmp = $this->validator->getValidationMessages();
                if ($orderModel->id && self::existsInDatabase($orderModel->id, 'orders', 'order')) {
                    try {
                        $res = $orderModel->update();
                    } catch (PrestaShopException $e) {
                        $err_tmp = $e->getMessage();
                    }
                }
                if (!$res) {
                    try {
                        $res = $orderModel->add(false);
                    } catch (PrestaShopException $e) {
                        $err_tmp = $e->getMessage();
                    }
                }
                if (!$res) {
                    $this->showMigrationMessageAndLog(sprintf(WooImport::displayError($this->module->l('Order (ID: %1$s) cannot be saved. %2$s')), (isset($order['id_order']) && !WooImport::isEmpty($order['id_order'])) ? Tools::safeOutput($order['id_order']) : 'No ID', $err_tmp), 'Order');
                } else {
                    //import Order Detail
                    foreach ($orderDetails[$order['id_order']] as $orderDetail) {
                        if ($orderDetailModel = $this->createObjectModel('OrderDetail', $orderDetail['order_item_id'])) {
                            $orderDetailModel->id_order = $orderModel->id;
                            $id_product = self::getLocalID('Product', (int) $orderDetail['_product_id'], 'data');
                            $product_attribute_id = self::getLocalID('Combination', (int) $orderDetail['_variation_id'], 'data');
                            $orderDetailModel->product_id = WooImport::isEmpty($id_product) ? $orderDetail['_product_id'] : $id_product;
                            $orderDetailModel->id_warehouse = "0";
                            $orderDetailModel->product_attribute_id = WooImport::isEmpty($product_attribute_id) ? $orderDetail['_variation_id'] : $product_attribute_id;
                            $orderDetailModel->product_name = $orderDetail['order_item_name'];
                            $orderDetailModel->product_quantity = $orderDetail['_qty'];
                            $orderDetailModel->product_quantity_in_stock = ProductCore::getQuantity($id_product, $product_attribute_id);
                            $orderDetailModel->product_quantity_return = $orderDetail['product_quantity_return'];
                            $orderDetailModel->product_quantity_refunded = $orderDetail['product_quantity_refunded'];
                            $orderDetailModel->product_quantity_reinjected = $orderDetail['product_quantity_reinjected'];
                            $orderDetailModel->product_price = Tools::ps_round($orderDetail['_line_total'] / $orderDetail['_qty'], 6);
                            $orderDetailModel->reduction_percent = $orderDetail['reduction_percent'];
                            $orderDetailModel->reduction_amount = $orderDetail['reduction_amount'];
                            $orderDetailModel->group_reduction = $orderDetail['group_reduction'];
                            $orderDetailModel->product_quantity_discount = $orderDetail['product_quantity_discount'];
                            $orderDetailModel->product_ean13 = $orderDetail['product_ean13'];
                            $orderDetailModel->product_upc = $orderDetail['product_upc'];
                            $orderDetailModel->product_reference = $orderDetail['order_item_name'];
                            $orderDetailModel->product_supplier_reference = $orderDetail['product_supplier_reference'];
                            $orderDetailModel->product_weight = $orderDetail['product_weight'];
                            $orderDetailModel->tax_name = $orderDetail['_tax_class'];
                            $orderDetailModel->tax_rate = $orderDetail['tax_amount'] * 100 / Tools::ps_round($orderDetail['_line_total'], 6);
                            $orderDetailModel->ecotax = $orderDetail['ecotax'];
                            $orderDetailModel->ecotax_tax_rate = $orderDetail['ecotax_tax_rate'];
                            $orderDetailModel->discount_quantity_applied = $orderDetail['discount_quantity_applied'];
                            $orderDetailModel->download_hash = $orderDetail['download_hash'];
                            $orderDetailModel->download_nb = $orderDetail['download_nb'];
                            $orderDetailModel->download_deadline = $orderDetail['download_deadline'];
                            $orderDetailModel->id_shop = Configuration::get('PS_SHOP_DEFAULT');
                            $orderDetailModel->unit_price_tax_excl = Tools::ps_round($orderDetail['_line_total'] / $orderDetail['_qty'], 6);
                            $orderDetailModel->unit_price_tax_incl = Tools::ps_round((Tools::ps_round($orderDetail['_line_total'], 6) / $orderDetail['_qty']) + ($orderDetail['_line_tax'] / $orderDetail['_qty']), 6);
                            $orderDetailModel->total_price_tax_excl = Tools::ps_round($orderDetail['_line_total'], 6);
                            $orderDetailModel->total_price_tax_incl = Tools::ps_round($orderDetail['_line_total'], 6) + Tools::ps_round($orderDetail['_line_tax'], 6);
                            $orderDetailModel->total_shipping_price_tax_excl = $orderDetail['cost'];
                            $orderDetailModel->total_shipping_price_tax_incl = $orderDetail['cost'] + $orderDetail['shipping_tax_amount'];


                            $res = false;
                            $err_tmp = '';
                            $this->validator->setObject($orderDetailModel);
                            $this->validator->checkFields();
                            $order_detail_error_tmp = $this->validator->getValidationMessages();
                            if ($orderDetailModel->id && OrderDetail::existsInDatabase(
                                $orderDetailModel->id,
                                'order_detail'
                            )) {
                                try {
                                    $res = $orderDetailModel->update();
                                } catch (PrestaShopException $e) {
                                    $err_tmp = $e->getMessage();
                                }
                            }
                            if (!$res) {
                                try {
                                    $res = $orderDetailModel->add(false);
                                } catch (PrestaShopException $e) {
                                    $err_tmp = $e->getMessage();
                                }
                            }
                            if (!$res) {
                                $this->showMigrationMessageAndLog(sprintf(WooImport::displayError($this->module->l('Order Detail (ID: %1$s) cannot be saved. %2$s')), (isset($orderDetail['id_order_detail']) && !WooImport::isEmpty($orderDetail['id_order_detail'])) ? Tools::safeOutput($orderDetail['id_order_detail']) : 'No ID', $err_tmp), 'OrderDetail');
                            } else {
                                self::addLog(
                                    'OrderDetail',
                                    $orderDetail['order_item_id'],
                                    $orderDetailModel->id
                                );
                            }
                            $this->showMigrationMessageAndLog($order_detail_error_tmp, 'OrderDetail');
                        }
                    }

                    // import Order History
                    foreach ($orderHistorys as $orderHistory) {
                        if ($orderHistory['id_order'] == $order['id_order']) {
                            if ($orderHistoryModel = $this->createObjectModel('OrderHistory', $orderHistory['id_order'])) {
                                $orderHistoryModel->id_order = $orderModel->id;
                                $orderHistoryModel->id_order_state = WooMigrationProMapping::listMapping(true, false, true)['order_states'][$order['post_status']];
                                $orderHistoryModel->id_employee = $orderHistory['id_employee'];
                                $orderHistoryModel->date_add = $orderHistory['date_add'];


                                $res = false;
                                $err_tmp = '';
                                $this->validator->setObject($orderHistoryModel);
                                $this->validator->checkFields();
                                $order_history_error_tmp = $this->validator->getValidationMessages();
                                if ($orderHistoryModel->id && OrderHistory::existsInDatabase($orderHistoryModel->id, 'order_history')) {
                                    try {
                                        $res = $orderHistoryModel->update();
                                    } catch (PrestaShopException $e) {
                                        $err_tmp = $e->getMessage();
                                    }
                                }
                                if (!$res) {
                                    try {
                                        $res = $orderHistoryModel->add(false);
                                    } catch (PrestaShopException $e) {
                                        $err_tmp = $e->getMessage();
                                    }
                                }
                                if (!$res) {
                                    $this->showMigrationMessageAndLog(sprintf(WooImport::displayError($this->module->l('Order History (ID: %1$s) cannot be saved. %2$s')), (isset($orderHistory['id_order_history']) && !WooImport::isEmpty($orderHistory['id_order_history'])) ? Tools::safeOutput($orderHistory['id_order_history']) : 'No ID', $err_tmp), 'OrderHistory');
                                } else {
                                    self::addLog('OrderHistory', $orderHistory['id_order'], $orderHistoryModel->id);
                                }
                                $this->showMigrationMessageAndLog($order_history_error_tmp, 'OrderHistory');
                            }
                        }
                    }
                    if (count($this->error_msg) == 0) {
                        Configuration::updateValue('woomigrationpro_order', $order['id_order']);
                        self::addLog('Order', $order['id_order'], $orderModel->id);
                    }
                }
                $this->showMigrationMessageAndLog($order_error_tmp, 'Order');
            }
        }

        //        update order invoice and shipping addresses

        foreach ($orders['billing_address'] as $address) {
            $id_customer = self::getLocalID('Customer', $address['_customer_user'], 'data');
            if (!WooImport::isEmpty($id_customer)) {
                $customer = new Customer($id_customer);
                $CustomerExistAdress = $customer->getAddresses(Configuration::get('PS_LANG_DEFAULT'));
                $addres1Array = $this->getCustomersAdress1Array($CustomerExistAdress);
                if ($billAddressObject = $this->createObjectModel('Address', $address['id'])) {
                    if (!in_array($address['_billing_address_1'], $addres1Array)) {
                        $id_customer = self::getLocalID('Customer', $address['_customer_user'], 'data');
                        $billAddressObject->id_customer = $id_customer;
                        //find country and state id from target
                        $iso_code = $this->checkIsoCode($address['_billing_country']);
                        if (!WooImport::isEmpty($iso_code)) {
                            if (Validate::isLanguageIsoCode($iso_code)) {
                                if (!WooImport::isEmpty(CountryCore::getByIso($iso_code))) {
                                    $billAddressObject->id_country = CountryCore::getByIso($iso_code);
                                    if (!WooImport::isEmpty($address['_billing_state'])) {
                                        if (!WooImport::isEmpty(StateCore::getIdByIso($address['billing_state']))) {
                                            $billAddressObject->id_state = StateCore::getIdByIso($address['_billing_state']);
                                        } else {
                                            $billAddressObject->id_state = 0;
                                        }
                                    } else {
                                        $billAddressObject->id_state = 0;
                                    }
                                } else {
                                    $this->showMigrationMessageAndLog(sprintf(WooImport::displayError('Address of Customer (ID: %1$s) ' . $iso_code . ' Country is not avilable on your database.'), (isset($address['_id_customer']) && !WooImport::isEmpty($address['_id_customer'])) ? Tools::safeOutput($address['_id_customer']) : 'No ID'), 'Address');
                                }
                            } else {
                                $this->showMigrationMessageAndLog(sprintf(WooImport::displayError('Address of Customer (ID: %1$s)' . $iso_code . ' Country Iso Code is not Valid.'), (isset($address['_id_customer']) && !WooImport::isEmpty($address['_id_customer'])) ? Tools::safeOutput($address['_id_customer']) : 'No ID'), 'Address');
                            }
                        } else {
                            $this->showMigrationMessageAndLog(sprintf(WooImport::displayError('Address of Customer (ID: %1$s) ' . $iso_code . ' Country Iso Code is Null.Address can not be saved.'), (isset($address['_id_customer']) && !WooImport::isEmpty($address['_id_customer'])) ? Tools::safeOutput($address['_id_customer']) : 'No ID'), 'Address');
                        }
                        $billAddressObject->alias = 'Adress Alias';
                        $billAddressObject->company = $address['_billing_company'];
                        $billAddressObject->lastname = self::checkEmptyProperty($address['_billing_last_name'], 'emptyLastname');
                        $billAddressObject->firstname = self::checkEmptyProperty(self::cleanString($address['_billing_first_name']), 'emptyFirstname');
                        $billAddressObject->address1 = self::checkEmptyProperty($address['_billing_address_1'], 'address 1');
                        $billAddressObject->address2 = self::checkEmptyProperty($address['_billing_address_2'], 'address 2');
                        $billAddressObject->postcode = $address['_billing_postcode'];
                        $billAddressObject->other = "bill";
                        $billAddressObject->city = self::checkEmptyProperty($address['_billing_city'], 'emptyCIty');
                        $billAddressObject->phone = self::checkEmptyProperty($address['_billing_phone'], '000 000 000');
                        $billAddressObject->date_add = date('Y-m-d H:i:s', time());
                        $billAddressObject->date_upd = date('Y-m-d H:i:s', time());
                        $res = false;
                        $err_tmp = '';
                        $this->validator->setObject($billAddressObject);
                        $this->validator->checkFields();
                        $billAddress_history_error_tmp = $this->validator->getValidationMessages();
                        if ($billAddressObject->id && Address::existsInDatabase($billAddressObject->id, 'address')) {
                            try {
                                $res = $billAddressObject->update();
                            } catch (PrestaShopException $e) {
                                $err_tmp = $e->getMessage();
                            }
                        }
                        if (!$res) {
                            try {
                                $res = $billAddressObject->add(false);
                            } catch (PrestaShopException $e) {
                                $err_tmp = $e->getMessage();
                            }
                        }
                        if (!$res) {
                            $this->showMigrationMessageAndLog(sprintf(WooImport::displayError($this->module->l('Address of Customer (ID: %1$s) cannot be saved. %2$s')), (isset($address['_id_customer']) && !WooImport::isEmpty($address['_id_customer'])) ? Tools::safeOutput($address['_id_customer']) : 'No ID', $err_tmp), 'Address');
                        } else {
                            self::addLog('Address', $address['id'], $billAddressObject->id);
                            $id_order = self::getLocalID('Order', $address['id_order'], 'data');
                            $order = new Order($id_order);
                            $order->id_address_invoice = $billAddressObject->id;
                            if (!WooImport::isEmpty($order->id_address_invoice)) {
                                $order->save();
                            }
                        }
                        $this->showMigrationMessageAndLog($billAddress_history_error_tmp, 'Address');
                    }
                }
            }
        }

        foreach ($orders['shipping_address'] as $address) {
            $id_customer = self::getLocalID('Customer', $address['_customer_user'], 'data');
            if (!WooImport::isEmpty($id_customer)) {
                $customer = new Customer($id_customer);
                $CustomerExistAdress = $customer->getAddresses(Configuration::get('PS_LANG_DEFAULT'));
                $addres1Array = $this->getCustomersAdress1Array($CustomerExistAdress);
                if ($shipAddressObject = $this->createObjectModel('Address', $address['id'])) {
                    if (!in_array($address['_shipping_address_1'], $addres1Array)) {
                        $id_customer = self::getLocalID('Customer', $address['_customer_user'], 'data');
                        $shipAddressObject->id_customer = $id_customer;
                        //find country and state id from target
                        $iso_code = $this->checkIsoCode($address['_shipping_country']);
                        if (!WooImport::isEmpty($iso_code)) {
                            if (Validate::isLanguageIsoCode($iso_code)) {
                                if (!WooImport::isEmpty(CountryCore::getByIso($iso_code))) {
                                    $shipAddressObject->id_country = CountryCore::getByIso($iso_code);
                                    if (!WooImport::isEmpty($address['_shipping_state'])) {
                                        if (!WooImport::isEmpty(StateCore::getIdByIso($address['_shipping_state']))) {
                                            $shipAddressObject->id_state = StateCore::getIdByIso($address['_shipping_state']);
                                        } else {
                                            $shipAddressObject->id_state = 0;
                                        }
                                    } else {
                                        $shipAddressObject->id_state = 0;
                                    }
                                } else {
                                    $this->showMigrationMessageAndLog(sprintf(WooImport::displayError('Address of Customer (ID: %1$s) ' . $iso_code . ' Country is not avilable on your database.'), (isset($address['_customer_user']) && !WooImport::isEmpty($address['_customer_user'])) ? Tools::safeOutput($address['_customer_user']) : 'No ID'), 'Address');
                                }
                            } else {
                                $this->showMigrationMessageAndLog(sprintf(WooImport::displayError('Address of Customer (ID: %1$s) ' . $iso_code . '  Country Iso Code is not Valid.'), (isset($address['_customer_user']) && !WooImport::isEmpty($address['_customer_user'])) ? Tools::safeOutput($address['_customer_user']) : 'No ID'), 'Address');
                            }
                        } else {
                            $this->showMigrationMessageAndLog(sprintf(WooImport::displayError('Address of Customer (ID: %1$s) ' . $iso_code . ' Country Iso Code is Null.Address can not be saved.'), (isset($address['_customer_user']) && !WooImport::isEmpty($address['_customer_user'])) ? Tools::safeOutput($address['_customer_user']) : 'No ID'), 'Address');
                        }
                        $shipAddressObject->alias = 'Adress Alias';
                        $shipAddressObject->company = $address['_shipping_company'];
                        $shipAddressObject->lastname = self::checkEmptyProperty($address['_shipping_last_name'], 'emptyLastname');
                        $shipAddressObject->firstname = self::checkEmptyProperty(self::cleanString($address['_shipping_first_name']), 'emptyFirstname');
                        $shipAddressObject->address1 = self::checkEmptyProperty($address['_shipping_address_1'], 'address 1');
                        $shipAddressObject->address2 = self::checkEmptyProperty($address['_shipping_address_2'], 'address 2');
                        $shipAddressObject->postcode = $address['_shipping_postcode'];
                        $shipAddressObject->other = "shiping_address";
                        $shipAddressObject->city = self::checkEmptyProperty($address['_shipping_city'], 'emptyCity');
                        $shipAddressObject->date_add = date('Y-m-d H:i:s', time());
                        $shipAddressObject->date_upd = date('Y-m-d H:i:s', time());
                        $res = false;
                        $err_tmp = '';
                        $this->validator->setObject($shipAddressObject);
                        $this->validator->checkFields();
                        $shipAddress_history_error_tmp = $this->validator->getValidationMessages();
                        if ($shipAddressObject->id && Address::existsInDatabase($shipAddressObject->id, 'address')) {
                            try {
                                $res = $shipAddressObject->update();
                            } catch (PrestaShopException $e) {
                                $err_tmp = $e->getMessage();
                            }
                        }
                        if (!$res) {
                            try {
                                $res = $shipAddressObject->add(false);
                            } catch (PrestaShopException $e) {
                                $err_tmp = $e->getMessage();
                            }
                        }
                        if (!$res) {
                            $this->showMigrationMessageAndLog(sprintf(WooImport::displayError($this->module->l('Address of Customer (ID: %1$s) cannot be saved. %2$s')), (isset($address['_customer_user']) && !WooImport::isEmpty($address['_customer_user'])) ? Tools::safeOutput($address['_customer_user']) : 'No ID', $err_tmp), 'Address');
                        } else {
                            self::addLog('Address', $address['id'], $shipAddressObject->id);
                            $id_order = self::getLocalID('Order', $address['id_order'], 'data');
                            $order = new Order($id_order);
                            $order->id_address_delivery = $shipAddressObject->id;
                            if (WooImport::isEmpty($order->id_address_delivery)) {
                                $order->save();
                            }
                        }
                        $this->showMigrationMessageAndLog($shipAddress_history_error_tmp, 'Address');
                    }
                }
            }
        }

        $this->updateProcess(count($orders['order']));
    }
}
