/**
 * Cloudflare Worker for Hospital Web Support System (webb)
 * Tối ưu hóa phân phối tĩnh qua Cloudflare Edge Assets và Reverse Proxy thông minh về Render PaaS
 */

export default {
  async fetch(request, env, ctx) {
    const url = new URL(request.url);
    const backendOrigin = env.BACKEND_ORIGIN || 'https://conghotrophongkhamphuthai.io.vn';
    const fallbackOrigin = env.BACKEND_FALLBACK || 'https://web-iewr.onrender.com';

    // 1. Phục vụ Clean URLs cho các trang tĩnh (nếu có binding ASSETS và request GET)
    if (env.ASSETS && request.method === 'GET') {
      const cleanUrlRoutes = {
        '/': '/index.html',
        '/login': '/login.html',
        '/register': '/register.html',
        '/book_appointment': '/book_appointment.html',
        '/news': '/news.html',
        '/resources': '/resources.html',
      };

      const mappedPath = cleanUrlRoutes[url.pathname];
      if (mappedPath) {
        try {
          const assetUrl = new URL(mappedPath, url.origin);
          const assetReq = new Request(assetUrl.toString(), request);
          const assetRes = await env.ASSETS.fetch(assetReq);
          if (assetRes && assetRes.status === 200) {
            return assetRes;
          }
        } catch (e) {
          // Bỏ qua lỗi và chuyển tiếp xuống backend
        }
      }
    }

    // 2. Hàm chuyển tiếp (Reverse Proxy) an toàn tới Backend Render
    async function forwardToBackend(origin) {
      const targetUrl = new URL(url.pathname + url.search, origin);

      const newHeaders = new Headers(request.headers);
      newHeaders.set('Host', new URL(origin).host);
      newHeaders.set('X-Forwarded-Host', url.host);
      newHeaders.set('X-Forwarded-Proto', url.protocol.replace(':', ''));

      const clientIp = request.headers.get('cf-connecting-ip') || request.headers.get('x-forwarded-for');
      if (clientIp) {
        newHeaders.set('X-Real-IP', clientIp);
        newHeaders.set('X-Forwarded-For', clientIp);
      }

      const init = {
        method: request.method,
        headers: newHeaders,
        redirect: 'manual',
      };

      if (!['GET', 'HEAD'].includes(request.method.toUpperCase())) {
        init.body = request.body;
      }

      return await fetch(targetUrl.toString(), init);
    }

    // 3. Thực thi gọi Backend Render với cơ chế chịu lỗi (Failover)
    try {
      let response = await forwardToBackend(backendOrigin);

      // Nếu backend chính gặp mã lỗi 502/503/504, thử backend phụ
      if ([502, 503, 504].includes(response.status) && fallbackOrigin && fallbackOrigin !== backendOrigin) {
        try {
          const fallbackRes = await forwardToBackend(fallbackOrigin);
          if (fallbackRes.status < 500) {
            response = fallbackRes;
          }
        } catch (fbErr) {
          // Giữ nguyên response gốc
        }
      }

      // Xử lý rewrite header Location nếu backend redirect về domain nội bộ
      const location = response.headers.get('location');
      const responseHeaders = new Headers(response.headers);

      if (location) {
        try {
          const locUrl = new URL(location);
          const beMainHost = new URL(backendOrigin).host;
          const beFbHost = new URL(fallbackOrigin).host;
          if (locUrl.host === beMainHost || locUrl.host === beFbHost) {
            locUrl.protocol = url.protocol;
            locUrl.host = url.host;
            responseHeaders.set('location', locUrl.toString());
          }
        } catch (e) {
          // Relative path -> giữ nguyên
        }
      }

      // Bổ sung Security Headers theo chuẩn OWASP
      responseHeaders.set('X-Content-Type-Options', 'nosniff');
      responseHeaders.set('X-Frame-Options', 'SAMEORIGIN');

      return new Response(response.body, {
        status: response.status,
        statusText: response.statusText,
        headers: responseHeaders,
      });

    } catch (err) {
      // Nếu backend chính mất kết nối hoàn toàn, thử backend phụ
      try {
        if (fallbackOrigin && fallbackOrigin !== backendOrigin) {
          const fallbackRes = await forwardToBackend(fallbackOrigin);
          return fallbackRes;
        }
      } catch (fbErr) {
        // Tiếp tục trả lỗi
      }

      return new Response(
        JSON.stringify({
          error: 'GATEWAY_ERROR',
          message: 'Không thể kết nối đến máy chủ Render Backend. Vui lòng kiểm tra lại trạng thái dịch vụ.',
          detail: err.message
        }),
        {
          status: 502,
          headers: {
            'Content-Type': 'application/json; charset=utf-8',
            'X-Content-Type-Options': 'nosniff'
          }
        }
      );
    }
  }
};
