type TopsValue =
    | string
    | number
    | boolean
    | null
    | TopsValue[]
    | {
          [key: string]: TopsValue;
      };

export default TopsValue;
