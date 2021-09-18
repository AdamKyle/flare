/*
Template Name: Monster Admin
Author: Themedesigner
Email: niravjoshi87@gmail.com
File: js
*/
$(function () {
    $(".sidebartoggler").on('click', function () {
        if ($("body").hasClass("mini-sidebar")) {
            $("body").removeClass("mini-sidebar");
            $("body").addClass("mini-sidebar-expanded");
        }
        else {
            $("body").removeClass("mini-sidebar-expanded");
            $("body").addClass("mini-sidebar");
        }
    });

    $('#sidebarnav li a').unbind('click').on('click', function(event) {
        event.preventDefault();
        const foundElement = $(this).parent().find('ul');

        if (foundElement.hasClass('collapse')) {
            foundElement.attr('aria-expanded', true);
            foundElement.removeClass('collapse');
        } else {
            if ($(this).attr('href')) {
                window.location = $(this).attr('href');
            }

            foundElement.attr('aria-expanded', false);
            foundElement.addClass('collapse');
        }
    });
});
