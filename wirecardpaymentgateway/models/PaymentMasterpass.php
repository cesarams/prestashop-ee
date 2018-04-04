<?php
/**
 * Shop System Plugins - Terms of Use
 *
 * The plugins offered are provided free of charge by Wirecard AG and are explicitly not part
 * of the Wirecard AG range of products and services.
 *
 * They have been tested and approved for full functionality in the standard configuration
 * (status on delivery) of the corresponding shop system. They are under General Public
 * License version 3 (GPLv3) and can be used, developed and passed on to third parties under
 * the same terms.
 *
 * However, Wirecard AG does not provide any guarantee or accept any liability for any errors
 * occurring when used in an enhanced, customized shop system configuration.
 *
 * Operation in an enhanced, customized configuration is at your own risk and requires a
 * comprehensive test phase by the user of the plugin.
 *
 * Customers use the plugins at their own risk. Wirecard AG does not guarantee their full
 * functionality neither does Wirecard AG assume liability for any disadvantages related to
 * the use of the plugins. Additionally, Wirecard AG does not guarantee the full functionality
 * for customized shop systems or installed plugins of other vendors of plugins within the same
 * shop system.
 *
 * Customers are responsible for testing the plugin's functionality before starting productive
 * operation.
 *
 * By installing the plugin into the shop system the customer agrees to these terms of use.
 * Please do not use the plugin if you do not agree to these terms of use!
 *
 * @author Wirecard AG
 * @copyright Wirecard AG
 * @license GPLv3
 */

namespace WirecardEE\Prestashop\Models;

use Wirecard\PaymentSdk\Config\PaymentMethodConfig;
use WirecardEE\Prestashop\Helper\AdditionalInformation;
use Wirecard\PaymentSdk\Transaction\CreditCardTransaction;
use Wirecard\PaymentSdk\Transaction\MasterpassTransaction;
use Wirecard\PaymentSdk\Entity\Amount;

/**
 * Class PaymentMasterpass
 *
 * @extends Payment
 *
 * @since 1.0.0
 */
class PaymentMasterpass extends Payment
{
    /**
     * PaymentiDEAL constructor.
     *
     * @since 1.0.0
     */
    public function __construct($module)
    {
        parent::__construct($module);

        $this->type = 'masterpass';
        $this->name = 'Wirecard Masterpass';
        $this->formFields = $this->createFormFields();

        $this->cancel = array('authorization');
        $this->capture = array('authorization');
        $this->refund = array('debit');
    }

    /**
     * Create form fields for iDEAL
     *
     * @return array|null
     * @since 1.0.0
     */
    public function createFormFields()
    {
        return array(
            'tab' => 'masterpass',
            'fields' => array(
                array(
                    'name' => 'enabled',
                    'label' => 'Enable',
                    'type' => 'onoff',
                    'doc' => $this->translate('master_enable_doc'),
                    'default' => 0,
                ),
                array(
                    'name' => 'title',
                    'label' => 'Title',
                    'type' => 'text',
                    'default' => $this->translate('master_title_doc'),
                    'required' => true,
                ),
                array(
                    'name' => 'merchant_account_id',
                    'label'   => $this->translate('merchant_id_doc'),
                    'type'    => 'text',
                    'default' => '8bc8ed6d-81a8-43be-bd7b-75b008f89fa6',
                    'required' => true,
                ),
                array(
                    'name' => 'secret',
                    'label'   => $this->translate('secret_key_doc'),
                    'type'    => 'text',
                    'default' => '2d96596b-9d10-4c98-ac47-4d56e22fd878',
                    'required' => true,
                ),
                array(
                    'name' => 'base_url',
                    'label'       => $this->translate('base_url_doc'),
                    'type'        => 'text',
                    'doc' => $this->translate('base_url_example_doc'),
                    'default'     => 'https://api-test.wirecard.com',
                    'required' => true,
                ),
                array(
                    'name' => 'http_user',
                    'label'   => $this->translate('http_user_doc'),
                    'type'    => 'text',
                    'default' => '70000-APITEST-AP',
                    'required' => true,
                ),
                array(
                    'name' => 'http_pass',
                    'label'   => $this->translate('http_pass_doc'),
                    'type'    => 'text',
                    'default' => 'qD2wzQ_hrc!8',
                    'required' => true,
                ),
                array(
                    'name' => 'payment_action',
                    'type'    => 'select',
                    'default' => 'authorization',
                    'label'   => $this->translate('payment_action_doc'),
                    'options' => array(
                        array('key' => 'reserve', 'value' => $this->translate('payment_action_auth_doc')),
                        array('key' => 'pay', 'value' => $this->translate('payment_action_capture_doc')),
                    ),
                ),
                array(
                    'name' => 'descriptor',
                    'label'   => $this->translate('descriptor_doc'),
                    'type'    => 'onoff',
                    'default' => 0,
                ),
                array(
                    'name' => 'send_additional',
                    'label'   => $this->translate('send_addit_info_doc'),
                    'type'    => 'onoff',
                    'default' => 1,
                ),
                array(
                    'name' => 'test_credentials',
                    'type' => 'linkbutton',
                    'required' => false,
                    'buttonText' => $this->translate('master_test_config_butoon_doc'),
                    'id' => 'masterpassConfig',
                    'method' => 'Masterpass',
                    'send' => array(
                        'WIRECARD_PAYMENT_GATEWAY_MASTERPASS_BASE_URL',
                        'WIRECARD_PAYMENT_GATEWAY_MASTERPASS_HTTP_USER',
                        'WIRECARD_PAYMENT_GATEWAY_MASTERPASS_HTTP_PASS'
                    )
                )
            )
        );
    }

    /**
     * Create config for Masterpass transactions
     *
     * @param \WirecardPaymentGateway $paymentModule
     * @return \Wirecard\PaymentSdk\Config\Config
     * @since 1.0.0
     */
    public function createPaymentConfig($paymentModule)
    {
        $baseUrl  = $paymentModule->getConfigValue($this->type, 'base_url');
        $httpUser = $paymentModule->getConfigValue($this->type, 'http_user');
        $httpPass = $paymentModule->getConfigValue($this->type, 'http_pass');

        $merchantAccountId = $paymentModule->getConfigValue($this->type, 'merchant_account_id');
        $secret = $paymentModule->getConfigValue($this->type, 'secret');

        $config = $this->createConfig($baseUrl, $httpUser, $httpPass);
        $paymentConfig = new PaymentMethodConfig(MasterpassTransaction::NAME, $merchantAccountId, $secret);
        $config->add($paymentConfig);

        return $config;
    }

    /**
     * Create Masterpass transaction
     *
     * @param \WirecardPaymentGateway $module
     * @param \Cart $cart
     * @param array $values
     * @param int $orderId
     * @return null|MasterpassTransaction
     * @since 1.0.0
     */
    public function createTransaction($module, $cart, $values, $orderId)
    {
        $transaction = new MasterpassTransaction();

        $additionalInformation = new AdditionalInformation();
        $transaction->setAccountHolder($additionalInformation->createAccountHolder($cart, 'billing'));

        return $transaction;
    }

    /**
     * Create cancel transaction
     *
     * @param $transactionData
     * @return MasterpassTransaction
     * @since 1.0.0
     */
    public function createCancelTransaction($transactionData)
    {
        $transaction = new MasterpassTransaction();
        $transaction->setParentTransactionId($transactionData->transaction_id);
        $transaction->setAmount(new Amount($transactionData->amount, $transactionData->currency));

        return $transaction;
    }

    /**
     * Create pay transaction
     *
     * @param Transaction $transactionData
     * @return MasterpassTransaction
     * @since 1.0.0
     */
    public function createPayTransaction($transactionData)
    {
        $transaction = new MasterpassTransaction();
        $transaction->setParentTransactionId($transactionData->transaction_id);
        $transaction->setAmount(new Amount($transactionData->amount, $transactionData->currency));

        return $transaction;
    }
}