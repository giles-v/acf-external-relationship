<?php

/*--------------------------------------------------------------------------------------
*
*	External Relationship
*	A field for ACFv4, allowing you to link to an external resource in a database.
*	An example use case might be embedding a list of products into a WordPress page.
*
*	@author Giles Copp
*	@copyright Version Industries 2013
*
*	Licenced under the MIT Licence.
* 
*-------------------------------------------------------------------------------------*/


class acf_field_external_relationship extends acf_field
{
	function __construct()
	{
		// vars
		$this->name = 'external_relationship';
		$this->label = __("External Relationship",'acf');
		$this->category = __("Relational",'acf');
		
		// do not delete!
		parent::__construct();
		
		
		// extra
		add_action('wp_ajax_acf/fields/external_relationship/query_items', array($this, 'query_items'));
		add_action('wp_ajax_nopriv_acf/fields/external_relationship/query_items', array($this, 'query_items'));

		if (is_admin()) {
			wp_enqueue_script(
				'external_relationship', 
				plugins_url('external_relationship.js', __FILE__), 
				array(), 
				'1.0.0',
				true
			);
			wp_enqueue_style(
				'external_relationship', 
				plugins_url('external_relationship.css', __FILE__) 
			);
		}
	}

	function get_sql_column($query) {
		$result = array();
		global $wpdb;
		$rows = $wpdb->get_results($query, ARRAY_N);
		if (is_array($rows)) {
			foreach($rows as $row) {
				$result[$row[0]] = $row[1];
			}
		}
		return $result;
	}
	
	function query_items()
	{
		// vars
		$options = array(
			'all_items_query' => '',
			'search_items_query' => '',
			'single_item_query' => '',
			'sql_query' => '',
			's' => '',
			'nonce' => ''
		);

		$options = array_merge($options, $_POST);
		
		// validate
		if( !wp_verify_nonce($options['nonce'], 'acf_nonce') ) {
			die(0);
		}

		// search
		$sql_query = $options['all_items_query'];
		if( $options['s'] )
		{
			$sql_query = str_ireplace('{QUERY}', $options['s'], $options['search_items_query']);
		}
		
		unset( $options['s'] );

		$results = $this->get_sql_column($sql_query);
		$html = '';

		if (empty($results)) {
			$html = '
			<li class="no-results">
				<span>No items found ('.$sql_query.')</span>
			</li>';
		}

		foreach( $results as $item_id => $title )
		{
			$html .= '
			<li>
				<a href="#" data-item_id="' . $item_id . '">
					'.$title.'
					<span class="acf-button-add"></span>
				</a>
			</li>';
		}
		
		echo $html;
		exit();
		
	}


	/*
	*  create_field()
	*
	*  Create the HTML interface for your field
	*
	*  @param	$field - an array holding all the field's data
	*
	*  @type	action
	*  @since	3.6
	*  @date	23/01/13
	*/
	function create_field($field)
	{
		global $wpdb;
		// vars
		$defaults = array(
			'all_items_query'    =>	'',
			'search_items_query' =>	'',
			'single_item_query'  => '',
			'max'                =>	-1,
		);
		
		$field = array_merge($defaults, $field);
		
		// validate types
		$field['max'] = (int) $field['max'];
		
		// row limit <= 0?
		if( $field['max'] <= 0 )
		{
			$field['max'] = 9999;
		}
		
		?>
<div class="acf_external_relationship" data-max="<?php echo $field['max']; ?>" data-s="" data-all_items_query="<?php echo $field['all_items_query']; ?>" data-search_items_query="<?php echo $field['search_items_query']; ?>">
	
	<!-- Hidden Blank default value -->
	<input type="hidden" name="<?php echo $field['name']; ?>" value="" />
	
	<!-- Template for value -->
	<script type="text/html" class="tmpl-li">
	<li>
		<a href="#" data-value="{item_id}">{title}<span class="acf-button-remove"></span></a>
		<input type="hidden" name="<?php echo $field['name']; ?>[]" value="{item_id}" />
	</li>
	</script>
	<!-- / Template for value -->
	
	<!-- Left List -->
	<div class="relationship_left">
		<table class="widefat">
			<thead>
				<tr>
					<th>
						<label class="relationship_label" for="relationship_<?php echo $field['name']; ?>"><?php _e("Search",'acf'); ?>...</label>
						<input class="relationship_search" type="text" id="relationship_<?php echo $field['name']; ?>" />
						<div class="clear_relationship_search"></div>
					</th>
				</tr>
			</thead>
		</table>
		<ul class="bl relationship_list">
			<li class="load-more">
				<div class="acf-loading"></div>
			</li>
		</ul>
	</div>
	<!-- /Left List -->
	
	<!-- Right List -->
	<div class="relationship_right">
		<ul class="bl relationship_list">
		<?php

		if( $field['value'] )
		{
			foreach( $field['value'] as $id )
			{
				$title_query = str_ireplace('{ITEM-ID}', $id, $field['single_item_query']);
				$title = $wpdb->get_var($title_query);
				
				echo '
				<li>
					<a href="#" class="" data-item_id="' . $id . '">
						'.$title.'
						<span class="acf-button-remove"></span>
					</a>
					<input type="hidden" name="' . $field['name'] . '[]" value="' . $id . '" />
				</li>';	
			}
		}
			
		?>
		</ul>
	</div>
	<!-- / Right List -->
	
</div>
		<?php

	
	}
	
	
	/*--------------------------------------------------------------------------------------
	*
	*	create_options
	*
	*	@author Elliot Condon
	*	@since 2.0.6
	*	@updated 2.2.0
	* 
	*-------------------------------------------------------------------------------------*/
	
	function create_options($field)
	{
		// vars
		$defaults = array(
			'all_items_query'    =>	'SELECT id, title FROM products ORDER BY title',
			'search_items_query' =>	'SELECT id, title FROM products WHERE title LIKE \'%{QUERY}%\' ORDER BY title',
			'single_item_query'  => 'SELECT title FROM products WHERE id = \'{ITEM-ID}\'',
			'max'                =>	'',
		);
		
		$field = array_merge($defaults, $field);
		$key = $field['name'];
		
		?>
		<tr class="field_option field_option_<?php echo $this->name; ?>">
			<td class="label">
				<label><?php _e("Get All Items SQL Query",'acf'); ?></label>
				<p class="description">This query must select two columns, a VALUE (id) and a LABEL (title). It doesn't matter what they're called.</p>
			</td>
			<td>
				<?php 
				do_action('acf/create_field', array(
					'type'	=>	'text',
					'name'	=>	'fields['.$key.'][all_items_query]',
					'value'	=>	$field['all_items_query'],
				));
				?>
			</td>
		</tr>
		<tr class="field_option field_option_<?php echo $this->name; ?>">
			<td class="label">
				<label><?php _e("Search For Items SQL Query",'acf'); ?></label>
				<p class="description">Must select the same fields as "Get All Items", and include {QUERY}
					which will be replaced by the search-string.</p>
			</td>
			<td>
				<?php 
				do_action('acf/create_field', array(
					'type'	=>	'text',
					'name'	=>	'fields['.$key.'][search_items_query]',
					'value'	=>	$field['search_items_query'],
				));
				?>
			</td>
		</tr>
		<tr class="field_option field_option_<?php echo $this->name; ?>">
			<td class="label">
				<label><?php _e("Get Title From Value SQL Query",'acf'); ?></label>
				<p class="description">Uses {ITEM-ID} value to retrieve an item label.</p>
			</td>
			<td>
				<?php 
				do_action('acf/create_field', array(
					'type'	=>	'text',
					'name'	=>	'fields['.$key.'][single_item_query]',
					'value'	=>	$field['single_item_query'],
				));
				?>
			</td>
		</tr>
		<tr class="field_option field_option_<?php echo $this->name; ?>">
			<td class="label">
				<label><?php _e("Maximum Items",'acf'); ?></label>
			</td>
			<td>
				<?php 
				do_action('acf/create_field', array(
					'type'	=>	'text',
					'name'	=>	'fields['.$key.'][max]',
					'value'	=>	$field['max'],
				));
				?>
			</td>
		</tr>
		<?php
	}
	
	
	/*--------------------------------------------------------------------------------------
	*
	*	get_value
	*
	*	@author Elliot Condon
	*	@since 3.3.3
	* 
	*-------------------------------------------------------------------------------------*/
	
	function get_value($post_id, $field)
	{
		// get value
		$value = parent::get_value($post_id, $field);
		
		// format value
		
		// return value
		return $value;	
	}
	
}

new acf_field_external_relationship();
