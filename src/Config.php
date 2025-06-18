<?php

declare(strict_types=1);

namespace Worksome\Html2Text;

readonly class Config
{
    public function __construct(
        public bool $dropLinks = false,
        public string $characterSet = 'auto',
    ) {
    }
}
