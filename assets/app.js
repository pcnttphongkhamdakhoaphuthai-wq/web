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

const onReady = (fn) => {
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', fn);
  } else {
    fn();
  }
};

onReady(() => {
  const widget = document.querySelector('[data-floating-chat]');
  if (!widget) {
    return;
  }

  const launcher = widget.querySelector('[data-chat-toggle="true"]');
  const closeButtons = widget.querySelectorAll('[data-chat-close], .floating-chat-close');
  const thread = widget.querySelector('[data-chat-thread]');
  const counter = widget.querySelector('.floating-chat-count');
  const nudge = widget.querySelector('[data-chat-nudge]');
  const nudgeText = widget.querySelector('[data-chat-nudge-text]');
  const nudgeOpenButton = widget.querySelector('[data-chat-nudge-open="true"]');
  const nudgeCloseButton = widget.querySelector('[data-chat-nudge-close="true"]');
  const openButtons = document.querySelectorAll('[data-chat-open="true"]');
  const endpoint = widget.getAttribute('data-chat-endpoint');
  let latestChatId = Number(widget.getAttribute('data-chat-latest-id') || '0');

  const scrollThreadToBottom = (smooth = true) => {
    if (!thread) {
      return;
    }
    const scrollToBottomDirect = () => {
      try {
        if (smooth && typeof thread.scrollTo === 'function') {
          thread.scrollTo({
            top: thread.scrollHeight,
            behavior: 'smooth',
          });
        } else {
          thread.scrollTop = thread.scrollHeight;
        }
      } catch (_) {
        thread.scrollTop = thread.scrollHeight;
      }
    };

    scrollToBottomDirect();
    requestAnimationFrame(scrollToBottomDirect);
    setTimeout(scrollToBottomDirect, 120);
    setTimeout(scrollToBottomDirect, 300);
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

  const formatMarkdown = (text) => {
    if (!text) return '';
    let html = escapeHtml(text);

    function formatInlineMarkdown(str) {
      if (!str) return '';
      let res = str;
      res = res.replace(/\[([^\]]+)\]\((https?:\/\/[^\s)]+)\)/g, '<a href="$2" target="_blank" rel="noopener" class="aichat-link">$1</a>');
      res = res.replace(/(?<!href=["'])(https?:\/\/[^\s<)]+)/gi, '<a href="$1" target="_blank" rel="noopener" class="aichat-link">$1</a>');
      res = res.replace(/`([^`]+)`/g, '<code class="aichat-code">$1</code>');
      res = res.replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>');
      res = res.replace(/(?<!\*)\*(?!\*)(.*?)(?<!\*)\*(?!\*)/g, '<em>$1</em>');
      return res;
    }

    function formatTableCell(rawVal, isHeader, isPriceCol, isBhytCol) {
      const trimmed = (rawVal || '').trim();
      if (isHeader) {
        return formatInlineMarkdown(trimmed);
      }
      if (!trimmed) return '&nbsp;';

      const bhytPositivePattern = /^(có\s*bhyt|được\s*áp\s*dụng|áp\s*dụng\s*bhyt|có\s*áp\s*dụng|bhyt(\s+đúng\s+tuyến)?|hỗ\s*trợ\s*bhyt|đúng\s*tuyến|có\s*hỗ\s*trợ.*)$/i;
      const bhytNegativePattern = /^(không\s*bhyt|không\s*áp\s*dụng|chưa\s*áp\s*dụng|không\s*hỗ\s*trợ.*|không.*|chưa.*|tự\s*túc.*|tự\s*trả.*|tự\s*nguyện.*)$/i;
      const checkIcon = '<svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" style="display:inline-block;vertical-align:-1px;margin-right:3px"><polyline points="20 6 9 17 4 12"/></svg>';

      if (bhytPositivePattern.test(trimmed) || (isBhytCol && /^(có.*|áp\s*dụng.*|được.*)$/i.test(trimmed))) {
        return '<span class="badge-bhyt-yes aichat-badge-bhyt">' + checkIcon + formatInlineMarkdown(trimmed) + '</span>';
      }

      if (bhytNegativePattern.test(trimmed) || (isBhytCol && /^(không.*|chưa.*|tự\s*túc.*|tự\s*trả.*|tự\s*nguyện.*)$/i.test(trimmed))) {
        return '<span class="badge-bhyt-no aichat-badge-nobhyt">' + formatInlineMarkdown(trimmed) + '</span>';
      }

      let formatted = formatInlineMarkdown(trimmed);
      formatted = formatted.replace(/\b(Có\s+BHYT|Được\s+áp\s+dụng|Áp\s+dụng\s+BHYT|Có\s+hỗ\s+trợ)\b/gi, '<span class="badge-bhyt-yes aichat-badge-bhyt">' + checkIcon + '$1</span>');
      return formatted;
    }

    // 1. Fenced Code blocks
    const codeBlocks = [];
    html = html.replace(/```([a-z0-9_-]*)\n([\s\S]*?)```/gi, (match, lang, code) => {
      const placeholder = '<!--AICHAT_CODEBLOCK_' + codeBlocks.length + '-->';
      codeBlocks.push('<pre class="aichat-pre"><code class="aichat-code-block">' + code.trim() + '</code></pre>');
      return placeholder;
    });

    // 2. Markdown Tables Parser
    const lines = html.split('\n');
    const processedLines = [];
    const tableBlocks = [];
    let i = 0;

    function isSeparatorRow(line) {
      if (!line) return false;
      let trimmed = line.trim();
      if (!trimmed.includes('-')) return false;
      if (trimmed.startsWith('|')) trimmed = trimmed.slice(1);
      if (trimmed.endsWith('|')) trimmed = trimmed.slice(0, -1);
      const parts = trimmed.split('|');
      if (parts.length === 0) return false;
      return parts.every((p) => /^[\s:]*-{1,}[\s:]*$/.test(p));
    }

    function isTableRow(line) {
      if (!line) return false;
      const trimmed = line.trim();
      return trimmed.length > 0 && trimmed.includes('|');
    }

    function splitCells(line) {
      let trimmed = line.trim();
      if (trimmed.startsWith('|')) trimmed = trimmed.slice(1);
      if (trimmed.endsWith('|')) trimmed = trimmed.slice(0, -1);
      return trimmed.split('|').map((c) => c.trim());
    }

    function getAlignment(sepCell) {
      const t = (sepCell || '').trim();
      const left = t.startsWith(':');
      const right = t.endsWith(':');
      if (left && right) return 'center';
      if (right) return 'right';
      if (left) return 'left';
      return '';
    }

    while (i < lines.length) {
      const currentLine = lines[i];
      const nextLine = (i + 1 < lines.length) ? lines[i + 1] : null;

      if (nextLine && isTableRow(currentLine) && isSeparatorRow(nextLine)) {
        const headerLine = currentLine;
        const sepLine = nextLine;
        const dataLines = [];
        i += 2;

        while (i < lines.length && isTableRow(lines[i]) && !isSeparatorRow(lines[i])) {
          dataLines.push(lines[i]);
          i++;
        }

        const headerCells = splitCells(headerLine);
        const sepCells = splitCells(sepLine);
        const aligns = sepCells.map(getAlignment);
        const numCols = headerCells.length;

        const priceHeaderPattern = /(giá|đơn\s*giá|thành\s*tiền|chi\s*phí|viện\s*phí|lệ\s*phí|tiền|price|cost|fee)/i;
        const bhytHeaderPattern = /(bhyt|bảo\s*hiểm|áp\s*dụng)/i;
        const priceValuePattern = /(?:\d+[.,\d]*\s*(?:vnđ|vnd|đ|k|đồng)\b|miễn\s*phí|^\s*\d{1,3}([.,]\d{3})+\s*$)/i;

        const priceCols = {};
        const bhytCols = {};

        for (let c = 0; c < numCols; c++) {
          if (priceHeaderPattern.test(headerCells[c])) {
            priceCols[c] = true;
          }
          if (bhytHeaderPattern.test(headerCells[c])) {
            bhytCols[c] = true;
          }
        }

        for (let c = 0; c < numCols; c++) {
          if (!priceCols[c] && dataLines.length > 0) {
            let matchCount = 0;
            for (let r = 0; r < dataLines.length; r++) {
              const rowCells = splitCells(dataLines[r]);
              const cellVal = (c < rowCells.length) ? rowCells[c] : '';
              if (priceValuePattern.test(cellVal)) {
                matchCount++;
              }
            }
            if (matchCount > 0 && matchCount >= dataLines.length * 0.5) {
              priceCols[c] = true;
            }
          }
        }

        let thead = '<thead><tr>';
        for (let c = 0; c < numCols; c++) {
          const classes = [];
          if (priceCols[c]) {
            classes.push('col-price text-right price-col');
          } else if (bhytCols[c] || c === 0) {
            classes.push('col-center text-center');
          } else if (aligns[c]) {
            classes.push('text-' + aligns[c]);
          }
          const clsAttr = classes.length ? ' class="' + classes.join(' ') + '"' : '';
          thead += '<th' + clsAttr + '>' + formatTableCell(headerCells[c], true, priceCols[c], bhytCols[c]) + '</th>';
        }
        thead += '</tr></thead>';

        let tbody = '<tbody>';
        for (let r = 0; r < dataLines.length; r++) {
          const rowCells = splitCells(dataLines[r]);
          tbody += '<tr>';
          for (let c = 0; c < numCols; c++) {
            const cellVal = (c < rowCells.length) ? rowCells[c] : '';
            const classes = [];
            if (priceCols[c]) {
              classes.push('col-price text-right price-col');
            } else if (bhytCols[c] || c === 0) {
              classes.push('col-center text-center');
            } else if (aligns[c]) {
              classes.push('text-' + aligns[c]);
            }
            const clsAttr = classes.length ? ' class="' + classes.join(' ') + '"' : '';
            tbody += '<td' + clsAttr + '>' + formatTableCell(cellVal, false, priceCols[c], bhytCols[c]) + '</td>';
          }
          tbody += '</tr>';
        }
        tbody += '</tbody>';

        const fullTable = '<div class="aichat-table-responsive"><table class="aichat-table">' + thead + tbody + '</table></div>';
        const placeholder = '<!--AICHAT_TABLE_' + tableBlocks.length + '-->';
        tableBlocks.push(fullTable);
        processedLines.push(placeholder);
      } else {
        processedLines.push(currentLine);
        i++;
      }
    }

    html = processedLines.join('\n');

    // 3. Lists (Ordered & Unordered)
    const listLines = html.split('\n');
    const parsedListLines = [];
    let currentListType = null;
    let currentListItems = [];

    function flushList() {
      if (!currentListType) return;
      const itemsHtml = currentListItems.map((item) => '<li>' + formatInlineMarkdown(item) + '</li>').join('');
      parsedListLines.push('<' + currentListType + ' class="aichat-' + currentListType + '">' + itemsHtml + '</' + currentListType + '>');
      currentListType = null;
      currentListItems = [];
    }

    for (let j = 0; j < listLines.length; j++) {
      const l = listLines[j];
      if (l.startsWith('<!--AICHAT_')) {
        flushList();
        parsedListLines.push(l);
        continue;
      }

      const olMatch = l.match(/^\s*(\d+)\.\s+(.+)$/);
      const ulMatch = l.match(/^\s*[•\-\*]\s+(.+)$/);

      if (olMatch) {
        if (currentListType === 'ul') flushList();
        currentListType = 'ol';
        currentListItems.push(olMatch[2]);
      } else if (ulMatch) {
        if (currentListType === 'ol') flushList();
        currentListType = 'ul';
        currentListItems.push(ulMatch[1]);
      } else {
        flushList();
        parsedListLines.push(l);
      }
    }
    flushList();

    html = parsedListLines.join('\n');

    // 4. Inline format cho phần text còn lại ngoài bảng và list
    const finalLines = html.split('\n').map((l) => {
      if (l.startsWith('<!--AICHAT_') || l.startsWith('<ol') || l.startsWith('<ul') || l.startsWith('<pre')) {
        return l;
      }
      return formatInlineMarkdown(l);
    });

    html = finalLines.join('\n');

    // 5. Line breaks \n -> <br>
    html = html.replace(/\n/g, '<br>');

    // Dọn dẹp <br> thừa xung quanh các khối block
    html = html.replace(/(?:<br>\s*)+(<!--AICHAT_(?:TABLE|CODEBLOCK)_\d+-->)/g, '$1');
    html = html.replace(/(<!--AICHAT_(?:TABLE|CODEBLOCK)_\d+-->)(?:\s*<br>)+/g, '$1');
    html = html.replace(/(?:<br>\s*)*(<\/?(?:ol|ul|li|pre)[^>]*>)(?:\s*<br>)*/g, '$1');

    // 6. Khôi phục placeholders
    for (let t = 0; t < tableBlocks.length; t++) {
      html = html.replace('<!--AICHAT_TABLE_' + t + '-->', tableBlocks[t]);
    }
    for (let k = 0; k < codeBlocks.length; k++) {
      html = html.replace('<!--AICHAT_CODEBLOCK_' + k + '-->', codeBlocks[k]);
    }

    return html;
  };

  const buildThreadMarkup = (messages) => {
    if (!messages.length) {
      return '<div class="empty-state">Chưa có tin nhắn nào. Bạn có thể chọn một câu hỏi nhanh hoặc nhập nội dung cần hỗ trợ.</div>';
    }

    return messages.map((message) => `
      <div class="chat-message ${escapeHtml(message.sender || '')}">
        <strong>${message.sender === 'patient' ? 'Bạn' : 'Hỗ trợ phòng khám'}</strong>
        <div>${formatMarkdown(message.message || '')}</div>
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
    scrollThreadToBottom(true);
  };

  const isMobileScreen = () => window.innerWidth <= 768;

  const updateBodyScrollLock = (open) => {
    if (open && isMobileScreen()) {
      document.body.classList.add('chat-modal-open');
    } else {
      document.body.classList.remove('chat-modal-open');
    }
  };

  const syncState = (open) => {
    widget.classList.toggle('open', open);
    if (launcher) {
      launcher.setAttribute('aria-expanded', open ? 'true' : 'false');
    }
    updateBodyScrollLock(open);
    if (open) {
      hideNudge();
      scrollThreadToBottom(true);
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
    launcher.addEventListener('click', (event) => {
      if (event) event.preventDefault();
      syncState(!isOpen());
    });
  }

  if (nudgeOpenButton) {
    nudgeOpenButton.addEventListener('click', (event) => {
      if (event) event.preventDefault();
      syncState(true);
    });
  }

  if (nudgeCloseButton) {
    nudgeCloseButton.addEventListener('click', (event) => {
      event.stopPropagation();
      hideNudge();
    });
  }

  closeButtons.forEach((btn) => {
    const handleClose = (event) => {
      if (event) {
        event.preventDefault();
        event.stopPropagation();
      }
      syncState(false);
    };
    btn.addEventListener('click', handleClose);
    btn.addEventListener('touchend', (event) => {
      event.preventDefault();
      handleClose(event);
    }, { passive: false });
  });

  document.addEventListener('keydown', (event) => {
    if (event.key === 'Escape' && isOpen()) {
      syncState(false);
    }
  });

  window.addEventListener('resize', () => {
    if (isOpen()) {
      updateBodyScrollLock(true);
    } else {
      document.body.classList.remove('chat-modal-open');
    }
  });

  openButtons.forEach((button) => {
    button.addEventListener('click', (event) => {
      event.preventDefault();
      syncState(true);
    });
  });

  // Global delegation dự phòng cho mọi nút mở chat nổi (đảm bảo hoạt động trên mọi trang & vị trí DOM)
  document.addEventListener('click', (event) => {
    const target = event.target.closest('[data-chat-open="true"]');
    if (target) {
      event.preventDefault();
      syncState(true);
    }
  });

  // Hỗ trợ tự cuộn và giữ trạng thái mở khi bấm câu hỏi gợi ý / gửi tin nhắn
  const quickReplyButtons = widget.querySelectorAll('.quick-replies button');
  quickReplyButtons.forEach((btn) => {
    btn.addEventListener('click', () => {
      sessionStorage.setItem('phuthai_chat_open', 'true');
      scrollThreadToBottom(true);
    });
  });

  const chatForms = widget.querySelectorAll('.floating-chat-body form');
  chatForms.forEach((form) => {
    form.addEventListener('submit', () => {
      sessionStorage.setItem('phuthai_chat_open', 'true');
      scrollThreadToBottom(true);
    });
  });

  const shouldAutoOpen = window.location.hash === '#support' || sessionStorage.getItem('phuthai_chat_open') === 'true';
  if (shouldAutoOpen) {
    sessionStorage.removeItem('phuthai_chat_open');
    syncState(true);
    setTimeout(() => scrollThreadToBottom(true), 250);
  }

  if (thread) {
    scrollThreadToBottom(false);
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
      if (isOpen()) {
        scrollThreadToBottom(true);
      }
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

onReady(() => {
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

onReady(() => {
  // ── TAB SWITCHING (Chỉ chạy khi có các phần tử tab quản trị tương ứng) ──
  const tabs = document.querySelectorAll('.tab-bar .tab-btn, .admin-tabs .tab-btn, .tab-bar-modern .tab-btn-modern');
  const panels = document.querySelectorAll('.tab-content');
  
  if (tabs.length > 0 && panels.length > 0) {
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
