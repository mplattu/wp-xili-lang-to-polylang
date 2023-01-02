<?php
/*
Plugin Name: xili-lang-to-polylang
Description: Plugin to help moving xili-language site to a Polylang site
Author: mplattu
*/

require_once plugin_dir_path(__FILE__) . 'includes/xltp-functions.php';

function xltp_add_menu_page() {
    add_menu_page(
        'xili-language to Polylang',
        'XLTP',
        'manage_options',
        'xltp-settings-page',
        'xltp_render_plugin_settings_page'
    );
}

add_action('admin_menu', 'xltp_add_menu_page');

function xltp_get_incoming_xili_data() {
    if (@$_POST['xltp_incoming_xili_data']) {
        return json_decode(stripslashes($_POST['xltp_incoming_xili_data']), true);
    }

    return "";
}

function xltp_render_plugin_settings_page() {
    echo('<h2>Migration tool from xili-language to Polylang</h2>');

    if (xltp_is_plugin_active_xililanguage()) {
        echo('<p>Detected plugin: <b>xili-language</b></p>');
    }

    if (xltp_is_plugin_active_polylang()) {
        echo('<p>Detected plugin: <b>polylang</b></p>');
    }

    if (xltp_is_plugin_active_none()) {
        echo('<p>No language plugins (xili-language or polylang) was detected</p>');
    }

    if (xltp_is_plugin_active_xililanguage()) {
        echo('<p>JSON array created from xili-language data:</p>');

        $xili_language_data = xltp_get_xili_language_data();

        echo('<textarea rows="20" cols="20">');
        echo(json_encode($xili_language_data, JSON_PRETTY_PRINT));
        echo('</textarea>');

        echo('<p>Copy the JSON string above, disable xili-language, enable Polylang and paste the data back here.</p>');
    }

    if (xltp_is_plugin_active_polylang()) {
        echo('<form action="admin.php?page=xltp-settings-page" method="post">');

        echo('<p><textarea rows="20" cols="20" name="xltp_incoming_xili_data">'.json_encode(xltp_get_incoming_xili_data(), JSON_PRETTY_PRINT).'</textarea></p>');

        echo('<p><input name="submit" class="button button-primary" type="submit" value="Profit!" /></p>');
        echo("</form>");
    }

}

function xltp_process_incoming_settings() {
    if (xltp_get_incoming_xili_data()) {
        $messages = xltp_set_polylang_data(xltp_get_incoming_xili_data());

        foreach ($messages as $this_message) {
            echo('<div class="notice"><p>'.$this_message.'</p></div>');
        }
    }
}

add_action('admin_notices', 'xltp_process_incoming_settings');

?>