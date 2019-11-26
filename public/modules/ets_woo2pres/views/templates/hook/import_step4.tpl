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
<p>{l s='Please review and confirm the migration before processing it!' mod='ets_woo2pres'}</p>
<div class="data-to-export">
    <div>{l s='Data migrated:' mod='ets_woo2pres'}</div>
    <ul class="list-data-to-import">
        {if $ets_woo2press_import}
            {foreach from=$ets_woo2press_import item='data_import'}
                {if $data_import!='page_cms'}
                <li>
                    {if $data_import=='employees'}
                        {l s='Employees' mod='ets_woo2pres'}
                    {/if}
                    {if $data_import=='categories'}
                        {l s='Product categories' mod='ets_woo2pres'}
                    {/if}
                    {if $data_import=='manufactures'}
                        {l s='Manufactures' mod='ets_woo2pres'}
                    {/if}
                    {if $data_import=='suppliers'}
                        {l s='Suppliers' mod='ets_woo2pres'}
                    {/if}
                    {if $data_import=='products'}
                        {l s='Products' mod='ets_woo2pres'}
                    {/if}
                    {if $data_import=='customers'}
                        {l s='Customers' mod='ets_woo2pres'}
                    {/if}
                    {if $data_import=='carriers'}
                        {l s='Carriers' mod='ets_woo2pres'}
                    {/if}
                    {if $data_import=='cart_rules'}
                        {l s='Cart rules' mod='ets_woo2pres'}
                    {/if}
                    {if $data_import=='catelog_rules'}
                        {l s='Catalog rules' mod='ets_woo2pres'}
                    {/if}
                    {if $data_import=='orders'}
                        {l s='Orders' mod='ets_woo2pres'}
                    {/if}
                    {if $data_import=='cms_cateogories'}
                        {l s='CMS categories' mod='ets_woo2pres'}
                    {/if}
                    {if $data_import=='cms'}
                        {l s='CMSs' mod='ets_woo2pres'}
                    {/if}
                    {if $data_import=='messages'}
                        {l s='Contact form messages' mod='ets_woo2pres'}
                    {/if}
                    {if $data_import=='vouchers'}
                        {l s='Vouchers' mod='ets_woo2pres'}
                    {/if}
                    {if $data_import=='shops'}
                        {l s='Shops' mod='ets_woo2pres'}
                    {/if}
                
                    ({if $data_import=='cms' && isset($assign['page_cms'])}{$assign[$data_import]+$assign['page_cms']|escape:'html':'UTF-8'}{else}{$assign[$data_import]|escape:'html':'UTF-8'}{/if} {if $assign[$data_import]<=1}{l s='item' mod='ets_woo2pres'}{else}{l s='items' mod='ets_woo2pres'}{/if})
                </li>
                {/if}
            {/foreach}
        {/if}
    </ul>
</div>
<div class="data-format-to-import">
    <div>{l s='Activated migration option:' mod='ets_woo2pres'}</div>
    <ul>
        <li>
            <span>{l s='Delete data before importing:' mod='ets_woo2pres'}&nbsp;{if $ets_woo2press_import_delete}{l s='YES' mod='ets_woo2pres'}{else}{l s='NO' mod='ets_woo2pres'}{/if}</span>
        </li>
        <li>
            <span>{l s='Force all ID numbers:' mod='ets_woo2pres'}&nbsp;{if $ets_woo2press_import_force_all_id}{l s='YES' mod='ets_woo2pres'}{else}{l s='NO' mod='ets_woo2pres'}{/if}</span>
        </li>
        <li>
            <span>{l s='Keep customer password:' mod='ets_woo2pres'}&nbsp;{if !$ets_regenerate_customer_passwords}{l s='YES' mod='ets_woo2pres'}{else}{l s='NO' mod='ets_woo2pres'}{/if}</span>
        </li>
    </ul>
</div>
<div class="data-format-to-import">
    <div>{l s='Source website information:' mod='ets_woo2pres'}</div>
    <ul>
        <li>
            <span>{l s='Site URL: ' mod='ets_woo2pres'}
            {if count($link_sites)>1}
                {foreach from=$link_sites key='key' item='link_site'}
                    <p>{l s='Shop' mod='ets_woo2pres'}{$key+1|intval}: &nbsp;<a target="_blank" href="{$link_site|escape:'html':'UTF-8'}">{$link_site|escape:'html':'UTF-8'}</a></p>
                {/foreach}
            {else}
                <a target="_blank" href="{$link_sites[0]|escape:'html':'UTF-8'}">{$link_sites[0]|escape:'html':'UTF-8'}</a>
            {/if}
            
            </span>
        </li>
        <li>
            <span>{l s='Platform: ' mod='ets_woo2pres'}{$platform|escape:'html':'UTF-8'}</span>
        </li>
        <li>
            <span>{l s='Version wordpress: ' mod='ets_woo2pres'}{$version_wp|escape:'html':'UTF-8'}</span>
        </li>
        {if $version_woo}
            <li>
            <span>{l s='Version woocommece: ' mod='ets_woo2pres'}{$version_woo|escape:'html':'UTF-8'}</span>
            </li>
        {/if}
    </ul>
</div>
<div class="alert alert-warning">
    {l s='You are going to make big changes to website database and images.' mod='ets_woo2pres'}
    {l s='Make sure you have a complete backup of your website (both files and database)' mod='ets_woo2pres'}
</div>
<div class="form-group">
    <div class="checkbox col-xs-12">
        <label for="have_made_backup" class="one-line">
            <input id="have_made_backup" name="have_made_backup" type="checkbox"/><span class="data_checkbox_style"><i class="icon icon-check"></i></span> {l s='I have made a complete backup of this website' mod='ets_woo2pres'}
        </label>
    </div>
</div>
