<?php
/**
 * NOTICE OF LICENSE
 *
 * @author    202 ecommerce <tech@202-ecommerce.com>
 * @author    Mondial Relay
 * @copyright Copyright (c) Mondial Relay
 * @license http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

require_once _PS_MODULE_DIR_ . '/mondialrelay/controllers/admin/AdminMondialrelayController.php';
require_once _PS_MODULE_DIR_ . '/mondialrelay/classes/services/MondialrelayService.php';

class AdminMondialrelayAccountSettingsController extends AdminMondialrelayController
{
    public $table = null;

    /**
     * @see AdminController::init()
     */
    public function init()
    {
        $this->initOptions();
        parent::init();
    }

    /**
     * @see AdminController::setMedia()
     */
    public function setMedia($isNewTheme = false)
    {
        parent::setMedia($isNewTheme);

        $this->addJS($this->module->getPathUri() . '/views/js/admin/account-settings.js');

        Media::addJsDef(array(
            'MONDIALRELAY_ACCOUNTSETTINGS' => array(
                'accountSettingsUrl' => $this->context->link->getAdminLink('AdminMondialrelayAccountSettings'),
                'checkConnectionFields' => array(
                    Mondialrelay::WEBSERVICE_ENSEIGNE,
                    Mondialrelay::WEBSERVICE_BRAND_CODE,
                    Mondialrelay::WEBSERVICE_KEY,
                )
            )
        ));
    }

    /**
     * Sets the fields for an "options" form
     */
    public function initOptions()
    {
        $this->fields_options = array(
            array(
                'title' => $this->module->l('Authentication', 'AdminMondialrelayAccountSettingsController'),
                'icon' => 'icon-cog',
                'description' => sprintf(
                    $this->module->l('Your account parameters are provided by Mondial Relay when you have a professional acount. For more information click the [a] "Help" [/a] button. Once your credentials have been entered, you have to setup the [a]"advanced parameters"[/a].', 'AdminMondialrelayAccountSettingsController', array('href' => $this->context->link->getAdminLink('AdminMondialrelayHelp'),
                        'href_1' => $this->context->link->getAdminLink('AdminMondialrelayAdvancedSettings'),
                        'target' => 'blank',
                        'target_1' => 'blank')),
                    '<a>',
                    '</a>'
                ),
                'fields' => array(
                    Mondialrelay::WEBSERVICE_ENSEIGNE => array(
                        'type' => 'text',
                        'title' => $this->module->l('Enseigne (brand)', 'AdminMondialrelayAccountSettingsController'),
                        'required' => true,
                    ),
                    Mondialrelay::WEBSERVICE_BRAND_CODE => array(
                        'type' => 'text',
                        'title' => $this->module->l('Brand Code', 'AdminMondialrelayAccountSettingsController'),
                        'required' => true
                    ),
                    Mondialrelay::WEBSERVICE_KEY => array(
                        'type' => 'text',
                        'title' => $this->module->l('Private Key', 'AdminMondialrelayAccountSettingsController'),
                        'required' => true
                    ),
                    Mondialrelay::LABEL_LANG => array(
                        'type' => 'select',
                        'title' => $this->module->l('Labels language', 'AdminMondialrelayAccountSettingsController'),
                        'required' => true,
                        'list' => array(
                            array('iso_code' => 'FR', 'name' => $this->l('French', 'AdminMondialrelayAccountSettingsController')),
                            array('iso_code' => 'ES', 'name' => $this->l('Spanish', 'AdminMondialrelayAccountSettingsController')),
                            array('iso_code' => 'NL', 'name' => $this->l('Dutch', 'AdminMondialrelayAccountSettingsController')),
                        ),
                        'identifier' => 'iso_code',
                        'defaultValue' => $this->context->language->id
                    ),
                    Mondialrelay::WEIGHT_COEFF => array(
                        'type' => 'select',
                        'title' => $this->module->l('Unit of Weight', 'AdminMondialrelayAccountSettingsController'),
                        'hint' => $this->module->l('Please choose the unit type of weight measurement using for products in your shop.', 'AdminMondialrelayAccountSettingsController'),
                        'required' => true,
                        'list' => array(
                            array('value' => 1, 'name' => $this->module->l('Grams', 'AdminMondialrelayAccountSettingsController')),
                            array('value' => 1000, 'name' => $this->module->l('Kilograms', 'AdminMondialrelayAccountSettingsController')),
                        ),
                        'identifier' => 'value',
                    ),
                    'checkConnection' => array(
                        'type' => 'button',
                        'title' => '',
                        'auto_value' => true, // Prevent PS from getting the value in Configuration
                        'defaultValue' => $this->module->l('Check connection', 'AdminMondialrelayAccountSettingsController'),
                        'id' => 'mondialrelay_check-connection',
                        'class' => 'btn-primary',
                        'no_multishop_checkbox' => true,
                    ),
                ),
                'submit' => array(
                    'title' => $this->module->l('Save', 'AdminMondialrelayAccountSettingsController'),
                )
            )
        );
    }

    /**
     * For some reason, fields are never considered as required when in
     * "All shops" context; PS wants the "multishopOverrideOption" checked for
     * each field to consider them as "required", but the checkboxes never
     * appear when on "All shops". So we'll validate ourselves...
     *
     * @see AdminController::processUpdateOptions
     */
    public function beforeUpdateOptions()
    {
        // Most of the code here is from AdminController::processUpdateOptions
        $languages = Language::getLanguages(false);
        $hide_multishop_checkbox = (Shop::getTotalShops(false, null) < 2) ? true : false;

        foreach ($this->fields_options as $category_data) {
            if (!isset($category_data['fields'])) {
                continue;
            }

            $fields = $category_data['fields'];

            // Validate fields
            foreach ($fields as $field_name => $values) {
                // We don't validate fields with no visibility
                if (!$hide_multishop_checkbox && Shop::isFeatureActive() && isset($values['visibility']) && $values['visibility'] > Shop::getContext()) {
                    continue;
                }

                // Same test as original method, but don't check Shop context
                if (isset($values['required']) && $values['required']) {
                    if (isset($values['type']) && $values['type'] == 'textLang') {
                        foreach ($languages as $language) {
                            if (($value = Tools::getValue($field_name . '_' . $language['id_lang'])) == false && (string) $value != '0') {
                                $this->errors[] = $this->module->l('Field %field% is required.', 'AdminMondialrelayAccountSettingsController', array('%field%' => $values['title']));
                                continue;
                            }
                        }
                    } elseif (($value = Tools::getValue($field_name)) == false && (string) $value != '0') {
                        $this->errors[] = $this->module->l('Field %field% is required.', 'AdminMondialrelayAccountSettingsController', array('%field%' => $values['title']));
                        continue;
                    }
                }

                if ($field_name == Mondialrelay::WEBSERVICE_BRAND_CODE && !MondialrelayTools::validateBrandCode(Tools::getValue($field_name))) {
                    $fieldLabel = $this->fields_options[0]['fields'][Mondialrelay::WEBSERVICE_BRAND_CODE]['title'];
                    $this->errors[] = $this->module->l('Field %field% is invalid.', 'AdminMondialrelayAccountSettingsController', array('%field%' => $fieldLabel));
                }
            }
        }

        if (empty($this->errors)) {
            $errors = array();
            MondialrelayTools::checkWebserviceConnection(
                Tools::getValue(Mondialrelay::WEBSERVICE_ENSEIGNE),
                Tools::getValue(Mondialrelay::WEBSERVICE_KEY),
                $errors
            );

            $errorFormat = $this->module->l('Connection unavailable : %s', 'AdminMondialrelayAccountSettingsController');
            foreach ($errors as $error) {
                $this->errors[] = sprintf(
                    $errorFormat,
                    $error
                );
            }
        }

        // This is the only way we have of evading a double validation
        // Throwing an exception without a message will add an empty error
        // message, and PS doesn't care if we add errors in this function
        if (!empty($this->errors)) {
            throw new PrestaShopException(array_pop($this->errors));
        }
    }

    /**
     * Ajax call to check if the parameters are valid
     */
    public function ajaxProcessCheckConnection()
    {
        $this->json = true;

        if (($value = Tools::getValue(Mondialrelay::WEBSERVICE_ENSEIGNE)) == false && (string) $value != '0') {
            $fieldName = $this->fields_options[0]['fields'][Mondialrelay::WEBSERVICE_ENSEIGNE]['title'];
            $this->jsonError($this->module->l('Field %field% is required.', 'AdminMondialrelayAccountSettingsController', array('%field%' => $fieldName)));
        }

        if (($value = Tools::getValue(Mondialrelay::WEBSERVICE_KEY)) == false && (string) $value != '0') {
            $fieldName = $this->fields_options[0]['fields'][Mondialrelay::WEBSERVICE_KEY]['title'];
            $this->jsonError($this->module->l('Field %field% is required.', 'AdminMondialrelayAccountSettingsController', array('%field%' => $fieldName)));
        }

        if (($value = Tools::getValue(Mondialrelay::WEBSERVICE_BRAND_CODE)) == false && (string) $value != '0') {
            $fieldName = $this->fields_options[0]['fields'][Mondialrelay::WEBSERVICE_BRAND_CODE]['title'];
            $this->jsonError($this->module->l('Field %field% is required.', 'AdminMondialrelayAccountSettingsController', array('%field%' => $fieldName)));
        } elseif (!MondialrelayTools::validateBrandCode(Tools::getValue(Mondialrelay::WEBSERVICE_BRAND_CODE))) {
            $fieldName = $this->fields_options[0]['fields'][Mondialrelay::WEBSERVICE_BRAND_CODE]['title'];
            $this->jsonError($this->module->l('Field %field% is invalid.', 'AdminMondialrelayAccountSettingsController', array('%field%' => $fieldName)));
        }

        if (!empty($this->errors)) {
            $this->status = 'error';
            return false;
        }

        $errors = array();
        if (MondialrelayTools::checkWebserviceConnection(
            Tools::getValue(Mondialrelay::WEBSERVICE_ENSEIGNE),
            Tools::getValue(Mondialrelay::WEBSERVICE_KEY),
            $errors
        )) {
            $this->jsonConfirmation($this->module->l('Connection successful !', 'AdminMondialrelayAccountSettingsController'));
        } else {
            $errorFormat = $this->module->l('Connection unavailable : %s', 'AdminMondialrelayAccountSettingsController');
            foreach ($errors as $error) {
                $this->jsonError(sprintf(
                    $errorFormat,
                    $error
                ));
            }
            $this->status = 'error';
        }
    }
}
