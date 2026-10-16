<?php
/**
 * Decorative bridge arches (the "Setu" motif from the logo).
 */
defined( 'ABSPATH' ) || exit;
$class = $args['class'] ?? '';
?>
<svg class="bridge-art <?php echo esc_attr( $class ); ?>" viewBox="0 0 640 220" preserveAspectRatio="xMidYMax slice" aria-hidden="true" focusable="false">
	<path class="bridge-art__deck" d="M0 150 Q320 70 640 150" fill="none" stroke-width="10" stroke-linecap="round"/>
	<g class="bridge-art__arches" fill="none" stroke-width="6">
		<path d="M20 220 V170 A40 40 0 0 1 100 170 V220"/>
		<path d="M120 220 V156 A40 40 0 0 1 200 156 V220"/>
		<path d="M220 220 V146 A40 40 0 0 1 300 146 V220"/>
		<path d="M340 220 V146 A40 40 0 0 1 420 146 V220"/>
		<path d="M440 220 V156 A40 40 0 0 1 520 156 V220"/>
		<path d="M540 220 V170 A40 40 0 0 1 620 170 V220"/>
	</g>
	<g class="bridge-art__cables" fill="none" stroke-width="2">
		<path d="M320 20 L180 128 M320 20 L220 118 M320 20 L260 110 M320 20 L380 110 M320 20 L420 118 M320 20 L460 128"/>
	</g>
	<path class="bridge-art__pylon" d="M312 20 h16 v110 h-16z"/>
</svg>
