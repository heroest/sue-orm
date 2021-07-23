<?php

// autoload_static.php @generated by Composer

namespace Composer\Autoload;

class ComposerStaticInitea23dd715e6367c4447145d2ad853e09
{
    public static $prefixLengthsPsr4 = array (
        'S' => 
        array (
            'Sue\\LaravelModel\\' => 17,
        ),
    );

    public static $prefixDirsPsr4 = array (
        'Sue\\LaravelModel\\' => 
        array (
            0 => __DIR__ . '/../..' . '/src',
        ),
    );

    public static $classMap = array (
        'Composer\\InstalledVersions' => __DIR__ . '/..' . '/composer/InstalledVersions.php',
    );

    public static function getInitializer(ClassLoader $loader)
    {
        return \Closure::bind(function () use ($loader) {
            $loader->prefixLengthsPsr4 = ComposerStaticInitea23dd715e6367c4447145d2ad853e09::$prefixLengthsPsr4;
            $loader->prefixDirsPsr4 = ComposerStaticInitea23dd715e6367c4447145d2ad853e09::$prefixDirsPsr4;
            $loader->classMap = ComposerStaticInitea23dd715e6367c4447145d2ad853e09::$classMap;

        }, null, ClassLoader::class);
    }
}
