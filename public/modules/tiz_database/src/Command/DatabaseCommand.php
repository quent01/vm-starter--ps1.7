<?php
namespace Tiz\TizDatabase\Command;

// require_once dirname(__FILE__) . '/../../../../config/config.inc.php';

use Symfony\Component\Console\Command\Command;
use PrestaShopBundle\Command\ModuleCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class DatabaseCommand extends ModuleCommand{

    protected $translator;


    private $allowedActions = array(
        'dbresetwoomigration',
        'dbclean',
        'dbrepair',
        'dboptimize'
    );


    protected function configure()
    {
        // The name of the command (the part after "bin/console")
        $this->setName('tiz:tiz_database');
        $this->setDescription('Remove woocommerce data from Prestashop.');
        $this->addArgument('action', InputArgument::REQUIRED, sprintf('Action to execute (Allowed actions: %s).', implode(' / ', $this->allowedActions)));

        $this->setHelp('Execute preconfigured db action on prestashop from PHP CLI.');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->init($input, $output);
        $action = $input->getArgument('action');

        if (!in_array($action, $this->allowedActions)) {
            $this->displayMessage(
                $this->translator->trans(
                    'Unknown module action. It must be one of these values: %actions%',
                    array(
                        '%actions%' => implode(' / ', $this->allowedActions)
                    ),
                    'Admin.Modules.Notification'
                ),
                'error'
            );

            return;
        }

        $this->{'execute'.ucfirst($action)}();
    }

    protected function executeDbresetwoomigration(){
        $arr_order_tables = array(
            'orders',
            'order_carrier',
            'order_cart_rule',
            'order_detail',
            'order_detail_tax',
            'order_history',
            'order_invoice',
            'order_invoice_payment',
            'order_invoice_tax',
            'order_message',
            'order_message_lang',
            'order_payment',
            'order_return',
            'order_return_detail',
            'order_return_state',
            'order_return_state_lang',
            'order_slip',
            'order_slip_detail',
            'order_slip_detail_tax',
        );
        $arr_customer_tables = array(
            'customer',
            'customer_message',
            'customer_message_sync_imap',
            'customer_thread',
        );
        $arr_migrationpro_tables = array(
            'migrationpro_woo',
            'woomigrationpro_data',
            'woomigrationpro_migrated_data',
            'woomigrationpro_process',
            'woomigrationpro_warning_logs',
        );
        $this->displayMessage('We will remove all orders ...', 'info');
        foreach ($arr_order_tables as $order_table) {
            $query = 'TRUNCATE TABLE `'._DB_PREFIX_.$order_table. '`';
            if(!\Db::getInstance()->Execute($query)){
                die('Erreur : '.$order_table.' cannot be truncated');
            }
        }
        $this->displayMessage('All order table have been truncated.', 'info');
        

        $this->displayMessage('We will remove all customers ...', 'info');
        foreach ($arr_customer_tables as $customer_table) {
            $query = 'TRUNCATE TABLE `' . _DB_PREFIX_ . $customer_table . '`';
            if (!\Db::getInstance()->Execute($query)) {
                die('Erreur : ' . $customer_table . ' cannot be truncated');
            }
        }
        $this->displayMessage('All customers have been deleted.', 'info');

        
        $this->displayMessage('We will remove all migrationpro data (except mapping)...', 'info');
        foreach ($arr_migrationpro_tables as $migrationpro_table) {
            $query = 'TRUNCATE TABLE `' . _DB_PREFIX_ . $migrationpro_table . '`';
            if (!\Db::getInstance()->Execute($query)) {
                die('Erreur : ' . $migrationpro_table . ' cannot be truncated');
            }
        }
        $this->displayMessage('Woomigrationpro tables to reset import have been deleted.', 'info');
    }

    protected function executeDboptimize(){
        // @todo
        // we run a script to optimize database
        // https://electrictoolbox.com/optimize-tables-mysql-php/
        $this->displayMessage(
            "dboptimize was executed with success",
            'info'
        );
    }

    protected function executeDbclean(){
        // @todo
        // we delete @tiz.fr clients because password is perhaps not secure
        $this->displayMessage(
            "dbclean was executed with success",
            'info'
        );
    }

    protected function executeDbrepair(){
        $query = 'UPDATE '. _DB_PREFIX_.'orders SET conversion_rate = 1.00000 WHERE conversion_rate = 0';
        if (!\Db::getInstance()->Execute($query)) {
            die('Erreur : '.$query.' cannot be executed');
        }
        $this->displayMessage(
            "dbrepair was executed with success",
            'info'
        );
    }
}