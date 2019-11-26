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

$context = Context::getContext();
$ets_woo2pres = Module::getInstanceByName('ets_woo2pres');
if(Tools::isSubmit('submitImport'))
{
    if (ob_get_length() > 0) {
        ob_end_clean();
    }
    
    $errors=array();
    $step=(int)Tools::getValue('step');
    switch ($step) {
        case 1:
            if(Tools::getValue('source_type'))
            {
                if(Tools::getValue('source_type')=='link')
                    $ets_woo2pres->processImport(Tools::getValue('link_file'));
                elseif(Tools::getValue('source_type')=='url_site')
                {
                    $url = Tools::getValue('link_site_connector') ? Tools::getValue('link_site_connector'):'#';
                    $ets_woo2pres->processImport($url);
                }
                else
                    $ets_woo2pres->processImport();
            }
            else
            {
                $step++;
            }
            break;
        case 2:
            if(Tools::getValue('data_import'))
            {
                $id_import_history=$context->cookie->id_import_history;
                $data = implode(',',Tools::getValue('data_import'));
                Db::getInstance()->Execute('UPDATE '._DB_PREFIX_.'ets_woo2press_import_history SET data="'.pSQL($data).'" where id_import_history='.(int)$id_import_history);
                $step++;
            }
            else
                $errors = $ets_woo2pres->l('Please select kinds of data you want to import');
            break;
        case 3:
            $id_import_history=$context->cookie->id_import_history;
            $id_category_default = (int)Tools::getValue('categoryBox');
            $id_supplier = Tools::getValue('id_supplier_default');
            $id_manufacture = Tools::getValue('id_manufacturer_default');
            $id_category_cms = Tools::getValue('id_cmscategorydefault');
            $import_multi_shop= Tools::getValue('import_multi_shop');
            $delete_before_importing = Tools::getValue('import_delete_before');
            $force_all_id_number =Tools::getValue('import_force_all_id');
            Configuration::updateValue('ETS_WOO2PRESS_NEW_PASSWD',Tools::getValue('import_create_new_passwd'));
            Configuration::updateValue('PS_WOO2PRESS_IMPORTING','');
            Db::getInstance()->Execute('UPDATE '._DB_PREFIX_.'ets_woo2press_import_history 
            SET id_category_default="'.(int)$id_category_default.'",
            id_supplier="'.(int)$id_supplier.'",
            id_manufacture="'.(int)$id_manufacture.'",
            id_category_cms="'.(int)$id_category_cms.'",
            import_multi_shop="'.(int)$import_multi_shop.'",
            delete_before_importing="'.(int)$delete_before_importing.'",
            force_all_id_number="'.(int)$force_all_id_number.'" WHERE id_import_history='.(int)$id_import_history);
            $step++;
            die(
                Tools::jsonEncode(
                    array(
                        'error'=>false,
                        'step'=>$step,
                        'form_step'=>$ets_woo2pres->displayFromStep($step),
                        'popup_import'=>$ets_woo2pres->displayPopupHtml(),
                    )
                )
            );
        case 4:
                $step++;
                if($ets_woo2pres->pres_version==1.4)
                {
                    $ets_woo2pres->processImportData14();
                } 
                else
                {
                    $ets_woo2pres->processImportData();
                }
            break;
        case 5:
            break;
    } 
    if($errors)
    {
        die(
            Tools::jsonEncode(
                array(
                    'error'=>true,
                    'errors' => $ets_woo2pres->displayError($errors),
                )
            )
        );
    }
    else
    {
        die(
            Tools::jsonEncode(
                array(
                    'error'=>false,
                    'step'=>$step,
                    'form_step'=>$ets_woo2pres->displayFromStep($step),
                )
            )
        );
    }
}