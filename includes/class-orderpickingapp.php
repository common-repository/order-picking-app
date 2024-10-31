<?php
class OrderPickingApp {

    protected $loader;
    private static $logPath;
    private static $logFileHandle = NULL;
    private $orderedCategories;
    private $productCategories;

        public function __construct(){
            $this->load_dependencies();
            $this->define_admin_hooks();

            $this->orderedCategories = array();
            $this->productCategories = array();

            $path = wp_get_upload_dir()['basedir'];
            if (is_dir($path)) {
                $pathComplete = $path;
                $subpath = '/orderpickingapp/logs';
                if (is_dir($pathComplete . $subpath)) {
                    $pathComplete .= $subpath;
                } else {
                    foreach (explode("/", $subpath) as $subpath_part) {
                        if (strlen($subpath_part) > 0) {
                            if (!is_dir($pathComplete . "/" . $subpath_part)) {
                                if (!mkdir($pathComplete . "/" . $subpath_part, 0777)) {
                                    exit("Failed to create the subdirectory: " . $pathComplete . "/" . $subpath_part . "(" . error_get_last()['message'] . ")");
                                }
                            }
                            $pathComplete .= '/' . $subpath_part;
                        }
                    }
                }

                self::$logPath = $pathComplete;
                $logFilePath = $pathComplete . '/' . date('Y-m-d') . '.log';
                $logFileHandle = fopen($logFilePath, 'a');
                if ($logFileHandle === false) {
                    exit("Failed to open the logfile for: " . $logFilePath . "(" . error_get_last()['message'] . ")");
                } else {
                    self::$logFileHandle = $logFileHandle;
                }
            } else {
                exit("Failed to create logs in directory: " . $path);
            }

            add_action('woocommerce_admin_order_item_headers', array($this, 'orderpickingapp_admin_order_item_headers'));
            add_action('woocommerce_admin_order_item_values', array($this, 'orderpickingapp_admin_order_item_values'), 10, 3);

            add_filter('woocommerce_get_wp_query_args', function ($wp_query_args, $query_vars) {
                if (isset($query_vars['meta_query'])) {
                    $meta_query = isset($wp_query_args['meta_query']) ? $wp_query_args['meta_query'] : [];
                    $wp_query_args['meta_query'] = array_merge($meta_query, $query_vars['meta_query']);
                }

                return $wp_query_args;
            }, 10, 2);
        }

        public
        function orderpickingapp_admin_order_item_headers()
        {
            echo '<th>Picking</th>';
        }

        public
        function orderpickingapp_admin_order_item_values($_product, $item, $item_id = null)
        {
            $picking_status = get_post_meta($item_id, 'picking_status', true);
            $backorder = get_post_meta($item_id, 'backorder', true);

            try {
                $user_claimed = get_post_meta($item->get_order_id(), 'user_claimed', true);
            } catch (myCustomException $e) {
                $user_claimed = '-';
            }

            if (isset($picking_status) && !empty($picking_status)) {
                echo '<td>Status: ' . $picking_status . '</br>Backorders: ' . $backorder . '</br>Picker: ' . ucfirst($user_claimed) . '</td>';
            } else {
                echo '<td></td>';
            }

        }

        private
        function load_dependencies()
        {

            // Plugin defaults
            require_once plugin_dir_path(dirname(__FILE__)) . 'vendor/autoload.php';
            require_once plugin_dir_path(dirname(__FILE__)) . 'admin/class-orderpickingapp-admin.php';
            require_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-orderpickingapp-loader.php';
            require_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-feedback.php';

            $this->loader = new OrderPickingApp_Loader();
        }

        private
        function define_admin_hooks()
        {

            // Default actions
            $admin = new OrderPickingApp_Admin();
            $this->loader->add_action('admin_enqueue_scripts', $admin, 'enqueue_plugin_styles_scripts');
            $this->loader->add_action('admin_menu', $admin, 'add_orderpickingapp_admin_pages');
            $this->loader->add_action('admin_init', $admin, 'display_orderpickingapp_panel_fields');
            $this->loader->add_action('admin_notices', $admin, 'missing_app_token_message');
            $this->loader->add_action('wp_ajax_save_app_settings', $admin, 'save_app_settings');
            $this->loader->add_action('wp_ajax_reset_api_key', $admin, 'reset_api_key');
            $this->loader->add_action('wp_ajax_create_user_account', $admin, 'create_user_account');
            $this->loader->add_action('init', $admin, 'add_pickingroute_taxonomy');

            $this->loader->add_action('plugins_loaded', $this, 'mc_load_textdomain');
            $this->loader->add_action('rest_api_init', $this, 'wp_register_endpoints');

            $uninstall_feedback = new OPA_Uninstall_Feedback();
        }

        public
        function run()
        {
            $this->loader->run();
        }

        public
        function mc_load_textdomain()
        {
            $plugin_rel_path = basename(dirname(__FILE__)) . '/languages';
            load_plugin_textdomain('orderpickingapp', false, $plugin_rel_path);
        }

        public
        function wp_register_endpoints()
        {

            register_rest_route('picking/v1', '/get-settings', array(
                'methods' => 'GET',
                'callback' => array($this, 'getSettings'),
                'permission_callback' => '__return_true',
            ));

            register_rest_route('picking/v1', '/get-order-products', array(
                'methods' => 'GET',
                'callback' => array($this, 'getOrderProducts'),
                'permission_callback' => '__return_true',
            ));

            register_rest_route('picking/v1', '/pickinglist', array(
                'methods' => 'GET',
                'callback' => array($this, 'getPickingList'),
                'permission_callback' => '__return_true',
            ));

            register_rest_route('picking/v1', '/update-order-products', array(
                'methods' => 'POST',
                'callback' => array($this, 'updateOrderProducts'),
                'permission_callback' => '__return_true',
            ));

            register_rest_route('picking/v1', '/reset-order-products', array(
                'methods' => 'GET',
                'callback' => array($this, 'resetOrderProducts'),
                'permission_callback' => '__return_true',
            ));

            register_rest_route('picking/v1', '/get-packing-orders', array(
                'methods' => 'GET',
                'callback' => array($this, 'requestPackingOrders'),
                'permission_callback' => '__return_true',
            ));

            register_rest_route('picking/v1', '/update-order-status', array(
                'methods' => 'POST',
                'callback' => array($this, 'updateOrderStatus'),
                'permission_callback' => '__return_true',
            ));

            register_rest_route('picking/v1', '/create-order-note', array(
                'methods' => 'POST',
                'callback' => array($this, 'createOrderNote'),
                'permission_callback' => '__return_true',
            ));

            register_rest_route('picking/v1', '/get-categories', array(
                'methods' => 'GET',
                'callback' => array($this, 'getCategories'),
                'permission_callback' => '__return_true',
            ));

            register_rest_route('picking/v1', '/get-product', array(
                'methods' => 'GET',
                'callback' => array($this, 'getProduct'),
                'permission_callback' => '__return_true',
            ));
            register_rest_route('picking/v1', '/update-product', array(
                'methods' => 'POST',
                'callback' => array($this, 'updateProduct'),
                'permission_callback' => '__return_true',
            ));

            register_rest_route('picking/v1', '/reset-picking-orders', array(
                'methods' => 'GET',
                'callback' => array($this, 'resetPickingOrders'),
                'permission_callback' => '__return_true',
            ));
        }

        public
        function getSettings($request)
        {

            header('Access-Control-Allow-Origin: *');
            header("Access-Control-Allow-Methods: GET");

            $Products = array();
            if (class_exists('WooCommerce')) {

                $token = $request->get_param('token');

                if (isset($token)) {

                    $orderpickingapp_apikey = get_option('orderpickingapp_apikey');
                    if (isset($orderpickingapp_apikey) && $token == $orderpickingapp_apikey) {

                        $post_status = array('wc-processing');
                        $orderpickingapp_order_status = get_option('orderpickingapp_order_status');
                        if (isset($orderpickingapp_order_status) && !empty($orderpickingapp_order_status)) {
                            $post_status = (array)$orderpickingapp_order_status;
                        }

                        $order_statussen = array();
                        foreach ($post_status as $status) {
                            $order_statussen[] = str_replace('wc-', '', $status);
                        }

                        $args = array(
                            'status' => $order_statussen,
                            'limit' => -1,
                            'meta_query' => array(
                                'relation' => 'OR',
                                array(
                                    'relation' => 'AND',
                                    array(
                                        'key' => 'picking_status',
                                        'value' => 'packing',
                                        'compare' => '!=',
                                    ),
                                    array(
                                        'key' => 'picking_status',
                                        'value' => 'completed',
                                        'compare' => '!=',
                                    ),
                                ),
                                array(
                                    'key' => 'picking_status',
                                    'compare' => 'NOT EXISTS',
                                )
                            ),
                            'order' => 'ASC',
                        );

                        $pickingDate = get_option('pickingDate');
                        if (isset($pickingDate) && !empty($pickingDate)) {
                            $args['date_query'] = array(
                                array(
                                    'after' => $pickingDate,
                                ),
                            );
                        }

                        $query = new WC_Order_Query($args);
                        $picking_orders = $query->get_orders();

                        $total_picking_orders = count($picking_orders);

                        $user_orders = array();
                        foreach ($picking_orders as $picking_order) {
                            $user_claimed = get_post_meta($picking_order->get_id(), 'user_claimed', true);

                            if (isset($user_claimed) && !empty($user_claimed) ) {
                                if (isset($user_orders[$user_claimed])) {
                                    $user_orders[$user_claimed] = $user_orders[$user_claimed] + 1;
                                } else {
                                    $user_orders[$user_claimed] = 1;
                                }
                            }

                        }

                        $args = array(
                            'status' => $order_statussen,
                            'limit' => -1,
                            'meta_query' => array(
                                array(
                                    'key' => 'picking_status',
                                    'value' => 'packing',
                                    'compare' => '=',
                                )
                            ),

                        );
                        $query = new WC_Order_Query($args);
                        $packing_orders = $query->get_orders();
                        $total_packing_orders = count($packing_orders);
                        $total_backorders = 0;

                        if (count($packing_orders) > 0) {
                            foreach ($packing_orders as $packing_order) {
                                foreach ($packing_order->get_items() as $item_id => $item) {
                                    $backorder = get_post_meta($item_id, 'backorder', true);

                                    if ($backorder !== '0' && !empty($backorder)) {
                                        $total_backorders++;
                                        break;
                                    }

                                }
                            }
                        }

                        $output = array(
                            'total_picking_orders' => (int)$total_picking_orders,
                            'total_packing_orders' => (int)$total_packing_orders,
                            'total_backorders' => (int)$total_backorders,
                            'user_orders' => $user_orders
                        );
                        wp_send_json($output);
                    } else {
                        header("HTTP/1.1 401 Unauthorized");
                        exit;
                    }
                } else {
                    header("HTTP/1.1 401 Unauthorized");
                    exit;
                }
            }
        }

        public
        function updateOrderProducts($request)
        {

            header('Access-Control-Allow-Origin: *');
            header("Access-Control-Allow-Credentials: true");
            header('Access-Control-Allow-Methods: POST');
            header('Access-Control-Max-Age: 1000');
            header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token , Authorization');

            $body = $request->get_body();
            $data = json_decode($body, true);

            if (isset($data[0]['orderid'])) {
                $data = $data[0];
            }

            if (class_exists('WooCommerce')) {
                $token = $request->get_param('token');
                if (isset($token)) {
                    $orderpickingapp_apikey = get_option('orderpickingapp_apikey');
                    if (isset($orderpickingapp_apikey) && $token == $orderpickingapp_apikey) {

                        $currency_symbol = get_woocommerce_currency_symbol();
                        $currency_symbol = html_entity_decode($currency_symbol);

                        if (isset($data['products']) && !empty($data['products'])) {

                            $products = json_decode($data['products'], true);

                            // Iterate picked products
                            $order_notes_done = array();
                            foreach ($products as $product) {

                                $picked_amount = $product['ordered'] - $product['backorder'];

                                // Iterate related orders for this product
                                foreach ($product['orders'] as $order) {
                                    $Order = new WC_Order($order['orderid']);

                                    if (!in_array($order['orderid'], $order_notes_done)) {
                                        $Order->add_order_note('Order Picking App | Order picking completed by ' . $data['appuser'] . '. Order now available for packing.');
                                        $Order->update_meta_data('picking_status', $data['status']);
                                        $this->log('Order picking completed for order:  ' . $order['orderid'] . ' by ' . $data['appuser'], 'Info');
                                        $Order->save();

                                        $order_notes_done[$order['orderid']] = $order['orderid'];
                                    }

                                    // Check for existing product inside order
                                    foreach ($Order->get_items() as $item_id => $item) {
                                        $product_processed_stock = $item->get_quantity();

                                        // Set order item/product status to picked
                                        $variation_id = $item->get_variation_id();
                                        if (isset($variation_id) && !empty($variation_id)) {
                                            $product_id = $variation_id;
                                        } else {
                                            $product_id = $item->get_product_id();
                                        }

                                        if (isset($product_id) && $product_id == $product['product_id']) {
                                            if ($product['backorder'] == 0) {
                                                update_post_meta($item_id, 'picking_status', 'completed');
                                                update_post_meta($item_id, 'backorder', '0');
                                            } elseif ($picked_amount >= $product_processed_stock) {
                                                $picked_amount = $picked_amount - $product_processed_stock;
                                                update_post_meta($item_id, 'picking_status', 'completed');
                                                update_post_meta($item_id, 'backorder', '0');
                                            } else {
                                                update_post_meta($item_id, 'picking_status', 'backorder');
                                                update_post_meta($item_id, 'backorder', $product['backorder']);
                                            }
                                            break;
                                        } elseif ((!isset($product_id) || empty($product_id)) && $item_id == $product['product_id']) {
                                            if ($product['backorder'] == 0) {
                                                update_post_meta($item_id, 'picking_status', 'completed');
                                                update_post_meta($item_id, 'backorder', '0');
                                            } elseif ($picked_amount >= $product_processed_stock) {
                                                $picked_amount = $picked_amount - $product_processed_stock;
                                                update_post_meta($item_id, 'picking_status', 'completed');
                                                update_post_meta($item_id, 'backorder', '0');
                                            } else {
                                                update_post_meta($item_id, 'picking_status', 'backorder');
                                                update_post_meta($item_id, 'backorder', $product['backorder']);
                                            }
                                            break;
                                        }
                                    }
                                }
                            }
                        }

                        $post_status = array('wc-processing');
                        $orderpickingapp_order_status = get_option('orderpickingapp_order_status');
                        if (isset($orderpickingapp_order_status) && !empty($orderpickingapp_order_status)) {
                            $post_status = (array)$orderpickingapp_order_status;
                        }

                        $order_statussen = array();
                        foreach ($post_status as $status) {
                            $order_statussen[] = str_replace('wc-', '', $status);
                        }

                        $args = array(
                            'status' => $order_statussen,
                            'limit' => -1,
                            'meta_query' => array(
                                'relation' => 'OR',
                                array(
                                    'relation' => 'AND',
                                    array(
                                        'key' => 'picking_status',
                                        'value' => 'packing',
                                        'compare' => '!=',
                                    ),
                                    array(
                                        'key' => 'picking_status',
                                        'value' => 'completed',
                                        'compare' => '!=',
                                    ),
                                ),
                                array(
                                    'key' => 'picking_status',
                                    'compare' => 'NOT EXISTS',
                                )
                            ),
                            'order' => 'ASC',
                        );

                        $pickingDate = get_option('pickingDate');
                        if (isset($pickingDate) && !empty($pickingDate)) {
                            $args['date_query'] = array(
                                array(
                                    'after' => $pickingDate,
                                ),
                            );
                        }

                        $query = new WC_Order_Query($args);
                        $shop_orders = $query->get_orders();
                        $total_picking_orders = count($shop_orders);

                        $open_picking_orders = array();
                        foreach ($shop_orders as $total_picking_order) {
                            $total_picking_order_id = $total_picking_order->get_id();

                            $total_items = 0;
                            foreach ($total_picking_order->get_items() as $item_id => $item) {
                                $product_id = $item->get_product_id();
                                if (is_plugin_active('woocommerce-product-bundles/woocommerce-product-bundles.php') && !empty($product_id)) {
                                    $product_info = wc_get_product($product_id);
                                    $product_type = $product_info->get_type();
                                    if (!in_array($product_type, array('woosb', 'bundle', 'grouped'))) {
                                        $total_items = $total_items + $item->get_quantity();
                                    }
                                } else {
                                    $total_items = $total_items + $item->get_quantity();
                                }
                            }

                            $lastname = substr($total_picking_order->get_shipping_first_name(), 0, 1) . '. ' . $total_picking_order->get_shipping_last_name();
                            if (empty($lastname)) {
                                $lastname = substr($total_picking_order->get_billing_first_name(), 0, 1) . '. ' . $total_picking_order->get_billing_last_name();
                            }

                            $order_number = $total_picking_order_id;
                            $order_prefix = get_option('order_prefix');
                            if( isset($order_prefix) && !empty($order_prefix) ){
                                $order_number = $order_prefix . $total_picking_order_id;
                            }

                            $open_picking_orders[] = array(
                                'orderid' => $total_picking_order_id,
                                'order_number' => $order_number,
                                'date' => substr($total_picking_order->get_date_created(), 5, 5),
                                'items' => $total_items,
                                'total' => $currency_symbol . ' ' . $total_picking_order->get_total(),
                                'lastname' => $lastname,
                                'claimed_by' => get_post_meta($total_picking_order_id, 'user_claimed', true),
                            );
                        }

                        $args = array(
                            'status' => $order_statussen,
                            'limit' => -1,
                            'meta_query' => array(
                                array(
                                    'key' => 'picking_status',
                                    'value' => 'packing',
                                    'compare' => '=',
                                )
                            )
                        );
                        $query = new WC_Order_Query($args);
                        $packing_orders = $query->get_orders();
                        $total_packing_orders = count($packing_orders);
                        $total_backorders = 0;

                        if (count($packing_orders) > 0) {
                            foreach ($packing_orders as $packing_order) {
                                foreach ($packing_order->get_items() as $item_id => $item) {
                                    $backorder = get_post_meta($item_id, 'backorder', true);

                                    if ($backorder !== '0' && !empty($backorder)) {
                                        $total_backorders++;
                                        break;
                                    }

                                }
                            }
                        }

                        $output = array(
                            'total_picking_orders' => (int)$total_picking_orders,
                            'total_packing_orders' => (int)$total_packing_orders,
                            'total_backorders' => (int)$total_backorders,
                            'open_picking_orders' => $open_picking_orders
                        );
                        wp_send_json($output);
                    } else {
                        header("HTTP/1.1 401 Unauthorized");
                        exit;
                    }
                } else {
                    header("HTTP/1.1 401 Unauthorized");
                    exit;
                }
            }
            exit;
        }

        public
        function resetPickingOrders($request)
        {

            header('Access-Control-Allow-Origin: *');
            header("Access-Control-Allow-Methods: GET");

            if (class_exists('WooCommerce')) {
                $token = $request->get_param('token');
                $appuser = $request->get_param('appuser');


                if (isset($token)) {
                    $orderpickingapp_apikey = get_option('orderpickingapp_apikey');
                    if (isset($orderpickingapp_apikey) && $token == $orderpickingapp_apikey) {

                        $post_status = array('wc-processing');
                        $orderpickingapp_order_status = get_option('orderpickingapp_order_status');
                        if (isset($orderpickingapp_order_status) && !empty($orderpickingapp_order_status)) {
                            $post_status = (array)$orderpickingapp_order_status;
                        }

                        $order_statussen = array();
                        foreach ($post_status as $status) {
                            $order_statussen[] = str_replace('wc-', '', $status);
                        }

                        $args = array(
                            'status' => $order_statussen,
                            'limit' => -1,
                            'meta_query' => array(
                                'relation' => 'OR',
                                array(
                                    'relation' => 'AND',
                                    array(
                                        'key' => 'picking_status',
                                        'value' => 'packing',
                                        'compare' => '!=',
                                    ),
                                    array(
                                        'key' => 'picking_status',
                                        'value' => 'completed',
                                        'compare' => '!=',
                                    ),
                                ),
                                array(
                                    'key' => 'picking_status',
                                    'compare' => 'NOT EXISTS',
                                )
                            ),
                            'order' => 'ASC',
                        );

                        $pickingDate = get_option('pickingDate');
                        if (isset($pickingDate) && !empty($pickingDate)) {
                            $args['date_query'] = array(
                                array(
                                    'after' => $pickingDate,
                                ),
                            );
                        }

                        $query = new WC_Order_Query($args);
                        $picking_orders = $query->get_orders();

                        foreach ($picking_orders as $picking_order) {
                            $picking_order_id = $picking_order->get_id();

                            if (!empty($appuser)) {
                                $user_claimed = get_post_meta($picking_order_id, 'user_claimed', true);
                                if (isset($user_claimed) && !empty($user_claimed) && $user_claimed != $appuser) {
                                    continue;
                                }
                            }

                            delete_post_meta($picking_order_id, 'picking_status');
                            delete_post_meta($picking_order_id, 'user_claimed');
                            delete_post_meta($picking_order_id, 'batch_id');
                        }

                    } else {
                        header("HTTP/1.1 401 Unauthorized");
                        exit;
                    }
                } else {
                    header("HTTP/1.1 401 Unauthorized");
                    exit;
                }
            }
            exit;
        }

        public
        function resetOrderProducts($request)
        {

            header('Access-Control-Allow-Origin: *');
            header("Access-Control-Allow-Methods: GET");

            if (class_exists('WooCommerce')) {
                $token = $request->get_param('token');
                $orderid = $request->get_param('orderid');
                $appuser = $request->get_param('appuser');
                if (isset($token)) {
                    $orderpickingapp_apikey = get_option('orderpickingapp_apikey');
                    if (isset($orderpickingapp_apikey) && $token == $orderpickingapp_apikey) {

                        $currency_symbol = get_woocommerce_currency_symbol();
                        $currency_symbol = html_entity_decode($currency_symbol);

                        $Order = new WC_Order($orderid);

                        $Order->add_order_note('Order Picking App | Order picking reset by ' . $appuser . '. Order reset to picking list.');
                        delete_post_meta($orderid, 'picking_status');
                        $this->log('Order picking reset for order:  ' . $orderid . ' by ' . $appuser, 'Info');

                        foreach ($Order->get_items() as $item_id => $item) {
                            delete_post_meta($item_id, 'picking_status');
                            delete_post_meta($item_id, 'backorder');
                        }

                        $post_status = array('wc-processing');
                        $orderpickingapp_order_status = get_option('orderpickingapp_order_status');
                        if (isset($orderpickingapp_order_status) && !empty($orderpickingapp_order_status)) {
                            $post_status = (array)$orderpickingapp_order_status;
                        }

                        $order_statussen = array();
                        foreach ($post_status as $status) {
                            $order_statussen[] = str_replace('wc-', '', $status);
                        }

                        $args = array(
                            'status' => $order_statussen,
                            'limit' => -1,
                            'meta_query' => array(
                                'relation' => 'OR',
                                array(
                                    'relation' => 'AND',
                                    array(
                                        'key' => 'picking_status',
                                        'value' => 'packing',
                                        'compare' => '!=',
                                    ),
                                    array(
                                        'key' => 'picking_status',
                                        'value' => 'completed',
                                        'compare' => '!=',
                                    ),
                                ),
                                array(
                                    'key' => 'picking_status',
                                    'compare' => 'NOT EXISTS',
                                )
                            ),
                            'order' => 'ASC',
                        );

                        $pickingDate = get_option('pickingDate');
                        if (isset($pickingDate) && !empty($pickingDate)) {
                            $args['date_query'] = array(
                                array(
                                    'after' => $pickingDate,
                                ),
                            );
                        }

                        $query = new WC_Order_Query($args);
                        $shop_orders = $query->get_orders();
                        $total_picking_orders = count($shop_orders);

                        $open_picking_orders = array();
                        foreach ($shop_orders as $total_picking_order) {
                            $total_picking_order_id = $total_picking_order->get_id();

                            $total_items = 0;
                            foreach ($total_picking_order->get_items() as $item_id => $item) {
                                if (is_plugin_active('woocommerce-product-bundles/woocommerce-product-bundles.php')) {
                                    $product_id = $item->get_product_id();
                                    $product_info = wc_get_product($product_id);
                                    $product_type = $product_info->get_type();
                                    if (!in_array($product_type, array('woosb', 'bundle', 'grouped'))) {
                                        $total_items = $total_items + $item->get_quantity();
                                    }
                                } else {
                                    $total_items = $total_items + $item->get_quantity();
                                }
                            }

                            $lastname = substr($total_picking_order->get_shipping_first_name(), 0, 1) . '. ' . $total_picking_order->get_shipping_last_name();
                            if (empty($lastname)) {
                                $lastname = substr($total_picking_order->get_billing_first_name(), 0, 1) . '. ' . $total_picking_order->get_billing_last_name();
                            }

                            $order_number = $total_picking_order_id;
                            $order_prefix = get_option('order_prefix');
                            if( isset($order_prefix) && !empty($order_prefix) ){
                                $order_number = $order_prefix . $total_picking_order_id;
                            }

                            $open_picking_orders[] = array(
                                'orderid' => $total_picking_order_id,
                                'order_number' => $order_number,
                                'date' => substr($total_picking_order->get_date_created(), 5, 5),
                                'items' => $total_items,
                                'total' => $currency_symbol . ' ' . $total_picking_order->get_total(),
                                'lastname' => $lastname,
                                'claimed_by' => get_post_meta($total_picking_order_id, 'user_claimed', true),
                            );
                        }

                        $args = array(
                            'status' => $order_statussen,
                            'limit' => -1,
                            'meta_query' => array(
                                array(
                                    'key' => 'picking_status',
                                    'value' => 'packing',
                                    'compare' => '=',
                                )
                            )
                        );
                        $query = new WC_Order_Query($args);
                        $packing_orders = $query->get_orders();
                        $total_packing_orders = count($packing_orders);
                        $total_backorders = 0;

                        if (count($packing_orders) > 0) {
                            foreach ($packing_orders as $packing_order) {
                                foreach ($packing_order->get_items() as $item_id => $item) {
                                    $backorder = get_post_meta($item_id, 'backorder', true);

                                    if ($backorder !== '0' && !empty($backorder)) {
                                        $total_backorders++;
                                        break;
                                    }

                                }
                            }
                        }

                        $output = array(
                            'total_picking_orders' => (int)$total_picking_orders,
                            'total_packing_orders' => (int)$total_packing_orders,
                            'total_backorders' => (int)$total_backorders,
                            'open_picking_orders' => $open_picking_orders
                        );
                        wp_send_json($output);
                    } else {
                        header("HTTP/1.1 401 Unauthorized");
                        exit;
                    }
                } else {
                    header("HTTP/1.1 401 Unauthorized");
                    exit;
                }
            }
            exit;
        }

        public
        function updateOrderStatus($request)
        {

            header('Access-Control-Allow-Origin: *');
            header("Access-Control-Allow-Credentials: true");
            header('Access-Control-Allow-Methods: POST');
            header('Access-Control-Max-Age: 1000');
            header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token , Authorization');

            $body = $request->get_body();
            $data = json_decode($body, true);

            if (isset($data[0]['orderid'])) {
                $data = $data[0];
            }

            if (class_exists('WooCommerce')) {

                $token = $request->get_param('token');
                if (isset($token)) {

                    $orderpickingapp_apikey = get_option('orderpickingapp_apikey');
                    $auto_completed_order = get_option('auto_completed_order');
                    if (isset($orderpickingapp_apikey) && $token == $orderpickingapp_apikey) {

                        $currency_symbol = get_woocommerce_currency_symbol();
                        $currency_symbol = html_entity_decode($currency_symbol);

                        if (isset($data['orderid']) && !empty($data['orderid'])) {
                            $Order = new WC_Order($data['orderid']);
                            if (!empty($Order)) {

                                $Order->add_order_note('Order Picking App | Order packing completed by ' . $data['appuser']);
                                $Order->update_meta_data('picking_status', $data['status']);

                                if (isset($data['status']) && $data['status'] == 'completed' && isset($auto_completed_order) && $auto_completed_order == 'yes') {
                                    $Order->update_status('completed');
                                }

                                $this->log('Order packing completed for order:  ' . $data['orderid'] . ' by ' . $data['appuser'], 'Info');
                                $Order->save();

                                $post_status = array('wc-processing');
                                $orderpickingapp_order_status = get_option('orderpickingapp_order_status');
                                if (isset($orderpickingapp_order_status) && !empty($orderpickingapp_order_status)) {
                                    $post_status = (array)$orderpickingapp_order_status;
                                }

                                $order_statussen = array();
                                foreach ($post_status as $status) {
                                    $order_statussen[] = str_replace('wc-', '', $status);
                                }

                                $args = array(
                                    'status' => $order_statussen,
                                    'limit' => -1,
                                    'meta_query' => array(
                                        'relation' => 'OR',
                                        array(
                                            'relation' => 'AND',
                                            array(
                                                'key' => 'picking_status',
                                                'value' => 'packing',
                                                'compare' => '!=',
                                            ),
                                            array(
                                                'key' => 'picking_status',
                                                'value' => 'completed',
                                                'compare' => '!=',
                                            ),
                                        ),
                                        array(
                                            'key' => 'picking_status',
                                            'compare' => 'NOT EXISTS',
                                        )
                                    ),
                                    'order' => 'ASC',
                                );

                                $pickingDate = get_option('pickingDate');
                                if (isset($pickingDate) && !empty($pickingDate)) {
                                    $args['date_query'] = array(
                                        array(
                                            'after' => $pickingDate,
                                        ),
                                    );
                                }

                                $query = new WC_Order_Query($args);
                                $shop_orders = $query->get_orders();
                                $total_picking_orders = count($shop_orders);

                                $open_picking_orders = array();
                                foreach ($shop_orders as $total_picking_order) {
                                    $total_picking_order_id = $total_picking_order->get_id();

                                    $total_items = 0;
                                    foreach ($total_picking_order->get_items() as $item_id => $item) {
                                        if (is_plugin_active('woocommerce-product-bundles/woocommerce-product-bundles.php')) {
                                            $product_id = $item->get_product_id();
                                            $product_info = wc_get_product($product_id);
                                            $product_type = $product_info->get_type();
                                            if (!in_array($product_type, array('woosb', 'bundle', 'grouped'))) {
                                                $total_items = $total_items + $item->get_quantity();
                                            }
                                        } else {
                                            $total_items = $total_items + $item->get_quantity();
                                        }
                                    }

                                    $lastname = substr($total_picking_order->get_shipping_first_name(), 0, 1) . '. ' . $total_picking_order->get_shipping_last_name();
                                    if (empty($lastname)) {
                                        $lastname = substr($total_picking_order->get_billing_first_name(), 0, 1) . '. ' . $total_picking_order->get_billing_last_name();
                                    }

                                    $order_number = $total_picking_order_id;
                                    $order_prefix = get_option('order_prefix');
                                    if( isset($order_prefix) && !empty($order_prefix) ){
                                        $order_number = $order_prefix . $total_picking_order_id;
                                    }

                                    $open_picking_orders[] = array(
                                        'orderid' => $total_picking_order_id,
                                        'order_number' => $order_number,
                                        'date' => substr($total_picking_order->get_date_created(), 5, 5),
                                        'items' => $total_items,
                                        'total' => $currency_symbol . ' ' . $total_picking_order->get_total(),
                                        'lastname' => $lastname,
                                        'claimed_by' => get_post_meta($total_picking_order_id, 'user_claimed', true),
                                    );
                                }

                                $args = array(
                                    'status' => $order_statussen,
                                    'limit' => -1,
                                    'meta_query' => array(
                                        array(
                                            'key' => 'picking_status',
                                            'value' => 'packing',
                                            'compare' => '=',
                                        )
                                    )
                                );
                                $query = new WC_Order_Query($args);
                                $packing_orders = $query->get_orders();
                                $total_packing_orders = count($packing_orders);
                                $total_backorders = 0;

                                if (count($packing_orders) > 0) {
                                    foreach ($packing_orders as $packing_order) {
                                        foreach ($packing_order->get_items() as $item_id => $item) {
                                            $backorder = get_post_meta($item_id, 'backorder', true);

                                            if ($backorder !== '0' && !empty($backorder)) {
                                                $total_backorders++;
                                                break;
                                            }

                                        }
                                    }
                                }

                                $output = array(
                                    'total_picking_orders' => (int)$total_picking_orders,
                                    'total_packing_orders' => (int)$total_packing_orders,
                                    'total_backorders' => (int)$total_backorders,
                                    'open_picking_orders' => $open_picking_orders,
                                );
                                wp_send_json($output);
                            } else {
                                echo 'Unknown order id!';
                                exit;
                            }
                        } else {
                            echo 'Missing order id!';
                            exit;
                        }
                    } else {
                        header("HTTP/1.1 401 Unauthorized");
                        exit;
                    }
                } else {
                    header("HTTP/1.1 401 Unauthorized");
                    exit;
                }
            }
            exit;
        }


        public
        function createOrderNote($request)
        {

            header('Access-Control-Allow-Origin: *');
            header("Access-Control-Allow-Credentials: true");
            header('Access-Control-Allow-Methods: POST');
            header('Access-Control-Max-Age: 1000');
            header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token , Authorization');

            $token = $request->get_param('token');

            $body = $request->get_body();
            $data = json_decode($body, true);
            $orderid = $data['orderid'];
            $message = $data['message'];

            if (class_exists('WooCommerce')) {
                if (isset($token)) {
                    $orderpickingapp_apikey = get_option('orderpickingapp_apikey');
                    if (isset($orderpickingapp_apikey) && $token == $orderpickingapp_apikey) {

                        $Order = wc_get_order($orderid);
                        if (!empty($Order)) {
                            $Order->add_order_note($message, true, false);
                            $Order->save();


                            wp_send_json('Order ' . $orderid . ' note created!');
                        } else {
                            wp_send_json('Unknown order ID: ' . $orderid);
                        }
                    } else {
                        wp_send_json('Unauthorized');
                        header("HTTP/1.1 401 Unauthorized");
                        exit;
                    }
                } else {
                    wp_send_json('Unauthorized');
                    header("HTTP/1.1 401 Unauthorized");
                    exit;
                }
            }
            exit;
        }

        public
        function getOrderProducts($request)
        {

            header('Access-Control-Allow-Origin: *');
            header("Access-Control-Allow-Methods: GET");

            $Products = array();
            if (class_exists('WooCommerce')) {
                $picking_amount = $request->get_param('picking_amount');
                $appuser = $request->get_param('appuser');
                $token = $request->get_param('token');

                $picking_batch = get_option('picking_batch');
                if (!isset($picking_batch) || empty($picking_batch)) {
                    $picking_batch = 0;
                }

                if (isset($token)) {

                    $orderpickingapp_apikey = get_option('orderpickingapp_apikey');
                    if (isset($orderpickingapp_apikey) && $token == $orderpickingapp_apikey) {

                        $currency_symbol = get_woocommerce_currency_symbol();
                        $currency_symbol = html_entity_decode($currency_symbol);

                        $manual_order_assigning = get_option('manual_order_assigning');

                        $post_status = array('wc-processing');
                        $orderpickingapp_order_status = get_option('orderpickingapp_order_status');
                        if (isset($orderpickingapp_order_status) && !empty($orderpickingapp_order_status)) {
                            $post_status = (array)$orderpickingapp_order_status;
                        }

                        $order_statussen = array();
                        foreach ($post_status as $status) {
                            $order_statussen[] = str_replace('wc-', '', $status);
                        }

                        $args = array(
                            'status' => $order_statussen,
                            'limit' => -1,
                            'meta_query' => array(
                                'relation' => 'OR',
                                array(
                                    'relation' => 'AND',
                                    array(
                                        'key' => 'picking_status',
                                        'value' => 'packing',
                                        'compare' => '!=',
                                    ),
                                    array(
                                        'key' => 'picking_status',
                                        'value' => 'completed',
                                        'compare' => '!=',
                                    ),
                                ),
                                array(
                                    'key' => 'picking_status',
                                    'compare' => 'NOT EXISTS',
                                )
                            ),
                            'order' => 'ASC',
                        );

                        $pickingDate = get_option('pickingDate');
                        if (isset($pickingDate) && !empty($pickingDate)) {
                            $args['date_query'] = array(
                                array(
                                    'after' => $pickingDate,
                                ),
                            );
                        }

                        $query = new WC_Order_Query($args);
                        $shop_orders = $query->get_orders();
                        $total_picking_orders = count($shop_orders);
                        $ordersClaimed = false;

                        $disable_product_combining = get_option('disable_product_combining');

                        $open_picking_orders = array();
                        foreach ($shop_orders as $total_picking_order) {
                            $total_picking_order_id = $total_picking_order->get_id();

                            $batch_id = get_post_meta($total_picking_order_id, 'batch_id', true);
                            $user_claimed = get_post_meta($total_picking_order_id, 'user_claimed', true);
                            if ((isset($batch_id) && !empty($batch_id)) && (isset($user_claimed) && !empty($user_claimed) && $user_claimed == $appuser)) {
                                $ordersClaimed = true;
                            }

                            // Manual order assigning
                            if (isset($manual_order_assigning) && $manual_order_assigning == 'yes' && $user_claimed != $appuser) {
                                continue;
                            }

                            $total_items = 0;
                            foreach ($total_picking_order->get_items() as $item) {
                                $product_id = $item->get_product_id();
                                if (is_plugin_active('woocommerce-product-bundles/woocommerce-product-bundles.php') && !empty($product_id)) {
                                    $product_info = wc_get_product($product_id);
                                    $product_type = $product_info->get_type();
                                    if (!in_array($product_type, array('woosb', 'bundle', 'grouped'))) {
                                        $total_items = $total_items + $item->get_quantity();
                                    }
                                } else {
                                    $total_items = $total_items + $item->get_quantity();
                                }
                            }

                            $lastname = substr($total_picking_order->get_shipping_first_name(), 0, 1) . '. ' . $total_picking_order->get_shipping_last_name();
                            if (empty($lastname)) {
                                $lastname = substr($total_picking_order->get_billing_first_name(), 0, 1) . '. ' . $total_picking_order->get_billing_last_name();
                            }

                            $order_number = $total_picking_order_id;
                            $order_prefix = get_option('order_prefix');
                            if( isset($order_prefix) && !empty($order_prefix) ){
                                $order_number = $order_prefix . $total_picking_order_id;
                            }

                            $open_picking_orders[] = array(
                                'orderid' => $total_picking_order_id,
                                'order_number' => $order_number,
                                'date' => substr($total_picking_order->get_date_created(), 5, 5),
                                'items' => $total_items,
                                'total' => $currency_symbol . ' ' . $total_picking_order->get_total(),
                                'lastname' => $lastname,
                                'claimed_by' => $user_claimed,
                            );
                        }

                        $orderid = $request->get_param('orderid');

                        // GET SPECIFIC ORDER
                        if (isset($orderid) && strpos($orderid, 'find_') !== false) {
                            $args['post__in'] = (array)str_replace('find_', '', $orderid);
                            $query = new WC_Order_Query($args);
                            $shop_orders = $query->get_orders();
                        } // GET NEXT ORDER
                        elseif (isset($orderid) && strpos($orderid, 'takeover') === false) {
                            $args['post__not_in'] = range(($orderid - $total_picking_orders), $orderid);
                            $query = new WC_Order_Query($args);
                            $next_shop_orders = $query->get_orders();
                            $next_shop_order = $orderid;

                            if (count($next_shop_orders) > 0) {
                                $shop_orders = $next_shop_orders;
                            }
                        } elseif (isset($orderid) && strpos($orderid, 'takeover') !== false) {
                            $orderid = str_replace('takeover_', '', $orderid);
                            delete_post_meta($orderid, 'user_claimed');
                            $args['post__in'] = (array)$orderid;
                            $query = new WC_Order_Query($args);
                            $shop_orders = $query->get_orders();
                        }

                        $args = array(
                            'status' => $order_statussen,
                            'limit' => -1,
                            'meta_query' => array(
                                array(
                                    'key' => 'picking_status',
                                    'value' => 'packing',
                                    'compare' => '=',
                                )
                            )
                        );
                        $query = new WC_Order_Query($args);
                        $packing_orders = $query->get_orders();
                        $total_packing_orders = count($packing_orders);
                        $total_backorders = 0;

                        if (count($packing_orders) > 0) {
                            foreach ($packing_orders as $packing_order) {
                                foreach ($packing_order->get_items() as $item_id => $item) {
                                    $backorder = get_post_meta($item_id, 'backorder', true);
                                    if ($backorder !== '0' && !empty($backorder)) {
                                        $total_backorders++;
                                        break;
                                    }

                                }
                            }
                        }

                        $user_order_claims = array();
                        if (count($shop_orders) > 0) {

                            if (!$ordersClaimed) {
                                $picking_batch++;
                                update_option('picking_batch', $picking_batch);
                            }

                            $total_orders = 0;
                            $BatchBoxCharacter = 'A';
                            $BatchBoxCharacterSecond = 'A';
                            foreach ($shop_orders as $shop_order) {
                                $shop_order_id = $shop_order->get_id();

                                if (isset($next_shop_order) && $shop_order_id <= $next_shop_order) {
                                    continue;
                                }

                                // Custom function to return true/false
                                $skip_order = apply_filters('opa_skip_picking_order', false, $shop_order_id);
                                if ($skip_order) {
                                    continue;
                                }

                                if (!empty($appuser)) {
                                    $user_claimed = get_post_meta($shop_order_id, 'user_claimed', true);

                                    // Manual order assigning
                                    if (isset($manual_order_assigning) && $manual_order_assigning == 'yes' && $user_claimed != $appuser) {
                                        continue;
                                    }

                                    $user_order_claims[] = array(
                                        'orderid' => $shop_order_id,
                                        'appuser' => ucfirst($user_claimed),
                                    );
                                    if (isset($user_claimed) && !empty($user_claimed) && $user_claimed != $appuser) {
                                        continue;
                                    }
                                }

                                if ($total_orders >= $picking_amount) {
                                    break;
                                }

                                $order = $shop_order;
                                $currency_code = $order->get_currency();
                                $currency_symbol = get_woocommerce_currency_symbol($currency_code);
                                $currency_symbol = html_entity_decode($currency_symbol);
                                foreach ($order->get_items() as $item_id => $item) {

                                    $picking_path = '';
                                    $product_picking_locations = array();
                                    $picking_location = '';
                                    $description = '';

                                    $title = $item->get_name();
                                    $product_title = explode(" - ", $title, 2)[0];

                                    // Check if product no longer exist in Woocommerce
                                    $product_id = $item->get_product_id();
                                    if (!isset($product_id) || empty($product_id)) {
                                        $categories = array('Custom products');
                                        $order_cat_id = '';
                                        $thumbnail = '';

                                        $description = 'Product don\'t exist in Woocommerce!';
                                        $product_id = $item_id;
                                        $sku = $item_id;

                                        $price = $item->get_total() / $item->get_quantity();
                                        if (isset($price) && !empty($price)) {
                                            $price = $currency_symbol . ' ' . number_format($price, 2, ",", ".");
                                        } else {
                                            $price = '-';
                                        }

                                        $stock = '0';
                                        $quantity = $item->get_quantity();
                                    } else {

                                        $categories = array();

                                        // YOAST COMPATIBILITY
                                        $primary_product_cat = get_post_meta($product_id, '_yoast_wpseo_primary_product_cat', true);
                                        if (isset($primary_product_cat) && !empty($primary_product_cat)) {
                                            $primary_term = get_term($primary_product_cat);

                                            if (!empty($primary_term->parent)) {
                                                $categories[] = get_term($primary_term->parent)->name;
                                            }

                                            $categories[] = get_term($primary_product_cat)->name;
                                        } else {
                                            $categories = wp_get_post_terms($product_id, 'product_cat', array("orderby" => "parent"));

                                            if (isset($categories) && !empty($categories)) {
                                                $categories = wp_list_pluck($categories, 'name');
                                            }
                                        }

                                        $categoryHierarchy = array();
                                        $this->productCategories = array();
                                        $product_categories = get_the_terms($product_id, 'product_cat', array('hide_empty' => false));
                                        if (isset($product_categories) && is_array($product_categories)) {
                                            $this->sort_terms_hierarchically($product_categories, $categoryHierarchy);
                                        }
                                        $order_cat_id = end($this->productCategories);
                                        if (count($this->productCategories) > 2) {
                                            $order_cat_id = $this->productCategories[1];
                                        }

                                        $product_picking_locations = wp_get_post_terms($product_id, 'pickingroute', array("orderby" => "parent"));
                                        if (isset($product_picking_locations) && !empty($product_picking_locations)) {
                                            $order_cat_id = end($product_picking_locations)->term_id;
                                            $product_picking_locations = wp_list_pluck($product_picking_locations, 'name');

                                            $ordered_product_picking_locations = array_reverse($product_picking_locations);
                                            $picking_location = array_pop($ordered_product_picking_locations);

                                            $picking_path = implode(' / ', $product_picking_locations);
                                        }

                                        $thumbnail = get_the_post_thumbnail_url($product_id, 'medium');

                                        $variation_id = $item->get_variation_id();

                                        if (isset($variation_id) && !empty($variation_id)) {
                                            $product_info = wc_get_product($variation_id);
                                            $product_details = $product_info->get_data();
                                            $product_id = $variation_id;
                                            $product_parent_id = $product_details['parent_id'];

                                            $opa_picking_location = get_post_meta($product_id, 'opa_picking_location', true);
                                            if (!empty($opa_picking_location)) {
                                                $order_cat_id = (int)$opa_picking_location;
                                                $picking_path = get_term_parents_list($order_cat_id, 'pickingroute', array(
                                                    'separator' => '/',
                                                    'link' => false,
                                                    'format' => 'name',
                                                ));
                                                if (!is_wp_error($picking_path)) {
                                                    if (str_ends_with($picking_path, '/')) {
                                                        $picking_path = substr_replace($picking_path, '', -1);
                                                    }

                                                    $pos = strrpos($picking_path, '/');
                                                    $picking_location = $pos === false ? $picking_path : substr($picking_path, $pos + 1);
                                                }
                                            }

                                            $sku = $product_details['sku'];
                                            if (empty($sku)) {
                                                $sku = get_post_meta($product_parent_id, '_sku', true);
                                            }

                                            if (isset($product_details['image_id']) && !empty($product_details['image_id'])) {
                                                $thumbnail = wp_get_attachment_image_url($product_details['image_id']);
                                            }

                                            if (empty($sku)) {
                                                $sku = $variation_id;
                                                $barcode = 'no sku / barcode';
                                            } else {
                                                $barcode = $sku;
                                            }
                                        } else {
                                            $product_info = wc_get_product($product_id);
                                            $product_details = $product_info->get_data();
                                            $product_id = $item->get_product_id();
                                            $product_parent_id = $item->get_product_id();

                                            $sku = $product_details['sku'];

                                            if (empty($sku)) {
                                                $sku = $product_id;
                                                $barcode = 'no sku / barcode';
                                            } else {
                                                $barcode = $sku;
                                            }
                                        }

                                        $description = $product_details['short_description'];

                                        $price = $product_info->get_price();
                                        if (isset($price) && !empty($price)) {
                                            $price = $currency_symbol . ' ' . number_format($product_info->get_price(), 2, ",", ".");
                                        } else {
                                            $price = '-';
                                        }

                                        $stock = $product_info->get_stock_quantity();
                                        $quantity = $item->get_quantity();
                                    }

                                    $description = strip_tags($description, '<p><a><strong><i>');
                                    $description = str_replace(array("\r", "\n"), '', $description);
                                    if (strlen($description) > 200) {
                                        $description = substr($description, 0, 200) . '...';
                                    }

                                    if (isset($product_info)) {
                                        $product_type = $product_info->get_type();
                                        if (in_array($product_type, array('woosb', 'bundle', 'grouped'))) {
                                            $custom_product_field_label = ucfirst($product_type);
                                            $custom_product_field = $title;
                                            continue;
                                        }
                                    }

                                    $meta_description = '';
                                    $item_meta = $item->get_meta_data();
                                    if ($item_meta) {
                                        foreach ($item_meta as $meta) {
                                            if (in_array($meta->key, array('_reduced_stock')) || str_contains($meta->key, 'pa_') || is_array($meta->value) || str_contains($meta->key, '_')) {
                                                continue;
                                            }
                                            $meta_description .= '<strong>' . $meta->key . '</strong>: ' . $meta->value . '<br />';
                                        }
                                    }

                                    if (!empty($meta_description)) {
                                        $description = $meta_description;
                                    }

                                    $orderpickingapp_ean_field = get_option('orderpickingapp_ean_field');
                                    if (isset($orderpickingapp_ean_field) && !empty($orderpickingapp_ean_field)) {
                                        $barcode = get_post_meta($product_id, $orderpickingapp_ean_field, true);
                                    }

                                    if (strpos($thumbnail, "?")) {
                                        $thumbnail = substr($thumbnail, 0, strpos($thumbnail, "?"));
                                    }
                                    if (!isset($thumbnail) || empty($thumbnail)) {
                                        $thumbnail = 'https://orderpickingapp.com/missing_product.jpg';
                                    }

                                    // Fallback for product picking location
                                    if (!isset($picking_path) || empty($picking_path)) {
                                        $categories = implode(' / ', $categories);
                                    } else {
                                        $categories = $picking_path;
                                    }

                                    $orderpickingapp_location_field = get_option('orderpickingapp_location_field');
                                    if (isset($orderpickingapp_location_field) && !empty($orderpickingapp_location_field)) {
                                        $categories = get_post_meta($product_parent_id, $orderpickingapp_location_field, true);
                                    }

                                    $identifier = $product_id;
                                    if (isset($disable_product_combining) && $disable_product_combining == 'yes') {
                                        $identifier = $item_id;
                                    }

                                    $product_data = array(
                                        'product_id' => $identifier,
                                        'title' => $title,
                                        'product_title' => $product_title,
                                        'thumbnail' => $thumbnail,
                                        'description' => $description,
                                        'sku' => $sku,
                                        'barcode' => $barcode,
                                        'price' => $price,
                                        'stock' => $stock,
                                        'quantity' => $quantity,
                                        'backorder' => 0,
                                        'type' => $product_type,
                                        'order_cat' => $order_cat_id,
                                        'categories' => $categories,
                                        'picking_path' => $picking_path,
                                        'picking_location' => $picking_location,
                                        'total_picking_orders' => $total_picking_orders,
                                        'total_packing_orders' => $total_packing_orders,
                                        'total_backorders' => $total_backorders,
                                        'open_picking_orders' => $open_picking_orders,
                                        'custom_field_label' => '',
                                        'custom_field' => '',
                                    );

                                    if (isset($custom_product_field_label) && !empty($custom_product_field_label)) {
                                        $product_data['custom_field_label'] = $custom_product_field_label;
                                        $product_data['custom_field'] = $custom_product_field;
                                    } elseif (isset($orderpickingapp_location_field) && !empty($orderpickingapp_location_field)) {
                                        $product_data['custom_field'] = get_post_meta($product_parent_id, $orderpickingapp_location_field, true);
                                    }

                                    if (!isset($Products[$identifier]['ordered'])) {
                                        $product_data['ordered'] = $item->get_quantity();
                                    } else {
                                        $Products[$identifier]['ordered'] = $Products[$identifier]['ordered'] + $item->get_quantity();
                                    }

                                    if (!isset($Products[$identifier]['unpicked'])) {
                                        $product_data['unpicked'] = $item->get_quantity();
                                    } else {
                                        $Products[$identifier]['unpicked'] = $Products[$identifier]['unpicked'] + $item->get_quantity();
                                    }

                                    if (empty($product_data['stock'])) {

                                        $_backorders = get_post_meta($item_id, '_backorders', true);
                                        if ($_backorders == 'yes') {
                                            $product_data['stock'] = '0';
                                        } else {
                                            $product_data['stock'] = $item->get_quantity();
                                        }
                                    } else {
                                        $product_data['stock'] = $product_data['stock'] + $item->get_quantity();
                                    }

                                    if (isset($Products[$identifier])) {
                                        if (isset($Products[$identifier]['quantity'])) {
                                            $Products[$identifier]['quantity'] += $product_data['quantity'];
                                        } else {
                                            $Products[$identifier] = array(
                                                'quantity' => $product_data['quantity']
                                            );
                                        }
                                    } else {
                                        $Products[$identifier] = $product_data;
                                    }

                                    if (!isset($Products[$identifier]['orders_list'])) {
                                        $Products[$identifier]['orders_list'] = '';
                                    }

                                    $custom_field = apply_filters('opa_custom_order_field', '', $shop_order_id);

                                    $total_items = $order->get_item_count();
                                    if (is_plugin_active('woocommerce-product-bundles/woocommerce-product-bundles.php')) {
                                        $total_items = 0;
                                        foreach ($order->get_items() as $item) {
                                            $temp_product_id = $item->get_product_id();
                                            if (!empty($temp_product_id)) {
                                                $product_info = wc_get_product($temp_product_id);
                                                $product_type = $product_info->get_type();
                                                if (!in_array($product_type, array('woosb', 'bundle', 'grouped'))) {
                                                    $total_items++;
                                                }
                                            }
                                        }
                                    }

                                    $customer_note = $order->get_customer_note();
                                    $customer_note = apply_filters('orderpickingapp_order_note', $customer_note);

                                    if (!isset($Products[$identifier]['orders_list'])) {
                                        $Products[$identifier] = array(
                                            'orders_list' => '#' . $shop_order_id . ' '
                                        );
                                    } else {
                                        $Products[$identifier]['orders_list'] .= '#' . $shop_order_id . ' ';
                                    }

                                    $BatchID = get_post_meta($shop_order_id, 'batch_id', true);
                                    if (!isset($BatchID) || empty($BatchID)) {
                                        $BatchID = $BatchBoxCharacter . $BatchBoxCharacterSecond . '-' . $picking_batch;
                                    }

                                    $fullname = $order->get_shipping_first_name() . ' ' . $order->get_shipping_last_name();
                                    $lastname = substr($order->get_shipping_first_name(), 0, 1) . '. ' . $order->get_shipping_last_name();
                                    if (empty($lastname)) {
                                        $fullname = $order->get_billing_first_name() . ' ' . $order->get_billing_last_name();
                                        $lastname = substr($order->get_billing_first_name(), 0, 1) . '. ' . $order->get_billing_last_name();
                                    }

                                    $order_number = $shop_order_id;
                                    $order_prefix = get_option('order_prefix');
                                    if( isset($order_prefix) && !empty($order_prefix) ){
                                        $order_number = $order_prefix . $shop_order_id;
                                    }

                                    $Products[$identifier]['orders'][] = array(
                                        'orderid' => $shop_order_id,
                                        'order_number' => $order_number,
                                        'date' => substr($order->get_date_created(), 5, 5),
                                        'items' => $total_items,
                                        'product_items' => $item->get_quantity(),
                                        'total' => $currency_symbol . ' ' . $order->get_total(),
                                        'fullname' => $fullname,
                                        'lastname' => $lastname,
                                        'shipping' => $order->get_shipping_method(),
                                        'order_note' => $customer_note,
                                        'custom_field' => $custom_field,
                                        'batch_id' => $BatchID,
                                        'batch_number' => preg_replace("/[^0-9]/", '', $BatchID),
                                        'claimed_by' => get_post_meta($shop_order_id, 'user_claimed', $appuser),
                                    );
                                }

                                // Set user claim
                                if (!empty($appuser)) {
                                    $Order = new WC_Order($shop_order_id);
                                    $Order->update_meta_data('claimed', 'true');

                                    // Manual order assigning
                                    if (!isset($manual_order_assigning) || $manual_order_assigning != 'yes') {
                                        $Order->update_meta_data('user_claimed', $appuser);
                                    }
                                    $Order->update_meta_data('picking_status', 'picking');
                                    $Order->update_meta_data('batch_id', $BatchBoxCharacter . $BatchBoxCharacterSecond . '-' . $picking_batch);
                                    $Order->add_order_note('Order Picking App | Order picking started by user: ' . $appuser);
                                    $Order->save();
                                    $total_orders++;

                                    if ($BatchBoxCharacterSecond == 'Z') {
                                        $BatchBoxCharacter = chr(ord($BatchBoxCharacter) + 1);
                                        $BatchBoxCharacterSecond = 'A';
                                    } else {
                                        $BatchBoxCharacterSecond = chr(ord($BatchBoxCharacterSecond) + 1);
                                    }
                                }
                            }
                        }
                    } else {
                        header("HTTP/1.1 401 Unauthorized");
                        exit;
                    }
                } else {
                    header("HTTP/1.1 401 Unauthorized");
                    exit;
                }
            }

            $this->log('Retrieving open Woocommerce orders with total amount of ' . count($Products), 'Info');

            if (isset($Products) && !empty($Products)) {
                wp_send_json(array_values($Products));
            } elseif (isset($user_order_claims) && !empty($user_order_claims)) {
                wp_send_json($user_order_claims);
            } elseif (empty($appuser)) {
                wp_send_json(array(
                    'total_picking_orders' => $total_picking_orders,
                    'total_packing_orders' => $total_packing_orders,
                    'total_backorders' => $total_backorders,
                ));
            } else {
                wp_send_json(array());
            }
            header("HTTP/1.1 401 Unauthorized");
            exit;
        }

        public
        function getPickingList($request)
        {

            header('Access-Control-Allow-Origin: *');
            header("Access-Control-Allow-Methods: GET");

            $Products = array();
            if (class_exists('WooCommerce')) {
                $token = $request->get_param('token');
                $status = $request->get_param('status');

                if (isset($token)) {

                    $orderpickingapp_apikey = get_option('orderpickingapp_apikey');
                    if (isset($orderpickingapp_apikey) && $token == $orderpickingapp_apikey) {

                        $post_statuses = array('wc-processing');
                        $orderpickingapp_order_status = get_option('orderpickingapp_order_status');
                        if (isset($orderpickingapp_order_status) && !empty($orderpickingapp_order_status)) {
                            $post_statuses = (array)$orderpickingapp_order_status;
                        }

                        $order_statussen = array();
                        foreach ($post_statuses as $post_status) {
                            $order_statussen[] = str_replace('wc-', '', $post_status);
                        }

                        if (isset($status) && $status == 'backorders') {
                            $args = array(
                                'status' => $order_statussen,
                                'limit' => -1,
                                'meta_query' => array(
                                    'relation' => 'OR',
                                    array(
                                        'key' => 'picking_status',
                                        'value' => 'packing',
                                        'compare' => '==',
                                    ),
                                ),
                                'order' => 'ASC',
                            );
                        } else {
                            $args = array(
                                'status' => $order_statussen,
                                'limit' => -1,
                                'meta_query' => array(
                                    'relation' => 'OR',
                                    array(
                                        'relation' => 'AND',
                                        array(
                                            'key' => 'picking_status',
                                            'value' => 'packing',
                                            'compare' => '!=',
                                        ),
                                        array(
                                            'key' => 'picking_status',
                                            'value' => 'completed',
                                            'compare' => '!=',
                                        ),
                                    ),
                                    array(
                                        'key' => 'picking_status',
                                        'compare' => 'NOT EXISTS',
                                    )
                                ),
                                'order' => 'ASC',
                            );
                        }

                        $pickingDate = get_option('pickingDate');
                        if (isset($pickingDate) && !empty($pickingDate)) {
                            $args['date_query'] = array(
                                array(
                                    'after' => $pickingDate,
                                ),
                            );
                        }

                        $query = new WC_Order_Query($args);
                        $shop_orders = $query->get_orders();

                        if (count($shop_orders) > 0) {

                            foreach ($shop_orders as $order) {
                                $contains_backorder_products = false;

                                $currency_code = $order->get_currency();
                                $currency_symbol = get_woocommerce_currency_symbol($currency_code);
                                $currency_symbol = html_entity_decode($currency_symbol);

                                $order_id = $order->get_id();
                                $order_datetime = str_replace('T', ' ', substr($order->get_date_created(), 0, 16));

                                $total_items = $order->get_item_count();
                                if (is_plugin_active('woocommerce-product-bundles/woocommerce-product-bundles.php')) {
                                    $total_items = 0;
                                    foreach ($order->get_items() as $item_id => $item) {
                                        $product_id = $item->get_product_id();
                                        $product_info = wc_get_product($product_id);
                                        $product_type = $product_info->get_type();
                                        if (!in_array($product_type, array('woosb', 'bundle', 'grouped'))) {
                                            $total_items++;
                                        }
                                    }
                                }

                                $lastname = substr($order->get_shipping_first_name(), 0, 1) . '. ' . $order->get_shipping_last_name();
                                if (empty($lastname)) {
                                    $lastname = substr($order->get_billing_first_name(), 0, 1) . '. ' . $order->get_billing_last_name();
                                }

                                $customer_note = $order->get_customer_note();
                                $customer_note = apply_filters('orderpickingapp_order_note', $customer_note);

                                $custom_field = apply_filters('opa_custom_order_field', '', $order_id);

                                $order_number = $order_id;
                                $order_prefix = get_option('order_prefix');
                                if( isset($order_prefix) && !empty($order_prefix) ){
                                    $order_number = $order_prefix . $order_id;
                                }

                                $output[$order_id] = array(
                                    'orderid' => $order_id,
                                    'order_number' => $order_number,
                                    'date' => substr($order_datetime, 0, 10),
                                    'items' => $total_items,
                                    'total' => $currency_symbol . ' ' . $order->get_total(),
                                    'lastname' => $lastname,
                                    'notes' => $customer_note,
                                    'custom_field' => $custom_field,
                                );

                                foreach ($order->get_items() as $item_id => $item) {

                                    $product_id = $item->get_product_id();

                                    // Skip none backorder items
                                    if (isset($status) && $status == 'backorders') {
                                        $backorder = get_post_meta($item_id, 'backorder', true);
                                        if ($backorder !== '0' && !empty($backorder)) {
                                            $contains_backorder_products = true;
                                        } else {
                                            continue;
                                        }
                                    }

                                    if (is_plugin_active('woocommerce-product-bundles/woocommerce-product-bundles.php')) {
                                        $product_info = wc_get_product($product_id);
                                        $product_type = $product_info->get_type();
                                        if (in_array($product_type, array('woosb', 'bundle', 'grouped'))) {
                                            continue;
                                        }
                                    }

                                    $title = $item->get_name();
                                    $product_title = explode(" - ", $title, 2)[0];

                                    $thumbnail = get_the_post_thumbnail_url($product_id, 'medium');

                                    $variation_id = $item->get_variation_id();
                                    if (isset($variation_id) && !empty($variation_id)) {
                                        $product_info = wc_get_product($variation_id);
                                        $product_details = $product_info->get_data();

                                        if (isset($product_details['image_id']) && !empty($product_details['image_id'])) {
                                            $thumbnail = wp_get_attachment_image_url($product_details['image_id']);
                                        }

                                        if (strpos($thumbnail, "?")) {
                                            $thumbnail = substr($thumbnail, 0, strpos($thumbnail, "?"));
                                        }
                                        if (!isset($thumbnail) || empty($thumbnail)) {
                                            $thumbnail = 'https://orderpickingapp.com/missing_product.jpg';
                                        }

                                        $product_data = array(
                                            'title' => $title,
                                            'product_title' => $product_title,
                                            'thumbnail' => $thumbnail,
                                            'quantity' => $item->get_quantity(),
                                        );
                                    } else {
                                        $product_info = wc_get_product($product_id);
                                        if (strpos($thumbnail, "?")) {
                                            $thumbnail = substr($thumbnail, 0, strpos($thumbnail, "?"));
                                        }
                                        if (!isset($thumbnail) || empty($thumbnail)) {
                                            $thumbnail = 'https://orderpickingapp.com/missing_product.jpg';
                                        }

                                        $product_data = array(
                                            'title' => $title,
                                            'product_title' => $product_title,
                                            'thumbnail' => $thumbnail,
                                            'quantity' => $item->get_quantity(),
                                        );
                                    }

                                    $product_data['stock'] = $product_info->get_stock_quantity();

                                    $variation_id = $item->get_variation_id();
                                    if (isset($variation_id) && !empty($variation_id)) {
                                        $product_info = wc_get_product($variation_id);
                                        $product_details = $product_info->get_data();
                                        $product_data['product_id'] = $variation_id;
                                        $product_id = $variation_id;

                                        $sku = $product_details['sku'];
                                        if (empty($sku)) {
                                            $sku = get_post_meta($product_details['parent_id'], '_sku', true);
                                        }

                                        if (!empty($product_details['short_description'])) {
                                            $product_data['description'] = $product_details['short_description'];
                                        } else {
                                            $product_data['description'] = $product_details['description'];
                                        }


                                        $product_data['sku'] = $sku;
                                        $product_data['price'] = get_post_meta($product_id, '_price', true);

                                        if (empty($product_data['sku'])) {
                                            $product_data['sku'] = $variation_id;
                                        }
                                    } elseif (isset($product_id) && !empty($product_id)) {
                                        $product_info = wc_get_product($product_id);
                                        $product_details = $product_info->get_data();
                                        $product_id = $item->get_product_id();
                                        $product_data['product_id'] = $product_id;

                                        $sku = $product_details['sku'];
                                        if (empty($sku)) {
                                            $sku = get_post_meta($product_id, '_sku', true);
                                        }

                                        if (!empty($product_details['short_description'])) {
                                            $product_data['description'] = $product_details['short_description'];
                                        } else {
                                            $product_data['description'] = $product_details['description'];
                                        }

                                        $product_data['sku'] = $sku;
                                        $product_data['price'] = get_post_meta($product_id, '_price', true);

                                        if (empty($product_data['sku'])) {
                                            $product_data['sku'] = $product_id;
                                        }
                                    } else {
                                        $product_data['product_id'] = $item_id;
                                        $product_data['description'] = 'Product don\'t exist in Woocommerce!';
                                        $product_data['sku'] = $item_id;

                                        $price = $item->get_total() / $item->get_quantity();
                                        if (isset($price) && !empty($price)) {
                                            $price = $currency_symbol . ' ' . number_format($price, 2, ",", ".");
                                        } else {
                                            $price = '-';
                                        }
                                        $product_data['price'] = $price;

                                        if (empty($product_data['sku'])) {
                                            $product_data['sku'] = $product_data['product_id'];
                                        }
                                    }

                                    $orderpickingapp_ean_field = get_option('orderpickingapp_ean_field');
                                    $barcode = $sku;
                                    if (isset($orderpickingapp_ean_field) && !empty($orderpickingapp_ean_field)) {
                                        $barcode = get_post_meta($product_id, $orderpickingapp_ean_field, true);
                                    }
                                    $product_data['barcode'] = $barcode;

                                    $output[$order_id]['products'][] = $product_data;
                                }

                                // Remove orders without any backorder products
                                if (isset($status) && $status == 'backorders' && !$contains_backorder_products) {
                                    unset($output[$order_id]);
                                }
                            }
                        }
                    } else {
                        header("HTTP/1.1 401 Unauthorized");
                        exit;
                    }
                } else {
                    header("HTTP/1.1 401 Unauthorized");
                    exit;
                }
            }

            if (isset($output) && !empty($output)) {
                wp_send_json(array_values($output));
            } else {
                wp_send_json(array());
            }
            header("HTTP/1.1 401 Unauthorized");
            exit;
        }

        public
        function requestPackingOrders($request)
        {

            header('Access-Control-Allow-Origin: *');
            header("Access-Control-Allow-Methods: GET");

            $output = array();
            $Products = array();
            if (class_exists('WooCommerce')) {

                $token = $request->get_param('token');

                if (isset($token)) {

                    $orderpickingapp_apikey = get_option('orderpickingapp_apikey');
                    if (isset($orderpickingapp_apikey) && $token == $orderpickingapp_apikey) {
                        $output = $this->getPackingOrders();
                    } else {
                        header("HTTP/1.1 401 Unauthorized");
                        exit;
                    }
                } else {
                    header("HTTP/1.1 401 Unauthorized");
                    exit;
                }
            }

            $this->log('Retrieving open Woocommerce orders with total amount of ' . count($Products), 'Info');
            wp_send_json($output);
            http_response_code(200);
            exit;
        }

        public
        function getPackingOrders($return = 'json')
        {
            $post_status = array('wc-processing');
            $orderpickingapp_order_status = get_option('orderpickingapp_order_status');
            if (isset($orderpickingapp_order_status) && !empty($orderpickingapp_order_status)) {
                $post_status = (array)$orderpickingapp_order_status;
            }

            $currency_symbol = get_woocommerce_currency_symbol();
            $currency_symbol = html_entity_decode($currency_symbol);

            $order_statussen = array();
            foreach ($post_status as $status) {
                $order_statussen[] = str_replace('wc-', '', $status);
            }

            $args = array(
                'status' => $order_statussen,
                'limit' => -1,
                'meta_query' => array(
                    'relation' => 'OR',
                    array(
                        'relation' => 'AND',
                        array(
                            'key' => 'picking_status',
                            'value' => 'packing',
                            'compare' => '!=',
                        ),
                        array(
                            'key' => 'picking_status',
                            'value' => 'completed',
                            'compare' => '!=',
                        ),
                    ),
                    array(
                        'key' => 'picking_status',
                        'compare' => 'NOT EXISTS',
                    )
                ),
                'order' => 'ASC',
            );

            $pickingDate = get_option('pickingDate');
            if (isset($pickingDate) && !empty($pickingDate)) {
                $args['date_query'] = array(
                    array(
                        'after' => $pickingDate,
                    ),
                );
            }
            $query = new WC_Order_Query($args);
            $picking_orders = $query->get_orders();
            $total_picking_orders = count($picking_orders);

            $args = array(
                'status' => $order_statussen,
                'limit' => -1,
                'meta_query' => array(
                    array(
                        'key' => 'picking_status',
                        'value' => 'packing',
                        'compare' => '=',
                    )
                ),
                'orderby' => 'date',
                'order' => 'ASC'
            );
            $query = new WC_Order_Query($args);
            $packing_orders = $query->get_orders();
            $total_packing_orders = count($packing_orders);
            $total_backorders = 0;

            if (count($packing_orders) > 0) {
                foreach ($packing_orders as $packing_order) {
                    foreach ($packing_order->get_items() as $item_id => $item) {
                        $backorder = get_post_meta($item_id, 'backorder', true);

                        if ($backorder !== '0' && !empty($backorder)) {
                            $total_backorders++;
                            break;
                        }

                    }
                }
            }

            if (count($packing_orders) > 0) {
                foreach ($packing_orders as $order) {
                    $packing_order_id = $order->get_id();
                    $order_datetime = str_replace('T', ' ', substr($order->get_date_created(), 0, 16));

                    $customer_note = $order->get_customer_note();
                    $customer_note = apply_filters('orderpickingapp_order_note', $customer_note);

                    $total_items = $order->get_item_count();
                    if (is_plugin_active('woocommerce-product-bundles/woocommerce-product-bundles.php')) {
                        $total_items = 0;
                        foreach ($order->get_items() as $item_id => $item) {
                            $product_id = $item->get_product_id();
                            $product_info = wc_get_product($product_id);
                            $product_type = $product_info->get_type();
                            if (!in_array($product_type, array('woosb', 'bundle', 'grouped'))) {
                                $total_items++;
                            }
                        }
                    }

                    $fullname = $order->get_shipping_first_name() . ' ' . $order->get_shipping_last_name();
                    $lastname = substr($order->get_shipping_first_name(), 0, 1) . '. ' . $order->get_shipping_last_name();
                    if (empty($lastname)) {
                        $fullname = $order->get_billing_first_name() . ' ' . $order->get_billing_last_name();
                        $lastname = substr($order->get_billing_first_name(), 0, 1) . '. ' . $order->get_billing_last_name();
                    }

                    $order_number = $packing_order_id;
                    $order_prefix = get_option('order_prefix');
                    if( isset($order_prefix) && !empty($order_prefix) ){
                        $order_number = $order_prefix . $packing_order_id;
                    }

                    $output[$packing_order_id] = array(
                        'orderid' => $packing_order_id,
                        'order_number' => $order_number,
                        'datetime' => $order_datetime,
                        'date' => substr($order_datetime, 5, 5),
                        'items' => $total_items,
                        'shipping' => $order->get_shipping_method(),
                        'total' => $currency_symbol . ' ' . $order->get_total(),
                        'notes' => $customer_note,
                        'fullname' => $fullname,
                        'lastname' => $lastname,
                        'onbackorder' => 'false',
                        'batch_id' => get_post_meta($packing_order_id, 'batch_id', true),
                        'total_packing_orders' => $total_packing_orders,
                        'total_picking_orders' => $total_picking_orders,
                        'total_backorders' => $total_backorders,
                    );

                    foreach ($order->get_items() as $item_id => $item) {

                        $product_id = $item->get_product_id();

                        if (is_plugin_active('woocommerce-product-bundles/woocommerce-product-bundles.php')) {
                            $product_info = wc_get_product($product_id);
                            $product_type = $product_info->get_type();
                            if (in_array($product_type, array('woosb', 'bundle', 'grouped'))) {
                                continue;
                            }
                        }

                        $title = $item->get_name();
                        $product_title = explode(" - ", $title, 2)[0];

                        $thumbnail = get_the_post_thumbnail_url($product_id, 'medium');

                        $variation_id = $item->get_variation_id();
                        if (isset($variation_id) && !empty($variation_id)) {
                            $product_info = wc_get_product($variation_id);
                            $product_details = $product_info->get_data();

                            if (isset($product_details['image_id']) && !empty($product_details['image_id'])) {
                                $thumbnail = wp_get_attachment_image_url($product_details['image_id']);
                            }

                            if (strpos($thumbnail, "?")) {
                                $thumbnail = substr($thumbnail, 0, strpos($thumbnail, "?"));
                            }
                            if (!isset($thumbnail) || empty($thumbnail)) {
                                $thumbnail = 'https://orderpickingapp.com/missing_product.jpg';
                            }

                            $product_data = array(
                                'title' => $title,
                                'product_title' => $product_title,
                                'thumbnail' => $thumbnail,
                                'quantity' => $item->get_quantity(),
                                'status' => get_post_meta($item_id, 'picking_status', true),
                                'backorder' => get_post_meta($item_id, 'backorder', true),
                            );
                        } else {


                            if (strpos($thumbnail, "?")) {
                                $thumbnail = substr($thumbnail, 0, strpos($thumbnail, "?"));
                            }
                            if (!isset($thumbnail) || empty($thumbnail)) {
                                $thumbnail = 'https://orderpickingapp.com/missing_product.jpg';
                            }

                            $product_data = array(
                                'title' => $title,
                                'product_title' => $product_title,
                                'thumbnail' => $thumbnail,
                                'quantity' => $item->get_quantity(),
                                'status' => get_post_meta($item_id, 'picking_status', true),
                                'backorder' => get_post_meta($item_id, 'backorder', true),
                            );
                        }

                        if ($product_data['backorder'] !== '0' && !empty($product_data['backorder'])) {
                            $output[$packing_order_id]['onbackorder'] = 'true';
                        }

                        $variation_id = $item->get_variation_id();
                        if (isset($variation_id) && !empty($variation_id)) {
                            $product_info = wc_get_product($variation_id);
                            $product_details = $product_info->get_data();
                            $product_data['product_id'] = $variation_id;
                            $product_id = $variation_id;

                            $sku = $product_details['sku'];
                            if (empty($sku)) {
                                $sku = get_post_meta($product_details['parent_id'], '_sku', true);
                            }

                            if (!empty($product_details['short_description'])) {
                                $product_data['description'] = $product_details['short_description'];
                            } else {
                                $product_data['description'] = $product_details['description'];
                            }


                            $product_data['sku'] = $sku;
                            $product_data['price'] = get_post_meta($product_id, '_price', true);
                            $product_data['stock'] = $product_info->get_stock_quantity();

                            if (empty($product_data['sku'])) {
                                $product_data['sku'] = $variation_id;
                            }
                        } elseif (isset($product_id) && !empty($product_id)) {
                            $product_info = wc_get_product($product_id);
                            $product_details = $product_info->get_data();
                            $product_id = $item->get_product_id();
                            $product_data['product_id'] = $product_id;

                            $sku = $product_details['sku'];
                            if (empty($sku)) {
                                $sku = get_post_meta($product_details['$product_id'], '_sku', true);
                            }

                            if (!empty($product_details['short_description'])) {
                                $product_data['description'] = $product_details['short_description'];
                            } else {
                                $product_data['description'] = $product_details['description'];
                            }

                            $product_data['sku'] = $sku;
                            $product_data['price'] = get_post_meta($product_id, '_price', true);
                            $product_data['stock'] = $product_info->get_stock_quantity();

                            if (empty($product_data['sku'])) {
                                $product_data['sku'] = $product_id;
                            }
                        } else {
                            $product_data['product_id'] = $item_id;
                            $product_data['description'] = 'Product don\'t exist in Woocommerce!';
                            $product_data['sku'] = $item_id;

                            $price = $item->get_total() / $item->get_quantity();
                            if (isset($price) && !empty($price)) {
                                $price = $currency_symbol . ' ' . number_format($price, 2, ",", ".");
                            } else {
                                $price = '-';
                            }
                            $product_data['price'] = $price;
                            $product_data['stock'] = $item->get_quantity();

                            if (empty($product_data['sku'])) {
                                $product_data['sku'] = $product_data['product_id'];
                            }
                        }

                        $product_data['description'] = strip_tags($product_data['description'], '<p><a><strong><i>');
                        $product_data['description'] = str_replace(array("\r", "\n"), '', $product_data['description']);
                        if (strlen($product_data['description']) > 300) {
                            $product_data['description'] = substr($product_data['description'], 0, 300) . '...';
                        }

                        // Niet aanwezig in Woocommerce
                        $product_data['product_reference'] = '';
                        $product_data['reference'] = '';

                        $product_data['ordered'] = $product_data['quantity'];
                        $product_data['unpacked'] = $product_data['quantity'];

                        if (empty($product_data['stock'])) {
                            $_backorders = get_post_meta($item_id, '_backorders', true);
                            if ($_backorders == 'yes') {
                                $product_data['stock'] = '0';
                            } else {
                                $product_data['stock'] = $item->get_quantity();
                            }
                        }

                        $orderpickingapp_ean_field = get_option('orderpickingapp_ean_field');
                        $barcode = $sku;
                        if (isset($orderpickingapp_ean_field) && !empty($orderpickingapp_ean_field)) {
                            $barcode = get_post_meta($product_id, $orderpickingapp_ean_field, true);
                        }
                        $product_data['barcode'] = $barcode;

                        $output[$packing_order_id]['products'][] = $product_data;
                    }
                }
            } else {
                $output = array(
                    array(
                        'total_picking_orders' => $total_picking_orders,
                        'total_packing_orders' => 0,
                        'total_backorders' => 0,
                    ),
                );
            }

            return $output;
        }

        public
        function log($Message, $Type = 'Info')
        {
            fwrite(self::$logFileHandle, date('d-m-Y H:i:s') . " | " . str_pad($Type, 8, " ", STR_PAD_RIGHT) . " | " . $Message . "\n");
        }

        public
        function getCategories($output = 'json', $taxonomy = 'product_cat')
        {

            header('Access-Control-Allow-Origin: *');
            header("Access-Control-Allow-Methods: GET");

            $categories = get_terms($taxonomy, array('hide_empty' => false));
            $categoryHierarchy = array();

            if (isset($categories) && !empty($categories)) {
                $this->sort_terms_hierarchically($categories, $categoryHierarchy);
            }

            if ($output == 'json') {
                echo json_encode($this->orderedCategories);
                exit;
            }
            return $this->orderedCategories;
        }

        public
        function sort_terms_hierarchically(array &$cats, array &$into, $parentId = 0)
        {
            foreach ($cats as $i => $cat) {
                if ($cat->parent == $parentId) {
                    $into[$cat->term_id] = $cat;
                    if ($parentId == 0) {
                        $this->orderedCategories[$cat->term_id]['name'] = $cat->name;
                        $this->orderedCategories[$cat->term_id]['count'] = $cat->count;
                        $this->orderedCategories[$cat->term_id]['children'] = array();
                        $this->productCategories[] = $cat->term_id;
                    } else {
                        $this->orderedCategories[$parentId]['children'][$cat->term_id] = $cat->name;
                        $this->productCategories[] = $cat->term_id;
                    }
                    unset($cats[$i]);
                }
            }

            foreach ($into as $topCat) {
                $topCat->children = array();
                $this->sort_terms_hierarchically($cats, $topCat->children, $topCat->term_id);
            }
        }

        public
        function downloadLog($data)
        {
            if (isset($data['date'])) {
                $Day = $data['date'];
                if (preg_match("/\d{4}-\d{2}-\d{2}/", $Day)) {
                    if (is_dir(self::$logPath)) {
                        if (file_exists(self::$logPath . "/" . $Day . ".log")) {
                            header("Content-Description: File Transfer");
                            header("Content-Type: application/octet-stream");
                            header("Content-Disposition: attachment; filename=" . $Day . ".log");
                            readfile(self::$logPath . "/" . $Day . ".log");
                            exit;
                        } else {
                            $response['Status'] = 'Failed';
                            $response['Message'] = 'Log file for: ' . $Day . ' not found';
                            exit(json_encode($response));
                        }
                    } else {
                        $response['Status'] = 'Failed';
                        $response['Message'] = 'Couldn\'t read the log - directory';
                        exit(json_encode($response));
                    }
                } else {
                    $response['Status'] = 'Failed';
                    $response['Message'] = 'Expecting the date to be formatted as: YYYY-mm-dd';
                    exit(json_encode($response));
                }
            } else {
                $response['Status'] = 'Failed';
                $response['Message'] = 'No date specified';
                exit(json_encode($response));
            }
        }

        public
        function getAvailableLogs($Limit = 25)
        {
            if ($handle = opendir(self::$logPath)) {
                $AvailableLogs = array();
                while (false !== ($entry = readdir($handle))) {
                    if ($entry != "." && $entry != "..") {
                        if (preg_match("/\d{4}-\d{2}-\d{2}/", $entry)) {
                            if (filesize(self::$logPath . "/" . $entry) > 0) {
                                $AvailableLogs[] = preg_replace("/\.log$/", "", $entry);
                            }
                        }
                    }
                }
                $response['Status'] = 'Success';
                $response['Files'] = $AvailableLogs;
            } else {
                $response['Status'] = 'Failed';
                $response['Files'] = array();
            }
            return $response;
        }

        public
        function readLogs($data)
        {
            $response = array();
            if (isset($data['date'])) {
                $Day = $data['date'];
                if (!preg_match("/\d{4}-\d{2}-\d{2}/", $Day)) {
                    $response['Status'] = 'Failed';
                    $response['Message'] = 'Expecting the date to be formatted as: YYYY-mm-dd';
                    exit(json_encode($response));
                }
            } else {
                $Day = date('Y-m-d');
            }
            if (is_dir(self::$logPath)) {
                if (file_exists(self::$logPath . "/" . $Day . ".log")) {
                    $response['Status'] = 'Success';
                    $MemoryAvailable = self::returnBytes(ini_get('memory_limit'));
                    $fp = fopen(self::$logPath . "/" . $Day . ".log", 'r');
                    if ($fp !== false) {
                        $lines = array();
                        $currentLine = '';
                        $pos = -2; // Skip final new line character (Set to -1 if not present)
                        while (-1 !== fseek($fp, $pos, SEEK_END) && ($MemoryAvailable - memory_get_usage()) > self::returnBytes("10MB")) {
                            $char = fgetc($fp);
                            if (PHP_EOL == $char) {
                                $lines[] = $currentLine;
                                $currentLine = '';
                            } else {
                                $currentLine = $char . $currentLine;
                            }
                            $pos--;
                        }

                        if (($MemoryAvailable - memory_get_usage()) > self::returnBytes("10MB")) {
                            $lines[] = $currentLine; // Grab final line
                        }
                        fclose($fp);
                        $response['Logs'] = $lines;
                    } else {
                        $response['Status'] = 'Failed';
                        $response['Logs'] = array(
                            "Failed to open log-file for: " . $Day
                        );
                    }
                } else {
                    $response['Status'] = 'Failed';
                    $response['Logs'] = array(
                        "No logs found for: " . $Day
                    );
                }
            } else {
                $response['Status'] = 'Failed';
                $response['Logs'] = array(
                    "Failed to read logs for: " . $Day
                );
            }
            return $response;
        }

        public
        function getProduct($request)
        {

            global $woocommerce;

            header('Access-Control-Allow-Origin: *');
            header("Access-Control-Allow-Methods: GET");

            if (class_exists('WooCommerce')) {

                $token = $request->get_param('token');
                $searchterm = $request->get_param('searchterm');

                if (isset($token)) {

                    $currency_symbol = get_woocommerce_currency_symbol();
                    $currency_symbol = html_entity_decode($currency_symbol);

                    $orderpickingapp_apikey = get_option('orderpickingapp_apikey');
                    if (isset($orderpickingapp_apikey) && $token == $orderpickingapp_apikey) {

                        // Search by sku
                        $args = array(
                            'post_type' => array('product', 'product_variation'),
                            'post_status' => 'any',
                            'posts_per_page' => -1,
                            'meta_query' => array(
                                'relation' => 'OR',
                                array(
                                    'key' => '_sku',
                                    'value' => $searchterm
                                ),
                            ),
                            'fields' => 'ids'
                        );

                        // Search by custom EAN field
                        $orderpickingapp_ean_field = get_option('orderpickingapp_ean_field');
                        if (isset($orderpickingapp_ean_field) && !empty($orderpickingapp_ean_field)) {
                            $args['meta_query'][] = array(
                                'key' => $orderpickingapp_ean_field,
                                'value' => $searchterm
                            );
                        }
                        $product_query = new WP_Query($args);
                        $product_ids = $product_query->posts;

                        // Search by product/variation id
                        if (empty($product_ids)) {
                            $args = array(
                                'post_type' => array('product', 'product_variation'),
                                'post_status' => 'any',
                                'posts_per_page' => 1,
                                'post__in' => (array)$searchterm,
                                'fields' => 'ids'
                            );
                            $product_query = new WP_Query($args);
                            $product_ids = $product_query->posts;
                        }

                        // Search by title
                        if (empty($product_ids)) {
                            $args = array(
                                's' => $searchterm,
                                'post_type' => array('product'),
                                'post_status' => 'any',
                                'posts_per_page' => -1,
                                'fields' => 'ids'
                            );
                            $product_query = new WP_Query($args);
                            $product_ids = $product_query->posts;
                        }

                        $Products = array();
                        if (isset($product_ids[0])) {
                            foreach ($product_ids as $product_id) {

                                $product_info = wc_get_product($product_id);
                                $product_details = $product_info->get_data();

                                $title = $product_info->get_name();

                                $main_product_id = $product_id;
                                if ($product_info->get_parent_id() !== 0) {
                                    $main_product_id = $product_info->get_parent_id();
                                }

                                // YOAST COMPATIBILITY
                                $primary_product_cat = get_post_meta($main_product_id, '_yoast_wpseo_primary_product_cat', true);
                                if (isset($primary_product_cat) && !empty($primary_product_cat)) {
                                    $primary_term = get_term($primary_product_cat);

                                    if (!empty($primary_term->parent)) {
                                        $categories[] = get_term($primary_term->parent)->name;
                                    }

                                    $categories[] = get_term($primary_product_cat)->name;
                                } else {
                                    $categories = wp_get_post_terms($main_product_id, 'product_cat', array("orderby" => "parent"));

                                    if (isset($categories) && !empty($categories)) {
                                        $categories = wp_list_pluck($categories, 'name');
                                    } else {
                                        $categories = array();
                                    }
                                }

                                $categoryHierarchy = array();
                                $this->productCategories = array();
                                $product_categories = get_the_terms($main_product_id, 'product_cat', array('hide_empty' => false));

                                if (isset($product_categories) && is_array($product_categories)) {
                                    $this->sort_terms_hierarchically($product_categories, $categoryHierarchy);
                                }

                                $order_cat_id = end($this->productCategories);
                                if (count($this->productCategories) > 2) {
                                    $order_cat_id = $this->productCategories[1];
                                }

                                $thumbnail = get_the_post_thumbnail_url($main_product_id, 'medium');

                                $sku = $product_details['sku'];

                                if (empty($sku)) {
                                    $sku = $product_id;
                                    $barcode = 'no sku / barcode';
                                } else {
                                    $barcode = $sku;
                                }

                                if (!empty($product_details['short_description'])) {
                                    $description = $product_details['short_description'];
                                } else {
                                    $description = $product_details['description'];
                                }

                                $price = $product_info->get_price();
                                if (isset($price) && !empty($price)) {
                                    $price = $currency_symbol . ' ' . number_format($product_info->get_price(), 2, ",", ".");
                                } else {
                                    $price = '-';
                                }

                                $stock = $product_info->get_stock_quantity();
                                if ($stock == NULL && !$product_info->managing_stock()) {
                                    $stock_status = $product_info->get_stock_status();

                                    if ($stock_status == 'outofstock') {
                                        $stock = 'OUT_OF_STOCK';
                                    } elseif ($stock_status == 'onbackorder') {
                                        $stock = 'ON_BACKORDER';
                                    } else {
                                        $stock = 'IN_STOCK';
                                    }
                                }

                                $description = strip_tags($description, '<p><a><strong><i>');
                                $description = str_replace(array("\r", "\n"), '', $description);
                                if (strlen($description) > 200) {
                                    $description = substr($description, 0, 200) . '...';
                                }

                                if (!empty($meta_description)) {
                                    $description = $meta_description;
                                }

                                if (isset($orderpickingapp_ean_field) && !empty($orderpickingapp_ean_field)) {
                                    $barcode = get_post_meta($product_id, $orderpickingapp_ean_field, true);
                                }

                                if (strpos($thumbnail, "?")) {
                                    $thumbnail = substr($thumbnail, 0, strpos($thumbnail, "?"));
                                }
                                if (!isset($thumbnail) || empty($thumbnail)) {
                                    $thumbnail = 'https://orderpickingapp.com/missing_product.jpg';
                                }

                                $product_data = array(
                                    'product_id' => $product_id,
                                    'title' => $title,
                                    'thumbnail' => $thumbnail,
                                    'description' => $description,
                                    'sku' => $sku,
                                    'barcode' => $barcode,
                                    'price' => $price,
                                    'stock' => $stock,
                                    'order_cat' => $order_cat_id,
                                    'categories' => implode(' / ', $categories),
                                );

                                $Products[] = $product_data;
                            }

                            wp_send_json($Products);
                        } else {
                            $args = array(
                                'status' => 400,
                                'message' => 'Product with his searchterm nog found!',
                            );
                            wp_send_json($args);
                        }
                    } else {
                        header("HTTP/1.1 401 Unauthorized");
                        exit;
                    }
                } else {
                    header("HTTP/1.1 401 Unauthorized");
                    exit;
                }
            }
        }

        public
        function updateProduct($request)
        {

            header('Access-Control-Allow-Origin: *');
            header("Access-Control-Allow-Credentials: true");
            header('Access-Control-Allow-Methods: POST');
            header('Access-Control-Max-Age: 1000');
            header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token , Authorization');

            if (class_exists('WooCommerce')) {

                $token = $request->get_param('token');
                $product_id = $request->get_param('productid');
                $stock = $request->get_param('stock');
                if (isset($token)) {

                    $orderpickingapp_apikey = get_option('orderpickingapp_apikey');
                    if (isset($orderpickingapp_apikey) && $token == $orderpickingapp_apikey) {
                        $product = wc_get_product($product_id);
                        $product->set_stock_quantity($stock);
                        $product->save();
                    }
                } else {
                    header("HTTP/1.1 401 Unauthorized");
                    exit;
                }
            }
            exit;
        }

        function returnBytes($val)
        {
            $val = trim($val);
            $last = strtolower($val[strlen($val) - 1]);
            $val = substr($val, 0, strlen($val) - 1);
            switch ($last) {
                case 'g':
                    $val *= 1024;
                case 'm':
                    $val *= 1024;
                case 'k':
                    $val *= 1024;
            }
            return $val;
        }

    }