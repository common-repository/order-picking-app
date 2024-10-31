<?php

// autoload_real.php @generated by Composer

class ComposerAutoloaderInit6736277f9aafe4a566fb2a51d9ff4077
{
    private static $loader;

    public static function loadClassLoader($class)
    {
        if ('Composer\Autoload\ClassLoader' === $class) {
            require __DIR__ . '/ClassLoader.php';
        }
    }

    /**
     * @return \Composer\Autoload\ClassLoader
     */
    public static function getLoader()
    {
        if (null !== self::$loader) {
            return self::$loader;
        }

        require __DIR__ . '/platform_check.php';

        spl_autoload_register(array('ComposerAutoloaderInit6736277f9aafe4a566fb2a51d9ff4077', 'loadClassLoader'), true, true);
        self::$loader = $loader = new \Composer\Autoload\ClassLoader(\dirname(__DIR__));
        spl_autoload_unregister(array('ComposerAutoloaderInit6736277f9aafe4a566fb2a51d9ff4077', 'loadClassLoader'));

        require __DIR__ . '/autoload_static.php';
        call_user_func(\Composer\Autoload\ComposerStaticInit6736277f9aafe4a566fb2a51d9ff4077::getInitializer($loader));

        $loader->register(true);

        return $loader;
    }
}