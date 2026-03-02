<?php


namespace App\Http\Middleware;

use Closure;
use App\Support\AuthStorage;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class TeacherAuth
{
    public function handle(Request $request, Closure $next): Response
    {
        if (!AuthStorage::get('auth_token')) {
            return redirect('/');
        }

        return $next($request);
    }
}
