<?php

// autoload_static.php @generated by Composer

namespace Composer\Autoload;

class ComposerStaticInit8e70ede8735471a3cdb13bedf36b9541
{
    public static $prefixLengthsPsr4 = array (
        'A' => 
        array (
            'Automattic\\WooCommerce\\' => 23,
        ),
    );

    public static $prefixDirsPsr4 = array (
        'Automattic\\WooCommerce\\' => 
        array (
            0 => __DIR__ . '/..' . '/automattic/woocommerce/src/WooCommerce',
        ),
    );

    public static function getInitializer(ClassLoader $loader)
    {
        return \Closure::bind(function () use ($loader) {
            $loader->prefixLengthsPsr4 = ComposerStaticInit8e70ede8735471a3cdb13bedf36b9541::$prefixLengthsPsr4;
            $loader->prefixDirsPsr4 = ComposerStaticInit8e70ede8735471a3cdb13bedf36b9541::$prefixDirsPsr4;

        }, null, ClassLoader::class);
    }
}
