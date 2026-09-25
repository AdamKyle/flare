import React, { ReactNode } from 'react';

import ExternalInfoLinkProps from '../types/external-info-link-props';
import OnboardingSlideDefinition from '../types/onboarding-slide-definition';

const ExternalInfoLink = ({
  href,
  children,
}: ExternalInfoLinkProps): ReactNode => (
  <a
    href={href}
    target="_blank"
    rel="noopener noreferrer"
    className="font-semibold underline"
  >
    {children}
    <i className="fas fa-external-link-alt ml-1 text-xs" aria-hidden="true" />
    <span className="sr-only"> (opens in a new tab)</span>
  </a>
);

export const buildOnboardingSlides = (): OnboardingSlideDefinition[] => [
  {
    id: 'welcome',
    title: 'Welcome to Tlessa',
    body: (
      <>
        <p>
          Planes of Tlessa is about <strong>fighting</strong> monsters to gain
          loot to take on stronger critters. The core game loop of Tlessa is
          simple: <strong>fight</strong>, <strong>craft</strong>, and{' '}
          <strong>enchant</strong> to make better gear.
        </p>
        <p>
          Tlessa also offers a variety of{' '}
          <ExternalInfoLink href="/features">features</ExternalInfoLink> and
          depth in its systems, from character advancement to kingdom management
          and more.
        </p>
        <p>
          Tlessa also offers a{' '}
          <ExternalInfoLink href="/information/home">
            comprehensive help section
          </ExternalInfoLink>{' '}
          as well as the <strong>Guide system</strong>.
        </p>
      </>
    ),
  },
  {
    id: 'fighting',
    title: 'Fighting',
    body: (
      <>
        <p>
          Fighting is done by selecting a monster and clicking attack to
          initiate the battle.
        </p>
        <p>
          From there you can select one of five attack types: Attack, Cast, Cast
          and Attack, Attack and Cast, or Defend.
        </p>
        <p>
          When you are new, use Attack — this attacks with your weapons. Later
          on you can change your attack type based on your class; for example,
          Heretics will eventually want one of the Cast attacks: Cast, Cast and
          Attack, or Attack and Cast.
        </p>
        <p>
          You can learn more about the various attack types{' '}
          <ExternalInfoLink href="/information/combat">here</ExternalInfoLink>.
        </p>
      </>
    ),
  },
  {
    id: 'crafting-enchanting',
    title: 'Crafting and Enchanting',
    body: (
      <>
        <p>
          While fighting, you will find randomly enchanted gear. As you
          progress, you will want to craft your own gear, starting with shop
          gear and advancing beyond what the shop sells.
        </p>
        <p>
          From there, enchant your gear with specific enchantments, which you
          can see{' '}
          <ExternalInfoLink href="/information/enchanting">
            here
          </ExternalInfoLink>
          . As you progress through these systems, you will be able to take on
          stronger creatures, gaining more rewards, gold, and other currencies
          for mid and end-game crafting.
        </p>
      </>
    ),
  },
  {
    id: 'quests',
    title: 'Quests to Unlock Progression',
    body: (
      <>
        <p>
          Tlessa does not lock features behind cash shops. Players can earn all
          game features at their own pace, based on their play style.
        </p>
        <p>
          Tlessa offers{' '}
          <ExternalInfoLink href="/information/quests">quests</ExternalInfoLink>
          , which advance the main storyline and unlock features like leveling
          beyond 1,000, accessing different planes, walking on water or liquid
          surfaces, and more.
        </p>
      </>
    ),
  },
  {
    id: 'events',
    title: 'Raids, Temporary Planes, and Weekly Events',
    body: (
      <>
        <p>
          Tlessa offers players a variety of activities, including events that
          unlock new planes of existence. These activities are available for all
          levels, providing opportunities for epic loot and quests that advance
          the game&apos;s story.
        </p>
        <p>
          Don&apos;t worry about missing out — these events repeat regularly,
          and you can always see what is currently running from the
          Announcements list.
        </p>
      </>
    ),
  },
  {
    id: 'lets-get-going',
    title: 'Into the World We Go!',
    body: (
      <>
        <p>
          There is so much to explore and do. The game might seem overwhelming
          at first, but the Guide system has you covered.
        </p>
        <p>
          The Guide walks you through requirements, story, and instructions for
          your device. Follow it, chat with other players, ask questions, and
          share feedback. Your feedback shapes where Tlessa goes next.
        </p>
        <p>Let&apos;s go, adventurer!</p>
      </>
    ),
  },
];
