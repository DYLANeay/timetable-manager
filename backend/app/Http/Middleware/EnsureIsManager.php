<?php

namespace App\Http\Middleware;

use App\Enums\Role;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureIsManager
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->user()?->role !== Role::Manager) {
            abort(403, 'This action requires manager privileges.');
        }

        return $next($request);
    }
}
