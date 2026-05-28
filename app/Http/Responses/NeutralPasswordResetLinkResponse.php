<?php

namespace App\Http\Responses;

use Illuminate\Http\RedirectResponse;
use Laravel\Fortify\Contracts\FailedPasswordResetLinkResponse;
use Laravel\Fortify\Contracts\PasswordResetLinkResponse;

final class NeutralPasswordResetLinkResponse implements FailedPasswordResetLinkResponse, PasswordResetLinkResponse
{
    public function toResponse($request): RedirectResponse
    {
        return back()->with('status', __('passwords.reset_link_sent'));
    }
}
