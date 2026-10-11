(function () {
  var sidebar = document.getElementById('sidebar');
  var burger = document.getElementById('burgerBtn');
  var notificationToggle = document.getElementById('notificationToggle');
  var notificationPanel = document.getElementById('notificationPanel');
  var notificationOverlay = document.getElementById('notificationOverlay');
  var liveDatetime = document.getElementById('liveDatetime');

  if (liveDatetime) {
    function updateLiveDatetime() {
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
      var partValue = function (parts, type) {
        return parts.find(function (part) {
          return part.type === type;
        }).value;
      };

      liveDatetime.innerHTML =
        '<span class="dt-date">' + partValue(dateParts, 'weekday') + ', ' +
        partValue(dateParts, 'day') + ' ' + partValue(dateParts, 'month') + ' ' +
        partValue(dateParts, 'year') + '</span>' +
        '<span class="dt-sep"> | </span>' +
        '<span class="dt-time">' + partValue(timeParts, 'hour') + ':' +
        partValue(timeParts, 'minute') + '</span>' +
        '<span class="dt-seconds">:' + partValue(timeParts, 'second') + '</span>' +
        '<span class="dt-zone"> WIB</span>';
    }

    updateLiveDatetime();
    window.setInterval(updateLiveDatetime, 1000);
  }

  if (!sidebar || !burger || !notificationToggle || !notificationPanel || !notificationOverlay) return;

  var badge = notificationToggle.querySelector('.badge-dot');
  var seenKey = notificationToggle.dataset.seenKey;

  function notificationSignatures() {
    return Array.prototype.map.call(
      notificationPanel.querySelectorAll('[data-notification-signature]'),
      function (item) {
        return item.dataset.notificationSignature;
      }
    );
  }

  function updateNotificationBadge() {
    var seen = [];

    try {
      seen = JSON.parse(localStorage.getItem(seenKey) || '[]');
    } catch (error) {
      seen = [];
    }

    var hasUnseen = notificationSignatures().some(function (signature) {
      return !Array.isArray(seen) || !seen.includes(signature);
    });

    badge.hidden = !hasUnseen;
  }

  function closeNotifications(returnFocus) {
    notificationToggle.setAttribute('aria-expanded', 'false');
    notificationPanel.classList.remove('is-open');
    notificationPanel.hidden = true;
    notificationOverlay.classList.remove('is-open');
    notificationOverlay.setAttribute('aria-hidden', 'true');

    if (returnFocus) notificationToggle.focus();
  }

  function openNotifications() {
    if (typeof window.toggleSidebar === 'function') window.toggleSidebar(false);
    notificationPanel.hidden = false;
    notificationOverlay.classList.add('is-open');
    notificationOverlay.setAttribute('aria-hidden', 'false');
    notificationToggle.setAttribute('aria-expanded', 'true');
    window.requestAnimationFrame(function () {
      notificationPanel.classList.add('is-open');
    });

    badge.hidden = true;

    try {
      localStorage.setItem(seenKey, JSON.stringify(notificationSignatures()));
    } catch (error) {
      return;
    }

    updateNotificationBadge();
  }

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
    var notificationIsOpen = notificationToggle.getAttribute('aria-expanded') === 'true';

    if (notificationIsOpen && !clickedNotificationToggle) closeNotifications(false);

    if (sidebar.classList.contains('open') && !clickedBurger && typeof window.toggleSidebar === 'function') {
      window.toggleSidebar(false);
    }
  }, true);

  document.addEventListener('keydown', function (event) {
    if (event.key !== 'Escape') return;
    if (notificationToggle.getAttribute('aria-expanded') === 'true') closeNotifications(true);
    if (sidebar.classList.contains('open') && typeof window.toggleSidebar === 'function') {
      window.toggleSidebar(false);
    }
  });

  updateNotificationBadge();
})();
