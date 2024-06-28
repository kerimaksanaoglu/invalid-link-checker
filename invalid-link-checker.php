<?php
/*
Plugin Name: Jusbil.com Link ve Metin Kontrol Eklentisi
Description: Web sitesi içerisindeki tüm linkleri tarar ve geçerli olmayan alan adlarını listeler. Ayrıca belirli bir metni veya kelimeyi arar ve değiştirir.
Version: 1.2
Author: Kerim Aksanaoğlu
*/

// CSS dosyasını yükle
function ilc_enqueue_styles() {
    wp_enqueue_style('ilc-style', plugin_dir_url(__FILE__) . 'css/style.css');
}
add_action('admin_enqueue_scripts', 'ilc_enqueue_styles');

// Eklentiyi başlat
function ilc_start() {
    add_menu_page('Invalid Link Checker', 'Invalid Link Checker', 'manage_options', 'invalid-link-checker', 'ilc_page');
}
add_action('admin_menu', 'ilc_start');

// Eklenti sayfa içeriği
function ilc_page() {
    echo '<div class="wrap">';
    echo '<h1>Invalid Link Checker</h1>';
    ilc_check_links();
    ilc_search_form();
    ilc_search_results();
    ilc_replace_form();
    ilc_replace_results();
    echo '</div>';
}

// Bağlantıları kontrol etme fonksiyonu
function ilc_check_links() {
    // Tüm gönderileri çek
    $args = array('post_type' => 'any', 'post_status' => 'publish', 'numberposts' => -1);
    $all_posts = get_posts($args);

    $invalid_links = array();

    // Her gönderiyi kontrol et
    foreach ($all_posts as $post) {
        if (has_shortcode($post->post_content, 'ilc_shortcode')) {
            continue; // Kendi kısa kodumuzu atla
        }

        // Gönderi içeriğindeki bağlantıları bul
        preg_match_all('/href="([^"]+)"/', $post->post_content, $matches);

        foreach ($matches[1] as $link) {
            // Geçerli alan adı olup olmadığını kontrol et
            if (!filter_var($link, FILTER_VALIDATE_URL) || !checkdnsrr(parse_url($link, PHP_URL_HOST), 'A')) {
                $invalid_links[] = $link;
            }
        }
    }

    // Geçersiz bağlantıları listele
    if (!empty($invalid_links)) {
        echo '<h2>Geçersiz Bağlantılar</h2>';
        echo '<ul>';
        foreach ($invalid_links as $link) {
            echo '<li>' . esc_url($link) . '</li>';
        }
        echo '</ul>';
    } else {
        echo '<p>Tüm bağlantılar geçerli.</p>';
    }
}

// Arama formu fonksiyonu
function ilc_search_form() {
    ?>
    <form method="post" action="">
        <h2>Sayfa İçinde Arama</h2>
        <input type="text" name="ilc_search_query" placeholder="Aranacak kelime veya metin" required>
        <input type="submit" value="Ara">
    </form>
    <?php
}

// Arama sonuçları fonksiyonu
function ilc_search_results() {
    if (isset($_POST['ilc_search_query'])) {
        $search_query = sanitize_text_field($_POST['ilc_search_query']);

        // Tüm gönderileri çek
        $args = array('post_type' => 'any', 'post_status' => 'publish', 'numberposts' => -1);
        $all_posts = get_posts($args);

        $found_posts = array();

        // Her gönderiyi kontrol et
        foreach ($all_posts as $post) {
            if (stripos($post->post_content, $search_query) !== false) {
                $found_posts[] = $post;
            }
        }

        // Arama sonuçlarını listele
        if (!empty($found_posts)) {
            echo '<h2>Arama Sonuçları</h2>';
            echo '<ul>';
            foreach ($found_posts as $post) {
                echo '<li><a href="' . get_permalink($post->ID) . '">' . esc_html($post->post_title) . '</a></li>';
            }
            echo '</ul>';
        } else {
            echo '<p>Aranan kelime veya metin bulunamadı.</p>';
        }
    }
}

// Değiştirme formu fonksiyonu
function ilc_replace_form() {
    ?>
    <form method="post" action="">
        <h2>Metni veya Bağlantıyı Değiştir</h2>
        <input type="text" name="ilc_search_replace" placeholder="Aranacak kelime veya metin" required>
        <input type="text" name="ilc_replace_with" placeholder="Yeni kelime veya metin" required>
        <input type="submit" value="Değiştir">
    </form>
    <?php
}

// Değiştirme sonuçları fonksiyonu
function ilc_replace_results() {
    if (isset($_POST['ilc_search_replace']) && isset($_POST['ilc_replace_with'])) {
        $search_replace = sanitize_text_field($_POST['ilc_search_replace']);
        $replace_with = sanitize_text_field($_POST['ilc_replace_with']);

        // Tüm gönderileri çek
        $args = array('post_type' => 'any', 'post_status' => 'publish', 'numberposts' => -1);
        $all_posts = get_posts($args);

        $updated_posts = array();

        // Her gönderiyi kontrol et
        foreach ($all_posts as $post) {
            if (stripos($post->post_content, $search_replace) !== false) {
                // İçeriği değiştir
                $updated_content = str_ireplace($search_replace, $replace_with, $post->post_content);

                // Gönderiyi güncelle
                wp_update_post(array(
                    'ID' => $post->ID,
                    'post_content' => $updated_content
                ));

                $updated_posts[] = $post;
            }
        }

        // Değiştirilen sonuçları listele
        if (!empty($updated_posts)) {
            echo '<h2>Değiştirilen Gönderiler</h2>';
            echo '<ul>';
            foreach ($updated_posts as $post) {
                echo '<li><a href="' . get_permalink($post->ID) . '">' . esc_html($post->post_title) . '</a></li>';
            }
            echo '</ul>';
        } else {
            echo '<p>Hiçbir içerik değiştirilmedi.</p>';
        }
    }
}
?>
