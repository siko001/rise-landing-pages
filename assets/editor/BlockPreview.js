import { useBlockProps } from '@wordpress/block-editor';
import { useSelect } from '@wordpress/data';

const DEFAULT_COLORS = {
	background: '#FFFFFF',
	panel: '#F5F8F7',
	accent: '#006B64',
	text: '#172D2A',
	muted: '#52635F',
	buttonText: '#FFFFFF',
};

function Text( {
	x,
	y,
	children,
	size = 14,
	color,
	weight = 400,
	fontFamily = 'Arial, Helvetica, sans-serif',
} ) {
	return (
		<text
			x={ x }
			y={ y }
			fill={ color }
			fontFamily={ fontFamily }
			fontSize={ size }
			fontWeight={ weight }
		>
			{ children }
		</text>
	);
}

function Intro( { eyebrow, heading, description, colors } ) {
	return (
		<>
			<Text
				x={ 25 }
				y={ 32 }
				size={ 11 }
				color={ colors.accent }
				weight={ 700 }
			>
				{ eyebrow.toUpperCase() }
			</Text>
			<Text x={ 25 } y={ 64 } size={ 23 } weight={ 700 }>
				{ heading }
			</Text>
			{ description && (
				<Text x={ 25 } y={ 85 } size={ 12 } color={ colors.muted }>
					{ description }
				</Text>
			) }
		</>
	);
}

function PreviewArtwork( { type } ) {
	const siteBrand = window.riseLandingEditor?.settings || {};
	const colors = {
		background: siteBrand.background_color || DEFAULT_COLORS.background,
		panel: siteBrand.surface_color || DEFAULT_COLORS.panel,
		accent: siteBrand.primary_color || DEFAULT_COLORS.accent,
		text: siteBrand.text_color || DEFAULT_COLORS.text,
		muted: siteBrand.muted_color || DEFAULT_COLORS.muted,
		buttonText: siteBrand.button_text_color || DEFAULT_COLORS.buttonText,
	};
	return (
		<svg
			viewBox="0 0 452 256"
			width="452"
			height="256"
			aria-hidden="true"
			fill={ colors.text }
			style={ { display: 'block', width: '100%', height: 'auto' } }
		>
			<rect width="452" height="256" fill={ colors.background } />
			{ type === 'hero' && (
				<>
					<rect
						x="268"
						width="184"
						height="256"
						fill={ colors.panel }
					/>
					<circle
						cx="377"
						cy="82"
						r="56"
						fill={ colors.accent }
						fillOpacity="0.3"
					/>
					<path
						d="M268 255c34-69 63-99 101-94 31 4 62 35 83 74v20Z"
						fill={ colors.accent }
						fillOpacity="0.18"
					/>
					<circle
						cx="380"
						cy="115"
						r="32"
						fill={ colors.accent }
						fillOpacity="0.55"
					/>
					<path
						d="M313 256c6-59 28-97 68-97s65 39 71 97Z"
						fill={ colors.accent }
						fillOpacity="0.38"
					/>
					<Text
						x={ 24 }
						y={ 38 }
						size={ 11 }
						color={ colors.accent }
						weight={ 700 }
					>
						CARE BUILT AROUND YOU
					</Text>
					<Text x={ 24 } y={ 86 } size={ 30 } weight={ 700 }>
						Take your next
					</Text>
					<Text x={ 24 } y={ 122 } size={ 30 } weight={ 700 }>
						step with Rise
					</Text>
					<Text x={ 24 } y={ 153 } size={ 12 } color={ colors.muted }>
						Care and support tailored to your goals.
					</Text>
					<rect
						x="24"
						y="181"
						width="132"
						height="39"
						rx="2"
						fill={ colors.accent }
					/>
					<Text
						x={ 41 }
						y={ 205 }
						size={ 13 }
						color={ colors.buttonText }
						weight={ 700 }
					>
						Get started →
					</Text>
				</>
			) }
			{ type === 'services' && (
				<>
					<Intro
						colors={ colors }
						eyebrow="Our services"
						heading="Find the right support"
						description="Explore ways we can help you move forward."
					/>
					{ [
						'One to one care',
						'Recovery support',
						'Ongoing wellbeing',
					].map( ( label, index ) => {
						const x = 25 + index * 140;
						return (
							<g key={ label }>
								<rect
									x={ x }
									y="105"
									width="127"
									height="126"
									rx="3"
									fill={ colors.panel }
								/>
								<rect
									x={ x }
									y="105"
									width="127"
									height="56"
									fill={ colors.accent }
									fillOpacity={
										[ 0.22, 0.32, 0.18 ][ index ]
									}
								/>
								<circle
									cx={ x + 64 }
									cy="133"
									r="19"
									fill={ colors.accent }
									fillOpacity="0.6"
								/>
								<Text
									x={ x + 12 }
									y={ 186 }
									size={ 13 }
									weight={ 700 }
								>
									{ label }
								</Text>
								<rect
									x={ x + 12 }
									y="203"
									width="82"
									height="4"
									rx="2"
									fill={ colors.muted }
									opacity="0.65"
								/>
								<rect
									x={ x + 12 }
									y="214"
									width="63"
									height="4"
									rx="2"
									fill={ colors.muted }
									opacity="0.4"
								/>
							</g>
						);
					} ) }
				</>
			) }
			{ type === 'process' && (
				<>
					<Intro
						colors={ colors }
						eyebrow="How it works"
						heading="A clear path from the start"
						description="Four simple steps to getting the support you need."
					/>
					<line
						x1="59"
						x2="393"
						y1="142"
						y2="142"
						stroke={ colors.accent }
						strokeWidth="2"
						opacity="0.55"
					/>
					{ [
						'Get in touch',
						'Meet your team',
						'Make a plan',
						'Move forward',
					].map( ( label, index ) => {
						const x = 25 + index * 104;
						return (
							<g key={ label }>
								<circle
									cx={ x + 34 }
									cy="142"
									r="23"
									fill={ colors.accent }
								/>
								<Text
									x={ x + 24 }
									y={ 148 }
									size={ 15 }
									color={ colors.buttonText }
									weight={ 700 }
								>
									{ String( index + 1 ).padStart( 2, '0' ) }
								</Text>
								<Text
									x={ x }
									y={ 198 }
									size={ 12 }
									weight={ 700 }
								>
									{ label }
								</Text>
							</g>
						);
					} ) }
				</>
			) }
			{ type === 'benefits' && (
				<>
					<Intro
						colors={ colors }
						eyebrow="Why choose Rise"
						heading="Support that puts you first"
						description="Thoughtful care for every step of your journey."
					/>
					{ [
						'Built around you',
						'Clear communication',
						'Here for your next step',
					].map( ( label, index ) => {
						const y = 108 + index * 44;
						return (
							<g key={ label }>
								<rect
									x="25"
									y={ y }
									width="402"
									height="36"
									rx="3"
									fill={ colors.panel }
								/>
								<circle
									cx="48"
									cy={ y + 18 }
									r="10"
									fill={ colors.accent }
								/>
								<path
									d={ `M43 ${ y + 18 }l4 4 7-8` }
									fill="none"
									stroke={ colors.buttonText }
									strokeWidth="2"
								/>
								<Text
									x={ 68 }
									y={ y + 23 }
									size={ 13 }
									weight={ 700 }
								>
									{ label }
								</Text>
							</g>
						);
					} ) }
				</>
			) }
			{ type === 'faq' && (
				<>
					<Intro
						colors={ colors }
						eyebrow="Your questions"
						heading="Good to know before you start"
					/>
					{ [
						'How do I get started?',
						'What should I bring?',
						'Where can I find you?',
					].map( ( label, index ) => {
						const y = 87 + index * 52;
						return (
							<g key={ label }>
								<rect
									x="25"
									y={ y }
									width="402"
									height="44"
									rx="3"
									fill={ colors.panel }
								/>
								<Text
									x={ 42 }
									y={ y + 27 }
									size={ 14 }
									weight={ 700 }
								>
									{ label }
								</Text>
								<Text
									x={ 392 }
									y={ y + 28 }
									size={ 21 }
									color={ colors.accent }
								>
									+
								</Text>
							</g>
						);
					} ) }
				</>
			) }
			{ type === 'cta' && (
				<>
					<rect
						x="19"
						y="20"
						width="414"
						height="216"
						rx="3"
						fill={ colors.panel }
					/>
					<Text
						x={ 44 }
						y={ 62 }
						size={ 11 }
						color={ colors.accent }
						weight={ 700 }
					>
						TAKE THE NEXT STEP
					</Text>
					<Text x={ 44 } y={ 105 } size={ 29 } weight={ 700 }>
						Ready to get started?
					</Text>
					<Text x={ 44 } y={ 134 } size={ 13 } color={ colors.muted }>
						Get in touch with our team to discuss
					</Text>
					<Text x={ 44 } y={ 152 } size={ 13 } color={ colors.muted }>
						the right option for you.
					</Text>
					<rect
						x="44"
						y="175"
						width="126"
						height="39"
						rx="2"
						fill={ colors.accent }
					/>
					<Text
						x={ 61 }
						y={ 199 }
						size={ 13 }
						color={ colors.buttonText }
						weight={ 700 }
					>
						Get in touch →
					</Text>
				</>
			) }
			{ type === 'spacer' && (
				<>
					<rect
						x="25"
						y="29"
						width="402"
						height="50"
						rx="3"
						fill={ colors.panel }
					/>
					<rect
						x="25"
						y="177"
						width="402"
						height="50"
						rx="3"
						fill={ colors.panel }
					/>
					<line
						x1="226"
						x2="226"
						y1="87"
						y2="169"
						stroke={ colors.accent }
						strokeWidth="2"
						strokeDasharray="5 5"
					/>
					<path
						d="M220 93l6-6 6 6M220 163l6 6 6-6"
						fill="none"
						stroke={ colors.accent }
						strokeWidth="2"
					/>
					<rect
						x="167"
						y="112"
						width="118"
						height="32"
						rx="16"
						fill={ colors.accent }
					/>
					<Text
						x={ 192 }
						y={ 133 }
						size={ 13 }
						color={ colors.buttonText }
						weight={ 700 }
					>
						Space · 64 px
					</Text>
				</>
			) }
			{ type === 'separator' && (
				<>
					<rect
						x="0"
						y="70"
						width="452"
						height="116"
						fill={ colors.accent }
					/>
					<Text
						x={ 22 }
						y={ 136 }
						size={ 30 }
						color={ colors.buttonText }
						weight={ 700 }
						fontFamily="F37 Judge, F37Judge, Arial, sans-serif"
					>
						R
					</Text>
					<Text
						x={ 88 }
						y={ 136 }
						size={ 27 }
						color={ colors.buttonText }
						weight={ 700 }
						fontFamily="F37 Judge, F37Judge, Arial, sans-serif"
					>
						RISE
					</Text>
					<Text
						x={ 190 }
						y={ 136 }
						size={ 30 }
						color={ colors.buttonText }
						weight={ 700 }
						fontFamily="F37 Judge, F37Judge, Arial, sans-serif"
					>
						R
					</Text>
					<Text
						x={ 247 }
						y={ 135 }
						size={ 22 }
						color={ colors.buttonText }
						weight={ 700 }
						fontFamily="F37 Judge, F37Judge, Arial, sans-serif"
					>
						MOVE WITH RISE
					</Text>
				</>
			) }
		</svg>
	);
}

function InserterPreview( { type } ) {
	return (
		<div { ...useBlockProps( { style: { width: '100%', margin: 0 } } ) }>
			<PreviewArtwork type={ type } />
		</div>
	);
}

export default function withInserterPreview( type, Edit ) {
	return function EditWithPreview( props ) {
		const isPreview = useSelect(
			( select ) =>
				select( 'core/block-editor' ).getSettings()?.isPreviewMode,
			[]
		);
		return isPreview ? (
			<InserterPreview type={ type } />
		) : (
			<Edit { ...props } />
		);
	};
}
