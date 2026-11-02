<section class="task-collaboration" aria-label="Task collaboration">
    <?php $taskComments = $comments[(int) $collaborationTaskId] ?? []; ?>
    <?php $taskAttachments = $attachments[(int) $collaborationTaskId] ?? []; ?>
    <details class="collaboration-details">
        <summary>Conversation and files <span><?= count($taskComments) ?> comments · <?= count($taskAttachments) ?> files</span></summary>
        <?php if ($taskComments): ?>
            <div class="comment-list">
                <?php foreach ($taskComments as $comment): ?>
                    <article class="comment-item">
                        <div class="comment-heading"><strong><?= e($comment['first_name'] . ' ' . $comment['last_name']) ?></strong><time datetime="<?= e($comment['created_at']) ?>"><?= e(date('M j, Y · g:i a', strtotime($comment['created_at']))) ?></time></div>
                        <p><?= nl2br(e($comment['body'])) ?></p>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <p class="collaboration-empty">No comments yet.</p>
        <?php endif; ?>
        <?php if ($taskAttachments): ?>
            <ul class="attachment-list">
                <?php foreach ($taskAttachments as $attachment): ?>
                    <li>
                        <a href="<?= e(rtrim($_ENV['APP_URL'] ?? '', '/')) ?>/attachments/download?id=<?= (int) $attachment['id'] ?>"><?= e($attachment['original_name']) ?></a>
                        <span><?= e($attachment['uploader_name']) ?> · <?= e(number_format((int) $attachment['file_size'] / 1024, 1)) ?> KB</span>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
        <?php if ($canCollaborate && $projectOpen): ?>
            <form method="post" action="<?= e(rtrim($_ENV['APP_URL'] ?? '', '/')) ?>/comments/create" class="comment-form">
                <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
                <input type="hidden" name="task_id" value="<?= (int) $collaborationTaskId ?>">
                <label class="sr-only" for="comment-<?= (int) $collaborationTaskId ?>">Add a comment</label>
                <textarea id="comment-<?= (int) $collaborationTaskId ?>" name="body" rows="2" maxlength="5000" placeholder="Write a comment…" required></textarea>
                <button class="button-secondary" type="submit">Comment</button>
            </form>
            <form method="post" action="<?= e(rtrim($_ENV['APP_URL'] ?? '', '/')) ?>/attachments/upload" class="attachment-form" enctype="multipart/form-data">
                <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
                <input type="hidden" name="task_id" value="<?= (int) $collaborationTaskId ?>">
                <label for="attachment-<?= (int) $collaborationTaskId ?>">Add file <small>PDF, images, text or CSV · max 10 MB</small></label>
                <input id="attachment-<?= (int) $collaborationTaskId ?>" type="file" name="attachment" accept=".pdf,.jpg,.jpeg,.png,.gif,.webp,.txt,.csv" required>
                <button class="button-secondary" type="submit">Upload</button>
            </form>
        <?php endif; ?>
    </details>
</section>
