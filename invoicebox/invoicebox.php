<?php

class Invoicebox extends PaymentModule
{
    private $_html = '';
    private $_postErrors = array();
    public $itransfer_participant_id;
    public $itransfer_participant_ident;
    public $invoicebox_api_key;
    public $itransfer_testmode;

    public function __construct()
    {
        $this->name = 'invoicebox';
        $this->tab = 'payments_gateways';
        $this->version = 1.0;
        $this->author = 'Invoicebox';

        $this->currencies = true;
        $this->currencies_mode = 'radio';

        $config = Configuration::getMultiple(array('ITRANSFER_PARTICIPANT_ID', 'ITRANSFER_PARTICIPANT_IDENT', 'INVOICEBOX_API_KEY', 'ITRANSFER_TESTMODE'));
        if (isset($config['ITRANSFER_PARTICIPANT_ID']))
            $this->itransfer_participant_id = $config['ITRANSFER_PARTICIPANT_ID'];
        if (isset($config['ITRANSFER_PARTICIPANT_IDENT']))
            $this->itransfer_participant_ident = $config['ITRANSFER_PARTICIPANT_IDENT'];
        if (isset($config['INVOICEBOX_API_KEY']))
            $this->invoicebox_api_key = $config['INVOICEBOX_API_KEY'];
        if (isset($config['ITRANSFER_TESTMODE']))
            $this->itransfer_testmode = $config['ITRANSFER_TESTMODE'];

        parent::__construct();

        
        $this->page = basename(__FILE__, '.php');
        $this->displayName = 'Invoicebox';
        $this->description = $this->l('Accept payments with Invoicebox');
        $this->confirmUninstall = $this->l('Are you sure you want to delete your details ?');
    }

    public function install()
    {
        if (!parent::install() OR !$this->registerHook('payment') OR !$this->registerHook('paymentReturn'))
            return false;

        Configuration::updateValue('ITRANSFER_PARTICIPANT_ID', '');
        Configuration::updateValue('ITRANSFER_PARTICIPANT_IDENT', '');
        Configuration::updateValue('INVOICEBOX_API_KEY', '');
        Configuration::updateValue('ITRANSFER_TESTMODE', '1');

        return true;
    }

    public function uninstall()
    {
        Configuration::deleteByName('ITRANSFER_PARTICIPANT_ID');
        Configuration::deleteByName('ITRANSFER_PARTICIPANT_IDENT');
        Configuration::deleteByName('INVOICEBOX_API_KEY');
        Configuration::deleteByName('ITRANSFER_TESTMODE');

        parent::uninstall();
    }

    private function _postValidation()
    {
        if (isset($_POST['btnSubmit'])) {
            if (empty($_POST['itransfer_participant_id']))
                $this->_postErrors[] = $this->l('Shop ID is required');
            elseif (empty($_POST['itransfer_participant_ident']))
                $this->_postErrors[] = $this->l('Region Shop ID is required');
            elseif (empty($_POST['invoicebox_api_key']))
                $this->_postErrors[] = $this->l('Secret key is required');
        }
    }

    private function _postProcess()
    {
        if (isset($_POST['btnSubmit'])) {
            if (!isset($_POST['itransfer_testmode']))
                $_POST['itransfer_testmode'] = 0;

            Configuration::updateValue('ITRANSFER_PARTICIPANT_ID', $_POST['itransfer_participant_id']);
            Configuration::updateValue('ITRANSFER_PARTICIPANT_IDENT', $_POST['itransfer_participant_ident']);
            Configuration::updateValue('INVOICEBOX_API_KEY', $_POST['invoicebox_api_key']);
            Configuration::updateValue('ITRANSFER_TESTMODE', $_POST['itransfer_testmode']);
        }
        $this->_html .= '<div class="conf confirm"><img src="../img/admin/ok.gif" alt="' . $this->l('OK') . '" /> ' . $this->l('Settings updated') . '</div>';
    }

    private function _displayRb()
    {
        $this->_html .= '<img src="../modules/invoicebox/invoicebox.jpg" style="float:left; margin-right:15px;"><b>' . $this->l('This module allows you to accept payments by Invoicebox.') . '</b><br /><br />';
    }

    private function _displayForm()
    {
        $bTestMode = htmlentities(Tools::getValue('itransfer_testmode', $this->itransfer_testmode), ENT_COMPAT, 'UTF-8');
        $checked = '';
        if ($bTestMode)
            $checked = 'checked="checked"';

        $this->_html .=
            '<form action="' . $_SERVER['REQUEST_URI'] . '" method="post">
            <fieldset>
            <legend><img src="../img/admin/contact.gif" />' . $this->l('Contact details') . '</legend>
                <table border="0" width="500" cellpadding="0" cellspacing="0" id="form">
                    <tr><td colspan="2">' . $this->l('Please specify required data') . '.<br /><br /></td></tr>
                    <tr><td width="140" style="height: 35px;">' . $this->l('Shop id') . '</td><td><input type="text" name="itransfer_participant_id" value="' . htmlentities(Tools::getValue('itransfer_participant_id', $this->itransfer_participant_id), ENT_COMPAT, 'UTF-8') . '" style="width: 300px;" /></td></tr>
                    <tr><td width="140" style="height: 35px;">' . $this->l('Region Shop id') . '</td><td><input type="text" name="itransfer_participant_ident" value="' . htmlentities(Tools::getValue('itransfer_participant_ident', $this->itransfer_participant_ident), ENT_COMPAT, 'UTF-8') . '" style="width: 300px;" /></td></tr>
                    <tr><td width="140" style="height: 35px;">' . $this->l('Secret key') . '</td><td><input type="text" name="invoicebox_api_key" value="' . htmlentities(Tools::getValue('invoicebox_api_key', $this->invoicebox_api_key), ENT_COMPAT, 'UTF-8') . '" style="width: 300px;" /></td></tr>
					<tr><td width="140" style="height: 35px;">' . $this->l('Test mode') . '</td><td>
<input id="itransfer_testmode" type="checkbox" '.$checked.' value="1" name="itransfer_testmode"></td></tr>


                    
                    <tr><td colspan="2" align="center"><br /><input class="button" name="btnSubmit" value="' . $this->l('Update settings') . '" type="submit" /></td></tr>
                </table>
            </fieldset>
        </form>';
    }

    public function getContent()
    {
        $this->_html = '<h2>' . $this->displayName . '</h2>';

        if (!empty($_POST)) {
            $this->_postValidation();
            if (!sizeof($this->_postErrors))
                $this->_postProcess();
            else
                foreach ($this->_postErrors AS $err)
                    $this->_html .= '<div class="alert error">' . $err . '</div>';
        } else
            $this->_html .= '<br />';

        $this->_displayRb();
        $this->_displayForm();

        return $this->_html;
    }

    public function hookPayment($params)
    {
        $strDescription = '';

        foreach ($params['cart']->getProducts() as $product) {
            $strDescription .= $product['name'];
            if (!empty($product['attributes_small']))
                $strDescription .= $product['attributes_small'];
            if ($product['cart_quantity'] > 1)
                $strDescription .= "*" . $product['cart_quantity'];
            $strDescription .= " ";
        }

        $data = array(
            'cartId' => $params['cart']->id,
            'amount' =>  $params['cart']->getOrderTotal(true, 3),
            'desc' => $strDescription,
            'data' => 'Email=' . $this->context->cookie->email . '|connection_type=prestashop',
        );

        global $smarty;
        $smarty->caching = false;
        $smarty->force_compile = true;
        $smarty->compile_check = false;
        $smarty->assign('data', $data);

        return $this->display(__FILE__, 'invoicebox.tpl');
    }

    public function getL($key)
    {
        $translations = array(
            'success' => 'Invoicebox transaction is carried out successfully.',
            'fail' => 'Invoicebox transaction is refused.'
        );
        return $translations[$key];
    }

    public function hookPaymentReturn($params)
    {
        if (!$this->active)
            return;

        return $this->display(__FILE__, 'confirmation.tpl');
    }

}

?>
