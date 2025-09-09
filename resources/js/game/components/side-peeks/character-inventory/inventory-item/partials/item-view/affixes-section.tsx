import React from 'react';

import DefinitionRow from '../../../../../../reusable-components/viewable-sections/definition-row';
import InfoLabel from '../../../../../../reusable-components/viewable-sections/info-label';
import Section from '../../../../../../reusable-components/viewable-sections/section';
import AffixesSectionProps from '../../types/partials/item-view/affixes-section-props';

import { ButtonVariant } from 'ui/buttons/enums/button-variant-enum';
import LinkButton from 'ui/buttons/link-button';

const AffixesSection = ({
  prefix,
  suffix,
  onOpenAffix,
}: AffixesSectionProps) => {
  const renderPrefixRow = () => {
    if (!prefix) {
      return null;
    }

    return (
      <DefinitionRow
        left={<InfoLabel label="Prefix Name" tooltip="Prefix Name" />}
        right={
          <LinkButton
            label={prefix.name}
            variant={ButtonVariant.SUCCESS}
            on_click={() => onOpenAffix(prefix.id)}
          />
        }
      />
    );
  };

  const renderSuffixRow = () => {
    if (!suffix) {
      return null;
    }

    return (
      <DefinitionRow
        left={<InfoLabel label="Suffix Name" tooltip="Suffix Name" />}
        right={
          <LinkButton
            label={suffix.name}
            variant={ButtonVariant.SUCCESS}
            on_click={() => onOpenAffix(suffix.id)}
          />
        }
      />
    );
  };

  const nothingToShow = !prefix && !suffix;

  if (nothingToShow) {
    return null;
  }

  return (
    <Section title="Affixes">
      {renderPrefixRow()}
      {renderSuffixRow()}
    </Section>
  );
};

export default AffixesSection;
