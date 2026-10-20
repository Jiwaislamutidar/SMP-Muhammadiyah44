(function () {
  var sidebar = document.getElementById('sidebar');
  var sidebarOverlay = document.getElementById('sidebarOverlay');
  var sidebarClose = document.getElementById('sidebarCloseBtn');
  var burger = document.getElementById('burgerBtn');
  var notificationToggle = document.getElementById('notificationToggle');
  var notificationPanel = document.getElementById('notificationPanel');
  var notificationOverlay = document.getElementById('notificationOverlay');
  var liveDatetime = document.getElementById('liveDatetime');

  function setSidebarOpen(open) {
    sidebar.classList.toggle('open', open);
    sidebarOverlay.classList.toggle('show', open);
    sidebarOverlay.setAttribute('aria-hidden', String(!open));
    burger.classList.toggle('active', open);
    burger.setAttribute('aria-expanded', String(open));
    burger.setAttribute('aria-label', open ? 'Tutup sidebar' : 'Buka sidebar');
  }

  function signatures() {
    return Array.prototype.map.call(
      notificationPanel.querySelectorAll('[data-notification-signature]'),
      function (item) {
        return item.dataset.notificationSignature;
      }
    );
  }

  function closeNotifications(returnFocus) {
    notificationToggle.setAttribute('aria-expanded', 'false');
    notificationPanel.classList.remove('is-open');
    notificationPanel.hidden = true;
    notificationOverlay.classList.remove('is-open');
    notificationOverlay.setAttribute('aria-hidden', 'true');

    if (returnFocus) notificationToggle.focus();
  }

  function updateBadge() {
    var seen = [];

    try {
      seen = JSON.parse(localStorage.getItem(notificationToggle.dataset.seenKey) || '[]');
    } catch (error) {
      seen = [];
    }

    notificationToggle.querySelector('.badge-dot').hidden = !signatures().some(function (signature) {
      return !Array.isArray(seen) || !seen.includes(signature);
    });
  }

  function openNotifications() {
    setSidebarOpen(false);
    notificationPanel.hidden = false;
    notificationOverlay.classList.add('is-open');
    notificationOverlay.setAttribute('aria-hidden', 'false');
    notificationToggle.setAttribute('aria-expanded', 'true');
    window.requestAnimationFrame(function () {
      notificationPanel.classList.add('is-open');
    });
    notificationToggle.querySelector('.badge-dot').hidden = true;

    try {
      localStorage.setItem(notificationToggle.dataset.seenKey, JSON.stringify(signatures()));
    } catch (error) {
      return;
    }

    updateBadge();
  }

  function updateLiveDatetime() {
    var dateParts = new Intl.DateTimeFormat('id-ID', {
      timeZone: 'Asia/Jakarta',
      weekday: 'long',
      day: 'numeric',
      month: 'long',
      year: 'numeric'
    }).formatToParts(new Date());
    var timeParts = new Intl.DateTimeFormat('id-ID', {
      timeZone: 'Asia/Jakarta',
      hour: '2-digit',
      minute: '2-digit',
      second: '2-digit',
      hourCycle: 'h23'
    }).formatToParts(new Date());
    var value = function (parts, type) {
      return parts.find(function (part) {
        return part.type === type;
      }).value;
    };

    liveDatetime.innerHTML =
      '<span class="date-html">' + value(dateParts, 'weekday') + ', ' +
      value(dateParts, 'day') + ' ' + value(dateParts, 'month') + ' ' +
      value(dateParts, 'year') + '</span><span class="sep"> | </span>' +
      '<span class="time-html">' + value(timeParts, 'hour') + ':' +
      value(timeParts, 'minute') + ':' + value(timeParts, 'second') + ' WIB</span>';
  }

  window.toggleSidebar = function (open) {
    setSidebarOpen(typeof open === 'boolean' ? open : !sidebar.classList.contains('open'));
    if (notificationToggle.getAttribute('aria-expanded') === 'true') closeNotifications(false);
  };

  burger.addEventListener('click', function () {
    window.toggleSidebar();
  });

  sidebarClose.addEventListener('click', function () {
    window.toggleSidebar(false);
  });

  notificationToggle.addEventListener('click', function () {
    if (notificationToggle.getAttribute('aria-expanded') === 'true') {
      closeNotifications(false);
    } else {
      openNotifications();
    }
  });

  document.addEventListener('click', function (event) {
    if (!(event.target instanceof Element)) return;
    var target = event.target;
    var clickedBurger = target.closest('#burgerBtn');
    var clickedNotificationToggle = target.closest('#notificationToggle');

    if (clickedBurger) {
      if (notificationToggle.getAttribute('aria-expanded') === 'true') closeNotifications(false);
      return;
    }

    if (notificationToggle.getAttribute('aria-expanded') === 'true' && !clickedNotificationToggle) {
      closeNotifications(false);
    }

    if (sidebar.classList.contains('open')) window.toggleSidebar(false);
  }, true);

  document.addEventListener('keydown', function (event) {
    if (event.key !== 'Escape') return;
    if (notificationToggle.getAttribute('aria-expanded') === 'true') closeNotifications(true);
    if (sidebar.classList.contains('open')) window.toggleSidebar(false);
  });

  sidebarOverlay.addEventListener('click', function () {
    window.toggleSidebar(false);
  });

  sidebar.querySelector('[aria-current="page"]').addEventListener('click', function (event) {
    event.preventDefault();
    window.toggleSidebar(false);
  });

  updateLiveDatetime();
  window.setInterval(updateLiveDatetime, 1000);
  updateBadge();
})();
