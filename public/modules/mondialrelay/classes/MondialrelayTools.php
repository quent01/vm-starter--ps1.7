<?php
/**
 * NOTICE OF LICENSE
 *
 * @author    202 ecommerce <tech@202-ecommerce.com>
 * @author    Mondial Relay
 * @copyright Copyright (c) Mondial Relay
 * @license http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 */

require_once _PS_MODULE_DIR_ . '/mondialrelay/classes/services/MondialrelayService.php';

/*
** Some tools using used in the module
*/
class MondialrelayTools
{
    const REGEX_CLEAN_ADDR = '/[^a-zA-Z0-9-\s\'\!\,\|\(\)\.\*\&\#\/\:]/';

    const REGEX_CLEAN_PHONE = '/[^0-9+\(\)]*/';

    /**
     * Checks if a zipcode is valid according to its country
     *
     * @param string $zipcode
     * @param string $iso_country

     * @return boolean
     */
    public static function checkZipcodeByCountry($zipcode, $iso_country)
    {
        $zipcodeFormat = Db::getInstance()->getValue('
                SELECT `zip_code_format`
                FROM `'._DB_PREFIX_."country`
                WHERE `iso_code` = '". pSQL($iso_country) . "'");

        if (!$zipcodeFormat) {
            return true;
        }

        $regxMask = str_replace(
            array('N', 'C', 'L'),
            array(
                '[0-9]',
                $iso_country,
                '[a-zA-Z]'
            ),
            $zipcodeFormat
        );

        return preg_match('/^'.$regxMask.'$/', $zipcode);
    }

    /**
     * Formats a (french) phonenumber
     *
     * @param string $phone_number
     *
     * @return string
     */
    public static function getFormattedPhonenumber($phone_number)
    {
        if (!$phone_number) {
            return '';
        }
        $begin      = Tools::substr($phone_number, 0, 3);
        $pad_number = (strpos($begin, '+3') !== false) ? 12 :
            (strpos($begin, '00') ? 13 : 10);

        return str_pad(
            Tools::substr(preg_replace(self::REGEX_CLEAN_PHONE, '', $phone_number), 0, $pad_number),
            $pad_number,
            '0',
            STR_PAD_LEFT
        );
    }
    
    /**
     * Checks for SOAP and cURL availability
     * @return boolean
     */
    public static function checkDependencies()
    {
        $loadedExtensions = get_loaded_extensions();
        return in_array('curl', $loadedExtensions) && in_array('soap', $loadedExtensions);
    }
    
    /**
     * Returns an array of hooks in which the module is not registered
     * @return array
     */
    public static function getModuleMissingHooks()
    {
        // @see Module::isRegisteredInHook
        $module = Module::getInstanceByName('mondialrelay');
        
        $hooksAliases = Hook::getHookAliasList();
        
        $missingHooks = array();
        foreach ($module->hooks as $hook) {
            $hook = isset($hooksAliases[$hook]) ? $hooksAliases[$hook] : $hook;
            if (!$module->isRegisteredInHook($hook)) {
                $missingHooks[] = $hook;
            }
        }
        
        return $missingHooks;
    }
    
    /**
     * Checks that every required configuration field is filled for the current shop
     * context
     */
    public static function checkWebserviceConfiguration()
    {
        $conf = Configuration::getMultiple(array(
            Mondialrelay::WEBSERVICE_ENSEIGNE,
            Mondialrelay::WEBSERVICE_BRAND_CODE,
            Mondialrelay::WEBSERVICE_KEY,
            Mondialrelay::WEIGHT_COEFF,
            Mondialrelay::LABEL_LANG,
        ));
        
        return count($conf) == count(array_filter($conf));
    }
    
    /**
     * Formats an array for a select list, by creating an array of array with
     * the keys and values of the original array in 'label' and 'value' fields
     *
     * @param array $array
     * @return array
     */
    public static function formatArrayForSelect($array)
    {
        return array_map(
            function ($v, $l) {
                return array(
                    'label' => $l,
                    'value' => $v,
                );
            },
            array_keys($array),
            $array
        );
    }
    
    /**
     * Checks the webservice connection with specific information, by trying to
     * retrieve a relay list
     *
     * @param string $enseigne
     * @param string $key
     * @param string $errors an error array filled by the function
     *
     * @return boolean
     */
    public static function checkWebserviceConnection($enseigne, $key, &$errors = array())
    {
        $params = array(
            'CP' => array(
                // We need the iso_country to validate the zipcode format
                'zipcode' => '75000',
                'iso_country' => 'FR',
            ),
            'Pays' => 'FR',
        );

        try {
            $service = MondialrelayService::getService('Relay_Search');
            $service->setEnseigne($enseigne);
            $service->setPrivateKey($key);

            // Set data
            if (!$service->init(array($params))) {
                foreach ($service->getErrors() as $itemErrors) {
                    foreach ($itemErrors as $error) {
                        $errors[] = $error;
                    }
                }
                return false;
            }

            // Send data
            if (!$service->send()) {
                foreach ($service->getErrors() as $itemErrors) {
                    foreach ($itemErrors as $error) {
                        $errors[] = $error;
                    }
                }
                return false;
            }

            $result = $service->getResult();

            $statCode = $result[0]->STAT;
            if ($statCode == 0) {
                return true;
            } else {
                $errors[] = $service->getErrorFromStatCode($result[0]->STAT);
            }
        } catch (Exception $e) {
            $errors[] = $e->getMessage();
        }
        
        return false;
    }
    
    /**
     * The brand code is never used by the webservice, so we have implement a
     * validation function somewhere.
     *
     * @param string $brandCode
     *
     * @return boolean
     */
    public static function validateBrandCode($brandCode)
    {
        return preg_match("#^[0-9]{2}$#", $brandCode);
    }
    
    /**
     * Sets a delivery address for a cart and all its products and
     * customizations.
     * We can't just set the id_address_delivery, because the cart_product and
     * customization tables also have delivery addresses.
     *
     * @param Cart $cart
     * @param int $id_address_delivery
     */
    public static function setCartDeliveryAddress($cart, $id_address_delivery)
    {
        $id_address_invoice = $cart->id_address_invoice;
        $cart->updateAddressId($cart->id_address_delivery, $id_address_delivery);
        $cart->id_address_invoice = $id_address_invoice;
    }
    
    /**
     * From an array of shop ids, returns only those where the module is
     * enabled
     *
     * @param int $id_module
     * @param array $id_shop_list
     * @return array
     */
    public static function getShopsWithModuleEnabled($id_module, $id_shop_list)
    {
        $query = new DbQuery();
        $query->select('id_shop')
            ->from('module_shop')
            ->where('id_module = ' . (int) $id_module)
            ->where('id_shop IN (' . implode(', ', array_map(
                function ($i) {
                    return (int)$i;
                },
                $id_shop_list
            )) . ')');
        $res = Db::getInstance()->executeS($query);
        if (!$res) {
            return array();
        }
        
        $return = array();
        foreach ($res as $row) {
            $return[] = $row['id_shop'];
        }
        return $return;
    }
}
