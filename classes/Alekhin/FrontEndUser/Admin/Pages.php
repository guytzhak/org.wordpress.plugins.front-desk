<?php

namespace Alekhin\FrontEndUser\Admin;

use \Alekhin\WebsiteHelpers\ReturnObject;
use \Alekhin\FrontEndUser\FrontEndUser;

class Pages {

    const session_key_posted = __CLASS__ . '_posted';
    const option_key_pages = 'feu_pages';

    static $p = NULL;

    static function get_wordpress_pages() {
        $attr = new \stdClass();
        $attr->posts_per_page = -1;
        $attr->order_by = 'title';
        $attr->order = 'ASC';
        $attr->post_type = 'page';
        return get_posts((array) $attr);
    }

    static function get_system_pages() {
        $pages = [];

        $pages['login'] = 'Login';
        $pages['register'] = 'Register';
        $pages['recover'] = 'Password Recovery';
        $pages['reset'] = 'Reset Password';

        return $pages;
    }

    static function save_pages() {
        $r = new ReturnObject();
        $r->data->pages = [];
        $r->data->pages['login'] = intval(trim(filter_input(INPUT_POST, 'page_login')));
        $r->data->pages['register'] = intval(trim(filter_input(INPUT_POST, 'page_register')));
        $r->data->pages['recover'] = intval(trim(filter_input(INPUT_POST, 'page_recover')));
        $r->data->pages['reset'] = intval(trim(filter_input(INPUT_POST, 'page_reset')));

        if (!wp_verify_nonce(trim(filter_input(INPUT_POST, 'front_end_user_pages')), 'front_end_user_pages')) {
            $r->message = 'Invalid request session!';
            return $r;
        }

        update_option(self::option_key_pages, $r->data->pages);

        $r->success = TRUE;
        $r->message = 'Your pages have been saved!';
        return $r;
    }

    static function get_pages($key = NULL) {
        $pages = get_option(self::option_key_pages, []);
        if ($key === NULL) {
            return $pages;
        }
        if (isset($pages[$key])) {
            return intval(trim($pages[$key]));
        }
        return 0;
    }

    static function get_page_url($key) {
        $page_id = self::get_pages($key);
        if ($page_id === 0) {
            return home_url();
        }
        return get_permalink($page_id);
    }

    static function on_init() {
        if (isset($_SESSION[self::session_key_posted])) {
            self::$p = $_SESSION[self::session_key_posted];
            unset($_SESSION[self::session_key_posted]);
        }
    }

    static function on_admin_menu() {
        add_submenu_page('front-end-user', 'Front-End User - Pages', 'Pages', 'manage_options', 'front-end-user-pages', [__CLASS__, 'view_admin',]);
    }

    static function on_current_screen() {
        if (get_current_screen()->id !== 'front-end-user_page_front-end-user-pages') {
            return;
        }

        if (filter_input(INPUT_POST, 'save_pages') !== NULL) {
            self::$p = $_SESSION[self::session_key_posted] = self::save_pages();
            wp_redirect(self::$p->redirect);
            exit;
        }
    }

    static function on_admin_notices() {
        if (get_current_screen()->id !== 'front-end-user_page_front-end-user-pages') {
            return;
        }

        if (self::$p === NULL) {
            return;
        }

        $classes = [];
        $classes[] = 'notice';
        $classes[] = 'is-dismissible';
        $classes[] = 'notice-' . (self::$p->success ? 'success' : 'error');

        echo '<div class="' . implode(' ', $classes) . '"><p>';
        echo self::$p->message;
        echo '</p></div>';
    }

    static function view_admin() {
        include FrontEndUser::get_dir('/views/admin/pages.php');
    }

    static function initialize() {
        add_action('init', [__CLASS__, 'on_init',]);
        add_action('admin_menu', [__CLASS__, 'on_admin_menu',]);
        add_action('current_screen', [__CLASS__, 'on_current_screen',]);
        add_action('admin_notices', [__CLASS__, 'on_admin_notices',]);
    }

}
