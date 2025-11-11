<?php
namespace App\Core\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_METHOD)]
class FormStep
{
    public function __construct(
        public string $name,           // step nomi
        public ?string $next = null,   // keyingi step nomi
        public bool $final = false     // bu oxirgi bosqichmi
    ) {}
}
