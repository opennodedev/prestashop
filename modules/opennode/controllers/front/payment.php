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

class OpennodePaymentModuleFrontController extends ModuleFrontController
{
    public $ssl = true;
    public $display_column_left = false;

    public function initContent()
    {
        parent::initContent();

        $cart = $this->context->cart;

        if (!$this->module->checkCurrency($cart)) {
            Tools::redirect('index.php?controller=order');
        }

        $this->context->smarty->assign(array(
            'nbProducts'    => $cart->nbProducts(),
            'cust_currency' => $cart->id_currency,
            'currencies'    => $this->module->getCurrency((int)$cart->id_currency),
            'total'         => $cart->getOrderTotal(true, Cart::BOTH),
            'this_path'     => $this->module->getPathUri(),
            'this_path_bw'  => $this->module->getPathUri(),
            'this_path_ssl' => Tools::getShopDomainSsl(true, true)
                . __PS_BASE_URI__ . 'modules/' . $this->module->name . '/'
        ));

        $this->setTemplate('payment_execution.tpl');
    }
}
