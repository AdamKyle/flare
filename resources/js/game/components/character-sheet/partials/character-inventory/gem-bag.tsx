import React, { ReactNode } from 'react';

import GemBagInfiniteScroll from './gem-bag-inifinite-scroll';
import GemBagProps from './types/gem-bag-props';
import { CharacterInventoryApiUrls } from '../../../side-peeks/character-inventory/api/enums/character-inventory-api-urls';
import { useGetCharacterGemBag } from '../../../side-peeks/character-inventory/api/hooks/use-get-character-gem-bag';

import { GameDataError } from 'game-data/components/game-data-error';

import { Alert } from 'ui/alerts/alert';
import { AlertVariant } from 'ui/alerts/enums/alert-variant';
import BackButton from 'ui/buttons/back-button';
import InfiniteLoader from 'ui/loading-bar/infinite-loader';
import Separator from 'ui/seperatror/separator';

const GemBag = ({ close_gem_bag, character_id }: GemBagProps): ReactNode => {
  const { data, error, loading } = useGetCharacterGemBag({
    url: CharacterInventoryApiUrls.CHARACTER_GEM_BAG,
    urlParams: {
      character: character_id,
    },
  });

  if (loading) {
    return <InfiniteLoader />;
  }

  if (error) {
    return <Alert variant={AlertVariant.DANGER}>{error.message}</Alert>;
  }

  if (data === null) {
    return <GameDataError />;
  }

  return (
    <>
      <BackButton title={'Back to Inventory'} handle_back={close_gem_bag} />
      <Separator />
      <div>
        <h4 className="text-primary-500 dark:text-primary-200">Gems</h4>
        <GemBagInfiniteScroll gems={data} />
      </div>
    </>
  );
};

export default GemBag;
