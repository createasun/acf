<?php

class acf_Repeater extends acf_Field
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

        $this->type = 'repeater';
		$this->title = __("Repeater",'acf');

        add_filter( ACF_SAVE_FIELD_.TYPE_.$this->type,       array($this, 'acf_save_field')   );

        add_filter(ACF_LOAD_VALUE_.TYPE_.$this->type,      array($this, 'filter_value_repeater')    );
        add_filter(ACF_UPDATE_VALUE_.TYPE_.$this->type,      array($this, 'filter_value_repeater')    );

   	}

    /**
     * Replaces the keys in the given array with an array of in-order
     * replacement keys.
     *
     * @param array &$array
     * @param array $replacement_keys
     **/
    function renameKeys(&$array, $replacement_keys)
    {
        $keys   = array_keys($array);
        $values = array_values($array);

        for ($i=0; $i < count($replacement_keys); $i++) {
            $keys[$i] = $replacement_keys[$i];
        }

        $array = array_combine($keys, $values);
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
        // vars
        $defaults = array(
            'row_limit'		=>	0,
            'row_min'		=>	0,
            'layout' 		=> 'table',
            'sub_fields'	=>	array(),
            'button_label'	=>	__("Add Row",'acf'),
            'value'			=>	array(),
        );

        $field = array_merge($defaults, $field);


        // validate types
        $field['row_limit'] = (int) $field['row_limit'];
        $field['row_min'] = (int) $field['row_min'];


        // value may be false
        if( !$field['value'] )
        {
            $field['value'] = array();
        }


        // row limit = 0?
        if( $field['row_limit'] < 1 )
        {
            $field['row_limit'] = 999;
        }



        // min rows
        if( $field['row_min'] > count($field['value']) )
        {
            for( $i = 0; $i < $field['row_min']; $i++ )
            {
                // already have a value? continue...
                if( isset($field['value'][$i]) )
                {
                    continue;
                }

                // populate values
                $field['value'][$i] = array();

                foreach( $field['sub_fields'] as $sub_field)
                {
                    $sub_value = isset($sub_field['default_value']) ? $sub_field['default_value'] : false;
                    $field['value'][$i][ $sub_field['name'] ] = $sub_value;
                }

            }
        }


        // max rows
        if( $field['row_limit'] < count($field['value']) )
        {
            for( $i = 0; $i < count($field['value']); $i++ )
            {
                if( $i >= $field['row_limit'] )
                {
                    unset( $field['value'][$i] );
                }
            }
        }


        // setup values for row clone
        $field['value']['acfcloneindex'] = array();
        foreach( $field['sub_fields'] as $sub_field)
        {
            $sub_value = isset($sub_field['default_value']) ? $sub_field['default_value'] : false;
            $field['value']['acfcloneindex'][ $sub_field['name'] ] = $sub_value;
        }

        ?>
    <div class="repeater" data-min_rows="<?php echo $field['row_min']; ?>" data-max_rows="<?php echo $field['row_limit']; ?>">
        <table class="widefat acf-input-table <?php if( $field['layout'] == 'row' ): ?>row_layout<?php endif; ?>">
            <?php if( $field['layout'] == 'table' ): ?>
            <thead>
            <tr>
                <?php

                // order th

                if( $field['row_limit'] > 1 ): ?>
                    <th class="order"></th>
                    <?php endif; ?>

                <?php foreach( $field['sub_fields'] as $sub_field_i => $sub_field):

                // add width attr
                $attr = "";

                if( count($field['sub_fields']) > 1 && isset($sub_field['column_width']) && $sub_field['column_width'] )
                {
                    $attr = 'width="' . $sub_field['column_width'] . '%"';
                }

                ?>
                <th class="acf-th-<?php echo $sub_field['name']; ?>" <?php echo $attr; ?>>
                    <span><?php echo $sub_field['label']; ?></span>
                    <?php if( isset($sub_field['instructions']) ): ?>
                    <span class="sub-field-instructions"><?php echo $sub_field['instructions']; ?></span>
                    <?php endif; ?>
                </th><?php
            endforeach; ?>

                <?php

                // remove th

                if( $field['row_min'] < $field['row_limit'] ):  ?>
                    <th class="remove"></th>
                    <?php endif; ?>
            </tr>
            </thead>
            <?php endif; ?>
            <tbody>
                <?php if( $field['value'] ): foreach( $field['value'] as $i => $value ): ?>

            <tr class="<?php echo ( (string) $i == 'acfcloneindex') ? "row-clone" : "row"; ?>">

                <?php

                // row number

                if( $field['row_limit'] > 1 ): ?>
                    <td class="order"><?php echo $i+1; ?></td>
                    <?php endif; ?>

                <?php

                // layout: Row

                if( $field['layout'] == 'row' ): ?>
			<td class="acf_input-wrap">
				<table class="widefat acf_input">
		<?php endif; ?>


                <?php

                // loop though sub fields

                foreach( $field['sub_fields'] as $j => $sub_field ): ?>

                    <?php

                    // layout: Row

                    if( $field['layout'] == 'row' ): ?>
				<tr>
					<td class="label">
                        <label><?php echo $sub_field['label']; ?></label>
                        <?php if( isset($sub_field['instructions']) ): ?>
                        <span class="sub-field-instructions"><?php echo $sub_field['instructions']; ?></span>
                        <?php endif; ?>
                    </td>
                        <?php endif; ?>

                    <td>
                        <?php

                        // add value
                        $sub_field['value'] = isset($value[$sub_field['name']]) ? $value[$sub_field['name']] : '';

                        // *********************
                        // ** setting the $sub_field['name'] here in effect creates the structure of the values array ie the $_POST array **
                        // wdh : we are using the name/slug as key not the 'field_n' key
                        // wdh : removed
//                      $sub_field['name'] = $field['name'] . '[' . $i . '][' . $sub_field['key'] . ']';
                        // wdh : added
                        $sub_field['name'] = $field['name'] . '[' . $i . '][' . $sub_field['name'] . ']';

                        // *********************

                        // create field
                        $this->acf->create_field($sub_field);

                        ?>
                    </td>

                    <?php

                    // layout: Row

                    if( $field['layout'] == 'row' ): ?>
				</tr>
			<?php endif; ?>

                    <?php endforeach; ?>

                <?php

                // layout: Row

                if( $field['layout'] == 'row' ): ?>
				</table>
			</td>
		<?php endif; ?>

                <?php

                // delete row

                if( $field['row_min'] < $field['row_limit'] ): ?>
                    <td class="remove">
                        <a class="acf-button-add add-row-before" href="javascript:;"></a>
                        <a class="acf-button-remove" href="javascript:;"></a>
                    </td>
                    <?php endif; ?>

            </tr>
                <?php endforeach; endif; ?>
            </tbody>
        </table>
        <?php if( $field['row_min'] < $field['row_limit'] ): ?>

        <ul class="hl clearfix repeater-footer">
            <li class="right">
                <a href="javascript:;" class="add-row-end acf-button"><?php echo $field['button_label']; ?></a>
            </li>
        </ul>

        <?php endif; ?>
    </div>
    <?php
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
		$fields_names = array();

		$defaults = array(
			'row_limit'		=>	'',
			'row_min'		=>	0,
			'layout' 		=> 'table',
			'sub_fields'	=>	array(),
			'button_label'	=>	__("Add Row",'acf'),
			'value'			=>	array(),
		);
		
		$field = array_merge($defaults, $field);
		
		
		// validate types
		$field['row_min'] = (int) $field['row_min'];
		
		
		// add clone
		$field['sub_fields'][] = array(
			'key' => 'field_clone',
			'label' => __("New Field",'acf'),
			'name' => __("new_field",'acf'),
			'type' => 'text',
			'order_no' =>	1,
			'instructions' =>	'',
		);

        // wdh
        $fields_names = $this->acf->get_field_type_titles();

        // no tabs in repeater
        unset( $fields_names['tab'] );

		?>
<tr class="field_option field_option_<?php echo $this->type; ?> field_option_<?php echo $this->type; ?>_fields">
	<td class="label">
		<label><?php _e("Repeater Fields",'acf'); ?></label>
	</td>
	<td>
	<div class="repeater">
		<div class="fields_header">
			<table class="acf widefat">
				<thead>
					<tr>
						<th class="field_order"><?php _e('Field Order','acf'); ?></th>
						<th class="field_label"><?php _e('Field Label','acf'); ?></th>
						<th class="field_name"><?php _e('Field Name','acf'); ?></th>
						<th class="field_type"><?php _e('Field Type','acf'); ?></th>
					</tr>
				</thead>
			</table>
		</div>
		<div class="fields">

			<div class="no_fields_message" <?php if(count($field['sub_fields']) > 1){ echo 'style="display:none;"'; } ?>>
				<?php _e("No fields. Click the \"+ Add Sub Field button\" to create your first field.",'acf'); ?>
			</div>
	
			<?php foreach($field['sub_fields'] as $sub_field): ?>
				<div class="field field-<?php echo $sub_field['key']; ?> sub_field" data-id="<?php echo $sub_field['key']; ?>">
					<div class="field_meta">
					<table class="acf widefat">
						<tr>
							<td class="field_order"><span class="circle"><?php echo (int)$sub_field['order_no'] + 1; ?></span></td>
							<td class="field_label">
								<strong>
									<a class="acf_edit_field" title="<?php _e("Edit this Field",'acf'); ?>" href="javascript:;"><?php echo $sub_field['label']; ?></a>
								</strong>
								<div class="row_options">
									<span><a class="acf_edit_field" title="<?php _e("Edit this Field",'acf'); ?>" href="javascript:;"><?php _e("Edit",'acf'); ?></a> | </span>
									<span><a title="<?php _e("Read documentation for this field",'acf'); ?>" href="http://www.advancedcustomfields.com/docs/field-types/" target="_blank"><?php _e("Docs",'acf'); ?></a> | </span>
									<span><a class="acf_duplicate_field" title="<?php _e("Duplicate this Field",'acf'); ?>" href="javascript:;"><?php _e("Duplicate",'acf'); ?></a> | </span>
									<span><a class="acf_delete_field" title="<?php _e("Delete this Field",'acf'); ?>" href="javascript:;"><?php _e("Delete",'acf'); ?></a>
								</div>
							</td>
							<td class="field_name"><?php echo $sub_field['name']; ?></td>
							<td class="field_type"><?php echo $sub_field['type']; ?></td>
						</tr>
					</table>
					</div>
					
					<div class="field_form_mask">
					<div class="field_form">
						
						<table class="acf_input widefat">
							<tbody>
								<tr class="field_label">
									<td class="label">
										<label><span class="required">*</span><?php _e("Field Label",'acf'); ?></label>
										<p class="description"><?php _e("This is the name which will appear on the EDIT page",'acf'); ?></p>
									</td>
									<td>
										<?php 
										$this->acf->create_field(array(
											'type'	=>	'text',
											'name'	=>	'fields['.$key.'][sub_fields]['.$sub_field['key'].'][label]',
											'value'	=>	$sub_field['label'],
											'class'	=>	'label',
										));
										?>
									</td>
								</tr>
								<tr class="field_name">
									<td class="label">
										<label><span class="required">*</span><?php _e("Field Name",'acf'); ?></label>
										<p class="description"><?php _e("Single word, no spaces. Underscores and dashes allowed",'acf'); ?></p>
									</td>
									<td>
										<?php 
										$this->acf->create_field(array(
											'type'	=>	'text',
											'name'	=>	'fields['.$key.'][sub_fields]['.$sub_field['key'].'][name]',
											'value'	=>	$sub_field['name'],
											'class'	=>	'name',
										));
										?>
									</td>
								</tr>
								<tr class="field_type">
									<td class="label"><label><span class="required">*</span><?php _e("Field Type",'acf'); ?></label></td>
									<td>
										<?php 
										$this->acf->create_field(array(
											'type'	=>	'select',
											'name'	=>	'fields['.$key.'][sub_fields]['.$sub_field['key'].'][type]',
											'value'	=>	$sub_field['type'],
											'class'	=>	'type',
											'choices'	=>	$fields_names
										));
										?>
									</td>
								</tr>
								<tr class="field_instructions">
									<td class="label"><label><?php _e("Field Instructions",'acf'); ?></label></td>
									<td>
										<?php
										
										if( !isset($sub_field['instructions']) )
										{
											$sub_field['instructions'] = "";
										}
										
										$this->acf->create_field(array(
											'type'	=>	'text',
											'name'	=>	'fields['.$key.'][sub_fields]['.$sub_field['key'].'][instructions]',
											'value'	=>	$sub_field['instructions'],
											'class'	=>	'instructions',
										));
										?>
									</td>
								</tr>
								<tr class="field_column_width">
									<td class="label">
										<label><?php _e("Column Width",'acf'); ?></label>
										<p class="description"><?php _e("Leave blank for auto",'acf'); ?></p>
									</td>
									<td>
										<?php 
										
										if( !isset($sub_field['column_width']) )
										{
											$sub_field['column_width'] = "";
										}
										
										$this->acf->create_field(array(
											'type'	=>	'number',
											'name'	=>	'fields['.$key.'][sub_fields]['.$sub_field['key'].'][column_width]',
											'value'	=>	$sub_field['column_width'],
											'class'	=>	'column_width',
										));
										?> %
									</td>
								</tr>
								<?php 
								
								if( isset($this->acf->fields[ $sub_field['type'] ]) )
								{
									$this->acf->fields[$sub_field['type']]->create_options($key.'][sub_fields]['.$sub_field['key'], $sub_field);
								}
								
								?>
								<tr class="field_save">
									<td class="label">
										<!-- <label><?php _e("Save Field",'acf'); ?></label> -->
									</td>
									<td>
										<ul class="hl clearfix">
											<li>
												<a class="acf_edit_field acf-button grey" title="<?php _e("Close Field",'acf'); ?>" href="javascript:;"><?php _e("Close Sub Field",'acf'); ?></a>
											</li>
										</ul>
									</td>
								</tr>								
							</tbody>
						</table>
				
					</div><!-- End Form -->
					</div><!-- End Form Mask -->
				
				</div>
			<?php endforeach; ?>
		</div>
		<div class="table_footer">
			<div class="order_message"><?php _e('Drag and drop to reorder','acf'); ?></div>
			<a href="javascript:;" id="add_field" class="acf-button"><?php _e('+ Add Sub Field','acf'); ?></a>
		</div>
	</div>
	</td>
</tr>
<tr class="field_option field_option_<?php echo $this->type; ?>">
	<td class="label">
		<label><?php _e("Minimum Rows",'acf'); ?></label>
	</td>
	<td>
		<?php 
		$this->acf->create_field(array(
			'type'	=>	'text',
			'name'	=>	'fields['.$key.'][row_min]',
			'value'	=>	$field['row_min'],
		));
		?>
	</td>
</tr>
<tr class="field_option field_option_<?php echo $this->type; ?>">
	<td class="label">
		<label><?php _e("Maximum Rows",'acf'); ?></label>
	</td>
	<td>
		<?php 
		$this->acf->create_field(array(
			'type'	=>	'text',
			'name'	=>	'fields['.$key.'][row_limit]',
			'value'	=>	$field['row_limit'],
		));
		?>
	</td>
</tr>
<tr class="field_option field_option_<?php echo $this->type; ?> field_option_<?php echo $this->type; ?>_layout">
	<td class="label">
		<label><?php _e("Layout",'acf'); ?></label>
	</td>
	<td>
		<?php 
		$this->acf->create_field(array(
			'type'	=>	'radio',
			'name'	=>	'fields['.$key.'][layout]',
			'value'	=>	$field['layout'],
			'layout'	=>	'horizontal',
			'choices'	=>	array(
				'table'	=>	__("Table (default)",'acf'),
				'row'	=>	__("Row",'acf')
			)
		));
		?>
	</td>
</tr>
<tr class="field_option field_option_<?php echo $this->type; ?>">
	<td class="label">
		<label><?php _e("Button Label",'acf'); ?></label>
	</td>
	<td>
		<?php 
		$this->acf->create_field(array(
			'type'	=>	'text',
			'name'	=>	'fields['.$key.'][button_label]',
			'value'	=>	$field['button_label'],
		));
		?>
	</td>
</tr>
		<?php
	}
	

	/*--------------------------------------------------------------------------------------
	*
	*	acf_save_field
	*	- called just before saving the field to the database.
	*
	*	@author Elliot Condon
	*	@since 2.2.0
	* 
	*-------------------------------------------------------------------------------------*/
	
	function acf_save_field($field)
	{
//        phplog('repeater.php','PRE $field=',$field );

		// format sub_fields
		if( $field['sub_fields'] )
		{
			// remove dummy field
			unset( $field['sub_fields']['field_clone'] );
			
			
			// loop through and save fields
			$i = -1;
			$sub_fields = array();
			
			
			foreach( $field['sub_fields'] as $key => $sub_field )   // wdh : changed $f to $sub_field
			{
				$i++;

				// order
				$sub_field['order_no'] = $i;
				$sub_field['key'] = $key;

                // wdh :removed
				// apply filters
//				$sub_field = apply_filters('acf_save_field', $sub_field );
//				$sub_field = apply_filters('acf_save_field-' . $sub_field['type'], $sub_field );
                // wdh : added
                $sub_field = $this->acf->apply_save_field_filters($sub_field);

                // ********************************
                // ** important **
                // wdh : save field via 'name' not 'field_n' key
                // wdh : removed
//				$sub_fields[ $sub_field['key'] ] = $sub_field;
                // wdh : added
                $sub_fields[ $sub_field['name'] ] = $sub_field;
                // ********************************
			}
			// update sub fields
			$field['sub_fields'] = $sub_fields;
		}

//        phplog('repeater.php','POST $field=',$field );

		// return updated repeater field
		return $field;

	}

    /*--------------------------------------------------------------------------------------
    *
    *	filter_value_repeater
    *   called on read and update
    *
    *   @author Wayne D Harris
    *	@since
    *
    *-------------------------------------------------------------------------------------*/
    function filter_value_repeater( $field_value )
    {
        $sub_fields = array();

        if( $field_value )
        {
//        phplog('sola-acf.php','............filter subfields..............................'  );
//        phplog('sola-acf.php','PRE FILTER $field_value=', $field_value  );

            /* from eg
            1 =>
            array (
                "text" => "aaa",
                "num" => "1",
            ),
            "1358216806848" =>
            array (
                "text" => "bbb",
                "num" => "2",
            ),
            "acfcloneindex" =>
            array (
                "text" => "default text",
                "num" => "0",
            ),
            */

            if( $field_value['acfcloneindex'] )
            {
                unset( $field_value['acfcloneindex'] );
            }

            // loop through rows
            foreach( $field_value as $sub_field_row )
            {
                $sub_fields[] = $sub_field_row;
            }

            /* to eg
            0 =>
            array (
                "text" => "aaa",
                "num" => "1",
            ),
            1 =>
            array (
                "text" => "bbb",
                "num" => "2",
            ),
            */

//            phplog('sola-acf.php','POST FILTER $sub_fields=', $sub_fields  );
//            phplog('sola-acf.php','..........................................................'  );
        }

        return $sub_fields;
    }
	/*--------------------------------------------------------------------------------------
	*
	*	update_value
	*
	*	@author Elliot Condon
	*	@since 2.2.0
	* 
	*-------------------------------------------------------------------------------------*/
	
//	function update_value($post_id, $field, $value)
//	{
//		$total = 0;
//
//		if($value)
//		{
//			// remove dummy field
//			unset($value['acfcloneindex']);
//
//			$i = -1;
//
//			// loop through rows
//			foreach($value as $row)
//			{
//				$i++;
//
//				// increase total
//				$total++;
//
//				// loop through sub fields
//				foreach($field['sub_fields'] as $sub_field)
//				{
//					// get sub field data
//					$v = isset($row[$sub_field['key']]) ? $row[$sub_field['key']] : '';
//
//					// add to parent value
//					//$parent_value[$i][$sub_field['name']] = $v;
//
//					// update full name
//					$sub_field['name'] = $field['name'] . '_' . $i . '_' . $sub_field['name'];
//
//					// save sub field value
//					$this->acf->update_value($post_id, $sub_field, $v);
//				}
//			}
//		}
//
//		parent::update_value($post_id, $field, $total);
//
//	}
//
	
	/*--------------------------------------------------------------------------------------
	*
	*	get_value
	*
	*	@author Elliot Condon
	*	@since 2.2.0
	* 
	*-------------------------------------------------------------------------------------*/
	
//	function get_value($post_id, $field)
//	{
//		// vars
//		$values = array();
//		$total = 0;
//
//
//		// get total rows
//		$total = (int) parent::get_value($post_id, $field);
//
//
//		if($total > 0)
//		{
//			// loop through rows
//			for($i = 0; $i < $total; $i++)
//			{
//				// loop through sub fields
//				foreach($field['sub_fields'] as $sub_field)
//				{
//					// store name
//					$field_name = $sub_field['name'];
//
//					// update full name
//					$sub_field['name'] = $field['name'] . '_' . $i . '_' . $field_name;
//
//					$values[$i][$field_name] = $this->acf->get_value($post_id, $sub_field);
//				}
//			}
//
//		}
//
//		return $values;
//	}
	
	/*--------------------------------------------------------------------------------------
	*
	*	get_value_for_api
	*
	*	@author Elliot Condon
	*	@since 3.0.0
	* 
	*-------------------------------------------------------------------------------------*/
	
	function get_value_for_api($post_id, $field)
	{
		// vars
		$values = array();
		$total = 0;
		
		
		// get total rows
		$total = (int) parent::get_value($post_id, $field);
		
		
		if($total > 0)
		{
			// loop through rows
			for($i = 0; $i < $total; $i++)
			{
				// loop through sub fields
				foreach($field['sub_fields'] as $sub_field)
				{
					// store name
					$field_name = $sub_field['name'];
					
					// update full name
					$sub_field['name'] = $field['name'] . '_' . $i . '_' . $field_name;
					
					$values[$i][$field_name] = $this->acf->get_value_for_api($post_id, $sub_field);
				}
			}
			
			return $values;
		}
		
		return array();
	}
	
}

?>