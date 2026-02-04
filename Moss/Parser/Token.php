<?php declare(strict_types=1);

namespace SetFix\Moss\Parser;

class Token
{
    public function __construct(
        public string $kind,
        public ?string $value = null
    ){}
}