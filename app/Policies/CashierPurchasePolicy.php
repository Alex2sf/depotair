<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\CashierPurchase;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;

class CashierPurchasePolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return true;
    }

    public function view(AuthUser $authUser, CashierPurchase $cashierPurchase): bool
    {
        return true;
    }

    public function create(AuthUser $authUser): bool
    {
        return false;
    }

    public function update(AuthUser $authUser, CashierPurchase $cashierPurchase): bool
    {
        return false;
    }

    public function delete(AuthUser $authUser, CashierPurchase $cashierPurchase): bool
    {
        return false;
    }

    public function restore(AuthUser $authUser, CashierPurchase $cashierPurchase): bool
    {
        return false;
    }

    public function forceDelete(AuthUser $authUser, CashierPurchase $cashierPurchase): bool
    {
        return false;
    }
}
