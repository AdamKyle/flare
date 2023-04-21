export default class SpecialAttackClasses {

    private static FIGHTER          = 'Fighter';
    private static VAMPIRE          = 'Vampire';
    private static RANGER           = 'Ranger'
    private static PROPHET          = 'Prophet';
    private static HERETIC          = 'Heretic';
    private static THIEF            = 'Thief';
    private static BLACKSMITH       = 'Blacksmith';
    private static ARCANE_ALCHEMIST = 'Arcane Alchemist';
    private static PRISONER         = 'Prisoner';
    private static ALCOHOLIC        = 'Alcoholic';
    private static MERCHANT         = 'Merchant';


    public static isFighter(className: string): boolean {
        return className === SpecialAttackClasses.FIGHTER;
    }

    public static isVampire(className: string): boolean {
        return className === SpecialAttackClasses.VAMPIRE;
    }

    public static isRanger(className: string): boolean {
        return className === SpecialAttackClasses.RANGER;
    }

    public static isProphet(className: string): boolean {
        return className === SpecialAttackClasses.PROPHET;
    }

    public static isHeretic(className: string): boolean {
        return className === SpecialAttackClasses.HERETIC;
    }

    public static isThief(className: string): boolean {
        return className === SpecialAttackClasses.THIEF;
    }

    public static isBlackSmith(className: string): boolean {
        return className === SpecialAttackClasses.BLACKSMITH;
    }

    public static isArcaneAlchemist(className: string): boolean {
        return className === SpecialAttackClasses.ARCANE_ALCHEMIST;
    }

    public static isPrisoner(className: string): boolean {
        return className === SpecialAttackClasses.PRISONER;
    }

    public static isAlcoholic(className: string): boolean {
        return className === SpecialAttackClasses.ALCOHOLIC;
    }

    public static isMerchant(className: string): boolean {
        return className === SpecialAttackClasses.MERCHANT;
    }

}
