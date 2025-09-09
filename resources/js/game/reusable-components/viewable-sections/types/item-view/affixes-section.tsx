import React from 'react';

import AffixesSectionProps from '../../../../components/side-peeks/character-inventory/inventory-item/types/partials/item-view/affixes-section-props';
import DefinitionRow from '../../definition-row';
import InfoLabel from '../../info-label';
import Section from '../../section';

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
