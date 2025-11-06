import React, { ReactNode } from 'react';

import StepProps from 'ui/form-wizard/types/step-props';

const Step = ({ step_title, children }: StepProps) => {
  const renderHeader = () => {
    if (!step_title) {
      return null;
    }

    return <h3 className="mb-6 text-lg font-semibold">{step_title}</h3>;
  };

  const renderBody = () => {
    if (!children) {
      return null;
    }

    return <div>{children as ReactNode}</div>;
  };

  return (
    <div className="flex w-full flex-none self-start p-6">
      <div className="w-full">
        {renderHeader()}
        {renderBody()}
      </div>
    </div>
  );
};

export default Step;
