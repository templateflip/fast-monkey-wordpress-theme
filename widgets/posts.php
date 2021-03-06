<?php
/*-----------------------------------------------------------------------------------*/
/*	Posts Widget Class
/*  Borrowed and modified from Shamrock https://wordpress.org/themes/fast-monkey/ License: GPL V2
/*-----------------------------------------------------------------------------------*/

class FastMonkey_Posts_Widget extends WP_Widget {

	var $defaults;

	function __construct() {
		$widget_ops = array( 'classname' => 'fast_monkey_posts_widget', 'description' => __( 'Display your posts with this widget', 'fast-monkey' ) );
		$control_ops = array( 'id_base' => 'fast_monkey_posts_widget' );
		parent::__construct( 'fast_monkey_posts_widget', __( 'FastMonkey Posts', 'fast-monkey' ), $widget_ops, $control_ops );

		$this->defaults = array(
			'title' => __( 'Featured Posts', 'fast-monkey' ),
			'numposts' => 5,
			'category' => array(),
			'orderby' => 0,
			'date_limit' => 0,
			'auto_detect' => 0,
			'meta' => 0,
			'manual' => array()
		);
	}


	function widget( $args, $instance ) {
		extract( $args );
		$instance = wp_parse_args( (array) $instance, $this->defaults );

		echo $before_widget;

		$title = apply_filters( 'widget_title', $instance['title'] );

		if ( !empty( $title ) ) {
			echo $before_title . $title . $after_title;
		}

		$q_args = array(
			'post_type'=> 'post',
			'posts_per_page' => $instance['numposts'],
			'ignore_sticky_posts' => 1,
			'orderby' => $instance['orderby']
		);


		if ( !empty( $instance['manual'] ) && !empty( $instance['manual'][0] ) ) {
			$q_args['posts_per_page'] = absint( count( $instance['manual'] ) );
			$q_args['orderby'] =  'post__in';
			$q_args['post__in'] =  $instance['manual'];
			$q_args['post_type'] = array_keys( get_post_types( array( 'public' => true ) ) ); //support all existing public post types

		} else {

			if ( !empty( $instance['auto_detect'] ) && is_single() ) {

				$cats = get_the_category();

				if ( !empty( $cats ) ) {
					foreach ( $cats as $k => $cat ) {
						$q_args['category__in'][] = $cat->term_id;
					}
				}

			} else {

				if ( !empty( $instance['category'] ) ) {
					$q_args['category__in'] = (array)$instance['category'];
				}
			}

			if ( $instance['orderby'] == 'views' && function_exists( 'ev_get_meta_key' ) ) {
				$q_args['orderby'] = 'meta_value_num';
				$q_args['meta_key'] = ev_get_meta_key();
			}


			if ( !empty( $instance['date_limit'] ) ) {
				$q_args['date_query'] = array(
					'after' => date( 'Y-m-d', strtotime( $instance['date_limit'] ) )
				);
			}
		}

		$fast_monkey_posts = new WP_Query( $q_args );

		if ( $fast_monkey_posts->have_posts() ): ?>
			<ul class="tm-posts-widget uk-list uk-list-space">

				<?php while ( $fast_monkey_posts->have_posts() ) : $fast_monkey_posts->the_post(); ?>

			 		<li>
			 			<a href="<?php echo esc_url( get_permalink() ); ?>"  title="<?php echo esc_attr( get_the_title() ); ?>">
			 				<?php if(has_post_thumbnail()): ?>
			 					<?php the_post_thumbnail(array(48, 48), array( 'class' => 'tm-post-widget-img uk-border-rounded uk-float-left' ) ); ?>
			 				<?php else: ?>
			 					<?php echo fast_monkey_placeholder_img(); ?>
			 				<?php endif; ?>

			 			</a>
			 			<a href="<?php echo esc_url( get_permalink() ); ?>" title="<?php echo esc_attr( get_the_title() ); ?>"><?php the_title(); ?></a>
			 			<?php if ( !empty( $instance['meta'] ) && $meta = fast_monkey_get_meta_data( $instance['meta'] ) ): ?>
			 				<div class="entry-meta"><?php echo $meta;?></div>
			 			<?php endif; ?>
			 		</li>
				<?php endwhile; ?>

			  </ul>
		<?php endif; ?>

		<?php wp_reset_postdata(); ?>

		<?php
		echo $after_widget;
	}


	function update( $new_instance, $old_instance ) {
		$instance = $old_instance;
		$instance['title'] = strip_tags( $new_instance['title'] );
		$instance['orderby'] = $new_instance['orderby'];
		$instance['category'] = $new_instance['category'];
		$instance['numposts'] = absint( $new_instance['numposts'] );
		$instance['date_limit'] = $new_instance['date_limit'];
		$instance['auto_detect'] = isset( $new_instance['auto_detect'] ) ? 1 : 0;
		$instance['meta'] = $new_instance['meta'];
		$instance['manual'] = !empty( $new_instance['manual'] ) ? explode( ",", $new_instance['manual'] ) : array();

		return $instance;
	}

	function form( $instance ) {

		$instance = wp_parse_args( (array) $instance, $this->defaults ); ?>

		<p>
			<label for="<?php echo $this->get_field_id( 'title' ); ?>"><?php _e( 'Title', 'fast-monkey' ); ?>:</label>
			<input id="<?php echo $this->get_field_id( 'title' ); ?>" type="text" name="<?php echo $this->get_field_name( 'title' ); ?>" value="<?php echo esc_attr( $instance['title'] ); ?>" class="widefat" />
		</p>

		<p>
	   	 	<label for="<?php echo $this->get_field_id( 'numposts' ); ?>"><?php _e( 'Number of posts to show', 'fast-monkey' ); ?>:</label>
		 	<input id="<?php echo $this->get_field_id( 'numposts' ); ?>" type="text" name="<?php echo $this->get_field_name( 'numposts' ); ?>" value="<?php echo absint( $instance['numposts'] ); ?>" class="small-text" />
	  	</p>

	  <p>
	  	<?php $this->widget_tax( $this, 'category', $instance['category'] ); ?>
	  </p>

	  <p>
		<input id="<?php echo $this->get_field_id( 'auto_detect' ); ?>" type="checkbox" name="<?php echo $this->get_field_name( 'auto_detect' ); ?>" value="1" <?php checked( 1, $instance['auto_detect'] ); ?>/>
		<label for="<?php echo $this->get_field_id( 'auto_detect' ); ?>"><?php _e( 'Auto detect category', 'fast-monkey' ); ?></label>
		<small class="howto"><?php _e( 'If sidebar is used on single post template, display posts from current post category ', 'fast-monkey' ); ?></small>
	  </p>

	   <p>
	  	<?php $this->widget_orderby( $this, $instance['orderby'] ); ?>
	   </p>

	<p>
		<label for="<?php echo $this->get_field_id( 'date_limit' ); ?>"><?php _e( 'Only select posts which are not older than', 'fast-monkey' ); ?>:</label>
		<select id="<?php echo $this->get_field_id( 'date_limit' ); ?>" type="text" name="<?php echo $this->get_field_name( 'date_limit' ); ?>" class="widefat">
			<?php $dates = $this->dates_q(); ?>
			<?php foreach ( $dates as $key => $value ): ?>
				<option value="<?php echo esc_attr($key); ?>" <?php selected( $instance['date_limit'], $key, true ); ?>><?php echo $value;?></option>
			<?php endforeach; ?>
		</select>
	</p>

	<p>
	   	 <label for="<?php echo $this->get_field_id( 'manual' ); ?>"><?php _e( 'Choose manually', 'fast-monkey' ); ?>:</label>
		 <input id="<?php echo $this->get_field_id( 'manual' ); ?>" type="text" name="<?php echo $this->get_field_name( 'manual' ); ?>" value="<?php echo esc_attr(implode( ",", $instance['manual'] )); ?>" class="widefat" />
		 <small class="howto"><?php _e( 'Specify post ids separated by comma if you want to select only those posts. i.e. 213,32,12,45 Note: you can also choose pages as well as custom post types', 'fast-monkey' ); ?></small>
	</p>

	<p>
	  		<?php $this->widget_meta( $this, $instance['meta'] ); ?>
	</p>



	<?php
	}

	function dates_q() {
		$dates = array(
			'-1 day' => __( '1 Day', 'fast-monkey' ),
			'-1 week' => __( '1 Week', 'fast-monkey' ),
			'-1 month' => __( '1 Month', 'fast-monkey' ),
			'-3 months' => __( '3 Months', 'fast-monkey' ),
			'-6 months' => __( '6 Months', 'fast-monkey' ),
			'-1 year' => __( '1 Year', 'fast-monkey' ),
			'0' => __( 'Select all posts', 'fast-monkey' )
		);

		return $dates;
	}

	function widget_orderby( $widget_instance = false, $orderby = false ) {

		$orders['date'] = __( 'Published date', 'fast-monkey' );
		$orders['comment_count'] = __( 'Number of comments', 'fast-monkey' );
		$orders['rand'] = __( 'Random', 'fast-monkey' );

		if ( !empty( $widget_instance ) ) { ?>
				<label for="<?php echo $widget_instance->get_field_id( 'orderby' ); ?>"><?php _e( 'Order by:', 'fast-monkey' ); ?></label>
				<select id="<?php echo $widget_instance->get_field_id( 'orderby' ); ?>" name="<?php echo $widget_instance->get_field_name( 'orderby' ); ?>" class="widefat">
					<?php foreach ( $orders as $key => $order ) { ?>
						<option value="<?php echo $key; ?>" <?php selected( $orderby, $key );?>><?php echo $order; ?></option>
					<?php } ?>
				</select>
		<?php }
	}

	function widget_tax( $widget_instance, $taxonomy, $selected_taxonomy = false ) {
		if ( !empty( $widget_instance ) && !empty( $taxonomy ) ) {
			$categories = get_terms( $taxonomy, 'orderby=name&hide_empty=0' );
?>
				<label for="<?php echo $widget_instance->get_field_id( 'category' ); ?>"><?php _e( 'Choose from:', 'fast-monkey' ); ?></label><br/>
					<?php foreach ( $categories as $category ) { ?>
						<input type="checkbox" name="<?php echo $widget_instance->get_field_name( 'category' ); ?>[]" value="<?php echo $category->term_id; ?>" <?php echo in_array( $category->term_id, (array)$selected_taxonomy ) ? 'checked': ''?> /> <?php echo $category->name; ?><br/>
					<?php } ?>
		<?php }
	}


	function widget_meta( $widget_instance = false, $current = false ) {

		$metas = array(
			'0' => __( 'None', 'fast-monkey' ),
			'date' => __( 'Date/time', 'fast-monkey' ),
			'author' => __( 'Author', 'fast-monkey' ),
			'comments' => __( 'Comments', 'fast-monkey' ),
			'rtime' => __( 'Reading time', 'fast-monkey' )
		);

		if ( !empty( $widget_instance ) ) { ?>
				<label for="<?php echo $widget_instance->get_field_id( 'meta' ); ?>"><?php _e( 'Display meta data:', 'fast-monkey' ); ?></label>
				<select id="<?php echo $widget_instance->get_field_id( 'meta' ); ?>" name="<?php echo $widget_instance->get_field_name( 'meta' ); ?>" class="widefat">
					<?php foreach ( $metas as $id => $title ) { ?>
						<option value="<?php echo $id; ?>" <?php selected( $current, $id );?>><?php echo $title; ?></option>
					<?php } ?>
				</select>
		<?php }
	}


}

?>
