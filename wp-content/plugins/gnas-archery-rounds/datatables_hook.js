// set up datatables behaviour for the rounds table
//
// the anonymous function is executed when the web page is
// "ready" (i.e. fully loaded into the browser)

jQuery(document).ready( function () {
    var oTable = jQuery('table.rounds').dataTable(
        {
            "bPaginate": true,
            "bJQueryUI": true
        }
    );
} );
