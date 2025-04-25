import React from 'react';

import CharacterKingdomDetailsProps from './types/character-kingdom-details-props';

const CharacterKingdomDetails = ({
  kingdom_id,
}: CharacterKingdomDetailsProps) => {
  const kingdomId = () => {
    return kingdom_id;
  };

  return (
    <div className={'h-[300px] p-2'}>
      <p>
        Lorem ipsum dolor sit amet, consectetur adipiscing elit. Integer nec
        odio. Praesent libero. Sed cursus ante dapibus diam. Sed nisi. Nulla
        quis sem at nibh elementum imperdiet. Duis sagittis ipsum. Praesent
        mauris. Fusce nec tellus sed augue semper porta. Mauris massa.
        Vestibulum lacinia arcu eget nulla. Class aptent taciti sociosqu ad
        litora torquent per conubia nostra, per inceptos himenaeos. Curabitur
        sodales ligula in libero. Sed dignissim lacinia nunc.{' '}
      </p>
      {kingdomId()}
    </div>
  );
};

export default CharacterKingdomDetails;
