<?php

/**
 * bootstrap.php for PEAR2_Net_Transmitter.
 * 
 * PHP version 5
 * 
 * @category  Net
 * @package   PEAR2_Net_Transmitter
 * @author    Vasil Rangelov <boen.robot@gmail.com>
 * @copyright 2011 Vasil Rangelov
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 * @version   GIT: $Id$
 * @link      http://pear2.php.net/PEAR2_Net_Transmitter
 */

/**
 * Possible autoloader to initialize.
 */
use PEAR2\Autoload;

chdir(__DIR__);

$vendorAutoload = dirname(__DIR__) . '/vendor/autoload.php';
if (file_exists($vendorAutoload)) {
    require_once $vendorAutoload;
} else {
    spl_autoload_register(function ($class) {
        $prefix = 'PEAR2\\Net\\Transmitter\\';
        $baseDir = dirname(__DIR__) . '/src/PEAR2/Net/Transmitter/';
        $len = strlen($prefix);
        if (strncmp($prefix, $class, $len) !== 0) {
            return;
        }
        $relativeClass = substr($class, $len);
        $file = $baseDir . str_replace('\\', '/', $relativeClass) . '.php';
        if (file_exists($file)) {
            require_once $file;
        }
    });
}

if (!class_exists('PHPUnit_Framework_TestCase') && class_exists('PHPUnit\\Framework\\TestCase')) {
    class_alias('PHPUnit\\Framework\\TestCase', 'PHPUnit_Framework_TestCase');
}

if (!defined('CERTIFICATE_FILE')) {
    define('CERTIFICATE_FILE', 'selfSigned');
}
if (!defined('REMOTE_HOSTNAME')) {
    define('REMOTE_HOSTNAME', '::1');
}
if (!defined('REMOTE_PORT')) {
    define('REMOTE_PORT', 6666);
}
if (!defined('LOCAL_HOSTNAME')) {
    define('LOCAL_HOSTNAME', '::1');
}
if (!defined('LOCAL_PORT')) {
    define('LOCAL_PORT', 6667);
}
if (!defined('SILENT_HOSTNAME')) {
    define('SILENT_HOSTNAME', '::1');
}
if (!defined('SILENT_PORT')) {
    define('SILENT_PORT', 6668);
}

if (extension_loaded('openssl')) {
    if (!is_file(__DIR__ . DIRECTORY_SEPARATOR . CERTIFICATE_FILE)) {
        //Prepare a self signed certificate
        $configargs = array();
        if (strpos(PHP_OS, 'WIN') === 0) {
            $phpbin = defined('PHP_BINARY')
                ? PHP_BINARY
                : getenv('PHP_PEAR_PHP_BIN');
            $configargs['config'] = dirname($phpbin) . '/extras/ssl/openssl.cnf';
        }

        $privkey = openssl_pkey_new($configargs);
        $cert = openssl_csr_sign(
            openssl_csr_new(
                array(
                    'countryName' => 'US',
                    'stateOrProvinceName' => 'IRRELEVANT',
                    'localityName' => 'IRRELEVANT',
                    'organizationName' => 'PEAR2',
                    'organizationalUnitName' => 'PEAR2',
                    'commonName' => 'IRRELEVANT',
                    'emailAddress' => 'IRRELEVANT@example.com'
                ),
                $privkey,
                $configargs
            ),
            null,
            $privkey,
            2,
            $configargs
        );

        $pem = array();
        openssl_x509_export($cert, $pem[0]);
        openssl_pkey_export($privkey, $pem[1], null, $configargs);

        openssl_pkcs12_export_to_file(
            $cert,
            __DIR__ . DIRECTORY_SEPARATOR . CERTIFICATE_FILE . '.pfx',
            $privkey,
            null
        );
        file_put_contents(
            __DIR__ . DIRECTORY_SEPARATOR . CERTIFICATE_FILE . '.cer',
            implode('', $pem)
        );
    }
}