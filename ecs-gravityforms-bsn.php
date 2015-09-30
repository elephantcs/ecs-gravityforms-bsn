<?php
/*
Plugin Name: Gravity Forms BSN
Description: Adds an BSN mask and validation for Gravity Forms.
Author: Martijn Schipper - Elephantcs.nl
Version: 1.0
Author URI: http://elephantcs.nl
*/

if ( class_exists( 'GFForms' ) ) {

	/**
	 * Add the BSN input mask to GF via the input mask filter
	 */
	function ecs_gform_add_bsn_mask($masks){
		$masks['BSN'] = 'bsn';
		return $masks;
	}
	add_filter( 'gform_input_masks', 'ecs_gform_add_bsn_mask' );

	/**
	 * Add the script for the BSN input mask
	 */
	function ecs_gform_add_bsn_mask_script($script, $form_id, $field_id, $mask){

		if ( 'bsn' == $mask ) {
			$script = "jQuery('#input_{$form_id}_{$field_id}').mask('99999999?9');";
		}
		return $script;
	}
	add_filter( 'gform_input_mask_script', 'ecs_gform_add_bsn_mask_script', 10, 4 );

	/**
	 * Validate the given BSN number
	 */
	function ecs_gform_add_bsn_validation( $validation_result ){

	    foreach ( $validation_result['form']['fields'] as &$field ) {

	        $field_value = rgpost( "input_{$field['id']}" );

	        if ( 'bsn' == $field['inputMaskValue'] ) {
				if ( 0 == strlen( $field_value ) ) { // If empty dont check, when the field is required GF will automaticly catch that before us.
	                continue;
	            } else {

	                if ( !ecs_validate_bsn( $field_value ) ) {
	                    $validation_result['is_valid'] = false;
	                    $field['failed_validation'] = true;
	                    $field['validation_message'] = __( 'Please enter a valid value.' , 'gravityforms' );
	                }
	            }
	        }
	    }
	    return $validation_result;
	}
	add_filter( 'gform_validation', 'ecs_gform_add_bsn_validation' );

	/**
	 * Function to check if a given BSN is valid
	 */
	function ecs_validate_bsn( $bsn_number ) {

		if( ( strlen( $bsn_number ) < 8 ) || ( strlen( $bsn_number ) > 9 ) ) // Return false when BSN is not 8 or 9 numbers, this should never happen as the mask should catch that.
			return false;

		if( strlen( $bsn_number ) == 8 )
			$bsn_number = '0' . $bsn_number; // Add leading zero when BSN is 8 numbers long as that needed for the 11 check

		$bsn_digits = str_split( $bsn_number );
		$multiply_nums_to_check_to = array( 9, 8, 7, 6, 5, 4, 3, 2, -1 );
		$bsn_multiply_results = array();

		foreach ($bsn_digits as $key => $bsn_digit) {
			$bsn_digit = intval( $bsn_digit ); // convert the bsn digit to an int for comparison

			$bsn_multiply_results[] = $bsn_digit * $multiply_nums_to_check_to[$key]; // Multiply the bsn digit with the 11 check multiply values
		}

		$bsn_multiply_sum = array_sum( $bsn_multiply_results );

		$bsn_multiply_check = $bsn_multiply_sum / 11;
		$bsn_multiply_check = floatval( $bsn_multiply_check ); // Make sure we are comparing floats


		if( round( $bsn_multiply_check ) === $bsn_multiply_check ) { // Check if the result is a round number, if so this is most likely a valid BSN
			return true;
		} else {
			return false;
		}

	}
}
