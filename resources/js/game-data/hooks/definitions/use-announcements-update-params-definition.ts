import AnnouncementMessageDefinition from '../../../game/api-definitions/chat/annoucement-message-definition';

export default interface UseAnnouncementsUpdateParamsDefinition {
  onEvent: (data: AnnouncementMessageDefinition) => void;
}
