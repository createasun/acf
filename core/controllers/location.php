<?php 

/*
*  acf_location
*
*  @description: 
*  @since: 3.5.7
*  @created: 3/01/13
*/

class acf_location
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

		// ajax
		add_action('wp_ajax_acf/location/get_screen_field_group_ids_ajax', array($this, 'get_screen_field_group_ids_ajax'));

	}

	
	/*
	*  match_field_groups_ajax
	*
	*  @description: 
	*  @since: 3.5.7
	*  @created: 3/01/13
	*/
	
	function get_screen_field_group_ids_ajax()
	{
		
		// vars
		$screen = array(
			'nonce' => '',
			'return' => 'json'
		);
		
		
		// load post options
		$screen = array_merge($screen, $_POST);
		
		
		// verify nonce
		if( ! wp_verify_nonce($screen['nonce'], 'acf_nonce') )
		{
			die(0);
		}
		
		
		// return array
//		$return = array();
//		$return = apply_filters( 'acf/location/match_field_groups', $return, $screen );

        $return = $this->get_screen_field_group_ids( $screen );
		
		
		// echo json
		echo json_encode( $return );
		
		
		die();	
	}
	
	
	/*
	*  match_field_groups
	*
	*  @description: 
	*  @since: 3.5.7
	*  @created: 3/01/13
	*/
	
	function get_screen_field_group_ids( $screen )
	{
		// vars
		$defaults = array(
			'post_id' => 0,
			'post_type' => 0,

            //match types
			'page_template' => 0,
			'page_parent' => 0,
			'page_type' => 0,
			'post_category' => array(),
			'post_format' => 0,
			'post_taxonomy' => array(),   //todo : make post_taxonomy
			'taxonomy' => 0,     //todo : make taxonomy
			'user' => 0,         //todo : make media
			'media' => 0,

			'lang' => 0,
			'return' => 'php',

		);
        phplog('location.php','$screen=',$screen);
		
		// merge in $screen
		$screen = array_merge($defaults, $screen);


		// Parse values
		$screen = apply_filters( 'acf_parse_value', $screen );
		

		// WPML
		if( defined('ICL_LANGUAGE_CODE') )
		{
			$screen['lang'] = ICL_LANGUAGE_CODE;
			
			//global $sitepress;
			//$sitepress->switch_lang( $screen['lang'] );
		}
		
		
		// find all acf objects
		$field_groups = $this->acf->get_field_groups();

        $field_group_post_ids = array();
		
		
		if( $field_groups )
		{
			foreach( $field_groups as $field_group )
			{
				if( $field_group['location']['rules'] )
				{
					// defaults
					$rule_defaults = array(
						'param' => '',
						'operator' => '==',
						'value' => ''
					);
					
					foreach($field_group['location']['rules'] as $rule)
					{
						$rule = array_merge( $rule_defaults, $rule );

//                        phplog('location.php','$rule[param]=',$rule['param']);
//                        phplog('location.php','$screen=',$screen);

                        $match = call_user_func( array($this, 'rule_match_'.$rule['param']), $rule['value'], $screen );

                        $match = ($rule['operator'] == "==") ? $match : !$match;

                        $add_id = ( !( $field_group['location']['allorany']=='all' && !$match ) || ($field_group['location']['allorany']=='any' && $match ) );
					}
				}
					
				// add ID to array
				if( $add_id )
				{
					$field_group_post_ids[] = $field_group['id'];
				}
				
			}
		}
		return $field_group_post_ids;
	}

	/*
	*  rule_match_post_type
	*
	*  @description: 
	*  @since: 3.5.7
	*  @created: 3/01/13
	*/
	
	function rule_match_post_type( $config_value, $screen )
	{
		$post_type = $screen['type'];

		if( !$post_type )
		{
			$post_type = get_post_type( $screen['post_id'] );
		}

        $match = ( $post_type == $config_value );

        return $match;
	}
	
	
	/*
	*  rule_match_post
	*
	*  @description: 
	*  @since: 3.5.7
	*  @created: 3/01/13
	*/
	
	function rule_match_post( $config_value, $screen )
	{

		// translate $config_value
		// - this variable will hold the origional post_id, but $screen['post_id'] will hold the translated version
		//if( function_exists('icl_object_id') )
		//{
		//	$config_value = icl_object_id( $config_value, $screen['type'], true );
		//}
		
       	$match = ( $screen['post_id'] && $screen['post_id'] == $config_value );

        return $match;

	}
	
	
	/*
	*  rule_match_page_type
	*
	*  @description: 
	*  @since: 3.5.7
	*  @created: 3/01/13
	*/
	
	function rule_match_page_type( $config_value, $screen )
	{
		// validation
		if( !$screen['post_id'] )
		{
			return false;
		}

		$post = get_post( $screen['post_id'] );
		        
        if( $config_value == 'front_page')
        {
            $match = ( (int)get_option('page_on_front') == $post->ID );
        }
        elseif( $config_value == 'posts_page')
        {
            $match = ( (int)get_option('page_for_posts') == $post->ID );
        }
        elseif( $config_value == 'parent')
        {
         	$children = get_pages( array('post_type' => $post->post_type,'child_of' =>  $post->ID) );
            $match = ( count($children) > 0 );
        }
        elseif( $config_value == 'child')
        {
        	$post_parent = ( $screen['page_parent'] ) ? $screen['page_parent'] : $post->post_parent;
            $match = ($post_parent > 0);
        }
        return $match;

	}
	
	
	/*
	*  rule_match_page_parent
	*
	*  @description: 
	*  @since: 3.5.7
	*  @created: 3/01/13
	*/
	
	function rule_match_page_parent( $config_value, $screen )
	{
		// validation
		if( !$screen['post_id'] )
		{
			return false;
		}

		$post = get_post( $screen['post_id'] );
		$page_parent = $post->post_parent;

      	$match = ( $page_parent == $config_value );

        return $match;

	}
	
	
	/*
	*  rule_match_page_template
	*
	*  @description: 
	*  @since: 3.5.7
	*  @created: 3/01/13
	*/
	
	function rule_match_page_template( $config_value, $screen )
	{
		$page_template = ($screen['page_template']) ? $screen['page_template'] : get_post_meta( $screen['post_id'], '_wp_page_template', true );

		if( !$page_template )
		{
			$post_type = $screen['type'];

			if( !$post_type ){ $post_type = get_post_type( $screen['post_id'] ); }
			
			if( $post_type == 'page' )
			{
				$page_template = "default";
			}
		}
		
       	$match = ( $page_template == $config_value );

        return $match;

	}
	
	
	/*
	*  rule_match_post_category
	*
	*  @description: 
	*  @since: 3.5.7
	*  @created: 3/01/13
	*/
	
	function rule_match_post_category( $config_value, $screen )
	{
		$cats = $screen['post_category'];
		
		if( empty($cats) )
		{
			if(!$screen['post_id']) { return false; }
			
			$cats = get_the_category( $screen['post_id'] );
        	foreach( $cats as $cat )
			{
				$cats[] = $cat->term_id;
			}
		}
		
		$post_type = $screen['type'];

		if( !$post_type )
		{
			$post_type = get_post_type( $screen['post_id'] );
		}
		
		$taxonomies = get_object_taxonomies( $post_type );

		// If no $cats is a new post treat as uncategorized
		if( in_array('category', $taxonomies) && empty($cats) )
		{
			$cats[] = '1';
		}

        $match = ( $cats && in_array($config_value, $cats) );

        return $match;
     }
    
    
    /*
	*  rule_match_user_type
	*
	*  @description: 
	*  @since: 3.5.7
	*  @created: 3/01/13
	*/
	
	function rule_match_user_type( $config_value, $screen )
	{
		$match = ( current_user_can($config_value) );

        return $match;
        
    }
    
    
    /*
	*  rule_match_user_type
	*
	*  @description: 
	*  @since: 3.5.7
	*  @created: 3/01/13
	*/
	
	function rule_match_options_page( $config_value, $screen )
	{
		global $plugin_page;
		    	
		    	
		// older location rules may be "options-pagename"
//		if( substr($config_value, 0, 8) == 'options-' )
//		{
//			$config_value = 'acf-' . $config_value;
//		}
//
//
//		// older location ruels may be "Pagename"
//		if( substr($config_value, 0, 11) != 'acf-options' )
//		{
//			$config_value = 'acf-options-' . sanitize_title( $config_value );
//
//			// value may now be wrong (acf-options-options)
//			if( $config_value == 'acf-options-options' )
//			{
//				$config_value = 'acf-options';
//			}
//		}
		
       	$match = ( $plugin_page == $config_value );

        return $match;
        
    }
    
    
    /*
	*  rule_match_post_format
	*
	*  @description: 
	*  @since: 3.5.7
	*  @created: 3/01/13
	*/
	
	function rule_match_post_format( $config_value, $screen )
	{
		// vars
		$post_format = $screen['post_format'];
		if( ! $post_format )
		{
			$post_format = get_post_format( $screen['post_id'] );
		}
       
       	$match = ( $post_format == $config_value );

        return $match;
        
    }
    
    
    /*
	*  rule_match_taxonomy
	*
	*  @description: 
	*  @since: 3.5.7
	*  @created: 3/01/13
	*/

    // wdh : this is 'post_taxonomy' ********************

	function rule_match_post_taxonomy( $config_value, $screen )
	{
        $terms = $screen['post_taxonomy'];

		if( empty($terms) )
		{
			if( !$screen['post_id'] )
			{
				return false;
			}
			
			$post_type = $screen['type'];

			if( !$post_type )
			{
				$post_type = get_post_type( $screen['post_id'] );
			}
			
			$taxonomies = get_object_taxonomies( $post_type );
			
        	if($taxonomies)
        	{
	        	foreach($taxonomies as $tax)
				{
					$all_terms = get_the_terms( $screen['post_id'], $tax );
					if($all_terms)
					{
						foreach($all_terms as $all_term)
						{
							$terms[] = $all_term->term_id;
						}
					}
				}
			}
		}
		
		
		// If no $cats is a new post treat as uncategorized
		if( in_array('category', $taxonomies) && empty($terms) )    // todo $taxonomies ????????????????
		{
			$terms[] = '1';
		}

       	$match = ( $terms && in_array($config_value, $terms) );

        return $match;
        
    }
    
    
    /*
	*  rule_match_ef_taxonomy
	*
	*  @description: 
	*  @since: 3.5.7
	*  @created: 3/01/13
	*/
	
	function rule_match_taxonomy( $config_value, $screen )
	{
	
		$taxonomy = $screen['taxonomy'];
		
       	$match = ( $config_value=="all" ) ? true : ( $taxonomy && $taxonomy==$config_value );

        return $match;
        
    }
    
    
    /*
	*  rule_match_ef_user
	*
	*  @description: 
	*  @since: 3.5.7
	*  @created: 3/01/13
	*/
	
	function rule_match_user( $config_value, $screen )
	{
        $ef_user = $screen['user'];

        $match = ($config_value == "all") ? true : ( user_can($ef_user, $config_value) );

        return $match;

    }
    
    
    /*
	*  rule_match_ef_media
	*
	*  @description: 
	*  @since: 3.5.7
	*  @created: 3/01/13
	*/
	
	function rule_match_media( $config_value, $screen )
	{
		global $pagenow;

		
		if( $pagenow == 'post.php' )
		{
			// in 3.5, the media rule should check the post type
			$config_value['param'] = 'post_type';
			$config_value = 'attachment';
			return $this->rule_match_post_type( $config_value, $screen );
		}
		
		
		$ef_media = $screen['media'];
		
        $match = ( $ef_media && $config_value == "all" );

        return $match;

    }

}

?>