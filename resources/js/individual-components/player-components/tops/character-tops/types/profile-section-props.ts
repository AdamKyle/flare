import TopsValue from "../../shared/types/tops-value";

export default interface ProfileSectionProps {
    overview?: Record<string, TopsValue>;
    stats?: Record<string, TopsValue>;
    equipment?: Record<string, TopsValue>;
    skills?: Record<string, TopsValue>;
    factions?: Record<string, TopsValue>;
    reincarnation?: Record<string, TopsValue>;
    activity?: Record<string, TopsValue>;
    quests?: Record<string, TopsValue>;
    kingdoms?: Record<string, TopsValue>;
    analytics?: Record<string, TopsValue>;
}
