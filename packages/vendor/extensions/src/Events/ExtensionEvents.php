<?php

namespace Vendor\Extensions\Events;

use Vendor\Extensions\Models\TenantExtension;
use Illuminate\Foundation\Events\Dispatchable;

class ExtensionActivated   { use Dispatchable; public function __construct(public TenantExtension $activation) {} }
class ExtensionDeactivated { use Dispatchable; public function __construct(public TenantExtension $activation) {} }
class ExtensionSuspended   { use Dispatchable; public function __construct(public TenantExtension $activation) {} }