document.addEventListener('click', async (event) => {
  const button = event.target.closest('[data-pdf-export]');
  if (!button || button.disabled) return;

  const label = button.querySelector('[data-export-label]');
  const originalLabel = label.textContent;
  button.disabled = true;
  button.classList.remove('is-complete');
  button.classList.add('is-loading');
  button.setAttribute('aria-busy', 'true');
  label.textContent = 'Menyiapkan PDF...';

  try {
    const url = new URL(button.dataset.exportUrl, window.location.href);
    if (button.dataset.exportParams !== undefined) {
      url.search = button.dataset.exportParams;
    } else {
      url.search = window.location.search;
    }

    const response = await fetch(url, { headers: { Accept: 'application/pdf' } });
    if (!response.ok || !response.headers.get('content-type')?.includes('application/pdf')) {
      throw new Error('Server tidak dapat membuat PDF. Silakan coba lagi.');
    }

    const downloadUrl = URL.createObjectURL(await response.blob());
    const link = document.createElement('a');
    link.href = downloadUrl;
    link.download = button.dataset.exportFilename;
    document.body.append(link);
    link.click();
    link.remove();
    window.setTimeout(() => URL.revokeObjectURL(downloadUrl), 1000);

    button.classList.remove('is-loading');
    button.classList.add('is-complete');
    button.removeAttribute('aria-busy');
    label.textContent = 'Unduhan Selesai';
    window.setTimeout(() => {
      button.classList.remove('is-complete');
      label.textContent = originalLabel;
      button.disabled = false;
    }, 1800);
  } catch (error) {
    button.classList.remove('is-loading');
    button.removeAttribute('aria-busy');
    label.textContent = originalLabel;
    button.disabled = false;
    window.alert(error instanceof Error ? error.message : 'PDF gagal diunduh.');
  }
});
