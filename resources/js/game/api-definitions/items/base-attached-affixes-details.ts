export type StatKeys = 'str' | 'int' | 'dex' | 'dur' | 'chr' | 'agi' | 'focus';

export type StatModifiers = {
  [key in `${StatKeys}_mod`]?: number;
};

export default interface BaseAttachedAffixesDetails extends StatModifiers {
  affix_name: string;
}
