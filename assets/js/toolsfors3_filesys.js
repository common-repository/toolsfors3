jQuery(document).ready(function() {
// console.log('----- filesys ------------');
    jQuery('*').on('click', function(event) {
        target = jQuery(event.target);
        buttonclicked = target.closest("button").attr('id');
        // console.log(buttonclicked);
        if (buttonclicked === 'toolsfors3_choose_bucket') {
            if (event.isDefaultPrevented()) return;
            event.preventDefault();
            var toolsfors3_bucket_name = jQuery("#select_bucket").val();
            jQuery('.toolsfors3_transfer_row').show();
            var toolsfors3_temp = "<div class='spinner-border' style='width: 34px; height: 34px;'  role='status'><span class='sr-only'></span></div>";
            jQuery('#toolsfors3_treeview2').html(toolsfors3_temp);
            jQuery("#toolsfors3_bucket_selected").text(toolsfors3_bucket_name);
            jQuery("#toolsfors3_bucket_name").text(toolsfors3_bucket_name);
            var onTreeNodeSelected2 = function(e, node) {
                jQuery("#toolsfors3_selected_cloud").text(node["text"]);
                jQuery("#toolsfors3_server_folder_modal").text(node["text"]);
                jQuery('#toolsfors3_treeview2').treeview('collapseAll', {
                    silent: true
                });
            };
            jQuery.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    'action': 'toolsfors3_ajax_create_filesys_cloud',
                    'bucket_name': toolsfors3_bucket_name
                },
                datatype: 'json',
                success: function(data_toolsfors3_filesys) {
                    jQuery('#toolsfors3_treeview2').treeview(
                        {
                            data: data_toolsfors3_filesys,
                            expandIcon: "bi bi-node-plus",
                            collapseIcon: "bi bi-node-minus",
                            emptyIcon: "bi bi-folder",
                            onNodeSelected: onTreeNodeSelected2
                        }
                    )
                    jQuery('#treeview2').treeview('collapseAll', {
                        silent: true
                    });
                },
                error: function(jqXHR, textStatus, errorThrown) {
                    console.log("Ajax Error: " + errorThrown);
                    console.log('request failed');
                }
            });
        }
    });
    var onTreeNodeSelected = function(e, node) {
        jQuery("#toolsfors3_server_folder_label").text("Folder Server: ");
        jQuery("#toolsfors3_selected").text(node["text"]);
        // jQuery("#toolsfors3_selected").text("xxxxx");
        jQuery('#treeview').treeview('collapseAll', {
            silent: true
        });
    };
    jQuery.ajax({
        url: ajaxurl,
        type: 'POST',
        data: {
            'action': 'toolsfors3_ajax_create_filesys'
        },
        datatype: 'json',
        success: function(data_toolsfors3_filesys) {
            //successFunction(data);
            jQuery('#treeview').treeview(
                // { data: data_toolsfors3_filesys,
                {
                    data: data_toolsfors3_filesys,
                    expandIcon: "bi bi-node-plus",
                    collapseIcon: "bi bi-node-minus",
                    emptyIcon: "bi bi-folder",
                    onNodeSelected: onTreeNodeSelected
                }
            )
            jQuery('#treeview').treeview('collapseAll', {
                silent: true
            });
        },
        error: function(jqXHR, textStatus, errorThrown) {
            console.log("Ajax Error: " + errorThrown);
            console.log('request failed');
        }
    });
});