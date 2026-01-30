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

          // Add tooltip with card name for debugging
          if (card.name) {
            div.title = `${card.name} (${card.type} #${card.type_arg})`;
          }
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
                    <b class="section-label-discard">Discard Pile (<span id="discard-count">${cardsInDiscard}</span>)</b>  
                    <div id="discard-pile"></div>
                </div>

            </div>

        </div>


        `
      );

      /*******************************
       *          PLAYER HAND         *
       *******************************/
      const handElement = document.getElementById("player-hand");
      
      this.handStock = new BgaCards.LineStock(
        this.cardsManager,
        handElement,
        {
            gap: "0px", // Set to 0, we'll handle spacing with margin
            selectableCardStyle: {
            outlineSize: 0,
          },
          autowidth: true,
          wrap: "nowrap",
          selectedCardStyle: {
            outlineColor: "rgba(255, 0, 221, 0.6)",
          },
        }
      );

      // Function to dynamically update card overlap based on hand size
      const updateHandOverlap = () => {
        const cards = handElement.querySelectorAll('.card');
        const cardCount = cards.length;
        const multiplier = (cardCount * cardCount);
        if (cardCount === 0) return;
        
        // Calculate overlap amount based on number of cards
        // More cards = more overlap needed
        // Start with no overlap for few cards, increase as hand grows
        let overlapAmount = 0;
        
        if (cardCount <= 5) {
          overlapAmount = 0;
        } else {  
          overlapAmount = 10 + multiplier;
        } 

        // Apply margin-right to all cards except the last one
        cards.forEach((card, index) => {
          if (index === cards.length - 1) {
            // Last card has no margin
            card.style.marginRight = '0';
          } else {
            // Other cards have negative margin for overlap
            card.style.marginRight = `-${overlapAmount}px`;
          }
        });
      };
      
      // Override addCard to update overlap after adding
      const originalAddCard = this.handStock.addCard.bind(this.handStock);
      this.handStock.addCard = async function(card, settings) {
        const result = await originalAddCard(card, settings);
        setTimeout(updateHandOverlap, 50);
        return result;
      };
      
      // Override addCards to update overlap after adding
      const originalAddCards = this.handStock.addCards.bind(this.handStock);
      this.handStock.addCards = async function(cards, settings, shift) {
        const result = await originalAddCards(cards, settings, shift);
        setTimeout(updateHandOverlap, 50);
        return result;
      };
      
      // Override removeCard to update overlap after removing
      const originalRemoveCard = this.handStock.removeCard.bind(this.handStock);
      this.handStock.removeCard = async function(card, settings) {
        const result = await originalRemoveCard(card, settings);
        setTimeout(updateHandOverlap, 50);
        return result;
      };

      // Enable selection mode - can only play one card from hand
      this.handStock.setSelectionMode("single");

      this.handStock.onCardClick = (card) => {
        this.playCard(this.player_id, card);
      };

      this.handStock.addCards(Array.from(Object.values(this.gamedatas.hand)));
      
      // Initial overlap calculation after cards are added
      setTimeout(updateHandOverlap, 100);

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

      // Disable card selection - we just want to show the expanded view when clicking
      this.discardDeck.setSelectionMode("none");

      // Make the entire discard pile area clickable to show expanded view
      const discardPileWrap = document.getElementById("discard-pile_wrap");
      if (discardPileWrap) {
        // Add pointer cursor to indicate it's clickable
        discardPileWrap.style.cursor = "pointer";
        
        // Single click handler for the entire area
        discardPileWrap.addEventListener("click", (e) => {
          // If player must draw from discard pile, trigger draw action
          if (this.mustDrawFromDiscard && this.isCurrentPlayerActive()) {
            this.bgaPerformAction("actDrawCard").catch((error) => {
              console.log("Draw from discard action failed:", error);
            });
            return;
          }
          
          // Otherwise, toggle the discard pile view
          if (this.discardPileViewVisible) {
            this.hideDiscardPileView();
          } else {
            this.showDiscardPileView();
          }
        });
      }

      // Initialize discard pile view state
      this.discardPileViewVisible = false;
      this.discardPileViewStock = null;
      this.mustDrawFromDiscard = false;

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
            this.counters[playerId][entry.name] = counter;
          } else {
            counter.create(entry.id);
            counter.setValue(entry.default);
            this.counters[playerId][entry.name] = counter;
          }
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
            
            // Track if current player must draw from discard pile
            this.mustDrawFromDiscard = stateArgs.draw_from_discard_only && this.isCurrentPlayerActive();
            
            // Update discard pile visual indicator
            this.updateDiscardPileDrawIndicator();
            
            // Update status bar description based on available actions
            // Add safety checks to prevent errors during initialization
            if (stateArgs && stateArgs.descriptionMyTurn && this.isCurrentPlayerActive && this.isCurrentPlayerActive()) {
              try {
                let description = stateArgs.descriptionMyTurn;
                if (this.getPlayerName && this.player_id) {
                  description = description.replace('${you}', this.getPlayerName(this.player_id));
                }
                if (stateArgs.available_actions && Array.isArray(stateArgs.available_actions) && stateArgs.available_actions.length > 0) {
                  // Translate and capitalize action names
                  const translatedActions = stateArgs.available_actions.map(action => {
                    if (!action) return '';
                    // Capitalize first letter of each word
                    const translated = _(action);
                    return translated.split(' ').map(word => word.charAt(0).toUpperCase() + word.slice(1).toLowerCase()).join(' ');
                  }).filter(a => a);
                  
                  if (translatedActions.length === 1) {
                    description = description.replace('${action}', translatedActions[0]);
                  } else if (translatedActions.length > 1) {
                    const actionList = translatedActions.slice(0, -1).join(', ') + ' ' + _('or') + ' ' + translatedActions[translatedActions.length - 1];
                    description = description.replace('${actionList}', actionList);
                  }
                }
                if (this.statusBar && this.statusBar.setTitle && description) {
                  this.statusBar.setTitle(description);
                }
              } catch (e) {
                console.error("Error updating description in onEnteringState:", e);
              }
            } else if (stateArgs && stateArgs.description && activePlayerId && this.getPlayerName) {
              try {
                let description = stateArgs.description;
                description = description.replace('${actplayer}', this.getPlayerName(activePlayerId));
                if (stateArgs.available_actions && Array.isArray(stateArgs.available_actions) && stateArgs.available_actions.length > 0) {
                  // Translate and capitalize action names
                  const translatedActions = stateArgs.available_actions.map(action => {
                    if (!action) return '';
                    // Capitalize first letter of each word
                    const translated = _(action);
                    return translated.split(' ').map(word => word.charAt(0).toUpperCase() + word.slice(1).toLowerCase()).join(' ');
                  }).filter(a => a);
                  
                  if (translatedActions.length === 1) {
                    description = description.replace('${action}', translatedActions[0]);
                  } else if (translatedActions.length > 1) {
                    const actionList = translatedActions.slice(0, -1).join(', ') + ' ' + _('or') + ' ' + translatedActions[translatedActions.length - 1];
                    description = description.replace('${actionList}', actionList);
                  }
                }
                if (this.statusBar && this.statusBar.setTitle && description) {
                  this.statusBar.setTitle(description);
                }
              } catch (e) {
                console.error("Error updating description in onEnteringState:", e);
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
        
        // Update status bar description based on available actions
        // Add safety checks to prevent errors during initialization
        if (args && this.statusBar && this.statusBar.setTitle) {
          try {
            let description = '';
            
            if (args.descriptionMyTurn && this.isCurrentPlayerActive && this.isCurrentPlayerActive()) {
              // For active player
              description = args.descriptionMyTurn;
              if (this.getPlayerName && this.player_id) {
                description = description.replace('${you}', this.getPlayerName(this.player_id));
              }
            } else if (args.description && activePlayerId && this.getPlayerName) {
              // For other players
              description = args.description;
              description = description.replace('${actplayer}', this.getPlayerName(activePlayerId));
            }
            
            // Replace action placeholders if we have available actions
            if (description && args.available_actions && Array.isArray(args.available_actions) && args.available_actions.length > 0) {
              // Translate and capitalize action names
              const translatedActions = args.available_actions.map(action => {
                if (!action) return '';
                // Capitalize first letter of each word
                const translated = _(action);
                return translated.split(' ').map(word => word.charAt(0).toUpperCase() + word.slice(1).toLowerCase()).join(' ');
              }).filter(a => a);
              
              if (translatedActions.length === 1) {
                description = description.replace('${action}', translatedActions[0]);
              } else if (translatedActions.length > 1) {
                // Build action list with "or" before last item
                const actionList = translatedActions.slice(0, -1).join(', ') + ' ' + _('or') + ' ' + translatedActions[translatedActions.length - 1];
                description = description.replace('${actionList}', actionList);
              }
            }
            
            // Always set a description (use default if dynamic one is empty)
            if (description) {
              this.statusBar.setTitle(description);
            }
          } catch (e) {
            console.error("Error updating description:", e);
          }
        }

        // Add Solar Flare button if available
        // Only show if we're not in "solar flare selection mode" (i.e., row selection buttons aren't showing)
        if (stateName === "PlayerTurn" && args && args.solar_flare_available && this.isCurrentPlayerActive()) {
          // Check if we're already in selection mode (row buttons exist)
          const row1Btn = document.getElementById("solar_flare_row1_btn");
          const row2Btn = document.getElementById("solar_flare_row2_btn");
          const cancelBtn = document.getElementById("solar_flare_cancel_btn");
          
          if (!row1Btn && !row2Btn && !cancelBtn) {
            // Not in selection mode, show the main Solar Flare button
            const existingBtn = document.getElementById("solar_flare_btn");
            if (!existingBtn) {
              this.addActionButton(
                "solar_flare_btn",
                _("Solar Flare"),
                () => {
                  // Hide Solar Flare button and show row selection buttons
                  this.showSolarFlareRowButtons();
                },
                null,
                false,
                "blue"
              );
            } else {
              // Button exists but might be hidden - make sure it's visible
              existingBtn.style.display = '';
            }
          }
        } else {
          // Remove all Solar Flare related buttons if not available or not active player
          this.hideSolarFlareButtons();
        }

        // Add Sun Ability button if available
        // Don't add if we're in Solar Flare selection mode (row buttons exist)
        const row1Btn = document.getElementById("solar_flare_row1_btn");
        const row2Btn = document.getElementById("solar_flare_row2_btn");
        const cancelBtn = document.getElementById("solar_flare_cancel_btn");
        const inSolarFlareSelection = row1Btn || row2Btn || cancelBtn;
        
        if (stateName === "PlayerTurn" && args && args.sun_ability && args.sun_ability_available && this.isCurrentPlayerActive() && !inSolarFlareSelection) {
          // Check if button already exists to avoid duplicates
          const existingBtn = document.getElementById("sun_ability_btn");
          if (!existingBtn) {
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
          } else if (existingBtn.style.display === 'none') {
            // Button exists but is hidden - show it
            existingBtn.style.display = '';
          }
        } else if (!inSolarFlareSelection) {
          // Remove button if not available or not active player (but not if we're in selection mode)
          const btn = document.getElementById("sun_ability_btn");
          if (btn && btn.style.display !== 'none') {
            btn.remove();
          }
        }

        // Add Pass button if it's the player's turn
        if (stateName === "PlayerTurn" && this.isCurrentPlayerActive() && !inSolarFlareSelection) {
          // Check if button already exists to avoid duplicates
          const existingBtn = document.getElementById("pass_btn");
          if (!existingBtn) {
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
          } else if (existingBtn.style.display === 'none') {
            // Button exists but is hidden - show it
            existingBtn.style.display = '';
          }
        } else if (!inSolarFlareSelection) {
          // Remove button if not active player (but not if we're in selection mode)
          const btn = document.getElementById("pass_btn");
          if (btn && btn.style.display !== 'none') {
            btn.remove();
          }
        }

        // Add Debug End Game button (for testing)
        if (stateName === "PlayerTurn" && this.isCurrentPlayerActive() && !inSolarFlareSelection) {
          const existingDebugBtn = document.getElementById("debug_end_game_btn");
          if (!existingDebugBtn) {
            this.addActionButton(
              "debug_end_game_btn",
              _("DEBUG: End Game"),
              () => {
                this.bgaPerformAction("actDebugEndGame");
              },
              null,
              false,
              "red"
            );
          } else if (existingDebugBtn.style.display === 'none') {
            existingDebugBtn.style.display = '';
          }
        } else if (!inSolarFlareSelection) {
          const debugBtn = document.getElementById("debug_end_game_btn");
          if (debugBtn && debugBtn.style.display !== 'none') {
            debugBtn.remove();
          }
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

    showSolarFlareRowButtons() {
      // Hide the main Solar Flare button (but keep it in DOM for position)
      const mainBtn = document.getElementById("solar_flare_btn");
      if (mainBtn) {
        mainBtn.style.display = 'none';
      }
      
      // Hide other action buttons (Sun Ability, Pass) - store references to restore later
      const sunAbilityBtn = document.getElementById("sun_ability_btn");
      const passBtn = document.getElementById("pass_btn");
      if (sunAbilityBtn) {
        sunAbilityBtn.style.display = 'none';
        this._hiddenSunAbilityBtn = sunAbilityBtn;
      }
      if (passBtn) {
        passBtn.style.display = 'none';
        this._hiddenPassBtn = passBtn;
      }
      
      // Update status bar to indicate Solar Flare row selection
      if (this.statusBar && this.statusBar.setTitle) {
        const playerName = this.getPlayerName ? this.getPlayerName(this.player_id) : _("You");
        this.statusBar.setTitle(playerName + " " + _("must select which row to solar flare"));
        this._savedStatusTitle = this.gamedatas.gamestate.args?.descriptionMyTurn || this.gamedatas.gamestate.args?.description || '';
      }
      
      // Add row selection buttons
      this.addActionButton(
        "solar_flare_row1_btn",
        _("Solar Row 1"),
        () => {
          this.bgaPerformAction("actSolarFlare", { row: 1 });
        },
        null,
        false,
        "blue"
      );
      
      this.addActionButton(
        "solar_flare_row2_btn",
        _("Solar Row 2"),
        () => {
          this.bgaPerformAction("actSolarFlare", { row: 2 });
        },
        null,
        false,
        "blue"
      );
      
      this.addActionButton(
        "solar_flare_cancel_btn",
        _("Cancel"),
        () => {
          // Cancel: hide row buttons and restore main button (without consuming solar flare)
          this.hideSolarFlareRowButtons();
          // Show the main button again (it was hidden, not removed)
          const mainBtn = document.getElementById("solar_flare_btn");
          if (mainBtn) {
            mainBtn.style.display = '';
          }
          
          // Restore other action buttons
          if (this._hiddenSunAbilityBtn) {
            this._hiddenSunAbilityBtn.style.display = '';
            this._hiddenSunAbilityBtn = null;
          }
          if (this._hiddenPassBtn) {
            this._hiddenPassBtn.style.display = '';
            this._hiddenPassBtn = null;
          }
          
          // Restore original status bar description
          if (this.statusBar && this.statusBar.setTitle && this._savedStatusTitle) {
            const stateArgs = this.gamedatas.gamestate.args || {};
            let description = this._savedStatusTitle;
            
            // Replace placeholders if needed
            if (this.isCurrentPlayerActive && this.isCurrentPlayerActive()) {
              if (description.includes('${you}') && this.getPlayerName && this.player_id) {
                description = description.replace('${you}', this.getPlayerName(this.player_id));
              }
              if (description.includes('${actplayer}') && this.getPlayerName && this.player_id) {
                description = description.replace('${actplayer}', this.getPlayerName(this.player_id));
              }
            }
            
            // Replace action placeholders if available
            if (stateArgs.available_actions && Array.isArray(stateArgs.available_actions) && stateArgs.available_actions.length > 0) {
              const translatedActions = stateArgs.available_actions.map(action => {
                if (!action) return '';
                const translated = _(action);
                return translated.split(' ').map(word => word.charAt(0).toUpperCase() + word.slice(1).toLowerCase()).join(' ');
              }).filter(a => a);
              
              if (translatedActions.length === 1) {
                description = description.replace('${action}', translatedActions[0]);
              } else if (translatedActions.length > 1) {
                const actionList = translatedActions.slice(0, -1).join(', ') + ' ' + _('or') + ' ' + translatedActions[translatedActions.length - 1];
                description = description.replace('${actionList}', actionList);
              }
            }
            
            this.statusBar.setTitle(description);
            this._savedStatusTitle = null;
          }
        },
        null,
        false,
        "gray"
      );
    },
    
    hideSolarFlareRowButtons() {
      // Remove row selection buttons
      const r1 = document.getElementById("solar_flare_row1_btn");
      const r2 = document.getElementById("solar_flare_row2_btn");
      const cancel = document.getElementById("solar_flare_cancel_btn");
      if (r1) r1.remove();
      if (r2) r2.remove();
      if (cancel) cancel.remove();
    },
    
    hideSolarFlareButtons() {
      // Remove all Solar Flare related buttons
      const btn = document.getElementById("solar_flare_btn");
      const r1 = document.getElementById("solar_flare_row1_btn");
      const r2 = document.getElementById("solar_flare_row2_btn");
      const cancel = document.getElementById("solar_flare_cancel_btn");
      if (btn) btn.remove();
      if (r1) r1.remove();
      if (r2) r2.remove();
      if (cancel) cancel.remove();
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


    showDiscardPileView: async function () {
      // Check if view already exists
      let viewContainer = document.getElementById("discard-pile-view-container");
      
      if (!viewContainer) {
        // Create the container div above solar-grid
        const solarArea = document.getElementById("solar-area");
        viewContainer = document.createElement("div");
        viewContainer.id = "discard-pile-view-container";
        viewContainer.className = "whiteblock";
        viewContainer.style.marginBottom = "10px";
        
        // Insert before solar-grid
        const solarGrid = document.getElementById("solar-grid");
        solarArea.insertBefore(viewContainer, solarGrid);
        
        // Create header with close button
        const header = document.createElement("div");
        header.style.display = "flex";
        header.style.justifyContent = "space-between";
        header.style.alignItems = "center";
        header.style.marginBottom = "10px";
        
        const title = document.createElement("b");
        title.className = "section-label";
        title.textContent = _("Discard Pile - All Cards");
        
        const closeBtn = document.createElement("button");
        closeBtn.textContent = _("Close");
        closeBtn.className = "bgabutton bgabutton_blue";
        closeBtn.style.padding = "5px 15px";
        closeBtn.onclick = () => this.hideDiscardPileView();
        
        header.appendChild(title);
        header.appendChild(closeBtn);
        viewContainer.appendChild(header);
        
        // Create container for cards
        const cardsContainer = document.createElement("div");
        cardsContainer.id = "discard-pile-view-cards";
        cardsContainer.style.display = "flex";
        cardsContainer.style.flexWrap = "wrap";
        cardsContainer.style.gap = "10px";
        cardsContainer.style.justifyContent = "flex-start";
        viewContainer.appendChild(cardsContainer);
        
        // Create a LineStock for displaying cards
        this.discardPileViewStock = new BgaCards.LineStock(
          this.cardsManager,
          cardsContainer,
          {
            gap: "10px",
            selectableCardStyle: {
              outlineSize: 0,
            },
            selectedCardStyle: {
              outlineColor: "rgba(255, 0, 221, 0.6)",
            },
          }
        );
      }
      
      // Get all cards from discard pile
      const discardCards = Array.from(Object.values(this.gamedatas.discardPile || {}));
      
      // Clear existing cards and add all discard cards
      if (this.discardPileViewStock) {
        // Remove all existing cards
        if (this.discardPileViewStock.items && this.discardPileViewStock.items.length > 0) {
          const itemsCopy = Array.from(this.discardPileViewStock.items);
          for (let item of itemsCopy) {
            if (item && item.id) {
              try {
                await this.discardPileViewStock.removeCard(item);
              } catch (e) {
                console.warn("Error removing card from discard view:", e);
              }
            }
          }
        }
        
        // Add all discard cards
        for (let card of discardCards) {
          try {
            await this.discardPileViewStock.addCard(card);
          } catch (e) {
            console.warn("Error adding card to discard view:", e);
          }
        }
      }
      
      // Show the container
      viewContainer.style.display = "block";
      this.discardPileViewVisible = true;
      
      // Ensure the original discard pile wrapper is still visible
      const discardPileWrap = document.getElementById("discard-pile_wrap");
      if (discardPileWrap) {
        discardPileWrap.style.display = "";
      }
    },

    hideDiscardPileView: async function () {
      const viewContainer = document.getElementById("discard-pile-view-container");
      if (viewContainer) {
        viewContainer.style.display = "none";
      }
      this.discardPileViewVisible = false;
      
      // Clear the view stock to release cards back to the manager
      if (this.discardPileViewStock && this.discardPileViewStock.items && this.discardPileViewStock.items.length > 0) {
        const itemsCopy = Array.from(this.discardPileViewStock.items);
        for (let item of itemsCopy) {
          if (item && item.id) {
            try {
              await this.discardPileViewStock.removeCard(item);
            } catch (e) {
              console.warn("Error removing card from view stock:", e);
            }
          }
        }
      }
      
      // Rebuild discardDeck from gamedatas to ensure it's properly restored
      // The BGA Cards library moves cards between stocks, so we need to rebuild
      if (this.discardDeck && this.gamedatas.discardPile) {
        // Remove all cards from discardDeck first
        if (this.discardDeck.items && this.discardDeck.items.length > 0) {
          const itemsCopy = Array.from(this.discardDeck.items);
          for (let item of itemsCopy) {
            if (item && item.id) {
              try {
                await this.discardDeck.removeCard(item);
              } catch (e) {
                console.warn("Error removing card from discardDeck:", e);
              }
            }
          }
        }
        
        // Add all cards from gamedatas back to discardDeck
        const discardCards = Array.from(Object.values(this.gamedatas.discardPile));
        for (let card of discardCards) {
          try {
            await this.discardDeck.addCard(card);
          } catch (e) {
            console.warn("Error adding card to discardDeck:", e);
          }
        }
      }
      
      // Ensure the original discard pile wrapper is visible
      const discardPileWrap = document.getElementById("discard-pile_wrap");
      if (discardPileWrap) {
        discardPileWrap.style.display = "";
      }
      
      // Ensure the discard pile container itself is visible
      const discardPile = document.getElementById("discard-pile");
      if (discardPile) {
        discardPile.style.display = "";
      }
    },

    refreshDiscardPileView: async function () {
      if (!this.discardPileViewVisible || !this.discardPileViewStock) {
        return;
      }

      // Get all cards from discard pile
      const discardCards = Array.from(Object.values(this.gamedatas.discardPile || {}));
      
      // Remove all existing cards
      if (this.discardPileViewStock.items && this.discardPileViewStock.items.length > 0) {
        const itemsCopy = Array.from(this.discardPileViewStock.items);
        for (let item of itemsCopy) {
          if (item && item.id) {
            try {
              await this.discardPileViewStock.removeCard(item);
            } catch (e) {
              console.warn("Error removing card from discard view:", e);
            }
          }
        }
      }
      
      // Add all discard cards
      for (let card of discardCards) {
        try {
          await this.discardPileViewStock.addCard(card);
        } catch (e) {
          console.warn("Error adding card to discard view:", e);
        }
      }
    },

    updateDiscardPileDrawIndicator: function () {
      const discardPileWrap = document.getElementById("discard-pile_wrap");
      if (!discardPileWrap) return;
      
      // Add or remove visual indicator class based on mustDrawFromDiscard
      if (this.mustDrawFromDiscard) {
        discardPileWrap.classList.add("draw-from-discard-active");
        // Update the label to indicate clicking will draw
        const label = discardPileWrap.querySelector(".section-label-discard");
        if (label && !label.dataset.originalText) {
          label.dataset.originalText = label.textContent;
          label.innerHTML = `<span style="color: #ff0; font-weight: bold;">Click to Draw from Discard!</span>`;
        }
      } else {
        discardPileWrap.classList.remove("draw-from-discard-active");
        // Restore original label
        const label = discardPileWrap.querySelector(".section-label-discard");
        if (label && label.dataset.originalText) {
          const discardCount = this.gamedatas.discardPile ? Object.keys(this.gamedatas.discardPile).length : 0;
          label.innerHTML = `Discard Pile (<span id="discard-count">${discardCount}</span>)`;
          delete label.dataset.originalText;
        }
      }
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

      // Add drawn card to hand ONLY if it's the current player who drew
      if (playerId == this.player_id) {
        await this.handStock.addCard(notif.deckTop);
      }
    },

    /*******************************
    *       DRAW FROM DISCARD      *
    *******************************/
    notif_discardDraw: async function (notif) {
      console.log("notif_discardDraw", notif);
      const card = notif.card;
      const playerId = notif.player_id;
      const open_actions = notif.open_actions;
      const draw_actions = notif.draw_actions;

      // Update discard pile count
      if (notif.cardsInDiscard !== undefined) {
        const discardCountEl = document.getElementById("discard-count");
        if (discardCountEl) {
          discardCountEl.innerText = notif.cardsInDiscard;
        }
      }

      // Remove card from discard pile visual
      if (this.discardDeck && card) {
        try {
          await this.discardDeck.removeCard(card);
        } catch (e) {
          console.warn("Error removing card from discard pile:", e);
        }
      }

      // Update gamedatas discard pile
      if (this.gamedatas.discardPile && card.id) {
        delete this.gamedatas.discardPile[card.id];
      }

      // Update draw/open action counters
      if (this.counters && this.counters[playerId]) {
        if (open_actions !== undefined) {
          this.counters[playerId].open_actions.setValue(open_actions);
        }
        if (draw_actions !== undefined) {
          this.counters[playerId].draw_actions.setValue(draw_actions);
        }
      }

      // Add drawn card to hand ONLY if it's the current player who drew
      if (playerId == this.player_id) {
        await this.handStock.addCard(card);
        
        // Check if draw actions are now 0 - clear the discard draw indicator
        if (draw_actions === 0) {
          this.mustDrawFromDiscard = false;
          this.updateDiscardPileDrawIndicator();
        }
      }

      // Refresh discard pile view if it's visible
      if (this.discardPileViewVisible) {
        this.refreshDiscardPileView();
      }
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

      // Hide all Solar Flare buttons if this player used it
      if (notif.player_id === this.player_id) {
        this.hideSolarFlareButtons();
        
        // Restore other action buttons that were hidden during selection
        if (this._hiddenSunAbilityBtn) {
          this._hiddenSunAbilityBtn.style.display = '';
          this._hiddenSunAbilityBtn = null;
        }
        if (this._hiddenPassBtn) {
          this._hiddenPassBtn.style.display = '';
          this._hiddenPassBtn = null;
        }
        
        // Clear saved status title
        this._savedStatusTitle = null;
      }

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
        // Update gamedatas discard pile
        if (!this.gamedatas.discardPile) {
          this.gamedatas.discardPile = {};
        }
        this.gamedatas.discardPile[card.id] = card;
      }

      // Add new cards to the solar row
      for (let card of newCards) {
        const slot = card.location_arg || 0;
        await solarRowStock.addCard(card, { index: slot });
      }

      // Update discard count display
      if (notif.cardsInDiscard !== undefined) {
        const discardCountEl = document.getElementById("discard-count");
        if (discardCountEl) {
          discardCountEl.innerText = notif.cardsInDiscard;
        }
      }

      // Refresh discard pile view if it's visible
      if (this.discardPileViewVisible) {
        this.refreshDiscardPileView();
      }

      // Hide all Solar Flare buttons if this player used it
      if (notif.player_id === this.player_id) {
        this.hideSolarFlareButtons();
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

    /*******************************
    *        SCORE UPDATED         *
    *******************************/
    notif_scoreUpdated: function (notif) {
      console.log("notif_scoreUpdated", notif);
      const playerId = notif.player_id;
      const score = notif.score;

      // Update the built-in BGA scoreCtrl (displays with star icon in player panel)
      if (this.scoreCtrl && this.scoreCtrl[playerId]) {
        this.scoreCtrl[playerId].toValue(score);
      }
      
      // Also update gamedatas for refresh safety
      if (this.gamedatas && this.gamedatas.players && this.gamedatas.players[playerId]) {
        this.gamedatas.players[playerId].score = score.toString();
      }
    },

  });
});
