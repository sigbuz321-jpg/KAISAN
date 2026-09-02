<?php

namespace App\Policies;

use App\Models\AiGenerationJob;
use App\Models\User;

class AiGenerationJobPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAdmin() || $user->isGuru();
    }

    /**
     * A teacher sees what they asked for. Only the admin sees everyone's, and
     * only because the admin is the one paying the router's bill.
     */
    public function view(User $user, AiGenerationJob $job): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        return $user->isGuru() && $job->requested_by === $user->id;
    }

    public function create(User $user): bool
    {
        return $this->viewAny($user);
    }

    /** Generation records are an audit and billing trail; nobody edits them. */
    public function update(User $user, AiGenerationJob $job): bool
    {
        return false;
    }

    public function delete(User $user, AiGenerationJob $job): bool
    {
        return false;
    }

    /** The monthly cost recap is the owner's business, not a teacher's. */
    public function viewCostReport(User $user): bool
    {
        return $user->isAdmin();
    }
}
