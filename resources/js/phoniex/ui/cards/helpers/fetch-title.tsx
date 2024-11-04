import React, { ReactNode } from "react";
import { TitleSize } from "../enums/title-size";
import { match } from "ts-pattern";

export const fetchTitle = (title: string, titleSize?: TitleSize): ReactNode => {
    return match(titleSize)
        .with(TitleSize.H1, () => <h1 className="w-full">{title}</h1>)
        .with(TitleSize.H2, () => <h2 className="w-full">{title}</h2>)
        .with(TitleSize.H3, () => <h3 className="w-full">{title}</h3>)
        .with(TitleSize.H4, () => <h4 className="w-full">{title}</h4>)
        .with(TitleSize.H5, () => <h5 className="w-full">{title}</h5>)
        .otherwise(() => <h3 className="w-full">{title}</h3>);
};
