jQuery(document).ready( function () {
    var oTable = jQuery('table.rounds').dataTable(
        {
            // "iDisplayLength": 5,
            // "aLengthMenu": [[1, 5, 10, 25, -1], [1, 5, 10, 25, "All"]],
            // "sScrollX": "100%",
            "sScrollY": "200px",
            "bScrollCollapse": true,
            "sDom": 'lfrtp',
            "bPaginate": false,
            "bSort": false
        }
    );
    // new FixedColumns(oTable);
} );
