<?php

namespace Core\Router\Application\Save;

use Core\Router\Domain\Repositories\RouteUpFileRepository;

class SaveRouteUpFile
{

    public function __construct(
        private RouteUpFileRepository $routerUpFileRepository
    ){}

    public function __invoke($file) {
        return $this->routerUpFileRepository->saveRouteUpFile($file);
    }


}