/**
 *------
 * BGA framework: Gregory Isabelli & Emmanuel Colin & BoardGameArena
 * SolarDraftSecondEdition implementation : © <Your name here> <Your email address here>
 *
 * This code has been produced on the BGA studio platform for use on http://boardgamearena.com.
 * See http://en.boardgamearena.com/#!doc/Studio for more information.
 * -----
 *
 * solardraftsecondedition.js
 *
 * SolarDraftSecondEdition user interface script
 *
 * In this file, you are describing the logic of your user interface, in Javascript language.
 *
 */

define([
  "dojo",
  "dojo/_base/declare",
  "ebg/core/gamegui",
  "ebg/counter",
  getLibUrl("bga-animations", "1.x"), // the lib uses bga-animations so this is required!
  getLibUrl("bga-cards", "1.x"), // bga-cards itself
], function (dojo, declare, gamegui, counter, BgaAnimations, BgaCards) {
  return declare("bgagame.solardraftsecondedition", ebg.core.gamegui, {
    constructor: function () {
      console.log("solardraftsecondedition constructor");

      // Here, you can init the global variables of your user interface
      // Example:
      // this.myGlobalValue = 0;
    },

    /*
            setup:
            
            This method must set up the game user interface according to current game situation specified
            in parameters.
            
            The method is called each time the game interface is displayed to a player, ie:
            _ when the game starts
            _ when a player refreshes the game page (F5)
            
            "gamedatas" argument contains all datas retrieved by your "getAllDatas" PHP method.
        */

    setup: function (gamedatas) {
      console.log("Starting game setup");
      const activePlayerId = this.getActivePlayerId();
      var gameArea = document.getElementById("game_play_area");
      const cardWidth = 150;
      const cardHeight = 236;
      var cardsRemaining = gamedatas.cardsRemaining;
      var cardsInDiscard = gamedatas.cardsInDiscard;
      var cardsInHand = gamedatas.cardsInHand;
      // create the animation manager, and bind it to the `game.bgaAnimationsActive()` function
      this.animationManager = new BgaAnimations.Manager({
        animationsActive: () => this.bgaAnimationsActive(),
      });

      this.cardsManager = new BgaCards.Manager({
        animationManager: this.animationManager,
        type: "card", // the "type" of our cards in css
        getId: (card) => card.id,

        // IMPORTANT: keep these, the manager relies on them
        cardWidth: cardWidth,
        cardHeight: cardHeight,

        setupFrontDiv: (card, div) => {
          const cardWidth = 150;
          const cardHeight = 236;

          const index = Number(card.type_arg) - 1;
          const col = index % 10;
          const row = Math.floor(index / 10);

          // IMPORTANT: Correct BGA asset path
          div.style.backgroundImage = `url('${g_gamethemeurl}img/cards.png')`;
          div.style.backgroundSize = `${10 * cardWidth}px ${11 * cardHeight}px`;

          div.style.backgroundPosition = `-${col * cardWidth}px -${
            row * cardHeight
          }px`;

          div.style.width = cardWidth + "px";
          div.style.height = cardHeight + "px";
        },
      });

      /*******************************
       *           GAME AREA          *
       *******************************/
      gameArea.insertAdjacentHTML(
        "beforeend",
        `
        <div id="solar-area">

            <div id="solar-grid">

                <!-- Row 1, Col 1 -->
                <div id="mysolarsystem_wrap"></div>

                <!-- Row 1, Col 2 -->
                <div id="solar-deck_wrap" class="whiteblock">
                    <b class="section-label">Solar Deck (<span id="deck-count">${cardsRemaining}</span>)</b>
                    <div id="solar-deck"></div>
                </div>

                <!-- Col 3 (spans both rows!!) -->
                <div id="solar-rows_column" class="whiteblock">
                    <div class="solar-row-block">
                        <b class="section-label">Solar Rows</b>
                        <div id="solar-row-1" class="solar-row-cards">
                            <div class="slot" id="solar1_slot0"></div>
                            <div class="slot" id="solar1_slot1"></div>
                            <div class="slot" id="solar1_slot2"></div>
                        </div>
                    </div>

                    <div class="solar-row-block">
                        <div id="solar-row-2" class="solar-row-cards">
                            <div class="slot" id="solar2_slot0"></div>
                            <div class="slot" id="solar2_slot1"></div>
                            <div class="slot" id="solar2_slot2"></div>                    
                        </div>
                    </div>
                </div>

                <!-- Row 2, Col 1 -->
                <div id="player-hand_wrap" class="whiteblock">
                    <b class="section-label">My Hand</b>
                    <div id="player-hand"></div>
                </div>

                <!-- Row 2, Col 2 --> 
                <div id="discard-pile_wrap" class="whiteblock">
                    <b class="section-label-discard">Discard Pile (<span id="deck-count">${cardsInDiscard}</span>)</b>  
                    <div id="discard-pile"></div>
                <div>

            </div>

        </div>


        `
      );

      /*******************************
       *          PLAYER HAND         *
       *******************************/
      //TO DO - clikcing on card in hand will prompt PLAY action
      this.handStock = new BgaCards.HandStock(
        this.cardsManager,
        document.getElementById("player-hand"),
        {
          selectedCardStyle: {
            outlineColor: "rgba(255, 0, 221, 12)",
          },
          fanShaped: false, // <-- turn off fanning
          cardOverlap: 2, // <-- keep cards flat
          center: false, // <-- optional: left-align
          direction: "row", // <-- optional: horizontal
        }
      );
      
      // Disable floating behavior - override watchFloatingState to prevent cards from floating to bottom
      // HandStock automatically floats to bottom when not visible - we want to disable this
      const originalWatchFloating = this.handStock.watchFloatingState;
      if (originalWatchFloating) {
        this.handStock.watchFloatingState = function() {
          // Override to do nothing - prevents floating to bottom of viewport
          // Cards will stay in their original position in the hand container
        };
      }
      
      // Also prevent floating by setting the threshold to an impossible value
      if (this.handStock.floatingThreshold !== undefined) {
        this.handStock.floatingThreshold = 9999; // Set threshold so high it never triggers
      }
      
      //can only play one card from hand - might change this select to only matter for moons since that's the only time you have a choice where a card goes
      this.handStock.setSelectionMode("single", {
        unselectOnClick: true,
        selectableCardClass: "card-selectable",
      });

      this.handStock.onCardClick = (card) => {
        this.playCard(this.player_id, card);
      };

      this.handStock.addCards(Array.from(Object.values(this.gamedatas.hand)));

      /*******************************
       *         SOLAR DECK           *
       *******************************/
      if (gamedatas.deckTop) {
        this.addCardBackToDeck(gamedatas.deckTop);
      }

      document
        .getElementById("solar-deck")
        .addEventListener("click", this.onDeckClick.bind(this));

      /*******************************
       *          DISCARD PILE        *
       *******************************/
      this.discardDeck = new BgaCards.DiscardDeck(
        this.cardsManager,
        document.getElementById("discard-pile"),
        {
          maxHorizontalShift: 2,
          maxRotation: 2,
          maxVerticalShift: 2,
          // Only one of these is needed
          selectableCardStyle: {
            outlineSize: 0,
            outlineColor: "rgba(255, 0, 221, 0.6)",
          },
        }
      );

      // Add cards to the discard pile
      this.discardDeck.addCards(
        Array.from(Object.values(this.gamedatas.discardPile))
      );

      // DiscardDeck doesn't support onCardClick directly
      // You need to use setSelectionMode and onSelectionChange instead
      this.discardDeck.setSelectionMode("single");

      this.discardDeck.onSelectionChange = (selection, lastChange) => {
        console.log("=== DISCARD PILE CARD SELECTED ===");
        console.log("Selected cards:", selection);
        console.log("Last changed card:", lastChange);

        if (selection.length > 0) {
          const card = selection[0];
          console.log("Selected card from discard:", card);

          // Do something with the selected card
          // For example:
          // this.bgaPerformAction("actTakeFromDiscard", { card_id: parseInt(card.id) });
        }
      };

      /*******************************
       *          SOLAR ROWS          *
       *******************************/
      this.solarRow1 = new BgaCards.LineStock(
        this.cardsManager,
        document.getElementById("solar-row-1"),
        {
          gap: "3px",
          selectableCardStyle: {
            outlineSize: 0,
          },
          selectedCardStyle: {
            outlineColor: "rgba(255, 0, 221, 0.6)",
          },
          slots: [
            document.getElementById("solar1_slot0"),
            document.getElementById("solar1_slot1"),
            document.getElementById("solar1_slot2"),
          ],
        }
      );

      // Enable selection mode
      this.solarRow1.setSelectionMode("single");

      this.solarRow2 = new BgaCards.LineStock(
        this.cardsManager,
        document.getElementById("solar-row-2"),
        {
          gap: "3px",
          selectableCardStyle: {
            outlineSize: 0,
          },
          selectedCardStyle: {
            outlineColor: "rgba(255, 0, 221, 0.6)",
          },
          slots: [
            document.getElementById("solar2_slot0"),
            document.getElementById("solar2_slot1"),
            document.getElementById("solar2_slot2"),
          ],
        }
      );

      // Enable selection mode
      this.solarRow2.setSelectionMode("single");

      // Fill Solar Row 1 - FIRST
      Object.values(this.gamedatas.solarRow1).forEach((card) => {
        if (card) {
          const slot = parseInt(card.location_arg);
          this.solarRow1.addCard(card, { index: slot });
        }
      });

      // Fill Solar Row 2 - FIRST
      Object.values(this.gamedatas.solarRow2).forEach((card) => {
        if (card) {
          const slot = parseInt(card.location_arg);
          this.solarRow2.addCard(card, { index: slot });
        }
      });

      // NOW set click handlers AFTER cards are added
      this.solarRow1.onCardClick = (card) => {
        console.log("=== SOLAR ROW 1 CARD CLICKED ===");
        console.log("Card:", card);

        if (!this.isCurrentPlayerActive()) {
          console.log("It is not your turn");
          return;
        }

        // Check if player has draft actions available
        const playerId = this.player_id;
        const openActions = this.counters[playerId].open_actions.getValue();
        const draftActions = this.counters[playerId].draft_actions.getValue();
        
        if (openActions === 0 && draftActions === 0) {
          this.showMessage(_("You don't have any DRAFT actions available"), "error");
          return;
        }

        const slot = parseInt(card.location_arg);
        console.log("Drafting from row 1, slot", slot);

        this.bgaPerformAction("actDraftCard", {
          card_id: parseInt(card.id),
          row: 1,
          slot: slot,
        }).catch((error) => {
          console.log("Draft action failed:", error);
        });
      };

      this.solarRow2.onCardClick = (card) => {
        console.log("=== SOLAR ROW 2 CARD CLICKED ===");
        console.log("Card:", card);

        if (!this.isCurrentPlayerActive()) {
          console.log("It is not your turn");
          return;
        }

        // Check if player has draft actions available
        const playerId = this.player_id;
        const openActions = this.counters[playerId].open_actions.getValue();
        const draftActions = this.counters[playerId].draft_actions.getValue();
        
        if (openActions === 0 && draftActions === 0) {
          this.showMessage(_("You don't have any DRAFT actions available"), "error");
          return;
        }

        const slot = parseInt(card.location_arg);
        console.log("Drafting from row 2, slot", slot);

        this.bgaPerformAction("actDraftCard", {
          card_id: parseInt(card.id),
          row: 2,
          slot: slot,
        }).catch((error) => {
          console.log("Draft action failed:", error);
        });
      };

      /*******************************
       *     SOLAR SYSTEM SETUP
       *******************************/

      // Create player table container
      gameArea.insertAdjacentHTML(
        "beforeend",
        '<div id="player-tables"></div>'
      );

      // --------------------------------------
      // 1. Create each player's solar-system wrapper
      // --------------------------------------
      Object.values(gamedatas.players).forEach((player) => {
        document.getElementById("player-tables").insertAdjacentHTML(
          "beforeend",
          `
              <div class="playertable whiteblock playertable_${player.id}">
                  <div class="player-title">
                    <strong>Solar System – 
                      <span class="player-name" id="playername_${player.id}">
                        ${player.name}
                      </span>
                    </strong>
                  </div>
                  <div class="solar-system" id="solar_${player.id}"></div>
              </div>
              `
        );

        //display player name same color as player's color
        const nameEl = document.getElementById(`playername_${player.id}`);
        nameEl.style.color = `#${player.color}`;
      });

      // Move LOCAL player's system into personal view
      const localPlayerId = this.player_id;
      const myWrapper = document.querySelector(`.playertable_${localPlayerId}`);
      document.getElementById("mysolarsystem_wrap").appendChild(myWrapper);

      // --------------------------------------
      // 2. STOCK STORAGE (keyed by planetId)
      // --------------------------------------
      this.planetStocks = {}; // planetId → LineStock
      this.moonStocks = {}; // planetId → LineStock
      this.cometStocks = {}; // planetId → LineStock

      // --------------------------------------
      // 3. Create a planet slot
      // --------------------------------------
      this.createPlanetSlot = (playerId, planetCard) => {
        const solar = document.getElementById(`solar_${playerId}`);

        const slot = document.createElement("div");
        slot.classList.add("planet-slot");
        slot.dataset.planetId = planetCard.id;

        slot.innerHTML = `
              <div class="moon-container"></div>
              <div class="planet-container"></div>
              <div class="comet-container"></div>
          `;

        solar.appendChild(slot);

        // Bind LineStocks to containers
        this.planetStocks[planetCard.id] = new BgaCards.LineStock(
          this.cardsManager,
          slot.querySelector(".planet-container")
        );

        this.moonStocks[planetCard.id] = new BgaCards.LineStock(
          this.cardsManager,
          slot.querySelector(".moon-container")
        );

        this.cometStocks[planetCard.id] = new BgaCards.LineStock(
          this.cardsManager,
          slot.querySelector(".comet-container")
        );

        // Add the planet card
        this.planetStocks[planetCard.id].addCard(planetCard);
      };

      // --------------------------------------
      // 4. Build all solar systems from gamedatas (REFRESH SAFE!)
      // --------------------------------------
      Object.values(gamedatas.players).forEach((player) => {
        const tableau = gamedatas.tableau[player.id];
        if (!tableau) return;

        const cards = Object.values(tableau).sort(
          (a, b) => a.planet_order - b.planet_order
        );

        // First pass: create all planet slots
        cards.forEach((card) => {
          if (card.type === "planet") {
            this.createPlanetSlot(player.id, card);
          }
        });

        // Second pass: attach moons & comets to their saved parent
        cards.forEach((card) => {
          if (card.type === "moon") {
            const parentId = card.parent_id;
            const slotIndex = card.parent_slot ?? undefined;

            this.moonStocks[parentId].addCard(card, {
              index: slotIndex,
            });
          }

          if (card.type === "comet") {
            const parentId = card.parent_id;
            const slotIndex = card.parent_slot ?? undefined;

            this.cometStocks[parentId].addCard(card, {
              index: slotIndex,
            });
          }
        });
      });

      /*******************************
       *         PLAYER PANELS        *
       *******************************/
      this.counters = {};
      // Player boards (keep it simple for now)
      for (var playerId in gamedatas.players) {
        if (!gamedatas.players.hasOwnProperty(playerId)) continue;
        var player = gamedatas.players[playerId];

        // Classic player panel div
        var playerPanel = document.getElementById("player_board_" + playerId);
        if (playerPanel) {
          playerPanel.insertAdjacentHTML(
            "beforeend",
            `
                <div class="player-counters-grid">

          <div class="counter-block-actions">
              <span class="counter-label-actions">Open:</span>
              <span id="open-actions-counter-${playerId}" class="counter-value-actions"></span>
          </div>

          <div class="counter-block-actions">
              <span class="counter-label-actions">Draft:</span>
              <span id="draft-actions-counter-${playerId}" class="counter-value-actions"></span>
          </div>

          <div class="counter-block-actions">
              <span class="counter-label-actions">Draw:</span>
              <span id="draw-actions-counter-${playerId}" class="counter-value-actions"></span>
          </div>

          <div class="counter-block-actions">
              <span class="counter-label-actions">Play:</span>
              <span id="play-actions-counter-${playerId}" class="counter-value-actions"></span>
          </div>
                
                    <div class="counter-block">
                        <img class="counter-icon" src="${g_gamethemeurl}img/counter-bluePlanet.png"/>
                        <span id="blue-planet-counter-${playerId}" class="counter-value"></span>
                    </div>

                    <div class="counter-block">
                        <img class="counter-icon" src="${g_gamethemeurl}img/counter-greenPlanet.png"/>
                        <span id="green-planet-counter-${playerId}" class="counter-value"></span>
                    </div>

                    <div class="counter-block">
                        <img class="counter-icon" src="${g_gamethemeurl}img/counter-redPlanet.png"/>
                        <span id="red-planet-counter-${playerId}" class="counter-value"></span>
                    </div>

                    <div class="counter-block">
                        <img class="counter-icon" src="${g_gamethemeurl}img/counter-tanPlanet.png"/>
                        <span id="tan-planet-counter-${playerId}" class="counter-value"></span>
                    </div>

                    <div class="counter-block">
                        <img class="counter-icon" src="${g_gamethemeurl}img/counter-comet.png"/>
                        <span id="comet-counter-${playerId}" class="counter-value"></span>
                    </div>

                    <div class="counter-block">
                        <img class="counter-icon" src="${g_gamethemeurl}img/counter-moon.png"/>
                        <span id="moon-counter-${playerId}" class="counter-value"></span>
                    </div>

                    <div class="counter-block">
                        <img class="counter-icon" src="${g_gamethemeurl}img/counter-ring.png"/>
                        <span id="ring-counter-${playerId}" class="counter-value"></span>
                    </div>

                    <div class="counter-block">
                        <img class="counter-icon" src="${g_gamethemeurl}img/counter-hand.png"/>
                        <span id="hand-counter-${playerId}" class="counter-value"></span>
                    </div>

                </div>
            `
          );
        }

        //
        // COUNTER DEFINITIONS
        //
        this.counters[playerId] = {};
        const counterList = [
          {
            name: "blue",
            id: `blue-planet-counter-${playerId}`,
            default: gamedatas.players[playerId].blue_planet_count ?? 0,
          },
          {
            name: "green",
            id: `green-planet-counter-${playerId}`,
            default: gamedatas.players[playerId].green_planet_count ?? 0,
          },
          {
            name: "red",
            id: `red-planet-counter-${playerId}`,
            default: gamedatas.players[playerId].red_planet_count ?? 0,
          },
          {
            name: "tan",
            id: `tan-planet-counter-${playerId}`,
            default: gamedatas.players[playerId].tan_planet_count ?? 0,
          },
          {
            name: "comet",
            id: `comet-counter-${playerId}`,
            default: gamedatas.players[playerId].comet_count ?? 0,
          },
          {
            name: "moon",
            id: `moon-counter-${playerId}`,
            default: gamedatas.players[playerId].moon_count ?? 0,
          },
          {
            name: "ring",
            id: `ring-counter-${playerId}`,
            default: gamedatas.players[playerId].ring_count ?? 0,
          },
          {
            name: "hand",
            id: `hand-counter-${playerId}`,
            default: gamedatas.cardsInHand[playerId] ?? 0,
          },
          {
              name: "open_actions",
              id: `open-actions-counter-${playerId}`,
              default: gamedatas.players[playerId].open_actions ?? 0,
          },
          {
              name: "draft_actions",
              id: `draft-actions-counter-${playerId}`,
              default: gamedatas.players[playerId].draft_actions ?? 0,
          },
          {
              name: "draw_actions",
              id: `draw-actions-counter-${playerId}`,
              default: gamedatas.players[playerId].draw_actions ?? 0,
          },
          {
              name: "play_actions",
              id: `play-actions-counter-${playerId}`,
              default: gamedatas.players[playerId].play_actions ?? 0,
          },
        ];

        for (let entry of counterList) {
          const counter = new ebg.counter();
          // Link action counters to server-side PlayerCounter for automatic updates
          if (entry.name === 'open_actions' || entry.name === 'draft_actions' || 
              entry.name === 'draw_actions' || entry.name === 'play_actions') {
            counter.create(entry.id, {
              playerCounter: entry.name,
              playerId: playerId
            });
          } else {
            counter.create(entry.id);
            counter.setValue(entry.default);
          }
          this.counters[playerId][entry.name] = counter;
        }
      }

      this.setupNotifications();
      console.log("FULL GAMEDATAS:", gamedatas);
      console.log("Ending game setup");
    },

  /*------------------------------------------------------------------------------------/
                                    GAME & CLIENT STATES
  /*------------------------------------------------------------------------------------/
    /*******************************
    *            ENTER             *
    *******************************/
    onEnteringState: function (stateName, args) {
      console.log("Entering state: " + stateName, args);

      switch (stateName) {
        case "PlayerTurn":
          // Update action counters when entering PlayerTurn state
          // Note: args might be nested as args.args depending on BGA framework version
          const stateArgs = args?.args || args;
          if (stateArgs) {
            const activePlayerId = this.getActivePlayerId();
            if (activePlayerId && this.counters && this.counters[activePlayerId]) {
              console.log("Updating action counters in onEnteringState:", stateArgs);
              if (stateArgs.open_actions !== undefined) {
                this.counters[activePlayerId].open_actions.toValue(stateArgs.open_actions);
              }
              if (stateArgs.draft_actions !== undefined) {
                this.counters[activePlayerId].draft_actions.toValue(stateArgs.draft_actions);
              }
              if (stateArgs.draw_actions !== undefined) {
                this.counters[activePlayerId].draw_actions.toValue(stateArgs.draw_actions);
              }
              if (stateArgs.play_actions !== undefined) {
                this.counters[activePlayerId].play_actions.toValue(stateArgs.play_actions);
              }
            }
          }
          break;

        case "MoonPlacement": // Changed from "moonPlacement"
          this.onEnteringMoonPlacement(args);
          break;
      }
    },

    /*******************************
    *            LEAVE             *
    *******************************/
    onLeavingState: function (stateName) {
      console.log("Leaving state: " + stateName);

      switch (stateName) {
        case "moonPlacement":
          this.cleanupMoonPlacement();
          break;

        case "dummy":
          break;
      }
    },

    /*******************************
    *            UPDATE            *
    *******************************/
    onUpdateActionButtons: function (stateName, args) {
      console.log("onUpdateActionButtons: " + stateName, args);

      // Update action counters when action buttons are updated (for all players, not just active)
      // This is called BEFORE onEnteringState, so we need to update counters here too
      if (stateName === "PlayerTurn" && args) {
        const activePlayerId = this.getActivePlayerId();
        if (activePlayerId && this.counters && this.counters[activePlayerId]) {
          console.log("Updating action counters in onUpdateActionButtons:", args);
          if (args.open_actions !== undefined) {
            this.counters[activePlayerId].open_actions.toValue(args.open_actions);
          }
          if (args.draft_actions !== undefined) {
            this.counters[activePlayerId].draft_actions.toValue(args.draft_actions);
          }
          if (args.draw_actions !== undefined) {
            this.counters[activePlayerId].draw_actions.toValue(args.draw_actions);
          }
          if (args.play_actions !== undefined) {
            this.counters[activePlayerId].play_actions.toValue(args.play_actions);
          }
        }

        // Add Solar Flare button if available
        if (stateName === "PlayerTurn" && args && args.solar_flare_available && this.isCurrentPlayerActive()) {
          // Check if button already exists to avoid duplicates
          if (!document.getElementById("solar_flare_btn")) {
            this.addActionButton(
              "solar_flare_btn",
              _("Solar Flare"),
              () => {
                // Show a dialog to choose which row (1 or 2)
                this.showSolarFlareDialog();
              },
              null,
              false,
              "blue"
            );
          }
        } else {
          // Remove button if not available or not active player
          const btn = document.getElementById("solar_flare_btn");
          if (btn) btn.remove();
        }

        // Add Sun Ability button if available
        if (stateName === "PlayerTurn" && args && args.sun_ability && args.sun_ability_available && this.isCurrentPlayerActive()) {
          // Check if button already exists to avoid duplicates
          if (!document.getElementById("sun_ability_btn")) {
            this.addActionButton(
              "sun_ability_btn",
              args.sun_ability,
              () => {
                // Show appropriate dialog/action based on ability
                this.useSunAbility(args.sun_ability);
              },
              null,
              false,
              "green"
            );
          }
        } else {
          // Remove button if not available or not active player
          const btn = document.getElementById("sun_ability_btn");
          if (btn) btn.remove();
        }

        // Add Pass button if it's the player's turn
        if (stateName === "PlayerTurn" && this.isCurrentPlayerActive()) {
          // Check if button already exists to avoid duplicates
          if (!document.getElementById("pass_btn")) {
            this.addActionButton(
              "pass_btn",
              _("Pass"),
              () => {
                this.bgaPerformAction("actPass");
              },
              null,
              false,
              "gray"
            );
          }
        } else {
          // Remove button if not active player
          const btn = document.getElementById("pass_btn");
          if (btn) btn.remove();
        }
      }

      if (this.isCurrentPlayerActive()) {
        switch (stateName) {
          case "PlayerTurn":
            // TEMP: don't add buttons yet, just log to avoid crashes
            if (args && args.playableCardsIds) {
              console.log("Playable cards:", args.playableCardsIds);
            }
            break;
        }
      }
    },

    /*******************************
    *        MOON - PLACEMENT      *
    *******************************/
    onEnteringMoonPlacement(args) {
      if (!this.isCurrentPlayerActive()) return;

      console.log("=== ENTERING MOON PLACEMENT ===");
      console.log("Full args:", args);
      console.log("Nested args:", args.args);
      console.log("=== TEST ===");
      
      const playerId = this.player_id;
      const tableau = Object.values(this.gamedatas.tableau[playerId]);
      const planets = tableau.filter((c) => c.type === "planet");

      console.log("Player ID:", playerId);
      console.log("Tableau:", tableau);
      console.log("Found planets:", planets);

      // Enable selection mode on all planet stocks
      planets.forEach((planet) => {
        console.log("Processing planet:", planet.id);
        const planetStock = this.planetStocks[planet.id];
        console.log("Planet stock found:", planetStock);

        if (planetStock) {
          console.log("Enabling selection for planet stock:", planet.id);
          planetStock.setSelectionMode("single");

          // Set click handler for this stock
          planetStock.onCardClick = (card) => {
            console.log("Planet card clicked in stock:", card);
            this.onPlanetSelectedForMoon(card);
          };

          // Add visual highlight to the card
          const cardDiv = document.querySelector(
            `[data-card-id="${planet.id}"]`
          );
          console.log("Card div found:", cardDiv);
          if (cardDiv) {
            dojo.addClass(cardDiv, "selectable-planet");
            console.log("Added selectable-planet class to:", cardDiv);
          }
        } else {
          console.error("No planet stock found for planet:", planet.id);
        }
      });

      // Add cancel button
      this.addActionButton(
        "cancel_moon_btn",
        _("Cancel"),
        () => {
          console.log("Cancel clicked");
          this.cleanupMoonPlacement();
          this.bgaPerformAction("actCancelMoonPlacement");
        },
        null,
        false,
        "gray"
      );
    },

    /*******************************
    *          MOON - SELECT       *
    *******************************/
    onPlanetSelectedForMoon(planet) {
      console.log("=== PLANET SELECTED ===");
      console.log("Selected planet:", planet);

      this.cleanupMoonPlacement();

      // Get the moon card ID from game state - use args.args
      const moonCardId = this.gamedatas.gamestate.args.pending_moon_card_id;

      console.log("Moon card ID:", moonCardId);
      console.log("Placing moon on planet:", planet.id);

      this.bgaPerformAction("actPlaceMoon", {
        card_id: moonCardId,
        target_planet_id: planet.id,
      });
    },

    /*******************************
    *         MOON - CLEANUP       *
    *******************************/
    cleanupMoonPlacement() {
      console.log("=== CLEANING UP MOON PLACEMENT ===");

      const playerId = this.player_id;
      const tableau = Object.values(this.gamedatas.tableau[playerId] || {});
      const planets = tableau.filter((c) => c.type === "planet");

      // Disable selection and remove handlers from all planet stocks
      planets.forEach((planet) => {
        const planetStock = this.planetStocks[planet.id];
        if (planetStock) {
          planetStock.setSelectionMode("none");
          planetStock.onCardClick = undefined;
        }

        // Remove visual highlight
        const cardDiv = document.querySelector(`[data-card-id="${planet.id}"]`);
        if (cardDiv) {
          dojo.removeClass(cardDiv, "selectable-planet");
        }
      });

      // UNSELECT the moon card in hand
      this.handStock.unselectAll();

      // Remove cancel button
      const cancelBtn = document.getElementById("cancel_moon_btn");
      if (cancelBtn) cancelBtn.remove();
    },

  /*------------------------------------------------------------------------------------/
                                      ACTIONS
  /*------------------------------------------------------------------------------------/
    /*******************************
    *             PLAY             *
    *******************************/
    
    playCard(playerId, card) {
      console.log("Card played!");

      if (!this.isCurrentPlayerActive()) {
        this.showMessage(_("It is not your turn"), "error");
        this.handStock.unselectAll();
        return;
      }

      //******CHECK IF PLAYER HAS ACTIONS AVAILABLE
      const openActions = this.counters[playerId].open_actions.getValue();
      let playActions = this.counters[playerId].play_actions.getValue();
      // Ignore Shell Star's 999 value
      if (playActions > 100) {
        playActions = 0;
      }
      
      // Check if player has actions available
      // Note: Shell Star allows unlimited moon plays, but we can't check that from client
      // So we'll check actions for all cards including moons, and let the server handle Shell Star exception
      // The server will reject if Shell Star isn't active and there are no actions
      if (openActions === 0 && playActions === 0) {
        this.showMessage(
          _("You don't have any PLAY actions available"),
          "error"
        );
        this.handStock.unselectAll();
        return;
      }

      //******MUST PLAY PLANET FIRST
      const blue = this.counters[playerId].blue.getValue(playerId);
      const green = this.counters[playerId].green.getValue(playerId);
      const red = this.counters[playerId].red.getValue(playerId);
      const tan = this.counters[playerId].tan.getValue(playerId);

      console.log(blue + ", " + green + ", " + red + ", " + tan);

      const total = blue + green + red + tan;

      if (total === 0 && card.type !== "planet") {
        this.showMessage(
          _("You must play a planet before playing a comet or moon"),
          "error"
        );
        this.handStock.unselectAll();
        return;
      }

      //******CANNOT PLAY COMET NEXT ANOTHER COMET
      const lastCardIsAComet = this.isLastCardInTableauAComet(playerId);

      if (card.type === "comet" && lastCardIsAComet) {
        this.showMessage(
          _("You cannot play a comet next to another comet"),
          "error"
        );
        this.handStock.unselectAll();
        return;
      }

      //******PLAY CARD AFTER ALL CHECKS
      this.bgaPerformAction("actPlayCard", { card_id: card.id })
        .catch((error) => {
          // If the action fails (e.g., no actions available), deselect the card
          console.log("Action failed, deselecting card:", error);
          this.handStock.unselectAll();
        });
    },

    isLastCardInTableauAComet(playerId) {
      // Use the real tableau data sent by the server
      const tableau = Object.values(this.gamedatas.tableau[playerId]);
      const cards = Object.values(tableau);

      if (cards.length === 0) return null;

      // Step 1 - Get last planet using planet_order (highest numbered planet)
      const planets = cards
        .filter((c) => c.type === "planet")
        .map((p) => ({ ...p, planet_order: Number(p.planet_order) }));

      if (planets.length === 0) return null;

      planets.sort((a, b) => b.planet_order - a.planet_order);

      const lastPlanet = planets[0];

      // Step 2 — Check if last planet has a comet
      const hasComet = cards.filter(
        (c) =>
          c.type === "comet" && Number(c.parent_id) === Number(lastPlanet.id)
      );

      if (hasComet.length > 0) {
        return true;
      }

      return false;
    },

    showSolarFlareDialog() {
      const self = this;
      const dialog = new ebg.popindialog();
      dialog.create("solar_flare_dialog");
      dialog.setTitle(_("Choose Solar Row"));
      dialog.setContent(
        '<div style="padding: 10px;">' +
          '<p>' + _("Which solar row would you like to refresh?") + '</p>' +
          '<div style="margin-top: 20px; text-align: center;">' +
            '<button id="solar_flare_row1" style="margin: 5px; padding: 10px 20px; font-size: 16px;">Solar Row 1</button>' +
            '<button id="solar_flare_row2" style="margin: 5px; padding: 10px 20px; font-size: 16px;">Solar Row 2</button>' +
          '</div>' +
        '</div>'
      );
      dialog.show();

      dojo.connect(dojo.byId("solar_flare_row1"), "onclick", () => {
        dialog.destroy();
        this.bgaPerformAction("actSolarFlare", { row: 1 });
      });

      dojo.connect(dojo.byId("solar_flare_row2"), "onclick", () => {
        dialog.destroy();
        this.bgaPerformAction("actSolarFlare", { row: 2 });
      });
    },

    addCardBackToDeck(card) {
      if (!card) {
        return;
      }

      const deck = document.getElementById("solar-deck");
      if (!deck) return;

      dojo.create(
        "div",
        {
          id: "deck_top_card",
          class: `card card-back-${card.type}`,
          style: "z-index:0;",
        },
        deck
      );
    },

    onDeckClick: function () {
      if (!this.isCurrentPlayerActive()) {
        this.showMessage(_("It is not your turn"), "error");
        return;
      }

      // Check if player has draw actions available
      const playerId = this.player_id;
      const openActions = this.counters[playerId].open_actions.getValue();
      const drawActions = this.counters[playerId].draw_actions.getValue();
      
      if (openActions === 0 && drawActions === 0) {
        this.showMessage(_("You don't have any DRAW actions available"), "error");
        return;
      }

      this.bgaPerformAction("actDrawCard").catch((error) => {
        console.log("Draw action failed:", error);
      });
    },

  /*------------------------------------------------------------------------------------/
                                    NOTIFICATIONS
  /*------------------------------------------------------------------------------------/
    /*******************************
    *            SETUP             *
    *******************************/
    setupNotifications: function () {
      console.log("notifications subscriptions setup");

      // automatically listen to the notifications, based on the `notif_xxx` function on this class.
      this.bgaSetupPromiseNotifications();
    },



    /*******************************
    *             PLAY             *
    *******************************/
    notif_cardPlayed: async function (notif) {
      console.log("notif_cardPlayed", notif);
      const card = notif.card;
      const playerId = notif.player_id;
      const newValue = notif.newValue;
      const counter = notif.counter;
      const newRingCount = notif.newRingCount;
      const open_actions = notif.open_actions
      const draft_actions = notif.draft_actions;
      const draw_actions = notif.draw_actions;
      const play_actions = notif.play_actions;
      
      // Remove from hand if it's the current player's card
      if (playerId == this.player_id) {
        await this.handStock.removeCard(card);
      }

      if (card.type === "planet") {
        this.createPlanetSlot(playerId, card);
      }

      if (card.type === "moon") {
        const parent = card.parent_id;
        this.moonStocks[parent].addCard(card, { index: card.parent_slot });
      }

      if (card.type === "comet") {
        const parent = card.parent_id;
        this.cometStocks[parent].addCard(card, { index: card.parent_slot });
      }

      //add card to appropriate spot and tick its corresponding counters
      if (card.type == "planet") {
        if (counter == "blue") {
          this.counters[playerId].blue.setValue(newValue);
        }
        if (counter == "green") {
          this.counters[playerId].green.setValue(newValue);
        }
        if (counter == "red") {
          this.counters[playerId].red.setValue(newValue);
        }
        if (counter == "tan") {
          this.counters[playerId].tan.setValue(newValue);
        }
        if (newRingCount > 0) {
          this.counters[playerId].ring.setValue(newRingCount);
        }
      }

      if (card.type == "comet") {
        this.counters[playerId].comet.setValue(newValue);
      }

      if (card.type == "moon") {
        this.counters[playerId].moon.setValue(newValue);
      }

      // Update action counters after card is played
      if (this.counters && this.counters[playerId]) {
        if (open_actions !== undefined) {
          this.counters[playerId].open_actions.setValue(open_actions);
        }
        if (draft_actions !== undefined) {
          this.counters[playerId].draft_actions.setValue(draft_actions);
        }
        if (draw_actions !== undefined) {
          this.counters[playerId].draw_actions.setValue(draw_actions);
        }
        if (play_actions !== undefined) {
          this.counters[playerId].play_actions.setValue(play_actions);
        }
      }

      // **Update gamedatas.tableau with the new card**
      if (!this.gamedatas.tableau[playerId]) {
        this.gamedatas.tableau[playerId] = {};
      }
      this.gamedatas.tableau[playerId][card.id] = card;
    },

    /*******************************
    *             DRAW             *
    *******************************/
    notif_deckDraw: async function (notif) {
      const open_actions = notif.open_actions;
      const draw_actions = notif.draw_actions;
      const playerId = notif.player_id;
      console.log("notif_deckDraw", notif);

      // --- UPDATE THE COUNT ---
      if (notif.cardsRemaining !== undefined) {
        document.getElementById("deck-count").innerText = notif.cardsRemaining;
      }

      /*
       * If deck still has cards, show new top card-back
       * does this first to put card under current top card
       * so current one when moved will reveal new top card
       */

      if (notif.newDeckTop) {
        this.addCardBackToDeck(notif.newDeckTop);
      } //add else to show empty deck

      // Remove old deck-top visual
      const deckTopElem = document.getElementById("deck_top_card");
      if (deckTopElem) {
        deckTopElem.remove();
      }

      // Update draw/open action counters after card is drawn
      if (this.counters && this.counters[playerId]) {
        if (open_actions !== undefined) {
          this.counters[playerId].open_actions.setValue(open_actions);
        }
        if (draw_actions !== undefined) {
          this.counters[playerId].draw_actions.setValue(draw_actions);
        }
      }

      // Add drawn card to hand
      await this.handStock.addCard(notif.deckTop);
    },

    /*******************************
    *             DRAFT            *
    *******************************/
    notif_draft: async function (notif) {
      console.log("notif_draft", notif);
      const row = notif.row; // 'solar1' or 'solar2'
      const slot = notif.slot; // 0,1,2
      const card = notif.card;
      const playerId = notif.player_id;
      const open_actions = notif.open_actions;
      const draft_actions = notif.draft_actions;

      // --- UPDATE THE COUNT ---
      if (notif.cardsRemaining !== undefined) {
        document.getElementById("deck-count").innerText = notif.cardsRemaining;
      }

      //put new card on top to display to user
      if (notif.newDeckTop) {
        this.addCardBackToDeck(notif.newDeckTop);
      } //add else to show empty deck ----------------------TO DO

      // Remove old deck-top visual
      const deckTopElem = document.getElementById("deck_top_card");
      if (deckTopElem) {
        await deckTopElem.remove();
      }

      // Remove ONLY from the row the card came from
      if (row === "solar1") {
        await this.solarRow1.removeCard(card);
      } else {
        await this.solarRow2.removeCard(card);
      }

      // Add new card from deck to solar row (if any)
      if (notif.deckTop) {
        if (row === "solar1") {
          await this.solarRow1.addCard(notif.deckTop, { index: slot });
        } else {
          await this.solarRow2.addCard(notif.deckTop, { index: slot });
        }
      }

    // Update draft/open counters after card is drafted
      if (this.counters && this.counters[playerId]) {
        if (open_actions !== undefined) {
          this.counters[playerId].open_actions.setValue(open_actions);
        }
        if (draft_actions !== undefined) {
          this.counters[playerId].draft_actions.setValue(draft_actions);
        }
      }
      // If it's the current player, add to hand
      if (playerId == this.player_id) {
        await this.handStock.addCard(card);
      }
    },

    /*******************************
    *             PASS             *
    *******************************/
    notif_pass: function (notif) {
      console.log("notif_pass", notif);
      // Nothing to do visually for a pass
    },

    notif_sunAbilityUsed: function (notif) {
      console.log("notif_sunAbilityUsed", notif);
      // Hide the Sun Ability button if this player used it
      if (notif.player_id === this.player_id) {
        const btn = document.getElementById("sun_ability_btn");
        if (btn) btn.remove();
      }
      
      // Update action counters if provided
      if (notif.open_actions !== undefined && this.counters && this.counters[notif.player_id]) {
        this.counters[notif.player_id].open_actions.setValue(notif.open_actions);
      }
      if (notif.draft_actions !== undefined && this.counters && this.counters[notif.player_id]) {
        this.counters[notif.player_id].draft_actions.setValue(notif.draft_actions);
      }
      if (notif.draw_actions !== undefined && this.counters && this.counters[notif.player_id]) {
        this.counters[notif.player_id].draw_actions.setValue(notif.draw_actions);
      }
      if (notif.play_actions !== undefined && this.counters && this.counters[notif.player_id]) {
        this.counters[notif.player_id].play_actions.setValue(notif.play_actions);
      }
    },

    notif_solarFlare: async function (notif) {
      console.log("notif_solarFlare", notif);
      const row = notif.row;
      const discardedCards = notif.discardedCards || [];
      const newCards = notif.newCards || [];

      // Update deck count
      if (notif.cardsRemaining !== undefined) {
        document.getElementById("deck-count").innerText = notif.cardsRemaining;
      }

      // Determine which solar row stock to update
      const solarRowStock = row === 1 ? this.solarRow1 : this.solarRow2;

      // Remove all cards from the row
      // Get all items from the stock and remove them
      // LineStock has an items array containing all cards
      if (solarRowStock.items && solarRowStock.items.length > 0) {
        // Create a copy of items array to avoid issues while iterating
        const itemsCopy = Array.from(solarRowStock.items);
        for (let item of itemsCopy) {
          if (item && item.id) {
            try {
              await solarRowStock.removeCard(item);
            } catch (e) {
              console.warn("Error removing card from solar row:", e);
            }
          }
        }
      }

      // Add discarded cards to discard pile
      for (let card of discardedCards) {
        await this.discardDeck.addCard(card);
      }

      // Add new cards to the solar row
      for (let card of newCards) {
        const slot = card.location_arg || 0;
        await solarRowStock.addCard(card, { index: slot });
      }

      // Update discard count display
      if (notif.cardsInDiscard !== undefined) {
        const discardCountEl = document.getElementById("deck-count");
        if (discardCountEl) {
          discardCountEl.innerText = notif.cardsInDiscard;
        }
      }

      // Hide the Solar Flare button if this player used it
      if (notif.player_id === this.player_id) {
        const btn = document.getElementById("solar_flare_btn");
        if (btn) btn.remove();
      }
    },

    /*******************************
    *        ACTION GRANTED        *
    *******************************/
    notif_actionGranted: function (notif) {
      console.log("notif_actionGranted", notif);
      const playerId = notif.player_id;
      const actionType = notif.action_type;
      const newValue = notif.new_value;

      // Update the appropriate action counter
      if (this.counters && this.counters[playerId]) {
        const counterName = actionType + "_actions";
        if (this.counters[playerId][counterName]) {
          this.counters[playerId][counterName].setValue(newValue);
        } else {
          console.warn("Action counter not found:", counterName, "for player", playerId);
        }
      }
    },

  });
});
