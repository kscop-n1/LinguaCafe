<?php

namespace App\Http\Controllers\Users;

use App\Http\Controllers\Controller;
use App\Http\Requests\Users\AuthenticateUserRequest;
use App\Http\Resources\User\UserResource;
use App\Services\LanguageService;
use App\Services\SettingsService;
use App\Services\UserService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    public function __construct(
        private UserService $userService,
        private LanguageService $languageService,
        private SettingsService $settingsService
    ) {
        //
    }

    public function loginForm()
    {
        if (Auth::check()) {
            return redirect()->intended('/');
        }

        return view('auth.login');
    }

    public function login(AuthenticateUserRequest $request)
    {
        $email = $request->validated('email');
        $password = $request->validated('password');

        if (!Auth::attempt([
            'email' => $email,
            'password' => $password,
        ])) {
            throw new \Exception('Incorrect e-mail or password.');
        }

        $request->session()->regenerate();
        Auth::logoutOtherDevices($password);

        return new UserResource(auth()->user());
    }

    public function logout(Request $request): Response
    {
        Auth::logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return response()->noContent();
    }
}
