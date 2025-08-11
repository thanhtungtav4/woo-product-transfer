
<?php
/**
 * Plugin Name: Woo Product Transfer
 * Description: Export/Import WooCommerce products via WP-CLI with NDJSON streaming.
 * Author: Dev Team
 * Version: 1.0.0
 */

if (defined('WP_CLI') && WP_CLI) {
    class Woo_Product_Transfer_CLI {
        public function export($args, $assoc_args) {
            list($file) = $args;
            $posts_per_page = isset($assoc_args['posts-per-page']) ? (int)$assoc_args['posts-per-page'] : 500;
            $fh = fopen($file, 'w');
            if (!$fh) {
                WP_CLI::error("Cannot open file: $file");
                return;
            }
            $paged = 1;
            do {
                $q = new WP_Query([
                    'post_type' => 'product',
                    'posts_per_page' => $posts_per_page,
                    'paged' => $paged,
                    'post_status' => 'publish',
                ]);
                foreach ($q->posts as $post) {
                    $meta = get_post_meta($post->ID);
                    $terms = [];
                    foreach (get_object_taxonomies('product') as $tax) {
                        $terms[$tax] = wp_get_object_terms($post->ID, $tax, ['fields' => 'slugs']);
                    }
                    $images = [];
                    $thumb_id = get_post_thumbnail_id($post->ID);
                    if ($thumb_id) {
                        $images[] = wp_get_attachment_url($thumb_id);
                    }
                    $gallery = get_post_meta($post->ID, '_product_image_gallery', true);
                    if ($gallery) {
                        foreach (explode(',', $gallery) as $img_id) {
                            $images[] = wp_get_attachment_url($img_id);
                        }
                    }
                    fwrite($fh, json_encode([
                        'post' => $post,
                        'meta' => $meta,
                        'terms' => $terms,
                        'images' => array_values(array_filter(array_unique($images)))
                    ], JSON_UNESCAPED_UNICODE) . "\n");
                }
                $paged++;
                wp_cache_flush();
            } while ($q->max_num_pages >= $paged);
            fclose($fh);
            WP_CLI::success("Export completed to $file");
        }

        public function import($args, $assoc_args) {
            list($file) = $args;
            $mode = isset($assoc_args['mode']) ? $assoc_args['mode'] : 'create';
            $flush_every = isset($assoc_args['flush-every']) ? (int)$assoc_args['flush-every'] : 100;
            if (!file_exists($file)) {
                WP_CLI::error("File not found: $file");
                return;
            }
            $fh = fopen($file, 'r');
            if (!$fh) {
                WP_CLI::error("Cannot open file: $file");
                return;
            }
            $count = 0;
            while (($line = fgets($fh)) !== false) {
                $data = json_decode(trim($line), true);
                if (!$data) continue;
                $post_data = (array)$data['post'];
                $post_id = null;

                if (in_array($mode, ['overwrite','update-title','update-sku','update-partial'])) {
                    if ($mode === 'overwrite' || $mode === 'update-title') {
                        $found = get_page_by_title($post_data['post_title'], OBJECT, 'product');
                        if ($found) {
                            $post_id = $found->ID;
                            if ($mode === 'overwrite') {
                                wp_delete_post($post_id, true);
                                $post_id = null;
                            }
                        }
                    } elseif (in_array($mode, ['update-sku','update-partial'])) {
                        $sku = $data['meta']['_sku'][0] ?? '';
                        if ($sku) {
                            $found = wc_get_product_id_by_sku($sku);
                            if ($found) $post_id = $found;
                        }
                    }
                }

                if (!$post_id || $mode === 'create') {
                    $post_id = wp_insert_post([
                        'post_type' => 'product',
                        'post_status' => 'publish',
                        'post_title' => $post_data['post_title'],
                        'post_content' => $post_data['post_content'],
                        'post_excerpt' => $post_data['post_excerpt']
                    ]);
                } else {
                    wp_update_post(array_merge(['ID' => $post_id], [
                        'post_title' => $post_data['post_title'],
                        'post_content' => $post_data['post_content'],
                        'post_excerpt' => $post_data['post_excerpt']
                    ]));
                }

                if (!empty($data['meta'])) {
                    foreach ($data['meta'] as $k => $v) {
                        update_post_meta($post_id, $k, maybe_unserialize($v[0] ?? ''));
                    }
                }

                if (!empty($data['terms'])) {
                    foreach ($data['terms'] as $tax => $slugs) {
                        wp_set_object_terms($post_id, $slugs, $tax);
                    }
                }

                if (!empty($data['images'])) {
                    $first = true;
                    $gallery_ids = [];
                    foreach ($data['images'] as $url) {
                        $att_id = attachment_url_to_postid($url);
                        if (!$att_id) {
                            $tmp = download_url($url);
                            if (!is_wp_error($tmp)) {
                                $att_id = media_handle_sideload([
                                    'name' => basename($url),
                                    'tmp_name' => $tmp
                                ], $post_id);
                            }
                        }
                        if ($att_id) {
                            if ($first) {
                                set_post_thumbnail($post_id, $att_id);
                                $first = false;
                            } else {
                                $gallery_ids[] = $att_id;
                            }
                        }
                    }
                    if (!empty($gallery_ids)) {
                        update_post_meta($post_id, '_product_image_gallery', implode(',', $gallery_ids));
                    }
                }

                $count++;
                if ($count % $flush_every === 0) {
                    wp_cache_flush();
                    WP_CLI::log("Processed $count products...");
                }
            }
            fclose($fh);
            WP_CLI::success("Import completed. Total: $count");
        }
    }

    WP_CLI::add_command('products export', [new Woo_Product_Transfer_CLI(), 'export']);
    WP_CLI::add_command('products import', [new Woo_Product_Transfer_CLI(), 'import']);
}
