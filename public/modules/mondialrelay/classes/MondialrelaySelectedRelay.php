<?php
/**
 * NOTICE OF LICENSE
 *
 * @author    202 ecommerce <tech@202-ecommerce.com>
 * @author    Mondial Relay
 * @copyright Copyright (c) Mondial Relay
 * @license http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 */

class MondialrelaySelectedRelay extends ObjectModel
{
    /**
     * @var int When a relay is selected, a native Prestashop address will be created and
     * associated to the order
     */
    public $id_address_delivery;

    /**
     * @var int We could retrieve this from the cart, but this will ease the
     * joinings
     */
    public $id_customer;

    /**
     * @var int The Mondial Relay carrier method selected during checkout; this
     * could also be retrieved from the order's carrier
     */
    public $id_mondialrelay_carrier_method;

    /**
     * @var int The cart being shipped
     */
    public $id_cart;

    /**
     * @var int The order associated to the cart
     */
    public $id_order;

    /**
     * @var int The package weight; can be specified by the merchant when
     * generating the label
     */
    public $package_weight;

    /**
     * @var string The Mondial Relay insurance level; defaults to the one
     * from the carrier method, but can be set when generating the label
     */
    public $insurance_level;

    /**
     * @var string The relay's Mondial Relay identifier ("Num" field)
     */
    public $selected_relay_num;

    /**
     * @var string The relay's name; line 1 ("LgAdr1" field)
     */
    public $selected_relay_adr1;

    /**
     * @var string The relay's name; line 2 ("LgAdr2" field)
     */
    public $selected_relay_adr2;

    /**
     * @var string The relay's address; line 3 ("LgAdr3" field)
     */
    public $selected_relay_adr3;

    /**
     * @var string The relay's address; line 4 ("LgAdr4" field)
     */
    public $selected_relay_adr4;

    /**
     * @var string The relay's postcode ("CP" field)
     */
    public $selected_relay_postcode;

    /**
     * @var string The relay's city ("Ville" field)
     */
    public $selected_relay_city;

    /**
     * @var string The relay's country iso code ("Pays" field)
     */
    public $selected_relay_country_iso;

    /**
     * @var string The order's tracking url
     */
    public $tracking_url;

    /**
     * @var string The order's label url
     */
    public $label_url;

    /**
     * @var string The order's expedition number
     */
    public $expedition_num;

    /**
     * @var string The order's label generation date
     */
    public $date_label_generation;

    /**
     * @var bool Should this order be logged in history ?
     * Interesting sidenote : if a field has a non-zero default value (weak
     * comparison), we'll never be able to set it to 0 (weak comparison)
     * @see ObjectModel::validateField()
     */
    public $hide_history;

    // PS default fields
    public $date_add;
    public $date_upd;
    
    /**
     *@inheritdoc
     */
    public static $definition = array(
        'table'   => 'mondialrelay_selected_relay',
        'primary' => 'id_mondialrelay_selected_relay',
        'fields'  => array(
            'id_address_delivery'            => array('type' => self::TYPE_INT, 'validate' => 'isUnsignedId', 'allow_null' => true),
            'id_customer'                    => array('type' => self::TYPE_INT, 'validate' => 'isUnsignedId', 'allow_null' => true),
            'id_mondialrelay_carrier_method' => array('type' => self::TYPE_INT, 'validate' => 'isUnsignedId', 'allow_null' => true),
            'id_cart'                        => array('type' => self::TYPE_INT, 'validate' => 'isUnsignedId', 'allow_null' => true),
            'id_order'                       => array('type' => self::TYPE_INT, 'validate' => 'isUnsignedId', 'allow_null' => true),
            'package_weight'                 => array('type' => self::TYPE_STRING, 'size' => 7, 'allow_null' => true),
            'insurance_level'                => array('type' => self::TYPE_STRING, 'values' => array(0, 1, 2, 3, 4, 5), 'default' => 0, 'size' => 1, 'allow_null' => true),
            // Mondial Relay fields, from webservice function WSI4_PointRelais_Recherche
            // These fields *could* always be retrieved from the webservice
            'selected_relay_num'             => array('type' => self::TYPE_STRING, 'size' => 6, 'allow_null' => true),
            'selected_relay_adr1'            => array('type' => self::TYPE_STRING, 'size' => 36, 'allow_null' => true),
            'selected_relay_adr2'            => array('type' => self::TYPE_STRING, 'size' => 36, 'allow_null' => true),
            'selected_relay_adr3'            => array('type' => self::TYPE_STRING, 'size' => 36, 'allow_null' => true),
            'selected_relay_adr4'            => array('type' => self::TYPE_STRING, 'size' => 36, 'allow_null' => true),
            'selected_relay_postcode'        => array('type' => self::TYPE_STRING, 'size' => 10, 'allow_null' => true),
            'selected_relay_city'            => array('type' => self::TYPE_STRING, 'size' => 32, 'allow_null' => true),
            'selected_relay_country_iso'     => array('type' => self::TYPE_STRING, 'size' => 2, 'allow_null' => true),
            //
            'tracking_url'                   => array('type' => self::TYPE_STRING, 'size' => 1000, 'allow_null' => true),
            'label_url'                      => array('type' => self::TYPE_STRING, 'size' => 1000, 'allow_null' => true),
            'expedition_num'                 => array('type' => self::TYPE_STRING, 'size' => 8, 'allow_null' => true),
            'date_label_generation'          => array('type' => self::TYPE_DATE, 'validate' => 'isDate', 'allow_null' => true),
            'hide_history'                   => array('type' => self::TYPE_BOOL, 'default' => 0, 'validate' => 'isBool'),
            'date_add'                       => array('type' => self::TYPE_DATE, 'validate' => 'isDate'),
            'date_upd'                       => array('type' => self::TYPE_DATE, 'validate' => 'isDate'),
        ),
    );

    /**
     * Returns an existing MondialrelaySelectedRelay object associated to a
     * cart, or an empty one if none exists
     * @param int $id_cart
     * @return MondialrelaySelectedRelay
     */
    public static function getFromIdCart($id_cart)
    {
        $query = new DbQuery();
        $query->select('*')
            ->from(self::$definition['table'])
            ->where('id_cart = ' . (int)$id_cart);
        
        $res = Db::getInstance()->getRow($query);
        
        $selectedRelay = new MondialrelaySelectedRelay();
        if ($res) {
            $selectedRelay->hydrate($res);
        }
        
        return $selectedRelay;
    }

    /**
     * Returns an existing MondialrelaySelectedRelay object associated to an
     * address; there is NO guarantee on WHICH line will be returned.
     *
     * @param int $id_address
     * @return MondialrelaySelectedRelay|false
     */
    public static function getAnyFromIdAddressDelivery($id_address)
    {
        $query = new DbQuery();
        $query->select('*')
            ->from(self::$definition['table'], 'mr_sr')
            // Join to make sure carrier method still exists
            ->innerJoin('mondialrelay_carrier_method', 'mr_cm', 'mr_cm.id_mondialrelay_carrier_method = mr_sr.id_mondialrelay_carrier_method')
            ->where('mr_sr.id_address_delivery = ' . (int)$id_address)
            ->where('mr_cm.is_deleted = 0');
        
        $res = Db::getInstance()->getRow($query);
        
        if (!$res) {
            return false;
        }
        
        $selectedRelay = new MondialrelaySelectedRelay();
        $selectedRelay->hydrate($res);
        return $selectedRelay;
    }
    
    /**
     * Retrieves a customer's existing relay address id, using the previously
     * placed orders.
     *
     * @param int $id_customer
     * @param string $relayNumber
     * @param string $country_iso
     *
     * @return MondialrelaySelectedRelay
     */
    public static function getCustomerRelayAddress($id_customer, $relayNumber, $country_iso)
    {
        $query = new DbQuery();
        $query->select('a.id_address')
            ->from(self::$definition['table'], 'mr_sr')
            // Join with "address" table, just to make sure the address actually
            // exists and the customer owns it
            ->innerJoin('address', 'a', 'a.id_address = mr_sr.id_address_delivery')
            ->where('mr_sr.id_customer = ' . (int)$id_customer)
            ->where('a.id_customer = ' . (int)$id_customer)
            ->where("mr_sr.selected_relay_num = '" . pSQL($relayNumber) . "'")
            ->where("mr_sr.selected_relay_country_iso = '" . pSQL($country_iso) . "'")
            ->where("mr_sr.id_order IS NOT NULL")
        ;

        $id_address = Db::getInstance()->getValue($query);

        if ($id_address) {
            return new Address($id_address);
        }
        return false;
    }
    
    /**
     * Checks if an address represents a relay
     *
     * @param int $id_address
     * @return bool
     */
    public static function isRelayAddress($id_address)
    {
        $query = new DbQuery();
        $query->select('a.id_address')
            ->from(self::$definition['table'], 'mr_sr')
            // Join with "address" table, just to make sure the address actually
            // exists and the customer owns it
            ->innerJoin(
                'address',
                'a',
                'a.id_address = mr_sr.id_address_delivery '
                . 'AND a.id_customer = mr_sr.id_customer'
            )
            // Checks if the carrier method needs a relay
            ->innerJoin(
                MondialrelayCarrierMethod::$definition['table'],
                'mr_cm',
                'mr_cm.id_mondialrelay_carrier_method = mr_sr.id_mondialrelay_carrier_method'
            )
            ->where(
                "mr_cm.delivery_mode IN ('" .
                implode("', '", array_map(function ($i) {
                    return pSQL($i);
                }, MondialrelayCarrierMethod::$relayDeliveryModes)) .
                "')"
            )
            ->where('a.id_address = ' . (int)$id_address)
        ;
        
        return (bool)Db::getInstance()->getValue($query);
    }
    
    /**
     * Checks if an address represents a relay AND has already been used to
     * place an order with Mondial Relay
     * IMPORTANT : This will return "false" if the address is not used OR if it's
     * not a relay address. Therefore, it can't be used on its own to check wether
     * an address is used by Mondial Relay as a whole, as an address may not be
     * a relay address but still be used by MR carriers not requiring a relay.
     *
     * @param int $id_address
     * @param int $exclude_id_order If we're checking the address of an existing
     * order, we need to exclude it
     * @return bool
     */
    public static function isUsedRelayAddress($id_address, $exclude_id_order = null)
    {
        $query = new DbQuery();
        $query->select('a.id_address')
            ->from(self::$definition['table'], 'mr_sr')
            // Join with "address" table, just to make sure the address actually
            // exists and the customer owns it
            ->innerJoin(
                'address',
                'a',
                'a.id_address = mr_sr.id_address_delivery '
                . 'AND a.id_customer = mr_sr.id_customer'
            )
            ->where('mr_sr.id_address_delivery = ' . (int)$id_address)
            ->where('mr_sr.id_order IS NOT NULL')
            ->where('(mr_sr.selected_relay_num IS NOT NULL AND mr_sr.selected_relay_num <> "")')
        ;
        
        if ($exclude_id_order) {
            $query->where('mr_sr.id_order <> ' . (int)$exclude_id_order);
        }

        return (bool)Db::getInstance()->getValue($query);
    }
    
    /**
     * Returns the relay's full identifier
     * @return string|false
     */
    public function getFullRelayIdentifier()
    {
        if (!$this->selected_relay_country_iso || !$this->selected_relay_num) {
            return false;
        }
        return $this->selected_relay_country_iso . '-' . $this->selected_relay_num;
    }
    
    /**
     * Sets the Mondial relay's order tracking url
     *
     * @param string $enseigne
     * @param string $brand_code
     * @param string $iso_lang The language of the destination page
     * @param string $key
     *
     * @return void
     */
    public function setTrackingUrl($enseigne, $brand_code, $iso_lang, $key)
    {
        if (!$this->expedition_num) {
            return;
        }
        
        $deliveryAddress = new Address($this->id_address_delivery);
        $this->tracking_url = Mondialrelay::URL_DOMAIN . '/public/permanent/tracking.aspx?'
            . 'ens=' . $enseigne . $brand_code
            . '&exp=' . $this->expedition_num
            . '&pays=' . Country::getIsoById($deliveryAddress->id_country)
            . '&language=' . $iso_lang
            . '&crc=' . Tools::strtoupper(md5(
                '<' . $enseigne . $brand_code . '>'
                . $this->expedition_num
                . '<' . $key . '>'
            ));
    }
    
    /**
     * Retrieves every object with a generated label that wasn't delivered; i.e.
     * all orders with an expedition number and an order state different from
     * the one configured for "delivered" orders
     *
     * @return array An array of MondialrelaySelectedRelay objects
     */
    public static function getAllUndeliveredWithLabel()
    {
        $query = new DbQuery();
        $query->select('mr_sr.*')
            ->from(self::$definition['table'], 'mr_sr')
            ->innerJoin(Order::$definition['table'], 'o', 'o.id_order = mr_sr.id_order')
            ->where('expedition_num IS NOT NULL AND expedition_num <> ""')
            ->where('o.current_state <> ' . (int)Configuration::get(Mondialrelay::OS_ORDER_DELIVERED))
            ->where('DATE_ADD(mr_sr.date_label_generation, INTERVAL 1 YEAR) > CURRENT_DATE')
        ;
        $lines = Db::getInstance()->executeS($query);
        
        if (empty($lines)) {
            return array();
        }
        
        $return = array();
        foreach ($lines as $line) {
            $selectedRelay = new MondialrelaySelectedRelay();
            $selectedRelay->hydrate($line);
            $return[] = $selectedRelay;
        }
        
        return $return;
    }
}
