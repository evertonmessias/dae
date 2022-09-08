$(() => {
    $(".select-submit").click(() => {
        if ($(".result").val() != "") {
            $(".wait-submit").show();
        }
    })
});

function showrow(tab) {
    $(".result").hide();
    $(".form .wait").show();
    $(".form .ico-wait").show();
    $.ajax({
        type: "POST",
        url: "showrow",
        data: { tab: tab },
        success: function (res) {
            $(".form .wait").hide();
            $(".form .ico-wait").hide();
            $(".result").attr("placeholder", "( " + res + " rows )").show();
        }
    });
}

const sorting = document.querySelector('.selectpicker');
const commentSorting = document.querySelector('.selectpicker');
const sortingchoices = new Choices(sorting, {
    placeholder: false,
    itemSelectText: ''
});

let sortingClass = sorting.getAttribute('class');
window.onload = function () {
    sorting.parentElement.setAttribute('class', sortingClass);
}