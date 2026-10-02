<?php
$currentPath = current_request_path();
?>
<style>
.tab-nav-container {
  max-width: 1180px;
  margin: 0 auto 32px;
  padding: 0 20px;
}

.tab-bar-modern {
  display: flex;
  gap: 6px;
  flex-wrap: wrap;
  padding: 6px;
  background: #fff;
  border-radius: 100px;
  border: 1px solid var(--border);
  box-shadow: var(--shadow);
  align-items: center;
}

.tab-btn-modern {
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 10px 22px;
  border-radius: 100px;
  border: none;
  font-size: 14px;
  font-weight: 500;
  cursor: pointer;
  background: transparent;
  color: #475569;
  transition: all 0.2s;
  text-decoration: none;
  line-height: 1;
  white-space: nowrap;
}

.tab-btn-modern:hover {
  background: #f1f5f9;
  color: var(--primary);
}

.tab-btn-modern.active {
  background: #e2e8f0;
  color: #0f172a;
  font-weight: 600;
}

.tab-btn-modern.logout-btn {
  color: #ef4444;
  margin-left: auto;
}

.tab-btn-modern.logout-btn:hover {
  background: #fef2f2;
  color: #dc2626;
}

.tab-icon {
  font-size: 16px;
  opacity: 0.8;
}

@media (max-width: 900px) {
  .tab-nav-container {
    padding: 0 16px;
  }
  .tab-bar-modern {
    border-radius: 20px;
    padding: 8px;
  }
  .tab-btn-modern {
    padding: 8px 14px;
    font-size: 13px;
  }
  .tab-btn-modern.logout-btn {
    margin-left: 0;
  }
}
</style>

<div class="tab-nav-container">
  <div class="tab-bar-modern">
    <?php if (admin_can('manage_records')): ?>
      <?php $recTab = trim((string)($_GET['tab'] ?? '')); ?>
      <a href="admin_add_record.php?tab=records" class="tab-btn-modern <?= $currentPath === 'admin_add_record.php' && $recTab !== 'appointments' ? 'active' : '' ?>">
        <span class="tab-icon">📋</span> Trả kết quả
      </a>
      <a href="admin_add_record.php?tab=appointments" class="tab-btn-modern <?= $currentPath === 'admin_add_record.php' && $recTab === 'appointments' ? 'active' : '' ?>">
        <span class="tab-icon">📅</span> Quản lý lịch hẹn
      </a>
    <?php endif; ?>

    <?php if (is_root_admin() || admin_can_any(array_keys(admin_permission_definitions()))): ?>
      <?php if (admin_can('manage_clinic_content') || admin_can('create_backup') || is_root_admin()): ?>
        <a href="admin_accounts.php#tab-system" class="tab-btn-modern <?= $currentPath === 'admin_accounts.php' && strpos($_SERVER['REQUEST_URI'], '#tab-system') !== false ? 'active' : '' ?>" data-tab="tab-system">
          <span class="tab-icon">⚙</span> Hệ thống
        </a>
      <?php endif; ?>
      
      <?php if (admin_can('manage_accounts')): ?>
        <a href="admin_accounts.php#tab-staff" class="tab-btn-modern <?= strpos($_SERVER['REQUEST_URI'], '#tab-staff') !== false ? 'active' : '' ?>" data-tab="tab-staff">
          <span class="tab-icon">👥</span> Nhân viên
        </a>
      <?php endif; ?>
      
      <?php if (admin_can('manage_doctors')): ?>
        <a href="admin_accounts.php#tab-doctors" class="tab-btn-modern <?= strpos($_SERVER['REQUEST_URI'], '#tab-doctors') !== false ? 'active' : '' ?>" data-tab="tab-doctors">
          <span class="tab-icon">🩺</span> Bác sĩ
        </a>
      <?php endif; ?>
      
      <?php if (admin_can('manage_patients')): ?>
        <a href="admin_accounts.php#tab-patients" class="tab-btn-modern <?= strpos($_SERVER['REQUEST_URI'], '#tab-patients') !== false ? 'active' : '' ?>" data-tab="tab-patients">
          <span class="tab-icon">👤</span> Bệnh nhân
        </a>
      <?php endif; ?>
      
      <?php if (admin_can('manage_chatbot') || admin_can('publish_announcements')): ?>
        <a href="admin_accounts.php#tab-communications" class="tab-btn-modern <?= strpos($_SERVER['REQUEST_URI'], '#tab-communications') !== false ? 'active' : '' ?>" data-tab="tab-communications">
          <span class="tab-icon">💬</span> Giao tiếp & Bot
        </a>
      <?php endif; ?>
      
      <?php if (admin_can('manage_support_chat')): ?>
        <a href="admin_accounts.php#tab-support" class="tab-btn-modern <?= strpos($_SERVER['REQUEST_URI'], '#tab-support') !== false ? 'active' : '' ?>" data-tab="tab-support">
          <span class="tab-icon">✅</span> Chat hỗ trợ
        </a>
      <?php endif; ?>
      
      <?php if (admin_can('manage_clinic_content') || is_root_admin()): ?>
        <a href="admin_accounts.php#tab-news" class="tab-btn-modern <?= strpos($_SERVER['REQUEST_URI'], '#tab-news') !== false ? 'active' : '' ?>" data-tab="tab-news">
          <span class="tab-icon">📰</span> Tin tức
        </a>
        <a href="admin_accounts.php#tab-resources" class="tab-btn-modern <?= strpos($_SERVER['REQUEST_URI'], '#tab-resources') !== false ? 'active' : '' ?>" data-tab="tab-resources">
          <span class="tab-icon">📚</span> Tư liệu
        </a>
      <?php endif; ?>
      
      <?php if (admin_can('view_logs')): ?>
        <a href="admin_accounts.php#tab-logs" class="tab-btn-modern <?= strpos($_SERVER['REQUEST_URI'], '#tab-logs') !== false ? 'active' : '' ?>" data-tab="tab-logs">
          <span class="tab-icon">📄</span> Nhật ký
        </a>
      <?php endif; ?>
    <?php endif; ?>
    
    <a href="logout.php" class="tab-btn-modern logout-btn">
      <span class="tab-icon">🚪</span> Đăng xuất
    </a>
  </div>
</div>
