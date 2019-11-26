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
    <label class="control-label col-lg-4" for="source_type">{l s='Source type' mod='ets_woo2pres'}</label>
    <div class="col-lg-8">
        <select id="source_type" name="source_type">
            <option value="url_site">{l s='Wordpress site URL' mod='ets_woo2pres'}</option>
            <option value="upload_file">{l s='Upload data file from computer' mod='ets_woo2pres'}</option>
            <option value="link">{l s='Upload data file from URL' mod='ets_woo2pres'}</option>
        </select>
    </div>
</div>
<div class="form-group source upload">
    <label class="control-label col-lg-4" for="file_import">{l s='Select data file' mod='ets_woo2pres'}<span class="required">*</span></label>
    <div class="col-lg-8">
        <div class="data_upload_button_wrap">
            <input type="file" name="file_import" id="file_import"/>
            <div class="data_upload_button">
                <input readonly="true" type="text" name="data_upload_button" id="data_upload_button_input" value="{l s='No file selected.' mod='ets_woo2pres'}" />
                <div class="data_upload_button_right">
                    <i class="icon icon-file"></i> {l s='Choose file' mod='ets_woo2pres'}
                </div>
            </div>
        </div>
    </div>
</div>
<div class="form-group source link">
    <label class="control-label col-lg-4" for="link_file">{l s='Enter data file URL' mod='ets_woo2pres'}<span class="required">*</span></label>
    <div class="col-lg-8">
        <input type="text" name="link_file" id="link_file" placeholder="{l s='Data file URL' mod='ets_woo2pres'}" />
    </div>
</div>
<div class="form-group source url_site">
    <label class="control-label col-lg-4" for="link_site">{l s='Enter site URL' mod='ets_woo2pres'}<span class="required">*</span></label>
    <div class="col-lg-8">
        <input type="text" name="link_site" id="link_site" placeholder="{l s='Enter site URL' mod='ets_woo2pres'}" />
    </div>
</div>
<div class="form-group source url_site">
    <label class="control-label col-lg-4" for="secure_access_tocken">{l s='Secure access token' mod='ets_woo2pres'}<span class="required">*</span></label>
    <div class="col-lg-8">
        <input type="text" name="secure_access_tocken" id="secure_access_tocken" placeholder="{l s='Secure access tocken' mod='ets_woo2pres'}" />
    </div>
</div>
<p class="link_download_plugin">{l s='Before getting started with the migration. Please ' mod='ets_woo2pres'}<strong><a href="{$mod_dr|escape:'html':'UTF-8'}plugins/woo2presconnector.zip" target="_blank">{l s='Download Woo2Press Connector plugin' mod='ets_woo2pres'}</a></strong>&nbsp;{l s=' and install the plugin on Wordpress website (source website). This Wordpress plugin gives you the "Secure access token" (and data file) that is required above' mod='ets_woo2pres'}</p>