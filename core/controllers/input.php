<?php 

/*
*  Input
*
*  @description:
*  @since 3.2.6
*  @recreated: 23/01/13
*/



class acf_input
{

	var $acf,
		$data,
        $screen;


    /*=========================================================================================
    *  __construct
    *
    *  @description:
    *  @since 3.1.8
    *  @created: 23/06/12
    *=========================================================================================*/
	
	function __construct($acf)
	{
		// vars
		$this->acf = $acf;

        $this->screen = $this->acf->screen;

		// actions
		add_action( 'admin_print_scripts',      array($this,'admin_print_scripts')              );
		add_action( 'admin_print_styles',       array($this,'admin_print_styles')               );
		add_action( 'admin_head',               array($this,'admin_head')                       );

		// save
		$save_priority = 20;
		
		if( isset($_POST['post_type']) )
		{
			if( $_POST['post_type'] == "tribe_events" ){ $save_priority = 15; }
		}

		add_action( 'save_post', array($this, 'save_post'), $save_priority); // save later to avoid issues with 3rd party plugins
		
		
		// custom actions (added in 3.1.8)
		add_action( 'acf_head-input',           array($this, 'acf_head_input')                  );

		add_action( 'acf_print_scripts-input',  array($this, 'acf_print_scripts_input')         );
		add_action( 'acf_print_styles-input',   array($this, 'acf_print_styles_input')          );
		add_action( 'wp_restore_post_revision', array($this, 'wp_restore_post_revision'), 10, 2 );
		add_filter( '_wp_post_revision_fields', array($this, 'wp_post_revision_fields')         );
		
		// ajax
		add_action( 'wp_ajax_acf_input',        array($this, 'ajax_acf_input')                  );
		add_action( 'wp_ajax_get_input_style',  array($this, 'ajax_get_input_style')            );

        add_action( 'wp_ajax_render_field_groups_for_input', array($this, 'render_field_groups_for_input'));
		
		
		// edit attachment hooks (used by image / file / gallery)
		add_action( 'admin_head-media.php',     array($this, 'admin_head_media')                );
		add_action( 'admin_head-upload.php',    array($this, 'admin_head_upload')               );
	}

    /*--------------------------------------------------------------------------------------
    *  admin_print_scripts
    *
    *  @description:
    *  @since 3.1.8
    *  @created: 23/06/12
    *-------------------------------------------------------------------------------------*/
	
	function admin_print_scripts()
	{
        phplog('input.php','*********************************************** admin_print_scripts' );

		do_action('acf_print_scripts-input');

		// only "edit post" input pages need the ajax
		wp_enqueue_script(array(
			'acf-input-ajax',	
		));
	}


    /*--------------------------------------------------------------------------------------
    *  admin_print_styles
    *
    *  @description:
    *  @since 3.1.8
    *  @created: 23/06/12
    *-------------------------------------------------------------------------------------*/
	
	function admin_print_styles()
	{
        phplog('input.php','*********************************************** admin_print_styles' );

		do_action('acf_print_styles-input');
	}


    /*--------------------------------------------------------------------------------------
    *  admin_head
    *
    *  @description:
    *  @since 3.1.8
    *  @created: 23/06/12
    *-------------------------------------------------------------------------------------*/
	
	function admin_head()
	{
        phplog('input.php','*********************************************** admin_head' );

		global $post, $pagenow, $typenow;


        $post_type = $typenow;
		// shopp
		if( $pagenow == "admin.php" && isset( $_GET['page'] ) && $_GET['page'] == "shopp-products" && isset( $_GET['id'] ) )
		{
            $post_type = "shopp_product";
		}


        // decide here how we call render_field_groups_for_input...
        
        switch (true)
        {
            //....................................................................
            // page/post/custom-post/ media ??? -> metabox callback
            case $this->screen->is_options_screen():

                $this->screen->set_value('acf_layout','div');
                $this->render_field_groups_for_input();
                break;

            //....................................................................
            case $this->screen->is_base_post_screen():

                $this->screen->set_value('acf_layout','metabox');

//              $style = isset($screen_field_group_ids[0]) ? $this->show_on_screen($screen_field_group_ids[0]) : '';
//              echo '<style type="text/css" id="acf_style" >' .$style . '</style>';

               /* echo '<style type="text/css">.acf_postbox, .postbox[id*="acf_"] { display: none; }</style>';*/

                // Create nonce for page/posts
                echo '<script type="text/javascript">acf.post_id = ' . $post->ID . '; acf.nonce = "' . wp_create_nonce( 'acf_nonce' ) . '";</script>';

                // add user js + css
                do_action('acf_head-input');

                $this->render_field_groups_for_input();

                break;


            //....................................................................
            // taxonomy edit -> $taxonomy_name.'_add_form_fields' hook
            case $this->screen->is_taxonomy_add_screen():

                $this->screen->set_value('acf_layout','div');
                add_action( $this->screen->get_taxonomy().'_add_form_fields', array($this,'render_field_groups_for_input'));
                break;


            //....................................................................
            // taxonomy edit -> $taxonomy_name.'_edit_form_fields' hook
            case $this->screen->is_taxonomy_edit_screen():

                $this->screen->set_value('acf_layout','table');
                add_action( $this->screen->get_taxonomy().'_edit_form_fields', array($this,'render_field_groups_for_input'));
                break;


            //....................................................................
            // user add ->  js / ajax (no hook unfortunately)
            case $this->screen->is_user_add_screen():

                $this->screen->set_value('acf_layout','table');

                $this->render_user_add_fields_via_ajax();
                break;


            //....................................................................
            // user edit -> 'show_user_profile' hook
            case $this->screen->is_user_edit_screen():

                $this->screen->set_value('acf_layout','table');
                add_action('show_user_profile', array($this,'render_field_groups_for_input'));
                break;

            //....................................................................
            // shopp??

            default:

        }

	}
    /*--------------------------------------------------------------------------------------
    *  render_field_groups_for_input
    *
    *  @description:
    *  @since
    *  @author: Wayne D Harris adapted from Elliot Condon
    *-------------------------------------------------------------------------------------*/
    function render_field_groups_for_input()
    {
        global $post;

        $post_id = ($post) ? $post->ID : 0;

        // get field groups
//        $filter = array(
//            'post_id' => $post_id,
//            'post_type' => $this->screen->get_post_type()
//        );

        $screen_field_group_ids = $this->acf->location->get_screen_field_group_ids( $this->screen->get_screen() );


        foreach( $screen_field_group_ids as $field_group_post_id)
        {
            $field_group = $this->acf->get_acf_field_group($field_group_post_id);

            if( $this->screen->is_base_post_screen() )
            {
                // create metabox
                // $field_group_values_key for native acf is set as $field_group['name'].
                // * but note there is scope to dynamically add_field_group_meta_boxes with unique $field_group_values_key
                $this->add_field_group_meta_box( $field_group_post_id, null ); // todo !!!!
            }
            else
            {
                $this->render_field_group_input( $field_group, $field_group['name'], $post_id );
            }
        }

        // exit for ajax
        die();
    }
    /*--------------------------------------------------------------------------------------
    *  add_field_group_meta_box
    *
    *  @description:
    *  @since
    *  @author: Wayne D Harris adapted from Elliot Condon
    *-------------------------------------------------------------------------------------*/
    function add_field_group_meta_box( $field_group_post_key, $field_group_values_key=null, $screen=null, $show=true )
    {
        global $post;

        $field_group_post_id = (is_numeric($field_group_post_key)) ? $field_group_post_key : $this->acf->get_post_id_via_post_name($field_group_post_key);

        $field_group = $this->acf->get_acf_field_group($field_group_post_id);

        // if no unique id for the field_group_values is set use the field_group_name which works for acf natively
        if (!isset($field_group_values_key))
        {
            $field_group_values_key = $field_group['name'];
        }

        $priority = ($field_group['options']['position']=='side') ? 'core' : 'high';

        // add meta box
        add_meta_box(
            // wdh : ** note: metabox id must start with 'acf_' for input-actions.js
            'acf_'.$field_group['name'],        //metabox id : wdh : replaced 'acf_'.$field_group['id'],
            __( $field_group['title'], 'acf' ), //metabox title : wdh : added localisation
            array($this, 'add_field_group_meta_box_callback'),
            $screen,
            $field_group['options']['position'],
            $priority,
            array(
                'field_group'   => $field_group,      // wdh: added
//                'field_group_post_name' => $field_group['name'],    // wdh: added
//                'fields'                => $field_group['fields'],
//                'options'               => $field_group['options'],
                'field_group_values_key'=> $field_group_values_key, // wdh: added
                'show'                  => $show,
//                'post_id'               => $post->ID,  // todo check this
            )
	    );
    }
    /*--------------------------------------------------------------------------------------
    *  add_field_group_meta_box
    *
    *  @description:
    *  @since 1.0.0
    *  @created: 23/06/12
    *-------------------------------------------------------------------------------------*/

    function add_field_group_meta_box_callback( $post, $metabox )
    {
        $field_group_values_key = $metabox['args']['field_group_values_key'];
        $field_group            = $metabox['args']['field_group'];

        $this->render_field_group_input( $field_group, $field_group_values_key, $post->ID );
    }

    /*--------------------------------------------------------------------------------------
    *  render_field_group_input
    *
    *  @description:
    *  @since 1.0.0
    *  @created: 23/06/12
    *-------------------------------------------------------------------------------------*/

    function render_field_group_input( $field_group, $field_group_values_key=null, $primary_key_id=null )
    {
//        $field_group = $this->acf->get_acf_field_group($field_group_post_id);


        $page_type      = $this->screen->get_screen_type();
        $layout         = $this->screen->get_value('acf_layout');
        $metabox_layout = $field_group['options']['layout'];
        $show           = true;//$field_group['options']['show'];

        $title = (is_numeric( $field_group['id'])) ? get_the_title( $field_group['id'] ) : apply_filters( 'the_title', $field_group['title'] );

        if(!isset($field_group_values_key)) { $field_group_values_key = $field_group['name']; }

        $field_group_post_name  = $field_group['name'];
        $field_group_post_id    = $field_group['id'];
//        $primary_key_id         = $options['primary_key_id'];


        echo '<input type="hidden" name="save_input" value="true" />';

        echo '<input type="hidden" name="field_group_values_target_post_id['.$field_group_values_key.']" value="'.$primary_key_id.'"/>';

        // save the field_group_post_name/id the metabox is using
        echo '<input type="hidden" name="field_group_post_id['.$field_group_values_key.']" value="'.$field_group_post_id.'"/>';
        echo '<input type="hidden" name="field_group_post_name['.$field_group_values_key.']" value="'.$field_group_post_name.'"/>';


        //  acf->render_fields_for_input() >>>>>>>>>>>>>>>>>

        $field_group_values = $this->acf->get_field_group_values( $primary_key_id, $field_group_values_key, $field_group['id'], $page_type );

        // filter, set defaults and clean
        $field_config_value_pair = $this->acf->map_field_config_to_value( $field_group['fields'], $field_group_values['field_values'], ACF_LOAD_VALUE_, ACF_LOAD_FIELD_ );

        //set caches of load value filtered results - we'll read from these now until an update
        $field_group_values['field_values'] = $field_config_value_pair['value'];
        $this->acf->set_cache( 'acf_field_group_values_'.$field_group_values_key, $field_group_values );


        // title
//        if( $options['page_action'] == "edit" && !in_array($page_type, array('media', 'shopp_category')) )
        if ($layout=='table')
        {
            echo '<h3>' .$title . '</h3>';
            echo '<table class="form-table">';
        }
        elseif( $layout=='metabox' )
        {
//            echo '<div class="postbox acf_postbox" id="acf_'. $field_group['id'] .'">';
//            echo '<div title="Click to toggle" class="handlediv"><br></div><h3 class="hndle"><span>' . $title . '</span></h3>';
//            echo '<div class="inside">';
        }


        // render
        foreach($field_group['fields'] as $field)
        {
            // no type - skip this field
            if(!$field['type'] || $field['type'] == 'null') { continue; }

            $required_class = ($field['required']) ? ' required' : '';
            $required_label = ($field['required']) ? ' <span class="required">*</span>' : '';

            // wdh : set fields and $vars to make more readable in rendering code
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
                echo '<div class="options" data-layout="'.$metabox_layout.'" data-show="'.$show.'" style="display:none"></div>';

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

        // close tags
//      if( $options['page_action'] == "edit" && $page_type != "media")
        if ($layout=='table')
        {
            echo '</table>';
        }
//      elseif( $page_type == 'shopp_category' )
        elseif ($layout=='div')
        {
            echo '</div></div>';
        }
    }


    //----------------------------------------------------------------------
    function render_field_groups_for_input_test()
    {
        echo 'fffffffffffffuuuuuuuuuuuuuucccccccccccccccccckkkkkkkkkkkk';

        // exit for ajax
        die();
    }
    /*--------------------------------------------------------------------------------------
	*
	*	admin_head
	*
	*	@author Elliot Condon
	*	@since 3.1.8
	*
	*-------------------------------------------------------------------------------------*/

    function render_user_add_fields_via_ajax()
    {
        // add user js + css
        do_action('acf_head-input');

        ?>
    <script type="text/javascript">
        (function($){

//            acf.data = {
//                action : 'render_field_groups_for_input_test'
//            };

            $(document).ready(function(){

                $.ajax({
                    url: ajaxurl,
                    data: {
                        action : 'render_field_groups_for_input'
                    },
                    type: 'post',
                    dataType: 'html',
                    success: function(html){

                        <?php

                        echo "$('#createuser > table.form-table > tbody').append( html );";

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
    *  show_on_screen
    *
    *  @description: called by admin_head to generate acf css style (hide other metaboxes)
    *  @since 2.0.5
    *  @rewrite: 05/01/13 by wdh
    *  @author: Wayne D Harris / Elliot Condon
    *-------------------------------------------------------------------------------------*/
    function show_on_screen( $field_group_post_id = false )
	{
        // vars
        $field_groups = $this->acf->get_field_groups();
        $html = "";

        // find acf
		if($field_groups)
		{
			foreach($field_groups as $field_group)
			{
                if($field_group['id'] != $field_group_post_id) { continue; }

                $options_hide_array = $field_group['options']['hide_on_screen'];

                $html .= $this->maybe_hide_screen_style( $options_hide_array,  'the_content',      'postdivrich'       );
                $html .= $this->maybe_hide_screen_style( $options_hide_array,  'excerpt',          'postexcerpt'       );
                $html .= $this->maybe_hide_screen_style( $options_hide_array,  'custom_fields',    'postcustom'        );
                $html .= $this->maybe_hide_screen_style( $options_hide_array,  'discussion',       'commentstatusdiv'  );
                $html .= $this->maybe_hide_screen_style( $options_hide_array,  'comments',         'commentsdiv'       );
                $html .= $this->maybe_hide_screen_style( $options_hide_array,  'slug',             'slugdiv'           );
                $html .= $this->maybe_hide_screen_style( $options_hide_array,  'author',           'authordiv'         );
                $html .= $this->maybe_hide_screen_style( $options_hide_array,  'format',           'formatdiv'         );
                $html .= $this->maybe_hide_screen_style( $options_hide_array,  'featured_image',   'postimagediv'      );
                $html .= $this->maybe_hide_screen_style( $options_hide_array,  'revisions',        'revisionsdiv'      );
                $html .= $this->maybe_hide_screen_style( $options_hide_array,  'categories',       'categorydiv'       );
                $html .= $this->maybe_hide_screen_style( $options_hide_array,  'tags',             'tagsdiv-post_tag'  );
                $html .= $this->maybe_hide_screen_style( $options_hide_array,  'send-trackbacks',  'trackbacksdiv'     );
            }
        }
        return $html;
    }
    /*--------------------------------------------------------------------------------------
    *  maybe_hide_screen_style
    *
    *  @description: hide metabox style
    *  @since 2.0.5
    *  @rewrite: 05/01/13 by wdh
    *  @author: Wayne D Harris / Elliot Condon
    *-------------------------------------------------------------------------------------*/
    function maybe_hide_screen_style( $options_hide_array, $style_key, $style_name )
	{
        if( in_array( $style_key, $options_hide_array ) )
        {
            if($style_key=='the_content')
            {
                return '#'.$style_name.' {display: none;} ';
            }
            else
            {
                return '#'.$style_name.', #screen-meta label[for='.$style_name.'-hide] {display: none;} ';
            }
        }
    }


    /*--------------------------------------------------------------------------------------
    *  the_input_style
    *
    *  @description: called by input-actions.js to hide / show other metaboxes
    *  @since 2.0.5
    *  @created: 23/06/12
    *-------------------------------------------------------------------------------------*/
	
	function ajax_get_input_style()
	{
		// overrides
		if(isset($_POST['acf_id']))
		{
			echo $this->show_on_screen($_POST['acf_id']);
		}
		
		die;
	}

    /*--------------------------------------------------------------------------------------
    *  ajax_acf_input
    *
    *  @description:
    *  @since 3.1.6
    *  @created: 23/06/12
    *-------------------------------------------------------------------------------------*/

	function ajax_acf_input()
	{
        phplog('input.php','??????????????????????????????????  render_fields_for_input !!!!!' );


		// defaults
		$defaults = array(
			'acf_id' => null,
			'post_id' => null,
		);
		
		// load post options
		$options = array_merge($defaults, $_POST);
		
		// required
		if(!$options['acf_id'] || !$options['post_id'])
		{
			echo "";
			die();
		}

        phplog('input.php','$options=',$options);

		// get acfs
		$field_groups = $this->acf->get_field_groups();
		if( $field_groups )
		{
			foreach( $field_groups as $field_group )
			{
				if( $field_group['id'] == $options['acf_id'] )
				{
                    //******************************
                    // wdh : removed
//					$this->acf->render_fields_for_input( $field_group['fields'], $options['post_id']);
                    // wdh : added : todo test
                    $this->acf->render_fields_for_input( $options['fields'], $options['post_id'], $options['field_group_values_key'], $options['field_group_post_id'] );

                    //******************************

					break;
				}
			}
		}

		die();
		
	}


    /*--------------------------------------------------------------------------------------
	*  save_post
	*
	*  @description: Saves the field / location / option data for a field group
	*  @since 1.0.0
	*  @created: 23/06/12
	*-------------------------------------------------------------------------------------*/

    function save_post($post_id)
    {
        // do not save if this is an auto save routine
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return $post_id;

        // only for save acf
        if( ! isset($_POST['save_input']) || $_POST['save_input'] != 'true')
        {
            return $post_id;
        }

        // Save revision (copy and paste of current metadata. ie: what it was)
        $parent_id = wp_is_post_revision( $post_id );
        if( $parent_id )
        {
            $this->save_post_revision( $parent_id, $post_id );
        }
        else
        {
            do_action('acf_save_post', $post_id);
        }
    }


    /*--------------------------------------------------------------------------------------
	*  save_post_revision
	*
	*  @description: simple copy and paste of fields
	*  @since 3.4.4
	*  @created: 4/09/12
	*-------------------------------------------------------------------------------------*/

    function save_post_revision( $parent_id, $revision_id )
    {
        // load from post
        if( !isset($_POST['fields']) )
        {
            return false;
        }
        // field data was posted. Find all values (not references) and copy / paste them over.

        global $wpdb;

        // get field from postmeta
        $rows = $wpdb->get_results( $wpdb->prepare(
            "SELECT meta_key, meta_value FROM $wpdb->postmeta WHERE post_id = %d AND meta_key NOT LIKE %s",
            $parent_id,
            '\_%'
        ), ARRAY_A);

        if( $rows )
        {
            foreach( $rows as $row )
            {
                $wpdb->insert(
                    $wpdb->postmeta,
                    array(
                        'post_id' => $revision_id,
                        'meta_key' => $row['meta_key'],
                        'meta_value' => $row['meta_value']
                    )
                );
            }
        }
        return true;
    }

	/*--------------------------------------------------------------------------------------
	*
	*	acf_head_input
	*
	*	This is fired from an action: acf_head-input
	*
	*	@author Elliot Condon
	*	@since 3.0.6
	* 
	*-------------------------------------------------------------------------------------*/
	
	function acf_head_input()
	{
		
		?>
<script type="text/javascript">

// admin url
acf.admin_url = "<?php echo admin_url(); ?>";
	
// messages
acf.text.validation_error = "<?php _e("Validation Failed. One or more fields below are required.",'acf'); ?>";
acf.text.file_tb_title_add = "<?php _e("Add File to Field",'acf'); ?>";
acf.text.file_tb_title_edit = "<?php _e("Edit File",'acf'); ?>";
acf.text.image_tb_title_add = "<?php _e("Add Image to Field",'acf'); ?>";
acf.text.image_tb_title_edit = "<?php _e("Edit Image",'acf'); ?>";
acf.text.relationship_max_alert = "<?php _e("Maximum values reached ( {max} values )",'acf'); ?>";
acf.text.gallery_tb_title_add = "<?php _e("Add Image to Gallery",'acf'); ?>";
acf.text.gallery_tb_title_edit = "<?php _e("Edit Image",'acf'); ?>";

</script>
		<?php
		
		foreach($this->acf->fields as $field)
		{
			$field->admin_head();
		}
	}
	
	
	/*--------------------------------------------------------------------------------------
	*
	*	acf_print_scripts
	*
	*	@author Elliot Condon
	*	@since 3.1.8
	* 
	*-------------------------------------------------------------------------------------*/
	
	function acf_print_scripts_input()
	{
		wp_enqueue_script(array(
			'jquery',
			'jquery-ui-core',
			'jquery-ui-tabs',
			'jquery-ui-sortable',
			'farbtastic',
			'thickbox',
			'media-upload',
			'acf-input-actions',
			'acf-datepicker',	
		));

		
		foreach($this->acf->fields as $field)
		{
			$field->admin_print_scripts();
		}
	}
	
	
	/*--------------------------------------------------------------------------------------
	*
	*	acf_print_styles
	*
	*	@author Elliot Condon
	*	@since 3.1.8
	* 
	*-------------------------------------------------------------------------------------*/
	
	function acf_print_styles_input()
	{
		wp_enqueue_style(array(
			'thickbox',
			'farbtastic',
			'acf-global',
			'acf-input',
			'acf-datepicker',	
		));
		
		foreach($this->acf->fields as $field)
		{
			$field->admin_print_styles();
		}
	}


    /*--------------------------------------------------------------------------------------
    *  admin_head_upload
    *
    *  @description:
    *  @since 3.2.6
    *  @created: 3/07/12
    */
	
	function admin_head_upload()
	{
		// vars
		$defaults = array(
			'acf_action'	=>	null,
			'acf_field'		=>	'',
		);
		
		$options = array_merge($defaults, wp_parse_args( wp_get_referer() ));
		
		
		// validate
		if( $options['acf_action'] != 'edit_attachment')
		{
			return false;
		}
		
		
		// call the apropriate field action
		do_action('acf_head-update_attachment-' . $options['acf_field']);
		
		?>
<script type="text/javascript">

	// reset global
	self.parent.acf_edit_attachment = null;
	
	// remove tb
	self.parent.tb_remove();
	
</script>
</head>
<body>
	
	<div class="updated" id="message"><p><?php _e("Attachment updated",'acf'); ?>.</div>
	
</body>
</html
		<?php
		
		die;
	}


    /*--------------------------------------------------------------------------------------
    *  admin_head_media
    *
    *  @description:
    *  @since 3.2.6
    *  @created: 3/07/12
    */
	
	function admin_head_media()
	{

		// vars
		$defaults = array(
			'acf_action'	=>	null,
			'acf_field'		=>	'',
		);
		
		$options = array_merge($defaults, $_GET);
		
		
		// validate
		if( $options['acf_action'] != 'edit_attachment')
		{
			return false;
		}
		
		?>
<style type="text/css">
#wpadminbar,
#adminmenuback,
#adminmenuwrap,
#footer,
#media-single-form > .submit:first-child,
#media-single-form td.savesend,
.add-new-h2 {
	display: none;
}

#wpcontent {
	margin-left: 0px !important;
}

.wrap {
	margin: 20px 15px;
}

html.wp-toolbar {
    padding-top: 0px;
}
</style>
<script type="text/javascript">
(function($){
	
	$(document).ready( function(){
		
		$('#media-single-form').append('<input type="hidden" name="acf_action" value="<?php echo $options['acf_action']; ?>" />');
		$('#media-single-form').append('<input type="hidden" name="acf_field" value="<?php echo $options['acf_field']; ?>" />');
		
	});
		
})(jQuery);
</script>
		<?php
		
		do_action('acf_head-edit_attachment');
	}


    /*--------------------------------------------------------------------------------------
    *  wp_restore_post_revision
    *
    *  @description:
    *  @since 3.4.4
    *  @created: 4/09/12
    */
	
	function wp_restore_post_revision( $parent_id, $revision_id )
	{
		global $wpdb;
		
		
		// get field from postmeta
		$rows = $wpdb->get_results( $wpdb->prepare(
			"SELECT meta_key, meta_value FROM $wpdb->postmeta WHERE post_id = %d AND meta_key NOT LIKE %s", 
			$revision_id, 
			'\_%'
		), ARRAY_A);
		
		
		if( $rows )
		{
			foreach( $rows as $row )
			{
				update_post_meta( $parent_id, $row['meta_key'], $row['meta_value'] );
			}
		}
			
	}


    /*--------------------------------------------------------------------------------------
    *  wp_post_revision_fields
    *
    *  @description:
    *  @since 3.4.4
    *  @created: 4/09/12
    */
	
	function wp_post_revision_fields( $fields ) {
		
		global $post, $wpdb, $revision, $left_revision, $right_revision, $pagenow;
		
		
		if( $pagenow != "revision.php" )
		{
			return $fields;
		}
		
		
		// get field from postmeta
		$rows = $wpdb->get_results( $wpdb->prepare(
			"SELECT meta_key, meta_value FROM $wpdb->postmeta WHERE post_id = %d AND meta_key NOT LIKE %s", 
			$post->ID, 
			'\_%'
		), ARRAY_A);
		
		
		if( $rows )
		{
			foreach( $rows as $row )
			{
				$fields[ $row['meta_key'] ] =  ucwords( str_replace('_', ' ', $row['meta_key']) );


				// left vs right
				if( isset($_GET['left']) && isset($_GET['right']) )
				{
					$left_revision->$row['meta_key'] = get_metadata( 'post', $_GET['left'], $row['meta_key'], true );
					$right_revision->$row['meta_key'] = get_metadata( 'post', $_GET['right'], $row['meta_key'], true );
				}
				else
				{
					$revision->$row['meta_key'] = get_metadata( 'post', $revision->ID, $row['meta_key'], true );
				}
				
			}
		}
		
		
		return $fields;
	
	}

    /*
    *  get_input_style
    *
    *  @description: called by admin_head to generate acf css style (hide other metaboxes)
    *  @since 2.0.5
    *  @created: 23/06/12
    */

//    function get_input_style($field_group_post_id = false)
//    {
//        // vars
//        $field_groups = $this->parent->get_field_groups();
//        $html = "";
//
//        // find acf
//        if($field_groups)
//        {
//            foreach($field_groups as $field_group)
//            {
//                if($field_group['id'] != $field_group_post_id) continue;
//
//
//                // add style to html
//                if( in_array('the_content',$field_group['options']['hide_on_screen']) )
//                {
//                    $html .= '#postdivrich {display: none;} ';
//                }
//                if( in_array('excerpt',$field_group['options']['hide_on_screen']) )
//                {
//                    $html .= '#postexcerpt, #screen-meta label[for=postexcerpt-hide] {display: none;} ';
//                }
//                if( in_array('custom_fields',$field_group['options']['hide_on_screen']) )
//                {
//                    $html .= '#postcustom, #screen-meta label[for=postcustom-hide] { display: none; } ';
//                }
//                if( in_array('discussion',$field_group['options']['hide_on_screen']) )
//                {
//                    $html .= '#commentstatusdiv, #screen-meta label[for=commentstatusdiv-hide] {display: none;} ';
//                }
//                if( in_array('comments',$field_group['options']['hide_on_screen']) )
//                {
//                    $html .= '#commentsdiv, #screen-meta label[for=commentsdiv-hide] {display: none;} ';
//                }
//                if( in_array('slug',$field_group['options']['hide_on_screen']) )
//                {
//                    $html .= '#slugdiv, #screen-meta label[for=slugdiv-hide] {display: none;} ';
//                }
//                if( in_array('author',$field_group['options']['hide_on_screen']) )
//                {
//                    $html .= '#authordiv, #screen-meta label[for=authordiv-hide] {display: none;} ';
//                }
//                if( in_array('format',$field_group['options']['hide_on_screen']) )
//                {
//                    $html .= '#formatdiv, #screen-meta label[for=formatdiv-hide] {display: none;} ';
//                }
//                if( in_array('featured_image',$field_group['options']['hide_on_screen']) )
//                {
//                    $html .= '#postimagediv, #screen-meta label[for=postimagediv-hide] {display: none;} ';
//                }
//                if( in_array('revisions',$field_group['options']['hide_on_screen']) )
//                {
//                    $html .= '#revisionsdiv, #screen-meta label[for=revisionsdiv-hide] {display: none;} ';
//                }
//                if( in_array('categories',$field_group['options']['hide_on_screen']) )
//                {
//                    $html .= '#categorydiv, #screen-meta label[for=categorydiv-hide] {display: none;} ';
//                }
//                if( in_array('tags',$field_group['options']['hide_on_screen']) )
//                {
//                    $html .= '#tagsdiv-post_tag, #screen-meta label[for=tagsdiv-post_tag-hide] {display: none;} ';
//                }
//                if( in_array('send-trackbacks',$field_group['options']['hide_on_screen']) )
//                {
//                    $html .= '#trackbacksdiv, #screen-meta label[for=trackbacksdiv-hide] {display: none;} ';
//                }
//
//
//                break;
//
//            }
//            // foreach($field_groups as $field_group)
//        }
//        //if($field_groups)
//
//        return $html;
//    }


    /*--------------------------------------------------------------------------------------
    *  render_field_group_input
    *
    *  @description:
    *  @since 1.0.0
    *  @created: 23/06/12
    *-------------------------------------------------------------------------------------*/

//    function render_field_group_input( $field_group_values_key, $field_group_post_id, $primary_key_id )
//    {
//        // vars
//        $options = array(
//            'fields'    => array(),
//            'options'   => array( 'layout' =>	'default' ),
//            'show'      => 0,
//            'post_id'   => 0,
//        );
//        $options = array_merge( $options, $metabox['args'] );
//
//
//        // needs fields
//        if(!$field_group['fields']) { continue; }
//
//
//        // needs fields
//        if( $options['fields'] )
//        {
//            echo '<input type="hidden" name="save_input" value="true" />';
//
//            // ***********************************
//            // wdh : additional hidden data sent in $_POST via name/value pairs
//            // allow data from this metabox to be posted to a different post than its acf
//
//            $field_group_values_key = $options['field_group_values_key'];
//
//            echo '<input type="hidden" name="field_group_values_target_post_id['.$field_group_values_key.']" value="'.$options['post_id'].'"/>';
//
//            // save the field_group_post_name/id the metabox is using
//            echo '<input type="hidden" name="field_group_post_id['.$field_group_values_key.']" value="'.$options['field_group_post_id'].'"/>';
//            echo '<input type="hidden" name="field_group_post_name['.$field_group_values_key.']" value="'.$options['field_group_post_name'].'"/>';
//
//            // ***********************************
//
//
//
//            $this->acf->render_fields_for_input( $options['fields'], $options['post_id'], $field_group_values_key, $options['field_group_post_id'] );
//
//        }
//    }
//
//    /*--------------------------------------------------------------------------------------
//     *  render_fields_for_input
//     *
//     *  @description:
//     *  @since 3.1.6
//     *  @author Elliot Condon / Wayne D Harris
//     *-------------------------------------------------------------------------------------*/
    function render_fields_for_input( $fields, $post_id, $field_group_values_key, $field_group_post_id )
    {

        //filter field values
        $field_group_values = $this->get_field_group_values( $post_id, $field_group_values_key, $field_group_post_id );

        // filter, set defaults and clean
        $field_config_value_pair = $this->map_field_config_to_value( $fields, $field_group_values['field_values'], ACF_LOAD_VALUE_, ACF_LOAD_FIELD_ );

        //set caches of load value filtered results - we'll read from these now until an update
        $field_group_values['field_values'] = $field_config_value_pair['value'];
        $this->set_cache( 'acf_field_group_values_'.$field_group_values_key, $field_group_values );

        // * at this point we have set data as required only on read to be used for processing/display now but not in db until save post/update

        // create fields
        if($fields)
        {
            foreach($fields as $field)
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
                $field['value'] = $this->get_field_value( $field_group_values_key, array( $field_slug ), $post_id, false, $field_group_post_id );



                echo '<div class="options" data-layout="' . $options['options']['layout'] . '" data-show="' . $options['show'] . '" style="display:none"></div>';
                echo '<div id='.$field_wrapper_id.'" class="field field-'.$field_type.' field-'.$field_slug. $required_class. '"data-field_name="'.$field_slug.'" data-field_key="'.$field_key.'">';
                echo '<p class="label">';
                echo '<label for='.$field_id.'>'.$field_label.$required_label.'</label>';
                echo $field_instructions;
                echo '</p>';
                $this->create_field($field);
                echo '</div>';

            }
        }
    }

	
}

?>