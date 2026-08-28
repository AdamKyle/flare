import React from 'react';

import StepProps from 'ui/form-wizard/types/step-props';

const Step = ({ step_title, show_title = true, children }: StepProps) => {
  const renderHeader = () => {
    if (!show_title) {
      return null;
    }

    return (
      <h3 className="text-glacier-900 dark:text-glacier-100 mb-6 text-lg font-semibold">
        {step_title}
      </h3>
    );
  };

  const renderBody = () => {
    if (!children) {
      return null;
    }

    return <div>{children}</div>;
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
