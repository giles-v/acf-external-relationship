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
		$this->dir = apply_filters('acf/helpers/get_dir', __FILE__);
		
		// do not delete!
		parent::__construct();
		
		// extra
		add_action('wp_ajax_acf/fields/external_relationship/query_items', array($this, 'query_items'));
		add_action('wp_ajax_nopriv_acf/fields/external_relationship/query_items', array($this, 'query_items'));
		add_action('admin_enqueue_scripts', array($this, 'admin_enqueue_scripts'));
		add_action('acf/input/admin_enqueue_scripts', array($this, 'input_admin_enqueue_scripts'));
	}

	function admin_enqueue_scripts() {
		wp_enqueue_script(
			'external_relationship_admin', 
			$this->dir . 'external_relationship_admin.js',
			array(), 
			'1.0.0',
			true
		);
	}

	function input_admin_enqueue_scripts() {
		wp_enqueue_script(
			'external_relationship', 
			$this->dir . 'external_relationship.js',
			array(), 
			'1.0.0',
			true
		);
		wp_enqueue_style(
			'external_relationship', 
			$this->dir . 'external_relationship.css' 
		);
	}

	function get_sql_column(&$db, $query) {
		$result = array();
		$rows = $db->get_results($query, ARRAY_N);
		if (is_array($rows)) {
			foreach($rows as $row) {
				$result[$row[0]] = $row[1];
			}
		}
		return $result;
	}

	function get_field_data_by_key($key) {
		global $wpdb;
		$value = $wpdb->get_var($wpdb->prepare("SELECT meta_value FROM $wpdb->postmeta WHERE meta_key = %s LIMIT 1" , $key) );
		if ($value) {
			return unserialize($value);
		}
		return null;
	}
	
	function query_items()
	{
		// vars
		$options = array(
			's' => '',
			'nonce' => ''
		);

		$options = array_merge($options, $_POST);

		$field = $this->get_field_data_by_key($_POST['field_key']);

		global $wpdb;
		$db = $wpdb;
		if (!empty($field['use_external_db']['status'])) {
			$db = new wpdb(
				$field['use_external_db']['db_credentials']['username'],
				$field['use_external_db']['db_credentials']['password'],
				$field['use_external_db']['db_credentials']['db_name'],
				$field['use_external_db']['db_credentials']['host']
			);
		}

		// validate
		if( !wp_verify_nonce($options['nonce'], 'acf_nonce') ) {
			die(0);
		}

		// search
		$sql_query = $field['all_items_query'];
		if( $options['s'] )
		{
			$sql_query = str_ireplace('{QUERY}', $options['s'], $field['search_items_query']);
		}
		
		unset( $options['s'] );

		$results = $this->get_sql_column($db, $sql_query);
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
	*/
	function create_field($field)
	{
		global $wpdb;
		// vars
		$defaults = array(
			'use_external_db'    => array(
				'status' => 0,
				'db_credentials' => array(
					'host'       => '',
					'db_name'    => '',
					'user'       => '',
					'password'   => '',
				),
			),
			'all_items_query'    =>	'',
			'search_items_query' =>	'',
			'single_item_query'  => '',
			'max'                =>	-1,
		);
		
		$field = array_merge($defaults, $field);

		global $wpdb;
		$db = $wpdb;
		if (!empty($field['use_external_db']['status'])) {
			$db = new wpdb(
				$field['use_external_db']['db_credentials']['username'],
				$field['use_external_db']['db_credentials']['password'],
				$field['use_external_db']['db_credentials']['db_name'],
				$field['use_external_db']['db_credentials']['host']
			);
		}
		
		// validate types
		$field['max'] = (int) $field['max'];
		
		// row limit <= 0?
		if( $field['max'] <= 0 )
		{
			$field['max'] = 9999;
		}
		
		?>
<div class="acf_external_relationship" data-max="<?php echo $field['max']; ?>" data-s="">
	
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
				$title = $db->get_var($title_query);
				
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

	function create_options($field)
	{
		// vars
		$defaults = array(
			'use_external_db'    => array(
				'status' => 0,
				'db_credentials' => array(
					'host'       => '',
					'db_name'    => '',
					'user'       => '',
					'password'   => '',
				),
			),
			'all_items_query'    =>	'SELECT id, title FROM products ORDER BY title',
			'search_items_query' =>	'SELECT id, title FROM products WHERE title LIKE \'%{QUERY}%\' ORDER BY title',
			'single_item_query'  => 'SELECT title FROM products WHERE id = \'{ITEM-ID}\'',
			'max'                =>	'',
		);
		
		$field = array_merge($defaults, $field);
		$key = $field['name'];
		
		?>
		<tr class="external-db" data-field_name="<?php echo $field['key']; ?>">
			<td class="label">
				<label><?php _e("Use External Database?",'acf'); ?></label>
			</td>
			<td>
				<?php 
				do_action('acf/create_field', array(
					'type'	=>	'radio',
					'name'	=>	'fields['.$field['key'].'][use_external_db][status]',
					'value'	=>	$field['use_external_db']['status'],
					'choices'	=>	array(
						1	=>	__("Yes",'acf'),
						0	=>	__("No",'acf'),
					),
					'layout'	=>	'horizontal',
				));
				?>
				<div class="er-dbcreds-wrapper" <?php if( ! $field['use_external_db']['status'] ) echo 'style="display:none"'; ?>>
					<table class="er-dbcreds widefat">
						<tbody>
							<tr>
								<td width="25%">
									<label><?php _e("DB Host",'acf'); ?></label>
									<?php 
									do_action('acf/create_field', array(
										'type'	=>	'text',
										'name'	=>	'fields['.$field['key'].'][use_external_db][db_credentials][host]',
										'value'	=>	$field['use_external_db']['db_credentials']['host'],
									));
									?>
								</td>
								<td width="25%">
									<label><?php _e("DB Name",'acf'); ?></label>
									<?php 
									do_action('acf/create_field', array(
										'type'	=>	'text',
										'name'	=>	'fields['.$field['key'].'][use_external_db][db_credentials][db_name]',
										'value'	=>	$field['use_external_db']['db_credentials']['db_name'],
									));
									?>
								</td>
								<td width="25%">
									<label><?php _e("Username",'acf'); ?></label>
									<?php 
									do_action('acf/create_field', array(
										'type'	=>	'text',
										'name'	=>	'fields['.$field['key'].'][use_external_db][db_credentials][username]',
										'value'	=>	$field['use_external_db']['db_credentials']['username'],
									));
									?>
								</td>
								<td width="25%">
									<label><?php _e("Password",'acf'); ?></label>
									<?php 
									do_action('acf/create_field', array(
										'type'	=>	'text',
										'name'	=>	'fields['.$field['key'].'][use_external_db][db_credentials][password]',
										'value'	=>	$field['use_external_db']['db_credentials']['password'],
									));
									?>
								</td>
							</tr>
						</tbody>
					</table>
				</div>
			</td>
		</tr>
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
}

new acf_field_external_relationship();
