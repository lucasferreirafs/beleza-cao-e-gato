<?php
declare(strict_types=1);
require dirname(__DIR__) . '/vendor/autoload.php';

use App\Repositories\AppointmentStorage;

// PDO simulado: nenhuma conexao ou alteracao em banco real.
final class CapabilityStatement extends PDOStatement
{
    public function __construct(private array $rows) {}
    public function fetchAll(int $mode = PDO::FETCH_DEFAULT, mixed ...$args): array { return $this->rows; }
}
final class CapabilityConnection extends PDO
{
    public int $queries = 0;
    public bool $fail = false;
    public function __construct(public array $rows = []) {}
    public function query(string $query, ?int $fetchMode = null, mixed ...$args): PDOStatement|false
    {
        $this->queries++;
        if ($this->fail) throw new RuntimeException('Falha simulada');
        return new CapabilityStatement($this->rows);
    }
}
function verify(bool $result, string $message): void
{
    if (!$result) throw new RuntimeException($message);
}

$modern = [['relation' => 'public.appointments', 'attname' => 'duration_minutes']];
foreach (['appointment_id', 'service_id', 'position', 'service_name', 'price', 'duration_minutes'] as $column) {
    $modern[] = ['relation' => 'public.appointment_services', 'attname' => $column];
}
$pdo = new CapabilityConnection($modern);
$storage = new AppointmentStorage($pdo);
foreach (range(1, 3) as $iteration) {
    verify((new AppointmentStorage($pdo))->capabilities() === ['duration' => true, 'items' => true], 'Schema moderno');
}
verify($pdo->queries === 1, 'Tres instancias devem executar somente uma consulta');

$legacy = new CapabilityConnection();
verify((new AppointmentStorage($legacy))->capabilities() === ['duration' => false, 'items' => false], 'Conexoes devem ser isoladas');
verify($legacy->queries === 1, 'Conexao nova deve consultar o catalogo');

foreach (['legacy', 'duration-only', 'items-only', 'modern'] as $mode) {
    $pdo->rows = array_values(array_filter($modern, static fn(array $row): bool =>
        $row['relation'] === 'public.appointments'
            ? in_array($mode, ['duration-only', 'modern'], true)
            : in_array($mode, ['items-only', 'modern'], true)));
    AppointmentStorage::clearCapabilities($pdo);
    verify($storage->capabilities() === [
        'duration' => in_array($mode, ['duration-only', 'modern'], true),
        'items' => in_array($mode, ['items-only', 'modern'], true),
    ], 'Invalidacao na mesma instancia: ' . $mode);
}

$failed = new CapabilityConnection($modern);
$failed->fail = true;
try { (new AppointmentStorage($failed))->capabilities(); throw new LogicException('Falha deveria propagar'); }
catch (RuntimeException $error) { verify($error->getMessage() === 'Falha simulada', 'Erro inesperado'); }
$failed->fail = false;
verify((new AppointmentStorage($failed))->capabilities()['items'], 'Erro nao deve envenenar o cache');
verify($failed->queries === 2, 'Deve tentar novamente apos erro');

$weak = WeakReference::create($legacy);
unset($legacy);
verify($weak->get() === null, 'Cache nao deve reter conexoes encerradas');
echo "OK: 3 consultas reduzidas a 1; isolamento, quatro schemas, invalidacao, erro e liberacao de conexao.\n";
