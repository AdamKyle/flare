import Game from "../../../game";
import Ajax from "../../ajax/ajax";
import {AxiosResponse} from "axios";

export default class FetchGameData {

    private component: Game;

    private urls?: {url: string, name: string}[];

    constructor(component: Game) {
        this.component   = component;
    }

    setUrls(urls: {url: string, name: string}[]): FetchGameData {
        this.urls = urls;

        return this;
    }

    doAjaxCalls() {

        if (typeof this.urls === 'undefined') {
            return;
        }

        this.urls.forEach((url) => {
            (new Ajax()).setRoute(url.url).doAjaxCall('get', (result: AxiosResponse) => {
                switch(url.name) {
                    case 'character-sheet':
                        return this.setCharacterSheet(result);
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

    setCharacterSheet(result: AxiosResponse)  {
        this.component.setState({
            character: result.data.sheet,
            percentage_loaded: .33,
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
                can_adventure: result.data.sheet.can_adventure,
                is_dead: result.data.sheetis_dead,
                automation_locked: result.data.sheet.automation_locked,
                is_silenced: result.data.sheet.is_silenced,
            }
        });
    }

    setQuestData(result: AxiosResponse)  {
        this.component.setState({
            quests: result.data,
            percentage_loaded: this.component.state.percentage_loaded + .33,
            secondary_loading_title: 'Fetching Kingdom Data ...',
        });
    }

    setKingdomsData(result: AxiosResponse)  {
        this.component.setState({
            kingdoms: result.data,
            loading: false,
        });
    }
}
