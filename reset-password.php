<?php
/*------------------------------  Reset Password section -----------------------------*/
/**
 * Redirects the user to the custom "Forgot your password?" page instead of
 * wp-login.php?action=lostpassword.
 */

add_action( 'login_form_lostpassword', 'redirect_to_custom_lostpassword' );
function redirect_to_custom_lostpassword() {
    if ( 'GET' == $_SERVER['REQUEST_METHOD'] ) {
        if ( is_user_logged_in() ) {
            //redirect_logged_in_user();
            exit;
        }
 
        wp_redirect( home_url( 'forgot-your-password' ) );
        exit;
    }
}

/**
 * A shortcode for rendering the form used to initiate the password reset.
*/

add_shortcode( 'custom-password-lost-form', 'render_password_lost_form' );

function render_password_lost_form( $attributes, $content = null ) {
	// Parse shortcode attributes
    $default_attributes = array( 'show_title' => false );
    $attributes = shortcode_atts( $default_attributes, $attributes );
 	
 	// Check if the user just requested a new password 
	$attributes['lost_password_sent'] = isset( $_REQUEST['checkemail'] ) && $_REQUEST['checkemail'] == 'confirm';

	// Retrieve possible errors from request parameters
	$attributes['errors'] = array();
	if ( isset( $_REQUEST['errors'] ) ) {
	    $error_codes = explode( ',', $_REQUEST['errors'] );
	 
	    foreach ( $error_codes as $error_code ) {
	        $attributes['errors'] []= get_error_message( $error_code );
	    }
	}

    if ( is_user_logged_in() ) {
        return __( 'You are already signed in.', 'personalize-login' );
    } else { ?>

    	<div id="password-lost-form" class="widecolumn">
    		<h2>Lost Username or Password</h2>
    		<?php if ( $attributes['lost_password_sent'] ) : ?>
			    <p class="login-info">
			        <?php _e( 'Check your email for a link to reset your password.', 'personalize-login' ); ?>
			    </p>
			<?php endif; ?>

			<?php if ( count( $attributes['errors'] ) > 0 ) : ?>
			    <?php foreach ( $attributes['errors'] as $error ) : ?>
			        <p class="message">
			            <?php echo $error; ?>
			        </p>
			    <?php endforeach; ?>
			<?php endif; ?>

		    <?php if ( $attributes['show_title'] ) : ?>
		        <h3><?php _e( 'Forgot Your Password?', 'personalize-login' ); ?></h3>
		    <?php endif; ?>
		 
		    <p>
		        <?php
		            _e(
		                "Enter your e-mail address below and ECT News will send you the information you need to sign in to your account.",
		                'personalize_login'
		            );
		        ?>
		    </p>
		 	<div class="registration-form-section">
			    <form id="lostpasswordform" action="<?php echo wp_lostpassword_url(); ?>" method="post" class="form-signin" >
			    	<div class="login_form">
				        <div class="form-group">
				            <label for="user_login"><?php _e( 'Email', 'personalize-login' ); ?><span>*</span></label>
				            <input type="text" class="form-control" name="user_login" id="user_login" required="">
				        </div>
				 
				        <div class="form-group">
				            <input type="submit" name="submit" class="submit_button form-control btn" value="<?php _e( 'Reset Password', 'personalize-login' ); ?>"/>
				        </div>
			    	</div>
			    </form>
			</div>
		</div>
   <?php    
    }
}

// Display error code
function get_error_message( $error_code ){
	switch ($error_code) {		
		case 'empty_username':
		    return __( 'You need to enter your email address to continue.', 'personalize-login' );
		 
		case 'invalid_email':
		case 'invalidcombo':
		    return __( 'There are no users registered with this email address.', 'personalize-login' );
		case 'sendemail':
		    return __( 'An email has been send to your registered email address.', 'personalize-login' );
		case 'expiredkey':
		case 'invalidkey':
		    return __( 'The password reset link you used is not valid anymore.', 'personalize-login' );
		 
		case 'password_reset_mismatch':
		    return __( "The two passwords you entered don't match.", 'personalize-login' );
		     
		case 'password_reset_empty':
		    return __( "Sorry, we don't accept empty passwords.", 'personalize-login' );

		default:			
			break;
	}
}


/**
 * Initiates password reset.
 */
add_action( 'login_form_lostpassword', 'do_password_lost' );
function do_password_lost() {
    if ( 'POST' == $_SERVER['REQUEST_METHOD'] ) {
        $errors = retrieve_password();
        $attributes['errors'] = array();
        if ( is_wp_error( $errors ) ) {
            // Errors found
            $redirect_url = home_url( 'forgot-your-password' );
            $redirect_url = add_query_arg( 'errors', join( ',', $errors->get_error_codes() ), $redirect_url );
        } else {
            // Email sent
            $redirect_url = home_url( 'forgot-your-password' );
            $redirect_url = add_query_arg( 'checkemail', 'confirm', $redirect_url );
            
            $attributes['errors'][] = 'sendemail';
        }
 
        wp_redirect( $redirect_url );
        exit;
    }
}


add_filter( 'retrieve_password_message', 'replace_retrieve_password_message', 10, 4 );
/**
 * Returns the message body for the password reset mail.
 * Called through the retrieve_password_message filter. 
 */
function replace_retrieve_password_message( $message, $key, $user_login, $user_data ) {
    // Create new message
    $msg  = __( 'Hello!', 'personalize-login' ) . "\r\n\r\n";
    $msg .= sprintf( __( 'You asked us to reset your password for your account using the email address %s.', 'personalize-login' ), $user_login ) . "\r\n\r\n";
    $msg .= __( "If this was a mistake, or you didn't ask for a password reset, just ignore this email and nothing will happen.", 'personalize-login' ) . "\r\n\r\n";
    $msg .= __( 'To reset your password, visit the following address:', 'personalize-login' ) . "\r\n\r\n";
    $msg .= site_url( "wp-login.php?action=rp&key=$key&login=" . rawurlencode( $user_login ), 'login' ) . "\r\n\r\n";
    $msg .= __( 'Thanks!', 'personalize-login' ) . "\r\n";
 
    return $msg;
}

////////////////////////////////////

add_action( 'login_form_rp', 'redirect_to_custom_password_reset' );
add_action( 'login_form_resetpass', 'redirect_to_custom_password_reset' );

/**
 * Redirects to the custom password reset page, or the login page
 * if there are errors.
 */
function redirect_to_custom_password_reset() {
    if ( 'GET' == $_SERVER['REQUEST_METHOD'] ) {
        // Verify key / login combo
        $user = check_password_reset_key( $_REQUEST['key'], $_REQUEST['login'] );
        if ( ! $user || is_wp_error( $user ) ) {
            if ( $user && $user->get_error_code() === 'expired_key' ) {
                wp_redirect( home_url( '?login=expiredkey' ) );
            } else {
                wp_redirect( home_url( '?login=invalidkey' ) );
            }
            exit;
        }
 
        $redirect_url = home_url( 'password-reset' );
        $redirect_url = add_query_arg( 'login', esc_attr( $_REQUEST['login'] ), $redirect_url );
        $redirect_url = add_query_arg( 'key', esc_attr( $_REQUEST['key'] ), $redirect_url );
 
        wp_redirect( $redirect_url );
        exit;
    }
}

/**
 * A shortcode for rendering the form used to reset a user's password.
*/
add_shortcode( 'custom-password-reset-form', 'render_password_reset_form' );

function render_password_reset_form( $attributes, $content = null ) {
    // Parse shortcode attributes
    $default_attributes = array( 'show_title' => true );
    $attributes = shortcode_atts( $default_attributes, $attributes );
 
    if ( is_user_logged_in() ) {
        return __( 'You are already signed in.', 'personalize-login' );
    } else {
        if ( isset( $_REQUEST['login'] ) && isset( $_REQUEST['key'] ) ) {
            $attributes['login'] = $_REQUEST['login'];
            $attributes['key'] = $_REQUEST['key'];
 
            // Error messages
            $errors = array();
            if ( isset( $_REQUEST['error'] ) ) {
                $error_codes = explode( ',', $_REQUEST['error'] );
 
                foreach ( $error_codes as $code ) {
                    $errors []= get_error_message( $code );
                }
            }
            $attributes['errors'] = $errors;
 
            //return get_template_html( 'password_reset_form', $attributes );
            ?>
			<div id="password-reset-form" class="widecolumn">
		    <?php if ( $attributes['show_title'] ) : ?>
		        <h2><?php _e( 'Pick a New Password', 'personalize-login' ); ?></h2>
		    <?php endif; ?>
		 	<?php if ( count( $attributes['errors'] ) > 0 ) : ?>
	            <?php foreach ( $attributes['errors'] as $error ) : ?>
	                <p class="message">
	                    <?php echo $error; ?>
	                </p>
	            <?php endforeach; ?>
	        <?php endif; ?>
		    <form name="resetpassform" id="resetpassform" action="<?php echo site_url( 'wp-login.php?action=resetpass' ); ?>" method="post" autocomplete="off" class="form-signin">
		    	<div class="login_form">
			        <input type="hidden" id="user_login" name="rp_login" value="<?php echo esc_attr( $attributes['login'] ); ?>" autocomplete="off" />
			        <input type="hidden" name="rp_key" value="<?php echo esc_attr( $attributes['key'] ); ?>" />			         			        
			 
			        <div class="form-group">
			            <label for="pass1"><?php _e( 'New password', 'personalize-login' ) ?></label>
			            <input type="password" name="pass1" id="pass1" class="input form-control" size="20" value="" autocomplete="off" />
			        </div>
			        <div class="form-group">
			            <label for="pass2"><?php _e( 'Repeat new password', 'personalize-login' ) ?></label>
			            <input type="password" name="pass2" id="pass2" class="input form-control" size="20" value="" autocomplete="off" />
			        </div>
			        <div class="form-group">
			        	<p class="description"><?php echo wp_get_password_hint(); ?></p>
			        </div>

			        <div class="form-group">
			            <input type="submit" name="submit" id="resetpass-button"
			                   class="submit_button form-control btn" value="<?php _e( 'Reset Password', 'personalize-login' ); ?>" />
			        </div>
			    </div>
		    </form>
		</div>
	
            <?php
        } else {
            return __( 'Invalid password reset link.', 'personalize-login' );
        }
    }
}

/**
 * Resets the user's password if the password reset form was submitted.
 */

add_action( 'login_form_rp', 'do_password_reset' );
add_action( 'login_form_resetpass', 'do_password_reset' );

function do_password_reset() {
    if ( 'POST' == $_SERVER['REQUEST_METHOD'] ) {
        $rp_key = $_REQUEST['rp_key'];
        $rp_login = $_REQUEST['rp_login'];
 
        $user = check_password_reset_key( $rp_key, $rp_login );
 
        if ( ! $user || is_wp_error( $user ) ) {
            if ( $user && $user->get_error_code() === 'expired_key' ) {
                wp_redirect( home_url( '?login=expiredkey' ) );
            } else {
                wp_redirect( home_url( '?login=invalidkey' ) );
            }
            exit;
        }
 
        if ( isset( $_POST['pass1'] ) ) {
            if ( $_POST['pass1'] != $_POST['pass2'] ) {
                // Passwords don't match
                $redirect_url = home_url( 'password-reset' );
 
                $redirect_url = add_query_arg( 'key', $rp_key, $redirect_url );
                $redirect_url = add_query_arg( 'login', $rp_login, $redirect_url );
                $redirect_url = add_query_arg( 'error', 'password_reset_mismatch', $redirect_url );
 
                wp_redirect( $redirect_url );
                exit;
            }
 
            if ( empty( $_POST['pass1'] ) ) {
                // Password is empty
                $redirect_url = home_url( 'password-reset' );
 
                $redirect_url = add_query_arg( 'key', $rp_key, $redirect_url );
                $redirect_url = add_query_arg( 'login', $rp_login, $redirect_url );
                $redirect_url = add_query_arg( 'error', 'password_reset_empty', $redirect_url );
 
                wp_redirect( $redirect_url );
                exit;
            }
 
            // Parameter checks OK, reset password
            reset_password( $user, $_POST['pass1'] );
            wp_redirect( home_url( '?password=changed' ) );
        } else {
            echo "Invalid request.";
        }
 
        exit;
    }
}
