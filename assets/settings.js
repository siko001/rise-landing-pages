/* global riseLandingSettings */
( function () {
	'use strict';
	const strings = riseLandingSettings;
	const sections = [
		...document.querySelectorAll( '.rise-settings__section' ),
	];
	const quickLinks = [
		...document.querySelectorAll( '.rise-settings__quick-menu a' ),
	];
	let applyContactPreset;
	let applyLinksPreset;

	function setActiveSection( activeSection ) {
		quickLinks.forEach( function ( link ) {
			if ( activeSection && link.hash === '#' + activeSection.id ) {
				link.setAttribute( 'aria-current', 'location' );
			} else {
				link.removeAttribute( 'aria-current' );
			}
		} );
	}

	sections.forEach( function ( section ) {
		section.addEventListener( 'toggle', function () {
			if ( section.open ) {
				sections.forEach( function ( otherSection ) {
					if ( otherSection !== section ) {
						otherSection.open = false;
					}
				} );
				setActiveSection( section );
			} else if ( ! sections.some( ( item ) => item.open ) ) {
				setActiveSection( null );
			}
		} );
	} );

	quickLinks.forEach( function ( link ) {
		link.addEventListener( 'click', function ( event ) {
			const section = document.getElementById( link.hash.slice( 1 ) );
			if ( ! section ) {
				return;
			}
			event.preventDefault();
			sections.forEach( function ( otherSection ) {
				otherSection.open = otherSection === section;
			} );
			setActiveSection( section );
			section.scrollIntoView( { block: 'start' } );
			section.querySelector( 'summary' ).focus( { preventScroll: true } );
		} );
	} );

	document
		.querySelectorAll( '.rise-settings-presets' )
		.forEach( function ( container ) {
			const select = container.querySelector( '#rise-brand-preset' );
			const apply = container.querySelector(
				'.rise-settings-presets__apply'
			);
			const status = container.querySelector(
				'.rise-settings-presets__status'
			);
			apply.addEventListener( 'click', function () {
				const preset = strings.presets[ select.value ];
				if ( ! preset ) {
					return;
				}
				Object.entries( preset.values ).forEach( function ( [
					key,
					value,
				] ) {
					const field = document.getElementById(
						'rise-setting-' + key
					);
					if ( field ) {
						field.value = value;
						field.dispatchEvent(
							new Event( 'change', { bubbles: true } )
						);
					}
				} );
				if (
					Object.prototype.hasOwnProperty.call(
						preset.values,
						'logo_preset'
					)
				) {
					[ 'logo_id', 'alternate_logo_id' ].forEach(
						function ( key ) {
							const field = document.getElementById(
								'rise-setting-' + key
							);
							if ( field ) {
								field.value = '0';
								field.dispatchEvent(
									new Event( 'change', { bubbles: true } )
								);
							}
						}
					);
				}
				if ( preset.contact && applyContactPreset ) {
					applyContactPreset( preset.contact );
				}
				if ( preset.links && applyLinksPreset ) {
					applyLinksPreset( preset.links );
				}
				status.textContent = strings.presetApplied;
			} );
		} );

	document
		.querySelectorAll( '.rise-settings-media' )
		.forEach( function ( container ) {
			const input = container.querySelector(
				'.rise-settings-media__value'
			);
			const preview = container.querySelector(
				'.rise-settings-media__preview'
			);
			const choose = container.querySelector(
				'.rise-settings-media__choose'
			);
			const remove = container.querySelector(
				'.rise-settings-media__remove'
			);
			let frame;

			function showFallback() {
				const preset = document.getElementById(
					'rise-setting-logo_preset'
				);
				const url =
					container.dataset.logoRole === 'primary' && preset
						? strings.presetLogoUrls[ preset.value ]
						: '';
				if ( url ) {
					const image = document.createElement( 'img' );
					image.src = url;
					image.alt = '';
					image.className = 'rise-settings-media__image';
					preview.replaceChildren( image );
				} else {
					preview.textContent = strings.noLogo;
				}
				remove.hidden = true;
			}

			input.addEventListener( 'change', function () {
				if ( ! input.value || input.value === '0' ) {
					showFallback();
				}
			} );

			choose.addEventListener( 'click', function () {
				if ( ! frame ) {
					frame = wp.media( {
						title: strings.mediaTitle,
						button: { text: strings.mediaUse },
						library: { type: 'image' },
						multiple: false,
					} );
					frame.on( 'select', function () {
						const attachment = frame
							.state()
							.get( 'selection' )
							.first();
						if ( ! attachment ) {
							return;
						}
						const selected = attachment.toJSON();
						if ( selected.type !== 'image' ) {
							return;
						}
						const image = document.createElement( 'img' );
						image.src =
							selected.sizes && selected.sizes.medium
								? selected.sizes.medium.url
								: selected.url;
						image.alt = '';
						image.className = 'rise-settings-media__image';
						preview.replaceChildren( image );
						input.value = selected.id;
						remove.hidden = false;
					} );
					frame.on( 'open', function () {
						if ( input.value && input.value !== '0' ) {
							const attachment = wp.media.attachment(
								Number( input.value )
							);
							attachment.fetch();
							frame.state().get( 'selection' ).add( attachment );
						}
					} );
				}
				frame.open();
			} );

			remove.addEventListener( 'click', function () {
				input.value = '0';
				showFallback();
				choose.focus();
			} );
		} );

	document
		.querySelectorAll( '.rise-settings-links' )
		.forEach( function ( container ) {
			const rows = container.querySelector(
				'.rise-settings-links__rows'
			);
			const add = container.querySelector( '.rise-settings-links__add' );
			const status = container.querySelector(
				'.rise-settings-links__status'
			);
			let nextIndex = rows.children.length;

			function updateLimit() {
				add.disabled = rows.children.length >= 20;
			}

			function addFooterLink( values = {} ) {
				const row = document.createElement( 'div' );
				row.className = 'rise-settings-links__row';
				const label = document.createElement( 'input' );
				label.type = 'text';
				label.name =
					'rise_landing_settings[footer_links][' +
					nextIndex +
					'][label]';
				label.placeholder = strings.linkLabel;
				label.setAttribute( 'aria-label', strings.linkLabel );
				label.value = values.label || '';
				const url = document.createElement( 'input' );
				url.type = 'text';
				url.inputMode = 'url';
				url.name =
					'rise_landing_settings[footer_links][' +
					nextIndex +
					'][url]';
				url.placeholder = 'https://';
				url.setAttribute( 'aria-label', strings.linkUrl );
				url.value = values.url || '';
				const remove = document.createElement( 'button' );
				remove.type = 'button';
				remove.className =
					'button-link-delete rise-settings-links__remove';
				remove.textContent = strings.remove;
				row.append( label, url, remove );
				rows.appendChild( row );
				nextIndex += 1;
				updateLimit();
				return label;
			}

			applyLinksPreset = ( links ) => {
				[ 'privacy_url', 'terms_url', 'about_url' ].forEach(
					( key ) => {
						if (
							! Object.prototype.hasOwnProperty.call( links, key )
						) {
							return;
						}
						const field = document.getElementById(
							'rise-setting-' + key
						);
						field.value = links[ key ] || '';
					}
				);
				if ( Array.isArray( links.footer_links ) ) {
					rows.replaceChildren();
					nextIndex = 0;
					links.footer_links.forEach( addFooterLink );
					updateLimit();
				}
			};

			add.addEventListener( 'click', function () {
				if ( rows.children.length >= 20 ) {
					return;
				}
				const label = addFooterLink();
				status.textContent = strings.added;
				label.focus();
			} );

			rows.addEventListener( 'click', function ( event ) {
				const remove = event.target.closest(
					'.rise-settings-links__remove'
				);
				if ( ! remove ) {
					return;
				}
				const row = remove.closest( '.rise-settings-links__row' );
				const following =
					row.nextElementSibling || row.previousElementSibling;
				row.remove();
				updateLimit();
				status.textContent = strings.removed;
				if ( following ) {
					following.querySelector( 'input' ).focus();
				} else {
					add.focus();
				}
			} );
			updateLimit();
		} );

	const contactMode = document.getElementById( 'rise-setting-contact_mode' );
	const companies = document.querySelector( '.rise-settings-companies' );
	if ( contactMode && companies ) {
		const singleFields = [
			'phone',
			'additional_phones',
			'email',
			'address',
			'map_url',
			'company_details',
		].map( ( key ) =>
			document.getElementById( 'rise-setting-' + key )?.closest( 'tr' )
		);
		const companyField = companies.closest( 'tr' );
		const rows = companies.querySelector(
			'.rise-settings-companies__rows'
		);
		const template = companies.querySelector(
			'.rise-settings-companies__template'
		);
		const add = companies.querySelector( '.rise-settings-companies__add' );
		const status = companies.querySelector(
			'.rise-settings-companies__status'
		);
		function updateRows() {
			[ ...rows.children ].forEach( ( row, index ) => {
				const name = row.querySelector( '[name$="[name]"]' );
				row.querySelector(
					'.rise-settings-companies__title'
				).textContent =
					name.value.trim() ||
					strings.companyLabel + ' ' + ( index + 1 );
				row.querySelectorAll( '[name]' ).forEach( ( field ) => {
					field.name = field.name.replace(
						/\[companies\]\[\d+\]/,
						'[companies][' + index + ']'
					);
					field.id = field.id.replace(
						/rise-company-\d+-/,
						'rise-company-' + index + '-'
					);
				} );
				row.querySelectorAll( 'label[for]' ).forEach( ( label ) => {
					label.htmlFor = label.htmlFor.replace(
						/rise-company-\d+-/,
						'rise-company-' + index + '-'
					);
				} );
			} );
			add.disabled = rows.children.length >= 10;
		}

		function addLocation( values = {}, open = true ) {
			const markup = template.innerHTML.replaceAll(
				'__INDEX__',
				String( rows.children.length )
			);
			rows.insertAdjacentHTML( 'beforeend', markup );
			const row = rows.lastElementChild;
			Object.entries( values ).forEach( ( [ key, value ] ) => {
				const field = row.querySelector( '[name$="[' + key + ']"]' );
				if ( field ) {
					field.value = value;
				}
			} );
			row.open = open;
			updateRows();
			return row;
		}

		function updateMode() {
			const multiple = contactMode.value === 'multiple';
			singleFields.forEach( ( row ) => {
				if ( row ) {
					row.hidden = multiple;
				}
			} );
			companyField.hidden = ! multiple;
		}

		applyContactPreset = ( contact ) => {
			if ( contact.mode === 'multiple' ) {
				rows.replaceChildren();
				contact.companies.forEach( ( location ) =>
					addLocation( location, false )
				);
			} else {
				[
					'address',
					'map_url',
					'phone',
					'additional_phones',
					'email',
					'company_details',
				].forEach( ( key ) => {
					const field = document.getElementById(
						'rise-setting-' + key
					);
					field.value = contact.single[ key ] || '';
				} );
			}
			contactMode.value = contact.mode;
			updateRows();
			updateMode();
		};

		contactMode.addEventListener( 'change', updateMode );
		add.addEventListener( 'click', () => {
			if ( rows.children.length >= 10 ) {
				return;
			}
			const row = addLocation();
			status.textContent = strings.companyAdded;
			row.querySelector( '[name$="[name]"]' ).focus();
		} );
		rows.addEventListener( 'input', ( event ) => {
			if ( event.target.matches( '[name$="[name]"]' ) ) {
				updateRows();
			}
		} );
		rows.addEventListener( 'click', ( event ) => {
			if ( event.target.closest( '.rise-settings-companies__handle' ) ) {
				event.preventDefault();
				return;
			}
			const remove = event.target.closest(
				'.rise-settings-companies__remove'
			);
			if ( ! remove ) {
				return;
			}
			const row = remove.closest( '.rise-settings-companies__row' );
			const following =
				row.nextElementSibling || row.previousElementSibling;
			row.remove();
			updateRows();
			status.textContent = strings.companyRemoved;
			( following ? following.querySelector( 'summary' ) : add ).focus();
		} );
		rows.addEventListener( 'keydown', ( event ) => {
			if (
				! event.target.matches( '.rise-settings-companies__handle' ) ||
				! [ 'ArrowUp', 'ArrowDown' ].includes( event.key )
			) {
				return;
			}
			const row = event.target.closest( '.rise-settings-companies__row' );
			const sibling =
				event.key === 'ArrowUp'
					? row.previousElementSibling
					: row.nextElementSibling;
			if ( ! sibling ) {
				return;
			}
			event.preventDefault();
			rows.insertBefore(
				row,
				event.key === 'ArrowUp' ? sibling : sibling.nextElementSibling
			);
			updateRows();
			status.textContent = strings.locationMoved;
			event.target.focus();
		} );

		let dragging = null;
		function clearDragState() {
			rows.querySelectorAll(
				'.is-dragging, .is-drop-before, .is-drop-after'
			).forEach( ( row ) => {
				row.classList.remove(
					'is-dragging',
					'is-drop-before',
					'is-drop-after'
				);
			} );
			dragging = null;
		}
		rows.addEventListener( 'dragstart', ( event ) => {
			const handle = event.target.closest(
				'.rise-settings-companies__handle'
			);
			if ( ! handle ) {
				return;
			}
			dragging = handle.closest( '.rise-settings-companies__row' );
			dragging.classList.add( 'is-dragging' );
			event.dataTransfer.effectAllowed = 'move';
			event.dataTransfer.setData( 'text/plain', 'rise-location' );
		} );
		rows.addEventListener( 'dragover', ( event ) => {
			const target = event.target.closest(
				'.rise-settings-companies__row'
			);
			if ( ! dragging || ! target || target === dragging ) {
				return;
			}
			event.preventDefault();
			const before =
				event.clientY <
				target.getBoundingClientRect().top + target.offsetHeight / 2;
			rows.querySelectorAll( '.is-drop-before, .is-drop-after' ).forEach(
				( row ) => {
					row.classList.remove( 'is-drop-before', 'is-drop-after' );
				}
			);
			target.classList.add( before ? 'is-drop-before' : 'is-drop-after' );
		} );
		rows.addEventListener( 'drop', ( event ) => {
			const target = event.target.closest(
				'.rise-settings-companies__row'
			);
			if ( ! dragging || ! target || target === dragging ) {
				clearDragState();
				return;
			}
			event.preventDefault();
			const before =
				event.clientY <
				target.getBoundingClientRect().top + target.offsetHeight / 2;
			rows.insertBefore(
				dragging,
				before ? target : target.nextElementSibling
			);
			const handle = dragging.querySelector(
				'.rise-settings-companies__handle'
			);
			clearDragState();
			updateRows();
			status.textContent = strings.locationMoved;
			handle.focus();
		} );
		rows.addEventListener( 'dragend', clearDragState );
		updateRows();
		updateMode();
	}
} )();
