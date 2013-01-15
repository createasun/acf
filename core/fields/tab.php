<?php

class acf_Tab extends acf_Field
{
	
	/*--------------------------------------------------------------------------------------
	*
	*	Constructor
	*
	*	@author Elliot Condon
	*	@since 1.0.0
	*	@updated 2.2.0
	* 
	*-------------------------------------------------------------------------------------*/
	
	function __construct($acf)
	{
        parent::__construct($acf);

        $this->type = 'tab';
		$this->title = __("Tab",'acf');

//      add_filter( ACF_SAVE_FIELD_.TYPE_.$this->type,       array($this, 'acf_save_field')   );
//      add_filter( ACF_LOAD_VALUE_.TYPE_.$this->type,       array($this, 'acf_load_value')   );
//      add_filter( ACF_UPDATE_VALUE_.TYPE_.$this->type,     array($this, 'acf_update_value') );
		
   	}
   

	/*--------------------------------------------------------------------------------------
	*
	*	create_field
	*
	*	@author Elliot Condon
	*	@since 2.0.5
	*	@updated 2.2.0
	* 
	*-------------------------------------------------------------------------------------*/
	
	function create_field($field)
	{
		echo '<div class="acf-tab" data-id="' . $field['type'] . '">' . $field['label'] . '</div>';
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
	
	function create_options($key, $field)
	{
		?>
		<tr class="field_option field_option_<?php echo $this->type; ?>">
			<td class="label">
				<label><?php _e("Instructions",'acf'); ?></label>
			</td>
			<td>
				<p><?php _e("All fields proceeding this \"tab field\" (or until another \"tab field\"  is defined) will appear grouped on the edit screen.",'acf'); ?></p>
				<p><?php _e("You can use multiple tabs to break up your fields into sections.",'acf'); ?></p>
			</td>
		</tr>
		<?php
	}
	
}

?>