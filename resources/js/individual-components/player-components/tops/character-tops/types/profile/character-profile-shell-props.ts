import CharacterProfileDefinition from "../character-profile";

export default interface CharacterProfileShellProps {
    profile: CharacterProfileDefinition;
    activeTab: string;
    onTabChange: (tab: string) => void;
}
