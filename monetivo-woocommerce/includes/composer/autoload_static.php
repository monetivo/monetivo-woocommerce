<?php

// autoload_static.php @generated by Composer

namespace Composer\Autoload;

class ComposerStaticInit19ba5ea3dbdd93fb04468c2b1da06f38
{
    public static $prefixLengthsPsr4 = array (
        'M' => 
        array (
            'Monetivo\\' => 9,
        ),
    );

    public static $prefixDirsPsr4 = array (
        'Monetivo\\' => 
        array (
            0 => __DIR__ . '/..' . '/monetivo/monetivo-php/src',
        ),
    );

    public static function getInitializer(ClassLoader $loader)
    {
        return \Closure::bind(function () use ($loader) {
            $loader->prefixLengthsPsr4 = ComposerStaticInit19ba5ea3dbdd93fb04468c2b1da06f38::$prefixLengthsPsr4;
            $loader->prefixDirsPsr4 = ComposerStaticInit19ba5ea3dbdd93fb04468c2b1da06f38::$prefixDirsPsr4;

        }, null, ClassLoader::class);
    }
}
