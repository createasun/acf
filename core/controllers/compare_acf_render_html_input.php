<?php
/**
 * .php
 *
 * @author        Waynoss
 * @copyright    Copyright (c) Wayne D Harris
 * @link        http://createasunthemes.com
 * @package     solaframework
 * @version     1.00
 */



if( $layout == 'metabox' )
{
    echo '<div id="acf-' . $field['name'] . '" class="field field-' . $field['type'] . ' field-'.$field['key'] . $required_class . '">';

    echo '<p class="label">';

    echo '<label for="fields[' . $field['key'] . ']">' . $field['label'] . $required_label . '</label>';
    echo $field['instructions'];
    echo '</p>';

    $field['name'] = 'fields[' . $field['key'] . ']';
    $this->parent->create_field($field);

    echo '</div>';
}
elseif( $layout == 'div' )
{
    echo '<div id="acf-' . $field['name'] . '" class="form-field field field-' . $field['type'] . ' field-'.$field['key'] . $required_class . '">';

    echo '<label for="fields[' . $field['key'] . ']">' . $field['label'] . $required_label . '</label>';
    $field['name'] = 'fields[' . $field['key'] . ']';
    $this->parent->create_field($field);
    if($field['instructions']) echo '<p class="description">' . $field['instructions'] . '</p>';
    echo '</div>';
}
else
{
    echo '<tr id="acf-' . $field['name'] . '" class="form-field field field-' . $field['type'] . ' field-'.$field['key'] . $required_class . '">';
    echo '<th valign="top" scope="row"><label for="fields[' . $field['key'] . ']">' . $field['label'] . $required_label . '</label></th>';
    echo '<td>';
    $field['name'] = 'fields[' . $field['key'] . ']';
    $this->parent->create_field($field);

    if($field['instructions']) echo '<p class="description">' . $field['instructions'] . '</p>';
    echo '</td>';
    echo '</tr>';

}