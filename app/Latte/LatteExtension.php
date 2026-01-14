<?php declare(strict_types = 1);

namespace App\Latte;

use Latte\Extension;

final class LatteExtension extends Extension
{

	/**
	 * @return array<string, callable>
	 */
	public function getFilters(): array
	{
		return [
			'json' => fn (mixed $value): string => json_encode($value, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
		];
	}

}
