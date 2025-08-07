export const STAT_DEFINITIONS = [
  {
    label: 'Strength',
    baseKey: 'str_modifier' as const,
    adjKey: 'str_adjustment' as const,
    isPercent: true,
  },
  {
    label: 'Durability',
    baseKey: 'dur_modifier' as const,
    adjKey: 'dur_adjustment' as const,
    isPercent: true,
  },
  {
    label: 'Intelligence',
    baseKey: 'int_modifier' as const,
    adjKey: 'int_adjustment' as const,
    isPercent: true,
  },
  {
    label: 'Dexterity',
    baseKey: 'dex_modifier' as const,
    adjKey: 'dex_adjustment' as const,
    isPercent: true,
  },
  {
    label: 'Charisma',
    baseKey: 'chr_modifier' as const,
    adjKey: 'chr_adjustment' as const,
    isPercent: true,
  },
  {
    label: 'Agility',
    baseKey: 'agi_modifier' as const,
    adjKey: 'agi_adjustment' as const,
    isPercent: true,
  },
  {
    label: 'Focus',
    baseKey: 'focus_modifier' as const,
    adjKey: 'focus_adjustment' as const,
    isPercent: true,
  },
] as const;
