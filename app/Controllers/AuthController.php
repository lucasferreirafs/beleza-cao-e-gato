<?php
declare(strict_types=1);
namespace App\Controllers;

use App\Core\{
    Request,
    Response,
    Session,
    View,
    Env
};
use App\Security\{
    JwtService,
    Csrf,
    RateLimiter
};
use App\Services\{
    AuthService,
    AccountService
};
use InvalidArgumentException;

final class AuthController
{
    public function landing(Request $r): void
    {
        if (Session::user())
            Response::redirect('/dashboard');

        View::render(
            'pages/landing',
            ['title' => 'Gestão inteligente para pet shops'],
            'auth'
        );
    }

    public function loginForm(Request $r): void
    {
        if (Session::user())
            Response::redirect('/dashboard');
        View::render(
            'pages/auth/login',
            [
                'title' => 'Entrar',
                'error' => Session::flash('error'),
                'success' => Session::flash('success')
            ],
            'auth'
        );
    }

    public function login(Request $r): void
    {
        $email = filter_var(trim((string) $r->input('email')), FILTER_VALIDATE_EMAIL);
        $password = (string) $r->input('password');

        if (!$email || $password === '') {
            Session::flash('error', 'Informe e-mail e senha válidos.');
            Response::redirect('/login');
        }

        $limitKey = 'login|' . ($_SERVER['REMOTE_ADDR'] ?? 'unknown') . '|' . strtolower((string) $email);
        $limiter = new RateLimiter();
        $retryAfter = $limiter->consume($limitKey, 6, 900);

        if ($retryAfter > 0) {
            Session::flash('error', 'Muitas tentativas. Aguarde alguns minutos e tente novamente.');
            Response::redirect('/login');
        }

        $user = (new AuthService())->authenticate($email, $password);
        if (!$user) {
            Session::flash('error', 'E-mail ou senha inválidos.');
            Response::redirect('/login');
        }

        $limiter->clear($limitKey);
        Session::login($user, (bool) $r->input('remember'));
        Csrf::regenerate();
        Response::redirect('/dashboard');
    }

    public function logout(Request $r): void
    {
        Session::logout();
        Response::redirect('/login');
    }

    public function token(Request $r): void
    {
        $user = Session::user();
        try {
            Response::json([
                'token' => (new JwtService())->issue(['sub' => (string) $user['id'], 'role' => $user['role']]),
                'token_type' => 'Bearer',
                'expires_in' => (int) \App\Core\Env::get('JWT_EXPIRATION', 3600)
            ]);
        } catch (\Throwable $e) {
            Response::json(['error' => 'JWT indisponível.'], 503);
        }
    }

    public function registerForm(Request $r): void
    {
        if (Session::user())
            Response::redirect('/dashboard');
        View::render(
            'pages/auth/register',
            [
                'title' => 'Criar conta',
                'error' => Session::flash('error'),
                'old'   => Session::consumeOld(),
            ],
            'auth'
        );
    }

    public function register(Request $r): void
    {
        try {
            if ((new RateLimiter())->consume('register|' . ($_SERVER['REMOTE_ADDR'] ?? 'unknown'), 10, 3600) > 0)
                throw new InvalidArgumentException('Limite de cadastros atingido. Tente novamente mais tarde.');

            $result = (new AccountService())->register($r->all());
            Session::put('pending_verification_email', $result['email']);

            if (!$result['sent'] && Env::get('APP_ENV') === 'development' && Env::bool('APP_DEBUG')) {
                Session::flash('dev_code', $result['code']);
            }

            Session::flash('success', 'Conta criada. Informe o código enviado ao seu e-mail.');
            Response::redirect('/verificar-email');
        } catch (InvalidArgumentException $e) {
            Session::flash('error', $e->getMessage());
            Session::flashOld(array_diff_key($r->all(), array_flip(['password', 'password_confirmation', '_token'])));
            Response::redirect('/cadastro');
        }
    }

    public function recovery(Request $r): void
    {
        View::render(
            'pages/auth/recovery',
            [
                'title' => 'Recuperar senha',
                'error' => Session::flash('error'),
                'success' => Session::flash('success'),
                'devUrl' => Session::flash('dev_url')
            ],
            'auth'
        );
    }

    public function sendRecovery(Request $r): void
    {
        try {
            if ((new RateLimiter())->consume('recovery|' . ($_SERVER['REMOTE_ADDR'] ?? 'unknown'), 6, 900) > 0)
                throw new InvalidArgumentException('Muitas solicitações. Tente novamente mais tarde.');

            $url = (new AccountService())->requestPasswordReset((string) $r->input('email'));
            if ($url && Env::get('APP_ENV') === 'development' && Env::bool('APP_DEBUG'))
                Session::flash('dev_url', $url);

            Session::flash('success', 'Se o e-mail estiver cadastrado, você receberá as instruções em instantes.');
        } catch (InvalidArgumentException $e) {
            Session::flash('error', $e->getMessage());
        }
        Response::redirect('/recuperar-senha');
    }

    public function verification(Request $r): void
    {
        View::render(
            'pages/auth/verification',
            [
                'title' => 'Verificar e-mail',
                'email' => Session::get('pending_verification_email', (string) $r->input('email')),
                'error' => Session::flash('error'),
                'success' => Session::flash('success'),
                'devCode' => Session::flash('dev_code')
            ],
            'auth'
        );
    }

    public function verify(Request $r): void
    {
        $code = $r->input('code', []);
        $code = is_array($code) ? implode('', $code) : (string) $code;

        try {
            $key = 'verify|' . ($_SERVER['REMOTE_ADDR'] ?? 'unknown') . '|' . strtolower((string) $r->input('email'));

            if ((new RateLimiter())->consume($key, 10, 900) > 0)
                throw new InvalidArgumentException('Muitas tentativas. Solicite um novo código mais tarde.');

            if (!(new AccountService())->verify((string) $r->input('email'), $code))
                throw new InvalidArgumentException('Código inválido ou expirado.');

            (new RateLimiter())->clear($key);

            Session::forget('pending_verification_email');
            Session::flash('success', 'E-mail verificado. Agora você pode entrar.');
            Response::redirect('/login');
        } catch (InvalidArgumentException $e) {
            Session::flash('error', $e->getMessage());
            Response::redirect('/verificar-email');
        }
    }

    public function resendVerification(Request $r): void
    {
        try {
            if ((new RateLimiter())->consume('resend|' . ($_SERVER['REMOTE_ADDR'] ?? 'unknown'), 6, 900) > 0)
                throw new InvalidArgumentException('Muitas solicitações. Tente novamente mais tarde.');

            $result = (new AccountService())->resendVerification((string) $r->input('email'));
            if ($result['code'] && Env::get('APP_ENV') === 'development' && Env::bool('APP_DEBUG'))
                Session::flash('dev_code', $result['code']);

            Session::flash('success', 'Se a conta aguarda verificação, um novo código foi enviado.');
        } catch (InvalidArgumentException $e) {
            Session::flash('error', $e->getMessage());
        }
        Response::redirect('/verificar-email');
    }

    public function resetForm(Request $r): void
    {
        View::render(
            'pages/auth/reset',
            [
                'title' => 'Redefinir senha',
                'email' => (string) $r->input('email'),
                'token' => (string) $r->input('token'),
                'error' => Session::flash('error')
            ],
            'auth'
        );
    }

    public function resetPassword(Request $r): void
    {
        try {
            if ((new RateLimiter())->consume('reset|' . ($_SERVER['REMOTE_ADDR'] ?? 'unknown') . '|' . strtolower((string) $r->input('email')), 10, 900) > 0)
                throw new InvalidArgumentException('Muitas tentativas. Tente novamente mais tarde.');

            if (!(new AccountService())->resetPassword($r->all()))
                throw new InvalidArgumentException('Link inválido ou expirado.');

            Session::flash('success', 'Senha alterada com sucesso.');
            Response::redirect('/login');
        } catch (InvalidArgumentException $e) {
            Session::flash('error', $e->getMessage());
            Response::redirect('/redefinir-senha?email=' . rawurlencode((string) $r->input('email')) . '&token=' . rawurlencode((string) $r->input('token')));
        }
    }
    public function passwordStrength(Request $r): void
    {
        $password = (string) $r->input('password');
        $score = 0;

        if (mb_strlen($password) >= 8)  $score++;
        if (preg_match('/[A-Z]/', $password)) $score++;
        if (preg_match('/[a-z]/', $password)) $score++;
        if (preg_match('/[0-9]/', $password)) $score++;
        if (preg_match('/[^A-Za-z0-9]/', $password)) $score++;

        // Map score (0-5) to level (0-4)
        $level = match (true) {
            $score <= 1 => 0,
            $score === 2 => 1,
            $score === 3 => 2,
            $score === 4 => 3,
            default      => 4,
        };

        $labels = [
            ['label' => 'Muito fraca', 'emoji' => '🔴'],
            ['label' => 'Fraca',       'emoji' => '🟠'],
            ['label' => 'Razoável',    'emoji' => '🟡'],
            ['label' => 'Forte',       'emoji' => '🟢'],
            ['label' => 'Muito forte', 'emoji' => '✨'],
        ];

        Response::json([
            'level' => $level,
            'bars'  => $level + 1,
            ...$labels[$level],
        ]);
    }
}
