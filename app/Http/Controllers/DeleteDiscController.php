<?php

namespace App\Http\Controllers;

use App\Http\Requests\DeleteDiscRequest;
use Illuminate\Http\RedirectResponse;

class DeleteDiscController extends Controller
{
    public function __invoke(DeleteDiscRequest $request): RedirectResponse
    {
        $discParam = $request->route('disc');
        $disc = $discParam instanceof \App\Models\Disc ? $discParam : \App\Models\Disc::query()->find($discParam);

        if ($disc === null) {
            abort(403);
        }

        $disc->delete();

        return redirect()->route('dashboard');
    }
}
