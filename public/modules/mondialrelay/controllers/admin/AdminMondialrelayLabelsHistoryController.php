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
require_once _PS_MODULE_DIR_ . '/mondialrelay/mondialrelay.php';
require_once _PS_MODULE_DIR_ . '/mondialrelay/classes/MondialrelayTools.php';

class AdminMondialrelayLabelsHistoryController extends AdminMondialrelayController
{
    /**
     * @inheritdoc
     */
    protected $with_mondialrelay_header = false;

    /**
     * @inheritdoc
     * We don't want a link on whole lines
     */
    protected $list_no_link = true;

    public function __construct()
    {
        $this->table = MondialrelaySelectedRelay::$definition['table'];

        parent::__construct();

        $this->initList();
    }

    public function initProcess()
    {
        parent::initProcess();
        if (Tools::isSubmit('deleteFromHistory')) {
            $this->action = 'deleteFromHistory';
        }
    }

    public function initContent()
    {
        $this->informations[] = $this->module->l('You can print several labels in a row or delete labels from the history. To do so, use the "group action" command.', 'AdminMondialrelayLabelsHistoryController');

        return parent::initContent();
    }

    public function initList()
    {
        $this->explicitSelect = true;

        $this->fields_list = array(
            'id_order' => array(
                'title' => $this->module->l('Order ID', 'AdminMondialrelayLabelsHistoryController'),
                'align' => 'center',
                'class' => 'fixed-width-xs',
                'filter_key' => 'a!id_order'
            ),
            'expedition_num' => array(
                'title' => $this->module->l('Exp number', 'AdminMondialrelayLabelsHistoryController'),
            ),
            'date_add' => array(
                'title' => $this->module->l('Date', 'AdminMondialrelayLabelsHistoryController'),
                'filter_key' => 'a!date_label_generation',
                'type' => 'date',
            ),
            'label_a4' => array(
                'title' => $this->module->l('Print stick A4', 'AdminMondialrelayLabelsHistoryController'),
                'align' => 'center',
                'filter_key' => 'a!label_url',
                'callback' => 'displayLabelA4Url',
                'search' => false,
                'class' => 'mondialrelay_label-url-container',
            ),
            'label_a5' => array(
                'title' => $this->module->l('Print stick A5', 'AdminMondialrelayLabelsHistoryController'),
                'align' => 'center',
                'filter_key' => 'a!label_url',
                'callback' => 'displayLabelA5Url',
                'search' => false,
                'class' => 'mondialrelay_label-url-container',
            ),
            'label_10x15' => array(
                'title' => $this->module->l('Print stick 10x15', 'AdminMondialrelayLabelsHistoryController'),
                'align' => 'center',
                'filter_key' => 'a!label_url',
                'callback' => 'displayLabel10x15Url',
                'search' => false,
                'class' => 'mondialrelay_label-url-container',
            ),
        );

        // Build the query
        $this->_select .= 'a.' . MondialrelaySelectedRelay::$definition['primary'];
        $this->_join .= 'INNER JOIN `'._DB_PREFIX_.Order::$definition['table'].'` o ON o.id_order = a.id_order ';
        $this->_where .= 'AND a.expedition_num IS NOT NULL '
            . 'AND a.expedition_num <> "" '
            . 'AND a.hide_history = 0 '
            . 'AND o.id_shop IN (' . implode(', ', Shop::getContextListShopID()) . ') ';
        $this->_orderBy = 'a.date_add';
        $this->_orderWay = 'DESC';

        // Per-row actions
        $this->actions_available = array('deleteFromHistory');
        $this->actions = array('deleteFromHistory');

        // Bulk actions
        $this->bulk_actions = array(
            'printSelectionLabelsA4' => array(
                'text' => $this->module->l('Print selected stick A4', 'AdminMondialrelayLabelsHistoryController'),
                'icon' => 'icon-print'
            ),
            'printSelectionLabelsA5' => array(
                'text' => $this->module->l('Print selected stick A5', 'AdminMondialrelayLabelsHistoryController'),
                'icon' => 'icon-print'
            ),
            'printSelectionLabels10x15' => array(
                'text' => $this->module->l('Print selected stick 10x15', 'AdminMondialrelayLabelsHistoryController'),
                'icon' => 'icon-print'
            ),
            'divider' => array(
                'text' => 'divider'
            ),
            'hideSelectionFromHistory' => array(
                'text' => $this->module->l('Delete selected history', 'AdminMondialrelayLabelsHistoryController'),
                'icon' => 'icon-remove'
            ),
        );
    }

    public function displayLabelA4Url($url, $data)
    {
        return $this->displayLabelUrl($url, 'A4');
    }

    public function displayLabelA5Url($url, $data)
    {
        return $this->displayLabelUrl($url, 'A5');
    }

    public function displayLabel10x15Url($url, $data)
    {
        return $this->displayLabelUrl($url, '10x15');
    }

    public function displayLabelUrl($url, $format)
    {
        return $this->createTemplate('displayLabelUrl.tpl')
            ->assign(array(
                'label_url' => preg_replace('#format=.*?(&|$)#', 'format=' . $format . '$1', $url),
            ))->fetch();
    }
    /**
     * Display "delete" action link
     * @see HelperList::displayListContent
     */
    public function displayDeleteFromHistoryLink($token, $id, $name = null)
    {
        $tpl = $this->helper->createTemplate('list_action_delete_history.tpl');
        
        $tpl->assign(array(
            'href' => $this->context->link->getAdminLink('AdminMondialrelayLabelsHistory')
                    . '&deleteFromHistory&' . MondialrelaySelectedRelay::$definition['primary'] . '=' . $id,
        ));
        return $tpl->fetch();
    }

    /**
     * @inheritdoc
     */
    public function setHelperDisplay(Helper $helper)
    {
        parent::setHelperDisplay($helper);
        switch (get_class($this->helper)) {
            case 'HelperList':
                $this->tpl_list_vars['original_content'] = $this->helper->base_folder . 'list_content.tpl';
                $this->tpl_list_vars['view_order_url'] = $this->context->link->getAdminLink('AdminOrders')
                    . '&vieworder&id_order=';
                break;
        }
    }

    public function setMedia($isNewTheme = false)
    {
        parent::setMedia($isNewTheme);

        $this->addCSS($this->module->getPathUri() . '/views/css/admin/labels-history.css');
        $this->addJS($this->module->getPathUri() . '/views/js/admin/labels-history.js');
    }

    public function processBulkPrintSelectionLabelsA4()
    {
        return $this->printSelectionLabels('a4');
    }

    public function processBulkPrintSelectionLabelsA5()
    {
        return $this->printSelectionLabels('a5');
    }

    public function processBulkPrintSelectionLabels10x15()
    {
        return $this->printSelectionLabels('10x15');
    }

    public function printSelectionLabels($format)
    {
        $selectionIds = $this->boxes;

        if (empty($selectionIds)) {
            $this->errors[] = $this->module->l('No orders selected.', 'AdminMondialrelayLabelsHistoryController');
            return false;
        }

        $expeditionNumbers = array();
        foreach ($selectionIds as $id_mondialrelay_selected_relay) {
            $selectedRelay = new MondialrelaySelectedRelay($id_mondialrelay_selected_relay);
            if (!Validate::isLoadedObject($selectedRelay)) {
                $this->errors[] = Tools::displayError(sprintf(
                    'The Mondial Relay order object %s cannot be loaded (or found)',
                    $id_mondialrelay_selected_relay
                ));
                continue;
            }

            if (!$selectedRelay->id_order || !Validate::isLoadedObject(new Order($selectedRelay->id_order))) {
                $this->errors[] = sprintf(
                    $this->module->l('Mondial Relay order object %s : no associated PrestaShop order.', 'AdminMondialrelayLabelsHistoryController'),
                    $id_mondialrelay_selected_relay
                );
                continue;
            }

            if (empty($selectedRelay->expedition_num)) {
                $this->errors[] = sprintf(
                    $this->module->l('Order %s : no expedition number found.', 'AdminMondialrelayLabelsHistoryController'),
                    $selectedRelay->id_order
                );
                continue;
            }

            $expeditionNumbers[] = $selectedRelay->expedition_num;
        }
        if (!empty($this->errors)) {
            return false;
        }

        try {
            $service = MondialrelayService::getService('Label_Bulk_Retrieval');

            // Set data
            if (!$service->init(array(array('Expeditions' => $expeditionNumbers)))) {
                $errors = $service->getErrors();
                $this->errors = array_merge($this->errors, $errors[0]);
                $this->errors = array_merge($this->errors, $errors['generic']);
                $this->warnings[] = $this->module->l('Please [a] Check requirements [/a] to verify if all settings are OK.', 'AdminMondialrelayLabelsGenerationController', array('href' => $this->context->link->getAdminLink('AdminMondialrelayHelp') . '#mondialrelay_requirements-results', 'target' => 'blank'));
                return false;
            }

            // Send data
            if (!$service->send()) {
                $errors = $service->getErrors();
                $this->errors = array_merge($this->errors, $errors[0]);
                $this->errors = array_merge($this->errors, $errors['generic']);
                $this->warnings[] = $this->module->l('Please [a] Check requirements [/a] to verify if all settings are OK.', 'AdminMondialrelayLabelsGenerationController', array('href' => $this->context->link->getAdminLink('AdminMondialrelayHelp') . '#mondialrelay_requirements-results', 'target' => 'blank'));
                return false;
            }

            $result = $service->getResult();

            $statCode = $result[0]->STAT;
            if ($statCode != 0) {
                $this->errors[] = sprintf(
                    $this->module->l('API error %d : %s'),
                    $statCode,
                    $service->getErrorFromStatCode($statCode)
                );
                $this->warnings[] = $this->module->l('Please [a] Check requirements [/a] to verify if all settings are OK.', 'AdminMondialrelayLabelsGenerationController', array('href' => $this->context->link->getAdminLink('AdminMondialrelayHelp') . '#mondialrelay_requirements-results', 'target' => 'blank'));
                return false;
            }

            switch ($format) {
                case 'a4':
                    Tools::redirect(Mondialrelay::URL_DOMAIN . $result[0]->URL_PDF_A4);
                    return false;
                case 'a5':
                    Tools::redirect(Mondialrelay::URL_DOMAIN . $result[0]->URL_PDF_A5);
                    return false;
                case '10x15':
                    Tools::redirect(Mondialrelay::URL_DOMAIN . $result[0]->URL_PDF_10x15);
                    return false;
            }
        } catch (Exception $e) {
            if (isset($service)) {
                $errors = $service->getErrors();
                $this->errors = array_merge($this->errors, $errors[0]);
                $this->errors = array_merge($this->errors, $errors['generic']);
            }

            $this->errors[] = $e->getMessage();
            $this->warnings[] = $this->module->l('Please [a] Check requirements [/a] to verify if all settings are OK.', 'AdminMondialrelayLabelsGenerationController', array('href' => $this->context->link->getAdminLink('AdminMondialrelayHelp') . '#mondialrelay_requirements-results', 'target' => 'blank'));
            return false;
        }

        return true;
    }

    public function processBulkHideSelectionFromHistory()
    {
        $selectionIds = $this->boxes;

        if (empty($selectionIds)) {
            $this->errors[] = $this->module->l('No orders selected.', 'AdminMondialrelayLabelsHistoryController');
            return false;
        }

        foreach ($selectionIds as $id_mondialrelay_selected_relay) {
            $selectedRelay = new MondialrelaySelectedRelay($id_mondialrelay_selected_relay);
            if (!Validate::isLoadedObject($selectedRelay)) {
                $this->errors[] = Tools::displayError(sprintf(
                    'The Mondial Relay order object %s cannot be loaded (or found)',
                    $id_mondialrelay_selected_relay
                ));
                continue;
            }

            $selectedRelay->hide_history = 1;
            $selectedRelay->save();
        }

        if (!empty($this->errors)) {
            return false;
        }

        $this->confirmations[] = $this->module->l('Selected label(s) has been deleted successfully.', 'AdminMondialrelayLabelsHistoryController');
        return true;
    }
    
    public function processDeleteFromHistory()
    {
        $selectedRelay = new MondialrelaySelectedRelay(
            Tools::getValue(MondialrelaySelectedRelay::$definition['primary'])
        );
        if (!Validate::isLoadedObject($selectedRelay)) {
            $this->errors[] = Tools::displayError('The object cannot be loaded (or found)');
            return false;
        }

        $selectedRelay->hide_history = 1;
        $selectedRelay->save();

        $this->confirmations[] = $this->module->l('Selected label(s) has been deleted successfully.', 'AdminMondialrelayLabelsHistoryController');
    }
}
