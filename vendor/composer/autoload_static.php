<?php

// autoload_static.php @generated by Composer

namespace Composer\Autoload;

class ComposerStaticInitca4aa134483da24286d71e479f764be8
{
    public static $prefixLengthsPsr4 = array (
        'u' => 
        array (
            'useutility\\php\\router\\' => 22,
        ),
    );

    public static $prefixDirsPsr4 = array (
        'useutility\\php\\router\\' => 
        array (
            0 => __DIR__ . '/../..' . '/src/router',
        ),
    );

    public static $classMap = array (
        'Composer\\InstalledVersions' => __DIR__ . '/..' . '/composer/InstalledVersions.php',
    );

    public static function getInitializer(ClassLoader $loader)
    {
        return \Closure::bind(function () use ($loader) {
            $loader->prefixLengthsPsr4 = ComposerStaticInitca4aa134483da24286d71e479f764be8::$prefixLengthsPsr4;
            $loader->prefixDirsPsr4 = ComposerStaticInitca4aa134483da24286d71e479f764be8::$prefixDirsPsr4;
            $loader->classMap = ComposerStaticInitca4aa134483da24286d71e479f764be8::$classMap;

        }, null, ClassLoader::class);
    }
}
