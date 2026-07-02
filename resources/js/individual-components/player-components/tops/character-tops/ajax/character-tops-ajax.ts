import { injectable } from "tsyringe";
import TopsAjax from "../../shared/ajax/tops-ajax";
import { topsServiceContainer } from "../../shared/container/tops-container";
import TopsAjaxParams from "../../shared/types/tops-ajax-params";
import TopsApiResponse from "../../shared/types/tops-api-response";
import CharacterProfile from "../types/character-profile";
import TopsValue from "../../shared/types/tops-value";

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

    public fetchProfile(
        characterId: number,
        success: (data: CharacterProfile) => void,
        failure: (message: string) => void,
    ): void {
        const sections = [
            "overview",
            "stats",
            "equipment",
            "skills",
            "factions",
            "reincarnation",
            "activity",
            "quests",
            "kingdoms",
            "analytics",
        ];
        const results: Record<string, Record<string, TopsValue>> = {};
        let remaining = sections.length;

        sections.forEach((section: string) => {
            this.ajax.fetch<Record<string, TopsValue>>(
                "game/tops/characters/" + characterId + "/" + section,
                {},
                (data: Record<string, TopsValue>) => {
                    results[section] = data;
                    remaining -= 1;

                    if (remaining === 0) {
                        success(results as CharacterProfile);
                    }
                },
                failure,
            );
        });
    }
}
