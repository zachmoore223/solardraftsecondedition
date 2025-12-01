<?php

declare(strict_types=1);

namespace Bga\Games\SolarDraftSecondEdition\States;

use Bga\GameFramework\StateType;
use Bga\GameFramework\States\GameState;
use Bga\GameFramework\States\PossibleAction;
use Bga\GameFramework\UserException;
use Bga\Games\SolarDraftSecondEdition\Game;

class MoonPlacement extends GameState
{
    function __construct(
        protected Game $game,
    ) {
        parent::__construct(
            $game,
            id: 11, // Choose an unused state ID
            type: StateType::ACTIVE_PLAYER,
            description: clienttranslate('${actplayer} must select a planet for their moon'),
            descriptionMyTurn: clienttranslate('${you} must select a planet for your moon'),
        );
    }

    public function getArgs(): array
    {
        $pending_moon_card_id = $this->game->getGameStateValue('pending_moon_card_id');
        
        return [
            'pending_moon_card_id' => $pending_moon_card_id
        ];
    }

    #[PossibleAction]
    public function actPlaceMoon(int $card_id, int $target_planet_id, int $activePlayerId)
    {
        // Get the moon card
        $card = $this->game->cards->getCard($card_id);
        
        if ($card['type'] !== 'moon') {
            throw new UserException("This card is not a moon");
        }

        // Validate that the target planet exists and belongs to this player
        $targetPlanet = $this->game->getObjectFromDB("
            SELECT card_id
            FROM `card`
            WHERE card_id = $target_planet_id
            AND card_location = 'tableau'
            AND card_location_arg = $activePlayerId
            AND card_type = 'planet'
        ");

        if (!$targetPlanet) {
            throw new UserException("Invalid planet selected");
        }

        // Enrich the card
        $card = $this->game->enrichCard($card);

        // Move card to tableau
        $this->game->cards->moveCard($card_id, 'tableau', $activePlayerId);

        // Set parent info
        $parent_id = (int)$target_planet_id;
        $parent_slot = (int) $this->game->getUniqueValueFromDB("
            SELECT COUNT(*)
            FROM `card`
            WHERE parent_id = $parent_id
            AND card_type = 'moon'
        ");

        $this->game->DbQuery("
            UPDATE `card`
            SET parent_id = $parent_id,
                parent_slot = $parent_slot
            WHERE card_id = " . (int)$card['id']
        );

        $card['parent_id'] = $parent_id;
        $card['parent_slot'] = $parent_slot;

        // Update moon counter
        $this->game->moon_count->inc($activePlayerId, 1);
        $newValue = $this->game->moon_count->get($activePlayerId);

        // Update ring counter if applicable
        $newRingCount = 0;
        if ($card['rings'] > 0) {
            $this->game->ring_count->inc($activePlayerId, $card['rings']);
            $newRingCount = $this->game->ring_count->get($activePlayerId);
        }

        // Notify all players
        $this->notify->all(
            'cardPlayed',
            '${player_name} plays ${cardName} on a planet.',
            [
                'player_id' => $activePlayerId,
                'player_name' => $this->game->getPlayerNameById($activePlayerId),
                'cardName' => $this->game->getCardName($card),
                'card' => $card,
                'newValue' => $newValue,
                'counter' => 'moon',
                'newRingCount' => $newRingCount,
                'target_planet_id' => $target_planet_id
            ]
        );

        return PlayerTurn::class;
    }

    #[PossibleAction]
    public function actCancelMoonPlacement(int $activePlayerId)
    {
        // Return the moon card back to hand
        $pending_moon_card_id = $this->game->getGameStateValue('pending_moon_card_id');
        
        // Card should still be in hand, so just transition back
        return PlayerTurn::class;
    }

    /**
     * Zombie player behavior - automatically cancel moon placement
     */
    public function zombie(int $playerId)
    {
        // If a zombie (disconnected) player is in moon placement state,
        // just cancel and return to normal turn
        return $this->actCancelMoonPlacement($playerId);
    }
}