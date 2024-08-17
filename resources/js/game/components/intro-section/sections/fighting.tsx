import React from "react";
import BasicCard from "../../ui/cards/basic-card";
import SectionState from "./types/section-state";
import SectionProps from "./types/section-props";

export default class Fighting extends React.Component<
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
                        ? "Mobile Fighting"
                        : "Desktop Fighting",
                },
                {
                    url: this.getMainImageUrl(!this.props.show_small_image),
                    name: !this.props.show_small_image
                        ? "Mobile Fighting"
                        : "Desktop Fighting",
                },
                {
                    url: this.getGif("fighting-desktop"),
                    name: "Fighting - Desktop",
                },
                {
                    url: this.getGif("fighting-mobile"),
                    name: "Fighting - Mobile",
                },
            ],
        };
    }

    getMainImageUrl(renderMobileImage: boolean): string {
        const baseUrl = this.props.base_image_url;

        if (renderMobileImage) {
            return `${baseUrl}/intro-section-images/fighting/mobile-fighting.png`;
        }

        return `${baseUrl}/intro-section-images/fighting/desktop-fighting.png`;
    }

    getGif(name: string): string {
        const baseUrl = this.props.base_image_url;

        return `${baseUrl}/intro-section-images/fighting/${name}.gif`;
    }

    managePreview() {
        this.props.preview_files(this.state.preview_files);
    }

    render() {
        return (
            <BasicCard>
                <div className="flex flex-col md:flex-row items-center space-y-4 md:space-y-0 md:space-x-4">
                    <div className="w-full md:w-1/2 flex flex-col justify-center px-4 md:px-0">
                        <h2 className="text-xl md:text-2xl font-bold dark:text-white text-black">
                            Fighting
                        </h2>
                        <p className="mt-4 dark:text-gray-300 text-gray-700">
                            Fighting is done by selecting a monster and clicking
                            attack to initiate the battle.
                        </p>
                        <p className="mt-4 dark:text-gray-300 text-gray-700">
                            From here we can select one of the five attack
                            types: Attack, Cast, Cast and Attack, Attack and
                            Cast or Defend.
                        </p>
                        <p className="mt-4 dark:text-gray-300 text-gray-700">
                            When you are new, you should use Attack, this
                            attacks with your weapons, later on you can change
                            up your attack based on your class, for example:
                            Heretics will eventually want one of the Cast
                            attacks: Cast, Cast and Attack or Attack and Cast.
                        </p>
                        <p className="mt-4 dark:text-gray-300 text-gray-700">
                            You can learn more about the various attack types{" "}
                            <a href="/information/combat" target="_blank">
                                here{" "}
                                <i className="fas fa-external-link-alt"></i>
                            </a>
                            .
                        </p>
                    </div>
                    <div className="w-full md:w-1/2 flex flex-col items-center">
                        <img
                            src={this.getMainImageUrl(
                                this.props.show_small_image,
                            )}
                            alt="Fighting"
                            className="w-48 h-36 md:w-[200px] md:h-[150px] object-cover border-4 dark:border-gray-700 border-gray-300 cursor-pointer"
                            onClick={this.managePreview.bind(this)}
                        />
                        <div className="mt-2 text-center italic text-sm">
                            Click/Tap to make larger
                        </div>
                    </div>
                </div>
            </BasicCard>
        );
    }
}
