import React from 'react';
import FloatingCard from '../../../components/icon-section/floating-card';
import { useManageMapSectionVisibility } from './hooks/use-manage-map-section-visibility';
import Button from 'ui/buttons/button';
import { ButtonVariant } from 'ui/buttons/enums/button-variant-enum';

const MapCard = () => {

  const {closeMapCard} = useManageMapSectionVisibility();

  return (
    <FloatingCard title={'Map: Surface'} close_action={closeMapCard}>
      <div className='text-center'>
        <img src={'https://placecats.com/neo/500/500'} />
      </div>
      <div className="my-2 p-2 flex flex-col gap-2 md:flex-row justify-center">
        <Button on_click={() => {}} label={'North'} variant={ButtonVariant.PRIMARY} />
        <Button on_click={() => {}} label={'South'} variant={ButtonVariant.PRIMARY} />
        <Button on_click={() => {}} label={'East'} variant={ButtonVariant.PRIMARY} />
        <Button on_click={() => {}} label={'West'} variant={ButtonVariant.PRIMARY} />
      </div>

      <div className="my-2 p-2 flex flex-col gap-2 md:flex-row justify-center">
        <Button on_click={() => {}} label={'Teleport'} variant={ButtonVariant.PRIMARY} />
        <Button on_click={() => {}} label={'Set Sail'} variant={ButtonVariant.PRIMARY} />
        <Button on_click={() => {}} label={'Traverse'} variant={ButtonVariant.PRIMARY} />
        <Button on_click={() => {}} label={'Conjure'} variant={ButtonVariant.PRIMARY} />
      </div>

    </FloatingCard>
  )
}

export default MapCard;