'use strict';

function setupToggle(buttonSelector, panelSelector) {
    const button = document.querySelector(buttonSelector);
    const panel = document.querySelector(panelSelector);
    if (!button || !panel) return;
    const mobile = window.matchMedia('(max-width: 760px)');
    function setExpanded(open) {
        const expanded = mobile.matches && open;
        panel.classList.toggle('is-open', expanded);
        panel.inert = mobile.matches && !expanded;
        button.setAttribute('aria-expanded', String(expanded));
    }
    function close() {
        if (mobile.matches && panel.contains(document.activeElement)) button.focus();
        setExpanded(false);
    }
    setExpanded(false);
    button.addEventListener('click', () => setExpanded(!panel.classList.contains('is-open')));
    panel.querySelectorAll('a').forEach(link => link.addEventListener('click', close));
    document.addEventListener('keydown', event => {
        if (event.key === 'Escape') close();
    });
    document.addEventListener('click', event => {
        if (event.target instanceof Node && !panel.contains(event.target) && !button.contains(event.target)) close();
    });
    mobile.addEventListener('change', close);
}

setupToggle('.menu-toggle', '#main-navigation');
setupToggle('.dashboard-menu', '#dashboard-sidebar');

const dialogTriggers = new WeakMap();
function openDialog(dialog, trigger) {
    if (!(dialog instanceof HTMLDialogElement)) return;
    dialogTriggers.set(dialog, trigger);
    dialog.showModal();
}

document.querySelectorAll('[data-open-dialog]').forEach(button => {
    button.addEventListener('click', () => {
        openDialog(document.getElementById(button.getAttribute('data-open-dialog') || ''), button);
    });
});

document.querySelectorAll('[data-demo-title]').forEach(button => {
    button.addEventListener('click', () => {
        const title = document.getElementById('action-title');
        const description = document.getElementById('action-description');
        if (title) title.textContent = button.getAttribute('data-demo-title');
        if (description) description.textContent = button.getAttribute('data-demo-description');
        openDialog(document.getElementById('action-dialog'), button);
    });
});

document.querySelectorAll('dialog').forEach(dialog => {
    dialog.querySelectorAll('[data-close-dialog]').forEach(button => {
        button.addEventListener('click', () => dialog.close());
    });
    dialog.addEventListener('click', event => {
        const bounds = dialog.getBoundingClientRect();
        if (event.target === dialog && (event.clientX < bounds.left || event.clientX > bounds.right || event.clientY < bounds.top || event.clientY > bounds.bottom)) {
            dialog.close();
        }
    });
    dialog.addEventListener('close', () => {
        const trigger = dialogTriggers.get(dialog);
        if (trigger instanceof HTMLElement) trigger.focus();
    });
});

const quiz = document.getElementById('demo-quiz');
if (quiz instanceof HTMLFormElement) {
    quiz.addEventListener('submit', event => {
        event.preventDefault();
        const selected = new FormData(quiz).get('answer');
        const result = document.getElementById('quiz-result');
        if (!result) return;
        result.textContent = selected === 'templates'
            ? 'Bonne réponse ! Twig génère le HTML à partir de templates. Ce résultat n’est pas enregistré.'
            : 'Pas tout à fait. Twig sert à générer les pages HTML à partir de templates. Vous pouvez réessayer.';
    });
}
