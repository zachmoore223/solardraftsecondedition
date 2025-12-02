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

        const slot = parseInt(card.location_arg);
        console.log("Drafting from row 1, slot", slot);

        this.bgaPerformAction("actDraftCard", {
          card_id: parseInt(card.id),
          row: 1,
          slot: slot,
        });
      };

      this.solarRow2.onCardClick = (card) => {
        console.log("=== SOLAR ROW 2 CARD CLICKED ===");
        console.log("Card:", card);

        if (!this.isCurrentPlayerActive()) {
          console.log("It is not your turn");
          return;
        }

        const slot = parseInt(card.location_arg);
        console.log("Drafting from row 2, slot", slot);

        this.bgaPerformAction("actDraftCard", {
          card_id: parseInt(card.id),
          row: 2,
          slot: slot,
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
      this.counters = {}; // make sure this exists before the loop
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
        // CORRECT COUNTER DEFINITIONS
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
        ];

        for (let entry of counterList) {
          const counter = new ebg.counter();
          counter.create(entry.id);
          counter.setValue(entry.default);
          this.counters[playerId][entry.name] = counter;
        }
      }

      this.setupNotifications();
      console.log("FULL GAMEDATAS:", gamedatas);
      console.log("Ending game setup");
    },

    ///////////////////////////////////////////////////
    //// Game & client states

    // onEnteringState: this method is called each time we are entering into a new game state.
    //                  You can use this method to perform some user interface changes at this moment.
    //
    onEnteringState: function (stateName, args) {
      console.log("Entering state: " + stateName, args);

      switch (stateName) {
        case "PlayerTurn":
          // Normal turn UI
          break;

        case "MoonPlacement": // Changed from "moonPlacement"
          this.onEnteringMoonPlacement(args);
          break;
      }
    },

    // onLeavingState: this method is called each time we are leaving a game state.
    //                 You can use this method to perform some user interface changes at this moment.
    //
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

    // onUpdateActionButtons: in this method you can manage "action buttons" that are displayed in the
    //                        action status bar (ie: the HTML links in the status bar).
    //
    onUpdateActionButtons: function (stateName, args) {
      console.log("onUpdateActionButtons: " + stateName, args);

      if (this.isCurrentPlayerActive()) {
        switch (stateName) {
          case "PlayerTurn":
            // TEMP: don't add buttons yet, just log to avoid crashes
            if (args && args.playableCardsIds) {
              console.log("Playable cards:", args.playableCardsIds);
            }
            break;
          /*
                    this.addActionButton(
                        'play_card_' + cardId,
                        _('Play card with id ${card_id}').replace('${card_id}', cardId),
                        'onCardClick'
                    );*/
        }
      }
    },

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
    ///////////////////////////////////////////////////
    //// Utility methods

    /*
        
            Here, you can defines some utility methods that you can use everywhere in your javascript
            script.
        
        */
    playCard(playerId, card) {
      console.log("Card played!");

      if (!this.isCurrentPlayerActive()) {
        this.showMessage(_("It is not your turn"), "error");
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
        return;
      }

      //******CANNOT PLAY COMET NEXT ANOTHER COMET
      const lastCardIsAComet = this.isLastCardInTableauAComet(playerId);

      if (card.type === "comet" && lastCardIsAComet) {
        this.showMessage(
          _("You cannot play a comet next to another comet"),
          "error"
        );


        
        return;
      }

      //******PLAY CARD AFTER ALL CHECKS
      this.bgaPerformAction("actPlayCard", { card_id: card.id });
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

    ///////////////////////////////////////////////////
    //// Player's action
    //change this to directly occur from the onClick
    onDeckClick: function () {
      if (!this.isCurrentPlayerActive()) {
        this.showMessage(_("It is not your turn"), "error");
        return;
      }

      this.bgaPerformAction("actDrawCard");
    },
    ///////////////////////////////////////////////////
    //// Reaction to cometD notifications

    /*
            setupNotifications:
            
            In this method, you associate each of your game notifications with your local method to handle it.
            
            Note: game notification names correspond to "notifyAllPlayers" and "notifyPlayer" calls in
                  your solardraftsecondedition.game.php file.
        
        */
    setupNotifications: function () {
      console.log("notifications subscriptions setup");

      // automatically listen to the notifications, based on the `notif_xxx` function on this class.
      this.bgaSetupPromiseNotifications();
    },

    /**
     * Handle card played notification
     */
    notif_cardPlayed: async function (notif) {
      console.log("notif_cardPlayed", notif);
      const card = notif.card;
      const playerId = notif.player_id;
      const newValue = notif.newValue;
      const counter = notif.counter;
      const newRingCount = notif.newRingCount;
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

      // **Update gamedatas.tableau with the new card**
      if (!this.gamedatas.tableau[playerId]) {
        this.gamedatas.tableau[playerId] = {};
      }
      this.gamedatas.tableau[playerId][card.id] = card;
    },

    /**
     * Handle deck draw notification (public - just shows someone drew)
     */
    notif_deckDraw: async function (notif) {
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

      // Add drawn card to hand
      await this.handStock.addCard(notif.deckTop);
    },

    /**
     * Handle draft notification
     */
    notif_draft: async function (notif) {
      console.log("notif_draft", notif);
      const row = notif.row; // 'solar1' or 'solar2'
      const slot = notif.slot; // 0,1,2
      const card = notif.card;
      const playerId = notif.player_id;

      // --- UPDATE THE COUNT ---
      if (notif.cardsRemaining !== undefined) {
        document.getElementById("deck-count").innerText = notif.cardsRemaining;
      }

      //put new card on top to display to user
      if (notif.newDeckTop) {
        this.addCardBackToDeck(notif.newDeckTop);
      } //add else to show empty deck

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

      // If it's the current player, add to hand
      if (playerId == this.player_id) {
        await this.handStock.addCard(card);
      }
    },

    /**
     * Handle pass notification
     */
    notif_pass: function (notif) {
      console.log("notif_pass", notif);
      // Nothing to do visually for a pass
    },
    // TODO: from this point and below, you can write your game notifications handling methods

    /*
        Example:
        
        notif_cardPlayed: async function( args )
        {
            console.log( 'notif_cardPlayed' );
            console.log( args );
            
            // Note: args contains the arguments specified during you "notifyAllPlayers" / "notifyPlayer" PHP call
            
            // TODO: play the card in the user interface.
        },    
        
        */
  });
});
