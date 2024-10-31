<?php
use Automattic\WooCommerce\Internal\DataStores\Orders\CustomOrdersTableController;

class OrderPickingApp_Admin
{
    public function __construct()
    {
        // HPOS
        add_filter('manage_woocommerce_page_wc-orders_columns', array($this, 'add_orderpickingapp_order_column') );
        add_action('manage_woocommerce_page_wc-orders_custom_column', array($this, 'add_orderpickingapp_order_column_value'), 10, 2);
        add_filter('bulk_actions-woocommerce_page_wc-orders', array($this, 'opa_register_bulk_action') );
        add_filter('handle_bulk_actions-woocommerce_page_wc-orders', array($this, 'opa_handle_bulk_actions_shop_order'), 20, 3 );
        add_action('woocommerce_update_order', array($this, 'save_opa_order_meta_box_data'));

        // OLD
        add_action('manage_edit-shop_order_columns', array($this, 'add_orderpickingapp_order_column'));
        add_action('manage_shop_order_posts_custom_column', array($this, 'add_orderpickingapp_order_column_value'));

        add_action('add_meta_boxes', array($this, 'opa_order_meta_box'));
        add_action('save_post_shop_order', array($this, 'save_opa_order_meta_box_data'));

        add_action('woocommerce_product_after_variable_attributes', array($this, 'opa_picking_location'), 10, 3);
        add_action('woocommerce_save_product_variation', array($this, 'save_opa_picking_location'), 10, 2);

        add_filter( "plugin_action_links_orderpickingapp_panel", array( $this, 'plugin_add_settings_link') );
    }

    public function opa_register_bulk_action( $bulk_actions ) {

        $app_users = get_transient('app_users');

        if (!isset($app_users) || !$app_users) {
            $orderpickingapp_apikey = get_option('orderpickingapp_apikey');
            if (isset($orderpickingapp_apikey) && !empty($orderpickingapp_apikey)) {
                $url = 'https://orderpickingapp.com/wp-json/account/v1/settings?token=' . $orderpickingapp_apikey;
                $ch = curl_init();
                curl_setopt($ch, CURLOPT_URL, $url);
                curl_setopt($ch, CURLOPT_HEADER, false);
                curl_setopt($ch, CURLOPT_REFERER, site_url());
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
                $response = curl_exec($ch);
                curl_close($ch);

                $response = json_decode($response, true);

                if (isset($response['settings']['app_users'])) {
                    set_transient('app_users', $response['settings']['app_users'], (86400 * 7));
                    $app_users = $response['settings']['app_users'];
                }
            }
        }

        foreach( $app_users as $app_user ){
            $app_user_key = strtolower($app_user['app_users_name']);
            $app_user_key = str_replace(' ', '_', $app_user_key);
            $bulk_actions[ 'assign_orders_to_'.$app_user_key ] = 'Assign order(s) to '.$app_user['app_users_name'];
        }

        return $bulk_actions;
    }

    public function opa_handle_bulk_actions_shop_order($redirect_url, $action, $order_ids){
        if( strpos($action, 'assign_orders_to') !== false && !empty($order_ids) ) {
            $user_claimed = str_replace('assign_orders_to_', '', $action);
            foreach( $order_ids as $order_id ) {
                update_post_meta($order_id, 'user_claimed', $user_claimed);
            }
        }
        return $redirect_url;
    }

    public function plugin_add_settings_link( $links ) {
        $settings_link = '<a href="admin.php?page=orderpickingapp_panel">' . __( 'Settings' ) . '</a>';
        array_unshift( $links, $settings_link );
        return $links;
    }

    public function opa_picking_location($loop, $variation_data, $variation)
    {

        $value = get_post_meta($variation->ID, 'opa_picking_location', true);
        if (empty($value)) $value = '';

        $picking_locations = get_terms('pickingroute', array(
            'hide_empty' => false,
            'orderby' => 'term_order',
            'order' => 'ASC'
        ));

        $options[''] = 'Select picking location';
        foreach ($picking_locations as $term) {

            $cat_path = get_term_parents_list($term->term_id, $term->taxonomy, array(
                'separator' => '/',
                'link' => false,
                'format' => 'name',
            ));

            $options[$term->term_id] = trim($cat_path, '/');
        }
        woocommerce_wp_select(array(
            'id' => 'opa_picking_location[' . $loop . ']',
            'label' => 'Picking location',
            'options' => $options,
            'value' => $value,
        ));

    }

    public function save_opa_picking_location($variation_id, $loop)
    {
        $select_field = !empty($_POST['opa_picking_location'][$loop]) ? $_POST['opa_picking_location'][$loop] : '';
        update_post_meta($variation_id, 'opa_picking_location', $select_field);
    }

    public function opa_order_meta_box()
    {
        $screen = class_exists( '\Automattic\WooCommerce\Internal\DataStores\Orders\CustomOrdersTableController' ) && wc_get_container()->get( CustomOrdersTableController::class )->custom_orders_table_usage_is_enabled()
            ? wc_get_page_screen_id( 'shop-order' )
            : 'shop_order';

        add_meta_box(
            'opa-order-meta-box',
            'Order Picking App',
            array($this, 'opa_order_meta_box_callback'),
            $screen,
            'side',
            'high'
        );
    }

    public function opa_order_meta_box_callback($post)
    {
        // Get the WC_Order object
        $order = is_a( $post, 'WP_Post' ) ? wc_get_order( $post->ID ) : $post;

        $user_claimed = get_post_meta($order->get_id(), 'user_claimed', true);
        $app_users = get_transient('app_users');

        if (!isset($app_users) || !$app_users) {

            $orderpickingapp_apikey = get_option('orderpickingapp_apikey');
            if (isset($orderpickingapp_apikey) && !empty($orderpickingapp_apikey)) {
                $url = 'https://orderpickingapp.com/wp-json/account/v1/settings?token=' . $orderpickingapp_apikey;
                $ch = curl_init();
                curl_setopt($ch, CURLOPT_URL, $url);
                curl_setopt($ch, CURLOPT_HEADER, false);
                curl_setopt($ch, CURLOPT_REFERER, site_url());
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
                $response = curl_exec($ch);
                curl_close($ch);

                $response = json_decode($response, true);

                if (isset($response['settings']['app_users'])) {
                    set_transient('app_users', $response['settings']['app_users'], (86400 * 7));
                    $app_users = $response['settings']['app_users'];
                }
            }
        }


        ob_start(); ?>

        <h5>Change or assign picker to order</h5>
        <div class="form-group">
            <select name="user_claimed">
                <option value="">Select picker</option>
                <?php foreach ($app_users as $app_user):

                    // Check for new settings with user_name and picking_amount
                    if (is_array($app_user)) {
                        $app_user = $app_user['app_users_name'];
                    }

                    $app_user_key = strtolower($app_user);
                    $app_user_key = str_replace(' ', '_', $app_user_key);
                    ?>
                    <option value="<?php echo $app_user_key; ?>" <?php echo (isset($user_claimed) && $user_claimed == $app_user_key) ? 'selected=true' : ''; ?>><?php echo $app_user; ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <?php
        echo ob_get_clean();
    }

    public function save_opa_order_meta_box_data($order_id)
    {
        if (isset($_POST['user_claimed'])) {
            update_post_meta($order_id, 'user_claimed', $_POST['user_claimed']);
        }
    }

    public function add_pickingroute_taxonomy()
    {

        register_taxonomy('pickingroute', 'product', array(
            'hierarchical' => true,
            'show_admin_column' => true,
            'show_in_nav_menus' => true,
            'labels' => array(
                'name' => 'Picking route',
                'singular_name' => 'Picking route',
                'search_items' => 'Search picking routes',
                'all_items' => 'All picking routes',
                'parent_item' => 'Parent picking route:',
                'parent_item_colon' => 'Parent picking route:',
                'edit_item' => 'Edit picking route',
                'update_item' => 'Update picking route',
                'add_new_item' => 'Add picking route',
                'new_item_name' => 'Add picking route',
                'menu_name' => __('Picking route'),
            ),
            'rewrite' => array(
                'slug' => 'pickingroute', // This controls the base slug that will display before each term
                'with_front' => false, // Don't display the category base before "/locations/"
                'hierarchical' => true // This will allow URL's like "/locations/boston/cambridge/"
            ),
        ));
    }

    public function missing_app_token_message()
    {

        // Get the API key from the options
        $orderpickingapp_apikey = get_option('orderpickingapp_apikey');

        // Check whether the saved key makes sense
        if (!isset($orderpickingapp_apikey) || empty($orderpickingapp_apikey)) {
            $this->create_message('error', __('Not activated, create a 30-day free trial to work faster and more accurately. <a class="btn button" style="margin-left: 10px;" href="' . admin_url() . '/admin.php?page=orderpickingapp-panel">Start free trial</a>', 'orderpickingapp'));
        }

    }

    private function create_message($status, $message)
    {
        // Check whether the values are entered
        if (isset($status) && !empty($status) && $status != '' && $status != null && isset($message) && !empty($message) && $message != '' && $message != null) {
            $status = strtolower($status);
            if ($status != "error" && $status != "success" && $status != "warning" && $status != "info") {
                $status = "info";
            }

            $class = 'notice notice-' . $status;
            printf('<div class="%1$s"><p>Order Picking App | %2$s</p></div>', esc_attr($class), $message);
        }
    }

    public function enqueue_plugin_styles_scripts()
    {
        if (is_admin()) {
            ?>
            <script type="text/javascript">
                var ajaxurl = '<?php echo admin_url('admin-ajax.php'); ?>';
            </script>
            <?php
        }
    }

    public function add_orderpickingapp_order_column($columns)
    {
        $columns['orderpickingapp'] = 'Order Picking';
        return $columns;
    }

    public function add_orderpickingapp_order_column_value($column, $order = '' )
    {
        global $post;


        if( empty($post) ){
            $order_id = $order->get_id();
        }
        else{
            $order_id = $post->ID;
        }

        if ($column == 'orderpickingapp') {
            $picker = get_post_meta($order_id, 'user_claimed', true);
            $picker = str_replace('_', ' ', $picker);
            if (!isset($picker) || empty($picker)) {
                $picker = 'Unassigned';
            }


            $packer = get_post_meta($order_id, 'packer', true);
            $packer = str_replace('_', ' ', $packer);
            if (!isset($packer) || empty($packer)) {
                $packer = 'Unassigned';
            }

            $picking_status = get_post_meta($order_id, 'picking_status', true);
            if (isset($picking_status) && !empty($picking_status)) {
                echo 'Status: ' . ucfirst($picking_status);
                echo '</br>';
            }
            else{
                echo 'Status: Open';
                echo '</br>';
            }

            echo 'Picker: ' . ucfirst($picker);

            if ($picking_status == 'completed') {
                echo '</br>';
                echo 'Packer: ' . ucfirst($packer);
            }

            $batch_id = get_post_meta($order_id, 'batch_id', true);
            if (isset($batch_id) && !empty($batch_id)) {
                echo '</br>';
                echo 'BATCH: ' . $batch_id;
            }
        }
    }

    public function add_orderpickingapp_admin_pages()
    {
        add_menu_page('Order Picking App', 'Order Picking App', 'manage_options', 'orderpickingapp-panel', array($this, 'orderpickingapp_settings_page'), plugin_dir_url(__FILE__) . 'images/dashicon.png', 99);
        add_submenu_page('orderpickingapp-panel', 'Packing', 'Packing', 'manage_options', 'orderpickingapp-packing', array($this, 'orderpickingapp_packing_page'));
    }

    public function display_orderpickingapp_panel_fields()
    {
        add_settings_section('orderpickingapp_section', '', null, 'orderpickingapp_options');
        add_settings_field('orderpickingapp_apikey', 'API key', array($this, 'display_apiKey_element'), 'orderpickingapp_options', 'orderpickingapp_section');
        register_setting('orderpickingapp_options', 'orderpickingapp_apikey');
    }

    public function display_apiKey_element()
    {
        $orderpickingapp_apikey = get_option('orderpickingapp_apikey');
        ?>
        <input type='text' name='orderpickingapp_apikey' id='orderpickingapp_apikey'
               value='<?php echo $orderpickingapp_apikey; ?>' style='width: 100%; min-wdith: 250px;'/>
        <hr>
        <?php
    }

    public function save_app_settings()
    {

        $data_array = array();
        $app_users_counter = 0;
        $app_users = array();
        $data_array['orderpickingapp_order_status'] = array();

        foreach ($_POST['data'] as $item) {

            if ($item['name'] == 'pickroute' && !empty($item['value'])) {
                foreach ($item['value'] as $value) {
                    if (strpos($value, '/') !== false) {
                        $values = explode('/', $value);

                        if (isset($data_array['pickroute'][$values[0]]) && !is_array($data_array['pickroute'][$values[0]])) {
                            $data_array['pickroute'][$values[0]] = array();
                            $data_array['pickroute'][$values[0]][$values[1]] = $values[1];

                            if (count($values) == 3) {
                                $data_array['pickroute'][$values[0]][$values[1]] = array();
                                $data_array['pickroute'][$values[0]][$values[1]][$values[2]] = $values[2];
                            } else {
                                $data_array['pickroute'][$values[0]][$values[1]] = $values[1];
                            }
                        } else {
                            if (count($values) == 3) {
                                $data_array['pickroute'][$values[0]][$values[1]][$values[2]] = $values[2];
                            } else {
                                $data_array['pickroute'][$values[0]][$values[1]] = $values[1];
                            }
                        }
                    } else {
                        $data_array['pickroute'][$value] = $value;
                    }
                }
            } elseif (str_contains($item['name'], 'app_users')) {

                if (isset($item['value']) && !empty($item['value'])) {
                    if (str_contains($item['name'], 'app_users_name')) {
                        $app_users[$app_users_counter]['app_users_name'] = $item['value'];
                    } else {
                        $app_users[$app_users_counter]['app_users_picking_amount'] = $item['value'];
                        $app_users_counter++;
                    }
                }
            } elseif (str_contains($item['name'], 'orderpickingapp_order_status')) {
                if (isset($item['value']) && !empty($item['value'])) {
                    $data_array['orderpickingapp_order_status'][$item['value']] = $item['value'];
                }
            } else {
                if (isset($item['value']) && !empty($item['value'])) {
                    $data_array[$item['name']] = $item['value'];
                    update_option($item['name'], $item['value']);
                } else {
                    $data_array[$item['name']] = '';
                    update_option($item['name'], '');
                }
            }
        }

        $data_array['orderpickingapp_order_status'] = array_values($data_array['orderpickingapp_order_status']);
        update_option('orderpickingapp_order_status', array_values($data_array['orderpickingapp_order_status']));

        if (!empty($app_users)) {
            $data_array['app_users'] = $app_users;
        }


        $url = 'https://orderpickingapp.com/wp-json/account/v1/settings?token=' . $_POST['token'];
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type:application/json'));
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data_array));
        curl_setopt($ch, CURLOPT_REFERER, site_url());
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $response = curl_exec($ch);
        curl_close($ch);

        $response = json_decode($response, true);

        $args = array(
            'status' => $response['status'],
            'message' => $response['message'],
        );
        wp_send_json($args);
    }

    public function reset_api_key()
    {
        delete_option('orderpickingapp_apikey');
    }

    public function create_user_account()
    {

        $data_array = array();

        foreach ($_POST['data'] as $item) {
            if (isset($item['value']) && !empty($item['value'])) {
                $data_array[$item['name']] = $item['value'];
            }
        }

        $url = 'https://orderpickingapp.com/wp-json/account/v1/create-user';
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type:application/json'));
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data_array));
        curl_setopt($ch, CURLOPT_REFERER, site_url());
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $response = curl_exec($ch);
        curl_close($ch);

        $response = json_decode($response, true);

        if (isset($response['token'])) {
            update_option('orderpickingapp_apikey', $response['token']);

            $args = array(
                'status' => $response['status'],
                'message' => '',
            );
            wp_send_json($args);
        } else {
            $args = array(
                'status' => $response['status'],
                'message' => $response['message'],
            );
            wp_send_json($args);
        }
    }

    public function orderpickingapp_settings_page()
    {
        require_once plugin_dir_path(__FILE__) . 'partials/orderpickingapp-settings-page.php';
    }

    public function orderpickingapp_packing_page()
    {
        require_once plugin_dir_path(__FILE__) . 'partials/orderpickingapp-packing-page.php';
    }

    public function get_all_meta_keys($post_type = 'post', $exclude_empty = false, $exclude_hidden = false)
    {
        global $wpdb;
        $query = "
        SELECT DISTINCT($wpdb->postmeta.meta_key) 
        FROM $wpdb->posts 
        LEFT JOIN $wpdb->postmeta 
        ON $wpdb->posts.ID = $wpdb->postmeta.post_id 
        WHERE $wpdb->posts.post_type = '%s'
    ";
        if ($exclude_empty)
            $query .= " AND $wpdb->postmeta.meta_key != ''";
        if ($exclude_hidden)
            $query .= " AND $wpdb->postmeta.meta_key NOT RegExp '(^[_0-9].+$)' ";

        $meta_keys = $wpdb->get_col($wpdb->prepare($query, $post_type));

        return $meta_keys;
    }

}