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
<div {if $submit_clear_history}style="display:block;"{else}style="display:none;"{/if} class="block_sessucfull"> 
    {if !$message_error}
    <div class="bootstrap">
		<div class="alert alert-success">
			<button type="button" class="close" data-dismiss="alert">×</button>
            {l s='Migration history cleared successfully' mod='ets_woo2pres'}
		</div>
	</div>
    {else}
        <ul>
            {$message_error nofilter}
        </ul>
    {/if}
</div>
<div class="dtm-left-block">
    {hook h='woo2pressLeftBlock'}
</div>
<div class="dtm-right-block">
    <form id="module_form" action="{$link->getAdminLink('AdminWoo2PressClean')|escape:'html':'UTF-8'}" class="defaultForm form-horizontal" novalidate="" enctype="multipart/form-data" method="post" >
        <div class="panel data_tab_clearup">
            <div class="panel-heading"><i class="icon-import"></i>{l s='Clean Up' mod='ets_woo2pres'}</div>
            <div id="block_form_clear_message">
                <div class="form-group">
                    <label for="title_message" class="control-label col-sm-3">{l s='Time range to clear' mod='ets_woo2pres'}</label>
                    <div class="col-sm-9">
                        <select name="ETS_DATAMATER_CLEAR" class="fixed-width-xl" id="ETS_DATAMATER_CLEAR">
                            <option value="last_hour">{l s='Last hour' mod='ets_woo2pres'}</option>
                            <option value="last_tow_hours">{l s='Last two hours' mod='ets_woo2pres'}</option>
                            <option value="last_four_hours">{l s='Last four hours' mod='ets_woo2pres'}</option>
                            <option selected="selected" value="today">{l s='Today' mod='ets_woo2pres'}</option>
                            <option value="1_week">{l s='1 week' mod='ets_woo2pres'}</option>
                            <option value="1_month_ago">{l s='1 month ago' mod='ets_woo2pres'}</option>
                            <option value="1_year_ago">{l s='1 year ago' mod='ets_woo2pres'}</option>
                            <option value="everything">{l s='Everything' mod='ets_woo2pres'}</option>
                        </select>
                    </div>
                    <p class="help-block">{l s='Clear migration history to save your hosting disk space.' mod='ets_woo2pres'}</p>
                </div>
                <div class="form-group">
                    <div class="col-sm-3"></div>
                    <div class="col-sm-9" style="text-align: left;">
                        <button class="btn btn-default" type="submit" id="submit_clear_history" name="submit_clear_history" onclick="return confirm('Do you want to delete data?');">{l s='Clear' mod='ets_woo2pres'}</button>
                    </div>
                </div>
                <div class="clearfix"></div>
            </div>
        </div>
    </form>
</div>