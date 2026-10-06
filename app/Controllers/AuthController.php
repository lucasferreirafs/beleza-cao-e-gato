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
        $remember = $r->input('remember') === '1';
        $persistence = new \App\Services\RememberMeService();
        if ($remember) $persistence->issue($user['id']);
        else $persistence->forget();
        Session::login($user, $remember);
        Csrf::regenerate();
        Response::redirect('/dashboard');
    }

    public function logout(Request $r): void
    {
        (new \App\Services\RememberMeService())->forget();
        Session::logout();
        Response::redirect('/login');
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
            ],
            'auth'
        );
    }

    public function recoveryConfirmation(Request $r): void
    {
        $email = Session::get('password_recovery_email');
        if (!is_string($email) || $email === '') Response::redirect('/recuperar-senha');

        header('Cache-Control: no-store, private');
        View::render('pages/auth/recovery-confirmation', [
            'title' => 'Verifique seu e-mail',
            'email' => $email,
            'error' => Session::flash('error'),
            'notice' => Session::flash('recovery_notice'),
            'devUrl' => Session::flash('dev_url'),
        ], 'auth');
    }

    public function sendRecovery(Request $r): void
    {
        try {
            if ((new RateLimiter())->consume('recovery|' . ($_SERVER['REMOTE_ADDR'] ?? 'unknown'), 6, 900) > 0)
                throw new InvalidArgumentException('Muitas solicitações. Tente novamente mais tarde.');

            $email = $r->input('email', '');
            if (!is_string($email)) throw new InvalidArgumentException('Informe um endereço de e-mail válido.');
            $result = (new AccountService())->requestPasswordReset($email);
            $email = \App\Validation\Validator::email($email);
            Session::put('password_recovery_email', $email);
            Session::flash('dev_url', '');
            Session::flash('recovery_notice', '');
            if (!$result['sent'] && $result['debugUrl'] !== null
                && Env::get('APP_ENV') === 'development' && Env::bool('APP_DEBUG')) {
                Session::flash('dev_url', $result['debugUrl']);
            }

        } catch (InvalidArgumentException $e) {
            Session::flash('error', $e->getMessage());
            Response::redirect('/recuperar-senha');
        }
        Response::redirect('/recuperar-senha/confirmacao');
    }

    public function resendRecovery(Request $r): void
    {
        $email = Session::get('password_recovery_email');
        if (!is_string($email) || $email === '') Response::redirect('/recuperar-senha');

        try {
            $ip = (string) ($_SERVER['REMOTE_ADDR'] ?? 'unknown');
            $key = 'recovery-resend|' . $ip . '|' . hash('sha256', $email);
            if ((new RateLimiter())->consume($key, 3, 900) > 0) {
                throw new InvalidArgumentException('Muitas solicitações. Tente novamente mais tarde.');
            }

            $result = (new AccountService())->requestPasswordReset($email);
            Session::flash('dev_url', '');
            if (!$result['sent'] && $result['debugUrl'] !== null
                && Env::get('APP_ENV') === 'development' && Env::bool('APP_DEBUG')) {
                Session::flash('dev_url', $result['debugUrl']);
            }
            Session::flash('recovery_notice', 'Se houver uma conta associada a este endereço, enviaremos um novo link. O link anterior não poderá mais ser utilizado.');
        } catch (InvalidArgumentException $exception) {
            Session::flash('error', $exception->getMessage());
        }

        Response::redirect('/recuperar-senha/confirmacao');
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
        header('Cache-Control: no-store, private');
        header('Referrer-Policy: no-referrer');
        $selector = $r->input('selector', '');
        $token = $r->input('token', '');
        $selector = is_string($selector) ? $selector : '';
        $token = is_string($token) ? $token : '';
        $validToken = (new AccountService())->isPasswordResetValid($selector, $token);

        View::render(
            'pages/auth/reset',
            [
                'title' => 'Redefinir senha',
                'selector' => $selector,
                'token' => $token,
                'validToken' => $validToken,
                'error' => Session::flash('error') ?? ($validToken ? null : 'Este link é inválido, expirou ou já foi utilizado.'),
            ],
            'auth'
        );
    }

    public function resetPassword(Request $r): void
    {
        try {
            if ((new RateLimiter())->consume('reset|' . ($_SERVER['REMOTE_ADDR'] ?? 'unknown'), 10, 900) > 0)
                throw new InvalidArgumentException('Muitas tentativas. Tente novamente mais tarde.');

            if (!(new AccountService())->resetPassword($r->all()))
                throw new InvalidArgumentException('Link inválido ou expirado.');

            Session::forget('password_recovery_email');
            Session::flash('success', 'Senha alterada com sucesso.');
            Response::redirect('/login');
        } catch (InvalidArgumentException $e) {
            Session::flash('error', $e->getMessage());
            $selector = $r->input('selector', '');
            $token = $r->input('token', '');
            $selector = is_string($selector) ? $selector : '';
            $token = is_string($token) ? $token : '';
            Response::redirect('/redefinir-senha?selector=' . rawurlencode($selector) . '&token=' . rawurlencode($token));
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
