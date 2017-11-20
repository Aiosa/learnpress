<?php
/**
 * Class LP_Question_Post_Type
 *
 * @author  ThimPress
 * @package LearnPress/Classes
 * @version 3.0.0
 */

/**
 * Prevent loading this file directly
 */
defined( 'ABSPATH' ) || exit();


if ( ! class_exists( 'LP_Question_Post_Type' ) ) {

	/**
	 * Class LP_Question_Post_Type
	 */
	class LP_Question_Post_Type extends LP_Abstract_Post_Type {
		/**
		 * @var null
		 */
		protected static $_instance = null;

		/**
		 * @var array
		 */
		public static $metaboxes = array();

		/**
		 * LP_Question_Post_Type constructor.
		 *
		 * @param $post_type
		 * @param mixed
		 */
		public function __construct( $post_type, $args = '' ) {
			add_action( 'admin_head', array( $this, 'init' ) );

			add_action( 'edit_form_after_editor', array( __CLASS__, 'template_question_editor' ) );
			add_action( 'learn-press/admin/after-enqueue-scripts', array( $this, 'data_question_editor' ) );

//			$this->add_map_method( 'before_delete', 'delete_question_answers' );

			parent::__construct( $post_type, $args );
		}


		/**
		 * JS template for admin question editor.
		 *
		 * @since 3.0.0
		 */
		public static function template_question_editor() {
			if ( LP_QUESTION_CPT !== get_post_type() ) {
				return;
			}
			learn_press_admin_view( 'question/editor' );
		}

		/**
		 * Load data for question editor.
		 *
		 * @since 3.0.0
		 */
		public function data_question_editor() {

			if ( LP_QUESTION_CPT !== get_post_type() ) {
				return;
			}

			global $post, $pagenow;

			// add default answer for new question
			if ( $pagenow === 'post-new.php' ) {
				$question = LP_Question::get_question( $post->ID, array( 'type' => apply_filters( 'learn-press/default-add-new-question-type', 'true_or_false' ) ) );
				$answers  = $question->get_default_answers();
			} else {
				$question = LP_Question::get_question( $post->ID );
				$answers  = $question->get_data( 'answer_options' );
			}

			wp_localize_script( 'learn-press-admin-question-editor', 'lp_question_editor', array(
				'root' => array(
					'id'                  => $post->ID,
					'open'                => false,
					'title'               => get_the_title( $post->ID ),
					'type'                => array(
						'key'   => $question->get_type(),
						'label' => $question->get_type_label()
					),
					'answers'             => $answers,
					'ajax'                => admin_url( '' ),
					'action'              => 'admin_question_editor',
					'nonce'               => wp_create_nonce( 'learnpress_admin_question_editor' ),
					'questionTypes'       => LP_Question_Factory::get_types(),
					'pageNow'             => $pagenow
				)
			) );

		}


		/**
		 * Delete all question answers when delete question.
		 *
		 * @since 3.0.0
		 *
		 * @param $question_id
		 */
		public function delete_question_answers( $question_id ) {
			// question curd
			$curd = new LP_Question_CURD();
			// remove all answer of quesion
			$curd->delete_question_answers( $question_id );
		}

		/**
		 * Init question.
		 *
		 * @since 3.0.0
		 */
		public function init() {
			global $pagenow, $post_type;
			$hidden = get_user_meta( get_current_user_id(), 'manageedit-lp_questioncolumnshidden', true );
			if ( ! is_array( $hidden ) && empty( $hidden ) ) {
				update_user_meta( get_current_user_id(), 'manageedit-lp_questioncolumnshidden', array( 'taxonomy-question-tag' ) );
			}
		}

		/**
		 * Register question post type
		 */
		public function register() {
			register_taxonomy( 'question_tag', array( LP_QUESTION_CPT ),
				array(
					'labels'            => array(
						'name'          => __( 'Question Tag', 'learnpress' ),
						'menu_name'     => __( 'Tag', 'learnpress' ),
						'singular_name' => __( 'Tag', 'learnpress' ),
						'add_new_item'  => __( 'Add New Tag', 'learnpress' ),
						'all_items'     => __( 'All Tags', 'learnpress' )
					),
					'public'            => true,
					'hierarchical'      => false,
					'show_ui'           => true,
					'show_admin_column' => 'true',
					'show_in_nav_menus' => true,
					'rewrite'           => array(
						'slug'         => 'question-tag',
						'hierarchical' => false,
						'with_front'   => false
					),
				)
			);
			add_post_type_support( 'question', 'comments' );

			return array(
				'labels'             => array(
					'name'               => __( 'Question Bank', 'learnpress' ),
					'menu_name'          => __( 'Question Bank', 'learnpress' ),
					'singular_name'      => __( 'Question', 'learnpress' ),
					'all_items'          => __( 'Questions', 'learnpress' ),
					'view_item'          => __( 'View Question', 'learnpress' ),
					'add_new_item'       => __( 'Add New Question', 'learnpress' ),
					'add_new'            => __( 'Add New', 'learnpress' ),
					'edit_item'          => __( 'Edit Question', 'learnpress' ),
					'update_item'        => __( 'Update Question', 'learnpress' ),
					'search_items'       => __( 'Search Questions', 'learnpress' ),
					'not_found'          => __( 'No questions found', 'learnpress' ),
					'not_found_in_trash' => __( 'No questions found in trash', 'learnpress' ),
				),
				'public'             => false, // disable access directly via permalink url
				'publicly_queryable' => false,
				'show_ui'            => true,
				'has_archive'        => false,
				'capability_type'    => LP_LESSON_CPT,
				'map_meta_cap'       => true,
				'show_in_menu'       => 'learn_press',
				'show_in_admin_bar'  => true,
				'show_in_nav_menus'  => true,
				'supports'           => array( 'title', 'editor', 'revisions' ),
				'hierarchical'       => true,
				'rewrite'            => array( 'slug' => 'questions', 'hierarchical' => true, 'with_front' => false )
			);
		}

		/**
		 * Add question meta box settings.
		 */
		public function add_meta_boxes() {
			self::$metaboxes['general_settings'] = new RW_Meta_Box( self::settings_meta_box() );
			parent::add_meta_boxes();
		}

		/**
		 * Register question meta box settings.
		 *
		 * @return mixed
		 */
		public static function settings_meta_box() {
			$prefix   = '_lp_';
			$meta_box = array(
				'id'     => 'question_settings',
				'title'  => __( 'Settings', 'learnpress' ),
				'pages'  => array( LP_QUESTION_CPT ),
				'fields' => array(
					array(
						'name'  => __( 'Mark for this question', 'learnpress' ),
						'id'    => "{$prefix}mark",
						'type'  => 'number',
						'clone' => false,
						'desc'  => __( 'Mark for choosing the right answer.', 'learnpress' ),
						'min'   => 1,
						'std'   => 1
					),
					array(
						'name' => __( 'Question explanation', 'learnpress' ),
						'id'   => "{$prefix}explanation",
						'type' => 'textarea',
						'desc' => __( 'Explain why an option is true and other is false. The text will be shown when user click on \'Check answer\' button.', 'learnpress' ),
						'std'  => null
					),
					array(
						'name' => __( 'Question hint', 'learnpress' ),
						'id'   => "{$prefix}hint",
						'type' => 'textarea',
						'desc' => __( 'Instruction for user to select the right answer. The text will be shown when user clicking \'Hint\' button.', 'learnpress' ),
						'std'  => null
					)
				)
			);

			return apply_filters( 'learn_press_question_meta_box_args', $meta_box );
		}

		/**
		 * Add columns to admin manage question page
		 *
		 * @param  array $columns
		 *
		 * @return array
		 */
		public function columns_head( $columns ) {
			$pos         = array_search( 'title', array_keys( $columns ) );
			$new_columns = array(
				'author'  => __( 'Author', 'learnpress' ),
				'lp_quiz' => __( 'Quiz', 'learnpress' ),
				'type'    => __( 'Type', 'learnpress' )
			);

			if ( false !== $pos && ! array_key_exists( 'lp_quiz', $columns ) ) {
				$columns = array_merge(
					array_slice( $columns, 0, $pos + 1 ),
					$new_columns,
					array_slice( $columns, $pos + 1 )
				);
			}

			$user = wp_get_current_user();
			if ( in_array( LP_TEACHER_ROLE, $user->roles ) ) {
				unset( $columns['author'] );
			}

			return $columns;
		}

		/**
		 * Displaying the content of extra columns
		 *
		 * @param $name
		 * @param $post_id
		 */
		public function columns_content( $name, $post_id = 0 ) {
			switch ( $name ) {
				case 'lp_quiz':
					$quizzes = learn_press_get_question_quizzes( $post_id );
					if ( $quizzes ) {
						foreach ( $quizzes as $quiz ) {
							echo '<div><a href="' . esc_url( add_query_arg( array( 'filter_quiz' => $quiz->ID ) ) ) . '">' . get_the_title( $quiz->ID ) . '</a>';
							echo '<div class="row-actions">';
							printf( '<a href="%s">%s</a>', admin_url( sprintf( 'post.php?post=%d&action=edit', $quiz->ID ) ), __( 'Edit', 'learnpress' ) );
							echo "&nbsp;|&nbsp;";
							printf( '<a href="%s">%s</a>', get_the_permalink( $quiz->ID ), __( 'View', 'learnpress' ) );
							echo "&nbsp;|&nbsp;";
							if ( $quiz_id = learn_press_get_request( 'filter_quiz' ) ) {
								printf( '<a href="%s">%s</a>', remove_query_arg( 'filter_quiz' ), __( 'Remove Filter', 'learnpress' ) );
							} else {
								printf( '<a href="%s">%s</a>', add_query_arg( 'filter_quiz', $quiz->ID ), __( 'Filter', 'learnpress' ) );
							}
							echo '</div></div>';
						}

					} else {
						_e( 'Not assigned yet', 'learnpress' );
					}

					break;
				case 'type':
					echo learn_press_question_name_from_slug( get_post_meta( $post_id, '_lp_type', true ) );
			}
		}

		/**
		 * Posts_join_paged.
		 *
		 * @param $join
		 *
		 * @return string
		 */
		public function posts_join_paged( $join ) {
			if ( ! $this->_is_archive() ) {
				return $join;
			}
			global $wpdb;
			if ( $quiz_id = $this->_filter_quiz() || ( $this->_get_orderby() == 'quiz-name' ) ) {
				$join .= " LEFT JOIN {$wpdb->prefix}learnpress_quiz_questions qq ON {$wpdb->posts}.ID = qq.question_id";
				$join .= " LEFT JOIN {$wpdb->posts} q ON q.ID = qq.quiz_id";
			}

			return $join;
		}

		/**
		 * @param $where
		 *
		 * @return mixed|string
		 */
		public function posts_where_paged( $where ) {

			if ( ! $this->_is_archive() ) {
				return $where;
			}

			global $wpdb;
			if ( $quiz_id = $this->_filter_quiz() ) {
				$where .= $wpdb->prepare( " AND (q.ID = %d)", $quiz_id );
			}

			return $where;
		}

		/**
		 * @param $order_by_statement
		 *
		 * @return string
		 */
		public function posts_orderby( $order_by_statement ) {
			if ( ! $this->_is_archive() ) {
				return $order_by_statement;
			}
			if ( isset ( $_GET['orderby'] ) && isset ( $_GET['order'] ) ) {
				switch ( $_GET['orderby'] ) {
					case 'quiz-name':
						$order_by_statement = "q.post_title {$_GET['order']}";
						break;
				}
			}

			return $order_by_statement;
		}

		/**
		 * @param $columns
		 *
		 * @return mixed
		 */
		public function sortable_columns( $columns ) {
			$columns['author']  = 'author';
			$columns['lp_quiz'] = 'quiz-name';

			return $columns;
		}

		/**
		 * @return bool
		 */
		private function _is_archive() {
			global $pagenow, $post_type;
			if ( ! is_admin() || ( $pagenow != 'edit.php' ) || ( LP_QUESTION_CPT != $post_type ) ) {
				return false;
			}

			return true;
		}

		/**
		 * @return bool|int
		 */
		private function _filter_quiz() {
			return ! empty( $_REQUEST['filter_quiz'] ) ? absint( $_REQUEST['filter_quiz'] ) : false;
		}

		/**
		 * @return string
		 */
		private function _get_orderby() {
			return isset( $_REQUEST['orderby'] ) ? $_REQUEST['orderby'] : '';
		}

		/**
		 * Quiz assigned view.
		 *
		 * @since 3.0.0
		 */
		public static function question_assigned() {
			learn_press_admin_view( 'meta-boxes/quiz/assigned.php' );
		}

		/**
		 * @return LP_Question_Post_Type|null
		 */
		public static function instance() {
			if ( ! self::$_instance ) {
				$args            = array(
					'default_meta' => array(
						'_lp_mark' => 1,
						'_lp_type' => 'true_or_false'
					)
				);
				self::$_instance = new self( LP_QUESTION_CPT, $args );
			}

			return self::$_instance;
		}
	}

	// LP_Question_Post_Type
	$question_post_type = LP_Question_Post_Type::instance();

	// add meta box
	$question_post_type
		->add_meta_box( 'lesson_assigned', __( 'Assigned', 'learnpress' ), 'question_assigned', 'side', 'high' );
}