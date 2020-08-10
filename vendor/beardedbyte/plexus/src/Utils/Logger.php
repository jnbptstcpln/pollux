<?php
/**
 * Created by PhpStorm.
 * User: jeanbaptistecaplan
 * Date: 23/01/2019
 * Time: 20:38
 */

namespace Plexus\Utils;


class Logger {

    /**
     * @param mixed $data
     * @param null $identifier
     * @param string $log_dirpath
     */
    static public function log($data, $log_dirpath, $identifier=null) {

        if ($data instanceof \Throwable) {
            Logger::logThrowable($data, $log_dirpath, $identifier);
            return;
        }

        if ($identifier !== null && strlen($identifier) > 0 && $identifier != 'application') {
            static::_log($data, $log_dirpath, $identifier);
        }
        static::_log($data, 'application', $log_dirpath);
    }

    /**
     * @param $data
     * @param $identifier
     * @param $log_dirpath
     */
    static private function _log($data, $identifier, $log_dirpath) {
        if (!is_dir($log_dirpath)) {
            mkdir($log_dirpath);
        }

        if (is_array($data) || is_object($data)) {
            $data = json_encode($data);
        }

        $filepath = Path::build($log_dirpath, $identifier.'.log');
        $line = '['.date('d-M-Y H:i:s e').'] ['.$_SERVER['REQUEST_METHOD'].' '.$_SERVER['REQUEST_URI'].'] '.strval($data);
        file_put_contents($filepath,  $line.PHP_EOL, FILE_APPEND);
        chmod($filepath, 0777);
    }

    /**
     * @param $message
     * @param null $identifier
     * @param string $log_dirpath
     */
    static public function logMessage($message, $log_dirpath, $identifier=null) {
        Logger::log($message, $log_dirpath, $identifier);
    }

    /**
     * @param \Throwable $e
     * @param null $identifier
     * @param string $log_dirpath
     */
    static public function logThrowable(\Throwable $e, $log_dirpath, $identifier=null) {
        $data = "An error occured :";
        $acc = 1;
        while ($e !== null) {
            $indent = str_repeat("\t", $acc);
            $data .= PHP_EOL;
            $data .= Text::format("{}{}: {} in {}:{}{}{}",
                $indent,
                get_class($e),
                $e->getMessage(),
                $e->getFile(),
                $e->getLine(),
                PHP_EOL.$indent,
                str_replace(PHP_EOL, PHP_EOL.$indent, $e->getTraceAsString())
            );
            $acc += 1;
            $e = $e->getPrevious();
        }
        Logger::log($data, $log_dirpath, $identifier);
    }

}