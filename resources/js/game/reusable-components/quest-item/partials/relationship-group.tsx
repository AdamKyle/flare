import React, { ReactNode } from 'react';

import RelationshipGroupProps from '../types/partials/relationship-group-props';

import Separator from 'ui/separator/separator';

/**
 * Group a set of compact Quest Item relationship rows under a titled
 * section, matching the visual title treatment of the shared `Section`
 * component. Uses a plain row list rather than `Section`'s `<Dl>` wrapper,
 * since these rows are compact clickable cards, not `Dt`/`Dd` definition
 * pairs.
 */
const RelationshipGroup = ({
  title,
  children,
  show_separator: showSeparator = true,
  lead,
}: RelationshipGroupProps): ReactNode => (
  <>
    <div>
      <h4 className="text-mango-tango-500 dark:text-mango-tango-300 mt-3 mb-2 text-xs font-semibold tracking-wide uppercase">
        {title}
      </h4>
      {lead && <div className="mb-2 space-y-2">{lead}</div>}
      <div className="space-y-2">{children}</div>
    </div>
    {showSeparator && <Separator />}
  </>
);

export default RelationshipGroup;
