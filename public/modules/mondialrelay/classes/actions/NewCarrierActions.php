<?php
/**
 * NOTICE OF LICENSE
 *
 * @author    202 ecommerce <tech@202-ecommerce.com>
 * @author    Mondial Relay
 * @copyright Copyright (c) Mondial Relay
 * @license http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 */

require_once _PS_MODULE_DIR_ . '/mondialrelay/classes/MondialrelayCarrierMethod.php';

use MondialrelayClasslib\Actions\DefaultActions;

class NewCarrierActions extends DefaultActions
{
    public function setConveyor($conveyorData)
    {
        if (!isset($conveyorData['errors'])) {
            $conveyorData['errors'] = array();
        }
        parent::setConveyor($conveyorData);
    }
    
    protected function getCarrierFromConveyor()
    {
        if (empty($this->conveyor['carrier']) && !empty($this->conveyor['id_carrier'])) {
            $carrier = new Carrier($this->conveyor['id_carrier']);
        } else {
            $carrier = $this->conveyor['carrier'];
        }
        if (!$carrier || !Validate::isLoadedObject($carrier)) {
            return false;
        }
        return $carrier;
    }
    
    public function addNativeCarrier()
    {
        if (empty($this->conveyor['name'])) {
            return false;
        }
        if (empty($this->conveyor['delay'])) {
            return false;
        }

        // Create carrier
        $carrier = new Carrier();

        $delay_lang = array();
        foreach (Language::getLanguages(false) as $lang) {
            $delay_lang[$lang['id_lang']] = $this->conveyor['delay'];
        }

        $carrier->name                 = $this->conveyor['name'];
        $carrier->active               = 1;
        $carrier->range_behavior       = 1;
        $carrier->need_range           = 1;
        $carrier->external_module_name = 'mondialrelay';
        $carrier->shipping_method      = Carrier::SHIPPING_METHOD_WEIGHT;
        $carrier->delay                = $delay_lang;
        $carrier->is_module            = 1;
        $carrier->id_shop_list         = MondialrelayTools::getShopsWithModuleEnabled(
            Module::getModuleIdByName('mondialrelay'),
            Shop::getContextListShopID()
        );
        
        try {
            if (!$carrier->add()) {
                $this->conveyor['errors'][] = $this->l('Failed to create Prestashop carrier.', 'NewCarrierActions');
                return false;
            }
        } catch (\Exception $e) {
            $this->conveyor['errors'][] = sprintf(
                $this->l('Failed to create Prestashop carrier : %s', 'NewCarrierActions'),
                $e->getMessage()
            );
            return false;
        }

        copy(_PS_MODULE_DIR_.'mondialrelay/logo.png', _PS_SHIP_IMG_DIR_ . '/' . (int) $carrier->id . '.jpg');
        
        $this->conveyor['carrier'] = $carrier;
        return true;
    }
    
    public function addMondialRelayCarrierMethod()
    {
        if (empty($this->conveyor['delivery_mode'])) {
            $this->conveyor['errors'][] = $this->l('Failed to create Mondial Relay carrier method : missing Delivery mode.', 'NewCarrierActions');
            return false;
        }
        if (empty($this->conveyor['insurance_level']) && (string)$this->conveyor['insurance_level'] != '0') {
            $this->conveyor['errors'][] = $this->l('Failed to create Mondial Relay carrier method : missing Insurance level.', 'NewCarrierActions');
            return false;
        }
        
        $carrier = $this->getCarrierFromConveyor();
        if (!$carrier || !Validate::isLoadedObject($carrier)) {
            $this->conveyor['errors'][] = $this->l('Failed to create Mondial Relay carrier method : invalid Prestashop carrier', 'NewCarrierActions');
            return false;
        }
        
        // Create our Mondial Relay Carrier method
        $carrierMethod = new MondialrelayCarrierMethod();
        $carrierMethod->id_carrier = $carrier->id;
        $carrierMethod->delivery_mode = $this->conveyor['delivery_mode'];
        $carrierMethod->insurance_level = $this->conveyor['insurance_level'];
        
        try {
            if (!$carrierMethod->add()) {
                $this->conveyor['errors'][] = $this->l('Failed to create Mondial Relay carrier method.', 'NewCarrierActions');
                return false;
            }
        } catch (Exception $e) {
            $this->conveyor['errors'][] = sprintf(
                $this->l('Failed to create Mondial Relay carrier method : %s.', 'NewCarrierActions'),
                $e->getMessage()
            );
            return false;
        }
        
        return true;
    }
    
    public function setDefaultZones()
    {
        $carrier = $this->getCarrierFromConveyor();
        if (!$carrier || !Validate::isLoadedObject($carrier)) {
            $this->conveyor['errors'][] = $this->l('Failed to add default zones to carrier : invalid Prestashop carrier', 'NewCarrierActions');
            return true;
        }
        
        // Set default carrier zones (all zones)
        // This will automatically associate all carrier ranges to the zone, if any (i.e. the 'delivery' table will be filled)
        $zones = Zone::getZones();
        foreach ($zones as $zone) {
            if (!$carrier->addZone($zone['id_zone'])) {
                // This can be configured in BO later, so don't fail the process
                $this->errors[] = sprintf(
                    $this->l('Failed to add zone "%s" to carrier.', 'NewCarrierActions'),
                    $zone['name']
                );
            }
        }
        
        return true;
    }
    
    public function setDefaultRangeWeight()
    {
        if (empty($this->conveyor['weight_coeff'])) {
            $this->conveyor['errors'][] = $this->l('Failed to add default weight range to carrier : missing Weight coefficient.', 'NewCarrierActions');
            return false;
        }
        
        if (empty($this->conveyor['delivery_mode'])) {
            $this->conveyor['errors'][] = $this->l('Failed to add default weight range to carrier : missing Delivery mode.', 'NewCarrierActions');
            return false;
        }
        
        $carrier = $this->getCarrierFromConveyor();
        if (!$carrier || !Validate::isLoadedObject($carrier)) {
            $this->conveyor['errors'][] = $this->l('Failed to add default weight range to carrier : invalid Prestashop carrier', 'NewCarrierActions');
            return true;
        }
        
        // Add a default weight range
        // This will automatically associate all carrier zones to the range, if any (i.e. the 'delivery' table will be filled)
        $rangeWeightValues = MondialrelayCarrierMethod::getCarrierDefaultRangeWeightValues($this->conveyor['weight_coeff'], $this->conveyor['delivery_mode']);
        if (!$rangeWeightValues) {
            // This can be configured in BO later, so don't fail the process
            $this->conveyor['errors'][] = sprintf(
                $this->l('Could not find a default weight range for delivery mode %s.', 'NewCarrierActions'),
                $this->conveyor['delivery_mode']
            );
        }
        
        $rangeWeight = new RangeWeight();
        $rangeWeight->id_carrier = $carrier->id;
        $rangeWeight->delimiter1 = $rangeWeightValues['min'];
        $rangeWeight->delimiter2 = $rangeWeightValues['max'];
        
        // This can be configured in BO later, so don't fail the process
        try {
            if (!$rangeWeight->add()) {
                $this->conveyor['errors'][] = $this->l('Failed to add default weight range to carrier.', 'NewCarrierActions');
            }
        } catch (Exception $e) {
            $this->conveyor['errors'][] = sprintf(
                $this->l('Failed to add default weight range to carrier : %s.', 'NewCarrierActions'),
                $e->getMessage()
            );
        }
        
        return true;
    }
    
    public function setDefaultRangePrice()
    {
        $carrier = $this->getCarrierFromConveyor();
        if (!$carrier || !Validate::isLoadedObject($carrier)) {
            $this->conveyor['errors'][] = $this->l('Failed to add default price range for carrier : invalid Prestashop carrier', 'NewCarrierActions');
            return true;
        }
        
        // Add a default price range
        // This will automatically associate all carrier zones to the range, if any (i.e. the 'delivery' table will be filled)
        $rangePriceValues = MondialrelayCarrierMethod::getCarrierDefaultRangePriceValues();
        $rangePrice = new RangePrice();
        $rangePrice->id_carrier = $carrier->id;
        $rangePrice->delimiter1 = $rangePriceValues['min'];
        $rangePrice->delimiter2 = $rangePriceValues['max'];
        
        // This can be configured in BO later, so don't fail the process
        try {
            if (!$rangePrice->add()) {
                $this->conveyor['errors'][] = $this->l('Failed to create default price range for carrier.', 'NewCarrierActions');
            }
        } catch (Exception $e) {
            $this->conveyor['errors'][] = sprintf(
                $this->l('Failed to create default price range for carrier : %s.', 'NewCarrierActions'),
                $e->getMessage()
            );
        }
        
        return true;
    }
    
    public function setDefaultGroups()
    {
        $carrier = $this->getCarrierFromConveyor();
        if (!$carrier || !Validate::isLoadedObject($carrier)) {
            $this->conveyor['errors'][] = $this->l('Failed to associate customer groups to carrier : invalid Prestashop carrier', 'NewCarrierActions');
            return true;
        }
        
        // This can be configured in BO later, so don't fail the process
        try {
            $groups = Group::getGroups(Configuration::get('PS_LANG_DEFAULT'));
            if (!$carrier->setGroups(array_column($groups, 'id_group'))) {
                $this->conveyor['errors'][] = $this->l('Failed to associate customer groups to carrier.', 'NewCarrierActions');
            }
        } catch (Exception $e) {
            $this->conveyor['errors'][] = sprintf(
                $this->l('Failed to associate customer groups to carrier : %s.', 'NewCarrierActions'),
                $e->getMessage()
            );
        }
        
        return true;
    }
}
