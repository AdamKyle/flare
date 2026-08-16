import clsx from 'clsx';
import React, { useMemo } from 'react';

import Button from 'ui/buttons/button';
import { ButtonVariant } from 'ui/buttons/enums/button-variant-enum';
import IconButton from 'ui/buttons/icon-button';
import FormWizardNavProps from 'ui/form-wizard/types/form-wizard-nav-props';

interface DotViewModel {
  index: number;
  is_active: boolean;
  aria_label: string;
}

const buildDotView = (
  index: number,
  total_steps: number,
  current_index: number,
  step_title: string
): DotViewModel => {
  let aria_label = `Step ${index + 1} of ${total_steps}`;

  if (step_title) {
    aria_label = `Step ${index + 1} of ${total_steps}: ${step_title}`;
  }

  return {
    index,
    is_active: index === current_index,
    aria_label,
  };
};

const FormWizardNav = ({
  current_index,
  total_steps,
  step_titles,
  can_go_previous,
  is_last_step,
  is_loading,
  on_previous_click,
  on_next_click,
  on_dot_click,
  render_loading_icon,
  finish_label,
  icon_navigation,
}: FormWizardNavProps) => {
  const dot_views = useMemo<DotViewModel[]>(
    () =>
      Array.from({ length: total_steps }, (_, index) =>
        buildDotView(
          index,
          total_steps,
          current_index,
          step_titles[index] ?? ''
        )
      ),
    [total_steps, current_index, step_titles]
  );

  const renderPrevious = () => {
    if (icon_navigation) {
      return (
        <IconButton
          on_click={on_previous_click}
          variant={ButtonVariant.PRIMARY}
          disabled={!can_go_previous}
          icon={<i className="fas fa-arrow-left" aria-hidden="true" />}
          aria_label="Previous step"
        />
      );
    }

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
    const action_variant = is_last_step
      ? ButtonVariant.PRIMARY
      : ButtonVariant.SUCCESS;

    if (icon_navigation && !is_last_step) {
      return (
        <IconButton
          disabled={!!is_loading}
          on_click={on_next_click}
          variant={action_variant}
          icon={
            icon_node ?? <i className="fas fa-arrow-right" aria-hidden="true" />
          }
          aria_label="Next step"
        />
      );
    }

    if (icon_navigation && is_last_step) {
      return (
        <Button
          label={finish_label ?? 'Finish'}
          variant={ButtonVariant.PRIMARY}
          disabled={!!is_loading}
          on_click={on_next_click}
        />
      );
    }

    const action_label = is_last_step ? (finish_label ?? 'Finish') : 'Next';

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

  const renderDot = (dot_view: DotViewModel) => (
    <button
      key={`dot-${dot_view.index}`}
      type="button"
      onClick={() => on_dot_click(dot_view.index)}
      disabled={!!is_loading}
      aria-current={dot_view.is_active ? 'step' : undefined}
      aria-label={dot_view.aria_label}
      className={clsx(
        'focus-visible:ring-danube-400 h-3 w-3 rounded-full transition-colors duration-300 focus:outline-none focus-visible:ring-2 disabled:cursor-not-allowed',
        dot_view.is_active ? 'bg-danube-600' : 'bg-gray-300 dark:bg-gray-600'
      )}
    />
  );

  const renderDots = () => {
    if (!dot_views.length) {
      return null;
    }

    return (
      <nav
        aria-label="Wizard steps"
        className="flex items-center justify-center gap-2"
      >
        {dot_views.map(renderDot)}
      </nav>
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
