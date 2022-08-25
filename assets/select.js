function showrow(tab){
    $(".result").attr("placeholder","Wait ...");
    $.ajax({
        type: "POST",
        url: "showrow",
        data: {tab:tab},
        success: function(res){
            $(".result").attr("placeholder","(Max. "+res+" rows)").prop('disabled', false);
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
window.onload= function () {
    $(".result").prop('disabled', true);
    sorting.parentElement.setAttribute('class', sortingClass);
}