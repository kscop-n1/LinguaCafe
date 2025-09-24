<?php

namespace App\Services;

use App\Helpers\Language\LanguageConfig;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class UserService
{
    public function __construct() {}

    public function getUsers(User $user): Collection
    {
        $users = User::query()
            ->select([
                'id',
                'name',
                'email',
                'is_admin',
                'password_changed',
                'created_at',
            ])
            ->get();

        $users->transform(function (User $item) use ($user) {
            $item->is_current_user = $item->id === $user->id;

            return $item;
        });

        return $users;
    }

    public function updatePassword(User $user, string $password): void
    {
        $user->password = Hash::make($password);
        $user->password_changed = true;
        $user->save();
    }

    public function createUser(
        string $name,
        string $email,
        string $password,
        bool $isAdmin,
        bool $passwordChanged
    ): void {

        if (User::where('email', '=', $email)->exists()) {
            throw new \Exception('An other user already exists with this email address.');
        }

        $user = new User();
        $user->name = $name;
        $user->email = $email;
        $user->is_admin = $isAdmin;
        $user->password_changed = $passwordChanged;
        $user->uuid = Str::uuid()->toString();
        $user->password = Hash::make($password);
        $user->save();

        (new GoalService())->createGoalsForLanguage($user, LanguageConfig::load('spanish'));
    }

    public function updateUser(User $user, string $name, string $email, bool $isAdmin): void
    {
        if (User::query()->where('email', '=', $email)->where('id', '!=', $user->id)->exists()) {
            throw new \Exception('A user already exists with this email address.');
        }

        if (!$isAdmin && !User::where('id', '!=', $user->id)->where('is_admin', true)->exists()) {
            throw new \Exception('You cannot remove admin rights from the last admin user.');
        }

        $user->name = $name;
        $user->email = $email;
        $user->is_admin = $isAdmin;
        $user->save();
    }
}
