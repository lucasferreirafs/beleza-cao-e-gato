<?php
declare(strict_types=1);
namespace App\Controllers;

use App\Core\{
    Request,
    Session,
    View,
    Response
};
use App\Repositories\ManagementRepository;
use InvalidArgumentException;

final class AdminController
{
    public function page(Request $r): void
    {
        $section = $r->param('section') ?? 'clientes';
        $allowed = ['clientes', 'pets', 'funcionarios', 'servicos', 'agendamentos', 'relatorios', 'configuracoes'];

        if (!in_array($section, $allowed, true)) {
            $section = 'clientes';
        }

        $rows = (new ManagementRepository())->adminRows($section);
        View::render(
            'pages/admin/list',
            [
                'title' => ucfirst($section),
                'active' => $section,
                'section' => $section,
                'rows' => $rows,
                'user' => Session::user()
            ]
        );
    }

    public function status(Request $r): void
    {
        $section = (string) $r->param('section');
        $ok = (new ManagementRepository())->setAdminStatus(
            $section,
            (int) $r->param('id'),
            (string) $r->input('status')
        );

        Session::flash($ok ? 'success' : 'error', $ok ? 'Status atualizado.' : 'Alteração de status inválida.');
        Response::redirect('/admin/' . rawurlencode($section));
    }

    public function form(Request $r): void
    {
        $section = (string) $r->param('section');
        if (!in_array($section, ['clientes', 'pets', 'funcionarios', 'servicos'], true))
            Response::redirect('/admin/' . rawurlencode($section));

        $id = $r->param('id') !== null ? (int) $r->param('id') : null;
        $repository = new ManagementRepository();
        $record = $id ? $repository->findEditable($section, $id) : null;

        if ($id && !$record) {
            Session::flash('error', 'Registro não encontrado.');
            Response::redirect('/admin/' . rawurlencode($section));
        }

        View::render(
            'pages/admin/form',
            [
                'title' => ($id ? 'Editar ' : 'Novo ') . ucfirst($section),
                'active' => $section,
                'section' => $section,
                'record' => $record,
                'options' => $repository->formOptions(),
                'user' => Session::user()
            ]
        );
    }

    public function save(Request $r): void
    {
        $section = (string) $r->param('section');
        $id = $r->param('id') !== null ? (int) $r->param('id') : null;

        try {
            (new ManagementRepository())->save($section, $id, $r->all());
            Session::flash('success', $id ? 'Registro atualizado.' : 'Registro criado.');
            Response::redirect('/admin/' . rawurlencode($section));
        } catch (InvalidArgumentException $e) {
            Session::flash('error', $e->getMessage());
            Response::redirect('/admin/' . rawurlencode($section) . ($id ? '/' . $id . '/editar' : '/novo'));
        }
    }
}
