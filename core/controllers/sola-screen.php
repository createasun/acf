<?php 

/*

*/


class Acf_Screen
{
    var $wp_session,
        $screen;

	function __construct()
	{

        define( 'POST_TYPE',            'post_type'             );
        define( 'PARENT_TYPE',          'parent_type'           );
        define( 'POST_ID',              'post_id'               );
        define( 'USER_ID',              'user_id'               );

        define( 'TYPE',                 'type'                  );
        define( 'SUBTYPE',              'subtype'               );
        define( 'ACTION',               'action'                );
        define( 'PARENT',               'parent'                );

        define( 'ATTACHMENT',           'attachment'            );
        define( 'MEDIA',                'media'                 );
        define( 'TAXONOMY',             'taxonomy'              );
        define( 'USER',                 'user'                  );
        define( 'PROFILE',              'profile'               );
        define( 'ADMIN',                'admin'                 );


        define( 'EDIT',                 'edit'                  );
        define( 'ADD',                  'add'                   );


        $this->screen = array(
            BASE        => '',
            TYPE        => '',
            SUBTYPE     => '',
            ACTION      => '',
            TAXONOMY    => '',
            PARENT_TYPE => '',
            POST_ID     => -1,
            USER_ID     => -1,
        );

        $this->wp_session = WP_Session::get_instance();
        $this->screen = $this->wp_session['screen'];

	}


    //-------------------------------------------------------------------------------------*/
    function get_screen()
    {
        global $pagenow;

        // $pagenow can be 'admin-ajax.php on ajax calls and page onload data is incorrect
        // so using a session to store screen data

        if( $pagenow == 'admin-ajax.php' )
        {
            //use stored session screen data
            $this->screen = $this->wp_session['screen'];
        }
        else
        {
            if( !isset($this->screen) )
            {
                $this->screen = $this->get_current_screen();
                //store screen data in wp_session
                $this->wp_session['screen'] = $this->get_screen();
            }
        }

        return $this->screen;
    }
    //-------------------------------------------------------------------------------------*/
    function is_base_post_screen()
    {
        $screen = $this->get_screen();

        return ( $screen[BASE]==POST);
    }
    //-------------------------------------------------------------------------------------*/
    function is_user_edit_screen()
    {
        $screen = $this->get_screen();

        return ( $screen[TYPE]==USER && $screen[ACTION]==EDIT );

    }
    //-------------------------------------------------------------------------------------*/
    function is_user_add_screen()
    {
        $screen = $this->get_screen();

        return ( $screen[TYPE]==USER && $screen[ACTION]==ADD );

    }
    //-------------------------------------------------------------------------------------*/
    function is_taxonomy_add_screen()
    {
        $screen = $this->get_screen();

        return ( $screen[TYPE]==TAXONOMY && $screen[ACTION]==ADD );

    }
    //-------------------------------------------------------------------------------------*/
    function is_taxonomy_edit_screen()
    {
        $screen = $this->get_screen();

        return ( $screen[TYPE]==TAXONOMY && $screen[ACTION]==EDIT );

    }
    //-------------------------------------------------------------------------------------*/
    function is_options_screen()
    {
        return false;

    }
    //-------------------------------------------------------------------------------------*/
    function is_field_group_screen($page)
    {
        $screen = $this->get_screen();

        return ( $screen[TYPE]=='acf' && $screen[SUBTYPE]==$page);

    }
    //-------------------------------------------------------------------------------------*/
    function is_field_groups_screen()
    {
        $screen = $this->get_screen();

        return ( $screen[TYPE]=='acf' );

    }
    //-------------------------------------------------------------------------------------*/
    function get_taxonomy()
    {
        $screen = $this->get_screen();

        return $screen[TAXONOMY];

    }
    //-------------------------------------------------------------------------------------*/
    function get_screen_type()
    {
        $screen = $this->get_screen();

        return $screen[TYPE];

    }
    //-------------------------------------------------------------------------------------*/
//    function set_acf_layout($layout)
//    {
//        $this->screen['acf_layout'] = $layout;
//    }
//    //-------------------------------------------------------------------------------------*/
//    function get_acf_layout()
//    {
//        return $this->screen['acf_layout'];
//    }
    //-------------------------------------------------------------------------------------*/
    function set_screen_value( $key, $value )
    {
        $this->screen[$key] = $value;
    }
    //-------------------------------------------------------------------------------------*/
    function get_screen_value( $key )
    {
        return $this->screen[$key];
    }

    /*--------------------------------------------------------------------------------------
    *  get_screen
    *
    *  @description:
    *  @since
    *  @ Wayne D Harris
    *  @created: 19/01/13
    *-------------------------------------------------------------------------------------*/
    function get_current_screen()
    {

        $screen = array(
            BASE        => '',
            TYPE        => '',
            SUBTYPE     => '',
            ACTION      => '',
            TAXONOMY    => '',
            PARENT_TYPE => '',
            POST_ID     => -1,
            USER_ID     => -1,

        );
        //-----------------------------------------------------
        // posts / pages / custom-posts
        if ( $pagenow=='edit.php' )
        {
            $screen[BASE] = POST;
            $screen[TYPE] = ( isset($_GET[POST_TYPE]) ) ? $_GET[POST_TYPE] : POST;
            $screen[SUBTYPE] = ( isset($_GET[PAGE]) ) ? $_GET[PAGE] : '';
        }

        //-----------------------------------------------------
        // post / page / custom-post / media : edit
        elseif ( $pagenow=='post.php' && ($_GET[ACTION]==EDIT) )
        {
            $curr_post = get_post($_GET[POST]);

            if( isset($curr_post) )
            {
                $screen[BASE] = POST;
                $screen[TYPE] = ($curr_post->post_type==ATTACHMENT) ? MEDIA : $curr_post->post_type;
                $screen[ACTION] = EDIT;
                $screen[POST_ID] = $_GET[POST];
            }
        }
        //-----------------------------------------------------
        // post / page / custom-post : add
        elseif ( $pagenow=='post-new.php' )
        {
            $screen[BASE] = POST;
            $screen[TYPE] = ( isset($_GET[POST_TYPE]) ) ? $_GET[POST_TYPE] : POST;
            $screen[ACTION] = ADD;
        }

        //-----------------------------------------------------
        // media library
        elseif ( $pagenow=='upload.php' )
        {
            $screen[TYPE] = MEDIA; //media library
        }
        //-----------------------------------------------------
        // media : add
        elseif ( $pagenow=='media-new.php' )
        {
            $screen[TYPE]     = MEDIA;
            $screen[ACTION]   = ADD;
        }

        //-----------------------------------------------------
        // taxonomy
        elseif ( $pagenow=='edit-tags.php' && isset($_GET['taxonomy']) )
        {
            $screen[TYPE]         = TAXONOMY;
            $screen[SUBTYPE]      = $_GET[TAXONOMY];
            $screen[TAXONOMY]     = $_GET[TAXONOMY];
            $screen[PARENT_TYPE]  = $_GET[POST_TYPE];
            $screen[ACTION]       = (isset($_GET[ACTION]) && ($_GET[ACTION]==EDIT) ) ? EDIT : ADD;
        }

        //-----------------------------------------------------
        // users
        elseif ( $pagenow=='users.php' )
        {
            $screen[TYPE] = USER;
        }
        // user : current user profile : edit
        elseif ( $pagenow=='profile.php' )
        {
            $screen[TYPE]     = USER;
            $screen[SUBTYPE]  = PROFILE;
            $screen[ACTION]   = EDIT;
            $screen[USER_ID]  = get_current_user_id();
        }
        // user : current user profile : edit
        elseif ( $pagenow=='user-edit.php' && isset($_GET[USER_ID]) )
        {
            $screen[TYPE]     = USER;
            $screen[ACTION]   = EDIT;
            $screen[USER_ID]  = $_GET[USER_ID];
        }

        // user : add
        elseif ( $pagenow=='user-new.php' )
        {
            $screen[TYPE]     = USER;
            $screen[ACTION]   = ADD;
        }


        //-----------------------------------------------------
        // admin (includes shopp subtypes)
        elseif ( $pagenow=="admin.php" && isset($_GET['page'], $_GET['id']) )
        {
            $screen[TYPE] = ADMIN;
            $screen[SUBTYPE] = $_GET['page'];
            $screen[ACTION] = ($_GET['id']=="new") ? ADD : EDIT;
        }

//        }
        return $screen;
    }



}

?>