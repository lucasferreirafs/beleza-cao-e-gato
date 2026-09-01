<?php
declare(strict_types= 1);

namespace App\Services;

use App\Repositories\UserRepository;

final class AuthService
{
    public function authenticate(string $email, string $password): ?array
    {
        $user = (new UserRepository())->findByEmail($email);

        if(!$user || $user['status'] !== 'active' || !password_verify($password, $user['password_hash'])) {
            password_verify($password, '30f9ddc519a9858c9f339678880fd99bdff46b1a929a1635196ef396060d874cfdc00d628b51f718a95cb6d984c17734078418b745d640ae7f4281dfeb96983a');
            return null;
        }

        unset($user['password']);

        return $user;
    }
}