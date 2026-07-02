import { injectable } from "tsyringe";
import TopsAjax from "../../shared/ajax/tops-ajax";
import { topsServiceContainer } from "../../shared/container/tops-container";
import TopsAjaxParams from "../../shared/types/tops-ajax-params";
import TopsApiResponse from "../../shared/types/tops-api-response";
import CharacterProfile from "../types/character-profile";

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
        this.ajax.fetch<CharacterProfile>(
            "game/tops/characters/" + characterId + "/profile",
            {},
            success,
            failure,
        );
    }
}
