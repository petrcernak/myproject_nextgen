<?php

namespace App\Http\Controllers;

abstract class Controller
{
    protected function currentUser(): ?\App\Models\User
    {
        /** @var \App\Models\User|null $u */
        $u = auth()->user();
        return $u;
    }

    protected function currentGroupId(): ?int
    {
        return session('current_group_id') ?? $this->currentUser()?->id_group;
    }
}
