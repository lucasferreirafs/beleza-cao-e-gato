<?php
$adminClientProfile = true;
$clientProfileUrl = '/admin/clientes/' . rawurlencode((string) $client['id']);
$returnQuery = '';
$e = static fn(mixed $v): string => htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');
require dirname(__DIR__) . '/staff/client-profile.php';
?>
