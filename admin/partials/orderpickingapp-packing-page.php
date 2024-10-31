<link rel="stylesheet" href="<?php echo OPA_PLUGIN_URL; ?>admin/css/orderpickingapp.css" type="text/css" media="all">
<script type="text/javascript" src="<?php echo OPA_PLUGIN_URL; ?>admin/js/bootstrap.min.js"></script>
<script type="text/javascript" src="<?php echo OPA_PLUGIN_URL; ?>admin/js/bootstrap-toggle.min.js"></script>
<script type="text/javascript" src="https://code.jquery.com/ui/1.13.2/jquery-ui.min.js"></script>
<script type="text/javascript" src="<?php echo OPA_PLUGIN_URL; ?>admin/js/admin.js"></script>

<div id='orderpickingapp-packing' class='wrap'>
    <?php
    $orderpickingapp_apikey = get_option('orderpickingapp_apikey');
    if( isset($orderpickingapp_apikey) && !empty($orderpickingapp_apikey) ):

        $OrderPickingApp = new OrderPickingApp();
        $packing_orders = $OrderPickingApp->getPackingOrders();

        if( isset($packing_orders) && count($packing_orders) == 3 ): ?>
            <h2>No open packing orders to collect!</h2>
        <?php else: ?>
            <h2>BETA: DESKTOP PACKING</h2>
            <style>
                #packing {
                    --bs-body-color: #000;
                    color: #000 !important;
                    --bs-white-rgb: rgb(0,0,0);
                }
                #packing p,
                #packing li,
                #packing span  { color: #000 !important; }
                #packing .open { background-color: #24b3eb; }
                #packing .packing_product.completed { background: #50d950; }
                #packing .packing_product.backorder { background: #ff7100; }
                #packing .packing_product .backorder {
                    background: #ff7100;
                    border-radius: 5px;
                    color: #fff;
                    max-width: 50px;
                    text-align: center;
                    padding: 5px;
                }
                #packing .clickers { font-size: 24px; }
                #packing .decrement i { color: red; font-size: 24px; }
                #packing .increment i { color: green; font-size: 24px; }
                #packing .packedAmount { font-size: 30px; margin-bottom: 10px; }
            </style>

            <div class="row justify-content-center">

                <table class="table">
                    <thead>
                    <tr>
                        <th>Batch</th>
                        <th>Order</th>
                        <th>Date</th>
                        <th>Name</th>
                        <th>Items</th>
                        <th>Backorder</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php
                    foreach( $packing_orders as $packing_order ): ?>
                        <tr class="order_list_item <?php echo ( $packing_order['onbackorder'] === 'true')? 'onbackorder' : ''; ?>" data-orderid="<?php echo $packing_order['orderid']; ?>">
                            <td><?php echo $packing_order['batch_id']; ?></td>
                            <td>#<?php echo ( isset($packing_order['order_number']) && !empty($packing_order['order_number']) )? $packing_order['order_number'] : $packing_order['orderid']; ?></td>
                            <td><?php echo $packing_order['date']; ?></td>
                            <td><?php echo $packing_order['lastname']; ?></td>
                            <td><?php echo $packing_order['items']; ?></td>
                            <td><?php echo ( $packing_order['onbackorder'] === 'true')? 'Yes' : 'No'; ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>

                <?php
                $use_barcodescanner = get_option('use_barcodescanner');
                if( isset($use_barcodescanner) && $use_barcodescanner == 'android' ): ?>
                    <div style="color: green; text-align: center; background: green;">
                        <p style="margin: 0; padding: 5px; color: #fff;"><img style="height: 20px; float: left;" src="<?php echo OPA_PLUGIN_URL; ?>admin/images/scanner-gun-thin.svg"/><span style="color: #fff !important;">Scanner enabled</span></p>
                    </div>
                    <input id="barcodeScannerInput" name="barcode" type="text" autofocus value=""/>
                    <br/>
                <?php endif; ?>

                <?php
                foreach( $packing_orders as $packing_order ): ?>
                    <div id="<?php echo $packing_order['orderid']; ?>" class="order_detail" data-token="<?php echo $orderpickingapp_apikey; ?>" style="display: none; border: 1px solid #3dc2ff; border-radius: 5px; padding: 5px; margin-top: 25px;">

                        <h2 style="text-align: center; color: #000;">#<?php echo ( isset($packing_order['order_number']) && !empty($packing_order['order_number']) )? $packing_order['order_number'] : $packing_order['orderid']; ?></h2>

                        <?php if( $packing_order['onbackorder'] === 'true' ): ?>
                            <p style="text-align: center;margin: 20px;display: block;background: #ff7100;padding: 5px;border-radius: 5px;color: #fff;">Notice: this order contains backorder products!</p>
                        <?php endif; ?>

                        <table style="width: 100%;">
                            <?php foreach( $packing_order['products'] as $product ): ?>
                                <tr class="packing_product" data-sku="<?php echo $product['sku']; ?>" data-barcode="<?php echo $product['barcode']; ?>" data-ordered="<?php echo $product['ordered']; ?>" data-unpacked="<?php echo $product['unpacked']; ?>">
                                    <td style="width: 33.3333%;">
                                        <div class="<?php echo $product['status']; ?>">
                                            <p style="max-width: 50px;text-align: center;display: block; font-size: 9px; position: relative;margin: 0;">PICK</p>
                                            <i style="max-width: 50px;text-align: center;display: block; font-size: 9px;"><?php echo $product['quantity']; ?> / <?php echo $product['quantity'] - $product['backorder']; ?></i>
                                        </div>
                                        <hr style="border-bottom: 1px solid #000; margin: 5px 0; max-width: 50px;">
                                        <p style="max-width: 50px;text-align: center;display: block; font-size: 11px; margin-bottom: 0;">PACK</p>
                                        <i style="max-width: 50px;text-align: center;display: block;"><?php echo $product['ordered']; ?> / <span class="packed" style="font-size: 11px;"><?php echo $product['ordered'] - $product['unpacked']; ?></span></i>
                                    </td>
                                    <td style="width: 33.3333%;">
                                        <img style="text-align: center; margin: 0 auto; max-width: 200px;" src="<?php echo $product['thumbnail']; ?>"/>
                                    </td>
                                    <td style="width: 33.3333%;">
                                        <ul style="list-style: none; text-align: left; margin: 0; padding: 0;">
                                            <li><strong><?php echo $product['title']; ?></strong></li>
                                            <li style="font-size: 14px;"><span style="min-width: 65px; display: inline-block;">Barcode:</span> <?php echo ( isset($product['barcode']) && !empty($product['barcode']) )? $product['barcode'] : $product['sku']; ?></li>
                                            <li style="font-size: 14px;"><span style="min-width: 65px; display: inline-block;">SKU:</span> <?php echo ( isset($product['sku_value']) && !empty($product['sku_value']) )? $product['sku_value'] : $product['sku']; ?></li>

                                            <?php if( isset($product['reference']) && !empty($product['reference']) ): ?>
                                                <li style="font-size: 11px;">Ref: <?php echo $product['reference']; ?></li>
                                            <?php endif; ?>

                                            <?php if( isset($product['product_reference']) && !empty($product['product_reference']) ): ?>
                                                <li style="font-size: 11px;">Product ref: <?php echo $product['product_reference']; ?></li>
                                            <?php endif; ?>

                                            <?php if( isset($product['custom_field_label']) && !empty($product['custom_field_label']) ): ?>
                                                <li style="font-size: 11px;"><span style="min-width: 65px; display: inline-block;"><?php echo $product['custom_field_label']; ?>:</span> <?php echo $product['custom_field']; ?></li>
                                            <?php endif; ?>
                                        </ul>
                                    </td>
                                    <td>
                                        <div class="row" style="min-width: 150px;text-align: center;">
                                            <div class="col-4 clickers decrement" data-barcode="<?php echo ( isset($product['barcode']) && !empty($product['barcode']) )? $product['barcode'] : $product['sku']; ?>" data-ordered="<?php echo $product['ordered']; ?>"><span class="dashicons dashicons-remove"></span></div>
                                            <span class="col-4 packedAmount">0</span>
                                            <div class="col-4 clickers increment" data-barcode="<?php echo ( isset($product['barcode']) && !empty($product['barcode']) )? $product['barcode'] : $product['sku']; ?>" data-ordered="<?php echo $product['ordered']; ?>"><span class="dashicons dashicons-insert"></span></div>
                                        </div>
                                        <p style="cursor: pointer; text-align: center; text-decoration: underline; margin: 0 auto; display: block;" class="completeAmount" data-ordered="<?php echo $product['ordered']; ?>">Check all</p>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </table>

                        <button class="completeOrder btn btn-primary" style="font-size: 14px;margin-top: 5px;">Complete package</button>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    <?php else: ?>
        <h2>Desktop packing not available. Please register your store and start using the #1 picking tool!</h2>
    <?php endif; ?>
</div>