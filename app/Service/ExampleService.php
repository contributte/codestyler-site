<?php declare(strict_types = 1);

namespace App\Service;

final class ExampleService
{

	/** @var array<string, array<string, mixed>>|null */
	private ?array $examplesCache = null;

	public function __construct(
		private readonly ExampleLoader $exampleLoader,
	)
	{
	}

	/**
	 * @return array<string, array<string, mixed>>
	 */
	public function getExamples(): array
	{
		if ($this->examplesCache !== null) {
			return $this->examplesCache;
		}

		$this->examplesCache = $this->exampleLoader->loadAll();

		return $this->examplesCache;
	}

	/**
	 * @return array<string, mixed>|null
	 */
	public function getExample(string $id): ?array
	{
		return $this->exampleLoader->get($id);
	}

	/**
	 * Get list of example summaries for UI display
	 *
	 * @return array<int, array{id: string, name: string, description: string, category: string, sniffCount: int}>
	 */
	public function getExampleSummaries(): array
	{
		$examples = $this->getExamples();
		$summaries = [];

		foreach ($examples as $example) {
			$summaries[] = [
				'id' => $example['id'],
				'name' => $example['name'],
				'description' => $example['description'],
				'category' => $example['category'] ?? 'General',
				'sniffCount' => count($example['sniffs']),
			];
		}

		return $summaries;
	}

}
