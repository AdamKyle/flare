export interface DeferredProfileSectionLoading {
    activity: boolean;
    quests: boolean;
    kingdoms: boolean;
    analytics: boolean;
}

export interface DeferredProfileSectionErrors {
    activity: string | null;
    quests: string | null;
    kingdoms: string | null;
    analytics: string | null;
}
