<?php
namespace App\Core\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_METHOD)]
class Permission
{
    /**
     * @param string|array $role — ruxsat berilgan rollar
     */
    public function __construct(public string|array $role)
    {
        $this->role = (array) $role;
    }
}
