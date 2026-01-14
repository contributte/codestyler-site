<?php declare(strict_types = 1);

namespace App\UI\Sniffs;

use App\Service\SniffService;
use App\UI\BasePresenter;

final class SniffsPresenter extends BasePresenter
{

	public function __construct(
		private readonly SniffService $sniffService,
	)
	{
		parent::__construct();
	}

	public function renderDefault(?string $search = null, ?string $standard = null): void
	{
		$sniffs = $this->sniffService->getSniffs();

		// Filter by search query
		if ($search !== null && $search !== '') {
			$search = strtolower($search);
			$sniffs = array_filter($sniffs, fn ($sniff) => str_contains(strtolower($sniff['name']), $search) || str_contains(strtolower($sniff['code']), $search));
		}

		// Filter by standard
		if ($standard !== null && $standard !== '') {
			$sniffs = array_filter($sniffs, fn ($sniff) => $sniff['standard'] === $standard);
		}

		$this->template->sniffs = $sniffs;
		$this->template->standards = $this->sniffService->getStandards();
		$this->template->search = $search;
		$this->template->selectedStandard = $standard;
		$this->template->totalCount = count($this->sniffService->getSniffs());
	}

	public function renderDetail(string $code): void
	{
		$sniff = $this->sniffService->getSniff($code);

		if ($sniff === null) {
			$this->error('Sniff not found');
		}

		$this->template->sniff = $sniff;
	}

}
