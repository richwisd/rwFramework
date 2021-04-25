<?php
namespace Richwisd;
class Loader
{
    /**
     * 注册自动加载类的相关函数
     */
    public static function register()
    {
        spl_autoload_register(array('Loader', 'ossAutoload'));
        spl_autoload_register(array('Loader', 'appAutoload'));

        require_once FRAME_PATH .  'application.base.php';
    }

    /**
     * 自动加载框架以及应用程序的类库
     */
    public static function appAutoload($className)
    {
        if (defined('ENV') && ENV == 'test') {
            $filePath = APP_PATH;
        } else {
            if (strpos($className, 'Lib') !== false) {
                $filePath = NTX_PATH;
            } else {
                $filePath = APP_PATH;
            }
        }

        if (strpos($className, 'Lib\Paas') !== false) {
            $className = str_replace('\\Paas\\', '\\Paas\\' . ucfirst(RUN_ENV) . '\\', $className);
        }

        $fileName = $filePath . DIRECTORY_SEPARATOR . str_replace('\\', DIRECTORY_SEPARATOR, ucwords($className, '\\')) . '.php';

        //echo $fileName . PHP_EOL;

        if (is_file($fileName)) {
            require_once $fileName;
        } else {
            throw new \Exception($className . ' class file not exist, please check!', -1002);
        }
    }

    /**
     *自动加载OSS的类库
     */
    public static function ossAutoload($className)
    {
        $baseFile = OSS_SWOOLE_PHP7LIB . '/base/' . $className . '.class' . '.php';
        $logicFile = OSS_SWOOLE_PHP7LIB . '/logic/' . $className . '.class' . '.php';
        $oidbBaseFile = OSS_SWOOLE_PHP7LIB . '/logic/' . 'OIDBBase/' . $className . '.class' . '.php';
        $paasFile = OSS_SWOOLE_PHP7LIB . '/paas/' . $className . '.class.php';

        if (!class_exists($className)) {
            if (is_file($baseFile)) {
                require_once $baseFile;
            } else if (is_file($oidbBaseFile)) {
                require_once $oidbBaseFile;
            } else if (is_file($logicFile)) {
                require_once $logicFile;
            } else if (is_file($paasFile)) {
                require_once $paasFile;
            }
        }
    }
}
