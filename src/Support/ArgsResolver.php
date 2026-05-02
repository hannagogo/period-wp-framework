<?php

declare(strict_types=1);

namespace Period\WpFramework\Support;

final class ArgsResolver
{
    public function resolve(array $defaults, array $args = []): array
    {
        return array_merge($defaults, $args);
    }
}
