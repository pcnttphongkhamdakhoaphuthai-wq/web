<?php
declare(strict_types=1);

require_once 'config.php';
require_admin_permission('manage_clinic_content');

$adminId = (int) ($_SESSION['admin_id'] ?? 0);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = (string) ($_POST['action'] ?? '');

    $guardError = validate_form_guard('admin_news', 30, 600, 0, false);
    if ($guardError !== null) {
        set_flash('error', $guardError);
        redirect('admin_news.php');
    }

    audit_log('admin_news_action', [
        'action' => $action,
        'target_id' => (int) ($_POST['target_id'] ?? $_POST['news_id'] ?? $_POST['resource_id'] ?? 0),
    ]);

    if ($action === 'save_news_post') {
        $newsId = (int) ($_POST['news_id'] ?? 0);
        $title = trim((string) ($_POST['news_title'] ?? ''));
        $excerpt = trim((string) ($_POST['news_excerpt'] ?? ''));
        $body = trim((string) ($_POST['news_body'] ?? ''));
        $currentMedia = trim((string) ($_POST['current_media'] ?? ''));
        $currentMediaType = trim((string) ($_POST['current_media_type'] ?? ''));
        $mediaPath = $currentMedia !== '' ? $currentMedia : null;
        $mediaType = $currentMediaType !== '' ? $currentMediaType : null;
        $isPublished = isset($_POST['is_published']);

        if ($title === '' || $body === '') {
            set_flash('error', 'Vui lòng nhập tiêu đề và nội dung tin tức.');
            redirect('admin_news.php#news-content');
        }

        if (isset($_POST['remove_media'])) {
            delete_news_media($mediaPath);
            $mediaPath = null;
            $mediaType = null;
        }

        if (isset($_FILES['news_media']) && ($_FILES['news_media']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
            try {
                $uploadedMedia = store_news_media($_FILES['news_media'], $mediaPath);
                $mediaPath = $uploadedMedia['path'];
                $mediaType = $uploadedMedia['type'];
            } catch (Throwable $exception) {
                log_internal_error('news_media_upload_failed', $exception, ['admin_id' => $adminId, 'news_id' => $newsId]);
                set_flash('error', $exception->getMessage());
                redirect('admin_news.php#news-content');
            }
        }

        save_news_post($newsId, $title, $excerpt, $body, $isPublished, $adminId, $mediaPath, $mediaType);
        set_flash('success', $newsId > 0 ? 'Đã cập nhật tin tức.' : 'Đã thêm tin tức mới.');
        redirect('admin_news.php#news-content');
    }

    if ($action === 'delete_news_post') {
        $newsId = (int) ($_POST['target_id'] ?? 0);
        if ($newsId > 0) {
            delete_news_post($newsId);
            set_flash('success', 'Đã xóa tin tức.');
        }
        redirect('admin_news.php#news-content');
    }

    if ($action === 'save_customer_resource') {
        $resourceId = (int) ($_POST['resource_id'] ?? 0);
        $title = trim((string) ($_POST['resource_title'] ?? ''));
        $description = trim((string) ($_POST['resource_description'] ?? ''));
        $resourceUrl = trim((string) ($_POST['resource_url'] ?? ''));
        $sortOrder = (int) ($_POST['sort_order'] ?? 0);
        $isPublished = isset($_POST['is_published']);

        if ($title === '') {
            set_flash('error', 'Vui lòng nhập tiêu đề tư liệu khách hàng.');
            redirect('admin_news.php#customer-resources');
        }
        if ($resourceUrl !== '' && !filter_var($resourceUrl, FILTER_VALIDATE_URL)) {
            set_flash('error', 'Link tư liệu khách hàng không hợp lệ.');
            redirect('admin_news.php#customer-resources');
        }

        save_customer_resource($resourceId, $title, $description, $resourceUrl, $sortOrder, $isPublished, $adminId);
        set_flash('success', $resourceId > 0 ? 'Đã cập nhật tư liệu khách hàng.' : 'Đã thêm tư liệu khách hàng mới.');
        redirect('admin_news.php#customer-resources');
    }

    if ($action === 'delete_customer_resource') {
        $resourceId = (int) ($_POST['target_id'] ?? 0);
        if ($resourceId > 0) {
            delete_customer_resource($resourceId);
            set_flash('success', 'Đã xóa tư liệu khách hàng.');
        }
        redirect('admin_news.php#customer-resources');
    }
}

$newsPosts = get_recent_news_posts(50, false);
$customerResources = get_customer_resources(50, false);

render_header('Quản lý Tin tức & Tư liệu');
?>
<div class="topbar" style="max-width:1180px;margin:40px auto 20px;padding:0 20px;">
  <div>
    <h1>Quản lý Tin tức và Tư liệu</h1>
    <p class="muted">Khu vực đăng bài viết, bản tin và tài liệu hướng dẫn dành cho bệnh nhân.</p>
  </div>
</div>

<?php require 'admin_tabs_nav.php'; ?>

<div class="wrap" style="margin-top:0;">
  <?php render_flash(); ?>

  <section class="card" id="news-content">
    <h2>Tin tức</h2>
    <form method="post" enctype="multipart/form-data" class="grid grid-2" style="margin-bottom:18px;">
      <?php render_form_guard('admin_news'); ?>
      <input type="hidden" name="action" value="save_news_post">
      <input type="hidden" name="news_id" value="0">
      <input type="hidden" name="current_media" value="">
      <input type="hidden" name="current_media_type" value="">
      <div><label for="news_title_new">Tiêu đề tin tức</label><input id="news_title_new" name="news_title" required></div>
      <div><label><input type="checkbox" name="is_published" checked> Hiển thị cho khách hàng</label></div>
      <div style="grid-column:1/-1;"><label for="news_media_new">Ảnh hoặc video</label><input id="news_media_new" type="file" name="news_media" accept=".jpg,.jpeg,.png,.webp,.gif,.mp4,.webm,.mov,image/*,video/*"><div class="muted text-sm" style="margin-top:6px;">Hỗ trợ ảnh JPG, PNG, WEBP, GIF hoặc video MP4, WEBM, MOV. Tối đa 50MB.</div></div>
      <div style="grid-column:1/-1;"><label for="news_excerpt_new">Tóm tắt</label><textarea id="news_excerpt_new" name="news_excerpt"></textarea></div>
      <div style="grid-column:1/-1;"><label for="news_body_new">Nội dung</label><textarea id="news_body_new" name="news_body" required></textarea></div>
      <div class="actions"><button type="submit">Thêm tin tức</button></div>
    </form>
    <table>
      <thead><tr><th>ID</th><th>Tin tức</th><th>Trạng thái</th><th>Cập nhật</th></tr></thead>
      <tbody>
      <?php foreach ($newsPosts as $post): ?>
        <tr>
          <td><?= (int) $post['id'] ?></td>
          <td>
            <?php render_news_media($post); ?>
            <strong><?= e($post['title']) ?></strong>
            <div class="muted text-sm"><?= e(date('d/m/Y H:i', strtotime((string) $post['created_at']))) ?></div>
            <div><?= nl2br(e((string) ($post['excerpt'] ?: $post['body']))) ?></div>
          </td>
          <td><?= !empty($post['is_published']) ? '<span class="badge">Đang hiển thị</span>' : '<span class="badge">Đang ẩn</span>' ?></td>
          <td>
            <form method="post" enctype="multipart/form-data" class="grid">
              <?php render_form_guard('admin_news'); ?>
              <input type="hidden" name="action" value="save_news_post">
              <input type="hidden" name="news_id" value="<?= (int) $post['id'] ?>">
              <input type="hidden" name="current_media" value="<?= e((string) ($post['media_path'] ?? '')) ?>">
              <input type="hidden" name="current_media_type" value="<?= e((string) ($post['media_type'] ?? '')) ?>">
              <input type="text" name="news_title" value="<?= e($post['title']) ?>" required>
              <?php if (!empty($post['media_path'])): ?>
                <div>
                  <?php render_news_media($post); ?>
                  <label><input type="checkbox" name="remove_media"> Xóa ảnh/video hiện tại</label>
                </div>
              <?php endif; ?>
              <div><label>Thay ảnh/video</label><input type="file" name="news_media" accept=".jpg,.jpeg,.png,.webp,.gif,.mp4,.webm,.mov,image/*,video/*"></div>
              <textarea name="news_excerpt" placeholder="Tóm tắt"><?= e((string) ($post['excerpt'] ?? '')) ?></textarea>
              <textarea name="news_body" required><?= e($post['body']) ?></textarea>
              <label><input type="checkbox" name="is_published" <?= !empty($post['is_published']) ? 'checked' : '' ?>> Hiển thị</label>
              <div class="actions">
                <button type="submit">Lưu tin tức</button>
                <button type="submit" class="danger-btn" name="action" value="delete_news_post" onclick="return confirm('Xóa tin tức này?');">Xóa</button>
              </div>
              <input type="hidden" name="target_id" value="<?= (int) $post['id'] ?>">
            </form>
          </td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </section>

  <section class="card" id="customer-resources">
    <h2>Tư liệu khách hàng</h2>
    <form method="post" class="grid grid-2" style="margin-bottom:18px;">
      <?php render_form_guard('admin_news'); ?>
      <input type="hidden" name="action" value="save_customer_resource">
      <input type="hidden" name="resource_id" value="0">
      <div><label for="resource_title_new">Tên tư liệu</label><input id="resource_title_new" name="resource_title" required></div>
      <div><label for="resource_sort_new">Thứ tự hiển thị</label><input id="resource_sort_new" type="number" name="sort_order" value="0"></div>
      <div style="grid-column:1/-1;"><label for="resource_url_new">Link tư liệu</label><input id="resource_url_new" name="resource_url" placeholder="https://..."></div>
      <div style="grid-column:1/-1;"><label for="resource_description_new">Mô tả</label><textarea id="resource_description_new" name="resource_description"></textarea></div>
      <div><label><input type="checkbox" name="is_published" checked> Hiển thị cho khách hàng</label></div>
      <div class="actions"><button type="submit">Thêm tư liệu</button></div>
    </form>
    <table>
      <thead><tr><th>ID</th><th>Tư liệu</th><th>Trạng thái</th><th>Cập nhật</th></tr></thead>
      <tbody>
      <?php foreach ($customerResources as $resource): ?>
        <tr>
          <td><?= (int) $resource['id'] ?></td>
          <td>
            <strong><?= e($resource['title']) ?></strong>
            <?php if (!empty($resource['resource_url'])): ?>
              <div><a href="<?= e($resource['resource_url']) ?>" target="_blank" rel="noopener">Mở liên kết</a></div>
            <?php endif; ?>
            <div><?= nl2br(e((string) ($resource['description'] ?? ''))) ?></div>
          </td>
          <td><?= !empty($resource['is_published']) ? '<span class="badge">Đang hiển thị</span>' : '<span class="badge">Đang ẩn</span>' ?></td>
          <td>
            <form method="post" class="grid">
              <?php render_form_guard('admin_news'); ?>
              <input type="hidden" name="action" value="save_customer_resource">
              <input type="hidden" name="resource_id" value="<?= (int) $resource['id'] ?>">
              <input type="text" name="resource_title" value="<?= e($resource['title']) ?>" required>
              <input type="url" name="resource_url" value="<?= e((string) ($resource['resource_url'] ?? '')) ?>" placeholder="https://...">
              <textarea name="resource_description"><?= e((string) ($resource['description'] ?? '')) ?></textarea>
              <input type="number" name="sort_order" value="<?= (int) $resource['sort_order'] ?>">
              <label><input type="checkbox" name="is_published" <?= !empty($resource['is_published']) ? 'checked' : '' ?>> Hiển thị</label>
              <div class="actions">
                <button type="submit">Lưu tư liệu</button>
                <button type="submit" class="danger-btn" name="action" value="delete_customer_resource" onclick="return confirm('Xóa tư liệu này?');">Xóa</button>
              </div>
              <input type="hidden" name="target_id" value="<?= (int) $resource['id'] ?>">
            </form>
          </td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </section>
</div>
<?php render_footer(); ?>
