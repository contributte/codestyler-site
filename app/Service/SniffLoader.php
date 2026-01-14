<?php declare(strict_types = 1);

namespace App\Service;

use Nette\Neon\Neon;
use Nette\Utils\Finder;

final class SniffLoader
{

	private const SNIFFS_DIR = __DIR__ . '/../../resources/sniffs';

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

		$sniffs = [];
		$dir = self::SNIFFS_DIR;

		if (!is_dir($dir)) {
			return $this->cache = [];
		}

		foreach (Finder::findFiles('*.neon')->in($dir) as $file) {
			$sniffCode = $file->getBasename('.neon');
			$content = file_get_contents($file->getPathname());
			$data = Neon::decode($content);

			if (is_array($data)) {
				$sniffs[$sniffCode] = $data;
			}
		}

		return $this->cache = $sniffs;
	}

	/**
	 * @return array<string, mixed>|null
	 */
	public function get(string $code): ?array
	{
		$all = $this->loadAll();

		return $all[$code] ?? null;
	}

}
