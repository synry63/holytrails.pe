jQuery( document ).ready(function() {

    // blink effet
        setInterval(function(){
            jQuery('.fa-chevron-down').delay(200).fadeTo('slow',0).delay(50).fadeTo('slow',1);

        },1000);


        // hover on menu
        if( /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent) ) {
            jQuery('ul.ddl-nav li.menu-item-has-children').addClass('open');
        }
        else{
            jQuery('ul.ddl-nav li.menu-item-has-children').on('mouseenter', function(){
                jQuery(this).stop();
                jQuery(this).find('ul.ddl-dropdown-menu').slideToggle(0);
            });
            jQuery('ul.ddl-nav li.menu-item-has-children').on('mouseleave', function(){
                jQuery(this).stop();
                jQuery(this).find('ul.ddl-dropdown-menu').slideToggle(0);
            });
        }

        // quick form
        var is_open = false;
        jQuery('.quick-form-wrap .wpcf7-response-output').remove();
        jQuery('.bottom-contact-us').click(function(){
            if(!is_open){
                jQuery('#scrollUp').hide();
                is_open = true;
            }
            else{

                is_open = false;
                if(jQuery( document ).scrollTop()>500){
                    jQuery('#scrollUp').show();
                }
            }

            jQuery('.quick-form-wrap').slideToggle(200,function(){
                if(jQuery(this).is(":hidden")) {


                }
                else{


                }
            });

            return false;
        });
        /*var is_safari = navigator.userAgent.indexOf("Safari") > -1;
        if(is_safari){
            setTimeout(function(){
                jQuery("div.twoxtwo").css('background','none');
            },1000);
        }*/


});