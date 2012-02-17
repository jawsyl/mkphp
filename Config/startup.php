<?php
// Ustawienie uprawnień użytkownika/grupy "www-data"
$posixInfo = posix_getpwnam('www-data');
if ($posixInfo !== false) {
    posix_setgid($posixInfo['gid']);
    posix_setuid($posixInfo['uid']);
}

require_once ('defines.php');
require_once (MK_PATH . DIRECTORY_SEPARATOR . 'functions.php');
require_once (MK_PATH . DIRECTORY_SEPARATOR . 'MK.php');

validate_directory(MK_DIR_TEMP);
validate_directory(MK_DIR_SESSION);

spl_autoload_register('MK::_autoload');

use_soap_error_handler(MK_DEBUG);

MK::checkApplicationState();

// wylaczenie cachowania wsdl'a
ini_set("soap.wsdl_cache_enabled", WSDL_CACHE_ENABLE);

// ustawienie strefy czasowej
date_default_timezone_set(MK_TIMEZONE);

// do polskich nazw dat w kalendarzu
setlocale(LC_TIME, MK_LOCALE_TIME);

// do "." w liczbach, a nie ","
setlocale(LC_NUMERIC, MK_LOCALE_NUMERIC);

// rejestracja wrapperów
stream_wrapper_register("tcp", "MK_Stream_Tcp");

// #ErrorHandling
error_reporting(MK_DEBUG || MK_IS_CLI ? (E_ALL | E_STRICT) : '');
ini_set('display_errors', MK_DEBUG || MK_IS_CLI ? 'on' : 'off');

if (MK_IS_CLI === true) {
	set_time_limit(0);
	//resetowanie maski uprawnien
	umask(0);
} else {
	// Ustawiamy własną funkcję do obsługi błędów, jeżeli nie wywołujemy aplikacji z konsoli
	set_error_handler('MK_Error::handler');
	register_shutdown_function('MK::shutdownFunction');
}

if (MK_ERROR_JS_ENABLED) {
	MK_Error::fromJavaScript();
}

// Nadpisanie php.ini
ini_set("memory_limit", "512M");
ini_set("max_execution_time", "600");
ini_set("default_socket_timeout", "600");

// #SessionHandling
ini_set('session.entropy_length', 16);
ini_set('session.entropy_file', '/dev/urandom');
ini_set('session.hash_function', 1);
ini_set('session.hash_bits_per_character', 6);
ini_set('session.save_handler', SESSION_SAVE_HANDLER);
ini_set('session.gc_maxlifetime', 0);
ini_set('session.cookie_lifetime', 0);
ini_set('session.cache_expire', 480);

session_save_path(MK_DIR_SESSION);
session_set_cookie_params(0, MK_COOKIES_PATH);

// Uruchomienie sesji
session_start();

// #Debuging
define('MK_DEBUG_FIREPHP', (isset($_SESSION['DEBUG_FIREPHP']) && !MK_IS_CLI));
if (MK_DEBUG_FIREPHP) {
	require (DIR_LIBS . DIRECTORY_SEPARATOR . 'FirePHPCore' . DIRECTORY_SEPARATOR . 'FirePHP.class.php');
	require (DIR_LIBS . DIRECTORY_SEPARATOR . 'FirePHPCore' . DIRECTORY_SEPARATOR . 'fb.php');
	//@TODO sprawdzic ten klucz sesji i obsłużyć
	$_SESSION['sql_last_time'] = microtime(true);
}

// Uruchomienie kontrollera konsoli jezeli wywołanie jest z konsoli
if (MK_IS_CLI) {
    MK::executeCLICommand($argv);
}
