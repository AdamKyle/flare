import React from 'react';

import DefinitionRow from '../../../../../reusable-components/viewable-sections/definition-row';
import InfoLabel from '../../../../../reusable-components/viewable-sections/info-label';
import Section from '../../../../../reusable-components/viewable-sections/section';
import GeneralSectionProps from '../types/partials/general-section-props';

import { Alert } from 'ui/alerts/alert';
import { AlertVariant } from 'ui/alerts/enums/alert-variant';

const GeneralSection = ({
  item,
  showSeparator,
  showTitleSeparator,
}: GeneralSectionProps) => {
  const renderLead = () => {
    return (
      <Alert variant={AlertVariant.INFO}>
        <strong>Can Stack</strong> refers to the items ability to stack its
        effects with other items that have the same effects as this item
      </Alert>
    );
  };

  return (
    <Section
      title="General"
      showSeparator={showSeparator}
      showTitleSeparator={showTitleSeparator}
      lead={renderLead()}
    >
      <DefinitionRow
        left={<InfoLabel label="Can Stack" />}
        right={
          <span className="text-gray-800 dark:text-gray-200">
            {item.can_stack ? 'Yes' : 'No'}
          </span>
        }
      />
      <DefinitionRow
        left={<InfoLabel label="Lasts For (Minutes)" />}
        right={
          <span className="text-gray-800 dark:text-gray-200">
            {item.lasts_for}
          </span>
        }
      />
    </Section>
  );
};

export default GeneralSection;
