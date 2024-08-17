import React from "react";
import BasicCard from "../../ui/cards/basic-card";
import SectionProps from "./types/section-props";
import SectionState from "./types/section-state";

export default class CraftingEnchanting extends React.Component<
    SectionProps,
    SectionState
> {
    constructor(props: SectionProps) {
        super(props);

        this.state = {
            is_preview_open: false,
            preview_files: [
                {
                    url: this.getMainImageUrl(this.props.show_small_image),
                    name: this.props.show_small_image
                        ? "Mobile Crafting"
                        : "Desktop Crafting",
                },
                {
                    url: this.getMainImageUrl(!this.props.show_small_image),
                    name: !this.props.show_small_image
                        ? "Mobile Crafting"
                        : "Desktop Crafting",
                },
                {
                    url: this.getGif("desktop-crafting"),
                    name: "Crafting - Desktop",
                },
                {
                    url: this.getGif("mobile-crafting"),
                    name: "crafting - Mobile",
                },
            ],
        };
    }

    getMainImageUrl(renderMobileImage: boolean): string {
        const baseUrl = this.props.base_image_url;

        if (renderMobileImage) {
            return `${baseUrl}/intro-section-images/crafting-enchanting/mobile-crafting.png`;
        }

        return `${baseUrl}/intro-section-images/crafting-enchanting/desktop-crafting.png`;
    }

    getGif(name: string): string {
        const baseUrl = this.props.base_image_url;

        return `${baseUrl}/intro-section-images/crafting-enchanting/${name}.gif`;
    }

    managePreview() {
        this.props.preview_files(this.state.preview_files);
    }

    render() {
        return (
            <BasicCard>
                <div className="flex flex-col md:flex-row items-center space-y-4 md:space-x-4 md:space-y-0 mt-8">
                    <div className="w-full md:w-1/2 flex flex-col items-center">
                        <img
                            src={this.getMainImageUrl(
                                this.props.show_small_image,
                            )}
                            alt="Crafting & Enchanting"
                            className="w-48 h-36 md:w-[200px] md:h-[150px] object-cover border-4 dark:border-gray-700 border-gray-300 cursor-pointer"
                            onClick={this.managePreview.bind(this)}
                        />
                        <div className="mt-2 text-center italic text-sm">
                            Click/Tap to make larger
                        </div>
                    </div>
                    <div className="w-full md:w-1/2 flex flex-col justify-center pl-0 md:pl-4">
                        <h2 className="text-xl md:text-2xl font-bold dark:text-white text-black">
                            Crafting and Enchanting
                        </h2>
                        <p className="mt-4 dark:text-gray-300 text-gray-700">
                            While fighting, players will find randomly enchanted
                            gear, similar to what you would get in other games
                            like Diablo or Path of Exile. As you start
                            progressing, you will want to craft your own gear,
                            starting with shop gear and advancing beyond what
                            the shop sells around level 200 of the crafting
                            type.
                        </p>
                        <p className="mt-4 dark:text-gray-300 text-gray-700">
                            From there, you will want to enchant your gear with
                            specific enchantments, which you can see{" "}
                            <a href="/information/enchanting" target="_blank">
                                here{" "}
                                <i className="fas fa-external-link-alt"></i>
                            </a>
                            . As you progress through these systems, you will be
                            able to take on stronger and tougher creatures,
                            gaining more rewards, gold, and other currencies for
                            mid and end-game crafting.
                        </p>
                    </div>
                </div>
            </BasicCard>
        );
    }
}
