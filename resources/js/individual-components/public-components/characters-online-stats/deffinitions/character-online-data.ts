export interface CharacterOnlineData {
    name: string;
    level: number;
    map: string | null;
    duration: number;
    currently_exploring: boolean;
    last_activity: string | null;
    last_heart_beat: string | null;
}
