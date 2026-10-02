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
    var html = escapeHtml(text);
    
    // Convert markdown links: [label](url)
    html = html.replace(/\[([^\]]+)\]\((https?:\/\/[^\s)]+)\)/g, '<a href="$2" target="_blank" rel="noopener" class="aichat-link">$1</a>');
    
    // Convert raw URLs: http/https (excluding those already in href attributes)
    html = html.replace(/(?<!href=["'])(https?:\/\/[^\s<)]+)/gi, '<a href="$1" target="_blank" rel="noopener" class="aichat-link">$1</a>');

    html = html.replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>');
    html = html.replace(/\*(.*?)\*/g, '<em>$1</em>');
    html = html.replace(/(?:^|\n)(\d+)\.\s(.+)/g, function(m, num, item) {
      return '\n<li>' + item + '</li>';
    });
    if (html.includes('<li>')) {
      html = html.replace(/(<li>.*<\/li>)/gs, '<ol>$1</ol>');
    }
    html = html.replace(/(?:^|\n)[•\-]\s(.+)/g, function(m, item) {
      return '\n<li>' + item + '</li>';
    });
    if (html.includes('<li>') && !html.includes('<ol>')) {
        html = html.replace(/(<li>.*<\/li>)/gs, '<ul>$1</ul>');
    }
    html = html.replace(/\n/g, '<br>');
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
