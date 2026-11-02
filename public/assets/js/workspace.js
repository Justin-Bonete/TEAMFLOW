(() => {
    document.querySelectorAll('.avatar-photo').forEach((image) => {
        image.addEventListener('error', () => image.remove(), { once: true });
    });

    const settingsDialog = document.getElementById('profile-settings-dialog');
    const settingsContent = settingsDialog?.querySelector('[data-profile-settings-content]');
    if (settingsDialog && settingsContent) {
        const settingsToggles = [...document.querySelectorAll('[data-profile-settings-toggle]')];
        const settingsClose = settingsDialog.querySelector('[data-profile-settings-close]');
        const settingsUrl = `${document.querySelector('.app-brand')?.getAttribute('href')?.replace(/\/dashboard$/, '') ?? ''}/settings`;
        let previousFocus = null;
        let loadId = 0;

        const setSettingsMessage = (message, isError = false) => {
            const paragraph = document.createElement('p');
            paragraph.className = `quick-panel-message${isError ? ' is-error' : ''}`;
            paragraph.textContent = message;
            settingsContent.replaceChildren(paragraph);
            if (isError) {
                const retry = document.createElement('button');
                retry.className = 'button-secondary settings-retry';
                retry.type = 'button';
                retry.textContent = 'Try again';
                retry.addEventListener('click', loadSettings);
                settingsContent.append(retry);
            }
        };

        async function loadSettings() {
            const requestId = ++loadId;
            setSettingsMessage('Loading your settings…');
            try {
                const response = await fetch(`${settingsUrl}?fragment=1`, {
                    credentials: 'same-origin',
                    cache: 'no-store',
                    headers: { Accept: 'text/html' },
                });
                if (!response.ok) throw new Error('Your settings could not be loaded.');
                const contentType = response.headers.get('content-type') ?? '';
                if (!contentType.includes('text/html')) throw new Error('The settings response was unexpected.');
                const markup = await response.text();
                if (requestId === loadId) settingsContent.innerHTML = markup;
            } catch {
                if (requestId === loadId) setSettingsMessage('Your settings could not be loaded. Please try again.', true);
            }
        }

        const openSettings = () => {
            previousFocus = document.activeElement;
            settingsDialog.hidden = false;
            document.body.classList.add('profile-settings-open');
            settingsToggles.forEach((toggle) => toggle.setAttribute('aria-expanded', 'true'));
            settingsClose?.focus();
            loadSettings();
        };

        const closeSettings = (restoreFocus = true) => {
            settingsDialog.hidden = true;
            document.body.classList.remove('profile-settings-open');
            settingsToggles.forEach((toggle) => toggle.setAttribute('aria-expanded', 'false'));
            loadId += 1;
            if (restoreFocus && previousFocus instanceof HTMLElement) previousFocus.focus();
        };

        settingsToggles.forEach((toggle) => toggle.addEventListener('click', openSettings));
        settingsClose?.addEventListener('click', () => closeSettings());
        settingsDialog.addEventListener('click', (event) => {
            if (event.target === settingsDialog) closeSettings();
        });
        settingsDialog.addEventListener('keydown', (event) => {
            if (event.key === 'Escape') {
                event.preventDefault();
                closeSettings();
                return;
            }
            if (event.key !== 'Tab') return;
            const focusable = [...settingsDialog.querySelectorAll(
                'a[href], button:not([disabled]), input:not([disabled]), select:not([disabled]), textarea:not([disabled]), [tabindex]:not([tabindex="-1"])'
            )].filter((element) => !element.closest('[hidden]'));
            if (!focusable.length) {
                event.preventDefault();
                settingsClose?.focus();
                return;
            }
            const first = focusable[0];
            const last = focusable[focusable.length - 1];
            if (event.shiftKey && document.activeElement === first) {
                event.preventDefault();
                last.focus();
            } else if (!event.shiftKey && document.activeElement === last) {
                event.preventDefault();
                first.focus();
            }
        });

        if (new URLSearchParams(window.location.search).get('panel') === '1') openSettings();
    }

    const toggles = [...document.querySelectorAll('[data-quick-panel-toggle]')];
    if (!toggles.length) return;

    const appBaseUrl = document.querySelector('.app-brand')?.getAttribute('href')?.replace(/\/dashboard$/, '') ?? '';
    const calendarPanel = document.getElementById('quick-panel-calendar');
    const notificationsPanel = document.getElementById('quick-panel-notifications');
    let activeCalendarMonth = new Date();
    activeCalendarMonth = new Date(activeCalendarMonth.getFullYear(), activeCalendarMonth.getMonth(), 1);
    let activePanel = null;

    const setPanelMessage = (panel, message, state = '') => {
        const content = panel.querySelector('[data-quick-panel-content]');
        const paragraph = document.createElement('p');
        paragraph.className = `quick-panel-message${state ? ` ${state}` : ''}`;
        paragraph.textContent = message;
        content.replaceChildren(paragraph);
    };

    const formatDate = (value, options) => {
        const date = new Date(value.includes('T') ? value : `${value.replace(' ', 'T')}`);
        if (Number.isNaN(date.getTime())) return value;
        return new Intl.DateTimeFormat(undefined, options).format(date);
    };

    const fetchPreview = async (panel, query = '') => {
        const url = `${panel.dataset.previewUrl}${query}`;
        const response = await fetch(url, {
            credentials: 'same-origin',
            cache: 'no-store',
            headers: { Accept: 'application/json' },
        });
        if (!response.ok) throw new Error('The preview could not be loaded.');
        const contentType = response.headers.get('content-type') ?? '';
        if (!contentType.includes('application/json')) throw new Error('The preview returned an unexpected response.');
        return response.json();
    };

    const updateUnreadBadge = (count) => {
        const badge = document.querySelector('[data-unread-badge]');
        const unreadCount = Number.isFinite(Number(count)) ? Math.max(0, Number(count)) : 0;
        badge.textContent = unreadCount > 99 ? '99+' : String(unreadCount);
        badge.hidden = unreadCount === 0;
        badge.setAttribute('aria-label', `${unreadCount} unread notifications`);
    };

    const renderNotifications = (data) => {
        const content = notificationsPanel.querySelector('[data-quick-panel-content]');
        content.replaceChildren();
        updateUnreadBadge(data.unread_count);
        if (!Array.isArray(data.notifications) || data.notifications.length === 0) {
            setPanelMessage(notificationsPanel, 'You’re all caught up. New project updates will appear here.');
            return;
        }

        const list = document.createDocumentFragment();
        data.notifications.forEach((notification) => {
            const link = document.createElement('a');
            link.className = `quick-notification${notification.read ? '' : ' is-unread'}`;
            link.href = notification.project_id
                ? `${appBaseUrl}/projects/view?project_id=${encodeURIComponent(notification.project_id)}`
                : `${appBaseUrl}/notifications`;
            const title = document.createElement('strong');
            title.textContent = notification.title;
            const message = document.createElement('span');
            message.className = 'quick-notification-message';
            message.textContent = notification.message;
            const meta = document.createElement('span');
            meta.className = 'quick-item-meta';
            meta.textContent = formatDate(notification.created_at, { dateStyle: 'medium', timeStyle: 'short' });
            link.append(title, message, meta);
            list.append(link);
        });
        content.append(list);
    };

    const renderCalendar = (data) => {
        const content = calendarPanel.querySelector('[data-quick-panel-content]');
        content.replaceChildren();
        const monthLabel = calendarPanel.querySelector('[data-calendar-month-label]');
        monthLabel.textContent = activeCalendarMonth.toLocaleDateString(undefined, { month: 'long', year: 'numeric' });

        if (!Array.isArray(data.events) || data.events.length === 0) {
            setPanelMessage(calendarPanel, 'No project or task deadlines this month.');
            return;
        }

        const list = document.createDocumentFragment();
        data.events.forEach((event) => {
            const link = document.createElement('a');
            link.className = 'quick-event';
            link.href = `${appBaseUrl}/projects/view?project_id=${encodeURIComponent(event.project_id)}${event.task_id ? `#task-${encodeURIComponent(event.task_id)}` : ''}`;
            const title = document.createElement('strong');
            title.textContent = event.title;
            const meta = document.createElement('span');
            meta.className = 'quick-item-meta';
            const eventType = event.event_type === 'project' ? 'Project' : 'Task';
            meta.textContent = `${formatDate(event.event_date, { month: 'short', day: 'numeric' })} · ${eventType} · ${event.project_key}`;
            link.append(title, meta);
            list.append(link);
        });
        content.append(list);
    };

    const loadNotifications = async () => {
        setPanelMessage(notificationsPanel, 'Loading notifications…');
        try {
            renderNotifications(await fetchPreview(notificationsPanel));
        } catch {
            setPanelMessage(notificationsPanel, 'Notifications could not be loaded. Please try again.', 'is-error');
        }
    };

    const loadCalendar = async () => {
        const month = `${activeCalendarMonth.getFullYear()}-${String(activeCalendarMonth.getMonth() + 1).padStart(2, '0')}`;
        calendarPanel.querySelector('[data-calendar-month-label]').textContent =
            activeCalendarMonth.toLocaleDateString(undefined, { month: 'long', year: 'numeric' });
        setPanelMessage(calendarPanel, 'Loading deadlines…');
        try {
            renderCalendar(await fetchPreview(calendarPanel, `?month=${encodeURIComponent(month)}`));
        } catch {
            setPanelMessage(calendarPanel, 'Deadlines could not be loaded. Please try again.', 'is-error');
        }
    };

    const closePanel = (panel, restoreFocus = false) => {
        if (!panel) return;
        panel.hidden = true;
        const toggle = toggles.find((button) => button.getAttribute('aria-controls') === panel.id);
        toggle?.setAttribute('aria-expanded', 'false');
        if (restoreFocus) toggle?.focus();
        if (activePanel === panel) activePanel = null;
    };

    toggles.forEach((toggle) => {
        toggle.addEventListener('click', () => {
            const panel = document.getElementById(toggle.getAttribute('aria-controls'));
            if (!panel) return;
            if (!panel.hidden) {
                closePanel(panel);
                return;
            }
            closePanel(activePanel);
            activePanel = panel;
            panel.hidden = false;
            toggle.setAttribute('aria-expanded', 'true');
            if (panel === calendarPanel) loadCalendar();
            if (panel === notificationsPanel) loadNotifications();
        });
    });

    document.querySelectorAll('[data-quick-panel-close]').forEach((button) => {
        button.addEventListener('click', () => closePanel(button.closest('.quick-popover'), true));
    });

    calendarPanel.querySelectorAll('[data-calendar-month-shift]').forEach((button) => {
        button.addEventListener('click', () => {
            activeCalendarMonth = new Date(
                activeCalendarMonth.getFullYear(),
                activeCalendarMonth.getMonth() + Number(button.dataset.calendarMonthShift),
                1
            );
            loadCalendar();
        });
    });

    document.addEventListener('click', (event) => {
        if (activePanel && !event.target.closest('.topbar-actions')) closePanel(activePanel);
    });

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape' && activePanel) closePanel(activePanel, true);
    });

    loadNotifications();
})();
