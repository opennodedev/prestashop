<?php

require_once(_PS_MODULE_DIR_ . '/opennode/vendor/opennode/init.php');
require_once(_PS_MODULE_DIR_ . '/opennode/vendor/version.php');
define('OPENNODE_CHECKOUT_PATH', 'https://opennode.co/checkout/');

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
            'amount'           => (strtoupper($currency->iso_code)) === 'BTC' ? convertToSats($total) : number_format($total, 8, '.', ''),
            'currency'         => $currency->iso_code,
            'auto_settle'      => true,
            'callback_url'     => $this->context->link->getModuleLink('opennode', 'callback'),
            'success_url'      => $success_url,
            'description'      => join($description, ', '),
            'name'             => $customer->firstname . ' ' . $customer->lastname,
            'email'            => $customer->email
        ));
        } catch(Exception $e){
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

    private function convertToSats($amount) {
      number_format($amount*100000000, 8, '.', '');
    }

}
