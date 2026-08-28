export enum GameMapImageReplacementCopy {
  Warning = 'Replacing the image changes the terrain beneath existing coordinates. Map tiles and terrain data will be regenerated. Locations, NPCs, player-owned kingdoms, and NPC-owned kingdoms remain at their existing coordinates. Review the regenerated map and manually relocate any entities positioned on inaccessible terrain.',
  Acknowledgement = 'I understand the terrain will be regenerated and I must review and manually relocate affected entities.',
  ClearReplacement = 'Clear replacement image',
}
