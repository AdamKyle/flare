import React, { ReactNode } from 'react';

import { ButtonVariant } from 'ui/buttons/enums/button-variant-enum';
import DropDownProps from "ui/buttons/types/drop-down-props";
import DropDownButtonBase from "ui/buttons/drop-down-button-base";


const DropDownButton = <T,>({
  data,
  on_select,
  disabled,
}: DropDownProps<T>) => {
  const itemClassName =
    'inline-flex w-full items-center justify-start rounded-md px-3 py-2 text-left transition-colors ' +
    'bg-gray-300 dark:bg-gray-500 hover:bg-gray-400 hover:text-gray-600 ' +
    'dark:bg-gray-400 dark:hover:bg-gray-400 hover:text-gray-800 text-gray-600 dark:text-gray-800 ' +
    'focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-gray-400 dark:focus-visible:ring-gray-600 my-1';

  const handleSelect = (value: T) => {
    on_select(value);
  };

  const renderIcon = (icon: ReactNode) => {
    return <span className="me-2 inline-flex items-center">{icon}</span>;
  };

  const renderItems = () => {
    return data.items.map((dataItem) => {
      const resolvedItemClassName = dataItem.class_name
        ? `${itemClassName} ${dataItem.class_name}`
        : itemClassName;

      return (
        <button
          key={dataItem.aria_label}
          type="button"
          onClick={() => {
            handleSelect(dataItem.value);
          }}
          className={resolvedItemClassName}
          role="menuitem"
          aria-label={dataItem.aria_label}
          disabled={disabled}
        >
          {dataItem.icon ? renderIcon(dataItem.icon) : null}
          {dataItem.label}
        </button>
      );
    });
  };

  return (
    <DropDownButtonBase
      label={data.dropdown_label}
      variant={ButtonVariant.PRIMARY}
      disabled={disabled}
    >
      {renderItems()}
    </DropDownButtonBase>
  );
};

export default DropDownButton;
