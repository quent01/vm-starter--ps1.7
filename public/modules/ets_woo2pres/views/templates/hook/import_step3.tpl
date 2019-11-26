{*
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
* needs, please contact us for extra customization service at an affordable price
*
*  @author ETS-Soft <etssoft.jsc@gmail.com>
*  @copyright  2007-2019 ETS-Soft
*  @license    Valid for 1 website (or project) for each purchase of license
*  International Registered Trademark & Property of ETS-Soft
*}
{if isset($import_product) && $import_product}
    <div class="form-group category_default">
        <label class="control-label col-lg-4">{l s='Default category' mod='ets_woo2pres'}</label>
        <div class="col-lg-5">
            <select name="categoryBox">
                {$categoryotpionsHtml nofilter}
            </select>
        </div>
    </div>
    <div class="form-group supplier_default">
        <label class="control-label col-lg-4">{l s='Default Supplier ' mod='ets_woo2pres'}</label>
        <div class="col-lg-5">
            <select name="id_supplier_default">
                <option value="0">{l s='None' mod='ets_woo2pres'}</option>
                {if $suppliers}
                    {foreach from =$suppliers item='supplier'}
                        <option value="{$supplier.id_supplier|intval}"{if $selected_id_supplier==$supplier.id_supplier} selected="selected"{/if} >{$supplier.name|escape:'html':'UTF-8'}</option>
                    {/foreach}
                {/if}
            </select>
        </div>
    </div>
    <div class="form-group manufacturer_default">
        <label class="control-label col-lg-4">{l s='Default manufacturer' mod='ets_woo2pres'}</label>
        <div class="col-lg-5">
            <select name="id_manufacturer_default">
                <option value="0">{l s='None' mod='ets_woo2pres'}</option>
                {foreach from =$manufacturers item='manufacturer'}
                    <option value="{$manufacturer.id_manufacturer|intval}"{if $selected_id_manufacturer==$manufacturer.id_manufacturer} selected="selected"{/if}>{$manufacturer.name|escape:'html':'UTF-8'}</option>
                {/foreach}
            </select>
        </div>
    </div>
{/if}
{if isset($import_cms) && $import_cms}
    <div class="form-group cms_category_default">
        <label class="control-label col-lg-4" for="id_cmscategorydefault">{l s='Default CMS category ' mod='ets_woo2pres'}</label>
        <div class="col-lg-5">
            <select name="id_cmscategorydefault" id="id_cmscategorydefault">
                {$cmsCategoryotpionsHtml nofilter}
            </select>
        </div>
    </div>
{/if}
<div class="form-group ">
    <label class="control-label col-lg-4">{l s='Delete data before migrating' mod='ets_woo2pres'}</label>
    <div class="col-lg-8">
        <span class="switch prestashop-switch fixed-width-lg">
            <input id="import_delete_before_on" type="radio"{if $ets_woo2press_import_delete} checked="checked"{/if} value="1" name="import_delete_before" />
            <label for="import_delete_before_on">{l s='Yes' mod='ets_woo2pres'}</label>
            <input id="import_delete_before_off" type="radio"{if !$ets_woo2press_import_delete} checked="checked"{/if} value="0" name="import_delete_before" />
            <label for="import_delete_before_off">{l s='No' mod='ets_woo2pres'}</label>
            <a class="slide-button btn"></a>
        </span>
        <p class="help-block">{l s='Enable this option will delete the same kinds of data (that you are migrating) on your website before importing new data so make sure you want to do that' mod='ets_woo2pres'} </p>
    </div>
    <label class="control-label col-lg-4">{l s='Force all ID numbers' mod='ets_woo2pres'}</label>
    <div class="col-lg-8">
        <span class="switch prestashop-switch fixed-width-lg">
            <input id="import_force_all_id_on" type="radio"{if $ets_woo2press_import_force_all_id} checked="checked"{/if} value="1" name="import_force_all_id" />
            <label for="import_force_all_id_on">{l s='Yes' mod='ets_woo2pres'}</label>
            <input id="import_force_all_id_off" type="radio"{if !$ets_woo2press_import_force_all_id} checked="checked"{/if} value="0" name="import_force_all_id" />
            <label for="import_force_all_id_off">{l s='No' mod='ets_woo2pres'}</label>
            <a class="slide-button btn"></a>
        </span>
        <p class="help-block">{l s='Keep ID numbers (such as product ID, customer ID, etc) of source website.' mod='ets_woo2pres'} </p>
    </div>
    {if in_array('employees',$ets_woo2press_import) || in_array('customers',$ets_woo2press_import)}
        <label class="control-label col-lg-4">{l s='Keep customer password' mod='ets_woo2pres'}</label>
        <div class="col-lg-8">
            <span class="switch prestashop-switch fixed-width-lg">
                <input id="import_create_new_passwd_off" type="radio" checked="checked" value="0" name="import_create_new_passwd" />
                <label for="import_create_new_passwd_off">{l s='Yes' mod='ets_woo2pres'}</label>
                <input id="import_create_new_passwd_on" type="radio" value="1" name="import_create_new_passwd" />
                <label for="import_create_new_passwd_on">{l s='No' mod='ets_woo2pres'}</label>
                <a class="slide-button btn"></a>
                
            </span>
            <p class="help-block">
                <span class="no_cookie_key">{l s='A password file with all new plain passwords can be downloaded when you complete the migration.' mod='ets_woo2pres'}</span>
                <span class="cookie_key">{l s='You will need to install ' mod='ets_woo2pres'} <a href="{$mod_dr|escape:'html':'UTF-8'}plugins/ets_woo2prespwkeeper.zip" target="_blank">{l s='"Prestashop Password Keeper"' mod='ets_woo2pres'}</a>  {l s='module to recover customer passwords. Refer to help tab and user guide document for more details.' mod='ets_woo2pres'}</span>
            </p>
        </div>
    {/if}
</div>
<script>
$(document).ready(function(){
    $('input[name="import_create_new_passwd"]').click(function(){
        if($('input[name="import_create_new_passwd"]:checked').val()=='1')
        {
            $('.help-block .cookie_key').hide();
            $('.help-block .no_cookie_key').show();
        }
        else
        {
            $('.help-block .cookie_key').show();
            $('.help-block .no_cookie_key').hide();
        }
            
    });
    if($('input[name="import_create_new_passwd"]:checked').val()=='1')
    {
        $('.help-block .cookie_key').hide();
        $('.help-block .no_cookie_key').show();
    }
    else
    {
        $('.help-block .cookie_key').show();
        $('.help-block .no_cookie_key').hide();
    }
});
</script>