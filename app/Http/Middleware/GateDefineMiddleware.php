<?php

namespace App\Http\Middleware;

use App\Models\Permission;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Gate;

class GateDefineMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if(auth()->check()){
            $permissions = Permission::whereHas('roles', function($query){
                $query->where('roles.id', auth()->user()->role_id);
            })->get();

            foreach($permissions as $permission)
            {
                Gate::define($permission->name, fn() => true);
            }
        }
        return $next($request);
    }
}
