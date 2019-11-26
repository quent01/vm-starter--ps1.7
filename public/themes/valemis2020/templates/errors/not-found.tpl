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
<section id="content" class="page-content page-not-found">
  {block name='page_content'}
    {* <h4>{l s='Sorry for the inconvenience.' d='Shop.Theme.Global'}</h4> *}
    <div class="text-center">
      <p>
        {* {l s='Search again what you are looking for' d='Shop.Theme.Global'} *}
        Il semblerait que la page à laquelle vous souhaitez accéder n'existe pas. <br>
        Mais pas de panique, notre boutique elle, est toujours accessible !
      </p>
      <div>
        <a href="" class="btn btn-primary">Retourner à la boutique</a>
      </div>
    </div>

    {* {block name='search'}
      {hook h='displaySearch'}
    {/block} *}

    {block name='hook_not_found'}
      {hook h='displayNotFound'}
    {/block}

  {/block}
</section>
