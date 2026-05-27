<?php

namespace App\Models\Concerns;

trait HasPlatformCatalog
{
    public function isPlatformCatalog(): bool
    {
        return (bool) ($this->is_platform ?? false);
    }
}
