import React from 'react';

import SectionProps from './types/section-props';

import Dl from 'ui/dl/dl';
import Separator from 'ui/separator/separator';

const Section = ({
  title,
  children,
  className,
  showSeparator = true,
  showTitleSeparator = false,
  lead,
}: SectionProps) => {
  const renderSeparator = () => {
    if (!showSeparator) {
      return null;
    }

    return <Separator />;
  };

  const renderLead = () => {
    if (!lead) {
      return null;
    }

    return <div className="mb-2 space-y-2">{lead}</div>;
  };

  const renderTitleSeparator = () => {
    if (!showTitleSeparator) {
      return null;
    }

    return <Separator />;
  };

  return (
    <>
      <div className={className}>
        <h4 className="text-mango-tango-500 dark:text-mango-tango-300 mt-3 mb-2 text-xs font-semibold tracking-wide uppercase">
          {title}
        </h4>
        {renderLead()}
        {renderTitleSeparator()}
        <Dl>{children}</Dl>
      </div>
      {renderSeparator()}
    </>
  );
};

export default Section;
