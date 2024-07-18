<?php

namespace Core\Router\Domain\Repositories;

interface ExpeditionsRepository
{
    public function saveNewExpedition(array $expedition);
}