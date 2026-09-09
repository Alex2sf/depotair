<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\OrderProduct;
use Illuminate\Auth\Access\HandlesAuthorization;

class OrderProductPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:OrderProduct');
    }

    public function view(AuthUser $authUser, OrderProduct $orderProduct): bool
    {
        return $authUser->can('View:OrderProduct');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:OrderProduct');
    }

    public function update(AuthUser $authUser, OrderProduct $orderProduct): bool
    {
        return $authUser->can('Update:OrderProduct');
    }

    public function delete(AuthUser $authUser, OrderProduct $orderProduct): bool
    {
        return $authUser->can('Delete:OrderProduct');
    }

    public function restore(AuthUser $authUser, OrderProduct $orderProduct): bool
    {
        return $authUser->can('Restore:OrderProduct');
    }

    public function forceDelete(AuthUser $authUser, OrderProduct $orderProduct): bool
    {
        return $authUser->can('ForceDelete:OrderProduct');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:OrderProduct');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:OrderProduct');
    }

    public function replicate(AuthUser $authUser, OrderProduct $orderProduct): bool
    {
        return $authUser->can('Replicate:OrderProduct');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:OrderProduct');
    }

}