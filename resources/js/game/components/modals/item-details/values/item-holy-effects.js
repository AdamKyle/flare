var __decorate =
    (this && this.__decorate) ||
    function (decorators, target, key, desc) {
        var c = arguments.length,
            r =
                c < 3
                    ? target
                    : desc === null
                      ? (desc = Object.getOwnPropertyDescriptor(target, key))
                      : desc,
            d;
        if (
            typeof Reflect === "object" &&
            typeof Reflect.decorate === "function"
        )
            r = Reflect.decorate(decorators, target, key, desc);
        else
            for (var i = decorators.length - 1; i >= 0; i--)
                if ((d = decorators[i]))
                    r =
                        (c < 3
                            ? d(r)
                            : c > 3
                              ? d(target, key, r)
                              : d(target, key)) || r;
        return c > 3 && r && Object.defineProperty(target, key, r), r;
    };
import { singleton } from "tsyringe";
var ItemHolyEffects = (function () {
    function ItemHolyEffects() {}
    ItemHolyEffects.prototype.determineItemHolyEffects = function (holyLevel) {
        return {
            stat_increase: this.getStatIncrease(holyLevel),
            devouring_adjustment: this.getDevouringIncrease(holyLevel),
        };
    };
    ItemHolyEffects.prototype.getStatIncrease = function (holyLevel) {
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
    };
    ItemHolyEffects.prototype.getDevouringIncrease = function (holyLevel) {
        switch (holyLevel) {
            case 1:
                return "0.1-0.3";
            case 2:
                return "0.1-0.5";
            case 3:
                return "0.1-0.8";
            case 4:
                return "0.1-1";
            case 5:
                return "0.1-1.5";
            default:
                return "ERROR";
        }
    };
    ItemHolyEffects = __decorate([singleton()], ItemHolyEffects);
    return ItemHolyEffects;
})();
export default ItemHolyEffects;
//# sourceMappingURL=item-holy-effects.js.map
