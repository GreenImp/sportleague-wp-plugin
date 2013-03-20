<?php
/**
 * Author: lee
 * Date Created: 28/08/2012 14:55
 */

class sportLeagueLatestMatchWidget extends WP_Widget{
	public function sportLeagueLatestMatchWidget(){
		$widget_ops = array(
			'classname' => 'sportLeagueLatestMatchWidget',
			'description' => 'Displays the last played fixture'
		);
		$this->WP_Widget('sportLeagueLatestMatchWidget', 'Sport League Last Fixture', $widget_ops);
	}

	public function form($instance){
		$instance = wp_parse_args( (array) $instance, array( 'title' => '' ) );
		$title = $instance['title'];
		?>
		<p><label for="<?php echo $this->get_field_id('title'); ?>">Title: <input class="widefat" id="<?php echo $this->get_field_id('title'); ?>" name="<?php echo $this->get_field_name('title'); ?>" type="text" value="<?php echo esc_attr($title); ?>" /></label></p>
		<?php
	}

	public function update($new_instance, $old_instance){
		$instance = $old_instance;
		$instance['title'] = $new_instance['title'];
		return $instance;
	}

	function widget($args, $instance){
		extract($args, EXTR_SKIP);

		echo $args['before_widget'];
		if(!empty($instance['title'])){
			echo $args['before_title'];
			echo esc_html( $instance['title'] );
			echo $args['after_title'];
		}

		echo sportLeagueLatestMatch();

		echo $args['after_widget'];
	}
}

class sportLeagueNextMatchWidget extends WP_Widget{
	public function sportLeagueNextMatchWidget(){
		$widget_ops = array(
			'classname' => 'sportLeagueNextMatchWidget',
			'description' => 'Displays the next fixture'
		);
		$this->WP_Widget('sportLeagueNextMatchWidget', 'Sport League Next Fixture', $widget_ops);
	}

	public function form($instance){
		$instance = wp_parse_args( (array) $instance, array( 'title' => '' ) );
		$title = $instance['title'];
		?>
		<p><label for="<?php echo $this->get_field_id('title'); ?>">Title: <input class="widefat" id="<?php echo $this->get_field_id('title'); ?>" name="<?php echo $this->get_field_name('title'); ?>" type="text" value="<?php echo esc_attr($title); ?>" /></label></p>
		<?php
	}

	public function update($new_instance, $old_instance){
		$instance = $old_instance;
		$instance['title'] = $new_instance['title'];
		return $instance;
	}

	function widget($args, $instance){
		extract($args, EXTR_SKIP);

		echo $args['before_widget'];
		if(!empty($instance['title'])){
			echo $args['before_title'];
			echo esc_html( $instance['title'] );
			echo $args['after_title'];
		}

		echo sportLeagueLatestMatch();

		echo $args['after_widget'];
	}
}




function sportLeague_register_widgets(){
	register_widget('sportLeagueLatestMatchWidget');
	register_widget('sportLeagueNextMatchWidget');
}

add_action('widgets_init', 'sportLeague_register_widgets');