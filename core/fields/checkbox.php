<?php

class acf_Checkbox extends acf_Field
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

        $this->type = 'checkbox';
		$this->title = __("Checkbox",'acf');

//      add_filter( ACF_SAVE_FIELD_.TYPE_.$this->type,       array($this, 'acf_save_field')   );
//      add_filter( ACF_LOAD_VALUE_.TYPE_.$this->type,       array($this, 'acf_load_value')   );
//      add_filter( ACF_UPDATE_VALUE_.TYPE_.$this->type,     array($this, 'acf_update_value') );

        add_filter( ACF_SAVE_FIELD_.TYPE_.$this->type,      array($this,'save_field_choices') );
		
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
		// defaults
		if(empty($field['value']))
		{
			$field['value'] = array();
		}
		
		
		// single value to array conversion
		if( !is_array($field['value']) )
		{
			$field['value'] = array( $field['value'] );
		}
		
		
		// no choices
		if(empty($field['choices']))
		{
			echo '<p>' . __("No choices to choose from",'acf') . '</p>';
			return false;
		}
		
		
		// html
		echo '<ul class="checkbox_list '.$field['class'].'">';
		echo '<input type="hidden" name="'.$field['name'].'" value="" />';
		// checkbox saves an array
		$field['name'] .= '[]';
		
		// foreach choices
		foreach($field['choices'] as $key => $value)
		{
			$selected = '';
			if(in_array($key, $field['value']))
			{
				$selected = 'checked="yes"';
			}
			echo '<li><label><input id="' . $field['id'] . '-' . $key . '" type="checkbox" class="' . $field['class'] . '" name="' . $field['name'] . '" value="' . $key . '" ' . $selected . ' />' . $value . '</label></li>';
		}
		
		echo '</ul>';

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
		// defaults
		
		
		// implode checkboxes so they work in a textarea
		if(isset($field['choices']) && is_array($field['choices']))
		{		
			foreach($field['choices'] as $choice_key => $choice_val)
			{
				$field['choices'][$choice_key] = $choice_key.' : '.$choice_val;
			}
			$field['choices'] = implode("\n", $field['choices']);
		}
		else
		{
			$field['choices'] = "";
		}
		?>
		<tr class="field_option field_option_<?php echo $this->type; ?>">
			<td class="label">
				<label for=""><?php _e("Choices",'acf'); ?></label>
				<p class="description"><?php _e("Enter your choices one per line",'acf'); ?><br />
				<br />
				<?php _e("Red",'acf'); ?><br />
				<?php _e("Blue",'acf'); ?><br />
				<br />
				<?php _e("red : Red",'acf'); ?><br />
				<?php _e("blue : Blue",'acf'); ?><br />
				</p>
			</td>
			<td>
				<?php 
				$this->acf->create_field(array(
					'type'	=>	'textarea',
					'class' => 	'textarea field_option-choices',
					'name'	=>	'fields['.$key.'][choices]',
					'value'	=>	$field['choices'],
				));
				?>
			</td>
		</tr>
		<?php
	}
		
}
?>