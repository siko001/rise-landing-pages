import { Button } from '@wordpress/components';
import { __ } from '@wordpress/i18n';

export default function SpacerInserter( { className = '', label, onClick } ) {
	return (
		<div className={ `rise-lp-editor__spacer-slot ${ className }` }>
			<Button icon="plus" aria-label={ label } onClick={ onClick }>
				<span className="rise-lp-editor__spacer-label">
					{ __( 'Add a spacer', 'rise-landing-pages' ) }
				</span>
			</Button>
		</div>
	);
}
