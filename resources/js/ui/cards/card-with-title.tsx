import React, { ReactNode } from "react";
import CardWithTitleProps from "./types/card-with-title-props";
import Separator from "../seperatror/separator";
import { fetchTitle } from "./helpers/fetch-title";

const CardWithTitle = (props: CardWithTitleProps): ReactNode => {
    return (
        <div className="bg-white rounded-sm drop-shadow-md dark:bg-gray-800 dark:text-gray-400">
            <div className="p-6">
                {fetchTitle(props.title, props.title_size)}
                <Separator />
                {props.children}
            </div>
        </div>
    );
};

export default CardWithTitle;
