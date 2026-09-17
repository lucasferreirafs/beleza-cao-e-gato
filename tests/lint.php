<?php
$root = dirname(__DIR__);
$it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS));
$failed = [];
foreach ($it as $file) {
    if ($file->getExtension() !== 'php' || str_contains($file->getPathname(), DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR))
        continue;
    exec('php -l ' . escapeshellarg($file->getPathname()), $out, $code);
    if ($code !== 0)
        $failed[] = $file->getPathname();
}
if ($failed) {
    fwrite(STDERR, "Falha:\n" . implode("\n", $failed));
    exit(1);
}
echo "PHP lint OK\n";
