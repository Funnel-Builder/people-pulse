<?php

namespace App\Http\Responses;

use Illuminate\Http\JsonResponse;
use Laravel\Fortify\Contracts\VerifyEmailResponse as VerifyEmailResponseContract;

class VerifyEmailResponse implements VerifyEmailResponseContract
{
    public function toResponse($request)
    {
        $tenant = tenant();
        $redirectPath = $tenant ? "/dashboard?verified=1" : '/?verified=1';

        return $request->wantsJson()
            ? new JsonResponse('', 204)
            : redirect()->intended($redirectPath);
    }
}
