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

  const cloneRow = (targetName) => {
    const repeater = document.querySelector(`[data-repeater="${targetName}"]`);
    if (!repeater) return;
    const rows = repeater.querySelectorAll('[data-row]');
    if (rows.length === 0) return;
    const template = rows[rows.length - 1].cloneNode(true);
    template.querySelectorAll('input').forEach((input) => {
      input.value = '';
    });
    repeater.appendChild(template);
  };

  document.querySelectorAll('[data-add-row]').forEach((btn) => {
    btn.addEventListener('click', () => {
      const target = btn.getAttribute('data-target');
      if (target) cloneRow(target);
    });
  });

  document.addEventListener('click', (e) => {
    const removeBtn = e.target.closest('[data-remove-row]');
    if (removeBtn) {
      const row = removeBtn.closest('[data-row]');
      const repeater = removeBtn.closest('[data-repeater]');
      if (row && repeater && repeater.querySelectorAll('[data-row]').length > 1) {
        row.remove();
      }
    }
  });

  document.querySelectorAll('[data-event-form]').forEach((form) => {
    form.addEventListener('submit', (e) => {
      const errors = [];
      const name = form.querySelector('[name="name"]');
      const desc = form.querySelector('[name="description"]');
      const category = form.querySelector('[name="category"]');
      const start = form.querySelector('[name="startDateTime"]');
      const end = form.querySelector('[name="endDateTime"]');
      const sedeNome = form.querySelector('[name="sede_nome"]');
      const sedeVia = form.querySelector('[name="sede_via"]');
      const sedeCitta = form.querySelector('[name="sede_citta"]');
      const sedeProvincia = form.querySelector('[name="sede_provincia"]');
      const contattoEmail = form.querySelector('[name="contatto_email"]');

      if (name && name.value.trim().length < 3) errors.push('Il nome deve avere almeno 3 caratteri.');
      if (desc && desc.value.trim().length < 10) errors.push('La descrizione deve avere almeno 10 caratteri.');
      if (category && category.value.trim() === '') errors.push("Inserisci l'ambito.");
      if (sedeNome && sedeNome.value.trim() === '') errors.push('Inserisci il nome della sede.');
      if (sedeVia && sedeVia.value.trim() === '') errors.push('Inserisci l\'indirizzo della sede.');
      if (sedeCitta && sedeCitta.value.trim() === '') errors.push('Inserisci la città.');
      if (sedeProvincia && sedeProvincia.value.trim() === '') errors.push('Inserisci la provincia.');
      if (contattoEmail && contattoEmail.value.trim() === '') errors.push('Inserisci la email di contatto.');
      if (contattoEmail && contattoEmail.value.trim() && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(contattoEmail.value.trim())) {
        errors.push('Inserisci una email valida.');
      }

      const startVal = start?.value;
      const endVal = end?.value;
      if (!startVal) errors.push('Inserisci la data di inizio.');
      if (!endVal) errors.push('Inserisci la data di fine.');
      if (startVal && endVal) {
        const startDate = new Date(startVal);
        const endDate = new Date(endVal);
        if (startDate > endDate) errors.push('La data di inizio deve precedere quella di fine.');
      }

      const mediaNames = form.querySelectorAll('[name="media_nome[]"]');
      const mediaTypes = form.querySelectorAll('[name="media_tipo[]"]');
      const mediaUrls = form.querySelectorAll('[name="media_url[]"]');
      mediaNames.forEach((el, idx) => {
        const n = el.value.trim();
        const t = mediaTypes[idx]?.value.trim();
        const u = mediaUrls[idx]?.value.trim();
        if (n || t || u) {
          if (!n || !t || !u) errors.push('Completa nome, tipo e URL per i media.');
          if (u && !/^https?:\/\//i.test(u)) errors.push('Inserisci URL validi per i media.');
        }
      });

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
