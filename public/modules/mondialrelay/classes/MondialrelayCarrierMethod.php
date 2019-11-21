<?php
/**
 * NOTICE OF LICENSE
 *
 * @author    202 ecommerce <tech@202-ecommerce.com>
 * @author    Mondial Relay
 * @copyright Copyright (c) Mondial Relay
 * @license http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 */

class MondialrelayCarrierMethod extends ObjectModel
{

    /** @var int $id_carrier The id of the associated Prestashop carrier */
    public $id_carrier;

    /** @var string $collection_mode See Webservice 'ModeLiv' field */
    public $delivery_mode;

    /** @var string $insurance_level See Webservice 'Assurance' field */
    public $insurance_level;

    /** @var int $id_carrier 0/1 : Was the carrier deleted ? We need to keep it
     * for history purposes.
     */
    public $is_deleted;

    public $date_add;
    public $date_upd;
    
    public static $definition = array(
        'table'   => 'mondialrelay_carrier_method',
        'primary' => 'id_mondialrelay_carrier_method',
        'fields'  => array(
            'id_carrier'      => array('type' => self::TYPE_INT, 'validate' => 'isUnsignedId', 'required' => true),
            'delivery_mode'   => array('type' => self::TYPE_STRING, 'values' => array('24R', 'DRI', 'LD1', 'LDS', 'HOM'), 'required' => true, 'size' => 3),
            'insurance_level' => array('type' => self::TYPE_STRING, 'values' => array(0, 1, 2, 3, 4, 5), 'default' => 0, 'size' => 1),
            'is_deleted'      => array('type' => self::TYPE_BOOL, 'default' => 0, 'validate' => 'isBool'),
            'date_add'        => array('type' => self::TYPE_DATE, 'validate' => 'isDate'),
            'date_upd'        => array('type' => self::TYPE_DATE, 'validate' => 'isDate'),
        ),
    );
    
    /**
     * @var array The delivery modes using relays
     */
    public static $relayDeliveryModes = array('24R', 'DRI');

    /**
     * Returns an array of insurance levels labels indexed with the associated
     * value.
     * We can't set this method as "static", as calls to the translation method
     * won't be parsed by PS if we don't use the "$this" syntax
     *
     * @return array
     */
    public function getInsuranceLevelsList()
    {
        return array(
            0 => '0 : ' . $this->l('No insurance'),
            1 => '1 : ' . $this->l('Complementary Insurance Lv1'),
            2 => '2 : ' . $this->l('Complementary Insurance Lv2'),
            3 => '3 : ' . $this->l('Complementary Insurance Lv3'),
            4 => '4 : ' . $this->l('Complementary Insurance Lv4'),
            5 => '5 : ' . $this->l('Complementary Insurance Lv5'),
        );
    }

    /**
     * Returns an array of delivery modes labels indexed with the associated
     * value
     *
     * @return array
     */
    public function getDeliveryModesList()
    {
        return array(
            '24R' => $this->l('24R : Delivery to a Point Relais'),
            'HOM' => $this->l('HOM : Special Home delivery'),
            'DRI' => $this->l('DRI : Colis Drive delivery'),
            'LD1' => $this->l('LD1 : Home delivery RDC (1 person)'),
            'LDS' => $this->l('LDS : Special Home delivery (2 people)'),
        );
    }
    
    /**
     * Returns the default weight range values for a carrier, in an array with
     * 'min' and 'max' keys
     *
     * @param float $weightCoeff The weight coefficient to use when calculating the values
     * @param string $deliveryMode The carrier's delivery mode
     */
    public static function getCarrierDefaultRangeWeightValues($weightCoeff, $deliveryMode)
    {
        $ranges = array(
            '24R' => array(
                'min' => 0,
                'max' => 30000 / $weightCoeff
            ),
            'DRI' => array(
                'min' => 0,
                'max' => 130000 / $weightCoeff
            ),
            'LD1' => array(
                'min' => 0,
                'max' => 60000 / $weightCoeff
            ),
            'HOM' => array(
                'min' => 0,
                'max' => 30000 / $weightCoeff
            ),
            'LDS' => array(
                'min' => 0,
                'max' => 130000 / $weightCoeff
            ),
        );
        return isset($ranges[$deliveryMode])? $ranges[$deliveryMode] : false;
    }
    
    /**
     * Returns the default weight price values for a carrier, in an array with
     * 'min' and 'max' keys
     * @return array
     */
    public static function getCarrierDefaultRangePriceValues()
    {
        return array(
            'min' => 0,
            'max' => 10000,
        );
    }
    
    /**
     * Gets a Mondial Relay carrier method from its id_carrier
     *
     * @param int $id_carrier
     * @return MondialrelayCarrierMethod|false
     */
    public static function getFromNativeCarrierId($id_carrier)
    {
        $query = new DbQuery();
        $query->select('*')
            ->from(self::$definition['table'])
            ->where('id_carrier = ' . (int)$id_carrier);
        
        $res = Db::getInstance()->getRow($query);
        
        if ($res) {
            $carrierMethod = new MondialrelayCarrierMethod();
            $carrierMethod->hydrate($res);
            return $carrierMethod;
        }
        
        return false;
    }
    
    /**
     * Returns an array of native Carrier objects that are linked to Mondial Relay.
     * This will not check wether a carrier is *available* for a shop; the $id_shop
     * is only used for translation purposes.
     *
     * @param boolean $active true/false will return active/inactive carriers; null will return both
     * @param int $id_shop null will return values with the default shop
     * @param int $id_lang null will return values with the default language
     * @param boolean $deleted true/false will return deleted/existing carriers; null will return both
     *
     * @return boolean|\Carrier
     */
    public static function getAllPrestashopCarriers($active = true, $id_shop = null, $id_lang = null, $deleted = false)
    {
        if (!$id_lang) {
            $id_lang = (int)Configuration::get('PS_LANG_DEFAULT');
        }
        
        if (!$id_shop) {
            $id_shop = (int)Configuration::get('PS_SHOP_DEFAULT');
        }
        
        $query = new DbQuery();
        $query->select('c.*, cl.*')
            ->from(self::$definition['table'], 'mr_cm')
            ->innerJoin(
                Carrier::$definition['table'],
                'c',
                'c.id_carrier = mr_cm.id_carrier'
            )
            ->leftJoin(
                Carrier::$definition['table'] . '_lang',
                'cl',
                'cl.id_carrier = c.id_carrier '
                    . 'AND cl.id_lang = ' . (int)$id_lang . ' '
                    . 'AND cl.id_shop = ' . (int)$id_shop
            );
        
        if ($deleted !== null) {
            $query->where('mr_cm.is_deleted = ' . (int)$deleted)
            ->where('c.deleted = ' . (int)$deleted);
        }
        if ($active !== null) {
            $query->where('c.active = ' . (int)$active);
        }
        
        $res = Db::getInstance()->executeS($query);
        
        if ($res === false) {
            return false;
        }
        if (empty($res)) {
            return array();
        }

        return ObjectModel::hydrateCollection('Carrier', $res, $id_lang);
    }
    
    /**
     * Checks if an array contains native carrier ids associated to a Mondial
     * Relay active carrier method, and returns only those ids
     * @param array $nativeCarriersIds An array of native carriers ids
     * @return false|array
     */
    public static function findMondialrelayCarrierIds($nativeCarriersIds, $onlyRelays = false)
    {
        if (empty($nativeCarriersIds)) {
            return false;
        }
        
        $query = new DbQuery();
        $query->select('id_carrier')->from(self::$definition['table'])
            ->where('is_deleted = 0')
            ->where(
                'id_carrier IN (' .
                implode(',', array_map(function ($i) {
                    return (int)$i;
                }, $nativeCarriersIds)) .
                ')'
            )
        ;
        
        if ($onlyRelays) {
            $query->where(
                "delivery_mode IN ('" .
                implode("', '", array_map(function ($relayDeliveryMode) {
                    return pSQL($relayDeliveryMode);
                }, self::$relayDeliveryModes)) .
                "')"
            );
        }
        
        $res = Db::getInstance()->executeS($query);
        if (!$res) {
            return false;
        }
        return array_column($res, 'id_carrier');
    }
    
    /**
     * Checks if a carrier method needs to have a selected relay
     * @return bool
     */
    public function needsRelay()
    {
        return in_array($this->delivery_mode, self::$relayDeliveryModes);
    }
    
    /**
     * Translation function.
     *
     * @param string $string The string to translate
     * @param string $specific The name of the file, if different from the
     * calling class.
     *
     * @return string
     */
    protected function l($string, $specific = false)
    {
        if (!$specific) {
            $specific = basename(str_replace('\\', '/', get_class($this)));
        }
        return Translate::getModuleTranslation('mondialrelay', $string, $specific);
    }
    
    /**
     * Disables native carriers associated to Mondial Relay in the shops from
     * the given list
     *
     * @param array $id_shop_list
     * @return bool
     */
    public static function removeNativeCarriersFromShops($id_shop_list)
    {
        $deleteFromCarrierShop = "DELETE FROM " . _DB_PREFIX_ . "carrier_shop "
            . "WHERE id_carrier IN ("
            . "SELECT id_carrier FROM " . _DB_PREFIX_ . self::$definition['table'] . " "
            . ") "
            . "AND id_shop IN (" .
            implode(', ', array_map(
                function ($i) {
                    return (int)$i;
                },
                $id_shop_list
            ))
            . ")";
                
        return Db::getInstance()->execute($deleteFromCarrierShop);
    }
}
