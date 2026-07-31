import CharacterProfileDefinition from "./character-profile";
import {
    DeferredProfileSectionLoading,
    DeferredProfileSectionErrors,
} from "./deferred-profile-section-state";

export default interface CharacterProfileProps {
    profile: CharacterProfileDefinition;
    activeTab: string;
    onTabChange: (tab: string) => void;
    deferred_loading: DeferredProfileSectionLoading;
    deferred_errors: DeferredProfileSectionErrors;
}
