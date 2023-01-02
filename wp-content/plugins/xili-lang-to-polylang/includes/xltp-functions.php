<?php

function xltp_is_plugin_active_xililanguage() {
    return class_exists('xili_language');
}

function xltp_is_plugin_active_polylang() {
    return function_exists('pll_get_post_translations');
}

function xltp_is_plugin_active_none() {
    return !(xltp_is_plugin_active_xililanguage() or xltp_is_plugin_active_polylang());
}

// Getting data from xili-language

function xltp_get_xili_language_data() {

    function get_xili_language_data($posts) {
        global $xili_language;

        $comb = new xltp_xili_post_combiner();
        $languages = xltp_get_xili_language_slugs();

        foreach ($posts as $this_post) {
            $this_id = $this_post->ID;
    
            $this_language = $xili_language->get_post_language($this_id);
    
            $page_has_translations = false;
    
            foreach ($languages as $linked_language) {
                if ($this_language == $linked_language) {
                    // This is not a linked language
                    continue;
                }
    
                $linked_post_id = xl_get_linked_post_in($this_id, $linked_language);
    
                if ($linked_post_id == 0) {
                    // Did not found linked posts
                    continue;
                }
    
                $added = $comb->add_combination($this_language, $this_id, $linked_language, $linked_post_id);
    
                $page_has_translations = $page_has_translations or $added;
            }
    
            if (!$page_has_translations) {
                $comb->add_single($this_language, $this_id);
            }
        }
    
        return $comb->get_combinations();
    }

    return [
        'posts' => get_xili_language_data(get_posts(['numberposts' => -1])),
        'pages' => get_xili_language_data(get_pages()),
    ];
}

function xltp_get_xili_language_slugs() {
    global $xili_language;

    $languages = $xili_language->get_listlanguages();
    $slugs = [];

    foreach ($languages as $this_language) {
        array_push($slugs, $this_language->slug);
    }

    return $slugs;
}

class xltp_xili_post_combiner {
    private $combinations = [];

    public function add_single($slug, $id) {
        $found = false;

        for ($n = 0; $n < count($this->combinations); $n++) {
            if (@$this->combinations[$n][$slug] == $id) {
                $found = true;
            }
        }

        if (! $found) {
            array_push($this->combinations, [$slug => (int)$id]);
        }
    }

    public function add_combination($slug1, $id1, $slug2, $id2) {
        $added = false;

        for ($n = 0; $n < count($this->combinations); $n++) {
            if (@$this->combinations[$n][$slug1] == $id1) {
                $this->combinations[$n][$slug2] = (int)$id2;
                $added = true;
            }

            if (@$this->combinations[$n][$slug2] == $id2) {
                $this->combinations[$n][$slug1] = (int)$id1;
                $added = true;
            }
        }

        if (! $added) {
            array_push($this->combinations, [$slug1 => (int)$id1, $slug2 => (int)$id2]);
        }

        return $added;
    }

    public function get_combinations() {
        return $this->combinations;
    }
}

// Settings data to Polylang

function xltp_set_polylang_data($data) {
    $messages = [];

    echo(print_r($data, true));

    if ($data['posts']) {
        $n = xltp_set_polylang_data_groups($data['posts']);
        array_push($messages, "Processed $n posts");
    }
    else {
        array_push($messages, "Warning: Incoming data did not contain any posts data");
    }

    if ($data['pages']) {
        $n = xltp_set_polylang_data_groups($data['pages']);
        array_push($messages, "Processed $n pages");
    }
    else {
        array_push($messages, "Warning: Incoming data did not contain any pages data");
    }

    return $messages;
}

function xltp_set_polylang_data_groups($post_groups) {
    $posts_count = 0;

    foreach ($post_groups as $this_group) {
        $posts_count++;
        xltp_set_polylang_post_language($this_group);

        pll_save_post_translations($this_group);
    }

    return $posts_count;
}

function xltp_set_polylang_post_language($language_group) {
    foreach ($language_group as $language_slug => $post_id) {
        pll_set_post_language($post_id, $language_slug);
    }
}
?>