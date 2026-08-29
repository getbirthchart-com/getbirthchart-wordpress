<?php
/**
 * Map GetBirthChart natal responses into frontend-safe calculator results.
 *
 * @package GetBirthChart
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Treats API responses as untrusted data.
 */
class GetBirthChart_Result_Mapper {

	private const SIGNS = array(
		'aries',
		'taurus',
		'gemini',
		'cancer',
		'leo',
		'virgo',
		'libra',
		'scorpio',
		'sagittarius',
		'capricorn',
		'aquarius',
		'pisces',
	);

	private const PLANET_ORDER = array(
		'sun',
		'moon',
		'mercury',
		'venus',
		'mars',
		'jupiter',
		'saturn',
		'uranus',
		'neptune',
		'pluto',
		'chiron',
		'true_node',
		'mean_node',
	);

	/**
	 * Build a public calculator result from a natal chart payload.
	 *
	 * @param string               $type Calculator type.
	 * @param array<string, mixed> $chart Natal response.
	 * @return array<string, mixed>|WP_Error
	 */
	public static function map( string $type, array $chart ) {
		$subject          = isset( $chart['subject'] ) && is_array( $chart['subject'] ) ? $chart['subject'] : array();
		$birth_time_known = ! empty( $subject['birthTimeKnown'] );
		$bodies           = isset( $chart['bodies'] ) && is_array( $chart['bodies'] ) ? $chart['bodies'] : array();
		$angles           = isset( $chart['angles'] ) && is_array( $chart['angles'] ) ? $chart['angles'] : array();

		$sun    = self::map_body( $bodies['sun'] ?? null, 'sun' );
		$moon   = self::map_moon( $bodies['moon'] ?? null, $chart, $birth_time_known );
		$rising = $birth_time_known ? self::map_angle( $angles['ascendant'] ?? null ) : null;

		if ( 'rising-sign' === $type ) {
			if ( ! $birth_time_known || null === $rising ) {
				return new WP_Error(
					'birth_time_required',
					GetBirthChart_Validator::rising_requires_time_message()
				);
			}
			return array(
				'type'             => $type,
				'birth_time_known' => true,
				'rising'           => $rising,
			);
		}

		if ( 'moon-sign' === $type ) {
			return array(
				'type'             => $type,
				'birth_time_known' => $birth_time_known,
				'moon'             => $moon,
			);
		}

		$result = array(
			'type'             => $type,
			'birth_time_known' => $birth_time_known,
			'sun'              => $sun,
			'moon'             => $moon,
		);
		if ( null !== $rising ) {
			$result['rising'] = $rising;
		}
		if ( 'birth-chart' === $type ) {
			$result['planets'] = self::map_planets( $bodies );
		}
		return $result;
	}

	/**
	 * Map one natal body into a public placement.
	 *
	 * @param mixed  $body Body payload.
	 * @param string $name Body name.
	 * @return array<string, mixed>|null
	 */
	private static function map_body( $body, string $name ) {
		if ( ! is_array( $body ) ) {
			return null;
		}
		$sign = self::sanitize_sign( $body['sign'] ?? '' );
		if ( '' === $sign ) {
			return null;
		}
		$degree = self::sanitize_degree( $body['degreeInSign'] ?? null );
		$item   = array(
			'name' => self::display_name( $name ),
			'sign' => $sign,
		);
		if ( null !== $degree ) {
			$item['degree'] = $degree;
		}
		return $item;
	}

	/**
	 * Map the Moon, including unknown-time uncertainty.
	 *
	 * @param mixed                $body  Moon payload.
	 * @param array<string, mixed> $chart Full chart.
	 * @param bool                 $birth_time_known Whether time is known.
	 * @return array<string, mixed>
	 */
	private static function map_moon( $body, array $chart, bool $birth_time_known ): array {
		$mapped      = self::map_body( $body, 'moon' );
		$uncertainty = self::moon_uncertainty( $chart );
		$result      = is_array( $mapped ) ? $mapped : array();

		if ( $birth_time_known ) {
			if ( ! empty( $uncertainty ) ) {
				$result['uncertainty'] = $uncertainty;
			}
			return $result;
		}

		$ambiguous = ! isset( $uncertainty['ambiguous'] ) || true === $uncertainty['ambiguous'];
		if ( $ambiguous ) {
			$out = array(
				'uncertain' => true,
			);
			if ( ! empty( $uncertainty['possible_signs'] ) ) {
				$out['possible_signs'] = $uncertainty['possible_signs'];
			}
			$out['note'] = __( 'The Moon sign cannot be confirmed without a birth time.', 'getbirthchart' );
			return $out;
		}

		if ( isset( $result['degree'] ) ) {
			unset( $result['degree'] );
		}
		$result['uncertain'] = false;
		$result['note']      = __( 'Exact degree still needs a birth time.', 'getbirthchart' );
		return $result;
	}

	/**
	 * Map an angle such as the Ascendant.
	 *
	 * @param mixed $angle Angle payload.
	 * @return array<string, mixed>|null
	 */
	private static function map_angle( $angle ) {
		if ( ! is_array( $angle ) ) {
			return null;
		}
		$sign = self::sanitize_sign( $angle['sign'] ?? '' );
		if ( '' === $sign ) {
			return null;
		}
		$item   = array( 'sign' => $sign );
		$degree = self::sanitize_degree( $angle['degreeInSign'] ?? null );
		if ( null !== $degree ) {
			$item['degree'] = $degree;
		}
		return $item;
	}

	/**
	 * Map natal bodies into an ordered planet list.
	 *
	 * @param array<string, mixed> $bodies Bodies object.
	 * @return list<array<string, mixed>>
	 */
	private static function map_planets( array $bodies ): array {
		$planets = array();
		foreach ( self::PLANET_ORDER as $name ) {
			if ( ! isset( $bodies[ $name ] ) ) {
				continue;
			}
			$mapped = self::map_body( $bodies[ $name ], $name );
			if ( null !== $mapped ) {
				$planets[] = $mapped;
			}
		}
		return $planets;
	}

	/**
	 * Read explicit Moon uncertainty from the natal payload when present.
	 *
	 * @param array<string, mixed> $chart Chart payload.
	 * @return array<string, mixed>
	 */
	private static function moon_uncertainty( array $chart ): array {
		$meta      = isset( $chart['meta'] ) && is_array( $chart['meta'] ) ? $chart['meta'] : array();
		$candidate = $meta['moonUncertainty'] ?? null;
		if ( ! is_array( $candidate ) ) {
			$uncertainty = $meta['uncertainty'] ?? null;
			$candidate   = is_array( $uncertainty ) ? ( $uncertainty['moon'] ?? null ) : null;
		}
		if ( ! is_array( $candidate ) || ! array_key_exists( 'ambiguous', $candidate ) ) {
			return array();
		}
		$result = array(
			'ambiguous' => (bool) $candidate['ambiguous'],
		);
		if ( isset( $candidate['possibleSigns'] ) && is_array( $candidate['possibleSigns'] ) ) {
			$signs = array();
			foreach ( $candidate['possibleSigns'] as $sign ) {
				$clean = self::sanitize_sign( $sign );
				if ( '' !== $clean ) {
					$signs[] = $clean;
				}
			}
			if ( ! empty( $signs ) ) {
				$result['possible_signs'] = $signs;
			}
		}
		return $result;
	}

	/**
	 * Allowlisted zodiac sign to display form.
	 *
	 * @param mixed $value Raw sign.
	 */
	private static function sanitize_sign( $value ): string {
		if ( ! is_string( $value ) ) {
			return '';
		}
		$sign = strtolower( trim( $value ) );
		if ( ! in_array( $sign, self::SIGNS, true ) ) {
			return '';
		}
		return ucfirst( $sign );
	}

	/**
	 * Degree in sign, if it is a finite 0–30 value.
	 *
	 * @param mixed $value Raw degree.
	 */
	private static function sanitize_degree( $value ): ?float {
		if ( ! is_numeric( $value ) ) {
			return null;
		}
		$degree = (float) $value;
		if ( $degree < 0 || $degree >= 30 ) {
			return null;
		}
		return round( $degree, 2 );
	}

	/**
	 * Translated body display name.
	 *
	 * @param string $name Canonical body key.
	 */
	private static function display_name( string $name ): string {
		$names = array(
			'sun'       => __( 'Sun', 'getbirthchart' ),
			'moon'      => __( 'Moon', 'getbirthchart' ),
			'mercury'   => __( 'Mercury', 'getbirthchart' ),
			'venus'     => __( 'Venus', 'getbirthchart' ),
			'mars'      => __( 'Mars', 'getbirthchart' ),
			'jupiter'   => __( 'Jupiter', 'getbirthchart' ),
			'saturn'    => __( 'Saturn', 'getbirthchart' ),
			'uranus'    => __( 'Uranus', 'getbirthchart' ),
			'neptune'   => __( 'Neptune', 'getbirthchart' ),
			'pluto'     => __( 'Pluto', 'getbirthchart' ),
			'chiron'    => __( 'Chiron', 'getbirthchart' ),
			'true_node' => __( 'North Node', 'getbirthchart' ),
			'mean_node' => __( 'Mean Node', 'getbirthchart' ),
		);
		return $names[ $name ] ?? ucwords( str_replace( '_', ' ', $name ) );
	}
}
