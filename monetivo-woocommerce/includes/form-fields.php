<?php

return array(
    'enabled' => array(
        'title' => __( 'Włącz/Wyłącz', 'monetivo' ),
        'type' => 'checkbox',
        'label' => __( 'Aktywuj moduł płatności monetivo.', 'monetivo' ),
        'default' => 'no'),
    'title' => array(
        'title' => __( 'Tytuł:', 'woocommerce' ),
        'type' => 'text',
        'description' => __( 'Tekst który zobaczą klienci podczas dokonywania zakupu', 'monetivo' ),
        'default' => __( 'Szybka płatność online monetivo', 'woocommerce' )),
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
    'mvo_app_token' => array(
        'title' => __( 'Token aplikacji', 'monetivo' ),
        'type' => 'text',
        'description' => __( 'Token aplikacji nadany w systemie monetivo.', 'monetivo' ),
        'placeholder' => __( '(wymagany)', 'monetivo' ),
        'required' => true),
);