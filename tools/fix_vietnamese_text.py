from __future__ import annotations

import argparse
from pathlib import Path


ROOT = Path(__file__).resolve().parents[1]
SKIP_DIRS = {".git", ".claude", ".sixth", "storage", "uploads"}
TARGET_SUFFIXES = {".php", ".md"}
MOJIBAKE_MARKERS = ("Ã", "Ă", "Â", "áº", "á»", "Ä", "Æ", "�")

REPLACEMENTS = {
    "Ho ten khong hop le.": "Họ tên không hợp lệ.",
    "He thong chua bat truong Gmail cho benh nhan.": "Hệ thống chưa bật trường Gmail cho bệnh nhân.",
    "Gmail khong hop le.": "Gmail không hợp lệ.",
    "Ban can doi mat khau tam thoi truoc khi tiep tuc.": "Bạn cần đổi mật khẩu tạm thời trước khi tiếp tục.",
    "Vui long nhap mat khau hien tai de doi mat khau.": "Vui lòng nhập mật khẩu hiện tại để đổi mật khẩu.",
    "Mat khau tam thoi khong dung.": "Mật khẩu tạm thời không đúng.",
    "Mat khau hien tai khong dung.": "Mật khẩu hiện tại không đúng.",
    "Mat khau moi can toi thieu 8 ky tu.": "Mật khẩu mới cần tối thiểu 8 ký tự.",
    "Mat khau moi va xac nhan mat khau chua khop.": "Mật khẩu mới và xác nhận mật khẩu chưa khớp.",
    "Da cap nhat mat khau moi thanh cong.": "Đã cập nhật mật khẩu mới thành công.",
    "Da cap nhat thong tin tai khoan.": "Đã cập nhật thông tin tài khoản.",
    " (chua san sang)": " (chưa sẵn sàng)",
    " (de nhan OTP)": " (để nhận OTP)",
    "Kênh nhận OTP khong hop le.": "Kênh nhận OTP không hợp lệ.",
    "Xác nhận mật khẩu moi": "Xác nhận mật khẩu mới",
    "Đặt lại mật khẩu thanh cong. Ban co the dang nhap lai.": "Đặt lại mật khẩu thành công. Bạn có thể đăng nhập lại.",
    "Đăng ký benh nhan": "Đăng ký bệnh nhân",
    "Tạo tài khoản benh nhan de dat lich va xem ket qua kham.": "Tạo tài khoản bệnh nhân để đặt lịch và xem kết quả khám.",
    "Da tao backup tai ": "Đã tạo backup tại ",
    "Khong the tao backup luc nay.": "Không thể tạo backup lúc này.",
    "Backup du lieu": "Backup dữ liệu",
    "Tao backup ngay": "Tạo backup ngay",
    "Tao mat khau tam 10 phut": "Tạo mật khẩu tạm 10 phút",
    "Loc theo tai khoan": "Lọc theo tài khoản",
    "Nhap username, ho ten hoac CCCD": "Nhập username, họ tên hoặc CCCD",
    "Loc theo su kien": "Lọc theo sự kiện",
    "Vi du: login, backup, deleted": "Ví dụ: login, backup, deleted",
    "Loai tai khoan": "Loại tài khoản",
    "Tat ca": "Tất cả",
    "Chi admin": "Chỉ admin",
    "Chi benh nhan": "Chỉ bệnh nhân",
    "Chi khach/chua dang nhap": "Chỉ khách/chưa đăng nhập",
    "Tim kiem": "Tìm kiếm",
    "Bo loc": "Bỏ lọc",
    "Chua co luu vet thao tac nao.": "Chưa có lưu vết thao tác nào.",
    "Thoi gian": "Thời gian",
    "Tai khoan": "Tài khoản",
    "Su kien": "Sự kiện",
    "Chi tiet": "Chi tiết",
    "Nhat ky bao mat": "Nhật ký bảo mật",
    "Chua co su kien bao mat nao.": "Chưa có sự kiện bảo mật nào.",
    "Chua cau hinh HOSPITAL_SMTP_HOST.": "Chưa cấu hình HOSPITAL_SMTP_HOST.",
    "Tài khoản nay chua co email de nhan OTP.": "Tài khoản này chưa có email để nhận OTP.",
    "Tài khoản nay chua co so dien thoai de nhan OTP.": "Tài khoản này chưa có số điện thoại để nhận OTP.",
}


def iter_target_files() -> list[Path]:
    files: list[Path] = []
    for path in ROOT.rglob("*"):
        if not path.is_file():
            continue
        rel_parts = set(path.relative_to(ROOT).parts[:-1])
        if rel_parts & SKIP_DIRS:
            continue
        if path.name.endswith(".bak"):
            continue
        if path.suffix.lower() in TARGET_SUFFIXES:
            files.append(path)
    return sorted(files)


def decode_mojibake_once(text: str) -> str:
    if not any(marker in text for marker in MOJIBAKE_MARKERS[:-1]):
        return text
    try:
        fixed = text.encode("cp1258").decode("utf-8")
    except UnicodeError:
        return text
    return fixed


def normalize_text(text: str) -> str:
    fixed = decode_mojibake_once(text)
    for old, new in REPLACEMENTS.items():
        fixed = fixed.replace(old, new)
    return fixed


def find_markers(text: str) -> list[str]:
    return [marker for marker in MOJIBAKE_MARKERS if marker in text]


def main() -> int:
    parser = argparse.ArgumentParser(description="Fix or check Vietnamese text encoding in project files.")
    parser.add_argument("--check", action="store_true", help="Only report files that would change or still contain mojibake markers.")
    args = parser.parse_args()

    changed: list[Path] = []
    marker_hits: list[tuple[Path, list[str]]] = []

    for path in iter_target_files():
        original = path.read_text(encoding="utf-8", errors="replace")
        fixed = normalize_text(original)
        markers = find_markers(fixed)
        if fixed != original:
            changed.append(path)
            if not args.check:
                path.write_text(fixed, encoding="utf-8", newline="")
        if markers:
            marker_hits.append((path, markers))

    if changed:
        verb = "Would update" if args.check else "Updated"
        print(f"{verb} {len(changed)} file(s):")
        for path in changed:
            print(f"- {path.relative_to(ROOT)}")
    else:
        print("No text changes needed.")

    if marker_hits:
        print("Files still containing possible mojibake markers:")
        for path, markers in marker_hits:
            print(f"- {path.relative_to(ROOT)}: {', '.join(markers)}")
        return 1 if args.check else 0

    return 1 if args.check and changed else 0


if __name__ == "__main__":
    raise SystemExit(main())
