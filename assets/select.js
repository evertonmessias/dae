function showrow(tab){
    $(".result").attr("placeholder","wait ...");
    $.ajax({
        type: "POST",
        url: "showrow",
        data: {tab:tab},
        success: function(res){
            $(".result").attr("placeholder","Rows ?  (max. "+res+" rows)").prop('disabled', false);
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