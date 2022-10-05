<?php

declare(strict_types=1);

namespace Worksome\Html2Text;

class Html2TextException extends \Exception
{
    public function __construct(string $message = '', public readonly mixed $details = '')
    {
        parent::__construct($message);
    }
}
