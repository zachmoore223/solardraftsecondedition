<?php

declare(strict_types=1);

namespace Bga\Games\SolarDraftSecondEdition\States;

use Bga\GameFramework\StateType;
use Bga\GameFramework\States\GameState;
use Bga\GameFramework\States\PossibleAction;
use Bga\GameFramework\UserException;
use Bga\Games\SolarDraftSecondEdition\Game;

class PlayerTurn extends GameState
{
    function __construct(
        protected Game $game,
    ) {
        parent::__construct(
            $game,
            id: 10,
            type: StateType::ACTIVE_PLAYER,
            description: clienttranslate('${actplayer} must take an action'),
            descriptionMyTurn: clienttranslate('${you} must take an action'),
        );
    }

    /**
     * Called when entering PlayerTurn state - grant starting action only once per turn
     */
    public function onEnteringState(int $activePlayerId): void
    {
        // Clear Shell Star flag at the start of each turn (unless it's still active for this player)
        // Shell Star should only be active during the turn it's used, not persist across turns
        $shellStarActive = $this->game->getGameStateValue('shell_star_active');
        if ($shellStarActive != $activePlayerId && $shellStarActive > 0) {
            // Clear Shell Star if it was set for a different player
            $this->game->setGameStateValue('shell_star_active', 0);
            // Also clear the 999 play_actions if it exists
            $playActions = $this->game->play_actions->get($activePlayerId);
            if ($playActions > 100) {
                $this->game->play_actions->set($activePlayerId, 0);
            }
        }
        
        // Only grant starting action if this is a NEW turn (player changed)
        // Check if this is the same player as last turn
        $lastTurnPlayer = $this->game->getGameStateValue('last_turn_player');
        
        // If player changed, this is a new turn - grant starting action
        if ($lastTurnPlayer != $activePlayerId) {
            // Update the last turn player
            $this->game->setGameStateValue('last_turn_player', $activePlayerId);
            
            // Grant 1 open action at the start of the turn (only if they have no actions)
            $totalActions = $this->getTotalActions($activePlayerId);
            if ($totalActions == 0) {
                // Use set with an empty notification message to ensure the counter updates on client
                $this->game->open_actions->set($activePlayerId, 1, new \Bga\GameFramework\NotificationMessage(''));
            }
        }
        // If same player, don't grant action - they're returning to PlayerTurn after an action
    }

    public function stMoonPlacement()
    {
        // This is called when entering the state
        // You can set any state variables here if needed
    }

    /**
     * Game state arguments, example content.
     *
     * This method returns some additional information that is very specific to the `PlayerTurn` game state.
     */
    public function getArgs(): array
    {
        // Get some values from the current game situation from the database.
        $activePlayerId = (int) $this->game->getActivePlayerId();
        
        // Safety check: if no active player, return default values
        if (!$activePlayerId || $activePlayerId == 0) {
            return [
                "mustPlayPlanet" => false,
                'open_actions' => 0,
                'draft_actions' => 0,
                'draw_actions' => 0,
                'play_actions' => 0,
                'total_actions' => 0,
                'solar_flare_available' => false,
                'sun_ability' => null,
                'sun_ability_available' => false,
                'description' => '',
                'descriptionMyTurn' => '',
                'available_actions' => [],
            ];
        }

        // Check if player has any planets
        $hasPlanets = (int) $this->game->getUniqueValueFromDB("
                SELECT COUNT(*)
                FROM `card`
                WHERE card_location = 'tableau'
                AND card_location_arg = $activePlayerId
                AND card_type = 'planet'
            ") > 0;

        $abilityId = $this->game->sun_ability_id->get($activePlayerId);
        $sunAbility = $this->game->getSunAbilityName($abilityId);
        
        // Build dynamic description based on available actions
        $openActions = $this->game->open_actions->get($activePlayerId);
        $draftActions = $this->game->draft_actions->get($activePlayerId);
        $drawActions = $this->game->draw_actions->get($activePlayerId);
        $playActions = $this->game->play_actions->get($activePlayerId);
        // Normalize playActions (ignore Shell Star's 999)
        if ($playActions > 100) {
            $playActions = 0;
        }
        
        $totalActions = $openActions + $draftActions + $drawActions + $playActions;
        $solarFlareAvailable = $this->game->solar_flare_used->get($activePlayerId) == 0;
        $sunAbilityAvailable = $this->game->sun_ability_used->get($activePlayerId) == 0 && $abilityId > 0;
        
        // Build list of available actions with natural language
        // Priority: specific actions first, then open action, then sun abilities, then pass
        $availableActions = [];
        
        // Add specific actions first (if they have dedicated actions)
        if ($draftActions > 0) {
            $availableActions[] = 'draft';
        }
        if ($drawActions > 0) {
            $availableActions[] = 'draw';
        }
        if ($playActions > 0) {
            $availableActions[] = 'play';
        }
        
        // Add open action if available (always show it when they have open actions)
        if ($openActions > 0) {
            $availableActions[] = 'use your open action';
        }
        
        // Add sun abilities if available
        if ($solarFlareAvailable) {
            $availableActions[] = 'use Solar Flare';
        }
        if ($sunAbilityAvailable && $sunAbility) {
            $availableActions[] = 'use ' . $sunAbility;
        }
        
        // Always add pass at the end
        $availableActions[] = 'pass';
        
        // Build description text with natural language
        if (count($availableActions) == 1) {
            $description = '${actplayer} must ${action}';
            $descriptionMyTurn = '${you} must ${action}';
        } else {
            $description = '${actplayer} must ${actionList}';
            $descriptionMyTurn = '${you} must ${actionList}';
        }
        
        return [
            "mustPlayPlanet" => !$hasPlanets,
            'open_actions' => $openActions,
            'draft_actions' => $draftActions,
            'draw_actions' => $drawActions,
            'play_actions' => $playActions,
            'total_actions' => $totalActions,
            'solar_flare_available' => $solarFlareAvailable,
            'sun_ability' => $sunAbility,
            'sun_ability_available' => $sunAbilityAvailable,
            'description' => $description,
            'descriptionMyTurn' => $descriptionMyTurn,
            'available_actions' => $availableActions,
        ];
    }

    private function getTotalActions(int $playerId): int
    {
        // Ignore Shell Star's 999 play_actions value when calculating total
        $playActions = $this->game->play_actions->get($playerId);
        if ($playActions > 100) {
            $playActions = 0; // Shell Star's 999 doesn't count as real actions
        }
        
        return $this->game->open_actions->get($playerId)
            + $this->game->draft_actions->get($playerId)
            + $this->game->draw_actions->get($playerId)
            + $playActions;
    }

    /**
     * Check if the turn should automatically end.
     * Turn auto-ends only if player has no actions AND has already used both Solar Flare and Sun Ability.
     */
    private function shouldAutoEndTurn(int $playerId): bool
    {
        $hasActions = $this->getTotalActions($playerId) > 0;
        $solarFlareUsed = $this->game->solar_flare_used->get($playerId) == 1;
        $sunAbilityUsed = $this->game->sun_ability_used->get($playerId) == 1;
        
        // Auto-end only if no actions left AND both abilities already used
        return !$hasActions && $solarFlareUsed && $sunAbilityUsed;
    }

    private function canDraft(int $playerId): bool
    {
        return $this->game->open_actions->get($playerId) > 0
            || $this->game->draft_actions->get($playerId) > 0;
    }

    private function canDraw(int $playerId): bool
    {
        return $this->game->open_actions->get($playerId) > 0
            || $this->game->draw_actions->get($playerId) > 0;
    }

    private function canPlay(int $playerId, ?string $cardType = null): bool
    {
        // Check if Shell Star is active
        $shellStarActive = $this->game->getGameStateValue('shell_star_active');
        if ($shellStarActive == $playerId) {
            // Shell Star only allows moon plays (unlimited, no action check needed)
            return $cardType === 'moon';
        }
        
        // Normal play - check if player has actions
        // For moons, we still need actions unless Shell Star is active (checked above)
        // Ignore play_actions if it's 999 (Shell Star's unlimited value)
        $playActions = $this->game->play_actions->get($playerId);
        if ($playActions > 100) {
            // This is Shell Star's 999 - don't count it for normal plays
            $playActions = 0;
        }
        
        // All cards (including moons) require actions when Shell Star is not active
        return $this->game->open_actions->get($playerId) > 0
            || $playActions > 0;
    }

    private function consumeAction(int $playerId, string $actionType)
    {
        // Check if Shell Star is active for play actions
        $shellStarActive = $this->game->getGameStateValue('shell_star_active');
        if ($actionType === 'play' && $shellStarActive == $playerId) {
            // Shell Star allows unlimited moon plays - don't consume actions
            return;
        }
        
        // Try to use specific action first, then fall back to open action
        // Ensure we actually have actions to consume
        switch ($actionType) {
            case 'draft':
                $draftActions = $this->game->draft_actions->get($playerId);
                $openActions = $this->game->open_actions->get($playerId);
                if ($draftActions > 0 && $draftActions <= 100) { // Ignore Shell Star's 999
                    $this->game->draft_actions->inc($playerId, -1);
                } elseif ($openActions > 0) {
                    $this->game->open_actions->inc($playerId, -1);
                } else {
                    throw new UserException("No actions available to consume.");
                }
                break;
            case 'draw':
                $drawActions = $this->game->draw_actions->get($playerId);
                $openActions = $this->game->open_actions->get($playerId);
                if ($drawActions > 0 && $drawActions <= 100) { // Ignore Shell Star's 999
                    $this->game->draw_actions->inc($playerId, -1);
                } elseif ($openActions > 0) {
                    $this->game->open_actions->inc($playerId, -1);
                } else {
                    throw new UserException("No actions available to consume.");
                }
                break;
            case 'play':
                $playActions = $this->game->play_actions->get($playerId);
                $openActions = $this->game->open_actions->get($playerId);
                if ($playActions > 0 && $playActions <= 100) { // Ignore Shell Star's 999
                    $this->game->play_actions->inc($playerId, -1);
                } elseif ($openActions > 0) {
                    $this->game->open_actions->inc($playerId, -1);
                } else {
                    throw new UserException("No actions available to consume.");
                }
                break;
        }
        
        // Ensure counters don't go negative
        $this->game->open_actions->set($playerId, max(0, $this->game->open_actions->get($playerId)));
        $this->game->draft_actions->set($playerId, max(0, $this->game->draft_actions->get($playerId)));
        $this->game->draw_actions->set($playerId, max(0, $this->game->draw_actions->get($playerId)));
        $playActionsFinal = $this->game->play_actions->get($playerId);
        if ($playActionsFinal <= 100) { // Only cap normal play actions, not Shell Star's 999
            $this->game->play_actions->set($playerId, max(0, $playActionsFinal));
        }
    }

    /*******************
     *   PLAY A CARD   *           
     *******************/
    #[PossibleAction]
    public function actPlayCard(int $card_id, int $activePlayerId, ?int $target_planet_id = null)
    {   
        // Get card first to check type
        $card = $this->game->cards->getCard($card_id);
        
        // Check if Shell Star is active - if so, only moons can be played
        $shellStarActive = $this->game->getGameStateValue('shell_star_active');
        if ($shellStarActive == $activePlayerId) {
            if ($card['type'] !== 'moon') {
                throw new UserException("Shell Star ability is active - you can only play MOONS.");
            }
        }
        
        // Check if player can play (pass card type to canPlay for Shell Star check)
        // This is the critical check - must have actions to play
        $openActions = $this->game->open_actions->get($activePlayerId);
        $playActions = $this->game->play_actions->get($activePlayerId);
        // Normalize playActions (ignore Shell Star's 999)
        if ($playActions > 100) {
            $playActions = 0;
        }
        
        if (!$this->canPlay($activePlayerId, $card['type'])) {
            throw new UserException("You don't have any PLAY actions available. Open: $openActions, Play: $playActions");
        }
        
        $newRingCount = 0;
        $newValue = 0;

        // Enrich before sending
        $card = $this->game->enrichCard($card);

        // Check if Shell Star is active - if so, don't consume actions for moons
        $shellStarActive = $this->game->getGameStateValue('shell_star_active');
        if (!($shellStarActive == $activePlayerId && $card['type'] === 'moon')) {
            // Consume the action FIRST (before granting new actions from the card)
            // Double-check we still have actions before consuming (race condition protection)
            if (!$this->canPlay($activePlayerId, $card['type'])) {
                throw new UserException("You don't have any PLAY actions available.");
            }
            
            // Store action counts before consumption for verification
            $openBefore = $this->game->open_actions->get($activePlayerId);
            $playBefore = $this->game->play_actions->get($activePlayerId);
            // Normalize playBefore (ignore Shell Star's 999)
            if ($playBefore > 100) {
                $playBefore = 0;
            }
            
            $this->consumeAction($activePlayerId, 'play');
            
            // Verify action was actually consumed (check immediately after consumption, before card grants actions)
            $openAfterConsume = $this->game->open_actions->get($activePlayerId);
            $playAfterConsume = $this->game->play_actions->get($activePlayerId);
            // Normalize playAfterConsume (ignore Shell Star's 999)
            if ($playAfterConsume > 100) {
                $playAfterConsume = 0;
            }
            
            // At least one counter should have decreased after consumption
            // (before card grants new actions)
            if ($openAfterConsume >= $openBefore && $playAfterConsume >= $playBefore) {
                throw new UserException("Action consumption failed - no actions were consumed. Open: $openBefore -> $openAfterConsume, Play: $playBefore -> $playAfterConsume");
            }
            
            // Final verification: ensure we actually consumed an action
            // If we had actions before and still have the same or more after, something is wrong
            $totalBefore = $openBefore + $playBefore;
            $totalAfterConsume = $openAfterConsume + $playAfterConsume;
            if ($totalAfterConsume >= $totalBefore) {
                throw new UserException("Action consumption verification failed - total actions did not decrease. Before: $totalBefore, After: $totalAfterConsume");
            }
        }

        //Add any actions granted from card to current player's action count
        // This happens AFTER consuming the action, so the granted actions are available for future use
        $this->game->getCardActions($card, $activePlayerId);

        // Get all planets currently in tableau (before adding this card)
        $planet_order = array_values(
            array_filter(
                $this->game->cards->getCardsInLocation('tableau', $activePlayerId),
                fn($c) => $c['type'] === 'planet'
            )
        );

        // This card is being added now.
        // So the index for THIS planet = count before adding it.
        $planet_index = count($planet_order);

        // Save this index on the new planet (planets only)
        if ($card['type'] === 'planet') {
            $this->game->DbQuery(
                "
                UPDATE `card`
                SET planet_order = $planet_index
                WHERE card_id = " . (int) $card['id']
            );
            $card['planet_order'] = $planet_index;
        }

        // Move card to the player's tableau
        $this->game->cards->moveCard($card_id, 'tableau', $activePlayerId);

        //---------------------------------------
        // Determine parent planet / slot ///////
        //---------------------------------------
        $parent_id = null;
        $parent_slot = null;

        // Planets never have parents
        if ($card['type'] === 'planet') {
            $parent_id = null;
            $parent_slot = null;
        }

        // If it's a moon, transition to moon placement state instead
        if ($card['type'] === 'moon') {
            // Store the moon card ID in a global variable so the next state knows which card
            $this->game->setGameStateValue('pending_moon_card_id', $card_id);
            return MoonPlacement::class;
        }

        // COMETS attach to the most recent planet
        // and cannot be placed next to another comet
        if ($card['type'] === 'comet') {

            // 1. Find latest planet
            $latestPlanet = $this->game->getObjectFromDB("
                SELECT card_id
                FROM `card`
                WHERE card_location = 'tableau'
                AND card_location_arg = $activePlayerId
                AND card_type = 'planet'
                ORDER BY planet_order DESC
                LIMIT 1
            ");

            if ($latestPlanet) {
                $parent_id = (int)$latestPlanet['card_id'];

                // 2. Count comets already attached
                $parent_slot = (int) $this->game->getUniqueValueFromDB("
                    SELECT COUNT(*)
                    FROM `card`
                    WHERE parent_id = $parent_id
                    AND card_type = 'comet'
                ");
            }
        }

        //---------------------------------------
        // Save parent info into DB
        //---------------------------------------
        $parentIdSql   = ($parent_id === null)   ? "NULL" : $parent_id;
        $parentSlotSql = ($parent_slot === null) ? "NULL" : $parent_slot;

        $this->game->DbQuery(
            "
            UPDATE `card`
            SET parent_id = $parentIdSql,
                parent_slot = $parentSlotSql
            WHERE card_id = " . (int)$card['id']
        );

        // Add them to the card being sent to UI
        $card['parent_id'] = $parent_id;
        $card['parent_slot'] = $parent_slot;

        $cardColor = $card['color'];
        $cardRings = $card['rings'];


        //Increment appropriate counter for card played
        if ($card['type'] === 'planet') {

            $cardColor = $card['color'];
            $newValue = null;
            
            // Check if this planet counts as double (Diazure, Diverde, Dirojo, Dimarron)
            // These are: 12 (Diazure), 27 (Diverde), 41 (Dirojo), 58 (Dimarron)
            $countsAsDouble = in_array($card['type_arg'], [12, 27, 41, 58]);
            $incrementAmount = $countsAsDouble ? 2 : 1;

            switch ($cardColor) {
                case 'BLUE':
                    $this->game->blue_planet_count->inc($activePlayerId, $incrementAmount);
                    $newValue = $this->game->blue_planet_count->get($activePlayerId);
                    $counter = 'blue';
                    break;

                case 'GREEN':
                    $this->game->green_planet_count->inc($activePlayerId, $incrementAmount);
                    $newValue = $this->game->green_planet_count->get($activePlayerId);
                    $counter = 'green';
                    break;

                case 'RED':
                    $this->game->red_planet_count->inc($activePlayerId, $incrementAmount);
                    $newValue = $this->game->red_planet_count->get($activePlayerId);
                    $counter = 'red';
                    break;

                case 'TAN':
                    $this->game->tan_planet_count->inc($activePlayerId, $incrementAmount);
                    $newValue = $this->game->tan_planet_count->get($activePlayerId);
                    $counter = 'tan';
                    break;
            }
        }

        if ($cardRings > 0) {
            $this->game->ring_count->inc($activePlayerId, $cardRings);
            $newRingCount = $this->game->ring_count->get($activePlayerId);
        }

        if ($card['type'] === 'comet') {
            $this->game->comet_count->inc($activePlayerId, 1);
            $newValue = $this->game->comet_count->get($activePlayerId);
            $counter = 'comet';
        }

        if ($card['type'] === 'moon') {
            $this->game->moon_count->inc($activePlayerId, 1);
            $newValue = $this->game->moon_count->get($activePlayerId);
            $counter = 'moon';
        }

        // Notify all players (action was already consumed earlier, and new actions were granted)
        $this->notify->all(
            'cardPlayed',
            '${player_name} plays ${cardName}.',
            [
                'player_id' => $activePlayerId,
                'player_name' => $this->game->getPlayerNameById($activePlayerId),
                'cardName' => $this->game->getCardName($card),
                'card' => $card,
                'newValue' => $newValue,
                'counter'   => $counter,
                'newRingCount'   => $newRingCount,
                'planet_order' => $planet_order,
                'open_actions' => $this->game->open_actions->get($activePlayerId),
                'draft_actions' => $this->game->draft_actions->get($activePlayerId),
                'draw_actions' => $this->game->draw_actions->get($activePlayerId),
                'play_actions' => $this->game->play_actions->get($activePlayerId),
            ]
        );

        // After action, check if turn should auto-end
        // Only auto-end if no actions left AND solar flare already used
        if ($this->shouldAutoEndTurn($activePlayerId)) {
            return NextPlayer::class;
        } else {
            return PlayerTurn::class;
        }

    }

    /*******************
     *   DRAFT A CARD  *           
     *******************/
    #[PossibleAction]
    public function actDraftCard(int $card_id, int $row, int $slot, int $activePlayerId)
    {

        if (!$this->canDraft($activePlayerId)) {
            throw new UserException("You don't have any DRAFT actions available.");
        }

        $deckTop = $this->game->cards->getCardOnTop(Game::LOCATION_DECK);
        $this->game->cards->moveCard($deckTop['id'], 'hand', $activePlayerId);
        $card = $this->game->cards->getCard($card_id);
        // Remember where the card was (row & position)
        $row = $card['location'];         // 'solar1' or 'solar2'
        $slot = $card['location_arg'];    // 0,1,2

        // Move card from row to hand
        $this->game->cards->moveCard($card_id, 'hand', $activePlayerId);

        // Replace card from top of deck to the proper solar row & slot #
        $this->game->cards->moveCard($deckTop['id'], $row, $slot);

        // Consume the action
        $this->consumeAction($activePlayerId, 'draft');

        $this->notify->all("draft", clienttranslate('${player_name} DRAFTS ${cardName}'), [
            'player_id' => $activePlayerId,
            'player_name' => $this->game->getPlayerNameById($activePlayerId),
            'card' => $this->game->enrichCard($card),
            "cardName" => $this->game->getCardName($card),
            'deckTop' => $deckTop,
            'newDeckTop' => $this->game->cards->getCardOnTop(Game::LOCATION_DECK),
            'cardsRemaining' => $this->game->cards->countCardsInLocation(Game::LOCATION_DECK),
            'row' => $row,
            'slot' => $slot,
            'open_actions' => $this->game->open_actions->get($activePlayerId),
            'draft_actions' => $this->game->draft_actions->get($activePlayerId)
        ]);

        // After action, check if turn should auto-end
        // Only auto-end if no actions left AND solar flare already used
        if ($this->shouldAutoEndTurn($activePlayerId)) {
            return NextPlayer::class;
        } else {
            return PlayerTurn::class;
        }
    }

    /*******************
     *   DRAW A CARD   *           
     *******************/
    #[PossibleAction]
    public function actDrawCard(int $activePlayerId)
    {
        // Check if player can draw
        if (!$this->canDraw($activePlayerId)) {
            throw new UserException("You don't have any DRAW actions available.");
        }

        // Double-check we still have actions before consuming (race condition protection)
        if (!$this->canDraw($activePlayerId)) {
            throw new UserException("You don't have any DRAW actions available.");
        }

        $deckTop = $this->game->cards->getCardOnTop(Game::LOCATION_DECK);
        $this->game->cards->moveCard($deckTop['id'], 'hand', $activePlayerId);

        // Consume the action
        $this->consumeAction($activePlayerId, 'draw');

        // Check if deck is now empty after drawing
        $cardsRemaining = $this->game->cards->countCardsInLocation(Game::LOCATION_DECK);
        $deckEmpty = ($cardsRemaining == 0);
        
        if ($deckEmpty) {
            // Mark that the deck is empty and track who drew the last card
            $this->game->setGameStateValue('deck_empty', 1);
            $this->game->setGameStateValue('last_card_drawer', $activePlayerId);
        }

        // Notify each player that current player drew a card
        $this->game->notify->all(
            'deckDraw',
            clienttranslate('${player_name} drew a card'),
            [
                'player_id' => $activePlayerId,
                'player_name' => $this->game->getPlayerNameById($activePlayerId),
                'deckTop' => $deckTop,
                'newDeckTop' => $this->game->cards->getCardOnTop(Game::LOCATION_DECK),
                'cardsRemaining' => $cardsRemaining,
                'cardsInHand' => $this->game->cards->countCardsInLocation('hand', $activePlayerId),
                'open_actions' => $this->game->open_actions->get($activePlayerId),
                'draw_actions' => $this->game->draw_actions->get($activePlayerId),
                'deckEmpty' => $deckEmpty
            ]
        );

        // Notify current player only which card they drew 
        $this->notify->player(
            $activePlayerId,
            "dealCardPrivate",
            clienttranslate('You drew ${cardName}'),
            [
                "card" => $deckTop,
                "type" => $deckTop["type"],
                "cardName" => $this->game->getCardName($deckTop)
            ]
        );


        // After action, check if turn should auto-end
        // Only auto-end if no actions left AND solar flare already used
        if ($this->shouldAutoEndTurn($activePlayerId)) {
            return NextPlayer::class;
        } else {
            return PlayerTurn::class;
        }
    }


    /*******************
     *   DEBUG END GAME (TESTING)     *           
     *******************/
    #[PossibleAction]
    public function actDebugEndGame(int $activePlayerId)
    {
        // Debug action to trigger end game for testing purposes
        // This will transition directly to EndScore state
        return EndScore::class;
    }

    /*******************
     *   PASS TURN     *           
     *******************/
    #[PossibleAction]
    public function actPass(int $activePlayerId)
    {   
        // Clear all actions
        $this->game->open_actions->set($activePlayerId, 0);
        $this->game->draft_actions->set($activePlayerId, 0);
        $this->game->draw_actions->set($activePlayerId, 0);
        $this->game->play_actions->set($activePlayerId, 0);

        // Notify all players about the choice to pass.
        $this->notify->all("pass", clienttranslate('${player_name} passes'), [
            "player_id" => $activePlayerId,
            "player_name" => $this->game->getPlayerNameById($activePlayerId), // remove this line if you uncomment notification decorator
        ]);



        // at the end of the action, move to the next state
        return NextPlayer::class;
    }

    /*******************
     *   SOLAR FLARE   *           
     *******************/
    #[PossibleAction]
    public function actSolarFlare(int $row, int $activePlayerId)
    {
        // Check if player has already used their Solar Flare
        if ($this->game->solar_flare_used->get($activePlayerId) == 1) {
            throw new UserException("You have already used your Solar Flare ability this game.");
        }

        // Validate row number (must be 1 or 2)
        if ($row != 1 && $row != 2) {
            throw new UserException("Invalid solar row selected.");
        }

        // Determine which location to use
        $solarRowLocation = ($row == 1) ? Game::LOCATION_SOLARROW1 : Game::LOCATION_SOLARROW2;
        $rowName = ($row == 1) ? 'Solar Row 1' : 'Solar Row 2';

        // Get all cards currently in the selected solar row
        $cardsInRow = $this->game->cards->getCardsInLocation($solarRowLocation);
        
        // Move all cards from the row to discard
        foreach ($cardsInRow as $card) {
            $this->game->cards->moveCard($card['id'], Game::LOCATION_DISCARD, 3);
        }

        // Fill the row with new cards from the deck
        $newCards = [];
        for ($slot = 0; $slot < 3; $slot++) {
            $newCard = $this->game->cards->pickCardForLocation('deck', $solarRowLocation, $slot);
            if ($newCard) {
                $newCards[] = $this->game->enrichCard($newCard);
            }
        }

        // Mark Solar Flare as used
        $this->game->solar_flare_used->set($activePlayerId, 1);

        // Notify all players
        $this->notify->all(
            'solarFlare',
            clienttranslate('${player_name} uses Solar Flare on ${rowName}'),
            [
                'player_id' => $activePlayerId,
                'player_name' => $this->game->getPlayerNameById($activePlayerId),
                'row' => $row,
                'rowName' => $rowName,
                'discardedCards' => $this->game->enrichCards(array_values($cardsInRow)),
                'newCards' => $newCards,
                'cardsRemaining' => $this->game->cards->countCardsInLocation('deck'),
                'cardsInDiscard' => $this->game->cards->countCardsInLocation(Game::LOCATION_DISCARD),
            ]
        );

        // Solar Flare is a bonus action, doesn't consume regular actions, doesn't end turn
        // But check if we should auto-end now (no actions left and solar flare just used)
        if ($this->shouldAutoEndTurn($activePlayerId)) {
            return NextPlayer::class;
        } else {
            return PlayerTurn::class;
        }
    }

    /*******************
     *   SUN ABILITY   *           
     *******************/
    #[PossibleAction]
    public function actSunAbility(int $activePlayerId, ?array $args = null)
    {
        // Check if player has already used their Sun Ability
        if ($this->game->sun_ability_used->get($activePlayerId) == 1) {
            throw new UserException("You have already used your Sun Ability this game.");
        }

        // Get player's sun ability
        $abilityId = $this->game->sun_ability_id->get($activePlayerId);
        $sunAbility = $this->game->getSunAbilityName($abilityId);
        
        if (!$sunAbility || $abilityId == 0) {
            throw new UserException("You do not have a Sun Ability assigned.");
        }

        // Route to the specific ability implementation
        switch ($sunAbility) {
            case 'Shell Star':
                return $this->actShellStar($activePlayerId, $args);
            case 'Binary Star':
                return $this->actBinaryStar($activePlayerId, $args);
            case 'Quasar':
                return $this->actQuasar($activePlayerId, $args);
            case 'Supernova':
                return $this->actSupernova($activePlayerId, $args);
            case 'Neutron Star':
                return $this->actNeutronStar($activePlayerId, $args);
            case 'Ternary Star':
                return $this->actTernaryStar($activePlayerId, $args);
            case 'Pulsar':
                return $this->actPulsar($activePlayerId, $args);
            case 'Super Star':
                return $this->actSuperStar($activePlayerId, $args);
            case 'Protostar':
                return $this->actProtostar($activePlayerId, $args);
            case 'Red Dwarf':
                return $this->actRedDwarf($activePlayerId, $args);
            default:
                throw new UserException("Unknown Sun Ability: " . $sunAbility);
        }
    }

    private function actShellStar(int $playerId, ?array $args): string
    {
        // PLAY any number of MOONS
        // Grant unlimited moon play actions (or a very large number)
        $this->game->play_actions->set($playerId, 999);
        // Set a flag so we know only moons can be played
        $this->game->setGameStateValue('shell_star_active', $playerId);
        
        $this->game->sun_ability_used->set($playerId, 1);
        
        $this->notify->all('sunAbilityUsed', clienttranslate('${player_name} uses ${ability}: PLAY any number of MOONS'), [
            'player_id' => $playerId,
            'player_name' => $this->game->getPlayerNameById($playerId),
            'ability' => 'Shell Star',
        ]);
        
        return PlayerTurn::class;
    }

    private function actBinaryStar(int $playerId, ?array $args): string
    {
        // DRAFT a card then PLAY a card
        $this->game->grantDraftAction($playerId, 1);
        $this->game->grantPlayAction($playerId, 1);
        
        $this->game->sun_ability_used->set($playerId, 1);
        
        $this->notify->all('sunAbilityUsed', clienttranslate('${player_name} uses ${ability}: DRAFT a card then PLAY a card'), [
            'player_id' => $playerId,
            'player_name' => $this->game->getPlayerNameById($playerId),
            'ability' => 'Binary Star',
            'open_actions' => $this->game->open_actions->get($playerId),
            'draft_actions' => $this->game->draft_actions->get($playerId),
            'play_actions' => $this->game->play_actions->get($playerId),
        ]);
        
        return PlayerTurn::class;
    }

    private function actQuasar(int $playerId, ?array $args): string
    {
        // DRAFT 2 cards
        $this->game->grantDraftAction($playerId, 2);
        
        $this->game->sun_ability_used->set($playerId, 1);
        
        $this->notify->all('sunAbilityUsed', clienttranslate('${player_name} uses ${ability}: DRAFT 2 cards'), [
            'player_id' => $playerId,
            'player_name' => $this->game->getPlayerNameById($playerId),
            'ability' => 'Quasar',
            'open_actions' => $this->game->open_actions->get($playerId),
            'draft_actions' => $this->game->draft_actions->get($playerId),
        ]);
        
        return PlayerTurn::class;
    }

    private function actSupernova(int $playerId, ?array $args): string
    {
        // Discard three cards to DRAFT a ROW of cards and then PLAY up to 3 cards
        // This is a multi-step ability - for now, we'll grant the actions
        // TODO: Implement card selection for discard and row selection
        
        if (!$args || !isset($args['discarded_card_ids']) || count($args['discarded_card_ids']) != 3) {
            throw new UserException("You must discard exactly 3 cards from your hand to use Supernova.");
        }
        
        if (!isset($args['row']) || ($args['row'] != 1 && $args['row'] != 2)) {
            throw new UserException("You must select a solar row (1 or 2) to draft.");
        }
        
        // Verify all cards are in player's hand
        $handCards = $this->game->cards->getCardsInLocation('hand', $playerId);
        $handCardIds = array_map(fn($c) => $c['id'], $handCards);
        
        foreach ($args['discarded_card_ids'] as $cardId) {
            if (!in_array($cardId, $handCardIds)) {
                throw new UserException("One or more selected cards are not in your hand.");
            }
        }
        
        // Discard the cards
        foreach ($args['discarded_card_ids'] as $cardId) {
            $this->game->cards->moveCard($cardId, Game::LOCATION_DISCARD, 3);
        }
        
        // Draft the entire row
        $solarRowLocation = ($args['row'] == 1) ? Game::LOCATION_SOLARROW1 : Game::LOCATION_SOLARROW2;
        $draftedCards = [];
        for ($slot = 0; $slot < 3; $slot++) {
            $card = $this->game->cards->getCardsInLocation($solarRowLocation, $slot);
            if (!empty($card)) {
                $card = array_values($card)[0];
                $this->game->cards->moveCard($card['id'], 'hand', $playerId);
                $draftedCards[] = $this->game->enrichCard($card);
            }
        }
        
        // Grant 3 play actions
        $this->game->grantPlayAction($playerId, 3);
        
        $this->game->sun_ability_used->set($playerId, 1);
        
        $this->notify->all('sunAbilityUsed', clienttranslate('${player_name} uses ${ability}: Discards 3 cards, drafts a row, and can PLAY up to 3 cards'), [
            'player_id' => $playerId,
            'player_name' => $this->game->getPlayerNameById($playerId),
            'ability' => 'Supernova',
            'row' => $args['row'],
            'draftedCards' => $draftedCards,
            'play_actions' => $this->game->play_actions->get($playerId),
        ]);
        
        return PlayerTurn::class;
    }

    private function actNeutronStar(int $playerId, ?array $args): string
    {
        // PLAY 2 cards
        $this->game->grantPlayAction($playerId, 2);
        
        $this->game->sun_ability_used->set($playerId, 1);
        
        $this->notify->all('sunAbilityUsed', clienttranslate('${player_name} uses ${ability}: PLAY 2 cards'), [
            'player_id' => $playerId,
            'player_name' => $this->game->getPlayerNameById($playerId),
            'ability' => 'Neutron Star',
            'play_actions' => $this->game->play_actions->get($playerId),
        ]);
        
        return PlayerTurn::class;
    }

    private function actTernaryStar(int $playerId, ?array $args): string
    {
        // Gain an additional ACTION and then DRAW 2 cards
        $this->game->open_actions->inc($playerId, 1);
        $this->game->grantDrawAction($playerId, 2);
        
        $this->game->sun_ability_used->set($playerId, 1);
        
        $this->notify->all('sunAbilityUsed', clienttranslate('${player_name} uses ${ability}: Gains an ACTION and can DRAW 2 cards'), [
            'player_id' => $playerId,
            'player_name' => $this->game->getPlayerNameById($playerId),
            'ability' => 'Ternary Star',
            'open_actions' => $this->game->open_actions->get($playerId),
            'draw_actions' => $this->game->draw_actions->get($playerId),
        ]);
        
        return PlayerTurn::class;
    }

    private function actPulsar(int $playerId, ?array $args): string
    {
        // Reuse the ability of one of your comets
        if (!$args || !isset($args['comet_card_id'])) {
            throw new UserException("You must select a comet to reuse its ability.");
        }
        
        $cometCard = $this->game->cards->getCard($args['comet_card_id']);
        if (!$cometCard || $cometCard['type'] != 'comet') {
            throw new UserException("Invalid comet card selected.");
        }
        
        // Verify the comet is in the player's tableau
        if ($cometCard['location'] != 'tableau' || $cometCard['location_arg'] != $playerId) {
            throw new UserException("The selected comet must be in your tableau.");
        }
        
        // Re-execute the comet's ability
        $cometCard = $this->game->enrichCard($cometCard);
        $this->game->getCardActions($cometCard, $playerId);
        
        $this->game->sun_ability_used->set($playerId, 1);
        
        $this->notify->all('sunAbilityUsed', clienttranslate('${player_name} uses ${ability}: Reuses the ability of ${cometName}'), [
            'player_id' => $playerId,
            'player_name' => $this->game->getPlayerNameById($playerId),
            'ability' => 'Pulsar',
            'cometName' => $this->game->getCardName($cometCard),
            'open_actions' => $this->game->open_actions->get($playerId),
            'draft_actions' => $this->game->draft_actions->get($playerId),
            'draw_actions' => $this->game->draw_actions->get($playerId),
            'play_actions' => $this->game->play_actions->get($playerId),
        ]);
        
        return PlayerTurn::class;
    }

    private function actSuperStar(int $playerId, ?array $args): string
    {
        // Look at the TOP 4 cards of the Solar Deck. Put 2 into your hand, place the rest on the bottom.
        if (!$args || !isset($args['selected_card_ids']) || count($args['selected_card_ids']) != 2) {
            throw new UserException("You must select exactly 2 cards to add to your hand.");
        }
        
        // Get top 4 cards from deck
        $topCards = $this->game->cards->getCardsOnTop(4, 'deck');
        if (count($topCards) < 4) {
            throw new UserException("There are not enough cards in the deck.");
        }
        
        $topCardIds = array_map(fn($c) => $c['id'], $topCards);
        
        // Verify selected cards are in the top 4
        foreach ($args['selected_card_ids'] as $cardId) {
            if (!in_array($cardId, $topCardIds)) {
                throw new UserException("Selected cards must be from the top 4 cards of the deck.");
            }
        }
        
        // Move selected cards to hand
        foreach ($args['selected_card_ids'] as $cardId) {
            $this->game->cards->moveCard($cardId, 'hand', $playerId);
        }
        
        // Move remaining cards to bottom of deck
        foreach ($topCards as $card) {
            if (!in_array($card['id'], $args['selected_card_ids'])) {
                $this->game->cards->insertCardOnExtremePosition($card['id'], 'deck', false);
            }
        }
        
        $this->game->sun_ability_used->set($playerId, 1);
        
        $this->notify->all('sunAbilityUsed', clienttranslate('${player_name} uses ${ability}: Looks at top 4 cards, takes 2 into hand, places rest on bottom'), [
            'player_id' => $playerId,
            'player_name' => $this->game->getPlayerNameById($playerId),
            'ability' => 'Super Star',
            'cardsInHand' => count($this->game->cards->getCardsInLocation('hand', $playerId)),
        ]);
        
        return PlayerTurn::class;
    }

    private function actProtostar(int $playerId, ?array $args): string
    {
        // COPY the sun ability of the player to your left or right
        if (!$args || !isset($args['target_player_id'])) {
            throw new UserException("You must select a player (left or right) to copy their sun ability.");
        }
        
        $targetPlayerId = (int)$args['target_player_id'];
        $targetAbilityId = $this->game->sun_ability_id->get($targetPlayerId);
        $targetAbility = $this->game->getSunAbilityName($targetAbilityId);
        
        if (!$targetAbility || $targetAbilityId == 0) {
            throw new UserException("The selected player does not have a sun ability.");
        }
        
        // Execute the copied ability by temporarily setting the player's ability ID and calling it
        // Save original ability ID
        $originalAbilityId = $this->game->sun_ability_id->get($playerId);
        
        // Temporarily set to target ability ID and execute
        $this->game->sun_ability_id->set($playerId, $targetAbilityId);
        
        // Execute the ability (but don't mark as used for the original player, just for this player)
        $tempArgs = $args['ability_args'] ?? null;
        $result = $this->actSunAbility($playerId, $tempArgs);
        
        // Restore original ability ID
        $this->game->sun_ability_id->set($playerId, $originalAbilityId);
        
        // Mark as used for THIS player (not the target)
        $this->game->sun_ability_used->set($playerId, 1);
        
        $this->notify->all('sunAbilityUsed', clienttranslate('${player_name} uses ${ability}: Copies the sun ability of ${target_name}'), [
            'player_id' => $playerId,
            'player_name' => $this->game->getPlayerNameById($playerId),
            'ability' => 'Protostar',
            'target_name' => $this->game->getPlayerNameById($targetPlayerId),
            'copied_ability' => $targetAbility,
        ]);
        
        return $result;
    }

    private function actRedDwarf(int $playerId, ?array $args): string
    {
        // Put the TOP 3 cards of the DISCARD PILE into your hand
        $discardCards = $this->game->cards->getCardsInLocation(Game::LOCATION_DISCARD);
        
        if (count($discardCards) < 3) {
            throw new UserException("There are not enough cards in the discard pile.");
        }
        
        // Get top 3 cards (assuming they're ordered by location_arg, highest = top)
        usort($discardCards, fn($a, $b) => $b['location_arg'] - $a['location_arg']);
        $top3Cards = array_slice($discardCards, 0, 3);
        
        foreach ($top3Cards as $card) {
            $this->game->cards->moveCard($card['id'], 'hand', $playerId);
        }
        
        $this->game->sun_ability_used->set($playerId, 1);
        
        $this->notify->all('sunAbilityUsed', clienttranslate('${player_name} uses ${ability}: Takes the top 3 cards from the discard pile into hand'), [
            'player_id' => $playerId,
            'player_name' => $this->game->getPlayerNameById($playerId),
            'ability' => 'Red Dwarf',
            'cardsInHand' => count($this->game->cards->getCardsInLocation('hand', $playerId)),
            'cardsInDiscard' => count($this->game->cards->getCardsInLocation(Game::LOCATION_DISCARD)),
        ]);
        
        return PlayerTurn::class;
    }

    /**
     * This method is called each time it is the turn of a player who has quit the game (= "zombie" player).
     * You can do whatever you want in order to make sure the turn of this player ends appropriately
     * (ex: play a random card).
     * 
     * See more about Zombie Mode: https://en.doc.boardgamearena.com/Zombie_Mode
     *
     * Important: your zombie code will be called when the player leaves the game. This action is triggered
     * from the main site and propagated to the gameserver from a server, not from a browser.
     * As a consequence, there is no current player associated to this action. In your zombieTurn function,
     * you must _never_ use `getCurrentPlayerId()` or `getCurrentPlayerName()`, 
     * but use the $playerId passed in parameter and $this->game->getPlayerNameById($playerId) instead.
     */
    function zombie(int $playerId)
    {
        return $this->actPass($playerId);
    }
}
