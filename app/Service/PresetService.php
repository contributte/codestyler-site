<?php declare(strict_types = 1);

namespace App\Service;

final class PresetService
{

	/** @var array<string, array<string, mixed>>|null */
	private ?array $presetsCache = null;

	/**
	 * @return array<string, array<string, mixed>>
	 */
	public function getPresets(): array
	{
		if ($this->presetsCache !== null) {
			return $this->presetsCache;
		}

		$this->presetsCache = json_decode(
			file_get_contents(__DIR__ . '/../../resources/presets.json'),
			true
		);

		return $this->presetsCache;
	}

	/**
	 * @return array<string, mixed>|null
	 */
	public function getPreset(string $id): ?array
	{
		$presets = $this->getPresets();

		return $presets[$id] ?? null;
	}

	/**
	 * Get list of preset summaries for UI display
	 *
	 * @return array<int, array{id: string, name: string, description: string, sniffCount: int}>
	 */
	public function getPresetSummaries(): array
	{
		$presets = $this->getPresets();
		$summaries = [];

		foreach ($presets as $preset) {
			$summaries[] = [
				'id' => $preset['id'],
				'name' => $preset['name'],
				'description' => $preset['description'],
				'sniffCount' => count($preset['sniffs']),
			];
		}

		return $summaries;
	}

}
