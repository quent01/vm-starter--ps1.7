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
    <div class="panel data_tab_help">
        <div class="panel-heading"><i class="icon-import"></i>{l s='Help' mod='ets_woo2pres'}</div>            
        <h4>{l s='STEPS TO MIGRATE FROM WORDPRESS TO PRESTASHOP' mod='ets_woo2pres'}</h4>
        <ul>
            <li>{l s='1. Install "Woo2press connector" plugin on Wordpress website (source website). Download Woo2press connector plugin' mod='ets_woo2pres'} <a href="{$plugin_connector|escape:'html'}">{l s='here' mod='ets_woo2pres'}</a></li>
            <li>{l s='2. Install a fresh Prestashop website (target website)' mod='ets_woo2pres'}</li>
            <li>{l s='3. Install "Woocomerce to Prestashop " on target website' mod='ets_woo2pres'}</li>
            <li>{l s='4. Enter "Secure access token" (or import data file) that is available on "Woo2press connector" plugin' mod='ets_woo2pres'}</li>
            <li>{l s='5. Final tweaks (Clear cache, Regenerate friendly URL, reindex data, recover passwords). Download Woo2press Password Keeper' mod='ets_woo2pres'} <a href="{$plugin_password|escape:'html'}">{l s='here' mod='ets_woo2pres'}</a></li>
        </ul>
    </div>
</div>
<div class="dtm-clearfix"></div>