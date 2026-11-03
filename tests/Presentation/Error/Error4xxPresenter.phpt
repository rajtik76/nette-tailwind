<?php declare(strict_types=1);

use Nette\Application\BadRequestException;
use Nette\Application\IPresenterFactory;
use Nette\Application\Request;
use Nette\Application\Responses\TextResponse;
use Nette\Application\UI\Presenter;
use Tester\Assert;

/** @var Nette\DI\Container $container */
$container = require __DIR__ . '/../../bootstrap.php';


$createPresenter = function () use ($container): Presenter {
	$presenter = $container->getByType(IPresenterFactory::class)->createPresenter('Error:Error4xx');
	assert($presenter instanceof Presenter);
	$presenter->autoCanonicalize = false;
	return $presenter;
};

$cases = [
	403 => 'Access Denied',
	404 => 'Page Not Found',
	410 => 'Page Not Found',
	400 => 'Oops...', // no dedicated template, falls back to 4xx.latte
	405 => 'Oops...',
];

foreach ($cases as $code => $title) {
	test("error $code renders '$title'", function () use ($createPresenter, $code, $title) {
		$response = $createPresenter()->run(new Request('Error:Error4xx', Request::FORWARD, [
			'exception' => new BadRequestException('', $code),
		]));

		Assert::type(TextResponse::class, $response);
		$html = (string) $response->getSource();
		Assert::match('~<title>\s*' . preg_quote($title, '~') . '\s*\| Nette Web</title>~', $html);
		Assert::contains("<h1>$title</h1>", $html);
		Assert::contains("<small>error $code</small>", $html);
	});
}


test('presenter is not reachable directly, only via forward', function () use ($createPresenter) {
	Assert::exception(
		fn() => $createPresenter()->run(new Request('Error:Error4xx', 'GET', [
			'exception' => new BadRequestException('', 404),
		])),
		BadRequestException::class,
	);
});
