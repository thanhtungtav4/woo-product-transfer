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

🖼 Xử lý ảnh
   Check nếu URL hoặc filename đã tồn tại → dùng lại attachment ID.
   
   Kiểm tra HTTP HEAD (200 OK) trước khi tải ảnh.
   
   Ảnh đầu tiên được set làm thumbnail nếu chưa có.

⚙ Xử lý meta
   Meta single value (_price, _stock, _sku, …) luôn update_post_meta.
   
   Meta multi value được replace toàn bộ để tránh trùng.
   
   Giữ nguyên các custom meta nếu không được truyền mới.

📂 Định dạng NDJSON
   Mỗi dòng là một JSON object chứa:
   ```bash
   {
     "post": { ... },
     "meta": { "meta_key": ["value"] },
     "terms": { "taxonomy": ["slug"] },
     "images": ["https://example.com/image1.jpg"]
   }
   ```

🔗 Đồng bộ trực tiếp giữa 2 server bằng SCP
Giả sử:

   Server nguồn (A): userA@source-server.com
   
   Server đích (B): userB@target-server.com
   
   File NDJSON lưu tạm tại: /tmp/products.ndjson

1. Xuất trên server nguồn
   ```bash 
   ssh userA@source-server.com "cd /var/www/html && wp products export /tmp/products.ndjson --posts-per-page=1000 --allow-root"
   ```

2. Chuyển file sang server đích qua SCP
   ```bash
   scp userA@source-server.com:/tmp/products.ndjson /tmp/products.ndjson
   
   ```

💡 One-liner: Xuất → SCP → Import
   ```bash
   ssh userA@source-server.com "cd /var/www/html && wp products export /tmp/products.ndjson --posts-per-page=1000 --allow-root" && \
   scp userA@source-server.com:/tmp/products.ndjson /tmp/products.ndjson && \
   ssh userB@target-server.com "cd /var/www/html && wp products import /tmp/products.ndjson --mode=update-sku --flush-every=200 --allow-root"
   
   ```

Giải thích:

   update-sku: tìm sản phẩm theo SKU, update nếu có, tạo mới nếu chưa.
   
   --flush-every=200: giảm số lần flush cache → nhanh hơn.
   
   &&: chỉ chạy bước tiếp theo nếu bước trước thành công.

🔒 Lưu ý
   Chạy bằng user sở hữu file WP hoặc --allow-root.
   
   Với job dài (>1h) nên dùng screen hoặc tmux.
   
   Test trước trên staging.
   
   WooCommerce phải active ở cả site nguồn & đích.

🛠 Roadmap
   --dry-run (test import không ghi DB)
   
   --skip-images hoặc --only-images
   
   --update-fields="price,stock,meta:_custom_field"



