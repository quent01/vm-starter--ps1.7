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
{assign var="productscount" value=$products|count}
<div class="container">
  <section class="section section--pb0 ps-featuredproducts featured-products clearfix">
    <h2 class="h1 text-center">
      {l s='Sélection de produit' d='Shop.Theme.Catalog'}
    </h2>
    <div class="text-center">
      <p class="">
        Retrouvez tous nos produits apportant bien-être et confort à votre circulation sanguine
      </p>
    </div>
    <div class="products products-slick spacing-md-top{if $productscount > 1} products--slickmobile{/if}" data-slick='{strip}
      {ldelim}
      "slidesToShow": 1,
      "slidesToScroll": 1,
      "mobileFirst":true,
      "arrows":true,
      "rows":0,
      "responsive": [
        {ldelim}
          "breakpoint": 992,
          "settings":
          {if $productscount > 4}
          {ldelim}
          "arrows":true,
          "slidesToShow": 3,
          "slidesToScroll": 3,
          "arrows":true
          {rdelim}
          {else}
          "unslick"
          {/if}
        {rdelim},
        {ldelim}
          "breakpoint": 720,
          "settings":
          {if $productscount > 3}
          {ldelim}
          "arrows":true,
          "slidesToShow": 3,
          "slidesToScroll": 3
          {rdelim}
          {else}
          "unslick"
          {/if}
        {rdelim},
        {ldelim}
          "breakpoint": 540,
          "settings":
          {if $productscount > 2}
          {ldelim}
          "arrows":true,
          "slidesToShow": 2,
          "slidesToScroll": 2
          {rdelim}
          {else}
          "unslick"
          {/if}
        {rdelim}
      ]{rdelim}{/strip}'>
      {foreach from=$products item="product"}
        {include file="catalog/_partials/miniatures/product.tpl" product=$product}
      {/foreach}
    </div>

    {* <div class="text-center">
      <a class="all-product-link btn btn-primary" href="{$allProductsLink}">
        <span>{l s='All products' d='Shop.Theme.Catalog'}</span>
        <i class="material-icons">&#xE315;</i>
      </a>
    </div>   *}
  </section>
</div>
