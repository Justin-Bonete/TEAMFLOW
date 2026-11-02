<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

final class Collaboration
{
    public function log(
        int $actorId,
        int $projectId,
        string $entityType,
        int $entityId,
        string $action,
        array $details = []
    ): void {
        $stmt = Database::connection()->prepare(
            'INSERT INTO activity_logs
                (actor_id, project_id, entity_type, entity_id, action, details, ip_address, user_agent)
             VALUES (:actor_id, :project_id, :entity_type, :entity_id, :action, :details, :ip_address, :user_agent)'
        );
        $detailsJson = json_encode($details, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $stmt->execute([
            'actor_id' => $actorId,
            'project_id' => $projectId,
            'entity_type' => $entityType,
            'entity_id' => $entityId,
            'action' => $action,
            'details' => $detailsJson,
            'ip_address' => filter_var($_SERVER['REMOTE_ADDR'] ?? null, FILTER_VALIDATE_IP) ?: null,
            'user_agent' => isset($_SERVER['HTTP_USER_AGENT']) ? substr((string) $_SERVER['HTTP_USER_AGENT'], 0, 255) : null,
        ]);
    }

    public function createComment(int $taskId, int $userId, int $projectId, string $body): int
    {
        $connection = Database::connection();
        $connection->beginTransaction();
        try {
            $stmt = $connection->prepare(
                'INSERT INTO task_comments (task_id, user_id, body) VALUES (:task_id, :user_id, :body)'
            );
            $stmt->execute(['task_id' => $taskId, 'user_id' => $userId, 'body' => $body]);
            $commentId = (int) $connection->lastInsertId();
            $this->log($userId, $projectId, 'comment', $commentId, 'comment.created', ['task_id' => $taskId]);
            $this->notifyProjectMembers(
                $projectId,
                $userId,
                'task.comment',
                'New task comment',
                'A teammate commented on a task.',
                'task',
                $taskId
            );
            $connection->commit();
            return $commentId;
        } catch (\Throwable $error) {
            if ($connection->inTransaction()) {
                $connection->rollBack();
            }
            throw $error;
        }
    }

    public function commentsForProject(int $projectId): array
    {
        $stmt = Database::connection()->prepare(
            'SELECT c.id, c.task_id, c.body, c.created_at, u.first_name, u.last_name
             FROM task_comments c INNER JOIN users u ON u.id = c.user_id
             INNER JOIN tasks t ON t.id = c.task_id
             WHERE t.project_id = :project_id
             ORDER BY c.created_at, c.id'
        );
        $stmt->execute(['project_id' => $projectId]);
        $comments = [];
        foreach ($stmt->fetchAll() as $comment) {
            $comments[(int) $comment['task_id']][] = $comment;
        }
        return $comments;
    }

    public function createAttachment(
        int $taskId,
        int $userId,
        int $projectId,
        string $originalName,
        string $storedName,
        string $mimeType,
        int $fileSize
    ): int {
        $connection = Database::connection();
        $connection->beginTransaction();
        try {
            $stmt = $connection->prepare(
                'INSERT INTO task_attachments
                    (task_id, uploaded_by, original_name, stored_name, mime_type, file_size)
                 VALUES (:task_id, :uploaded_by, :original_name, :stored_name, :mime_type, :file_size)'
            );
            $stmt->execute([
                'task_id' => $taskId,
                'uploaded_by' => $userId,
                'original_name' => $originalName,
                'stored_name' => $storedName,
                'mime_type' => $mimeType,
                'file_size' => $fileSize,
            ]);
            $attachmentId = (int) $connection->lastInsertId();
            $this->log($userId, $projectId, 'attachment', $attachmentId, 'attachment.uploaded', ['task_id' => $taskId]);
            $this->notifyProjectMembers(
                $projectId,
                $userId,
                'task.attachment',
                'Task attachment added',
                'A teammate added an attachment to a task.',
                'task',
                $taskId
            );
            $connection->commit();
            return $attachmentId;
        } catch (\Throwable $error) {
            if ($connection->inTransaction()) {
                $connection->rollBack();
            }
            throw $error;
        }
    }

    public function attachmentsForProject(int $projectId): array
    {
        $stmt = Database::connection()->prepare(
            'SELECT a.*, t.title AS task_title, CONCAT(u.first_name, \' \', u.last_name) AS uploader_name
             FROM task_attachments a
             INNER JOIN tasks t ON t.id = a.task_id
             INNER JOIN users u ON u.id = a.uploaded_by
             WHERE t.project_id = :project_id
             ORDER BY a.created_at, a.id'
        );
        $stmt->execute(['project_id' => $projectId]);
        $attachments = [];
        foreach ($stmt->fetchAll() as $attachment) {
            $attachments[(int) $attachment['task_id']][] = $attachment;
        }
        return $attachments;
    }

    public function attachment(int $attachmentId): ?array
    {
        $stmt = Database::connection()->prepare(
            'SELECT a.*, t.project_id FROM task_attachments a
             INNER JOIN tasks t ON t.id = a.task_id WHERE a.id = :id LIMIT 1'
        );
        $stmt->execute(['id' => $attachmentId]);
        return $stmt->fetch() ?: null;
    }

    public function activities(int $projectId, int $limit = 60): array
    {
        $limit = max(1, min($limit, 100));
        $stmt = Database::connection()->prepare(
            'SELECT l.*, CONCAT(u.first_name, \' \', u.last_name) AS actor_name
             FROM activity_logs l LEFT JOIN users u ON u.id = l.actor_id
             WHERE l.project_id = :project_id
             ORDER BY l.created_at DESC, l.id DESC LIMIT ' . $limit
        );
        $stmt->execute(['project_id' => $projectId]);
        return $stmt->fetchAll();
    }

    public function calendarEvents(int $userId, string $month): array
    {
        $userModel = new User();
        $admin = $userModel->isAdministrator($userId);
        $projectFilter = $admin ? '' : ' AND EXISTS (
            SELECT 1 FROM project_members pm WHERE pm.project_id = p.id AND pm.user_id = :project_user_id
        )';
        $taskFilter = $admin ? '' : ' AND EXISTS (
            SELECT 1 FROM project_members pm WHERE pm.project_id = t.project_id AND pm.user_id = :task_user_id
        )';
        $sql = 'SELECT p.id AS project_id, NULL AS task_id, p.name AS title, p.due_date AS event_date,
                    \'project\' AS event_type, p.project_key, p.status
                FROM projects p
                WHERE p.due_date >= :project_start AND p.due_date < :project_end' . $projectFilter . '
                UNION ALL
                SELECT t.project_id, t.id, t.title, t.due_date, \'task\', p.project_key, t.status
                FROM tasks t INNER JOIN projects p ON p.id = t.project_id
                WHERE t.due_date >= :task_start AND t.due_date < :task_end' . $taskFilter . '
                ORDER BY event_date, title';
        $stmt = Database::connection()->prepare($sql);
        $start = $month . '-01';
        $end = (new \DateTimeImmutable($start))->modify('+1 month')->format('Y-m-d');
        $params = [
            'project_start' => $start,
            'project_end' => $end,
            'task_start' => $start,
            'task_end' => $end,
        ];
        if (!$admin) {
            $params['project_user_id'] = $userId;
            $params['task_user_id'] = $userId;
        }
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function notifications(int $userId, int $limit = 100): array
    {
        $limit = max(1, min($limit, 100));
        $stmt = Database::connection()->prepare(
            'SELECT n.*, CASE
                WHEN n.reference_type = \'project\' THEN n.reference_id
                WHEN n.reference_type = \'task\' THEN t.project_id
                ELSE NULL END AS project_id
             FROM notifications n
             LEFT JOIN tasks t ON n.reference_type = \'task\' AND t.id = n.reference_id
             WHERE n.user_id = :user_id
             ORDER BY n.created_at DESC, n.id DESC LIMIT ' . $limit
        );
        $stmt->execute(['user_id' => $userId]);
        return $stmt->fetchAll();
    }

    public function unreadCount(int $userId): int
    {
        $stmt = Database::connection()->prepare(
            'SELECT COUNT(*) FROM notifications WHERE user_id = :user_id AND read_at IS NULL'
        );
        $stmt->execute(['user_id' => $userId]);
        return (int) $stmt->fetchColumn();
    }

    public function markNotificationRead(int $notificationId, int $userId): bool
    {
        $stmt = Database::connection()->prepare(
            'UPDATE notifications SET read_at = CURRENT_TIMESTAMP
             WHERE id = :id AND user_id = :user_id AND read_at IS NULL'
        );
        $stmt->execute(['id' => $notificationId, 'user_id' => $userId]);
        return $stmt->rowCount() > 0;
    }

    public function markAllNotificationsRead(int $userId): void
    {
        $stmt = Database::connection()->prepare(
            'UPDATE notifications SET read_at = CURRENT_TIMESTAMP
             WHERE user_id = :user_id AND read_at IS NULL'
        );
        $stmt->execute(['user_id' => $userId]);
    }

    public function taskStatusChanged(int $taskId, int $projectId, int $actorId, string $status): void
    {
        $this->log($actorId, $projectId, 'task', $taskId, 'task.status_changed', ['status' => $status]);
        $this->notifyTaskAssignees($taskId, $actorId, 'Task status updated', 'A task assigned to you has changed status.', 'task', $taskId);
    }

    public function taskCreated(int $taskId, int $projectId, int $actorId, string $title): void
    {
        $this->log($actorId, $projectId, 'task', $taskId, 'task.created', ['title' => $title]);
        $this->notifyTaskAssignees($taskId, $actorId, 'New task assigned', 'You were assigned a task: ' . $title, 'task', $taskId);
    }

    public function taskUpdated(int $taskId, int $projectId, int $actorId, string $title): void
    {
        $this->log($actorId, $projectId, 'task', $taskId, 'task.updated', ['title' => $title]);
        $this->notifyProjectMembers(
            $projectId,
            $actorId,
            'task.updated',
            'Task details updated',
            'A task in your project was updated: ' . $title,
            'task',
            $taskId
        );
    }

    public function projectUpdated(int $projectId, int $actorId, string $action, array $details): void
    {
        $this->log($actorId, $projectId, 'project', $projectId, $action, $details);
        $this->notifyProjectMembers(
            $projectId,
            $actorId,
            'project.updated',
            'Project updated',
            'A project you belong to was updated.',
            'project',
            $projectId
        );
    }

    public function checklistChanged(int $taskId, int $projectId, int $actorId, string $action, string $title): void
    {
        $this->log($actorId, $projectId, 'task', $taskId, $action, ['checklist_item' => $title]);
    }

    public function activityForTask(int $taskId, int $projectId, int $actorId, string $action, array $details = []): void
    {
        $this->log($actorId, $projectId, 'task', $taskId, $action, $details);
    }

    private function notifyProjectMembers(
        int $projectId,
        int $actorId,
        string $type,
        string $title,
        string $message,
        string $referenceType,
        int $referenceId
    ): void {
        $stmt = Database::connection()->prepare(
            'SELECT user_id FROM project_members WHERE project_id = :project_id AND user_id <> :actor_id'
        );
        $stmt->execute(['project_id' => $projectId, 'actor_id' => $actorId]);
        $this->insertNotifications(array_map('intval', array_column($stmt->fetchAll(), 'user_id')), $type, $title, $message, $referenceType, $referenceId);
    }

    private function notifyTaskAssignees(
        int $taskId,
        int $actorId,
        string $title,
        string $message,
        string $referenceType,
        int $referenceId
    ): void {
        $stmt = Database::connection()->prepare(
            'SELECT user_id FROM task_assignments WHERE task_id = :task_id AND user_id <> :actor_id'
        );
        $stmt->execute(['task_id' => $taskId, 'actor_id' => $actorId]);
        $this->insertNotifications(array_map('intval', array_column($stmt->fetchAll(), 'user_id')), 'task.updated', $title, $message, $referenceType, $referenceId);
    }

    private function insertNotifications(
        array $userIds,
        string $type,
        string $title,
        string $message,
        string $referenceType,
        int $referenceId
    ): void {
        if (!$userIds) {
            return;
        }
        $stmt = Database::connection()->prepare(
            'INSERT INTO notifications (user_id, type, title, message, reference_type, reference_id)
             VALUES (:user_id, :type, :title, :message, :reference_type, :reference_id)'
        );
        foreach (array_unique($userIds) as $userId) {
            $stmt->execute([
                'user_id' => $userId,
                'type' => $type,
                'title' => $title,
                'message' => $message,
                'reference_type' => $referenceType,
                'reference_id' => $referenceId,
            ]);
        }
    }
}
