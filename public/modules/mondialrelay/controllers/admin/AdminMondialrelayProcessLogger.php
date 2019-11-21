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

require_once _PS_MODULE_DIR_ . '/mondialrelay/mondialrelay.php';

use MondialrelayClasslib\Extensions\ProcessLogger\Controllers\Admin\AdminProcessLoggerController;

class AdminMondialrelayProcessLoggerController extends AdminProcessLoggerController
{
    /**
     * @see parent::init()
     */
    public function init()
    {
        parent::init();

        $this->context->smarty->assign(array(
            'module_path' => $this->module->getPathUri(),
        ));
    }

    /**
     * @see AdminController::setMedia()
     */
    public function setMedia($isNewTheme = false)
    {
        if (version_compare(_PS_VERSION_, '1.7', '<')) {
            parent::setMedia();
        } else {
            parent::setMedia($isNewTheme);
        }
        
        $this->addCSS($this->module->getPathUri() . '/views/css/admin/global.css');
    }
}
