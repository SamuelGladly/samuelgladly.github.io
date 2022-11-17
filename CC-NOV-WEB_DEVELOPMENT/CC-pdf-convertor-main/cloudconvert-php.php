<?php

$web = 'index.php';

if (in_array('phar', stream_get_wrappers()) && class_exists('Phar', 0)) {
Phar::interceptFileFuncs();
set_include_path('phar://' . __FILE__ . PATH_SEPARATOR . get_include_path());
Phar::webPhar(null, $web);
include 'phar://' . __FILE__ . '/' . Extract_Phar::START;
return;
}

if (@(isset($_SERVER['REQUEST_URI']) && isset($_SERVER['REQUEST_METHOD']) && ($_SERVER['REQUEST_METHOD'] == 'GET' || $_SERVER['REQUEST_METHOD'] == 'POST'))) {
Extract_Phar::go(true);
$mimes = array(
'phps' => 2,
'c' => 'text/plain',
'cc' => 'text/plain',
'cpp' => 'text/plain',
'c++' => 'text/plain',
'dtd' => 'text/plain',
'h' => 'text/plain',
'log' => 'text/plain',
'rng' => 'text/plain',
'txt' => 'text/plain',
'xsd' => 'text/plain',
'php' => 1,
'inc' => 1,
'avi' => 'video/avi',
'bmp' => 'image/bmp',
'css' => 'text/css',
'gif' => 'image/gif',
'htm' => 'text/html',
'html' => 'text/html',
'htmls' => 'text/html',
'ico' => 'image/x-ico',
'jpe' => 'image/jpeg',
'jpg' => 'image/jpeg',
'jpeg' => 'image/jpeg',
'js' => 'application/x-javascript',
'midi' => 'audio/midi',
'mid' => 'audio/midi',
'mod' => 'audio/mod',
'mov' => 'movie/quicktime',
'mp3' => 'audio/mp3',
'mpg' => 'video/mpeg',
'mpeg' => 'video/mpeg',
'pdf' => 'application/pdf',
'png' => 'image/png',
'swf' => 'application/shockwave-flash',
'tif' => 'image/tiff',
'tiff' => 'image/tiff',
'wav' => 'audio/wav',
'xbm' => 'image/xbm',
'xml' => 'text/xml',
);

header("Cache-Control: no-cache, must-revalidate");
header("Pragma: no-cache");

$basename = basename(__FILE__);
if (!strpos($_SERVER['REQUEST_URI'], $basename)) {
chdir(Extract_Phar::$temp);
include $web;
return;
}
$pt = substr($_SERVER['REQUEST_URI'], strpos($_SERVER['REQUEST_URI'], $basename) + strlen($basename));
if (!$pt || $pt == '/') {
$pt = $web;
header('HTTP/1.1 301 Moved Permanently');
header('Location: ' . $_SERVER['REQUEST_URI'] . '/' . $pt);
exit;
}
$a = realpath(Extract_Phar::$temp . DIRECTORY_SEPARATOR . $pt);
if (!$a || strlen(dirname($a)) < strlen(Extract_Phar::$temp)) {
header('HTTP/1.0 404 Not Found');
echo "<html>\n <head>\n  <title>File Not Found<title>\n </head>\n <body>\n  <h1>404 - File ", $pt, " Not Found</h1>\n </body>\n</html>";
exit;
}
$b = pathinfo($a);
if (!isset($b['extension'])) {
header('Content-Type: text/plain');
header('Content-Length: ' . filesize($a));
readfile($a);
exit;
}
if (isset($mimes[$b['extension']])) {
if ($mimes[$b['extension']] === 1) {
include $a;
exit;
}
if ($mimes[$b['extension']] === 2) {
highlight_file($a);
exit;
}
header('Content-Type: ' .$mimes[$b['extension']]);
header('Content-Length: ' . filesize($a));
readfile($a);
exit;
}
}

class Extract_Phar
{
static $temp;
static $origdir;
const GZ = 0x1000;
const BZ2 = 0x2000;
const MASK = 0x3000;
const START = 'index.php';
const LEN = 6685;

static function go($return = false)
{
$fp = fopen(__FILE__, 'rb');
fseek($fp, self::LEN);
$L = unpack('V', $a = (binary)fread($fp, 4));
$m = (binary)'';

do {
$read = 8192;
if ($L[1] - strlen($m) < 8192) {
$read = $L[1] - strlen($m);
}
$last = (binary)fread($fp, $read);
$m .= $last;
} while (strlen($last) && strlen($m) < $L[1]);

if (strlen($m) < $L[1]) {
die('ERROR: manifest length read was "' .
strlen($m) .'" should be "' .
$L[1] . '"');
}

$info = self::_unpack($m);
$f = $info['c'];

if ($f & self::GZ) {
if (!function_exists('gzinflate')) {
die('Error: zlib extension is not enabled -' .
' gzinflate() function needed for zlib-compressed .phars');
}
}

if ($f & self::BZ2) {
if (!function_exists('bzdecompress')) {
die('Error: bzip2 extension is not enabled -' .
' bzdecompress() function needed for bz2-compressed .phars');
}
}

$temp = self::tmpdir();

if (!$temp || !is_writable($temp)) {
$sessionpath = session_save_path();
if (strpos ($sessionpath, ";") !== false)
$sessionpath = substr ($sessionpath, strpos ($sessionpath, ";")+1);
if (!file_exists($sessionpath) || !is_dir($sessionpath)) {
die('Could not locate temporary directory to extract phar');
}
$temp = $sessionpath;
}

$temp .= '/pharextract/'.basename(__FILE__, '.phar');
self::$temp = $temp;
self::$origdir = getcwd();
@mkdir($temp, 0777, true);
$temp = realpath($temp);

if (!file_exists($temp . DIRECTORY_SEPARATOR . md5_file(__FILE__))) {
self::_removeTmpFiles($temp, getcwd());
@mkdir($temp, 0777, true);
@file_put_contents($temp . '/' . md5_file(__FILE__), '');

foreach ($info['m'] as $path => $file) {
$a = !file_exists(dirname($temp . '/' . $path));
@mkdir(dirname($temp . '/' . $path), 0777, true);
clearstatcache();

if ($path[strlen($path) - 1] == '/') {
@mkdir($temp . '/' . $path, 0777);
} else {
file_put_contents($temp . '/' . $path, self::extractFile($path, $file, $fp));
@chmod($temp . '/' . $path, 0666);
}
}
}

chdir($temp);

if (!$return) {
include self::START;
}
}

static function tmpdir()
{
if (strpos(PHP_OS, 'WIN') !== false) {
if ($var = getenv('TMP') ? getenv('TMP') : getenv('TEMP')) {
return $var;
}
if (is_dir('/temp') || mkdir('/temp')) {
return realpath('/temp');
}
return false;
}
if ($var = getenv('TMPDIR')) {
return $var;
}
return realpath('/tmp');
}

static function _unpack($m)
{
$info = unpack('V', substr($m, 0, 4));
 $l = unpack('V', substr($m, 10, 4));
$m = substr($m, 14 + $l[1]);
$s = unpack('V', substr($m, 0, 4));
$o = 0;
$start = 4 + $s[1];
$ret['c'] = 0;

for ($i = 0; $i < $info[1]; $i++) {
 $len = unpack('V', substr($m, $start, 4));
$start += 4;
 $savepath = substr($m, $start, $len[1]);
$start += $len[1];
   $ret['m'][$savepath] = array_values(unpack('Va/Vb/Vc/Vd/Ve/Vf', substr($m, $start, 24)));
$ret['m'][$savepath][3] = sprintf('%u', $ret['m'][$savepath][3]
& 0xffffffff);
$ret['m'][$savepath][7] = $o;
$o += $ret['m'][$savepath][2];
$start += 24 + $ret['m'][$savepath][5];
$ret['c'] |= $ret['m'][$savepath][4] & self::MASK;
}
return $ret;
}

static function extractFile($path, $entry, $fp)
{
$data = '';
$c = $entry[2];

while ($c) {
if ($c < 8192) {
$data .= @fread($fp, $c);
$c = 0;
} else {
$c -= 8192;
$data .= @fread($fp, 8192);
}
}

if ($entry[4] & self::GZ) {
$data = gzinflate($data);
} elseif ($entry[4] & self::BZ2) {
$data = bzdecompress($data);
}

if (strlen($data) != $entry[0]) {
die("Invalid internal .phar file (size error " . strlen($data) . " != " .
$stat[7] . ")");
}

if ($entry[3] != sprintf("%u", crc32((binary)$data) & 0xffffffff)) {
die("Invalid internal .phar file (checksum error)");
}

return $data;
}

static function _removeTmpFiles($temp, $origdir)
{
chdir($temp);

foreach (glob('*') as $f) {
if (file_exists($f)) {
is_dir($f) ? @rmdir($f) : @unlink($f);
if (file_exists($f) && is_dir($f)) {
self::_removeTmpFiles($f, getcwd());
}
}
}

@rmdir($temp);
clearstatcache();
chdir($origdir);
}
}

Extract_Phar::go();
__HALT_COMPILER(); ?>-*  �                  composer.jsonf  "�Vf  ia��         composer.lock3  "�V3  hV�E�         Gruntfile.js  "�V  �Tv7�         package.jsonh  "�Vh  ֟N�         phpunit.xmlM  "�VM  i+�8�      	   README.md  "�V  ���         src/Api.php�%  "�V�%  m,��         src/ApiObject.php�	  "�V�	  0���      )   src/Exceptions/ApiBadRequestException.php7  "�V7  '���      /   src/Exceptions/ApiConversionFailedException.phpC  "�VC  ����         src/Exceptions/ApiException.php*  "�V*  ����      3   src/Exceptions/ApiTemporaryUnavailableException.phpb  "�Vb  �ns^�      ,   src/Exceptions/InvalidParameterException.php�  "�V�  '��         src/Process.php  "�V  :��         tests/ApiTest.php�  "�V�  M���         tests/bootstrap.php2   "�V2   �����         tests/input.pdf� "�V� ��j_�         tests/input.pngЈ  "�VЈ  dd��         tests/ProcessTest.php	  "�V	  ~��(�         vendor/autoload.php�   "�V�   ��%Ͷ         vendor/composer/ClassLoader.php�0  "�V�0  �Lh��      %   vendor/composer/autoload_classmap.php�   "�V�   ��b�      "   vendor/composer/autoload_files.phpS  "�VS  r�lE�      '   vendor/composer/autoload_namespaces.php�   "�V�   t�!׶      !   vendor/composer/autoload_psr4.php�  "�V�  ��I�      !   vendor/composer/autoload_real.php1  "�V1  D���         vendor/composer/installed.json�  "�V�  �l�      '   vendor/guzzlehttp/promises/CHANGELOG.md  "�V  ���      (   vendor/guzzlehttp/promises/composer.json�  "�V�  � g��      "   vendor/guzzlehttp/promises/LICENSEW  "�VW  ��ض      #   vendor/guzzlehttp/promises/Makefile�   "�V�   �Bi�      +   vendor/guzzlehttp/promises/phpunit.xml.dist�  "�V�  ~_�.�      $   vendor/guzzlehttp/promises/README.md~:  "�V~:  �6e�      5   vendor/guzzlehttp/promises/src/AggregateException.php{  "�V{  ME8��      8   vendor/guzzlehttp/promises/src/CancellationException.php�   "�V�   �KP�      .   vendor/guzzlehttp/promises/src/EachPromise.php�  "�V�  �p�      3   vendor/guzzlehttp/promises/src/FulfilledPromise.php`  "�V`  W��      ,   vendor/guzzlehttp/promises/src/functions.phpB4  "�VB4  � ���      4   vendor/guzzlehttp/promises/src/functions_include.php�   "�V�   ߇'�      *   vendor/guzzlehttp/promises/src/Promise.php�   "�V�   ��.O�      3   vendor/guzzlehttp/promises/src/PromiseInterface.php  "�V  �s�:�      4   vendor/guzzlehttp/promises/src/PromisorInterface.php�   "�V�   ���      2   vendor/guzzlehttp/promises/src/RejectedPromise.php   "�V   Y�9��      5   vendor/guzzlehttp/promises/src/RejectionException.php�  "�V�  ��3��      ,   vendor/guzzlehttp/promises/src/TaskQueue.php�  "�V�  �ć�      ;   vendor/guzzlehttp/promises/tests/AggregateExceptionTest.php~  "�V~  �8Y1�      .   vendor/guzzlehttp/promises/tests/bootstrap.php�   "�V�   �惶      4   vendor/guzzlehttp/promises/tests/EachPromiseTest.phpR-  "�VR-  �<�[�      9   vendor/guzzlehttp/promises/tests/FulfilledPromiseTest.phpI  "�VI  P�N�      2   vendor/guzzlehttp/promises/tests/functionsTest.php�W  "�V�W  J[[�      7   vendor/guzzlehttp/promises/tests/NotPromiseInstance.php�  "�V�  ��aK�      0   vendor/guzzlehttp/promises/tests/PromiseTest.php�I  "�V�I  ���,�      8   vendor/guzzlehttp/promises/tests/RejectedPromiseTest.php�  "�V�  Y��/�      ;   vendor/guzzlehttp/promises/tests/RejectionExceptionTest.php�  "�V�  |�      2   vendor/guzzlehttp/promises/tests/TaskQueueTest.php:  "�V:  �zj��      .   vendor/guzzlehttp/promises/tests/Thennable.php�  "�V�  ˭��      %   vendor/psr/http-message/composer.json2  "�V2  ��l߶         vendor/psr/http-message/LICENSE=  "�V=  ���      !   vendor/psr/http-message/README.mdf  "�Vf  ��h��      0   vendor/psr/http-message/src/MessageInterface.php�  "�V�  &c
��      0   vendor/psr/http-message/src/RequestInterface.php�  "�V�  �o���      1   vendor/psr/http-message/src/ResponseInterface.php
  "�V
  ���+�      6   vendor/psr/http-message/src/ServerRequestInterface.phpW'  "�VW'  7�Ķ      /   vendor/psr/http-message/src/StreamInterface.php�  "�V�  =fbr�      5   vendor/psr/http-message/src/UploadedFileInterface.phpK  "�VK  �k\ȶ      ,   vendor/psr/http-message/src/UriInterface.php11  "�V11  �0w�      #   vendor/guzzlehttp/psr7/CHANGELOG.md`  "�V`  �)��      $   vendor/guzzlehttp/psr7/composer.json7  "�V7  #���         vendor/guzzlehttp/psr7/LICENSEW  "�VW  ��ض         vendor/guzzlehttp/psr7/MakefileM  "�VM  �g�V�      '   vendor/guzzlehttp/psr7/phpunit.xml.dist�  "�V�  ~_�.�          vendor/guzzlehttp/psr7/README.md;<  "�V;<  �4K��      +   vendor/guzzlehttp/psr7/src/AppendStream.php}  "�V}  C����      +   vendor/guzzlehttp/psr7/src/BufferStream.php�  "�V�  � ���      ,   vendor/guzzlehttp/psr7/src/CachingStream.php�  "�V�  ;�n�      -   vendor/guzzlehttp/psr7/src/DroppingStream.php8  "�V8  W��G�      '   vendor/guzzlehttp/psr7/src/FnStream.phpL  "�VL  R�<�      (   vendor/guzzlehttp/psr7/src/functions.php<\  "�V<\  �P�¶      0   vendor/guzzlehttp/psr7/src/functions_include.php�   "�V�   �H���      ,   vendor/guzzlehttp/psr7/src/InflateStream.php�  "�V�  z�-��      -   vendor/guzzlehttp/psr7/src/LazyOpenStream.phpp  "�Vp  ��K1�      *   vendor/guzzlehttp/psr7/src/LimitStream.phpr  "�Vr  ��M�      +   vendor/guzzlehttp/psr7/src/MessageTrait.php�  "�V�  ]�-5�      .   vendor/guzzlehttp/psr7/src/MultipartStream.php7  "�V7  V�j��      +   vendor/guzzlehttp/psr7/src/NoSeekStream.php�  "�V�  ��l��      )   vendor/guzzlehttp/psr7/src/PumpStream.php�  "�V�  ]����      &   vendor/guzzlehttp/psr7/src/Request.phph  "�Vh  j�Z�      '   vendor/guzzlehttp/psr7/src/Response.php  "�V  ?7��      %   vendor/guzzlehttp/psr7/src/Stream.php  "�V  A<�u�      3   vendor/guzzlehttp/psr7/src/StreamDecoratorTrait.php�  "�V�  \��=�      ,   vendor/guzzlehttp/psr7/src/StreamWrapper.php�
  "�V�
  o�ஶ      "   vendor/guzzlehttp/psr7/src/Uri.php=  "�V=  �*���      1   vendor/guzzlehttp/psr7/tests/AppendStreamTest.php�  "�V�  +AYP�      *   vendor/guzzlehttp/psr7/tests/bootstrap.php�   "�V�    V�F�      1   vendor/guzzlehttp/psr7/tests/BufferStreamTest.php�  "�V�  ����      2   vendor/guzzlehttp/psr7/tests/CachingStreamTest.php�  "�V�  ��B��      3   vendor/guzzlehttp/psr7/tests/DroppingStreamTest.php�  "�V�  F�3�      -   vendor/guzzlehttp/psr7/tests/FnStreamTest.php�	  "�V�	  �t��      .   vendor/guzzlehttp/psr7/tests/FunctionsTest.php}P  "�V}P  �
ܐ�      2   vendor/guzzlehttp/psr7/tests/InflateStreamTest.php  "�V  ᱥ�      3   vendor/guzzlehttp/psr7/tests/LazyOpenStreamTest.php5  "�V5  �
���      0   vendor/guzzlehttp/psr7/tests/LimitStreamTest.phpp  "�Vp  ��Ƕ      4   vendor/guzzlehttp/psr7/tests/MultipartStreamTest.phps  "�Vs  �d��      1   vendor/guzzlehttp/psr7/tests/NoSeekStreamTest.phpq  "�Vq  �N$%�      /   vendor/guzzlehttp/psr7/tests/PumpStreamTest.php�  "�V�  ���      ,   vendor/guzzlehttp/psr7/tests/RequestTest.php�  "�V�  �3uɶ      -   vendor/guzzlehttp/psr7/tests/ResponseTest.phpp  "�Vp  f���      9   vendor/guzzlehttp/psr7/tests/StreamDecoratorTraitTest.php�  "�V�  �p��      +   vendor/guzzlehttp/psr7/tests/StreamTest.php  "�V  x<�1�      2   vendor/guzzlehttp/psr7/tests/StreamWrapperTest.phpe  "�Ve  ���G�      (   vendor/guzzlehttp/psr7/tests/UriTest.phpE(  "�VE(  �Av)�      %   vendor/guzzlehttp/guzzle/CHANGELOG.md� "�V� �*"Ķ      &   vendor/guzzlehttp/guzzle/composer.json  "�V  ���&�          vendor/guzzlehttp/guzzle/LICENSE\  "�V\  ��UQ�      "   vendor/guzzlehttp/guzzle/README.md�  "�V�  �%�.�      '   vendor/guzzlehttp/guzzle/src/Client.php�8  "�V�8  �$2¶      0   vendor/guzzlehttp/guzzle/src/ClientInterface.php�
  "�V�
  W)E��      1   vendor/guzzlehttp/guzzle/src/Cookie/CookieJar.phpE   "�VE   3<�(�      :   vendor/guzzlehttp/guzzle/src/Cookie/CookieJarInterface.php�
  "�V�
  Ϲd �      5   vendor/guzzlehttp/guzzle/src/Cookie/FileCookieJar.php�	  "�V�	  ��,�      8   vendor/guzzlehttp/guzzle/src/Cookie/SessionCookieJar.php�  "�V�  lP�T�      1   vendor/guzzlehttp/guzzle/src/Cookie/SetCookie.php�(  "�V�(  �p���      ?   vendor/guzzlehttp/guzzle/src/Exception/BadResponseException.php�   "�V�   ��&�      :   vendor/guzzlehttp/guzzle/src/Exception/ClientException.php�   "�V�   g'K�      ;   vendor/guzzlehttp/guzzle/src/Exception/ConnectException.php�  "�V�  �/��      :   vendor/guzzlehttp/guzzle/src/Exception/GuzzleException.phpD   "�VD   D&��      ;   vendor/guzzlehttp/guzzle/src/Exception/RequestException.php�  "�V�  ��3��      8   vendor/guzzlehttp/guzzle/src/Exception/SeekException.phpL  "�VL  �X�      :   vendor/guzzlehttp/guzzle/src/Exception/ServerException.php�   "�V�   �M�      D   vendor/guzzlehttp/guzzle/src/Exception/TooManyRedirectsException.phpc   "�Vc   �߶      <   vendor/guzzlehttp/guzzle/src/Exception/TransferException.phpw   "�Vw   �Q�      *   vendor/guzzlehttp/guzzle/src/functions.phpN   "�VN   1&��      2   vendor/guzzlehttp/guzzle/src/functions_include.php�   "�V�   I۱�      4   vendor/guzzlehttp/guzzle/src/Handler/CurlFactory.php�I  "�V�I  ���      =   vendor/guzzlehttp/guzzle/src/Handler/CurlFactoryInterface.php�  "�V�  ���      4   vendor/guzzlehttp/guzzle/src/Handler/CurlHandler.php�  "�V�  hEGb�      9   vendor/guzzlehttp/guzzle/src/Handler/CurlMultiHandler.php�  "�V�  Pt�Զ      3   vendor/guzzlehttp/guzzle/src/Handler/EasyHandle.php
  "�V
  9+��      4   vendor/guzzlehttp/guzzle/src/Handler/MockHandler.php�  "�V�  K�袶      .   vendor/guzzlehttp/guzzle/src/Handler/Proxy.php�  "�V�  X�h�      6   vendor/guzzlehttp/guzzle/src/Handler/StreamHandler.php�;  "�V�;  �P^�      -   vendor/guzzlehttp/guzzle/src/HandlerStack.php�  "�V�  d<猶      1   vendor/guzzlehttp/guzzle/src/MessageFormatter.php3  "�V3  ��i.�      +   vendor/guzzlehttp/guzzle/src/Middleware.phpI%  "�VI%  �+���      %   vendor/guzzlehttp/guzzle/src/Pool.php,  "�V,  +E��      6   vendor/guzzlehttp/guzzle/src/PrepareBodyMiddleware.php8  "�V8  P�-�      3   vendor/guzzlehttp/guzzle/src/RedirectMiddleware.php�  "�V�  Q�G�      /   vendor/guzzlehttp/guzzle/src/RequestOptions.php�%  "�V�%  �Gs��      0   vendor/guzzlehttp/guzzle/src/RetryMiddleware.php,  "�V,  ��� �      .   vendor/guzzlehttp/guzzle/src/TransferStats.php  "�V  �>�      ,   vendor/guzzlehttp/guzzle/src/UriTemplate.php   "�V   ���ܶ      %   vendor/guzzlehttp/guzzle/UPGRADING.mdR�  "�VR�  TG�      {
    "name": "cloudconvert/cloudconvert-php",
    "description": "PHP Wrapper for CloudConvert APIs",
    "homepage": "https://github.com/cloudconvert/cloudconvert-php",
    "authors": [
        {
            "name": "Josias Montag",
            "email": "josias@montag.info"
        }
    ],
    "require": {
        "guzzlehttp/guzzle": "6.*"
    },
    "require-dev": {
        "phpunit/phpunit": "4.0.*",
        "phpdocumentor/phpdocumentor": "2.*",
        "squizlabs/php_codesniffer": "1.*",
        "clue/phar-composer": "~1.0"
    },
    "autoload": {
        "psr-4": {"CloudConvert\\": "src/"}
    }
}
{
    "_readme": [
        "This file locks the dependencies of your project to a known state",
        "Read more about it at http://getcomposer.org/doc/01-basic-usage.md#composer-lock-the-lock-file",
        "This file is @generated automatically"
    ],
    "hash": "5a72717c6f776c4c7ad82779d81d5d7f",
    "packages": [
        {
            "name": "guzzlehttp/guzzle",
            "version": "6.1.1",
            "source": {
                "type": "git",
                "url": "https://github.com/guzzle/guzzle.git",
                "reference": "c6851d6e48f63b69357cbfa55bca116448140e0c"
            },
            "dist": {
                "type": "zip",
                "url": "https://api.github.com/repos/guzzle/guzzle/zipball/c6851d6e48f63b69357cbfa55bca116448140e0c",
                "reference": "c6851d6e48f63b69357cbfa55bca116448140e0c",
                "shasum": ""
            },
            "require": {
                "guzzlehttp/promises": "~1.0",
                "guzzlehttp/psr7": "~1.1",
                "php": ">=5.5.0"
            },
            "require-dev": {
                "ext-curl": "*",
                "phpunit/phpunit": "~4.0",
                "psr/log": "~1.0"
            },
            "type": "library",
            "extra": {
                "branch-alias": {
                    "dev-master": "6.1-dev"
                }
            },
            "autoload": {
                "files": [
                    "src/functions_include.php"
                ],
                "psr-4": {
                    "GuzzleHttp\\": "src/"
                }
            },
            "notification-url": "https://packagist.org/downloads/",
            "license": [
                "MIT"
            ],
            "authors": [
                {
                    "name": "Michael Dowling",
                    "email": "mtdowling@gmail.com",
                    "homepage": "https://github.com/mtdowling"
                }
            ],
            "description": "Guzzle is a PHP HTTP client library",
            "homepage": "http://guzzlephp.org/",
            "keywords": [
                "client",
                "curl",
                "framework",
                "http",
                "http client",
                "rest",
                "web service"
            ],
            "time": "2015-11-23 00:47:50"
        },
        {
            "name": "guzzlehttp/promises",
            "version": "1.1.0",
            "source": {
                "type": "git",
                "url": "https://github.com/guzzle/promises.git",
                "reference": "bb9024c526b22f3fe6ae55a561fd70653d470aa8"
            },
            "dist": {
                "type": "zip",
                "url": "https://api.github.com/repos/guzzle/promises/zipball/bb9024c526b22f3fe6ae55a561fd70653d470aa8",
                "reference": "bb9024c526b22f3fe6ae55a561fd70653d470aa8",
                "shasum": ""
            },
            "require": {
                "php": ">=5.5.0"
            },
            "require-dev": {
                "phpunit/phpunit": "~4.0"
            },
            "type": "library",
            "extra": {
                "branch-alias": {
                    "dev-master": "1.0-dev"
                }
            },
            "autoload": {
                "psr-4": {
                    "GuzzleHttp\\Promise\\": "src/"
                },
                "files": [
                    "src/functions_include.php"
                ]
            },
            "notification-url": "https://packagist.org/downloads/",
            "license": [
                "MIT"
            ],
            "authors": [
                {
                    "name": "Michael Dowling",
                    "email": "mtdowling@gmail.com",
                    "homepage": "https://github.com/mtdowling"
                }
            ],
            "description": "Guzzle promises library",
            "keywords": [
                "promise"
            ],
            "time": "2016-03-08 01:15:46"
        },
        {
            "name": "guzzlehttp/psr7",
            "version": "1.2.3",
            "source": {
                "type": "git",
                "url": "https://github.com/guzzle/psr7.git",
                "reference": "2e89629ff057ebb49492ba08e6995d3a6a80021b"
            },
            "dist": {
                "type": "zip",
                "url": "https://api.github.com/repos/guzzle/psr7/zipball/2e89629ff057ebb49492ba08e6995d3a6a80021b",
                "reference": "2e89629ff057ebb49492ba08e6995d3a6a80021b",
                "shasum": ""
            },
            "require": {
                "php": ">=5.4.0",
                "psr/http-message": "~1.0"
            },
            "provide": {
                "psr/http-message-implementation": "1.0"
            },
            "require-dev": {
                "phpunit/phpunit": "~4.0"
            },
            "type": "library",
            "extra": {
                "branch-alias": {
                    "dev-master": "1.0-dev"
                }
            },
            "autoload": {
                "psr-4": {
                    "GuzzleHttp\\Psr7\\": "src/"
                },
                "files": [
                    "src/functions_include.php"
                ]
            },
            "notification-url": "https://packagist.org/downloads/",
            "license": [
                "MIT"
            ],
            "authors": [
                {
                    "name": "Michael Dowling",
                    "email": "mtdowling@gmail.com",
                    "homepage": "https://github.com/mtdowling"
                }
            ],
            "description": "PSR-7 message implementation",
            "keywords": [
                "http",
                "message",
                "stream",
                "uri"
            ],
            "time": "2016-02-18 21:54:00"
        },
        {
            "name": "psr/http-message",
            "version": "1.0",
            "source": {
                "type": "git",
                "url": "https://github.com/php-fig/http-message.git",
                "reference": "85d63699f0dbedb190bbd4b0d2b9dc707ea4c298"
            },
            "dist": {
                "type": "zip",
                "url": "https://api.github.com/repos/php-fig/http-message/zipball/85d63699f0dbedb190bbd4b0d2b9dc707ea4c298",
                "reference": "85d63699f0dbedb190bbd4b0d2b9dc707ea4c298",
                "shasum": ""
            },
            "require": {
                "php": ">=5.3.0"
            },
            "type": "library",
            "extra": {
                "branch-alias": {
                    "dev-master": "1.0.x-dev"
                }
            },
            "autoload": {
                "psr-4": {
                    "Psr\\Http\\Message\\": "src/"
                }
            },
            "notification-url": "https://packagist.org/downloads/",
            "license": [
                "MIT"
            ],
            "authors": [
                {
                    "name": "PHP-FIG",
                    "homepage": "http://www.php-fig.org/"
                }
            ],
            "description": "Common interface for HTTP messages",
            "keywords": [
                "http",
                "http-message",
                "psr",
                "psr-7",
                "request",
                "response"
            ],
            "time": "2015-05-04 20:22:00"
        }
    ],
    "packages-dev": null,
    "aliases": [],
    "minimum-stability": "stable",
    "stability-flags": [],
    "prefer-stable": false,
    "prefer-lowest": false,
    "platform": [],
    "platform-dev": []
}
/**
 * Gruntfile.js
 */
module.exports = function(grunt) {
  grunt.initConfig({
    pkg: grunt.file.readJSON('package.json'),
    php: {
        dist: {
            options: {
                port: 8080,
                base: 'web',
                open: true,
                keepalive: true
            }
        }
    },
    phpcs: {
        application: {
            dir: ['src/']
        },
        options: {
            bin: 'vendor/bin/phpcs',
            standard: 'PSR2'
        }
    },
    phplint: {
        options: {
            swapPath: '/tmp'
        },
        all: ['src/*.php']
    },
    phpdocumentor: {
        dist: {
            options: {
                directory: './src/',
                bin: 'vendor/bin/phpdoc.php',
                target: 'docs/'
            }
        }
    },
    clean: {
        phpdocumentor: 'docs/'
    },
    phpunit: {
        unit: {
            dir: 'tests'
        },
        options: {
            bin: 'vendor/bin/phpunit',
            colors: true,
            testdox: true
        }
    },
    watch: {
        scripts: {
            files: ['src/*.php', 'src/**/*.php', 'tests/*.php', 'tests/**/*.php'],
            tasks: ['precommit'],
        },
    },

  });

  grunt.loadNpmTasks('grunt-phpcs');
  grunt.loadNpmTasks('grunt-php');
  grunt.loadNpmTasks('grunt-phplint');
  grunt.loadNpmTasks('grunt-phpunit');
  grunt.loadNpmTasks('grunt-phpdocumentor');
  grunt.loadNpmTasks('grunt-contrib-watch');
  grunt.loadNpmTasks('grunt-contrib-clean');
  grunt.registerTask('phpdocs', [
    'clean:phpdocumentor',
    'phpdocumentor'
  ]);
  grunt.registerTask('precommit', ['phplint:all', 'phpcs', 'phpunit:unit']);
  grunt.registerTask('default', ['phplint:all', 'phpcs', 'phpunit:unit', 'phpdocs']);
  grunt.registerTask('server', ['php']);
};{
  "name": "cloudconvert-php",
  "version": "2.2.0",
  "project": "CloudConvert",
  "dependencies": {
    "grunt": "~0.4.1",
    "grunt-contrib-clean": "~0.5.0",
    "grunt-phpcs": "~0.2.2",
    "grunt-php": "~0.3.2",
    "grunt-phplint": "~0.0.5",
    "grunt-phpunit": "~0.3.3",
    "grunt-phpdocumentor": "~0.4.1",
    "grunt-contrib-watch ": "~0.6.1"
  }
}<?xml version="1.0" encoding="UTF-8"?>
<phpunit colors="true" bootstrap="tests/bootstrap.php">
    <testsuites>
        <testsuite name="CloudConvert APIs PHP wrapper">
            <directory>tests</directory>
        </testsuite>
    </testsuites>
    <php>
        <env name="API_KEY" value="your_api_key_here"/>
	</php>
</phpunit>cloudconvert-php
=======================

This is a lightweight wrapper for the [CloudConvert](https://cloudconvert.com) API.

Feel free to use, improve or modify this wrapper! If you have questions contact us or open an issue on GitHub.


[![Build Status](https://travis-ci.org/cloudconvert/cloudconvert-php.svg?branch=master)](https://travis-ci.org/cloudconvert/cloudconvert-php)


Quickstart
-------------------
```php
<?php
require __DIR__ . '/vendor/autoload.php';
use \CloudConvert\Api;
$api = new Api("your_api_key");

$api->convert([
        'inputformat' => 'png',
        'outputformat' => 'pdf',
        'input' => 'upload',
        'file' => fopen('./tests/input.png', 'r'),
    ])
    ->wait()
    ->download('./tests/output.pdf');
?>
```

You can use the [CloudConvert API Console](https://cloudconvert.com/apiconsole) to generate ready-to-use PHP code snippets using this wrapper.


Install with Composer
-------------------
To download this wrapper and integrate it inside your PHP application, you can use [Composer](https://getcomposer.org).

Add the repository in your **composer.json** file or, if you don't already have this file, create it at the root of your project with this content:

```json
{
    "name": "Example Application",
    "description": "This is an example",
    "require": {
        "cloudconvert/cloudconvert-php": "dev-master"
    }
}

```

Then, you can install CloudConvert APIs wrapper and dependencies with:

    php composer.phar install

This will install ``cloudconvert/cloudconvert-php`` to ``./vendor``, along with other dependencies including ``autoload.php``.

Install manually
-------------------
If you don't want to use composer, you can download the **cloudconvert-php.phar** release from the [Releases](https://github.com/cloudconvert/cloudconvert-php/releases) tab on GitHub. The .phar file is basically a ZIP file which contains all dependencies and can be used as shown here:

```php
<?php
require 'phar://cloudconvert-php.phar/vendor/autoload.php';
use \CloudConvert\Api;
$api = new Api("your_api_key");

//...
```

Using with Callback
-------------------

This is a non-blocking example for server side conversions: The public URL of the input file and a callback URL is sent to CloudConvert. CloudConvert will trigger this callback URL if the conversion is finished.

```php
<?php
require __DIR__ . '/vendor/autoload.php';
use \CloudConvert\Api;
$api = new Api("your_api_key");

$process = $api->createProcess([
    'inputformat' => 'png',
    'outputformat' => 'jpg',
]);

$process->start([
    'outputformat' => 'jpg',
    'converteroptions' => [
        'quality' => 75,
    ],
    'input' => 'download',
    'file' => 'https://cloudconvert.com/blog/wp-content/themes/cloudconvert/img/logo_96x60.png',
    'callback' => 'http://_INSERT_PUBLIC_URL_TO_/callback.php'
]);

echo "Conversion was started in background :-)";
?>
```

Using the following **callback.php** you can retrieve the finished process and download the output file.

```php
<?php
require __DIR__ . '/vendor/autoload.php';
use \CloudConvert\Api;
use \CloudConvert\Process;
$api = new Api("your_api_key");

$process = new Process($api, $_REQUEST['url']);
$process->refresh()->download("output.jpg");

?>
```



User uploaded input files
-------------------

If your input files are provided by your users, you can let your users directly upload their files to CloudConvert (instead of uploading them to your server first and afterwards sending them to CloudConvert).
The following example shows how this can be implemented easily.

```php
<?php
require __DIR__ . '/vendor/autoload.php';
use \CloudConvert\Api;
$api = new Api("your_api_key");

$process = $api->createProcess([
    'inputformat' => 'png',
    'outputformat' => 'jpg',
]);

$process->start([
    'input' => 'upload',
    'outputformat' => 'jpg',
    'converteroptions' => [
        'quality' => 75,
    ],
    'callback' => 'http://_INSERT_PUBLIC_URL_TO_/callback.php'
]);
?>
<form action="<?=$process->upload->url?>" method="POST" enctype="multipart/form-data">
     <input type="file" name="file">
     <input type="submit">
</form>

```


Download of multiple output files
-------------------

In some cases it might be possible that there are multiple output files (e.g. converting a multi-page PDF to JPG). You can download them all to one directory using the ``downloadAll()`` method.

```php
<?php
require __DIR__ . '/vendor/autoload.php';
use \CloudConvert\Api;
$api = new Api("your_api_key");

$process = $api->convert([
        'inputformat' => 'pdf',
        'outputformat' => 'jpg',
        'converteroptions' => [
            'page_range' => '1-3',
        ],
        'input' => 'download',
        'file' => fopen('./tests/input.pdf', 'r'),
    ])
    ->wait()
    ->downloadAll('./tests/');
?>
```

Alternatively you can iterate over ``$process->output->files`` and download them seperately using ``$process->download($localfile, $remotefile)``.


Catching Exceptions
-------------------
The following example shows how to catch the different exception types which can occur at conversions:

```php
<?php
require __DIR__ . '/vendor/autoload.php';
use \CloudConvert\Api;

$api = new Api("your_api_key");

try {

    $api->convert([
        'inputformat' => 'pdf',
        'outputformat' => 'jpg',
        'input' => 'upload',
        'file' => fopen('./tests/input.pdf', 'r'),
    ])
        ->wait()
        ->downloadAll('./tests/');

} catch (\CloudConvert\Exceptions\ApiBadRequestException $e) {
    echo "Something with your request is wrong: " . $e->getMessage();
} catch (\CloudConvert\Exceptions\ApiConversionFailedException $e) {
    echo "Conversion failed, maybe because of a broken input file: " . $e->getMessage();
}  catch (\CloudConvert\Exceptions\ApiTemporaryUnavailableException $e) {
    echo "API temporary unavailable: " . $e->getMessage() ."\n";
    echo "We should retry the conversion in " . $e->retryAfter . " seconds";
} catch (Exception $e) {
    // network problems, etc..
    echo "Something else went wrong: " . $e->getMessage() . "\n";
}
```



How to build the documentation?
-------------------------------

Documentation is based on phpdocumentor. To install it with other quality tools,
you can install local npm project in a clone a project

    git clone https://github.com/LunawebLtd/cloudconvert-php.git
    cd cloudconvert-php
    php composer.phar install
    npm install

To generate documentation, it's possible to use directly:

    grunt phpdocs

Documentation is available in docs/ directory.

How to run tests?
-----------------

Tests are based on phpunit. To install it with other quality tools, you can install
local npm project in a clone a project

    git https://github.com/LunawebLtd/cloudconvert-php.git
    cd cloudconvert-php
    php composer.phar install
    npm install

Edit **phpunit.xml** file with your API Key to pass functionals tests. Then,
you can run directly unit and functionals tests with grunt.

    grunt


Resources
---------

* [API Documentation](https://cloudconvert.com/apidoc)
* [Conversion Types](https://cloudconvert.com/formats)
* [CloudConvert Blog](https://cloudconvert.com/blog)
<?php
/**
 * This file contains code about \CloudConvert\Api class
 */
namespace CloudConvert;

use CloudConvert\Exceptions\InvalidParameterException;
use Exception;
use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Exception\RequestException;

/**
 * Base Wrapper to manage login and exchanges with CloudConvert API
 *
 * Http connections use guzzle http client api and result of request are
 * object from this http wrapper
 *
 * @package CloudConvert
 * @category CloudConvert
 * @author Josias Montag <josias@montag.info>
 */
class Api
{
    /**
     * Url to communicate with CloudConvert API
     * @var string
     */
    private $endpoint = 'api.cloudconvert.com';
    /**
     * Protocol (http or https) to communicate with CloudConvert API
     * @var string
     */
    private $protocol = 'https';
    /**
     * API Key of the current application
     * @var string
     */
    private $api_key = null;
    /**
     * Contain http client connection
     * @var GuzzleClient
     */
    private $http_client = null;

    /**
     * Construct a new wrapper instance
     *
     * @param string $api_key Key of your application.
     * You can get your API Key on https://cloudconvert.com/user/profile
     * @param GuzzleClient $http_client Instance of http client
     *
     * @throws InvalidParameterException if one parameter is missing or with bad value
     */
    public function __construct($api_key, GuzzleClient $http_client = null)
    {
        if (!isset($api_key)) {
            throw new Exceptions\InvalidParameterException("API Key parameter is empty");
        }
        if (!isset($http_client)) {
            $http_client = new GuzzleClient();
        }
        $this->api_key = $api_key;
        $this->http_client = $http_client;
    }

    /**
     * This is the main method of this wrapper. It will
     * sign a given query and return its result.
     *
     * @param string $method HTTP method of request (GET,POST,PUT,DELETE)
     * @param string $path relative url of API request
     * @param string $content body of the request
     * @param boolean $is_authenticated if the request use authentication
     * @return mixed
     *
     * @throws Exception
     * @throws Exceptions\ApiBadRequestException
     * @throws Exceptions\ApiConversionFailedException
     * @throws Exceptions\ApiException if the CloudConvert API returns an error
     * @throws Exceptions\ApiTemporaryUnavailableException
     * @throws \GuzzleHttp\Exception\GuzzleException if there is a general HTTP / network error
     */
    private function rawCall($method, $path, $content = null, $is_authenticated = true)
    {
        $url = $path;
        if (strpos($path, '//') === 0) {
            $url = $this->protocol . ":" . $path;
        } elseif (strpos($url, 'http') !== 0) {
            $url = $this->protocol . '://' . $this->endpoint . $path;
        }

        $options = array(
            'query' => array(),
            'body' => null,
            'headers' => array()
        );


        if (is_array($content) && $method == 'GET') {
            $options['query'] = $content;
        } elseif (gettype($content) == 'resource' && $method == 'PUT') {
            // is upload
            $options['body'] = \GuzzleHttp\Psr7\stream_for($content);

        } elseif (is_array($content)) {
            $body = json_encode($content);
            $options['body'] = \GuzzleHttp\Psr7\stream_for($body);
            $options['headers']['Content-Type'] = 'application/json; charset=utf-8';
        }

        if ($is_authenticated) {
            $options['headers']['Authorization'] = 'Bearer ' . $this->api_key;
        }

        try {
            $response = $this->http_client->request($method, $url, $options);
            if ($response->getHeader('Content-Type') && strpos($response->getHeader('Content-Type')[0], 'application/json') === 0) {
                return json_decode($response->getBody(), true);
            } elseif ($response->getBody()->isReadable()) {
                // if response is a download, return the stream
                return $response->getBody();
            }
        } catch (RequestException $e) {
            if (!$e->getResponse()) {
                throw $e;
            }
            // check if response is JSON error message from the CloudConvert API
            $json = json_decode($e->getResponse()->getBody(), true);

            if (JSON_ERROR_NONE !== json_last_error()) {
                throw new \RuntimeException('Error parsing JSON response');
            }

            if (isset($json['message']) || isset($json['error'])) {
                $msg = isset($json['error']) ? $json['error'] : $json['message'];
                $code = $e->getResponse()->getStatusCode();
                if ($code == 400) {
                    throw new Exceptions\ApiBadRequestException($msg, $code);
                } elseif ($code == 422) {
                    throw new Exceptions\ApiConversionFailedException($msg, $code);
                } elseif ($code == 503) {
                    throw new Exceptions\ApiTemporaryUnavailableException(
                        $msg,
                        $code,
                        $e->getResponse()->getHeader('Retry-After') ? $e->getResponse()->getHeader('Retry-After')[0] : null
                    );
                } else {
                    throw new Exceptions\ApiException($msg, $code);
                }
            } else {
                throw $e;
            }
        }
    }

    /**
     * Wrap call to CloudConvert APIs for GET requests
     *
     * @param string $path path ask inside api
     * @param string $content content to send inside body of request
     * @param boolean $is_authenticated if the request use authentication
     *
     * @throws \CloudConvert\Exceptions\ApiException if the CloudConvert API returns an error
     * @throws \GuzzleHttp\Exception\GuzzleException if there is a general HTTP / network error
     *
     */
    public function get($path, $content = null, $is_authenticated = true)
    {
        return $this->rawCall("GET", $path, $content, $is_authenticated);
    }

    /**
     * Wrap call to CloudConvert APIs for POST requests
     *
     * @param string $path path ask inside api
     * @param string $content content to send inside body of request
     * @param boolean $is_authenticated if the request use authentication
     *
     * @throws \CloudConvert\Exceptions\ApiException if the CloudConvert API returns an error
     * @throws \GuzzleHttp\Exception\GuzzleException if there is a general HTTP / network error
     *
     */
    public function post($path, $content, $is_authenticated = true)
    {
        return $this->rawCall("POST", $path, $content, $is_authenticated);
    }

    /**
     * Wrap call to CloudConvert APIs for PUT requests
     *
     * @param string $path path ask inside api
     * @param string $content content to send inside body of request
     * @param boolean $is_authenticated if the request use authentication
     *
     * @throws \CloudConvert\Exceptions\ApiException if the CloudConvert API returns an error
     * @throws \GuzzleHttp\Exception\GuzzleException if there is a general HTTP / network error
     *
     */
    public function put($path, $content, $is_authenticated = true)
    {
        return $this->rawCall("PUT", $path, $content, $is_authenticated);
    }

    /**
     * Wrap call to CloudConvert APIs for DELETE requests
     *
     * @param string $path path ask inside api
     * @param string $content content to send inside body of request
     * @param boolean $is_authenticated if the request use authentication
     *
     * @throws \CloudConvert\Exceptions\ApiException if the CloudConvert API returns an error
     * @throws \GuzzleHttp\Exception\GuzzleException if there is a general HTTP / network error
     *
     */
    public function delete($path, $content = null, $is_authenticated = true)
    {
        return $this->rawCall("DELETE", $path, $content, $is_authenticated);
    }

    /**
     * Get the current API Key
     *
     * @return string
     */
    public function getApiKey()
    {
        return $this->api_key;
    }

    /**
     * Return instance of http client
     *
     * @return GuzzleClient
     */
    public function getHttpClient()
    {
        return $this->http_client;
    }

    /**
     * Create a new Process
     *
     * @param array $parameters Parameters for creating the Process. See https://cloudconvert.com/apidoc#create
     * @return \CloudConvert\Process
     *
     * @throws \CloudConvert\Exceptions\ApiException if the CloudConvert API returns an error
     * @throws \GuzzleHttp\Exception\GuzzleException if there is a general HTTP / network error
     *
     */
    public function createProcess($parameters)
    {
        $result = $this->post("/process", $parameters, true);
        return new Process($this, $result['url']);
    }

    /**
     * Shortcut: Create a new Process and start it
     *
     * @param array $parameters Parameters for starting the Process. See https://cloudconvert.com/apidoc#start
     * @return \CloudConvert\Process
     *
     * @throws \CloudConvert\Exceptions\ApiException if the CloudConvert API returns an error
     * @throws \GuzzleHttp\Exception\GuzzleException if there is a general HTTP / network error
     *
     */
    public function convert($parameters)
    {
        $startparameters = $parameters;
        // we don't need the input file for creating the process
        unset($startparameters['file']);
        $process = $this->createProcess($startparameters);
        return $process->start($parameters);
    }
}
<?php
/**
 * This file contains code about \CloudConvert\ApiObject class
 */
namespace CloudConvert;

/**
 * Base class for Objects returned from the CloudConvert API
 *
 * @package CloudConvert
 * @category CloudConvert
 * @author Josias Montag <josias@montag.info>
 */
class ApiObject
{
    /** @var Api */
    protected $api;
    /** @var string */
    public $url;
    /**
     * Contains the object data returned from the CloudConvert API
     * @var array
     */
    protected $data = array();

    /**
     * Construct a new ApiObject instance
     *
     * @param Api $api
     * @param string $url The Object URL
     *
     * @throws Exceptions\InvalidParameterException If one parameter is missing or with bad value
     */
    public function __construct(Api $api, $url)
    {
        if (!isset($api)) {
            throw new Exceptions\InvalidParameterException("API parameter is not set");
        }
        if (!isset($url)) {
            throw new Exceptions\InvalidParameterException("Object URL parameter is not set");
        }
        $this->api = $api;
        $this->url = $url;
        return $this;
    }

    /**
     * Refresh Object Data
     *
     * @param array $parameters Parameters for refreshing the Object.
     *
     * @return \CloudConvert\ApiObject
     *
     * @throws \CloudConvert\Exceptions\ApiException if the CloudConvert API returns an error
     * @throws \GuzzleHttp\Exception\GuzzleException if there is a general HTTP / network error
     *
     */
    public function refresh($parameters = null)
    {
        $this->data = $this->api->get($this->url, $parameters, false);
        return $this;
    }

    /**
     * Access Object data via $object->prop->subprop
     *
     * @param string $name
     * @return null|object
     */
    public function __get($name)
    {

        if (is_array($this->data) && array_key_exists($name, $this->data)) {
            return self::arrayToObject($this->data[$name]);
        }

        return null;
    }

    /**
     * Converts multi dimensional arrays into objects
     *
     * @param array $d
     * @return object
     */
    private static function arrayToObject($d)
    {
        if (is_array($d)) {
            /*
             * Return array converted to object
             * Using [__CLASS__, __METHOD__] (Magic constant)
             * for recursive call
             */
            return (object)array_map([__CLASS__, __METHOD__], $d);
        } else {
            // Return object
            return $d;
        }
    }
}
<?php
namespace CloudConvert\Exceptions;


/**
 * ApiBadRequestException exception is throwned when a the CloudConvert API returns any HTTP error code 400
 *
 * @package CloudConvert
 * @category Exceptions
 * @author Josias Montag <josias@montag.info>
 */
class ApiBadRequestException extends ApiException
{
}
<?php
namespace CloudConvert\Exceptions;


/**
 * ApiConversionFailedException exception is throwned when a the CloudConvert API returns any HTTP error code 422
 *
 * @package CloudConvert
 * @category Exceptions
 * @author Josias Montag <josias@montag.info>
 */
class ApiConversionFailedException extends ApiException
{
}
<?php
namespace CloudConvert\Exceptions;

use Exception;

/**
 * ApiException exception is throwed when a the CloudConvert API returns any HTTP error code
 *
 * @package CloudConvert
 * @category Exceptions
 * @author Josias Montag <josias@montag.info>
 */
class ApiException extends Exception
{
}
<?php
namespace CloudConvert\Exceptions;


/**
 * ApiBadRequestException exception is throwned when a the CloudConvert API returns any HTTP error code 503
 *
 * @package CloudConvert
 * @category Exceptions
 * @author Josias Montag <josias@montag.info>
 */
class ApiTemporaryUnavailableException extends ApiException
{
    public $retryAfter = 0;

    /**
     * @param string $msg
     * @param int $code
     * @param int $retryAfter
     */
    public function __construct($msg, $code, $retryAfter = 0)
    {
        $this->retryAfter = $retryAfter;
        return parent::__construct($msg, $code);
    }
}
<?php
namespace CloudConvert\Exceptions;

use Exception;

/**
 * InvalidParameterException exception is throwed when a request failed because of a bad client configuration
 *
 * InvalidParameterException appears when the request failed because of a bad parameter from
 * the client request.
 *
 * @package CloudConvert
 * @category Exceptions
 * @author Josias Montag <josias@montag.info>
 */
class InvalidParameterException extends Exception
{
}
<?php
/**
 * This file contains code about \CloudConvert\Process class
 */
namespace CloudConvert;

use CloudConvert\Exceptions\InvalidParameterException;

/**
 * CloudConvert Process Wrapper
 *
 * @package CloudConvert
 * @category CloudConvert
 * @author Josias Montag <josias@montag.info>
 */
class Process extends ApiObject
{

    /**
     * Construct a new Process instance
     *
     * @param Api $api
     * @param string $url The Process URL
     * @return \CloudConvert\Process
     *
     * @throws InvalidParameterException if one parameter is missing or with bad value
     */
    public function __construct(Api $api, $url)
    {
        parent::__construct($api, $url);
        return $this;
    }

    /**
     * Starts the Process
     *
     * @param array $parameters Parameters for creating the Process. See https://cloudconvert.com/apidoc#start
     * @return \CloudConvert\Process
     *
     * @throws \CloudConvert\Exceptions\ApiException if the CloudConvert API returns an error
     * @throws \GuzzleHttp\Exception\GuzzleException if there is a general HTTP / network error
     *
     */

    public function start($parameters)
    {
        if (isset($parameters['file']) && gettype($parameters['file']) == 'resource') {
            $file = $parameters['file'];
            unset($parameters['file']);
            if (isset($parameters['wait']) && $parameters['wait']) {
                unset($parameters['wait']);
                $wait = true;
            }
        }
        $this->data = $this->api->post($this->url, $parameters, false);
        if (isset($file)) {
            $this->upload($file);
        }
        if (isset($wait)) {
            $this->wait();
        }
        return $this;
    }

    /**
     * Uploads the input file. See https://cloudconvert.com/apidoc#upload
     *
     * @param string $filename Filename of the input file
     * @return \CloudConvert\Process
     *
     * @throws \CloudConvert\Exceptions\ApiException if the CloudConvert API returns an error
     * @throws \GuzzleHttp\Exception\GuzzleException if there is a general HTTP / network error
     *
     */

    public function upload($stream, $filename = null)
    {
        if (!isset($this->upload->url)) {
            throw new Exceptions\ApiException("Upload is not allowed in this process state", 400);
        }

        if (empty($filename)) {
            $metadata = stream_get_meta_data($stream);
            $filename = basename($metadata['uri']);
        }
        $this->api->put($this->upload->url . "/" . rawurlencode($filename), $stream, false);
        return $this;
    }

    /**
     * Waits for the Process to finish (or end with an error)
     *
     * @return \CloudConvert\Process
     *
     * @throws \CloudConvert\Exceptions\ApiException if the CloudConvert API returns an error
     * @throws \GuzzleHttp\Exception\GuzzleException if there is a general HTTP / network error
     *
     */
    public function wait()
    {
        if ($this->step == 'finished' || $this->step == 'error') {
            return $this;
        }

        return $this->refresh(['wait' => 'true']);
    }

    /**
     * Download process files from API
     *
     * @param string $localfile Local file name (or directory) the file should be downloaded to
     * @param string $remotefile Remote file name which should be downloaded (if there are
     *         multiple output files available)
     *
     * @return \CloudConvert\Process
     *
     * @throws \CloudConvert\Exceptions\ApiException if the CloudConvert API returns an error
     * @throws \GuzzleHttp\Exception\GuzzleException if there is a general HTTP / network error
     * @throws Exceptions\InvalidParameterException
     *
     */
    public function download($localfile = null, $remotefile = null)
    {
        if (isset($localfile) && is_dir($localfile) && isset($this->output->filename)) {
            $localfile = realpath($localfile) . DIRECTORY_SEPARATOR
                . (isset($remotefile) ? $remotefile : $this->output->filename);
        } elseif (!isset($localfile) && isset($this->output->filename)) {
            $localfile = (isset($remotefile) ? $remotefile : $this->output->filename);
        }

        if (!isset($localfile) || is_dir($localfile)) {
            throw new Exceptions\InvalidParameterException("localfile parameter is not set correctly");
        }

        return $this->downloadStream(fopen($localfile, 'w'), $remotefile);
    }

    /**
     * Download process files from API and write to a given stream
     *
     * @param resource $stream Stream to write the downloaded data to
     * @param string $remotefile Remote file name which should be downloaded (if there are
     *         multiple output files available)
     *
     * @return \CloudConvert\Process
     *
     * @throws \CloudConvert\Exceptions\ApiException if the CloudConvert API returns an error
     * @throws \GuzzleHttp\Exception\GuzzleException if there is a general HTTP / network error
     *
     */
    public function downloadStream($stream, $remotefile = null)
    {
        if (!isset($this->output->url)) {
            throw new Exceptions\ApiException("There is no output file available (yet)", 400);
        }

        $local = \GuzzleHttp\Psr7\stream_for($stream);
        $download = $this->api->get($this->output->url . (isset($remotefile) ? '/' . rawurlencode($remotefile) : ''), false, false);
        $local->write($download);
        return $this;
    }

    /**
     * Download all output process files from API
     *
     * @param string $directory Local directory the files should be downloaded to
     *
     * @return \CloudConvert\Process
     *
     * @throws \CloudConvert\Exceptions\ApiException if the CloudConvert API returns an error
     * @throws \GuzzleHttp\Exception\GuzzleException if there is a general HTTP / network error
     *
     */
    public function downloadAll($directory = null)
    {
        if (!isset($this->output->files)) { // the are not multiple output files -> do normal downloader
            return $this->download($directory);
        }

        foreach ($this->output->files as $file) {
            $this->download($directory, $file);
        }

        return $this;
    }


    /**
     * Delete Process from API
     *
     * @return \CloudConvert\Process
     *
     * @throws \CloudConvert\Exceptions\ApiException if the CloudConvert API returns an error
     * @throws \GuzzleHttp\Exception\GuzzleException if there is a general HTTP / network error
     *
     */
    public function delete()
    {
        $this->api->delete($this->url, false, false);
        return $this;
    }
}
<?php
namespace CloudConvert\tests;

use CloudConvert\Api;
use CloudConvert\Exceptions\ApiTemporaryUnavailableException;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;


/**
 * Tests of Api class
 *
 * @package CloudConvert
 * @category CloudConvert
 * @author Josias Montag <josias@montag.info>
 */
class ApiTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Define id to create object
     */
    protected function setUp()
    {
        $this->api_key = getenv('API_KEY');
        $this->api = new Api($this->api_key);
    }
    /**
     * Get private and protected method to unit test it
     */
    protected static function getPrivateMethod($name)
    {
        $class = new \ReflectionClass('CloudConvert\Api');
        $method = $class->getMethod($name);
        $method->setAccessible(true);
        return $method;
    }
    protected static function getPrivateProperty($name)
    {
        $class = new \ReflectionClass('CloudConvert\Api');
        $property = $class->getProperty($name);
        $property->setAccessible(true);
        return $property;
    }
    /**
     * Test if request without authentication works
     */
    public function testIfRequestWithoutAuthenticationWorks()
    {
        $invoker = self::getPrivateMethod('rawCall');
        $result = $invoker->invokeArgs($this->api, array(
            'GET',
            '/conversiontypes',
            array(
                'inputformat' => 'pdf',
                'outputformat' => 'pdf',
            ),
            false,
        ));
        $this->assertNotEmpty($result);
    }
    /**
     * Test if request without authentication works
     */
    public function testIfRequestWithAuthenticationWorks()
    {
        $invoker = self::getPrivateMethod('rawCall');
        $result = $invoker->invokeArgs($this->api, array(
            'POST',
            '/process',
            array(
                'inputformat' => 'pdf',
                'outputformat' => 'pdf',
            ),
            true,
        ));

        $this->assertArrayHasKey('url', $result);
    }
    /**
     * Test if Process creation works
     */
    public function testIfProcessCreationWorks()
    {
        $process = $this->api->createProcess(array(
            'inputformat' => 'pdf',
            'outputformat' => 'pdf',
        ));
        $this->assertInstanceOf('CloudConvert\Process', $process);
    }
    /**
     * Test if Process creation with invalid format throws a CloudConvert\Exceptions\ApiException
     */
    public function testIfProcessCreationWithInvalidFormatThrowsTheRightException()
    {
        $this->setExpectedException('CloudConvert\Exceptions\ApiException', 'This conversiontype is not supported!', 400);
        $this->setExpectedException('CloudConvert\Exceptions\ApiBadRequestException', 'This conversiontype is not supported!', 400);

        $this->api->createProcess(array(
            'inputformat' => 'invalid',
            'outputformat' => 'pdf',
        ));
    }

    /**
     * Test if API error 503 throws a CloudConvert\Exceptions\ApiTemporaryUnavailableException with correct retryAfter value
     */
    public function testIfApiTemporaryUnavailableExceptionIsThrown()
    {

        $mock = new MockHandler([
            new Response(503, ['Retry-After' => 30, 'Content-Type' => 'application/json; charset=utf-8'], "{\"message\":\"API unavailable. Please try later.\"}"),
        ]);

        $handler = HandlerStack::create($mock);
        $client = new Client(['handler' => $handler]);



        $api = new Api($this->api_key, $client);
        $invoker = self::getPrivateMethod('rawCall');

        try {
            $invoker->invokeArgs($api, array(
                'GET',
                '/conversiontypes',
                array(
                    'inputformat' => 'pdf',
                    'outputformat' => 'pdf',
                ),
                false,
            ));
        }
        catch (ApiTemporaryUnavailableException $expected) {
            $this->assertEquals(30, $expected->retryAfter);
            return;
        }

        $this->fail('CloudConvert\Exceptions\ApiTemporaryUnavailableException has not been raised.');

    }
}
<?php
require __DIR__ . '/../vendor/autoload.php';%PDF-1.4%����
94 0 obj<</Linearized 1/L 172570/O 96/E 2745/N 31/T 172077/H [ 454 266]>>endobj                
99 0 obj<</DecodeParms<</Columns 4/Predictor 12>>/Filter/FlateDecode/ID[<3F11E3847274FCD30547F3C418965B22><C0008D8905001A42BB2455150C4C5158>]/Index[94 11]/Info 93 0 R/Length 49/Prev 172078/Root 95 0 R/Size 105/Type/XRef/W[1 2 1]>>stream
h�bbd``b`: $��F �b$~�0012��10"�S� �"�
endstreamendobjstartxref
0
%%EOF
        
104 0 obj<</Filter/FlateDecode/I 239/Length 183/S 203>>stream
h�b```"1�>�������������A�����Y0��<��'���?�P��V��1[~���-��96��Rי�(��H�f�:�%�-�<5�<U&��5���2�Sm��i��������� ��! l/�� �+Sf4�fb6�aA^�H8��ʔ�X �b`Z1H�e�0 \8@�
endstreamendobj95 0 obj<</Metadata 72 0 R/PageMode/UseNone/Pages 92 0 R/Type/Catalog>>endobj96 0 obj<</Contents 98 0 R/CropBox[0 0 595 842]/MediaBox[0 0 595 842]/Parent 92 0 R/Resources<</Font<</F1 101 0 R/F2 103 0 R>>/ProcSet[/PDF/Text]>>/Rotate 0/Type/Page>>endobj97 0 obj<</Filter/FlateDecode/First 32/Length 728/N 4/Type/ObjStm>>stream
hޤU�n�0�~@ ���H�$�$hA�-�lٰ�6��rF"M�r���R�qf8|�F8�Dp�WA���o$��)rqA�����2NǿO�Y%�ʷ�E�X6D3F���HpC��|Q��tS5����y��##����B���._��\�]��~�Ϋ/��jN�7�����Ū �����������MB�����k�I$M��&���l�l<�ܕ�f�����M� ���ߪ���Oe5��2~O�]�\-���ԧݼ�7yg��X�Ӈן$�{-0�����g��R�5���]��u�DPZF���N�B�c�^�IF���k��}B�ma�L����������BҠ2)J;�$���ÚʣO�w�>裏OT&:L�k �3E�`�j�
��H;�
|��z{��x�$T�
	��qz�Ɇ��:��aq S�eh��MP��o孿�{����7�#� I��v��dÈ��or `M�� E���8*;������� �y��$�C�c��}�����g�s�N�";�	�Sl�[�a8�c�oȨ��.�� C�	~0�� ������K�;؁/r�Q9��K�N�,�b����"-%/?h����Ҟ��N�~
�;x |����}���S�O�.<�#���D�d`�n��{D��<$:~Lt*��>����Kcv8��7����2 ��a�;��s��y�  �@;�
endstreamendobj98 0 obj<</Filter/FlateDecode/Length 875>>stream
x��UMO1�G���ⴖ�f���!� %�KK|�F
I	i%�}�3�fU��=;�͛ٙǬ�7̪<���Yg�@�Bp���(x,r�����+��Ç�{qY0�Eq�$7�%c����J�]q�\���++��1+��]<���g���f���cx���Y�,�<f�%�i�1�K���q�rm�>/����]��� (~�
j���q#cT\y��K����ɴ2�0���6=��_b�O[n�In����L
@��҂j3�P��d��00PŔd\�!�Ʉ�5��
Ǣ��▌�W/���U�%6G!FS��t=`�C�
�3B�	�k ���RU��؁�+�[7�1��Ȁ��\���ݡ%�G%np�s-�QI. ���Z���z�Lިx��M40�#^d����o�!,�� �� ������ߝ�j��
�cd3C�n�i�#"�U,�&'� 7���H`/��w��lߍA�1�T4S�<���О�{�O��\�K�U��e��w�Bt���P�ʄLi��)0��s�XB@Vo�	o\B	�f����H�/X�v#اNw�7��b��C��EId}2���}��X?�w���y��2l��ynuh��-��A���69���#n7!�t�t�qm��c��F%��K%���R��$Mj��`��X�9J��t�;wL�iҬN\J,xA���U�D�#P�E�"{D�|}��UY��0��p�cR�zh�>��[j�3���`+��Eܫ�}����c�s&&N�bU#-!ƴFH�\�"��x��mL��6�q��3��Iݗ�ئ���1JR-)�R{�<�Ho�R�L�W�:L'¥����	9��ݚ:t7�]h��@G���I���&���
endstreamendobj1 0 obj<</Contents 2 0 R/CropBox[0 0 595 842]/MediaBox[0 0 595 842]/Parent 92 0 R/Resources<</Font<</F1 101 0 R/F2 103 0 R/F3 83 0 R/F4 82 0 R/F5 81 0 R>>/ProcSet[/PDF/Text]>>/Rotate 0/Type/Page>>endobj2 0 obj<</Filter/FlateDecode/Length 2999>>stream
x��[�o��n���h�dF�4]G_�(�1�X�c�ě&����99������f��EH���U����U������0_����=T���Zl/=V���j��t���b��px�<^�Xޮ�&+�7+ �˻պ���^��~�V�|Y�u��������ÃO�Mwƪ�L�(�Y��Oy^���><��Ҕ� gK�Z�"����������ſ������Y�d��Wk���ӕ.��h����}�l��N`�r���eX���c�dy+m�B-ր������e��5@Z��1�B�=[�aJ�O��Z�ڮ�]��o^ `����f�X�|�B0����/I#���᷍�8������UMV�����W�Zʙ�ڳ���Ѐ3*P����o��p����9cƃl=�a����Pm蕧�,3 ��,�I&k�"hϖv�?���}J9�&�9e���#1��z����a�C�.J��HJ�;M �� J�V똒@ϙ��krL`�
!'z*�@�TL�q�@��"�_�\�^�yY�i2+�+q@�NċLe>PF�#_���XG�6�W>�� ��+`�nU,'�������=86��x����l���&�F��ƳC�hKe�z�����ىep���Cq�<��hOx�L�D`6D���R����L�2�_;�@��Ҏai��n�(������G���Hb㜗.*�d��ׇe�W"��?rw��$��C"(�&Sj�L�z�o(�*Sz�"ʎc,���%+-t|�t�x~�j,A�$w�5�GLC0���
k�dM-�����>�lij�����n�y_0`��};z','.A��U�sZ�x%�B��1�h����0�%L5+G�-(��P���Ul��nj�m+�.$��Q�(Ƒ_N�s��XB��� ���B�uf�X�I�`��-X�1X��ʋG�g1:���}���ʥ
=EȨ/DRg
�Ұ?�I����)7������.�iJp�q��ҽԥ���cy|��+��ra�����<��-�S|��Լċ1�&)(�D쒂e?�R5z?~}F�S���p(�1S;�`�Dg�M���1�����,�r�U W��M�6� s��}����e���+�eR�=#�nE	CM#�%�� 8�Q�Uv�V٪�fb �ש�*���&Nn�\x�WT�=��处pMr��)VlUg�Zhȟ̰=`��[�.���P�=C0֔1��X"��xSc�zQ���m�'~p,%q,[�IT�Fr��
u��"��D�ώS���x)Uy��*��Tp��\B��U �c�q�mՂ�c~���
��d�G�`��E�h����i�{����&��r�QM����L�]T]�j�XH���"�~�v.Z�;*Q��-B�ڮ�}���XB���m��v��bgM���dh��,gu�"�~h�=
�e����lv���e�vg����zŲF6M�Z�u���ْi���e?���a�-J}�DmQ9q"Sjm�W~��?����.�`�o�v�M9�:���I{S������H5��T�&m؋hQӱS:��ȰL�6~��;��t�<r3������&k��}6�f>%��ހ/�}�!�.�I���~}D���^p��w2
E=s*��0��
��w��d�i��'�|蠴�8j�IH@4���2Bb�}����DC"?C��&�m���0A��U��i�I�=�uQvt�NnK���C���� �t����Z��7�49���vz�f ��k>G�-�M.H��-���r|&�FG�m��~q#�(�X `Uj\_/�se��3��HQw����.�@�S�5�n���ͨT�Z�$U�/�l��o�4Gg7��,8�8�Ն�q֍��tf�#�u�t_�;22�ס�"yl®����aPt���l�<�ӕ�Dg�REi�8*ơ"�;�!9j����[9Taϧ��Q��*n*y6�T� Uߡ>�x�L}y���F)�`5p����A�;����Tb�=��U�gݍ��8�C%����d�8�쥯��Zy�/4Hݛ {9X,�L^�V��nz3kg��"�Q1�1F����{j�ξj�"�g����8�(�����T�n�}d[(,M�L;�����lģ�-��d�!\�/=�|+_�;�i��#�)��^U9v�pD���� ����6P �#?Nw���_-�����{ǔ �.,y�ω�"|E㜺-�t�.��1��5$\�Zف�2}5آ��d�P$w ��+HC�	�����8�c����2E��'�3���ie�L�؂
�sA�"��
.6�܍�-�z���;Lrq�#Z|?w aO�uw��}<������Gn��5]}q��?��s��b��ݞS�����[���1j�7����/ݳ�k�I���������/����w+���w��8�[���R��d��[������5T� _��g���l�o?,E_p��ay��'�p�Nd��
^�//���`�M/]�Imr@/M��}���N�U1���`��w��X-�>�>��W7�O��#c#w�<7�{^k��v�R��>szI�3�������A�׷2/+�(��/X*y�sXѹGD��+d=�|�ǲ~�m��?��<��6����/��EG_~����Z����!ƘU�M��A���u��v1D܍ߖM)G1˧�=�f���6�/H��N#��.c��:�Τ�m��ҵ}ވ�x7�\�^��[�	�FH�\��x)��,�7�H罟Nc�Qnc��8���k���i
��#�Τ��zw�-��~�-N�.n��pwEj� �~�1�ݕ��t�]D��x�o�ѷ�ke;��<#� ��C���>9gAv��Gz�X�[A*�(N��K�e�p}~`aJX�`Ê*�<Om�K��Lp)��R$^���D��l��
endstreamendobj3 0 obj<</Filter/FlateDecode/Length 10383/Length1 19219>>stream
x��|XT���>�i��r !*mPPQ鶨(`e�F��qf�h,�cרػb�KԨѨјX�I��^cL䭽ϙaP���}������Y���k���93Q!'T��(9!�g�Qw�턑+yg�K�|����G��@e��ް)���	;e�s���xU���a��s4%ٙ%�!�����\ś�M�/�~�\�a��
�s�o��o*�Խ����O���Z�Z�j�)B��|y���x p���V���(>����I�3�`Lu_���F?��qg���4�H�5�N���?�hi�w�Ȯ���_9Qb^U�w[��Q��Aj'6��	�T.�Q�"�G	�R���A6#͗�,k��I�ȈtH�d芋�ǆ��c����7�-�6��)]��h�廓��
�k�
�a��*>���\��o:��Ԕ��W��{8�VJ���RB`�|2a�?@ r���yJ�qG���M�AK�����Cꆇ��	�\[��h�3P�Q{wQZ�����ZH�񀃻;@�3�:[����:������7������<_�����ҖM���0i���j�$������w$M�av@���ԓݿ�@ƠNU�h��Ze�,Pږ���2A��S-{�2�B��1�M+(_[�PBį�����*(
�;�u��S��O&m�P�x[�'�:�ϑ�[�j~i����I'e��r9��K����_��h�]��Y[���}��}b_J��]^��a�po��?C�Z�|�b��f�x�~�q��FY�c����t��7ۮM�7�t�,����}mw�;��)=�TqD��n��憮�u�P�v_O����olF��`���	CO�xF/w/������#�~�-v��;�r׏ζ,��Tv��-ߦ��t�_�lxU�7��x8��'��}����vΩ�7?���ٍ>���Vm��\3��E�ei�O��<V(S���4@��җR�rM&}��P�Ҩ)�A�!J]>��U+�HEP�($��c���Q�UUa��r�ASou(�+��X�S[���.�i#<��@��C�
�3W6�6��7��1-5-*X�>�������^y��eJh.�T� p����ԅ��2Wfh�Iڮv��,�ۂ�G�?
EQ��g�]x�W���[�O?�����	5w��CoO�۷���>�[�y�<�Y��n�8��=��uݾx��_�k��vd̋ӎK�{x�s��w�G������V�nsq�t7�����N�
�[X8��8�����Qe���榡�`vӀ��1�ɭ�mz�|.�t�WBv��1�w-U�v��b�k��� �ł����M9g��ʻ݃}�?��|cox�7�~^�L��#�����+����Z���^�y��W7���F~g�ݵ��_p�����A�ҧ��Om�v魡#�K��]�?��ۓY�cn��~/�]���J�6�_U�O^8���I��˒�Ww36��,����������z�k�x�NբfnО.�K~�/}��V��-���	���d���)ښ�����r��Ʒ2����	�ŻL(��B����Ðt��$O���l0I�I�TT����`4��J9	��$�0����VF���������P��_J�WH�����}3���N�:���O���WH]��0y�����/h��щ_%�{����5��qOzsb��X��E�'�V��;ٻu�S�T��='�y�h:�[t���	�3��d*�۰b��yկ���'���G��۝�EU�*���NW>.�c���k��a_��̣����ɾA�g����8���[{����������1���Y�����~4�������;��rv��_��.m�S�|K3Et��i�ۉG��U3d�o�W��FO�$��A
��M�� �=��lח���bl5&���Ķ��o�N_bP���6ʶ��c�H��Zi�u�&:^gЇ�ZJ��Ȟ�gt�����b��U7��ә��S�Π6����1R*�I��\z����e\�?��?�}��:?���f��a�{��M����9���v�r�����-���w6NU�pc��KO�b|��K�e��+U�]h}ՙ��������.�Xp�S�~���n�w����Mǵ�{���>�y�B� �Ɗ�˲��>��`������2Ik���n����e��#+C�,m�2�՚G�yG����X3�l��i��n�{Mi�����s�����Y��=����k�Y�m/Y�}���G;:k\^$����esޚO}zaM3Ð�{KV�JkD�����?��卵��U���8.)A�Bi��2��g�Ԇ%~��xl�3���e������'Ya��S���j�`���"�gC�.q���p����;��y�8}V���������d�΃�uHS�m������W���M���E?b�[�~^�����:��wO1j����#���b��U�;+W�Jk�����g)��a�+��q3G����')��}X�-���bN�f}~h�[������ϫ>�����MJڎm�6n�3.<�L�kޑ�A7Jo�-�^X�Ό�v����ĺ�m?�{�����o.��6QplPx��Os'�N��S����-�T�����:M�U�t��U������b0�aA����K�y˲?�����?���!����E�<�����P�����]��>nاˍ�p0�>.���q
���iU��?�ه��M��O�Ê��vFŐ���$����L�3��d��O�S�^���������/��=�n`�_��ھ$^+�Ay|窗w�P=e�r��;*R�^qt�ˑ�捛��'�O5�`���(�8�[T�7�����e����kJ�_�1ݟ�mNz񑱥�qM[�ߑ��܊3�G�v)�:�'ax܃���]#�Z�pk����V_}���"���Qc�dܾq?��ëv��ݢ���}�&����&�z}~�8!���~�&�Zt g�=�7����\02:pM���ׂ�5s�����v��dB�)���{�T;�G@C�p�Gzq�q���_x|>�G�����z̐���˪vM�9�:�\�Ժă'pli�RQ<�ǣX�����$����%�򡲉K�Ɣ�y&�e���q�B�u�"�ɚ�r�X�Ϩ���?�h��G���������T������o�ku����ƥn����/Z�ٌ�S2�4?9�ܜ���g^���hX��:��i�}2�s��W�O{,O��)~�������ҕ���U�ט��6�=����G�O�?;qyC�V|�i�{������?	ب.�>�Ɯ�jՖ	��y����p�Zq`���>?JW�P�5ߒ>��R�/�E;F>�u��}��C�9}a��_?�Y�ћm�U�E���ڹ:K+��ʼ�4f/OZ�- ����P�������1"<�~Z���t����?"7����?^�N�ύ�<d�Ӄ׮��0g��ŭ&=t|���Ն6N��R��(�c�V��9�����~��?�n~�pe�7�d鶡��ZQ./Y�x��8���o�~���k��K}%�ln�Ik���IȾyy���])l� E7/|2j��-V���p|��RhN(O,���a�q�zn��a�SV�W���4��g᷒KW�=�����{�ּ������|4��������+�j72Ǵ�hJ����ݻ=<��/�F:�U�Y�d�9ls�Ws��k>�e�F�w���UP��F����R�^�[W�z��C�ME-W|����?����Q?A�2i�����2���D�{��Л}�K��Ic��VE��d���o�C���yj<�7�TJ�1 ���0�@���I4^+�f}���5D�1�O���3a��Gs���14M���;v��͹C}6�n�4��R؎?F�rz�Ӭ����ms�Tyx�"擉��GWx�k�q�WCN���h��M�X5����N.+X<sd������4,����a/�~��il���6��23�=î�g�	��
�vc�w��Y�X�`�Ńk�H<�}��H�������W��{����"��ϻN?I�;f��'�.뱗�*5���;"�@T�M���gѽ�A.o�/{�y$���O��V������k�=�����S�C����4�DV!���7<���o��I��|�k��;R�چ���B������2G�7��z]�A�Hj;�)��[(�A�F�٪.��Y�e�O�	,�ݻc���f��L%UTE�u@}�)�����ldB4J����"挀�Gh�� )�.(k�A�6��u9�>���ۤ��BҨN�_Μ�%Y]93��"�x�.:'�X�w�T���Êi����5����h��̜/�'|��Ȍ���l����|,�ʹ�u��Z��r���+wZ8�u�ݟG~^V���b��$�d�؛�UI?ܯ�+��Ʉ�G��y0�͘#��	/�|4E���׽�^���`�-���o�O���f���t���^��t{�L\����SU|�5�����d��Hd^�X'�ޘ4 T�v���1[~Xyl�t]֬�#��;�-C-��]z���#n����w��q��K^�~}d�
�UPo�,&�UP�a�v������JuIXx�e�2�^���P���g�ʜ�y|XXx�,�� ȿ6��&p��iUU/wI'���C��F4�#U6n�糛7��[�>M8]|��U>�����N���Uy;F�2ܶ?�x�\��nX��]1Kz��(s[Q=�ܪގ9S���Rt�1�ٍ}��~�Ӥ����^G�\v����U?�;��7'UDAuѴ��K��?1�s��:��A��f����z��ڊk]Z9�o��)ޅ)Mvrw����W��f/7�;�ɺ;��㟺k��h�����]�3�x������[z�8*w�>6��g_,hw�JP���`�o磦M[8 ��Ѧ�!??6C�������9<o�7���-�Q��$D�w���7��Hŧa�C}Ri�<�׊��H&�Q�b����~pFEIe��y����$dh!�
`��f�jX`�V/t[x�A.���h4|&�?��h<�F
�W ���j�ИMM!�'h�~«��"�݀n/��^���czc�ft�: 8��,�ۍ��U@y6ڄ^Qm�L�nRx�0J���N`�~�B�3ʃ�JUR{ǍWNxaw+�#P�*���4��2P�������xyU<3�?S�UxL�&�kȷ�<8,\ABL�/<�)���Re��G��Ҩ\j.U<�@y��u��2��%p��	�C9& ^,m!�f���QH�{��Uh*%e�Ѡ�
�U�eh�F_�#&�.���h�
�+��H�CɄb��P�@�m�T����������
�f��g��[�����w�w�w�w�����v��|��_�_�?�?-�!�,\\RB3є��C4T4���8O<N<S�X��>5��@�^(�*IF����X��6��1t������T�D��I���|�H[%ZI���R�@���D]�~�~���'�5�Z�K���yy����/x�#kx{y?񮂌7x/@F�ߓߒ��O����/��o��_�? �9
��
���G7w��<!_�/l/�%W��N.�~ | r$Zq��:�&���jDE�=ōžPB�2q�X#.��ߖl���S���z8�w���������f�e���|x�����9�5vj^�N�J�K��^��Po�Q���4B�A|����rj?	mD�ą�^~���_ԙ�'o!��D�%��>���C�X�dj-�D���^R/�0���k���Ih"U�$h�d#��v�׊�,\��*��'
�Pm����c����Q��.DHǙ44$8(�]�6������[�l�ܻYS�&�==��\]�99:��I�"!~߁����hs@�Y�ףG0���a@n3�e�a(�>���"ht}���~3�Ō�bR.t4���h�?z5���&�e���݇���XA'z�&�f*�N4'�V&f% �j�x�x�>8U�;@�Z�$?}5�ԕ"^Rb�j�8W�^~	��~	�3�?Q�2'��HL����2S�J?��ř�	
�'ۘE�f1نVcq�d�:�@�].H���Sɇd���L��k���_��{���]���]�.
�e�F�j˪{�%$d����3&�&�ޤ�7�2�KM�ne��\�?�v�ff���)>��_����A$ ��W(0�ǰ����_"�A�����r+Gd���U�QJ���f�bv���z%ҕi~>�n�~�����2���gݳ�LpP��+���F�\��ɶ�X�H���pmQ5�9��	.b��4p��g��G�&
U*� �2)Ш��U��	B���GW�@�~���s#"�7��X]�-ms`��];�)�x0-p֕���{��]hsoPJ΀E��BA�>>�ʓw� t�e�3�>��[QLh`����gXf<��2ˌuy���6��$O�$����Kc���Nf��f�y�D�Z ��L��WN�Ȫ��	�I�P��L�*�*�j�~��_eu�ޕ��,�H�jL�6�L�̥@��pVf���7/�m����p�����|$F~1����<�!A-��wS7
}�����>e�pWWW�2>�����"�?�����6���>���Q�) #�z�2i$,�$��-�z�E ��£��w�~!��jӬ��>w7������2�:|�c/��m2B@����5n���7x�-� �)�zٵA��� P/q �m�m�GG$�&�/�d���p& LA�$G�&��h�pW�8sp��A�$U@s�}�y���5h�(�3,�'� P���E��@H�^����� 0c�P{�٢�h�e/�k� Ϣ�0���ю��.h�b�'�5�'.�	�p������`}��Q�} �( �a�����DՃQ�3�]�_��n�ce&��KY�'<ƻa�C�psg��� m�❱	���@�x�AD����_���S�ڜؽ>���p}Q�� �d�:��>X�G�#��I;j�_FJ�'RJ�õ���_H��_\p~��rD�h*j��:B"�/�� ן:d��al�&�v�c�<8[:sm>�Fq\[ 8C��r|6��C��k�!�����A񹶽p#Մk;�0��\ۉ�#z��Y
�#��$��ŘsI_Ҷ,�dצ�����F��\��:H~���f�ŵ��ˮ�!_�8�-�U\[���[���S��Ry���N���{��=�Ӌ���������[�v��5�'mwh�5e���8MY~��ڦH�O"��dm
nK��,�v$�6լ�o�;��x�c5:�o6�)��12��t�6G`Lar
4r��Os��>��Y��@�`�t���:���fԦ\� &&G��̨h�A�b��<Z�gl��3N��4��h�&X�j��#-תB���l��hM5c�!�f}A������iAE��b�����m �{�4�,q:>Wn�@;N��htE�YǎR2Y�d�d�I���9j�\�)!?�W�(�*9�S����X�AW5�r��I���5L>�51�&��D(���|5���Z�6��)�(T�3�ȵ�R��#7,�a��h��>�YS���H�7�
�*�*��iպ#0`U��1ac��L��� Τ5�w���k�T:-���xՁ^�p8m,�gU��1�
6��Z	���?,Ȗ+�y9���� ,����A4fդ3�yL�B'7��P�N��S�Y��H*ZeP°Jm�cL&� ��r����d� �}q͘�!AX{ED�u�f�5Xk��t�"l,Wk�O�����"�VEl�Ԩ�wX�"9�A!ǌ��=��\�Rc���X�V�) �sAT�
�|<T���i=��m�V	������5�N�a5�F�;r����*��$o��J�Ѩ��)�>�._ӹ�2��$�QL��b˔:_�|[`�$^1{d[���!_<ab&�N��!����RmPj��^����j%��1�|�aX"��A����k`���h�XT����\�c%�Ԭ�4T��V�.H�m`F��.Вm�Yl<��IA�:<g�$�q9"��z�n�Ld)�U��8��܁	+����\%gYrE�*��R��Y���p��	��D��M�-�C�4������J���@�aL�!�o��l�3�4C\;k�X��Yz�����H�J�����`�C�)�!�R`�abȇ��3 �z&�h&�Ȣ��L]Z	��C5�:,�D��db�;�n‏cI���];Vh�0�f��r*l<Hz����g`m-,m�\���Fݖf�xU-��A�:�=P֗ŵ�������L8h��] u��рH+Z�Nn�)�8.���SRW`�� y_�qr�����ྖ��H�R�	j-���0�B��@)��j�+�x�ZO>�0�~�3�@Ou
��\�3�5��>U���Z�Pq����X��x�~��-��n�_������78P�OjȆ)ӀKC�W�Y�2����QA/�P̃1�uMó��[�	o4��@OMx���v5�Ӝ�C9t6(�W@��7�j��.�P	�!�p�
%0��ݐ�����q���Md6��r�*��z��1n�/���q�u�K4�����:�nх�u:��,����	��p�0�:�I�('����|��AcJ�W[�$:�� ��"�oჵ���e�yV���G�����Ś���v,&���Td���-��������7��g�(��b,y�X�J�3	D��luF0����#Ȇ[\c���DO�-0\�^�O���w����,#�����R[��ʢ'�FN�,_u<����"�b+�mᏥ������������.?���j�nL=/��]k�{�z�MY��;`_PZ����8ݱ����|�х�aѪ���<XS���N�C�5��/�\�Y��"V��T�a�H������D�4���Y�2�'�-rq~_LZO	Y}��hRc�7$m6���i��8�T�����+����mc���}�Ģuq�5p__w���L
�b5B���ੈ�,�>�9��N��ח��;�h�۲�<u�圾X��]6��DsJ���c$Y*�F���%�tDǶ>�˵�ּ#�ʄ=^c�����[q�$3I�Y,��,�I���jJr�ַY��Oh2�BSj�9���� C���w�W'��;]�(bsC>�McÙ��>9o�b�>5�XB�J��*rN��k +,��\�!���?�9FN��ƀ�2u֪�=̏��[�x�њ�س�=��s��N<5v��;�s��X�0����m�Pq���5p���@��rP@�c��-�Ԇ2)��:�wc�r�˹HSq'���O���`�7�R�]u�l�M���a�X��'��k-��l�u'M�ٲ��:��H�gs��Y�8��ZV`�-�niزI\�-$��I�+�gW�}rB�C
�/�o����w�Vx�m�.k�[}Ƣņ4g�,X���7�r7�'c�5t����
�?9w��,��&96�C��SO�:�ɹZ.9��|
���r۫�
��u(ᬀ�.��P�	��'[�
E�$�[�=�Y?T��:�]�,�Ob��Q�ޙO!��̰w9ɖ&��qQ���}��r*k䱷	=91l}���@s������{�c��ֵ%�,��(��}�����:�u��5�z7k՗�>_�mt�%aeao���G������hIF�PҺW�&fy��}�ԑ[{w`��D�J'�dG�?ب/�{?Qז����9��o2�WC��B�����I��~3���*��qDIIP��Jbzлx���j,���l���ֱ�Z�Z�V+�j[-e��lՃ���U[ŰUW��f��l%b+[�ي����� � . |�`;��M ��X
�`1��� J�����&�Z�V��j[�d�%l��V�lՅ���J�VB������"� � �8
p`'�6��� U �� �z�y�y�E��E���X&�1K<c�x�N<C#��-���g�$��)��!n-�В���f/Ic���M�"i$q��K$�D �I�����y�S���J�[A�_������2��(�[o�;-��h�M$?4�E�VSԴϼ�o�v#���l�7Wgf�Ɓ��y���N.ًZQ�H��5�V��x4Fg��xt���&�����Y-P����9[3Q��MΨ�����!l]�s�y��}2����:�x���R����!0���gv�S����xJ��T#��>n�kLg�/��ܔ��*��:
endstreamendobj4 0 obj<</Filter/FlateDecode/Length 252>>stream
x��PMO�0���z�v�:��J|C��,qJ$�Diz�'	�&q�J"��/��k�[�<�7gx�����h&���+M�5�������<zZ-��&�=dG�fX�>w�O7��v�U�Y��U�)��n���t�	�\ �'�)����."�M�~��CN������W6 ��9�1[�u����8Z��1�#��`��`A-���Q�/�b�U�e^a�^�ȋr�f�sa��i$��4�d��FV�?��|�
endstreamendobj5 0 obj<</Filter/FlateDecode/First 19/Length 354/N 3/Type/ObjStm>>stream
h�t�Mo�@���5��eACZ+��4�`<�Lqk����XC=p���y���6P����L&�7G,$8&#�w�T����L�j�";I+o������y�d��AP^w#�0��6c`PF�mr���L�K-�^�	e���_d9%KLRQd`�V��z��-��DӨD�$�������ǭ�v�VV�J�����q��q�>f<p����q%k��Fw��ȑ��V��Ђe����J�&i6����GIݎR�~�l#����{9��A�0gѭQM��T�:�Z�a�����3���6���C]�*ǳ^�<2��b}�����D�����]�U�N+�w����z|�y� ���5
endstreamendobj6 0 obj<</Contents 7 0 R/CropBox[0 0 595 842]/MediaBox[0 0 595 842]/Parent 92 0 R/Resources<</Font<</F1 101 0 R/F2 103 0 R>>/ProcSet[/PDF/Text]>>/Rotate 0/Type/Page>>endobj7 0 obj<</Filter/FlateDecode/Length 3705>>stream
x��[[o��~��`�I"��H)}��i��8���h{� Y��X�t�9����sۙ%i;�E.wg����ߏ��������g��͏�,-����Q����$��y��������?��.�Eo��頷�&�a���l�{�I?ϧi٫v��.)�ݛ��/��ۑ��s���7~�qo��
��%,�_�����'i����K��ʯY�5��x�.�&�
V��x��\&Y�^��޻��7~���{�l�=u��������9w;���
w��3�xew�����e�<��}����n��][�{q�~������I>�����QW��wpK�>�5����?����?��,ĆH#��HGt��7�����G���g?z���2p[�Bp�x�9*����(<�R�PAް�ǁ�������(�=^�\�;J(J���ofD�'y<�U�p�0)
E}�X۰g|zg�Ir!L	�Xraot�Fܒ�/H ?�͕��U~C�x�u��k��5h�|"X��c�tz��W�xXv�	@�t�NF���a���3���pd\,��Ց{��&�Ws�(7��	R;��OF؂-^�V����M��"�9+�	��b&���%����^B(a��Xȓ(ĵw�#0K܈�� �:��0�����r�;<�e4mLB�99Jk+���nؚ�)C+��5�>�N;X���>�Y�l��v�k^�#��F6���"M�
A���*D��k�W+��h���'�D�z�d�`�$��!�+�vfBՅQ暼�Ƣ`����l�F��$`u��t�k$�����Â����'��?��3�bd���ȃ���sw{��Ɵ�O:���ӂ�"F��(n�Z�PS+�a*�s���8R��9��M�׎kdn����ck�	��8/��W��8�8�x?�Ȝ��k:�'��HOK������?ؙ�1�(��?{�O�A5^,��>z�d)lu̹''��!�"Q�t��t̔�i�Q�;��&}} 6nŞ� ��m��D��n�R{H��b�ѫ[�q��p`0�oB��@H�K����Ӱ.z�[�X/�lP+�K��K�z�D{)Hh�����L�
������S5z.B���d�f��6]�K�6�A�W���9��ʎ���	��2�',Wf�ʆ�)�$co�+�o��B;a�/��Jnx	_��Z@H�_qة1+��qQQ����˒kZo��}��{�2%U�g�âp����rWᔚ<g�7�����Ɋ�[+AX"}ޛ 
��+cO)8�t� K�'�P�k���5�2$��}�x(C��T�m��j�e%���@��[�ʾ�ʾ��QLN��J�ʤ-f�ў?�Ϗ�q���'�!�1t�(�Z�e�L��E��ʁZI�p�`�F*��������{��p�uG��u������&���-C�S����[��Q�N�ǔ���� �"U�([T���]n*?&���(�wX�x��B� �����&JZnP!g��ލ 2�_�����v��
M*;���ʦH\� ~2��&�f��W�{r�� ��3�+˷{���V�p��-��pǅ>�!Č�#H�� �H=�Gqի�0r�˘[�߭���G*d�����H�L
^���e�01U%��)�`[��BKU%�w� �k�s�c�ӵ��^C�	��d�R<1y}87�R�Jp� )�Յj�j�r��
���G�����S���4}�XnG�I? rD(��#�N�n9�G<3��ʁ��˃APH�����B@[��59���[�E�f�;�W����2�p���YY�}�y �h*kɽB��}klfŭ��b�+yX�VI�;��6z�e����4bf&J�8Ƽ ��<�/�	6�d��zv�1��+�~�Ŗ�󩊭Jnhd^w�
-@kX���*�0�w0}�,�7�u7���&�Ji�%ï[�LP}�4�z��4y��JC�9��9wT�oi|����E@�j��ؿ��JT􋭵�e8Y�u��LA��)f9c>���W ��k�����V:�ڔ�9��]�ZT���>�qi�֘�VS#����\>T�bo�V���AAw��;��pm����q��%m!�ڜ-*���k�;4���ud�� ��}S559Ub��������	uM��p�HK;�d�7,$,�b�h1<��-"p�`����(��I  ;*�����i8�����6�5C~����ẦPV�Q�FZ(-��Ym�7(�������*J}눣��M硶ďU�vˑE�+0�U���l$ *��-خ�nʝ�vNK-\'��
9�EO���l���q�)�6%	��&kx>-ez���U'e��F0��缭}����$�!vI\���1+����zh�[�ȭ��Z3����ظ���MrJ�+lD�*V�@v�:P����]�y�.ţ����Rh�#�s:{/��lpN��d1sm*�"_�ε��bz��w��w����+ �&�Z�B�E��9ţ��$��co�ۢ�L�±�I�da�EW�W��a*��2�-D$By���2�F��MH��L�E$F'��.u-�"�e���E��$��֏5>�6�Kq�kRk��?�
4M�A<��[5O��Pt�4�$k[SZ��?�Oy/�J�����,V&cvn@xƃ���r���,EZ��/m|ˡ�]��LuC�` m��&C$N���]�&L�`��)��(SFr��L�{PN]�p�;��:ỖEߪ��@��t:��ܦ�� ���:qS�Vy�����iX���g��}�~�L^O��:Ï�p��9g���x���:�5_"q�*��ޡX��/�@'��l�q�7��9~���Hedi��~=V�yn���?!�>�u�
��993E�	�F��f�~�����M��j_�Ny�p���� �Q�� ���؇�_���1l|�u�4h�ک��d�ٵB�A����Ń֑�*8cŃ���(���Af�(BM�:52D!��Ԥ���F���r��r��7|��tB>c{5 ���8	`�m:�iG����7�{"�u��FG_���<�Li�H����e�jY[�����o�;b��䥚��:N>�E:��R-NZBkvp��5{�2�Xb�F�jqpX`��忊�DW6����Y����V7s0�`�h&�^s1V��ir�~G�{n��B���G�l���2���)=���[���C�O�������27͎�c<�pF��3�T�Y��h�x����g-������<�~�f#��4���(n��(����L��C��F�UM���$�v!�Y�#~���-R��h�W8j�3���,�͙��@�cr��9����P1�e:̈E���%�U�&j��b�"Q�u1���(�����?�NnS���ԖM�PIkî+��륜�C���?Q���{$}0�6���	����7�6���o�fRv�u�a��+��x���N���QTxX^,w>R%\�R���r.3)��>	�Q�޿N���B�'��X���8���Eǽ�X#�|FY!RX��[�N�����)�+Nj}S���b�M� 	w�T%&�Tʈ�"C�3_�G���I0�Pc��j̠��,vlM#�v�TѠG���?呎NIZ���X'�J��F�|����o��q�A�M T
�*��ѧ~
+g�-���_�O3
endstreamendobj8 0 obj<</Contents 9 0 R/CropBox[0 0 595 842]/MediaBox[0 0 595 842]/Parent 92 0 R/Resources<</Font<</F1 101 0 R/F2 103 0 R/F6 86 0 R>>/ProcSet[/PDF/Text]>>/Rotate 0/Type/Page>>endobj9 0 obj<</Filter/FlateDecode/Length 3384>>stream
x��[[o�~7�� ��$Pn�.I��N��AS����6}�D]X�$#Sv�_�s�vffw)i�r�<��o�����oΎ���w7G/��Kq��Y==>�>��q~\�ټ8�e6����>�s�a�*W�".���Q^f��ݰ���j?�����r�����f8*�z�9��M��n�2��0�l�Yz��fa���ǃ«��/�C���s�DG� ??����_��g��$<��G�l:�9.�"���/O�
�U��k���Ta�ٰ���|�O|6��p�>���z~�� �g�m��3nx�g�-����jy�D��k��Ut�(�ڿ�~:z{v���G/~���qV��BEM�~e�������{n��.J������=%{]�UHtY��EN2	��k��:<��x�a����+��UO�ճ2�f$��=y�i�[m�j^�R���]!:g��v8Es��a+��ܯA��*^���WYLi�~#���J� ���*V
Z&�>l���r�Q����p'Tj��*�L�
B�<�L{j{:�r6s�s�V��[}]������$<{c-�-��dq�H�hdM�Վt��{f4��69`jU�* p��k�W��m�\Uf�$W<�N�ZO�rD�tk<�>�Sg{�`2`:�fj2���m�D�����L"r�L��C��to�f�����Wm�b�A�|��^��BB>�����g"A��$xn[���[�|JŐg�D���M܂�q�| �}��#��C&t<K��~���:v�� T#$�����+��?t�U<x�������:n+��ގNM�I��V������d6�%C�}F�򿽁4.��$ ���?�k��x`<���r�G�D�	���5�Y~%�Ey�~n��X�EL�!h��`�;&�o���@�|��|�5�8�-$��ŧKR2�|������j�71��"E[��V���;���R�-���.�-,{"e2�'��P�᭵y�.4B�!;�;#+�˒0@+��}�v��v�L	����
��@�4\0q:��a���ɂ3�v
H���$q	56̔k�y�1����L�>sO��2e_�ed��rѮ�$�5��;*�D̤�ڢP7�<���$ʚ���ynլ��s�,1%�=cb�H*5;��kb�*
�Y���E�⏯ٵ6`�̪�1  ���v8�K,��9���;�"�ε��a�g	��=Q�&��}1WV)�"�[��A�M_N��`��0%�A	`�EVD��u ʽ�9e���W?�)���&����#�\�����u�|��zm��)�������A�S�<�������X���I��[�P]p�f�v�ЉZ�N8�E}Q��6�_Iф��V	�1��q��w8���﵇��0n��ſ����@ �wV%��"�QJ��	%�H'9��uq������qxn��;؎�TG�t3�~�$<1�#.<�7r�;�K�]���Mh/�]��$E"!S�u��,���N���RΚ��t��Ʋ��������T%�[r�%-x�3��Zr���.�ɛ-�����9��N�Qnblz�]3m�q����i֟y�XN;u�r �W}�WO�Ye]4L���9|�w��k����"$/��-'7���VK��@	%Jw&!�W�E'H�����Ü�0�/�9\b
�K�X�
�s�D�uu��Y���T �4Z2s�.�?�H��;p�1~3cE#ȹt/DUK�&T�ް(M���@��afC%���Y�4�?�Hn�u�~�wO���;�:K�b�6%�\0Ѝ�vl��f�=r�>�0"[�Y>p!�)�F�C�ąm>JO�_	8�<�}ªGτ �5(f�K�*����O�m|��I�� �jW`*�����ړZ�g��\Rۖy�&W�U/e)*�%�v��p_۝g�Q�#_�h���bd\�Ǟ)�٤���C���G��{��ں��d$�L�B�,,cɟraO�DW�����f��_:��X�i��H�'}�3�����~v�����_�FӴ
�C��S�6h%��c/�5=���מ�,���|NjxF��JF4!�y��EJ���%
��?���;��6ܮcTC����9+��޶�D���(J��p#�;��ӭ�u���hl9Y�#Ww*����/sT�9P�b���:� ���NTt���� ?�W�*/���<8�x�8�2�b��i{�z��Y@�����+�:	Bw6�g��b�"�;ݪ��\�R�os��ѻ�'7��m�,5)(7�}��DM�ǝמ�R��7��.t��� /��N�R�1�ݕ/E*:_(?���5%�$���͠�j$!h.;��$6&O�IB|����Z�73=�P�z��M睊�)#�D�<�a���i���r���1����ET�۞4UL�Y���e�<�2�u��)�����LA{�p
}�� ��l{�Rry��p��p�`b"JX��J��X�m�W�T��|�p�d�!����8��;h�7�:?�9�]/U	���e�*��tjާ2B�s�XО��{?J+j�C1u$?`���L�'f��_��v���M�
R�;ME3�嘊��Y}qͯ��#P4�&#�W�1'ݑ�|�ј�׮�ȃ؛t��6�(�B�m�&@j(���x�2}�ْ��kvg�&���NN��(��O�ԙrщ�	��VgϦ��~MS�$�P�I�TJ<ie�8��	D��~���&�/���VܰLܨo�!
@��o��u^˕w3�D�mI"~� ��i�Ŕ�HD��c+�t�T$q�)u�>�n���ʋ����:��i�1��n�횬F�P<�ԅ�ؚ!���I"5w�1�e����ÝuۤY=����b�1y���꺠��,*�#?iUJ�"{6�C��50"".,���P�*.4Ku��٦�m�h|�*Cؗ��Gm���'�I�4��M����е�]K��l?�/l���?��n�L����7��򬅉	E���q��w�0�� �y����a���tn���<-'z�u`����n��)R������-�0�}5L-1����>�\Mi6z�O��h�'{��
�Pr}�(�R-]�tΘ#�:RD
i���P�2\��5)7���X��ս_�^j��&n��|;�8��@��h��VJ�|A����"$Ӭm����s����Տ$]��F�'A��R�	5$1��U��y�2,=������kuTE�_,�F���K���Wϸ�j��j�4�s}�G��3�$��>��/��.��Lc�p�D�R~�q�G��������Ĝdm�̀�ml�R"�d���Z>˦� ���C�S��M���ξ�
endstreamendobj10 0 obj<</Contents 11 0 R/CropBox[0 0 595 842]/MediaBox[0 0 595 842]/Parent 92 0 R/Resources<</Font<</F1 101 0 R/F2 103 0 R/F6 86 0 R/F7 88 0 R>>/ProcSet[/PDF/Text]>>/Rotate 0/Type/Page>>endobj11 0 obj<</Filter/FlateDecode/Length 4187>>stream
x��\[o��~��`�KD RER"��l6Yl�N�����A+V#ˊ#9�_ߙs?Cʱ�(�$r8s�w�se?�<���dx�w����_^�y>�����I_��z0���E9����W'��-�b0��������ɣ��<�x���_��^6�wa�q��,��b0�=���e�<�jz��6����&~?T�|�_W��%�|�o`��>�������`�[g���6~��UV��q��>�������>�]N�ކ/͋A����F��mV��^�dw�*,��Op�M<V�&��ɝt�>%%��.��!t��M��3�Mw���&��"�O�Q�[�c� �]���(^B;?������p��?��V�+x&�_��ᱪ�7��d�J&9D��GA��-���ap�>B~�դ��!�|~�>���f��ˬ_������E��e��;__�gL{��,��<�\��s�k��jūe�n��ƕ*�D)~�Z$TMy�׺SB�l�>�s��a-\��I�;�|FF�`��a�=����=���i='G:�\�Fh?jb��
vI`�����[Y�x	Q��ƣF3���"�xC�^�W#v�)��L����O�7����Z1}7EE�|�Z��b# �]�;*C6J<*�c�&���e��B�� �)h��2��H�ܸ��K�8I����[�����AtUX���_�˴'$���M�D��p
Q:-����D�E�r�_���$fz��,�ƫI�hISfw SS�	���'A(F�NH��]��Qyv��!	�e�FO鄕�O�m��2�܈গ��!K�����A`]0Q��n�a9��������V�	�z�+�jl ��
+޹�t���c#0��k[��NP��Lk��R��:/oN���h?��sD��Dl���Q�l�E$+zN�)d��|xo?#��В�ފ�cN�"��iD�G8��Q��YQ������_��P�+��?ٷ�̭	�c����(p$g��y��5�9���i�j���/��[�=�Ő:"dX�L2�P|�Y6�����߲<�!���xC(2���>3��i7��w��_��62\z�p�Kha��O��JYoپ<V���6j�m;�`y���m8q9�~e��h�QX�S����T��oPr;�t�:�c<	��K�`H��f�r �(E&.y(�kh���h�;J���0��%��Z���>�4]��M|0&��w���zU(k7Ӓ*�U��kC���N���ĭ����%n��S�#��<�Oz�{�oUK'ԕ_�)(m������.7%�[<��o�Y�������>܊�,�ԏlxE �a"�y�A�}�8B��Kk��w��[	~��R
k��^� @�?w�0͎�s;
�L�>]���tE.�6KO#��5�e��|í�сVy����t�*RM�CI�����K�eE+W�ۧ~��a-྽Jwe�TW�,ݑ�,�����D�I�H孵)�$��|��g��}������q1�'�5��+�*��XAj<�([�V
�T��YD����8l�EV�>x��Q~'�@��:� 50���'W�F���ga �K��'��s+��ĖN�SR2�
 ���Sߑ�R!�E��:�?���)L�ڲ֫�ҷ��u$f��'h�6��8�<��o���|��g#�F!��<����a�0�n$�Q�K6,|��A:�{���p:�;A�k�5�=��7H!���J|h�9��3���ɞK�X�<�x��I�º��ˆ�}��o��>�Y��\]����X�	��dT�e��]h�d)�	��pb��m D��rD�\"э�X�	Z�t,�Q_q�
1	8Z�WK�i>=�L,ik�F�yDK�+�%]�������!N�Z�
������ר�9�3����J@�Xba�%�pm%qɿ¶�⟱g�]��m�����#���GN��H�a+�\��O�7l(6R�<H$�Em�p�wDK o^�B�Ι�����ѕ�DTV��n��{Dx��iM�Ge�g;ҥ{�Vωj�#,�`M>1i�����#vZ���Ĕ�w\��3�D�oN�#�ܕNw�p��'|zT<�3{_j5.����k�i��Ͳ9���rW T����!��I�&C�^D�o}v�D�@���}J	-k�c�������.�ِ���j�\���3�h�۸�}�HV�0|�ʍRY}��M����V��e�Ni��� ��N��4M��%]��¯�L8W��$�=9$��>Q#�j(�(�-v��\H�cRU6�����R��*9��в#k���ȄF�r&i#�&^�ſ�ɝ��AlT���2��$>ڎ����LcD Y�K켒�O��
LHQl�t�@���n�p�2&%��ڣ���,����Q��X�ϧaׂ�(�+�#,_l]Fa^Gl�f�m��m��:Ϧ�&��O⵺H��^_��7�AyK2!��~+�c٬���pX�\�֬�4�t�|X��b���o�*ICIM~�*,qӚڄ�C����c����O\����W���\�H�� �OI�L4�ꊳ�����e�`+=�06�OM}`i�؉8�Bؤ�Ň�9�%�?�<.��k+ՒBvy��(��1���R��X�`����N-ʏ�
�깪�`}��<�5۠m��i��-ٖG�]����jXkM��,�b���U�E��K
`M�8ED�N$HjR�٪|_-m/��n�VQ�C?2�"���ݶ��
�o�#�rEG��d.�S
c�f�R�[�RN�"�FP��št�C��y��ɻ]6l)� ��h���i���.�E���9�JJ�yL��θ��R�!8��z>T;�$	i꿵a�p�]�u��Ɔ�b¤i4�M����&�p��]H��6��nO�K$j�5)^V�)7s-8� �o-����Ou�`�a��E��y�����m� 1-�9�h�m�j�3w�,�Қ�)��&��m��+B��I a���q`�Fk����qTW-�g}'x����;蔗�_�M��=o�#�4R�y*�g��$J�YW�WT��	7�L2�$Lt>���6��̵t��$6�.���bӒr�-�?��i����ɘ��L4��'�q��*%���.�\VI���8G'���T���&�E_��H %w?컠Xh����8�@��s!����D��Ri�!;�s�WaZb�A;kpɃ���I&+���	N��D"�_ђi�[�KƄ����yjK�=,eʦ1Mr�xa����$T��F���l	���W�>e���S�3��50�Q8L���`�ぢs����Z[�-c�N�ē� ��D��Ưaɶ �̰�9(�w���(ŽJ�P�~F5	�9�7�T �f2�UT�ʂG5
�m�XC_OiX^�]IT{c.��b�VW�ᙍ��򳎐*�K�S��'���;�����=Ҁqm���x��1���GT���Hزy�I�?��vm �uI��߫]��3�U�9�z��$i+�17���	4����5����;�5�:���U�A�4����{VDGN��ˌ�����ɚ��"��/Ů�㌮{g���A�Jf�爇u$}�3p�1y�ͼ|#ڮ9�p����e�IR�^�xy��t�eʔ#��ƪ쏔��T�LT�������1"y�S�#vnp��ME
�x���?�r�C���_���Tq�"_$��^�cU�)"��qv6}m�C��hdB�KN�� r��� �˒<�;��9�:}C ������x��	F;��#Ӂ4�Q��jPV^�ny�6�
s@L�r;�,o�[l�Џ���SȾd�$^`>G�B���jx�*�2|H�`��wşA�w�e�K7�ʪ#MJ�e��U�r%���M?5/��H��(ڎO>��@��!:�
l �<X���W�l�~�4���E���w��,P�G{t�W���V�W���� 2^-9�f��A^1�k8�/s���)IcP�녰;adKX�["i�;�V(sa�)��v.f��O+'B�6�����qN����p�ZJg�o� ��f�ؙH�8C��N����%�
8*�ޒz�ܿ�Ƶ;8�t�O|>�G��HS<�����+��H=��	~�7������d�����y\	���������ے���5�E�%�l���{���
endstreamendobj12 0 obj<</Filter/FlateDecode/First 5/Length 188/N 1/Type/ObjStm>>stream
h�L��
�0�_e�����Z�TP*z�.i�MK�y{�s�;�;#c`�Za�d=D1��o��%���F�9]zX0�[�l��/1��v B��Ӵ�܃0��.� ���1�GU^LM�H�SS+;�yU�'N#��"��{R�����xP���Ãq�W�6gOuR�p��mi�v��M�^� D�Fw
endstreamendobj13 0 obj<</Contents 14 0 R/CropBox[0 0 595 842]/MediaBox[0 0 595 842]/Parent 92 0 R/Resources<</Font<</F1 101 0 R/F2 103 0 R>>/ProcSet[/PDF/Text]>>/Rotate 0/Type/Page>>endobj14 0 obj<</Filter/FlateDecode/Length 2749>>stream
x��Z�r�F}w��A�U&L�����$�nRQ���u��>P)��"�������N� �-ؕ*9 fz�{�O���'Ͼ���NΏ�g/�O�,�����$���i5J�����iQ���nO�=�c�i6x��i9�5N�|�s��dp�����HǃIR��`���x�Gcr7g��[|{/GN��փ��t�����"N��#���}��%K�S�����)��������͇Q���Vn�{TdI���|���I�_�̽%�߰����-�,A��{o�|O{�����c*��B���{Z��;�oɲ4�G~�K���/���~:y5;y�ϓg>ḳQ��|ܢ'������n�!m��`�ǎ�=��flp=�+:�5�jœ���^A��_�S��T�K�Mۊ������!:�.΁��0t�v�2�GRna/0#��Ϩ��/b���04���+�:�j�<0
��pUf	x��v�Γ����M?��Ըv��� ��#ߠ�Wr�H��H�	Z���,��=��䀘���T�}86�:�O��;|�䰽K� :P��
�A�@/�Թ��q�&ޓbGy{��3� ����3ت�(J� z�c�6=^�g5]Nq����>p�ѯ�tꂯ�b��n׉ö�����0n�&�W��Ѱl!g��4����*�O�C��n#8µ����p^#<��ނ�.�#���>�W	`p�KF���p6�"M��)>z{���Ԅ=���a!�+��Aw�z�)Z�˧Lg�	̿��tmUC4�����E2�,&��(KFs�{�S�{�*;a!�Ex�	{t�~�v�KDd��-����V�,�oOLbx��$%+Y���9G|���:�R�n�IdErun��,�D袋pQ�kK��Uj$�}CQ�"�J'��Fw�S,�_�+k>Cp(@�zKQ�b����o
��  :u�8��~�5��)��an�q�qں�M�Z�l5���~�XV��S�/�:�&n�5XCw�~�h�B^+�X;'�<�ոHG�/k�ӧSO�*�Ց��O�$y$�9S���S�fF����� :Mw�(�|Fu�%�1����鲃��t��M��)}4pt��0�H�%���g͸�!��a�n�:I���9�y�+��i#�py�:&�"v!�dOD�0ДZ<����;��g&���|�f��25���k1�uŎC[ِ>�m2<}݉5�����B4	��j�d���ڳ��U�]��
���kʮp=mR9�!%�{�X�n�u`�<�
4J�s=wx��j�;(��ɸB�n� hOq� /x��~�$,�6�u$3_C�"�+b+�e��(@���d��ǆ�hq��)���:B���j%Fh�C`^��uFnH�c�n�	��(�en4?/,��a�w�S�2{Sg4Fvc)١=��x���L�эaM��ΓW�X�mL�A�~�eT�3".�v\�1���/��ŝ��=|����s'du���v'�H؄���M���4�xR��c�@[�T.�BYM���5a�����_��9M��S!;#�_���4m�ГIU`fEu��d�=��ǠGvBװ���B��oA$9�]Q:��m���ZI�U��z�j).X�D��
��:i��.��S��3�\�a�"m��J@J�/aPc�H#	��~�� !K�Z�b�a��[= �0�m�A�t�͞קJ�V�J
� �sȼ	��UYp#r`.����/*��/�~�� �I*�f���aDu '���^#���f�-��?�Ox.�\���	����fnEHovEg��R�iG����
����JڴT����STd؇rxU�nQv��E��j.���BwE���3����׈�}�mT6��1 ��2:�F;�HKo��>�\�W[�G�٠<uo�eظ��|@�lCQ�´�#����a�M����^$���l���2�=�F�	� �v��ϸ�#Gr���B��{���_�C(g�����6�4Sdi$����v��ʗ�|SxMP/mm��<�i���/���h�۶+��@6��`��Ϙ����O��N�֔a|�n����Z�0�Y�x>e륒D��
��a�צY�Â��Xf�����,u�Y�w�m���K�g.���n���V�� t��:��-�:|>w�����մ^t�K����zg��O��2#(�DWp7\�_6
���i[�P�	䦣��QX�f�����g����@�I·I3�~��c�i1��vɕ�ig��Y._z�����NV���j2��������HS� �S��f��>G�q�ltˬ��g�_��V5+#m�6F���ck��`��h�_��Y�����%iA˗IXTf{�C¨�v�3�fgO�Q��l:1�^�]��Ͱ :JKmrԞxP@=�J�)c�%h����w���B�	8�Un�ic��Umؔ�!fD��g�tt�3�3����͊�߈_�b2�x!0U�%ߌrK<�D�CA�n��Z~��x1��Mǅ@��0��E1-l��Q�W�:H�2�^�oc���WyZ�m���^lu��c=Z�
�zSY�K �3�?�r~��O�:�-��Ѣ���߆i��ջ7I�U� aY�a�Ҷ	���{� �?�}[�����x3�6E�}ל}�.K�͇�]Dǜ�"�X��ёy�[y��r?	cop��� [�rݮ6m�~�x�?�k#����s�
endstreamendobj15 0 obj<</Contents 16 0 R/CropBox[0 0 595 842]/MediaBox[0 0 595 842]/Parent 92 0 R/Resources<</Font<</F1 101 0 R/F2 103 0 R>>/ProcSet[/PDF/Text]>>/Rotate 0/Type/Page>>endobj16 0 obj<</Filter/FlateDecode/Length 3297>>stream
x��[[o[�~�� ��<@���ٷ֍�M�6����"!��R���;��ٳG6����s�ݝ��曙��ۛ�����������wy>�����m��T�]S䃦�����o/��A�d���_~�e� ����7{�d�v1�~�����<{���j0��!s?�ޯ����M���zG��v�Kw�,��Ox����qBfY������v�O��"_��_w�kw�"���xP���M'f2hz����?�yj'|�ﺩ��K�b4qK�A�<�Ӊ����(D��RO����_��g��;�{���^�yE�|W^�W��N�Ge5�������u��V����`!��'\kf���w������7���y��ǃQA�3���������p��7p`�N?m?ߪt3\��M��=���~I���	��8 ����~[��l�.�ޞ�t�9��x��{�!�N�{��28�i������m��˝��8ɘ�a���.w$��������"�ݗaW�/C��8�f�?w�mA�S��jo
h�8�(j��>�IE�qdW�"�O�9,d<�3m�w� ��/�>�$��͜�DTh0'أҠ��_�ك%$v���n"����wڀ�G����廭ɬY��&zED>(ձrP���X�{|z�v����ڒe�DS2��O����p�y�)���1Y��@ٴxD�%��3���VbG9<����4nѲ3��:�������h�x��;�   �������D�Ib����� �-���Ҿ��J����1��q��gzE��	:A����B��!��a�J��]tbr X�xR�{�:qXb��~����\������&S�e���6!��^����ܝ����[`i�|o�rE0��$^�8��%햓>�o�16C7�;��l(ӌ�7li}
6E3q���y�<'��2#R��+�S�]�륒�zP�FR�삿���n��w庂p���ES8�bb� �-�4W�9Ṁ�Bi_`� MD�z4a�SL��x{�=��lL����4�� pH�+у�[���@J�6�0����>|5��'��c���a Yvf��`�&tK��h�*���E�!Sa��7?r���� k%R�M��ج U�	����u��1�-�j�6D���l1���;�z�� 8�)�1�Ȏ��9E���>��z�y+A����+G��w:��lr��N�
U����@gZ�wWZ�uO(#��L|98�� ڸ���\��Mkm��"��e��HQ-~�e���7��������ȦH��4j�3F��bd�Y�4�"&�����݄��JQM"�A~�pF�BlG���Ht��*Ԩ�cl�L�O����<���9@���(�Đ�_�����>
k����3"	A
�MV���B����$I�՞��J9�*Ɇ�i _\&���G���T�	q/�=�E"sJ��I+LL�_%9�b�^�: x3m���9��l�:���Q�x_����#\��w��*ZA�:X��j�H)T9`�|��2 ?�жu6
W�ۮB
�n�R2��&Cq� W�$�P������Lz�b���l���J�g���pՓ��*J�q;*��Z��M���^rf�p@]!����|՗�R���<�$T��T.},tĶ���F偅�`˱6�o���ʚ���C��$U�JC�� �� +�혡�O�M!�oB�e6�ܦ.�����o��+ͩ�Ҝ���/�v3�z�%��%�%��p�)k�o��\M���]6���������J��r�j5~t9��kGQ&��e�ߠ�|Fp��»��C^��ט��,
*�x�jh�Yr���K]���zU�i�׌"�7���S�h2�����Q����NL��SH��`�!�
�)nF�2�)N��4����B�v沑5�����q6��ڒ�t���������� �N	y��6�R�	� X���ut�.��4�q0�5�S��*T{}����p���Ԩ���k�#gO��C)��n8�s�*Jr;�R�D7x���i��Y����[�KDB�����rl�Qv�CVQz
e�7�l��K|<�%W��"c=�,��J!���6q�Q�Һ�!Q��;.�m��Z�F�`̵�Y��9�|�6ֶ`���T����hKȩLcl���SmU��1�����hT���a����a3�&w}wqw\�ެ�b_Pg1)B)f��/��p�Kj���%:���D7p���:���{n�L�qe'���6�q_Gi�.>�e�����(���de:A:ꦄ��o�P�R��c`Q΁i+�W"�2c kjơlzL�
-Ce��OM�aդD�1Sc�!�J�N�v$r�A\�8���+�q�$���B>{`��T��X��0�g������;ք���mZK"�0ED|��E�{|�u�%��
��WZ�v�]V�&S���D8ԃU����n���9~+*~	�f��{�{�ǐ�\^5�j�|C�8ߦ B��5K��T�I�7S|0nr�����2F:�7���2é���6�i=�͍9�+�{�ϕ��p���Z�2����C�].�^.Z�&��S��z;��$�@�-1�i��;�cs�B'Ik�1<��	��C���� 0�xa����2�8�3!���139�=�w\dO�q �tK�]T6Ka^v0���Lb�R�.���FD:}H�dɉ�T�m�FȆք�J� �����t�zrʕ�
ɨJ�l�ɜR�[�
s�����'���9fyI5�#�]g����P�;gs12�7*X7��L��q�����f �I����Q�Vk⭺I�.:8���4��K�L�7:��i�K�3v-MQR�x�4U�oϺ�B�ښS�*U8�#6��ٳ��~}-��;��Y��IJ7pRO��>3�(j�3)��h��R�6�+�!q�)}):���jܲm��/Z8�|��4y8�o�;(K#�4Ap+��b��?Y>A�I5�L���S��hAZ��ҁ@i�cIc(r���j����aot�Gޫd{C+��5K(MOy�tn�O��"`�9O�N��,�*CLՄ�T�ZydǸ͢S���}&����`�;�B����i_�_h"B%`F�Fł��D�V����ks$%DR��~i�P�*ݞ���iHU�4���?�j���}���"�p����Q�S�řY&�N�]F�D�sT�[b����!��J����#����O�G��8�a�{F~�@2���^��~�k�?;�Ϙ
endstreamendobj17 0 obj<</Contents 18 0 R/CropBox[0 0 595 842]/MediaBox[0 0 595 842]/Parent 92 0 R/Resources<</Font<</F1 101 0 R/F2 103 0 R>>/ProcSet[/PDF/Text]>>/Rotate 0/Type/Page>>endobj18 0 obj<</Filter/FlateDecode/Length 3662>>stream
x��[�s�~���M^	����H�-u7�$Nm՝i�J�D��Дl���.�,��Mg2�ǽ�| >`��Ϟ���lp��ݜ=���yU�����������hZ���q]��������߽ˢ?,�^Y��Q�Ǜ^єU�E]�zo��??�=�8{���'�,Q.1���/��a�ަ������[��)����mQ�qoQ�'�Sx������[�}6m�,�^��a�A������2���U=���Ɵ�i�l���a^�]�^�:�u?�c������bvvY�Qa� |��
h�Io����s�*�"�(i�[my�&�2����Ƞ_������j�)���Ґ�2�߬�L�i����b�ai������3R�ۋ:���l����#b��� pL�	�Β�Ԅ?
��$LrñsE��ir���_��v�NpV,�he�0�J��?���&t���nBQC�dX�U��{:S�L�	�����v��e�K��wZ(v@P'hE��ڲ�Vf
p܉��0��x�;�t�I?�.sԈ���HX�:�Ny��4�uV_f�>U�����g%^�K���T��'���(��r(@�y�B4Ky�G��-7E=bq����I��S�u{�0A�����/�ڷň}ϭ��,�P��b�(�X���{�ek�{K��
g����H8�#ުI<bDZ��3 ���]����6���0�+z�g�G�F%ª_��A�2	v�[�������.,9��$U�-�O�Oo��|M��UU�G8^�#��R`_鳋��W��ŞE\(�+�̸��w����d8�'�	:P�Ѝb�4�`@bS���ө1�G,��2Q�5D�3g��<����Y2�إ���w��g�7@{����n;�{��TEX+ڈ��  �� 
���n����a�+���N ��	B�w i/6F�����&���7z)@��(��8��^K"��|j����c�,-��k��{.����y�����[��7��\��-,�B���2����� j� A=�~��Q��`Gw.$�k�Mg8
��`vT[��>�����(� &��Bj�y�>�i�X
�L3+�-�+�-8��x�4� ^�[���o�����Ű
�S߂�e-y"ÑțZcSIT���x�;�B���P62>��1�'co��?.��7N:F���$�;k�C�hn^[��n ���K�� ��΅�$�����F2�K��WKV�U��p�\T�w�-ڻC�hB�׳�L���wYᛦ�`4x#�'�/%�� �NQ�]\����:;������>Pi�����x�����d�"f�췣i����R�Y�{MS��4��E �@'��&�(K�_�F�L����ְi���>W!���,)c�����Vf@4�S����HQ�?�Q���\]Zo5۵�#����_��8d��A	���yC&.�ĖK�i�mrj���U��z�&��P�>�M�I�.�kU)^���;�23�J��k,���[`�bY�a>(�����B���bY���	���ꪒ����I�@���\e�T�
#�6�0���WFԠ���ޚ�f���H.��e��&�R��֯)�4aÓ�RqSq�/m6�z��5	�ùd�L�@���py��^�G���B		�3�fJ��2t�:_�{0�V���F^�T�n���y��h&~��;�����L!R=���seD�i����]�������%�>&h�Z��c����ƻ42~� C�l�y�$�59')V�9�CR�gy���d�5�<��t��,�v��7�d4?x.:7ɴc�sY�Q���tr���`K�_|?AE��*g�� ���؃���j��`��P����甾2nzX�=�j��6%�4�*ZC۪ps�jnm��N����%g��)�ۙB-����z]ھ9,���v��u�a�!�dO���we�,ס.=l��;�����4��!dY������R�ͩ���H��9A�&���R���)X�������Qb�\��Ҁq�gy9�FyX̦O;XJfOΖ��&>��to����'���p[���o��*��9��Y6�;�7�x%�������/mH���t{�!֌R�t,Ѹ6�F�aJ��ܺ�J��<[~Y ���"��;sbO�i�q�pg~sRi�"S���$Y�_��w��̝E���ݍAuF1L:E�&i �z�����V�K�)�KD.cg�YO��P�5�$$�aFr�-����%6�)��)��fM�Aa!)��He��Vq>$v��"ƃ0��WIh�Srͳi�m#/^XTFߑ�7y��Jħ�zZ��D1>������p%�]`X�k��;,sL~W�U��dU/^��Wn����ȗ��o�j�5o^���*�a��G�\ph��n}�{#��8a�b�xE�q��K�1���ڌ^У_�&+r���=5�x'�e<)ǵ/zyw��gfk>}�ǨY1�:��'J�٩=e%@�/�'Eٍ`�.Q�7������5���� LʌM6zcxx��5t��+��:2����]�VޛCJ��B���ٲ�(UX���iG�T�Ŗ̕Cj��\��9cu�ǧ��΀Ǻ�"����轒f^���4��2s�������FD��G+'�݁@��v�,��B��muL �,����&p2w�Ép	^l*!�ݾo��� 3���-��<�_�3��륦���g�ʑFw���:��t[Yg��[�m>�9�:)�m�М`+U:��|������R!��&q�}�>��_"N��No[O���4�}��; �#^?gGK3�RJS�����{˟y	�M~Ω5��ϒ�<��/� ��нߏ,BvLϊ��Ejn��'�_?�D�d��̋�q�hO/s#�]:;	�O�{E�W�ҬL3�c�h���^e�;�������x�����JL7�J`�*]CQȮ��1͵;hC����Ň%�/�_e�9�@$K���k!cQ��~F�Of�|b��=�'�^����ƵY�����P�\>�}*���U�G�6*.]�@��I��l��;T	�G�Yܡ��}���ct��5��\ٕ�_�se�p-7Q��6D-���}¥c9���q�3h����65�?��||����4��u����Ta�|�##�X+�+�sM*�s�J]�9{�C>�Y�ړ�߾;�z���9�\��m��e��MxB"zk��Ҷ�v��S�.��n��D��6�hQ��-�۴PMߕB�.�t��]���p-���#0�h��"��j��C�޶�ά��u�}H�7L��#и��ˈ��ߪ�y�r�!��+��j��]A�I���w��%^IF��^�k������)�V�yL���!��#��(�"qx0�q\L�*^�왆UY�G}g�[���ܞ�T��,�۟��/`%*����X�Bֶ��h�#���c�4���%h�W�*���"Qύ͂�,��K�3t\)eGU*�o���7������ �_��G�=~� 񃔲9>a�Ķ��-8��@��lX� �yfc�Emf���<]���D����̛NQ%��	.Ir����N�^�t����k�Ov�D~&����tr���:e�G����ܺB
endstreamendobj19 0 obj<</Contents 20 0 R/CropBox[0 0 595 842]/MediaBox[0 0 595 842]/Parent 92 0 R/Resources<</Font<</F1 101 0 R/F2 103 0 R/F3 83 0 R>>/ProcSet[/PDF/Text]>>/Rotate 0/Type/Page>>endobj20 0 obj<</Filter/FlateDecode/Length 4745>>stream
x��\�o��.���/��!{��N��M��vm�%JS�R�����v�x�t���3;��������ǳ|���y?;��K;7fY7����f^5�Ɩ˼�Ϗ/g??���zr�-�e���]�Ov��ݲ{r����MV.}t�ϭ2c�}��L�l���G�ݗm�{XS>�����Ң[X��6;|����f��Z3_p�+�F�n�43י��[�����)<A|� �3���t�?"�eV��w���unm�!k=�+�K�l�g��	��~���HS��n��~����$��V\�_�%qػ��W����+��3GԸg.�6n�&3~#�&��������^ώ�9;�����x+[���mXui�6��Beɇ�z�zz�U��x��I���]��)nyͶ���Tޣf�⿗������/�+�q)��ߜ�����uW�m���41����sMO�?�R<����z.��T�9������Ĉ��!ݟ�K���l�W�5����a\��H�h[p��ԧG�5!P��$[x�~��o��ΐK��g�m嗌��>xK�ܒf
ُ��'�%�U�q@�v��rF������]Q��go �A<�hADN����I�­�6�2���%�s�X#���(��؁����ef��I83�9Ex��E��pe� ���o¢�M� Z���c��F5-�73n,U��l,���u�rY�x)�.�%찲`^����Ȓ0��(�:$R�����X>_�8�3�g��5��/N� C����n@���;��V�֢ns䭕?��˝J'<~�����gSt�h3���c���J4���ط�Z?��̪����anD��&��"��*9�#G��}�������<p��L|c�;UyM,��Z�2vT�7��7��6 2Ju*A���57�'i���M{�l{QPŹ�:�=�xA�GK�ez���w�琽��$:B)�,�ˮ&m~���Y	.^moPׯQ����i��D�k�1� 1V�DXT�W��|��w}P��7��XOH�V�4SBA�a"}�7L�)}B��B�`1���8ŰX@�T�z�E��4Nit�����ء`�k����������Q�	�7j��� �� ���&�>Wֶ/���^q�^����g�ErEk�i*�+Fא)�4Ρ{��5{��Ilr�2�K%u��� �5F���`�\��_i�`xo��TR�"ڤ�Aq3=yx�j\'��)s<��%6��dw�r�C%�N+`h��� nR@���*lځpO��q%������7���L�3���qĿb��H�9�e,C^�-Q����퀏�*%����p1�� 2ޮ8����ET�b	�]6�up����n��Hxk K� ,祇;�x�������N�[�2>��������4X�O����d��@�1����K�s4���*���e���R�R��
i�R<����ꦇ�.�̴����+�@�tm��,�e�!�#�w���(|`��;1�ؽ��@4�-r�GQS�Ǎ��)� ُ�`&�A��L�I @�q�4�	s�àN�J�ؤ�{r�Čr�o
���&+tЋh�5�ؿ"�;�=�G����@^��薖�|4��7�e"}��K� �K,�8?�wM#�I1�pI����b���E���8B$��X9H�3E��՝�k�Ҹq�!�N��'�2��+�j�A�L���r���"?�|t-*)�r1e��q��zZ*� ��.�[�_
�;4�9����2�Ԣ"��5aA|�\���-�%�L��2S�Ӣ�a�x�Ry�9+�\��Z�	���u�q2|�(�{���u���Z֗�%n�H)��
�Q]�?�ȩ�V�xT� �I��o��/��&z�|�S�PgK
�R�G��?�.�X,0�zx�_h�)9*��P,^�%<�^N �JP�����$�'D��n�m�.,�P[Qĩ ��p���4���� :�B���ʅo�\�ώ���=�,dhVB44�������haxL��J�ֆ�+�f9ӡY'�l|Ј2��_�Pا��#���K���
��+L�c�@�R�>$Rғ*!q�T�Or���_Q�������b{F��25����a�h�0�z��.P%���CѨU@5	E>�߱[�9J��&b�����.�RtU���d��6�b�{Id�I٥s�Ύa�XP+O�E8ڏ���[B�F�kHg�U(�[�x1��:�^�K�b��]�V@�X�y@��Gp��{?d�8�����}�c����x�i] gJ6cy?��l��>O7S�6M1#ۆy���."�e����Ƈ��'�Q���N,�D:.�����6�9Ѳc�n��AT���ɂ:��L�[��*���)�Z'�G���Q��m�-0�D�q=��I�x�{�Q!��\a������J�	8�-SDP���d{�)��������go�?C�a&�nO#E�&l���F�o�̀�N���"T�S�+�J���>±�:㣁+u��i�hA�
�g_p��O�pH'j�@���7�8��"*���]���%y]��QZ-�6m��l�f4���L^��,~d�p�o�"��:��P�4��FG������5�B�R�����ʽ���_�⚆(ȵ����!�a��) ��}�(dP�c�j`�g�ڬ�(�&t��dE�hn}���x =��l�:a�<tz�}ĩ�z#9G��d���Ŷ튐Ci��gs(|��R������m�P��㓠�����l�0���#��n�eg��K�vͲ�ʞ��i{)AR2Z�-2N��^����R:�S���g�nY?Z�e2��'�$��r(��	��$��L�u*��Y�m�e�`x:/��j��
�U�~�[FY����ڰ��l*��љ�M2���=��m�=���>��Hp�1m�1طua�ec�Ts�:��P�j7x���cc!#w }�-�=�U�Q�ZN�:5�7��T}H�F���ۍ�a.�I�-䊢�������qx��Փ3�	:#��PP���cze�i��
�Qt��A���3�ԭ�ou�*�]\�`M�H�h��k*ƾ&�I���mRC4nu����<������Pj[��C��+�r�
a�6�yT9�`��z*HP��.:���V8IE*D׆^�Z'�������4�Jҕ�����Q��ݥ����VTJ�Hݴ�R+և��g���H��D���|�VF�+�Q{oX�pP�eA�O����0^�\PX\� /���t��f�rQv��¬�^��Q+m��cN�t)*\S�r�����#�8�J���d.��{��im��S���.Ʀ�+�}�h���o�1;��㛙����n��<7�ߏu�����4@o1t
�-:m����������;}	}��ۓ
�f�@܏Y��L�����GY������S�s��q?KxG�Z5����!y1g�y+����Ax7���3,�"*��`d=)��Q������p�iܢ�z|��Y����V�M`��0����$Z���T��N�Z�Z�g�/����G��c�FKFfyxZ��ƣ���{�H�"6>����y�8��L�/��=�T�V�$��O�40}��Q�ݴ9��m/�������F�%��Q-E9�AiBO߃���e���n[g$�I^޻b+
^�e��?a�o�2��eU��gps����	S�TE:��#�Js��@��0Q$E��SUe�N���K�DǦ�>�ON �zT4#�B�$!NH��
�� ��S]�R+�� t�1�Q�li{O'Qx��7���t����i�f���F�a pY'�ĤJ�����OUGex��4c�J߃�ZBV�n�<G��g�N�g���{��NeĀ�Jj�����% hU'͋`L��>��G�,�7-i)��g8�u�L���#M�47�3����=.�ʀo(feΤ�馮\v��0@*�� P��T����I��r���|Dq1�%����я�f1���
�K��w�2n�U	vY�g~u�t�����:Ufī�D�/3�M�
}���Ώ�iK���{)���k��r�`��@���{#�3��z��[-��~�a�:�5�N��Bm#��փۻ?�F�!��,*���Eӫv����&.�Ci:�#rM�ׂM8^:���	�W�
(l���ͯ��5zy�����s�aq�� �@�7�h�%���6���0�3�f�p�$���%���w��_gGH�w�r�fG��Q�X΋�0��Rn����̷��w�^	���3���Jp��+��M+5��J9��',R֕�iֹ�l��k��{ش�;��*O��0���ߜ͎~���-�EZ��4�"���98(�v�1G�Zx́���'�#k0;gߗ�Fw���u�j����$@���`'�2�_�%aӧ
���&3]
�g[���s>e�c-h�F�Vr�T�T~�/�z����������&�a�����F��|��*[�s�7 � LcO0;Z�m�W��30���^R����v��JB"�`�tڴ���3�$��{��6����ćw���!i;��	'�����I1��X7�7��;^Ŕ�����ÿP�)�'�iAهz��)h�x��e�f�$h�yi|Tt~rX�#����*����M�0�(�cj<"��pԇ�#�T]s�0�(Д+C��K��r���M�{
endstreamendobj21 0 obj<</Contents 22 0 R/CropBox[0 0 595 842]/MediaBox[0 0 595 842]/Parent 92 0 R/Resources<</Font<</F2 103 0 R>>/ProcSet[/PDF/Text]>>/Rotate 0/Type/Page>>endobj22 0 obj<</Filter/FlateDecode/Length 3763>>stream
x��\[s[�~����uf�C���f��ٙ6��۝i���E�|I"g7���Gr���)GR��|<<x~|X-O���^��se�/�/]�������wC9����ῗ�E��a��p]9.��mUxWV�;|�.|_��K��ny��rS�����Ǉ�8<�8Cz7��a�Gőo|�.�V��[�]�pT��jS�I���'��~,]G�D��,�n���o��s�� �#�f�]�P���e�c^=H:�E��%����ǳL)psRբ�|�j��	�Ծ�P����m��mj�(�:�G����lYy0� ww	��$��?��f������?�Z�Q_(͠��
k��\�3l�=[vXZ��K�������E�u�5���Wm�4$���N�[F�4d3�a>'V��;Q,Z��d��h�峳h�|���8��Є�Q'+���<�~(;����(ڬ�p��N<i��mgPl��5<����`�Vյ�Y���Ec�Kg!�;�]K5�I�^�6-�τ�`_�����\�y��9��g�$�1�/��F��+����;;`8�{��)��y���y���28�D��)���;�N�y���#?П8�����|��!F�T�&��4�?��I�#������.���(2����6�C����8��`�tNT���,�qM>?�r������ٯ
׈��i��Ӭr�Ab�j|�W�f�S\��v�+��|������A#j���1ȯ����g|�{t)/��`䊕zGxE��g;7�*|��:�Q����1?��g�v���f�S ���\t]کۭ(�1V,���(_߄� '	����5<W�z�/ҩ1�ϼQH/ �E�	 \�y�m�>}��,��o��ӥ�����e��;��r��:,�`�v�1���c��I0�� hm�^;������3ͮP��`z
6,���n^��!���Ȁ��«k�lq��U�GW�!Ћ�I�LQ@<��V^{N�ѐ��	�Ƙ�|5Ohh��*��׃:�Q�@T�����6����	�<��B��gs�(�L��AҌ'���� �H�Π��é�5O��P������=�Ė����K�DYR*��9�i����]�M�����ȁ_�>J$1������h���!v�v>,�s�ЈYV'y�e	���Q��P8��+ثx�0@�b�T-j���Y��р�RJ�7�8
G�C�SJ=W�L4����*A",1a���{���)��tS�Oy�X�(�l3���|�����׽��m��lJ��ҵW�� |!^m�
���ɲ`Nr�s�b��j�~g�Ж�[������*(/��VV6��AP��թ�R�nN�R�Iiހo�U�k���B���;�U�2g>��3�!��P�N��B��\ov07d�O?&��a9�c(!���9D>B�f�x�n����1!�3W�� �ߕ>n:�c"�F�����:YO��"xc/1˺2�y,
̧�0���!HMy�ȑH?�)��곞�8��d��f�,|Hg���g$
0_Y�)I��߫�;!�>p���UB���Iq��b�L��YL�<�L:�U�`)��$�1��h�U�\$��Rig�����I)v�����&���^�@e��?Y�:� �*�ߖ�B]�ɷ�k|�L$�S��~�l�.��N]f9�$5�e��A����9@���3���fw���rW�D�*>��"ׅ_�g_�W��w#�\!���nTRJܺ&����l*�N�@Cb鵪��,E�Z}g).U6�T� �,�Y�\��Pw_W�L�K9
��#R��&p��Lb3���T?��𯅃���i����u]�&fy�]j�F$����Q0C���#�c%71�H��;U!����ӟ3Ġ����w��eh}j���CI�'��nWɊ��%R+RXsXR��ǆ�����.�X��_u�lG^r�O�[j9����<���N�ȁ�F��F�����(���`h���~ { �5��g4��k$qy�VqTB�`(.�=e����1����1��l�<A�����+]%eEQ������v1nh�1�������t�u���A�c��q�k�5C�]��i�sx��6��E�Ar]�ŧ�Ã_7s&�޷05�.i�U���[�Yݖ��h+���U�] &m��M�8�
ݟ��|��C��:��&�i@SK� ��DFպ��Ŕ9�oh:T����rʜ9���º�{�Zнy;hΈ浆�ql=���騻&E:������	�&�D��\s4��HB*�T�r��"j46Ιc���76#� r>��$�!�FBN|���V��&��M�º��C�dͭ�,;��D�<e�$���������e u���������ʎ
=��-g,P���{�o�Ա���	�>��7k�bR���; i����d]c"���	mc�K��D�eMw9V�m���hz%���"M�j��+����}�ʦ^4����@8B�c��d���7:�wӍU����)�ZC$���;���X�}rt��@��w(�_4�,h�&9;�3I�9�!U�ʼ�t�jf��vỀ�3@'�
���$|:�8 �}��ؤ��,�~�.g�
n�Ɂ�I�s��Ĩv��<�A��;�u��,�2�s������ʃc"\
ú�0#�;J�pՔ~�D�N��4�W&���r�F$bX��
	�H/�i�>S��I�a��2��'�Gt[TvegO#k�:��5Wֈz�U�i`PX����#;��l�R�j�0SX4��K�C� m]����n���e !��4\��	�:��W��K��jf����r
`!ಛԯ%,
5�g����h�(�WMJ��J�ԩ7+��ʳ0 �D���N4�Q�S�U%��Zl.�mR�R�w���,(�B�2�(3VDI~�/�K4y�ޢYcM�.�q��庂QphO�y����zwy	�?nV9��v�44a>٩�U5I[�z
L�6߶�	;�Z�w/Q8���7�bj��"���oS��\4�M�fPu����+s�� (�~`($qs*Ѻ)��3�bv�f�U��#�F��Jĕt��V��m{��:�B�,Z骼Y
d�����pL���n�S��6Հn�u��=�H�n$��T(���7�ˤ{o��W�#���̵�5ݢ���������% ����'�`ow�Mӆ�R�{L��S��p"��"�%l�$�l̎���>��??l�î�1'�P�*vx�\j��t[����P50�I���~Ye>�v9�ud��l�̣�^-6$��b��nd�{�%�*�yn�؈�#ܶ�3�CjRÀm8.v��8U8x#C�HQ����b��p̶��K���-N�̩S�&���m�K(�~����)`<�b�#��t�y���r����s,C�U;Z����!i���:�2�y�����\0x؆� /yEk�5�	&S��d
��8�zh&.\<�g��]Գ��q!.�EJ[����[u��������_�o�����Y�C_�P���ځ�ևo�=�{&\գ�3l~;>b�N�n@���ԅ��xs3G��A��q��ԅ6�~�0��λ ^�Vm�3�((ܼ�e�8}\>7=�>F�2�z����>�R\� �gMg�m�u�]5����c�'�����cmऺ�~̎ɜ�݃J�@*��fT�ڲv
iB�����C̝�#��
N��*��@�M��
endstreamendobj23 0 obj<</Contents 24 0 R/CropBox[0 0 595 842]/MediaBox[0 0 595 842]/Parent 92 0 R/Resources<</Font<</F1 101 0 R/F2 103 0 R>>/ProcSet[/PDF/Text]>>/Rotate 0/Type/Page>>endobj24 0 obj<</Filter/FlateDecode/Length 4262>>stream
x��\YoG~�� �eI ���s=:��H쵵X`7�@K�Ą:,S�ڿ~����%M��9"��������?>̏������w�ʣ�Ț����p����ʊ�-ڬ/���/�=[�e]f���ܾ��]ϛ����MV�.�M���_���n�˼0Y5{1/�z��}�峟�Mƾ{;_�{���9��������>L`+7Y�V�t���r�ճ;~u;'b�D*M�gM2��Z�p��cN�(������݈�w�$�ͭ-K�-��|^�V\+�N~���%2նY%{��q��a�L��,�$V�٦��l�Sk7��f�>DW��2�tk�\��ט,7��+N�F~�����$�&3����B�,��}����Z�࿰�كHկ�ν~Au��D���7�cm���������|�ұo��2�t�f"6��;�TZhk��6	�4A(��x��L䧴V,`�r���]c�G+�)�]i�aU�|�J�>/�8L{�J�]�/Y�]u[��=�$�Q���R�� V��	+����q2Ţͳ���S�!�kh�/X�� ����厭ī�{��<T6�)�z�xGFN��;a#�(�������O/��;�3Q���g�+�tgKe���x5�ȏLW@!V�Y'�`=zK�d_/I�ib��y��e���Ml3/�[f��#u�G�:������:�.��^y�t�:WlA�<���Xw�Ǌdo��r^q�~�Oŉ��i�?2��#���YX0v8�[��D��;Ⓟģ�s2A�2�,~�$
�'*]�.d��L�B����h<�2! �h�%�%Ϋ�Xy�!إ:y��Tx�z��-�����<�<�L ��<����4��i�@n���y�؂8K�&dC�q�9��=��d�<+�H�8J�Zdw���LqZ��з��w������]�潹%�R�Gzm�V2U��	��ey��d�26����x�,�U�[�r��0�@x}��Ane,Z�q�Ko�N��ت� ��Uo�ڌ/Q����V�Q{�D��G��++�l#�/�x��.D>�yК<(K$*H.�y��*��ǭ���ʺ�)�'�����TB%;Ϫ��V�.o/�����+��Bm�4{Q�[Ǒ���K>+�D2�-�c���s�Dݹd�ӭW!�,M����l[���I��.M%Wڬ�ZDc��������]9�E|�b�ЋT���D`�:�x�� �vݵA�ްu�
�ɿq�I�0=��*4�N�ST�Ө#��a�E1T��J�҄�i�q�{��H�Ú�J�Rn����#+���u磻��lB��C'�tm%狩�g^E�y��]����D�56V�{������s
�;���>n��C��f�4�	�|�-�b� ڑ��|���I�?��{���5!j�f���fda^+Ri��(�������3���qΧ�c �}��(p7�Q�=�V(�3q<8�x�����^H�J����;Ӿ�w.ۡ�Ȏ�aoS��:�O����Zu�=��.xx6�RYiw|̘ߞ�zU���-�Pax��]�b�vt����L7Z�����朆����-��e�7Qh�*��� � @8���].�"�H���x̅�R�t�aJuӇ����޵q*��Y����d�f4\F �n�f�z�e���A߆+їot��~��@��z��b%��&���t�k�Qw�
^rt�	[S��㠎-���j���* �H빦�������SAq�>t�oz��4M�G S�Uu���j�
��/?sc�3�CC"m�+;=<�q#�h��s�&rBm��Qq�*P�ޡC���T%3���5?7�t��}p�КJ$[5!`\U�Z�u��t�@��塞VA��WӉt�lr�'�����v��D�T�W��� R��f���!�mؔ�s-��4m�>���B�U@y�eB� c6�+w�Ğ�l�%#Ea�1ũ�[����4����8�aG��pt���J �0��aTB��K�JW�$�M�&6��P�N�<�.j<�nn*�N��]�H��-�_=�5���j6z�6��l�SBl�x� ��;]��KF��"��Rə0���A-����L� P*�\��Α��D��gZ�)�8B���a���Ij��<�h��!H��9�ș'R�
���׭��X��1V�R�5;���9�~�g �+k�?�?
/:&���,(�iV*9����ɊFa︻ �F��Wi>�J!]7lߒo��4��)R�)ܜ�c̔qv&9Z����h��ՠ�/�u,�\���Hi?>�#+F���>@�(�G$W�#�sFG���%@ab8�ax[D��[2YJ��s"�t�p��:�2�T���N�b��mt�r��G�M/�2�'����ƅ��b^�Y�E�a+V5_���_�B��_�����+K�<=Ub@�ſ��z���p�S��Qˑ��h����hq��=�b#C���e��_g�WiKJ=�Uv/���u���(��	S.�/��ar\>�ڛy�4�omŋ��K�W��{�>�n��P�M
��C���V�c;Ɩ>@ӯ
s;-�f�ûyO��k��h���]�Jkb�oH�
����	��V}�O@jH��	
M���PG6�F�D� �l�0B��ו;uS<{��RM4v�@s�d��U����Py��0���̜��+��pm��թ'fwcEh5�نr�Ŵ5f�����{���p�J�5����
�T�2��B��Ō2`*[��<�������������QV� �S��4}H�q�P�#���ҥ��,��<4 X拭��]ܽ���9����G��#�I o��g��I��,�W�l�`���d���G=���T*0?��z�n �^�s���s^����P����ĞW>�7�B�=L�����b2s�pz�'���Y	��4�*66Bh�!�)�}��c�fc]�+Ż6+m�ō�~˷Ô��Z�����g6�+q������l����A:qA�.&��r��<���(��r��Nbo��8�i]�`���|�~�m$F���8&�hV8�j���G�9S��:���^@ddl��@tI�)�[}�W��@�#<2P��r��D�<�HIj�A��e;L��a(UӇk�.��!q�	f)��e�h��>������УC�ǭA�!�G�	?F�(+�`6���u���o��J28���Ѥ���E�j��Q���X��N�ñ�!��Qe�(P�X\�=j�"[�;t�g�3�1�����V�!~R!�v8}8D���&"#\�C�7�¡�!����!�����2cjO���.�{wI�J@�O���2�p!�L�W��o�﷈��>��O�g͚����v�ƫ�x�����/��Hi3�11�$I�Ӫ±�/z�Ӎ-���P��>���6���c>�G%P%��,�vy��3�	:�/'����)��cÜ��2]�`�{�%���D�l|�Q�i���u�k�TN�S*���p����)ĉ:���E��_^qC��i"���R<���y�@�i�h�'N��Cb8�����S����Z��~t���a�A�`����l�Q��i+XH��[9Kn�Zw�j� ~Eo@_�77�}a����؇}��&�_֗6	��u[w,�uq�ᆯ����V��YC�~8����U>X�����ך�7lL�#)d �N�~���e��|Y�
��Q�4���e�g�$�bڝ�Ý�z<M�� ���ci���[tz��BHU��aJzP *�T��=�x*,�J�a!�ɤx����l�P�\�������o�'�Fކ�����Y�/���>���{�8o���Rc�~�v��]��r4�]J�bw�1�A'(�G����Y������3t��=�
\|KH�d�"�zt�U�"��I���_��H#�W���2���w�4y�Sj���c�)�~��������ϧÃ��*ssdlX���K��]έ�6��&�d覭胗���۬{�J��J4
�I������m�o��d���.ݾ迦�f��<+UAӛ�d�[�j;y5a�&w��f
뉪RL�L�t�$]�y%~P��ӎ��S佻�JC������	<�V4�e���*��'�ddY�vf_Զ+�"e�BE��!,<�h�cҩC�W@y�wwU���&+���ue7�M��:��t��,��9j��b���F-D��u�J��Cg]�����ul\d����*%���4\�
endstreamendobj25 0 obj<</Contents 26 0 R/CropBox[0 0 595 842]/MediaBox[0 0 595 842]/Parent 92 0 R/Resources<</Font<</F1 101 0 R/F2 103 0 R>>/ProcSet[/PDF/Text]>>/Rotate 0/Type/Page>>endobj26 0 obj<</Filter/FlateDecode/Length 4597>>stream
x��\YoG~7�� �eE q�G�cg�p�
��f��k�dII�뷻�ꪞ!-��E`��������������0���>xq��:,ˢ��^�t�<l�bZΪ�hO?���b����*&Gw��I1_ʪ��nGMQ���e[4r�b4����:ڌ�ux�6~��y������kx��ʣk���-<:=Z��w7�r;	�.��U��X�kY�y���x���xFw��<��Q5�q�8�?hXL�gy�fǼ���8}��vw/�B�p7L5S���&,�#=��A?�f��O�j��cziI4(������9=�\c�k!���t�_��K!n����E�A��j��*�x�3��N�>x}z�⇃�<Ap&'E5��\��0� 6�ټ�{�i�}������5,7�H����(��F�o�S���v΋O$�Fa>��gfv^���3�k�΢O���tE�T��874�\�z�b�^N:ȏc�w��H��Լ!L�O	wA,�J���Q#'��W{���Q����r��].L��(N-�l��Ө<	߿��a�/���|k�}me�7Ϫ2��`.�0c1yDhh��Ã��
[c6��X$���֋�����)���Һۖ��8n�6�Өx����[��,����Ɗ��3Ee������1�����i9��g��AB�{{ǳ�d���^���o�"˰,�zVĬ�n�{�+jo��P��?��5�Tl8gzச8&�B�Ad,�9W�IЊ8���Ba�1����M��<tZ��n,�G&��6.Q�o�'mS���دh�\���<X���ОMy��&��_>����nla�j���_��[�'��B��@�߱y6�|��V��&�%�N\�.>�?`�[�mR��	I.�U����}/���$=jO7���w);��fy�����#h����\P�^A�롲PV=�̃��
�zR[ih٘(�!Uq�*��`���6w�x%#J�i��K���ƈ��m0vi�ƪTLu�\x4�#�R��[)z�~�0�#eyN#�t��VB}?c�ح�l�m��ԁ�&>y�ld�y�n��Ӡ�*��j��ވ[q��"�(?��(��<䉝7ca؊s6�n.�8'ec������_���+��i��wr)��θm��Ǆ��<	��p�=OS���5k�I������!��#IV��H4� ,��/�k�D��q�Q6���� �G������@�q�+{��qG�a�`��c&�H�V��f�=�̲��c��QKx�~�x���5�	�f�;���`Q���&�|�ԋo~�/=<�C���=c�!N�Չ�����˭�l�]^���|����/h��`5�CNY�4č�0,�f��'a�R���v�������/$�ɍ4��oh��6��b�0q~ށ�hsl-���z%����M���Ti�%�fy�7�w`g��"+F�8=�4�#�<��䴷FıYa�#c� 2N��N9|Mܨ�Y���Uꗶ�j��b2�L�{[W�8c�y �f�V�e��[��[�s�R%?�RY��(��ϬT�N�l�J(%�6�l�1�ԅd�MJ�?�(1Qo�G�5.J)%��E�%��f@b7&�w��>�[DÞ���@���j�`��ň�rΪ@�t_Z-J��751�IrB�IQ�TQ0��I�@�Cv/�#}��5�bX\׉��n5P\�f�F�]�bui ������њ
h�A@�d0ꤓ��5Zx�ϸw�,�k�Lt/�x�e�o�b�J1����$>�E�r�!C�(�%�� �{��ƿV�2<���RQ��8�B!{���#�L�%��:Ӧv�"�*Iϭ��ye���ހ�2��^�ƍ�F�$�y���B��XQc�{@1F��3��@6�y��w$����D��Oıl�G�]~��{�����݁�H"��>�M'?��51 =�2�6.'Jև$s���<�B�ӿYI�i��s� Y��i�B��l�.hhE�����d��P>���]i��5���+5��t��VN\ZI�@�̋����]��{�l)���P�V����n_�a�6��Vݶ)v�j89�n��{� T�Њ��b��C��̻��F��ؐSs�����J
i'�]���%���E�aR֜L��O��|*�S�����������-��,Ϲ��BN>Mq��lN��݉��7F�T_��Q�s��/d�I(M�L�Rr'c��<�v8���"[\�#�T��Ƌ��y�ʃk�	+b�<]H�M8��C�9$�u�R�?	Q'f�g��������5�ڨL�M�P��O�q8�s��tPp��iܾVք�Y�>�,��Y��oJ(Lj������0�v�*�6��gK�×�\�#���b��E��(&����.lۑCk�>�z5O�������
�� L���8��#�;�^�e�d}�� ��|��e��a�p룾�����q�=�<������n����q��6*��^=6�N�hD�6�0m�4~upL����'�ߍi=����3��2Dx^�!��b��,���"����gl�N��Vx1�A��g��_mM�!,9f�A�Y5��|�6_�"��k<D�y�N����
>N�T�?$��h��4A�,7���${ZU�%��LN(9ڗ������B���-��5&BΒ�K�c���@����X�e�@<���<�Q�3W�6�F�@�S�uὄRoFU�R��':ڸ���/eo]�@��(�{-w[�$����y�nVtu�7ǝ�'��v"��q�QY��T�Q��n���!9��hʑ�j,`	 C�Sۖ덽�Z漟�%�)`�Q�\���I���FVd��$���A25�p ��e�/��%��H!gG�����e8ϟ�r�� y�#���@lWY�����&g�A8ciW�"��i��%���TP���l��y���|�\�;�T����S����q�j��7��mX�E�萸�>HG�CL�E�)(u���	m�"�F�:E�}=�
�Z7��c�I[�ɬ_� t$�H{̴uԁMsS�d�PC��*�{w£�|��.ܠ���i�%%������/��Dv������l��R�>��IX��ǚOb���������f��Τ$l�#�k��窚��k�_w0���s0ˏ�k^�`KA�֭���9.Iɉ���44�3�2zMG���d\�%T��d��L�j4�̈tG�2ĊEV|v��⠠���a�����h`(���-67����\6���wv�x������}pM���G���ݹ�� m!�+.��PCp�eoM�v�fv؟b̰�<����3��'\�;�(A�L��?W����6�d�g��CSG��~�Q�����(�9��ܫ�G��Eǚ��T����h���S�9�����~�6�ԇ�a����F/�[?���.�W2�P��{^�����u{��>o�dճ�z�Дq��U��.����N��(�#�Y��^a�N�����-yĶǱ�-g�2�L�8�������>~3�ez��_�qd(8Z��a4�el�Q����O�S<�A�6� A��>���VZ�q�v99�{����{�0�����$�o�2��_Jx�@Z��;Ȃ^�#�3:Q��*�eAT|��Ish��P09�~}�<{�L�q���zc����C�V��pڄÇ��M�>�P���]/V��H�k�x�J�Aˏi!E�>hy		�\w�I�AI��>gTS
.��x�g��?g\���N!��C��|�&#�@����R �n=
�H�
4L3��b�s�j"��99���y��ugZρ.�~B�{�L���`����1Y�����`�J6�3���ޗ���x]Og��Z��Ȣd��l�z[�N��Sy;h��O0�g#�&K*m�Thl�8L�K�����Gմ�g�E���_I�3��˝�m%�x�͡'H��_|�4�1��=�S3{	���ƈ�8��;��L�,�
���$��Д0scua��
c�x�v0ؙ;8QGFp�Ē)x m#b;v�}Q� �)_�ωO6��L�'~����Q��$8�Q�>�'�Xҕ��3P��g�=�2C�K���36SS�u͖�Wfz��t!At,��i���7L!�`g>�ڒ݊��?�B,���:F��2"��q�۵0 Y�
��N���&��Mj)�?r�6)�m�!qH�'����9�B��J�c�B��ƥ�m����������'���p�+f��<���ۻ�+��(f�
(����|��Pk֮#�t9��I� ��&$�Md���p��������l=x|>J}]6z���͖�r�y I�z(�\�4���:�a=7/k_6�ܘ�x�;�I�{z9�M����*�A���M��l�<���35>��o��\�-0A]Ov����s�դ����s����֢�D�?�dj2�5z�]ƶm ��lY�)�s�
�e�r?�*?���Q���a-��a�ٷk�G1�K�L��grm+ڗ�JL�N��>y�sXƾ��9��"7I
endstreamendobj27 0 obj<</Contents 28 0 R/CropBox[0 0 595 842]/MediaBox[0 0 595 842]/Parent 92 0 R/Resources<</Font<</F1 101 0 R/F2 103 0 R>>/ProcSet[/PDF/Text]>>/Rotate 0/Type/Page>>endobj28 0 obj<</Filter/FlateDecode/Length 2683>>stream
x��ZYo�H~��`̓	���!Jz�8� �\;���}�d�"K�#yv�뷻���*Zr��&�G_�ͯG/~�8ǿ�����<.��_\��8n�|\��*����ۣ��g�&/NYQ����lP�����}V��"+���%��٠�ޚw߲A�����5.��I^�	E�OxF�&yy7(d��.�a��M�V��F'W4s���X����ł��i��2+�%�U>EZ�L�^n�&��Dx�H~�zɯ�҃b�OO�ϫ��_'�a=�d��ܾ")�]�*���v����E���u��2r��n�8����i|���D��@k��
\F��P�W
JyC�߰�_Y��?�^]���ы����!���l04.WD�*J�	���՝�u����HhNq'p��k6C�AIgX�@՗(�i�ג�T�no�dI�' 7�	M�%��T^CS����8�˦�@�n`"��ll�C�L���q�6��V�}�l�o�v��S���vmF��B3m�s�P*Ԏ������e�;��o�\���i��$6Ӽd
�z?������#�%?�HJ��F��D��^\B� �7 @y-�´�<>	C��Rhk&L���+�aNv�;�p%/Is��#�bH���UK�[��o���G*؃�b2·��K"y�&�����"�Zatb�`�M@,���u���q1Iϙ��	U�@XnWq�2L�N��)�,%8�5�D	�b�:8��:�\���=VZ_���4��oR���	O1�aO��Oا�cZ^B��$��Յs���Q,e�zƦ�.��/ś�we��I�����1.�Yk6^�Ā��UL���>1��4S<� �s�p*?J0��"�q�	<�+ܺ����p�b&�j\]��u��y�-/����Ǒ�@C0��((F�@/�4#Y��2w)V:�R]��`��䠒Iw��z/�GMs�w�x̲\���]Z�$@�~kN
G'a4��X?Y4�>L�JN�XD���(31�.�C� Ń���C"r�z�����	��$��)�\��ӿUK���"�rBPñ����)���]Y`,��7�3����o�ٵXݵ/�.�e7]�Vf���
��G$�:�)�."��U�#�`Lٵs
_x|�iEk�r�mOT�&�|*�bnP2C���s�x��[��t��˹�!Uim�\I�_;�b�[�7��.eW&���!nؒVH��[R�V��U�nl�˼�]���+����p�*����!4~�{�p.�r�S�Yjz�r�	��(�%����b��*~��m��F�&�D|ǃ����P���%�Win8/��o,X 2>��F5���H��L�(RZ�M���+�M[C�o���q6��K}�b*�n\;�ɵ�^������+�I{b�	{�S�K��sV\
�&��	��b��j]�b"�Ս[���3��+I��#,����d�J߻����Q���9�r1yk�c�.��ڌ�%��t��΍Of�w��zb �C�"��ĉp}X�jX\��'Pp�D#�h�}��̤�hRi�P)m��ﺪL���+1�ha�7��Ȥ�����R�Q��;�������Ǖ�E�m���{��I�J�9��Ds=���B��W�}�P��8eM��PgR	��� ߵ��<|��Rx�V�ajCi���mCՓ"�����P�
�b�iٺ$���):,�{o�V�i��|fpoj��c���}��MP[�,*����}����s�	����&�h�6��a��ß%���|7ye�Q�Y1S��4�o�n�����jYzZ��s�d�&��[e�ōt�ƦM�SsU�BB̡����aO+���Vێ�Ѿ�`����渿\8�eXH' �����(��l����{XK0���k�e�A���G-NT��eցz̣B.�`C\*\F�m��!.ϳ*��%�-8"��鲆�T�9�����\��mV"1&n7�i�˷,l7��g5i�y�%��+U�����+�ьX�cd�PqD�G�:��MҜ#`f������p��y�_}����bM�!y���t��O���-$>6��S�;� ȧq�Rٺ
ͦ�ҟY9Es�Q��做�t��<w�?����L�d{ӭ�ڬ�GF��@zo5�%��1���,T�Nw/1g!�9�YӰs�����=��kZ�Q���� �jX~�vp�3~i����@oQ��>���P��#1�g(Νō��r�*�g��J���I�����<Z�HK\���� o�M�!M�T����d�f�������Լquڽ-��Lj_2V���ɇ���kQ����r�in���Щ�C�iW�|�!̈́�B�M^�\�[�F�J|�C�M�Y����"�A�µj>}�W�{����I�	���G��� �T�Bj?n�p �cÒLן�� ���2p�롲򴓊���Rб>JF�D��d�?��B1�s��y�u*М�!iA'���[�|ΰ���[�^U"����4ߵ��L0�}�/g�z��(rP3�\���s��M'�iՍi�u�8�a�?�Yn���[�K::�O�J��4��t��G�k{n���X�,\˿-)uq�C�η z�S��){��Ú�+h��w��W�/F
�����T�a�?&sY�ޮ��@O���A�� ��G��8۬
endstreamendobj29 0 obj<</Contents 30 0 R/CropBox[0 0 595 842]/MediaBox[0 0 595 842]/Parent 92 0 R/Resources<</Font<</F2 103 0 R>>/ProcSet[/PDF/Text]>>/Rotate 0/Type/Page>>endobj30 0 obj<</Filter/FlateDecode/Length 3091>>stream
x��[�n#�}_@� �VΝ|�e��	�/�Wq�Y���$G_��uUϐ�L��k��S��۟���p{5���<�]���_��,K���v{����u��Y}�L�i�__�~���d��i=I�������ջ_��}~�XM��v��'I�f�ߒ<�&�'�M^�����Tn�N�)i\����F����6Y��&�m��N6��/��,�'�0z����f�u�g?��zbk�if��j?R�F8�4o����1������{�m���/��������&��,��
?�;\�#��Y���\y=u�R�I�4l�?un�|��_�����ɂz���vA�ɾ7�����$�����o���W�$d��ܲK�,l3sP
�|Nn��vI���WZ���v�1��*�����䠎 ���:r�U��3�őndF:�F�=u�m�v~M�H���W��{�䚏�{�ґ��`I4�4��w`7q<F�M6s� ��Bf_3,�3z�s�Z�V(���\�n�g���8m�Ꝥ����W0~���Լj��q�c����8�r�o�A*+ӆ�t�&j6C�1U�nWO���	�"X~��l��b�u����@��hG2}�.S��GJ1w]O���ҧg�y��X5nWL=�����_��cRX81R	��h�gt����4� �}N̐v��vF�&�:��`eR;�l�=T���_mO9x��l~��?�vSB�?�2 �_�07:��@�A4�!#���}b;RD�m��:��EN��<����O�����ctbZ�1�#��Pk�	x�Ԃ%��h�gVP-D㖲��Hn�2������&a��]z�3�u��qg%Y�q�ؗ{`�u��~��yQa�P�78�4���6�������ǼF)%b�J�	��N;\u�Q�^ �� wX ��Zt�H�'��KY��s"4EXY�WZN��hxM��!L��푘Ɓhղ�/1�h�9���'@D�;`K���f���3�i�fI�ȱW��k!�H9F^`x�I+�� �ڠ��
N=��lJD[�o��()���F+�{��A'==@�A�M�F�E��H(��"�E��D� UnU����H�˓8�3rғ�����*K���BG4���W�6��A�R��$H"ʐ��Ƅ�<C@QD�-i/L�}`m�G����Ht�u���ߨ�cFze�AוB�����:�ԑ�S����q�����t�=��b�'�E��<]jy3�+�g��4Θ��I�x�1�B�ǿ� ��b�lB��.�^ƾyҚZrB����$�\�-r#z"uNDH15]\E#S�°��],u�j�'��$_����K�"Y��}HB!��!@�FH�0đ�Y�L��s���HAǞm��\�l�4��#��N[���6=N�z\�P����B�$.Z�DÏ�#�g�h�o��`;J<(�is��������үA>�MC( W��%����U(�A�R#QAyy�>A�hz�C���m��J6*}^��T��3��$���%w�sdb6��1�ߦ6<ԣ}�63��gC^g�y0G�#��!36�sT�two*����?_W�{U�@���9�Z�߯X_LK��$�~��̩�!e��/��$^/��q{���"�i�����AY��{�}�s�9�*���k�)���+VQ�Y������-.t$�fn8&l�c�_��RIS\S��k��OJN�6!u��f��$������N�6�$qR�܅5�$5�"F�. ����n4/�J��c��P&�SL|4�!(�8�`cp���+�]D�]�����e��Ia��A���L����Х���rҲ�%O=��RSw n��J�#�SR[2�\�Z�.����p�ѷ#_�Y`S1����vk�D\����ܧCy�T��no�����!@B�2������#md��t�_�c�:��){�F���W� a�`�\�}uM�T]�[F#��L�?�Nf��O��+N?��Lm��y=P�{9�}�^I���ܙ" �/���)��v���Z���ݍ�L3K�\�;a�kt�K2��BR�M�,MsQ�}�+}ǹ�kE�Q�r�m���d�L8�W�ܩNc"K�dTS����qT� 0���gC�S�F�uց:{���+�O	ʨ4qԪTp$Z�*�oR����UUv�
��7����L9}�f�`>�`�$a$��}s?.mW��+�oW�E_�֐�NM�ya�׸��:�	��[[ŵ�/�A�l"M' �H����Fc8�ݷ���J���[j�z�3���vɳZJ�@�q+ml�&]�B�Tp�~ߨ�3�<.t�αL���Z�����.^u})�^ʙ	m]���;H��}d��JM��=uT�S�e���hG�Â��GB�;x�����-S�i�s��\[�]�؅>_�S7�0�R3A\��0?�
�,D��	A��]�+J�!���ûP�8YU�hY��O�%�#�$­,]��oo�6r��K$a�)� ���u3[���l>5���=����m~[�ar�K���SA� �Zm���'�(�I)ۨ߃9:���V���B\f�4Y�&*�����m(�Z�zZ�±_J�������ݬ�[��'g3�����1���0��I	k�2֬V龒/��x3��|
��~ �h�p�ظ��"��7ݤFtem���"^'�0�4�1`���&I1��3�����$��!�]�(�;�3[�38i%e2���f��Yx�t�f�z 1�.s��'&�ד#!��df�h>�ĉ�y�BO����z��Q�~�o����C4`R�"�4>J���G��I�;���Lښ��^x�	>b���I4�v)�Q��\��{�!��\QI����Q���%��a$��,�O���g-&^�����"(i��������kRT"�ՍhTM�����Z͞��<��fAm��K-m{E���]��Rp���}t_ *�qpb/���<SQ��QW���̏l��*�����˱3?qn/���JQ�c�_]��3\H����T���
�5^b��{2�Wm&ш3s=̋��1�Rj3�?2�XL�
endstreamendobj31 0 obj<</Contents 32 0 R/CropBox[0 0 595 842]/MediaBox[0 0 595 842]/Parent 92 0 R/Resources<</Font<</F1 101 0 R/F2 103 0 R>>/ProcSet[/PDF/Text]>>/Rotate 0/Type/Page>>endobj32 0 obj<</Filter/FlateDecode/Length 2862>>stream
x��ZKo������[D f�7y�u6@���f�E�ds�ʖ-�V�ڒ��_�]Ϯn�c��C0�5$�����z��g﾿9��ÿ���w�[~�ei��߬�2|�����y�i�����v���oI��4���?�~�9{�ϳw���R�ǔ~�%Y��~J.�����$yY��ٿwI�Vn�7x��[�����zrc�"�f�$�I��E�����M=�l�sn���H��r�,�{�Wp�V~d^vi>;�)@�_35X�������l����I0�k����w��Q��BI�O̚~��+�Z���⣎^$�� �x\�����쒬u���ʰ����`#	�B��L_�_B�Hg>9t�#o�?�;��y�65c�9���)V2�PQeNbK\vGoV{�.��Aa�|�� ���&�g:� ��a}�q5�|���~��-%�e�OT��Ԙ��^�Q��Q��L��
�E*t�z�E�'��N����7B�vo��^�2P�>���O�c$t<����Tmi�_y^Z�9���5x�[��!��""P`�9i�ږ/�PF�l�u��%���V�cT-�� n��D��]Z��*Dy�(���e�J�� ��t{�k�y�15rz���nN10�;|p8D���گG#�8x��Z٘��3�"`:��j�6jW\\�7�[�GQ�~p�-Zj��:-,:'����Ɖ�
$�~?F�޻�m��~�'��|p�;�A��ܯ��φ>������z"p�^v��3�Dw���\p�Kf��ګ���\U|�� )P�5��x� ���S�b>w�y�F-8nP&
�^K���/y�N}!n��.��� |���@�e����1�p"u�:�����v��pGT�ȇ>X�4��@�ڽ����$�!��a��K~/'���i
��0�-��qB=��Rl�����ړm"A[,.ӽ���Dǝ�>A�w1���86�<|�Z�;��봍{!�a lPR
�F0���Uu��] ��0.(�?�1�F/វ��#�G���~�CQ t�#RM0$��u�ˇ��r`���g$�0+D@u(`���$ WN[Ԧ�ԏ��n0h!�c���]���[V�3p�_�H�� $�/��S0��ၛE�p���|�h��3��}�(�QQ�c�P���!��a�J� (���ހ��u�*�K�&:V�býx�f"�F\kdc0��z�ckLI�ӌ�VP��5n��J`��U��o`�Bb;=
�|lҍn�sb�}�L����_1o�h�Q�]�����]%`cM2��8��
��$��6�͝�GPxÄ���TǘA��$�6�*|�H�@�;4#�'#2���AV��ыy+����&��ӑD
j$H2^���*�>>%�<�i�N��A��'�Ku��S����a|>���d`�;�6<Ƀb���( �eH-�5Dd+"�S�V�0�����Ң4b4�i6x�X�=9MG�6�$5g�P���n�>���pR�]Aґ��p_����!?��3oɈ?*���+}�6U)��5}�:7�M5���_���WOe��5F/&�v��	�uʀ�@����2��z����^��VG�yPX����;c�0���a�r]#��=٪/��jT�MU8���c|�,�ɽZ6L���_�lfTH@ԍ�0��R �A3�J��$k�se]b�A�(x��O4@X紑
�	�q[T;��rH�J��}��Dĕ�T
okYz*�-P��h&"������A1�-n�E��C�2(�)	�d���Z��
���$}�dBc'���8z�F��PeH���Q!V�"7f�I5��l)`dk�pv�S�#��K7L�$�/a!VN+������Ц@�~�T��2���Y੍A;�\Zjcmj���-�[�nÑFڶݠ�,�Zd gӛ�zA�:`��[����w"x�ޠQˀ���ht�^���'�>�S�fg�xF�jxa�N�w O�PQ��;Mc���uH�i���E���D�6�H�Ӗ�2�@�$�I����p�=�[f����}p��s�
���V{�r�m� ��$�ƭ� x�}��,�x�;ς�n��%ʁ�?q0��k���{ÜO����T��PrY�d:��J;�t���9�Vl9%k�/��
	��7�2��0R����m�"X"�.Q�_����J��`���7u�-���Z�s��� � �}�Isz�J��
���/�H8�7�D�a��6��Zɚ�%��,��m�c������p��P�}��J��R�N����J��6�>�s�i�>�}�����÷����᥏Ϯ�ۗ r��uo6��0��Ė�G�)]tC��A��mX!ll�ʅ|��3m9�1��_t�6_!;S��+�>hk�?	r@v����Ұ�a>	5P�[Q��-�Ѕ1���qf��B� �
M^Wi����':�	!�-)����t�^S�eҡ�4����`�$C���0��JJ�[o;0�X$ں�L`ǯ�������b���H����-s!UO'�8���Ě��_}��U
c�t�e��5�E!��E��-�rM��"c>���zYh��6����J����V�b�g�b�k�PȅI�}k����C�k�Ơ�GGܿ�^����Ⴢ��Dl���e�J���5x�mb,(/+)m����0��@�Z_��bU@ḽG������V ݆[�zs�\H/* �x���z\���&�G�	K;��y����H����{�0.-ʳdOˤN�A�El�נU��	`�w��
|�X.�V�R�ͧ�����@����T�>�{�׏�yq�����
endstreamendobj33 0 obj<</Contents 34 0 R/CropBox[0 0 595 842]/MediaBox[0 0 595 842]/Parent 92 0 R/Resources<</Font<</F1 101 0 R/F2 103 0 R>>/ProcSet[/PDF/Text]>>/Rotate 0/Type/Page>>endobj34 0 obj<</Filter/FlateDecode/Length 3900>>stream
x��[�r�H�;B��#�� 	�G[^���R��s���S�,����]�[e J���S �Z�2_�\���ɳ����w{u��_/���h��N/�'#�zt�����4M������俽2�ۆ��$�t��އ^2�z$�A�����'�i�)\o��p0�m��t��>�S��5\Γ�{�/+:��V�ֱ�i��g����?qx����U�r�Lzw����Q�7n��f���S��]�|H����&n��������ܯ�}<KR����׹�ａ�W�Z$LW���������q��l��.q+wL��ѯ��MBÛ_J����_�!aί|�ŶǙ��K��r�����ɋ��'�9y��;�bXf)�������q}w���͑�0�S�~:t��í<wc���
��9�%�����D��]����ӝ���}�F�8�����>w���f��8�pz������>���n'��o�s�F|����w���k�[B�6+K8������K?�*:g��1�nG��F;u?YN�n���x�ƫ��+�������s�8��2��E�y�3�rO�d�:	z/��N�ܷ��4��̼I��c^^|t<��,l��O��#�:�v��Ya���7��	Z����.�h�+��'0��F�c�_��+��hM6	�� /vp+"�J��<%˕�3-K��"��?�!���b_�08�/8�z�!�\�(rϥ�8!�%�g4Ai���`���^y�Jr�����~��;�Oa����j�v�?���ڲC@�T�^h��ȡф�UWM�3��h�@��X�K��q.�dc��־@͛ۍ��ri<���<���(�Gy��]�mv��	�(�[fs=���v�a7 ��x(�#)�5��	[R�M?���К�ψ��q,���AoȆ���HV�F����v�2΀�Հ��b��K��M���捡!�W�DH-��kW-��v��T�2bZ$���'��z���;f�Y?ʚ}�q�֊�ǹ׋�p�%�Ĵ��'A��+�1>+LTր�c����U�����䘲^�Y��Jw#�%���"D�U�p�x�HAh�"|1k#a����TXSa���wlv�1vS�IQ4�Ϳ]�������Il�P��h�g��8�
��KX�������?H����*���8�C@ux�j ����Z�Ŝ  v��vP�T�v��u<��m4�ó�\Ō��U0�;?Of�[��ރP���Wg��[��>�G��JCq �%����"���gyj���ژႦ�c�W�g�P��6�R��` �m��+=�@��){@ѽRF���J�+�Cq�p�aގ�7v,��J]�G��dxǐ�d����X*�=��4G_8l]4�Bo@x���G˵��ɏه	�@���#�V��@G�;���� SL��38뜉���1�����c���) ��@F�o�)��4hhs�!�/&�����Y8z�=^2@��.��������3���1-f��ը<	�d���_�(7��	)G]d@j�{Q����9��>f3�`��!0�f�i�zd�Q������s΀j�{�]u��3���Kݣw�T3���UCq2��G܊��%I�("_�P���}�{��VC��h�्��%�'��Q�a��Q�jc	E}�r%>�Y�܆B̊Br�ѴEWU+��!�R�:���R��7��H��V��~�z��	S�G�D��7�������,���t0�R�-��3/,�+����ȁ�a)1��8�Q"�Vk#�Da�$�f�x�:����i�;!������ԅJ���F�\�B���D��|��mE�TӴ�"iF&&n���z�t�d�:*�x
?�TӔ�r�Q�����Rq�ń (�E�hc3)B�<;
�L 6�#<�V�d�Ey��x�2D�TD���J�����E>
����4O��-�22�zeIqWPҵ�9Q	Ge9; �j^��<���z�	���yI�%��?�Mx�%����������;�.���:��-+S�
y�Z�%�T�d�b�1G�{Jt#L�v��l�����4*RL��Xu�eE�ln!�k�q��V d��"\�%�*�����F����Q
H��p�o�V[���r%�ɛD����d� 6�K��t����;�_Ã`���b	�˚r|�R����ؓc�
��Uu����G��w8,CH��:��yG��f�P�e�j9G��I%F��Ҹ�����[��ka����a�5Ԝt-��%�m��[�Ӌ��Ԕ�:�_�M�5������P���T���dJb �GQ�*�/ҋ�(B��
�lvQK"�@G-ˇ�C.Kqw!�Х%��?�an\�Y/Ql�������Ta��%�4�yф�:Ehx�Ξ����T{��:�(�Wq�Ќ %"��%�6E�8����ՈU_Cހ���?�E�Qa\�U����M]���G]q"��e�P�������^��d&"S��sxQHF��ii��B#x��h�~��p�YJ�C��z�Y�򽼡��gY(�4T=�ښ� ���L�����#�ߐK& ��	�h��Y?��@K�L&4���!�W�� �8��Pl��M5S>��.��0������[w�X�@|� ��(В���RRfPDUׁ3#�!�	Y��J4��|s�e��Z�T�"l�Ⱦ޸#
���wI����!���ix�,�g�LUv�4M�$y�{�,�oi0�T�֌�^��_�K%��ɠ�I	�!Cq��0,��ֵ���Nk��i3��$WA��J->�;����+��Qt�c��͒�Ė����n:u���`�q�k�a�<�X
 ��.k��]�\׳.�1oGGN��� Qc�j�Bs4�زp>�a7��MV{�v�6'�1�PK�r������?�X�M��n��)_	H�6AMs4�O����(n!Ax8�q*�P�w�i�c���)+b�g��HY���6�E���(\��云<��3�������si���g�fMm��	LL�.H�ȴ@�:��^�xP��&;*�ĉ<�����ʾ��K�';}(ۻ��V5�\qp-�MJ�s�� ����v�B5�I�`N�����H��q�����2ʏ����;�U�}�~�,Ia��+�砓��L���^�4���<76�gtE�������Mju!���4tZJ.(d�N�=H�mTXAۿ���j�z�Ћ���Z�˨�\W^�f�|Dn��7闪�6�o/���&-!YH3Gl:�~���P%��qU1�P�&�dΎ:�N�y��z[��?R��f���ݔ` ����ҫ�,�S-�����G9:Z"��~�NB��%J���`jJ4�sK5R��6�,Qe}#�_��� jnц�'�:x*�<Kl���gl|��������7+N$��Qg�Q�����D�5��|TN��plQI���9.��0�R����\�^+�~���㐂
�Q��Q��B� �=�v��]�Q�ZJ� �6���)�r��7�ѷ�k"��ds�&�Y48&��uգ����E.L�F��`��F����k���]]s�Ӕ2X33�`ݴa�F���x�1�Z�H��&԰k3������8�#��:Qa6�8���gp���sgu6��l��3��E��	��lxMT}i����45���qb��?�q�r�6�\�K	\��T&���V8*E��)p��mx��"�L�͊�F#�a���-X���޳��Ix�0��Ғy�Έ��3��B�Zsb�hpo���Ǳ�M�S�q�-��-}΀�XBK�!�Wl9�d&�T�;�& j(����<SoW|e_�]jSn�q��V%�B09�"f��4���9^U������K��)Y*�c�Wh�3� ~��0O�ha�6SG3
endstreamendobj35 0 obj<</Contents 36 0 R/CropBox[0 0 595 842]/MediaBox[0 0 595 842]/Parent 92 0 R/Resources<</Font<</F1 101 0 R/F2 103 0 R>>/ProcSet[/PDF/Text]>>/Rotate 0/Type/Page>>endobj36 0 obj<</Filter/FlateDecode/Length 3718>>stream
x��[YsI~'���1/��@�)i�X30�`�v�A�|d��2ׯ�ʳ2�[�n6&�����ˣ����u|0>������<?��Q39<>;��v~��Yq8)�Ѵ:<<�<����AV�����Ճ��lX����i6��&�7�$<|����У�����A���g��x�*��2�
7���������g��O�Ï߆�z4��{�������<�|��5�:n.φ���&����r��zK�\^fa�b0�)���t	����w$����x���7�`������F��}]� ��.n�i�zt����b2��y3��,LF:%�4�)n|J[�?E=�r6�ʆ�I��4���%4E1�����x�G��e��S��� ��d��'��h��
e�U�ø�hS6�*<��P�K���h vP�CE��i�Y�4�Õ4���ʍ���h���K�t��}�����J�#�(�`�@c���'j��'�g!ܙ0���!���`g�;Q)b�@��70�4(�L
������D�ǰ�W��~ɣ�$E�֚v,+���)�0��c�8<�gy�0�F�K�	�:��X*��V̠����#ѩ��.�p��S�쾂�I�?�3���1�@�	,5A�2�+;�giʅ��/bĢ_d	��
���F�;�bN����4�����Y�/`p��S�����B=M��Jnq�l'��	ruD�%�'6��� X�_����,1��\�;�H�D�����Ze7���VX������D:�liEv」�^�7��Ի-l����E�R�Lk��h mE8�mqʪ=�P�5������rA�YQ�1_��^K�O�_�����ڻ��(fN�v�Cfk1���z;I��:�"�-p_23��B�	r�Bv�v�.�{,�#$~"�mh��2[�1!��k��4���e���m��#����0w�|N�f�?銗��l��qX7��H������[2�[mر�K��de��%$(Gw�a
4�s*��=���l��E��$Dw�_s��i����Q�N���>�z���2��B�T�MH{�&��/�����wЁG��!��!~�C�ƨ�vb�[7��6I�d�}�6/�j5�6;m�b��O��$@xʢf��X��Z�E5�J`�p��9B���j&�|���UXK�/�gꎎ�S���,1��� �B�jVa<$�O���ãb�:���g�KXB���>�7�au�)��}�?��d�=~��?pbNdy�JZ�����UH��TcSn�'&J�D����i�S9���p��#GZ�Q�(�a�p���_�㹪|.��4�Dz���-���;�ٿыR�h(�H��82�@־�����C)��S��3	��ղ+�|�mu����:�%u�DE��x��,0��l!�'���Cd7��y?�zC�W�$¬��Z}7ᴓV��=)�d.�=���iY�j	�7v�k�H���ò(R�Q;n���z�풁���$�p��gy��$C�3n�o.��l&[�㾋��0�6�\:)���#x^�-������2V�����p�*J�^��8�5n����&����+pb�A׺/8��42�nBR�$m��M�����x�6k)ga�I g���G�� ��q��T��Z�mF���w��*�sD'No�#R0��R\�LeȒY]#��GI��(���"��:��Do{�' �)~r��J#(��z:��2�#���֜�?aR��� $L��3�+�z�w?��Ӗ�O[���v��|�ȫ,��g���ѻ��B�J��e!6O�&�f�?�1 td���D�T���	��RDk�䑁�g)��Z��XO��!I.�H��՘[�q_cW>���+��dI'>4�<)��]������u�UXƸ�uԫV��9I��|>��ɛ�s���//0�w����t�)v6�;�k��\�iM��I�wm�}B���%z����tk+;�W��z��\!���]Yv�[H�[gSw��8�&���x���F �p���n܄z���SwZ��\)\��ԑM
�}W?C�5�#$���ao x�֮'cw�Z/�'W�%WfĮ�'��M�s�mO�۽-���eR��Okp��Y>	��.8�	�)MOT@mBr��!+�Y���I]���ŧ���tF��J
g�b�S��0��H�f�$h�[�@�\��C���v0T'g0��-�>�a�fϤ��U�LO���|�m��(�b�X��7d.wԯ�V+X����K(��L�h��Z[�\8�����uV���o�h�T*)��|"1���|~�k���#�8�z��
)�	��X��SB*f2����)�\
!��2nbp�G{���t�עW'�uo�3��;�����h&o���)�+	
��\�%M��9"����-m/-)ٖ���c�i�~�|�l�����&�-CZ��1��'^X#L%t�ך��Q9��D0���k��Ez��к!�_G_�zn��)PI/_]M����3�4��xH{��:B8[$^
���c����ݬ�����K�l��$�H���f'�.���$�Vw�tȽ�HL[E�.�����w�(W�r���I��	�di�a��VsV����z|#C��T�wZo�"G��#�v2�y�]Oh��i�iL��ߓF�(�oO��4̹��l��7.*˦��+=t��܏��Kh�1�@`?ܞ�f�hg*r�laI�4� T�`kwG�k�<�:eO�g�Ȃ��g�I���5��P� ;��pzĨ��-���S'g[��_L���W�,�MR{��v
�Ǳ�]�3/���P�8N�e@��f{��O�=�	rL߬�]�>˾�.jk=T�,jM�S�"ƃ;'3|E=�I��O��=PL|[ ^���;D)O�͜��s
Ы/��Bo���voe峩�|�I� ���T�3�,S�LqC0�"f�Z`�j%���;*����X��\���k��^��Mr���*��&.-'8�Ms$cD	�;ݹ׹m�v���zׂ{�P�y��Hve�
��-|�߸��!O	>)csEJ�X)I�l�|��ώO'�j��ȳ�X��>�+���;�� (���4IK@Yk޿�ᗵ�+(J8��(��L��IA+wв��#��C��Ջ�Gq�$�r�W�:j���V�k:c:��`Wn��='��Pq�((@�6�3����Ρr�:�ݷC%������t��sMބ׮��g���$�mY)yi����jjK��1�9؞�R����W<)D}po�\�I�����ҎSv5�2��/�|��J�ᕴ)���{��qk���:*f�DS#��:��I��'^��|a��6�܈N�A �е�v���>O�}ɜX�:��$mW�NgsF�Y���ov�r�B�*��4B���Dp�Jk��R���	��5o��¨j����c�1ݜ�~����P-.RrF��(��I��QU�%�M*Z̺i#c�d�Y��,�+�7��n���Q���iŒ�t�l,�̱�a�i�C���m� �%��WT�`FFZGL*df��8W{�}R"Oօ M'�x�V�}�L�'DBԮy��t�nC:�1�� �e��rD��)WF\C��N��풓o��5a1��6��g�7h���8p�M������OFl��4�"�]9WÀH!{ʩ�Zb��\��=��O�
endstreamendobj37 0 obj<</Contents 38 0 R/CropBox[0 0 595 842]/MediaBox[0 0 595 842]/Parent 92 0 R/Resources<</Font<</F1 101 0 R/F2 103 0 R/F8 89 0 R/F9 90 0 R>>/ProcSet[/PDF/Text]>>/Rotate 0/Type/Page>>endobj38 0 obj<</Filter/FlateDecode/Length 2407>>stream
x��ZIoG����}`�k��c'F� ��0�(K�hP�LQN���m��Mu�� 2�X���m�|�]�p3+������껟�\�\���z�h[ͽ�}����F��7��?�L�jq�9��%[����*S.�m��*/e�NX���-����0pli\��OY	۫l�r��H&��	_���3�w�,�+���	���*�Qh���q����tx�E�6�-���dpP4�c�wB�E��T�{֥��B�2`]+#VM���K���k��@���c$�{;�;��z���~���Kpa�ج�f��C;>�������u����ꟳ��	P(���v���U߈$;�*��6��
�iW���|�kG^#�=gN=g˒�Cg��F &~��޳Hآ��g�K�ߋ1ք���z��9�x��"���N(tٸ���b�w�����Ж���Ļ�C$m�Q�|$�"R��}n��@_�%�֨KQKtn��D}�ˈP����v�C�k+���
�y+V���6D��6��� L� ��0/�ŭ���(�h�p̳/h��;����px�|>�1s���z'T(��{��c#��D@�D��kUS��
 ?���A���\����u��w����K����A*���H��An��
����2���}�,̱QL�UFW@�U���c���Z�E�XU~�%��O⽦����k]W����Z5;�mO����mo�~1�]2�D�m(�kr�)~z��ۗVy�v���&��[$A^o��]��r��.� �����3�l��ѐ-�w`�葎j�ã���\��Q���3���0�5	�I��z��}�&�-@\�$fus�i��q�^	89�BlL|�O�V��q` sf�C��w8�!�C����B[�.�8�j� 8����d9!7:_[O��#� j�#N�b��[���S9�
�^k!�ň�e;l^�pgH:zn!Y���<H�w�x��_��b��m-�-V��I"<Oy�Ȕ�n$gMMa��n΀vhrM�Vl��K.²��˨F�7�@��skm�N|H��A����Tv撅�ƚ�:"I��� � Q�;xb�u,���<�%�5 ��o8%Kk��[Z���r9J*����r,Tv�iJ9��Ľ����Ԇ��kᱍ�Ϛz��~��Xʦ��+���溊�e]F��鮬�W�����vSa_)���b�<��>ZO����w׌&xK���tZ6S�E���%�Ƅ�H��h�c�NX�*s#ե��ͮDhM|9�.�����jݒ��?�:�h�ӈ�En�0��-�]��r?kr姱{�9��[z.%>]�FSI�ɔ*�\AHL�1ƵDP��1��.˦�e0�H��靬{� i�)&�����	.N�*Is$���m+/��49V�%CCSSw?p'&�I�6}��B�����I��r�~@�t�	m�!l���)hr}3L�#��8V'�SU(��4�S�o� �
�R����Ĕ�-ࣖ÷���jG]����Cހ�
[teå�:�w�G��D�;�E�COA�N��q���O7O+\o7�u*�77h�������PÂS~~���� q-7	������n���C�#��)�jxR�D4�2X���ih!=~�U�/Y�Ć�0^�5'	rr��eϖSe���fY�P�sI�,'w�]��ʩ�������cK���ݗ�|��%��{A�b���^Ҵh�\����&	�JӴe�r��m 3N	1y�J���������]��?�b���-|��ȿh0p	J�����^� U�̷���#����WP�R��r#(A:Je�-�/!�WD�74t�6M&뱧���:��|�E��2!�F�Ҡ���d�߈Z�ӺP�/�W�S�w��B�Q#ȢiqR��|w	mr����wf��=�n�	�>���K�$&�P ЛX�vߋy�f���7n�oF�+�v_�s�V��{��k\�?9��k��ҕ�@�5Vvkh`�0��0 �+�c0	�a!�UJ�hm���j]���f�:1���_ 	�)n`F-��BQ!�Q�$f�K��g�<<޻j�)���J��K`��,q^��PݟC6Gaw:>��&i���J��$�,�%�!��⦲�=�SATLH���gP�Ƙ|k(���-�c�K�!/�.�v�	�����&�PM-�ĺr��d=��\�A5��^P�BĻq#)�4X���b����ꍤ¬���g��fC�Dip�f�rFU�_d#������w>��N6�q����(��4B`o��" ˚�{�j�z:cT���w`��.���V�����FR�a�X�:)�a��y#����J^j�;��F
!E�%`�"x#%�y�G���-oԔ���0
endstreamendobj39 0 obj<</Filter/FlateDecode/Length 10287/Length1 19027>>stream
x��|T��p��$A��EAP�(F@3CP�8IQ�>̊��<Tu�Y׬�뺫�F̉���A�e�{�����}��n�TU����9@ @���p��~}Cr3�@�#�6����ES�L 0�E^xd�D����,PK�L���>%�^ d�8iL��,.P�c�3���p�@��X_-�r6�yб����M�u����	�����3���B݌O���m�U.X�&M��K�i7��Ad�����ζ��� ��I��倊 �u�ꥲhiƋ�3X����<���6��#�i3B��5/ �6Q��s�"�/E���XC(�ѡ)S�%�V6B7�0���b�f�v%�Y����C$C�C4�zq�آ�`�Q�w�C�I��\t���A޳��?^�c� �a��9�b�"��n���!�Q+O�6�n��Hl�]��0F���'I�!������#����n���HYRr�89�$��9���56n�uM�L"�KJt���z���K}PR���"�M���3��m��n��x99�G��vpĢ2�!��m���a��b}��n��;Irl\b����pL5C��訄�Ĩ&�Z��`Vb�F�L��GE��q1�8*;ħ�8�X��?/ !�s�. ��CTfL�<�������Y��K�� Zu�o����~*84a@P�˥ԡ�W���w����U�f@崔[~5���9lmS_�����|��#��m�~Q���e����w�L��ڼg������./���F��6�'3W|�]9-�ݨ��������מ��aHn�N3��ϗG�yf��{����u�ݎVS"��[�$Y;oG��/���s#O�^s�k�dO���C�MΌ��y��c��V���(��r`r���q�K����8�����s���<XW;�v0E���~/�~#6Ds��f�ĭ����M��K8.a����g�<'}'[e5t�Q��B��5����������1K�|�ב�n{�u9	Q21�Hܞc�0ƌѩ�gRA:r���?Z6��n�o�3�&W��0Fy�B��<b��S�w�?�w��$���kJYާ-�O��O�ar3�NI���ȉ����:��&4�Y��&_xRtl٥vU���j���.S�>ސ�u^�{���{�'\�����퇗�4̪i�{�_��|�g`'��KѢ>:O��>��ѱ�k�zO�Þ�=��'�i��I�.��s{r�ݻ[^߾�]&�������%��~���F��o5����y�{ܯ��a��������s�l+׬?��:[�_�6�5��7�e�;c��v��y@��[�d����`���9&B�c$�t9�[7�#��bTc�q�D�i���A�p\>�����Ȧ�;�.3p𐠦��_4���g�D���+��?�l���PyL:]kM߮�ߏP��[���i��)�^���Z��Y<��/��3�We�ѻ�3J�g����m���-��s�;U;��]^D=��O�����@n��m�ъ��W�G�]����h l��TX��Z����z�lhwoֳ[�J��<__�Uum�ʵ��9���̶��m�<읪�E�c�rE�	�է_����Wp���Oy^�#����>�����8܇X1��_�ь8S��"�O�v�y[��t��ˣ��y��=o�ٯZ�=_���짓Zco�������#}:���^��zd�,>o��bL��Y-��/�@�x�{�݋݊]�b�ri{�HY�]B��E&%�K'�q\{�,)*%R�l���g�,q@���0���Me1�g�0--���e�F�P|�	�t5S�Ϡoo7�1)忔��~��6l����k�,ybR��uB�U�i�m���]�a�9p����S{~��8v���27�<|ʍY���:��z;hh�o�BS��β�"O۳�)���zI��50H`�4f��H��Z��M����v���lI�5{ß��4*��Y��Jdʄ�O3��m���m�-,�״�*ֻ��oƈio��?���E����kٲ^1g�?�~��,�F�kη�_Xq��:ӆ(��;^�U9Z��Y��4����k_�>+1��6f��Kׂ��~��I1%����Y�U���*v;;�q������NH\Bt�\� �w�:7�?�8֧�D�cgz׾�b���q���c��r���aa�QwDCf�>x~���Ӕ}s��pi{�t\z�q**��V�~����:��]��z_e�Rw%D%��q�y���ӏfݞ6�r��U�*�P�ؾ��զ���Ra͔���.R�����c=�=M�6�ਫyn�)�u~7w��LmX�8�Σ^����3��`��p�U����7��~�ސ�w�L��?�^n���ޕ\��9�ɮG�1��T��@��T����3z���>Q!�8�������:Nh�79�3Z�CKG���ӎ�PC�fњpGh�(��Z\��%0o���o�����9q���(F˼C
���@�OB:F������p0s���55�S ���49��KC�MY548��c��kC5�
*z����u��ʝC-̒��N�K,�Ǘ'dZV�_��b��>�|�L}(��z�Sgn�����t���/ͬ>���y���u=���%���u��� ��劃���u�"<_��Q���=g�M�1XV'~�н�����ݳ�ZDeE
���e���d�Y��Ե�w����Ee�D�S+ov�d<o�Bߢ;�n�V��"Ǫ��G�=k6ͮ{0�m�Kˢ�i!C{\���Z����¦㑠D����{ǣ?%.Gu�9�`jrpp�r�Scс+����ǣNb�Ƣy�O�46Z����~��ztw�u��$v��������ZlըS��u��)�G�R�"��ez{�t+-�5͎���1��0��xw�A�����j?��v�}��Ԫ-~Y�|�y�����uC�+/�?ѳ|������v.�~=�Q@��6�����q'�'i#��$]���s;������߻��vv�0b�,�`�#g���*���ﯿr�`�[��ܻ�:_5{^��y���W�խg|�|��n��b*�ůJӎՃ�[S��;��˙���6��#W�[F/��o��ڑ�h���>y�����|�U�l��+O:N�{d��6����i���v��n1�c�,�qa���ǩ/	����o�<�5����Ao��O���R���$O�FJ�����F�����:�	k[�V�^icx�[��@0CZ�t醣�s��N{M����&��G�Қ�{�ַ��f�'ζ�WLDH��i���?:sm�Yo��ZM���[}�~m&�K��
�8gs���Y�W�r������k���6��8P�1�>b��q�/�h�Y�x㄃������_L���K�%|�/ܞ]�z��΅���5�_e�}��@�JX�y�4�6�d���}VOy]��?8Y����1/�>r;%)�����H�a����:��D].�0ɫ�H�`���;��pjv��!�Y�k1E�8{�����/?ug�v'ղi�Z�G�y��4t��k[c��ܑq@W���2��r��Cw�9��<0!䉵x\�.Za��b۬�0� d���7���v��)�� '��5�:����3�I12�46��&319:��Z���Cg�ˮ/=y�����i�ԿP}A=��5�����O�/��4�3#�酉�����J��7�2��9�/aW�ѥ�̗��/yA�|1���-��{���:`��y���|�֍}78%����r��j5w�?��9h޾��K7����q�e����:�� ���"C-Y̶��	;�8zuĽ���c��L�����S�t�c�����{��K"Z�aO������]���U��Ŏ�<Z$�d�1�ю�F�>�\a���mQh��aQ���P"qe�e��9����������ƭ�r���b�澨��_�s~�8��?89:8�9���F��O.�+כּM���F��y���|��8�vv���&Y>�q�qg^���Φ���:��O��h¼�G�8� n����S���ks�/�����rg翜#<���j��1��ߴbN�EO+:��m�-�������ȋ�6mش�iR����´�ѐ����cNh���s�c��sW<���-����ٍ���X>�����S��h�7im���}��\����	�?�~]�&��J��|��N�g�߼����F�����p�	������%�����ƍw]�.ѯ�/XrrA������tS�`e���b��|��`��	�X7b�ҩ��@i
Z�0��Jo`Gb=n��, C�Ih�0N�k��,��\3Zp�����M���o�4GS!���)�k����4#��k��������704jm�Ƥ��Y���"��Ҫ�u�N��t���fg/vptrvqu��ޣ��g//��}||�����8`��C���.l����F�.��Ȩ�q1�q�'�'$&I'ʒ�)�i��&gN�:-+;g��ܼ��f��)�[8o������G��%K�����V�)Y�n���?lڼ�޺m���Ҳ�]�Uջ��ٷ�� ���ǎ�8y���������?]�|�]�q�V�m`��P��B�S�42�,$k��()3j8u�:Nݢ	M���D�����t��>�h3���L3���,b3+���&����\c
��Y:^���O�?7Ƕf-َ��uf{�l/֗Ma��i�.��}f!�0�0�@�Zt���cIY
-u-,[[�Z�[�XXJ,�����ֲֳ6�6�^c�����Y�_;��M�M�(�X��R����T��^��RLv���	�ru�J�W��@]
�at��L:����g��eL	����b�2�*]�P�b�S����u�gYw�.r6u)nA��*]�Q��*]�-���es3]&n4�8�Q��tQ�Cm�+�`,U擉� Р� �����0?#�M��~��ufʶJ��Oo>�����٧ߐ>���a�'�O^��Ӽ�ܫ�%��ǝ\�9�λ��w�{�N%��?���~���ϣo���q'��G]Q]I����uc���M�K�un��Qt�ɍ��o�^q��<j�(k�>��*>؎}bTz#|œ#L��Qﱗ^#�������.�ݕ�dN�����x��^U7�x:��5��(	ka�ҙP�B�X	�`�|Tt:,�g���b����Oal�P/���%��6|����G�8���p
N�ox9g�l�x�'� !�0���d$@<$B1�Q&�E��<��B<�t�0�@&T��S1ʳ�wx�ė���x&��H�I_@��'h B���AI��@2�$��`�AZM�E����ސ LB�0J�#ad8AF�Qd4Cƒp"��p�t'�$�D�(M�m�CbH,�%zD���K��#�IkbLz�	$�$�D�D��1�� m�)�HdČ�#�9I&rx��g�GD�%Ē��T�F�I�D&�LbE:kґL!\>�"�$v�N�3�B��/p�L��p��u�upn��dYM�1[�!%dYO6���bC6��d�Jl�6�s���$�����]��T�*ҍT��d�Kj�>��Ԓ� �#�ȏĞ��ar�%��qr��$��ir��AΒsā8��ĉ\ �O��L�����Nn����#��r��L�_�}zՙ�Bu�l([�eG�Sbʁr�����3�B�Rn���ZN��VR��e�rz��, i9�B��it:�A���PJu'ΰ*�YeP?R �L���ă�H�@m�6�+�j���Qۨ��z��N��*���\:��Iς���z�G�"j��\h$x&8�9u4}�ۯ��)�6�7D5��FQFD5������8J�c����c<����3o	F�P|�n>�]�Ѹ����7#h	ʶ�Y�$�	�`9��E�^�;D�F	�u��L�|aF�T��R8F �yC�+�H[���osyB(��hMD�W�m��+rC0����k"F�l�ҍ�嚄�-�e-��MYP����	\�#��F�A+ <so[P��=!J�
�U��*��j<�n=,����ޮ�mGk���������lYm�g�g%��g�b�|+�p�n��Aa~�fûٲ��e$��S��ƚ��q6
�ڏ��
�9�HX�ZXX`�ᗚ*emA�*6N�-Q�v�mm~A�D��hEYEIF�)h	εP�ؐ0N&�cY����rT"ru������^-��6Ӣ�La��O�o��-�N�Ō��3�c�b~�LVQ<4�y��>|��Wf������b����%Uʬ+V�*g``��/�U��0A��9f
���
��X�U���
�2���Q,,8}�TyCYC��,D������p���6մ��d5�|�n���'��Jl8tD��X���X�IA��Vd�Н�dV���jLvVHX)E(��>�wv���j���R�cr�+@ As���ެ� ��ex_��"��ԛx"��F�^�B����y܅�P>!aͥF�d�;B��Q�_50�Rp`��i�� �зз��Vﳸ�1 ���K�w]0J���VV&���� z�F�aZ�v�tkga+�*��r-�>Ðx[�������\[��+���߽�uu��������ϭ���7l��-ħ�>SE�"��[D�2����ܨ�FZUʛޚzz�0-�˿zkrcj�cb�u97,���xY����Z��^ۨ��66��*��c/�hb$T
�,;v���E�I�ȸ��������+]�xfv���C���d�x���n�,�P�%χ���`=�����}z�q_�@��9��i�g;'���xf�|��<շ��o=~�CާE��Թ��4ToW�K]�!@�k'j.��
���Ju�:�tGbEo��pH`��W�?� ����؅�����ay)�Ot��g��c�k�6�4�a�$��t	��~�1^	���b�I!HSA&A��+b]8�,�cBg,�p���`+��9��a���@2s��#/�'h�Pʍ��F	&�/������O�����ʏ({sb��P��@�x�G�v��h�TS���(2{� �{ԎA5�gn�x�������N��vb[O�#�G3�0Z`������=���ޜ�*�����L-'#��P�b�Q��+��e�8��\'&�_�ժ�ڊkR���k�ƵÝ�9�,0�ڣ�Fpk�vt8�c�,_i\S�������?0p:�l��X��q#d o��:�����D��8�B�������ut�۱\�d'~�y��vu1��8�Rz��1�n�Q���X5�V����/JN?�5ߍpӔ��B@�9�~�q��Z�(37�.�G�XW�{c`4�T��dh��T�����}���mf���!�L��`B��	��3�or�����s�e�u����>�:�m���� �ߢ��׃��Q��c�oq>ӄy�F��t���|�	�g�|�o5a.�P�-<�~��ނ:��r�ք���|㑋	^�$�\�4a�~�/6a�-�03�1��sU�ye1��&���B+aʏ�0J��hX�1ۿA�};��N������4�t�K����9@z'b�g;|c�omЄ���'L�l@����c��X�g�	�>�����>�an��΂���7�5�֮�'�5�`.'�yI�_TΡҟ�k.6Usp�8�����s�;��9>생M�����'Q����)Ās�	q���s�P<kh!�����Qy��^P��A�=��֙6���x��znH ���*������\<L������Ɯ^,��<�y�Ӄ�Ԙ�~m����S`�X���5�G5�������p(�90�_3�ǵA��8���)���c���R哩�ګ׃�:��Q*y@yj������R;,��
	�A��q8{p�����*[p��ۣ�K�P�S��}�'CXu����g~9s��è�;���>�G�-�ψx�`�@{�B,�m�sx������!��1�Wc>+T��Z����#�� !���O�k\����9���`�1/jC�=�~^'n^����F8���BI[̕O~E(S���}��U1������Z�S����5T��n���O3�2�j����&�~����y����"W5�]�������E�w[�-��?tЦ���q�ߔ��0ﯜ���+��]ܚ��qk��1��������un?��r��=�?!�g���1���=�a1����6�s��{ w.��%���V������|}��~�����^���o�����1�'���.M�����g�-ʇ�-��Fh�� k��:g���͹s�:<3���;"�W�Ovhd�<ƫ��ޓ2�����)���|2��0�G����xn�yBk��<ՓaB酘�(<W� s� �<������r ��u���+��_�x���]q�D�����������������w7�O%�[=L P����򭨏�_�e��7hGh�$�`Ǧ��vW�4r�U4�|���d�h!�sU���:pTEk�pIE�@W<C�4�siQ�<�KK}��B���j<?���yz:Ok�HQ�"M@�z��)|G�h�hC� ?ME���U�B�T�j�JoQ�Ў�P����SE�@��O���T��Ӛ�lj.<����xZ����'��s�!�jxڈo3��[����ic������}��ߦ����m񴈧��t������9Z�Qf�V����4Gk�|u]���W7��:���8�c{�ǳA��$�A��Ѳ��(��hY�$Q�/�}��C�gU\9a]��Nl������5����G�Kf%�\&��N��&�I�Xylt3cdI)R��� �$�E'��ȱM�#�ɣeÓR�I���]q�qI�rV��J�e	qryt���7l@o���՗�l\"�۬/�����(�*Ob�⒥�8�$1
{�a�Hl�(�c��NJ��`;�ua�"�N_�JljܢD|�(�sNYt�\ɭD�ٱ��z�t��Y��	ܲ��p֨����$I�IQfI���2�M�s���"���٨h���6����o�7>��H��A�'�	 ���$���������q"D�S�����I�������V�,�$18�����4������!/b�.�/E#��֩���|)��C���@
��$��=�i�~ӣ�>Y%	.8��,tna�.ߌ�W�[j�E�8^7���v���^�	��f�jb�۲c�r
ڱ�u$�,s3��6�Þ���I�m�� �e��g�Y3��Vn��Q�q�Fr^N�,��%`��#
y|�&I�`�ul�+kV#���Y"��x[��sE��y�\�H\��JQ|�$|F��R~3x)�Zn�8������'�e������Tg��1��gjI��?�������Q�H1ȓ��%���-��8�������&���������Q�(��'��ײ��v�|e�h~]�T�F�i�c?)�dyiSym�?�õ��=���P,o9)ƥ=�i�m�[���Sy�=���1��R!�MZ$����͕�Z���U;4�{|���1� 3��sƁ	d�2��t�j�D��F��T~e����2�l"/�_�i����v����6/���Wʖz�)��f��*Jϕ�)mU[�����&�]mL\E�D7TJ6v�BV�����a������L�~A�?7�o?G& ���;����xzy2��,��L� �7�7���#���8�83.�!���9s����3g�J����:��Jy��\�
�o�r]�r� &�,��zVVƷx�,���Zf�̞e�H���۫(��m]�W��z/n�TQ3׼\C���\�B�y��E3D��y��Y�Y��-��;��Y��9z��2��򞨡媛@�c�=J��<=B���SA B/��K(ݑ���>ckd���3]��.F���9c��0��s�ԏ15�˘�z0�F�Lklg���2�R#�m���UW���D�P�H�`��Um�HA�7P�����@�;PD*EP(:t���vWQ�w�^��jQe���������[�O�z����*-��Y{)��jJ�«bpŴ
F���$$�W��PV���pc��)C�E�" UDI��GaH�٩�h��
�;wn{EQ`P�"���*ul� 
R8\��"���'Y��l�¥��B�X�Bh��t����/
]�ֵ�!
#�X�R$��IVU6N�? ��99Y������1�
endstreamendobj40 0 obj<</Filter/FlateDecode/First 5/Length 190/N 1/Type/ObjStm>>stream
h�LNM�@�+��
h!QR���c[�Ut	���]��3$	�Ý��Hb��S�H�p��-�p#��/�	�1o� ���eY;^=)c�
����&�6��iX@Oc�Yث'cVT��8s_+�p�Tc��a��6V��%�j�=DK,�0LΟrr��B���ջ�y���7�k���#� "�G�
endstreamendobj41 0 obj<</Contents 42 0 R/CropBox[0 0 595 842]/MediaBox[0 0 595 842]/Parent 92 0 R/Resources<</Font<</F1 101 0 R/F2 103 0 R/F8 89 0 R>>/ProcSet[/PDF/Text]>>/Rotate 0/Type/Page>>endobj42 0 obj<</Filter/FlateDecode/Length 2741>>stream
x��ZYs��~W��+/!�L,�� x��J�^����JU�y�u�Y��%z�O��ࡤ�����t�uO���Ͼ�<�&��pw~�ݟ�Ę24���sC���W�0iLW6v2��t���/�f���e=��(�u����������Gɴm��IS�ҋ�?��u13�l��
S���})f]i����⊆�e�TY�L�S�<����&�seՊJ7������ ��̀�z��tqԯ��V�2�}�z���}�����={�nh���"AtY_h�Ǣ��7�眴��j�b�%t���74]x�^ �@=�#/�ۿ.f���u��ş��iK�+S��7�8������J�������Z�xN�=.�5Y�q��)fmo$��M���`a�� ZL��.�ˢ
�y#�3��^����b5
�G�v�<��B꙾�_x�%[�xD��Ú��=Yc|i��n��R_`pi��s�DOY�r�1�̅�����x��T۶���Z��tM�4m;�A�os��%��)�,�?��P<X��Iy7~<~z�a�&¹��'�2~N�!���I�ua �n4���@�Զ�c�my/,�E..�Sı��bܻ��=�u5d�D8[v���kZf����u "�@DV����j�H>��^����kђu�0���Uڇ��[���?Z����с����BYw$�y�	�Ⱥ���R���!���dX��a���T�*B�<6�'�D
�ذ�r���6�>�%�H>��#���҉�Y㧎+|�ȕ-�9�u�F�k��*�^i±���3P��"KT+��:|���Ik���u^
���	&?��t�u�i}�Q,���?�z�'MP�8�q$^B����l3&�K�rЌ��O�	�@R��dެI-w������ӫ���]�]�r�����Z�9sAn�MKfy���˶�)|�-�Ҭ��������:�O@�\��j��5�.�i�)>�Ѭ���yĠB�`����X�E5�4�!+��F�&����W����˜+�W�95��B3��F��4 Ur3#����k�"�W�x�z�� O�������$���7��э���)SLfk�6"���u -��`3�L^�K(�2Q�&)\�P������ye��EO�s���0�&2��Vt1�����وҌ�u�S0`c�m�9�キ��W�5�P/o�t��%�v�c����5�\[+Y�Q
I!��������tT�m�(d��=������ðnS >h�ѹK?s�(��N����18�U��VNC���WE�}���J[	1�)�� 9E�#����NKB�Z�rʦ�$��z\�uƦ{��y�d�N֜%g3��o�|�>i�p0�G�~�"!`�R}1�U9c��	���LBRK��(�jۋj����[�uO�t�2^O"�od��<wZ|r�O����Th�-�J�(9�M��*m��+��;!��F}q�:NBi�z����S�D}V�F���ŭ/�����"��o���2}��>=`��;�]nx+%��2�>�t0�C��!���K+��w)��mA���JYK�5[X���"�׊<��<-��Q�y;�DMk�Tk�M�,�C~�5ڔ��e��"-ș�<z�����F��/��3E$�0W��ėh�E�;�m�Y���C�'ߵP5nb���\�\V���ƃ�^BCYdʌ�3�y�+.g�,t7�[m��Ȋzx�C]��Pg����K�*Q�P6z��]�E��"��{�T���ᒉInM�V��h�B~�I*n�y�n��N�@�����"'6�R*�~���T�����";�-����Uؐ�WԤe���M����[���%�ɗ�T9��kA�I��yJY�b��>f4�����5�Pf��ӿ�,�_�N���4�����JD�V,�����!�鸡>�>���F�_	[\��L�Rϙ%��'rQ`�5�		�gz�|�Sb
�������ǆ"���������Ƞ>!�7t�cK���9�_lYGZ�a�; ����~0h�w���1�3]�v*������4}7����tBॶ�z���:[My��	��+k�/p�z�!E�� �B�`'77�P7ZgB��~�q@թp��R�^����1�AEy�88����]�o��� K��悥�i�]-��GZR���7��nRW�l�I Տt��Ǳ�-#=:ud$%��=�p[wԄ��:6�|��&��،��3%]-"m���:��P^��{������ʤ�:Б�##==_?B$8�ŵ�"�S{�m���8����HF��ɌI��Vb��ڂ��&K��mk��hT�mٍ���q�#D����O��9p���������:z<v8S��-ҧ*<�y5T�^�ӛ��d�c�'���vtj��m�H���X�,o�B���⪨��%=�vw�J�+�_�����u�ۗ��OE���i�M��Z�JkU������{2D������on�!��uO���S������77�̯�'l�?��"gS
p��N�8I������i�T|P�R1�KŮ�D�[������F�H7��k��`/q�;�6�&K#	z�x��Ꞔ����^�]~��~x5�M��uH��6q�٘�5}甏9��~��r�J�n��a���"DQ C�@�<"�0
��C(��(�/�W��
endstreamendobj43 0 obj<</Contents 44 0 R/CropBox[0 0 595 842]/MediaBox[0 0 595 842]/Parent 92 0 R/Resources<</Font<</F1 101 0 R/F2 103 0 R>>/ProcSet[/PDF/Text]>>/Rotate 0/Type/Page>>endobj44 0 obj<</Filter/FlateDecode/Length 2750>>stream
x��Z�rI}w����î:��꫆'�Y`�X���,�4�G�a�����ꪶd���� ¢K�YY'Ofef��������<��z���87�ng|��d��T~�4;��~gg�q�߽_�z���?���`�{���7�rg}_惲�'�/i�꽥���YD���[��T��ӌ��ޏ"E_>��"�7Y�Ĭ���UY?{�~8W�_y�������y�L��0���������?������W�z�* ��D_�H�=���mVjl��!�m�HK��cS@�+^tHh�E�U4�ͦk�2^��L�������و�J�w��_�E�x�����{�I͜Kî��o�B���Lk�Il��Ml� -��[O�U�߲w_��-�}U��k�H�ly�g����:�ZM9�oyf��^��!؋�x��s����Erb�wu�*�����L*�|P����J5[q�WL+��t�'�o�w�P��R���SŞ�e_?l�;�h����UW����?�R7��ȡ�5��d$���27$C_�؂��3g�btO轲7e�M�V'-�	��Ȳ_����f�x�Q��
�2t&C����Q�8e!��ȹ�:��ap����nHRE*���]٭^�:s,|�m�.�v%^�<���4�Fm��LC��I�2|74��jmv�*oj�X�'��-P�6]��a�Sgv"_5*\��?f=�߉A��%�����$na��h��P�%�*�yt�5��/��c�<���Û�}��Ct�#dcK5� ��64T,i����o�Sx!�q	<E̺T̗�+��Aj�+}q~��|�M׀QoL��?�������#J�
|H�I���1J <�.��%�3�ϲ}��H�yU�s�H9�eL����ο2+��QB��,�E��H�����<󯂞����'����u�#TC�1nNe�~`P��9��1��XK�&8���x�*��.��a�KɌ�[�yl�/�6�.X�K��ְs�.+��'BF�-��W�^�҅��^��U�W�-'窌_m̮|4�뉴!�I+�C�
ǚZG��tDͮuc��lzG��\G��[�hG�'�x�OZ� >���[,?�����
�`��j��n,����3k.z��4�R�2��(9�mj; L�8v�W!97I���	?�����9`p�����w�E�5�B��nʒ�䏸\M6�{���:ߙ������ ����f-���׺��5�U��%~�r�Vkh�SRV�WfKkPգ�!@e3���g�UU$�����m̿���Qen�,V;6�5ׄ��(9�:��:�T�đ�Ŭi|e��ѕ���{X�_a�h��6�HѶ_�g�_gr�0.p`��W3�Ѧ�iM���J�bI��^��D�ǻ�����6&���@�<}b��B�RK�E�ϔ.kz�I��ٵ���R�V�@2p�����ߠ��[t�)u�v�:I����Q�F!�e��u%�-Y>����ް��k�=,ڐ���n�t9���<�']W��PO_1M��W��װ;r��UlTr3:�ٍ���q'֧2?�	�����S�C���ѹ��$�;��t�2���7?y0>�þƞ�/��Q���$�|0Z����|v�q(9�� ��Bu8{��rO@w��ܯ���Y��)���$THsC�gq�T9�rܸ��f�e���Bɪ�2J��.S|�9���JPv]Z��w-�6<�Qm�Ki=Ά�v�(s�_2_�	.3?7���[�q�yT~ȷm( ϳܵ5���t0��)�;��j+�'��E"s�)ܞ��Jԫ�N��'*~ɒ��m#D$�UH:խ�Ζ��HC���Yr`g��}#�@a�{�ܛ�{ٵ�� ��Ü0y��T�RI�����T�^�3)�C���]�Sϒ�zj8u�jS�����L���we�{dT���e��`����鱚�q&=�Ϻ���K<�'�yb3�gT`y�0�m>�=a3e6t���ҙ��S/W#��|�X yu ,��c��E+��J���_�M�ѧ
޹� eJpH܅�^ ��X}���J��ϴ��"_�+�
�.���C�;�
+��/����v��Ѣ�q��,O��N�7\x��7'���wh��\����Q�j*���r���i�X[��≀u%��d^̐a�*�h>�6q�c��4����[tkڐ�k��V�G#i*v�	&IY}�5�:�.��h��F�߬ä���y݊�����I\V^tR�� ��?�$���PC�����&n(�[ш�U+�Ji�ø�����CR�clǶ	�@�c����$��V$"�m���V���������Z��}�D�~ua��i�4��Z�J���~�RĔ��l�����@�:�]�/8�G{:MD�洕���.�oD2ۈ��d�-Y�9�ش���U}}v�:oO�h��E�We�qr�I��*�5���᫡�����B��s|{š���b=��^x�SE�Q��_�&�9���g���u!Je&~�wv�ݽ�SA>KI�z����ԙv�6�Bu,�"��>������b�!$����Rq��B^�3� ->r�^� �]g�"���N�{�A6��á����@X���}�p��%*n��݋a��'N��"�ͪD�fXT2b��o�?`8F}��dβk����h�[����#�NW]���.�j�r/��3�zbK@�%��P�V�X�_\Zl:
endstreamendobj45 0 obj<</Contents 46 0 R/CropBox[0 0 595 842]/MediaBox[0 0 595 842]/Parent 92 0 R/Resources<</Font<</F1 101 0 R/F2 103 0 R>>/ProcSet[/PDF/Text]>>/Rotate 0/Type/Page>>endobj46 0 obj<</Filter/FlateDecode/Length 3964>>stream
x��\]s��}�����/gB� A�ʛ��tb;��i�M(RYӢ,�r�_�{w��ݽ d�d�6@�~��ٳ�y��O'������ɓ��\��e��O/�OJ�]�6�bڜΫY1�NO/ޝ���󨬊��~4��ٻѼ(�.�b{5ϋ��xT��k��5���v4.Ðg�QUL�n���^��:\����h|s�~�	kZ�64��xO���gu�m�!�4�wǋ���2,�)��ce1��<Ԋ��^��3�P�X�Y'�7��#vK,u�m%�[wrM�Ӧa�a�/���� ��@�u�O�<�a�PA��q�	]��2�O�Zw�@��wc��T޼�wK��J��:�G����ɋ��';y��w�HXr�������8Ã�n�9-���ҨRY�Б��ͨ�A<͝��œ��Q�6��l��8^P�/c����yƦ{�!EAn�����H��og�����ѭ�F�{��Y�/tW|"!u9��e�"&��k�{���rH�<���%`}{kӘ��vσ��Ds�(5L���]#�M���wS�Dq[��e4c�6�W�V����G��J���,S�t��(�28��r4#��a��s�� ��(P�i��F���5=��F���Ƌ��ڃ�Y0!��ϴ�Ge�,�*lf��U�����n(Y�b�P)V�j��
��8p��lkgfS����-�������ݦ�&����sA*�vȝ�D��� ײXg������OẊc~�����Z��g�0�:
|�cJ~P�6$�FzB�8Ǿ����XH�dC�kY-9�A�]�wi�6ԹGo�(#�	�L��1V��=�E��L�9ycbql�n�+(�1p���c�B+h%�X	J�~ehzà�� �����) �5�{W����A<jaK�޺%aK���]��x�>�"��`�ф�-�Ԃ샵�ݞm��j��-FQ�,��,JF�,�7�Y�P+�\<ʰ+f��;#����s�`!�P��_b3�\��e$�,��E��`�I��iL�5˶_��[_�X�c�7���|T����w�f{G�dЁ1-�y�P�K�� g,5_lK�I�j��S@��e�v�`�ѩ�k캃�=�@�4��Ȝ��|��i���$b&��]	��Jj����d�t��kF�R�c%!��|O��KJ�d�,��|-�dh��i:�����E1Py�A�^X��Pގ�K!������<����f����ߊ���x��#���mw�����A�e��3�/��:l����<;�O�i!W��ht�e��^P��8��@K��p.�w𣄢N[��.�K���;�~=� B��J!B����0�ק�=�q�E���֎Zw!�0��$^K�Yg�oRXZF����5�Qt�j�G5��c��lZ+��P(X�;f;��e�^8�{�!�+�>���N�u��UI^��iv�34gTx��)��1+)mtq蒲y6R1�F�<Y�Ww;!i+[Y��U^���/�d�L���ϯ������1�@�/:H��ɤzjҨ
���8�J�Z!$�f�����vx*��7&��R�qT�S�|b�.M@�Ζ�D T�;[���L,]�)�'�9�H�07@Տ�M첢���R*�heB�`i�b�@�F,:ψĚ�szB/U�'�c�¨'o�B�9p���4�uN;Z�\����4�,�ͦ�0�"T����:*cu� �\qC��ӈ�w6u�H��0��:ng��nT͹D�e#$�>o٬���cGq隑���RMg�^E���q�R���%W%��� ��RrPFG��I�SN#x�䖀��	zC	�t.hGWm4w�$�dȷ��.KveH֬$R1x��A�uJ��h�J{��*<.L��t�q�����
]|����"�XN48�j΅0�rM����M�#-���j�o��I9lO���V���ZMҰY뇚ߊ�Ai�1+>z������#�~U������~���0��Ng�)l{Nk"���R]���
�[�LLn�+v[�p*��ε�xu2����
�5A^�����u�\d�yN�R�"��a��z�6���g��<���@R�bJ�y.ҕ e�ej�yo�˹6��oe�bŹa�S��yw��lE�F�&���Y���Kۂ��J�]�U:	���G�ݴ홪T�I����L��V�� 
.u�Ǎ�.@���Ң����]���D8�F�,�#����� 9���v�4�x6�a#?��hI�0�D�V���6���f��Z�6���a��R?�n�l9��#yq\i��Ǚ�))�y�T��J��=n��m
�-�4�
屘����+I�J�#�7��7�����|�'��}b��x\�1�jI4���>yݵ�5.xj#����gu.�Q���Xׁ� д�@6�Lb۶�h���F$[>7�`�Ĭ��h����
��|����: Ao��`�=殛��h�������?�4��'�r�qԶk>�N�.���N)������aoJ�"
p�9g�H�}�9��@�7��_;N-��a�XM5?{c�R|DaP��Wt3��q�/P��U��?+]� �b�"����nz����y��\I��J��i�F�+���Tt��<3h�T΁2(���R[��-�
7�n6^l,S��t�֒�x^��6�׍�͉���;}A(E��h|��Y�ȉ[WN��\�G\��:c��ۗ���c�-���tR�_u�Vj ���O{ud���=Z��� ��V� @��� L<��5���L��s�1���� �ӃTR�%<$�s��Q"/\�լl#}�&��y���� aY�o�\:����c�kW�ʕk_q��`�����t�+[�������j��O�s�f�S�����V� ��_�����<�=�<̉g�jOV!�o�꽃���9pm����N*�h5��J�`�6�x㬔�ڮ��������+�Q���p�1.� "�y�!ҕ�����>�5i[Ts���r@^k�>�ۤ�a�R��{��h[�	C���[b}d��-���?$�H�T����zj�qiKg�zoPL��=�M�Uw�g5
�iFK�mq�}��#��s0���e6y�.�mn2⥑���O���F�U�X�9�I<F����ٵ�?G!w�{L��c	Ca���x����ֆސyh�n,C4Μ���R;2�GO*|ڤ~'NJ�4��!�nj9���j� �;i�����T���34�N'��,|�j >��U��Jȍ�I�E�/B���W�_2&._�a¯U�ft3��@�X*b$����rTΈ؃F-�Oe�W��@fHX�9�
��j�V�l���P��f�*��eK��ⵇ{��]���K��֠3~1��w7$�%[W�M@W9c})���:�R�-��)�Վ�nH���C���[���#n;}R���௕���u��S|A��;��HS��+���#�/M!i%����=������+Sk�\�W��R�ُ�/i��'����)I�8�b�L6A���H6isU��Z�D�3}08�l�E��Uj����w���6'f&�UbY���w~�H�]����G�aQ��u��>  `L�5��r���Vt|a��Z#�E��f�۶N�k<r\���!�h:?�h(!�ՙ�f�#N�e%�b�p�����;g�!t�%+�����Z5�������8J�/B@,G�c��l�$�˯�G/)qc����) �'��&A�C�o"�����Z����$���R�rZ��X?%��~��T�c�O���}/̹�m:��GI	���ǎO����/��Y�r&;��]E�
圣�����tڝi��/��vX�����FT{5i�R�<�?G�#��7�J ��������R�_���w3M=�Vdn�_\�K_t3:�A��7�-A��9��"�3��A�X�
endstreamendobj47 0 obj<</Contents 48 0 R/CropBox[0 0 595 842]/MediaBox[0 0 595 842]/Parent 92 0 R/Resources<</Font<</F1 101 0 R/F2 103 0 R>>/ProcSet[/PDF/Text]>>/Rotate 0/Type/Page>>endobj48 0 obj<</Filter/FlateDecode/Length 4012>>stream
x��[�r�}W��o�V� o�$��$[�ʉ�<PH �H�"i[_����=ݳ��\�2���s��u;x�����0�w{y���'�aU�����⠢��aW���n�I}xx���?G�uQ�㣲�����O��x���L5�������Qі�ѻ�.'G�Ũ�����e1	O�����nU�������h?tG�������i1��yF��<��b4/��'�>:_Mʹ��~r�h��l�^ӳ�a�
���>�7t؍�wO���sO��5�TTu�f'����?��3����^Uvsŧ�.���3�(|Y5a�yN�E��9��_f�|(�An�ɰ�jv��o��x�?ï븥�bԩ�o�O�����,L��_��3������;䣬��f�ga����A����;��O�	� -��L��Fq��|l>�=vK�\qqn��'�t�w҄�Ú���EG}����cu�p�zL�Ƣn�D��G�7�q����rIà�K]��%�q
K ��¥�/'m�[��,8���Ս��nn����+�|Zv-�����<=�n��b�R�_\C��U<a%9���<愸��@%
u�Xt��kA6�~���^�L�4'M,hV^3���x��,`�h������Q*���I.�!x�;�j6A��"X�#\Z�R@��E;e C�zW��& �dfmوu�;�v�b<�ʲސ.~����)fvH��|��֓8�y1���\����a\TrǐQ3
+	xz��[V�mӯ��%^ˀP��p tfi��M��΋�TG�ٙ��2�>�2�"W_1la��8[�ۼ񠁙!F�g�/�ahRU~�;.(i?��M9��)��c1���#e5^��&�1�K�1,fR�R�i����G����@������s���n��v͝%����)�ci��r�=��L+����U�� P�m*�,�G�x~w= �_��f\�bj��p^�暡q� ��ǻ"�hAF���8j� E���ؒ�V�d��t���q���m��kw��1S՛��a={+6�5m�D��S������x�1
��j�;�L��z��5@n��gW�҅�	o�N��x��--o�-*�_���)�	�( l�V����nK1�qQ/��ux"��	S�J 9�9aX�f7#��cАG
�u�ҝ�����",��<)1#_����(�_�͕�!u���ģ��������ҏy�;�Dvyu;�P��^�}�A�T"Wccce 2�;�Qw'���V"I�T��?��76�W�C�ai��E��O��oI��܋��k�\�'��w�#%+P�~
^��癧b�;D�Yz������wX ��U�,9"聽���/���朕\pg�f�vV�[%;��L�w�PE�}g�����ʲ�^gBk����zi=Wψ!����b��&�%Hh�Il�AN՚��f��@rABZ'�б�.�RH��(�?�t�����K���+!v��<��1h�F����{�|&��j��}Q�(�<���<5q��f<!�ݒ �uǛJWοG��Dʌ9u?<�a
��x�)�v��Q�ƃ�B�50��Y#)��U�<��	OH�=�EX٦�� ��sQ+)�ҿ� �$
��
�n�yI����n�Y=�:�=G��MƢ�^��0���L���A��g	�g{&U&������ˇ�*vU�ʸa�x��a��<?��A�!�Xy�""�R�N����\I��R���Lٸ6aLo��!EUﺰs�B*�j�""����O��� �j:���{�%���W���f?Ĵ�I�k�*27qyVq��T\LBY��I�U3��<_�o�KH~i?��-�rM���%<S�y�E��p����{�^�@2��-�^�K2�$Y��t���9r#Y�j��!�9 &��G��@���2C���,�-�*#t6l�%��\u�����g��z'a�<�$%��>���3�C�x���+D읍{<A��ݘQ.�7ݛ�<;�a��,��-�՞$U�+ �^I�G^g�'ⰷ���*$"F�g0�˪.j��M7/�b�QQr�2�A\�$3e�˜�� n\�:�s�}��W�aE�R�J�+}�	dzM���gF�Ƞ���Ӌh����E=n��̉QR� ��l�3<�+��ay*�t�j��,u�L�z1ˎ���d��y�9:&ܴ�L|.�e{4yb<ՑM��պLa]��k��x�)1�r��lb\�~��ge��_���\�d����be�w#���:&�T�O�����+K Ceb��g�s[cN�G��7mV�	��#��UI�_�S��8[�;˄.�Gd��b��D��++�n�-d�}1K�DXv��>�	8�s4�+�^�"�KYj���zb�$>����k���l:�P҆�E�Á>O�=:Ϋ"�n���B���m��Н�̑9�G�4��Q��`P�I21A��H��_3��^�,�6��@�GJ�[��ql@4�DȪ�|P�c�m��hK���3Г�I
L�����P�̓tIj���F�_
�y�Յ W��Q�����6�p���
(�n1�{_<�;�X�E��C6��g�^3��brH��W��b�&��F�k4�� �'���L#�N��j>=�����D�2��%˂�����n�M�H闢�{��4PsaN��J��&��h���z���?�=8��%v�r��@`��H�!�2MN����@��!�~@���6�~�1{n��w&-|mM�5\�JFӀ�)C�.�>˩�%�߻+��Z��P��At3��ibN߷z0����H� ��5���V�A�Cm�FgJ�8L@�����V6�:0ٜ.u3������ͫ8�~ 	�(�t��?Ǡ�A4푺If|ːі����vW������$ؕ��>�=���E�6ֱK�:�r>@�*C~�QM.��f����Q4��oއ>hVh����R����]z��J���!��T��y���7PuL��{ɵҤ7�ʓ���_�i���
Vч�T�T�j]��,���e�z�	�EzY>ral��ޮ%)�8�c��fj4W������To�C��1�ǔ�q�GQB���?���o���n�0�@_�q�jȲ�<����
%���N���19-�T��җ��cb�k�V��خVOr��M�����mMv�ܴr�P�韼����_��e�kOJ|���i���|n��&�D���x?��1��(w����}�M�{�4�oV�M�嵪՝�P�>$��hP��8[��}&�0��@���+M�K����3�As�c���,a�u0��(�D��N(���v& %)����*u�]Gmz��&�.�k%��^��cQr��H�~@'׃��l#��ӏ��s��]2U2�����OE=Oճ�}!�3�1�S~.lt0��{1# ���f=Oi��|��jݚ�L�*ᷘ3��[��'Pa��s��9� ��2�dL�z}�;{u;�wN�-$����C2�O�ys;jzȣ�-��%]/��@���n��]��N��=!���$�-�!���M�r��]���\L+P1�>P�zA ��J�W8�1�GD�������bLV7�r6=�����Ѹlg��˃'F;�S��sm�-q�oy�ұ�e|�Ǽ'��C��E޲��,zʲ�Y�f�tJ�������˝��I_�2pB��!NŎL�	h�;<7C�r:�i�Q�s�&U�k�&�����R�A߫2��0~*Xx�*�L�Q�m�^�A:�^*�Q�q{������K��P{�v�WA8���9��M��O_�:˟��8�ڡ�^
"��*;\�s-kyc��_/���]}W�[n]}{?w�:�/���:�q�=��P���"��H\����R����o���KpS�#�2nқw����mzq8�nԤ�M�j?����]f��;)`���������7a����*i��V��an��ݭ*;��H��� N�ٸ���ޗΦB��^�6K�+y�u����K�L\
endstreamendobj49 0 obj<</Contents 50 0 R/CropBox[0 0 595 842]/MediaBox[0 0 595 842]/Parent 92 0 R/Resources<</Font<</F2 103 0 R>>/ProcSet[/PDF/Text]>>/Rotate 0/Type/Page>>endobj50 0 obj<</Filter/FlateDecode/Length 3534>>stream
x��[Ks�������%\���>8'?dǩHq"F�r��V`��T�_��~M��"7��R�2�}��t��u�컳g__�M��w۳g���</����_n�
�\�7��>��e^��痿�����d��.�Y���d���Xge>��e������٤r���I�L���MV����
��O�e���I�ț��Y�拋ʫ������������;����=C#�z/@�~�2���_�s'<�ĚM���2����d整�ⷸ�;'W��<���.��r�4��rãz:���}�.�{/[K��J�"�|���������~1���S6i��8����⢿�YEsYR����g//Ϟ���ٻ��`:��9�`emڅ�5������vzk1_�ez-�� �H�=�,�ְ�V�J���q�5�*J������~����v~ς�-��\�8K���"�;A�v�.�8GL���)ȆJ���/?�r:�{�5�%�r�Yh��\���f�d����^�ًX7���.�3��(�e��x$�K���A�ya)^�m����̅���>� VT���r�����cĢ�S�Xo��)��m���xi�k�!+jc��K�D�����o}�C�\4{�	�yb�[���i�1��GW�(o�	�� ��i�{W�2�-����$��'5w!�4c��!=�������{�W]�u��XT�NrTI�:�a�`���d�����
�D�u?��UXȞA�I�a������OYE\�4."��$�GN��ɷS >���`��{��=�|_��fP�\\��97qM��R����H��E^3JDq0e]JL�wĉ����C+�R)̤��X*t`s�7(Cv\3�a���	�s��t��i�sY��3���%(a'` H)w,���:&�����7��q�(���H��q�L�0K��#��/�@��Sqsv0"y'q�}���N�� W��<`�b)�)��<��@є�����f�T�F�����b�����CVi�D�(x	X�$��8�(6�<'hl �Ш�/��?(�pY�����C�����nM����,Rř� ����Yr�N���ث��D�]��<zR�-�答���\d��g�d�������&���&0(��T�P�-�y �i����_�C��lh��N
�P�z����_�P��r�����j�,���|�M]����F&�z���MF؊��̈��\dh���K,�(}�nw*|7�A�p������C}�4�g����H�C�_a�VGktd��n[��vQ6�u2�er69Yq�n�
JҦ4����i��N8u���*Q~���͇(!���=[o���-�c
r�#�}�b��z;�]��!�i��A��v�Sk�=�as
]�r$���I�W}�D�q�m^�OK8_S±-�H�qS���}6G򃡎FX�ht��>&io��i�ؤ�b��Q��7E��w,7�UС�ŷ�4^�[�[�dC����&��RN�����Zk��Kh���8+��9�R@ć�!��0c�τ���>9L��2R�-aF,j�����pR��ΉiX�G*V�&��L�N���>~���p�`Cҽ�H���i�*7�Џ4��|���0EMi��݁�l�a�GY(�w@�ݐ}oW}�N�X�PD�8y�<�N&�|䝵�zIZw��B��Z;���MR�u�:"��v�b׿�M#��Z�HH����VGA�������!9��,.�&�/��QBo1�Sψ5���p{���;��r�ޠH. T$�ׁ��K�c��Լ	�(ҬR�f��M�BH��Bi�&oX���"Č��"�!C���p�{=�	6����|�!�<�]VCMUb�XDI��w%���ޘm�'�� �a�|��)Gh�i����8�M�����xM�S�9�C{���>�#w���X�F��DҠ���Ԃk�8�&͂�碉��i��ćN�{.
�:�h�L�����D��,;(�g����QX(N��I.�+m���lB>M��hg�Jˤs�!���i�3,udJ2&�l��Y�����h5<�����=r�+�ղ�[ꦑ��G��'�L���&i�0��c��2�_�����1s,���`��Р�d ��K�&�I��A�,W&�fB���פ07��*<�ӧl�N���K��q�#S^��*p6!�i�nu1'����V~��sQ'�)���BC�C��\�Xih�^6�������_���Nc�:4a�r�R� id�5�9�"�J�XG<W'��J$���xx��O�d֜Κ�f�
oF�ht*u�`MPW��t�M	KZ��.��T7�G���n2�S[��I�vN=��������Gv�]ݤ���cV���B����]D�S�zD�"����z�E�����K�wVzkY�|�[w=�d�"��5��T�n�Y���6���T��$�x��x�7+b���Ǧ�F���G��&W���rr�TH�4��U����F��n�'�8��h�Drs��Hq�ק���=�n��ôc,x���������2�B(fPOr��}(��x��"*���[5�k�b�W�޵M{�`$?��i���ǥ	�@^Պ�����-�_g�d)vO�`ڃj��H8�V5D�M���Q��>
2����@�k��/O;u睵���4���̍< :y"�p[��Gb*Lξ��Ǥ��a���!YRMj����B���!�й�
����*�̉���x+	2�����_��6��&ilv��J�-�5�X#�)#�ޱg����9$�����g��uظ6M1�Ƹz$�=b�K�S (���2s�X/��L�DwB�y��vW�xw$T`�w��#�F������lH7�{��FH�+#��ST]�~9��R�p�}�%�EV��(J8��C��)ٞ�%a�wPAJ�ѳ#,�Hk�yxԎ�D(xᡈ����N����Ùҍb�f��d'����|�qE� UFV�m�Clӓ�U��Q�ܩgV'{؞��,C0W%wY8�c���v�x|��!�tK)���l�t��3��������E�%찀_	�,gb7�ߪ2�hGRo���8]���,7fM������T�l3Z�'\~�u�aY	@��2�N&I� �ևKE�ݒ��������ZpϢ�����gPFwa���hA�Y)lG����/���U�'Ea�KV�)�	�R����h\�@Ŝ)�kY6��0Y04m�N�b!o^�\�x!���|,n����j�0�+����~����T�#Z��,^��FUY�*���t���.��4'nA��!����v���6�@��TUU
�U4��\���<*<�ߞW*�Fš�ȜK<ڠ(ܧ@���)!����m���1Co��BQۣ��@a������7��Fs#9�B�eI٨>�ߠ���B�8hm�OE�Rb@�	E�	��*��f+��
endstreamendobj51 0 obj<</Contents 52 0 R/CropBox[0 0 595 842]/MediaBox[0 0 595 842]/Parent 92 0 R/Resources<</Font<</F2 103 0 R>>/ProcSet[/PDF/Text]>>/Rotate 0/Type/Page>>endobj52 0 obj<</Filter/FlateDecode/Length 4582>>stream
x��\]o��}_��a�'���(�1M�-���}h�`��Z]�����&�����sgH�lZ��"9��s?�=�?����������ߟ���w�ۺ����wwgu��~�.����YT��۷�>�����0�h�����v�T���.�{������[���]�Uuy~����'��@n�ot��Z����o�x|�A?ϖ���o{���|�??�6�̺a�	��߳�����wS�Ο�ߴ���n��<�&��ُ��x�^}�w�<�U[-���M�^tCt���躈=��8[w�w"����Ko�����&N^˨�_k��A�ӹ�[u""jډ���~��
�ࣈ��J��Y���E��-���9?��?�}����_��|:]���Z֡+�Zۭ��{��Kˡ����l����?v"�hT?��`V�N�t��Z��A�(
��R�Ks���8K������'��'|��gwiM=�"L�$�'��{�]�${��G��Mc}H?�2��ݿ���*I���������,��e���?�}6)'�c%��i�ݑ��ooқtY7^�*�4�ޛ��4�i/�j�D�yvܒ���vr�rg4���u��ߦgҬ��\��R�e�e����m'�{5���£��՝�y�X�X�w��>�i�d[m����V{r2"���H��o
͉WN��2;�j�1i��rO/l�A��j��^'$@8-�Wɨ��|�{����u��i7������m��045��m1Jɮ��gmڳ���N�a<��ۇ��L;mK��-�uQ��N�t�^�
}�.J�f+�@��F}�N�c�g?��rp�U{��cY�jށ�цE; DF�zI�4:װV'+︂�ίbq�}W���6�v�b_?Ԧ���b��C��y����X��J��j�W'��ˣ�(�w�H�M���h���SOA>QCk���Wc4�[��+Z�ƿ>M\}J���;���z�w�q�$�ےj�'ɫ����C :1LS�Ni����5��[���ԭ�8W	��=!�w~�]�'e �Y8�*�M/g�q1_���k�5{����[� �Ӭn|8{�LE�0k`Y���D�s�%�Q��e����U�*���4=��[���M���КKV��ϚKB6IEn�U>��üI+*H ��Y�N@��B�@�Wz�*��+�0�^1F����0d���֏+.���-jR\��k��b��l2���i�4��9$r�d�\��{C���B�q��p�'�H}�W��6��ZI�bt@>���55���	N"��z1ފ�0$���C� �-
{�@��$Æ����Y����b�<���=���L��$#��,]��N�##6�&}�x�iJ4��ƸQUN�_5mֈ�����3������ЛGI��!R_�E�DN�f �U��2vƖ�i����~n�_�m@�}�&)e���KHYt��8�E'��nRf�|��-�83�1�$Zm6ե����z�R'G�7FO�����7%�ܓ����|H�AL���ސ9~�)	����͂��`yr+)�Z��R���ho3���� w' �xځ����z��5fy��*�HWm���zU-�ݖ���/�4��rt�3�Hq۳6��F6\���J����Ϫ;.t��+Ωy#�u��x���)�I�i�n?~V f:��p�DN��@fp�4Bm���q\gp/�N��Q���Q�;D�7b���E��TmS��?��%�OuGwyR�p�q� /���I��Ε)b��V�p^͑�B��!��/A�т���֌��z;��0�1* ����	�ׂ�Ij�ő�GSE��9B�Oԟ�n�M	W�u��{�s��]��M��97�FU5����{ӫ�\λ����,l�� v��ۧ�/��闃y��XK����6�~�3�HYヷ����1X�����Ԣ�s���F��Ƅ��Y&UФ�4�0=z�{��1
��1�$��F%�\�n*���ʹ�$�?��Lf�)��\O1<���2\��}W#�%��Ȝ<��商���z#Ͳ�s҇��:O;�nU6��NK�z�*��Ap�L�Πiz�,����J:�}�r�m�U؝��R_:I�+k��V����v�5�;�։�d.2��2�^
9%Ĳ�=)%�&�{..E��HB!@c�.�paa~|:Nc"��P������H؋h���g�<�e���Q,<z4Q��.cNS���K�u�K3y޲�Z.��iv�Re'eT_����I�>����}�p-�����fX;-���O�H�kI�%}2m֪n7:魬�"R�\,�:��p�Rlq���|�u>m9I<��[��&��\��� �
K���:�;5F��y�E���c֩0-2�_nG�b0;�l.7n�j}X.1fJ�?]i�J���%EهiJ��S�R\�b�����j��b�c�-73H��+ٖ#��RB�}�t�<	�uJ����1F��1$�7���*�1х�@��1*խp��8M�������9I�hg�Y%48�W�����h���͖�ۊx����+yq$!)7&��w7՛4�>O*б�P��_Α<Z8�P�� "�c�0CVf�<�����x�E��K%���H��������0�u(��@�)�s�dI����FnA�Vr�j���]�����a�Lu�:��L0�b>J�2����V�|�@�:�U��qT�r�#�\��&��d�5��ts=�c�ܫ��7X���}����h ���H+�4��V����b����N1ʪ��z`��<zT�y�)�O��?+�?1��Z��U�dU��r]0�@c��}�>��T��I���R�f�G��r}i�Ν%t���<�x��'�%�?����L�x��҆�r���� �i-�HX;�q-V�L2�:*�B(w�~Lt͋vn�y�U�j�PL���s���D
AvN����J֝o�М_��)t��z���xbZOjڞa�L;�� ���p�E$�t v� �� �kY�'m&١����h�Nt��j��c�+-xRr�3�4�t���_kA2�X�Ln��sg�� ��G��|�����x��AܙRm.a�JL���~�p#�
�����*t���d��LT�Ų�K`1g���u�-�%�R��9L3�X��b�ktN���^�M��H�L�X'_:��ם'�
	F���1��nC��৉��źu������}Rl6�������ztђO�;�q�V�))�`��D�{;eIf�����<�+�Ǔ��j��쀮#���8�d@�#��k�1B����O��_!7�U}�� �*vjNӷ��j6�q9�v^�^[ͫ�'u�}|v� thxs���䰔����`��s"T]�k`�I`�t�c�+��؍w��wJ�|�����sb��l���-v<�h�eN�1c��8S�{���6�
�g��{Zϕ1�Ia�Q�v�a������;�_���JOC��#��Xjѯ9����8!�+~�T<Q<�>1�jڵ�옅��3橕+�|�M��� ��@�/lִ ��U-�I����_����N$u������{A.��;ʹ[��H�/l��;`��p��U��45�v[�xP�U�4���VV�|-�G��`��-l.,��-�>�>�.���e�H "�_tg���B��Z��fܪ��4�YΫ�N�^u�
�.	jGF,��	2:`�$�a�H�"e�D5ٱ
�����|�aȒ!�"����v@	"��>��4i�R�t>A�|��[K]�ԤY[]��)�C���k9/���Ә4鴱�d��Pψ�ME6G��0�z�\��9x!%���Vahh���D-6���|�	}s/Q�Y8����`�6��֣%VĆ��]���f�	�Ҏ����2-D�gW�9��G�%�#;�����_9����&3j�WY�zI:�BO��u��J����S�
����wkV�>M���()4��.��s�D��l$|#c<� ���^����� ��*���W�)m�n���˙�і��/2ڿ�� �5����9����I0[cs�b�wׅ��_M��������B6�W&��touim`��l�4�AA)m�������?
�d�ԏ��F8,/*��ayn�!����ț�X��|Y�b�Q��kL8���`����/�1O$��_i�4���.��j���1�ѳh��`��kC��Ca�>)!D�c�?��5���	���m)K�>7��o`j�FԴm~�ٕ6`1�4��)zd��|qn2��`�-��fh'{�Н��ԋF;��o�!��u"���O�e'����p2���f����"�<zt��q3
��c���~�5r�
��F
҂��$m=��E4��ܒ@�b>`'Z[����0I�.����Ѧ*Y}i>u�/u�aW)C����;:�I�Ξ&G
_��F��c���7FЗ�?�a�ifk�բS"[��y�o�bC�"덟�bǂ_ ��J�)���x�Y;����#G,'����cmiiW@���s37
endstreamendobj53 0 obj<</Contents 54 0 R/CropBox[0 0 595 842]/MediaBox[0 0 595 842]/Parent 92 0 R/Resources<</Font<</F1 101 0 R/F2 103 0 R>>/ProcSet[/PDF/Text]>>/Rotate 0/Type/Page>>endobj54 0 obj<</Filter/FlateDecode/Length 4204>>stream
x��\YoG~7��  �,�sp���F�s�X{c��n��:8E�6)����]w�%k� �bOOw_U}]�GϾ�8��>�={�8��Q�_\����xҌ���)�Q1=>��=���2;-G�I�);�G㓫�?���S65�/�ŤMO�۬	#�� Y��ݧ8��N`�Ix&/F��}�T�'qҏY5�u����8��twY~�遛,��l�8v��¿�YXM�sz�߳����]��Nv��2�^�1~.�#�5�*��"H"����"li���:��-�c�[g�3�ZU����	���/i�4]�o��X�E�VQXw��k^Պ�g�ꨚ������T���y�����ѳ=��{�ǣiE��nA��F�"���8��~�����妻���S-��� Q��{c�.������� �/h6K���f��+ٶZ����{�vR��o��9�/��w#�Kr�����ȷN�d;�[�'h7���/���`3;�-.�b��Y�,p�:��|�h-��M
����+ܔ�+��]���ǥ��ɒ�*/a6�=NJ��O@����K�<���
{�}���ь�?29�b����,g�f�B<��d�1��{݁<F�b�cU�D��Jq�"�H(6��,o¯}�ۺ[��{�vO�O�V��fo��N,��@ۚV�1G.
�蕅�3�8���h_dl��e5Z�&y5IusmͧEݰ�2r}pi�d�"Z���U&�����+)X������ Ŕ�����6����-E��+��ıd�4(�b2�!S@�l�o�b��ӄ[�6���VӌG5�/	$�7rx6w�Z��9���&6��������f��X�I�g�/*U�b��%!p8)/�7<���m$��p(~�ڣ@q�v�k�0.	S�;٤��IV�1:F43��T�`5��{k�h�_9^�L����MN0�p?jQ�Ǹ>�R���x'��9в� ���l�ְEP�O��� B
�v�&a2�O5� +'s����c	�3"y��y/�ga�4���f��.������ש�z��O��8��Gy����)�;Si�D�JI�0�_��j�:�ե�u��S�O'4�\YE
���J>R�->��w�s7Y/��fN��F�	qq�=�m�i��^��P�7�؋�K��-y08wN����Y���S�G���u��n�EFso�F�ō�\��F֋!ɡ0N?�`&e�b���4��ޚ8��ϴ��OВp�Fj���-M!�wĕ�A�#`�+3'���U|#����c�F!�3�dUB��0!��9X�m��+	�"W�V���4M�Rr�+#� �餓t����@�s�#8�Mh�V�F�4aJ�ҁ�O��Z����i�%��$e/B�,�Jы�^h��^��*E̦c'��4E���^�4��Z�8̒���W-�~*�G��F�`X�:�����l�ΜZgU����w�FwY�$_Bz 턀�E,��t�_
�SK�v�C�����1�Y0N�"��9Pۓ�Vm3�z)4���7��T=%�gGI^�LX���׽ة�w�� �ڂL��M��������B�J�$����bl��.������,m���9�P�tŕp���Ql���t�)�%<5c&��$T&v498U�[qI�=��R}�.Qe~9�؂o\�h�2���X��ɕՠ̚��\�5��'��(��I�ҕVz� V|�
���-zl�d��	����@���G��7׌�H�1۴̸����L�o��Z�J�Vr��5����"���Ϻ�-��&� O��%x~����o�i���.iY�Tj�W"L>
֥��_!�I��W(���2��S�	��9\�\��R�1�C1��)��KSx�����86y��ڥ)K�����:��g8x���1��!{m8N�&a�ΤS�4&qOX���Dv����r&�@����Kdv�o�R�"�Τ=�`�"��9|5��GR��J���#��.WKڠ����`îjr�fv4��o���d�Oٞgt���� �KWvI�ɀ�^Jf�&�S��)�MXU-*����`P�-27��WQOH��xGqصs)CB4�o�(K
`68��I�����T��ѫ=��\�÷:����Xk1�.��b�d7�iJ�9��%9�G��<i"U�>��"o�M�gz,��V*�FA�B�KYMb�c�j����'KH�ܤ"):!e�T�v�l皸��H{A�b� e|��;:�ZZ�r�!��x�J�~�"��pD������h�����\NNƟV�o�&��Xaָ���X�¢}&��Oe?�R���e��=��a���s3ҩ�\}щ��Q�6�
��*����_��?��#$扇tN^I�
�l2rS��!\7}
�I��L�`%�c����nn� :�9b�\�:Q4�ٞ�Q��k+��Ӟ�9�D~(�]MK=�o�Ѥ����!�F4�	@��u��m��czd����+�LF� � 5~�0�3�眬c���5���g}���V�+��F���wC�Z��.x�t@��!p^eE��k b��`��:�_t���]w�FRiA���j�mkH9[9DM����J5�k>"<�Y&=]n�p�(�RK3�$�5Z:���:��|
��ȠeN�7�
ǕZ��tά1��5Ї�<Y���&B��`+�?_�E�)x~7��=�]4�I��s�����G��8�h~��*/�=MO��N�3��?&���Q6=�o�.��3�k�0�aYo��?����3ӌ@���A�d׆O1�HB�+�f�P��Z�#(����iC�u�P(���`�r@C>�\0q�3��q�=����1�ƃ�����k&����H�L�癍�dy�a�;Є�\��M�Z����a���D栿�e�wF�|hIxX�����C��QU�(K�R�2�E����s���;JK�~T�?Q v�����	��5[�ǿ��� 㦕!>�-�6�W�>�H��4ĸQ��sV%!�MD�� �@y5	�"h���N�D��������=D]"�����qI�:��dX:��d�ڎ�c�;������~��vx�>-m�nςw"�~F�`� �: B�a�S̪N����ǽ$�>AA�M<B�B(�.��N�!�?��F��c�B@�[z�����u(�ӷ�q7����|@�tp>z 쳺u��,���=��	'�ˌ3b�kM'�I��@S��z�����?�I&��֚��峇��Q�1�c��פ��'mس}��=lܾ��@?ڃ���Bw��6S�Ÿ&��1XS����D�����H��
�����-+!��h��)4�a+�,@6̕��ee	_����4d�߽���BҟM|���'��7��x�>�.UU�ce���8��\)ER5�떚�/�6�8��;|%�P/��|Mɨ䪦}����CN�?�\Z�R�޴���d�s�ܐ�&J��KF�&�Ù��:k�٣U�C�	
�d�A]��E"mQ j�V�y�)1��rMʱ\ώT:�\J����Ǝ�i�u����`�_"��b��)?&܂�|�|����w%G���Z�w&��f#����E�4�0A*!��\�%'u�1��a�OR�|dN;���/ۯYa������W	��hr��G�>.E��;�q2�c�-e�QO�nߌP禺~R��z պj :�Ń���U��Y�#�S/�vN�If|�؞-��.�f��� f���]^:���T�����	P䰡ˏ���h3�y�խɐ]N.���n�ZO屓�c�67��	�	Ȑ��2`�����;��P�ݛu�Jo�BKi�+���kS����rC�K��{W��2��y\�IG�#e��"��HQ�̦8��$/�^�����*�'ZT�DB�s�S�0��^����"�w�<5`���eɟ�i��?�$�*�k"�o�i�dz&9b��5�d��+��K����4n���x[|В�zS@�̏�7ɥ{'�]�$���N������۵��w�:+&P���Ӊ�V)4����� )��P��n�lR�MH�����%�>^��H�Hu�r��)�۽}��z���{��RB`��Ku�ٓѡ�y`��� uC���?H�!�/��Ĳ�2�s�_/W�>��8i��t��'���iS�N����
endstreamendobj55 0 obj<</Contents 56 0 R/CropBox[0 0 595 842]/MediaBox[0 0 595 842]/Parent 92 0 R/Resources<</Font<</F1 101 0 R/F2 103 0 R>>/ProcSet[/PDF/Text]>>/Rotate 0/Type/Page>>endobj56 0 obj<</Filter/FlateDecode/Length 4255>>stream
x��\[s��~����&y�8c��d�%i�q�Qڙ4}��"��eHn��{��{v@�B:��D8g�^��@^|}r0=���^���8,�I�8<�<(�rqX�&��pQL'Eyxx����Gg��jRMF�9����'/�~��Cg���֢��J��/G�qQO�����|R���Iq��h4.��p���b�:����E�����S|n���������߆�g�e�o���u���K��/&��?���p�K<W��q�����b2?zǇ�a9�N�?�c\d��y�����4�(��G�/eXAv�g�k�/��ۗ��r�]$5<���2��pu��|JLUGG�u��L�S��0�W��/荌�iT2u�r^�W�x�x���E��lT�ߛ��E8wQ�K�x�s�2Jy>�m�A<�\E=:&�-�n�	��VA2�XNAӣ����^���L�}Z|~-WI^L3���o����'�j��tO�����tUV4�Q3B��Y[�7V���Pk�l�IR&�}
��p�{�O��������ֺb�.ش�&W�H-��#$<Wq����d)�SЂS9d��mTF��\���"�6���_;�`��/��|��,���x-l�4d"���޽���ѷZ6}��G����?����3HP�├�ͫo� -y���p�XpJ���M\p^"�0ҳ;V[՗�5	ޚuNY��+�Q�o�E��DԱ*qKT����F�[��K�<��q8�4��*���;���-��y�I^p�ߪ�N�e*�v�*�<���P�u.��c�����S�Y~�&��A'�D>��(����+=W%d��.�Qrٹ���1��6g����������t���(����6)�&����:$f8�x/���}�Џ�in%���"�.����x��j�x2�º����s*�'���A�m��Gr>�l^�b�5��f���8S�CK����@�	�n*��9f	w��1��W���O���猉S�1�}�G���@s{���*� X�Q�m\p0u���W�q/��?��o#��w�~ ��@����O��L�u�,����!��{�8����+;��qڎ����ųoݧ�𒩵� ���Ǽ0{��@�~Vց.���{�=@�9���.�Q�G�m� G��"�`D#4@?�
1��xm~˅�#�x�c�@ڶ�����#�3���r� 8Z�.�D�D{kQ� 2��)�g{H�������Nm�~�=�:!.]K��T��n���@�qwǬ���-�;e?y�������Rx(q�	��V�î�G:vK ��6Q������P%.2��,t/�M3�����X��g
��>�m���4�뢄"����X�oM�P>!�^��^��DyY*�p|b �� �&.Do� �F�|��a�XS&������]"�!�j�O0�^�3���t��5\��O$,��)L���B����l�|�E�j��%c�
`x���Fs���{����v/��9Ϫ^$��<D,�1�w�t]g������/�x��GE�T���4���tC�]ڐ4��ψ�[�뒝�s�T�6{.	�E��v��dw_=[��|���[E [n*�Ń���Uw@�85� ZG��b�˘l�Xn��e7��j�6�Nγ����)[uKO��ke�Ɯ�)�/h�b�@�W�S��@����`�`��.�܀9��譂�$TY�3�Pw��o/i"7�S��%~��ʳ5MzL�BE<�#-�]�5�Y�l���3O��B�uI�j�|T����W+=
� �lA%[�)NgjM5���������>2&z�,�z��撬9I�H��%Tm�V���*�X��f����ʹi�s���-u�����A>I��c����#�ك����1-:�`
���X�q-��#R��9�_3�v�ė��	siO�����`FC�^DS������!9.o�����������$Z��s�I�(/��Z�a����McV��Yg���`EU�( \��Zi�A�%3"���Q���n����wy/��l�H=�W�>Na#m��(�Y���0/�����������* ���l�jb�//u���g�&#;wIH.�=j���|������A��׶�*�jB[���LA��"�N�r/����=K������� MN��1�n:�|k;����nǫi�q��-f��r?�8����.z��S��mK_� �XeM�H�ql�iW���%���+�$�H�r[w�	����	�_�ɗ��3��6�lW���,�;E\
��BmF�H?|=fh�{�\h]7���>�kE�A=k`Ya4�>�[����A�s�v�ָȅ�s��;��)��r@�Y�7�	{3R���Z�&2%������u��#��(�{s��\��l�`h���i��Xk���d�Lkռ�i�`?��S���������=Z��m{�m�)W�!K��r.1w6��;xʔ��r��0����A�W�{��L���j5WgV�g�v�f�R�-�=�В�Α#VfA�y�v%�P��kv�Cg=fu�Zl��\�g����r�N�:�,�΍���Fa�A��8���&X�tr�!�X�n]�E|�8�_`Ow<Lw�9�����k
��N�'�����G��y�S��D�aϩ�f�VU���J�c��nWr෣r>� H�&����B�a�y��!&}F5!�������,/�=+�=�؄�Օ|C�d9�Q�=ɸp�6w����Q��ޥ��=��E�Q���h�6�A�4��\�&���둨IT�Y�$�f�,�Z������]��Y�㋎�	�.n�\��=�󾾅���dof�P#6��à�0���ͤ��j~���:?��WKD�#sfK
A�Z�j�&�@ǡ�X��p��Π3��{c9�[iݎ���"��}���!�g!�8�lH�l��P���f���������w�X���M�ɲ�L�����	6�SJ�Y�ZEj��N�V���1�j�I F��ͳ� �N誛�l�,ri�ແ��\;�(�a��$p]Q�u&�֚�2C߂���*&t�l���l�˹9ف3�$�Z�c��u�
�L��h�Xc֩V̔S���i�p�u�ҹ�!i�"B(�PN֗H�1�f��L�P���6�[�ۛ?����GSe��]Z�Oh�:�A��\���p��M2�r�\5��5��֨��L!�n�&�\�)u�4��eXa�����������q��[��*.�#ab����~��ZDq�0�2��Ny��n�T��y8��m��M���:mfGԪ�����êߟ/��۩M��0�U���	�����S���%az&�8�隹y��NN��@�	�-��rDF
�k���`��-�&��k4�++���03r׏~$v��g;q+��E��{
�ϲ�[{0��e�w�84!�
"a�Y����o\�4�P\��d.h����wC�`P�c�W�;�G��%�E�iQiΘ�x�'i��I �̔$d������/CZ�u(R��7�Dܥ�lI�-��K	�6�/���jP��ⵕFR�	{�4P��΋:9D��f.���������Qo߭��*�U���Y�\����a�?�w�4��K��RY��"�O�?�Qbq���`3ϭ�&y���'�(]Z��^X�x��s��S�d��S�b�J�	~N����-E�^��4����0�IY�)V����[O �Y�cJ���X֯G�~1��uq]��Z��z+Z��t�g��h}tT�JGsHvb(����;_)��q�?�Ni�����S�a`7�T�5Tt ,GO��b���-y���`�����9x�(	P�<�XLLռ��گ�-Q
���Z*����&ţ�Zi����xy�lϬ��t�&�֞��,_���(�PA�5ea�Q��q�`��v�e��w^v�E��H���Yn��i��������u�����%�	�\�T�DLչ��Ù��_H�̸sz�c�
9M�̼�������q	\�eM�Zj�(�ˡ
�q�a���^5��:��� ���{9�[:"]T�R7`4K���f���H��)�U`S�J�J�	���*�太��!� �����-�5�Sw'+����%��S���M� s+}0.���tx!%�V�$�pSI�E��S�O�E��ѩ�2����CЬ�qMZB��Dʐ�;�G߹Q4��>��Y�k���DXE���,��uҢ�������H���p���� �
endstreamendobj57 0 obj<</Contents 58 0 R/CropBox[0 0 595 842]/MediaBox[0 0 595 842]/Parent 92 0 R/Resources<</Font<</F1 101 0 R/F2 103 0 R>>/ProcSet[/PDF/Text]>>/Rotate 0/Type/Page>>endobj58 0 obj<</Filter/FlateDecode/Length 4143>>stream
x��\�r��}�*����"VE1 A�o���l�NlE�C�<P�.���VK�b}0ݧ{� W���*iI����>}�>������8�{�?zs�.�e�L���Jz�<��es<uQ_}8���r=�����i=����|��ɧѬ��lG���/�`7:����fT����v��}��ŭ���!�K7|�۱ʲ�p������ڻ�br����q��(~���Nۇ����6���_˾�k~��{�ȵrcC�?����eU�N�ۓq�9���{�e�|,���!]�>w��]���t���~n�>�{��ⳛq��kl�����@�����=S�dV�t�P�[�9�����Ю�݌�u�磷WGo~8z��&T���6�O��;���<����Q��D�CK�R�+uwܴ�ۈ6HV�|�vIe޷�U�Ź�o.�o��%�����"�$��:��^���5�W=�q�^O��^uM���Zjn@����>X��$g�7V<b���%�Z7�v���h5���hy^�Q�����"f��>��@�@�WG�nͅ4���j�<�^��� �������$�`�k�D:���lZ��Ӗ7�ְV�;k6�	��b��B�S�[�Sk�6��!�3�V���W��h|S��N<���E+OmWh;��0�li����G��B���N�,B�}���d�G�����U�`gns���ؿ�%��x�� a�^�q��~�LI`3Z���
`5g ~f��丹m� hX�Ӏ�|t����ze<���X�8w�ۺjF�q1�����b:� s�C�u�o�X��rK�\�3���Gk�9�H�q�l�W��ٱ!O!��~T�L�B�D0�զ�pE�>�� +h�TS�p5��[Z�ԭuψ�utEDC��"zO~86jP1�:Ԑ&Ӣ�X�܇|O��b�6��8|eQ��7�i��s��Rl(���E�\�Y[�hĜ������r�2��P,���П\����+���s����vv�G�ܢ#Jƣrztޔ���Zr7�^�o����q��قV�0e9ǊnȒ( ��HM�'���h/)]t��+@!9[�K�;�V�&�Q�b�%�	,ر�p��V�]��\f��t;Bٔ��{�Ĕ��<A1{|px����e14�j\4���%��c�Z�(3F�JШк�B���<��U�IvS�?9�6�Xbۆ��a�X;�U�tn�"Rֹ����]�@x�=k�j��F��F�n�K2��e!�4��\r2'�M9-�O�`φ�h��i�%9�D��G��x��W	��c�[ ��ֽ�r��LRx�胝t3a�&2S�E��I�Ç��j9ճ������Z�%���ָ*�C��`e��c.���|��bW�1����*����[2Ϳ��/�U*����gr����!�Ds�:(�r�0'��������]Ћ�,��1������R���q����(c��.��W��~v�X8��K�5xɏ��~~+��R7������]����;~O.O[i?�N~:!Q_pX��a��d�t�@��c�R����,F�;�T"y6\��_E��S��@h3�K���;F�'| h/�̒�5]��e�Wy6I�K�����,R�����E#���80��}����bҳ�L�@�y`��\���piS�N��z��#�H�Wâ�hs[+1�D5e+��HPu.���u� ��Y����6��"S=F��C�@d��@ci�[��Ʀ���=�<�R�qc�bAdr�Ju��*|w��`����lO�5UkCř�i�������5�lZ�
���,J���ШR����_e�Ą­��:h����;�}	՜�-�
��uR��Ϟ�� W_
	��L��j~�]	�^�Y���r����y.���e�z�V��&6rP#�ɦ�h��U|���ߓ�5��z�V�w�i�{~�KOᴏy�× S)���4�΍��?Zʉv�5��c�:��vK;S��"�w�,���T
K�x���<��n�����F�-����=�-S^xim+ _lqU��l�pL���?7 �;|f�HMb\�.����5c2�yAd9�iv������a�w)}l�`_{�r:.&�/z�Q������r/{��ۍ$�_��!?�>]a�IǗR1IH^�ltw+�Z�5�0<���p8��d?��+et��ln��r�MV�Y�Q��crz��w����m��� �����Ρ����[CT�a-�r{����^���)�?�Q�w���wΈ���[J��arf,�/��h@Ӊ6��̦��^��1��҃H��N�����J�`_ɒ���֤$bp���q~7��_����v6�iO�ڧ��pǮl�!0��XP�����4�4��Lg�֞J�E7���-.NZBa�A����-��\4���(�;���qpj�2ឮʋ�ߞ(���z��Ʈ'�vrs]ֵ�����%���"C�n�}O�u@�Z��U���-y�Cs:��kr��r�oY|��0nyv�4B���lgWΨ
H��av�Գ;{׮?Ѻ�b��~i=�J�m%M�D�#@�����wH��b��U�޸��VW�cp
 ���~
��╦P�tH+���`��q�U��SRM��l������j<�ԕ	�jx'��7���r�>D�M�>S�Ւʵ��
��M*���&{�Q�q��>-Y;u"�њ����t-5�x�8y��	�֒Pv�5��[�Wh}���n�[�i��=�ϖ=5s�~���*�L&��������g��lo6�4�bo	2�[_�M�N}W׳��|�lK�fe)�}��x- %3�e����̞�Fz��,?\���h�|y~p�m���Z�m�:���7)�v&^��|uM ;9�JA ϩ{�ۙ�k���UK��꒺|[��,�d5>T��Ў]躆�#�u�e^�:����0����+f���騧C-����_)�g~҅�(|J���������p�'W[,n�cI0ۊ&Px蝡E4�.�̢��XK#����,�Qi$Sg\��Ji��f��,m��3��Ÿ�31�`q;5A:��o�:"`�|��4��I6������H���o�����]��+1���/�PW�]͂��]��?Ө�?[��Ǧ~Y�K��Y�*Y~����M@"s��}��0*Tv�2����f����m���A�ȵ�-?ٯd	������"����bRBB��A{�1w��cW�ϓ�W��fd���+���s^Y蝶h2���������ZšzC�����8R6���K�7(�U��2�T�L*��,��Zrp+�d��{�b�1M�Ekz�RbW*f��|�v�mM�͖�e�o�$S��=��ױ�<�	�TK��=��JL��{.�.�V_5��+�P���b_ɩySw����)�w��F8�9�>v/uxj��Q�DZ���x���4�Ba�2ھ.�cy;�w�|�:��J���m[��@�������u9M�%\cv��h�>T>�8G�-��2�#��e���B�2�)�/��L�1`�D3��9>7K��%�ɴ���!�{��G�6�Z*ٷ�MZ5"d	د&��\je>��+ǔ����KL&��veb�V�{�o���^Й%�։%V�j�gyj`�II[�6����AF�a%(�z�X�r&�@��Q:����x4Y�ݥ�R8��;��q�ߒz��'�Ma��4k��.����1E@����8 K~����&6q�����JG�hyW|Y����Xl�Л��M�w0vN��g�J�K�)��.k�E2}T���US�����8���F/�Td��f��pnA�ėa����U�����'A��=��X��F� �9PcT�@�g:��/
��i��r2��������jr��f:l,H �����I��\Lс�'$�hqw"L����H2w_���{�QrOU 5t!�X$�����n�r�]�S�� ;�$�(��n��S�?�1�V�u֤$G�?uA���`���;H���b0���4n�o�H��4E>ψ�{v���F���V��f����h��'����2e�u�����ܸ��4��U����/�o|���-����p�����ع���=���
endstreamendobj59 0 obj<</Contents 60 0 R/CropBox[0 0 595 842]/MediaBox[0 0 595 842]/Parent 92 0 R/Resources<</Font<</F1 101 0 R/F2 103 0 R>>/ProcSet[/PDF/Text]>>/Rotate 0/Type/Page>>endobj60 0 obj<</Filter/FlateDecode/Length 3859>>stream
x��\Yo�~7��  @�V���d����d�v,-��%J2c��eJ����]WW���ga��3}���W=�t��OWg�s�����ŏo��������Y���v�O��IY��������_���΋�&���v��]�u�7���6+ʼͳ2�VY���b���[���}�Z��C6q��k��t��r�߷p�Y>s3����.fn���-�(������8�j3�b�w�EO�{k��WM����q������v�]���q���i��n��k]�Ze�-��H���?��hUնDn�卟k���9���Oj�g�}������s���������}�׳���^���ŧ�a<ˋ���VH�EU�ͩ/HN����m7��S���RI7ڨ�����t[,�@�;����ޚ����_�H�K����+���y'�N�e��yI��Z��E��������[�=;�-�ثl榠e�W��h�L�r���.��ݶn�n���ǚ���5c�:���k��W�
>�GQ����k�q�]xA]���x�/�t�3�ކT�E������+x��`��R�G3��i��	<��[
u��c�d�8�M�\�}O�SI7,ſqR�n���i�!{����~�?��֕SJ�gT�j3/����صȂ/����5+'n	^nm唘�;�+}�Z��N����>�ص�ڦ�����jK��t�#~��%�4H>���x>��7��v�S��-��-�G�۲�T����:�Z͚$rϒ���]�����:���W��#�5��I�\24��F������l:��4S��6��9NC!���s�q���������Ŵ�ؕ#j$0�&6������\w~�68�Z��c�����mŧbr2ݍfA����D�E.��`�}��p0v
=�9_u�$�4��M�c�NR�@2Ҩ��$2�kk���\�P����r�ϊyy�0�`���xKv��1�F�,؜�
x�ֶC�^��L� �ɻ��eJ(�'�X**�l����$��4ah1kV��KV�{p-��ĥ�v�+�d�Ɣ���>��(_�dK�@���Qt�*n�!Q��?Hh�j.
5ǃ\�\�0_sXeLk���=d	m�#�	��7������C'�°�\[��.������?@@���G��l�**�XC�h�22��敘�T�����H�>h��Ǐ..otD-�Ntl'Z.�؄!�etF0K�+�����xAD"�-�K%
�B1vݰ��wƣ#7����}c�P�O.iA��%���Nr�և�C��ZDsCfC?. AJ�]�V�nI8<D-�f)�t��ʄ��I�MF���YS#�g$X�D��i��2���k3��M6Yh���$��@�VJ�q�l�s�C�]��q
�P��H���x|(��&tɂ~Sh1�&�n�th��8�B�[h�,�J�}(�`@��a�6}'��TN]���C!���:�]h���B�
�7�g.D$y��} �zD��$))Vq	�<э��g�Q��9#�B���3n�f�`���ݜ���z�)�G�{��� H>j����C+.Ѭ�k@*�fF��L�]�]Hr���7��X��(�%��(����+I$��~�©d'L����N���*���/M��E4jo��t�K}C"(b��H]�[\�T�6[�G�7�j����J\��3��S/�l]D=wI~�V�HDyU��隱�r�9si\a�>ЀƓ��M�OfN)�[''�M'<Ka�b`�M�{�l�>ܸ� N1	f&pc662"#��)�C� ��G��2+8Pv��Ab��26*��e���	��j0�	�7������0۪gN�&6�,��<ܚ��¾��N@+�F�)��U�$������Xa`Ӥa�{m�h�!�V(1e!M7�T�ȅ�э5M��=S��S�p��|�t����ũ�^ϵ�@���m�:Qk��p��E�~��յN�wf���R�1JZ�V�N��~�Ґ�+{�m%>	�3�a"�$^������!��[��9��H�j� ��\3�z��I>��z�η����L�i��n?��9a�̱i�M:�<	c��(�F�x���,�+�B���F1���z�ŉ1�v���R��g������$����;�0ܲ�.�[�L�mh���-2O���^<���$�D��:�������t��a3��eTl��u�8P��k�������4�f,WB��T��U:.-��jZ�+�u�*�]�D�*K�k|f�3Jwh�D�c����Hl����вtV@�P�����ʶ����1p�E�Ŕ!ƞf�^���7��P�!�^��T�W^|c���`~�m]ft��E�L]�	�������XLN��"v^�sB�����ӿŐ@'K�b���9��z�VE2���w��Ԝ#�,�t4ԙ�]�V-\�H�#�T�}m�Ҁ�bB���Ok�ȇ�N[�umΟ MF^�3L����V����;^Fq*��	����%�wK�V���:�hS�7�=��5�gҩ�5�m*�臭��V4���V-D �^�HC30,	NIi���rF���q�hD���-Zz���3I�(C��gLu��Gt�G��?:IږF�9C����q��"��8�nYz�C�׊�/�Ir'8�9v���Y$ak�c�oU�ǧ��*��e�y� �+e9�YB��,���t`�*s��i�*K�L���EEk�'�7��o$�9>���l8��8��.�-��<�CIv�Ű�U�� ����8�(kC�YN^��0�;&DtC~:B�KQ��񻼂=�q�_����k�:��t�H	s3�7�e���IKb�i�B8!�t ۴���v�V���i��`�2n������
�p�&���itt&�����z���)VYj�zh�PN]a?)��X0c��ڢrK�jۖU�!��
��#�ih�J���~;���Ӈ"��z�vE�*9���=C>錏_�l�6��0G���xⳠ�Wc��>;]!sLT0���\Bj��6��M [s����$���[�B��V�*a ��߹Ek­4�����h@:��*�>��:k�P[Tqƺ�E�.��L���EH4��p���f�nOtWƲ��;�k�ċ���B&����<5�ݭb�M��,���.;k�������>b��V qM�T�j'uW]�]�7Ш�6���\D:�Ҟ3͗q	��=�G�X��֥V�i��!��z�8��7���N
�x��4F��Oة��I�O�ĻX��w�īB*�\5M�����>���d����|R�~���ߣY�q��T��NJ�xn�aM��r��Ffc�	��ү�}��S��4��9n� .��w<M:��g�!V�����ח�M��;KT���EYd��;��(�k��S�C&���兽�-Q�b���wt�O�1���@�����AnC���	f�T��v����՞�K�u���^��>��1.� q��;�y!i��ݾ7��I�f�4���0E�u^�p�<�A���B ��o�f������c����C���t�෴�?uY�D�ZXh�m����@[�Ҕ���M\�5�J����H�}��撸��U+�'�ؘ"l�R��yo��a�ԍ�����u���U(�N�}��O5�c$i��po�V�T��w�E�����z��,���]Зz�("Ao�	�p_����O�? >�A1����FS�7r�o��y���q_ L��G���"��^ n���]�Ym��3qq'B���Y���kw�o����(��ռ�~j[w,zV�7Ž�b
����
endstreamendobj61 0 obj<</Contents 62 0 R/CropBox[0 0 595 842]/MediaBox[0 0 595 842]/Parent 92 0 R/Resources<</Font<</F1 101 0 R/F2 103 0 R>>/ProcSet[/PDF/Text]>>/Rotate 0/Type/Page>>endobj62 0 obj<</Filter/FlateDecode/Length 4288>>stream
x��\Ms�����A���2���b+���>R�Oq�H��� M�T�_����=ݳ���%�R�@ ��==o^~;z��ӣ���������?6����O?��q�|����MS����O���y�qR7�츚���oG==z���g�=d�ES-?֯Ǔ�����4������i�_����/''��r>�����]���N/z�&'�jq��L�w�.�w��uU{�~di�8񻏓�exs�P�#w�M/��%��ď�,�'����d>�[?�av|a��F�ۖ��`��g;?z{�ĩ�i7�4\����?��֬�K̓�=�sDڌ�����&HK��o~�7/��A��3�Tq�( �}O��d���t��M�i�1	wâ��"��T�5�>;�骪;@�@訓���y��q𑞹���j���d�x�K��H��.\�K�gaƂ�=���Q�(AOJ֫@�!ǧ	�0µ��#�t�P��H��� �5 �m7 �]ἶX=�]Э���<@���b��f�dEә_�EWP���i����y�)F��[ͫe��a<1(/{�0�f�3����UO֖D�e�[xU+'�9�� �	��Ƌ���z�Q�ٲ>7LJZqМ���@��VU��/�	C_aʆ�M�B�|@�e�M`�H�5}�AaY㻚�b$T�������ŏ�a5.&��WAUn�w���e���@�f��Z����ڳ$x���|�(:�3�;_"z�!�����݊���v�|^2��>k�* =ሤ`�5�:'���۞�6ƅ�RHSk�$]�Q����X&�'
�ȝ^ɓ��vY�;�L�)7ߴ�x�����K�+v�����nUM���L[b��=I�3?�e?�^��k�Vq��Q;�Aa�[��p�6(�Y�8�����-�w�����-�H�^��B�
��c�1�����p�����um���ƲĄ�	m/�8DR[t�P)YH�ڀ�8�z`�p�b'E:�GW���\e����k�8���y�����9cd�E&:��+7���S�'/�DS>7�s�j��s9�>�#C����"V�
W��� �������H�"�%8���[�~ğQ�18h`L]�b|E,k�����~bjyk�2�ةD���v�Eb|�3[L�M|�g~ J�Ϲ
I�Q��h�=ci�2}�R @��&���s���3��	���G��`����g#���nl���UG-^��v��v�=��W����=%Y����l��q��,	�,G�>���/�|��#�1������OjNW�g�[2smb���6��%e������3>>����ĭ40��f0J���B��=L�z�wf�y��&ԹIK�ܾ�s,���79�Y�Ol����m��+Y��db^�G��a���Q��\���YF"���Q2/3fu�tnS���15Y>I�9�o�X|_���{f��lv�N)k�X�B�J<ʭ8.)�T�eM�,�s�L��NQ�_�Wmoy��S�L����E��/�h=g�&^�9|�\��@,�"���|i�yYD�L��B�I�=@�y¶�V&��c�/T�2�N�}*b�t�g�m���4�Do�_�� ���!��� aa����cm���X��;�{aF��^g���|f&����6��-=�=���rz�y�-�����"�E�\r�?��O����A^�sj�H)i�?���j&E�
���/���σ���l�V��9YRJًgm�@�g���,ݟ�<R�N*IU"R��S��H�
�[cAMG��X~��m���bYk5�
���X�������\tNqa�Β��<� (�ry7����h��Z=$=����-�d���#@�1��,���~�[\�K�8r��{Zao+\2Wcn�]��J��W6a��74�*�4���iuq;�7�֑�_2�CP%��C����m��֑L�%�	��ȧTW�# Q�&�w���w]�q�[�߼
�����ă�<�u
[�c���e��|���R�fxC6>Y��,6�_�oΆQ0��fNď>��Zri\������t=+#��8t�H�~��BR3�X[ lֲC~��@�ȰAF���լq���j�z��ulB7�^���kTH�A8��e�b�7��\LeG6.=^��������ץM�߂�XR���F����:0�d���"3�!�Y�2ڣ��-�"�R�<Ĕ��"��6 �p�Њ�V�,ː7]�K@*���8�x?2�o$�:X��#1X3>q�""��joK�M�GB㬜r3�gp-�F�&>�g-~pfNHڄ�ܯ��v�Uk~��Z�gS��M$�+�e}Q��;�	-���O�_i�x��<��g�E5��y1	;�M�n�!z��T
���5y�����4����RL��{��e%��-� M�C�[T�\�څI?v$(�5�Ǭ)�K��k&�$��]�抯b��kQrC�j�|��> aP��yz�, ʏ���ǃ�Y�"��r��	煬:*�Y1�с�3R�	Y�m��g*����ܔ��j0�N���]S!����}�#�31�SLA��;�L��Tі�bz���Z6�lL�j߯�������Y�̯���u���aĦj��2Ƀ�L���	B�����F�:�w�H+9iq^;���k�������0J����;$�d_������*�����e6[U��+|"��'��R��t
l����"��e;}� x����q2�)I����j;R��ԃƶ��=�^��ZB�K>Ur�\2�
�Z7�n���T�=#nq�$�4yN���r�^��9����Wo�>L2vSMU��<��,YU(u�J��ӃQ�f5KD�Jo��?�9
�6���D���g�+��s��F�ǫI�zu��n�Z$@'��o��o{c�D�MЕ�������)�F���v El6��dt�aW�Q��pJ]��
�˼ax8�6/�����h����s�z��&/�n����j�-�U�m����J�$���!�|rl�6.f�J�n1������,��x�1=�����W���%�q�u=Gܚ�)�N?�][��3�8�t]���<5�X+5K���-����-u�yw�w𲆡�*dGsNl}�xݥLB�vmgn��b�h,�e��>n=�`.n٠A_2q�;��۱�]˱˪�־^���S����E�p%�B�0�]��˾�;�"�O��Y�A�]����{H�e(|h�yj5�=-�pc}� ��g�]��}`�m��	�)��Ȫ�$�WZ5; FD
��������Ypd�4U�ZɄ��!S��})
�|5���mx��Ӱ�0����Ԧc��3�ε�R�����6�e����k���Ž�Sl�R�җ�uK�S�=�=#�>�B����l��e8�qcl�wQ��  J��#�3O�F�ZUӶn��awd�Ԟ�3+/I�!�u'���d�L�0[\��s�ɽ+?}o��y.5�yr��,�||i��ލ���i&���5`+�J�l�0���"�?�E�-���f���a�!g6{?t�ncev�0@��AK���]c-a8��5��x6״�5'2'�[�Z��׶?�X�iK��
J��|��O�7>L'egj�6�H"����V�m�����ol��z�^IM�g�H��w���oK�aϓA�QV��T����A�>H���]-%����ӈo��&�m9
!��a�&��tS�$�ٌ�Y;N
*ņ�inGw$M��Y�Jk�4�hfdx���P�htb���~�x��+�5��}�p@A>ZV�r��;���������1�ݷ�%~b�w�;^`5°�[�Q�˿y%�-#q���J5�F��Lέ�C~!�U��>p��#��q���n�bF��gu�*�Бc�gCj����2�;���̥����v]�[����~��]p��Cg³kx�P
���p�d�9}t��V�p���Qp�>�)��}vB��.kiC.�[,�XTY^�DL�����Xl(M�J:�*SU�Y$��S�QS���(���hZK�����&�/A�~瞘�7�{�|@�O�6��
�J��[ݱ�?�����#L�K#d�Ֆ��=Ed
*��B4J��nF�.�i�H&;?fa��L۞[���s����:��g0�+=w�L�F\j7��4�s�w�Xy�c��	�8o^CZSŸM������f�c!)�����j`�x.8�����#*��=�a����6��y%?��˔�Y$�����̚��&ڝ
endstreamendobj63 0 obj<</Contents 64 0 R/CropBox[0 0 595 842]/MediaBox[0 0 595 842]/Parent 92 0 R/Resources<</Font<</F1 101 0 R/F2 103 0 R>>/ProcSet[/PDF/Text]>>/Rotate 0/Type/Page>>endobj64 0 obj<</Filter/FlateDecode/Length 4587>>stream
x��\�r�}W�A�<��2`�b� �&K�%�,+���y o"�"
�#}}f�O�t�Hq�r�E���3}=}���xt0~��y�臿׏�j���T�u�x:���z��?>�8���ՠ��׃�$�s6�G�ÛA3����v=��?W��K�g9����p3�m;�~���p=.§���-�Z|yNk�O¢S,}IO>�:<yXO���p~��v.ޮ�pk�����Ƈ����ݱl�v��g��q�i;j�m��E����K���+	����I����=?�'�J���3�"�
�95��K�N��e��A�q/|����rU�Ʌ�b���ٟ��9}����Ϗ����ǯ7��b4n`.�>W�=osFߍ�ȯ�c�w���`��2�[�֛hu2�k��χ�o�1��$+� �����p3�F�E��U�%?�5��^�
�,�I���ǋ����[���n�Bִ>�?��rB�K�:f!@`�-��9�s~�FG�������Ba��:�Mp
���ܥ���M���Z�LR�3�5����yP�§��irf�I���/��o;���a�\��/��F=�Ѿ�xf,�F~�}�3��ݒ�oչ���y;���n�V�d�Y���v����K��Ѭ�k�q���`�J1�%q.*@���r�f�l�~�~�/��	X')D��5�OÙz��>����Y!�4���ӞȮ�o�m�l���3�ΫAO�$>���U��|�1qc��?���gm��	�t���O�H�+���,�5����%l��u�F�rҵ�k{�K�j�O?�� �iJ�Z��"��mB0�H$މ?=��4��Mw<���&��M��%�LC�~Ϊxx<��\��HP��ə�1�9����)q�d-��\�,�Jo� sV��B����VU���K�ׂ��Y�\o3�a�%����F\�������J2��Ֆ��CX>)f�~��T1<��Ot�n�朻�$6��_�8�d��E�UOqE��z�G����N҅��az��M��ل��G*�+4I�a���`1Y����0n��bʔ���#NW���m<ɲz	`ZȂ������f�"�v�����-�oț�]������&Ύ�ohZZl�ƴ�^�E��
���k��@L˵�5;�&�0��3��������M�!�M��.մpj�Ax�J�B�p��ĭ=�;�$���q�5%\���*�I=lV�$w���-ߥ����,�,�������!Rt�����_��e���-�ƺ���;"�%�Iv �
�vP��%*�%(DT�� >��Gc���� �[����Xb+J7�����QĿ	@�����;�֯ (bQ�L)���\l�q:����K��͘Ao�>	��3U<3V���}�-9��l�W�0��6(]/Q���k½ףzŌ�,�3J�B���l&9��^�K�W�Υ�5n�?�ŶŬ�ݺ*�����q�֑�Χq�mh�"��s!::N���LD�\���7��x�z����r.BI�`?[iCH�+�|j�����"X����(p�#�9�H�SR�����K�E�W�Џ��e��C� ZR����y[+O�y��U.����g�2�}�E�������9x�ɺaV�1�*ʗZf�5���/�YM��z���Uj�Ӕ	���!�@�p�,��-��, �Y*�3]>�"�?�a�N�L�x։���j.�5k'����]��S��R�W�+ن��̪ފ� L�����GƲ�6�^���Q�����Ϧ&��k��6R�K�}�U�K+�̢��y�+ԗO�I�����բ��7�DiO#��?��3꓍�+e�2��3��Q��.B2�n�Z�g��'C*��N���ξذ�(W�X�I&N����Aڷ.E��|o�P�2�+N&(WK+|��lR�}�u�����֮��)�Q�����?���g`���)��*ڲ*�Ȧ����uP����f�pSm�0`ѳ�jyg�Ve����X����$`fZ�1��XٔėJ|�
~�~�"}iz-w�%�t�fH�|��a��ɣ���6���dN�\UMx`ߕJ@ǯ�����b�p��1��'�yZY���/��U��q~P��m[���H3���x��n�8�H��{7��1�֐�č='����H+M '�$�b���Y���I��͎T�X%\�\�(�`7�i��\�#��o��Q� a�L���MLS$�Ű'E[c�8 }��k�V8T�>���\@2:4*�� ���Q���H%RlW�k�l%|���vL�cĉOf������
��	�!H�*ޥ+=Z]Y�8}��meuƺIEڥd~VV]��)��Y��9r�/�"�=��aY8�n��If��D{Ͷ�+˶M>�����]w��~6�䴽v�-gȟ/��=�Ğ���4�D�k���-����B�̴�����p"е!�$D&@�Q ��;���Τ��jn=��P\'^0�=ɗc�I�)����ǹ�c'���i� ��h�JK��Z�B8Z���]	.���9�����3�IqH"�-�;����:�`�;����q:�d{��0ƉE�a�oUb�R��>��fa��M5Me�3����h���9��N�Dx=Gѽ#�������4�Y�j7���(�`��x%OxG�m�ݭ� ���z���G�m?�M���x��<?�)��Z��m$g\�Y;�`}�
r�*߸x�h%�O��fB��� �<;K�\�����<~JŅ⹷�X	M�`����S�Q��o5�ܙ�!Qp�&�:� N��'�ͥH F�ȫ_Z~�K�8?�����.��1R �%M�H^ue�fv,����'0�� I���H1.?�H��N.���B����� ��ܲ2��;K]�G9.���-1���@íP`d�����DuX�Ӓ��S���9W���c �Z |�����N����� &��f��T)ӓ��P7�z�3� Qu:��h^v�☽�m��n�������M}����;��Fˆ��	c�L��D��Q]��Z�j;�L`�0���W󐆒fd/l)���fˢ^�2JW�;���IEw#gq���~I�_���C$�����b�fR��VmA#�I�I�$ʇ~���5A
g+��wN�2�� �.��V��}y���/H	��l�^�i��܄G�P)�6�R�%�H�A#�J�+F[@'�2b�� Z௦l2�+�y|�?�]^�Z8�N�US���4m�BL���dVx�J+J��O�}�c��GlZ��ʂmt/]$l����9���(F|%u��	5@���� A�Mf"��Wj�'�7�D�9��"�6K�#R�k^ ���@�ʩ�t$�lzof���2�����,����Z�63Ņ4U�ҵ\���V�aO�#{�hX��~6њB�5�Ǿ�O����S�)W���R>�ʶ�5�z�H�UBDRgC�sq��7��1"/m�Q�n�h�v-���,;L�ߎ��	�x+O���;Oi"V��BrQ��MZpbH�̋����'r��M��M饔4��;�����@�ʖ��2E���G%@Z�센���AC&�'��d'�MZݻ|�o�CJ����$�q��d2*b^����OL�s,H0�h��0�@эk���z6�@���4��O�m�	tm�ccݮ-���pªvQ(��1ޛs�>Le�0��β��|m��-�372�H�4-=!�mI�M�_l&��fe�mH`K�7;�͉�Ә��=`��V�Hb��v����"�Ȝ�S��Q-Q	d ����3�:qY���`n֟�P�9��B��d�'خ�Z$����ڈG�)�1��� >ŚG~U���&sn�$U��p�����Z�GE��8:�
�rC�Q?��_j�NdF�Y#�������w��߉j�&��F����c�X�>�*ϴ�Y��E�8�WWX����K��rw4b���4��!fW���kV��YD��5YR�x�:l(�����MÜ��Gיb���?���ja�|C�3�����W�a��YL�r�����t����6E�J�F���EM�]�K�=>iI9���$h}%֕�o�M���V	)��=�y']e}�,����rZ�1�uf�W�5!����MOۙ���pw*������1��M���@u�{��h"g�U�"���.��P�� /,� u���n�x	�_؂�h�(�SA�v}t��$��;���1��H�{��1������lF�?)�z��f[��s�!iV'���b��O�ac�$���h��7��|�-`u�#D��1�F�Z u.ƚ mP�,�m��d�/v�Lƌ x|��UW�4(P!��ⲵ�eO�vg�SL��߈�k����_� �M���2��������w�������R>�.�a�ǰ��j��S�-Ú�湳��s����q����3.~V0=��>� �R���L�C�u0�l�eA@�N����&��i�'��tZ��\n,�W�>،*�f��\?�B5�?pņ����Q�R5�ō�C:�Ev��5 ��ȯ
endstreamendobj65 0 obj<</Contents 66 0 R/CropBox[0 0 595 842]/MediaBox[0 0 595 842]/Parent 92 0 R/Resources<</Font<</F1 101 0 R/F2 103 0 R/F8 89 0 R>>/ProcSet[/PDF/Text/ImageC/ImageI]/XObject<</Im1 68 0 R>>>>/Rotate 0/Type/Page>>endobj66 0 obj<</Filter/FlateDecode/Length 965>>stream
x��VQo7~7p�A��)�N���֭햮M���l{h'��8m�%���H�|�뭉a��(��G�H�s5��~���dQMbg��B�L�T����L������U�_���b����4��TcM��?L��N9g�Sӳ��qN����lz�����C��d��nM�_��X��5��$}P7�g+FGu�3�~SwX��������Wu�L�R;���20�-q��o؋��KX�«�����}r����V�a1#��q�,��ú�+.���8�q��(�>M8I���	�0��c��+'�!�����!�E9������3��3�t�z_�1gbV_�k�`�V���M_Vϧ�����EKD��u��||H�#����!���-�(�9en>�ۍ�����?�{启���x��S�xK
��c���\��ˑKW����U�s�:��&{��N=��F|��f��l�$��"�<~��r.���<�;���Pn�~͝$�XK��}b�g!�;�NA�D���/�7��c�����m��x÷�]h�V� fov$����!�v$��+���vc��+�hx��`��lAA�H@/�Y�VF������[4��j١g�I�sj2=�3��_�{��Y���z�6p��X�L�f[��(�6$Z��|/cf���6(v����e�ش�(�N��^��?��+ٿ�ZKon�ebsI�G�7c<��Q��1?y���~U��S	��2L�0�qY��H�?ٸxy�6G���ڲ��./�i��c�e��S�]��������k���zЃ}���ǐ-Hr_�^ ��X��gH������Q/���������P��,q
'"	 +��jr<B��
Sk 
���`##ɨxR�٫��jEm����'�$Mpd�L#H�A�-K4��(-qn^��6�)H�/�?�P8��эHm2���Tw~P,����>
endstreamendobj67 0 obj<</Filter/FlateDecode/Length 25>>stream
x�c�����w�:f���)� QX�
endstreamendobj68 0 obj<</BitsPerComponent 4/ColorSpace[/Indexed/DeviceGray 15 67 0 R]/Filter/FlateDecode/Height 301/Length 14978/Name/Im1/Subtype/Image/Type/XObject/Width 322>>stream
x�}}[�d�u�o>l��_��CҔ���C
��OՈ�� ک^�1 =Rs%Z¥ɕe�.���x�,�%)g适I��5 z�tpl��
�@���a �D���I�8�λn�rɝ�N����;M�U���9�+�n�4��;�)˜���ꄦ�����Y�[�<�:���������F����6h��������I������o����I�b��Q��v�wK�Tؒ�Ś��j��D����&�g�V���	�'EW�'Mf��Qcb��Z�4��X��Tp:�H.��Y�?r�[��8a�0�Z�O�g�OqJ����)�Ѵ�;ܰ�2? ྶ��2�Z���a(
��j�V�G�4��������Ey�%q�A9�?�� #��YS�����+Pi��N�Ft�=�� �N�Z��(;�����P)Lr�����`�Q��4��`��7���@m�T݂��(UK
�d��$� ��Pԓv��5��-#��Ƅ��̰5Ӯ�"��ƲI�^���4�Y$��!��A���H�Ɗ�I����r�4���V@�}r�d|,�1��8��	�e!0��\R*
��*���>�ӄ����B�jӯ���XIX�^�W�mp�W��������.7p��l�7`���"�d9�Y�kAT��*���+�f� Z�M�M�0���N�!SU^�ͨJ&� Z�`��UeM�d���1Ϊ2MdX�(d�vKR�L�P��T����Р��P���i��s��
����ak����WѮT꾇 �� h[�h$`Q5�T���:�r6�@����[_1@���Y��C�}5:P�@��R��ͅ����?��ގ״�z�>���d�;�����&�T
S�n�ox2��E���S��d�ڪ_��[�skk���G�g�^ˍ���g�(W�k��e�&��+�����ZY5�&Wh����A�p�T��6ܤ�w�jBC��2���~􄶊���:��}�5������߼d��k�3������-mc��5CO�2�
r���0���(���w�6���H�R���,]`�2�$�	z�/u����;v`L>��Rix�HHc��#�x��u���^��,EPvuM�8�8��,5)�l{T\g�'N�Hn�����ij�L�!��$��&�Z��5)����=��	�,64��ip$a�#�'Y�)�d��P5A�3,��ӊ�D!�+��Xr��L� ���T̋��d�JZ���0��U����}7�өp9��!kj��`q�'A
�1���l6�5����Z��{.)����hdY�?BN|�h�T�����924�:��7�3uT1YZ�ldnI�%o�FYf�%��:��,0~h�����Q�f� �bư��&�@�3I��Dc�&0|T4?w���tӱ(V��|��t���p�9m��	s����o9�����?0=����q?�EH�ob��Ȑ�A�x%���ۮ��������\0��J����ƴ�ǻ���������fM	s�>+�4sm��h���{i��?�HT�}RES��,�G��v^��.�6U*	�4q���l�ܔS�K�����1߽}��)~j��$�'9��0S�l�{������G��ƙN�_`T&u
@��������ȵQ�a2�fPl�;��~���P�m���k�`n�����ˇ?�����i�����ᅻ�n���̍�=��L��y�2�n�U������W�*F��`�T��*���~�q��@-a�@0.*.��a�,!���+o|�x�-��:!c}�(ʨ`����i/nBD�C-fd�H���Aq='qPk-ቔ���,�m�ѭ��{��5@���g�f
��lAI�j	)��[Ŗb-bI��?���3�5
���HE���.���W��&�6�:(����x���o2_��V�=�����i�q�����~��6�|L۟�~�x�M�sU �$�d���C�)��^<���Ndl�4z��}S�Y{��׿�c��C�_骘C��@�`���I�?y��~�XG�D#��
�|�#�㻳�~��C�
Z�
jq����?g/�E���p��ۧws0�C��a��m�}��+��7��gsM��&S5��TB��������p�<t���i���^� �֟*F,�_�/��C&Tі#�ٕ`UgH�2p�x*��D����T~��{v6�/��i.tA�{��ө���w�L�$�R��9 �DAT��f��ʔoFND|�Ͽ��g�]�" ##_y�������ٝ�����y �`�p���H�]�"?L(��=!N�E�ǟ��~�3�����:��X]�'�׃A=��&ILӪ�0Y2�'������ ����b`�ы���߁U����(e@0�&���bX��/D�1�|�ÁI0����A�x7�>-��hE���	Z�)�B��S�+_�\���ox�tEU�MQD(��^1\&�vtX9y �h㈘<�/.6�`�N[TU&� ��A*BD論N�_R�$�S��D�A1Έ�Vs��xh��jk�u�ءX���ʚc��oN
w:!gk4�q�GF��K�#u�, a{�ؚ��@��^#�=�c��*�Q����,*��x��+�d��c&��b���Y4��}WMi�B�)SoV\�e��B�w;�����o44Ō���HF6rڰg�5OBD�S���(�B�)~��`�ڞr�B�9�db��*�����z��*LDQn��,�@E�Me�C�fxD��_�H4Z
�#@��(���*�k�r~�A��h�5��(S�O9�?�pN���K�t�O�\pZS%]LE7��j��1�ZmmI�fSV[��&��mܦl���d]lI#ڀ�SB��Z��Vv+��&��jr,@�VҦB����0@��R��&�(�ԭ��<�t���T�&L�<������k8%Fҳ��u_�����䶷��l$ഗ�����Rq{�V6T S��E�sm����\8b;-A�-��)z���z�Ԙ�| !|�Q~��L5D�}��b��M����x�8cR��*�*������"I5�+�D;��f&*m��w��&���h�*�8-#���'�%�L��4�$1& wiȟ�f�- O>A��( ( n�D�m1��#2R�Zn�hod��#�*����_(�H�X`�D+�i�
p�op2q-��-P1u3� 6���:(�������m�:K��5�6f�Z������~h�jX�� �PK����.6��Xl�b.��KC��6��S�c��8+����VB�_��Ơ,�i��DJ�:D�:��=�1*^���I���3`�Չ���c6����/j���g>sf��WZ)�"n�\��|:�������|��+3ѵl3�Az�tD�C#8��yh
޺�t�w��f�@3�=��+��P���ן��mߞz	����B̽ g�$��1�J�ҽ���~�K>��R"8�1Z�ǉ�����r��d��������tXX xO�{j_�T���oMӧf�Q�z睊l�O���<�?���F�a3\�OXT q�$�|?AZ�`&��?��ۿ{�3"uռ��v���A@��H��ծ;#�I|0^��;H�oh��.��n�݅GT�63#�������|pJwݧ�$�{�@4�~#=��D�B�A*���=g:���@������@Ļ,�y�E��B�a�L�i*�Kfs�;���5�N��N4)Р_�< ,*�����j1Z���]�	���.n�������sŽ��\�t�*�A��;/��G?x15豯���\<S:R��$�0����P���/,H�Q�����r��o2���L�1�Ě+��7��A�h_i �U/�?~���{Cy�p37l�~q��>�y��4����f�Z�ʰE�@����/v�z���)�h��r+E����,�><����4!�d��m�:	ɒp"f�]<�{����8v�1��;۩0����#w>�cӖ����`v��~3A�������n\Ws�tn�F�)Ԧ?wt�ͧ�#�EQ%II�y)[ʦW_�Z���㙂���n�����5�-��,p���~l�7P4i��ߒ5#���B�`��mVX-{�z��ѵ�z�1QR�4�R�9���w���v�)�1s��$2C�!�;��!h�d� ���W>��sm�Y��%d�$��c��w_y�Gr:6�Ɛ�����M�R�"w�d���aO�yt�8u�����}>ݩ�27��������;U������C�F���d2[��B�2h�k������۲����!J�e,LWT{B��ޕI��'k䉆�	�`�)�)x�S��Q4a@�'%B
�����e��v��X�w�!�* ��ݔ6�A�̀�*.5��E2�JTa�]���f�J�_
�I�c�	�u�NBR�A�U�z��Z�o�)Ang����H���וb�6`ӯ�p� ��X˞��Z��H��Ԣ�ߖ�����'-�C���hH��ث���̱��"�����BA*:�Љ�7>Ә�-��@�ppd!���'SM���[�V=jɠ47i�lm#Ut��1xI�S�/yJձ֫�5H�y�Rѥ>bϑ&�ӀG��I9qW/�o�y���\H�Кՠ���O��A9
�����y��
���X��6B��j�c:��#�&�e�&A8QP�YP�f@jv��#��6��259�����%ΎlՍ%���Z�n~r)��z���P�g-���#���u��$�eKR&[�
ٚ�3�8�M0S�!BW݄GƷdg���=(1"ey�Z��Ok�hB�k(�A	�	'G���i���Tg�RT��@�I�2:˛9��J RL�a���� �&���ʨb67
��4u�M����꾵�X�6IK{�f�uQ�.�H.�n�*�4�G'��F�QB}���5=yx=XrT���R��v�/�O�o,_$2�Զ%�ڑoC[��`�	������`S,:��AV(���u6$�:��M����xX� ����QQuh+�S����`S%��D֌C��N�\�ir���$J��Cmwi[�i�Z�m�����Vr`/�I��$��,Y_ץ��y3g,X� ]�wL���=�VHC�N��gZ_X��B��Nj{�`�B��X�&0�4�k����Y�,Q77`'�m)��u��w���0���s]%�b�������E�V���{�x��.
�6�NM9�^,���G��{<�h�{ZB�O5[�hLlA����v��S_����}��d�����MG��?v�����So��6 $�K��+WL�q	��7����Ｖ��1�[���t�L�������C����C�����5��=���5oVs@���W���=�]�C=`0��UUIQ�I��Y�T�����©�6B��Z�|%OJ�+��];�+�M��#���5���@ųG����W�������B&��:e�������,�����?��|�CM�jA�GE�5Z,���>���з�033�DEoh���&`��_�Ŀ��]s�ŗ��a�Lr����:� *!
�(�Z/�͘���G��!D�d7��w����d��W�7y9aY%BZ<}p���������{���$e��O�T��*ߟx���
AT?"8Mz&M�B����Ơb�)T
G�y��xwCV�jrKd6�K��{�[KKm?����1j:ΊP�%&��N1P�D��8�`%2��~e�
F����D�M�/��'�N��O���*
��h��P��f;�ȃ7��PHxO��!/���oе.jC����r�K���o�����U�f�^��U2{m`T$8�0X�u�%��l-��/�HW8�3a��I�{0tQа��0h�{-���슍^��f� K_T��&�y���������4����F{�����<tcd�;a�j1���_8Z��������O�����3� L��۩������G�r��\*r��hQ���EX[@ï�	�p��}�Gx��k�ƤS�r���b�����K���,1�Ś�U����B։J '�$>Q	������/�tt�>� Z�:h�v�x�'M��y���>��3�n[���JZ�M	����	R��"}�u󍿻��ܛ��sk���'���d��iK7�r��}��Y{���|���35'�/�`-�%%B�l�?5[�Z�:?c�=� �1	�l�۰*��W߭��˱����IV�"f(�L�	�'�Y��{���bš�Oi�V���m����`�/�9��Ȋf�\Xٜt�>��W�E�����#���:�Qz��x���r�Bt� A!�K�!fr� I�?; ��Ij���K%Lp/��X��d�Q�SC̝A��u|�]���)��Բ<�D�l��_�)�S��BA`ǉ�qQE1��:y�
���dx���iB	!�H��0s��%Hθ�ThV�P4�2OV���\!�E\lk���>{�Q�ҩ@�w+?g�I\�a%���*��M��B[��IzYP)t�K�H��K�kG�
���QAr+�q�áXT����#���Аqq/�CHQ
�/i�R�:�!��[!��8��)�'��Q����;�ގ��L�l�����(M`��Y��힊Hܾ��S��@&t�V_�tV�t���yΌP����6����E�;9Pk����zk=��ZM֒3YSf���]Hk�$Y�𲌆�RY�y�c%�~���9ա�U|U����B���n:>EK�K�Z���~ �|�G�S�uS�;<j����iyTI���;�� �;�eh��R(�5��j���TH�;"�(h�'��9'󱫢�Q�H���C�]��ż��[���
�/H�}ڵ���T�\�C@���8��0��G�����eyV�O��t��YI�(A�p��ǫF�����U�m
�j;?����$MF	"<��Y���ܒj̤�ꂃp����X�K�-�l�GX8\�hQ��A��g#��xV]4��d=\U��A�19��߂��v
��j��Bj kNx0�����.�@�<�5�i&n&�5d��0��,S!M��11��W3	OܖORQ
u��"<��W&	��ɘ��	��5����{��A�F#���AХ3���ܥ �`�Ҡ=h�P=#� u��R!	�0�+�LX�ĭ��RB �aٗc�:�����׭z����t3�S�hE��J����3�'��3S��"���P|S%�ވ���d.�������u�����L�����s$�������co>\�/�l����!]����5I������Wg�kߞ�ݠ���XG��������W�Z��0��Q����$>;!���]����4��?q���s_�d�Dg���k�+��G�1��s��9����U��+~I��Az���|�������u+���Y�
ɺ�6��?g�3 �x �IB?ƥ"U'�9$�.Njun@��_��K��trE���4��R���@%<�?��K-���;r��]�U!,�3�����m�(�A�y�sj(9#4�sic�Bͮl[C޽�2b��P�v���������t��s��?a�*�K.-F�A? ��6g� @eA��A7G 
��҅�@�5������ɋ�>��mH�r�ퟆ�a��%��,�ZaQwl
�ȯ>�9��.>���|d��N2�N0��lG�p[�gk}��k��m���!� C1U].A�����y�������+�%6�cr&zv;�D�I`qi>
����{�&�Q�Xv��5h����xq9Z�U�b����/�K�h���DuV���Iܭ$g��a��E�p���|���Y��|G�1ɬ|���Ea:{40�NܶVF_�`p��	te��j6�:Mw+�<ý�?|x������D��K_�� f�/��!��olLC��Qv�뒢��cl�oq[g/������l��L�ѡ�%�v�"��Nrx�ouN�ڨ���ܠD���ծ��z���1�����(�*6` ��ѥ^)i���~p�9�듊��߇!��GWW��`����g��v��+Wϙ0�d�e�<G>��W�:w�����UR���y3���L&C ���,�+����zv����ƨ�_19�b{9)h��������§�n���%a�^u�2�'%����/�l�w?z�jqj59\A�p�c�����MN�w�(��E ��`������dP�0)_q�������H��h~m Ч_-A�紵�R�y��1ua�\2�EU��њ��[���� �S�(�Ľ@Ĥ�h��� �&�}4��㷭A�E��b�\΂@�%����8Y�{,���k^�
󖡑t���hY ��I��ˠ��O:�8�w�j�1m����yY6E9EU����%��hB,gi��@�0�� ��n��9U4�9f�&R��δ��.V�ū�|}p)�cv�4�bC\�h���7r0�U��[�	�M-��1r�r�J�]v�^�8qr,��J��f�]O�`�1�g*-DIK��$},�z���g��[[��Svt��Q`5,���,��������y�#y�,z��	�/&Yд�֔��� H>H��9�NY ��M$5��Yn�޹Q�P�����Ut��B�+��Q�%����z�p����!��pk}j}jE[DV�ad��M��<���_d2<���,����?uj��G�k����Z�T�^Y����>�U�����V��^����a`۞�0⣝Jzi3(����"�������,������$q��$�xq^�u),� 콇�VR/& y�S]򗅢����2�V�ްa	��9�*��R����&�]o3A�G{��D�֯}�m�R@d�'ٺ���{f�:=��(ַ>o����O
=��XH��&Y���<�bG ��͋wj���A-ƍ��)��g2��������d�cӄ9X�ظ��If�D�1Ly0a-���C��n胑 p�^ ��n��&�VcM(��t.�{���ي?Z��83 �G�
�0h� ]�l�����RN��7�py,���Vg\�>�k65|��$bl�Q^D0���(0W�y�e�5��@.7D�|}(��4I����<JJѭ`�SLW7��[pN]��Nͻ�̯�"��/�,QD��� *H��_��Fr̲�
791I��q����h�X��>�C/�� YhHicTp'�K<e�@�&��(���
i������.�F9h�×�H�f�f��+b�u�d����]t��iJ����w���>��[綟>����zl��"fӀ{��$&����K̝�������s�����Y��b���^sc��ս��g�t�{#/7T ydg}�6�d\x;��Qo�kW^H���evؙ��,��W��_gk"璿x�	��J
�@��{�N���"���
>��8������.J������px)F�t�z��pcf/@�]�Pv���I�����>Q(�PJq�P,��4��"����LS4p������D��=�ěİ��� �'�ޗ����a�wy	�D6yK�`�p��'Y<���Qە�Y"0�X+�8ʏ�+�hP@n��n���SmHCc3�m>��@T�ax��N��}�^p�f�&��\�g����xG��<̭\�.�)T��$�`�')�1�*ic�'��1�ɔ�а�1��5�O���2/K)UB�
#]��y<E�x��4$�����C@J���r��ۑ���f��/�}.C�o�]�^(���p��v�7��)~ �<Mf5\lfc͓虲%��6ء�tZ��l_��n�#�p���rRJQM���E��.�����*��:�mQ1	w;��O���ݿk��U�iu+��������f`��t�3�)�2M��ZvDq)¶���_׋�]�����l�`��pRE4�tF�A�޶������e�T"l���6�/�����ʍ����qD�['M�E9D����E�;.6n�Q�ʔj�=�~̕��	DA�?�?\�x��YR ��׽�l�j�����f����q���D�z��t���������'�����Ͽ}:��;��+��0�@Ʈ� ��������)%QI���ɀX.$��%׆������7�f�6��RyS�b�yN��q��㨐k��{��DW圕�h����ɻ^�Z��S~Pb��$֌�Cx�\@"	�;�-I���4	��&��T`Y-�����2�f˴�}bMQ�m�!1�B�����/=����7�͊P��3z+s�r�8/�-%* X�՘�.�-�
G��5�4�1�T�"s�'�'���^�`��D55*5,��l���K|�d�}(�%Wr��CEHL2�����P�8g9b$�M�j�)�L������17ƃ��15�<z֒�	4���w�J4CQ��X�����?hk6��J�J�9e�]@�)!�̩J=]�}����'�(\F��^,�Ӗ�F!��Vi��S�m�ǝ��p$9�?���ڔ-�t�׍/���p�"��� )�]I\� �z+��X|���@m����2?�*i�8��Za�I��ܢ�/ۀE^X��Z8�B�>~lWX7��R�n�{g!� =]�Ihy�&�:֞����������T^e������3��>��T���+����mB9������~�5�!��'[5w���ɊN�����͝���$u#ʄ_!��L̩U�7�	���^�ϟZ���Y��W|�ךr8���'w�8��N��Lv&�F}��ҝ_{"�r�[��I��Ȟ�x��$�DA���m�����]�^�c�;�lUC�`x�Ȣ��Vb� �����D29ϵ:�7qWbR#8i�D�w9}�n�:��.����tmϸ��NX
�[�r�$��,Xܳ2�71�T+-�|���|ݧ�AB��4�N��
ߚ�#TÖ�9����`����2��棸�#�A�b �����b,X�mt��S��*`�12��Hm�Oٰ�@g��dyL�!5� �h%zڠ~�_l�Hn=4Tyۛ����5
ˉ��:,�[,sO�uS';U6��'��6�"<�5�Dv7m��@��N�A���x�٭��J���E���~^�W��Pm�[�GH��F���q�E¼�1Y��v_���W�0"P%S��E���uQd]n]w�،co"�_�}^��ń�|X�46��?Y��7loS�~������!���Tm^2�c��ˁoRaI���䖽n��Iy��.P�u-!��g�}��I�t�-_?��Bo�a�?��[*��ɟ������g�]�����G��I�A ���3��|w6�&)�Zj���H�W�j|\	�8��ʕw����0�V�0�|� �}óG�N�����vT����i0�'bS�ӂ7���N��tZ�䖕�(���&�\���4LbW�Z�d�l��g�G�Y��$�E*�W�������M\\�+=h�Q�]}p�	�6�t��^�u��t!�^@PB
���G��,��0�y�E��5�����p�	������s�G8���%f\|����Gp�*̯���Ф�������������jSL.��j�
�Ⱥ��l$y�s���/~n[D�ͫoh��0��xRM�@��"1E���r�8U˜��&�w��g����&7`	���l�2�xR�?��}�QQt�H�Կ'��ޛz�>�~20[cl͸�0�����,�����l��pO:��Zr�W�\��]7�|]�9y����n�g�����Łz`�P��Y��ƂlR�
Ʃ=A	@!0����o��p>O�3 [3�.Y�RhW���3x�(W�'i=����I{2�����^������U���<�S���&t�������{���VW�A��4�S���h�O~���/_��]���ϱE��ͮ�=����΋����B��-&#�1o�S�I�"�ݷ����^3��?ّBI��rʗw���,��N7 &���5�U]�²�Г�yΆ�>�������%*M�9��(�ɗ^=�S��ok�6�'L���9Q²<�0�R�Pw��o���o��s2r��yY<�O��n�����x��l9�xC1T�hѶ�f�`@v�U(�%�o���_��}�3�O}r�h���g��-�I���,C�'sn�U������(�����R��^�v7}(�#Q8�Gf�p�UuRR +���)�A�g�"(Y��y�IG;��DZ���	i��3aA!��]�vI횐⍻�-�Ll��� �I�&W�=e��|t-���3y߶�G2�8��Z��i�D����|�-��e�x%n�vk�(�b��#��M`ճ��6�\�3�p��v�ɡ��Ǟ�n�E��T��p u��E@R`��ed1��D�(j#���WB�F���S�bA��)�c|(r��F��4�FQ��t��I--�|%��(G�i��c1��t�c�.L(�4���ڡ�Xu�(*�����-�i���c���l)����voaD�������A��Aז2L'�qڊ�q�v"A�)�T5��<nV��mk���Kqʍ0��v�ߎ��0�^�Azg3�Nz?��Z��N;܉���	r��N��5��/��Wq���_Ust���&?֓5�Nj���7(Y�E�����������=5�R�u�ʚ^F�җ�ז��jKw��ʚ_r2���.߸�Z��Y����D�O(5S���<�DިB�Ka�x�ʓ:�-�r:{���
���ŨCY����Q4EU��5I�|�X�(���ή}E١��7������eS�3%�"�n���y�ꤑM�m��*K��"�E{U�i7�P�&�Z죘��B�q�0�*p�(z\x?��g1j��%����� �rE�E�»Jt���5W֮����â��=����TI)�Sr����+�Y��e?���Օ�jqz�=Q$G!���Ч�U͉^ފ	�D�e�2��M�@-"0m����G�Ⱥ�i{ r�򤭱36������#}���_D�ˍaS�
$�@��{;A��UxÅ�5�Y|D�,��꟦�х95�M;5�-h�B]'m��,�0�,�z�a���)�����ݨ�~ԫK�'~(�+5�-�xE�~����v��^��goe+e�o8���K�P���
֊/}.
1�t����K�FȉeE�u��&Y�v|�����
C	������ﰇ��Z��bd�QliY0x�SO<�p���Es
K���9�=o�og&��A[t6|���ׯ�������5էL�8#r�2
}�<�s��׿5ś_]1�jM�!ԑ/�n5�!���J�w����3�ՃKZĺ4 �M���=,�ǻ���q^�<A?�;Aa� k0����7N|o��w�;��/��M`�W�p�'����������C�3j��y������ݖ��O&]�G{
�����o���ދ�958�t6撒�=�8��L{, �m����lL�p���.�R��/�v�O�zF��y���%l�ܛg�	���H�0,�4�i�خ�a�?$H�W���7���7d�@����.Wj��I��}A �_�Uu�L�:ȓ��ͺ�䍿�I�]h��f-�h:�齏��g?��'s[�L]�b�.��@�2	�fwӻ���$��E5�zZ9M9�K��r�Q��3i�K%|��yЦQ�i}G
�����|�h�\��am!��#N�[}K�P`��xC��)Ȳ�5������/�����v�0�}��"0����!LZi��6�� b~P���s�G?J��_vļK�g�2�f�g������9$��CJ�Ⱥ� Ds)&����<����c��c9��afU�f0�]��x��۟��jRS�}�5�Y�L�ٿ���x�U/�(�	����7_�]�lpq"���_|��i#�C���p!ݻ�͋�/�DOe6QW��ƚ�K�����L?���2,?�ګ��I�~��b|��^�I؅{��-�[/�yY62&�4Zy[��Yk
&h�:�5Y?���y��W���O���D�����Nᱽ��tJ!��`�4(�4m��D�X��k���gN,�G/��~�!`qv嘃�>Ahn���~��L:�̴���l�S��hR��.�����+G�v�r�s>5i�u�5�f�(��]����d�%K�e��a:x�3�e��U�:�,Jĺ�>4�O��Zgm�6U=�4�	�|��/��^��*�ػY?�W�`V�b�s���:���Ⳅx�j!��iQ�<��ZzIwE�r�����"�L�L��i��09|���tXR��Nm��˧�Q"��!�-D�����Rߐ
�#}�CJ#��Y�Y3����Mp&AA�{X��Bb��~Zm�Ոz������fsv�%����j��U!�]�b�x\��Ox&�ER%����l���B$,�  ļ�X:�L5�]��'L�k=��ͧMSI^(UAX�b���&�T�����X;�������(�����T���Bo�b�����%��R��S�<1���>�m�g�d�`Gq�j����
�S���2`*̵M|��A!GG�A-�^=&
9$�Z���;О�VRR���w��m)m\,�#�'��>Вr��@�P���Ğ�1g\�w����]�7��gìs���֕��D=���8�\m�u��ؓ!o���]'xg�g5���yS����'�]������^��_��񯉼M��c����E�ޭ©��	�<}���}����li��w�uJ��:u����s�x��֤6O�Ia5�[h4�"G�m�w��u3��?�@$UJ�UY���K��\~h�� ��0� 
endstreamendobj69 0 obj<</Filter/FlateDecode/Length 9953/Length1 16526>>stream
x��{\��𝲅&�2���g���T��,��a506�Jb�Q{�.*�{�&&F^�Fͳ��k�����]�y���������p�{�=��{3"B&�Q():u`ܴ��� s!��!�~��6A�1pä%j���EЮB�G�(s�^�x���\h�����D�"����ʓI�/�iE��%�{�b*U����=�@=!6+���o�R���7�����	� ����S()�	XZ#�c���#�B�F�8Ba�9��H�T��} ��O�F�P[!|8����|����4!dm����H��|�W���!V�7��4QDtm*��zw�8�u+wDa����
)P>�!5�p�d]:F[����5�w�=������V��j\m��U��:
ŧ�"	�4�;h��꜔ب��
��W�&��<P�b6V�J�-��bk֒k-���TjYQ!%Q��V��XEeI
K���2�)�XCK~Z��T-;����J�`�dEjy�\*Q��bg։#S�6zr�� �H
���\&*���Մ���,>Ft5s� ��^!�BF����*��Z��w&+���s����R_����F����6Y����T��V��B�UI�"����6�޶��Yf�ᇳ6�(~�#���C�s%�We;^���t����Y�S>�4���+L~{oҵSa�o5ٓ�,����S|6��|��cȚ���w[�r�ҵ�'ɟ?�r�K��)�M��;����c'��RK*,��1_�U&�}�NX`��b��<���������l�9���W��>c�i3밚��2,<�zl�O��\'_
��m�	�jħ�r��tt{\�R���ݪo�y�`��7e�']9�:l���[f�ly���鍄k_��n�
�>��^���_Uq�����!by<A�=Yw�{[�%�����>~~
�J�[~W��}��;N����,*�@l$�s����l���Z�鬞]Z�߉�O+C%*�z�Hur��Y�6-(!ۅC�r�hX|���4D�j;�k[|S��i��h�>b�^�
����n��hG�%^Vn&���T�^z�s��|��t��ø~(Xs���ĥ߻fY���2D).<;x��;w��o�.L��݆���D>���������kZx���=��ؔ���x����|h��Cp����ֲ��m�:6�����?x̴���YZ2��u�Y\�lp��8�o
�c}tB��J(G���ܖ�s�O��&~l�S<z������o��̓�܆�~,�!oI�͌:o�������a.��]��/`���?�
��u�o�3�ۨ�9���bZK����U0˷�U'|y�}��5hpï�w>�<���~)��7��m�ӫ����q����\��z&�U�mAm��Ʌk<��2�y�e~�����	MX��f+ƿ�%}�!bɶ�w��l����?�ky�"�i�B�}o��Z0��-�/�>\��+�{Ac����S6y����ҭn�ͭ����~@�Qf3����i��Ŀ�$�ۗ$�"6@��YO�g�{m����*��T���^~��b���
|wr�<c��bb
���Vv��=ڲ�n��Oё�g��������,���s����]�cY?9�@�٩�˻N]�c~�e��Ӎ�#�3˒G�f�^��!ɡ����|ח{O�,�o�n�+���%Y3�����(ڴj�E�/�y|��[�0 ��&���vQ�T�b�Mգ��~|e>�}����DR3i���#�]�'|۫d�g�Q�v�lm(:s��}FX��fN�~|M�Å�(���d��o'�*�@~�!ql/�����Ya^?���)����Q���|��5l֗l%m)�w]
0E��찰��߆��޻��c4d e��6�t�R(ˊ�yj��ԃ��1	ri�B��Q3Q�"�����lݙ�(�ݫ]Xg�4پ��(j&�X��(��˸�Ċ�,�O���?@�o�4��[9����V�D��5�&�a��m��6����W�j]^ǄON�[V7/������l.9�v�ɯ_Lw�W�q��c�'f���®���Yx��O�ҥy�K���>hܐ�~(��ax�B�=C��8����M�.�*�\9ye�O��_���]��(v���p�S/�[}K�2�y����/�=��<�p��И�������y▖uԉ[m�,4�邆Wgʃ��[iG�^�c(\�]Ű�w����(�/�8��|A���G���F����H�ʕ�Οvj;Sj9��>o�g+ְuܺ$芥lŢr����E+D�S��%��~������U�E�㬰��QӜ��l{�o$��Pj�tT��
���y�Μw��-�'����n��;����?΄����;M�ڽ�ߩ3��&�$�ӷ�L9no��[yӛsQ��G0C�fMں��W����J�*7S骗i��\N5[?M�\�/h������|�������(���tZ�a�p��\���c���N x"%m��������pޔ�E�6yߘxc}���Ztn\�C����i���8�q�z���#}c}}rD@pa��I�.úO�_H�{�q�Z�%�>3�׬����x8ت0g�dHr�d~�(�2�Ǟ�M��+%���y!P(��!���nK	k;?2X����p�*� ��B`�!H�e(
��43�3���L�3E������l~���F���c&1�2�g�#g�9��jÓL<�}w�%_�h�zK<�Ů�m��|�����S�ּ��С��O�	~7�Y�������͎�oz0�㹩{�~�&f���2EL�~n��Z�|����o�_�
D�D�=�%�yU7ׯ��uKޙ����s����}�/x��%zl��M��d3����Eߚ��ɯq�g+�.s1mMG��5���{en�^z�������uy7���u�5賣�S�V�x��eM�����N}�b�a^�r����O/��4p��y����3��Sg ��U��'���}������b�7�o��!���h�Eu�B��Vn�yŏ>Y������]m^z�Z�ڵ�X��q7C���a��"Y#������i�����.q�^���ר�kd�m�8|VV��W�I_�����4<�1���c�R�7�}}����W�v�<���Η'~��f�ť������O���I�^�3�/�<�٧W�3�����އqHz��(��G.;�}$I	�)����޼��Kel�N�*�u�}K�I�����;[����M����2���_L?l��c�������ir��l�!�ȲQ�{I7�*��UM���=.�`מβp�:|������	3z<��g3��Q'�i����~:����k�����x����� He�4f(�]��B�?���ߒ2�澐�����n)���ًk����z:�'��|$:[�0x˨�'��^9�i����γ����;�Q}��M�g�k�X�:����5�q����s��g�%[>Xv�hSƨ~�����fUH�Vf=+���\�O'j�[�l^"٣���\h3k��s�97/[q�����,�����ҝV�btj����%������[�٘�	�w�Ώ9fl�*�Ϛ�ǘ|bg]���ҵJ�;	��-����w�#9(x��؁֟'-�<�[��+�W�~�4m�����Ɯ�v��o�h�$��F;,�o?���U�2%����=U��G��a��6<�q���ԳC8$���b�7���I���aEt�J�bE�{��J���F����B�n���g#j�kæ���+h������OY��.��U~���b�o�t؉F��l���(9=@?nii��ƕ�q@�����?>\�l�b��i������_�?��ߦ��L.���m�-��.���k�&�X0�jԓ��S��>���<����T�?��:M*�v��/�jͬ�]7V�YY����:4C�~���☀��n�,�����'�����~9�����?�p�G�i!�~1J>�Ҹj���?'�vsi�9|��#��Zs�e�{����y�oʖ_ݶr�>ف��O��Ki���$퀽��F��q�K���Ǽo��h䠛�e\�}f�=i����雾;{����{5Ae�J�+H�'H�`+��$�N	��k�ڊ;�U��'!P<�����ހw|s��m����֬�-#-�u�~�[���ӉO<��-=��=�-��b,�f�j��{�$GRT���{R#�T��aH4��0�|�ؕ����4��eJEn�D�W���$]I��fO��n��MY��k����gf.��x�Q��FӀ��z�;�/qѭ�6eg�5Q!OtB�eT|�W�2��"���G���+=��75xBDؖ�߯	6~�롭u�<�KZ1�"�f���疥�K�p"�B�-[�\n�����,9�0u�׳�c�L~1���M�%��^Aɱ���]�,�ɬ~���Kg�r�n�L��ΧG�j`�=p*z`x�ñ9���(��k��_��^���'�&�ɨ��,��$��tr�W2�4Uv�㆜�j��xᮂ�}���Jx,�$^��1�����;\x��G^j��U�1_�S��,S���v�=���v�v
Ol�������/fG@��z�Y��]W<�3��Z6�'ƧF�v�7꫖х	�����]��'����cZ�����>^��2rb\����ۣ�������	��\r�H�'�o]�����8ژh�f�:wcq��EI����~'��mS_9��pe�a����$���5~��S����D��u����A�с-�=�����5_z޾0�V��96�pRT߻��>��Px��*�%�욻��ڡ��z��n����T���ڄ/�'�7P]�}U��d�ٴ~��_�Z�"���|t9�MZ��]O�^�\��,Q�xH�[�@�p��T-�!-�)� 	��	�;GZB�"1�Zy���B#�p����ļ���#�9r@H����h����G��q��=L��}��� 4F���%�&�9��d)��<�5�+(e#����}���TT�Bh{m��M���
�G�(� .��(�Eqh�8���D0Pd�F����2t }��";�5Cؽ���E*�0	�FWx�y��%��C`Cs��%Vw�������Ĩ7	��,�Z	�֡�H�Jk���]�=�A�M`�!td� b!%�Re��k���Ơ3hg$X�p�lF�	8+I��GJ[͵]� 9C6����4�Ds����mEw�~Dq�x@����A^� Q�hp��6N�d#�v8�& �gh>Z�+A�Q8��7J��b1�XC�Fz����Tʔ�2�Lj2�Oꕐ�2�uQ�9m�v�N���עQ����K�dT�ͅ���	N�� �G�O�:���t"�6�p�p�� b(1��%T�"b'�H �w�gd ٛ!��)d.�$�d5�!�Ƀ��)hه��T�G�&�0u����H#z-��t1�9��/Џ�gt+�Dp��$�ږ���֑Zwm�6K;G[��q7��� {�`V�pw����8��w����h����NԈ�A���=�Ρ�`�O��%z���&\B��K��9橄�L�s����z��&�2X�
#3�1d	9��C."����&�fBK�a&l�8*�N���Pjj!��ZB��j�F��:N�t:�.����t��>A��/�X^(�
N����w�o�w��S����Lx[؊���5��򉙄Q��$nS4UN�!�I#�����!z���7��O@C'�;2�NI��J"���S�T5���T��R�E�����mɫ�Z�W�&����[6h3�.(�XM�����!�5�!�n� 
��(\��B�>BSh������BS�.�P����3��r-�[�e�Ю��
}>D���Vs���Af��jbp�Ԗ�e��'B-�-�dD�P�F� z�������%4����'��J!�CoHXO��G�i������		��ݡ�|}��<=z�pw�.rua��99:���v�����073�bbldh �y�+�#��d4��]4`��I !���0����G�d�nL��3睞���=	3&��x31"Fs6Z�4#���-�`4�1��a�7L���L�m^4�!2�MlI^ULf4�Wod%���x�zC# � �t)뉮�Ȯ1}�I$4�4�����(�SAC��H�5I��1�..>�"J*�� Q���?J#�b9g���{7U}�h��2���EْQ�J���0��њ�oؾm��Q�3:R��[9�5��f0����Tc /��Y�?/Ƨ2 �����!��H����Jg�L�a2�1QQ^ոL��*J)s�fo�[{��0Ui�"M?Q�$ڱ�
U��m��`�:S|����u���b��M:�v�pw�Oi�,�i$�a�h�.����,UI��pi�aF����*�>������DL�s ��3F����̞#��=ր�k��4��\��`NA�p����]�HF��fT�>���d�����p<�1eACS���k3(�a����А����b=����Q��3E�;���5B��?S3˘�>��_�e:z|�(>yD:S���m|Z����N�C˨tʁ�C������;s�tc�|�ٍ!D%�L��,s��a���7���8.\�eӫ���չک�I=�*
�����UU��h���׋���������bf��o#	2*�F}w���f��X�˵���	�m��vG T��4Fප�@'l�H�H�pf�<:�"D��_�	PhD7�@
I�GK)d��I)� h)����^�f��Z��^�%����~a-a\��.�n.�.�4z�PMo"x�5b�&�S5�'ڞ� ��ȉ"`�mG3=��n��B~	-b���څ�}���bt��DmAF�e7��]�ބogl�ЅS�+�c�p_�V|��{����6��E���/j&#uu3L���0h�i�s����W��Շ�BxZmk8�i��c�F��pֺ鍯���8@�����\	e#��ہ$4Bnt6J�oDq�4���� -��>��/�ן�!Z-�Ay�J*J�(��L��L���Pfo��������Ȋ7�BmA�D��uԃ���p� ?�g�v�}��N����ݠ��A���}(xCyӐ��`����#g�61�A�h�!�CQa�4УJ,�� �@��Q����ȇ�CQP� ����l�B=��8j9�I^/�ǁ?#A�$�		��;��:O,E��n����dI���0[�ӛ{����|>�B���0�!���a��m5�r8��Ø\��2��9���>�JwN��-�<"D�9`;�_s�!�ߌ�$*�W��,D���C	��/}x;C(F@O�� ~
�rE�����d�q�4��E��`���=����m��A�x�(�ڡ��������j�)�pR��Cr���(�B;�� G�j$���K���B�q���T�?vr��	2� �!?��"�W��P�dI{���i�ʇ���VNS��.��4��L�`��X����Bd�6�a�D�Æ���a#�O��5F��6!�2=��Ro�7�4�Ӟ�qѽ1���X0>�Bga� Fj���0��y�z�D]x��0�R`��`�,��<d˫��|d�ۣ��ȝwZ���Gzؐ����a#�-l�k�r��z؄�a����Q��9[�'c؈��x&��1~1��`x5��8��5���� ��p�s���\ư��ð���\>���>&6vư+��s�M|1�a���l2�x|�al��c�c�M
�ʔ��T�l`��dL��P�T�7��2_��DKԒ�)2?�I�^����JVT"�N-+�R��r��%EmL}tX�O��H�1�}�X�g�g }���˗eu���v�i���;�dLt���{u;$'G&�C�C���*&GQ�f�p��U��3��R&EQ )d�d��T���%�*FR���+JeER�J����s��d:t�D%�2��B��Xg�Z�+S�Ɋ�R�:v�RE~�L�I��@4�ȥ�|��9�n�\Y��0�bp�J�$�i��H"U�Ѿ3p9�"F%S�9s:����e��7�L��h<�ITX|�2�s�
���b�$/dTj�-)��NQ�v���%�
��.��+Ps���W��Ɨ��M�RY>`e�%qH��؁Q�i�$2Cb���bSc�ȸ������4Cÿ�)CQ�(c��E���ە���Z-�I*Æ����5��9�K��Ҽ�P������
>˖��� �s��H��p(�K�pEa~�S���
�8��c��~�J�{67�P�"9��⁽}�P�AO9HQ�
�UV$�ي��|���PPZ�S��U�xT���j&[�}�������X�/g�k��s�J7�()P*@�P*#L��C��(oi�Hu!ʆkʦ�R��~� ���j3JCeH	�9@�B͠P�P��2���Š(<�_%�o���YR�P4�K��"a�|�S ��j�[s-����f�]e`a������l|WR�N}h���!Pz!������щ�O>"v� V��^���)g�,+��^2�G�_)H*mr�v2�s
���W�=����S��$�W�G��v2'B]�=��1�Y�dp��ܜvr̟�9t���H+��s}����r���E���zg�>��\tV
cw�k5��?Ryx\�˵AR�O���ya��:���R�1����-oӆ�R�e��(��J<Ϝ?������cH���f��i�u�rp�2���Ru���ڴi��k�Qs��)����O��[g}^Y����.�3F/��mܵEA�~$9���s�)�my�
%�
?8K��#v�K_�.~����H�f\K��v�Ʒ���Y\����:HI����X(Q�E8x`�����T�ry&�@�	��L�!.؇�9-��X?����5�l)q���U����Y�y��2�FPE��M6H��u�V�eI�Jx�\][�WU>�f��#ӕ8#�u�-N���,��+]��k9�#C=��j��6Y�ӫ�c�}/�=�}e��k�6��z�>���Whp��lQcymw n|���8��|'�SKu��t�.�)�׷Y���5_���L�'G��/���_Im4?�M�xD�;���S�[X������;<�N��\��1�OY�6ݍ��t��!�$p9��FI�k	�!��H����i��<w?Rv>��,"��p���HA��&y,���~���]I�m~�΍��Z�����U�����=,��zp������6�9�eS�a��S��A)�6ׯ�FgEE�b�b�ll@saD#q}�P++���F�`D��z��z��:WP�-�.��T�T�
���n���+���L�Eh,4
�|!-$�Hhը��Žc���4w�1l�_>��W$!$� ��������D��I��͋TQ#a�<B��'4�(>���&�+�Q�M�y�kI#��	bn`5��F��7Z5́�b��v�}���lJ����7��~�%S�z{�zu<���!g���i��.p�/఩����j[���N���隍N�:e�#"&q�2E12(���%y���,���h�}s�̂����A$��D�����I�!O�ȑ��z4)&-�~R�,z[dDd�H��%Y��s;���&n7�$��8b#����IL���s9r"'q.'q.'11"K��s��^/D�3�F�����!�E��KF3e8��P�){hD�GF^cQ�	���ɑ `8R�{��d;%��a�^O2���?�*�z�Pq���Gs4٭m"˷Y8�{ex�u+c�
endstreamendobj70 0 obj<</Filter/FlateDecode/Length 259>>stream
x��PMk� ���/[z0�v�B��m �/��z3:I�FŸ�����l��:�y���ݡ��{sF��aTZ:\��	�'�I^�TQ�b新@������� �k��Cu�n����}�k���ꎖZ�li��7m:;Z>�r{��:�N�	6�ht�����8������c���>#��N��}��H8?�"���r���	I��h�nC4��S/N�a_���<v�Y!���ۄ�*q�]qJ��"\�;%?��(Hi�Xn���t �|-
endstreamendobj71 0 obj<</Filter/FlateDecode/First 84/Length 1639/N 11/Type/ObjStm>>stream
h��XKo�6�+<�H�� k�n\8I����a�U�]cWi���CQ�dɡ�S$�����7�z�8�	�WL���P��yÄ��m�p\3�t�=��|`�+�g����`VH�^������t�i�����X���lnV �w�~�|�N���z�ZnZT�_9^����o�j�٪޴M{�z����SS]|������%������M�5�84�Vh���x�u�Ի����m�e��7�~������)M�w������2iHc�C�wh���$Ϣ�b�~�_Y��O�)?���!�/��������z�����!5�C�O���Lpn��?7�е�xy��nn��@�<�Ѳ)Bu�^�왍F���߮xln2Z��]�ͺ�hgņ���"������ܬkƫ�z�+cR����[��V՛f����墭o_C��#����'�||vrq�g�����n��w0�
�/����ٷ�����S����n]�bH��au������;��yP]�%_	�_�!�l�����m��4�~{���z�]���}_ڀL9�6�3҆4�)H��DG�s�&%��o�����P��i�S��8�K�pe]'������1�v!�N�>ԅ:�1��GcP��iM4^��:��l��c�>a�`}0�L}h#�D��_�@�8�K6��l!����*VX�q�ɛ�.� SX����E\�J��h��Wr�i���n͖��ׇ��x��S��^�/�����uBB��X(�$��)�q�h'L<�Y�ý���Yt��)�v]���'o-�<y�D�J��}``K��q�����z�wJ�> ��ۂ�����#�Ɩ���K�O��Џ�>��=�� �Z��'��TYg~���	���p��/�	��?���W�RI�z����7�&,5��G��\a��];�}��H�%�m��b��p}�ԗ*D}q�cꣅ&R���/�
���$g�n3��Q�jH|b��h�G2N\e+��$��������c�-7�I�&�_��ŮY���������ށ�hD(P�:�!rF�>'Yld�v��^z�D)��(����� �b�/�Oې���"Y����C:�X�r��t�eqA�R@ە�x�<��.��w�ACL�T�!q��H��y����-h@&�$�ǻf���S0�K~y�ی�=4��rÃkM�@��2��tK��A��4�S�����R�DYB�l3%���Y�@��9��Ӄ�����/?�;x2/��i��h!<�l�
�|&0/��)����wa�iFG�TP��S�����6H�6	�sփb���(��������.<z4^�Aޅ�����OS�.�Hpڑ{�$'ȑ� �9!��ʀ9V�oI��d��k��u:/X.��hb��EBE�ʤ����-^�Ӌ��B�ޡBt�w��q
\�`6��Y'�l?��|D#����Aaj�q ���
:w�y�Ӓ�D��R�x!2�{�ۍHx(��8��QJDF!1	cA��7�+%����U�0!�&�Gd��h(�
�#c$qO�[�� ::���3)��qi-�I��I�x&Y�����W��)�I�=d��,!�����E[ҋ�Ӌ�O���` �z�
endstreamendobj72 0 obj<</Length 3782/Subtype/XML/Type/Metadata>>stream
<?xpacket begin="﻿" id="W5M0MpCehiHzreSzNTczkc9d"?>
<x:xmpmeta xmlns:x="adobe:ns:meta/" x:xmptk="Adobe XMP Core 5.2-c001 63.139439, 2010/09/27-13:37:26        ">
   <rdf:RDF xmlns:rdf="http://www.w3.org/1999/02/22-rdf-syntax-ns#">
      <rdf:Description rdf:about=""
            xmlns:pdf="http://ns.adobe.com/pdf/1.3/">
         <pdf:Producer>ScanSoft PDF Create! 7</pdf:Producer>
      </rdf:Description>
      <rdf:Description rdf:about=""
            xmlns:xmp="http://ns.adobe.com/xap/1.0/">
         <xmp:CreatorTool>Microsoft Word - CONPLAN 8888-11 ... Pentagon Zombie Plan ... Counter Zombie Dominance 2011</xmp:CreatorTool>
         <xmp:CreateDate>2014-05-14T13:14:52-05:00</xmp:CreateDate>
         <xmp:ModifyDate>2014-05-14T13:36:57-05:00</xmp:ModifyDate>
         <xmp:MetadataDate>2014-05-14T13:36:57-05:00</xmp:MetadataDate>
      </rdf:Description>
      <rdf:Description rdf:about=""
            xmlns:dc="http://purl.org/dc/elements/1.1/">
         <dc:format>application/pdf</dc:format>
         <dc:title>
            <rdf:Alt>
               <rdf:li xml:lang="x-default">Microsoft Word - CONPLAN 8888-11 ... Pentagon Zombie Plan ... Counter Zombie Dominance 2011</rdf:li>
            </rdf:Alt>
         </dc:title>
         <dc:creator>
            <rdf:Seq>
               <rdf:li>Doug</rdf:li>
            </rdf:Seq>
         </dc:creator>
      </rdf:Description>
      <rdf:Description rdf:about=""
            xmlns:xmpMM="http://ns.adobe.com/xap/1.0/mm/">
         <xmpMM:DocumentID>uuid:406c2972-c2bd-4470-95ad-1bfb6efb5258</xmpMM:DocumentID>
         <xmpMM:InstanceID>uuid:c4d76637-aaa4-4e08-8a28-149644c2400c</xmpMM:InstanceID>
      </rdf:Description>
   </rdf:RDF>
</x:xmpmeta>
                                                                                                    
                                                                                                    
                                                                                                    
                                                                                                    
                                                                                                    
                                                                                                    
                                                                                                    
                                                                                                    
                                                                                                    
                                                                                                    
                                                                                                    
                                                                                                    
                                                                                                    
                                                                                                    
                                                                                                    
                                                                                                    
                                                                                                    
                                                                                                    
                                                                                                    
                                                                                                    
                           
<?xpacket end="w"?>
endstreamendobj73 0 obj<</Filter/FlateDecode/First 5/Length 127/N 1/Type/ObjStm>>stream
h�,α
�0��W�D�rр�8���ME��u��-�����.�+\���{|v�����6���>]�_�5��P�H9�-�D Q�B��@�]�;�b�� �>3���-�1��ֶ�  R�20
endstreamendobj74 0 obj<</Filter/FlateDecode/First 5/Length 189/N 1/Type/ObjStm>>stream
h޴�M�@�2�҃��GV�.��
�n�N�`;���?��ҹ9�3��,`P�jpW�����ʢr��P=��OY�S��4��M���ƟZ7��tqp$�B �n+7�-��	8�0A�q�#'��5��y-*�C��ݴQ�A[����(�Y��R;4h�}�����b/N ���v=�Y� 8na,
endstreamendobj75 0 obj<</DecodeParms<</Columns 5/Predictor 12>>/Filter/FlateDecode/ID[<3F11E3847274FCD30547F3C418965B22><C0008D8905001A42BB2455150C4C5158>]/Info 93 0 R/Length 236/Root 95 0 R/Size 94/Type/XRef/W[1 3 1]>>stream
h�bb &F��L�gA$�_��,�"����A$�~0���D2�����R`r#��f˂�|6�T��� R���,��W�-B����}��'
���������#؅����j�� ����|V�^��H�|F��'�`�"�ߌ��gH�eH� �Y�!.df? �,�`�āHk�a����'�戀E�Ii`�K����� ���H�O�1dI%���HF�  N23	
endstreamendobjstartxref
116
%%EOF
�PNG

   IHDR  �  �   ��ߊ   tEXtSoftware Adobe ImageReadyq�e<  fiTXtXML:com.adobe.xmp     <?xpacket begin="﻿" id="W5M0MpCehiHzreSzNTczkc9d"?> <x:xmpmeta xmlns:x="adobe:ns:meta/" x:xmptk="Adobe XMP Core 5.3-c011 66.145661, 2012/02/06-14:56:27        "> <rdf:RDF xmlns:rdf="http://www.w3.org/1999/02/22-rdf-syntax-ns#"> <rdf:Description rdf:about="" xmlns:xmpMM="http://ns.adobe.com/xap/1.0/mm/" xmlns:stRef="http://ns.adobe.com/xap/1.0/sType/ResourceRef#" xmlns:xmp="http://ns.adobe.com/xap/1.0/" xmpMM:OriginalDocumentID="xmp.did:4917B3331D1EE2118D06E312E31F7521" xmpMM:DocumentID="xmp.did:33D14B29222A11E2B969E4E3D4FF7B3B" xmpMM:InstanceID="xmp.iid:33D14B28222A11E2B969E4E3D4FF7B3B" xmp:CreatorTool="Adobe Photoshop CS6 (Windows)"> <xmpMM:DerivedFrom stRef:instanceID="xmp.iid:216E29D47220E211A758A08AE3BDDE83" stRef:documentID="xmp.did:4917B3331D1EE2118D06E312E31F7521"/> </rdf:Description> </rdf:RDF> </x:xmpmeta> <?xpacket end="r"?>�/��  � IDATx��|TU��ϼ�f&���i�(RĲ�����u�k�]˪`�ˮk[u�������XPAA�H/�j $!m2��}�M^�I%ef������$������߹�k��|   ��F�)     �    ��    �    :    A        t    @�   :     �    ��    �   @�    A        t    �    :     �    ��   t    @�    A       ��    �    :     �        t    @�    A    �    ��    �    :    A        t    @�   :     �    ��    �    :    A        t    @�   :     �    ��    �   @�    A        t    �    :     �    ��   t    @�    A       ��    �    :     �        t    @�    A    �    ��    �    :    A        t    @�   :     �    ��    �   @�q
    :     �    h4��f��    ��    A       h�h���n7�"   �Xִ��~8� ���#X�-�����/��g�0u� ��o���+��M�kp_��?���y  � ���)���ٳG��'!!���nﭪjEQ:��!Z��f�_/���>�K�����x
ű�������UQQ����lǖ-[6M�2e�ƍy��k�7�z�� � �<6q��/��s� �������^�6mڐ����B�G��$��<�~�TVUUm�CAA���+W.���K�����{��{�;�=��s�t �׉���[��k�Νu�p���{qu}�oҀQ�8!ƃG��z���ă�>��!��{<�@�!� �K��t!�ɣG��H��eB�O���G��C���//))Y�cǎ�7�|�'�/.�!�u7�C��@d����1y���qqq,��9�*�M�ƌ�_��z�:��7�|�ߋ.�h�!�U��	t�v AoeA���B� Ќ���s�K�,I8p�N��zq�v��Ĺ%�z}�F�������6l�y�%�|���Wnwӵ[���8q�x�C�hB��Y��SϞ=oB�;�q�U�k���7F��	;g�o޼��+����u�֕��]���-��/�u A��.B�(˖-�0`�����Yȣ����
��=~�r��
��ԤI��޵kW�!�Uv A���b�̙3'�����ȧ���<�E���^^^�]<�<!��ǆ���^Eաx��@�!� �����zAA��			O���[$	yC�=P�>��K/���_��M�J��f�C��@h��[�v�ҥ�3����t!o����"��+\p�6n�Xdvk(n@�-`�T ZQ�1����jEEE�֭�jEQ�b��@a�D1o��j��ɹvٲesg̘1F|��icE�&}�����GxH���@+��U�Vu:�^��L��G�#o�c���7o~��3�xr���%��
��í8t8t ZU̹��������o�p�A�<�yC{-�A�ӧ�U�ׯk�ԩܺ�p�9���8t8t Zʕ�n���c�=��xo���k���v90q�|t��e��ͻ��/�D�Uj�un֪sҭé����!� ����u�zS��Sb?~Q�ӵq����;��#G�p�����U�<��C�hn1���ЩS����,����,�袋n���oX�ze�[���v#�C�e�\=|����;.����;t�p҇~��u�]�O��@5���x̫�v�󢢢�qqqs��������ܢ���'��q�}�7D������mu�n@���sY���������y��7�UG�܌3�|�M7-"=�n�[wqC��ҸA Tż���~��_boa��^oլY����?o�uDD��k8� 4�����=�i�T�y���E�z^E�_v�eSU�>y��y��ڭ5�M<�ouih�qc�q8~1?z��_M1G��uE=�c��/�W�u�w����Aļ���\.t���q�yqq�4������#����^�����g��IO����U�TK? A��������u/�<�D��뮻��D�k,i��: �[�Ճ��O�yh���(�[o���7�t�� QwB�A$r�Y�.�g�;g���?*==}��b��	h�-H�{YY��+���?�p�x��V?J��b���a>���A�]ٴiSϬ��o��4�yx�zaaᮑ#G^���?�C�}u΂��; sۧ�~�Խ{�� �K�~INN���'�L�4-��rz�r机\��;g � 4P�sssձcǾ$_�yx�:ӳg���:C�ku̧��!w �sF-..撮G\�nAB���y�G�L����
E+&����y0�W�C�����@k���}�Nb��`�@��å}g��kn�8qbo:v���� n����{dѢE���^b�"�ޢn<��L�>�����4����wS�1� � D�;����O1b���ֱ.� �!�������w�}�C��DIr �@���?���?��zv�9�<|��l#G�����zB�u A ��6􎉉y8ؼ9O� 춛o���A�u2D=�������1@�cwn;�쳵�ݻ�L�Ҧ� qoQ��_\\\�̙3oţ��帗�UVV�,����t���>�'�Ο@�==}�Z����}����[�D;"Z�he<�Qu%9�p��s��Ah�``�?IY�rew!� ��n����]�^�2�:A.���� :W��Nj96h�4Y�jn����������:�<�]�աs����7�)�v�Eg��Yt�J4/_p� T��S
�"��f:�SO=�п������-��#G���G;v�(��o{(������r��1�W���3b�>�)�fߞt�I�^s�5_���K�I��Ϳ��O=8{ ����E{�ر��O?����1D|͉�����M���E8�ڄ͸~�^�����> ������KJJ6m۶m��W_�E���"���"��*���P�����M�2�G�􀠷O�����999w�\��S��z>��"�p� $:�}�����ӟ�d��;���ƞ�p8�	�Jz��1n�)e �B�W+���S�._�re�!���Z�����ݚWXXxOtt�}��-�/���+�_��'�q3���ZEH�t��B.]�+��s�9�s��i琾\�V�j�������+��x�޽{?�뮻>���O���"�^���Zb5���%K::t����U�G�KKKK<u׮]��z>��z7]�� �M���ݻOIJJ�F��I���js�u	X}�V�5�9�@��zK����]�v����mYYY��	y,߬�ݚ'.����WÝCԹ-X���	&�l:�tN��N3A��BMб#�"����?w�ȑ?
�\������+��qB@k��lh���|O�]� +6�o�ر��ٳ��իW���3��@5�q�K���V7�29e͚5'8��6Wt�?��z�'�tR�Yl�,�
r 4N��#ϑϟ??yĈ"u���u��}<���Χ������x����_��_�:�7�8h�"��ayoS�ŝk%%%�4M�?�s�t�K_�t��O?}�x�����9���H�p���\y��w㊊��=z�V޻[�ס>7^��>^��]Q�Į]�����_�aÆ��:�,�c�Z{c���7���4�AD���d���zם5Z�b3 ����rss�L����&��k0�ٜ	o��jsJV�TUUU�v���?~�xh1R���z������;�w���+V|?f̘����f��yb�C������-^�xQtt��㮵9�`�8\QC�;�MBt�ă˃۶m�;}�����m�P�ݱL�Ԑ�ue���'
w>��v:t�駟��p�1.s� �zEE�b+!�ߕϚ5+��sϽW�-,f�9�p����-���ܹ��+�����˗�fE��w����C�΋��_��7և :\��zS�����e��z�tås��a:v��z���x`�q�טAo�bή|H�^}֯��z��S0al���wΞ=���_�2�C��a�.w�t�yT/^�sذa��\k��9�n,��7nڊ+��nV�;j<L���)�A��|%j�^
�� ᇠC��ؕ�\��_~y����jfNS�#I�������u�^<��Ӟ9*����:�>�`�>!��p�!.���>[x���J�s�V�^i\gMr�A�[���|}�gjS�LIMHH�-���(��QRRR\VV�5k��*���}>���C�[ӕ/X� ��O�)�_�^�TA�Kع>|���n���9s��6D�"�[瓣��/�.�*�U,��@C\����ׯߟ��ٳ��C�E�.wck�0�����mƌ	���/���s~G_UU���#��tqm�9_/>D���������R\\�aժU?\|��?S�e��ˁ"q����󼼼����^�Q�*ꕕ�/����e�s����Js����o.��s�����p�u��	�����]��+��B{��G���Ĝ�i�8ц���~����^��A��+((X0{���x��T�2�S��ùC�A3���o߾	III���2μ��*Ӟ[�k�����g�=$8��T5��RRR�;�s��~�С�ݻw�_܋�H/6����\z��+���ʇ~8*..�"��q�x�CC΃�_��]K�&��V����y�w�zꩃa��Pc;4]���{aa����'E�u-Ck�n�k4ߪU�^9r����u��e�N0`��-QHD�5�����.1\:�!'�Suƻ' �t�6!≣G����t^/�W��Hf�-�e�***��r��W��_}C5+1Z�J!�t�T1��E�=d��o3��Vw7I�R��w۶m�5����bvQrS�}���+>>��p砱.���k׮]�I'��~�K/6+-.�/�7o�q����"cw��
?����u�����<}�Yg}\^^n]ꆰ����(B��W_}�M�nV$�CA���n����/_�xfff:��sN縸�s� ���������O<�C�f��͢Ffv�,8�`��Ԓ��G�v��N�����'����Ǧ�HnjI���ͯ����6���������DՅ��o47q���x�
��-��믿�.��EUU�P��C|���A�K�.�/^�Dǎ3�L��k>�8���חy����r�-#�i=�{�>r��#G��"���:�n� 7�4PC>��B�E}N9�g�����s�=72@��P��bQ%��Cʙ�����M���/ܵk����Ĕ���,< ��^S֩���у7?:@5����Z�jpNN�����`�q[\Ë���������
��#���i3�ԟ�0|��z��9���b~#ļe�����
r=YŎ��'O����o��#�s�ƍW5k֬�RRR�����R����M���.|`ҤI�z�^U/�/y���������W�@tt�_̋���������"�ׅ�4եϟ?��Y�����?��v�mq:�����px0�k)��v��9�K.���?������M��7�.��j�@�#D��СC�?1o=a�9���KJJ�fee=8s��A�{��	�n��y�yCݺ%>o����{ｫ�؊�n�[��C�ۗ��ٳ猤������C�m�nw�޽�K�.���v8���	��*�ϟ��\����w�uz{se۶m'fff~-^'`i �)t�DR�K]5�xӆf�3�µ����?��z��ekmp�|��g�B��1 |h��n��"���o������5k��+;;��<ē�����x	�]>��-).p���mC���io��z~`a	�9 ���k�H�L����uW\qŭ.̧c�O0��"Y�:��ĜQ�����x(�Z� ����H������i޼y;,�n���,�J���{뉹�}����N�� n[�æ�[|||��3g>{�y��^�>���+c"hf �t�g͚�ءC�W��oGIW @���)�BԻ���O�s�9=I�S粱���!��w�xLqq1�u��ys @�G-���cݎ/��o��v��4g����[w��"�{���<����X̑� �$�nu�SOHH���[o=������ԃf��L��C/++�YBLL�t�g�N�0a�|�Z�v�  �N=X�ܮ]��>���G�R���ֵ�r��Аv;
��C1�����z��B �T�l�z׮]���7���n&���?�/=���Ν;G:��j �GQ2d��7�x�B���XD]55�Aiw>v�X-%%�_�[)B� �,�s�'N�í��:����q&����`��ŜQ8pmll�ts�&�9  �%H�w�S/---<�����w�m�>Bz�;�1+�����m>����!��/��:au�ܦ! ОE�L�۹s皁�����O�nV��mO�����w��/~��! �7j�w��}�[o�u1՜O��;/gC��z�s�ҥ��N獨 h�(���//���0��ק��^c>4�ܛϝsS><�n�_��
� Юĥ��{aaa~nn��}�Ӈ�f59s��vzG�=�����˳�o�J ���tk�;SRR:����W����@X��lh��i���X��={N����Q �WQ�Fy\dF�=��k����_��p�3�o�*�`�Co��p���_��i  p��宻��}ttt�s������� ���p�8:Q  j�y���S�СC�g�}�<�����Y�uzkb{���S�N��r  �]��}�ĉ�N:餮��td�C�[՝�s8f̘���� �na�6����c�]bq��.V���{�p
��H<]j�b�.�25;  T�y��!C���������� WI��`�$9H=8�p�-�m����E�3p�  @�.���;３]���j�ť#A�޲X�����ī�9r�:  +�GnYYY�o���Q�#���ع[�`A��~��  ��nm�^{�$UU�%a�s�*\z�`���\�߿���B԰T�4G��h�tp�:�?<@Ņ�TV\,[�ѣTVRB��x������.9��I����p��1��D��)���N	�ɔ��I��(�{w�KLĉm&�;v�6mڴS���/��夗��n������k�=z�]����8)�;wv�_�~�x���p������][�Pަ��c�Fڻ�gڿs�	AW�=j6�8�m˯2^GyO[^�%�#����Z�ˉ��k��O��w�su;����W��MA �����mϞ=;���{����x�U��^L�6�.C�}Bw"b�zAAW׬Y3*++k�)榠á��Pt�m��Gڼ�G�"Z���dג��H�)�G�X5��)�b xF17Ű���A���^��G!�_us����*��W|�#%*���^C�R�aè�{�I���jl�b���w���3�<�D|�!�GH߼��t�tzs�97����������4��#�{���[Z��[�߼Y
4�]QE�R�9��&���Y���K1Wa'w���l�)�>��.�U�vK1�HA珹����G~�K=���8f,5�bЩ�Y]��͛7>�qC�٩�o$�tz��;:6nܸY��A����Mk�����%���K�()�!�NUU��J�Q4����Lq�8tӝ+�;��p膘���C7[�Cg7�.!�B�+����ͫ��B/�W������14�3)>%��ۥ��+�xh�ܹk�:��.������]����ݻ��Ć�ͫWӷ�Σe�~J�B��P�1JS)JQ�XGiE�G��U�5�CW7o���?v�6S̩:䮋�r�̟���<>�<�E�u���7S�+ı���r>zܖ�½���=w(�u<u9�N\ ��.}���3�x�tv�ֹtN�sG�K��������ǣ��n���@J�p/z�}���9tp�N!ʺ��b�".�����h�Ǧ���;���B~�<�1����-��X�jY2�ɜK��q>)����t���r)�n*u��(>�sFщg�I�L�D��{� �tqz��߷r�ʭ.�4�\:�m��B�W��p;0ٵu+}��l�n�<�*��G"-���qvr�q��B�;w9_��N�fқ?�nd���7��
�)��{�ϣ�7`��gΧ�s�R�/ǣUny�����i�y����N'�nǅ�
�y4��g�}��E]���~��x/1D�	.�޶��-X����Ç���&S�͚���ǦU��Ù/�OK����q���+ݸhB���\��U�?��)"nn�����,���)�������d9�c/7;�8;�R!�%UU�k��)4��i���(*&�ץ�����������t���Q�\z��ҽtzSN<����;wޒ�����훟�����<m���l:q]�Eb/�����h#�n&�9D\�8����<c&ҙ��ykdû<�<���Yȥ������sϣ1���''�B5�'p	��?������Ts.�]���CЛ��y�u��4�L���'�֭���y�6}��r}>�E;ޡ���tQgwn�rS�9�.���3�f"[�׵t�2��X���0���u1gQ7�K��!�O�.�K�@P����-[�l6lؓ���Z\z������қ[Џ��k;2�Yg�e��O�M*=�)ط�f�������R��X�N<A|�`w�c�!��F�[�Z����Pr፺	,�/�>�`�⮐�w��5��[�8'���QCЋ]U�aλ���i�i�%��2��ľ]�(�kW\�m��hLe���+{��᝖/_ή�k���	��^i�,[;ӡ�u�%%%����ev��+N����<p�S�RY^N��2���,"!D�Ɯ8y�hI]��hq���3S�KH=�D�I��[�w�e��~�.Z��%Z�p�c�����?'$J����z���/��aˋ�v��丷�~��뮻�]����8�^d�t�6�Ea7m	-��N�\FE;t�0[��~��z��G�H�s^^�t�9�D!�����.������ϑs��a�@6�Vm���jvN�"��2QP��/uk�P%Ρ8wE�(����/Z���t�o��!C��o�j�\z�����{��K/,�k�n�q����0��-U�۪�~U�cz��+����GY/6�p}�������2=���ύ��$�S4]�ͤ7}�\Ov�a�P7�Pq�W���&ύ��^�bT�|@��{��Z�G;�؅c�ϧ9w�F�F���7�D	�i����?c}�ܳő�;�߾��b�������a	=>��#.,c��~��C�9��Q������s1�,��z푇�UR"C��к�d!�,�+�5\�)䜱n���v~N���dUX+/'����u��B�͕��_F/�n%�|�o���'�x�����}��;�e��0W�#�w�9�����_t�E���o6=*����8_��΂Co�C�����+1XX�?eG�ҫ�=J��͓��$C�u!��G�+gщ1��r.����f�����*x����9�Q���rٟ�#.�x�Z?��p睔���"�[����O�Fk.�������4�{����P��v��'���{����CW{�C�3egg�+�L��_OO�u'���'E�����������g�k��j:ryC��m�F2�k��<�,�#��w��7^OC/��N��7ͺ}+�����)��j��y�I}�"�X�m� � �i��~z�/���(�CG��^?			��s�x����b�gѻO?M���Ovc7�:��S�Q_�f׋�u֭���p*�����w�"n��[��kر�������J�y�Uںd	�{�]�ڹ�q����n����Y�1�y����()��߾�br���bK�"����/���BзQ�yt�Ko��q�:�A��7�����U���xv�R�Yģ䑅�\oed�������W��s�}����(c:v�B|�{�t�7��3�䟻{�vz�O7Q����~ ?��	!�-�8�T��%n��wS|�tV���F�ћjΡ[�L�ek�����Q���TM��"!.2(ػ��5u*�۶U��&:��]y���y@�ו������:�> =���nn	�#E��jzv�*�"N�ܕN�Õ����'hǏ?�Y7�D���F�����S�[Z*�y�Cw�醘�Q�]��m����]��T�y��֭[�.]�$�޽����gl�K���s�=�]ゅ�@x�{����+���m�}s��]Y��h�M����2�.��c+Ss�R��dh-Ф������;�[�~��H}�Q�M'�G�c���׋����]���+-�'�ç�2� �p1Q��E��C��p�����9��=�q�ș��I��(�_~y� �nu��^�!����z����z���dŢ���'wI����`Ύ��nB�ӌp�\���r��24Hn�7��d�)��s�p��#�b�y��޽�f���w����s����� M�n~�c�����h���{':��v��O������iCa�
��ѣs1wZ��"�6#��]��{-�y��
����~�f?�\"�s�2��.]��z��a�z������i��ϡw�����'}c�M/�+7����/�+��R:��˂��~����g����t�z��:�����1�ؗ^?�#Hq̇C�έ_�~�-b�Е���!���)����S�| ���{��2�q3��?�k�ugH]�5�D�d�њK�*��[�卬x�ͳߤ#�v��So#�n����^_y��䃹�w�z��Ŝ]y
�p���^�V�������%TUt��I�6s��_���$��榯\�ҜG73�1�N�א��f�ۻK��S}ޞ�}��F�>kf3�ɥi����;՚��ѻ�v7K�}��j�5��&8�~K���n:�{��ϼ�^Z�x�r��@17���ؗ����?S���ݻIKHD����Iq��'fA�A����s����p�����j7��Ï���_������d���X��1��%>�\9����[��edm6#3^�;vv��l��D��]�ֿ����:����zV1��x~��x�ϣ��Nhca�6t��,C�k�'&&ڊ����`A�gIHHP4M�mRË�=���XC̭˒j��|��̰-L�]/H�,'�S%�H�/,(�J��2�c���t
_<ݒ�0�_O~��ⱔ��O����P��ĸP����N!���ytz݆�v�����C;NG�0�����̗�rY0��٣��!ìN�3w��T�r4Vn�FF^��X���/喭�oyΜב3�q����o=�����zV�O~�"7Nz�e���N�:��ٳ��"聢�.�C���޽{gΛí�._�����t�y�N�b�˒���$-�(c]���_dX\����dc��Qޞ�%�U�nl ����ըɗ0�ƽo/��{�0!�b�U-s����w�1c�>�rW,�]���:�z=---	p��ʯ��K�bU�z��p�ҙ���av���4��n܇���r,��a�+�9��f��c�z�*U��9�_�ʂ�dm ��^���v��!w{���<:: ˅`����3�l��'z�{(J����9����S����b�0�b3���<���mƺ%EO�s��W����%߸�7��٨b�.r���D���[�z��a��u��ϣá�3F8�4�E�z<H�ﺓ�n���d��җ��(#���"p�7+���������E�b0�U#4���73�����<zȈ{VVV�!������q������3>(��ŧ!L��E���v*;tH
w�����"�2�f{���cE��T��Gtd������*---)555�СC�:D�ޔ��c�A�GQ��e��Q����i�;O5wLs8�R��5�M�A$�q:���{p߷��[�ĸl5jT�ܹsP�!�vW`!�z�5M����&_͝K�>�D�ӓ̝�d��+���N]�ҤH�)���������֯EV]C���ҳ��������k1�:tK]�4��V�k�����#���qB��۷ӛ��.+�U;s^w���9�]f3�R4$�]�t*�
�)�	dKJ"_l,Qt��D�<��9�\Dn7�ʎ���������ȨSn�O�ŉac���.�gX!\zt��8'm,�ܲ��S�:�,9N�I�R>r�H�pd�:ܹqDQ�������u�B���_���5��
pj'�YG /b����=�l]����Ij�0)j�nc�8���Ƚs'U��Lޟ��������XE���3�wC�C��sؽK�.��;�{��e�zt!��v�8����}s�X̡�o��_tx�N��ƅbtAw�0�sM�5K	åiV��òs�ַ/��rH��xLa��ō�!��@��]6=F��>t�\�S�Okȷy3��e�W;w�:�{��]�t������\�:�,�'�pB�O>��B��sD|\P^^�'zS^^��Gyd���XؽF�Y^��!�%���/"g!tX�t)-y�}J0��N6
��1A�S;sK�=܄�+~�	}�6ti�����RSI�#�W8��5��r���ٴQ���+�P���5{�7z[��եG	rss�)�RDK���ۊ+++W��A��iӦ}�t��
C�=F��<�xo����<9�&�4x�]<�c#t������n�O�f�Coʎ�i�^BU��9���(�#��r�Z��.�{j����C�嵔�N�)�Hq�bbC��s���t�r-����"��,S�ew:���t4>��~7c[��� iY�(���7���={��}������ņ��-�^C�[Cحz˝dv�lLb��i�=O1���>�<�}��s6��:
1�-_�e=a"�~!�܅��Ǔ�@H�	�:�?���/>'�Ν�PuQ�̐|��v��2!�UU��_O���E�6a�`���.tk��ŋ_�<y�CЫ,�=P�[L�!��w"k��e���w��9��t�:tk���W�����j��1�+��bΥ]�;�[a����@�9�M9���İ��l�&*��c�#?@�c��e�V���r�P_䪢��露^�pS������.'ϡ�����?����	&�7D��p�n�Ϸ����������llki������۷ύ���5�9�ܑH��
*ΐ3�Y�ٙ�מ'��Wl!�;rq��'m�߹�l�:2�Rv��Ϋ
��=������b-=�7�P��pc�����^m!x�;����X�����kҤI_�O����.t�Y@hS����L��C���o��y饗戯��p8��v{���D.�*\�l[UUU���,--u���f���.�Kyy2�-Q�im�dq���c�N7'l��4T�	�tx<0�4�Wn��g@LN��q���JޟC�C���)�J�ͱ+>=t�Bb\h�[�׭��X����|-�`�رc_��矗�����wܱN|i�!�.��o����\���0��iW�X�+33�t!�#�8�}�����"p����?���>G��鞋~MJY�� ���cdB�?�.D=C���v��v�����M�|A���#������Ɋ羬�<�wV��I�n�7g:��B��sw���r�e��s�ƍņ���8��v�����^�-B.E\�o��s����n�ĸ�}vk+3Xۿ-ˬ'��o�#C�QzV;��Ӎ�v��L(�9��ʸ��9��F	W������7ɽr�\q�PT�֕�_��8�t/v8)����cq�M��P<+++�>���{�����ė��)��uz-B�nݺ����Wk�v���Cm�\���%�����=t�%S��6�F;y�h=.9D����a��I��
����.��x�:4�5r���.�����[��qG\.���dO�F���ڃ9uC�=�7o~�������ң��+,�~�n��=�6ga!7�\<x����w���dff�VU�f��ہ��jkf���u��P{�3w��D��+���$�~�t�!�����srL��݊9�0p u���:����B��}��e�pc��i�r��3zVm��ڻw��W�^�ʤI�z�/�=bD�u���ٺ�[��o�6�nr���o߹s�%_|�Ū���9⽱n��V���&��
��l�y�'/�V|��p�L~Kt�e�=֮I�'����]��?����x"9�t3)po���P�k���+��b�Y%U.)�<-w~3�[��g������`ySVILL��3ϼy�}���ϫ�ŉM�j�������!�eLL�C�� �s����㣙3�㯍bTM�ggg�	pr^֦�2��)�*������:���_�kbЁ�F��؞ٔ��tr�G��/�?]��	��u������v�k�7SKxE�7��Rvv�_��������.�(ӴU|H�<1�9��@�����j�&�pۡO�޽�j�-�y�!��F�]n�j$��bږMfŰ���9/�?�y-Dw�D�o����I��2�Wy}�܅����AS\{�|z${`�t삨s�9����{��n���T�'��-���\'�9III��뮻���Lu8'4�!!r���fެ7�!���4O��s!�qG+�5��9��[әk�:��<�[ٽ��>� Ŕ%�w{��N����k�#�h����n��5-�ZuKSƌs����S9Y.��[���t���VB.�nse�������_�Or���`�j�B�,J��e,����4�t�ќ�n����6s!�,�vvnXOs�M�8�K��V9�CN�Bne��(��N�o�,( gz::8�Eݯ�^o��2d�U_|�v�g>m�cD]hZ��zH�1����_����B�Г�aukx{�G�|�!��UR��5�kry���1�
ave�hr
wjg��o�?7�I��P����*'ől�n���mr+Y��w�BG���z7���������o"=�ǹ�[��ŨF���k����>����N��Αז�"���x�Z璮q<�.�<F8s�j�w�w5y�����������iu�x8{�λ�SQ)�M3vg�db���M��)�,/̿�nd��a7��(��M��������W�/�-q��:����y��b_�dI�޽{�'N�P3�L�O4�~��*9x�R�=�n�Kw�4�87�y�������(v���ǡW\�A�����ы/�U	��o�R�iN��C[H�ic���U���aj?�n�K�3V�5j�5/��R�5�\�^���zג��6u�����/a1�^�����~���5F�מ�D8�2fw�����k��߯�=���d?�f:���*Z
o�����Ncg6
7�΃�O�.T!�N��t�&N�x�<0����u����̡[�<//����w�II�m�ydSr��6~��U�pr��Gw*�;W��əIp���ک3:,U.=w�}�f�W���P��)�<����eq �do�$86���ٿ�|�t�T���z`����ڵ�^{��5k����,CG`�7��E���۷���)���9�<�Y��礊>�m�)��m�z�fIW���5v:+e%%���7�گ���z����y\�?M��8K���K�d�+���T��T۽S�6M�}�����ykEN�����Z4I���*�������/�/�"��}&ðr����aY�j/��Z�Kї^�u�A8|� �����5��TI2;s��yG�t�����JQF��U{-��a�����toߢnvSÄ�u}��7o#=��`����{��z��IMM�:�	�����n���,v�y��о}�g�Jq�e�v}��Fvcݹ�)��;�P�s��d���� ����[�T�:/1L�;�9�sn�+����J��a�]��٥s�;���+��]ڬdee�2s����ꪫ�"�,�ܙ�ܩ�֜�B�9t��+[�n=->>~�r֕��_-��o�\����Kg'g�����ܤ��6�9O:���k�������,�d���3�Q�A�T!�IFv;�B؍:�p[|��5��&J�Bԃ�U�v��gO��%b��̥9iA������%&&���k�9h���+�$ŵ�y�9��s�������aˣ���~�P{�C����ğn"oY�t������Ңإ��yq�s-_���_ܪ�@�ApaW�~��wߚ���Fա��ڂU�}��?k�ҥ]����Z�bn��~(-.�]����73!�)k��s�m�g&�i#O!�KWt��Es?�gﾋ4�[��9�n&�u��S�/w�3��Ch���@���L�Ç�SQ������Uԭ儘w�9s�A\�=��7������wn{�Ǣ{���?�Gv�!�����ߓ�s�Bģ���N��ɘ\j�k���0������/Ϡ�f��kp����'����Cf��Lv���1��E�(.�*��vR|�\�\������:��Z7r��_]{��߿��ߓ>wnΟ{�z	�'��)��s���ʧ�qxm��1h�O�-[&���C�2�����l�T�V�6j�II�$���|�Q��s���KӢ�KwR��"�沴���k��#31�cV�Tk�2eʔ�bcc��4�9\z�	�u�|�Ν�(�2��5�e@v�.7�Г�l5��`��p��g������d���D����E%9tQO��7�E���l�Ɍ�HSrKS�$�J�EA�ݪu)))]�y�ITzgQw��l�w�)��&&�7z���^�zp�N��q��Q(j����m�@�7�w�R���3GX��\6�u�R��ܹm�J��U� T=J�k���k�R�YT�!J��89g����Mm�f�8V��P?��q'.��R�q�w��k�&ƕ�_��׷/Y�d���â�V"Z�h.�C�沶�t3���#����w�[*�_�]� �6���T�e3Un�HU�7�'��.�RWK�\ *�����$G������mXO.!��͛H��ZBґ*�<-S�v�Ѩ(:��O�B ǈ:�)ꚦѺu�;��ӟ_R`��ю�s�!��Y&�u������iii�y�={��[�1�׵* <KK�{�D.n���߷OƩ�fx."��ܙR�'�IOD>�ܵ��Y�7
�߶��*�_��V�����W�q�HUe?�Or$"����E��v�m{���W�6D�X�R~6�ꄹ�tu��ͧ&%%�w�ݶ�ʺ����T!Ľb�&��7���~)�#�>c�v^�k�;�t����w!v�۶R�z���ٵS�`�9�֜Ji�(��J�ߘq�TJ���u����[�z����Ρ�r��7ɥ7�5C̕'�|2611�y!�ڪ�p<h		7l�lR���r!�����P���E�����6�'h�A���?��l�H�p�2D�Q��ڄ~
���J|��I���tp�m@�;�.]���2e�IB'�2D��"��r�&-ck6�n�9��ٳ��D2պ�9�ǀ֤��!]�7o�!z�o���qQ�ޔz���V�u�ϳ�W�~R����=���fb�Q��ԓGR��]�A]:7K؝8�sРA��/9`���������.,�����4M��[\������T��Eč���I��m�%~�0���у��"��t�N�ı�����;��Wr۾��*!���_�-��X����6�n
�Yt�[fff�;���{l���+,B�N]i�Ko�nI�������8���,�P��Wl�^��K�JA�����ѡNP�u��J�c���2��W:4�f��8WlS����<�����y.}߾};��ť�	rMv���Е-[���(��;ǜ9U��:�F�N�ΐVٶm�Bq8(�وƸKJ����ܸA��YG�-���}b��|/*=��t�ir;u����k���K/-5\�9��d�~�ݸo��.�(�?���J���;�� ����T�~�,n�Q[y�Ͽs�E��twS�7RJn.:4إoݺu�1c�!����y.���.�xݟ�}��kcbb����`�  �E\�k*�~�����������w�9-Y��ez���h �&��%lf������/�Qu��,6Snz�ץ7G�]�6mZ��V; ��FK���S6���VVR�)��h��{Z��O�"=6Z�{�:P'�J/����<]�Ϥo�M5����ب!�~<�n�����j���;C� ���tR܀��1�"Y������������Z��1�?��W���ă��j�7}��ṙ����۷�!�[E��7h.�x:ϝ;�;���r @��%&R��#ec*�c/[o�߼�l�R�k�i�{7��U��G>1�T'�)�VQ���QS�Ly�w.���:�f�\î����l?��e��-`�W;  dqv�$�q����۫3��N�������Lx�����خ]q�A�E��駟>B������g��Uj�NlMt������[�� �����ɑ�&�Gފ
*ݸ�_�Ƴw�\���2���� �»vB�A�Bn> ZMp�g��}��%��;\z���m?���8����1I;  ,������:rD.��z9�^\\c��f�&��]H��c&Ǚ�M�<y���T=��0\�FL�k���U�Ԥ���f D2��$J<e�lLE�n}��w�۲Y����1��,h�K7�/g���#F����X\�)����-4�T��|��']5M��<p> � �T�:w��~�K��+�r���~�b:h����8!!!�.���;��i�t�i�K�79���e�5�����q8�PH  t���EG�D���4`�t��mѢEK/���Y�K�Su�x^�VA�T�kRȽS�N��+��έ��  �Q��`h@��y�������g�skb�v7��k�+M�O>����ckT   ���nYO�Ǐ�2D=01���u��#��d����_f�C�  �Ƌ���{�'Z�uS���CW�>�l�`b�D8�:   �x!7rnnn/��;\�R�n7Vй2���j���a    4]ܻu�ֹcǎ�z�u��gZ|%99��`Ed��  ��XdFUUۤI�r�O�~�jΣ���lc�����,��AsQY^��  h��<Х���92����LwS�mT�<zc��پ�����B��{����SS���é���ԣo_R�[ ��z�֪����N���T�gΡM�k�����:u��vМt�ڕ�}��v���>��
���R�!��g�P:A��n�p�  �Fعeff��<����ku�� **���As�-;��~��������Jڵ�;�!D���b�Ө���\]�㓓q�  +�F����㻿��k��Lws_�F:]s�5q��3k�#4]������5�QY��ƿU��<r�6͟Ok?��<��M��A9B�{��k�@rDE�$ �Z��Z���Æ�*}Uϟ[���}�t�ꫯ!~���@sҹgOr
g+�<Q���d�鹚R�-���];i�<Z��;���k��ԋ���aԵwoyc  @8;tn}���D�����5���8��$���2
av�����n��P��ѢTE�5�ט��Ӆ��
�G��#>�?�n�[���|���8�9h�t�,��;� �Rػu���p妠[ע+��,w)�QQQ��?͍*<�s���G�̣��U59�.]����R�}�ũY�+*i�wKi۷�҇�af�p�R���c\b"N8  ,D=111�K�.�w�.�ꐻ)覨7M�y��,(��sМt�ʢ���k�]8�(U4�K�B,�>E4�t����Gݹ�{�?T@>��V:O���^�dx��߳ �Á� 	�3�|:<z�莳g��[�Co��2k֬6�-��%H�E{�~+�/+��F�p�t�|q���ϻ]�=����k��.�u|��y�b�vZ��[����]�z�p�,�]���0� h#Wnn�j5����ѱ�ւn�Ґ9t[NN� ��AK���C��Y�Y�=B�}�z�\^��(cL>��u�΂�Uxc`�ޥ����6\�9�n��eH^��{�����Ք��G��ҋ�HH��!�R�y�=53 hq75�G�i1r��ҵ�Ĝ[|||�`"a����0:���h��b�~ U���W'����,��|��޽�| �-��*�{���+ ������YL[���>_�Աe���c���Q!�ן|L���Sgq	Bn��СC�E�k[��(������H�-EJǎds8����/g�n�����b��W�]�:�;��g���K1��Lq����[2��u�>�UB,<��f�����\�n�<�D��vt\p ?�f<��|�KIK���>\i0wu��'����ǌ��� wn��M������f�nݨ*/O���#���~̫���g�<ay��)H~/�!���m�w#,�<.p����m���-���7���G�����{�Pꜝ�� �˚e�d�^�>r�6,ZDEc��;P�A��א��[�|JFN�n�������GYY� �jS�����
�s�8�� �	W�۽�g#�M:��9��5E*#<���C����0��L�S|�dϛ.�*�z��c|����]?��ϼ/PtR��0Ǩ?�ܡ:��X�x�^�@U�Gvs��]XH[.��ȾM�̠��CDc�BIii8� dݹY���I˗/?H�Ο7z�z�!�VGZ�t�٧�e=1�EWO�kع����{}��˹xU�G��*��XW�xc�\�Q���W��E���8�kW���7���n���ի(F�]�]�xM�����n�0Q��p�*(��?�5�}*�6�KY�����B�O E�w� =�C?f�>AW���ӣ�� �Mлu׳����1�"j�K��7^��y�ݡV'���w�29���)�5�_���u���Kk?��VΝK!.����Ͽ���O����/�&� ��\�(Y�S�f�`�e.߱�
̨Kվ}�Ӟy2O�-���n��X/!�9�QlBN2h�nm]�vM��x�ekt[RRR��AK�٣�����+��to	�ۛq U��C��7׿����`���̦/ڼ��n�H_�1��(���-ĝ|�=��Md�y2�Ρv��*]z͢D>����
�'מ|Z�{���}��D�=�'���!�K<"-�M����� 1����^��z�'�e�V������d�GTUQa�+'9����v��9�n>HpX�g.�3�����k�x_��.������X��扯�NNї���SSq14��7���[(I��hE�s�\2�[�E���q+�꾲$r��0f�W��G+��L߿;�<��;���0d0eHN���ٙ�u-5556@ȃ͡7ȡ�����t��eeQ�����|>j�����ڪ׿��ł���!(<�ﱄ��n,"_)7���Ƞ�*)��҆/�;�siݻ˰/��y]gt4.� ,��.���z���٥��s�$9��0�����J��
|�|�J�54_��V�u+�K�~�|�>��-~��ߟ�N':4��K�e�`b��9tUU3�o-Ij�,*Z�Nw�^�_Խ��.�>�W-B/ݻY��p��v�*�p�����\������U�v���ߓ��v>���S~u%���")(��P�M�[�J�ņl�lCe��*;�f�pP�ƌ~r,gtY��j#�^qd�&�n�Z�����ԥo_0v��t:	4Y���"���ݦiZ���C�AK�.��&���b.����o�o�_�n�׸7�Ҵ����g͠7�����m��^k��І�Թ[WJHA(����"��!��c��r����dp�(�ň�x�ɿO����ֽ,Iv����~��Q24�������Щ!�n�W������0㌚�ܼ�m��{�1��i~� Y����Q��z>�f;S�+<^p�o���.�������aZ2w�p=Ԯ�$8�s����OJ-�TS�ɿ�њi����*��'��@��9Åe\�YT�QIq6�z��.v�K-"�¡s�"���[�������04k��3,�^�u��Z�k�A�or&_E=�:K�'�o����B>W%Eiv��M�sV���9��������>#,o���>�od��T��=rs16��r��j�f��Ṷz]�C76��%@�Ak������t�*,�˴��Љ��ګ.OK5��>���e9�=�9���7P��(,�޾����C���db5�%�6'ȩ63��;�~bT��@|C-h��f�NQd�ݙ�MNl��١�����p�W�-%�mH���t?r�ߥK�n2Y�3�Չ[��96���|��郋!`p��䓲�L�]�s�1s��æHwn�ܣ_m����l^�8y$:
4;QQQV�^[B��>�.�HQ�X�q�EzV:�r�QV,ͥb+V�+LI�.S���v	���)履(Ac1�כ�����f�;7�Zz��IwF��>�
�������帠���yP�Ӑ��h5:�Ȣ5w�W��E���Wi�:Q��n#;�ُ�k����32�ή<N�o�b��j<ʵ��a�}):+���XV�_�� ��Z��.� --M�-!n����^el�b.��P�Ϣ�-�Μ�|�-����
��G�
!�	�p�Z��E����J�<X�z�ȓ�w�Y�y��V�jv���3�D�bк�޵���e�x��zMw5�����3$�:�Ꮴ8�� ,|�}Z�d	�	1�z��*�k��R��2���߶B��G��S�Y�%�K���չ7��ٳ��<�քw"K��I��u[��"���	�&7vu��&�y-��ˣw�}V�p�j��v~��d8;KՌ�jmя<-=` ��a�E������� G?�&��� �-@+�5������e��#!�C��彸']@�矏ή���R�>m��E1f�����=��s����V}��������@���4��7H�y�,]�F���%Kj�s��E�(a/�^!@�W]E��NCG�1�����Ю]2�ή<A�DS�tY���I_�֖yJr2%�NmE�J�JA�i�FRhq8ӽʿ5��(2މqҙ������S<�������'m�n��3�3�y���;�z�m��8}�r���A�	���
�g	��v)6\����Q��; �AFV}�2��;�7{ݥ�۴�y��bb��)�����^y����@_���IpI�ۣFq�6s���Q�z"-���������u�xc݅�sК�df�-������k�d�pt55��N���:wF����o�EϜ)����<Q�x�TM/"�� �W[��Q�IC�W��X��r���]��MJ
S�=�� @K?��t�&˿�bx���w���w�1���g͢9ӧː:�7�9O����)N��P;W��e{�V�[����s:���g=VTTT�w�ϱ�f���C6c�
bZ�t�V��b�5A.���墟��U8�N&��������^��ח�|y��oT�savQ���ܜE\�qÆ�3#ZT��XZZ�8r_���x_��\��r���N�1�pH-I��,�fn)J���Uӝ���+�o�����:�|2:�4UU��?�A�?�\.E�3��p�vM�����&C튱D��B��>�G���0�y���#G��[4� �J��	�^ �Zݡ����}ѽ�I1�A�pu��G������3�������^m��M7k���/Q"�
&"���gb��.���rN��h�D':q%��?�hƞ6
��$�A@Hˢ`�464���]�]U�{_�[u���^��������*ڮ�W�{����4���x�r����Ϸ������&�:rT����T����7����y:7>�Ѽ�5�-]J��@D)������J����tm��/����S�H}r "M!K�b�^��b=��TQ��W��'̓��]����R塃c��=��Ǵ���4�y���f���g�������+�h/;���n�	
b&v&�.)�{����'s~���t�&37�����sg�< �DB߸��L��Ҧ�O��64�6���x�I�hi3�)oe)���������|${+\�6;��v��1E�o�b��v����b�����wb*����wJ���=�l�@��3���I��?ц/�v�h����.�\ꉲ^���?�8��f"|ЗWה�N��I���G��^M��p���}�r������Ǩ��Q�>hz2��f7���Ԭ�-Q����ѯ��ô4*�eNL3���S�N�)"7+�&��w��wީ����A,�.ũ��sb�O2�̛�y��˵�Y�QZW'�y�Ez��ёݻ��3t�\���/ҏ��]���_�V�:�y�}ȱۂ����o��߻x�ڈy�n��l81ATE���ĉ�&	�tp\���۲���g�y�������_A�ȟ6�>˿�9��2�]e���]�|�E���	��nM/�_���<��u���_���	��ܵ�~���I-uuz��;��K���:�����i���X��}�Qy����4�Ư�:�y ��5ū.����ٳݒ�=&R0`�;/쏞r8��hw4��hS0c���>�ї�
�a��H��>����zFz�:���4J�ѣ��_��&~�����o��_�J½��~H�[���c��זaՌ9�~���f~�[�����P}�:5�O:g��{�#�Պ�D=�˥����D���>(�����t:�����:�&|�;x��'���͟���G+�.�,���<����4vKռ���t�����?w}u��t���uw���!��L�|�Z�Ts��%����S�F�1�MM�qԺ��1w.NHs��9s�U���-t}�����rss	��A,q��Q�|�ih�'t>0�W"5-�w^�g�X�L3��o~gb�x)�k�T��;,j����7��ޞ0�.��Z�d�Ք��7���'���۴���jj�kz��T��Ӭ�t����^��7}𛐸X�*���e�Фիq2���[MꜪ��fE�E���
= �����gϞݧ��D��iE�P_H�r�4Q��jZ��i�Gv�':K�<�vzx�R'{v��
�ܺ�f.ZL�XA\v)9�)1?���N���с�;�|�n�x<��3�U�R�`�,�C�v�X�����ghj��"kZ:NDS�מ<y��B��{�ɼ?��+�x_~��OV�\��F�c`�|�{��F-���|]w%�Ե���H�V�ˎ�W���S���^��Z��=��Kg�L+�����x1/YBŋQ���Q;�:�����O۽�>+/'_O�.d}%7&m�ȃ��ii���T�9}��V��#^��Ջ_�4e.X�k�yJ�.//o��*�>��ir��߿���v��4mƁXR��^��s/yya*��[�$���)���H��5Zכ��MJ��~�w����|@����:��/*�i���#�ΞM�Ӧ�������ݺ��ϝ��+*�����Աc��Р��#�y��]�^���R��g�f5����]��Z����i���=N@0*b���8p�@�$�^E�CZ�]N����򜜜Yj:��*t���%`I_�տ?��}E���8����͘��dO�i���b�I\�w�JN��3t������;�mg��
hK�Y���LM�����������;Xi���&j<�Z��5�8�*w\�9�f4�;4�C_F$���>��;��U�&%����3�۩��!m��! F*q�TWW��0ȼ�|�}���Ν;w //�f��d�ԩ�c���X����7��؝d�ZB�����ƈ~����>_>�ma�|�f"w�Bo�"��_WGu_|A�������3�W�[�j!2��	����0d�0Vt����TN�~�8����]'~�J+*��b&s3�WVV6*鼇�6��P�.��?����Hc',��A��ˡfO�D=55�Hw�|ty-Y����?I��\�>қ��{��{ty3�{}Ɯ}_�}Qҧ�	�SߥT�@%�/`�yݐ�]��Kb��m��U�\��f�x�R����+�Q��8����t��#G��Sh�y�I:٤e0-���JJJ*׮][Ǟ̀�2�h:�;s�<^B�t����Tw4����O�~Y�[���w�����=|��E�kl�ꑒhp'8�}�>|�H�Vi��M�H�b��&U!��oRf̠ik���������k%���H}X}�>����>��ͽ.��(wHD������ݻ��fC`J���C��B�>C�b�{]���uy[����\�+	=$�M�bn|�ɝ�M�V�g!7��H�+��>feьu���p�ZF]��C�5Qhs��$��P�� |oUUծ������b	�O���_����^������yz&��q�*��S�Fw���<(�"�^�~�nijI��Co�`�2w���d���y9~�x���ӹ��{iK��)]�6lݺu��ŋ��	5�1���.:����}�f�)�T��sQ�6�����5�Q�Eg	M���"-�"R�K�4����*��~J�=_"0�b���U�6��.,3��a�S��������(B5�C� Z����&t=��C�)���c�X�����m�����F0eC�gv�wmF�Z��п����?���t睔}��8�@�Ȝ�n߾�,���JJW���}��=����'� D��7m�1j;؏�\�Ӽy	�<�lE\V�J���` .d.Jsss������)�����ݧ����t�YB �L���ŀ/��(ù��"�7P���{��ߤI�܂����zyy�y
��<fu��L[���sϝ���>���s�d�r �썮���C�C�̴Y����I�55��Rx�dD\��W��ɫW�Z�F������:�-%�~�~��P�饧����tӄ^T2e���;�����/�9�~Hs������WU�z������(`d2�p��4�;��7ą��d�_���7�<c���Aq��j���{�n�Z��'�Z�  ��#�ɿ����JU^d��"~/���Ҭ�5�m����(cɥ���D�x�������$F��ͷ�l��4�s�>|�����%%s�"��������~���հ'=`�Z�$tHD���\J�i4���b���_�7Ҍ�g�X��/��N<� M��f����M��o���$�hvvI�|�4y�*�H ��.0�e�Νg(��.D.�K�}:V�xS���@�?}�>�<00�X
��P|��)w~�&�v����|VѼիi��#��A�FR���3�:�Q��#�J�ｏ
��'�+����|!����7�)8����>�&w!�@?��?����4�)�� Z��q=����Ki65�l6���T�w7�ߞ�d	]���+,���ے�g���9��%޴4-#�fo�HyK���q%s3���G��(���m�p��0�m�}��kjj:*++�f͚u����5���Ћ��(��C��j�e���h���4n��A�M�L_}�I*�yjص�R��;�Qp/oTU��I;�N��6�s�\�@\K]����������L��=Ԅ.���'}�W^c�Q�ݑ�A4З������^VDRlPb�Φ�?��.�bKI��Y��w�=�e�S���x��\�9|(qW�璹hoެ��x�Z�7���ܯ����?wI�<\s���.K]o���o{����/fB ������(M�b+P=���]���PKh#�誫��-[�>k���6v2�bj[�&sv����[G֔�) ���}ɻ�������A}��E���)KX�5��4�������_�����3-�2Y�ꑶ	���i��Q�L����<_&�,}�Q��z5u��r@�5�з/��6���e�}�ך~ �ҹ�_}��
ë.��	�"�����'*))����� R:�|Mwyښبeܢ�i�ƍ��򈞄��:o�*��'�6����&u/;���M��F�:%��/��K����'s��  �:/'N��?x�`#��e��h�������G�M������b�c+�^cj_`f���Ӝu�Is8�����y�=�M��;��m|!�Lo��ح�i4����<� ��9w�~�����eu��w �[��!���"�˯�i��c%w�������}qOO�����+Z z�}zg�ʷ�)����?��[o��1t���ї_��}���NJ>�׌5cMxTa���z�%���5kȑ��7$��E��ldgװ�����/�����_kb���Z��?kc�CJ�a�j�����<�wm۶���k�~�����h�G��&�^�����{h�׿��Vj^-����C��W_�����Ч��������ȉ�c�ͣik�K9��N��V$�7�x��9�&��	}����$t9�����S��ݻK


���;P�𔎓DOo/m�ַ趇��K���h���j��ڶ�գ�����	+����\�|���뮣I7݄� !�98O�"��K�.�߶�6��X����F:��꭬t����ԇ�3K�?��Ϟc�c֟@$�����g����'+��ϸ�jZ���D&��A.���1�N��C���x1-()�������O����z����m����NW��2���_J�����os�νE�t�/)$3��V:��?й���ʾ�<���؇.����i��Ք9o����\�;gnt/[��������f)���:#���֡�s_4�.�K�Zeg%Րz��9s������|���.�����:Hr�������ν�.Y��!�AH\ܦϙCSn�����e�1 �e.�.d��֭[�<��#��oRo2$��Q�`�v#�8.RB7K�9O=��7o��MrJRGJc�������3o�E���d����T��,��7� ������΅�p8�����+�x���CN��E�y;���n$BWSz�H���ٳ��	&,7�Rc	/;jv���;���"(vQ�crg��]�r�.�«������EI��e�����|��s�}J��h�F"2o��T5����>�p)=M���˗���/~���*uyC �-��tf�;T�gY\��ؓ=��i�YXH�W�����"[f&� �d��l�2?w�\ˊ+v0���s�����D�rJ�I)=��\V�����v���t ��vwS�޽T���Pۑ#��sB3�ړ%��g7��6���(�rʜ;_ ��BW������~����.���L�yG�S��.��S: ��aԛ�w�ܹyʔ)���MGJ�Owc#ղ�^�ou?N;/�Ԯ�=/'qkz:e/ZD�K�P���f���I/s�w.��={�T�y��[N�B��|X�<"B?}�4Y�k�H遦����i����ϝN��Q�: ��o_��OPK��ե'w�,/K=��S�N�JY]D�RVq1Yl6|�`���l7c�ʕ;Ξ=�D�����θ�%�>�tN4��_�͸)�����Ok�ӟ>����1��j���> cǸq4e�
���Z**���ajfrog�ՊIԠ-1N��V�)S�и��)�K_�,V�����E�yT4�?��S����(��+O�m��;����"�I�K�%����<��M��>��X����A�  TvδWWS�|[e%�����vw�H^>�Ýٖ�!�f�5-�R&M��1s&�ϘAӧ�55@:��^^^^�jժ��ԺX�N*�υ����Q:I��I�������6/�?R`踚�����WC�ٿ{������zX����U�}.�?I8����p��|z�^x�7ߊ��b���#;�M�ϧT&rGV�h ���l ���r_���1O6Rp ��;������4�!��G\�����+ANvvv�[o��H~~~q��!u   �$s��]�j߸q�۶m;E���VC�u���ij=�M�z%=�/��]ݸEl	��4777~�;�y����F�`^�M\̶^   �M��V�3�e������?'c�2I����=4̾�]B�א�x1m'O�<w���?�����  H�ʼ���iÆ冬;���"�.R���>�&��H�B�B袆���>������\.W3�   QE�ʼ��õv��Yh��`�H�rB[���|�D%�KM�>
6��C�[v��q|����3���R��C�   A�B���އ~x?K�-ad�*��E��#N�Q�D���V!�u��=�&u�A�   !��e˖�m߾��$s=�JE�s�w��tU�+M��YAN���q����������Z���6�   �$s�_�򗟼��K���;)�_�j�N4��t��p1K�&R��B�d�w�ޓ��v��uuuUb����!v   �y2/--=�y��c&2o6�H�3�KR;�MH������k��r�ȑᤎ&x   ����'��~�jݺu��6)��2o�d�c8�����2UUU����ӧˋΈ�����|�q�_M.�����o^���z�^M,+o�nꂅh   D+�˫�ɋ��ر����;�$��H�|��㶉��t*B�X:�IBWP�z��&�޸~���ٴiӳ����|-\9����  DS��Hv�"�7�x���{��2��y��2��[)8E-*M�1:K�j�<�C��4n۶���7޸�رc����ф;  �X���綾^:��C}L}��U��~s�%j`�����z?Rw�2�]4W|���g��_|���v�۹���.7��bǨx   ���_.��alڴi��?�))k�"o��.���<bS�L_G���e��tQ���A��\2�߯.�ֳ�M�V�裏^�dɒ��5ѯ.��˛��m�v   f2ׅ$�B9���������߻woG��+�\���˻�)j9�2&��M�.�QR��2�g��}m�ƍ��9s�43���[!�p� y  �"7K��:t��{�������D�-�̛�˫���<��ޏ���e�gJ����;�c��5k��:u�D3��K�ć*s��  �%�ު��ߊqZ����^8���OW����\���B}�i�S�͓J��-��Ŵ6��3�����zk���K�ϟ?��������{����k2F���"W�Y*��ĉ�ׯ?�駟�Pp�W�$s��]�fy�2OJ�+bRs����f�]���w�E]4�����.�����N����x��ԣ}q�ǋg<S��/�����ILy�K��R�y[[����?��/�b�g������mR�F��F�'�Е����i=M{�*u.~�͖~�7̼���]x�E�������	wD��H����M��3��+�/���YC�{�f��KE��ʜ7�����z��G?iii��T��k��T�N��i���NOKj��H�B�&x��EbO��xq_�7^p.[�lҕW^9��.(�={v|z���G��$�D8NT�ƶ�Q1�����YfM����o\�|�֒���gϞ�0�,��;���-��;(tјA�|L]����Ii]�_O���ԍ�?���0}�㋋�s'N��1~������T��aKKKs�|i,����p��g��l�p���M{,N�����>6^�X����L�T"F�����~r���OC{��7�^x�ʺ��N
�*���y�B��~�$su${���cZ���u�!t��)�v!�T��H]�6��`�*�2�M��g�����ӭ�p����kg_>��s:�{�-�cǍ��6Ӆ���-쿇���
��UT4�D��'{�#�cSRR�������̴[�|��v��kwr�ʑ�~`'(�x��9.���d��䕶p�kTڴd�����H�J�Xn�����^B̚��D���ݻo߾s�������[�{�f!~�˩�KI���LR�,s�`e>�&����;��.�{�Rd�;(8E�*M�<X)��=�
�P>�!W(F��h�H��WAA�#��J����*mJ'���*}a+)�rd㕠0��
�Z�b���c�=��R�+m����c�T�4��_e�W��T�4^1N��hi�%k�f����_�뫩�i�裏jw��y���ޫ������J��-�rY�&���BW��7T�GC�6�s�uD�+Ոz�������	�)��1@R��>��>��w�S�3�
I�KT+e����Q����b��n���J�f��\yyy�V�Xe��yrrr¶J�
�IK�܊g�-jf�+V!�����%*m�cy�����k��%�Ly�-�c��6k?�������V�p������3������t��S__���q��鶏?����kckk�[q�����r�̮!z��=J*�U�� ���]J�v���m�齿�n�Ї�;ѪE������㋧
I<U�"�b��O�֣1�Z�3)�tE�v�#�N���.~�-U
��I��L�#�hVFL�.�a�*¶+"�Rh��*s��yb)��V"ي��Ve(��/���x����9#%�D>�D���s1ZǯJ�K�[u{(��]�������n���D�'�S��@�B7��ܭ��m\��J�M�M���"E'�(��
e�UTq%��	=��E�KI�ݒ��4�#=�#���y2	=܇���z_M�̇[c�t��ww�Rtb�2֕��l��EkY�*���%�>�^I�nE��{�ߗ�Wy��#����Eƣ$w����D>Д��\t�#����F��&s�$Q+w��T�CJ��
]�?�Sz�"�
$'�q����y=مN�>��	��G"�h�^�+�z�x{�xy��~�^V���z�BO�����oև�Udݫ��Ԧ��y�} ��Ŏ��G�%~7Q�?^߯D� �[�,֯%׍�V*F����$u�����$qo_$cE�I>>�D�`��M�JY2C���%�s�굨2'I{��;�%�'OǚXXp�IU)K���X��}��t�"���8� *:������Xl-�M��5�  �[�A�l��-    �h�    $t    @�    �        :     t    B    �        :    �    B    �       ��    �    B    �   @�    ��    �    B    t    @�    ��    �   :     t    @�    ��       :     t    @�    �        :     t    B    �        :     t    B    �        :    �    B    �       ��    �    B    �   @�    ��    �    B    t    @�    ��    �   :     t    @�    ��       :     t    @�    �        :     t    B    �        :    �    B    �        :    �    B    �       ��    �    B    �   $�_� =�@Wť��    IEND�B`�<?php
namespace CloudConvert\tests;

use CloudConvert\Api;

/**
 * Tests of Process class
 *
 * @package CloudConvert
 * @category CloudConvert
 * @author Josias Montag <josias@montag.info>
 */
class ProcessTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Define id to create object
     */
    protected function setUp()
    {
        $this->api_key = getenv('API_KEY');
        $this->api = new Api($this->api_key);
    }

    /**
     * Test if process with uploading an input file (input.png) works
     */
    public function testIfProcessWithUploadWorks()
    {
        $process = $this->api->createProcess([
            'inputformat' => 'png',
            'outputformat' => 'pdf',
        ]);
        $process->start([
            'input' => 'upload',
            'outputformat' => 'pdf',
            'wait' => true,
            'file' => fopen(__DIR__ . '/input.png', 'r'),
        ]);
        $this->assertEquals($process->step, 'finished');
        $this->assertEquals($process->output->ext, 'pdf');
        $this->process = $process;
        // cleanup
        $process->delete();
    }




    /**
     * Test if download of output file works
     */
    public function testIfDownloadOfOutputFileWorks()
    {
        $process = $this->api->createProcess([
            'inputformat' => 'png',
            'outputformat' => 'pdf',
        ]);
        $process->start([
            'input' => 'upload',
            'outputformat' => 'pdf',
            'wait' => true,
            'file' => fopen(__DIR__ . '/input.png', 'r'),
        ])->download(__DIR__ . '/output.pdf');
        $this->assertFileExists(__DIR__ . '/output.pdf');
        // cleanup
        $process->delete();
        @unlink(__DIR__ . '/output.pdf');
    }


    /**
     * Test if process with uploading an input file (input.png) and custom options (quality) works
     */
    public function testIfProcessWithUploadAndCustomOptionsWorks()
    {
        $process = $this->api->createProcess([
            'inputformat' => 'png',
            'outputformat' => 'pdf',
        ]);
        $process->start([
            'input' => 'upload',
            'outputformat' => 'pdf',
            'wait' => true,
            'converteroptions' => [
                'quality' => 10,
            ],
            'file' => fopen(__DIR__ . '/input.png', 'r'),
        ]);
        $this->assertEquals($process->step, 'finished');
        $this->assertEquals($process->output->ext, 'pdf');
        $this->assertEquals($process->converter->options->quality, 10);
        // cleanup
        $process->delete();
    }

    /**
     * Test if process with downloading an input file from an URL works
     */
    public function testIfProcessWithInputDownloadWorks()
    {
        $process = $this->api->createProcess([
            'inputformat' => 'png',
            'outputformat' => 'jpg',
        ]);
        $process->start([
            'input' => 'download',
            'outputformat' => 'jpg',
            'wait' => true,
            'file' => 'https://cloudconvert.com/blog/wp-content/themes/cloudconvert/img/logo_96x60.png',
        ]);
        $this->assertEquals($process->step, 'finished');
        $this->assertEquals($process->output->ext, 'jpg');
        // cleanup
        $process->delete();
    }

    /**
     * Test if download of multiple output file works
     */
    public function testIfDownloadOfMultipleOutputFileWorks()
    {
        $process = $this->api->createProcess([
            'inputformat' => 'pdf',
            'outputformat' => 'jpg',
        ]);
        $process->start([
            'input' => 'upload',
            'outputformat' => 'jpg',
            'wait' => true,
            'converteroptions' => [
                'page_range' => '1-2',
            ],
            'file' => fopen(__DIR__ . '/input.pdf', 'r'),
        ])->downloadAll(__DIR__);
        $this->assertFileExists(__DIR__ . '/input-1.jpg');
        $this->assertFileExists(__DIR__ . '/input-2.jpg');
        // cleanup
        $process->delete();
        @unlink(__DIR__ . '/input-1.jpg');
        @unlink(__DIR__ . '/input-2.jpg');
    }


    /**
     * Test if the convert shortcut works
     */
    public function testIfConvertShortcutWorks()
    {
        $process = $this->api->convert([
            'input' => 'upload',
            'inputformat' => 'pdf',
            'outputformat' => 'jpg',
            'wait' => true,
            'converteroptions' => [
                'page_range' => '1-2',
            ],
            'file' => fopen(__DIR__ . '/input.pdf', 'r'),
        ]);
        $this->assertEquals($process->step, 'finished');
        // cleanup
        $process->delete();

    }



    /**
     * Test if multiple convert shortcut works
     */
    public function testIfMultipleConvertShortcutWorks()
    {
        foreach(["input.png","input.png","input.png"] as $file) {
            $process = $this->api->convert([
                'inputformat' => 'png',
                'outputformat' => 'pdf',
                'input' => 'upload',
                'wait' => true,
                'file' => fopen(__DIR__ . '/' . $file, 'r'),
            ]);
            $this->assertEquals($process->step, 'finished');
            $this->assertEquals($process->output->ext, 'pdf');
            $this->process = $process;
            // cleanup
            $process->delete();
        }
    }



}
<?php

// autoload.php @generated by Composer

require_once __DIR__ . '/composer' . '/autoload_real.php';

return ComposerAutoloaderInit0d640eadf053bd1fa6ed35b2b3c83d98::getLoader();
<?php

/*
 * This file is part of Composer.
 *
 * (c) Nils Adermann <naderman@naderman.de>
 *     Jordi Boggiano <j.boggiano@seld.be>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Composer\Autoload;

/**
 * ClassLoader implements a PSR-0 class loader
 *
 * See https://github.com/php-fig/fig-standards/blob/master/accepted/PSR-0.md
 *
 *     $loader = new \Composer\Autoload\ClassLoader();
 *
 *     // register classes with namespaces
 *     $loader->add('Symfony\Component', __DIR__.'/component');
 *     $loader->add('Symfony',           __DIR__.'/framework');
 *
 *     // activate the autoloader
 *     $loader->register();
 *
 *     // to enable searching the include path (eg. for PEAR packages)
 *     $loader->setUseIncludePath(true);
 *
 * In this example, if you try to use a class in the Symfony\Component
 * namespace or one of its children (Symfony\Component\Console for instance),
 * the autoloader will first look for the class under the component/
 * directory, and it will then fallback to the framework/ directory if not
 * found before giving up.
 *
 * This class is loosely based on the Symfony UniversalClassLoader.
 *
 * @author Fabien Potencier <fabien@symfony.com>
 * @author Jordi Boggiano <j.boggiano@seld.be>
 */
class ClassLoader
{
    // PSR-4
    private $prefixLengthsPsr4 = array();
    private $prefixDirsPsr4 = array();
    private $fallbackDirsPsr4 = array();

    // PSR-0
    private $prefixesPsr0 = array();
    private $fallbackDirsPsr0 = array();

    private $useIncludePath = false;
    private $classMap = array();

    private $classMapAuthoritative = false;

    public function getPrefixes()
    {
        if (!empty($this->prefixesPsr0)) {
            return call_user_func_array('array_merge', $this->prefixesPsr0);
        }

        return array();
    }

    public function getPrefixesPsr4()
    {
        return $this->prefixDirsPsr4;
    }

    public function getFallbackDirs()
    {
        return $this->fallbackDirsPsr0;
    }

    public function getFallbackDirsPsr4()
    {
        return $this->fallbackDirsPsr4;
    }

    public function getClassMap()
    {
        return $this->classMap;
    }

    /**
     * @param array $classMap Class to filename map
     */
    public function addClassMap(array $classMap)
    {
        if ($this->classMap) {
            $this->classMap = array_merge($this->classMap, $classMap);
        } else {
            $this->classMap = $classMap;
        }
    }

    /**
     * Registers a set of PSR-0 directories for a given prefix, either
     * appending or prepending to the ones previously set for this prefix.
     *
     * @param string       $prefix  The prefix
     * @param array|string $paths   The PSR-0 root directories
     * @param bool         $prepend Whether to prepend the directories
     */
    public function add($prefix, $paths, $prepend = false)
    {
        if (!$prefix) {
            if ($prepend) {
                $this->fallbackDirsPsr0 = array_merge(
                    (array) $paths,
                    $this->fallbackDirsPsr0
                );
            } else {
                $this->fallbackDirsPsr0 = array_merge(
                    $this->fallbackDirsPsr0,
                    (array) $paths
                );
            }

            return;
        }

        $first = $prefix[0];
        if (!isset($this->prefixesPsr0[$first][$prefix])) {
            $this->prefixesPsr0[$first][$prefix] = (array) $paths;

            return;
        }
        if ($prepend) {
            $this->prefixesPsr0[$first][$prefix] = array_merge(
                (array) $paths,
                $this->prefixesPsr0[$first][$prefix]
            );
        } else {
            $this->prefixesPsr0[$first][$prefix] = array_merge(
                $this->prefixesPsr0[$first][$prefix],
                (array) $paths
            );
        }
    }

    /**
     * Registers a set of PSR-4 directories for a given namespace, either
     * appending or prepending to the ones previously set for this namespace.
     *
     * @param string       $prefix  The prefix/namespace, with trailing '\\'
     * @param array|string $paths   The PSR-0 base directories
     * @param bool         $prepend Whether to prepend the directories
     *
     * @throws \InvalidArgumentException
     */
    public function addPsr4($prefix, $paths, $prepend = false)
    {
        if (!$prefix) {
            // Register directories for the root namespace.
            if ($prepend) {
                $this->fallbackDirsPsr4 = array_merge(
                    (array) $paths,
                    $this->fallbackDirsPsr4
                );
            } else {
                $this->fallbackDirsPsr4 = array_merge(
                    $this->fallbackDirsPsr4,
                    (array) $paths
                );
            }
        } elseif (!isset($this->prefixDirsPsr4[$prefix])) {
            // Register directories for a new namespace.
            $length = strlen($prefix);
            if ('\\' !== $prefix[$length - 1]) {
                throw new \InvalidArgumentException("A non-empty PSR-4 prefix must end with a namespace separator.");
            }
            $this->prefixLengthsPsr4[$prefix[0]][$prefix] = $length;
            $this->prefixDirsPsr4[$prefix] = (array) $paths;
        } elseif ($prepend) {
            // Prepend directories for an already registered namespace.
            $this->prefixDirsPsr4[$prefix] = array_merge(
                (array) $paths,
                $this->prefixDirsPsr4[$prefix]
            );
        } else {
            // Append directories for an already registered namespace.
            $this->prefixDirsPsr4[$prefix] = array_merge(
                $this->prefixDirsPsr4[$prefix],
                (array) $paths
            );
        }
    }

    /**
     * Registers a set of PSR-0 directories for a given prefix,
     * replacing any others previously set for this prefix.
     *
     * @param string       $prefix The prefix
     * @param array|string $paths  The PSR-0 base directories
     */
    public function set($prefix, $paths)
    {
        if (!$prefix) {
            $this->fallbackDirsPsr0 = (array) $paths;
        } else {
            $this->prefixesPsr0[$prefix[0]][$prefix] = (array) $paths;
        }
    }

    /**
     * Registers a set of PSR-4 directories for a given namespace,
     * replacing any others previously set for this namespace.
     *
     * @param string       $prefix The prefix/namespace, with trailing '\\'
     * @param array|string $paths  The PSR-4 base directories
     *
     * @throws \InvalidArgumentException
     */
    public function setPsr4($prefix, $paths)
    {
        if (!$prefix) {
            $this->fallbackDirsPsr4 = (array) $paths;
        } else {
            $length = strlen($prefix);
            if ('\\' !== $prefix[$length - 1]) {
                throw new \InvalidArgumentException("A non-empty PSR-4 prefix must end with a namespace separator.");
            }
            $this->prefixLengthsPsr4[$prefix[0]][$prefix] = $length;
            $this->prefixDirsPsr4[$prefix] = (array) $paths;
        }
    }

    /**
     * Turns on searching the include path for class files.
     *
     * @param bool $useIncludePath
     */
    public function setUseIncludePath($useIncludePath)
    {
        $this->useIncludePath = $useIncludePath;
    }

    /**
     * Can be used to check if the autoloader uses the include path to check
     * for classes.
     *
     * @return bool
     */
    public function getUseIncludePath()
    {
        return $this->useIncludePath;
    }

    /**
     * Turns off searching the prefix and fallback directories for classes
     * that have not been registered with the class map.
     *
     * @param bool $classMapAuthoritative
     */
    public function setClassMapAuthoritative($classMapAuthoritative)
    {
        $this->classMapAuthoritative = $classMapAuthoritative;
    }

    /**
     * Should class lookup fail if not found in the current class map?
     *
     * @return bool
     */
    public function isClassMapAuthoritative()
    {
        return $this->classMapAuthoritative;
    }

    /**
     * Registers this instance as an autoloader.
     *
     * @param bool $prepend Whether to prepend the autoloader or not
     */
    public function register($prepend = false)
    {
        spl_autoload_register(array($this, 'loadClass'), true, $prepend);
    }

    /**
     * Unregisters this instance as an autoloader.
     */
    public function unregister()
    {
        spl_autoload_unregister(array($this, 'loadClass'));
    }

    /**
     * Loads the given class or interface.
     *
     * @param  string    $class The name of the class
     * @return bool|null True if loaded, null otherwise
     */
    public function loadClass($class)
    {
        if ($file = $this->findFile($class)) {
            includeFile($file);

            return true;
        }
    }

    /**
     * Finds the path to the file where the class is defined.
     *
     * @param string $class The name of the class
     *
     * @return string|false The path if found, false otherwise
     */
    public function findFile($class)
    {
        // work around for PHP 5.3.0 - 5.3.2 https://bugs.php.net/50731
        if ('\\' == $class[0]) {
            $class = substr($class, 1);
        }

        // class map lookup
        if (isset($this->classMap[$class])) {
            return $this->classMap[$class];
        }
        if ($this->classMapAuthoritative) {
            return false;
        }

        $file = $this->findFileWithExtension($class, '.php');

        // Search for Hack files if we are running on HHVM
        if ($file === null && defined('HHVM_VERSION')) {
            $file = $this->findFileWithExtension($class, '.hh');
        }

        if ($file === null) {
            // Remember that this class does not exist.
            return $this->classMap[$class] = false;
        }

        return $file;
    }

    private function findFileWithExtension($class, $ext)
    {
        // PSR-4 lookup
        $logicalPathPsr4 = strtr($class, '\\', DIRECTORY_SEPARATOR) . $ext;

        $first = $class[0];
        if (isset($this->prefixLengthsPsr4[$first])) {
            foreach ($this->prefixLengthsPsr4[$first] as $prefix => $length) {
                if (0 === strpos($class, $prefix)) {
                    foreach ($this->prefixDirsPsr4[$prefix] as $dir) {
                        if (file_exists($file = $dir . DIRECTORY_SEPARATOR . substr($logicalPathPsr4, $length))) {
                            return $file;
                        }
                    }
                }
            }
        }

        // PSR-4 fallback dirs
        foreach ($this->fallbackDirsPsr4 as $dir) {
            if (file_exists($file = $dir . DIRECTORY_SEPARATOR . $logicalPathPsr4)) {
                return $file;
            }
        }

        // PSR-0 lookup
        if (false !== $pos = strrpos($class, '\\')) {
            // namespaced class name
            $logicalPathPsr0 = substr($logicalPathPsr4, 0, $pos + 1)
                . strtr(substr($logicalPathPsr4, $pos + 1), '_', DIRECTORY_SEPARATOR);
        } else {
            // PEAR-like class name
            $logicalPathPsr0 = strtr($class, '_', DIRECTORY_SEPARATOR) . $ext;
        }

        if (isset($this->prefixesPsr0[$first])) {
            foreach ($this->prefixesPsr0[$first] as $prefix => $dirs) {
                if (0 === strpos($class, $prefix)) {
                    foreach ($dirs as $dir) {
                        if (file_exists($file = $dir . DIRECTORY_SEPARATOR . $logicalPathPsr0)) {
                            return $file;
                        }
                    }
                }
            }
        }

        // PSR-0 fallback dirs
        foreach ($this->fallbackDirsPsr0 as $dir) {
            if (file_exists($file = $dir . DIRECTORY_SEPARATOR . $logicalPathPsr0)) {
                return $file;
            }
        }

        // PSR-0 include paths.
        if ($this->useIncludePath && $file = stream_resolve_include_path($logicalPathPsr0)) {
            return $file;
        }
    }
}

/**
 * Scope isolated include.
 *
 * Prevents access to $this/self from included files.
 */
function includeFile($file)
{
    include $file;
}
<?php

// autoload_classmap.php @generated by Composer

$vendorDir = dirname(dirname(__FILE__));
$baseDir = dirname($vendorDir);

return array(
);
<?php

// autoload_files.php @generated by Composer

$vendorDir = dirname(dirname(__FILE__));
$baseDir = dirname($vendorDir);

return array(
    $vendorDir . '/guzzlehttp/promises/src/functions_include.php',
    $vendorDir . '/guzzlehttp/psr7/src/functions_include.php',
    $vendorDir . '/guzzlehttp/guzzle/src/functions_include.php',
);
<?php

// autoload_namespaces.php @generated by Composer

$vendorDir = dirname(dirname(__FILE__));
$baseDir = dirname($vendorDir);

return array(
);
<?php

// autoload_psr4.php @generated by Composer

$vendorDir = dirname(dirname(__FILE__));
$baseDir = dirname($vendorDir);

return array(
    'Psr\\Http\\Message\\' => array($vendorDir . '/psr/http-message/src'),
    'GuzzleHttp\\Psr7\\' => array($vendorDir . '/guzzlehttp/psr7/src'),
    'GuzzleHttp\\Promise\\' => array($vendorDir . '/guzzlehttp/promises/src'),
    'GuzzleHttp\\' => array($vendorDir . '/guzzlehttp/guzzle/src'),
    'CloudConvert\\' => array($baseDir . '/src'),
);
<?php

// autoload_real.php @generated by Composer

class ComposerAutoloaderInit0d640eadf053bd1fa6ed35b2b3c83d98
{
    private static $loader;

    public static function loadClassLoader($class)
    {
        if ('Composer\Autoload\ClassLoader' === $class) {
            require __DIR__ . '/ClassLoader.php';
        }
    }

    public static function getLoader()
    {
        if (null !== self::$loader) {
            return self::$loader;
        }

        spl_autoload_register(array('ComposerAutoloaderInit0d640eadf053bd1fa6ed35b2b3c83d98', 'loadClassLoader'), true, true);
        self::$loader = $loader = new \Composer\Autoload\ClassLoader();
        spl_autoload_unregister(array('ComposerAutoloaderInit0d640eadf053bd1fa6ed35b2b3c83d98', 'loadClassLoader'));

        $map = require __DIR__ . '/autoload_namespaces.php';
        foreach ($map as $namespace => $path) {
            $loader->set($namespace, $path);
        }

        $map = require __DIR__ . '/autoload_psr4.php';
        foreach ($map as $namespace => $path) {
            $loader->setPsr4($namespace, $path);
        }

        $classMap = require __DIR__ . '/autoload_classmap.php';
        if ($classMap) {
            $loader->addClassMap($classMap);
        }

        $loader->register(true);

        $includeFiles = require __DIR__ . '/autoload_files.php';
        foreach ($includeFiles as $file) {
            composerRequire0d640eadf053bd1fa6ed35b2b3c83d98($file);
        }

        return $loader;
    }
}

function composerRequire0d640eadf053bd1fa6ed35b2b3c83d98($file)
{
    require $file;
}
[
    {
        "name": "guzzlehttp/promises",
        "version": "1.1.0",
        "version_normalized": "1.1.0.0",
        "source": {
            "type": "git",
            "url": "https://github.com/guzzle/promises.git",
            "reference": "bb9024c526b22f3fe6ae55a561fd70653d470aa8"
        },
        "dist": {
            "type": "zip",
            "url": "https://api.github.com/repos/guzzle/promises/zipball/bb9024c526b22f3fe6ae55a561fd70653d470aa8",
            "reference": "bb9024c526b22f3fe6ae55a561fd70653d470aa8",
            "shasum": ""
        },
        "require": {
            "php": ">=5.5.0"
        },
        "require-dev": {
            "phpunit/phpunit": "~4.0"
        },
        "time": "2016-03-08 01:15:46",
        "type": "library",
        "extra": {
            "branch-alias": {
                "dev-master": "1.0-dev"
            }
        },
        "installation-source": "dist",
        "autoload": {
            "psr-4": {
                "GuzzleHttp\\Promise\\": "src/"
            },
            "files": [
                "src/functions_include.php"
            ]
        },
        "notification-url": "https://packagist.org/downloads/",
        "license": [
            "MIT"
        ],
        "authors": [
            {
                "name": "Michael Dowling",
                "email": "mtdowling@gmail.com",
                "homepage": "https://github.com/mtdowling"
            }
        ],
        "description": "Guzzle promises library",
        "keywords": [
            "promise"
        ]
    },
    {
        "name": "psr/http-message",
        "version": "1.0",
        "version_normalized": "1.0.0.0",
        "source": {
            "type": "git",
            "url": "https://github.com/php-fig/http-message.git",
            "reference": "85d63699f0dbedb190bbd4b0d2b9dc707ea4c298"
        },
        "dist": {
            "type": "zip",
            "url": "https://api.github.com/repos/php-fig/http-message/zipball/85d63699f0dbedb190bbd4b0d2b9dc707ea4c298",
            "reference": "85d63699f0dbedb190bbd4b0d2b9dc707ea4c298",
            "shasum": ""
        },
        "require": {
            "php": ">=5.3.0"
        },
        "time": "2015-05-04 20:22:00",
        "type": "library",
        "extra": {
            "branch-alias": {
                "dev-master": "1.0.x-dev"
            }
        },
        "installation-source": "dist",
        "autoload": {
            "psr-4": {
                "Psr\\Http\\Message\\": "src/"
            }
        },
        "notification-url": "https://packagist.org/downloads/",
        "license": [
            "MIT"
        ],
        "authors": [
            {
                "name": "PHP-FIG",
                "homepage": "http://www.php-fig.org/"
            }
        ],
        "description": "Common interface for HTTP messages",
        "keywords": [
            "http",
            "http-message",
            "psr",
            "psr-7",
            "request",
            "response"
        ]
    },
    {
        "name": "guzzlehttp/psr7",
        "version": "1.2.3",
        "version_normalized": "1.2.3.0",
        "source": {
            "type": "git",
            "url": "https://github.com/guzzle/psr7.git",
            "reference": "2e89629ff057ebb49492ba08e6995d3a6a80021b"
        },
        "dist": {
            "type": "zip",
            "url": "https://api.github.com/repos/guzzle/psr7/zipball/2e89629ff057ebb49492ba08e6995d3a6a80021b",
            "reference": "2e89629ff057ebb49492ba08e6995d3a6a80021b",
            "shasum": ""
        },
        "require": {
            "php": ">=5.4.0",
            "psr/http-message": "~1.0"
        },
        "provide": {
            "psr/http-message-implementation": "1.0"
        },
        "require-dev": {
            "phpunit/phpunit": "~4.0"
        },
        "time": "2016-02-18 21:54:00",
        "type": "library",
        "extra": {
            "branch-alias": {
                "dev-master": "1.0-dev"
            }
        },
        "installation-source": "dist",
        "autoload": {
            "psr-4": {
                "GuzzleHttp\\Psr7\\": "src/"
            },
            "files": [
                "src/functions_include.php"
            ]
        },
        "notification-url": "https://packagist.org/downloads/",
        "license": [
            "MIT"
        ],
        "authors": [
            {
                "name": "Michael Dowling",
                "email": "mtdowling@gmail.com",
                "homepage": "https://github.com/mtdowling"
            }
        ],
        "description": "PSR-7 message implementation",
        "keywords": [
            "http",
            "message",
            "stream",
            "uri"
        ]
    },
    {
        "name": "guzzlehttp/guzzle",
        "version": "6.1.1",
        "version_normalized": "6.1.1.0",
        "source": {
            "type": "git",
            "url": "https://github.com/guzzle/guzzle.git",
            "reference": "c6851d6e48f63b69357cbfa55bca116448140e0c"
        },
        "dist": {
            "type": "zip",
            "url": "https://api.github.com/repos/guzzle/guzzle/zipball/c6851d6e48f63b69357cbfa55bca116448140e0c",
            "reference": "c6851d6e48f63b69357cbfa55bca116448140e0c",
            "shasum": ""
        },
        "require": {
            "guzzlehttp/promises": "~1.0",
            "guzzlehttp/psr7": "~1.1",
            "php": ">=5.5.0"
        },
        "require-dev": {
            "ext-curl": "*",
            "phpunit/phpunit": "~4.0",
            "psr/log": "~1.0"
        },
        "time": "2015-11-23 00:47:50",
        "type": "library",
        "extra": {
            "branch-alias": {
                "dev-master": "6.1-dev"
            }
        },
        "installation-source": "dist",
        "autoload": {
            "files": [
                "src/functions_include.php"
            ],
            "psr-4": {
                "GuzzleHttp\\": "src/"
            }
        },
        "notification-url": "https://packagist.org/downloads/",
        "license": [
            "MIT"
        ],
        "authors": [
            {
                "name": "Michael Dowling",
                "email": "mtdowling@gmail.com",
                "homepage": "https://github.com/mtdowling"
            }
        ],
        "description": "Guzzle is a PHP HTTP client library",
        "homepage": "http://guzzlephp.org/",
        "keywords": [
            "client",
            "curl",
            "framework",
            "http",
            "http client",
            "rest",
            "web service"
        ]
    }
]
# CHANGELOG

## 1.1.0 - 2016-03-07

* Update EachPromise to prevent recurring on a iterator when advancing, as this
  could trigger fatal generator errors.
* Update Promise to allow recursive waiting without unwrapping exceptions.

## 1.0.3 - 2015-10-15

* Update EachPromise to immediately resolve when the underlying promise iterator
  is empty. Previously, such a promise would throw an exception when its `wait`
  function was called.

## 1.0.2 - 2015-05-15

* Conditionally require functions.php.

## 1.0.1 - 2015-06-24

* Updating EachPromise to call next on the underlying promise iterator as late
  as possible to ensure that generators that generate new requests based on
  callbacks are not iterated until after callbacks are invoked.

## 1.0.0 - 2015-05-12

* Initial release
{
    "name": "guzzlehttp/promises",
    "type": "library",
    "description": "Guzzle promises library",
    "keywords": ["promise"],
    "license": "MIT",
    "authors": [
        {
            "name": "Michael Dowling",
            "email": "mtdowling@gmail.com",
            "homepage": "https://github.com/mtdowling"
        }
    ],
    "require": {
        "php": ">=5.5.0"
    },
    "require-dev": {
        "phpunit/phpunit": "~4.0"
    },
    "autoload": {
        "psr-4": {
            "GuzzleHttp\\Promise\\": "src/"
        },
        "files": ["src/functions_include.php"]
    },
    "extra": {
        "branch-alias": {
            "dev-master": "1.0-dev"
        }
    }
}
Copyright (c) 2015 Michael Dowling, https://github.com/mtdowling <mtdowling@gmail.com>

Permission is hereby granted, free of charge, to any person obtaining a copy
of this software and associated documentation files (the "Software"), to deal
in the Software without restriction, including without limitation the rights
to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
copies of the Software, and to permit persons to whom the Software is
furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in
all copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
THE SOFTWARE.
all: clean test

test:
	vendor/bin/phpunit

coverage:
	vendor/bin/phpunit --coverage-html=artifacts/coverage

view-coverage:
	open artifacts/coverage/index.html

clean:
	rm -rf artifacts/*
<?xml version="1.0" encoding="UTF-8"?>
<phpunit bootstrap="./tests/bootstrap.php"
         colors="true">
  <testsuites>
    <testsuite>
      <directory>tests</directory>
    </testsuite>
  </testsuites>
  <filter>
    <whitelist>
      <directory suffix=".php">src</directory>
      <exclude>
        <directory suffix="Interface.php">src/</directory>
      </exclude>
    </whitelist>
  </filter>
</phpunit>
# Guzzle Promises

[Promises/A+](https://promisesaplus.com/) implementation that handles promise
chaining and resolution iteratively, allowing for "infinite" promise chaining
while keeping the stack size constant. Read [this blog post](https://blog.domenic.me/youre-missing-the-point-of-promises/)
for a general introduction to promises.

- [Features](#features)
- [Quick start](#quick-start)
- [Synchronous wait](#synchronous-wait)
- [Cancellation](#cancellation)
- [API](#api)
  - [Promise](#promise)
  - [FulfilledPromise](#fulfilledpromise)
  - [RejectedPromise](#rejectedpromise)
- [Promise interop](#promise-interop)
- [Implementation notes](#implementation-notes)


# Features

- [Promises/A+](https://promisesaplus.com/) implementation.
- Promise resolution and chaining is handled iteratively, allowing for
  "infinite" promise chaining.
- Promises have a synchronous `wait` method.
- Promises can be cancelled.
- Works with any object that has a `then` function.
- C# style async/await coroutine promises using
  `GuzzleHttp\Promise\coroutine()`.


# Quick start

A *promise* represents the eventual result of an asynchronous operation. The
primary way of interacting with a promise is through its `then` method, which
registers callbacks to receive either a promise's eventual value or the reason
why the promise cannot be fulfilled.


## Callbacks

Callbacks are registered with the `then` method by providing an optional 
`$onFulfilled` followed by an optional `$onRejected` function.


```php
use GuzzleHttp\Promise\Promise;

$promise = new Promise();
$promise->then(
    // $onFulfilled
    function ($value) {
        echo 'The promise was fulfilled.';
    },
    // $onRejected
    function ($reason) {
        echo 'The promise was rejected.';
    }
);
```

*Resolving* a promise means that you either fulfill a promise with a *value* or
reject a promise with a *reason*. Resolving a promises triggers callbacks
registered with the promises's `then` method. These callbacks are triggered
only once and in the order in which they were added.


## Resolving a promise

Promises are fulfilled using the `resolve($value)` method. Resolving a promise
with any value other than a `GuzzleHttp\Promise\RejectedPromise` will trigger
all of the onFulfilled callbacks (resolving a promise with a rejected promise
will reject the promise and trigger the `$onRejected` callbacks).

```php
use GuzzleHttp\Promise\Promise;

$promise = new Promise();
$promise
    ->then(function ($value) {
        // Return a value and don't break the chain
        return "Hello, " . $value;
    })
    // This then is executed after the first then and receives the value
    // returned from the first then.
    ->then(function ($value) {
        echo $value;
    });

// Resolving the promise triggers the $onFulfilled callbacks and outputs
// "Hello, reader".
$promise->resolve('reader.');
```


## Promise forwarding

Promises can be chained one after the other. Each then in the chain is a new
promise. The return value of of a promise is what's forwarded to the next
promise in the chain. Returning a promise in a `then` callback will cause the
subsequent promises in the chain to only be fulfilled when the returned promise
has been fulfilled. The next promise in the chain will be invoked with the
resolved value of the promise.

```php
use GuzzleHttp\Promise\Promise;

$promise = new Promise();
$nextPromise = new Promise();

$promise
    ->then(function ($value) use ($nextPromise) {
        echo $value;
        return $nextPromise;
    })
    ->then(function ($value) {
        echo $value;
    });

// Triggers the first callback and outputs "A"
$promise->resolve('A');
// Triggers the second callback and outputs "B"
$nextPromise->resolve('B');
```

## Promise rejection

When a promise is rejected, the `$onRejected` callbacks are invoked with the
rejection reason.

```php
use GuzzleHttp\Promise\Promise;

$promise = new Promise();
$promise->then(null, function ($reason) {
    echo $reason;
});

$promise->reject('Error!');
// Outputs "Error!"
```

## Rejection forwarding

If an exception is thrown in an `$onRejected` callback, subsequent
`$onRejected` callbacks are invoked with the thrown exception as the reason.

```php
use GuzzleHttp\Promise\Promise;

$promise = new Promise();
$promise->then(null, function ($reason) {
    throw new \Exception($reason);
})->then(null, function ($reason) {
    assert($reason->getMessage() === 'Error!');
});

$promise->reject('Error!');
```

You can also forward a rejection down the promise chain by returning a
`GuzzleHttp\Promise\RejectedPromise` in either an `$onFulfilled` or
`$onRejected` callback.

```php
use GuzzleHttp\Promise\Promise;
use GuzzleHttp\Promise\RejectedPromise;

$promise = new Promise();
$promise->then(null, function ($reason) {
    return new RejectedPromise($reason);
})->then(null, function ($reason) {
    assert($reason === 'Error!');
});

$promise->reject('Error!');
```

If an exception is not thrown in a `$onRejected` callback and the callback
does not return a rejected promise, downstream `$onFulfilled` callbacks are
invoked using the value returned from the `$onRejected` callback.

```php
use GuzzleHttp\Promise\Promise;
use GuzzleHttp\Promise\RejectedPromise;

$promise = new Promise();
$promise
    ->then(null, function ($reason) {
        return "It's ok";
    })
    ->then(function ($value) {
        assert($value === "It's ok");
    });

$promise->reject('Error!');
```

# Synchronous wait

You can synchronously force promises to complete using a promise's `wait`
method. When creating a promise, you can provide a wait function that is used
to synchronously force a promise to complete. When a wait function is invoked
it is expected to deliver a value to the promise or reject the promise. If the
wait function does not deliver a value, then an exception is thrown. The wait
function provided to a promise constructor is invoked when the `wait` function
of the promise is called.

```php
$promise = new Promise(function () use (&$promise) {
    $promise->deliver('foo');
});

// Calling wait will return the value of the promise.
echo $promise->wait(); // outputs "foo"
```

If an exception is encountered while invoking the wait function of a promise,
the promise is rejected with the exception and the exception is thrown.

```php
$promise = new Promise(function () use (&$promise) {
    throw new \Exception('foo');
});

$promise->wait(); // throws the exception.
```

Calling `wait` on a promise that has been fulfilled will not trigger the wait
function. It will simply return the previously delivered value.

```php
$promise = new Promise(function () { die('this is not called!'); });
$promise->deliver('foo');
echo $promise->wait(); // outputs "foo"
```

Calling `wait` on a promise that has been rejected will throw an exception. If
the rejection reason is an instance of `\Exception` the reason is thrown.
Otherwise, a `GuzzleHttp\Promise\RejectionException` is thrown and the reason
can be obtained by calling the `getReason` method of the exception.

```php
$promise = new Promise();
$promise->reject('foo');
$promise->wait();
```

> PHP Fatal error:  Uncaught exception 'GuzzleHttp\Promise\RejectionException' with message 'The promise was rejected with value: foo'


## Unwrapping a promise

When synchronously waiting on a promise, you are joining the state of the
promise into the current state of execution (i.e., return the value of the
promise if it was fulfilled or throw an exception if it was rejected). This is
called "unwrapping" the promise. Waiting on a promise will by default unwrap
the promise state.

You can force a promise to resolve and *not* unwrap the state of the promise
by passing `false` to the first argument of the `wait` function:

```php
$promise = new Promise();
$promise->reject('foo');
// This will not throw an exception. It simply ensures the promise has
// been resolved.
$promise->wait(false);
```

When unwrapping a promise, the delivered value of the promise will be waited
upon until the unwrapped value is not a promise. This means that if you resolve
promise A with a promise B and unwrap promise A, the value returned by the
wait function will be the value delivered to promise B.

**Note**: when you do not unwrap the promise, no value is returned.


# Cancellation

You can cancel a promise that has not yet been fulfilled using the `cancel()`
method of a promise. When creating a promise you can provide an optional
cancel function that when invoked cancels the action of computing a resolution
of the promise.


# API


## Promise

When creating a promise object, you can provide an optional `$waitFn` and
`$cancelFn`. `$waitFn` is a function that is invoked with no arguments and is
expected to resolve the promise. `$cancelFn` is a function with no arguments
that is expected to cancel the computation of a promise. It is invoked when the
`cancel()` method of a promise is called.

```php
use GuzzleHttp\Promise\Promise;

$promise = new Promise(
    function () use (&$promise) {
        $promise->resolve('waited');
    },
    function () {
        // do something that will cancel the promise computation (e.g., close
        // a socket, cancel a database query, etc...)
    }
);

assert('waited' === $promise->wait());
```

A promise has the following methods:

- `then(callable $onFulfilled, callable $onRejected) : PromiseInterface`
  
  Creates a new promise that is fulfilled or rejected when the promise is
  resolved.

- `wait($unwrap = true) : mixed`

  Synchronously waits on the promise to complete.
  
  `$unwrap` controls whether or not the value of the promise is returned for a
  fulfilled promise or if an exception is thrown if the promise is rejected.
  This is set to `true` by default.

- `cancel()`

  Attempts to cancel the promise if possible. The promise being cancelled and
  the parent most ancestor that has not yet been resolved will also be
  cancelled. Any promises waiting on the cancelled promise to resolve will also
  be cancelled.

- `getState() : string`

  Returns the state of the promise. One of `pending`, `fulfilled`, or
  `rejected`.

- `resolve($value)`

  Fulfills the promise with the given `$value`.

- `reject($reason)`

  Rejects the promise with the given `$reason`.


## FulfilledPromise

A fulfilled promise can be created to represent a promise that has been
fulfilled.

```php
use GuzzleHttp\Promise\FulfilledPromise;

$promise = new FulfilledPromise('value');

// Fulfilled callbacks are immediately invoked.
$promise->then(function ($value) {
    echo $value;
});
```


## RejectedPromise

A rejected promise can be created to represent a promise that has been
rejected.

```php
use GuzzleHttp\Promise\RejectedPromise;

$promise = new RejectedPromise('Error');

// Rejected callbacks are immediately invoked.
$promise->then(null, function ($reason) {
    echo $reason;
});
```


# Promise interop

This library works with foreign promises that have a `then` method. This means
you can use Guzzle promises with [React promises](https://github.com/reactphp/promise)
for example. When a foreign promise is returned inside of a then method
callback, promise resolution will occur recursively.

```php
// Create a React promise
$deferred = new React\Promise\Deferred();
$reactPromise = $deferred->promise();

// Create a Guzzle promise that is fulfilled with a React promise.
$guzzlePromise = new \GuzzleHttp\Promise\Promise();
$guzzlePromise->then(function ($value) use ($reactPromise) {
    // Do something something with the value...
    // Return the React promise
    return $reactPromise;
});
```

Please note that wait and cancel chaining is no longer possible when forwarding
a foreign promise. You will need to wrap a third-party promise with a Guzzle
promise in order to utilize wait and cancel functions with foreign promises.


## Event Loop Integration

In order to keep the stack size constant, Guzzle promises are resolved
asynchronously using a task queue. When waiting on promises synchronously, the
task queue will be automatically run to ensure that the blocking promise and
any forwarded promises are resolved. When using promises asynchronously in an
event loop, you will need to run the task queue on each tick of the loop. If
you do not run the task queue, then promises will not be resolved.

You can run the task queue using the `run()` method of the global task queue
instance.

```php
// Get the global task queue
$queue = \GuzzleHttp\Promise\queue();
$queue->run();
```

For example, you could use Guzzle promises with React using a periodic timer:

```php
$loop = React\EventLoop\Factory::create();
$loop->addPeriodicTimer(0, [$queue, 'run']);
```

*TODO*: Perhaps adding a `futureTick()` on each tick would be faster?


# Implementation notes


## Promise resolution and chaining is handled iteratively

By shuffling pending handlers from one owner to another, promises are
resolved iteratively, allowing for "infinite" then chaining.

```php
<?php
require 'vendor/autoload.php';

use GuzzleHttp\Promise\Promise;

$parent = new Promise();
$p = $parent;

for ($i = 0; $i < 1000; $i++) {
    $p = $p->then(function ($v) {
        // The stack size remains constant (a good thing)
        echo xdebug_get_stack_depth() . ', ';
        return $v + 1;
    });
}

$parent->resolve(0);
var_dump($p->wait()); // int(1000)

```

When a promise is fulfilled or rejected with a non-promise value, the promise
then takes ownership of the handlers of each child promise and delivers values
down the chain without using recursion.

When a promise is resolved with another promise, the original promise transfers
all of its pending handlers to the new promise. When the new promise is
eventually resolved, all of the pending handlers are delivered the forwarded
value.


## A promise is the deferred.

Some promise libraries implement promises using a deferred object to represent
a computation and a promise object to represent the delivery of the result of
the computation. This is a nice separation of computation and delivery because
consumers of the promise cannot modify the value that will be eventually
delivered.

One side effect of being able to implement promise resolution and chaining
iteratively is that you need to be able for one promise to reach into the state
of another promise to shuffle around ownership of handlers. In order to achieve
this without making the handlers of a promise publicly mutable, a promise is
also the deferred value, allowing promises of the same parent class to reach
into and modify the private properties of promises of the same type. While this
does allow consumers of the value to modify the resolution or rejection of the
deferred, it is a small price to pay for keeping the stack size constant.

```php
$promise = new Promise();
$promise->then(function ($value) { echo $value; });
// The promise is the deferred value, so you can deliver a value to it.
$promise->deliver('foo');
// prints "foo"
```
<?php
namespace GuzzleHttp\Promise;

/**
 * Exception thrown when too many errors occur in the some() or any() methods.
 */
class AggregateException extends RejectionException
{
    public function __construct($msg, array $reasons)
    {
        parent::__construct(
            $reasons,
            sprintf('%s; %d rejected promises', $msg, count($reasons))
        );
    }
}
<?php
namespace GuzzleHttp\Promise;

/**
 * Exception that is set as the reason for a promise that has been cancelled.
 */
class CancellationException extends RejectionException
{
}
<?php
namespace GuzzleHttp\Promise;

/**
 * Represents a promise that iterates over many promises and invokes
 * side-effect functions in the process.
 */
class EachPromise implements PromisorInterface
{
    private $pending = [];

    /** @var \Iterator */
    private $iterable;

    /** @var callable|int */
    private $concurrency;

    /** @var callable */
    private $onFulfilled;

    /** @var callable */
    private $onRejected;

    /** @var Promise */
    private $aggregate;

    /** @var bool */
    private $mutex;

    /**
     * Configuration hash can include the following key value pairs:
     *
     * - fulfilled: (callable) Invoked when a promise fulfills. The function
     *   is invoked with three arguments: the fulfillment value, the index
     *   position from the iterable list of the promise, and the aggregate
     *   promise that manages all of the promises. The aggregate promise may
     *   be resolved from within the callback to short-circuit the promise.
     * - rejected: (callable) Invoked when a promise is rejected. The
     *   function is invoked with three arguments: the rejection reason, the
     *   index position from the iterable list of the promise, and the
     *   aggregate promise that manages all of the promises. The aggregate
     *   promise may be resolved from within the callback to short-circuit
     *   the promise.
     * - concurrency: (integer) Pass this configuration option to limit the
     *   allowed number of outstanding concurrently executing promises,
     *   creating a capped pool of promises. There is no limit by default.
     *
     * @param mixed    $iterable Promises or values to iterate.
     * @param array    $config   Configuration options
     */
    public function __construct($iterable, array $config = [])
    {
        $this->iterable = iter_for($iterable);

        if (isset($config['concurrency'])) {
            $this->concurrency = $config['concurrency'];
        }

        if (isset($config['fulfilled'])) {
            $this->onFulfilled = $config['fulfilled'];
        }

        if (isset($config['rejected'])) {
            $this->onRejected = $config['rejected'];
        }
    }

    public function promise()
    {
        if ($this->aggregate) {
            return $this->aggregate;
        }

        try {
            $this->createPromise();
            $this->iterable->rewind();
            $this->refillPending();
        } catch (\Exception $e) {
            $this->aggregate->reject($e);
        }

        return $this->aggregate;
    }

    private function createPromise()
    {
        $this->mutex = false;
        $this->aggregate = new Promise(function () {
            reset($this->pending);
            if (empty($this->pending) && !$this->iterable->valid()) {
                $this->aggregate->resolve(null);
                return;
            }

            // Consume a potentially fluctuating list of promises while
            // ensuring that indexes are maintained (precluding array_shift).
            while ($promise = current($this->pending)) {
                next($this->pending);
                $promise->wait();
                if ($this->aggregate->getState() !== PromiseInterface::PENDING) {
                    return;
                }
            }
        });

        // Clear the references when the promise is resolved.
        $clearFn = function () {
            $this->iterable = $this->concurrency = $this->pending = null;
            $this->onFulfilled = $this->onRejected = null;
        };

        $this->aggregate->then($clearFn, $clearFn);
    }

    private function refillPending()
    {
        if (!$this->concurrency) {
            // Add all pending promises.
            while ($this->addPending() && $this->advanceIterator());
            return;
        }

        // Add only up to N pending promises.
        $concurrency = is_callable($this->concurrency)
            ? call_user_func($this->concurrency, count($this->pending))
            : $this->concurrency;
        $concurrency = max($concurrency - count($this->pending), 0);
        // Concurrency may be set to 0 to disallow new promises.
        if (!$concurrency) {
            return;
        }
        // Add the first pending promise.
        $this->addPending();
        // Note this is special handling for concurrency=1 so that we do
        // not advance the iterator after adding the first promise. This
        // helps work around issues with generators that might not have the
        // next value to yield until promise callbacks are called.
        while (--$concurrency
            && $this->advanceIterator()
            && $this->addPending());
    }

    private function addPending()
    {
        if (!$this->iterable || !$this->iterable->valid()) {
            return false;
        }

        $promise = promise_for($this->iterable->current());
        $idx = $this->iterable->key();

        $this->pending[$idx] = $promise->then(
            function ($value) use ($idx) {
                if ($this->onFulfilled) {
                    call_user_func(
                        $this->onFulfilled, $value, $idx, $this->aggregate
                    );
                }
                $this->step($idx);
            },
            function ($reason) use ($idx) {
                if ($this->onRejected) {
                    call_user_func(
                        $this->onRejected, $reason, $idx, $this->aggregate
                    );
                }
                $this->step($idx);
            }
        );

        return true;
    }

    private function advanceIterator()
    {
        // Place a lock on the iterator so that we ensure to not recurse,
        // preventing fatal generator errors.
        if ($this->mutex) {
            return false;
        }

        $this->mutex = true;

        try {
            $this->iterable->next();
            $this->mutex = false;
            return true;
        } catch (\Exception $e) {
            $this->aggregate->reject($e);
            $this->mutex = false;
            return false;
        }
    }

    private function step($idx)
    {
        // If the promise was already resolved, then ignore this step.
        if ($this->aggregate->getState() !== PromiseInterface::PENDING) {
            return;
        }

        unset($this->pending[$idx]);

        // Only refill pending promises if we are not locked, preventing the
        // EachPromise to recursively invoke the provided iterator, which
        // cause a fatal error: "Cannot resume an already running generator"
        if ($this->advanceIterator() && !$this->checkIfFinished()) {
            // Add more pending promises if possible.
            $this->refillPending();
        }
    }

    private function checkIfFinished()
    {
        if (!$this->pending && !$this->iterable->valid()) {
            // Resolve the promise if there's nothing left to do.
            $this->aggregate->resolve(null);
            return true;
        }

        return false;
    }
}
<?php
namespace GuzzleHttp\Promise;

/**
 * A promise that has been fulfilled.
 *
 * Thenning off of this promise will invoke the onFulfilled callback
 * immediately and ignore other callbacks.
 */
class FulfilledPromise implements PromiseInterface
{
    private $value;

    public function __construct($value)
    {
        if (method_exists($value, 'then')) {
            throw new \InvalidArgumentException(
                'You cannot create a FulfilledPromise with a promise.');
        }

        $this->value = $value;
    }

    public function then(
        callable $onFulfilled = null,
        callable $onRejected = null
    ) {
        // Return itself if there is no onFulfilled function.
        if (!$onFulfilled) {
            return $this;
        }

        $queue = queue();
        $p = new Promise([$queue, 'run']);
        $value = $this->value;
        $queue->add(static function () use ($p, $value, $onFulfilled) {
            if ($p->getState() === self::PENDING) {
                try {
                    $p->resolve($onFulfilled($value));
                } catch (\Exception $e) {
                    $p->reject($e);
                }
            }
        });

        return $p;
    }

    public function otherwise(callable $onRejected)
    {
        return $this->then(null, $onRejected);
    }

    public function wait($unwrap = true, $defaultDelivery = null)
    {
        return $unwrap ? $this->value : null;
    }

    public function getState()
    {
        return self::FULFILLED;
    }

    public function resolve($value)
    {
        if ($value !== $this->value) {
            throw new \LogicException("Cannot resolve a fulfilled promise");
        }
    }

    public function reject($reason)
    {
        throw new \LogicException("Cannot reject a fulfilled promise");
    }

    public function cancel()
    {
        // pass
    }
}
<?php
namespace GuzzleHttp\Promise;

/**
 * Get the global task queue used for promise resolution.
 *
 * This task queue MUST be run in an event loop in order for promises to be
 * settled asynchronously. It will be automatically run when synchronously
 * waiting on a promise.
 *
 * <code>
 * while ($eventLoop->isRunning()) {
 *     GuzzleHttp\Promise\queue()->run();
 * }
 * </code>
 *
 * @return TaskQueue
 */
function queue()
{
    static $queue;

    if (!$queue) {
        $queue = new TaskQueue();
    }

    return $queue;
}

/**
 * Adds a function to run in the task queue when it is next `run()` and returns
 * a promise that is fulfilled or rejected with the result.
 *
 * @param callable $task Task function to run.
 *
 * @return PromiseInterface
 */
function task(callable $task)
{
    $queue = queue();
    $promise = new Promise([$queue, 'run']);
    $queue->add(function () use ($task, $promise) {
        try {
            $promise->resolve($task());
        } catch (\Exception $e) {
            $promise->reject($e);
        }
    });

    return $promise;
}

/**
 * Creates a promise for a value if the value is not a promise.
 *
 * @param mixed $value Promise or value.
 *
 * @return PromiseInterface
 */
function promise_for($value)
{
    if ($value instanceof PromiseInterface) {
        return $value;
    }

    // Return a Guzzle promise that shadows the given promise.
    if (method_exists($value, 'then')) {
        $wfn = method_exists($value, 'wait') ? [$value, 'wait'] : null;
        $cfn = method_exists($value, 'cancel') ? [$value, 'cancel'] : null;
        $promise = new Promise($wfn, $cfn);
        $value->then([$promise, 'resolve'], [$promise, 'reject']);
        return $promise;
    }

    return new FulfilledPromise($value);
}

/**
 * Creates a rejected promise for a reason if the reason is not a promise. If
 * the provided reason is a promise, then it is returned as-is.
 *
 * @param mixed $reason Promise or reason.
 *
 * @return PromiseInterface
 */
function rejection_for($reason)
{
    if ($reason instanceof PromiseInterface) {
        return $reason;
    }

    return new RejectedPromise($reason);
}

/**
 * Create an exception for a rejected promise value.
 *
 * @param mixed $reason
 *
 * @return \Exception
 */
function exception_for($reason)
{
    return $reason instanceof \Exception
        ? $reason
        : new RejectionException($reason);
}

/**
 * Returns an iterator for the given value.
 *
 * @param mixed $value
 *
 * @return \Iterator
 */
function iter_for($value)
{
    if ($value instanceof \Iterator) {
        return $value;
    } elseif (is_array($value)) {
        return new \ArrayIterator($value);
    } else {
        return new \ArrayIterator([$value]);
    }
}

/**
 * Synchronously waits on a promise to resolve and returns an inspection state
 * array.
 *
 * Returns a state associative array containing a "state" key mapping to a
 * valid promise state. If the state of the promise is "fulfilled", the array
 * will contain a "value" key mapping to the fulfilled value of the promise. If
 * the promise is rejected, the array will contain a "reason" key mapping to
 * the rejection reason of the promise.
 *
 * @param PromiseInterface $promise Promise or value.
 *
 * @return array
 */
function inspect(PromiseInterface $promise)
{
    try {
        return [
            'state' => PromiseInterface::FULFILLED,
            'value' => $promise->wait()
        ];
    } catch (RejectionException $e) {
        return ['state' => PromiseInterface::REJECTED, 'reason' => $e->getReason()];
    } catch (\Exception $e) {
        return ['state' => PromiseInterface::REJECTED, 'reason' => $e];
    }
}

/**
 * Waits on all of the provided promises, but does not unwrap rejected promises
 * as thrown exception.
 *
 * Returns an array of inspection state arrays.
 *
 * @param PromiseInterface[] $promises Traversable of promises to wait upon.
 *
 * @return array
 * @see GuzzleHttp\Promise\inspect for the inspection state array format.
 */
function inspect_all($promises)
{
    $results = [];
    foreach ($promises as $key => $promise) {
        $results[$key] = inspect($promise);
    }

    return $results;
}

/**
 * Waits on all of the provided promises and returns the fulfilled values.
 *
 * Returns an array that contains the value of each promise (in the same order
 * the promises were provided). An exception is thrown if any of the promises
 * are rejected.
 *
 * @param mixed $promises Iterable of PromiseInterface objects to wait on.
 *
 * @return array
 * @throws \Exception on error
 */
function unwrap($promises)
{
    $results = [];
    foreach ($promises as $key => $promise) {
        $results[$key] = $promise->wait();
    }

    return $results;
}

/**
 * Given an array of promises, return a promise that is fulfilled when all the
 * items in the array are fulfilled.
 *
 * The promise's fulfillment value is an array with fulfillment values at
 * respective positions to the original array. If any promise in the array
 * rejects, the returned promise is rejected with the rejection reason.
 *
 * @param mixed $promises Promises or values.
 *
 * @return Promise
 */
function all($promises)
{
    $results = [];
    return each(
        $promises,
        function ($value, $idx) use (&$results) {
            $results[$idx] = $value;
        },
        function ($reason, $idx, Promise $aggregate) {
            $aggregate->reject($reason);
        }
    )->then(function () use (&$results) {
        ksort($results);
        return $results;
    });
}

/**
 * Initiate a competitive race between multiple promises or values (values will
 * become immediately fulfilled promises).
 *
 * When count amount of promises have been fulfilled, the returned promise is
 * fulfilled with an array that contains the fulfillment values of the winners
 * in order of resolution.
 *
 * This prommise is rejected with a {@see GuzzleHttp\Promise\AggregateException}
 * if the number of fulfilled promises is less than the desired $count.
 *
 * @param int   $count    Total number of promises.
 * @param mixed $promises Promises or values.
 *
 * @return Promise
 */
function some($count, $promises)
{
    $results = [];
    $rejections = [];

    return each(
        $promises,
        function ($value, $idx, PromiseInterface $p) use (&$results, $count) {
            if ($p->getState() !== PromiseInterface::PENDING) {
                return;
            }
            $results[$idx] = $value;
            if (count($results) >= $count) {
                $p->resolve(null);
            }
        },
        function ($reason) use (&$rejections) {
            $rejections[] = $reason;
        }
    )->then(
        function () use (&$results, &$rejections, $count) {
            if (count($results) !== $count) {
                throw new AggregateException(
                    'Not enough promises to fulfill count',
                    $rejections
                );
            }
            ksort($results);
            return array_values($results);
        }
    );
}

/**
 * Like some(), with 1 as count. However, if the promise fulfills, the
 * fulfillment value is not an array of 1 but the value directly.
 *
 * @param mixed $promises Promises or values.
 *
 * @return PromiseInterface
 */
function any($promises)
{
    return some(1, $promises)->then(function ($values) { return $values[0]; });
}

/**
 * Returns a promise that is fulfilled when all of the provided promises have
 * been fulfilled or rejected.
 *
 * The returned promise is fulfilled with an array of inspection state arrays.
 *
 * @param mixed $promises Promises or values.
 *
 * @return Promise
 * @see GuzzleHttp\Promise\inspect for the inspection state array format.
 */
function settle($promises)
{
    $results = [];

    return each(
        $promises,
        function ($value, $idx) use (&$results) {
            $results[$idx] = ['state' => PromiseInterface::FULFILLED, 'value' => $value];
        },
        function ($reason, $idx) use (&$results) {
            $results[$idx] = ['state' => PromiseInterface::REJECTED, 'reason' => $reason];
        }
    )->then(function () use (&$results) {
        ksort($results);
        return $results;
    });
}

/**
 * Given an iterator that yields promises or values, returns a promise that is
 * fulfilled with a null value when the iterator has been consumed or the
 * aggregate promise has been fulfilled or rejected.
 *
 * $onFulfilled is a function that accepts the fulfilled value, iterator
 * index, and the aggregate promise. The callback can invoke any necessary side
 * effects and choose to resolve or reject the aggregate promise if needed.
 *
 * $onRejected is a function that accepts the rejection reason, iterator
 * index, and the aggregate promise. The callback can invoke any necessary side
 * effects and choose to resolve or reject the aggregate promise if needed.
 *
 * @param mixed    $iterable    Iterator or array to iterate over.
 * @param callable $onFulfilled
 * @param callable $onRejected
 *
 * @return Promise
 */
function each(
    $iterable,
    callable $onFulfilled = null,
    callable $onRejected = null
) {
    return (new EachPromise($iterable, [
        'fulfilled' => $onFulfilled,
        'rejected'  => $onRejected
    ]))->promise();
}

/**
 * Like each, but only allows a certain number of outstanding promises at any
 * given time.
 *
 * $concurrency may be an integer or a function that accepts the number of
 * pending promises and returns a numeric concurrency limit value to allow for
 * dynamic a concurrency size.
 *
 * @param mixed        $iterable
 * @param int|callable $concurrency
 * @param callable     $onFulfilled
 * @param callable     $onRejected
 *
 * @return mixed
 */
function each_limit(
    $iterable,
    $concurrency,
    callable $onFulfilled = null,
    callable $onRejected = null
) {
    return (new EachPromise($iterable, [
        'fulfilled'   => $onFulfilled,
        'rejected'    => $onRejected,
        'concurrency' => $concurrency
    ]))->promise();
}

/**
 * Like each_limit, but ensures that no promise in the given $iterable argument
 * is rejected. If any promise is rejected, then the aggregate promise is
 * rejected with the encountered rejection.
 *
 * @param mixed        $iterable
 * @param int|callable $concurrency
 * @param callable     $onFulfilled
 *
 * @return mixed
 */
function each_limit_all(
    $iterable,
    $concurrency,
    callable $onFulfilled = null
) {
    return each_limit(
        $iterable,
        $concurrency,
        $onFulfilled,
        function ($reason, $idx, PromiseInterface $aggregate) {
            $aggregate->reject($reason);
        }
    );
}

/**
 * Returns true if a promise is fulfilled.
 *
 * @param PromiseInterface $promise
 *
 * @return bool
 */
function is_fulfilled(PromiseInterface $promise)
{
    return $promise->getState() === PromiseInterface::FULFILLED;
}

/**
 * Returns true if a promise is rejected.
 *
 * @param PromiseInterface $promise
 *
 * @return bool
 */
function is_rejected(PromiseInterface $promise)
{
    return $promise->getState() === PromiseInterface::REJECTED;
}

/**
 * Returns true if a promise is fulfilled or rejected.
 *
 * @param PromiseInterface $promise
 *
 * @return bool
 */
function is_settled(PromiseInterface $promise)
{
    return $promise->getState() !== PromiseInterface::PENDING;
}

/**
 * Creates a promise that is resolved using a generator that yields values or
 * promises (somewhat similar to C#'s async keyword).
 *
 * When called, the coroutine function will start an instance of the generator
 * and returns a promise that is fulfilled with its final yielded value.
 *
 * Control is returned back to the generator when the yielded promise settles.
 * This can lead to less verbose code when doing lots of sequential async calls
 * with minimal processing in between.
 *
 *     use GuzzleHttp\Promise;
 *
 *     function createPromise($value) {
 *         return new Promise\FulfilledPromise($value);
 *     }
 *
 *     $promise = Promise\coroutine(function () {
 *         $value = (yield createPromise('a'));
 *         try {
 *             $value = (yield createPromise($value . 'b'));
 *         } catch (\Exception $e) {
 *             // The promise was rejected.
 *         }
 *         yield $value . 'c';
 *     });
 *
 *     // Outputs "abc"
 *     $promise->then(function ($v) { echo $v; });
 *
 * @param callable $generatorFn Generator function to wrap into a promise.
 *
 * @return Promise
 * @link https://github.com/petkaantonov/bluebird/blob/master/API.md#generators inspiration
 */
function coroutine(callable $generatorFn)
{
    $generator = $generatorFn();
    return __next_coroutine($generator->current(), $generator)->then();
}

/** @internal */
function __next_coroutine($yielded, \Generator $generator)
{
    return promise_for($yielded)->then(
        function ($value) use ($generator) {
            $nextYield = $generator->send($value);
            return $generator->valid()
                ? __next_coroutine($nextYield, $generator)
                : $value;
        },
        function ($reason) use ($generator) {
            $nextYield = $generator->throw(exception_for($reason));
            // The throw was caught, so keep iterating on the coroutine
            return __next_coroutine($nextYield, $generator);
        }
    );
}
<?php

// Don't redefine the functions if included multiple times.
if (!function_exists('GuzzleHttp\Promise\promise_for')) {
    require __DIR__ . '/functions.php';
}
<?php
namespace GuzzleHttp\Promise;

/**
 * Promises/A+ implementation that avoids recursion when possible.
 *
 * @link https://promisesaplus.com/
 */
class Promise implements PromiseInterface
{
    private $state = self::PENDING;
    private $result;
    private $cancelFn;
    private $waitFn;
    private $waitList;
    private $handlers = [];

    /**
     * @param callable $waitFn   Fn that when invoked resolves the promise.
     * @param callable $cancelFn Fn that when invoked cancels the promise.
     */
    public function __construct(
        callable $waitFn = null,
        callable $cancelFn = null
    ) {
        $this->waitFn = $waitFn;
        $this->cancelFn = $cancelFn;
    }

    public function then(
        callable $onFulfilled = null,
        callable $onRejected = null
    ) {
        if ($this->state === self::PENDING) {
            $p = new Promise(null, [$this, 'cancel']);
            $this->handlers[] = [$p, $onFulfilled, $onRejected];
            $p->waitList = $this->waitList;
            $p->waitList[] = $this;
            return $p;
        }

        // Return a fulfilled promise and immediately invoke any callbacks.
        if ($this->state === self::FULFILLED) {
            return $onFulfilled
                ? promise_for($this->result)->then($onFulfilled)
                : promise_for($this->result);
        }

        // It's either cancelled or rejected, so return a rejected promise
        // and immediately invoke any callbacks.
        $rejection = rejection_for($this->result);
        return $onRejected ? $rejection->then(null, $onRejected) : $rejection;
    }

    public function otherwise(callable $onRejected)
    {
        return $this->then(null, $onRejected);
    }

    public function wait($unwrap = true)
    {
        $this->waitIfPending();

        $inner = $this->result instanceof PromiseInterface
            ? $this->result->wait($unwrap)
            : $this->result;

        if ($unwrap) {
            if ($this->result instanceof PromiseInterface
                || $this->state === self::FULFILLED
            ) {
                return $inner;
            } else {
                // It's rejected so "unwrap" and throw an exception.
                throw exception_for($inner);
            }
        }
    }

    public function getState()
    {
        return $this->state;
    }

    public function cancel()
    {
        if ($this->state !== self::PENDING) {
            return;
        }

        $this->waitFn = $this->waitList = null;

        if ($this->cancelFn) {
            $fn = $this->cancelFn;
            $this->cancelFn = null;
            try {
                $fn();
            } catch (\Exception $e) {
                $this->reject($e);
            }
        }

        // Reject the promise only if it wasn't rejected in a then callback.
        if ($this->state === self::PENDING) {
            $this->reject(new CancellationException('Promise has been cancelled'));
        }
    }

    public function resolve($value)
    {
        $this->settle(self::FULFILLED, $value);
    }

    public function reject($reason)
    {
        $this->settle(self::REJECTED, $reason);
    }

    private function settle($state, $value)
    {
        if ($this->state !== self::PENDING) {
            // Ignore calls with the same resolution.
            if ($state === $this->state && $value === $this->result) {
                return;
            }
            throw $this->state === $state
                ? new \LogicException("The promise is already {$state}.")
                : new \LogicException("Cannot change a {$this->state} promise to {$state}");
        }

        if ($value === $this) {
            throw new \LogicException('Cannot fulfill or reject a promise with itself');
        }

        // Clear out the state of the promise but stash the handlers.
        $this->state = $state;
        $this->result = $value;
        $handlers = $this->handlers;
        $this->handlers = null;
        $this->waitList = $this->waitFn = null;
        $this->cancelFn = null;

        if (!$handlers) {
            return;
        }

        // If the value was not a settled promise or a thenable, then resolve
        // it in the task queue using the correct ID.
        if (!method_exists($value, 'then')) {
            $id = $state === self::FULFILLED ? 1 : 2;
            // It's a success, so resolve the handlers in the queue.
            queue()->add(static function () use ($id, $value, $handlers) {
                foreach ($handlers as $handler) {
                    self::callHandler($id, $value, $handler);
                }
            });
        } elseif ($value instanceof Promise
            && $value->getState() === self::PENDING
        ) {
            // We can just merge our handlers onto the next promise.
            $value->handlers = array_merge($value->handlers, $handlers);
        } else {
            // Resolve the handlers when the forwarded promise is resolved.
            $value->then(
                static function ($value) use ($handlers) {
                    foreach ($handlers as $handler) {
                        self::callHandler(1, $value, $handler);
                    }
                },
                static function ($reason) use ($handlers) {
                    foreach ($handlers as $handler) {
                        self::callHandler(2, $reason, $handler);
                    }
                }
            );
        }
    }

    /**
     * Call a stack of handlers using a specific callback index and value.
     *
     * @param int   $index   1 (resolve) or 2 (reject).
     * @param mixed $value   Value to pass to the callback.
     * @param array $handler Array of handler data (promise and callbacks).
     *
     * @return array Returns the next group to resolve.
     */
    private static function callHandler($index, $value, array $handler)
    {
        /** @var PromiseInterface $promise */
        $promise = $handler[0];

        // The promise may have been cancelled or resolved before placing
        // this thunk in the queue.
        if ($promise->getState() !== self::PENDING) {
            return;
        }

        try {
            if (isset($handler[$index])) {
                $promise->resolve($handler[$index]($value));
            } elseif ($index === 1) {
                // Forward resolution values as-is.
                $promise->resolve($value);
            } else {
                // Forward rejections down the chain.
                $promise->reject($value);
            }
        } catch (\Exception $reason) {
            $promise->reject($reason);
        }
    }

    private function waitIfPending()
    {
        if ($this->state !== self::PENDING) {
            return;
        } elseif ($this->waitFn) {
            $this->invokeWaitFn();
        } elseif ($this->waitList) {
            $this->invokeWaitList();
        } else {
            // If there's not wait function, then reject the promise.
            $this->reject('Cannot wait on a promise that has '
                . 'no internal wait function. You must provide a wait '
                . 'function when constructing the promise to be able to '
                . 'wait on a promise.');
        }

        queue()->run();

        if ($this->state === self::PENDING) {
            $this->reject('Invoking the wait callback did not resolve the promise');
        }
    }

    private function invokeWaitFn()
    {
        try {
            $wfn = $this->waitFn;
            $this->waitFn = null;
            $wfn(true);
        } catch (\Exception $reason) {
            if ($this->state === self::PENDING) {
                // The promise has not been resolved yet, so reject the promise
                // with the exception.
                $this->reject($reason);
            } else {
                // The promise was already resolved, so there's a problem in
                // the application.
                throw $reason;
            }
        }
    }

    private function invokeWaitList()
    {
        $waitList = $this->waitList;
        $this->waitList = null;

        foreach ($waitList as $result) {
            $result->waitIfPending();
            while ($result->result instanceof Promise) {
                $result = $result->result;
                $result->waitIfPending();
            }
        }
    }
}
<?php
namespace GuzzleHttp\Promise;

/**
 * A promise represents the eventual result of an asynchronous operation.
 *
 * The primary way of interacting with a promise is through its then method,
 * which registers callbacks to receive either a promise’s eventual value or
 * the reason why the promise cannot be fulfilled.
 *
 * @link https://promisesaplus.com/
 */
interface PromiseInterface
{
    const PENDING = 'pending';
    const FULFILLED = 'fulfilled';
    const REJECTED = 'rejected';

    /**
     * Appends fulfillment and rejection handlers to the promise, and returns
     * a new promise resolving to the return value of the called handler.
     *
     * @param callable $onFulfilled Invoked when the promise fulfills.
     * @param callable $onRejected  Invoked when the promise is rejected.
     *
     * @return PromiseInterface
     */
    public function then(
        callable $onFulfilled = null,
        callable $onRejected = null
    );

    /**
     * Appends a rejection handler callback to the promise, and returns a new
     * promise resolving to the return value of the callback if it is called,
     * or to its original fulfillment value if the promise is instead
     * fulfilled.
     *
     * @param callable $onRejected Invoked when the promise is rejected.
     *
     * @return PromiseInterface
     */
    public function otherwise(callable $onRejected);

    /**
     * Get the state of the promise ("pending", "rejected", or "fulfilled").
     *
     * The three states can be checked against the constants defined on
     * PromiseInterface: PENDING, FULFILLED, and REJECTED.
     *
     * @return string
     */
    public function getState();

    /**
     * Resolve the promise with the given value.
     *
     * @param mixed $value
     * @throws \RuntimeException if the promise is already resolved.
     */
    public function resolve($value);

    /**
     * Reject the promise with the given reason.
     *
     * @param mixed $reason
     * @throws \RuntimeException if the promise is already resolved.
     */
    public function reject($reason);

    /**
     * Cancels the promise if possible.
     *
     * @link https://github.com/promises-aplus/cancellation-spec/issues/7
     */
    public function cancel();

    /**
     * Waits until the promise completes if possible.
     *
     * Pass $unwrap as true to unwrap the result of the promise, either
     * returning the resolved value or throwing the rejected exception.
     *
     * If the promise cannot be waited on, then the promise will be rejected.
     *
     * @param bool $unwrap
     *
     * @return mixed
     * @throws \LogicException if the promise has no wait function or if the
     *                         promise does not settle after waiting.
     */
    public function wait($unwrap = true);
}
<?php
namespace GuzzleHttp\Promise;

/**
 * Interface used with classes that return a promise.
 */
interface PromisorInterface
{
    /**
     * Returns a promise.
     *
     * @return PromiseInterface
     */
    public function promise();
}
<?php
namespace GuzzleHttp\Promise;

/**
 * A promise that has been rejected.
 *
 * Thenning off of this promise will invoke the onRejected callback
 * immediately and ignore other callbacks.
 */
class RejectedPromise implements PromiseInterface
{
    private $reason;

    public function __construct($reason)
    {
        if (method_exists($reason, 'then')) {
            throw new \InvalidArgumentException(
                'You cannot create a RejectedPromise with a promise.');
        }

        $this->reason = $reason;
    }

    public function then(
        callable $onFulfilled = null,
        callable $onRejected = null
    ) {
        // If there's no onRejected callback then just return self.
        if (!$onRejected) {
            return $this;
        }

        $queue = queue();
        $reason = $this->reason;
        $p = new Promise([$queue, 'run']);
        $queue->add(static function () use ($p, $reason, $onRejected) {
            if ($p->getState() === self::PENDING) {
                try {
                    // Return a resolved promise if onRejected does not throw.
                    $p->resolve($onRejected($reason));
                } catch (\Exception $e) {
                    // onRejected threw, so return a rejected promise.
                    $p->reject($e);
                }
            }
        });

        return $p;
    }

    public function otherwise(callable $onRejected)
    {
        return $this->then(null, $onRejected);
    }

    public function wait($unwrap = true, $defaultDelivery = null)
    {
        if ($unwrap) {
            throw exception_for($this->reason);
        }
    }

    public function getState()
    {
        return self::REJECTED;
    }

    public function resolve($value)
    {
        throw new \LogicException("Cannot resolve a rejected promise");
    }

    public function reject($reason)
    {
        if ($reason !== $this->reason) {
            throw new \LogicException("Cannot reject a rejected promise");
        }
    }

    public function cancel()
    {
        // pass
    }
}
<?php
namespace GuzzleHttp\Promise;

/**
 * A special exception that is thrown when waiting on a rejected promise.
 *
 * The reason value is available via the getReason() method.
 */
class RejectionException extends \RuntimeException
{
    /** @var mixed Rejection reason. */
    private $reason;

    /**
     * @param mixed $reason       Rejection reason.
     * @param string $description Optional description
     */
    public function __construct($reason, $description = null)
    {
        $this->reason = $reason;

        $message = 'The promise was rejected';

        if ($description) {
            $message .= ' with reason: ' . $description;
        } elseif (is_string($reason)
            || (is_object($reason) && method_exists($reason, '__toString'))
        ) {
            $message .= ' with reason: ' . $this->reason;
        } elseif ($reason instanceof \JsonSerializable) {
            $message .= ' with reason: '
                . json_encode($this->reason, JSON_PRETTY_PRINT);
        }

        parent::__construct($message);
    }

    /**
     * Returns the rejection reason.
     *
     * @return mixed
     */
    public function getReason()
    {
        return $this->reason;
    }
}
<?php
namespace GuzzleHttp\Promise;

/**
 * A task queue that executes tasks in a FIFO order.
 *
 * This task queue class is used to settle promises asynchronously and
 * maintains a constant stack size. You can use the task queue asynchronously
 * by calling the `run()` function of the global task queue in an event loop.
 *
 *     GuzzleHttp\Promise\queue()->run();
 */
class TaskQueue
{
    private $enableShutdown = true;
    private $queue = [];

    public function __construct($withShutdown = true)
    {
        if ($withShutdown) {
            register_shutdown_function(function () {
                if ($this->enableShutdown) {
                    // Only run the tasks if an E_ERROR didn't occur.
                    $err = error_get_last();
                    if (!$err || ($err['type'] ^ E_ERROR)) {
                        $this->run();
                    }
                }
            });
        }
    }

    /**
     * Returns true if the queue is empty.
     *
     * @return bool
     */
    public function isEmpty()
    {
        return !$this->queue;
    }

    /**
     * Adds a task to the queue that will be executed the next time run is
     * called.
     *
     * @param callable $task
     */
    public function add(callable $task)
    {
        $this->queue[] = $task;
    }

    /**
     * Execute all of the pending task in the queue.
     */
    public function run()
    {
        /** @var callable $task */
        while ($task = array_shift($this->queue)) {
            $task();
        }
    }

    /**
     * The task queue will be run and exhausted by default when the process
     * exits IFF the exit is not the result of a PHP E_ERROR error.
     *
     * You can disable running the automatic shutdown of the queue by calling
     * this function. If you disable the task queue shutdown process, then you
     * MUST either run the task queue (as a result of running your event loop
     * or manually using the run() method) or wait on each outstanding promise.
     *
     * Note: This shutdown will occur before any destructors are triggered.
     */
    public function disableShutdown()
    {
        $this->enableShutdown = false;
    }
}
<?php
namespace GuzzleHttp\Promise\Tests;

use GuzzleHttp\Promise\AggregateException;

class AggregateExceptionTest extends \PHPUnit_Framework_TestCase
{
    public function testHasReason()
    {
        $e = new AggregateException('foo', ['baz', 'bar']);
        $this->assertContains('foo', $e->getMessage());
        $this->assertEquals(['baz', 'bar'], $e->getReason());
    }
}
<?php
require __DIR__ . '/../vendor/autoload.php';
require __DIR__ . '/Thennable.php';
require __DIR__ . '/NotPromiseInstance.php';
<?php
namespace GuzzleHttp\Promise\Tests;

use GuzzleHttp\Promise\RejectedPromise;
use GuzzleHttp\Promise\FulfilledPromise;
use GuzzleHttp\Promise\Promise;
use GuzzleHttp\Promise\PromiseInterface;
use GuzzleHttp\Promise\EachPromise;
use GuzzleHttp\Promise as P;

/**
 * @covers GuzzleHttp\Promise\EachPromise
 */
class EachPromiseTest extends \PHPUnit_Framework_TestCase
{
    public function testReturnsSameInstance()
    {
        $each = new EachPromise([], ['concurrency' => 100]);
        $this->assertSame($each->promise(), $each->promise());
    }

    public function testInvokesAllPromises()
    {
        $promises = [new Promise(), new Promise(), new Promise()];
        $called = [];
        $each = new EachPromise($promises, [
            'fulfilled' => function ($value) use (&$called) {
                $called[] = $value;
            }
        ]);
        $p = $each->promise();
        $promises[0]->resolve('a');
        $promises[1]->resolve('c');
        $promises[2]->resolve('b');
        P\queue()->run();
        $this->assertEquals(['a', 'c', 'b'], $called);
        $this->assertEquals(PromiseInterface::FULFILLED, $p->getState());
    }

    public function testIsWaitable()
    {
        $a = $this->createSelfResolvingPromise('a');
        $b = $this->createSelfResolvingPromise('b');
        $called = [];
        $each = new EachPromise([$a, $b], [
            'fulfilled' => function ($value) use (&$called) { $called[] = $value; }
        ]);
        $p = $each->promise();
        $this->assertNull($p->wait());
        $this->assertEquals(PromiseInterface::FULFILLED, $p->getState());
        $this->assertEquals(['a', 'b'], $called);
    }

    public function testCanResolveBeforeConsumingAll()
    {
        $called = 0;
        $a = $this->createSelfResolvingPromise('a');
        $b = new Promise(function () { $this->fail(); });
        $each = new EachPromise([$a, $b], [
            'fulfilled' => function ($value, $idx, Promise $aggregate) use (&$called) {
                $this->assertSame($idx, 0);
                $this->assertEquals('a', $value);
                $aggregate->resolve(null);
                $called++;
            },
            'rejected' => function (\Exception $reason) {
                $this->fail($reason->getMessage());
            }
        ]);
        $p = $each->promise();
        $p->wait();
        $this->assertNull($p->wait());
        $this->assertEquals(1, $called);
        $this->assertEquals(PromiseInterface::FULFILLED, $a->getState());
        $this->assertEquals(PromiseInterface::PENDING, $b->getState());
        // Resolving $b has no effect on the aggregate promise.
        $b->resolve('foo');
        $this->assertEquals(1, $called);
    }

    public function testLimitsPendingPromises()
    {
        $pending = [new Promise(), new Promise(), new Promise(), new Promise()];
        $promises = new \ArrayIterator($pending);
        $each = new EachPromise($promises, ['concurrency' => 2]);
        $p = $each->promise();
        $this->assertCount(2, $this->readAttribute($each, 'pending'));
        $pending[0]->resolve('a');
        $this->assertCount(2, $this->readAttribute($each, 'pending'));
        $this->assertTrue($promises->valid());
        $pending[1]->resolve('b');
        P\queue()->run();
        $this->assertCount(2, $this->readAttribute($each, 'pending'));
        $this->assertTrue($promises->valid());
        $promises[2]->resolve('c');
        P\queue()->run();
        $this->assertCount(1, $this->readAttribute($each, 'pending'));
        $this->assertEquals(PromiseInterface::PENDING, $p->getState());
        $promises[3]->resolve('d');
        P\queue()->run();
        $this->assertNull($this->readAttribute($each, 'pending'));
        $this->assertEquals(PromiseInterface::FULFILLED, $p->getState());
        $this->assertFalse($promises->valid());
    }

    public function testDynamicallyLimitsPendingPromises()
    {
        $calls = [];
        $pendingFn = function ($count) use (&$calls) {
            $calls[] = $count;
            return 2;
        };
        $pending = [new Promise(), new Promise(), new Promise(), new Promise()];
        $promises = new \ArrayIterator($pending);
        $each = new EachPromise($promises, ['concurrency' => $pendingFn]);
        $p = $each->promise();
        $this->assertCount(2, $this->readAttribute($each, 'pending'));
        $pending[0]->resolve('a');
        $this->assertCount(2, $this->readAttribute($each, 'pending'));
        $this->assertTrue($promises->valid());
        $pending[1]->resolve('b');
        $this->assertCount(2, $this->readAttribute($each, 'pending'));
        P\queue()->run();
        $this->assertTrue($promises->valid());
        $promises[2]->resolve('c');
        P\queue()->run();
        $this->assertCount(1, $this->readAttribute($each, 'pending'));
        $this->assertEquals(PromiseInterface::PENDING, $p->getState());
        $promises[3]->resolve('d');
        P\queue()->run();
        $this->assertNull($this->readAttribute($each, 'pending'));
        $this->assertEquals(PromiseInterface::FULFILLED, $p->getState());
        $this->assertEquals([0, 1, 1, 1], $calls);
        $this->assertFalse($promises->valid());
    }

    public function testClearsReferencesWhenResolved()
    {
        $called = false;
        $a = new Promise(function () use (&$a, &$called) {
            $a->resolve('a');
            $called = true;
        });
        $each = new EachPromise([$a], [
            'concurrency'       => function () { return 1; },
            'fulfilled' => function () {},
            'rejected'  => function () {}
        ]);
        $each->promise()->wait();
        $this->assertNull($this->readAttribute($each, 'onFulfilled'));
        $this->assertNull($this->readAttribute($each, 'onRejected'));
        $this->assertNull($this->readAttribute($each, 'iterable'));
        $this->assertNull($this->readAttribute($each, 'pending'));
        $this->assertNull($this->readAttribute($each, 'concurrency'));
        $this->assertTrue($called);
    }

    public function testCanBeCancelled()
    {
        $this->markTestIncomplete();
    }

    public function testFulfillsImmediatelyWhenGivenAnEmptyIterator()
    {
        $each = new EachPromise(new \ArrayIterator([]));
        $result = $each->promise()->wait();
    }

    public function testDoesNotBlowStackWithFulfilledPromises()
    {
        $pending = [];
        for ($i = 0; $i < 100; $i++) {
            $pending[] = new FulfilledPromise($i);
        }
        $values = [];
        $each = new EachPromise($pending, [
            'fulfilled' => function ($value) use (&$values) {
                $values[] = $value;
            }
        ]);
        $called = false;
        $each->promise()->then(function () use (&$called) {
            $called = true;
        });
        $this->assertFalse($called);
        P\queue()->run();
        $this->assertTrue($called);
        $this->assertEquals(range(0, 99), $values);
    }

    public function testDoesNotBlowStackWithRejectedPromises()
    {
        $pending = [];
        for ($i = 0; $i < 100; $i++) {
            $pending[] = new RejectedPromise($i);
        }
        $values = [];
        $each = new EachPromise($pending, [
            'rejected' => function ($value) use (&$values) {
                $values[] = $value;
            }
        ]);
        $called = false;
        $each->promise()->then(
            function () use (&$called) { $called = true; },
            function () { $this->fail('Should not have rejected.'); }
        );
        $this->assertFalse($called);
        P\queue()->run();
        $this->assertTrue($called);
        $this->assertEquals(range(0, 99), $values);
    }

    public function testReturnsPromiseForWhatever()
    {
        $called = [];
        $arr = ['a', 'b'];
        $each = new EachPromise($arr, [
            'fulfilled' => function ($v) use (&$called) { $called[] = $v; }
        ]);
        $p = $each->promise();
        $this->assertNull($p->wait());
        $this->assertEquals(['a', 'b'], $called);
    }

    public function testRejectsAggregateWhenNextThrows()
    {
        $iter = function () {
            yield 'a';
            throw new \Exception('Failure');
        };
        $each = new EachPromise($iter());
        $p = $each->promise();
        $e = null;
        $received = null;
        $p->then(null, function ($reason) use (&$e) { $e = $reason; });
        P\queue()->run();
        $this->assertInstanceOf('Exception', $e);
        $this->assertEquals('Failure', $e->getMessage());
    }

    public function testDoesNotCallNextOnIteratorUntilNeededWhenWaiting()
    {
        $results = [];
        $values = [10];
        $remaining = 9;
        $iter = function () use (&$values) {
            while ($value = array_pop($values)) {
                yield $value;
            }
        };
        $each = new EachPromise($iter(), [
            'concurrency' => 1,
            'fulfilled' => function ($r) use (&$results, &$values, &$remaining) {
                $results[] = $r;
                if ($remaining > 0) {
                    $values[] = $remaining--;
                }
            }
        ]);
        $each->promise()->wait();
        $this->assertEquals(range(10, 1), $results);
    }

    public function testDoesNotCallNextOnIteratorUntilNeededWhenAsync()
    {
        $firstPromise = new Promise();
        $pending = [$firstPromise];
        $values = [$firstPromise];
        $results = [];
        $remaining = 9;
        $iter = function () use (&$values) {
            while ($value = array_pop($values)) {
                yield $value;
            }
        };
        $each = new EachPromise($iter(), [
            'concurrency' => 1,
            'fulfilled' => function ($r) use (&$results, &$values, &$remaining, &$pending) {
                $results[] = $r;
                if ($remaining-- > 0) {
                    $pending[] = $values[] = new Promise();
                }
            }
        ]);
        $i = 0;
        $each->promise();
        while ($promise = array_pop($pending)) {
            $promise->resolve($i++);
            P\queue()->run();
        }
        $this->assertEquals(range(0, 9), $results);
    }

    private function createSelfResolvingPromise($value)
    {
        $p = new Promise(function () use (&$p, $value) {
            $p->resolve($value);
        });

        return $p;
    }

    public function testMutexPreventsGeneratorRecursion()
    {
        $results = $promises = [];
        for ($i = 0; $i < 20; $i++) {
            $p = $this->createSelfResolvingPromise($i);
            $pending[] = $p;
            $promises[] = $p;
        }

        $iter = function () use (&$promises, &$pending) {
            foreach ($promises as $promise) {
                // Resolve a promises, which will trigger the then() function,
                // which would cause the EachPromise to try to add more
                // promises to the queue. Without a lock, this would trigger
                // a "Cannot resume an already running generator" fatal error.
                if ($p = array_pop($pending)) {
                    $p->wait();
                }
                yield $promise;
            }
        };

        $each = new EachPromise($iter(), [
            'concurrency' => 5,
            'fulfilled' => function ($r) use (&$results, &$pending) {
                $results[] = $r;
            }
        ]);

        $each->promise()->wait();
        $this->assertCount(20, $results);
    }
}
<?php
namespace GuzzleHttp\Tests\Promise;

use GuzzleHttp\Promise\Promise;
use GuzzleHttp\Promise\FulfilledPromise;

/**
 * @covers GuzzleHttp\Promise\FulfilledPromise
 */
class FulfilledPromiseTest extends \PHPUnit_Framework_TestCase
{
    public function testReturnsValueWhenWaitedUpon()
    {
        $p = new FulfilledPromise('foo');
        $this->assertEquals('fulfilled', $p->getState());
        $this->assertEquals('foo', $p->wait(true));
    }

    public function testCannotCancel()
    {
        $p = new FulfilledPromise('foo');
        $this->assertEquals('fulfilled', $p->getState());
        $p->cancel();
        $this->assertEquals('foo', $p->wait());
    }

    /**
     * @expectedException \LogicException
     * @exepctedExceptionMessage Cannot resolve a fulfilled promise
     */
    public function testCannotResolve()
    {
        $p = new FulfilledPromise('foo');
        $p->resolve('bar');
    }

    /**
     * @expectedException \LogicException
     * @exepctedExceptionMessage Cannot reject a fulfilled promise
     */
    public function testCannotReject()
    {
        $p = new FulfilledPromise('foo');
        $p->reject('bar');
    }

    public function testCanResolveWithSameValue()
    {
        $p = new FulfilledPromise('foo');
        $p->resolve('foo');
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testCannotResolveWithPromise()
    {
        new FulfilledPromise(new Promise());
    }

    public function testReturnsSelfWhenNoOnFulfilled()
    {
        $p = new FulfilledPromise('a');
        $this->assertSame($p, $p->then());
    }

    public function testAsynchronouslyInvokesOnFulfilled()
    {
        $p = new FulfilledPromise('a');
        $r = null;
        $f = function ($d) use (&$r) { $r = $d; };
        $p2 = $p->then($f);
        $this->assertNotSame($p, $p2);
        $this->assertNull($r);
        \GuzzleHttp\Promise\queue()->run();
        $this->assertEquals('a', $r);
    }

    public function testReturnsNewRejectedWhenOnFulfilledFails()
    {
        $p = new FulfilledPromise('a');
        $f = function () { throw new \Exception('b'); };
        $p2 = $p->then($f);
        $this->assertNotSame($p, $p2);
        try {
            $p2->wait();
            $this->fail();
        } catch (\Exception $e) {
            $this->assertEquals('b', $e->getMessage());
        }
    }

    public function testOtherwiseIsSugarForRejections()
    {
        $c = null;
        $p = new FulfilledPromise('foo');
        $p->otherwise(function ($v) use (&$c) { $c = $v; });
        $this->assertNull($c);
    }

    public function testDoesNotTryToFulfillTwiceDuringTrampoline()
    {
        $fp = new FulfilledPromise('a');
        $t1 = $fp->then(function ($v) { return $v . ' b'; });
        $t1->resolve('why!');
        $this->assertEquals('why!', $t1->wait());
    }
}
<?php
namespace GuzzleHttp\Promise\Tests;

use GuzzleHttp\Promise as P;
use GuzzleHttp\Promise\FulfilledPromise;
use GuzzleHttp\Promise\Promise;
use GuzzleHttp\Promise\RejectedPromise;

class FunctionsTest extends \PHPUnit_Framework_TestCase
{
    public function testCreatesPromiseForValue()
    {
        $p = \GuzzleHttp\Promise\promise_for('foo');
        $this->assertInstanceOf('GuzzleHttp\Promise\FulfilledPromise', $p);
    }

    public function testReturnsPromiseForPromise()
    {
        $p = new Promise();
        $this->assertSame($p, \GuzzleHttp\Promise\promise_for($p));
    }

    public function testReturnsPromiseForThennable()
    {
        $p = new Thennable();
        $wrapped = \GuzzleHttp\Promise\promise_for($p);
        $this->assertNotSame($p, $wrapped);
        $this->assertInstanceOf('GuzzleHttp\Promise\PromiseInterface', $wrapped);
        $p->resolve('foo');
        P\queue()->run();
        $this->assertEquals('foo', $wrapped->wait());
    }

    public function testReturnsRejection()
    {
        $p = \GuzzleHttp\Promise\rejection_for('fail');
        $this->assertInstanceOf('GuzzleHttp\Promise\RejectedPromise', $p);
        $this->assertEquals('fail', $this->readAttribute($p, 'reason'));
    }

    public function testReturnsPromisesAsIsInRejectionFor()
    {
        $a = new Promise();
        $b = \GuzzleHttp\Promise\rejection_for($a);
        $this->assertSame($a, $b);
    }

    public function testWaitsOnAllPromisesIntoArray()
    {
        $e = new \Exception();
        $a = new Promise(function () use (&$a) { $a->resolve('a'); });
        $b = new Promise(function () use (&$b) { $b->reject('b'); });
        $c = new Promise(function () use (&$c, $e) { $c->reject($e); });
        $results = \GuzzleHttp\Promise\inspect_all([$a, $b, $c]);
        $this->assertEquals([
            ['state' => 'fulfilled', 'value' => 'a'],
            ['state' => 'rejected', 'reason' => 'b'],
            ['state' => 'rejected', 'reason' => $e]
        ], $results);
    }

    /**
     * @expectedException \GuzzleHttp\Promise\RejectionException
     */
    public function testUnwrapsPromisesWithNoDefaultAndFailure()
    {
        $promises = [new FulfilledPromise('a'), new Promise()];
        \GuzzleHttp\Promise\unwrap($promises);
    }

    public function testUnwrapsPromisesWithNoDefault()
    {
        $promises = [new FulfilledPromise('a')];
        $this->assertEquals(['a'], \GuzzleHttp\Promise\unwrap($promises));
    }

    public function testUnwrapsPromisesWithKeys()
    {
        $promises = [
            'foo' => new FulfilledPromise('a'),
            'bar' => new FulfilledPromise('b'),
        ];
        $this->assertEquals([
            'foo' => 'a',
            'bar' => 'b'
        ], \GuzzleHttp\Promise\unwrap($promises));
    }

    public function testAllAggregatesSortedArray()
    {
        $a = new Promise();
        $b = new Promise();
        $c = new Promise();
        $d = \GuzzleHttp\Promise\all([$a, $b, $c]);
        $b->resolve('b');
        $a->resolve('a');
        $c->resolve('c');
        $d->then(
            function ($value) use (&$result) { $result = $value; },
            function ($reason) use (&$result) { $result = $reason; }
        );
        P\queue()->run();
        $this->assertEquals(['a', 'b', 'c'], $result);
    }

    public function testAllThrowsWhenAnyRejected()
    {
        $a = new Promise();
        $b = new Promise();
        $c = new Promise();
        $d = \GuzzleHttp\Promise\all([$a, $b, $c]);
        $b->resolve('b');
        $a->reject('fail');
        $c->resolve('c');
        $d->then(
            function ($value) use (&$result) { $result = $value; },
            function ($reason) use (&$result) { $result = $reason; }
        );
        P\queue()->run();
        $this->assertEquals('fail', $result);
    }

    public function testSomeAggregatesSortedArrayWithMax()
    {
        $a = new Promise();
        $b = new Promise();
        $c = new Promise();
        $d = \GuzzleHttp\Promise\some(2, [$a, $b, $c]);
        $b->resolve('b');
        $c->resolve('c');
        $a->resolve('a');
        $d->then(function ($value) use (&$result) { $result = $value; });
        P\queue()->run();
        $this->assertEquals(['b', 'c'], $result);
    }

    public function testSomeRejectsWhenTooManyRejections()
    {
        $a = new Promise();
        $b = new Promise();
        $d = \GuzzleHttp\Promise\some(2, [$a, $b]);
        $a->reject('bad');
        $b->resolve('good');
        P\queue()->run();
        $this->assertEquals($a::REJECTED, $d->getState());
        $d->then(null, function ($reason) use (&$called) {
            $called = $reason;
        });
        P\queue()->run();
        $this->assertInstanceOf('GuzzleHttp\Promise\AggregateException', $called);
        $this->assertContains('bad', $called->getReason());
    }

    public function testCanWaitUntilSomeCountIsSatisfied()
    {
        $a = new Promise(function () use (&$a) { $a->resolve('a'); });
        $b = new Promise(function () use (&$b) { $b->resolve('b'); });
        $c = new Promise(function () use (&$c) { $c->resolve('c'); });
        $d = \GuzzleHttp\Promise\some(2, [$a, $b, $c]);
        $this->assertEquals(['a', 'b'], $d->wait());
    }

    /**
     * @expectedException \GuzzleHttp\Promise\AggregateException
     * @expectedExceptionMessage Not enough promises to fulfill count
     */
    public function testThrowsIfImpossibleToWaitForSomeCount()
    {
        $a = new Promise(function () use (&$a) { $a->resolve('a'); });
        $d = \GuzzleHttp\Promise\some(2, [$a]);
        $d->wait();
    }

    /**
     * @expectedException \GuzzleHttp\Promise\AggregateException
     * @expectedExceptionMessage Not enough promises to fulfill count
     */
    public function testThrowsIfResolvedWithoutCountTotalResults()
    {
        $a = new Promise();
        $b = new Promise();
        $d = \GuzzleHttp\Promise\some(3, [$a, $b]);
        $a->resolve('a');
        $b->resolve('b');
        $d->wait();
    }

    public function testAnyReturnsFirstMatch()
    {
        $a = new Promise();
        $b = new Promise();
        $c = \GuzzleHttp\Promise\any([$a, $b]);
        $b->resolve('b');
        $a->resolve('a');
        //P\queue()->run();
        //$this->assertEquals('fulfilled', $c->getState());
        $c->then(function ($value) use (&$result) { $result = $value; });
        P\queue()->run();
        $this->assertEquals('b', $result);
    }

    public function testSettleFulfillsWithFulfilledAndRejected()
    {
        $a = new Promise();
        $b = new Promise();
        $c = new Promise();
        $d = \GuzzleHttp\Promise\settle([$a, $b, $c]);
        $b->resolve('b');
        $c->resolve('c');
        $a->reject('a');
        P\queue()->run();
        $this->assertEquals('fulfilled', $d->getState());
        $d->then(function ($value) use (&$result) { $result = $value; });
        P\queue()->run();
        $this->assertEquals([
            ['state' => 'rejected', 'reason' => 'a'],
            ['state' => 'fulfilled', 'value' => 'b'],
            ['state' => 'fulfilled', 'value' => 'c']
        ], $result);
    }

    public function testCanInspectFulfilledPromise()
    {
        $p = new FulfilledPromise('foo');
        $this->assertEquals([
            'state' => 'fulfilled',
            'value' => 'foo'
        ], \GuzzleHttp\Promise\inspect($p));
    }

    public function testCanInspectRejectedPromise()
    {
        $p = new RejectedPromise('foo');
        $this->assertEquals([
            'state'  => 'rejected',
            'reason' => 'foo'
        ], \GuzzleHttp\Promise\inspect($p));
    }

    public function testCanInspectRejectedPromiseWithNormalException()
    {
        $e = new \Exception('foo');
        $p = new RejectedPromise($e);
        $this->assertEquals([
            'state'  => 'rejected',
            'reason' => $e
        ], \GuzzleHttp\Promise\inspect($p));
    }

    public function testCallsEachLimit()
    {
        $p = new Promise();
        $aggregate = \GuzzleHttp\Promise\each_limit($p, 2);
        $p->resolve('a');
        P\queue()->run();
        $this->assertEquals($p::FULFILLED, $aggregate->getState());
    }

    public function testEachLimitAllRejectsOnFailure()
    {
        $p = [new FulfilledPromise('a'), new RejectedPromise('b')];
        $aggregate = \GuzzleHttp\Promise\each_limit_all($p, 2);
        P\queue()->run();
        $this->assertEquals(P\PromiseInterface::REJECTED, $aggregate->getState());
        $result = \GuzzleHttp\Promise\inspect($aggregate);
        $this->assertEquals('b', $result['reason']);
    }

    public function testIterForReturnsIterator()
    {
        $iter = new \ArrayIterator();
        $this->assertSame($iter, \GuzzleHttp\Promise\iter_for($iter));
    }

    public function testKnowsIfFulfilled()
    {
        $p = new FulfilledPromise(null);
        $this->assertTrue(P\is_fulfilled($p));
        $this->assertFalse(P\is_rejected($p));
    }

    public function testKnowsIfRejected()
    {
        $p = new RejectedPromise(null);
        $this->assertTrue(P\is_rejected($p));
        $this->assertFalse(P\is_fulfilled($p));
    }

    public function testKnowsIfSettled()
    {
        $p = new RejectedPromise(null);
        $this->assertTrue(P\is_settled($p));
        $p = new Promise();
        $this->assertFalse(P\is_settled($p));
    }

    public function testReturnsTrampoline()
    {
        $this->assertInstanceOf('GuzzleHttp\Promise\TaskQueue', P\queue());
        $this->assertSame(P\queue(), P\queue());
    }

    public function testCanScheduleThunk()
    {
        $tramp = P\queue();
        $promise = P\task(function () { return 'Hi!'; });
        $c = null;
        $promise->then(function ($v) use (&$c) { $c = $v; });
        $this->assertNull($c);
        $tramp->run();
        $this->assertEquals('Hi!', $c);
    }

    public function testCanScheduleThunkWithRejection()
    {
        $tramp = P\queue();
        $promise = P\task(function () { throw new \Exception('Hi!'); });
        $c = null;
        $promise->otherwise(function ($v) use (&$c) { $c = $v; });
        $this->assertNull($c);
        $tramp->run();
        $this->assertEquals('Hi!', $c->getMessage());
    }

    public function testCanScheduleThunkWithWait()
    {
        $tramp = P\queue();
        $promise = P\task(function () { return 'a'; });
        $this->assertEquals('a', $promise->wait());
        $tramp->run();
    }

    public function testYieldsFromCoroutine()
    {
        $promise = P\coroutine(function () {
            $value = (yield new P\FulfilledPromise('a'));
            yield  $value . 'b';
        });
        $promise->then(function ($value) use (&$result) { $result = $value; });
        P\queue()->run();
        $this->assertEquals('ab', $result);
    }

    public function testCanCatchExceptionsInCoroutine()
    {
        $promise = P\coroutine(function () {
            try {
                yield new P\RejectedPromise('a');
                $this->fail('Should have thrown into the coroutine!');
            } catch (P\RejectionException $e) {
                $value = (yield new P\FulfilledPromise($e->getReason()));
                yield  $value . 'b';
            }
        });
        $promise->then(function ($value) use (&$result) { $result = $value; });
        P\queue()->run();
        $this->assertEquals(P\PromiseInterface::FULFILLED, $promise->getState());
        $this->assertEquals('ab', $result);
    }

    public function testRejectsParentExceptionWhenException()
    {
        $promise = P\coroutine(function () {
            yield new P\FulfilledPromise(0);
            throw new \Exception('a');
        });
        $promise->then(
            function () { $this->fail(); },
            function ($reason) use (&$result) { $result = $reason; }
        );
        P\queue()->run();
        $this->assertInstanceOf('Exception', $result);
        $this->assertEquals('a', $result->getMessage());
    }

    public function testCanRejectFromRejectionCallback()
    {
        $promise = P\coroutine(function () {
            yield new P\FulfilledPromise(0);
            yield new P\RejectedPromise('no!');
        });
        $promise->then(
            function () { $this->fail(); },
            function ($reason) use (&$result) { $result = $reason; }
        );
        P\queue()->run();
        $this->assertInstanceOf('GuzzleHttp\Promise\RejectionException', $result);
        $this->assertEquals('no!', $result->getReason());
    }

    public function testCanAsyncReject()
    {
        $rej = new P\Promise();
        $promise = P\coroutine(function () use ($rej) {
            yield new P\FulfilledPromise(0);
            yield $rej;
        });
        $promise->then(
            function () { $this->fail(); },
            function ($reason) use (&$result) { $result = $reason; }
        );
        $rej->reject('no!');
        P\queue()->run();
        $this->assertInstanceOf('GuzzleHttp\Promise\RejectionException', $result);
        $this->assertEquals('no!', $result->getReason());
    }

    public function testCanCatchAndThrowOtherException()
    {
        $promise = P\coroutine(function () {
            try {
                yield new P\RejectedPromise('a');
                $this->fail('Should have thrown into the coroutine!');
            } catch (P\RejectionException $e) {
                throw new \Exception('foo');
            }
        });
        $promise->otherwise(function ($value) use (&$result) { $result = $value; });
        P\queue()->run();
        $this->assertEquals(P\PromiseInterface::REJECTED, $promise->getState());
        $this->assertContains('foo', $result->getMessage());
    }

    public function testCanCatchAndYieldOtherException()
    {
        $promise = P\coroutine(function () {
            try {
                yield new P\RejectedPromise('a');
                $this->fail('Should have thrown into the coroutine!');
            } catch (P\RejectionException $e) {
                yield new P\RejectedPromise('foo');
            }
        });
        $promise->otherwise(function ($value) use (&$result) { $result = $value; });
        P\queue()->run();
        $this->assertEquals(P\PromiseInterface::REJECTED, $promise->getState());
        $this->assertContains('foo', $result->getMessage());
    }

    public function createLotsOfSynchronousPromise()
    {
        return P\coroutine(function () {
            $value = 0;
            for ($i = 0; $i < 1000; $i++) {
                $value = (yield new P\FulfilledPromise($i));
            }
            yield $value;
        });
    }

    public function testLotsOfSynchronousDoesNotBlowStack()
    {
        $promise = $this->createLotsOfSynchronousPromise();
        $promise->then(function ($v) use (&$r) { $r = $v; });
        P\queue()->run();
        $this->assertEquals(999, $r);
    }

    public function testLotsOfSynchronousWaitDoesNotBlowStack()
    {
        $promise = $this->createLotsOfSynchronousPromise();
        $promise->then(function ($v) use (&$r) { $r = $v; });
        $this->assertEquals(999, $promise->wait());
        $this->assertEquals(999, $r);
    }

    private function createLotsOfFlappingPromise()
    {
        return P\coroutine(function () {
            $value = 0;
            for ($i = 0; $i < 1000; $i++) {
                try {
                    if ($i % 2) {
                        $value = (yield new P\FulfilledPromise($i));
                    } else {
                        $value = (yield new P\RejectedPromise($i));
                    }
                } catch (\Exception $e) {
                    $value = (yield new P\FulfilledPromise($i));
                }
            }
            yield $value;
        });
    }

    public function testLotsOfTryCatchingDoesNotBlowStack()
    {
        $promise = $this->createLotsOfFlappingPromise();
        $promise->then(function ($v) use (&$r) { $r = $v; });
        P\queue()->run();
        $this->assertEquals(999, $r);
    }

    public function testLotsOfTryCatchingWaitingDoesNotBlowStack()
    {
        $promise = $this->createLotsOfFlappingPromise();
        $promise->then(function ($v) use (&$r) { $r = $v; });
        $this->assertEquals(999, $promise->wait());
        $this->assertEquals(999, $r);
    }

    public function testAsyncPromisesWithCorrectlyYieldedValues()
    {
        $promises = [
            new P\Promise(),
            new P\Promise(),
            new P\Promise()
        ];

        $promise = P\coroutine(function () use ($promises) {
            $value = null;
            $this->assertEquals('skip', (yield new P\FulfilledPromise('skip')));
            foreach ($promises as $idx => $p) {
                $value = (yield $p);
                $this->assertEquals($value, $idx);
                $this->assertEquals('skip', (yield new P\FulfilledPromise('skip')));
            }
            $this->assertEquals('skip', (yield new P\FulfilledPromise('skip')));
            yield $value;
        });

        $promises[0]->resolve(0);
        $promises[1]->resolve(1);
        $promises[2]->resolve(2);

        $promise->then(function ($v) use (&$r) { $r = $v; });
        P\queue()->run();
        $this->assertEquals(2, $r);
    }

    public function testYieldFinalWaitablePromise()
    {
        $p1 = new P\Promise(function () use (&$p1) {
            $p1->resolve('skip me');
        });
        $p2 = new P\Promise(function () use (&$p2) {
            $p2->resolve('hello!');
        });
        $co = P\coroutine(function() use ($p1, $p2) {
            yield $p1;
            yield $p2;
        });
        P\queue()->run();
        $this->assertEquals('hello!', $co->wait());
    }

    public function testCanYieldFinalPendingPromise()
    {
        $p1 = new P\Promise();
        $p2 = new P\Promise();
        $co = P\coroutine(function() use ($p1, $p2) {
            yield $p1;
            yield $p2;
        });
        $p1->resolve('a');
        $p2->resolve('b');
        $co->then(function ($value) use (&$result) { $result = $value; });
        P\queue()->run();
        $this->assertEquals('b', $result);
    }

    public function testCanNestYieldsAndFailures()
    {
        $p1 = new P\Promise();
        $p2 = new P\Promise();
        $p3 = new P\Promise();
        $p4 = new P\Promise();
        $p5 = new P\Promise();
        $co = P\coroutine(function() use ($p1, $p2, $p3, $p4, $p5) {
            try {
                yield $p1;
            } catch (\Exception $e) {
                yield $p2;
                try {
                    yield $p3;
                    yield $p4;
                } catch (\Exception $e) {
                    yield $p5;
                }
            }
        });
        $p1->reject('a');
        $p2->resolve('b');
        $p3->resolve('c');
        $p4->reject('d');
        $p5->resolve('e');
        $co->then(function ($value) use (&$result) { $result = $value; });
        P\queue()->run();
        $this->assertEquals('e', $result);
    }

    public function testCanYieldErrorsAndSuccessesWithoutRecursion()
    {
        $promises = [];
        for ($i = 0; $i < 20; $i++) {
            $promises[] = new P\Promise();
        }

        $co = P\coroutine(function() use ($promises) {
            for ($i = 0; $i < 20; $i += 4) {
                try {
                    yield $promises[$i];
                    yield $promises[$i + 1];
                } catch (\Exception $e) {
                    yield $promises[$i + 2];
                    yield $promises[$i + 3];
                }
            }
        });

        for ($i = 0; $i < 20; $i += 4) {
            $promises[$i]->resolve($i);
            $promises[$i + 1]->reject($i + 1);
            $promises[$i + 2]->resolve($i + 2);
            $promises[$i + 3]->resolve($i + 3);
        }

        $co->then(function ($value) use (&$result) { $result = $value; });
        P\queue()->run();
        $this->assertEquals('19', $result);
    }

    public function testCanWaitOnPromiseAfterFulfilled()
    {
        $f = function () {
            static $i = 0;
            $i++;
            return $p = new P\Promise(function () use (&$p, $i) {
                $p->resolve($i . '-bar');
            });
        };

        $promises = [];
        for ($i = 0; $i < 20; $i++) {
            $promises[] = $f();
        }

        $p = P\coroutine(function () use ($promises) {
            yield new P\FulfilledPromise('foo!');
            foreach ($promises as $promise) {
                yield $promise;
            }
        });

        $this->assertEquals('20-bar', $p->wait());
    }

    public function testCanWaitOnErroredPromises()
    {
        $p1 = new P\Promise(function () use (&$p1) { $p1->reject('a'); });
        $p2 = new P\Promise(function () use (&$p2) { $p2->resolve('b'); });
        $p3 = new P\Promise(function () use (&$p3) { $p3->resolve('c'); });
        $p4 = new P\Promise(function () use (&$p4) { $p4->reject('d'); });
        $p5 = new P\Promise(function () use (&$p5) { $p5->resolve('e'); });
        $p6 = new P\Promise(function () use (&$p6) { $p6->reject('f'); });

        $co = P\coroutine(function() use ($p1, $p2, $p3, $p4, $p5, $p6) {
            try {
                yield $p1;
            } catch (\Exception $e) {
                yield $p2;
                try {
                    yield $p3;
                    yield $p4;
                } catch (\Exception $e) {
                    yield $p5;
                    yield $p6;
                }
            }
        });

        $res = P\inspect($co);
        $this->assertEquals('f', $res['reason']);
    }

    public function testCoroutineOtherwiseIntegrationTest()
    {
        $a = new P\Promise();
        $b = new P\Promise();
        $promise = P\coroutine(function () use ($a, $b) {
            // Execute the pool of commands concurrently, and process errors.
            yield $a;
            yield $b;
        })->otherwise(function (\Exception $e) {
            // Throw errors from the operations as a specific Multipart error.
            throw new \OutOfBoundsException('a', 0, $e);
        });
        $a->resolve('a');
        $b->reject('b');
        $reason = P\inspect($promise)['reason'];
        $this->assertInstanceOf('OutOfBoundsException', $reason);
        $this->assertInstanceOf('GuzzleHttp\Promise\RejectionException', $reason->getPrevious());
    }
}
<?php
namespace GuzzleHttp\Promise\Tests;

use GuzzleHttp\Promise\Promise;
use GuzzleHttp\Promise\PromiseInterface;

class NotPromiseInstance extends Thennable implements PromiseInterface
{
    private $nextPromise = null;

    public function __construct()
    {
        $this->nextPromise = new Promise();
    }

    public function then(callable $res = null, callable $rej = null)
    {
        return $this->nextPromise->then($res, $rej);
    }

    public function otherwise(callable $onRejected)
    {
        return $this->then($onRejected);
    }

    public function resolve($value)
    {
        $this->nextPromise->resolve($value);
    }

    public function reject($reason)
    {
        $this->nextPromise->reject($reason);
    }

    public function wait($unwrap = true, $defaultResolution = null)
    {

    }

    public function cancel()
    {

    }

    public function getState()
    {
        return $this->nextPromise->getState();
    }
}
<?php
namespace GuzzleHttp\Promise\Tests;

use GuzzleHttp\Promise\CancellationException;
use GuzzleHttp\Promise as P;
use GuzzleHttp\Promise\Promise;
use GuzzleHttp\Promise\RejectedPromise;
use GuzzleHttp\Promise\RejectionException;

/**
 * @covers GuzzleHttp\Promise\Promise
 */
class PromiseTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @expectedException \LogicException
     * @expectedExceptionMessage The promise is already fulfilled
     */
    public function testCannotResolveNonPendingPromise()
    {
        $p = new Promise();
        $p->resolve('foo');
        $p->resolve('bar');
        $this->assertEquals('foo', $p->wait());
    }

    public function testCanResolveWithSameValue()
    {
        $p = new Promise();
        $p->resolve('foo');
        $p->resolve('foo');
    }

    /**
     * @expectedException \LogicException
     * @expectedExceptionMessage Cannot change a fulfilled promise to rejected
     */
    public function testCannotRejectNonPendingPromise()
    {
        $p = new Promise();
        $p->resolve('foo');
        $p->reject('bar');
        $this->assertEquals('foo', $p->wait());
    }

    public function testCanRejectWithSameValue()
    {
        $p = new Promise();
        $p->reject('foo');
        $p->reject('foo');
    }

    /**
     * @expectedException \LogicException
     * @expectedExceptionMessage Cannot change a fulfilled promise to rejected
     */
    public function testCannotRejectResolveWithSameValue()
    {
        $p = new Promise();
        $p->resolve('foo');
        $p->reject('foo');
    }

    public function testInvokesWaitFunction()
    {
        $p = new Promise(function () use (&$p) { $p->resolve('10'); });
        $this->assertEquals('10', $p->wait());
    }

    /**
     * @expectedException \GuzzleHttp\Promise\RejectionException
     */
    public function testRejectsAndThrowsWhenWaitFailsToResolve()
    {
        $p = new Promise(function () {});
        $p->wait();
    }

    /**
     * @expectedException \GuzzleHttp\Promise\RejectionException
     * @expectedExceptionMessage The promise was rejected with reason: foo
     */
    public function testThrowsWhenUnwrapIsRejectedWithNonException()
    {
        $p = new Promise(function () use (&$p) { $p->reject('foo'); });
        $p->wait();
    }

    /**
     * @expectedException \UnexpectedValueException
     * @expectedExceptionMessage foo
     */
    public function testThrowsWhenUnwrapIsRejectedWithException()
    {
        $e = new \UnexpectedValueException('foo');
        $p = new Promise(function () use (&$p, $e) { $p->reject($e); });
        $p->wait();
    }

    public function testDoesNotUnwrapExceptionsWhenDisabled()
    {
        $p = new Promise(function () use (&$p) { $p->reject('foo'); });
        $this->assertEquals('pending', $p->getState());
        $p->wait(false);
        $this->assertEquals('rejected', $p->getState());
    }

    public function testRejectsSelfWhenWaitThrows()
    {
        $e = new \UnexpectedValueException('foo');
        $p = new Promise(function () use ($e) { throw $e; });
        try {
            $p->wait();
            $this->fail();
        } catch (\UnexpectedValueException $e) {
            $this->assertEquals('rejected', $p->getState());
        }
    }

    public function testWaitsOnNestedPromises()
    {
        $p = new Promise(function () use (&$p) { $p->resolve('_'); });
        $p2 = new Promise(function () use (&$p2) { $p2->resolve('foo'); });
        $p3 = $p->then(function () use ($p2) { return $p2; });
        $this->assertSame('foo', $p3->wait());
    }

    /**
     * @expectedException \GuzzleHttp\Promise\RejectionException
     */
    public function testThrowsWhenWaitingOnPromiseWithNoWaitFunction()
    {
        $p = new Promise();
        $p->wait();
    }

    public function testThrowsWaitExceptionAfterPromiseIsResolved()
    {
        $p = new Promise(function () use (&$p) {
            $p->reject('Foo!');
            throw new \Exception('Bar?');
        });

        try {
            $p->wait();
            $this->fail();
        } catch (\Exception $e) {
            $this->assertEquals('Bar?', $e->getMessage());
        }
    }

    public function testGetsActualWaitValueFromThen()
    {
        $p = new Promise(function () use (&$p) { $p->reject('Foo!'); });
        $p2 = $p->then(null, function ($reason) {
            return new RejectedPromise([$reason]);
        });

        try {
            $p2->wait();
            $this->fail('Should have thrown');
        } catch (RejectionException $e) {
            $this->assertEquals(['Foo!'], $e->getReason());
        }
    }

    public function testWaitBehaviorIsBasedOnLastPromiseInChain()
    {
        $p3 = new Promise(function () use (&$p3) { $p3->resolve('Whoop'); });
        $p2 = new Promise(function () use (&$p2, $p3) { $p2->reject($p3); });
        $p = new Promise(function () use (&$p, $p2) { $p->reject($p2); });
        $this->assertEquals('Whoop', $p->wait());
    }

    public function testWaitsOnAPromiseChainEvenWhenNotUnwrapped()
    {
        $p2 = new Promise(function () use (&$p2) {
            $p2->reject('Fail');
        });
        $p = new Promise(function () use ($p2, &$p) {
            $p->resolve($p2);
        });
        $p->wait(false);
        $this->assertSame(Promise::REJECTED, $p2->getState());
    }

    public function testCannotCancelNonPending()
    {
        $p = new Promise();
        $p->resolve('foo');
        $p->cancel();
        $this->assertEquals('fulfilled', $p->getState());
    }

    /**
     * @expectedException \GuzzleHttp\Promise\CancellationException
     */
    public function testCancelsPromiseWhenNoCancelFunction()
    {
        $p = new Promise();
        $p->cancel();
        $this->assertEquals('rejected', $p->getState());
        $p->wait();
    }

    public function testCancelsPromiseWithCancelFunction()
    {
        $called = false;
        $p = new Promise(null, function () use (&$called) { $called = true; });
        $p->cancel();
        $this->assertEquals('rejected', $p->getState());
        $this->assertTrue($called);
    }

    public function testCancelsUppermostPendingPromise()
    {
        $called = false;
        $p1 = new Promise(null, function () use (&$called) { $called = true; });
        $p2 = $p1->then(function () {});
        $p3 = $p2->then(function () {});
        $p4 = $p3->then(function () {});
        $p3->cancel();
        $this->assertEquals('rejected', $p1->getState());
        $this->assertEquals('rejected', $p2->getState());
        $this->assertEquals('rejected', $p3->getState());
        $this->assertEquals('pending', $p4->getState());
        $this->assertTrue($called);

        try {
            $p3->wait();
            $this->fail();
        } catch (CancellationException $e) {
            $this->assertContains('cancelled', $e->getMessage());
        }

        try {
            $p4->wait();
            $this->fail();
        } catch (CancellationException $e) {
            $this->assertContains('cancelled', $e->getMessage());
        }

        $this->assertEquals('rejected', $p4->getState());
    }

    public function testCancelsChildPromises()
    {
        $called1 = $called2 = $called3 = false;
        $p1 = new Promise(null, function () use (&$called1) { $called1 = true; });
        $p2 = new Promise(null, function () use (&$called2) { $called2 = true; });
        $p3 = new Promise(null, function () use (&$called3) { $called3 = true; });
        $p4 = $p2->then(function () use ($p3) { return $p3; });
        $p5 = $p4->then(function () { $this->fail(); });
        $p4->cancel();
        $this->assertEquals('pending', $p1->getState());
        $this->assertEquals('rejected', $p2->getState());
        $this->assertEquals('rejected', $p4->getState());
        $this->assertEquals('pending', $p5->getState());
        $this->assertFalse($called1);
        $this->assertTrue($called2);
        $this->assertFalse($called3);
    }

    public function testRejectsPromiseWhenCancelFails()
    {
        $called = false;
        $p = new Promise(null, function () use (&$called) {
            $called = true;
            throw new \Exception('e');
        });
        $p->cancel();
        $this->assertEquals('rejected', $p->getState());
        $this->assertTrue($called);
        try {
            $p->wait();
            $this->fail();
        } catch (\Exception $e) {
            $this->assertEquals('e', $e->getMessage());
        }
    }

    public function testCreatesPromiseWhenFulfilledAfterThen()
    {
        $p = new Promise();
        $carry = null;
        $p2 = $p->then(function ($v) use (&$carry) { $carry = $v; });
        $this->assertNotSame($p, $p2);
        $p->resolve('foo');
        P\queue()->run();

        $this->assertEquals('foo', $carry);
    }

    public function testCreatesPromiseWhenFulfilledBeforeThen()
    {
        $p = new Promise();
        $p->resolve('foo');
        $carry = null;
        $p2 = $p->then(function ($v) use (&$carry) { $carry = $v; });
        $this->assertNotSame($p, $p2);
        $this->assertNull($carry);
        \GuzzleHttp\Promise\queue()->run();
        $this->assertEquals('foo', $carry);
    }

    public function testCreatesPromiseWhenFulfilledWithNoCallback()
    {
        $p = new Promise();
        $p->resolve('foo');
        $p2 = $p->then();
        $this->assertNotSame($p, $p2);
        $this->assertInstanceOf('GuzzleHttp\Promise\FulfilledPromise', $p2);
    }

    public function testCreatesPromiseWhenRejectedAfterThen()
    {
        $p = new Promise();
        $carry = null;
        $p2 = $p->then(null, function ($v) use (&$carry) { $carry = $v; });
        $this->assertNotSame($p, $p2);
        $p->reject('foo');
        P\queue()->run();
        $this->assertEquals('foo', $carry);
    }

    public function testCreatesPromiseWhenRejectedBeforeThen()
    {
        $p = new Promise();
        $p->reject('foo');
        $carry = null;
        $p2 = $p->then(null, function ($v) use (&$carry) { $carry = $v; });
        $this->assertNotSame($p, $p2);
        $this->assertNull($carry);
        P\queue()->run();
        $this->assertEquals('foo', $carry);
    }

    public function testCreatesPromiseWhenRejectedWithNoCallback()
    {
        $p = new Promise();
        $p->reject('foo');
        $p2 = $p->then();
        $this->assertNotSame($p, $p2);
        $this->assertInstanceOf('GuzzleHttp\Promise\RejectedPromise', $p2);
    }

    public function testInvokesWaitFnsForThens()
    {
        $p = new Promise(function () use (&$p) { $p->resolve('a'); });
        $p2 = $p
            ->then(function ($v) { return $v . '-1-'; })
            ->then(function ($v) { return $v . '2'; });
        $this->assertEquals('a-1-2', $p2->wait());
    }

    public function testStacksThenWaitFunctions()
    {
        $p1 = new Promise(function () use (&$p1) { $p1->resolve('a'); });
        $p2 = new Promise(function () use (&$p2) { $p2->resolve('b'); });
        $p3 = new Promise(function () use (&$p3) { $p3->resolve('c'); });
        $p4 = $p1
            ->then(function () use ($p2) { return $p2; })
            ->then(function () use ($p3) { return $p3; });
        $this->assertEquals('c', $p4->wait());
    }

    public function testForwardsFulfilledDownChainBetweenGaps()
    {
        $p = new Promise();
        $r = $r2 = null;
        $p->then(null, null)
            ->then(function ($v) use (&$r) { $r = $v; return $v . '2'; })
            ->then(function ($v) use (&$r2) { $r2 = $v; });
        $p->resolve('foo');
        P\queue()->run();
        $this->assertEquals('foo', $r);
        $this->assertEquals('foo2', $r2);
    }

    public function testForwardsRejectedPromisesDownChainBetweenGaps()
    {
        $p = new Promise();
        $r = $r2 = null;
        $p->then(null, null)
            ->then(null, function ($v) use (&$r) { $r = $v; return $v . '2'; })
            ->then(function ($v) use (&$r2) { $r2 = $v; });
        $p->reject('foo');
        P\queue()->run();
        $this->assertEquals('foo', $r);
        $this->assertEquals('foo2', $r2);
    }

    public function testForwardsThrownPromisesDownChainBetweenGaps()
    {
        $e = new \Exception();
        $p = new Promise();
        $r = $r2 = null;
        $p->then(null, null)
            ->then(null, function ($v) use (&$r, $e) {
                $r = $v;
                throw $e;
            })
            ->then(
                null,
                function ($v) use (&$r2) { $r2 = $v; }
            );
        $p->reject('foo');
        P\queue()->run();
        $this->assertEquals('foo', $r);
        $this->assertSame($e, $r2);
    }

    public function testForwardsReturnedRejectedPromisesDownChainBetweenGaps()
    {
        $p = new Promise();
        $rejected = new RejectedPromise('bar');
        $r = $r2 = null;
        $p->then(null, null)
            ->then(null, function ($v) use (&$r, $rejected) {
                $r = $v;
                return $rejected;
            })
            ->then(
                null,
                function ($v) use (&$r2) { $r2 = $v; }
            );
        $p->reject('foo');
        P\queue()->run();
        $this->assertEquals('foo', $r);
        $this->assertEquals('bar', $r2);
        try {
            $p->wait();
        } catch (RejectionException $e) {
            $this->assertEquals('foo', $e->getReason());
        }
    }

    public function testForwardsHandlersToNextPromise()
    {
        $p = new Promise();
        $p2 = new Promise();
        $resolved = null;
        $p
            ->then(function ($v) use ($p2) { return $p2; })
            ->then(function ($value) use (&$resolved) { $resolved = $value; });
        $p->resolve('a');
        $p2->resolve('b');
        P\queue()->run();
        $this->assertEquals('b', $resolved);
    }

    public function testRemovesReferenceFromChildWhenParentWaitedUpon()
    {
        $r = null;
        $p = new Promise(function () use (&$p) { $p->resolve('a'); });
        $p2 = new Promise(function () use (&$p2) { $p2->resolve('b'); });
        $pb = $p->then(
            function ($v) use ($p2, &$r) {
                $r = $v;
                return $p2;
            })
            ->then(function ($v) { return $v . '.'; });
        $this->assertEquals('a', $p->wait());
        $this->assertEquals('b', $p2->wait());
        $this->assertEquals('b.', $pb->wait());
        $this->assertEquals('a', $r);
    }

    public function testForwardsHandlersWhenFulfilledPromiseIsReturned()
    {
        $res = [];
        $p = new Promise();
        $p2 = new Promise();
        $p2->resolve('foo');
        $p2->then(function ($v) use (&$res) { $res[] = 'A:' . $v; });
        // $res is A:foo
        $p
            ->then(function () use ($p2, &$res) { $res[] = 'B'; return $p2; })
            ->then(function ($v) use (&$res) { $res[] = 'C:' . $v; });
        $p->resolve('a');
        $p->then(function ($v) use (&$res) { $res[] = 'D:' . $v; });
        P\queue()->run();
        $this->assertEquals(['A:foo', 'B', 'D:a', 'C:foo'], $res);
    }

    public function testForwardsHandlersWhenRejectedPromiseIsReturned()
    {
        $res = [];
        $p = new Promise();
        $p2 = new Promise();
        $p2->reject('foo');
        $p2->then(null, function ($v) use (&$res) { $res[] = 'A:' . $v; });
        $p->then(null, function () use ($p2, &$res) { $res[] = 'B'; return $p2; })
            ->then(null, function ($v) use (&$res) { $res[] = 'C:' . $v; });
        $p->reject('a');
        $p->then(null, function ($v) use (&$res) { $res[] = 'D:' . $v; });
        P\queue()->run();
        $this->assertEquals(['A:foo', 'B', 'D:a', 'C:foo'], $res);
    }

    public function testDoesNotForwardRejectedPromise()
    {
        $res = [];
        $p = new Promise();
        $p2 = new Promise();
        $p2->cancel();
        $p2->then(function ($v) use (&$res) { $res[] = "B:$v"; return $v; });
        $p->then(function ($v) use ($p2, &$res) { $res[] = "B:$v"; return $p2; })
            ->then(function ($v) use (&$res) { $res[] = 'C:' . $v; });
        $p->resolve('a');
        $p->then(function ($v) use (&$res) { $res[] = 'D:' . $v; });
        P\queue()->run();
        $this->assertEquals(['B:a', 'D:a'], $res);
    }

    public function testRecursivelyForwardsWhenOnlyThennable()
    {
        $res = [];
        $p = new Promise();
        $p2 = new Thennable();
        $p2->resolve('foo');
        $p2->then(function ($v) use (&$res) { $res[] = 'A:' . $v; });
        $p->then(function () use ($p2, &$res) { $res[] = 'B'; return $p2; })
            ->then(function ($v) use (&$res) { $res[] = 'C:' . $v; });
        $p->resolve('a');
        $p->then(function ($v) use (&$res) { $res[] = 'D:' . $v; });
        P\queue()->run();
        $this->assertEquals(['A:foo', 'B', 'D:a', 'C:foo'], $res);
    }

    public function testRecursivelyForwardsWhenNotInstanceOfPromise()
    {
        $res = [];
        $p = new Promise();
        $p2 = new NotPromiseInstance();
        $p2->then(function ($v) use (&$res) { $res[] = 'A:' . $v; });
        $p->then(function () use ($p2, &$res) { $res[] = 'B'; return $p2; })
            ->then(function ($v) use (&$res) { $res[] = 'C:' . $v; });
        $p->resolve('a');
        $p->then(function ($v) use (&$res) { $res[] = 'D:' . $v; });
        P\queue()->run();
        $this->assertEquals(['B', 'D:a'], $res);
        $p2->resolve('foo');
        P\queue()->run();
        $this->assertEquals(['B', 'D:a', 'A:foo', 'C:foo'], $res);
    }

    /**
     * @expectedException \LogicException
     * @expectedExceptionMessage Cannot fulfill or reject a promise with itself
     */
    public function testCannotResolveWithSelf()
    {
        $p = new Promise();
        $p->resolve($p);
    }

    /**
     * @expectedException \LogicException
     * @expectedExceptionMessage Cannot fulfill or reject a promise with itself
     */
    public function testCannotRejectWithSelf()
    {
        $p = new Promise();
        $p->reject($p);
    }

    public function testDoesNotBlowStackWhenWaitingOnNestedThens()
    {
        $inner = new Promise(function () use (&$inner) { $inner->resolve(0); });
        $prev = $inner;
        for ($i = 1; $i < 100; $i++) {
            $prev = $prev->then(function ($i) { return $i + 1; });
        }

        $parent = new Promise(function () use (&$parent, $prev) {
            $parent->resolve($prev);
        });

        $this->assertEquals(99, $parent->wait());
    }

    public function testOtherwiseIsSugarForRejections()
    {
        $p = new Promise();
        $p->reject('foo');
        $p->otherwise(function ($v) use (&$c) { $c = $v; });
        P\queue()->run();
        $this->assertEquals($c, 'foo');
    }
}
<?php
namespace GuzzleHttp\Promise\Tests;

use GuzzleHttp\Promise\Promise;
use GuzzleHttp\Promise\RejectedPromise;

/**
 * @covers GuzzleHttp\Promise\RejectedPromise
 */
class RejectedPromiseTest extends \PHPUnit_Framework_TestCase
{
    public function testThrowsReasonWhenWaitedUpon()
    {
        $p = new RejectedPromise('foo');
        $this->assertEquals('rejected', $p->getState());
        try {
            $p->wait(true);
            $this->fail();
        } catch (\Exception $e) {
            $this->assertEquals('rejected', $p->getState());
            $this->assertContains('foo', $e->getMessage());
        }
    }

    public function testCannotCancel()
    {
        $p = new RejectedPromise('foo');
        $p->cancel();
        $this->assertEquals('rejected', $p->getState());
    }

    /**
     * @expectedException \LogicException
     * @exepctedExceptionMessage Cannot resolve a rejected promise
     */
    public function testCannotResolve()
    {
        $p = new RejectedPromise('foo');
        $p->resolve('bar');
    }

    /**
     * @expectedException \LogicException
     * @exepctedExceptionMessage Cannot reject a rejected promise
     */
    public function testCannotReject()
    {
        $p = new RejectedPromise('foo');
        $p->reject('bar');
    }

    public function testCanRejectWithSameValue()
    {
        $p = new RejectedPromise('foo');
        $p->reject('foo');
    }

    public function testThrowsSpecificException()
    {
        $e = new \Exception();
        $p = new RejectedPromise($e);
        try {
            $p->wait(true);
            $this->fail();
        } catch (\Exception $e2) {
            $this->assertSame($e, $e2);
        }
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testCannotResolveWithPromise()
    {
        new RejectedPromise(new Promise());
    }

    public function testReturnsSelfWhenNoOnReject()
    {
        $p = new RejectedPromise('a');
        $this->assertSame($p, $p->then());
    }

    public function testInvokesOnRejectedAsynchronously()
    {
        $p = new RejectedPromise('a');
        $r = null;
        $f = function ($reason) use (&$r) { $r = $reason; };
        $p->then(null, $f);
        $this->assertNull($r);
        \GuzzleHttp\Promise\queue()->run();
        $this->assertEquals('a', $r);
    }

    public function testReturnsNewRejectedWhenOnRejectedFails()
    {
        $p = new RejectedPromise('a');
        $f = function () { throw new \Exception('b'); };
        $p2 = $p->then(null, $f);
        $this->assertNotSame($p, $p2);
        try {
            $p2->wait();
            $this->fail();
        } catch (\Exception $e) {
            $this->assertEquals('b', $e->getMessage());
        }
    }

    public function testWaitingIsNoOp()
    {
        $p = new RejectedPromise('a');
        $p->wait(false);
    }

    public function testOtherwiseIsSugarForRejections()
    {
        $p = new RejectedPromise('foo');
        $p->otherwise(function ($v) use (&$c) { $c = $v; });
        \GuzzleHttp\Promise\queue()->run();
        $this->assertSame('foo', $c);
    }

    public function testCanResolveThenWithSuccess()
    {
        $actual = null;
        $p = new RejectedPromise('foo');
        $p->otherwise(function ($v) {
            return $v . ' bar';
        })->then(function ($v) use (&$actual) {
            $actual = $v;
        });
        \GuzzleHttp\Promise\queue()->run();
        $this->assertEquals('foo bar', $actual);
    }

    public function testDoesNotTryToRejectTwiceDuringTrampoline()
    {
        $fp = new RejectedPromise('a');
        $t1 = $fp->then(null, function ($v) { return $v . ' b'; });
        $t1->resolve('why!');
        $this->assertEquals('why!', $t1->wait());
    }
}
<?php
namespace GuzzleHttp\Promise\Tests;

use GuzzleHttp\Promise\RejectionException;

class Thing1
{
    public function __construct($message)
    {
        $this->message = $message;
    }

    public function __toString()
    {
        return $this->message;
    }
}

class Thing2 implements \JsonSerializable
{
    public function jsonSerialize()
    {
        return '{}';
    }
}

/**
 * @covers GuzzleHttp\Promise\RejectionException
 */
class RejectionExceptionTest extends \PHPUnit_Framework_TestCase
{
    public function testCanGetReasonFromException()
    {
        $thing = new Thing1('foo');
        $e = new RejectionException($thing);

        $this->assertSame($thing, $e->getReason());
        $this->assertEquals('The promise was rejected with reason: foo', $e->getMessage());
    }

    public function testCanGetReasonMessageFromJson()
    {
        $reason = new Thing2();
        $e = new RejectionException($reason);
        $this->assertContains("{}", $e->getMessage());
    }
}
<?php
namespace GuzzleHttp\Promise\Test;

use GuzzleHttp\Promise\TaskQueue;

class TaskQueueTest extends \PHPUnit_Framework_TestCase
{
    public function testKnowsIfEmpty()
    {
        $tq = new TaskQueue(false);
        $this->assertTrue($tq->isEmpty());
    }

    public function testKnowsIfFull()
    {
        $tq = new TaskQueue(false);
        $tq->add(function () {});
        $this->assertFalse($tq->isEmpty());
    }

    public function testExecutesTasksInOrder()
    {
        $tq = new TaskQueue(false);
        $called = [];
        $tq->add(function () use (&$called) { $called[] = 'a'; });
        $tq->add(function () use (&$called) { $called[] = 'b'; });
        $tq->add(function () use (&$called) { $called[] = 'c'; });
        $tq->run();
        $this->assertEquals(['a', 'b', 'c'], $called);
    }
}
<?php
namespace GuzzleHttp\Promise\Tests;

use GuzzleHttp\Promise\Promise;

class Thennable
{
    private $nextPromise = null;

    public function __construct()
    {
        $this->nextPromise = new Promise();
    }

    public function then(callable $res = null, callable $rej = null)
    {
        return $this->nextPromise->then($res, $rej);
    }

    public function resolve($value)
    {
        $this->nextPromise->resolve($value);
    }
}
{
    "name": "psr/http-message",
    "description": "Common interface for HTTP messages",
    "keywords": ["psr", "psr-7", "http", "http-message", "request", "response"],
    "license": "MIT",
    "authors": [
        {
            "name": "PHP-FIG",
            "homepage": "http://www.php-fig.org/"
        }
    ],
    "require": {
        "php": ">=5.3.0"
    },
    "autoload": {
        "psr-4": {
            "Psr\\Http\\Message\\": "src/"
        }
    },
    "extra": {
        "branch-alias": {
            "dev-master": "1.0.x-dev"
        }
    }
}
Copyright (c) 2014 PHP Framework Interoperability Group

Permission is hereby granted, free of charge, to any person obtaining a copy 
of this software and associated documentation files (the "Software"), to deal
in the Software without restriction, including without limitation the rights 
to use, copy, modify, merge, publish, distribute, sublicense, and/or sell 
copies of the Software, and to permit persons to whom the Software is 
furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in 
all copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
THE SOFTWARE.
PSR Http Message
================

This repository holds all interfaces/classes/traits related to
[PSR-7](http://www.php-fig.org/psr/psr-7/).

Note that this is not a HTTP message implementation of its own. It is merely an
interface that describes a HTTP message. See the specification for more details.

Usage
-----

We'll certainly need some stuff in here.<?php

namespace Psr\Http\Message;

/**
 * HTTP messages consist of requests from a client to a server and responses
 * from a server to a client. This interface defines the methods common to
 * each.
 *
 * Messages are considered immutable; all methods that might change state MUST
 * be implemented such that they retain the internal state of the current
 * message and return an instance that contains the changed state.
 *
 * @link http://www.ietf.org/rfc/rfc7230.txt
 * @link http://www.ietf.org/rfc/rfc7231.txt
 */
interface MessageInterface
{
    /**
     * Retrieves the HTTP protocol version as a string.
     *
     * The string MUST contain only the HTTP version number (e.g., "1.1", "1.0").
     *
     * @return string HTTP protocol version.
     */
    public function getProtocolVersion();

    /**
     * Return an instance with the specified HTTP protocol version.
     *
     * The version string MUST contain only the HTTP version number (e.g.,
     * "1.1", "1.0").
     *
     * This method MUST be implemented in such a way as to retain the
     * immutability of the message, and MUST return an instance that has the
     * new protocol version.
     *
     * @param string $version HTTP protocol version
     * @return self
     */
    public function withProtocolVersion($version);

    /**
     * Retrieves all message header values.
     *
     * The keys represent the header name as it will be sent over the wire, and
     * each value is an array of strings associated with the header.
     *
     *     // Represent the headers as a string
     *     foreach ($message->getHeaders() as $name => $values) {
     *         echo $name . ": " . implode(", ", $values);
     *     }
     *
     *     // Emit headers iteratively:
     *     foreach ($message->getHeaders() as $name => $values) {
     *         foreach ($values as $value) {
     *             header(sprintf('%s: %s', $name, $value), false);
     *         }
     *     }
     *
     * While header names are not case-sensitive, getHeaders() will preserve the
     * exact case in which headers were originally specified.
     *
     * @return array Returns an associative array of the message's headers. Each
     *     key MUST be a header name, and each value MUST be an array of strings
     *     for that header.
     */
    public function getHeaders();

    /**
     * Checks if a header exists by the given case-insensitive name.
     *
     * @param string $name Case-insensitive header field name.
     * @return bool Returns true if any header names match the given header
     *     name using a case-insensitive string comparison. Returns false if
     *     no matching header name is found in the message.
     */
    public function hasHeader($name);

    /**
     * Retrieves a message header value by the given case-insensitive name.
     *
     * This method returns an array of all the header values of the given
     * case-insensitive header name.
     *
     * If the header does not appear in the message, this method MUST return an
     * empty array.
     *
     * @param string $name Case-insensitive header field name.
     * @return string[] An array of string values as provided for the given
     *    header. If the header does not appear in the message, this method MUST
     *    return an empty array.
     */
    public function getHeader($name);

    /**
     * Retrieves a comma-separated string of the values for a single header.
     *
     * This method returns all of the header values of the given
     * case-insensitive header name as a string concatenated together using
     * a comma.
     *
     * NOTE: Not all header values may be appropriately represented using
     * comma concatenation. For such headers, use getHeader() instead
     * and supply your own delimiter when concatenating.
     *
     * If the header does not appear in the message, this method MUST return
     * an empty string.
     *
     * @param string $name Case-insensitive header field name.
     * @return string A string of values as provided for the given header
     *    concatenated together using a comma. If the header does not appear in
     *    the message, this method MUST return an empty string.
     */
    public function getHeaderLine($name);

    /**
     * Return an instance with the provided value replacing the specified header.
     *
     * While header names are case-insensitive, the casing of the header will
     * be preserved by this function, and returned from getHeaders().
     *
     * This method MUST be implemented in such a way as to retain the
     * immutability of the message, and MUST return an instance that has the
     * new and/or updated header and value.
     *
     * @param string $name Case-insensitive header field name.
     * @param string|string[] $value Header value(s).
     * @return self
     * @throws \InvalidArgumentException for invalid header names or values.
     */
    public function withHeader($name, $value);

    /**
     * Return an instance with the specified header appended with the given value.
     *
     * Existing values for the specified header will be maintained. The new
     * value(s) will be appended to the existing list. If the header did not
     * exist previously, it will be added.
     *
     * This method MUST be implemented in such a way as to retain the
     * immutability of the message, and MUST return an instance that has the
     * new header and/or value.
     *
     * @param string $name Case-insensitive header field name to add.
     * @param string|string[] $value Header value(s).
     * @return self
     * @throws \InvalidArgumentException for invalid header names or values.
     */
    public function withAddedHeader($name, $value);

    /**
     * Return an instance without the specified header.
     *
     * Header resolution MUST be done without case-sensitivity.
     *
     * This method MUST be implemented in such a way as to retain the
     * immutability of the message, and MUST return an instance that removes
     * the named header.
     *
     * @param string $name Case-insensitive header field name to remove.
     * @return self
     */
    public function withoutHeader($name);

    /**
     * Gets the body of the message.
     *
     * @return StreamInterface Returns the body as a stream.
     */
    public function getBody();

    /**
     * Return an instance with the specified message body.
     *
     * The body MUST be a StreamInterface object.
     *
     * This method MUST be implemented in such a way as to retain the
     * immutability of the message, and MUST return a new instance that has the
     * new body stream.
     *
     * @param StreamInterface $body Body.
     * @return self
     * @throws \InvalidArgumentException When the body is not valid.
     */
    public function withBody(StreamInterface $body);
}
<?php

namespace Psr\Http\Message;

/**
 * Representation of an outgoing, client-side request.
 *
 * Per the HTTP specification, this interface includes properties for
 * each of the following:
 *
 * - Protocol version
 * - HTTP method
 * - URI
 * - Headers
 * - Message body
 *
 * During construction, implementations MUST attempt to set the Host header from
 * a provided URI if no Host header is provided.
 *
 * Requests are considered immutable; all methods that might change state MUST
 * be implemented such that they retain the internal state of the current
 * message and return an instance that contains the changed state.
 */
interface RequestInterface extends MessageInterface
{
    /**
     * Retrieves the message's request target.
     *
     * Retrieves the message's request-target either as it will appear (for
     * clients), as it appeared at request (for servers), or as it was
     * specified for the instance (see withRequestTarget()).
     *
     * In most cases, this will be the origin-form of the composed URI,
     * unless a value was provided to the concrete implementation (see
     * withRequestTarget() below).
     *
     * If no URI is available, and no request-target has been specifically
     * provided, this method MUST return the string "/".
     *
     * @return string
     */
    public function getRequestTarget();

    /**
     * Return an instance with the specific request-target.
     *
     * If the request needs a non-origin-form request-target — e.g., for
     * specifying an absolute-form, authority-form, or asterisk-form —
     * this method may be used to create an instance with the specified
     * request-target, verbatim.
     *
     * This method MUST be implemented in such a way as to retain the
     * immutability of the message, and MUST return an instance that has the
     * changed request target.
     *
     * @link http://tools.ietf.org/html/rfc7230#section-2.7 (for the various
     *     request-target forms allowed in request messages)
     * @param mixed $requestTarget
     * @return self
     */
    public function withRequestTarget($requestTarget);

    /**
     * Retrieves the HTTP method of the request.
     *
     * @return string Returns the request method.
     */
    public function getMethod();

    /**
     * Return an instance with the provided HTTP method.
     *
     * While HTTP method names are typically all uppercase characters, HTTP
     * method names are case-sensitive and thus implementations SHOULD NOT
     * modify the given string.
     *
     * This method MUST be implemented in such a way as to retain the
     * immutability of the message, and MUST return an instance that has the
     * changed request method.
     *
     * @param string $method Case-sensitive method.
     * @return self
     * @throws \InvalidArgumentException for invalid HTTP methods.
     */
    public function withMethod($method);

    /**
     * Retrieves the URI instance.
     *
     * This method MUST return a UriInterface instance.
     *
     * @link http://tools.ietf.org/html/rfc3986#section-4.3
     * @return UriInterface Returns a UriInterface instance
     *     representing the URI of the request.
     */
    public function getUri();

    /**
     * Returns an instance with the provided URI.
     *
     * This method MUST update the Host header of the returned request by
     * default if the URI contains a host component. If the URI does not
     * contain a host component, any pre-existing Host header MUST be carried
     * over to the returned request.
     *
     * You can opt-in to preserving the original state of the Host header by
     * setting `$preserveHost` to `true`. When `$preserveHost` is set to
     * `true`, this method interacts with the Host header in the following ways:
     *
     * - If the the Host header is missing or empty, and the new URI contains
     *   a host component, this method MUST update the Host header in the returned
     *   request.
     * - If the Host header is missing or empty, and the new URI does not contain a
     *   host component, this method MUST NOT update the Host header in the returned
     *   request.
     * - If a Host header is present and non-empty, this method MUST NOT update
     *   the Host header in the returned request.
     *
     * This method MUST be implemented in such a way as to retain the
     * immutability of the message, and MUST return an instance that has the
     * new UriInterface instance.
     *
     * @link http://tools.ietf.org/html/rfc3986#section-4.3
     * @param UriInterface $uri New request URI to use.
     * @param bool $preserveHost Preserve the original state of the Host header.
     * @return self
     */
    public function withUri(UriInterface $uri, $preserveHost = false);
}
<?php

namespace Psr\Http\Message;

/**
 * Representation of an outgoing, server-side response.
 *
 * Per the HTTP specification, this interface includes properties for
 * each of the following:
 *
 * - Protocol version
 * - Status code and reason phrase
 * - Headers
 * - Message body
 *
 * Responses are considered immutable; all methods that might change state MUST
 * be implemented such that they retain the internal state of the current
 * message and return an instance that contains the changed state.
 */
interface ResponseInterface extends MessageInterface
{
    /**
     * Gets the response status code.
     *
     * The status code is a 3-digit integer result code of the server's attempt
     * to understand and satisfy the request.
     *
     * @return int Status code.
     */
    public function getStatusCode();

    /**
     * Return an instance with the specified status code and, optionally, reason phrase.
     *
     * If no reason phrase is specified, implementations MAY choose to default
     * to the RFC 7231 or IANA recommended reason phrase for the response's
     * status code.
     *
     * This method MUST be implemented in such a way as to retain the
     * immutability of the message, and MUST return an instance that has the
     * updated status and reason phrase.
     *
     * @link http://tools.ietf.org/html/rfc7231#section-6
     * @link http://www.iana.org/assignments/http-status-codes/http-status-codes.xhtml
     * @param int $code The 3-digit integer result code to set.
     * @param string $reasonPhrase The reason phrase to use with the
     *     provided status code; if none is provided, implementations MAY
     *     use the defaults as suggested in the HTTP specification.
     * @return self
     * @throws \InvalidArgumentException For invalid status code arguments.
     */
    public function withStatus($code, $reasonPhrase = '');

    /**
     * Gets the response reason phrase associated with the status code.
     *
     * Because a reason phrase is not a required element in a response
     * status line, the reason phrase value MAY be null. Implementations MAY
     * choose to return the default RFC 7231 recommended reason phrase (or those
     * listed in the IANA HTTP Status Code Registry) for the response's
     * status code.
     *
     * @link http://tools.ietf.org/html/rfc7231#section-6
     * @link http://www.iana.org/assignments/http-status-codes/http-status-codes.xhtml
     * @return string Reason phrase; must return an empty string if none present.
     */
    public function getReasonPhrase();
}
<?php

namespace Psr\Http\Message;

/**
 * Representation of an incoming, server-side HTTP request.
 *
 * Per the HTTP specification, this interface includes properties for
 * each of the following:
 *
 * - Protocol version
 * - HTTP method
 * - URI
 * - Headers
 * - Message body
 *
 * Additionally, it encapsulates all data as it has arrived to the
 * application from the CGI and/or PHP environment, including:
 *
 * - The values represented in $_SERVER.
 * - Any cookies provided (generally via $_COOKIE)
 * - Query string arguments (generally via $_GET, or as parsed via parse_str())
 * - Upload files, if any (as represented by $_FILES)
 * - Deserialized body parameters (generally from $_POST)
 *
 * $_SERVER values MUST be treated as immutable, as they represent application
 * state at the time of request; as such, no methods are provided to allow
 * modification of those values. The other values provide such methods, as they
 * can be restored from $_SERVER or the request body, and may need treatment
 * during the application (e.g., body parameters may be deserialized based on
 * content type).
 *
 * Additionally, this interface recognizes the utility of introspecting a
 * request to derive and match additional parameters (e.g., via URI path
 * matching, decrypting cookie values, deserializing non-form-encoded body
 * content, matching authorization headers to users, etc). These parameters
 * are stored in an "attributes" property.
 *
 * Requests are considered immutable; all methods that might change state MUST
 * be implemented such that they retain the internal state of the current
 * message and return an instance that contains the changed state.
 */
interface ServerRequestInterface extends RequestInterface
{
    /**
     * Retrieve server parameters.
     *
     * Retrieves data related to the incoming request environment,
     * typically derived from PHP's $_SERVER superglobal. The data IS NOT
     * REQUIRED to originate from $_SERVER.
     *
     * @return array
     */
    public function getServerParams();

    /**
     * Retrieve cookies.
     *
     * Retrieves cookies sent by the client to the server.
     *
     * The data MUST be compatible with the structure of the $_COOKIE
     * superglobal.
     *
     * @return array
     */
    public function getCookieParams();

    /**
     * Return an instance with the specified cookies.
     *
     * The data IS NOT REQUIRED to come from the $_COOKIE superglobal, but MUST
     * be compatible with the structure of $_COOKIE. Typically, this data will
     * be injected at instantiation.
     *
     * This method MUST NOT update the related Cookie header of the request
     * instance, nor related values in the server params.
     *
     * This method MUST be implemented in such a way as to retain the
     * immutability of the message, and MUST return an instance that has the
     * updated cookie values.
     *
     * @param array $cookies Array of key/value pairs representing cookies.
     * @return self
     */
    public function withCookieParams(array $cookies);

    /**
     * Retrieve query string arguments.
     *
     * Retrieves the deserialized query string arguments, if any.
     *
     * Note: the query params might not be in sync with the URI or server
     * params. If you need to ensure you are only getting the original
     * values, you may need to parse the query string from `getUri()->getQuery()`
     * or from the `QUERY_STRING` server param.
     *
     * @return array
     */
    public function getQueryParams();

    /**
     * Return an instance with the specified query string arguments.
     *
     * These values SHOULD remain immutable over the course of the incoming
     * request. They MAY be injected during instantiation, such as from PHP's
     * $_GET superglobal, or MAY be derived from some other value such as the
     * URI. In cases where the arguments are parsed from the URI, the data
     * MUST be compatible with what PHP's parse_str() would return for
     * purposes of how duplicate query parameters are handled, and how nested
     * sets are handled.
     *
     * Setting query string arguments MUST NOT change the URI stored by the
     * request, nor the values in the server params.
     *
     * This method MUST be implemented in such a way as to retain the
     * immutability of the message, and MUST return an instance that has the
     * updated query string arguments.
     *
     * @param array $query Array of query string arguments, typically from
     *     $_GET.
     * @return self
     */
    public function withQueryParams(array $query);

    /**
     * Retrieve normalized file upload data.
     *
     * This method returns upload metadata in a normalized tree, with each leaf
     * an instance of Psr\Http\Message\UploadedFileInterface.
     *
     * These values MAY be prepared from $_FILES or the message body during
     * instantiation, or MAY be injected via withUploadedFiles().
     *
     * @return array An array tree of UploadedFileInterface instances; an empty
     *     array MUST be returned if no data is present.
     */
    public function getUploadedFiles();

    /**
     * Create a new instance with the specified uploaded files.
     *
     * This method MUST be implemented in such a way as to retain the
     * immutability of the message, and MUST return an instance that has the
     * updated body parameters.
     *
     * @param array An array tree of UploadedFileInterface instances.
     * @return self
     * @throws \InvalidArgumentException if an invalid structure is provided.
     */
    public function withUploadedFiles(array $uploadedFiles);

    /**
     * Retrieve any parameters provided in the request body.
     *
     * If the request Content-Type is either application/x-www-form-urlencoded
     * or multipart/form-data, and the request method is POST, this method MUST
     * return the contents of $_POST.
     *
     * Otherwise, this method may return any results of deserializing
     * the request body content; as parsing returns structured content, the
     * potential types MUST be arrays or objects only. A null value indicates
     * the absence of body content.
     *
     * @return null|array|object The deserialized body parameters, if any.
     *     These will typically be an array or object.
     */
    public function getParsedBody();

    /**
     * Return an instance with the specified body parameters.
     *
     * These MAY be injected during instantiation.
     *
     * If the request Content-Type is either application/x-www-form-urlencoded
     * or multipart/form-data, and the request method is POST, use this method
     * ONLY to inject the contents of $_POST.
     *
     * The data IS NOT REQUIRED to come from $_POST, but MUST be the results of
     * deserializing the request body content. Deserialization/parsing returns
     * structured data, and, as such, this method ONLY accepts arrays or objects,
     * or a null value if nothing was available to parse.
     *
     * As an example, if content negotiation determines that the request data
     * is a JSON payload, this method could be used to create a request
     * instance with the deserialized parameters.
     *
     * This method MUST be implemented in such a way as to retain the
     * immutability of the message, and MUST return an instance that has the
     * updated body parameters.
     *
     * @param null|array|object $data The deserialized body data. This will
     *     typically be in an array or object.
     * @return self
     * @throws \InvalidArgumentException if an unsupported argument type is
     *     provided.
     */
    public function withParsedBody($data);

    /**
     * Retrieve attributes derived from the request.
     *
     * The request "attributes" may be used to allow injection of any
     * parameters derived from the request: e.g., the results of path
     * match operations; the results of decrypting cookies; the results of
     * deserializing non-form-encoded message bodies; etc. Attributes
     * will be application and request specific, and CAN be mutable.
     *
     * @return array Attributes derived from the request.
     */
    public function getAttributes();

    /**
     * Retrieve a single derived request attribute.
     *
     * Retrieves a single derived request attribute as described in
     * getAttributes(). If the attribute has not been previously set, returns
     * the default value as provided.
     *
     * This method obviates the need for a hasAttribute() method, as it allows
     * specifying a default value to return if the attribute is not found.
     *
     * @see getAttributes()
     * @param string $name The attribute name.
     * @param mixed $default Default value to return if the attribute does not exist.
     * @return mixed
     */
    public function getAttribute($name, $default = null);

    /**
     * Return an instance with the specified derived request attribute.
     *
     * This method allows setting a single derived request attribute as
     * described in getAttributes().
     *
     * This method MUST be implemented in such a way as to retain the
     * immutability of the message, and MUST return an instance that has the
     * updated attribute.
     *
     * @see getAttributes()
     * @param string $name The attribute name.
     * @param mixed $value The value of the attribute.
     * @return self
     */
    public function withAttribute($name, $value);

    /**
     * Return an instance that removes the specified derived request attribute.
     *
     * This method allows removing a single derived request attribute as
     * described in getAttributes().
     *
     * This method MUST be implemented in such a way as to retain the
     * immutability of the message, and MUST return an instance that removes
     * the attribute.
     *
     * @see getAttributes()
     * @param string $name The attribute name.
     * @return self
     */
    public function withoutAttribute($name);
}
<?php

namespace Psr\Http\Message;

/**
 * Describes a data stream.
 *
 * Typically, an instance will wrap a PHP stream; this interface provides
 * a wrapper around the most common operations, including serialization of
 * the entire stream to a string.
 */
interface StreamInterface
{
    /**
     * Reads all data from the stream into a string, from the beginning to end.
     *
     * This method MUST attempt to seek to the beginning of the stream before
     * reading data and read the stream until the end is reached.
     *
     * Warning: This could attempt to load a large amount of data into memory.
     *
     * This method MUST NOT raise an exception in order to conform with PHP's
     * string casting operations.
     *
     * @see http://php.net/manual/en/language.oop5.magic.php#object.tostring
     * @return string
     */
    public function __toString();

    /**
     * Closes the stream and any underlying resources.
     *
     * @return void
     */
    public function close();

    /**
     * Separates any underlying resources from the stream.
     *
     * After the stream has been detached, the stream is in an unusable state.
     *
     * @return resource|null Underlying PHP stream, if any
     */
    public function detach();

    /**
     * Get the size of the stream if known.
     *
     * @return int|null Returns the size in bytes if known, or null if unknown.
     */
    public function getSize();

    /**
     * Returns the current position of the file read/write pointer
     *
     * @return int Position of the file pointer
     * @throws \RuntimeException on error.
     */
    public function tell();

    /**
     * Returns true if the stream is at the end of the stream.
     *
     * @return bool
     */
    public function eof();

    /**
     * Returns whether or not the stream is seekable.
     *
     * @return bool
     */
    public function isSeekable();

    /**
     * Seek to a position in the stream.
     *
     * @link http://www.php.net/manual/en/function.fseek.php
     * @param int $offset Stream offset
     * @param int $whence Specifies how the cursor position will be calculated
     *     based on the seek offset. Valid values are identical to the built-in
     *     PHP $whence values for `fseek()`.  SEEK_SET: Set position equal to
     *     offset bytes SEEK_CUR: Set position to current location plus offset
     *     SEEK_END: Set position to end-of-stream plus offset.
     * @throws \RuntimeException on failure.
     */
    public function seek($offset, $whence = SEEK_SET);

    /**
     * Seek to the beginning of the stream.
     *
     * If the stream is not seekable, this method will raise an exception;
     * otherwise, it will perform a seek(0).
     *
     * @see seek()
     * @link http://www.php.net/manual/en/function.fseek.php
     * @throws \RuntimeException on failure.
     */
    public function rewind();

    /**
     * Returns whether or not the stream is writable.
     *
     * @return bool
     */
    public function isWritable();

    /**
     * Write data to the stream.
     *
     * @param string $string The string that is to be written.
     * @return int Returns the number of bytes written to the stream.
     * @throws \RuntimeException on failure.
     */
    public function write($string);

    /**
     * Returns whether or not the stream is readable.
     *
     * @return bool
     */
    public function isReadable();

    /**
     * Read data from the stream.
     *
     * @param int $length Read up to $length bytes from the object and return
     *     them. Fewer than $length bytes may be returned if underlying stream
     *     call returns fewer bytes.
     * @return string Returns the data read from the stream, or an empty string
     *     if no bytes are available.
     * @throws \RuntimeException if an error occurs.
     */
    public function read($length);

    /**
     * Returns the remaining contents in a string
     *
     * @return string
     * @throws \RuntimeException if unable to read or an error occurs while
     *     reading.
     */
    public function getContents();

    /**
     * Get stream metadata as an associative array or retrieve a specific key.
     *
     * The keys returned are identical to the keys returned from PHP's
     * stream_get_meta_data() function.
     *
     * @link http://php.net/manual/en/function.stream-get-meta-data.php
     * @param string $key Specific metadata to retrieve.
     * @return array|mixed|null Returns an associative array if no key is
     *     provided. Returns a specific key value if a key is provided and the
     *     value is found, or null if the key is not found.
     */
    public function getMetadata($key = null);
}
<?php

namespace Psr\Http\Message;

/**
 * Value object representing a file uploaded through an HTTP request.
 *
 * Instances of this interface are considered immutable; all methods that
 * might change state MUST be implemented such that they retain the internal
 * state of the current instance and return an instance that contains the
 * changed state.
 */
interface UploadedFileInterface
{
    /**
     * Retrieve a stream representing the uploaded file.
     *
     * This method MUST return a StreamInterface instance, representing the
     * uploaded file. The purpose of this method is to allow utilizing native PHP
     * stream functionality to manipulate the file upload, such as
     * stream_copy_to_stream() (though the result will need to be decorated in a
     * native PHP stream wrapper to work with such functions).
     *
     * If the moveTo() method has been called previously, this method MUST raise
     * an exception.
     *
     * @return StreamInterface Stream representation of the uploaded file.
     * @throws \RuntimeException in cases when no stream is available or can be
     *     created.
     */
    public function getStream();

    /**
     * Move the uploaded file to a new location.
     *
     * Use this method as an alternative to move_uploaded_file(). This method is
     * guaranteed to work in both SAPI and non-SAPI environments.
     * Implementations must determine which environment they are in, and use the
     * appropriate method (move_uploaded_file(), rename(), or a stream
     * operation) to perform the operation.
     *
     * $targetPath may be an absolute path, or a relative path. If it is a
     * relative path, resolution should be the same as used by PHP's rename()
     * function.
     *
     * The original file or stream MUST be removed on completion.
     *
     * If this method is called more than once, any subsequent calls MUST raise
     * an exception.
     *
     * When used in an SAPI environment where $_FILES is populated, when writing
     * files via moveTo(), is_uploaded_file() and move_uploaded_file() SHOULD be
     * used to ensure permissions and upload status are verified correctly.
     *
     * If you wish to move to a stream, use getStream(), as SAPI operations
     * cannot guarantee writing to stream destinations.
     *
     * @see http://php.net/is_uploaded_file
     * @see http://php.net/move_uploaded_file
     * @param string $targetPath Path to which to move the uploaded file.
     * @throws \InvalidArgumentException if the $path specified is invalid.
     * @throws \RuntimeException on any error during the move operation, or on
     *     the second or subsequent call to the method.
     */
    public function moveTo($targetPath);
    
    /**
     * Retrieve the file size.
     *
     * Implementations SHOULD return the value stored in the "size" key of
     * the file in the $_FILES array if available, as PHP calculates this based
     * on the actual size transmitted.
     *
     * @return int|null The file size in bytes or null if unknown.
     */
    public function getSize();
    
    /**
     * Retrieve the error associated with the uploaded file.
     *
     * The return value MUST be one of PHP's UPLOAD_ERR_XXX constants.
     *
     * If the file was uploaded successfully, this method MUST return
     * UPLOAD_ERR_OK.
     *
     * Implementations SHOULD return the value stored in the "error" key of
     * the file in the $_FILES array.
     *
     * @see http://php.net/manual/en/features.file-upload.errors.php
     * @return int One of PHP's UPLOAD_ERR_XXX constants.
     */
    public function getError();
    
    /**
     * Retrieve the filename sent by the client.
     *
     * Do not trust the value returned by this method. A client could send
     * a malicious filename with the intention to corrupt or hack your
     * application.
     *
     * Implementations SHOULD return the value stored in the "name" key of
     * the file in the $_FILES array.
     *
     * @return string|null The filename sent by the client or null if none
     *     was provided.
     */
    public function getClientFilename();
    
    /**
     * Retrieve the media type sent by the client.
     *
     * Do not trust the value returned by this method. A client could send
     * a malicious media type with the intention to corrupt or hack your
     * application.
     *
     * Implementations SHOULD return the value stored in the "type" key of
     * the file in the $_FILES array.
     *
     * @return string|null The media type sent by the client or null if none
     *     was provided.
     */
    public function getClientMediaType();
}
<?php
namespace Psr\Http\Message;

/**
 * Value object representing a URI.
 *
 * This interface is meant to represent URIs according to RFC 3986 and to
 * provide methods for most common operations. Additional functionality for
 * working with URIs can be provided on top of the interface or externally.
 * Its primary use is for HTTP requests, but may also be used in other
 * contexts.
 *
 * Instances of this interface are considered immutable; all methods that
 * might change state MUST be implemented such that they retain the internal
 * state of the current instance and return an instance that contains the
 * changed state.
 *
 * Typically the Host header will be also be present in the request message.
 * For server-side requests, the scheme will typically be discoverable in the
 * server parameters.
 *
 * @link http://tools.ietf.org/html/rfc3986 (the URI specification)
 */
interface UriInterface
{
    /**
     * Retrieve the scheme component of the URI.
     *
     * If no scheme is present, this method MUST return an empty string.
     *
     * The value returned MUST be normalized to lowercase, per RFC 3986
     * Section 3.1.
     *
     * The trailing ":" character is not part of the scheme and MUST NOT be
     * added.
     *
     * @see https://tools.ietf.org/html/rfc3986#section-3.1
     * @return string The URI scheme.
     */
    public function getScheme();

    /**
     * Retrieve the authority component of the URI.
     *
     * If no authority information is present, this method MUST return an empty
     * string.
     *
     * The authority syntax of the URI is:
     *
     * <pre>
     * [user-info@]host[:port]
     * </pre>
     *
     * If the port component is not set or is the standard port for the current
     * scheme, it SHOULD NOT be included.
     *
     * @see https://tools.ietf.org/html/rfc3986#section-3.2
     * @return string The URI authority, in "[user-info@]host[:port]" format.
     */
    public function getAuthority();

    /**
     * Retrieve the user information component of the URI.
     *
     * If no user information is present, this method MUST return an empty
     * string.
     *
     * If a user is present in the URI, this will return that value;
     * additionally, if the password is also present, it will be appended to the
     * user value, with a colon (":") separating the values.
     *
     * The trailing "@" character is not part of the user information and MUST
     * NOT be added.
     *
     * @return string The URI user information, in "username[:password]" format.
     */
    public function getUserInfo();

    /**
     * Retrieve the host component of the URI.
     *
     * If no host is present, this method MUST return an empty string.
     *
     * The value returned MUST be normalized to lowercase, per RFC 3986
     * Section 3.2.2.
     *
     * @see http://tools.ietf.org/html/rfc3986#section-3.2.2
     * @return string The URI host.
     */
    public function getHost();

    /**
     * Retrieve the port component of the URI.
     *
     * If a port is present, and it is non-standard for the current scheme,
     * this method MUST return it as an integer. If the port is the standard port
     * used with the current scheme, this method SHOULD return null.
     *
     * If no port is present, and no scheme is present, this method MUST return
     * a null value.
     *
     * If no port is present, but a scheme is present, this method MAY return
     * the standard port for that scheme, but SHOULD return null.
     *
     * @return null|int The URI port.
     */
    public function getPort();

    /**
     * Retrieve the path component of the URI.
     *
     * The path can either be empty or absolute (starting with a slash) or
     * rootless (not starting with a slash). Implementations MUST support all
     * three syntaxes.
     *
     * Normally, the empty path "" and absolute path "/" are considered equal as
     * defined in RFC 7230 Section 2.7.3. But this method MUST NOT automatically
     * do this normalization because in contexts with a trimmed base path, e.g.
     * the front controller, this difference becomes significant. It's the task
     * of the user to handle both "" and "/".
     *
     * The value returned MUST be percent-encoded, but MUST NOT double-encode
     * any characters. To determine what characters to encode, please refer to
     * RFC 3986, Sections 2 and 3.3.
     *
     * As an example, if the value should include a slash ("/") not intended as
     * delimiter between path segments, that value MUST be passed in encoded
     * form (e.g., "%2F") to the instance.
     *
     * @see https://tools.ietf.org/html/rfc3986#section-2
     * @see https://tools.ietf.org/html/rfc3986#section-3.3
     * @return string The URI path.
     */
    public function getPath();

    /**
     * Retrieve the query string of the URI.
     *
     * If no query string is present, this method MUST return an empty string.
     *
     * The leading "?" character is not part of the query and MUST NOT be
     * added.
     *
     * The value returned MUST be percent-encoded, but MUST NOT double-encode
     * any characters. To determine what characters to encode, please refer to
     * RFC 3986, Sections 2 and 3.4.
     *
     * As an example, if a value in a key/value pair of the query string should
     * include an ampersand ("&") not intended as a delimiter between values,
     * that value MUST be passed in encoded form (e.g., "%26") to the instance.
     *
     * @see https://tools.ietf.org/html/rfc3986#section-2
     * @see https://tools.ietf.org/html/rfc3986#section-3.4
     * @return string The URI query string.
     */
    public function getQuery();

    /**
     * Retrieve the fragment component of the URI.
     *
     * If no fragment is present, this method MUST return an empty string.
     *
     * The leading "#" character is not part of the fragment and MUST NOT be
     * added.
     *
     * The value returned MUST be percent-encoded, but MUST NOT double-encode
     * any characters. To determine what characters to encode, please refer to
     * RFC 3986, Sections 2 and 3.5.
     *
     * @see https://tools.ietf.org/html/rfc3986#section-2
     * @see https://tools.ietf.org/html/rfc3986#section-3.5
     * @return string The URI fragment.
     */
    public function getFragment();

    /**
     * Return an instance with the specified scheme.
     *
     * This method MUST retain the state of the current instance, and return
     * an instance that contains the specified scheme.
     *
     * Implementations MUST support the schemes "http" and "https" case
     * insensitively, and MAY accommodate other schemes if required.
     *
     * An empty scheme is equivalent to removing the scheme.
     *
     * @param string $scheme The scheme to use with the new instance.
     * @return self A new instance with the specified scheme.
     * @throws \InvalidArgumentException for invalid or unsupported schemes.
     */
    public function withScheme($scheme);

    /**
     * Return an instance with the specified user information.
     *
     * This method MUST retain the state of the current instance, and return
     * an instance that contains the specified user information.
     *
     * Password is optional, but the user information MUST include the
     * user; an empty string for the user is equivalent to removing user
     * information.
     *
     * @param string $user The user name to use for authority.
     * @param null|string $password The password associated with $user.
     * @return self A new instance with the specified user information.
     */
    public function withUserInfo($user, $password = null);

    /**
     * Return an instance with the specified host.
     *
     * This method MUST retain the state of the current instance, and return
     * an instance that contains the specified host.
     *
     * An empty host value is equivalent to removing the host.
     *
     * @param string $host The hostname to use with the new instance.
     * @return self A new instance with the specified host.
     * @throws \InvalidArgumentException for invalid hostnames.
     */
    public function withHost($host);

    /**
     * Return an instance with the specified port.
     *
     * This method MUST retain the state of the current instance, and return
     * an instance that contains the specified port.
     *
     * Implementations MUST raise an exception for ports outside the
     * established TCP and UDP port ranges.
     *
     * A null value provided for the port is equivalent to removing the port
     * information.
     *
     * @param null|int $port The port to use with the new instance; a null value
     *     removes the port information.
     * @return self A new instance with the specified port.
     * @throws \InvalidArgumentException for invalid ports.
     */
    public function withPort($port);

    /**
     * Return an instance with the specified path.
     *
     * This method MUST retain the state of the current instance, and return
     * an instance that contains the specified path.
     *
     * The path can either be empty or absolute (starting with a slash) or
     * rootless (not starting with a slash). Implementations MUST support all
     * three syntaxes.
     *
     * If the path is intended to be domain-relative rather than path relative then
     * it must begin with a slash ("/"). Paths not starting with a slash ("/")
     * are assumed to be relative to some base path known to the application or
     * consumer.
     *
     * Users can provide both encoded and decoded path characters.
     * Implementations ensure the correct encoding as outlined in getPath().
     *
     * @param string $path The path to use with the new instance.
     * @return self A new instance with the specified path.
     * @throws \InvalidArgumentException for invalid paths.
     */
    public function withPath($path);

    /**
     * Return an instance with the specified query string.
     *
     * This method MUST retain the state of the current instance, and return
     * an instance that contains the specified query string.
     *
     * Users can provide both encoded and decoded query characters.
     * Implementations ensure the correct encoding as outlined in getQuery().
     *
     * An empty query string value is equivalent to removing the query string.
     *
     * @param string $query The query string to use with the new instance.
     * @return self A new instance with the specified query string.
     * @throws \InvalidArgumentException for invalid query strings.
     */
    public function withQuery($query);

    /**
     * Return an instance with the specified URI fragment.
     *
     * This method MUST retain the state of the current instance, and return
     * an instance that contains the specified URI fragment.
     *
     * Users can provide both encoded and decoded fragment characters.
     * Implementations ensure the correct encoding as outlined in getFragment().
     *
     * An empty fragment value is equivalent to removing the fragment.
     *
     * @param string $fragment The fragment to use with the new instance.
     * @return self A new instance with the specified fragment.
     */
    public function withFragment($fragment);

    /**
     * Return the string representation as a URI reference.
     *
     * Depending on which components of the URI are present, the resulting
     * string is either a full URI or relative reference according to RFC 3986,
     * Section 4.1. The method concatenates the various components of the URI,
     * using the appropriate delimiters:
     *
     * - If a scheme is present, it MUST be suffixed by ":".
     * - If an authority is present, it MUST be prefixed by "//".
     * - The path can be concatenated without delimiters. But there are two
     *   cases where the path has to be adjusted to make the URI reference
     *   valid as PHP does not allow to throw an exception in __toString():
     *     - If the path is rootless and an authority is present, the path MUST
     *       be prefixed by "/".
     *     - If the path is starting with more than one "/" and no authority is
     *       present, the starting slashes MUST be reduced to one.
     * - If a query is present, it MUST be prefixed by "?".
     * - If a fragment is present, it MUST be prefixed by "#".
     *
     * @see http://tools.ietf.org/html/rfc3986#section-4.1
     * @return string
     */
    public function __toString();
}
# CHANGELOG

## 1.2.3 - 2016-02-18

* Fixed support in `GuzzleHttp\Psr7\CachingStream` for seeking forward on remote
  streams, which can sometimes return fewer bytes than requested with `fread`.
* Fixed handling of gzipped responses with FNAME headers.

## 1.2.2 - 2016-01-22

* Added support for URIs without any authority.
* Added support for HTTP 451 'Unavailable For Legal Reasons.'
* Added support for using '0' as a filename.
* Added support for including non-standard ports in Host headers.

## 1.2.1 - 2015-11-02

* Now supporting negative offsets when seeking to SEEK_END.

## 1.2.0 - 2015-08-15

* Body as `"0"` is now properly added to a response.
* Now allowing forward seeking in CachingStream.
* Now properly parsing HTTP requests that contain proxy targets in
  `parse_request`.
* functions.php is now conditionally required.
* user-info is no longer dropped when resolving URIs.

## 1.1.0 - 2015-06-24

* URIs can now be relative.
* `multipart/form-data` headers are now overridden case-insensitively.
* URI paths no longer encode the following characters because they are allowed
  in URIs: "(", ")", "*", "!", "'"
* A port is no longer added to a URI when the scheme is missing and no port is
  present.

## 1.0.0 - 2015-05-19

Initial release.

Currently unsupported:

- `Psr\Http\Message\ServerRequestInterface`
- `Psr\Http\Message\UploadedFileInterface`
{
    "name": "guzzlehttp/psr7",
    "type": "library",
    "description": "PSR-7 message implementation",
    "keywords": ["message", "stream", "http", "uri"],
    "license": "MIT",
    "authors": [
        {
            "name": "Michael Dowling",
            "email": "mtdowling@gmail.com",
            "homepage": "https://github.com/mtdowling"
        }
    ],
    "require": {
        "php": ">=5.4.0",
        "psr/http-message": "~1.0"
    },
    "require-dev": {
        "phpunit/phpunit": "~4.0"
    },
    "provide": {
        "psr/http-message-implementation": "1.0"
    },
    "autoload": {
        "psr-4": {
            "GuzzleHttp\\Psr7\\": "src/"
        },
        "files": ["src/functions_include.php"]
    },
    "extra": {
        "branch-alias": {
            "dev-master": "1.0-dev"
        }
    }
}
Copyright (c) 2015 Michael Dowling, https://github.com/mtdowling <mtdowling@gmail.com>

Permission is hereby granted, free of charge, to any person obtaining a copy
of this software and associated documentation files (the "Software"), to deal
in the Software without restriction, including without limitation the rights
to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
copies of the Software, and to permit persons to whom the Software is
furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in
all copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
THE SOFTWARE.
all: clean test

test:
	vendor/bin/phpunit $(TEST)

coverage:
	vendor/bin/phpunit --coverage-html=artifacts/coverage $(TEST)

view-coverage:
	open artifacts/coverage/index.html

check-tag:
	$(if $(TAG),,$(error TAG is not defined. Pass via "make tag TAG=4.2.1"))

tag: check-tag
	@echo Tagging $(TAG)
	chag update $(TAG)
	git commit -a -m '$(TAG) release'
	chag tag
	@echo "Release has been created. Push using 'make release'"
	@echo "Changes made in the release commit"
	git diff HEAD~1 HEAD

release: check-tag
	git push origin master
	git push origin $(TAG)

clean:
	rm -rf artifacts/*
<?xml version="1.0" encoding="UTF-8"?>
<phpunit bootstrap="./tests/bootstrap.php"
         colors="true">
  <testsuites>
    <testsuite>
      <directory>tests</directory>
    </testsuite>
  </testsuites>
  <filter>
    <whitelist>
      <directory suffix=".php">src</directory>
      <exclude>
        <directory suffix="Interface.php">src/</directory>
      </exclude>
    </whitelist>
  </filter>
</phpunit>
# PSR-7 Message Implementation

This repository contains a partial [PSR-7](http://www.php-fig.org/psr/psr-7/)
message implementation, several stream decorators, and some helpful
functionality like query string parsing.  Currently missing
ServerRequestInterface and UploadedFileInterface; a pull request for these features is welcome.


[![Build Status](https://travis-ci.org/guzzle/psr7.svg?branch=master)](https://travis-ci.org/guzzle/psr7)


# Stream implementation

This package comes with a number of stream implementations and stream
decorators.


## AppendStream

`GuzzleHttp\Psr7\AppendStream`

Reads from multiple streams, one after the other.

```php
use GuzzleHttp\Psr7;

$a = Psr7\stream_for('abc, ');
$b = Psr7\stream_for('123.');
$composed = new Psr7\AppendStream([$a, $b]);

$composed->addStream(Psr7\stream_for(' Above all listen to me'));

echo $composed(); // abc, 123. Above all listen to me.
```


## BufferStream

`GuzzleHttp\Psr7\BufferStream`

Provides a buffer stream that can be written to fill a buffer, and read
from to remove bytes from the buffer.

This stream returns a "hwm" metadata value that tells upstream consumers
what the configured high water mark of the stream is, or the maximum
preferred size of the buffer.

```php
use GuzzleHttp\Psr7;

// When more than 1024 bytes are in the buffer, it will begin returning
// false to writes. This is an indication that writers should slow down.
$buffer = new Psr7\BufferStream(1024);
```


## CachingStream

The CachingStream is used to allow seeking over previously read bytes on
non-seekable streams. This can be useful when transferring a non-seekable
entity body fails due to needing to rewind the stream (for example, resulting
from a redirect). Data that is read from the remote stream will be buffered in
a PHP temp stream so that previously read bytes are cached first in memory,
then on disk.

```php
use GuzzleHttp\Psr7;

$original = Psr7\stream_for(fopen('http://www.google.com', 'r'));
$stream = new Psr7\CachingStream($original);

$stream->read(1024);
echo $stream->tell();
// 1024

$stream->seek(0);
echo $stream->tell();
// 0
```


## DroppingStream

`GuzzleHttp\Psr7\DroppingStream`

Stream decorator that begins dropping data once the size of the underlying
stream becomes too full.

```php
use GuzzleHttp\Psr7;

// Create an empty stream
$stream = Psr7\stream_for();

// Start dropping data when the stream has more than 10 bytes
$dropping = new Psr7\DroppingStream($stream, 10);

$stream->write('01234567890123456789');
echo $stream; // 0123456789
```


## FnStream

`GuzzleHttp\Psr7\FnStream`

Compose stream implementations based on a hash of functions.

Allows for easy testing and extension of a provided stream without needing 
to create a concrete class for a simple extension point.

```php

use GuzzleHttp\Psr7;

$stream = Psr7\stream_for('hi');
$fnStream = Psr7\FnStream::decorate($stream, [
    'rewind' => function () use ($stream) {
        echo 'About to rewind - ';
        $stream->rewind();
        echo 'rewound!';
    }
]);

$fnStream->rewind();
// Outputs: About to rewind - rewound!
```


## InflateStream

`GuzzleHttp\Psr7\InflateStream`

Uses PHP's zlib.inflate filter to inflate deflate or gzipped content.

This stream decorator skips the first 10 bytes of the given stream to remove
the gzip header, converts the provided stream to a PHP stream resource,
then appends the zlib.inflate filter. The stream is then converted back
to a Guzzle stream resource to be used as a Guzzle stream.


## LazyOpenStream

`GuzzleHttp\Psr7\LazyOpenStream`

Lazily reads or writes to a file that is opened only after an IO operation
take place on the stream.

```php
use GuzzleHttp\Psr7;

$stream = new Psr7\LazyOpenStream('/path/to/file', 'r');
// The file has not yet been opened...

echo $stream->read(10);
// The file is opened and read from only when needed.
```


## LimitStream

`GuzzleHttp\Psr7\LimitStream`

LimitStream can be used to read a subset or slice of an existing stream object.
This can be useful for breaking a large file into smaller pieces to be sent in
chunks (e.g. Amazon S3's multipart upload API).

```php
use GuzzleHttp\Psr7;

$original = Psr7\stream_for(fopen('/tmp/test.txt', 'r+'));
echo $original->getSize();
// >>> 1048576

// Limit the size of the body to 1024 bytes and start reading from byte 2048
$stream = new Psr7\LimitStream($original, 1024, 2048);
echo $stream->getSize();
// >>> 1024
echo $stream->tell();
// >>> 0
```


## MultipartStream

`GuzzleHttp\Psr7\MultipartStream`

Stream that when read returns bytes for a streaming multipart or
multipart/form-data stream.


## NoSeekStream

`GuzzleHttp\Psr7\NoSeekStream`

NoSeekStream wraps a stream and does not allow seeking.

```php
use GuzzleHttp\Psr7;

$original = Psr7\stream_for('foo');
$noSeek = new Psr7\NoSeekStream($original);

echo $noSeek->read(3);
// foo
var_export($noSeek->isSeekable());
// false
$noSeek->seek(0);
var_export($noSeek->read(3));
// NULL
```


## PumpStream

`GuzzleHttp\Psr7\PumpStream`

Provides a read only stream that pumps data from a PHP callable.

When invoking the provided callable, the PumpStream will pass the amount of
data requested to read to the callable. The callable can choose to ignore
this value and return fewer or more bytes than requested. Any extra data
returned by the provided callable is buffered internally until drained using
the read() function of the PumpStream. The provided callable MUST return
false when there is no more data to read.


## Implementing stream decorators

Creating a stream decorator is very easy thanks to the
`GuzzleHttp\Psr7\StreamDecoratorTrait`. This trait provides methods that
implement `Psr\Http\Message\StreamInterface` by proxying to an underlying
stream. Just `use` the `StreamDecoratorTrait` and implement your custom
methods.

For example, let's say we wanted to call a specific function each time the last
byte is read from a stream. This could be implemented by overriding the
`read()` method.

```php
use Psr\Http\Message\StreamInterface;
use GuzzleHttp\Psr7\StreamDecoratorTrait;

class EofCallbackStream implements StreamInterface
{
    use StreamDecoratorTrait;

    private $callback;

    public function __construct(StreamInterface $stream, callable $cb)
    {
        $this->stream = $stream;
        $this->callback = $cb;
    }

    public function read($length)
    {
        $result = $this->stream->read($length);

        // Invoke the callback when EOF is hit.
        if ($this->eof()) {
            call_user_func($this->callback);
        }

        return $result;
    }
}
```

This decorator could be added to any existing stream and used like so:

```php
use GuzzleHttp\Psr7;

$original = Psr7\stream_for('foo');

$eofStream = new EofCallbackStream($original, function () {
    echo 'EOF!';
});

$eofStream->read(2);
$eofStream->read(1);
// echoes "EOF!"
$eofStream->seek(0);
$eofStream->read(3);
// echoes "EOF!"
```


## PHP StreamWrapper

You can use the `GuzzleHttp\Psr7\StreamWrapper` class if you need to use a
PSR-7 stream as a PHP stream resource.

Use the `GuzzleHttp\Psr7\StreamWrapper::getResource()` method to create a PHP
stream from a PSR-7 stream.

```php
use GuzzleHttp\Psr7\StreamWrapper;

$stream = GuzzleHttp\Psr7\stream_for('hello!');
$resource = StreamWrapper::getResource($stream);
echo fread($resource, 6); // outputs hello!
```


# Function API

There are various functions available under the `GuzzleHttp\Psr7` namespace.


## `function str`

`function str(MessageInterface $message)`

Returns the string representation of an HTTP message.

```php
$request = new GuzzleHttp\Psr7\Request('GET', 'http://example.com');
echo GuzzleHttp\Psr7\str($request);
```


## `function uri_for`

`function uri_for($uri)`

This function accepts a string or `Psr\Http\Message\UriInterface` and returns a
UriInterface for the given value. If the value is already a `UriInterface`, it
is returned as-is.

```php
$uri = GuzzleHttp\Psr7\uri_for('http://example.com');
assert($uri === GuzzleHttp\Psr7\uri_for($uri));
```


## `function stream_for`

`function stream_for($resource = '', array $options = [])`

Create a new stream based on the input type.

Options is an associative array that can contain the following keys:

* - metadata: Array of custom metadata.
* - size: Size of the stream.

This method accepts the following `$resource` types:

- `Psr\Http\Message\StreamInterface`: Returns the value as-is.
- `string`: Creates a stream object that uses the given string as the contents.
- `resource`: Creates a stream object that wraps the given PHP stream resource.
- `Iterator`: If the provided value implements `Iterator`, then a read-only
  stream object will be created that wraps the given iterable. Each time the
  stream is read from, data from the iterator will fill a buffer and will be
  continuously called until the buffer is equal to the requested read size.
  Subsequent read calls will first read from the buffer and then call `next`
  on the underlying iterator until it is exhausted.
- `object` with `__toString()`: If the object has the `__toString()` method,
  the object will be cast to a string and then a stream will be returned that
  uses the string value.
- `NULL`: When `null` is passed, an empty stream object is returned.
- `callable` When a callable is passed, a read-only stream object will be
  created that invokes the given callable. The callable is invoked with the
  number of suggested bytes to read. The callable can return any number of
  bytes, but MUST return `false` when there is no more data to return. The
  stream object that wraps the callable will invoke the callable until the
  number of requested bytes are available. Any additional bytes will be
  buffered and used in subsequent reads.

```php
$stream = GuzzleHttp\Psr7\stream_for('foo');
$stream = GuzzleHttp\Psr7\stream_for(fopen('/path/to/file', 'r'));

$generator function ($bytes) {
    for ($i = 0; $i < $bytes; $i++) {
        yield ' ';
    }
}

$stream = GuzzleHttp\Psr7\stream_for($generator(100));
```


## `function parse_header`

`function parse_header($header)`

Parse an array of header values containing ";" separated data into an array of
associative arrays representing the header key value pair data of the header.
When a parameter does not contain a value, but just contains a key, this
function will inject a key with a '' string value.


## `function normalize_header`

`function normalize_header($header)`

Converts an array of header values that may contain comma separated headers
into an array of headers with no comma separated values.


## `function modify_request`

`function modify_request(RequestInterface $request, array $changes)`

Clone and modify a request with the given changes. This method is useful for
reducing the number of clones needed to mutate a message.

The changes can be one of:

- method: (string) Changes the HTTP method.
- set_headers: (array) Sets the given headers.
- remove_headers: (array) Remove the given headers.
- body: (mixed) Sets the given body.
- uri: (UriInterface) Set the URI.
- query: (string) Set the query string value of the URI.
- version: (string) Set the protocol version.


## `function rewind_body`

`function rewind_body(MessageInterface $message)`

Attempts to rewind a message body and throws an exception on failure. The body
of the message will only be rewound if a call to `tell()` returns a value other
than `0`.


## `function try_fopen`

`function try_fopen($filename, $mode)`

Safely opens a PHP stream resource using a filename.

When fopen fails, PHP normally raises a warning. This function adds an error
handler that checks for errors and throws an exception instead.


## `function copy_to_string`

`function copy_to_string(StreamInterface $stream, $maxLen = -1)`

Copy the contents of a stream into a string until the given number of bytes
have been read.


## `function copy_to_stream`

`function copy_to_stream(StreamInterface $source, StreamInterface $dest, $maxLen = -1)`

Copy the contents of a stream into another stream until the given number of
bytes have been read.


## `function hash`

`function hash(StreamInterface $stream, $algo, $rawOutput = false)`

Calculate a hash of a Stream. This method reads the entire stream to calculate
a rolling hash (based on PHP's hash_init functions).


## `function readline`

`function readline(StreamInterface $stream, $maxLength = null)`

Read a line from the stream up to the maximum allowed buffer length.


## `function parse_request`

`function parse_request($message)`

Parses a request message string into a request object.


## `function parse_response`

`function parse_response($message)`

Parses a response message string into a response object.


## `function parse_query`

`function parse_query($str, $urlEncoding = true)`

Parse a query string into an associative array.

If multiple values are found for the same key, the value of that key value pair
will become an array. This function does not parse nested PHP style arrays into
an associative array (e.g., `foo[a]=1&foo[b]=2` will be parsed into
`['foo[a]' => '1', 'foo[b]' => '2']`).


## `function build_query`

`function build_query(array $params, $encoding = PHP_QUERY_RFC3986)`

Build a query string from an array of key value pairs.

This function can use the return value of parseQuery() to build a query string.
This function does not modify the provided keys when an array is encountered
(like http_build_query would).


## `function mimetype_from_filename`

`function mimetype_from_filename($filename)`

Determines the mimetype of a file by looking at its extension.


## `function mimetype_from_extension`

`function mimetype_from_extension($extension)`

Maps a file extensions to a mimetype.


# Static URI methods

The `GuzzleHttp\Psr7\Uri` class has several static methods to manipulate URIs.


## `GuzzleHttp\Psr7\Uri::removeDotSegments`

`public static function removeDotSegments($path) -> UriInterface`

Removes dot segments from a path and returns the new path.

See http://tools.ietf.org/html/rfc3986#section-5.2.4


## `GuzzleHttp\Psr7\Uri::resolve`

`public static function resolve(UriInterface $base, $rel) -> UriInterface`

Resolve a base URI with a relative URI and return a new URI.

See http://tools.ietf.org/html/rfc3986#section-5


## `GuzzleHttp\Psr7\Uri::withQueryValue`

`public static function withQueryValue(UriInterface $uri, $key, $value) -> UriInterface`

Create a new URI with a specific query string value.

Any existing query string values that exactly match the provided key are
removed and replaced with the given key value pair.

Note: this function will convert "=" to "%3D" and "&" to "%26".


## `GuzzleHttp\Psr7\Uri::withoutQueryValue`

`public static function withoutQueryValue(UriInterface $uri, $key, $value) -> UriInterface`

Create a new URI with a specific query string value removed.

Any existing query string values that exactly match the provided key are
removed.

Note: this function will convert "=" to "%3D" and "&" to "%26".


## `GuzzleHttp\Psr7\Uri::fromParts`

`public static function fromParts(array $parts) -> UriInterface`

Create a `GuzzleHttp\Psr7\Uri` object from a hash of `parse_url` parts.


# Not Implemented

A few aspects of PSR-7 are not implemented in this project. A pull request for
any of these features is welcome:

- `Psr\Http\Message\ServerRequestInterface`
- `Psr\Http\Message\UploadedFileInterface`
<?php
namespace GuzzleHttp\Psr7;

use Psr\Http\Message\StreamInterface;

/**
 * Reads from multiple streams, one after the other.
 *
 * This is a read-only stream decorator.
 */
class AppendStream implements StreamInterface
{
    /** @var StreamInterface[] Streams being decorated */
    private $streams = [];

    private $seekable = true;
    private $current = 0;
    private $pos = 0;
    private $detached = false;

    /**
     * @param StreamInterface[] $streams Streams to decorate. Each stream must
     *                                   be readable.
     */
    public function __construct(array $streams = [])
    {
        foreach ($streams as $stream) {
            $this->addStream($stream);
        }
    }

    public function __toString()
    {
        try {
            $this->rewind();
            return $this->getContents();
        } catch (\Exception $e) {
            return '';
        }
    }

    /**
     * Add a stream to the AppendStream
     *
     * @param StreamInterface $stream Stream to append. Must be readable.
     *
     * @throws \InvalidArgumentException if the stream is not readable
     */
    public function addStream(StreamInterface $stream)
    {
        if (!$stream->isReadable()) {
            throw new \InvalidArgumentException('Each stream must be readable');
        }

        // The stream is only seekable if all streams are seekable
        if (!$stream->isSeekable()) {
            $this->seekable = false;
        }

        $this->streams[] = $stream;
    }

    public function getContents()
    {
        return copy_to_string($this);
    }

    /**
     * Closes each attached stream.
     *
     * {@inheritdoc}
     */
    public function close()
    {
        $this->pos = $this->current = 0;

        foreach ($this->streams as $stream) {
            $stream->close();
        }

        $this->streams = [];
    }

    /**
     * Detaches each attached stream
     *
     * {@inheritdoc}
     */
    public function detach()
    {
        $this->close();
        $this->detached = true;
    }

    public function tell()
    {
        return $this->pos;
    }

    /**
     * Tries to calculate the size by adding the size of each stream.
     *
     * If any of the streams do not return a valid number, then the size of the
     * append stream cannot be determined and null is returned.
     *
     * {@inheritdoc}
     */
    public function getSize()
    {
        $size = 0;

        foreach ($this->streams as $stream) {
            $s = $stream->getSize();
            if ($s === null) {
                return null;
            }
            $size += $s;
        }

        return $size;
    }

    public function eof()
    {
        return !$this->streams ||
            ($this->current >= count($this->streams) - 1 &&
             $this->streams[$this->current]->eof());
    }

    public function rewind()
    {
        $this->seek(0);
    }

    /**
     * Attempts to seek to the given position. Only supports SEEK_SET.
     *
     * {@inheritdoc}
     */
    public function seek($offset, $whence = SEEK_SET)
    {
        if (!$this->seekable) {
            throw new \RuntimeException('This AppendStream is not seekable');
        } elseif ($whence !== SEEK_SET) {
            throw new \RuntimeException('The AppendStream can only seek with SEEK_SET');
        }

        $this->pos = $this->current = 0;

        // Rewind each stream
        foreach ($this->streams as $i => $stream) {
            try {
                $stream->rewind();
            } catch (\Exception $e) {
                throw new \RuntimeException('Unable to seek stream '
                    . $i . ' of the AppendStream', 0, $e);
            }
        }

        // Seek to the actual position by reading from each stream
        while ($this->pos < $offset && !$this->eof()) {
            $result = $this->read(min(8096, $offset - $this->pos));
            if ($result === '') {
                break;
            }
        }
    }

    /**
     * Reads from all of the appended streams until the length is met or EOF.
     *
     * {@inheritdoc}
     */
    public function read($length)
    {
        $buffer = '';
        $total = count($this->streams) - 1;
        $remaining = $length;
        $progressToNext = false;

        while ($remaining > 0) {

            // Progress to the next stream if needed.
            if ($progressToNext || $this->streams[$this->current]->eof()) {
                $progressToNext = false;
                if ($this->current === $total) {
                    break;
                }
                $this->current++;
            }

            $result = $this->streams[$this->current]->read($remaining);

            // Using a loose comparison here to match on '', false, and null
            if ($result == null) {
                $progressToNext = true;
                continue;
            }

            $buffer .= $result;
            $remaining = $length - strlen($buffer);
        }

        $this->pos += strlen($buffer);

        return $buffer;
    }

    public function isReadable()
    {
        return true;
    }

    public function isWritable()
    {
        return false;
    }

    public function isSeekable()
    {
        return $this->seekable;
    }

    public function write($string)
    {
        throw new \RuntimeException('Cannot write to an AppendStream');
    }

    public function getMetadata($key = null)
    {
        return $key ? null : [];
    }
}
<?php
namespace GuzzleHttp\Psr7;

use Psr\Http\Message\StreamInterface;

/**
 * Provides a buffer stream that can be written to to fill a buffer, and read
 * from to remove bytes from the buffer.
 *
 * This stream returns a "hwm" metadata value that tells upstream consumers
 * what the configured high water mark of the stream is, or the maximum
 * preferred size of the buffer.
 */
class BufferStream implements StreamInterface
{
    private $hwm;
    private $buffer = '';

    /**
     * @param int $hwm High water mark, representing the preferred maximum
     *                 buffer size. If the size of the buffer exceeds the high
     *                 water mark, then calls to write will continue to succeed
     *                 but will return false to inform writers to slow down
     *                 until the buffer has been drained by reading from it.
     */
    public function __construct($hwm = 16384)
    {
        $this->hwm = $hwm;
    }

    public function __toString()
    {
        return $this->getContents();
    }

    public function getContents()
    {
        $buffer = $this->buffer;
        $this->buffer = '';

        return $buffer;
    }

    public function close()
    {
        $this->buffer = '';
    }

    public function detach()
    {
        $this->close();
    }

    public function getSize()
    {
        return strlen($this->buffer);
    }

    public function isReadable()
    {
        return true;
    }

    public function isWritable()
    {
        return true;
    }

    public function isSeekable()
    {
        return false;
    }

    public function rewind()
    {
        $this->seek(0);
    }

    public function seek($offset, $whence = SEEK_SET)
    {
        throw new \RuntimeException('Cannot seek a BufferStream');
    }

    public function eof()
    {
        return strlen($this->buffer) === 0;
    }

    public function tell()
    {
        throw new \RuntimeException('Cannot determine the position of a BufferStream');
    }

    /**
     * Reads data from the buffer.
     */
    public function read($length)
    {
        $currentLength = strlen($this->buffer);

        if ($length >= $currentLength) {
            // No need to slice the buffer because we don't have enough data.
            $result = $this->buffer;
            $this->buffer = '';
        } else {
            // Slice up the result to provide a subset of the buffer.
            $result = substr($this->buffer, 0, $length);
            $this->buffer = substr($this->buffer, $length);
        }

        return $result;
    }

    /**
     * Writes data to the buffer.
     */
    public function write($string)
    {
        $this->buffer .= $string;

        // TODO: What should happen here?
        if (strlen($this->buffer) >= $this->hwm) {
            return false;
        }

        return strlen($string);
    }

    public function getMetadata($key = null)
    {
        if ($key == 'hwm') {
            return $this->hwm;
        }

        return $key ? null : [];
    }
}
<?php
namespace GuzzleHttp\Psr7;

use Psr\Http\Message\StreamInterface;

/**
 * Stream decorator that can cache previously read bytes from a sequentially
 * read stream.
 */
class CachingStream implements StreamInterface
{
    use StreamDecoratorTrait;

    /** @var StreamInterface Stream being wrapped */
    private $remoteStream;

    /** @var int Number of bytes to skip reading due to a write on the buffer */
    private $skipReadBytes = 0;

    /**
     * We will treat the buffer object as the body of the stream
     *
     * @param StreamInterface $stream Stream to cache
     * @param StreamInterface $target Optionally specify where data is cached
     */
    public function __construct(
        StreamInterface $stream,
        StreamInterface $target = null
    ) {
        $this->remoteStream = $stream;
        $this->stream = $target ?: new Stream(fopen('php://temp', 'r+'));
    }

    public function getSize()
    {
        return max($this->stream->getSize(), $this->remoteStream->getSize());
    }

    public function rewind()
    {
        $this->seek(0);
    }

    public function seek($offset, $whence = SEEK_SET)
    {
        if ($whence == SEEK_SET) {
            $byte = $offset;
        } elseif ($whence == SEEK_CUR) {
            $byte = $offset + $this->tell();
        } elseif ($whence == SEEK_END) {
            $size = $this->remoteStream->getSize();
            if ($size === null) {
                $size = $this->cacheEntireStream();
            }
            $byte = $size + $offset;
        } else {
            throw new \InvalidArgumentException('Invalid whence');
        }

        $diff = $byte - $this->stream->getSize();

        if ($diff > 0) {
            // Read the remoteStream until we have read in at least the amount
            // of bytes requested, or we reach the end of the file.
            while ($diff > 0 && !$this->remoteStream->eof()) {
                $this->read($diff);
                $diff = $byte - $this->stream->getSize();
            }
        } else {
            // We can just do a normal seek since we've already seen this byte.
            $this->stream->seek($byte);
        }
    }

    public function read($length)
    {
        // Perform a regular read on any previously read data from the buffer
        $data = $this->stream->read($length);
        $remaining = $length - strlen($data);

        // More data was requested so read from the remote stream
        if ($remaining) {
            // If data was written to the buffer in a position that would have
            // been filled from the remote stream, then we must skip bytes on
            // the remote stream to emulate overwriting bytes from that
            // position. This mimics the behavior of other PHP stream wrappers.
            $remoteData = $this->remoteStream->read(
                $remaining + $this->skipReadBytes
            );

            if ($this->skipReadBytes) {
                $len = strlen($remoteData);
                $remoteData = substr($remoteData, $this->skipReadBytes);
                $this->skipReadBytes = max(0, $this->skipReadBytes - $len);
            }

            $data .= $remoteData;
            $this->stream->write($remoteData);
        }

        return $data;
    }

    public function write($string)
    {
        // When appending to the end of the currently read stream, you'll want
        // to skip bytes from being read from the remote stream to emulate
        // other stream wrappers. Basically replacing bytes of data of a fixed
        // length.
        $overflow = (strlen($string) + $this->tell()) - $this->remoteStream->tell();
        if ($overflow > 0) {
            $this->skipReadBytes += $overflow;
        }

        return $this->stream->write($string);
    }

    public function eof()
    {
        return $this->stream->eof() && $this->remoteStream->eof();
    }

    /**
     * Close both the remote stream and buffer stream
     */
    public function close()
    {
        $this->remoteStream->close() && $this->stream->close();
    }

    private function cacheEntireStream()
    {
        $target = new FnStream(['write' => 'strlen']);
        copy_to_stream($this, $target);

        return $this->tell();
    }
}
<?php
namespace GuzzleHttp\Psr7;

use Psr\Http\Message\StreamInterface;

/**
 * Stream decorator that begins dropping data once the size of the underlying
 * stream becomes too full.
 */
class DroppingStream implements StreamInterface
{
    use StreamDecoratorTrait;

    private $maxLength;

    /**
     * @param StreamInterface $stream    Underlying stream to decorate.
     * @param int             $maxLength Maximum size before dropping data.
     */
    public function __construct(StreamInterface $stream, $maxLength)
    {
        $this->stream = $stream;
        $this->maxLength = $maxLength;
    }

    public function write($string)
    {
        $diff = $this->maxLength - $this->stream->getSize();

        // Begin returning 0 when the underlying stream is too large.
        if ($diff <= 0) {
            return 0;
        }

        // Write the stream or a subset of the stream if needed.
        if (strlen($string) < $diff) {
            return $this->stream->write($string);
        }

        return $this->stream->write(substr($string, 0, $diff));
    }
}
<?php
namespace GuzzleHttp\Psr7;

use Psr\Http\Message\StreamInterface;

/**
 * Compose stream implementations based on a hash of functions.
 *
 * Allows for easy testing and extension of a provided stream without needing
 * to create a concrete class for a simple extension point.
 */
class FnStream implements StreamInterface
{
    /** @var array */
    private $methods;

    /** @var array Methods that must be implemented in the given array */
    private static $slots = ['__toString', 'close', 'detach', 'rewind',
        'getSize', 'tell', 'eof', 'isSeekable', 'seek', 'isWritable', 'write',
        'isReadable', 'read', 'getContents', 'getMetadata'];

    /**
     * @param array $methods Hash of method name to a callable.
     */
    public function __construct(array $methods)
    {
        $this->methods = $methods;

        // Create the functions on the class
        foreach ($methods as $name => $fn) {
            $this->{'_fn_' . $name} = $fn;
        }
    }

    /**
     * Lazily determine which methods are not implemented.
     * @throws \BadMethodCallException
     */
    public function __get($name)
    {
        throw new \BadMethodCallException(str_replace('_fn_', '', $name)
            . '() is not implemented in the FnStream');
    }

    /**
     * The close method is called on the underlying stream only if possible.
     */
    public function __destruct()
    {
        if (isset($this->_fn_close)) {
            call_user_func($this->_fn_close);
        }
    }

    /**
     * Adds custom functionality to an underlying stream by intercepting
     * specific method calls.
     *
     * @param StreamInterface $stream  Stream to decorate
     * @param array           $methods Hash of method name to a closure
     *
     * @return FnStream
     */
    public static function decorate(StreamInterface $stream, array $methods)
    {
        // If any of the required methods were not provided, then simply
        // proxy to the decorated stream.
        foreach (array_diff(self::$slots, array_keys($methods)) as $diff) {
            $methods[$diff] = [$stream, $diff];
        }

        return new self($methods);
    }

    public function __toString()
    {
        return call_user_func($this->_fn___toString);
    }

    public function close()
    {
        return call_user_func($this->_fn_close);
    }

    public function detach()
    {
        return call_user_func($this->_fn_detach);
    }

    public function getSize()
    {
        return call_user_func($this->_fn_getSize);
    }

    public function tell()
    {
        return call_user_func($this->_fn_tell);
    }

    public function eof()
    {
        return call_user_func($this->_fn_eof);
    }

    public function isSeekable()
    {
        return call_user_func($this->_fn_isSeekable);
    }

    public function rewind()
    {
        call_user_func($this->_fn_rewind);
    }

    public function seek($offset, $whence = SEEK_SET)
    {
        call_user_func($this->_fn_seek, $offset, $whence);
    }

    public function isWritable()
    {
        return call_user_func($this->_fn_isWritable);
    }

    public function write($string)
    {
        return call_user_func($this->_fn_write, $string);
    }

    public function isReadable()
    {
        return call_user_func($this->_fn_isReadable);
    }

    public function read($length)
    {
        return call_user_func($this->_fn_read, $length);
    }

    public function getContents()
    {
        return call_user_func($this->_fn_getContents);
    }

    public function getMetadata($key = null)
    {
        return call_user_func($this->_fn_getMetadata, $key);
    }
}
<?php
namespace GuzzleHttp\Psr7;

use Psr\Http\Message\MessageInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;
use Psr\Http\Message\UriInterface;

/**
 * Returns the string representation of an HTTP message.
 *
 * @param MessageInterface $message Message to convert to a string.
 *
 * @return string
 */
function str(MessageInterface $message)
{
    if ($message instanceof RequestInterface) {
        $msg = trim($message->getMethod() . ' '
                . $message->getRequestTarget())
            . ' HTTP/' . $message->getProtocolVersion();
        if (!$message->hasHeader('host')) {
            $msg .= "\r\nHost: " . $message->getUri()->getHost();
        }
    } elseif ($message instanceof ResponseInterface) {
        $msg = 'HTTP/' . $message->getProtocolVersion() . ' '
            . $message->getStatusCode() . ' '
            . $message->getReasonPhrase();
    } else {
        throw new \InvalidArgumentException('Unknown message type');
    }

    foreach ($message->getHeaders() as $name => $values) {
        $msg .= "\r\n{$name}: " . implode(', ', $values);
    }

    return "{$msg}\r\n\r\n" . $message->getBody();
}

/**
 * Returns a UriInterface for the given value.
 *
 * This function accepts a string or {@see Psr\Http\Message\UriInterface} and
 * returns a UriInterface for the given value. If the value is already a
 * `UriInterface`, it is returned as-is.
 *
 * @param string|UriInterface $uri
 *
 * @return UriInterface
 * @throws \InvalidArgumentException
 */
function uri_for($uri)
{
    if ($uri instanceof UriInterface) {
        return $uri;
    } elseif (is_string($uri)) {
        return new Uri($uri);
    }

    throw new \InvalidArgumentException('URI must be a string or UriInterface');
}

/**
 * Create a new stream based on the input type.
 *
 * Options is an associative array that can contain the following keys:
 * - metadata: Array of custom metadata.
 * - size: Size of the stream.
 *
 * @param resource|string|StreamInterface $resource Entity body data
 * @param array                           $options  Additional options
 *
 * @return Stream
 * @throws \InvalidArgumentException if the $resource arg is not valid.
 */
function stream_for($resource = '', array $options = [])
{
    switch (gettype($resource)) {
        case 'string':
            $stream = fopen('php://temp', 'r+');
            if ($resource !== '') {
                fwrite($stream, $resource);
                fseek($stream, 0);
            }
            return new Stream($stream, $options);
        case 'resource':
            return new Stream($resource, $options);
        case 'object':
            if ($resource instanceof StreamInterface) {
                return $resource;
            } elseif ($resource instanceof \Iterator) {
                return new PumpStream(function () use ($resource) {
                    if (!$resource->valid()) {
                        return false;
                    }
                    $result = $resource->current();
                    $resource->next();
                    return $result;
                }, $options);
            } elseif (method_exists($resource, '__toString')) {
                return stream_for((string) $resource, $options);
            }
            break;
        case 'NULL':
            return new Stream(fopen('php://temp', 'r+'), $options);
    }

    if (is_callable($resource)) {
        return new PumpStream($resource, $options);
    }

    throw new \InvalidArgumentException('Invalid resource type: ' . gettype($resource));
}

/**
 * Parse an array of header values containing ";" separated data into an
 * array of associative arrays representing the header key value pair
 * data of the header. When a parameter does not contain a value, but just
 * contains a key, this function will inject a key with a '' string value.
 *
 * @param string|array $header Header to parse into components.
 *
 * @return array Returns the parsed header values.
 */
function parse_header($header)
{
    static $trimmed = "\"'  \n\t\r";
    $params = $matches = [];

    foreach (normalize_header($header) as $val) {
        $part = [];
        foreach (preg_split('/;(?=([^"]*"[^"]*")*[^"]*$)/', $val) as $kvp) {
            if (preg_match_all('/<[^>]+>|[^=]+/', $kvp, $matches)) {
                $m = $matches[0];
                if (isset($m[1])) {
                    $part[trim($m[0], $trimmed)] = trim($m[1], $trimmed);
                } else {
                    $part[] = trim($m[0], $trimmed);
                }
            }
        }
        if ($part) {
            $params[] = $part;
        }
    }

    return $params;
}

/**
 * Converts an array of header values that may contain comma separated
 * headers into an array of headers with no comma separated values.
 *
 * @param string|array $header Header to normalize.
 *
 * @return array Returns the normalized header field values.
 */
function normalize_header($header)
{
    if (!is_array($header)) {
        return array_map('trim', explode(',', $header));
    }

    $result = [];
    foreach ($header as $value) {
        foreach ((array) $value as $v) {
            if (strpos($v, ',') === false) {
                $result[] = $v;
                continue;
            }
            foreach (preg_split('/,(?=([^"]*"[^"]*")*[^"]*$)/', $v) as $vv) {
                $result[] = trim($vv);
            }
        }
    }

    return $result;
}

/**
 * Clone and modify a request with the given changes.
 *
 * The changes can be one of:
 * - method: (string) Changes the HTTP method.
 * - set_headers: (array) Sets the given headers.
 * - remove_headers: (array) Remove the given headers.
 * - body: (mixed) Sets the given body.
 * - uri: (UriInterface) Set the URI.
 * - query: (string) Set the query string value of the URI.
 * - version: (string) Set the protocol version.
 *
 * @param RequestInterface $request Request to clone and modify.
 * @param array            $changes Changes to apply.
 *
 * @return RequestInterface
 */
function modify_request(RequestInterface $request, array $changes)
{
    if (!$changes) {
        return $request;
    }

    $headers = $request->getHeaders();

    if (!isset($changes['uri'])) {
        $uri = $request->getUri();
    } else {
        // Remove the host header if one is on the URI
        if ($host = $changes['uri']->getHost()) {
            $changes['set_headers']['Host'] = $host;

            if ($port = $changes['uri']->getPort()) {
                $standardPorts = ['http' => 80, 'https' => 443];
                $scheme = $changes['uri']->getScheme();
                if (isset($standardPorts[$scheme]) && $port != $standardPorts[$scheme]) {
                    $changes['set_headers']['Host'] .= ':'.$port;
                }
            }
        }
        $uri = $changes['uri'];
    }

    if (!empty($changes['remove_headers'])) {
        $headers = _caseless_remove($changes['remove_headers'], $headers);
    }

    if (!empty($changes['set_headers'])) {
        $headers = _caseless_remove(array_keys($changes['set_headers']), $headers);
        $headers = $changes['set_headers'] + $headers;
    }

    if (isset($changes['query'])) {
        $uri = $uri->withQuery($changes['query']);
    }

    return new Request(
        isset($changes['method']) ? $changes['method'] : $request->getMethod(),
        $uri,
        $headers,
        isset($changes['body']) ? $changes['body'] : $request->getBody(),
        isset($changes['version'])
            ? $changes['version']
            : $request->getProtocolVersion()
    );
}

/**
 * Attempts to rewind a message body and throws an exception on failure.
 *
 * The body of the message will only be rewound if a call to `tell()` returns a
 * value other than `0`.
 *
 * @param MessageInterface $message Message to rewind
 *
 * @throws \RuntimeException
 */
function rewind_body(MessageInterface $message)
{
    $body = $message->getBody();

    if ($body->tell()) {
        $body->rewind();
    }
}

/**
 * Safely opens a PHP stream resource using a filename.
 *
 * When fopen fails, PHP normally raises a warning. This function adds an
 * error handler that checks for errors and throws an exception instead.
 *
 * @param string $filename File to open
 * @param string $mode     Mode used to open the file
 *
 * @return resource
 * @throws \RuntimeException if the file cannot be opened
 */
function try_fopen($filename, $mode)
{
    $ex = null;
    set_error_handler(function () use ($filename, $mode, &$ex) {
        $ex = new \RuntimeException(sprintf(
            'Unable to open %s using mode %s: %s',
            $filename,
            $mode,
            func_get_args()[1]
        ));
    });

    $handle = fopen($filename, $mode);
    restore_error_handler();

    if ($ex) {
        /** @var $ex \RuntimeException */
        throw $ex;
    }

    return $handle;
}

/**
 * Copy the contents of a stream into a string until the given number of
 * bytes have been read.
 *
 * @param StreamInterface $stream Stream to read
 * @param int             $maxLen Maximum number of bytes to read. Pass -1
 *                                to read the entire stream.
 * @return string
 * @throws \RuntimeException on error.
 */
function copy_to_string(StreamInterface $stream, $maxLen = -1)
{
    $buffer = '';

    if ($maxLen === -1) {
        while (!$stream->eof()) {
            $buf = $stream->read(1048576);
            // Using a loose equality here to match on '' and false.
            if ($buf == null) {
                break;
            }
            $buffer .= $buf;
        }
        return $buffer;
    }

    $len = 0;
    while (!$stream->eof() && $len < $maxLen) {
        $buf = $stream->read($maxLen - $len);
        // Using a loose equality here to match on '' and false.
        if ($buf == null) {
            break;
        }
        $buffer .= $buf;
        $len = strlen($buffer);
    }

    return $buffer;
}

/**
 * Copy the contents of a stream into another stream until the given number
 * of bytes have been read.
 *
 * @param StreamInterface $source Stream to read from
 * @param StreamInterface $dest   Stream to write to
 * @param int             $maxLen Maximum number of bytes to read. Pass -1
 *                                to read the entire stream.
 *
 * @throws \RuntimeException on error.
 */
function copy_to_stream(
    StreamInterface $source,
    StreamInterface $dest,
    $maxLen = -1
) {
    if ($maxLen === -1) {
        while (!$source->eof()) {
            if (!$dest->write($source->read(1048576))) {
                break;
            }
        }
        return;
    }

    $bytes = 0;
    while (!$source->eof()) {
        $buf = $source->read($maxLen - $bytes);
        if (!($len = strlen($buf))) {
            break;
        }
        $bytes += $len;
        $dest->write($buf);
        if ($bytes == $maxLen) {
            break;
        }
    }
}

/**
 * Calculate a hash of a Stream
 *
 * @param StreamInterface $stream    Stream to calculate the hash for
 * @param string          $algo      Hash algorithm (e.g. md5, crc32, etc)
 * @param bool            $rawOutput Whether or not to use raw output
 *
 * @return string Returns the hash of the stream
 * @throws \RuntimeException on error.
 */
function hash(
    StreamInterface $stream,
    $algo,
    $rawOutput = false
) {
    $pos = $stream->tell();

    if ($pos > 0) {
        $stream->rewind();
    }

    $ctx = hash_init($algo);
    while (!$stream->eof()) {
        hash_update($ctx, $stream->read(1048576));
    }

    $out = hash_final($ctx, (bool) $rawOutput);
    $stream->seek($pos);

    return $out;
}

/**
 * Read a line from the stream up to the maximum allowed buffer length
 *
 * @param StreamInterface $stream    Stream to read from
 * @param int             $maxLength Maximum buffer length
 *
 * @return string|bool
 */
function readline(StreamInterface $stream, $maxLength = null)
{
    $buffer = '';
    $size = 0;

    while (!$stream->eof()) {
        // Using a loose equality here to match on '' and false.
        if (null == ($byte = $stream->read(1))) {
            return $buffer;
        }
        $buffer .= $byte;
        // Break when a new line is found or the max length - 1 is reached
        if ($byte == PHP_EOL || ++$size == $maxLength - 1) {
            break;
        }
    }

    return $buffer;
}

/**
 * Parses a request message string into a request object.
 *
 * @param string $message Request message string.
 *
 * @return Request
 */
function parse_request($message)
{
    $data = _parse_message($message);
    $matches = [];
    if (!preg_match('/^[a-zA-Z]+\s+([a-zA-Z]+:\/\/|\/).*/', $data['start-line'], $matches)) {
        throw new \InvalidArgumentException('Invalid request string');
    }
    $parts = explode(' ', $data['start-line'], 3);
    $version = isset($parts[2]) ? explode('/', $parts[2])[1] : '1.1';

    $request = new Request(
        $parts[0],
        $matches[1] === '/' ? _parse_request_uri($parts[1], $data['headers']) : $parts[1],
        $data['headers'],
        $data['body'],
        $version
    );

    return $matches[1] === '/' ? $request : $request->withRequestTarget($parts[1]);
}

/**
 * Parses a response message string into a response object.
 *
 * @param string $message Response message string.
 *
 * @return Response
 */
function parse_response($message)
{
    $data = _parse_message($message);
    if (!preg_match('/^HTTP\/.* [0-9]{3} .*/', $data['start-line'])) {
        throw new \InvalidArgumentException('Invalid response string');
    }
    $parts = explode(' ', $data['start-line'], 3);

    return new Response(
        $parts[1],
        $data['headers'],
        $data['body'],
        explode('/', $parts[0])[1],
        isset($parts[2]) ? $parts[2] : null
    );
}

/**
 * Parse a query string into an associative array.
 *
 * If multiple values are found for the same key, the value of that key
 * value pair will become an array. This function does not parse nested
 * PHP style arrays into an associative array (e.g., foo[a]=1&foo[b]=2 will
 * be parsed into ['foo[a]' => '1', 'foo[b]' => '2']).
 *
 * @param string      $str         Query string to parse
 * @param bool|string $urlEncoding How the query string is encoded
 *
 * @return array
 */
function parse_query($str, $urlEncoding = true)
{
    $result = [];

    if ($str === '') {
        return $result;
    }

    if ($urlEncoding === true) {
        $decoder = function ($value) {
            return rawurldecode(str_replace('+', ' ', $value));
        };
    } elseif ($urlEncoding == PHP_QUERY_RFC3986) {
        $decoder = 'rawurldecode';
    } elseif ($urlEncoding == PHP_QUERY_RFC1738) {
        $decoder = 'urldecode';
    } else {
        $decoder = function ($str) { return $str; };
    }

    foreach (explode('&', $str) as $kvp) {
        $parts = explode('=', $kvp, 2);
        $key = $decoder($parts[0]);
        $value = isset($parts[1]) ? $decoder($parts[1]) : null;
        if (!isset($result[$key])) {
            $result[$key] = $value;
        } else {
            if (!is_array($result[$key])) {
                $result[$key] = [$result[$key]];
            }
            $result[$key][] = $value;
        }
    }

    return $result;
}

/**
 * Build a query string from an array of key value pairs.
 *
 * This function can use the return value of parseQuery() to build a query
 * string. This function does not modify the provided keys when an array is
 * encountered (like http_build_query would).
 *
 * @param array     $params   Query string parameters.
 * @param int|false $encoding Set to false to not encode, PHP_QUERY_RFC3986
 *                            to encode using RFC3986, or PHP_QUERY_RFC1738
 *                            to encode using RFC1738.
 * @return string
 */
function build_query(array $params, $encoding = PHP_QUERY_RFC3986)
{
    if (!$params) {
        return '';
    }

    if ($encoding === false) {
        $encoder = function ($str) { return $str; };
    } elseif ($encoding == PHP_QUERY_RFC3986) {
        $encoder = 'rawurlencode';
    } elseif ($encoding == PHP_QUERY_RFC1738) {
        $encoder = 'urlencode';
    } else {
        throw new \InvalidArgumentException('Invalid type');
    }

    $qs = '';
    foreach ($params as $k => $v) {
        $k = $encoder($k);
        if (!is_array($v)) {
            $qs .= $k;
            if ($v !== null) {
                $qs .= '=' . $encoder($v);
            }
            $qs .= '&';
        } else {
            foreach ($v as $vv) {
                $qs .= $k;
                if ($vv !== null) {
                    $qs .= '=' . $encoder($vv);
                }
                $qs .= '&';
            }
        }
    }

    return $qs ? (string) substr($qs, 0, -1) : '';
}

/**
 * Determines the mimetype of a file by looking at its extension.
 *
 * @param $filename
 *
 * @return null|string
 */
function mimetype_from_filename($filename)
{
    return mimetype_from_extension(pathinfo($filename, PATHINFO_EXTENSION));
}

/**
 * Maps a file extensions to a mimetype.
 *
 * @param $extension string The file extension.
 *
 * @return string|null
 * @link http://svn.apache.org/repos/asf/httpd/httpd/branches/1.3.x/conf/mime.types
 */
function mimetype_from_extension($extension)
{
    static $mimetypes = [
        '7z' => 'application/x-7z-compressed',
        'aac' => 'audio/x-aac',
        'ai' => 'application/postscript',
        'aif' => 'audio/x-aiff',
        'asc' => 'text/plain',
        'asf' => 'video/x-ms-asf',
        'atom' => 'application/atom+xml',
        'avi' => 'video/x-msvideo',
        'bmp' => 'image/bmp',
        'bz2' => 'application/x-bzip2',
        'cer' => 'application/pkix-cert',
        'crl' => 'application/pkix-crl',
        'crt' => 'application/x-x509-ca-cert',
        'css' => 'text/css',
        'csv' => 'text/csv',
        'cu' => 'application/cu-seeme',
        'deb' => 'application/x-debian-package',
        'doc' => 'application/msword',
        'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'dvi' => 'application/x-dvi',
        'eot' => 'application/vnd.ms-fontobject',
        'eps' => 'application/postscript',
        'epub' => 'application/epub+zip',
        'etx' => 'text/x-setext',
        'flac' => 'audio/flac',
        'flv' => 'video/x-flv',
        'gif' => 'image/gif',
        'gz' => 'application/gzip',
        'htm' => 'text/html',
        'html' => 'text/html',
        'ico' => 'image/x-icon',
        'ics' => 'text/calendar',
        'ini' => 'text/plain',
        'iso' => 'application/x-iso9660-image',
        'jar' => 'application/java-archive',
        'jpe' => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'jpg' => 'image/jpeg',
        'js' => 'text/javascript',
        'json' => 'application/json',
        'latex' => 'application/x-latex',
        'log' => 'text/plain',
        'm4a' => 'audio/mp4',
        'm4v' => 'video/mp4',
        'mid' => 'audio/midi',
        'midi' => 'audio/midi',
        'mov' => 'video/quicktime',
        'mp3' => 'audio/mpeg',
        'mp4' => 'video/mp4',
        'mp4a' => 'audio/mp4',
        'mp4v' => 'video/mp4',
        'mpe' => 'video/mpeg',
        'mpeg' => 'video/mpeg',
        'mpg' => 'video/mpeg',
        'mpg4' => 'video/mp4',
        'oga' => 'audio/ogg',
        'ogg' => 'audio/ogg',
        'ogv' => 'video/ogg',
        'ogx' => 'application/ogg',
        'pbm' => 'image/x-portable-bitmap',
        'pdf' => 'application/pdf',
        'pgm' => 'image/x-portable-graymap',
        'png' => 'image/png',
        'pnm' => 'image/x-portable-anymap',
        'ppm' => 'image/x-portable-pixmap',
        'ppt' => 'application/vnd.ms-powerpoint',
        'pptx' => 'application/vnd.openxmlformats-officedocument.presentationml.presentation',
        'ps' => 'application/postscript',
        'qt' => 'video/quicktime',
        'rar' => 'application/x-rar-compressed',
        'ras' => 'image/x-cmu-raster',
        'rss' => 'application/rss+xml',
        'rtf' => 'application/rtf',
        'sgm' => 'text/sgml',
        'sgml' => 'text/sgml',
        'svg' => 'image/svg+xml',
        'swf' => 'application/x-shockwave-flash',
        'tar' => 'application/x-tar',
        'tif' => 'image/tiff',
        'tiff' => 'image/tiff',
        'torrent' => 'application/x-bittorrent',
        'ttf' => 'application/x-font-ttf',
        'txt' => 'text/plain',
        'wav' => 'audio/x-wav',
        'webm' => 'video/webm',
        'wma' => 'audio/x-ms-wma',
        'wmv' => 'video/x-ms-wmv',
        'woff' => 'application/x-font-woff',
        'wsdl' => 'application/wsdl+xml',
        'xbm' => 'image/x-xbitmap',
        'xls' => 'application/vnd.ms-excel',
        'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        'xml' => 'application/xml',
        'xpm' => 'image/x-xpixmap',
        'xwd' => 'image/x-xwindowdump',
        'yaml' => 'text/yaml',
        'yml' => 'text/yaml',
        'zip' => 'application/zip',
    ];

    $extension = strtolower($extension);

    return isset($mimetypes[$extension])
        ? $mimetypes[$extension]
        : null;
}

/**
 * Parses an HTTP message into an associative array.
 *
 * The array contains the "start-line" key containing the start line of
 * the message, "headers" key containing an associative array of header
 * array values, and a "body" key containing the body of the message.
 *
 * @param string $message HTTP request or response to parse.
 *
 * @return array
 * @internal
 */
function _parse_message($message)
{
    if (!$message) {
        throw new \InvalidArgumentException('Invalid message');
    }

    // Iterate over each line in the message, accounting for line endings
    $lines = preg_split('/(\\r?\\n)/', $message, -1, PREG_SPLIT_DELIM_CAPTURE);
    $result = ['start-line' => array_shift($lines), 'headers' => [], 'body' => ''];
    array_shift($lines);

    for ($i = 0, $totalLines = count($lines); $i < $totalLines; $i += 2) {
        $line = $lines[$i];
        // If two line breaks were encountered, then this is the end of body
        if (empty($line)) {
            if ($i < $totalLines - 1) {
                $result['body'] = implode('', array_slice($lines, $i + 2));
            }
            break;
        }
        if (strpos($line, ':')) {
            $parts = explode(':', $line, 2);
            $key = trim($parts[0]);
            $value = isset($parts[1]) ? trim($parts[1]) : '';
            $result['headers'][$key][] = $value;
        }
    }

    return $result;
}

/**
 * Constructs a URI for an HTTP request message.
 *
 * @param string $path    Path from the start-line
 * @param array  $headers Array of headers (each value an array).
 *
 * @return string
 * @internal
 */
function _parse_request_uri($path, array $headers)
{
    $hostKey = array_filter(array_keys($headers), function ($k) {
        return strtolower($k) === 'host';
    });

    // If no host is found, then a full URI cannot be constructed.
    if (!$hostKey) {
        return $path;
    }

    $host = $headers[reset($hostKey)][0];
    $scheme = substr($host, -4) === ':443' ? 'https' : 'http';

    return $scheme . '://' . $host . '/' . ltrim($path, '/');
}

/** @internal */
function _caseless_remove($keys, array $data)
{
    $result = [];

    foreach ($keys as &$key) {
        $key = strtolower($key);
    }

    foreach ($data as $k => $v) {
        if (!in_array(strtolower($k), $keys)) {
            $result[$k] = $v;
        }
    }

    return $result;
}
<?php

// Don't redefine the functions if included multiple times.
if (!function_exists('GuzzleHttp\Psr7\str')) {
    require __DIR__ . '/functions.php';
}
<?php
namespace GuzzleHttp\Psr7;

use Psr\Http\Message\StreamInterface;

/**
 * Uses PHP's zlib.inflate filter to inflate deflate or gzipped content.
 *
 * This stream decorator skips the first 10 bytes of the given stream to remove
 * the gzip header, converts the provided stream to a PHP stream resource,
 * then appends the zlib.inflate filter. The stream is then converted back
 * to a Guzzle stream resource to be used as a Guzzle stream.
 *
 * @link http://tools.ietf.org/html/rfc1952
 * @link http://php.net/manual/en/filters.compression.php
 */
class InflateStream implements StreamInterface
{
    use StreamDecoratorTrait;

    public function __construct(StreamInterface $stream)
    {
        // read the first 10 bytes, ie. gzip header
        $header = $stream->read(10);
        $filenameHeaderLength = $this->getLengthOfPossibleFilenameHeader($stream, $header);
        // Skip the header, that is 10 + length of filename + 1 (nil) bytes
        $stream = new LimitStream($stream, -1, 10 + $filenameHeaderLength);
        $resource = StreamWrapper::getResource($stream);
        stream_filter_append($resource, 'zlib.inflate', STREAM_FILTER_READ);
        $this->stream = new Stream($resource);
    }

    /**
     * @param StreamInterface $stream
     * @param $header
     * @return int
     */
    private function getLengthOfPossibleFilenameHeader(StreamInterface $stream, $header)
    {
        $filename_header_length = 0;

        if (substr(bin2hex($header), 6, 2) === '08') {
            // we have a filename, read until nil
            $filename_header_length = 1;
            while ($stream->read(1) !== chr(0)) {
                $filename_header_length++;
            }
        }

        return $filename_header_length;
    }
}
<?php
namespace GuzzleHttp\Psr7;

use Psr\Http\Message\StreamInterface;

/**
 * Lazily reads or writes to a file that is opened only after an IO operation
 * take place on the stream.
 */
class LazyOpenStream implements StreamInterface
{
    use StreamDecoratorTrait;

    /** @var string File to open */
    private $filename;

    /** @var string $mode */
    private $mode;

    /**
     * @param string $filename File to lazily open
     * @param string $mode     fopen mode to use when opening the stream
     */
    public function __construct($filename, $mode)
    {
        $this->filename = $filename;
        $this->mode = $mode;
    }

    /**
     * Creates the underlying stream lazily when required.
     *
     * @return StreamInterface
     */
    protected function createStream()
    {
        return stream_for(try_fopen($this->filename, $this->mode));
    }
}
<?php
namespace GuzzleHttp\Psr7;

use Psr\Http\Message\StreamInterface;


/**
 * Decorator used to return only a subset of a stream
 */
class LimitStream implements StreamInterface
{
    use StreamDecoratorTrait;

    /** @var int Offset to start reading from */
    private $offset;

    /** @var int Limit the number of bytes that can be read */
    private $limit;

    /**
     * @param StreamInterface $stream Stream to wrap
     * @param int             $limit  Total number of bytes to allow to be read
     *                                from the stream. Pass -1 for no limit.
     * @param int|null        $offset Position to seek to before reading (only
     *                                works on seekable streams).
     */
    public function __construct(
        StreamInterface $stream,
        $limit = -1,
        $offset = 0
    ) {
        $this->stream = $stream;
        $this->setLimit($limit);
        $this->setOffset($offset);
    }

    public function eof()
    {
        // Always return true if the underlying stream is EOF
        if ($this->stream->eof()) {
            return true;
        }

        // No limit and the underlying stream is not at EOF
        if ($this->limit == -1) {
            return false;
        }

        return $this->stream->tell() >= $this->offset + $this->limit;
    }

    /**
     * Returns the size of the limited subset of data
     * {@inheritdoc}
     */
    public function getSize()
    {
        if (null === ($length = $this->stream->getSize())) {
            return null;
        } elseif ($this->limit == -1) {
            return $length - $this->offset;
        } else {
            return min($this->limit, $length - $this->offset);
        }
    }

    /**
     * Allow for a bounded seek on the read limited stream
     * {@inheritdoc}
     */
    public function seek($offset, $whence = SEEK_SET)
    {
        if ($whence !== SEEK_SET || $offset < 0) {
            throw new \RuntimeException(sprintf(
                'Cannot seek to offset % with whence %s',
                $offset,
                $whence
            ));
        }

        $offset += $this->offset;

        if ($this->limit !== -1) {
            if ($offset > $this->offset + $this->limit) {
                $offset = $this->offset + $this->limit;
            }
        }

        $this->stream->seek($offset);
    }

    /**
     * Give a relative tell()
     * {@inheritdoc}
     */
    public function tell()
    {
        return $this->stream->tell() - $this->offset;
    }

    /**
     * Set the offset to start limiting from
     *
     * @param int $offset Offset to seek to and begin byte limiting from
     *
     * @throws \RuntimeException if the stream cannot be seeked.
     */
    public function setOffset($offset)
    {
        $current = $this->stream->tell();

        if ($current !== $offset) {
            // If the stream cannot seek to the offset position, then read to it
            if ($this->stream->isSeekable()) {
                $this->stream->seek($offset);
            } elseif ($current > $offset) {
                throw new \RuntimeException("Could not seek to stream offset $offset");
            } else {
                $this->stream->read($offset - $current);
            }
        }

        $this->offset = $offset;
    }

    /**
     * Set the limit of bytes that the decorator allows to be read from the
     * stream.
     *
     * @param int $limit Number of bytes to allow to be read from the stream.
     *                   Use -1 for no limit.
     */
    public function setLimit($limit)
    {
        $this->limit = $limit;
    }

    public function read($length)
    {
        if ($this->limit == -1) {
            return $this->stream->read($length);
        }

        // Check if the current position is less than the total allowed
        // bytes + original offset
        $remaining = ($this->offset + $this->limit) - $this->stream->tell();
        if ($remaining > 0) {
            // Only return the amount of requested data, ensuring that the byte
            // limit is not exceeded
            return $this->stream->read(min($remaining, $length));
        }

        return '';
    }
}
<?php
namespace GuzzleHttp\Psr7;

use Psr\Http\Message\StreamInterface;

/**
 * Trait implementing functionality common to requests and responses.
 */
trait MessageTrait
{
    /** @var array Cached HTTP header collection with lowercase key to values */
    private $headers = [];

    /** @var array Actual key to list of values per header. */
    private $headerLines = [];

    /** @var string */
    private $protocol = '1.1';

    /** @var StreamInterface */
    private $stream;

    public function getProtocolVersion()
    {
        return $this->protocol;
    }

    public function withProtocolVersion($version)
    {
        if ($this->protocol === $version) {
            return $this;
        }

        $new = clone $this;
        $new->protocol = $version;
        return $new;
    }

    public function getHeaders()
    {
        return $this->headerLines;
    }

    public function hasHeader($header)
    {
        return isset($this->headers[strtolower($header)]);
    }

    public function getHeader($header)
    {
        $name = strtolower($header);
        return isset($this->headers[$name]) ? $this->headers[$name] : [];
    }

    public function getHeaderLine($header)
    {
        return implode(', ', $this->getHeader($header));
    }

    public function withHeader($header, $value)
    {
        $new = clone $this;
        $header = trim($header);
        $name = strtolower($header);

        if (!is_array($value)) {
            $new->headers[$name] = [trim($value)];
        } else {
            $new->headers[$name] = $value;
            foreach ($new->headers[$name] as &$v) {
                $v = trim($v);
            }
        }

        // Remove the header lines.
        foreach (array_keys($new->headerLines) as $key) {
            if (strtolower($key) === $name) {
                unset($new->headerLines[$key]);
            }
        }

        // Add the header line.
        $new->headerLines[$header] = $new->headers[$name];

        return $new;
    }

    public function withAddedHeader($header, $value)
    {
        if (!$this->hasHeader($header)) {
            return $this->withHeader($header, $value);
        }

        $new = clone $this;
        $new->headers[strtolower($header)][] = $value;
        $new->headerLines[$header][] = $value;
        return $new;
    }

    public function withoutHeader($header)
    {
        if (!$this->hasHeader($header)) {
            return $this;
        }

        $new = clone $this;
        $name = strtolower($header);
        unset($new->headers[$name]);

        foreach (array_keys($new->headerLines) as $key) {
            if (strtolower($key) === $name) {
                unset($new->headerLines[$key]);
            }
        }

        return $new;
    }

    public function getBody()
    {
        if (!$this->stream) {
            $this->stream = stream_for('');
        }

        return $this->stream;
    }

    public function withBody(StreamInterface $body)
    {
        if ($body === $this->stream) {
            return $this;
        }

        $new = clone $this;
        $new->stream = $body;
        return $new;
    }

    private function setHeaders(array $headers)
    {
        $this->headerLines = $this->headers = [];
        foreach ($headers as $header => $value) {
            $header = trim($header);
            $name = strtolower($header);
            if (!is_array($value)) {
                $value = trim($value);
                $this->headers[$name][] = $value;
                $this->headerLines[$header][] = $value;
            } else {
                foreach ($value as $v) {
                    $v = trim($v);
                    $this->headers[$name][] = $v;
                    $this->headerLines[$header][] = $v;
                }
            }
        }
    }
}
<?php
namespace GuzzleHttp\Psr7;

use Psr\Http\Message\StreamInterface;

/**
 * Stream that when read returns bytes for a streaming multipart or
 * multipart/form-data stream.
 */
class MultipartStream implements StreamInterface
{
    use StreamDecoratorTrait;

    private $boundary;

    /**
     * @param array  $elements Array of associative arrays, each containing a
     *                         required "name" key mapping to the form field,
     *                         name, a required "contents" key mapping to a
     *                         StreamInterface/resource/string, an optional
     *                         "headers" associative array of custom headers,
     *                         and an optional "filename" key mapping to a
     *                         string to send as the filename in the part.
     * @param string $boundary You can optionally provide a specific boundary
     *
     * @throws \InvalidArgumentException
     */
    public function __construct(array $elements = [], $boundary = null)
    {
        $this->boundary = $boundary ?: uniqid();
        $this->stream = $this->createStream($elements);
    }

    /**
     * Get the boundary
     *
     * @return string
     */
    public function getBoundary()
    {
        return $this->boundary;
    }

    public function isWritable()
    {
        return false;
    }

    /**
     * Get the headers needed before transferring the content of a POST file
     */
    private function getHeaders(array $headers)
    {
        $str = '';
        foreach ($headers as $key => $value) {
            $str .= "{$key}: {$value}\r\n";
        }

        return "--{$this->boundary}\r\n" . trim($str) . "\r\n\r\n";
    }

    /**
     * Create the aggregate stream that will be used to upload the POST data
     */
    protected function createStream(array $elements)
    {
        $stream = new AppendStream();

        foreach ($elements as $element) {
            $this->addElement($stream, $element);
        }

        // Add the trailing boundary with CRLF
        $stream->addStream(stream_for("--{$this->boundary}--\r\n"));

        return $stream;
    }

    private function addElement(AppendStream $stream, array $element)
    {
        foreach (['contents', 'name'] as $key) {
            if (!array_key_exists($key, $element)) {
                throw new \InvalidArgumentException("A '{$key}' key is required");
            }
        }

        $element['contents'] = stream_for($element['contents']);

        if (empty($element['filename'])) {
            $uri = $element['contents']->getMetadata('uri');
            if (substr($uri, 0, 6) !== 'php://') {
                $element['filename'] = $uri;
            }
        }

        list($body, $headers) = $this->createElement(
            $element['name'],
            $element['contents'],
            isset($element['filename']) ? $element['filename'] : null,
            isset($element['headers']) ? $element['headers'] : []
        );

        $stream->addStream(stream_for($this->getHeaders($headers)));
        $stream->addStream($body);
        $stream->addStream(stream_for("\r\n"));
    }

    /**
     * @return array
     */
    private function createElement($name, $stream, $filename, array $headers)
    {
        // Set a default content-disposition header if one was no provided
        $disposition = $this->getHeader($headers, 'content-disposition');
        if (!$disposition) {
            $headers['Content-Disposition'] = ($filename === '0' || $filename)
                ? sprintf('form-data; name="%s"; filename="%s"',
                    $name,
                    basename($filename))
                : "form-data; name=\"{$name}\"";
        }

        // Set a default content-length header if one was no provided
        $length = $this->getHeader($headers, 'content-length');
        if (!$length) {
            if ($length = $stream->getSize()) {
                $headers['Content-Length'] = (string) $length;
            }
        }

        // Set a default Content-Type if one was not supplied
        $type = $this->getHeader($headers, 'content-type');
        if (!$type && ($filename === '0' || $filename)) {
            if ($type = mimetype_from_filename($filename)) {
                $headers['Content-Type'] = $type;
            }
        }

        return [$stream, $headers];
    }

    private function getHeader(array $headers, $key)
    {
        $lowercaseHeader = strtolower($key);
        foreach ($headers as $k => $v) {
            if (strtolower($k) === $lowercaseHeader) {
                return $v;
            }
        }

        return null;
    }
}
<?php
namespace GuzzleHttp\Psr7;

use Psr\Http\Message\StreamInterface;

/**
 * Stream decorator that prevents a stream from being seeked
 */
class NoSeekStream implements StreamInterface
{
    use StreamDecoratorTrait;

    public function seek($offset, $whence = SEEK_SET)
    {
        throw new \RuntimeException('Cannot seek a NoSeekStream');
    }

    public function isSeekable()
    {
        return false;
    }
}
<?php
namespace GuzzleHttp\Psr7;

use Psr\Http\Message\StreamInterface;

/**
 * Provides a read only stream that pumps data from a PHP callable.
 *
 * When invoking the provided callable, the PumpStream will pass the amount of
 * data requested to read to the callable. The callable can choose to ignore
 * this value and return fewer or more bytes than requested. Any extra data
 * returned by the provided callable is buffered internally until drained using
 * the read() function of the PumpStream. The provided callable MUST return
 * false when there is no more data to read.
 */
class PumpStream implements StreamInterface
{
    /** @var callable */
    private $source;

    /** @var int */
    private $size;

    /** @var int */
    private $tellPos = 0;

    /** @var array */
    private $metadata;

    /** @var BufferStream */
    private $buffer;

    /**
     * @param callable $source Source of the stream data. The callable MAY
     *                         accept an integer argument used to control the
     *                         amount of data to return. The callable MUST
     *                         return a string when called, or false on error
     *                         or EOF.
     * @param array $options   Stream options:
     *                         - metadata: Hash of metadata to use with stream.
     *                         - size: Size of the stream, if known.
     */
    public function __construct(callable $source, array $options = [])
    {
        $this->source = $source;
        $this->size = isset($options['size']) ? $options['size'] : null;
        $this->metadata = isset($options['metadata']) ? $options['metadata'] : [];
        $this->buffer = new BufferStream();
    }

    public function __toString()
    {
        try {
            return copy_to_string($this);
        } catch (\Exception $e) {
            return '';
        }
    }

    public function close()
    {
        $this->detach();
    }

    public function detach()
    {
        $this->tellPos = false;
        $this->source = null;
    }

    public function getSize()
    {
        return $this->size;
    }

    public function tell()
    {
        return $this->tellPos;
    }

    public function eof()
    {
        return !$this->source;
    }

    public function isSeekable()
    {
        return false;
    }

    public function rewind()
    {
        $this->seek(0);
    }

    public function seek($offset, $whence = SEEK_SET)
    {
        throw new \RuntimeException('Cannot seek a PumpStream');
    }

    public function isWritable()
    {
        return false;
    }

    public function write($string)
    {
        throw new \RuntimeException('Cannot write to a PumpStream');
    }

    public function isReadable()
    {
        return true;
    }

    public function read($length)
    {
        $data = $this->buffer->read($length);
        $readLen = strlen($data);
        $this->tellPos += $readLen;
        $remaining = $length - $readLen;

        if ($remaining) {
            $this->pump($remaining);
            $data .= $this->buffer->read($remaining);
            $this->tellPos += strlen($data) - $readLen;
        }

        return $data;
    }

    public function getContents()
    {
        $result = '';
        while (!$this->eof()) {
            $result .= $this->read(1000000);
        }

        return $result;
    }

    public function getMetadata($key = null)
    {
        if (!$key) {
            return $this->metadata;
        }

        return isset($this->metadata[$key]) ? $this->metadata[$key] : null;
    }

    private function pump($length)
    {
        if ($this->source) {
            do {
                $data = call_user_func($this->source, $length);
                if ($data === false || $data === null) {
                    $this->source = null;
                    return;
                }
                $this->buffer->write($data);
                $length -= strlen($data);
            } while ($length > 0);
        }
    }
}
<?php
namespace GuzzleHttp\Psr7;

use InvalidArgumentException;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\StreamInterface;
use Psr\Http\Message\UriInterface;

/**
 * PSR-7 request implementation.
 */
class Request implements RequestInterface
{
    use MessageTrait {
        withHeader as protected withParentHeader;
    }

    /** @var string */
    private $method;

    /** @var null|string */
    private $requestTarget;

    /** @var null|UriInterface */
    private $uri;

    /**
     * @param null|string $method HTTP method for the request.
     * @param null|string|UriInterface $uri URI for the request.
     * @param array $headers Headers for the message.
     * @param string|resource|StreamInterface $body Message body.
     * @param string $protocolVersion HTTP protocol version.
     *
     * @throws InvalidArgumentException for an invalid URI
     */
    public function __construct(
        $method,
        $uri,
        array $headers = [],
        $body = null,
        $protocolVersion = '1.1'
    ) {
        if (is_string($uri)) {
            $uri = new Uri($uri);
        } elseif (!($uri instanceof UriInterface)) {
            throw new \InvalidArgumentException(
                'URI must be a string or Psr\Http\Message\UriInterface'
            );
        }

        $this->method = strtoupper($method);
        $this->uri = $uri;
        $this->setHeaders($headers);
        $this->protocol = $protocolVersion;

        $host = $uri->getHost();
        if ($host && !$this->hasHeader('Host')) {
            $this->updateHostFromUri($host);
        }

        if ($body) {
            $this->stream = stream_for($body);
        }
    }

    public function getRequestTarget()
    {
        if ($this->requestTarget !== null) {
            return $this->requestTarget;
        }

        $target = $this->uri->getPath();
        if ($target == null) {
            $target = '/';
        }
        if ($this->uri->getQuery()) {
            $target .= '?' . $this->uri->getQuery();
        }

        return $target;
    }

    public function withRequestTarget($requestTarget)
    {
        if (preg_match('#\s#', $requestTarget)) {
            throw new InvalidArgumentException(
                'Invalid request target provided; cannot contain whitespace'
            );
        }

        $new = clone $this;
        $new->requestTarget = $requestTarget;
        return $new;
    }

    public function getMethod()
    {
        return $this->method;
    }

    public function withMethod($method)
    {
        $new = clone $this;
        $new->method = strtoupper($method);
        return $new;
    }

    public function getUri()
    {
        return $this->uri;
    }

    public function withUri(UriInterface $uri, $preserveHost = false)
    {
        if ($uri === $this->uri) {
            return $this;
        }

        $new = clone $this;
        $new->uri = $uri;

        if (!$preserveHost) {
            if ($host = $uri->getHost()) {
                $new->updateHostFromUri($host);
            }
        }

        return $new;
    }

    public function withHeader($header, $value)
    {
        /** @var Request $newInstance */
        $newInstance = $this->withParentHeader($header, $value);
        return $newInstance;
    }

    private function updateHostFromUri($host)
    {
        // Ensure Host is the first header.
        // See: http://tools.ietf.org/html/rfc7230#section-5.4
        if ($port = $this->uri->getPort()) {
            $host .= ':' . $port;
        }

        $this->headerLines = ['Host' => [$host]] + $this->headerLines;
        $this->headers = ['host' => [$host]] + $this->headers;
    }
}
<?php
namespace GuzzleHttp\Psr7;

use Psr\Http\Message\ResponseInterface;

/**
 * PSR-7 response implementation.
 */
class Response implements ResponseInterface
{
    use MessageTrait;

    /** @var array Map of standard HTTP status code/reason phrases */
    private static $phrases = [
        100 => 'Continue',
        101 => 'Switching Protocols',
        102 => 'Processing',
        200 => 'OK',
        201 => 'Created',
        202 => 'Accepted',
        203 => 'Non-Authoritative Information',
        204 => 'No Content',
        205 => 'Reset Content',
        206 => 'Partial Content',
        207 => 'Multi-status',
        208 => 'Already Reported',
        300 => 'Multiple Choices',
        301 => 'Moved Permanently',
        302 => 'Found',
        303 => 'See Other',
        304 => 'Not Modified',
        305 => 'Use Proxy',
        306 => 'Switch Proxy',
        307 => 'Temporary Redirect',
        400 => 'Bad Request',
        401 => 'Unauthorized',
        402 => 'Payment Required',
        403 => 'Forbidden',
        404 => 'Not Found',
        405 => 'Method Not Allowed',
        406 => 'Not Acceptable',
        407 => 'Proxy Authentication Required',
        408 => 'Request Time-out',
        409 => 'Conflict',
        410 => 'Gone',
        411 => 'Length Required',
        412 => 'Precondition Failed',
        413 => 'Request Entity Too Large',
        414 => 'Request-URI Too Large',
        415 => 'Unsupported Media Type',
        416 => 'Requested range not satisfiable',
        417 => 'Expectation Failed',
        418 => 'I\'m a teapot',
        422 => 'Unprocessable Entity',
        423 => 'Locked',
        424 => 'Failed Dependency',
        425 => 'Unordered Collection',
        426 => 'Upgrade Required',
        428 => 'Precondition Required',
        429 => 'Too Many Requests',
        431 => 'Request Header Fields Too Large',
        451 => 'Unavailable For Legal Reasons',
        500 => 'Internal Server Error',
        501 => 'Not Implemented',
        502 => 'Bad Gateway',
        503 => 'Service Unavailable',
        504 => 'Gateway Time-out',
        505 => 'HTTP Version not supported',
        506 => 'Variant Also Negotiates',
        507 => 'Insufficient Storage',
        508 => 'Loop Detected',
        511 => 'Network Authentication Required',
    ];

    /** @var null|string */
    private $reasonPhrase = '';

    /** @var int */
    private $statusCode = 200;

    /**
     * @param int    $status  Status code for the response, if any.
     * @param array  $headers Headers for the response, if any.
     * @param mixed  $body    Stream body.
     * @param string $version Protocol version.
     * @param string $reason  Reason phrase (a default will be used if possible).
     */
    public function __construct(
        $status = 200,
        array $headers = [],
        $body = null,
        $version = '1.1',
        $reason = null
    ) {
        $this->statusCode = (int) $status;

        if ($body !== null) {
            $this->stream = stream_for($body);
        }

        $this->setHeaders($headers);
        if (!$reason && isset(self::$phrases[$this->statusCode])) {
            $this->reasonPhrase = self::$phrases[$status];
        } else {
            $this->reasonPhrase = (string) $reason;
        }

        $this->protocol = $version;
    }

    public function getStatusCode()
    {
        return $this->statusCode;
    }

    public function getReasonPhrase()
    {
        return $this->reasonPhrase;
    }

    public function withStatus($code, $reasonPhrase = '')
    {
        $new = clone $this;
        $new->statusCode = (int) $code;
        if (!$reasonPhrase && isset(self::$phrases[$new->statusCode])) {
            $reasonPhrase = self::$phrases[$new->statusCode];
        }
        $new->reasonPhrase = $reasonPhrase;
        return $new;
    }
}
<?php
namespace GuzzleHttp\Psr7;

use Psr\Http\Message\StreamInterface;

/**
 * PHP stream implementation.
 *
 * @var $stream
 */
class Stream implements StreamInterface
{
    private $stream;
    private $size;
    private $seekable;
    private $readable;
    private $writable;
    private $uri;
    private $customMetadata;

    /** @var array Hash of readable and writable stream types */
    private static $readWriteHash = [
        'read' => [
            'r' => true, 'w+' => true, 'r+' => true, 'x+' => true, 'c+' => true,
            'rb' => true, 'w+b' => true, 'r+b' => true, 'x+b' => true,
            'c+b' => true, 'rt' => true, 'w+t' => true, 'r+t' => true,
            'x+t' => true, 'c+t' => true, 'a+' => true
        ],
        'write' => [
            'w' => true, 'w+' => true, 'rw' => true, 'r+' => true, 'x+' => true,
            'c+' => true, 'wb' => true, 'w+b' => true, 'r+b' => true,
            'x+b' => true, 'c+b' => true, 'w+t' => true, 'r+t' => true,
            'x+t' => true, 'c+t' => true, 'a' => true, 'a+' => true
        ]
    ];

    /**
     * This constructor accepts an associative array of options.
     *
     * - size: (int) If a read stream would otherwise have an indeterminate
     *   size, but the size is known due to foreknownledge, then you can
     *   provide that size, in bytes.
     * - metadata: (array) Any additional metadata to return when the metadata
     *   of the stream is accessed.
     *
     * @param resource $stream  Stream resource to wrap.
     * @param array    $options Associative array of options.
     *
     * @throws \InvalidArgumentException if the stream is not a stream resource
     */
    public function __construct($stream, $options = [])
    {
        if (!is_resource($stream)) {
            throw new \InvalidArgumentException('Stream must be a resource');
        }

        if (isset($options['size'])) {
            $this->size = $options['size'];
        }

        $this->customMetadata = isset($options['metadata'])
            ? $options['metadata']
            : [];

        $this->stream = $stream;
        $meta = stream_get_meta_data($this->stream);
        $this->seekable = $meta['seekable'];
        $this->readable = isset(self::$readWriteHash['read'][$meta['mode']]);
        $this->writable = isset(self::$readWriteHash['write'][$meta['mode']]);
        $this->uri = $this->getMetadata('uri');
    }

    public function __get($name)
    {
        if ($name == 'stream') {
            throw new \RuntimeException('The stream is detached');
        }

        throw new \BadMethodCallException('No value for ' . $name);
    }

    /**
     * Closes the stream when the destructed
     */
    public function __destruct()
    {
        $this->close();
    }

    public function __toString()
    {
        try {
            $this->seek(0);
            return (string) stream_get_contents($this->stream);
        } catch (\Exception $e) {
            return '';
        }
    }

    public function getContents()
    {
        $contents = stream_get_contents($this->stream);

        if ($contents === false) {
            throw new \RuntimeException('Unable to read stream contents');
        }

        return $contents;
    }

    public function close()
    {
        if (isset($this->stream)) {
            if (is_resource($this->stream)) {
                fclose($this->stream);
            }
            $this->detach();
        }
    }

    public function detach()
    {
        if (!isset($this->stream)) {
            return null;
        }

        $result = $this->stream;
        unset($this->stream);
        $this->size = $this->uri = null;
        $this->readable = $this->writable = $this->seekable = false;

        return $result;
    }

    public function getSize()
    {
        if ($this->size !== null) {
            return $this->size;
        }

        if (!isset($this->stream)) {
            return null;
        }

        // Clear the stat cache if the stream has a URI
        if ($this->uri) {
            clearstatcache(true, $this->uri);
        }

        $stats = fstat($this->stream);
        if (isset($stats['size'])) {
            $this->size = $stats['size'];
            return $this->size;
        }

        return null;
    }

    public function isReadable()
    {
        return $this->readable;
    }

    public function isWritable()
    {
        return $this->writable;
    }

    public function isSeekable()
    {
        return $this->seekable;
    }

    public function eof()
    {
        return !$this->stream || feof($this->stream);
    }

    public function tell()
    {
        $result = ftell($this->stream);

        if ($result === false) {
            throw new \RuntimeException('Unable to determine stream position');
        }

        return $result;
    }

    public function rewind()
    {
        $this->seek(0);
    }

    public function seek($offset, $whence = SEEK_SET)
    {
        if (!$this->seekable) {
            throw new \RuntimeException('Stream is not seekable');
        } elseif (fseek($this->stream, $offset, $whence) === -1) {
            throw new \RuntimeException('Unable to seek to stream position '
                . $offset . ' with whence ' . var_export($whence, true));
        }
    }

    public function read($length)
    {
        if (!$this->readable) {
            throw new \RuntimeException('Cannot read from non-readable stream');
        }

        return fread($this->stream, $length);
    }

    public function write($string)
    {
        if (!$this->writable) {
            throw new \RuntimeException('Cannot write to a non-writable stream');
        }

        // We can't know the size after writing anything
        $this->size = null;
        $result = fwrite($this->stream, $string);

        if ($result === false) {
            throw new \RuntimeException('Unable to write to stream');
        }

        return $result;
    }

    public function getMetadata($key = null)
    {
        if (!isset($this->stream)) {
            return $key ? null : [];
        } elseif (!$key) {
            return $this->customMetadata + stream_get_meta_data($this->stream);
        } elseif (isset($this->customMetadata[$key])) {
            return $this->customMetadata[$key];
        }

        $meta = stream_get_meta_data($this->stream);

        return isset($meta[$key]) ? $meta[$key] : null;
    }
}
<?php
namespace GuzzleHttp\Psr7;

use Psr\Http\Message\StreamInterface;

/**
 * Stream decorator trait
 * @property StreamInterface stream
 */
trait StreamDecoratorTrait
{
    /**
     * @param StreamInterface $stream Stream to decorate
     */
    public function __construct(StreamInterface $stream)
    {
        $this->stream = $stream;
    }

    /**
     * Magic method used to create a new stream if streams are not added in
     * the constructor of a decorator (e.g., LazyOpenStream).
     *
     * @param string $name Name of the property (allows "stream" only).
     *
     * @return StreamInterface
     */
    public function __get($name)
    {
        if ($name == 'stream') {
            $this->stream = $this->createStream();
            return $this->stream;
        }

        throw new \UnexpectedValueException("$name not found on class");
    }

    public function __toString()
    {
        try {
            if ($this->isSeekable()) {
                $this->seek(0);
            }
            return $this->getContents();
        } catch (\Exception $e) {
            // Really, PHP? https://bugs.php.net/bug.php?id=53648
            trigger_error('StreamDecorator::__toString exception: '
                . (string) $e, E_USER_ERROR);
            return '';
        }
    }

    public function getContents()
    {
        return copy_to_string($this);
    }

    /**
     * Allow decorators to implement custom methods
     *
     * @param string $method Missing method name
     * @param array  $args   Method arguments
     *
     * @return mixed
     */
    public function __call($method, array $args)
    {
        $result = call_user_func_array([$this->stream, $method], $args);

        // Always return the wrapped object if the result is a return $this
        return $result === $this->stream ? $this : $result;
    }

    public function close()
    {
        $this->stream->close();
    }

    public function getMetadata($key = null)
    {
        return $this->stream->getMetadata($key);
    }

    public function detach()
    {
        return $this->stream->detach();
    }

    public function getSize()
    {
        return $this->stream->getSize();
    }

    public function eof()
    {
        return $this->stream->eof();
    }

    public function tell()
    {
        return $this->stream->tell();
    }

    public function isReadable()
    {
        return $this->stream->isReadable();
    }

    public function isWritable()
    {
        return $this->stream->isWritable();
    }

    public function isSeekable()
    {
        return $this->stream->isSeekable();
    }

    public function rewind()
    {
        $this->seek(0);
    }

    public function seek($offset, $whence = SEEK_SET)
    {
        $this->stream->seek($offset, $whence);
    }

    public function read($length)
    {
        return $this->stream->read($length);
    }

    public function write($string)
    {
        return $this->stream->write($string);
    }

    /**
     * Implement in subclasses to dynamically create streams when requested.
     *
     * @return StreamInterface
     * @throws \BadMethodCallException
     */
    protected function createStream()
    {
        throw new \BadMethodCallException('Not implemented');
    }
}
<?php
namespace GuzzleHttp\Psr7;

use Psr\Http\Message\StreamInterface;

/**
 * Converts Guzzle streams into PHP stream resources.
 */
class StreamWrapper
{
    /** @var resource */
    public $context;

    /** @var StreamInterface */
    private $stream;

    /** @var string r, r+, or w */
    private $mode;

    /**
     * Returns a resource representing the stream.
     *
     * @param StreamInterface $stream The stream to get a resource for
     *
     * @return resource
     * @throws \InvalidArgumentException if stream is not readable or writable
     */
    public static function getResource(StreamInterface $stream)
    {
        self::register();

        if ($stream->isReadable()) {
            $mode = $stream->isWritable() ? 'r+' : 'r';
        } elseif ($stream->isWritable()) {
            $mode = 'w';
        } else {
            throw new \InvalidArgumentException('The stream must be readable, '
                . 'writable, or both.');
        }

        return fopen('guzzle://stream', $mode, null, stream_context_create([
            'guzzle' => ['stream' => $stream]
        ]));
    }

    /**
     * Registers the stream wrapper if needed
     */
    public static function register()
    {
        if (!in_array('guzzle', stream_get_wrappers())) {
            stream_wrapper_register('guzzle', __CLASS__);
        }
    }

    public function stream_open($path, $mode, $options, &$opened_path)
    {
        $options = stream_context_get_options($this->context);

        if (!isset($options['guzzle']['stream'])) {
            return false;
        }

        $this->mode = $mode;
        $this->stream = $options['guzzle']['stream'];

        return true;
    }

    public function stream_read($count)
    {
        return $this->stream->read($count);
    }

    public function stream_write($data)
    {
        return (int) $this->stream->write($data);
    }

    public function stream_tell()
    {
        return $this->stream->tell();
    }

    public function stream_eof()
    {
        return $this->stream->eof();
    }

    public function stream_seek($offset, $whence)
    {
        $this->stream->seek($offset, $whence);

        return true;
    }

    public function stream_stat()
    {
        static $modeMap = [
            'r'  => 33060,
            'r+' => 33206,
            'w'  => 33188
        ];

        return [
            'dev'     => 0,
            'ino'     => 0,
            'mode'    => $modeMap[$this->mode],
            'nlink'   => 0,
            'uid'     => 0,
            'gid'     => 0,
            'rdev'    => 0,
            'size'    => $this->stream->getSize() ?: 0,
            'atime'   => 0,
            'mtime'   => 0,
            'ctime'   => 0,
            'blksize' => 0,
            'blocks'  => 0
        ];
    }
}
<?php
namespace GuzzleHttp\Psr7;

use Psr\Http\Message\UriInterface;

/**
 * Basic PSR-7 URI implementation.
 *
 * @link https://github.com/phly/http This class is based upon
 *     Matthew Weier O'Phinney's URI implementation in phly/http.
 */
class Uri implements UriInterface
{
    private static $schemes = [
        'http'  => 80,
        'https' => 443,
    ];

    private static $charUnreserved = 'a-zA-Z0-9_\-\.~';
    private static $charSubDelims = '!\$&\'\(\)\*\+,;=';
    private static $replaceQuery = ['=' => '%3D', '&' => '%26'];

    /** @var string Uri scheme. */
    private $scheme = '';

    /** @var string Uri user info. */
    private $userInfo = '';

    /** @var string Uri host. */
    private $host = '';

    /** @var int|null Uri port. */
    private $port;

    /** @var string Uri path. */
    private $path = '';

    /** @var string Uri query string. */
    private $query = '';

    /** @var string Uri fragment. */
    private $fragment = '';

    /**
     * @param string $uri URI to parse and wrap.
     */
    public function __construct($uri = '')
    {
        if ($uri != null) {
            $parts = parse_url($uri);
            if ($parts === false) {
                throw new \InvalidArgumentException("Unable to parse URI: $uri");
            }
            $this->applyParts($parts);
        }
    }

    public function __toString()
    {
        return self::createUriString(
            $this->scheme,
            $this->getAuthority(),
            $this->getPath(),
            $this->query,
            $this->fragment
        );
    }

    /**
     * Removes dot segments from a path and returns the new path.
     *
     * @param string $path
     *
     * @return string
     * @link http://tools.ietf.org/html/rfc3986#section-5.2.4
     */
    public static function removeDotSegments($path)
    {
        static $noopPaths = ['' => true, '/' => true, '*' => true];
        static $ignoreSegments = ['.' => true, '..' => true];

        if (isset($noopPaths[$path])) {
            return $path;
        }

        $results = [];
        $segments = explode('/', $path);
        foreach ($segments as $segment) {
            if ($segment == '..') {
                array_pop($results);
            } elseif (!isset($ignoreSegments[$segment])) {
                $results[] = $segment;
            }
        }

        $newPath = implode('/', $results);
        // Add the leading slash if necessary
        if (substr($path, 0, 1) === '/' &&
            substr($newPath, 0, 1) !== '/'
        ) {
            $newPath = '/' . $newPath;
        }

        // Add the trailing slash if necessary
        if ($newPath != '/' && isset($ignoreSegments[end($segments)])) {
            $newPath .= '/';
        }

        return $newPath;
    }

    /**
     * Resolve a base URI with a relative URI and return a new URI.
     *
     * @param UriInterface $base Base URI
     * @param string       $rel  Relative URI
     *
     * @return UriInterface
     */
    public static function resolve(UriInterface $base, $rel)
    {
        if ($rel === null || $rel === '') {
            return $base;
        }

        if (!($rel instanceof UriInterface)) {
            $rel = new self($rel);
        }

        // Return the relative uri as-is if it has a scheme.
        if ($rel->getScheme()) {
            return $rel->withPath(static::removeDotSegments($rel->getPath()));
        }

        $relParts = [
            'scheme'    => $rel->getScheme(),
            'authority' => $rel->getAuthority(),
            'path'      => $rel->getPath(),
            'query'     => $rel->getQuery(),
            'fragment'  => $rel->getFragment()
        ];

        $parts = [
            'scheme'    => $base->getScheme(),
            'authority' => $base->getAuthority(),
            'path'      => $base->getPath(),
            'query'     => $base->getQuery(),
            'fragment'  => $base->getFragment()
        ];

        if (!empty($relParts['authority'])) {
            $parts['authority'] = $relParts['authority'];
            $parts['path'] = self::removeDotSegments($relParts['path']);
            $parts['query'] = $relParts['query'];
            $parts['fragment'] = $relParts['fragment'];
        } elseif (!empty($relParts['path'])) {
            if (substr($relParts['path'], 0, 1) == '/') {
                $parts['path'] = self::removeDotSegments($relParts['path']);
                $parts['query'] = $relParts['query'];
                $parts['fragment'] = $relParts['fragment'];
            } else {
                if (!empty($parts['authority']) && empty($parts['path'])) {
                    $mergedPath = '/';
                } else {
                    $mergedPath = substr($parts['path'], 0, strrpos($parts['path'], '/') + 1);
                }
                $parts['path'] = self::removeDotSegments($mergedPath . $relParts['path']);
                $parts['query'] = $relParts['query'];
                $parts['fragment'] = $relParts['fragment'];
            }
        } elseif (!empty($relParts['query'])) {
            $parts['query'] = $relParts['query'];
        } elseif ($relParts['fragment'] != null) {
            $parts['fragment'] = $relParts['fragment'];
        }

        return new self(static::createUriString(
            $parts['scheme'],
            $parts['authority'],
            $parts['path'],
            $parts['query'],
            $parts['fragment']
        ));
    }

    /**
     * Create a new URI with a specific query string value removed.
     *
     * Any existing query string values that exactly match the provided key are
     * removed.
     *
     * Note: this function will convert "=" to "%3D" and "&" to "%26".
     *
     * @param UriInterface $uri URI to use as a base.
     * @param string       $key Query string key value pair to remove.
     *
     * @return UriInterface
     */
    public static function withoutQueryValue(UriInterface $uri, $key)
    {
        $current = $uri->getQuery();
        if (!$current) {
            return $uri;
        }

        $result = [];
        foreach (explode('&', $current) as $part) {
            if (explode('=', $part)[0] !== $key) {
                $result[] = $part;
            };
        }

        return $uri->withQuery(implode('&', $result));
    }

    /**
     * Create a new URI with a specific query string value.
     *
     * Any existing query string values that exactly match the provided key are
     * removed and replaced with the given key value pair.
     *
     * Note: this function will convert "=" to "%3D" and "&" to "%26".
     *
     * @param UriInterface $uri URI to use as a base.
     * @param string $key   Key to set.
     * @param string $value Value to set.
     *
     * @return UriInterface
     */
    public static function withQueryValue(UriInterface $uri, $key, $value)
    {
        $current = $uri->getQuery();
        $key = strtr($key, self::$replaceQuery);

        if (!$current) {
            $result = [];
        } else {
            $result = [];
            foreach (explode('&', $current) as $part) {
                if (explode('=', $part)[0] !== $key) {
                    $result[] = $part;
                };
            }
        }

        if ($value !== null) {
            $result[] = $key . '=' . strtr($value, self::$replaceQuery);
        } else {
            $result[] = $key;
        }

        return $uri->withQuery(implode('&', $result));
    }

    /**
     * Create a URI from a hash of parse_url parts.
     *
     * @param array $parts
     *
     * @return self
     */
    public static function fromParts(array $parts)
    {
        $uri = new self();
        $uri->applyParts($parts);
        return $uri;
    }

    public function getScheme()
    {
        return $this->scheme;
    }

    public function getAuthority()
    {
        if (empty($this->host)) {
            return '';
        }

        $authority = $this->host;
        if (!empty($this->userInfo)) {
            $authority = $this->userInfo . '@' . $authority;
        }

        if ($this->isNonStandardPort($this->scheme, $this->host, $this->port)) {
            $authority .= ':' . $this->port;
        }

        return $authority;
    }

    public function getUserInfo()
    {
        return $this->userInfo;
    }

    public function getHost()
    {
        return $this->host;
    }

    public function getPort()
    {
        return $this->port;
    }

    public function getPath()
    {
        return $this->path == null ? '' : $this->path;
    }

    public function getQuery()
    {
        return $this->query;
    }

    public function getFragment()
    {
        return $this->fragment;
    }

    public function withScheme($scheme)
    {
        $scheme = $this->filterScheme($scheme);

        if ($this->scheme === $scheme) {
            return $this;
        }

        $new = clone $this;
        $new->scheme = $scheme;
        $new->port = $new->filterPort($new->scheme, $new->host, $new->port);
        return $new;
    }

    public function withUserInfo($user, $password = null)
    {
        $info = $user;
        if ($password) {
            $info .= ':' . $password;
        }

        if ($this->userInfo === $info) {
            return $this;
        }

        $new = clone $this;
        $new->userInfo = $info;
        return $new;
    }

    public function withHost($host)
    {
        if ($this->host === $host) {
            return $this;
        }

        $new = clone $this;
        $new->host = $host;
        return $new;
    }

    public function withPort($port)
    {
        $port = $this->filterPort($this->scheme, $this->host, $port);

        if ($this->port === $port) {
            return $this;
        }

        $new = clone $this;
        $new->port = $port;
        return $new;
    }

    public function withPath($path)
    {
        if (!is_string($path)) {
            throw new \InvalidArgumentException(
                'Invalid path provided; must be a string'
            );
        }

        $path = $this->filterPath($path);

        if ($this->path === $path) {
            return $this;
        }

        $new = clone $this;
        $new->path = $path;
        return $new;
    }

    public function withQuery($query)
    {
        if (!is_string($query) && !method_exists($query, '__toString')) {
            throw new \InvalidArgumentException(
                'Query string must be a string'
            );
        }

        $query = (string) $query;
        if (substr($query, 0, 1) === '?') {
            $query = substr($query, 1);
        }

        $query = $this->filterQueryAndFragment($query);

        if ($this->query === $query) {
            return $this;
        }

        $new = clone $this;
        $new->query = $query;
        return $new;
    }

    public function withFragment($fragment)
    {
        if (substr($fragment, 0, 1) === '#') {
            $fragment = substr($fragment, 1);
        }

        $fragment = $this->filterQueryAndFragment($fragment);

        if ($this->fragment === $fragment) {
            return $this;
        }

        $new = clone $this;
        $new->fragment = $fragment;
        return $new;
    }

    /**
     * Apply parse_url parts to a URI.
     *
     * @param $parts Array of parse_url parts to apply.
     */
    private function applyParts(array $parts)
    {
        $this->scheme = isset($parts['scheme'])
            ? $this->filterScheme($parts['scheme'])
            : '';
        $this->userInfo = isset($parts['user']) ? $parts['user'] : '';
        $this->host = isset($parts['host']) ? $parts['host'] : '';
        $this->port = !empty($parts['port'])
            ? $this->filterPort($this->scheme, $this->host, $parts['port'])
            : null;
        $this->path = isset($parts['path'])
            ? $this->filterPath($parts['path'])
            : '';
        $this->query = isset($parts['query'])
            ? $this->filterQueryAndFragment($parts['query'])
            : '';
        $this->fragment = isset($parts['fragment'])
            ? $this->filterQueryAndFragment($parts['fragment'])
            : '';
        if (isset($parts['pass'])) {
            $this->userInfo .= ':' . $parts['pass'];
        }
    }

    /**
     * Create a URI string from its various parts
     *
     * @param string $scheme
     * @param string $authority
     * @param string $path
     * @param string $query
     * @param string $fragment
     * @return string
     */
    private static function createUriString($scheme, $authority, $path, $query, $fragment)
    {
        $uri = '';

        if (!empty($scheme)) {
            $uri .= $scheme . ':';
        }

        $hierPart = '';

        if (!empty($authority)) {
            if (!empty($scheme)) {
                $hierPart .= '//';
            }
            $hierPart .= $authority;
        }

        if ($path != null) {
            // Add a leading slash if necessary.
            if ($hierPart && substr($path, 0, 1) !== '/') {
                $hierPart .= '/';
            }
            $hierPart .= $path;
        }

        $uri .= $hierPart;

        if ($query != null) {
            $uri .= '?' . $query;
        }

        if ($fragment != null) {
            $uri .= '#' . $fragment;
        }

        return $uri;
    }

    /**
     * Is a given port non-standard for the current scheme?
     *
     * @param string $scheme
     * @param string $host
     * @param int $port
     * @return bool
     */
    private static function isNonStandardPort($scheme, $host, $port)
    {
        if (!$scheme && $port) {
            return true;
        }

        if (!$host || !$port) {
            return false;
        }

        return !isset(static::$schemes[$scheme]) || $port !== static::$schemes[$scheme];
    }

    /**
     * @param string $scheme
     *
     * @return string
     */
    private function filterScheme($scheme)
    {
        $scheme = strtolower($scheme);
        $scheme = rtrim($scheme, ':/');

        return $scheme;
    }

    /**
     * @param string $scheme
     * @param string $host
     * @param int $port
     *
     * @return int|null
     *
     * @throws \InvalidArgumentException If the port is invalid.
     */
    private function filterPort($scheme, $host, $port)
    {
        if (null !== $port) {
            $port = (int) $port;
            if (1 > $port || 0xffff < $port) {
                throw new \InvalidArgumentException(
                    sprintf('Invalid port: %d. Must be between 1 and 65535', $port)
                );
            }
        }

        return $this->isNonStandardPort($scheme, $host, $port) ? $port : null;
    }

    /**
     * Filters the path of a URI
     *
     * @param $path
     *
     * @return string
     */
    private function filterPath($path)
    {
        return preg_replace_callback(
            '/(?:[^' . self::$charUnreserved . self::$charSubDelims . ':@\/%]+|%(?![A-Fa-f0-9]{2}))/',
            [$this, 'rawurlencodeMatchZero'],
            $path
        );
    }

    /**
     * Filters the query string or fragment of a URI.
     *
     * @param $str
     *
     * @return string
     */
    private function filterQueryAndFragment($str)
    {
        return preg_replace_callback(
            '/(?:[^' . self::$charUnreserved . self::$charSubDelims . '%:@\/\?]+|%(?![A-Fa-f0-9]{2}))/',
            [$this, 'rawurlencodeMatchZero'],
            $str
        );
    }

    private function rawurlencodeMatchZero(array $match)
    {
        return rawurlencode($match[0]);
    }
}
<?php
namespace GuzzleHttp\Tests\Psr7;

use GuzzleHttp\Psr7\AppendStream;
use GuzzleHttp\Psr7;

class AppendStreamTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Each stream must be readable
     */
    public function testValidatesStreamsAreReadable()
    {
        $a = new AppendStream();
        $s = $this->getMockBuilder('Psr\Http\Message\StreamInterface')
            ->setMethods(['isReadable'])
            ->getMockForAbstractClass();
        $s->expects($this->once())
            ->method('isReadable')
            ->will($this->returnValue(false));
        $a->addStream($s);
    }

    /**
     * @expectedException \RuntimeException
     * @expectedExceptionMessage The AppendStream can only seek with SEEK_SET
     */
    public function testValidatesSeekType()
    {
        $a = new AppendStream();
        $a->seek(100, SEEK_CUR);
    }

    /**
     * @expectedException \RuntimeException
     * @expectedExceptionMessage Unable to seek stream 0 of the AppendStream
     */
    public function testTriesToRewindOnSeek()
    {
        $a = new AppendStream();
        $s = $this->getMockBuilder('Psr\Http\Message\StreamInterface')
            ->setMethods(['isReadable', 'rewind', 'isSeekable'])
            ->getMockForAbstractClass();
        $s->expects($this->once())
            ->method('isReadable')
            ->will($this->returnValue(true));
        $s->expects($this->once())
            ->method('isSeekable')
            ->will($this->returnValue(true));
        $s->expects($this->once())
            ->method('rewind')
            ->will($this->throwException(new \RuntimeException()));
        $a->addStream($s);
        $a->seek(10);
    }

    public function testSeeksToPositionByReading()
    {
        $a = new AppendStream([
            Psr7\stream_for('foo'),
            Psr7\stream_for('bar'),
            Psr7\stream_for('baz'),
        ]);

        $a->seek(3);
        $this->assertEquals(3, $a->tell());
        $this->assertEquals('bar', $a->read(3));

        $a->seek(6);
        $this->assertEquals(6, $a->tell());
        $this->assertEquals('baz', $a->read(3));
    }

    public function testDetachesEachStream()
    {
        $s1 = Psr7\stream_for('foo');
        $s2 = Psr7\stream_for('bar');
        $a = new AppendStream([$s1, $s2]);
        $this->assertSame('foobar', (string) $a);
        $a->detach();
        $this->assertSame('', (string) $a);
        $this->assertSame(0, $a->getSize());
    }

    public function testClosesEachStream()
    {
        $s1 = Psr7\stream_for('foo');
        $a = new AppendStream([$s1]);
        $a->close();
        $this->assertSame('', (string) $a);
    }

    /**
     * @expectedExceptionMessage Cannot write to an AppendStream
     * @expectedException \RuntimeException
     */
    public function testIsNotWritable()
    {
        $a = new AppendStream([Psr7\stream_for('foo')]);
        $this->assertFalse($a->isWritable());
        $this->assertTrue($a->isSeekable());
        $this->assertTrue($a->isReadable());
        $a->write('foo');
    }

    public function testDoesNotNeedStreams()
    {
        $a = new AppendStream();
        $this->assertEquals('', (string) $a);
    }

    public function testCanReadFromMultipleStreams()
    {
        $a = new AppendStream([
            Psr7\stream_for('foo'),
            Psr7\stream_for('bar'),
            Psr7\stream_for('baz'),
        ]);
        $this->assertFalse($a->eof());
        $this->assertSame(0, $a->tell());
        $this->assertEquals('foo', $a->read(3));
        $this->assertEquals('bar', $a->read(3));
        $this->assertEquals('baz', $a->read(3));
        $this->assertSame('', $a->read(1));
        $this->assertTrue($a->eof());
        $this->assertSame(9, $a->tell());
        $this->assertEquals('foobarbaz', (string) $a);
    }

    public function testCanDetermineSizeFromMultipleStreams()
    {
        $a = new AppendStream([
            Psr7\stream_for('foo'),
            Psr7\stream_for('bar')
        ]);
        $this->assertEquals(6, $a->getSize());

        $s = $this->getMockBuilder('Psr\Http\Message\StreamInterface')
            ->setMethods(['isSeekable', 'isReadable'])
            ->getMockForAbstractClass();
        $s->expects($this->once())
            ->method('isSeekable')
            ->will($this->returnValue(null));
        $s->expects($this->once())
            ->method('isReadable')
            ->will($this->returnValue(true));
        $a->addStream($s);
        $this->assertNull($a->getSize());
    }

    public function testCatchesExceptionsWhenCastingToString()
    {
        $s = $this->getMockBuilder('Psr\Http\Message\StreamInterface')
            ->setMethods(['isSeekable', 'read', 'isReadable', 'eof'])
            ->getMockForAbstractClass();
        $s->expects($this->once())
            ->method('isSeekable')
            ->will($this->returnValue(true));
        $s->expects($this->once())
            ->method('read')
            ->will($this->throwException(new \RuntimeException('foo')));
        $s->expects($this->once())
            ->method('isReadable')
            ->will($this->returnValue(true));
        $s->expects($this->any())
            ->method('eof')
            ->will($this->returnValue(false));
        $a = new AppendStream([$s]);
        $this->assertFalse($a->eof());
        $this->assertSame('', (string) $a);
    }

    public function testCanDetach()
    {
        $s = new AppendStream();
        $s->detach();
    }

    public function testReturnsEmptyMetadata()
    {
        $s = new AppendStream();
        $this->assertEquals([], $s->getMetadata());
        $this->assertNull($s->getMetadata('foo'));
    }
}
<?php
namespace GuzzleHttp\Tests\Psr7;

require __DIR__ . '/../vendor/autoload.php';

class HasToString
{
    public function __toString() {
        return 'foo';
    }
}
<?php
namespace GuzzleHttp\Tests\Psr7;

use GuzzleHttp\Psr7\BufferStream;

class BufferStreamTest extends \PHPUnit_Framework_TestCase
{
    public function testHasMetadata()
    {
        $b = new BufferStream(10);
        $this->assertTrue($b->isReadable());
        $this->assertTrue($b->isWritable());
        $this->assertFalse($b->isSeekable());
        $this->assertEquals(null, $b->getMetadata('foo'));
        $this->assertEquals(10, $b->getMetadata('hwm'));
        $this->assertEquals([], $b->getMetadata());
    }

    public function testRemovesReadDataFromBuffer()
    {
        $b = new BufferStream();
        $this->assertEquals(3, $b->write('foo'));
        $this->assertEquals(3, $b->getSize());
        $this->assertFalse($b->eof());
        $this->assertEquals('foo', $b->read(10));
        $this->assertTrue($b->eof());
        $this->assertEquals('', $b->read(10));
    }

    /**
     * @expectedException \RuntimeException
     * @expectedExceptionMessage Cannot determine the position of a BufferStream
     */
    public function testCanCastToStringOrGetContents()
    {
        $b = new BufferStream();
        $b->write('foo');
        $b->write('baz');
        $this->assertEquals('foo', $b->read(3));
        $b->write('bar');
        $this->assertEquals('bazbar', (string) $b);
        $b->tell();
    }

    public function testDetachClearsBuffer()
    {
        $b = new BufferStream();
        $b->write('foo');
        $b->detach();
        $this->assertTrue($b->eof());
        $this->assertEquals(3, $b->write('abc'));
        $this->assertEquals('abc', $b->read(10));
    }

    public function testExceedingHighwaterMarkReturnsFalseButStillBuffers()
    {
        $b = new BufferStream(5);
        $this->assertEquals(3, $b->write('hi '));
        $this->assertFalse($b->write('hello'));
        $this->assertEquals('hi hello', (string) $b);
        $this->assertEquals(4, $b->write('test'));
    }
}
<?php
namespace GuzzleHttp\Tests\Psr7;

use GuzzleHttp\Psr7;
use GuzzleHttp\Psr7\CachingStream;

/**
 * @covers GuzzleHttp\Psr7\CachingStream
 */
class CachingStreamTest extends \PHPUnit_Framework_TestCase
{
    /** @var CachingStream */
    protected $body;
    protected $decorated;

    public function setUp()
    {
        $this->decorated = Psr7\stream_for('testing');
        $this->body = new CachingStream($this->decorated);
    }

    public function tearDown()
    {
        $this->decorated->close();
        $this->body->close();
    }

    public function testUsesRemoteSizeIfPossible()
    {
        $body = Psr7\stream_for('test');
        $caching = new CachingStream($body);
        $this->assertEquals(4, $caching->getSize());
    }

    public function testReadsUntilCachedToByte()
    {
        $this->body->seek(5);
        $this->assertEquals('n', $this->body->read(1));
        $this->body->seek(0);
        $this->assertEquals('t', $this->body->read(1));
    }

    public function testCanSeekNearEndWithSeekEnd()
    {
        $baseStream = Psr7\stream_for(implode('', range('a', 'z')));
        $cached = new CachingStream($baseStream);
        $cached->seek(-1, SEEK_END);
        $this->assertEquals(25, $baseStream->tell());
        $this->assertEquals('z', $cached->read(1));
        $this->assertEquals(26, $cached->getSize());
    }

    public function testCanSeekToEndWithSeekEnd()
    {
        $baseStream = Psr7\stream_for(implode('', range('a', 'z')));
        $cached = new CachingStream($baseStream);
        $cached->seek(0, SEEK_END);
        $this->assertEquals(26, $baseStream->tell());
        $this->assertEquals('', $cached->read(1));
        $this->assertEquals(26, $cached->getSize());
    }

    public function testCanUseSeekEndWithUnknownSize()
    {
        $baseStream = Psr7\stream_for('testing');
        $decorated = Psr7\FnStream::decorate($baseStream, [
            'getSize' => function () { return null; }
        ]);
        $cached = new CachingStream($decorated);
        $cached->seek(-1, SEEK_END);
        $this->assertEquals('g', $cached->read(1));
    }

    public function testRewindUsesSeek()
    {
        $a = Psr7\stream_for('foo');
        $d = $this->getMockBuilder('GuzzleHttp\Psr7\CachingStream')
            ->setMethods(array('seek'))
            ->setConstructorArgs(array($a))
            ->getMock();
        $d->expects($this->once())
            ->method('seek')
            ->with(0)
            ->will($this->returnValue(true));
        $d->seek(0);
    }

    public function testCanSeekToReadBytes()
    {
        $this->assertEquals('te', $this->body->read(2));
        $this->body->seek(0);
        $this->assertEquals('test', $this->body->read(4));
        $this->assertEquals(4, $this->body->tell());
        $this->body->seek(2);
        $this->assertEquals(2, $this->body->tell());
        $this->body->seek(2, SEEK_CUR);
        $this->assertEquals(4, $this->body->tell());
        $this->assertEquals('ing', $this->body->read(3));
    }

    public function testCanSeekToReadBytesWithPartialBodyReturned()
    {
        $stream = fopen('php://temp', 'r+');
        fwrite($stream, 'testing');
        fseek($stream, 0);

        $this->decorated = $this->getMockBuilder('\GuzzleHttp\Psr7\Stream')
            ->setConstructorArgs([$stream])
            ->setMethods(['read'])
            ->getMock();

        $this->decorated->expects($this->exactly(2))
            ->method('read')
            ->willReturnCallback(function($length) use ($stream){
                return fread($stream, 2);
            });

        $this->body = new CachingStream($this->decorated);

        $this->assertEquals(0, $this->body->tell());
        $this->body->seek(4, SEEK_SET);
        $this->assertEquals(4, $this->body->tell());

        $this->body->seek(0);
        $this->assertEquals('test', $this->body->read(4));
    }

    public function testWritesToBufferStream()
    {
        $this->body->read(2);
        $this->body->write('hi');
        $this->body->seek(0);
        $this->assertEquals('tehiing', (string) $this->body);
    }

    public function testSkipsOverwrittenBytes()
    {
        $decorated = Psr7\stream_for(
            implode("\n", array_map(function ($n) {
                return str_pad($n, 4, '0', STR_PAD_LEFT);
            }, range(0, 25)))
        );

        $body = new CachingStream($decorated);

        $this->assertEquals("0000\n", Psr7\readline($body));
        $this->assertEquals("0001\n", Psr7\readline($body));
        // Write over part of the body yet to be read, so skip some bytes
        $this->assertEquals(5, $body->write("TEST\n"));
        $this->assertEquals(5, $this->readAttribute($body, 'skipReadBytes'));
        // Read, which skips bytes, then reads
        $this->assertEquals("0003\n", Psr7\readline($body));
        $this->assertEquals(0, $this->readAttribute($body, 'skipReadBytes'));
        $this->assertEquals("0004\n", Psr7\readline($body));
        $this->assertEquals("0005\n", Psr7\readline($body));

        // Overwrite part of the cached body (so don't skip any bytes)
        $body->seek(5);
        $this->assertEquals(5, $body->write("ABCD\n"));
        $this->assertEquals(0, $this->readAttribute($body, 'skipReadBytes'));
        $this->assertEquals("TEST\n", Psr7\readline($body));
        $this->assertEquals("0003\n", Psr7\readline($body));
        $this->assertEquals("0004\n", Psr7\readline($body));
        $this->assertEquals("0005\n", Psr7\readline($body));
        $this->assertEquals("0006\n", Psr7\readline($body));
        $this->assertEquals(5, $body->write("1234\n"));
        $this->assertEquals(5, $this->readAttribute($body, 'skipReadBytes'));

        // Seek to 0 and ensure the overwritten bit is replaced
        $body->seek(0);
        $this->assertEquals("0000\nABCD\nTEST\n0003\n0004\n0005\n0006\n1234\n0008\n0009\n", $body->read(50));

        // Ensure that casting it to a string does not include the bit that was overwritten
        $this->assertContains("0000\nABCD\nTEST\n0003\n0004\n0005\n0006\n1234\n0008\n0009\n", (string) $body);
    }

    public function testClosesBothStreams()
    {
        $s = fopen('php://temp', 'r');
        $a = Psr7\stream_for($s);
        $d = new CachingStream($a);
        $d->close();
        $this->assertFalse(is_resource($s));
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testEnsuresValidWhence()
    {
        $this->body->seek(10, -123456);
    }
}
<?php
namespace GuzzleHttp\Tests\Psr7;

use GuzzleHttp\Psr7\BufferStream;
use GuzzleHttp\Psr7\DroppingStream;

class DroppingStreamTest extends \PHPUnit_Framework_TestCase
{
    public function testBeginsDroppingWhenSizeExceeded()
    {
        $stream = new BufferStream();
        $drop = new DroppingStream($stream, 5);
        $this->assertEquals(3, $drop->write('hel'));
        $this->assertEquals(2, $drop->write('lo'));
        $this->assertEquals(5, $drop->getSize());
        $this->assertEquals('hello', $drop->read(5));
        $this->assertEquals(0, $drop->getSize());
        $drop->write('12345678910');
        $this->assertEquals(5, $stream->getSize());
        $this->assertEquals(5, $drop->getSize());
        $this->assertEquals('12345', (string) $drop);
        $this->assertEquals(0, $drop->getSize());
        $drop->write('hello');
        $this->assertSame(0, $drop->write('test'));
    }
}
<?php
namespace GuzzleHttp\Tests\Psr7;

use GuzzleHttp\Psr7;
use GuzzleHttp\Psr7\FnStream;

/**
 * @covers GuzzleHttp\Psr7\FnStream
 */
class FnStreamTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @expectedException \BadMethodCallException
     * @expectedExceptionMessage seek() is not implemented in the FnStream
     */
    public function testThrowsWhenNotImplemented()
    {
        (new FnStream([]))->seek(1);
    }

    public function testProxiesToFunction()
    {
        $s = new FnStream([
            'read' => function ($len) {
                $this->assertEquals(3, $len);
                return 'foo';
            }
        ]);

        $this->assertEquals('foo', $s->read(3));
    }

    public function testCanCloseOnDestruct()
    {
        $called = false;
        $s = new FnStream([
            'close' => function () use (&$called) {
                $called = true;
            }
        ]);
        unset($s);
        $this->assertTrue($called);
    }

    public function testDoesNotRequireClose()
    {
        $s = new FnStream([]);
        unset($s);
    }

    public function testDecoratesStream()
    {
        $a = Psr7\stream_for('foo');
        $b = FnStream::decorate($a, []);
        $this->assertEquals(3, $b->getSize());
        $this->assertEquals($b->isWritable(), true);
        $this->assertEquals($b->isReadable(), true);
        $this->assertEquals($b->isSeekable(), true);
        $this->assertEquals($b->read(3), 'foo');
        $this->assertEquals($b->tell(), 3);
        $this->assertEquals($a->tell(), 3);
        $this->assertSame('', $a->read(1));
        $this->assertEquals($b->eof(), true);
        $this->assertEquals($a->eof(), true);
        $b->seek(0);
        $this->assertEquals('foo', (string) $b);
        $b->seek(0);
        $this->assertEquals('foo', $b->getContents());
        $this->assertEquals($a->getMetadata(), $b->getMetadata());
        $b->seek(0, SEEK_END);
        $b->write('bar');
        $this->assertEquals('foobar', (string) $b);
        $this->assertInternalType('resource', $b->detach());
        $b->close();
    }

    public function testDecoratesWithCustomizations()
    {
        $called = false;
        $a = Psr7\stream_for('foo');
        $b = FnStream::decorate($a, [
            'read' => function ($len) use (&$called, $a) {
                $called = true;
                return $a->read($len);
            }
        ]);
        $this->assertEquals('foo', $b->read(3));
        $this->assertTrue($called);
    }
}
<?php
namespace GuzzleHttp\Tests\Psr7;

use GuzzleHttp\Psr7;
use GuzzleHttp\Psr7\FnStream;
use GuzzleHttp\Psr7\NoSeekStream;

class FunctionsTest extends \PHPUnit_Framework_TestCase
{
    public function testCopiesToString()
    {
        $s = Psr7\stream_for('foobaz');
        $this->assertEquals('foobaz', Psr7\copy_to_string($s));
        $s->seek(0);
        $this->assertEquals('foo', Psr7\copy_to_string($s, 3));
        $this->assertEquals('baz', Psr7\copy_to_string($s, 3));
        $this->assertEquals('', Psr7\copy_to_string($s));
    }

    public function testCopiesToStringStopsWhenReadFails()
    {
        $s1 = Psr7\stream_for('foobaz');
        $s1 = FnStream::decorate($s1, [
            'read' => function () { return ''; }
        ]);
        $result = Psr7\copy_to_string($s1);
        $this->assertEquals('', $result);
    }

    public function testCopiesToStream()
    {
        $s1 = Psr7\stream_for('foobaz');
        $s2 = Psr7\stream_for('');
        Psr7\copy_to_stream($s1, $s2);
        $this->assertEquals('foobaz', (string) $s2);
        $s2 = Psr7\stream_for('');
        $s1->seek(0);
        Psr7\copy_to_stream($s1, $s2, 3);
        $this->assertEquals('foo', (string) $s2);
        Psr7\copy_to_stream($s1, $s2, 3);
        $this->assertEquals('foobaz', (string) $s2);
    }

    public function testStopsCopyToStreamWhenWriteFails()
    {
        $s1 = Psr7\stream_for('foobaz');
        $s2 = Psr7\stream_for('');
        $s2 = FnStream::decorate($s2, ['write' => function () { return 0; }]);
        Psr7\copy_to_stream($s1, $s2);
        $this->assertEquals('', (string) $s2);
    }

    public function testStopsCopyToSteamWhenWriteFailsWithMaxLen()
    {
        $s1 = Psr7\stream_for('foobaz');
        $s2 = Psr7\stream_for('');
        $s2 = FnStream::decorate($s2, ['write' => function () { return 0; }]);
        Psr7\copy_to_stream($s1, $s2, 10);
        $this->assertEquals('', (string) $s2);
    }

    public function testStopsCopyToSteamWhenReadFailsWithMaxLen()
    {
        $s1 = Psr7\stream_for('foobaz');
        $s1 = FnStream::decorate($s1, ['read' => function () { return ''; }]);
        $s2 = Psr7\stream_for('');
        Psr7\copy_to_stream($s1, $s2, 10);
        $this->assertEquals('', (string) $s2);
    }

    public function testReadsLines()
    {
        $s = Psr7\stream_for("foo\nbaz\nbar");
        $this->assertEquals("foo\n", Psr7\readline($s));
        $this->assertEquals("baz\n", Psr7\readline($s));
        $this->assertEquals("bar", Psr7\readline($s));
    }

    public function testReadsLinesUpToMaxLength()
    {
        $s = Psr7\stream_for("12345\n");
        $this->assertEquals("123", Psr7\readline($s, 4));
        $this->assertEquals("45\n", Psr7\readline($s));
    }

    public function testReadsLineUntilFalseReturnedFromRead()
    {
        $s = $this->getMockBuilder('GuzzleHttp\Psr7\Stream')
            ->setMethods(['read', 'eof'])
            ->disableOriginalConstructor()
            ->getMock();
        $s->expects($this->exactly(2))
            ->method('read')
            ->will($this->returnCallback(function () {
                static $c = false;
                if ($c) {
                    return false;
                }
                $c = true;
                return 'h';
            }));
        $s->expects($this->exactly(2))
            ->method('eof')
            ->will($this->returnValue(false));
        $this->assertEquals("h", Psr7\readline($s));
    }

    public function testCalculatesHash()
    {
        $s = Psr7\stream_for('foobazbar');
        $this->assertEquals(md5('foobazbar'), Psr7\hash($s, 'md5'));
    }

    /**
     * @expectedException \RuntimeException
     */
    public function testCalculatesHashThrowsWhenSeekFails()
    {
        $s = new NoSeekStream(Psr7\stream_for('foobazbar'));
        $s->read(2);
        Psr7\hash($s, 'md5');
    }

    public function testCalculatesHashSeeksToOriginalPosition()
    {
        $s = Psr7\stream_for('foobazbar');
        $s->seek(4);
        $this->assertEquals(md5('foobazbar'), Psr7\hash($s, 'md5'));
        $this->assertEquals(4, $s->tell());
    }

    public function testOpensFilesSuccessfully()
    {
        $r = Psr7\try_fopen(__FILE__, 'r');
        $this->assertInternalType('resource', $r);
        fclose($r);
    }

    /**
     * @expectedException \RuntimeException
     * @expectedExceptionMessage Unable to open /path/to/does/not/exist using mode r
     */
    public function testThrowsExceptionNotWarning()
    {
        Psr7\try_fopen('/path/to/does/not/exist', 'r');
    }

    public function parseQueryProvider()
    {
        return [
            // Does not need to parse when the string is empty
            ['', []],
            // Can parse mult-values items
            ['q=a&q=b', ['q' => ['a', 'b']]],
            // Can parse multi-valued items that use numeric indices
            ['q[0]=a&q[1]=b', ['q[0]' => 'a', 'q[1]' => 'b']],
            // Can parse duplicates and does not include numeric indices
            ['q[]=a&q[]=b', ['q[]' => ['a', 'b']]],
            // Ensures that the value of "q" is an array even though one value
            ['q[]=a', ['q[]' => 'a']],
            // Does not modify "." to "_" like PHP's parse_str()
            ['q.a=a&q.b=b', ['q.a' => 'a', 'q.b' => 'b']],
            // Can decode %20 to " "
            ['q%20a=a%20b', ['q a' => 'a b']],
            // Can parse funky strings with no values by assigning each to null
            ['q&a', ['q' => null, 'a' => null]],
            // Does not strip trailing equal signs
            ['data=abc=', ['data' => 'abc=']],
            // Can store duplicates without affecting other values
            ['foo=a&foo=b&?µ=c', ['foo' => ['a', 'b'], '?µ' => 'c']],
            // Sets value to null when no "=" is present
            ['foo', ['foo' => null]],
            // Preserves "0" keys.
            ['0', ['0' => null]],
            // Sets the value to an empty string when "=" is present
            ['0=', ['0' => '']],
            // Preserves falsey keys
            ['var=0', ['var' => '0']],
            ['a[b][c]=1&a[b][c]=2', ['a[b][c]' => ['1', '2']]],
            ['a[b]=c&a[d]=e', ['a[b]' => 'c', 'a[d]' => 'e']],
            // Ensure it doesn't leave things behind with repeated values
            // Can parse mult-values items
            ['q=a&q=b&q=c', ['q' => ['a', 'b', 'c']]],
        ];
    }

    /**
     * @dataProvider parseQueryProvider
     */
    public function testParsesQueries($input, $output)
    {
        $result = Psr7\parse_query($input);
        $this->assertSame($output, $result);
    }

    public function testDoesNotDecode()
    {
        $str = 'foo%20=bar';
        $data = Psr7\parse_query($str, false);
        $this->assertEquals(['foo%20' => 'bar'], $data);
    }

    /**
     * @dataProvider parseQueryProvider
     */
    public function testParsesAndBuildsQueries($input, $output)
    {
        $result = Psr7\parse_query($input, false);
        $this->assertSame($input, Psr7\build_query($result, false));
    }

    public function testEncodesWithRfc1738()
    {
        $str = Psr7\build_query(['foo bar' => 'baz+'], PHP_QUERY_RFC1738);
        $this->assertEquals('foo+bar=baz%2B', $str);
    }

    public function testEncodesWithRfc3986()
    {
        $str = Psr7\build_query(['foo bar' => 'baz+'], PHP_QUERY_RFC3986);
        $this->assertEquals('foo%20bar=baz%2B', $str);
    }

    public function testDoesNotEncode()
    {
        $str = Psr7\build_query(['foo bar' => 'baz+'], false);
        $this->assertEquals('foo bar=baz+', $str);
    }

    public function testCanControlDecodingType()
    {
        $result = Psr7\parse_query('var=foo+bar', PHP_QUERY_RFC3986);
        $this->assertEquals('foo+bar', $result['var']);
        $result = Psr7\parse_query('var=foo+bar', PHP_QUERY_RFC1738);
        $this->assertEquals('foo bar', $result['var']);
    }

    public function testParsesRequestMessages()
    {
        $req = "GET /abc HTTP/1.0\r\nHost: foo.com\r\nFoo: Bar\r\nBaz: Bam\r\nBaz: Qux\r\n\r\nTest";
        $request = Psr7\parse_request($req);
        $this->assertEquals('GET', $request->getMethod());
        $this->assertEquals('/abc', $request->getRequestTarget());
        $this->assertEquals('1.0', $request->getProtocolVersion());
        $this->assertEquals('foo.com', $request->getHeaderLine('Host'));
        $this->assertEquals('Bar', $request->getHeaderLine('Foo'));
        $this->assertEquals('Bam, Qux', $request->getHeaderLine('Baz'));
        $this->assertEquals('Test', (string) $request->getBody());
        $this->assertEquals('http://foo.com/abc', (string) $request->getUri());
    }

    public function testParsesRequestMessagesWithHttpsScheme()
    {
        $req = "PUT /abc?baz=bar HTTP/1.1\r\nHost: foo.com:443\r\n\r\n";
        $request = Psr7\parse_request($req);
        $this->assertEquals('PUT', $request->getMethod());
        $this->assertEquals('/abc?baz=bar', $request->getRequestTarget());
        $this->assertEquals('1.1', $request->getProtocolVersion());
        $this->assertEquals('foo.com:443', $request->getHeaderLine('Host'));
        $this->assertEquals('', (string) $request->getBody());
        $this->assertEquals('https://foo.com/abc?baz=bar', (string) $request->getUri());
    }

    public function testParsesRequestMessagesWithUriWhenHostIsNotFirst()
    {
        $req = "PUT / HTTP/1.1\r\nFoo: Bar\r\nHost: foo.com\r\n\r\n";
        $request = Psr7\parse_request($req);
        $this->assertEquals('PUT', $request->getMethod());
        $this->assertEquals('/', $request->getRequestTarget());
        $this->assertEquals('http://foo.com/', (string) $request->getUri());
    }

    public function testParsesRequestMessagesWithFullUri()
    {
        $req = "GET https://www.google.com:443/search?q=foobar HTTP/1.1\r\nHost: www.google.com\r\n\r\n";
        $request = Psr7\parse_request($req);
        $this->assertEquals('GET', $request->getMethod());
        $this->assertEquals('https://www.google.com:443/search?q=foobar', $request->getRequestTarget());
        $this->assertEquals('1.1', $request->getProtocolVersion());
        $this->assertEquals('www.google.com', $request->getHeaderLine('Host'));
        $this->assertEquals('', (string) $request->getBody());
        $this->assertEquals('https://www.google.com/search?q=foobar', (string) $request->getUri());
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testValidatesRequestMessages()
    {
        Psr7\parse_request("HTTP/1.1 200 OK\r\n\r\n");
    }

    public function testParsesResponseMessages()
    {
        $res = "HTTP/1.0 200 OK\r\nFoo: Bar\r\nBaz: Bam\r\nBaz: Qux\r\n\r\nTest";
        $response = Psr7\parse_response($res);
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('OK', $response->getReasonPhrase());
        $this->assertEquals('1.0', $response->getProtocolVersion());
        $this->assertEquals('Bar', $response->getHeaderLine('Foo'));
        $this->assertEquals('Bam, Qux', $response->getHeaderLine('Baz'));
        $this->assertEquals('Test', (string) $response->getBody());
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testValidatesResponseMessages()
    {
        Psr7\parse_response("GET / HTTP/1.1\r\n\r\n");
    }

    public function testDetermineMimetype()
    {
        $this->assertNull(Psr7\mimetype_from_extension('not-a-real-extension'));
        $this->assertEquals(
            'application/json',
            Psr7\mimetype_from_extension('json')
        );
        $this->assertEquals(
            'image/jpeg',
            Psr7\mimetype_from_filename('/tmp/images/IMG034821.JPEG')
        );
    }

    public function testCreatesUriForValue()
    {
        $this->assertInstanceOf('GuzzleHttp\Psr7\Uri', Psr7\uri_for('/foo'));
        $this->assertInstanceOf(
            'GuzzleHttp\Psr7\Uri',
            Psr7\uri_for(new Psr7\Uri('/foo'))
        );
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testValidatesUri()
    {
        Psr7\uri_for([]);
    }

    public function testKeepsPositionOfResource()
    {
        $h = fopen(__FILE__, 'r');
        fseek($h, 10);
        $stream = Psr7\stream_for($h);
        $this->assertEquals(10, $stream->tell());
        $stream->close();
    }

    public function testCreatesWithFactory()
    {
        $stream = Psr7\stream_for('foo');
        $this->assertInstanceOf('GuzzleHttp\Psr7\Stream', $stream);
        $this->assertEquals('foo', $stream->getContents());
        $stream->close();
    }

    public function testFactoryCreatesFromEmptyString()
    {
        $s = Psr7\stream_for();
        $this->assertInstanceOf('GuzzleHttp\Psr7\Stream', $s);
    }

    public function testFactoryCreatesFromNull()
    {
        $s = Psr7\stream_for(null);
        $this->assertInstanceOf('GuzzleHttp\Psr7\Stream', $s);
    }

    public function testFactoryCreatesFromResource()
    {
        $r = fopen(__FILE__, 'r');
        $s = Psr7\stream_for($r);
        $this->assertInstanceOf('GuzzleHttp\Psr7\Stream', $s);
        $this->assertSame(file_get_contents(__FILE__), (string) $s);
    }

    public function testFactoryCreatesFromObjectWithToString()
    {
        $r = new HasToString();
        $s = Psr7\stream_for($r);
        $this->assertInstanceOf('GuzzleHttp\Psr7\Stream', $s);
        $this->assertEquals('foo', (string) $s);
    }

    public function testCreatePassesThrough()
    {
        $s = Psr7\stream_for('foo');
        $this->assertSame($s, Psr7\stream_for($s));
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testThrowsExceptionForUnknown()
    {
        Psr7\stream_for(new \stdClass());
    }

    public function testReturnsCustomMetadata()
    {
        $s = Psr7\stream_for('foo', ['metadata' => ['hwm' => 3]]);
        $this->assertEquals(3, $s->getMetadata('hwm'));
        $this->assertArrayHasKey('hwm', $s->getMetadata());
    }

    public function testCanSetSize()
    {
        $s = Psr7\stream_for('', ['size' => 10]);
        $this->assertEquals(10, $s->getSize());
    }

    public function testCanCreateIteratorBasedStream()
    {
        $a = new \ArrayIterator(['foo', 'bar', '123']);
        $p = Psr7\stream_for($a);
        $this->assertInstanceOf('GuzzleHttp\Psr7\PumpStream', $p);
        $this->assertEquals('foo', $p->read(3));
        $this->assertFalse($p->eof());
        $this->assertEquals('b', $p->read(1));
        $this->assertEquals('a', $p->read(1));
        $this->assertEquals('r12', $p->read(3));
        $this->assertFalse($p->eof());
        $this->assertEquals('3', $p->getContents());
        $this->assertTrue($p->eof());
        $this->assertEquals(9, $p->tell());
    }

    public function testConvertsRequestsToStrings()
    {
        $request = new Psr7\Request('PUT', 'http://foo.com/hi?123', [
            'Baz' => 'bar',
            'Qux' => ' ipsum'
        ], 'hello', '1.0');
        $this->assertEquals(
            "PUT /hi?123 HTTP/1.0\r\nHost: foo.com\r\nBaz: bar\r\nQux: ipsum\r\n\r\nhello",
            Psr7\str($request)
        );
    }

    public function testConvertsResponsesToStrings()
    {
        $response = new Psr7\Response(200, [
            'Baz' => 'bar',
            'Qux' => ' ipsum'
        ], 'hello', '1.0', 'FOO');
        $this->assertEquals(
            "HTTP/1.0 200 FOO\r\nBaz: bar\r\nQux: ipsum\r\n\r\nhello",
            Psr7\str($response)
        );
    }

    public function parseParamsProvider()
    {
        $res1 = array(
            array(
                '<http:/.../front.jpeg>',
                'rel' => 'front',
                'type' => 'image/jpeg',
            ),
            array(
                '<http://.../back.jpeg>',
                'rel' => 'back',
                'type' => 'image/jpeg',
            ),
        );
        return array(
            array(
                '<http:/.../front.jpeg>; rel="front"; type="image/jpeg", <http://.../back.jpeg>; rel=back; type="image/jpeg"',
                $res1
            ),
            array(
                '<http:/.../front.jpeg>; rel="front"; type="image/jpeg",<http://.../back.jpeg>; rel=back; type="image/jpeg"',
                $res1
            ),
            array(
                'foo="baz"; bar=123, boo, test="123", foobar="foo;bar"',
                array(
                    array('foo' => 'baz', 'bar' => '123'),
                    array('boo'),
                    array('test' => '123'),
                    array('foobar' => 'foo;bar')
                )
            ),
            array(
                '<http://.../side.jpeg?test=1>; rel="side"; type="image/jpeg",<http://.../side.jpeg?test=2>; rel=side; type="image/jpeg"',
                array(
                    array('<http://.../side.jpeg?test=1>', 'rel' => 'side', 'type' => 'image/jpeg'),
                    array('<http://.../side.jpeg?test=2>', 'rel' => 'side', 'type' => 'image/jpeg')
                )
            ),
            array(
                '',
                array()
            )
        );
    }
    /**
     * @dataProvider parseParamsProvider
     */
    public function testParseParams($header, $result)
    {
        $this->assertEquals($result, Psr7\parse_header($header));
    }

    public function testParsesArrayHeaders()
    {
        $header = ['a, b', 'c', 'd, e'];
        $this->assertEquals(['a', 'b', 'c', 'd', 'e'], Psr7\normalize_header($header));
    }

    public function testRewindsBody()
    {
        $body = Psr7\stream_for('abc');
        $res = new Psr7\Response(200, [], $body);
        Psr7\rewind_body($res);
        $this->assertEquals(0, $body->tell());
        $body->rewind(1);
        Psr7\rewind_body($res);
        $this->assertEquals(0, $body->tell());
    }

    /**
     * @expectedException \RuntimeException
     */
    public function testThrowsWhenBodyCannotBeRewound()
    {
        $body = Psr7\stream_for('abc');
        $body->read(1);
        $body = FnStream::decorate($body, [
            'rewind' => function () { throw new \RuntimeException('a'); }
        ]);
        $res = new Psr7\Response(200, [], $body);
        Psr7\rewind_body($res);
    }

    public function testCanModifyRequestWithUri()
    {
        $r1 = new Psr7\Request('GET', 'http://foo.com');
        $r2 = Psr7\modify_request($r1, [
            'uri' => new Psr7\Uri('http://www.foo.com')
        ]);
        $this->assertEquals('http://www.foo.com', (string) $r2->getUri());
        $this->assertEquals('www.foo.com', (string) $r2->getHeaderLine('host'));
    }

    public function testCanModifyRequestWithUriAndPort()
    {
        $r1 = new Psr7\Request('GET', 'http://foo.com:8000');
        $r2 = Psr7\modify_request($r1, [
            'uri' => new Psr7\Uri('http://www.foo.com:8000')
        ]);
        $this->assertEquals('http://www.foo.com:8000', (string) $r2->getUri());
        $this->assertEquals('www.foo.com:8000', (string) $r2->getHeaderLine('host'));
    }

    public function testCanModifyRequestWithCaseInsensitiveHeader()
    {
        $r1 = new Psr7\Request('GET', 'http://foo.com', ['User-Agent' => 'foo']);
        $r2 = Psr7\modify_request($r1, ['set_headers' => ['User-agent' => 'bar']]);
        $this->assertEquals('bar', $r2->getHeaderLine('User-Agent'));
        $this->assertEquals('bar', $r2->getHeaderLine('User-agent'));
    }

    public function testReturnsAsIsWhenNoChanges()
    {
        $request = new Psr7\Request('GET', 'http://foo.com');
        $this->assertSame($request, Psr7\modify_request($request, []));
    }

    public function testReturnsUriAsIsWhenNoChanges()
    {
        $r1 = new Psr7\Request('GET', 'http://foo.com');
        $r2 = Psr7\modify_request($r1, ['set_headers' => ['foo' => 'bar']]);
        $this->assertNotSame($r1, $r2);
        $this->assertEquals('bar', $r2->getHeaderLine('foo'));
    }

    public function testRemovesHeadersFromMessage()
    {
        $r1 = new Psr7\Request('GET', 'http://foo.com', ['foo' => 'bar']);
        $r2 = Psr7\modify_request($r1, ['remove_headers' => ['foo']]);
        $this->assertNotSame($r1, $r2);
        $this->assertFalse($r2->hasHeader('foo'));
    }

    public function testAddsQueryToUri()
    {
        $r1 = new Psr7\Request('GET', 'http://foo.com');
        $r2 = Psr7\modify_request($r1, ['query' => 'foo=bar']);
        $this->assertNotSame($r1, $r2);
        $this->assertEquals('foo=bar', $r2->getUri()->getQuery());
    }
}
<?php
namespace GuzzleHttp\Tests\Psr7;

use GuzzleHttp\Psr7;
use GuzzleHttp\Psr7\InflateStream;

class InflateStreamtest extends \PHPUnit_Framework_TestCase
{
    public function testInflatesStreams()
    {
        $content = gzencode('test');
        $a = Psr7\stream_for($content);
        $b = new InflateStream($a);
        $this->assertEquals('test', (string) $b);
    }

    public function testInflatesStreamsWithFilename()
    {
        $content = $this->getGzipStringWithFilename('test');
        $a = Psr7\stream_for($content);
        $b = new InflateStream($a);
        $this->assertEquals('test', (string) $b);
    }

    private function getGzipStringWithFilename($original_string)
    {
        $gzipped = bin2hex(gzencode($original_string));

        $header = substr($gzipped, 0, 20);
        // set FNAME flag
        $header[6]=0;
        $header[7]=8;
        // make a dummy filename
        $filename = "64756d6d7900";
        $rest = substr($gzipped, 20);

        return hex2bin($header . $filename . $rest);
    }
}
<?php
namespace GuzzleHttp\Tests\Psr7;

use GuzzleHttp\Psr7\LazyOpenStream;

class LazyOpenStreamTest extends \PHPUnit_Framework_TestCase
{
    private $fname;

    public function setup()
    {
        $this->fname = tempnam('/tmp', 'tfile');

        if (file_exists($this->fname)) {
            unlink($this->fname);
        }
    }

    public function tearDown()
    {
        if (file_exists($this->fname)) {
            unlink($this->fname);
        }
    }

    public function testOpensLazily()
    {
        $l = new LazyOpenStream($this->fname, 'w+');
        $l->write('foo');
        $this->assertInternalType('array', $l->getMetadata());
        $this->assertFileExists($this->fname);
        $this->assertEquals('foo', file_get_contents($this->fname));
        $this->assertEquals('foo', (string) $l);
    }

    public function testProxiesToFile()
    {
        file_put_contents($this->fname, 'foo');
        $l = new LazyOpenStream($this->fname, 'r');
        $this->assertEquals('foo', $l->read(4));
        $this->assertTrue($l->eof());
        $this->assertEquals(3, $l->tell());
        $this->assertTrue($l->isReadable());
        $this->assertTrue($l->isSeekable());
        $this->assertFalse($l->isWritable());
        $l->seek(1);
        $this->assertEquals('oo', $l->getContents());
        $this->assertEquals('foo', (string) $l);
        $this->assertEquals(3, $l->getSize());
        $this->assertInternalType('array', $l->getMetadata());
        $l->close();
    }

    public function testDetachesUnderlyingStream()
    {
        file_put_contents($this->fname, 'foo');
        $l = new LazyOpenStream($this->fname, 'r');
        $r = $l->detach();
        $this->assertInternalType('resource', $r);
        fseek($r, 0);
        $this->assertEquals('foo', stream_get_contents($r));
        fclose($r);
    }
}
<?php
namespace GuzzleHttp\Tests\Psr7;

use GuzzleHttp\Psr7;
use GuzzleHttp\Psr7\FnStream;
use GuzzleHttp\Psr7\Stream;
use GuzzleHttp\Psr7\LimitStream;
use GuzzleHttp\Psr7\NoSeekStream;

/**
 * @covers GuzzleHttp\Psr7\LimitStream
 */
class LimitStreamTest extends \PHPUnit_Framework_TestCase
{
    /** @var LimitStream */
    protected $body;

    /** @var Stream */
    protected $decorated;

    public function setUp()
    {
        $this->decorated = Psr7\stream_for(fopen(__FILE__, 'r'));
        $this->body = new LimitStream($this->decorated, 10, 3);
    }

    public function testReturnsSubset()
    {
        $body = new LimitStream(Psr7\stream_for('foo'), -1, 1);
        $this->assertEquals('oo', (string) $body);
        $this->assertTrue($body->eof());
        $body->seek(0);
        $this->assertFalse($body->eof());
        $this->assertEquals('oo', $body->read(100));
        $this->assertSame('', $body->read(1));
        $this->assertTrue($body->eof());
    }

    public function testReturnsSubsetWhenCastToString()
    {
        $body = Psr7\stream_for('foo_baz_bar');
        $limited = new LimitStream($body, 3, 4);
        $this->assertEquals('baz', (string) $limited);
    }

    /**
     * @expectedException \RuntimeException
     * @expectedExceptionMessage Unable to seek to stream position 10 with whence 0
     */
    public function testEnsuresPositionCanBeekSeekedTo()
    {
        new LimitStream(Psr7\stream_for(''), 0, 10);
    }

    public function testReturnsSubsetOfEmptyBodyWhenCastToString()
    {
        $body = Psr7\stream_for('01234567891234');
        $limited = new LimitStream($body, 0, 10);
        $this->assertEquals('', (string) $limited);
    }

    public function testReturnsSpecificSubsetOBodyWhenCastToString()
    {
        $body = Psr7\stream_for('0123456789abcdef');
        $limited = new LimitStream($body, 3, 10);
        $this->assertEquals('abc', (string) $limited);
    }

    public function testSeeksWhenConstructed()
    {
        $this->assertEquals(0, $this->body->tell());
        $this->assertEquals(3, $this->decorated->tell());
    }

    public function testAllowsBoundedSeek()
    {
        $this->body->seek(100);
        $this->assertEquals(10, $this->body->tell());
        $this->assertEquals(13, $this->decorated->tell());
        $this->body->seek(0);
        $this->assertEquals(0, $this->body->tell());
        $this->assertEquals(3, $this->decorated->tell());
        try {
            $this->body->seek(-10);
            $this->fail();
        } catch (\RuntimeException $e) {}
        $this->assertEquals(0, $this->body->tell());
        $this->assertEquals(3, $this->decorated->tell());
        $this->body->seek(5);
        $this->assertEquals(5, $this->body->tell());
        $this->assertEquals(8, $this->decorated->tell());
        // Fail
        try {
            $this->body->seek(1000, SEEK_END);
            $this->fail();
        } catch (\RuntimeException $e) {}
    }

    public function testReadsOnlySubsetOfData()
    {
        $data = $this->body->read(100);
        $this->assertEquals(10, strlen($data));
        $this->assertSame('', $this->body->read(1000));

        $this->body->setOffset(10);
        $newData = $this->body->read(100);
        $this->assertEquals(10, strlen($newData));
        $this->assertNotSame($data, $newData);
    }

    /**
     * @expectedException \RuntimeException
     * @expectedExceptionMessage Could not seek to stream offset 2
     */
    public function testThrowsWhenCurrentGreaterThanOffsetSeek()
    {
        $a = Psr7\stream_for('foo_bar');
        $b = new NoSeekStream($a);
        $c = new LimitStream($b);
        $a->getContents();
        $c->setOffset(2);
    }

    public function testCanGetContentsWithoutSeeking()
    {
        $a = Psr7\stream_for('foo_bar');
        $b = new NoSeekStream($a);
        $c = new LimitStream($b);
        $this->assertEquals('foo_bar', $c->getContents());
    }

    public function testClaimsConsumedWhenReadLimitIsReached()
    {
        $this->assertFalse($this->body->eof());
        $this->body->read(1000);
        $this->assertTrue($this->body->eof());
    }

    public function testContentLengthIsBounded()
    {
        $this->assertEquals(10, $this->body->getSize());
    }

    public function testGetContentsIsBasedOnSubset()
    {
        $body = new LimitStream(Psr7\stream_for('foobazbar'), 3, 3);
        $this->assertEquals('baz', $body->getContents());
    }

    public function testReturnsNullIfSizeCannotBeDetermined()
    {
        $a = new FnStream([
            'getSize' => function () { return null; },
            'tell'    => function () { return 0; },
        ]);
        $b = new LimitStream($a);
        $this->assertNull($b->getSize());
    }

    public function testLengthLessOffsetWhenNoLimitSize()
    {
        $a = Psr7\stream_for('foo_bar');
        $b = new LimitStream($a, -1, 4);
        $this->assertEquals(3, $b->getSize());
    }
}
<?php
namespace GuzzleHttp\Tests;

use GuzzleHttp\Psr7;
use GuzzleHttp\Psr7\MultipartStream;

class MultipartStreamTest extends \PHPUnit_Framework_TestCase
{
    public function testCreatesDefaultBoundary()
    {
        $b = new MultipartStream();
        $this->assertNotEmpty($b->getBoundary());
    }

    public function testCanProvideBoundary()
    {
        $b = new MultipartStream([], 'foo');
        $this->assertEquals('foo', $b->getBoundary());
    }

    public function testIsNotWritable()
    {
        $b = new MultipartStream();
        $this->assertFalse($b->isWritable());
    }

    public function testCanCreateEmptyStream()
    {
        $b = new MultipartStream();
        $boundary = $b->getBoundary();
        $this->assertSame("--{$boundary}--\r\n", $b->getContents());
        $this->assertSame(strlen($boundary) + 6, $b->getSize());
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testValidatesFilesArrayElement()
    {
        new MultipartStream([['foo' => 'bar']]);
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testEnsuresFileHasName()
    {
        new MultipartStream([['contents' => 'bar']]);
    }

    public function testSerializesFields()
    {
        $b = new MultipartStream([
            [
                'name'     => 'foo',
                'contents' => 'bar'
            ],
            [
                'name' => 'baz',
                'contents' => 'bam'
            ]
        ], 'boundary');
        $this->assertEquals(
            "--boundary\r\nContent-Disposition: form-data; name=\"foo\"\r\nContent-Length: 3\r\n\r\n"
            . "bar\r\n--boundary\r\nContent-Disposition: form-data; name=\"baz\"\r\nContent-Length: 3"
            . "\r\n\r\nbam\r\n--boundary--\r\n", (string) $b);
    }

    public function testSerializesFiles()
    {
        $f1 = Psr7\FnStream::decorate(Psr7\stream_for('foo'), [
            'getMetadata' => function () {
                return '/foo/bar.txt';
            }
        ]);

        $f2 = Psr7\FnStream::decorate(Psr7\stream_for('baz'), [
            'getMetadata' => function () {
                return '/foo/baz.jpg';
            }
        ]);

        $f3 = Psr7\FnStream::decorate(Psr7\stream_for('bar'), [
            'getMetadata' => function () {
                return '/foo/bar.gif';
            }
        ]);

        $b = new MultipartStream([
            [
                'name'     => 'foo',
                'contents' => $f1
            ],
            [
                'name' => 'qux',
                'contents' => $f2
            ],
            [
                'name'     => 'qux',
                'contents' => $f3
            ],
        ], 'boundary');

        $expected = <<<EOT
--boundary
Content-Disposition: form-data; name="foo"; filename="bar.txt"
Content-Length: 3
Content-Type: text/plain

foo
--boundary
Content-Disposition: form-data; name="qux"; filename="baz.jpg"
Content-Length: 3
Content-Type: image/jpeg

baz
--boundary
Content-Disposition: form-data; name="qux"; filename="bar.gif"
Content-Length: 3
Content-Type: image/gif

bar
--boundary--

EOT;

        $this->assertEquals($expected, str_replace("\r", '', $b));
    }

    public function testSerializesFilesWithCustomHeaders()
    {
        $f1 = Psr7\FnStream::decorate(Psr7\stream_for('foo'), [
            'getMetadata' => function () {
                return '/foo/bar.txt';
            }
        ]);

        $b = new MultipartStream([
            [
                'name' => 'foo',
                'contents' => $f1,
                'headers'  => [
                    'x-foo' => 'bar',
                    'content-disposition' => 'custom'
                ]
            ]
        ], 'boundary');

        $expected = <<<EOT
--boundary
x-foo: bar
content-disposition: custom
Content-Length: 3
Content-Type: text/plain

foo
--boundary--

EOT;

        $this->assertEquals($expected, str_replace("\r", '', $b));
    }

    public function testSerializesFilesWithCustomHeadersAndMultipleValues()
    {
        $f1 = Psr7\FnStream::decorate(Psr7\stream_for('foo'), [
            'getMetadata' => function () {
                return '/foo/bar.txt';
            }
        ]);

        $f2 = Psr7\FnStream::decorate(Psr7\stream_for('baz'), [
            'getMetadata' => function () {
                return '/foo/baz.jpg';
            }
        ]);

        $b = new MultipartStream([
            [
                'name'     => 'foo',
                'contents' => $f1,
                'headers'  => [
                    'x-foo' => 'bar',
                    'content-disposition' => 'custom'
                ]
            ],
            [
                'name'     => 'foo',
                'contents' => $f2,
                'headers'  => ['cOntenT-Type' => 'custom'],
            ]
        ], 'boundary');

        $expected = <<<EOT
--boundary
x-foo: bar
content-disposition: custom
Content-Length: 3
Content-Type: text/plain

foo
--boundary
cOntenT-Type: custom
Content-Disposition: form-data; name="foo"; filename="baz.jpg"
Content-Length: 3

baz
--boundary--

EOT;

        $this->assertEquals($expected, str_replace("\r", '', $b));
    }
}
<?php
namespace GuzzleHttp\Tests\Psr7;

use GuzzleHttp\Psr7;
use GuzzleHttp\Psr7\NoSeekStream;

/**
 * @covers GuzzleHttp\Psr7\NoSeekStream
 * @covers GuzzleHttp\Psr7\StreamDecoratorTrait
 */
class NoSeekStreamTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @expectedException \RuntimeException
     * @expectedExceptionMessage Cannot seek a NoSeekStream
     */
    public function testCannotSeek()
    {
        $s = $this->getMockBuilder('Psr\Http\Message\StreamInterface')
            ->setMethods(['isSeekable', 'seek'])
            ->getMockForAbstractClass();
        $s->expects($this->never())->method('seek');
        $s->expects($this->never())->method('isSeekable');
        $wrapped = new NoSeekStream($s);
        $this->assertFalse($wrapped->isSeekable());
        $wrapped->seek(2);
    }

    /**
     * @expectedException \RuntimeException
     * @expectedExceptionMessage Cannot write to a non-writable stream
     */
    public function testHandlesClose()
    {
        $s = Psr7\stream_for('foo');
        $wrapped = new NoSeekStream($s);
        $wrapped->close();
        $wrapped->write('foo');
    }
}
<?php
namespace GuzzleHttp\Tests\Psr7;

use GuzzleHttp\Psr7\LimitStream;
use GuzzleHttp\Psr7\PumpStream;
use GuzzleHttp\Psr7;

class PumpStreamTest extends \PHPUnit_Framework_TestCase
{
    public function testHasMetadataAndSize()
    {
        $p = new PumpStream(function () {}, [
            'metadata' => ['foo' => 'bar'],
            'size'     => 100
        ]);

        $this->assertEquals('bar', $p->getMetadata('foo'));
        $this->assertEquals(['foo' => 'bar'], $p->getMetadata());
        $this->assertEquals(100, $p->getSize());
    }

    public function testCanReadFromCallable()
    {
        $p = Psr7\stream_for(function ($size) {
            return 'a';
        });
        $this->assertEquals('a', $p->read(1));
        $this->assertEquals(1, $p->tell());
        $this->assertEquals('aaaaa', $p->read(5));
        $this->assertEquals(6, $p->tell());
    }

    public function testStoresExcessDataInBuffer()
    {
        $called = [];
        $p = Psr7\stream_for(function ($size) use (&$called) {
            $called[] = $size;
            return 'abcdef';
        });
        $this->assertEquals('a', $p->read(1));
        $this->assertEquals('b', $p->read(1));
        $this->assertEquals('cdef', $p->read(4));
        $this->assertEquals('abcdefabc', $p->read(9));
        $this->assertEquals([1, 9, 3], $called);
    }

    public function testInifiniteStreamWrappedInLimitStream()
    {
        $p = Psr7\stream_for(function () { return 'a'; });
        $s = new LimitStream($p, 5);
        $this->assertEquals('aaaaa', (string) $s);
    }

    public function testDescribesCapabilities()
    {
        $p = Psr7\stream_for(function () {});
        $this->assertTrue($p->isReadable());
        $this->assertFalse($p->isSeekable());
        $this->assertFalse($p->isWritable());
        $this->assertNull($p->getSize());
        $this->assertEquals('', $p->getContents());
        $this->assertEquals('', (string) $p);
        $p->close();
        $this->assertEquals('', $p->read(10));
        $this->assertTrue($p->eof());

        try {
            $this->assertFalse($p->write('aa'));
            $this->fail();
        } catch (\RuntimeException $e) {}
    }
}
<?php
namespace GuzzleHttp\Tests\Psr7;

use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Uri;

/**
 * @covers GuzzleHttp\Psr7\Request
 */
class RequestTest extends \PHPUnit_Framework_TestCase
{
    public function testRequestUriMayBeString()
    {
        $r = new Request('GET', '/');
        $this->assertEquals('/', (string) $r->getUri());
    }

    public function testRequestUriMayBeUri()
    {
        $uri = new Uri('/');
        $r = new Request('GET', $uri);
        $this->assertSame($uri, $r->getUri());
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testValidateRequestUri()
    {
        new Request('GET', true);
    }

    public function testCanConstructWithBody()
    {
        $r = new Request('GET', '/', [], 'baz');
        $this->assertEquals('baz', (string) $r->getBody());
    }

    public function testCapitalizesMethod()
    {
        $r = new Request('get', '/');
        $this->assertEquals('GET', $r->getMethod());
    }

    public function testCapitalizesWithMethod()
    {
        $r = new Request('GET', '/');
        $this->assertEquals('PUT', $r->withMethod('put')->getMethod());
    }

    public function testWithUri()
    {
        $r1 = new Request('GET', '/');
        $u1 = $r1->getUri();
        $u2 = new Uri('http://www.example.com');
        $r2 = $r1->withUri($u2);
        $this->assertNotSame($r1, $r2);
        $this->assertSame($u2, $r2->getUri());
        $this->assertSame($u1, $r1->getUri());
    }

    public function testSameInstanceWhenSameUri()
    {
        $r1 = new Request('GET', 'http://foo.com');
        $r2 = $r1->withUri($r1->getUri());
        $this->assertSame($r1, $r2);
    }

    public function testWithRequestTarget()
    {
        $r1 = new Request('GET', '/');
        $r2 = $r1->withRequestTarget('*');
        $this->assertEquals('*', $r2->getRequestTarget());
        $this->assertEquals('/', $r1->getRequestTarget());
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testRequestTargetDoesNotAllowSpaces()
    {
        $r1 = new Request('GET', '/');
        $r1->withRequestTarget('/foo bar');
    }

    public function testRequestTargetDefaultsToSlash()
    {
        $r1 = new Request('GET', '');
        $this->assertEquals('/', $r1->getRequestTarget());
        $r2 = new Request('GET', '*');
        $this->assertEquals('*', $r2->getRequestTarget());
        $r3 = new Request('GET', 'http://foo.com/bar baz/');
        $this->assertEquals('/bar%20baz/', $r3->getRequestTarget());
    }

    public function testBuildsRequestTarget()
    {
        $r1 = new Request('GET', 'http://foo.com/baz?bar=bam');
        $this->assertEquals('/baz?bar=bam', $r1->getRequestTarget());
    }

    public function testHostIsAddedFirst()
    {
        $r = new Request('GET', 'http://foo.com/baz?bar=bam', ['Foo' => 'Bar']);
        $this->assertEquals([
            'Host' => ['foo.com'],
            'Foo'  => ['Bar']
        ], $r->getHeaders());
    }

    public function testCanGetHeaderAsCsv()
    {
        $r = new Request('GET', 'http://foo.com/baz?bar=bam', [
            'Foo' => ['a', 'b', 'c']
        ]);
        $this->assertEquals('a, b, c', $r->getHeaderLine('Foo'));
        $this->assertEquals('', $r->getHeaderLine('Bar'));
    }

    public function testHostIsNotOverwrittenWhenPreservingHost()
    {
        $r = new Request('GET', 'http://foo.com/baz?bar=bam', ['Host' => 'a.com']);
        $this->assertEquals(['Host' => ['a.com']], $r->getHeaders());
        $r2 = $r->withUri(new Uri('http://www.foo.com/bar'), true);
        $this->assertEquals('a.com', $r2->getHeaderLine('Host'));
    }

    public function testOverridesHostWithUri()
    {
        $r = new Request('GET', 'http://foo.com/baz?bar=bam');
        $this->assertEquals(['Host' => ['foo.com']], $r->getHeaders());
        $r2 = $r->withUri(new Uri('http://www.baz.com/bar'));
        $this->assertEquals('www.baz.com', $r2->getHeaderLine('Host'));
    }

    public function testAggregatesHeaders()
    {
        $r = new Request('GET', 'http://foo.com', [
            'ZOO' => 'zoobar',
            'zoo' => ['foobar', 'zoobar']
        ]);
        $this->assertEquals('zoobar, foobar, zoobar', $r->getHeaderLine('zoo'));
    }

    public function testAddsPortToHeader()
    {
        $r = new Request('GET', 'http://foo.com:8124/bar');
        $this->assertEquals('foo.com:8124', $r->getHeaderLine('host'));
    }

    public function testAddsPortToHeaderAndReplacePreviousPort()
    {
        $r = new Request('GET', 'http://foo.com:8124/bar');
        $r = $r->withUri(new Uri('http://foo.com:8125/bar'));
        $this->assertEquals('foo.com:8125', $r->getHeaderLine('host'));
    }
}
<?php
namespace GuzzleHttp\Tests\Psr7;

use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Psr7;

/**
 * @covers GuzzleHttp\Psr7\MessageTrait
 * @covers GuzzleHttp\Psr7\Response
 */
class ResponseTest extends \PHPUnit_Framework_TestCase
{
    public function testAddsDefaultReason()
    {
        $r = new Response('200');
        $this->assertSame(200, $r->getStatusCode());
        $this->assertEquals('OK', $r->getReasonPhrase());
    }

    public function testCanGiveCustomReason()
    {
        $r = new Response(200, [], null, '1.1', 'bar');
        $this->assertEquals('bar', $r->getReasonPhrase());
    }

    public function testCanGiveCustomProtocolVersion()
    {
        $r = new Response(200, [], null, '1000');
        $this->assertEquals('1000', $r->getProtocolVersion());
    }

    public function testCanCreateNewResponseWithStatusAndNoReason()
    {
        $r = new Response(200);
        $r2 = $r->withStatus(201);
        $this->assertEquals(200, $r->getStatusCode());
        $this->assertEquals('OK', $r->getReasonPhrase());
        $this->assertEquals(201, $r2->getStatusCode());
        $this->assertEquals('Created', $r2->getReasonPhrase());
    }

    public function testCanCreateNewResponseWithStatusAndReason()
    {
        $r = new Response(200);
        $r2 = $r->withStatus(201, 'Foo');
        $this->assertEquals(200, $r->getStatusCode());
        $this->assertEquals('OK', $r->getReasonPhrase());
        $this->assertEquals(201, $r2->getStatusCode());
        $this->assertEquals('Foo', $r2->getReasonPhrase());
    }

    public function testCreatesResponseWithAddedHeaderArray()
    {
        $r = new Response();
        $r2 = $r->withAddedHeader('foo', ['baz', 'bar']);
        $this->assertFalse($r->hasHeader('foo'));
        $this->assertEquals('baz, bar', $r2->getHeaderLine('foo'));
    }

    public function testReturnsIdentityWhenRemovingMissingHeader()
    {
        $r = new Response();
        $this->assertSame($r, $r->withoutHeader('foo'));
    }

    public function testAlwaysReturnsBody()
    {
        $r = new Response();
        $this->assertInstanceOf('Psr\Http\Message\StreamInterface', $r->getBody());
    }

    public function testCanSetHeaderAsArray()
    {
        $r = new Response(200, [
            'foo' => ['baz ', ' bar ']
        ]);
        $this->assertEquals('baz, bar', $r->getHeaderLine('foo'));
        $this->assertEquals(['baz', 'bar'], $r->getHeader('foo'));
    }

    public function testSameInstanceWhenSameBody()
    {
        $r = new Response(200, [], 'foo');
        $b = $r->getBody();
        $this->assertSame($r, $r->withBody($b));
    }

    public function testNewInstanceWhenNewBody()
    {
        $r = new Response(200, [], 'foo');
        $b2 = Psr7\stream_for('abc');
        $this->assertNotSame($r, $r->withBody($b2));
    }

    public function testSameInstanceWhenSameProtocol()
    {
        $r = new Response(200);
        $this->assertSame($r, $r->withProtocolVersion('1.1'));
    }

    public function testNewInstanceWhenNewProtocol()
    {
        $r = new Response(200);
        $this->assertNotSame($r, $r->withProtocolVersion('1.0'));
    }

    public function testNewInstanceWhenRemovingHeader()
    {
        $r = new Response(200, ['Foo' => 'Bar']);
        $r2 = $r->withoutHeader('Foo');
        $this->assertNotSame($r, $r2);
        $this->assertFalse($r2->hasHeader('foo'));
    }

    public function testNewInstanceWhenAddingHeader()
    {
        $r = new Response(200, ['Foo' => 'Bar']);
        $r2 = $r->withAddedHeader('Foo', 'Baz');
        $this->assertNotSame($r, $r2);
        $this->assertEquals('Bar, Baz', $r2->getHeaderLine('foo'));
    }

    public function testNewInstanceWhenAddingHeaderThatWasNotThereBefore()
    {
        $r = new Response(200, ['Foo' => 'Bar']);
        $r2 = $r->withAddedHeader('Baz', 'Bam');
        $this->assertNotSame($r, $r2);
        $this->assertEquals('Bam', $r2->getHeaderLine('Baz'));
        $this->assertEquals('Bar', $r2->getHeaderLine('Foo'));
    }

    public function testRemovesPreviouslyAddedHeaderOfDifferentCase()
    {
        $r = new Response(200, ['Foo' => 'Bar']);
        $r2 = $r->withHeader('foo', 'Bam');
        $this->assertNotSame($r, $r2);
        $this->assertEquals('Bam', $r2->getHeaderLine('Foo'));
    }

    public function testBodyConsistent()
    {
        $r = new Response(200, [], '0');
        $this->assertEquals('0', (string)$r->getBody());
    }
    
}
<?php
namespace GuzzleHttp\Tests\Psr7;

use Psr\Http\Message\StreamInterface;
use GuzzleHttp\Psr7;
use GuzzleHttp\Psr7\StreamDecoratorTrait;

class Str implements StreamInterface
{
    use StreamDecoratorTrait;
}

/**
 * @covers GuzzleHttp\Psr7\StreamDecoratorTrait
 */
class StreamDecoratorTraitTest extends \PHPUnit_Framework_TestCase
{
    private $a;
    private $b;
    private $c;

    public function setUp()
    {
        $this->c = fopen('php://temp', 'r+');
        fwrite($this->c, 'foo');
        fseek($this->c, 0);
        $this->a = Psr7\stream_for($this->c);
        $this->b = new Str($this->a);
    }

    public function testCatchesExceptionsWhenCastingToString()
    {
        $s = $this->getMockBuilder('Psr\Http\Message\StreamInterface')
            ->setMethods(['read'])
            ->getMockForAbstractClass();
        $s->expects($this->once())
            ->method('read')
            ->will($this->throwException(new \Exception('foo')));
        $msg = '';
        set_error_handler(function ($errNo, $str) use (&$msg) { $msg = $str; });
        echo new Str($s);
        restore_error_handler();
        $this->assertContains('foo', $msg);
    }

    public function testToString()
    {
        $this->assertEquals('foo', (string) $this->b);
    }

    public function testHasSize()
    {
        $this->assertEquals(3, $this->b->getSize());
    }

    public function testReads()
    {
        $this->assertEquals('foo', $this->b->read(10));
    }

    public function testCheckMethods()
    {
        $this->assertEquals($this->a->isReadable(), $this->b->isReadable());
        $this->assertEquals($this->a->isWritable(), $this->b->isWritable());
        $this->assertEquals($this->a->isSeekable(), $this->b->isSeekable());
    }

    public function testSeeksAndTells()
    {
        $this->b->seek(1);
        $this->assertEquals(1, $this->a->tell());
        $this->assertEquals(1, $this->b->tell());
        $this->b->seek(0);
        $this->assertEquals(0, $this->a->tell());
        $this->assertEquals(0, $this->b->tell());
        $this->b->seek(0, SEEK_END);
        $this->assertEquals(3, $this->a->tell());
        $this->assertEquals(3, $this->b->tell());
    }

    public function testGetsContents()
    {
        $this->assertEquals('foo', $this->b->getContents());
        $this->assertEquals('', $this->b->getContents());
        $this->b->seek(1);
        $this->assertEquals('oo', $this->b->getContents(1));
    }

    public function testCloses()
    {
        $this->b->close();
        $this->assertFalse(is_resource($this->c));
    }

    public function testDetaches()
    {
        $this->b->detach();
        $this->assertFalse($this->b->isReadable());
    }

    public function testWrapsMetadata()
    {
        $this->assertSame($this->b->getMetadata(), $this->a->getMetadata());
        $this->assertSame($this->b->getMetadata('uri'), $this->a->getMetadata('uri'));
    }

    public function testWrapsWrites()
    {
        $this->b->seek(0, SEEK_END);
        $this->b->write('foo');
        $this->assertEquals('foofoo', (string) $this->a);
    }

    /**
     * @expectedException \UnexpectedValueException
     */
    public function testThrowsWithInvalidGetter()
    {
        $this->b->foo;
    }

    /**
     * @expectedException \BadMethodCallException
     */
    public function testThrowsWhenGetterNotImplemented()
    {
        $s = new BadStream();
        $s->stream;
    }
}

class BadStream
{
    use StreamDecoratorTrait;

    public function __construct() {}
}
<?php
namespace GuzzleHttp\Tests\Psr7;

use GuzzleHttp\Psr7\NoSeekStream;
use GuzzleHttp\Psr7\Stream;

/**
 * @covers GuzzleHttp\Psr7\Stream
 */
class StreamTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @expectedException \InvalidArgumentException
     */
    public function testConstructorThrowsExceptionOnInvalidArgument()
    {
        new Stream(true);
    }

    public function testConstructorInitializesProperties()
    {
        $handle = fopen('php://temp', 'r+');
        fwrite($handle, 'data');
        $stream = new Stream($handle);
        $this->assertTrue($stream->isReadable());
        $this->assertTrue($stream->isWritable());
        $this->assertTrue($stream->isSeekable());
        $this->assertEquals('php://temp', $stream->getMetadata('uri'));
        $this->assertInternalType('array', $stream->getMetadata());
        $this->assertEquals(4, $stream->getSize());
        $this->assertFalse($stream->eof());
        $stream->close();
    }

    public function testStreamClosesHandleOnDestruct()
    {
        $handle = fopen('php://temp', 'r');
        $stream = new Stream($handle);
        unset($stream);
        $this->assertFalse(is_resource($handle));
    }

    public function testConvertsToString()
    {
        $handle = fopen('php://temp', 'w+');
        fwrite($handle, 'data');
        $stream = new Stream($handle);
        $this->assertEquals('data', (string) $stream);
        $this->assertEquals('data', (string) $stream);
        $stream->close();
    }

    public function testGetsContents()
    {
        $handle = fopen('php://temp', 'w+');
        fwrite($handle, 'data');
        $stream = new Stream($handle);
        $this->assertEquals('', $stream->getContents());
        $stream->seek(0);
        $this->assertEquals('data', $stream->getContents());
        $this->assertEquals('', $stream->getContents());
    }

    public function testChecksEof()
    {
        $handle = fopen('php://temp', 'w+');
        fwrite($handle, 'data');
        $stream = new Stream($handle);
        $this->assertFalse($stream->eof());
        $stream->read(4);
        $this->assertTrue($stream->eof());
        $stream->close();
    }

    public function testGetSize()
    {
        $size = filesize(__FILE__);
        $handle = fopen(__FILE__, 'r');
        $stream = new Stream($handle);
        $this->assertEquals($size, $stream->getSize());
        // Load from cache
        $this->assertEquals($size, $stream->getSize());
        $stream->close();
    }

    public function testEnsuresSizeIsConsistent()
    {
        $h = fopen('php://temp', 'w+');
        $this->assertEquals(3, fwrite($h, 'foo'));
        $stream = new Stream($h);
        $this->assertEquals(3, $stream->getSize());
        $this->assertEquals(4, $stream->write('test'));
        $this->assertEquals(7, $stream->getSize());
        $this->assertEquals(7, $stream->getSize());
        $stream->close();
    }

    public function testProvidesStreamPosition()
    {
        $handle = fopen('php://temp', 'w+');
        $stream = new Stream($handle);
        $this->assertEquals(0, $stream->tell());
        $stream->write('foo');
        $this->assertEquals(3, $stream->tell());
        $stream->seek(1);
        $this->assertEquals(1, $stream->tell());
        $this->assertSame(ftell($handle), $stream->tell());
        $stream->close();
    }

    public function testCanDetachStream()
    {
        $r = fopen('php://temp', 'w+');
        $stream = new Stream($r);
        $stream->write('foo');
        $this->assertTrue($stream->isReadable());
        $this->assertSame($r, $stream->detach());
        $stream->detach();

        $this->assertFalse($stream->isReadable());
        $this->assertFalse($stream->isWritable());
        $this->assertFalse($stream->isSeekable());

        $throws = function (callable $fn) use ($stream) {
            try {
                $fn($stream);
                $this->fail();
            } catch (\Exception $e) {}
        };

        $throws(function ($stream) { $stream->read(10); });
        $throws(function ($stream) { $stream->write('bar'); });
        $throws(function ($stream) { $stream->seek(10); });
        $throws(function ($stream) { $stream->tell(); });
        $throws(function ($stream) { $stream->eof(); });
        $throws(function ($stream) { $stream->getSize(); });
        $throws(function ($stream) { $stream->getContents(); });
        $this->assertSame('', (string) $stream);
        $stream->close();
    }

    public function testCloseClearProperties()
    {
        $handle = fopen('php://temp', 'r+');
        $stream = new Stream($handle);
        $stream->close();

        $this->assertFalse($stream->isSeekable());
        $this->assertFalse($stream->isReadable());
        $this->assertFalse($stream->isWritable());
        $this->assertNull($stream->getSize());
        $this->assertEmpty($stream->getMetadata());
    }

    public function testDoesNotThrowInToString()
    {
        $s = \GuzzleHttp\Psr7\stream_for('foo');
        $s = new NoSeekStream($s);
        $this->assertEquals('foo', (string) $s);
    }
}
<?php
namespace GuzzleHttp\Tests\Psr7;

use GuzzleHttp\Psr7\StreamWrapper;
use GuzzleHttp\Psr7;

/**
 * @covers GuzzleHttp\Psr7\StreamWrapper
 */
class StreamWrapperTest extends \PHPUnit_Framework_TestCase
{
    public function testResource()
    {
        $stream = Psr7\stream_for('foo');
        $handle = StreamWrapper::getResource($stream);
        $this->assertSame('foo', fread($handle, 3));
        $this->assertSame(3, ftell($handle));
        $this->assertSame(3, fwrite($handle, 'bar'));
        $this->assertSame(0, fseek($handle, 0));
        $this->assertSame('foobar', fread($handle, 6));
        $this->assertSame('', fread($handle, 1));
        $this->assertTrue(feof($handle));

        // This fails on HHVM for some reason
        if (!defined('HHVM_VERSION')) {
            $this->assertEquals([
                'dev'     => 0,
                'ino'     => 0,
                'mode'    => 33206,
                'nlink'   => 0,
                'uid'     => 0,
                'gid'     => 0,
                'rdev'    => 0,
                'size'    => 6,
                'atime'   => 0,
                'mtime'   => 0,
                'ctime'   => 0,
                'blksize' => 0,
                'blocks'  => 0,
                0         => 0,
                1         => 0,
                2         => 33206,
                3         => 0,
                4         => 0,
                5         => 0,
                6         => 0,
                7         => 6,
                8         => 0,
                9         => 0,
                10        => 0,
                11        => 0,
                12        => 0,
            ], fstat($handle));
        }

        $this->assertTrue(fclose($handle));
        $this->assertSame('foobar', (string) $stream);
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testValidatesStream()
    {
        $stream = $this->getMockBuilder('Psr\Http\Message\StreamInterface')
            ->setMethods(['isReadable', 'isWritable'])
            ->getMockForAbstractClass();
        $stream->expects($this->once())
            ->method('isReadable')
            ->will($this->returnValue(false));
        $stream->expects($this->once())
            ->method('isWritable')
            ->will($this->returnValue(false));
        StreamWrapper::getResource($stream);
    }

    /**
     * @expectedException \PHPUnit_Framework_Error_Warning
     */
    public function testReturnsFalseWhenStreamDoesNotExist()
    {
        fopen('guzzle://foo', 'r');
    }

    public function testCanOpenReadonlyStream()
    {
        $stream = $this->getMockBuilder('Psr\Http\Message\StreamInterface')
            ->setMethods(['isReadable', 'isWritable'])
            ->getMockForAbstractClass();
        $stream->expects($this->once())
            ->method('isReadable')
            ->will($this->returnValue(false));
        $stream->expects($this->once())
            ->method('isWritable')
            ->will($this->returnValue(true));
        $r = StreamWrapper::getResource($stream);
        $this->assertInternalType('resource', $r);
        fclose($r);
    }
}
<?php
namespace GuzzleHttp\Tests\Psr7;

use GuzzleHttp\Psr7\Uri;

/**
 * @covers GuzzleHttp\Psr7\Uri
 */
class UriTest extends \PHPUnit_Framework_TestCase
{
    const RFC3986_BASE = "http://a/b/c/d;p?q";

    public function testParsesProvidedUrl()
    {
        $uri = new Uri('https://michael:test@test.com:443/path/123?q=abc#test');

        // Standard port 443 for https gets ignored.
        $this->assertEquals(
            'https://michael:test@test.com/path/123?q=abc#test',
            (string) $uri
        );

        $this->assertEquals('test', $uri->getFragment());
        $this->assertEquals('test.com', $uri->getHost());
        $this->assertEquals('/path/123', $uri->getPath());
        $this->assertEquals(null, $uri->getPort());
        $this->assertEquals('q=abc', $uri->getQuery());
        $this->assertEquals('https', $uri->getScheme());
        $this->assertEquals('michael:test', $uri->getUserInfo());
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Unable to parse URI
     */
    public function testValidatesUriCanBeParsed()
    {
        new Uri('///');
    }

    public function testCanTransformAndRetrievePartsIndividually()
    {
        $uri = (new Uri(''))
            ->withFragment('#test')
            ->withHost('example.com')
            ->withPath('path/123')
            ->withPort(8080)
            ->withQuery('?q=abc')
            ->withScheme('http')
            ->withUserInfo('user', 'pass');

        // Test getters.
        $this->assertEquals('user:pass@example.com:8080', $uri->getAuthority());
        $this->assertEquals('test', $uri->getFragment());
        $this->assertEquals('example.com', $uri->getHost());
        $this->assertEquals('path/123', $uri->getPath());
        $this->assertEquals(8080, $uri->getPort());
        $this->assertEquals('q=abc', $uri->getQuery());
        $this->assertEquals('http', $uri->getScheme());
        $this->assertEquals('user:pass', $uri->getUserInfo());
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testPortMustBeValid()
    {
        (new Uri(''))->withPort(100000);
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testPathMustBeValid()
    {
        (new Uri(''))->withPath([]);
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testQueryMustBeValid()
    {
        (new Uri(''))->withQuery(new \stdClass);
    }

    public function testAllowsFalseyUrlParts()
    {
        $url = new Uri('http://a:1/0?0#0');
        $this->assertSame('a', $url->getHost());
        $this->assertEquals(1, $url->getPort());
        $this->assertSame('/0', $url->getPath());
        $this->assertEquals('0', (string) $url->getQuery());
        $this->assertSame('0', $url->getFragment());
        $this->assertEquals('http://a:1/0?0#0', (string) $url);
        $url = new Uri('');
        $this->assertSame('', (string) $url);
        $url = new Uri('0');
        $this->assertSame('0', (string) $url);
        $url = new Uri('/');
        $this->assertSame('/', (string) $url);
    }

    /**
     * @dataProvider getResolveTestCases
     */
    public function testResolvesUris($base, $rel, $expected)
    {
        $uri = new Uri($base);
        $actual = Uri::resolve($uri, $rel);
        $this->assertEquals($expected, (string) $actual);
    }

    public function getResolveTestCases()
    {
        return [
            //[self::RFC3986_BASE, 'g:h',           'g:h'],
            [self::RFC3986_BASE, 'g',             'http://a/b/c/g'],
            [self::RFC3986_BASE, './g',           'http://a/b/c/g'],
            [self::RFC3986_BASE, 'g/',            'http://a/b/c/g/'],
            [self::RFC3986_BASE, '/g',            'http://a/g'],
            [self::RFC3986_BASE, '//g',           'http://g'],
            [self::RFC3986_BASE, '?y',            'http://a/b/c/d;p?y'],
            [self::RFC3986_BASE, 'g?y',           'http://a/b/c/g?y'],
            [self::RFC3986_BASE, '#s',            'http://a/b/c/d;p?q#s'],
            [self::RFC3986_BASE, 'g#s',           'http://a/b/c/g#s'],
            [self::RFC3986_BASE, 'g?y#s',         'http://a/b/c/g?y#s'],
            [self::RFC3986_BASE, ';x',            'http://a/b/c/;x'],
            [self::RFC3986_BASE, 'g;x',           'http://a/b/c/g;x'],
            [self::RFC3986_BASE, 'g;x?y#s',       'http://a/b/c/g;x?y#s'],
            [self::RFC3986_BASE, '',              self::RFC3986_BASE],
            [self::RFC3986_BASE, '.',             'http://a/b/c/'],
            [self::RFC3986_BASE, './',            'http://a/b/c/'],
            [self::RFC3986_BASE, '..',            'http://a/b/'],
            [self::RFC3986_BASE, '../',           'http://a/b/'],
            [self::RFC3986_BASE, '../g',          'http://a/b/g'],
            [self::RFC3986_BASE, '../..',         'http://a/'],
            [self::RFC3986_BASE, '../../',        'http://a/'],
            [self::RFC3986_BASE, '../../g',       'http://a/g'],
            [self::RFC3986_BASE, '../../../g',    'http://a/g'],
            [self::RFC3986_BASE, '../../../../g', 'http://a/g'],
            [self::RFC3986_BASE, '/./g',          'http://a/g'],
            [self::RFC3986_BASE, '/../g',         'http://a/g'],
            [self::RFC3986_BASE, 'g.',            'http://a/b/c/g.'],
            [self::RFC3986_BASE, '.g',            'http://a/b/c/.g'],
            [self::RFC3986_BASE, 'g..',           'http://a/b/c/g..'],
            [self::RFC3986_BASE, '..g',           'http://a/b/c/..g'],
            [self::RFC3986_BASE, './../g',        'http://a/b/g'],
            [self::RFC3986_BASE, 'foo////g',      'http://a/b/c/foo////g'],
            [self::RFC3986_BASE, './g/.',         'http://a/b/c/g/'],
            [self::RFC3986_BASE, 'g/./h',         'http://a/b/c/g/h'],
            [self::RFC3986_BASE, 'g/../h',        'http://a/b/c/h'],
            [self::RFC3986_BASE, 'g;x=1/./y',     'http://a/b/c/g;x=1/y'],
            [self::RFC3986_BASE, 'g;x=1/../y',    'http://a/b/c/y'],
            ['http://u@a/b/c/d;p?q', '.',         'http://u@a/b/c/'],
            ['http://u:p@a/b/c/d;p?q', '.',       'http://u:p@a/b/c/'],
            ['http://a/b/c/d/', 'e',              'http://a/b/c/d/e'],
        ];
    }

    public function testAddAndRemoveQueryValues()
    {
        $uri = new Uri('http://foo.com/bar');
        $uri = Uri::withQueryValue($uri, 'a', 'b');
        $uri = Uri::withQueryValue($uri, 'c', 'd');
        $uri = Uri::withQueryValue($uri, 'e', null);
        $this->assertEquals('a=b&c=d&e', $uri->getQuery());

        $uri = Uri::withoutQueryValue($uri, 'c');
        $uri = Uri::withoutQueryValue($uri, 'e');
        $this->assertEquals('a=b', $uri->getQuery());
        $uri = Uri::withoutQueryValue($uri, 'a');
        $uri = Uri::withoutQueryValue($uri, 'a');
        $this->assertEquals('', $uri->getQuery());
    }

    public function testGetAuthorityReturnsCorrectPort()
    {
        // HTTPS non-standard port
        $uri = new Uri('https://foo.co:99');
        $this->assertEquals('foo.co:99', $uri->getAuthority());

        // HTTP non-standard port
        $uri = new Uri('http://foo.co:99');
        $this->assertEquals('foo.co:99', $uri->getAuthority());

        // No scheme
        $uri = new Uri('foo.co:99');
        $this->assertEquals('foo.co:99', $uri->getAuthority());

        // No host or port
        $uri = new Uri('http:');
        $this->assertEquals('', $uri->getAuthority());

        // No host or port
        $uri = new Uri('http://foo.co');
        $this->assertEquals('foo.co', $uri->getAuthority());
    }

    public function pathTestProvider()
    {
        return [
            // Percent encode spaces.
            ['http://foo.com/baz bar', 'http://foo.com/baz%20bar'],
            // Don't encoding something that's already encoded.
            ['http://foo.com/baz%20bar', 'http://foo.com/baz%20bar'],
            // Percent encode invalid percent encodings
            ['http://foo.com/baz%2-bar', 'http://foo.com/baz%252-bar'],
            // Don't encode path segments
            ['http://foo.com/baz/bar/bam?a', 'http://foo.com/baz/bar/bam?a'],
            ['http://foo.com/baz+bar', 'http://foo.com/baz+bar'],
            ['http://foo.com/baz:bar', 'http://foo.com/baz:bar'],
            ['http://foo.com/baz@bar', 'http://foo.com/baz@bar'],
            ['http://foo.com/baz(bar);bam/', 'http://foo.com/baz(bar);bam/'],
            ['http://foo.com/a-zA-Z0-9.-_~!$&\'()*+,;=:@', 'http://foo.com/a-zA-Z0-9.-_~!$&\'()*+,;=:@'],
        ];
    }

    /**
     * @dataProvider pathTestProvider
     */
    public function testUriEncodesPathProperly($input, $output)
    {
        $uri = new Uri($input);
        $this->assertEquals((string) $uri, $output);
    }

    public function testDoesNotAddPortWhenNoPort()
    {
        $this->assertEquals('bar', new Uri('//bar'));
        $this->assertEquals('bar', (new Uri('//bar'))->getHost());
    }

    public function testAllowsForRelativeUri()
    {
        $uri = (new Uri)->withPath('foo');
        $this->assertEquals('foo', $uri->getPath());
        $this->assertEquals('foo', (string) $uri);
    }

    public function testAddsSlashForRelativeUriStringWithHost()
    {
        $uri = (new Uri)->withPath('foo')->withHost('bar.com');
        $this->assertEquals('foo', $uri->getPath());
        $this->assertEquals('bar.com/foo', (string) $uri);
    }

    /**
     * @dataProvider pathTestNoAuthority
     */
    public function testNoAuthority($input)
    {
        $uri = new Uri($input);

        $this->assertEquals($input, (string) $uri);
    }

    public function pathTestNoAuthority()
    {
        return [
            // path-rootless
            ['urn:example:animal:ferret:nose'],
            // path-absolute
            ['urn:/example:animal:ferret:nose'],
            ['urn:/'],
            // path-empty
            ['urn:'],
            ['urn'],
        ];
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Unable to parse URI
     */
    public function testNoAuthorityWithInvalidPath()
    {
        $input = 'urn://example:animal:ferret:nose';
        $uri = new Uri($input);
    }
}
# CHANGELOG

## 6.1.1 - 2015-11-22

* Bug fix: Proxy::wrapSync() now correctly proxies to the appropriate handler
  https://github.com/guzzle/guzzle/commit/911bcbc8b434adce64e223a6d1d14e9a8f63e4e4
* Feature: HandlerStack is now more generic.
  https://github.com/guzzle/guzzle/commit/f2102941331cda544745eedd97fc8fd46e1ee33e
* Bug fix: setting verify to false in the StreamHandler now disables peer
  verification. https://github.com/guzzle/guzzle/issues/1256
* Feature: Middleware now uses an exception factory, including more error
  context. https://github.com/guzzle/guzzle/pull/1282
* Feature: better support for disabled functions.
  https://github.com/guzzle/guzzle/pull/1287
* Bug fix: fixed regression where MockHandler was not using `sink`.
  https://github.com/guzzle/guzzle/pull/1292

## 6.1.0 - 2015-09-08

* Feature: Added the `on_stats` request option to provide access to transfer
  statistics for requests. https://github.com/guzzle/guzzle/pull/1202
* Feature: Added the ability to persist session cookies in CookieJars.
  https://github.com/guzzle/guzzle/pull/1195
* Feature: Some compatibility updates for Google APP Engine
  https://github.com/guzzle/guzzle/pull/1216
* Feature: Added support for NO_PROXY to prevent the use of a proxy based on
  a simple set of rules. https://github.com/guzzle/guzzle/pull/1197
* Feature: Cookies can now contain square brackets.
  https://github.com/guzzle/guzzle/pull/1237
* Bug fix: Now correctly parsing `=` inside of quotes in Cookies.
  https://github.com/guzzle/guzzle/pull/1232
* Bug fix: Cusotm cURL options now correctly override curl options of the
  same name. https://github.com/guzzle/guzzle/pull/1221
* Bug fix: Content-Type header is now added when using an explicitly provided
  multipart body. https://github.com/guzzle/guzzle/pull/1218
* Bug fix: Now ignoring Set-Cookie headers that have no name.
* Bug fix: Reason phrase is no longer cast to an int in some cases in the
  cURL handler. https://github.com/guzzle/guzzle/pull/1187
* Bug fix: Remove the Authorization header when redirecting if the Host
  header changes. https://github.com/guzzle/guzzle/pull/1207
* Bug fix: Cookie path matching fixes
  https://github.com/guzzle/guzzle/issues/1129
* Bug fix: Fixing the cURL `body_as_string` setting
  https://github.com/guzzle/guzzle/pull/1201
* Bug fix: quotes are no longer stripped when parsing cookies.
  https://github.com/guzzle/guzzle/issues/1172
* Bug fix: `form_params` and `query` now always uses the `&` separator.
  https://github.com/guzzle/guzzle/pull/1163
* Bug fix: Adding a Content-Length to PHP stream wrapper requests if not set.
  https://github.com/guzzle/guzzle/pull/1189

## 6.0.2 - 2015-07-04

* Fixed a memory leak in the curl handlers in which references to callbacks
  were not being removed by `curl_reset`.
* Cookies are now extracted properly before redirects.
* Cookies now allow more character ranges.
* Decoded Content-Encoding responses are now modified to correctly reflect
  their state if the encoding was automatically removed by a handler. This
  means that the `Content-Encoding` header may be removed an the
  `Content-Length` modified to reflect the message size after removing the
  encoding.
* Added a more explicit error message when trying to use `form_params` and
  `multipart` in the same request.
* Several fixes for HHVM support.
* Functions are now conditionally required using an additional level of
  indirection to help with global Composer installations.

## 6.0.1 - 2015-05-27

* Fixed a bug with serializing the `query` request option where the `&`
  separator was missing.
* Added a better error message for when `body` is provided as an array. Please
  use `form_params` or `multipart` instead.
* Various doc fixes.

## 6.0.0 - 2015-05-26

* See the UPGRADING.md document for more information.
* Added `multipart` and `form_params` request options.
* Added `synchronous` request option.
* Added the `on_headers` request option.
* Fixed `expect` handling.
* No longer adding default middlewares in the client ctor. These need to be
  present on the provided handler in order to work.
* Requests are no longer initiated when sending async requests with the
  CurlMultiHandler. This prevents unexpected recursion from requests completing
  while ticking the cURL loop.
* Removed the semantics of setting `default` to `true`. This is no longer
  required now that the cURL loop is not ticked for async requests.
* Added request and response logging middleware.
* No longer allowing self signed certificates when using the StreamHandler.
* Ensuring that `sink` is valid if saving to a file.
* Request exceptions now include a "handler context" which provides handler
  specific contextual information.
* Added `GuzzleHttp\RequestOptions` to allow request options to be applied
  using constants.
* `$maxHandles` has been removed from CurlMultiHandler.
* `MultipartPostBody` is now part of the `guzzlehttp/psr7` package.

## 5.3.0 - 2015-05-19

* Mock now supports `save_to`
* Marked `AbstractRequestEvent::getTransaction()` as public.
* Fixed a bug in which multiple headers using different casing would overwrite
  previous headers in the associative array.
* Added `Utils::getDefaultHandler()`
* Marked `GuzzleHttp\Client::getDefaultUserAgent` as deprecated.
* URL scheme is now always lowercased.

## 6.0.0-beta.1

* Requires PHP >= 5.5
* Updated to use PSR-7
  * Requires immutable messages, which basically means an event based system
    owned by a request instance is no longer possible.
  * Utilizing the [Guzzle PSR-7 package](https://github.com/guzzle/psr7).
  * Removed the dependency on `guzzlehttp/streams`. These stream abstractions
    are available in the `guzzlehttp/psr7` package under the `GuzzleHttp\Psr7`
    namespace.
* Added middleware and handler system
  * Replaced the Guzzle event and subscriber system with a middleware system.
  * No longer depends on RingPHP, but rather places the HTTP handlers directly
    in Guzzle, operating on PSR-7 messages.
  * Retry logic is now encapsulated in `GuzzleHttp\Middleware::retry`, which
    means the `guzzlehttp/retry-subscriber` is now obsolete.
  * Mocking responses is now handled using `GuzzleHttp\Handler\MockHandler`.
* Asynchronous responses
  * No longer supports the `future` request option to send an async request.
    Instead, use one of the `*Async` methods of a client (e.g., `requestAsync`,
    `getAsync`, etc.).
  * Utilizing `GuzzleHttp\Promise` instead of React's promise library to avoid
    recursion required by chaining and forwarding react promises. See
    https://github.com/guzzle/promises
  * Added `requestAsync` and `sendAsync` to send request asynchronously.
  * Added magic methods for `getAsync()`, `postAsync()`, etc. to send requests
    asynchronously.
* Request options
  * POST and form updates
    * Added the `form_fields` and `form_files` request options.
    * Removed the `GuzzleHttp\Post` namespace.
    * The `body` request option no longer accepts an array for POST requests.
  * The `exceptions` request option has been deprecated in favor of the
    `http_errors` request options.
  * The `save_to` request option has been deprecated in favor of `sink` request
    option.
* Clients no longer accept an array of URI template string and variables for
  URI variables. You will need to expand URI templates before passing them
  into a client constructor or request method.
* Client methods `get()`, `post()`, `put()`, `patch()`, `options()`, etc. are
  now magic methods that will send synchronous requests.
* Replaced `Utils.php` with plain functions in `functions.php`.
* Removed `GuzzleHttp\Collection`.
* Removed `GuzzleHttp\BatchResults`. Batched pool results are now returned as
  an array.
* Removed `GuzzleHttp\Query`. Query string handling is now handled using an
  associative array passed into the `query` request option. The query string
  is serialized using PHP's `http_build_query`. If you need more control, you
  can pass the query string in as a string.
* `GuzzleHttp\QueryParser` has been replaced with the
  `GuzzleHttp\Psr7\parse_query`.

## 5.2.0 - 2015-01-27

* Added `AppliesHeadersInterface` to make applying headers to a request based
  on the body more generic and not specific to `PostBodyInterface`.
* Reduced the number of stack frames needed to send requests.
* Nested futures are now resolved in the client rather than the RequestFsm
* Finishing state transitions is now handled in the RequestFsm rather than the
  RingBridge.
* Added a guard in the Pool class to not use recursion for request retries.

## 5.1.0 - 2014-12-19

* Pool class no longer uses recursion when a request is intercepted.
* The size of a Pool can now be dynamically adjusted using a callback.
  See https://github.com/guzzle/guzzle/pull/943.
* Setting a request option to `null` when creating a request with a client will
  ensure that the option is not set. This allows you to overwrite default
  request options on a per-request basis.
  See https://github.com/guzzle/guzzle/pull/937.
* Added the ability to limit which protocols are allowed for redirects by
  specifying a `protocols` array in the `allow_redirects` request option.
* Nested futures due to retries are now resolved when waiting for synchronous
  responses. See https://github.com/guzzle/guzzle/pull/947.
* `"0"` is now an allowed URI path. See
  https://github.com/guzzle/guzzle/pull/935.
* `Query` no longer typehints on the `$query` argument in the constructor,
  allowing for strings and arrays.
* Exceptions thrown in the `end` event are now correctly wrapped with Guzzle
  specific exceptions if necessary.

## 5.0.3 - 2014-11-03

This change updates query strings so that they are treated as un-encoded values
by default where the value represents an un-encoded value to send over the
wire. A Query object then encodes the value before sending over the wire. This
means that even value query string values (e.g., ":") are url encoded. This
makes the Query class match PHP's http_build_query function. However, if you
want to send requests over the wire using valid query string characters that do
not need to be encoded, then you can provide a string to Url::setQuery() and
pass true as the second argument to specify that the query string is a raw
string that should not be parsed or encoded (unless a call to getQuery() is
subsequently made, forcing the query-string to be converted into a Query
object).

## 5.0.2 - 2014-10-30

* Added a trailing `\r\n` to multipart/form-data payloads. See
  https://github.com/guzzle/guzzle/pull/871
* Added a `GuzzleHttp\Pool::send()` convenience method to match the docs.
* Status codes are now returned as integers. See
  https://github.com/guzzle/guzzle/issues/881
* No longer overwriting an existing `application/x-www-form-urlencoded` header
  when sending POST requests, allowing for customized headers. See
  https://github.com/guzzle/guzzle/issues/877
* Improved path URL serialization.

  * No longer double percent-encoding characters in the path or query string if
    they are already encoded.
  * Now properly encoding the supplied path to a URL object, instead of only
    encoding ' ' and '?'.
  * Note: This has been changed in 5.0.3 to now encode query string values by
    default unless the `rawString` argument is provided when setting the query
    string on a URL: Now allowing many more characters to be present in the
    query string without being percent encoded. See http://tools.ietf.org/html/rfc3986#appendix-A

## 5.0.1 - 2014-10-16

Bugfix release.

* Fixed an issue where connection errors still returned response object in
  error and end events event though the response is unusable. This has been
  corrected so that a response is not returned in the `getResponse` method of
  these events if the response did not complete. https://github.com/guzzle/guzzle/issues/867
* Fixed an issue where transfer statistics were not being populated in the
  RingBridge. https://github.com/guzzle/guzzle/issues/866

## 5.0.0 - 2014-10-12

Adding support for non-blocking responses and some minor API cleanup.

### New Features

* Added support for non-blocking responses based on `guzzlehttp/guzzle-ring`.
* Added a public API for creating a default HTTP adapter.
* Updated the redirect plugin to be non-blocking so that redirects are sent
  concurrently. Other plugins like this can now be updated to be non-blocking.
* Added a "progress" event so that you can get upload and download progress
  events.
* Added `GuzzleHttp\Pool` which implements FutureInterface and transfers
  requests concurrently using a capped pool size as efficiently as possible.
* Added `hasListeners()` to EmitterInterface.
* Removed `GuzzleHttp\ClientInterface::sendAll` and marked
  `GuzzleHttp\Client::sendAll` as deprecated (it's still there, just not the
  recommended way).

### Breaking changes

The breaking changes in this release are relatively minor. The biggest thing to
look out for is that request and response objects no longer implement fluent
interfaces.

* Removed the fluent interfaces (i.e., `return $this`) from requests,
  responses, `GuzzleHttp\Collection`, `GuzzleHttp\Url`,
  `GuzzleHttp\Query`, `GuzzleHttp\Post\PostBody`, and
  `GuzzleHttp\Cookie\SetCookie`. This blog post provides a good outline of
  why I did this: http://ocramius.github.io/blog/fluent-interfaces-are-evil/.
  This also makes the Guzzle message interfaces compatible with the current
  PSR-7 message proposal.
* Removed "functions.php", so that Guzzle is truly PSR-4 compliant. Except
  for the HTTP request functions from function.php, these functions are now
  implemented in `GuzzleHttp\Utils` using camelCase. `GuzzleHttp\json_decode`
  moved to `GuzzleHttp\Utils::jsonDecode`. `GuzzleHttp\get_path` moved to
  `GuzzleHttp\Utils::getPath`. `GuzzleHttp\set_path` moved to
  `GuzzleHttp\Utils::setPath`. `GuzzleHttp\batch` should now be
  `GuzzleHttp\Pool::batch`, which returns an `objectStorage`. Using functions.php
  caused problems for many users: they aren't PSR-4 compliant, require an
  explicit include, and needed an if-guard to ensure that the functions are not
  declared multiple times.
* Rewrote adapter layer.
    * Removing all classes from `GuzzleHttp\Adapter`, these are now
      implemented as callables that are stored in `GuzzleHttp\Ring\Client`.
    * Removed the concept of "parallel adapters". Sending requests serially or
      concurrently is now handled using a single adapter.
    * Moved `GuzzleHttp\Adapter\Transaction` to `GuzzleHttp\Transaction`. The
      Transaction object now exposes the request, response, and client as public
      properties. The getters and setters have been removed.
* Removed the "headers" event. This event was only useful for changing the
  body a response once the headers of the response were known. You can implement
  a similar behavior in a number of ways. One example might be to use a
  FnStream that has access to the transaction being sent. For example, when the
  first byte is written, you could check if the response headers match your
  expectations, and if so, change the actual stream body that is being
  written to.
* Removed the `asArray` parameter from
  `GuzzleHttp\Message\MessageInterface::getHeader`. If you want to get a header
  value as an array, then use the newly added `getHeaderAsArray()` method of
  `MessageInterface`. This change makes the Guzzle interfaces compatible with
  the PSR-7 interfaces.
* `GuzzleHttp\Message\MessageFactory` no longer allows subclasses to add
  custom request options using double-dispatch (this was an implementation
  detail). Instead, you should now provide an associative array to the
  constructor which is a mapping of the request option name mapping to a
  function that applies the option value to a request.
* Removed the concept of "throwImmediately" from exceptions and error events.
  This control mechanism was used to stop a transfer of concurrent requests
  from completing. This can now be handled by throwing the exception or by
  cancelling a pool of requests or each outstanding future request individually.
* Updated to "GuzzleHttp\Streams" 3.0.
    * `GuzzleHttp\Stream\StreamInterface::getContents()` no longer accepts a
      `maxLen` parameter. This update makes the Guzzle streams project
      compatible with the current PSR-7 proposal.
    * `GuzzleHttp\Stream\Stream::__construct`,
      `GuzzleHttp\Stream\Stream::factory`, and
      `GuzzleHttp\Stream\Utils::create` no longer accept a size in the second
      argument. They now accept an associative array of options, including the
      "size" key and "metadata" key which can be used to provide custom metadata.

## 4.2.2 - 2014-09-08

* Fixed a memory leak in the CurlAdapter when reusing cURL handles.
* No longer using `request_fulluri` in stream adapter proxies.
* Relative redirects are now based on the last response, not the first response.

## 4.2.1 - 2014-08-19

* Ensuring that the StreamAdapter does not always add a Content-Type header
* Adding automated github releases with a phar and zip

## 4.2.0 - 2014-08-17

* Now merging in default options using a case-insensitive comparison.
  Closes https://github.com/guzzle/guzzle/issues/767
* Added the ability to automatically decode `Content-Encoding` response bodies
  using the `decode_content` request option. This is set to `true` by default
  to decode the response body if it comes over the wire with a
  `Content-Encoding`. Set this value to `false` to disable decoding the
  response content, and pass a string to provide a request `Accept-Encoding`
  header and turn on automatic response decoding. This feature now allows you
  to pass an `Accept-Encoding` header in the headers of a request but still
  disable automatic response decoding.
  Closes https://github.com/guzzle/guzzle/issues/764
* Added the ability to throw an exception immediately when transferring
  requests in parallel. Closes https://github.com/guzzle/guzzle/issues/760
* Updating guzzlehttp/streams dependency to ~2.1
* No longer utilizing the now deprecated namespaced methods from the stream
  package.

## 4.1.8 - 2014-08-14

* Fixed an issue in the CurlFactory that caused setting the `stream=false`
  request option to throw an exception.
  See: https://github.com/guzzle/guzzle/issues/769
* TransactionIterator now calls rewind on the inner iterator.
  See: https://github.com/guzzle/guzzle/pull/765
* You can now set the `Content-Type` header to `multipart/form-data`
  when creating POST requests to force multipart bodies.
  See https://github.com/guzzle/guzzle/issues/768

## 4.1.7 - 2014-08-07

* Fixed an error in the HistoryPlugin that caused the same request and response
  to be logged multiple times when an HTTP protocol error occurs.
* Ensuring that cURL does not add a default Content-Type when no Content-Type
  has been supplied by the user. This prevents the adapter layer from modifying
  the request that is sent over the wire after any listeners may have already
  put the request in a desired state (e.g., signed the request).
* Throwing an exception when you attempt to send requests that have the
  "stream" set to true in parallel using the MultiAdapter.
* Only calling curl_multi_select when there are active cURL handles. This was
  previously changed and caused performance problems on some systems due to PHP
  always selecting until the maximum select timeout.
* Fixed a bug where multipart/form-data POST fields were not correctly
  aggregated (e.g., values with "&").

## 4.1.6 - 2014-08-03

* Added helper methods to make it easier to represent messages as strings,
  including getting the start line and getting headers as a string.

## 4.1.5 - 2014-08-02

* Automatically retrying cURL "Connection died, retrying a fresh connect"
  errors when possible.
* cURL implementation cleanup
* Allowing multiple event subscriber listeners to be registered per event by
  passing an array of arrays of listener configuration.

## 4.1.4 - 2014-07-22

* Fixed a bug that caused multi-part POST requests with more than one field to
  serialize incorrectly.
* Paths can now be set to "0"
* `ResponseInterface::xml` now accepts a `libxml_options` option and added a
  missing default argument that was required when parsing XML response bodies.
* A `save_to` stream is now created lazily, which means that files are not
  created on disk unless a request succeeds.

## 4.1.3 - 2014-07-15

* Various fixes to multipart/form-data POST uploads
* Wrapping function.php in an if-statement to ensure Guzzle can be used
  globally and in a Composer install
* Fixed an issue with generating and merging in events to an event array
* POST headers are only applied before sending a request to allow you to change
  the query aggregator used before uploading
* Added much more robust query string parsing
* Fixed various parsing and normalization issues with URLs
* Fixing an issue where multi-valued headers were not being utilized correctly
  in the StreamAdapter

## 4.1.2 - 2014-06-18

* Added support for sending payloads with GET requests

## 4.1.1 - 2014-06-08

* Fixed an issue related to using custom message factory options in subclasses
* Fixed an issue with nested form fields in a multi-part POST
* Fixed an issue with using the `json` request option for POST requests
* Added `ToArrayInterface` to `GuzzleHttp\Cookie\CookieJar`

## 4.1.0 - 2014-05-27

* Added a `json` request option to easily serialize JSON payloads.
* Added a `GuzzleHttp\json_decode()` wrapper to safely parse JSON.
* Added `setPort()` and `getPort()` to `GuzzleHttp\Message\RequestInterface`.
* Added the ability to provide an emitter to a client in the client constructor.
* Added the ability to persist a cookie session using $_SESSION.
* Added a trait that can be used to add event listeners to an iterator.
* Removed request method constants from RequestInterface.
* Fixed warning when invalid request start-lines are received.
* Updated MessageFactory to work with custom request option methods.
* Updated cacert bundle to latest build.

4.0.2 (2014-04-16)
------------------

* Proxy requests using the StreamAdapter now properly use request_fulluri (#632)
* Added the ability to set scalars as POST fields (#628)

## 4.0.1 - 2014-04-04

* The HTTP status code of a response is now set as the exception code of
  RequestException objects.
* 303 redirects will now correctly switch from POST to GET requests.
* The default parallel adapter of a client now correctly uses the MultiAdapter.
* HasDataTrait now initializes the internal data array as an empty array so
  that the toArray() method always returns an array.

## 4.0.0 - 2014-03-29

* For more information on the 4.0 transition, see:
  http://mtdowling.com/blog/2014/03/15/guzzle-4-rc/
* For information on changes and upgrading, see:
  https://github.com/guzzle/guzzle/blob/master/UPGRADING.md#3x-to-40
* Added `GuzzleHttp\batch()` as a convenience function for sending requests in
  parallel without needing to write asynchronous code.
* Restructured how events are added to `GuzzleHttp\ClientInterface::sendAll()`.
  You can now pass a callable or an array of associative arrays where each
  associative array contains the "fn", "priority", and "once" keys.

## 4.0.0.rc-2 - 2014-03-25

* Removed `getConfig()` and `setConfig()` from clients to avoid confusion
  around whether things like base_url, message_factory, etc. should be able to
  be retrieved or modified.
* Added `getDefaultOption()` and `setDefaultOption()` to ClientInterface
* functions.php functions were renamed using snake_case to match PHP idioms
* Added support for `HTTP_PROXY`, `HTTPS_PROXY`, and
  `GUZZLE_CURL_SELECT_TIMEOUT` environment variables
* Added the ability to specify custom `sendAll()` event priorities
* Added the ability to specify custom stream context options to the stream
  adapter.
* Added a functions.php function for `get_path()` and `set_path()`
* CurlAdapter and MultiAdapter now use a callable to generate curl resources
* MockAdapter now properly reads a body and emits a `headers` event
* Updated Url class to check if a scheme and host are set before adding ":"
  and "//". This allows empty Url (e.g., "") to be serialized as "".
* Parsing invalid XML no longer emits warnings
* Curl classes now properly throw AdapterExceptions
* Various performance optimizations
* Streams are created with the faster `Stream\create()` function
* Marked deprecation_proxy() as internal
* Test server is now a collection of static methods on a class

## 4.0.0-rc.1 - 2014-03-15

* See https://github.com/guzzle/guzzle/blob/master/UPGRADING.md#3x-to-40

## 3.8.1 - 2014-01-28

* Bug: Always using GET requests when redirecting from a 303 response
* Bug: CURLOPT_SSL_VERIFYHOST is now correctly set to false when setting `$certificateAuthority` to false in
  `Guzzle\Http\ClientInterface::setSslVerification()`
* Bug: RedirectPlugin now uses strict RFC 3986 compliance when combining a base URL with a relative URL
* Bug: The body of a request can now be set to `"0"`
* Sending PHP stream requests no longer forces `HTTP/1.0`
* Adding more information to ExceptionCollection exceptions so that users have more context, including a stack trace of
  each sub-exception
* Updated the `$ref` attribute in service descriptions to merge over any existing parameters of a schema (rather than
  clobbering everything).
* Merging URLs will now use the query string object from the relative URL (thus allowing custom query aggregators)
* Query strings are now parsed in a way that they do no convert empty keys with no value to have a dangling `=`.
  For example `foo&bar=baz` is now correctly parsed and recognized as `foo&bar=baz` rather than `foo=&bar=baz`.
* Now properly escaping the regular expression delimiter when matching Cookie domains.
* Network access is now disabled when loading XML documents

## 3.8.0 - 2013-12-05

* Added the ability to define a POST name for a file
* JSON response parsing now properly walks additionalProperties
* cURL error code 18 is now retried automatically in the BackoffPlugin
* Fixed a cURL error when URLs contain fragments
* Fixed an issue in the BackoffPlugin retry event where it was trying to access all exceptions as if they were
  CurlExceptions
* CURLOPT_PROGRESS function fix for PHP 5.5 (69fcc1e)
* Added the ability for Guzzle to work with older versions of cURL that do not support `CURLOPT_TIMEOUT_MS`
* Fixed a bug that was encountered when parsing empty header parameters
* UriTemplate now has a `setRegex()` method to match the docs
* The `debug` request parameter now checks if it is truthy rather than if it exists
* Setting the `debug` request parameter to true shows verbose cURL output instead of using the LogPlugin
* Added the ability to combine URLs using strict RFC 3986 compliance
* Command objects can now return the validation errors encountered by the command
* Various fixes to cache revalidation (#437 and 29797e5)
* Various fixes to the AsyncPlugin
* Cleaned up build scripts

## 3.7.4 - 2013-10-02

* Bug fix: 0 is now an allowed value in a description parameter that has a default value (#430)
* Bug fix: SchemaFormatter now returns an integer when formatting to a Unix timestamp
  (see https://github.com/aws/aws-sdk-php/issues/147)
* Bug fix: Cleaned up and fixed URL dot segment removal to properly resolve internal dots
* Minimum PHP version is now properly specified as 5.3.3 (up from 5.3.2) (#420)
* Updated the bundled cacert.pem (#419)
* OauthPlugin now supports adding authentication to headers or query string (#425)

## 3.7.3 - 2013-09-08

* Added the ability to get the exception associated with a request/command when using `MultiTransferException` and
  `CommandTransferException`.
* Setting `additionalParameters` of a response to false is now honored when parsing responses with a service description
* Schemas are only injected into response models when explicitly configured.
* No longer guessing Content-Type based on the path of a request. Content-Type is now only guessed based on the path of
  an EntityBody.
* Bug fix: ChunkedIterator can now properly chunk a \Traversable as well as an \Iterator.
* Bug fix: FilterIterator now relies on `\Iterator` instead of `\Traversable`.
* Bug fix: Gracefully handling malformed responses in RequestMediator::writeResponseBody()
* Bug fix: Replaced call to canCache with canCacheRequest in the CallbackCanCacheStrategy of the CachePlugin
* Bug fix: Visiting XML attributes first before visiting XML children when serializing requests
* Bug fix: Properly parsing headers that contain commas contained in quotes
* Bug fix: mimetype guessing based on a filename is now case-insensitive

## 3.7.2 - 2013-08-02

* Bug fix: Properly URL encoding paths when using the PHP-only version of the UriTemplate expander
  See https://github.com/guzzle/guzzle/issues/371
* Bug fix: Cookie domains are now matched correctly according to RFC 6265
  See https://github.com/guzzle/guzzle/issues/377
* Bug fix: GET parameters are now used when calculating an OAuth signature
* Bug fix: Fixed an issue with cache revalidation where the If-None-Match header was being double quoted
* `Guzzle\Common\AbstractHasDispatcher::dispatch()` now returns the event that was dispatched
* `Guzzle\Http\QueryString::factory()` now guesses the most appropriate query aggregator to used based on the input.
  See https://github.com/guzzle/guzzle/issues/379
* Added a way to add custom domain objects to service description parsing using the `operation.parse_class` event. See
  https://github.com/guzzle/guzzle/pull/380
* cURL multi cleanup and optimizations

## 3.7.1 - 2013-07-05

* Bug fix: Setting default options on a client now works
* Bug fix: Setting options on HEAD requests now works. See #352
* Bug fix: Moving stream factory before send event to before building the stream. See #353
* Bug fix: Cookies no longer match on IP addresses per RFC 6265
* Bug fix: Correctly parsing header parameters that are in `<>` and quotes
* Added `cert` and `ssl_key` as request options
* `Host` header can now diverge from the host part of a URL if the header is set manually
* `Guzzle\Service\Command\LocationVisitor\Request\XmlVisitor` was rewritten to change from using SimpleXML to XMLWriter
* OAuth parameters are only added via the plugin if they aren't already set
* Exceptions are now thrown when a URL cannot be parsed
* Returning `false` if `Guzzle\Http\EntityBody::getContentMd5()` fails
* Not setting a `Content-MD5` on a command if calculating the Content-MD5 fails via the CommandContentMd5Plugin

## 3.7.0 - 2013-06-10

* See UPGRADING.md for more information on how to upgrade.
* Requests now support the ability to specify an array of $options when creating a request to more easily modify a
  request. You can pass a 'request.options' configuration setting to a client to apply default request options to
  every request created by a client (e.g. default query string variables, headers, curl options, etc.).
* Added a static facade class that allows you to use Guzzle with static methods and mount the class to `\Guzzle`.
  See `Guzzle\Http\StaticClient::mount`.
* Added `command.request_options` to `Guzzle\Service\Command\AbstractCommand` to pass request options to requests
      created by a command (e.g. custom headers, query string variables, timeout settings, etc.).
* Stream size in `Guzzle\Stream\PhpStreamRequestFactory` will now be set if Content-Length is returned in the
  headers of a response
* Added `Guzzle\Common\Collection::setPath($path, $value)` to set a value into an array using a nested key
  (e.g. `$collection->setPath('foo/baz/bar', 'test'); echo $collection['foo']['bar']['bar'];`)
* ServiceBuilders now support storing and retrieving arbitrary data
* CachePlugin can now purge all resources for a given URI
* CachePlugin can automatically purge matching cached items when a non-idempotent request is sent to a resource
* CachePlugin now uses the Vary header to determine if a resource is a cache hit
* `Guzzle\Http\Message\Response` now implements `\Serializable`
* Added `Guzzle\Cache\CacheAdapterFactory::fromCache()` to more easily create cache adapters
* `Guzzle\Service\ClientInterface::execute()` now accepts an array, single command, or Traversable
* Fixed a bug in `Guzzle\Http\Message\Header\Link::addLink()`
* Better handling of calculating the size of a stream in `Guzzle\Stream\Stream` using fstat() and caching the size
* `Guzzle\Common\Exception\ExceptionCollection` now creates a more readable exception message
* Fixing BC break: Added back the MonologLogAdapter implementation rather than extending from PsrLog so that older
  Symfony users can still use the old version of Monolog.
* Fixing BC break: Added the implementation back in for `Guzzle\Http\Message\AbstractMessage::getTokenizedHeader()`.
  Now triggering an E_USER_DEPRECATED warning when used. Use `$message->getHeader()->parseParams()`.
* Several performance improvements to `Guzzle\Common\Collection`
* Added an `$options` argument to the end of the following methods of `Guzzle\Http\ClientInterface`:
  createRequest, head, delete, put, patch, post, options, prepareRequest
* Added an `$options` argument to the end of `Guzzle\Http\Message\Request\RequestFactoryInterface::createRequest()`
* Added an `applyOptions()` method to `Guzzle\Http\Message\Request\RequestFactoryInterface`
* Changed `Guzzle\Http\ClientInterface::get($uri = null, $headers = null, $body = null)` to
  `Guzzle\Http\ClientInterface::get($uri = null, $headers = null, $options = array())`. You can still pass in a
  resource, string, or EntityBody into the $options parameter to specify the download location of the response.
* Changed `Guzzle\Common\Collection::__construct($data)` to no longer accepts a null value for `$data` but a
  default `array()`
* Added `Guzzle\Stream\StreamInterface::isRepeatable`
* Removed `Guzzle\Http\ClientInterface::setDefaultHeaders(). Use
  $client->getConfig()->setPath('request.options/headers/{header_name}', 'value')`. or
  $client->getConfig()->setPath('request.options/headers', array('header_name' => 'value'))`.
* Removed `Guzzle\Http\ClientInterface::getDefaultHeaders(). Use $client->getConfig()->getPath('request.options/headers')`.
* Removed `Guzzle\Http\ClientInterface::expandTemplate()`
* Removed `Guzzle\Http\ClientInterface::setRequestFactory()`
* Removed `Guzzle\Http\ClientInterface::getCurlMulti()`
* Removed `Guzzle\Http\Message\RequestInterface::canCache`
* Removed `Guzzle\Http\Message\RequestInterface::setIsRedirect`
* Removed `Guzzle\Http\Message\RequestInterface::isRedirect`
* Made `Guzzle\Http\Client::expandTemplate` and `getUriTemplate` protected methods.
* You can now enable E_USER_DEPRECATED warnings to see if you are using a deprecated method by setting
  `Guzzle\Common\Version::$emitWarnings` to true.
* Marked `Guzzle\Http\Message\Request::isResponseBodyRepeatable()` as deprecated. Use
      `$request->getResponseBody()->isRepeatable()` instead.
* Marked `Guzzle\Http\Message\Request::canCache()` as deprecated. Use
  `Guzzle\Plugin\Cache\DefaultCanCacheStrategy->canCacheRequest()` instead.
* Marked `Guzzle\Http\Message\Request::canCache()` as deprecated. Use
  `Guzzle\Plugin\Cache\DefaultCanCacheStrategy->canCacheRequest()` instead.
* Marked `Guzzle\Http\Message\Request::setIsRedirect()` as deprecated. Use the HistoryPlugin instead.
* Marked `Guzzle\Http\Message\Request::isRedirect()` as deprecated. Use the HistoryPlugin instead.
* Marked `Guzzle\Cache\CacheAdapterFactory::factory()` as deprecated
* Marked 'command.headers', 'command.response_body' and 'command.on_complete' as deprecated for AbstractCommand.
  These will work through Guzzle 4.0
* Marked 'request.params' for `Guzzle\Http\Client` as deprecated. Use [request.options][params].
* Marked `Guzzle\Service\Client::enableMagicMethods()` as deprecated. Magic methods can no longer be disabled on a Guzzle\Service\Client.
* Marked `Guzzle\Service\Client::getDefaultHeaders()` as deprecated. Use $client->getConfig()->getPath('request.options/headers')`.
* Marked `Guzzle\Service\Client::setDefaultHeaders()` as deprecated. Use $client->getConfig()->setPath('request.options/headers/{header_name}', 'value')`.
* Marked `Guzzle\Parser\Url\UrlParser` as deprecated. Just use PHP's `parse_url()` and percent encode your UTF-8.
* Marked `Guzzle\Common\Collection::inject()` as deprecated.
* Marked `Guzzle\Plugin\CurlAuth\CurlAuthPlugin` as deprecated. Use `$client->getConfig()->setPath('request.options/auth', array('user', 'pass', 'Basic|Digest');`
* CacheKeyProviderInterface and DefaultCacheKeyProvider are no longer used. All of this logic is handled in a
  CacheStorageInterface. These two objects and interface will be removed in a future version.
* Always setting X-cache headers on cached responses
* Default cache TTLs are now handled by the CacheStorageInterface of a CachePlugin
* `CacheStorageInterface::cache($key, Response $response, $ttl = null)` has changed to `cache(RequestInterface
  $request, Response $response);`
* `CacheStorageInterface::fetch($key)` has changed to `fetch(RequestInterface $request);`
* `CacheStorageInterface::delete($key)` has changed to `delete(RequestInterface $request);`
* Added `CacheStorageInterface::purge($url)`
* `DefaultRevalidation::__construct(CacheKeyProviderInterface $cacheKey, CacheStorageInterface $cache, CachePlugin
  $plugin)` has changed to `DefaultRevalidation::__construct(CacheStorageInterface $cache,
  CanCacheStrategyInterface $canCache = null)`
* Added `RevalidationInterface::shouldRevalidate(RequestInterface $request, Response $response)`

## 3.6.0 - 2013-05-29

* ServiceDescription now implements ToArrayInterface
* Added command.hidden_params to blacklist certain headers from being treated as additionalParameters
* Guzzle can now correctly parse incomplete URLs
* Mixed casing of headers are now forced to be a single consistent casing across all values for that header.
* Messages internally use a HeaderCollection object to delegate handling case-insensitive header resolution
* Removed the whole changedHeader() function system of messages because all header changes now go through addHeader().
* Specific header implementations can be created for complex headers. When a message creates a header, it uses a
  HeaderFactory which can map specific headers to specific header classes. There is now a Link header and
  CacheControl header implementation.
* Removed from interface: Guzzle\Http\ClientInterface::setUriTemplate
* Removed from interface: Guzzle\Http\ClientInterface::setCurlMulti()
* Removed Guzzle\Http\Message\Request::receivedRequestHeader() and implemented this functionality in
  Guzzle\Http\Curl\RequestMediator
* Removed the optional $asString parameter from MessageInterface::getHeader(). Just cast the header to a string.
* Removed the optional $tryChunkedTransfer option from Guzzle\Http\Message\EntityEnclosingRequestInterface
* Removed the $asObjects argument from Guzzle\Http\Message\MessageInterface::getHeaders()
* Removed Guzzle\Parser\ParserRegister::get(). Use getParser()
* Removed Guzzle\Parser\ParserRegister::set(). Use registerParser().
* All response header helper functions return a string rather than mixing Header objects and strings inconsistently
* Removed cURL blacklist support. This is no longer necessary now that Expect, Accept, etc. are managed by Guzzle
  directly via interfaces
* Removed the injecting of a request object onto a response object. The methods to get and set a request still exist
  but are a no-op until removed.
* Most classes that used to require a `Guzzle\Service\Command\CommandInterface` typehint now request a
  `Guzzle\Service\Command\ArrayCommandInterface`.
* Added `Guzzle\Http\Message\RequestInterface::startResponse()` to the RequestInterface to handle injecting a response
  on a request while the request is still being transferred
* The ability to case-insensitively search for header values
* Guzzle\Http\Message\Header::hasExactHeader
* Guzzle\Http\Message\Header::raw. Use getAll()
* Deprecated cache control specific methods on Guzzle\Http\Message\AbstractMessage. Use the CacheControl header object
  instead.
* `Guzzle\Service\Command\CommandInterface` now extends from ToArrayInterface and ArrayAccess
* Added the ability to cast Model objects to a string to view debug information.

## 3.5.0 - 2013-05-13

* Bug: Fixed a regression so that request responses are parsed only once per oncomplete event rather than multiple times
* Bug: Better cleanup of one-time events across the board (when an event is meant to fire once, it will now remove
  itself from the EventDispatcher)
* Bug: `Guzzle\Log\MessageFormatter` now properly writes "total_time" and "connect_time" values
* Bug: Cloning an EntityEnclosingRequest now clones the EntityBody too
* Bug: Fixed an undefined index error when parsing nested JSON responses with a sentAs parameter that reference a
  non-existent key
* Bug: All __call() method arguments are now required (helps with mocking frameworks)
* Deprecating Response::getRequest() and now using a shallow clone of a request object to remove a circular reference
  to help with refcount based garbage collection of resources created by sending a request
* Deprecating ZF1 cache and log adapters. These will be removed in the next major version.
* Deprecating `Response::getPreviousResponse()` (method signature still exists, but it'sdeprecated). Use the
  HistoryPlugin for a history.
* Added a `responseBody` alias for the `response_body` location
* Refactored internals to no longer rely on Response::getRequest()
* HistoryPlugin can now be cast to a string
* HistoryPlugin now logs transactions rather than requests and responses to more accurately keep track of the requests
  and responses that are sent over the wire
* Added `getEffectiveUrl()` and `getRedirectCount()` to Response objects

## 3.4.3 - 2013-04-30

* Bug fix: Fixing bug introduced in 3.4.2 where redirect responses are duplicated on the final redirected response
* Added a check to re-extract the temp cacert bundle from the phar before sending each request

## 3.4.2 - 2013-04-29

* Bug fix: Stream objects now work correctly with "a" and "a+" modes
* Bug fix: Removing `Transfer-Encoding: chunked` header when a Content-Length is present
* Bug fix: AsyncPlugin no longer forces HEAD requests
* Bug fix: DateTime timezones are now properly handled when using the service description schema formatter
* Bug fix: CachePlugin now properly handles stale-if-error directives when a request to the origin server fails
* Setting a response on a request will write to the custom request body from the response body if one is specified
* LogPlugin now writes to php://output when STDERR is undefined
* Added the ability to set multiple POST files for the same key in a single call
* application/x-www-form-urlencoded POSTs now use the utf-8 charset by default
* Added the ability to queue CurlExceptions to the MockPlugin
* Cleaned up how manual responses are queued on requests (removed "queued_response" and now using request.before_send)
* Configuration loading now allows remote files

## 3.4.1 - 2013-04-16

* Large refactoring to how CurlMulti handles work. There is now a proxy that sits in front of a pool of CurlMulti
  handles. This greatly simplifies the implementation, fixes a couple bugs, and provides a small performance boost.
* Exceptions are now properly grouped when sending requests in parallel
* Redirects are now properly aggregated when a multi transaction fails
* Redirects now set the response on the original object even in the event of a failure
* Bug fix: Model names are now properly set even when using $refs
* Added support for PHP 5.5's CurlFile to prevent warnings with the deprecated @ syntax
* Added support for oauth_callback in OAuth signatures
* Added support for oauth_verifier in OAuth signatures
* Added support to attempt to retrieve a command first literally, then ucfirst, the with inflection

## 3.4.0 - 2013-04-11

* Bug fix: URLs are now resolved correctly based on http://tools.ietf.org/html/rfc3986#section-5.2. #289
* Bug fix: Absolute URLs with a path in a service description will now properly override the base URL. #289
* Bug fix: Parsing a query string with a single PHP array value will now result in an array. #263
* Bug fix: Better normalization of the User-Agent header to prevent duplicate headers. #264.
* Bug fix: Added `number` type to service descriptions.
* Bug fix: empty parameters are removed from an OAuth signature
* Bug fix: Revalidating a cache entry prefers the Last-Modified over the Date header
* Bug fix: Fixed "array to string" error when validating a union of types in a service description
* Bug fix: Removed code that attempted to determine the size of a stream when data is written to the stream
* Bug fix: Not including an `oauth_token` if the value is null in the OauthPlugin.
* Bug fix: Now correctly aggregating successful requests and failed requests in CurlMulti when a redirect occurs.
* The new default CURLOPT_TIMEOUT setting has been increased to 150 seconds so that Guzzle works on poor connections.
* Added a feature to EntityEnclosingRequest::setBody() that will automatically set the Content-Type of the request if
  the Content-Type can be determined based on the entity body or the path of the request.
* Added the ability to overwrite configuration settings in a client when grabbing a throwaway client from a builder.
* Added support for a PSR-3 LogAdapter.
* Added a `command.after_prepare` event
* Added `oauth_callback` parameter to the OauthPlugin
* Added the ability to create a custom stream class when using a stream factory
* Added a CachingEntityBody decorator
* Added support for `additionalParameters` in service descriptions to define how custom parameters are serialized.
* The bundled SSL certificate is now provided in the phar file and extracted when running Guzzle from a phar.
* You can now send any EntityEnclosingRequest with POST fields or POST files and cURL will handle creating bodies
* POST requests using a custom entity body are now treated exactly like PUT requests but with a custom cURL method. This
  means that the redirect behavior of POST requests with custom bodies will not be the same as POST requests that use
  POST fields or files (the latter is only used when emulating a form POST in the browser).
* Lots of cleanup to CurlHandle::factory and RequestFactory::createRequest

## 3.3.1 - 2013-03-10

* Added the ability to create PHP streaming responses from HTTP requests
* Bug fix: Running any filters when parsing response headers with service descriptions
* Bug fix: OauthPlugin fixes to allow for multi-dimensional array signing, and sorting parameters before signing
* Bug fix: Removed the adding of default empty arrays and false Booleans to responses in order to be consistent across
  response location visitors.
* Bug fix: Removed the possibility of creating configuration files with circular dependencies
* RequestFactory::create() now uses the key of a POST file when setting the POST file name
* Added xmlAllowEmpty to serialize an XML body even if no XML specific parameters are set

## 3.3.0 - 2013-03-03

* A large number of performance optimizations have been made
* Bug fix: Added 'wb' as a valid write mode for streams
* Bug fix: `Guzzle\Http\Message\Response::json()` now allows scalar values to be returned
* Bug fix: Fixed bug in `Guzzle\Http\Message\Response` where wrapping quotes were stripped from `getEtag()`
* BC: Removed `Guzzle\Http\Utils` class
* BC: Setting a service description on a client will no longer modify the client's command factories.
* BC: Emitting IO events from a RequestMediator is now a parameter that must be set in a request's curl options using
  the 'emit_io' key. This was previously set under a request's parameters using 'curl.emit_io'
* BC: `Guzzle\Stream\Stream::getWrapper()` and `Guzzle\Stream\Stream::getSteamType()` are no longer converted to
  lowercase
* Operation parameter objects are now lazy loaded internally
* Added ErrorResponsePlugin that can throw errors for responses defined in service description operations' errorResponses
* Added support for instantiating responseType=class responseClass classes. Classes must implement
  `Guzzle\Service\Command\ResponseClassInterface`
* Added support for additionalProperties for top-level parameters in responseType=model responseClasses. These
  additional properties also support locations and can be used to parse JSON responses where the outermost part of the
  JSON is an array
* Added support for nested renaming of JSON models (rename sentAs to name)
* CachePlugin
    * Added support for stale-if-error so that the CachePlugin can now serve stale content from the cache on error
    * Debug headers can now added to cached response in the CachePlugin

## 3.2.0 - 2013-02-14

* CurlMulti is no longer reused globally. A new multi object is created per-client. This helps to isolate clients.
* URLs with no path no longer contain a "/" by default
* Guzzle\Http\QueryString does no longer manages the leading "?". This is now handled in Guzzle\Http\Url.
* BadResponseException no longer includes the full request and response message
* Adding setData() to Guzzle\Service\Description\ServiceDescriptionInterface
* Adding getResponseBody() to Guzzle\Http\Message\RequestInterface
* Various updates to classes to use ServiceDescriptionInterface type hints rather than ServiceDescription
* Header values can now be normalized into distinct values when multiple headers are combined with a comma separated list
* xmlEncoding can now be customized for the XML declaration of a XML service description operation
* Guzzle\Http\QueryString now uses Guzzle\Http\QueryAggregator\QueryAggregatorInterface objects to add custom value
  aggregation and no longer uses callbacks
* The URL encoding implementation of Guzzle\Http\QueryString can now be customized
* Bug fix: Filters were not always invoked for array service description parameters
* Bug fix: Redirects now use a target response body rather than a temporary response body
* Bug fix: The default exponential backoff BackoffPlugin was not giving when the request threshold was exceeded
* Bug fix: Guzzle now takes the first found value when grabbing Cache-Control directives

## 3.1.2 - 2013-01-27

* Refactored how operation responses are parsed. Visitors now include a before() method responsible for parsing the
  response body. For example, the XmlVisitor now parses the XML response into an array in the before() method.
* Fixed an issue where cURL would not automatically decompress responses when the Accept-Encoding header was sent
* CURLOPT_SSL_VERIFYHOST is never set to 1 because it is deprecated (see 5e0ff2ef20f839e19d1eeb298f90ba3598784444)
* Fixed a bug where redirect responses were not chained correctly using getPreviousResponse()
* Setting default headers on a client after setting the user-agent will not erase the user-agent setting

## 3.1.1 - 2013-01-20

* Adding wildcard support to Guzzle\Common\Collection::getPath()
* Adding alias support to ServiceBuilder configs
* Adding Guzzle\Service\Resource\CompositeResourceIteratorFactory and cleaning up factory interface

## 3.1.0 - 2013-01-12

* BC: CurlException now extends from RequestException rather than BadResponseException
* BC: Renamed Guzzle\Plugin\Cache\CanCacheStrategyInterface::canCache() to canCacheRequest() and added CanCacheResponse()
* Added getData to ServiceDescriptionInterface
* Added context array to RequestInterface::setState()
* Bug: Removing hard dependency on the BackoffPlugin from Guzzle\Http
* Bug: Adding required content-type when JSON request visitor adds JSON to a command
* Bug: Fixing the serialization of a service description with custom data
* Made it easier to deal with exceptions thrown when transferring commands or requests in parallel by providing
  an array of successful and failed responses
* Moved getPath from Guzzle\Service\Resource\Model to Guzzle\Common\Collection
* Added Guzzle\Http\IoEmittingEntityBody
* Moved command filtration from validators to location visitors
* Added `extends` attributes to service description parameters
* Added getModels to ServiceDescriptionInterface

## 3.0.7 - 2012-12-19

* Fixing phar detection when forcing a cacert to system if null or true
* Allowing filename to be passed to `Guzzle\Http\Message\Request::setResponseBody()`
* Cleaning up `Guzzle\Common\Collection::inject` method
* Adding a response_body location to service descriptions

## 3.0.6 - 2012-12-09

* CurlMulti performance improvements
* Adding setErrorResponses() to Operation
* composer.json tweaks

## 3.0.5 - 2012-11-18

* Bug: Fixing an infinite recursion bug caused from revalidating with the CachePlugin
* Bug: Response body can now be a string containing "0"
* Bug: Using Guzzle inside of a phar uses system by default but now allows for a custom cacert
* Bug: QueryString::fromString now properly parses query string parameters that contain equal signs
* Added support for XML attributes in service description responses
* DefaultRequestSerializer now supports array URI parameter values for URI template expansion
* Added better mimetype guessing to requests and post files

## 3.0.4 - 2012-11-11

* Bug: Fixed a bug when adding multiple cookies to a request to use the correct glue value
* Bug: Cookies can now be added that have a name, domain, or value set to "0"
* Bug: Using the system cacert bundle when using the Phar
* Added json and xml methods to Response to make it easier to parse JSON and XML response data into data structures
* Enhanced cookie jar de-duplication
* Added the ability to enable strict cookie jars that throw exceptions when invalid cookies are added
* Added setStream to StreamInterface to actually make it possible to implement custom rewind behavior for entity bodies
* Added the ability to create any sort of hash for a stream rather than just an MD5 hash

## 3.0.3 - 2012-11-04

* Implementing redirects in PHP rather than cURL
* Added PECL URI template extension and using as default parser if available
* Bug: Fixed Content-Length parsing of Response factory
* Adding rewind() method to entity bodies and streams. Allows for custom rewinding of non-repeatable streams.
* Adding ToArrayInterface throughout library
* Fixing OauthPlugin to create unique nonce values per request

## 3.0.2 - 2012-10-25

* Magic methods are enabled by default on clients
* Magic methods return the result of a command
* Service clients no longer require a base_url option in the factory
* Bug: Fixed an issue with URI templates where null template variables were being expanded

## 3.0.1 - 2012-10-22

* Models can now be used like regular collection objects by calling filter, map, etc.
* Models no longer require a Parameter structure or initial data in the constructor
* Added a custom AppendIterator to get around a PHP bug with the `\AppendIterator`

## 3.0.0 - 2012-10-15

* Rewrote service description format to be based on Swagger
    * Now based on JSON schema
    * Added nested input structures and nested response models
    * Support for JSON and XML input and output models
    * Renamed `commands` to `operations`
    * Removed dot class notation
    * Removed custom types
* Broke the project into smaller top-level namespaces to be more component friendly
* Removed support for XML configs and descriptions. Use arrays or JSON files.
* Removed the Validation component and Inspector
* Moved all cookie code to Guzzle\Plugin\Cookie
* Magic methods on a Guzzle\Service\Client now return the command un-executed.
* Calling getResult() or getResponse() on a command will lazily execute the command if needed.
* Now shipping with cURL's CA certs and using it by default
* Added previousResponse() method to response objects
* No longer sending Accept and Accept-Encoding headers on every request
* Only sending an Expect header by default when a payload is greater than 1MB
* Added/moved client options:
    * curl.blacklist to curl.option.blacklist
    * Added ssl.certificate_authority
* Added a Guzzle\Iterator component
* Moved plugins from Guzzle\Http\Plugin to Guzzle\Plugin
* Added a more robust backoff retry strategy (replaced the ExponentialBackoffPlugin)
* Added a more robust caching plugin
* Added setBody to response objects
* Updating LogPlugin to use a more flexible MessageFormatter
* Added a completely revamped build process
* Cleaning up Collection class and removing default values from the get method
* Fixed ZF2 cache adapters

## 2.8.8 - 2012-10-15

* Bug: Fixed a cookie issue that caused dot prefixed domains to not match where popular browsers did

## 2.8.7 - 2012-09-30

* Bug: Fixed config file aliases for JSON includes
* Bug: Fixed cookie bug on a request object by using CookieParser to parse cookies on requests
* Bug: Removing the path to a file when sending a Content-Disposition header on a POST upload
* Bug: Hardening request and response parsing to account for missing parts
* Bug: Fixed PEAR packaging
* Bug: Fixed Request::getInfo
* Bug: Fixed cases where CURLM_CALL_MULTI_PERFORM return codes were causing curl transactions to fail
* Adding the ability for the namespace Iterator factory to look in multiple directories
* Added more getters/setters/removers from service descriptions
* Added the ability to remove POST fields from OAuth signatures
* OAuth plugin now supports 2-legged OAuth

## 2.8.6 - 2012-09-05

* Added the ability to modify and build service descriptions
* Added the use of visitors to apply parameters to locations in service descriptions using the dynamic command
* Added a `json` parameter location
* Now allowing dot notation for classes in the CacheAdapterFactory
* Using the union of two arrays rather than an array_merge when extending service builder services and service params
* Ensuring that a service is a string before doing strpos() checks on it when substituting services for references
  in service builder config files.
* Services defined in two different config files that include one another will by default replace the previously
  defined service, but you can now create services that extend themselves and merge their settings over the previous
* The JsonLoader now supports aliasing filenames with different filenames. This allows you to alias something like
  '_default' with a default JSON configuration file.

## 2.8.5 - 2012-08-29

* Bug: Suppressed empty arrays from URI templates
* Bug: Added the missing $options argument from ServiceDescription::factory to enable caching
* Added support for HTTP responses that do not contain a reason phrase in the start-line
* AbstractCommand commands are now invokable
* Added a way to get the data used when signing an Oauth request before a request is sent

## 2.8.4 - 2012-08-15

* Bug: Custom delay time calculations are no longer ignored in the ExponentialBackoffPlugin
* Added the ability to transfer entity bodies as a string rather than streamed. This gets around curl error 65. Set `body_as_string` in a request's curl options to enable.
* Added a StreamInterface, EntityBodyInterface, and added ftell() to Guzzle\Common\Stream
* Added an AbstractEntityBodyDecorator and a ReadLimitEntityBody decorator to transfer only a subset of a decorated stream
* Stream and EntityBody objects will now return the file position to the previous position after a read required operation (e.g. getContentMd5())
* Added additional response status codes
* Removed SSL information from the default User-Agent header
* DELETE requests can now send an entity body
* Added an EventDispatcher to the ExponentialBackoffPlugin and added an ExponentialBackoffLogger to log backoff retries
* Added the ability of the MockPlugin to consume mocked request bodies
* LogPlugin now exposes request and response objects in the extras array

## 2.8.3 - 2012-07-30

* Bug: Fixed a case where empty POST requests were sent as GET requests
* Bug: Fixed a bug in ExponentialBackoffPlugin that caused fatal errors when retrying an EntityEnclosingRequest that does not have a body
* Bug: Setting the response body of a request to null after completing a request, not when setting the state of a request to new
* Added multiple inheritance to service description commands
* Added an ApiCommandInterface and added `getParamNames()` and `hasParam()`
* Removed the default 2mb size cutoff from the Md5ValidatorPlugin so that it now defaults to validating everything
* Changed CurlMulti::perform to pass a smaller timeout to CurlMulti::executeHandles

## 2.8.2 - 2012-07-24

* Bug: Query string values set to 0 are no longer dropped from the query string
* Bug: A Collection object is no longer created each time a call is made to `Guzzle\Service\Command\AbstractCommand::getRequestHeaders()`
* Bug: `+` is now treated as an encoded space when parsing query strings
* QueryString and Collection performance improvements
* Allowing dot notation for class paths in filters attribute of a service descriptions

## 2.8.1 - 2012-07-16

* Loosening Event Dispatcher dependency
* POST redirects can now be customized using CURLOPT_POSTREDIR

## 2.8.0 - 2012-07-15

* BC: Guzzle\Http\Query
    * Query strings with empty variables will always show an equal sign unless the variable is set to QueryString::BLANK (e.g. ?acl= vs ?acl)
    * Changed isEncodingValues() and isEncodingFields() to isUrlEncoding()
    * Changed setEncodeValues(bool) and setEncodeFields(bool) to useUrlEncoding(bool)
    * Changed the aggregation functions of QueryString to be static methods
    * Can now use fromString() with querystrings that have a leading ?
* cURL configuration values can be specified in service descriptions using `curl.` prefixed parameters
* Content-Length is set to 0 before emitting the request.before_send event when sending an empty request body
* Cookies are no longer URL decoded by default
* Bug: URI template variables set to null are no longer expanded

## 2.7.2 - 2012-07-02

* BC: Moving things to get ready for subtree splits. Moving Inflection into Common. Moving Guzzle\Http\Parser to Guzzle\Parser.
* BC: Removing Guzzle\Common\Batch\Batch::count() and replacing it with isEmpty()
* CachePlugin now allows for a custom request parameter function to check if a request can be cached
* Bug fix: CachePlugin now only caches GET and HEAD requests by default
* Bug fix: Using header glue when transferring headers over the wire
* Allowing deeply nested arrays for composite variables in URI templates
* Batch divisors can now return iterators or arrays

## 2.7.1 - 2012-06-26

* Minor patch to update version number in UA string
* Updating build process

## 2.7.0 - 2012-06-25

* BC: Inflection classes moved to Guzzle\Inflection. No longer static methods. Can now inject custom inflectors into classes.
* BC: Removed magic setX methods from commands
* BC: Magic methods mapped to service description commands are now inflected in the command factory rather than the client __call() method
* Verbose cURL options are no longer enabled by default. Set curl.debug to true on a client to enable.
* Bug: Now allowing colons in a response start-line (e.g. HTTP/1.1 503 Service Unavailable: Back-end server is at capacity)
* Guzzle\Service\Resource\ResourceIteratorApplyBatched now internally uses the Guzzle\Common\Batch namespace
* Added Guzzle\Service\Plugin namespace and a PluginCollectionPlugin
* Added the ability to set POST fields and files in a service description
* Guzzle\Http\EntityBody::factory() now accepts objects with a __toString() method
* Adding a command.before_prepare event to clients
* Added BatchClosureTransfer and BatchClosureDivisor
* BatchTransferException now includes references to the batch divisor and transfer strategies
* Fixed some tests so that they pass more reliably
* Added Guzzle\Common\Log\ArrayLogAdapter

## 2.6.6 - 2012-06-10

* BC: Removing Guzzle\Http\Plugin\BatchQueuePlugin
* BC: Removing Guzzle\Service\Command\CommandSet
* Adding generic batching system (replaces the batch queue plugin and command set)
* Updating ZF cache and log adapters and now using ZF's composer repository
* Bug: Setting the name of each ApiParam when creating through an ApiCommand
* Adding result_type, result_doc, deprecated, and doc_url to service descriptions
* Bug: Changed the default cookie header casing back to 'Cookie'

## 2.6.5 - 2012-06-03

* BC: Renaming Guzzle\Http\Message\RequestInterface::getResourceUri() to getResource()
* BC: Removing unused AUTH_BASIC and AUTH_DIGEST constants from
* BC: Guzzle\Http\Cookie is now used to manage Set-Cookie data, not Cookie data
* BC: Renaming methods in the CookieJarInterface
* Moving almost all cookie logic out of the CookiePlugin and into the Cookie or CookieJar implementations
* Making the default glue for HTTP headers ';' instead of ','
* Adding a removeValue to Guzzle\Http\Message\Header
* Adding getCookies() to request interface.
* Making it easier to add event subscribers to HasDispatcherInterface classes. Can now directly call addSubscriber()

## 2.6.4 - 2012-05-30

* BC: Cleaning up how POST files are stored in EntityEnclosingRequest objects. Adding PostFile class.
* BC: Moving ApiCommand specific functionality from the Inspector and on to the ApiCommand
* Bug: Fixing magic method command calls on clients
* Bug: Email constraint only validates strings
* Bug: Aggregate POST fields when POST files are present in curl handle
* Bug: Fixing default User-Agent header
* Bug: Only appending or prepending parameters in commands if they are specified
* Bug: Not requiring response reason phrases or status codes to match a predefined list of codes
* Allowing the use of dot notation for class namespaces when using instance_of constraint
* Added any_match validation constraint
* Added an AsyncPlugin
* Passing request object to the calculateWait method of the ExponentialBackoffPlugin
* Allowing the result of a command object to be changed
* Parsing location and type sub values when instantiating a service description rather than over and over at runtime

## 2.6.3 - 2012-05-23

* [BC] Guzzle\Common\FromConfigInterface no longer requires any config options.
* [BC] Refactoring how POST files are stored on an EntityEnclosingRequest. They are now separate from POST fields.
* You can now use an array of data when creating PUT request bodies in the request factory.
* Removing the requirement that HTTPS requests needed a Cache-Control: public directive to be cacheable.
* [Http] Adding support for Content-Type in multipart POST uploads per upload
* [Http] Added support for uploading multiple files using the same name (foo[0], foo[1])
* Adding more POST data operations for easier manipulation of POST data.
* You can now set empty POST fields.
* The body of a request is only shown on EntityEnclosingRequest objects that do not use POST files.
* Split the Guzzle\Service\Inspector::validateConfig method into two methods. One to initialize when a command is created, and one to validate.
* CS updates

## 2.6.2 - 2012-05-19

* [Http] Better handling of nested scope requests in CurlMulti.  Requests are now always prepares in the send() method rather than the addRequest() method.

## 2.6.1 - 2012-05-19

* [BC] Removing 'path' support in service descriptions.  Use 'uri'.
* [BC] Guzzle\Service\Inspector::parseDocBlock is now protected. Adding getApiParamsForClass() with cache.
* [BC] Removing Guzzle\Common\NullObject.  Use https://github.com/mtdowling/NullObject if you need it.
* [BC] Removing Guzzle\Common\XmlElement.
* All commands, both dynamic and concrete, have ApiCommand objects.
* Adding a fix for CurlMulti so that if all of the connections encounter some sort of curl error, then the loop exits.
* Adding checks to EntityEnclosingRequest so that empty POST files and fields are ignored.
* Making the method signature of Guzzle\Service\Builder\ServiceBuilder::factory more flexible.

## 2.6.0 - 2012-05-15

* [BC] Moving Guzzle\Service\Builder to Guzzle\Service\Builder\ServiceBuilder
* [BC] Executing a Command returns the result of the command rather than the command
* [BC] Moving all HTTP parsing logic to Guzzle\Http\Parsers. Allows for faster C implementations if needed.
* [BC] Changing the Guzzle\Http\Message\Response::setProtocol() method to accept a protocol and version in separate args.
* [BC] Moving ResourceIterator* to Guzzle\Service\Resource
* [BC] Completely refactored ResourceIterators to iterate over a cloned command object
* [BC] Moved Guzzle\Http\UriTemplate to Guzzle\Http\Parser\UriTemplate\UriTemplate
* [BC] Guzzle\Guzzle is now deprecated
* Moving Guzzle\Common\Guzzle::inject to Guzzle\Common\Collection::inject
* Adding Guzzle\Version class to give version information about Guzzle
* Adding Guzzle\Http\Utils class to provide getDefaultUserAgent() and getHttpDate()
* Adding Guzzle\Curl\CurlVersion to manage caching curl_version() data
* ServiceDescription and ServiceBuilder are now cacheable using similar configs
* Changing the format of XML and JSON service builder configs.  Backwards compatible.
* Cleaned up Cookie parsing
* Trimming the default Guzzle User-Agent header
* Adding a setOnComplete() method to Commands that is called when a command completes
* Keeping track of requests that were mocked in the MockPlugin
* Fixed a caching bug in the CacheAdapterFactory
* Inspector objects can be injected into a Command object
* Refactoring a lot of code and tests to be case insensitive when dealing with headers
* Adding Guzzle\Http\Message\HeaderComparison for easy comparison of HTTP headers using a DSL
* Adding the ability to set global option overrides to service builder configs
* Adding the ability to include other service builder config files from within XML and JSON files
* Moving the parseQuery method out of Url and on to QueryString::fromString() as a static factory method.

## 2.5.0 - 2012-05-08

* Major performance improvements
* [BC] Simplifying Guzzle\Common\Collection.  Please check to see if you are using features that are now deprecated.
* [BC] Using a custom validation system that allows a flyweight implementation for much faster validation. No longer using Symfony2 Validation component.
* [BC] No longer supporting "{{ }}" for injecting into command or UriTemplates.  Use "{}"
* Added the ability to passed parameters to all requests created by a client
* Added callback functionality to the ExponentialBackoffPlugin
* Using microtime in ExponentialBackoffPlugin to allow more granular backoff strategies.
* Rewinding request stream bodies when retrying requests
* Exception is thrown when JSON response body cannot be decoded
* Added configurable magic method calls to clients and commands.  This is off by default.
* Fixed a defect that added a hash to every parsed URL part
* Fixed duplicate none generation for OauthPlugin.
* Emitting an event each time a client is generated by a ServiceBuilder
* Using an ApiParams object instead of a Collection for parameters of an ApiCommand
* cache.* request parameters should be renamed to params.cache.*
* Added the ability to set arbitrary curl options on requests (disable_wire, progress, etc.). See CurlHandle.
* Added the ability to disable type validation of service descriptions
* ServiceDescriptions and ServiceBuilders are now Serializable
{
    "name": "guzzlehttp/guzzle",
    "type": "library",
    "description": "Guzzle is a PHP HTTP client library",
    "keywords": ["framework", "http", "rest", "web service", "curl", "client", "HTTP client"],
    "homepage": "http://guzzlephp.org/",
    "license": "MIT",
    "authors": [
        {
            "name": "Michael Dowling",
            "email": "mtdowling@gmail.com",
            "homepage": "https://github.com/mtdowling"
        }
    ],
    "require": {
        "php": ">=5.5.0",
        "guzzlehttp/psr7": "~1.1",
        "guzzlehttp/promises": "~1.0"
    },
    "require-dev": {
        "ext-curl": "*",
        "phpunit/phpunit": "~4.0",
        "psr/log": "~1.0"
    },
    "autoload": {
        "files": ["src/functions_include.php"],
        "psr-4": {
            "GuzzleHttp\\": "src/"
        }
    },
    "autoload-dev": {
        "psr-4": {
            "GuzzleHttp\\Tests\\": "tests/"
        }
    },
    "extra": {
        "branch-alias": {
            "dev-master": "6.1-dev"
        }
    }
}
Copyright (c) 2011-2015 Michael Dowling, https://github.com/mtdowling <mtdowling@gmail.com>

Permission is hereby granted, free of charge, to any person obtaining a copy
of this software and associated documentation files (the "Software"), to deal
in the Software without restriction, including without limitation the rights
to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
copies of the Software, and to permit persons to whom the Software is
furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in
all copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
THE SOFTWARE.
Guzzle, PHP HTTP client
=======================

[![Build Status](https://secure.travis-ci.org/guzzle/guzzle.svg?branch=master)](http://travis-ci.org/guzzle/guzzle)

Guzzle is a PHP HTTP client that makes it easy to send HTTP requests and
trivial to integrate with web services.

- Simple interface for building query strings, POST requests, streaming large
  uploads, streaming large downloads, using HTTP cookies, uploading JSON data,
  etc...
- Can send both synchronous and asynchronous requests using the same interface.
- Uses PSR-7 interfaces for requests, responses, and streams. This allows you
  to utilize other PSR-7 compatible libraries with Guzzle.
- Abstracts away the underlying HTTP transport, allowing you to write
  environment and transport agnostic code; i.e., no hard dependency on cURL,
  PHP streams, sockets, or non-blocking event loops.
- Middleware system allows you to augment and compose client behavior.

```php
$client = new GuzzleHttp\Client();
$res = $client->request('GET', 'https://api.github.com/user', [
    'auth' => ['user', 'pass']
]);
echo $res->getStatusCode();
// 200
echo $res->getHeader('content-type');
// 'application/json; charset=utf8'
echo $res->getBody();
// {"type":"User"...'

// Send an asynchronous request.
$request = new \GuzzleHttp\Psr7\Request('GET', 'http://httpbin.org');
$promise = $client->sendAsync($request)->then(function ($response) {
    echo 'I completed! ' . $response->getBody();
});
$promise->wait();
```

## Help and docs

- [Documentation](http://guzzlephp.org/)
- [stackoverflow](http://stackoverflow.com/questions/tagged/guzzle)
- [Gitter](https://gitter.im/guzzle/guzzle)


## Installing Guzzle

The recommended way to install Guzzle is through
[Composer](http://getcomposer.org).

```bash
# Install Composer
curl -sS https://getcomposer.org/installer | php
```

Next, run the Composer command to install the latest stable version of Guzzle:

```bash
composer.phar require guzzlehttp/guzzle
```

After installing, you need to require Composer's autoloader:

```php
require 'vendor/autoload.php';
```

You can then later update Guzzle using composer:

 ```bash
composer.phar update
 ```


## Version Guidance

| Version | Status      | Packagist           | Namespace    | Repo                | Docs                | PSR-7 |
|---------|-------------|---------------------|--------------|---------------------|---------------------|-------|
| 3.x     | EOL         | `guzzle/guzzle`     | `Guzzle`     | [v3][guzzle-3-repo] | [v3][guzzle-3-docs] | No    |
| 4.x     | EOL         | `guzzlehttp/guzzle` | `GuzzleHttp` | N/A                 | N/A                 | No    |
| 5.x     | Maintained  | `guzzlehttp/guzzle` | `GuzzleHttp` | [v5][guzzle-5-repo] | [v5][guzzle-5-docs] | No    |
| 6.x     | Latest      | `guzzlehttp/guzzle` | `GuzzleHttp` | [v6][guzzle-6-repo] | [v6][guzzle-6-docs] | Yes   |

[guzzle-3-repo]: https://github.com/guzzle/guzzle3
[guzzle-5-repo]: https://github.com/guzzle/guzzle/tree/5.3
[guzzle-6-repo]: https://github.com/guzzle/guzzle
[guzzle-3-docs]: http://guzzle3.readthedocs.org/en/latest/
[guzzle-5-docs]: http://guzzle.readthedocs.org/en/5.3/
[guzzle-6-docs]: http://guzzle.readthedocs.org/en/latest/
<?php
namespace GuzzleHttp;

use GuzzleHttp\Cookie\CookieJar;
use GuzzleHttp\Promise;
use GuzzleHttp\Psr7;
use Psr\Http\Message\UriInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * @method ResponseInterface get($uri, array $options = [])
 * @method ResponseInterface head($uri, array $options = [])
 * @method ResponseInterface put($uri, array $options = [])
 * @method ResponseInterface post($uri, array $options = [])
 * @method ResponseInterface patch($uri, array $options = [])
 * @method ResponseInterface delete($uri, array $options = [])
 * @method Promise\PromiseInterface getAsync($uri, array $options = [])
 * @method Promise\PromiseInterface headAsync($uri, array $options = [])
 * @method Promise\PromiseInterface putAsync($uri, array $options = [])
 * @method Promise\PromiseInterface postAsync($uri, array $options = [])
 * @method Promise\PromiseInterface patchAsync($uri, array $options = [])
 * @method Promise\PromiseInterface deleteAsync($uri, array $options = [])
 */
class Client implements ClientInterface
{
    /** @var array Default request options */
    private $config;

    /**
     * Clients accept an array of constructor parameters.
     *
     * Here's an example of creating a client using a base_uri and an array of
     * default request options to apply to each request:
     *
     *     $client = new Client([
     *         'base_uri'        => 'http://www.foo.com/1.0/',
     *         'timeout'         => 0,
     *         'allow_redirects' => false,
     *         'proxy'           => '192.168.16.1:10'
     *     ]);
     *
     * Client configuration settings include the following options:
     *
     * - handler: (callable) Function that transfers HTTP requests over the
     *   wire. The function is called with a Psr7\Http\Message\RequestInterface
     *   and array of transfer options, and must return a
     *   GuzzleHttp\Promise\PromiseInterface that is fulfilled with a
     *   Psr7\Http\Message\ResponseInterface on success. "handler" is a
     *   constructor only option that cannot be overridden in per/request
     *   options. If no handler is provided, a default handler will be created
     *   that enables all of the request options below by attaching all of the
     *   default middleware to the handler.
     * - base_uri: (string|UriInterface) Base URI of the client that is merged
     *   into relative URIs. Can be a string or instance of UriInterface.
     * - **: any request option
     *
     * @param array $config Client configuration settings.
     *
     * @see \GuzzleHttp\RequestOptions for a list of available request options.
     */
    public function __construct(array $config = [])
    {
        if (!isset($config['handler'])) {
            $config['handler'] = HandlerStack::create();
        }

        // Convert the base_uri to a UriInterface
        if (isset($config['base_uri'])) {
            $config['base_uri'] = Psr7\uri_for($config['base_uri']);
        }

        $this->configureDefaults($config);
    }

    public function __call($method, $args)
    {
        if (count($args) < 1) {
            throw new \InvalidArgumentException('Magic request methods require a URI and optional options array');
        }

        $uri = $args[0];
        $opts = isset($args[1]) ? $args[1] : [];

        return substr($method, -5) === 'Async'
            ? $this->requestAsync(substr($method, 0, -5), $uri, $opts)
            : $this->request($method, $uri, $opts);
    }

    public function sendAsync(RequestInterface $request, array $options = [])
    {
        // Merge the base URI into the request URI if needed.
        $options = $this->prepareDefaults($options);

        return $this->transfer(
            $request->withUri($this->buildUri($request->getUri(), $options)),
            $options
        );
    }

    public function send(RequestInterface $request, array $options = [])
    {
        $options[RequestOptions::SYNCHRONOUS] = true;
        return $this->sendAsync($request, $options)->wait();
    }

    public function requestAsync($method, $uri = null, array $options = [])
    {
        $options = $this->prepareDefaults($options);
        // Remove request modifying parameter because it can be done up-front.
        $headers = isset($options['headers']) ? $options['headers'] : [];
        $body = isset($options['body']) ? $options['body'] : null;
        $version = isset($options['version']) ? $options['version'] : '1.1';
        // Merge the URI into the base URI.
        $uri = $this->buildUri($uri, $options);
        if (is_array($body)) {
            $this->invalidBody();
        }
        $request = new Psr7\Request($method, $uri, $headers, $body, $version);
        // Remove the option so that they are not doubly-applied.
        unset($options['headers'], $options['body'], $options['version']);

        return $this->transfer($request, $options);
    }

    public function request($method, $uri = null, array $options = [])
    {
        $options[RequestOptions::SYNCHRONOUS] = true;
        return $this->requestAsync($method, $uri, $options)->wait();
    }

    public function getConfig($option = null)
    {
        return $option === null
            ? $this->config
            : (isset($this->config[$option]) ? $this->config[$option] : null);
    }

    private function buildUri($uri, array $config)
    {
        if (!isset($config['base_uri'])) {
            return $uri instanceof UriInterface ? $uri : new Psr7\Uri($uri);
        }

        return Psr7\Uri::resolve(Psr7\uri_for($config['base_uri']), $uri);
    }

    /**
     * Configures the default options for a client.
     *
     * @param array $config
     */
    private function configureDefaults(array $config)
    {
        $defaults = [
            'allow_redirects' => RedirectMiddleware::$defaultSettings,
            'http_errors'     => true,
            'decode_content'  => true,
            'verify'          => true,
            'cookies'         => false
        ];

        // Use the standard Linux HTTP_PROXY and HTTPS_PROXY if set
        if ($proxy = getenv('HTTP_PROXY')) {
            $defaults['proxy']['http'] = $proxy;
        }

        if ($proxy = getenv('HTTPS_PROXY')) {
            $defaults['proxy']['https'] = $proxy;
        }

        if ($noProxy = getenv('NO_PROXY')) {
            $cleanedNoProxy = str_replace(' ', '', $noProxy);
            $defaults['proxy']['no'] = explode(',', $cleanedNoProxy);
        }
        
        $this->config = $config + $defaults;

        if (!empty($config['cookies']) && $config['cookies'] === true) {
            $this->config['cookies'] = new CookieJar();
        }

        // Add the default user-agent header.
        if (!isset($this->config['headers'])) {
            $this->config['headers'] = ['User-Agent' => default_user_agent()];
        } else {
            // Add the User-Agent header if one was not already set.
            foreach (array_keys($this->config['headers']) as $name) {
                if (strtolower($name) === 'user-agent') {
                    return;
                }
            }
            $this->config['headers']['User-Agent'] = default_user_agent();
        }
    }

    /**
     * Merges default options into the array.
     *
     * @param array $options Options to modify by reference
     *
     * @return array
     */
    private function prepareDefaults($options)
    {
        $defaults = $this->config;

        if (!empty($defaults['headers'])) {
            // Default headers are only added if they are not present.
            $defaults['_conditional'] = $defaults['headers'];
            unset($defaults['headers']);
        }

        // Special handling for headers is required as they are added as
        // conditional headers and as headers passed to a request ctor.
        if (array_key_exists('headers', $options)) {
            // Allows default headers to be unset.
            if ($options['headers'] === null) {
                $defaults['_conditional'] = null;
                unset($options['headers']);
            } elseif (!is_array($options['headers'])) {
                throw new \InvalidArgumentException('headers must be an array');
            }
        }

        // Shallow merge defaults underneath options.
        $result = $options + $defaults;

        // Remove null values.
        foreach ($result as $k => $v) {
            if ($v === null) {
                unset($result[$k]);
            }
        }

        return $result;
    }

    /**
     * Transfers the given request and applies request options.
     *
     * The URI of the request is not modified and the request options are used
     * as-is without merging in default options.
     *
     * @param RequestInterface $request
     * @param array            $options
     *
     * @return Promise\PromiseInterface
     */
    private function transfer(RequestInterface $request, array $options)
    {
        // save_to -> sink
        if (isset($options['save_to'])) {
            $options['sink'] = $options['save_to'];
            unset($options['save_to']);
        }

        // exceptions -> http_error
        if (isset($options['exceptions'])) {
            $options['http_errors'] = $options['exceptions'];
            unset($options['exceptions']);
        }

        $request = $this->applyOptions($request, $options);
        $handler = $options['handler'];

        try {
            return Promise\promise_for($handler($request, $options));
        } catch (\Exception $e) {
            return Promise\rejection_for($e);
        }
    }

    /**
     * Applies the array of request options to a request.
     *
     * @param RequestInterface $request
     * @param array            $options
     *
     * @return RequestInterface
     */
    private function applyOptions(RequestInterface $request, array &$options)
    {
        $modify = [];

        if (isset($options['form_params'])) {
            if (isset($options['multipart'])) {
                throw new \InvalidArgumentException('You cannot use '
                    . 'form_params and multipart at the same time. Use the '
                    . 'form_params option if you want to send application/'
                    . 'x-www-form-urlencoded requests, and the multipart '
                    . 'option to send multipart/form-data requests.');
            }
            $options['body'] = http_build_query($options['form_params'], null, '&');
            unset($options['form_params']);
            $options['_conditional']['Content-Type'] = 'application/x-www-form-urlencoded';
        }

        if (isset($options['multipart'])) {
            $elements = $options['multipart'];
            unset($options['multipart']);
            $options['body'] = new Psr7\MultipartStream($elements);
        }

        if (!empty($options['decode_content'])
            && $options['decode_content'] !== true
        ) {
            $modify['set_headers']['Accept-Encoding'] = $options['decode_content'];
        }

        if (isset($options['headers'])) {
            if (isset($modify['set_headers'])) {
                $modify['set_headers'] = $options['headers'] + $modify['set_headers'];
            } else {
                $modify['set_headers'] = $options['headers'];
            }
            unset($options['headers']);
        }

        if (isset($options['body'])) {
            if (is_array($options['body'])) {
                $this->invalidBody();
            }
            $modify['body'] = Psr7\stream_for($options['body']);
            unset($options['body']);
        }

        if (!empty($options['auth'])) {
            $value = $options['auth'];
            $type = is_array($value)
                ? (isset($value[2]) ? strtolower($value[2]) : 'basic')
                : $value;
            $config['auth'] = $value;
            switch (strtolower($type)) {
                case 'basic':
                    $modify['set_headers']['Authorization'] = 'Basic '
                        . base64_encode("$value[0]:$value[1]");
                    break;
                case 'digest':
                    // @todo: Do not rely on curl
                    $options['curl'][CURLOPT_HTTPAUTH] = CURLAUTH_DIGEST;
                    $options['curl'][CURLOPT_USERPWD] = "$value[0]:$value[1]";
                    break;
            }
        }

        if (isset($options['query'])) {
            $value = $options['query'];
            if (is_array($value)) {
                $value = http_build_query($value, null, '&', PHP_QUERY_RFC3986);
            }
            if (!is_string($value)) {
                throw new \InvalidArgumentException('query must be a string or array');
            }
            $modify['query'] = $value;
            unset($options['query']);
        }

        if (isset($options['json'])) {
            $modify['body'] = Psr7\stream_for(json_encode($options['json']));
            $options['_conditional']['Content-Type'] = 'application/json';
            unset($options['json']);
        }

        $request = Psr7\modify_request($request, $modify);
        if ($request->getBody() instanceof Psr7\MultipartStream) {
            // Use a multipart/form-data POST if a Content-Type is not set.
            $options['_conditional']['Content-Type'] = 'multipart/form-data; boundary='
                . $request->getBody()->getBoundary();
        }

        // Merge in conditional headers if they are not present.
        if (isset($options['_conditional'])) {
            // Build up the changes so it's in a single clone of the message.
            $modify = [];
            foreach ($options['_conditional'] as $k => $v) {
                if (!$request->hasHeader($k)) {
                    $modify['set_headers'][$k] = $v;
                }
            }
            $request = Psr7\modify_request($request, $modify);
            // Don't pass this internal value along to middleware/handlers.
            unset($options['_conditional']);
        }

        return $request;
    }

    private function invalidBody()
    {
        throw new \InvalidArgumentException('Passing in the "body" request '
            . 'option as an array to send a POST request has been deprecated. '
            . 'Please use the "form_params" request option to send a '
            . 'application/x-www-form-urlencoded request, or a the "multipart" '
            . 'request option to send a multipart/form-data request.');
    }
}
<?php
namespace GuzzleHttp;

use GuzzleHttp\Promise\PromiseInterface;
use GuzzleHttp\Exception\GuzzleException;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\UriInterface;

/**
 * Client interface for sending HTTP requests.
 */
interface ClientInterface
{
    const VERSION = '6.1.1';

    /**
     * Send an HTTP request.
     *
     * @param RequestInterface $request Request to send
     * @param array            $options Request options to apply to the given
     *                                  request and to the transfer.
     *
     * @return ResponseInterface
     * @throws GuzzleException
     */
    public function send(RequestInterface $request, array $options = []);

    /**
     * Asynchronously send an HTTP request.
     *
     * @param RequestInterface $request Request to send
     * @param array            $options Request options to apply to the given
     *                                  request and to the transfer.
     *
     * @return PromiseInterface
     */
    public function sendAsync(RequestInterface $request, array $options = []);

    /**
     * Create and send an HTTP request.
     *
     * Use an absolute path to override the base path of the client, or a
     * relative path to append to the base path of the client. The URL can
     * contain the query string as well.
     *
     * @param string              $method  HTTP method
     * @param string|UriInterface $uri     URI object or string.
     * @param array               $options Request options to apply.
     *
     * @return ResponseInterface
     * @throws GuzzleException
     */
    public function request($method, $uri, array $options = []);

    /**
     * Create and send an asynchronous HTTP request.
     *
     * Use an absolute path to override the base path of the client, or a
     * relative path to append to the base path of the client. The URL can
     * contain the query string as well. Use an array to provide a URL
     * template and additional variables to use in the URL template expansion.
     *
     * @param string              $method  HTTP method
     * @param string|UriInterface $uri     URI object or string.
     * @param array               $options Request options to apply.
     *
     * @return PromiseInterface
     */
    public function requestAsync($method, $uri, array $options = []);

    /**
     * Get a client configuration option.
     *
     * These options include default request options of the client, a "handler"
     * (if utilized by the concrete client), and a "base_uri" if utilized by
     * the concrete client.
     *
     * @param string|null $option The config option to retrieve.
     *
     * @return mixed
     */
    public function getConfig($option = null);
}
<?php
namespace GuzzleHttp\Cookie;

use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * Cookie jar that stores cookies an an array
 */
class CookieJar implements CookieJarInterface
{
    /** @var SetCookie[] Loaded cookie data */
    private $cookies = [];

    /** @var bool */
    private $strictMode;

    /**
     * @param bool $strictMode   Set to true to throw exceptions when invalid
     *                           cookies are added to the cookie jar.
     * @param array $cookieArray Array of SetCookie objects or a hash of
     *                           arrays that can be used with the SetCookie
     *                           constructor
     */
    public function __construct($strictMode = false, $cookieArray = [])
    {
        $this->strictMode = $strictMode;

        foreach ($cookieArray as $cookie) {
            if (!($cookie instanceof SetCookie)) {
                $cookie = new SetCookie($cookie);
            }
            $this->setCookie($cookie);
        }
    }

    /**
     * Create a new Cookie jar from an associative array and domain.
     *
     * @param array  $cookies Cookies to create the jar from
     * @param string $domain  Domain to set the cookies to
     *
     * @return self
     */
    public static function fromArray(array $cookies, $domain)
    {
        $cookieJar = new self();
        foreach ($cookies as $name => $value) {
            $cookieJar->setCookie(new SetCookie([
                'Domain'  => $domain,
                'Name'    => $name,
                'Value'   => $value,
                'Discard' => true
            ]));
        }

        return $cookieJar;
    }

    /**
     * Quote the cookie value if it is not already quoted and it contains
     * problematic characters.
     *
     * @param string $value Value that may or may not need to be quoted
     *
     * @return string
     */
    public static function getCookieValue($value)
    {
        if (substr($value, 0, 1) !== '"' &&
            substr($value, -1, 1) !== '"' &&
            strpbrk($value, ';,=')
        ) {
            $value = '"' . $value . '"';
        }

        return $value;
    }

    /**
     * Evaluate if this cookie should be persisted to storage
     * that survives between requests.
     *
     * @param SetCookie $cookie Being evaluated.
     * @param bool $allowSessionCookies If we should persist session cookies
     * @return bool
     */
    public static function shouldPersist(
        SetCookie $cookie,
        $allowSessionCookies = false
    ) {
        if ($cookie->getExpires() || $allowSessionCookies) {
            if (!$cookie->getDiscard()) {
                return true;
            }
        }

        return false;
    }

    public function toArray()
    {
        return array_map(function (SetCookie $cookie) {
            return $cookie->toArray();
        }, $this->getIterator()->getArrayCopy());
    }

    public function clear($domain = null, $path = null, $name = null)
    {
        if (!$domain) {
            $this->cookies = [];
            return;
        } elseif (!$path) {
            $this->cookies = array_filter(
                $this->cookies,
                function (SetCookie $cookie) use ($path, $domain) {
                    return !$cookie->matchesDomain($domain);
                }
            );
        } elseif (!$name) {
            $this->cookies = array_filter(
                $this->cookies,
                function (SetCookie $cookie) use ($path, $domain) {
                    return !($cookie->matchesPath($path) &&
                        $cookie->matchesDomain($domain));
                }
            );
        } else {
            $this->cookies = array_filter(
                $this->cookies,
                function (SetCookie $cookie) use ($path, $domain, $name) {
                    return !($cookie->getName() == $name &&
                        $cookie->matchesPath($path) &&
                        $cookie->matchesDomain($domain));
                }
            );
        }
    }

    public function clearSessionCookies()
    {
        $this->cookies = array_filter(
            $this->cookies,
            function (SetCookie $cookie) {
                return !$cookie->getDiscard() && $cookie->getExpires();
            }
        );
    }

    public function setCookie(SetCookie $cookie)
    {
        // If the name string is empty (but not 0), ignore the set-cookie
        // string entirely.
        $name = $cookie->getName();
        if (!$name && $name !== '0') {
            return false;
        }

        // Only allow cookies with set and valid domain, name, value
        $result = $cookie->validate();
        if ($result !== true) {
            if ($this->strictMode) {
                throw new \RuntimeException('Invalid cookie: ' . $result);
            } else {
                $this->removeCookieIfEmpty($cookie);
                return false;
            }
        }

        // Resolve conflicts with previously set cookies
        foreach ($this->cookies as $i => $c) {

            // Two cookies are identical, when their path, and domain are
            // identical.
            if ($c->getPath() != $cookie->getPath() ||
                $c->getDomain() != $cookie->getDomain() ||
                $c->getName() != $cookie->getName()
            ) {
                continue;
            }

            // The previously set cookie is a discard cookie and this one is
            // not so allow the new cookie to be set
            if (!$cookie->getDiscard() && $c->getDiscard()) {
                unset($this->cookies[$i]);
                continue;
            }

            // If the new cookie's expiration is further into the future, then
            // replace the old cookie
            if ($cookie->getExpires() > $c->getExpires()) {
                unset($this->cookies[$i]);
                continue;
            }

            // If the value has changed, we better change it
            if ($cookie->getValue() !== $c->getValue()) {
                unset($this->cookies[$i]);
                continue;
            }

            // The cookie exists, so no need to continue
            return false;
        }

        $this->cookies[] = $cookie;

        return true;
    }

    public function count()
    {
        return count($this->cookies);
    }

    public function getIterator()
    {
        return new \ArrayIterator(array_values($this->cookies));
    }

    public function extractCookies(
        RequestInterface $request,
        ResponseInterface $response
    ) {
        if ($cookieHeader = $response->getHeader('Set-Cookie')) {
            foreach ($cookieHeader as $cookie) {
                $sc = SetCookie::fromString($cookie);
                if (!$sc->getDomain()) {
                    $sc->setDomain($request->getUri()->getHost());
                }
                $this->setCookie($sc);
            }
        }
    }

    public function withCookieHeader(RequestInterface $request)
    {
        $values = [];
        $uri = $request->getUri();
        $scheme = $uri->getScheme();
        $host = $uri->getHost();
        $path = $uri->getPath() ?: '/';

        foreach ($this->cookies as $cookie) {
            if ($cookie->matchesPath($path) &&
                $cookie->matchesDomain($host) &&
                !$cookie->isExpired() &&
                (!$cookie->getSecure() || $scheme == 'https')
            ) {
                $values[] = $cookie->getName() . '='
                    . self::getCookieValue($cookie->getValue());
            }
        }

        return $values
            ? $request->withHeader('Cookie', implode('; ', $values))
            : $request;
    }

    /**
     * If a cookie already exists and the server asks to set it again with a
     * null value, the cookie must be deleted.
     *
     * @param SetCookie $cookie
     */
    private function removeCookieIfEmpty(SetCookie $cookie)
    {
        $cookieValue = $cookie->getValue();
        if ($cookieValue === null || $cookieValue === '') {
            $this->clear(
                $cookie->getDomain(),
                $cookie->getPath(),
                $cookie->getName()
            );
        }
    }
}
<?php
namespace GuzzleHttp\Cookie;

use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * Stores HTTP cookies.
 *
 * It extracts cookies from HTTP requests, and returns them in HTTP responses.
 * CookieJarInterface instances automatically expire contained cookies when
 * necessary. Subclasses are also responsible for storing and retrieving
 * cookies from a file, database, etc.
 *
 * @link http://docs.python.org/2/library/cookielib.html Inspiration
 */
interface CookieJarInterface extends \Countable, \IteratorAggregate
{
    /**
     * Create a request with added cookie headers.
     *
     * If no matching cookies are found in the cookie jar, then no Cookie
     * header is added to the request and the same request is returned.
     *
     * @param RequestInterface $request Request object to modify.
     *
     * @return RequestInterface returns the modified request.
     */
    public function withCookieHeader(RequestInterface $request);

    /**
     * Extract cookies from an HTTP response and store them in the CookieJar.
     *
     * @param RequestInterface  $request  Request that was sent
     * @param ResponseInterface $response Response that was received
     */
    public function extractCookies(
        RequestInterface $request,
        ResponseInterface $response
    );

    /**
     * Sets a cookie in the cookie jar.
     *
     * @param SetCookie $cookie Cookie to set.
     *
     * @return bool Returns true on success or false on failure
     */
    public function setCookie(SetCookie $cookie);

    /**
     * Remove cookies currently held in the cookie jar.
     *
     * Invoking this method without arguments will empty the whole cookie jar.
     * If given a $domain argument only cookies belonging to that domain will
     * be removed. If given a $domain and $path argument, cookies belonging to
     * the specified path within that domain are removed. If given all three
     * arguments, then the cookie with the specified name, path and domain is
     * removed.
     *
     * @param string $domain Clears cookies matching a domain
     * @param string $path   Clears cookies matching a domain and path
     * @param string $name   Clears cookies matching a domain, path, and name
     *
     * @return CookieJarInterface
     */
    public function clear($domain = null, $path = null, $name = null);

    /**
     * Discard all sessions cookies.
     *
     * Removes cookies that don't have an expire field or a have a discard
     * field set to true. To be called when the user agent shuts down according
     * to RFC 2965.
     */
    public function clearSessionCookies();

    /**
     * Converts the cookie jar to an array.
     *
     * @return array
     */
    public function toArray();
}
<?php
namespace GuzzleHttp\Cookie;

/**
 * Persists non-session cookies using a JSON formatted file
 */
class FileCookieJar extends CookieJar
{
    /** @var string filename */
    private $filename;

    /** @var bool Control whether to persist session cookies or not. */
    private $storeSessionCookies;
    
    /**
     * Create a new FileCookieJar object
     *
     * @param string $cookieFile        File to store the cookie data
     * @param bool $storeSessionCookies Set to true to store session cookies
     *                                  in the cookie jar.
     *
     * @throws \RuntimeException if the file cannot be found or created
     */
    public function __construct($cookieFile, $storeSessionCookies = false)
    {
        $this->filename = $cookieFile;
        $this->storeSessionCookies = $storeSessionCookies;

        if (file_exists($cookieFile)) {
            $this->load($cookieFile);
        }
    }

    /**
     * Saves the file when shutting down
     */
    public function __destruct()
    {
        $this->save($this->filename);
    }

    /**
     * Saves the cookies to a file.
     *
     * @param string $filename File to save
     * @throws \RuntimeException if the file cannot be found or created
     */
    public function save($filename)
    {
        $json = [];
        foreach ($this as $cookie) {
            /** @var SetCookie $cookie */
            if (CookieJar::shouldPersist($cookie, $this->storeSessionCookies)) {
                $json[] = $cookie->toArray();
            }
        }

        if (false === file_put_contents($filename, json_encode($json))) {
            throw new \RuntimeException("Unable to save file {$filename}");
        }
    }

    /**
     * Load cookies from a JSON formatted file.
     *
     * Old cookies are kept unless overwritten by newly loaded ones.
     *
     * @param string $filename Cookie file to load.
     * @throws \RuntimeException if the file cannot be loaded.
     */
    public function load($filename)
    {
        $json = file_get_contents($filename);
        if (false === $json) {
            throw new \RuntimeException("Unable to load file {$filename}");
        }

        $data = json_decode($json, true);
        if (is_array($data)) {
            foreach (json_decode($json, true) as $cookie) {
                $this->setCookie(new SetCookie($cookie));
            }
        } elseif (strlen($data)) {
            throw new \RuntimeException("Invalid cookie file: {$filename}");
        }
    }
}
<?php
namespace GuzzleHttp\Cookie;

/**
 * Persists cookies in the client session
 */
class SessionCookieJar extends CookieJar
{
    /** @var string session key */
    private $sessionKey;
    
    /** @var bool Control whether to persist session cookies or not. */
    private $storeSessionCookies;

    /**
     * Create a new SessionCookieJar object
     *
     * @param string $sessionKey        Session key name to store the cookie 
     *                                  data in session
     * @param bool $storeSessionCookies Set to true to store session cookies
     *                                  in the cookie jar.
     */
    public function __construct($sessionKey, $storeSessionCookies = false)
    {
        $this->sessionKey = $sessionKey;
        $this->storeSessionCookies = $storeSessionCookies;
        $this->load();
    }

    /**
     * Saves cookies to session when shutting down
     */
    public function __destruct()
    {
        $this->save();
    }

    /**
     * Save cookies to the client session
     */
    public function save()
    {
        $json = [];
        foreach ($this as $cookie) {
            /** @var SetCookie $cookie */
            if (CookieJar::shouldPersist($cookie, $this->storeSessionCookies)) {
                $json[] = $cookie->toArray();
            }
        }

        $_SESSION[$this->sessionKey] = json_encode($json);
    }

    /**
     * Load the contents of the client session into the data array
     */
    protected function load()
    {
        $cookieJar = isset($_SESSION[$this->sessionKey])
            ? $_SESSION[$this->sessionKey]
            : null;

        $data = json_decode($cookieJar, true);
        if (is_array($data)) {
            foreach ($data as $cookie) {
                $this->setCookie(new SetCookie($cookie));
            }
        } elseif (strlen($data)) {
            throw new \RuntimeException("Invalid cookie data");
        }
    }
}
<?php
namespace GuzzleHttp\Cookie;

/**
 * Set-Cookie object
 */
class SetCookie
{
    /** @var array */
    private static $defaults = [
        'Name'     => null,
        'Value'    => null,
        'Domain'   => null,
        'Path'     => '/',
        'Max-Age'  => null,
        'Expires'  => null,
        'Secure'   => false,
        'Discard'  => false,
        'HttpOnly' => false
    ];

    /** @var array Cookie data */
    private $data;

    /**
     * Create a new SetCookie object from a string
     *
     * @param string $cookie Set-Cookie header string
     *
     * @return self
     */
    public static function fromString($cookie)
    {
        // Create the default return array
        $data = self::$defaults;
        // Explode the cookie string using a series of semicolons
        $pieces = array_filter(array_map('trim', explode(';', $cookie)));
        // The name of the cookie (first kvp) must include an equal sign.
        if (empty($pieces) || !strpos($pieces[0], '=')) {
            return new self($data);
        }

        // Add the cookie pieces into the parsed data array
        foreach ($pieces as $part) {

            $cookieParts = explode('=', $part, 2);
            $key = trim($cookieParts[0]);
            $value = isset($cookieParts[1])
                ? trim($cookieParts[1], " \n\r\t\0\x0B")
                : true;

            // Only check for non-cookies when cookies have been found
            if (empty($data['Name'])) {
                $data['Name'] = $key;
                $data['Value'] = $value;
            } else {
                foreach (array_keys(self::$defaults) as $search) {
                    if (!strcasecmp($search, $key)) {
                        $data[$search] = $value;
                        continue 2;
                    }
                }
                $data[$key] = $value;
            }
        }

        return new self($data);
    }

    /**
     * @param array $data Array of cookie data provided by a Cookie parser
     */
    public function __construct(array $data = [])
    {
        $this->data = array_replace(self::$defaults, $data);
        // Extract the Expires value and turn it into a UNIX timestamp if needed
        if (!$this->getExpires() && $this->getMaxAge()) {
            // Calculate the Expires date
            $this->setExpires(time() + $this->getMaxAge());
        } elseif ($this->getExpires() && !is_numeric($this->getExpires())) {
            $this->setExpires($this->getExpires());
        }
    }

    public function __toString()
    {
        $str = $this->data['Name'] . '=' . $this->data['Value'] . '; ';
        foreach ($this->data as $k => $v) {
            if ($k != 'Name' && $k != 'Value' && $v !== null && $v !== false) {
                if ($k == 'Expires') {
                    $str .= 'Expires=' . gmdate('D, d M Y H:i:s \G\M\T', $v) . '; ';
                } else {
                    $str .= ($v === true ? $k : "{$k}={$v}") . '; ';
                }
            }
        }

        return rtrim($str, '; ');
    }

    public function toArray()
    {
        return $this->data;
    }

    /**
     * Get the cookie name
     *
     * @return string
     */
    public function getName()
    {
        return $this->data['Name'];
    }

    /**
     * Set the cookie name
     *
     * @param string $name Cookie name
     */
    public function setName($name)
    {
        $this->data['Name'] = $name;
    }

    /**
     * Get the cookie value
     *
     * @return string
     */
    public function getValue()
    {
        return $this->data['Value'];
    }

    /**
     * Set the cookie value
     *
     * @param string $value Cookie value
     */
    public function setValue($value)
    {
        $this->data['Value'] = $value;
    }

    /**
     * Get the domain
     *
     * @return string|null
     */
    public function getDomain()
    {
        return $this->data['Domain'];
    }

    /**
     * Set the domain of the cookie
     *
     * @param string $domain
     */
    public function setDomain($domain)
    {
        $this->data['Domain'] = $domain;
    }

    /**
     * Get the path
     *
     * @return string
     */
    public function getPath()
    {
        return $this->data['Path'];
    }

    /**
     * Set the path of the cookie
     *
     * @param string $path Path of the cookie
     */
    public function setPath($path)
    {
        $this->data['Path'] = $path;
    }

    /**
     * Maximum lifetime of the cookie in seconds
     *
     * @return int|null
     */
    public function getMaxAge()
    {
        return $this->data['Max-Age'];
    }

    /**
     * Set the max-age of the cookie
     *
     * @param int $maxAge Max age of the cookie in seconds
     */
    public function setMaxAge($maxAge)
    {
        $this->data['Max-Age'] = $maxAge;
    }

    /**
     * The UNIX timestamp when the cookie Expires
     *
     * @return mixed
     */
    public function getExpires()
    {
        return $this->data['Expires'];
    }

    /**
     * Set the unix timestamp for which the cookie will expire
     *
     * @param int $timestamp Unix timestamp
     */
    public function setExpires($timestamp)
    {
        $this->data['Expires'] = is_numeric($timestamp)
            ? (int) $timestamp
            : strtotime($timestamp);
    }

    /**
     * Get whether or not this is a secure cookie
     *
     * @return null|bool
     */
    public function getSecure()
    {
        return $this->data['Secure'];
    }

    /**
     * Set whether or not the cookie is secure
     *
     * @param bool $secure Set to true or false if secure
     */
    public function setSecure($secure)
    {
        $this->data['Secure'] = $secure;
    }

    /**
     * Get whether or not this is a session cookie
     *
     * @return null|bool
     */
    public function getDiscard()
    {
        return $this->data['Discard'];
    }

    /**
     * Set whether or not this is a session cookie
     *
     * @param bool $discard Set to true or false if this is a session cookie
     */
    public function setDiscard($discard)
    {
        $this->data['Discard'] = $discard;
    }

    /**
     * Get whether or not this is an HTTP only cookie
     *
     * @return bool
     */
    public function getHttpOnly()
    {
        return $this->data['HttpOnly'];
    }

    /**
     * Set whether or not this is an HTTP only cookie
     *
     * @param bool $httpOnly Set to true or false if this is HTTP only
     */
    public function setHttpOnly($httpOnly)
    {
        $this->data['HttpOnly'] = $httpOnly;
    }

    /**
     * Check if the cookie matches a path value.
     *
     * A request-path path-matches a given cookie-path if at least one of
     * the following conditions holds:
     *
     * - The cookie-path and the request-path are identical.
     * - The cookie-path is a prefix of the request-path, and the last
     *   character of the cookie-path is %x2F ("/").
     * - The cookie-path is a prefix of the request-path, and the first
     *   character of the request-path that is not included in the cookie-
     *   path is a %x2F ("/") character.
     *
     * @param string $requestPath Path to check against
     *
     * @return bool
     */
    public function matchesPath($requestPath)
    {
        $cookiePath = $this->getPath();

        // Match on exact matches or when path is the default empty "/"
        if ($cookiePath == '/' || $cookiePath == $requestPath) {
            return true;
        }

        // Ensure that the cookie-path is a prefix of the request path.
        if (0 !== strpos($requestPath, $cookiePath)) {
            return false;
        }

        // Match if the last character of the cookie-path is "/"
        if (substr($cookiePath, -1, 1) == '/') {
            return true;
        }

        // Match if the first character not included in cookie path is "/"
        return substr($requestPath, strlen($cookiePath), 1) == '/';
    }

    /**
     * Check if the cookie matches a domain value
     *
     * @param string $domain Domain to check against
     *
     * @return bool
     */
    public function matchesDomain($domain)
    {
        // Remove the leading '.' as per spec in RFC 6265.
        // http://tools.ietf.org/html/rfc6265#section-5.2.3
        $cookieDomain = ltrim($this->getDomain(), '.');

        // Domain not set or exact match.
        if (!$cookieDomain || !strcasecmp($domain, $cookieDomain)) {
            return true;
        }

        // Matching the subdomain according to RFC 6265.
        // http://tools.ietf.org/html/rfc6265#section-5.1.3
        if (filter_var($domain, FILTER_VALIDATE_IP)) {
            return false;
        }

        return (bool) preg_match('/\.' . preg_quote($cookieDomain) . '$/', $domain);
    }

    /**
     * Check if the cookie is expired
     *
     * @return bool
     */
    public function isExpired()
    {
        return $this->getExpires() && time() > $this->getExpires();
    }

    /**
     * Check if the cookie is valid according to RFC 6265
     *
     * @return bool|string Returns true if valid or an error message if invalid
     */
    public function validate()
    {
        // Names must not be empty, but can be 0
        $name = $this->getName();
        if (empty($name) && !is_numeric($name)) {
            return 'The cookie name must not be empty';
        }

        // Check if any of the invalid characters are present in the cookie name
        if (preg_match(
            '/[\x00-\x20\x22\x28-\x29\x2c\x2f\x3a-\x40\x5c\x7b\x7d\x7f]/',
            $name)
        ) {
            return 'Cookie name must not contain invalid characters: ASCII '
                . 'Control characters (0-31;127), space, tab and the '
                . 'following characters: ()<>@,;:\"/?={}';
        }

        // Value must not be empty, but can be 0
        $value = $this->getValue();
        if (empty($value) && !is_numeric($value)) {
            return 'The cookie value must not be empty';
        }

        // Domains must not be empty, but can be 0
        // A "0" is not a valid internet domain, but may be used as server name
        // in a private network.
        $domain = $this->getDomain();
        if (empty($domain) && !is_numeric($domain)) {
            return 'The cookie domain must not be empty';
        }

        return true;
    }
}
<?php
namespace GuzzleHttp\Exception;

/**
 * Exception when an HTTP error occurs (4xx or 5xx error)
 */
class BadResponseException extends RequestException {}
<?php
namespace GuzzleHttp\Exception;

/**
 * Exception when a client error is encountered (4xx codes)
 */
class ClientException extends BadResponseException {}
<?php
namespace GuzzleHttp\Exception;

use Psr\Http\Message\RequestInterface;

/**
 * Exception thrown when a connection cannot be established.
 *
 * Note that no response is present for a ConnectException
 */
class ConnectException extends RequestException
{
    public function __construct(
        $message,
        RequestInterface $request,
        \Exception $previous = null,
        array $handlerContext = []
    ) {
        parent::__construct($message, $request, null, $previous, $handlerContext);
    }

    /**
     * @return null
     */
    public function getResponse()
    {
        return null;
    }

    /**
     * @return bool
     */
    public function hasResponse()
    {
        return false;
    }
}
<?php
namespace GuzzleHttp\Exception;

interface GuzzleException {}
<?php
namespace GuzzleHttp\Exception;

use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use GuzzleHttp\Promise\PromiseInterface;

/**
 * HTTP Request exception
 */
class RequestException extends TransferException
{
    /** @var RequestInterface */
    private $request;

    /** @var ResponseInterface */
    private $response;

    /** @var array */
    private $handlerContext;

    public function __construct(
        $message,
        RequestInterface $request,
        ResponseInterface $response = null,
        \Exception $previous = null,
        array $handlerContext = []
    ) {
        // Set the code of the exception if the response is set and not future.
        $code = $response && !($response instanceof PromiseInterface)
            ? $response->getStatusCode()
            : 0;
        parent::__construct($message, $code, $previous);
        $this->request = $request;
        $this->response = $response;
        $this->handlerContext = $handlerContext;
    }

    /**
     * Wrap non-RequestExceptions with a RequestException
     *
     * @param RequestInterface $request
     * @param \Exception       $e
     *
     * @return RequestException
     */
    public static function wrapException(RequestInterface $request, \Exception $e)
    {
        return $e instanceof RequestException
            ? $e
            : new RequestException($e->getMessage(), $request, null, $e);
    }

    /**
     * Factory method to create a new exception with a normalized error message
     *
     * @param RequestInterface  $request  Request
     * @param ResponseInterface $response Response received
     * @param \Exception        $previous Previous exception
     * @param array             $ctx      Optional handler context.
     *
     * @return self
     */
    public static function create(
        RequestInterface $request,
        ResponseInterface $response = null,
        \Exception $previous = null,
        array $ctx = []
    ) {
        if (!$response) {
            return new self(
                'Error completing request',
                $request,
                null,
                $previous,
                $ctx
            );
        }

        $level = floor($response->getStatusCode() / 100);
        if ($level == '4') {
            $label = 'Client error';
            $className = __NAMESPACE__ . '\\ClientException';
        } elseif ($level == '5') {
            $label = 'Server error';
            $className = __NAMESPACE__ . '\\ServerException';
        } else {
            $label = 'Unsuccessful request';
            $className = __CLASS__;
        }

        // Server Error: `GET /` resulted in a `404 Not Found` response:
        // <html> ... (truncated)
        $message = sprintf(
            '%s: `%s` resulted in a `%s` response',
            $label,
            $request->getMethod() . ' ' . $request->getUri(),
            $response->getStatusCode() . ' ' . $response->getReasonPhrase()
        );

        $summary = static::getResponseBodySummary($response);

        if ($summary !== null) {
            $message .= ":\n{$summary}\n";
        }

        return new $className($message, $request, $response, $previous, $ctx);
    }

    /**
     * Get a short summary of the response
     *
     * Will return `null` if the response is not printable.
     *
     * @param ResponseInterface $response
     *
     * @return string|null
     */
    public static function getResponseBodySummary(ResponseInterface $response)
    {
        $body = $response->getBody();

        if (!$body->isSeekable()) {
            return null;
        }

        $size = $body->getSize();
        $summary = $body->read(120);
        $body->rewind();

        if ($size > 120) {
            $summary .= ' (truncated...)';
        }

        // Matches any printable character, including unicode characters:
        // letters, marks, numbers, punctuation, spacing, and separators.
        if (preg_match('/[^\pL\pM\pN\pP\pS\pZ\n\r\t]/', $summary)) {
            return null;
        }

        return $summary;
    }

    /**
     * Get the request that caused the exception
     *
     * @return RequestInterface
     */
    public function getRequest()
    {
        return $this->request;
    }

    /**
     * Get the associated response
     *
     * @return ResponseInterface|null
     */
    public function getResponse()
    {
        return $this->response;
    }

    /**
     * Check if a response was received
     *
     * @return bool
     */
    public function hasResponse()
    {
        return $this->response !== null;
    }

    /**
     * Get contextual information about the error from the underlying handler.
     *
     * The contents of this array will vary depending on which handler you are
     * using. It may also be just an empty array. Relying on this data will
     * couple you to a specific handler, but can give more debug information
     * when needed.
     *
     * @return array
     */
    public function getHandlerContext()
    {
        return $this->handlerContext;
    }
}
<?php
namespace GuzzleHttp\Exception;

use Psr\Http\Message\StreamInterface;

/**
 * Exception thrown when a seek fails on a stream.
 */
class SeekException extends \RuntimeException implements GuzzleException
{
    private $stream;

    public function __construct(StreamInterface $stream, $pos = 0, $msg = '')
    {
        $this->stream = $stream;
        $msg = $msg ?: 'Could not seek the stream to position ' . $pos;
        parent::__construct($msg);
    }

    /**
     * @return StreamInterface
     */
    public function getStream()
    {
        return $this->stream;
    }
}
<?php
namespace GuzzleHttp\Exception;

/**
 * Exception when a server error is encountered (5xx codes)
 */
class ServerException extends BadResponseException {}
<?php
namespace GuzzleHttp\Exception;

class TooManyRedirectsException extends RequestException {}
<?php
namespace GuzzleHttp\Exception;

class TransferException extends \RuntimeException implements GuzzleException {}
<?php
namespace GuzzleHttp;

use GuzzleHttp\Handler\CurlHandler;
use GuzzleHttp\Handler\CurlMultiHandler;
use GuzzleHttp\Handler\Proxy;
use GuzzleHttp\Handler\StreamHandler;
use Psr\Http\Message\StreamInterface;

/**
 * Expands a URI template
 *
 * @param string $template  URI template
 * @param array  $variables Template variables
 *
 * @return string
 */
function uri_template($template, array $variables)
{
    if (extension_loaded('uri_template')) {
        // @codeCoverageIgnoreStart
        return \uri_template($template, $variables);
        // @codeCoverageIgnoreEnd
    }

    static $uriTemplate;
    if (!$uriTemplate) {
        $uriTemplate = new UriTemplate();
    }

    return $uriTemplate->expand($template, $variables);
}

/**
 * Debug function used to describe the provided value type and class.
 *
 * @param mixed $input
 *
 * @return string Returns a string containing the type of the variable and
 *                if a class is provided, the class name.
 */
function describe_type($input)
{
    switch (gettype($input)) {
        case 'object':
            return 'object(' . get_class($input) . ')';
        case 'array':
            return 'array(' . count($input) . ')';
        default:
            ob_start();
            var_dump($input);
            // normalize float vs double
            return str_replace('double(', 'float(', rtrim(ob_get_clean()));
    }
}

/**
 * Parses an array of header lines into an associative array of headers.
 *
 * @param array $lines Header lines array of strings in the following
 *                     format: "Name: Value"
 * @return array
 */
function headers_from_lines($lines)
{
    $headers = [];

    foreach ($lines as $line) {
        $parts = explode(':', $line, 2);
        $headers[trim($parts[0])][] = isset($parts[1])
            ? trim($parts[1])
            : null;
    }

    return $headers;
}

/**
 * Returns a debug stream based on the provided variable.
 *
 * @param mixed $value Optional value
 *
 * @return resource
 */
function debug_resource($value = null)
{
    if (is_resource($value)) {
        return $value;
    } elseif (defined('STDOUT')) {
        return STDOUT;
    }

    return fopen('php://output', 'w');
}

/**
 * Chooses and creates a default handler to use based on the environment.
 *
 * The returned handler is not wrapped by any default middlewares.
 *
 * @throws \RuntimeException if no viable Handler is available.
 * @return callable Returns the best handler for the given system.
 */
function choose_handler()
{
    $handler = null;
    if (function_exists('curl_multi_exec') && function_exists('curl_exec')) {
        $handler = Proxy::wrapSync(new CurlMultiHandler(), new CurlHandler());
    } elseif (function_exists('curl_exec')) {
        $handler = new CurlHandler();
    } elseif (function_exists('curl_multi_exec')) {
        $handler = new CurlMultiHandler();
    }

    if (ini_get('allow_url_fopen')) {
        $handler = $handler
            ? Proxy::wrapStreaming($handler, new StreamHandler())
            : new StreamHandler();
    } elseif (!$handler) {
        throw new \RuntimeException('GuzzleHttp requires cURL, the '
            . 'allow_url_fopen ini setting, or a custom HTTP handler.');
    }

    return $handler;
}

/**
 * Get the default User-Agent string to use with Guzzle
 *
 * @return string
 */
function default_user_agent()
{
    static $defaultAgent = '';

    if (!$defaultAgent) {
        $defaultAgent = 'GuzzleHttp/' . Client::VERSION;
        if (extension_loaded('curl') && function_exists('curl_version')) {
            $defaultAgent .= ' curl/' . \curl_version()['version'];
        }
        $defaultAgent .= ' PHP/' . PHP_VERSION;
    }

    return $defaultAgent;
}

/**
 * Returns the default cacert bundle for the current system.
 *
 * First, the openssl.cafile and curl.cainfo php.ini settings are checked.
 * If those settings are not configured, then the common locations for
 * bundles found on Red Hat, CentOS, Fedora, Ubuntu, Debian, FreeBSD, OS X
 * and Windows are checked. If any of these file locations are found on
 * disk, they will be utilized.
 *
 * Note: the result of this function is cached for subsequent calls.
 *
 * @return string
 * @throws \RuntimeException if no bundle can be found.
 */
function default_ca_bundle()
{
    static $cached = null;
    static $cafiles = [
        // Red Hat, CentOS, Fedora (provided by the ca-certificates package)
        '/etc/pki/tls/certs/ca-bundle.crt',
        // Ubuntu, Debian (provided by the ca-certificates package)
        '/etc/ssl/certs/ca-certificates.crt',
        // FreeBSD (provided by the ca_root_nss package)
        '/usr/local/share/certs/ca-root-nss.crt',
        // OS X provided by homebrew (using the default path)
        '/usr/local/etc/openssl/cert.pem',
        // Google app engine
        '/etc/ca-certificates.crt',
        // Windows?
        'C:\\windows\\system32\\curl-ca-bundle.crt',
        'C:\\windows\\curl-ca-bundle.crt',
    ];

    if ($cached) {
        return $cached;
    }

    if ($ca = ini_get('openssl.cafile')) {
        return $cached = $ca;
    }

    if ($ca = ini_get('curl.cainfo')) {
        return $cached = $ca;
    }

    foreach ($cafiles as $filename) {
        if (file_exists($filename)) {
            return $cached = $filename;
        }
    }

    throw new \RuntimeException(<<< EOT
No system CA bundle could be found in any of the the common system locations.
PHP versions earlier than 5.6 are not properly configured to use the system's
CA bundle by default. In order to verify peer certificates, you will need to
supply the path on disk to a certificate bundle to the 'verify' request
option: http://docs.guzzlephp.org/en/latest/clients.html#verify. If you do not
need a specific certificate bundle, then Mozilla provides a commonly used CA
bundle which can be downloaded here (provided by the maintainer of cURL):
https://raw.githubusercontent.com/bagder/ca-bundle/master/ca-bundle.crt. Once
you have a CA bundle available on disk, you can set the 'openssl.cafile' PHP
ini setting to point to the path to the file, allowing you to omit the 'verify'
request option. See http://curl.haxx.se/docs/sslcerts.html for more
information.
EOT
    );
}

/**
 * Creates an associative array of lowercase header names to the actual
 * header casing.
 *
 * @param array $headers
 *
 * @return array
 */
function normalize_header_keys(array $headers)
{
    $result = [];
    foreach (array_keys($headers) as $key) {
        $result[strtolower($key)] = $key;
    }

    return $result;
}

/**
 * Returns true if the provided host matches any of the no proxy areas.
 *
 * This method will strip a port from the host if it is present. Each pattern
 * can be matched with an exact match (e.g., "foo.com" == "foo.com") or a
 * partial match: (e.g., "foo.com" == "baz.foo.com" and ".foo.com" ==
 * "baz.foo.com", but ".foo.com" != "foo.com").
 *
 * Areas are matched in the following cases:
 * 1. "*" (without quotes) always matches any hosts.
 * 2. An exact match.
 * 3. The area starts with "." and the area is the last part of the host. e.g.
 *    '.mit.edu' will match any host that ends with '.mit.edu'.
 *
 * @param string $host         Host to check against the patterns.
 * @param array  $noProxyArray An array of host patterns.
 *
 * @return bool
 */
function is_host_in_noproxy($host, array $noProxyArray)
{
    if (strlen($host) === 0) {
        throw new \InvalidArgumentException('Empty host provided');
    }

    // Strip port if present.
    if (strpos($host, ':')) {
        $host = explode($host, ':', 2)[0];
    }

    foreach ($noProxyArray as $area) {
        // Always match on wildcards.
        if ($area === '*') {
            return true;
        } elseif (empty($area)) {
            // Don't match on empty values.
            continue;
        } elseif ($area === $host) {
            // Exact matches.
            return true;
        } else {
            // Special match if the area when prefixed with ".". Remove any
            // existing leading "." and add a new leading ".".
            $area = '.' . ltrim($area, '.');
            if (substr($host, -(strlen($area))) === $area) {
                return true;
            }
        }
    }

    return false;
}
<?php

// Don't redefine the functions if included multiple times.
if (!function_exists('GuzzleHttp\uri_template')) {
    require __DIR__ . '/functions.php';
}
<?php
namespace GuzzleHttp\Handler;

use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Promise\FulfilledPromise;
use GuzzleHttp\Promise\RejectedPromise;
use GuzzleHttp\Psr7;
use GuzzleHttp\Psr7\LazyOpenStream;
use GuzzleHttp\TransferStats;
use Psr\Http\Message\RequestInterface;

/**
 * Creates curl resources from a request
 */
class CurlFactory implements CurlFactoryInterface
{
    /** @var array */
    private $handles;

    /** @var int Total number of idle handles to keep in cache */
    private $maxHandles;

    /**
     * @param int $maxHandles Maximum number of idle handles.
     */
    public function __construct($maxHandles)
    {
        $this->maxHandles = $maxHandles;
    }

    public function create(RequestInterface $request, array $options)
    {
        if (isset($options['curl']['body_as_string'])) {
            $options['_body_as_string'] = $options['curl']['body_as_string'];
            unset($options['curl']['body_as_string']);
        }

        $easy = new EasyHandle;
        $easy->request = $request;
        $easy->options = $options;
        $conf = $this->getDefaultConf($easy);
        $this->applyMethod($easy, $conf);
        $this->applyHandlerOptions($easy, $conf);
        $this->applyHeaders($easy, $conf);
        unset($conf['_headers']);

        // Add handler options from the request configuration options
        if (isset($options['curl'])) {
            $conf = array_replace($conf, $options['curl']);
        }

        $conf[CURLOPT_HEADERFUNCTION] = $this->createHeaderFn($easy);
        $easy->handle = $this->handles
            ? array_pop($this->handles)
            : curl_init();
        curl_setopt_array($easy->handle, $conf);

        return $easy;
    }

    public function release(EasyHandle $easy)
    {
        $resource = $easy->handle;
        unset($easy->handle);

        if (count($this->handles) >= $this->maxHandles) {
            curl_close($resource);
        } else {
            // Remove all callback functions as they can hold onto references
            // and are not cleaned up by curl_reset. Using curl_setopt_array
            // does not work for some reason, so removing each one
            // individually.
            curl_setopt($resource, CURLOPT_HEADERFUNCTION, null);
            curl_setopt($resource, CURLOPT_READFUNCTION, null);
            curl_setopt($resource, CURLOPT_WRITEFUNCTION, null);
            curl_setopt($resource, CURLOPT_PROGRESSFUNCTION, null);
            curl_reset($resource);
            $this->handles[] = $resource;
        }
    }

    /**
     * Completes a cURL transaction, either returning a response promise or a
     * rejected promise.
     *
     * @param callable             $handler
     * @param EasyHandle           $easy
     * @param CurlFactoryInterface $factory Dictates how the handle is released
     *
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public static function finish(
        callable $handler,
        EasyHandle $easy,
        CurlFactoryInterface $factory
    ) {
        if (isset($easy->options['on_stats'])) {
            self::invokeStats($easy);
        }

        if (!$easy->response || $easy->errno) {
            return self::finishError($handler, $easy, $factory);
        }

        // Return the response if it is present and there is no error.
        $factory->release($easy);

        // Rewind the body of the response if possible.
        $body = $easy->response->getBody();
        if ($body->isSeekable()) {
            $body->rewind();
        }

        return new FulfilledPromise($easy->response);
    }

    private static function invokeStats(EasyHandle $easy)
    {
        $curlStats = curl_getinfo($easy->handle);
        $stats = new TransferStats(
            $easy->request,
            $easy->response,
            $curlStats['total_time'],
            $easy->errno,
            $curlStats
        );
        call_user_func($easy->options['on_stats'], $stats);
    }

    private static function finishError(
        callable $handler,
        EasyHandle $easy,
        CurlFactoryInterface $factory
    ) {
        // Get error information and release the handle to the factory.
        $ctx = [
            'errno' => $easy->errno,
            'error' => curl_error($easy->handle),
        ] + curl_getinfo($easy->handle);
        $factory->release($easy);

        // Retry when nothing is present or when curl failed to rewind.
        if (empty($easy->options['_err_message'])
            && (!$easy->errno || $easy->errno == 65)
        ) {
            return self::retryFailedRewind($handler, $easy, $ctx);
        }

        return self::createRejection($easy, $ctx);
    }

    private static function createRejection(EasyHandle $easy, array $ctx)
    {
        static $connectionErrors = [
            CURLE_OPERATION_TIMEOUTED  => true,
            CURLE_COULDNT_RESOLVE_HOST => true,
            CURLE_COULDNT_CONNECT      => true,
            CURLE_SSL_CONNECT_ERROR    => true,
            CURLE_GOT_NOTHING          => true,
        ];

        // If an exception was encountered during the onHeaders event, then
        // return a rejected promise that wraps that exception.
        if ($easy->onHeadersException) {
            return new RejectedPromise(
                new RequestException(
                    'An error was encountered during the on_headers event',
                    $easy->request,
                    $easy->response,
                    $easy->onHeadersException,
                    $ctx
                )
            );
        }

        $message = sprintf(
            'cURL error %s: %s (%s)',
            $ctx['errno'],
            $ctx['error'],
            'see http://curl.haxx.se/libcurl/c/libcurl-errors.html'
        );

        // Create a connection exception if it was a specific error code.
        $error = isset($connectionErrors[$easy->errno])
            ? new ConnectException($message, $easy->request, null, $ctx)
            : new RequestException($message, $easy->request, $easy->response, null, $ctx);

        return new RejectedPromise($error);
    }

    private function getDefaultConf(EasyHandle $easy)
    {
        $conf = [
            '_headers'             => $easy->request->getHeaders(),
            CURLOPT_CUSTOMREQUEST  => $easy->request->getMethod(),
            CURLOPT_URL            => (string) $easy->request->getUri(),
            CURLOPT_RETURNTRANSFER => false,
            CURLOPT_HEADER         => false,
            CURLOPT_CONNECTTIMEOUT => 150,
        ];

        if (defined('CURLOPT_PROTOCOLS')) {
            $conf[CURLOPT_PROTOCOLS] = CURLPROTO_HTTP | CURLPROTO_HTTPS;
        }

        $version = $easy->request->getProtocolVersion();
        if ($version == 1.1) {
            $conf[CURLOPT_HTTP_VERSION] = CURL_HTTP_VERSION_1_1;
        } elseif ($version == 2.0) {
            $conf[CURLOPT_HTTP_VERSION] = CURL_HTTP_VERSION_2_0;
        } else {
            $conf[CURLOPT_HTTP_VERSION] = CURL_HTTP_VERSION_1_0;
        }

        return $conf;
    }

    private function applyMethod(EasyHandle $easy, array &$conf)
    {
        $body = $easy->request->getBody();
        $size = $body->getSize();

        if ($size === null || $size > 0) {
            $this->applyBody($easy->request, $easy->options, $conf);
            return;
        }

        $method = $easy->request->getMethod();
        if ($method === 'PUT' || $method === 'POST') {
            // See http://tools.ietf.org/html/rfc7230#section-3.3.2
            if (!$easy->request->hasHeader('Content-Length')) {
                $conf[CURLOPT_HTTPHEADER][] = 'Content-Length: 0';
            }
        } elseif ($method === 'HEAD') {
            $conf[CURLOPT_NOBODY] = true;
            unset(
                $conf[CURLOPT_WRITEFUNCTION],
                $conf[CURLOPT_READFUNCTION],
                $conf[CURLOPT_FILE],
                $conf[CURLOPT_INFILE]
            );
        }
    }

    private function applyBody(RequestInterface $request, array $options, array &$conf)
    {
        $size = $request->hasHeader('Content-Length')
            ? (int) $request->getHeaderLine('Content-Length')
            : null;

        // Send the body as a string if the size is less than 1MB OR if the
        // [curl][body_as_string] request value is set.
        if (($size !== null && $size < 1000000) ||
            !empty($options['_body_as_string'])
        ) {
            $conf[CURLOPT_POSTFIELDS] = (string) $request->getBody();
            // Don't duplicate the Content-Length header
            $this->removeHeader('Content-Length', $conf);
            $this->removeHeader('Transfer-Encoding', $conf);
        } else {
            $conf[CURLOPT_UPLOAD] = true;
            if ($size !== null) {
                $conf[CURLOPT_INFILESIZE] = $size;
                $this->removeHeader('Content-Length', $conf);
            }
            $body = $request->getBody();
            $conf[CURLOPT_READFUNCTION] = function ($ch, $fd, $length) use ($body) {
                return $body->read($length);
            };
        }

        // If the Expect header is not present, prevent curl from adding it
        if (!$request->hasHeader('Expect')) {
            $conf[CURLOPT_HTTPHEADER][] = 'Expect:';
        }

        // cURL sometimes adds a content-type by default. Prevent this.
        if (!$request->hasHeader('Content-Type')) {
            $conf[CURLOPT_HTTPHEADER][] = 'Content-Type:';
        }
    }

    private function applyHeaders(EasyHandle $easy, array &$conf)
    {
        foreach ($conf['_headers'] as $name => $values) {
            foreach ($values as $value) {
                $conf[CURLOPT_HTTPHEADER][] = "$name: $value";
            }
        }

        // Remove the Accept header if one was not set
        if (!$easy->request->hasHeader('Accept')) {
            $conf[CURLOPT_HTTPHEADER][] = 'Accept:';
        }
    }

    /**
     * Remove a header from the options array.
     *
     * @param string $name    Case-insensitive header to remove
     * @param array  $options Array of options to modify
     */
    private function removeHeader($name, array &$options)
    {
        foreach (array_keys($options['_headers']) as $key) {
            if (!strcasecmp($key, $name)) {
                unset($options['_headers'][$key]);
                return;
            }
        }
    }

    private function applyHandlerOptions(EasyHandle $easy, array &$conf)
    {
        $options = $easy->options;
        if (isset($options['verify'])) {
            if ($options['verify'] === false) {
                unset($conf[CURLOPT_CAINFO]);
                $conf[CURLOPT_SSL_VERIFYHOST] = 0;
                $conf[CURLOPT_SSL_VERIFYPEER] = false;
            } else {
                $conf[CURLOPT_SSL_VERIFYHOST] = 2;
                $conf[CURLOPT_SSL_VERIFYPEER] = true;
                if (is_string($options['verify'])) {
                    $conf[CURLOPT_CAINFO] = $options['verify'];
                    if (!file_exists($options['verify'])) {
                        throw new \InvalidArgumentException(
                            "SSL CA bundle not found: {$options['verify']}"
                        );
                    }
                }
            }
        }

        if (!empty($options['decode_content'])) {
            $accept = $easy->request->getHeaderLine('Accept-Encoding');
            if ($accept) {
                $conf[CURLOPT_ENCODING] = $accept;
            } else {
                $conf[CURLOPT_ENCODING] = '';
                // Don't let curl send the header over the wire
                $conf[CURLOPT_HTTPHEADER][] = 'Accept-Encoding:';
            }
        }

        if (isset($options['sink'])) {
            $sink = $options['sink'];
            if (!is_string($sink)) {
                $sink = \GuzzleHttp\Psr7\stream_for($sink);
            } elseif (!is_dir(dirname($sink))) {
                // Ensure that the directory exists before failing in curl.
                throw new \RuntimeException(sprintf(
                    'Directory %s does not exist for sink value of %s',
                    dirname($sink),
                    $sink
                ));
            } else {
                $sink = new LazyOpenStream($sink, 'w+');
            }
            $easy->sink = $sink;
            $conf[CURLOPT_WRITEFUNCTION] = function ($ch, $write) use ($sink) {
                return $sink->write($write);
            };
        } else {
            // Use a default temp stream if no sink was set.
            $conf[CURLOPT_FILE] = fopen('php://temp', 'w+');
            $easy->sink = Psr7\stream_for($conf[CURLOPT_FILE]);
        }

        if (isset($options['timeout'])) {
            $conf[CURLOPT_TIMEOUT_MS] = $options['timeout'] * 1000;
        }

        if (isset($options['connect_timeout'])) {
            $conf[CURLOPT_CONNECTTIMEOUT_MS] = $options['connect_timeout'] * 1000;
        }

        if (isset($options['proxy'])) {
            if (!is_array($options['proxy'])) {
                $conf[CURLOPT_PROXY] = $options['proxy'];
            } else {
                $scheme = $easy->request->getUri()->getScheme();
                if (isset($options['proxy'][$scheme])) {
                    $host = $easy->request->getUri()->getHost();
                    if (!isset($options['proxy']['no']) ||
                        !\GuzzleHttp\is_host_in_noproxy($host, $options['proxy']['no'])
                    ) {
                        $conf[CURLOPT_PROXY] = $options['proxy'][$scheme];
                    }
                }
            }
        }

        if (isset($options['cert'])) {
            $cert = $options['cert'];
            if (is_array($cert)) {
                $conf[CURLOPT_SSLCERTPASSWD] = $cert[1];
                $cert = $cert[0];
            }
            if (!file_exists($cert)) {
                throw new \InvalidArgumentException(
                    "SSL certificate not found: {$cert}"
                );
            }
            $conf[CURLOPT_SSLCERT] = $cert;
        }

        if (isset($options['ssl_key'])) {
            $sslKey = $options['ssl_key'];
            if (is_array($sslKey)) {
                $conf[CURLOPT_SSLKEYPASSWD] = $sslKey[1];
                $sslKey = $sslKey[0];
            }
            if (!file_exists($sslKey)) {
                throw new \InvalidArgumentException(
                    "SSL private key not found: {$sslKey}"
                );
            }
            $conf[CURLOPT_SSLKEY] = $sslKey;
        }

        if (isset($options['progress'])) {
            $progress = $options['progress'];
            if (!is_callable($progress)) {
                throw new \InvalidArgumentException(
                    'progress client option must be callable'
                );
            }
            $conf[CURLOPT_NOPROGRESS] = false;
            $conf[CURLOPT_PROGRESSFUNCTION] = function () use ($progress) {
                $args = func_get_args();
                // PHP 5.5 pushed the handle onto the start of the args
                if (is_resource($args[0])) {
                    array_shift($args);
                }
                call_user_func_array($progress, $args);
            };
        }

        if (!empty($options['debug'])) {
            $conf[CURLOPT_STDERR] = \GuzzleHttp\debug_resource($options['debug']);
            $conf[CURLOPT_VERBOSE] = true;
        }
    }

    /**
     * This function ensures that a response was set on a transaction. If one
     * was not set, then the request is retried if possible. This error
     * typically means you are sending a payload, curl encountered a
     * "Connection died, retrying a fresh connect" error, tried to rewind the
     * stream, and then encountered a "necessary data rewind wasn't possible"
     * error, causing the request to be sent through curl_multi_info_read()
     * without an error status.
     */
    private static function retryFailedRewind(
        callable $handler,
        EasyHandle $easy,
        array $ctx
    ) {
        try {
            // Only rewind if the body has been read from.
            $body = $easy->request->getBody();
            if ($body->tell() > 0) {
                $body->rewind();
            }
        } catch (\RuntimeException $e) {
            $ctx['error'] = 'The connection unexpectedly failed without '
                . 'providing an error. The request would have been retried, '
                . 'but attempting to rewind the request body failed. '
                . 'Exception: ' . $e;
            return self::createRejection($easy, $ctx);
        }

        // Retry no more than 3 times before giving up.
        if (!isset($easy->options['_curl_retries'])) {
            $easy->options['_curl_retries'] = 1;
        } elseif ($easy->options['_curl_retries'] == 2) {
            $ctx['error'] = 'The cURL request was retried 3 times '
                . 'and did not succeed. The most likely reason for the failure '
                . 'is that cURL was unable to rewind the body of the request '
                . 'and subsequent retries resulted in the same error. Turn on '
                . 'the debug option to see what went wrong. See '
                . 'https://bugs.php.net/bug.php?id=47204 for more information.';
            return self::createRejection($easy, $ctx);
        } else {
            $easy->options['_curl_retries']++;
        }

        return $handler($easy->request, $easy->options);
    }

    private function createHeaderFn(EasyHandle $easy)
    {
        if (!isset($easy->options['on_headers'])) {
            $onHeaders = null;
        } elseif (!is_callable($easy->options['on_headers'])) {
            throw new \InvalidArgumentException('on_headers must be callable');
        } else {
            $onHeaders = $easy->options['on_headers'];
        }

        return function ($ch, $h) use (
            $onHeaders,
            $easy,
            &$startingResponse
        ) {
            $value = trim($h);
            if ($value === '') {
                $startingResponse = true;
                $easy->createResponse();
                if ($onHeaders) {
                    try {
                        $onHeaders($easy->response);
                    } catch (\Exception $e) {
                        // Associate the exception with the handle and trigger
                        // a curl header write error by returning 0.
                        $easy->onHeadersException = $e;
                        return -1;
                    }
                }
            } elseif ($startingResponse) {
                $startingResponse = false;
                $easy->headers = [$value];
            } else {
                $easy->headers[] = $value;
            }
            return strlen($h);
        };
    }
}
<?php
namespace GuzzleHttp\Handler;

use Psr\Http\Message\RequestInterface;

interface CurlFactoryInterface
{
    /**
     * Creates a cURL handle resource.
     *
     * @param RequestInterface $request Request
     * @param array            $options Transfer options
     *
     * @return EasyHandle
     * @throws \RuntimeException when an option cannot be applied
     */
    public function create(RequestInterface $request, array $options);

    /**
     * Release an easy handle, allowing it to be reused or closed.
     *
     * This function must call unset on the easy handle's "handle" property.
     *
     * @param EasyHandle $easy
     */
    public function release(EasyHandle $easy);
}
<?php
namespace GuzzleHttp\Handler;

use GuzzleHttp\Psr7;
use Psr\Http\Message\RequestInterface;

/**
 * HTTP handler that uses cURL easy handles as a transport layer.
 *
 * When using the CurlHandler, custom curl options can be specified as an
 * associative array of curl option constants mapping to values in the
 * **curl** key of the "client" key of the request.
 */
class CurlHandler
{
    /** @var CurlFactoryInterface */
    private $factory;

    /**
     * Accepts an associative array of options:
     *
     * - factory: Optional curl factory used to create cURL handles.
     *
     * @param array $options Array of options to use with the handler
     */
    public function __construct(array $options = [])
    {
        $this->factory = isset($options['handle_factory'])
            ? $options['handle_factory']
            : new CurlFactory(3);
    }

    public function __invoke(RequestInterface $request, array $options)
    {
        if (isset($options['delay'])) {
            usleep($options['delay'] * 1000);
        }

        $easy = $this->factory->create($request, $options);
        curl_exec($easy->handle);
        $easy->errno = curl_errno($easy->handle);

        return CurlFactory::finish($this, $easy, $this->factory);
    }
}
<?php
namespace GuzzleHttp\Handler;

use GuzzleHttp\Promise as P;
use GuzzleHttp\Promise\Promise;
use GuzzleHttp\Psr7;
use Psr\Http\Message\RequestInterface;

/**
 * Returns an asynchronous response using curl_multi_* functions.
 *
 * When using the CurlMultiHandler, custom curl options can be specified as an
 * associative array of curl option constants mapping to values in the
 * **curl** key of the provided request options.
 *
 * @property resource $_mh Internal use only. Lazy loaded multi-handle.
 */
class CurlMultiHandler
{
    /** @var CurlFactoryInterface */
    private $factory;
    private $selectTimeout;
    private $active;
    private $handles = [];
    private $delays = [];

    /**
     * This handler accepts the following options:
     *
     * - handle_factory: An optional factory  used to create curl handles
     * - select_timeout: Optional timeout (in seconds) to block before timing
     *   out while selecting curl handles. Defaults to 1 second.
     *
     * @param array $options
     */
    public function __construct(array $options = [])
    {
        $this->factory = isset($options['handle_factory'])
            ? $options['handle_factory'] : new CurlFactory(50);
        $this->selectTimeout = isset($options['select_timeout'])
            ? $options['select_timeout'] : 1;
    }

    public function __get($name)
    {
        if ($name === '_mh') {
            return $this->_mh = curl_multi_init();
        }

        throw new \BadMethodCallException();
    }

    public function __destruct()
    {
        if (isset($this->_mh)) {
            curl_multi_close($this->_mh);
            unset($this->_mh);
        }
    }

    public function __invoke(RequestInterface $request, array $options)
    {
        $easy = $this->factory->create($request, $options);
        $id = (int) $easy->handle;

        $promise = new Promise(
            [$this, 'execute'],
            function () use ($id) { return $this->cancel($id); }
        );

        $this->addRequest(['easy' => $easy, 'deferred' => $promise]);

        return $promise;
    }

    /**
     * Ticks the curl event loop.
     */
    public function tick()
    {
        // Add any delayed handles if needed.
        if ($this->delays) {
            $currentTime = microtime(true);
            foreach ($this->delays as $id => $delay) {
                if ($currentTime >= $delay) {
                    unset($this->delays[$id]);
                    curl_multi_add_handle(
                        $this->_mh,
                        $this->handles[$id]['easy']->handle
                    );
                }
            }
        }

        // Step through the task queue which may add additional requests.
        P\queue()->run();

        if ($this->active &&
            curl_multi_select($this->_mh, $this->selectTimeout) === -1
        ) {
            // Perform a usleep if a select returns -1.
            // See: https://bugs.php.net/bug.php?id=61141
            usleep(250);
        }

        while (curl_multi_exec($this->_mh, $this->active) === CURLM_CALL_MULTI_PERFORM);

        $this->processMessages();
    }

    /**
     * Runs until all outstanding connections have completed.
     */
    public function execute()
    {
        $queue = P\queue();

        while ($this->handles || !$queue->isEmpty()) {
            // If there are no transfers, then sleep for the next delay
            if (!$this->active && $this->delays) {
                usleep($this->timeToNext());
            }
            $this->tick();
        }
    }

    private function addRequest(array $entry)
    {
        $easy = $entry['easy'];
        $id = (int) $easy->handle;
        $this->handles[$id] = $entry;
        if (empty($easy->options['delay'])) {
            curl_multi_add_handle($this->_mh, $easy->handle);
        } else {
            $this->delays[$id] = microtime(true) + ($easy->options['delay'] / 1000);
        }
    }

    /**
     * Cancels a handle from sending and removes references to it.
     *
     * @param int $id Handle ID to cancel and remove.
     *
     * @return bool True on success, false on failure.
     */
    private function cancel($id)
    {
        // Cannot cancel if it has been processed.
        if (!isset($this->handles[$id])) {
            return false;
        }

        $handle = $this->handles[$id]['easy']->handle;
        unset($this->delays[$id], $this->handles[$id]);
        curl_multi_remove_handle($this->_mh, $handle);
        curl_close($handle);

        return true;
    }

    private function processMessages()
    {
        while ($done = curl_multi_info_read($this->_mh)) {
            $id = (int) $done['handle'];
            curl_multi_remove_handle($this->_mh, $done['handle']);

            if (!isset($this->handles[$id])) {
                // Probably was cancelled.
                continue;
            }

            $entry = $this->handles[$id];
            unset($this->handles[$id], $this->delays[$id]);
            $entry['easy']->errno = $done['result'];
            $entry['deferred']->resolve(
                CurlFactory::finish(
                    $this,
                    $entry['easy'],
                    $this->factory
                )
            );
        }
    }

    private function timeToNext()
    {
        $currentTime = microtime(true);
        $nextTime = PHP_INT_MAX;
        foreach ($this->delays as $time) {
            if ($time < $nextTime) {
                $nextTime = $time;
            }
        }

        return max(0, $currentTime - $nextTime);
    }
}
<?php
namespace GuzzleHttp\Handler;

use GuzzleHttp\Psr7\Response;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;

/**
 * Represents a cURL easy handle and the data it populates.
 *
 * @internal
 */
final class EasyHandle
{
    /** @var resource cURL resource */
    public $handle;

    /** @var StreamInterface Where data is being written */
    public $sink;

    /** @var array Received HTTP headers so far */
    public $headers = [];

    /** @var ResponseInterface Received response (if any) */
    public $response;

    /** @var RequestInterface Request being sent */
    public $request;

    /** @var array Request options */
    public $options = [];

    /** @var int cURL error number (if any) */
    public $errno = 0;

    /** @var \Exception Exception during on_headers (if any) */
    public $onHeadersException;

    /**
     * Attach a response to the easy handle based on the received headers.
     *
     * @throws \RuntimeException if no headers have been received.
     */
    public function createResponse()
    {
        if (empty($this->headers)) {
            throw new \RuntimeException('No headers have been received');
        }

        // HTTP-version SP status-code SP reason-phrase
        $startLine = explode(' ', array_shift($this->headers), 3);
        $headers = \GuzzleHttp\headers_from_lines($this->headers);
        $normalizedKeys = \GuzzleHttp\normalize_header_keys($headers);

        if (!empty($this->options['decode_content'])
            && isset($normalizedKeys['content-encoding'])
        ) {
            unset($headers[$normalizedKeys['content-encoding']]);
            if (isset($normalizedKeys['content-length'])) {
                $bodyLength = (int) $this->sink->getSize();
                if ($bodyLength) {
                    $headers[$normalizedKeys['content-length']] = $bodyLength;
                } else {
                    unset($headers[$normalizedKeys['content-length']]);
                }
            }
        }

        // Attach a response to the easy handle with the parsed headers.
        $this->response = new Response(
            $startLine[1],
            $headers,
            $this->sink,
            substr($startLine[0], 5),
            isset($startLine[2]) ? (string) $startLine[2] : null
        );
    }

    public function __get($name)
    {
        $msg = $name === 'handle'
            ? 'The EasyHandle has been released'
            : 'Invalid property: ' . $name;
        throw new \BadMethodCallException($msg);
    }
}
<?php
namespace GuzzleHttp\Handler;

use GuzzleHttp\HandlerStack;
use GuzzleHttp\Promise\PromiseInterface;
use GuzzleHttp\Promise\RejectedPromise;
use GuzzleHttp\TransferStats;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * Handler that returns responses or throw exceptions from a queue.
 */
class MockHandler implements \Countable
{
    private $queue;
    private $lastRequest;
    private $lastOptions;
    private $onFulfilled;
    private $onRejected;

    /**
     * Creates a new MockHandler that uses the default handler stack list of
     * middlewares.
     *
     * @param array $queue Array of responses, callables, or exceptions.
     * @param callable $onFulfilled Callback to invoke when the return value is fulfilled.
     * @param callable $onRejected  Callback to invoke when the return value is rejected.
     *
     * @return MockHandler
     */
    public static function createWithMiddleware(
        array $queue = null,
        callable $onFulfilled = null,
        callable $onRejected = null
    ) {
        return HandlerStack::create(new self($queue, $onFulfilled, $onRejected));
    }

    /**
     * The passed in value must be an array of
     * {@see Psr7\Http\Message\ResponseInterface} objects, Exceptions,
     * callables, or Promises.
     *
     * @param array $queue
     * @param callable $onFulfilled Callback to invoke when the return value is fulfilled.
     * @param callable $onRejected  Callback to invoke when the return value is rejected.
     */
    public function __construct(
        array $queue = null,
        callable $onFulfilled = null,
        callable $onRejected = null
    ) {
        $this->onFulfilled = $onFulfilled;
        $this->onRejected = $onRejected;

        if ($queue) {
            call_user_func_array([$this, 'append'], $queue);
        }
    }

    public function __invoke(RequestInterface $request, array $options)
    {
        if (!$this->queue) {
            throw new \OutOfBoundsException('Mock queue is empty');
        }

        if (isset($options['delay'])) {
            usleep($options['delay'] * 1000);
        }

        $this->lastRequest = $request;
        $this->lastOptions = $options;
        $response = array_shift($this->queue);

        if (is_callable($response)) {
            $response = $response($request, $options);
        }

        $response = $response instanceof \Exception
            ? new RejectedPromise($response)
            : \GuzzleHttp\Promise\promise_for($response);

        return $response->then(
            function ($value) use ($request, $options) {
                $this->invokeStats($request, $options, $value);
                if ($this->onFulfilled) {
                    call_user_func($this->onFulfilled, $value);
                }
                if (isset($options['sink'])) {
                    $contents = (string) $value->getBody();
                    $sink = $options['sink'];

                    if (is_resource($sink)) {
                        fwrite($sink, $contents);
                    } elseif (is_string($sink)) {
                        file_put_contents($sink, $contents);
                    } elseif ($sink instanceof \Psr\Http\Message\StreamInterface) {
                        $sink->write($contents);
                    }
                }

                return $value;
            },
            function ($reason) use ($request, $options) {
                $this->invokeStats($request, $options, null, $reason);
                if ($this->onRejected) {
                    call_user_func($this->onRejected, $reason);
                }
                return new RejectedPromise($reason);
            }
        );
    }

    /**
     * Adds one or more variadic requests, exceptions, callables, or promises
     * to the queue.
     */
    public function append()
    {
        foreach (func_get_args() as $value) {
            if ($value instanceof ResponseInterface
                || $value instanceof \Exception
                || $value instanceof PromiseInterface
                || is_callable($value)
            ) {
                $this->queue[] = $value;
            } else {
                throw new \InvalidArgumentException('Expected a response or '
                    . 'exception. Found ' . \GuzzleHttp\describe_type($value));
            }
        }
    }

    /**
     * Get the last received request.
     *
     * @return RequestInterface
     */
    public function getLastRequest()
    {
        return $this->lastRequest;
    }

    /**
     * Get the last received request options.
     *
     * @return RequestInterface
     */
    public function getLastOptions()
    {
        return $this->lastOptions;
    }

    /**
     * Returns the number of remaining items in the queue.
     *
     * @return int
     */
    public function count()
    {
        return count($this->queue);
    }

    private function invokeStats(
        RequestInterface $request,
        array $options,
        ResponseInterface $response = null,
        $reason = null
    ) {
        if (isset($options['on_stats'])) {
            $stats = new TransferStats($request, $response, 0, $reason);
            call_user_func($options['on_stats'], $stats);
        }
    }
}
<?php
namespace GuzzleHttp\Handler;

use GuzzleHttp\RequestOptions;
use Psr\Http\Message\RequestInterface;

/**
 * Provides basic proxies for handlers.
 */
class Proxy
{
    /**
     * Sends synchronous requests to a specific handler while sending all other
     * requests to another handler.
     *
     * @param callable $default Handler used for normal responses
     * @param callable $sync    Handler used for synchronous responses.
     *
     * @return callable Returns the composed handler.
     */
    public static function wrapSync(
        callable $default,
        callable $sync
    ) {
        return function (RequestInterface $request, array $options) use ($default, $sync) {
            return empty($options[RequestOptions::SYNCHRONOUS])
                ? $default($request, $options)
                : $sync($request, $options);
        };
    }

    /**
     * Sends streaming requests to a streaming compatible handler while sending
     * all other requests to a default handler.
     *
     * This, for example, could be useful for taking advantage of the
     * performance benefits of curl while still supporting true streaming
     * through the StreamHandler.
     *
     * @param callable $default   Handler used for non-streaming responses
     * @param callable $streaming Handler used for streaming responses
     *
     * @return callable Returns the composed handler.
     */
    public static function wrapStreaming(
        callable $default,
        callable $streaming
    ) {
        return function (RequestInterface $request, array $options) use ($default, $streaming) {
            return empty($options['stream'])
                ? $default($request, $options)
                : $streaming($request, $options);
        };
    }
}
<?php
namespace GuzzleHttp\Handler;

use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Promise\FulfilledPromise;
use GuzzleHttp\Promise\RejectedPromise;
use GuzzleHttp\Promise\PromiseInterface;
use GuzzleHttp\Psr7;
use GuzzleHttp\TransferStats;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;

/**
 * HTTP handler that uses PHP's HTTP stream wrapper.
 */
class StreamHandler
{
    private $lastHeaders = [];

    /**
     * Sends an HTTP request.
     *
     * @param RequestInterface $request Request to send.
     * @param array            $options Request transfer options.
     *
     * @return PromiseInterface
     */
    public function __invoke(RequestInterface $request, array $options)
    {
        // Sleep if there is a delay specified.
        if (isset($options['delay'])) {
            usleep($options['delay'] * 1000);
        }

        $startTime = isset($options['on_stats']) ? microtime(true) : null;

        try {
            // Does not support the expect header.
            $request = $request->withoutHeader('Expect');

            // Append a content-length header if body size is zero to match
            // cURL's behavior.
            if (0 === $request->getBody()->getSize()) {
                $request = $request->withHeader('Content-Length', 0);
            }

            return $this->createResponse(
                $request,
                $options,
                $this->createStream($request, $options),
                $startTime
            );
        } catch (\InvalidArgumentException $e) {
            throw $e;
        } catch (\Exception $e) {
            // Determine if the error was a networking error.
            $message = $e->getMessage();
            // This list can probably get more comprehensive.
            if (strpos($message, 'getaddrinfo') // DNS lookup failed
                || strpos($message, 'Connection refused')
                || strpos($message, "couldn't connect to host") // error on HHVM
            ) {
                $e = new ConnectException($e->getMessage(), $request, $e);
            }
            $e = RequestException::wrapException($request, $e);
            $this->invokeStats($options, $request, $startTime, null, $e);

            return new RejectedPromise($e);
        }
    }

    private function invokeStats(
        array $options,
        RequestInterface $request,
        $startTime,
        ResponseInterface $response = null,
        $error = null
    ) {
        if (isset($options['on_stats'])) {
            $stats = new TransferStats(
                $request,
                $response,
                microtime(true) - $startTime,
                $error,
                []
            );
            call_user_func($options['on_stats'], $stats);
        }
    }

    private function createResponse(
        RequestInterface $request,
        array $options,
        $stream,
        $startTime
    ) {
        $hdrs = $this->lastHeaders;
        $this->lastHeaders = [];
        $parts = explode(' ', array_shift($hdrs), 3);
        $ver = explode('/', $parts[0])[1];
        $status = $parts[1];
        $reason = isset($parts[2]) ? $parts[2] : null;
        $headers = \GuzzleHttp\headers_from_lines($hdrs);
        list ($stream, $headers) = $this->checkDecode($options, $headers, $stream);
        $stream = Psr7\stream_for($stream);
        $sink = $this->createSink($stream, $options);
        $response = new Psr7\Response($status, $headers, $sink, $ver, $reason);

        if (isset($options['on_headers'])) {
            try {
                $options['on_headers']($response);
            } catch (\Exception $e) {
                $msg = 'An error was encountered during the on_headers event';
                $ex = new RequestException($msg, $request, $response, $e);
                return new RejectedPromise($ex);
            }
        }

        if ($sink !== $stream) {
            $this->drain($stream, $sink);
        }

        $this->invokeStats($options, $request, $startTime, $response, null);

        return new FulfilledPromise($response);
    }

    private function createSink(StreamInterface $stream, array $options)
    {
        if (!empty($options['stream'])) {
            return $stream;
        }

        $sink = isset($options['sink'])
            ? $options['sink']
            : fopen('php://temp', 'r+');

        return is_string($sink)
            ? new Psr7\Stream(Psr7\try_fopen($sink, 'r+'))
            : Psr7\stream_for($sink);
    }

    private function checkDecode(array $options, array $headers, $stream)
    {
        // Automatically decode responses when instructed.
        if (!empty($options['decode_content'])) {
            $normalizedKeys = \GuzzleHttp\normalize_header_keys($headers);
            if (isset($normalizedKeys['content-encoding'])) {
                $encoding = $headers[$normalizedKeys['content-encoding']];
                if ($encoding[0] == 'gzip' || $encoding[0] == 'deflate') {
                    $stream = new Psr7\InflateStream(
                        Psr7\stream_for($stream)
                    );
                    // Remove content-encoding header
                    unset($headers[$normalizedKeys['content-encoding']]);
                    // Fix content-length header
                    if (isset($normalizedKeys['content-length'])) {
                        $length = (int) $stream->getSize();
                        if ($length == 0) {
                            unset($headers[$normalizedKeys['content-length']]);
                        } else {
                            $headers[$normalizedKeys['content-length']] = [$length];
                        }
                    }
                }
            }
        }

        return [$stream, $headers];
    }

    /**
     * Drains the source stream into the "sink" client option.
     *
     * @param StreamInterface $source
     * @param StreamInterface $sink
     *
     * @return StreamInterface
     * @throws \RuntimeException when the sink option is invalid.
     */
    private function drain(StreamInterface $source, StreamInterface $sink)
    {
        Psr7\copy_to_stream($source, $sink);
        $sink->seek(0);
        $source->close();

        return $sink;
    }

    /**
     * Create a resource and check to ensure it was created successfully
     *
     * @param callable $callback Callable that returns stream resource
     *
     * @return resource
     * @throws \RuntimeException on error
     */
    private function createResource(callable $callback)
    {
        $errors = null;
        set_error_handler(function ($_, $msg, $file, $line) use (&$errors) {
            $errors[] = [
                'message' => $msg,
                'file'    => $file,
                'line'    => $line
            ];
            return true;
        });

        $resource = $callback();
        restore_error_handler();

        if (!$resource) {
            $message = 'Error creating resource: ';
            foreach ($errors as $err) {
                foreach ($err as $key => $value) {
                    $message .= "[$key] $value" . PHP_EOL;
                }
            }
            throw new \RuntimeException(trim($message));
        }

        return $resource;
    }

    private function createStream(RequestInterface $request, array $options)
    {
        static $methods;
        if (!$methods) {
            $methods = array_flip(get_class_methods(__CLASS__));
        }

        // HTTP/1.1 streams using the PHP stream wrapper require a
        // Connection: close header
        if ($request->getProtocolVersion() == '1.1'
            && !$request->hasHeader('Connection')
        ) {
            $request = $request->withHeader('Connection', 'close');
        }

        // Ensure SSL is verified by default
        if (!isset($options['verify'])) {
            $options['verify'] = true;
        }

        $params = [];
        $context = $this->getDefaultContext($request, $options);

        if (isset($options['on_headers']) && !is_callable($options['on_headers'])) {
            throw new \InvalidArgumentException('on_headers must be callable');
        }

        if (!empty($options)) {
            foreach ($options as $key => $value) {
                $method = "add_{$key}";
                if (isset($methods[$method])) {
                    $this->{$method}($request, $context, $value, $params);
                }
            }
        }

        if (isset($options['stream_context'])) {
            if (!is_array($options['stream_context'])) {
                throw new \InvalidArgumentException('stream_context must be an array');
            }
            $context = array_replace_recursive(
                $context,
                $options['stream_context']
            );
        }

        $context = $this->createResource(
            function () use ($context, $params) {
                return stream_context_create($context, $params);
            }
        );

        return $this->createResource(
            function () use ($request, &$http_response_header, $context) {
                $resource = fopen($request->getUri(), 'r', null, $context);
                $this->lastHeaders = $http_response_header;
                return $resource;
            }
        );
    }

    private function getDefaultContext(RequestInterface $request)
    {
        $headers = '';
        foreach ($request->getHeaders() as $name => $value) {
            foreach ($value as $val) {
                $headers .= "$name: $val\r\n";
            }
        }

        $context = [
            'http' => [
                'method'           => $request->getMethod(),
                'header'           => $headers,
                'protocol_version' => $request->getProtocolVersion(),
                'ignore_errors'    => true,
                'follow_location'  => 0,
            ],
        ];

        $body = (string) $request->getBody();

        if (!empty($body)) {
            $context['http']['content'] = $body;
            // Prevent the HTTP handler from adding a Content-Type header.
            if (!$request->hasHeader('Content-Type')) {
                $context['http']['header'] .= "Content-Type:\r\n";
            }
        }

        $context['http']['header'] = rtrim($context['http']['header']);

        return $context;
    }

    private function add_proxy(RequestInterface $request, &$options, $value, &$params)
    {
        if (!is_array($value)) {
            $options['http']['proxy'] = $value;
        } else {
            $scheme = $request->getUri()->getScheme();
            if (isset($value[$scheme])) {
                if (!isset($value['no'])
                    || !\GuzzleHttp\is_host_in_noproxy(
                        $request->getUri()->getHost(),
                        $value['no']
                    )
                ) {
                    $options['http']['proxy'] = $value[$scheme];
                }
            }
        }
    }

    private function add_timeout(RequestInterface $request, &$options, $value, &$params)
    {
        $options['http']['timeout'] = $value;
    }

    private function add_verify(RequestInterface $request, &$options, $value, &$params)
    {
        if ($value === true) {
            // PHP 5.6 or greater will find the system cert by default. When
            // < 5.6, use the Guzzle bundled cacert.
            if (PHP_VERSION_ID < 50600) {
                $options['ssl']['cafile'] = \GuzzleHttp\default_ca_bundle();
            }
        } elseif (is_string($value)) {
            $options['ssl']['cafile'] = $value;
            if (!file_exists($value)) {
                throw new \RuntimeException("SSL CA bundle not found: $value");
            }
        } elseif ($value === false) {
            $options['ssl']['verify_peer'] = false;
            $options['ssl']['verify_peer_name'] = false;
            return;
        } else {
            throw new \InvalidArgumentException('Invalid verify request option');
        }

        $options['ssl']['verify_peer'] = true;
        $options['ssl']['verify_peer_name'] = true;
        $options['ssl']['allow_self_signed'] = false;
    }

    private function add_cert(RequestInterface $request, &$options, $value, &$params)
    {
        if (is_array($value)) {
            $options['ssl']['passphrase'] = $value[1];
            $value = $value[0];
        }

        if (!file_exists($value)) {
            throw new \RuntimeException("SSL certificate not found: {$value}");
        }

        $options['ssl']['local_cert'] = $value;
    }

    private function add_progress(RequestInterface $request, &$options, $value, &$params)
    {
        $this->addNotification(
            $params,
            function ($code, $a, $b, $c, $transferred, $total) use ($value) {
                if ($code == STREAM_NOTIFY_PROGRESS) {
                    $value($total, $transferred, null, null);
                }
            }
        );
    }

    private function add_debug(RequestInterface $request, &$options, $value, &$params)
    {
        if ($value === false) {
            return;
        }

        static $map = [
            STREAM_NOTIFY_CONNECT       => 'CONNECT',
            STREAM_NOTIFY_AUTH_REQUIRED => 'AUTH_REQUIRED',
            STREAM_NOTIFY_AUTH_RESULT   => 'AUTH_RESULT',
            STREAM_NOTIFY_MIME_TYPE_IS  => 'MIME_TYPE_IS',
            STREAM_NOTIFY_FILE_SIZE_IS  => 'FILE_SIZE_IS',
            STREAM_NOTIFY_REDIRECTED    => 'REDIRECTED',
            STREAM_NOTIFY_PROGRESS      => 'PROGRESS',
            STREAM_NOTIFY_FAILURE       => 'FAILURE',
            STREAM_NOTIFY_COMPLETED     => 'COMPLETED',
            STREAM_NOTIFY_RESOLVE       => 'RESOLVE',
        ];
        static $args = ['severity', 'message', 'message_code',
            'bytes_transferred', 'bytes_max'];

        $value = \GuzzleHttp\debug_resource($value);
        $ident = $request->getMethod() . ' ' . $request->getUri();
        $this->addNotification(
            $params,
            function () use ($ident, $value, $map, $args) {
                $passed = func_get_args();
                $code = array_shift($passed);
                fprintf($value, '<%s> [%s] ', $ident, $map[$code]);
                foreach (array_filter($passed) as $i => $v) {
                    fwrite($value, $args[$i] . ': "' . $v . '" ');
                }
                fwrite($value, "\n");
            }
        );
    }

    private function addNotification(array &$params, callable $notify)
    {
        // Wrap the existing function if needed.
        if (!isset($params['notification'])) {
            $params['notification'] = $notify;
        } else {
            $params['notification'] = $this->callArray([
                $params['notification'],
                $notify
            ]);
        }
    }

    private function callArray(array $functions)
    {
        return function () use ($functions) {
            $args = func_get_args();
            foreach ($functions as $fn) {
                call_user_func_array($fn, $args);
            }
        };
    }
}
<?php
namespace GuzzleHttp;

use Psr\Http\Message\RequestInterface;

/**
 * Creates a composed Guzzle handler function by stacking middlewares on top of
 * an HTTP handler function.
 */
class HandlerStack
{
    /** @var callable */
    private $handler;

    /** @var array */
    private $stack = [];

    /** @var callable|null */
    private $cached;

    /**
     * Creates a default handler stack that can be used by clients.
     *
     * The returned handler will wrap the provided handler or use the most
     * appropriate default handler for you system. The returned HandlerStack has
     * support for cookies, redirects, HTTP error exceptions, and preparing a body
     * before sending.
     *
     * The returned handler stack can be passed to a client in the "handler"
     * option.
     *
     * @param callable $handler HTTP handler function to use with the stack. If no
     *                          handler is provided, the best handler for your
     *                          system will be utilized.
     *
     * @return HandlerStack
     */
    public static function create(callable $handler = null)
    {
        $stack = new self($handler ?: choose_handler());
        $stack->push(Middleware::httpErrors(), 'http_errors');
        $stack->push(Middleware::redirect(), 'allow_redirects');
        $stack->push(Middleware::cookies(), 'cookies');
        $stack->push(Middleware::prepareBody(), 'prepare_body');

        return $stack;
    }

    /**
     * @param callable $handler Underlying HTTP handler.
     */
    public function __construct(callable $handler = null)
    {
        $this->handler = $handler;
    }

    /**
     * Invokes the handler stack as a composed handler
     *
     * @param RequestInterface $request
     * @param array            $options
     */
    public function __invoke(RequestInterface $request, array $options)
    {
        $handler = $this->resolve();

        return $handler($request, $options);
    }

    /**
     * Dumps a string representation of the stack.
     *
     * @return string
     */
    public function __toString()
    {
        $depth = 0;
        $stack = [];
        if ($this->handler) {
            $stack[] = "0) Handler: " . $this->debugCallable($this->handler);
        }

        $result = '';
        foreach (array_reverse($this->stack) as $tuple) {
            $depth++;
            $str = "{$depth}) Name: '{$tuple[1]}', ";
            $str .= "Function: " . $this->debugCallable($tuple[0]);
            $result = "> {$str}\n{$result}";
            $stack[] = $str;
        }

        foreach (array_keys($stack) as $k) {
            $result .= "< {$stack[$k]}\n";
        }

        return $result;
    }

    /**
     * Set the HTTP handler that actually returns a promise.
     *
     * @param callable $handler Accepts a request and array of options and
     *                          returns a Promise.
     */
    public function setHandler(callable $handler)
    {
        $this->handler = $handler;
        $this->cached = null;
    }

    /**
     * Returns true if the builder has a handler.
     *
     * @return bool
     */
    public function hasHandler()
    {
        return (bool) $this->handler;
    }

    /**
     * Unshift a middleware to the bottom of the stack.
     *
     * @param callable $middleware Middleware function
     * @param string   $name       Name to register for this middleware.
     */
    public function unshift(callable $middleware, $name = null)
    {
        array_unshift($this->stack, [$middleware, $name]);
        $this->cached = null;
    }

    /**
     * Push a middleware to the top of the stack.
     *
     * @param callable $middleware Middleware function
     * @param string   $name       Name to register for this middleware.
     */
    public function push(callable $middleware, $name = '')
    {
        $this->stack[] = [$middleware, $name];
        $this->cached = null;
    }

    /**
     * Add a middleware before another middleware by name.
     *
     * @param string   $findName   Middleware to find
     * @param callable $middleware Middleware function
     * @param string   $withName   Name to register for this middleware.
     */
    public function before($findName, callable $middleware, $withName = '')
    {
        $this->splice($findName, $withName, $middleware, true);
    }

    /**
     * Add a middleware after another middleware by name.
     *
     * @param string   $findName   Middleware to find
     * @param callable $middleware Middleware function
     * @param string   $withName   Name to register for this middleware.
     */
    public function after($findName, callable $middleware, $withName = '')
    {
        $this->splice($findName, $withName, $middleware, false);
    }

    /**
     * Remove a middleware by instance or name from the stack.
     *
     * @param callable|string $remove Middleware to remove by instance or name.
     */
    public function remove($remove)
    {
        $this->cached = null;
        $idx = is_callable($remove) ? 0 : 1;
        $this->stack = array_values(array_filter(
            $this->stack,
            function ($tuple) use ($idx, $remove) {
                return $tuple[$idx] !== $remove;
            }
        ));
    }

    /**
     * Compose the middleware and handler into a single callable function.
     *
     * @return callable
     */
    public function resolve()
    {
        if (!$this->cached) {
            if (!($prev = $this->handler)) {
                throw new \LogicException('No handler has been specified');
            }

            foreach (array_reverse($this->stack) as $fn) {
                $prev = $fn[0]($prev);
            }

            $this->cached = $prev;
        }

        return $this->cached;
    }

    /**
     * @param $name
     * @return int
     */
    private function findByName($name)
    {
        foreach ($this->stack as $k => $v) {
            if ($v[1] === $name) {
                return $k;
            }
        }

        throw new \InvalidArgumentException("Middleware not found: $name");
    }

    /**
     * Splices a function into the middleware list at a specific position.
     *
     * @param          $findName
     * @param          $withName
     * @param callable $middleware
     * @param          $before
     */
    private function splice($findName, $withName, callable $middleware, $before)
    {
        $this->cached = null;
        $idx = $this->findByName($findName);
        $tuple = [$middleware, $withName];

        if ($before) {
            if ($idx === 0) {
                array_unshift($this->stack, $tuple);
            } else {
                $replacement = [$tuple, $this->stack[$idx]];
                array_splice($this->stack, $idx, 1, $replacement);
            }
        } elseif ($idx === count($this->stack) - 1) {
            $this->stack[] = $tuple;
        } else {
            $replacement = [$this->stack[$idx], $tuple];
            array_splice($this->stack, $idx, 1, $replacement);
        }
    }

    /**
     * Provides a debug string for a given callable.
     *
     * @param array|callable $fn Function to write as a string.
     *
     * @return string
     */
    private function debugCallable($fn)
    {
        if (is_string($fn)) {
            return "callable({$fn})";
        }

        if (is_array($fn)) {
            return is_string($fn[0])
                ? "callable({$fn[0]}::{$fn[1]})"
                : "callable(['" . get_class($fn[0]) . "', '{$fn[1]}'])";
        }

        return 'callable(' . spl_object_hash($fn) . ')';
    }
}
<?php
namespace GuzzleHttp;

use Psr\Http\Message\MessageInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * Formats log messages using variable substitutions for requests, responses,
 * and other transactional data.
 *
 * The following variable substitutions are supported:
 *
 * - {request}:        Full HTTP request message
 * - {response}:       Full HTTP response message
 * - {ts}:             ISO 8601 date in GMT
 * - {date_iso_8601}   ISO 8601 date in GMT
 * - {date_common_log} Apache common log date using the configured timezone.
 * - {host}:           Host of the request
 * - {method}:         Method of the request
 * - {uri}:            URI of the request
 * - {host}:           Host of the request
 * - {version}:        Protocol version
 * - {target}:         Request target of the request (path + query + fragment)
 * - {hostname}:       Hostname of the machine that sent the request
 * - {code}:           Status code of the response (if available)
 * - {phrase}:         Reason phrase of the response  (if available)
 * - {error}:          Any error messages (if available)
 * - {req_header_*}:   Replace `*` with the lowercased name of a request header to add to the message
 * - {res_header_*}:   Replace `*` with the lowercased name of a response header to add to the message
 * - {req_headers}:    Request headers
 * - {res_headers}:    Response headers
 * - {req_body}:       Request body
 * - {res_body}:       Response body
 */
class MessageFormatter
{
    /**
     * Apache Common Log Format.
     * @link http://httpd.apache.org/docs/2.4/logs.html#common
     * @var string
     */
    const CLF = "{hostname} {req_header_User-Agent} - [{date_common_log}] \"{method} {target} HTTP/{version}\" {code} {res_header_Content-Length}";
    const DEBUG = ">>>>>>>>\n{request}\n<<<<<<<<\n{response}\n--------\n{error}";
    const SHORT = '[{ts}] "{method} {target} HTTP/{version}" {code}';

    /** @var string Template used to format log messages */
    private $template;

    /**
     * @param string $template Log message template
     */
    public function __construct($template = self::CLF)
    {
        $this->template = $template ?: self::CLF;
    }

    /**
     * Returns a formatted message string.
     *
     * @param RequestInterface  $request  Request that was sent
     * @param ResponseInterface $response Response that was received
     * @param \Exception        $error    Exception that was received
     *
     * @return string
     */
    public function format(
        RequestInterface $request,
        ResponseInterface $response = null,
        \Exception $error = null
    ) {
        $cache = [];

        return preg_replace_callback(
            '/{\s*([A-Za-z_\-\.0-9]+)\s*}/',
            function (array $matches) use ($request, $response, $error, &$cache) {

                if (isset($cache[$matches[1]])) {
                    return $cache[$matches[1]];
                }

                $result = '';
                switch ($matches[1]) {
                    case 'request':
                        $result = Psr7\str($request);
                        break;
                    case 'response':
                        $result = $response ? Psr7\str($response) : '';
                        break;
                    case 'req_headers':
                        $result = trim($request->getMethod()
                                . ' ' . $request->getRequestTarget())
                            . ' HTTP/' . $request->getProtocolVersion() . "\r\n"
                            . $this->headers($request);
                        break;
                    case 'res_headers':
                        $result = $response ?
                            sprintf(
                                'HTTP/%s %d %s',
                                $response->getProtocolVersion(),
                                $response->getStatusCode(),
                                $response->getReasonPhrase()
                            ) . "\r\n" . $this->headers($response)
                            : 'NULL';
                        break;
                    case 'req_body':
                        $result = $request->getBody();
                        break;
                    case 'res_body':
                        $result = $response ? $response->getBody() : 'NULL';
                        break;
                    case 'ts':
                    case 'date_iso_8601':
                        $result = gmdate('c');
                        break;
                    case 'date_common_log':
                        $result = date('d/M/Y:H:i:s O');
                        break;
                    case 'method':
                        $result = $request->getMethod();
                        break;
                    case 'version':
                        $result = $request->getProtocolVersion();
                        break;
                    case 'uri':
                    case 'url':
                        $result = $request->getUri();
                        break;
                    case 'target':
                        $result = $request->getRequestTarget();
                        break;
                    case 'req_version':
                        $result = $request->getProtocolVersion();
                        break;
                    case 'res_version':
                        $result = $response
                            ? $response->getProtocolVersion()
                            : 'NULL';
                        break;
                    case 'host':
                        $result = $request->getHeaderLine('Host');
                        break;
                    case 'hostname':
                        $result = gethostname();
                        break;
                    case 'code':
                        $result = $response ? $response->getStatusCode() : 'NULL';
                        break;
                    case 'phrase':
                        $result = $response ? $response->getReasonPhrase() : 'NULL';
                        break;
                    case 'error':
                        $result = $error ? $error->getMessage() : 'NULL';
                        break;
                    default:
                        // handle prefixed dynamic headers
                        if (strpos($matches[1], 'req_header_') === 0) {
                            $result = $request->getHeaderLine(substr($matches[1], 11));
                        } elseif (strpos($matches[1], 'res_header_') === 0) {
                            $result = $response
                                ? $response->getHeaderLine(substr($matches[1], 11))
                                : 'NULL';
                        }
                }

                $cache[$matches[1]] = $result;
                return $result;
            },
            $this->template
        );
    }

    private function headers(MessageInterface $message)
    {
        $result = '';
        foreach ($message->getHeaders() as $name => $values) {
            $result .= $name . ': ' . implode(', ', $values) . "\r\n";
        }

        return trim($result);
    }
}
<?php
namespace GuzzleHttp;

use GuzzleHttp\Cookie\CookieJarInterface;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Promise\RejectedPromise;
use GuzzleHttp\Psr7;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;

/**
 * Functions used to create and wrap handlers with handler middleware.
 */
final class Middleware
{
    /**
     * Middleware that adds cookies to requests.
     *
     * The options array must be set to a CookieJarInterface in order to use
     * cookies. This is typically handled for you by a client.
     *
     * @return callable Returns a function that accepts the next handler.
     */
    public static function cookies()
    {
        return function (callable $handler) {
            return function ($request, array $options) use ($handler) {
                if (empty($options['cookies'])) {
                    return $handler($request, $options);
                } elseif (!($options['cookies'] instanceof CookieJarInterface)) {
                    throw new \InvalidArgumentException('cookies must be an instance of GuzzleHttp\Cookie\CookieJarInterface');
                }
                $cookieJar = $options['cookies'];
                $request = $cookieJar->withCookieHeader($request);
                return $handler($request, $options)
                    ->then(function ($response) use ($cookieJar, $request) {
                        $cookieJar->extractCookies($request, $response);
                        return $response;
                    }
                );
            };
        };
    }

    /**
     * Middleware that throws exceptions for 4xx or 5xx responses when the
     * "http_error" request option is set to true.
     *
     * @return callable Returns a function that accepts the next handler.
     */
    public static function httpErrors()
    {
        return function (callable $handler) {
            return function ($request, array $options) use ($handler) {
                if (empty($options['http_errors'])) {
                    return $handler($request, $options);
                }
                return $handler($request, $options)->then(
                    function (ResponseInterface $response) use ($request, $handler) {
                        $code = $response->getStatusCode();
                        if ($code < 400) {
                            return $response;
                        }
                        throw RequestException::create($request, $response);
                    }
                );
            };
        };
    }

    /**
     * Middleware that pushes history data to an ArrayAccess container.
     *
     * @param array $container Container to hold the history (by reference).
     *
     * @return callable Returns a function that accepts the next handler.
     */
    public static function history(array &$container)
    {
        return function (callable $handler) use (&$container) {
            return function ($request, array $options) use ($handler, &$container) {
                return $handler($request, $options)->then(
                    function ($value) use ($request, &$container, $options) {
                        $container[] = [
                            'request'  => $request,
                            'response' => $value,
                            'error'    => null,
                            'options'  => $options
                        ];
                        return $value;
                    },
                    function ($reason) use ($request, &$container, $options) {
                        $container[] = [
                            'request'  => $request,
                            'response' => null,
                            'error'    => $reason,
                            'options'  => $options
                        ];
                        return new RejectedPromise($reason);
                    }
                );
            };
        };
    }

    /**
     * Middleware that invokes a callback before and after sending a request.
     *
     * The provided listener cannot modify or alter the response. It simply
     * "taps" into the chain to be notified before returning the promise. The
     * before listener accepts a request and options array, and the after
     * listener accepts a request, options array, and response promise.
     *
     * @param callable $before Function to invoke before forwarding the request.
     * @param callable $after  Function invoked after forwarding.
     *
     * @return callable Returns a function that accepts the next handler.
     */
    public static function tap(callable $before = null, callable $after = null)
    {
        return function (callable $handler) use ($before, $after) {
            return function ($request, array $options) use ($handler, $before, $after) {
                if ($before) {
                    $before($request, $options);
                }
                $response = $handler($request, $options);
                if ($after) {
                    $after($request, $options, $response);
                }
                return $response;
            };
        };
    }

    /**
     * Middleware that handles request redirects.
     *
     * @return callable Returns a function that accepts the next handler.
     */
    public static function redirect()
    {
        return function (callable $handler) {
            return new RedirectMiddleware($handler);
        };
    }

    /**
     * Middleware that retries requests based on the boolean result of
     * invoking the provided "decider" function.
     *
     * If no delay function is provided, a simple implementation of exponential
     * backoff will be utilized.
     *
     * @param callable $decider Function that accepts the number of retries,
     *                          a request, [response], and [exception] and
     *                          returns true if the request is to be retried.
     * @param callable $delay   Function that accepts the number of retries and
     *                          returns the number of milliseconds to delay.
     *
     * @return callable Returns a function that accepts the next handler.
     */
    public static function retry(callable $decider, callable $delay = null)
    {
        return function (callable $handler) use ($decider, $delay) {
            return new RetryMiddleware($decider, $handler, $delay);
        };
    }

    /**
     * Middleware that logs requests, responses, and errors using a message
     * formatter.
     *
     * @param LoggerInterface  $logger Logs messages.
     * @param MessageFormatter $formatter Formatter used to create message strings.
     * @param string           $logLevel Level at which to log requests.
     *
     * @return callable Returns a function that accepts the next handler.
     */
    public static function log(LoggerInterface $logger, MessageFormatter $formatter, $logLevel = LogLevel::INFO)
    {
        return function (callable $handler) use ($logger, $formatter, $logLevel) {
            return function ($request, array $options) use ($handler, $logger, $formatter, $logLevel) {
                return $handler($request, $options)->then(
                    function ($response) use ($logger, $request, $formatter, $logLevel) {
                        $message = $formatter->format($request, $response);
                        $logger->log($logLevel, $message);
                        return $response;
                    },
                    function ($reason) use ($logger, $request, $formatter) {
                        $response = $reason instanceof RequestException
                            ? $reason->getResponse()
                            : null;
                        $message = $formatter->format($request, $response, $reason);
                        $logger->notice($message);
                        return \GuzzleHttp\Promise\rejection_for($reason);
                    }
                );
            };
        };
    }

    /**
     * This middleware adds a default content-type if possible, a default
     * content-length or transfer-encoding header, and the expect header.
     *
     * @return callable
     */
    public static function prepareBody()
    {
        return function (callable $handler) {
            return new PrepareBodyMiddleware($handler);
        };
    }

    /**
     * Middleware that applies a map function to the request before passing to
     * the next handler.
     *
     * @param callable $fn Function that accepts a RequestInterface and returns
     *                     a RequestInterface.
     * @return callable
     */
    public static function mapRequest(callable $fn)
    {
        return function (callable $handler) use ($fn) {
            return function ($request, array $options) use ($handler, $fn) {
                return $handler($fn($request), $options);
            };
        };
    }

    /**
     * Middleware that applies a map function to the resolved promise's
     * response.
     *
     * @param callable $fn Function that accepts a ResponseInterface and
     *                     returns a ResponseInterface.
     * @return callable
     */
    public static function mapResponse(callable $fn)
    {
        return function (callable $handler) use ($fn) {
            return function ($request, array $options) use ($handler, $fn) {
                return $handler($request, $options)->then($fn);
            };
        };
    }
}
<?php
namespace GuzzleHttp;

use GuzzleHttp\Promise\PromisorInterface;
use Psr\Http\Message\RequestInterface;
use GuzzleHttp\Promise\EachPromise;

/**
 * Sends and iterator of requests concurrently using a capped pool size.
 *
 * The pool will read from an iterator until it is cancelled or until the
 * iterator is consumed. When a request is yielded, the request is sent after
 * applying the "request_options" request options (if provided in the ctor).
 *
 * When a function is yielded by the iterator, the function is provided the
 * "request_options" array that should be merged on top of any existing
 * options, and the function MUST then return a wait-able promise.
 */
class Pool implements PromisorInterface
{
    /** @var EachPromise */
    private $each;

    /**
     * @param ClientInterface $client   Client used to send the requests.
     * @param array|\Iterator $requests Requests or functions that return
     *                                  requests to send concurrently.
     * @param array           $config   Associative array of options
     *     - concurrency: (int) Maximum number of requests to send concurrently
     *     - options: Array of request options to apply to each request.
     *     - fulfilled: (callable) Function to invoke when a request completes.
     *     - rejected: (callable) Function to invoke when a request is rejected.
     */
    public function __construct(
        ClientInterface $client,
        $requests,
        array $config = []
    ) {
        // Backwards compatibility.
        if (isset($config['pool_size'])) {
            $config['concurrency'] = $config['pool_size'];
        } elseif (!isset($config['concurrency'])) {
            $config['concurrency'] = 25;
        }

        if (isset($config['options'])) {
            $opts = $config['options'];
            unset($config['options']);
        } else {
            $opts = [];
        }

        $iterable = \GuzzleHttp\Promise\iter_for($requests);
        $requests = function () use ($iterable, $client, $opts) {
            foreach ($iterable as $key => $rfn) {
                if ($rfn instanceof RequestInterface) {
                    yield $key => $client->sendAsync($rfn, $opts);
                } elseif (is_callable($rfn)) {
                    yield $key => $rfn($opts);
                } else {
                    throw new \InvalidArgumentException('Each value yielded by '
                        . 'the iterator must be a Psr7\Http\Message\RequestInterface '
                        . 'or a callable that returns a promise that fulfills '
                        . 'with a Psr7\Message\Http\ResponseInterface object.');
                }
            }
        };

        $this->each = new EachPromise($requests(), $config);
    }

    public function promise()
    {
        return $this->each->promise();
    }

    /**
     * Sends multiple requests concurrently and returns an array of responses
     * and exceptions that uses the same ordering as the provided requests.
     *
     * IMPORTANT: This method keeps every request and response in memory, and
     * as such, is NOT recommended when sending a large number or an
     * indeterminate number of requests concurrently.
     *
     * @param ClientInterface $client   Client used to send the requests
     * @param array|\Iterator $requests Requests to send concurrently.
     * @param array           $options  Passes through the options available in
     *                                  {@see GuzzleHttp\Pool::__construct}
     *
     * @return array Returns an array containing the response or an exception
     *               in the same order that the requests were sent.
     * @throws \InvalidArgumentException if the event format is incorrect.
     */
    public static function batch(
        ClientInterface $client,
        $requests,
        array $options = []
    ) {
        $res = [];
        self::cmpCallback($options, 'fulfilled', $res);
        self::cmpCallback($options, 'rejected', $res);
        $pool = new static($client, $requests, $options);
        $pool->promise()->wait();
        ksort($res);

        return $res;
    }

    private static function cmpCallback(array &$options, $name, array &$results)
    {
        if (!isset($options[$name])) {
            $options[$name] = function ($v, $k) use (&$results) {
                $results[$k] = $v;
            };
        } else {
            $currentFn = $options[$name];
            $options[$name] = function ($v, $k) use (&$results, $currentFn) {
                $currentFn($v, $k);
                $results[$k] = $v;
            };
        }
    }
}
<?php
namespace GuzzleHttp;

use GuzzleHttp\Promise\PromiseInterface;
use GuzzleHttp\Psr7;
use Psr\Http\Message\RequestInterface;

/**
 * Prepares requests that contain a body, adding the Content-Length,
 * Content-Type, and Expect headers.
 */
class PrepareBodyMiddleware
{
    /** @var callable  */
    private $nextHandler;

    /** @var array */
    private static $skipMethods = ['GET' => true, 'HEAD' => true];

    /**
     * @param callable $nextHandler Next handler to invoke.
     */
    public function __construct(callable $nextHandler)
    {
        $this->nextHandler = $nextHandler;
    }

    /**
     * @param RequestInterface $request
     * @param array            $options
     *
     * @return PromiseInterface
     */
    public function __invoke(RequestInterface $request, array $options)
    {
        $fn = $this->nextHandler;

        // Don't do anything if the request has no body.
        if (isset(self::$skipMethods[$request->getMethod()])
            || $request->getBody()->getSize() === 0
        ) {
            return $fn($request, $options);
        }

        $modify = [];

        // Add a default content-type if possible.
        if (!$request->hasHeader('Content-Type')) {
            if ($uri = $request->getBody()->getMetadata('uri')) {
                if ($type = Psr7\mimetype_from_filename($uri)) {
                    $modify['set_headers']['Content-Type'] = $type;
                }
            }
        }

        // Add a default content-length or transfer-encoding header.
        if (!isset(self::$skipMethods[$request->getMethod()])
            && !$request->hasHeader('Content-Length')
            && !$request->hasHeader('Transfer-Encoding')
        ) {
            $size = $request->getBody()->getSize();
            if ($size !== null) {
                $modify['set_headers']['Content-Length'] = $size;
            } else {
                $modify['set_headers']['Transfer-Encoding'] = 'chunked';
            }
        }

        // Add the expect header if needed.
        $this->addExpectHeader($request, $options, $modify);

        return $fn(Psr7\modify_request($request, $modify), $options);
    }

    private function addExpectHeader(
        RequestInterface $request,
        array $options,
        array &$modify
    ) {
        // Determine if the Expect header should be used
        if ($request->hasHeader('Expect')) {
            return;
        }

        $expect = isset($options['expect']) ? $options['expect'] : null;

        // Return if disabled or if you're not using HTTP/1.1 or HTTP/2.0
        if ($expect === false || $request->getProtocolVersion() < 1.1) {
            return;
        }

        // The expect header is unconditionally enabled
        if ($expect === true) {
            $modify['set_headers']['Expect'] = '100-Continue';
            return;
        }

        // By default, send the expect header when the payload is > 1mb
        if ($expect === null) {
            $expect = 1048576;
        }

        // Always add if the body cannot be rewound, the size cannot be
        // determined, or the size is greater than the cutoff threshold
        $body = $request->getBody();
        $size = $body->getSize();

        if ($size === null || $size >= (int) $expect || !$body->isSeekable()) {
            $modify['set_headers']['Expect'] = '100-Continue';
        }
    }
}
<?php
namespace GuzzleHttp;

use GuzzleHttp\Exception\BadResponseException;
use GuzzleHttp\Exception\TooManyRedirectsException;
use GuzzleHttp\Promise\PromiseInterface;
use GuzzleHttp\Psr7;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\UriInterface;

/**
 * Request redirect middleware.
 *
 * Apply this middleware like other middleware using
 * {@see GuzzleHttp\Middleware::redirect()}.
 */
class RedirectMiddleware
{
    const HISTORY_HEADER = 'X-Guzzle-Redirect-History';

    public static $defaultSettings = [
        'max'             => 5,
        'protocols'       => ['http', 'https'],
        'strict'          => false,
        'referer'         => false,
        'track_redirects' => false,
    ];

    /** @var callable  */
    private $nextHandler;

    /**
     * @param callable $nextHandler Next handler to invoke.
     */
    public function __construct(callable $nextHandler)
    {
        $this->nextHandler = $nextHandler;
    }

    /**
     * @param RequestInterface $request
     * @param array            $options
     *
     * @return PromiseInterface
     */
    public function __invoke(RequestInterface $request, array $options)
    {
        $fn = $this->nextHandler;

        if (empty($options['allow_redirects'])) {
            return $fn($request, $options);
        }

        if ($options['allow_redirects'] === true) {
            $options['allow_redirects'] = self::$defaultSettings;
        } elseif (!is_array($options['allow_redirects'])) {
            throw new \InvalidArgumentException('allow_redirects must be true, false, or array');
        } else {
            // Merge the default settings with the provided settings
            $options['allow_redirects'] += self::$defaultSettings;
        }

        if (empty($options['allow_redirects']['max'])) {
            return $fn($request, $options);
        }

        return $fn($request, $options)
            ->then(function (ResponseInterface $response) use ($request, $options) {
                return $this->checkRedirect($request, $options, $response);
            });
    }

    /**
     * @param RequestInterface  $request
     * @param array             $options
     * @param ResponseInterface|PromiseInterface $response
     *
     * @return ResponseInterface|PromiseInterface
     */
    public function checkRedirect(
        RequestInterface $request,
        array $options,
        ResponseInterface $response
    ) {
        if (substr($response->getStatusCode(), 0, 1) != '3'
            || !$response->hasHeader('Location')
        ) {
            return $response;
        }

        $this->guardMax($request, $options);
        $nextRequest = $this->modifyRequest($request, $options, $response);

        if (isset($options['allow_redirects']['on_redirect'])) {
            call_user_func(
                $options['allow_redirects']['on_redirect'],
                $request,
                $response,
                $nextRequest->getUri()
            );
        }

        /** @var PromiseInterface|ResponseInterface $promise */
        $promise = $this($nextRequest, $options);

        // Add headers to be able to track history of redirects.
        if (!empty($options['allow_redirects']['track_redirects'])) {
            return $this->withTracking(
                $promise,
                (string) $nextRequest->getUri()
            );
        }

        return $promise;
    }

    private function withTracking(PromiseInterface $promise, $uri)
    {
        return $promise->then(
            function (ResponseInterface $response) use ($uri) {
                // Note that we are pushing to the front of the list as this
                // would be an earlier response than what is currently present
                // in the history header.
                $header = $response->getHeader(self::HISTORY_HEADER);
                array_unshift($header, $uri);
                return $response->withHeader(self::HISTORY_HEADER, $header);
            }
        );
    }

    private function guardMax(RequestInterface $request, array &$options)
    {
        $current = isset($options['__redirect_count'])
            ? $options['__redirect_count']
            : 0;
        $options['__redirect_count'] = $current + 1;
        $max = $options['allow_redirects']['max'];

        if ($options['__redirect_count'] > $max) {
            throw new TooManyRedirectsException(
                "Will not follow more than {$max} redirects",
                $request
            );
        }
    }

    /**
     * @param RequestInterface  $request
     * @param array             $options
     * @param ResponseInterface $response
     *
     * @return RequestInterface
     */
    public function modifyRequest(
        RequestInterface $request,
        array $options,
        ResponseInterface $response
    ) {
        // Request modifications to apply.
        $modify = [];
        $protocols = $options['allow_redirects']['protocols'];

        // Use a GET request if this is an entity enclosing request and we are
        // not forcing RFC compliance, but rather emulating what all browsers
        // would do.
        $statusCode = $response->getStatusCode();
        if ($statusCode == 303 ||
            ($statusCode <= 302 && $request->getBody() && !$options['allow_redirects']['strict'])
        ) {
            $modify['method'] = 'GET';
            $modify['body'] = '';
        }

        $modify['uri'] = $this->redirectUri($request, $response, $protocols);
        Psr7\rewind_body($request);

        // Add the Referer header if it is told to do so and only
        // add the header if we are not redirecting from https to http.
        if ($options['allow_redirects']['referer']
            && $modify['uri']->getScheme() === $request->getUri()->getScheme()
        ) {
            $uri = $request->getUri()->withUserInfo('', '');
            $modify['set_headers']['Referer'] = (string) $uri;
        } else {
            $modify['remove_headers'][] = 'Referer';
        }

        // Remove Authorization header if host is different.
        if ($request->getUri()->getHost() !== $modify['uri']->getHost()) {
            $modify['remove_headers'][] = 'Authorization';
        }

        return Psr7\modify_request($request, $modify);
    }

    /**
     * Set the appropriate URL on the request based on the location header
     *
     * @param RequestInterface  $request
     * @param ResponseInterface $response
     * @param array             $protocols
     *
     * @return UriInterface
     */
    private function redirectUri(
        RequestInterface $request,
        ResponseInterface $response,
        array $protocols
    ) {
        $location = Psr7\Uri::resolve(
            $request->getUri(),
            $response->getHeaderLine('Location')
        );

        // Ensure that the redirect URI is allowed based on the protocols.
        if (!in_array($location->getScheme(), $protocols)) {
            throw new BadResponseException(
                sprintf(
                    'Redirect URI, %s, does not use one of the allowed redirect protocols: %s',
                    $location,
                    implode(', ', $protocols)
                ),
                $request,
                $response
            );
        }

        return $location;
    }
}
<?php
namespace GuzzleHttp;

/**
 * This class contains a list of built-in Guzzle request options.
 *
 * More documentation for each option can be found at http://guzzlephp.org/.
 *
 * @link http://docs.guzzlephp.org/en/v6/request-options.html
 */
final class RequestOptions
{
    /**
     * allow_redirects: (bool|array) Controls redirect behavior. Pass false
     * to disable redirects, pass true to enable redirects, pass an
     * associative to provide custom redirect settings. Defaults to "false".
     * This option only works if your handler has the RedirectMiddleware. When
     * passing an associative array, you can provide the following key value
     * pairs:
     *
     * - max: (int, default=5) maximum number of allowed redirects.
     * - strict: (bool, default=false) Set to true to use strict redirects
     *   meaning redirect POST requests with POST requests vs. doing what most
     *   browsers do which is redirect POST requests with GET requests
     * - referer: (bool, default=true) Set to false to disable the Referer
     *   header.
     * - protocols: (array, default=['http', 'https']) Allowed redirect
     *   protocols.
     * - on_redirect: (callable) PHP callable that is invoked when a redirect
     *   is encountered. The callable is invoked with the request, the redirect
     *   response that was received, and the effective URI. Any return value
     *   from the on_redirect function is ignored.
     */
    const ALLOW_REDIRECTS = 'allow_redirects';

    /**
     * auth: (array) Pass an array of HTTP authentication parameters to use
     * with the request. The array must contain the username in index [0],
     * the password in index [1], and you can optionally provide a built-in
     * authentication type in index [2]. Pass null to disable authentication
     * for a request.
     */
    const AUTH = 'auth';

    /**
     * body: (string|null|callable|iterator|object) Body to send in the
     * request.
     */
    const BODY = 'body';

    /**
     * cert: (string|array) Set to a string to specify the path to a file
     * containing a PEM formatted SSL client side certificate. If a password
     * is required, then set cert to an array containing the path to the PEM
     * file in the first array element followed by the certificate password
     * in the second array element.
     */
    const CERT = 'cert';

    /**
     * cookies: (bool|GuzzleHttp\Cookie\CookieJarInterface, default=false)
     * Specifies whether or not cookies are used in a request or what cookie
     * jar to use or what cookies to send. This option only works if your
     * handler has the `cookie` middleware. Valid values are `false` and
     * an instance of {@see GuzzleHttp\Cookie\CookieJarInterface}.
     */
    const COOKIES = 'cookies';

    /**
     * connect_timeout: (float, default=0) Float describing the number of
     * seconds to wait while trying to connect to a server. Use 0 to wait
     * indefinitely (the default behavior).
     */
    const CONNECT_TIMEOUT = 'connect_timeout';

    /**
     * debug: (bool|resource) Set to true or set to a PHP stream returned by
     * fopen()  enable debug output with the HTTP handler used to send a
     * request.
     */
    const DEBUG = 'debug';

    /**
     * decode_content: (bool, default=true) Specify whether or not
     * Content-Encoding responses (gzip, deflate, etc.) are automatically
     * decoded.
     */
    const DECODE_CONTENT = 'decode_content';

    /**
     * delay: (int) The amount of time to delay before sending in milliseconds.
     */
    const DELAY = 'delay';

    /**
     * expect: (bool|integer) Controls the behavior of the
     * "Expect: 100-Continue" header.
     *
     * Set to `true` to enable the "Expect: 100-Continue" header for all
     * requests that sends a body. Set to `false` to disable the
     * "Expect: 100-Continue" header for all requests. Set to a number so that
     * the size of the payload must be greater than the number in order to send
     * the Expect header. Setting to a number will send the Expect header for
     * all requests in which the size of the payload cannot be determined or
     * where the body is not rewindable.
     *
     * By default, Guzzle will add the "Expect: 100-Continue" header when the
     * size of the body of a request is greater than 1 MB and a request is
     * using HTTP/1.1.
     */
    const EXPECT = 'expect';

    /**
     * form_params: (array) Associative array of form field names to values
     * where each value is a string or array of strings. Sets the Content-Type
     * header to application/x-www-form-urlencoded when no Content-Type header
     * is already present.
     */
    const FORM_PARAMS = 'form_params';

    /**
     * headers: (array) Associative array of HTTP headers. Each value MUST be
     * a string or array of strings.
     */
    const HEADERS = 'headers';

    /**
     * http_errors: (bool, default=true) Set to false to disable exceptions
     * when a non- successful HTTP response is received. By default,
     * exceptions will be thrown for 4xx and 5xx responses. This option only
     * works if your handler has the `httpErrors` middleware.
     */
    const HTTP_ERRORS = 'http_errors';

    /**
     * json: (mixed) Adds JSON data to a request. The provided value is JSON
     * encoded and a Content-Type header of application/json will be added to
     * the request if no Content-Type header is already present.
     */
    const JSON = 'json';

    /**
     * multipart: (array) Array of associative arrays, each containing a
     * required "name" key mapping to the form field, name, a required
     * "contents" key mapping to a StreamInterface|resource|string, an
     * optional "headers" associative array of custom headers, and an
     * optional "filename" key mapping to a string to send as the filename in
     * the part. If no "filename" key is present, then no "filename" attribute
     * will be added to the part.
     */
    const MULTIPART = 'multipart';

    /**
     * on_headers: (callable) A callable that is invoked when the HTTP headers
     * of the response have been received but the body has not yet begun to
     * download.
     */
    const ON_HEADERS = 'on_headers';

    /**
     * on_stats: (callable) allows you to get access to transfer statistics of
     * a request and access the lower level transfer details of the handler
     * associated with your client. ``on_stats`` is a callable that is invoked
     * when a handler has finished sending a request. The callback is invoked
     * with transfer statistics about the request, the response received, or
     * the error encountered. Included in the data is the total amount of time
     * taken to send the request.
     */
    const ON_STATS = 'on_stats';

    /**
     * progress: (callable) Defines a function to invoke when transfer
     * progress is made. The function accepts the following positional
     * arguments: the total number of bytes expected to be downloaded, the
     * number of bytes downloaded so far, the number of bytes expected to be
     * uploaded, the number of bytes uploaded so far.
     */
    const PROGRESS = 'progress';

    /**
     * proxy: (string|array) Pass a string to specify an HTTP proxy, or an
     * array to specify different proxies for different protocols (where the
     * key is the protocol and the value is a proxy string).
     */
    const PROXY = 'proxy';

    /**
     * query: (array|string) Associative array of query string values to add
     * to the request. This option uses PHP's http_build_query() to create
     * the string representation. Pass a string value if you need more
     * control than what this method provides
     */
    const QUERY = 'query';

    /**
     * sink: (resource|string|StreamInterface) Where the data of the
     * response is written to. Defaults to a PHP temp stream. Providing a
     * string will write data to a file by the given name.
     */
    const SINK = 'sink';

    /**
     * synchronous: (bool) Set to true to inform HTTP handlers that you intend
     * on waiting on the response. This can be useful for optimizations. Note
     * that a promise is still returned if you are using one of the async
     * client methods.
     */
    const SYNCHRONOUS = 'synchronous';

    /**
     * ssl_key: (array|string) Specify the path to a file containing a private
     * SSL key in PEM format. If a password is required, then set to an array
     * containing the path to the SSL key in the first array element followed
     * by the password required for the certificate in the second element.
     */
    const SSL_KEY = 'ssl_key';

    /**
     * stream: Set to true to attempt to stream a response rather than
     * download it all up-front.
     */
    const STREAM = 'stream';

    /**
     * verify: (bool|string, default=true) Describes the SSL certificate
     * verification behavior of a request. Set to true to enable SSL
     * certificate verification using the system CA bundle when available
     * (the default). Set to false to disable certificate verification (this
     * is insecure!). Set to a string to provide the path to a CA bundle on
     * disk to enable verification using a custom certificate.
     */
    const VERIFY = 'verify';

    /**
     * timeout: (float, default=0) Float describing the timeout of the
     * request in seconds. Use 0 to wait indefinitely (the default behavior).
     */
    const TIMEOUT = 'timeout';

    /**
     * version: (float) Specifies the HTTP protocol version to attempt to use.
     */
    const VERSION = 'version';
}
<?php
namespace GuzzleHttp;

use GuzzleHttp\Promise\PromiseInterface;
use GuzzleHttp\Promise\RejectedPromise;
use GuzzleHttp\Psr7;
use Psr\Http\Message\RequestInterface;

/**
 * Middleware that retries requests based on the boolean result of
 * invoking the provided "decider" function.
 */
class RetryMiddleware
{
    /** @var callable  */
    private $nextHandler;

    /** @var callable */
    private $decider;

    /**
     * @param callable $decider     Function that accepts the number of retries,
     *                              a request, [response], and [exception] and
     *                              returns true if the request is to be
     *                              retried.
     * @param callable $nextHandler Next handler to invoke.
     * @param callable $delay       Function that accepts the number of retries
     *                              and returns the number of milliseconds to
     *                              delay.
     */
    public function __construct(
        callable $decider,
        callable $nextHandler,
        callable $delay = null
    ) {
        $this->decider = $decider;
        $this->nextHandler = $nextHandler;
        $this->delay = $delay ?: __CLASS__ . '::exponentialDelay';
    }

    /**
     * Default exponential backoff delay function.
     *
     * @param $retries
     *
     * @return int
     */
    public static function exponentialDelay($retries)
    {
        return (int) pow(2, $retries - 1);
    }

    /**
     * @param RequestInterface $request
     * @param array            $options
     *
     * @return PromiseInterface
     */
    public function __invoke(RequestInterface $request, array $options)
    {
        if (!isset($options['retries'])) {
            $options['retries'] = 0;
        }

        $fn = $this->nextHandler;
        return $fn($request, $options)
            ->then(
                $this->onFulfilled($request, $options),
                $this->onRejected($request, $options)
            );
    }

    private function onFulfilled(RequestInterface $req, array $options)
    {
        return function ($value) use ($req, $options) {
            if (!call_user_func(
                $this->decider,
                $options['retries'],
                $req,
                $value,
                null
            )) {
                return $value;
            }
            return $this->doRetry($req, $options);
        };
    }

    private function onRejected(RequestInterface $req, array $options)
    {
        return function ($reason) use ($req, $options) {
            if (!call_user_func(
                $this->decider,
                $options['retries'],
                $req,
                null,
                $reason
            )) {
                return new RejectedPromise($reason);
            }
            return $this->doRetry($req, $options);
        };
    }

    private function doRetry(RequestInterface $request, array $options)
    {
        $options['delay'] = call_user_func($this->delay, ++$options['retries']);

        return $this($request, $options);
    }
}
<?php
namespace GuzzleHttp;

use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\UriInterface;

/**
 * Represents data at the point after it was transferred either successfully
 * or after a network error.
 */
final class TransferStats
{
    private $request;
    private $response;
    private $transferTime;
    private $handlerStats;
    private $handlerErrorData;

    /**
     * @param RequestInterface  $request          Request that was sent.
     * @param ResponseInterface $response         Response received (if any)
     * @param null              $transferTime     Total handler transfer time.
     * @param mixed             $handlerErrorData Handler error data.
     * @param array             $handlerStats     Handler specific stats.
     */
    public function __construct(
        RequestInterface $request,
        ResponseInterface $response = null,
        $transferTime = null,
        $handlerErrorData = null,
        $handlerStats = []
    ) {
        $this->request = $request;
        $this->response = $response;
        $this->transferTime = $transferTime;
        $this->handlerErrorData = $handlerErrorData;
        $this->handlerStats = $handlerStats;
    }

    /**
     * @return RequestInterface
     */
    public function getRequest()
    {
        return $this->request;
    }

    /**
     * Returns the response that was received (if any).
     *
     * @return ResponseInterface|null
     */
    public function getResponse()
    {
        return $this->response;
    }

    /**
     * Returns true if a response was received.
     *
     * @return bool
     */
    public function hasResponse()
    {
        return $this->response !== null;
    }

    /**
     * Gets handler specific error data.
     *
     * This might be an exception, a integer representing an error code, or
     * anything else. Relying on this value assumes that you know what handler
     * you are using.
     *
     * @return mixed
     */
    public function getHandlerErrorData()
    {
        return $this->handlerErrorData;
    }

    /**
     * Get the effective URI the request was sent to.
     *
     * @return UriInterface
     */
    public function getEffectiveUri()
    {
        return $this->request->getUri();
    }

    /**
     * Get the estimated time the request was being transferred by the handler.
     *
     * @return float Time in seconds.
     */
    public function getTransferTime()
    {
        return $this->transferTime;
    }

    /**
     * Gets an array of all of the handler specific transfer data.
     *
     * @return array
     */
    public function getHandlerStats()
    {
        return $this->handlerStats;
    }

    /**
     * Get a specific handler statistic from the handler by name.
     *
     * @param string $stat Handler specific transfer stat to retrieve.
     *
     * @return mixed|null
     */
    public function getHandlerStat($stat)
    {
        return isset($this->handlerStats[$stat])
            ? $this->handlerStats[$stat]
            : null;
    }
}
<?php
namespace GuzzleHttp;

/**
 * Expands URI templates. Userland implementation of PECL uri_template.
 *
 * @link http://tools.ietf.org/html/rfc6570
 */
class UriTemplate
{
    /** @var string URI template */
    private $template;

    /** @var array Variables to use in the template expansion */
    private $variables;

    /** @var array Hash for quick operator lookups */
    private static $operatorHash = array(
        ''  => array('prefix' => '',  'joiner' => ',', 'query' => false),
        '+' => array('prefix' => '',  'joiner' => ',', 'query' => false),
        '#' => array('prefix' => '#', 'joiner' => ',', 'query' => false),
        '.' => array('prefix' => '.', 'joiner' => '.', 'query' => false),
        '/' => array('prefix' => '/', 'joiner' => '/', 'query' => false),
        ';' => array('prefix' => ';', 'joiner' => ';', 'query' => true),
        '?' => array('prefix' => '?', 'joiner' => '&', 'query' => true),
        '&' => array('prefix' => '&', 'joiner' => '&', 'query' => true)
    );

    /** @var array Delimiters */
    private static $delims = array(':', '/', '?', '#', '[', ']', '@', '!', '$',
        '&', '\'', '(', ')', '*', '+', ',', ';', '=');

    /** @var array Percent encoded delimiters */
    private static $delimsPct = array('%3A', '%2F', '%3F', '%23', '%5B', '%5D',
        '%40', '%21', '%24', '%26', '%27', '%28', '%29', '%2A', '%2B', '%2C',
        '%3B', '%3D');

    public function expand($template, array $variables)
    {
        if (false === strpos($template, '{')) {
            return $template;
        }

        $this->template = $template;
        $this->variables = $variables;

        return preg_replace_callback(
            '/\{([^\}]+)\}/',
            [$this, 'expandMatch'],
            $this->template
        );
    }

    /**
     * Parse an expression into parts
     *
     * @param string $expression Expression to parse
     *
     * @return array Returns an associative array of parts
     */
    private function parseExpression($expression)
    {
        $result = array();

        if (isset(self::$operatorHash[$expression[0]])) {
            $result['operator'] = $expression[0];
            $expression = substr($expression, 1);
        } else {
            $result['operator'] = '';
        }

        foreach (explode(',', $expression) as $value) {
            $value = trim($value);
            $varspec = array();
            if ($colonPos = strpos($value, ':')) {
                $varspec['value'] = substr($value, 0, $colonPos);
                $varspec['modifier'] = ':';
                $varspec['position'] = (int) substr($value, $colonPos + 1);
            } elseif (substr($value, -1) == '*') {
                $varspec['modifier'] = '*';
                $varspec['value'] = substr($value, 0, -1);
            } else {
                $varspec['value'] = (string) $value;
                $varspec['modifier'] = '';
            }
            $result['values'][] = $varspec;
        }

        return $result;
    }

    /**
     * Process an expansion
     *
     * @param array $matches Matches met in the preg_replace_callback
     *
     * @return string Returns the replacement string
     */
    private function expandMatch(array $matches)
    {
        static $rfc1738to3986 = array('+' => '%20', '%7e' => '~');

        $replacements = array();
        $parsed = self::parseExpression($matches[1]);
        $prefix = self::$operatorHash[$parsed['operator']]['prefix'];
        $joiner = self::$operatorHash[$parsed['operator']]['joiner'];
        $useQuery = self::$operatorHash[$parsed['operator']]['query'];

        foreach ($parsed['values'] as $value) {

            if (!isset($this->variables[$value['value']])) {
                continue;
            }

            $variable = $this->variables[$value['value']];
            $actuallyUseQuery = $useQuery;
            $expanded = '';

            if (is_array($variable)) {

                $isAssoc = $this->isAssoc($variable);
                $kvp = array();
                foreach ($variable as $key => $var) {

                    if ($isAssoc) {
                        $key = rawurlencode($key);
                        $isNestedArray = is_array($var);
                    } else {
                        $isNestedArray = false;
                    }

                    if (!$isNestedArray) {
                        $var = rawurlencode($var);
                        if ($parsed['operator'] == '+' ||
                            $parsed['operator'] == '#'
                        ) {
                            $var = $this->decodeReserved($var);
                        }
                    }

                    if ($value['modifier'] == '*') {
                        if ($isAssoc) {
                            if ($isNestedArray) {
                                // Nested arrays must allow for deeply nested
                                // structures.
                                $var = strtr(
                                    http_build_query([$key => $var]),
                                    $rfc1738to3986
                                );
                            } else {
                                $var = $key . '=' . $var;
                            }
                        } elseif ($key > 0 && $actuallyUseQuery) {
                            $var = $value['value'] . '=' . $var;
                        }
                    }

                    $kvp[$key] = $var;
                }

                if (empty($variable)) {
                    $actuallyUseQuery = false;
                } elseif ($value['modifier'] == '*') {
                    $expanded = implode($joiner, $kvp);
                    if ($isAssoc) {
                        // Don't prepend the value name when using the explode
                        // modifier with an associative array.
                        $actuallyUseQuery = false;
                    }
                } else {
                    if ($isAssoc) {
                        // When an associative array is encountered and the
                        // explode modifier is not set, then the result must be
                        // a comma separated list of keys followed by their
                        // respective values.
                        foreach ($kvp as $k => &$v) {
                            $v = $k . ',' . $v;
                        }
                    }
                    $expanded = implode(',', $kvp);
                }

            } else {
                if ($value['modifier'] == ':') {
                    $variable = substr($variable, 0, $value['position']);
                }
                $expanded = rawurlencode($variable);
                if ($parsed['operator'] == '+' || $parsed['operator'] == '#') {
                    $expanded = $this->decodeReserved($expanded);
                }
            }

            if ($actuallyUseQuery) {
                if (!$expanded && $joiner != '&') {
                    $expanded = $value['value'];
                } else {
                    $expanded = $value['value'] . '=' . $expanded;
                }
            }

            $replacements[] = $expanded;
        }

        $ret = implode($joiner, $replacements);
        if ($ret && $prefix) {
            return $prefix . $ret;
        }

        return $ret;
    }

    /**
     * Determines if an array is associative.
     *
     * This makes the assumption that input arrays are sequences or hashes.
     * This assumption is a tradeoff for accuracy in favor of speed, but it
     * should work in almost every case where input is supplied for a URI
     * template.
     *
     * @param array $array Array to check
     *
     * @return bool
     */
    private function isAssoc(array $array)
    {
        return $array && array_keys($array)[0] !== 0;
    }

    /**
     * Removes percent encoding on reserved characters (used with + and #
     * modifiers).
     *
     * @param string $string String to fix
     *
     * @return string
     */
    private function decodeReserved($string)
    {
        return str_replace(self::$delimsPct, self::$delims, $string);
    }
}
Guzzle Upgrade Guide
====================

5.0 to 6.0
----------

Guzzle now uses [PSR-7](http://www.php-fig.org/psr/psr-7/) for HTTP messages.
Due to the fact that these messages are immutable, this prompted a refactoring
of Guzzle to use a middleware based system rather than an event system. Any
HTTP message interaction (e.g., `GuzzleHttp\Message\Request`) need to be
updated to work with the new immutable PSR-7 request and response objects. Any
event listeners or subscribers need to be updated to become middleware
functions that wrap handlers (or are injected into a
`GuzzleHttp\HandlerStack`).

- Removed `GuzzleHttp\BatchResults`
- Removed `GuzzleHttp\Collection`
- Removed `GuzzleHttp\HasDataTrait`
- Removed `GuzzleHttp\ToArrayInterface`
- The `guzzlehttp/streams` dependency has been removed. Stream functionality
  is now present in the `GuzzleHttp\Psr7` namespace provided by the
  `guzzlehttp/psr7` package.
- Guzzle no longer uses ReactPHP promises and now uses the
  `guzzlehttp/promises` library. We use a custom promise library for three
  significant reasons:
  1. React promises (at the time of writing this) are recursive. Promise
     chaining and promise resolution will eventually blow the stack. Guzzle
     promises are not recursive as they use a sort of trampolining technique.
     Note: there has been movement in the React project to modify promises to
     no longer utilize recursion.
  2. Guzzle needs to have the ability to synchronously block on a promise to
     wait for a result. Guzzle promises allows this functionality (and does
     not require the use of recursion).
  3. Because we need to be able to wait on a result, doing so using React
     promises requires wrapping react promises with RingPHP futures. This
     overhead is no longer needed, reducing stack sizes, reducing complexity,
     and improving performance.
- `GuzzleHttp\Mimetypes` has been moved to a function in
  `GuzzleHttp\Psr7\mimetype_from_extension` and
  `GuzzleHttp\Psr7\mimetype_from_filename`.
- `GuzzleHttp\Query` and `GuzzleHttp\QueryParser` have been removed. Query
  strings must now be passed into request objects as strings, or provided to
  the `query` request option when creating requests with clients. The `query`
  option uses PHP's `http_build_query` to convert an array to a string. If you
  need a different serialization technique, you will need to pass the query
  string in as a string. There are a couple helper functions that will make
  working with query strings easier: `GuzzleHttp\Psr7\parse_query` and
  `GuzzleHttp\Psr7\build_query`.
- Guzzle no longer has a dependency on RingPHP. Due to the use of a middleware
  system based on PSR-7, using RingPHP and it's middleware system as well adds
  more complexity than the benefits it provides. All HTTP handlers that were
  present in RingPHP have been modified to work directly with PSR-7 messages
  and placed in the `GuzzleHttp\Handler` namespace. This significantly reduces
  complexity in Guzzle, removes a dependency, and improves performance. RingPHP
  will be maintained for Guzzle 5 support, but will no longer be a part of
  Guzzle 6.
- As Guzzle now uses a middleware based systems the event system and RingPHP
  integration has been removed. Note: while the event system has been removed,
  it is possible to add your own type of event system that is powered by the
  middleware system.
  - Removed the `Event` namespace.
  - Removed the `Subscriber` namespace.
  - Removed `Transaction` class
  - Removed `RequestFsm`
  - Removed `RingBridge`
  - `GuzzleHttp\Subscriber\Cookie` is now provided by
    `GuzzleHttp\Middleware::cookies`
  - `GuzzleHttp\Subscriber\HttpError` is now provided by
    `GuzzleHttp\Middleware::httpError`
  - `GuzzleHttp\Subscriber\History` is now provided by
    `GuzzleHttp\Middleware::history`
  - `GuzzleHttp\Subscriber\Mock` is now provided by
    `GuzzleHttp\Handler\MockHandler`
  - `GuzzleHttp\Subscriber\Prepare` is now provided by
    `GuzzleHttp\PrepareBodyMiddleware`
  - `GuzzleHttp\Subscriber\Redirect` is now provided by
    `GuzzleHttp\RedirectMiddleware`
- Guzzle now uses `Psr\Http\Message\UriInterface` (implements in
  `GuzzleHttp\Psr7\Uri`) for URI support. `GuzzleHttp\Url` is now gone.
- Static functions in `GuzzleHttp\Utils` have been moved to namespaced
  functions under the `GuzzleHttp` namespace. This requires either a Composer
  based autoloader or you to include functions.php.
- `GuzzleHttp\ClientInterface::getDefaultOption` has been renamed to
  `GuzzleHttp\ClientInterface::getConfig`.
- `GuzzleHttp\ClientInterface::setDefaultOption` has been removed.
- The `json` and `xml` methods of response objects has been removed. With the
  migration to strictly adhering to PSR-7 as the interface for Guzzle messages,
  adding methods to message interfaces would actually require Guzzle messages
  to extend from PSR-7 messages rather then work with them directly.

## Migrating to middleware

The change to PSR-7 unfortunately required significant refactoring to Guzzle
due to the fact that PSR-7 messages are immutable. Guzzle 5 relied on an event
system from plugins. The event system relied on mutability of HTTP messages and
side effects in order to work. With immutable messages, you have to change your
workflow to become more about either returning a value (e.g., functional
middlewares) or setting a value on an object. Guzzle v6 has chosen the
functional middleware approach.

Instead of using the event system to listen for things like the `before` event,
you now create a stack based middleware function that intercepts a request on
the way in and the promise of the response on the way out. This is a much
simpler and more predictable approach than the event system and works nicely
with PSR-7 middleware. Due to the use of promises, the middleware system is
also asynchronous.

v5:

```php
use GuzzleHttp\Event\BeforeEvent;
$client = new GuzzleHttp\Client();
// Get the emitter and listen to the before event.
$client->getEmitter()->on('before', function (BeforeEvent $e) {
    // Guzzle v5 events relied on mutation
    $e->getRequest()->setHeader('X-Foo', 'Bar');
});
```

v6:

In v6, you can modify the request before it is sent using the `mapRequest`
middleware. The idiomatic way in v6 to modify the request/response lifecycle is
to setup a handler middleware stack up front and inject the handler into a
client.

```php
use GuzzleHttp\Middleware;
// Create a handler stack that has all of the default middlewares attached
$handler = GuzzleHttp\HandlerStack::create();
// Push the handler onto the handler stack
$handler->push(Middleware::mapRequest(function (RequestInterface $request) {
    // Notice that we have to return a request object
    return $request->withHeader('X-Foo', 'Bar');
});
// Inject the handler into the client
$client = new GuzzleHttp\Client(['handler' => $handler]);
```

## POST Requests

This version added the [`form_params`](http://guzzle.readthedocs.org/en/latest/request-options.html#form_params)
and `multipart` request options. `form_params` is an associative array of
strings or array of strings and is used to serialize an
`application/x-www-form-urlencoded` POST request. The
[`multipart`](http://guzzle.readthedocs.org/en/latest/request-options.html#multipart)
option is now used to send a multipart/form-data POST request.

`GuzzleHttp\Post\PostFile` has been removed. Use the `multipart` option to add
POST files to a multipart/form-data request.

The `body` option no longer accepts an array to send POST requests. Please use
`multipart` or `form_params` instead.

The `base_url` option has been renamed to `base_uri`.

4.x to 5.0
----------

## Rewritten Adapter Layer

Guzzle now uses [RingPHP](http://ringphp.readthedocs.org/en/latest) to send
HTTP requests. The `adapter` option in a `GuzzleHttp\Client` constructor
is still supported, but it has now been renamed to `handler`. Instead of
passing a `GuzzleHttp\Adapter\AdapterInterface`, you must now pass a PHP
`callable` that follows the RingPHP specification.

## Removed Fluent Interfaces

[Fluent interfaces were removed](http://ocramius.github.io/blog/fluent-interfaces-are-evil)
from the following classes:

- `GuzzleHttp\Collection`
- `GuzzleHttp\Url`
- `GuzzleHttp\Query`
- `GuzzleHttp\Post\PostBody`
- `GuzzleHttp\Cookie\SetCookie`

## Removed functions.php

Removed "functions.php", so that Guzzle is truly PSR-4 compliant. The following
functions can be used as replacements.

- `GuzzleHttp\json_decode` -> `GuzzleHttp\Utils::jsonDecode`
- `GuzzleHttp\get_path` -> `GuzzleHttp\Utils::getPath`
- `GuzzleHttp\Utils::setPath` -> `GuzzleHttp\set_path`
- `GuzzleHttp\Pool::batch` -> `GuzzleHttp\batch`. This function is, however,
  deprecated in favor of using `GuzzleHttp\Pool::batch()`.

The "procedural" global client has been removed with no replacement (e.g.,
`GuzzleHttp\get()`, `GuzzleHttp\post()`, etc.). Use a `GuzzleHttp\Client`
object as a replacement.

## `throwImmediately` has been removed

The concept of "throwImmediately" has been removed from exceptions and error
events. This control mechanism was used to stop a transfer of concurrent
requests from completing. This can now be handled by throwing the exception or
by cancelling a pool of requests or each outstanding future request
individually.

## headers event has been removed

Removed the "headers" event. This event was only useful for changing the
body a response once the headers of the response were known. You can implement
a similar behavior in a number of ways. One example might be to use a
FnStream that has access to the transaction being sent. For example, when the
first byte is written, you could check if the response headers match your
expectations, and if so, change the actual stream body that is being
written to.

## Updates to HTTP Messages

Removed the `asArray` parameter from
`GuzzleHttp\Message\MessageInterface::getHeader`. If you want to get a header
value as an array, then use the newly added `getHeaderAsArray()` method of
`MessageInterface`. This change makes the Guzzle interfaces compatible with
the PSR-7 interfaces.

3.x to 4.0
----------

## Overarching changes:

- Now requires PHP 5.4 or greater.
- No longer requires cURL to send requests.
- Guzzle no longer wraps every exception it throws. Only exceptions that are
  recoverable are now wrapped by Guzzle.
- Various namespaces have been removed or renamed.
- No longer requiring the Symfony EventDispatcher. A custom event dispatcher
  based on the Symfony EventDispatcher is
  now utilized in `GuzzleHttp\Event\EmitterInterface` (resulting in significant
  speed and functionality improvements).

Changes per Guzzle 3.x namespace are described below.

## Batch

The `Guzzle\Batch` namespace has been removed. This is best left to
third-parties to implement on top of Guzzle's core HTTP library.

## Cache

The `Guzzle\Cache` namespace has been removed. (Todo: No suitable replacement
has been implemented yet, but hoping to utilize a PSR cache interface).

## Common

- Removed all of the wrapped exceptions. It's better to use the standard PHP
  library for unrecoverable exceptions.
- `FromConfigInterface` has been removed.
- `Guzzle\Common\Version` has been removed. The VERSION constant can be found
  at `GuzzleHttp\ClientInterface::VERSION`.

### Collection

- `getAll` has been removed. Use `toArray` to convert a collection to an array.
- `inject` has been removed.
- `keySearch` has been removed.
- `getPath` no longer supports wildcard expressions. Use something better like
  JMESPath for this.
- `setPath` now supports appending to an existing array via the `[]` notation.

### Events

Guzzle no longer requires Symfony's EventDispatcher component. Guzzle now uses
`GuzzleHttp\Event\Emitter`.

- `Symfony\Component\EventDispatcher\EventDispatcherInterface` is replaced by
  `GuzzleHttp\Event\EmitterInterface`.
- `Symfony\Component\EventDispatcher\EventDispatcher` is replaced by
  `GuzzleHttp\Event\Emitter`.
- `Symfony\Component\EventDispatcher\Event` is replaced by
  `GuzzleHttp\Event\Event`, and Guzzle now has an EventInterface in
  `GuzzleHttp\Event\EventInterface`.
- `AbstractHasDispatcher` has moved to a trait, `HasEmitterTrait`, and
  `HasDispatcherInterface` has moved to `HasEmitterInterface`. Retrieving the
  event emitter of a request, client, etc. now uses the `getEmitter` method
  rather than the `getDispatcher` method.

#### Emitter

- Use the `once()` method to add a listener that automatically removes itself
  the first time it is invoked.
- Use the `listeners()` method to retrieve a list of event listeners rather than
  the `getListeners()` method.
- Use `emit()` instead of `dispatch()` to emit an event from an emitter.
- Use `attach()` instead of `addSubscriber()` and `detach()` instead of
  `removeSubscriber()`.

```php
$mock = new Mock();
// 3.x
$request->getEventDispatcher()->addSubscriber($mock);
$request->getEventDispatcher()->removeSubscriber($mock);
// 4.x
$request->getEmitter()->attach($mock);
$request->getEmitter()->detach($mock);
```

Use the `on()` method to add a listener rather than the `addListener()` method.

```php
// 3.x
$request->getEventDispatcher()->addListener('foo', function (Event $event) { /* ... */ } );
// 4.x
$request->getEmitter()->on('foo', function (Event $event, $name) { /* ... */ } );
```

## Http

### General changes

- The cacert.pem certificate has been moved to `src/cacert.pem`.
- Added the concept of adapters that are used to transfer requests over the
  wire.
- Simplified the event system.
- Sending requests in parallel is still possible, but batching is no longer a
  concept of the HTTP layer. Instead, you must use the `complete` and `error`
  events to asynchronously manage parallel request transfers.
- `Guzzle\Http\Url` has moved to `GuzzleHttp\Url`.
- `Guzzle\Http\QueryString` has moved to `GuzzleHttp\Query`.
- QueryAggregators have been rewritten so that they are simply callable
  functions.
- `GuzzleHttp\StaticClient` has been removed. Use the functions provided in
  `functions.php` for an easy to use static client instance.
- Exceptions in `GuzzleHttp\Exception` have been updated to all extend from
  `GuzzleHttp\Exception\TransferException`.

### Client

Calling methods like `get()`, `post()`, `head()`, etc. no longer create and
return a request, but rather creates a request, sends the request, and returns
the response.

```php
// 3.0
$request = $client->get('/');
$response = $request->send();

// 4.0
$response = $client->get('/');

// or, to mirror the previous behavior
$request = $client->createRequest('GET', '/');
$response = $client->send($request);
```

`GuzzleHttp\ClientInterface` has changed.

- The `send` method no longer accepts more than one request. Use `sendAll` to
  send multiple requests in parallel.
- `setUserAgent()` has been removed. Use a default request option instead. You
  could, for example, do something like:
  `$client->setConfig('defaults/headers/User-Agent', 'Foo/Bar ' . $client::getDefaultUserAgent())`.
- `setSslVerification()` has been removed. Use default request options instead,
  like `$client->setConfig('defaults/verify', true)`.

`GuzzleHttp\Client` has changed.

- The constructor now accepts only an associative array. You can include a
  `base_url` string or array to use a URI template as the base URL of a client.
  You can also specify a `defaults` key that is an associative array of default
  request options. You can pass an `adapter` to use a custom adapter,
  `batch_adapter` to use a custom adapter for sending requests in parallel, or
  a `message_factory` to change the factory used to create HTTP requests and
  responses.
- The client no longer emits a `client.create_request` event.
- Creating requests with a client no longer automatically utilize a URI
  template. You must pass an array into a creational method (e.g.,
  `createRequest`, `get`, `put`, etc.) in order to expand a URI template.

### Messages

Messages no longer have references to their counterparts (i.e., a request no
longer has a reference to it's response, and a response no loger has a
reference to its request). This association is now managed through a
`GuzzleHttp\Adapter\TransactionInterface` object. You can get references to
these transaction objects using request events that are emitted over the
lifecycle of a request.

#### Requests with a body

- `GuzzleHttp\Message\EntityEnclosingRequest` and
  `GuzzleHttp\Message\EntityEnclosingRequestInterface` have been removed. The
  separation between requests that contain a body and requests that do not
  contain a body has been removed, and now `GuzzleHttp\Message\RequestInterface`
  handles both use cases.
- Any method that previously accepts a `GuzzleHttp\Response` object now accept a
  `GuzzleHttp\Message\ResponseInterface`.
- `GuzzleHttp\Message\RequestFactoryInterface` has been renamed to
  `GuzzleHttp\Message\MessageFactoryInterface`. This interface is used to create
  both requests and responses and is implemented in
  `GuzzleHttp\Message\MessageFactory`.
- POST field and file methods have been removed from the request object. You
  must now use the methods made available to `GuzzleHttp\Post\PostBodyInterface`
  to control the format of a POST body. Requests that are created using a
  standard `GuzzleHttp\Message\MessageFactoryInterface` will automatically use
  a `GuzzleHttp\Post\PostBody` body if the body was passed as an array or if
  the method is POST and no body is provided.

```php
$request = $client->createRequest('POST', '/');
$request->getBody()->setField('foo', 'bar');
$request->getBody()->addFile(new PostFile('file_key', fopen('/path/to/content', 'r')));
```

#### Headers

- `GuzzleHttp\Message\Header` has been removed. Header values are now simply
  represented by an array of values or as a string. Header values are returned
  as a string by default when retrieving a header value from a message. You can
  pass an optional argument of `true` to retrieve a header value as an array
  of strings instead of a single concatenated string.
- `GuzzleHttp\PostFile` and `GuzzleHttp\PostFileInterface` have been moved to
  `GuzzleHttp\Post`. This interface has been simplified and now allows the
  addition of arbitrary headers.
- Custom headers like `GuzzleHttp\Message\Header\Link` have been removed. Most
  of the custom headers are now handled separately in specific
  subscribers/plugins, and `GuzzleHttp\Message\HeaderValues::parseParams()` has
  been updated to properly handle headers that contain parameters (like the
  `Link` header).

#### Responses

- `GuzzleHttp\Message\Response::getInfo()` and
  `GuzzleHttp\Message\Response::setInfo()` have been removed. Use the event
  system to retrieve this type of information.
- `GuzzleHttp\Message\Response::getRawHeaders()` has been removed.
- `GuzzleHttp\Message\Response::getMessage()` has been removed.
- `GuzzleHttp\Message\Response::calculateAge()` and other cache specific
  methods have moved to the CacheSubscriber.
- Header specific helper functions like `getContentMd5()` have been removed.
  Just use `getHeader('Content-MD5')` instead.
- `GuzzleHttp\Message\Response::setRequest()` and
  `GuzzleHttp\Message\Response::getRequest()` have been removed. Use the event
  system to work with request and response objects as a transaction.
- `GuzzleHttp\Message\Response::getRedirectCount()` has been removed. Use the
  Redirect subscriber instead.
- `GuzzleHttp\Message\Response::isSuccessful()` and other related methods have
  been removed. Use `getStatusCode()` instead.

#### Streaming responses

Streaming requests can now be created by a client directly, returning a
`GuzzleHttp\Message\ResponseInterface` object that contains a body stream
referencing an open PHP HTTP stream.

```php
// 3.0
use Guzzle\Stream\PhpStreamRequestFactory;
$request = $client->get('/');
$factory = new PhpStreamRequestFactory();
$stream = $factory->fromRequest($request);
$data = $stream->read(1024);

// 4.0
$response = $client->get('/', ['stream' => true]);
// Read some data off of the stream in the response body
$data = $response->getBody()->read(1024);
```

#### Redirects

The `configureRedirects()` method has been removed in favor of a
`allow_redirects` request option.

```php
// Standard redirects with a default of a max of 5 redirects
$request = $client->createRequest('GET', '/', ['allow_redirects' => true]);

// Strict redirects with a custom number of redirects
$request = $client->createRequest('GET', '/', [
    'allow_redirects' => ['max' => 5, 'strict' => true]
]);
```

#### EntityBody

EntityBody interfaces and classes have been removed or moved to
`GuzzleHttp\Stream`. All classes and interfaces that once required
`GuzzleHttp\EntityBodyInterface` now require
`GuzzleHttp\Stream\StreamInterface`. Creating a new body for a request no
longer uses `GuzzleHttp\EntityBody::factory` but now uses
`GuzzleHttp\Stream\Stream::factory` or even better:
`GuzzleHttp\Stream\create()`.

- `Guzzle\Http\EntityBodyInterface` is now `GuzzleHttp\Stream\StreamInterface`
- `Guzzle\Http\EntityBody` is now `GuzzleHttp\Stream\Stream`
- `Guzzle\Http\CachingEntityBody` is now `GuzzleHttp\Stream\CachingStream`
- `Guzzle\Http\ReadLimitEntityBody` is now `GuzzleHttp\Stream\LimitStream`
- `Guzzle\Http\IoEmittyinEntityBody` has been removed.

#### Request lifecycle events

Requests previously submitted a large number of requests. The number of events
emitted over the lifecycle of a request has been significantly reduced to make
it easier to understand how to extend the behavior of a request. All events
emitted during the lifecycle of a request now emit a custom
`GuzzleHttp\Event\EventInterface` object that contains context providing
methods and a way in which to modify the transaction at that specific point in
time (e.g., intercept the request and set a response on the transaction).

- `request.before_send` has been renamed to `before` and now emits a
  `GuzzleHttp\Event\BeforeEvent`
- `request.complete` has been renamed to `complete` and now emits a
  `GuzzleHttp\Event\CompleteEvent`.
- `request.sent` has been removed. Use `complete`.
- `request.success` has been removed. Use `complete`.
- `error` is now an event that emits a `GuzzleHttp\Event\ErrorEvent`.
- `request.exception` has been removed. Use `error`.
- `request.receive.status_line` has been removed.
- `curl.callback.progress` has been removed. Use a custom `StreamInterface` to
  maintain a status update.
- `curl.callback.write` has been removed. Use a custom `StreamInterface` to
  intercept writes.
- `curl.callback.read` has been removed. Use a custom `StreamInterface` to
  intercept reads.

`headers` is a new event that is emitted after the response headers of a
request have been received before the body of the response is downloaded. This
event emits a `GuzzleHttp\Event\HeadersEvent`.

You can intercept a request and inject a response using the `intercept()` event
of a `GuzzleHttp\Event\BeforeEvent`, `GuzzleHttp\Event\CompleteEvent`, and
`GuzzleHttp\Event\ErrorEvent` event.

See: http://docs.guzzlephp.org/en/latest/events.html

## Inflection

The `Guzzle\Inflection` namespace has been removed. This is not a core concern
of Guzzle.

## Iterator

The `Guzzle\Iterator` namespace has been removed.

- `Guzzle\Iterator\AppendIterator`, `Guzzle\Iterator\ChunkedIterator`, and
  `Guzzle\Iterator\MethodProxyIterator` are nice, but not a core requirement of
  Guzzle itself.
- `Guzzle\Iterator\FilterIterator` is no longer needed because an equivalent
  class is shipped with PHP 5.4.
- `Guzzle\Iterator\MapIterator` is not really needed when using PHP 5.5 because
  it's easier to just wrap an iterator in a generator that maps values.

For a replacement of these iterators, see https://github.com/nikic/iter

## Log

The LogPlugin has moved to https://github.com/guzzle/log-subscriber. The
`Guzzle\Log` namespace has been removed. Guzzle now relies on
`Psr\Log\LoggerInterface` for all logging. The MessageFormatter class has been
moved to `GuzzleHttp\Subscriber\Log\Formatter`.

## Parser

The `Guzzle\Parser` namespace has been removed. This was previously used to
make it possible to plug in custom parsers for cookies, messages, URI
templates, and URLs; however, this level of complexity is not needed in Guzzle
so it has been removed.

- Cookie: Cookie parsing logic has been moved to
  `GuzzleHttp\Cookie\SetCookie::fromString`.
- Message: Message parsing logic for both requests and responses has been moved
  to `GuzzleHttp\Message\MessageFactory::fromMessage`. Message parsing is only
  used in debugging or deserializing messages, so it doesn't make sense for
  Guzzle as a library to add this level of complexity to parsing messages.
- UriTemplate: URI template parsing has been moved to
  `GuzzleHttp\UriTemplate`. The Guzzle library will automatically use the PECL
  URI template library if it is installed.
- Url: URL parsing is now performed in `GuzzleHttp\Url::fromString` (previously
  it was `Guzzle\Http\Url::factory()`). If custom URL parsing is necessary,
  then developers are free to subclass `GuzzleHttp\Url`.

## Plugin

The `Guzzle\Plugin` namespace has been renamed to `GuzzleHttp\Subscriber`.
Several plugins are shipping with the core Guzzle library under this namespace.

- `GuzzleHttp\Subscriber\Cookie`: Replaces the old CookiePlugin. Cookie jar
  code has moved to `GuzzleHttp\Cookie`.
- `GuzzleHttp\Subscriber\History`: Replaces the old HistoryPlugin.
- `GuzzleHttp\Subscriber\HttpError`: Throws errors when a bad HTTP response is
  received.
- `GuzzleHttp\Subscriber\Mock`: Replaces the old MockPlugin.
- `GuzzleHttp\Subscriber\Prepare`: Prepares the body of a request just before
  sending. This subscriber is attached to all requests by default.
- `GuzzleHttp\Subscriber\Redirect`: Replaces the RedirectPlugin.

The following plugins have been removed (third-parties are free to re-implement
these if needed):

- `GuzzleHttp\Plugin\Async` has been removed.
- `GuzzleHttp\Plugin\CurlAuth` has been removed.
- `GuzzleHttp\Plugin\ErrorResponse\ErrorResponsePlugin` has been removed. This
  functionality should instead be implemented with event listeners that occur
  after normal response parsing occurs in the guzzle/command package.

The following plugins are not part of the core Guzzle package, but are provided
in separate repositories:

- `Guzzle\Http\Plugin\BackoffPlugin` has been rewritten to be muchs simpler
  to build custom retry policies using simple functions rather than various
  chained classes. See: https://github.com/guzzle/retry-subscriber
- `Guzzle\Http\Plugin\Cache\CachePlugin` has moved to
  https://github.com/guzzle/cache-subscriber
- `Guzzle\Http\Plugin\Log\LogPlugin` has moved to
  https://github.com/guzzle/log-subscriber
- `Guzzle\Http\Plugin\Md5\Md5Plugin` has moved to
  https://github.com/guzzle/message-integrity-subscriber
- `Guzzle\Http\Plugin\Mock\MockPlugin` has moved to
  `GuzzleHttp\Subscriber\MockSubscriber`.
- `Guzzle\Http\Plugin\Oauth\OauthPlugin` has moved to
  https://github.com/guzzle/oauth-subscriber

## Service

The service description layer of Guzzle has moved into two separate packages:

- http://github.com/guzzle/command Provides a high level abstraction over web
  services by representing web service operations using commands.
- http://github.com/guzzle/guzzle-services Provides an implementation of
  guzzle/command that provides request serialization and response parsing using
  Guzzle service descriptions.

## Stream

Stream have moved to a separate package available at
https://github.com/guzzle/streams.

`Guzzle\Stream\StreamInterface` has been given a large update to cleanly take
on the responsibilities of `Guzzle\Http\EntityBody` and
`Guzzle\Http\EntityBodyInterface` now that they have been removed. The number
of methods implemented by the `StreamInterface` has been drastically reduced to
allow developers to more easily extend and decorate stream behavior.

## Removed methods from StreamInterface

- `getStream` and `setStream` have been removed to better encapsulate streams.
- `getMetadata` and `setMetadata` have been removed in favor of
  `GuzzleHttp\Stream\MetadataStreamInterface`.
- `getWrapper`, `getWrapperData`, `getStreamType`, and `getUri` have all been
  removed. This data is accessible when
  using streams that implement `GuzzleHttp\Stream\MetadataStreamInterface`.
- `rewind` has been removed. Use `seek(0)` for a similar behavior.

## Renamed methods

- `detachStream` has been renamed to `detach`.
- `feof` has been renamed to `eof`.
- `ftell` has been renamed to `tell`.
- `readLine` has moved from an instance method to a static class method of
  `GuzzleHttp\Stream\Stream`.

## Metadata streams

`GuzzleHttp\Stream\MetadataStreamInterface` has been added to denote streams
that contain additional metadata accessible via `getMetadata()`.
`GuzzleHttp\Stream\StreamInterface::getMetadata` and
`GuzzleHttp\Stream\StreamInterface::setMetadata` have been removed.

## StreamRequestFactory

The entire concept of the StreamRequestFactory has been removed. The way this
was used in Guzzle 3 broke the actual interface of sending streaming requests
(instead of getting back a Response, you got a StreamInterface). Streeaming
PHP requests are now implemented throught the `GuzzleHttp\Adapter\StreamAdapter`.

3.6 to 3.7
----------

### Deprecations

- You can now enable E_USER_DEPRECATED warnings to see if you are using any deprecated methods.:

```php
\Guzzle\Common\Version::$emitWarnings = true;
```

The following APIs and options have been marked as deprecated:

- Marked `Guzzle\Http\Message\Request::isResponseBodyRepeatable()` as deprecated. Use `$request->getResponseBody()->isRepeatable()` instead.
- Marked `Guzzle\Http\Message\Request::canCache()` as deprecated. Use `Guzzle\Plugin\Cache\DefaultCanCacheStrategy->canCacheRequest()` instead.
- Marked `Guzzle\Http\Message\Request::canCache()` as deprecated. Use `Guzzle\Plugin\Cache\DefaultCanCacheStrategy->canCacheRequest()` instead.
- Marked `Guzzle\Http\Message\Request::setIsRedirect()` as deprecated. Use the HistoryPlugin instead.
- Marked `Guzzle\Http\Message\Request::isRedirect()` as deprecated. Use the HistoryPlugin instead.
- Marked `Guzzle\Cache\CacheAdapterFactory::factory()` as deprecated
- Marked `Guzzle\Service\Client::enableMagicMethods()` as deprecated. Magic methods can no longer be disabled on a Guzzle\Service\Client.
- Marked `Guzzle\Parser\Url\UrlParser` as deprecated. Just use PHP's `parse_url()` and percent encode your UTF-8.
- Marked `Guzzle\Common\Collection::inject()` as deprecated.
- Marked `Guzzle\Plugin\CurlAuth\CurlAuthPlugin` as deprecated. Use
  `$client->getConfig()->setPath('request.options/auth', array('user', 'pass', 'Basic|Digest|NTLM|Any'));` or
  `$client->setDefaultOption('auth', array('user', 'pass', 'Basic|Digest|NTLM|Any'));`

3.7 introduces `request.options` as a parameter for a client configuration and as an optional argument to all creational
request methods. When paired with a client's configuration settings, these options allow you to specify default settings
for various aspects of a request. Because these options make other previous configuration options redundant, several
configuration options and methods of a client and AbstractCommand have been deprecated.

- Marked `Guzzle\Service\Client::getDefaultHeaders()` as deprecated. Use `$client->getDefaultOption('headers')`.
- Marked `Guzzle\Service\Client::setDefaultHeaders()` as deprecated. Use `$client->setDefaultOption('headers/{header_name}', 'value')`.
- Marked 'request.params' for `Guzzle\Http\Client` as deprecated. Use `$client->setDefaultOption('params/{param_name}', 'value')`
- Marked 'command.headers', 'command.response_body' and 'command.on_complete' as deprecated for AbstractCommand. These will work through Guzzle 4.0

        $command = $client->getCommand('foo', array(
            'command.headers' => array('Test' => '123'),
            'command.response_body' => '/path/to/file'
        ));

        // Should be changed to:

        $command = $client->getCommand('foo', array(
            'command.request_options' => array(
                'headers' => array('Test' => '123'),
                'save_as' => '/path/to/file'
            )
        ));

### Interface changes

Additions and changes (you will need to update any implementations or subclasses you may have created):

- Added an `$options` argument to the end of the following methods of `Guzzle\Http\ClientInterface`:
  createRequest, head, delete, put, patch, post, options, prepareRequest
- Added an `$options` argument to the end of `Guzzle\Http\Message\Request\RequestFactoryInterface::createRequest()`
- Added an `applyOptions()` method to `Guzzle\Http\Message\Request\RequestFactoryInterface`
- Changed `Guzzle\Http\ClientInterface::get($uri = null, $headers = null, $body = null)` to
  `Guzzle\Http\ClientInterface::get($uri = null, $headers = null, $options = array())`. You can still pass in a
  resource, string, or EntityBody into the $options parameter to specify the download location of the response.
- Changed `Guzzle\Common\Collection::__construct($data)` to no longer accepts a null value for `$data` but a
  default `array()`
- Added `Guzzle\Stream\StreamInterface::isRepeatable`
- Made `Guzzle\Http\Client::expandTemplate` and `getUriTemplate` protected methods.

The following methods were removed from interfaces. All of these methods are still available in the concrete classes
that implement them, but you should update your code to use alternative methods:

- Removed `Guzzle\Http\ClientInterface::setDefaultHeaders(). Use
  `$client->getConfig()->setPath('request.options/headers/{header_name}', 'value')`. or
  `$client->getConfig()->setPath('request.options/headers', array('header_name' => 'value'))` or
  `$client->setDefaultOption('headers/{header_name}', 'value')`. or
  `$client->setDefaultOption('headers', array('header_name' => 'value'))`.
- Removed `Guzzle\Http\ClientInterface::getDefaultHeaders(). Use `$client->getConfig()->getPath('request.options/headers')`.
- Removed `Guzzle\Http\ClientInterface::expandTemplate()`. This is an implementation detail.
- Removed `Guzzle\Http\ClientInterface::setRequestFactory()`. This is an implementation detail.
- Removed `Guzzle\Http\ClientInterface::getCurlMulti()`. This is a very specific implementation detail.
- Removed `Guzzle\Http\Message\RequestInterface::canCache`. Use the CachePlugin.
- Removed `Guzzle\Http\Message\RequestInterface::setIsRedirect`. Use the HistoryPlugin.
- Removed `Guzzle\Http\Message\RequestInterface::isRedirect`. Use the HistoryPlugin.

### Cache plugin breaking changes

- CacheKeyProviderInterface and DefaultCacheKeyProvider are no longer used. All of this logic is handled in a
  CacheStorageInterface. These two objects and interface will be removed in a future version.
- Always setting X-cache headers on cached responses
- Default cache TTLs are now handled by the CacheStorageInterface of a CachePlugin
- `CacheStorageInterface::cache($key, Response $response, $ttl = null)` has changed to `cache(RequestInterface
  $request, Response $response);`
- `CacheStorageInterface::fetch($key)` has changed to `fetch(RequestInterface $request);`
- `CacheStorageInterface::delete($key)` has changed to `delete(RequestInterface $request);`
- Added `CacheStorageInterface::purge($url)`
- `DefaultRevalidation::__construct(CacheKeyProviderInterface $cacheKey, CacheStorageInterface $cache, CachePlugin
  $plugin)` has changed to `DefaultRevalidation::__construct(CacheStorageInterface $cache,
  CanCacheStrategyInterface $canCache = null)`
- Added `RevalidationInterface::shouldRevalidate(RequestInterface $request, Response $response)`

3.5 to 3.6
----------

* Mixed casing of headers are now forced to be a single consistent casing across all values for that header.
* Messages internally use a HeaderCollection object to delegate handling case-insensitive header resolution
* Removed the whole changedHeader() function system of messages because all header changes now go through addHeader().
  For example, setHeader() first removes the header using unset on a HeaderCollection and then calls addHeader().
  Keeping the Host header and URL host in sync is now handled by overriding the addHeader method in Request.
* Specific header implementations can be created for complex headers. When a message creates a header, it uses a
  HeaderFactory which can map specific headers to specific header classes. There is now a Link header and
  CacheControl header implementation.
* Moved getLinks() from Response to just be used on a Link header object.

If you previously relied on Guzzle\Http\Message\Header::raw(), then you will need to update your code to use the
HeaderInterface (e.g. toArray(), getAll(), etc.).

### Interface changes

* Removed from interface: Guzzle\Http\ClientInterface::setUriTemplate
* Removed from interface: Guzzle\Http\ClientInterface::setCurlMulti()
* Removed Guzzle\Http\Message\Request::receivedRequestHeader() and implemented this functionality in
  Guzzle\Http\Curl\RequestMediator
* Removed the optional $asString parameter from MessageInterface::getHeader(). Just cast the header to a string.
* Removed the optional $tryChunkedTransfer option from Guzzle\Http\Message\EntityEnclosingRequestInterface
* Removed the $asObjects argument from Guzzle\Http\Message\MessageInterface::getHeaders()

### Removed deprecated functions

* Removed Guzzle\Parser\ParserRegister::get(). Use getParser()
* Removed Guzzle\Parser\ParserRegister::set(). Use registerParser().

### Deprecations

* The ability to case-insensitively search for header values
* Guzzle\Http\Message\Header::hasExactHeader
* Guzzle\Http\Message\Header::raw. Use getAll()
* Deprecated cache control specific methods on Guzzle\Http\Message\AbstractMessage. Use the CacheControl header object
  instead.

### Other changes

* All response header helper functions return a string rather than mixing Header objects and strings inconsistently
* Removed cURL blacklist support. This is no longer necessary now that Expect, Accept, etc. are managed by Guzzle
  directly via interfaces
* Removed the injecting of a request object onto a response object. The methods to get and set a request still exist
  but are a no-op until removed.
* Most classes that used to require a `Guzzle\Service\Command\CommandInterface` typehint now request a
  `Guzzle\Service\Command\ArrayCommandInterface`.
* Added `Guzzle\Http\Message\RequestInterface::startResponse()` to the RequestInterface to handle injecting a response
  on a request while the request is still being transferred
* `Guzzle\Service\Command\CommandInterface` now extends from ToArrayInterface and ArrayAccess

3.3 to 3.4
----------

Base URLs of a client now follow the rules of http://tools.ietf.org/html/rfc3986#section-5.2.2 when merging URLs.

3.2 to 3.3
----------

### Response::getEtag() quote stripping removed

`Guzzle\Http\Message\Response::getEtag()` no longer strips quotes around the ETag response header

### Removed `Guzzle\Http\Utils`

The `Guzzle\Http\Utils` class was removed. This class was only used for testing.

### Stream wrapper and type

`Guzzle\Stream\Stream::getWrapper()` and `Guzzle\Stream\Stream::getStreamType()` are no longer converted to lowercase.

### curl.emit_io became emit_io

Emitting IO events from a RequestMediator is now a parameter that must be set in a request's curl options using the
'emit_io' key. This was previously set under a request's parameters using 'curl.emit_io'

3.1 to 3.2
----------

### CurlMulti is no longer reused globally

Before 3.2, the same CurlMulti object was reused globally for each client. This can cause issue where plugins added
to a single client can pollute requests dispatched from other clients.

If you still wish to reuse the same CurlMulti object with each client, then you can add a listener to the
ServiceBuilder's `service_builder.create_client` event to inject a custom CurlMulti object into each client as it is
created.

```php
$multi = new Guzzle\Http\Curl\CurlMulti();
$builder = Guzzle\Service\Builder\ServiceBuilder::factory('/path/to/config.json');
$builder->addListener('service_builder.create_client', function ($event) use ($multi) {
    $event['client']->setCurlMulti($multi);
}
});
```

### No default path

URLs no longer have a default path value of '/' if no path was specified.

Before:

```php
$request = $client->get('http://www.foo.com');
echo $request->getUrl();
// >> http://www.foo.com/
```

After:

```php
$request = $client->get('http://www.foo.com');
echo $request->getUrl();
// >> http://www.foo.com
```

### Less verbose BadResponseException

The exception message for `Guzzle\Http\Exception\BadResponseException` no longer contains the full HTTP request and
response information. You can, however, get access to the request and response object by calling `getRequest()` or
`getResponse()` on the exception object.

### Query parameter aggregation

Multi-valued query parameters are no longer aggregated using a callback function. `Guzzle\Http\Query` now has a
setAggregator() method that accepts a `Guzzle\Http\QueryAggregator\QueryAggregatorInterface` object. This object is
responsible for handling the aggregation of multi-valued query string variables into a flattened hash.

2.8 to 3.x
----------

### Guzzle\Service\Inspector

Change `\Guzzle\Service\Inspector::fromConfig` to `\Guzzle\Common\Collection::fromConfig`

**Before**

```php
use Guzzle\Service\Inspector;

class YourClient extends \Guzzle\Service\Client
{
    public static function factory($config = array())
    {
        $default = array();
        $required = array('base_url', 'username', 'api_key');
        $config = Inspector::fromConfig($config, $default, $required);

        $client = new self(
            $config->get('base_url'),
            $config->get('username'),
            $config->get('api_key')
        );
        $client->setConfig($config);

        $client->setDescription(ServiceDescription::factory(__DIR__ . DIRECTORY_SEPARATOR . 'client.json'));

        return $client;
    }
```

**After**

```php
use Guzzle\Common\Collection;

class YourClient extends \Guzzle\Service\Client
{
    public static function factory($config = array())
    {
        $default = array();
        $required = array('base_url', 'username', 'api_key');
        $config = Collection::fromConfig($config, $default, $required);

        $client = new self(
            $config->get('base_url'),
            $config->get('username'),
            $config->get('api_key')
        );
        $client->setConfig($config);

        $client->setDescription(ServiceDescription::factory(__DIR__ . DIRECTORY_SEPARATOR . 'client.json'));

        return $client;
    }
```

### Convert XML Service Descriptions to JSON

**Before**

```xml
<?xml version="1.0" encoding="UTF-8"?>
<client>
    <commands>
        <!-- Groups -->
        <command name="list_groups" method="GET" uri="groups.json">
            <doc>Get a list of groups</doc>
        </command>
        <command name="search_groups" method="GET" uri='search.json?query="{{query}} type:group"'>
            <doc>Uses a search query to get a list of groups</doc>
            <param name="query" type="string" required="true" />
        </command>
        <command name="create_group" method="POST" uri="groups.json">
            <doc>Create a group</doc>
            <param name="data" type="array" location="body" filters="json_encode" doc="Group JSON"/>
            <param name="Content-Type" location="header" static="application/json"/>
        </command>
        <command name="delete_group" method="DELETE" uri="groups/{{id}}.json">
            <doc>Delete a group by ID</doc>
            <param name="id" type="integer" required="true"/>
        </command>
        <command name="get_group" method="GET" uri="groups/{{id}}.json">
            <param name="id" type="integer" required="true"/>
        </command>
        <command name="update_group" method="PUT" uri="groups/{{id}}.json">
            <doc>Update a group</doc>
            <param name="id" type="integer" required="true"/>
            <param name="data" type="array" location="body" filters="json_encode" doc="Group JSON"/>
            <param name="Content-Type" location="header" static="application/json"/>
        </command>
    </commands>
</client>
```

**After**

```json
{
    "name":       "Zendesk REST API v2",
    "apiVersion": "2012-12-31",
    "description":"Provides access to Zendesk views, groups, tickets, ticket fields, and users",
    "operations": {
        "list_groups":  {
            "httpMethod":"GET",
            "uri":       "groups.json",
            "summary":   "Get a list of groups"
        },
        "search_groups":{
            "httpMethod":"GET",
            "uri":       "search.json?query=\"{query} type:group\"",
            "summary":   "Uses a search query to get a list of groups",
            "parameters":{
                "query":{
                    "location":   "uri",
                    "description":"Zendesk Search Query",
                    "type":       "string",
                    "required":   true
                }
            }
        },
        "create_group": {
            "httpMethod":"POST",
            "uri":       "groups.json",
            "summary":   "Create a group",
            "parameters":{
                "data":        {
                    "type":       "array",
                    "location":   "body",
                    "description":"Group JSON",
                    "filters":    "json_encode",
                    "required":   true
                },
                "Content-Type":{
                    "type":    "string",
                    "location":"header",
                    "static":  "application/json"
                }
            }
        },
        "delete_group": {
            "httpMethod":"DELETE",
            "uri":       "groups/{id}.json",
            "summary":   "Delete a group",
            "parameters":{
                "id":{
                    "location":   "uri",
                    "description":"Group to delete by ID",
                    "type":       "integer",
                    "required":   true
                }
            }
        },
        "get_group":    {
            "httpMethod":"GET",
            "uri":       "groups/{id}.json",
            "summary":   "Get a ticket",
            "parameters":{
                "id":{
                    "location":   "uri",
                    "description":"Group to get by ID",
                    "type":       "integer",
                    "required":   true
                }
            }
        },
        "update_group": {
            "httpMethod":"PUT",
            "uri":       "groups/{id}.json",
            "summary":   "Update a group",
            "parameters":{
                "id":          {
                    "location":   "uri",
                    "description":"Group to update by ID",
                    "type":       "integer",
                    "required":   true
                },
                "data":        {
                    "type":       "array",
                    "location":   "body",
                    "description":"Group JSON",
                    "filters":    "json_encode",
                    "required":   true
                },
                "Content-Type":{
                    "type":    "string",
                    "location":"header",
                    "static":  "application/json"
                }
            }
        }
}
```

### Guzzle\Service\Description\ServiceDescription

Commands are now called Operations

**Before**

```php
use Guzzle\Service\Description\ServiceDescription;

$sd = new ServiceDescription();
$sd->getCommands();     // @returns ApiCommandInterface[]
$sd->hasCommand($name);
$sd->getCommand($name); // @returns ApiCommandInterface|null
$sd->addCommand($command); // @param ApiCommandInterface $command
```

**After**

```php
use Guzzle\Service\Description\ServiceDescription;

$sd = new ServiceDescription();
$sd->getOperations();           // @returns OperationInterface[]
$sd->hasOperation($name);
$sd->getOperation($name);       // @returns OperationInterface|null
$sd->addOperation($operation);  // @param OperationInterface $operation
```

### Guzzle\Common\Inflection\Inflector

Namespace is now `Guzzle\Inflection\Inflector`

### Guzzle\Http\Plugin

Namespace is now `Guzzle\Plugin`. Many other changes occur within this namespace and are detailed in their own sections below.

### Guzzle\Http\Plugin\LogPlugin and Guzzle\Common\Log

Now `Guzzle\Plugin\Log\LogPlugin` and `Guzzle\Log` respectively.

**Before**

```php
use Guzzle\Common\Log\ClosureLogAdapter;
use Guzzle\Http\Plugin\LogPlugin;

/** @var \Guzzle\Http\Client */
$client;

// $verbosity is an integer indicating desired message verbosity level
$client->addSubscriber(new LogPlugin(new ClosureLogAdapter(function($m) { echo $m; }, $verbosity = LogPlugin::LOG_VERBOSE);
```

**After**

```php
use Guzzle\Log\ClosureLogAdapter;
use Guzzle\Log\MessageFormatter;
use Guzzle\Plugin\Log\LogPlugin;

/** @var \Guzzle\Http\Client */
$client;

// $format is a string indicating desired message format -- @see MessageFormatter
$client->addSubscriber(new LogPlugin(new ClosureLogAdapter(function($m) { echo $m; }, $format = MessageFormatter::DEBUG_FORMAT);
```

### Guzzle\Http\Plugin\CurlAuthPlugin

Now `Guzzle\Plugin\CurlAuth\CurlAuthPlugin`.

### Guzzle\Http\Plugin\ExponentialBackoffPlugin

Now `Guzzle\Plugin\Backoff\BackoffPlugin`, and other changes.

**Before**

```php
use Guzzle\Http\Plugin\ExponentialBackoffPlugin;

$backoffPlugin = new ExponentialBackoffPlugin($maxRetries, array_merge(
        ExponentialBackoffPlugin::getDefaultFailureCodes(), array(429)
    ));

$client->addSubscriber($backoffPlugin);
```

**After**

```php
use Guzzle\Plugin\Backoff\BackoffPlugin;
use Guzzle\Plugin\Backoff\HttpBackoffStrategy;

// Use convenient factory method instead -- see implementation for ideas of what
// you can do with chaining backoff strategies
$backoffPlugin = BackoffPlugin::getExponentialBackoff($maxRetries, array_merge(
        HttpBackoffStrategy::getDefaultFailureCodes(), array(429)
    ));
$client->addSubscriber($backoffPlugin);
```

### Known Issues

#### [BUG] Accept-Encoding header behavior changed unintentionally.

(See #217) (Fixed in 09daeb8c666fb44499a0646d655a8ae36456575e)

In version 2.8 setting the `Accept-Encoding` header would set the CURLOPT_ENCODING option, which permitted cURL to
properly handle gzip/deflate compressed responses from the server. In versions affected by this bug this does not happen.
See issue #217 for a workaround, or use a version containing the fix.
�HzFe�����y�gߪ��   GBMB