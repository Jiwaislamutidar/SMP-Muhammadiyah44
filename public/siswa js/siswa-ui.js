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

(function () {
  var content = document.querySelector('.content');
  var pageLoader = document.getElementById('pageLoader');
  var reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

  if (content) {
    window.addEventListener('pageshow', function () {
      content.classList.remove('page-leaving');
    });
  }

  if (pageLoader) {
    pageLoader.hidden = false;
    var introStartedAt = performance.now();
    var hideIntroAfterReady = function () {
      var elapsed = performance.now() - introStartedAt;
      var remaining = Math.min(Math.max(600 - elapsed, 0), Math.max(1200 - elapsed, 0));
      window.setTimeout(function () {
        pageLoader.classList.add('hide');
        initializeMotion();
      }, remaining);
    };

    if (document.readyState === 'complete') {
      hideIntroAfterReady();
    } else {
      document.addEventListener('DOMContentLoaded', hideIntroAfterReady, { once: true });
    }
  } else {
    initializeMotion();
  }

  function initializeMotion() {
    if (!content) {
      window.studentMotionScriptLoaded = true;
      return;
    }

    var originalContentVisibility = content.style.visibility;
    if (window.getComputedStyle(content).visibility === 'hidden') content.style.visibility = 'visible';

    function restoreContentVisibility() {
      content.style.visibility = originalContentVisibility;
    }

    function wrapHeadingText(heading) {
      var textNode = Array.prototype.find.call(heading.childNodes, function (node) {
        return node.nodeType === Node.TEXT_NODE && node.nodeValue.trim() !== '';
      });

      if (!textNode) return null;

      var mask = document.createElement('span');
      var inner = document.createElement('span');
      mask.className = 'text-mask is-pending';
      inner.className = 'text-mask-inner';
      textNode.parentNode.replaceChild(mask, textNode);
      mask.appendChild(inner);
      inner.appendChild(textNode);
      return mask;
    }

    var exclusionSelector = [
      'aside',
      '.sidebar',
      '.notification-menu',
      '.notification-panel',
      '.notification-overlay',
      '.filter-options',
      '.filter-select-trigger',
      '[role="listbox"]',
      '[role="menu"]',
      '[role="dialog"]',
      '[role="alertdialog"]',
      '[role="tooltip"]',
      '[popover]',
      '.popover',
      '.tooltip',
      '.modal',
      '.password-modal',
      '.password-modal-backdrop',
      '.camera-frame',
      '.scanner-card',
      '.session-status-badge',
      '.next-session .live',
      '#liveDatetime',
      '#statusText',
      '#statusDot',
      '#scanFeedback',
      '#scanSuccessModal',
      '[aria-live]',
      '[data-live]',
      'button',
      'input',
      'textarea',
      'select',
      'option',
      'output',
      'video',
      'canvas'
    ].join(',');

    function isExcluded(element) {
      return element.matches(exclusionSelector) || Boolean(element.closest(exclusionSelector));
    }

    var headings = Array.prototype.filter.call(
      content.querySelectorAll('h1, h2'),
      function (heading) { return !isExcluded(heading); }
    );
    var headingTargets = headings.map(function (heading) {
      return { element: heading, mask: wrapHeadingText(heading), isRow: false };
    }).filter(function (target) {
      return Boolean(target.mask);
    });

    var supportsNativeTransitions = window.CSS &&
      window.CSS.supports('view-transition-name: none') &&
      typeof document.startViewTransition === 'function';

    if (!supportsNativeTransitions && !reducedMotion) {
      document.addEventListener('click', function (event) {
        if (event.defaultPrevented || event.button !== 0 || event.metaKey || event.ctrlKey || event.shiftKey || event.altKey) return;
        if (!(event.target instanceof Element)) return;

        var link = event.target.closest('a[href]');
        if (!link || link.hasAttribute('download')) return;
        if (link.matches('[aria-current="page"], .nav-item.active')) {
          event.preventDefault();
          return;
        }
        if (link.target && link.target.toLowerCase() !== '_self') return;

        var destination = new URL(link.href, window.location.href);
        if (destination.origin !== window.location.origin || destination.hash) return;
        if (destination.pathname === window.location.pathname && destination.search === window.location.search) {
          event.preventDefault();
          return;
        }

        event.preventDefault();
        content.classList.add('page-leaving');
        window.setTimeout(function () {
          window.location.assign(destination.href);
        }, 150);
      });
    }

    if (document.documentElement.classList.contains('motion-off')) {
      content.classList.add('motion-ready');
      window.studentMotionScriptLoaded = true;
      return;
    }

    var rowSelector = 'tr, li, .session-row, .step-item';
    var displayCache = new WeakMap();
    var targetsByElement = new Map();

    function displayOf(element) {
      if (!displayCache.has(element)) displayCache.set(element, window.getComputedStyle(element).display);
      return displayCache.get(element);
    }

    function isRendered(element) {
      for (var current = element; current && current !== content; current = current.parentElement) {
        var style = window.getComputedStyle(current);
        if (current.hidden || style.display === 'none' || style.visibility === 'hidden' || style.contentVisibility === 'hidden') {
          return false;
        }
      }
      return true;
    }

    function registerTarget(element, inlineOnly, mask, isRow) {
      if (!element || element === content || isExcluded(element)) return;
      var target = targetsByElement.get(element);
      if (!target) {
        target = { element: element, inlineOnly: inlineOnly, mask: mask || null, isRow: Boolean(isRow) };
        targetsByElement.set(element, target);
      } else {
        target.inlineOnly = target.inlineOnly && inlineOnly;
        target.mask = target.mask || mask || null;
        target.isRow = target.isRow || Boolean(isRow);
      }
    }

    headingTargets.forEach(function (target) {
      if (target.mask) registerTarget(target.element, false, target.mask, false);
    });

    var textWalker = document.createTreeWalker(content, NodeFilter.SHOW_TEXT);
    var textRecords = [];
    var textNode;

    while ((textNode = textWalker.nextNode())) {
      if (!textNode.nodeValue.trim()) continue;
      var parent = textNode.parentElement;
      if (!parent || isExcluded(parent) || !isRendered(parent) || parent.closest('h1, h2')) continue;

      var row = parent.closest(rowSelector);
      if (row && content.contains(row)) {
        textRecords.push({ row: row });
        continue;
      }

      var block = parent;
      while (block && block !== content && (displayOf(block) === 'inline' || displayOf(block) === 'contents')) {
        block = block.parentElement;
      }

      var branch = null;
      if (block && block !== content) {
        branch = parent;
        while (branch !== block && branch.parentElement && branch.parentElement !== block) {
          branch = branch.parentElement;
        }
      } else {
        branch = parent;
      }

      textRecords.push({ block: block || content, branch: branch });
    }

    var branchesByBlock = new Map();
    textRecords.forEach(function (record) {
      if (record.row) {
        registerTarget(record.row, false, null, true);
        return;
      }
      var branches = branchesByBlock.get(record.block);
      if (!branches) {
        branches = new Set();
        branchesByBlock.set(record.block, branches);
      }
      branches.add(record.branch);
    });

    textRecords.forEach(function (record) {
      if (record.row) return;
      var branches = branchesByBlock.get(record.block);
      var useInlineBranch = record.branch &&
        displayOf(record.branch) === 'inline' &&
        (record.block === content || branches.size > 1);
      var targetElement = useInlineBranch ? record.branch : record.block;
      var inlineOnly = displayOf(targetElement) === 'inline';
      registerTarget(targetElement, inlineOnly, null, targetElement.matches(rowSelector));
    });

    var targets = Array.from(targetsByElement.values()).sort(function (left, right) {
      var leftRect = left.element.getBoundingClientRect();
      var rightRect = right.element.getBoundingClientRect();
      return leftRect.top - rightRect.top ||
        leftRect.left - rightRect.left ||
        (left.element.compareDocumentPosition(right.element) & Node.DOCUMENT_POSITION_FOLLOWING ? -1 : 1);
    }).slice(0, 150);

    targets.forEach(function (target) {
      target.element.dataset.motionTextTarget = 'true';
      if (!target.mask && target.inlineOnly) {
        target.element.classList.add('text-reveal-inline-target', 'text-reveal-inline-pending');
      } else if (!target.mask) {
        target.element.classList.add('text-reveal-target', 'text-reveal-pending');
      }
    });
    if (reducedMotion || typeof window.IntersectionObserver !== 'function') {
      targets.forEach(function (target) {
        target.element.dataset.motionTextRevealed = 'true';
        target.element.classList.remove(
          'text-reveal-target',
          'text-reveal-pending',
          'text-reveal-inline-target',
          'text-reveal-inline-pending'
        );
        if (target.mask) {
          target.mask.classList.remove('is-pending');
          target.mask.classList.add('is-visible');
        }
      });
      content.classList.add('motion-ready');
      restoreContentVisibility();
      window.studentMotionScriptLoaded = true;
      return;
    }

    var observer;
    var queuedTargets = [];
    var flushScheduled = false;
    var motionStep = window.matchMedia('(max-width: 640px)').matches ? 35 : 45;
    var motionDuration = window.matchMedia('(max-width: 640px)').matches ? 350 : 450;
    var targetsByElementMap = new Map(targets.map(function (target) {
      return [target.element, target];
    }));

    function revealBatch(batch) {
      var ordered = batch.sort(function (left, right) {
        var leftRect = left.element.getBoundingClientRect();
        var rightRect = right.element.getBoundingClientRect();
        return leftRect.top - rightRect.top || leftRect.left - rightRect.left;
      });

      window.requestAnimationFrame(function () {
        ordered.forEach(function (target, index) {
          var element = target.element;
          if (element.dataset.motionTextRevealed) return;

          element.dataset.motionTextRevealed = 'true';
          var delay = Math.min(index * motionStep, 700);

          if (target.mask) {
            target.mask.style.transitionDelay = delay + 'ms';
            target.mask.classList.add('is-visible');
            window.setTimeout(function () {
              target.mask.classList.remove('is-pending');
              target.mask.style.removeProperty('transition-delay');
            }, delay + 600);
            return;
          }

          if (target.inlineOnly) {
            element.style.setProperty('--text-reveal-delay', delay + 'ms');
            element.classList.add('text-reveal-inline-visible');
            window.setTimeout(function () {
              element.classList.remove('text-reveal-inline-target', 'text-reveal-inline-pending', 'text-reveal-inline-visible');
              element.style.removeProperty('--text-reveal-delay');
            }, delay + motionDuration);
            return;
          }

          element.style.setProperty('--text-reveal-delay', delay + 'ms');
          element.classList.add('text-reveal-animating', 'text-reveal-visible');
          window.setTimeout(function () {
            element.classList.remove('text-reveal-target', 'text-reveal-pending', 'text-reveal-animating', 'text-reveal-visible');
            element.style.removeProperty('--text-reveal-delay');
          }, delay + motionDuration);
        });
      });
    }

    function flushQueuedTargets() {
      flushScheduled = false;
      var batch = queuedTargets;
      queuedTargets = [];
      if (batch.length) revealBatch(batch);
    }

    function queueTargets(batch) {
      batch.forEach(function (target) {
        if (target.element.dataset.motionTextQueued || target.element.dataset.motionTextRevealed) return;
        target.element.dataset.motionTextQueued = 'true';
        queuedTargets.push(target);
      });
      if (flushScheduled || queuedTargets.length === 0) return;
      flushScheduled = true;
      window.requestAnimationFrame(flushQueuedTargets);
    }

    observer = new IntersectionObserver(function (entries) {
      var batch = [];
      entries.forEach(function (entry) {
        if (!entry.isIntersecting) return;
        observer.unobserve(entry.target);
        var target = targetsByElementMap.get(entry.target);
        if (target) batch.push(target);
      });
      if (batch.length) queueTargets(batch);
    }, {
      threshold: 0.12,
      rootMargin: '0px 0px -8% 0px'
    });

    var firstViewport = [];
    targets.forEach(function (target) {
      var bounds = target.element.getBoundingClientRect();
      if (bounds.top < window.innerHeight && bounds.bottom > 0) {
        firstViewport.push(target);
      } else {
        observer.observe(target.element);
      }
    });

    content.classList.add('motion-ready');
    restoreContentVisibility();
    if (firstViewport.length) {
      window.requestAnimationFrame(function () {
        revealBatch(firstViewport);
      });
    }
    window.studentMotionScriptLoaded = true;
  }
})();
