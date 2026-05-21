<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureActiveCompanyAndBranch
{
    /**
     * @param Closure(Request): Response $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        abort_unless($user, 401);

        if ($user->company && $user->company->status !== 'ACTIVE') {
            abort(403, 'La empresa no está activa.');
        }

        if ($user->branch && $user->branch->status !== 'ACTIVE') {
            abort(403, 'La sucursal no está activa.');
        }

        if (! session()->has('active_company_id') && $user->company_id) {
            session(['active_company_id' => $user->company_id]);
        }

        if (! session()->has('active_branch_id') && $user->branch_id) {
            session(['active_branch_id' => $user->branch_id]);
        }

        return $next($request);
    }
}
