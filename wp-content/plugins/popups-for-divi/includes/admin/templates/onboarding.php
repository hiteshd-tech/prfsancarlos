<?php
/**
 * Content of the Popups for Divi onboarding notice which is displayed on the
 * wp-admin dashboard after first installation.
 *
 * This template intentionally outputs inline CSS and JS - since this notice is
 * only displayed a single time, it does not make sense to store the CSS/JS in
 * the browsers cache.
 *
 * @since   3.0.0
 *
 * @var WP_User $user The currently logged in user.
 *
 * @package PopupsForDivi
 */

$kses_args = [
	'strong' => [],
	'a'      => [
		'href'   => [],
		'target' => [],
	],
];

?>
<div class="pfd-onboarding notice">
	<p class="title">
		<?php esc_html_e( 'Thanks for using Popups&nbsp;for&nbsp;Divi', 'divi-popup' ); ?> 😊
	</p>
	<div class="pfd-layout">
		<p class="msg">
			<?php
			echo wp_kses(
				__( 'We have created a <strong>free email course</strong> to help you get the most out of Popups for Divi. <strong>Sign up now</strong>, and you will receive six emails with easy to follow instructions, lots of examples and some pretty advanced Popup techniques.', 'divi-popup' ),
				$kses_args
			);
			?>
		</p>
		<div class="form">
			<input
					type="text"
					class="name"
					autocomplete="name"
					placeholder="Your first name"
			/>
			<input
					type="email"
					class="email"
					placeholder="Your email address"
					autocomplete="email"
					value="<?php echo esc_attr( $user->user_email ); ?>"
			/>
			<button class="button-primary submit">
				<?php esc_html_e( 'Start The Course', 'divi-popup' ); ?>
			</button>
		</div>
	</div>
	<p class="privacy">
		<?php
		echo wp_kses(
			__( 'Only your name and email is sent to our website. We use the information to deliver the onboarding mails. <a href="https://divimode.com/privacy/" target="_blank">Privacy&nbsp;Policy</a>', 'divi-popup' ),
			$kses_args
		);
		?>
	</p>
	<div class="loader"><span class="spinner is-active"></span></div>
	<span class="notice-dismiss"><?php esc_html_e( 'Close forever', 'divi-popup' ); ?></span>
</div>

<style>
	.wrap .notice.pfd-onboarding {
		position: relative;
		margin-bottom: 4em;
		padding-bottom: 0;
		border-left-color: #660099
	}

	.pfd-onboarding .title {
		font-weight: 600;
		color: #000;
		border-bottom: 1px solid #eee;
		padding-bottom: .5em;
		padding-right: 100px;
		margin-bottom: 0
	}

	.pfd-onboarding .form {
		text-align: center;
		position: relative;
		padding: .5em
	}

	.pfd-onboarding .privacy {
		font-size: .9em;
		text-align: center;
		opacity: .6;
		position: absolute;
		left: 0;
		right: 0
	}

	.pfd-onboarding .pfd-layout {
		display: flex;
		flex-wrap: wrap;
		position: relative
	}

	.pfd-onboarding .form:before {
		content: '';
		position: absolute;
		right: -9px;
		left: -9px;
		top: 0;
		bottom: 1px;
		background: #9944cc linear-gradient(-45deg, #660099 0%, #9944cc 100%) !important;
		box-shadow: 0 0 0 1px #0004 inset
	}

	.pfd-onboarding .pfd-layout > * {
		flex: 1 1 100%;
		align-self: center;
		z-index: 10
	}

	.pfd-onboarding input:focus,
	.pfd-onboarding input,
	.pfd-onboarding button.button-primary,
	.pfd-onboarding button.button-primary:focus {
		display: block;
		width: 80%;
		margin: 12px auto;
		text-align: center;
		border-radius: 0;
		height: 30px;
		box-shadow: 0 0 0 5px #fff3;
		outline: none;
		position: relative;
		z-index: 10
	}

	.pfd-onboarding input:focus,
	.pfd-onboarding input {
		border: 1px solid #0002;
		padding: 5px 3px
	}

	.pfd-onboarding .notice-dismiss:before {
		display: none
	}

	.pfd-onboarding .msg {
		position: relative;
		z-index: 20
	}

	.pfd-onboarding .msg .dismiss {
		float: right
	}

	.pfd-onboarding .msg strong {
		white-space: nowrap
	}

	.pfd-onboarding .msg .emoji {
		width: 3em !important;
		height: 3em !important;
		vertical-align: middle !important;
		margin-right: 1em !important;
		float: left
	}

	.pfd-onboarding .loader {
		display: none;
		position: absolute;
		background: #fffc;
		z-index: 50;
		left: 0;
		top: 0;
		right: 0;
		bottom: 0
	}

	.pfd-onboarding.loading .loader {
		display: block
	}

	.pfd-onboarding .loader .spinner {
		position: absolute;
		left: 50%;
		top: 50%;
		margin: 0;
		transform: translate(-50%, -50%)
	}

	@media (min-width: 783px) and (max-width: 1023px) {
		.pfd-onboarding .form:before {
			right: -11px;
			left: -11px
		}
	}

	@media (min-width: 1024px) {
		.wrap .notice.pfd-onboarding {
			margin-bottom: 2em;
			padding-right: 0
		}

		.pfd-onboarding .pfd-layout {
			flex-wrap: nowrap;
			overflow: hidden;
			padding: .5em 0
		}

		.pfd-onboarding .pfd-layout > * {
			flex: 0 0 50%
		}

		.pfd-onboarding input:focus,
		.pfd-onboarding input,
		.pfd-onboarding button.button-primary,
		.pfd-onboarding button.button-primary:focus {
			display: inline-block;
			width: auto;
			margin: 5px
		}

		.pfd-onboarding input:focus,
		.pfd-onboarding input {
			width: 32%
		}

		.pfd-onboarding .form {
			position: static
		}

		.pfd-onboarding .form:before {
			width: 50%;
			right: 0;
			left: auto;
			bottom: 0
		}

		.pfd-onboarding .form:after {
			content: '';
			position: absolute;
			right: 50%;
			width: 50px;
			height: 50px;
			top: 50%;
			background: #fff;
			transform: translate(50%, -50%) rotate(45deg) skew(20deg, 20deg)
		}
	}

	.pdf-input-error {
		transition: all 0.05s;
		position: relative;
		animation: pdf-error 0.8s linear;
		animation-iteration-count: 1;
		transform: scale(1);
	}

	@keyframes pdf-error {
		0% {
			box-shadow: 0 0 0 5px #fff3;
		}
		10% {
			transform: scale(0.92) rotate(1deg);
		}
		25% {
			transform: scale(1.1) rotate(-1deg);
		}
		40% {
			transform: unset;
			box-shadow: 0 0 0 5px #fff3, 0 0 0 1px #f00c;
		}
		65% {
			box-shadow: 0 0 0 5px #fff3, 0 0 0 5px #f009;
		}
		95% {
			box-shadow: 0 0 0 5px #fff3, 0 0 20px 20px #f000;
		}
		100% {
			box-shadow: 0 0 0 5px #fff3;
		}
	}
</style>

<script>
	jQuery(function ($) {
		var notice = $('.pfd-onboarding.notice');
		var msg = notice.find('.msg');
		var email = notice.find('input.email');
		var name = notice.find('input.name');
		var submit = notice.find('.submit');

		notice.on('click', '.notice-dismiss,.dismiss', dismissForever);
		notice.on('click', focusForm);
		submit.on('click', startCourse);
		name.on('keypress', maybeSubmit);
		email.on('keypress', maybeSubmit);

		function dismissForever(e) {
			notice.addClass('loading');
			$.post(ajaxurl, {
				action: 'pfd_hide_onboarding',
				_wpnonce: '<?php echo esc_js( wp_create_nonce( 'no-onboarding' ) ); ?>'
			}, function () {
				notice.removeClass('loading');
				notice.fadeOut(400, function () {
					notice.remove();
				});
			});
		}

		function focusForm(e) {
			var el = $(e.target);
			var tag = el.prop('tagName');
			if (
				'A' === tag
				|| 'INPUT' === tag
				|| 'BUTTON' === tag
				|| el.hasClass('dismiss')
				|| el.hasClass('notice-dismiss')
			) {
				return;
			}

			if (name.val().trim().length < 2) {
				name.focus().select();
			} else if (email.val().trim().length < 5) {
				email.focus().select();
			} else {
				submit.focus();
			}
		}

		function maybeSubmit(e) {
			if (13 === e.which) {
				startCourse();
				return false;
			}
		}

		function focusField(field) {
			field.removeClass('pdf-input-error');

			setTimeout(function () {
				field.addClass('pdf-input-error');
				field.focus().select();
			}, 20);
		}

		function startCourse() {
			var valEmail = email.val().trim();
			var valName = name.val().trim();

			if (valName.length < 2) {
				focusField(name);
				return false;
			}
			if (valEmail.length < 5) {
				focusField(email);
				return false;
			}
			notice.addClass('loading');
			$.post(ajaxurl, {
				action: 'pfd_start_course',
				name: valName,
				email: valEmail,
				_wpnonce: '<?php echo esc_js( wp_create_nonce( 'onboarding' ) ); ?>'
			}, function (res) {
				notice.removeClass('loading');
				var state = res && res.data ? res.data : '';

				if ('OK' === state) {
					msg.html("🎉 <?php echo wp_kses( __( 'Congratulations! Please check your inbox and look for an email with the subject &quot;<strong>Just one more click for your free content!</strong>&quot; to confirm your registration.', 'divi-popup' ), $kses_args ); ?>");
					msg.append("<br><a href='#' class='dismiss'><?php esc_html_e( 'Close this message', 'divi-popup' ); ?></a>");
				} else if ('DUPLICATE' === state) {
					msg.html("<?php esc_html_e( 'It looks like you already signed up for this course... Please check your inbox or use a different email address.', 'divi-popup' ); ?>");
				} else if ('INVALID_NAME' === state) {
					msg.html("<?php esc_html_e( 'Our system says, your name is invalid. Please check your input.', 'divi-popup' ); ?>");
					focusField(name);
				} else if ('INVALID_EMAIL' === state) {
					msg.html("<?php esc_html_e( 'Our system rejected the email address. Please check your input.', 'divi-popup' ); ?>");
					focusField(email);
				} else {
					msg.html(<?php echo wp_json_encode( wp_kses( __( 'Something went wrong, but we\'re not sure what. Please reload this page and try again. If that does not work, you can contact us via the <a href="https://wordpress.org/support/plugin/popups-for-divi/" target="_blank">wp.org support forum</a>.', 'divi-popup' ), $kses_args ) ); ?>);
				}
			});
		}
	});
</script>
