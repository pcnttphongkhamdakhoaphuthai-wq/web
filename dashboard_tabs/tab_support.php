<?php /* TAB: Hỗ trợ & Liên hệ */ ?>
<div id="tab-support" class="dashboard-tab">

  <div style="max-width: 800px; margin: 0 auto;">

    <section class="card" style="padding: 0; overflow: hidden; border: 1px solid #e2e8f0; margin-bottom: 24px;">
      <?php require_once APP_ROOT . '/dashboard_tabs/chatbot_inline.php'; ?>
    </section>

    <section class="card">
      <h2 style="font-size:20px;margin-bottom:18px;text-align:center;">📞 Thông tin liên hệ phòng khám</h2>
      <div class="info-row" style="padding: 16px; background: #f8fafc; border-radius: 12px; margin-bottom: 12px; display: flex; align-items: center; gap: 16px;">
        <span class="info-icon" style="font-size: 28px;">📱</span>
        <div>
          <div class="info-label" style="font-weight: 600; color: #475569;">Hotline hỗ trợ (Zalo/Gọi điện)</div>
          <a href="tel:<?= e(preg_replace('/[^0-9+]/', '', $clinic['support_hotline'])) ?>" class="info-val" style="font-size: 20px; font-weight: 700; color: #0284c7; text-decoration: none; display: block; margin-top: 4px;"><?= e($clinic['support_hotline']) ?></a>
        </div>
      </div>
      
      <div class="info-row" style="padding: 16px; background: #f8fafc; border-radius: 12px; margin-bottom: 12px; display: flex; align-items: center; gap: 16px;">
        <span class="info-icon" style="font-size: 28px;">✉️</span>
        <div>
          <div class="info-label" style="font-weight: 600; color: #475569;">Email</div>
          <a href="mailto:<?= e($clinic['support_email']) ?>" class="info-val" style="font-size: 16px; font-weight: 600; color: #0284c7; text-decoration: none; display: block; margin-top: 4px;"><?= e($clinic['support_email']) ?></a>
        </div>
      </div>
      
      <?php if ($clinic['clinic_address'] !== ''): ?>
      <div class="info-row" style="padding: 16px; background: #f8fafc; border-radius: 12px; margin-bottom: 16px; display: flex; align-items: flex-start; gap: 16px;">
        <span class="info-icon" style="font-size: 28px;">📍</span>
        <div>
          <div class="info-label" style="font-weight: 600; color: #475569;">Địa chỉ</div>
          <div class="info-val" style="font-size: 16px; font-weight: 500; color: #1e293b; margin-top: 4px; line-height: 1.5;"><?= e($clinic['clinic_address']) ?></div>
        </div>
      </div>
      <?php if ($clinic['google_maps_url'] !== '' || $clinic['apple_maps_url'] !== ''): ?>
      <div class="map-btns" style="display: flex; gap: 12px; justify-content: center; flex-wrap: wrap;">
        <?php if ($clinic['google_maps_url'] !== ''): ?>
          <a href="<?= e($clinic['google_maps_url']) ?>" target="_blank" rel="noopener" class="btn btn-light" style="padding: 12px 24px; font-size: 15px;">🗺 Google Maps</a>
        <?php endif; ?>
        <?php if ($clinic['apple_maps_url'] !== ''): ?>
          <a href="<?= e($clinic['apple_maps_url']) ?>" target="_blank" rel="noopener" class="btn btn-light" style="padding: 12px 24px; font-size: 15px;">🍎 Apple Maps</a>
        <?php endif; ?>
      </div>
      <?php endif; ?>
      <?php endif; ?>
    </section>
  </div>

</div>

