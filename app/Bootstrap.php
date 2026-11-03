<?php declare(strict_types=1);

namespace App;

use Nette;
use Nette\Bootstrap\Configurator;


class Bootstrap
{
	private readonly Configurator $configurator;
	private readonly string $rootDir;


	public function __construct()
	{
		$this->rootDir = dirname(__DIR__);
		$this->configurator = new Configurator;
		$this->configurator->setTempDirectory($this->rootDir . '/temp');
		// Herd/Valet runs index.php via its own server.php, so wwwDir can't be derived from SCRIPT_FILENAME
		$this->configurator->addStaticParameters(['wwwDir' => $this->rootDir . '/www']);
	}


	public function bootWebApplication(): Nette\DI\Container
	{
		$this->initializeEnvironment();
		$this->setupContainer();
		return $this->configurator->createContainer();
	}


	public function bootTestContainer(): Nette\DI\Container
	{
		// separate cache keeps tests from clashing with the dev server; no Tracy, Tester owns error handling
		$this->configurator->setTempDirectory($this->rootDir . '/temp/tests');
		// debug mode makes Latte and DI pick up changed templates/config instead of serving stale cache
		$this->configurator->setDebugMode(true);
		$this->configurator->createRobotLoader()
			->addDirectory(__DIR__)
			->register();

		$this->setupContainer();
		return $this->configurator->createContainer();
	}


	public function initializeEnvironment(): void
	{
		//$this->configurator->setDebugMode('secret@23.75.345.200'); // enable for your remote IP
		$this->configurator->enableTracy($this->rootDir . '/log');

		$this->configurator->createRobotLoader()
			->addDirectory(__DIR__)
			->register();
	}


	private function setupContainer(): void
	{
		$configDir = $this->rootDir . '/config';
		$this->configurator->addConfig($configDir . '/common.neon');
		$this->configurator->addConfig($configDir . '/services.neon');
	}
}
