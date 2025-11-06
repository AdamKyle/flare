import { motion } from 'framer-motion';
import React, { ReactElement, useMemo, useRef, useState } from 'react';

import Card from 'ui/cards/card';
import FormWizardNav from 'ui/form-wizard/form-wizard-nav';
import FormWizardProps from 'ui/form-wizard/types/form-wizard-props';

const FormWizard = ({
  total_steps,
  name,
  is_loading,
  render_loading_icon,
  on_request_next,
  children,
}: FormWizardProps) => {
  const [current_index, set_current_index] = useState(0);
  const step_refs = useRef<Array<HTMLDivElement | null>>([]);

  const step_elements = useMemo(
    () => React.Children.toArray(children) as ReactElement[],
    [children]
  );

  const computed_total_steps = useMemo(
    () => step_elements.length || total_steps,
    [step_elements, total_steps]
  );

  const handlePreviousClick = () => {
    if (current_index === 0) {
      return;
    }

    set_current_index((value) => value - 1);
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

    set_current_index((value) => value + 1);
  };

  const handleDotClick = (target_index: number) => {
    if (target_index >= current_index) {
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

  const renderTrack = () => {
    return (
      <div className="relative">
        {step_elements.map((element, index) => {
          const is_active = index === current_index;

          return (
            <motion.div
              key={(element.key as string) ?? `step-${index}`}
              ref={(el) => {
                step_refs.current[index] = el;
              }}
              initial={false}
              animate={{
                x: is_active ? 0 : index < current_index ? -32 : 32,
                opacity: is_active ? 1 : 0,
              }}
              transition={{ duration: 0.25 }}
              className={is_active ? 'relative' : 'absolute inset-0'}
              style={{ pointerEvents: is_active ? 'auto' : 'none' }}
              aria-hidden={!is_active}
            >
              {element}
            </motion.div>
          );
        })}
      </div>
    );
  };

  const renderFooter = () => {
    return (
      <FormWizardNav
        current_index={current_index}
        total_steps={computed_total_steps}
        can_go_previous={current_index > 0}
        is_last_step={current_index === computed_total_steps - 1}
        is_loading={!!is_loading}
        on_previous_click={handlePreviousClick}
        on_next_click={handleNextClick}
        on_dot_click={handleDotClick}
        render_loading_icon={render_loading_icon}
      />
    );
  };

  return (
    <div className="container my-4 flex items-center justify-center px-4">
      <div className="w-full max-w-5xl">
        <Card>
          <div className="space-y-4">
            {renderHeader()}
            {renderTrack()}
            {renderFooter()}
          </div>
        </Card>
      </div>
    </div>
  );
};

export default FormWizard;
