<?php
/**
 * NOTICE OF LICENSE
 *
 * @author    202 ecommerce <tech@202-ecommerce.com>
 * @author    Mondial Relay
 * @copyright Copyright (c) Mondial Relay
 * @license http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 */

require_once _PS_MODULE_DIR_ . '/mondialrelay/mondialrelay.php';
require_once _PS_MODULE_DIR_ . '/mondialrelay/classes/MondialrelayTools.php';

abstract class MondialrelayService
{
    const BASE_URL       = 'http://www.mondialrelay.fr/';
    const WEBSERVICE_URL = 'http://www.mondialrelay.fr/webservice/Web_Services.asmx?WSDL';

    /**
     * @var string $webservice_enseigne Usually retrieved from Configuration
     * @see MondialrelayService::setEnseigne()
     */
    protected $webservice_enseigne = '';
    
    /**
     * @var string $webservice_enseigne Usually retrieved from Configuration
     * @see MondialrelayService::setPrivateKey()
     */
    protected $webservice_key = '';

    /**
     * @var array $webservice_errorCodes Should hold mostly all of the possible
     * error codes returned by the webservice.
     */
    private static $webservice_errorCodes = array();

    /**
     * @var array The available functions as index, and the corresponding
     * classes as values.
     */
    private static $webservice_functions = array(
        'Relay_Search' => 'MondialrelayServiceRecherchePointRelais',
        'Relay_Infos' => 'MondialrelayServiceInfosPointRelais',
        'Label_Generation' => 'MondialrelayServiceCreationEtiquette',
        'Label_Bulk_Retrieval' => 'MondialrelayServiceGetEtiquettes',
        'Order_Trace' => 'MondialrelayServiceTracingColis'
    );

    /**
     * @var array The available fields for the webservice function as index,
     * parameters as value. Natively available parameters : "required", "regex".
     *
     * IMPORTANT : The fields must be ordered as specified in the Webservice
     * documentation so that the Security key will be properly generated
     */
    protected $fields = array();

    /** @var string $function The webservice function to call  */
    protected $function = '';

    /**
     * @var array The data passed to the class, including the payload.
     * Structure may depend on the function called. It is kept in case the
     * concrete service needs more data than used by the webservice.
     * @see MondialrelayService::setPayloadFromData()
     */
    protected $data = array();

    /**
     * @var array The data sent to the webservice; should be an array of array.
     * @see MondialrelayService::setPayloadFromData()
     */
    protected $payload = array();

    /**
     * @var array The data resulting of the function call.
     * Structure may depend on the function called.
     */
    protected $result;

    /**
     * @var array The errors resulting of the function call.
     * Structure may depend on the function called, but each "item" passed in
     * $data should have its errors set at the same position in this array.
     * Generic / global service errors are set in an array at the 'generic' key.
     */
    protected $errors = array(
        'generic' => array()
    );

    /**
     * "Factory" method; returns a concrete "Service" class when given a
     * webservice function, or false if the function wasn't found
     *
     * @param string $function The webservice's function name
     *
     * @return MondialrelayService
     */
    public static function getService($function)
    {
        if (isset(self::$webservice_functions[$function])) {
            require_once dirname(__FILE__) . '/' . self::$webservice_functions[$function] . '.php';
            return new self::$webservice_functions[$function]();
        }
        return false;
    }

    /**
     * Use getService() instead.
     *
     * @param type $data
     */
    protected function __construct()
    {
        $this->webservice_enseigne = Configuration::get(Mondialrelay::WEBSERVICE_ENSEIGNE);
        $this->webservice_key = Configuration::get(Mondialrelay::WEBSERVICE_KEY);
    }

    /**
     * Sets data to be used by the service; this is where setPayloadFromData()
     * should be called, and where "contextual" data should be added.
     *
     * @param array $data data used by the service
     *
     * @return boolean
     */
    abstract public function init($data);

    /**
     * Validates data and sets the payload.
     *
     * @param array $data
     *
     * @return boolean
     */
    protected function setPayloadFromData()
    {
        foreach ($this->data as $key => $item) {
            $isValid = true;
            $item['Enseigne'] = $this->webservice_enseigne;

            // Child classes can implement a custom preprocessing method
            if (method_exists($this, 'preprocessData')) {
                $item = $this->{'preprocessData'}($key, $item);
            }

            // Validate each field available in the webservice
            foreach (array_keys($this->fields) as $fieldName) {
                if (!$this->validateField($key, $item, $fieldName)) {
                    $isValid = false;
                    break;
                }
            }

            // If item is valid, add it to payload
            if ($isValid) {
                // Remove parameters not used by the webservice function
                $item = array_intersect_key($item, $this->fields);
                
                // Order keys according to fields list
                $this->payload[$key] = array();
                foreach (array_keys($this->fields) as $fieldName) {
                    if (isset($item[$fieldName]) && !Tools::isEmpty($item[$fieldName])) {
                        $this->payload[$key][$fieldName] = $item[$fieldName];
                    }
                }
            }
        }

        if (empty($this->payload)) {
            return false;
        }

        // Add "Security" field to every item where it's absent
        $this->generateMD5SecurityKey();

        return true;
    }
    
    /**
     * Validates a field from an item in the $data array.
     *
     * @param int $key the item's position in the $data array
     * @param array $item the validated item from the $data array
     * @param string $fieldName the field to validate in the item
     */
    protected function validateField($key, &$item, $fieldName)
    {
        $field = $this->fields[$fieldName];
        // Check if field is present and required; we need a strict comparison
        // as some fields may be set to 0
        if (!isset($item[$fieldName]) || Tools::isEmpty($item[$fieldName])) {
            if (empty($field['required'])) {
                return true;
            }
            
            $this->errors[$key][] = $this->l('Field %s is required for webservice function %s.', 'MondialrelayService', array($fieldName, $this->function));

            return false;
        }
        $value = $item[$fieldName];

        // Child classes can implement custom validation/formatting
        // methods for each field
        $processMethodName = 'process' . str_replace('_', '', $fieldName);
        if (method_exists($this, $processMethodName)) {
            $value = $this->{$processMethodName}($key, $value, $item);

            if (false === $value) {
                if (!empty($field['required'])) {
                    return false;
                }

                unset($item[$fieldName]);
                return true;
            }
        }
        
        // Check if format is valid
        if (isset($field['regex']) && !preg_match($field['regex'], $value)) {
            if (!empty($field['required'])) {
                $this->errors[$key][] = $this->l('Field %s format is invalid for webservice function %s.', 'MondialrelayService', array($fieldName, $this->function));

                return false;
            }

            $this->errors[$key][] = $this->l('Field %s format is invalid for webservice function %s and was removed from the request.', 'MondialrelayService', array($fieldName, $this->function));
            unset($item[$fieldName]);
            return true;
        }

        $item[$fieldName] = $value;
        
        return true;
    }

    /**
     * Generate the MD5 key for each item in payload.
     *
     * The "Security" field is always the last to be added.
     * Don't check for invalid fields here; data should be cleaned before any
     * attempt to build the key to avoid a mess.
     *
     * @see MondialrelayService::setPayloadFromData()
     */
    protected function generateMD5SecurityKey()
    {
        // Fields excluded from the key
        $excludeFields = array('Texte');

        foreach ($this->payload as &$item) {
            if (!empty($item['Security'])) {
                continue;
            }

            $concatenationValue = '';

            foreach (array_keys($this->fields) as $paramName) {
                if (in_array($paramName, $excludeFields) || !isset($item[$paramName]) || Tools::isEmpty($item[$paramName])) {
                    continue;
                }
                $concatenationValue .= $item[$paramName];
            }

            $concatenationValue .= $this->webservice_key;
            $item['Security']   = Tools::strtoupper(md5($concatenationValue));
        }
    }

    /**
     * Sends the $payload to the webservice; the answer should be parsed and the
     * $result set during this process.
     *
     * @return boolean
     *
     * @throws Exception
     *
     * @see parseResult
     */
    public function send()
    {
        if (!MondialrelayTools::checkDependencies()) {
            throw new Exception($this->l('SOAP and cURL should be installed on your server.', 'MondialrelayService'));
        }
        
        if (empty($this->payload)) {
            $this->errors['generic'][] = $this->l('No data to send.', 'MondialrelayService');
            return false;
        }
        
        if (($soapClient = new \SoapClient(self::WEBSERVICE_URL))) {
            $soapClient->soap_defencoding = 'UTF-8';
            $soapClient->decode_utf8      = false;

            foreach ($this->payload as $key => $item) {
                $result = $soapClient->{$this->function}($item);
                $this->parseResult($soapClient, $result, $key);
            }

            unset($soapClient);
        } else {
            throw new Exception($this->l('The Mondial Relay webservice is currently unavailable.', 'MondialrelayService'));
        }

        return true;
    }

    /**
     * Parses a result retrieved from the SOAP client, and sets $result
     * variable
     *
     * @param SoapClient $soapClient
     * @param mixed $result
     * @param int $key The index of the item sent from the $payload array
     */
    abstract protected function parseResult($soapClient, $result, $key);
    
    public function setEnseigne($enseigne)
    {
        $this->webservice_enseigne = $enseigne;
    }
    
    public function setPrivateKey($key)
    {
        $this->webservice_key = $key;
    }

    public function getResult()
    {
        return $this->result;
    }
    
    public function getErrors()
    {
        return $this->errors;
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
    protected function l($string, $specific = false, $sprintf = null)
    {
        return Translate::getModuleTranslation('mondialrelay', $string, $specific ? $specific : get_class($this), $sprintf);
    }
    
    public function getErrorFromStatCode($code)
    {
        if (empty(self::$webservice_errorCodes)) {
            self::$webservice_errorCodes = array(
                1 => $this->l('Incorrect merchant', 'MondialrelayService'),
                2 => $this->l('Merchant number empty', 'MondialrelayService'),
                3 => $this->l('Incorrect merchant account number', 'MondialrelayService'),
                5 => $this->l('Incorrect Merchant shipment reference', 'MondialrelayService'),
                7 => $this->l('Incorrect Consignee reference', 'MondialrelayService'),
                8 => $this->l('Incorrect password or hash', 'MondialrelayService'),
                9 => $this->l('Unknown or not unique city', 'MondialrelayService'),
                10 => $this->l('Incorrect type of collection', 'MondialrelayService'),
                11 => $this->l('Point Relais collection number incorrect', 'MondialrelayService'),
                12 => $this->l('Point Relais collection country incorrect', 'MondialrelayService'),
                13 => $this->l('Incorrect type of delivery', 'MondialrelayService'),
                14 => $this->l('Incorrect delivery Point Relais number', 'MondialrelayService'),
                15 => $this->l('Point Relais delivery country.incorrect', 'MondialrelayService'),
                20 => $this->l('Incorrect parcel weight', 'MondialrelayService'),
                21 => $this->l('Incorrect developped lenght (length + height)', 'MondialrelayService'),
                22 => $this->l('Incorrect parcel size', 'MondialrelayService'),
                24 => $this->l('Incorrect shipment number', 'MondialrelayService'),
                26 => $this->l('Incorrect assembly time', 'MondialrelayService'),
                27 => $this->l('Incorrect mode of collection or delivery', 'MondialrelayService'),
                28 => $this->l('Incorrect mode of collection', 'MondialrelayService'),
                29 => $this->l('Incorrect mode of delivery', 'MondialrelayService'),
                30 => $this->l('Incorrect address (L1)', 'MondialrelayService'),
                31 => $this->l('Incorrect address (L2)', 'MondialrelayService'),
                33 => $this->l('Incorrect address (L3)', 'MondialrelayService'),
                34 => $this->l('Incorrect address (L4)', 'MondialrelayService'),
                35 => $this->l('Incorrect city', 'MondialrelayService'),
                36 => $this->l('Incorrect zipcode', 'MondialrelayService'),
                37 => $this->l('Incorrect country', 'MondialrelayService'),
                38 => $this->l('Incorrect phone number', 'MondialrelayService'),
                39 => $this->l('Incorrect e-mail', 'MondialrelayService'),
                40 => $this->l('Missing parameters', 'MondialrelayService'),
                42 => $this->l('Incorrect COD value', 'MondialrelayService'),
                43 => $this->l('Incorrect COD currency', 'MondialrelayService'),
                44 => $this->l('Incorrect shipment value', 'MondialrelayService'),
                45 => $this->l('Incorrect shipment value currency', 'MondialrelayService'),
                46 => $this->l('End of shipments number range reached', 'MondialrelayService'),
                47 => $this->l('Incorrect number of parcels', 'MondialrelayService'),
                48 => $this->l('Multi-Parcel not permitted at Point Relais', 'MondialrelayService'),
                49 => $this->l('Incorrect action', 'MondialrelayService'),
                60 => $this->l('Incorrect text field (this error code has no impact)', 'MondialrelayService'),
                61 => $this->l('Incorrect notification request', 'MondialrelayService'),
                62 => $this->l('Incorrect extra delivery information', 'MondialrelayService'),
                63 => $this->l('Incorrect insurance', 'MondialrelayService'),
                64 => $this->l('Incorrect assembly time', 'MondialrelayService'),
                65 => $this->l('Incorrect appointement', 'MondialrelayService'),
                66 => $this->l('Incorrect take back', 'MondialrelayService'),
                67 => $this->l('Incorrect latitude', 'MondialrelayService'),
                68 => $this->l('Incorrect longitude', 'MondialrelayService'),
                69 => $this->l('Incorrect merchant code', 'MondialrelayService'),
                70 => $this->l('Incorrect Point Relais number', 'MondialrelayService'),
                71 => $this->l('Incorrect Nature de point de vente non valide', 'MondialrelayService'),
                74 => $this->l('Incorrect language', 'MondialrelayService'),
                78 => $this->l('Incorrect country of collection', 'MondialrelayService'),
                79 => $this->l('Incorrect country of delivery', 'MondialrelayService'),
                /* This is only here as a reminder
                80 => $this->l('Tracking code : Recorded parcel', 'MondialrelayService'),
                81 => $this->l('Tracking code : Parcel in process at Mondial Relay', 'MondialrelayService'),
                82 => $this->l('Tracking code : Delivered parcel', 'MondialrelayService'),
                83 => $this->l('Tracking code : Anomaly', 'MondialrelayService'),
                 */
                /* We should never use this.
                84 => $this->l('(Reserved tracking code)', 'MondialrelayService'),
                85 => $this->l('(Reserved tracking code)', 'MondialrelayService'),
                86 => $this->l('(Reserved tracking code)', 'MondialrelayService'),
                87 => $this->l('(Reserved tracking code)', 'MondialrelayService'),
                88 => $this->l('(Reserved tracking code)', 'MondialrelayService'),
                89 => $this->l('(Reserved tracking code)', 'MondialrelayService'),
                 */
                92 => $this->l('The Point Relais country code and the consignee country code are different, or you have insufficient funds (pre-paid accounts)', 'MondialrelayService'),
                93 => $this->l('No information given by the sorting plan. If you want to do a collection or delivery at Point Relais, please check it is available. If you want to do a home delivery, please check if the zipcode exists.', 'MondialrelayService'),
                94 => $this->l('Unknown parcel', 'MondialrelayService'),
                95 => $this->l('Merchant account not activated', 'MondialrelayService'),
                97 => $this->l('Incorrect security key', 'MondialrelayService'),
                98 => $this->l('Generic error (Incorrect parameters)', 'MondialrelayService'),
                99 => $this->l('Generic error of service system. This error can happen due to a technical service problem. Please notify this error to Mondial Relay with the date and time of the request as well as the parameters sent in order to verify', 'MondialrelayService'),
            );
        }
        return !empty(self::$webservice_errorCodes[$code]) ? self::$webservice_errorCodes[$code] : self::$webservice_errorCodes[99];
    }
}
