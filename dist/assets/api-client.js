/**
 * assets/api-client.js
 * Quản lý kết nối Client-Side thông minh giữa Cloudflare Pages Frontend và Render Backend
 * Hỗ trợ tự động phục hồi kết nối và thông báo trạng thái khi Backend ngủ (Cold-Start)
 */

(function () {
  'use strict';

  // Cấu hình các đầu mối API
  const API_CONFIG = {
    // Ưu tiên gọi relative qua Cloudflare Worker Proxy, fallback sang Render trực tiếp
    baseUrls: [
      '', // Gọi trực tiếp trên cùng domain qua Cloudflare Edge Proxy
      'https://web-iewr.onrender.com',
      'https://hospital-web-support.onrender.com'
    ],
    timeoutMs: 30000 // 30s để chờ Render wake up nếu đang cold-start
  };

  // Helper hiển thị thông báo nổi (Toast Notification)
  function showToast(message, type = 'info', duration = 5000) {
    let container = document.getElementById('toast-container');
    if (!container) {
      container = document.createElement('div');
      container.id = 'toast-container';
      container.style.cssText = 'position:fixed;bottom:24px;right:24px;z-index:9999;display:flex;flex-direction:column;gap:10px;max-width:380px;pointer-events:none;';
      document.body.appendChild(container);
    }

    const toast = document.createElement('div');
    toast.style.cssText = `
      padding: 14px 18px;
      border-radius: 12px;
      font-size: 0.92rem;
      font-weight: 500;
      line-height: 1.4;
      box-shadow: 0 10px 25px rgba(0,0,0,0.15);
      pointer-events: auto;
      transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
      transform: translateY(20px);
      opacity: 0;
      display: flex;
      align-items: center;
      gap: 10px;
    `;

    if (type === 'success') {
      toast.style.background = '#059669';
      toast.style.color = '#ffffff';
      toast.innerHTML = `<span>✓</span> <div>${message}</div>`;
    } else if (type === 'error') {
      toast.style.background = '#dc2626';
      toast.style.color = '#ffffff';
      toast.innerHTML = `<span>✕</span> <div>${message}</div>`;
    } else if (type === 'warning') {
      toast.style.background = '#d97706';
      toast.style.color = '#ffffff';
      toast.innerHTML = `<span>⚠</span> <div>${message}</div>`;
    } else {
      toast.style.background = '#0284c7';
      toast.style.color = '#ffffff';
      toast.innerHTML = `<span class="spinner" style="display:inline-block;width:14px;height:14px;border:2px solid #fff;border-top-color:transparent;border-radius:50%;animation:spin 1s linear infinite;"></span> <div>${message}</div>`;
    }

    container.appendChild(toast);
    requestAnimationFrame(() => {
      toast.style.transform = 'translateY(0)';
      toast.style.opacity = '1';
    });

    if (duration > 0) {
      setTimeout(() => {
        toast.style.transform = 'translateY(10px)';
        toast.style.opacity = '0';
        setTimeout(() => toast.remove(), 300);
      }, duration);
    }

    return toast;
  }

  // Thêm style animation cho spinner
  if (!document.getElementById('api-client-style')) {
    const styleEl = document.createElement('style');
    styleEl.id = 'api-client-style';
    styleEl.textContent = `
      @keyframes spin { to { transform: rotate(360deg); } }
      .btn-loading { opacity: 0.7; pointer-events: none; position: relative; }
    `;
    document.head.appendChild(styleEl);
  }

  // Hàm gọi API thông minh có hỗ trợ Cold-Start
  async function callApi(endpoint, data = null, method = 'POST') {
    const cleanEndpoint = endpoint.startsWith('/') ? endpoint : '/' + endpoint;
    let loadingToast = null;
    let coldStartTimer = setTimeout(() => {
      loadingToast = showToast('Máy chủ dữ liệu đang khởi động an toàn, vui lòng đợi trong giây lát...', 'info', 0);
    }, 2500);

    let lastError = null;

    for (const baseUrl of API_CONFIG.baseUrls) {
      const url = `${baseUrl}${cleanEndpoint}`;
      try {
        const controller = new AbortController();
        const timeoutId = setTimeout(() => controller.abort(), API_CONFIG.timeoutMs);

        const options = {
          method: method,
          headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
          },
          credentials: 'include',
          signal: controller.signal
        };

        if (data && method !== 'GET') {
          options.body = JSON.stringify(data);
        }

        const response = await fetch(url, options);
        clearTimeout(timeoutId);
        clearTimeout(coldStartTimer);
        if (loadingToast) loadingToast.remove();

        // Kiểm tra xem phản hồi có phải JSON không
        const contentType = response.headers.get('content-type') || '';
        if (!contentType.includes('application/json')) {
          if (response.status === 404 || response.status === 502 || response.status === 503) {
            throw new Error('Máy chủ dữ liệu hiện chưa sẵn sàng hoặc đang khởi động lại (HTTP ' + response.status + '). Vui lòng thử lại sau giây lát.');
          }
          throw new Error('Máy chủ phản hồi định dạng không hợp lệ');
        }

        const result = await response.json();
        return { status: response.status, ok: response.ok, data: result };

      } catch (err) {
        lastError = err;
        // Tiếp tục thử baseUrl tiếp theo nếu có lỗi mạng
      }
    }

    clearTimeout(coldStartTimer);
    if (loadingToast) loadingToast.remove();

    let friendlyError = lastError?.message || 'Không thể kết nối đến máy chủ xử lý dữ liệu.';
    if (friendlyError === 'Failed to fetch' || friendlyError.includes('NetworkError') || friendlyError.includes('Load failed')) {
      friendlyError = 'Không thể kết nối đến máy chủ Backend (Render). Vui lòng kiểm tra trạng thái máy chủ dữ liệu hoặc thử lại sau.';
    }

    return {
      status: 0,
      ok: false,
      data: {
        success: false,
        error: friendlyError
      }
    };
  }

  // Xuất API ra global window
  window.ApiClient = {
    call: callApi,
    showToast: showToast
  };

})();
