<?php declare(strict_types = 1);

namespace App\UI\Playground;

use App\Service\CodeCheckerService;
use App\Service\ExampleService;
use App\Service\PresetService;
use App\Service\RulesetGeneratorService;
use App\Service\ShareService;
use App\Service\SniffService;
use App\UI\BasePresenter;

final class PlaygroundPresenter extends BasePresenter
{

	public function __construct(
		private readonly SniffService $sniffService,
		private readonly CodeCheckerService $codeChecker,
		private readonly RulesetGeneratorService $rulesetGenerator,
		private readonly ShareService $shareService,
		private readonly PresetService $presetService,
		private readonly ExampleService $exampleService,
	)
	{
		parent::__construct();
	}

	public function renderDefault(?string $s = null): void
	{
		$sniffs = $this->sniffService->getSniffs();
		$standards = $this->sniffService->getStandards();

		// Decode shared state if present
		$sharedState = null;
		if ($s !== null) {
			$sharedState = $this->shareService->decode($s);
		}

		$this->template->sniffs = $sniffs;
		$this->template->standards = $standards;
		$this->template->sharedState = $sharedState;
		$this->template->phpVersions = ['8.2', '8.3', '8.4', '8.5'];
		$this->template->presets = $this->presetService->getPresetSummaries();
		$this->template->examples = $this->exampleService->getExampleSummaries();
	}

	public function handleCheck(): void
	{
		$request = $this->getHttpRequest();
		$data = json_decode($request->getRawBody(), true);

		$code = $data['code'] ?? '';
		$sniffs = $data['sniffs'] ?? [];
		$phpVersion = $data['phpVersion'] ?? '8.4';

		if (empty($code)) {
			$this->sendJson(['error' => 'No code provided']);
		}

		if (empty($sniffs)) {
			$this->sendJson(['error' => 'No sniffs selected']);
		}

		$result = $this->codeChecker->check($code, $sniffs, $phpVersion);

		$this->sendJson($result);
	}

	public function handleFix(): void
	{
		$request = $this->getHttpRequest();
		$data = json_decode($request->getRawBody(), true);

		$code = $data['code'] ?? '';
		$sniffs = $data['sniffs'] ?? [];
		$phpVersion = $data['phpVersion'] ?? '8.4';

		if (empty($code)) {
			$this->sendJson(['error' => 'No code provided']);
		}

		if (empty($sniffs)) {
			$this->sendJson(['error' => 'No sniffs selected']);
		}

		$result = $this->codeChecker->fix($code, $sniffs, $phpVersion);

		$this->sendJson($result);
	}

	public function handleRuleset(): void
	{
		$request = $this->getHttpRequest();
		$data = json_decode($request->getRawBody(), true);

		$sniffs = $data['sniffs'] ?? [];
		$properties = $data['properties'] ?? [];

		if (empty($sniffs)) {
			$this->sendJson(['error' => 'No sniffs selected']);
		}

		$xml = $this->rulesetGenerator->generate($sniffs, $properties);

		$this->sendJson(['ruleset' => $xml]);
	}

	public function handleShare(): void
	{
		$request = $this->getHttpRequest();
		$data = json_decode($request->getRawBody(), true);

		$hash = $this->shareService->encode($data);

		$this->sendJson(['hash' => $hash, 'url' => $this->link('//default', ['s' => $hash])]);
	}

	public function handleLoadPreset(): void
	{
		$request = $this->getHttpRequest();
		$data = json_decode($request->getRawBody(), true);

		$presetId = $data['preset'] ?? '';
		$preset = $this->presetService->getPreset($presetId);

		if ($preset === null) {
			$this->sendJson(['error' => 'Preset not found']);

			return;
		}

		$this->sendJson([
			'sniffs' => $preset['sniffs'],
			'properties' => $preset['properties'] ?? [],
		]);
	}

	public function handleLoadExample(): void
	{
		$request = $this->getHttpRequest();
		$data = json_decode($request->getRawBody(), true);

		$exampleId = $data['example'] ?? '';
		$loadSniffs = $data['loadSniffs'] ?? true;

		$example = $this->exampleService->getExample($exampleId);

		if ($example === null) {
			$this->sendJson(['error' => 'Example not found']);

			return;
		}

		$response = [
			'code' => $example['code'],
		];

		// Only include sniffs if requested
		if ($loadSniffs) {
			$response['sniffs'] = $example['sniffs'];
			$response['properties'] = $example['properties'] ?? [];
		}

		$this->sendJson($response);
	}

}
