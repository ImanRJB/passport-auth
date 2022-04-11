<?php

namespace PassportAuth\Observer;

use Laravel\Passport\Token;

class TokenObserber
{
    public function creating(Token $token)
    {
        $token->user_agent = request()->userAgent();
        $token->ip = request()->ip();
    }
}
