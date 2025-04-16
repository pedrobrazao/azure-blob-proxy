<?php

declare(strict_types=1);

namespace App\Factory;

use DI\ContainerBuilder;
use Psr\Container\ContainerInterface;

final class ContainerFactory
{
    public function __construct(private readonly array $definitions)
    {}

    public function create(): ContainerInterface
    {
        $builder = new ContainerBuilder();
        $builder->addDefinitions($this->definitions);

        return $builder->build();
    }
}
