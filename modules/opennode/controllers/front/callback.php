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

class OpennodeCallbackModuleFrontController extends ModuleFrontController
{
    public $ssl = true;

    public function postProcess()
    {
        $cart_id = (int)Tools::getValue('order_id');
        $order_id = Order::getOrderByCartId($cart_id);
        $order = new Order($order_id);

        try {
            if (!$order) {
                $error_message = 'OpenNode Order #' . Tools::getValue('order_id') . ' does not exists';

                $this->logError($error_message, $cart_id);
                throw new Exception($error_message);
            }

            $auth_token = Configuration::get('OPENNODE_API_AUTH_TOKEN');

            if (strcmp(hash_hmac('sha256', Tools::getValue('id'), $auth_token), Tools::getValue('hashed_order')) != 0) {
                $error_message = 'Request is not signed with the same API Key, ignoring';

                $this->logError($error_message, $cart_id);
                throw new Exception($error_message);
            }

            $apiConfig = array(
              'auth_token' => $auth_token,
              'environment' => 'live',
              'user_agent' => 'OpenNode - Prestashop v'._PS_VERSION_
            );

            \OpenNode\OpenNode::config($apiConfig);
            $charge = \OpenNode\Merchant\Charge::find(Tools::getValue('id'));

            if (!$charge) {
                $error_message = 'OpenNode Order #' . Tools::getValue('id') . ' does not exists';

                $this->logError($error_message, $cart_id);
                throw new Exception($error_message);
            }

            if ($order->id_cart != $charge->order_id) {
                $error_message = 'OpenNode and Prestashop orders do not match';

                $this->logError($error_message, $cart_id);
                throw new Exception($error_message);
            }


            switch ($charge->status) {
                case 'paid':
                    $order_status = 'PS_OS_PAYMENT';
                    break;
                case 'pending':
                    $order_status = 'OPENNODE_PENDING';
                    break;
                case 'underpaid':
                    $order_status = 'OPENNODE_UNDERPAID';
                    break;
                case 'processing':
                    $order_status = 'OPENNODE_CONFIRMING';
                    break;
                case 'canceled':
                    $order_status = 'PS_OS_CANCELED';
                    break;
                case 'refunded':
                    $order_status = 'PS_OS_CANCELED';
                    break;
                default:
                    $order_status = false;
            }

            if ($order_status !== false) {
                $history = new OrderHistory();
                $history->id_order = $order->id;
                $history->changeIdOrderState((int)Configuration::get($order_status), $order->id);
                $history->addWithemail(true, array(
                    'order_name' => Tools::getValue('order_id'),
                ));

                $this->context->smarty->assign(array(
                    'text' => 'OK'
                ));
            } else {
                $this->context->smarty->assign(array(
                    'text' => 'Order Status '. $charge->status .' not implemented'
                ));
            }
        } catch (Exception $e) {
            $this->context->smarty->assign(array(
                'text' => get_class($e) . ': ' . $e->getMessage()
            ));
        }
        if (_PS_VERSION_ >= '1.7') {
            $this->setTemplate('module:opennode/views/templates/front/payment_callback.tpl');
        } else {
            $this->setTemplate('payment_callback.tpl');
        }
    }

    private function logError($message, $cart_id)
    {
        PrestaShopLogger::addLog($message, 3, null, 'Cart', $cart_id, true);
    }
}
