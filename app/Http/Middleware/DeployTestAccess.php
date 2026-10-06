<?php
namespace App\Http\Middleware;
use Closure;
use Illuminate\Http\Request;
class DeployTestAccess
{
    public function handle(Request $request, Closure $next)
    {
        $expected = (string) config('deploy.test_token');
        $provided = (string) $request->header('X-Deploy-Test-Token', '');
        if ($expected === '' || ! hash_equals($expected, $provided)) {
            return response()->json(['success' => false, 'message' => 'Akses pengujian memerlukan kunci.'], 401);
        }
        return $next($request);
    }
}
