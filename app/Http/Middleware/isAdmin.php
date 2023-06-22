<?php

namespace App\Http\Middleware;

use App\Models\RoleModel;
use App\Models\UserRoleMappingModel;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class isAdmin
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {

        $checkAdmin = RoleModel::join("user_role_mapping", "user_role_mapping.role_id", "=", "roles.id")->where("roles.name", "admin")->where("user_role_mapping.user_id", Auth::user()->id)->first();

        if (!empty($checkAdmin)) {
            return $next($request);
        }

        return response(["status" => false, "messge" => "User is not admin"], 400);
    }
}
