<?php echo $header; ?>

<?php echo $content_top; ?>

<div class="container">
    <div class="row">

        <div id="content">
            <div class="container">

                <!-- STATIC -->
                <div class="row commonrow">

                    <div class="col-md-4">
                        <?php echo $winners; ?>
                    </div>
                    <div class="col-md-8">

                        <div class="headbox">

                            <div class="catssort decoratemenu" id="search-lottypes">
                                <a href="<?php echo $links["link"]; ?>" data-category="" class="catssort-item active">
                                    <span class="catssort-title">Все категории</span>
                                </a>
                                <a href="<?php echo $links["link"]; ?>" data-category="product" class="catssort-item">
                                    <span class="catssort-title">Товары</span>
                                </a>
                                <a href="<?php echo $links["link"]; ?>" data-category="money" class="catssort-item">
                                    <span class="catssort-title">Деньги</span>
                                </a>
                                <a href="<?php echo $links["link"]; ?>" data-category="exclusive" class="catssort-item">
                                    <span class="catssort-title">Эксклюзив</span>
                                </a>
                            </div>

                        </div>

                        <div id="content" class="<?php echo $class; ?>">


                            <div class="col-sm-6 col-sm-offset-3 text-center message hidden" id="empty-message">
                                <img class="message-img" src="./catalog/view/theme/default/image/profile/inactive-big.png" alt="" />
                                <div class="message-text"><?php echo $i18l->error_no_category_lots; ?></div>
                            </div>
                            <!-- SECTION OUTPUT-->
                            <section class="product-output"></section>
                            <!-- END SECTION OUTPUT-->
                        </div>


                    </div>
                </div>
                <!-- END STATIC -->
            </div>

            <?php echo $content_bottom; ?>

        </div>

        <?php echo $column_right; ?>

    </div>
</div>

<script>
    var start = 0, category = '', search = '';
    var link = '';
    var lock = false;

    function getSearchFiltered(is_new) {
        if ( !lock ) {

            lock = true;

            if (is_new) {
                start = 0;
                $('section.product-output').html('');
            }
            $('#empty-message').addClass('hidden');
            $('.preloader').removeClass('hidden');
            $.ajax(link, {
                method: 'POST',
                data: {
                    action: 'next',
                    category: category,
                    start: start
                },
                success: function success(response) {
                    $('.preloader').addClass('hidden');
                    if (response.success) {
                        var next = parseInt(response.next);
                        var total = parseInt(response.total);
                        $('span.found-num').html(total);
                        if (total) {
                            if (response.html.length) {
                                $('section.product-output:last').append(response.html);
                                start = next;
                            }
                        } else {
                            $('#empty-message').removeClass('hidden');
                        }
                        if ( !next ) {
                            $(window).off('scroll', loadByScroll );
                        }

                        lock = false;

                        // init social block
                        var script = "//s7.addthis.com/js/300/addthis_widget.js#pubid=<?php echo $addthisData['ID']; ?>";
                        if (window.addthis){
                            window.addthis = null;
                        }
                        $.getScript( script , function() { addthis.init(); });
                    }
                }
            });
        }
    }

    function loadByScroll() {

        var win = $(window);

        if ($(document).height() - win.height() == win.scrollTop()) {
            getSearchFiltered(false);
        }

    }

    $(document).ready(function() {

        link = $('#search-lottypes a:first-child').attr('href');
        search = $('header #search input[name=\'search\']').val();

        getSearchFiltered(true);

        $(window).on('scroll', loadByScroll );

        var clipboard = new Clipboard('.productview-copy-btn');
        clipboard.on('success', function() {
            $('body').append('<div class="clipboard-message">Ссылка скопирована</div>');
            $('.clipboard-message').fadeIn('fast');
            setTimeout(function(){
                $('.clipboard-message').fadeOut();
            }, 2000)
        });

    });
</script>

<?php echo $footer; ?>