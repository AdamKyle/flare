import UseOpenRaceDetailDefinition from './definitions/use-open-race-detail-definition';
import { SidePeekComponentRegistrationEnum } from '../../base/component-registration/side-peek-component-registration-enum';
import { SidePeek } from '../../base/event-types/side-peek';
import { useSidePeekEmitter } from '../../base/hooks/use-side-peek-emitter';

export const useOpenRaceDetail = (): UseOpenRaceDetailDefinition => {
  const sidePeekEmitter = useSidePeekEmitter();

  const openRaceDetail = (raceId: number, raceName: string): void => {
    sidePeekEmitter.emit(
      SidePeek.SIDE_PEEK,
      SidePeekComponentRegistrationEnum.RACE_DETAIL,
      {
        is_open: true,
        title: raceName,
        allow_clicking_outside: true,
        race_id: raceId,
      }
    );
  };

  return { openRaceDetail };
};
