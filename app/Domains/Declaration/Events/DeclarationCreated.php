<?php

namespace App\Domains\Declaration\Events;

use App\Domains\Declaration\Entities\Declaration;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class DeclarationCreated
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public Declaration $declaration
    ) {}
}
