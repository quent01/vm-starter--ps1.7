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
<div class="form-group">							
	<label class="col-lg-12">{l s='Which kinds of data do you want to migrate?' mod='ets_woo2pres'}</label>
    <div class="col-lg-9">
        {if $pres_version!=1.4}
            {if isset($assign['shops']) && $assign['shops'] && in_array('shops',$export_datas)}
                <div class="checkbox">
                    <label for="data_import_shops">
                        <input{if in_array('shops',$ets_woo2press_import)} checked="checked"{/if} name="data_import[]" value="shops" type="checkbox" id="data_import_shops" />
                        <span class="data_checkbox_style"><i class="icon icon-check"></i></span>
                        {l s='Shops' mod='ets_woo2pres'}&nbsp;({$assign['shops']|intval})</label>
                </div>
            {/if}
        {/if}
        {if isset($assign['employees']) && $assign['employees'] && in_array('employees',$export_datas)}
            <div class="checkbox">
                <label for="data_import_employees">
                <input{if in_array('employees',$ets_woo2press_import)} checked="checked"{/if} name="data_import[]" value="employees" type="checkbox" id="data_import_employees" />
                <span class="data_checkbox_style"><i class="icon icon-check"></i></span>{l s='Employees' mod='ets_woo2pres'} ({$assign['employees']|intval})</label>
            </div>
        {/if}
        {if isset($assign['categories']) && $assign['categories'] && in_array('categories',$export_datas)}
            <div class="checkbox">
                <label for="data_import_categories"><input{if in_array('categories',$ets_woo2press_import)} checked="checked"{/if} name="data_import[]" value="categories" type="checkbox" id="data_import_categories" /><span class="data_checkbox_style"><i class="icon icon-check"></i></span>{l s='Woo Product categories' mod='ets_woo2pres'} ({$assign['categories']|intval})</label>
            </div>
        {/if}
        {if isset($assign['manufactures']) && $assign['manufactures'] && in_array('manufactures',$export_datas)}
            <div class="checkbox">
                <label for="data_import_manufactures"><input{if in_array('manufactures',$ets_woo2press_import)} checked="checked"{/if} name="data_import[]" value="manufactures" type="checkbox" id="data_import_manufactures" /><span class="data_checkbox_style"><i class="icon icon-check"></i></span>{l s='Manufacturers' mod='ets_woo2pres'} ({$assign['manufactures']|intval})</label>
            </div>
        {/if}
        {if isset($assign['suppliers']) && $assign['suppliers'] && in_array('suppliers',$export_datas)}
            <div class="checkbox">
                <label for="data_import_suppliers"><input{if in_array('suppliers',$ets_woo2press_import)} checked="checked"{/if} name="data_import[]" value="suppliers" type="checkbox" id="data_import_suppliers" /><span class="data_checkbox_style"><i class="icon icon-check"></i></span>{l s='Suppliers' mod='ets_woo2pres'} ({$assign['suppliers']|intval})</label>
            </div>
        {/if}
        {if isset($assign['customers']) && $assign['customers'] && in_array('customers',$export_datas)}
            <div class="checkbox">
                <label for="data_import_customers"><input{if in_array('customers',$ets_woo2press_import)} checked="checked"{/if} name="data_import[]" value="customers" type="checkbox" id="data_import_customers" /><span class="data_checkbox_style"><i class="icon icon-check"></i></span>{l s='Woo Customers & addresses' mod='ets_woo2pres'} ({$assign['customers']|intval})</label>
            </div>
        {/if}
        {if isset($assign['carriers']) && $assign['carriers'] && in_array('carriers',$export_datas)}
            <div class="checkbox">
                <label for="data_import_carriers"><input{if in_array('carriers',$ets_woo2press_import)} checked="checked"{/if} name="data_import[]" value="carriers" type="checkbox" id="data_import_carriers" /><span class="data_checkbox_style"><i class="icon icon-check"></i></span>{l s='Woo Carriers & shipping prices' mod='ets_woo2pres'} ({$assign['carriers']|intval})</label>
            </div>
        {/if}
        {if $pres_version==1.4}
            {if isset($assign['vouchers']) && $assign['vouchers'] && in_array('vouchers',$export_datas)}
                <div class="checkbox">
                    <label for="data_import_vouchers"><input{if in_array('vouchers',$ets_woo2press_import)} checked="checked"{/if} name="data_import[]" value="vouchers" type="checkbox" id="data_import_vouchers" /><span class="data_checkbox_style"><i class="icon icon-check"></i></span>{l s='Vouchers' mod='ets_woo2pres'} ({$assign['vouchers']|intval})</label>
                </div>
            {/if}
        {else}
            {if isset($assign['cart_rules']) && $assign['cart_rules'] && in_array('cart_rules',$export_datas)}
                <div class="checkbox">
                    <label for="data_import_cart_rules"><input{if in_array('cart_rules',$ets_woo2press_import)} checked="checked"{/if} name="data_import[]" value="cart_rules" type="checkbox" id="data_import_cart_rules" /><span class="data_checkbox_style"><i class="icon icon-check"></i></span>{l s='Cart rules' mod='ets_woo2pres'} ({$assign['cart_rules']|intval})</label>
                </div>
            {/if}
            {if isset($assign['catelog_rules']) && $assign['catelog_rules'] && in_array('catelog_rules',$export_datas)}
                <div class="checkbox">
                    <label for="data_import_catelog_rules"><input{if in_array('catelog_rules',$ets_woo2press_import)} checked="checked"{/if} name="data_import[]" value="catelog_rules" type="checkbox" id="data_import_catelog_rules" /><span class="data_checkbox_style"><i class="icon icon-check"></i></span>{l s='Catelog rules' mod='ets_woo2pres'} ({$assign['catelog_rules']|intval})</label>
                </div>
            {/if}
        {/if}
        {if isset($assign['products']) && $assign['products'] && in_array('products',$export_datas)}
            <div class="checkbox">
                <label for="data_import_products"><input{if in_array('products',$ets_woo2press_import)} checked="checked"{/if} name="data_import[]" value="products" type="checkbox" id="data_import_products" /><span class="data_checkbox_style"><i class="icon icon-check"></i></span>{l s='Woo Products' mod='ets_woo2pres'} ({$assign['products']|intval})</label>
            </div>
        {/if}
        {if isset($assign['orders']) && $assign['orders'] && in_array('orders',$export_datas)}
            <div class="checkbox">
                <label for="data_import_orders"><input{if in_array('orders',$ets_woo2press_import)} checked="checked"{/if} name="data_import[]" value="orders" type="checkbox" id="data_import_orders" /><span class="data_checkbox_style"><i class="icon icon-check"></i></span>{l s='Woo Orders & shopping carts' mod='ets_woo2pres'} ({$assign['orders']|intval})</label>
            </div>
        {/if}
        {if isset($assign['cms_cateogories']) && $assign['cms_cateogories'] && in_array('cms_cateogories',$export_datas)}
            <div class="checkbox">
                <label for="data_import_cms_cateogories"><input{if in_array('cms_cateogories',$ets_woo2press_import)} checked="checked"{/if} name="data_import[]" value="cms_cateogories" type="checkbox" id="data_import_cms_cateogories" /><span class="data_checkbox_style"><i class="icon icon-check"></i></span>{l s='WP post categories (to CMS categories)' mod='ets_woo2pres'} ({$assign['cms_cateogories']|intval})</label>
            </div>
        {/if}
        {if isset($assign['cms']) && $assign['cms'] && in_array('cms',$export_datas)}
            <div class="checkbox">
                <label for="data_import_cms"><input{if in_array('cms',$ets_woo2press_import)} checked="checked"{/if} name="data_import[]" value="cms" type="checkbox" id="data_import_cms" /><span class="data_checkbox_style"><i class="icon icon-check"></i></span>{l s='WP posts (to CMS)' mod='ets_woo2pres'} ({$assign['cms']|intval})</label>
            </div>
        {/if}
        {if isset($assign['page_cms']) && $assign['page_cms'] && in_array('page_cms',$export_datas)}
            <div class="checkbox">
                <label for="data_import_page_cms"><input{if in_array('page_cms',$ets_woo2press_import)} checked="checked"{/if} name="data_import[]" value="page_cms" type="checkbox" id="data_import_page_cms" /><span class="data_checkbox_style"><i class="icon icon-check"></i></span>{l s='WP pages (to CMS)' mod='ets_woo2pres'} ({$assign['page_cms']|intval})</label>
            </div>
        {/if}
        {if isset($assign['messages']) && $assign['messages'] && in_array('messages',$export_datas)}
            <div class="checkbox">
                <label for="data_import_messages"><input{if in_array('messages',$ets_woo2press_import)} checked="checked"{/if} name="data_import[]" value="messages" type="checkbox" id="data_import_messages" /><span class="data_checkbox_style"><i class="icon icon-check"></i></span>{l s='Contact form messages' mod='ets_woo2pres'} ({$assign['messages']|intval})</label>
            </div>
        {/if}
    </div>
</div>
<div class="alert-warning alert">
    {l s='We recommend you to import all kinds of data for migration purpose. However you can deselect some kinds of data if you really do not need them.' mod='ets_woo2pres'}
</div>