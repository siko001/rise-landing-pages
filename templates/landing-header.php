	<?php if ( 'minimal' === $rise_header ) : ?>
		<header class="rise-lp__header">
			<div class="rise-lp__container rise-lp__header-inner">
				<a class="rise-lp__brand" href="<?php echo esc_url( home_url( '/' ) ); ?>" rel="home">
					<?php if ( $rise_logo ) : ?>
						<?php echo $rise_logo; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped by wp_get_attachment_image. ?>
					<?php else : ?>
						<span class="rise-lp__brand-name"><?php echo esc_html( $rise_brand ); ?></span>
					<?php endif; ?>
				</a>
				<?php if ( ! empty( $rise_settings['about_url'] ) || ( ! empty( $rise_settings['header_cta'] ) && ! empty( $rise_cta['url'] ) ) ) : ?>
					<nav class="rise-lp__header-nav" aria-label="<?php esc_attr_e( 'Landing page navigation', 'rise-landing-pages' ); ?>">
						<?php if ( ! empty( $rise_settings['about_url'] ) ) : ?>
							<a class="rise-lp__nav-link" href="<?php echo esc_url( $rise_settings['about_url'] ); ?>"><?php esc_html_e( 'About', 'rise-landing-pages' ); ?></a>
						<?php endif; ?>
						<?php if ( ! empty( $rise_settings['header_cta'] ) ) : ?>
							<?php echo \RiseLandingPages\Frontend\Renderer::button( $rise_cta['label'], $rise_cta['url'] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Renderer escapes its output. ?>
						<?php endif; ?>
					</nav>
				<?php endif; ?>
			</div>
		</header>
	<?php endif; ?>
