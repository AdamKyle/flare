import React, { ReactNode, useEffect } from 'react';

import { useGameLoader } from './hooks/use-game-loader';
import { useManageGameLoaderVisibility } from '../hooks/use-manage-game-loader-visibility';

import { useGameData } from 'game-data/hooks/use-game-data';

import { Alert } from 'ui/alerts/alert';
import { AlertVariant } from 'ui/alerts/enums/alert-variant';
import ContainerWrapper from 'ui/container/container-wrapper';
import { ProgressBarHeightVariant } from 'ui/loading-bar/enums/progress-bar-height-variant';
import ProgressBar from 'ui/loading-bar/progress-bar';

const GameLoader = (): ReactNode => {
  const { loading, progress, error, data } = useGameLoader();
  const { hideGameLoader } = useManageGameLoaderVisibility();

  const { setGameData } = useGameData();

  useEffect(
    () => {
      if (!loading) {
        setGameData(data);

        if (!error) {
          hideGameLoader();
        }
      }
    },
    // eslint-disable-next-line react-hooks/exhaustive-deps
    [loading, data, error]
  );

  if (loading) {
    return (
      <div className="fixed top-0 left-0 right-0 bottom-0 flex items-center justify-center bg-transparent z-50">
        <ContainerWrapper>
          <ProgressBar
            progress={progress}
            label={'Loading Game ...'}
            variant={ProgressBarHeightVariant.MEDIUM}
          />
        </ContainerWrapper>
      </div>
    );
  }

  if (error) {
    return (
      <div className="fixed top-0 left-0 right-0 bottom-0 flex items-center justify-center bg-transparent z-50">
        <ContainerWrapper>
          <Alert variant={AlertVariant.DANGER}>
            <p>{error.message}</p>
          </Alert>
        </ContainerWrapper>
      </div>
    );
  }

  return null;
};

export default GameLoader;
