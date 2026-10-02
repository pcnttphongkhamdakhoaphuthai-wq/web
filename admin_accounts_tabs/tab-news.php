<?php
/** @var array $newsPosts */
?>
<div class="page-header">
  <div>
    <div class="page-title">Tin tức</div>
    <div class="page-desc">Đăng bài viết, bản tin hiển thị trên trang chủ dành cho bệnh nhân.</div>
  </div>
</div>

<div class="section-title">Thêm tin tức mới</div>
<div class="c" style="margin-bottom:16px">
  <form method="post" enctype="multipart/form-data">
    <?php render_form_guard('admin_accounts'); ?>
    <input type="hidden" name="action" value="save_news_post">
    <input type="hidden" name="news_id" value="0">
    <input type="hidden" name="current_media" value="">
    <input type="hidden" name="current_media_type" value="">
    <div class="f-grid2">
      <div class="f-group">
        <label class="f-label-sm" for="news_title_new">Tiêu đề tin tức</label>
        <input class="f-inp" id="news_title_new" name="news_title" required>
      </div>
      <div class="f-group" style="display:flex;align-items:flex-end;padding-bottom:4px;">
        <label style="display:flex;align-items:center;gap:8px;font-size:13px;cursor:pointer;color:var(--tx)">
          <input type="checkbox" name="is_published" checked> Hiển thị cho bệnh nhân
        </label>
      </div>
    </div>
    <div class="f-group">
      <label class="f-label-sm" for="news_media_new">Ảnh hoặc video đính kèm</label>
      <input class="f-inp" id="news_media_new" type="file" name="news_media" accept=".jpg,.jpeg,.png,.webp,.gif,.mp4,.webm,.mov,image/*,video/*">
      <div class="txt-sm txt-muted" style="margin-top:4px;">JPG, PNG, WEBP, GIF hoặc MP4, WEBM, MOV. Tối đa 50MB.</div>
    </div>
    <div class="f-group">
      <label class="f-label-sm" for="news_excerpt_new">Tóm tắt</label>
      <textarea class="f-ta" id="news_excerpt_new" name="news_excerpt" style="min-height:60px;"></textarea>
    </div>
    <div class="f-group">
      <label class="f-label-sm" for="news_body_new">Nội dung đầy đủ</label>
      <textarea class="f-ta" id="news_body_new" name="news_body" required style="min-height:120px;"></textarea>
    </div>
    <button type="submit" class="btn-p">Đăng tin tức</button>
  </form>
</div>

<div class="section-title">Danh sách tin tức (<?= count($newsPosts) ?> bài)</div>
<?php if (empty($newsPosts)): ?>
  <div class="empty-note">Chưa có tin tức nào.</div>
<?php else: ?>
  <div class="tbl-wrap">
    <table>
      <thead>
        <tr>
          <th>ID</th>
          <th>Nội dung</th>
          <th>Trạng thái</th>
          <th style="min-width:260px">Chỉnh sửa</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($newsPosts as $post): ?>
          <tr>
            <td><?= (int) $post['id'] ?></td>
            <td>
              <?php render_news_media($post); ?>
              <strong><?= e($post['title']) ?></strong>
              <div class="txt-sm txt-muted"><?= e(date('d/m/Y H:i', strtotime((string) $post['created_at']))) ?></div>
              <div class="txt-sm"><?= e(mb_substr((string) ($post['excerpt'] ?: $post['body']), 0, 80)) ?>...</div>
            </td>
            <td><?= !empty($post['is_published']) ? '<span class="tag tag-green">Hiển thị</span>' : '<span class="tag tag-gray">Đang ẩn</span>' ?></td>
            <td>
              <form method="post" enctype="multipart/form-data">
                <?php render_form_guard('admin_accounts'); ?>
                <input type="hidden" name="action" value="save_news_post">
                <input type="hidden" name="news_id" value="<?= (int) $post['id'] ?>">
                <input type="hidden" name="current_media" value="<?= e((string) ($post['media_path'] ?? '')) ?>">
                <input type="hidden" name="current_media_type" value="<?= e((string) ($post['media_type'] ?? '')) ?>">
                <input class="f-inp" name="news_title" value="<?= e($post['title']) ?>" required style="margin-bottom:6px;">
                <?php if (!empty($post['media_path'])): ?>
                  <div style="margin-bottom:6px;">
                    <?php render_news_media($post); ?>
                    <label class="txt-sm" style="display:flex;align-items:center;gap:6px;cursor:pointer;margin-top:4px;">
                      <input type="checkbox" name="remove_media"> Xóa ảnh/video hiện tại
                    </label>
                  </div>
                <?php endif; ?>
                <div class="f-group">
                  <label class="f-label-sm">Thay ảnh/video</label>
                  <input class="f-inp" type="file" name="news_media" accept=".jpg,.jpeg,.png,.webp,.gif,.mp4,.webm,.mov,image/*,video/*">
                </div>
                <textarea class="f-ta" name="news_excerpt" placeholder="Tóm tắt" style="min-height:50px;margin-bottom:6px;"><?= e((string) ($post['excerpt'] ?? '')) ?></textarea>
                <textarea class="f-ta" name="news_body" required style="min-height:80px;margin-bottom:6px;"><?= e($post['body']) ?></textarea>
                <label class="txt-sm" style="display:flex;align-items:center;gap:6px;cursor:pointer;margin-bottom:8px;">
                  <input type="checkbox" name="is_published" <?= !empty($post['is_published']) ? 'checked' : '' ?>> Hiển thị
                </label>
                <input type="hidden" name="target_id" value="<?= (int) $post['id'] ?>">
                <div class="actions-row">
                  <button type="submit" class="btn-p">Lưu</button>
                  <button type="submit" class="btn-d" name="action" value="delete_news_post" onclick="return confirm('Xóa tin tức này?');">Xóa</button>
                </div>
              </form>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
<?php endif; ?>
