<?php
/** @var array $customerResources */
?>
<div class="page-header">
  <div>
    <div class="page-title">Tư liệu khách hàng</div>
    <div class="page-desc">Quản lý tài liệu hướng dẫn, liên kết hữu ích hiển thị trên trang chủ dành cho bệnh nhân.</div>
  </div>
</div>

<div class="section-title">Thêm tư liệu mới</div>
<div class="c" style="margin-bottom:16px">
  <form method="post">
    <?php render_form_guard('admin_accounts'); ?>
    <input type="hidden" name="action" value="save_customer_resource">
    <input type="hidden" name="resource_id" value="0">
    <div class="f-grid2">
      <div class="f-group">
        <label class="f-label-sm" for="resource_title_new">Tên tư liệu</label>
        <input class="f-inp" id="resource_title_new" name="resource_title" required>
      </div>
      <div class="f-group">
        <label class="f-label-sm" for="resource_sort_new">Thứ tự hiển thị</label>
        <input class="f-inp" id="resource_sort_new" type="number" name="sort_order" value="0">
      </div>
    </div>
    <div class="f-group">
      <label class="f-label-sm" for="resource_url_new">Link tư liệu</label>
      <input class="f-inp" id="resource_url_new" name="resource_url" placeholder="https://...">
    </div>
    <div class="f-group">
      <label class="f-label-sm" for="resource_desc_new">Mô tả</label>
      <textarea class="f-ta" id="resource_desc_new" name="resource_description" style="min-height:60px;"></textarea>
    </div>
    <div style="display:flex;align-items:center;gap:10px;margin-bottom:12px;">
      <label style="display:flex;align-items:center;gap:8px;font-size:13px;cursor:pointer;color:var(--tx)">
        <input type="checkbox" name="is_published" checked> Hiển thị cho bệnh nhân
      </label>
    </div>
    <button type="submit" class="btn-p">Thêm tư liệu</button>
  </form>
</div>

<div class="section-title">Danh sách tư liệu (<?= count($customerResources) ?> mục)</div>
<?php if (empty($customerResources)): ?>
  <div class="empty-note">Chưa có tư liệu nào.</div>
<?php else: ?>
  <div class="tbl-wrap">
    <table>
      <thead>
        <tr>
          <th>ID</th>
          <th>Tư liệu</th>
          <th>Trạng thái</th>
          <th style="min-width:240px">Chỉnh sửa</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($customerResources as $resource): ?>
          <tr>
            <td><?= (int) $resource['id'] ?></td>
            <td>
              <strong><?= e($resource['title']) ?></strong>
              <?php if (!empty($resource['resource_url'])): ?>
                <div class="txt-sm"><a href="<?= e($resource['resource_url']) ?>" target="_blank" rel="noopener">Mở liên kết ↗</a></div>
              <?php endif; ?>
              <div class="txt-sm txt-muted"><?= e(mb_substr((string) ($resource['description'] ?? ''), 0, 80)) ?></div>
            </td>
            <td><?= !empty($resource['is_published']) ? '<span class="tag tag-green">Hiển thị</span>' : '<span class="tag tag-gray">Đang ẩn</span>' ?></td>
            <td>
              <form method="post">
                <?php render_form_guard('admin_accounts'); ?>
                <input type="hidden" name="action" value="save_customer_resource">
                <input type="hidden" name="resource_id" value="<?= (int) $resource['id'] ?>">
                <input class="f-inp" name="resource_title" value="<?= e($resource['title']) ?>" required style="margin-bottom:6px;">
                <input class="f-inp" type="url" name="resource_url" value="<?= e((string) ($resource['resource_url'] ?? '')) ?>" placeholder="https://..." style="margin-bottom:6px;">
                <textarea class="f-ta" name="resource_description" style="min-height:60px;margin-bottom:6px;"><?= e((string) ($resource['description'] ?? '')) ?></textarea>
                <input class="f-inp" type="number" name="sort_order" value="<?= (int) $resource['sort_order'] ?>" style="margin-bottom:6px;">
                <label class="txt-sm" style="display:flex;align-items:center;gap:6px;cursor:pointer;margin-bottom:8px;">
                  <input type="checkbox" name="is_published" <?= !empty($resource['is_published']) ? 'checked' : '' ?>> Hiển thị
                </label>
                <input type="hidden" name="target_id" value="<?= (int) $resource['id'] ?>">
                <div class="actions-row">
                  <button type="submit" class="btn-p">Lưu</button>
                  <button type="submit" class="btn-d" name="action" value="delete_customer_resource" onclick="return confirm('Xóa tư liệu này?');">Xóa</button>
                </div>
              </form>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
<?php endif; ?>
