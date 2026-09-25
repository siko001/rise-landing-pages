	<?php if ( 'landing' === $rise_footer ) : ?>
		<footer class="rise-lp__footer">
			<div class="rise-lp__container">
				<div class="rise-lp__footer-grid<?php echo 'multiple' === $rise_settings['contact_mode'] ? ' rise-lp__footer-grid--multiple' : ''; ?>">
					<div class="rise-lp__footer-brand">
						<a class="rise-lp__brand" href="<?php echo esc_url( home_url( '/' ) ); ?>" rel="home">
							<?php if ( $rise_footer_logo ) : ?>
								<?php echo $rise_footer_logo; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped by wp_get_attachment_image. ?>
							<?php else : ?>
								<span class="rise-lp__brand-name"><?php echo esc_html( $rise_brand ); ?></span>
							<?php endif; ?>
						</a>
						<?php if ( 'single' === $rise_settings['contact_mode'] && ! empty( $rise_settings['company_details'] ) ) : ?>
							<div class="rise-lp__company-details"><?php echo nl2br( esc_html( $rise_settings['company_details'] ) ); ?></div>
						<?php endif; ?>
					</div>
					<?php if ( 'multiple' === $rise_settings['contact_mode'] && ! empty( $rise_settings['companies'] ) ) : ?>
						<div class="rise-lp__footer-companies">
							<?php foreach ( $rise_settings['companies'] as $rise_company ) : ?>
								<div class="rise-lp__footer-company">
									<h2 class="rise-lp__footer-heading"><?php echo esc_html( $rise_company['name'] ?: __( 'Location', 'rise-landing-pages' ) ); ?></h2>
									<?php if ( $rise_company['phones'] || $rise_company['email'] || $rise_company['address'] || $rise_company['map_url'] ) : ?>
										<address class="rise-lp__address">
											<?php if ( $rise_company['address'] ) : ?>
												<div><?php echo nl2br( esc_html( $rise_company['address'] ) ); ?></div>
											<?php endif; ?>
											<?php if ( $rise_company['map_url'] ) : ?>
												<a class="rise-lp__map-link" href="<?php echo esc_url( $rise_company['map_url'] ); ?>" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'View on Google Maps', 'rise-landing-pages' ); ?></a>
											<?php endif; ?>
											<?php foreach ( explode( "\n", $rise_company['phones'] ) as $rise_phone ) : ?>
												<?php if ( $rise_phone ) : ?><a href="<?php echo esc_url( 'tel:' . preg_replace( '/[^0-9+]/', '', $rise_phone ) ); ?>"><?php echo esc_html( $rise_phone ); ?></a><?php endif; ?>
											<?php endforeach; ?>
											<?php if ( $rise_company['email'] ) : ?>
												<a href="<?php echo esc_url( 'mailto:' . $rise_company['email'] ); ?>"><?php echo esc_html( $rise_company['email'] ); ?></a>
											<?php endif; ?>
										</address>
									<?php endif; ?>
									<?php if ( $rise_company['company_details'] ) : ?>
										<div class="rise-lp__company-details"><?php echo nl2br( esc_html( $rise_company['company_details'] ) ); ?></div>
									<?php endif; ?>
								</div>
							<?php endforeach; ?>
						</div>
					<?php elseif ( 'single' === $rise_settings['contact_mode'] && ( ! empty( $rise_settings['phone'] ) || ! empty( $rise_settings['additional_phones'] ) || ! empty( $rise_settings['email'] ) || ! empty( $rise_settings['address'] ) || ! empty( $rise_settings['map_url'] ) ) ) : ?>
						<div class="rise-lp__footer-contact">
							<h2 class="rise-lp__footer-heading"><?php esc_html_e( 'Get in touch', 'rise-landing-pages' ); ?></h2>
							<address class="rise-lp__address">
								<?php if ( ! empty( $rise_settings['address'] ) ) : ?>
									<div><?php echo nl2br( esc_html( $rise_settings['address'] ) ); ?></div>
								<?php endif; ?>
								<?php if ( ! empty( $rise_settings['map_url'] ) ) : ?>
									<a class="rise-lp__map-link" href="<?php echo esc_url( $rise_settings['map_url'] ); ?>" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'View on Google Maps', 'rise-landing-pages' ); ?></a>
								<?php endif; ?>
								<?php if ( ! empty( $rise_settings['phone'] ) ) : ?>
									<a href="<?php echo esc_url( 'tel:' . preg_replace( '/[^0-9+]/', '', $rise_settings['phone'] ) ); ?>"><?php echo esc_html( $rise_settings['phone'] ); ?></a>
								<?php endif; ?>
								<?php foreach ( explode( "\n", $rise_settings['additional_phones'] ) as $rise_phone ) : ?>
									<?php if ( $rise_phone ) : ?><a href="<?php echo esc_url( 'tel:' . preg_replace( '/[^0-9+]/', '', $rise_phone ) ); ?>"><?php echo esc_html( $rise_phone ); ?></a><?php endif; ?>
								<?php endforeach; ?>
								<?php if ( ! empty( $rise_settings['email'] ) ) : ?>
									<a href="<?php echo esc_url( 'mailto:' . $rise_settings['email'] ); ?>"><?php echo esc_html( $rise_settings['email'] ); ?></a>
								<?php endif; ?>
							</address>
						</div>
					<?php endif; ?>
				</div>
				<div class="rise-lp__footer-bottom">
					<p class="rise-lp__copyright">&copy; <?php echo esc_html( wp_date( 'Y' ) . ' ' . $rise_brand ); ?></p>
					<?php if ( $rise_footer_links ) : ?>
						<nav class="rise-lp__footer-links" aria-label="<?php esc_attr_e( 'Footer links', 'rise-landing-pages' ); ?>">
							<?php foreach ( $rise_footer_links as $rise_link ) : ?>
								<?php if ( is_array( $rise_link ) && ! empty( $rise_link['label'] ) && ! empty( $rise_link['url'] ) ) : ?>
									<a href="<?php echo esc_url( $rise_link['url'] ); ?>"><?php echo esc_html( $rise_link['label'] ); ?></a>
								<?php endif; ?>
							<?php endforeach; ?>
						</nav>
					<?php endif; ?>
				</div>
			</div>
		</footer>
	<?php endif; ?>
