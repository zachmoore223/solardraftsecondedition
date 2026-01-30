<?php

/**
 *------
 * BGA framework: Gregory Isabelli & Emmanuel Colin & BoardGameArena
 * SolarDraftSecondEdition implementation : © <Your name here> <Your email address here>
 *
 * This code has been produced on the BGA studio platform for use on http://boardgamearena.com.
 * See http://en.boardgamearena.com/#!doc/Studio for more information.
 * -----
 *
 * Game.php
 *
 * This is the main file for your game logic.
 *
 * In this PHP file, you are going to defines the rules of the game.
 */

declare(strict_types=1);

namespace Bga\Games\solarDraftSecondEdition;

use Bga\Games\SolarDraftSecondEdition\States\PlayerTurn;
use Bga\GameFramework\Components\Counters\PlayerCounter;

class Game extends \Bga\GameFramework\Table
{

    public PlayerCounter $blue_planet_count;
    public PlayerCounter $green_planet_count;
    public PlayerCounter $red_planet_count;
    public PlayerCounter $tan_planet_count;
    public PlayerCounter $comet_count;
    public PlayerCounter $moon_count;
    public PlayerCounter $ring_count;
    public PlayerCounter $draft_actions;
    public PlayerCounter $draw_actions;
    public PlayerCounter $play_actions;
    public PlayerCounter $open_actions;
    public PlayerCounter $solar_flare_used;
    public PlayerCounter $sun_ability_used;
    public PlayerCounter $sun_ability_id; // Stores which ability (1-10) the player has
    public $planetOrder = [];   
    public $cards;
    const LOCATION_DECK = 'deck';
    const LOCATION_DISCARD = 'discardPile';
    const LOCATION_SOLARROW1 = 'solar1';
    const LOCATION_SOLARROW2 = 'solar2';
    const CARD_PLANET = 'planet';
    const CARD_MOON   = 'moon';
    const CARD_COMET  = 'comet';

    // ===== CARD INFO LOOKUP TABLE =====
    public static $CARD_INFO = [
        //declare the 60 planets in base deck
        'planet' => [
            1 => ['name' => 'Tezcatlipoca',      'color' => 'BLUE',  'points' => 0,  'ability' => 'Score 3 points for each ADJACENT COMET.',                                                        'rings' => 3,   'size' => 'MEDIUM', 'moonLimit' => 3,     'moonUnlock'=> true,   'moonUnlockReq'=> 1, 'moonUnlockAbility'=> 'Score 6 points instead.'],
            2 => ['name' => 'Fioon',             'color' => 'BLUE',  'points' => 2,  'ability' => 'Score 3 points for each ADJACENT LARGE PLANET.',                                                 'rings' => 3,   'size' => 'LARGE',  'moonLimit' => 3,     'moonUnlock'=> true,   'moonUnlockReq'=> 1, 'moonUnlockAbility'=> 'Score 6 points instead.'],
            3 => ['name' => 'Masazul',           'color' => 'BLUE',  'points' => 2,  'ability' => 'Score 3 points if you have the single MOST BLUE PLANETS.',                                       'rings' => 0,   'size' => 'LARGE',  'moonLimit' => 3,       'moonUnlock'=> true,   'moonUnlockReq'=> 1, 'moonUnlockAbility'=> 'Score 6 points instead.'],
            4 => ['name' => 'Lunapalooza',       'color' => 'BLUE',  'points' => 2,  'ability' => 'Score 5 points if you have the single MOST MOONS.',                                              'rings' => 1,   'size' => 'MEDIUM', 'moonLimit' => 3,      'moonUnlock'=> true,   'moonUnlockReq'=> 2, 'moonUnlockAbility'=> 'Score 10 points instead.'],
            5 => ['name' => 'Krishna',           'color' => 'BLUE',  'points' => 1,  'ability' => 'Score 1 point for each BLUE PLANET .',                                                           'rings' => 0,   'size' => 'SMALL',  'moonLimit' => 3,       'moonUnlock'=> false,   'moonUnlockReq'=> null, 'moonUnlockAbility'=> null],
            6 => ['name' => 'Hedgehog',          'color' => 'BLUE',  'points' => 1,  'ability' => 'Score 1 point for each PLANET WITH RING(S).',                                                    'rings' => 3,   'size' => 'SMALL',  'moonLimit' => 3,       'moonUnlock'=> false,   'moonUnlockReq'=> null, 'moonUnlockAbility'=> null],
            7 => ['name' => 'Halley',            'color' => 'BLUE',  'points' => 3,  'ability' => 'Score 1 point for each COMET.',                                                                  'rings' => 0,   'size' => 'SMALL',  'moonLimit' => 3,       'moonUnlock'=> false,   'moonUnlockReq'=> null, 'moonUnlockAbility'=> null],
            8 => ['name' => 'Cometviewer',       'color' => 'BLUE',  'points' => 3,  'ability' => 'When played, you may immediately PLAY A COMET.',                                                 'rings' => 3,   'size' => 'SMALL',  'moonLimit' => 3,       'moonUnlock'=> false,   'moonUnlockReq'=> null, 'moonUnlockAbility'=> null],
            9 => ['name' => 'Diluna',            'color' => 'BLUE',  'points' => 1,  'ability' => 'When played, you may immediately PLAY up TWO MOONS onto this planet.',                           'rings' => 2,   'size' => 'SMALL',  'moonLimit' => 3,       'moonUnlock'=> false,   'moonUnlockReq'=> null, 'moonUnlockAbility'=> null],
            10 => ['name' => 'Lone Wolf',        'color' => 'BLUE',  'points' => 2,  'ability' => 'Score 12 points if this is your ONLY MEDIUM PLANET.',                                            'rings' => 0,   'size' => 'MEDIUM',  'moonLimit' => 3,      'moonUnlock'=> false,   'moonUnlockReq'=> null, 'moonUnlockAbility'=> null],
            11 => ['name' => 'Luke',             'color' => 'BLUE',  'points' => 2,  'ability' => 'TRIPLE the POINTS VALUE of the first moon orbiting this planet.',                               'rings' => 2,   'size' => 'MEDIUM',  'moonLimit' => 3,      'moonUnlock'=> false,   'moonUnlockReq'=> null, 'moonUnlockAbility'=> null],
            12 => ['name' => 'Diazure',          'color' => 'BLUE',  'points' => 4,  'ability' => 'This counts as two MEDIUM BLUE PLANETS.',                                                        'rings' => 0,   'size' => 'MEDIUM',  'moonLimit' => 3,      'moonUnlock'=> false,   'moonUnlockReq'=> null, 'moonUnlockAbility'=> null],
            13 => ['name' => 'Octo',             'color' => 'BLUE',  'points' => 1,  'ability' => 'Score 5 points if you have at least 8 PLANETS.',                                                 'rings' => 3,   'size' => 'LARGE',  'moonLimit' => 3,       'moonUnlock'=> false,   'moonUnlockReq'=> null, 'moonUnlockAbility'=> null],
            14 => ['name' => 'Repeat double',    'color' => 'BLUE',  'points' => 2,  'ability' => 'When you play a comet adjacent to this planet, do its effect twice.',                            'rings' => 0,   'size' => 'LARGE',  'moonLimit' => 3,       'moonUnlock'=> false,   'moonUnlockReq'=> null, 'moonUnlockAbility'=> null],
            15 => ['name' => 'Jötnar',           'color' => 'BLUE',  'points' => 2,  'ability' => 'Score 1 point for each LARGE PLANET.',                                                           'rings' => 0,   'size' => 'LARGE',  'moonLimit' => 3,       'moonUnlock'=> false,   'moonUnlockReq'=> null, 'moonUnlockAbility'=> null],
            16 => ['name' => 'cometon',          'color' => 'GREEN', 'points' => 3,  'ability' => 'Score 5 points if you have the single MOST COMETS.',                                             'rings' => 3,   'size' => 'SMALL',  'moonLimit' => 3,       'moonUnlock'=> true,   'moonUnlockReq'=> 1, 'moonUnlockAbility'=> 'Score 10 points instead.'],
            17 => ['name' => 'Threecolored',     'color' => 'GREEN', 'points' => 3,  'ability' => 'Score 5 points if your Solar System only has three different colored planets.',                  'rings' => 1,   'size' => 'MEDIUM',  'moonLimit' => 3,      'moonUnlock'=> true,   'moonUnlockReq'=> 1, 'moonUnlockAbility'=> 'Score 10 points instead.'],
            18 => ['name' => 'Degrassius',       'color' => 'GREEN', 'points' => 2,  'ability' => 'Score 3 points if you have the single MOST GREEN PLANETS.',                                      'rings' => 0,   'size' => 'LARGE',  'moonLimit' => 3,       'moonUnlock'=> true,   'moonUnlockReq'=> 1, 'moonUnlockAbility'=> 'Score 6 points instead.'],
            19 => ['name' => 'Sagan',            'color' => 'GREEN', 'points' => 3,  'ability' => 'Score 5 points if you have the single MOST PLANETS.',                                            'rings' => 2,   'size' => 'LARGE',  'moonLimit' => 3,       'moonUnlock'=> true,   'moonUnlockReq'=> 1, 'moonUnlockAbility'=> 'Score 10 points instead.'],
            20 => ['name' => 'Hathor',           'color' => 'GREEN', 'points' => 1,  'ability' => 'Score 3 points if this is your SEVENTH PLANET.',                                                 'rings' => 2,   'size' => 'SMALL',  'moonLimit' => 3,       'moonUnlock'=> true,   'moonUnlockReq'=> 2, 'moonUnlockAbility'=> 'Score 7 points instead.'],
            21 => ['name' => 'Echo',             'color' => 'GREEN', 'points' => 0,  'ability' => 'Copies the POINTS VALUE and ABILITY of the previously played planet.',                           'rings' => 1,   'size' => 'SMALL',  'moonLimit' => 3,       'moonUnlock'=> false,   'moonUnlockReq'=> null, 'moonUnlockAbility'=> null],
            22 => ['name' => 'Gaia',             'color' => 'GREEN', 'points' => 1,  'ability' => 'Score 1 point for each GREEN PLANET.',                                                           'rings' => 0,   'size' => 'SMALL',  'moonLimit' => 3,       'moonUnlock'=> false,   'moonUnlockReq'=> null, 'moonUnlockAbility'=> null],
            23 => ['name' => 'Gizmo',            'color' => 'GREEN', 'points' => 1,  'ability' => 'Score 1 point for each SMALL PLANET.',                                                           'rings' => 2,   'size' => 'SMALL',  'moonLimit' => 3,       'moonUnlock'=> false,   'moonUnlockReq'=> null, 'moonUnlockAbility'=> null],
            24 => ['name' => 'Goldilocks',       'color' => 'GREEN', 'points' => 3,  'ability' => 'Score 1 point for each MEDIUM PLANET.',                                                          'rings' => 1,   'size' => 'MEDIUM',  'moonLimit' => 3,      'moonUnlock'=> false,   'moonUnlockReq'=> null, 'moonUnlockAbility'=> null],
            25 => ['name' => 'Pluetto',          'color' => 'GREEN', 'points' => 3,  'ability' => 'Score 1 point for each PLANET BEFORE this one.',                                                 'rings' => 3,   'size' => 'MEDIUM',  'moonLimit' => 3,      'moonUnlock'=> false,   'moonUnlockReq'=> null, 'moonUnlockAbility'=> null],
            26 => ['name' => 'Artemiz',          'color' => 'GREEN', 'points' => 3,  'ability' => 'Score 2 points for each MOON orbiting this planet. This planet may have two additional MOONS.',  'rings' => 0,   'size' => 'MEDIUM',  'moonLimit' => 5,      'moonUnlock'=> false,   'moonUnlockReq'=> null, 'moonUnlockAbility'=> null],
            27 => ['name' => 'Diverde',          'color' => 'GREEN', 'points' => 4,  'ability' => 'This counts as two MEDIUM GREEN PLANETS.',                                                       'rings' => 0,   'size' => 'MEDIUM',  'moonLimit' => 3,      'moonUnlock'=> false,   'moonUnlockReq'=> null, 'moonUnlockAbility'=> null],
            28 => ['name' => 'Sasquatch',        'color' => 'GREEN', 'points' => 6,  'ability' => 'NO MOONS may orbit this planet.',                                                                'rings' => 3,   'size' => 'LARGE',  'moonLimit' => 0,       'moonUnlock'=> false,   'moonUnlockReq'=> null, 'moonUnlockAbility'=> null],
            29 => ['name' => 'Gogmagog',         'color' => 'GREEN', 'points' => 8,  'ability' => 'To play this card, you must DISCARD A CARD from your hand.',                                     'rings' => 1,   'size' => 'LARGE',  'moonLimit' => 3,       'moonUnlock'=> false,   'moonUnlockReq'=> null, 'moonUnlockAbility'=> null],
            30 => ['name' => 'Rahu',             'color' => 'GREEN', 'points' => 1,  'ability' => 'TRIPLE the POINTS VALUE of each adjacent COMET.',                                                'rings' => 2,   'size' => 'LARGE',  'moonLimit' => 3,       'moonUnlock'=> false,   'moonUnlockReq'=> null, 'moonUnlockAbility'=> null],
            31 => ['name' => 'Masrojo',          'color' => 'RED',   'points' => 2,  'ability' => 'Score 3 points if you have the single MOST RED PLANETS.',                                        'rings' => 0,   'size' => 'LARGE',  'moonLimit' => 3,       'moonUnlock'=> true,   'moonUnlockReq'=> 1, 'moonUnlockAbility'=> 'Score 6 points instead.'],
            32 => ['name' => 'Planetta',         'color' => 'RED',   'points' => 2,  'ability' => 'Score 2 point for every 2 PLANETS.',                                                             'rings' => 1,   'size' => 'SMALL',  'moonLimit' => 3,       'moonUnlock'=> true,   'moonUnlockReq'=> 1, 'moonUnlockAbility'=> 'Score 3 points instead.'],
            33 => ['name' => 'Bigtin',           'color' => 'RED',   'points' => 2,  'ability' => 'Score 3 points for each  ADJACENT SMALL PLANET.',                                                'rings' => 2,   'size' => 'SMALL',  'moonLimit' => 3,       'moonUnlock'=> true,   'moonUnlockReq'=> 1, 'moonUnlockAbility'=> 'Score 5 points instead.'],
            34 => ['name' => 'Trescom',          'color' => 'RED',   'points' => 3,  'ability' => 'Score 3 points for every 3 COMETS.',                                                             'rings' => 0,   'size' => 'SMALL',  'moonLimit' => 3,       'moonUnlock'=> true,   'moonUnlockReq'=> 1, 'moonUnlockAbility'=> 'Score 6 points instead.'],
            35 => ['name' => 'Ocho',             'color' => 'RED',   'points' => 1,  'ability' => 'Score 4 points if this is your EIGHTH PLANET.',                                                  'rings' => 2,   'size' => 'SMALL',  'moonLimit' => 3,       'moonUnlock'=> true,   'moonUnlockReq'=> 1, 'moonUnlockAbility'=> 'Score 8 points instead.'],
            36 => ['name' => 'Amaterasu',        'color' => 'RED',   'points' => 4,  'ability' => 'Score 5 points for every set of planets you have of each color.',                               'rings' => 2,   'size' => 'MEDIUM',  'moonLimit' => 3,      'moonUnlock'=> true,   'moonUnlockReq'=> 2, 'moonUnlockAbility'=> 'Score 10 points instead.'],
            37 => ['name' => 'Scarlett',         'color' => 'RED',   'points' => 1,  'ability' => 'Score 1 point for each RED PLANET.',                                                             'rings' => 0,   'size' => 'SMALL',  'moonLimit' => 3,       'moonUnlock'=> false,   'moonUnlockReq'=> null, 'moonUnlockAbility'=> null],
            38 => ['name' => 'Trifecten',        'color' => 'RED',   'points' => 0,  'ability' => 'Copy the points and ability of the planet played after this one.',                               'rings' => 3,   'size' => 'MEDIUM',  'moonLimit' => 3,      'moonUnlock'=> false,   'moonUnlockReq'=> null, 'moonUnlockAbility'=> null],
            39 => ['name' => 'Midmed',           'color' => 'RED',   'points' => 2,  'ability' => 'Score 3 points for each ADJACENT MEDIUM PLANET.',                                                'rings' => 1,   'size' => 'MEDIUM',  'moonLimit' => 3,      'moonUnlock'=> false,   'moonUnlockReq'=> null, 'moonUnlockAbility'=> null],
            40 => ['name' => 'Quatro',           'color' => 'RED',   'points' => 1,  'ability' => 'Score 3 points for every other 1 POINT VALUE PLANET.',                                           'rings' => 3,   'size' => 'MEDIUM',  'moonLimit' => 3,      'moonUnlock'=> false,   'moonUnlockReq'=> null, 'moonUnlockAbility'=> null],
            41 => ['name' => 'Dirojo',           'color' => 'RED',   'points' => 4,  'ability' => 'This counts as two MEDIUM RED PLANETS.',                                                         'rings' => 0,   'size' => 'MEDIUM',  'moonLimit' => 3,      'moonUnlock'=> false,   'moonUnlockReq'=> null, 'moonUnlockAbility'=> null],
            42 => ['name' => 'Hectate',          'color' => 'RED',   'points' => 1,  'ability' => 'DOUBLE the POINTS BONUS of one MOON orbiting this planet.',                                      'rings' => 2,   'size' => 'LARGE',  'moonLimit' => 3,       'moonUnlock'=> false,   'moonUnlockReq'=> null, 'moonUnlockAbility'=> null],
            43 => ['name' => 'Lunamania',        'color' => 'RED',   'points' => 1,  'ability' => 'DOUBLE the POINTS scored from the EFFECT of one MOON orbiting this planet.',                    'rings' => 0,   'size' => 'LARGE',  'moonLimit' => 3,       'moonUnlock'=> false,   'moonUnlockReq'=> null, 'moonUnlockAbility'=> null],
            44 => ['name' => 'Lycanthropia',     'color' => 'RED',   'points' => 3,  'ability' => 'Each time you play a MOON onto this planet, DRAFT A CARD.',                                      'rings' => 2,   'size' => 'LARGE',  'moonLimit' => 3,       'moonUnlock'=> false,   'moonUnlockReq'=> null, 'moonUnlockAbility'=> null],
            45 => ['name' => 'Giganta',          'color' => 'RED',   'points' => 10, 'ability' => 'The planet played after this one scores NO POINTS.',                                             'rings' => 3,   'size' => 'LARGE',  'moonLimit' => 3,       'moonUnlock'=> false,   'moonUnlockReq'=> null, 'moonUnlockAbility'=> null],
            46 => ['name' => 'Arthuria',         'color' => 'TAN',   'points' => 2,  'ability' => 'Score 5 points if you have the single MOST total RINGS.',                                        'rings' => 3,   'size' => 'MEDIUM',  'moonLimit' => 3,      'moonUnlock'=> true,   'moonUnlockReq'=> 1, 'moonUnlockAbility'=> 'Score 10 points instead.'],
            47 => ['name' => 'Solbrown',         'color' => 'TAN',   'points' => 2,  'ability' => 'Score 3 points if you have the single MOST TAN PLANETS.',                                        'rings' => 0,   'size' => 'LARGE',  'moonLimit' => 3,       'moonUnlock'=> true,   'moonUnlockReq'=> 1, 'moonUnlockAbility'=> 'Score 6 points instead.'],
            48 => ['name' => 'Rokugan',          'color' => 'TAN',   'points' => 1,  'ability' => 'Score 4 points if this is your NINTH PLANET.',                                                   'rings' => 3,   'size' => 'LARGE',  'moonLimit' => 3,       'moonUnlock'=> true,   'moonUnlockReq'=> 1, 'moonUnlockAbility'=> 'Score 9 points instead.'],
            49 => ['name' => 'Blopper',          'color' => 'TAN',   'points' => 1,  'ability' => 'Score 5 points if this is your ONLY LARGE PLANET.',                                              'rings' => 0,   'size' => 'LARGE',  'moonLimit' => 3,       'moonUnlock'=> true,   'moonUnlockReq'=> 1, 'moonUnlockAbility'=> 'Score 10 points instead.'],
            50 => ['name' => 'Carnival',         'color' => 'TAN',   'points' => 3,  'ability' => 'Score 6 points if this is the ONLY PLANET with AT LEAST 3 RINGS.',                               'rings' => 3,   'size' => 'MEDIUM',  'moonLimit' => 3,      'moonUnlock'=> true,   'moonUnlockReq'=> 2, 'moonUnlockAbility'=> 'Score 12 points instead.'],
            51 => ['name' => 'Baldu',            'color' => 'TAN',   'points' => 7,  'ability' => 'COMETS CANNOT be adjacent to this planet.',                                                      'rings' => 2,   'size' => 'SMALL',  'moonLimit' => 3,       'moonUnlock'=> false,   'moonUnlockReq'=> null, 'moonUnlockAbility'=> null],
            52 => ['name' => 'Lunaria',          'color' => 'TAN',   'points' => 1,  'ability' => 'Each time you play a MOON onto this planet, gain ANOTHER ACTION.',                               'rings' => 1,   'size' => 'SMALL',  'moonLimit' => 3,       'moonUnlock'=> false,   'moonUnlockReq'=> null, 'moonUnlockAbility'=> null],
            53 => ['name' => 'Geb',              'color' => 'TAN',   'points' => 1,  'ability' => 'Score 1 point for each TAN PLANET.',                                                             'rings' => 0,   'size' => 'SMALL',  'moonLimit' => 3,       'moonUnlock'=> false,   'moonUnlockReq'=> null, 'moonUnlockAbility'=> null],
            54 => ['name' => 'Mani',             'color' => 'TAN',   'points' => 4,  'ability' => 'Score 2 points for each PLANET with AT LEAST 1 MOON orbiting it.',                               'rings' => 1,   'size' => 'SMALL',  'moonLimit' => 3,       'moonUnlock'=> false,   'moonUnlockReq'=> null, 'moonUnlockAbility'=> null],
            55 => ['name' => 'Lasten',           'color' => 'TAN',   'points' => 3,  'ability' => 'Score 4 points if this is your LAST PLANET.',                                                    'rings' => 0,   'size' => 'SMALL',  'moonLimit' => 3,       'moonUnlock'=> false,   'moonUnlockReq'=> null, 'moonUnlockAbility'=> null],
            56 => ['name' => 'Threemoona',       'color' => 'TAN',   'points' => 2,  'ability' => 'Score 2 points for each MOON.',                                                                  'rings' => 1,   'size' => 'MEDIUM',  'moonLimit' => 3,      'moonUnlock'=> false,   'moonUnlockReq'=> null, 'moonUnlockAbility'=> null],
            57 => ['name' => 'Merconius',        'color' => 'TAN',   'points' => 1,  'ability' => 'Score 1 point for each PLANET AFTER this one.',                                                  'rings' => 1,   'size' => 'MEDIUM',   'moonLimit' => 3,     'moonUnlock'=> false,   'moonUnlockReq'=> null, 'moonUnlockAbility'=> null],
            58 => ['name' => 'Dimarron',         'color' => 'TAN',   'points' => 4,  'ability' => 'This counts as two MEDIUM TAN PLANETS.',                                                         'rings' => 0,   'size' => 'MEDIUM',   'moonLimit' => 3,     'moonUnlock'=> false,   'moonUnlockReq'=> null, 'moonUnlockAbility'=> null],
            59 => ['name' => 'Ishtar',           'color' => 'TAN',   'points' => 0,  'ability' => 'Each time you play COMET, DRAW A CARD.',                                                         'rings' => 2,   'size' => 'LARGE',  'moonLimit' => 3,       'moonUnlock'=> false,   'moonUnlockReq'=> null, 'moonUnlockAbility'=> null],
            60 => ['name' => 'Echo',             'color' => 'TAN',   'points' => -2, 'ability' => 'Score either the POINT VALUE or ABILITY of an adjacent planet again.',                           'rings' => 3,   'size' => 'LARGE',   'moonLimit' => 3,      'moonUnlock'=> false,   'moonUnlockReq'=> null, 'moonUnlockAbility'=> null],
        ],
            //declare the 25 comets in base deck
        'comet' => [
            61 => [ 'name'=>'Comet1',  'points'=>1, 'ability'=>'DRAFT 2 CARDS.' ],
            62 => [ 'name'=>'Comet2',  'points'=>1, 'ability'=>'DRAFT A CARD and then PLAY A CARD.' ],
            63 => [ 'name'=>'Comet3',  'points'=>1, 'ability'=>'DRAFT A CARD for each bonus token you currently hold.' ],
            64 => [ 'name'=>'Comet4',  'points'=>0, 'ability'=>'DRAFT A CARD for each MOON orbiting adjacent planet.' ],
            65 => [ 'name'=>'Comet5',  'points'=>0, 'ability'=>'DRAFT A CARD for each RING adjacent planet has.' ],
            66 => [ 'name'=>'Comet6',  'points'=>3, 'ability'=>'If able, place the top card of the Solar Deck into your Solar System. Otherwise, draw it.' ],
            67 => [ 'name'=>'Comet7',  'points'=>4, 'ability'=>'DRAW 1 CARD.' ],
            68 => [ 'name'=>'Comet8',  'points'=>3, 'ability'=>'DRAW 2 CARDS.' ],
            69 => [ 'name'=>'Comet9',  'points'=>2, 'ability'=>'DRAW A CARD and then DRAFT A CARD.' ],
            70 => [ 'name'=>'Comet10', 'points'=>2, 'ability'=>'DRAW A CARD and then PLAY A CARD.' ],
            71 => [ 'name'=>'Comet11', 'points'=>1, 'ability'=>'DRAW A CARD for EACH MOON orbiting adjacent planet.' ],
            72 => [ 'name'=>'Comet12', 'points'=>1, 'ability'=>'DRAW A CARD for each RING adjacent planet has.' ],
            73 => [ 'name'=>'Comet13', 'points'=>2, 'ability'=>'Discard a COMET card from the Solar Row and gain its EFFECT.' ],
            74 => [ 'name'=>'Comet14', 'points'=>3, 'ability'=>'DRAW the top card of the discard pile.' ],
            75 => [ 'name'=>'Comet15', 'points'=>1, 'ability'=>'DRAW the top three cards of the discard pile.' ],
            76 => [ 'name'=>'Comet16', 'points'=>2, 'ability'=>'DRAW the top two cards of the discard pile.' ],
            77 => [ 'name'=>'Comet17', 'points'=>2, 'ability'=>'Place a PLANET card from the Solar Row into your Solar System.' ],
            78 => [ 'name'=>'Comet18', 'points'=>1, 'ability'=>'PLAY 2 CARDS.' ],
            79 => [ 'name'=>'Comet19', 'points'=>0, 'ability'=>'PLAY A CARD for each MOON orbiting adjacent planet.' ],
            80 => [ 'name'=>'Comet20', 'points'=>0, 'ability'=>'PLAY A CARD for each RING adjacent planet has.' ],
            81 => [ 'name'=>'Comet21', 'points'=>2, 'ability'=>'Place a MOON card from the Solar Row into your Solar System.' ],
            82 => [ 'name'=>'Comet22', 'points'=>3, 'ability'=>'PLAY A CARD.' ],
            83 => [ 'name'=>'Comet23', 'points'=>2, 'ability'=>'PLAY up to TWO PLANETS.' ],
            84 => [ 'name'=>'Comet24', 'points'=>1, 'ability'=>'REFRESH A SUN ABILITY.' ],
            85 => [ 'name'=>'Comet25', 'points'=>2, 'ability'=>'SOLAR FLARE and then DRAFT A CARD.' ],
            ],
            //declare the 25 moons in base deck
        'moon' => [
            86 => [ 'name'=>'Amigon',  'points'=>2, 'ability'=>'Score 2 points for each MOON orbiting this planet.' ],
            87 => [ 'name'=>'Bigmoona',  'points'=>5, 'ability'=>'-' ],
            88 => [ 'name'=>'Doagain',  'points'=>-1, 'ability'=>'DOUBLE points scored from this planet\'s EFFECT.' ],
            89 => [ 'name'=>'Dosarta',  'points'=>2, 'ability'=>'Score 2 points if this is the SECOND MOON orbiting this planet.' ],
            90 => [ 'name'=>'Gigamoon',  'points'=>6, 'ability'=>'May only be played on your most recently played planet.' ],
            91 => [ 'name'=>'Longway',  'points'=>4, 'ability'=>'Score 1 point for each COMET adjacent to the planet this moon orbits.' ],
            92 => [ 'name'=>'New1',  'points'=>2, 'ability'=>'PLAY A COMET.' ],
            93 => [ 'name'=>'New2',  'points'=>2, 'ability'=>'DRAFT A CARD.' ],
            94 => [ 'name'=>'New3',  'points'=>4, 'ability'=>'DRAW A CARD.' ],
            95 => [ 'name'=>'New4',  'points'=>1, 'ability'=>'Score 1 point for each RED planet.' ],
            96 => [ 'name'=>'New5',  'points'=>1, 'ability'=>'Score 1 point for each BLUE planet.' ],
            97 => [ 'name'=>'New6',  'points'=>1, 'ability'=>'Score 1 point for each GREEN planet.' ],
            98 => [ 'name'=>'New7',  'points'=>1, 'ability'=>'Score 1 point for each TAN planet.' ],
            99 => [ 'name'=>'New8',  'points'=>4, 'ability'=>'This planet may have an additional MOON orbiting it.' ],
            100 => [ 'name'=>'New9', 'points'=>1, 'ability'=>'Score 5 points if this planet is only planet of its color.' ],
            101 => [ 'name'=>'New10','points'=>3, 'ability'=>'Score 1 point for each panet with at least one MOON.' ],
            102 => [ 'name'=>'Qixx','points'=>3, 'ability'=>'PLAY A MOON.' ],
            103 => [ 'name'=>'Ringis','points'=>1, 'ability'=>'Score 2 point for each RING this planet has.' ],
            104 => [ 'name'=>'Smallway','points'=>3, 'ability'=>'Score 1 point for EACH CARD in your hand.' ],
            105 => [ 'name'=>'Solmoon','points'=>2, 'ability'=>'This planet may have two additional MOONS orbiting it.' ],
            106 => [ 'name'=>'Timestooer','points'=>0, 'ability'=>'DOUBLE the POINT VALUE of this planet. May only be played on a SMALL or MEDIUM planet.' ],
            107 => [ 'name'=>'Tresarta','points'=>3, 'ability'=>'Score 3 points if this is the THIRD MOON orbiting this planet.' ],
            108 => [ 'name'=>'Unarta','points'=>6, 'ability'=>'May only be played if it\'s the THIRD MOON orbiting this planet.' ],
            109 => [ 'name'=>'Xiq','points'=>1, 'ability'=>'PLAY A CARD.' ],
            110 => [ 'name'=>'ColorDude','points'=>1, 'ability'=>'Score 1 point for each other planet of this planet\'s color.' ],
            ],
    ];

    /**
     * Your global variables labels:
     *
     * Here, you can assign labels to global variables you are using for this game. You can use any number of global
     * variables with IDs between 10 and 99. If you want to store any type instead of int, use $this->globals instead.
     *
     * NOTE: afterward, you can get/set the global variables with `getGameStateValue`, `setGameStateInitialValue` or
     * `setGameStateValue` functions.
     */
    public function __construct()
    {
        parent::__construct();

        $this->initGameStateLabels([
            'pending_moon_card_id' => 10,
            'shell_star_active' => 11, // Player ID for Shell Star ability (only moons can be played)
            'last_turn_player' => 12, // Track the last player who received starting action
            'deck_empty' => 13, // Flag: 1 if deck is empty (game ending), 0 otherwise
            'last_card_drawer' => 14, // Player ID who drew the last card
            'draw_from_discard_only' => 15, // Player ID who must draw from discard pile (0 = none)
        ]); // mandatory, even if the array is empty

        $this->blue_planet_count = $this->counterFactory->createPlayerCounter('blue_planet_count');
        $this->green_planet_count = $this->counterFactory->createPlayerCounter('green_planet_count');
        $this->red_planet_count = $this->counterFactory->createPlayerCounter('red_planet_count');
        $this->tan_planet_count = $this->counterFactory->createPlayerCounter('tan_planet_count');
        $this->comet_count = $this->counterFactory->createPlayerCounter('comet_count');
        $this->moon_count = $this->counterFactory->createPlayerCounter('moon_count');
        $this->ring_count = $this->counterFactory->createPlayerCounter('ring_count');
        $this->cards = $this->deckFactory->createDeck('card');
        $this->cards->init('card');
        // ACTION COUNTERS
        $this->open_actions = $this->counterFactory->createPlayerCounter('open_actions');
        $this->draft_actions = $this->counterFactory->createPlayerCounter('draft_actions');
        $this->draw_actions = $this->counterFactory->createPlayerCounter('draw_actions');
        $this->play_actions = $this->counterFactory->createPlayerCounter('play_actions');
        // SUN ABILITY TRACKING (0 = available, 1 = used)
        $this->solar_flare_used = $this->counterFactory->createPlayerCounter('solar_flare_used');
        $this->sun_ability_used = $this->counterFactory->createPlayerCounter('sun_ability_used');
        // SUN ABILITY ID (1-10) mapping:
        // 1=Shell Star, 2=Binary Star, 3=Quasar, 4=Supernova, 5=Neutron Star,
        // 6=Ternary Star, 7=Pulsar, 8=Super Star, 9=Protostar, 10=Red Dwarf
        $this->sun_ability_id = $this->counterFactory->createPlayerCounter('sun_ability_id');
    
    }

    /**
     * Compute and return the current game progression.
     *
     * The number returned must be an integer between 0 and 100.
     *
     * This method is called each time we are in a game state with the "updateGameProgression" property set to true.
     *
     * @return int
     * @see ./states.inc.php
     */
    public function getGameProgression()
    {
        // TODO: compute and return the game progression

        return 0;
    }

    /**
     * Migrate database.
     *
     * You don't have to care about this until your game has been published on BGA. Once your game is on BGA, this
     * method is called everytime the system detects a game running with your old database scheme. In this case, if you
     * change your database scheme, you just have to apply the needed changes in order to update the game database and
     * allow the game to continue to run with your new version.
     *
     * @param int $from_version
     * @return void
     */
    public function upgradeTableDb($from_version)
    {
        //       if ($from_version <= 1404301345)
        //       {
        //            // ! important ! Use `DBPREFIX_<table_name>` for all tables
        //
        //            $sql = "ALTER TABLE `DBPREFIX_xxxxxxx` ....";
        //            $this->applyDbUpgradeToAllDB( $sql );
        //       }
        //
        //       if ($from_version <= 1405061421)
        //       {
        //            // ! important ! Use `DBPREFIX_<table_name>` for all tables
        //
        //            $sql = "CREATE TABLE `DBPREFIX_xxxxxxx` ....";
        //            $this->applyDbUpgradeToAllDB( $sql );
        //       }
    }

    /*
     * Gather all information about current game situation (visible by the current player).
     *
     * The method is called each time the game interface is displayed to a player, i.e.:
     *
     * - when the game starts
     * - when a player refreshes the game page (F5)
     */
        protected function getAllDatas(): array
        {
            $result = [];

            $current_player_id = (int) $this->getCurrentPlayerId();

            // ----------------------
            // PLAYERS
            // ----------------------
            $result["players"] = $this->getCollectionFromDb(
                "SELECT `player_id` `id`, `player_score` `score` FROM `player`"
            );
            
            $this->blue_planet_count->fillResult($result);
            $this->green_planet_count->fillResult($result);
            $this->red_planet_count->fillResult($result);
            $this->tan_planet_count->fillResult($result);
            $this->comet_count->fillResult($result);
            $this->moon_count->fillResult($result);
            $this->ring_count->fillResult($result);
            $this->open_actions->fillResult($result);
            $this->draft_actions->fillResult($result);
            $this->draw_actions->fillResult($result);
            $this->play_actions->fillResult($result);
            $this->solar_flare_used->fillResult($result);
            $this->sun_ability_used->fillResult($result);
            $this->sun_ability_id->fillResult($result);
            
            // Add sun ability info to each player (convert ID to name)
            foreach ($result['players'] as $playerId => $player) {
                $abilityId = $this->sun_ability_id->get($playerId);
                $result['players'][$playerId]['sun_ability'] = $this->getSunAbilityName($abilityId);
                $result['players'][$playerId]['sun_ability_used'] = $this->sun_ability_used->get($playerId) == 1;
            }

            // ----------------------
            // TABLEAU (planet / moon / comet)
            // ----------------------
            $result['tableau'] = [];
            foreach ($result['players'] as $p_id => $player) {

                $cards = $this->getCollectionFromDb(
                    "SELECT
                        card_id AS id,
                        card_type AS type,
                        card_type_arg AS type_arg,
                        card_location AS location,
                        card_location_arg AS location_arg,
                        parent_id,
                        parent_slot,
                        planet_order
                    FROM card
                    WHERE card_location = 'tableau'
                    AND card_location_arg = $p_id
                    ORDER BY card_id ASC"
                );


                // Add parent info + enrich sprite info
                $cards = array_map(function ($c) {
                    return [
                        'id'            => (int)$c['id'],
                        'type'          => $c['type'],
                        'type_arg'      => (int)$c['type_arg'],
                        'location'      => $c['location'],
                        'location_arg'  => (int)$c['location_arg'],
                        'parent_id'     => $c['parent_id'] ? (int)$c['parent_id'] : null,
                        'parent_slot'   => $c['parent_slot'] ? (int)$c['parent_slot'] : null,
                        'planet_order'   => $c['planet_order'] ? (int)$c['planet_order'] : null,
                    ];
                }, $cards);

                $result['tableau'][$p_id] = $this->enrichCards($cards);
            }

            
            // ----------------------
            // HAND COUNTS
            // ----------------------
            $result['cardsInHand'] = [];
            foreach ($result['players'] as $p_id => $player) {
                $result['cardsInHand'][$p_id] =
                    $this->cards->countCardsInLocation('hand', $p_id);
            }

            // ----------------------
            // DECK / DISCARD
            // ----------------------
            $discardPile = $this->cards->getCardsInLocation(self::LOCATION_DISCARD);
            $top = $this->cards->getCardOnTop(self::LOCATION_DECK);

            $result['cardsInDiscard'] = $this->cards->countCardsInLocation(self::LOCATION_DISCARD);
            $result['cardsRemaining'] = $this->cards->countCardsInLocation('deck');
            $result['deckTop'] = $top ? $this->enrichCard($top) : null;
            $result['hand'] = $this->enrichCards(
                $this->cards->getCardsInLocation('hand', $current_player_id)
            );
            $result['discardPile'] = $this->enrichCards($discardPile);

            // ----------------------
            // SOLAR ROWS
            // ----------------------
            $solarRow1 = $this->cards->getCardsInLocation(self::LOCATION_SOLARROW1);
            $solarRow2 = $this->cards->getCardsInLocation(self::LOCATION_SOLARROW2);

            $solarRow1Slots = [null, null, null];
            $solarRow2Slots = [null, null, null];

            foreach ($solarRow1 as $card) {
                $slot = intval($card['location_arg']);
                $solarRow1Slots[$slot] = $this->enrichCard($card);
            }

            foreach ($solarRow2 as $card) {
                $slot = intval($card['location_arg']);
                $solarRow2Slots[$slot] = $this->enrichCard($card);
            }

            $result['solarRow1'] = $solarRow1Slots;
            $result['solarRow2'] = $solarRow2Slots;

            return $result;
        }


    /**
     * This method is called only once, when a new game is launched. In this method, you must setup the game
     *  according to the game rules, so that the game is ready to be played.
     */
    protected function setupNewGame($players, $options = [])
    {   
        //put all counters into the DB
        $this->blue_planet_count->initDb(array_keys($players));
        $this->green_planet_count->initDb(array_keys($players));
        $this->red_planet_count->initDb(array_keys($players));
        $this->tan_planet_count->initDb(array_keys($players));
        $this->comet_count->initDb(array_keys($players));
        $this->moon_count->initDb(array_keys($players));
        $this->ring_count->initDb(array_keys($players)); 
        $this->open_actions->initDb(array_keys($players));
        $this->draft_actions->initDb(array_keys($players));
        $this->draw_actions->initDb(array_keys($players));
        $this->play_actions->initDb(array_keys($players));
        $this->solar_flare_used->initDb(array_keys($players));
        $this->sun_ability_used->initDb(array_keys($players));
        $this->sun_ability_id->initDb(array_keys($players));
        
        // Randomly assign one sun ability to each player (stored as ID 1-10)
        // Ability mapping: 1=Shell Star, 2=Binary Star, 3=Quasar, 4=Supernova, 5=Neutron Star,
        // 6=Ternary Star, 7=Pulsar, 8=Super Star, 9=Protostar, 10=Red Dwarf
        $abilityIds = [1, 2, 3, 4, 5, 6, 7, 8, 9, 10];
        shuffle($abilityIds);
        
        $playerIds = array_keys($players);
        foreach ($playerIds as $idx => $playerId) {
            $abilityId = $abilityIds[$idx % count($abilityIds)];
            $this->sun_ability_id->set($playerId, $abilityId);
        }
        
        // Set the colors of the players with HTML color code. The default below is red/green/blue/orange/brown. The
        // number of colors defined here must correspond to the maximum number of players allowed for the game.
        $gameinfos = $this->getGameinfos();
        $default_colors = $gameinfos['player_colors'];

        foreach ($players as $player_id => $player) {
            // Now you can access both $player_id and $player array
            $query_values[] = vsprintf("('%s', '%s', '%s', '%s', '%s')", [
                $player_id,
                array_shift($default_colors),
                $player["player_canal"],
                addslashes($player["player_name"]),
                addslashes($player["player_avatar"]),                
            ]);
        }

        // Create players based on generic information.
        //
        // NOTE: You can add extra field on player table in the database (see dbmodel.sql) and initialize
        // additional fields directly here.
        static::DbQuery(
            sprintf(
                "INSERT INTO player (player_id, player_color, player_canal, player_name, player_avatar) VALUES %s",
                implode(",", $query_values)
            )
        );

        $this->reattributeColorsBasedOnPreferences($players, $gameinfos["player_colors"]);
        $this->reloadPlayersBasicInfos();

        // Init global values with their initial values.

        // Init game statistics.
        //
        // NOTE: statistics used in this file must be defined in your `stats.inc.php` file.

        // Dummy content.
        // $this->tableStats->init('table_teststat1', 0);
        // $this->playerStats->init('player_teststat1', 0);

        // TODO: Setup the initial game situation here.


        /*******************************
        *           SOLAR DECK         *
        *******************************/
        $playerCount = count($players);

        //create pools of all cards
        $planets = [];
        $comets = [];
        $moons = [];

        //set card counts according to player count
        if ($playerCount == 2){
            $numOfPlanets = 30;
            $numOfComets = 10;
            $numOfMoons = 10;
        } 
        else if ($playerCount == 3) {
            $numOfPlanets = 40;
            $numOfComets = 15;
            $numOfMoons = 15;
        } 
        else if ($playerCount == 4) {
            $numOfPlanets = 50;
            $numOfComets = 20;
            $numOfMoons = 20;
        }
        else { // 5 players
            $numOfPlanets = 60;
            $numOfComets = 25;
            $numOfMoons = 25;
        }

        // ---------- PLANETS 1-60 ----------
        for ($i = 1; $i <= 60; $i++) {
            $planets[] = [
                'type' => 'planet',
                'type_arg' => $i,
                'nbr' => 1
            ];
        }

        // ---------- COMETS 61-85 ----------
        for ($i = 61; $i <= 85; $i++) {
            $comets[] = [
                'type' => 'comet',
                'type_arg' => $i,
                'nbr' => 1
            ];
        }

        // ---------- MOONS 86-110 ----------
        for ($i = 86; $i <= 110; $i++) {
            $moons[] = [
                'type' => 'moon',
                'type_arg' => $i,
                'nbr' => 1
            ];
        }

        // Shuffle each pool
        shuffle($planets);
        shuffle($comets);
        shuffle($moons);

        /*******************************
        *     PLAYER COUNT SETUP       *
        *******************************/
        // Select the subset needed for this player count
        $selectedPlanets = array_slice($planets, 0, $numOfPlanets);
        $selectedComets  = array_slice($comets, 0, $numOfComets);
        $selectedMoons   = array_slice($moons, 0, $numOfMoons);

        // Deal starting hands: 1 random planet, comet, moon per player
        $startingPlanets = array_splice($selectedPlanets, 0, $playerCount);
        $startingComets  = array_splice($selectedComets, 0, $playerCount);
        $startingMoons   = array_splice($selectedMoons, 0, $playerCount);

        // Prepare discard pile: 1 of each type
        $discardPlanet = array_shift($selectedPlanets);
        $discardComet  = array_shift($selectedComets);
        $discardMoon   = array_shift($selectedMoons);

        // Create ALL selected cards in the deck first (including starting hands and discard)
        $allCards = array_merge(
            $startingPlanets, 
            $startingComets, 
            $startingMoons,
            ($discardPlanet ? [$discardPlanet] : []),
            ($discardComet ? [$discardComet] : []),
            ($discardMoon ? [$discardMoon] : []),
            $selectedPlanets, 
            $selectedComets, 
            $selectedMoons
        );
        
        $this->cards->createCards($allCards, 'deck');
        $this->cards->shuffle('deck');

        /*******************************
        *          PLAYER HANDS        *
        *******************************/
        // Move starting cards to players' hands
        foreach ($players as $idx => $player) {
            $pid = (int)$idx;
            $pCard = array_shift($startingPlanets);
            $cCard = array_shift($startingComets);
            $mCard = array_shift($startingMoons);

            if ($pCard) {
                $cards = $this->cards->getCardsOfType($pCard['type'], $pCard['type_arg'], 'deck');
                if (!empty($cards)) {
                    $card = array_values($cards)[0];
                    $this->cards->moveCard($card['id'], 'hand', $pid);
                }
            }
            if ($cCard) {
                $cards = $this->cards->getCardsOfType($cCard['type'], $cCard['type_arg'], 'deck');
                if (!empty($cards)) {
                    $card = array_values($cards)[0];
                    $this->cards->moveCard($card['id'], 'hand', $pid);
                }
            }
            if ($mCard) {
                $cards = $this->cards->getCardsOfType($mCard['type'], $mCard['type_arg'], 'deck');
                if (!empty($cards)) {
                    $card = array_values($cards)[0];
                    $this->cards->moveCard($card['id'], 'hand', $pid);
                }
            }
        }
        /*******************************
        *          DISCARD PILE        *
        *******************************/
        // Move one of each type to the discard pile
        if ($discardPlanet) {
            $cards = $this->cards->getCardsOfType($discardPlanet['type'], $discardPlanet['type_arg'], 'deck');
            if (!empty($cards)) {
                $card = array_values($cards)[0];
                $this->cards->moveCard($card['id'], self::LOCATION_DISCARD, 3);
            }
        }
        if ($discardComet) {
            $cards = $this->cards->getCardsOfType($discardComet['type'], $discardComet['type_arg'], 'deck');
            if (!empty($cards)) {
                $card = array_values($cards)[0];
                $this->cards->moveCard($card['id'], self::LOCATION_DISCARD, 3);
            }
        }
        if ($discardMoon) {
            $cards = $this->cards->getCardsOfType($discardMoon['type'], $discardMoon['type_arg'], 'deck');
            if (!empty($cards)) {
                $card = array_values($cards)[0];
                $this->cards->moveCard($card['id'], self::LOCATION_DISCARD, 3);
            }
        }


        /*******************************
        *           SOLAR ROWS         *
        *******************************/
        for ($slot = 0; $slot < 3; $slot++) {
            $this->cards->pickCardForLocation('deck', self::LOCATION_SOLARROW1, $slot);
            $this->cards->pickCardForLocation('deck', self::LOCATION_SOLARROW2, $slot);
        }

        // Activate first player once everything has been initialized and ready.
        $this->activeNextPlayer();

        return PlayerTurn::class;
    }

    /**
     * Example of debug function.
     * Here, jump to a state you want to test (by default, jump to next player state)
     * You can trigger it on Studio using the Debug button on the right of the top bar.
     */
    public function debug_goToState(int $state = 3)
    {
        $this->gamestate->jumpToState($state);
    }

    /**
     * Another example of debug function, to easily test the zombie code.
     */
    public function debug_playAutomatically(int $moves = 50)
    {
        $count = 0;
        while (intval($this->gamestate->getCurrentMainStateId()) < 99 && $count < $moves) {
            $count++;
            foreach ($this->gamestate->getActivePlayerList() as $playerId) {
                $playerId = (int)$playerId;
                $this->gamestate->runStateClassZombie($this->gamestate->getCurrentState($playerId), $playerId);
            }
        }
    }

    /*
    Another example of debug function, to easily create situations you want to test.
    Here, put a card you want to test in your hand (assuming you use the Deck component).

    public function debug_setCardInHand(int $cardType, int $playerId) {
        $card = array_values($this->cards->getCardsOfType($cardType))[0];
        $this->cards->moveCard($card['id'], 'hand', $playerId);
    }     
    */


    /*************************************************
     *               CARD INFO HELPERS                
     *************************************************/

    /**
     * Get sun ability name from ID
     */
    public function getSunAbilityName(int $abilityId): ?string
    {
        $abilities = [
            1 => 'Shell Star',
            2 => 'Binary Star',
            3 => 'Quasar',
            4 => 'Supernova',
            5 => 'Neutron Star',
            6 => 'Ternary Star',
            7 => 'Pulsar',
            8 => 'Super Star',
            9 => 'Protostar',
            10 => 'Red Dwarf'
        ];
        return $abilities[$abilityId] ?? null;
    }

    /**
     * Get sun ability ID from name
     */
    public function getSunAbilityId(string $abilityName): ?int
    {
        $abilities = [
            'Shell Star' => 1,
            'Binary Star' => 2,
            'Quasar' => 3,
            'Supernova' => 4,
            'Neutron Star' => 5,
            'Ternary Star' => 6,
            'Pulsar' => 7,
            'Super Star' => 8,
            'Protostar' => 9,
            'Red Dwarf' => 10
        ];
        return $abilities[$abilityName] ?? null;
    }

    //Get full card info (name, points, ability, etc.)
    public function getCardInfo($card)
    {
        return self::$CARD_INFO[$card['type']][$card['type_arg']];
    }

    //Get the display name of a card
    public function getCardName($card)
    {
        return self::$CARD_INFO[$card['type']][$card['type_arg']]['name'];
    }

    //Get the points for a card (if you store points)
    public function getCardPoints($card)
    {
        return self::$CARD_INFO[$card['type']][$card['type_arg']]['points'] ?? null;
    }
    //Get a card's ability text, if it has one
    public function getCardAbility($card)
    {
        return self::$CARD_INFO[$card['type']][$card['type_arg']]['ability'] ?? null;
    }

    //Attach name/points/ability to a card array before sending to client
    public function enrichCard($card)
    {
        $info = $this->getCardInfo($card);

        $card['name'] = $info['name'] ?? null;
        $card['color'] = $info['color'] ?? null;
        $card['points'] = $info['points'] ?? null;
        $card['rings'] = $info['rings'] ?? null;
        $card['ability'] = $info['ability'] ?? null;
        $card['moonLimit'] = $info['moonLimit'] ?? null;
        $card['moonUnlock'] = $info['moonUnlock'] ?? null;
        $card['moonUnlockReq'] = $info['moonUnlockReq'] ?? null;
        $card['moonUnlockAbility'] = $info['moonUnlockAbility'] ?? null;

        return $card;
    }

    //Enrich a list of cards
    public function enrichCards($cards)
    {
        foreach ($cards as &$card) {
            $card = $this->enrichCard($card);
        }
        return $cards;
    }

    /*************************************************
* SCORING HELPERS
     *************************************************/

    /**
     * Get all cards in a player's tableau, organized by type
     */
    private function getTableauCards(int $playerId): array
    {
        // Use the same SQL query approach as getAllDatas() to ensure consistent format
        $cards = $this->getCollectionFromDb(
            "SELECT
                card_id AS id,
                card_type AS type,
                card_type_arg AS type_arg,
                card_location AS location,
                card_location_arg AS location_arg,
                parent_id,
                parent_slot,
                planet_order
            FROM card
            WHERE card_location = 'tableau'
            AND card_location_arg = $playerId
            ORDER BY card_id ASC"
        );
        
        $result = [
            'all' => [],
            'planets' => [],
            'moons' => [],
            'comets' => [],
        ];
        
        // Map and enrich cards
        foreach ($cards as $card) {
            $mappedCard = [
                'id'            => (int)$card['id'],
                'type'          => $card['type'],
                'type_arg'      => (int)$card['type_arg'],
                'location'      => $card['location'],
                'location_arg'  => (int)$card['location_arg'],
                'parent_id'     => $card['parent_id'] ? (int)$card['parent_id'] : null,
                'parent_slot'   => $card['parent_slot'] ? (int)$card['parent_slot'] : null,
                'planet_order'  => $card['planet_order'] ? (int)$card['planet_order'] : null,
            ];
            
            $enrichedCard = $this->enrichCard($mappedCard);
            $result['all'][] = $enrichedCard;
            
            if ($enrichedCard['type'] === 'planet') {
                $result['planets'][] = $enrichedCard;
            } elseif ($enrichedCard['type'] === 'moon') {
                $result['moons'][] = $enrichedCard;
            } elseif ($enrichedCard['type'] === 'comet') {
                $result['comets'][] = $enrichedCard;
            }
        }
        
        // Sort planets by planet_order
        usort($result['planets'], function($a, $b) {
            return ($a['planet_order'] ?? 0) <=> ($b['planet_order'] ?? 0);
        });
        
        return $result;
    }

    /**
     * Get adjacent planets for a given planet
     */
    private function getAdjacentPlanets(array $planet, array $allPlanets): array
    {
        $adjacent = [];
        $planetOrder = $planet['planet_order'] ?? null;
        
        if ($planetOrder === null) {
            return [];
        }
        
        foreach ($allPlanets as $p) {
            $pOrder = $p['planet_order'] ?? null;
            if ($pOrder !== null && abs($pOrder - $planetOrder) === 1) {
                $adjacent[] = $p;
            }
        }
        
        return $adjacent;
    }

    /**
     * Get comets adjacent to a planet
     */
    private function getAdjacentComets(array $planet, array $allComets): array
    {
        $adjacent = [];
        $planetId = $planet['id'];
        
        foreach ($allComets as $comet) {
            if (($comet['parent_id'] ?? null) == $planetId) {
                $adjacent[] = $comet;
            }
        }
        
        return $adjacent;
    }

    /**
     * Get moons orbiting a planet
     */
    private function getMoonsForPlanet(int $planetId, array $allMoons): array
    {
        $moons = [];
        foreach ($allMoons as $moon) {
            if (($moon['parent_id'] ?? null) == $planetId) {
                $moons[] = $moon;
            }
        }
        // Sort by parent_slot to maintain order
        usort($moons, function($a, $b) {
            return ($a['parent_slot'] ?? 0) <=> ($b['parent_slot'] ?? 0);
        });
        return $moons;
    }

    /**
     * Check if player has most of a type (color, size, etc.) compared to all players
     */
    private function hasMostOfType(int $playerId, string $type, string $value): bool
    {
        $players = $this->getCollectionFromDb("SELECT player_id FROM player");
        $counts = [];
        
        foreach ($players as $pId => $p) {
            $counts[$pId] = $this->countCardsByType((int)$pId, $type, $value);
        }
        
        $myCount = $counts[$playerId] ?? 0;
        $maxCount = max($counts);
        
        // Must be strictly the most (tie = false)
        return $myCount === $maxCount && array_count_values($counts)[$maxCount] === 1;
    }

    /**
     * Count cards by type and value (color, size, etc.)
     */
    private function countCardsByType(int $playerId, string $type, string $value): int
    {
        $cards = $this->getTableauCards($playerId);
        $count = 0;
        
        if ($type === 'color') {
            foreach ($cards['planets'] as $planet) {
                $cardInfo = $this->getCardInfo($planet);
                if (($cardInfo['color'] ?? '') === $value) {
                    $count++;
                    // Check for double-counting planets
                    if ($cardInfo['name'] === 'Diazure' && $value === 'BLUE') {
                        $count++; // Counts as 2 blue planets
                    } elseif ($cardInfo['name'] === 'Diverde' && $value === 'GREEN') {
                        $count++; // Counts as 2 green planets
                    } elseif ($cardInfo['name'] === 'Dirojo' && $value === 'RED') {
                        $count++; // Counts as 2 red planets
                    } elseif ($cardInfo['name'] === 'Dimarron' && $value === 'TAN') {
                        $count++; // Counts as 2 tan planets
                    }
                }
            }
        } elseif ($type === 'size') {
            foreach ($cards['planets'] as $planet) {
                $cardInfo = $this->getCardInfo($planet);
                if (($cardInfo['size'] ?? '') === $value) {
                    $count++;
                }
            }
        } elseif ($type === 'total_planets') {
            $count = count($cards['planets']);
        } elseif ($type === 'total_moons') {
            $count = count($cards['moons']);
        } elseif ($type === 'total_comets') {
            $count = count($cards['comets']);
        } elseif ($type === 'total_rings') {
            foreach ($cards['planets'] as $planet) {
                $cardInfo = $this->getCardInfo($planet);
                $count += (int)($cardInfo['rings'] ?? 0);
            }
        }
        
        return $count;
    }

    /**
     * Calculate basic score for a player (just points values of cards)
     */
    public function calculateBasicScore(int $playerId): int
    {
        $score = 0;
        $cards = $this->getTableauCards($playerId);
        
        // Sum up points from planets, moons, and comets
        // Cards are already enriched in getTableauCards()
        foreach ($cards['all'] as $card) {
            // Points are already added to card by enrichCard()
            if (isset($card['points']) && is_numeric($card['points'])) {
                $points = (int)$card['points'];
                $score += $points;
            }
        }
        
        return $score;
    }

    /**
     * Calculate ability-based score for a planet
     * @param int $depth Used to prevent infinite recursion with Echo/Trifecten
     */
    private function scorePlanetAbility(array $planet, array $tableau, int $playerId, int $depth = 0): int
    {
        // Prevent infinite recursion
        if ($depth > 10) {
            return 0;
        }
        
        $cardInfo = $this->getCardInfo($planet);
        if (!$cardInfo) {
            return 0; // Card info not found
        }
        
        $ability = $cardInfo['ability'] ?? '';
        if (empty($ability) || $ability === '-') {
            return 0; // No ability to score
        }
        
        $score = 0;
        
        // Check for moon unlock
        $moons = $this->getMoonsForPlanet($planet['id'], $tableau['moons']);
        $hasMoonUnlock = false;
        $moonUnlockAbility = null;
        
        if (($cardInfo['moonUnlock'] ?? false) && count($moons) >= ($cardInfo['moonUnlockReq'] ?? 0)) {
            $hasMoonUnlock = true;
            $moonUnlockAbility = $cardInfo['moonUnlockAbility'] ?? null;
        }
        
        // Use moon unlock ability if available
        if ($hasMoonUnlock && $moonUnlockAbility) {
            $ability = $moonUnlockAbility;
        }
        
        // Check for Doagain moon (doubles planet ability)
        $hasDoagain = false;
        foreach ($moons as $moon) {
            $moonInfo = $this->getCardInfo($moon);
            if ($moonInfo['name'] === 'Doagain') {
                $hasDoagain = true;
                break;
            }
        }
        
        // Parse and score ability
        if (strpos($ability, 'ADJACENT COMET') !== false) {
            $adjacentComets = $this->getAdjacentComets($planet, $tableau['comets']);
            $count = count($adjacentComets);
            if (preg_match('/(\d+) points? for each/', $ability, $matches)) {
                $pointsPer = (int)$matches[1];
                $score += $pointsPer * $count;
            }
        } elseif (strpos($ability, 'ADJACENT LARGE PLANET') !== false) {
            $adjacentPlanets = $this->getAdjacentPlanets($planet, $tableau['planets']);
            $count = 0;
            foreach ($adjacentPlanets as $adjPlanet) {
                $adjInfo = $this->getCardInfo($adjPlanet);
                if (($adjInfo['size'] ?? '') === 'LARGE') {
                    $count++;
                }
            }
            if (preg_match('/(\d+) points? for each/', $ability, $matches)) {
                $pointsPer = (int)$matches[1];
                $score += $pointsPer * $count;
            }
        } elseif (strpos($ability, 'ADJACENT SMALL PLANET') !== false) {
            $adjacentPlanets = $this->getAdjacentPlanets($planet, $tableau['planets']);
            $count = 0;
            foreach ($adjacentPlanets as $adjPlanet) {
                $adjInfo = $this->getCardInfo($adjPlanet);
                if (($adjInfo['size'] ?? '') === 'SMALL') {
                    $count++;
                }
            }
            if (preg_match('/(\d+) points? for each/', $ability, $matches)) {
                $pointsPer = (int)$matches[1];
                $score += $pointsPer * $count;
            }
        } elseif (strpos($ability, 'ADJACENT MEDIUM PLANET') !== false) {
            $adjacentPlanets = $this->getAdjacentPlanets($planet, $tableau['planets']);
            $count = 0;
            foreach ($adjacentPlanets as $adjPlanet) {
                $adjInfo = $this->getCardInfo($adjPlanet);
                if (($adjInfo['size'] ?? '') === 'MEDIUM') {
                    $count++;
                }
            }
            if (preg_match('/(\d+) points? for each/', $ability, $matches)) {
                $pointsPer = (int)$matches[1];
                $score += $pointsPer * $count;
            }
        } elseif (strpos($ability, 'MOST BLUE PLANETS') !== false) {
            if ($this->hasMostOfType($playerId, 'color', 'BLUE')) {
                if (preg_match('/(\d+) points?/', $ability, $matches)) {
                    $score += (int)$matches[1];
                }
            }
        } elseif (strpos($ability, 'MOST GREEN PLANETS') !== false) {
            if ($this->hasMostOfType($playerId, 'color', 'GREEN')) {
                if (preg_match('/(\d+) points?/', $ability, $matches)) {
                    $score += (int)$matches[1];
                }
            }
        } elseif (strpos($ability, 'MOST RED PLANETS') !== false) {
            if ($this->hasMostOfType($playerId, 'color', 'RED')) {
                if (preg_match('/(\d+) points?/', $ability, $matches)) {
                    $score += (int)$matches[1];
                }
            }
        } elseif (strpos($ability, 'MOST TAN PLANETS') !== false) {
            if ($this->hasMostOfType($playerId, 'color', 'TAN')) {
                if (preg_match('/(\d+) points?/', $ability, $matches)) {
                    $score += (int)$matches[1];
                }
            }
        } elseif (strpos($ability, 'MOST MOONS') !== false) {
            if ($this->hasMostOfType($playerId, 'total_moons', '')) {
                if (preg_match('/(\d+) points?/', $ability, $matches)) {
                    $score += (int)$matches[1];
                }
            }
        } elseif (strpos($ability, 'MOST COMETS') !== false) {
            if ($this->hasMostOfType($playerId, 'total_comets', '')) {
                if (preg_match('/(\d+) points?/', $ability, $matches)) {
                    $score += (int)$matches[1];
                }
            }
        } elseif (strpos($ability, 'MOST PLANETS') !== false) {
            if ($this->hasMostOfType($playerId, 'total_planets', '')) {
                if (preg_match('/(\d+) points?/', $ability, $matches)) {
                    $score += (int)$matches[1];
                }
            }
        } elseif (strpos($ability, 'MOST total RINGS') !== false) {
            if ($this->hasMostOfType($playerId, 'total_rings', '')) {
                if (preg_match('/(\d+) points?/', $ability, $matches)) {
                    $score += (int)$matches[1];
                }
            }
        } elseif (strpos($ability, 'BLUE PLANET') !== false && strpos($ability, 'for each') !== false) {
            $count = $this->countCardsByType($playerId, 'color', 'BLUE');
            if (preg_match('/(\d+) point/', $ability, $matches)) {
                $score += (int)$matches[1] * $count;
            }
        } elseif (strpos($ability, 'GREEN PLANET') !== false && strpos($ability, 'for each') !== false) {
            $count = $this->countCardsByType($playerId, 'color', 'GREEN');
            if (preg_match('/(\d+) point/', $ability, $matches)) {
                $score += (int)$matches[1] * $count;
            }
        } elseif (strpos($ability, 'RED PLANET') !== false && strpos($ability, 'for each') !== false) {
            $count = $this->countCardsByType($playerId, 'color', 'RED');
            if (preg_match('/(\d+) point/', $ability, $matches)) {
                $score += (int)$matches[1] * $count;
            }
        } elseif (strpos($ability, 'TAN PLANET') !== false && strpos($ability, 'for each') !== false) {
            $count = $this->countCardsByType($playerId, 'color', 'TAN');
            if (preg_match('/(\d+) point/', $ability, $matches)) {
                $score += (int)$matches[1] * $count;
            }
        } elseif (strpos($ability, 'PLANET WITH RING') !== false) {
            $count = 0;
            foreach ($tableau['planets'] as $p) {
                $pInfo = $this->getCardInfo($p);
                if (($pInfo['rings'] ?? 0) > 0) {
                    $count++;
                }
            }
            if (preg_match('/(\d+) point/', $ability, $matches)) {
                $score += (int)$matches[1] * $count;
            }
        } elseif (strpos($ability, 'COMET') !== false && strpos($ability, 'for each') !== false) {
            $count = count($tableau['comets']);
            if (preg_match('/(\d+) point/', $ability, $matches)) {
                $score += (int)$matches[1] * $count;
            }
        } elseif (strpos($ability, 'LARGE PLANET') !== false && strpos($ability, 'for each') !== false) {
            $count = $this->countCardsByType($playerId, 'size', 'LARGE');
            if (preg_match('/(\d+) point/', $ability, $matches)) {
                $score += (int)$matches[1] * $count;
            }
        } elseif (strpos($ability, 'SMALL PLANET') !== false && strpos($ability, 'for each') !== false) {
            $count = $this->countCardsByType($playerId, 'size', 'SMALL');
            if (preg_match('/(\d+) point/', $ability, $matches)) {
                $score += (int)$matches[1] * $count;
            }
        } elseif (strpos($ability, 'MEDIUM PLANET') !== false && strpos($ability, 'for each') !== false) {
            $count = $this->countCardsByType($playerId, 'size', 'MEDIUM');
            if (preg_match('/(\d+) point/', $ability, $matches)) {
                $score += (int)$matches[1] * $count;
            }
        } elseif (strpos($ability, 'PLANET BEFORE') !== false) {
            $planetOrder = $planet['planet_order'] ?? 0;
            $count = 0;
            foreach ($tableau['planets'] as $p) {
                $pOrder = $p['planet_order'] ?? 0;
                if ($pOrder < $planetOrder) {
                    $count++;
                }
            }
            if (preg_match('/(\d+) point/', $ability, $matches)) {
                $score += (int)$matches[1] * $count;
            }
        } elseif (strpos($ability, 'PLANET AFTER') !== false) {
            $planetOrder = $planet['planet_order'] ?? 0;
            $count = 0;
            foreach ($tableau['planets'] as $p) {
                $pOrder = $p['planet_order'] ?? 0;
                if ($pOrder > $planetOrder) {
                    $count++;
                }
            }
            if (preg_match('/(\d+) point/', $ability, $matches)) {
                $score += (int)$matches[1] * $count;
            }
        } elseif (strpos($ability, 'MOON orbiting this planet') !== false) {
            $count = count($moons);
            if (preg_match('/(\d+) points? for each/', $ability, $matches)) {
                $pointsPer = (int)$matches[1];
                $score += $pointsPer * $count;
            }
        } elseif (strpos($ability, 'PLANET with AT LEAST 1 MOON') !== false) {
            $count = 0;
            foreach ($tableau['planets'] as $p) {
                $pMoons = $this->getMoonsForPlanet($p['id'], $tableau['moons']);
                if (count($pMoons) >= 1) {
                    $count++;
                }
            }
            if (preg_match('/(\d+) points? for each/', $ability, $matches)) {
                $pointsPer = (int)$matches[1];
                $score += $pointsPer * $count;
            }
        } elseif (strpos($ability, 'ONLY MEDIUM PLANET') !== false) {
            $mediumCount = $this->countCardsByType($playerId, 'size', 'MEDIUM');
            if ($mediumCount === 1) {
                if (preg_match('/(\d+) points?/', $ability, $matches)) {
                    $score += (int)$matches[1];
                }
            }
        } elseif (strpos($ability, 'ONLY LARGE PLANET') !== false) {
            $largeCount = $this->countCardsByType($playerId, 'size', 'LARGE');
            if ($largeCount === 1) {
                if (preg_match('/(\d+) points?/', $ability, $matches)) {
                    $score += (int)$matches[1];
                }
            }
        } elseif (strpos($ability, 'exactly 2 MOONS') !== false) {
            if (count($moons) === 2) {
                if (preg_match('/(\d+) points?/', $ability, $matches)) {
                    $score += (int)$matches[1];
                }
            }
        } elseif (strpos($ability, 'at least 8 PLANETS') !== false) {
            $planetCount = count($tableau['planets']);
            if ($planetCount >= 8) {
                if (preg_match('/(\d+) points?/', $ability, $matches)) {
                    $score += (int)$matches[1];
                }
            }
        } elseif (strpos($ability, 'SEVENTH PLANET') !== false) {
            $planetOrder = $planet['planet_order'] ?? 0;
            if ($planetOrder === 7) {
                if (preg_match('/(\d+) points?/', $ability, $matches)) {
                    $score += (int)$matches[1];
                }
            }
        } elseif (strpos($ability, 'EIGHTH PLANET') !== false) {
            $planetOrder = $planet['planet_order'] ?? 0;
            if ($planetOrder === 8) {
                if (preg_match('/(\d+) points?/', $ability, $matches)) {
                    $score += (int)$matches[1];
                }
            }
        } elseif (strpos($ability, 'NINTH PLANET') !== false) {
            $planetOrder = $planet['planet_order'] ?? 0;
            if ($planetOrder === 9) {
                if (preg_match('/(\d+) points?/', $ability, $matches)) {
                    $score += (int)$matches[1];
                }
            }
        } elseif (strpos($ability, 'LAST PLANET') !== false) {
            $maxOrder = 0;
            foreach ($tableau['planets'] as $p) {
                $pOrder = $p['planet_order'] ?? 0;
                if ($pOrder > $maxOrder) {
                    $maxOrder = $pOrder;
                }
            }
            $planetOrder = $planet['planet_order'] ?? 0;
            if ($planetOrder === $maxOrder && $maxOrder > 0) {
                if (preg_match('/(\d+) points?/', $ability, $matches)) {
                    $score += (int)$matches[1];
                }
            }
        } elseif (strpos($ability, 'every 2 PLANETS') !== false) {
            $planetCount = count($tableau['planets']);
            $count = (int)floor($planetCount / 2);
            if (preg_match('/(\d+) point/', $ability, $matches)) {
                $score += (int)$matches[1] * $count;
            }
        } elseif (strpos($ability, 'every 3 COMETS') !== false) {
            $cometCount = count($tableau['comets']);
            $count = (int)floor($cometCount / 3);
            if (preg_match('/(\d+) points?/', $ability, $matches)) {
                $score += (int)$matches[1] * $count;
            }
        } elseif (strpos($ability, 'every other 1 POINT VALUE PLANET') !== false) {
            $count = 0;
            foreach ($tableau['planets'] as $p) {
                $pInfo = $this->getCardInfo($p);
                if (($pInfo['points'] ?? 0) === 1) {
                    $count++;
                }
            }
            $count = (int)floor($count / 2); // Every other
            if (preg_match('/(\d+) points?/', $ability, $matches)) {
                $score += (int)$matches[1] * $count;
            }
        } elseif (strpos($ability, 'every set of planets you have of each color') !== false) {
            // Amaterasu - Score points for every complete set (one of each color)
            $blueCount = $this->countCardsByType($playerId, 'color', 'BLUE');
            $greenCount = $this->countCardsByType($playerId, 'color', 'GREEN');
            $redCount = $this->countCardsByType($playerId, 'color', 'RED');
            $tanCount = $this->countCardsByType($playerId, 'color', 'TAN');
            // A "set" is one planet of each color, so count = minimum of all colors
            $setCount = min($blueCount, $greenCount, $redCount, $tanCount);
            if (preg_match('/(\d+) points?/', $ability, $matches)) {
                $score += (int)$matches[1] * $setCount;
            }
        } elseif (strpos($ability, 'only has three different colored planets') !== false) {
            $colors = [];
            foreach ($tableau['planets'] as $p) {
                $pInfo = $this->getCardInfo($p);
                $color = $pInfo['color'] ?? '';
                if ($color && !in_array($color, $colors)) {
                    $colors[] = $color;
                }
            }
            if (count($colors) === 3) {
                if (preg_match('/(\d+) points?/', $ability, $matches)) {
                    $score += (int)$matches[1];
                }
            }
        } elseif (strpos($ability, 'ONLY PLANET with AT LEAST 3 RINGS') !== false) {
            $count = 0;
            foreach ($tableau['planets'] as $p) {
                $pInfo = $this->getCardInfo($p);
                if (($pInfo['rings'] ?? 0) >= 3) {
                    $count++;
                }
            }
            if ($count === 1) {
                $pInfo = $this->getCardInfo($planet);
                if (($pInfo['rings'] ?? 0) >= 3) {
                    if (preg_match('/(\d+) points?/', $ability, $matches)) {
                        $score += (int)$matches[1];
                    }
                }
            }
        } elseif (strpos($ability, 'POINT VALUE or ABILITY of an adjacent planet') !== false) {
            // Echo (TAN) - score either points or ability of adjacent planet
            $adjacentPlanets = $this->getAdjacentPlanets($planet, $tableau['planets']);
            if (!empty($adjacentPlanets)) {
                // Choose the best option - for now, use ability scoring
                // This is complex and might need player choice, but for end-game scoring,
                // we'll use the ability (which is usually better)
                $adjPlanet = $adjacentPlanets[0];
                $adjInfo = $this->getCardInfo($adjPlanet);
                // Score the adjacent planet's ability (recursive call with depth)
                $adjScore = $this->scorePlanetAbility($adjPlanet, $tableau, $playerId, $depth + 1);
                if ($adjScore > ($adjInfo['points'] ?? 0)) {
                    $score += $adjScore;
                } else {
                    $score += ($adjInfo['points'] ?? 0);
                }
            }
        } elseif (strpos($ability, 'Copies the POINTS VALUE and ABILITY') !== false) {
            // Echo (GREEN) - copies previous planet
            $planetOrder = $planet['planet_order'] ?? 0;
            $prevPlanet = null;
            foreach ($tableau['planets'] as $p) {
                $pOrder = $p['planet_order'] ?? 0;
                if ($pOrder < $planetOrder && ($prevPlanet === null || $pOrder > ($prevPlanet['planet_order'] ?? 0))) {
                    $prevPlanet = $p;
                }
            }
            if ($prevPlanet) {
                $prevInfo = $this->getCardInfo($prevPlanet);
                $score += ($prevInfo['points'] ?? 0);
                // Also score the ability (recursive call with depth)
                $score += $this->scorePlanetAbility($prevPlanet, $tableau, $playerId, $depth + 1);
            }
        } elseif (strpos($ability, 'Copy the points and ability of the planet played after') !== false) {
            // Trifecten - copies next planet
            $planetOrder = $planet['planet_order'] ?? 0;
            $nextPlanet = null;
            foreach ($tableau['planets'] as $p) {
                $pOrder = $p['planet_order'] ?? 0;
                if ($pOrder > $planetOrder && ($nextPlanet === null || $pOrder < ($nextPlanet['planet_order'] ?? 0))) {
                    $nextPlanet = $p;
                }
            }
            if ($nextPlanet) {
                $nextInfo = $this->getCardInfo($nextPlanet);
                $score += ($nextInfo['points'] ?? 0);
                // Also score the ability (recursive call with depth)
                $score += $this->scorePlanetAbility($nextPlanet, $tableau, $playerId, $depth + 1);
            }
        } elseif (strpos($ability, 'DOUBLE the POINTS BONUS of one MOON') !== false) {
            // Hectate - doubles the points value of one moon
            // For end-game scoring, choose the moon with highest points value
            $bestMoonPoints = 0;
            foreach ($moons as $moon) {
                $moonInfo = $this->getCardInfo($moon);
                $moonPoints = (int)($moonInfo['points'] ?? 0);
                if ($moonPoints > $bestMoonPoints) {
                    $bestMoonPoints = $moonPoints;
                }
            }
            $score += $bestMoonPoints; // Double = add once more
        } elseif (strpos($ability, 'DOUBLE the POINTS scored from the EFFECT of one MOON') !== false) {
            // Lunamania - doubles the ability/effect score of one moon
            // For end-game scoring, choose the moon with highest ability score
            $bestMoonAbilityScore = 0;
            foreach ($moons as $moon) {
                $moonAbilityScore = $this->scoreMoonAbility($moon, $tableau, $playerId);
                if ($moonAbilityScore > $bestMoonAbilityScore) {
                    $bestMoonAbilityScore = $moonAbilityScore;
                }
            }
            $score += $bestMoonAbilityScore; // Double = add once more
        } elseif (strpos($ability, 'TRIPLE the POINTS VALUE of the first moon') !== false) {
            // Luke - triple the points value of the first moon orbiting this planet
            if (!empty($moons)) {
                $firstMoon = $moons[0]; // First moon (sorted by parent_slot)
                $moonInfo = $this->getCardInfo($firstMoon);
                $moonPoints = (int)($moonInfo['points'] ?? 0);
                // Add 2x more (already counted 1x in basic score)
                $score += $moonPoints * 2;
            }
        } elseif (strpos($ability, 'for each MOON.') !== false) {
            // Threemoona - Score X points for each MOON (total moons in tableau)
            $totalMoons = count($tableau['moons']);
            if (preg_match('/(\d+) points? for each/', $ability, $matches)) {
                $score += (int)$matches[1] * $totalMoons;
            }
        }
        
        // Apply Doagain doubling if present
        if ($hasDoagain) {
            $score *= 2;
        }
        
        return $score;
    }

    /**
     * Calculate ability-based score for a moon
     * Note: This returns the ADDITIONAL score from the moon's ability.
     * Basic moon points are already counted in calculateBasicScore.
     */
    private function scoreMoonAbility(array $moon, array $tableau, int $playerId): int
    {
        $cardInfo = $this->getCardInfo($moon);
        if (!$cardInfo) {
            return 0; // Card info not found
        }
        
        $ability = $cardInfo['ability'] ?? '';
        if (empty($ability) || $ability === '-') {
            return 0; // No ability to score
        }
        
        $score = 0;
        
        $planetId = $moon['parent_id'] ?? null;
        if (!$planetId) {
            return 0;
        }
        
        // Find the planet this moon orbits
        $planet = null;
        foreach ($tableau['planets'] as $p) {
            if ($p['id'] == $planetId) {
                $planet = $p;
                break;
            }
        }
        
        if (!$planet) {
            return 0;
        }
        
        $planetInfo = $this->getCardInfo($planet);
        $moons = $this->getMoonsForPlanet($planetId, $tableau['moons']);
        
        // Handle Timestooer - doubles planet point value (only on SMALL or MEDIUM)
        if (strpos($ability, 'DOUBLE the POINT VALUE of this planet') !== false) {
            $planetSize = $planetInfo['size'] ?? '';
            if ($planetSize === 'SMALL' || $planetSize === 'MEDIUM') {
                $planetPoints = (int)($planetInfo['points'] ?? 0);
                $score += $planetPoints; // Double = add once more (basic already counted)
            }
        }
        
        // Parse moon abilities
        if (strpos($ability, 'MOON orbiting this planet') !== false) {
            $count = count($moons);
            if (preg_match('/(\d+) points? for each/', $ability, $matches)) {
                $pointsPer = (int)$matches[1];
                $score += $pointsPer * $count;
            }
        } elseif (strpos($ability, 'SECOND MOON') !== false) {
            $moonSlot = $moon['parent_slot'] ?? 0;
            // Check if this is the second moon (slot 1, 0-indexed)
            if ($moonSlot === 1 && count($moons) >= 2) {
                if (preg_match('/(\d+) points?/', $ability, $matches)) {
                    $score += (int)$matches[1];
                }
            }
        } elseif (strpos($ability, 'THIRD MOON') !== false) {
            $moonSlot = $moon['parent_slot'] ?? 0;
            // Check if this is the third moon (slot 2, 0-indexed)
            if ($moonSlot === 2 && count($moons) >= 3) {
                if (preg_match('/(\d+) points?/', $ability, $matches)) {
                    $score += (int)$matches[1];
                }
            }
        } elseif (strpos($ability, 'COMET adjacent to the planet') !== false) {
            $adjacentComets = $this->getAdjacentComets($planet, $tableau['comets']);
            $count = count($adjacentComets);
            if (preg_match('/(\d+) point/', $ability, $matches)) {
                $score += (int)$matches[1] * $count;
            }
        } elseif (strpos($ability, 'RED planet') !== false && strpos($ability, 'for each') !== false) {
            $count = $this->countCardsByType($playerId, 'color', 'RED');
            if (preg_match('/(\d+) point/', $ability, $matches)) {
                $score += (int)$matches[1] * $count;
            }
        } elseif (strpos($ability, 'BLUE planet') !== false && strpos($ability, 'for each') !== false) {
            $count = $this->countCardsByType($playerId, 'color', 'BLUE');
            if (preg_match('/(\d+) point/', $ability, $matches)) {
                $score += (int)$matches[1] * $count;
            }
        } elseif (strpos($ability, 'GREEN planet') !== false && strpos($ability, 'for each') !== false) {
            $count = $this->countCardsByType($playerId, 'color', 'GREEN');
            if (preg_match('/(\d+) point/', $ability, $matches)) {
                $score += (int)$matches[1] * $count;
            }
        } elseif (strpos($ability, 'TAN planet') !== false && strpos($ability, 'for each') !== false) {
            $count = $this->countCardsByType($playerId, 'color', 'TAN');
            if (preg_match('/(\d+) point/', $ability, $matches)) {
                $score += (int)$matches[1] * $count;
            }
        } elseif (strpos($ability, 'panet with at least one MOON') !== false) {
            $count = 0;
            foreach ($tableau['planets'] as $p) {
                $pMoons = $this->getMoonsForPlanet($p['id'], $tableau['moons']);
                if (count($pMoons) >= 1) {
                    $count++;
                }
            }
            if (preg_match('/(\d+) point/', $ability, $matches)) {
                $score += (int)$matches[1] * $count;
            }
        } elseif (strpos($ability, 'only planet of its color') !== false) {
            $planetColor = $planetInfo['color'] ?? '';
            $count = $this->countCardsByType($playerId, 'color', $planetColor);
            if ($count === 1) {
                if (preg_match('/(\d+) point/', $ability, $matches)) {
                    $score += (int)$matches[1];
                }
            }
        } elseif (strpos($ability, 'other planet of this planet\'s color') !== false) {
            $planetColor = $planetInfo['color'] ?? '';
            $count = $this->countCardsByType($playerId, 'color', $planetColor);
            $count--; // Exclude this planet
            if (preg_match('/(\d+) point/', $ability, $matches)) {
                $score += (int)$matches[1] * $count;
            }
        } elseif (strpos($ability, 'RING this planet has') !== false) {
            $rings = (int)($planetInfo['rings'] ?? 0);
            if (preg_match('/(\d+) point/', $ability, $matches)) {
                $score += (int)$matches[1] * $rings;
            }
        } elseif (strpos($ability, 'CARD in your hand') !== false) {
            // This would require hand data - for end game, hand is usually empty
            // But we'll leave this for now as it's not typically scored at end game
        }
        
        return $score;
    }

    /**
     * Calculate full score for a player (basic points + ability-based scoring)
     */
    public function calculatePlayerScore(int $playerId): int
    {
        // Start with basic points
        $score = $this->calculateBasicScore($playerId);
        
        // Get tableau structure
        $tableau = $this->getTableauCards($playerId);
        
        // Find planets affected by Giganta (next planet scores no points)
        $gigantaAffectedPlanets = [];
        foreach ($tableau['planets'] as $planet) {
            $cardInfo = $this->getCardInfo($planet);
            if ($cardInfo['name'] === 'Giganta') {
                $planetOrder = $planet['planet_order'] ?? 0;
                // Find the next planet
                foreach ($tableau['planets'] as $p) {
                    $pOrder = $p['planet_order'] ?? 0;
                    if ($pOrder > $planetOrder) {
                        if (!isset($gigantaAffectedPlanets[$p['id']]) || $pOrder < $gigantaAffectedPlanets[$p['id']]['order']) {
                            $gigantaAffectedPlanets[$p['id']] = ['planet' => $p, 'order' => $pOrder];
                        }
                    }
                }
            }
        }
        
        // Remove basic points for Giganta-affected planets
        foreach ($gigantaAffectedPlanets as $affected) {
            $affectedPlanet = $affected['planet'];
            $affectedInfo = $this->getCardInfo($affectedPlanet);
            $affectedPoints = (int)($affectedInfo['points'] ?? 0);
            $score -= $affectedPoints; // Remove basic points
            
            // Also remove points from moons/comets attached to this planet
            foreach ($tableau['moons'] as $moon) {
                if (($moon['parent_id'] ?? null) == $affectedPlanet['id']) {
                    $moonInfo = $this->getCardInfo($moon);
                    $moonPoints = (int)($moonInfo['points'] ?? 0);
                    $score -= $moonPoints;
                }
            }
            foreach ($tableau['comets'] as $comet) {
                if (($comet['parent_id'] ?? null) == $affectedPlanet['id']) {
                    $cometInfo = $this->getCardInfo($comet);
                    $cometPoints = (int)($cometInfo['points'] ?? 0);
                    $score -= $cometPoints;
                }
            }
        }
        
        // Score planet abilities (start with depth 0)
        foreach ($tableau['planets'] as $planet) {
            // Skip ability scoring for Giganta-affected planets
            if (isset($gigantaAffectedPlanets[$planet['id']])) {
                continue;
            }
            $score += $this->scorePlanetAbility($planet, $tableau, $playerId, 0);
        }
        
        // Score moon abilities (skip moons on Giganta-affected planets)
        foreach ($tableau['moons'] as $moon) {
            $moonPlanetId = $moon['parent_id'] ?? null;
            if ($moonPlanetId && isset($gigantaAffectedPlanets[$moonPlanetId])) {
                continue; // Skip moons on affected planets
            }
            $score += $this->scoreMoonAbility($moon, $tableau, $playerId);
        }
        
        // Handle Rahu - triple adjacent comet points
        foreach ($tableau['planets'] as $planet) {
            $cardInfo = $this->getCardInfo($planet);
            if ($cardInfo['name'] === 'Rahu') {
                $adjacentComets = $this->getAdjacentComets($planet, $tableau['comets']);
                foreach ($adjacentComets as $comet) {
                    $cometInfo = $this->getCardInfo($comet);
                    $cometPoints = (int)($cometInfo['points'] ?? 0);
                    // Add 2x more (already counted 1x in basic score)
                    $score += $cometPoints * 2;
                }
            }
        }
        
        return $score;
    }

    /**
     * Update scores for all players
     */
    public function updateAllScores(): void
    {
        $players = $this->getCollectionFromDb(
            "SELECT player_id FROM player"
        );
        
        foreach ($players as $playerId => $player) {
            $score = $this->calculatePlayerScore((int)$playerId);
            $this->DbQuery(
                "UPDATE player SET player_score = $score WHERE player_id = $playerId"
            );
        }
    }

    /*************************************************
     * ACTION HELPERS
     *************************************************/
    // In Game.php, add helper functions:

    public function grantDraftAction(int $playerId, int $count = 1)
    {
        $this->draft_actions->inc($playerId, $count);
        
        $this->notify->all(
            'actionGranted',
            clienttranslate('${player_name} gains ${count} draft action(s)'),
            [
                'player_id' => $playerId,
                'player_name' => $this->getPlayerNameById($playerId),
                'count' => $count,
                'action_type' => 'draft',
                'new_value' => $this->draft_actions->get($playerId)
            ]
        );
    }

    public function grantDrawAction(int $playerId, int $count = 1)
    {
        $this->draw_actions->inc($playerId, $count);
        
        $this->notify->all(
            'actionGranted',
            clienttranslate('${player_name} gains ${count} draw action(s)'),
            [
                'player_id' => $playerId,
                'player_name' => $this->getPlayerNameById($playerId),
                'count' => $count,
                'action_type' => 'draw',
                'new_value' => $this->draw_actions->get($playerId)
            ]
        );
    }

    public function grantPlayAction(int $playerId, int $count = 1)
    {
        $this->play_actions->inc($playerId, $count);
        
        $this->notify->all(
            'actionGranted',
            clienttranslate('${player_name} gains ${count} play action(s)'),
            [
                'player_id' => $playerId,
                'player_name' => $this->getPlayerNameById($playerId),
                'count' => $count,
                'action_type' => 'play',
                'new_value' => $this->play_actions->get($playerId)
            ]
        );
    }

    public function grantOpenAction(int $playerId, int $count = 1)
    {
        $this->open_actions->inc($playerId, $count);
        
        $this->notify->all(
            'actionGranted',
            clienttranslate('${player_name} gains ${count} action(s)'),
            [
                'player_id' => $playerId,
                'player_name' => $this->getPlayerNameById($playerId),
                'count' => $count,
                'action_type' => 'open',
                'new_value' => $this->open_actions->get($playerId)
            ]
        );
    }

    /**
     * Process comet abilities when played - grants actions to player
     * @param array $card The comet card being played
     * @param int $playerId The player who played the comet
     * @param int|null $adjacentPlanetId The planet this comet is adjacent to (for moon/ring counting)
     */
    public function getCardActions(array $card, int $playerId, ?int $adjacentPlanetId = null)
    {
        // Only process comets
        if ($card['type'] !== 'comet') {
            return;
        }

        $cardNum = $card['type_arg'];
        
        // Get adjacent planet info if needed for moon/ring counting
        $adjacentMoonCount = 0;
        $adjacentRingCount = 0;
        if ($adjacentPlanetId !== null) {
            $tableau = $this->getTableauCards($playerId);
            // Find the adjacent planet
            foreach ($tableau['planets'] as $planet) {
                if ($planet['id'] == $adjacentPlanetId) {
                    // Count moons orbiting this planet
                    $moons = $this->getMoonsForPlanet($adjacentPlanetId, $tableau['moons']);
                    $adjacentMoonCount = count($moons);
                    // Get ring count from planet info
                    $planetInfo = $this->getCardInfo($planet);
                    $adjacentRingCount = (int)($planetInfo['rings'] ?? 0);
                    break;
                }
            }
        }

        // Process based on comet number (61-85)
        switch ($cardNum) {
            case 61: // Comet1: DRAFT 2 CARDS
                $this->grantDraftAction($playerId, 2);
                break;
            
            case 62: // Comet2: DRAFT A CARD and then PLAY A CARD
                $this->grantDraftAction($playerId, 1);
                $this->grantPlayAction($playerId, 1);
                break;
            
            case 63: // Comet3: DRAFT A CARD for each bonus token you currently hold
                // TODO: implement bonus token counting when bonus tokens are added
                // For now, grant 0 actions (no bonus tokens implemented yet)
                break;
            
            case 64: // Comet4: DRAFT A CARD for each MOON orbiting adjacent planet
                if ($adjacentMoonCount > 0) {
                    $this->grantDraftAction($playerId, $adjacentMoonCount);
                }
                break;
            
            case 65: // Comet5: DRAFT A CARD for each RING adjacent planet has
                if ($adjacentRingCount > 0) {
                    $this->grantDraftAction($playerId, $adjacentRingCount);
                }
                break;
            
            case 66: // Comet6: If able, place the top card of the Solar Deck into your Solar System. Otherwise, draw it.
                // Special action - requires checking if top card can be placed
                // TODO: Implement deck-to-tableau or deck-to-hand logic
                // For now, grant a draw action as fallback
                $this->grantDrawAction($playerId, 1);
                break;
            
            case 67: // Comet7: DRAW 1 CARD
                $this->grantDrawAction($playerId, 1);
                break;
            
            case 68: // Comet8: DRAW 2 CARDS
                $this->grantDrawAction($playerId, 2);
                break;
            
            case 69: // Comet9: DRAW A CARD and then DRAFT A CARD
                $this->grantDrawAction($playerId, 1);
                $this->grantDraftAction($playerId, 1);
                break;
            
            case 70: // Comet10: DRAW A CARD and then PLAY A CARD
                $this->grantDrawAction($playerId, 1);
                $this->grantPlayAction($playerId, 1);
                break;
            
            case 71: // Comet11: DRAW A CARD for EACH MOON orbiting adjacent planet
                if ($adjacentMoonCount > 0) {
                    $this->grantDrawAction($playerId, $adjacentMoonCount);
                }
                break;
            
            case 72: // Comet12: DRAW A CARD for each RING adjacent planet has
                if ($adjacentRingCount > 0) {
                    $this->grantDrawAction($playerId, $adjacentRingCount);
                }
                break;
            
            case 73: // Comet13: Place a COMET card from the Solar Row into your Solar System
                // Special action - player picks a comet from Solar Row to place
                // TODO: Implement Solar Row comet selection state
                // This doesn't grant regular actions - it's a special placement
                break;
            
            case 74: // Comet14: DRAW the top card of the discard pile
                // Set flag to restrict draws to discard pile only
                $this->setGameStateValue('draw_from_discard_only', $playerId);
                $this->grantDrawAction($playerId, 1);
                break;
            
            case 75: // Comet15: DRAW the top three cards of the discard pile
                // Set flag to restrict draws to discard pile only
                $this->setGameStateValue('draw_from_discard_only', $playerId);
                $this->grantDrawAction($playerId, 3);
                break;
            
            case 76: // Comet16: DRAW the top two cards of the discard pile
                // Set flag to restrict draws to discard pile only
                $this->setGameStateValue('draw_from_discard_only', $playerId);
                $this->grantDrawAction($playerId, 2);
                break;
            
            case 77: // Comet17: Place a PLANET card from the Solar Row into your Solar System
                // Special action - player picks a planet from Solar Row to place
                // TODO: Implement Solar Row planet selection state
                // This doesn't grant regular actions - it's a special placement
                break;
            
            case 78: // Comet18: PLAY 2 CARDS
                $this->grantPlayAction($playerId, 2);
                break;
            
            case 79: // Comet19: PLAY A CARD for each MOON orbiting adjacent planet
                if ($adjacentMoonCount > 0) {
                    $this->grantPlayAction($playerId, $adjacentMoonCount);
                }
                break;
            
            case 80: // Comet20: PLAY A CARD for each RING adjacent planet has
                if ($adjacentRingCount > 0) {
                    $this->grantPlayAction($playerId, $adjacentRingCount);
                }
                break;
            
            case 81: // Comet21: Place a MOON card from the Solar Row into your Solar System
                // Special action - player picks a moon from Solar Row to place
                // TODO: Implement Solar Row moon selection state
                // This doesn't grant regular actions - it's a special placement
                break;
            
            case 82: // Comet22: PLAY A CARD
                $this->grantPlayAction($playerId, 1);
                break;
            
            case 83: // Comet23: PLAY up to TWO PLANETS
                // TODO: Restrict to planet-only plays
                $this->grantPlayAction($playerId, 2);
                break;
            
            case 84: // Comet24: REFRESH A SUN ABILITY
                // Reset the player's sun ability so they can use it again
                $this->sun_ability_used->set($playerId, 0);
                $this->notify->all(
                    'sunAbilityRefreshed',
                    clienttranslate('${player_name} refreshes their Sun Ability'),
                    [
                        'player_id' => $playerId,
                        'player_name' => $this->getPlayerNameById($playerId),
                    ]
                );
                break;
            
            case 85: // Comet25: SOLAR FLARE and then DRAFT A CARD
                // Reset Solar Flare so player can use it again
                $this->solar_flare_used->set($playerId, 0);
                $this->notify->all(
                    'solarFlareRefreshed',
                    clienttranslate('${player_name} performs a Solar Flare'),
                    [
                        'player_id' => $playerId,
                        'player_name' => $this->getPlayerNameById($playerId),
                    ]
                );
                // Then grant a draft action
                $this->grantDraftAction($playerId, 1);
                break;
        }
    }

}
