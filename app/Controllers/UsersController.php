<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Models\User;
use PDOException;

final class UsersController
{
    private const STATUSES = ['pending', 'active', 'suspended', 'archived'];

    public function index(): void
    {
        $userModel = new User();
        $users = $userModel->allWithRoles();
        $roles = $userModel->roles();
        $error = $_SESSION['flash_error'] ?? null;
        $success = $_SESSION['flash_success'] ?? null;
        unset($_SESSION['flash_error'], $_SESSION['flash_success']);

        $name = $_SESSION['user_name'] ?? 'Team member';
        $role = $_SESSION['role'] ?? 'User';
        $actorId = (int) $_SESSION['user_id'];
        $canManageRoles = $userModel->hasPermission($actorId, 'roles.manage') && $userModel->isAdministrator($actorId);
        $canManageAdministrators = $userModel->isAdministrator($actorId);
        require BASE_PATH . '/app/Views/users/index.php';
    }

    public function create(): void
    {
        verify_csrf();

        $userModel = new User();
        $account = $this->validatedAccount($_POST, $userModel->roles(), true);
        if ($account === null) {
            redirect('/users');
        }
        if ($account['role_name'] === 'Administrator' && !$userModel->isAdministrator((int) $_SESSION['user_id'])) {
            $_SESSION['flash_error'] = 'Only an active Administrator can create Administrator accounts.';
            redirect('/users');
        }
        if ($userModel->emailExists($account['email'])) {
            $_SESSION['flash_error'] = 'An account with that email address already exists.';
            redirect('/users');
        }

        try {
            $userModel->create([
                'role_id' => $account['role_id'],
                'first_name' => $account['first_name'],
                'last_name' => $account['last_name'],
                'email' => $account['email'],
                'password_hash' => password_hash($account['password'], PASSWORD_DEFAULT),
                'status' => $account['status'],
            ]);
        } catch (PDOException $error) {
            if ($error->getCode() !== '23000' || (int) ($error->errorInfo[1] ?? 0) !== 1062) {
                throw $error;
            }
            $_SESSION['flash_error'] = 'An account with that email address already exists.';
            redirect('/users');
        }

        $_SESSION['flash_success'] = 'The user account was created.';
        redirect('/users');
    }

    public function update(): void
    {
        verify_csrf();

        $userId = filter_var($_POST['user_id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        if ($userId === false || $userId === null) {
            $_SESSION['flash_error'] = 'Select a valid user account.';
            redirect('/users');
        }

        $userModel = new User();
        $account = $this->validatedAccount($_POST, $userModel->roles(), false);
        if ($account === null) {
            redirect('/users');
        }
        if ($userModel->emailExists($account['email'], (int) $userId)) {
            $_SESSION['flash_error'] = 'An account with that email address already exists.';
            redirect('/users');
        }

        $data = [
            'role_id' => $account['role_id'],
            'role_name' => $account['role_name'],
            'first_name' => $account['first_name'],
            'last_name' => $account['last_name'],
            'email' => $account['email'],
            'status' => $account['status'],
        ];
        if ($account['password'] !== '') {
            $data['password_hash'] = password_hash($account['password'], PASSWORD_DEFAULT);
        }

        $actorIsAdministrator = $userModel->isAdministrator((int) $_SESSION['user_id']);
        if (
            !$actorIsAdministrator
            && $userModel->hasRole((int) $userId, 'Administrator')
        ) {
            $_SESSION['flash_error'] = 'Only an active Administrator can manage Administrator accounts.';
            redirect('/users');
        }

        try {
            $userModel->updateManagedAccount((int) $userId, $data, $actorIsAdministrator);
        } catch (PDOException $error) {
            if ($error->getCode() !== '23000' || (int) ($error->errorInfo[1] ?? 0) !== 1062) {
                throw $error;
            }
            $_SESSION['flash_error'] = 'An account with that email address already exists.';
            redirect('/users');
        } catch (\RuntimeException $error) {
            $_SESSION['flash_error'] = $error->getMessage();
            redirect('/users');
        }

        $isCurrentUser = (int) $_SESSION['user_id'] === (int) $userId;
        if ($isCurrentUser) {
            $_SESSION['user_name'] = $account['first_name'] . ' ' . $account['last_name'];
            $_SESSION['role'] = $account['role_name'];
        }

        if ($isCurrentUser && $account['status'] !== 'active') {
            unset($_SESSION['user_id'], $_SESSION['role'], $_SESSION['user_name']);
            $_SESSION['flash_error'] = 'Your account is now inactive. Contact an administrator for access.';
            redirect('/login');
        }

        $_SESSION['flash_success'] = 'The user account was updated.';
        redirect($isCurrentUser && $account['role_name'] !== 'Administrator' ? '/dashboard' : '/users');
    }

    private function validatedAccount(array $input, array $roles, bool $requirePassword): ?array
    {
        $firstNameInput = $input['first_name'] ?? null;
        $lastNameInput = $input['last_name'] ?? null;
        $emailInput = $input['email'] ?? null;
        $passwordInput = $input['password'] ?? '';
        $statusInput = $input['status'] ?? null;
        if (
            !is_string($firstNameInput) || !is_string($lastNameInput)
            || !is_string($emailInput) || !is_string($passwordInput)
            || !is_string($statusInput)
        ) {
            $_SESSION['flash_error'] = 'Submit valid text values for all account fields.';
            return null;
        }

        $firstName = trim($firstNameInput);
        $lastName = trim($lastNameInput);
        $email = trim($emailInput);
        $password = $passwordInput;
        $roleId = filter_var($input['role_id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        $status = $statusInput;

        $selectedRole = null;
        foreach ($roles as $role) {
            if ((int) $role['id'] === $roleId) {
                $selectedRole = $role;
                break;
            }
        }

        if (
            $firstName === '' || strlen($firstName) > 80
            || $lastName === '' || strlen($lastName) > 80
            || !filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 190
            || !$selectedRole || !in_array($status, self::STATUSES, true)
            || ($requirePassword && strlen($password) < 12)
            || (!$requirePassword && $password !== '' && strlen($password) < 12)
        ) {
            $_SESSION['flash_error'] = $requirePassword
                ? 'Complete all fields, choose a valid role and status, and use a password of at least 12 characters.'
                : 'Complete all fields, choose a valid role and status, and use at least 12 characters for a password reset.';
            return null;
        }

        return [
            'role_id' => (int) $selectedRole['id'],
            'role_name' => $selectedRole['name'],
            'first_name' => $firstName,
            'last_name' => $lastName,
            'email' => $email,
            'status' => $status,
            'password' => $password,
        ];
    }
}
