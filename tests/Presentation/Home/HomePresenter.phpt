<?php declare(strict_types=1);

use Nette\Application\IPresenterFactory;
use Nette\Application\Request;
use Nette\Application\Responses\TextResponse;
use Nette\Application\UI\Presenter;
use Tester\Assert;

/** @var Nette\DI\Container $container */
$container = require __DIR__ . '/../../bootstrap.php';


test('Home:default renders the welcome page', function () use ($container) {
	$presenter = $container->getByType(IPresenterFactory::class)->createPresenter('Home');
	Assert::type(Presenter::class, $presenter);
	$presenter->autoCanonicalize = false;

	$response = $presenter->run(new Request('Home', 'GET', ['action' => 'default']));

	Assert::type(TextResponse::class, $response);
	$html = (string) $response->getSource();
	Assert::match('~<title>\s*Congratulations!\s*\| Nette Web</title>~', $html);
	Assert::contains('<h1>Congratulations!</h1>', $html);
	Assert::contains('You have successfully created your', $html);
});
