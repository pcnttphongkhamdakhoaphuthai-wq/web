<?php
declare(strict_types=1);

require_once 'config.php';

$newsPosts = get_recent_news_posts(50, true);

render_header('Tin tức - ' . site_setting('clinic_name', 'Phòng khám'));
?>
<div class="wrap" style="margin-top: 32px;">
  <section class="section card">
    <div class="panel-title">
      <div>
        <h1 class="section-title">Tin tức</h1>
        <p class="section-lead">Cập nhật các thông tin mới nhất từ phòng khám.</p>
      </div>
      <a class="btn btn-secondary" href="index.php">← Trang chủ</a>
    </div>
  </section>

  <?php if ($newsPosts === []): ?>
    <section class="section card">
      <p class="muted" style="text-align:center;padding:32px 0;">Chưa có tin tức nào được đăng.</p>
    </section>
  <?php else: ?>
    <section class="section">
      <div class="grid grid-3">
        <?php foreach ($newsPosts as $post): ?>
          <article class="service-card">
            <?php render_news_media($post); ?>
            <h3><?= e($post['title']) ?></h3>
            <div class="muted text-sm"><?= e(date('d/m/Y', strtotime((string) $post['created_at']))) ?></div>
            <p><?= nl2br(e((string) ($post['excerpt'] ?: $post['body']))) ?></p>
          </article>
        <?php endforeach; ?>
      </div>
    </section>
  <?php endif; ?>
</div>
<?php render_footer(); ?>
