<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'mailgun' => [
        'domain' => env('MAILGUN_DOMAIN'),
        'secret' => env('MAILGUN_SECRET'),
        'endpoint' => env('MAILGUN_ENDPOINT', 'api.mailgun.net'),
        'scheme' => 'https',
    ],

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    /*
     * Cloudflare Turnstile — captcha gratuito e ilimitado.
     * Painel: https://dash.cloudflare.com → Turnstile
     *
     * Se 'enabled' estiver false, a validação é ignorada. Use assim só em
     * desenvolvimento local e em testes automatizados. Em produção, deixe
     * ligado: é a primeira barreira contra bot.
     */
    'turnstile' => [
        'enabled' => env('TURNSTILE_ENABLED', true),
        'site_key' => env('TURNSTILE_SITE_KEY'),
        'secret' => env('TURNSTILE_SECRET'),
    ],

    /*
     * FaucetPay — microcarteira que faz os saques.
     * Cadastro do faucet e geração da chave: https://faucetpay.io/page/faucet-admin
     *
     * Configure também, NO PAINEL DELES, um teto diário em dólar na chave.
     * É a segunda trava: se a nossa falhar, a deles segura.
     */
    'faucetpay' => [
        'api_key' => env('FAUCETPAY_API_KEY'),
    ],

];
