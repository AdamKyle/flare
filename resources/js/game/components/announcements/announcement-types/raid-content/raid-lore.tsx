import React, { ReactNode } from 'react';

import { RaidType } from '../../enums/RaidType';

export interface RaidCardContent {
  key: string;
  aria_label: string;
  icon_class: string;
  title: string;
  front_body: ReactNode;
  back_body: ReactNode;
}

export interface RaidFaqEntry {
  question: string;
  answer: ReactNode;
}

export interface RaidLore {
  tagline: string;
  story: string;
  cards: [RaidCardContent, RaidCardContent, RaidCardContent];
  faq: [RaidFaqEntry, RaidFaqEntry];
}

/**
 * Raid story/detail content adapted from the 1.0 raid pages, reconciled
 * against current `RaidType` naming and verified current backend mechanics:
 * `Raid.corrupted_location_ids`/`raid_boss_location_id` (corrupted
 * locations/boss location are per-Raid data), `Raid.item_specialty_reward_type`
 * matched against the current `ItemSpecialtyType` enum (Pirate Lord Leather,
 * Corrupted Ice, Delusional Silver, Labyrinth Cloth all still exist),
 * `RaidBossRewardHandler::handleWhenRaidBossIsKilled()` (the character who
 * lands the killing blow receives a Raid-specific Ancient Artifact via
 * `Raid.artifact_item_id`, and some participating characters receive a
 * bonus gear piece from the Raid's specialty item type), and
 * `Quest.raid_id`/`Quest.unlocks_feature` (quests are tied to a specific
 * Raid and can unlock a `FeatureType`). `the-smugglers-are-back-raid` in 1.0
 * is the same raid as current `PIRATE_LORD` — its boss is the Pirate Lord's
 * son. Delusional Silver is also a current Delusional Memories plane
 * location reward independent of the Raid, so it is described here as the
 * Raid's own gear reward, not implied to be exclusive to the Raid.
 *
 * The quest-unlock claims below (Cosmetic Text, Cosmetic Name Tags, Race
 * Change, extra Sets, Extended Backpack) are verified per-Raid by the
 * current quest import data in `resources/data-imports/Quests/quests.xlsx`
 * against the current `App\Game\Core\Values\FeatureType` mapping.
 */
const RAID_LORE: Record<RaidType, RaidLore> = {
  [RaidType.PIRATE_LORD]: {
    tagline: "The Pirate Lord's son rises from Smugglers Port's bloody past",
    story:
      "Smugglers Port was once ruled by pirates until the merchants took over — now the Pirate Lord's son has returned seeking retribution, and dragged the port's war back into the light.",
    cards: [
      {
        key: 'the_pirate_lords_son',
        aria_label: "Toggle details for The Pirate Lord's Son",
        icon_class: 'ra ra-anchor',
        title: "The Pirate Lord's Son",
        front_body:
          "This Raid corrupts locations on the Surface map. One of them holds the Raid boss: the Pirate Lord's son, returning for retribution against the merchants who took Smugglers Port.",
        back_body:
          "Fight through the corrupted Surface locations named in the Raid's announcement, then take your daily attempts against the Pirate Lord's son at his location.",
      },
      {
        key: 'pirate_lord_leather',
        aria_label: 'Toggle details for Pirate Lord Leather',
        icon_class: 'ra ra-relic-blade',
        title: 'Pirate Lord Leather & Ancient Artifact',
        front_body:
          "Landing the killing blow on the Pirate Lord's son rewards you with a unique Ancient Artifact tied to this Raid.",
        back_body:
          "Characters who fought the boss can also receive a Pirate Lord Leather gear piece, this Raid's specialty item reward, with no enchantments so you can build it your own way.",
      },
      {
        key: 'cosmetic_text_unlock',
        aria_label: 'Toggle details for Cosmetic Text Unlock',
        icon_class: 'ra ra-quill-ink',
        title: 'Raid Quests',
        front_body:
          'This Raid has its own quests, separate from the corrupted-location fighting and boss attempts.',
        back_body:
          "This Raid's quest line can unlock Cosmetic Text, letting you customize how your character's name displays.",
      },
    ],
    faq: [
      {
        question: 'Where do I find this Raid?',
        answer: (
          <>
            On the Surface map. Corrupted locations and the Pirate Lord's son
            himself are called out by name in the Raid's announcement.
          </>
        ),
      },
      {
        question: "What do I get for defeating the Pirate Lord's son?",
        answer: (
          <>
            The character who lands the killing blow receives a unique Ancient
            Artifact. Characters who fought the boss can also receive a Pirate
            Lord Leather gear piece.
          </>
        ),
      },
    ],
  },
  [RaidType.ICE_QUEEN]: {
    tagline: 'A mother trapped in her own pain',
    story:
      'The Ice Queen is a grieving mother, kept trapped in her own memories by her pain. Her story ties into the Winter Event on the Ice Plane.',
    cards: [
      {
        key: 'the_grieving_queen',
        aria_label: 'Toggle details for The Grieving Queen',
        icon_class: 'ra ra-queen-crown',
        title: 'The Grieving Queen',
        front_body:
          'This Raid corrupts locations on The Ice Plane. One of them holds the Raid boss: the Ice Queen, trapped by her own grief.',
        back_body:
          "Fight through the corrupted Ice Plane locations named in the Raid's announcement, then take your daily attempts against the Ice Queen at her location.",
      },
      {
        key: 'corrupted_ice',
        aria_label: 'Toggle details for Corrupted Ice',
        icon_class: 'ra ra-relic-blade',
        title: 'Corrupted Ice & Ancient Artifact',
        front_body:
          'Landing the killing blow on the Ice Queen rewards you with a unique Ancient Artifact tied to this Raid.',
        back_body:
          "Characters who fought the boss can also receive a Corrupted Ice gear piece, this Raid's specialty item reward, with no enchantments so you can build it your own way.",
      },
      {
        key: 'cosmetic_name_tag_unlock',
        aria_label: 'Toggle details for Cosmetic Name Tag Unlock',
        icon_class: 'ra ra-scroll-unfurled',
        title: 'Raid Quests',
        front_body:
          'This Raid has its own quests, separate from the corrupted-location fighting and boss attempts.',
        back_body:
          "This Raid's quest line can unlock a Cosmetic Name Tag, a badge that shows next to your name in public chat.",
      },
    ],
    faq: [
      {
        question: 'Where do I find this Raid?',
        answer: (
          <>
            On The Ice Plane, alongside the Winter Event. Corrupted locations
            and the Ice Queen herself are called out by name in the Raid's
            announcement.
          </>
        ),
      },
      {
        question: 'What do I get for defeating the Ice Queen?',
        answer: (
          <>
            The character who lands the killing blow receives a unique Ancient
            Artifact. Characters who fought the boss can also receive a
            Corrupted Ice gear piece.
          </>
        ),
      },
    ],
  },
  [RaidType.FROZEN_KING]: {
    tagline: "A father's rage awaits",
    story:
      "The Frozen King grieves his wife and his son, his cries carried on the icy wind. His story continues the Ice Plane's Winter Event.",
    cards: [
      {
        key: 'a_fathers_grief',
        aria_label: "Toggle details for A Father's Grief",
        icon_class: 'ra ra-crown',
        title: "A Father's Grief",
        front_body:
          'This Raid corrupts locations on The Ice Plane. One of them holds the Raid boss: the Frozen King, grieving his lost wife and son.',
        back_body:
          "Fight through the corrupted Ice Plane locations named in the Raid's announcement, then take your daily attempts against the Frozen King at his location.",
      },
      {
        key: 'corrupted_ice',
        aria_label: 'Toggle details for Corrupted Ice',
        icon_class: 'ra ra-relic-blade',
        title: 'Corrupted Ice & Ancient Artifact',
        front_body:
          'Landing the killing blow on the Frozen King rewards you with a unique Ancient Artifact tied to this Raid.',
        back_body:
          "Characters who fought the boss can also receive a Corrupted Ice gear piece, this Raid's specialty item reward, with no enchantments so you can build it your own way.",
      },
      {
        key: 'race_change_unlock',
        aria_label: 'Toggle details for Race Change Unlock',
        icon_class: 'ra ra-bird-mask',
        title: 'Raid Quests',
        front_body:
          'This Raid has its own quests, separate from the corrupted-location fighting and boss attempts.',
        back_body:
          "This Raid's quest line can unlock a cosmetic Race Change, letting you freely switch your character's race from Settings.",
      },
    ],
    faq: [
      {
        question: 'Where do I find this Raid?',
        answer: (
          <>
            On The Ice Plane, alongside the Winter Event. Corrupted locations
            and the Frozen King himself are called out by name in the Raid's
            announcement.
          </>
        ),
      },
      {
        question: 'What do I get for defeating the Frozen King?',
        answer: (
          <>
            The character who lands the killing blow receives a unique Ancient
            Artifact. Characters who fought the boss can also receive a
            Corrupted Ice gear piece.
          </>
        ),
      },
    ],
  },
  [RaidType.JESTER_OF_TIME]: {
    tagline: 'A mad man with a corrupted sense of cruelty',
    story:
      "The Jester of Time plays with delusions on the Delusional Memories plane, flushing out more of that plane's story for those who take him on.",
    cards: [
      {
        key: 'the_mad_jester',
        aria_label: 'Toggle details for The Mad Jester',
        icon_class: 'ra ra-arcane-mask',
        title: 'The Mad Jester',
        front_body:
          'This Raid corrupts locations on the Delusional Memories plane. One of them holds the Raid boss: the Jester of Time himself.',
        back_body:
          "Fight through the corrupted Delusional Memories locations named in the Raid's announcement, then take your daily attempts against the Jester at his location.",
      },
      {
        key: 'delusional_silver',
        aria_label: 'Toggle details for Delusional Silver',
        icon_class: 'ra ra-relic-blade',
        title: 'Delusional Silver & Ancient Artifact',
        front_body:
          'Landing the killing blow on the Jester of Time rewards you with a unique Ancient Artifact tied to this Raid.',
        back_body:
          "Characters who fought the boss can also receive a Delusional Silver gear piece, this Raid's specialty item reward, with no enchantments so you can build it your own way.",
      },
      {
        key: 'extend_sets_unlock',
        aria_label: 'Toggle details for Extend Sets Unlock',
        icon_class: 'ra ra-ammo-bag',
        title: 'Raid Quests',
        front_body:
          'This Raid has its own quests, separate from the corrupted-location fighting and boss attempts.',
        back_body:
          "This Raid's quest line can unlock additional gear Sets, giving you more storage for equipment loadouts.",
      },
    ],
    faq: [
      {
        question: 'Where do I find this Raid?',
        answer: (
          <>
            On the Delusional Memories plane. Corrupted locations and the Jester
            of Time himself are called out by name in the Raid's announcement.
          </>
        ),
      },
      {
        question: 'What do I get for defeating the Jester of Time?',
        answer: (
          <>
            The character who lands the killing blow receives a unique Ancient
            Artifact. Characters who fought the boss can also receive a
            Delusional Silver gear piece.
          </>
        ),
      },
    ],
  },
  [RaidType.CORRUPTED_BISHOP]: {
    tagline: 'A mad bishop weaving his own delusions into your nightmares',
    story:
      "The Corrupted Bishop is a figure of a time long lost, using his alchemy to corrupt his own delusional memories — another thread of the Delusional Memories plane's story.",
    cards: [
      {
        key: 'the_corrupted_bishop',
        aria_label: 'Toggle details for The Corrupted Bishop',
        icon_class: 'ra ra-poison-cloud',
        title: 'The Corrupted Bishop',
        front_body:
          'This Raid corrupts locations on the Delusional Memories plane. One of them holds the Raid boss: the Corrupted Bishop, turning his own alchemy against himself.',
        back_body:
          "Fight through the corrupted Delusional Memories locations named in the Raid's announcement, then take your daily attempts against the Bishop at his location.",
      },
      {
        key: 'delusional_silver',
        aria_label: 'Toggle details for Delusional Silver',
        icon_class: 'ra ra-relic-blade',
        title: 'Delusional Silver & Ancient Artifact',
        front_body:
          'Landing the killing blow on the Corrupted Bishop rewards you with a unique Ancient Artifact tied to this Raid.',
        back_body:
          "Characters who fought the boss can also receive a Delusional Silver gear piece, this Raid's specialty item reward, with no enchantments so you can build it your own way.",
      },
      {
        key: 'extended_backpack_unlock',
        aria_label: 'Toggle details for Extended Backpack Unlock',
        icon_class: 'ra ra-three-keys',
        title: 'Raid Quests',
        front_body:
          'This Raid has its own quests, separate from the corrupted-location fighting and boss attempts.',
        back_body:
          "This Raid's quest line can unlock Extended Backpack, raising your inventory space to 150 slots.",
      },
    ],
    faq: [
      {
        question: 'Where do I find this Raid?',
        answer: (
          <>
            On the Delusional Memories plane. Corrupted locations and the
            Corrupted Bishop himself are called out by name in the Raid's
            announcement.
          </>
        ),
      },
      {
        question: 'What do I get for defeating the Corrupted Bishop?',
        answer: (
          <>
            The character who lands the killing blow receives a unique Ancient
            Artifact. Characters who fought the boss can also receive a
            Delusional Silver gear piece.
          </>
        ),
      },
    ],
  },
  [RaidType.ENRAGED_LITTLE_GIRL]: {
    tagline: "A little girl and a witch's curse",
    story:
      "A little girl, hunted by her own parents under a witch's curse — the Labyrinth Monster raid follows her story, and not everything is what it first seems.",
    cards: [
      {
        key: 'the_cursed_girl',
        aria_label: 'Toggle details for The Cursed Girl',
        icon_class: 'ra ra-broken-heart',
        title: 'The Cursed Girl',
        front_body:
          'This Raid corrupts locations on the Labyrinth map. One of them holds the Raid boss: a little girl, turned by a curse into something else.',
        back_body:
          "Fight through the corrupted Labyrinth locations named in the Raid's announcement, then take your daily attempts against her at her location.",
      },
      {
        key: 'labyrinth_cloth',
        aria_label: 'Toggle details for Labyrinth Cloth',
        icon_class: 'ra ra-relic-blade',
        title: 'Labyrinth Cloth & Ancient Artifact',
        front_body:
          'Landing the killing blow on the Raid boss rewards you with a unique Ancient Artifact tied to this Raid.',
        back_body:
          "Characters who fought the boss can also receive a Labyrinth Cloth gear piece, this Raid's specialty item reward, with no enchantments so you can build it your own way.",
      },
      {
        key: 'extended_backpack_unlock',
        aria_label: 'Toggle details for Extended Backpack Unlock',
        icon_class: 'ra ra-three-keys',
        title: 'Raid Quests',
        front_body:
          'This Raid has its own quests, separate from the corrupted-location fighting and boss attempts.',
        back_body:
          "This Raid's quest line can unlock Extended Backpack, raising your inventory space to 150 slots.",
      },
    ],
    faq: [
      {
        question: 'Where do I find this Raid?',
        answer: (
          <>
            On the Labyrinth map. Corrupted locations and the Raid boss herself
            are called out by name in the Raid's announcement.
          </>
        ),
      },
      {
        question: 'What do I get for defeating the Raid boss?',
        answer: (
          <>
            The character who lands the killing blow receives a unique Ancient
            Artifact. Characters who fought the boss can also receive a
            Labyrinth Cloth gear piece.
          </>
        ),
      },
    ],
  },
};

export const resolveRaidLore = (raidType: RaidType): RaidLore =>
  RAID_LORE[raidType];
