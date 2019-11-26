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
<div class="popup_uploading_table">
        <div class="popup_uploading_tablecell">
            <div class="popup_uploading_content">
                <div class="import-wrap-title">
                    <h4 class="import-title">
                        {l s='Migrating Data' mod='ets_woo2pres'}
                    </h4>
                    <div id="basicUsageClock"></div>
                    <div class="clearfix"></div>
                </div>
                <div id="data-importing-content" class="data-importing-content">
                    <ul class="list-data-to-importing">
                        {if $ets_woo2press_import}
                            <li class="minor_data process"><span>{l s='Migrate minor data' mod='ets_woo2pres'}</span> <span class="process_import">({l s='Processing...' mod='ets_woo2pres'})</span></li>
                            {foreach from=$ets_woo2press_import item='data_import'}
                                {if isset($assign[$data_import]) && $assign[$data_import]}
                                    <li class="{$data_import|escape:'html':'UTF-8'}">
                                        <span>{l s='Migrate' mod='ets_woo2pres'} {$assign[$data_import]|escape:'html':'UTF-8'} {if $data_import=='cms'}{l s='WP posts (to CMS)' mod='ets_woo2pres'}{else}{if $data_import=='page_cms'}{l s='WP pages (to CMS)' mod='ets_woo2pres'}{else}{if $data_import=='cms_cateogories'}{l s='CMS categories' mod='ets_woo2pres'}{else}{str_replace('_',' ',$data_import)|escape:'html':'UTF-8'}{/if}{/if}{/if}</span>
                                        <span class="process_import">({l s='Processing...' mod='ets_woo2pres'})</span>
                                        <span class="wait_import">({l s='Waiting' mod='ets_woo2pres'})</span>
                                    </li>
                                {/if}
                            {/foreach}
                        {/if}
                    </ul>
                </div>
                <div class="import-wapper-block-3">
                    <div class="import-wapper-all">
                        <div class="import-wapper-percent" style="width:0%;"></div>
                        <div class="noTrespassingOuterBarG">
                        	<div class="noTrespassingAnimationG">
                        		<div class="noTrespassingBarLineG"></div>
                        		<div class="noTrespassingBarLineG"></div>
                        		<div class="noTrespassingBarLineG"></div>
                        		<div class="noTrespassingBarLineG"></div>
                        		<div class="noTrespassingBarLineG"></div>
                        		<div class="noTrespassingBarLineG"></div>
                                <div class="noTrespassingBarLineG"></div>
                                <div class="noTrespassingBarLineG"></div>
                        		<div class="noTrespassingBarLineG"></div>
                        		<div class="noTrespassingBarLineG"></div>
                        		<div class="noTrespassingBarLineG"></div>
                        		<div class="noTrespassingBarLineG"></div>
                                <div class="noTrespassingBarLineG"></div>
                        		<div class="noTrespassingBarLineG"></div>
                                <div class="noTrespassingBarLineG"></div>
                        		<div class="noTrespassingBarLineG"></div>
                        		<div class="noTrespassingBarLineG"></div>
                        		<div class="noTrespassingBarLineG"></div>
                                <div class="noTrespassingBarLineG"></div>
                        		<div class="noTrespassingBarLineG"></div>
                        	</div>
                        </div>
                        <span class="running">0%</span>
                    </div>
                    <samp class="percentage_import"></samp>
                    <div class="alert alert-warning import-alert">
                            {l s='We are processing the migration, please be patient and wait! Do not close web browser! This process can take some minutes (even some hours) depends on your server speed and your data size. You may want to take a cup of coffee while waiting if it is too long' mod='ets_woo2pres'}
                    </div>

                </div>
                <div class="clearfix"></div>
            </div>
        </div>
</div>