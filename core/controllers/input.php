<?php 

/*
*  Input
*
*  @description: All the functionality for adding fields to a page / post
*  @since 3.2.6
*  @created: 23/06/12
*/

 
class acf_input
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
		$this->acf = $acf;  //wdh changed from $parent - better naming
		
		
		// actions
		add_action('admin_print_scripts', array($this,'admin_print_scripts'));
		add_action('admin_print_styles', array($this,'admin_print_styles'));
		add_action('admin_head', array($this,'admin_head'));
		
		
		// save
		$save_priority = 20;
		
		if( isset($_POST['post_type']) )
		{
			if( $_POST['post_type'] == "tribe_events" ){ $save_priority = 15; }
		}
		add_action('save_post', array($this, 'save_post'), $save_priority); // save later to avoid issues with 3rd party plugins
		
		
		// custom actions (added in 3.1.8)
		add_action('acf_head-input', array($this, 'acf_head_input'));
		add_action('acf_print_scripts-input', array($this, 'acf_print_scripts_input'));
		add_action('acf_print_styles-input', array($this, 'acf_print_styles_input'));
		add_action('wp_restore_post_revision', array($this, 'wp_restore_post_revision'), 10, 2 );
		add_filter('_wp_post_revision_fields', array($this, 'wp_post_revision_fields') );
		
		// ajax
		add_action('wp_ajax_acf_input', array($this, 'ajax_acf_input'));
		add_action('wp_ajax_get_input_style', array($this, 'ajax_get_input_style'));
		
		
		// edit attachment hooks (used by image / file / gallery)
		add_action('admin_head-media.php', array($this, 'admin_head_media'));
		add_action('admin_head-upload.php', array($this, 'admin_head_upload'));
	}
	
	
	/*
	*  validate_page
	*
	*  @description: returns true | false. Used to stop a function from continuing
	*  @since 3.2.6
	*  @created: 23/06/12
	*/
	
	function validate_page()
	{
        phplog('input.php','*********************************************** validate_page' );

		// global
		global $pagenow, $typenow;


		// vars
		$return = false;


		// validate page
		if( in_array( $pagenow, array('post.php', 'post-new.php') ) )
		{

			// validate post type
			global $typenow;

			if( $typenow != "acf" )
			{
				$return = true;
			}

		}


		// validate page (Shopp)
		if( $pagenow == "admin.php" && isset( $_GET['page'] ) && $_GET['page'] == "shopp-products" && isset( $_GET['id'] ) )
		{
			$return = true;
		}


		// return
		return $return;
	}
	
	
	/*
	*  admin_print_scripts
	*
	*  @description: 
	*  @since 3.1.8
	*  @created: 23/06/12
	*/
	
	function admin_print_scripts()
	{
        phplog('input.php','*********************************************** admin_print_scripts' );

		// validate page
//		if( ! $this->validate_page() ) return;
		
		
		do_action('acf_print_scripts-input');
		
		
		// only "edit post" input pages need the ajax
		wp_enqueue_script(array(
			'acf-input-ajax',	
		));
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
        phplog('input.php','*********************************************** admin_print_styles' );

		// validate page
//		if( ! $this->validate_page() ) return;
		
		do_action('acf_print_styles-input');
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
        phplog('input.php','*********************************************** admin_head' );

		// validate page
//		if( ! $this->validate_page() ) return;
		
		
		// globals
		global $post, $pagenow, $typenow;
		
		
		// shopp
		if( $pagenow == "admin.php" && isset( $_GET['page'] ) && $_GET['page'] == "shopp-products" && isset( $_GET['id'] ) )
		{
            $post_type = "shopp_product";
		}
        else
        {
            $post_type = $typenow;
        }


        $this->render_meta_boxes( $post, $post_type  );


	}
    /*--------------------------------------------------------------------------------------
    *  render_meta_boxes
    *
    *  @description:
    *  @since
    *  @author: Wayne D Harris adapted from Elliot Condon
    *-------------------------------------------------------------------------------------*/
    function render_meta_boxes( $post, $post_type  )
    {
//        phplog('input.php',' $post = ',$post );

        $post_id = ($post) ? $post->ID : 0;

        // get style for page
        $show_field_group_ids = $this->acf->get_input_metabox_ids( array( 'post_id' => $post_id, 'post_type' => $post_type ), false);

        $style = isset($show_field_group_ids[0]) ? $this->get_input_style($show_field_group_ids[0]) : '';

        echo '<style type="text/css" id="acf_style" >' .$style . '</style>';


        // Style
        echo '<style type="text/css">.acf_postbox, .postbox[id*="acf_"] { display: none; }</style>';


        // Javascript
        echo '<script type="text/javascript">acf.post_id = ' . $post_id . '; acf.nonce = "' . wp_create_nonce( 'acf_nonce' ) . '";</script>';


        // add user js + css
        do_action('acf_head-input');


        // wdh : ?? is there any reason we need to display hidden metaboxes here ??
        // wdh : simplified down to just displaying relevant metaboxes for this page/post

        foreach($show_field_group_ids as $field_group_id)
        {
            $field_group = $this->acf->get_acf_field_group($field_group_id);

            // $field_group_values_key for native acf is set as $field_group['name'].
            // But note there is scope to dynamically add_acf_meta_boxes with unique $field_group_values_key
            $this->add_acf_meta_box( $field_group, $field_group['name'], $post_type );
        }

//        // get acf's
//        $field_groups = $this->acf->get_field_groups(); //wdh : changed '$acfs' to '$field_group' - better naming
//
//        if($field_groups)
//        {
//            foreach($field_groups as $field_group)
//            {
//                // hide / show
//                $show = in_array($field_group['id'], $show_field_group_ids) ? 1 : 0;
//
//                $this->add_acf_meta_box( $field_group, $field_group['name'], $post_type, $show );
//
//            }
//        }

    }
    /*--------------------------------------------------------------------------------------
    *  add_acf_meta_box
    *
    *  @description:
    *  @since
    *  @author: Wayne D Harris from Elliot Condon
    *-------------------------------------------------------------------------------------*/
    function add_acf_meta_box( $field_group, $field_group_values_key=null, $screen=null, $show=true )
    {
        global $post, $typenow;

        // wdh : if no unique id for the field_group_values is set use the field_group_name which works for acf natively
        if (!isset($field_group_values_key))
        {
            $field_group['name'];
        }

        if ( !isset($screen) )
        {
            $screen = $typenow;
        }

        $priority = ($field_group['options']['position']=='side') ? 'core' : 'high';

        // add meta box
        add_meta_box(
            // wdh : ** note: metabox id must start with 'acf_' for input-actions.js
            'acf_'.$field_group['name'],       //metabox id : wdh : replaced 'acf_'.$field_group['id'],
            __( $field_group['title'], 'acf' ), //metabox title : wdh : added localisation
            array($this, 'meta_box_input'),
            $screen,
            $field_group['options']['position'],
            $priority,
            array(
                'field_group_post_id'   => $field_group['id'],      // wdh: added
                'field_group_post_name' => $field_group['name'],    // wdh: added
                'fields'                => $field_group['fields'],
                'options'               => $field_group['options'],
                'field_group_values_key'=> $field_group_values_key, // wdh: added
                'show'                  => $show,
                'post_id'               => $post->ID,
            )
	    );
    }
    /*--------------------------------------------------------------------------------------
    *  meta_box_input
    *
    *
    *  @description: add_acf_meta_box callback
    *  @since 1.0.0
    *  @created: 23/06/12
    *-------------------------------------------------------------------------------------*/

    function meta_box_input($post, $metabox)
    {
        // vars
        $options = array(
            'fields' => array(),
            'options' => array(
                'layout'	=>	'default'
            ),
            'show' => 0,
            'post_id' => 0,
        );
        $options = array_merge( $options, $metabox['args'] );

        // needs fields
        if( $options['fields'] )
        {
            echo '<input type="hidden" name="save_input" value="true" />';

            // ***********************************
            // wdh : additional hidden data sent in $_POST via name/value pairs
            // allow data from this metabox to be posted to a different post than its acf

            $field_group_values_key = $options['field_group_values_key'];

            echo '<input type="hidden" name="field_group_values_target_post_id['.$field_group_values_key.']" value="'.$options['post_id'].'"/>';

            // save the field_group_post_name/id the metabox is using
            echo '<input type="hidden" name="field_group_post_id['.$field_group_values_key.']" value="'.$options['field_group_post_id'].'"/>';
            echo '<input type="hidden" name="field_group_post_name['.$field_group_values_key.']" value="'.$options['field_group_post_name'].'"/>';

            // ***********************************

            echo '<div class="options" data-layout="' . $options['options']['layout'] . '" data-show="' . $options['show'] . '" style="display:none"></div>';

            if( $options['show'] )
            {
                // ***********************************
                // wdh :  pass additional params

                // wdh: removed
//              $this->acf->render_fields_for_input( $options['fields'], $options['post_id'] );

                // wdh: added
                $this->acf->render_fields_for_input( $options['fields'], $options['post_id'], $field_group_values_key, $options['field_group_post_id'] );

                // ***********************************
            }
            else
            {
                echo '<div class="acf-replace-with-fields"><div class="acf-loading"></div></div>';
            }

        }
    }
    /*--------------------------------------------------------------------------------------
    *  get_input_style
    *
    *  @description: called by admin_head to generate acf css style (hide other metaboxes)
    *  @since 2.0.5
    *  @rewrite: 05/01/13 by wdh
    *  @author: Wayne D Harris / Elliot Condon
    *-------------------------------------------------------------------------------------*/
    function get_input_style( $field_group_id = false )
	{
        // vars
        $field_groups = $this->acf->get_field_groups();
        $html = "";

        // find acf
		if($field_groups)
		{
			foreach($field_groups as $field_group)
			{
                if($field_group['id'] != $field_group_id) { continue; }

                $options_hide_array = $field_group['options']['hide_on_screen'];

                $html .= $this->maybe_hide_metabox_style( $options_hide_array,  'the_content',      'postdivrich'       );
                $html .= $this->maybe_hide_metabox_style( $options_hide_array,  'excerpt',          'postexcerpt'       );
                $html .= $this->maybe_hide_metabox_style( $options_hide_array,  'custom_fields',    'postcustom'        );
                $html .= $this->maybe_hide_metabox_style( $options_hide_array,  'discussion',       'commentstatusdiv'  );
                $html .= $this->maybe_hide_metabox_style( $options_hide_array,  'comments',         'commentsdiv'       );
                $html .= $this->maybe_hide_metabox_style( $options_hide_array,  'slug',             'slugdiv'           );
                $html .= $this->maybe_hide_metabox_style( $options_hide_array,  'author',           'authordiv'         );
                $html .= $this->maybe_hide_metabox_style( $options_hide_array,  'format',           'formatdiv'         );
                $html .= $this->maybe_hide_metabox_style( $options_hide_array,  'featured_image',   'postimagediv'      );
                $html .= $this->maybe_hide_metabox_style( $options_hide_array,  'revisions',        'revisionsdiv'      );
                $html .= $this->maybe_hide_metabox_style( $options_hide_array,  'categories',       'categorydiv'       );
                $html .= $this->maybe_hide_metabox_style( $options_hide_array,  'tags',             'tagsdiv-post_tag'  );
                $html .= $this->maybe_hide_metabox_style( $options_hide_array,  'send-trackbacks',  'trackbacksdiv'     );
            }
        }
        return $html;
    }
    /*--------------------------------------------------------------------------------------
    *  maybe_hide_metabox_style
    *
    *  @description: hide metabox style
    *  @since 2.0.5
    *  @rewrite: 05/01/13 by wdh
    *  @author: Wayne D Harris / Elliot Condon
    *-------------------------------------------------------------------------------------*/
    function maybe_hide_metabox_style( $options_hide_array, $style_key, $style_name )
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
			echo $this->get_input_style($_POST['acf_id']);
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


    /*
	*  save_post
	*
	*  @description: Saves the field / location / option data for a field group
	*  @since 1.0.0
	*  @created: 23/06/12
	*/

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


    /*
	*  save_post_revision
	*
	*  @description: simple copy and paste of fields
	*  @since 3.4.4
	*  @created: 4/09/12
	*/

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
	
	
	/*
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
	
	
	/*
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
	
	
	/*
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
	
	
	/*
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

//    function get_input_style($field_group_id = false)
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
//                if($field_group['id'] != $field_group_id) continue;
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

	
}

?>