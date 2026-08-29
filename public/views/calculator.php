<?php
/**
 * Frontend calculator form.
 *
 * @package GetBirthChart
 *
 * @var string $calculator_type
 * @var string $calculator_theme
 * @var string $calculator_uid
 * @var bool   $calculator_requires_time
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$getbirthchart_date_id            = $calculator_uid . '-date';
$getbirthchart_time_id            = $calculator_uid . '-time';
$getbirthchart_place_id           = $calculator_uid . '-place';
$getbirthchart_unknown_id         = $calculator_uid . '-unknown';
$getbirthchart_result_id          = $calculator_uid . '-result';
$getbirthchart_error_id           = $calculator_uid . '-error';
$getbirthchart_title              = GetBirthChart_Shortcodes::type_label( $calculator_type );
$getbirthchart_time_required_note = $calculator_requires_time
	? __( 'A birth time is required to calculate the Rising sign.', 'getbirthchart' )
	: __( 'Optional if you do not know your birth time.', 'getbirthchart' );
?>
<div
	class="getbirthchart-calculator getbirthchart-theme-<?php echo esc_attr( $calculator_theme ); ?>"
	data-getbirthchart-calculator
	data-type="<?php echo esc_attr( $calculator_type ); ?>"
	data-requires-time="<?php echo $calculator_requires_time ? '1' : '0'; ?>"
>
	<form class="getbirthchart-form" novalidate>
		<fieldset>
			<legend class="getbirthchart-title"><?php echo esc_html( $getbirthchart_title ); ?></legend>

			<div class="getbirthchart-field">
				<label for="<?php echo esc_attr( $getbirthchart_date_id ); ?>"><?php esc_html_e( 'Date of birth', 'getbirthchart' ); ?></label>
				<input
					id="<?php echo esc_attr( $getbirthchart_date_id ); ?>"
					name="date"
					type="date"
					required
					autocomplete="bday"
					aria-required="true"
					aria-describedby="<?php echo esc_attr( $getbirthchart_error_id ); ?>"
				/>
			</div>

			<div class="getbirthchart-field">
				<label for="<?php echo esc_attr( $getbirthchart_time_id ); ?>"><?php esc_html_e( 'Time of birth', 'getbirthchart' ); ?></label>
				<input
					id="<?php echo esc_attr( $getbirthchart_time_id ); ?>"
					name="time"
					type="time"
					<?php echo $calculator_requires_time ? 'required' : ''; ?>
					aria-required="<?php echo $calculator_requires_time ? 'true' : 'false'; ?>"
					aria-describedby="<?php echo esc_attr( $calculator_uid ); ?>-time-help <?php echo esc_attr( $getbirthchart_error_id ); ?>"
				/>
				<p class="getbirthchart-help" id="<?php echo esc_attr( $calculator_uid ); ?>-time-help">
					<?php echo esc_html( $getbirthchart_time_required_note ); ?>
				</p>
			</div>

			<div class="getbirthchart-field">
				<label for="<?php echo esc_attr( $getbirthchart_place_id ); ?>"><?php esc_html_e( 'Place of birth', 'getbirthchart' ); ?></label>
				<input
					id="<?php echo esc_attr( $getbirthchart_place_id ); ?>"
					name="place"
					type="text"
					required
					maxlength="80"
					autocomplete="off"
					aria-required="true"
					aria-describedby="<?php echo esc_attr( $getbirthchart_error_id ); ?>"
				/>
			</div>

			<div class="getbirthchart-field getbirthchart-field-checkbox">
				<input
					id="<?php echo esc_attr( $getbirthchart_unknown_id ); ?>"
					name="unknown_time"
					type="checkbox"
					value="1"
					<?php echo $calculator_requires_time ? 'disabled' : ''; ?>
				/>
				<label for="<?php echo esc_attr( $getbirthchart_unknown_id ); ?>"><?php esc_html_e( "I don't know my birth time", 'getbirthchart' ); ?></label>
			</div>
		</fieldset>

		<p class="getbirthchart-error" id="<?php echo esc_attr( $getbirthchart_error_id ); ?>" role="alert" hidden></p>

		<button type="submit" class="getbirthchart-submit">
			<?php esc_html_e( 'Calculate', 'getbirthchart' ); ?>
		</button>
	</form>

	<div
		class="getbirthchart-result"
		id="<?php echo esc_attr( $getbirthchart_result_id ); ?>"
		aria-live="polite"
		aria-atomic="true"
	></div>
</div>
