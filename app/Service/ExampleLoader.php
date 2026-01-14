<?php declare(strict_types = 1);

namespace App\Service;

use Nette\Neon\Neon;
use Nette\Utils\Finder;

final class ExampleLoader
{

	private const EXAMPLES_DIR = __DIR__ . '/../../resources/examples';

	/** @var array<string, array<string, mixed>>|null */
	private ?array $cache = null;

	/**
	 * @return array<string, array<string, mixed>>
	 */
	public function loadAll(): array
	{
		if ($this->cache !== null) {
			return $this->cache;
		}

		$examples = [];
		$dir = self::EXAMPLES_DIR;

		if (!is_dir($dir)) {
			return $this->cache = [];
		}

		foreach (Finder::findFiles('*.neon')->in($dir) as $file) {
			$exampleId = $file->getBasename('.neon');
			$content = file_get_contents($file->getPathname());
			$data = Neon::decode($content);

			if (is_array($data)) {
				$examples[$exampleId] = $data;
			}
		}

		return $this->cache = $examples;
	}

	/**
	 * @return array<string, mixed>|null
	 */
	public function get(string $id): ?array
	{
		$all = $this->loadAll();

		return $all[$id] ?? null;
	}

}
