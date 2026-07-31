import { injectable } from "tsyringe";
import TopsAjax from "../../shared/ajax/tops-ajax";
import { topsServiceContainer } from "../../shared/container/tops-container";
import TopsAjaxParams from "../../shared/types/tops-ajax-params";
import TopsApiResponse from "../../shared/types/tops-api-response";
import TopsValue from "../../shared/types/tops-value";
import CharacterProfile, {
    CharacterOverview,
    EquipmentProfile,
    ActivityProfile,
    QuestProfile,
    KingdomProfile,
    AnalyticsProfile,
} from "../types/character-profile";

@injectable()
export default class CharacterTopsAjax {
    private ajax: TopsAjax;

    constructor() {
        this.ajax = topsServiceContainer().fetch(TopsAjax);
    }

    public fetchLeaderboard(
        params: TopsAjaxParams,
        success: (data: TopsApiResponse) => void,
        failure: (message: string) => void,
    ): void {
        this.ajax.fetch<TopsApiResponse>(
            "game/tops/characters",
            params,
            success,
            failure,
        );
    }

    // Kept available for compatibility. The staged Character Profile fetch
    // (see the section-specific methods below) no longer calls this
    // monolithic endpoint.
    public fetchProfile(
        characterId: number,
        success: (data: CharacterProfile) => void,
        failure: (message: string) => void,
    ): void {
        this.ajax.fetch<CharacterProfile>(
            "game/tops/characters/" + characterId + "/profile",
            {},
            success,
            failure,
        );
    }

    public fetchOverview(characterId: number): Promise<CharacterOverview> {
        return this.fetchSection<CharacterOverview>(characterId, "overview");
    }

    public fetchStats(characterId: number): Promise<Record<string, TopsValue>> {
        return this.fetchSection<Record<string, TopsValue>>(
            characterId,
            "stats",
        );
    }

    public fetchEquipment(characterId: number): Promise<EquipmentProfile> {
        return this.fetchSection<EquipmentProfile>(characterId, "equipment");
    }

    public fetchSkills(
        characterId: number,
    ): Promise<Record<string, TopsValue>> {
        return this.fetchSection<Record<string, TopsValue>>(
            characterId,
            "skills",
        );
    }

    public fetchFactions(
        characterId: number,
    ): Promise<Record<string, TopsValue>> {
        return this.fetchSection<Record<string, TopsValue>>(
            characterId,
            "factions",
        );
    }

    public fetchReincarnation(
        characterId: number,
    ): Promise<Record<string, TopsValue>> {
        return this.fetchSection<Record<string, TopsValue>>(
            characterId,
            "reincarnation",
        );
    }

    public fetchActivity(characterId: number): Promise<ActivityProfile> {
        return this.fetchSection<ActivityProfile>(characterId, "activity");
    }

    public fetchQuests(characterId: number): Promise<QuestProfile> {
        return this.fetchSection<QuestProfile>(characterId, "quests");
    }

    public fetchKingdoms(characterId: number): Promise<KingdomProfile> {
        return this.fetchSection<KingdomProfile>(characterId, "kingdoms");
    }

    public fetchAnalytics(characterId: number): Promise<AnalyticsProfile> {
        return this.fetchSection<AnalyticsProfile>(characterId, "analytics");
    }

    // Typed promise wrapper around the existing callback-based
    // `TopsAjax.fetch()` implementation. No new Ajax implementation is
    // introduced; this only adapts the existing one to `Promise.all()`.
    private fetchSection<T>(characterId: number, section: string): Promise<T> {
        return new Promise<T>((resolve, reject) => {
            this.ajax.fetch<T>(
                "game/tops/characters/" + characterId + "/" + section,
                {},
                (data: T) => resolve(data),
                (message: string) => reject(message),
            );
        });
    }
}
