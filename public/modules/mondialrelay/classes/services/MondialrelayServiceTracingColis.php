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

/**
 * Note : The "usual" testing account from MR won't work with this service, as
 * the sent data is never actually inserted in their system. Testing requires
 * a separate account provided by MR. The expedition "07155324" was originally
 * used to test this class.
 */
class MondialrelayServiceTracingColis extends MondialrelayService
{
    /** @inheritdoc */
    protected $function = 'WSI2_TracingColisDetaille';

    /** @inheritdoc */
    protected $fields = array(
        'Enseigne'     => array(
            'required' => true,
            'regex'    => '#^[0-9A-Z]{2}[0-9A-Z ]{6}$#'
        ),
        'Expedition'     => array(
            'required' => true,
            'regex'    => '#^[0-9]{8}$#'
        ),
        'Langue'     => array(
            'required' => true,
            // Original regex : ^[A-Z]{2}$
            // But we only have 3 languages available, and we'll likely use only
            // one, so...
            'regex'    => '#^FR|ES|NL$#'
        ),
        // Required, but set by the service if it's absent
        'Security'     => array(
            'regex' => '#^[0-9A-Z]{32}$#'
        ),
    );
    
    /** This function doesn't use '0' as a successful STAT code; it has a list
     * instead.
     */
    const STAT_CODE_REGISTERED = 80;
    const STAT_CODE_PROCESSING = 81;
    const STAT_CODE_DELIVERED = 82;
    const STAT_CODE_ANOMALY = 83;
    
    /**
     * @var array Usually retrieved from configuration; the ISO code for the
     * labels language. Can be set for the whole service by using the setter, and
     * will never overwrite already set field
     * @see MondialrelayServiceTracingColis::setLangue()
     * @see MondialrelayServiceTracingColis::preprocessData()
     */
    protected $webservice_Langue = '';
    
    /**
     * @inheritdoc
     */
    protected function __construct()
    {
        parent::__construct();
        // For now, this is only used to automatically update orders, so the
        // language is fixed as we need to check for a string in the result
        $this->webservice_Langue = 'FR';
    }

    /**
     * @inheritdoc
     */
    public function init($data)
    {
        $this->data = $data;
        return $this->setPayloadFromData();
    }

    /**
     * Preprocess a data item
     *
     * @param int $key
     * @param array $item
     *
     * @return array the preprocessed item
     */
    protected function preprocessData($key, $item)
    {
        if (empty($item['Langue'])) {
            $item['Langue'] = $this->webservice_Langue;
        }
        
        return $item;
    }
    
    public function processLangue($key, $value, $item)
    {
        return Tools::strtoupper($value);
    }

    /**
     * @inheritdoc
     */
    protected function parseResult($soapClient, $result, $key)
    {
        $this->result[$key] = $result->{$this->function . "Result"};
        
        if (!isset($this->result[$key]->Tracing)) {
            return;
        }
        
        // Note : many of the items in "Tracing" may be empty. It's not really a
        // problem for now though.
        $this->result[$key]->Tracing = $this->result[$key]->Tracing->ret_WSI2_sub_TracingColisDetaille;
    }
    
    public function setLangue($langue)
    {
        $this->webservice_Langue = $langue;
    }
    
    /**
     * Checks if a STAT code is a valid response from the webservice or an
     * error
     * @param int $stat_code
     * @return bool
     */
    public static function isSuccessStatCode($stat_code)
    {
        return in_array($stat_code, array(
            self::STAT_CODE_REGISTERED,
            self::STAT_CODE_PROCESSING,
            self::STAT_CODE_DELIVERED,
            self::STAT_CODE_ANOMALY
        ));
    }
}
