import AnnouncementMessageDefinition from '../../../api-definitions/chat/annoucement-message-definition';

export default interface AnnouncementCardProps {
  announcement: AnnouncementMessageDefinition;
  on_click_announcement?: (announcementId: number | null) => void;
}
