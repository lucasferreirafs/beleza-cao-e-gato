<?php
declare(strict_types=1);
namespace App\Repositories;

use App\Core\Database;
use App\Validation\Validator;
use InvalidArgumentException;

final class ManagementRepository
{
    public function formOptions(): array
    {
        $pdo = Database::connection();
        return [
            'clients' => $pdo->query("SELECT id,name FROM users WHERE role='client' AND status='active' ORDER BY name")->fetchAll(),
        ];
    }

    public function findEditable(string $section, int $id): ?array
    {
        $definition = match ($section) {
            'clientes' => ["SELECT id,name,email,phone,cpf,birth_date,status FROM users WHERE id=:id AND role='client'", null],
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

    public function save(string $section, ?int $id, array $input): int
    {
        $pdo = Database::connection();
        if (in_array($section, ['clientes', 'funcionarios'], true)) {
            $role = $section === 'clientes' ? 'client' : 'staff';
            $data = [
                'name' => Validator::text($input['name'] ?? '', 'Nome'),
                'email' => Validator::email($input['email'] ?? ''),
                'phone' => Validator::phone($input['phone'] ?? ''),
                'cpf' => Validator::cpf($input['cpf'] ?? ''),
                'birth_date' => Validator::date($input['birth_date'] ?? '')
            ];
            $users = new UserRepository();

            if ($users->emailExists($data['email'], $id))
                throw new InvalidArgumentException('Já existe uma conta com este e-mail.');

            if ($users->cpfExists($data['cpf'], $id))
                throw new InvalidArgumentException('Já existe uma conta com este CPF.');

            $password = (string) ($input['password'] ?? '');
            if (!$id || $password !== '')
                $password = Validator::password($password);

            if ($id) {
                $sql = 'UPDATE users SET name=:name,email=:email,phone=:phone,cpf=:cpf,birth_date=:birth_date,updated_at=now()' . ($password !== '' ? ',password_hash=:password_hash' : '') . ' WHERE id=:id AND role=:role';
                $params = $data + ['id' => $id, 'role' => $role];

                if ($password !== '')
                    $params['password_hash'] = password_hash($password, PASSWORD_DEFAULT);

                $pdo->prepare($sql)->execute($params);
                return $id;
            }

            $s = $pdo->prepare("
                INSERT INTO users(name,email,password_hash,role,status,phone,cpf,birth_date,email_verified_at) 
                VALUES (:name,:email,:password_hash,:role,'active',:phone,:cpf,:birth_date,now()) 
                RETURNING id
            ");

            $s->execute($data + ['password_hash' => password_hash($password, PASSWORD_DEFAULT), 'role' => $role]);
            return (int) $s->fetchColumn();
        }

        if ($section === 'servicos') {
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
            return (int) $s->fetchColumn();
        }

        if ($section === 'pets') {
            $owner = filter_var($input['owner_id'] ?? null, FILTER_VALIDATE_INT);
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

            if ($id) {
                $current = $this->findEditable('pets', $id);
                if (!$current)
                    throw new InvalidArgumentException('Pet não encontrado.');

                (new PetRepository())->update($id, (int) $current['owner_id'], $data);

                if ((int) $current['owner_id'] !== (int) $owner)
                    $pdo->prepare('UPDATE pets SET owner_id=:owner WHERE id=:id')->execute(['owner' => $owner, 'id' => $id]);

                return $id;
            }
            return (new PetRepository())->create((int) $owner, $data);
        }
        throw new InvalidArgumentException('Tipo de cadastro inválido.');
    }

    public function adminRows(string $section): array
    {
        $sql = match ($section) {
            'clientes' => "SELECT u.id,u.name label,concat(u.email,' • ',coalesce(u.phone,'Sem telefone')) detail,concat(count(DISTINCT p.id),' pets') links,u.status FROM users u LEFT JOIN pets p ON p.owner_id=u.id WHERE u.role='client' GROUP BY u.id ORDER BY u.name",
            'pets' => "SELECT p.id,p.name label,concat(p.species,' • ',p.breed) detail,u.name links,'active' status FROM pets p JOIN users u ON u.id=p.owner_id ORDER BY p.name",
            'funcionarios' => "SELECT u.id,u.name label,concat(u.email,' • ',coalesce(u.phone,'Sem telefone')) detail,'Equipe da unidade' links,u.status FROM users u WHERE u.role='staff' ORDER BY u.name",
            'servicos' => "SELECT s.id,s.name label,concat(s.duration_minutes,' min • R$ ',to_char(s.price,'FM999G999D00')) detail,concat(count(a.id),' agendamentos') links,CASE WHEN s.active THEN 'active' ELSE 'inactive' END status FROM services s LEFT JOIN appointments a ON a.service_id=s.id GROUP BY s.id ORDER BY s.name",
            'agendamentos' => "SELECT a.id,concat(c.name,' / ',p.name) label,concat(to_char(a.scheduled_at,'DD/MM/YYYY HH24:MI'),' • ',s.name) detail,coalesce(st.name,'Sem profissional') links,a.status FROM appointments a JOIN pets p ON p.id=a.pet_id JOIN users c ON c.id=p.owner_id JOIN services s ON s.id=a.service_id LEFT JOIN users st ON st.id=a.staff_id ORDER BY a.scheduled_at DESC",
            default => null,
        };
        return $sql ? Database::connection()->query($sql)->fetchAll() : [];
    }

    public function staffRows(string $section, int $staffId): array
    {
        $pdo = Database::connection();
        if (in_array($section, ['agenda', 'atendimentos', 'historico'], true)) {
            $condition = $section === 'agenda' ? 'AND a.scheduled_at::date=current_date' : ($section === 'historico' ? "AND a.scheduled_at<now()" : '');
            $s = $pdo->prepare("
                SELECT a.id,to_char(a.scheduled_at,'DD/MM HH24:MI') schedule,p.name pet_name,c.name client_name,sv.name service_name,a.status 
                FROM appointments a 
                JOIN pets p 
                ON p.id=a.pet_id 
                JOIN users c 
                ON c.id=p.owner_id 
                JOIN services sv 
                ON sv.id=a.service_id 
                WHERE a.staff_id=:staff {$condition} 
                ORDER BY a.scheduled_at DESC
            ");

            $s->execute(['staff' => $staffId]);
            return $s->fetchAll();
        }
        if ($section === 'clientes') {
            $s = $pdo->prepare('
                SELECT DISTINCT c.id,c.name client_name,c.email schedule,p.name pet_name,\'Cliente atendido\' service_name,c.status 
                FROM appointments a 
                JOIN pets p 
                ON p.id=a.pet_id 
                JOIN users c 
                ON c.id=p.owner_id 
                WHERE a.staff_id=:staff 
                ORDER BY c.name
            ');

            $s->execute(['staff' => $staffId]);
            return $s->fetchAll();
        }

        if ($section === 'pets') {
            $s = $pdo->prepare('
                SELECT DISTINCT p.id,p.name pet_name,c.name client_name,p.breed service_name,p.species schedule,\'active\' status 
                FROM appointments a 
                JOIN pets p ON p.id=a.pet_id 
                JOIN users c 
                ON c.id=p.owner_id 
                WHERE a.staff_id=:staff 
                ORDER BY p.name
            ');
            $s->execute(['staff' => $staffId]);
            return $s->fetchAll();
        }
        return [];
    }

    public function setAdminStatus(string $section, int $id, string $status): bool
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

        if ($section === 'agendamentos' && in_array($status, ['pending', 'confirmed', 'in_progress', 'completed', 'cancelled'], true)) {
            $s = $pdo->prepare('
                UPDATE appointments 
                SET status=:status,
                completed_at=CASE WHEN CAST(:is_completed AS boolean) 
                THEN now() 
                ELSE completed_at 
                END,updated_at=now() 
                WHERE id=:id
            ');

            $s->execute(['status' => $status, 'is_completed' => $status === 'completed', 'id' => $id]);
            return $s->rowCount() === 1;
        }
        return false;
    }

    public function setStaffAppointmentStatus(int $id, int $staffId, string $status): bool
    {
        if (!in_array($status, ['confirmed', 'in_progress', 'completed', 'cancelled'], true))
            return false;
        $s = Database::connection()->prepare('
            UPDATE appointments 
            SET status=:status,completed_at=CASE 
            WHEN CAST(:is_completed AS boolean) 
            THEN now() 
            ELSE completed_at 
            END,updated_at=now() 
            WHERE id=:id 
            AND staff_id=:staff
        ');

        $s->execute(['status' => $status, 'is_completed' => $status === 'completed', 'id' => $id, 'staff' => $staffId]);
        return $s->rowCount() === 1;
    }
}
