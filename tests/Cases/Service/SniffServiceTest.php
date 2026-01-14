<?php declare(strict_types = 1);

namespace Tests\Cases\Service;

use App\Service\SniffService;
use PHPUnit\Framework\TestCase;

final class SniffServiceTest extends TestCase
{

	private SniffService $service;

	public function testGetSniffsReturnsArray(): void
	{
		$sniffs = $this->service->getSniffs();

		$this->assertIsArray($sniffs);
		$this->assertNotEmpty($sniffs);
	}

	public function testGetSniffsContainsExpectedSniffs(): void
	{
		$sniffs = $this->service->getSniffs();

		// Should contain some well-known sniffs
		$this->assertArrayHasKey('Generic.Arrays.ArrayIndent', $sniffs);
		$this->assertArrayHasKey('Generic.Arrays.DisallowLongArraySyntax', $sniffs);
	}

	public function testGetSniffsContainsSlevomatSniffs(): void
	{
		$sniffs = $this->service->getSniffs();

		// Find any Slevomat sniff
		$slevomatSniffs = array_filter($sniffs, fn ($s) => $s['standard'] === 'SlevomatCodingStandard');

		$this->assertNotEmpty($slevomatSniffs);
	}

	public function testSniffStructure(): void
	{
		$sniffs = $this->service->getSniffs();
		$sniff = $sniffs['Generic.Arrays.ArrayIndent'] ?? null;

		$this->assertNotNull($sniff);
		$this->assertArrayHasKey('code', $sniff);
		$this->assertArrayHasKey('standard', $sniff);
		$this->assertArrayHasKey('category', $sniff);
		$this->assertArrayHasKey('name', $sniff);
		$this->assertArrayHasKey('class', $sniff);
		$this->assertArrayHasKey('properties', $sniff);
		$this->assertArrayHasKey('examples', $sniff);

		$this->assertSame('Generic.Arrays.ArrayIndent', $sniff['code']);
		$this->assertSame('Generic', $sniff['standard']);
		$this->assertSame('Arrays', $sniff['category']);
		$this->assertSame('ArrayIndent', $sniff['name']);
	}

	public function testGetStandards(): void
	{
		$standards = $this->service->getStandards();

		$this->assertIsArray($standards);
		$this->assertContains('Generic', $standards);
		$this->assertContains('Squiz', $standards);
		$this->assertContains('SlevomatCodingStandard', $standards);
	}

	public function testGetSniff(): void
	{
		$sniff = $this->service->getSniff('Generic.Arrays.ArrayIndent');

		$this->assertNotNull($sniff);
		$this->assertSame('Generic.Arrays.ArrayIndent', $sniff['code']);
	}

	public function testGetSniffNotFound(): void
	{
		$sniff = $this->service->getSniff('NonExistent.Sniff.Name');

		$this->assertNull($sniff);
	}

	public function testSniffPropertiesAreExtracted(): void
	{
		$sniffs = $this->service->getSniffs();

		// Find a sniff with known properties
		$lineLength = $sniffs['Generic.Files.LineLength'] ?? null;

		if ($lineLength !== null) {
			$this->assertIsArray($lineLength['properties']);
			// LineLength sniff has lineLimit and absoluteLineLimit properties
			$propertyNames = array_column($lineLength['properties'], 'name');
			$this->assertContains('lineLimit', $propertyNames);
		}
	}

	public function testSniffCountIsReasonable(): void
	{
		$sniffs = $this->service->getSniffs();

		// Should have at least 100 sniffs (Generic + Squiz + Slevomat)
		$this->assertGreaterThan(100, count($sniffs));

		// But not more than 500 (sanity check)
		$this->assertLessThan(500, count($sniffs));
	}

	protected function setUp(): void
	{
		$this->service = new SniffService();
	}

}
