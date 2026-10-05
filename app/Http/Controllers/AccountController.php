<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Authenticated account page (profile + password), backed by Fortify's
 * /user/profile-information and /user/password endpoints.
 */
class AccountController extends Controller
{
    public function show(Request $request): Response
    {
        return Inertia::render('Account');
    }
}
