<?php

class acf_Color_picker extends acf_Field
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

        $this->type = 'color_picker';
		$this->title = __("Color Picker",'acf');

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
		// html
		echo '<input type="text" value="' . $field['value'] . '" class="acf_color_picker" name="' . $field['name'] . '" id="' . $field['id'] . '" />';

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
		// vars
		$defaults = array(
			'default_value'	=>	'',
		);
		
		$field = array_merge($defaults, $field);

		
		?>
		<tr class="field_option field_option_<?php echo $this->type; ?>">
			<td class="label">
				<label><?php _e("Default Value",'acf'); ?></label>
				<p class="description"><?php _e("eg: #ffffff",'acf'); ?></p>
			</td>
			<td>
				<?php 
				$this->acf->create_field(array(
					'type'	=>	'text',
					'name'	=>	'fields['.$key.'][default_value]',
					'value'	=>	$field['default_value'],
				));
				?>
			</td>
		</tr>
		<?php
	}
	
	
}

?>