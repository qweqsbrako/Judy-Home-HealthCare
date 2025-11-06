<?php

return [
    /**
     * Public Key From Paystack Dashboard
     */
    'publicKey' => env('PAYSTACK_PUBLIC_KEY'),

    /**
     * Secret Key From Paystack Dashboard
     */
    'secretKey' => env('PAYSTACK_SECRET_KEY'),

    /**
     * Paystack Payment URL
     */
    'paymentUrl' => env('PAYSTACK_PAYMENT_URL', 'https://api.paystack.co'),

    /**
     * Merchant Email
     */
    'merchantEmail' => env('MERCHANT_EMAIL'),

    /**
     * Supported Payment Channels
     * Options: card, bank, ussd, qr, mobile_money, bank_transfer, eft
     */
    'paymentChannels' => [
        'card',
        'bank',
        'ussd',
        'mobile_money',
        'bank_transfer',
    ],
];