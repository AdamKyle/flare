import React from 'react';

import SectionProps from './types/section-props';

import Dl from 'ui/dl/dl';
import Separator from 'ui/separator/separator';

const Section = ({ title, children, className }: SectionProps) => (
  <>
    <div className={className}>
      <h4 className="mt-3 mb-1 text-xs font-semibold uppercase tracking-wide text-mango-tango-500 dark:text-mango-tango-300">
        {title}
      </h4>
      <Dl>{children}</Dl>
    </div>
    <Separator />
  </>
);

export default Section;
