import { decodeEntities } from '@wordpress/html-entities';
import StringMethods from '../../assets/paycove-methods.svg';
import PaycoveLogo from '../../assets/paycove-logo-wide-small.svg';
import CardPaymentIcon from '../../assets/card-payment-icon.svg';

const { registerPaymentMethod } = window.wc.wcBlocksRegistry;
const { getSetting } = window.wc.wcSettings;
const settings = getSetting( 'paycove_data', {} );
const label = decodeEntities( settings.title );

const Content = () => {
	return (
		<div className="wc-block-checkout-button-block-paycove">
			<div style={ { display: 'flex', alignItems: 'center' } }>
				<img
					style={ { height: '60px', marginRight: '10px' } }
					src={ CardPaymentIcon }
					alt="card payment icon"
				/>
				<p>{ settings.description }</p>
			</div>
			<br />
			<small>Secured by </small>
			<img src={ PaycoveLogo } alt="paycove logo" />
			{ settings.testMode && (
				<div
					style={ {
						borderLeft: '3px solid #3173DC',
						borderRadius: '3px',
						paddingLeft: '10px',
						marginTop: '20px',
					} }
				>
					In test mode, you can use the card number 4242424242424242
					with any CVC and a valid expiration date or check the{ ' ' }
					<a href="https://stripe.com/docs/testing" target="_blank">
						Testing Stripe documentation
					</a>{ ' ' }
					for more card numbers.
				</div>
			) }
		</div>
	);
};

const Label = ( props ) => {
	const { PaymentMethodLabel } = props.components;
	// return <PaymentMethodLabel text={label} />;
	return (
		<div
			style={ {
				display: 'flex',
				alignItems: 'center',
				justifyContent: 'space-between',
				width: '95%',
			} }
		>
			<PaymentMethodLabel text={ label } />
			<div style={ { marginLeft: '10px' } }>
				<img src={ StringMethods } alt="payment methods" />
			</div>
		</div>
	);
};

registerPaymentMethod( {
	name: 'paycove',
	label: <Label />,
	content: <Content />,
	edit: <Content />,
	canMakePayment: () => true,
	ariaLabel: label,
	supports: {
		features: settings.supports,
	},
} );
