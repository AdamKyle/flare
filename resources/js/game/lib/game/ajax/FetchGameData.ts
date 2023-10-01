import { setDefaultResultOrder } from "dns";
import Game from "../../../game";
import Ajax from "../../ajax/ajax";
import { AxiosResponse } from "axios";
import { calculateTimeLeft } from "../../helpers/time-calculator";
import MapStateManager from "../map/state/map-state-manager";

export default class FetchGameData {

    private component: Game;

    private urls?: { url: string, name: string }[];

    constructor(component: Game) {
        this.component = component;
    }

    setUrls(urls: { url: string, name: string }[]): FetchGameData {
        this.urls = urls;

        return this;
    }

    doAjaxCalls() {

        if (typeof this.urls === 'undefined') {
            return;
        }

        this.urls.forEach((url) => {
            (new Ajax()).setRoute(url.url).doAjaxCall('get', (result: AxiosResponse) => {
                switch (url.name) {
                    case 'character-sheet':
                        return this.setCharacterSheet(result);
                    case 'actions':
                        return this.setActionData(result);
                    case 'game-map':
                        return this.setMapData(result);
                    case 'quests':
                        return this.setQuestData(result);
                    case 'kingdoms':
                        return this.setKingdomsData(result);
                    default:
                        break;
                }
            }, (error: AxiosResponse) => {
                console.error(error);
            });
        });
    }

    setCharacterSheet(result: AxiosResponse) {

        this.component.setState({
            character: result.data.sheet,
            percentage_loaded: .20,
            secondary_loading_title: 'Fetching Quest Data ...',
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
                is_dead: result.data.sheetis_dead,
                automation_locked: result.data.sheet.automation_locked,
                is_silenced: result.data.sheet.is_silenced,
                killed_in_pvp: result.data.sheet.kill_in_pvp,
            },
        }, () => {
            this.component.setCharacterPosition(result.data.sheet.base_position);

            if (result.data.sheet.is_in_timeout) {
                (new Ajax()).initiateGlobalTimeOut();
            }
        });
    }

    setQuestData(result: AxiosResponse) {
        this.component.setState({
            quests: result.data,
            percentage_loaded: this.component.state.percentage_loaded + .20,
            secondary_loading_title: 'Fetching Kingdom Data ...',
        });
    }

    setKingdomsData(result: AxiosResponse) {

        this.component.setState({
            kingdoms: result.data.kingdoms,
            kingdom_logs: result.data.logs,
            loading: false,
            percentage_loaded: this.component.state.percentage_loaded + .20,
            secondary_loading_title: 'Fetching Action Data ...',
        });
    }

    setActionData(result: AxiosResponse) {
        if (this.component.state.character === null) {
            return;
        }

        this.component.setState({
            percentage_loaded: this.component.state.percentage_loaded + .20,
            secondary_loading_title: 'Fetching Map Data ...',
            action_data: {
                raid_monsters: [],
                monsters: result.data.monsters,
                attack_time_out: this.component.state.character.can_attack_again_at !== null ?
                    calculateTimeLeft(this.component.state.character.can_attack_again_at) : 0,
                crafting_time_out: this.component.state.character.can_craft_again_at !== null ?
                    calculateTimeLeft(this.component.state.character.can_craft_again_at) : 0,
                attack_time_out_started: 0,
                crafting_time_out_started: 0,
            }
        });
    }

    setMapData(result: AxiosResponse) {
        this.component.setState({
            map_data: MapStateManager.buildCoreState(result.data, this.component),
        });
    }
}
