import { Screens } from 'configuration/screen-manager/screen-manager-constants';
import {
  useBindScreen,
  useScreenNavigation,
} from 'configuration/screen-manager/screen-manager-kit';
import { ScreenPropsOf } from 'configuration/screen-manager/screen-manager-props';
import { useRef } from 'react';

import { UseManageAnnouncementsVisibility } from '../components/announcements/hooks/use-manage-announcements-visibility';

const BindAnnouncementsSection = () => {
  const { pop } = useScreenNavigation();
  const { closeAnnouncements, showAnnouncements } =
    UseManageAnnouncementsVisibility();

  const activeRef = useRef(false);

  useBindScreen({
    when: showAnnouncements,
    to: Screens.ANNOUNCEMENTS,
    props: (): ScreenPropsOf<typeof Screens.ANNOUNCEMENTS> => ({
      on_close: () => {
        if (activeRef.current) {
          pop();
        }
        closeAnnouncements();
        activeRef.current = false;
      },
    }),
    mode: 'push',
    dedupeKey: 'shop',
  });

  return null;
};

export default BindAnnouncementsSection;
