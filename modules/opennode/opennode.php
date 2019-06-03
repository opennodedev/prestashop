<?php
/**
* NOTICE OF LICENSE
* The MIT License (MIT)
*
* Copyright (c) 2018 OpenNode https://opennode.co
*
* Permission is hereby granted, free of charge, to any person obtaining a copy
* of this software and associated documentation files (the "Software"), to deal
* in the Software without restriction, including without limitation the rights
* to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
* copies of the Software, and to permit persons to whom the Software is
* furnished to do so, subject to the following conditions:
*
* The above copyright notice and this permission notice shall be included in
* all copies or substantial portions of the Software.
*
* THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
* IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
* FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
* AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
* LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
* OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
* THE SOFTWARE.
*
* @author    OpenNode <hello@opennode.co>
* @copyright 2018 OpenNode
* @license   https://github.com/opennodedev/prestashop/blob/master/LICENSE  The MIT License (MIT)
*/

if (!defined('_PS_VERSION_')) {
    exit;
}

require_once _PS_MODULE_DIR_ . '/opennode/vendor/opennode/init.php';
require_once _PS_MODULE_DIR_ . '/opennode/vendor/version.php';

class OpenNode extends PaymentModule
{
    private $html = '';
    private $postErrors = array();

    public $api_auth_token;

    public function __construct()
    {
        $this->name = 'opennode';
        $this->tab = 'payments_gateways';
        $this->version = '1.0.0';
        $this->author = 'OpenNode.co';
        $this->is_eu_compatible = 1;
        $this->controllers = array('payment', 'redirect', 'callback', 'cancel');
        $this->ps_versions_compliancy = array('min' => '1.6', 'max' => _PS_VERSION_);
        $this->module_key = 'b64bcc26da067c2c6d2d0d235c6b2fe0';

        $this->bootstrap = true;

        $config = Configuration::getMultiple(
            array(
                'OPENNODE_API_AUTH_TOKEN'
            )
        );

        if (!empty($config['OPENNODE_API_AUTH_TOKEN'])) {
            $this->api_auth_token = $config['OPENNODE_API_AUTH_TOKEN'];
        }

        parent::__construct();

        $this->displayName = $this->l('Accept Bitcoin instantly via OpenNode');
        $this->description = $this->l('Start accepting bitcoin instantly through Lightning Network today.');
        $this->confirmUninstall = $this->l('Are you sure you want to delete your info?');

        if (!isset($this->api_auth_token)) {
            $this->warning = $this->l('API Credentials must be set in order to use this module correctly.');
        }
    }

    public function install()
    {
        if (!function_exists('curl_version')) {
            $this->_errors[] = $this->l('This module requires cURL PHP extension in order to function normally.');

            return false;
        }

        $order_pending = new OrderState();
        $order_pending->name = array_fill(0, 10, 'Awaiting OpenNode payment');
        $order_pending->send_email = 0;
        $order_pending->invoice = 0;
        $order_pending->color = 'RoyalBlue';
        $order_pending->unremovable = false;
        $order_pending->logable = 0;

        $order_underpaid = new OrderState();
        $order_underpaid->name = array_fill(0, 10, 'Customer has paid via standard on-Chain, but has underpaid. Waiting on user to send the remainder before marking as PAID');
        $order_underpaid->send_email = 0;
        $order_underpaid->invoice = 0;
        $order_underpaid->color = '#f0ce00';
        $order_underpaid->unremovable = false;
        $order_underpaid->logable = 0;

        $order_confirming = new OrderState();
        $order_confirming->name = array_fill(0, 10, 'Awaiting 1 confirmation by Bitcoin network');
        $order_confirming->send_email = 0;
        $order_confirming->invoice = 0;
        $order_confirming->color = '#d9ff94';
        $order_confirming->unremovable = false;
        $order_confirming->logable = 0;

        if ($order_pending->add()) {
            copy(
                _PS_ROOT_DIR_ . '/modules/opennode/logo.png',
                _PS_ROOT_DIR_ . '/img/os/' . (int) $order_pending->id . '.png'
            );
        }

        if ($order_underpaid->add()) {
            copy(
                _PS_ROOT_DIR_ . '/modules/opennode/logo.png',
                _PS_ROOT_DIR_ . '/img/os/' . (int) $order_underpaid->id . '.png'
            );
        }

        if ($order_confirming->add()) {
            copy(
                _PS_ROOT_DIR_ . '/modules/opennode/logo.png',
                _PS_ROOT_DIR_ . '/img/os/' . (int) $order_confirming->id . '.png'
            );
        }

        Configuration::updateValue('OPENNODE_PENDING', $order_pending->id);
        Configuration::updateValue('OPENNODE_UNDERPAID', $order_underpaid->id);
        Configuration::updateValue('OPENNODE_CONFIRMING', $order_confirming->id);

        if (!parent::install()
            || !$this->registerHook('payment')
            || !$this->registerHook('displayPaymentEU')
            || !$this->registerHook('paymentReturn')
            || !$this->registerHook('paymentOptions')) {
            return false;
        }

        return true;
    }

    public function uninstall()
    {
        $order_state_pending = new OrderState(Configuration::get('OPENNODE_PENDING'));
        $order_state_underpaid = new OrderState(Configuration::get('OPENNODE_UNDERPAID'));
        $order_state_confirming = new OrderState(Configuration::get('OPENNODE_CONFIRMING'));

        return (
            Configuration::deleteByName('OPENNODE_APP_ID') &&
            Configuration::deleteByName('OPENNODE_API_AUTH_TOKEN') &&
            $order_state_pending->delete() &&
            $order_state_underpaid->delete() &&
            $order_state_confirming->delete() &&
            parent::uninstall()
        );
    }

    private function postValidation()
    {
        if (Tools::isSubmit('btnSubmit')) {
            if (!Tools::getValue('OPENNODE_API_AUTH_TOKEN')) {
                $this->postErrors[] = $this->l('API Key is required.');
            }
        }
    }

    private function postProcess()
    {
        if (Tools::isSubmit('btnSubmit')) {
            Configuration::updateValue(
                'OPENNODE_API_AUTH_TOKEN',
                $this->stripString(Tools::getValue('OPENNODE_API_AUTH_TOKEN'))
            );
        }

        $this->html .= $this->displayConfirmation($this->l('Settings updated successfully'));
    }

    private function displayOpennode()
    {
        return $this->display(__FILE__, 'infos.tpl');
    }

    private function displayOpennodeInformation($renderForm)
    {
        $this->html .= $this->displayOpennode();
        $this->context->controller->addCSS($this->_path.'/views/css/tabs.css', 'all');
        $this->context->controller->addJS($this->_path.'/views/js/javascript.js', 'all');
        $this->context->smarty->assign('form', $renderForm);
        return $this->display(__FILE__, 'information.tpl');
    }

    public function getContent()
    {
        if (Tools::isSubmit('btnSubmit')) {
            $this->postValidation();
            if (!count($this->postErrors)) {
                $this->postProcess();
            } else {
                foreach ($this->postErrors as $err) {
                    $this->html .= $this->displayError($err);
                }
            }
        } else {
            $this->html .= '<br />';
        }

        $renderForm = $this->renderForm();
        $this->html .= $this->displayOpennodeInformation($renderForm);

        return $this->html;
    }

    public function hookPayment($params)
    {
        if (_PS_VERSION_ >= 1.7) {
            return;
        }

        if (!$this->active) {
            return;
        }

        if (!$this->checkCurrency($params['cart'])) {
            return;
        }

        $this->smarty->assign(array(
            'this_path'     => $this->_path,
            'this_path_bw'  => $this->_path,
            'this_path_ssl' => Tools::getShopDomainSsl(true, true) . __PS_BASE_URI__ . 'modules/' . $this->name . '/',
        ));

        return $this->display(__FILE__, 'payment.tpl');
    }


    public function hookDisplayOrderConfirmation($params)
    {
        if (_PS_VERSION_ <= 1.7) {
            return;
        }

        if (!$this->active) {
            return;
        }

        if (!$this->checkCurrency($params['cart'])) {
            return;
        }

        $this->smarty->assign(array(
            'this_path'     => $this->_path,
            'this_path_bw'  => $this->_path,
            'this_path_ssl' => Tools::getShopDomainSsl(true, true) . __PS_BASE_URI__ . 'modules/' . $this->name . '/',
        ));

        return $this->context->smarty->fetch(__FILE__, 'payment.tpl');
    }

    public function hookPaymentOptions($params)
    {
        if (!$this->active) {
            return;
        }

        if (!$this->checkCurrency($params['cart'])) {
            return;
        }

        $newOption = new PrestaShop\PrestaShop\Core\Payment\PaymentOption();
        $newOption->setCallToActionText('Pay with Bitcoin: on-chain or with lightning')
            ->setAction($this->context->link->getModuleLink($this->name, 'redirect', array(), true));
            //->setLogo(Media::getMediaPath(_PS_MODULE_DIR_.$this->name.'/views/img/bitcoin-logo.jpg'));

        $payment_options = array($newOption);

        return $payment_options;
    }

    public function checkCurrency($cart)
    {
        $currency_order = new Currency($cart->id_currency);
        $currencies_module = $this->getCurrency($cart->id_currency);

        if (is_array($currencies_module)) {
            foreach ($currencies_module as $currency_module) {
                if ($currency_order->id == $currency_module['id_currency']) {
                    return true;
                }
            }
        }

        return false;
    }

    public function renderForm()
    {
        $fields_form = array(
            'form' => array(
                'legend' => array(
                    'title' => $this->l('Accept Bitcoin instantly via OpenNode'),
                    'icon'  => 'icon-bitcoin',
                ),
                'input'  => array(
                    array(
                        'type'     => 'text',
                        'label'    => $this->l('API Key'),
                        'name'     => 'OPENNODE_API_AUTH_TOKEN',
                        'desc'     => $this->l('Your Auth Token generated on OpenNode dashboard'),
                        'required' => true,
                    ),
                ),
                'submit' => array(
                    'title' => $this->l('Save'),
                ),
            ),
        );

        $helper = new HelperForm();
        $helper->show_toolbar = false;
        $helper->table = $this->table;
        $lang = new Language((int) Configuration::get('PS_LANG_DEFAULT'));
        $helper->default_form_language = $lang->id;
        $helper->allow_employee_form_lang = (Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG')
            ? Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG') : 0);
        $this->fields_form = array();
        $helper->id = (int) Tools::getValue('id_carrier');
        $helper->identifier = $this->identifier;
        $helper->submit_action = 'btnSubmit';
        $helper->currentIndex = $this->context->link->getAdminLink('AdminModules', false)
        . '&configure=' . $this->name . '&tab_module='
        . $this->tab . '&module_name=' . $this->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');
        $helper->tpl_vars = array(
            'fields_value' => $this->getConfigFieldsValues(),
            'languages'    => $this->context->controller->getLanguages(),
            'id_language'  => $this->context->language->id,
        );

        return $helper->generateForm(array($fields_form));
    }

    public function getConfigFieldsValues()
    {
        return array(
            'OPENNODE_API_AUTH_TOKEN' => $this->stripString(
                Tools::getValue(
                    'OPENNODE_API_AUTH_TOKEN',
                    Configuration::get('OPENNODE_API_AUTH_TOKEN')
                )
            ),
        );
    }

    private function stripString($item)
    {
        return preg_replace('/\s+/', '', $item);
    }
}
