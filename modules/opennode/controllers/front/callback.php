<?php
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
                case 'processing':
                    $order_status = 'OPENNODE_CONFIRMING';
                    break;
                case 'canceled':
                    $order_status = 'PS_OS_CANCELED';
                    break;
                case 'refunded':
                    $order_status = 'PS_OS_REFUND';
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
