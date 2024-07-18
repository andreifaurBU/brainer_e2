<?php

namespace Core\Router\Application\Get;

use Core\Router\Domain\Repositories\ReroutingTimesRepository;

class GetServicesToExecute
{

    public function __construct(
        private ReroutingTimesRepository $reroutingTimes
    ){}

    public function __invoke($time) {
        return $this->reroutingTimes->getServicesByExecutionTime($time);
    }


}