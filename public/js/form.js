function dateMatchesRegex(dateString) {
    if (
        dateString.match(
            /^(-?[0-9]*[1-9]+0*)(-(0?[1-9]|1[012])-(0?[1-9]|[12][0-9]|3[01])(T(0?[0-9]|1[0-9]|2[0-3])(:(0?[0-9]|1[0-9]|2[0-9]|3[0-9]|4[0-9]|5[0-9])(:(0?[0-9]|1[0-9]|2[0-9]|3[0-9]|4[0-9]|5[0-9])([.][0-9]+)?)?)?)?)?$/
        )
    )
        return true;
    else if (
        dateString.match(
            /^(0?[1-9]|[12][0-9]|3[01])\/(0?[1-9]|1[012])\/(-?[0-9]*[1-9]+0*)( ((0?[0-9]|1[0-9]|2[0-3]):(0[0-9]|1[0-9]|2[0-9]|3[0-9]|4[0-9]|5[0-9])))?$/
        )
    )
        return true;
    return false;
}

$(function () {

    $(".menu-nav-button").on("click", function () {
        let navPane = $(".menu-nav");
        let menuWidth = navPane.outerWidth();
        navPane.css("right", -menuWidth);
        navPane.show();
        navPane.animate({ right: 0 }, 350);
    });

    $(".menu-nav-close").on("click", function () {
        let navPane = $(".menu-nav");
        let menuWidth = navPane.outerWidth();
        navPane.css("right", 0);
        navPane.animate({ right: -menuWidth }, 350, function () {
            navPane.hide();
        });
    });

    $(".submenu-toggle").on("click", function (e) {
        e.preventDefault();
        const element = this;
        const submenu = $(this).parent().next(".submenu");
        submenu.slideToggle();
        if (element.classList.contains("icon-arrow-down-dark")) {
            element.classList.remove("icon-arrow-down-dark");
            element.classList.add("icon-arrow-up-dark");
        } else if (element.classList.contains("icon-arrow-up-dark")) {
            element.classList.remove("icon-arrow-up-dark");
            element.classList.add("icon-arrow-down-dark");
        }
    });

    // Ensure the submenu is expanded by default
    $(".submenu").hide();
    $(".submenu-toggle").addClass("icon-arrow-down-dark");
});