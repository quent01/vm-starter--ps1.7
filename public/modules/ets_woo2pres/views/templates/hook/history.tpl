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
<div class="dtm-left-block">
    {hook h='woo2pressLeftBlock'}
</div>
<div class="dtm-right-block">
    <div id="import_history" class="panel tab_content import{if $tab_history=='import'} active{/if}">
        {if $imports}
            <table class="import_history_content">
                <tr>
                    <td>{l s='Migration ID' mod='ets_woo2pres'}</td>
                    <td>{l s='Migration details' mod='ets_woo2pres'}</td>
                    <td>{l s='Execution time' mod='ets_woo2pres'}</td>
                    <td>{l s='Action' mod='ets_woo2pres'}</td>
                </tr>
                {foreach from=$imports item='import'}
                    <tr>
                        <td>{$import.id_import_history|intval}</td>
                        <td>{$import.content nofilter}</td>
                        <td>{$import.date_import|escape:'html':'UTF-8'}</td>
                        <td>
                            {if $woo2press_import_last==$import.id_import_history&&!$import.import_ok}
                                <a href="{$link->getAdminLink('AdminWoo2PressImport',true)|escape:'html':'UTF-8'}&resumeImport&id_import_history={$import.id_import_history|intval}"><i class="icon icon-undo"> </i> {l s='Resume migration' mod='ets_woo2pres'}</a>
                            {/if}
                            <a href="{$link->getAdminLink('AdminWoo2PressImport',true)|escape:'html':'UTF-8'}&restartImport&id_import_history={$import.id_import_history|intval}"><i class="icon icon-refresh"> </i> {l s='Restart migration' mod='ets_woo2pres'}</a>
                            <a href="{$link->getAdminLink('AdminWoo2PressHistory',true)|escape:'html':'UTF-8'}&deleteimporthistory&id_import_history={$import.id_import_history|intval}" onclick="return confirm('Do you want to delete item?');"><i class="icon icon-trash" > </i> {l s='Delete' mod='ets_woo2pres'}</a>
                            {if $import.new_passwd_customer}
                                <a href="{$link->getAdminLink('AdminWoo2PressHistory',true)|escape:'html':'UTF-8'}&downloadpasscustomer&id_import_history={$import.id_import_history|intval}"><i class="icon icon-cloud-download"> </i>  {l s='Download new customer passwords' mod='ets_woo2pres'}</a>
                            {/if}
                            {if $import.new_passwd_employee}
                                <a href="{$link->getAdminLink('AdminWoo2PressHistory',true)|escape:'html':'UTF-8'}&downloadpassemployee&id_import_history={$import.id_import_history|intval}"><i class="icon icon-cloud-download"> </i> {l s='Download new employee passwords' mod='ets_woo2pres'}</a>
                            {/if}
                        </td>
                    </tr>
                {/foreach}
            </table>
        {else}
            <div class="alert alert-warning no-have-history">{l s='Migration history is empty' mod='ets_woo2pres'}</div>
        {/if}
    </div>
</div>
<div class="dtm-clearfix"></div>