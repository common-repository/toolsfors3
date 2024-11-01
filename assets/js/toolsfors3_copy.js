/**
 * @ Author: Bill Minozzi
 * @ Copyright: 2020 www.BillMinozzi.com
 * @ Modified time: 2022-12-05
 * */
jQuery(document).ready(function ($) {
    // console.log('copy.js');
    $('html, body').scrollTop(0);
    jQuery('#transfer-form').modal({
        backdrop: 'static',
        keyboard: false,
        show: true
    });
    /* Open modal  */
    jQuery('*').on('click', function(event) {
        target = jQuery(event.target);
        buttonclicked = target.closest("button").attr('id');
        // console.log(buttonclicked);
        if (buttonclicked === 'valid_open_transfer') {
            if (event.isDefaultPrevented()) return;
            event.preventDefault();
            // console.log(buttonclicked);
            // console.log('L 18');
            var toolsfors3_radioValue = jQuery("input[name='toolsfors3_server_cloud']:checked").val();
            var toolsfors3_server_folder = jQuery("#toolsfors3_selected").text();
            var toolsfors3_cloud_folder = jQuery("#toolsfors3_selected_cloud").text();
            var toolsfors3_bucket_name = jQuery("#select_bucket").val();
            var toolsfors3_test = toolsfors3_server_folder.substring(0, 7);
            // console.log(toolsfors3_test);
            if (toolsfors3_test === 'Please,') {
               jQuery("#toolsfors3_selected").css('color', 'red');
               alert('Please, choose one folder on server!');
                return false;
            }
            var toolsfors3_test = toolsfors3_cloud_folder.substring(0, 7);
            if (toolsfors3_test === 'Please,') {
                jQuery("#toolsfors3_selected_cloud").css('color', 'red');
                alert('Please, choose one folder on cloud (after choose a Bucket).');
                return false;
            }
            //console.log(buttonclicked);
					document.getElementById("toolsfors3_tansferring").innerHTML = '';
					document.getElementById("toolsfors3_log").innerHTML = '';
					jQuery(".toolsfors3_log").hide();
					jQuery("#toolsfors3_log").hide();
                    jQuery("#tranfer_form").show();
                    jQuery("#open_transfer").click();
            var toolsfors3_radioValue = jQuery("input[name='toolsfors3_server_cloud']:checked").val();
            if (toolsfors3_radioValue == "cloud") {
                document.getElementById("toolsfors3_server_cloud").innerHTML = "Transfer from Cloud to Server";
            } else {
                document.getElementById("toolsfors3_server_cloud").innerHTML = "Transfer from Server to Cloud";
            }
            var toolsfors3_server_folder_modal = jQuery("#toolsfors3_selected").text();
            jQuery("#toolsfors3_server_folder_modal").text(toolsfors3_server_folder_modal);
            var toolsfors3_cloud_folder_modal = jQuery("#toolsfors3_selected_cloud").text();
            jQuery("#toolsfors3_cloud_folder_modal").text(toolsfors3_cloud_folder_modal);
        } // end if clicked open transfer
        if (buttonclicked === 'close_transfer') {


            if (event.isDefaultPrevented()) return;
            event.preventDefault();

            jQuery("#toolsfors3-transfer-spinner").hide();

            jQuery("#close_transfer").css('opacity', '0.4');
            $("#toolsfors3_tansferring_status").hide();

            // alert('Close Button Clicked. Please, wait few seconds to clear temp files.');
            var toolsfors3_alert = jQuery(".alert-container");
            jQuery('#basicToast').css("margin-top", "20px");
            jQuery('.toast-header').css("background", "#1E90FF");
            jQuery('.toast-header').text("INFO");
            jQuery('.toast-body').text("Job Cancelled by User... Please, wait. Cleaning and Reloading page...");
            jQuery('.toast-header').css("color", "white");
            jQuery('#basicToast').slideDown();
            window.setTimeout(function() {
                jQuery('#basicToast').slideUp();
                   jQuery('#wait').show();
                   location.reload(); 
                }, 10000);
            

            
            clearInterval(window.toolsfors3_Interval);
            var toolsfors3_radioValue = jQuery("input[name='toolsfors3_server_cloud']:checked").val();
            var toolsfors3_server_folder = jQuery("#toolsfors3_selected").text();
            var toolsfors3_cloud_folder = jQuery("#toolsfors3_selected_cloud").text();
            var toolsfors3_bucket_name = jQuery("#select_bucket").val();
            var radValue = $(".speed:checked").val();
            var nonce2 = $("#toolsfors3_truncate").text();

            console.log('Job Cancelled by user!');

            jQuery.ajax({
                url: ajaxurl,
                type: "POST",
                data: {
                    'action': 'toolsfors3_ajax_truncate',
                    'server_cloud':toolsfors3_radioValue,
					'folder_server':toolsfors3_server_folder,
					'folder_cloud':toolsfors3_cloud_folder,
					'bucket_name':toolsfors3_bucket_name,
                    'nonce':nonce2
                },
                success: function (data) {
                    parent.location.reload(1);
                },
                error: function (xhr, status, error) {
                    /*
                    console.log('Ajax Error (toolsfors3_ajax_truncate): '+error);
                    console.log('Status: '+status);
                    console.log('Error Status Code: '+xhr.status);
                    */
                },
                timeout: 15000
            });
            jQuery("#tranfer-form").hide();
        }




        if (buttonclicked === 'start_transfer') {

            if (event.isDefaultPrevented()) return;
            event.preventDefault();
            // console.log(buttonclicked);
            jQuery("#toolsfors3-transfer-spinner").show();

            $("#toolsfors3_tansferring_status").text('Cleaning temp files...');

      jQuery.ajax({
        url: ajaxurl,
        type: "POST",
        data: {
            'action': 'toolsfors3_ajax_truncate_inic'
        },
        success: function (data ) {
              console.log('Cleaned temp files...');
              $("#toolsfors3_tansferring_status").text('Beginning...');
        },
        error: function (xhr, status, error) {
            console.log('Ajax Error (toolsfors3_ajax_truncate_inic): '+error);
            console.log('Status: '+status);
            console.log('Error Status Code: '+xhr.status);
        },
        timeout: 5000
        });



            /* Begin to copy */
            var toolsfors3_radioValue = jQuery("input[name='toolsfors3_server_cloud']:checked").val();
            var toolsfors3_server_folder = jQuery("#toolsfors3_selected").text();
            var toolsfors3_cloud_folder = jQuery("#toolsfors3_selected_cloud").text();
            var toolsfors3_bucket_name = jQuery("#select_bucket").val();
            var toolsfors3_test = toolsfors3_server_folder.substring(0, 7);
            var toolsfors3_test = toolsfors3_cloud_folder.substring(0, 7);
            jQuery("#start_transfer").prop('disabled', true);
			document.getElementById("toolsfors3_tansferring").innerHTML = '';
			document.getElementById("toolsfors3_log").innerHTML = '';
			jQuery(".toolsfors3_log").hide();
			jQuery("#toolsfors3_log").hide();
			jQuery("#toolsfors3_status_label").show();
            var toolsfors3_radioValue = jQuery("input[name='toolsfors3_server_cloud']:checked").val();
            var toolsfors3_server_folder = jQuery("#toolsfors3_selected").text();
            var toolsfors3_cloud_folder = jQuery("#toolsfors3_selected_cloud").text();
            var toolsfors3_bucket_name = jQuery("#select_bucket").val();
            var radValue = $(".speed:checked").val();
            // console.log('R.V '+radValue);
           window.$frequency = 40000;
           if (radValue == 'very_slow') {
            window.$frequency = 90000;
           }
           if (radValue == 'slow') {
            window.$frequency = 60000;
           }
           if (radValue == 'normal') {
            window.$frequency = 40000;
           }
           if (radValue == 'fast') {
            window.$frequency = 20000;
           }
           if (radValue == 'very_fast') {
               window.$frequency = 5000;
           }
            toolsfors3_copy_run();
            window.toolsfors3_Interval =  setInterval(toolsfors3_copy_run, $frequency);
        } // end Transfer
    });
    /* end open modal  */
    $(".spinner").addClass("is-active");
    function toolsfors3_copy_run() {
        // console.log('12345');
      var toolsfors3_radioValue = jQuery("input[name='toolsfors3_server_cloud']:checked").val();
      var toolsfors3_server_folder = jQuery("#toolsfors3_selected").text();
      var toolsfors3_cloud_folder = jQuery("#toolsfors3_selected_cloud").text();
      var toolsfors3_bucket_name = jQuery("#select_bucket").val();
      var radValue = $(".speed:checked").val();
      var nonce = $("#toolsfors3_nonce").text();



            jQuery.ajax({
                url: ajaxurl,
                type: "POST",
                data: {
                    'action': 'toolsfors3_ajax_transf_files_to_cloud',
                    'speed': radValue,
                    'server_cloud':toolsfors3_radioValue,
					'folder_server':toolsfors3_server_folder,
					'folder_cloud':toolsfors3_cloud_folder,
					'bucket_name':toolsfors3_bucket_name,
                    'nonce':nonce
                },
                success: function (data) {
                    // console.log(data);
                    // console.log($('#transfer-form').is(':visible'));
                    if ($('#transfer-form').is(':visible')) {
                       if (data == 'End of Job!') {
                            clearInterval(window.toolsfors3_Interval);
                            jQuery("#toolsfors3-transfer-spinner").hide();
                            $("#toolsfors3_tansferring_status").text(data);
                            alert('End of Job!');
                            parent.location.reload(1);
                        } 
                        else{
                                data = data.replace( /(<([^>]+)>)/ig, '');
                                $("#toolsfors3_tansferring_status").text(data);
                                function sleep(milliseconds) {
                                    const date = Date.now();
                                    let currentDate = null;
                                    do {
                                        currentDate = Date.now();
                                    } while (currentDate - date < milliseconds);
                                }
                                sleep(3000);
                                clearInterval(window.toolsfors3_Interval);
                                setInterval(window.toolsfors3_Interval, window.$frequency);
                                toolsfors3_copy_run();
                                // console.log(data);
                        }
                    }
                },
                error: function (xhr, status, error) {


                    //clearInterval(window.toolsfors3_Interval);
                    function sleep(milliseconds) {
                        const date = Date.now();
                        let currentDate = null;
                        do {
                            currentDate = Date.now();
                        } while (currentDate - date < milliseconds);
                    }
                    
                    jQuery('*').on('click', function(event) {
                        target = jQuery(event.target);
                        buttonclicked = target.closest("button").attr('id');
                        //console.log(buttonclicked);
                        //alert(buttonclicked);

                        if(buttonclicked == 'close_transfer')
                        {
                            clearInterval(window.toolsfors3_Interval);
                            sleep(5);
                            return;
                        }
                        else{

                            console.log('Ajax Error (toolsfors3_ajax_transf_files_to_cloud): '+error);
                            console.log('Status: '+status);
                            console.log('Error Status Code: '+xhr.status);

                            sleep(5);
                            clearInterval(window.toolsfors3_Interval);
                            setInterval(window.toolsfors3_Interval, window.$frequency);
                            toolsfors3_copy_run();

                        }



                    });

                    console.log('Ajax Error (toolsfors3_ajax_transf_files_to_cloud): '+error);
                    console.log('Status: '+status);
                    console.log('Error Status Code: '+xhr.status);

                    sleep(5);
                    clearInterval(window.toolsfors3_Interval);
                    setInterval(window.toolsfors3_Interval, window.$frequency);
                    toolsfors3_copy_run();
 

                    
                },
                timeout: 180000
            });
      //  }
    }
});