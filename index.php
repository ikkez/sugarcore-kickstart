<?php
/**
*  Sugarcore - F3 Application Platform
*
*  The contents of this file are subject to the terms of the GNU General
*  Public License Version 3.0. You may not use this file except in
*  compliance with the license. Any of the license terms and conditions
*  can be waived if you get permission from the copyright holder.
*
*  Copyright (c) 2019
*  https://github.com/ikkez/
*
*  @author   Christian Knuth <mail@ikkez.de>
*
*/

$base_ready=file_exists($base_inc=__DIR__.'/vendor/autoload.php');
$ext_ready=file_exists($ext_inc=__DIR__.'/lib/src/autoload.php');

if ($base_ready && $ext_ready) {
	require $ext_inc;
	require $base_inc;
	require __DIR__.'/vendor/ikkez/sugarcore/bootstrap.php';

} else {

	function __install($dir='') {
		error_reporting(E_ALL & ~E_NOTICE & ~E_USER_NOTICE & ~E_DEPRECATED & ~E_USER_DEPRECATED
			& ~E_WARNING & ~E_CORE_WARNING & ~E_USER_WARNING & ~E_STRICT);
		$conf = [
			'url' => 'https://getcomposer.org/composer.phar',
			'dir' => __DIR__.'/'.$dir,
			'bin' => __DIR__.'/lib/bin/',
			'pkg' => 'composer.phar',
			'json' => __DIR__.'/'.$dir.'composer.json',
			'composer' => [
				"config" => [
					"vendor-dir"=> "src"
				],
				"require"=> [
	//				"bcosca/fatfree-core" => "dev-master#36916119c76f635a6b84df29fda98afa0ee28700 as 3.6.5",
	//				"ikkez/f3-flash" => "^1.0",
					"ikkez/f3-cortex" => "^1.0"
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

		if (!file_exists($conf['bin'].$conf['pkg']) || filemtime($conf['bin'].$conf['pkg']) < strtotime('-3 months')) {
			mkdir($conf['bin'],0777,true);
			copy($conf['url'],$conf['bin'].$conf['pkg']);
			if (!file_exists($conf['bin'].$conf['pkg'])) {
				trigger_error(sprintf('Failed to download composer. Please make sure that %s is existing and writeable.',$conf['bin']),E_USER_ERROR);
				exit();
			}
		}
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

//		header('Content-Type: text');

		$app = new \Composer\Console\Application();
		$factory = new \Composer\Factory();
		$output = $factory->createOutput();

		$input = new \Symfony\Component\Console\Input\ArrayInput(array(
			'command' => 'update',
		));
		$input->setInteractive(false);

		$cmdret = $app->doRun($input,$output);

		echo "============="."\n";
		echo "> done";
		die();
	}
	header('Content-Type: text');

	if (!$base_ready) {
		echo "Installing base requirements".
		__install();
	}
	if (!$ext_ready) {
		echo "Installing extension base".
		__install('lib/');
	}
}

//new Pagination(5);
/** @var \Base $f3 */
//$f3 = \Base::instance();
//echo $f3->VERSION;
//echo $f3->PACKAGE;
