export default interface ChatType {
    color: string;
    map_name: string;
    character_name: string;
    message: string;
    x: number;
    y: number;
    type: 'chat' | 'creator-message' | 'global-message' | 'error-message' | 'private-message-sent',
    hide_location: boolean,
}
