/**
 * index.js - Cloudflare Worker Entrypoint
 * Kiến trúc Decoupled: Tự động phục vụ 100% tài nguyên tĩnh từ Edge CDN và Proxy API an toàn
 */

export default {
  async fetch(request, env) {
    const url = new URL(request.url);
    const backendOrigin = (env && env.BACKEND_ORIGIN) || 'https://web-iewr.onrender.com';

    // 1. Nếu là yêu cầu API hoặc Chatbot: Proxy chuyển tiếp ngầm về Render Backend
    if (url.pathname.startsWith('/api/') || url.pathname === '/api_chat_ai.php') {
      try {
        const targetUrl = new URL(url.pathname + url.search, backendOrigin);
        const forwardReq = new Request(targetUrl.toString(), {
          method: request.method,
          headers: request.headers,
          body: (request.method !== 'GET' && request.method !== 'HEAD') ? request.body : undefined,
          redirect: 'follow'
        });

        // Đảm bảo Host header trỏ đúng backend
        forwardReq.headers.set('Host', new URL(backendOrigin).host);
        forwardReq.headers.set('X-Forwarded-Host', url.host);
        forwardReq.headers.set('X-Forwarded-Proto', 'https');

        const response = await fetch(forwardReq);
        const newHeaders = new Headers(response.headers);
        newHeaders.set('Access-Control-Allow-Origin', url.origin);
        newHeaders.set('Access-Control-Allow-Credentials', 'true');

        return new Response(response.body, {
          status: response.status,
          statusText: response.statusText,
          headers: newHeaders
        });
      } catch (err) {
        // Dự phòng khi máy chủ Render đang ngủ (Free Tier Cold-Start) hoặc bảo trì
        return new Response(
          JSON.stringify({
            success: false,
            error: 'Máy chủ dữ liệu đang khởi động an toàn từ chế độ nghỉ. Vui lòng đợi trong giây lát và thử lại.',
            detail: err.message
          }),
          {
            status: 503,
            headers: {
              'Content-Type': 'application/json; charset=utf-8',
              'Access-Control-Allow-Origin': url.origin,
              'Access-Control-Allow-Credentials': 'true'
            }
          }
        );
      }
    }

    // 2. Toàn bộ các yêu cầu còn lại: Phục vụ trực tiếp từ Cloudflare Edge CDN Static Assets
    if (env && env.ASSETS) {
      return env.ASSETS.fetch(request);
    }

    return new Response('Phòng Khám Đa Khoa Phú Thái - Cổng thông tin y tế trực tuyến', {
      headers: { 'Content-Type': 'text/html; charset=utf-8' },
      status: 200
    });
  }
};
