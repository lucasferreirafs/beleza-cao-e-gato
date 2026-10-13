<?php
declare(strict_types=1);
namespace App\Repositories;

use App\Core\Database;
use App\Validation\Validator;
use DomainException;
use InvalidArgumentException;
use PDO;
use App\Support\Uuid;

final class ManagementRepository
{
    public function formOptions(): array
    {
        $pdo = Database::connection();
        return [
            'clients' => $pdo->query("SELECT id,name FROM users WHERE role='client' ORDER BY name")->fetchAll(),
        ];
    }

    public function findEditable(string $section, string $id): ?array
    {
        $definition = match ($section) {
            'clientes' => ["SELECT id,name,email,phone,birth_date,status FROM users WHERE id=:id AND role='client'", null],
            'funcionarios' => ["SELECT id,name,email,phone,cpf,birth_date,status FROM users WHERE id=:id AND role='staff'", null],
            'servicos' => ['SELECT id,name,description,duration_minutes,price,active FROM services WHERE id=:id', null],
            'pets' => ['SELECT * FROM pets WHERE id=:id', null],
            default => null,
        };
        if (!$definition)
            return null;
        $s = Database::connection()->prepare($definition[0]);
        $s->execute(['id' => $id]);
        return $s->fetch() ?: null;
    }

    public function save(string $section, ?string $id, array $input): string
    {
        if ($id === null && in_array($section, ['clientes', 'pets', 'funcionarios'], true)) {
            throw new InvalidArgumentException('A criação administrativa deste cadastro não está disponível.');
        }

        $pdo = Database::connection();
        if (in_array($section, ['clientes', 'funcionarios'], true)) {
            $role = $section === 'clientes' ? 'client' : 'staff';
            $data = [
                'name' => Validator::text($input['name'] ?? '', 'Nome'),
                'email' => Validator::email($input['email'] ?? ''),
                'phone' => Validator::phone($input['phone'] ?? ''),
                'cpf' => $section === 'clientes' ? null : Validator::cpf($input['cpf'] ?? ''),
                'birth_date' => Validator::date($input['birth_date'] ?? '')
            ];
            $users = new UserRepository();

            if ($users->emailExists($data['email'], $id))
                throw new InvalidArgumentException('Já existe uma conta com este e-mail.');

            if ($data['cpf'] !== null && $users->cpfExists($data['cpf'], $id))
                throw new InvalidArgumentException('Já existe uma conta com este CPF.');

            $password = (string) ($input['password'] ?? '');
            if ($password !== '')
                $password = Validator::password($password);

            $sql = 'UPDATE users SET name=:name,email=:email,phone=:phone,birth_date=:birth_date,updated_at=now()' . ($data['cpf'] !== null ? ',cpf=:cpf' : '') . ($password !== '' ? ',password_hash=:password_hash' : '') . ' WHERE id=:id AND role=:role';
            $params = array_diff_key($data, ['cpf' => true]) + ['id' => $id, 'role' => $role];
            if ($data['cpf'] !== null) $params['cpf'] = $data['cpf'];

            if ($password !== '')
                $params['password_hash'] = password_hash($password, PASSWORD_DEFAULT);

            $pdo->prepare($sql)->execute($params);
            return $id;
        }

        if ($section === 'servicos') {
            if (!is_string($input['name'] ?? null) || !is_string($input['description'] ?? null)) {
                throw new InvalidArgumentException('Informe um nome e uma descrição válidos para o serviço.');
            }
            $name = Validator::text($input['name'] ?? '', 'Nome do serviço');
            $description = Validator::text($input['description'] ?? '', 'Descrição', 5, 2000);
            $duration = filter_var($input['duration_minutes'] ?? null, FILTER_VALIDATE_INT);
            $price = filter_var($input['price'] ?? null, FILTER_VALIDATE_FLOAT);

            if (!$duration || $duration > 1440 || $price === false || $price < 0)
                throw new InvalidArgumentException('Informe duração e preço válidos.');

            if ($id) {
                $pdo->prepare('
                    UPDATE services 
                    SET name=:name,description=:description,duration_minutes=:duration,price=:price,updated_at=now() 
                    WHERE id=:id
                ')->execute(compact('name', 'description', 'duration', 'price', 'id'));

                return $id;
            }

            $s = $pdo->prepare('
                INSERT INTO services(name,description,duration_minutes,price) 
                VALUES (:name,:description,:duration,:price) 
                RETURNING id
            ');

            $s->execute(compact('name', 'description', 'duration', 'price'));
            return (string) $s->fetchColumn();
        }

        if ($section === 'pets') {
            $owner = Uuid::parse($input['owner_id'] ?? null);
            if (!$owner) throw new InvalidArgumentException('Selecione um cliente válido.');
            $ownerCheck = $pdo->prepare("SELECT 1 FROM users WHERE id=:id AND role='client'");
            $ownerCheck->execute(['id' => $owner]);

            if (!$owner || !$ownerCheck->fetchColumn())
                throw new InvalidArgumentException('Selecione um cliente válido.');

            $species = (string) ($input['species'] ?? '');
            $sex = (string) ($input['sex'] ?? '');

            if (!in_array($species, ['Cachorro', 'Gato'], true) || !in_array($sex, ['Fêmea', 'Macho'], true))
                throw new InvalidArgumentException('Espécie ou sexo inválido.');

            $data = [
                'name' => Validator::text($input['name'] ?? '', 'Nome do pet', 1, 80),
                'breed' => Validator::text($input['breed'] ?? '', 'Raça', 1, 100),
                'birth_date' => Validator::date($input['birth_date'] ?? '')
            ] + $input;

            $current = $this->findEditable('pets', $id);
            if (!$current)
                throw new InvalidArgumentException('Pet não encontrado.');

            (new PetRepository())->update($id, $current['owner_id'], $data);

            if ($current['owner_id'] !== $owner)
                $pdo->prepare('UPDATE pets SET owner_id=:owner WHERE id=:id')->execute(['owner' => $owner, 'id' => $id]);

            return $id;
        }
        throw new InvalidArgumentException('Tipo de cadastro inválido.');
    }

    public function adminRows(string $section): array
    {
        if ($section === 'agendamentos') {
            return array_map(static fn(array $a): array => [
                'id' => $a['id'],
                'scheduled_at' => $a['scheduled_at'],
                'pet_name' => $a['pet_name'],
                'client_name' => $a['client_name'],
                'staff_name' => $a['staff_name'] ?? null,
                'services' => $a['services'] ?? [],
                'duration_minutes' => (int) ($a['duration_minutes'] ?? 0),
                'price' => (float) ($a['price'] ?? 0),
                'notes' => $a['notes'] ?? null,
                'status' => $a['status'],
            ], (new AppointmentRepository())->allForAdmin());
        }
        $sql = match ($section) {
            'clientes' => "SELECT u.id,u.name label,concat(u.email,' • ',coalesce(u.phone,'Sem telefone')) detail,concat(count(DISTINCT p.id),' pets') links,u.status FROM users u LEFT JOIN pets p ON p.owner_id=u.id WHERE u.role='client' GROUP BY u.id ORDER BY u.name",
            'pets' => "SELECT p.id,p.name label,concat(p.species,' • ',p.breed) detail,u.name links,'active' status FROM pets p JOIN users u ON u.id=p.owner_id ORDER BY p.name",
            'funcionarios' => "SELECT u.id,u.name label,concat(u.email,' • ',coalesce(u.phone,'Sem telefone')) detail,'Equipe da unidade' links,u.status FROM users u WHERE u.role='staff' ORDER BY u.name",
            default => null,
        };
        return $sql ? Database::connection()->query($sql)->fetchAll() : [];
    }

    /** @return array{services:list<array<string,mixed>>,total:int,page:int,pages:int,perPage:int} */
    public function adminServices(string $search, string $status, int $page, int $perPage = 6): array
    {
        $pdo = Database::connection();
        $where = [];
        $params = [];

        if ($search !== '') {
            $pattern = '%' . strtr($search, ['!' => '!!', '%' => '!%', '_' => '!_']) . '%';
            $where[] = "(s.name ILIKE :name ESCAPE '!' OR s.description ILIKE :description ESCAPE '!')";
            $params['name'] = $pattern;
            $params['description'] = $pattern;
        }

        if (in_array($status, ['active', 'inactive'], true)) {
            $where[] = 's.active=:active';
            $params['active'] = $status === 'active';
        }

        $whereSql = $where ? ' WHERE ' . implode(' AND ', $where) : '';
        $count = $pdo->prepare('SELECT count(*) FROM services s' . $whereSql);
        foreach ($params as $key => $value) {
            $count->bindValue(':' . $key, $value, is_bool($value) ? PDO::PARAM_BOOL : PDO::PARAM_STR);
        }
        $count->execute();
        $total = (int) $count->fetchColumn();
        $pages = max(1, (int) ceil($total / $perPage));
        $page = max(1, min($page, $pages));

        $statement = $pdo->prepare('SELECT s.id,s.name,s.description,s.duration_minutes,s.price,s.active,
                COALESCE(appointment_counts.total,0)::int AS appointment_count
            FROM services s
            LEFT JOIN (
                SELECT service_links.service_id,COUNT(*) AS total
                FROM (
                    SELECT service_id,appointment_id FROM appointment_services
                    UNION
                    SELECT service_id,id AS appointment_id FROM appointments
                ) service_links
                GROUP BY service_links.service_id
            ) appointment_counts ON appointment_counts.service_id=s.id' . $whereSql . '
            ORDER BY lower(s.name),s.id
            LIMIT :limit OFFSET :offset');
        foreach ($params as $key => $value) {
            $statement->bindValue(':' . $key, $value, is_bool($value) ? PDO::PARAM_BOOL : PDO::PARAM_STR);
        }
        $statement->bindValue(':limit', $perPage, PDO::PARAM_INT);
        $statement->bindValue(':offset', ($page - 1) * $perPage, PDO::PARAM_INT);
        $statement->execute();

        return [
            'services' => $statement->fetchAll(),
            'total' => $total,
            'page' => $page,
            'pages' => $pages,
            'perPage' => $perPage,
        ];
    }

    /** Admin-only directory data; CPF and authentication credentials are never selected here. */
    public function adminClients(string $search, int $page, int $perPage = 10): array
    {
        $pdo = Database::connection();
        $where = "u.role='client'";
        $params = [];

        if ($search !== '') {
            $pattern = '%' . strtr($search, ['!' => '!!', '%' => '!%', '_' => '!_']) . '%';
            $where .= " AND (u.name ILIKE :name ESCAPE '!' OR u.email ILIKE :email ESCAPE '!'";
            $params = ['name' => $pattern, 'email' => $pattern];
            $digits = preg_replace('/\D/', '', $search);
            if ($digits !== '' && preg_match('/^[0-9\s()+.\/-]+$/D', $search)) {
                $where .= " OR regexp_replace(coalesce(u.phone,''), '[^0-9]', '', 'g') LIKE :phone";
                $params['phone'] = '%' . $digits . '%';
            }
            $where .= ')';
        }

        $count = $pdo->prepare('SELECT count(*) FROM users u WHERE ' . $where);
        $count->execute($params);
        $total = (int) $count->fetchColumn();
        $pages = max(1, (int) ceil($total / $perPage));
        $page = max(1, min($page, $pages));

        $statement = $pdo->prepare('SELECT u.id,u.name,u.email,u.phone,u.status,count(p.id)::int AS pets_count
            FROM users u LEFT JOIN pets p ON p.owner_id=u.id
            WHERE ' . $where . '
            GROUP BY u.id
            ORDER BY lower(u.name),u.id
            LIMIT :limit OFFSET :offset');
        foreach ($params as $key => $value) $statement->bindValue(':' . $key, $value, PDO::PARAM_STR);
        $statement->bindValue(':limit', $perPage, PDO::PARAM_INT);
        $statement->bindValue(':offset', ($page - 1) * $perPage, PDO::PARAM_INT);
        $statement->execute();

        return [
            'clients' => $statement->fetchAll(),
            'total' => $total,
            'page' => $page,
            'pages' => $pages,
            'perPage' => $perPage,
        ];
    }

    /** Staff directory data excludes CPF and authentication credentials. */
    public function adminStaff(string $search, int $page, int $perPage = 10): array
    {
        $pdo = Database::connection();
        $where = "u.role='staff'";
        $params = [];

        if ($search !== '') {
            $pattern = '%' . strtr($search, ['!' => '!!', '%' => '!%', '_' => '!_']) . '%';
            $where .= " AND (u.name ILIKE :name ESCAPE '!' OR u.email ILIKE :email ESCAPE '!')";
            $params = ['name' => $pattern, 'email' => $pattern];
            $digits = preg_replace('/\\D/', '', $search);
            if ($digits !== '' && preg_match('/^[0-9\\s()+.\\/-]+$/D', $search)) {
                $where = substr($where, 0, -1) . " OR regexp_replace(coalesce(u.phone,''), '[^0-9]', '', 'g') LIKE :phone)";
                $params['phone'] = '%' . $digits . '%';
            }
        }

        $count = $pdo->prepare('SELECT count(*) FROM users u WHERE ' . $where);
        $count->execute($params);
        $total = (int) $count->fetchColumn();
        $pages = max(1, (int) ceil($total / $perPage));
        $page = max(1, min($page, $pages));

        $statement = $pdo->prepare('SELECT u.id,u.name,u.email,u.phone,u.status
            FROM users u WHERE ' . $where . '
            ORDER BY lower(u.name),u.id
            LIMIT :limit OFFSET :offset');
        foreach ($params as $key => $value) $statement->bindValue(':' . $key, $value, PDO::PARAM_STR);
        $statement->bindValue(':limit', $perPage, PDO::PARAM_INT);
        $statement->bindValue(':offset', ($page - 1) * $perPage, PDO::PARAM_INT);
        $statement->execute();

        return [
            'employees' => $statement->fetchAll(),
            'total' => $total,
            'page' => $page,
            'pages' => $pages,
            'perPage' => $perPage,
        ];
    }

    /** Admin-only employee profile; CPF is masked before it reaches the view. */
    public function adminStaffProfile(string $id): ?array
    {
        $statement = Database::connection()->prepare("SELECT id,name,email,phone,birth_date,status,created_at,
            CASE WHEN length(regexp_replace(coalesce(cpf,''), '[^0-9]', '', 'g')) >= 4
                THEN '***.***.***-' || right(regexp_replace(cpf, '[^0-9]', '', 'g'), 4)
                ELSE NULL END AS cpf_masked
            FROM users WHERE id=:id AND role='staff'");
        $statement->execute(['id' => $id]);
        return $statement->fetch() ?: null;
    }

    public function promoteAdminStaff(string $id): bool
    {
        $statement = Database::connection()->prepare("UPDATE users
            SET role='admin',auth_version=auth_version+1,updated_at=clock_timestamp()
            WHERE id=:id AND role='staff' AND status='active'");
        $statement->execute(['id' => $id]);
        return $statement->rowCount() === 1;
    }

    /**
     * Demote a staff account only after its open appointments have been resolved.
     * The staff-row lock serializes this operation with appointment creation.
     */
    public function demoteAdminStaff(string $id): string
    {
        $pdo = Database::connection();
        $pdo->beginTransaction();
        try {
            $staff = $pdo->prepare("SELECT id FROM users WHERE id=:id AND role='staff' FOR UPDATE");
            $staff->execute(['id' => $id]);
            if (!$staff->fetchColumn()) {
                $pdo->commit();
                return 'not_staff';
            }

            $openAppointments = $pdo->prepare("SELECT EXISTS (
                SELECT 1 FROM appointments
                WHERE staff_id=:id AND status IN ('pending', 'in_progress')
            )");
            $openAppointments->execute(['id' => $id]);
            if ((bool) $openAppointments->fetchColumn()) {
                $pdo->commit();
                return 'active_appointments';
            }

            $update = $pdo->prepare("UPDATE users
                SET role='client',auth_version=auth_version+1,updated_at=clock_timestamp()
                WHERE id=:id AND role='staff'");
            $update->execute(['id' => $id]);
            $demoted = $update->rowCount() === 1;
            $pdo->commit();
            return $demoted ? 'demoted' : 'not_staff';
        } catch (\Throwable $error) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            throw $error;
        }
    }

    /** CPF is masked in SQL so the full value never enters the view data. */
    public function adminClientProfile(string $id): ?array
    {
        $statement = Database::connection()->prepare("SELECT id,name,email,phone,birth_date,status,avatar_path,created_at,
            CASE WHEN length(regexp_replace(coalesce(cpf,''), '[^0-9]', '', 'g')) >= 4
                THEN '***.***.***-' || right(regexp_replace(cpf, '[^0-9]', '', 'g'), 4)
                ELSE NULL END AS cpf_masked
            FROM users WHERE id=:id AND role='client'");
        $statement->execute(['id' => $id]);
        return $statement->fetch() ?: null;
    }

    public function promoteAdminClient(string $id): bool
    {
        $statement = Database::connection()->prepare("UPDATE users
            SET role='staff',auth_version=auth_version+1,updated_at=clock_timestamp()
            WHERE id=:id AND role='client' AND status='active'");
        $statement->execute(['id' => $id]);
        return $statement->rowCount() === 1;
    }

    /**
     * Delete only clients without appointment history; preserve appointments as an audit record.
     * The shared booking lock prevents a reservation racing the pet/user cascade.
     * @return array{avatar_path:?string,pet_photo_paths:list<string|null>}|null
     */
    public function deleteAdminClient(string $id): ?array
    {
        $pdo = Database::connection();
        $pdo->beginTransaction();
        try {
            $pdo->query('SELECT pg_advisory_xact_lock(4242, 4)');
            $clientStatement = $pdo->prepare("SELECT id,avatar_path FROM users WHERE id=:id AND role='client' FOR UPDATE");
            $clientStatement->execute(['id' => $id]);
            $client = $clientStatement->fetch();
            if (!$client) {
                $pdo->commit();
                return null;
            }

            $petStatement = $pdo->prepare('SELECT id,photo_path FROM pets WHERE owner_id=:owner ORDER BY id FOR UPDATE');
            $petStatement->execute(['owner' => $id]);
            $pets = $petStatement->fetchAll();

            $appointments = $pdo->prepare('SELECT EXISTS(
                SELECT 1 FROM appointments a JOIN pets p ON p.id=a.pet_id WHERE p.owner_id=:owner
            )');
            $appointments->execute(['owner' => $id]);
            if ((bool) $appointments->fetchColumn()) {
                throw new DomainException('Este cliente possui agendamentos vinculados. O histórico precisa ser preservado; bloqueie a conta em vez de excluí-la.');
            }

            $delete = $pdo->prepare("DELETE FROM users WHERE id=:id AND role='client'");
            $delete->execute(['id' => $id]);
            if ($delete->rowCount() !== 1) {
                $pdo->rollBack();
                return null;
            }
            $pdo->commit();

            return [
                'avatar_path' => is_string($client['avatar_path']) ? $client['avatar_path'] : null,
                'pet_photo_paths' => array_map(static fn(array $pet): ?string => is_string($pet['photo_path']) ? $pet['photo_path'] : null, $pets),
            ];
        } catch (\Throwable $error) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            throw $error;
        }
    }

    /** Delete a pet only when it has no appointment history; return its private photo path after commit. */
    public function deleteAdminPet(string $id): ?array
    {
        $pdo = Database::connection();
        $pdo->beginTransaction();
        try {
            $pdo->query('SELECT pg_advisory_xact_lock(4242, 4)');
            $petStatement = $pdo->prepare("SELECT p.id,p.photo_path FROM pets p
                JOIN users u ON u.id=p.owner_id
                WHERE p.id=:id AND u.role='client' FOR UPDATE OF p");
            $petStatement->execute(['id' => $id]);
            $pet = $petStatement->fetch();
            if (!$pet) {
                $pdo->commit();
                return null;
            }

            $appointments = $pdo->prepare('SELECT EXISTS(SELECT 1 FROM appointments WHERE pet_id=:id)');
            $appointments->execute(['id' => $id]);
            if ((bool) $appointments->fetchColumn()) {
                throw new DomainException('Este pet possui agendamentos vinculados. O histórico precisa ser preservado, portanto ele não pode ser excluído.');
            }

            $delete = $pdo->prepare("DELETE FROM pets p USING users u
                WHERE p.id=:id AND u.id=p.owner_id AND u.role='client'");
            $delete->execute(['id' => $id]);
            if ($delete->rowCount() !== 1) {
                $pdo->rollBack();
                return null;
            }
            $pdo->commit();
            return ['photo_path' => is_string($pet['photo_path']) ? $pet['photo_path'] : null];
        } catch (\Throwable $error) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            throw $error;
        }
    }

    public function staffRows(string $section, string $staffId): array
    {
        if (in_array($section, ['agenda', 'atendimentos'], true)) {
            $rows = (new AppointmentRepository())->allForStaff($staffId, $section === 'agenda');
            return array_map(static fn(array $a): array => $a + ['schedule' => date('d/m H:i', strtotime($a['scheduled_at']))], $rows);
        }
        return [];
    }

    public function setAdminStatus(string $section, string $id, string $status): bool
    {
        $pdo = Database::connection();
        if (in_array($section, ['clientes', 'funcionarios'], true) && in_array($status, ['active', 'blocked'], true)) {
            $role = $section === 'clientes' ? 'client' : 'staff';
            $s = $pdo->prepare('UPDATE users SET status=:status,updated_at=now() WHERE id=:id AND role=:role');
            $s->execute(compact('status', 'id', 'role'));
            return $s->rowCount() === 1;
        }

        if ($section === 'servicos' && in_array($status, ['active', 'inactive'], true)) {
            $s = $pdo->prepare('UPDATE services SET active=:active,updated_at=now() WHERE id=:id');
            $s->execute(['active' => $status === 'active', 'id' => $id]);
            return $s->rowCount() === 1;
        }

        if ($section === 'agendamentos' && $status === 'cancelled') {
            return (new AppointmentRepository())->changeStatus($id, 'cancelled');
        }
        return false;
    }

    public function setStaffAppointmentStatus(string $id, string $staffId, string $status): bool
    {
        if (!in_array($status, ['pending', 'in_progress', 'completed', 'cancelled'], true))
            return false;
        return (new AppointmentRepository())->changeStatus($id, $status, $staffId);
    }
}
