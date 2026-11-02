<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Models\Role;
use PDOException;

final class RolesController
{
    public function index(): void
    {
        $roleModel = new Role();
        $roles = $roleModel->allWithPermissions();
        $permissions = $roleModel->permissions();
        $error = $_SESSION['flash_error'] ?? null;
        $success = $_SESSION['flash_success'] ?? null;
        unset($_SESSION['flash_error'], $_SESSION['flash_success']);

        $name = $_SESSION['user_name'] ?? 'Team member';
        $role = $_SESSION['role'] ?? 'User';
        $userModel = new \App\Models\User();
        $currentUserId = (int) $_SESSION['user_id'];
        $canManageUsers = $userModel->hasPermission($currentUserId, 'users.manage');
        require BASE_PATH . '/app/Views/roles/index.php';
    }

    public function create(): void
    {
        verify_csrf();
        $input = $this->validatedRole($_POST);
        if ($input === null) {
            redirect('/roles');
        }

        try {
            (new Role())->create($input['name'], $input['description'], $input['permission_ids']);
        } catch (PDOException $error) {
            if ($error->getCode() !== '23000' || (int) ($error->errorInfo[1] ?? 0) !== 1062) {
                throw $error;
            }
            $_SESSION['flash_error'] = 'A role with that name already exists.';
            redirect('/roles');
        }

        $_SESSION['flash_success'] = 'The role was created.';
        redirect('/roles');
    }

    public function update(): void
    {
        verify_csrf();
        $roleId = filter_var($_POST['role_id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        if ($roleId === false || $roleId === null) {
            $_SESSION['flash_error'] = 'Select a valid role.';
            redirect('/roles');
        }

        $input = $this->validatedRole($_POST);
        if ($input === null) {
            redirect('/roles');
        }

        try {
            (new Role())->update((int) $roleId, $input['name'], $input['description'], $input['permission_ids']);
        } catch (PDOException $error) {
            if ($error->getCode() !== '23000' || (int) ($error->errorInfo[1] ?? 0) !== 1062) {
                throw $error;
            }
            $_SESSION['flash_error'] = 'A role with that name already exists.';
            redirect('/roles');
        } catch (\RuntimeException $error) {
            $_SESSION['flash_error'] = $error->getMessage();
            redirect('/roles');
        }

        $_SESSION['flash_success'] = 'The role and its permissions were updated.';
        redirect('/roles');
    }

    private function validatedRole(array $input): ?array
    {
        $nameInput = $input['name'] ?? null;
        $descriptionInput = $input['description'] ?? '';
        if (!is_string($nameInput) || !is_string($descriptionInput)) {
            $_SESSION['flash_error'] = 'Submit valid text values for the role name and description.';
            return null;
        }
        $name = trim($nameInput);
        $description = trim($descriptionInput);
        $submittedIds = $input['permissions'] ?? [];
        if (!is_array($submittedIds)) {
            $_SESSION['flash_error'] = 'Submit permissions as a valid selection.';
            return null;
        }

        $roleModel = new Role();
        $permissions = $roleModel->permissions();
        $allowedIds = array_map('intval', array_column($permissions, 'id'));
        $permissionKeys = [];
        foreach ($permissions as $permission) {
            $permissionKeys[(int) $permission['id']] = $permission['permission_key'];
        }
        $permissionIds = [];
        foreach ($submittedIds as $value) {
            $permissionId = filter_var($value, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
            if ($permissionId === false || !in_array($permissionId, $allowedIds, true)) {
                $_SESSION['flash_error'] = 'Select only permissions that are available in the system.';
                return null;
            }
            if ($permissionKeys[(int) $permissionId] === 'roles.manage') {
                $_SESSION['flash_error'] = 'The roles.manage permission is reserved for the built-in Administrator role.';
                return null;
            }
            $permissionIds[] = (int) $permissionId;
        }

        if ($name === '' || strlen($name) > 50 || strlen($description) > 255) {
            $_SESSION['flash_error'] = 'Enter a role name of up to 50 characters and a description of up to 255 characters.';
            return null;
        }

        return [
            'name' => $name,
            'description' => $description === '' ? null : $description,
            'permission_ids' => array_values(array_unique($permissionIds)),
        ];
    }
}
