<?php
/**
* NOTICE OF LICENSE
* The MIT License (MIT)
*
* Copyright (c) 2019 OpenNode https://opennode.com
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
* @author    OpenNode <hello@opennode.com>
* @copyright 2019 OpenNode
* @license   https://github.com/opennodedev/prestashop/blob/master/LICENSE  The MIT License (MIT)
*/

require_once(_PS_MODULE_DIR_ . '/opennode/vendor/opennode/init.php');
require_once(_PS_MODULE_DIR_ . '/opennode/vendor/version.php');
define('OPENNODE_CHECKOUT_PATH', 'https://checkout.opennode.com/');

class OpenNodeRedirectModuleFrontController extends ModuleFrontController
{
    public $ssl = true;

    public function initContent()
    {
        parent::initContent();

        $cart = $this->context->cart;

        if (!$this->module->checkCurrency($cart)) {
            Tools::redirect('index.php?controller=order');
        }

        $total = (float)number_format($cart->getOrderTotal(true, 3), 2, '.', '');
        $currency = Context::getContext()->currency;

        $description = array();
        foreach ($cart->getProducts() as $product) {
            $description[] = $product['cart_quantity'] . ' × ' . $product['name'];
        }

        $customer = new Customer($cart->id_customer);

        $link = new Link();
        $success_url = $link->getPageLink('order-confirmation', null, null, array(
          'id_cart'     => $cart->id,
          'id_module'   => $this->module->id,
          'key'         => $customer->secure_key
        ));

        $auth_token = Configuration::get('OPENNODE_API_AUTH_TOKEN');

        $apiConfig = array(
          'auth_token' => $auth_token,
          'environment' => 'live',
          'user_agent' => 'OpenNode - Prestashop v'._PS_VERSION_
        );

        \OpenNode\OpenNode::config($apiConfig);

        try {
            $order = \OpenNode\Merchant\Charge::create(array(
                'order_id'         => $cart->id,
                'amount'           => calculateAmount($currency->iso_code, $total),
                'currency'         => $currency->iso_code,
                'auto_settle'      => true,
                'callback_url'     => $this->context->link->getModuleLink('opennode', 'callback'),
                'success_url'      => $success_url,
                'description'      => join($description, ', '),
                'name'             => $customer->firstname . ' ' . $customer->lastname,
                'email'            => $customer->email
            ));
        } catch (Exception $e) {
            Tools::redirect('index.php?controller=order&step=3');
        }

        if ($order) {
            if (!$order->id) {
                Tools::redirect('index.php?controller=order&step=3');
            }

            $customer = new Customer($cart->id_customer);
            $this->module->validateOrder(
                $cart->id,
                Configuration::get('OPENNODE_PENDING'),
                $total,
                $this->module->displayName,
                null,
                null,
                (int)$currency->id,
                false,
                $customer->secure_key
            );

            Tools::redirect(OPENNODE_CHECKOUT_PATH . $order->id);
        } else {
            Tools::redirect('index.php?controller=order&step=3');
        }
    }

    private function convertToSats($amount)
    {
        return number_format($amount*100000000, 8, '.', '');
    }

    private function calculateAmount($currency, $total)
    {
        if (Tools::strtoupper($currency) === 'BTC') {
            return convertToSats($total);
        } else {
            return number_format($total, 8, '.', '');
        }
    }
}
