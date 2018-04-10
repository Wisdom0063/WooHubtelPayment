jQuery(document).on("ready", function() {
    if (jQuery("#woocommerce_wc_hubtelpayment_notify").is(":checked")) {
        jQuery("#woocommerce_mpowerpayment_sms_email_addresses").prop("visibility", "visible");
    } else {
        jQuery("#woocommerce_mpowerpayment_sms_email_addresses").prop("visibility", "hidden");
    }
    jQuery("#woocommerce_wc_hubtelpayment_notify").on("click", function() {
        if (jQuery(this).is(":checked")) {
            jQuery("#woocommerce_mpowerpayment_sms_email_addresses").prop("visibility", "visible");
        } else {
            jQuery("#woocommerce_mpowerpayment_sms_email_addresses").prop("visibility", "hidden");
        }
    });

    if(jQuery("#hubtel-buynow").length > 0) {
        jQuery("#woocommerce_wc_hubtelpayment_enabled").attr("disabled", "disabled");
    }

    jQuery(".delhubtelbutton").on("click", function(e){
        var url = jQuery(this).data("url");
        if(confirm("You are about to delete this payment button. Are you sure you want to proceed.")){
            jQuery.get(url, function(data){
                if(data == "1"){
                    location.reload();
                }
            });
        }
    });
    jQuery("input[type=text].readonly").prop("readonly", true);
    jQuery(".why-donate").on("click", function(){
        alert(`Create dynamic payment and donation buttons 
Be notified via email whenever payment is received 
View payment logs`);
    });

    var modal = document.getElementById('new-button-modal');
    jQuery("#newhubtelbutton").on("click", function(e){
        e.preventDefault();
        jQuery(modal).css("display", "block");
    });
    jQuery("#new-button-modal .close").on("click", function(e){
        e.preventDefault();
        jQuery("#new-button-modal").css("display", "none");
    });

    window.onclick = function(event) {
        if (event.target == modal) {
            modal.style.display = "none";
        }
    }

});