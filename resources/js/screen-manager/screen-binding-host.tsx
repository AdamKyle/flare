import React from 'react';

type BindingComponent = () => null;

type ScreenBindingHostProps = {
  bindings: readonly BindingComponent[];
};

const ScreenBindingHost = (props: ScreenBindingHostProps) => {
  const renderBindings = () => {
    if (props.bindings.length === 0) {
      return null;
    }

    return props.bindings.map((Bind, index) => <Bind key={index} />);
  };

  return <>{renderBindings()}</>;
};

export default ScreenBindingHost;
