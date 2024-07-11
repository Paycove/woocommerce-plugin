import { useEffect, useState } from 'react';
import { decodeEntities } from '@wordpress/html-entities';

const { registerPaymentMethod } = window.wc.wcBlocksRegistry;
const { getSetting } = window.wc.wcSettings;

const settings = getSetting('paycove_data', {});

const label = decodeEntities(settings.title);

const fetchCartData = async () => {
  try {
    const response = await fetch('/wp-json/wc/store/v1/cart');
    if (!response.ok) {
      throw new Error('Network response was not ok');
    }
    const data = await response.json();
    // console.log(data)
    return data;
    // return data.totals.total_price;
  } catch (error) {
    console.error('Error fetching cart total:', error);
    return null;
  }
};

const Content = () => {
  const [cart, setCart] = useState(null);
  const [cartTotal, setCartTotal] = useState(null);

  useEffect(() => {
    const getTotal = async () => {
      const data = await fetchCartData();
      setCartTotal(data.totals.total_price);
      setCart(data);
    };
    getTotal();
  }, []);

  const totalQueryParam = cartTotal
    ? `&total=${parseFloat(cartTotal / 100).toFixed(2)}`
    : '';

  const handleCreateOrder = async (e) => {
    e.preventDefault();
    console.log(cart)
    const response = await fetch('/wp-json/paycove/v1/create-pending-order-from-cart', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
      },
      body: JSON.stringify(cart),
    });
    const data = await response.json();
    console.log(data);
  };

  return (
    <div>
      {/* <img src={settings.icon} alt={settings.description} style={{width: '50px'}} /> */}
      <a
        className='wp-element-button'
        href={`https://staging.paycove.io/checkout-builder-form?type=invoice&invoice_template_id=816&account_id=d3c9b3c2b6cd9aa0fef90df78b82869b&adjustable_amount=true${totalQueryParam}`}
        onClick={handleCreateOrder}>
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
