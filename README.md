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

   wp-content/plugins/woo-product-transfer


2. Kích hoạt plugin:
```bash
wp plugin activate woo-product-transfer --allow-root
```

Kiểm tra WP-CLI:
```bash
wp --info
```

🔄 Xuất sản phẩm
```bash
 wp products export /path/to/products.ndjson [--posts-per-page=<num>] --allow-root
```

Tham số:

/path/to/products.ndjson — file output (ghi đè nếu tồn tại).

--posts-per-page — số sản phẩm mỗi lần query (mặc định 500).

Ví dụ:
```bash
wp products export /tmp/products.ndjson --posts-per-page=1000 --allow-root
```

🔄 Nhập sản phẩm
```bash
wp products import /path/to/products.ndjson [--mode=<mode>] [--flush-every=<num>] --allow-root
```

Chế độ --mode:

create — luôn tạo mới.

overwrite — tìm theo title, xóa rồi tạo mới.

update-title — tìm theo title, update (nếu không có thì tạo mới).

update-sku — tìm theo SKU, update (nếu không có thì tạo mới).

update-partial — tìm theo SKU, chỉ update meta + taxonomy + ảnh (nếu chưa có).

Tham số khác:

--flush-every — flush cache sau N sản phẩm (mặc định 100).

Ví dụ:

# Tạo mới
```bash
wp products import /tmp/products.ndjson --mode=create --allow-root
```
# Ghi đè theo title
```bash
wp products import /tmp/products.ndjson --mode=overwrite --flush-every=200 --allow-root
```
# Update một phần theo SKU
```bash
wp products import /tmp/products.ndjson --mode=update-partial --allow-root
```


