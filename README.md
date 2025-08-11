# WooCommerce Product Transfer CLI

Plugin WP-CLI để **xuất / nhập sản phẩm WooCommerce** ở định dạng **NDJSON streaming** — tối ưu cho dataset lớn (>10k sản phẩm) mà không tốn nhiều RAM.

---

## 🚀 Tính năng
- **Streaming NDJSON**: xuất/nhập từng dòng, không load hết vào RAM.
- **Xử lý meta thông minh**: tránh trùng lặp, luôn update key quan trọng.
- **Kiểm tra ảnh trước khi tải**: tránh download ảnh trùng hoặc 404.
- **Nhiều chế độ đồng bộ**: `create`, `overwrite`, `update-title`, `update-sku`, `update-partial`.
- **Tùy chỉnh cache flush** để tăng tốc import.
- **Log tiến trình** trực tiếp trên WP-CLI.

---

## 📦 Cài đặt

1. Copy thư mục `woo-product-transfer` vào:
