<?php
/**
 * Email Footer
 *
 * Override this template by copying it to yourtheme/charitable/emails/footer.php
 *
 * @author  WP Charitable LLC
 * @package Charitable/Templates/Emails
 * @version 1.0.0
 * @version 1.8.8.4 - fixed PHP warning when using wp_kses_post() in footer content.
 * @version 1.8.8.6
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// For gmail compatibility, including CSS styles in head/body are stripped out therefore styles need to be inline. These variables contain rules which are added to the template inline.
$charitable_template_footer = "
	border-top:0;
	-webkit-border-radius:3px;
";

$charitable_credit = "
	border:0;
	color: #000000;
	font-family: 'Helvetica Neue', Helvetica, Arial, 'Lucida Grande', sans-serif;
	font-size:12px;
	line-height:125%;
	text-align:center;
";
?>
															</div>
														</td>
													</tr>
												</table>
												<!-- End Content -->
											</td>
										</tr>
									</table>
									<!-- End Body -->
								</td>
							</tr>
							<tr>
								<td align="center" valign="top">
									<!-- Footer -->
									<table border="0" cellpadding="10" cellspacing="0" width="600" id="template_footer" style="<?php echo esc_attr( $charitable_template_footer ); ?>">
										<tr>
											<td valign="top">
												<table border="0" cellpadding="10" cellspacing="0" width="100%">
													<tr>
														<td colspan="2" valign="middle" id="credit" style="<?php echo esc_attr( $charitable_credit ); ?>">
															<?php
																/**
																 * Filter the complete email footer content output.
																 *
																 * @since 1.8.0
																 *
																 * @param string $footer_content The default footer content.
																 */
																$charitable_footer_content = apply_filters( 'charitable_email_footer_content', wp_kses_post( wpautop(
																	wp_kses_post(
																		wptexturize(
																			/**
																			 * Filter the email footer text & link.
																			 *
																			 * @since 1.0.0
																			 *
																			 * @param string $text The default text.
																			 */
																			apply_filters( 'charitable_email_footer_text', '<a href="' . esc_url( home_url() ) . '">' . get_bloginfo( 'name' ) . '</a>' )
																		)
																	)
																) ) );

																echo wp_kses_post( $charitable_footer_content );
															?>
														</td>
													</tr>
												</table>
											</td>
										</tr>
									</table>
									<!-- End Footer -->
								</td>
							</tr>
						</table>
					</td>
				</tr>
			</table>
		</div>
	</body>
</html>
