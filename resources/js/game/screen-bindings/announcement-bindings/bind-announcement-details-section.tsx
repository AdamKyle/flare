import { Screens } from 'configuration/screen-manager/screen-manager-constants';
import {
  useBindScreen,
  useScreenNavigation,
} from 'configuration/screen-manager/screen-manager-kit';
import { ScreenPropsOf } from 'configuration/screen-manager/screen-manager-props';
import { useRef } from 'react';

import { UseManageAnnouncementDetailsVisibility } from '../../components/announcements/hooks/use-manage-announcement-details-visibility';

const BindAnnouncementDetailsSection = () => {
  const { pop } = useScreenNavigation();
  const { announcementId, closeAnnouncementDetails } =
    UseManageAnnouncementDetailsVisibility();

  const activeRef = useRef(false);

  useBindScreen({
    when: announcementId !== null,
    to: Screens.ANNOUNCEMENT_DETAILS,
    props: (): ScreenPropsOf<typeof Screens.ANNOUNCEMENT_DETAILS> => ({
      on_close: () => {
        if (activeRef.current) {
          pop();
        }
        closeAnnouncementDetails();
        activeRef.current = false;
      },
      announcement_id: announcementId,
    }),
    mode: 'push',
    dedupeKey: 'shop',
  });

  return null;
};

export default BindAnnouncementDetailsSection;
