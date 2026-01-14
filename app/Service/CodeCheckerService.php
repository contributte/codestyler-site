<?php declare(strict_types = 1);

namespace App\Service;

use Nette\Utils\FileSystem;
use Nette\Utils\Random;
use Symfony\Component\Process\Process;

final class CodeCheckerService
{

	public function __construct(
		private readonly string $rootDir,
		private readonly string $tempDir,
	)
	{
	}

	/**
	 * @param array<string> $sniffs
	 * @return array<string, mixed>
	 */
	public function check(string $code, array $sniffs, string $phpVersion): array
	{
		$tempFile = $this->createTempFile($code);
		$rulesetFile = $this->createTempRuleset($sniffs, $phpVersion);

		try {
			$process = new Process([
				$this->rootDir . '/vendor/bin/phpcs',
				'--standard=' . $rulesetFile,
				'--report=json',
				'-q',
				$tempFile,
			]);

			$process->run();

			$output = $process->getOutput();
			$result = json_decode($output, true);

			if ($result === null) {
				return [
					'error' => 'Failed to parse phpcs output',
					'raw' => $output,
				];
			}

			// Extract messages from the result
			$messages = [];
			$totals = ['errors' => 0, 'warnings' => 0];

			foreach ($result['files'] ?? [] as $file) {
				$totals['errors'] += $file['errors'] ?? 0;
				$totals['warnings'] += $file['warnings'] ?? 0;

				foreach ($file['messages'] ?? [] as $msg) {
					$messages[] = [
						'line' => $msg['line'],
						'column' => $msg['column'],
						'type' => $msg['type'],
						'message' => $msg['message'],
						'source' => $msg['source'],
						'fixable' => $msg['fixable'] ?? false,
					];
				}
			}

			return [
				'totals' => $totals,
				'messages' => $messages,
			];
		} finally {
			FileSystem::delete($tempFile);
			FileSystem::delete($rulesetFile);
		}
	}

	/**
	 * @param array<string> $sniffs
	 * @return array<string, mixed>
	 */
	public function fix(string $code, array $sniffs, string $phpVersion): array
	{
		$tempFile = $this->createTempFile($code);
		$rulesetFile = $this->createTempRuleset($sniffs, $phpVersion);

		try {
			$process = new Process([
				$this->rootDir . '/vendor/bin/phpcbf',
				'--standard=' . $rulesetFile,
				'-q',
				$tempFile,
			]);

			$process->run();

			// Read fixed file
			$fixedCode = FileSystem::read($tempFile);

			// Also run check to get remaining issues
			$checkResult = $this->check($fixedCode, $sniffs, $phpVersion);

			return [
				'originalCode' => $code,
				'fixedCode' => $fixedCode,
				'totals' => $checkResult['totals'] ?? ['errors' => 0, 'warnings' => 0],
				'messages' => $checkResult['messages'] ?? [],
			];
		} finally {
			FileSystem::delete($tempFile);
			FileSystem::delete($rulesetFile);
		}
	}

	private function createTempFile(string $code): string
	{
		$filename = $this->tempDir . '/code_' . Random::generate(10) . '.php';
		FileSystem::write($filename, $code);

		return $filename;
	}

	/**
	 * @param array<string> $sniffs
	 */
	private function createTempRuleset(array $sniffs, string $phpVersion): string
	{
		$xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
		$xml .= '<ruleset name="Playground">' . "\n";
		$xml .= "\t" . '<config name="php_version" value="' . str_replace('.', '', $phpVersion) . '00"/>' . "\n";

		foreach ($sniffs as $sniff) {
			$xml .= "\t" . '<rule ref="' . htmlspecialchars($sniff) . '"/>' . "\n";
		}

		$xml .= '</ruleset>' . "\n";

		$filename = $this->tempDir . '/ruleset_' . Random::generate(10) . '.xml';
		FileSystem::write($filename, $xml);

		return $filename;
	}

}
