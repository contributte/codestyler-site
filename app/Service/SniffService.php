<?php declare(strict_types = 1);

namespace App\Service;

final class SniffService
{

	/** @var array<string, array<string, mixed>>|null */
	private ?array $sniffsCache = null;

	public function __construct(
		private readonly SniffLoader $sniffLoader,
	)
	{
	}

	/**
	 * @return array<string, array<string, mixed>>
	 */
	public function getSniffs(): array
	{
		if ($this->sniffsCache !== null) {
			return $this->sniffsCache;
		}

		$sniffs = $this->sniffLoader->loadAll();

		$this->sniffsCache = $sniffs;

		return $this->sniffsCache;
	}

	/**
	 * @return array<string>
	 */
	public function getStandards(): array
	{
		$sniffs = $this->getSniffs();
		$standards = array_unique(array_column($sniffs, 'standard'));
		sort($standards);

		return $standards;
	}

	/**
	 * @return array<string, mixed>|null
	 */
	public function getSniff(string $code): ?array
	{
		return $this->sniffLoader->get($code);
	}

}
