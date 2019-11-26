<?php
/**
 * 2007-2018 ETS-Soft
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
 *  @copyright  2007-2018 ETS-Soft
 *  @license    Valid for 1 website (or project) for each purchase of license
 *  International Registered Trademark & Property of ETS-Soft
 */

if (!defined('_PS_VERSION_'))
	exit;      
class Ets_woo2prespwkeeper extends Module
{
    public $is14;
    public function __construct()
	{
		$this->name = 'ets_woo2prespwkeeper';
		$this->tab = 'front_office_features';
		$this->version = '1.0.1';
		$this->author = 'ETS-Soft';
		$this->need_instance = 0;
		$this->secure_key = Tools::encrypt($this->name);        
		$this->bootstrap = true;
        $this->module_key = '8c4686a2fe6d643fe0dea93e2e0a7082';
		parent::__construct();
        $this->context = Context::getContext();
        $this->url_module = $this->_path;
        $this->displayName = $this->l('Woo2Pres Password Keeper');
		$this->description = $this->l('Keep Wordpress passwords when migrate Woocommerce to Prestashop');
        $this->is14 = version_compare(_PS_VERSION_, '1.5.0', '<=')&&version_compare(_PS_VERSION_, '1.4.0', '>=');
    }
    /**
	 * @see Module::install()
	 */
    public function install()
	{
        return parent::install() && $this->overrideDir();     
    }
    /**
	 * @see Module::uninstall()
	 */
	public function uninstall()
	{
        return parent::uninstall();
    }
    public function overrideDir()
    {
        
        if (!$this->is14)
            return true;  
         /*@override class*/   
        $dir = _PS_ROOT_DIR_.'/override/classes';
        if(!is_dir($dir)){
            @mkdir($dir, 0777);
        }
        if (is_dir($dir)){
            if (($dest =  $dir.'/Customer.php') && !file_exists($dest)){
                $source = dirname(__FILE__).'/classes/Customer14.php';
                Ets_woo2prespwkeeper::copy($source, $dest);
            }
        }
        return true;
    }  
    public static function copy($source, $destination, $stream_context = null)
    {
        if (is_null($stream_context) && !preg_match('/^https?:\/\//', $source)) {
            return @copy($source, $destination);
        }
        return @file_put_contents($destination, Tools::file_get_contents($source, false, $stream_context));
    }  
}