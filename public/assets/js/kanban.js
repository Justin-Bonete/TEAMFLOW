(() => {
    const board = document.querySelector('[data-kanban-board]');
    if (!board) return;

    let draggedCard = null;
    board.querySelectorAll('[data-kanban-card][draggable="true"]').forEach((card) => {
        card.addEventListener('dragstart', (event) => {
            draggedCard = card;
            card.classList.add('kanban-card-dragging');
            event.dataTransfer.effectAllowed = 'move';
            event.dataTransfer.setData('text/plain', card.dataset.kanbanCard || '');
        });
        card.addEventListener('dragend', () => {
            card.classList.remove('kanban-card-dragging');
            draggedCard = null;
        });
    });

    board.querySelectorAll('[data-kanban-column]').forEach((column) => {
        column.addEventListener('dragover', (event) => {
            if (!draggedCard) return;
            event.preventDefault();
            event.dataTransfer.dropEffect = 'move';
            column.classList.add('kanban-column-dragover');
        });
        column.addEventListener('dragleave', (event) => {
            if (!column.contains(event.relatedTarget)) {
                column.classList.remove('kanban-column-dragover');
            }
        });
        column.addEventListener('drop', (event) => {
            event.preventDefault();
            column.classList.remove('kanban-column-dragover');
            if (!draggedCard) return;

            const form = draggedCard.querySelector('[data-kanban-status-form]');
            const status = column.dataset.kanbanColumn;
            const select = form?.querySelector('select[name="status"]');
            if (!form || !select || !status) return;

            select.value = status;
            if (select.value !== status) return;
            if (typeof form.requestSubmit === 'function') {
                form.requestSubmit();
            } else {
                form.submit();
            }
        });
    });
})();
