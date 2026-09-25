import React, { ReactNode } from 'react';

import InventoryStackBodyProps from './types/inventory-stack-body-props';

import SidePeekOptionsFooter from 'ui/side-peek/options/components/side-peek-options-footer';

const InventoryStackBody = ({
  children,
  footer_options = [],
}: InventoryStackBodyProps): ReactNode => {
  const renderFooter = (): ReactNode => {
    if (footer_options.length === 0) {
      return null;
    }

    return <SidePeekOptionsFooter options={footer_options} />;
  };

  return (
    <div className="relative flex h-full min-h-0 flex-col overflow-hidden">
      <div className="min-h-0 flex-1 overflow-y-auto py-4">{children}</div>
      {renderFooter()}
    </div>
  );
};

export default InventoryStackBody;
