<?php

namespace App\Domains\User\Events;

use App\Domains\User\Entities\User;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class UserProfileCompleted
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public User $user
    ) {}
}
