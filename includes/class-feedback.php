<?php
class OPA_Uninstall_Feedback
{

    public function __construct()
    {
        add_action('admin_footer', array($this, 'deactivate_scripts'));
        add_action('wp_ajax_opa_submit_uninstall_reason', array($this, "send_uninstall_reason"));
    }

    public function deactivate_scripts()
    {

        global $pagenow;
        if ('plugins.php' != $pagenow) {
            return;
        }
        $reasons = $this->get_uninstall_reasons();
        ?>
        <div class="opa-modal" id="opa-opa-modal">
            <div class="opa-modal-wrap">
                <div class="opa-modal-header">
                    <h3><?php _e('If you have a moment, please let us know why you are deactivating:', 'orderpickingapp'); ?></h3>
                </div>
                <div class="opa-modal-body">
                    <ul class="reasons">
                        <?php foreach ($reasons as $reason) { ?>
                            <li data-type="<?php echo esc_attr($reason['type']); ?>"
                                data-placeholder="<?php echo esc_attr($reason['placeholder']); ?>">
                                <label><input type="radio" name="selected-reason"
                                              value="<?php echo $reason['id']; ?>"> <?php echo $reason['text']; ?>
                                </label>
                            </li>
                        <?php } ?>
                    </ul>
                </div>
                <div class="opa-modal-footer">
                    <a class="button-primary" href="https://orderpickingapp.com" target="_blank">
                        <span class="dashicons dashicons-external" style="margin-top:3px;"></span>
                        <?php _e('Get support', 'orderpickingapp'); ?></a>
                    <button class="button-primary opa-model-submit"><?php _e('Submit & Deactivate', 'orderpickingapp'); ?></button>
                    <button class="button-secondary opa-model-cancel"><?php _e('Cancel', 'orderpickingapp'); ?></button>
                </div>
            </div>
        </div>

        <style type="text/css">
            .opa-modal {
                position: fixed;
                z-index: 99999;
                top: 0;
                right: 0;
                bottom: 0;
                left: 0;
                background: rgba(0, 0, 0, 0.5);
                display: none;
            }

            .opa-modal.modal-active {
                display: block;
            }

            .opa-modal-wrap {
                width: 50%;
                position: relative;
                margin: 10% auto;
                background: #fff;
            }

            .opa-modal-header {
                border-bottom: 1px solid #eee;
                padding: 8px 20px;
            }

            .opa-modal-header h3 {
                line-height: 150%;
                margin: 0;
            }

            .opa-modal-body {
                padding: 5px 20px 20px 20px;
            }

            .opa-modal-body .input-text, .opa-modal-body textarea {
                width: 75%;
            }

            .opa-modal-body .reason-input {
                margin-top: 5px;
                margin-left: 20px;
            }

            .opa-modal-footer {
                border-top: 1px solid #eee;
                padding: 12px 20px;
                text-align: right;
            }

            .reviewlink, .supportlink {
                padding: 10px 0px 0px 35px !important;
                font-size: 14px;
            }

            .review-and-deactivate {
                padding: 5px;
            }

            .wt-uninstall-feedback-privacy-policy {
                text-align: left;
                font-size: 12px;
                color: #aaa;
                line-height: 14px;
                margin-top: 20px;
                font-style: italic;
            }

            .wt-uninstall-feedback-privacy-policy a {
                font-size: 11px;
                color: #4b9cc3;
                text-decoration-color: #99c3d7;
            }
        </style>
        <script type="text/javascript">
            (function ($) {
                $(function () {
                    var modal = $('#opa-opa-modal');
                    var deactivateLink = '';

                    $(document).on('click', '#deactivate-order-picking-app', function (e) {
                        e.preventDefault();
                        modal.addClass('modal-active');
                        deactivateLink = $(this).attr('href');
                        modal.find('a.dont-bother-me').attr('href', deactivateLink).css('float', 'left');
                    });

                    modal.on('click', 'a.review-and-deactivate', function (e) {
                        e.preventDefault();
                        window.open("https://wordpress.org/plugins/order-picking-app/reviews/#new-post");
                        window.location.href = deactivateLink;
                    });
                    modal.on('click', 'a.doc-and-support-doc', function (e) {
                        e.preventDefault();
                        window.open("https://orderpickingapp.com/support/");
                    });
                    modal.on('click', 'a.doc-and-support-forum', function (e) {
                        e.preventDefault();
                        window.open("https://orderpickingapp.com/contact/");
                    });
                    modal.on('click', 'button.opa-model-cancel', function (e) {
                        e.preventDefault();
                        modal.removeClass('modal-active');
                    });
                    modal.on('click', 'input[type="radio"]', function () {
                        var parent = $(this).parents('li:first');
                        modal.find('.reason-input').remove();
                        var inputType = parent.data('type'),
                            inputPlaceholder = parent.data('placeholder');
                        var reasonInputHtml = '';
                        if ('reviewhtml' === inputType) {
                            if ($('.reviewlink').length == 0) {
                                reasonInputHtml = '<div class="reviewlink"><a href="#" target="_blank" class="review-and-deactivate"><?php _e('Deactivate and leave a review', 'orderpickingapp'); ?> <span class="xa-opa-rating-link"> &#9733;&#9733;&#9733;&#9733;&#9733; </span></a></div>';
                            }
                        } else if ('supportlink' === inputType) {
                            if ($('.supportlink').length == 0) {
                                reasonInputHtml = '<div class="supportlink"><?php _e('Please go through the', 'orderpickingapp'); ?><a href="#" target="_blank" class="doc-and-support-doc"> <?php _e('documentation', 'orderpickingapp'); ?></a> <?php _e('or contact us via', 'orderpickingapp'); ?><a href="#" target="_blank" class="doc-and-support-forum"> <?php _e('support', 'orderpickingapp'); ?></a></div>';
                            }
                        } else {
                            if ($('.reviewlink').length) {
                                $('.reviewlink').remove();
                            }
                            if ($('.supportlink').length) {
                                $('.supportlink').remove();
                            }
                            reasonInputHtml = '<div class="reason-input">' + (('text' === inputType) ? '<input type="text" class="input-text" size="40" />' : '<textarea rows="5" cols="45"></textarea>') + '</div>';
                        }
                        if (inputType !== '') {
                            parent.append($(reasonInputHtml));
                            parent.find('input, textarea').attr('placeholder', inputPlaceholder).focus();
                        }
                    });

                    modal.on('click', 'button.opa-model-submit', function (e) {
                        e.preventDefault();
                        var button = $(this);
                        if (button.hasClass('disabled')) {
                            return;
                        }
                        var $radio = $('input[type="radio"]:checked', modal);
                        var $selected_reason = $radio.parents('li:first'),
                            $input = $selected_reason.find('textarea, input[type="text"]');

                        $.ajax({
                            url: ajaxurl,
                            type: 'POST',
                            data: {
                                action: 'opa_submit_uninstall_reason',
                                reason_id: (0 === $radio.length) ? 'none' : $radio.val(),
                                reason_info: (0 !== $input.length) ? $input.val().trim() : ''
                            },
                            beforeSend: function () {
                                button.addClass('disabled');
                                button.text('Processing...');
                            },
                            complete: function () {
                                window.location.href = deactivateLink;
                            }
                        });
                    });
                });
            }(jQuery));
        </script>
        <?php
    }

    public function send_uninstall_reason()
    {

        if (!isset($_POST['reason_id'])) {
            return;
        }

        $body = '<table>';
        $body .= '<tr>';
        $body .= '<td>Site:</td><td>' . home_url() . '</td>';
        $body .= '<td>Reden:</td><td>' . $_POST['reason_id'] . '</td>';
        $body .= '<td>Aanvullende informatie:</td><td>' . $_POST['reason_info'] . '</td>';
        $body .= '</tr>';
        $body .= '</table>';

        $to = 'support@orderpickingapp.nl';
        $subject = 'Woocommerce deactivatie feedback';
        $headers = array('Content-Type: text/html; charset=UTF-8');
        wp_mail($to, $subject, $body, $headers);
    }

    private function get_uninstall_reasons()
    {

        $reasons = array(
            array(
                'id' => 'used-it',
                'text' => __('Used it successfully. Don\'t need anymore.', 'orderpickingapp'),
                'type' => 'reviewhtml',
                'placeholder' => __('Have used it successfully and aint in need of it anymore', 'orderpickingapp')
            ),
            array(
                'id' => 'temporary-deactivation',
                'text' => __('Temporary deactivation', 'orderpickingapp'),
                'type' => '',
                'placeholder' => __('Temporary de-activation. Will re-activate later.', 'orderpickingapp')
            ),
            array(
                'id' => 'could-not-understand',
                'text' => __('I couldn\'t understand how to make it work', 'orderpickingapp'),
                'type' => 'supportlink',
                'placeholder' => __('Would you like us to assist you?', 'orderpickingapp')
            ),
            array(
                'id' => 'found-better-plugin',
                'text' => __('I found a better plugin', 'orderpickingapp'),
                'type' => 'text',
                'placeholder' => __('Which plugin?', 'orderpickingapp')
            ),
            array(
                'id' => 'not-have-that-feature',
                'text' => __('The plugin is great, but I need specific feature that you don\'t support', 'orderpickingapp'),
                'type' => 'textarea',
                'placeholder' => __('Could you tell us more about that feature?', 'orderpickingapp')
            ),
            array(
                'id' => 'is-not-working',
                'text' => __('The plugin is not working', 'orderpickingapp'),
                'type' => 'textarea',
                'placeholder' => __('Could you tell us a bit more whats not working?', 'orderpickingapp')
            ),
            array(
                'id' => 'looking-for-other',
                'text' => __('It\'s not what I was looking for', 'orderpickingapp'),
                'type' => 'textarea',
                'placeholder' => __('Could you tell us a bit more?', 'orderpickingapp')
            ),
            array(
                'id' => 'did-not-work-as-expected',
                'text' => __('The plugin didn\'t work as expected', 'orderpickingapp'),
                'type' => 'textarea',
                'placeholder' => __('What did you expect?', 'orderpickingapp')
            ),
            array(
                'id' => 'other',
                'text' => __('Other', 'orderpickingapp'),
                'type' => 'textarea',
                'placeholder' => __('Could you tell us a bit more?', 'orderpickingapp')
            ),
        );

        return $reasons;
    }

}