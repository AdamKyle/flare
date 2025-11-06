import clsx from 'clsx';
import React, { useMemo } from 'react';

import Button from 'ui/buttons/button';
import { ButtonVariant } from 'ui/buttons/enums/button-variant-enum';
import IconButton from 'ui/buttons/icon-button';
import FormWizardNavProps from 'ui/form-wizard/types/form-wizard-nav-props';

const FormWizardNav = ({
  current_index,
  total_steps,
  can_go_previous,
  is_last_step,
  is_loading,
  on_previous_click,
  on_next_click,
  on_dot_click,
  render_loading_icon,
}: FormWizardNavProps) => {
  const dots = useMemo(
    () => Array.from({ length: total_steps }, (_, i) => i),
    [total_steps]
  );

  const renderPrevious = () => {
    return (
      <Button
        on_click={on_previous_click}
        label="Previous"
        variant={ButtonVariant.PRIMARY}
        disabled={!can_go_previous}
      />
    );
  };

  const renderNext = () => {
    const icon_node =
      is_loading && render_loading_icon ? render_loading_icon() : undefined;
    const action_label = is_last_step ? 'Finish' : 'Next';
    const action_variant = is_last_step
      ? ButtonVariant.PRIMARY
      : ButtonVariant.SUCCESS;

    return (
      <IconButton
        disabled={!!is_loading}
        on_click={on_next_click}
        label={action_label}
        variant={action_variant}
        icon={icon_node}
      />
    );
  };

  const handleDotBarClick = (e: React.MouseEvent<HTMLDivElement>) => {
    const target = e.target as HTMLElement;

    const button = target.closest(
      'button[data-index]'
    ) as HTMLButtonElement | null;

    if (!button) {
      return;
    }

    const index_value = Number(button.dataset.index);
    const is_backwards = index_value < current_index;

    on_dot_click(is_backwards ? index_value : current_index);
  };

  const renderDots = () => {
    if (!dots.length) {
      return null;
    }

    return (
      <div
        className="flex items-center justify-center gap-2"
        onClick={handleDotBarClick}
        role="tablist"
        aria-label="Wizard steps"
      >
        {dots.map((index_value) => {
          const is_active = index_value === current_index;

          return (
            <button
              key={`dot-${index_value}`}
              type="button"
              data-index={index_value}
              aria-current={is_active ? 'true' : undefined}
              aria-disabled={!is_active && index_value >= current_index}
              className={clsx(
                'h-3 w-3 rounded-full transition-colors duration-300 focus:outline-none',
                is_active ? 'bg-danube-600' : 'bg-gray-300 dark:bg-gray-600'
              )}
            />
          );
        })}
      </div>
    );
  };

  return (
    <div className="flex items-center justify-between border-t border-gray-200 px-6 py-4 dark:border-gray-700">
      <div className="shrink-0">{renderPrevious()}</div>
      <div className="flex-1">{renderDots()}</div>
      <div className="shrink-0">{renderNext()}</div>
    </div>
  );
};

export default FormWizardNav;
