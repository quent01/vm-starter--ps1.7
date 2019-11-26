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

class Woo2PressImportHistory extends ObjectModel
{
    public $file_name;
    public $id_category_default;
    public $id_manufacture;
    public $id_supplier;
    public $id_category_cms;
    public $import_multi_shop;
    public $delete_before_importing;
    public $force_all_id_number;
    public $data;
    public $content;
    public $currentindex=1;
    public $number_import=0;
    public $number_import2=0;
    public $date_import;
    public static $definition = array(
        'table' => 'ets_woo2press_import_history',
        'primary' => 'id_import_history',
        'fields' => array(
            'file_name' =>    array('type' => self::TYPE_STRING),
            'id_category_default' => array('type'=>self::TYPE_INT),
            'id_manufacture' => array('type'=>self::TYPE_INT),
            'id_supplier' => array('type'=>self::TYPE_INT),
            'id_category_cms' => array('type'=>self::TYPE_INT),
            'import_multi_shop' => array('type'=>self::TYPE_INT),
            'delete_before_importing' => array('type'=>self::TYPE_INT),
            'force_all_id_number' => array('type'=>self::TYPE_INT),
            'data' =>            array('type' => self::TYPE_STRING),
            'content' =>            array('type' => self::TYPE_HTML),
            'currentindex' => array('type'=>self::TYPE_INT),
            'number_import' => array('type'=>self::TYPE_INT),
            'number_import2' => array('type'=>self::TYPE_INT),
            'date_import' =>         array('type' => self::TYPE_STRING),
        ),
    );
    public	function __construct($id_item = null, $id_lang = null, $id_shop = null)
	{
		parent::__construct($id_item, $id_lang, $id_shop);
	}
    public function delete()
    {
        foreach (glob(dirname(__FILE__).'/../xml/'.$this->file_name.'/*.*') as $filename) {
            @unlink($filename);
        }
        @rmdir(dirname(__FILE__).'/../xml/'.$this->file_name);
        @unlink(dirname(__FILE__).'/../cache/import/'.$this->file_name.'.zip');
        $_module = new Ets_woo2pres();
        if($_module->tables)
        foreach($_module->tables as $table)
        {
            Db::getInstance()->execute('DELETE FROM '._DB_PREFIX_.'ets_woo2press_'.pSQL($table).'_import WHERE id_import_history='.(int)$this->id);
        }
        return parent::delete();
    }
}