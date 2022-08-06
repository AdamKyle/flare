<?php

namespace App\Game\Messages\Services;

use App\Admin\Events\UpdateAdminChatEvent;
use App\Flare\Models\Character;
use App\Flare\Models\Npc;
use App\Flare\Models\User;
use App\Game\Messages\Events\NPCMessageEvent;
use App\Game\Messages\Events\PrivateMessageEvent;
use App\Game\Messages\Events\ServerMessageEvent;

class PrivateMessage {

    /**
     * Send a private message.
     *
     * - Can send a private message to a character.
     * - Can send a message to a NPC.
     *
     * Will get back a server message telling the player they do not exist.
     *
     * @param string $userName
     * @param string $message
     * @return void
     */
    public function sendPrivateMessage(string $userName, string $message): void {

        $character = $this->getCharacterForSendingTo($userName);

        if (!is_null($character)) {
            $this->sendMessageToCharacter($character, $message);

            return;
        }

        $npc  = $this->getNPCForSendingTo($userName);
        $user = auth()->user();

        if (!is_null($npc)) {
            event(new NPCMessageEvent($user, $this->buildNPCMessage($npc), $npc->name));

            return;
        }

        event(new ServerMessageEvent($user, 'No Character or NPC exists for: ' . $userName));
    }

    /**
     * Get character for the message.
     *
     * @param string $userName
     * @return Character|null
     */
    protected function getCharacterForSendingTo(string $userName): ?Character {
        return Character::where('name', $userName)->first();
    }

    /**
     * Get NPC for the message.
     *
     * @param string $userName
     * @return Npc|null
     */
    protected function getNPCForSendingTo(string $userName): ?Npc {
        return Npc::where('name', $userName)->first();
    }

    /**
     * Send a message to the character.
     *
     * @param Character $character
     * @param string $message
     * @return void
     */
    protected function sendMessageToCharacter(Character $character, string $message): void {
        $user = auth()->user();

        $user->messages()->create([
            'from_user' => $user->id,
            'to_user'   => $character->user->id,
            'message'   => $message,
        ]);

        broadcast(new PrivateMessageEvent($user->refresh(), $character->user, $message));

        broadcast(new UpdateAdminChatEvent($this->getAdminUser()));
    }

    /**
     * Build the NPC Message.
     *
     * @param Npc $npc
     * @return string
     */
    protected function buildNPCMessage(Npc $npc): string {
        return 'My name is: ' . $npc->real_name . '. ' . $this->getNPCTypeMessage($npc);
    }

    /**
     * Build NPC message for type.
     *
     * Returns a default message if the NPC does not exist.
     *
     * @param Npc $npc
     * @return string
     */
    private function getNPCTypeMessage(Npc $npc): string {
        if ($npc->type()->isConjurer()) {
            return 'I hold the gates closed over the Celestials. Alas they seem to escape my grasp once a week on Wednesdays at 1pm GMT-6.
            Want to conjure your own? Click conjure under the map. Celestials can drop shards, used in Alchemy, The Queen of Hearts and Quests!
            These creatures cost Gold Dust and Gold to conjure and are stronger then current plane creatures! They even take into consideration special
            plane effects. See Help docs for more info. You can only conjure creatures for the plane you are on, all except Purgatory! Alas, I am busy now child, away with you!';
        }

        if ($npc->type()->isEnchantress()) {
            return 'Ooooh hoo hoo! My beautiful child! Come to Hell and while you are on the plane, you can open Craft/Enchant to see the new Queen option! I love gold!
            If you pay me a pretty price I can give you Basic (get through faction farming, gained by killing creatures on any plane but Purgatory (see Character Sheet -> Factions Tab or Pay her 10 Billion Gold), Medium (50 Billion Gold) and Legendary (100 Billion) uniques! I only
            Carry the best of the best child! If you are kind to me I can even re-reroll those fancy uniques and move their enchantments to your super powerful sexy gear!
            But you must come to hell child! (Complete the quest: Satan\'s Calling (Surface quest)) or you can\'t get the good stuff from me! All Uniques are randomly rolled!
            Oooh hoo hoo hoo. (The Queen Shoves her cleavage in your face and winks!)';
        }

        if ($npc->type()->isQuestHolder()) {
            return 'I am a quest holder with a story of my own, or multiple. Open your Quest tab and click on the quest you want to do. I have explicit instructions on how to finish
            the quest! Everything from items, locations and how to get those items. Sometimes I might require currencies or Faction points! When you are ready to hand in a quest, click Hand in.
            I will instantly teleport you across planes to me to hand it in! All for free. (Quests are used to gate some of Tlessa\'s feature such as Plane access, Max Level (past 1000) and other features.
            All quests can be done for free and only require time investment! Here\'s a hint for your first plane: Kill Labyrinth fiend on Surface to get: The Key of Labyrinth. Once you have this quest item, click traverse
            under the map actions, select Labyrinth and boom done! Want to walk on Water? Kill Pirate Master on Surface till the Flask of Fresh Air drops. All quest items require you level Looting Skill for them to drop. The higher the Skill bonus, the higher the chance.
            Some Quest items only drop from Special Locations (Where you can only manually fight for them to drop - theres one for each plane but Purgatory!). To get a list of Quest items: (Hover over Profile icon, click Help I\'m Stuck!, Hover over Game Systems, Click Quest items).';
        }

        if ($npc->type()->isKingdomHolder()) {
            return 'I hold onto kingdoms others have selfishly abandoned. These are yellow on the map, regardless of plane. Careful child, you can purchase population
            but if you purchase too much (that is your current population is over your max) and you cannot pay me, I will destroy your hard work!
            You can wage war on my kingdoms and take them or you can purchase them from me for the cost of 10,000 Gold x Your kingdom count. This price goes across planes!
            Your first kingdom will always be free - even if you loose all your kingdoms!';
        }

        return 'Who are you? What do you want? Go away child!';
    }

    /**
     * Get the admin user.
     *
     * @return User
     */
    protected function getAdminUser(): User {
        return User::with('roles')
                   ->whereHas('roles', function($q) {
                       $q->where('name', 'Admin');
                   })->first();
    }
}
