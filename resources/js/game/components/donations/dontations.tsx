import React from 'react';

import DonationsProps from './types/donations-props';

import Card from 'ui/cards/card';
import ContainerWithTitle from 'ui/container/container-with-title';

const Donations = ({ on_close }: DonationsProps) => {
  const basePath: string = import.meta.env.VITE_BASE_IMAGE_URL;
  const adventurer: string = `${basePath}/donations-images/adventurer.png`;

  return (
    <ContainerWithTitle
      manageSectionVisibility={on_close}
      title="Tlessa needs your help"
    >
      <Card>
        <div className="relative w-full overflow-hidden rounded-tl-md rounded-tr-md border-1 border-b-gray-500 dark:border-gray-700">
          <img
            src={adventurer}
            alt="A lone adventurer gazing into a mysterious fantasy landscape"
            className="h-40 w-full object-cover sm:h-56 md:h-64"
          />

          <div className="pointer-events-none absolute inset-0 bg-gradient-to-t from-black/80 via-black/40 to-transparent" />

          <div className="absolute inset-0 flex items-center justify-center">
            <h4 className="rounded-md bg-black/70 px-6 py-3 text-lg font-semibold text-white shadow-lg sm:text-2xl md:text-3xl">
              Donate today
            </h4>
          </div>
        </div>

        <div className="px-4 py-6">
          <div className="prose dark:prose mx-auto my-4 text-left text-gray-800 dark:text-gray-400">
            <p>
              Tlessa needs your help. Developed by a single person, responsible
              for every aspect of the game, including new features and
              addressing issues.
            </p>
            <p>
              Tlessa is completely free, with no cash shops or pay-to-win
              options. However, without generating income, sustaining the game
              solely depends on support. If I, The Creator, lose my job, I can
              only maintain the game for a limited time.
            </p>
            <p>
              So, I&apos;m reaching out to you. If you enjoy Tlessa and want to
              ensure its survival and ongoing development, please consider
              donating any amount you can.
            </p>
            <p>
              For context, the game server costs $52 CAD per month. While I
              don&apos;t expect that level of generosity, it provides insight
              into the game&apos;s expenses.
            </p>
          </div>

          <div className="text-center text-4xl">
            <form
              action="https://www.paypal.com/donate"
              method="post"
              target="_top"
            >
              <input
                type="hidden"
                name="hosted_button_id"
                value="S2QDQHV83DUH6"
              />
              <input
                type="image"
                src="https://www.paypalobjects.com/en_US/i/btn/btn_donate_LG.gif"
                name="submit"
                title="PayPal - The safer, easier way to pay online!"
                alt="Donate with PayPal button"
              />
              <img
                alt=""
                src="https://www.paypal.com/en_CA/i/scr/pixel.gif"
                width="1"
                height="1"
              />
            </form>
          </div>

          <div className="prose dark:prose mx-auto my-4 text-left text-gray-800 dark:text-gray-400">
            <p>
              Clicking the button above will take you to a safe and secure site
              operated and owned by Paypal. You will see a giant zero. Tap or
              click the $0 to enter a custom amount.
            </p>
            <p>
              Paypal may offer the option to cover associated fees with your
              donation, but you&apos;re not obligated to click the checkbox.
            </p>
            <p>
              Importantly, Planes of Tlessa doesn&apos;t gather any information
              from Paypal, such as your email or credit card details. You also
              have the option, though not obligatory, to contribute the same
              amount monthly.
            </p>
            <p>
              Donating doesn&apos;t provide any in-game benefits like items or
              unlocked content, but earns you a heartfelt thank you from me.
            </p>
          </div>
        </div>
      </Card>
    </ContainerWithTitle>
  );
};

export default Donations;
