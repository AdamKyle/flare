import { singleton } from "tsyringe";

interface EffectsResult {
    stat_increase: string;
    devouring_adjustment: string;
}
@singleton()
export default class ItemHolyEffects {
    public determineItemHolyEffects(holyLevel: number): EffectsResult {
        return {
            stat_increase: this.getStatIncrease(holyLevel),
            devouring_adjustment: this.getDevouringIncrease(holyLevel),
        };
    }

    private getStatIncrease(holyLevel: number): string {
        switch (holyLevel) {
            case 1:
                return "1-3";
            case 2:
                return "1-5";
            case 3:
                return "1-8";
            case 4:
                return "1-10";
            case 5:
                return "1-15";
            default:
                return "ERROR";
        }
    }

    private getDevouringIncrease(holyLevel: number): string {
        switch (holyLevel) {
            case 1:
                return "1-3";
            case 2:
                return "1-5";
            case 3:
                return "1-8";
            case 4:
                return "1-10";
            case 5:
                return "1-15";
            default:
                return "ERROR";
        }
    }
}
