<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\CashierShift;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;

class CashierShiftPolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return true;
    }

    public function view(AuthUser $authUser, CashierShift $cashierShift): bool
    {
        return true;
    }

    public function create(AuthUser $authUser): bool
    {
        return false;
    }

    public function update(AuthUser $authUser, CashierShift $cashierShift): bool
    {
        return false;
    }

    public function delete(AuthUser $authUser, CashierShift $cashierShift): bool
    {
        return false;
    }

    public function restore(AuthUser $authUser, CashierShift $cashierShift): bool
    {
        return false;
    }

    public function forceDelete(AuthUser $authUser, CashierShift $cashierShift): bool
    {
        return false;
    }
}
