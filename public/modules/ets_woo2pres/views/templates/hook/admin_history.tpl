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
<div class="dtm-right-block">
    <div class="dtm_history_tab_header">
    <div id="import_history" class="tab_content import{if $tab_history=='import'} active{/if}">
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
                            {if $woo2press_import_last==$import.id_import_history && $import.ok_import}
                                <a href="index.php?tab=AdminModules&configure=ets_woo2pres&token={$token|escape:'html':'UTF-8'}&tab_module=front_office_features&module_name=ets_woo2pres&tabmodule=import&resumeImport&id_import_history={$import.id_import_history|intval}"><i class="fa fa-undo"> </i> {l s='Resume migration' mod='ets_woo2pres'}</a><br />
                            {/if}
                                <a href="index.php?tab=AdminModules&configure=ets_woo2pres&token={$token|escape:'html':'UTF-8'}&tab_module=front_office_features&module_name=ets_woo2pres&tabmodule=import&restartImport&id_import_history={$import.id_import_history|intval}"><i class="fa fa-refresh"> </i> {l s='Restart migration' mod='ets_woo2pres'}</a><br />
                                <a href="index.php?tab=AdminModules&configure=ets_woo2pres&token={$token|escape:'html':'UTF-8'}&tab_module=front_office_features&module_name=ets_woo2pres&tabmodule=history&deleteimporthistory&id_import_history={$import.id_import_history|intval}"><i class="fa fa-trash"> </i> {l s='Delete' mod='ets_woo2pres'}</a><br />
                            {if $import.new_passwd_customer}
                                <a href="index.php?tab=AdminModules&configure=ets_woo2pres&token={$token|escape:'html':'UTF-8'}&tab_module=front_office_features&module_name=ets_woo2pres&tabmodule=history&downloadpasscustomer&id_import_history={$import.id_import_history|intval}"><i class="fa fa-cloud-download"> </i> {l s='Download new password customer' mod='ets_woo2pres'}</a><br />
                            {/if}
                            {if $import.new_passwd_employee}
                                <a href="index.php?tab=AdminModules&configure=ets_woo2pres&token={$token|escape:'html':'UTF-8'}&tab_module=front_office_features&module_name=ets_woo2pres&tabmodule=history&downloadpassemployee&id_import_history={$import.id_import_history|intval}"><i class="fa fa-cloud-download"> </i> {l s='Download new password employee' mod='ets_woo2pres'}</a><br />
                            {/if}
                        </td>
                    </tr>
                {/foreach}
            </table>
        {else}
            <div class="no-have-history">{l s='Migration history is empty' mod='ets_woo2pres'}</div>
        {/if}
    </div>
</div>
<div class="dtm-clearfix"></div>