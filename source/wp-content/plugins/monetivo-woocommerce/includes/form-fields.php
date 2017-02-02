<?php

return array(
    'enabled' => array(
        'title' => __( 'Włącz/Wyłącz', 'monetivo' ),
        'type' => 'checkbox',
        'label' => __( 'Aktywuj moduł płatności Monetivo.', 'monetivo' ),
        'default' => 'no'),
    'title' => array(
        'title' => __( 'Tytuł:', 'woocommerce' ),
        'type' => 'text',
        'description' => __( 'Tekst który zobaczą klienci podczas dokonywania zakupu', 'monetivo' ),
        'default' => __( 'Monetivo', 'woocommerce' )),
    'mvo_app_token' => array(
        'title' => __( 'Token aplikacji', 'monetivo' ),
        'type' => 'text',
        'description' => __( 'Token aplikacji nadany w systemie Monetivo.', 'monetivo' ),
        'placeholder' => __( '(wymagany)', 'monetivo' ),
        'required' => true),
    'mvo_login' => array(
        'title' => __( 'Login użytkownika', 'monetivo' ),
        'type' => 'text',
        'description' => __( 'Login użytkownika integracji.', 'monetivo' ),
        'placeholder' => __( '(wymagany)', 'monetivo' ),
        'required' => true),
    'mvo_password' => array(
        'title' => __( 'Hasło', 'monetivo' ),
        'type' => 'password',
        'description' => __( 'Hasło użytkownika integracji.', 'monetivo' ),
        'required' => true),
);