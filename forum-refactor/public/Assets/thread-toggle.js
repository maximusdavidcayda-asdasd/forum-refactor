document.addEventListener('DOMContentLoaded', () => {
  const buttons = document.querySelectorAll('.js-toggle-replies');

  buttons.forEach((button) => {
    const targetId = button.getAttribute('data-target');
    const container = targetId ? document.getElementById(targetId) : null;
    if (!container) {
      return;
    }

    button.addEventListener('click', () => {
      const currentlyExpanded = button.getAttribute('aria-expanded') === 'true';
      const nextExpanded = !currentlyExpanded;

      button.setAttribute('aria-expanded', String(nextExpanded));
      button.textContent = nextExpanded ? 'Hide replies' : 'Show replies';
      container.hidden = !nextExpanded;
    });
  });
});