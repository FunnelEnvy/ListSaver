jQuery(document).ready(function($){
	$(document).on( 'submit', 'form[action*="'+wtm.form_action_url+'"]', function(e) {
    
    form = $(this);

    if( form.attr('data-submit') == 1)
    
    return;
    
    e.preventDefault();    
    
    form.removeAttr('target');
    
    var email, fname, lname;

    email = form.find('input[name*="email"]').val() || form.find('input[name*="EMAIL"]').val() ; 
    
    fname = form.find('input[name*="fname"]').val() || form.find('input[name*="FNAME"]').val() ; 
    
    lname = form.find('input[name*="lname"]').val() || form.find('input[name*="LNAME"]').val() ; 
    
    var form_data = {
       
       action : 'list_saver_ajax_mailchimp_subscribe',
       
       sub_email :  email,
       
       first_name:  fname,
       
       last_name:  lname,
     }
    
     $.ajax({
    
      url: wtm.ajaxUrl,
   
      data: form_data,

      type: 'POST',

      dataType : 'json',

      success : function(resp){
       
         form.attr('data-submit', 1);
        
         form.submit();    
              
       }

      


     })
     
     

  })
  
  

})
