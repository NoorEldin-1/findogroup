(() => {
    const root = document.documentElement;
    const applyTheme = theme => {
        root.classList.toggle('dark', theme === 'dark');
        document.querySelectorAll('[data-theme-toggle]').forEach(button => {
            const label = root.lang === 'en' ? (theme === 'dark' ? 'Light mode' : 'Dark mode') : (theme === 'dark' ? 'الوضع الفاتح' : 'الوضع الداكن');
            button.setAttribute('aria-label', label);
            button.setAttribute('title', label);
        });
    };
    const savedTheme = () => {
        try { return localStorage.getItem('findo-theme') || 'light'; }
        catch { return 'light'; }
    };
    applyTheme(savedTheme());
    document.addEventListener('DOMContentLoaded', () => {
        applyTheme(savedTheme());
        document.querySelectorAll('[data-theme-toggle]').forEach(button => {
            button.addEventListener('click', () => {
                const next = root.classList.contains('dark') ? 'light' : 'dark';
                try { localStorage.setItem('findo-theme', next); } catch {}
                applyTheme(next);
                window.dispatchEvent(new Event('findo:theme'));
            });
        });
    });
    window.addEventListener('storage', event => {
        if (event.key === 'findo-theme') applyTheme(event.newValue || 'light');
    });
})();
