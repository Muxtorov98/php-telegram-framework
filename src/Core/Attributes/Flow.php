<?php
namespace App\Core\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_METHOD)]
class Flow
{
    public function __construct(
        public ?string $depends_on = null,   // oldingi command (masalan "/start")
        public ?int $order = 0               // tartib raqami (0 = istalgan)
    ) {}
}
