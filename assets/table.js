$(document).ready(function () {

    var table = $('#result').DataTable({
        lengthChange: false,
        buttons: ['excel', 'pdf']
    });
    table.buttons().container().appendTo(".dae-export");

});