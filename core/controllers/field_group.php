<?php 

/*
*  Field Group
*
*  @description: All the functionality for creating / editing a field group
*  @since 3.2.6
*  @created: 23/06/12
*/

 
class acf_field_group
{

	var $acf,
		$data;
		
	
	/*
	*  __construct
	*
	*  @description: 
	*  @since 3.1.8
	*  @created: 23/06/12
	*/
	
	function __construct($acf)
	{
	
		// vars
		$this->acf = $acf;
		
		
		// actions
		add_action('admin_print_scripts',       array($this,'admin_print_scripts')      );
		add_action('admin_print_styles',        array($this,'admin_print_styles')       );
		add_action('admin_head',                array($this,'admin_head')               );
		add_action('save_post',                 array($this, 'save_post')               );
		
		
		// filters
		add_filter('name_save_pre',             array($this, 'save_name')               );
		
		
		// ajax
		add_action('wp_ajax_acf_field_options', array($this, 'ajax_acf_field_options')  );
		add_action('wp_ajax_acf_location',      array($this, 'ajax_acf_location')       );
		add_action('wp_ajax_acf_next_field_id', array($this, 'ajax_acf_next_field_id')  );
	}
	
	
	/*
	*  validate_page
	*
	*  @description: returns true | false. Used to stop a function from continuing
	*  @since 3.2.6
	*  @created: 23/06/12
	*/
	
//	function validate_page()
//	{
//		// global
//		global $pagenow, $typenow;
//
//
//        phplog('field_group.php','********************************************* INIT' );
//        phplog('field_group.php','$pagenow = ',$pagenow );
//
//
//		// vars
//		$return = false;
//
//
//		// validate page
//		if( in_array( $pagenow, array('post.php', 'post-new.php') ) )
//		{
//
//			// validate post type
//			if( $typenow == "acf" )
//			{
//				$return = true;
//			}
//
//		}
//
//
//		// return
//		return $return;
//	}


	/*
	*  admin_print_scripts
	*
	*  @description:
	*  @since 3.1.8
	*  @created: 23/06/12
	*/
	
	function admin_print_scripts()
	{
		// validate page
//		if( ! $this->validate_page() ) return;
		
		
		// no autosave
		wp_dequeue_script( 'autosave' );
		
		
		// custom scripts
        wp_enqueue_script(array(
            'acf-field-group',
        ));


        do_action('acf_print_scripts-fields');
	}
	
	
	/*
	*  admin_print_styles
	*
	*  @description: 
	*  @since 3.1.8
	*  @created: 23/06/12
	*/
	
	function admin_print_styles()
	{
		// validate page
//		if( ! $this->validate_page() ) return;


        // custom styles
        wp_enqueue_style(array(
            'acf-global',
            'acf-field-group',
        ));


        do_action('acf_print_styles-fields');
		
	}
	
	
	/*
	*  admin_head
	*
	*  @description: 
	*  @since 3.1.8
	*  @created: 23/06/12
	*/
	
	function admin_head()
	{
		// validate page
//		if( ! $this->validate_page() ) return;
		
		
		global $post;
		
		
		// add js vars
		echo '<script type="text/javascript">
			acf.nonce = "' . wp_create_nonce( 'acf_nonce' ) . '";
			acf.post_id = ' . $post->ID . ';
		</script>';

		
		do_action('acf_head-fields');


		// add metaboxes
		add_meta_box('acf_fields', __("Fields",'acf'), array($this, 'meta_box_fields'), 'acf', 'normal', 'high');
		add_meta_box('acf_location', __("Location",'acf'), array($this, 'meta_box_location'), 'acf', 'normal', 'high');
		add_meta_box('acf_options', __("Options",'acf'), array($this, 'meta_box_options'), 'acf', 'normal', 'high');


		// add screen settings
		add_filter('screen_settings', array($this, 'screen_settings'), 10, 1);
	}
	
	
	/*
	*  screen_settings
	*
	*  @description: 
	*  @created: 4/09/12
	*/
	
	function screen_settings( $current )
	{
	    $current .= '<h5>' . __("Fields",'acf') . '</h5>';
	    
	    $current .= '<div class="show-field_key">Show Field Key:';
	    	 $current .= '<label class="show-field_key-no"><input checked="checked" type="radio" value="0" name="show-field_key" /> No</label>';
	    	 $current .= '<label class="show-field_key-yes"><input type="radio" value="1" name="show-field_key" /> Yes</label>';
		$current .= '</div>';
	    
	    return $current;
	}
	
	
	/*
	*  meta_box_fields
	*
	*  @description: 
	*  @since 1.0.0
	*  @created: 23/06/12
	*/
	
	function meta_box_fields()
	{
		include( $this->acf->path . 'core/views/meta_box_fields.php' );
	}
	
	
	/*
	*  meta_box_location
	*
	*  @description: 
	*  @since 1.0.0
	*  @created: 23/06/12
	*/

	function meta_box_location()
	{
		include( $this->acf->path . 'core/views/meta_box_location.php' );
	}
	
	
	/*
	*  meta_box_options
	*
	*  @description: 
	*  @since 1.0.0
	*  @created: 23/06/12
	*/
	
	function meta_box_options()
	{
		include( $this->acf->path . 'core/views/meta_box_options.php' );
	}
	
	
	/*
	*  ajax_acf_field_options
	*
	*  @description: creates the HTML for a field's options (field group edit page)
	*  @since 3.1.6
	*  @created: 23/06/12
	*/
	
	function ajax_acf_field_options()
	{

		// vars
		$options = array(
			'field_key' => '',
			'field_type' => '',
			'post_id' => 0,
			'nonce' => ''
		);

		// load post options
		$options = array_merge($options, $_POST);


		// verify nonce
		if( ! wp_verify_nonce($options['nonce'], 'acf_nonce') )
		{
			die(0);
		}



		// required
		if( ! $options['field_type'] )
		{
			die(0);
		}


		// find key (not actual field key, more the html attr name)
		$options['field_key'] = str_replace("fields[", "", $options['field_key']);
		$options['field_key'] = str_replace("][type]", "", $options['field_key']) ;



		$field = array();

		// render options
		$this->acf->fields[ $options['field_type'] ]->create_options($options['field_key'], $field);

		die();

	}
	
	
	/*
	*  ajax_acf_location
	*
	*  @description: creates the HTML for the field group location metabox. Called from both Ajax and PHP
	*  @since 3.1.6
	*  @created: 23/06/12
	*/
	
	function ajax_acf_location( $options = array() )
	{
		// defaults
		$defaults = array(
			'key' => null,
			'value' => null,
			'param' => null,
		);

		// Is AJAX call?
		if(isset($_POST['action']) && $_POST['action'] == "acf_location")
		{
			$options = array_merge($defaults, $_POST);
		}
		else
		{
			$options = array_merge($defaults, $options);
		}


		// some case's have the same outcome
		if($options['param'] == "page_parent")
		{
			$options['param'] = "page";
		}


		$choices = array();
		$optgroup = false;

		switch($options['param'])
		{
			//...........................................................................
            case "post_type":

				// all post types except attachment
				$choices = $this->acf->get_post_types( array('attachment') );

				break;

            //...........................................................................
			case "page":

				$optgroup = true;
				$post_types = get_post_types( array('capability_type'  => 'page') );
				unset( $post_types['attachment'], $post_types['revision'] , $post_types['nav_menu_item'], $post_types['acf']  );

				if( $post_types )
				{
					foreach( $post_types as $post_type )
					{
						$pages = get_pages(array(
							'numberposts' => -1,
							'post_type' => $post_type,
							'sort_column' => 'menu_order',
							'order' => 'ASC',
							'post_status' => array('publish', 'private', 'draft', 'inherit', 'future'),
							'suppress_filters' => false,
						));

						if( $pages )
						{
							$choices[$post_type] = array();

							foreach($pages as $page)
							{
								$title = '';
								$ancestors = get_ancestors($page->ID, 'page');
								if($ancestors)
								{
									foreach($ancestors as $a)
									{
										$title .= '- ';
									}
								}

								$title .= apply_filters( 'the_title', $page->post_title, $page->ID );


								// status
								if($page->post_status != "publish")
								{
									$title .= " ($page->post_status)";
								}

								$choices[$post_type][$page->ID] = $title;

							}
							// foreach($pages as $page)
						}
						// if( $pages )
					}
					// foreach( $post_types as $post_type )
				}
				// if( $post_types )

				break;

            //...........................................................................
			case "page_type" :

				$choices = array(
					'front_page'	=>	__("Front Page",'acf'),
					'posts_page'	=>	__("Posts Page",'acf'),
					'parent'		=>	__("Parent Page",'acf'),
					'child'			=>	__("Child Page",'acf'),
				);

				break;

            //...........................................................................
			case "page_template" :

				$choices = array(
					'default'	=>	__("Default Template",'acf'),
				);

				$templates = get_page_templates();
				foreach($templates as $k => $v)
				{
					$choices[$v] = $k;
				}

				break;

            //...........................................................................
			case "post" :

				$optgroup = true;
				$post_types = get_post_types( array('capability_type'  => 'post') );
				unset( $post_types['attachment'], $post_types['revision'] , $post_types['nav_menu_item'], $post_types['acf']  );

				if( $post_types )
				{
					foreach( $post_types as $post_type )
					{

						$posts = get_posts(array(
							'numberposts' => '-1',
							'post_type' => $post_type,
							'post_status' => array('publish', 'private', 'draft', 'inherit', 'future'),
							'suppress_filters' => false,
						));

						if( $posts)
						{
							$choices[$post_type] = array();

							foreach($posts as $post)
							{
								$title = apply_filters( 'the_title', $post->post_title, $post->ID );

								// status
								if($post->post_status != "publish")
								{
									$title .= " ($post->post_status)";
								}

								$choices[$post_type][$post->ID] = $title;

							}
							// foreach($posts as $post)
						}
						// if( $posts )
					}
					// foreach( $post_types as $post_type )
				}
				// if( $post_types )


				break;

            //...........................................................................
			case "post_category" :

				$category_ids = get_all_category_ids();

				foreach($category_ids as $cat_id)
				{
				  $cat_name = get_cat_name($cat_id);
				  $choices[$cat_id] = $cat_name;
				}

				break;

            //...........................................................................
			case "post_format" :

				$choices = get_post_format_strings();

				break;

            //...........................................................................
			case "user_type" :

				global $wp_roles;

				$choices = $wp_roles->get_names();

				break;

            //...........................................................................
			case "options_page" :

				$defaults = $this->acf->defaults['options_page'];

				$choices = array(
					'acf-options' => $defaults['title']
				);

				$titles = $defaults['pages'];
				if( !empty($titles) )
				{
					$choices = array();
					foreach( $titles as $title )
					{
						$slug = 'acf-options-' . sanitize_title( $title );
						$choices[ $slug ] = $title;
					}
				}

				break;

            //...........................................................................
			case "post_taxonomy" :

				$choices = $this->acf->get_taxonomies_for_select( array('simple_value' => true) );
				$optgroup = true;

				break;

            //...........................................................................
			case "taxonomy" :

				$choices = array('all' => __('All', 'acf'));
				$taxonomies = get_taxonomies( array('public' => true), 'objects' );

				foreach($taxonomies as $taxonomy)
				{
					$choices[ $taxonomy->name ] = $taxonomy->labels->name;
				}

				// unset post_format (why is this a public taxonomy?)
				if( isset($choices['post_format']) )
				{
					unset( $choices['post_format']) ;
				}


				break;

            //...........................................................................
			case "user" :

				global $wp_roles;

				$choices = array_merge( array('all' => __('All', 'acf')), $wp_roles->get_names() );

				break;

            //...........................................................................
			case "media" :

				$choices = array('all' => __('All', 'acf'));

				break;

		}
		
		$this->acf->create_field(array(
			'type'	=>	'select',
			'name'	=>	'location[rules][' . $options['key'] . '][value]',
			'value'	=>	$options['value'],
			'choices' => $choices,
			'optgroup' => $optgroup,
		));
		
		// ajax?
		if(isset($_POST['action']) && $_POST['action'] == "acf_location")
		{
			die();
		}
								
	}	
	
	
	/*
	*  save_name
	*
	*  @description: intercepts the acf post obejct and adds an "acf_" to the start of 
	*				 it's name to stop conflicts between acf's and page's urls
	*  @since 1.0.0
	*  @created: 23/06/12
	*/
		
	function save_name($name)
	{
        //******************************
        //   wdh : removed
        //   keep acf post name as is no conflict with page/post names theyre separate post types
        //

        if (isset($_POST['post_type']) && $_POST['post_type'] == 'acf')
        {
//			$name = 'acf_' . sanitize_title_with_dashes($_POST['post_title']);
            $name = sanitize_title_with_dashes($_POST['post_title']);
        }

        // ******************************
        
        return $name;
	}


    /*--------------------------------------------------------------------------------------
    *  save_post
    *
    *  @description: Saves the field / location / display-option data for a field group
    *  @author Elliot Condon / Wayne D Harris
    *  @since 1.0.0
    *  @created: 23/06/12
    *-------------------------------------------------------------------------------------*/

    function save_post($acf_post_id)
    {
        global $post;

        // only for save acf
        if( ! isset($_POST['acf_field_group']) || ! wp_verify_nonce($_POST['acf_field_group'], 'acf_field_group') )
        {
            return $acf_post_id;
        }

        // do not save if this is an auto save routine
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return $acf_post_id;

        // only save once! WordPress save's a revision as well.
        if( wp_is_post_revision($acf_post_id) )
        {
            return $acf_post_id;
        }

        /*--------------------------------------
		*  save fields
		*--------------------------------------*/
        $fields = array();

        if( $_POST['fields'] )
        {
            $i = -1;

            // remove clone field
            unset( $_POST['fields']['field_clone'] );

            // loop through and save fields
            foreach( $_POST['fields'] as $key => $field )
            {
                $i++;

                $field['order_no'] = $i;
                $field['key'] = $key;
                // trim key
                $field['key'] = preg_replace('/\s+/' , '' , $field['key']);
                $field = $this->acf->apply_save_field_filters( $field );
                $fields[$field['name']] = $field;
            }
        }

        /*--------------------------------------
        *  save location rules
        *--------------------------------------*/
        $location_defaults = array(
            'rules'		=>	array(),
            'allorany'	=>	'all',
        );

        $location = $_POST['location'];

        $location_config = array();
        $location_config['allorany'] = $location['allorany'];

        if($location['rules'])
        {
            foreach($location['rules'] as $k => $rule)
            {
                $rule['order_no'] = $k;

                $location_config['rules'][$rule['order_no']] = $rule;
            }
        }
        ksort($location_config['rules']);

        $location_config = array_merge( $location_defaults, $location_config );

        /*--------------------------------------
		*  save options
		*--------------------------------------*/
        $options_defaults = array(
            'position'			=>	'normal',
            'layout'			=>	'no_box',
            'hide_on_screen'	=>	array(),
        );

        $options = $_POST['options'];

        if(!isset($options['position'])) { $options['position'] = 'normal'; }
        if(!isset($options['layout'])) { $options['layout'] = 'default'; }
        if(!isset($options['hide_on_screen'])) { $options['hide_on_screen'] = array(); }

        $options = array_merge( $options_defaults, $options );
        /*--------------------------------------
        *  build field group config array
        *--------------------------------------*/

        // ********************************
        // wdh : all the field_group config is saved as one post_meta value rather than hitting the table with many values
        // id, title and menu_order are added here to make for easier access later (rather than adding on the fly in acf.php->get_field_groups())
        // this saving of one array also allows a cascading of global default options (saved in one array entry in the options table) with page-specfic options

        $field_group_config                 = array();
        $field_group_config['id']           = $acf_post_id;
        $field_group_config['name']         = sanitize_title( $_POST['post_title'] );    // wdh :  new field added to $acf object
        $field_group_config['title']        = $_POST['post_title'];
        $field_group_config['fields']       = $fields;
        $field_group_config['location']     = $location_config;
        $field_group_config['options']      = $options;
        $field_group_config['menu_order']   = $_POST['menu_order'];

//        $_POST['post_name'] = sanitize_title( get_the_title() );

        // todo - add theme prefix

        update_post_meta( $acf_post_id, '_field_group', $field_group_config );

//      phplog('field_group','$field_group_config=',$field_group_config);

    }


    /*--------------------------------------------------------------------------------------
    *  ajax_next_field_id
    *
    *  @description:
    *  @since: 2.0.4
    *  @created: 5/12/12
    *-------------------------------------------------------------------------------------*/
	
	function ajax_acf_next_field_id()
	{
		// vars
		$options = array(
			'nonce' => '',
		);
		$options = array_merge($options, $_POST);
		
		
		// verify nonce
		if( ! wp_verify_nonce($options['nonce'], 'acf_nonce') )
		{
			die(0);
		}
		
		
		// return id
		$id = $this->acf->get_next_field_id();
		
		
		// die
		die( 'field_' . $id );
	}

}

?>