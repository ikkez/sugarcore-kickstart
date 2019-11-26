<?php
/**
 *  Sugarcore - F3 Application Platform
 *
 *  The contents of this file are subject to the terms of the GNU General
 *  Public License Version 3.0. You may not use this file except in
 *  compliance with the license. Any of the license terms and conditions
 *  can be waived if you get permission from the copyright holder.
 *
 *  crafted by   __ __     __
 *              |__|  |--.|  |--.-----.-----.
 *              |  |    < |    <|  -__|-- __|
 *              |__|__|__||__|__|_____|_____|
 *
 *  Copyright (c) 2019
 *  https://github.com/ikkez/
 *
 *  @author   Christian Knuth <mail@ikkez.de>
 *
 */

const EXT_LIB = 'ext_lib/';
$base_ready=file_exists(__DIR__.'/vendor/autoload.php');
$ext_ready=file_exists(__DIR__.'/'.EXT_LIB.'src/autoload.php');

if ($base_ready && $ext_ready) {
	require __DIR__.'/vendor/ikkez/sugarcore/bootstrap.php';
} else {
	function __install($dir='') {
		error_reporting(E_ALL & ~E_NOTICE & ~E_USER_NOTICE & ~E_DEPRECATED & ~E_USER_DEPRECATED
			& ~E_WARNING & ~E_CORE_WARNING & ~E_USER_WARNING & ~E_STRICT);

		ini_set('display_errors', 1);

		$conf = [
			'url' => 'https://getcomposer.org/composer.phar',
			'dir' => __DIR__.'/'.$dir,
			'bin' => __DIR__.'/'.$dir.'bin/',
			'pkg' => 'composer.phar',
			'json' => __DIR__.'/'.$dir.'composer.json',
			'composer' => [
				"config" => [
					"vendor-dir"=> "src"
				]
			]
		];
		@ini_set('memory_limit',-1);
		if ((int)ini_get('max_execution_time')<300)
			@ini_set('max_execution_time',300);

		if (!file_exists($conf['dir'])) {
			mkdir($conf['dir'],0777,true);
			if (!file_exists($conf['dir']) || !is_writable($conf['dir'])) {
				trigger_error(sprintf('Please make sure %s is existing and writeable.',$conf['dir']),E_USER_ERROR);
				exit();
			}
		}
		if (empty($dir) && !is_writable($conf['dir'])) {
			trigger_error(sprintf('Please make sure %s is writeable.',$conf['dir']),E_USER_ERROR);
			exit();
		}

		if (!file_exists($conf['bin'].$conf['pkg']) || filemtime($conf['bin'].$conf['pkg']) < strtotime('-3 months')) {
			mkdir($conf['bin'],0777,true);
			copy($conf['url'],$conf['bin'].$conf['pkg']);
			if (!file_exists($conf['bin'].$conf['pkg'])) {
				trigger_error(sprintf('Failed to download composer. Please make sure that %s is existing and writeable.',__DIR__.'/'.$dir),E_USER_ERROR);
				exit();
			}
		}
		ob_start();
		require_once "phar://{$conf['bin']}{$conf['pkg']}/src/bootstrap.php";

		if (!empty($dir)) {
			if (!file_exists($conf['json'])) {
				$conf_json = json_encode($conf['composer'],JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE);
				file_put_contents($conf['json'],$conf_json);
			}
			chdir($conf['dir']);
		}
		putenv("COMPOSER_HOME={$conf['dir']}");
		putenv("OSTYPE=OS400"); //force to use php://output instead of php://stdout

		if (empty($dir) && !preg_match('/^win/i',PHP_OS))
			putenv("COMPOSER_CACHE_DIR=".__DIR__.'/'.$dir.'/cache/');

		$app = new \Composer\Console\Application();
		$factory = new \Composer\Factory();
		$output = $factory->createOutput();

		$input = new \Symfony\Component\Console\Input\ArrayInput(array(
			'command' => 'update',
		));
		$input->setInteractive(false);

		$cmdret = $app->doRun($input,$output);
		return ob_get_clean();
	}

	header('Content-Type: text/html');
	$body=<<<HTML
	<html><title>Sugarcore Install</title><head><style>*{font-family: Verdana;} code{ background: #e3e3e3; padding: 5px;} pre{padding: 15px; background: #e3e3e3; } pre code{padding: 0;}</style></head><body>%s</body></html>
HTML;

	$msg='';
	if (!$base_ready) {
		$msg.='<h1>Installation</h1>';
		if (isset($_GET['proceed']) && $_GET['proceed']=='base') {
			$msg.='Running composer:';
			$result = __install();
			$msg.='<pre><code>'.$result.'</code></pre>';
			$msg.='<strong>Done.</strong> <a href="?">continue.</a>';
		} else {
			$msg.='Base repositories required. To install run <code>composer install</code> on terminal or <a href="?proceed=base">run Installer</a>';
		}
	} elseif (!$ext_ready) {
		$msg.='<h1>Installation</h1>';
		if (isset($_GET['proceed']) && $_GET['proceed']=='ext') {
			$msg.='Running composer:';
			$result = __install(EXT_LIB);
			$msg.='<pre><code>'.$result.'</code></pre>';
			$msg.='<strong>Done.</strong> <a href="?">continue.</a>';
		} else {
			$msg.='Extension setup required. <a href="?proceed=ext">Proceed</a>';
		}
	}
	echo sprintf($body,$msg);
}