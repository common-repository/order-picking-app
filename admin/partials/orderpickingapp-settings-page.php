<link rel="stylesheet" href="<?php echo OPA_PLUGIN_URL; ?>admin/css/orderpickingapp.css" type="text/css" media="all">
<script type="text/javascript" src="<?php echo OPA_PLUGIN_URL; ?>admin/js/bootstrap.min.js"></script>
<script type="text/javascript" src="<?php echo OPA_PLUGIN_URL; ?>admin/js/bootstrap-toggle.min.js"></script>
<script type="text/javascript" src="https://code.jquery.com/ui/1.13.2/jquery-ui.min.js"></script>
<script type="text/javascript" src="<?php echo OPA_PLUGIN_URL; ?>admin/js/admin.js"></script>


<div id='orderpickingapp-settings' class='wrap'>
    <?php
    $orderpickingapp_apikey = get_option('orderpickingapp_apikey');
    if( !isset($orderpickingapp_apikey) || empty($orderpickingapp_apikey) ):
        ?>
        <section class="cta-with-feature ptb-120">
            <div class="bg-dark text-white rounded-custom position-relative" style="background-size: cover; background-image: url('<?php echo OPA_PLUGIN_URL; ?>admin/images/bg_opa.jpg')">
                <div class="row">
                    <p style="text-align: center; padding: 20px; font-weight: 700; font-size: 2.5rem; line-height: 1.2; font-family: Montserrat, sans-serif;">Order Picking App, the smart way of picking</p>
                    <div class="col-lg-5 col-md-6">
                        <div class="cta-with-feature-wrap p-5" style="padding-top: 0 !important;">
                            <ul class="nav justify-content-center feature-tab-list" id="nav-tab" role="tablist">
                                <li class="nav-item"><a class="nav-link active" href="#new_user"  data-bs-toggle="tab" data-bs-target="#new_user" role="tab" aria-selected="true">Start free trial</a></li>
                                <li class="nav-item"><a class="nav-link" href="#existing_user" data-bs-toggle="tab" data-bs-target="#existing_user" role="tab" aria-selected="false">I have a key</a></li>
                            </ul>

                            <div class="tab-content rounded-custom p-50" style="background: #fff;">
                                <div class="tab-pane fade show active" id="new_user" role="tabpanel">
                                    <form method='post'>
                                        <h5 style="color: #000;">Connect your webshop</h5>
                                        <div class="form-group">
                                            <label>Company</label>
                                            <input type="text" class="form-control" name="billing_company" value="<?php echo get_bloginfo( 'name' ); ?>">
                                        </div>
                                        <div class="form-group">
                                            <label>Country</label>
                                            <input type="text" class="form-control" name="billing_country" value="<?php echo ( get_option( 'woocommerce_default_country' ) )? get_option( 'woocommerce_default_country' ) : ''; ?>">
                                        </div>
                                        <div class="form-group">
                                            <label>City</label>
                                            <input type="text" class="form-control" name="billing_city" value="<?php echo ( get_option( 'woocommerce_store_city' ) )? get_option( 'woocommerce_store_city' ) : ''; ?>">
                                        </div>
                                        <div class="form-group">
                                            <label>Address 1</label>
                                            <input type="text" class="form-control" name="billing_address_1" value="<?php echo ( get_option( 'woocommerce_store_address' ) )? get_option( 'woocommerce_store_address' ) : ''; ?>">
                                        </div>
                                        <div class="form-group">
                                            <label>Address 2</label>
                                            <input type="text" class="form-control" name="billing_address_2" value="<?php echo ( get_option( 'woocommerce_store_address_2' ) )? get_option( 'woocommerce_store_address_2' ) : ''; ?>">
                                        </div>
                                        <div class="form-group">
                                            <label>Postal</label>
                                            <input type="text" class="form-control" name="billing_postcode" value="<?php echo ( get_option( 'woocommerce_store_postcode' ) )? get_option( 'woocommerce_store_postcode' ) : ''; ?>">
                                        </div>
                                        <div class="form-group">
                                            <label>Email</label>
                                            <input type="email" class="form-control" name="email" value="<?php echo get_bloginfo('admin_email'); ?>">
                                        </div>
                                        <button id="createAccount" class="btn btn-primary" style="margin-top: 15px;">Start 30-day free trial</button>
                                        <p style="color: #000;">or check out our free demo on Apple IOS or Android. See downloads below.</p>
                                    </form>
                                </div>

                                <div class="tab-pane fade" id="existing_user" role="tabpanel">
                                    <form method='post' action='options.php'>
                                        <?php
                                        settings_fields( 'orderpickingapp_options' );
                                        do_settings_sections( 'orderpickingapp_options' );
                                        submit_button();
                                        ?>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-lg-7 col-md-6">
                        <div class="container">
                            <div class="row" style="margin-top: 40px;">
                                <video controls="" poster="https://orderpickingapp.com/wp-content/uploads/2023/12/Scherm%C2%ADafbeelding-2023-12-23-om-20.30.55.png" preload="none" src="https://orderpickingapp.com/wp-content/uploads/2023/12/explainer%20orderpickingapp_v2.mp4"></video>
                            </div>
                            <ul class="cta-feature-list list-unstyled mb-0 mt-3">
                                <li class="d-flex align-items-center py-1"><span class="dashicons dashicons-yes"></span> Orderpick with your mobile of scanner</li>
                                <li class="d-flex align-items-center py-1"><span class="dashicons dashicons-yes"></span> Single or batch picking</li>
                                <li class="d-flex align-items-center py-1"><span class="dashicons dashicons-yes"></span> Multi picker</li>
                            </ul>
                            <div class="action-btns mt-5">
                                <a href="https://orderpickingapp.com/plans-and-pricing/" target="_blank" class="btn btn-primary me-3">More information</a>
                            </div>
                            <br/>
                        </div>
                    </div>

                </div>
            </div>
        </section>
    <?php
    else:
        $url = 'https://orderpickingapp.com/wp-json/account/v1/settings?token='.$orderpickingapp_apikey;
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_REFERER, site_url());
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        $response = curl_exec($ch);
        curl_close($ch);

        $response = json_decode($response, true);

        $app_settings = array();
        if( isset($response['settings']) ){
            $app_settings = $response['settings'];
        }

        $categoryHierarchy = array();
        $OrderPickingApp = new OrderPickingApp();

        $all_categories = $OrderPickingApp->getCategories('return', 'pickingroute');
        $categories = get_terms('pickingroute', array('hide_empty' => false));

        if( empty($all_categories) ) {
            $categories = get_terms('product_cat', array('hide_empty' => false));
            $all_categories = $OrderPickingApp->getCategories('return', 'product_cat');
        }
        wp_enqueue_script( 'media' );

        $Admin = new OrderPickingApp_Admin();
        $all_meta_keys = $Admin->get_all_meta_keys('product');

        // Needed for uploads
        if(function_exists( 'wp_enqueue_media' )){
            wp_enqueue_media();
        }
        ?>

        <?php if( isset($app_settings['subscription']['trial']) && $app_settings['subscription']['trial'] ): ?>
        <section class="our-integration">
            <div class="container">
                <div class="position-relative w-100">
                    <div class="row position-relative connected-app-content bg-white border border-2 transition-base rounded-custom">
                        <div class="col-12" style="margin-bottom: 10px;">
                            <h5 style="margin: 10px 0;">TRIAL PERIOD</h5>
                            <p class=text-muted">Your trial will end in <?php echo $app_settings['subscription']['trial_days']; ?> days!</p>
                            <?php if( $app_settings['subscription']['trial_days'] > 0 ): ?>
                                <a href="https://orderpickingapp.com/mijn-account" title="Check license" target="_blank" class="btn btn-primary">Check license</a>
                            <?php else: ?>
                                <a href="https://orderpickingapp.com/mijn-account" title="Extend license" target="_blank" class="btn btn-primary">Extend license</a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    <?php endif; ?>

        <!-- Nav tabs -->
        <ul class="nav justify-content-center feature-tab-list mt-4" id="nav-tab" role="tablist">
            <li class="nav-item"><a class="nav-link active" href="#api_credentials"  data-bs-toggle="tab" data-bs-target="#api_credentials" role="tab" aria-selected="true">Settings</a></li>
            <li class="nav-item"><a class="nav-link" href="#api_users" data-bs-toggle="tab" data-bs-target="#api_users" role="tab" aria-selected="false">Pickers</a></li>
            <li class="nav-item"><a class="nav-link" href="#pickingroute" data-bs-toggle="tab" data-bs-target="#pickingroute" role="tab" aria-selected="false">Pickingroute</a></li>
            <li class="nav-item"><a class="nav-link" href="#shops" data-bs-toggle="tab" data-bs-target="#shops" role="tab" aria-selected="false">Shops</a></li>
            <li class="nav-item"><a class="nav-link" href="#api_account" data-bs-toggle="tab" data-bs-target="#api_account" role="tab" aria-selected="false">Account</a></li>
            <li class="nav-item"><a class="nav-link" href="#logs" data-bs-toggle="tab" data-bs-target="#logs" role="tab" aria-selected="false">Logs</a></li>
        </ul>

        <!-- Tab panes -->
        <div class="tab-content rounded-custom p-50" id="nav-tabContent" style="border: 1px solid #0b163f;">

            <div class="tab-pane fade show active" id="api_credentials" role="tabpanel">
                <div class="row justify-content-center">
                    <img style="width: 224px;left: 40px;position: absolute;" src="<?php echo OPA_PLUGIN_URL; ?>admin/images/orderpickingapp.png"/>
                    <h5 style="text-align: center;">API key</h5>
                    <strong style="text-align: center;"><?php echo $orderpickingapp_apikey; ?></strong>
                    <?php
                    $generator = new Picqer\Barcode\BarcodeGeneratorPNG();
                    echo '<img style="text-align: center;display: block;margin: 0 auto; max-width: 500px;" src="data:image/png;base64,' . base64_encode($generator->getBarcode($orderpickingapp_apikey, $generator::TYPE_CODE_128)) . '">';
                    ?>
                    <input id="token" type="hidden" name="token" value="<?php echo $orderpickingapp_apikey; ?>">
                    <form class="client_options" method="post" style="width: 100%;margin-top:25px;">
                        <p style="font-size: 14px; text-align: center; margin: 25px 15%;">Set up the Order Picking App software here so that it suits your way of working. To add phones with the Order Picking App (iOS or Android), download the app from the app store and scan the barcode to connect. This way, all employees can have their own Order Picking app on their phone.</p>
                        <div class="form-group">
                            <?php if( isset($app_settings['logoUrl']) && !empty($app_settings['logoUrl']) ): ?>
                                <img class="app_logo" style="max-width: 100px; margin: 10px;" src="<?php echo $app_settings['logoUrl']; ?>"/>
                            <?php else: ?>
                                <img class="app_logo" src=""  style="max-width: 100px; margin: 10px; display: none;"/>
                            <?php endif; ?>
                            <input type="text" style="max-width: 500px;" class="logoUrl form-control" placeholder="Url to your logo" name="logoUrl" value="<?php echo ( isset($app_settings['logoUrl']) && !empty($app_settings['logoUrl']) )? $app_settings['logoUrl'] : ''; ?>">
                            <a href="#" style="margin-top: 5px;clear: both;display: block;width: 64px;" class="button app_logo_upload">Upload</a>
                        </div>
                        <div class="form-group">
                            <input type="checkbox" name="allow_logo_use" value="yes" <?php echo (isset($app_settings['allow_logo_use']) && $app_settings['allow_logo_use'] == 'yes')? 'checked' : ''; ?>>
                            <label  class="form-check-label" for="allow_logo_use"><?php echo __( 'Yes, I want you to show my logo on Orderpickingapp.com. We will also paste the shop URL behind the logo so that we can help each other with link building.', 'orderpickingapp' ); ?></label>
                        </div>

                        <h5>Select order amount to pick at once</h5>
                        <div class="form-group">
                            <select name="picking_amount">
                                <option value="1" <?php echo (isset($app_settings['picking_amount']) && $app_settings['picking_amount'] == '1')? 'selected' : ''; ?>>Single order picking</option>
                                <?php for ($x = 2; $x <= 20; $x++): ?>
                                    <option value="<?php echo $x; ?>" <?php echo (isset($app_settings['picking_amount']) && $app_settings['picking_amount'] == $x)? 'selected' : ''; ?>><?php echo $x; ?> orders</option>
                                <?php endfor; ?>
                            </select>
                        </div>

                        <h5>Scan settings</h5>
                        <div class="form-check">
                            <input style="margin-top: 23px;" type="radio" class="form-check-input" name="use_barcodescanner" value="no" <?php echo ( (isset($app_settings['use_barcodescanner']) && $app_settings['use_barcodescanner'] == 'no') || empty($app_settings['use_barcodescanner']) )? 'checked' : ''; ?>>
                            <label class="form-check-label" for="no">Use phone camera
                                <img style="max-width: 80px;" src="https://orderpickingapp.com/wp-content/themes/orderpickingapp/assets/img/mobile.png"/>
                            </label>
                        </div>
                        <div class="form-check">
                            <input style="margin-top: 33px;" type="radio" class="form-check-input" name="use_barcodescanner" value="android" <?php echo ( (isset($app_settings['use_barcodescanner']) && $app_settings['use_barcodescanner'] == 'android') )? 'checked' : ''; ?>>
                            <label class="form-check-label" for="yes">Use Barcodescanner for Android and Bluetooth
                                <img style="max-width: 80px;" src="https://orderpickingapp.com/wp-content/themes/orderpickingapp/assets/img/barcodescanner.jpeg"/>
                            </label>
                        </div>

                        <h5>Woocommerce custom settings</h5>
                        <strong  style="font-size: 12px; margin-top:10px;">Custom picking status(en)</strong><br/>
                        <?php
                        $app_settings['orderpickingapp_order_status'] =  (array)$app_settings['orderpickingapp_order_status'];
                        $wc_order_statussen = wc_get_order_statuses();
                        foreach( $wc_order_statussen as $key => $name ): ?>
                            <div class="form-check">
                                <input type="checkbox" class="form-check-input" name="orderpickingapp_order_status" value="<?php echo $key;?>" <?php echo ( isset($app_settings['orderpickingapp_order_status']) && in_array($key, $app_settings['orderpickingapp_order_status']) )? 'checked' : ''; ?>>
                                <label class="form-check-label" for="<?php echo $key;?>"><?php echo $name;?></label>
                            </div>
                        <?php endforeach; ?>
                        <br/>
                        <div class="form-group">
                            <strong  style="font-size: 12px; margin-top:10px;"><?php echo __( 'Order assigning', 'orderpickingapp' ); ?></strong>
                            <br/>
                            <input type="checkbox" name="manual_order_assigning" value="yes" <?php echo (isset($app_settings['manual_order_assigning']) && $app_settings['manual_order_assigning'] == 'yes')? 'checked' : ''; ?>>
                            <label  class="form-check-label" for="manual_order_assigning"><?php echo __( 'Enable manual order assigning to pickers', 'orderpickingapp' ); ?></label>
                        </div>
                        <div class="form-group">
                            <strong  style="font-size: 12px; margin-top:10px;"><?php echo __( 'Order handling', 'orderpickingapp' ); ?></strong>
                            <br/>
                            <input type="checkbox" name="auto_completed_order" value="yes" <?php echo (isset($app_settings['auto_completed_order']) && $app_settings['auto_completed_order'] == 'yes')? 'checked' : ''; ?>>
                            <label  class="form-check-label" for="auto_completed_order"><?php echo __( 'Enable auto completed order after picking and packing', 'orderpickingapp' ); ?></label>
                        </div>
                        <div class="form-group">
                            <strong  style="font-size: 12px; margin-top:10px;"><?php echo __( 'Product combining', 'orderpickingapp' ); ?></strong>
                            <br/>
                            <input type="checkbox" name="disable_product_combining" value="yes" <?php echo (isset($app_settings['disable_product_combining']) && $app_settings['disable_product_combining'] == 'yes')? 'checked' : ''; ?>>
                            <label  class="form-check-label" for="disable_product_combining"><?php echo __( 'Disable product combining by SKU/ID and process each order product as individual', 'orderpickingapp' ); ?></label>
                        </div>
                        <div class="form-group">
                            <strong  style="font-size: 12px; margin-top:10px;"><?php echo __( 'Product summary', 'orderpickingapp' ); ?></strong>
                            <br/>
                            <input type="checkbox" name="pre_picking_summary" value="yes" <?php echo (isset($app_settings['pre_picking_summary']) && $app_settings['pre_picking_summary'] == 'yes')? 'checked' : ''; ?>>
                            <label  class="form-check-label" for="pre_picking_summary"><?php echo __( 'Enable this pre picking step with a list of all your open picking products. When enabled this will add a additional tab inside you app.', 'orderpickingapp' ); ?></label>
                        </div>
                        <div class="form-group">
                            <strong  style="font-size: 12px; margin-top:10px;">Order prefix</strong>
                            <input type="text" style="max-width: 500px;" class="form-control" name="order_prefix" value="<?php echo ( isset($app_settings['order_prefix']) && !empty($app_settings['order_prefix']) )? $app_settings['order_prefix'] : ''; ?>">
                        </div>
                        <div style="font-size: 11px; margin-top: 5px;">* Add a custom prefix to orders</div>
                        <div class="form-group">
                            <strong  style="font-size: 12px; margin-top:10px;">Picking date</strong>
                            <input type="text" style="max-width: 500px;" placeholder="YYYY-mm-dd" pattern="\d{4}-\d{2}-\d{2}" class="pickingDate form-control" name="pickingDate" value="<?php echo ( isset($app_settings['pickingDate']) && !empty($app_settings['pickingDate']) )? $app_settings['pickingDate'] : ''; ?>">
                        </div>
                        <div style="font-size: 11px; margin-top: 5px;">* Start picking orders from this date. Format: Year-month-day</div>

                        <?php
                        $orderpickingapp_location_field = get_option('orderpickingapp_location_field');
                        ?>
                        <strong style="font-size: 12px; margin-top:5px;">Custom location field</strong><br/>
                        <select name="orderpickingapp_location_field">
                            <?php
                            echo '<option value="">Category (Default)</option>';
                            foreach( $all_meta_keys as $meta_key ){
                                echo '<option value="' .$meta_key. '" ' . selected($meta_key, $orderpickingapp_location_field ) . '>' .$meta_key. '</option>';
                            }
                            ?>
                        </select>
                        <br/>
                        <div style="font-size: 11px; margin-top: 5px;">* Default we use the SKU as barcode. You can select a custom field if you have a custom EAN field.</div>

                        <?php
                        $orderpickingapp_ean_field = get_option('orderpickingapp_ean_field');
                        ?>
                        <strong style="font-size: 12px; margin-top:5px;">Custom EAN field</strong><br/>
                        <select name="orderpickingapp_ean_field">
                            <?php
                            echo '<option value="_sku">SKU</option>';
                            echo '<option value="_global_unique_id">Product | GTIN, UPC, EAN of ISBN</option>';
                            echo '<option value="variable_global_unique_id">Variation | GTIN, UPC, EAN of ISBN</option>';
                            foreach( $all_meta_keys as $meta_key ){
                                echo '<option value="' .$meta_key. '" ' . selected($meta_key, $orderpickingapp_ean_field ) . '>' .$meta_key. '</option>';
                            }
                            ?>
                        </select>
                        <br/>
                        <div style="font-size: 11px; margin-top: 5px;">* Default we use the SKU as barcode. You can select a custom field if you have a custom EAN field.</div>

                        <h5>Display settings</h5>
                        <div class="form-check">
                            <input type="checkbox" class="form-check-input" name="hide_order_total" value="yes" <?php echo ( (isset($app_settings['hide_order_total']) && $app_settings['hide_order_total'] == 'yes') )? 'checked' : ''; ?>>
                            <label class="form-check-label" for="yes">Hide order total</label>
                        </div>
                        <div class="form-check">
                            <input type="checkbox" class="form-check-input" name="hide_product_description" value="yes" <?php echo ( (isset($app_settings['hide_product_description']) && $app_settings['hide_product_description'] == 'yes') )? 'checked' : ''; ?>>
                            <label class="form-check-label" for="yes">Hide product description</label>
                        </div>
                        <div class="form-check">
                            <input type="checkbox" class="form-check-input" name="hide_product_location" value="yes" <?php echo ( (isset($app_settings['hide_product_location']) && $app_settings['hide_product_location'] == 'yes') )? 'checked' : ''; ?>>
                            <label class="form-check-label" for="yes">Hide product category/location</label>
                        </div>

                        <br/>

                        <button id="reset_api_key" class="btn btn-secondary" style="font-size: 14px; float: left;">Reset API key</button>
                        <button class="btn btn-primary submit_options" style="font-size: 14px; float: right;">Save</button>
                    </form>
                </div>
            </div>

            <div class="tab-pane fade" id="api_users" role="tabpanel">
                <div class="row justify-content-center">
                    <form method="post" style="width: 100%;">
                        <h5>Pickers</h5>
                        <p style="font-size: 14px; text-align: center; margin: 0 15%;">Add all employees who will use the Order Picking App here. In the app you start by choosing a picker. For picked orders/products, the relevant picker is noted in the order notes.</p>
                        <?php
                        if( isset($app_settings['app_users']) && !empty($app_settings['app_users']) && is_array($app_settings['app_users']) ):
                            $i = count($app_settings['app_users']);
                            foreach( $app_settings['app_users'] as $app_user ):

                                if( !isset($app_user['app_users_name']) ){
                                    $app_user = array(
                                        'app_users_name' => $app_user,
                                        'app_users_picking_amount' => ''
                                    );
                                }

                                $i--; ?>
                                <div class="form-group <?php echo ($i == 0)? 'duplicate_row' : ''; ?>" style="margin: 10px 0; min-height: 30px;">
                                    <input style="float: left; width: 75%;" type="text" class="form-control app_users_name" name="app_users_name" value="<?php echo $app_user['app_users_name']; ?>" placeholder="<?php echo $app_user['app_users_name']; ?>"/>
                                    <span class="dashicons dashicons-trash remove_picker" style="float: right;margin: 4px 10px;"></span>
                                    <select style="float: right; width: 20%;" name="app_users_picking_amount">
                                        <option value="1" <?php echo (isset($app_user['app_users_picking_amount']) && $app_user['app_users_picking_amount'] == '1')? 'selected' : ''; ?>>Single order picking</option>
                                        <?php for ($x = 2; $x <= 20; $x++): ?>
                                            <option value="<?php echo $x; ?>" <?php echo (isset($app_user['app_users_picking_amount']) && $app_user['app_users_picking_amount'] == $x)? 'selected' : ''; ?>><?php echo $x; ?> orders</option>
                                        <?php endfor; ?>
                                    </select>
                                </div>
                            <?php
                            endforeach; ?>
                        <?php else: ?>
                            <div class="form-group duplicate_row" style="margin: 10px 0; min-height: 30px;">
                                <input style="float: left; width: 75%;" type="text" class="form-control" name="app_users_name" value="" placeholder="Enter user name"/>
                                <select style="float: right; width: 20%;" name="app_users_picking_amount">
                                    <option value="1">Single order picking</option>
                                    <?php for ($x = 2; $x <= 20; $x++): ?>
                                        <option value="<?php echo $x; ?>"><?php echo $x; ?> orders</option>
                                    <?php endfor; ?>
                                </select>
                            </div>
                        <?php endif; ?>
                        <button class="btn btn-secondary" id="add_app_user">Add</button>
                        <button class="btn btn-primary submit_options" style="font-size: 14px; float: right;">Save</button>
                    </form>
                </div>
            </div>

            <div class="tab-pane fade" id="shops" role="tabpanel">
                <div class="row justify-content-center">
                    <form method="post" style="width: 100%;">
                        <div class="form-group">
                            <input type="text" class="form-control" name="connected_shops" value="<?php echo ( isset($app_settings['connected_shops']) && !empty($app_settings['connected_shops']) )? $app_settings['connected_shops'] : ''; ?>">
                            <i>* To bundle multiple Shopify stores inside Order Picking App, enter your unique API keys comma seperated. Do this for every Shopify store you have to connect to your existing Woocommerce/Prestashop store.</i>
                        </div>
                        <button class="btn btn-primary submit_options" style="font-size: 14px; float: right;">Save</button>
                    </form>
                </div>
            </div>

            <div class="tab-pane fade" id="api_account" role="tabpanel">
                <div class="row justify-content-center">
                    <table class="table">
                        <tbody>
                        <tr>
                            <td>Trial</td>
                            <td><?php echo ( $app_settings['subscription']['trial'] )? 'Yes' : 'No'; ?></td>
                        </tr>
                        <tr>
                            <td>Open trail days</td>
                            <td><?php echo $app_settings['subscription']['trial_days']; ?> </td>
                        </tr>
                        <tr>
                            <td>Start date</td>
                            <td><?php echo $app_settings['subscription']['created']?></td>
                        </tr>
                        </tbody>
                    </table>
                    <button class="btn btn-primary submit_options" style="font-size: 14px; float: right;">Save</button>
                </div>
            </div>

            <div class="tab-pane fade" id="pickingroute" role="tabpanel">
                <div class="row justify-content-center">
                    <form method="post">
                        <h4>Picking order</h4>
                        <select class="" name="pick_by">
                            <option value="category" <?php echo ( empty($app_settings['pick_by']) || ( isset($app_settings['pick_by']) && $app_settings['pick_by'] == 'category') ) ? 'selected=selected' : ''; ?>>Category (Default)</option>
                            <option value="sku_asc" <?php echo ( isset( $app_settings['pick_by'] ) && $app_settings['pick_by'] == 'sku_asc' ) ? 'selected=selected' : ''; ?>>SKU (ASC)</option>
                            <option value="sku_desc" <?php echo ( isset( $app_settings['pick_by'] ) && $app_settings['pick_by'] == 'sku_desc' ) ? 'selected=selected' : ''; ?>>SKU (DESC)</option>
                            <option value="barcode_asc" <?php echo ( isset( $app_settings['pick_by'] ) && $app_settings['pick_by'] == 'barcode_asc' ) ? 'selected=selected' : ''; ?>>Barcode (ASC)</option>
                            <option value="barcode_desc" <?php echo ( isset( $app_settings['pick_by'] ) && $app_settings['pick_by'] == 'barcode_desc' ) ? 'selected=selected' : ''; ?>>Barcode (DESC)</option>
                            <option value="custom_field_asc" <?php echo ( isset( $app_settings['pick_by'] ) && $app_settings['pick_by'] == 'custom_field_asc' ) ? 'selected=selected' : ''; ?>>Custom meta field (ASC)</option>
                            <option value="custom_field_desc" <?php echo ( isset( $app_settings['pick_by'] ) && $app_settings['pick_by'] == 'custom_field_desc' ) ? 'selected=selected' : ''; ?>>Custom meta field (DESC)</option>
                        </select>

                        <br/>

                        <?php
                        if( empty($app_settings['pick_by']) || ( isset($app_settings['pick_by']) && $app_settings['pick_by'] == 'category') ): ?>
                            <p style="font-size: 14px; text-align: center; margin: 0 15%;">Drag the website categories into the correct order to get the most efficient walking route during order picking.</p>
                            <table id="pickingRoute" class="shop_table">
                                <?php
                                if( isset($app_settings['pickroute']) && !empty($app_settings['pickroute']) && is_array($all_categories) ):
                                    $pickroute = $app_settings['pickroute'];
                                    foreach( $pickroute as $main_category_id => $children ):
                                        if( is_array($children) ): ?>
                                            <tr class="main">
                                                <td><i style="font-size: 24px; cursor: grab;" class="fa-solid fa-up-down-left-right"></i><input type="hidden" name="pickroute" value="<?php echo $main_category_id; ?>"/></td>
                                                <td><?php echo $all_categories[$main_category_id]['name']; ?></td>
                                                <td>
                                                    <table class="pickingSubRoute">
                                                        <?php
                                                        foreach( $children as $sub_category_id => $sub_children ):

                                                        	if( is_array($sub_children) ):
                                                                foreach( $sub_children as $sub_sub_category_key => $sub_sub_category_id ):

                                                                	if( !is_numeric($sub_sub_category_id) ){
                                                                		$sub_sub_category_id = $sub_sub_category_key;
                                                                	}

                                                                	// Location deleted
                                                                	if( !isset($all_categories[$sub_category_id]['children'][$sub_sub_category_id]) ){
                                                                		continue;
                                                                	}
                                                                	?>
                                                                    <tr class="sub sub_child">
                                                                        <td><i style="font-size: 24px; cursor: grab;" class="fa-solid fa-up-down-left-right"></i><input type="hidden" name="pickroute" value="<?php echo $main_category_id.'/'.$sub_category_id.'/'.$sub_sub_category_id; ?>"/></td>
                                                                        <td><?php echo $all_categories[$main_category_id]['name']. ' / ' .$all_categories[$main_category_id]['children'][$sub_category_id]. ' / ' .$all_categories[$sub_category_id]['children'][$sub_sub_category_id]; ?></td>
                                                                    </tr>
                                                                    <?php
                                                                 		unset($all_categories[$sub_category_id]['children'][$sub_sub_category_id]);
                                                                endforeach;

                                                                // New/remaining categories
                                                                if( !empty($all_categories[$sub_category_id]['children']) ){
                                                                    foreach( $all_categories[$sub_category_id]['children'] as $sub_sub_category_id ): ?>
                                                                        <tr class="sub">
                                                                            <td><i style="font-size: 24px; cursor: grab;" class="fa-solid fa-up-down-left-right"></i><input type="hidden" name="pickroute" value="<?php echo $main_category_id.'/'.$sub_category_id.'/'.$sub_sub_category_id; ?>"/></td>
                                                                            <td><?php echo $all_categories[$main_category_id]['name']. ' / ' .$all_categories[$main_category_id]['children'][$sub_category_id]. ' / ' .$sub_sub_category_id; ?> ( unordered )</td>
                                                                        </tr>
                                                                    <?php endforeach;
                                                                }

                                                                unset($all_categories[$main_category_id]['children'][$sub_category_id]);
                                                                ?>
                                                            <?php else: ?>
                                                                <tr class="sub child">
                                                                    <td><i style="font-size: 24px; cursor: grab;" class="fa-solid fa-up-down-left-right"></i><input type="hidden" name="pickroute" value="<?php echo $main_category_id .'/'.$sub_category_id; ?>"/></td>
                                                                    <td><?php echo $all_categories[$main_category_id]['name']. ' / ' .$all_categories[$main_category_id]['children'][$sub_category_id]; ?></td>
                                                                </tr>
                                                            <?php endif; ?>

                                                            <?php
                                                            unset($all_categories[$main_category_id]['children'][$sub_category_id]);
                                                        endforeach;

                                                        // New/remaining categories
                                                        if( !empty($all_categories[$main_category_id]['children']) ){
                                                            foreach( $all_categories[$main_category_id]['children'] as $sub_category_id => $sub_category ): ?>
                                                                <tr class="sub">
                                                                    <td><i style="font-size: 24px; cursor: grab;" class="fa-solid fa-up-down-left-right"></i><input type="hidden" name="pickroute" value="<?php echo $main_category_id .'/'.$sub_category_id; ?>"/></td>
                                                                    <td><?php echo $all_categories[$main_category_id]['name']. ' / ' .$sub_category; ?> ( unordered )</td>
                                                                </tr>
                                                            <?php endforeach;
                                                        }
                                                        ?>
                                                    </table>
                                                </td>
                                            </tr>
                                        <?php else: ?>
                                            <tr class="main">
                                                <td><i style="font-size: 24px; cursor: grab;" class="fa-solid fa-up-down-left-right"></i><input type="hidden" name="pickroute" value="<?php echo $main_category_id; ?>"/></td>
                                                <td><?php echo $all_categories[$main_category_id]['name']; ?></td>
                                                <td>-</td>
                                            </tr>
                                        <?php endif;
                                        unset($all_categories[$main_category_id]);
                                    endforeach;

                                    // New/remaining categories
                                    if( !empty($all_categories) ):
                                        foreach( $all_categories as $main_category_id => $main_category ):

                                            if( !isset($main_category['name']) ){
                                                continue;
                                            }

                                            if( isset($main_category['children']) && !empty($main_category['children']) ): ?>
                                                <tr class="main">
                                                    <td><i style="font-size: 24px; cursor: grab;" class="fa-solid fa-up-down-left-right"></i><input type="hidden" name="pickroute" value="<?php echo $main_category_id; ?>"/></td>
                                                    <td><?php echo $main_category['name']; ?> ( unordered )</td>
                                                    <td>
                                                        <table class="pickingSubRoute">
                                                            <?php
                                                            foreach( $main_category['children'] as $sub_category_id => $sub_category ):

                                                                if( isset($all_categories[$sub_category_id]['children']) && !empty($all_categories[$sub_category_id]['children']) ):
                                                                    foreach( $all_categories[$sub_category_id]['children'] as $sub_sub_category_id => $sub_sub_category ): ?>
                                                                        <tr class="sub">
                                                                            <td><i style="font-size: 24px; cursor: grab;" class="fa-solid fa-up-down-left-right"></i><input type="hidden" name="pickroute" value="<?php echo $main_category_id.'/'.$sub_category_id.'/'.$sub_sub_category_id; ?>"/></td>
                                                                            <td><?php echo ( isset($main_category['name']) )? $main_category['name'] : '-'; ?> / <?php echo $sub_category; ?> / <?php echo $sub_sub_category; ?></td>
                                                                        </tr>
                                                                    <?php endforeach; ?>
                                                                </tr>
                                                                <?php else: ?>
                                                                    <tr class="sub">
                                                                        <td><i style="font-size: 24px; cursor: grab;" class="fa-solid fa-up-down-left-right"></i><input type="hidden" name="pickroute" value="<?php echo $main_category_id .'/'.$sub_category_id; ?>"/></td>
                                                                        <td><?php echo ( isset($main_category['name']) )? $main_category['name'] : '-'; ?> / <?php echo $sub_category; ?></td>
                                                                    </tr>
                                                                <?php endif; ?>

                                                            <?php endforeach; ?>
                                                        </table>
                                                    </td>
                                                </tr>
                                            <?php else: ?>
                                                <tr class="main">
                                                    <td><i style="font-size: 24px; cursor: grab;" class="fa-solid fa-up-down-left-right"></i><input type="hidden" name="pickroute" value="<?php echo $main_category_id; ?>"/></td>
                                                    <td><?php echo $main_category_id; ?> <?php echo $main_category['name']; ?> ( unordered )</td>
                                                    <td>-</td>
                                                </tr>
                                            <?php endif; ?>
                                        <?php endforeach;
                                    endif;
                                else:
                                    if( !empty($all_categories) ):
                                        foreach( $all_categories as $main_category_id => $main_category ):
                                            if( !isset($main_category['name']) || empty($main_category['name']) ){
                                                continue;
                                            }
                                            if( isset($main_category['children']) && !empty($main_category['children']) ): ?>
                                                <tr class="main">
                                                    <td><i style="font-size: 24px; cursor: grab;" class="fa-solid fa-up-down-left-right"></i><input type="hidden" name="pickroute" value="<?php echo $main_category_id; ?>"/></td>
                                                    <td><?php echo $main_category['name']; ?></td>
                                                    <td>
                                                        <table class="pickingSubRoute">
                                                            <?php
                                                            foreach( $main_category['children'] as $sub_category_id => $sub_category ):

                                                                if( isset($all_categories[$sub_category_id]['children']) && !empty($all_categories[$sub_category_id]['children']) ):
                                                                    foreach( $all_categories[$sub_category_id]['children'] as $sub_sub_category_id => $sub_sub_category ): ?>
                                                                        <tr class="sub">
                                                                            <td><i style="font-size: 24px; cursor: grab;" class="fa-solid fa-up-down-left-right"></i><input type="hidden" name="pickroute" value="<?php echo $main_category_id.'/'.$sub_category_id.'/'.$sub_sub_category_id; ?>"/></td>
                                                                            <td>#<?php echo ( isset($main_category['name']) )? $main_category['name'] : '-'; ?> / <?php echo $sub_category; ?> / <?php echo $sub_sub_category; ?></td>
                                                                        </tr>
                                                                    <?php endforeach; ?>
                                                                </tr>
                                                                <?php else: ?>
                                                                    <tr class="sub">
                                                                        <td><i style="font-size: 24px; cursor: grab;" class="fa-solid fa-up-down-left-right"></i><input type="hidden" name="pickroute" value="<?php echo $main_category_id .'/'.$sub_category_id; ?>"/></td>
                                                                        <td>#<?php echo ( isset($main_category['name']) )? $main_category['name'] : '-'; ?> / <?php echo $sub_category; ?></td>
                                                                    </tr>
                                                                <?php endif; ?>

                                                            <?php endforeach; ?>
                                                        </table>
                                                    </td>
                                                </tr>
                                            <?php else: ?>
                                                <tr class="main">
                                                    <td><i style="font-size: 24px; cursor: grab;" class="fa-solid fa-up-down-left-right"></i><input type="hidden" name="pickroute" value="<?php echo $main_category_id; ?>"/></td>
                                                    <td><?php echo $main_category['name']; ?></td>
                                                    <td>-</td>
                                                </tr>
                                            <?php endif; ?>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                <?php endif; ?>
                            </table>
                        <?php endif; ?>

                        <?php if( empty($pickroute) && empty($all_categories) ):?>
                            <i>* There is no connection available with your webshop. Please check your credentials.</i>
                        <?php endif; ?>

                        <button class="btn btn-primary submit_options" style="font-size: 14px; float: right;">Save</button>
                    </form>
                </div>
            </div>

            <div class="tab-pane fade" id="logs" role="tabpanel">
                <div class="row justify-content-center">
                    <h5>Logs</h5>
                    <p style="font-size: 14px; text-align: center; margin: 0 15%;">This data is useful for our technical department if something goes wrong.</p>
                    <?php
                    if( isset($client_logs) && !empty($client_logs) ):

                        $i = 0;
                        foreach( $client_logs as $client_log_day => $client_day_logs ): ?>
                            <button class="btn btn-secondary" style="margin-bottom: 10px;" type="button" data-bs-toggle="collapse" data-bs-target="#Day<?php echo $i; ?>" aria-expanded="false" aria-controls="Day<?php echo $i; ?>">
                                Date: <?php echo $client_log_day; ?>
                            </button>
                            <?php

                            foreach( $client_logs as $client_log_day => $client_day_logs ): ?>
                                <div class="collapse" id="Day<?php echo $i; ?>">
                                    <div class="card card-body">
                                        <ul style="list-style: none; max-height: 500px; overflow: scroll;">
                                            <?php
                                            foreach( $client_day_logs as $client_log ){
                                                echo '<li style="color: #000;"><strong style="min-width: 150px;display: inline-block;">'.$client_log['created'].'</strong>'.$client_log['message'].'</li>';
                                            }
                                            ?>
                                        </ul>
                                    </div>
                                </div>
                                <?php
                                $i++;
                            endforeach;

                        endforeach;
                    endif; ?>
                </div>
            </div>

        </div>
    <?php endif; ?>
</div>

<section class="integration-section" style="margin-top: 25px;">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-5 col-md-12">
                <a href="https://apps.apple.com/app/orderpickingapp/id6444366422" target="_blank" title="Install Order Picking App for IOS" class="mb-4 mb-lg-0 mb-xl-0 position-relative text-decoration-none connected-app-single border border-light border-2 rounded-custom d-block overflow-hidden p-5">
                    <div class="position-relative connected-app-content">
                        <div class="integration-logo bg-custom-light rounded-circle p-2 d-inline-block">
                            <img src="https://orderpickingapp.com/wp-content/uploads/2022/12/Logo-Apple.png" width="40" alt="Apple iOS" class="img-fluid">
                        </div>
                        <h5 style="margin: 0;">Apple iOS</h5>
                        <p class="mb-0 text-body">The Orderpicking App is compatible with all versions of Apple iOS. Even on an iPad</p>
                    </div>
                    <span class="position-absolute integration-badge badge px-3 py-2 bg-primary-soft text-primary">Download the app</span>
                </a>
            </div>

            <div class="col-lg-5 col-md-12">
                <a href="https://play.google.com/store/apps/details?id=com.orderpickingapp.app" target="_blank" title="Install Order Picking App for Android"  class="position-relative text-decoration-none connected-app-single border border-light border-2 rounded-custom d-block overflow-hidden p-5">
                    <div class="position-relative connected-app-content">
                        <div class="integration-logo bg-custom-light rounded-circle p-2 d-inline-block">
                            <img src="https://orderpickingapp.com/wp-content/uploads/2022/12/Logo-Android.png" width="40" alt="integration" class="img-fluid">
                        </div>
                        <h5 style="margin: 0;">Google Android OS</h5>
                        <p class="mb-0 text-body">The Orderpicking App is compatible with all versions of Android OS. Even on a tablet</p>
                    </div>
                    <span class="position-absolute integration-badge badge px-3 py-2 bg-danger-soft text-danger">Download the app</span>
                </a>
            </div>
        </div>
    </div>
</section>
