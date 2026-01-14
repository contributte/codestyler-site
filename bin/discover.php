#!/usr/bin/env php
<?php declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

// Initialize PHP_CodeSniffer autoloader to ensure all Sniff classes can be loaded
require __DIR__ . '/../vendor/squizlabs/php_codesniffer/autoload.php';

use Nette\Utils\FileSystem;
use Nette\Utils\Finder;

/**
 * Sniff Discovery Script
 *
 * Discovers all available sniffs from PHP_CodeSniffer and Slevomat Coding Standard
 * and writes them to resources/sniffs.json.
 *
 * Usage: php bin/discover.php
 */

$rootDir = dirname(__DIR__);
$outputFile = $rootDir . '/resources/sniffs.json';

echo "Discovering sniffs...\n";

$sniffs = discoverSniffs($rootDir);

echo sprintf("Found %d sniffs\n", count($sniffs));

// Ensure resources directory exists
$resourcesDir = dirname($outputFile);
if (!is_dir($resourcesDir)) {
	mkdir($resourcesDir, 0755, true);
}

// Write to file
FileSystem::write($outputFile, json_encode($sniffs, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

echo sprintf("Written to %s\n", $outputFile);

/**
 * @return array<string, array<string, mixed>>
 */
function discoverSniffs(string $rootDir): array
{
	$sniffs = [];
	$vendorDir = $rootDir . '/vendor';

	// Find all Sniff classes
	$paths = [
		$vendorDir . '/squizlabs/php_codesniffer/src/Standards',
		$vendorDir . '/slevomat/coding-standard/SlevomatCodingStandard',
	];

	foreach ($paths as $path) {
		if (!is_dir($path)) {
			continue;
		}

		foreach (Finder::findFiles('*Sniff.php')->from($path) as $file) {
			$sniff = parseSniffFile($file->getPathname());
			if ($sniff !== null) {
				$sniffs[$sniff['code']] = $sniff;
			}
		}
	}

	// Sort by code
	ksort($sniffs);

	// Load examples from test fixtures
	loadExamples($sniffs, $rootDir);

	return $sniffs;
}

/**
 * @return array<string, mixed>|null
 */
function parseSniffFile(string $path): ?array
{
	$content = file_get_contents($path);

	// Extract namespace and class
	if (!preg_match('/namespace\s+([^;]+)/', $content, $nsMatch)) {
		return null;
	}
	if (!preg_match('/class\s+(\w+Sniff)/', $content, $classMatch)) {
		return null;
	}

	$namespace = $nsMatch[1];
	$className = $classMatch[1];
	$fqcn = $namespace . '\\' . $className;

	// Build code like "Generic.Arrays.ArrayIndent"
	// PHP_CodeSniffer namespace: PHP_CodeSniffer\Standards\Generic\Sniffs\Arrays
	// Slevomat namespace: SlevomatCodingStandard\Sniffs\TypeHints
	$parts = explode('\\', $namespace);
	if ($parts[0] === 'PHP_CodeSniffer') {
		$standard = $parts[2];
		$category = $parts[4] ?? '';
	} else {
		$standard = $parts[0];
		$category = $parts[2] ?? '';
	}
	$name = str_replace('Sniff', '', $className);
	$code = sprintf('%s.%s.%s', $standard, $category, $name);

	// Extract description from PHPDoc
	$description = '';
	if (preg_match('/\/\*\*\s*\n\s*\*\s*([^\n]+)/', $content, $docMatch)) {
		$description = trim($docMatch[1]);
		if (str_starts_with($description, '@')) {
			$description = '';
		}
	}

	// Extract public properties via reflection
	$properties = [];
	if (class_exists($fqcn)) {
		try {
			$reflection = new \ReflectionClass($fqcn);
			foreach ($reflection->getProperties(\ReflectionProperty::IS_PUBLIC) as $prop) {
				if ($prop->isStatic()) {
					continue;
				}

				$propDoc = $prop->getDocComment() ?: '';
				$propDescription = '';
				if (preg_match('/@var\s+(\S+)/', $propDoc, $typeMatch)) {
					$type = $typeMatch[1];
				} else {
					$type = $prop->getType()?->getName() ?? 'mixed';
				}
				if (preg_match('/\*\s+([^@\n]+)/', $propDoc, $descMatch)) {
					$propDescription = trim($descMatch[1]);
				}

				$properties[] = [
					'name' => $prop->getName(),
					'type' => $type,
					'default' => $prop->getDefaultValue(),
					'description' => $propDescription,
				];
			}
		} catch (Throwable) {
			// Ignore reflection errors
		}
	}

	return [
		'code' => $code,
		'standard' => $standard,
		'category' => $category,
		'name' => $name,
		'class' => $fqcn,
		'description' => $description,
		'properties' => $properties,
		'examples' => [
			'good' => null,
			'bad' => null,
		],
	];
}

/**
 * @param array<string, array<string, mixed>> $sniffs
 */
function loadExamples(array &$sniffs, string $rootDir): void
{
	$testsDir = $rootDir . '/tests/Sniffs';

	if (!is_dir($testsDir)) {
		return;
	}

	foreach (Finder::findDirectories('*')->in($testsDir) as $standardDir) {
		foreach (Finder::findDirectories('*')->in($standardDir->getPathname()) as $sniffDir) {
			// Try to match sniff code
			$dirName = $sniffDir->getBasename();
			$standardName = $standardDir->getBasename();

			// Find matching sniff
			foreach ($sniffs as $code => &$sniff) {
				$expectedDir = $sniff['standard'] . '.' . $sniff['category'];
				if ($standardName === $expectedDir && $dirName === $sniff['name']) {
					$goodFile = $sniffDir->getPathname() . '/good.php';
					$badFile = $sniffDir->getPathname() . '/bad.php';

					if (file_exists($goodFile)) {
						$sniff['examples']['good'] = FileSystem::read($goodFile);
					}
					if (file_exists($badFile)) {
						$sniff['examples']['bad'] = FileSystem::read($badFile);
					}
					break;
				}
			}
		}
	}
}
