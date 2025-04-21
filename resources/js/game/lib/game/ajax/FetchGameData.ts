import { AxiosResponse } from "axios";
import Game from "../../../game";
import MapStateManager from "../../../sections/map/lib/state/map-state-manager";
import Ajax from "../../ajax/ajax";
import { CharacterType } from "../character/character-type";

type AjaxUrls = { url: string; name: string }[];

export default class FetchGameData {
    private component: Game;

    private urls: AjaxUrls | [];

    private characterSheet: CharacterType | null;

    constructor(component: Game) {
        this.component = component;

        this.characterSheet = null;

        this.urls = [];
    }

    setUrls(urls: { url: string; name: string }[]): FetchGameData {
        this.urls = urls;

        return this;
    }

    async doAjaxCalls() {
        if (typeof this.urls === "undefined") {
            return;
        }

        const makeSequentialAjaxCalls = async (urls: AjaxUrls) => {
            if (urls.length === 0) {
                return;
            }

            const url = urls[0];
            const result = await this.makeAjaxCall(url.url);

            switch (url.name) {
                case "character-sheet":
                    this.setCharacterSheet(result);
                    break;
                case "actions":
                    this.setActionData(result);
                    break;
                case "game-map":
                    this.setMapData(result);
                    break;
                case "quests":
                    this.setQuestData(result);
                    break;
                case "kingdoms":
                    this.setKingdomsData(result);
                    break;
                default:
                    break;
            }

            await makeSequentialAjaxCalls(urls.slice(1));
        };

        await makeSequentialAjaxCalls(this.urls);
    }

    async makeAjaxCall(url: string): Promise<AxiosResponse> {
        return new Promise((resolve, reject) => {
            new Ajax().setRoute(url).doAjaxCall(
                "get",
                (result: AxiosResponse) => {
                    resolve(result);
                },
                (error: AxiosResponse) => {
                    reject(error);
                },
            );
        });
    }

    setCharacterSheet(result: AxiosResponse) {
        this.characterSheet = result.data.sheet;

        this.component.setState(
            {
                character: result.data.sheet,
                percentage_loaded: 0.2,
                secondary_loading_title: "Fetching Quest Data ...",
                character_currencies: {
                    gold: result.data.sheet.gold,
                    gold_dust: result.data.sheet.gold_dust,
                    shards: result.data.sheet.shards,
                    copper_coins: result.data.sheet.copper_coins,
                },
                character_status: {
                    can_attack: result.data.sheet.can_attack,
                    can_attack_again_at: result.data.sheet.can_attack_again_at,
                    can_craft: result.data.sheet.can_craft,
                    can_craft_again_at: result.data.sheet.can_craft_again_at,
                    can_move: result.data.sheet.can_move,
                    is_dead: result.data.sheetis_dead,
                    automation_locked: result.data.sheet.automation_locked,
                    is_silenced: result.data.sheet.is_silenced,
                },
                fame_action_tasks: result.data.sheet.current_fame_tasks,
            },
            () => {
                this.component.setCharacterPosition(
                    result.data.sheet.base_position,
                );

                if (result.data.sheet.is_in_timeout) {
                    new Ajax().initiateGlobalTimeOut();
                }
            },
        );
    }

    setQuestData(result: AxiosResponse) {
        this.component.setState({
            quests: result.data,
            percentage_loaded: this.component.state.percentage_loaded + 0.2,
            secondary_loading_title: "Fetching Kingdom Data ...",
        });
    }

    setKingdomsData(result: AxiosResponse) {
        this.component.setState(
            {
                kingdoms: result.data.kingdoms,
                kingdom_logs: result.data.logs,
                loading: false,
                percentage_loaded: this.component.state.percentage_loaded + 0.2,
                secondary_loading_title: "Fetching Action Data ...",
            },
            () => {
                this.component.updateLogIcon();
            },
        );
    }

    setActionData(result: AxiosResponse) {
        if (this.characterSheet === null) {
            return;
        }

        this.component.setState({
            percentage_loaded: this.component.state.percentage_loaded + 0.2,
            secondary_loading_title: "Fetching Map Data ...",
            action_data: {
                raid_monsters: [],
                monsters: result.data.monsters,
            },
        });
    }

    setMapData(result: AxiosResponse) {
        console.log("setMapData", result.data);
        this.component.setState({
            map_data: MapStateManager.buildCoreState(
                result.data,
                this.component,
            ),
        });
    }
}
