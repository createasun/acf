<?php 

/*

*/


class Acf_Screen
{
    var $wp_session,
        $screen;

	function __construct()
	{

        define( 'SCREEN',               'screen'                );

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



        $this->wp_session = WP_Session::get_instance();

        $this->init_screen();
	}


    //-------------------------------------------------------------------------------------*/
    function init_screen()
    {
        global $pagenow;

        // $pagenow can be 'admin-ajax.php on ajax calls
        // and page onload data is incorrect
        // so using a session to store screen data

//        phplog('screen.php','$pagenow=',$pagenow);
//        phplog('screen.php','$_POST=',$_POST);
//        phplog('screen.php','empty($_POST)=',empty($_POST));


        if( $pagenow == 'admin-ajax.php' || !empty($_POST) )
        {
            //use stored session screen data
            $this->screen = $this->wp_session['screen'];
        }
        else
        {
            $this->screen = $this->get_current_screen();
            //store screen data in wp_session
            $this->wp_session[SCREEN] = $this->screen;
        }
//        phplog('screen.php','$this->screen=',$this->screen);

        return $this->screen;
    }
    //-------------------------------------------------------------------------------------*/
    function get_screen()
    {
        if( !isset($this->screen) )
        {
            $this->screen = $this->wp_session[SCREEN];
        }
        return $this->screen;
    }
    //-------------------------------------------------------------------------------------*/
    function is_base_post_screen()
    {
        return ( $this->screen[BASE]==POST );
    }
    //-------------------------------------------------------------------------------------*/
    function is_user_edit_screen()
    {
        return ( $this->screen[TYPE]==USER && $this->screen[ACTION]==EDIT );
    }
    //-------------------------------------------------------------------------------------*/
    function is_user_add_screen()
    {
        return ( $this->screen[TYPE]==USER && $this->screen[ACTION]==ADD );
    }
    //-------------------------------------------------------------------------------------*/
    function is_taxonomy_add_screen()
    {
        return ( $this->screen[TYPE]==TAXONOMY && $this->screen[ACTION]==ADD );
    }
    //-------------------------------------------------------------------------------------*/
    function is_taxonomy_edit_screen()
    {
        return ( $this->screen[TYPE]==TAXONOMY && $this->screen[ACTION]==EDIT );
    }
    //-------------------------------------------------------------------------------------*/
    function is_options_screen()
    {
        return false;
    }
    //-------------------------------------------------------------------------------------*/
    function is_field_group_screen($page)
    {
        return ( $this->screen[TYPE]=='acf' && $this->screen[SUBTYPE]==$page);
    }
    //-------------------------------------------------------------------------------------*/
    function is_field_groups_screen()
    {
         return ( $this->screen[TYPE]=='acf' );
    }
    //-------------------------------------------------------------------------------------*/
    function get_taxonomy()
    {
        return $this->screen[TAXONOMY];
    }
    //-------------------------------------------------------------------------------------*/
    function get_screen_type()
    {
        return $this->screen[TYPE];
    }
    //-------------------------------------------------------------------------------------*/
    function get_screen_action()
    {
        return $this->screen[ACTION];
    }
    //-------------------------------------------------------------------------------------*/
    function is_edit_screen()
    {
        return $this->screen[ACTION]==EDIT;
    }
    //-------------------------------------------------------------------------------------*/
    function is_add_screen()
    {
        return $this->screen[ACTION]==ADD;
    }
    //-------------------------------------------------------------------------------------*/
    function is_action_screen()
    {
        return $this->screen[ACTION]==ADD || $this->screen[ACTION]==EDIT;
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
    function set_value( $key, $value )
    {
        $this->screen[$key] = $value;
    }
    //-------------------------------------------------------------------------------------*/
    function get_value( $key )
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
        global $pagenow;


        $screen = array(
            BASE        => '',
            TYPE        => '',
            SUBTYPE     => '',
            ACTION      => '',
            TAXONOMY    => $_GET[TAXONOMY],
            POST_TYPE   => $_GET[POST_TYPE],
            POST_ID     => $_GET[POST_ID],
            USER_ID     => $_GET[USER_ID],

        );
        //-----------------------------------------------------
        // posts / pages / custom-posts
        if ( $pagenow=='edit.php' )
        {
            $screen[BASE]       = POST;
            $screen[POST_TYPE]  = ( isset($_GET[POST_TYPE]) ) ? $_GET[POST_TYPE] : POST;
            $screen[TYPE]       = $screen[POST_TYPE];
            $screen[SUBTYPE]    = ( isset($_GET[PAGE]) ) ? $_GET[PAGE] : '';
        }

        //-----------------------------------------------------
        // post / page / custom-post / media : edit
        elseif ( $pagenow=='post.php' && ($_GET[ACTION]==EDIT) )
        {
            $curr_post = get_post($_GET[POST]);

            if( isset($curr_post) )
            {
                $screen[BASE]       = POST;
                $screen[POST_TYPE]  = $curr_post->post_type;
                $screen[POST_ID]    = $_GET[POST];
                $screen[TYPE]       = ($curr_post->post_type==ATTACHMENT) ? MEDIA : $curr_post->post_type;
                $screen[ACTION]     = EDIT;

            }
        }
        //-----------------------------------------------------
        // post / page / custom-post : add
        elseif ( $pagenow=='post-new.php' )
        {
            $screen[BASE]       = POST;
            $screen[POST_TYPE]  = ( isset($_GET[POST_TYPE]) ) ? $_GET[POST_TYPE] : POST;
            $screen[TYPE]       = $screen[POST_TYPE];
            $screen[ACTION]     = ADD;
        }

        //-----------------------------------------------------
        // media library
        elseif ( $pagenow=='upload.php' )
        {
            $screen[TYPE]       = MEDIA; //media library
            $screen[POST_TYPE]  = ATTACHMENT;
        }
        //-----------------------------------------------------
        // media : add
        elseif ( $pagenow=='media-new.php' )
        {
            $screen[TYPE]       = MEDIA;
            $screen[POST_TYPE]  = ATTACHMENT;
            $screen[ACTION]     = ADD;
        }

        //-----------------------------------------------------
        // taxonomy
        elseif ( $pagenow=='edit-tags.php' && isset($_GET['taxonomy']) )
        {
            $screen[TYPE]       = TAXONOMY;
            $screen[SUBTYPE]    = $_GET[TAXONOMY];
            $screen[TAXONOMY]   = $_GET[TAXONOMY];
            $screen[POST_TYPE]  = $_GET[POST_TYPE];
            $screen[ACTION]     = (isset($_GET[ACTION]) && ($_GET[ACTION]==EDIT) ) ? EDIT : ADD;
        }

        //-----------------------------------------------------
        // users
        elseif ( $pagenow=='users.php' )
        {
            $screen[TYPE]       = USER;
            $screen[POST_TYPE]  = $_GET[POST_TYPE];
        }
        // user : current user profile : edit
        elseif ( $pagenow=='profile.php' )
        {
            $screen[TYPE]       = USER;
            $screen[SUBTYPE]    = PROFILE;
            $screen[ACTION]     = EDIT;
            $screen[USER_ID]    = get_current_user_id();
            $screen[POST_TYPE]  = $_GET[POST_TYPE];
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
            $screen[USER_ID]  = $_GET[USER_ID];
        }


        //-----------------------------------------------------
        // admin (includes shopp subtypes)
        elseif ( $pagenow=="admin.php" && isset($_GET['page'], $_GET['id']) )
        {
            $screen[TYPE] = ADMIN;
            $screen[SUBTYPE] = $_GET['page'];
            $screen[ACTION] = ($_GET['id']=="new") ? ADD : EDIT;
            $screen[POST_TYPE]  = $_GET[POST_TYPE];
        }

//        }
        return $screen;
    }



}

?>