<?php declare(strict_types = 1);

namespace App\Service;

final class ShareService
{

	/**
	 * Encode playground state to a shareable hash
	 *
	 * @param array<string, mixed> $state
	 */
	public function encode(array $state): string
	{
		$data = [
			'sniffs' => $state['sniffs'] ?? [],
			'phpVersion' => $state['phpVersion'] ?? '8.4',
			'properties' => $state['properties'] ?? [],
		];

		// Optionally include code (if not too large)
		$code = $state['code'] ?? '';
		if (strlen($code) <= 10000) {
			$data['code'] = $code;
		}

		$json = json_encode($data);
		$compressed = gzcompress($json, 9);

		return rtrim(strtr(base64_encode($compressed), '+/', '-_'), '=');
	}

	/**
	 * Decode a shareable hash back to playground state
	 *
	 * @return array<string, mixed>|null
	 */
	public function decode(string $hash): ?array
	{
		try {
			$compressed = base64_decode(strtr($hash, '-_', '+/'));
			if ($compressed === false) {
				return null;
			}

			$json = gzuncompress($compressed);
			if ($json === false) {
				return null;
			}

			$data = json_decode($json, true);
			if (!is_array($data)) {
				return null;
			}

			return [
				'sniffs' => $data['sniffs'] ?? [],
				'phpVersion' => $data['phpVersion'] ?? '8.4',
				'properties' => $data['properties'] ?? [],
				'code' => $data['code'] ?? '',
			];
		} catch (\Throwable) {
			return null;
		}
	}

}
