<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\CashBalance;
use Illuminate\Auth\Access\HandlesAuthorization;

class CashBalancePolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:CashBalance');
    }

    public function view(AuthUser $authUser, CashBalance $cashBalance): bool
    {
        return $authUser->can('View:CashBalance');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:CashBalance');
    }

    public function update(AuthUser $authUser, CashBalance $cashBalance): bool
    {
        return $authUser->can('Update:CashBalance');
    }

    public function delete(AuthUser $authUser, CashBalance $cashBalance): bool
    {
        return $authUser->can('Delete:CashBalance');
    }

    public function restore(AuthUser $authUser, CashBalance $cashBalance): bool
    {
        return $authUser->can('Restore:CashBalance');
    }

    public function forceDelete(AuthUser $authUser, CashBalance $cashBalance): bool
    {
        return $authUser->can('ForceDelete:CashBalance');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:CashBalance');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:CashBalance');
    }

    public function replicate(AuthUser $authUser, CashBalance $cashBalance): bool
    {
        return $authUser->can('Replicate:CashBalance');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:CashBalance');
    }
}
