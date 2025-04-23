import React from 'react';

import { useManageMapSectionVisibility } from './hooks/use-manage-map-section-visibility';
import { useToggleFullMapVisibility } from '../../../../map-section/hooks/use-toggle-full-map-visibility';
import FloatingCard from '../../../components/icon-section/floating-card';

import Button from 'ui/buttons/button';
import { ButtonVariant } from 'ui/buttons/enums/button-variant-enum';

const MapCard = () => {
  const { closeMapCard } = useManageMapSectionVisibility();
  const { openFullMap } = useToggleFullMapVisibility();

  return (
    <FloatingCard title={'Map: Surface'} close_action={closeMapCard}>
      <div className="text-center">
        <img src={'https://placecats.com/neo/500/500'} />
      </div>
      <div className="my-2 p-2 flex flex-col gap-2 md:flex-row justify-center">
        <Button
          on_click={() => {}}
          label={'North'}
          variant={ButtonVariant.PRIMARY}
        />
        <Button
          on_click={() => {}}
          label={'South'}
          variant={ButtonVariant.PRIMARY}
        />
        <Button
          on_click={() => {}}
          label={'East'}
          variant={ButtonVariant.PRIMARY}
        />
        <Button
          on_click={() => {}}
          label={'West'}
          variant={ButtonVariant.PRIMARY}
        />
      </div>

      <div className="my-2 p-2 flex flex-col gap-2 md:flex-row justify-center">
        <Button
          on_click={() => {}}
          label={'Teleport'}
          variant={ButtonVariant.PRIMARY}
        />
        <Button
          on_click={() => {}}
          label={'Set Sail'}
          variant={ButtonVariant.PRIMARY}
        />
        <Button
          on_click={() => {}}
          label={'Traverse'}
          variant={ButtonVariant.PRIMARY}
        />
        <Button
          on_click={() => {}}
          label={'Conjure'}
          variant={ButtonVariant.PRIMARY}
        />
      </div>
      <div className="my-2 p-2 w-full">
        <Button
          on_click={openFullMap}
          label={'View Full Map'}
          variant={ButtonVariant.SUCCESS}
          additional_css={'w-full'}
        />
      </div>
    </FloatingCard>
  );
};

export default MapCard;
