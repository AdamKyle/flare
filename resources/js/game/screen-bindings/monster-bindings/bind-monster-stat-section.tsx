import { consumeScreenIntent } from 'configuration/screen-manager/screen-intent';
import { Screens } from 'configuration/screen-manager/screen-manager-constants';
import {
  useBindScreen,
  useScreenNavigation,
} from 'configuration/screen-manager/screen-manager-kit';
import { ScreenPropsOf } from 'configuration/screen-manager/screen-manager-props';
import { useRef } from 'react';

import { useManageMonsterStatSectionVisibility } from '../../components/actions/partials/monster-stat-section/hooks/use-manage-monster-stat-section-visibility';

const BindMonsterStatSection = () => {
  const { pop } = useScreenNavigation();
  const { showMonsterStatsSection, closeMonsterStats } =
    useManageMonsterStatSectionVisibility();

  const activeRef = useRef(false);

  const intentRef = useRef<
    ScreenPropsOf<typeof Screens.MONSTER_DETAILS> | undefined
  >(undefined);

  useBindScreen({
    when: showMonsterStatsSection,
    to: Screens.MONSTER_DETAILS,
    props: (): ScreenPropsOf<typeof Screens.MONSTER_DETAILS> => {
      if (!intentRef.current) {
        intentRef.current = consumeScreenIntent(Screens.MONSTER_DETAILS);
      }

      const payload = intentRef.current;

      if (!payload) {
        closeMonsterStats();
        return {
          monster_id: 0,
          toggle_monster_stat_visibility: () => {},
        } as ScreenPropsOf<typeof Screens.MONSTER_DETAILS>;
      }

      return {
        monster_id: payload.monster_id,
        toggle_monster_stat_visibility: (id: number) => {
          if (activeRef.current) {
            pop();
          }
          payload.toggle_monster_stat_visibility(id);
          activeRef.current = false;
        },
      };
    },
    mode: 'push',
    dedupeKey: `monster-details:${intentRef.current?.monster_id ?? 'none'}`,
  });

  return null;
};

export default BindMonsterStatSection;
