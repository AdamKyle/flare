export type SurveyInput = {
    [index: number]: {
        [key: string]: {
            value: string | boolean | string[];
            type: string;
        };
    };
};
