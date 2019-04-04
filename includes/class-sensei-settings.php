<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/*
 * Sensei Settings Class
 *
 * All functionality pertaining to the settings in Sensei.
 *
 * @package Core
 * @author Automattic
 *
 * @since 1.0.0
 */
class Sensei_Settings extends Sensei_Settings_API {

	/**
	 * Constructor.
	 *
	 * @access public
	 * @since 1.0.0
	 */
	public function __construct() {
		parent::__construct(); // Required in extended classes.

		add_action( 'init', array( __CLASS__, 'flush_rewrite_rules' ) );

		// Setup Admin Settings data
		if ( is_admin() ) {

			$this->has_tabs   = true;
			$this->name       = __( 'Sensei Settings', 'sensei-lms' );
			$this->menu_label = __( 'Settings', 'sensei-lms' );
			$this->page_slug  = 'sensei-settings';

		} // End If Statement

		$this->register_hook_listener();
		$this->get_settings();

	} // End __construct()

	/**
	 * Get settings value
	 *
	 * @since 1.9.0
	 * @param string $setting_name
	 * @return mixed
	 */
	public function get( $setting_name ) {

		if ( isset( $this->settings[ $setting_name ] ) ) {

			return $this->settings[ $setting_name ];

		}

		return false;
	}

	/**
	 * @since 1.9.0
	 *
	 * @param $setting
	 * @param $new_value
	 */
	public function set( $setting, $new_value ) {

		$settings             = self::get_settings_raw();
		$settings[ $setting ] = $new_value;
		return update_option( $this->token, $settings );

	}

	/**
	 * Register the settings screen within the WordPress admin.
	 *
	 * @access public
	 * @since  1.0.0
	 * @return void
	 */
	public function register_settings_screen() {

		$this->settings_version = Sensei()->version; // Use the global plugin version on this settings screen.
		$hook                   = add_submenu_page( 'sensei', $this->name, $this->menu_label, 'manage_sensei', $this->page_slug, array( $this, 'settings_screen' ) );
		$this->hook             = $hook;

		if ( isset( $_GET['page'] ) && ( $_GET['page'] == $this->page_slug ) ) {
			add_action( 'admin_notices', array( $this, 'settings_errors' ) );
			add_action( 'admin_print_scripts', array( $this, 'enqueue_scripts' ) );
			add_action( 'admin_print_styles', array( $this, 'enqueue_styles' ) );
		}
	} // End register_settings_screen()

	/**
	 * Add settings sections.
	 *
	 * @access public
	 * @since  1.0.0
	 * @return void
	 */
	public function init_sections() {
		$sections = array();

		$sections['default-settings'] = array(
			'name'        => __( 'General', 'sensei-lms' ),
			'description' => __( 'Settings that apply to the entire plugin.', 'sensei-lms' ),
		);

		$sections['course-settings'] = array(
			'name'        => __( 'Courses', 'sensei-lms' ),
			'description' => __( 'Settings that apply to all Courses.', 'sensei-lms' ),
		);

		$sections['lesson-settings'] = array(
			'name'        => __( 'Lessons', 'sensei-lms' ),
			'description' => __( 'Settings that apply to all Lessons.', 'sensei-lms' ),
		);

		$sections['email-notification-settings'] = array(
			'name'        => __( 'Email Notifications', 'sensei-lms' ),
			'description' => __( 'Settings for email notifications sent from your site.', 'sensei-lms' ),
		);

		$sections['learner-profile-settings'] = array(
			'name'        => __( 'Learner Profiles', 'sensei-lms' ),
			'description' => __( 'Settings for public Learner Profiles.', 'sensei-lms' ),
		);

		$this->sections = apply_filters( 'sensei_settings_tabs', $sections );
	} // End init_sections()

	/**
	 * Add settings fields.
	 *
	 * @access public
	 * @since  1.0.0
	 * @uses   Sensei_Utils::get_slider_types()
	 * @return void
	 */
	public function init_fields() {
		global $pagenow;

		$pages_array          = $this->pages_array();
		$posts_per_page_array = array(
			'0'  => '0',
			'1'  => '1',
			'2'  => '2',
			'3'  => '3',
			'4'  => '4',
			'5'  => '5',
			'6'  => '6',
			'7'  => '7',
			'8'  => '8',
			'9'  => '9',
			'10' => '10',
			'11' => '11',
			'12' => '12',
			'13' => '13',
			'14' => '14',
			'15' => '15',
			'16' => '16',
			'17' => '17',
			'18' => '18',
			'19' => '19',
			'20' => '20',
		);
		$complete_settings    = array(
			'passed'   => __( 'Once all the course lessons have been completed', 'sensei-lms' ),
			'complete' => __( 'At any time (by clicking the \'Complete Course\' button)', 'sensei-lms' ),
		);
		$quiz_points_formats  = array(
			'none'     => __( "Don't show quiz question points", 'sensei-lms' ),
			'number'   => __( 'Number (e.g. 1. Default)', 'sensei-lms' ),
			'brackets' => __( 'Brackets (e.g. [1])', 'sensei-lms' ),
			'text'     => __( 'Text (e.g. Points: 1)', 'sensei-lms' ),
			'full'     => __( 'Text and Brackets (e.g. [Points: 1])', 'sensei-lms' ),
		);
		$fields               = array();

		$fields['access_permission'] = array(
			'name'        => __( 'Access Permissions', 'sensei-lms' ),
			'description' => __( 'Users must be logged in to view lesson content.', 'sensei-lms' ),
			'type'        => 'checkbox',
			'default'     => true,
			'section'     => 'default-settings',
		);

		$fields['messages_disable'] = array(
			'name'        => __( 'Disable Private Messages', 'sensei-lms' ),
			'description' => __( 'Disable the private message functions between learners and teachers.', 'sensei-lms' ),
			'type'        => 'checkbox',
			'default'     => false,
			'section'     => 'default-settings',
		);

		$fields['course_page'] = array(
			'name'        => __( 'Course Archive Page', 'sensei-lms' ),
			'description' => __( 'The page to use to display courses. If you leave this blank the default custom post type archive will apply.', 'sensei-lms' ),
			'type'        => 'select',
			'default'     => get_option( 'woothemes-sensei_courses_page_id', 0 ),
			'section'     => 'default-settings',
			'required'    => 0,
			'options'     => $pages_array,
		);

		$fields['my_course_page'] = array(
			'name'        => __( 'My Courses Page', 'sensei-lms' ),
			'description' => __( 'The page to use to display the courses that a user is currently taking as well as the courses a user has complete.', 'sensei-lms' ),
			'type'        => 'select',
			'default'     => get_option( 'woothemes-sensei_user_dashboard_page_id', 0 ),
			'section'     => 'default-settings',
			'required'    => 0,
			'options'     => $pages_array,
		);

		$fields['placeholder_images_enable'] = array(
			'name'        => __( 'Use placeholder images', 'sensei-lms' ),
			'description' => __( 'Output a placeholder image when no featured image has been specified for Courses and Lessons.', 'sensei-lms' ),
			'type'        => 'checkbox',
			'default'     => false,
			'section'     => 'default-settings',
		);

		$fields['styles_disable']              = array(
			'name'        => __( 'Disable Sensei Styles', 'sensei-lms' ),
			'description' => __( 'Prevent the frontend stylesheets from loading. This will remove the default styles for all Sensei elements.', 'sensei-lms' ),
			'type'        => 'checkbox',
			'default'     => false,
			'section'     => 'default-settings',
		);
		$fields['quiz_question_points_format'] = array(
			'name'        => __( 'Quiz question points format', 'sensei-lms' ),
			'description' => __( 'Set the quiz question points format', 'sensei-lms' ),
			'type'        => 'select',
			'default'     => 'number',
			'section'     => 'default-settings',
			'options'     => $quiz_points_formats,
		);

		$fields['js_disable'] = array(
			'name'        => __( 'Disable Sensei Javascript', 'sensei-lms' ),
			'description' => __( 'Prevent the frontend javascript from loading. This affects the progress bars and the My Courses tabs.', 'sensei-lms' ),
			'type'        => 'checkbox',
			'default'     => false,
			'section'     => 'default-settings',
		);

		$fields['sensei_video_embed_html_sanitization_disable'] = array(
			'name'        => __( 'Disable HTML security', 'sensei-lms' ),
			'description' => __( 'Allow any HTML tags in the Video Embed field. Warning: Enabling this may leave your site more vulnerable to XSS attacks', 'sensei-lms' ),
			'type'        => 'checkbox',
			'default'     => false,
			'section'     => 'default-settings',
		);

		$fields['sensei_delete_data_on_uninstall'] = array(
			'name'        => __( 'Delete data on uninstall', 'sensei-lms' ),
			'description' => __( 'Delete Sensei data when the plugin is deleted. Once removed, this data cannot be restored.', 'sensei-lms' ),
			'type'        => 'checkbox',
			'default'     => false,
			'section'     => 'default-settings',
		);

		// Course Settings
		$fields['course_completion'] = array(
			'name'        => __( 'Courses are complete:', 'sensei-lms' ),
			'description' => __( 'This will determine when courses are marked as complete.', 'sensei-lms' ),
			'type'        => 'select',
			'default'     => 'passed',
			'section'     => 'course-settings',
			'required'    => 0,
			'options'     => $complete_settings,
		);

		$fields['course_author'] = array(
			'name'        => __( 'Display Course Author', 'sensei-lms' ),
			'description' => __( 'Output the Course Author on Course archive and My Courses page.', 'sensei-lms' ),
			'type'        => 'checkbox',
			'default'     => true,
			'section'     => 'course-settings',
		);

		$fields['my_course_amount'] = array(
			'name'        => __( 'My Courses Pagination', 'sensei-lms' ),
			'description' => __( 'The number of courses to output for the my courses page.', 'sensei-lms' ),
			'type'        => 'range',
			'default'     => '0',
			'section'     => 'course-settings',
			'required'    => 0,
			'options'     => $posts_per_page_array,
		);

		$fields['course_archive_image_enable'] = array(
			'name'        => __( 'Course Archive Image', 'sensei-lms' ),
			'description' => __( 'Output the Course Image on the Course Archive Page.', 'sensei-lms' ),
			'type'        => 'checkbox',
			'default'     => true,
			'section'     => 'course-settings',
		);

		$fields['course_archive_image_width'] = array(
			'name'        => __( 'Image Width - Archive', 'sensei-lms' ),
			'description' => __( 'The width in pixels of the featured image for the Course Archive page.', 'sensei-lms' ),
			'type'        => 'text',
			'default'     => '100',
			'section'     => 'course-settings',
			'required'    => 0,
		);

		$fields['course_archive_image_height'] = array(
			'name'        => __( 'Image Height - Archive', 'sensei-lms' ),
			'description' => __( 'The height in pixels of the featured image for the Course Archive page.', 'sensei-lms' ),
			'type'        => 'text',
			'default'     => '100',
			'section'     => 'course-settings',
			'required'    => 0,
		);

		$fields['course_archive_image_hard_crop'] = array(
			'name'        => __( 'Image Hard Crop - Archive', 'sensei-lms' ),
			// translators: Placeholders are an opening and closing <a> tag linking to the documentation page.
			'description' => sprintf( __( 'After changing this setting, you may need to %1$sregenerate your thumbnails%2$s.', 'sensei-lms' ), '<a href="' . esc_url( 'http://wordpress.org/extend/plugins/regenerate-thumbnails/' ) . '">', '</a>' ),
			'type'        => 'checkbox',
			'default'     => false,
			'section'     => 'course-settings',
		);

		$fields['course_single_image_enable'] = array(
			'name'        => __( 'Single Course Image', 'sensei-lms' ),
			'description' => __( 'Output the Course Image on the Single Course Page.', 'sensei-lms' ),
			'type'        => 'checkbox',
			'default'     => false,
			'section'     => 'course-settings',
		);

		$fields['course_single_image_width'] = array(
			'name'        => __( 'Image Width - Single', 'sensei-lms' ),
			'description' => __( 'The width in pixels of the featured image for the Course single post page.', 'sensei-lms' ),
			'type'        => 'text',
			'default'     => '100',
			'section'     => 'course-settings',
			'required'    => 0,
		);

		$fields['course_single_image_height'] = array(
			'name'        => __( 'Image Height - Single', 'sensei-lms' ),
			'description' => __( 'The height in pixels of the featured image for the Course single post page.', 'sensei-lms' ),
			'type'        => 'text',
			'default'     => '100',
			'section'     => 'course-settings',
			'required'    => 0,
		);

		$fields['course_single_image_hard_crop'] = array(
			'name'        => __( 'Image Hard Crop - Single', 'sensei-lms' ),
			// translators: Placeholders are an opening and closing <a> tag linking to the documentation page.
			'description' => sprintf( __( 'After changing this setting, you may need to %1$sregenerate your thumbnails%2$s.', 'sensei-lms' ), '<a href="' . esc_url( 'http://wordpress.org/extend/plugins/regenerate-thumbnails/' ) . '">', '</a>' ),
			'type'        => 'checkbox',
			'default'     => false,
			'section'     => 'course-settings',
		);

		$fields['course_archive_featured_enable'] = array(
			'name'        => __( 'Featured Courses Panel', 'sensei-lms' ),
			'description' => __( 'Output the Featured Courses Panel on the Course Archive Page.', 'sensei-lms' ),
			'type'        => 'checkbox',
			'default'     => true,
			'section'     => 'course-settings',
		);

		$fields['course_archive_more_link_text'] = array(
			'name'        => __( 'More link text', 'sensei-lms' ),
			'description' => __( 'The text that will be displayed on the Course Archive for the more courses link.', 'sensei-lms' ),
			'type'        => 'text',
			'default'     => __( 'More', 'sensei-lms' ),
			'section'     => 'course-settings',
			'required'    => 0,
		);

		// Lesson Settings
		$fields['lesson_comments'] = array(
			'name'        => __( 'Allow Comments for Lessons', 'sensei-lms' ),
			'description' => __( 'This will allow learners to post comments on the single Lesson page, only learner who have access to the Lesson will be allowed to comment.', 'sensei-lms' ),
			'type'        => 'checkbox',
			'default'     => true,
			'section'     => 'lesson-settings',
		);

		$fields['lesson_author'] = array(
			'name'        => __( 'Display Lesson Author', 'sensei-lms' ),
			'description' => __( 'Output the Lesson Author on Course single page & Lesson archive page.', 'sensei-lms' ),
			'type'        => 'checkbox',
			'default'     => true,
			'section'     => 'lesson-settings',
		);

		$fields['course_lesson_image_enable'] = array(
			'name'        => __( 'Course Lesson Images', 'sensei-lms' ),
			'description' => __( 'Output the Lesson Image on the Single Course Page.', 'sensei-lms' ),
			'type'        => 'checkbox',
			'default'     => false,
			'section'     => 'lesson-settings',
		);

		$fields['lesson_archive_image_width'] = array(
			'name'        => __( 'Image Width - Course Lessons', 'sensei-lms' ),
			'description' => __( 'The width in pixels of the featured image for the Lessons on the Course Single page.', 'sensei-lms' ),
			'type'        => 'text',
			'default'     => '100',
			'section'     => 'lesson-settings',
			'required'    => 0,
		);

		$fields['lesson_archive_image_height'] = array(
			'name'        => __( 'Image Height - Course Lessons', 'sensei-lms' ),
			'description' => __( 'The height in pixels of the featured image for the Lessons on the Course Single page.', 'sensei-lms' ),
			'type'        => 'text',
			'default'     => '100',
			'section'     => 'lesson-settings',
			'required'    => 0,
		);

		$fields['lesson_archive_image_hard_crop'] = array(
			'name'        => __( 'Image Hard Crop - Course Lessons', 'sensei-lms' ),
			// translators: Placeholders are an opening and closing <a> tag linking to the documentation page.
			'description' => sprintf( __( 'After changing this setting, you may need to %1$sregenerate your thumbnails%2$s.', 'sensei-lms' ), '<a href="' . esc_url( 'http://wordpress.org/extend/plugins/regenerate-thumbnails/' ) . '">', '</a>' ),
			'type'        => 'checkbox',
			'default'     => false,
			'section'     => 'lesson-settings',
		);

		$fields['lesson_single_image_enable'] = array(
			'name'        => __( 'Single Lesson Images', 'sensei-lms' ),
			'description' => __( 'Output the Lesson Image on the Single Lesson Page.', 'sensei-lms' ),
			'type'        => 'checkbox',
			'default'     => false,
			'section'     => 'lesson-settings',
		);

		$fields['lesson_single_image_width'] = array(
			'name'        => __( 'Image Width - Single', 'sensei-lms' ),
			'description' => __( 'The width in pixels of the featured image for the Lessons single post page.', 'sensei-lms' ),
			'type'        => 'text',
			'default'     => '100',
			'section'     => 'lesson-settings',
			'required'    => 0,
		);

		$fields['lesson_single_image_height'] = array(
			'name'        => __( 'Image Height - Single', 'sensei-lms' ),
			'description' => __( 'The height in pixels of the featured image for the Lessons single post page.', 'sensei-lms' ),
			'type'        => 'text',
			'default'     => '100',
			'section'     => 'lesson-settings',
			'required'    => 0,
		);

		$fields['lesson_single_image_hard_crop'] = array(
			'name'        => __( 'Image Hard Crop - Single', 'sensei-lms' ),
			// translators: Placeholders are an opening and closing <a> tag linking to the documentation page.
			'description' => sprintf( __( 'After changing this setting, you may need to %1$sregenerate your thumbnails%2$s.', 'sensei-lms' ), '<a href="' . esc_url( 'http://wordpress.org/extend/plugins/regenerate-thumbnails/' ) . '">', '</a>' ),
			'type'        => 'checkbox',
			'default'     => false,
			'section'     => 'lesson-settings',
		);

		// Learner Profile settings
		$profile_url_base    = apply_filters( 'sensei_learner_profiles_url_base', __( 'learner', 'sensei-lms' ) );
		$profile_url_example = trailingslashit( get_home_url() ) . $profile_url_base . '/%username%';

		$fields['learner_profile_enable'] = array(
			'name'        => __( 'Public learner profiles', 'sensei-lms' ),
			// translators: Placeholder is a profile URL example.
			'description' => sprintf( __( 'Enable public learner profiles that will be accessible to everyone. Profile URL format: %s', 'sensei-lms' ), $profile_url_example ),
			'type'        => 'checkbox',
			'default'     => true,
			'section'     => 'learner-profile-settings',
		);

		$fields['learner_profile_show_courses'] = array(
			'name'        => __( 'Show learner\'s courses', 'sensei-lms' ),
			'description' => __( 'Display the learner\'s active and completed courses on their profile.', 'sensei-lms' ),
			'type'        => 'checkbox',
			'default'     => true,
			'section'     => 'learner-profile-settings',
		);

		// Email notifications
		$learner_email_options = array(
			'learner-graded-quiz'      => __( 'Their quiz is graded (auto and manual grading)', 'sensei-lms' ),
			'learner-completed-course' => __( 'They complete a course', 'sensei-lms' ),
		);

		$teacher_email_options = array(
			'teacher-started-course'   => __( 'A learner starts their course', 'sensei-lms' ),
			'teacher-completed-course' => __( 'A learner completes their course', 'sensei-lms' ),
			'teacher-completed-lesson' => __( 'A learner completes a lesson', 'sensei-lms' ),
			'teacher-quiz-submitted'   => __( 'A learner submits a quiz for grading', 'sensei-lms' ),
			'teacher-new-message'      => __( 'A learner sends a private message to a teacher', 'sensei-lms' ),
		);

		$global_email_options = array(
			'new-message-reply' => __( 'They receive a reply to their private message', 'sensei-lms' ),
		);

		$fields['email_learners'] = array(
			'name'        => __( 'Emails Sent to Learners', 'sensei-lms' ),
			'description' => __( 'Select the notifications that will be sent to learners.', 'sensei-lms' ),
			'type'        => 'multicheck',
			'options'     => $learner_email_options,
			'defaults'    => array( 'learner-graded-quiz', 'learner-completed-course' ),
			'section'     => 'email-notification-settings',
		);

		$fields['email_teachers'] = array(
			'name'        => __( 'Emails Sent to Teachers', 'sensei-lms' ),
			'description' => __( 'Select the notifications that will be sent to teachers.', 'sensei-lms' ),
			'type'        => 'multicheck',
			'options'     => $teacher_email_options,
			'defaults'    => array( 'teacher-completed-course', 'teacher-started-course', 'teacher-quiz-submitted', 'teacher-new-message' ),
			'section'     => 'email-notification-settings',
		);

		$fields['email_global'] = array(
			'name'        => __( 'Emails Sent to All Users', 'sensei-lms' ),
			'description' => __( 'Select the notifications that will be sent to all users.', 'sensei-lms' ),
			'type'        => 'multicheck',
			'options'     => $global_email_options,
			'defaults'    => array( 'new-message-reply' ),
			'section'     => 'email-notification-settings',
		);

		$fields['email_from_name'] = array(
			'name'        => __( '"From" Name', 'sensei-lms' ),
			'description' => __( 'The name from which all emails will be sent.', 'sensei-lms' ),
			'type'        => 'text',
			'default'     => get_bloginfo( 'name' ),
			'section'     => 'email-notification-settings',
			'required'    => 1,
		);

		$fields['email_from_address'] = array(
			'name'        => __( '"From" Address', 'sensei-lms' ),
			'description' => __( 'The address from which all emails will be sent.', 'sensei-lms' ),
			'type'        => 'text',
			'default'     => get_bloginfo( 'admin_email' ),
			'section'     => 'email-notification-settings',
			'required'    => 1,
		);

		$fields['email_header_image'] = array(
			'name'        => __( 'Header Image', 'sensei-lms' ),
			// translators: Placeholders are opening and closing <a> tags linking to the media uploader.
			'description' => sprintf( __( 'Enter a URL to an image you want to show in the email\'s header. Upload your image using the %1$smedia uploader%2$s.', 'sensei-lms' ), '<a href="' . admin_url( 'media-new.php' ) . '">', '</a>' ),
			'type'        => 'text',
			'default'     => '',
			'section'     => 'email-notification-settings',
			'required'    => 0,
		);

		$fields['email_footer_text'] = array(
			'name'        => __( 'Email Footer Text', 'sensei-lms' ),
			'description' => __( 'The text to appear in the footer of Sensei emails.', 'sensei-lms' ),
			'type'        => 'textarea',
			// translators: Placeholder is the blog name.
			'default'     => sprintf( __( '%1$s - Powered by Sensei', 'sensei-lms' ), get_bloginfo( 'name' ) ),
			'section'     => 'email-notification-settings',
			'required'    => 0,
		);

		$fields['email_base_color'] = array(
			'name'        => __( 'Base Colour', 'sensei-lms' ),
			// translators: Placeholders are opening and closing <code> tags.
			'description' => sprintf( __( 'The base colour for Sensei email templates. Default %1$s#557da1%2$s.', 'sensei-lms' ), '<code>', '</code>' ),
			'type'        => 'color',
			'default'     => '#557da1',
			'section'     => 'email-notification-settings',
			'required'    => 1,
		);

		$fields['email_background_color'] = array(
			'name'        => __( 'Background Colour', 'sensei-lms' ),
			// translators: Placeholders are opening and closing <code> tags.
			'description' => sprintf( __( 'The background colour for Sensei email templates. Default %1$s#f5f5f5%2$s.', 'sensei-lms' ), '<code>', '</code>' ),
			'type'        => 'color',
			'default'     => '#f5f5f5',
			'section'     => 'email-notification-settings',
			'required'    => 1,
		);

		$fields['email_body_background_color'] = array(
			'name'        => __( 'Body Background Colour', 'sensei-lms' ),
			// translators: Placeholders are opening and closing <code> tags.
			'description' => sprintf( __( 'The main body background colour for Sensei email templates. Default %1$s#fdfdfd%2$s.', 'sensei-lms' ), '<code>', '</code>' ),
			'type'        => 'color',
			'default'     => '#fdfdfd',
			'section'     => 'email-notification-settings',
			'required'    => 1,
		);

		$fields['email_text_color'] = array(
			'name'        => __( 'Body Text Colour', 'sensei-lms' ),
			// translators: Placeholders are opening and closing <code> tags.
			'description' => sprintf( __( 'The main body text colour for Sensei email templates. Default %1$s#505050%2$s.', 'sensei-lms' ), '<code>', '</code>' ),
			'type'        => 'color',
			'default'     => '#505050',
			'section'     => 'email-notification-settings',
			'required'    => 1,
		);

		$this->fields = apply_filters( 'sensei_settings_fields', $fields );

	} // End init_fields()

	/**
	 * Get options for the duration fields.
	 *
	 * @since  1.0.0
	 * @param  $include_milliseconds (default: true) Whether or not to include milliseconds between 0 and 1.
	 * @return array Options between 0.1 and 10 seconds.
	 */
	private function get_duration_options( $include_milliseconds = true ) {
		$numbers = array( '1.0', '1.5', '2.0', '2.5', '3.0', '3.5', '4.0', '4.5', '5.0', '5.5', '6.0', '6.5', '7.0', '7.5', '8.0', '8.5', '9.0', '9.5', '10.0' );
		$options = array();

		if ( true == (bool) $include_milliseconds ) {
			$milliseconds = array( '0.1', '0.2', '0.3', '0.4', '0.5', '0.6', '0.7', '0.8', '0.9' );
			foreach ( $milliseconds as $k => $v ) {
				$options[ $v ] = $v;
			}
		} else {
			$options['0.5'] = '0.5';
		}

		foreach ( $numbers as $k => $v ) {
			$options[ $v ] = $v;
		}

		return $options;
	} // End get_duration_options()

	/**
	 * Return an array of pages.
	 *
	 * @access private
	 * @since  1.0.0
	 * @return array
	 */
	private function pages_array() {
		// REFACTOR - Transform this into a field type instead.
		// Setup an array of portfolio gallery terms for a dropdown.
		$pages_dropdown = wp_dropdown_pages(
			array(
				'echo'         => 0,
				'hierarchical' => 1,
				'sort_column'  => 'post_title',
				'sort_order'   => 'ASC',
			)
		);
		$page_items     = array();

		// Quick string hack to make sure we get the pages with the indents.
		$pages_dropdown = str_replace( "<select class='' name='page_id' id='page_id'>", '', $pages_dropdown );
		$pages_dropdown = str_replace( '</select>', '', $pages_dropdown );
		$pages_split    = explode( '</option>', $pages_dropdown );

		$page_items[] = __( 'Select a Page:', 'sensei-lms' );

		foreach ( $pages_split as $k => $v ) {
			$id = '';
			// Get the ID value.
			preg_match( '/value="(.*?)"/i', $v, $matches );

			if ( isset( $matches[1] ) ) {
				$id                = $matches[1];
				$page_items[ $id ] = trim( strip_tags( $v ) );
			} // End If Statement
		} // End For Loop

		$pages_array = $page_items;

		return $pages_array;
	} // End pages_array()

	/**
	 * Flush the rewrite rules after the settings have been updated.
	 * This is to ensure that the
	 *
	 * @since 1.9.0
	 */
	public static function flush_rewrite_rules() {

		/*
		 * Skipping nonce check because it is already done by WordPress for the Settings page.
		 * phpcs:disable WordPress.Security.NonceVerification
		 */
		if ( isset( $_POST['option_page'] ) && 'sensei-settings' === $_POST['option_page']
			&& isset( $_POST['action'] ) && 'update' === $_POST['action'] ) {
			// phpcs:enable WordPress.Security.NonceVerification

			Sensei()->initiate_rewrite_rules_flush();

		}

	}//end flush_rewrite_rules()
} // End Class

/**
 * Class WooThemes_Sensei_Settings
 *
 * @ignore only for backward compatibility
 * @since 1.9.0
 */
class WooThemes_Sensei_Settings extends Sensei_Settings{}
