<?php

/**
 * Spizer main executable file. Currently not very well implemented - should be
 * quite different (configuration file based) execution in the final Spizer
 * release
 * 
 * This file can be used as an example for running Spizer
 * 
 * @todo Implement configuration file parsing based on Zend_Config for the 
 *       final bundeled runner
 * 
 * @todo Consider other runners (GTK, web based, etc.)
 * 
 * @package    Spizer
 * @subpackage Runner
 * @author     Shahar Evron, shahar.evron@gmail.com
 * @license    Licensed under the Apache License 2.0, see COPYING for details
 */

set_include_path(dirname(__FILE__) . '/lib' . PATH_SEPARATOR . get_include_path());

require_once 'Zend/Console/Getopt.php';
require_once 'Spizer/Engine.php';
require_once 'Spizer/Handler/LinkAppender.php';
require_once 'Spizer/Handler/StringMatch.php';
require_once 'Spizer/Logger/Sqlite.php';

$opts = new Zend_Console_Getopt(array(
    'delay|d=i'     => 'Delay between requests',
    'log|l=s'       => 'Log output file (defaults to spizerlog.sq3)',
    'savecookies|s' => 'Save and resend cookies throughout session',
    'help|h'        => 'Show this help text'
));

// Parse command line options
try {
    $opts->parse();
} catch (Zend_Console_Getopt_Exception $e) {
    fwrite(STDERR, "Error parsing command line options: {$e->getMessage()}\n");
    exit(1);
}

// If help, show usage and exit
if ($opts->h) {
    spizer_usage();
    exit(0);
}

$delay = (int) $opts->delay;
$log   = $opts->log;
if (! $log) $log = 'spizerlog.sq3';

// Get URL
$args = $opts->getRemainingArgs();
$url = $args[0];
if (! $url) {
   spizer_usage();
   exit(1); 
}

// If we have pcntl - set up a handler for sigterm
if (function_exists('pcntl_signal')) {
    declare(ticks = 1);
    pcntl_signal(SIGABRT, 'do_exit');
    pcntl_signal(SIGHUP,  'do_exit');
    pcntl_signal(SIGQUIT, 'do_exit');
    pcntl_signal(SIGINT,  'do_exit');
    pcntl_signal(SIGTERM, 'do_exit');
}

// Instantiate Spizer engine
$spizer = new Spizer_Engine(array(
	'delay'       => $delay, 
	'savecookies' => $opts->savecookies, 
	'lifo'        => true
));

// Set logger
$logger = new Spizer_Logger_Sqlite(array('dbfile' => $log));
$spizer->setLogger($logger);

// Set the spider to follow links, hrefs, images and script references
$spizer->addHandler(new Spizer_Handler_LinkAppender(array(
	'domain'        => parse_url($url, PHP_URL_HOST) 
)));

// Add some handlers to be executed on 200 OK + text/html pages
$spizer->addHandler(new Spizer_Handler_StringMatch(array(
	'match'        => 'error', 
	'matchcase'    => false, 
	'status'       => 200, 
	'content-type' => 'text/html')));

$spizer->addHandler(new Spizer_Handler_StringMatch(array(
	'match'        => 'warning', 
	'matchcase'    => false, 
	'status'       => 200, 
	'content-type' => 'text/html')));



// Go!
$spizer->run($url);

do_exit();
// -- end here --

// Some functions
function spizer_usage()
{
    if (! isset($argv)) $argv = $_SERVER['argv'];
    
    echo <<<USAGE
Spizer - the flexible web spider, v. 0.1
Usage: {$argv[0]} [options] <Start URL>

Where [options] can be:
  --delay       | -d <seconds>     Number of seconds to delay between requests
  --log         | -l <log file>    Send messages to file instead of to stdout
  --savecookies | -s               Save and resend cookies throughout session
  --help        | -h               Show this help message


USAGE;
}

function do_exit()
{
    global $spizer;
    
    $c = $spizer->getRequestCounter();
    unset($spizer);
    
    file_put_contents('php://stdout', "Spizer preformed a total of $c HTTP requests.\n");
    exit(0);
}
