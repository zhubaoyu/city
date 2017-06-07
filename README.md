Simple City

Each player may have 1-10 cities.  Each city has the following properties:

    * Located somewhere on a 100x100 coordinate plane, from (0,0) to (99,99). 
    * Produces food at a fixed rate constantly (granularity = seconds)
    * Collects tax in "gold" every hour (starting from the time the city was created)
    * Population fluctuates after each tax collection:
          o Population increase by 5% if population < tax rate * 1000, minimum increase by 1 (up to 1000).
          o Population decrease by 5% if population > tax rate * 1000, minimum decrease by 1 (down to 0).


Each city keeps track of how much food and gold it has. A new city starts with 100 population, no food, and no gold and an initial 20% tax rate.

Each player always have exactly one capital city

    * The capital city produces 10,000 food per hour.
    * A regular city produces 1,000 food per hour.


At any time, the player can switch which city is the capital, and can change tax rate.

You task is to implement the simple city with following features:

   1. Function to create a additional city for a player (assuming the player already has at least one capital city) at a given coordinatethis is a demo of city in games
   2. Function that returns the current food, gold, population, and tax rate of all cities belonging to a player.
   3. Function to change tax rate for any city
   4. Function to change which city is the capital of a player.

Please include database schema, and everything needed to store/track/update the states of cities.


Medium City

Each city trains its own soldiers in batches.  Soldiers are not counted in the population

    * A city may queue up to 5 batches of training at any given time, where each batch consists of any number of soldiers of the same type. 
    * Queuing a batch of soldiers to train reduces the population by the same amount of soldiers being trained.  
    * When one batch of training completes, the next batch begins training.


The following soldier types exist:

    * Pikemen - Training cost 1 gold and 3 minutes per soldier in the batch.  When training is complete, each soldier consumes 10 food per hour.
    * Archer - Training cost 3 gold and 12 minutes per soldier in the batch. When training is complete, each soldier consumes 13 food per hour.
    * Cavalry - Training cost 10 gold and 50 minutes per soldier in the batch. When training is complete, each soldier consumes 30 food per hour.


Note: even though food consumption is measured by the hour, the soldiers actually eat constantly (granularity = seconds).

If soldiers eat more food than the city can produce, and the city runs out of food, food stays at 0 (does not go negative), and 10% of soldiers of each type will disappear in that city the next time tax is collected. 
our task is to, forking from your answer to question 1, add the following additional features:

   1. Function to add a new batch of training for a city
   2. Function to cancel a queued batch of training that has not started yet.
   3. Extend the player cities look up function (Q1 Pt2) to include soldier count of each type, as well as progress of current training (if any) and information on queued training batches
Note: Your answer to question 1 should be kept separate as a simple city system.


 Advanced City

A player may send soldiers from one of their cities to attack cities of other players.

    * An attack may consist of any mixture of Pikemen, Archer, and Cavalry
    * An attack must travel the (straight-line) distance between the two cities.
    * The speed the attack travels depends on the slowest moving soldier type in the attack.
          o Pikeman move 1.5 coordinates per minute
          o Archer move 2 coordinates per minute
          o Cavalry move 10 coordinates per minute
    * When the attack arrives at the destination city, all soldiers not in training in the destination city will fight
    * Assume a blackbox function exists to decide how many soldiers on each side survives (can be random).
    * After the attack happens, any surviving soldiers travel back to the starting city at the original speed
    * Soldiers continue to eat directly from the city even when they are out on an attack.  However after the attack, when soldiers are returning home, only surviving solders eat.
    * Each city can have 5 attacks going out + coming back at once


Your task is to, forking from your answer to question 2, add the following additional features:

   1. Function to send out attack from one city to another player's city, using blackbox function to determine survivors and plunder
   2. Extend the player cities look up function (Q1 Pt2 / Q2 Pt3) to include status of attacks (outgoing/returning) from the player's cities, and warning of attacks to any of the player's city.

