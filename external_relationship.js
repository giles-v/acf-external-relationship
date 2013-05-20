/*
*  External Relationship
*
*  @description: 
*  @since: 3.5.8
*  @created: 17/01/13
*/



(function($){

	acf.fields.external_relationship = {
		timeout : null,
		update_results : function(){},
		hide_results : function(){},
		text : {
			max : "Maximum values reached ( {max} values )"
		}
	}
	
	var _external_relationship = acf.fields.external_relationship;
	
		
	/*
	*  acf/setup_fields
	*
	*  @description: 
	*  @since: 3.5.8
	*  @created: 17/01/13
	*/
	
	$(document).live('acf/setup_fields', function(e, postbox){
		
		$(postbox).find('.acf_external_relationship').each(function(){
			
			// is clone field?
			if( acf.helpers.is_clone_field($(this).children('input[type="hidden"]')) )
			{
				return;
			}
			
			
			$(this).find('.relationship_right .relationship_list').sortable({
				axis: "y", // limit the dragging to up/down only
				items: '> li',
				forceHelperSize: true,
				forcePlaceholderSize: true,
				scroll: true
			});
			
			
			// load more
			$(this).find('.relationship_left .relationship_list').scrollTop(0).scroll( function(){
				
				// vars
				var div = $(this).closest('.acf_external_relationship');
				
				
				// validate
				if( div.hasClass('loading') )
				{
					return;
				}
				
				
				// Scrolled to bottom
				if( $(this).scrollTop() + $(this).innerHeight() >= $(this).get(0).scrollHeight )
				{
					var paged = parseInt( div.attr('data-paged') );
					
					div.attr('data-paged', (paged + 1) );
					
					_external_relationship.update_results( div );
				}

			});
			
			
			// ajax fetch values for left side
			_external_relationship.update_results( $(this) );
			
		});
		
	});
	
	
	/*
	*  Button Add
	*
	*  @description: 
	*  @since: 3.5.8
	*  @created: 17/01/13
	*/
	
	$('.acf_external_relationship .relationship_left .relationship_list a').live('click', function(){
		
		// vars
		var id = $(this).attr('data-item_id'),
			title = $(this).html(),
			div = $(this).closest('.acf_external_relationship'),
			max = parseInt(div.attr('data-max')),
			right = div.find('.relationship_right .relationship_list');
		
		
		// max posts
		if( right.find('a').length >= max )
		{
			alert( _external_relationship.text.max.replace('{max}', max) );
			return false;
		}
		
		
		// can be added?
		if( $(this).parent().hasClass('hide') )
		{
			return false;
		}
		
		
		// hide / show
		$(this).parent().addClass('hide');
		
		
		// create new li for right side
		var new_li = div.children('.tmpl-li').html()
			.replace( /\{item_id}/gi, id )
			.replace( /\{title}/gi, title );
			


		// add new li
		right.append( new_li );
		
		
		// validation
		div.closest('.field').removeClass('error');
		
		return false;
		
	});
	
	
	/*
	*  Button Remove
	*
	*  @description: 
	*  @since: 3.5.8
	*  @created: 17/01/13
	*/
	
	$('.acf_external_relationship .relationship_right .relationship_list a').live('click', function(){
		
		// vars
		var id = $(this).attr('data-item_id'),
			div = $(this).closest('.acf_external_relationship'),
			left = div.find('.relationship_left .relationship_list');
		
		
		// hide
		$(this).parent().remove();
		
		
		// show
		left.find('a[data-item_id="' + id + '"]').parent('li').removeClass('hide');
		
		
		return false;
		
	});
	
	
	/*
	*  Search on keyup
	*
	*  @description: 
	*  @since: 3.5.8
	*  @created: 17/01/13
	*/
	
	$('.acf_external_relationship input.relationship_search').live('keyup', function()
	{	
		// vars
		var val = $(this).val(),
			div = $(this).closest('.acf_external_relationship');
			
		
		// update data-s
	    div.attr('data-s', val);
	    
	    
	    // new search, reset paged
	    div.attr('data-paged', 1);
	    
	    
	    // ajax
	    clearTimeout( _external_relationship.timeout );
	    _external_relationship.timeout = setTimeout(function(){
	    	_external_relationship.update_results( div );
	    }, 250);
	    
	    return false;
	    
	})
	.live('focus', function(){
		$(this).siblings('label').hide();
	})
	.live('blur', function(){
		if($(this).val() == "")
		{
			$(this).siblings('label').show();
		}
	});
	
	
	// hide results
	_external_relationship.hide_results = function( div ){
		
		// vars
		var left = div.find('.relationship_left .relationship_list'),
			right = div.find('.relationship_right .relationship_list');
			
			
		// apply .hide to left li's
		left.find('a').each(function(){
			
			var id = $(this).attr('data-item_id');
			
			if( right.find('a[data-item_id="' + id + '"]').exists() )
			{
				$(this).parent().addClass('hide');
			}
			
		});
		
	}
	
	
	// update results
	_external_relationship.update_results = function( div ){
		
		
		// add loading class, stops scroll loading
		div.addClass('loading');
		
		
		// vars
		var attributes = {},
			left = div.find('.relationship_left .relationship_list'),
			right = div.find('.relationship_right .relationship_list'); 
		
		
		// find attributes
        $.each( div[0].attributes, function( index, attr ) {
        	
        	// must have 'data-'
        	if( attr.name.substr(0, 5) != 'data-' )
        	{
	        	return;
        	}
        	
        	
        	// ignore
        	if( attr.name == 'data-max' )
        	{
	        	return;
        	}
        	
        	
        	// add to attributes
            attributes[ attr.name.replace('data-', '') ] = attr.value;
        }); 
        
		
		// get results
	    $.ajax({
			url: ajaxurl,
			type: 'post',
			dataType: 'html',
			data: $.extend( attributes, { 
				action : 'acf/fields/external_relationship/query_items',
				field_name : div.parent().attr('data-field_name'),
				field_key : div.parent().attr('data-field_key'),
				nonce : acf.nonce
			}),
			success: function( html ){
				
				div.removeClass('no-results').removeClass('loading');
				
				// new search?
				if( attributes.paged == 1 )
				{
					left.find('li:not(.load-more)').remove();
				}
				
				
				// no results?
				if( !html )
				{
					div.addClass('no-results');
					return;
				}
				
				
				// append new results
				left.find('.load-more').before( html );
				
				
				// less than 10 results?
				var ul = $('<ul>' + html + '</ul>');
				if( ul.find('li').length < 10 )
				{
					div.addClass('no-results');
				}
				
				
				// hide values
				_external_relationship.hide_results( div );
				
			}
		});
	};
	

})(jQuery);