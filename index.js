export default {
  async fetch(request, env) {
    if (env && env.ASSETS) {
      return env.ASSETS.fetch(request);
    }
    return new Response("Phòng Khám Đa Khoa Phú Thái - Cổng thông tin y tế", {
      headers: { "Content-Type": "text/html; charset=utf-8" },
      status: 200
    });
  }
};
