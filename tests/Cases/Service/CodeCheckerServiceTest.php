<?php declare(strict_types = 1);

namespace Tests\Cases\Service;

use App\Service\CodeCheckerService;
use PHPUnit\Framework\TestCase;

final class CodeCheckerServiceTest extends TestCase
{

	private CodeCheckerService $service;

	private string $tempDir;

	public function testCheckCodeWithErrors(): void
	{
		$code = <<<'PHP'
<?php
$array = array(1, 2, 3);
PHP;

		$result = $this->service->check($code, ['Generic.Arrays.DisallowLongArraySyntax'], '8.4');

		$this->assertArrayHasKey('totals', $result);
		$this->assertArrayHasKey('messages', $result);
		$this->assertGreaterThan(0, $result['totals']['errors']);
	}

	public function testCheckCodeWithNoErrors(): void
	{
		$code = <<<'PHP'
<?php declare(strict_types = 1);

$array = [1, 2, 3];
PHP;

		$result = $this->service->check($code, ['Generic.Arrays.DisallowLongArraySyntax'], '8.4');

		$this->assertArrayHasKey('totals', $result);
		$this->assertSame(0, $result['totals']['errors']);
	}

	public function testFixCode(): void
	{
		$code = <<<'PHP'
<?php
$array = array(1, 2, 3);
PHP;

		$result = $this->service->fix($code, ['Generic.Arrays.DisallowLongArraySyntax'], '8.4');

		$this->assertArrayHasKey('originalCode', $result);
		$this->assertArrayHasKey('fixedCode', $result);
		$this->assertSame($code, $result['originalCode']);
		$this->assertStringContainsString('[1, 2, 3]', $result['fixedCode']);
		$this->assertStringNotContainsString('array(', $result['fixedCode']);
	}

	public function testCheckCodeWithMultipleSniffs(): void
	{
		$code = <<<'PHP'
<?php
$array = array(1,2,3);
PHP;

		$result = $this->service->check($code, [
			'Generic.Arrays.DisallowLongArraySyntax',
			'Generic.Arrays.ArrayIndent',
		], '8.4');

		$this->assertArrayHasKey('totals', $result);
		$this->assertArrayHasKey('messages', $result);
	}

	public function testCheckCodeReturnsMessageDetails(): void
	{
		$code = <<<'PHP'
<?php
$array = array(1, 2, 3);
PHP;

		$result = $this->service->check($code, ['Generic.Arrays.DisallowLongArraySyntax'], '8.4');

		$this->assertNotEmpty($result['messages']);

		$message = $result['messages'][0];
		$this->assertArrayHasKey('line', $message);
		$this->assertArrayHasKey('column', $message);
		$this->assertArrayHasKey('type', $message);
		$this->assertArrayHasKey('message', $message);
		$this->assertArrayHasKey('source', $message);
	}

	public function testTempFilesAreCleanedUp(): void
	{
		$code = '<?php echo "test";';

		$this->service->check($code, ['Generic.PHP.LowerCaseConstant'], '8.4');

		// Temp directory should be empty after check
		$files = glob($this->tempDir . '/*');
		$this->assertEmpty($files);
	}

	protected function setUp(): void
	{
		$rootDir = dirname(__DIR__, 3);
		$this->tempDir = sys_get_temp_dir() . '/codestyler_test_' . uniqid();
		mkdir($this->tempDir, 0777, true);

		$this->service = new CodeCheckerService($rootDir, $this->tempDir);
	}

	protected function tearDown(): void
	{
		// Clean up temp directory
		if (is_dir($this->tempDir)) {
			$files = glob($this->tempDir . '/*');
			foreach ($files as $file) {
				unlink($file);
			}

			rmdir($this->tempDir);
		}
	}

}
