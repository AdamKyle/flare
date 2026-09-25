import ExplorationMessageProps from '../components/exploration-messages/definitions/exploration-message-props';
import MessagesProps from '../components/messages/definitions/message-props';
import ServerMessagesProps from '../components/server-messages/definitions/server-messages-props';

import { ComponentFromProps, TabTupleFromProps } from 'ui/tabs/types/tab-item';

type Params = {
  chatComponent: ComponentFromProps<MessagesProps>;
  serverComponent: ComponentFromProps<ServerMessagesProps>;
  explorationComponent: ComponentFromProps<ExplorationMessageProps>;
  bellIconClass: string;
  bellIconStyles: string;
  chatProps: MessagesProps;
  serverProps: ServerMessagesProps;
  explorationProps: ExplorationMessageProps;
  unreadServer: boolean;
  unreadExploration: boolean;
};

const buildTabs = (
  params: Params
): Readonly<
  TabTupleFromProps<
    [MessagesProps, ServerMessagesProps, ExplorationMessageProps]
  >
> => {
  const {
    chatComponent,
    serverComponent,
    explorationComponent,
    bellIconClass,
    bellIconStyles,
    chatProps,
    serverProps,
    explorationProps,
    unreadServer,
    unreadExploration,
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

  const explorationTab = unreadExploration
    ? ({
        label: 'Exploration',
        component: explorationComponent,
        activity_icon: bellIconClass,
        icon_styles: bellIconStyles,
        props: explorationProps,
      } as const)
    : ({
        label: 'Exploration',
        component: explorationComponent,
        props: explorationProps,
      } as const);

  return [chatTab, serverTab, explorationTab] as const;
};

export default buildTabs;
