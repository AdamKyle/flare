import { EventType } from '../../components/announcements/enums/EventType';
import RaidIdentityDefinition from '../../components/announcements/types/raid-identity-definition';

export interface EventTypeDefinition {
  id: number;
  type: EventType;
  started_at: string;
  ends_at: string;
  created_at: string;
  updated_at: string;
  raid_id: number | null;
  raid_identity?: RaidIdentityDefinition;
  event_goal_steps: number | null;
  current_event_goal_step: number | null;
}

export default interface AnnouncementMessageDefinition {
  id: number;
  message: string;
  expires_at: string;
  event_id: number;
  created_at: string;
  updated_at: string;
  expires_at_formatted: string;
  event_name: string;
  event: EventTypeDefinition | null;
}
