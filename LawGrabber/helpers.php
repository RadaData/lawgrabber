<?php

use Symfony\Component\DomCrawler\Crawler;

$GLOBALS['start_time'] = time();

function log_path()
{
	return storage_path() . '/logs';
}

function shell_parameters()
{
	global $argv;
	if (!isset($args)) {
		$args = [];
		foreach ($argv as $i => $param) {
			if ($i > 0) {
				list($key, $value) = explode('=', $param . '=');
				$args[$key] = $value;
			}
		}
	}

	return $args;
}

function _log($message, $style = 'default')
{
	date_default_timezone_set('Europe/Kiev');

	$output = str_pad(getmypid(), 7, ':') . ' |%| ' . date('Y-m-d H:i:s') . ' :: ' . $message . "\n";
	if ($style == 'title') {
		$output = "\n\n" . $output;
	}
	$args = shell_parameters();
	if (!is_dir(log_path())) {
		mkdir(log_path());
	}

	if ($style == 'red') {
		$log_file = isset($args['log']) ? $args['log'] : log_path() . '/log.txt';
		file_put_contents($log_file, $output, FILE_APPEND);
	}

	if ($style == 'red') {
		//echo "\x07\x07\x07\x07\x07\x07\x07\x07\x07";
		$output = "\033[0;31m" . $output . "\033[0m";
	} elseif ($style == 'yellow') {
		$output = "\033[1;33m" . $output . "\033[0m";
	} elseif ($style == 'green') {
		$output = "\033[0;32m" . $output . "\033[0m";
	} elseif ($style == 'title') {
		$output = "\033[1m" . $output . "\033[0m";
	}
	print($output);
}

function _logDump($filename, $data)
{
	if (!is_dir(log_path())) {
		mkdir(log_path());
	}
	$dump_dir = log_path() . '/dumps';
	if (!is_dir($dump_dir)) {
		mkdir($dump_dir);
	}
	file_put_contents($dump_dir . '/' . $filename, $data);
}


function delTree($dir) {
	$files = array_diff(scandir($dir), ['.','..']);
	foreach ($files as $file) {
		(is_dir("$dir/$file")) ? delTree("$dir/$file") : unlink("$dir/$file");
	}
	return rmdir($dir);
}

function better_trim($text) {
	$text = preg_replace('|^[\n\s ]*|', '', $text);
	$text = preg_replace('|[\n\s ]*$|', '', $text);
	return $text;
}

/**
 * Return DOMCrawler from a HTML string.
 *
 * @param $html
 *
 * @return Crawler
 */
function crawler($html) {
	return new Crawler($html);
}

/**
 * @return ContainerInterface
 */
function container()
{
	global $container;
	return $container;
}

function max_date()
{
	return env('MAX_CRAWL_DATE', date('Y-m-d'));
}

/**
 * @return \LawGrabber\Jobs\JobsManager
 */
function job_manager() {
	return app()->make('lawgrabber.jobs.manager');
}

/**
 * @return \LawGrabber\Downloader\BaseDownloader
 */
function downloader() {
    return app()->make('lawgrabber.downloader');
}

function download($url, $options = [])
{
	return app()->make('lawgrabber.downloader')->download($url, $options);
}

function downloadList($url, $options = [])
{
	return app()->make('lawgrabber.list_downloader')->downloadList($url, $options);
}

function downloadCard($law_id, $options = [])
{
	return app()->make('lawgrabber.card_downloader')->downloadCard($law_id, $options);
}

function downloadRevision($law_id, $date, $options = [])
{
	return app()->make('lawgrabber.revision_downloader')->downloadRevision($law_id, $date);
}

function shortURL($url)
{
	return downloader()->shortURL($url);
}

function fullURL($url)
{
	return downloader()->fullURL($url);
}