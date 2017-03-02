<?php

/**
 * Copyright (C) 2017 Comolo GmbH
 *
 * @author    Hendrik Obermayer
 * @copyright 2017 Comolo GmbH <https://www.comolo.de>
 * @license   MIT
 */

namespace Comolo\Isotope\Model\Payment;

use System;
use Environment;

use Isotope\Interfaces\IsotopePayment;
use Isotope\Interfaces\IsotopeProductCollection;
use Isotope\Interfaces\IsotopePurchasableCollection;
use Isotope\Model\Payment\Postsale;
use Isotope\Model\Product;
use Isotope\Model\ProductCollection\Order;
use Isotope\Module\Checkout;
use Isotope\Template;
use Isotope\Currency;

use Paymill\Models\Request\Transaction as PaymillTransaction;
use Paymill\Request as PaymillRequest;
use Paymill\Services\PaymillException;

/**
 * Paymill payment method
 *
 * @property string $paymill_private_key
 * @property string $paymill_public_key
 */
class PaymillPayment extends Postsale
{
    /**
     * Return information in the backend.
     *
     * @param integer
     * @return string
     */
    public function backendInterface($orderId)
    {
        // todo
        $database = \Database::getInstance();
        $order = $database
            ->prepare("SELECT * FROM tl_iso_product_collection WHERE id LIKE ?")
            ->limit(1)
            ->execute($orderId);
        $template = new \BackendTemplate('be_iso_payment_paymill');
        $template->order = $order;
        $template->payment_data = unserialize($order->payment_data);
        return $template->parse();
    }

    /**
     * @inheritdoc
     */
    public function processPayment(IsotopeProductCollection $objOrder, \Module $objModule)
    {
        if ($objOrder->order_status < 1) {
            $this->processPostsale($objOrder);
        }

        return parent::processPayment($objOrder, $objModule);
    }

    /**
     * @inheritdoc
     */
    public function processPostsale(IsotopeProductCollection $objOrder)
    {
        if (!$objOrder instanceof IsotopePurchasableCollection) {
            \System::log('Product collection ID "' . $objOrder->getId() . '" is not purchasable', __METHOD__, TL_ERROR);
            return false;
        }

        $paymillToken = \Input::post('paymillToken');

        if (!$paymillToken || empty($paymillToken)) {
            return false; // no token
        }

        $transaction = new PaymillTransaction();
        $transaction
            ->setAmount(Currency::getAmountInMinorUnits($objOrder->getTotal(), $objOrder->getCurrency()))
            ->setCurrency($objOrder->getCurrency())
            ->setToken($paymillToken)
            ->setDescription('#'.$objOrder->getId())
        ;

        try {
            $request = new PaymillRequest($this->paymill_private_key);
            $response = $request->create($transaction);

            if ($response->getResponseCode() == 20000) {
                // Received payment
                /* todo
                 * $objOrder->payment_data = array(
                    'something' => 'something',
                );*/

                $objOrder->setDatePaid(time());
                $objOrder->updateOrderStatus($this->new_order_status);
                $objOrder->save();

                return true;
            }

            return false;

            // todo: remove
            /*
            var_dump([
                'code' => $response->getResponseCode(), // 20000 => ok
                'status' => $response->getStatus(), // closed
                'id' => $response->getId(), // tran_4959d795d57306b4922e6999a017
                'shortId' => $response->getShortId(),
            ]);
            */

        } catch(PaymillException $e){

            System::log('Paymill error. Order "' . $objOrder->getId() . '". Paymill Status: '.$e->getResponseCode() .' Error: '.$e->getErrorMessage(), __METHOD__, TL_ERROR);

            return false;

            // todo: remove
            /*echo $e->getResponseCode();
            echo "<br>";
            echo $e->getStatusCode();
            echo "<br>";
            echo $e->getErrorMessage();
            echo "<br>";
            echo $e->getRawError();
            */
        }
    }

    /**
     * @inheritdoc
     */
    public function checkoutForm(IsotopeProductCollection $objOrder, \Module $objModule)
    {
        if (!$objOrder instanceof IsotopePurchasableCollection) {
            \System::log('Product collection ID "' . $objOrder->getId() . '" is not purchasable', __METHOD__, TL_ERROR);
            return false;
        }

        /** @var Template|\stdClass $objTemplate */
        $objTemplate = new Template('iso_payment_paymill');

        $objTemplate->setData($this->arrData);
        $objTemplate->id = $objOrder->getId();
        $objTemplate->amount = Currency::getAmountInMinorUnits($objOrder->getTotal(), $objOrder->getCurrency()); // todo: check
        $objTemplate->currency = $objOrder->getCurrency();
        $objTemplate->paymill_public_key = $objModule->paymill_public_key;

        $objTemplate->action = Environment::get('base') . Checkout::generateUrlForStep('complete', $objOrder);
        $objTemplate->cancel_return = Environment::get('base') . Checkout::generateUrlForStep('failed');
        // todo: remove
        //$objTemplate->notify_url = Environment::get('base') . 'system/modules/isotope/postsale.php?mod=pay&id=' . $this->id;

        $objTemplate->headline = specialchars($GLOBALS['TL_LANG']['MSC']['pay_with_redirect'][0]);
        $objTemplate->message = specialchars($GLOBALS['TL_LANG']['MSC']['pay_with_redirect'][1]);
        $objTemplate->slabel = specialchars($GLOBALS['TL_LANG']['MSC']['pay_with_redirect'][2]);
        $objTemplate->noscript = specialchars($GLOBALS['TL_LANG']['MSC']['pay_with_redirect'][3]);

        return $objTemplate->parse();
    }
}