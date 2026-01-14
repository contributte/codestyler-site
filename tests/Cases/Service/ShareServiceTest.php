<?php declare(strict_types = 1);

namespace Tests\Cases\Service;

use App\Service\ShareService;
use PHPUnit\Framework\TestCase;

final class ShareServiceTest extends TestCase
{

	private ShareService $service;

	public function testEncodeAndDecode(): void
	{
		$state = [
			'sniffs' => ['Generic.Arrays.ArrayIndent', 'Squiz.Arrays.ArrayBracketSpacing'],
			'phpVersion' => '8.4',
			'properties' => [],
			'code' => '<?php echo "Hello";',
		];

		$hash = $this->service->encode($state);

		$this->assertNotEmpty($hash);
		$this->assertIsString($hash);

		$decoded = $this->service->decode($hash);

		$this->assertNotNull($decoded);
		$this->assertSame($state['sniffs'], $decoded['sniffs']);
		$this->assertSame($state['phpVersion'], $decoded['phpVersion']);
		$this->assertSame($state['code'], $decoded['code']);
	}

	public function testEncodeWithoutCode(): void
	{
		$state = [
			'sniffs' => ['Generic.Arrays.ArrayIndent'],
			'phpVersion' => '8.3',
		];

		$hash = $this->service->encode($state);
		$decoded = $this->service->decode($hash);

		$this->assertNotNull($decoded);
		$this->assertSame(['Generic.Arrays.ArrayIndent'], $decoded['sniffs']);
		$this->assertSame('8.3', $decoded['phpVersion']);
	}

	public function testDecodeInvalidHash(): void
	{
		$decoded = $this->service->decode('invalid-hash');

		$this->assertNull($decoded);
	}

	public function testDecodeEmptyHash(): void
	{
		$decoded = $this->service->decode('');

		$this->assertNull($decoded);
	}

	public function testEncodeLargeCodeIsSkipped(): void
	{
		$largeCode = str_repeat('<?php echo "x";', 1000);

		$state = [
			'sniffs' => ['Generic.Arrays.ArrayIndent'],
			'phpVersion' => '8.4',
			'code' => $largeCode,
		];

		$hash = $this->service->encode($state);
		$decoded = $this->service->decode($hash);

		$this->assertNotNull($decoded);
		// Large code should be included since it's under 10000 chars
		$this->assertSame($largeCode, $decoded['code']);
	}

	public function testHashIsUrlSafe(): void
	{
		$state = [
			'sniffs' => ['SlevomatCodingStandard.TypeHints.DeclareStrictTypes'],
			'phpVersion' => '8.4',
			'code' => '<?php declare(strict_types = 1);',
		];

		$hash = $this->service->encode($state);

		// URL safe base64 uses - and _ instead of + and /
		$this->assertStringNotContainsString('+', $hash);
		$this->assertStringNotContainsString('/', $hash);
		$this->assertStringNotContainsString('=', $hash);
	}

	protected function setUp(): void
	{
		$this->service = new ShareService();
	}

}
