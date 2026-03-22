<?php

namespace App\apps\security\Service\User;

use App\apps\security\Repository\UserRepository;
use App\apps\security\Service\User\Dto\UserFilterDto;
use App\shared\Service\Filter\SearchTextFilter;
use App\shared\Service\FilterService;
use CarlosChininin\Spreadsheet\Writer\OpenSpout\SpreadsheetWriter;
use Symfony\Component\HttpFoundation\Response;

final readonly class DownloadUsersService
{
    public function __construct(
        private UserRepository $userRepository,
        private FilterService $filterService,
    ) {
    }

    public function execute(UserFilterDto $filterDto): Response
    {
        $this->filterService->addFilter(new SearchTextFilter($filterDto->search, ['user.username', 'user.fullName']));
        $users = $this->userRepository->downloadAndFilter($this->filterService);
        $export = new SpreadsheetWriter($users, $this->headers());

        return $export->execute()->download('export_users');
    }

    private function headers(): array
    {
        return [
            'Usuario',
            'Nombre',
            'Activo',
        ];
    }
}
