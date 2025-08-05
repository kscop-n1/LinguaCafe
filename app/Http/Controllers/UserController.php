<?php

namespace App\Http\Controllers;

use App\Helpers\Language\LanguageConfig;
use App\Http\Requests\Users\AuthenticateUserRequest;
use App\Http\Requests\Users\CreateUserRequest;
use App\Http\Requests\Users\UpdatePasswordRequest;
use App\Http\Requests\Users\UpdateUserRequest;
use App\Http\Resources\User\UserResourceCollection;
use App\Models\User;
use App\Services\SettingsService;
use App\Services\UserService;
use Illuminate\Support\Facades\Auth;

class UserController extends Controller
{
    public function __construct(
        private UserService $userService,
        private SettingsService $settingsService
    ) {
        //
    }

    public function getInitUserData()
    {
        $userCount = User::count();

        if (!Auth::check()) {
            return response()->json([
                'userCount' => $userCount,
            ]);
        }

        $selectedLanguage = Auth::user()->selected_language;
        $userName = Auth::user()->name;
        $userEmail = Auth::user()->email;
        $isAdmin = Auth::user()->is_admin === 1;
        $theme = $_COOKIE['theme'] ?? 'dark';

        $themeSettingNames = collect(['textStyling', 'vuetifyThemes']);

        $themeSettings = $this->settingsService->getUserSettingsByName(
            Auth::user(),
            $themeSettingNames,
        );

        return response()->json([
            'language' => $selectedLanguage,
            'userCount' => $userCount,
            'userName' => $userName,
            'userEmail' => $userEmail,
            'isAdmin' => $isAdmin,
            'theme' => $theme,
            'themeSettings' => $themeSettings,
            'userUuid' => Auth::user()->uuid,
        ]);
    }

    public function isUserPasswordChanged()
    {
        return response()->json([
            'data' => (bool) Auth::user()->password_changed,
        ]);
    }

    public function getUsers()
    {
        $user = Auth::user();

        $users = $this->userService->getUsers($user);

        return new UserResourceCollection($users);
    }

    // TODO: move to authController
    public function showLoginForm()
    {
        if (Auth::check()) {
            return redirect()->intended('/');
        }

        return view('auth.login');
    }

    // TODO: move to authController
    public function authenticateUser(AuthenticateUserRequest $request)
    {
        $email = $request->validated('email');
        $password = $request->validated('password');

        // TODO: move to service
        if (!Auth::attempt([
            'email' => $email,
            'password' => $password,
        ])) {
            throw new \Exception('Login error.');
        }

        $request->session()->regenerate();
        Auth::logoutOtherDevices($password);

        return response()->noContent();
    }

    public function updatePassword(UpdatePasswordRequest $request)
    {
        $user = Auth::user();
        $password = $request->validated('password');

        $this->userService->updatePassword($user, $password);

        return response()->noContent();
    }

    public function createUser(CreateUserRequest $request)
    {
        $userCount = User::count();
        $name = $request->validated('name');
        $email = $request->validated('email');
        $password = $request->validated('password');
        $isAdmin = $request->validated('isAdmin');
        $passwordChanged = $userCount === 0;

        if (!Auth::check() && $userCount !== 0) {
            throw new \Exception('Not authorized to create a user.');
        }

        $this->userService->createUser($name, $email, $password, $isAdmin, $passwordChanged);

        return response()->noContent();
    }

    public function updateUser(UpdateUserRequest $request, User $user)
    {
        $name = $request->validated('name');
        $email = $request->validated('email');
        $isAdmin = $request->validated('isAdmin');

        $this->userService->updateUser($user, $name, $email, $isAdmin);

        return response()->noContent();
    }

    public function deleteUserLanguageData($language)
    {
        $user = Auth::user();
        $language = LanguageConfig::load($language);

        $this->userService->deleteUserLanguageData($user, $language);

        return response()->noContent();
    }
}
