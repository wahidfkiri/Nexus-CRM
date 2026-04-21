<?php

namespace Vendor\Extensions\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Vendor\Extensions\Models\TenantExtension;

class ExtensionActivated
{
    use Dispatchable;

    public function __construct(public TenantExtension $activation)
    {
    }
}

