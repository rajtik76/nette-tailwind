<?php declare(strict_types=1);

use App\Presentation\Error\Error5xx\Error5xxPresenter;
use Nette\Application\IPresenterFactory;
use Nette\Application\Request;
use Nette\Application\Responses\CallbackResponse;
use Nette\Http;
use Tester\Assert;
use Tracy\ILogger;

/** @var Nette\DI\Container $container */
$container = require __DIR__ . '/../../bootstrap.php';


/** Records logged values instead of writing them to disk. */
final class MemoryLogger implements ILogger
{
	/** @var list<array{mixed, string}> */
	public array $entries = [];


	public function log(mixed $value, string $level = self::INFO): void
	{
		$this->entries[] = [$value, $level];
	}
}


/** CLI has no real headers, so the response's Content-Type has to be faked. */
function createHttpResponse(string $contentType): Http\IResponse
{
	return new class ($contentType) implements Http\IResponse {
		public function __construct(
			private string $contentType,
		) {
		}


		public function setCode(int $code, ?string $reason = null): static
		{
			return $this;
		}


		public function getCode(): int
		{
			return 500;
		}


		public function setHeader(string $name, string $value): static
		{
			return $this;
		}


		public function addHeader(string $name, string $value): static
		{
			return $this;
		}


		public function setContentType(string $type, ?string $charset = null): static
		{
			return $this;
		}


		public function redirect(string $url, int $code = self::S302_Found): void
		{
		}


		public function setExpiration(?string $expire): static
		{
			return $this;
		}


		public function isSent(): bool
		{
			return false;
		}


		public function getHeader(string $header): ?string
		{
			return strcasecmp($header, 'Content-Type') === 0 ? $this->contentType : null;
		}


		/** @return array<string, string> */
		public function getHeaders(): array
		{
			return ['Content-Type' => $this->contentType];
		}


		public function setCookie(
			string $name,
			string $value,
			mixed $expire,
			?string $path = null,
			?string $domain = null,
			?bool $secure = null,
			?bool $httpOnly = null,
		): static {
			return $this;
		}


		public function deleteCookie(string $name, ?string $path = null, ?string $domain = null, ?bool $secure = null): void
		{
		}
	};
}


function render(CallbackResponse $response, string $contentType): string
{
	ob_start();
	$response->send(new Http\Request(new Http\UrlScript('http://localhost/')), createHttpResponse($contentType));
	return (string) ob_get_clean();
}


test('presenter is wired in DI container', function () use ($container) {
	$presenter = $container->getByType(IPresenterFactory::class)->createPresenter('Error:Error5xx');
	Assert::type(Error5xxPresenter::class, $presenter);
});


test('exception is logged and 500 page rendered for HTML', function () {
	$logger = new MemoryLogger;
	$exception = new RuntimeException('Boom');

	$response = (new Error5xxPresenter($logger))->run(new Request('Error:Error5xx', Request::FORWARD, [
		'exception' => $exception,
	]));

	Assert::same([[$exception, ILogger::EXCEPTION]], $logger->entries);
	Assert::type(CallbackResponse::class, $response);

	$html = render($response, 'text/html; charset=utf-8');
	Assert::contains('<title>Server Error</title>', $html);
	Assert::contains('<small>error 500</small>', $html);
});


test('nothing is rendered for non-HTML responses', function () {
	$response = (new Error5xxPresenter(new MemoryLogger))->run(new Request('Error:Error5xx', Request::FORWARD, [
		'exception' => new RuntimeException('Boom'),
	]));

	Assert::type(CallbackResponse::class, $response);
	Assert::same('', render($response, 'application/json'));
});


test('503 maintenance page renders', function () {
	ob_start();
	require __DIR__ . '/../../../app/Presentation/Error/Error5xx/503.phtml';
	$html = (string) ob_get_clean();

	Assert::contains('<title>Site is temporarily down for maintenance</title>', $html);
	Assert::contains("We're Sorry", $html);
});
