<?php
/**
 * 2007-2019 ETS-Soft
 *
 * NOTICE OF LICENSE
 *
 * This file is not open source! Each license that you purchased is only available for 1 wesite only.
 * If you want to use this file on more websites (or projects), you need to purchase additional licenses. 
 * You are not allowed to redistribute, resell, lease, license, sub-license or offer our resources to any third party.
 * 
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade PrestaShop to newer
 * versions in the future. If you wish to customize PrestaShop for your
 * needs please contact us for extra customization service at an affordable price
 *
 *  @author ETS-Soft <etssoft.jsc@gmail.com>
 *  @copyright  2007-2019 ETS-Soft
 *  @license    Valid for 1 website (or project) for each purchase of license
 *  International Registered Trademark & Property of ETS-Soft
 */

include_once(_PS_MODULE_DIR_.'ets_woo2pres/classes/ReadXMl.php');
class Woo2PressExtraImport extends Module
{
    public $_module;
    public $imported;
	public	function __construct()
	{
		parent::__construct();
		if (!(isset($this->context)) || !$this->context)
            $this->context = Context::getContext();
        $this->_module = Module::getInstanceByName('ets_woo2pres');
	}
    // import
    public function importProductTag($file_xml)
    {
        $this->imported= (int)Db::getInstance()->getValue('SELECT number_import FROM '._DB_PREFIX_.'ets_woo2press_import_history WHERE id_import_history='.(int)$this->context->cookie->id_import_history);
        $readXML = new Woo2PressReadXML($file_xml);
        while($xml = $readXML->_readXML())
		{	
		    Configuration::updateValue('PS_WOO2PRESS_IMPORTING',_DB_PREFIX_.'product_tag');  
            if($xml->product_tag)
            {
                foreach($xml->product_tag as $producttag_xml)
                {
                    $id_product= $this->_module->getNewID('product',(int)$producttag_xml->id_product);
                    $id_tag = $this->_module->getNewID('tag',(int)$producttag_xml->id_tag);
                    if(version_compare(_PS_VERSION_, '1.6', '>='))
                    {
                        if(isset($producttag_xml->id_lang) && (int)$producttag_xml->id_lang)
                        {
                            $id_lang= $this->_module->getNewID('lang',$producttag_xml->id_lang);
                            if(!Db::getInstance()->getRow('SELECT * FROM '._DB_PREFIX_.'product_tag WHERE id_product="'.(int)$id_product.'" AND id_tag="'.(int)$id_tag.'"',0))
                            {
                                Db::getInstance()->execute('INSERT INTO '._DB_PREFIX_.'product_tag(id_product,id_tag,id_lang) values("'.(int)$id_product.'","'.(int)$id_tag.'","'.(int)$id_lang.'")');
                                $this->stopResetImport();
                            }    
                        }
                        else
                        {
                            if(!Db::getInstance()->getRow('SELECT * FROM '._DB_PREFIX_.'product_tag WHERE id_product="'.(int)$id_product.'" AND id_tag="'.(int)$id_tag.'"',0))
                            {
                                Db::getInstance()->execute('INSERT INTO '._DB_PREFIX_.'product_tag(id_product,id_tag) values("'.(int)$id_product.'","'.(int)$id_tag.'")');
                                $this->stopResetImport();
                            }
                        
                        }
                    }
                    else
                    {
                        if(!Db::getInstance()->getRow('SELECT * FROM '._DB_PREFIX_.'product_tag WHERE id_product="'.(int)$id_product.'" AND id_tag="'.(int)$id_tag.'"',0))
                        {
                            Db::getInstance()->execute('INSERT INTO '._DB_PREFIX_.'product_tag(id_product,id_tag) values("'.(int)$id_product.'","'.(int)$id_tag.'")');
                            $this->stopResetImport();
                        }    
                    }
                }
            }
            $readXML->deleteFileXML();
        }
        if($readXML->imported)
            Db::getInstance()->execute('UPDATE '._DB_PREFIX_.'ets_woo2press_import_history set currentindex=1 WHERE id_import_history='.(int)$this->context->cookie->id_import_history);
    }
    public function ImportCustomizedData($file_xml)
    {
        
        $this->imported= (int)Db::getInstance()->getValue('SELECT number_import FROM '._DB_PREFIX_.'ets_woo2press_import_history WHERE id_import_history='.(int)$this->context->cookie->id_import_history);
        $readXML = new Woo2PressReadXML($file_xml);
        while($xml = $readXML->_readXML())
		{	
		    Configuration::updateValue('PS_WOO2PRESS_IMPORTING',_DB_PREFIX_.'customized_data');  
            if($xml->customized_data)
            {
                foreach($xml->customized_data as $customizeddata_xml)
                {
                    if(isset($customizeddata_xml->id_module) && (int)$customizeddata_xml->id_module)
                        continue;
                    $id_customization= (int)$this->_module->getNewID('customization',(int)$customizeddata_xml->id_customization);
                    $index= (int)$this->_module->getNewID('customization_field',(int)$customizeddata_xml->index);
                    if(!Db::getInstance()->getRow('SELECT * FROM '._DB_PREFIX_.'customized_data WHERE id_customization="'.(int)$id_customization.'" AND index="'.(int)$index.'"',0) && $id_customization && $index)
                    {
                        Db::getInstance()->execute('INSERT INTO '._DB_PREFIX_.'customized_data(id_customization,type,index,value) VALUES("'.(int)$id_customization.'","'.(int)$customizeddata_xml->type.'","'.(int)$index.'","'.pSQL((string)$customizeddata_xml->value).'")');
                    }
                }
            }
            $readXML->deleteFileXML();
        }
        if($readXML->imported)
            Db::getInstance()->execute('UPDATE '._DB_PREFIX_.'ets_woo2press_import_history set currentindex=1 WHERE id_import_history='.(int)$this->context->cookie->id_import_history);
    }
    public function importCategoryGroup($category=false)
    {
        $this->imported= (int)Db::getInstance()->getValue('SELECT number_import FROM '._DB_PREFIX_.'ets_woo2press_import_history WHERE id_import_history='.(int)$this->context->cookie->id_import_history);
        if($category)
        {
            $categories= Db::getInstance()->executeS('SELECT * FROM '._DB_PREFIX_.'ets_woo2press_category_import WHERE id_import_history='.(int)$this->context->cookie->id_import_history,true,0);
            if($categories)
            {
                foreach($categories as $category)
                {
                    $id_category=$category['id_new'];
                    $groups= Db::getInstance()->executeS('SELECT * FROM '._DB_PREFIX_.'group');
                    if($groups)
                    {
                        foreach($groups as $group)
                        {
                            if(!Db::getInstance()->getRow('SELECT * FROM '._DB_PREFIX_.'category_group WHERE id_category="'.(int)$id_category.'" AND id_group="'.(int)$group['id_group'].'"',0))
                            {
                                Db::getInstance()->execute('INSERT INTO '._DB_PREFIX_.'category_group (id_category,id_group) VALUES("'.(int)$id_category.'","'.(int)$group['id_group'].'")');
                                $this->stopResetImport();
                            }
                        }
                    }
                }
            }
        }
        else
        {
            $groups= Db::getInstance()->executeS('SELECT * FROM '._DB_PREFIX_.'ets_woo2press_group_import WHERE id_import_history='.(int)$this->context->cookie->id_import_history);
            if($groups)
            {
                foreach($groups as $group)
                {
                    $id_group=$group['id_new'];
                    $categories= Db::getInstance()->executeS('SELECT * FROM '._DB_PREFIX_.'category');
                    if($categories)
                    {
                        foreach($categories as $category)
                        {
                            if(!Db::getInstance()->executeS('SELECT * FROM '._DB_PREFIX_.'category_group WHERE id_category="'.(int)$category['id_category'].'" AND id_group="'.(int)$id_group.'"'))
                            {
                                Db::getInstance()->execute('INSERT INTO '._DB_PREFIX_.'category_group (id_category,id_group) VALUES("'.(int)$category['id_category'].'","'.(int)$id_group.'")');
                                $this->stopResetImport();
                            }
                        }
                    }
                }
            }
        }
    }
    public function importCustomerGroup($file_xml)
    {
        $this->imported= (int)Db::getInstance()->getValue('SELECT number_import FROM '._DB_PREFIX_.'ets_woo2press_import_history WHERE id_import_history='.(int)$this->context->cookie->id_import_history);
        $readXML = new Woo2PressReadXML($file_xml);
        
        while($xml = $readXML->_readXML())
		{	
            Configuration::updateValue('PS_WOO2PRESS_IMPORTING',_DB_PREFIX_.'customer_group');
            if($xml->customer_group)
            {
                foreach($xml->customer_group as $customergroup_xml)
                {
                    $id_customer = (int)$this->_module->getNewID('customer',(int)$customergroup_xml->id_customer);
                    $id_group = (int)$this->_module->getNewID('group',(int)$customergroup_xml->id_group);
                    if(!Db::getInstance()->getRow('SELECT * FROM '._DB_PREFIX_.'customer_group WHERE id_customer="'.(int)$id_customer.'" AND id_group="'.(int)$id_group.'"',0))
                    {
                        Db::getInstance()->execute('INSERT INTO '._DB_PREFIX_.'customer_group (id_customer,id_group) VALUES("'.(int)$id_customer.'","'.(int)$id_group.'")');
                        $this->stopResetImport();
                    }
                }
            }
            $readXML->deleteFileXML();
        }
        if($readXML->imported)
            Db::getInstance()->execute('UPDATE '._DB_PREFIX_.'ets_woo2press_import_history set currentindex=1 WHERE id_import_history='.(int)$this->context->cookie->id_import_history);
    }
    public function importProductCategory($file_xml)
    {
        $this->imported= (int)Db::getInstance()->getValue('SELECT number_import FROM '._DB_PREFIX_.'ets_woo2press_import_history WHERE id_import_history='.(int)$this->context->cookie->id_import_history);
        $readXML = new Woo2PressReadXML($file_xml);
        while($xml = $readXML->_readXML())
		{
            Configuration::updateValue('PS_WOO2PRESS_IMPORTING',_DB_PREFIX_.'category_product');
            if($xml->category_product)
            {
                foreach($xml->category_product as $productcategory_xml)
                {
                    $id_product= (int)$this->_module->getNewID('product',(int)$productcategory_xml->id_product);
                    $id_category =(int)$this->_module->getNewID('category',(int)$productcategory_xml->id_category);
                    if(!$id_category)
                    {
                        $id_import_history = Context::getContext()->cookie->id_import_history;
                        $id_category = Db::getInstance()->getValue('SELECT id_category_default FROM '._DB_PREFIX_.'ets_woo2press_import_history WHERE id_import_history ="'.(int)$id_import_history.'"');
                    }
                    if(!Db::getInstance()->getRow('SELECT * FROM '._DB_PREFIX_.'category_product WHERE id_product="'.(int)$id_product.'" AND id_category="'.(int)$id_category.'"',0))
                    {
                        Db::getInstance()->execute('INSERT INTO '._DB_PREFIX_.'category_product (id_category,id_product) VALUES("'.(int)$id_category.'","'.(int)$id_product.'")');
                        $this->stopResetImport();
                    }
                }
            }
            $readXML->deleteFileXML();
        }
        if($readXML->imported)
            Db::getInstance()->execute('UPDATE '._DB_PREFIX_.'ets_woo2press_import_history set currentindex=1 WHERE id_import_history='.(int)$this->context->cookie->id_import_history);
    }
    public function importAccessory($file_xml)
    {
        $readXML = new Woo2PressReadXML($file_xml);
        while($xml = $readXML->_readXML())
		{
            Configuration::updateValue('PS_WOO2PRESS_IMPORTING',_DB_PREFIX_.'accessory');
            if($xml->accessory)
            {
                foreach($xml->accessory as $accessory_xml)
                {
                    $id_product_1= (int)$this->_module->getNewID('product',(int)$accessory_xml->id_product_1);
                    $id_product_2= (int)$this->_module->getNewID('product',(int)$accessory_xml->id_product_2);
                    if(!Db::getInstance()->getRow('SELECT * FROM '._DB_PREFIX_.'accessory WHERE id_product_1="'.(int)$id_product_1.'" AND id_product_2="'.(int)$id_product_2.'"',0))
                    {
                        Db::getInstance()->execute('INSERT INTO '._DB_PREFIX_.'accessory (id_product_1,id_product_2) VALUES ("'.(int)$id_product_1.'","'.(int)$id_product_2.'")');
                    }
                }
            }
            $readXML->deleteFileXML();
        }
        if($readXML->imported)
            Db::getInstance()->execute('UPDATE '._DB_PREFIX_.'ets_woo2press_import_history set currentindex=1 WHERE id_import_history='.(int)$this->context->cookie->id_import_history);
    }
    public function importProductAttributeCombination($file_xml)
    {
        $this->imported= (int)Db::getInstance()->getValue('SELECT number_import FROM '._DB_PREFIX_.'ets_woo2press_import_history WHERE id_import_history='.(int)$this->context->cookie->id_import_history);
        $readXML = new Woo2PressReadXML($file_xml);
        while($xml = $readXML->_readXML())
		{	
            Configuration::updateValue('PS_WOO2PRESS_IMPORTING',_DB_PREFIX_.'product_attribute_combination');
            if($xml->product_attribute_combination)
            {
                foreach($xml->product_attribute_combination as $productatributecombination_xml)
                {
                    $id_product_attribute= (int)$this->_module->getNewID('product_attribute',(int)$productatributecombination_xml->id_product_attribute);
                    $id_attribute = (int)$this->_module->getNewID('attribute',(int)$productatributecombination_xml->id_attribute);
                    if(!Db::getInstance()->getRow('SELECT * FROM '._DB_PREFIX_.'product_attribute_combination WHERE id_product_attribute ="'.(int)$id_product_attribute.'" AND id_attribute ="'.(int)$id_attribute.'"',0))
                    {
                        Db::getInstance()->execute('INSERT INTO '._DB_PREFIX_.'product_attribute_combination (id_product_attribute,id_attribute) VALUES("'.(int)$id_product_attribute.'","'.(int)$id_attribute.'")');
                        $this->stopResetImport();
                    }
                }
            }
            $readXML->deleteFileXML();
        }
        if($readXML->imported)
            Db::getInstance()->execute('UPDATE '._DB_PREFIX_.'ets_woo2press_import_history set currentindex=1 WHERE id_import_history='.(int)$this->context->cookie->id_import_history);
    }
    public function importFeatureProduct($file_xml)
    {
        $this->imported= (int)Db::getInstance()->getValue('SELECT number_import FROM '._DB_PREFIX_.'ets_woo2press_import_history WHERE id_import_history='.(int)$this->context->cookie->id_import_history);
        $readXML = new Woo2PressReadXML($file_xml);
        while($xml = $readXML->_readXML())
		{
            Configuration::updateValue('PS_WOO2PRESS_IMPORTING',_DB_PREFIX_.'feature_product');
            if($xml->feature_product)
            {
                foreach($xml->feature_product as $featureproduct_xml)
                {
                    $id_product= (int)$this->_module->getNewID('product',(int)$featureproduct_xml->id_product);
                    $id_feature =(int)$this->_module->getNewID('feature',(int)$featureproduct_xml->id_feature);
                    $id_feature_value =(int)$this->_module->getNewID('feature_value',(int)$featureproduct_xml->id_feature_value);
                    if(!Db::getInstance()->getRow('SELECT * FROM '._DB_PREFIX_.'feature_product WHERE id_product="'.(int)$id_product.'" AND id_feature="'.(int)$id_feature.'" AND id_feature_value ="'.(int)$id_feature_value.'"',0))
                    {
                        Db::getInstance()->execute('INSERT INTO '._DB_PREFIX_.'feature_product (id_feature,id_product,id_feature_value) VALUES ("'.(int)$id_feature.'","'.(int)$id_product.'","'.(int)$id_feature_value.'")');
                        $this->stopResetImport();
                    }
                }
            }
            $readXML->deleteFileXML();
        }
        if($readXML->imported)
            Db::getInstance()->execute('UPDATE '._DB_PREFIX_.'ets_woo2press_import_history set currentindex=1 WHERE id_import_history='.(int)$this->context->cookie->id_import_history);
    }
    public function importProductCarrier($file_xml)
    {
        $this->imported= (int)Db::getInstance()->getValue('SELECT number_import FROM '._DB_PREFIX_.'ets_woo2press_import_history WHERE id_import_history='.(int)$this->context->cookie->id_import_history);
        $readXML = new Woo2PressReadXML($file_xml);
        while($xml = $readXML->_readXML())
		{	
		    Configuration::updateValue('PS_WOO2PRESS_IMPORTING',_DB_PREFIX_.'product_carrier');  
            if($xml->product_carrier)
            {
                foreach($xml->product_carrier as $productcarrier_xml)
                {
                    $id_product= (int)$this->_module->getNewID('product',(int)$productcarrier_xml->id_product);
                    $id_carrier_reference = (int)$this->_module->getNewID('reference',(int)$productcarrier_xml->id_carrier_reference);
                    $id_shop =(int)$this->_module->getNewID('shop',(int)$productcarrier_xml->id_shop);
                    if(!$id_shop)
                        $id_shop = $this->context->shop->id;
                    if($id_product && $id_carrier_reference && $id_shop)
                    {
                        if(!Db::getInstance()->getRow('SELECT * FROM '._DB_PREFIX_.'product_carrier WHERE id_product="'.(int)$id_product.'" AND id_carrier_reference="'.(int)$id_carrier_reference.'" AND id_shop="'.(int)$id_shop.'"',0))
                        {
                            Db::getInstance()->execute('INSERT INTO '._DB_PREFIX_.'product_carrier(id_product,id_carrier_reference,id_shop) values("'.(int)$id_product.'","'.(int)$id_carrier_reference.'","'.(int)$id_shop.'")');
                            $this->stopResetImport();
                        }    
                    }
                }
            }
            $readXML->deleteFileXML();
        }
        if($readXML->imported)
            Db::getInstance()->execute('UPDATE '._DB_PREFIX_.'ets_woo2press_import_history set currentindex=1 WHERE id_import_history='.(int)$this->context->cookie->id_import_history);
    }
    public function importProductSupplier($file_xml)
    {
        $this->imported= (int)Db::getInstance()->getValue('SELECT number_import FROM '._DB_PREFIX_.'ets_woo2press_import_history WHERE id_import_history='.(int)$this->context->cookie->id_import_history);
        $readXML = new Woo2PressReadXML($file_xml);
        while($xml = $readXML->_readXML())
		{	
            Configuration::updateValue('PS_WOO2PRESS_IMPORTING',_DB_PREFIX_.'product_supplier');
            if($xml->product_supplier)
            {
                foreach($xml->product_supplier as $productsupplier_xml)
                {
                    $id_product= (int)$this->_module->getNewID('product',(int)$productsupplier_xml->id_product);
                    $id_product_attribute = (int)$this->_module->getNewID('product_attribute',(int)$productsupplier_xml->id_product_attribute);
                    $id_supplier =(int)$this->_module->getNewID('supplier',(int)$productsupplier_xml->id_supplier);
                    if(!$id_supplier)
                    {
                        $id_import_history = Context::getContext()->cookie->id_import_history;
                        $importHistory = new Woo2PressImportHistory($id_import_history);
                        $id_supplier = $importHistory->id_supplier;
                    }
                    $id_currency = (int)$this->_module->getNewID('currency',(int)$productsupplier_xml->id_currency);
                    if(!Db::getInstance()->getRow('SELECT * FROM '._DB_PREFIX_.'product_supplier WHERE id_supplier="'.(int)$id_supplier.'" AND id_product="'.(int)$id_product.'" AND id_product_attribute="'.(int)$id_product_attribute.'"',0))
                    {
                        Db::getInstance()->execute('INSERT INTO '._DB_PREFIX_.'product_supplier SET id_supplier="'.(int)$id_supplier.'", id_product="'.(int)$id_product.'", id_product_attribute="'.(int)$id_product_attribute.'", product_supplier_reference="'.pSQL((string)$productsupplier_xml->product_supplier_reference).'", product_supplier_price_te="'.(float)$productsupplier_xml->product_supplier_price_te.'",id_currency ="'.(int)$id_currency.'"');
                        $this->stopResetImport();
                    }
                }
            }
            $readXML->deleteFileXML();
        }
        if($readXML->imported)
            Db::getInstance()->execute('UPDATE '._DB_PREFIX_.'ets_woo2press_import_history set currentindex=1 WHERE id_import_history='.(int)$this->context->cookie->id_import_history);
    }
    public function ImportProductAttributeImages($file_xml)
    {
        $this->imported= (int)Db::getInstance()->getValue('SELECT number_import FROM '._DB_PREFIX_.'ets_woo2press_import_history WHERE id_import_history='.(int)$this->context->cookie->id_import_history);
        $readXML = new Woo2PressReadXML($file_xml);
        while($xml = $readXML->_readXML())
		{
		    Configuration::updateValue('PS_WOO2PRESS_IMPORTING',_DB_PREFIX_.'product_attribute_image');  
            if($xml->product_attribute_image)
            {
                foreach($xml->product_attribute_image as $productattributeimage_xml)
                {
                    $id_image= (int)$this->_module->getNewID('image',(int)$productattributeimage_xml->id_image);
                    $id_product_attribute = (int)$this->_module->getNewID('product_attribute',(int)$productattributeimage_xml->id_product_attribute);
                    if(!Db::getInstance()->executeS('SELECT * FROM '._DB_PREFIX_.'product_attribute_image WHERE id_image="'.(int)$id_image.'" AND id_product_attribute="'.(int)$id_product_attribute.'"'))
                    {
                        Db::getInstance()->execute('INSERT INTO '._DB_PREFIX_.'product_attribute_image SET id_image="'.(int)$id_image.'", id_product_attribute="'.(int)$id_product_attribute.'"');
                        $this->stopResetImport();
                    }
                }
            }
            $readXML->deleteFileXML();
        }
        if($readXML->imported)
            Db::getInstance()->execute('UPDATE '._DB_PREFIX_.'ets_woo2press_import_history set currentindex=1 WHERE id_import_history='.(int)$this->context->cookie->id_import_history);
    }
    public function importCarrierGroup()
    {
        $this->imported= (int)Db::getInstance()->getValue('SELECT number_import FROM '._DB_PREFIX_.'ets_woo2press_import_history WHERE id_import_history='.(int)$this->context->cookie->id_import_history);
        $carriers= Db::getInstance()->executeS('SELECT id_new FROM '._DB_PREFIX_.'ets_woo2press_carrier_import WHERE id_import_history='.(int)$this->context->cookie->id_import_history,true,0);
        if($carriers)
        {
            foreach($carriers as $carrier)
            {
                $id_carrier= $carrier['id_new'];
                $groups= Db::getInstance()->executeS('SELECT * FROM '._DB_PREFIX_.'group',true,0);
                if($groups)
                {
                    foreach($groups as $group)
                    {
                        if(!Db::getInstance()->getRow('SELECT * FROM '._DB_PREFIX_.'carrier_group WHERE id_group="'.(int)$group['id_group'].'" AND id_carrier="'.(int)$id_carrier.'"',0))
                        {
                            Db::getInstance()->execute('INSERT INTO '._DB_PREFIX_.'carrier_group (id_group,id_carrier) values("'.(int)$group['id_group'].'","'.(int)$id_carrier.'")');
                            $this->stopResetImport();
                        }
                    }
                }
            }
        }
    }
    public function importCarrierZone($file_xml)
    {
        $this->imported= (int)Db::getInstance()->getValue('SELECT number_import FROM '._DB_PREFIX_.'ets_woo2press_import_history WHERE id_import_history='.(int)$this->context->cookie->id_import_history);
        $readXML = new Woo2PressReadXML($file_xml);
        while($xml = $readXML->_readXML())
		{
            Configuration::updateValue('PS_WOO2PRESS_IMPORTING',_DB_PREFIX_.'carrier_zone');
            if($xml->carrier_zone)
            {
                foreach($xml->carrier_zone as $carrierzone_xml)
                {
                    $id_carrier= (int)$this->_module->getNewID('carrier',(int)$carrierzone_xml->id_carrier);
                    $id_zone = (int)$this->_module->getNewID('zone',(int)$carrierzone_xml->id_zone);
                    if(!Db::getInstance()->getRow('SELECT * FROM '._DB_PREFIX_.'carrier_zone WHERE id_carrier="'.(int)$id_carrier.'" AND id_zone="'.(int)$id_zone.'"',0))
                    {
                        Db::getInstance()->execute('INSERT INTO '._DB_PREFIX_.'carrier_zone SET id_carrier="'.(int)$id_carrier.'", id_zone="'.(int)$id_zone.'"');
                        $this->stopResetImport();
                    }
                }
            }
            $readXML->deleteFileXML();
        }
        if($readXML->imported)
            Db::getInstance()->execute('UPDATE '._DB_PREFIX_.'ets_woo2press_import_history set currentindex=1 WHERE id_import_history='.(int)$this->context->cookie->id_import_history);
    }
    public function importDataQuantity14($file_xml)
    {
        $this->imported= (int)Db::getInstance()->getValue('SELECT number_import FROM '._DB_PREFIX_.'ets_woo2press_import_history WHERE id_import_history='.(int)$this->context->cookie->id_import_history);
        $readXML = new Woo2PressReadXML($file_xml);
        while($xml = $readXML->_readXML())
		{	
            Configuration::updateValue('PS_WOO2PRESS_IMPORTING',_DB_PREFIX_.'product_attribute');
            if($xml->stock_available)
            {
                foreach($xml->stock_available as $stock_available_xml)
                {
                    $id_product_attribute=(int)$stock_available_xml->id_product_attribute;
                    $quantity= (int)$stock_available_xml->quantity;
                    if($id_product_attribute)
                    {
                        $id_combination = $this->_module->getNewID('product_attribute',(int)$id_product_attribute);
                        if($id_combination)
                        {
                            $combination = new Combination($id_combination);
                            $combination->quantity=(int)$quantity;
                            $combination->update();
                        }
                        
                    }
                    else
                    {
                        $id_product= $this->_module->getNewID('product',(int)$stock_available_xml->id_product);
                        if($id_product)
                        {
                            $product= new Product($id_product);
                            $product->quantity=$quantity;
                            $product->update();
                        }
                    }
                    //$this->stopResetImport();
                }
            }
            $readXML->deleteFileXML();
        }
        if($readXML->imported)
            Db::getInstance()->execute('UPDATE '._DB_PREFIX_.'ets_woo2press_import_history set currentindex=1 WHERE id_import_history='.(int)$this->context->cookie->id_import_history);
    }
    public function stopResetImport()
    {
        $this->imported++;
        Db::getInstance()->execute('UPDATE '._DB_PREFIX_.'ets_woo2press_import_history SET number_import ="'.(int)$this->imported.'" WHERE id_import_history='.(int)$this->context->cookie->id_import_history);
        if($this->imported%100==0)
        {
            die('imported='.$this->imported);
        }
    }
    public function importProductAttributeGenerator($file_xml)
    {
        $this->imported= (int)Db::getInstance()->getValue('SELECT number_import FROM '._DB_PREFIX_.'ets_woo2press_import_history WHERE id_import_history='.(int)$this->context->cookie->id_import_history);
        $readXML = new Woo2PressReadXML($file_xml);
        while($xml = $readXML->_readXML())
		{
            Configuration::updateValue('PS_WOO2PRESS_IMPORTING',_DB_PREFIX_.'product_attribute');
            if($xml->product_attribute)
            {
                foreach($xml->product_attribute as $product_atribute)
                {
                    $id_product= (int)$this->_module->getNewID('product',(int)$product_atribute['id_product']);
                    if($id_product)
                    {
                        $options=array();
                        if($product_atribute->attribute)
                        {
                            foreach($product_atribute->attribute as $attribute_xml)
                            {
                                $option=array();
                                $attributes= explode(',',(string)$attribute_xml);
                                if($attributes)
                                foreach($attributes as $attribute)
                                {
                                    $id_attribute= (int)$this->_module->getNewID('attribute',(int)$attribute);
                                    $option[$id_attribute]=$id_attribute;
                                }
                                if($option)
                                    $options[]=$option;    
                            }
                        }
                        if($options)
                        {
                            $this->product= new Product($id_product);
                            $this->processGenerate($options);
                        }
                    }
                    
                }
            }
            $readXML->deleteFileXML();
        }
        if($readXML->imported)
            Db::getInstance()->execute('UPDATE '._DB_PREFIX_.'ets_woo2press_import_history set currentindex=1 WHERE id_import_history='.(int)$this->context->cookie->id_import_history);
    }
    protected function addAttribute($attributes, $price = 0, $weight = 0)
    {
        foreach ($attributes as $attribute) {
            $price += (float)preg_replace('/[^0-9.-]/', '', str_replace(',', '.', Tools::getValue('price_impact_'.(int)$attribute)));
            $weight += (float)preg_replace('/[^0-9.]/', '', str_replace(',', '.', Tools::getValue('weight_impact_'.(int)$attribute)));
        }
        if ($this->product->id) {
            return array(
                'id_product' => (int)$this->product->id,
                'price' => (float)$price,
                'weight' => (float)$weight,
                'ecotax' => 0,
                'quantity' => (int)$this->product->quantity,
                'reference' => 'demo_'.$this->product->id,
                'default_on' => 0,
                'available_date' => '0000-00-00'
            );
        }
        return array();
    }
    public function processGenerate($options)
    {
        $tab = array_values($options);
        if (count($tab) && Validate::isLoadedObject($this->product)) {
            Woo2PressExtraImport::setAttributesImpacts($this->product->id, $tab);
            $this->combinations = array_values(Woo2PressExtraImport::createCombinations($tab));
            $values = array_values(array_map(array($this, 'addAttribute'), $this->combinations));

            // @since 1.5.0
            if ($this->product->depends_on_stock == 0) {
                $attributes = Product::getProductAttributesIds($this->product->id, true);
                foreach ($attributes as $attribute) {
                    StockAvailable::removeProductFromStockAvailable($this->product->id, $attribute['id_product_attribute'], Context::getContext()->shop);
                }
            }

            //SpecificPriceRule::disableAnyApplication();

            $this->product->deleteProductAttributes();
            $this->product->generateMultipleCombinations($values, $this->combinations);

            // Reset cached default attribute for the product and get a new one
            Product::getDefaultAttribute($this->product->id, 0, true);
            Product::updateDefaultAttribute($this->product->id);

            // @since 1.5.0
            if ($this->product->depends_on_stock == 0) {
                $attributes = Product::getProductAttributesIds($this->product->id, true);
                $quantity = (int)Tools::getValue('quantity');
                foreach ($attributes as $attribute) {
                    if (Shop::getContext() == Shop::CONTEXT_ALL) {
                        $shops_list = Shop::getShops();
                        if (is_array($shops_list)) {
                            foreach ($shops_list as $current_shop) {
                                if (isset($current_shop['id_shop']) && (int)$current_shop['id_shop'] > 0) {
                                    StockAvailable::setQuantity($this->product->id, (int)$attribute['id_product_attribute'], $quantity, (int)$current_shop['id_shop']);
                                }
                            }
                        }
                    } else {
                        StockAvailable::setQuantity($this->product->id, (int)$attribute['id_product_attribute'], $quantity);
                    }
                }
            } else {
                StockAvailable::synchronize($this->product->id);
            }

            //SpecificPriceRule::enableAnyApplication();
            SpecificPriceRule::applyAllRules(array((int)$this->product->id));
            Db::getInstance()->execute('UPDATE '._DB_PREFIX_.'ets_woo2press_import_history SET number_import = (number_import + 1) WHERE id_import_history='.(int)$this->context->cookie->id_import_history);
        }
    }
    protected static function setAttributesImpacts($id_product, $tab)
    {
        $attributes = array();
        foreach ($tab as $group) {
            foreach ($group as $attribute) {
                $price = preg_replace('/[^0-9.]/', '', str_replace(',', '.', Tools::getValue('price_impact_'.(int)$attribute)));
                $weight = preg_replace('/[^0-9.]/', '', str_replace(',', '.', Tools::getValue('weight_impact_'.(int)$attribute)));
                $attributes[] = '('.(int)$id_product.', '.(int)$attribute.', '.(float)$price.', '.(float)$weight.')';
            }
        }

        return Db::getInstance()->execute('
		INSERT INTO `'._DB_PREFIX_.'attribute_impact` (`id_product`, `id_attribute`, `price`, `weight`)
		VALUES '.implode(',', $attributes).'
		ON DUPLICATE KEY UPDATE `price` = VALUES(price), `weight` = VALUES(weight)');
    }
    protected static function createCombinations($list)
    {
        if (count($list) <= 1) {
            return count($list) ? array_map(create_function('$v', 'return (array($v));'), $list[0]) : $list;
        }
        $res = array();
        $first = array_pop($list);
        foreach ($first as $attribute) {
            $tab = Woo2PressExtraImport::createCombinations($list);
            foreach ($tab as $to_add) {
                $res[] = is_array($to_add) ? array_merge($to_add, array($attribute)) : array($to_add, $attribute);
            }
        }
        return $res;
    }
 }