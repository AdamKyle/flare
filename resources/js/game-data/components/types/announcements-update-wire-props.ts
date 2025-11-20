import AnnouncementMessageDefinition from '../../../game/api-definitions/chat/annoucement-message-definition';

export default interface AnnouncementsUpdateWireProps {
  onEvent: (data: AnnouncementMessageDefinition) => void;
}
