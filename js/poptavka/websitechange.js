$(function(){

	$('#change-form')
	   .jqTransform()
	   .validate({
         submitHandler: function(form) {
           $(form).ajaxSubmit({
                success: function() {
                    $('#change-form').hide();
                    $('#page-wrap').append("<p class='thanks'>Děkujeme! Vaše poptávka byla v pořádku odeslána.</p>")
                    window.location.replace("https://www.dynal.cz/odeslano.php");
                }
           });
         }
        }); 
        
    $("#addURLSArea").hide();
        

    $('.jqTransformCheckbox').click(function(){
        if ($('#multCheck:checked').val() == 'on') {
            $("#addURLSArea").slideDown();
        } else {
            $("#addURLSArea").slideUp();
        }
    });  
    
    $(".jqTransformRadio").click(function() {
        if ($(".jqTransformRadio").eq(1).attr("class") == "jqTransformRadio jqTransformChecked") {
            $("#curTextArea").slideUp();
        } else {
            $("#curTextArea").slideDown();
        }
    });
	
});