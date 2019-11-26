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
<ul>
    <li{if $controller=='AdminWoo2PressImport'} class="active"{/if}><a href="{$link->getAdminLink('AdminWoo2PressImport',true)|escape:'html':'UTF-8'}"><i class="icon icon-cloud-upload"> </i> {l s='Migration' mod='ets_woo2pres'}</a></li>
    <li{if $controller=='AdminWoo2PressHistory'} class="active"{/if}><a href="{$link->getAdminLink('AdminWoo2PressHistory',true)|escape:'html':'UTF-8'}"><i class="icon icon-history"> </i> {l s='History' mod='ets_woo2pres'}</a></li>
    <li{if $controller=='AdminWoo2PressClean'} class="active"{/if}><a href="{$link->getAdminLink('AdminWoo2PressClean',true)|escape:'html':'UTF-8'}"><i class="icon icon-eraser"> </i> {l s='Clean-up' mod='ets_woo2pres'}</a></li>
    <li{if $controller=='AdminWoo2PressHelp'} class="active"{/if}><a href="{$link->getAdminLink('AdminWoo2PressHelp',true)|escape:'html':'UTF-8'}"><i class="icon icon-question-circle"> </i> {l s='Help' mod='ets_woo2pres'}</a></li>
</ul>