<?php
namespace RiseLandingPages\Blocks;

/** Editable campaign starting points assembled entirely from Rise blocks. */
final class Patterns {
	const CATEGORY = 'rise-campaigns';

	public function register() {
		register_block_pattern_category( self::CATEGORY, array( 'label' => __( 'Rise campaigns', 'rise-landing-pages' ) ) );
		foreach ( self::definitions() as $slug => $pattern ) {
			register_block_pattern( 'rise-landing/' . $slug, array(
				'title'       => $pattern['title'],
				'description' => $pattern['description'],
				'categories'  => array( self::CATEGORY ),
				'keywords'    => $pattern['keywords'],
				'postTypes'   => array( 'page' ),
				'content'     => self::content( $pattern['blocks'] ),
			) );
		}
	}

	public static function names() {
		return array_map( static function ( $slug ) { return 'rise-landing/' . $slug; }, array_keys( self::definitions() ) );
	}

	private static function block( $name, $attributes = array() ) {
		return array( 'blockName' => 'rise-landing/' . $name, 'attrs' => $attributes, 'innerBlocks' => array(), 'innerHTML' => '', 'innerContent' => array() );
	}

	private static function content( $blocks ) {
		return serialize_blocks( $blocks );
	}

	public static function definitions() {
		return array(
			'physio-recovery' => array(
				'title' => __( 'Physio · Recovery journey', 'rise-landing-pages' ),
				'description' => __( 'A complete appointment campaign: split hero, image service cards, benefits, circular process, questions and booking prompt. Add campaign imagery and a booking link.', 'rise-landing-pages' ),
				'keywords' => array( 'physio', 'recovery', 'appointment' ),
				'blocks' => array(
					self::block( 'hero', array( 'layout' => 'split', 'mediaSide' => 'right', 'sectionBackground' => 'offwhite', 'eyebrow' => 'Rise Physio', 'heading' => 'Move forward with support that fits you', 'description' => 'Start with a conversation about your goals, then build a plan with the Rise Physio team.', 'ctaLabel' => 'Book a physio appointment', 'reassurance' => 'Your next step starts with a clear plan.' ) ),
					self::block( 'services', array( 'eyebrow' => 'Ways we can help', 'heading' => 'Explore your next step', 'intro' => 'Add an image and approved service details to each card.', 'cardLayout' => 'image', 'gridColumns' => 3, 'imageGridAlignment' => 'center', 'items' => array( array( 'title' => 'Your first visit', 'description' => 'Introduce the first appointment and what it covers.', 'ctaLabel' => 'Ask about a first visit' ), array( 'title' => 'A follow-up plan', 'description' => 'Explain how ongoing support can work.', 'ctaLabel' => 'Ask about follow-up' ), array( 'title' => 'Support for your goals', 'description' => 'Add a campaign-specific service here.', 'ctaLabel' => 'Explore this service' ) ) ) ),
					self::block( 'benefits', array( 'eyebrow' => 'Why Rise Physio', 'heading' => 'Care that moves with you', 'intro' => 'Show the practical difference your team makes.', 'layout' => 'left', 'sectionBackground' => 'white', 'items' => array( array( 'title' => 'Built around your goals', 'description' => 'Explain how appointments are shaped around each person.' ), array( 'title' => 'A plan you can follow', 'description' => 'Describe the guidance people receive between visits.' ), array( 'title' => 'Progress you can understand', 'description' => 'Show how the team reviews next steps together.' ) ) ) ),
					self::block( 'process', array( 'layout' => 'circle', 'sectionBackground' => 'offwhite', 'heading' => 'Your path with Rise Physio', 'items' => array( array( 'number' => '01', 'title' => 'Get in touch', 'description' => 'Tell us what you would like help with.' ), array( 'number' => '02', 'title' => 'Meet your physio', 'description' => 'Discuss your goals and current situation.' ), array( 'number' => '03', 'title' => 'Make a plan', 'description' => 'Agree on a practical next step together.' ) ) ) ),
					self::block( 'faq', array( 'heading' => 'Before your first visit', 'sectionBackground' => 'white', 'items' => array( array( 'question' => 'How do I book?', 'answer' => 'Add your booking instructions here.' ), array( 'question' => 'What should I bring?', 'answer' => 'Add any useful preparation details here.' ), array( 'question' => 'What happens after my appointment?', 'answer' => 'Explain how follow-up is arranged.' ) ) ) ),
					self::block( 'cta', array( 'heading' => 'Ready to take the next step?', 'description' => 'Talk to the Rise Physio team about a starting point that works for you.', 'ctaLabel' => 'Book a physio appointment', 'sectionBackground' => 'surface' ) ),
				),
			),
			'fitness-programme' => array(
				'title' => __( 'Fitness · Programme launch', 'rise-landing-pages' ),
				'description' => __( 'An energetic programme page with an overlay hero, moving brand strip, pricing cards and a final action. Add campaign media, prices and links.', 'rise-landing-pages' ),
				'keywords' => array( 'fitness', 'programme', 'pricing' ),
				'blocks' => array(
					self::block( 'hero', array( 'layout' => 'overlay', 'alignment' => 'center', 'eyebrow' => 'Rise Fitness', 'heading' => 'Find your way to move', 'description' => 'Introduce the programme, who it is for and the result visitors can work towards.', 'ctaLabel' => 'Explore the programme', 'overlayOpacity' => 65 ) ),
					self::block( 'separator', array( 'background' => 'fitness', 'texts' => array( 'Find your momentum', 'Move with Rise' ), 'dividerLogoChoice' => 'fitnessMark' ) ),
					self::block( 'services', array( 'eyebrow' => 'Choose your route', 'heading' => 'Ways to get started', 'intro' => 'Replace these examples with your current programmes and prices.', 'cardLayout' => 'pricing', 'gridColumns' => 3, 'sectionBackground' => 'offwhite', 'items' => array( array( 'title' => 'Start', 'badge' => 'First step', 'features' => array( 'Describe the sessions included', 'Add who this option suits' ), 'price' => 'Add price', 'ctaLabel' => 'Ask about Start' ), array( 'title' => 'Build', 'features' => array( 'Describe the sessions included', 'Add a key benefit' ), 'price' => 'Add price', 'ctaLabel' => 'Ask about Build' ), array( 'title' => 'Progress', 'features' => array( 'Describe the sessions included', 'Add a key benefit' ), 'price' => 'Add price', 'ctaLabel' => 'Ask about Progress' ) ) ) ),
					self::block( 'benefits', array( 'eyebrow' => 'The Rise approach', 'heading' => 'More reasons to begin', 'layout' => 'center', 'centerItems' => true, 'items' => array( array( 'title' => 'A place to start', 'description' => 'Explain how new members can settle in.' ), array( 'title' => 'Support along the way', 'description' => 'Show how coaches help people stay engaged.' ), array( 'title' => 'Room to progress', 'description' => 'Describe how the programme adapts over time.' ) ) ) ),
					self::block( 'cta', array( 'heading' => 'Make your next move', 'description' => 'Find the option that works for your goals and schedule.', 'ctaLabel' => 'Get started', 'sectionBackground' => 'surface' ) ),
				),
			),
			'medical-service' => array(
				'title' => __( 'Medical · Service explainer', 'rise-landing-pages' ),
				'description' => __( 'A calm service page with a stacked hero, service cards, clear steps, practical questions and a consultation prompt.', 'rise-landing-pages' ),
				'keywords' => array( 'medical', 'service', 'consultation' ),
				'blocks' => array(
					self::block( 'hero', array( 'layout' => 'stacked', 'sectionBackground' => 'surface', 'eyebrow' => 'Rise Medical', 'heading' => 'Clear support for your next step', 'description' => 'Introduce the service, who it helps and what visitors can expect.', 'ctaLabel' => 'Enquire about this service' ) ),
					self::block( 'services', array( 'eyebrow' => 'Your options', 'heading' => 'Explore the support available', 'intro' => 'Replace these cards with the services relevant to this campaign.', 'cardLayout' => 'standard', 'gridColumns' => 2, 'items' => array( array( 'title' => 'Assessment', 'description' => 'Explain what this appointment covers and who it is for.', 'ctaLabel' => 'Ask about assessment' ), array( 'title' => 'Follow-up', 'description' => 'Explain the support available after a first visit.', 'ctaLabel' => 'Ask about follow-up' ) ) ) ),
					self::block( 'process', array( 'heading' => 'What happens next', 'layout' => 'classic', 'sectionBackground' => 'offwhite', 'items' => array( array( 'number' => '01', 'title' => 'Contact the team', 'description' => 'Share what you need help with.' ), array( 'number' => '02', 'title' => 'Arrange a visit', 'description' => 'Choose a suitable appointment.' ), array( 'number' => '03', 'title' => 'Discuss your options', 'description' => 'Agree on the next step together.' ) ) ) ),
					self::block( 'faq', array( 'heading' => 'Useful details', 'faqMediaMode' => 'text', 'items' => array( array( 'question' => 'How do I arrange an appointment?', 'answer' => 'Add the current booking route and contact details.' ), array( 'question' => 'Where will my appointment take place?', 'answer' => 'Add the location and access details.' ), array( 'question' => 'What happens afterwards?', 'answer' => 'Explain the follow-up process.' ) ) ) ),
					self::block( 'cta', array( 'heading' => 'Speak with Rise Medical', 'description' => 'Ask the team about the service and the right way to begin.', 'ctaLabel' => 'Make an enquiry', 'sectionBackground' => 'surface' ) ),
				),
			),
			'cross-brand-pathway' => array(
				'title' => __( 'Cross-brand · Your Rise pathway', 'rise-landing-pages' ),
				'description' => __( 'Introduce Medical, Fitness and Physio together. Each service card needs its own destination before it shows a link.', 'rise-landing-pages' ),
				'keywords' => array( 'cross-brand', 'medical', 'fitness', 'physio' ),
				'blocks' => array(
					self::block( 'hero', array( 'layout' => 'split', 'eyebrow' => 'The Rise family', 'heading' => 'Find the support that fits your next step', 'description' => 'Explore care, movement and training across the Rise family.', 'showCta' => false, 'sectionBackground' => 'offwhite' ) ),
					self::block( 'services', array( 'eyebrow' => 'Three ways forward', 'heading' => 'One connected approach', 'intro' => 'Add the destination for each brand in its card settings before publishing.', 'cardLayout' => 'standard', 'gridColumns' => 3, 'items' => array( array( 'title' => 'Rise Medical', 'description' => 'Find medical support and a clear next step.', 'ctaLabel' => 'Explore Rise Medical', 'requireExplicitCta' => true ), array( 'title' => 'Rise Fitness', 'description' => 'Find a way to move that fits your goals.', 'ctaLabel' => 'Explore Rise Fitness', 'requireExplicitCta' => true ), array( 'title' => 'Rise Physio', 'description' => 'Find support for movement and recovery.', 'ctaLabel' => 'Explore Rise Physio', 'requireExplicitCta' => true ) ) ) ),
					self::block( 'separator', array( 'background' => 'medical', 'texts' => array( 'Care', 'Movement', 'Progress' ), 'dividerLogoChoice' => 'medical' ) ),
					self::block( 'benefits', array( 'heading' => 'Start with what matters to you', 'intro' => 'Use this section to explain when each team can help.', 'sectionBackground' => 'offwhite', 'items' => array( array( 'title' => 'Care', 'description' => 'Introduce the Medical team and its role.' ), array( 'title' => 'Recover', 'description' => 'Introduce the Physio team and its role.' ), array( 'title' => 'Build', 'description' => 'Introduce the Fitness team and its role.' ) ) ) ),
			),
			),
			'cross-brand-handoff' => array(
				'title' => __( 'Cross-brand · Next-step handoff', 'rise-landing-pages' ),
				'description' => __( 'A compact campaign section that promotes another Rise brand after the main message. Add a destination to the final action.', 'rise-landing-pages' ),
				'keywords' => array( 'cross-brand', 'handoff', 'referral' ),
				'blocks' => array(
					self::block( 'spacer', array( 'height' => 48, 'mobileHeight' => 24 ) ),
					self::block( 'separator', array( 'background' => 'physio', 'texts' => array( 'Your next step with Rise' ), 'dividerLogoChoice' => 'physio' ) ),
					self::block( 'cta', array( 'eyebrow' => 'More from the Rise family', 'heading' => 'Keep moving with Rise Physio', 'description' => 'Introduce a relevant Physio service and explain why visitors may want to explore it.', 'ctaLabel' => 'Explore Rise Physio', 'requireExplicitCta' => true, 'sectionBackground' => 'offwhite' ) ),
			),
			),
		);
	}
}
