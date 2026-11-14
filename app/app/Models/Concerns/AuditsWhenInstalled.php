<?php

namespace App\Models\Concerns;

// Audit trail from classivo/security when that package is installed; otherwise nothing.
if (trait_exists(\Classivo\Security\Concerns\Auditable::class)) {
    trait AuditsWhenInstalled
    {
        use \Classivo\Security\Concerns\Auditable;
    }
} else {
    trait AuditsWhenInstalled {}
}
