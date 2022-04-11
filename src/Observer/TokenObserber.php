<?php

namespace PassportAuth\Observer;

use Laravel\Passport\Token;

class TokenObserber
{
    public function creating(Token $token)
    {
        $token->user_agent = request()->user_agent;
        $token->ip = request()->ip;
    }
}
