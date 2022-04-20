<?php
/**
 * Filters label template
 */

if ( isset( $args['show_label'] ) ) {
	$show_label = filter_var( $args['show_label'], FILTER_VALIDATE_BOOLEAN );
} else {
	$show_label = false;
}

if ( ! $show_label || empty( $args['filter_label'] ) ) {
	return;
}

?>
<div class="jet-filter-label"><?php echo $args['filter_label']; ?></div>
