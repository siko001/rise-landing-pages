import spacer from '../blocks/spacer/block.json';
import Spacer from './editor/Spacer';
import separator from '../blocks/separator/block.json';
import Separator from './editor/Separator';
import { registerBlockType } from '@wordpress/blocks';
import { InnerBlocks } from '@wordpress/block-editor';
import { registerPlugin } from '@wordpress/plugins';
import hero from '../blocks/hero/block.json';
import heroPart from '../blocks/hero-part/block.json';
import services from '../blocks/services/block.json';
import process from '../blocks/process/block.json';
import benefits from '../blocks/benefits/block.json';
import faq from '../blocks/faq/block.json';
import cta from '../blocks/cta/block.json';
import Hero from './editor/Hero';
import './editor/line-height-format';
import HeroPart from './editor/HeroPart';
import Collection from './editor/Collection';
import Cta from './editor/Cta';
import PageSettings from './editor/PageSettings';
import ChromePreview from './editor/ChromePreview';
import withInserterPreview from './editor/BlockPreview';
import './editor/resizable-sidebar';
import './editor.css';

registerBlockType( hero.name, {
	...hero,
	edit: withInserterPreview( 'hero', Hero ),
	save: () => <InnerBlocks.Content />,
} );
registerBlockType( heroPart.name, {
	...heroPart,
	edit: HeroPart,
	save: () => null,
} );
registerBlockType( cta.name, {
	...cta,
	edit: withInserterPreview( 'cta', Cta ),
	save: () => null,
} );

[ services, process, benefits, faq ].forEach( ( metadata ) => {
	registerBlockType( metadata.name, {
		...metadata,
		edit: withInserterPreview(
			metadata.name.split( '/' )[ 1 ],
			( props ) => (
				<Collection
					{ ...props }
					type={ metadata.name.split( '/' )[ 1 ] }
				/>
			)
		),
		save: () =>
			[ services.name, process.name ].includes( metadata.name ) ? (
				<InnerBlocks.Content />
			) : null,
	} );
} );

if ( window.riseLandingEditor?.isLanding ) {
	registerPlugin( 'rise-landing-page-settings', { render: PageSettings } );
	registerPlugin( 'rise-landing-chrome-preview', { render: ChromePreview } );
}

registerBlockType( spacer.name, {
	...spacer,
	edit: withInserterPreview( 'spacer', Spacer ),
	save: () => null,
} );

registerBlockType( separator.name, {
	...separator,
	edit: withInserterPreview( 'separator', Separator ),
	save: () => null,
} );
