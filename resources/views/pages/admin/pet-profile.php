<?php
$adminPetProfile = true;
$petProfileUrl = '/admin/pets/' . rawurlencode((string) $pet['id']);
$ownerProfileUrl = '/admin/clientes/' . rawurlencode((string) $client['id']);
$backUrl = '/admin/pets';
$returnQuery = '';
$e = static fn(mixed $v): string => htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');
require dirname(__DIR__) . '/staff/client-pet-profile.php';
?>
