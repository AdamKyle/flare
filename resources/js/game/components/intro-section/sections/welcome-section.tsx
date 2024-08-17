import React from "react";
import BasicCard from "../../ui/cards/basic-card";
import SectionProps from "./types/section-props";
import SectionState from "./types/section-state";

export default class WelcomeSection extends React.Component<
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
                        ? "Mobile Game"
                        : "Desktop Game",
                },
                {
                    url: this.getMainImageUrl(!this.props.show_small_image),
                    name: !this.props.show_small_image
                        ? "Mobile Game"
                        : "Desktop Game",
                },
            ],
        };
    }

    getMainImageUrl(renderMobileImage: boolean): string {
        const baseUrl = this.props.base_image_url;

        if (renderMobileImage) {
            return `${baseUrl}/intro-section-images/welcome/mobile-game.png`;
        }

        return `${baseUrl}/intro-section-images/welcome/desktop-game.png`;
    }
    managePreview() {
        this.props.preview_files(this.state.preview_files);
    }

    render() {
        return (
            <div>
                <BasicCard>
                    <div className="relative flex flex-col md:flex-row items-center md:space-x-4">
                        <div className="w-full md:w-1/2 flex flex-col items-center">
                            <img
                                src={this.getMainImageUrl(
                                    this.props.show_small_image,
                                )}
                                alt="Main Game"
                                className="w-[200px] h-[150px] object-cover border-4 dark:border-gray-700 border-gray-300 cursor-pointer"
                                onClick={this.managePreview.bind(this)}
                            />
                            <div className={"mt-2 text-center italic text-sm"}>
                                Click/Tap to make larger
                            </div>
                        </div>
                        <div className="w-full md:w-1/2 flex flex-col justify-center pl-0 md:pl-4 mt-4 md:mt-0">
                            <h2 className="text-2xl font-bold dark:text-white text-black">
                                Welcome to Tlessa
                            </h2>
                            <p className="mt-4 dark:text-gray-300 text-gray-700">
                                Planes of Tlessa is about{" "}
                                <strong>fighting</strong> monsters to gain loot
                                to take on stronger critters. The core game loop
                                of Tlessa is simple: <strong>fight</strong>,{" "}
                                <strong>craft</strong> and{" "}
                                <strong>enchant</strong> to make better gear.
                            </p>
                            <p className="mt-4 dark:text-gray-300 text-gray-700">
                                Tlessa also offers a variety of{" "}
                                <a href="/features" target="_blank">
                                    features{" "}
                                    <i className="fas fa-external-link-alt"></i>
                                </a>
                                {""}
                                and depth in its systems from character
                                advancement to kingdom management and more.
                            </p>

                            <p className="mt-4 dark:text-gray-300 text-gray-700">
                                Tlessa also offers a{" "}
                                <a href="/information/home" target="_blank">
                                    comprehensive help{" "}
                                    <i className="fas fa-external-link-alt"></i>
                                </a>
                                {""}
                                section as well as the{" "}
                                <strong>Guide system</strong>
                            </p>
                        </div>
                    </div>
                </BasicCard>
            </div>
        );
    }
}
