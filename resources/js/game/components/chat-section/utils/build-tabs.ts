import AnnouncementMessagesProps from '../components/announcements/definitions/announcement-messages-props';
import ExplorationMessageProps from '../components/exploration-messages/definitions/exploration-message-props';
import MessagesProps from '../components/messages/definitions/message-props';
import ServerMessagesProps from '../components/server-messages/definitions/server-messages-props';

import { ComponentFromProps, TabTupleFromProps } from 'ui/tabs/types/tab-item';

type Params = {
  chatComponent: ComponentFromProps<MessagesProps>;
  serverComponent: ComponentFromProps<ServerMessagesProps>;
  explorationComponent: ComponentFromProps<ExplorationMessageProps>;
  announcementsComponent: ComponentFromProps<AnnouncementMessagesProps>;
  bellIconClass: string;
  bellIconStyles: string;
  chatProps: MessagesProps;
  serverProps: ServerMessagesProps;
  explorationProps: ExplorationMessageProps;
  announcementsProps: AnnouncementMessagesProps;
  unreadServer: boolean;
  unreadAnnouncements: boolean;
};

const buildTabs = (
  params: Params
): Readonly<
  TabTupleFromProps<
    [
      MessagesProps,
      ServerMessagesProps,
      ExplorationMessageProps,
      AnnouncementMessagesProps,
    ]
  >
> => {
  const {
    chatComponent,
    serverComponent,
    explorationComponent,
    announcementsComponent,
    bellIconClass,
    bellIconStyles,
    chatProps,
    serverProps,
    explorationProps,
    announcementsProps,
    unreadServer,
    unreadAnnouncements,
  } = params;

  const chatTab = {
    label: 'Chat',
    component: chatComponent,
    props: chatProps,
  } as const;

  const serverTab = unreadServer
    ? ({
        label: 'Server Messages',
        component: serverComponent,
        activity_icon: bellIconClass,
        icon_styles: bellIconStyles,
        props: serverProps,
      } as const)
    : ({
        label: 'Server Messages',
        component: serverComponent,
        props: serverProps,
      } as const);

  const explorationTab = {
    label: 'Exploration',
    component: explorationComponent,
    props: explorationProps,
  } as const;

  const announcementsTab = unreadAnnouncements
    ? ({
        label: 'Announcements',
        component: announcementsComponent,
        activity_icon: bellIconClass,
        icon_styles: bellIconStyles,
        props: announcementsProps,
      } as const)
    : ({
        label: 'Announcements',
        component: announcementsComponent,
        props: announcementsProps,
      } as const);

  return [chatTab, serverTab, explorationTab, announcementsTab] as const;
};

export default buildTabs;
