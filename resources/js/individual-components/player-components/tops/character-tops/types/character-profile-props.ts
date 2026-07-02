import CharacterProfileDefinition from "./character-profile";

export default interface CharacterProfileProps {
    profile: CharacterProfileDefinition;
    activeTab: string;
    onTabChange: (tab: string) => void;
}
