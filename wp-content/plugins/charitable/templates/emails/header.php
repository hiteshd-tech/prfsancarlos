<?php
/**
 * Email Header
 *
 * Override this template by copying it to yourtheme/charitable/emails/header.php
 *
 * @author  WP Charitable LLC
 * @package Charitable/Templates/Emails
 * @version 1.0.0
 * @version 1.8.8.6
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$charitable_email = $view_args['email'];

if ( is_object( $charitable_email ) && is_a( $charitable_email, 'Charitable_Email' ) ) {

	$charitable_headline = $charitable_email->get_headline();

} elseif ( array_key_exists( 'headline', $view_args ) ) {

	$charitable_headline = $view_args['headline'];

}

// For gmail compatibility, including CSS styles in head/body are stripped out therefore styles need to be inline. These variables contain rules which are added to the template inline. !important; is a gmail hack to prevent styles being stripped if it doesn't like something.
$charitable_body = "
	background-color: #f6f6f6;
	font-family: 'Helvetica Neue', Helvetica, Arial, 'Lucida Grande', sans-serif;
";
$charitable_wrapper = "
	width:100%;
	-webkit-text-size-adjust:none !important;
	margin:0;
	padding: 70px 0 70px 0;
";
$charitable_template_container = "
	box-shadow:0 0 0 1px #f3f3f3 !important;
	border-radius:3px !important;
	background-color: #ffffff;
	border: 1px solid #e9e9e9;
	border-radius:3px !important;
	padding: 20px;
";
$charitable_template_header = "
	color: #00000;
	border-top-left-radius:3px !important;
	border-top-right-radius:3px !important;
	border-bottom: 0;
	font-weight:bold;
	line-height:100%;
	text-align: center;
	vertical-align:middle;
";
$charitable_body_content = "
	border-radius:3px !important;
	font-family: 'Helvetica Neue', Helvetica, Arial, 'Lucida Grande', sans-serif;
";
$charitable_body_content_inner = "
	color: #000000;
	font-size:14px;
	font-family: 'Helvetica Neue', Helvetica, Arial, 'Lucida Grande', sans-serif;
	line-height:150%;
	text-align:left;
";
$charitable_header_content_h1 = "
	color: #000000;
	margin:0;
	padding: 28px 24px;
	display:block;
	font-family: 'Helvetica Neue', Helvetica, Arial, 'Lucida Grande', sans-serif;
	font-size:32px;
	font-weight: 500;
	line-height: 1.2;
";
$charitable_header_img = false;
?>
<!DOCTYPE html>
<html>
	<head>
		<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
		<title><?php echo esc_html( get_bloginfo( 'name' ) ); ?></title>
	</head>
	<body leftmargin="0" marginwidth="0" topmargin="0" marginheight="0" offset="0" style="<?php echo esc_attr( $charitable_body ); ?>">
		<div style="<?php echo esc_attr( $charitable_wrapper ); ?>">
		<table border="0" cellpadding="0" cellspacing="0" height="100%" width="100%">
			<tr>
				<td align="center" valign="top">
					<?php if( ! empty( $charitable_header_img ) ) : ?>
						<div id="template_header_image">
							<?php echo '<p style="margin-top:0;"><img src="' . esc_url( $charitable_header_img ) . '" alt="' . esc_attr( get_bloginfo( 'name' ) ) . '" /></p>'; ?>
						</div>
					<?php endif; ?>
						<table border="0" cellpadding="0" cellspacing="0" width="520" id="template_container" style="<?php echo esc_attr( $charitable_template_container ); ?>">
						<tr>
							<td align="center" valign="top">
								<!-- Header -->
								<table border="0" cellpadding="0" cellspacing="0" width="520" id="template_header" style="<?php echo esc_attr( $charitable_template_header ); ?>" bgcolor="#ffffff">
									<tr>
										<td>
											<h1 style="<?php echo esc_attr( $charitable_header_content_h1 ); ?>"><?php echo esc_html( $charitable_headline ); ?></h1>
										</td>
									</tr>
								</table>
								<!-- End Header -->
							</td>
						</tr>
						<tr>
							<td align="center" valign="top">
								<!-- Body -->
								<table border="0" cellpadding="0" cellspacing="0" width="520" id="template_body">
									<tr>
										<td valign="top" style="<?php echo esc_attr( $charitable_body_content ); ?>">
											<!-- Content -->
											<table border="0" cellpadding="20" cellspacing="0" width="100%">
												<tr>
													<td valign="top">
														<div style="<?php echo esc_attr( $charitable_body_content_inner ); ?>">
