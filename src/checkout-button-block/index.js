import { decodeEntities } from '@wordpress/html-entities';

const { registerPaymentMethod } = window.wc.wcBlocksRegistry;
const { getSetting } = window.wc.wcSettings;

const settings = getSetting( 'paycove_data', {} );

const label = decodeEntities( settings.title );

const Content = () => {
	// return decodeEntities(settings.description || '');
	return (
		<div>
			<a
				className="wp-element-button"
				href="https://staging.paycove.io/checkout-builder-form?type=invoice&invoice_template_id=816&total=110&account_id=d3c9b3c2b6cd9aa0fef90df78b82869b&adjustable_amount=true"
			>
				Pay Now
			</a>
		</div>
	);
};

const Label = ( props ) => {
	const { PaymentMethodLabel } = props.components;
	return <PaymentMethodLabel text={ label } />;
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
