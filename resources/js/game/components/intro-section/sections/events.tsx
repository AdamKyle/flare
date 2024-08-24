import React from "react";
import BasicCard from "../../ui/cards/basic-card";
import SectionProps from "./types/section-props";
import SectionState from "./types/section-state";

export default class Events extends React.Component<
    SectionProps,
    SectionState
> {
    constructor(props: SectionProps) {
        super(props);

        this.state = {
            is_preview_open: false,
            preview_files: [
                {
                    url: this.getMainImageUrl("events"),
                    name: "Event Schedule which you can see in game via the left hand sidebar",
                },
            ],
        };
    }

    getMainImageUrl(name: string): string {
        const baseUrl = this.props.base_image_url;

        return `${baseUrl}/intro-section-images/events/${name}.png`;
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
                            src={this.getMainImageUrl("events")}
                            alt="Events"
                            className="w-48 h-36 md:w-[200px] md:h-[150px] object-cover border-4 dark:border-gray-700 border-gray-300 cursor-pointer"
                            onClick={this.managePreview.bind(this)}
                        />
                        <div className="mt-2 text-center italic text-sm">
                            Click/Tap to make larger
                        </div>
                    </div>
                    <div className="w-full md:w-1/2 flex flex-col justify-center pl-0 md:pl-4">
                        <h2 className="text-xl md:text-2xl font-bold dark:text-white text-black">
                            Raids, Temporary Planes, and Weekly Events!
                        </h2>
                        <p className="mt-4 dark:text-gray-300 text-gray-700">
                            Tlessa offers players a variety of activities,
                            including events that unlock new planes of
                            existence. These activities are available for all
                            levels, providing opportunities for epic loot and
                            quests that advance the game's story. Don't worry
                            about missing out—these events repeat regularly!
                        </p>
                    </div>
                </div>
            </BasicCard>
        );
    }
}
