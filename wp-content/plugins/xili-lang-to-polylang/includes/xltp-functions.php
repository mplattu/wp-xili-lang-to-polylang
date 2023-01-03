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

    function get_xili_language_data_for_posts($wp_data_object) {
        global $xili_language;

        $comb = new xltp_xili_post_combiner();
        $languages = xltp_get_xili_language_slugs();

        foreach ($wp_data_object as $this_wp_data_object) {
            $this_id = $this_wp_data_object->ID;
            $this_language = $xili_language->get_post_language($this_id);
    
            $item_has_translations = false;
    
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
    
                $item_has_translations = $item_has_translations or $added;
            }
    
            if (!$item_has_translations) {
                $comb->add_single($this_language, $this_id);
            }
        }
    
        return $comb->get_combinations();
    }

    function get_xili_language_data_for_categories($wp_data_objects) {
        $categories = new xltp_category_storage(xltp_get_xili_language_slugs());

        foreach ($wp_data_objects as $this_wp_data_object) {
            $this_id = $this_wp_data_object->ID;
            $categories->add_object_categories($this_id, wp_get_post_categories($this_id));
        }

        return $categories->get_categories();
    }

    return [
        'posts' => get_xili_language_data_for_posts(get_posts(['numberposts' => -1])),
        'pages' => get_xili_language_data_for_posts(get_pages()),
        'categories' => get_xili_language_data_for_categories(get_posts(['numberposts' => -1])),
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

class xltp_category_storage {
    private $categories = [];
    private $languages = [];

    public function __construct($languages) {
        $this->languages = $languages;
    }

    public function add_object_categories($object_id, $object_categories) {
        error_log($object_id."  -  ".print_r($object_categories, true), 4);

        foreach ($object_categories as $this_category) {
            if (!array_key_exists($this_category, $this->categories)) {
                $this->categories[$this_category] = [];
                $this->categories[$this_category]['ids'] = [];
                $this->categories[$this_category]['name'] = [];

                $category_name = get_cat_name($this_category);

                foreach ($this->languages as $this_language) {
                    $this->categories[$this_category]['name'][$this_language] = $category_name;
                }
            }
    
            array_push($this->categories[$this_category]['ids'], $object_id); 
        }
    }

    public function get_categories() {
        return $this->categories;
    }
}

// Settings data to Polylang

function xltp_set_polylang_data($data) {
    $messages = [];

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

    if ($data['categories']) {
        $n = xltp_set_polylang_categories($data['categories']);
        array_push($messages, "Processed $n categories");
    }
    else {
        array_push($messages, "Warning: Incoming data did not contain any categories data");
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

function xltp_set_polylang_categories($categories) {
    $categories_count = 0;

    foreach ($categories as $old_category_id => $old_category_data) {
        $categories_count++;

        $category_ids = [];
        $category_languages_and_ids = [];

        foreach ($old_category_data['name'] as $this_lang => $this_category_name) {
            $category_id = wp_insert_category(['cat_name'=>$this_category_name, 'cat_nicename'=>$this_category_name]);
            pll_set_term_language($category_id, $this_lang);

            array_push($category_ids, $category_id);
            $category_languages_and_ids[$this_lang] = $category_id;
        }

        pll_save_term_translations($category_languages_and_ids);

        foreach ($old_category_data['ids'] as $this_post_id) {
            wp_set_post_categories($this_post_id, $category_ids, true);
        }
    }

    return $categories_count;
}
?>