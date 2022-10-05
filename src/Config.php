<?php

declare(strict_types=1);

namespace Worksome\Html2Text;

class Config
{
    public function __construct(
        public readonly bool $dropLinks = false,
    ) {
    }
}
