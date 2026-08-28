import ApiErrorAlert from 'api-handler/components/api-error-alert';
import { motion, useReducedMotion } from 'framer-motion';
import React, {
  ReactElement,
  ReactNode,
  useEffect,
  useMemo,
  useRef,
  useState,
} from 'react';

import Card from 'ui/cards/card';
import FormWizardNav from 'ui/form-wizard/form-wizard-nav';
import FormWizardProps from 'ui/form-wizard/types/form-wizard-props';
import StepProps from 'ui/form-wizard/types/step-props';

interface StepViewModel {
  key: React.Key;
  element: ReactElement<StepProps>;
  index: number;
  is_active: boolean;
  motion_x: number;
  motion_opacity: number;
}

const getStepElements = (children: ReactNode): ReactElement<StepProps>[] =>
  React.Children.toArray(children).filter(
    (child): child is ReactElement<StepProps> =>
      React.isValidElement<StepProps>(child)
  );

const buildStepView = (
  element: ReactElement<StepProps>,
  index: number,
  current_index: number,
  reduce_motion: boolean
): StepViewModel => {
  const is_active = index === current_index;

  let motion_x = 32;

  if (index < current_index) {
    motion_x = -32;
  }

  if (reduce_motion || is_active) {
    motion_x = 0;
  }

  return {
    key: element.key ?? `step-${index}`,
    element,
    index,
    is_active,
    motion_x,
    motion_opacity: is_active ? 1 : 0,
  };
};

const FormWizard = ({
  total_steps,
  name,
  is_loading,
  render_loading_icon,
  on_request_next,
  finish_label,
  children,
  form_error,
  embedded,
  icon_navigation,
  current_step_index,
  on_step_change,
}: FormWizardProps) => {
  const [internal_current_index, set_internal_current_index] = useState(0);
  const is_controlled = current_step_index !== undefined;
  const current_index = is_controlled
    ? current_step_index
    : internal_current_index;
  const step_refs = useRef<Array<HTMLDivElement | null>>([]);
  const reduce_motion = useReducedMotion();

  const set_current_index = (next_index: number) => {
    if (is_controlled) {
      on_step_change?.(next_index);

      return;
    }

    set_internal_current_index(next_index);
  };

  useEffect(() => {
    step_refs.current[current_index]?.focus({ preventScroll: true });
  }, [current_index]);

  const step_elements = useMemo(() => getStepElements(children), [children]);

  const computed_total_steps = useMemo(
    () => step_elements.length || total_steps,
    [step_elements, total_steps]
  );

  const step_titles = useMemo(
    () => step_elements.map((element) => element.props.step_title),
    [step_elements]
  );

  const step_views = useMemo<StepViewModel[]>(
    () =>
      step_elements.map((element, index) =>
        buildStepView(element, index, current_index, !!reduce_motion)
      ),
    [step_elements, current_index, reduce_motion]
  );

  const handlePreviousClick = () => {
    if (current_index === 0) {
      return;
    }

    set_current_index(current_index - 1);
  };

  const handleNextClick = async () => {
    if (is_loading) {
      return;
    }

    if (on_request_next) {
      const allowed = await on_request_next(current_index);

      if (!allowed) {
        return;
      }
    }

    if (current_index >= computed_total_steps - 1) {
      return;
    }

    set_current_index(current_index + 1);
  };

  const handleDotClick = (target_index: number) => {
    if (is_loading) {
      return;
    }

    if (target_index < 0 || target_index >= computed_total_steps) {
      return;
    }

    if (target_index === current_index) {
      return;
    }

    set_current_index(target_index);
  };

  const renderHeader = () => {
    if (!name) {
      return null;
    }

    return (
      <div className="flex items-center justify-between border-b border-gray-200 pb-4 dark:border-gray-700">
        <h2 className="text-xl font-semibold text-gray-800 dark:text-gray-100">
          {name}
        </h2>
      </div>
    );
  };

  const renderStep = (step_view: StepViewModel) => (
    <motion.div
      key={step_view.key}
      ref={(el) => {
        step_refs.current[step_view.index] = el;
      }}
      tabIndex={-1}
      initial={false}
      animate={{
        x: step_view.motion_x,
        opacity: step_view.motion_opacity,
      }}
      transition={{ duration: reduce_motion ? 0 : 0.25 }}
      className={
        step_view.is_active ? 'relative focus:outline-none' : 'absolute inset-0'
      }
      style={{ pointerEvents: step_view.is_active ? 'auto' : 'none' }}
      aria-hidden={!step_view.is_active}
      inert={!step_view.is_active}
    >
      {step_view.element}
    </motion.div>
  );

  const renderTrack = () => {
    return <div className="relative">{step_views.map(renderStep)}</div>;
  };

  const renderFormError = () => {
    if (!form_error) {
      return null;
    }

    return <ApiErrorAlert apiError={form_error.message} />;
  };

  const renderFooter = () => {
    return (
      <>
        {renderFormError()}
        <FormWizardNav
          current_index={current_index}
          total_steps={computed_total_steps}
          step_titles={step_titles}
          can_go_previous={current_index > 0}
          is_last_step={current_index === computed_total_steps - 1}
          is_loading={!!is_loading}
          on_previous_click={handlePreviousClick}
          on_next_click={handleNextClick}
          on_dot_click={handleDotClick}
          render_loading_icon={render_loading_icon}
          finish_label={finish_label}
          icon_navigation={icon_navigation}
        />
      </>
    );
  };

  const wizard_content = (
    <div className="space-y-4">
      {renderHeader()}
      {renderTrack()}
      {renderFooter()}
    </div>
  );

  if (embedded) {
    return wizard_content;
  }

  return (
    <div className="container my-4 flex items-center justify-center px-4">
      <div className="w-full max-w-5xl">
        <Card>{wizard_content}</Card>
      </div>
    </div>
  );
};

export default FormWizard;
