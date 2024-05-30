import { useEffect, useState } from 'react';
import { decodeEntities } from '@wordpress/html-entities';

const { registerPaymentMethod } = window.wc.wcBlocksRegistry;
const { getSetting } = window.wc.wcSettings;

const settings = getSetting('paycove_data', {});

const label = decodeEntities(settings.title);

const fetchCartTotal = async () => {
  try {
    const response = await fetch('/wp-json/wc/store/v1/cart');
    if (!response.ok) {
      throw new Error('Network response was not ok');
    }
    const data = await response.json();
    return data.totals.total_price;
  } catch (error) {
    console.error('Error fetching cart total:', error);
    return null;
  }
};

const Content = () => {
  const [cartTotal, setCartTotal] = useState(null);

  console.log(settings)

  useEffect(() => {
    const getTotal = async () => {
      const total = await fetchCartTotal();
      setCartTotal(total);
    };
    getTotal();
  }, []);

  const totalQueryParam = cartTotal
    ? `&total=${parseFloat(cartTotal / 100).toFixed(2)}`
    : '';

  return (
    <div>
      <a
        className='wp-element-button'
        href={`https://staging.paycove.io/checkout-builder-form?type=invoice&invoice_template_id=816&account_id=d3c9b3c2b6cd9aa0fef90df78b82869b&adjustable_amount=true${totalQueryParam}`}>
        Pay Now
      </a>
    </div>
  );
};

const Label = (props) => {
  const { PaymentMethodLabel } = props.components;
  return <PaymentMethodLabel text={label} />;
};

registerPaymentMethod({
  name: 'paycove',
  label: <Label />,
  content: <Content />,
  edit: <Content />,
  canMakePayment: () => true,
  ariaLabel: label,
  supports: {
    features: settings.supports,
  },
});
