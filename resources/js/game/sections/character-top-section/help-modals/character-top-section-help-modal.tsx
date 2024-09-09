import React from "react";
import HelpDialogue from "../../../components/ui/dialogue/help-dialogue";

export default class CharacterTopSectionHelpModal extends React.Component<
    any,
    any
> {
    constructor(props: any) {
        super(props);
    }

    buildTitle() {
        switch (this.props.type) {
            case "ac":
                return "AC Help";
            case "attack":
                return "Attack Help";
            case "health":
                return "Health Help";
            default:
                return "UNKNOWN";
        }
    }

    buildAcHelpSection() {
        return (
            <div>
                <p className="my-2 text-gray-700 dark:text-gray-200">
                    Armour Class, or AC, is the amount of damage you will block
                    when the enemy attacks.
                </p>
                <p className="my-2 text-gray-700 dark:text-gray-200">
                    Armour Class, or AC, is calculated by taking all items that
                    give a Base AC, like Body, Helmet and so on and adding them
                    together and then dividing by the amount of items that give
                    a base AC. We then apply any{" "}
                    <a href="/information/enchanting" target="_blank">
                        enchantments{" "}
                        <i className="fas fa-external-link-alt"></i>
                    </a>
                    , skills and so on - including{" "}
                    <a href="/information/class-ranks" target="_blank">
                        class specials{" "}
                        <i className="fas fa-external-link-alt"></i>
                    </a>
                    , that increase the AC on top. There are also{" "}
                    <a href="/information/class-skills" target="_blank">
                        class skills that effect this as well.{" "}
                        <i className="fas fa-external-link-alt"></i>
                    </a>
                </p>
                <p className="my-2 text-gray-700 dark:text-gray-200">
                    You can see a complete breakdown of your AC if you go to
                    Character Sheet tab and click "Show Additional Information".
                    From here you can see a complete break down of everything
                    that goes into your AC by clicking on the "Ac" stat title.
                    If you are on mobile, you go to your Character Sheet Tab and
                    expand the top section (Character Details) and then click
                    the "Show Additional Information" button.
                </p>
            </div>
        );
    }

    buildAttackHelpSection() {
        return (
            <div>
                <p className="my-2 text-gray-700 dark:text-gray-200">
                    Attack is the culmination of your weapons, rings and spells
                    equipped.
                </p>
                <p className="my-2 text-gray-700 dark:text-gray-200">
                    There are 5 attack types in the game: Attack, Cast, Attack
                    and Cast, Cast and Attack and Defend. Each will use a
                    different aspect of your equipped weapons.
                </p>
                <p className="my-2 text-gray-700 dark:text-gray-200">
                    <strong>Attack</strong> will use weapons in both of your
                    hands.
                </p>
                <p className="my-2 text-gray-700 dark:text-gray-200">
                    <strong>Cast</strong> will use spells in both of your spell
                    slots.
                </p>
                <p className="my-2 text-gray-700 dark:text-gray-200">
                    <strong>Attack and Cast</strong> will use your left hand
                    weapon and your spell slot 2.
                </p>
                <p className="my-2 text-gray-700 dark:text-gray-200">
                    <strong>Cast and Attack</strong> will use spell slot 1 and
                    then your right hand weapon.
                </p>
                <p className="my-2 text-gray-700 dark:text-gray-200">
                    <strong>Defend</strong> Will use your armour and shields
                    equipped to block damage. Some class specials and the class
                    Vampire, once more established, will make use of this to
                    deal more damage, fire off special abilities and so on.
                </p>
                <p className="my-2 text-gray-700 dark:text-gray-200">
                    Regardless of your attack type, other aspects that effect
                    your attack include:{" "}
                    <a href="/information/enchanting" target="_blank">
                        enchantments{" "}
                        <i className="fas fa-external-link-alt"></i>
                    </a>
                    ,{" "}
                    <a href="/information/class-ranks" target="_blank">
                        class specials{" "}
                        <i className="fas fa-external-link-alt"></i>
                    </a>{" "}
                    and
                    <a href="/information/class-skills" target="_blank">
                        class skills that effect this as well.{" "}
                        <i className="fas fa-external-link-alt"></i>
                    </a>{" "}
                    As well as your characters attack stat, which can be seen on
                    the character sheet in the Top Left called: Damage Stat. If
                    you are on mobile you can see this by going to the character
                    sheet tab and expanding the top section (Character Details).
                </p>

                <p className="my-2 text-gray-700 dark:text-gray-200">
                    You can see a complete breakdown of your various attacks if
                    you go to Character Sheet tab and click "Show Additional
                    Information". From here you can see a complete break down of
                    everything that goes into your specified attack by clicking
                    on the attack stat title (ie, weapon damage, ring damage,
                    spell damage and healing amount). If you are on mobile, you
                    go to your Character Sheet Tab and expand the top section
                    (Character Details) and then click the "Show Additional
                    Information" button.
                </p>
            </div>
        );
    }

    buildHealthSection() {
        return (
            <div>
                <p className="my-2 text-gray-700 dark:text-gray-200">
                    Health is how much HP you have, how much damage you can take
                    before dying.
                </p>
                <p className="my-2 text-gray-700 dark:text-gray-200">
                    Health is calculated by taking your durability, applying all{" "}
                    <a href="/information/enchanting" target="_blank">
                        enchantments{" "}
                        <i className="fas fa-external-link-alt"></i>
                    </a>{" "}
                    to it and any{" "}
                    <a href="/information/class-ranks" target="_blank">
                        class specials{" "}
                        <i className="fas fa-external-link-alt"></i>
                    </a>{" "}
                    as well as{" "}
                    <a href="/information/class-skills" target="_blank">
                        class skills.{" "}
                        <i className="fas fa-external-link-alt"></i>
                    </a>{" "}
                    Durability or Dur is the calculation of all enchantments
                    that effect this stat - also known as Modded Durability.
                </p>
                <p className="my-2 text-gray-700 dark:text-gray-200">
                    You can see a complete breakdown of your Health/Modded
                    Durability if you go to Character Sheet tab and click "Show
                    Additional Information". From here you can see a complete
                    break down of everything that goes into your Health by
                    clicking on the "Health" stat title. If you want to know
                    what goes into your durability, click the Modded Durability
                    stat. If you are on mobile, you go to your Character Sheet
                    Tab and expand the top section (Character Details) and then
                    click the "Show Additional Information" button.
                </p>
            </div>
        );
    }

    render() {
        return (
            <HelpDialogue
                is_open={true}
                manage_modal={this.props.manage_modal}
                title={this.buildTitle()}
            >
                {this.props.type === "ac" ? this.buildAcHelpSection() : null}

                {this.props.type === "attack"
                    ? this.buildAttackHelpSection()
                    : null}

                {this.props.type === "health"
                    ? this.buildHealthSection()
                    : null}
            </HelpDialogue>
        );
    }
}
