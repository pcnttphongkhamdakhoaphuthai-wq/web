const fileDialogGuard = (() => {
  let fileDialogActive = false;
  let releaseTimer = null;

  const setActive = (active) => {
    window.clearTimeout(releaseTimer);
    fileDialogActive = active;
    document.documentElement.classList.toggle('file-dialog-active', active);
  };

  const releaseSoon = () => {
    window.clearTimeout(releaseTimer);
    releaseTimer = window.setTimeout(() => setActive(false), 900);
  };

  const isFileInput = (target) => target instanceof HTMLInputElement && target.type === 'file';

  document.addEventListener('pointerdown', (event) => {
    if (isFileInput(event.target)) {
      setActive(true);
    }
  }, true);

  document.addEventListener('keydown', (event) => {
    if (isFileInput(event.target) && (event.key === 'Enter' || event.key === ' ')) {
      setActive(true);
    }
  }, true);

  document.addEventListener('change', (event) => {
    if (isFileInput(event.target)) {
      releaseSoon();
    }
  }, true);

  window.addEventListener('focus', () => {
    if (fileDialogActive) {
      releaseSoon();
    }
  });

  return {
    isActive: () => fileDialogActive || document.hidden,
  };
})();

document.addEventListener('DOMContentLoaded', () => {
  const widget = document.querySelector('[data-floating-chat]');
  if (!widget) {
    return;
  }

  const launcher = widget.querySelector('[data-chat-toggle="true"]');
  const closeButton = widget.querySelector('[data-chat-close="true"]');
  const thread = widget.querySelector('[data-chat-thread]');
  const counter = widget.querySelector('.floating-chat-count');
  const nudge = widget.querySelector('[data-chat-nudge]');
  const nudgeText = widget.querySelector('[data-chat-nudge-text]');
  const nudgeOpenButton = widget.querySelector('[data-chat-nudge-open="true"]');
  const nudgeCloseButton = widget.querySelector('[data-chat-nudge-close="true"]');
  const openButtons = document.querySelectorAll('[data-chat-open="true"]');
  const endpoint = widget.getAttribute('data-chat-endpoint');
  let latestChatId = Number(widget.getAttribute('data-chat-latest-id') || '0');

  const scrollThreadToBottom = () => {
    if (thread) {
      thread.scrollTop = thread.scrollHeight;
    }
  };

  const escapeHtml = (value) => String(value || '')
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;')
    .replace(/'/g, '&#39;');

  const endpointWithBust = (url) => {
    const separator = url.includes('?') ? '&' : '?';
    return `${url}${separator}_=${Date.now()}`;
  };

  const parseJsonResponse = async (response) => JSON.parse((await response.text()).replace(/^\uFEFF/, ''));

  const formatTimestamp = (value) => {
    const normalized = String(value || '').trim().replace(' ', 'T');
    const parsed = new Date(normalized);
    if (Number.isNaN(parsed.getTime())) {
      return escapeHtml(value || '');
    }

    const pad = (part) => String(part).padStart(2, '0');
    return `${pad(parsed.getDate())}/${pad(parsed.getMonth() + 1)}/${parsed.getFullYear()} ${pad(parsed.getHours())}:${pad(parsed.getMinutes())}`;
  };

  const buildThreadMarkup = (messages) => {
    if (!messages.length) {
      return '<div class="empty-state">Chưa có tin nhắn nào. Bạn có thể chọn một câu hỏi nhanh hoặc nhập nội dung cần hỗ trợ.</div>';
    }

    return messages.map((message) => `
      <div class="chat-message ${escapeHtml(message.sender || '')}">
        <strong>${message.sender === 'patient' ? 'Bạn' : 'Hỗ trợ phòng khám'}</strong>
        <div>${escapeHtml(message.message || '').replace(/\n/g, '<br>')}</div>
        <div class="muted text-sm" style="margin-top:8px;">${formatTimestamp(message.created_at || '')}</div>
      </div>
    `).join('');
  };

  const renderThread = (payload) => {
    if (!thread) {
      return;
    }

    thread.innerHTML = buildThreadMarkup(payload.messages || []);
    if (counter) {
      counter.textContent = String(payload.message_count || 0);
    }
    requestAnimationFrame(scrollThreadToBottom);
  };

  const syncState = (open) => {
    widget.classList.toggle('open', open);
    if (launcher) {
      launcher.setAttribute('aria-expanded', open ? 'true' : 'false');
    }
    if (open) {
      hideNudge();
      requestAnimationFrame(scrollThreadToBottom);
    }
  };

  const isOpen = () => widget.classList.contains('open');

  const showNudge = (message) => {
    if (!nudge || isOpen()) {
      return;
    }

    const preview = String(message || '').trim();
    if (nudgeText) {
      nudgeText.textContent = preview.length > 96 ? `${preview.slice(0, 96)}...` : (preview || 'Hỗ trợ phòng khám vừa phản hồi.');
    }

    nudge.hidden = false;
    widget.classList.add('has-new-message');
  };

  function hideNudge() {
    if (nudge) {
      nudge.hidden = true;
    }
    widget.classList.remove('has-new-message');
  }

  if (launcher) {
    launcher.addEventListener('click', () => {
      syncState(!isOpen());
    });
  }

  if (nudgeOpenButton) {
    nudgeOpenButton.addEventListener('click', () => {
      syncState(true);
    });
  }

  if (nudgeCloseButton) {
    nudgeCloseButton.addEventListener('click', (event) => {
      event.stopPropagation();
      hideNudge();
    });
  }

  if (closeButton) {
    closeButton.addEventListener('click', () => {
      syncState(false);
    });
  }

  openButtons.forEach((button) => {
    button.addEventListener('click', (event) => {
      event.preventDefault();
      syncState(true);
    });
  });

  if (window.location.hash === '#support') {
    syncState(true);
  }

  if (thread) {
    scrollThreadToBottom();
    ['wheel', 'touchstart', 'touchmove'].forEach((eventName) => {
      thread.addEventListener(eventName, (event) => {
        event.stopPropagation();
      }, { passive: true });
    });
  }

  const loadThread = async () => {
    if (!endpoint) {
      return;
    }
    if (fileDialogGuard.isActive()) {
      return;
    }

    const response = await fetch(endpointWithBust(endpoint), {
      credentials: 'same-origin',
      cache: 'no-store',
      headers: {
        'Accept': 'application/json',
        'Cache-Control': 'no-cache',
        'X-Requested-With': 'XMLHttpRequest',
      },
    });

    if (!response.ok) {
      return;
    }

    const payload = await parseJsonResponse(response);
    const nextLatestChatId = Number(payload.latest_chat_id || 0);
    const messages = payload.messages || [];
    const incomingMessages = messages.filter((message) => Number(message.id || 0) > latestChatId && message.sender !== 'patient');
    const hasIncoming = nextLatestChatId > latestChatId && incomingMessages.length > 0;

    renderThread(payload);

    if (hasIncoming) {
      const newestIncoming = incomingMessages[incomingMessages.length - 1];
      showNudge(newestIncoming ? newestIncoming.message : '');
    }

    latestChatId = nextLatestChatId;
    widget.setAttribute('data-chat-latest-id', String(latestChatId));
  };

  loadThread().catch(() => {});
  window.setInterval(() => {
    if (!fileDialogGuard.isActive()) {
      loadThread().catch(() => {});
    }
  }, 5000);
});

document.addEventListener('DOMContentLoaded', () => {
  const supportProbe = document.querySelector('[data-admin-support-endpoint]');
  if (!supportProbe) {
    return;
  }

  const endpoint = supportProbe.getAttribute('data-admin-support-endpoint');
  if (!endpoint) {
    return;
  }

  const chatLink = supportProbe.getAttribute('data-admin-chat-link') || '';
  const csrfToken = supportProbe.getAttribute('data-admin-support-csrf') || '';
  let activeToast = document.querySelector('.admin-alert-toast');

  const escapeHtml = (value) => String(value || '')
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;')
    .replace(/'/g, '&#39;');

  const endpointWithBust = () => {
    const separator = endpoint.includes('?') ? '&' : '?';
    return `${endpoint}${separator}_=${Date.now()}`;
  };

  const parseJsonResponse = async (response) => JSON.parse((await response.text()).replace(/^\uFEFF/, ''));

  const postAction = async (payload) => {
    payload._csrf = csrfToken;
    const response = await fetch(endpointWithBust(), {
      method: 'POST',
      headers: {
        'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8',
        'X-Requested-With': 'XMLHttpRequest',
        'Cache-Control': 'no-cache',
      },
      cache: 'no-store',
      body: new URLSearchParams(payload).toString(),
      credentials: 'same-origin',
    });

    if (!response.ok) {
      throw new Error('request_failed');
    }

    return parseJsonResponse(response);
  };

  const dismissNotice = async (latestChatId) => {
    await postAction({
      action: 'dismiss',
      latest_chat_id: String(latestChatId || 0),
    });

    if (activeToast) {
      activeToast.remove();
      activeToast = null;
    }
  };

  const replyToPatient = async (patientId, latestChatId, message) => {
    await postAction({
      action: 'reply',
      patient_id: String(patientId || 0),
      latest_chat_id: String(latestChatId || 0),
      message,
    });
  };

  const buildMessageItems = (messages) => messages.map((message) => {
    const when = escapeHtml(message.created_at || '');
    const phone = escapeHtml(message.phone || '');
    const patientId = String(message.patient_id || 0);
    const latestChatId = String(message.id || 0);

    return `
      <div class="admin-alert-item">
        <strong>${escapeHtml(message.full_name || '')} - ${escapeHtml(message.cccd || '')}</strong>
        <div>${escapeHtml(message.message || '').replace(/\n/g, '<br>')}</div>
        <div class="muted text-sm" style="margin-top:6px;">S\u0110T: ${phone} | ${when}</div>
        <div class="grid" style="margin-top:10px;">
          <textarea data-support-reply-input="${patientId}" placeholder="Nh\u1eadp tr\u1ea3 l\u1eddi cho b\u1ec7nh nh\u00e2n"></textarea>
          <div class="actions">
            <button type="button" class="btn" data-support-reply-send="true" data-patient-id="${patientId}" data-latest-chat-id="${latestChatId}">G\u1eedi tr\u1ea3 l\u1eddi</button>
          </div>
        </div>
      </div>
    `;
  }).join('');

  const renderToast = (payload) => {
    if (!payload.has_new) {
      if (activeToast) {
        activeToast.remove();
        activeToast = null;
      }
      return;
    }

    if (!activeToast) {
      activeToast = document.createElement('div');
      activeToast.className = 'admin-alert-toast';
      document.body.appendChild(activeToast);
    }

    activeToast.innerHTML = `
      <h3>C\u00f3 ${payload.unread_count || 0} tin nh\u1eafn h\u1ed7 tr\u1ee3 m\u1edbi</h3>
      <div class="muted">B\u1ec7nh nh\u00e2n v\u1eeba g\u1eedi c\u00e2u h\u1ecfi h\u1ed7 tr\u1ee3. H\u00e3y ki\u1ec3m tra v\u00e0 ph\u1ea3n h\u1ed3i s\u1edbm.</div>
      <div class="admin-alert-list">${buildMessageItems(payload.messages || [])}</div>
      <div class="actions">
        <button type="button" class="btn" data-dismiss-support-toast="true">\u0110\u00e3 xem</button>
        ${chatLink ? `<a class="btn btn-secondary" href="${chatLink}">M\u1edf nh\u1eadt k\u00fd chat</a>` : ''}
      </div>
    `;

    const dismissButton = activeToast.querySelector('[data-dismiss-support-toast="true"]');
    if (dismissButton) {
      dismissButton.addEventListener('click', () => {
        dismissNotice(payload.latest_chat_id || 0).catch(() => {});
      }, { once: true });
    }

    activeToast.querySelectorAll('[data-support-reply-send="true"]').forEach((button) => {
      button.addEventListener('click', async () => {
        const patientId = button.getAttribute('data-patient-id') || '0';
        const latestChatId = button.getAttribute('data-latest-chat-id') || '0';
        const input = activeToast.querySelector(`[data-support-reply-input="${patientId}"]`);
        const message = input ? input.value.trim() : '';
        if (!message) {
          return;
        }

        button.disabled = true;
        try {
          await replyToPatient(patientId, latestChatId, message);
          await dismissNotice(latestChatId);
        } catch (_) {
          button.disabled = false;
        }
      });
    });
  };

  const loadNotice = async () => {
    if (fileDialogGuard.isActive()) {
      return;
    }

    const response = await fetch(endpointWithBust(), {
      credentials: 'same-origin',
      cache: 'no-store',
      headers: {
        'Accept': 'application/json',
        'Cache-Control': 'no-cache',
        'X-Requested-With': 'XMLHttpRequest',
      },
    });
    if (!response.ok) {
      return;
    }

    const payload = await parseJsonResponse(response);
    renderToast(payload);
  };

  loadNotice().catch(() => {});
  window.setInterval(() => {
    if (!fileDialogGuard.isActive()) {
      loadNotice().catch(() => {});
    }
  }, 5000);
});

document.addEventListener('DOMContentLoaded', () => {
  // ── TAB SWITCHING ──
  const tabs = document.querySelectorAll('.tab-bar .tab-btn, .admin-tabs .tab-btn, .tab-bar-modern .tab-btn-modern');
  const panels = document.querySelectorAll('.tab-content');
  
  const switchTab = (id) => {
    panels.forEach(p => p.classList.remove('tab-active'));
    tabs.forEach(t => t.classList.remove('active'));
    const el = document.getElementById(id);
    if (el) el.classList.add('tab-active');
    const btn = document.querySelector(`.tab-btn[data-target="${id}"], .tab-btn-modern[data-tab="${id}"]`);
    if (btn) btn.classList.add('active');
    try { history.replaceState(null, '', `#${id}`); } catch(e) {}
  };

  tabs.forEach(b => {
    b.addEventListener('click', function(e) { 
      const target = this.getAttribute('data-target') || this.getAttribute('data-tab');
      if (target && document.getElementById(target)) {
        e.preventDefault();
        switchTab(target);
      }
    });
  });

  const h = location.hash;
  const path = window.location.pathname.toLowerCase();
  const isAuthPage = path.includes('login') || path.includes('register') || path === '/' || path.endsWith('/index.php') || path === '';

  if (isAuthPage) {
    // On auth/home pages, always strip the hash to keep the URL extremely clean!
    if (window.history.replaceState) {
      window.history.replaceState(null, null, window.location.pathname);
    }
  }

  if (h) {
    const targetElement = document.getElementById(h.slice(1));
    if (targetElement && !isAuthPage) {
      switchTab(h.slice(1));
    } else {
      // If hash target does not exist or we are on an auth page, clean it from the URL
      if (window.history.replaceState && !isAuthPage) {
        window.history.replaceState(null, null, window.location.pathname);
      }
      activateFirstTab();
    }
  } else {
    activateFirstTab();
  }

  function activateFirstTab() {
    const activePanel = document.querySelector('.tab-content.tab-active');
    if (activePanel) {
      const btn = document.querySelector(`.tab-btn[data-target="${activePanel.id}"], .tab-btn-modern[data-tab="${activePanel.id}"]`);
      if (btn) btn.classList.add('active');
    }
  }

  // ── MODAL OPEN/CLOSE ──
  const openModal = (id) => {
    const m = document.getElementById(id);
    if (m) { m.classList.add('open'); document.body.style.overflow = 'hidden'; }
  };

  const closeModal = (id) => {
    const m = document.getElementById(id);
    if (m) { m.classList.remove('open'); document.body.style.overflow = ''; }
  };

  document.querySelectorAll('.modal-overlay').forEach(ov => {
    ov.addEventListener('click', (e) => { if (e.target === ov) closeModal(ov.id); });
  });

  document.querySelectorAll('.modal-close').forEach(b => {
    b.addEventListener('click', function() {
      const ov = this.closest('.modal-overlay');
      if (ov) closeModal(ov.id);
    });
  });

  document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') {
      document.querySelectorAll('.modal-overlay.open').forEach(m => closeModal(m.id));
    }
  });

  // ── FILL FORM ──
  const fillForm = (formId, data) => {
    const form = document.getElementById(formId);
    if (!form) return;
    Object.keys(data).forEach(k => {
      const el = form.querySelector(`[name="${k}"]`);
      if (!el) return;
      if (el.type === 'checkbox') { el.checked = !!parseInt(data[k], 10); }
      else { el.value = data[k] != null ? data[k] : ''; }
    });
    form.querySelectorAll('[data-perm]').forEach(cb => {
      const p = cb.getAttribute('data-perm');
      if (data[p] !== undefined) cb.checked = !!parseInt(data[p], 10);
    });
  };

  // ── DATA-ATTRIBUTE: [data-open-modal] ──
  document.addEventListener('click', (e) => {
    const btn = e.target.closest('[data-open-modal]');
    if (!btn) return;
    const modalId   = btn.getAttribute('data-open-modal');
    const fillId    = btn.getAttribute('data-fill-form');
    const fillRaw   = btn.getAttribute('data-fill');
    const photoSrc  = btn.getAttribute('data-photo-src');
    const photoTgt  = btn.getAttribute('data-photo-target');
    
    if (fillId && fillRaw) {
      try { fillForm(fillId, JSON.parse(fillRaw)); } catch(err) {}
    }
    if (photoSrc && photoTgt) {
      const img = document.getElementById(photoTgt);
      if (img) img.src = photoSrc;
    }
    openModal(modalId);
  });

  // ── DATA-ATTRIBUTE: [data-confirm] on forms ──
  document.addEventListener('submit', (e) => {
    const msg = e.target.getAttribute('data-confirm');
    if (msg && !confirm(msg)) e.preventDefault();
  });

  // ── DATA-ATTRIBUTE: [data-autosubmit] checkboxes ──
  document.addEventListener('change', (e) => {
    if (e.target.getAttribute('data-autosubmit')) {
      e.target.closest('form').submit();
    }
  });

  // ── DATA-ATTRIBUTE: [data-reply-modal] ──
  document.addEventListener('click', (e) => {
    const btn = e.target.closest('[data-reply-modal]');
    if (!btn) return;
    const patId  = btn.getAttribute('data-patient-id');
    const chatId = btn.getAttribute('data-chat-id');
    const name   = btn.getAttribute('data-patient-name');
    
    const pidEl  = document.getElementById('replyChatPatId');
    const cidEl  = document.getElementById('replyChatLatestId');
    const titEl  = document.getElementById('replyChatTitle');
    const msgEl  = document.getElementById('replyChatMsg');
    
    if (pidEl) pidEl.value = patId;
    if (cidEl) cidEl.value = chatId;
    if (titEl) titEl.textContent = `Trả lời cho ${name}`;
    if (msgEl) msgEl.value = '';
    openModal('modalReplyChat');
  });

  // ── VISUAL-ONLY TOGGLE ──
  document.addEventListener('click', (e) => {
    const tog = e.target.closest('.tog:not(.tog-wrap)');
    if (tog) tog.classList.toggle('on');
  });

  // ── CLIENT-SIDE SEARCH ──
  const initSearch = (inputId, tbodyId) => {
    const inp = document.getElementById(inputId);
    const tb  = document.getElementById(tbodyId);
    if (!inp || !tb) return;
    inp.addEventListener('input', function() {
      const q = this.value.toLowerCase();
      tb.querySelectorAll('tr').forEach(r => {
        r.style.display = r.textContent.toLowerCase().includes(q) ? '' : 'none';
      });
    });
  };
  
  initSearch('searchStaff',    'staffTbody');
  initSearch('searchDoctors',  'doctorsTbody');
  initSearch('searchPatients', 'patientsTbody');
  initSearch('searchReplies',  'repliesTbody');

  // ── IMAGE PREVIEW ──
  document.addEventListener('change', (e) => {
    const inp = e.target.closest('.img-preview-input');
    if (!inp) return;
    const prev = document.getElementById(inp.dataset.preview);
    if (prev && inp.files && inp.files[0]) {
      prev.src = URL.createObjectURL(inp.files[0]);
      prev.classList.add('vis');
    }
  });

  // ── LOG SEARCH ──
  const logInp = document.getElementById('logSearchInp');
  if (logInp) {
    logInp.addEventListener('input', function() {
      const q = this.value.toLowerCase();
      document.querySelectorAll('#tab-logs .log-item').forEach(item => {
        item.style.display = item.textContent.toLowerCase().includes(q) ? '' : 'none';
      });
    });
  }
});
