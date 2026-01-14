<?php declare(strict_types = 1);

namespace Tests\Cases\Service;

use App\Service\RulesetGeneratorService;
use PHPUnit\Framework\TestCase;

final class RulesetGeneratorServiceTest extends TestCase
{

	private RulesetGeneratorService $service;

	public function testGenerateSimpleRuleset(): void
	{
		$sniffs = [
			'Generic.Arrays.ArrayIndent',
			'Squiz.Arrays.ArrayBracketSpacing',
		];

		$xml = $this->service->generate($sniffs);

		$this->assertStringContainsString('<?xml version="1.0" encoding="UTF-8"?>', $xml);
		$this->assertStringContainsString('<ruleset name="Custom Ruleset">', $xml);
		$this->assertStringContainsString('<rule ref="Generic.Arrays.ArrayIndent"/>', $xml);
		$this->assertStringContainsString('<rule ref="Squiz.Arrays.ArrayBracketSpacing"/>', $xml);
		$this->assertStringContainsString('</ruleset>', $xml);
	}

	public function testGenerateRulesetWithProperties(): void
	{
		$sniffs = ['Generic.Files.LineLength'];
		$properties = [
			'Generic.Files.LineLength' => [
				'lineLimit' => 120,
				'absoluteLineLimit' => 150,
			],
		];

		$xml = $this->service->generate($sniffs, $properties);

		$this->assertStringContainsString('<rule ref="Generic.Files.LineLength">', $xml);
		$this->assertStringContainsString('<properties>', $xml);
		$this->assertStringContainsString('<property name="lineLimit" value="120"/>', $xml);
		$this->assertStringContainsString('<property name="absoluteLineLimit" value="150"/>', $xml);
		$this->assertStringContainsString('</properties>', $xml);
		$this->assertStringContainsString('</rule>', $xml);
	}

	public function testGenerateRulesetWithArrayProperty(): void
	{
		$sniffs = ['Generic.PHP.ForbiddenFunctions'];
		$properties = [
			'Generic.PHP.ForbiddenFunctions' => [
				'forbiddenFunctions' => [
					'sizeof' => 'count',
					'delete' => 'unset',
				],
			],
		];

		$xml = $this->service->generate($sniffs, $properties);

		$this->assertStringContainsString('<property name="forbiddenFunctions" type="array">', $xml);
		$this->assertStringContainsString('<element key="sizeof" value="count"/>', $xml);
		$this->assertStringContainsString('<element key="delete" value="unset"/>', $xml);
	}

	public function testGenerateRulesetIsValidXml(): void
	{
		$sniffs = [
			'Generic.Arrays.ArrayIndent',
			'SlevomatCodingStandard.TypeHints.DeclareStrictTypes',
		];

		$xml = $this->service->generate($sniffs);

		// Should parse as valid XML
		$doc = new \DOMDocument();
		$result = $doc->loadXML($xml);

		$this->assertTrue($result);
	}

	public function testGenerateRulesetEscapesSpecialCharacters(): void
	{
		$sniffs = ['Generic.Arrays.ArrayIndent'];
		$properties = [
			'Generic.Arrays.ArrayIndent' => [
				'description' => 'Test <value> & "quotes"',
			],
		];

		$xml = $this->service->generate($sniffs, $properties);

		$this->assertStringContainsString('&lt;value&gt;', $xml);
		$this->assertStringContainsString('&amp;', $xml);
		$this->assertStringContainsString('&quot;quotes&quot;', $xml);
	}

	public function testGenerateEmptyRuleset(): void
	{
		$xml = $this->service->generate([]);

		$this->assertStringContainsString('<ruleset name="Custom Ruleset">', $xml);
		$this->assertStringContainsString('</ruleset>', $xml);
		$this->assertStringNotContainsString('<rule ref=', $xml);
	}

	protected function setUp(): void
	{
		$this->service = new RulesetGeneratorService();
	}

}
