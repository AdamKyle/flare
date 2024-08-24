import React from "react";
import BasicCard from "../../ui/cards/basic-card";
import SectionProps from "./types/section-props";
import SectionState from "./types/section-state";

// Define an empty interface for props
interface QuestProps {}

export default class Quests extends React.Component<
    SectionProps,
    SectionState
> {
    constructor(props: SectionProps) {
        super(props);

        this.state = {
            is_preview_open: false,
            preview_files: [
                {
                    url: this.getMainImageUrl("quests"),
                    name: "Quests - mobile will require you to scroll",
                },
                {
                    url: this.getMainImageUrl("quest"),
                    name: "Quest - from Twisted Memories Plane",
                },
            ],
        };
    }

    getMainImageUrl(name: string): string {
        const baseUrl = this.props.base_image_url;

        return `${baseUrl}/intro-section-images/quests/${name}.png`;
    }

    managePreview() {
        this.props.preview_files(this.state.preview_files);
    }

    render() {
        return (
            <BasicCard>
                <div className="flex flex-col md:flex-row items-center space-y-4 md:space-x-4 md:space-y-0 mt-8">
                    <div className="w-full md:w-1/2 flex flex-col justify-center pl-0 md:pl-4">
                        <h2 className="text-xl md:text-2xl font-bold dark:text-white text-black">
                            Quests to Unlock Progression
                        </h2>
                        <p className="mt-4 dark:text-gray-300 text-gray-700">
                            Tlessa doesn't lock features behind cash shops.
                            Players can earn all game features at their own
                            pace, based on their play style.
                        </p>
                        <p className="mt-4 dark:text-gray-300 text-gray-700">
                            Tlessa offers{" "}
                            <a href="/information/quests" target="_blank">
                                quests{" "}
                                <i className="fas fa-external-link-alt"></i>
                            </a>
                            , which advance the main storyline and unlock
                            features like leveling beyond 1,000, accessing
                            different planes, walking on water or liquid
                            surfaces, and more.
                        </p>
                    </div>
                    <div className="w-full md:w-1/2 flex flex-col items-center">
                        <img
                            src={this.getMainImageUrl("quests")}
                            alt="Quests"
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
