import { useEffect } from 'react';

import { useSidePeekOptionsContext } from 'ui/side-peek/options/hooks/use-side-peek-options-context';
import SidePeekOptionDefinition from 'ui/side-peek/options/types/side-peek-option-definition';

/**
 * Register the supplied options as the active `SidePeek` footer options for
 * as long as the calling component is mounted with those options, clearing
 * them on cleanup. Safe to call outside a `SidePeek` context.
 *
 * @param  options
 */
export const useSidePeekOptions = (
  options: SidePeekOptionDefinition[]
): void => {
  const context = useSidePeekOptionsContext();

  useEffect(() => {
    if (!context) {
      return;
    }

    context.set_options(options);

    return () => {
      context.clear_options();
    };
  }, [context, options]);
};
