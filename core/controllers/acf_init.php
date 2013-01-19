<?php 

class acf_init 
{

	var $acf;
	var $dir;
	var $data;
	
	/*--------------------------------------------------------------------------------------
	*
	*	acf_init
	*
	*	@author Wayne D Harris
	*	@since 3.1.8
	* 
	*-------------------------------------------------------------------------------------*/
	
	function __construct($acf)
	{
		// vars
		$this->acf = $acf;
		$this->dir = $acf->dir;

//		// data for passing variables
//		$this->data = array(
//			'page_id' => '', // a string used to load values
//			'metabox_ids' => array(),
//			'page_type' => '', // taxonomy / user / media
//			'page_action' => '', // add / edit
//			'option_name' => '', // key used to find value in wp_options table. eg: user_1, category_4
//		);
//
//		add_action('admin_menu', array($this,'admin_menu'));

        $page_type = $this->get_page_type();

        $context = sola_util_get_context();

	}

    /*--------------------------------------------------------------------------------------
    *  get_page_type
    *
    *  @description:
    *  @since
    *  @ Wayne D Harris from Elliot Condons validate_page fns
    *  @created: 19/01/13
    *-------------------------------------------------------------------------------------*/

    function get_page_type()
    {
        // global
        global $pagenow, $typenow;

        $page_type = array();


        // field groups ......................................................
        if (in_array( $pagenow, array('edit.php')) && ($_GET['post_type']=='acf') && isset($_GET['post_type']) && !isset($_GET['page']) )
        {
            $page_type[] = 'field_groups';
        }

        // field group ......................................................
        elseif (in_array( $pagenow, array('post.php', 'post-new.php')) && ($typenow=="acf")    )
        {
            $page_type[] = 'field_group';
        }

        // page or post ......................................................
        elseif (in_array( $pagenow, array('post.php', 'post-new.php')) && ($typenow!="acf")    )
        {
            $page_type[] = 'page_post';
        }

        // taxonomy ......................................................
        elseif( $pagenow == "edit-tags.php" && isset($_GET['taxonomy']) )
        {
            $page_type[] = 'taxonomy';
            $page_type['taxonomy'] = array();

            $page_type['taxonomy'][] =  (isset($_GET['action']) && ($_GET['action']=="edit") ) ? 'edit' : 'add';

        }

        // user ......................................................
        elseif( in_array( $pagenow, array( 'profile.php', 'user-new.php', 'user-edit.php' ) ) )
        {
            $page_type[] = 'user';
            $page_type['user'] = array();

            if( $pagenow == "profile.php" )
            {
                $page_type['user'][] = 'profile';
            }
            elseif( $pagenow == "user-edit.php" && isset($_GET['user_id']) )
            {
                $page_type['user'][] = 'edit';

            }
            elseif( $pagenow == "user-new.php" )
            {
                $page_type['user'][] = 'add';
            }
        }

        // media ......................................................
        elseif( $pagenow == "media.php" )
        {
            $page_type[] = 'media';

            $page_type['media'][] =  (isset($_GET['attachment_id'])) ? 'edit' : 'add';
        }


        // woocommerce ..................................................


        // .......


        // shopp  ......................................................
        elseif ($pagenow=="admin.php" && isset($_GET['page'], $_GET['id']) )
        {
            $page_type[] = 'shopp';
            $page_type['shopp'] = array();

            if($_GET['page']=="shopp-products")
            {
                $page_type['shopp'][] = 'products';
            }
            elseif($_GET['page']=="shopp-categories")
            {
                $page_type['shopp'][] = 'categories';
            }

        }
        return $page_type;
    }



	
			
}

?>