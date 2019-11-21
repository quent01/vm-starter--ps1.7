{*
 * NOTICE OF LICENSE
 *
 * @author    202 ecommerce <tech@202-ecommerce.com>
 * @author    Mondial Relay
 * @copyright Copyright (c) Mondial Relay
 * @license http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 *}
{extends file=$original_template}

{block name="label"}
  {if isset($field['title']) && isset($field['hint'])}
    <label class="control-label col-lg-3{if isset($field['required']) && $field['required'] && $field['type'] != 'radio'} required{/if}">
      {if !$categoryData['hide_multishop_checkbox'] && $field['multishop_default'] && empty($field['no_multishop_checkbox'])}
      <input type="checkbox" name="multishopOverrideOption[{$key|escape:'htmlall':'UTF-8'}]" value="1"{if !$field['is_disabled']} checked="checked"{/if} onclick="toggleMultishopDefaultValue(this, '{$key|escape:'htmlall':'UTF-8'}')"/>
      {/if}
      <span title="" data-toggle="tooltip" class="label-tooltip" data-original-title="
        {if is_array($field['hint'])}
          {foreach $field['hint'] as $hint}
            {if is_array($hint)}
              {$hint.text|escape:'html':'UTF-8'}
            {else}
              {$hint|escape:'html':'UTF-8'}
            {/if}
          {/foreach}
        {else}
          {$field['hint']|escape:'htmlall':'UTF-8'}
        {/if}
      " data-html="true">
        {$field['title']|escape:'htmlall':'UTF-8'}
      </span>
    </label>
  {elseif isset($field['title'])}
    {* We can't add a "required" class on the "label" tag, so we extended the template *}
    <label class="control-label col-lg-3{if isset($field['required']) && $field['required'] && $field['type'] != 'radio'} required{/if}">
      {if !$categoryData['hide_multishop_checkbox'] && $field['multishop_default'] && empty($field['no_multishop_checkbox'])}
      <input type="checkbox" name="multishopOverrideOption[{$key|escape:'htmlall':'UTF-8'}]" value="1"{if !$field['is_disabled']} checked="checked"{/if} onclick="checkMultishopDefaultValue(this, '{$key|escape:'htmlall':'UTF-8'}')" />
      {/if}
      {$field['title']|escape:'htmlall':'UTF-8'}
    </label>
  {/if}
{/block}

{block name="input" prepend}
  {if $field['type'] == 'button'}
    <div class="col-lg-9">
      <button class="btn {if isset($field['class'])}{$field['class']|escape:'htmlall':'UTF-8'}{/if}" type="button"{if isset($field['id'])} id="{$field['id']|escape:'htmlall':'UTF-8'}"{/if} name="{$key|escape:'htmlall':'UTF-8'}">
        {if isset($field['value'])}
          {$field['value']|escape:'htmlall':'UTF-8'}
        {/if}
      </button>
    </div>
  {/if}
{/block}