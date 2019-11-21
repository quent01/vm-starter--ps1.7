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
 * Get informations for relays
 */
class MondialrelayServiceInfosPointRelais extends MondialrelayService
{
    /** @inheritdoc */
    protected $function = 'WSI4_PointRelais_Recherche';
    
    /** @inheritdoc */
    protected $fields = array(
        'Enseigne' => array(
            'required' => true,
            'regex' => '#^[0-9A-Z]{2}[0-9A-Z ]{6}$#'
        ),
        'Pays' => array(
            'required' => true,
            'regex' => '#^[A-Z]{2}$#'
        ),
        'NumPointRelais' => array(
            'required' => true,
            'regex' => '#^[0-9]{6}$#'
        ),
        // Required, but set by the service if it's absent
        'Security' => array(
            'regex' => '#^[0-9A-Z]{32}$#'
        ),
    );
    
    /**
     * @inheritdoc
     */
    public function init($data)
    {
        $this->data = $data;
        return $this->setPayloadFromData();
    }
    
    /**
     * @inheritdoc
     */
    protected function parseResult($soapClient, $result, $key)
    {
        $this->result[$key] = $result->{$this->function . "Result"};
        
        // Remove useless and undocumented nesting level...
        if (isset($this->result[$key]->PointsRelais->PointRelais_Details)) {
            $this->result[$key]->PointsRelais = $this->result[$key]->PointsRelais->PointRelais_Details;
        }
    }
}
