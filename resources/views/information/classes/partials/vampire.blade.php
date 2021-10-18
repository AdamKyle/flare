<hr />
<h4>Hints</h4>
<p class="mt-2">
  Focus on Durability and Dexterity, followed by Accuracy and Criticality.
</p>
<p>It is suggested you do not equip weapons, but instead two shields with additional durability mods on them.</p>
<p>Load your self up with <a href="/information/enchanting">life stealing affixes</a> and <a href="/information/enchanting">durability affixes</a>,
along with <a href="/information/enchanting">Class based affix</a> and <a href="/information/enchanting">Damage based affix</a></p>
<p>
  For Spells, you want one healing and one damage.
</p>
<p>If you gear up this way, you will notice that your class is OP. Your life stealing affixes will fire on your turn and at the end of the enemies turn
assuming you or the enemy are not dead (or you are not voided).</p>
<p>The attack you want is cast. This is because with cast, you you have two attempts to fire off your Thirst skill which both damages the enemy and heals you.
Once during your spell damage, once during your heal spell. If your damage spell misses or is blocked, you only have the one shot - during the healing phase of your turn.</p>
<p>Vampires can use attack and cast, cast and attack - but to get the two times chance to fire off your thirst attack your weapon and spell both have to hit or you only have
one time chance to fire it off.</p>
<p>Vampires can use defend, but it's useless as they have no healing spells that fire, so its all affix damage.</p>
<p>Vampires can attack, since they have no weapons (best to use two shields) they will attack for 2% of their dur.</p>
<p>Vampires want either Dark Dwarf or Centaur for the durability boost.</p>

<p>
  Vampires life stealing affixes do stack, but its 100% of the first ones damage and then 50% for each additional one divided by
  4 and subtracted for 100 to get your total damage. Here's an example:
</p>
<p>
  <pre>
    // Assume you have 5 suffixes for life stealing, Vampires are the only class where these affixes stack.
    // Lets assume all 5 are at 25% of the enemies durability.

    suffixTotal = 0.25 * (0.175 * 0.175 * 0.175) * 100 // => ~13%

    // Assume you have two prefixes at 25% and 2 at 10%:
    prefixTotal = 0.25 * (0.175 * 0.05 * 0.05) * 100 // => ~1%

    suffix + prefix = 14.49%
  </pre>
</p>
<p>
  The order of the affixes does not matter, as when it comes to calculation of % of damage, we rearrange the damage values on your affixes by highest to lowest.
  This way we always use the highest as your damage affix.
</p>