<?php

if (!defined('_PS_VERSION_')) {
    exit;
}

require_once(_PS_MODULE_DIR_ . 'woomigrationpro/classes/loggers/WooMigrationProDBWarningLogger.php');
require_once(_PS_MODULE_DIR_ . 'woomigrationpro/classes/loggers/WooMigrationProDBErrorLogger.php');

class AdminWooMigrationProControllerOverride extends AdminWooMigrationProController{
    
    public function importTaxes($process)
    {
        $this->client->serializeOn();
        $this->client->setPostData($this->query->taxes());
        if ($this->client->query()) {
            $taxs = $this->client->getContent();
            $taxRulesGroups = $this->converTaxRulesGroupStructur($taxs['tax_rules']);
            $import = new WooImportOverride($process, $this->version, $this->url_cart, $this->forceIds, $this->client, $this->query);
            $import->taxes($taxs, $taxRulesGroups);
            $this->errors = $import->getErrorMsg();
            $this->warnings = $import->getWarningMsg();
            $this->response = $import->getResponse();
        } else {
            $this->errors[] = $this->l('Can\'t execute query to source Shop. ' . $this->client->getMessage());
        }
    }
    
    public function importManufacturers($process)
    {
        $this->client->serializeOn();
        $this->client->setPostData($this->query->manufactures());
        if ($this->client->query()) {
            $manufacturers = $this->client->getContent();
            $import = new WooImportOverride($process, $this->version, $this->url_cart, $this->forceManufacturerIds, $this->client, $this->query);
            $import->setImagePath($this->image_manufacturer);
            $import->manufacturers($manufacturers);
            $this->errors = $import->getErrorMsg();
            $this->warnings = $import->getWarningMsg();
            $this->response = $import->getResponse();
        } else {
            $this->errors[] = $this->l('Can\'t execute query to source Shop. ' . $this->client->getMessage());
        }
    }

    public function importCategories($process)
    {

        //@TODO find fix for PS 1.4 for category id 2 WHERE is ID 2 standart category from list
        $this->client->serializeOn();
        $this->client->setPostData($this->query->category($this->wpml));
        if ($this->client->query()) {
            $categories = $this->client->getContent();
            $import = new WooImportOverride(
                $process,
                $this->version,
                $this->url_cart,
                $this->forceCategoryIds,
                $this->client,
                $this->query
            );
            $import->setImagePath($this->image_category);
            $import->categories($categories);
            $this->errors = $import->getErrorMsg();
            $this->warnings = $import->getWarningMsg();
            $this->response = $import->getResponse();
        } else {
            $this->errors[] = $this->l('Can\'t execute query to source Shop. ' . $this->client->getMessage());
        }
    }

    public function importProducts($process)
    {
        $this->client->serializeOn();
        $this->client->setPostData($this->query->product($this->wpml));
        if ($this->client->query()) {
            $products = $this->client->getContent();
            $import = new WooImportOverride($process, $this->version, $this->url_cart, $this->forceProductIds, $this->client, $this->query);
            $import->setImagePath($this->url_cart . '/wp-content/uploads/');
            $import->setPricesIncludeTax($this->prices_include_tax);
            $import->setPricesIncludeTaxAutoload($this->prices_include_tax_autoload);
            $import->setDefaultTaxRate(Configuration::get($this->module->name . '_default_tax_rate'));
            $import->products($products);
            $this->errors = $import->getErrorMsg();
            $this->warnings = $import->getWarningMsg();
            $this->response = $import->getResponse();
        } else {
            $this->errors[] = $this->l(
                'Can\'t execute query to source Shop. ' . $this->client->getMessage()
            );
        }
    }
    
    public function importCustomers($process){
        $this->client->serializeOn();
        $this->client->setPostData($this->query->customers());
        if ($this->client->query()) {
            $customers = $this->client->getContent();
            $customers['customer'] = WooMigrationProConvertDataStructur::connectWcMetadataWithData($customers['customer_second'], $customers['customer'], 'id_customer');
            $import = new WooImportOverride($process, $this->version, $this->url_cart, $this->forceCustomerIds, $this->client, $this->query);
            $import->customers($customers['customer'], $customers['addresses']);
            $this->errors = $import->getErrorMsg();
            $this->warnings = $import->getWarningMsg();
            $this->response = $import->getResponse();
        } else {
            $this->errors[] = $this->l('Can\'t execute query to source Shop. ' . $this->client->getMessage());
        }
    }

    public function importOrders($process)
    {
        $this->client->serializeOn();
        $this->client->setPostData($this->query->order($this->woo_version));
        if ($this->client->query()) {
            $orders = $this->client->getContent();
            $orders['order'] = WooMigrationProConvertDataStructur::connectWcMetadataWithData($orders['order_detail'], $orders['order'], 'post_id', 'ID');
            $orderHistorys = $orders['order_history'];
            $orders['billing_address'] = WooMigrationProConvertDataStructur::convertOrderAddressStructure($orders['billing_address'], true);
            $orders['shipping_address'] = WooMigrationProConvertDataStructur::convertOrderAddressStructure($orders['shipping_address']);
            $orderDetails = WooMigrationProConvertDataStructur::connectWcMetadataWithData($orders['line'], $orders['order_item'], 'order_item_id', null, 'order_detail');
            if (!self::isEmpty($orders['shipping'])) {
                $orderDetails = WooMigrationProConvertDataStructur::connectOrderAdditional($orderDetails, $orders['shipping']);
            }
            if (!self::isEmpty($orders['tax'])) {
                $orderDetails = WooMigrationProConvertDataStructur::connectOrderAdditional($orderDetails, $orders['tax']);
            }
            $import = new WooImportOverride($process, $this->version, $this->url_cart, $this->forceOrderIds, $this->client, $this->query);
            $import->orders($orders, $orderDetails, $orderHistorys);
            $this->errors = $import->getErrorMsg();
            $this->warnings = $import->getWarningMsg();
            $this->response = $import->getResponse();
        } else {
            $this->errors[] = $this->l('Can\'t execute query to source Shop. ' . $this->client->getMessage());
        }
    }

}
