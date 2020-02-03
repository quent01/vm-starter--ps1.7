{**
 * 2007-2017 PrestaShop
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License 3.0 (AFL-3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * https://opensource.org/licenses/AFL-3.0
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@prestashop.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade PrestaShop to newer
 * versions in the future. If you wish to customize PrestaShop for your
 * needs please refer to http://www.prestashop.com for more information.
 *
 * @author    PrestaShop SA <contact@prestashop.com>
 * @copyright 2007-2017 PrestaShop SA
 * @license   https://opensource.org/licenses/AFL-3.0 Academic Free License 3.0 (AFL-3.0)
 * International Registered Trademark & Property of PrestaShop SA
 *}
{block name='hook_footer_before'}
  {hook h='displayFooterBefore'}
{/block} 
<div class="container-fluid l-footer__top">
  <div class="row">
    <div class="bg bg--primary-darker l-footer__top__col col-xs-12 col-sm-12 col-md-6 col-lg-6">
        <div class="ps-emailsubscription block_newsletter">
          <div class="text-center">
            <h2 id="block-newsletter-label" class="h0 fw-normal ps-emailsubscription__title">
              <strong>Suivez-nous</strong> avec notre newsletter
            </h2>
            <button class="btn btn-primary" type="button" data-toggle="modal" data-target="#modalEmailsubscription">S'inscrire à la newsletter</button>
          </div>
        </div>
    </div>
    <div class="bg bg--white l-footer__top__col col-xs-12 col-sm-12 col-md-6 col-lg-6">
      <div class="text-center">
        <h2 class="h0 fw-normal">
          <strong>Une question ?</strong> <br>Contactez nous
        </h2>
        <a href="#" class="btn btn-primary">Nous contacter</a>
      </div>
    </div>
  </div>
</div> 
<div class="container l-footer__middle">
  <div class="row">
    <div class="container-fluid">
      <a href="{$urls.base_url}">
        <img class="logo img-fluid" src="{$shop.logo}" alt="{$shop.name}">
      </a>
    </div>
  </div>
  <div class="row footer-container">
    <div class="container">
      <div class="row">
        {block name='hook_footer'}
          {hook h='displayFooter'}
        {/block}
      </div>
      <div class="row">
        {block name='hook_footer_after'}
          {hook h='displayFooterAfter'}
        {/block}
      </div>
    </div>
  </div>
</div>


{literal}
  <style>
    .custom-file-label::after{
      content:"{/literal}{l s='Choose file' d='Shop.Theme.Actions'}"{literal}
    }
  </style>
{/literal}
