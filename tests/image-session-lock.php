<?php
declare(strict_types=1);
require dirname(__DIR__) . '/vendor/autoload.php';

use App\Support\PrivateImageResponse;

// Isolated temporary sessions; no .env, database or remote Storage.
function openTestSession(string $directory): void
{
    ini_set('session.use_cookies', '0');
    session_cache_limiter('');
    session_save_path($directory);
    session_id('image-session-qa');
    session_start();
}
function verify(bool $condition, string $message): void
{
    if (!$condition) throw new RuntimeException($message);
}
if (($argv[1] ?? '') === '--worker') {
    openTestSession($argv[2]);
    if ($argv[3] === 'download') {
        $_SESSION['last_activity'] = 12345;
        verify(!PrivateImageResponse::isNotModified('img-pets', 'qa.webp'), 'Primeiro acesso deve baixar.');
        verify(session_status() === PHP_SESSION_NONE, 'Lock ainda ativo antes do download.');
        echo "READY\n";
        fflush(STDOUT);
        fgets(STDIN); // Parent simulates a slow download, then allows completion.
        exit;
    }
    verify(($_SESSION['last_activity'] ?? null) === 12345, 'Atualizacao de sessao nao persistiu.');
    if ($argv[3] === 'logout') {
        $_SESSION = [];
        session_destroy();
    } else {
        $_SESSION['parallel'] = 'preserved';
        session_write_close();
    }
    echo "DONE\n";
    exit;
}

$directory = sys_get_temp_dir() . '/beleza-image-session-' . bin2hex(random_bytes(8));
mkdir($directory, 0700);
$workers = [];
try {
    foreach (['update', 'logout'] as $mode) {
        $slow = proc_open([PHP_BINARY, __FILE__, '--worker', $directory, 'download'],
            [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $slowPipes);
        verify(is_resource($slow), 'Worker de download indisponivel.');
        $workers[] = $slow;
        stream_set_timeout($slowPipes[1], 5);
        verify(trim((string) fgets($slowPipes[1])) === 'READY', 'Download nao liberou a sessao.');
        $parallel = proc_open([PHP_BINARY, __FILE__, '--worker', $directory, $mode],
            [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $parallelPipes);
        verify(is_resource($parallel), 'Worker paralelo indisponivel.');
        $workers[] = $parallel;
        fclose($parallelPipes[0]);
        stream_set_timeout($parallelPipes[1], 5);
        verify(trim((string) fgets($parallelPipes[1])) === 'DONE', 'Requisicao paralela bloqueada pelo download.');
        verify(proc_get_status($slow)['running'], 'Download deveria continuar pendente.');
        verify(stream_get_contents($parallelPipes[2]) === '', 'Erro no worker paralelo.');
        fclose($parallelPipes[1]); fclose($parallelPipes[2]);
        verify(proc_close($parallel) === 0, 'Worker paralelo falhou.');
        fwrite($slowPipes[0], "finish\n"); fclose($slowPipes[0]);
        verify(stream_get_contents($slowPipes[2]) === '', 'Erro no download.');
        fclose($slowPipes[1]); fclose($slowPipes[2]);
        verify(proc_close($slow) === 0, 'Download falhou.');
        if ($mode === 'logout') {
            verify(!is_file($directory . '/sess_image-session-qa'), 'Download restaurou sessao apos logout.');
        } else {
            openTestSession($directory);
            verify($_SESSION['parallel'] === 'preserved', 'Download sobrescreveu atualizacao paralela.');
            session_write_close();
        }
    }
    openTestSession($directory);
    $_SERVER['HTTP_IF_NONE_MATCH'] = '"' . hash('sha256', "img-pets\0qa.webp") . '"';
    verify(PrivateImageResponse::isNotModified('img-pets', 'qa.webp'), 'ETag nao respondeu 304.');
    verify(http_response_code() === 304 && session_status() === PHP_SESSION_NONE, '304 reteve sessao.');
    verify(PrivateImageResponse::isNotModified('img-pets', 'qa.webp'), 'Chamada sem sessao deve ser segura.');
} finally {
    foreach ($workers as $worker) if (is_resource($worker)) { proc_terminate($worker); proc_close($worker); }
    if (session_status() === PHP_SESSION_ACTIVE) session_write_close();
    $file = $directory . '/sess_image-session-qa';
    if (is_file($file)) unlink($file);
    rmdir($directory);
}
echo "OK: download libera lock; atualizacao paralela, logout sem ressurreicao, persistencia e 304.\n";
