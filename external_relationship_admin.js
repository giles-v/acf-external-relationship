/*
*  External Relationship
*
*  @description: 
*  @since: 3.5.8
*  @created: 17/01/13
*/



(function($){

	$('tr.external-db input[type="radio"]').live('change', function(){
		
		if( $(this).val() == "1" )
		{
			$(this).closest('tr.external-db').find('.er-dbcreds-wrapper').show();
		}
		else
		{
			$(this).closest('tr.external-db').find('.er-dbcreds-wrapper').hide();
		}
		
	});

})(jQuery);