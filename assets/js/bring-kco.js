jQuery(
	function( $ ) {

		function post_kco_delivery_post_code( post_code, country ) {
			$.post(
				_fraktguiden_kco.ajaxurl,
				{
					action: 'bring_post_code_validation',
					post_code: post_code,
					country: country,
					nonce:   _fraktguiden_kco.klarna_checkout_nonce
				  },
				function( response ) {
					if ( ! response.valid ) {
						$( '.bring-enter-postcode input' ).prop( 'disabled', false );
						$( '.bring-enter-postcode .input-text' ).addClass( 'bring-error-input' );
						$( '.bring-enter-postcode' ).addClass( 'bring-error' ).removeClass( 'loading' );
						$( '<p>' ).addClass( 'bring-error-message' ).html( response.result ).appendTo( $( '.bring-search-box' ) );
						return false;
					}
					location.href = location.href;
				}
			);
		}

		function submit_handler( e ) {
			e.preventDefault();
			e.stopPropagation();

			$( this ).addClass( 'loading' );
			$( this ).find( '.bring-enter-postcode .input-text' ).prop( 'disabled', true ).removeClass( 'bring-error-input' );
			$( '.bring-error-message' ).remove();
			post_kco_delivery_post_code(
				$( '#bring-post-code' ).val(),
				$( '#bring-country' ).val()
			);
		}

		function toggle_checkout() {
			var elem = $( '.bring-enter-postcode' );
			if ( elem.length && elem.hasClass( 'bring-required' ) ) {
				$( '.bring-enter-postcode' ).show();
				$( '#klarna-checkout-container' ).hide();
			} else {
				$( '.bring-enter-postcode' ).hide();
				$( '#klarna-checkout-container' ).show();
			}
		}

			$( document ).ajaxSuccess(
				function ( event, xhr, settings ) {
					var data = settings.data;
					if ( ! settings.url.match( /wc-ajax=kco_wc_/ ) ) {
						return;
					}
					toggle_checkout();
				}
			);

			$( document.body ).on(
				'updated_checkout',
				function () {
					if ( ! $( '.bring-enter-postcode .input-text' ).length ) {
						  return;
					}
					$( '.bring-enter-postcode .input-text' ).on(
						'keydown',
						function( event ) {
							$( '.bring-enter-postcode' ).removeClass( 'bring-error' );
							$( this ).removeClass( 'bring-error-input' );
							$( '.bring-error-message' ).remove();
							// Klarna block
							if (event.keyCode == 13) {
								$( '.bring-enter-postcode form' ).submit();
							}
						}
					);
					$( '.bring-button' ).on( 'click submit', submit_handler );
					$( '.bring-enter-postcode .input-text' ).submit( submit_handler );
					toggle_checkout();
				}
			);

	}
);
