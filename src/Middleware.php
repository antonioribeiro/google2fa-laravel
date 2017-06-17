<?php

namespace PragmaRX\Google2FALaravel;

use Auth;
use Closure;
use App\User;
use App\Mail\sendLoginOTP;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Mail;

class Middleware
{
    public function handle($request, Closure $next)
    {
        $user = Auth::user();

        // Skip if unauthenticated (not needed, when middleware is attached only to "protected" routes)
        if (!$user)
        {
            return $next($request);
        }

        // Skip if G2FA enabled and authorized in current session
        if ($request->session()->get('otp_authorized', false))
        {
            return $next($request);
        }

        // Add g2fa_authorized to session (be sure to clear out on logout, i.e. App\Listeners\LogSuccessfulLogout.php)
        if ($user->login_token == $request->input('otp'))
        {
            $request->session()->put('otp_authorized', true);

            return $next($request);
        }

        //generate new token
        $user->update([
            'login_token' => str_random(6),
        ]);

        //send new login token
        Mail::to($user->email)->send(new sendLoginOTP($user));

        return new Response(view('user.loginOTP'));
    }
}
