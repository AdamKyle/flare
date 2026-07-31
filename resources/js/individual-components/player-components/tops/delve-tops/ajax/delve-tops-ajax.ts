import { injectable } from "tsyringe";
import TopsAjax from "../../shared/ajax/tops-ajax";
import { topsServiceContainer } from "../../shared/container/tops-container";
import TopsAjaxParams from "../../shared/types/tops-ajax-params";
import TopsApiResponse from "../../shared/types/tops-api-response";

@injectable()
export default class DelveTopsAjax {
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
            "game/tops/delve",
            params,
            success,
            failure,
        );
    }
}
