<style>
/* ===== AI Chat Inline Styles ===== */
:root {
  --aichat-primary: #2563eb;
  --aichat-primary-dark: #1d4ed8;
  --aichat-bg: #ffffff;
  --aichat-bg-msg: #f8fafc;
  --aichat-border: #e2e8f0;
  --aichat-text: #0f172a;
  --aichat-muted: #64748b;
  --aichat-radius: 16px;
}

#aichat-inline-container {
  display: flex;
  flex-direction: column;
  height: 680px;
  background: var(--aichat-bg);
  font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
}

.aichat-header {
  background: linear-gradient(135deg, var(--aichat-primary), #4f46e5);
  padding: 16px 20px;
  display: flex;
  align-items: center;
  gap: 12px;
  flex-shrink: 0;
}
.aichat-avatar-ring {
  width: 44px;
  height: 44px;
  border-radius: 50%;
  background: rgba(255,255,255,0.2);
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
}
.aichat-header-name {
  color: white;
  font-weight: 700;
  font-size: 16px;
  line-height: 1.2;
}
.aichat-header-status {
  display: flex;
  align-items: center;
  gap: 6px;
  color: rgba(255,255,255,0.9);
  font-size: 13px;
  margin-top: 4px;
}
.aichat-status-dot {
  width: 8px;
  height: 8px;
  border-radius: 50%;
  background: #4ade80;
  box-shadow: 0 0 0 2px rgba(74,222,128,0.3);
  animation: pulse-dot 2s infinite;
}
@keyframes pulse-dot {
  0%, 100% { box-shadow: 0 0 0 2px rgba(74,222,128,0.3); }
  50%       { box-shadow: 0 0 0 5px rgba(74,222,128,0); }
}

#aichat-messages {
  flex: 1;
  overflow-y: auto;
  padding: 20px;
  display: flex;
  flex-direction: column;
  gap: 12px;
  background: var(--aichat-bg-msg);
  scroll-behavior: smooth;
}
#aichat-messages::-webkit-scrollbar { width: 6px; }
#aichat-messages::-webkit-scrollbar-track { background: transparent; }
#aichat-messages::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 10px; }

.aichat-msg {
  display: flex;
  flex-direction: column;
  animation: aichat-fade-in 0.25s ease;
}
@keyframes aichat-fade-in {
  from { opacity: 0; transform: translateY(8px); }
  to   { opacity: 1; transform: translateY(0); }
}

.aichat-msg--bot { align-items: flex-start; }
.aichat-msg--user { align-items: flex-end; }

.aichat-msg-bubble {
  max-width: 85%;
  min-width: 0;
  box-sizing: border-box;
  padding: 12px 16px;
  border-radius: 18px;
  font-size: 14.5px;
  line-height: 1.5;
  word-break: break-word;
}
.aichat-msg--bot .aichat-msg-bubble {
  background: white;
  color: var(--aichat-text);
  border: 1px solid var(--aichat-border);
  border-bottom-left-radius: 4px;
  box-shadow: 0 1px 3px rgba(0,0,0,0.05);
}
.aichat-msg--user .aichat-msg-bubble {
  background: linear-gradient(135deg, var(--aichat-primary), #4f46e5);
  color: white;
  border-bottom-right-radius: 4px;
}
.aichat-msg-bubble ul { margin: 8px 0 0 0; padding-left: 20px; }
.aichat-msg-bubble strong { font-weight: 600; }
.aichat-link {
  color: var(--aichat-primary);
  text-decoration: underline;
  font-weight: 600;
  word-break: break-all;
}
.aichat-link:hover {
  color: var(--aichat-primary-dark);
}

/* ===== AI Chat Table & Responsive Medical Price Table ===== */
.aichat-msg--bot .aichat-msg-bubble:has(.aichat-table-responsive) {
  max-width: 96%;
}

.aichat-table-responsive {
  width: 100%;
  max-width: 100%;
  overflow-x: auto;
  -webkit-overflow-scrolling: touch;
  margin: 10px 0;
  border-radius: 10px;
  border: 1px solid rgba(14, 165, 233, 0.2);
  box-shadow: 0 2px 8px rgba(14, 165, 233, 0.08);
  background: #ffffff;
  display: block;
  box-sizing: border-box;
}

.aichat-table-responsive::-webkit-scrollbar {
  height: 5px;
}
.aichat-table-responsive::-webkit-scrollbar-track {
  background: rgba(240, 249, 255, 0.8);
  border-radius: 0 0 10px 10px;
}
.aichat-table-responsive::-webkit-scrollbar-thumb {
  background: #bae6fd;
  border-radius: 4px;
}
.aichat-table-responsive::-webkit-scrollbar-thumb:hover {
  background: #7dd3fc;
}

.aichat-table {
  width: 100%;
  min-width: 440px;
  border-collapse: separate;
  border-spacing: 0;
  font-size: 13px;
  font-family: inherit;
  color: #1e293b;
  text-align: left;
  line-height: 1.5;
}

.aichat-table th {
  background: linear-gradient(135deg, #0284c7, #0369a1);
  color: #ffffff;
  padding: 8px 12px;
  font-weight: 600;
  text-align: left;
  border-bottom: 2px solid #0284c7;
  white-space: nowrap;
}

.aichat-table th:first-child {
  border-top-left-radius: 9px;
}
.aichat-table th:last-child {
  border-top-right-radius: 9px;
}

.aichat-table td {
  padding: 8px 12px;
  border-bottom: 1px solid rgba(0, 0, 0, 0.06);
  vertical-align: middle;
  color: #334155;
}

.aichat-table tbody tr:last-child td {
  border-bottom: none;
}
.aichat-table tbody tr:last-child td:first-child {
  border-bottom-left-radius: 9px;
}
.aichat-table tbody tr:last-child td:last-child {
  border-bottom-right-radius: 9px;
}

/* Zebra striping */
.aichat-table tbody tr:nth-child(even) {
  background: rgba(240, 249, 255, 0.6);
}

/* Hover effect */
.aichat-table tbody tr {
  transition: background-color 0.15s ease;
}
.aichat-table tbody tr:hover {
  background-color: rgba(224, 242, 254, 0.7);
}

/* Căn phải cột tiền tệ */
.aichat-table .col-price,
.aichat-table .price-col {
  text-align: right;
  font-weight: 600;
  color: #0284c7;
  white-space: nowrap;
  font-variant-numeric: tabular-nums;
}

.aichat-table th.col-price,
.aichat-table th.price-col {
  text-align: right;
  color: #ffffff;
}

.aichat-table .text-left { text-align: left; }
.aichat-table .text-center, .aichat-table .col-center { text-align: center; }
.aichat-table .text-right { text-align: right; }
.aichat-table th.text-center, .aichat-table th.col-center { text-align: center; }

/* Badge BHYT */
.badge-bhyt-yes,
.aichat-badge-bhyt {
  background: #dcfce7;
  color: #166534;
  padding: 2px 6px;
  border-radius: 4px;
  font-size: 11px;
  font-weight: 600;
  display: inline-flex;
  align-items: center;
  gap: 3px;
  white-space: nowrap;
  line-height: 1.3;
}

.badge-bhyt-no,
.aichat-badge-nobhyt {
  background: #f1f5f9;
  color: #64748b;
  padding: 2px 6px;
  border-radius: 4px;
  font-size: 11px;
  display: inline-flex;
  align-items: center;
  gap: 3px;
  white-space: nowrap;
  line-height: 1.3;
}
.aichat-msg-bubble ol,
.aichat-msg-bubble ul {
  margin: 6px 0;
  padding-left: 20px;
}
.aichat-msg-bubble li {
  margin-bottom: 3px;
}
.aichat-code {
  font-family: SFMono-Regular, Consolas, Menlo, monospace;
  font-size: 13px;
  background: #f1f5f9;
  color: #e11d48;
  padding: 2px 5px;
  border-radius: 4px;
  border: 1px solid #e2e8f0;
}
.aichat-pre {
  background: #0f172a;
  color: #f8fafc;
  padding: 12px;
  border-radius: 8px;
  overflow-x: auto;
  font-size: 12.5px;
  margin: 8px 0;
}

.aichat-msg-time {
  font-size: 12px;
  color: var(--aichat-muted);
  margin-top: 6px;
  padding: 0 4px;
}

.aichat-typing .aichat-msg-bubble {
  padding: 14px 18px;
  min-width: 64px;
}
.aichat-dots {
  display: flex;
  gap: 5px;
  align-items: center;
}
.aichat-dots span {
  width: 8px;
  height: 8px;
  border-radius: 50%;
  background: #94a3b8;
  animation: aichat-bounce 1.3s infinite;
}
.aichat-dots span:nth-child(2) { animation-delay: 0.15s; }
.aichat-dots span:nth-child(3) { animation-delay: 0.3s; }
@keyframes aichat-bounce {
  0%, 60%, 100% { transform: translateY(0); }
  30%            { transform: translateY(-7px); }
}

.aichat-quick-replies {
  padding: 10px 16px 12px;
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
  background: var(--aichat-bg-msg);
  border-top: 1px solid var(--aichat-border);
  flex-shrink: 0;
}
.aichat-quick-btn {
  background: white;
  border: 1px solid var(--aichat-border);
  border-radius: 20px;
  padding: 6px 14px;
  font-size: 13px;
  cursor: pointer;
  color: var(--aichat-primary);
  font-weight: 500;
  transition: all 0.15s;
}
.aichat-quick-btn:hover {
  background: var(--aichat-primary);
  color: white;
  border-color: var(--aichat-primary);
}

.aichat-input-area {
  display: flex;
  align-items: flex-end;
  gap: 12px;
  padding: 14px 16px;
  border-top: 1px solid var(--aichat-border);
  background: white;
  flex-shrink: 0;
}
#aichat-input {
  flex: 1;
  border: 1px solid var(--aichat-border);
  border-radius: 24px;
  padding: 12px 18px;
  font-size: 15px;
  font-family: inherit;
  resize: none;
  outline: none;
  max-height: 120px;
  line-height: 1.4;
  transition: border-color 0.15s;
  color: var(--aichat-text);
  background: #f8fafc;
}
#aichat-input:focus {
  border-color: var(--aichat-primary);
  background: white;
}
#aichat-send-btn {
  background: var(--aichat-primary);
  color: white;
  border: none;
  width: 44px;
  height: 44px;
  border-radius: 50%;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: background 0.15s, transform 0.1s;
  flex-shrink: 0;
}
#aichat-send-btn:hover { background: var(--aichat-primary-dark); }
#aichat-send-btn:active { transform: scale(0.95); }
#aichat-send-btn svg { margin-left: -2px; }

.aichat-footer-note {
  text-align: center;
  font-size: 12.5px;
  font-weight: 600;
  color: #334155;
  padding: 0 0 10px 0;
  background: white;
}

@media (max-width: 640px) {
  .aichat-msg-bubble {
    max-width: 94%;
    padding: 10px 13px;
    font-size: 14px;
  }
  .aichat-msg--bot .aichat-msg-bubble:has(.aichat-table-responsive) {
    max-width: 100%;
    padding: 10px 8px;
  }
  .aichat-table {
    min-width: 420px;
    font-size: 12.5px;
  }
  .aichat-table th,
  .aichat-table td {
    padding: 7px 10px;
  }
}
</style>

<div id="aichat-inline-container">
  <div class="aichat-header">
    <div class="aichat-avatar-ring">
      <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2a2 2 0 0 1 2 2v1h4a2 2 0 0 1 2 2v1h1a2 2 0 0 1 2 2v2a2 2 0 0 1-2 2h-1v4a2 2 0 0 1-2 2h-4v1a2 2 0 0 1-2 2h-2a2 2 0 0 1-2-2v-1H7a2 2 0 0 1-2-2v-4H4a2 2 0 0 1-2-2v-2a2 2 0 0 1 2-2h1V7a2 2 0 0 1 2-2h4V4a2 2 0 0 1 2-2z"></path><circle cx="9" cy="11" r="1"></circle><circle cx="15" cy="11" r="1"></circle><path d="M9 15h6"></path></svg>
    </div>
    <div>
      <div class="aichat-header-name">Trợ lý AI Phòng khám</div>
      <div class="aichat-header-status"><span class="aichat-status-dot"></span> Đang hoạt động</div>
    </div>
  </div>

  <div id="aichat-messages">
    <div class="aichat-msg aichat-msg--bot">
      <div class="aichat-msg-bubble">
        Xin chào! Tôi là trợ lý AI của <strong>Phòng khám đa khoa Phú Thái</strong>. 👋<br><br>
        Tôi có thể giúp bạn:<br>
        <ul>
          <li>📋 Tra cứu <strong>thủ tục khám bệnh</strong></li>
          <li>💰 Xem <strong>bảng giá dịch vụ</strong></li>
          <li>📍 Thông tin <strong>liên hệ</strong></li>
        </ul>
        Bạn muốn hỏi gì ạ?
      </div>
      <div class="aichat-msg-time">Vừa xong</div>
    </div>
  </div>

  <div class="aichat-quick-replies" id="aichat-quick-replies">
    <button onclick="aichat.sendQuick(this)" class="aichat-quick-btn">💰 Bảng giá dịch vụ</button>
    <button onclick="aichat.quickDoc(this)" class="aichat-quick-btn">📋 Thủ tục khám bệnh</button>
    <button onclick="aichat.quickContact(this)" class="aichat-quick-btn">📍 Địa chỉ & Liên hệ</button>
    <button onclick="aichat.quickTime(this)" class="aichat-quick-btn">🕐 Giờ làm việc</button>
  </div>

  <div class="aichat-input-area">
    <textarea id="aichat-input" rows="1" placeholder="Nhập câu hỏi của bạn..." aria-label="Nhập tin nhắn" maxlength="1000"></textarea>
    <button id="aichat-send-btn" onclick="aichat.send()" aria-label="Gửi tin nhắn" title="Gửi (Enter)">
      <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="22" y1="2" x2="11" y2="13"/><polygon points="22 2 15 22 11 13 2 9 22 2"/></svg>
    </button>
  </div>
  <div class="aichat-footer-note">AI có thể mắc lỗi · Liên hệ hotline để xác nhận</div>
</div>

<script>
var aichat = (function() {
  var history = [];
  var isRequesting = false;

  function sendQuick(btn) {
    var text = btn.innerText.replace(/^[^\s]+\s/, ''); // Remove emoji
    sendMsg(text);
  }
  
  function quickDoc(btn) { sendMsg("Thủ tục khám bệnh tại phòng khám như thế nào?"); }
  function quickContact(btn) { sendMsg("Địa chỉ và số điện thoại liên hệ của phòng khám?"); }
  function quickTime(btn) { sendMsg("Giờ làm việc của phòng khám?"); }

  function send() {
    var input = document.getElementById('aichat-input');
    var text = input.value.trim();
    if (!text || isRequesting) return;
    
    input.value = '';
    input.style.height = 'auto';
    sendMsg(text);
  }

  function sendMsg(text) {
    if (isRequesting) return;
    isRequesting = true;
    
    appendMessage('user', escapeHtml(text), false);
    var typingId = showTyping();

    var payload = {
      message: text,
      history: history
    };

    fetch('/api_chat_ai.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(payload)
    })
    .then(res => res.json())
    .then(data => {
      removeTyping(typingId);
      isRequesting = false;
      if (data && data.reply) {
        history.push({ role: 'user', content: text });
        history.push({ role: 'model', content: data.reply });
        appendMessage('bot', formatMarkdown(data.reply), true);
      } else {
        appendMessage('bot', 'Xin lỗi, hệ thống không thể phản hồi lúc này.', false);
      }
    })
    .catch(err => {
      removeTyping(typingId);
      isRequesting = false;
      appendMessage('bot', 'Lỗi kết nối đến máy chủ. Vui lòng thử lại sau.', false);
    });
  }

  function formatMarkdown(text) {
    if (!text) return '';
    var html = escapeHtml(text);

    function formatInlineMarkdown(str) {
      if (!str) return '';
      var res = str;
      // Convert markdown links: [label](url)
      res = res.replace(/\[([^\]]+)\]\((https?:\/\/[^\s)]+)\)/g, '<a href="$2" target="_blank" rel="noopener" class="aichat-link">$1</a>');
      // Convert raw URLs: http/https (excluding those in href)
      res = res.replace(/(?<!href=["'])(https?:\/\/[^\s<)]+)/gi, '<a href="$1" target="_blank" rel="noopener" class="aichat-link">$1</a>');
      // Inline code `code`
      res = res.replace(/`([^`]+)`/g, '<code class="aichat-code">$1</code>');
      // Bold **text**
      res = res.replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>');
      // Italic *text*
      res = res.replace(/\*([^\*]+)\*/g, '<em>$1</em>');
      return res;
    }

    function formatTableCell(rawVal, isHeader, isPriceCol, isBhytCol) {
      var trimmed = (rawVal || '').trim();
      if (isHeader) {
        return formatInlineMarkdown(trimmed);
      }
      if (!trimmed) return '&nbsp;';

      var bhytPositivePattern = /^(có\s*bhyt|được\s*áp\s*dụng|áp\s*dụng\s*bhyt|có\s*áp\s*dụng|bhyt(\s+đúng\s+tuyến)?|hỗ\s*trợ\s*bhyt|đúng\s*tuyến|có\s*hỗ\s*trợ.*)$/i;
      var bhytNegativePattern = /^(không\s*bhyt|không\s*áp\s*dụng|chưa\s*áp\s*dụng|không\s*hỗ\s*trợ.*|không.*|chưa.*|tự\s*túc.*|tự\s*trả.*|tự\s*nguyện.*)$/i;
      var checkIcon = '<svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" style="display:inline-block;vertical-align:-1px;margin-right:3px"><polyline points="20 6 9 17 4 12"/></svg>';

      if (bhytPositivePattern.test(trimmed) || (isBhytCol && /^(có.*|áp\s*dụng.*|được.*)$/i.test(trimmed))) {
        return '<span class="badge-bhyt-yes aichat-badge-bhyt">' + checkIcon + formatInlineMarkdown(trimmed) + '</span>';
      }

      if (bhytNegativePattern.test(trimmed) || (isBhytCol && /^(không.*|chưa.*|tự\s*túc.*|tự\s*trả.*|tự\s*nguyện.*)$/i.test(trimmed))) {
        return '<span class="badge-bhyt-no aichat-badge-nobhyt">' + formatInlineMarkdown(trimmed) + '</span>';
      }

      var formatted = formatInlineMarkdown(trimmed);
      formatted = formatted.replace(/\b(Có\s+BHYT|Được\s+áp\s+dụng|Áp\s+dụng\s+BHYT|Có\s+hỗ\s+trợ)\b/gi, '<span class="badge-bhyt-yes aichat-badge-bhyt">' + checkIcon + '$1</span>');
      return formatted;
    }

    // 1. Fenced Code blocks
    var codeBlocks = [];
    html = html.replace(/```([a-z0-9_-]*)\n([\s\S]*?)```/gi, function(match, lang, code) {
      var placeholder = '<!--AICHAT_CODEBLOCK_' + codeBlocks.length + '-->';
      codeBlocks.push('<pre class="aichat-pre"><code class="aichat-code-block">' + code.trim() + '</code></pre>');
      return placeholder;
    });

    // 2. Markdown Tables Parser
    var lines = html.split('\n');
    var processedLines = [];
    var tableBlocks = [];
    var i = 0;

    function isSeparatorRow(line) {
      if (!line) return false;
      var trimmed = line.trim();
      if (!trimmed.includes('-')) return false;
      if (trimmed.startsWith('|')) trimmed = trimmed.slice(1);
      if (trimmed.endsWith('|')) trimmed = trimmed.slice(0, -1);
      var parts = trimmed.split('|');
      if (parts.length === 0) return false;
      return parts.every(function(p) {
        return /^[\s:]*-{1,}[\s:]*$/.test(p);
      });
    }

    function isTableRow(line) {
      if (!line) return false;
      var trimmed = line.trim();
      return trimmed.length > 0 && trimmed.includes('|');
    }

    function splitCells(line) {
      var trimmed = line.trim();
      if (trimmed.startsWith('|')) trimmed = trimmed.slice(1);
      if (trimmed.endsWith('|')) trimmed = trimmed.slice(0, -1);
      return trimmed.split('|').map(function(c) {
        return c.trim();
      });
    }

    function getAlignment(sepCell) {
      var t = (sepCell || '').trim();
      var left = t.startsWith(':');
      var right = t.endsWith(':');
      if (left && right) return 'center';
      if (right) return 'right';
      if (left) return 'left';
      return '';
    }

    while (i < lines.length) {
      var currentLine = lines[i];
      var nextLine = (i + 1 < lines.length) ? lines[i + 1] : null;

      if (nextLine && isTableRow(currentLine) && isSeparatorRow(nextLine)) {
        var headerLine = currentLine;
        var sepLine = nextLine;
        var dataLines = [];
        i += 2;

        while (i < lines.length && isTableRow(lines[i]) && !isSeparatorRow(lines[i])) {
          dataLines.push(lines[i]);
          i++;
        }

        var headerCells = splitCells(headerLine);
        var sepCells = splitCells(sepLine);
        var aligns = sepCells.map(getAlignment);
        var numCols = headerCells.length;

        var priceHeaderPattern = /(giá|đơn\s*giá|thành\s*tiền|chi\s*phí|viện\s*phí|lệ\s*phí|tiền|price|cost|fee)/i;
        var bhytHeaderPattern = /(bhyt|bảo\s*hiểm|áp\s*dụng)/i;
        var priceValuePattern = /(?:\d+[.,\d]*\s*(?:vnđ|vnd|đ|k|đồng)\b|miễn\s*phí|^\s*\d{1,3}([.,]\d{3})+\s*$)/i;

        var priceCols = {};
        var bhytCols = {};

        for (var c = 0; c < numCols; c++) {
          if (priceHeaderPattern.test(headerCells[c])) {
            priceCols[c] = true;
          }
          if (bhytHeaderPattern.test(headerCells[c])) {
            bhytCols[c] = true;
          }
        }

        for (var c = 0; c < numCols; c++) {
          if (!priceCols[c] && dataLines.length > 0) {
            var matchCount = 0;
            for (var r = 0; r < dataLines.length; r++) {
              var rowCells = splitCells(dataLines[r]);
              var cellVal = (c < rowCells.length) ? rowCells[c] : '';
              if (priceValuePattern.test(cellVal)) {
                matchCount++;
              }
            }
            if (matchCount > 0 && matchCount >= dataLines.length * 0.5) {
              priceCols[c] = true;
            }
          }
        }

        var thead = '<thead><tr>';
        for (var c = 0; c < numCols; c++) {
          var classes = [];
          if (priceCols[c]) {
            classes.push('col-price text-right price-col');
          } else if (bhytCols[c] || c === 0) {
            classes.push('col-center text-center');
          } else if (aligns[c]) {
            classes.push('text-' + aligns[c]);
          }
          var clsAttr = classes.length ? ' class="' + classes.join(' ') + '"' : '';
          thead += '<th' + clsAttr + '>' + formatTableCell(headerCells[c], true, priceCols[c], bhytCols[c]) + '</th>';
        }
        thead += '</tr></thead>';

        var tbody = '<tbody>';
        for (var r = 0; r < dataLines.length; r++) {
          var rowCells = splitCells(dataLines[r]);
          tbody += '<tr>';
          for (var c = 0; c < numCols; c++) {
            var cellVal = (c < rowCells.length) ? rowCells[c] : '';
            var classes = [];
            if (priceCols[c]) {
              classes.push('col-price text-right price-col');
            } else if (bhytCols[c] || c === 0) {
              classes.push('col-center text-center');
            } else if (aligns[c]) {
              classes.push('text-' + aligns[c]);
            }
            var clsAttr = classes.length ? ' class="' + classes.join(' ') + '"' : '';
            tbody += '<td' + clsAttr + '>' + formatTableCell(cellVal, false, priceCols[c], bhytCols[c]) + '</td>';
          }
          tbody += '</tr>';
        }
        tbody += '</tbody>';

        var fullTable = '<div class="aichat-table-responsive"><table class="aichat-table">' + thead + tbody + '</table></div>';
        var placeholder = '<!--AICHAT_TABLE_' + tableBlocks.length + '-->';
        tableBlocks.push(fullTable);
        processedLines.push(placeholder);
      } else {
        processedLines.push(currentLine);
        i++;
      }
    }

    html = processedLines.join('\n');

    // 3. Lists (Ordered & Unordered)
    var listLines = html.split('\n');
    var parsedListLines = [];
    var currentListType = null;
    var currentListItems = [];

    function flushList() {
      if (!currentListType) return;
      var itemsHtml = currentListItems.map(function(item) {
        return '<li>' + formatInlineMarkdown(item) + '</li>';
      }).join('');
      parsedListLines.push('<' + currentListType + ' class="aichat-' + currentListType + '">' + itemsHtml + '</' + currentListType + '>');
      currentListType = null;
      currentListItems = [];
    }

    for (var j = 0; j < listLines.length; j++) {
      var l = listLines[j];
      if (l.startsWith('<!--AICHAT_')) {
        flushList();
        parsedListLines.push(l);
        continue;
      }

      var olMatch = l.match(/^\s*(\d+)\.\s+(.+)$/);
      var ulMatch = l.match(/^\s*[•\-\*]\s+(.+)$/);

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
    var finalLines = html.split('\n').map(function(l) {
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
    for (var t = 0; t < tableBlocks.length; t++) {
      html = html.replace('<!--AICHAT_TABLE_' + t + '-->', tableBlocks[t]);
    }
    for (var k = 0; k < codeBlocks.length; k++) {
      html = html.replace('<!--AICHAT_CODEBLOCK_' + k + '-->', codeBlocks[k]);
    }

    return html;
  }

  function appendMessage(role, content, isHtml) {
    var container = document.getElementById('aichat-messages');
    var wrapper = document.createElement('div');
    wrapper.className = 'aichat-msg aichat-msg--' + role;

    var bubble = document.createElement('div');
    bubble.className = 'aichat-msg-bubble';
    if (isHtml) {
      bubble.innerHTML = content;
    } else {
      bubble.innerText = content;
    }

    var timeEl = document.createElement('div');
    timeEl.className = 'aichat-msg-time';
    timeEl.innerText = getTimeNow();

    wrapper.appendChild(bubble);
    wrapper.appendChild(timeEl);
    container.appendChild(wrapper);
    container.scrollTop = container.scrollHeight;
  }

  function showTyping() {
    var container = document.getElementById('aichat-messages');
    var id = 'aichat-typing-' + Date.now();
    var wrapper = document.createElement('div');
    wrapper.className = 'aichat-msg aichat-msg--bot aichat-typing';
    wrapper.id = id;
    var bubble = document.createElement('div');
    bubble.className = 'aichat-msg-bubble';
    bubble.innerHTML = '<div class="aichat-dots"><span></span><span></span><span></span></div>';
    wrapper.appendChild(bubble);
    container.appendChild(wrapper);
    container.scrollTop = container.scrollHeight;
    return id;
  }

  function removeTyping(id) {
    var el = document.getElementById(id);
    if (el) el.remove();
  }

  function escapeHtml(str) {
    return String(str)
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;');
  }

  function getTimeNow() {
    var d = new Date();
    return d.getHours().toString().padStart(2,'0') + ':' + d.getMinutes().toString().padStart(2,'0');
  }

  document.addEventListener('DOMContentLoaded', function() {
    var input = document.getElementById('aichat-input');
    if (!input) return;
    input.addEventListener('keydown', function(e) {
      if (e.key === 'Enter' && !e.shiftKey) {
        e.preventDefault();
        send();
      }
    });
    input.addEventListener('input', function() {
      this.style.height = 'auto';
      this.style.height = Math.min(this.scrollHeight, 120) + 'px';
    });
  });

  return {
    send: send,
    sendQuick: sendQuick,
    quickDoc: quickDoc,
    quickContact: quickContact,
    quickTime: quickTime
  };
})();
</script>
