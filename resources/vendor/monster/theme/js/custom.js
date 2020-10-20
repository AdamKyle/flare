/*
Template Name: Monster Admin
Author: Themedesigner
Email: niravjoshi87@gmail.com
File: js
*/
$(function () {
    "use strict";
    $(function () {
        $(".preloader").fadeOut();
    });
    jQuery(document).on('click', '.mega-dropdown', function (e) {
        e.stopPropagation()
    });
    // ==============================================================
    // This is for the top header part and sidebar part
    // ==============================================================
     var set = function () {
            var width = (window.innerWidth > 0) ? window.innerWidth : this.screen.width;
            var topOffset = 70;

            if (width > 470) {
                $("body").addClass("hidden-mini-sidebar");
                $('.navbar-brand span').hide();
                $(".scroll-sidebar, .slimScrollDiv").css("overflow-x", "visible").parent().css("overflow", "visible");
                $(".sidebartoggler i").addClass("ti-menu");
            }
            else {
                $("body").removeClass("hidden-mini-sidebar");
                $('.navbar-brand span').show();
                $(".sidebartoggler i").removeClass("ti-menu");
            }

            // var height = ((window.innerHeight > 0) ? window.innerHeight : this.screen.height) - 1;
            // height = height - topOffset;
            // if (height < 1) height = 1;
            // if (height > topOffset) {
            //     $(".page-wrapper").css("min-height", (height) + "px");
            // }

    };
    // $(window).ready(set);
    // $(window).on("resize", set);
    // ==============================================================
    // Theme options
    // ==============================================================
    $(".sidebartoggler").on('click', function () {
        if ($("body").hasClass("mini-sidebar")) {
            //console.log('clicked');
            // $("body").trigger("resize");
            // $(".scroll-sidebar, .slimScrollDiv").css("overflow", "hidden").parent().css("overflow", "visible");
            $("body").removeClass("mini-sidebar");
            // $('.navbar-brand span').show();
            // $(".sidebartoggler i").addClass("ti-menu");
        }
        else {
            // $("body").trigger("resize");
            // $(".scroll-sidebar, .slimScrollDiv").css("overflow-x", "visible").parent().css("overflow", "visible");
            $("body").addClass("mini-sidebar");
            // $('.navbar-brand span').hide();
            // $(".sidebartoggler i").removeClass("ti-menu");
        }
    });

    $(".fix-header .topbar").stick_in_parent({

    });

    // ==============================================================
    //tooltip
    // ==============================================================
    $(function () {
        $('[data-toggle="tooltip"]').tooltip()
    });
    // ==============================================================
    //Popover
    // ==============================================================
    $(function () {
        $('[data-toggle="popover"]').popover()
    });
});
