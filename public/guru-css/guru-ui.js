
(function () {
  var sidebar = document.getElementById('sidebar');
  var sidebarOverlay = document.getElementById('sidebarOverlay');
  var sidebarClose = document.getElementById('sidebarCloseBtn');
  var burger = document.getElementById('burgerBtn');
  var notificationToggle = document.getElementById('notificationToggle');
  var notificationPanel = document.getElementById('notificationPanel');
  var notificationOverlay = document.getElementById('notificationOverlay');
  var liveDatetime = document.getElementById('liveDatetime');

  document.addEventListener('submit', function (event) {
    var form = event.target;

    if (!(form instanceof HTMLFormElement)) return;

    var button = form.querySelector('.processing-filter-button');

    if (!button) return;

    button.classList.add('is-processing');
    button.setAttribute('aria-busy', 'true');
  });

  function setSidebarOpen(open) {
    if (!sidebar || !sidebarOverlay || !burger) return;

    sidebar.classList.toggle('open', open);
    sidebarOverlay.classList.toggle('show', open);
    sidebarOverlay.setAttribute('aria-hidden', String(!open));
    burger.classList.toggle('active', open);
    burger.setAttribute('aria-expanded', String(open));
    burger.setAttribute('aria-label', open ? 'Tutup sidebar' : 'Buka sidebar');
  }

  function signatures() {
    if (!notificationPanel) return [];

    return Array.prototype.map.call(
      notificationPanel.querySelectorAll('[data-notification-signature]'),
      function (item) {
        return item.dataset.notificationSignature;
      }
    );
  }

  function closeNotifications(returnFocus) {
    if (!notificationToggle || !notificationPanel || !notificationOverlay) return;

    notificationToggle.setAttribute('aria-expanded', 'false');
    notificationPanel.classList.remove('is-open');
    notificationPanel.hidden = true;
    notificationOverlay.classList.remove('is-open');
    notificationOverlay.setAttribute('aria-hidden', 'true');

    if (returnFocus) notificationToggle.focus();
  }

  function updateBadge() {
    if (!notificationToggle) return;

    var badge = notificationToggle.querySelector('.badge-dot');

    if (!badge) return;

    var seen = [];

    try {
      seen = JSON.parse(
        localStorage.getItem(notificationToggle.dataset.seenKey) || '[]'
      );
    } catch (error) {
      seen = [];
    }

    badge.hidden = !signatures().some(function (signature) {
      return !Array.isArray(seen) || !seen.includes(signature);
    });
  }

  function openNotifications() {
    if (!notificationToggle || !notificationPanel || !notificationOverlay) return;

    setSidebarOpen(false);

    notificationPanel.hidden = false;
    notificationOverlay.classList.add('is-open');
    notificationOverlay.setAttribute('aria-hidden', 'false');
    notificationToggle.setAttribute('aria-expanded', 'true');

    window.requestAnimationFrame(function () {
      notificationPanel.classList.add('is-open');
    });

    var badge = notificationToggle.querySelector('.badge-dot');

    if (badge) badge.hidden = true;

    try {
      localStorage.setItem(
        notificationToggle.dataset.seenKey,
        JSON.stringify(signatures())
      );
    } catch (error) {
      updateBadge();
      return;
    }

    updateBadge();
  }

  function updateLiveDatetime() {
    if (!liveDatetime) return;

    var now = new Date();

    var dateParts = new Intl.DateTimeFormat('id-ID', {
      timeZone: 'Asia/Jakarta',
      weekday: 'long',
      day: 'numeric',
      month: 'long',
      year: 'numeric'
    }).formatToParts(now);

    var timeParts = new Intl.DateTimeFormat('id-ID', {
      timeZone: 'Asia/Jakarta',
      hour: '2-digit',
      minute: '2-digit',
      second: '2-digit',
      hourCycle: 'h23'
    }).formatToParts(now);

    var value = function (parts, type) {
      var part = parts.find(function (item) {
        return item.type === type;
      });

      return part ? part.value : '';
    };

    liveDatetime.innerHTML =
      '<span class="date-html">' +
      value(dateParts, 'weekday') + ', ' +
      value(dateParts, 'day') + ' ' +
      value(dateParts, 'month') + ' ' +
      value(dateParts, 'year') +
      '</span><span class="sep"> | </span>' +
      '<span class="time-html">' +
      value(timeParts, 'hour') + ':' +
      value(timeParts, 'minute') + ':' +
      value(timeParts, 'second') +
      ' WIB</span>';
  }

  window.toggleSidebar = function (open) {
    if (!sidebar) return;

    setSidebarOpen(
      typeof open === 'boolean'
        ? open
        : !sidebar.classList.contains('open')
    );

    if (
      notificationToggle &&
      notificationToggle.getAttribute('aria-expanded') === 'true'
    ) {
      closeNotifications(false);
    }
  };

  if (burger) {
    burger.addEventListener('click', function () {
      window.toggleSidebar();
    });
  }

  if (sidebarClose) {
    sidebarClose.addEventListener('click', function () {
      window.toggleSidebar(false);
    });
  }

  if (notificationToggle) {
    notificationToggle.addEventListener('click', function () {
      if (notificationToggle.getAttribute('aria-expanded') === 'true') {
        closeNotifications(false);
      } else {
        openNotifications();
      }
    });
  }

  document.addEventListener('click', function (event) {
    if (!(event.target instanceof Element)) return;

    var target = event.target;
    var clickedBurger = target.closest('#burgerBtn');
    var clickedNotificationToggle = target.closest('#notificationToggle');

    if (clickedBurger) {
      if (
        notificationToggle &&
        notificationToggle.getAttribute('aria-expanded') === 'true'
      ) {
        closeNotifications(false);
      }

      return;
    }

    if (
      notificationToggle &&
      notificationToggle.getAttribute('aria-expanded') === 'true' &&
      !clickedNotificationToggle
    ) {
      closeNotifications(false);
    }

    if (sidebar && sidebar.classList.contains('open')) {
      window.toggleSidebar(false);
    }
  }, true);

  document.addEventListener('keydown', function (event) {
    if (event.key !== 'Escape') return;

    if (
      notificationToggle &&
      notificationToggle.getAttribute('aria-expanded') === 'true'
    ) {
      closeNotifications(true);
    }

    if (sidebar && sidebar.classList.contains('open')) {
      window.toggleSidebar(false);
    }
  });

  if (sidebarOverlay) {
    sidebarOverlay.addEventListener('click', function () {
      window.toggleSidebar(false);
    });
  }

  if (sidebar) {
    var currentPageLink = sidebar.querySelector('[aria-current="page"]');

    if (currentPageLink) {
      currentPageLink.addEventListener('click', function (event) {
        event.preventDefault();
        window.toggleSidebar(false);
      });
    }
  }

  updateLiveDatetime();
  window.setInterval(updateLiveDatetime, 1000);
  updateBadge();
})();
