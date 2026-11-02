<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Database;
use App\Models\Collaboration;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use finfo;
use RuntimeException;

final class CollaborationController
{
    private const MAX_ATTACHMENT_SIZE = 10_485_760;

    public function calendar(): void
    {
        $month = $_GET['month'] ?? date('Y-m');
        if (!is_string($month) || !preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', $month)) {
            http_response_code(400);
            exit('Choose a valid calendar month.');
        }
        $firstDay = \DateTimeImmutable::createFromFormat('!Y-m-d', $month . '-01');
        if (!$firstDay || $firstDay->format('Y-m') !== $month) {
            http_response_code(400);
            exit('Choose a valid calendar month.');
        }

        $userId = (int) $_SESSION['user_id'];
        $userModel = new User();
        $events = (new Collaboration())->calendarEvents($userId, $month);
        $calendarStart = $firstDay->modify('monday this week');
        $lastDay = $firstDay->modify('last day of this month');
        $calendarEnd = $lastDay->modify('sunday this week');
        $weeks = [];
        $cursor = $calendarStart;
        while ($cursor <= $calendarEnd) {
            $week = [];
            for ($day = 0; $day < 7; $day++) {
                $week[] = $cursor;
                $cursor = $cursor->modify('+1 day');
            }
            $weeks[] = $week;
        }
        $eventsByDate = [];
        foreach ($events as $event) {
            $eventsByDate[$event['event_date']][] = $event;
        }
        $previousMonth = $firstDay->modify('-1 month')->format('Y-m');
        $nextMonth = $firstDay->modify('+1 month')->format('Y-m');
        $canManageUsers = $userModel->hasPermission($userId, 'users.manage');
        $canManageRoles = $userModel->hasPermission($userId, 'roles.manage') && $userModel->isAdministrator($userId);
        $name = $_SESSION['user_name'] ?? 'Team member';
        $role = $_SESSION['role'] ?? 'User';
        require BASE_PATH . '/app/Views/calendar/index.php';
    }

    public function notifications(): void
    {
        $userId = (int) $_SESSION['user_id'];
        $userModel = new User();
        $collaboration = new Collaboration();
        $notifications = $collaboration->notifications($userId);
        $unreadCount = $collaboration->unreadCount($userId);
        $canManageUsers = $userModel->hasPermission($userId, 'users.manage');
        $canManageRoles = $userModel->hasPermission($userId, 'roles.manage') && $userModel->isAdministrator($userId);
        $name = $_SESSION['user_name'] ?? 'Team member';
        $role = $_SESSION['role'] ?? 'User';
        $error = $_SESSION['flash_error'] ?? null;
        $success = $_SESSION['flash_success'] ?? null;
        unset($_SESSION['flash_error'], $_SESSION['flash_success']);
        require BASE_PATH . '/app/Views/notifications/index.php';
    }

    public function comment(): void
    {
        verify_csrf();
        $userId = (int) $_SESSION['user_id'];
        $taskId = $this->positiveId($_POST['task_id'] ?? null);
        $task = (new Task())->projectTask($taskId);
        if (!$task) {
            $this->notFound();
        }
        $projectId = (int) $task['project_id'];
        $project = $this->requireProject($projectId, $userId);
        $this->requireContributor($project, $userId);
        if (in_array($project['status'], ['completed', 'archived'], true)) {
            $_SESSION['flash_error'] = 'Comments cannot be added to a closed project.';
            redirect('/projects/view?project_id=' . $projectId . '#task-' . $taskId);
        }

        $body = $_POST['body'] ?? null;
        if (!is_string($body) || trim($body) === '' || strlen(trim($body)) > 5000) {
            $_SESSION['flash_error'] = 'Comments must contain 1 to 5,000 characters.';
            redirect('/projects/view?project_id=' . $projectId . '#task-' . $taskId);
        }
        (new Collaboration())->createComment($taskId, $userId, $projectId, trim($body));
        $_SESSION['flash_success'] = 'Comment added.';
        redirect('/projects/view?project_id=' . $projectId . '#task-' . $taskId);
    }

    public function upload(): void
    {
        verify_csrf();
        $userId = (int) $_SESSION['user_id'];
        $taskId = $this->positiveId($_POST['task_id'] ?? null);
        $task = (new Task())->projectTask($taskId);
        if (!$task) {
            $this->notFound();
        }
        $projectId = (int) $task['project_id'];
        $project = $this->requireProject($projectId, $userId);
        $this->requireContributor($project, $userId);
        if (in_array($project['status'], ['completed', 'archived'], true)) {
            $_SESSION['flash_error'] = 'Attachments cannot be added to a closed project.';
            redirect('/projects/view?project_id=' . $projectId . '#task-' . $taskId);
        }

        $file = $_FILES['attachment'] ?? null;
        if (!is_array($file) || !isset($file['error'], $file['tmp_name'], $file['name'], $file['size'])) {
            $_SESSION['flash_error'] = 'Choose a file to upload.';
            redirect('/projects/view?project_id=' . $projectId . '#task-' . $taskId);
        }
        if ($file['error'] !== UPLOAD_ERR_OK || !is_uploaded_file((string) $file['tmp_name'])) {
            $_SESSION['flash_error'] = $this->uploadErrorMessage((int) $file['error']);
            redirect('/projects/view?project_id=' . $projectId . '#task-' . $taskId);
        }
        if ((int) $file['size'] < 1 || (int) $file['size'] > self::MAX_ATTACHMENT_SIZE) {
            $_SESSION['flash_error'] = 'Attachments must be smaller than 10 MB.';
            redirect('/projects/view?project_id=' . $projectId . '#task-' . $taskId);
        }

        $mime = (new finfo(FILEINFO_MIME_TYPE))->file((string) $file['tmp_name']);
        $allowedTypes = [
            'application/pdf' => 'pdf',
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/gif' => 'gif',
            'image/webp' => 'webp',
            'text/plain' => 'txt',
            'text/csv' => 'csv',
        ];
        if (!is_string($mime) || !isset($allowedTypes[$mime])) {
            $_SESSION['flash_error'] = 'Allowed attachment types are PDF, JPEG, PNG, GIF, WebP, plain text, and CSV.';
            redirect('/projects/view?project_id=' . $projectId . '#task-' . $taskId);
        }

        $originalName = basename(str_replace('\\', '/', (string) $file['name']));
        $originalName = preg_replace('/[\x00-\x1F\x7F]/', '', $originalName) ?? '';
        $originalName = substr($originalName, 0, 255);
        if ($originalName === '' || $originalName === '.' || $originalName === '..') {
            $_SESSION['flash_error'] = 'The uploaded file has an invalid name.';
            redirect('/projects/view?project_id=' . $projectId . '#task-' . $taskId);
        }

        $uploadDirectory = BASE_PATH . '/storage/uploads';
        if (!is_dir($uploadDirectory) && !mkdir($uploadDirectory, 0750, true) && !is_dir($uploadDirectory)) {
            throw new RuntimeException('The private attachment directory could not be created.');
        }
        $storedName = bin2hex(random_bytes(24)) . '.' . $allowedTypes[$mime];
        $destination = $uploadDirectory . '/' . $storedName;
        if (!move_uploaded_file((string) $file['tmp_name'], $destination)) {
            throw new RuntimeException('The uploaded file could not be saved.');
        }

        try {
            (new Collaboration())->createAttachment(
                $taskId,
                $userId,
                $projectId,
                $originalName,
                $storedName,
                $mime,
                (int) $file['size']
            );
        } catch (\Throwable $error) {
            if (is_file($destination) && !unlink($destination)) {
                error_log('Unable to remove attachment after database failure: ' . $destination);
            }
            throw $error;
        }
        $_SESSION['flash_success'] = 'Attachment uploaded.';
        redirect('/projects/view?project_id=' . $projectId . '#task-' . $taskId);
    }

    public function download(): void
    {
        $attachmentId = $this->positiveId($_GET['id'] ?? null);
        $collaboration = new Collaboration();
        $attachment = $collaboration->attachment($attachmentId);
        if (!$attachment) {
            $this->notFound();
        }
        $project = $this->requireProject((int) $attachment['project_id'], (int) $_SESSION['user_id']);
        $path = BASE_PATH . '/storage/uploads/' . basename($attachment['stored_name']);
        if (!is_file($path) || !is_readable($path)) {
            throw new RuntimeException('The requested attachment is missing or unreadable.');
        }

        $downloadName = str_replace(["\r", "\n", '"'], ['', '', ''], $attachment['original_name']);
        header('Content-Type: ' . $attachment['mime_type']);
        header('Content-Length: ' . filesize($path));
        header('Content-Disposition: attachment; filename="' . $downloadName . '"; filename*=UTF-8\'\'' . rawurlencode($downloadName));
        header('X-Content-Type-Options: nosniff');
        header('Cache-Control: private, no-store');
        readfile($path);
    }

    public function markRead(): void
    {
        verify_csrf();
        $userId = (int) $_SESSION['user_id'];
        $collaboration = new Collaboration();
        if (isset($_POST['mark_all']) && $_POST['mark_all'] === '1') {
            $collaboration->markAllNotificationsRead($userId);
            $_SESSION['flash_success'] = 'All notifications marked as read.';
        } else {
            $notificationId = $this->positiveId($_POST['notification_id'] ?? null);
            if (!$collaboration->markNotificationRead($notificationId, $userId)) {
                $_SESSION['flash_error'] = 'Notification not found or already read.';
            } else {
                $_SESSION['flash_success'] = 'Notification marked as read.';
            }
        }
        redirect('/notifications');
    }

    public function logTaskChange(int $taskId, int $projectId, int $userId, string $kind, string $detail): void
    {
        $collaboration = new Collaboration();
        switch ($kind) {
            case 'created':
                $collaboration->taskCreated($taskId, $projectId, $userId, $detail);
                return;
            case 'updated':
                $collaboration->taskUpdated($taskId, $projectId, $userId, $detail);
                return;
            case 'status':
                $collaboration->taskStatusChanged($taskId, $projectId, $userId, $detail);
                return;
            default:
                throw new RuntimeException('Unknown task activity type.');
        }
    }

    private function requireProject(int $projectId, int $userId): array
    {
        $project = (new Project())->findVisible($projectId, $userId);
        if (!$project) {
            $this->notFound();
        }
        return $project;
    }

    private function requireContributor(array $project, int $userId): void
    {
        $canContribute = (new Project())->canManage((int) $project['id'], $userId)
            || in_array($project['member_role'] ?? '', ['member', 'manager'], true);
        if (!$canContribute) {
            $this->deny();
        }
    }

    private function positiveId(mixed $value): int
    {
        if (!is_string($value) && !is_int($value)) {
            $this->notFound();
        }
        $id = filter_var($value, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        if ($id === false) {
            $this->notFound();
        }
        return (int) $id;
    }

    private function uploadErrorMessage(int $error): string
    {
        return match ($error) {
            UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE => 'The selected file exceeds the upload size limit.',
            UPLOAD_ERR_PARTIAL => 'The file upload was incomplete. Try again.',
            UPLOAD_ERR_NO_FILE => 'Choose a file to upload.',
            UPLOAD_ERR_NO_TMP_DIR, UPLOAD_ERR_CANT_WRITE, UPLOAD_ERR_EXTENSION => 'The server could not process the upload.',
            default => 'The file upload failed. Try again.',
        };
    }

    private function deny(): never
    {
        http_response_code(403);
        require BASE_PATH . '/app/Views/errors/403.php';
        exit;
    }

    private function notFound(): never
    {
        http_response_code(404);
        require BASE_PATH . '/app/Views/errors/404.php';
        exit;
    }
}
