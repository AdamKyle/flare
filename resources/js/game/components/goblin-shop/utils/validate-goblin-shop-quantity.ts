import GoblinShopQuantityValidation from '../types/goblin-shop-quantity-validation';

export const resolveMaxAffordableQuantity = (
  goldBars: number,
  goldBarsCost: number
): number => {
  if (goldBarsCost <= 0) {
    return 0;
  }

  return Math.floor(goldBars / goldBarsCost);
};

export const validateGoblinShopQuantity = (
  quantityInput: string,
  maxAffordableQuantity: number
): GoblinShopQuantityValidation => {
  if (quantityInput.trim() === '') {
    return { quantity: null, error: null };
  }

  const parsedQuantity = Number(quantityInput);

  if (!Number.isFinite(parsedQuantity) || !Number.isInteger(parsedQuantity)) {
    return { quantity: null, error: 'Enter a whole number to buy.' };
  }

  if (parsedQuantity < 1) {
    return { quantity: null, error: 'You must buy at least one.' };
  }

  if (parsedQuantity > maxAffordableQuantity) {
    return {
      quantity: null,
      error: `You can afford at most ${maxAffordableQuantity.toLocaleString('en-US')}.`,
    };
  }

  return { quantity: parsedQuantity, error: null };
};
