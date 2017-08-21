<?php
/**
 * Template for displaying the price of a course.
 *
 * @author  ThimPress
 * @package LearnPress/Templates
 * @version 2.1.4.2
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}
$lp_user   = learn_press_get_current_user();
$lp_course = learn_press_get_course();

if ( $lp_user->has_enrolled_course( $lp_course->get_id() ) ) {
	return;
}

if ( $price = $lp_course->get_price_html() ) {
	return;
}

if ( $lp_course->has_sale_price() ) {
	?>
    <span class="course-origin-price">
		<?php echo $lp_course->get_origin_price_html(); ?>
	</span>
	<?php
}

?>
<span class="course-price">
	<?php echo $price; ?>
</span>

