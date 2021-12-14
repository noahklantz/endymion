jQuery(document).ready(function($) {
    $('#block-endymion-main-menu li a').hover(function(){
        $(this).find('.sub-menu').toggle();
    });
});