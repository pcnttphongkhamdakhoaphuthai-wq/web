<?php
declare(strict_types=1);

require_once 'config.php';

$customerResources = get_customer_resources(50, true);

render_header('Tư liệu khách hàng - ' . site_setting('clinic_name', 'Phòng khám'));
?>
<div class="wrap" style="margin-top: 32px;">
  <section class="section card">
    <div class="panel-title">
      <div>
        <h1 class="section-title">Tư liệu khách hàng</h1>
        <p class="section-lead">Các hướng dẫn và tài liệu giúp bệnh nhân chuẩn bị trước khi sử dụng dịch vụ.</p>
      </div>
      <a class="btn btn-secondary" href="index.php">← Trang chủ</a>
    </div>
  </section>

  <?php if ($customerResources === []): ?>
    <section class="section card">
      <p class="muted" style="text-align:center;padding:32px 0;">Chưa có tư liệu nào được đăng.</p>
    </section>
  <?php else: ?>
    <section class="section">
      <div class="grid grid-3">
        <?php foreach ($customerResources as $resource): ?>
          <article class="service-card">
            <h3><?= e($resource['title']) ?></h3>
            <?php if (!empty($resource['description'])): ?>
              <p><?= nl2br(e($resource['description'])) ?></p>
            <?php endif; ?>
            <?php if (!empty($resource['resource_url'])): ?>
              <div class="actions" style="margin-top:12px;">
                <a class="btn btn-light" href="<?= e($resource['resource_url']) ?>" target="_blank" rel="noopener">Mở tư liệu ↗</a>
              </div>
            <?php endif; ?>
          </article>
        <?php endforeach; ?>
      </div>
    </section>
  <?php endif; ?>
</div>
<?php render_footer(); ?>
