<?php 

class acf_everything_fields 
{

	var $acf;
	var $dir;
	var $data;
	
	/*--------------------------------------------------------------------------------------
	*
	*	Everything_fields
	*
	*	@author Elliot Condon
	*	@since 3.1.8
	* 
	*-------------------------------------------------------------------------------------*/
	
	function __construct($acf)
	{
		// vars
		$this->acf = $acf;
		$this->dir = $acf->dir;
		
		
		// data for passing variables
		$this->data = array(
			'page_id' => '', // a string used to load values
			'metabox_ids' => array(),
			'page_type' => '', // taxonomy / user / media
			'page_action' => '', // add / edit
			'option_name' => '', // key used to find value in wp_options table. eg: user_1, category_4
		);
		
		
		// actions
		add_action('admin_menu', array($this,'admin_menu'));
		add_action('wp_ajax_acf_everything_fields', array($this, 'acf_everything_fields'));
		
		
		// save
		add_action('create_term', array($this, 'save_taxonomy'));
		add_action('edited_term', array($this, 'save_taxonomy'));
		
		add_action('edit_user_profile_update', array($this, 'save_user'));
		add_action('personal_options_update', array($this, 'save_user'));
		add_action('user_register', array($this, 'save_user'));
		
		
		add_filter("attachment_fields_to_save", array($this, 'save_attachment'), null , 2);

		// shopp
		add_action('shopp_category_saved', array($this, 'shopp_category_saved'));
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
//		global $pagenow;
//
//
//		// vars
//		$return = false;
//
//
//		// validate page
//		if( in_array( $pagenow, array( 'edit-tags.php', 'profile.php', 'user-new.php', 'user-edit.php', 'media.php' ) ) )
//		{
//			$return = true;
//		}
//
//
//		// validate page (Shopp)
//		if( $pagenow == "admin.php" && isset( $_GET['page'], $_GET['id'] ) && $_GET['page'] == "shopp-categories" )
//		{
//			$return = true;
//		}
//
//
//		// return
//		return $return;
//	}
	
	
	/*--------------------------------------------------------------------------------------
	*
	*	admin_menu
	*
	*	@author Elliot Condon
	*	@since 3.1.8
	* 
	*-------------------------------------------------------------------------------------*/
	
	function admin_menu() 
	{
	
		global $pagenow;

		
		// validate page
//		if( ! $this->validate_page() ) return;
		
		
		// set page type
		$options = array();
		
		if( $pagenow == "admin.php" && isset( $_GET['page'], $_GET['id'] ) && $_GET['page'] == "shopp-categories" )
		{
			$this->data['page_type'] = "shopp_category";
			$options['ef_taxonomy'] = "shopp_category";
			
			$this->data['page_action'] = "add";
			$this->data['option_name'] = "";
			
			if( $_GET['id'] != "new" )
			{
				$this->data['page_action'] = "edit";
				$this->data['option_name'] = "shopp_category_" . $_GET['id'];
			}
			
		}
		if( $pagenow == "edit-tags.php" && isset($_GET['taxonomy']) )
		{
		    $taxonomy_name           = $_GET['taxonomy'];
			$this->data['page_type'] = "taxonomy";
			$options['ef_taxonomy']  = $taxonomy_name;
			
			$this->data['page_action'] = "add";
			$this->data['option_name'] = "";
			
			if(isset($_GET['action']) && $_GET['action'] == "edit")
			{
				$this->data['page_action'] = "edit";
				$this->data['option_name'] = $taxonomy_name . "_" . $_GET['tag_ID'];

                // wdh : hook rather than jquery - cleaner
                add_action( $taxonomy_name.'_edit_form_fields', array($this,'render_input_fields'));
			}
            else
            {
                // wdh : hook rather than jquery - cleaner
                add_action( $taxonomy_name.'_add_form_fields', array($this,'render_input_fields'));
            }
		}
		elseif( $pagenow == "profile.php" )
		{
			$this->data['page_type'] = "user";
			$options['ef_user'] = get_current_user_id();
			
			$this->data['page_action']  = "edit";
			$this->data['option_name']  = "user_" . get_current_user_id();
            //wdh
            $this->data['primary_key_id'] = get_current_user_id();
            // wdh : hook rather than jquery - cleaner
            add_action('show_user_profile', array($this,'render_input_fields'));
			
		}
		elseif( $pagenow == "user-edit.php" && isset($_GET['user_id']) )
		{
			$this->data['page_type'] = "user";
			$options['ef_user'] = $_GET['user_id'];
			
			$this->data['page_action'] = "edit";
			$this->data['option_name'] = "user_" . $_GET['user_id'];
            //wdh
            $this->data['primary_key_id'] = $_GET['user_id'];
            // wdh : hook rather than jquery - cleaner
            add_action('show_user_profile', array($this,'render_input_fields'));
			
		}
		elseif( $pagenow == "user-new.php" )
		{
			$this->data['page_type'] = "user";
			$options['ef_user'] ='all';
			
			$this->data['page_action'] = "add";
			$this->data['option_name'] = "";
            // wdh : no hook dammit use elliots jquery


		}

        // media.php is deprecated in WP 3.5+

		elseif( $pagenow == "media.php" )
		{
			
			$this->data['page_type'] = "media";
			$options['ef_media'] = 'all';
			
			$this->data['page_action'] = "add";
			$this->data['option_name'] = "";
			
			if(isset($_GET['attachment_id']))
			{
				$this->data['page_action'] = "edit";
				$this->data['option_name'] = $_GET['attachment_id'];
			}
			
		}
		
		
		// find metabox id's for this page
		$this->data['field_group_ids'] = $this->acf->get_input_metabox_ids( $options , false );

		
		// dont continue if no ids were found
		if(empty( $this->data['field_group_ids'] ))
		{
			return false;	
		}
		
		
		// some fields require js + css
		do_action('acf_print_scripts-input');
		do_action('acf_print_styles-input');

		
		// Add admin head
		add_action('admin_head', array($this,'admin_head'));

		
	}

    /*--------------------------------------------------------------------------------------
	*
	*	admin_head
	*
	*	@author Elliot Condon
	*	@since 3.1.8
	*
	*-------------------------------------------------------------------------------------*/

    function admin_head()
    {
        global $pagenow;


        // add user js + css
        do_action('acf_head-input');

//        $this->render_input_fields($this->data);


    }

    /*--------------------------------------------------------------------------------------
    *
    *	render_input_fields
    *
    *	@author 			Wayne D Harris / Elliot Condon
    *	@since 				3.1.8
    *
    *-------------------------------------------------------------------------------------*/

    function render_input_fields()
    {
        // defaults
        $defaults = array(
            'field_group_ids'   => '',
            'page_type'         => '',
            'page_action'       => '',
            'option_name'       => '',
            'primary_key_id'      => 0,
        );


        // load post options
        $options = array_merge($defaults, $this->data);


        // metabox ids is a string with commas
//        $options['field_group_ids'] = explode( ',', $options['field_group_ids'] );


        // layout
        $layout = 'tr';
        if( $options['page_type'] == "taxonomy" && $options['page_action'] == "add")
        {
            $layout = 'div';
        }
        if( $options['page_type'] == "shopp_category")
        {
            $layout = 'metabox';
        }


        foreach( $options['field_group_ids'] as $field_group_post_id)
        {
            $field_group = $this->acf->get_acf_field_group($field_group_post_id);


            // needs fields
            if(!$field_group['fields']) { continue; }



            // start : input ->render_field_group_input_form() >>>>>>>>>>>>>>>>>>>>>>>>>



            $title = "";
            if ( is_numeric( $field_group['id'] ) )
            {
                $title = get_the_title( $field_group['id'] );
            }
            else
            {
                $title = apply_filters( 'the_title', $field_group['title'] );
            }


            // title
            if( $options['page_action'] == "edit" && !in_array($options['page_type'], array('media', 'shopp_category')) )
            {
                echo '<h3>' .$title . '</h3>';
                echo '<table class="form-table">';
            }
            elseif( $layout == 'metabox' )
            {
                echo '<div class="postbox acf_postbox" id="acf_'. $field_group['id'] .'">';
                echo '<div title="Click to toggle" class="handlediv"><br></div><h3 class="hndle"><span>' . $title . '</span></h3>';
                echo '<div class="inside">';
            }

            $field_group_values_key = $field_group['name'];
            $field_group_post_name  = $field_group['name'];
            $primary_key_id         = $options['primary_key_id'];


            echo '<input type="hidden" name="save_input" value="true" />';

            // wdh : additional hidden data sent in $_POST via name/value pairs
            // allow data from this metabox to be posted to a different post than its acf

            echo '<input type="hidden" name="field_group_values_target_post_id['.$field_group_values_key.']" value="'.$primary_key_id.'"/>';

            // save the field_group_post_name/id the metabox is using
            echo '<input type="hidden" name="field_group_post_id['.$field_group_values_key.']" value="'.$field_group_post_id.'"/>';
            echo '<input type="hidden" name="field_group_post_name['.$field_group_values_key.']" value="'.$field_group_post_name.'"/>';



            // start : acf->render_fields_for_input() >>>>>>>>>>>>>>>>>>>>>>>>>


            $field_group_values = $this->acf->get_field_group_values( $primary_key_id, $field_group_values_key, $field_group['id'], $options['page_type'] );

            // filter, set defaults and clean
            $field_config_value_pair = $this->acf->map_field_config_to_value( $field_group['fields'], $field_group_values['field_values'], ACF_LOAD_VALUE_, ACF_LOAD_FIELD_ );

            //set caches of load value filtered results - we'll read from these now until an update
            $field_group_values['field_values'] = $field_config_value_pair['value'];
            $this->acf->set_cache( 'acf_field_group_values_'.$field_group_values_key, $field_group_values );


            // render
            foreach($field_group['fields'] as $field)
            {
                // no type - skip this field
                if(!$field['type'] || $field['type'] == 'null') { continue; }

                $required_class = ($field['required']) ? ' required' : '';
                $required_label = ($field['required']) ? ' <span class="required">*</span>' : '';

                // wdh : set fields and make more readable in rendering code
                // wdh : create $field['slug'] because $field['name'] gets rewritten later
                $field['slug']      = $field['name'];
                $field_slug         = $field['slug'];

                $uid                = $field_group_values_key.'-'.$field_slug;

                $field_wrapper_id   = 'acf-'.$uid;

                $field['id']        = 'acf-field-'.$uid;
                $field_id           = $field['id'];

                $field_type         = $field['type'];
                $field_key          = $field['key'];
                $field_label        = $field['label'];
                $field_instructions = $field['instructions'];

                // name is key
                // group separate fields into field groups
                // the $field['name'] is rewritten for $_POST field 'name' multidimensional array
                $field['name']      = 'fields['.$field_group_values_key.']['.$field_slug.']';

                // get value from values_array
                $field['value'] = $this->acf->get_field_value( $field_group_values_key, array( $field_slug ), $primary_key_id, false, $field_group_post_id );


                if( $layout == 'metabox' )
                {
                    echo '<div class="options" data-layout="'.$options['options']['layout'].' style="display:none"></div>';
                    echo '<div id='.$field_wrapper_id.'" class="field field-'.$field_type.' field-'.$field_slug. $required_class. '"data-field_name="'.$field_slug.'" data-field_key="'.$field_key.'">';
                    echo '<p class="label">';
                    echo '<label for='.$field_id.'>'.$field_label.$required_label.'</label>';
                    echo $field_instructions;
                    echo '</p>';
                    $this->acf->create_field($field);
                    echo '</div>';
                }
                elseif( $layout == 'div' )
                {
                    echo '<div id='.$field_wrapper_id.'" class="form-field field-'.$field_type.' field-'.$field_slug. $required_class.'">';
                    echo '<label for='.$field_id.'>'.$field_label.$required_label.'</label>';
                    $this->acf->create_field($field);
                    if($field_instructions) { echo '<p class="description">'.$field_instructions.'</p>'; }
                    echo '</div>';
                }
                else // table row for users /
                {
                    echo '<div id='.$field_wrapper_id.'" class="form-field field-'.$field_type.' field-'.$field_slug. $required_class.'">';
                    echo '<th valign="top" scope="row"><label for='.$field_id.'>'.$field_label.$required_label.'</label></th>';
                    echo '<td>';
                    $this->acf->create_field($field);
                    if($field_instructions) { echo '<p class="description">'.$field_instructions.'</p>'; }
                    echo '</td>';
                    echo '</tr>';
                }


            }
            // foreach($fields as $field)


            // close tags
            if( $options['page_action'] == "edit" && $options['page_type'] != "media")
            {
                echo '</table>';
            }
            elseif( $options['page_type'] == 'shopp_category' )
            {
                echo '</div></div>';
            }


            //<<<<<<<<<<<<<<<<<<< end : acf->render_fields_for_input()

        }

    }



    /*--------------------------------------------------------------------------------------
    *
    *	admin_head
    *
    *   use jquery to add/append html into user/option/.. page
    *
    *	@author Elliot Condon
    *	@since 3.1.8
    *
    *-------------------------------------------------------------------------------------*/
	
	function admin_head__elliots()
	{	
		global $pagenow;
		
		
		// add user js + css
		do_action('acf_head-input');

		?>
		<script type="text/javascript">
		(function($){

		acf.data = {
			action 			:	'acf_everything_fields',
			field_group_ids	:	'<?php echo implode( ',', $this->data['field_group_ids'] ); ?>',
			page_type		:	'<?php echo $this->data['page_type']; ?>',
			page_action		:	'<?php echo $this->data['page_action']; ?>',
			option_name		:	'<?php echo $this->data['option_name']; ?>'
		};
		
		$(document).ready(function(){

			$.ajax({
				url: ajaxurl,
				data: acf.data,
				type: 'post',
				dataType: 'html',
				success: function(html){

<?php
					if($this->data['page_type'] == "user")
					{
						if($this->data['page_action'] == "add")
						{
							echo "$('#createuser > table.form-table > tbody').append( html );";
						}
//						else
//						{
//							echo "$('#your-profile > p.submit').before( html );";
//						}
					}
					elseif($this->data['page_type'] == "shopp_category")
					{
						echo "$('#post-body-content').append( html );";
					}

//					elseif($this->data['page_type'] == "taxonomy")
//					{
//						if($this->data['page_action'] == "add")
//						{
//							echo "$('#addtag > p.submit').before( html );";
//						}
//						else
//						{
//							echo "$('#edittag > p.submit').before( html );";
//						}
//					}

					elseif($this->data['page_type'] == "media")
					{
						if($this->data['page_action'] == "add")
						{
							echo "$('#addtag > p.submit').before( html );";
						}
						else
						{
							echo "$('#media-single-form table tbody tr.submit').before( html );";
						}
					}
?>

					setTimeout( function(){
						$(document).trigger('acf/setup_fields', $('#wpbody') );
					}, 200);

				}
			});

		});
		})(jQuery);
		</script>
		<?php
	}
	
		
	
	/*--------------------------------------------------------------------------------------
	*
	*	save_taxonomy
	*
	*	@author Elliot Condon
	*	@since 3.1.8
	* 
	*-------------------------------------------------------------------------------------*/
	
	function save_taxonomy( $term_id )
	{
		// for some weird reason, this is triggered by saving a menu... 
		if( !isset($_POST['taxonomy']) )
		{
			return;
		}
		
		// $post_id to save against
		$post_id = $_POST['taxonomy'] . '_' . $term_id;
		
		do_action('acf_save_post', $post_id);
	}
		
		
	/*--------------------------------------------------------------------------------------
	*
	*	profile_save
	*
	*	@author Elliot Condon
	*	@since 3.1.8
	* 
	*-------------------------------------------------------------------------------------*/
	
	function save_user( $user_id )
	{
		// $post_id to save against
		$post_id = 'user_' . $user_id;
		
		do_action('acf_save_post', $post_id);		
	}
	
	
	/*--------------------------------------------------------------------------------------
	*
	*	save_attachment
	*
	*	@author Elliot Condon
	*	@since 3.1.8
	* 
	*-------------------------------------------------------------------------------------*/
	
	function save_attachment( $post, $attachment )
	{
		// $post_id to save against
		$post_id = $post['ID'];
		
		do_action('acf_save_post', $post_id);
		
		return $post;
	}
	
	
	/*
	*  shopp_category_saved
	*
	*  @description: 
	*  @since 3.5.2
	*  @created: 27/11/12
	*/
	
	function shopp_category_saved( $category )
	{
		// $post_id to save against
		$post_id = 'shopp_category_' . $category->id;
		
		do_action('acf_save_post', $post_id);
	}

	/*--------------------------------------------------------------------------------------
	*
	*	acf_everything_fields
	*
	*	@description		Ajax call that renders the html needed for the page
	*	@author 			Elliot Condon
	*	@since 				3.1.8
	* 
	*-------------------------------------------------------------------------------------*/
	
	function acf_everything_fields()
	{
		// defaults
		$defaults = array(
			'field_group_ids' => '',
			'page_type' => '',
			'page_action' => '',
			'option_name' => '',
		);
		
		
		// load post options
		$options = array_merge($defaults, $_POST);
		
		
		// metabox ids is a string with commas
		$options['field_group_ids'] = explode( ',', $options['field_group_ids'] );
		
			
		// get acfs
		$field_groups = $this->acf->get_field_groups();
		
		
		// layout
		$layout = 'tr';	
		if( $options['page_type'] == "taxonomy" && $options['page_action'] == "add")
		{
			$layout = 'div';
		}
		if( $options['page_type'] == "shopp_category")
		{
			$layout = 'metabox';
		}
		
		
		if($field_groups)
		{
			foreach($field_groups as $field_group)
			{
				// only add the chosen field groups
				if( !in_array( $field_group['id'], $options['field_group_ids'] ) )
				{
					continue;
				}
				
				
				// needs fields
				if(!$field_group['fields'])
				{
					continue;
				}
				
				$title = "";
				if ( is_numeric( $field_group['id'] ) )
			    {
			        $title = get_the_title( $field_group['id'] );
			    }
			    else
			    {
			        $title = apply_filters( 'the_title', $field_group['title'] );
			    }
			    
				
				// title 
				if( $options['page_action'] == "edit" && !in_array($options['page_type'], array('media', 'shopp_category')) )
				{
					echo '<h3>' .$title . '</h3>';
					echo '<table class="form-table">';
				}
				elseif( $layout == 'metabox' )
				{
					echo '<div class="postbox acf_postbox" id="acf_'. $field_group['id'] .'">';
					echo '<div title="Click to toggle" class="handlediv"><br></div><h3 class="hndle"><span>' . $title . '</span></h3>';
					echo '<div class="inside">';
				}


                //************************
                //wdh : added
                $field_group_values_key = $field_group['name'];
                //filter field values



                // todo !!!!!!!!!!!!!

                $field_group_values = $this->acf->get_field_group_values( $options['page_type'], $post_id, $field_group_values_key, $field_group['id'] );







                // filter, set defaults and clean
                $field_config_value_pair = $this->acf->map_field_config_to_value( $field_group['fields'], $field_group_values['field_values'], ACF_LOAD_VALUE_, ACF_LOAD_FIELD_ );

                //set caches of load value filtered results - we'll read from these now until an update
                $field_group_values['field_values'] = $field_config_value_pair['value'];
                $this->acf->set_cache( 'acf_field_group_values_'.$field_group_values_key, $field_group_values );
                //************************

				
				// render
				foreach($field_group['fields'] as $field)
				{
				
					// if they didn't select a type, skip this field
					if($field['type'] == 'null') continue;

                    //************************
                    // wdh : removed
					// set value
//					$field['value'] = $this->acf->get_value( $options['option_name'], $field);
                    // wdh : added
                    $field['value'] = $this->acf->get_field_value( $field_group_values_key, array( $field['slug'] ), $post_id, false, $field_group_post_id );
                    //************************
					
					// required
					if(!isset($field['required']))
					{
						$field['required'] = 0;
					}
					
					$required_class = "";
					$required_label = "";
					
					if( $field['required'] )
					{
						$required_class = ' required';
						$required_label = ' <span class="required">*</span>';
					}
					
					
					if( $layout == 'metabox' )
					{
						echo '<div id="acf-' . $field['name'] . '" class="field field-' . $field['type'] . ' field-'.$field['key'] . $required_class . '">';
		
							echo '<p class="label">';
								echo '<label for="fields[' . $field['key'] . ']">' . $field['label'] . $required_label . '</label>';
								echo $field['instructions'];
							echo '</p>';
							
							$field['name'] = 'fields[' . $field['key'] . ']';
							$this->acf->create_field($field);
						
						echo '</div>';
					}
					elseif( $layout == 'div' )
					{
						echo '<div id="acf-' . $field['name'] . '" class="form-field field field-' . $field['type'] . ' field-'.$field['key'] . $required_class . '">';
							echo '<label for="fields[' . $field['key'] . ']">' . $field['label'] . $required_label . '</label>';	
							$field['name'] = 'fields[' . $field['key'] . ']';
							$this->acf->create_field($field);
							if($field['instructions']) echo '<p class="description">' . $field['instructions'] . '</p>';
						echo '</div>';
					}
					else
					{
						echo '<tr id="acf-' . $field['name'] . '" class="form-field field field-' . $field['type'] . ' field-'.$field['key'] . $required_class . '">';
							echo '<th valign="top" scope="row"><label for="fields[' . $field['key'] . ']">' . $field['label'] . $required_label . '</label></th>';	
							echo '<td>';
								$field['name'] = 'fields[' . $field['key'] . ']';
								$this->acf->create_field($field);
								
								if($field['instructions']) echo '<p class="description">' . $field['instructions'] . '</p>';
							echo '</td>';
						echo '</tr>';

					}
					
										
				}
				// foreach($fields as $field)
				
				
				// footer
				if( $options['page_action'] == "edit" && $options['page_type'] != "media")
				{
					echo '</table>';
				}
				elseif( $options['page_type'] == 'shopp_category' )
				{
					echo '</div></div>';
				}
			}
			// foreach($field_groups as $field_group)
		}
		// if($field_groups)
		
		// exit for ajax
		die();

	}
	
			
}

?>