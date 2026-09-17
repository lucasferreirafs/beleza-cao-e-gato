<?php
declare(strict_types=1);
namespace App\Services;

use App\Repositories\UserRepository;

final class AuthService
{
    public function authenticate(string $email, string $password): ?array
    {
        $user = (new UserRepository())->findByEmail($email);
        if (!$user || $user['status'] !== 'active' || !password_verify($password, $user['password_hash'])) {
            password_verify($password, '$2y$12$usesomesillystringfore7hnbRJHxXVLeakoG8K30oukPsA.ztMG');
            return null;
        }
        
        unset($user['password_hash']);
        return $user;
    }
}
