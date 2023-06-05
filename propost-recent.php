<?php
/*
Plugin Name: ProPost Recent
Description: Displays recent posts and WooCommerce products in a widget sidebar.
Version: 2.4
Author: Your Name
*/

// Enqueue the plugin's CSS file
function propost_recent_enqueue_styles() {
    wp_enqueue_style('propost-recent-css', plugins_url('CSS/propost-recent.css', __FILE__));
}
add_action('wp_enqueue_scripts', 'propost_recent_enqueue_styles');

// Register the widget
function propost_recent_register_widget() {
    register_widget('ProPost_Recent_Widget');
}
add_action('widgets_init', 'propost_recent_register_widget');

// Widget class
class ProPost_Recent_Widget extends WP_Widget {

    // Widget setup
    public function __construct() {
        parent::__construct(
            'propost_recent_widget', // Base ID
            'ProPost Recent', // Widget name
            array('description' => 'Displays recent posts and WooCommerce products in a widget sidebar.') // Widget description
        );
    }

    // Display the widget content
    public function widget($args, $instance) {
        $title = !empty($instance['title']) ? $instance['title'] : '';
        $display_posts = !empty($instance['display_posts']) ? $instance['display_posts'] : false;
        $display_products = !empty($instance['display_products']) ? $instance['display_products'] : false;
        $custom_class = !empty($instance['custom_class']) ? $instance['custom_class'] : '';
        $title_tag = !empty($instance['title_tag']) ? $instance['title_tag'] : 'h2';
        $add_title_attribute = !empty($instance['add_title_attribute']) ? $instance['add_title_attribute'] : false;
        $make_title_clickable = !empty($instance['make_title_clickable']) ? $instance['make_title_clickable'] : false;
        $num_posts = !empty($instance['num_posts']) ? absint($instance['num_posts']) : 5;
        $post_order = !empty($instance['post_order']) ? sanitize_text_field($instance['post_order']) : 'DESC';
        $product_order = !empty($instance['product_order']) ? sanitize_text_field($instance['product_order']) : 'DESC';
        $post_order_by = !empty($instance['post_order_by']) ? sanitize_text_field($instance['post_order_by']) : 'date';
        $product_order_by = !empty($instance['product_order_by']) ? sanitize_text_field($instance['product_order_by']) : 'date';

        echo $args['before_widget'];

        // Widget class
        $widget_class = 'widget_propost_recent_widget';
        if ($custom_class) {
            $widget_class .= ' ' . $custom_class;
        }
        echo '<div class="' . esc_attr($widget_class) . '">';

        // Widget title
        if ($title) {
            echo $args['before_title'] . apply_filters('widget_title', $title) . $args['after_title'];
        }

        // Widget content
        if ($display_posts) {
            echo '<' . $title_tag . ' class="widget-post-title">Recent Posts</' . $title_tag . '>';
            $this->display_recent_posts($title_tag, $add_title_attribute, $make_title_clickable, $num_posts, $post_order, $post_order_by);
        }

        if ($display_products) {
            echo '<' . $title_tag . ' class="widget-product-title">Recent Products</' . $title_tag . '>';
            $this->display_recent_products($title_tag, $add_title_attribute, $make_title_clickable, $num_posts, $product_order, $product_order_by);
        }

        echo '</div>';
        echo $args['after_widget'];
    }

    // Display recent posts
    private function display_recent_posts($title_tag, $add_title_attribute, $make_title_clickable, $num_posts, $post_order, $post_order_by) {
        $recent_posts_args = array(
            'numberposts' => $num_posts, // Number of posts to display
            'post_status' => 'publish',
            'order' => $post_order,
            'orderby' => $post_order_by,
        );

        if ($post_order_by == 'most_comments') {
            $recent_posts_args['orderby'] = 'comment_count';
        }

        $recent_posts = wp_get_recent_posts($recent_posts_args);

        if ($recent_posts) {
            echo '<ul>';

            foreach ($recent_posts as $post) {
                $title_attribute = $add_title_attribute ? ' title="' . esc_attr(get_the_title($post['ID'])) . '"' : '';
                $title_link = $make_title_clickable ? '<a href="' . get_permalink($post['ID']) . '"' . $title_attribute . '>' . get_the_title($post['ID']) . '</a>' : get_the_title($post['ID']);
                echo '<li><' . $title_tag . '>' . $title_link . '</' . $title_tag . '></li>';
            }

            echo '</ul>';
        } else {
            echo 'No recent posts found.';
        }
    }

    // Display recent WooCommerce products
    private function display_recent_products($title_tag, $add_title_attribute, $make_title_clickable, $num_posts, $product_order, $product_order_by) {
        if (class_exists('WooCommerce')) {
            $args = array(
                'post_type' => 'product',
                'posts_per_page' => $num_posts,
                'orderby' => $product_order_by,
                'order' => $product_order,
            );

            if ($product_order_by == 'product_reviews') {
                $args['orderby'] = 'comment_count';
            } elseif ($product_order_by == 'product_star_rating') {
                $args['orderby'] = 'meta_value_num';
                $args['meta_key'] = '_wc_average_rating';
            } elseif ($product_order_by == 'price') {
                $args['orderby'] = 'meta_value_num';
                $args['meta_key'] = '_price';
            }

            $recent_products = new WP_Query($args);

            if ($recent_products->have_posts()) {
                echo '<ul>';

                while ($recent_products->have_posts()) {
                    $recent_products->the_post();
                    $title_attribute = $add_title_attribute ? ' title="' . esc_attr(get_the_title()) . '"' : '';
                    $title_link = $make_title_clickable ? '<a href="' . get_permalink() . '"' . $title_attribute . '>' . get_the_title() . '</a>' : get_the_title();
                    echo '<li><' . $title_tag . '>' . $title_link . '</' . $title_tag . '></li>';
                }

                echo '</ul>';
            } else {
                echo 'No recent products found.';
            }

            wp_reset_postdata();
        } else {
            echo 'WooCommerce is not active.';
        }
    }

    // Widget backend
    public function form($instance) {
        $title = !empty($instance['title']) ? $instance['title'] : '';
        $display_posts = isset($instance['display_posts']) ? (bool) $instance['display_posts'] : false;
        $display_products = isset($instance['display_products']) ? (bool) $instance['display_products'] : false;
        $custom_class = !empty($instance['custom_class']) ? sanitize_text_field($instance['custom_class']) : '';
        $title_tag = !empty($instance['title_tag']) ? sanitize_text_field($instance['title_tag']) : 'h2';
        $add_title_attribute = isset($instance['add_title_attribute']) ? (bool) $instance['add_title_attribute'] : false;
        $make_title_clickable = isset($instance['make_title_clickable']) ? (bool) $instance['make_title_clickable'] : false;
        $num_posts = !empty($instance['num_posts']) ? absint($instance['num_posts']) : 5;
        $post_order = !empty($instance['post_order']) ? sanitize_text_field($instance['post_order']) : 'DESC';
        $product_order = !empty($instance['product_order']) ? sanitize_text_field($instance['product_order']) : 'DESC';
        $post_order_by = !empty($instance['post_order_by']) ? sanitize_text_field($instance['post_order_by']) : 'date';
        $product_order_by = !empty($instance['product_order_by']) ? sanitize_text_field($instance['product_order_by']) : 'date';

        ?>
        <p>
            <label for="<?php echo $this->get_field_id('title'); ?>">Title:</label>
            <input class="widefat" type="text" id="<?php echo $this->get_field_id('title'); ?>"
                   name="<?php echo $this->get_field_name('title'); ?>"
                   value="<?php echo esc_attr($title); ?>"/>
        </p>
        <p>
            <input class="checkbox" type="checkbox" <?php checked($display_posts); ?>
                   id="<?php echo $this->get_field_id('display_posts'); ?>"
                   name="<?php echo $this->get_field_name('display_posts'); ?>"/>
            <label for="<?php echo $this->get_field_id('display_posts'); ?>">Display Recent Posts</label>
        </p>
        <p>
            <input class="checkbox" type="checkbox" <?php checked($display_products); ?>
                   id="<?php echo $this->get_field_id('display_products'); ?>"
                   name="<?php echo $this->get_field_name('display_products'); ?>"/>
            <label for="<?php echo $this->get_field_id('display_products'); ?>">Display Recent Products</label>
        </p>
        <p>
            <label for="<?php echo $this->get_field_id('custom_class'); ?>">Custom Class:</label>
            <input class="widefat" type="text" id="<?php echo $this->get_field_id('custom_class'); ?>"
                   name="<?php echo $this->get_field_name('custom_class'); ?>"
                   value="<?php echo esc_attr($custom_class); ?>"/>
        </p>
        <p>
            <label for="<?php echo $this->get_field_id('title_tag'); ?>">Title HTML Tag:</label>
            <select class="widefat" id="<?php echo $this->get_field_id('title_tag'); ?>"
                    name="<?php echo $this->get_field_name('title_tag'); ?>">
                <option value="h1" <?php selected($title_tag, 'h1'); ?>>h1</option>
                <option value="h2" <?php selected($title_tag, 'h2'); ?>>h2</option>
                <option value="h3" <?php selected($title_tag, 'h3'); ?>>h3</option>
                <option value="h4" <?php selected($title_tag, 'h4'); ?>>h4</option>
                <option value="h5" <?php selected($title_tag, 'h5'); ?>>h5</option>
                <option value="h6" <?php selected($title_tag, 'h6'); ?>>h6</option>
                <option value="h7" <?php selected($title_tag, 'h7'); ?>>h7</option>
                <option value="span" <?php selected($title_tag, 'span'); ?>>span</option>
                <option value="p" <?php selected($title_tag, 'p'); ?>>p</option>
                <option value="div" <?php selected($title_tag, 'div'); ?>>div</option>
            </select>
        </p>
        <p>
            <input class="checkbox" type="checkbox" <?php checked($add_title_attribute); ?>
                   id="<?php echo $this->get_field_id('add_title_attribute'); ?>"
                   name="<?php echo $this->get_field_name('add_title_attribute'); ?>"/>
            <label for="<?php echo $this->get_field_id('add_title_attribute'); ?>">Add "title" attribute to post titles</label>
        </p>
        <p>
            <input class="checkbox" type="checkbox" <?php checked($make_title_clickable); ?>
                   id="<?php echo $this->get_field_id('make_title_clickable'); ?>"
                   name="<?php echo $this->get_field_name('make_title_clickable'); ?>"/>
            <label for="<?php echo $this->get_field_id('make_title_clickable'); ?>">Make post titles clickable</label>
        </p>
        <p>
            <label for="<?php echo $this->get_field_id('num_posts'); ?>">Number of Posts:</label>
            <input class="widefat" type="number" id="<?php echo $this->get_field_id('num_posts'); ?>"
                   name="<?php echo $this->get_field_name('num_posts'); ?>"
                   value="<?php echo esc_attr($num_posts); ?>" min="1" max="20"/>
        </p>
        <p>
            <label for="<?php echo $this->get_field_id('post_order'); ?>">Post Order:</label>
            <select class="widefat" id="<?php echo $this->get_field_id('post_order'); ?>"
                    name="<?php echo $this->get_field_name('post_order'); ?>">
                <option value="DESC" <?php selected($post_order, 'DESC'); ?>>Descending</option>
                <option value="ASC" <?php selected($post_order, 'ASC'); ?>>Ascending</option>
            </select>
        </p>
        <p>
            <label for="<?php echo $this->get_field_id('post_order_by'); ?>">Post Order By:</label>
            <select class="widefat" id="<?php echo $this->get_field_id('post_order_by'); ?>"
                    name="<?php echo $this->get_field_name('post_order_by'); ?>">
                <option value="date" <?php selected($post_order_by, 'date'); ?>>Date</option>
                <option value="rand" <?php selected($post_order_by, 'rand'); ?>>Random</option>
                <option value="most_comments" <?php selected($post_order_by, 'most_comments'); ?>>Most Comments</option>
            </select>
        </p>
        <p>
            <label for="<?php echo $this->get_field_id('product_order'); ?>">Product Order:</label>
            <select class="widefat" id="<?php echo $this->get_field_id('product_order'); ?>"
                    name="<?php echo $this->get_field_name('product_order'); ?>">
                <option value="DESC" <?php selected($product_order, 'DESC'); ?>>Descending</option>
                <option value="ASC" <?php selected($product_order, 'ASC'); ?>>Ascending</option>
            </select>
        </p>
        <p>
            <label for="<?php echo $this->get_field_id('product_order_by'); ?>">Product Order By:</label>
            <select class="widefat" id="<?php echo $this->get_field_id('product_order_by'); ?>"
                    name="<?php echo $this->get_field_name('product_order_by'); ?>">
                <option value="date" <?php selected($product_order_by, 'date'); ?>>Date</option>
                <option value="rand" <?php selected($product_order_by, 'rand'); ?>>Random</option>
                <option value="product_reviews" <?php selected($product_order_by, 'product_reviews'); ?>>Product Reviews</option>
                <option value="product_star_rating" <?php selected($product_order_by, 'product_star_rating'); ?>>Product Star Rating</option>
                <option value="price" <?php selected($product_order_by, 'price'); ?>>Price</option>
            </select>
        </p>
        <?php
    }

    // Update widget
    public function update($new_instance, $old_instance) {
        $instance = array();
        $instance['title'] = !empty($new_instance['title']) ? sanitize_text_field($new_instance['title']) : '';
        $instance['display_posts'] = isset($new_instance['display_posts']) ? (bool) $new_instance['display_posts'] : false;
        $instance['display_products'] = isset($new_instance['display_products']) ? (bool) $new_instance['display_products'] : false;
        $instance['custom_class'] = !empty($new_instance['custom_class']) ? sanitize_text_field($new_instance['custom_class']) : '';
        $instance['title_tag'] = !empty($new_instance['title_tag']) ? sanitize_text_field($new_instance['title_tag']) : 'h2';
        $instance['add_title_attribute'] = isset($new_instance['add_title_attribute']) ? (bool) $new_instance['add_title_attribute'] : false;
        $instance['make_title_clickable'] = isset($new_instance['make_title_clickable']) ? (bool) $new_instance['make_title_clickable'] : false;
        $instance['num_posts'] = !empty($new_instance['num_posts']) ? absint($new_instance['num_posts']) : 5;
        $instance['post_order'] = !empty($new_instance['post_order']) ? sanitize_text_field($new_instance['post_order']) : 'DESC';
        $instance['product_order'] = !empty($new_instance['product_order']) ? sanitize_text_field($new_instance['product_order']) : 'DESC';
        $instance['post_order_by'] = !empty($new_instance['post_order_by']) ? sanitize_text_field($new_instance['post_order_by']) : 'date';
        $instance['product_order_by'] = !empty($new_instance['product_order_by']) ? sanitize_text_field($new_instance['product_order_by']) : 'date';

        return $instance;
    }
}
