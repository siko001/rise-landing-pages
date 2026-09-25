import { LandingRichText as RichText } from './outline-format';
import { __ } from '@wordpress/i18n';
import {
	ActionPreview,
	BODY_FORMATS,
	EYEBROW_FORMATS,
	HEADING_FORMATS,
	SectionFrame,
} from './common';

export default function Cta( { attributes, setAttributes } ) {
	return (
		<SectionFrame
			{ ...{ attributes, setAttributes } }
			title={ __( 'Call to action', 'rise-landing-pages' ) }
		>
			<section className="rise-lp__section rise-lp__cta">
				<div className="rise-lp__container">
					<div className="rise-lp__cta-panel">
						<div className="rise-lp__cta-copy">
							<RichText
								tagName="p"
								className="rise-lp__eyebrow"
								value={ attributes.eyebrow }
								onChange={ ( eyebrow ) =>
									setAttributes( { eyebrow } )
								}
								placeholder={ __(
									'Supporting text (optional)',
									'rise-landing-pages'
								) }
								aria-label={ __(
									'Supporting text',
									'rise-landing-pages'
								) }
								allowedFormats={ EYEBROW_FORMATS }
							/>
							<RichText
								tagName="h2"
								className="rise-lp__heading"
								value={ attributes.heading }
								onChange={ ( heading ) =>
									setAttributes( { heading } )
								}
								placeholder={ __(
									'Your invitation to get started',
									'rise-landing-pages'
								) }
								aria-label={ __(
									'Call to action heading',
									'rise-landing-pages'
								) }
								allowedFormats={ HEADING_FORMATS }
							/>
							<RichText
								tagName="p"
								className="rise-lp__intro"
								value={ attributes.description }
								onChange={ ( description ) =>
									setAttributes( { description } )
								}
								placeholder={ __(
									'Tell visitors what to do next',
									'rise-landing-pages'
								) }
								aria-label={ __(
									'Call to action description',
									'rise-landing-pages'
								) }
								allowedFormats={ BODY_FORMATS }
							/>
						</div>
						<div className="rise-lp__cta-actions">
							<ActionPreview
								attributes={ attributes }
								setAttributes={ setAttributes }
								secondary
							/>
						</div>
					</div>
				</div>
			</section>
		</SectionFrame>
	);
}
