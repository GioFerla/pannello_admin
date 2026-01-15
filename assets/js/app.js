document.addEventListener('DOMContentLoaded', () => {
  const mobileBtn = document.getElementById('mobile-menu-btn');
  const mobileMenu = document.getElementById('mobile-menu');
  if (mobileBtn && mobileMenu) {
    mobileBtn.addEventListener('click', () => {
      mobileMenu.classList.toggle('hidden');
    });
  }

  document.querySelectorAll('[data-dismiss-toast]').forEach((btn) => {
    btn.addEventListener('click', (e) => {
      const toast = e.target.closest('.toast');
      if (toast) toast.remove();
    });
  });

  setTimeout(() => {
    document.querySelectorAll('#toast-container .toast').forEach((toast) => {
      toast.classList.add('opacity-0');
      setTimeout(() => toast.remove(), 300);
    });
  }, 5000);

  const deleteModal = document.getElementById('delete-modal');
  const deleteName = document.getElementById('delete-event-name');
  const deleteIdInput = document.getElementById('delete-event-id');
  const deleteForm = document.getElementById('delete-form');
  document.querySelectorAll('[data-delete]').forEach((btn) => {
    btn.addEventListener('click', () => {
      if (!deleteModal || !deleteName || !deleteIdInput || !deleteForm) return;
      deleteName.textContent = btn.dataset.name || '';
      deleteIdInput.value = btn.dataset.id || '';
      deleteModal.classList.remove('hidden');
    });
  });
  document.querySelectorAll('[data-close-modal]').forEach((btn) => {
    btn.addEventListener('click', () => {
      if (deleteModal) deleteModal.classList.add('hidden');
    });
  });

  document.querySelectorAll('[data-event-form]').forEach((form) => {
    form.addEventListener('submit', (e) => {
      const errors = [];
      const name = form.querySelector('[name="name"]');
      const desc = form.querySelector('[name="description"]');
      const scope = form.querySelector('[name="scope"]');
      const start = form.querySelector('[name="startDateTime"]');
      const end = form.querySelector('[name="endDateTime"]');
      const email = form.querySelector('[name="contact_email"]');
      const phone = form.querySelector('[name="contact_phone"]');
      const media = form.querySelector('[name="mediaResource_uri"]');

      if (name && name.value.trim().length < 3) errors.push('Il nome deve avere almeno 3 caratteri.');
      if (desc && desc.value.trim().length < 10) errors.push('La descrizione deve avere almeno 10 caratteri.');
      if (scope && scope.value.trim() === '') errors.push('Seleziona uno scopo.');

      const startVal = start?.value;
      const endVal = end?.value;
      if (!startVal) errors.push('Inserisci la data di inizio.');
      if (!endVal) errors.push('Inserisci la data di fine.');
      if (startVal && endVal) {
        const startDate = new Date(startVal);
        const endDate = new Date(endVal);
        if (startDate > endDate) errors.push('La data di inizio deve precedere quella di fine.');
      }

      if (media && !/^https?:\/\//i.test(media.value.trim())) errors.push('Inserisci un URL valido per la risorsa.');
      if (email && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email.value.trim())) errors.push('Email non valida.');
      if (phone && phone.value.trim().length < 7) errors.push('Telefono non valido.');

      const errorsBox = form.querySelector('[data-client-errors]');
      if (errorsBox) errorsBox.innerHTML = '';

      if (errors.length) {
        e.preventDefault();
        if (errorsBox) {
          const list = document.createElement('ul');
          list.className = 'list-disc list-inside space-y-1 text-sm text-red-700';
          errors.forEach((msg) => {
            const li = document.createElement('li');
            li.textContent = msg;
            list.appendChild(li);
          });
          errorsBox.appendChild(list);
          errorsBox.classList.remove('hidden');
        } else {
          alert(errors.join('\n'));
        }
      }
    });
  });
});
