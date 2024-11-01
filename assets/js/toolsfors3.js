jQuery(document).ready(function(jQuery){
    Dropzone.autoDiscover = false;
    jQuery('#loading').hide();
    jQuery('#table').show();
    var options = {
            valueNames: ['filename', 'size', 'last_modified'],
            page: 20,
            pagination: true
        };
    var tableList = new List('browser-table', options);
    jQuery('*').click(function(event) {
             var clsname = event.target.className;
             var idname = event.target.id;
             if(idname === 'startUpload')
             {
                myDropzone.processQueue(); 
             }
    });
}); 
//
jQuery(document).ready(function(jQuery){




let nonce = '';
try {
  nonce = jQuery('#toolsfors3_nonce').val();
} catch (error) {

  nonce = '';
}

// console.log('Nonce value:', nonce);






            jQuery('#loading').hide();
            const toolsfors3_prefix =  jQuery("#toolsfors3_prefix").val();
            const toolsfors3_bucket =  jQuery("#toolsfors3_bucket").val();
    jQuery('#table').show();
    jQuery('#toolsfors3_pagination').show();
    jQuery('*').click(function(event) {
        var clsname = event.target.className;
    if(clsname === 'wait_download300'){
            event.stopPropagation();
            event.preventDefault();
        var href = event.target.href;
       var file_size = event.target.id;
       if(file_size > 1100000000){
            // alert("Max file size is 100MB on this version");
            console.log("Max file size is 1000GB on this first version");
            var alert = jQuery(".alert-container");
            jQuery('#basicToast').css("margin-top", "20px");
            jQuery('.toast-header').css("background", "orange");
            jQuery('.toast-header').text("INFO");
            jQuery('.toast-body').text("Max file size is 1000GB on this First version.");
            jQuery('.toast-header').css("color", "white");
            jQuery('#basicToast').slideDown();
            window.setTimeout(function() {
            jQuery('#basicToast').slideUp();
            }, 3000);
            return false;
       }
         var alert = jQuery(".alert-container");
                    jQuery('#basicToast').css("margin-top", "20px");
                    jQuery('.toast-header').css("background", "blue");
                    jQuery('.toast-header').text("INFO");
                    jQuery('.toast-body').text("Plase Wait...Getting file(s) from Cloud.");
                    jQuery('.toast-header').css("color", "white");
                    jQuery('#basicToast').slideDown();
                    window.setTimeout(function() {
                    jQuery('#basicToast').slideUp();
                    window.location.href = href; // redirect with stored href
                    }, 3000);
        }
    }); // end wait download...
    //////////////////////////////////////
    jQuery('#add_folder').click(function(event) {
        event.preventDefault();
        jQuery('#creating').show();
   var newfolder = jQuery('#new-folder-input').val();
   //console.log('antes: '+newfolder);
   jQuery("#new-folder").modal('hide');
   jQuery.ajax({
        url: ajaxurl,
        type: "POST",
        data: {
            'action':'toolsfors3_ajax_create_handle',
            'folder-name':newfolder,
            'prefix':toolsfors3_prefix,
            'bucket':toolsfors3_bucket,
            'toolsfors3_nonce': nonce
        },
        success:function(data) {
            if(data === 'created'){
                    //console.log('folder created');
                    jQuery('#creating').hide();
                    var alert = jQuery(".alert-container");
                    jQuery('#basicToast').css("margin-top", "20px");
                    jQuery('.toast-header').css("background", "green");
                    jQuery('.toast-header').text("INFO");
                    jQuery('.toast-body').text("Created Successful... reloading page...");
                    jQuery('.toast-header').css("color", "white");
                    jQuery('#basicToast').slideDown();
                    window.setTimeout(function() {
                    jQuery('#basicToast').slideUp();
                       jQuery('#wait').show();
                       location.reload(); 
                    }, 3000);
            }
            else if (data === 'folder_exist') {
                // fail
                jQuery('#creating').hide();
                const wdata = data.replace(/(<([^>]+)>)/gi, "");
                console.log(wdata);
                var alert = jQuery(".alert-container");
                jQuery('#basicToast').css("margin-top", "20px");
                jQuery('.toast-header').css("background", "red");
                jQuery('.toast-header').text("Error");
                jQuery('.toast-body').text("Fail to Create Folder. Folder Exist.");
                jQuery('.toast-header').css("color", "white");
                jQuery('#basicToast').slideDown();
                window.setTimeout(function() {
                jQuery('#basicToast').slideUp();
                }, 3000);
            }
            else{
                // fail
                jQuery('#creating').hide();
                const wdata = data.replace(/(<([^>]+)>)/gi, "");
                console.log(wdata);
                var alert = jQuery(".alert-container");
                jQuery('#basicToast').css("margin-top", "20px");
                jQuery('.toast-header').css("background", "red");
                jQuery('.toast-header').text("Error");
                jQuery('.toast-body').text("Fail to Create Folder. Maybe file permissions or wrong name.");
                jQuery('.toast-header').css("color", "white");
                jQuery('#basicToast').slideDown();
                window.setTimeout(function() {
                jQuery('#basicToast').slideUp();
                }, 3000);
            }
        },
          error: function(data, textStatus, jqXHR){
          console.log(textStatus);
          console.log(jqXHR);
          const wdata = data.replace(/(<([^>]+)>)/gi, "");
          console.log(wdata);
         jQuery('#wait').hide();
                           // fail
        jQuery('#wait').hide();
        var alert = jQuery(".alert-container");
        jQuery('#basicToast').css("margin-top", "20px");
        jQuery('.toast-header').css("background", "red");
        jQuery('.toast-header').text("Error");
        jQuery('.toast-body').text("Fail to Create Folder. Check your console.");
        jQuery('.toast-header').css("color", "white");
        jQuery('#basicToast').slideDown();
        window.setTimeout(function() {
        jQuery('#basicToast').slideUp();
        }, 3000);
        }
    }); 
    }); 
jQuery('#delete-button').click(function(event) {
        var myCheckboxes = new Array();
        jQuery(':checkbox').each(function() {
          // myCheckboxes.push(jQuery(this).val());
        });
       // var data = { 'services[]' : []};
        jQuery("input:checked").each(function() {
            myCheckboxes.push(jQuery(this).val());
        });
        if (myCheckboxes.length === 0) {
            alert('No files selected to delete.');
            return false;
        }
        //console.log(myCheckboxes);
        var answer = confirm("Are you sure you want to delete? You can't undo this action.");
    if (!answer) {
        return false;
    }
    event.preventDefault();
    //console.log(ajaxurl);
    jQuery('#deleting').show();
    jQuery.ajax({
                url: ajaxurl,
                type: "POST",
                data: {
                    'action':'toolsfors3_ajax_delete_handle',
                    'delete-list':myCheckboxes, 
                    'bucket':toolsfors3_bucket,
                    'toolsfors3_nonce': nonce
                },
                success:function(data) {
                    const wdata = data.replace(/(<([^>]+)>)/gi, "");
                    //console.log('retorno: '+wdata);
                    if(wdata === 'delete_ok'){
                       // console.log('deletou');
                        jQuery('#deleting').hide();
                        var alert = jQuery(".alert-container");
                        jQuery('#basicToast').css("margin-top", "20px");
                        jQuery('.toast-header').css("background", "green");
                        jQuery('.toast-header').text("INFO");
                        jQuery('.toast-body').text("Delete Successful... reloading page...");
                        jQuery('.toast-header').css("color", "white");
                        jQuery('#basicToast').slideDown();
                        window.setTimeout(function() {
                        jQuery('#basicToast').slideUp();
                        jQuery("input:checked").each(function() {
                            jQuery(this).prop('checked', false);
                        });
                        jQuery('#wait').show();
                        location.reload(); 
                        }, 3000);
                    }
                    else{
                        // fail
                        jQuery('#deleting').hide();
                        console.log(wdata);
                        var alert = jQuery(".alert-container");
                        jQuery('#basicToast').css("margin-top", "20px");
                        jQuery('.toast-header').css("background", "red");
                        jQuery('.toast-header').text("Error");
                        if(wdata === 'not_empty_folder'){
                            jQuery('.toast-body').text("Fail to Delete. Maybe not empty folder.");
                        }
                        else{
                           jQuery('.toast-body').text("Fail to Delete. Maybe file permissions."); 
                         }
                        jQuery('.toast-header').css("color", "white");
                        jQuery('#basicToast').slideDown();
                        window.setTimeout(function() {
                        jQuery('#basicToast').slideUp();
                        }, 3000);
                    }
                },
                error: function(data, textStatus, jqXHR){
                    console.log(textStatus);
                    console.log(jqXHR);
                    jQuery('#deleting').hide();
                        // fail
                        var alert = jQuery(".alert-container");
                        jQuery('#basicToast').css("margin-top", "20px");
                        jQuery('.toast-header').css("background", "red");
                        jQuery('.toast-header').text("Error");
                        jQuery('.toast-body').text("Fail to Delete. Check your console.");
                        jQuery('.toast-header').css("color", "white");
                        jQuery('#basicToast').slideDown();
                        window.setTimeout(function() {
                        jQuery('#basicToast').slideUp();
                        }, 3000);
                }
            }); 
        });
}); // end jQuery ready  //
