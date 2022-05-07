export default interface GameChatProps {
    user_id: number;
    character_id: number;
    is_silenced: boolean;
    can_talk_again_at: string|null;
    is_admin: boolean;
}
